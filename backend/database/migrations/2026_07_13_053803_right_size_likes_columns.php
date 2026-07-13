<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * likes.id has no other table pointing at it, and likeable_id is a
     * polymorphic reference (no real FK constraint) — so unlike the
     * users/posts/comments changes, this one needs no FK drop/recreate
     * dance. Now matches posts.id/comments.id, which likeable_id points at.
     */
    public function up(): void
    {
        Schema::table('likes', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement()->change();
            $table->unsignedInteger('likeable_id')->change();
        });
    }

    public function down(): void
    {
        Schema::table('likes', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->autoIncrement()->change();
            $table->unsignedBigInteger('likeable_id')->change();
        });
    }
};
