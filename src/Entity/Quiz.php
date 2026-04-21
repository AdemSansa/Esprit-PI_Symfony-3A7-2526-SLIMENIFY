<?php

namespace App\Entity;

use App\Repository\QuizRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: QuizRepository::class)]
#[ORM\Table(name: 'quiz')]
class Quiz
{
    public const STATUS_INACTIVE = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_UNDER_REVIEW = 2;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 150)]
    #[Assert\NotBlank(message: 'The quiz title cannot be empty.')]
    #[Assert\Length(max: 150, maxMessage: 'The title cannot be longer than {{ limit }} characters.')]
    private string $title;

    #[ORM\Column(type: 'text', nullable: true)]
    #[Assert\NotBlank(message: 'Please provide a description for the quiz.')]
    #[Assert\Length(max: 5000, maxMessage: 'The description cannot be longer than {{ limit }} characters.')]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\NotBlank(message: 'Please provide a category for the quiz.')]
    #[Assert\Length(max: 50, maxMessage: 'The category cannot be longer than {{ limit }} characters.')]
    private ?string $category = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero(message: 'Total questions must be zero or a positive number.')]
    private int $totalQuestions = 0;

    #[ORM\Column(type: 'smallint', options: ['default' => self::STATUS_UNDER_REVIEW])]
    private int $active = self::STATUS_UNDER_REVIEW;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $rejectionComment = null;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\PositiveOrZero(message: 'Minimum score must be zero or a positive number.')]
    private int $minScore = 0;

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Assert\Type(type: 'integer')]
    #[Assert\PositiveOrZero(message: 'Maximum score must be zero or a positive number.')]
    private int $maxScore = 0;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updatedAt;

    #[ORM\ManyToMany(targetEntity: Question::class, inversedBy: 'quizzes')]
    #[ORM\JoinTable(
        name: 'quiz_question',
        joinColumns: [new ORM\JoinColumn(name: 'quiz_id', referencedColumnName: 'id')],
        inverseJoinColumns: [new ORM\JoinColumn(name: 'question_id', referencedColumnName: 'id')]
    )]
    #[Assert\Count(min: 1, minMessage: 'You must select at least one question for this quiz.')]
    private Collection $questions;

    #[ORM\OneToMany(mappedBy: 'quiz', targetEntity: QuizResult::class)]
    private Collection $results;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $author = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->questions = new ArrayCollection();
        $this->results = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getTitle(): string { return $this->title; }
    public function setTitle(string $v): static { $this->title = $v; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $v): static { $this->description = $v; return $this; }
    public function getCategory(): ?string { return $this->category; }
    public function setCategory(?string $v): static { $this->category = $v; return $this; }
    public function getTotalQuestions(): int { return $this->totalQuestions; }
    public function setTotalQuestions(int $v): static { $this->totalQuestions = $v; return $this; }
    
    public function isActive(): bool { return $this->active === self::STATUS_ACTIVE; }
    public function isUnderReview(): bool { return $this->active === self::STATUS_UNDER_REVIEW; }
    public function isInactive(): bool { return $this->active === self::STATUS_INACTIVE; }
    public function setActive(int $v): static { $this->active = $v; return $this; }
    public function getRejectionComment(): ?string { return $this->rejectionComment; }
    public function setRejectionComment(?string $comment): static { $this->rejectionComment = $comment; return $this; }
    public function getMinScore(): int { return $this->minScore; }
    public function setMinScore(int $v): static { $this->minScore = $v; return $this; }
    public function getMaxScore(): int { return $this->maxScore; }
    public function setMaxScore(int $v): static { $this->maxScore = $v; return $this; }
    public function getEstimatedTime(): int
    {
        return $this->totalQuestions * 45;
    }

    public function getEstimatedTimeFormatted(): string
    {
        $seconds = $this->getEstimatedTime();
        if ($seconds === 0) {
            return '0s';
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes > 0) {
            return sprintf('%dm %ds', $minutes, $remainingSeconds);
        }

        return sprintf('%ds', $remainingSeconds);
    }

    public function getParticipantCount(): int
    {
        $userIds = [];
        foreach ($this->results as $result) {
            $userIds[$result->getUser()->getId()] = true;
        }
        return count($userIds);
    }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $v): static { $this->createdAt = $v; return $this; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $v): static { $this->updatedAt = $v; return $this; }
    public function getQuestions(): Collection { return $this->questions; }
    public function addQuestion(Question $q): static { if (!$this->questions->contains($q)) { $this->questions->add($q); } return $this; }
    public function removeQuestion(Question $q): static { $this->questions->removeElement($q); return $this; }
    public function getResults(): Collection { return $this->results; }
    
    public function getAuthor(): ?User { return $this->author; }
    public function setStatus(int $v): static { return $this->setActive($v); }
    public function getStatus(): int { return $this->getActive(); }
    public function getActive(): int { return $this->active; }
    public function setAuthor(?User $author): static { $this->author = $author; return $this; }
}
