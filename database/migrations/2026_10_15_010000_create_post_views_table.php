<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Track one first view per signed-in user and post type. */
    public function up(): void
    {
        Schema::create('post_views', function (Blueprint $table): void {
            $table->increments('view_id');
            $table->string('post_type', 32);
            $table->unsignedInteger('post_id');
            $table->unsignedInteger('user_id');
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['post_type', 'post_id', 'user_id'], 'uq_post_views_user_post');
            $table->index(['post_type', 'post_id'], 'idx_post_views_post');
            $table->foreign('user_id')->references('user_id')->on('users')
                ->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /** Remove per-user view tracking while leaving the aggregate counters intact. */
    public function down(): void
    {
        Schema::dropIfExists('post_views');
    }
};
