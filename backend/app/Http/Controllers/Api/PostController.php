<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePostRequest;
use App\Http\Resources\PostResource;
use App\Models\Post;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    /**
     * Cursor-paginated global feed, newest first, filtered to posts the
     * current user is allowed to see (public posts + their own private ones).
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $posts = Post::query()
            ->visibleTo($request->user())
            ->with('user')
            ->withExists(['likes as liked_by_me' => function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            }])
            // Explicit id tiebreaker: created_at has only second precision, so
            // posts created within the same second need a deterministic,
            // truly-newest-first order (cursorPaginate's implicit tiebreaker
            // does not default to DESC here).
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(10)
            ->withQueryString();

        return PostResource::collection($posts);
    }

    public function store(StorePostRequest $request): JsonResponse
    {
        $this->authorize('create', Post::class);

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('posts', 'public')
            : null;

        $post = $request->user()->posts()->create([
            'body' => $request->input('body'),
            'image_path' => $imagePath,
            'visibility' => $request->input('visibility'),
        ]);

        $post->load('user');

        return (new PostResource($post))->response()->setStatusCode(201);
    }

    public function destroy(Request $request, Post $post): JsonResponse
    {
        $this->authorize('delete', $post);

        if ($post->image_path) {
            Storage::disk('public')->delete($post->image_path);
        }

        $post->delete();

        return response()->json(['message' => 'Post deleted.']);
    }
}
