<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
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
    public function login(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
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
        
        if (!$passwordHasher->isPasswordValid($user, $password)) {
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
    public function register(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
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
        
        $hashedPassword = $passwordHasher->hashPassword($user, $password);
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
