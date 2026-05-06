<?php

namespace App\Service;

use App\Entity\BlogFavorite;

class BlogFavoriteManager
{
    public function validate(BlogFavorite $blogFavorite): bool
    {
        if ($blogFavorite->getBlog() === null) {
            throw new \InvalidArgumentException('Le blog est obligatoire');
        }

        if ($blogFavorite->getUser() === null) {
            throw new \InvalidArgumentException('L\'utilisateur est obligatoire');
        }

        return true;
    }
}
