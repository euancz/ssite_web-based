<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->string('category', 100)->nullable();
        });
    }

    // Removes category data; do not roll back application data without separate approval.
    public function down(): void
    {
        Schema::table('articles', fn (Blueprint $table) => $table->dropColumn('category'));
    }
};
