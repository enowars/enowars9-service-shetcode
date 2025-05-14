<?php

namespace App\Controller;

use App\Command\ImageHandler;
use App\Entity\Feedback;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FeedbackController extends AbstractController
{
    #[Route('/feedback', name: 'feedback', methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        $userId = $request->getSession()->get('user_id');
        $user = $entityManager->getRepository(User::class)->find($userId);
        $feedback = $entityManager->getRepository(Feedback::class)->findOneBy(['user' => $user]);

        return $this->render('feedback/index.html.twig', [
            'feedback' => $feedback,
        ]);
    }

    #[Route('/feedback/submit', name: 'feedback_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        EntityManagerInterface $entityManager,
        ImageHandler $imageHandler
    ): Response {
        $userId = $request->getSession()->get('user_id');
        $user = $entityManager->getRepository(User::class)->find($userId);
        $feedback = $entityManager->getRepository(Feedback::class)->findOneBy(['user' => $user]);
        if (!$feedback) {
            $feedback = new Feedback();
            $feedback->setUser($user);
        }

        $description = $request->request->get('description');
        $feedback->setDescription($description);

        $image = $request->files->get('image');
        if ($image) {
            $stream = $imageHandler->processUploadedImage($image);
            if ($stream === false) {
                return $this->json([
                    'success' => false,
                    'message' => 'Error processing image',
                ], 400);
            }
            
            $feedback->setImage($stream);
        }

        $entityManager->persist($feedback);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'message' => 'Feedback submitted successfully',
            'redirect' => $this->generateUrl('feedback')
        ]);
    }

    #[Route('/feedback/image/{id}', name: 'feedback_image', methods: ['GET'])]
    public function getImage(int $id, EntityManagerInterface $entityManager, ImageHandler $imageHandler): Response
    {
        $feedback = $entityManager->getRepository(Feedback::class)->find($id);
        if (!$feedback || !$feedback->getImage()) {
            return new Response('Image not found', 404);
        }

        $response = $imageHandler->createImageResponse($feedback->getImage());
        if ($response === null) {
            return new Response('Error reading image data', 500);
        }
        
        return $response;
    }
} 