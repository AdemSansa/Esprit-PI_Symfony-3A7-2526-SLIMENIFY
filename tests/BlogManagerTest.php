<?php
namespace App\Tests\Service;

use App\Entity\Blog;
use App\Service\BlogManager;
use PHPUnit\Framework\TestCase;

class BlogManagerTest extends TestCase
{
    public function testValidBlog()
    {
        $blog = new Blog();
        $blog->setTitle('Mon premier article');
        $blog->setContent('Contenu de larticle...');

        $manager = new BlogManager();
        $this->assertTrue($manager->validate($blog));
    }

    public function testBlogWithoutTitle()
    {
        $this->expectException(\InvalidArgumentException::class);

        $blog = new Blog();
        $blog->setContent('Contenu sans titre');

        $manager = new BlogManager();
        $manager->validate($blog);
    }

    public function testBlogWithoutContent()
    {
        $this->expectException(\InvalidArgumentException::class);

        $blog = new Blog();
        $blog->setTitle('Titre sans contenu');

        $manager = new BlogManager();
        $manager->validate($blog);
    }
}