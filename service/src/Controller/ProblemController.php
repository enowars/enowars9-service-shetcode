<?php

namespace App\Controller;

use App\Command\CodeExecutor;
use App\Entity\Problem;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ProblemController extends AbstractController
{
    #[Route('/problems', name: 'problems_list', methods: ['GET'])]
    public function listProblems(Request $request, EntityManagerInterface $entityManager): Response
    {
        $authorId = $request->query->get('author_id');
        $users = $entityManager->getRepository(User::class)->findAll();
            
        return $this->render('problem/list.html.twig', [
            'users' => $users,
            'selectedAuthor' => $authorId
        ]);
    }
    
    #[Route('/api/problems', name: 'get_problems_data', methods: ['POST'])]
    public function getProblemsData(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $sessionUserId = $request->getSession()->get('user_id');
        
        if (!$sessionUserId) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $authorId = $request->request->get('author_id');
        
        if ($authorId) {
            $connection = $entityManager->getConnection();
            $sql = "SELECT p.* FROM problems p WHERE p.is_published = true AND p.author_id = " . $authorId;
            
            $stmt = $connection->prepare($sql);
            $resultSet = $stmt->executeQuery();
            $problems = $resultSet->fetchAllAssociative();
        } else {
            $connection = $entityManager->getConnection();
            $sql = "SELECT p.* FROM problems p";
            
            $stmt = $connection->prepare($sql);
            $resultSet = $stmt->executeQuery();
            $problems = $resultSet->fetchAllAssociative();
        }
        
        return new JsonResponse($problems);
    }
    
    #[Route('/problems/details/{id}', name: 'problem_detail', methods: ['GET'])]
    public function problemDetail(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $sessionUserId = $request->getSession()->get('user_id');
        
        if (!$sessionUserId) {
            $this->addFlash('error', 'You must be logged in to view problems');
            return $this->redirectToRoute('login');
        }
        
        $problem = $entityManager->getRepository(Problem::class)->find($id);
        
        if (!$problem) {
            $this->addFlash('error', 'Problem not found');
            return $this->redirectToRoute('problems_list');
        }
        
        if (!$problem->isPublished() && $problem->getAuthor()->getId() != $sessionUserId) {
            $this->addFlash('error', 'You do not have permission to view this problem');
            return $this->redirectToRoute('problems_list');
        }
        
        // Get sample test cases for display (first 2 test cases)
        $testCases = $problem->getTestCases();
        $expectedOutputs = $problem->getExpectedOutputs();
        
        $test_examples = [];
        $maxExamplesToShow = min(2, count($testCases));
        
        for ($i = 0; $i < $maxExamplesToShow; $i++) {
            $test_examples[json_encode($testCases[$i])] = json_encode($expectedOutputs[$i]);
        }
        
        return $this->render('problem/detail.html.twig', [
            'problem' => $problem,
            'test_examples' => $test_examples
        ]);
    }
    
    #[Route('/problems/details/{id}/submit', name: 'submit_solution', methods: ['POST'])]
    public function submitSolution(Request $request, EntityManagerInterface $entityManager, CodeExecutor $codeExecutor, int $id): JsonResponse
    {
        $sessionUserId = $request->getSession()->get('user_id');
        
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
        
        $user = $entityManager->getRepository(User::class)->find($userId);
        
        if (!$user) {
            $this->addFlash('error', 'User not found');
            return $this->redirectToRoute('login');
        }
        
        $drafts = $entityManager->getRepository(Problem::class)->findBy([
            'author' => $user,
            'isPublished' => false
        ]);
        
        return $this->render('problem/drafts.html.twig', [
            'drafts' => $drafts
        ]);
    }
    
    #[Route('/problems/create', name: 'problem_create', methods: ['GET'])]
    public function createProblemForm(Request $request, EntityManagerInterface $entityManager): Response
    {
        return $this->render('problem/create.html.twig');
    }
    
    #[Route('/problems/create', name: 'problem_create_post', methods: ['POST'])]
    public function createProblem(Request $request, EntityManagerInterface $entityManager): Response
    {
        $title = $request->request->get('title');
        $description = $request->request->get('description');
        $difficulty = $request->request->get('difficulty');
        $testCases = $request->request->get('testCases');
        $expectedOutputs = $request->request->get('expectedOutputs');
        $maxRuntime = floatval($request->request->get('maxRuntime', 1.0));
        $isPublished = $request->request->getBoolean('isPublished', false);
        
        if (empty($title) || empty($description) || empty($difficulty) || 
            empty($testCases) || empty($expectedOutputs)) {
            $this->addFlash('error', 'All fields are required');
            return $this->redirectToRoute('problem_create');
        }
        
        $testCasesArray = json_decode($testCases, true);
        $expectedOutputsArray = json_decode($expectedOutputs, true);
        
        if (!$testCasesArray || !$expectedOutputsArray) {
            $this->addFlash('error', 'Invalid format for test cases or expected outputs');
            return $this->redirectToRoute('problem_create');
        }
        
        $problem = new Problem();
        $problem->setTitle($title);
        $problem->setDescription($description);
        $problem->setDifficulty($difficulty);
        $problem->setTestCases($testCasesArray);
        $problem->setExpectedOutputs($expectedOutputsArray);
        $problem->setMaxRuntime($maxRuntime);
        $problem->setIsPublished($isPublished);
        
        $userId = $request->getSession()->get('user_id');
        if ($userId) {
            $user = $entityManager->getRepository(User::class)->find($userId);
            $problem->setAuthor($user);
        }
 
        $entityManager->persist($problem);
        $entityManager->flush();
        
        $this->addFlash('success', 'Problem created successfully');
        return $this->redirectToRoute('problems_list');
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
        $description = $request->request->get('description');
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
} 