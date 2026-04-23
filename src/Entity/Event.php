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
    #[Assert\NotBlank(message: "The event title is required.")]
    #[Assert\Length(
        min: 5,
        max: 200,
        minMessage: "The title must be at least {{ limit }} characters long.",
        maxMessage: "The title cannot exceed {{ limit }} characters."
    )]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(message: "The description is required.")]
    #[Assert\Length(min: 10, minMessage: "The description must be at least {{ limit }} characters long.")]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 10, nullable: true)]
    #[Assert\NotBlank(message: "Please choose an event type (Online, In-Person...).")]
    private ?string $type = null; // online, physique, hybride

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Assert\NotBlank(message: "The start date is required.")]
    #[Assert\Type("\DateTimeInterface")]
    #[Assert\GreaterThan("now", message: "The event cannot start in the past.")]
    private ?\DateTimeInterface $dateStart = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Assert\Type("\DateTimeInterface")]
    #[Assert\GreaterThan(
        propertyPath: "dateStart", 
        message: "The end date must be after the start date."
    )]
    private ?\DateTimeInterface $dateEnd = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Expression(
        "this.getType() == 'online' or this.getLocation()",
        message: "The event location or link is required for In-Person/Hybrid events."
    )]
    private ?string $location = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    #[Assert\NotBlank(message: "Please specify the maximum number of participants.")]
    #[Assert\Positive(message: "The number of participants must be greater than 0.")]
    private ?int $maxParticipants = null;

    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'published'])]
    private string $status = 'published'; // published, draft, cancelled

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $organizerId = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $imageUrl = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $longitude = null;


    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Registration::class, cascade: ['remove'], orphanRemoval: true)]
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
    public function getLatitude(): ?float { return $this->latitude; }
    public function setLatitude(?float $v): static { $this->latitude = $v; return $this; }
    public function getLongitude(): ?float { return $this->longitude; }
    public function setLongitude(?float $v): static { $this->longitude = $v; return $this; }
    public function getRegistrations(): Collection { return $this->registrations; }

    /**
     * Get the current status of the event based on time.
     * returns 'upcoming', 'started', or 'ended'
     */
    public function getTimeStatus(): string
    {
        $now = new \DateTime();
        
        if ($this->dateStart > $now) {
            return 'upcoming';
        }

        if ($this->dateEnd !== null) {
            if ($now > $this->dateEnd) {
                return 'ended';
            }
            return 'started';
        }

        // Fallback if no end date: assume ended after 3 hours
        $assumedEnd = (clone $this->dateStart)->modify('+3 hours');
        if ($now > $assumedEnd) {
            return 'ended';
        }
        
        return 'started';
    }

    public function getStatusLabel(): string
    {
        $status = $this->getTimeStatus();
        return match($status) {
            'upcoming' => 'Upcoming',
            'started' => 'Started',
            'ended' => 'Ended',
            default => 'Unknown',
        };
    }

}
