<?php
// auth/auth_guard.php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* ==== helpers de sesión ==== */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user_id(): ?int {
    return isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;
}

function current_username(): ?string {
    return $_SESSION['username'] ?? null;
}

function current_role(): ?string {
    if (empty($_SESSION['rol'])) return null;
    return strtolower(trim((string)$_SESSION['rol']));
}

/* ==== guards ==== */
function require_login(): void {
    if (!is_logged_in()) {
        header('Location: /auth/login.php');
        exit;
    }
}

function require_role(array $roles): void {
    if (!is_logged_in()) {
        header('Location: /auth/login.php');
        exit;
    }
    $rolActual = current_role() ?? '';
    if (!in_array($rolActual, $roles, true)) {
        http_response_code(403);
        echo "Acceso no autorizado";
        exit;
    }
}

/* ==== CSRF ==== */
if (!function_exists('ensure_csrf_token')) {
    function ensure_csrf_token(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verify_csrf_or_fail')) {
    function verify_csrf_or_fail(?string $token): void {
        if (
            empty($_SESSION['csrf_token']) ||
            !$token ||
            !hash_equals($_SESSION['csrf_token'], $token)
        ) {
            http_response_code(400);
            echo "Token CSRF inválido";
            exit;
        }
    }
}
