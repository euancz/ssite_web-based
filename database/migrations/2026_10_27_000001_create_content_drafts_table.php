<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Add private draft storage without changing existing content or review states. */
    public function up(): void
    {
        // Deliberately fail on an existing table rather than silently adopting an unknown schema.
        Schema::create('content_drafts', function (Blueprint $table): void {
            $table->engine = 'InnoDB';
            $table->uuid('id')->primary();
            // The users primary key remains INT UNSIGNED; retain the matching foreign key type.
            $table->unsignedInteger('created_by_user_id');
            $table->string('module', 24);
            $table->json('payload');
            $table->unsignedInteger('lock_version')->default(1);
            $table->timestamps();

            $table->foreign('created_by_user_id')->references(Schema::hasColumn('users', 'id') ? 'id' : 'user_id')->on('users')->restrictOnDelete();
            $table->index(['created_by_user_id', 'module', 'updated_at'], 'content_drafts_owner_module_updated_index');
        });
    }

    /** Run only after explicitly approving deletion or backing up all saved drafts. */
    public function down(): void
    {
        Schema::dropIfExists('content_drafts');
    }
};
