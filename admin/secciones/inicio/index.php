<?php
// admin/secciones/inicio/index.php

require_once __DIR__ . "/../../auth_guard.php";
require_login(); // ambos roles con sesión
require_once __DIR__ . "/../../bd.php";

/* ===== Rol (robusto) ===== */
$roleFromHelper  = function_exists('current_role') ? current_role() : null;
$roleRaw         = $roleFromHelper ?: ($_SESSION['rol'] ?? '');
$roleNormalized  = strtolower(trim((string)$roleRaw));

/* Para este módulo, ambos roles pueden EDITAR; no habrá crear ni eliminar */
$canEdit   = in_array($roleNormalized, ['admin','user'], true);
$canCreate = false;
$canDelete = false;

/* Rutas a imágenes */
$IMG_DIR = __DIR__ . "/../../../assets/img";
$IMG_URL = "../../../assets/img/";

/* CSRF (ya no se usa para delete porque está deshabilitado, pero no estorba) */
if (function_exists('ensure_csrf_token')) {
  $csrf_token = ensure_csrf_token();
} else {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  $csrf_token = $_SESSION['csrf_token'];
}

/* ===== ELIMINAR: Deshabilitado a nivel servidor =====
   Si alguien intenta forzar un POST delete, lo ignoramos con error. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  header("Location: index.php?error=" . urlencode("La eliminación está deshabilitada en este módulo."));
  exit;
}

/* ===== LISTAR ===== */
$st = $conexion->prepare("SELECT * FROM tbl_inicioo ORDER BY id DESC");
$st->execute();
$lista = $st->fetchAll(PDO::FETCH_ASSOC);

$mensaje = $_GET['mensaje'] ?? '';
$error   = $_GET['error'] ?? '';

include("../../templates/header.php");
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span style="font-weight:700; font-size:1.25rem;">Portada y Logo</span>

    <!-- Agregar deshabilitado -->
    <button class="btn btn-primary" type="button" disabled title="Agregar deshabilitado en este módulo">
      Agregar registro
    </button>
  </div>

  <div class="card-body">
    <?php if ($mensaje): ?>
      <div class="alert alert-success py-2 mb-3"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger py-2 mb-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="alert alert-info py-2 mb-3">
      En <strong>Portada y Logo</strong> solo está disponible la acción <strong>Editar</strong>. La creación y eliminación están deshabilitadas.
    </div>

    <div class="table-responsive-sm">
      <table class="table align-middle">
        <thead>
          <tr>
            <th scope="col" style="width:70px;">ID</th>
            <th scope="col" style="width:220px;">Componente</th>
            <th scope="col" style="width:130px;">Imagen</th>
            <th scope="col" class="icon-col" style="width:120px;">Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista as $reg): ?>
            <?php
              $id   = (int)($reg['ID'] ?? $reg['id'] ?? 0);
              $comp = htmlspecialchars($reg['componente'] ?? '');
              $img  = htmlspecialchars($reg['imagen'] ?? '');
            ?>
            <tr>
              <td><?= $id ?></td>
              <td style="font-weight:600; text-transform:capitalize;"><?= $comp ?></td>
              <td>
                <?php if ($img): ?>
                  <img
                    src="<?= $IMG_URL . $img ?>"
                    alt="<?= $comp ?>"
                    style="width:88px;height:88px;object-fit:contain;border:1px solid #e5e7eb;border-radius:8px;">
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>

              <td class="cell-center">
                <?php if ($canEdit): ?>
                  <a class="btn btn-brand-outline btn-icon"
                     href="editar.php?txtID=<?= $id ?>" title="Editar">
                    <i class="fa-solid fa-pen"></i>
                  </a>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>

          <?php if (!$lista): ?>
            <tr>
              <td colspan="4" class="text-center text-muted">
                No hay registros.
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include("../../templates/footer.php"); ?>

