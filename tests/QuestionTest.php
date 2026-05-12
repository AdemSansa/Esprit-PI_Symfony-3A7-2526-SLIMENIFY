<?php

namespace App\Tests;

use App\Entity\Question;
use Doctrine\Common\Collections\Collection;
use PHPUnit\Framework\TestCase;

class QuestionTest extends TestCase
{
    private function logSuccess(string $message): void
    {
        echo "\e[32m  ✓ $message\e[0m\n";
    }

    private function logTestStart(string $name): void
    {
        echo "\n\e[34m[TEST] Running $name...\e[0m\n";
    }

    public function testInitialization(): void
    {
        $this->logTestStart('testInitialization');
        $question = new Question();
        
        $this->assertInstanceOf(\DateTimeImmutable::class, $question->getCreatedAt());
        $this->logSuccess('CreatedAt is DateTimeImmutable');
        
        $this->assertInstanceOf(Collection::class, $question->getQuizzes());
        $this->assertCount(0, $question->getQuizzes());
        $this->logSuccess('Quizzes collection initialized empty');
        
        $this->assertTrue($question->isRequired());
        $this->logSuccess('Default isRequired is true');
        
        $this->assertSame('', $question->getImagePath());
        $this->logSuccess('Default imagePath is empty string');
    }

    public function testSetAndGetQuestionText(): void
    {
        $this->logTestStart('testSetAndGetQuestionText');
        $question = new Question();
        $text = 'What is your favorite color?';
        $question->setQuestionText($text);

        $this->assertEquals($text, $question->getQuestionText());
        $this->logSuccess("Question text verified: '$text'");
    }

    public function testSetAndGetRequired(): void
    {
        $this->logTestStart('testSetAndGetRequired');
        $question = new Question();
        
        $question->setRequired(false);
        $this->assertFalse($question->isRequired());
        $this->logSuccess('Requirement status can be toggled to false');
        
        $question->setRequired(true);
        $this->assertTrue($question->isRequired());
        $this->logSuccess('Requirement status can be toggled back to true');
    }

    public function testSetAndGetImagePath(): void
    {
        $this->logTestStart('testSetAndGetImagePath');
        $question = new Question();
        $path = '/images/questions/q1.jpg';
        $question->setImagePath($path);

        $this->assertEquals($path, $question->getImagePath());
        $this->logSuccess("Image path verified: '$path'");
    }

    public function testSetAndGetCategory(): void
    {
        $this->logTestStart('testSetAndGetCategory');
        $question = new Question();
        $category = 'Cognitive';
        $question->setCategory($category);

        $this->assertEquals($category, $question->getCategory());
        $this->logSuccess("Category verified: '$category'");
    }
}
