<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Therapist;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ForgotPasswordController extends AbstractController
{
    public function __construct(
        private CacheItemPoolInterface $cache
    ) {}

    #[Route('/forgot-password', name: 'app_forgot_password')]
    public function request(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        if ($request->isMethod('POST')) {
            $email = $request->request->get('email');
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user) {
                // Génération d'un token aléatoire sécurisé
                $token = bin2hex(random_bytes(16));
                
                // Sauvegarde du token dans le cache pendant 30 minutes (1800 secondes)
                $item = $this->cache->getItem('reset_pwd_' . $token);
                $item->set($email);
                $item->expiresAfter(1800);
                $this->cache->save($item);

                // Envoi de l'email
                $emailMessage = (new TemplatedEmail())
                    ->from(new Address('Slimenify.team@gmail.com', 'Slimenify Team'))
                    ->to((string) $email)
                    ->subject('Réinitialisation de votre mot de passe')
                    ->htmlTemplate('forgot_password/email.html.twig')
                    ->context([
                        'resetToken' => $token,
                    ]);

                $mailer->send($emailMessage);
            }

            // On affiche toujours le même message pour des raisons de sécurité (empêcher d'énumérer les emails)
            $this->addFlash('success', 'Si un compte existe pour cet email, un lien de réinitialisation vient de vous être envoyé. Il est valide pendant 30 minutes.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('forgot_password/request.html.twig');
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password')]
    public function reset(string $token, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $item = $this->cache->getItem('reset_pwd_' . $token);

        // Si le token n'existe pas dans le cache, c'est qu'il est invalide ou expiré
        if (!$item->isHit()) {
            $this->addFlash('error', 'Ce lien de réinitialisation est invalide ou a expiré.');
            return $this->redirectToRoute('app_forgot_password');
        }

        $email = $item->get();
        
        if ($request->isMethod('POST')) {
            $password = (string) $request->request->get('password');
            $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);

            if ($user && $password) {
                // Hasher et mettre à jour le mot de passe de l'utilisateur
                $user->setPassword(
                    $passwordHasher->hashPassword($user, (string) $password)
                );
                
                // Si l'utilisateur est aussi un thérapeute, on met à jour l'entité Therapist
                $therapist = $em->getRepository(Therapist::class)->findOneBy(['email' => $email]);
                if ($therapist) {
                    $therapist->setPassword($passwordHasher->hashPassword($user, (string) $password));
                }

                $em->flush();
                
                // Supprimer le token du cache
                $this->cache->deleteItem('reset_pwd_' . $token);

                $this->addFlash('success', 'Votre mot de passe a été modifié avec succès. Vous pouvez maintenant vous connecter.');
                return $this->redirectToRoute('app_login');
            } else {
                 $this->addFlash('error', 'Veuillez saisir un mot de passe.');
            }
        }

        return $this->render('forgot_password/reset.html.twig', [
            'token' => $token
        ]);
    }
}
