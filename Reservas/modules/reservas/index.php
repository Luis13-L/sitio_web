<?php
// modules/reservas/index.php
declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth_guard.php';
require_once __DIR__ . '/../../config/db.php';

require_login();

$pdo       = db();
$rol       = strtolower((string)(current_role() ?? 'user'));
$userId    = current_user_id();
$username  = current_username() ?? 'Usuario';

// ==== Filtros (GET) ====
$tipo      = $_GET['tipo']       ?? '';  // vehiculo | salon | ''
$estado    = $_GET['estado']     ?? '';  // pendiente | confirmada | cancelada | ''
$desde     = $_GET['desde']      ?? '';
$hasta     = $_GET['hasta']      ?? '';
$q         = trim($_GET['q']     ?? '');
$recursoId = (int)($_GET['recurso_id'] ?? 0);

// ==== Cargar recursos para filtro ====
$stmtRec = $pdo->query("SELECT id, nombre, tipo FROM recursos WHERE activo = 1 ORDER BY tipo, nombre");
$recursos = $stmtRec->fetchAll(PDO::FETCH_ASSOC);

// ==== Construir consulta de reservas ====
$sql = "
  SELECT
    r.id,
    r.titulo,
    r.solicitante,
    r.inicio,
    r.fin,
    r.estado,
    rec.nombre AS recurso_nombre,
    rec.tipo   AS recurso_tipo,
    a.archivo_pdf
  FROM reservas r
  JOIN recursos rec ON rec.id = r.recurso_id
  LEFT JOIN adjuntos a ON a.reserva_id = r.id
  WHERE 1=1
";

$params = [];

// Si NO es admin, solo ve sus propias reservas
if ($rol !== 'admin' && $userId !== null) {
    $sql .= " AND r.creado_por = :creado_por";
    $params[':creado_por'] = $userId;
}

// Filtro tipo recurso
if (in_array($tipo, ['vehiculo', 'salon'], true)) {
    $sql .= " AND rec.tipo = :tipo";
    $params[':tipo'] = $tipo;
}

// Filtro estado
$estadosValidos = ['pendiente', 'confirmada', 'cancelada'];
if ($estado !== '' && in_array($estado, $estadosValidos, true)) {
    $sql .= " AND r.estado = :estado";
    $params[':estado'] = $estado;
}

// Filtro recurso específico
if ($recursoId > 0) {
    $sql .= " AND r.recurso_id = :recurso_id";
    $params[':recurso_id'] = $recursoId;
}

// Filtro rango de fechas (por inicio)
if ($desde !== '') {
    $sql .= " AND r.inicio >= :desde";
    $params[':desde'] = $desde . ' 00:00:00';
}
if ($hasta !== '') {
    $sql .= " AND r.inicio <= :hasta";
    $params[':hasta'] = $hasta . ' 23:59:59';
}

// Búsqueda por texto
if ($q !== '') {
    $sql .= " AND (
        r.titulo      LIKE :q OR
        r.solicitante LIKE :q OR
        rec.nombre    LIKE :q
    )";
    $params[':q'] = '%' . $q . '%';
}

$sql .= " ORDER BY r.inicio DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Listado de reservas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body{ background:#0f2b3a; }
    .shell{ background:#f6f9fc; min-height:100vh; border-radius:12px; }
    .table-sm td, .table-sm th { padding: .35rem .5rem; }
  </style>
</head>
<body class="py-3">
<div class="container shell p-0">
  <!-- NAV -->
  <nav class="navbar navbar-expand-lg bg-white border-bottom rounded-top-3 px-3">
    <a class="navbar-brand d-flex align-items-center gap-2" href="../../index.php">
      <img src="/assets/img/solola_emblem_32.png" alt="Logo" width="28" height="28">
      <span class="fw-semibold">Dirección Departamental · Reservas</span>
    </a>
    <div class="ms-auto d-flex align-items-center gap-3">
      <a class="btn btn-outline-secondary btn-sm" href="../../index.php">
        <i class="bi bi-arrow-left me-1"></i> Volver al panel
      </a>
      <a class="btn btn-primary btn-sm" href="nueva.php">
        <i class="bi bi-plus-lg me-1"></i> Nueva reserva
      </a>
      <div class="dropdown">
        <button class="btn btn-light border dropdown-toggle" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($username) ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li class="dropdown-item-text small text-muted">
            Rol: <b><?= htmlspecialchars($rol) ?></b>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="../../auth/logout.php">Cerrar sesión</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="p-4">
    <h4 class="mb-3">
      <i class="bi bi-list-ul me-2"></i>Listado de reservas
    </h4>

    <!-- Filtros -->
    <form class="card mb-3" method="get">
      <div class="card-body">
        <div class="row g-3 align-items-end">
          <div class="col-12 col-md-3">
            <label class="form-label" for="tipo">Tipo de recurso</label>
            <select class="form-select" name="tipo" id="tipo">
              <option value="">Todos</option>
              <option value="vehiculo" <?= $tipo === 'vehiculo' ? 'selected' : '' ?>>Vehículos</option>
              <option value="salon"    <?= $tipo === 'salon'    ? 'selected' : '' ?>>Salones</option>
            </select>
          </div>

          <div class="col-12 col-md-3">
            <label class="form-label" for="recurso_id">Recurso</label>
            <select class="form-select" name="recurso_id" id="recurso_id">
              <option value="0">Todos</option>
              <?php foreach ($recursos as $r): ?>
                <?php
                  $labelTipo = $r['tipo'] === 'vehiculo' ? 'Vehículo' : 'Salón';
                  $texto     = sprintf('[%s] %s', $labelTipo, $r['nombre']);
                ?>
                <option value="<?= (int)$r['id'] ?>" <?= $recursoId === (int)$r['id'] ? 'selected' : '' ?>>
                  <?= htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-6 col-md-2">
            <label class="form-label" for="desde">Desde</label>
            <input type="date" class="form-control" name="desde" id="desde"
                   value="<?= htmlspecialchars($desde) ?>">
          </div>

          <div class="col-6 col-md-2">
            <label class="form-label" for="hasta">Hasta</label>
            <input type="date" class="form-control" name="hasta" id="hasta"
                   value="<?= htmlspecialchars($hasta) ?>">
          </div>

          <div class="col-12 col-md-2">
            <label class="form-label" for="estado">Estado</label>
            <select class="form-select" name="estado" id="estado">
              <option value="">Todos</option>
              <option value="pendiente"  <?= $estado === 'pendiente'  ? 'selected' : '' ?>>Pendiente</option>
              <option value="confirmada" <?= $estado === 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
              <option value="cancelada"  <?= $estado === 'cancelada'  ? 'selected' : '' ?>>Cancelada</option>
            </select>
          </div>

          <div class="col-12 col-md-4">
            <label class="form-label" for="q">Buscar</label>
            <input type="text" class="form-control" name="q" id="q"
                   placeholder="Título, solicitante o recurso..."
                   value="<?= htmlspecialchars($q, ENT_QUOTES, 'UTF-8') ?>">
          </div>

          <div class="col-12 col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-primary w-100 mt-2 mt-md-4">
              <i class="bi bi-search me-1"></i> Filtrar
            </button>
            <a href="index.php" class="btn btn-outline-secondary w-100 mt-2 mt-md-4">
              Limpiar
            </a>
          </div>
        </div>
      </div>
    </form>

    <!-- Tabla de reservas -->
    <div class="card">
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-striped table-hover table-sm mb-0 align-middle">
            <thead class="table-light">
              <tr>
                <th>Fecha</th>
                <th>Horario</th>
                <th>Recurso</th>
                <th>Solicitante</th>
                <th>Título</th>
                <th class="text-center">Estado</th>
                <th class="text-center">PDF</th>
                <th class="text-end">Acciones</th>
              </tr>
            </thead>
            <tbody>
            <?php if (empty($reservas)): ?>
              <tr>
                <td colspan="8" class="text-center text-muted py-4">
                  No se encontraron reservas con los criterios seleccionados.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($reservas as $r): ?>
                <?php
                  $dtInicio = new DateTime($r['inicio']);
                  $dtFin    = new DateTime($r['fin']);
                  $fechaFmt = $dtInicio->format('d/m/Y');
                  $horaIni  = $dtInicio->format('H:i');
                  $horaFin  = $dtFin->format('H:i');

                  $tipoLabel = $r['recurso_tipo'] === 'vehiculo' ? 'Vehículo' : 'Salón';

                  // Badge de estado
                  $estadoBadge = '';
                  $estadoText  = ucfirst($r['estado']);
                  switch ($r['estado']) {
                      case 'confirmada':
                          $estadoBadge = '<span class="badge bg-success-subtle text-success border border-success-subtle">' . $estadoText . '</span>';
                          break;
                      case 'cancelada':
                          $estadoBadge = '<span class="badge bg-danger-subtle text-danger border border-danger-subtle">' . $estadoText . '</span>';
                          break;
                      default: // pendiente
                          $estadoBadge = '<span class="badge bg-warning-subtle text-warning border border-warning-subtle">' . $estadoText . '</span>';
                          break;
                  }
                ?>
                <tr>
                  <td><?= htmlspecialchars($fechaFmt) ?></td>
                  <td><?= htmlspecialchars($horaIni . ' - ' . $horaFin) ?></td>
                  <td>
                    <div class="fw-semibold"><?= htmlspecialchars($r['recurso_nombre']) ?></div>
                    <div class="small text-muted"><?= $tipoLabel ?></div>
                  </td>
                  <td><?= htmlspecialchars($r['solicitante']) ?></td>
                  <td><?= htmlspecialchars($r['titulo']) ?></td>
                  <td class="text-center"><?= $estadoBadge ?></td>
                  <td class="text-center">
                    <?php if (!empty($r['archivo_pdf'])): ?>
                      <a href="../../<?= htmlspecialchars($r['archivo_pdf'], ENT_QUOTES, 'UTF-8') ?>"
                         target="_blank"
                         class="text-decoration-none"
                         title="Ver PDF">
                        <i class="bi bi-file-earmark-pdf-fill"></i>
                      </a>
                    <?php else: ?>
                      <span class="text-muted">—</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end">
                    <a href="nueva.php?id=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-primary">
                      <i class="bi bi-pencil-square me-1"></i> Ver / Editar
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
