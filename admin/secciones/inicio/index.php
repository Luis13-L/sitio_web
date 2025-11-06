<?php
// admin/secciones/inicio/index.php

require_once __DIR__ . "/../../auth_guard.php";
require_login(); // ambos roles con sesión
require_once __DIR__ . "/../../bd.php";

/* ===== Rol (robusto) ===== */
$roleFromHelper  = function_exists('current_role') ? current_role() : null;
$roleRaw         = $roleFromHelper ?: ($_SESSION['rol'] ?? '');
$roleNormalized  = strtolower(trim((string)$roleRaw));

/* Para este módulo, tanto admin como user pueden gestionar */
$canManage = in_array($roleNormalized, ['admin','user'], true);

// Rutas a imágenes
$IMG_DIR = __DIR__ . "/../../../assets/img";
$IMG_URL = "../../../assets/img/";

// CSRF para acciones
$csrf_token = ensure_csrf_token();

/* ===== ELIMINAR (admin y user, POST + CSRF) ===== */
if ($canManage && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  verify_csrf_or_die($_POST['csrf_token'] ?? null);

  $id = $_POST['id'] ?? '';
  if (!ctype_digit((string)$id) || (int)$id <= 0) {
    header("Location: index.php?error=" . urlencode("ID inválido.")); exit;
  }
  $txtID = (int)$id;

  // obtener filename
  $st = $conexion->prepare("SELECT imagen FROM tbl_inicioo WHERE id = :id");
  $st->bindParam(":id", $txtID, PDO::PARAM_INT);
  $st->execute();
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if ($row) {
    // borrar archivo físico
    if (!empty($row['imagen'])) {
      $path = rtrim($IMG_DIR,'/')."/".$row['imagen'];
      if (is_file($path)) { @unlink($path); }
    }
    // borrar registro
    $del = $conexion->prepare("DELETE FROM tbl_inicioo WHERE id = :id");
    $del->bindParam(":id", $txtID, PDO::PARAM_INT);
    $del->execute();

    header("Location: index.php?mensaje=" . urlencode("Registro eliminado.")); exit;
  } else {
    header("Location: index.php?error=" . urlencode("Registro no encontrado.")); exit;
  }
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
    <?php if ($canManage): ?>
      <a class="btn btn-primary" href="crear.php">Agregar registro</a>
    <?php endif; ?>
  </div>

  <div class="card-body">
    <?php if ($mensaje): ?>
      <div class="alert alert-success py-2 mb-3"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger py-2 mb-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="table-responsive-sm">
      <table class="table align-middle">
        <thead>
          <tr>
            <th scope="col" style="width:70px;">ID</th>
            <th scope="col" style="width:220px;">Componente</th>
            <th scope="col" style="width:130px;">Imagen</th>
            <?php if ($canManage): ?>
              <th scope="col" class="icon-col" style="width:160px;">Acciones</th>
            <?php endif; ?>
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

              <?php if ($canManage): ?>
                <td class="cell-center">
                  <div class="action-group">
                    <a class="btn btn-brand-outline btn-icon"
                       href="editar.php?txtID=<?= $id ?>" title="Editar">
                      <i class="fa-solid fa-pen"></i>
                    </a>

                    <form method="post" class="d-inline"
                          onsubmit="return confirm('¿Eliminar este registro? También se borrará la imagen.');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <input type="hidden" name="id" value="<?= $id ?>">
                      <button type="submit" class="btn btn-danger btn-icon" title="Eliminar">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>

          <?php if (!$lista): ?>
            <tr>
              <td colspan="<?= $canManage ? 4 : 3 ?>" class="text-center text-muted">
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
