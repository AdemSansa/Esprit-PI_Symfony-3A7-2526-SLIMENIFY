<?php

namespace App\Entity;

use App\Repository\QuizResultRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizResultRepository::class)]
#[ORM\Table(name: 'quiz_results')]
class QuizResult
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(type: 'integer')]
    private int $score;

    #[ORM\Column(type: 'integer')]
    private int $result;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $mood = null;

    #[ORM\Column(type: 'datetime')]
    private \DateTimeInterface $takenAt;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'quizResults')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\ManyToOne(targetEntity: Quiz::class, inversedBy: 'results')]
    #[ORM\JoinColumn(name: 'quiz_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Quiz $quiz;

    public function __construct()
    {
        $this->takenAt = new \DateTime();
    }

    public function getId(): ?int { return $this->id; }
    public function getScore(): int { return $this->score; }
    public function setScore(int $v): static { $this->score = $v; return $this; }
    public function getResult(): int { return $this->result; }
    public function setResult(int $v): static { $this->result = $v; return $this; }
    public function getMood(): ?string { return $this->mood; }
    public function setMood(?string $v): static { $this->mood = $v; return $this; }
    public function getTakenAt(): \DateTimeInterface { return $this->takenAt; }

    public function getUser(): User { return $this->user; }
    public function setUser(User $v): static { $this->user = $v; return $this; }
    public function getQuiz(): Quiz { return $this->quiz; }
    public function setQuiz(Quiz $v): static { $this->quiz = $v; return $this; }
}
