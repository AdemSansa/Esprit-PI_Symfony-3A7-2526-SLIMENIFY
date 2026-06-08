<?php

namespace App\Controller;

use App\Entity\Conversation;
use App\Entity\Message;
use App\Entity\Therapist;
use App\Service\AiAnalysisService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/chat')]
#[IsGranted('ROLE_USER')]
class ChatController extends AbstractController
{
    private function assertConversationAccess(Conversation $conversation, EntityManagerInterface $em): void
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $role = $user->getRole();

        if ($role === 'therapist') {
            $therapist = $em->getRepository(Therapist::class)->findOneBy(['email' => $user->getEmail()]);
            if (!$therapist || $conversation->getTherapist()?->getId() !== $therapist->getId()) {
                throw $this->createAccessDeniedException();
            }

            return;
        }

        if ($conversation->getUser()?->getId() !== $user->getId()) {
            throw $this->createAccessDeniedException();
        }
    }

    #[Route('/', name: 'app_chat_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $role = $user->getRole();

        $qb = $em->createQueryBuilder()
                 ->select('c')
                 ->from(Conversation::class, 'c')
                 ->orderBy('c.createdAt', 'DESC');

        if ($role === 'therapist') {
            $therapist = $em->getRepository(Therapist::class)->findOneBy(['email' => $user->getEmail()]);
            if (!$therapist) {
                $this->addFlash('error', 'Therapist profile not found.');
                return $this->redirectToRoute('app_home');
            }
            $qb->where('c.therapist = :therapist')
               ->setParameter('therapist', $therapist);
        } else {
            $qb->where('c.user = :user')
               ->setParameter('user', $user);
        }

        $conversations = $qb->getQuery()->getResult();

        return $this->render('chat/index.html.twig', [
            'conversations' => $conversations,
            'activeConversation' => null
        ]);
    }

    #[Route('/{id}', name: 'app_chat_show', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function show(Conversation $conversation, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $role = $user->getRole();

        if ($role === 'therapist') {
            $therapist = $em->getRepository(Therapist::class)->findOneBy(['email' => $user->getEmail()]);
            if (!$therapist || $conversation->getTherapist()?->getId() !== $therapist->getId()) {
                throw $this->createAccessDeniedException();
            }
        } else {
            if ($conversation->getUser()?->getId() !== $user->getId()) {
                throw $this->createAccessDeniedException();
            }
        }

        $qb = $em->createQueryBuilder()
                 ->select('c')
                 ->from(Conversation::class, 'c')
                 ->orderBy('c.createdAt', 'DESC');

        if ($role === 'therapist') {
            $qb->where('c.therapist = :therapist')
               ->setParameter('therapist', $conversation->getTherapist());
        } else {
            $qb->where('c.user = :user')
               ->setParameter('user', $user);
        }
        $conversations = $qb->getQuery()->getResult();

        return $this->render('chat/index.html.twig', [
            'conversations' => $conversations,
            'activeConversation' => $conversation
        ]);
    }

    #[Route('/api/{id}/messages', name: 'app_chat_fetch_messages', methods: ['GET'])]
    public function fetchMessages(Conversation $conversation, EntityManagerInterface $em): Response
    {
        $this->assertConversationAccess($conversation, $em);

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $isTherapist = $user->getRole() === 'therapist';
        $messages = $conversation->getMessages();

        $data = [];
        foreach ($messages as $msg) {
            $entry = [
                'id'          => $msg->getId(),
                'senderType'  => $msg->getSenderType(),
                'content'     => $msg->getContent(),
            ];
            $entry['createdAt'] = $msg->getCreatedAt() ? $msg->getCreatedAt()->format('H:i') : '';

            // Only expose AI analysis data to therapists
            if ($isTherapist) {
                $entry['sensitivityLevel'] = $msg->getSensitivityLevel();
                $entry['aiAnalysis']       = $msg->getAiAnalysis();
            }

            $data[] = $entry;
        }

        return $this->json($data);
    }

    #[Route('/api/{id}/send', name: 'app_chat_send_message', methods: ['POST'])]
    public function sendMessage(
        Conversation $conversation,
        Request $request,
        EntityManagerInterface $em,
        AiAnalysisService $aiService,
        MailerInterface $mailer
    ): Response {
        $this->assertConversationAccess($conversation, $em);

        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $role = $user->getRole();

        $data    = json_decode($request->getContent(), true);
        $content = trim($data['content'] ?? '');

        if (!$content) {
            return $this->json(['error' => 'Message is empty'], 400);
        }

        $message = new Message();
        $message->setConversation($conversation);
        $message->setContent($content);
        $message->setSenderType($role === 'therapist' ? 'therapist' : 'user');

        $analysis = ['level' => 'low', 'analysis' => ''];
        // Run AI analysis only for patient messages
        if ($role !== 'therapist') {
            $analysis = $aiService->analyzeMessage($content);
            $message->setSensitivityLevel($analysis['level']);
            $message->setAiAnalysis($analysis['analysis']);
        }

        // Save message to DB FIRST — instant response, never blocked by email
        $em->persist($message);
        $em->flush();

        // Alert therapist if high or critical distress detected (after save, non-blocking)
        if ($role !== 'therapist' && in_array($analysis['level'] ?? 'low', ['high', 'critical'])) {
            $therapist  = $conversation->getTherapist();
            $levelLabel = ($analysis['level'] === 'critical') ? '🚨 CRITIQUE' : '🔴 ÉLEVÉ';
            if ($therapist && $therapist->getEmail()) {
                $alertEmail = (new Email())
                    ->from('Slimenify.team@gmail.com')
                    ->to($therapist->getEmail())
                ->subject("[Slimenify] {$levelLabel} — Message préoccupant détecté")
                ->html(
                    "<h2>⚠️ Alerte — Message de détresse détecté</h2>
                    <p><strong>Patient :</strong> {$user->getFirstName()} {$user->getLastName()}</p>
                    <p><strong>Niveau :</strong> {$levelLabel}</p>
                    <p><strong>Message :</strong> <em>\"{$content}\"</em></p>
                    <p><strong>Analyse IA :</strong> {$analysis['analysis']}</p>
                    <p>Veuillez répondre rapidement à ce patient.</p>"
                );
                try { $mailer->send($alertEmail); } catch (\Throwable $e) { /* silent */ }
            }
        }

        return $this->json([
            'id'               => $message->getId(),
            'senderType'       => $message->getSenderType(),
            'content'          => $message->getContent(),
            'createdAt'        => $message->getCreatedAt() ? $message->getCreatedAt()->format('H:i') : '',
            'sensitivityLevel' => $message->getSensitivityLevel(),
            'aiAnalysis'       => $message->getAiAnalysis(),
        ]);
    }

    #[Route('/start/{therapistId}', name: 'app_chat_start', methods: ['GET'])]
    public function startConversation(int $therapistId, EntityManagerInterface $em): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if ($user->getRole() === 'therapist') {
            $this->addFlash('error', 'Therapists cannot initiate chat with other therapists.');
            return $this->redirectToRoute('app_chat_index');
        }

        $therapist = $em->getRepository(Therapist::class)->find($therapistId);
        if (!$therapist) {
            throw $this->createNotFoundException('Therapist not found');
        }

        $conversation = $em->getRepository(Conversation::class)->findOneBy([
            'user'      => $user,
            'therapist' => $therapist
        ]);

        if (!$conversation) {
            $conversation = new Conversation();
            $conversation->setUser($user);
            $conversation->setTherapist($therapist);
            $em->persist($conversation);
            $em->flush();
        }

        return $this->redirectToRoute('app_chat_show', ['id' => $conversation->getId()]);
    }
}
