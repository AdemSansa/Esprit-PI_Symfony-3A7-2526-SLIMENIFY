<?php

namespace App\Entity;

use App\Repository\CommentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
class Comment
{
    #[ORM\Id, ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: "Comment cannot be empty")]
    #[Assert\Length(min: 2, minMessage: "Comment too short")]
    #[ORM\Column(type: "text")]
    private ?string $content = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Blog::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $blog;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private $user;

    #[ORM\ManyToOne(targetEntity: Therapist::class)]
    private $therapist;

    #[ORM\ManyToOne(targetEntity: self::class)]
    private $parent;

    #[ORM\OneToMany(mappedBy: 'comment', targetEntity: CommentLike::class, cascade: ['remove'])]
    private Collection $likes;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->likes = new ArrayCollection();
    }

    // GETTERS & SETTERS

    public function getId(): ?int { return $this->id; }

    public function getContent(): ?string { return $this->content; }
    public function setContent(?string $content): self { $this->content = $content; return $this; }

    public function getCreatedAt(): ?\DateTime { return $this->createdAt; }

    public function getBlog() { return $this->blog; }
    public function setBlog($blog): self { $this->blog = $blog; return $this; }

    public function getUser() { return $this->user; }
    public function setUser($user): self { $this->user = $user; return $this; }

    public function getTherapist() { return $this->therapist; }
    public function setTherapist($therapist): self { $this->therapist = $therapist; return $this; }

    public function getParent() { return $this->parent; }
    public function setParent($parent): self { $this->parent = $parent; return $this; }

    /** @return Collection<int, CommentLike> */
    public function getLikes(): Collection { return $this->likes; }

    public function addLike(CommentLike $like): self
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setComment($this);
        }
        return $this;
    }

    public function removeLike(CommentLike $like): self
    {
        if ($this->likes->removeElement($like)) {
            if ($like->getComment() === $this) {
                $like->setComment(null);
            }
        }
        return $this;
    }
}