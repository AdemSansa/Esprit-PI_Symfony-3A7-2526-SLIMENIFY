<?php

namespace App\Controller;

use App\Entity\User;
use App\Security\AuthAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class GoogleController extends AbstractController
{
    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectGoogleStart(): Response
    {
        $clientId = $this->getParameter('oauth_google_client_id');
        $redirectUri = $this->generateUrl('connect_google_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
        
        // This stops dummy requests in dev from crashing
        if ($clientId === 'dummy_google_client_id') {
            $this->addFlash('error', "Mode Test: L'ID Client Google n'est pas encore configuré. Veuillez modifier votre fichier .env.");
            return $this->redirectToRoute('app_login');
        }

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'access_type' => 'online',
        ]);

        return $this->redirect($url);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectGoogleCheck(
        Request $request, 
        HttpClientInterface $httpClient, 
        EntityManagerInterface $entityManager, 
        UserPasswordHasherInterface $passwordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        AuthAuthenticator $authenticator,
        MailerInterface $mailer
    ): Response
    {
        $code = $request->query->get('code');
        
        if (!$code) {
            $this->addFlash('error', 'Échec de la connexion avec Google. Aucun code reçu.');
            return $this->redirectToRoute('app_login');
        }

        $clientId = $this->getParameter('oauth_google_client_id');
        $clientSecret = $this->getParameter('oauth_google_client_secret');
        $redirectUri = $this->generateUrl('connect_google_check', [], UrlGeneratorInterface::ABSOLUTE_URL);

        try {
            // 1. Échanger le code contre un Access Token
            $response = $httpClient->request('POST', 'https://oauth2.googleapis.com/token', [
                'body' => [
                    'code' => $code,
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'redirect_uri' => $redirectUri,
                    'grant_type' => 'authorization_code',
                ],
            ]);

            $data = $response->toArray();
            $accessToken = $data['access_token'];

            // 2. Obtenir les infos du compte Google
            $userResponse = $httpClient->request('GET', 'https://www.googleapis.com/oauth2/v2/userinfo', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken
                ]
            ]);

            $googleUser = $userResponse->toArray();
            $email = $googleUser['email'];

            // 3. Vérifier si l'utilisateur existe déjà
            $userRepository = $entityManager->getRepository(User::class);
            $user = $userRepository->findOneBy(['email' => $email]);

            if (!$user) {
                // 4. Créer un nouvel utilisateur Patient si aucun n'existe
                $user = new User();
                $user->setEmail($email);
                
                // On met Google given_name et family_name
                $user->setFirstName($googleUser['given_name'] ?? 'Google User');
                $user->setLastName($googleUser['family_name'] ?? '');
                
                // Mettre isVerified à true puisque Google a validé l'email
                $user->setIsVerified(true);
                $user->setRole('patient');
                
                // S'il a une photo de profil sur Google
                if (isset($googleUser['picture'])) {
                    $user->setPhotoUrl($googleUser['picture']);
                } else {
                    $user->setPhotoUrl('/uploads/default.png');
                }

                // Mot de passe aléatoire très sécurisé par défaut
                $randomPassword = bin2hex(random_bytes(16));
                $user->setPassword($passwordHasher->hashPassword($user, $randomPassword));
                
                $entityManager->persist($user);
                $entityManager->flush();

                // Envoi de l'e-mail de Bienvenue via création Google
                try {
                    $welcomeEmail = (new TemplatedEmail())
                        ->from(new Address('Slimenify.team@gmail.com', 'Slimenify Team'))
                        ->to($email)
                        ->subject('Bienvenue sur Slimenify ! (via Google)')
                        ->htmlTemplate('registration/confirmation_email.html.twig');
                    $mailer->send($welcomeEmail);
                } catch (\Exception $e) {
                    // Ignorer si le SMTP crash pour que la connexion aboutisse
                    error_log('SMTP Mailer Error: ' . $e->getMessage());
                }

                $this->addFlash('success', 'Bienvenue ! Votre compte Google a permis de créer votre profil patient avec succès.');
            } else {
                $this->addFlash('success', 'De retour ! Connexion réussie via Google.');
                // Envoi de l'alerte de connexion pour utilisateur existant
                try {
                    $loginAlertEmail = (new TemplatedEmail())
                        ->from(new Address('Slimenify.team@gmail.com', 'Slimenify Security'))
                        ->to($email)
                        ->subject('Nouvelle connexion à votre compte Slimenify')
                        ->htmlTemplate('notification/login_alert.html.twig')
                        ->context([
                            'user' => $user,
                        ]);
                    $mailer->send($loginAlertEmail);
                } catch (\Exception $e) {
                    error_log('SMTP Mailer Error: ' . $e->getMessage());
                }
            }

            // 5. Connecter l'utilisateur dans le système de sécurité Symfony (comme un login manuel)
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            ) ?? $this->redirectToRoute('app_home');

        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la communication avec Google : ' . $e->getMessage());
            return $this->redirectToRoute('app_login');
        }
    }
}
