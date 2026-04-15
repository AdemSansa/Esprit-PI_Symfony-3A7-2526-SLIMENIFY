<?php

namespace App\Controller;

use App\Entity\Blog;
use App\Entity\Comment;
use App\Form\BlogType;
use App\Form\CommentType;
use App\Repository\BlogRepository;
use App\Repository\CommentRepository;
use App\Repository\TherapistRepository;
use App\Service\ModerationService;
use App\Service\TranslationService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPaginationInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/blogs')]
class BlogWebController extends AbstractController
{
    #[Route('', name: 'app_blog_web_index', methods: ['GET'])]
    public function index(
        BlogRepository $blogRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response {
        $search = $request->query->get('q');

        $qb = $blogRepository->createQueryBuilder('b')
            ->orderBy('b.createdAt', 'DESC');

        if ($search) {
            $qb->andWhere('b.title LIKE :search')
                ->setParameter('search', '%'.$search.'%');
        }

        $query = $qb->getQuery();

        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            3
        );

        if ($pagination instanceof SlidingPaginationInterface) {
            $pagination->setUsedRoute('app_blog_web_index');
        }

        return $this->render('blog/index.html.twig', [
            'pagination' => $pagination,
            'search' => $search ?? '',
        ]);
    }

    #[Route('/search-json', name: 'app_blog_search_json', methods: ['GET'])]
    public function searchJson(Request $request, BlogRepository $blogRepository): JsonResponse
    {
        $q = trim((string) $request->query->get('q', ''));
        if (mb_strlen($q) > 200) {
            $q = mb_substr($q, 0, 200);
        }

        $qb = $blogRepository->createQueryBuilder('b')
            ->leftJoin('b.therapist', 't')->addSelect('t')
            ->leftJoin('b.likes', 'lk')->addSelect('lk')
            ->orderBy('b.createdAt', 'DESC')
            ->setMaxResults(80);

        if ($q !== '') {
            $qb->andWhere('b.title LIKE :search')
                ->setParameter('search', '%'.$q.'%');
        }

        $blogs = $qb->getQuery()->getResult();
        $data = [];
        foreach ($blogs as $blog) {
            $therapist = $blog->getTherapist();
            $author = $therapist->getFirstName().' '.$therapist->getLastName();
            $data[] = [
                'id' => $blog->getId(),
                'title' => $blog->getTitle(),
                'content' => $blog->getContent(),
                'photo' => $blog->getPhoto(),
                'author' => $author,
                'date' => $blog->getCreatedAt()->format('M d, Y'),
                'likesCount' => $blog->getLikes()->count(),
            ];
        }

        return $this->json($data);
    }

    #[Route('/my-blogs', name: 'app_blog_web_my_blogs', methods: ['GET'])]
    #[IsGranted('ROLE_THERAPIST')]
    public function myBlogs(BlogRepository $blogRepository, TherapistRepository $therapistRepository): Response
    {
        $user = $this->getUser();
        $therapist = $therapistRepository->findOneBy(['email' => $user->getUserIdentifier()]);

        if (!$therapist) {
            $this->addFlash('error', 'Therapist profile not found.');

            return $this->redirectToRoute('app_blog_web_index');
        }

        $blogs = $blogRepository->findBy(['therapist' => $therapist], ['createdAt' => 'DESC']);

        return $this->render('blog/my_blogs.html.twig', [
            'blogs' => $blogs,
        ]);
    }

    #[Route('/new', name: 'app_blog_web_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_THERAPIST')]
    public function new(Request $request, EntityManagerInterface $entityManager, TherapistRepository $therapistRepository, SluggerInterface $slugger): Response
    {
        $blog = new Blog();
        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user = $this->getUser();
            $therapist = $therapistRepository->findOneBy(['email' => $user->getUserIdentifier()]);

            if (!$therapist) {
                $this->addFlash('error', 'Therapist profile not found.');

                return $this->redirectToRoute('app_blog_web_index');
            }

            /** @var UploadedFile|null $photoFile */
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/blogs',
                        $newFilename
                    );
                    $blog->setPhoto($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Could not upload photo.');
                }
            } else {
                $this->addFlash('error', 'Please upload a photo from your PC.');

                return $this->render('blog/new.html.twig', [
                    'blog' => $blog,
                    'form' => $form,
                ]);
            }

            $blog->setTherapist($therapist);
            $entityManager->persist($blog);
            $entityManager->flush();

            $this->addFlash('success', 'Blog created successfully!');

            return $this->redirectToRoute('app_blog_web_my_blogs');
        }

        return $this->render('blog/new.html.twig', [
            'blog' => $blog,
            'form' => $form,
        ]);
    }

    #[Route('/translate', name: 'blog_translate', methods: ['GET', 'POST'])]
    public function translate(Request $request, TranslationService $translator): JsonResponse
    {
        if ($request->isMethod('POST')) {
            $data = json_decode($request->getContent(), true) ?? [];
            $title = isset($data['title']) ? (string) $data['title'] : '';
            $content = isset($data['content']) ? (string) $data['content'] : '';
            $lang = isset($data['lang']) ? (string) $data['lang'] : '';
        } else {
            $title = (string) $request->query->get('title', '');
            $content = (string) $request->query->get('content', '');
            $lang = (string) $request->query->get('lang', '');
        }

        $lang = trim($lang);
        if (strlen($lang) < 2) {
            return new JsonResponse(['error' => 'Invalid language'], 400);
        }

        $from = 'en';
        $translatedTitle = $translator->translate($title !== '' ? $title : ' ', $from, $lang);
        $translatedContent = $translator->translate($content !== '' ? $content : ' ', $from, $lang);

        return new JsonResponse([
            'title' => $translatedTitle,
            'content' => $translatedContent,
        ]);
    }

    #[Route('/{blogId}/comment/{commentId}/edit', name: 'app_blog_comment_edit', requirements: ['blogId' => '\d+', 'commentId' => '\d+'], methods: ['GET'])]
    public function editCommentRedirect(int $blogId, int $commentId): Response
    {
        return $this->redirectToRoute('app_blog_web_show', [
            'id' => $blogId,
            'editComment' => $commentId,
            '_fragment' => 'comment-'.$commentId,
        ]);
    }

    #[Route('/{blogId}/comment/{commentId}/delete', name: 'app_blog_comment_delete', requirements: ['blogId' => '\d+', 'commentId' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function deleteComment(
        Request $request,
        int $blogId,
        int $commentId,
        CommentRepository $commentRepository,
        EntityManagerInterface $entityManager,
        TherapistRepository $therapistRepository
    ): Response {
        $comment = $commentRepository->find($commentId);
        if (!$comment || $comment->getBlog()->getId() !== $blogId) {
            throw $this->createNotFoundException();
        }

        $user = $this->getUser();
        $therapist = in_array('ROLE_THERAPIST', $user->getRoles(), true)
            ? $therapistRepository->findOneBy(['email' => $user->getUserIdentifier()])
            : null;

        if (
            $comment->getUser() !== $user
            && $comment->getTherapist() !== $therapist
            && !$this->isGranted('ROLE_ADMIN')
        ) {
            throw $this->createAccessDeniedException();
        }

        $tokenId = 'delete_comment'.$comment->getId();
        if ($this->isCsrfTokenValid($tokenId, (string) $request->request->get('_token'))) {
            $entityManager->remove($comment);
            $entityManager->flush();
            $this->addFlash('success', 'Comment deleted.');
        }

        return $this->redirectToRoute('app_blog_web_show', [
            'id' => $blogId,
            '_fragment' => 'comments',
        ]);
    }

    #[Route('/{id}', name: 'app_blog_web_show', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function show(
        Request $request,
        Blog $blog,
        EntityManagerInterface $entityManager,
        TherapistRepository $therapistRepository,
        ModerationService $moderationService,
        CommentRepository $commentRepository,
        FormFactoryInterface $formFactory,
    ): Response {
        $newComment = new Comment();
        $newComment->setBlog($blog);
        $newForm = $formFactory->createNamed('comment_new', CommentType::class, $newComment);

        $editingComment = null;
        $editForm = null;
        $editCommentId = $request->query->getInt('editComment');

        if ($editCommentId > 0 && $this->isGranted('ROLE_USER')) {
            $candidate = $commentRepository->find($editCommentId);
            if ($candidate
                && $candidate->getBlog()->getId() === $blog->getId()
                && $this->canUserEditComment($candidate, $therapistRepository)
            ) {
                $editingComment = $candidate;
                $editForm = $formFactory->createNamed(
                    'comment_edit',
                    CommentType::class,
                    $editingComment,
                    [
                        'action' => $this->generateUrl('app_blog_web_show', [
                            'id' => $blog->getId(),
                            'editComment' => $editingComment->getId(),
                        ]).'#comment-'.$editingComment->getId(),
                    ]
                );
                $editForm->handleRequest($request);
            }
        }

        $newForm->handleRequest($request);

        if ($editForm && $editForm->isSubmitted()) {
            if (!$this->isGranted('ROLE_USER')) {
                throw $this->createAccessDeniedException();
            }
            if ($editForm->isValid()) {
                $text = (string) $editingComment->getContent();
                if ($moderationService->checkText($text)) {
                    $this->addFlash('error', 'Your comment contains inappropriate language and was not saved.');

                    return $this->redirectToRoute('app_blog_web_show', [
                        'id' => $blog->getId(),
                        'editComment' => $editingComment->getId(),
                        '_fragment' => 'app-flash-messages',
                    ]);
                }
                $entityManager->flush();
                $this->addFlash('success', 'Comment updated.');

                return $this->redirectToRoute('app_blog_web_show', [
                    'id' => $blog->getId(),
                    '_fragment' => 'comments',
                ]);
            }
        }

        if ($newForm->isSubmitted()) {
            if (!$this->isGranted('ROLE_USER')) {
                throw $this->createAccessDeniedException();
            }
            if ($newForm->isValid()) {
                $text = (string) $newComment->getContent();
                if ($moderationService->checkText($text)) {
                    $this->addFlash('error', 'Your comment contains inappropriate language and was not published.');

                    return $this->redirectToRoute('app_blog_web_show', [
                        'id' => $blog->getId(),
                        '_fragment' => 'app-flash-messages',
                    ]);
                }

                $user = $this->getUser();
                if (in_array('ROLE_THERAPIST', $user->getRoles(), true)) {
                    $therapist = $therapistRepository->findOneBy(['email' => $user->getUserIdentifier()]);
                    $newComment->setTherapist($therapist);
                } else {
                    $newComment->setUser($user);
                }

                $entityManager->persist($newComment);
                $entityManager->flush();

                $this->addFlash('success', 'Your comment was published.');

                return $this->redirectToRoute('app_blog_web_show', [
                    'id' => $blog->getId(),
                    '_fragment' => 'comments',
                ]);
            }
        }

        return $this->render('blog/show.html.twig', [
            'blog' => $blog,
            'commentForm' => $newForm,
            'editCommentForm' => $editForm,
            'editingCommentId' => $editingComment?->getId(),
        ]);
    }

    private function canUserEditComment(Comment $comment, TherapistRepository $therapistRepository): bool
    {
        $user = $this->getUser();
        if (!$user) {
            return false;
        }
        if ($this->isGranted('ROLE_ADMIN')) {
            return true;
        }
        $therapist = in_array('ROLE_THERAPIST', $user->getRoles(), true)
            ? $therapistRepository->findOneBy(['email' => $user->getUserIdentifier()])
            : null;

        return $comment->getUser() === $user || $comment->getTherapist() === $therapist;
    }

    #[Route('/{id}/edit', name: 'app_blog_web_edit', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_THERAPIST')]
    public function edit(Request $request, Blog $blog, EntityManagerInterface $entityManager, TherapistRepository $therapistRepository, SluggerInterface $slugger): Response
    {
        $user = $this->getUser();
        $therapist = $therapistRepository->findOneBy(['email' => $user->getUserIdentifier()]);

        if ($blog->getTherapist() !== $therapist && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(BlogType::class, $blog);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $photoFile */
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('kernel.project_dir').'/public/uploads/blogs',
                        $newFilename
                    );

                    $blog->setPhoto($newFilename);
                } catch (FileException $e) {
                    // keep existing photo
                }
            }

            $entityManager->flush();

            $this->addFlash('success', 'Blog updated successfully!');

            return $this->redirectToRoute('app_blog_web_my_blogs');
        }

        return $this->render('blog/edit.html.twig', [
            'blog' => $blog,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_blog_web_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    #[IsGranted('ROLE_THERAPIST')]
    public function delete(Request $request, Blog $blog, EntityManagerInterface $entityManager, TherapistRepository $therapistRepository): Response
    {
        $user = $this->getUser();
        $therapist = $therapistRepository->findOneBy(['email' => $user->getUserIdentifier()]);

        if ($blog->getTherapist() !== $therapist && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$blog->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($blog);
            $entityManager->flush();
            $this->addFlash('success', 'Blog deleted.');
        }

        return $this->redirectToRoute('app_blog_web_my_blogs');
    }
}
