<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Blog;
use App\Repository\CommentRepository;
use App\Repository\BlogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/comments')]
class CommentController extends AbstractController
{
    private EntityManagerInterface $em;
    private CommentRepository $commentRepository;
    private BlogRepository $blogRepository;

    public function __construct(
        EntityManagerInterface $em,
        CommentRepository $commentRepository,
        BlogRepository $blogRepository
    ) {
        $this->em = $em;
        $this->commentRepository = $commentRepository;
        $this->blogRepository = $blogRepository;
    }

    // 🔹 GET ALL COMMENTS BY BLOG
    #[Route('/blog/{blogId}', methods: ['GET'])]
    public function getByBlog($blogId): JsonResponse
    {
        $comments = $this->commentRepository->findByBlog($blogId);

        $data = [];
        foreach ($comments as $comment) {
            $data[] = [
                'id' => $comment->getId(),
                'content' => $comment->getContent(),
                'createdAt' => $comment->getCreatedAt()?->format('Y-m-d H:i:s'),
                'blog_id' => $comment->getBlog()?->getId(),
                'user_id' => $comment->getUser()?->getId(),
                'therapist_id' => $comment->getTherapist()?->getId(),
                'parent_id' => $comment->getParent()?->getId()
            ];
        }

        return new JsonResponse($data);
    }

    // 🔹 CREATE COMMENT
    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $blog = $this->blogRepository->find($data['blog_id'] ?? null);

        if (!$blog) {
            return new JsonResponse(['error' => 'Blog not found'], 404);
        }

        $comment = new Comment();
        $comment->setContent($data['content'] ?? null);
        $comment->setBlog($blog);

        // assign user or therapist
        if ($this->getUser() instanceof \App\Entity\User) {
            $comment->setUser($this->getUser());
        } else {
            $comment->setTherapist($this->getUser());
        }

        // reply
        if (!empty($data['parent_id'])) {
            $parent = $this->commentRepository->find($data['parent_id']);
            if ($parent) {
                $comment->setParent($parent);
            }
        }

        $this->em->persist($comment);
        $this->em->flush();

        return new JsonResponse([
            'message' => 'Comment created',
            'id' => $comment->getId()
        ], 201);
    }

    // 🔹 UPDATE COMMENT
    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, $id): JsonResponse
    {
        $comment = $this->commentRepository->find($id);

        if (!$comment) {
            return new JsonResponse(['error' => 'Comment not found'], 404);
        }

        if (
            $comment->getUser() !== $this->getUser() &&
            $comment->getTherapist() !== $this->getUser()
        ) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['content'])) {
            $comment->setContent($data['content']);
        }

        $this->em->flush();

        return new JsonResponse(['message' => 'Comment updated']);
    }

    // 🔹 DELETE COMMENT
    #[Route('/{id}', methods: ['DELETE'])]
    public function delete($id): JsonResponse
    {
        $comment = $this->commentRepository->find($id);

        if (!$comment) {
            return new JsonResponse(['error' => 'Comment not found'], 404);
        }

        if (
            $comment->getUser() !== $this->getUser() &&
            $comment->getTherapist() !== $this->getUser()
        ) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $this->em->remove($comment);
        $this->em->flush();

        return new JsonResponse(['message' => 'Comment deleted']);
    }
}