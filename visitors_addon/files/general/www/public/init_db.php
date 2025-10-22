<?php
// files/general/www/public/init_db.php

declare(strict_types=1);

$liveDb = '/data/visitor_signin.db';
$seedDb = __DIR__ . '/visitor_signin.db';

if (!is_dir('/data')) {
    @mkdir('/data', 0775, true);
}

if (!file_exists($liveDb)) {
    if (is_file($seedDb)) {
        @copy($seedDb, $liveDb);
        @chmod($liveDb, 0664);
        error_log('[init-db] Seeded /data/visitor_signin.db from /www/public/visitor_signin.db');
    } else {
        try {
            $pdo = new PDO('sqlite:' . $liveDb, null, null, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            ]);
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

// Optional: keep legacy path working (if your app reads public/visitor_signin.db)
$publicDb = __DIR__ . '/visitor_signin.db';
if (file_exists($publicDb) && !is_link($publicDb) && is_file($publicDb)) {
    @unlink($publicDb);
}
if (file_exists($liveDb) && !is_link($publicDb)) {
    @symlink($liveDb, $publicDb);
}

if (!defined('DB_PATH')) {
    define('DB_PATH', $liveDb);
}
