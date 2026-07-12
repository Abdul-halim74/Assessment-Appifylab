<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LikeToggleTest extends TestCase
{
    use RefreshDatabase;

    public function test_liking_a_post_increments_its_counter(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/likes', [
            'likeable_type' => 'post',
            'likeable_id' => $post->id,
        ]);

        $response->assertOk()->assertJson(['liked' => true, 'likes_count' => 1]);
        $this->assertDatabaseHas('likes', [
            'user_id' => $user->id,
            'likeable_type' => 'post',
            'likeable_id' => $post->id,
        ]);
    }

    public function test_liking_twice_toggles_the_like_back_off(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();
        $payload = ['likeable_type' => 'post', 'likeable_id' => $post->id];

        $this->actingAs($user)->postJson('/api/likes', $payload)->assertJson(['liked' => true]);
        $response = $this->actingAs($user)->postJson('/api/likes', $payload);

        $response->assertOk()->assertJson(['liked' => false, 'likes_count' => 0]);
        $this->assertDatabaseMissing('likes', [
            'user_id' => $user->id,
            'likeable_type' => 'post',
            'likeable_id' => $post->id,
        ]);
    }

    public function test_a_user_cannot_like_the_same_post_twice_at_the_db_level(): void
    {
        $user = User::factory()->create();
        $post = Post::factory()->create();

        $post->likes()->create(['user_id' => $user->id, 'likeable_type' => 'post']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        $post->likes()->create(['user_id' => $user->id, 'likeable_type' => 'post']);
    }

    public function test_comments_and_replies_can_also_be_liked(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/likes', [
            'likeable_type' => 'comment',
            'likeable_id' => $comment->id,
        ]);

        $response->assertOk()->assertJson(['liked' => true, 'likes_count' => 1]);
    }

    public function test_who_liked_a_post_can_be_listed(): void
    {
        $liker = User::factory()->create();
        $post = Post::factory()->create();
        $post->likes()->create(['user_id' => $liker->id, 'likeable_type' => 'post']);

        $response = $this->actingAs($liker)->getJson("/api/likes?likeable_type=post&likeable_id={$post->id}");

        $response->assertOk();
        $this->assertEquals($liker->id, $response->json('data.0.id'));
    }
}
