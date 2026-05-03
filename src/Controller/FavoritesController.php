<?php

namespace App\Controller;

use App\Entity\Blog;
use App\Entity\BlogFavorite;
use App\Repository\BlogFavoriteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/interactive')]
class FavoritesController extends AbstractController
{
    #[Route('/blog/{id}/favorite', name: 'api_blog_favorite', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function toggleFavorite(
        Blog $blog,
        EntityManagerInterface $em,
        BlogFavoriteRepository $favRepo
    ): JsonResponse {

        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            return new JsonResponse(['error' => 'Login required'], 401);
        }
        // Favorites are only for users (not therapists, as per latest requirement)
        $existingFavorite = $favRepo->findOneBy([
            'user' => $user,
            'blog' => $blog
        ]);

        // REMOVE if already favorited (toggle OFF)
        if ($existingFavorite) {
            $em->remove($existingFavorite);
            $em->flush();

            return new JsonResponse([
                'favorited' => false
            ]);
        }

        // CREATE favorite (toggle ON)
        $favorite = new BlogFavorite();
        $favorite->setBlog($blog);
        $favorite->setUser($user);

        $em->persist($favorite);
        $em->flush();

        return new JsonResponse([
            'favorited' => true
        ]);
    }
}