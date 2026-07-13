<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Matches a manually-proposed structure for `users`: INT instead of
     * BIGINT for id, VARCHAR(50) for names, DATETIME instead of TIMESTAMP.
     * Every table with a user_id FK has to move to INT UNSIGNED in lockstep
     * with users.id, since InnoDB requires a FK column and its referenced
     * column to share the exact same type.
     */
    public function up(): void
    {
        Schema::table('posts', fn (Blueprint $table) => $table->dropForeign(['user_id']));
        Schema::table('comments', fn (Blueprint $table) => $table->dropForeign(['user_id']));
        Schema::table('likes', fn (Blueprint $table) => $table->dropForeign(['user_id']));

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('id')->autoIncrement()->change();
            $table->string('first_name', 50)->change();
            $table->string('last_name', 50)->change();
            $table->dateTime('created_at')->nullable()->change();
            $table->dateTime('updated_at')->nullable()->change();
        });

        Schema::table('posts', fn (Blueprint $table) => $table->unsignedInteger('user_id')->change());
        Schema::table('comments', fn (Blueprint $table) => $table->unsignedInteger('user_id')->change());
        Schema::table('likes', fn (Blueprint $table) => $table->unsignedInteger('user_id')->change());
        Schema::table('sessions', fn (Blueprint $table) => $table->unsignedInteger('user_id')->nullable()->change());

        Schema::table('posts', fn (Blueprint $table) => $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete());
        Schema::table('comments', fn (Blueprint $table) => $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete());
        Schema::table('likes', fn (Blueprint $table) => $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete());
    }

    public function down(): void
    {
        Schema::table('posts', fn (Blueprint $table) => $table->dropForeign(['user_id']));
        Schema::table('comments', fn (Blueprint $table) => $table->dropForeign(['user_id']));
        Schema::table('likes', fn (Blueprint $table) => $table->dropForeign(['user_id']));

        Schema::table('posts', fn (Blueprint $table) => $table->unsignedBigInteger('user_id')->change());
        Schema::table('comments', fn (Blueprint $table) => $table->unsignedBigInteger('user_id')->change());
        Schema::table('likes', fn (Blueprint $table) => $table->unsignedBigInteger('user_id')->change());
        Schema::table('sessions', fn (Blueprint $table) => $table->unsignedBigInteger('user_id')->nullable()->change());

        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('id')->autoIncrement()->change();
            $table->string('first_name', 255)->change();
            $table->string('last_name', 255)->change();
            $table->timestamp('created_at')->nullable()->change();
            $table->timestamp('updated_at')->nullable()->change();
        });

        Schema::table('posts', fn (Blueprint $table) => $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete());
        Schema::table('comments', fn (Blueprint $table) => $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete());
        Schema::table('likes', fn (Blueprint $table) => $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete());
    }
};
