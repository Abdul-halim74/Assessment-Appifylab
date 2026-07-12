<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Resources\CommentResource;
use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class CommentController extends Controller
{
    /**
     * Top-level comments for a post, each with its (one level of) replies eager
     * loaded, oldest first so the thread reads top-to-bottom.
     */
    public function index(Request $request, Post $post): AnonymousResourceCollection
    {
        $this->authorize('view', $post);

        $userId = $request->user()->id;

        $comments = $post->comments()
            ->with('user')
            ->withExists(['likes as liked_by_me' => fn ($query) => $query->where('user_id', $userId)])
            ->with(['replies' => function ($query) use ($userId) {
                $query->with('user')
                    ->withExists(['likes as liked_by_me' => fn ($q) => $q->where('user_id', $userId)])
                    ->orderBy('created_at')
                    ->orderBy('id');
            }])
            // Explicit id tiebreaker for same-second timestamps (see PostController::index).
            ->orderBy('created_at')
            ->orderBy('id')
            ->cursorPaginate(10)
            ->withQueryString();

        return CommentResource::collection($comments);
    }

    public function store(StoreCommentRequest $request, Post $post): JsonResponse
    {
        $this->authorize('view', $post);
        $this->authorize('create', Comment::class);

        $comment = DB::transaction(function () use ($request, $post) {
            $comment = new Comment([
                'body' => $request->string('body'),
                'parent_id' => $request->input('parent_id'),
            ]);
            $comment->post_id = $post->id;
            $comment->user_id = $request->user()->id;
            $comment->save();

            $post->increment('comments_count');

            return $comment;
        });

        $comment->load('user');

        return (new CommentResource($comment))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        $this->authorize('delete', $comment);

        DB::transaction(function () use ($comment) {
            $descendantCount = 1 + $comment->replies()->count();
            $comment->post()->decrement('comments_count', $descendantCount);
            $comment->delete();
        });

        return response()->json(['message' => 'Comment deleted.']);
    }
}
