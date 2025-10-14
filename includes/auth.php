<?php
// Session, auth helpers, CSRF, and guards

if (session_status() !== PHP_SESSION_ACTIVE) {
    $config = require __DIR__ . '/../config/config.php';
    session_name($config['session_name'] ?? 'itp_session');
    session_start([
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'cookie_samesite' => 'Lax',
    ]);
}

require_once __DIR__ . '/../config/db.php';

function current_user(): ?array {
    if (!empty($_SESSION['user'])) {
        return $_SESSION['user'];
    }
    return null;
}

function is_logged_in(): bool {
    return current_user() !== null;
}

function is_admin(): bool {
    return is_logged_in() && ($_SESSION['user']['role'] ?? null) === 'admin';
}

function is_employee(): bool {
    return is_logged_in() && ($_SESSION['user']['role'] ?? null) === 'employee';
}

function login_user(array $user): void {
    // Never store password hash in session
    unset($user['password_hash']);
    $_SESSION['user'] = $user;
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function require_login(): void {
    if (!is_logged_in()) {
        header('Location: ' . base_url('login.php'));
        exit;
    }
}

function require_role(string $role): void {
    require_login();
    if (($_SESSION['user']['role'] ?? '') !== $role) {
        http_response_code(403);
        echo 'Forbidden';
        exit;
    }
}

function flash_set(string $type, string $message): void {
    $_SESSION['flash'][$type][] = $message;
}

function flash_get_all(): array {
    $all = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $all;
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check(?string $token): bool {
    return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}
