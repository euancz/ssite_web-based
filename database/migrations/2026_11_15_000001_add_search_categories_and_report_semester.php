<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('activities') && ! Schema::hasColumn('activities', 'category')) {
            Schema::table('activities', fn (Blueprint $table) => $table->string('category', 100)->nullable());
        }
        if (Schema::hasTable('liquidations') && ! Schema::hasColumn('liquidations', 'semester')) {
            Schema::table('liquidations', fn (Blueprint $table) => $table->unsignedTinyInteger('semester')->nullable());
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('activities', 'category')) {
            Schema::table('activities', fn (Blueprint $table) => $table->dropColumn('category'));
        }
        if (Schema::hasColumn('liquidations', 'semester')) {
            Schema::table('liquidations', fn (Blueprint $table) => $table->dropColumn('semester'));
        }
    }
};
