<?php
namespace App\Service;

use App\Entity\Comment;

class CommentManager
{
    public function validate(Comment $comment): bool
    {
        if (empty($comment->getContent())) {
            throw new \InvalidArgumentException('Le commentaire est obligatoire');
        }

        if ($comment->getRating() < 1 || $comment->getRating() > 5) {
            throw new \InvalidArgumentException('La note doit être entre 1 et 5');
        }

        return true;
    }
}