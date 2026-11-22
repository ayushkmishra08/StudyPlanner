<?php

declare(strict_types=1);

namespace App\Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $config = require dirname(__DIR__, 2) . '/config/database.php';
        $host = (string) $config['host'];
        $port = (string) $config['port'];
        $dbName = (string) $config['database'];
        $user = (string) $config['username'];
        $pass = (string) $config['password'];

        $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $dbName);

        try {
            self::$connection = new PDO($dsn, $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (PDOException $e) {
            // Check if server refused connection (error 2002) - attempt auto-starting local MariaDB
            if (self::isConnectionRefused($e) && self::tryStartLocalMariaDB($host, (int) $port)) {
                try {
                    self::$connection = new PDO($dsn, $user, $pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                    return self::$connection;
                } catch (PDOException) {
                    // Fall through to error handler
                }
            }

            // Check if database doesn't exist yet (error 1049) - attempt auto-creation
            if (self::isUnknownDatabase($e) && self::tryAutoCreateDatabase($host, (int) $port, $dbName, $user, $pass)) {
                try {
                    self::$connection = new PDO($dsn, $user, $pass, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                    return self::$connection;
                } catch (PDOException) {
                    // Fall through to error handler
                }
            }

            // In web context, render a helpful diagnostic error page instead of raw crash
            if (PHP_SAPI !== 'cli') {
                self::renderDiagnosticError($e, $config);
                exit;
            }

            throw $e;
        }

        return self::$connection;
    }

    private static function isConnectionRefused(PDOException $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, '2002') || str_contains($message, 'refused') || str_contains($message, 'actively refused');
    }

    private static function isUnknownDatabase(PDOException $e): bool
    {
        $message = $e->getMessage();
        return str_contains($message, '1049') || str_contains($message, 'Unknown database');
    }

    private static function tryStartLocalMariaDB(string $host, int $port): bool
    {
        // Only attempt for local host connections
        if (!in_array($host, ['127.0.0.1', 'localhost', '::1'], true)) {
            return false;
        }

        $candidates = [
            'C:\Program Files\MariaDB 13.0\bin\mysqld.exe',
            'C:\Program Files\MariaDB 12.0\bin\mysqld.exe',
            'C:\Program Files\MariaDB 11.0\bin\mysqld.exe',
            'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysqld.exe',
            'C:\laragon\bin\mysql\mysql-8.0.30-winx64\bin\mysqld.exe',
            'C:\xampp\mysql\bin\mysqld.exe',
        ];

        foreach ($candidates as $exe) {
            if (file_exists($exe)) {
                @pclose(@popen("start \"MariaDB Server\" /B \"{$exe}\" --console", 'r'));
                // Wait up to 3 seconds for port to become active
                for ($i = 0; $i < 6; $i++) {
                    usleep(500000);
                    $fp = @fsockopen($host, $port, $errno, $errstr, 0.5);
                    if (is_resource($fp)) {
                        fclose($fp);
                        return true;
                    }
                }
                break;
            }
        }

        return false;
    }

    private static function tryAutoCreateDatabase(string $host, int $port, string $database, string $user, string $pass): bool
    {
        try {
            $serverPdo = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $user, $pass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            $serverPdo->exec("CREATE DATABASE IF NOT EXISTS `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Execute base schema if available
            $schemaFile = dirname(__DIR__, 2) . '/db/schema.sql';
            if (file_exists($schemaFile)) {
                $dbPdo = new PDO("mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4", $user, $pass, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                ]);
                $dbPdo->exec(file_get_contents($schemaFile));
            }

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function renderDiagnosticError(PDOException $e, array $config): void
    {
        http_response_code(500);
        $host = htmlspecialchars((string) $config['host']);
        $port = htmlspecialchars((string) $config['port']);
        $database = htmlspecialchars((string) $config['database']);
        $errorMsg = htmlspecialchars($e->getMessage());

        echo <<<HTML
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Database Connection Required - StudyPlanner</title>
    <style>
        :root {
            --bg: #0f172a;
            --surface: #1e293b;
            --border: #334155;
            --text: #f8fafc;
            --muted: #94a3b8;
            --accent: #26745c;
            --accent-hover: #1e5e4a;
            --code-bg: #090d16;
            --danger: #ef4444;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            min-height: 100vh;
            display: grid;
            place-content: center;
            padding: 1.5rem;
        }
        .card {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 1rem;
            max-width: 680px;
            width: 100%;
            padding: 2.5rem;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
        }
        .badge {
            display: inline-block;
            background: rgba(239, 68, 68, 0.15);
            color: var(--danger);
            border: 1px solid rgba(239, 68, 68, 0.3);
            font-size: 0.8rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        h1 { font-size: 1.75rem; margin-bottom: 0.75rem; font-weight: 700; }
        p { color: var(--muted); line-height: 1.6; margin-bottom: 1.25rem; }
        .steps { margin: 1.5rem 0; counter-reset: step-counter; list-style: none; }
        .steps li {
            position: relative;
            padding-left: 2.5rem;
            margin-bottom: 1rem;
            color: var(--text);
            line-height: 1.5;
        }
        .steps li::before {
            content: counter(step-counter);
            counter-increment: step-counter;
            position: absolute;
            left: 0;
            top: 0;
            width: 1.75rem;
            height: 1.75rem;
            background: var(--accent);
            color: white;
            border-radius: 50%;
            display: grid;
            place-content: center;
            font-size: 0.85rem;
            font-weight: 700;
        }
        pre {
            background: var(--code-bg);
            border: 1px solid var(--border);
            padding: 0.75rem 1rem;
            border-radius: 0.5rem;
            color: #38bdf8;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
            font-size: 0.9rem;
            margin: 0.5rem 0;
            overflow-x: auto;
        }
        .actions { margin-top: 2rem; display: flex; gap: 1rem; }
        .btn {
            display: inline-block;
            background: var(--accent);
            color: white;
            text-decoration: none;
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: background 0.2s;
        }
        .btn:hover { background: var(--accent-hover); }
        .details {
            margin-top: 1.5rem;
            padding-top: 1rem;
            border-top: 1px solid var(--border);
            font-size: 0.85rem;
            color: var(--muted);
        }
    </style>
</head>
<body>
    <div class="card">
        <span class="badge">Database Offline</span>
        <h1>Database Connection Required</h1>
        <p>StudyPlanner could not connect to MySQL/MariaDB at <strong>{$host}:{$port}</strong> (database: <em>{$database}</em>).</p>
        
        <ol class="steps">
            <li>
                <strong>Start the Database & Application with one click:</strong>
                <pre>Double-click start.bat in the project folder</pre>
            </li>
            <li>
                <strong>Or run the setup command in your terminal:</strong>
                <pre>php setup.php</pre>
            </li>
            <li>
                <strong>Or start MariaDB manually:</strong>
                <pre>& "C:\Program Files\MariaDB 13.0\bin\mysqld.exe" --console</pre>
            </li>
        </ol>

        <div class="actions">
            <button class="btn" onclick="window.location.reload()">Retry Connection</button>
        </div>

        <div class="details">
            <strong>Technical Error:</strong> {$errorMsg}
        </div>
    </div>
</body>
</html>
HTML;
    }
}
