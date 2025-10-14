<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void {
    header('Location: ' . base_url($path));
    exit;
}

function post(string $key, $default = null) {
    return $_POST[$key] ?? $default;
}

function get(string $key, $default = null) {
    return $_GET[$key] ?? $default;
}

function validate_email(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function pdo_fetch_one(PDO $pdo, string $sql, array $params = []): ?array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function pdo_fetch_all(PDO $pdo, string $sql, array $params = []): array {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function require_csrf_or_fail(): void {
    if (!csrf_check(post('csrf_token'))) {
        http_response_code(400);
        echo 'Invalid CSRF token';
        exit;
    }
}
