<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('site_sections', function (Blueprint $table) {
            $table->string('section')->primary();
            $table->json('content');
            $table->unsignedInteger('revision');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE site_sections ENABLE ROW LEVEL SECURITY');
            DB::statement('REVOKE ALL ON TABLE site_sections FROM PUBLIC');
            foreach (['anon', 'authenticated'] as $role) {
                if (DB::selectOne('SELECT 1 FROM pg_roles WHERE rolname = ?', [$role])) {
                    DB::statement("REVOKE ALL ON TABLE site_sections FROM {$role}");
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('site_sections');
    }
};
