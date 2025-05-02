<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\PasswordHasher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LoginController extends AbstractController
{
    #[Route('/', name: 'home', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('login/index.html.twig', [
            'controller_name' => 'LoginController',
        ]);
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request, EntityManagerInterface $entityManager, PasswordHasher $passwordHasher): Response
    {
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        
        $user = $entityManager->getRepository(User::class)->findOneBy(['username' => $username]);
        
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }
        
        if (!$passwordHasher->verify($user->getPassword(), $password)) {
            return $this->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }
        
        return $this->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
                'isAdmin' => $user->isAdmin(),
            ],
        ]);
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request, EntityManagerInterface $entityManager, PasswordHasher $passwordHasher): Response
    {
        // Get registration data from request
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        
        if ($entityManager->getRepository(User::class)->findOneBy(['username' => $username])) {
            return $this->json([
                'success' => false,
                'message' => 'Username already exists',
            ], 400);
        }
        
        $user = new User();
        $user->setUsername($username);
        
        $hashedPassword = $passwordHasher->hash($password);

        $user->setPassword($hashedPassword);
    
        $user->setIsAdmin(false);
        
        $entityManager->persist($user);
        $entityManager->flush();
        
        return $this->json([
            'success' => true,
            'message' => 'Registration successful',
            'user' => [
                'id' => $user->getId(),
                'username' => $user->getUsername(),
            ],
        ]);
    }
}
