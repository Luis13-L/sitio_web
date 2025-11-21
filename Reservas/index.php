<?php
// index.php
declare(strict_types=1);

require_once __DIR__ . '/auth/auth_guard.php';

// obliga a estar logueado; si no lo está, redirige al login
require_login();

// datos de sesión ya normalizados por el guard
$rol      = current_role()   ?? 'user';    // 'admin' | 'user'
$username = current_username() ?? 'Usuario';

// si necesitas CSRF en este archivo:
$csrf = ensure_csrf_token();

//opcional: si ya manejas login, puedes forzar sesión
if (!isset($_SESSION['user_id'])) {
    header('Location: /auth/login.php');
    exit;
}


require_once __DIR__ . '../config/db.php';
$pdo = db();

$stmt = $pdo->prepare("SELECT valor FROM config_reservas WHERE clave = 'logo_path'");
$stmt->execute();
$logoRow = $stmt->fetch();
$logoPath = $logoRow ? $logoRow['valor'] : 'assets/img/solola_emblem_32.png';

?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Panel de Reservas</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body{ background:#0f2b3a; }
    .shell{ background:#f6f9fc; min-height:100vh; border-radius:12px; }
    .card-hover{ transition:transform .12s ease, box-shadow .12s ease; cursor:pointer; }
    .card-hover:hover{ transform:translateY(-2px); box-shadow:0 10px 24px rgba(16,24,40,.08); }
    .kpi{ border-radius:12px; background:#fff; border:1px solid #e7eaf0; }
  </style>
</head>
<body class="py-3">
<div class="container shell p-0">
  <!-- NAVBAR -->
  <nav class="navbar navbar-expand-lg bg-white border-bottom rounded-top-3 px-3">
    <a class="navbar-brand d-flex align-items-center gap-2" href="#">
      <img src="<?= htmlspecialchars($logoPath) ?>" alt="Logo" width="100" height="50">
      <span class="fw-semibold">Dirección Departamental · Reservas</span>
    </a>
    <div class="ms-auto d-flex align-items-center gap-3">
      <?php if ($rol === 'admin'): ?>
        <a class="btn btn-primary btn-sm" href="modules/admin/index.php">
          <i class="bi bi-gear-fill me-1"></i> Administración
        </a>
      <?php else: ?>
        <a class="btn btn-primary btn-sm" href="modules/reservas/nueva.php">
          <i class="bi bi-plus-lg me-1"></i> Nueva reserva
        </a>
      <?php endif; ?>

      <div class="dropdown">
        <button class="btn btn-light border dropdown-toggle" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle me-1"></i>
          <?= htmlspecialchars($username) ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li class="dropdown-item-text small text-muted">
            Rol: <b><?= htmlspecialchars($rol) ?></b>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item" href="auth/logout.php">Cerrar sesión</a>
            <!-- si tu index.php está en la raíz de Reservas -->
            <!-- si más adelante lo mueves al root del dominio, puedes usar /auth/logout.php -->
          </li>
        </ul>
      </div>

    </div>
  </nav>

  <!-- CONTENIDO -->
  <div class="p-4">
    <!-- Acceso rápido -->
    <div class="row g-3 mb-3">
      <div class="col-12 col-md-4">
        <div class="kpi p-3">
          <div class="d-flex align-items-center justify-content-between">
            <div>
              <div class="text-muted small">Acceso rápido</div>
            </div>
          </div>
          <div class="mt-2 d-grid gap-2">
            <a class="btn btn-primary btn-sm" href="modules/reservas/nueva.php">
              <i class="bi bi-calendar-plus me-1"></i> Crear reserva
            </a>
          <a href="modules/calendar/index.php?tipo=vehiculo" class="btn btn-outline-secondary">
            <i class="bi bi-truck me-1"></i> Calendario de Vehículos
          </a>
          <a href="modules/calendar/index.php?tipo=salon" class="btn btn-outline-secondary">
            <i class="bi bi-building me-1"></i> Calendario de Salones
          </a>
            <?php if (in_array($rol, ['admin','user'])): ?>
              <a href="../modules/reservas/index.php" class="btn btn-outline-dark">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> Reportes
              </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Tarjetas módulos -->
      <div class="col-12 col-md-8">
        <div class="row g-3">
          <div class="col-12 col-lg-6">
            <a class="text-decoration-none" href="/modules/calendar/index.php?tipo=vehiculo">
              <div class="card card-hover h-100">
                <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0"><i class="bi bi-truck me-2"></i>Reservas de Vehículos</h5>
                    <span class="badge bg-primary-subtle text-primary border">Calendario</span>
                  </div>
                  <p class="text-muted mt-2 mb-0">
                    Registra y consulta reservas de vehículos por fecha y hora. Adjunta solicitudes en PDF/JPG/PNG.
                  </p>
                </div>
              </div>
            </a>
          </div>
          <div class="col-12 col-lg-6">
            <a class="text-decoration-none" href="/modules/calendar/index.php?tipo=salon">
              <div class="card card-hover h-100">
                <div class="card-body">
                  <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0"><i class="bi bi-building me-2"></i>Reservas de Salones</h5>
                    <span class="badge bg-primary-subtle text-primary border">Calendario</span>
                  </div>
                  <p class="text-muted mt-2 mb-0">
                    Lleva el registro de salones y horarios disponibles. Descarga reportes por rango de fechas.
                  </p>
                </div>
              </div>
            </a>
          </div>

          <?php if ($rol === 'admin'): ?>
          <!-- Gestión de recursos (solo admin) -->
          <div class="col-12">
            <div class="card h-100">
              <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                  <h5 class="card-title mb-1"><i class="bi bi-gear me-2"></i>Gestión de Recursos</h5>
                  <div class="text-muted">Crea o desactiva vehículos/salones disponibles para reservar.</div>
                </div>
                <div class="d-flex gap-2">
                  <a href="/modules/recursos/index.php?tipo=vehiculo" class="btn btn-outline-secondary">
                    <i class="bi bi-truck"></i> Vehículos
                  </a>
                  <a href="/modules/recursos/index.php?tipo=salon" class="btn btn-outline-secondary">
                    <i class="bi bi-building"></i> Salones
                  </a>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Calendarios públicos (embed opcional en home) -->
    <div class="row g-3">
      <div class="col-12 col-lg-6">
        <div class="card h-100">
          <div class="card-header bg-white">
            <i class="bi bi-calendar2-week me-2"></i>Calendario rápido — Vehículos (solo lectura)
          </div>
          <div class="card-body">
            <iframe src="modules/calendar/index.php?tipo=vehiculo&embed=1"
              style="width:100%;height:420px;border:0;border-radius:8px;"></iframe>
          </div>
        </div>
      </div>
      <div class="col-12 col-lg-6">
        <div class="card h-100">
          <div class="card-header bg-white">
            <i class="bi bi-calendar2-week me-2"></i>Calendario rápido — Salones (solo lectura)
          </div>
          <div class="card-body">
            <iframe src="modules/calendar/index.php?tipo=salon&embed=1"
                style="width:100%;height:420px;border:0;border-radius:8px;"></iframe>
          </div>
        </div>
      </div>
    </div>

    <footer class="text-center text-muted small py-4">
      © <?= date('Y') ?> Dirección Departamental de Educación de Sololá • Sistema de Reservas
    </footer>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
