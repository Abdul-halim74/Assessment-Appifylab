<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    /**
     * Anyone authenticated can comment; whether they may see/comment on the
     * parent post at all is enforced separately via PostPolicy::view.
     */
    public function create(User $user): bool
    {
        return true;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $comment->user_id === $user->id;
    }
}
