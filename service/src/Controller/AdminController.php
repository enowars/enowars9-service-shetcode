<?php

namespace App\Controller;

use App\Entity\AdminMessage;
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
        
        $currentMessage = $entityManager->getRepository(AdminMessage::class)
            ->findOneBy([], ['createdAt' => 'DESC']);
        
        return $this->render('admin/dashboard.html.twig', [
            'user' => $user,
            'currentMessage' => $currentMessage,
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

    #[Route('/admin/message', name: 'admin_message_form', methods: ['GET'])]
    public function messageForm(Request $request, EntityManagerInterface $entityManager): Response
    {
        $userId = $request->getSession()->get('user_id');
        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user || !$user->isAdmin()) {
            return $this->redirectToRoute('home');
        }
        
        $currentMessage = $entityManager->getRepository(AdminMessage::class)
            ->findOneBy([], ['createdAt' => 'DESC']);
        
        return $this->render('admin/message.html.twig', [
            'user' => $user,
            'currentMessage' => $currentMessage,
        ]);
    }

    #[Route('/admin/message', name: 'admin_message_post', methods: ['POST'])]
    public function postMessage(Request $request, EntityManagerInterface $entityManager): Response
    {
        $userId = $request->getSession()->get('user_id');
        $user = $entityManager->getRepository(User::class)->find($userId);
        if (!$user || !$user->isAdmin()) {
            return $this->redirectToRoute('home');
        }

        $message = $request->request->get('message');
        $year = (int) $request->request->get('year');

        if (empty($message) || empty($year)) {
            $this->addFlash('error', 'Both message and year are required');
            return $this->redirectToRoute('admin_message_form');
        }

        if ($year < 0 || $year > 3000) {
            $this->addFlash('error', 'Year must be between 0 and 3000');
            return $this->redirectToRoute('admin_message_form');
        }

        $existingMessages = $entityManager->getRepository(AdminMessage::class)->findAll();
        foreach ($existingMessages as $existingMessage) {
            $entityManager->remove($existingMessage);
        }

        $adminMessage = new AdminMessage();
        $adminMessage->setMessage($message);
        $adminMessage->setYear($year);
        $adminMessage->setAdmin($user);

        $entityManager->persist($adminMessage);
        $entityManager->flush();

        $this->addFlash('success', 'Time traveller message posted successfully!');
        return $this->redirectToRoute('admin_dashboard');
    }
}