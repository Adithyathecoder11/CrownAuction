<?php
// includes/session.php

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 86400,
        'path'     => '/',
        'secure'   => false, // set true in production with HTTPS
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    return [
        'id'       => $_SESSION['user_id'],
        'username' => $_SESSION['username'],
        'role'     => $_SESSION['role'],
        'email'    => $_SESSION['email'],
        'name'     => $_SESSION['full_name'] ?? ''
    ];
}

function requireLogin(string $redirect = '/index.php'): void {
    if (!isLoggedIn()) {
        header("Location: " . APP_URL . "/index.php?redirect=" . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireRole(string|array $roles): void {
    requireLogin();
    $user = getCurrentUser();
    $allowedRoles = is_array($roles) ? $roles : [$roles];
    if (!in_array($user['role'], $allowedRoles)) {
        header("Location: " . APP_URL . "/index.php?error=unauthorized");
        exit;
    }
}

function requireAdmin(): void {
    if (!isset($_SESSION['admin_authenticated']) || !$_SESSION['admin_authenticated']) {
        header("Location: " . APP_URL . "/" . ADMIN_SECRET_PATH . ".php");
        exit;
    }
}

function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function loginUser(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['username']  = $user['username'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['email']     = $user['email'];
    $_SESSION['full_name'] = $user['full_name'];

    // Update last login
    require_once __DIR__ . '/../config/database.php';
    Database::query("UPDATE users SET last_login = NOW() WHERE id = ?", [$user['id']]);
}

function logoutUser(): void {
    session_unset();
    session_destroy();
}

function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function sanitize(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

function formatINR(float $amount): string {
    return '₹' . number_format($amount, 2);
}

function timeRemaining(string $endTime): array {
    $now  = new DateTime();
    $end  = new DateTime($endTime);
    $diff = $now->diff($end);

    if ($now >= $end) {
        return ['expired' => true, 'text' => 'Auction Ended'];
    }

    $hours   = $diff->h + ($diff->days * 24);
    $minutes = $diff->i;
    $seconds = $diff->s;

    return [
        'expired' => false,
        'hours'   => $hours,
        'minutes' => $minutes,
        'seconds' => $seconds,
        'total_seconds' => ($hours * 3600) + ($minutes * 60) + $seconds,
        'text'    => sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds)
    ];
}
