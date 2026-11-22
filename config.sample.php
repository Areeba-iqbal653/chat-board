<?php
// ---- Database settings ----
// Copy this file to config.php and fill in your own details.
// XAMPP defaults: host localhost, user root, empty password, database chatboard.
const DB_HOST = 'localhost';          // on hosting, use the host name your provider gives you
const DB_NAME = 'your_database_name';
const DB_USER = 'your_database_user';
const DB_PASS = 'your_database_password';

date_default_timezone_set('Asia/Karachi');

session_set_cookie_params([
    'httponly' => true,       // JavaScript cannot read the session cookie
    'samesite' => 'Lax',
]);
session_start();

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }
    return $pdo;
}

function e(string $s): string {                       // escape output for HTML
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function current_user(): ?array {
    if (empty($_SESSION['user_id'])) return null;
    return ['id' => $_SESSION['user_id'], 'username' => $_SESSION['username']];
}

function require_login(): void {
    if (!current_user()) {
        header('Location: login.php');
        exit;
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_valid(?string $token): bool {
    return $token && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $token);
}
