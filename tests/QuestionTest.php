<?php

namespace App\Tests;

use App\Entity\Question;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class QuestionTest extends TestCase
{
    public function testInitialization(): void
    {
        $question = new Question();
        $this->assertInstanceOf(\DateTimeImmutable::class, $question->getCreatedAt());
        $this->assertInstanceOf(Collection::class, $question->getQuizzes());
        $this->assertCount(0, $question->getQuizzes());
        $this->assertTrue($question->isRequired());
        $this->assertSame('', $question->getImagePath());
    }

    public function testSetAndGetQuestionText(): void
    {
        $question = new Question();
        $text = 'What is your favorite color?';
        $question->setQuestionText($text);

        $this->assertEquals($text, $question->getQuestionText());
    }

    public function testSetAndGetRequired(): void
    {
        $question = new Question();
        $question->setRequired(false);

        $this->assertFalse($question->isRequired());
    }

    public function testSetAndGetImagePath(): void
    {
        $question = new Question();
        $path = '/uploads/images/q1.png';
        $question->setImagePath($path);

        $this->assertEquals($path, $question->getImagePath());
    }

    public function testSetAndGetCategory(): void
    {
        $question = new Question();
        $category = 'Psychology';
        $question->setCategory($category);

        $this->assertEquals($category, $question->getCategory());
    }

    public function testSetAndGetCreatedAt(): void
    {
        $question = new Question();
        $date = new \DateTimeImmutable('2026-05-01 12:00:00');
        $question->setCreatedAt($date);

        $this->assertSame($date, $question->getCreatedAt());
    }
}
