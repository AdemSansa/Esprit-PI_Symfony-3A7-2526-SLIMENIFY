<?php

namespace App\Entity;

use App\Repository\NoteRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: NoteRepository::class)]
#[ORM\Table(name: 'note')]
class Note
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $mood = null;

    #[ORM\ManyToOne(targetEntity: Appointment::class, inversedBy: 'notes')]
    #[ORM\JoinColumn(name: 'appointment_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Appointment $appointment;

    #[ORM\ManyToOne(targetEntity: Therapist::class, inversedBy: 'notes')]
    #[ORM\JoinColumn(name: 'therapist_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Therapist $therapist;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $v): static { $this->content = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $v): static { $this->createdAt = $v; return $this; }
    public function getMood(): ?string { return $this->mood; }
    public function setMood(?string $v): static { $this->mood = $v; return $this; }
    public function getAppointment(): Appointment { return $this->appointment; }
    public function setAppointment(Appointment $v): static { $this->appointment = $v; return $this; }
    public function getTherapist(): Therapist { return $this->therapist; }
    public function setTherapist(Therapist $v): static { $this->therapist = $v; return $this; }
}
