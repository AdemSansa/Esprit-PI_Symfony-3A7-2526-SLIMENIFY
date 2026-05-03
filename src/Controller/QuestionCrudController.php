<?php

namespace App\Controller;

use App\Entity\Question;
use App\Enum\PsychologyCategory;
use App\Form\QuestionType;
use App\Repository\QuestionRepository;
use App\Service\CloudinaryUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/questions')]
class QuestionCrudController extends AbstractController
{
    #[Route('', name: 'app_question_index', methods: ['GET'])]
    public function index(Request $request, QuestionRepository $questionRepository): Response
    {
        $query = trim((string) $request->query->get('q', ''));
        $category = trim((string) $request->query->get('category', ''));
        $page = max(1, (int) $request->query->get('page', 1));

        $result = $questionRepository->findForManagement(
            $query !== '' ? $query : null,
            $category !== '' ? $category : null,
            $page
        );

        return $this->render('question/index.html.twig', [
            'questions' => $result['questions'],
            'searchQuery' => $query,
            'selectedCategory' => $category,
            'categoryOptions' => PsychologyCategory::choices(),
            'currentPage' => $result['currentPage'],
            'totalPages' => $result['totalPages'],
            'totalQuestions' => $result['total'],
        ]);
    }

    #[Route('/new', name: 'app_question_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, CloudinaryUploader $cloudinaryUploader): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User || $user->getRole() !== 'therapist') {
            throw $this->createAccessDeniedException('Only therapists can add questions.');
        }

        $question = new Question();
        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $uploadedUrl = $cloudinaryUploader->uploadQuestionImage($imageFile);
                if ($uploadedUrl === '') {
                    $this->addFlash('error', 'Image upload failed. Please try again.');
                    return $this->render('question/new.html.twig', [
                        'question' => $question,
                        'form' => $form,
                    ]);
                }
                $question->setImagePath($uploadedUrl);
            }

            $entityManager->persist($question);
            $entityManager->flush();

            return $this->redirectToRoute('app_question_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('question/new.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_question_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Question $question, EntityManagerInterface $entityManager, CloudinaryUploader $cloudinaryUploader): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User || $user->getRole() !== 'therapist') {
            throw $this->createAccessDeniedException('Only therapists can edit questions.');
        }

        $form = $this->createForm(QuestionType::class, $question);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            if ($imageFile) {
                $uploadedUrl = $cloudinaryUploader->uploadQuestionImage($imageFile);
                if ($uploadedUrl === '') {
                    $this->addFlash('error', 'Image upload failed. Please try again.');
                    return $this->render('question/edit.html.twig', [
                        'question' => $question,
                        'form' => $form,
                    ]);
                }
                $question->setImagePath($uploadedUrl);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_question_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('question/edit.html.twig', [
            'question' => $question,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_question_delete', methods: ['POST'])]
    public function delete(Request $request, Question $question, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User|null $user */
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User || $user->getRole() !== 'therapist') {
            throw $this->createAccessDeniedException('Only therapists can delete questions.');
        }

        if ($this->isCsrfTokenValid('delete'.$question->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($question);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_question_index', [], Response::HTTP_SEE_OTHER);
    }
}
