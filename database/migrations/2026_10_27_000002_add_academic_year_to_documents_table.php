<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->string('academic_year', 9)->nullable();
            $table->index('academic_year', 'documents_academic_year_index');
        });
    }

    // Dropping this column discards assigned years and requires separate approval.
    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table): void {
            $table->dropIndex('documents_academic_year_index');
            $table->dropColumn('academic_year');
        });
    }
};
