<?php

namespace App\Entity;

use App\Repository\RegistrationRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RegistrationRepository::class)]
#[ORM\Table(name: 'registrations')]
class Registration
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_registration', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'registered'])]
    private string $status = 'registered'; // registered, cancelled, attended

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $registrationDate;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $qrCode = null;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    #[Assert\NotBlank(message: "Please enter your full name.")]
    private ?string $participantName = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: "Email address is required.")]
    #[Assert\Email(message: "Please enter a valid email address.")]
    private ?string $participantEmail = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $participantPhone = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $participantNotes = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $participantLocation = null;

    #[ORM\ManyToOne(targetEntity: Event::class, inversedBy: 'registrations')]
    #[ORM\JoinColumn(name: 'event_id', referencedColumnName: 'id_event', nullable: false, onDelete: 'CASCADE')]
    private Event $event;

    public function __construct()
    {
        $this->registrationDate = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): static { $this->status = $v; return $this; }
    public function getRegistrationDate(): \DateTimeImmutable { return $this->registrationDate; }
    public function setRegistrationDate(\DateTimeImmutable $v): static { $this->registrationDate = $v; return $this; }
    public function getQrCode(): ?string { return $this->qrCode; }
    public function setQrCode(?string $v): static { $this->qrCode = $v; return $this; }
    public function getParticipantName(): ?string { return $this->participantName; }
    public function setParticipantName(?string $v): static { $this->participantName = $v; return $this; }
    public function getParticipantEmail(): ?string { return $this->participantEmail; }
    public function setParticipantEmail(?string $v): static { $this->participantEmail = $v; return $this; }
    public function getParticipantPhone(): ?string { return $this->participantPhone; }
    public function setParticipantPhone(?string $v): static { $this->participantPhone = $v; return $this; }
    public function getParticipantNotes(): ?string { return $this->participantNotes; }
    public function setParticipantNotes(?string $v): static { $this->participantNotes = $v; return $this; }
    public function getEvent(): Event { return $this->event; }
    public function setEvent(Event $v): static { $this->event = $v; return $this; }

    public function getParticipantLocation(): ?string { return $this->participantLocation; }
    public function setParticipantLocation(?string $v): static { $this->participantLocation = $v; return $this; }
}
