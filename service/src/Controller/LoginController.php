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
