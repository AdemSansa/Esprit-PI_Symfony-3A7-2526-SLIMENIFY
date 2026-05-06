<?php
namespace App\Service;

use App\Entity\Blog;

class BlogManager
{
    public function validate(Blog $blog): bool
    {
        if (empty($blog->getTitle())) {
            throw new \InvalidArgumentException('Le titre est obligatoire');
        }

        if (empty($blog->getContent())) {
            throw new \InvalidArgumentException('Le contenu est obligatoire');
        }

        return true;
    }
}