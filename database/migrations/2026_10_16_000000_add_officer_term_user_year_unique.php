<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Add the unique roster key when an externally created table does not already have it. */
    public function up(): void
    {
        if (! Schema::hasTable('officer_terms')) {
            return;
        }

        $hasUnique = collect(Schema::getIndexes('officer_terms'))->contains(
            fn (array $index): bool => ($index['unique'] ?? false)
                && ($index['columns'] ?? []) === ['user_id', 'academic_year']
        );
        if ($hasUnique) {
            return;
        }

        // SECURITY: Stop before the DDL if duplicate linked-user/year pairs would violate the key.
        $hasDuplicates = DB::table('officer_terms')->whereNotNull('user_id')
            ->select('user_id', 'academic_year')->groupBy('user_id', 'academic_year')
            ->havingRaw('COUNT(*) > 1')->exists();
        if ($hasDuplicates) {
            throw new RuntimeException('Duplicate officer user/year rows must be reviewed before adding the unique key.');
        }

        Schema::table('officer_terms', function (Blueprint $table): void {
            $table->unique(['user_id', 'academic_year'], 'uq_terms_user_year');
        });
    }

    /** Keep existing roster uniqueness intact when this additive migration is rolled back. */
    public function down(): void
    {
        // Intentionally irreversible: dropping an owner-managed uniqueness rule could permit duplicate roster rows.
    }
};
