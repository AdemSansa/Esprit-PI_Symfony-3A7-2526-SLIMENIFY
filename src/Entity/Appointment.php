<?php

namespace App\Entity;

use App\Repository\AppointmentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AppointmentRepository::class)]
#[ORM\Table(name: 'appointment')]
class Appointment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'date')]
    private \DateTimeInterface $appointmentDate;

    #[ORM\Column(type: 'time')]
    private \DateTimeInterface $startTime;

    #[ORM\Column(type: 'time')]
    private \DateTimeInterface $endTime;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $status = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $type = null;

    #[ORM\ManyToOne(targetEntity: Therapist::class, inversedBy: 'appointments')]
    #[ORM\JoinColumn(name: 'therapist_id', referencedColumnName: 'id', nullable: false)]
    private Therapist $therapist;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'patient_id', referencedColumnName: 'id', nullable: false)]
    private User $patient;

    /** @var Collection<int, Note> */
    #[ORM\OneToMany(mappedBy: 'appointment', targetEntity: Note::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $notes;


    #[ORM\Column(type: 'string', length: 100, nullable: true)]
    private ?string $patientMood = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->notes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getAppointmentDate(): \DateTimeInterface { return $this->appointmentDate; }
    public function setAppointmentDate(\DateTimeInterface $v): static { $this->appointmentDate = $v; return $this; }
    public function getStartTime(): \DateTimeInterface { return $this->startTime; }
    public function setStartTime(\DateTimeInterface $v): static { $this->startTime = $v; return $this; }
    public function getEndTime(): \DateTimeInterface { return $this->endTime; }
    public function setEndTime(\DateTimeInterface $v): static { $this->endTime = $v; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $v): static { $this->status = $v; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $v): static { $this->createdAt = $v; return $this; }
    public function getType(): ?string { return $this->type; }
    public function setType(?string $v): static { $this->type = $v; return $this; }
    public function getTherapist(): Therapist { return $this->therapist; }
    public function setTherapist(Therapist $v): static { $this->therapist = $v; return $this; }
    public function getPatient(): User { return $this->patient; }
    public function setPatient(User $v): static { $this->patient = $v; return $this; }
    /** @return Collection<int, Note> */
    public function getNotes(): Collection { return $this->notes; }
    public function getPatientMood(): ?string { return $this->patientMood; }
    public function setPatientMood(?string $v): static { $this->patientMood = $v; return $this; }
}
