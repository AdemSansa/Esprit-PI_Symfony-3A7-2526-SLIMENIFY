<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Repository\TherapistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/comment')]
class CommentWebController extends AbstractController
{
    #[Route('/{id}/edit', name: 'api_comment_edit', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function edit(Comment $comment, Request $request, EntityManagerInterface $em, TherapistRepository $therapistRepository): JsonResponse
    {
        $user = $this->getUser();
        $isTherapist = in_array('ROLE_THERAPIST', $user->getRoles());
        $therapist = $isTherapist ? $therapistRepository->findOneBy(['email' => $user->getUserIdentifier()]) : null;

        // Check if author or admin
        if ($comment->getUser() !== $user && $comment->getTherapist() !== $therapist && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? '';

        if (strlen($content) < 2) {
            return new JsonResponse(['error' => 'Comment too short'], 400);
        }

        $comment->setContent($content);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }

    #[Route('/{id}/delete', name: 'api_comment_delete', methods: ['DELETE'])]
    #[IsGranted('ROLE_USER')]
    public function delete(Comment $comment, EntityManagerInterface $em, TherapistRepository $therapistRepository): JsonResponse
    {
        $user = $this->getUser();
        $isTherapist = in_array('ROLE_THERAPIST', $user->getRoles());
        $therapist = $isTherapist ? $therapistRepository->findOneBy(['email' => $user->getUserIdentifier()]) : null;

        if ($comment->getUser() !== $user && $comment->getTherapist() !== $therapist && !$this->isGranted('ROLE_ADMIN')) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $em->remove($comment);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
}
