<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TransferSupabaseData extends Command
{
    protected $signature = 'supabase:transfer-data {action : export or import} {--file= : Local JSONL export path}';
    protected $description = 'Export app records before cutover or import them into an empty PostgreSQL database';

    private const TABLES = ['users', 'articles', 'activities', 'achievements', 'documents', 'liquidations',
        'officer_terms', 'role_changes', 'notifications', 'post_likes', 'post_views',
        'content_drafts', 'content_review_events', 'content_versions'];
    private const OLD_KEYS = ['users' => 'user_id', 'articles' => 'article_id', 'activities' => 'activity_id',
        'achievements' => 'achievement_id', 'documents' => 'document_id', 'liquidations' => 'liquidation_id',
        'officer_terms' => 'term_id', 'role_changes' => 'role_change_id', 'post_likes' => 'like_id',
        'post_views' => 'view_id', 'content_drafts' => 'draft_id'];
    private const LEGACY_USER_FIELDS = ['student_number', 'institute', 'program', 'year_level', 'gender', 'contact_number', 'address'];

    public function handle(): int
    {
        $action = $this->argument('action');
        $path = $this->option('file') ?: base_path('.tmp/supabase-data.jsonl');
        if ($action === 'export') {
            if (! is_dir(dirname($path))) mkdir(dirname($path), 0700, true);
            // Exclusive creation prevents accidentally overwriting an earlier backup.
            $handle = @fopen($path, 'x');
            if (! $handle) {
                $this->error('Cannot create export. Choose a new --file path.');
                return self::FAILURE;
            }
            try {
                $this->writeLine($handle, ['format' => 'ssite-data-v1']);
                DB::transaction(function () use ($handle): void {
                    foreach (self::TABLES as $table) {
                        if (! Schema::hasTable($table)) continue;
                        $count = 0;
                        foreach (DB::table($table)->orderBy(Schema::hasColumn($table, 'id') ? 'id' : (self::OLD_KEYS[$table] ?? 'id'))->cursor() as $row) {
                            $record = (array) $row;
                            $oldKey = self::OLD_KEYS[$table] ?? null;
                            if ($oldKey && array_key_exists($oldKey, $record) && ! array_key_exists('id', $record)) {
                                $record['id'] = $record[$oldKey];
                                unset($record[$oldKey]);
                            }
                            $this->writeLine($handle, ['table' => $table, 'row' => $record]);
                            $count++;
                        }
                        $this->line($table.': '.$count.' exported');
                    }
                });
                $this->writeLine($handle, ['complete' => true]);
            } finally {
                fclose($handle);
            }
            $this->info('Export saved. It contains private data and password hashes; keep it outside public folders.');
            return self::SUCCESS;
        }
        if ($action !== 'import' || DB::connection()->getDriverName() !== 'pgsql') {
            $this->error('Use export on the old database, or import only with DB_CONNECTION=pgsql.');
            return self::FAILURE;
        }
        $handle = @fopen($path, 'r');
        if (! $handle) {
            $this->error('Export file could not be opened.');
            return self::FAILURE;
        }
        try {
            if (json_decode(fgets($handle) ?: '', true, flags: JSON_THROW_ON_ERROR) !== ['format' => 'ssite-data-v1']) {
                throw new \RuntimeException('Unsupported export format.');
            }
            $counts = [];
            DB::transaction(function () use ($handle, &$counts): void {
                foreach (self::TABLES as $table) {
                    if (! Schema::hasTable($table) || DB::table($table)->exists()) {
                        throw new \RuntimeException('Import requires migrated, empty application tables: '.$table);
                    }
                }
                $columns = [];
                $complete = false;
                while (($line = fgets($handle)) !== false) {
                    $entry = json_decode($line, true, flags: JSON_THROW_ON_ERROR);
                    if ($entry === ['complete' => true]) {
                        $complete = true;
                        if (trim(stream_get_contents($handle)) !== '') throw new \RuntimeException('Unexpected data after export footer.');
                        break;
                    }
                    $table = $entry['table'] ?? '';
                    $row = $entry['row'] ?? null;
                    if (! in_array($table, self::TABLES, true) || ! is_array($row) || $row === []) {
                        throw new \RuntimeException('Invalid exported record.');
                    }
                    $columns[$table] ??= Schema::getColumnListing($table);
                    if ($table === 'users') {
                        foreach (array_diff(self::LEGACY_USER_FIELDS, $columns[$table]) as $field) {
                            unset($row[$field]);
                        }
                    }
                    if (array_diff(array_keys($row), $columns[$table])) {
                        throw new \RuntimeException('Unmapped source columns in '.$table.'. Import rolled back; reconcile the schemas before retrying.');
                    }
                    DB::table($table)->insert($row);
                    $counts[$table] = ($counts[$table] ?? 0) + 1;
                }
                if (! $complete) throw new \RuntimeException('Incomplete export; import rolled back.');
                foreach (self::TABLES as $table) {
                    if (DB::table($table)->count() !== ($counts[$table] ?? 0)) {
                        throw new \RuntimeException('Row count mismatch in '.$table);
                    }
                }
                // Explicit imported IDs do not advance PostgreSQL sequences.
                foreach (self::TABLES as $table) {
                    $sequence = DB::selectOne('SELECT pg_get_serial_sequence(?, ?) AS name', [$table, 'id'])->name;
                    if ($sequence) {
                        $max = DB::table($table)->max('id');
                        DB::selectOne('SELECT setval(CAST(? AS regclass), ?, CAST(? AS boolean))', [$sequence, $max ?: 1, $max !== null ? 'true' : 'false']);
                    }
                }
            });
            foreach ($counts as $table => $count) $this->line($table.': '.$count.' imported');
            $this->info('Import complete. Original database and export were retained.');
        } finally {
            fclose($handle);
        }
        return self::SUCCESS;
    }

    private function writeLine($handle, array $record): void
    {
        $line = json_encode($record, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES)."\n";
        if (fwrite($handle, $line) !== strlen($line)) {
            throw new \RuntimeException('Export write failed; do not use the incomplete file.');
        }
    }
}
