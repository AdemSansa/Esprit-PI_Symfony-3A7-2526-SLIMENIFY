<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'event')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_event', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 200)]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    private ?string $type = null; // online, physique, hybride

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $dateStart;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTimeInterface $dateEnd = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $location = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $maxParticipants = null;

    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'draft'])]
    private string $status = 'draft'; // draft, published, cancelled

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $organizerId = null;

    #[ORM\Column(type: 'text')]
    private string $imageUrl;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Registration::class)]
    private Collection $registrations;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->registrations = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $v): static { $this->title = $v; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): static { $this->description = $v; return $this; }
    public function getType(): ?string { return $this->type; }
    public function setType(?string $v): static { $this->type = $v; return $this; }
    public function getDateStart(): \DateTimeInterface { return $this->dateStart; }
    public function setDateStart(\DateTimeInterface $v): static { $this->dateStart = $v; return $this; }
    public function getDateEnd(): ?\DateTimeInterface { return $this->dateEnd; }
    public function setDateEnd(?\DateTimeInterface $v): static { $this->dateEnd = $v; return $this; }
    public function getLocation(): ?string { return $this->location; }
    public function setLocation(?string $v): static { $this->location = $v; return $this; }
    public function getMaxParticipants(): ?int { return $this->maxParticipants; }
    public function setMaxParticipants(?int $v): static { $this->maxParticipants = $v; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): static { $this->status = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $v): static { $this->createdAt = $v; return $this; }
    public function getOrganizerId(): ?int { return $this->organizerId; }
    public function setOrganizerId(?int $v): static { $this->organizerId = $v; return $this; }
    public function getImageUrl(): string { return $this->imageUrl; }
    public function setImageUrl(string $v): static { $this->imageUrl = $v; return $this; }
    public function getRegistrations(): Collection { return $this->registrations; }
}
