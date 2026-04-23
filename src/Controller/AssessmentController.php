<?php

namespace App\Controller;

use App\Entity\Quiz;
use App\Entity\QuizResult;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/assessment')]
class AssessmentController extends AbstractController
{
    #[Route('/', name: 'app_assessment_index', methods: ['GET'])]
    public function index(QuizRepository $quizRepository): Response
    {
        $quizzes = $quizRepository->findBy(['active' => Quiz::STATUS_ACTIVE]);
        usort($quizzes, static fn(Quiz $a, Quiz $b) => $b->getParticipantCount() <=> $a->getParticipantCount());

        $mostTakenQuiz = $quizzes[0] ?? null;
        $totalAttempts = array_reduce(
            $quizzes,
            static fn(int $carry, Quiz $quiz): int => $carry + $quiz->getParticipantCount(),
            0
        );

        return $this->render('assessment/index.html.twig', [
            'quizzes' => $quizzes,
            'mostTakenQuiz' => $mostTakenQuiz,
            'totalAttempts' => $totalAttempts,
        ]);
    }

    #[Route('/history', name: 'app_assessment_history', methods: ['GET'])]
    public function history(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        $results = $entityManager->getRepository(QuizResult::class)
            ->findBy(['user' => $user], ['takenAt' => 'DESC']);

        return $this->render('assessment/history.html.twig', [
            'results' => $results,
        ]);
    }

    #[Route('/quiz/{id}', name: 'app_assessment_take', methods: ['GET', 'POST'])]
    public function take(Request $request, Quiz $quiz, EntityManagerInterface $entityManager): Response
    {
        if (!$quiz->isActive()) {
            $this->addFlash('error', 'This quiz is no longer active.');
            return $this->redirectToRoute('app_assessment_index');
        }

        if ($request->isMethod('POST')) {
            $score = 0;
            $answeredCount = 0;
            $questions = $quiz->getQuestions();
            $totalQuestions = count($questions);

            foreach ($questions as $question) {
                $qId = $question->getId();
                $answerValue = $request->request->get('question_' . $qId);

                if ($answerValue !== null) {
                    $score += (int) $answerValue;
                    $answeredCount++;
                }
            }

            // Ensure all required questions were answered
            if ($answeredCount < $totalQuestions) {
                $this->addFlash('error', 'Please answer all questions before submitting.');
            } else {
                // Calculate percentage result (assuming 5 is max score per question)
                $maxPossibleScore = $totalQuestions * 5;
                $percentageResult = $maxPossibleScore > 0 ? (int) (($score / $maxPossibleScore) * 100) : 0;

                $quizResult = new QuizResult();
                $quizResult->setQuiz($quiz);
                $quizResult->setUser($this->getUser());
                $quizResult->setScore($score);
                // We use result column to store the 0-100 percentage metric for now
                $quizResult->setResult($percentageResult);

                // Optional mood parameter if we want to expand this later
                $quizResult->setMood('Neutral');

                $entityManager->persist($quizResult);
                $entityManager->flush();

                $this->addFlash('success', 'Your assessment has been calculated!');
                return $this->redirectToRoute('app_assessment_result', ['id' => $quizResult->getId()]);
            }
        }

        return $this->render('assessment/take.html.twig', [
            'quiz' => $quiz,
        ]);
    }

    #[Route('/result/{id}', name: 'app_assessment_result', methods: ['GET'])]
    public function result(QuizResult $quizResult): Response
    {
        // Ensure that the patient views only their own result
        if ($quizResult->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException('You cannot view this result.');
        }

        return $this->render('assessment/result.html.twig', [
            'result' => $quizResult,
            'quiz' => $quizResult->getQuiz(),
        ]);
    }
}
