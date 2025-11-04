<?php
// admin/secciones/configuraciones/index.php

/* ==== GUARDS: antes de imprimir HTML ==== */
require_once __DIR__ . '/../../auth_guard.php';
require_login();                         // todos deben iniciar sesión
$esAdmin = (current_role() === 'admin');

require_once __DIR__ . '/../../bd.php';

/* ==== CSRF para acciones (p.ej. eliminar por POST si luego lo habilitas) ==== */
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

/* ==== ELIMINAR (OPCIONAL) — solo admin, por POST + CSRF ==== */
/*
if ($esAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  // 1) CSRF
  if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header("Location: index.php?error=" . urlencode("Token CSRF inválido.")); exit;
  }
  // 2) ID válido
  $id = $_POST['id'] ?? '';
  if (!ctype_digit((string)$id) || (int)$id <= 0) {
    header("Location: index.php?error=" . urlencode("ID inválido.")); exit;
  }
  // 3) ejecutar
  $del = $conexion->prepare("DELETE FROM `tbl_confifiguraciones` WHERE id = :id");
  $del->bindParam(":id", $id, PDO::PARAM_INT);
  $del->execute();

  // rotar token
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  header("Location: index.php?mensaje=" . urlencode("Registro eliminado.")); exit;
}
*/

/* ==== LISTAR ==== */
$st = $conexion->prepare("SELECT * FROM `tbl_confifiguraciones` ORDER BY id DESC");
$st->execute();
$lista_configuraciones = $st->fetchAll(PDO::FETCH_ASSOC);

$mensaje = $_GET['mensaje'] ?? '';
$error   = $_GET['error'] ?? '';

include("../../templates/header.php");
?>
<style>
  /* Quita color “magenta de código” y mono en la columna Valor */
.config-value{
  color: inherit !important;
  font-family: inherit !important;
  background: transparent !important;
  white-space: normal !important;
}

/* Por si algún tema envuelve en <code> */
.table td code,
.config-value code {
  color: inherit !important;
  font-family: inherit !important;
  background: transparent !important;
  padding: 0 !important;
}

</style>
<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span style="font-weight:700; font-size:1.25rem;">Configuración</span>

    <?php if ($esAdmin): ?>
      <!-- Si más adelante permites crear nuevos, habilita el botón: -->
      <!-- <a class="btn btn-primary" href="crear.php" role="button">Agregar registro</a> -->
    <?php endif; ?>
  </div>

  <div class="card-body">
    <?php if ($mensaje): ?>
      <div class="alert alert-success py-2 mb-3"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger py-2 mb-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$esAdmin): ?>
      <div class="alert alert-info py-2 mb-3">
        Modo solo lectura. Si necesitas editar, contacta a un administrador.
      </div>
    <?php endif; ?>

    <div class="table-responsive-sm">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width:70px;">ID</th>
            <th style="min-width:240px;">Nombre de la configuración</th>
            <th>Valor</th>
            <th class="icon-col" style="width:120px;">Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_configuraciones as $reg): ?>
            <?php
              $id   = (int)($reg['ID'] ?? $reg['id'] ?? 0);
              $name = htmlspecialchars($reg['nombreConfiguracion'] ?? '');
              $val  = (string)($reg['valor'] ?? '');
              $val_safe  = htmlspecialchars($val);
              // Truncar para celda (con ellipsis) pero dejar el completo en title
              $val_short = mb_strimwidth($val, 0, 160, '…', 'UTF-8');
            ?>
            <tr>
              <td><?= $id ?></td>
              <td class="fw-semibold"><?= $name ?></td>
              <td class="text-wrap">
                <span class="config-value"><?= htmlspecialchars($val_short) ?></span>
              </td>

              <td class="cell-center">
                <?php if ($esAdmin): ?>
                  <a class="btn btn-brand-outline btn-icon"
                     href="editar.php?txtID=<?= $id ?>"
                     data-bs-toggle="tooltip" data-bs-placement="top"
                     title="Editar">
                    <i class="fa-solid fa-pen"></i>
                    <span class="visually-hidden">Editar</span>
                  </a>

                  <!-- (Opcional) Eliminar por POST + CSRF
                  <form method="post" class="d-inline"
                        onsubmit="return confirm('¿Eliminar este registro?');">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <button type="submit" class="btn btn-danger btn-icon" title="Eliminar">
                      <i class="fa-solid fa-trash"></i>
                      <span class="visually-hidden">Eliminar</span>
                    </button>
                  </form>
                  -->
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (!$lista_configuraciones): ?>
            <tr>
              <td colspan="4" class="text-center text-muted">No hay registros.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
(function () {
  if (window.bootstrap) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      new bootstrap.Tooltip(el);
    });
  }
})();
</script>

<?php include("../../templates/footer.php"); ?>
