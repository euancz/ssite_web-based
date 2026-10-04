<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Makes Microsoft-created accounts completable while retaining existing user data.
 */
return new class extends Migration
{
    /**
     * Add profile completion support while preserving existing user rows.
     */
    public function up(): void
    {
        // Stop before making changes when this migration is pointed at the wrong database.
        if (! Schema::hasTable('users')) {
            throw new RuntimeException('The users table must exist before profile completion can be enabled.');
        }

        // Avoid a partially applied unique index when existing student numbers collide.
        if (Schema::hasColumn('users', 'student_number')) {
            $hasDuplicates = DB::table('users')
                ->whereNotNull('student_number')
                ->select('student_number')
                ->groupBy('student_number')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            if ($hasDuplicates) {
                throw new RuntimeException(
                    'Duplicate student numbers must be resolved before the unique student number index can be added.'
                );
            }
        }

        // Preserve existing IDs while adapting Laravel's default key name to this app's user_id key.
        if (! Schema::hasColumn('users', 'user_id') && Schema::hasColumn('users', 'id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->renameColumn('id', 'user_id');
            });
        }

        // Nullable profile columns let new Microsoft accounts exist before they submit the form.
        $nullableColumns = [
            'student_number' => 20,
            'year_level' => 30,
            'program' => 100,
            'institute' => 150,
            'gender' => 20,
            'contact_number' => 20,
            'address' => 255,
            'password' => 255,
            'profile_picture' => 255,
        ];

        foreach ($nullableColumns as $column => $length) {
            if (! Schema::hasColumn('users', $column)) {
                Schema::table('users', function (Blueprint $table) use ($column, $length): void {
                    $table->string($column, $length)->nullable();
                });

                continue;
            }

            Schema::table('users', function (Blueprint $table) use ($column, $length): void {
                $table->string($column, $length)->nullable()->change();
            });
        }

        // Guard missing role columns and use student as the safe default for new rows.
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('role', 20)->default('student');
            });
        } else {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('role', 20)->default('student')->change();
            });
        }

        // Timestamp guards allow this migration to work with the manually managed users schema.
        if (! Schema::hasColumn('users', 'created_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('created_at')->nullable();
            });
        }

        if (! Schema::hasColumn('users', 'profile_completed_at')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->timestamp('profile_completed_at')->nullable();
            });
        }

        // Keep an existing unique constraint and add one only when the schema lacks it.
        $indexes = Schema::getIndexes('users');
        $hasStudentNumberUniqueIndex = collect($indexes)->contains(
            fn (array $index): bool => ($index['unique'] ?? false)
                && ($index['columns'] ?? []) === ['student_number']
        );

        if (! $hasStudentNumberUniqueIndex) {
            Schema::table('users', function (Blueprint $table): void {
                $table->unique('student_number', 'users_student_number_unique');
            });
        }

        // Existing accounts use updated_at as the best available joined-date backfill.
        if (Schema::hasColumn('users', 'updated_at')) {
            DB::table('users')
                ->whereNull('created_at')
                ->whereNotNull('updated_at')
                ->update(['created_at' => DB::raw('updated_at')]);
        }

        // Mark only real, populated profiles complete; blank values and legacy placeholders stay gated.
        $completeProfile = DB::table('users')->whereNull('profile_completed_at');

        foreach (['student_number', 'name', 'institute', 'program', 'year_level', 'gender', 'contact_number', 'address'] as $column) {
            $completeProfile->whereRaw("TRIM(COALESCE({$column}, '')) <> ''");
        }

        // Earlier Microsoft signups used these literal placeholders instead of real profile data.
        foreach (['institute', 'program', 'year_level', 'gender', 'contact_number', 'address'] as $column) {
            $completeProfile->whereRaw("LOWER(TRIM(COALESCE({$column}, ''))) <> 'not set'");
        }
        $completeProfile->whereRaw("UPPER(TRIM(COALESCE(student_number, ''))) NOT LIKE 'PENDING-%'");

        $completeProfile->update(['profile_completed_at' => now()]);
    }

    /**
     * Keep this migration irreversible to avoid losing user profile data.
     */
    public function down(): void
    {
        // Intentionally irreversible: rolling back would discard profile completion data or
        // make existing null profile fields invalid again.
    }
};
