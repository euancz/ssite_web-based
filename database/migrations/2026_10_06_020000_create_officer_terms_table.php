<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Create yearly officer snapshots while preserving rows after their linked account is removed.
     */
    public function up(): void
    {
        // SECURITY: Guard creation so deployments with a pre-existing table do not fail.
        if (Schema::hasTable('officer_terms')) {
            return;
        }

        Schema::create('officer_terms', function (Blueprint $table): void {
            $table->increments('term_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('academic_year', 9)->index();
            $table->string('name', 100);
            $table->string('position', 100);
            $table->string('photo', 255)->nullable();
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->foreign('user_id')->references('user_id')->on('users')
                ->nullOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Remove only the table created for yearly officer snapshots.
     */
    public function down(): void
    {
        Schema::dropIfExists('officer_terms');
    }
};
