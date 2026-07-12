<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommentReplyTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_comment_on_a_post(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)->postJson("/api/posts/{$post->id}/comments", [
            'body' => 'Nice post!',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('comments', ['post_id' => $post->id, 'body' => 'Nice post!', 'parent_id' => null]);
        $this->assertEquals(1, $post->fresh()->comments_count);
    }

    public function test_user_can_reply_to_a_top_level_comment(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->for($post)->create();

        $response = $this->actingAs($user)->postJson("/api/posts/{$post->id}/comments", [
            'body' => 'Totally agree',
            'parent_id' => $comment->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('comments', ['parent_id' => $comment->id, 'body' => 'Totally agree']);
    }

    public function test_replying_to_a_reply_is_rejected_to_keep_nesting_one_level_deep(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $topLevel = Comment::factory()->for($post)->create();
        $reply = Comment::factory()->for($post)->create(['parent_id' => $topLevel->id]);

        $response = $this->actingAs($user)->postJson("/api/posts/{$post->id}/comments", [
            'body' => 'reply to a reply',
            'parent_id' => $reply->id,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['parent_id']);
    }

    public function test_parent_id_from_a_different_post_is_rejected(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $otherPostComment = Comment::factory()->create();

        $response = $this->actingAs($user)->postJson("/api/posts/{$post->id}/comments", [
            'body' => 'cross post reply',
            'parent_id' => $otherPostComment->id,
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors(['parent_id']);
    }

    public function test_feed_comments_endpoint_returns_replies_nested(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $comment = Comment::factory()->for($post)->create();
        Comment::factory()->for($post)->create(['parent_id' => $comment->id]);

        $response = $this->actingAs($user)->getJson("/api/posts/{$post->id}/comments");

        $response->assertOk();
        $this->assertCount(1, $response->json('data.0.replies'));
    }
}
