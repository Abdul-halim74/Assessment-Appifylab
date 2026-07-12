<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Like;
use App\Models\Post;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with a known demo account plus
     * random users/posts/comments/replies/likes for the feed walkthrough.
     */
    public function run(): void
    {
        $demo = User::factory()->create([
            'first_name' => 'Demo',
            'last_name' => 'User',
            'email' => 'demo@example.com',
            'password' => Hash::make('password'),
        ]);

        $others = User::factory(9)->create();
        $users = $others->push($demo);

        $users->each(function (User $author) use ($users) {
            Post::factory()
                ->count(fake()->numberBetween(2, 5))
                ->state(fn () => ['visibility' => fake()->boolean(80) ? 'public' : 'private'])
                ->for($author)
                ->create()
                ->each(function (Post $post) use ($users) {
                    $this->seedComments($post, $users);
                    $this->seedLikes($post, $users);
                });
        });
    }

    private function seedComments(Post $post, $users): void
    {
        $topLevel = Comment::factory()
            ->count(fake()->numberBetween(0, 4))
            ->for($post)
            ->for($users->random())
            ->create();

        foreach ($topLevel as $comment) {
            $replyCount = fake()->numberBetween(0, 3);

            if ($replyCount > 0) {
                Comment::factory()
                    ->count($replyCount)
                    ->for($post)
                    ->for($users->random())
                    ->create(['parent_id' => $comment->id]);
            }

            $this->seedLikes($comment, $users);
        }

        $post->update(['comments_count' => Comment::where('post_id', $post->id)->count()]);
    }

    private function seedLikes(Post|Comment $target, $users): void
    {
        $likers = $users->random(fake()->numberBetween(0, min(6, $users->count())));
        $type = $target instanceof Post ? 'post' : 'comment';

        foreach ($likers as $liker) {
            Like::create([
                'user_id' => $liker->id,
                'likeable_type' => $type,
                'likeable_id' => $target->id,
            ]);
        }

        $target->update(['likes_count' => count($likers)]);
    }
}
