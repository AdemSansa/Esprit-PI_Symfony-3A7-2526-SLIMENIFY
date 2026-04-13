<?php

namespace App\Controller;

use App\Entity\Blog;
use App\Repository\BlogRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api/blogs')]
class BlogController extends AbstractController
{
    private EntityManagerInterface $em;
    private BlogRepository $blogRepository;

    public function __construct(EntityManagerInterface $em, BlogRepository $blogRepository)
    {
        $this->em = $em;
        $this->blogRepository = $blogRepository;
    }

    #[Route('', methods: ['GET'])]
    public function index(): JsonResponse
    {
        $blogs = $this->blogRepository->findAllOrdered();

        $data = [];
        foreach ($blogs as $blog) {
            $data[] = [
                'id' => $blog->getId(),
                'title' => $blog->getTitle(),
                'photo' => $blog->getPhoto(),
                'createdAt' => $blog->getCreatedAt()?->format('Y-m-d H:i:s'),
                'therapist' => $blog->getTherapist()?->getId()
            ];
        }

        return new JsonResponse($data, Response::HTTP_OK);
    }

    #[Route('/{id}', methods: ['GET'])]
    public function show($id): JsonResponse
    {
        $blog = $this->blogRepository->find($id);

        if (!$blog) {
            return new JsonResponse(['error' => 'Blog not found'], 404);
        }

        return new JsonResponse([
            'id' => $blog->getId(),
            'title' => $blog->getTitle(),
            'content' => $blog->getContent(),
            'photo' => $blog->getPhoto(),
            'createdAt' => $blog->getCreatedAt()?->format('Y-m-d H:i:s'),
            'therapist' => $blog->getTherapist()?->getId()
        ]);
    }

    #[Route('', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $this->denyAccessUnlessGranted('ROLE_THERAPIST');

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return new JsonResponse(['error' => 'Invalid JSON'], 400);
        }

        $blog = new Blog();
        $blog->setTitle($data['title'] ?? null);
        $blog->setContent($data['content'] ?? null);
        $blog->setPhoto($data['photo'] ?? null);
        $blog->setTherapist($this->getUser());

        $this->em->persist($blog);
        $this->em->flush();

        return new JsonResponse([
            'message' => 'Blog created',
            'id' => $blog->getId()
        ], 201);
    }

    #[Route('/{id}', methods: ['PUT', 'PATCH'])]
    public function update(Request $request, $id): JsonResponse
    {
        $blog = $this->blogRepository->find($id);

        if (!$blog) {
            return new JsonResponse(['error' => 'Blog not found'], 404);
        }

        if ($blog->getTherapist() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $data = json_decode($request->getContent(), true);

        if (isset($data['title'])) $blog->setTitle($data['title']);
        if (isset($data['content'])) $blog->setContent($data['content']);
        if (isset($data['photo'])) $blog->setPhoto($data['photo']);

        $this->em->flush();

        return new JsonResponse(['message' => 'Blog updated']);
    }

    #[Route('/{id}', methods: ['DELETE'])]
    public function delete($id): JsonResponse
    {
        $blog = $this->blogRepository->find($id);

        if (!$blog) {
            return new JsonResponse(['error' => 'Blog not found'], 404);
        }

        if ($blog->getTherapist() !== $this->getUser()) {
            return new JsonResponse(['error' => 'Access denied'], 403);
        }

        $this->em->remove($blog);
        $this->em->flush();

        return new JsonResponse(['message' => 'Blog deleted']);
    }
}