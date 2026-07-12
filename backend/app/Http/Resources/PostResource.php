<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'body' => $this->body,
            'image_url' => $this->image_url,
            'visibility' => $this->visibility,
            'likes_count' => $this->likes_count,
            'comments_count' => $this->comments_count,
            // Populated via Post::query()->withExists(['likes as liked_by_me' => ...])
            // in the controller so the feed avoids an N+1 query per post.
            'liked_by_me' => (bool) ($this->liked_by_me ?? false),
            'is_mine' => $this->user_id === $request->user()?->id,
            'created_at' => $this->created_at,
            'user' => new UserResource($this->whenLoaded('user')),
        ];
    }
}
