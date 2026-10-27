<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->string('content_format', 16)->default('plain');
        });
    }

    // Removing format metadata changes how saved articles render; requires separate approval.
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropColumn('content_format');
        });
    }
};
