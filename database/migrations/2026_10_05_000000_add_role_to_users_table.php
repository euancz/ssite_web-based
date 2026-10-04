<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Gives existing accounts the student role unless an administrator assigns another role.
 */
return new class extends Migration
{
    /**
     * Add a default student role for existing accounts.
     */
    public function up(): void
    {
        // This migration assumes it runs once; it does not guard against a manually added role column.
        Schema::table('users', function (Blueprint $table): void {
            $table->string('role')->default('student');
        });
    }

    /**
     * Remove the role column if this migration is rolled back.
     */
    public function down(): void
    {
        // This removes only the role column and leaves the user rows intact.
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('role');
        });
    }
};
