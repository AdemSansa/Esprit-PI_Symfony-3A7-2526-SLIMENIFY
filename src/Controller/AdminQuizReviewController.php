<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/quizzes')]
#[IsGranted('ROLE_ADMIN')]
class AdminQuizReviewController extends AbstractController
{
    #[Route('/reviews', name: 'app_admin_quiz_reviews', methods: ['GET'])]
    public function index(QuizRepository $quizRepository): Response
    {
        $pendingQuizzes = $quizRepository->findBy(
            ['active' => Quiz::STATUS_UNDER_REVIEW],
            ['createdAt' => 'DESC']
        );

        return $this->render('admin_quiz_review/index.html.twig', [
            'quizzes' => $pendingQuizzes,
        ]);
    }

    #[Route('/{id}/approve', name: 'app_admin_quiz_approve', methods: ['POST'])]
    public function approve(Request $request, Quiz $quiz, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('approve_quiz_'.$quiz->getId(), $request->request->get('_token'))) {
            $quiz->setActive(Quiz::STATUS_ACTIVE);
            $quiz->setRejectionComment(null);
            $quiz->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('success', 'Quiz approved and now visible to users.');
        }

        return $this->redirectToRoute('app_admin_quiz_reviews');
    }

    #[Route('/{id}/reject', name: 'app_admin_quiz_reject', methods: ['POST'])]
    public function reject(Request $request, Quiz $quiz, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('reject_quiz_'.$quiz->getId(), $request->request->get('_token'))) {
            $rejectionComment = trim((string) $request->request->get('rejection_comment', ''));
            if ($rejectionComment === '') {
                $this->addFlash('error', 'A rejection comment is required so the therapist knows what to fix.');

                return $this->redirectToRoute('app_admin_quiz_reviews');
            }

            $quiz->setActive(Quiz::STATUS_INACTIVE);
            $quiz->setRejectionComment($rejectionComment);
            $quiz->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();
            $this->addFlash('warning', 'Quiz request rejected and marked as inactive.');
        }

        return $this->redirectToRoute('app_admin_quiz_reviews');
    }
}
