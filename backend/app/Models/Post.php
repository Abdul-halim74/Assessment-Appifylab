<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Storage;

#[Fillable(['body', 'image_path', 'visibility'])]
class Post extends Model
{
    use HasFactory;

    protected $appends = ['image_url'];

    /**
     * Mirror the DB column defaults in PHP too, so a freshly-created instance
     * (before any reload) reflects 0 rather than null in API responses.
     */
    protected $attributes = [
        'likes_count' => 0,
        'comments_count' => 0,
    ];

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Top-level comments only; replies are loaded via Comment::replies().
     *
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id');
    }

    /**
     * @return MorphMany<Like, $this>
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'likeable');
    }

    /**
     * Scope a query to posts visible to the given user: public posts, plus
     * the user's own private posts. Private posts are never visible to anyone else.
     *
     * @param  Builder<Post>  $query
     * @return Builder<Post>
     */
    public function scopeVisibleTo(Builder $query, ?User $user): Builder
    {
        return $query->where(function (Builder $query) use ($user) {
            $query->where('visibility', 'public');

            if ($user) {
                $query->orWhere(function (Builder $query) use ($user) {
                    $query->where('visibility', 'private')->where('user_id', $user->id);
                });
            }
        });
    }
}
