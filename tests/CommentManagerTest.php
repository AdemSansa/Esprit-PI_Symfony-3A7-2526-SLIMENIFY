<?php
namespace App\Tests\Service;

use App\Entity\Comment;
use App\Service\CommentManager;
use PHPUnit\Framework\TestCase;

class CommentManagerTest extends TestCase
{
    public function testValidComment()
    {
        $comment = new Comment();
        $comment->setContent('Super article !');
        $comment->setRating(5);

        $manager = new CommentManager();
        $this->assertTrue($manager->validate($comment));
    }

    public function testCommentWithoutContent()
    {
        $this->expectException(\InvalidArgumentException::class);

        $comment = new Comment();
        $comment->setRating(3);

        $manager = new CommentManager();
        $manager->validate($comment);
    }

    public function testCommentWithInvalidRating()
    {
        $this->expectException(\InvalidArgumentException::class);

        $comment = new Comment();
        $comment->setContent('Bon article');
        $comment->setRating(10); // invalid, must be 1-5

        $manager = new CommentManager();
        $manager->validate($comment);
    }
}