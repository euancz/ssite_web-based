<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait BuildsContentSchema
{
    /** The legacy content tables have no baseline migrations. Never build fixtures on application data. */
    protected function buildContentSchema(): void
    {
        if (DB::connection()->getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
            throw new \LogicException('Content fixtures require an isolated SQLite in-memory database.');
        }

        // Match the MySQL notification expression used by the existing navigation composer.
        DB::connection()->getPdo()->sqliteCreateFunction('JSON_UNQUOTE', static fn ($value) => $value);

        Schema::create('users', function (Blueprint $table): void {
            $table->increments('user_id');
            foreach (['name', 'email', 'password', 'role', 'officer_position', 'student_number', 'institute', 'program', 'year_level', 'gender', 'contact_number', 'address', 'profile_picture', 'microsoft_id', 'remember_token'] as $column) {
                $table->string($column)->nullable();
            }
            $table->timestamp('profile_completed_at')->nullable();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });

        foreach (['articles' => 'article', 'activities' => 'activity', 'achievements' => 'achievement', 'documents' => 'document', 'liquidations' => 'liquidation'] as $name => $type) {
            Schema::create($name, function (Blueprint $table) use ($type): void {
                $table->increments($type.'_id');
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
                if ($type === 'document') {
                    $table->unsignedTinyInteger('semester')->nullable();
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
                $table->timestamps();
                $table->index(['approval_status', 'content_status']);
            });
        }

        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
        Schema::create('officer_terms', function (Blueprint $table): void {
            $table->increments('term_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('academic_year', 9);
            $table->string('name', 100);
            $table->string('position', 100);
            $table->string('photo')->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'academic_year']);
        });
    }
}
