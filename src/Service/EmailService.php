<?php

namespace App\Service;

use App\Entity\Commande;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailService
{
    private MailerInterface $mailer;
    private \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface $params;

    public function __construct(MailerInterface $mailer, \Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface $params)
    {
        $this->mailer = $mailer;
        $this->params = $params;
    }

    public function sendOrderConfirmation(Commande $commande): void
    {
        $user = $commande->getUser();
        if (!$user || !$user->getEmail()) {
            return;
        }

        $projectDir = $this->params->get('kernel.project_dir');
        assert(is_string($projectDir));

        $email = (new TemplatedEmail())
            ->from(new Address('Slimenify.team@gmail.com', 'Slimenify Team'))
            ->to($user->getEmail())
            ->subject('Order Confirmation #' . $commande->getId())
            ->htmlTemplate('emails/order_confirmation.html.twig')
            ->embedFromPath($projectDir . '/public/logo.jpg', 'logo')
            ->embedFromPath($projectDir . '/public/images/ai.png', 'mascot')
            ->context([
                'commande' => $commande,
                'user' => $user,
            ]);

        $this->mailer->send($email);
    }
}
