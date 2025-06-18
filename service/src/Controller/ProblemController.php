<?php

namespace App\Controller;

use App\DatabaseManager\FindProblemsByAuthorId;
use App\Entity\AdminMessage;
use App\Entity\PrivateAccess;
use App\Entity\PrivateProblem;
use App\Entity\Problem;
use App\Entity\User;
use App\Service\CodeExecutor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ProblemController extends AbstractController
{
    #[Route('/problems', name: 'problems_list', methods: ['GET'])]
    public function listProblems(Request $request, EntityManagerInterface $entityManager): Response
    {
        $authorId = $request->query->get('author_id');
        
        $adminMessage = $entityManager->getRepository(AdminMessage::class)
            ->findOneBy([], ['createdAt' => 'DESC']);
            
        $response = $this->render('problem/list.html.twig', [
            'selectedAuthor' => $authorId,
            'adminMessage' => $adminMessage
        ]);
        $response->setSharedMaxAge(30);

        return $response;
    }
    
    #[Route('/api/problems', name: 'get_problems_data', methods: ['POST'])]
    public function getProblemsData(Request $request, FindProblemsByAuthorId $findProblemsByAuthorId): JsonResponse
    {
        $authorId = $request->request->get('author_id');
        
        return new JsonResponse($findProblemsByAuthorId->execute($authorId));
    }
    
    #[Route('/problems/details/{id}', name: 'problem_detail', methods: ['GET'])]
    public function problemDetail(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $sessionUserId = $request->getSession()->get('user_id');
        $problem = $entityManager->getRepository(Problem::class)->find($id);
        
        if (!$problem) {
            $this->addFlash('error', 'Problem not found');
            return $this->redirectToRoute('problems_list');
        }
        
        if (!$problem->isPublished() && $problem->getAuthor()->getId() != $sessionUserId) {
            $this->addFlash('error', 'You do not have permission to view this problem');
            return $this->redirectToRoute('problems_list');
        }

        $testCases = $problem->getTestCases();
        $expectedOutputs = $problem->getExpectedOutputs();
        
        $test_examples = [];
        $maxExamplesToShow = min(2, count($testCases));
        
        for ($i = 0; $i < $maxExamplesToShow; $i++) {
            $test_examples[json_encode($testCases[$i])] = json_encode($expectedOutputs[$i]);
        }
        
        $previousSolution = null;
        if ($sessionUserId) {
            $solutionPath = sprintf('%s/submissions/%s/%d/solution.py', getcwd(), $sessionUserId, $id);
            if (file_exists($solutionPath)) {
                $previousSolution = file_get_contents($solutionPath);
            }
        }
        
        return $this->render('problem/detail.html.twig', [
            'problem' => $problem,
            'test_examples' => $test_examples,
            'previous_solution' => $previousSolution
        ]);
    }
    
    #[Route('/problems/details/{id}/submit', name: 'submit_solution', methods: ['POST'])]
    public function submitSolution(Request $request, EntityManagerInterface $entityManager, CodeExecutor $codeExecutor, int $id): JsonResponse
    {
        $sessionUserId = $request->getSession()->get('user_id');
        if (!$sessionUserId) {
            $this->redirectToRoute('login');
        }
        
        $problem = $entityManager->getRepository(Problem::class)->find($id);
        
        if (!$problem) {
            return new JsonResponse(['error' => 'Problem not found'], 404);
        }
        
        $code = $request->request->get('code');
        
        if (empty($code)) {
            return new JsonResponse(['error' => 'Code cannot be empty'], 400);
        }

        $results = $codeExecutor->executeUserCode($code, $problem, $sessionUserId);
        
        return new JsonResponse(['results' => $results]);
    }

    
    #[Route('/problems/drafts', name: 'my_drafts', methods: ['GET'])]
    public function myDrafts(Request $request, EntityManagerInterface $entityManager): Response
    {
        $userId = $request->getSession()->get('user_id');
        
        $drafts = $entityManager->getRepository(Problem::class)->findBy([
            'author' => $userId,
            'isPublished' => false
        ]);

        return $this->render('problem/drafts.html.twig', [
            'drafts' => $drafts
        ]);
    }
    
    #[Route('/problems/create', name: 'problem_create', methods: ['GET'])]
    public function createProblemForm(): Response
    {
        return $this->render('problem/create.html.twig');
    }
    
    #[Route('/problems/create', name: 'problem_create_post', methods: ['POST'])]
    public function createProblem(Request $request, EntityManagerInterface $entityManager): Response
    {
        $title = $request->request->get('title');
        $title = substr($title, 0, 255);

        $description = $request->request->get('description');
        $description = substr($description, 0, 1000);

        $difficulty = $request->request->get('difficulty');
        $testCases = $request->request->get('testCases');
        $expectedOutputs = $request->request->get('expectedOutputs');
        $maxRuntime = floatval($request->request->get('maxRuntime', 1.0));
        $isPublished = $request->request->getBoolean('isPublished', false);
        $isPrivate = $request->request->getBoolean('isPrivate', false);
        $accessUsers = $request->request->get('accessUsers');
        
        if (empty($title) || empty($description) || empty($difficulty) || 
            empty($testCases) || empty($expectedOutputs)) {
            
            if ($request->headers->get('Accept') === 'application/json') {
                return $this->json([
                    'success' => false,
                    'message' => 'All fields are required'
                ], 400);
            }
            
            $this->addFlash('error', 'All fields are required');
            return $this->redirectToRoute('problem_create');
        }
        
        $testCasesArray = json_decode($testCases, true);
        $expectedOutputsArray = json_decode($expectedOutputs, true);
        
        if (!$testCasesArray || !$expectedOutputsArray) {
            if ($request->headers->get('Accept') === 'application/json') {
                return $this->json([
                    'success' => false,
                    'message' => 'Invalid format for test cases or expected outputs'
                ], 400);
            }
            
            $this->addFlash('error', 'Invalid format for test cases or expected outputs');
            return $this->redirectToRoute('problem_create');
        }
        
        $userId = $request->getSession()->get('user_id');
        $user = $entityManager->getRepository(User::class)->find($userId);
        
        if ($user->isAdmin() && !$isPrivate) {
            $this->addFlash('error', 'Admin users are not allowed to create public problems');
            return $this->redirectToRoute('problem_create');
        }
        
        if ($isPrivate) {
            $problem = new PrivateProblem();
            $problem->setTitle($title);
            $problem->setDescription($description);
            $problem->setDifficulty($difficulty);
            $problem->setTestCases($testCasesArray);
            $problem->setExpectedOutputs($expectedOutputsArray);
            $problem->setMaxRuntime($maxRuntime);
            $problem->setAuthor($user);
            
            $entityManager->persist($problem);
            $entityManager->flush();
            
            if (!empty($accessUsers)) {
                $usernames = array_map('trim', explode(',', $accessUsers));
                foreach ($usernames as $username) {
                    $accessUser = $entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
                    if ($accessUser) {
                        $access = new PrivateAccess();
                        $access->setProblem($problem);
                        $access->setUser($accessUser);
                        $entityManager->persist($access);
                    }
                }
                $entityManager->flush();
            }
            
            if ($request->headers->get('Accept') === 'application/json') {
                return $this->json([
                    'success' => true,
                    'message' => 'Private problem created successfully',
                    'problem_id' => $problem->getId()
                ]);
            }
            
            $this->addFlash('success', 'Private problem created successfully');
            return $this->redirectToRoute('private_problems_list');
        } else {
            $problem = new Problem();
            $problem->setTitle($title);
            $problem->setDescription($description);
            $problem->setDifficulty($difficulty);
            $problem->setTestCases($testCasesArray);
            $problem->setExpectedOutputs($expectedOutputsArray);
            $problem->setMaxRuntime($maxRuntime);
            $problem->setIsPublished($isPublished);
            $problem->setAuthor($user);
            
            $entityManager->persist($problem);
            $entityManager->flush();
            
            if ($request->headers->get('Accept') === 'application/json') {
                return $this->json([
                    'success' => true,
                    'message' => 'Problem created successfully',
                    'problem_id' => $problem->getId()
                ]);
            }
            
            $this->addFlash('success', 'Problem created successfully');
            return $this->redirectToRoute('problems_list');
        }
    }
    
    #[Route('/problems/{id}/edit', name: 'problem_edit', methods: ['GET'])]
    public function editProblemForm(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $userId = $request->getSession()->get('user_id');
        $problem = $entityManager->getRepository(Problem::class)->find($id);
        
        if (!$problem) {
            $this->addFlash('error', 'Problem not found');
            return $this->redirectToRoute('problems_list');
        }
        
        if ($problem->getAuthor()->getId() != $userId) {
            $this->addFlash('error', 'You can only edit your own problems');
            return $this->redirectToRoute('problems_list');
        }
        
        $testCasesJson = json_encode($problem->getTestCases());
        $expectedOutputsJson = json_encode($problem->getExpectedOutputs());
        
        return $this->render('problem/edit.html.twig', [
            'problem' => $problem,
            'testCasesJson' => $testCasesJson,
            'expectedOutputsJson' => $expectedOutputsJson
        ]);
    }
    
    #[Route('/problems/{id}/edit', name: 'problem_edit_post', methods: ['POST'])]
    public function updateProblem(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $userId = $request->getSession()->get('user_id');
        $problem = $entityManager->getRepository(Problem::class)->find($id);
        
        if (!$problem) {
            $this->addFlash('error', 'Problem not found');
            return $this->redirectToRoute('problems_list');
        }
        
        if ($problem->getAuthor()->getId() != $userId) {
            $this->addFlash('error', 'You can only edit your own problems');
            return $this->redirectToRoute('problems_list');
        }
        
        $title = $request->request->get('title');
        $title = substr($title, 0, 255);
        
        $description = $request->request->get('description');
        $description = substr($description, 0, 1000);
        
        $difficulty = $request->request->get('difficulty');
        $testCases = $request->request->get('testCases');
        $expectedOutputs = $request->request->get('expectedOutputs');
        $maxRuntime = floatval($request->request->get('maxRuntime', 1.0));
        $isPublished = $request->request->getBoolean('isPublished', false);
        
        if (empty($title) || empty($description) || empty($difficulty) || 
            empty($testCases) || empty($expectedOutputs)) {
            $this->addFlash('error', 'All fields are required');
            return $this->redirectToRoute('problem_edit', ['id' => $id]);
        }
        
        $testCasesArray = json_decode($testCases, true);
        $expectedOutputsArray = json_decode($expectedOutputs, true);
        
        if (!$testCasesArray || !$expectedOutputsArray) {
            $this->addFlash('error', 'Invalid format for test cases or expected outputs');
            return $this->redirectToRoute('problem_edit', ['id' => $id]);
        }
        

        $problem->setTitle($title);
        $problem->setDescription($description);
        $problem->setDifficulty($difficulty);
        $problem->setTestCases($testCasesArray);
        $problem->setExpectedOutputs($expectedOutputsArray);
        $problem->setMaxRuntime($maxRuntime);
        $problem->setIsPublished($isPublished);
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Problem updated successfully');
        
        if (!$isPublished) {
            return $this->redirectToRoute('my_drafts');
        }
        return $this->redirectToRoute('problems_list');
    }
    
    #[Route('/problems/{id}/publish', name: 'problem_publish', methods: ['POST'])]
    public function publishProblem(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $userId = $request->getSession()->get('user_id');
        $problem = $entityManager->getRepository(Problem::class)->find($id);
        
        if (!$problem) {
            $this->addFlash('error', 'Problem not found');
            return $this->redirectToRoute('my_drafts');
        }
        
        if ($problem->getAuthor()->getId() != $userId) {
            $this->addFlash('error', 'You can only publish your own problems');
            return $this->redirectToRoute('my_drafts');
        }
        
        $problem->setIsPublished(true);
        $entityManager->flush();
        
        $this->addFlash('success', 'Problem published successfully');
        return $this->redirectToRoute('problems_list');
    }
    
    #[Route('/private-problems', name: 'private_problems_list', methods: ['GET'])]
    public function listPrivateProblems(Request $request, EntityManagerInterface $entityManager): Response
    {
        $userId = $request->getSession()->get('user_id');
        if (!$userId) {
            return $this->redirectToRoute('login');
        }
        
        $ownProblems = $entityManager->getRepository(PrivateProblem::class)->findBy([
            'author' => $userId
        ]);

        $query = $entityManager->createQueryBuilder()
            ->select('p')
            ->from(PrivateProblem::class, 'p')
            ->join(PrivateAccess::class, 'pa', 'WITH', 'p.id = pa.problem')
            ->where('pa.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery();
        
        $sharedProblems = $query->getResult();
        
        return $this->render('problem/private_list.html.twig', [
            'ownProblems' => $ownProblems,
            'sharedProblems' => $sharedProblems
        ]);
    }
    
    #[Route('/private-problems/details/{id}', name: 'private_problem_detail', methods: ['GET'])]
    public function privateProblemDetail(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $sessionUserId = $request->getSession()->get('user_id');
        if (!$sessionUserId) {
            return $this->redirectToRoute('login');
        }
        
        $problem = $entityManager->getRepository(PrivateProblem::class)->find($id);
        
        if (!$problem) {
            $this->addFlash('error', 'Private problem not found');
            return $this->redirectToRoute('private_problems_list');
        }
        
        $hasAccess = false;
        
        if ($problem->getAuthor()->getId() == $sessionUserId) {
            $hasAccess = true;
        } else {
            $accessQuery = $entityManager->createQueryBuilder()
                ->select('pa')
                ->from(PrivateAccess::class, 'pa')
                ->where('pa.problem = :problemId AND pa.user = :userId')
                ->setParameter('problemId', $id)
                ->setParameter('userId', $sessionUserId)
                ->getQuery();
            
            $access = $accessQuery->getOneOrNullResult();
            if ($access) {
                $hasAccess = true;
            }
        }
        
        if (!$hasAccess) {
            $this->addFlash('error', 'You do not have permission to view this private problem');
            return $this->redirectToRoute('private_problems_list');
        }

        $testCases = $problem->getTestCases();
        $expectedOutputs = $problem->getExpectedOutputs();
        
        $test_examples = [];
        $maxExamplesToShow = min(2, count($testCases));
        
        for ($i = 0; $i < $maxExamplesToShow; $i++) {
            $test_examples[json_encode($testCases[$i])] = json_encode($expectedOutputs[$i]);
        }
        
        $previousSolution = null;
        if ($sessionUserId) {
            $solutionPath = sprintf('%s/submissions/%s/private_%d/solution.py', getcwd(), $sessionUserId, $id);
            if (file_exists($solutionPath)) {
                $previousSolution = file_get_contents($solutionPath);
            }
        }
        
        return $this->render('problem/detail.html.twig', [
            'problem' => $problem,
            'test_examples' => $test_examples,
            'previous_solution' => $previousSolution,
            'is_private' => true
        ]);
    }
    
    #[Route('/private-problems/details/{id}/submit', name: 'submit_private_solution', methods: ['POST'])]
    public function submitPrivateSolution(Request $request, EntityManagerInterface $entityManager, CodeExecutor $codeExecutor, int $id): JsonResponse
    {
        $sessionUserId = $request->getSession()->get('user_id');
        if (!$sessionUserId) {
            return new JsonResponse(['error' => 'User not logged in'], 401);
        }
        
        $problem = $entityManager->getRepository(PrivateProblem::class)->find($id);
        
        if (!$problem) {
            return new JsonResponse(['error' => 'Private problem not found'], 404);
        }
        
        $hasAccess = false;
        
        if ($problem->getAuthor()->getId() == $sessionUserId) {
            $hasAccess = true;
        } else {
            $accessQuery = $entityManager->createQueryBuilder()
                ->select('pa')
                ->from(PrivateAccess::class, 'pa')
                ->where('pa.problem = :problemId AND pa.user = :userId')
                ->setParameter('problemId', $id)
                ->setParameter('userId', $sessionUserId)
                ->getQuery();
            
            $access = $accessQuery->getOneOrNullResult();
            if ($access) {
                $hasAccess = true;
            }
        }
        
        if (!$hasAccess) {
            return new JsonResponse(['error' => 'You do not have permission to submit to this private problem'], 403);
        }
        
        $code = $request->request->get('code');
        
        if (empty($code)) {
            return new JsonResponse(['error' => 'Code cannot be empty'], 400);
        }

        $results = $codeExecutor->executeUserCodeForPrivateProblem($code, $problem, $sessionUserId);
        
        return new JsonResponse(['results' => $results]);
    }
} 