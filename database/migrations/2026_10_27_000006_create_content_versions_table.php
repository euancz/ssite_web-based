<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_versions', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('post_type', 32);
            $table->unsignedInteger('post_id');
            $table->unsignedInteger('editor_user_id')->nullable();
            $table->string('editor_name')->nullable();
            $table->string('kind', 20);
            $table->json('snapshot');
            $table->timestamp('created_at');
            $table->index(['post_type', 'post_id', 'id'], 'content_versions_post_index');
        });
    }

    // Rollback deletes recorded versions; do not run it against application data without approval.
    public function down(): void
    {
        Schema::dropIfExists('content_versions');
    }
};
