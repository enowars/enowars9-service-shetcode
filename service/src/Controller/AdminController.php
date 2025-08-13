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
    #[Route('/admin-challenge', name: 'admin_challenge', methods: ['GET'])]
    public function adminChallenge(Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$request->getSession()->get('pre_auth_user_id')) {
            return $this->redirectToRoute('home');
        }

        $user = $entityManager->createQuery(
            'SELECT u FROM ' . User::class . ' u WHERE u.id = :id'
        )
        ->setParameter('id', $request->getSession()->get('pre_auth_user_id'))
        ->setMaxResults(1)
        ->getOneOrNullResult();

        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $plain = bin2hex(random_bytes(16));
        $request->getSession()->set('admin_challenge_plain', $plain);

        $pubKeyPath = $this->getParameter('kernel.project_dir') . '/config/admin_public.pem';
        $publicKey  = openssl_pkey_get_public(file_get_contents($pubKeyPath));
        openssl_public_encrypt($plain, $encrypted, $publicKey);
        $encryptedB64 = base64_encode($encrypted);

        return $this->render('admin/admin_challenge.html.twig', [
            'encrypted_challenge' => $encryptedB64,
        ]);
    }

    #[Route('/admin-challenge', name: 'admin_challenge_submit', methods: ['POST'])]
    public function adminChallengeSubmit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();

        $preAuthUserId = $session->get('pre_auth_user_id');
        $decrypted = $request->request->get('decrypted_challenge');
        $plain = $session->get('admin_challenge_plain');

        if (!$preAuthUserId || !is_string($decrypted) || $decrypted === '' || !is_string($plain) || $plain === '') {
            $session->remove('admin_challenge_plain');
            $session->remove('pre_auth_user_id');
            return $this->json([
                'success' => false,
                'message' => 'Invalid admin response',
            ], 401);
        }

        $isValid = function_exists('hash_equals') ? hash_equals($plain, $decrypted) : $plain === $decrypted;
        if (!$isValid) {
            $session->remove('admin_challenge_plain');
            $session->remove('pre_auth_user_id');
            return $this->json([
                'success' => false,
                'message' => 'Invalid admin response',
            ], 401);
        }

        $user = $entityManager->createQuery(
            'SELECT u FROM ' . User::class . ' u WHERE u.id = :id'
        )
        ->setParameter('id', $preAuthUserId)
        ->setMaxResults(1)
        ->getOneOrNullResult();
        
        if (!$user) {
            $session->remove('admin_challenge_plain');
            $session->remove('pre_auth_user_id');
            return $this->json([
                'success' => false,
                'message' => 'User not found',
            ], 404);
        }

        $session->set('user_id', $user->getId());
        $session->set('username', $user->getUsername());
        $session->remove('admin_challenge_plain');
        $session->remove('pre_auth_user_id');

        return $this->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'isAdmin' => $user->isAdmin(),
            ],
            'redirect' => $this->generateUrl('problems_list')
        ]);
    }

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