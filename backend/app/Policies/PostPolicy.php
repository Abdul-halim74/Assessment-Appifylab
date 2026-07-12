<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    /**
     * Anyone authenticated can create a post.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Public posts are visible to everyone; private posts only to their author.
     */
    public function view(User $user, Post $post): bool
    {
        return $post->visibility === 'public' || $post->user_id === $user->id;
    }

    public function update(User $user, Post $post): bool
    {
        return $post->user_id === $user->id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $post->user_id === $user->id;
    }
}
