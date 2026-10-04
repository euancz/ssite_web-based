<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds a unique optional Microsoft identity link without replacing existing user accounts.
 */
return new class extends Migration
{
    /**
     * Add the optional unique key used to reconnect a Microsoft identity.
     */
    public function up(): void
    {
        // Microsoft IDs are nullable so existing local accounts remain usable until linked.
        Schema::table('users', function (Blueprint $table) {
            $table->string('microsoft_id')
                ->nullable()
                ->unique()
                ->after('email');
        });
    }

    /**
     * Remove the identity-link column while keeping all local user records.
     */
    public function down(): void
    {
        // Rolling back removes only the Microsoft identity link, not the user records.
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('microsoft_id');
        });
    }
};