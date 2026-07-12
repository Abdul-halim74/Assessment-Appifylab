<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ToggleLikeRequest;
use App\Http\Resources\UserResource;
use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LikeController extends Controller
{
    /**
     * Toggle a like on a post or comment/reply for the current user.
     */
    public function toggle(ToggleLikeRequest $request): JsonResponse
    {
        $target = $this->resolveAuthorizedLikeable(
            $request->string('likeable_type'),
            (int) $request->input('likeable_id')
        );

        $userId = $request->user()->id;
        $morphAlias = $request->string('likeable_type');

        $liked = DB::transaction(function () use ($target, $userId, $morphAlias) {
            $existing = Like::where('user_id', $userId)
                ->where('likeable_type', (string) $morphAlias)
                ->where('likeable_id', $target->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->delete();
                $target->decrement('likes_count');

                return false;
            }

            try {
                Like::create([
                    'user_id' => $userId,
                    'likeable_type' => (string) $morphAlias,
                    'likeable_id' => $target->id,
                ]);
                $target->increment('likes_count');

                return true;
            } catch (\Illuminate\Database\QueryException $e) {
                // 23000 = SQL integrity constraint violation. Only a *unique*
                // violation here means a concurrent duplicate like — anything
                // else is a real bug and must not be swallowed.
                if ($e->getCode() !== '23000') {
                    throw $e;
                }

                return true;
            }
        });

        $target->refresh();

        return response()->json([
            'liked' => $liked,
            'likes_count' => $target->likes_count,
        ]);
    }

    /**
     * Paginated list of users who liked a post/comment (the "who liked this" UI).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $validated = $request->validate([
            'likeable_type' => ['required', 'string', Rule::in(['post', 'comment'])],
            'likeable_id' => ['required', 'integer'],
        ]);

        $target = $this->resolveAuthorizedLikeable($validated['likeable_type'], (int) $validated['likeable_id']);

        $likers = $target->likes()
            ->with('user')
            // Explicit id tiebreaker for same-second timestamps (see PostController::index).
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(20)
            ->withQueryString();

        return UserResource::collection($likers->through(fn (Like $like) => $like->user));
    }

    /**
     * Resolve the liked post/comment and authorize that the current user may view it —
     * likes on a private post must never leak to anyone but its author.
     */
    private function resolveAuthorizedLikeable(string $type, int $id): Post|Comment
    {
        $modelClass = Relation::getMorphedModel($type);

        if (! $modelClass) {
            throw ValidationException::withMessages(['likeable_type' => 'Invalid likeable type.']);
        }

        /** @var Model $target */
        $target = $modelClass::findOrFail($id);

        if ($target instanceof Post) {
            $this->authorize('view', $target);
        } elseif ($target instanceof Comment) {
            $this->authorize('view', $target->post);
        }

        return $target;
    }
}
