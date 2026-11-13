<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['activities', 'achievements'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->string('content_format', 16)->default('plain');
            });
        }
    }

    public function down(): void
    {
        foreach (['activities', 'achievements'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table): void {
                $table->dropColumn('content_format');
            });
        }
    }
};
