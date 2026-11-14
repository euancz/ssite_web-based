<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mobile_publications', function (Blueprint $table): void {
            $table->string('module', 24);
            $table->unsignedBigInteger('record_id');
            $table->json('payload');
            $table->string('title')->default('');
            $table->text('search_text');
            $table->string('category')->nullable();
            $table->string('semester')->nullable();
            $table->string('academic_year')->nullable();
            $table->timestamp('sort_date')->nullable();
            $table->timestamp('featured_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamp('updated_at');
            $table->primary(['module', 'record_id']);
            $table->index(['module', 'sort_date']);
        });

        if (DB::getDriverName() === 'pgsql') {
            $sql = file_get_contents(database_path('sql/mobile-read-access.sql'));
            $quote = static fn (string $value): string => str_replace("'", "''", $value);
            DB::unprepared(str_replace(
                ['__EMAIL_DOMAIN__', '__MOBILE_BUCKET__'],
                [$quote(config('school.microsoft_email_domain') ?: 'mcc.edu.ph'),
                    $quote(config('filesystems.disks.mobile.bucket'))],
                $sql,
            ));
        }
    }

    public function down(): void
    {
        // App data and access policies must be removed together, explicitly.
        throw new LogicException('Keep mobile publications protected; do not roll back this migration on a shared backend.');
    }
};
