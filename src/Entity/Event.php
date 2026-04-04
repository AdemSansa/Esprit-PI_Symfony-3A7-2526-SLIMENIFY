<?php

namespace App\Entity;

use App\Repository\EventRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EventRepository::class)]
#[ORM\Table(name: 'event')]
class Event
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_event', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 200, nullable: true)]
    #[Assert\NotBlank(message: "Le titre de l'événement est obligatoire.")]
    #[Assert\Length(
        min: 5,
        max: 200,
        minMessage: "Le titre doit faire au moins {{ limit }} caractères.",
        maxMessage: "Le titre ne peut pas dépasser {{ limit }} caractères."
    )]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(message: "La description est obligatoire.")]
    #[Assert\Length(min: 10, minMessage: "La description doit faire au moins {{ limit }} caractères.")]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Assert\NotBlank(message: "Veuillez choisir un type d'événement (En ligne, Physique...).")]
    private ?string $type = null; // online, physique, hybride

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Assert\NotBlank(message: "La date de début est obligatoire.")]
    #[Assert\Type("\DateTimeInterface")]
    #[Assert\GreaterThan("now", message: "L'événement ne peut pas commencer dans le passé.")]
    private ?\DateTimeInterface $dateStart = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Assert\Type("\DateTimeInterface")]
    #[Assert\GreaterThan(
        propertyPath: "dateStart", 
        message: "La date de fin doit être après la date de début (ex: si vous commencez le 01/05/2026, vous ne pouvez pas finir le 10/01/2026)."
    )]
    private ?\DateTimeInterface $dateEnd = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\NotBlank(message: "L'adresse ou le lien de l'événement est obligatoire.")]
    private ?string $location = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\NotBlank(message: "Veuillez spécifier le nombre maximum de participants.")]
    #[Assert\Positive(message: "Le nombre de participants doit être supérieur à 0.")]
    private ?int $maxParticipants = null;

    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'draft'])]
    private string $status = 'draft'; // draft, published, cancelled

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $organizerId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Registration::class)]
    private Collection $registrations;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->registrations = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $v): static { $this->title = $v; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): static { $this->description = $v; return $this; }
    public function getType(): ?string { return $this->type; }
    public function setType(?string $v): static { $this->type = $v; return $this; }
    public function getDateStart(): ?\DateTimeInterface { return $this->dateStart; }
    public function setDateStart(?\DateTimeInterface $v): static { $this->dateStart = $v; return $this; }
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
    public function getImageUrl(): ?string { return $this->imageUrl; }
    public function setImageUrl(?string $v): static { $this->imageUrl = $v; return $this; }
    public function getRegistrations(): Collection { return $this->registrations; }
}
