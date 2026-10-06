<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Allow accounts to use initials when no profile picture is stored.
 */
return new class extends Migration
{
    /**
     * Make profile pictures optional and clear only known legacy placeholders.
     */
    public function up(): void
    {
        // Guard the custom users schema and preserve all non-placeholder picture values.
        if (! Schema::hasColumn('users', 'profile_picture')) {
            return;
        }

        DB::statement('ALTER TABLE `users` MODIFY `profile_picture` VARCHAR(255) NULL');
        DB::table('users')
            ->whereIn('profile_picture', ['', 'images/Wolf.png', 'default.png'])
            ->update(['profile_picture' => null]);
    }

    /**
     * Keep this change irreversible so cleared placeholders are never restored over user data.
     */
    public function down(): void
    {
        // Intentionally irreversible: restoring NOT NULL would reject accounts without pictures.
    }
};
