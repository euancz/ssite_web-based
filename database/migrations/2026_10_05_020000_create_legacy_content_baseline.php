<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Supply formerly manual tables for a fresh PostgreSQL installation. */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'officer_position')) {
            Schema::table('users', fn (Blueprint $table) => $table->string('officer_position', 100)->nullable());
        }
        foreach (['articles' => 'article', 'activities' => 'activity', 'achievements' => 'achievement', 'documents' => 'document', 'liquidations' => 'liquidation'] as $name => $type) {
            if (Schema::hasTable($name)) continue;
            Schema::create($name, function (Blueprint $table) use ($type): void {
                $table->increments('id');
                $table->unsignedInteger('created_by_user_id')->nullable();
                $table->string('title', 200);
                $table->text($type === 'article' ? 'content' : 'description')->nullable();
                $table->string('approval_status', 20)->default('pending');
                $table->string('content_status', 20)->default('active');
                $table->string('rejection_reason')->nullable();
                $table->unsignedInteger('reviewed_by_user_id')->nullable();
                $table->timestamp('reviewed_at')->nullable();
                if (in_array($type, ['article', 'activity', 'achievement'], true)) {
                    $table->string('image')->nullable();
                }
                if (in_array($type, ['document', 'achievement'], true)) {
                    $table->string('category', 100)->nullable();
                }
                if ($type === 'activity') {
                    $table->date('activity_date')->nullable();
                    $table->string('location')->nullable();
                }
                if ($type === 'achievement') {
                    $table->date('achievement_date')->nullable();
                    $table->string('awardee')->nullable();
                }
                if ($type === 'liquidation') {
                    $table->date('report_date')->nullable();
                    $table->decimal('amount', 12, 2)->nullable();
                }
                if (in_array($type, ['document', 'liquidation'], true)) {
                    $table->string('file_path');
                    $table->string('original_name');
                    $table->unsignedInteger('file_size')->default(0);
                }
                $table->unsignedInteger('view_count')->default(0);
                $table->unsignedInteger('like_count')->default(0);
                $table->timestamps();
                $table->index(['approval_status', 'content_status']);
            });
        }

        if (! Schema::hasTable('notifications')) {
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('role_changes')) {
            Schema::create('role_changes', function (Blueprint $table): void {
                $table->increments('id');
                $table->unsignedInteger('user_id');
                $table->unsignedInteger('changed_by_user_id')->nullable();
                foreach (['old_role', 'new_role', 'old_officer_position', 'new_officer_position'] as $column) {
                    $table->string($column, 100)->nullable();
                }
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Existing installations may already own these tables. Never drop their data.
    }
};
