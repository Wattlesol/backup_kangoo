<?php

declare(strict_types=1);

$basePath = dirname(__DIR__);
$envPath = $basePath . '/.env';

if (! file_exists($envPath)) {
    fwrite(STDERR, ".env not found\n");
    exit(1);
}

$env = parse_ini_file($envPath, false, INI_SCANNER_RAW) ?: [];

$required = ['LIVE_DB_HOST', 'LIVE_DB_PORT', 'LIVE_DB_DATABASE', 'LIVE_DB_USERNAME', 'LIVE_DB_PASSWORD'];
foreach ($required as $key) {
    if (! isset($env[$key]) || $env[$key] === '') {
        fwrite(STDERR, "Missing {$key}\n");
        exit(1);
    }
}

$localRequired = ['DB_HOST', 'DB_PORT', 'DB_DATABASE', 'DB_USERNAME'];
foreach ($localRequired as $key) {
    if (! isset($env[$key]) || $env[$key] === '') {
        fwrite(STDERR, "Missing {$key}\n");
        exit(1);
    }
}

function pdo(array $env, string $prefix): PDO
{
    $host = $env[$prefix . 'DB_HOST'];
    $port = $env[$prefix . 'DB_PORT'] ?? '3306';
    $database = $env[$prefix . 'DB_DATABASE'];
    $username = $env[$prefix . 'DB_USERNAME'];
    $password = $env[$prefix . 'DB_PASSWORD'] ?? '';
    $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

    return new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
}

function quoteName(string $name): string
{
    return '`' . str_replace('`', '``', $name) . '`';
}

function tableNames(PDO $pdo, string $database): array
{
    $stmt = $pdo->prepare(
        'select table_name from information_schema.tables where table_schema = ? and table_type = "BASE TABLE" order by table_name'
    );
    $stmt->execute([$database]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

function columns(PDO $pdo, string $database, string $table): array
{
    $stmt = $pdo->prepare(
        'select column_name from information_schema.columns where table_schema = ? and table_name = ? order by ordinal_position'
    );
    $stmt->execute([$database, $table]);

    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

$live = pdo($env, 'LIVE_');
$local = pdo($env, '');

$liveDatabase = $env['LIVE_DB_DATABASE'];
$localDatabase = $env['DB_DATABASE'];

$liveTables = tableNames($live, $liveDatabase);
$localTables = tableNames($local, $localDatabase);
$localTableSet = array_fill_keys($localTables, true);

echo "Live tables: " . count($liveTables) . PHP_EOL;
echo "Local tables: " . count($localTables) . PHP_EOL;

$local->exec('SET FOREIGN_KEY_CHECKS=0');

foreach ($localTables as $table) {
    $local->exec('TRUNCATE TABLE ' . quoteName($table));
}

$copiedTables = 0;
$copiedRows = 0;

foreach ($liveTables as $table) {
    if (! isset($localTableSet[$table])) {
        echo "Skipping missing local table: {$table}" . PHP_EOL;
        continue;
    }

    $liveColumns = columns($live, $liveDatabase, $table);
    $localColumns = columns($local, $localDatabase, $table);
    $localColumnSet = array_fill_keys($localColumns, true);
    $commonColumns = array_values(array_filter($liveColumns, fn ($column) => isset($localColumnSet[$column])));

    if ($commonColumns === []) {
        echo "Skipping table with no common columns: {$table}" . PHP_EOL;
        continue;
    }

    $columnSql = implode(', ', array_map('quoteName', $commonColumns));
    $select = $live->query('SELECT ' . $columnSql . ' FROM ' . quoteName($table));
    $placeholders = '(' . implode(', ', array_fill(0, count($commonColumns), '?')) . ')';
    $insertSql = 'INSERT INTO ' . quoteName($table) . ' (' . $columnSql . ') VALUES ';

    $batch = [];
    $tableRows = 0;
    while ($row = $select->fetch(PDO::FETCH_NUM)) {
        $batch[] = $row;
        if (count($batch) >= 200) {
            $valuesSql = implode(', ', array_fill(0, count($batch), $placeholders));
            $stmt = $local->prepare($insertSql . $valuesSql);
            $stmt->execute(array_merge(...$batch));
            $tableRows += count($batch);
            $batch = [];
        }
    }

    if ($batch !== []) {
        $valuesSql = implode(', ', array_fill(0, count($batch), $placeholders));
        $stmt = $local->prepare($insertSql . $valuesSql);
        $stmt->execute(array_merge(...$batch));
        $tableRows += count($batch);
    }

    $copiedTables++;
    $copiedRows += $tableRows;
    echo "Copied {$table}: {$tableRows}" . PHP_EOL;
}

$local->exec('SET FOREIGN_KEY_CHECKS=1');

echo "Copied {$copiedRows} rows across {$copiedTables} tables." . PHP_EOL;
