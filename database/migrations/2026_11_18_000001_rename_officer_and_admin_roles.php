<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->renameValues('superadmin', 'admin', 'Multimedia', 'Social Media Manager');
    }

    public function down(): void
    {
        $this->renameValues('admin', 'superadmin', 'Social Media Manager', 'Multimedia');
    }

    private function renameValues(string $oldRole, string $newRole, string $oldPosition, string $newPosition): void
    {
        $columns = [
            'users' => ['role', 'officer_position'],
            'role_changes' => ['old_role', 'new_role', 'old_officer_position', 'new_officer_position'],
            'officer_terms' => ['position'],
        ];

        DB::transaction(function () use ($columns, $oldRole, $newRole, $oldPosition, $newPosition): void {
            foreach ($columns as $table => $fields) {
                foreach ($fields as $column) {
                    if (! Schema::hasColumn($table, $column)) {
                        continue;
                    }
                    $isRole = in_array($column, ['role', 'old_role', 'new_role'], true);
                    $renames = $isRole ? [$oldRole => $newRole] : [
                        $oldPosition => $newPosition,
                        $oldPosition.' (Creative)' => $newPosition.' (Creative)',
                        $oldPosition.' (Documentation)' => $newPosition.' (Documentation)',
                    ];
                    foreach ($renames as $old => $new) {
                        DB::table($table)->whereRaw('LOWER(TRIM('.$column.')) = ?', [strtolower($old)])->update([$column => $new]);
                    }
                }
            }
        });
    }
};
