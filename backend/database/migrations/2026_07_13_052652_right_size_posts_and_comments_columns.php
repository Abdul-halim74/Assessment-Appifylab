<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Matches a manually-proposed structure: INT instead of BIGINT for
     * posts.id and comments.id/post_id/parent_id, TEXT for comments.body
     * (reverting the earlier VARCHAR(2000) right-size), INT for
     * comments.likes_count (reverting the earlier MEDIUMINT right-size),
     * and DATETIME instead of TIMESTAMP for comments' timestamps.
     *
     * comments.post_id and comments.parent_id both have to move to INT in
     * lockstep with posts.id/comments.id, since InnoDB requires a FK column
     * and its referenced column to share the exact same type.
     */
    public function up(): void
    {
        Schema::table('comments', fn (Blueprint $table) => $table->dropForeign(['parent_id']));
        Schema::table('comments', fn (Blueprint $table) => $table->dropForeign(['post_id']));

        Schema::table('posts', fn (Blueprint $table) => $table->unsignedInteger('id')->autoIncrement()->change());

        Schema::table('comments', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement()->change();
            $table->unsignedInteger('post_id')->change();
            $table->unsignedInteger('parent_id')->nullable()->change();
            $table->text('body')->change();
            $table->unsignedInteger('likes_count')->default(0)->change();
            $table->dateTime('created_at')->nullable()->change();
            $table->dateTime('updated_at')->nullable()->change();
        });

        Schema::table('comments', fn (Blueprint $table) => $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete());
        Schema::table('comments', fn (Blueprint $table) => $table->foreign('parent_id')->references('id')->on('comments')->cascadeOnDelete());
    }

    public function down(): void
    {
        Schema::table('comments', fn (Blueprint $table) => $table->dropForeign(['parent_id']));
        Schema::table('comments', fn (Blueprint $table) => $table->dropForeign(['post_id']));

        Schema::table('posts', fn (Blueprint $table) => $table->unsignedBigInteger('id')->autoIncrement()->change());

        Schema::table('comments', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->autoIncrement()->change();
            $table->unsignedBigInteger('post_id')->change();
            $table->unsignedBigInteger('parent_id')->nullable()->change();
            $table->string('body', 2000)->change();
            $table->unsignedMediumInteger('likes_count')->default(0)->change();
            $table->timestamp('created_at')->nullable()->change();
            $table->timestamp('updated_at')->nullable()->change();
        });

        Schema::table('comments', fn (Blueprint $table) => $table->foreign('post_id')->references('id')->on('posts')->cascadeOnDelete());
        Schema::table('comments', fn (Blueprint $table) => $table->foreign('parent_id')->references('id')->on('comments')->cascadeOnDelete());
    }
};
