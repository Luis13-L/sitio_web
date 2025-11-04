<?php
// admin/secciones/configuraciones/crear.php

/* ==== GUARDS: siempre antes de imprimir HTML ==== */
require_once __DIR__ . '/../../auth_guard.php';
require_role('admin'); // solo admin crea configuraciones

require_once __DIR__ . '/../../bd.php';

/* ==== CSRF (auth_guard ya inició la sesión) ==== */
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // 1) CSRF
  if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errores[] = "Token CSRF inválido. Recarga la página e inténtalo de nuevo.";
  }

  // 2) Inputs
  $nombreConfiguracion = trim($_POST['nombreConfiguracion'] ?? '');
  $valor               = trim($_POST['valor'] ?? '');

  // 3) Validaciones
  if ($nombreConfiguracion === '' || mb_strlen($nombreConfiguracion) < 3) {
    $errores[] = "El nombre es obligatorio (mínimo 3 caracteres).";
  }
  if (mb_strlen($valor) > 5000) {
    $errores[] = "El valor es demasiado largo (máximo 5000 caracteres).";
  }

  // 3.1) (Opcional) Evitar duplicados por nombre
  if (!$errores) {
    $chk = $conexion->prepare("SELECT 1 FROM `tbl_confifiguraciones` WHERE nombreConfiguracion = :n LIMIT 1");
    $chk->execute([':n' => $nombreConfiguracion]);
    if ($chk->fetch()) {
      $errores[] = "Ya existe una configuración con ese nombre.";
    }
  }

  // 4) Insert
  if (!$errores) {
    $ins = $conexion->prepare("
      INSERT INTO `tbl_confifiguraciones` (nombreConfiguracion, valor)
      VALUES (:nombreConfiguracion, :valor)
    ");
    $ins->bindParam(':nombreConfiguracion', $nombreConfiguracion);
    $ins->bindParam(':valor', $valor);
    $ins->execute();

    // Regenerar token para el siguiente formulario
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header("Location: index.php?mensaje=" . urlencode("Registro agregado con éxito"));
    exit;
  }
}

/* ==== Render ==== */
include("../../templates/header.php");
?>
<div class="card">
  <div class="card-header"><span style="font-weight:700; font-size:1.25rem;">Nueva configuración</span></div>

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

    <form id="form-config-create" action="" method="post" autocomplete="off" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

      <div class="mb-3">
        <label for="nombreConfiguracion" class="form-label">Nombre</label>
        <input type="text"
               class="form-control"
               name="nombreConfiguracion"
               id="nombreConfiguracion"
               placeholder="Nombre de la configuración"
               minlength="3"
               required
               value="<?= htmlspecialchars($_POST['nombreConfiguracion'] ?? '') ?>">
      </div>

      <div class="mb-2">
        <label for="valor" class="form-label d-flex align-items-center gap-2">
          Valor
          <small class="text-muted">(máx. 5000 caracteres)</small>
        </label>
        <textarea class="form-control"
                  name="valor"
                  id="valor"
                  rows="6"
                  maxlength="5000"
                  placeholder="Valor de la configuración (JSON/HTML/texto)"
                  style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, 'Liberation Mono', 'Courier New', monospace;"><?= htmlspecialchars($_POST['valor'] ?? '') ?></textarea>
        <div class="form-text">Puedes pegar JSON, HTML o texto plano. Se guardará tal cual.</div>
      </div>

      <div class="d-flex align-items-center gap-2 mt-3">
        <!-- Agregar -->
        <button type="submit"
                class="btn btn-icon btn-outline-primary"
                data-bs-toggle="tooltip" data-bs-placement="top"
                title="Agregar">
          <i class="fa-solid fa-floppy-disk"></i>
          <span class="visually-hidden">Agregar</span>
        </button>

        <!-- Cancelar -->
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
(function () {
  // Tooltips
  if (window.bootstrap) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      new bootstrap.Tooltip(el);
    });
  }

  const form = document.getElementById('form-config-create');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    if (!form.checkValidity()) return; // deja que HTML5 marque errores
    e.preventDefault();

    const nombre = document.getElementById('nombreConfiguracion')?.value?.trim() || '(sin nombre)';

    Swal.fire({
      icon: 'question',
      title: 'Agregar configuración',
      html: '¿Quieres crear la configuración?<br><b>' + nombre + '</b>',
      showCancelButton: true,
      confirmButtonText: 'Sí, crear',
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
