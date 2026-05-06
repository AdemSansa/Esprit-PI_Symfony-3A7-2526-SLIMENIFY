<?php

namespace App\Tests\Service;

use App\Entity\Blog;
use App\Entity\BlogFavorite;
use App\Entity\User;
use App\Service\BlogFavoriteManager;
use PHPUnit\Framework\TestCase;

class BlogFavoriteManagerTest extends TestCase
{
    private BlogFavoriteManager $manager;

    protected function setUp(): void
    {
        $this->manager = new BlogFavoriteManager();
    }

    private function makeValidFavorite(): BlogFavorite
    {
        $favorite = new BlogFavorite();
        $favorite->setBlog(new Blog());
        $favorite->setUser(new User());
        return $favorite;
    }

    public function testValidateReturnsTrueForValidFavorite(): void
    {
        $result = $this->manager->validate($this->makeValidFavorite());

        $this->assertTrue($result);
    }

    public function testValidateThrowsExceptionWhenBlogIsNull(): void
    {
        $favorite = new BlogFavorite();
        $favorite->setUser(new User());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le blog est obligatoire');

        $this->manager->validate($favorite);
    }

    public function testValidateThrowsExceptionWhenUserIsNull(): void
    {
        $favorite = new BlogFavorite();
        $favorite->setBlog(new Blog());

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('L\'utilisateur est obligatoire');

        $this->manager->validate($favorite);
    }

    public function testValidateThrowsExceptionWhenBothAreNull(): void
    {
        $favorite = new BlogFavorite();

        // Blog est vérifié en premier
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Le blog est obligatoire');

        $this->manager->validate($favorite);
    }

    public function testValidateWithDifferentUsersAndSameBlog(): void
    {
        $blog = new Blog();

        $favorite1 = new BlogFavorite();
        $favorite1->setBlog($blog);
        $favorite1->setUser(new User());

        $favorite2 = new BlogFavorite();
        $favorite2->setBlog($blog);
        $favorite2->setUser(new User());

        $this->assertTrue($this->manager->validate($favorite1));
        $this->assertTrue($this->manager->validate($favorite2));
    }
}
