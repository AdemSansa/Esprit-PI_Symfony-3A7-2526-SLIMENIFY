<?php
// src/Controller/ReviewController.php
namespace App\Controller;

use App\Entity\Review;
use App\Entity\ReviewReply;
use App\Repository\ReviewRepository;
use App\Repository\ReviewReplyRepository;
use App\Repository\TherapistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class ReviewController extends AbstractController
{
    #[Route('/reviews/admin', name: 'app_reviews_admin')]
    #[Route('/reviews/user', name: 'app_reviews_user')]
    #[Route('/reviews/therapist', name: 'app_reviews_therapist')]
    public function list(Request $request, ReviewRepository $reviewRepo): Response
    {
        $sort = strtoupper($request->query->get('sort', 'DESC'));
        $search = trim($request->query->get('search', ''));

        if (!in_array($sort, ['ASC', 'DESC'])) {
            $sort = 'DESC';
        }

        $reviews = $reviewRepo->findBySearchAndSort($search, $sort);

        return $this->render('review/reviews.html.twig', [
            'reviews' => $reviews,
            'currentSort' => $sort,
            'searchTerm' => $search,
        ]);
    }

    #[Route('/reviews/add', name: 'app_reviews_add', methods: ['POST'])]
    public function add(Request $request, ReviewRepository $reviewRepo): Response
    {
        $user = $this->getUser();
        $content = trim($request->request->get('content'));
        $rating  = (int)$request->request->get('rating');

        if (mb_strlen($content) <= 10) {
            $this->addFlash('error', 'The review must be more than 10 characters.');
            return $this->redirect($request->headers->get('referer') ?: '/reviews/user');
        }

        // Vérifier si la review existe déjà pour cet utilisateur
        if ($reviewRepo->findOneBy(['content' => $content, 'user' => $user])) {
            $this->addFlash('error', 'You already submitted this review!');
            return $this->redirect($request->headers->get('referer') ?: '/reviews/user');
        }

        $review = new Review();
        $review->setContent($content);
        $review->setRating($rating);
        $review->setUser($user);
        $reviewRepo->save($review, true);

        $this->addFlash('success', 'Review added successfully!');
        return $this->redirect($request->headers->get('referer') ?: '/reviews/user');
    }

    #[Route('/reviews/{id}/reply', name: 'app_reviews_reply', methods: ['POST'])]
    public function reply(Request $request, Review $review, ReviewReplyRepository $replyRepo, TherapistRepository $therapistRepo): Response
    {
        $user = $this->getUser();

        if (!$user || !in_array('ROLE_THERAPIST', $user->getRoles())) {
            $this->addFlash('error', 'Only therapists can reply.');
            return $this->redirect($request->headers->get('referer') ?: '/reviews/user');
        }

        $therapist = $therapistRepo->findOneBy(['email' => $user->getEmail()]);
        if (!$therapist) {
            $this->addFlash('error', 'Therapist not found.');
            return $this->redirect($request->headers->get('referer') ?: '/reviews/user');
        }

        $content = trim($request->request->get('content'));
        if (mb_strlen($content) <= 10) {
            $this->addFlash('error', 'The reply must be at least 11 characters.');
            return $this->redirect($request->headers->get('referer') ?: '/reviews/user');
        }

        $reply = new ReviewReply();
        $reply->setContent($content);
        $reply->setReview($review);
        $reply->setTherapist($therapist);
        $replyRepo->save($reply, true);

        $this->addFlash('success', 'Reply saved successfully!');
        return $this->redirect($request->headers->get('referer') ?: '/reviews/user');
    }

    // === LIKE / DISLIKE Review ===
    #[Route('/api/reviews/{id}/like', name: 'api_reviews_like', methods: ['POST'])]
    public function likeReview(Review $review, ReviewRepository $reviewRepo): JsonResponse
    {
        $review->setLikes($review->getLikes() + 1);
        $reviewRepo->save($review, true);
        return $this->json(['likes' => $review->getLikes()]);
    }

    #[Route('/api/reviews/{id}/dislike', name: 'api_reviews_dislike', methods: ['POST'])]
    public function dislikeReview(Review $review, ReviewRepository $reviewRepo): JsonResponse
    {
        $review->setDislikes($review->getDislikes() + 1);
        $reviewRepo->save($review, true);
        return $this->json(['dislikes' => $review->getDislikes()]);
    }

    // === LIKE / DISLIKE Reply ===
    #[Route('/api/review-replies/{id}/like', name: 'api_replies_like', methods: ['POST'])]
    public function likeReply(ReviewReply $reply, ReviewReplyRepository $replyRepo): JsonResponse
    {
        $reply->setLikes($reply->getLikes() + 1);
        $replyRepo->save($reply, true);
        return $this->json(['likes' => $reply->getLikes()]);
    }

    #[Route('/api/review-replies/{id}/dislike', name: 'api_replies_dislike', methods: ['POST'])]
    public function dislikeReply(ReviewReply $reply, ReviewReplyRepository $replyRepo): JsonResponse
    {
        $reply->setDislikes($reply->getDislikes() + 1);
        $replyRepo->save($reply, true);
        return $this->json(['dislikes' => $reply->getDislikes()]);
    }

    // === EDIT / DELETE Review ===
    #[Route('/reviews/{id}/edit', name: 'review_edit', methods: ['PUT'])]
    public function editReview(Request $request, Review $review, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['content'])) {
            return $this->json(['error' => 'Invalid data'], 400);
        }

        $review->setContent($data['content']);
        $em->flush();
        return $this->json(['message' => 'Review updated']);
    }

    #[Route('/reviews/{id}/delete', name: 'review_delete', methods: ['DELETE'])]
    public function deleteReview(Review $review, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($review);
        $em->flush();
        return $this->json(['message' => 'Review deleted']);
    }

    // === EDIT / DELETE Reply ===
    #[Route('/reviews/reply/{id}/edit', name: 'reply_edit', methods: ['PUT'])]
    public function editReply(Request $request, ReviewReply $reply, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        if (!isset($data['content'])) {
            return $this->json(['error' => 'Invalid data'], 400);
        }

        $reply->setContent($data['content']);
        $em->flush();
        return $this->json(['message' => 'Reply updated']);
    }

    #[Route('/reviews/reply/{id}/delete', name: 'reply_delete', methods: ['DELETE'])]
    public function deleteReply(ReviewReply $reply, EntityManagerInterface $em): JsonResponse
    {
        $em->remove($reply);
        $em->flush();
        return $this->json(['message' => 'Reply deleted']);
    }
}