<?php
// modules/reservas/nueva.php
declare(strict_types=1);

require_once __DIR__ . '/../../auth/auth_guard.php';
require_once __DIR__ . '/../../config/db.php';

require_login();

$pdo      = db();
$csrf     = ensure_csrf_token();
$rol      = current_role()      ?? 'user';
$username = current_username()  ?? 'Usuario';

// ==== ¿VENIMOS A EDITAR UNA RESERVA? ====
$reservaId   = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$modoEdicion = $reservaId > 0;
$reserva     = null;

if ($modoEdicion) {
    $stmt = $pdo->prepare("
        SELECT 
            r.*,
            rec.tipo,
            rec.nombre AS recurso_nombre,
            a.archivo_pdf,
            a.nombre_original
        FROM reservas r
        JOIN recursos rec      ON rec.id = r.recurso_id
        LEFT JOIN adjuntos a   ON a.reserva_id = r.id
        WHERE r.id = :id
    ");
    $stmt->execute([':id' => $reservaId]);
    $reserva = $stmt->fetch();

    if (!$reserva) {
        http_response_code(404);
        echo "Reserva no encontrada";
        exit;
    }
}

// ==== CARGAR RECURSOS ACTIVOS ====
$stmt = $pdo->query("SELECT id, nombre, tipo FROM recursos WHERE activo = 1 ORDER BY tipo, nombre");
$recursos = $stmt->fetchAll();

// Valores por defecto para fecha / horas
if ($modoEdicion) {
    $fecha     = substr($reserva['inicio'], 0, 10);
    $horaIni   = substr($reserva['inicio'], 11, 5);
    $horaFin   = substr($reserva['fin'],    11, 5);
} else {
    $fecha   = '';
    $horaIni = '08:00';
    $horaFin = '17:00';
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title><?= $modoEdicion ? 'Editar reserva' : 'Nueva reserva' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body{ background:#0f2b3a; }
    .shell{ background:#f6f9fc; min-height:100vh; border-radius:12px; }
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
      <div class="dropdown">
        <button class="btn btn-light border dropdown-toggle" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle me-1"></i><?= htmlspecialchars($username) ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li class="dropdown-item-text small text-muted">Rol: <b><?= htmlspecialchars($rol) ?></b></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item" href="../../auth/logout.php">Cerrar sesión</a></li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="p-4">
    <h4 class="mb-3">
      <i class="bi bi-calendar-plus me-2"></i>
      <?= $modoEdicion ? 'Editar reserva' : 'Nueva reserva' ?>
    </h4>

    <div id="alertBox" class="alert d-none" role="alert"></div>

    <!-- enctype no afecta al fetch, pero es correcto semánticamente -->
    <form id="formReserva" class="card shadow-sm" enctype="multipart/form-data">
      <div class="card-body">
        <div class="row g-3">

          <!-- Modo (agregar / actualizar) + ID -->
          <input type="hidden" name="accion" id="accion" value="<?= $modoEdicion ? 'actualizar' : 'agregar' ?>">
          <input type="hidden" name="id" id="id" value="<?= $modoEdicion ? (int)$reserva['id'] : '' ?>">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">

          <div class="col-12 col-md-6">
            <label for="recurso_id" class="form-label">Recurso a reservar</label>
            <select class="form-select" name="recurso_id" id="recurso_id" required>
              <option value="">Seleccione un recurso…</option>
              <?php foreach ($recursos as $r): ?>
                <?php
                  $etiquetaTipo = ($r['tipo'] === 'vehiculo') ? 'Vehículo' : 'Salón';
                  $texto        = sprintf('[%s] %s', $etiquetaTipo, $r['nombre']);
                  $selected     = $modoEdicion && (int)$reserva['recurso_id'] === (int)$r['id'] ? 'selected' : '';
                ?>
                <option
                  value="<?= (int)$r['id'] ?>"
                  data-tipo="<?= htmlspecialchars($r['tipo'], ENT_QUOTES, 'UTF-8') ?>"
                  <?= $selected ?>
                >
                  <?= htmlspecialchars($texto, ENT_QUOTES, 'UTF-8') ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="col-12 col-md-6">
            <label for="solicitante" class="form-label">Solicitante</label>
            <input
              type="text"
              class="form-control"
              name="solicitante"
              id="solicitante"
              placeholder="Nombre de quien solicita"
              value="<?= $modoEdicion ? htmlspecialchars($reserva['solicitante']) : '' ?>"
              required
            >
          </div>

          <div class="col-12">
            <label for="titulo" class="form-label">Título / motivo</label>
            <input
              type="text"
              class="form-control"
              name="titulo"
              id="titulo"
              placeholder="Ej. Reunión con supervisores"
              value="<?= $modoEdicion ? htmlspecialchars($reserva['titulo']) : '' ?>"
              required
            >
          </div>

          <div class="col-12">
            <label for="descripcion" class="form-label">Descripción (opcional)</label>
            <textarea
              class="form-control"
              name="descripcion"
              id="descripcion"
              rows="3"
              placeholder="Detalle de la actividad, participantes, etc."
            ><?= $modoEdicion ? htmlspecialchars($reserva['descripcion']) : '' ?></textarea>
          </div>

          <!-- ADJUNTO PDF -->
          <div class="col-12 col-md-6">
            <label for="adjunto_pdf" class="form-label">Adjuntar solicitud (PDF, opcional)</label>
            <input
              type="file"
              class="form-control"
              name="adjunto_pdf"
              id="adjunto_pdf"
              accept="application/pdf"
            >
            <div class="form-text">Solo archivos PDF, máximo 5 MB.</div>
          </div>

          <?php if ($modoEdicion && !empty($reserva['archivo_pdf'])): ?>
            <div class="col-12 col-md-6 d-flex align-items-end">
              <div>
                <div class="small text-muted">Archivo actual:</div>
                <a href="../../<?= htmlspecialchars($reserva['archivo_pdf'], ENT_QUOTES, 'UTF-8') ?>" target="_blank">
                  <?= htmlspecialchars($reserva['nombre_original'] ?: 'Ver PDF actual', ENT_QUOTES, 'UTF-8') ?>
                </a>
              </div>
            </div>
          <?php endif; ?>

          <div class="col-12 col-md-4">
            <label for="fecha" class="form-label">Fecha</label>
            <input
              type="date"
              class="form-control"
              name="fecha"
              id="fecha"
              value="<?= htmlspecialchars($fecha) ?>"
              required
            >
          </div>

          <div class="col-6 col-md-4">
            <label for="hora" class="form-label">Hora de inicio</label>
            <input
              type="time"
              class="form-control"
              name="hora"
              id="hora"
              value="<?= htmlspecialchars($horaIni) ?>"
              required
            >
          </div>

          <div class="col-6 col-md-4">
            <label for="hora_fin" class="form-label">Hora de fin</label>
            <input
              type="time"
              class="form-control"
              name="hora_fin"
              id="hora_fin"
              value="<?= htmlspecialchars($horaFin) ?>"
              required
            >
          </div>

        </div>
      </div>

      <div class="card-footer d-flex justify-content-between">
        <a href="../../index.php" class="btn btn-outline-secondary">
          <i class="bi bi-x-circle me-1"></i> Cancelar
        </a>

        <div class="d-flex gap-2">
          <?php if ($modoEdicion): ?>
            <button type="button" class="btn btn-outline-danger" id="btnCancelarReserva">
              <i class="bi bi-slash-circle me-1"></i> Cancelar reserva
            </button>
          <?php endif; ?>
          <button type="submit" class="btn btn-primary" id="btnGuardar">
            <i class="bi bi-check2-circle me-1"></i>
            <?= $modoEdicion ? 'Guardar cambios' : 'Guardar reserva' ?>
          </button>
        </div>
      </div>
    </form>

    <p class="text-muted small mt-3">
      * La reserva se maneja en estado <strong>pendiente / confirmada / cancelada</strong>.
      Administración podrá confirmar o cancelar según corresponda.
    </p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const form      = document.getElementById('formReserva');
const alertBox  = document.getElementById('alertBox');
const btnCancelarReserva = document.getElementById('btnCancelarReserva');
const inputAccion = document.getElementById('accion');
const inputId     = document.getElementById('id');

function mostrarError(msg) {
  alertBox.classList.remove('d-none');
  alertBox.classList.remove('alert-success');
  alertBox.classList.add('alert-danger');
  alertBox.innerText = msg;
}

function mostrarOk(msg) {
  alertBox.classList.remove('d-none');
  alertBox.classList.remove('alert-danger');
  alertBox.classList.add('alert-success');
  alertBox.innerText = msg;
}

async function enviarReserva(accion) {
  alertBox.classList.add('d-none');
  alertBox.innerText = '';

  const fd = new FormData(form);
  fd.set('accion', accion); // usamos el valor que le pasamos

  // Validación sencilla en front (para agregar/actualizar)
  if (accion !== 'cancelar') {
    if (!fd.get('recurso_id')) {
      mostrarError('Debe seleccionar un recurso.');
      return;
    }
    if (!fd.get('fecha') || !fd.get('hora') || !fd.get('hora_fin')) {
      mostrarError('Debe completar fecha y horas de inicio/fin.');
      return;
    }
    const hIni = fd.get('hora');
    const hFin = fd.get('hora_fin');
    if (hIni >= hFin) {
      mostrarError('La hora de fin debe ser mayor que la de inicio.');
      return;
    }
  }

  try {
    const resp = await fetch('../../api/reservas_save.php', {
      method: 'POST',
      body: fd
    });

    const data = await resp.json().catch(() => null);

    if (!resp.ok || !data || data.ok === false) {
      const msg = data && data.error ? data.error : 'Error al guardar la reserva.';
      mostrarError(msg);
      return;
    }

    let mensaje;
    if (accion === 'cancelar') {
      mensaje = 'Reserva cancelada correctamente. Redirigiendo al calendario...';
    } else if (accion === 'actualizar') {
      mensaje = 'Reserva actualizada correctamente. Redirigiendo al calendario...';
    } else {
      mensaje = 'Reserva creada correctamente. Redirigiendo al calendario...';
    }

    mostrarOk(mensaje);

    // Obtener tipo de recurso para redirigir al calendario adecuado
    const selectRecurso = document.getElementById('recurso_id');
    const optSel        = selectRecurso.options[selectRecurso.selectedIndex];
    const tipoRecurso   = optSel ? (optSel.dataset.tipo || 'vehiculo') : 'vehiculo';

    setTimeout(() => {
      window.location.href = '../calendar/index.php?tipo=' + encodeURIComponent(tipoRecurso);
    }, 1200);

  } catch (err) {
    console.error(err);
    mostrarError('Error de comunicación con el servidor.');
  }
}

// Submit del formulario: crear o actualizar
form.addEventListener('submit', (e) => {
  e.preventDefault();
  const accionActual = inputAccion.value || 'agregar'; // 'agregar' o 'actualizar'
  enviarReserva(accionActual);
});

// Botón cancelar reserva (solo en modo edición)
if (btnCancelarReserva) {
  btnCancelarReserva.addEventListener('click', () => {
    if (!inputId.value) {
      mostrarError('ID de reserva no válido.');
      return;
    }
    if (confirm('¿Seguro que deseas cancelar esta reserva?')) {
      enviarReserva('cancelar');
    }
  });
}
</script>

</body>
</html>
