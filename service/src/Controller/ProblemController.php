<?php

namespace App\Controller;

use App\Entity\Problem;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ProblemController extends AbstractController
{
    #[Route('/problems', name: 'problems_list', methods: ['GET'])]
    public function listProblems(Request $request, EntityManagerInterface $entityManager): Response
    {
        $problems = $entityManager->getRepository(Problem::class)->findBy(['isPublished' => true]);
        
        return $this->render('problem/list.html.twig', [
            'problems' => $problems,
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
        
        // Get the user from the session
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
} 