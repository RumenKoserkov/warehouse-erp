<?php

declare(strict_types=1);

use App\Core\Database;

require_once __DIR__ . '/../bootstrap/app.php';

$pdo = Database::getConnection();

$pdo->exec("
    CREATE TABLE IF NOT EXISTS migrations (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$stmt = $pdo->query("SELECT migration FROM migrations");
$executedMigrations = $stmt->fetchAll(PDO::FETCH_COLUMN);

$migrationFiles = glob(__DIR__ . '/migrations/*.sql');

sort($migrationFiles);

foreach ($migrationFiles as $migrationFile) {
    $migrationName = basename($migrationFile);

    if (in_array($migrationName, $executedMigrations)) {
        echo "Skipped: {$migrationName}" . PHP_EOL;
        continue;
    }

    $sql = file_get_contents($migrationFile);

    try {
        $pdo->exec($sql);

        $stmt = $pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
        $stmt->execute([$migrationName]);

        echo "Executed: {$migrationName}" . PHP_EOL;
    } catch (Throwable $exception) {
        echo "Migration failed: {$migrationName}" . PHP_EOL;
        echo $exception->getMessage() . PHP_EOL;

        exit;
    }
}

echo "All migrations completed." . PHP_EOL;