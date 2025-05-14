<?php

namespace App\Controller;

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
    public function submit(Request $request, EntityManagerInterface $entityManager): Response
    {
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
            if ($image->isValid()) {
                if (!file_exists($image->getPathname())) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Image upload failed - file not found',
                    ], 400);
                }
                
                $imageContent = file_get_contents($image->getPathname());
                
                if ($imageContent === false) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Failed to read uploaded image data',
                    ], 400);
                }
                $stream = fopen('php://memory', 'r+');
                fwrite($stream, $imageContent);
                rewind($stream);
                
                $feedback->setImage($stream);
            } else {
                return $this->json([
                    'success' => false,
                    'message' => 'Invalid file upload: ' . $image->getErrorMessage(),
                ], 400);
            }
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
    public function getImage(int $id, EntityManagerInterface $entityManager): Response
    {
        $feedback = $entityManager->getRepository(Feedback::class)->find($id);
        if (!$feedback || !$feedback->getImage()) {
            return new Response('Image not found', 404);
        }
        $imageContent = stream_get_contents($feedback->getImage());
        if ($imageContent === false) {
            return new Response('Error reading image data', 500);
        }
        $response = new Response($imageContent);
        
        $contentType = 'image/svg+xml';
        if (str_starts_with($imageContent, "\x89PNG")) {
            $contentType = 'image/png';
        } elseif (str_starts_with($imageContent, "\xff\xd8\xff")) {
            $contentType = 'image/jpeg';
        }
        
        $response->headers->set('Content-Type', $contentType);
        return $response;
    }
} 