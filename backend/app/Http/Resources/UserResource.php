<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'avatar_url' => $this->avatar_url,
            // Email is only ever exposed to the user themselves (e.g. /api/me),
            // never on posts/comments/likers lists.
            'email' => $this->when($request->user()?->id === $this->id, $this->email),
        ];
    }
}
