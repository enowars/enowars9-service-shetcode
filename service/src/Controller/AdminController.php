<?php

namespace App\Controller;

use App\Entity\Feedback;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard', methods: ['GET'])]
    public function dashboard(Request $request, EntityManagerInterface $entityManager): Response
    {
        $userId = $request->getSession()->get('user_id');
        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user || !$user->isAdmin()) {
            return $this->redirectToRoute('home');
        }
        
        return $this->render('admin/dashboard.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/admin/feedback', name: 'admin_feedback', methods: ['GET'])]
    public function viewAllFeedback(Request $request, EntityManagerInterface $entityManager): Response
    {
        $userId = $request->getSession()->get('user_id');
        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user || !$user->isAdmin()) {
            return $this->redirectToRoute('home');
        }
        
        $allFeedback = $entityManager->getRepository(Feedback::class)->findAll();
        
        $processedFeedback = [];
        foreach ($allFeedback as $feedback) {
            $feedbackItem = [
                'id' => $feedback->getId(),
                'username' => $feedback->getUser()->getUsername(),
                'description' => $feedback->getDescription(),
                'createdAt' => $feedback->getCreatedAt(),
                'image' => null,
            ];
            
            if ($feedback->getImage()) {
                $imageContent = stream_get_contents($feedback->getImage());
                if ($imageContent !== false) {
                    $feedbackItem['image'] = $imageContent;
                }
            }
            
            $processedFeedback[] = $feedbackItem;
        }
        
        return $this->render('admin/feedback.html.twig', [
            'feedback' => $processedFeedback,
        ]);
    }
}