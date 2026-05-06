<?php

namespace App\Tests\Service;

use App\Entity\Blog;
use App\Entity\BlogLike;
use App\Entity\Therapist;
use App\Entity\User;
use App\Service\BlogLikeManager;
use PHPUnit\Framework\TestCase;

class BlogLikeManagerTest extends TestCase
{
    private BlogLikeManager $manager;

    protected function setUp(): void
    {
        $this->manager = new BlogLikeManager();
    }

    public function testValidateReturnsTrueWhenLikedByUser(): void
    {
        $like = new BlogLike();
        $like->setBlog(new Blog());
        $like->setUser(new User());

        $this->assertTrue($this->manager->validate($like));
    }

    public function testValidateReturnsTrueWhenLikedByTherapist(): void
    {
        $like = new BlogLike();
        $like->setBlog(new Blog());
        $like->setTherapist(new Therapist());

        $this->assertTrue($this->manager->validate($like));
    }

    public function testValidateReturnsTrueWhenBothUserAndTherapistAreSet(): void
    {
        $like = new BlogLike();
        $like->setBlog(new Blog());
        $like->setUser(new User());
        $like->setTherapist(new Therapist());

        $this->assertTrue($this->manager->validate($like));
    }

    public function testValidateThrowsExceptionWhenBlogIsNull(): void
    {
        $like = new BlogLike();
        $like->setUser(new User());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le blog est obligatoire');

        $this->manager->validate($like);
    }

    public function testValidateThrowsExceptionWhenBlogIsNullWithTherapist(): void
    {
        $like = new BlogLike();
        $like->setTherapist(new Therapist());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le blog est obligatoire');

        $this->manager->validate($like);
    }

    public function testValidateThrowsExceptionWhenUserAndTherapistAreBothNull(): void
    {
        $like = new BlogLike();
        $like->setBlog(new Blog());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Un utilisateur ou un thérapeute est obligatoire');

        $this->manager->validate($like);
    }

    public function testValidateThrowsExceptionWhenEverythingIsNull(): void
    {
        $like = new BlogLike();

        // Blog est vérifié en premier
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le blog est obligatoire');

        $this->manager->validate($like);
    }
}
