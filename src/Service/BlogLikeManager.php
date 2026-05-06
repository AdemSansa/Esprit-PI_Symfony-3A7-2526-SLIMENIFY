<?php

namespace App\Service;

use App\Entity\BlogLike;

class BlogLikeManager
{
    public function validate(BlogLike $blogLike): bool
    {
        if ($blogLike->getBlog() === null) {
            throw new \InvalidArgumentException('Le blog est obligatoire');
        }

        if ($blogLike->getUser() === null && $blogLike->getTherapist() === null) {
            throw new \InvalidArgumentException('Un utilisateur ou un thérapeute est obligatoire');
        }

        return true;
    }
}
