<?php

namespace Tests\Feature;

use App\Models\Post;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_private_post_is_hidden_from_other_users_feed(): void
    {
        $author = User::factory()->create();
        $stranger = User::factory()->create();
        $privatePost = Post::factory()->for($author)->private()->create();

        $response = $this->actingAs($stranger)->getJson('/api/feed');

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertFalse($ids->contains($privatePost->id));
    }

    public function test_private_post_is_visible_to_its_author(): void
    {
        $author = User::factory()->create();
        $privatePost = Post::factory()->for($author)->private()->create();

        $response = $this->actingAs($author)->getJson('/api/feed');

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($privatePost->id));
    }

    public function test_stranger_cannot_comment_on_a_private_post(): void
    {
        $author = User::factory()->create();
        $stranger = User::factory()->create();
        $privatePost = Post::factory()->for($author)->private()->create();

        $response = $this->actingAs($stranger)
            ->postJson("/api/posts/{$privatePost->id}/comments", ['body' => 'sneaky comment']);

        $response->assertForbidden();
    }

    public function test_stranger_cannot_like_a_private_post(): void
    {
        $author = User::factory()->create();
        $stranger = User::factory()->create();
        $privatePost = Post::factory()->for($author)->private()->create();

        $response = $this->actingAs($stranger)->postJson('/api/likes', [
            'likeable_type' => 'post',
            'likeable_id' => $privatePost->id,
        ]);

        $response->assertForbidden();
    }

    public function test_only_the_author_can_delete_their_post(): void
    {
        $author = User::factory()->create();
        $stranger = User::factory()->create();
        $post = Post::factory()->for($author)->create();

        $this->actingAs($stranger)->deleteJson("/api/posts/{$post->id}")->assertForbidden();
        $this->actingAs($author)->deleteJson("/api/posts/{$post->id}")->assertOk();
        $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    }
}
