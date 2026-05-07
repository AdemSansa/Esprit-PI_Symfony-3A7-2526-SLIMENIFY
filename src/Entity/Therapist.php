<?php

namespace App\Entity;

use App\Repository\TherapistRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TherapistRepository::class)]
#[ORM\Table(name: 'therapists')]
class Therapist
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 100)]
    private string $firstName;

    #[ORM\Column(type: 'string', length: 100)]
    private string $lastName;

    #[ORM\Column(type: 'string', length: 150, unique: true)]
    private string $email;

    #[ORM\Column(type: 'string', length: 20)]
    private string $phoneNumber;

    #[ORM\Column(type: 'string', length: 150)]
    private string $specialization;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true, enumType: null)]
    private ?string $consultationType = null; // ONLINE, IN_PERSON, BOTH

    #[ORM\Column(type: 'string', length: 10, options: ['default' => 'ACTIVE'])]
    private string $status = 'ACTIVE'; // ACTIVE, INACTIVE

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 1000)]
    private string $photoUrl;

    #[ORM\Column(type: 'string', length: 1000)]
    private string $diplomaPath;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $latitude = null;

    #[ORM\Column(type: 'string', length: 45, nullable: true)]
    private ?string $longitude = null;

    /** @var Collection<int, Appointment> */
    #[ORM\OneToMany(mappedBy: 'therapist', targetEntity: Appointment::class)]
    private Collection $appointments;

    /** @var Collection<int, Availability> */
    #[ORM\OneToMany(mappedBy: 'therapist', targetEntity: Availability::class)]
    private Collection $availabilities;

    /** @var Collection<int, Note> */
    #[ORM\OneToMany(mappedBy: 'therapist', targetEntity: Note::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $notes;


    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->appointments = new ArrayCollection();
        $this->availabilities = new ArrayCollection();
        $this->notes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getFirstName(): string { return $this->firstName; }
    public function setFirstName(string $v): static { $this->firstName = $v; return $this; }
    public function getLastName(): string { return $this->lastName; }
    public function setLastName(string $v): static { $this->lastName = $v; return $this; }
    public function getEmail(): string { return $this->email; }
    public function setEmail(string $v): static { $this->email = $v; return $this; }
    public function getPhoneNumber(): string { return $this->phoneNumber; }
    public function setPhoneNumber(string $v): static { $this->phoneNumber = $v; return $this; }
    public function getSpecialization(): string { return $this->specialization; }
    public function setSpecialization(string $v): static { $this->specialization = $v; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): static { $this->description = $v; return $this; }
    public function getConsultationType(): ?string { return $this->consultationType; }
    public function setConsultationType(?string $v): static { $this->consultationType = $v; return $this; }
    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): static { $this->status = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function getPassword(): ?string { return $this->password; }
    public function setPassword(?string $v): static { $this->password = $v; return $this; }
    public function getPhotoUrl(): string { return $this->photoUrl; }
    public function setPhotoUrl(string $v): static { $this->photoUrl = $v; return $this; }
    public function getDiplomaPath(): string { return $this->diplomaPath; }
    public function setDiplomaPath(string $v): static { $this->diplomaPath = $v; return $this; }
    public function getLatitude(): ?float { return $this->latitude; }
    public function setLatitude(?float $v): static { $this->latitude = $v; return $this; }
    public function getLongitude(): ?string { return $this->longitude; }
    public function setLongitude(?string $v): static { $this->longitude = $v; return $this; }
    /** @return Collection<int, Appointment> */
    public function getAppointments(): Collection { return $this->appointments; }
    /** @return Collection<int, Availability> */
    public function getAvailabilities(): Collection { return $this->availabilities; }
    /** @return Collection<int, Note> */
    public function getNotes(): Collection { return $this->notes; }
}
