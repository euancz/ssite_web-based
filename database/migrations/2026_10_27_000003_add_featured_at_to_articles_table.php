<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->timestamp('featured_at')->nullable();
            $table->index('featured_at', 'articles_featured_at_index');
        });
    }

    // Removes editorial selections; requires separate approval on application data.
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table): void {
            $table->dropIndex('articles_featured_at_index');
            $table->dropColumn('featured_at');
        });
    }
};
