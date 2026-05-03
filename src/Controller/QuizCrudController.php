<?php

namespace App\Controller;

use App\Entity\Question;
use App\Entity\Quiz;
use App\Form\QuizType;
use App\Repository\QuizRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/quiz')]
class QuizCrudController extends AbstractController
{
    private function normalizeCategory(?string $value): string
    {
        return mb_strtolower(trim((string) $value));
    }

    private function hasCategoryMismatch(Quiz $quiz): bool
    {
        $quizCategory = $this->normalizeCategory($quiz->getCategory());
        if ($quizCategory === '') {
            return true;
        }

        foreach ($quiz->getQuestions() as $question) {
            if ($this->normalizeCategory($question->getCategory()) !== $quizCategory) {
                return true;
            }
        }

        return false;
    }

    private function attachInlineQuestionFromForm(FormInterface $form, Quiz $quiz, EntityManagerInterface $entityManager): ?Response
    {
        $inlineQuestionsText = trim((string) $form->get('inlineQuestionsText')->getData());
        if ($inlineQuestionsText === '') {
            return null;
        }

        $rawLines = preg_split('/\R+/', $inlineQuestionsText) ?: [];
        $inlineQuestions = array_values(array_filter(array_map(
            static fn (string $line): string => trim($line),
            $rawLines
        ), static fn (string $line): bool => $line !== ''));

        if ($inlineQuestions === []) {
            return null;
        }

        foreach ($inlineQuestions as $questionText) {
            if (mb_strlen($questionText) < 3 || mb_strlen($questionText) > 255) {
                $this->addFlash('error', 'Each inline question must be between 3 and 255 characters.');

                return new Response('', Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        }

        $isRequired = (bool) $form->get('inlineQuestionRequired')->getData();
        foreach ($inlineQuestions as $questionText) {
            $question = new Question();
            $question->setQuestionText($questionText);
            $question->setCategory($quiz->getCategory());
            $question->setRequired($isRequired);
            $question->setImagePath('');

            $entityManager->persist($question);
            $quiz->addQuestion($question);
        }

        return null;
    }

    #[Route('', name: 'app_quiz_index', methods: ['GET'])]
    public function index(Request $request, QuizRepository $quizRepository): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User || $user->getRole() !== 'therapist') {
            throw $this->createAccessDeniedException('Only therapists can manage quizzes.');
        }

        $query = $request->query->get('q');
        $quizzes = $query
            ? $quizRepository->findByAuthorAndSearchQuery($user->getId(), $query)
            : $quizRepository->findByAuthor($user->getId());

        return $this->render('quiz/index.html.twig', [
            'quizzes' => $quizzes,
            'searchQuery' => $query,
        ]);
    }

    #[Route('/new', name: 'app_quiz_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User || $user->getRole() !== 'therapist') {
            throw $this->createAccessDeniedException('Only therapists can create quizzes.');
        }

        $quiz = new Quiz();
        $quiz->setAuthor($user);
        $quiz->setActive(Quiz::STATUS_UNDER_REVIEW);
        $quiz->setRejectionComment(null);
        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $inlineQuestionError = $this->attachInlineQuestionFromForm($form, $quiz, $entityManager);
            if ($inlineQuestionError instanceof Response) {
                return $this->render('quiz/new.html.twig', [
                    'quiz' => $quiz,
                    'form' => $form,
                ], $inlineQuestionError);
            }

            if ($this->hasCategoryMismatch($quiz)) {
                $this->addFlash('error', 'All selected questions must belong to the same category as the quiz.');
                return $this->render('quiz/new.html.twig', [
                    'quiz' => $quiz,
                    'form' => $form,
                ]);
            }

            $quiz->setTotalQuestions(count($quiz->getQuestions()));
            $entityManager->persist($quiz);
            $entityManager->flush();

            return $this->redirectToRoute('app_quiz_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('quiz/new.html.twig', [
            'quiz' => $quiz,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_quiz_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Quiz $quiz, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User || $user->getRole() !== 'therapist') {
            throw $this->createAccessDeniedException('Only therapists can edit quizzes.');
        }

        $form = $this->createForm(QuizType::class, $quiz);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $inlineQuestionError = $this->attachInlineQuestionFromForm($form, $quiz, $entityManager);
            if ($inlineQuestionError instanceof Response) {
                return $this->render('quiz/edit.html.twig', [
                    'quiz' => $quiz,
                    'form' => $form,
                ], $inlineQuestionError);
            }

            if ($this->hasCategoryMismatch($quiz)) {
                $this->addFlash('error', 'All selected questions must belong to the same category as the quiz.');
                return $this->render('quiz/edit.html.twig', [
                    'quiz' => $quiz,
                    'form' => $form,
                ]);
            }

            $quiz->setTotalQuestions(count($quiz->getQuestions()));
            $quiz->setUpdatedAt(new \DateTimeImmutable());
            $quiz->setActive(Quiz::STATUS_UNDER_REVIEW);
            $quiz->setRejectionComment(null);


            $entityManager->flush();

            return $this->redirectToRoute('app_quiz_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('quiz/edit.html.twig', [
            'quiz' => $quiz,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_quiz_delete', methods: ['POST'])]
    public function delete(Request $request, Quiz $quiz, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User || $user->getRole() !== 'therapist') {
            throw $this->createAccessDeniedException('Only therapists can delete quizzes.');
        }

        if ($this->isCsrfTokenValid('delete'.$quiz->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($quiz);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_quiz_index', [], Response::HTTP_SEE_OTHER);
    }
}
