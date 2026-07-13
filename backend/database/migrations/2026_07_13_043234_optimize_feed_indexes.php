<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tighten the indexes backing the feed's cursor-paginated queries.
     *
     * cursorPaginate() orders by (created_at, id) and filters on the cursor
     * with a tuple comparison — without `id` as a trailing index column,
     * MySQL can use the index to find the starting row but still falls back
     * to a filesort/extra scan to resolve the tie-break, which gets
     * expensive once a table has more rows than fit in one page. Appending
     * `id` lets the index satisfy the full ORDER BY.
     */
    public function up(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            // Add the replacements before dropping the originals: InnoDB
            // requires user_id to stay covered by a leftmost-prefix index at
            // all times to satisfy its FK constraint, so the old index can't
            // be dropped first.
            $table->index(['visibility', 'created_at', 'id']);
            $table->index(['user_id', 'created_at', 'id']);

            $table->dropIndex(['visibility', 'created_at']);
            $table->dropIndex(['user_id', 'created_at']);
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->index(['post_id', 'parent_id', 'created_at', 'id']);
            $table->dropIndex(['post_id', 'parent_id', 'created_at']);

            // Comment::replies() queries `WHERE parent_id IN (...)` directly —
            // post_id isn't part of that filter, so the composite index above
            // (post_id first) can't be used as a leftmost prefix for it. This
            // also gives the self-referencing parent_id FK an explicit index
            // instead of relying on InnoDB's implicit one.
            $table->index(['parent_id', 'created_at', 'id']);
        });

        Schema::table('likes', function (Blueprint $table) {
            $table->dropIndex(['likeable_type', 'likeable_id']);

            // Backs LikeController::index's "who liked this" cursor pagination
            // (WHERE likeable_type = ? AND likeable_id = ? ORDER BY created_at, id).
            $table->index(['likeable_type', 'likeable_id', 'created_at', 'id']);
        });
    }

    public function down(): void
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['visibility', 'created_at']);
            $table->index(['user_id', 'created_at']);

            $table->dropIndex(['visibility', 'created_at', 'id']);
            $table->dropIndex(['user_id', 'created_at', 'id']);
        });

        Schema::table('comments', function (Blueprint $table) {
            $table->index(['post_id', 'parent_id', 'created_at']);

            $table->dropIndex(['post_id', 'parent_id', 'created_at', 'id']);
            $table->dropIndex(['parent_id', 'created_at', 'id']);
        });

        Schema::table('likes', function (Blueprint $table) {
            $table->dropIndex(['likeable_type', 'likeable_id', 'created_at', 'id']);

            $table->index(['likeable_type', 'likeable_id']);
        });
    }
};
