<?php

namespace App\Entity;

use App\Repository\BlogRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use App\Entity\Category;

#[ORM\Entity(repositoryClass: BlogRepository::class)]
class Blog
{
    #[ORM\Id, ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: "Title is required")]
    #[Assert\Length(min: 3, minMessage: "Title must be at least 3 characters")]
    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[Assert\NotBlank(message: "Content is required")]
    #[Assert\Length(min: 10, minMessage: "Content too short")]
    #[ORM\Column(type: "text")]
    private ?string $content = null;

    //#[Assert\NotBlank(message: "photo is required")]
    #[ORM\Column(length: 255)]
    private ?string $photo = null;

    #[ORM\Column]
    private ?\DateTime $createdAt = null;

    #[ORM\ManyToOne(targetEntity: Therapist::class)]
    #[ORM\JoinColumn(nullable: false)]
    private $therapist;

    
    /** @var Collection<int, Comment> */
    #[ORM\OneToMany(mappedBy: 'blog', targetEntity: Comment::class, cascade: ['remove'])]
    private Collection $comments;

    /** @var Collection<int, BlogLike> */
    #[ORM\OneToMany(mappedBy: 'blog', targetEntity: BlogLike::class, cascade: ['remove'])]
    private Collection $likes;

    /** @var Collection<int, BlogFavorite> */
    #[ORM\OneToMany(mappedBy: 'blog', targetEntity: BlogFavorite::class, cascade: ['remove'])]
    private Collection $favorites;

    #[Assert\NotNull(message: "Category is required")]
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'blogs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Category $category = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->likes = new ArrayCollection();
        $this->comments = new ArrayCollection();
        $this->favorites = new ArrayCollection();
    }

    // GETTERS & SETTERS

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = $content;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): self
    {
        $this->photo = $photo;
        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function getTherapist()
    {
        return $this->therapist;
    }

    public function setTherapist($therapist): self
    {
        $this->therapist = $therapist;
        return $this;
    }

   
    /** @return Collection<int, Comment> */
    public function getComments(): Collection
    {
        return $this->comments;
    }
        public function getCategory(): ?Category
{
    return $this->category;
}

    public function setCategory(?Category $category): self
    {
        $this->category = $category;
        return $this;
    }
    /** @return Collection<int, BlogLike> */
    public function getLikes(): Collection
    {
        return $this->likes;
    }

    public function addLike(BlogLike $like): self
    {
        if (!$this->likes->contains($like)) {
            $this->likes->add($like);
            $like->setBlog($this);
        }
        return $this;
    }

    public function removeLike(BlogLike $like): self
    {
        if ($this->likes->removeElement($like)) {
            if ($like->getBlog() === $this) {
                $like->setBlog(null);
            }
        }
        return $this;
    }

    /** @return Collection<int, BlogFavorite> */
    public function getFavorites(): Collection
    {
        return $this->favorites;
    }
}