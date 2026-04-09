<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'reply_review')]
class ReviewReply
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Review::class, inversedBy: 'replies')]
    #[ORM\JoinColumn(name: 'id_review', referencedColumnName: 'id', nullable: false)]
    private Review $review;

    #[ORM\ManyToOne(targetEntity: Therapist::class)]
    #[ORM\JoinColumn(name: 'id_therapist', referencedColumnName: 'id', nullable: false)]
    private Therapist $therapist;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'integer')]
    private int $likes = 0;

    #[ORM\Column(type: 'integer')]
    private int $dislikes = 0;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'visible';

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }

    public function getContent(): string { return $this->content; }
    public function setContent(string $c): static { $this->content = $c; return $this; }

    public function getReview(): Review { return $this->review; }
    public function setReview(Review $r): static { $this->review = $r; return $this; }

    public function getTherapist(): Therapist { return $this->therapist; }
    public function setTherapist(Therapist $t): static { $this->therapist = $t; return $this; }

    public function getLikes(): int { return $this->likes; }
    public function setLikes(int $l): static { $this->likes = $l; return $this; }

    public function getDislikes(): int { return $this->dislikes; }
    public function setDislikes(int $d): static { $this->dislikes = $d; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $s): static { $this->status = $s; return $this; }

    public function getCreatedAt(): \DateTimeInterface { return $this->createdAt; }
    public function setCreatedAt(\DateTimeInterface $d): static { $this->createdAt = $d; return $this; }
}