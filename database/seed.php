<?php

declare(strict_types=1);

use App\Core\Database;

require_once __DIR__ . '/../bootstrap/app.php';

$pdo = Database::getConnection();

$pdo->exec("
    CREATE TABLE IF NOT EXISTS seeders (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        seeder VARCHAR(255) NOT NULL UNIQUE,
        executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

$stmt = $pdo->query("SELECT seeder FROM seeders");
$executedSeeders = $stmt->fetchAll(PDO::FETCH_COLUMN);

$seederFiles = glob(__DIR__ . '/seeders/*.php');

sort($seederFiles);

foreach ($seederFiles as $seederFile) {
    $seederName = basename($seederFile);

    if (in_array($seederName, $executedSeeders)) {
        echo "Skipped: {$seederName}" . PHP_EOL;
        continue;
    }

    try {
        $pdo->beginTransaction();

        $seeder = require $seederFile;

        if (!is_callable($seeder)) {
            throw new Exception("Seeder {$seederName} must return a callable function.");
        }

        $seeder($pdo);

        $stmt = $pdo->prepare("INSERT INTO seeders (seeder) VALUES (?)");
        $stmt->execute([$seederName]);

        $pdo->commit();

        echo "Executed: {$seederName}" . PHP_EOL;
    } catch (Throwable $exception) {
        $pdo->rollBack();

        echo "Seeder failed: {$seederName}" . PHP_EOL;
        echo $exception->getMessage() . PHP_EOL;

        exit;
    }
}

echo "All seeders completed." . PHP_EOL;