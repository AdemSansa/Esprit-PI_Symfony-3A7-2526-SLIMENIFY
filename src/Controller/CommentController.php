<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Repository\TherapistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Service\ModerationService;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/comment')]
class CommentController extends AbstractController
{
    #[Route('/', name: 'comment_list')]
    public function index(CommentRepository $commentRepository): Response
    {
        $comments = $commentRepository->findBy([], ['id' => 'DESC']);

        return $this->render('comment/index.html.twig', [
            'comments' => $comments
        ]);
    }

    #[Route('/new', name: 'comment_new')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        TherapistRepository $therapistRepository,
        ModerationService $moderationService,
    ): Response {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        $user = $this->getUser();

        if ($form->isSubmitted() && $form->isValid()) {


           $content = $form->get('content')->getData();

        if ($moderationService->checkText($content)) {
            $this->addFlash('error', 'Your comment contains inappropriate language and was not published.');

            return $this->redirectToRoute('comment_new');
        }
            // Set user
            $comment->setUser($user instanceof \App\Entity\User ? $user : null);

            // If user is therapist
            if (in_array('ROLE_THERAPIST', $user->getRoles())) {
                $therapist = $therapistRepository->findOneBy([
                    'email' => $user->getUserIdentifier()
                ]);
                $comment->setTherapist($therapist);
            }

            $em->persist($comment);
            $em->flush();

            return $this->redirectToRoute('comment_list');
        }

        return $this->render('comment/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    #[Route('/{id}/edit', name: 'comment_edit')]
    public function edit(
        Comment $comment,
        Request $request,
        EntityManagerInterface $em,
        TherapistRepository $therapistRepository
    ): Response {

        $user = $this->getUser();

        // If user is therapist
        $therapist = in_array('ROLE_THERAPIST', $user->getRoles())
            ? $therapistRepository->findOneBy(['email' => $user->getUserIdentifier()])
            : null;

        // Check permission
        if (
            $comment->getUser() !== $user &&
            $comment->getTherapist() !== $therapist &&
            !$this->isGranted('ROLE_ADMIN')
        ) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('comment_list');
        }

        return $this->render('comment/edit.html.twig', [
            'form' => $form->createView(),
            'comment' => $comment
        ]);
    }

    #[Route('/{id}/delete', name: 'comment_delete')]
    public function delete(
        Comment $comment,
        EntityManagerInterface $em,
        TherapistRepository $therapistRepository
    ): Response {

        $user = $this->getUser();

        // If user is therapist
        $therapist = in_array('ROLE_THERAPIST', $user->getRoles())
            ? $therapistRepository->findOneBy(['email' => $user->getUserIdentifier()])
            : null;

        // Check permission
        if (
            $comment->getUser() !== $user &&
            $comment->getTherapist() !== $therapist &&
            !$this->isGranted('ROLE_ADMIN')
        ) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($comment);
        $em->flush();

        return $this->redirectToRoute('comment_list');
    }
}