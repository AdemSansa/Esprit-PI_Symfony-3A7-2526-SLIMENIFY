<?php

namespace App\Entity;

use App\Repository\CommandeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommandeRepository::class)]
#[ORM\Table(name: 'commande')]
class Commande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column]
    private ?float $totalAmount = null;

    #[ORM\Column(length: 50)]
    private ?string $status = 'en_attente';

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "Shipping address is required.")]
    #[Assert\Length(min: 8, max: 255, minMessage: "Shipping address must be at least {{ limit }} characters long.")]
    private ?string $shippingAddress = null;

    #[ORM\Column(length: 20)]
    #[Assert\NotBlank(message: "Contact phone number is required.")]
    #[Assert\Regex(pattern: "/^\d{8}$/", message: "Phone number must be exactly 8 digits.")]
    private ?string $contactPhone = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: "Payment method must be selected.")]
    #[Assert\Choice(choices: ["cash_on_delivery", "bank_card"], message: "Invalid payment method selected.")]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    /** @var array<int, array<string, mixed>> */
    #[ORM\Column]
    private array $itemsDetails = [];

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getTotalAmount(): ?float
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(float $totalAmount): static
    {
        $this->totalAmount = $totalAmount;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getShippingAddress(): ?string
    {
        return $this->shippingAddress;
    }

    public function setShippingAddress(string $shippingAddress): static
    {
        $this->shippingAddress = $shippingAddress;

        return $this;
    }

    public function getContactPhone(): ?string
    {
        return $this->contactPhone;
    }

    public function setContactPhone(string $contactPhone): static
    {
        $this->contactPhone = $contactPhone;

        return $this;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(string $paymentMethod): static
    {
        $this->paymentMethod = $paymentMethod;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /** @return array<int, array<string, mixed>> */
    public function getItemsDetails(): array
    {
        return $this->itemsDetails;
    }

    /** @param array<int, array<string, mixed>> $itemsDetails */
    public function setItemsDetails(array $itemsDetails): static
    {
        $this->itemsDetails = $itemsDetails;

        return $this;
    }
}
