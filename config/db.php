<?php
// Returns a shared PDO connection

function getPDO(): PDO {
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $config = require __DIR__ . '/config.php';

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $config['db_host'], $config['db_name']);
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
    return $pdo;
}

function base_url(string $path = ''): string {
    // Prefer configured base_url when provided
    $config = require __DIR__ . '/config.php';
    $cfgBase = trim((string)($config['base_url'] ?? ''));
    $path = ltrim($path, '/');

    if ($cfgBase !== '') {
        $base = rtrim($cfgBase, '/');
        return $path ? "$base/$path" : $base;
    }

    // Auto-detect base URL from filesystem + web server context
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';

    // Project root = parent of this config directory
    $projectRootFs = realpath(dirname(__DIR__));
    $docRootFs = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');

    $relative = '';
    if ($docRootFs && $projectRootFs && strncmp($projectRootFs, $docRootFs, strlen($docRootFs)) === 0) {
        $relative = trim(str_replace(DIRECTORY_SEPARATOR, '/', substr($projectRootFs, strlen($docRootFs))), '/');
    } else {
        // Fallback: use script directory name
        $relative = trim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/');
    }

    $base = $scheme . '://' . $host . ($relative !== '' ? '/' . $relative : '');
    return $path ? "$base/$path" : $base;
}
