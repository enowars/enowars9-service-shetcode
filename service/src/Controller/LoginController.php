<?php

namespace App\Controller;

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
    public function login(Request $request): Response
    {
        // Get username and password from request
        $username = $request->request->get('username');
        $password = $request->request->get('password');

        // TODO: Add authentication logic here

        // For now, just return a successful response
        return $this->json([
            'success' => true,
            'message' => 'Login successful',
        ]);
    }

    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): Response
    {
        // Get registration data from request
        $username = $request->request->get('username');
        $password = $request->request->get('password');
        $email = $request->request->get('email');

        // TODO: Add registration logic here

        // For now, just return a successful response
        return $this->json([
            'success' => true,
            'message' => 'Registration successful',
        ]);
    }
}
