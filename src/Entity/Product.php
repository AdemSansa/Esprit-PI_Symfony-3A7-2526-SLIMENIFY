<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: \App\Repository\ProductRepository::class)]
#[ORM\Table(name: 'products')]
#[UniqueEntity(fields: ['name'], message: 'A product with this name already exists in the inventory.')]
class Product
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank(message: "Product name is required to continue.")]
    #[Assert\Length(
        min: 2, 
        max: 150, 
        minMessage: "The name must be at least {{ limit }} characters long.",
        maxMessage: "The name cannot exceed {{ limit }} characters."
    )]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    #[Assert\NotBlank(message: "A price must be specified for this product.")]
    #[Assert\PositiveOrZero(message: "The price must be a positive number or zero.")]
    private ?string $price = null;

    #[ORM\Column(options: ['default' => 0])]
    #[Assert\NotNull(message: "Stock quantity cannot be empty.")]
    #[Assert\PositiveOrZero(message: "Stock quantity must be zero or a positive integer.")]
    private ?int $stockQuantity = 0;

    #[ORM\Column(type: 'string', columnDefinition: "ENUM('Authorized Vitamins', 'Psychology Books', 'Relaxing Products', 'Therapeutic Games & Activities')", nullable: true)]
    #[Assert\Choice(choices: [
        'Authorized Vitamins', 'Psychology Books', 'Relaxing Products', 'Therapeutic Games & Activities'
    ], message: "Please select a valid product category.")]
    private ?string $category = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Assert\GreaterThan("today", message: "The expiration date must be a future date.")]
    private ?\DateTimeInterface $expirationDate = null;

    #[ORM\ManyToOne(targetEntity: Supplier::class, inversedBy: 'products')]
    #[ORM\JoinColumn(name: 'supplier_id', referencedColumnName: 'id', nullable: true)]
    private ?Supplier $supplier = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photoUrl = null;

    #[ORM\Column(length: 20, options: ['default' => 'available'])]
    private ?string $status = 'available';

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['default' => 'CURRENT_TIMESTAMP'])]
    private ?\DateTimeInterface $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->updatedAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(string $price): static
    {
        $this->price = $price;

        return $this;
    }

    public function getStockQuantity(): ?int
    {
        return $this->stockQuantity;
    }

    public function setStockQuantity(int $stockQuantity): static
    {
        $this->stockQuantity = $stockQuantity;

        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(?string $category): static
    {
        $this->category = $category;

        return $this;
    }

    public function getExpirationDate(): ?\DateTimeInterface
    {
        return $this->expirationDate;
    }

    public function setExpirationDate(?\DateTimeInterface $expirationDate): static
    {
        $this->expirationDate = $expirationDate;

        return $this;
    }

    public function getSupplier(): ?Supplier
    {
        return $this->supplier;
    }

    public function setSupplier(?Supplier $supplier): static
    {
        $this->supplier = $supplier;

        return $this;
    }

    public function getPhotoUrl(): ?string
    {
        return $this->photoUrl;
    }

    public function setPhotoUrl(?string $photoUrl): static
    {
        $this->photoUrl = $photoUrl;

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

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
