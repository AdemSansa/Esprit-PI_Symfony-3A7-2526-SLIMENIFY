<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Security\AuthAuthenticator;
use App\Security\EmailVerifier;
use App\Service\CloudinaryUploader;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;

class RegistrationController extends AbstractController
{
    public function __construct(private EmailVerifier $emailVerifier)
    {
    }

    #[Route('/register', name: 'app_register')]
    public function register(Request $request, UserPasswordHasherInterface $userPasswordHasher, Security $security, EntityManagerInterface $entityManager, CloudinaryUploader $cloudinaryUploader): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // reCAPTCHA validation
            $recaptchaResponse = $request->request->get('g-recaptcha-response');
            if (!$this->verifyRecaptcha($recaptchaResponse !== null ? (string) $recaptchaResponse : null)) {
                $this->addFlash('error', 'Please complete the CAPTCHA correctly.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form,
                    'recaptcha_site_key' => $_ENV['RECAPTCHA_SITE_KEY'] ?? '',
                ]);
            }

            // Validate age for therapists
            $role = $form->get('role')->getData();
            $dateNaissance = $user->getDateNaissance();
            if ($role === 'therapist') {
                if (!$dateNaissance) {
                    $form->get('dateNaissance')->addError(new \Symfony\Component\Form\FormError('Please provide your date of birth.'));
                    return $this->render('registration/register.html.twig', [
                        'registrationForm' => $form,
                    ]);
                }
                $age = $dateNaissance->diff(new \DateTime())->y;
                if ($age < 18) {
                    $form->get('dateNaissance')->addError(new \Symfony\Component\Form\FormError('A therapist must be at least 18 years old.'));
                    return $this->render('registration/register.html.twig', [
                        'registrationForm' => $form,
                    ]);
                }
            }

            /** @var string $plainPassword */
            $plainPassword = $form->get('plainPassword')->getData();

            // encode the plain password
            $user->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

            /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $photoFile */
            $photoFile = $form->get('photoUrl')->getData();
            if ($photoFile) {
                try {
                    $url = $cloudinaryUploader->uploadPhoto($photoFile);
                    $user->setPhotoUrl($url);
                } catch (\Exception $e) {
                    $user->setPhotoUrl('/uploads/default.png');
                }
            } else {
                $user->setPhotoUrl('/uploads/default.png'); // Fallback if user doesn't upload a photo
            }

            // Set the selected role
            $role = $form->get('role')->getData();
            $user->setRole($role);

            $entityManager->persist($user);

            // If the user chose to register as a therapist, create the Therapist entity
            if ($role === 'therapist') {
                $therapist = new \App\Entity\Therapist();
                $therapist->setFirstName((string)$user->getFirstName());
                $therapist->setLastName((string)$user->getLastName());
                $therapist->setEmail((string)$user->getEmail());
                $therapist->setPhoneNumber((string)$user->getPhone() ?: '');
                $therapist->setPhotoUrl((string)$user->getPhotoUrl());
                
                // Extra therapist-specific fields
                $specValue = $form->get('specialization')->getData();
                $therapist->setSpecialization(is_string($specValue) ? $specValue : '');

                $consulValue = $form->get('consultationType')->getData();
                if ($consulValue) {
                    $therapist->setConsultationType((string)$consulValue);
                }
                
                /** @var \Symfony\Component\HttpFoundation\File\UploadedFile|null $diplomaFile */
                $diplomaFile = $form->get('diplomaPath')->getData();
                if ($diplomaFile) {
                    try {
                        $diplomaUrl = $cloudinaryUploader->uploadDiploma($diplomaFile);
                        $therapist->setDiplomaPath($diplomaUrl);
                    } catch (\Exception $e) {
                        $therapist->setDiplomaPath('');
                    }
                } else {
                    $therapist->setDiplomaPath('');
                }

                $therapist->setDescription($form->get('description')->getData() ? (string)$form->get('description')->getData() : null);
                
                // Add hashed password to therapist since entity expects it
                $therapist->setPassword($userPasswordHasher->hashPassword($user, $plainPassword));

                $entityManager->persist($therapist);
            }

            $entityManager->flush();

            // generate a signed url and email it to the user
            $this->emailVerifier->sendEmailConfirmation('app_verify_email', $user,
                (new TemplatedEmail())
                    ->from(new Address('Slimenify.team@gmail.com', 'Slimenify Team'))
                    ->to((string) $user->getEmail())
                    ->subject('Veuillez confirmer votre e-mail - Slimenify')
                    ->htmlTemplate('registration/confirmation_email.html.twig')
                    ->context(['utilisateur' => $user, 'role' => $role])
            );

            // do anything else you need here, like send an email

            return $security->login($user, AuthAuthenticator::class, 'main')
                ?? $this->redirectToRoute('app_home');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form,
            'recaptcha_site_key' => $_ENV['RECAPTCHA_SITE_KEY'] ?? '',
        ]);
    }

    private function verifyRecaptcha(?string $response): bool
    {
        if (!$response) {
            return false;
        }

        $secret = $_ENV['RECAPTCHA_SECRET_KEY'] ?? '';
        $verifyUrl = 'https://www.google.com/recaptcha/api/siteverify';
        
        $data = [
            'secret' => $secret,
            'response' => $response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data)
            ]
        ];

        $context  = stream_context_create($options);
        $result = file_get_contents($verifyUrl, false, $context);
        $resultJson = json_decode($result !== false ? $result : '{}');

        return $resultJson->success ?? false;
    }


    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyUserEmail(Request $request, TranslatorInterface $translator): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // validate email confirmation link, sets User::isVerified=true and persists
        try {
            /** @var User $user */
            $user = $this->getUser();
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        } catch (VerifyEmailExceptionInterface $exception) {
            $this->addFlash('verify_email_error', $translator->trans($exception->getReason(), [], 'VerifyEmailBundle'));

            return $this->redirectToRoute('app_register');
        }

        // @TODO Change the redirect on success and handle or remove the flash message in your templates
        $this->addFlash('success', 'Your email address has been verified.');

        return $this->redirectToRoute('app_register');
    }
}
