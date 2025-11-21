<?php
require_once __DIR__ . '/_admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
$pdo = db();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Administración · Reservas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h4 class="mb-0"><i class="bi bi-gear-fill me-2"></i>Administración del sistema de reservas</h4>
    <a href="../../index.php" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-arrow-left me-1"></i> Volver al panel
    </a>
  </div>

  <div class="row g-3">
    <div class="col-12 col-md-4">
      <a href="logo.php" class="text-decoration-none">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-image me-2"></i>Logo del sistema</h5>
            <p class="text-muted mb-0">Sube y cambia el logo que se muestra en el panel de reservas.</p>
          </div>
        </div>
      </a>
    </div>

    <div class="col-12 col-md-4">
      <a href="recursos.php" class="text-decoration-none">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-truck-front me-2"></i>Vehículos y salones</h5>
            <p class="text-muted mb-0">Agrega, edita o desactiva recursos disponibles para reservar.</p>
          </div>
        </div>
      </a>
    </div>

    <div class="col-12 col-md-4">
      <a href="usuarios.php" class="text-decoration-none">
        <div class="card h-100">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-people me-2"></i>Usuarios</h5>
            <p class="text-muted mb-0">Gestiona las cuentas de acceso (administradores y usuarios).</p>
          </div>
        </div>
      </a>
    </div>
  </div>
</div>
</body>
</html>