<?php

namespace App\Controller;

use App\Repository\QuizRepository;
use App\Repository\QuizResultRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminQuizDashboardController extends AbstractController
{
    #[Route('/admin/quiz-dashboard', name: 'app_admin_quiz_dashboard')]
    public function index(
        UserRepository $userRepository,
        QuizRepository $quizRepository,
        QuizResultRepository $resultRepository
    ): Response {
        $totalUsers = $userRepository->countTotalUsers();
        $totalQuizzes = $quizRepository->countTotalQuizzes();
        $globalStats = $resultRepository->getGlobalStats();

        // Chart data
        $completionTrends = $resultRepository->getCompletionTrends(30);
        $registrations = $userRepository->getRegistrationsOverTime(30);
        
        $topQuizzes = $resultRepository->getTopAttemptedQuizzes(5);
        $recentActivity = $resultRepository->getRecentActivity(10);
        $topScores = $resultRepository->getScoresPerQuiz(5);

        return $this->render('admin/quiz_dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'totalQuizzes' => $totalQuizzes,
            'totalAttempts' => $globalStats['total_attempts'],
            'averageScore' => $globalStats['average_score'],
            'completionTrends' => $completionTrends,
            'registrations' => $registrations,
            'topQuizzes' => $topQuizzes,
            'topScores' => $topScores,
            'recentActivity' => $recentActivity,
        ]);
    }
}
