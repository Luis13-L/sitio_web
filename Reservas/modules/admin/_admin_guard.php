<?php
// modules/admin/_admin_guard.php

// Reutilizamos el guard general del proyecto
require_once __DIR__ . '/../../auth/auth_guard.php';

// Obligar a que esté logueado…
require_login();

// …y que el rol sea administrador
require_role(['admin']);
