<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Right-size a couple of columns to match their actual value ranges.
     *
     * comments.body is capped at 2000 chars by StoreCommentRequest — well
     * under VARCHAR's inline-row-storage threshold, so keeping it TEXT sends
     * every comment read (the hottest path in the app) through InnoDB's
     * off-page storage indirection for no reason. The *_count columns are
     * plain hit counters; nothing here will ever need more than ~16.7M.
     */
    public function up(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->string('body', 2000)->change();
            $table->unsignedMediumInteger('likes_count')->default(0)->change();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedMediumInteger('likes_count')->default(0)->change();
            $table->unsignedMediumInteger('comments_count')->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('comments', function (Blueprint $table) {
            $table->text('body')->change();
            $table->unsignedInteger('likes_count')->default(0)->change();
        });

        Schema::table('posts', function (Blueprint $table) {
            $table->unsignedInteger('likes_count')->default(0)->change();
            $table->unsignedInteger('comments_count')->default(0)->change();
        });
    }
};
