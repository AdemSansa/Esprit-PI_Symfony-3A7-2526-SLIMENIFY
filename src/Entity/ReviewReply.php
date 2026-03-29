<?php

namespace App\Entity;

use App\Repository\ReviewReplyRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReviewReplyRepository::class)]
#[ORM\Table(name: 'review_reply')]
class ReviewReply
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'id_reply', type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    #[ORM\ManyToOne(targetEntity: Review::class, inversedBy: 'replies')]
    #[ORM\JoinColumn(name: 'id_review', referencedColumnName: 'id_review', nullable: false)]
    private Review $review;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'reviewReplies')]
    #[ORM\JoinColumn(name: 'id_therapist', referencedColumnName: 'id', nullable: true)]
    private ?User $therapist = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getContent(): string { return $this->content; }
    public function setContent(string $v): static { $this->content = $v; return $this; }
    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $v): static { $this->createdAt = $v; return $this; }
    public function getReview(): Review { return $this->review; }
    public function setReview(Review $v): static { $this->review = $v; return $this; }
    public function getTherapist(): ?User { return $this->therapist; }
    public function setTherapist(?User $v): static { $this->therapist = $v; return $this; }
}
