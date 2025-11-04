<?php
// admin/secciones/configuraciones/editar.php

/* ==== GUARDS: antes de imprimir HTML ==== */
require_once __DIR__ . '/../../auth_guard.php';
require_role('admin'); // solo admin puede editar

require_once __DIR__ . '/../../bd.php';

/* ==== CSRF (auth_guard ya abrió la sesión) ==== */
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

/* ===== CARGA INICIAL ===== */
$txtID = '';
$nombreConfiguracion = $valor = '';
$errores = [];

if (isset($_GET['txtID']) && ctype_digit($_GET['txtID'])) {
  $txtID = (int)$_GET['txtID'];

  $st = $conexion->prepare("
    SELECT id, nombreConfiguracion, valor
    FROM `tbl_confifiguraciones`
    WHERE id = :id
    LIMIT 1
  ");
  $st->bindParam(":id", $txtID, PDO::PARAM_INT);
  $st->execute();

  if ($reg = $st->fetch(PDO::FETCH_ASSOC)) {
    $nombreConfiguracion = $reg['nombreConfiguracion'] ?? '';
    $valor               = $reg['valor'] ?? '';
  } else {
    header("Location: index.php?error=" . urlencode("Configuración no encontrada.")); exit;
  }
} else {
  header("Location: index.php?error=" . urlencode("ID inválido.")); exit;
}

/* ===== ACTUALIZAR ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // 1) CSRF
  if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errores[] = "Token CSRF inválido. Recarga la página e inténtalo de nuevo.";
  }

  // 2) Inputs
  $txtID               = (isset($_POST['txtID']) && ctype_digit($_POST['txtID'])) ? (int)$_POST['txtID'] : 0;
  $nombreConfiguracion = trim($_POST['nombreConfiguracion'] ?? '');
  $valor               = trim($_POST['valor'] ?? '');

  // 3) Validaciones simples
  if ($txtID <= 0) {
    $errores[] = "ID inválido.";
  }
  if ($nombreConfiguracion === '' || mb_strlen($nombreConfiguracion) < 3) {
    $errores[] = "El nombre es obligatorio (mínimo 3 caracteres).";
  }
  if (mb_strlen($valor) > 5000) {
    $errores[] = "El valor es demasiado largo (máximo 5000 caracteres).";
  }

  // 4) Update
  if (!$errores) {
    $up = $conexion->prepare("
      UPDATE `tbl_confifiguraciones`
         SET nombreConfiguracion = :nombreConfiguracion,
             valor               = :valor
       WHERE id = :id
    ");
    $up->bindParam(":nombreConfiguracion", $nombreConfiguracion);
    $up->bindParam(":valor",               $valor);
    $up->bindParam(":id",                  $txtID, PDO::PARAM_INT);
    $up->execute();

    // Regenerar token para evitar reenvíos
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header("Location: index.php?mensaje=" . urlencode("Registro modificado con éxito"));
    exit;
  }
}

/* ==== Render ==== */
include("../../templates/header.php");
?>
<div class="card">
  <div class="card-header"><span style="font-weight:700; font-size:1.25rem;">Editar configuración</span></div>

  <div class="card-body">
    <?php if (!empty($errores)): ?>
      <div class="alert alert-danger py-2">
        <ul class="mb-0">
          <?php foreach ($errores as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form id="form-config" action="" method="post" autocomplete="off" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

      <div class="mb-3">
        <label for="txtID" class="form-label">ID</label>
        <input type="text" class="form-control" id="txtID" name="txtID"
               value="<?= htmlspecialchars((string)$txtID) ?>" readonly>
      </div>

      <div class="mb-3">
        <label for="nombreConfiguracion" class="form-label">Nombre</label>
        <input type="text" class="form-control" id="nombreConfiguracion" name="nombreConfiguracion"
               value="<?= htmlspecialchars($nombreConfiguracion) ?>"
               placeholder="Nombre de la configuración" required minlength="3">
      </div>

      <div class="mb-2">
        <label for="valor" class="form-label d-flex align-items-center gap-2">
          Valor
          <small class="text-muted">(máx. 5000 caracteres)</small>
        </label>
        <textarea class="form-control" id="valor" name="valor" rows="6"
                  placeholder="Valor de la configuración"
                  maxlength="5000"
                  style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;"><?= htmlspecialchars($valor) ?></textarea>
        <div class="form-text">
          Puedes pegar texto largo. Se guardará tal cual.
        </div>
      </div>

      <div class="d-flex align-items-center gap-2 mt-3">
        <!-- Guardar -->
        <button type="submit"
                class="btn btn-icon btn-outline-primary"
                data-bs-toggle="tooltip" data-bs-placement="top"
                title="Guardar">
          <i class="fa-solid fa-floppy-disk"></i>
          <span class="visually-hidden">Guardar</span>
        </button>

        <!-- Volver -->
        <a href="index.php"
           class="btn btn-icon btn-outline-danger"
           data-bs-toggle="tooltip" data-bs-placement="top"
           title="Regresar">
          <i class="fa-solid fa-arrow-left"></i>
          <span class="visually-hidden">Regresar</span>
        </a>
      </div>
    </form>
  </div>

  <div class="card-footer text-muted"></div>
</div>

<script>
// Tooltips + confirmación con SweetAlert2
(function () {
  if (window.bootstrap) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      new bootstrap.Tooltip(el);
    });
  }

  const form = document.getElementById('form-config');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    if (!form.checkValidity()) return; // deja que HTML5 marque errores
    e.preventDefault();

    const nombre = document.getElementById('nombreConfiguracion')?.value?.trim() || '(sin nombre)';

    Swal.fire({
      icon: 'question',
      title: 'Guardar cambios',
      html: '¿Estás seguro que quieres guardar la configuración?<br><b>' + nombre + '</b>',
      showCancelButton: true,
      confirmButtonText: 'Sí, guardar',
      cancelButtonText: 'No, volver',
      confirmButtonColor: '#0d6efd',
      cancelButtonColor: '#6c757d'
    }).then((res) => {
      if (res.isConfirmed) {
        Swal.fire({
          title: 'Guardando…',
          allowOutsideClick: false,
          allowEscapeKey: false,
          didOpen: () => Swal.showLoading()
        });
        form.submit();
      }
    });
  }, { passive: false });
})();
</script>

<?php include("../../templates/footer.php"); ?>
