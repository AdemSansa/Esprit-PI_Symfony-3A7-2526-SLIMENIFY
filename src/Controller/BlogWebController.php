<?php

namespace App\Controller;

use App\Entity\Blog;
use App\Entity\Comment;
use App\Form\BlogType;
use App\Repository\BlogRepository;
use App\Repository\TherapistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/blogs')]
class BlogWebController extends AbstractController
{
    #[Route('', name: 'app_blog_web_index', methods: ['GET'])]
    public function index(BlogRepository $blogRepository): Response
    {
        return $this->render('blog/index.html.twig', [
            'blogs' => $blogRepository->findAllOrdered(),
        ]);
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
        
        // Find blogs by therapist
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

            /** @var UploadedFile $photoFile */
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/blogs',
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

    #[Route('/{id}', name: 'app_blog_web_show', methods: ['GET'])]
    public function show(Blog $blog): Response
    {
        return $this->render('blog/show.html.twig', [
            'blog' => $blog,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_blog_web_edit', methods: ['GET', 'POST'])]
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
            /** @var UploadedFile $photoFile */
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/blogs',
                        $newFilename
                    );
                    
                    // Optional: remove old file logic here
                    
                    $blog->setPhoto($newFilename);
                } catch (FileException $e) {
                    // handle exception
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

    #[Route('/{id}/delete', name: 'app_blog_web_delete', methods: ['POST'])]
    #[IsGranted('ROLE_THERAPIST')]
    public function delete(Request $request, Blog $blog, EntityManagerInterface $entityManager, TherapistRepository $therapistRepository): Response
    {
        $user = $this->getUser();
        $therapist = $therapistRepository->findOneBy(['email' => $user->getUserIdentifier()]);

        if ($blog->getTherapist() !== $therapist && !$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$blog->getId(), $request->request->get('_token'))) {
            $entityManager->remove($blog);
            $entityManager->flush();
            $this->addFlash('success', 'Blog deleted.');
        }

        return $this->redirectToRoute('app_blog_web_my_blogs');
    }
}
