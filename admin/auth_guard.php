<?php
// admin/auth_guard.php
// Cárgalo SIEMPRE antes de imprimir HTML.

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

/* ---------- Utilidades base ---------- */

// Calcula la URL base de /admin para redirigir consistentemente al login
function admin_base_url(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '/';
    $needle = '/admin/';
    $pos = strpos($script, $needle);
    if ($pos !== false) {
        // Devuelve "/.../admin"
        return substr($script, 0, $pos + strlen($needle) - 1);
    }
    // Fallback: si no encuentra "/admin/", usa el dir actual
    return rtrim(dirname($script), '/');
}

// Requiere estar logueado
function require_login(): void {
    if (empty($_SESSION['user_id']) || empty($_SESSION['logueado'])) {
        header('Location: ' . admin_base_url() . '/login.php', true, 302);
        exit;
    }
}

// Requiere que el usuario tenga uno de los roles indicados
function require_role($roles): void {
    require_login();
    if (!is_array($roles)) { $roles = [$roles]; }
    $rol = $_SESSION['rol'] ?? null;
    if ($rol === null || !in_array($rol, $roles, true)) {
        http_response_code(403);
        echo "Acceso denegado. Se requiere rol: " . implode(', ', $roles) . ".";
        exit;
    }
}

/* ---------- Política de permisos por sección/acción ---------- */
/*
 * Regla:
 *  - admin: todo permitido
 *  - user:
 *      - configuraciones: sólo nombreConfiguracion IN ('logo','portada')
 *      - portafolio: CRUD completo
 *      - (otros módulos): denegado
 */
function can_access(string $section, string $action = 'view', array $opts = []): bool {
    $rol = $_SESSION['rol'] ?? 'user';
    if ($rol === 'admin') return true;

    switch ($section) {
        case 'configuraciones': {
            $allowedKeys = ['logo', 'portada'];
            // Para listados, puedes permitir ver solo esas llaves;
            // Para editar/actualizar, exige key explícita
            $key = $opts['key'] ?? null;
            if ($action === 'view-list') {
                return true; // ver listado, luego filtras en la query
            }
            if ($key === null) return false;
            return in_array($key, $allowedKeys, true);
        }
        case 'portafolio': {
            return in_array($action, ['view','create','edit','delete'], true);
        }
        default:
            return false;
    }
}

/** Aborta si NO puede acceder a la sección/acción dada */
function require_permission(string $section, string $action = 'view', array $opts = []): void {
    require_login();
    if (!can_access($section, $action, $opts)) {
        http_response_code(403);
        echo "Acceso denegado para {$section} ({$action}).";
        exit;
    }
}

/* ---------- CSRF helpers (opcional pero recomendado) ---------- */

function ensure_csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_or_die(?string $token): void {
    $ok = $token && hash_equals($_SESSION['csrf_token'] ?? '', $token);
    if (!$ok) {
        http_response_code(400);
        echo "Token CSRF inválido. Recarga la página e inténtalo de nuevo.";
        exit;
    }
}

/* ---------- Helpers extra ---------- */
function current_user_id() { return $_SESSION['user_id'] ?? null; }
function current_username() { return $_SESSION['usuario'] ?? null; }
function current_role() { return $_SESSION['rol'] ?? null; }
