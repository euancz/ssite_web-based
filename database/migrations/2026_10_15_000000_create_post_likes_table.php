<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Store one like per user and post type without coupling to external post tables. */
    public function up(): void
    {
        Schema::create('post_likes', function (Blueprint $table): void {
            $table->increments('like_id');
            $table->string('post_type', 32);
            $table->unsignedInteger('post_id');
            $table->unsignedInteger('user_id');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['post_type', 'post_id', 'user_id'], 'uq_post_likes_user_post');
            $table->index(['post_type', 'post_id'], 'idx_post_likes_post');
            $table->foreign('user_id')->references('user_id')->on('users')
                ->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /** Drop the like table created by this migration. */
    public function down(): void
    {
        Schema::dropIfExists('post_likes');
    }
};
