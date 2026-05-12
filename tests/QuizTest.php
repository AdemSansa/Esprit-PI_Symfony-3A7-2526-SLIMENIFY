<?php

namespace App\Tests;

use App\Entity\Quiz;
use App\Entity\Question;
use App\Entity\User;
use App\Entity\QuizResult;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class QuizTest extends TestCase
{
    private function logSuccess(string $message): void
    {
        echo "\e[32m  ✓ $message\e[0m\n";
    }

    private function logTestStart(string $name): void
    {
        echo "\n\e[35m[QUIZ-TEST] Starting $name...\e[0m\n";
    }

    public function testInitialization(): void
    {
        $this->logTestStart('testInitialization');
        $quiz = new Quiz();
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $quiz->getCreatedAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $quiz->getUpdatedAt());
        $this->logSuccess('Timestamps initialized correctly');
        
        $this->assertInstanceOf(Collection::class, $quiz->getQuestions());
        $this->assertInstanceOf(Collection::class, $quiz->getResults());
        $this->logSuccess('Collections (Questions/Results) initialized');
        
        $this->assertEquals(0, $quiz->getTotalQuestions());
        $this->assertEquals(Quiz::STATUS_UNDER_REVIEW, $quiz->getStatus());
        $this->logSuccess('Default status and count verified');
    }

    public function testSetAndGetTitle(): void
    {
        $this->logTestStart('testSetAndGetTitle');
        $quiz = new Quiz();
        $title = 'Psychology Evaluation';
        $quiz->setTitle($title);

        $this->assertEquals($title, $quiz->getTitle());
        $this->logSuccess("Title verified: '$title'");
    }

    public function testSetAndGetDescription(): void
    {
        $quiz = new Quiz();
        $desc = 'A quiz to evaluate psychological state.';
        $quiz->setDescription($desc);

        $this->assertEquals($desc, $quiz->getDescription());
    }

    public function testSetAndGetCategory(): void
    {
        $quiz = new Quiz();
        $category = 'Mental Health';
        $quiz->setCategory($category);

        $this->assertEquals($category, $quiz->getCategory());
    }

    public function testSetAndGetTotalQuestions(): void
    {
        $quiz = new Quiz();
        $quiz->setTotalQuestions(15);

        $this->assertEquals(15, $quiz->getTotalQuestions());
    }

    public function testSetAndGetActiveStatus(): void
    {
        $quiz = new Quiz();
        $quiz->setActive(Quiz::STATUS_ACTIVE);

        $this->assertTrue($quiz->isActive());
        $this->assertFalse($quiz->isInactive());
        $this->assertFalse($quiz->isUnderReview());
    }

    public function testSetAndGetRejectionComment(): void
    {
        $quiz = new Quiz();
        $comment = 'Questions need to be more specific.';
        $quiz->setRejectionComment($comment);

        $this->assertEquals($comment, $quiz->getRejectionComment());
    }

    public function testSetAndGetScores(): void
    {
        $quiz = new Quiz();
        $quiz->setMinScore(5);
        $quiz->setMaxScore(50);

        $this->assertEquals(5, $quiz->getMinScore());
        $this->assertEquals(50, $quiz->getMaxScore());
    }

    public function testEstimatedTime(): void
    {
        $quiz = new Quiz();
        
        // 0 questions
        $this->assertEquals(0, $quiz->getEstimatedTime());
        $this->assertEquals('0s', $quiz->getEstimatedTimeFormatted());
        
        // 2 questions = 2 * 45 = 90 seconds
        $quiz->setTotalQuestions(2);
        $this->assertEquals(90, $quiz->getEstimatedTime());
        $this->assertEquals('1m 30s', $quiz->getEstimatedTimeFormatted());
    }

    public function testAddAndRemoveQuestion(): void
    {
        $quiz = new Quiz();
        $question = $this->createMock(Question::class);

        $quiz->addQuestion($question);
        $this->assertCount(1, $quiz->getQuestions());
        $this->assertTrue($quiz->getQuestions()->contains($question));

        $quiz->removeQuestion($question);
        $this->assertCount(0, $quiz->getQuestions());
    }

    public function testSetAndGetAuthor(): void
    {
        $quiz = new Quiz();
        $user = $this->createMock(User::class);
        $quiz->setAuthor($user);

        $this->assertSame($user, $quiz->getAuthor());
    }

    public function testParticipantCount(): void
    {
        $this->logTestStart('testParticipantCount');
        $quiz = new Quiz();
        
        $user1 = $this->createMock(User::class);
        $user1->method('getId')->willReturn(1);
        
        $user2 = $this->createMock(User::class);
        $user2->method('getId')->willReturn(2);

        $result1 = $this->createMock(QuizResult::class);
        $result1->method('getUser')->willReturn($user1);
        
        $result2 = $this->createMock(QuizResult::class);
        $result2->method('getUser')->willReturn($user2);
        
        $result3 = $this->createMock(QuizResult::class);
        $result3->method('getUser')->willReturn($user1); 

        $quiz->getResults()->add($result1);
        $quiz->getResults()->add($result2);
        $quiz->getResults()->add($result3);

        $this->assertEquals(2, $quiz->getParticipantCount());
        $this->logSuccess('Unique participant count verified (multiple attempts by same user correctly counted once)');
    }
}
