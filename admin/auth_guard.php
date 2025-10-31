<?php
// admin/auth_guard.php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    // p.ej.: /Proyecto/Proyecto/admin/secciones/usuarios/crear.php
    $script = $_SERVER['SCRIPT_NAME'];

    // extrae todo hasta /admin
    $needle = '/admin/';
    $pos = strpos($script, $needle);

    if ($pos !== false) {
        // /Proyecto/Proyecto/admin
        $adminBase = substr($script, 0, $pos + strlen($needle) - 1); // -1 para quitar la última barra añadida por $needle
    } else {
        // fallback: asume que estás dentro de /admin
        $adminBase = rtrim(dirname($script), '/');
    }

    // SIEMPRE /admin/login.php (sin "secciones")
    $loginUrl = $adminBase . '/login.php';

    header('Location: ' . $loginUrl, true, 302);
    exit;
}


