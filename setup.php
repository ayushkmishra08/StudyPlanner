<?php

declare(strict_types=1);

/**
 * StudyPlanner - Database Setup & Migration Script
 *
 * Usage:
 *   php setup.php           (Creates database, tables, migrations, admin, and demo data)
 *   php setup.php --seed    (Re-runs data seeder)
 *   php setup.php --reset   (Drops tables and performs a clean fresh installation)
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo "<h1>Forbidden</h1><p>setup.php must be run from the command line: <code>php setup.php</code></p>";
    exit(1);
}

echo "===================================================\n";
echo "           StudyPlanner Database Setup             \n";
echo "===================================================\n\n";

if (!file_exists(__DIR__ . '/.env') && file_exists(__DIR__ . '/.env.example')) {
    copy(__DIR__ . '/.env.example', __DIR__ . '/.env');
    echo "  [+] Initialized .env configuration from .env.example\n";
}

$config = require __DIR__ . '/config/database.php';
$host = $config['host'];
$port = (int) $config['port'];
$database = $config['database'];
$username = $config['username'];
$password = $config['password'];

$reset = in_array('--reset', $argv, true);
$seedOnly = in_array('--seed', $argv, true);

echo "[1/5] Connecting to MySQL server at {$host}:{$port}...\n";

// Helper: Check if port is open
function isPortOpen(string $host, int $port, float $timeout = 1.0): bool {
    $connection = @fsockopen($host, $port, $errno, $errstr, $timeout);
    if (is_resource($connection)) {
        fclose($connection);
        return true;
    }
    return false;
}

// Attempt to start local MariaDB/MySQL if port 3306 is not open on Windows
if (!isPortOpen($host, $port)) {
    echo "  [!] Server not listening on {$host}:{$port}. Attempting to start local MariaDB...\n";
    $candidates = [
        'C:\Program Files\MariaDB 13.0\bin\mysqld.exe',
        'C:\Program Files\MariaDB 12.0\bin\mysqld.exe',
        'C:\Program Files\MariaDB 11.0\bin\mysqld.exe',
        'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqld.exe',
        'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqld.exe',
        'C:\xampp\mysql\bin\mysqld.exe',
    ];

    $started = false;
    foreach ($candidates as $exe) {
        if (file_exists($exe)) {
            echo "  [+] Found database engine at: {$exe}\n";
            pclose(popen("start \"MariaDB Server\" /B \"{$exe}\" --console", 'r'));
            $started = true;
            break;
        }
    }

    if ($started) {
        echo "  [*] Waiting for database to initialize sockets...\n";
        for ($i = 0; $i < 10; $i++) {
            usleep(500000); // 0.5 sec
            if (isPortOpen($host, $port)) {
                echo "  [✓] Database server is ready!\n";
                break;
            }
        }
    }
}

try {
    $serverPdo = new PDO(
        "mysql:host={$host};port={$port};charset=utf8mb4",
        $username,
        $password,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "  [✓] Connected to MySQL/MariaDB server successfully.\n";
} catch (PDOException $e) {
    fwrite(STDERR, "\n[ERROR] Could not connect to MySQL server:\n" . $e->getMessage() . "\n");
    fwrite(STDERR, "Please verify that MySQL or MariaDB is running on {$host}:{$port}.\n");
    exit(1);
}

// 2. Database Creation / Reset
echo "\n[2/5] Setting up database '{$database}'...\n";
if ($reset) {
    echo "  [!] Reset requested: Dropping database '{$database}'...\n";
    $serverPdo->exec("DROP DATABASE IF EXISTS `{$database}`");
}

$serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
echo "  [✓] Database '{$database}' is ready.\n";

// Connect directly to the database
$pdo = new PDO(
    "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4",
    $username,
    $password,
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
);

// 3. Apply Schema
echo "\n[3/5] Applying schema definitions from db/schema.sql...\n";
$schemaFile = __DIR__ . '/db/schema.sql';
if (file_exists($schemaFile)) {
    $sql = file_get_contents($schemaFile);
    $pdo->exec($sql);
    echo "  [✓] Base schema applied successfully.\n";
} else {
    echo "  [!] Schema file db/schema.sql not found!\n";
}

// 4. Run Migrations safely
echo "\n[4/5] Checking and running schema migrations in db/migrations/...\n";
$migrationsDir = __DIR__ . '/db/migrations';
if (is_dir($migrationsDir)) {
    $files = glob($migrationsDir . '/*.sql');
    sort($files);
    foreach ($files as $migrationFile) {
        $filename = basename($migrationFile);
        try {
            $migrationSql = file_get_contents($migrationFile);
            $pdo->exec($migrationSql);
            echo "  [✓] Applied migration: {$filename}\n";
        } catch (PDOException $e) {
            // Ignore errors if columns or tables already exist
            if (str_contains($e->getMessage(), 'Duplicate') || str_contains($e->getMessage(), 'already exists')) {
                echo "  [.] Already up to date: {$filename}\n";
            } else {
                echo "  [i] Migration {$filename} notice: " . $e->getMessage() . "\n";
            }
        }
    }
}

// 5. Seed Database & Accounts
echo "\n[5/5] Checking accounts and demo seed data...\n";
require_once __DIR__ . '/db/seed.php';
App\Database\seedDatabase();

// Display summary
echo "\n===================================================\n";
echo "        🎉 StudyPlanner Setup Complete!            \n";
echo "===================================================\n";
echo "Local Server URL:   http://127.0.0.1:8000/\n\n";
echo "Administrator Account:\n";
echo "  - Email:    admin@studyplanner.test\n";
echo "  - Password: AdminPassword123!\n\n";
echo "Student Account:\n";
echo "  - Email:    user@studyplanner.test\n";
echo "  - Password: Student123!\n\n";
echo "To start the application, run:\n";
echo "  .\\start.bat\n";
echo "  or: php -S 127.0.0.1:8000 index.php\n";
echo "===================================================\n";
