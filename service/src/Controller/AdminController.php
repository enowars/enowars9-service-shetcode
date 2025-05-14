<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin/feedback', name: 'feedback', methods: ['GET'])]
    public function feedback(Request $request): Response
    {
        file_put_contents("Page was loaded", "Page was loaded\n", FILE_APPEND);
        return $this->render('login/index.html.twig', [
            'controller_name' => 'LoginController',
        ]);
    }
}