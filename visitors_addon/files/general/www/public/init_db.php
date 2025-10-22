<?php
/**
 * Auto-prepended before any PHP script (configured in 00-autoprepend.ini).
 * Seeds /data/visitor_signin.db from /www/public/visitor_signin.db on first run.
 * Then keeps a symlink at /www/public/visitor_signin.db -> /data/visitor_signin.db
 * so legacy paths continue to work.
 */

declare(strict_types=1);

$liveDb   = '/data/visitor_signin.db';
$seedDb   = __DIR__ . '/visitor_signin.db'; // your existing default DB in repo
$nginxUid = function_exists('posix_getuid') ? posix_getuid() : null;

# Ensure /data exists
if (!is_dir('/data')) {
    @mkdir('/data', 0775, true);
}

# If live DB missing, seed from public or create empty one
if (!file_exists($liveDb)) {
    if (file_exists($seedDb) && is_file($seedDb)) {
        @copy($seedDb, $liveDb);
        @chmod($liveDb, 0664);
        error_log('[init-db] Seeded /data/visitor_signin.db from /www/public/visitor_signin.db');
    } else {
        try {
            $pdo = new PDO('sqlite:' . $liveDb, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
            // Reasonable defaults; your app/migrations can create real schema
            $pdo->exec('PRAGMA journal_mode=WAL;');
            $pdo->exec('PRAGMA synchronous=NORMAL;');
            @chmod($liveDb, 0664);
            error_log('[init-db] Created empty SQLite DB at /data/visitor_signin.db');
        } catch (Throwable $e) {
            http_response_code(500);
            echo 'Failed to initialize SQLite DB: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8');
            exit;
        }
    }
}

# Keep legacy path working: make public path a symlink to the live DB
if (file_exists($seedDb) && !is_link($seedDb) && is_file($seedDb)) {
    @unlink($seedDb); // replace file with symlink
}
if (!is_link($seedDb)) {
    @symlink($liveDb, $seedDb);
}

# Optional: define DB_PATH constant for app code that prefers an explicit path
if (!defined('DB_PATH')) {
    define('DB_PATH', $liveDb);
}
