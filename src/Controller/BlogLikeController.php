<?php

namespace App\Controller;

use App\Entity\Blog;
use App\Entity\BlogLike;
use App\Entity\Comment;
use App\Entity\CommentLike;
use App\Repository\BlogLikeRepository;
use App\Repository\CommentLikeRepository;
use App\Repository\CommentRepository;
use App\Repository\TherapistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/interactive')]
class BlogLikeController extends AbstractController
{
    #[Route('/blog/{id}/like', name: 'api_blog_like', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function likeBlog(Blog $blog, EntityManagerInterface $em, BlogLikeRepository $likeRepo, TherapistRepository $therapistRepository): JsonResponse
    {
        $user = $this->getUser();
        $isTherapist = in_array('ROLE_THERAPIST', $user->getRoles());
        $therapist = $isTherapist ? $therapistRepository->findOneBy(['email' => $user->getUserIdentifier()]) : null;

        // Check if already liked
        $criteria = $isTherapist ? ['therapist' => $therapist, 'blog' => $blog] : ['user' => $user, 'blog' => $blog];
        $existingLike = $likeRepo->findOneBy($criteria);

        if ($existingLike) {
            $em->remove($existingLike);
            $em->flush();
            return new JsonResponse(['liked' => false, 'count' => count($blog->getLikes())]);
        }

        $like = new BlogLike();
        $like->setBlog($blog);
        if ($isTherapist && $therapist) {
            $like->setTherapist($therapist);
        } else {
            $like->setUser($user);
        }

        $em->persist($like);
        $em->flush();

        return new JsonResponse(['liked' => true, 'count' => count($blog->getLikes())]);
    }

    #[Route('/comment/{id}/like', name: 'api_comment_like', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function likeComment(Comment $comment, EntityManagerInterface $em, CommentLikeRepository $likeRepo, TherapistRepository $therapistRepository): JsonResponse
    {
        $user = $this->getUser();
        $isTherapist = in_array('ROLE_THERAPIST', $user->getRoles());
        $therapist = $isTherapist ? $therapistRepository->findOneBy(['email' => $user->getUserIdentifier()]) : null;

        $criteria = $isTherapist ? ['therapist' => $therapist, 'comment' => $comment] : ['user' => $user, 'comment' => $comment];
        $existingLike = $likeRepo->findOneBy($criteria);

        if ($existingLike) {
            $em->remove($existingLike);
            $em->flush();
            return new JsonResponse(['liked' => false, 'count' => count($comment->getLikes())]);
        }

        $like = new CommentLike();
        $like->setComment($comment);
        if ($isTherapist && $therapist) {
            $like->setTherapist($therapist);
        } else {
            $like->setUser($user);
        }

        $em->persist($like);
        $em->flush();

        return new JsonResponse(['liked' => true, 'count' => count($comment->getLikes())]);
    }

    #[Route('/blog/{id}/comment', name: 'api_blog_comment_add', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function addComment(Blog $blog, Request $request, EntityManagerInterface $em, TherapistRepository $therapistRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $content = $data['content'] ?? '';

        if (strlen($content) < 2) {
            return new JsonResponse(['error' => 'Comment too short'], 400);
        }

        $user = $this->getUser();
        $isTherapist = in_array('ROLE_THERAPIST', $user->getRoles());
        $therapist = $isTherapist ? $therapistRepository->findOneBy(['email' => $user->getUserIdentifier()]) : null;

        $comment = new Comment();
        $comment->setBlog($blog);
        $comment->setContent($content);
        
        if ($isTherapist && $therapist) {
            $comment->setTherapist($therapist);
        } else {
            $comment->setUser($user);
        }

        $em->persist($comment);
        $em->flush();

        return new JsonResponse([
            'success' => true,
            'comment' => [
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'author' => $user->getFirstName(),
                'createdAt' => $comment->getCreatedAt()->format('M d, Y')
            ]
        ]);
    }
}
