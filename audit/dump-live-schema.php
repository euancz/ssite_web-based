<?php
require dirname(__DIR__) . '/vendor/autoload.php';
$app = require dirname(__DIR__) . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

foreach (Illuminate\Support\Facades\DB::select('SHOW TABLES') as $row) {
    $table = array_values((array) $row)[0];
    echo "\n--- {$table} ---\n";
    $identifier = '`' . str_replace('`', '``', $table) . '`';
    foreach (Illuminate\Support\Facades\DB::select("SHOW CREATE TABLE {$identifier}") as $ddl) {
        echo array_values((array) $ddl)[1] . "\n";
    }
}
