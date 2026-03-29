<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
#[ORM\Table(name: 'question')]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;

    #[ORM\Column(name: 'question_text', type: 'string', length: 255)]
    private string $questionText;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $required = true;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(name: 'image_path', type: 'string', length: 255)]
    private string $imagePath;

    #[ORM\ManyToMany(targetEntity: Quiz::class, mappedBy: 'questions')]
    private Collection $quizzes;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->quizzes = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }
    public function getQuestionText(): string { return $this->questionText; }
    public function setQuestionText(string $v): static { $this->questionText = $v; return $this; }
    public function isRequired(): bool { return $this->required; }
    public function setRequired(bool $v): static { $this->required = $v; return $this; }
    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $v): static { $this->createdAt = $v; return $this; }
    public function getImagePath(): string { return $this->imagePath; }
    public function setImagePath(string $v): static { $this->imagePath = $v; return $this; }
    public function getQuizzes(): Collection { return $this->quizzes; }
}
