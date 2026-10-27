<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_review_events', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('post_type', 32);
            $table->unsignedInteger('post_id');
            $table->unsignedInteger('reviewer_user_id');
            $table->string('reviewer_name');
            $table->string('post_title', 200);
            $table->string('decision', 20);
            $table->text('reason')->nullable();
            $table->timestamp('created_at');
            // Polymorphic post and reviewer snapshots intentionally survive source record deletion.
            $table->index(['post_type', 'post_id', 'id'], 'content_review_events_post_index');
        });
    }

    // This deletes review history and requires separate explicit approval on application data.
    public function down(): void
    {
        Schema::dropIfExists('content_review_events');
    }
};
