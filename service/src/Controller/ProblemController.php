<?php

namespace App\Controller;

use App\Entity\Problem;
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
} 