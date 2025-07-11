<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        if ($request->getSession()->get('user_id')) {
            return $this->redirectToRoute('problems_list');
        }
        
        return $this->render('login/index.html.twig', [
            'controller_name' => 'LoginController',
        ]);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request, EntityManagerInterface $entityManager): Response
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        
        $user = $entityManager->createQuery(
            'SELECT u FROM ' . User::class . ' u WHERE u.username = :username'
        )
        ->setParameter('username', $username)
        ->setMaxResults(1)
        ->getOneOrNullResult();
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }
        
        $expectedHash = md5($password . 'ctf_salt_2024');
        if ($user->getPassword() !== $expectedHash) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $session = $request->getSession();
        
        if ($user->isAdmin()) {
            $session->set('pre_auth_user_id', $user->getId());
            return $this->json([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user->getId(),
                    'username' => $user->getUsername(),
                    'isAdmin' => $user->isAdmin(),
                ],
                'redirect' => $this->generateUrl('admin_challenge')
            ]);
        }

        $session->set('user_id', $user->getId());
        $session->set('username', $user->getUsername());
        
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

        return $this->render('login/admin_challenge.html.twig', [
            'encrypted_challenge' => $encryptedB64,
        ]);
    }

    #[Route('/admin-challenge', name: 'admin_challenge_submit', methods: ['POST'])]
    public function adminChallengeSubmit(Request $request, EntityManagerInterface $entityManager): Response
    {
        file_put_contents('request.txt', json_encode($request->request->all()));
        $decrypted = $request->request->get('decrypted_challenge');
        $plain = $request->getSession()->get('admin_challenge_plain');

        if ($decrypted !== $plain) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid admin response',
            ], 401);
        }

        $user = $entityManager->createQuery(
            'SELECT u FROM ' . User::class . ' u WHERE u.id = :id'
        )
        ->setParameter('id', $request->getSession()->get('pre_auth_user_id'))
        ->setMaxResults(1)
        ->getOneOrNullResult();
        
        $request->getSession()->set('user_id', $user->getId());
        $request->getSession()->set('username', $user->getUsername());
        file_put_contents('session.txt', json_encode($request->getSession()->all()));

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

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $entityManager): Response
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        

        $existingUser = $entityManager->createQuery(
            'SELECT u.id FROM ' . User::class . ' u WHERE u.username = :username'
        )
        ->setParameter('username', $username)
        ->setMaxResults(1)
        ->getOneOrNullResult();
        
        if ($existingUser) {
            return $this->json([
                'success' => false,
                'message' => 'Username already exists',
            ], 400);
        }
        
        $user = new User();
        $user->setUsername($username);
        
        $hashedPassword = md5($password . 'ctf_salt_2024');
        $user->setPassword($hashedPassword);
        
        $user->setIsAdmin(false);
        
        $entityManager->persist($user);
        
        try {
            $entityManager->flush();
        } catch (\Exception $e) {
            return $this->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
            ], 500);
        }
        
        return $this->json([
            'success' => true,
            'message' => 'Registration successful',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
            ],
        ]);
    }
    
    #[Route('/logout', name: 'logout', methods: ['GET'])]
    public function logout(Request $request): Response
    {
        $request->getSession()->clear();
        
        return $this->redirectToRoute('home');
    }
}
