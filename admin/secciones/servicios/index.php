<?php
// admin/secciones/servicios/index.php
require_once __DIR__ . "/../../auth_guard.php";
require_login(); // ambos roles pueden ver el listado

require_once __DIR__ . "/../../bd.php";

/* Rutas unificadas */
$IMG_DIR = __DIR__ . "/../../../assets/img/services";
$IMG_URL = "../../../assets/img/services/";
$DOC_DIR = __DIR__ . "/../../../assets/docs/services";
$DOC_URL = "../../../assets/docs/services/";

$isAdmin = (($_SESSION['rol'] ?? '') === 'admin');

/* ===== CSRF para acciones ===== */
$csrf_token = ensure_csrf_token();

/* ===== ELIMINAR (solo admin, POST + CSRF) ===== */
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  verify_csrf_or_die($_POST['csrf_token'] ?? null);

  $id = $_POST['id'] ?? '';
  if (!ctype_digit((string)$id) || (int)$id <= 0) {
    header("Location: index.php?error=" . urlencode("ID inválido."));
    exit;
  }
  $txtID = (int)$id;

  // 1) obtener filenames
  $st = $conexion->prepare("SELECT icono, archivo FROM tbl_servicios WHERE id = :id");
  $st->bindParam(":id", $txtID, PDO::PARAM_INT);
  $st->execute();
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if ($row) {
    // 2) borrar imagen
    if (!empty($row['icono'])) {
      $imgPath = $IMG_DIR . "/" . $row['icono'];
      if (is_file($imgPath)) { @unlink($imgPath); }
    }
    // 3) borrar PDF
    if (!empty($row['archivo'])) {
      $pdfPath = $DOC_DIR . "/" . $row['archivo'];
      if (is_file($pdfPath)) { @unlink($pdfPath); }
    }
    // 4) borrar registro
    $del = $conexion->prepare("DELETE FROM tbl_servicios WHERE id = :id");
    $del->bindParam(":id", $txtID, PDO::PARAM_INT);
    $del->execute();

    header("Location: index.php?mensaje=" . urlencode("Servicio eliminado."));
    exit;
  } else {
    header("Location: index.php?error=" . urlencode("Servicio no encontrado."));
    exit;
  }
}

/* ---- LISTAR ---- */
$sentencia = $conexion->prepare("SELECT * FROM `tbl_servicios` ORDER BY id DESC");
$sentencia->execute();
$lista_servicios = $sentencia->fetchAll(PDO::FETCH_ASSOC);

$mensaje = $_GET['mensaje'] ?? '';
$error   = $_GET['error'] ?? '';

include("../../templates/header.php");
?>



<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span style="font-weight:700; font-size:1.25rem;">Servicios</span>
    <?php if ($isAdmin): ?>
      <a class="btn btn-primary" href="crear.php" role="button">Agregar registro</a>
    <?php endif; ?>
  </div>

  <div class="card-body">
    <?php if ($mensaje): ?>
      <div class="alert alert-success py-2 mb-3"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger py-2 mb-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
        <?php if (!$isAdmin): ?>
      <div class="alert alert-info py-2 mb-3">
        Modo solo lectura. Si necesitas editar, contacta a un administrador.
      </div>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th scope="col" style="width:70px;">ID</th>
            <th scope="col" style="width:120px;">Imagen</th>
            <th scope="col">Título</th>
            <th scope="col">Descripción</th>

            <th scope="col" class="icon-col" style="width:120px;">PDF</th>
            <?php if ($isAdmin): ?>
              <th scope="col" class="icon-col" style="width:160px;">Acción</th>
            <?php endif; ?>
          </tr>
        </thead>

        <tbody>
          <?php foreach ($lista_servicios as $reg): ?>
          <tr>
            <td><?= (int)$reg['ID']; ?></td>

            <td>
              <?php if (!empty($reg['icono'])): ?>
                <img
                  src="<?= $IMG_URL . htmlspecialchars($reg['icono']) ?>"
                  alt="<?= htmlspecialchars($reg['titulo']) ?>"
                  style="width:88px;height:88px;object-fit:contain;border:1px solid #e5e7eb;border-radius:8px;">
              <?php else: ?>
                <span class="text-muted">Sin imagen</span>
              <?php endif; ?>
            </td>

            <td><strong><?= htmlspecialchars($reg['titulo']) ?></strong></td>
            <td><?= htmlspecialchars($reg['descripcion']) ?></td>

            <td class="cell-center">
              <?php if (!empty($reg['archivo'])): ?>
                <a class="btn btn-brand-outline btn-icon"
                  href="<?= $DOC_URL . htmlspecialchars($reg['archivo']) ?>"
                  target="_blank" rel="noopener" title="Ver PDF">
                  <i class="fa-regular fa-file-pdf"></i>
                </a>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>



              <?php if ($isAdmin): ?>
                <td class="cell-center">
                  <div class="action-group">
                    <a class="btn btn-brand-outline btn-icon"
                      href="editar.php?txtID=<?= (int)$reg['ID']; ?>" title="Editar">
                      <i class="fa-solid fa-pen"></i>
                    </a>

                    <form method="post" class="d-inline js-delete">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <input type="hidden" name="id" value="<?= (int)$reg['ID']; ?>">
                      <button type="submit" class="btn btn-danger btn-icon" title="Eliminar">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </form>

                  </div>
                </td>
              <?php endif; ?>


          </tr>
          <?php endforeach; ?>

          <?php if (!$lista_servicios): ?>
          <tr>
            <td colspan="<?= $isAdmin ? 6 : 5 ?>" class="text-center text-muted">
              No hay servicios registrados.
            </td>
          </tr>
          <?php endif; ?>

        </tbody>
      </table>
    </div>
  </div>
</div>
<script>
  AdminUX.attachDeleteConfirms();
</script>

<?php include("../../templates/footer.php"); ?>
