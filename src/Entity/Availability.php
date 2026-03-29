<?php

namespace App\Entity;

use App\Repository\AvailabilityRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AvailabilityRepository::class)]
#[ORM\Table(name: 'availabilities')]
class Availability
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 10)]
    private string $day; // MONDAY, TUESDAY, ... SUNDAY

    #[ORM\Column(type: 'time')]
    private \DateTimeInterface $startTime;

    #[ORM\Column(type: 'time')]
    private \DateTimeInterface $endTime;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $isAvailable = true;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTimeInterface $specificDate = null;

    #[ORM\ManyToOne(targetEntity: Therapist::class, inversedBy: 'availabilities')]
    #[ORM\JoinColumn(name: 'therapist_id', referencedColumnName: 'id', nullable: false)]
    private Therapist $therapist;

    public function getId(): ?int { return $this->id; }
    public function getDay(): string { return $this->day; }
    public function setDay(string $v): static { $this->day = $v; return $this; }
    public function getStartTime(): \DateTimeInterface { return $this->startTime; }
    public function setStartTime(\DateTimeInterface $v): static { $this->startTime = $v; return $this; }
    public function getEndTime(): \DateTimeInterface { return $this->endTime; }
    public function setEndTime(\DateTimeInterface $v): static { $this->endTime = $v; return $this; }
    public function isAvailable(): bool { return $this->isAvailable; }
    public function setIsAvailable(bool $v): static { $this->isAvailable = $v; return $this; }
    public function getSpecificDate(): ?\DateTimeInterface { return $this->specificDate; }
    public function setSpecificDate(?\DateTimeInterface $v): static { $this->specificDate = $v; return $this; }
    public function getTherapist(): Therapist { return $this->therapist; }
    public function setTherapist(Therapist $v): static { $this->therapist = $v; return $this; }
}
