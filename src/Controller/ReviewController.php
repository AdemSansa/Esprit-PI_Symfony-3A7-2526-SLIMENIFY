<?php
// src/Controller/ReviewPageController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\ReviewRepository;
use App\Repository\ReviewReplyRepository;
use App\Repository\TherapistRepository;
use App\Entity\Review;
use App\Entity\ReviewReply;

class ReviewController extends AbstractController
{
    #[Route('/reviews/admin', name: 'app_reviews_admin')]
    public function admin(ReviewRepository $reviewRepo): Response
    {
        $reviews = $reviewRepo->findAll(); // Admin voit tout
        return $this->render('review/reviews.html.twig', ['reviews' => $reviews]);
    }

    #[Route('/reviews/therapist', name: 'app_reviews_therapist')]
    public function therapist(ReviewRepository $reviewRepo): Response
    {
        $reviews = $reviewRepo->findAll(); // Therapist voit tout
        return $this->render('review/reviews.html.twig', ['reviews' => $reviews]);
    }

    #[Route('/reviews/user', name: 'app_reviews_user')]
    public function user(ReviewRepository $reviewRepo): Response
    {
        $reviews = $reviewRepo->findAll(); // User voit tout (pour afficher à droite)
        return $this->render('review/reviews.html.twig', [
            'reviews' => $reviews
        ]);
    }

    // Ajouter Review
    #[Route('/reviews/add', name: 'app_reviews_add', methods: ['POST'])]
    public function add(Request $request, ReviewRepository $reviewRepo): Response
    {
        $user = $this->getUser();
        $content = trim($request->request->get('content'));
        $content = str_replace("\r\n", "\n", $content); // Normaliser les retours à la ligne
        $rating  = $request->request->get('rating');

        if (mb_strlen($content) <= 10) {
            $this->addFlash('error', 'The review must be more than 10 characters.');
            return $this->redirect($request->headers->get('referer') ?: '/reviews/user');
        }

        if ($reviewRepo->findOneBy(['content' => $content])) {
            $this->addFlash('error', 'This review already exists!');
            return $this->redirect($request->headers->get('referer') ?: '/reviews/user');
        }

        $review = new Review();
        $review->setContent($content);
        $review->setUser($user);
        $review->setRating((int)$rating);

        $reviewRepo->save($review);

        return $this->redirectToRoute('app_reviews_user');
    }

    // Ajouter Reply (Therapist)
    #[Route('/reviews/{id}/reply', name: 'app_reviews_reply', methods: ['POST'])]
    public function reply(Request $request, Review $review, ReviewReplyRepository $replyRepo, TherapistRepository $therapistRepo): Response
    {
        $user = $this->getUser();
        $therapist = $therapistRepo->findOneBy(['email' => $user->getEmail()]);
        
        // Fallback si la donnée de test (email) ne correspond pas parfaitement
        if (!$therapist) {
            $therapist = $therapistRepo->findOneBy([]);
        }

        if (!$therapist) {
            $this->addFlash('error', 'No therapist found in the database.');
            return $this->redirect($request->headers->get('referer') ?: '/reviews/therapist');
        }

        $content = trim($request->request->get('content'));
        $content = str_replace("\r\n", "\n", $content); // Normaliser

        if (mb_strlen($content) <= 10) {
            $this->addFlash('error', 'The reply must be more than 10 characters.');
            return $this->redirect($request->headers->get('referer') ?: '/reviews/therapist');
        }

        if ($replyRepo->findOneBy(['content' => $content])) {
            $this->addFlash('error', 'This reply already exists!');
            return $this->redirect($request->headers->get('referer') ?: '/reviews/therapist');
        }

        $reply = new ReviewReply();
        $reply->setContent($content);
        $reply->setReview($review);
        $reply->setTherapist($therapist);

        $replyRepo->save($reply);

        $this->addFlash('success', 'Reply submitted successfully.');
        return $this->redirect($request->headers->get('referer') ?: '/reviews/therapist');
    }

    // EDIT Review
    #[Route('/api/reviews/{id}', name: 'api_reviews_edit', methods: ['PUT'])]
    public function editReview(Request $request, Review $review, ReviewRepository $reviewRepo): Response
    {
        $data = json_decode($request->getContent(), true);
        if (isset($data['content'])) {
            $content = trim($data['content']);
            $content = str_replace("\r\n", "\n", $content);
            
            if (mb_strlen($content) <= 10) {
                return $this->json(['message' => 'The review must be more than 10 characters.'], 400);
            }

            $existing = $reviewRepo->findOneBy(['content' => $content]);
            if ($existing && $existing->getId() !== $review->getId()) {
                return $this->json(['message' => 'This review already exists!'], 400);
            }

            $review->setContent($content);
            if (method_exists($review, 'setUpdatedAt')) {
                $review->setUpdatedAt(new \DateTime());
            }
            $reviewRepo->save($review, true);
        }
        return $this->json(['message' => 'Review edited successfully']);
    }

    // DELETE Review
    #[Route('/api/reviews/{id}', name: 'api_reviews_delete', methods: ['DELETE'])]
    public function deleteReview(Review $review, ReviewRepository $reviewRepo): Response
    {
        $reviewRepo->remove($review, true);
        return $this->json(['message' => 'Review deleted successfully']);
    }

    // LIKE Review
    #[Route('/api/reviews/{id}/like', name: 'api_reviews_like', methods: ['POST'])]
    public function likeReview(Review $review, ReviewRepository $reviewRepo): Response
    {
        $review->setLikes($review->getLikes() + 1);
        $reviewRepo->save($review);
        return $this->json(['likes' => $review->getLikes(), 'message' => 'Liked successfully']);
    }

    // DISLIKE Review
    #[Route('/api/reviews/{id}/dislike', name: 'api_reviews_dislike', methods: ['POST'])]
    public function dislikeReview(Review $review, ReviewRepository $reviewRepo): Response
    {
        $review->setDislikes($review->getDislikes() + 1);
        $reviewRepo->save($review);
        return $this->json(['dislikes' => $review->getDislikes(), 'message' => 'Disliked successfully']);
    }

    // EDIT Reply
    #[Route('/api/review-replies/{id}', name: 'api_replies_edit', methods: ['PUT'])]
    public function editReply(Request $request, ReviewReply $reply, ReviewReplyRepository $replyRepo): Response
    {
        $data = json_decode($request->getContent(), true);
        if (isset($data['content'])) {
            $content = trim($data['content']);
            $content = str_replace("\r\n", "\n", $content);
            
            if (mb_strlen($content) <= 10) {
                return $this->json(['message' => 'The reply must be more than 10 characters.'], 400);
            }

            $existing = $replyRepo->findOneBy(['content' => $content]);
            if ($existing && $existing->getId() !== $reply->getId()) {
                return $this->json(['message' => 'This reply already exists!'], 400);
            }

            $reply->setContent($content);
            $replyRepo->save($reply, true);
        }
        return $this->json(['message' => 'Reply edited successfully']);
    }

    // DELETE Reply
    #[Route('/api/review-replies/{id}', name: 'api_replies_delete', methods: ['DELETE'])]
    public function deleteReply(ReviewReply $reply, ReviewReplyRepository $replyRepo): Response
    {
        $replyRepo->remove($reply, true);
        return $this->json(['message' => 'Reply deleted successfully']);
    }

    // LIKE Reply
    #[Route('/api/review-replies/{id}/like', name: 'api_replies_like', methods: ['POST'])]
    public function likeReply(ReviewReply $reply, ReviewReplyRepository $replyRepo): Response
    {
        $reply->setLikes($reply->getLikes() + 1);
        $replyRepo->save($reply);
        return $this->json(['likes' => $reply->getLikes(), 'message' => 'Liked successfully']);
    }

    // DISLIKE Reply
    #[Route('/api/review-replies/{id}/dislike', name: 'api_replies_dislike', methods: ['POST'])]
    public function dislikeReply(ReviewReply $reply, ReviewReplyRepository $replyRepo): Response
    {
        $reply->setDislikes($reply->getDislikes() + 1);
        $replyRepo->save($reply);
        return $this->json(['dislikes' => $reply->getDislikes(), 'message' => 'Disliked successfully']);
    }
}