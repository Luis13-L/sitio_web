<?php
// auth/csrf.php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

function ensure_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_or_fail(?string $token): void {
    if (empty($_SESSION['csrf_token']) || !$token || 
        !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Token CSRF inválido.']);
        exit;
    }
}
