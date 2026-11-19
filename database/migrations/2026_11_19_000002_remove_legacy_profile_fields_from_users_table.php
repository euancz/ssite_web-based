<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const FIELDS = [
        'student_number' => 20,
        'institute' => 150,
        'program' => 100,
        'year_level' => 30,
        'gender' => 20,
        'contact_number' => 20,
        'address' => 255,
    ];

    public function up(): void
    {
        $columns = array_values(array_intersect(array_keys(self::FIELDS), Schema::getColumnListing('users')));
        if ($columns === []) {
            return;
        }

        // SQLite requires the former student-number index to be dropped before the column.
        if (DB::connection()->getDriverName() === 'sqlite' && Schema::hasIndex('users', 'users_student_number_unique')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropUnique('users_student_number_unique');
            });
        }

        Schema::table('users', function (Blueprint $table) use ($columns): void {
            $table->dropColumn($columns);
        });
    }

    public function down(): void
    {
        // Rollback restores the columns as empty fields. Deleted values require a backup.
        Schema::table('users', function (Blueprint $table): void {
            foreach (self::FIELDS as $column => $length) {
                $table->string($column, $length)->nullable();
            }
            $table->unique('student_number');
        });
    }
};
