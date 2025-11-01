<?php
include("../../bd.php");
include("../../templates/header.php");

/* Rutas unificadas */
$IMG_DIR = __DIR__ . "/../../../assets/img/services";
$IMG_URL = "../../../assets/img/services/";
$DOC_DIR = __DIR__ . "/../../../assets/docs/services";
$DOC_URL = "../../../assets/docs/services/";

/* ---- ELIMINAR: borra DB + archivos (imagen + pdf) ---- */
if (isset($_GET['txtID']) && is_numeric($_GET['txtID'])) {
  $txtID = (int)$_GET['txtID'];

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
  }
}

/* ---- LISTAR ---- */
$sentencia = $conexion->prepare("SELECT * FROM `tbl_servicios` ORDER BY id DESC");
$sentencia->execute();
$lista_servicios = $sentencia->fetchAll(PDO::FETCH_ASSOC);

$mensaje = isset($_GET['mensaje']) ? $_GET['mensaje'] : '';
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Servicios</span>
    <a class="btn btn-primary" href="crear.php" role="button">Agregar registro</a>
  </div>

  <div class="card-body">
    <?php if ($mensaje): ?>
      <div class="alert alert-success py-2 mb-3"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th scope="col" style="width:70px;">ID</th>
            <th scope="col" style="width:120px;">Imagen</th>
            <th scope="col">Título</th>
            <th scope="col">Descripción</th>
            <th scope="col" style="width:120px;">PDF</th>
            <th scope="col" style="width:190px;">Acción</th>
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

            <td>
              <?php if (!empty($reg['archivo'])): ?>
                <a class="btn btn-sm btn-outline-primary"
                   href="<?= $DOC_URL . htmlspecialchars($reg['archivo']) ?>"
                   target="_blank" rel="noopener">
                   Ver PDF
                </a>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>

            <td>
              <a class="btn btn-info btn-sm"
                 href="editar.php?txtID=<?= (int)$reg['ID']; ?>">Editar</a>

              <a class="btn btn-danger btn-sm"
                 href="index.php?txtID=<?= (int)$reg['ID']; ?>"
                 onclick="return confirm('¿Eliminar este servicio? También se borrarán su imagen y PDF.');">
                 Eliminar
              </a>
            </td>
          </tr>
          <?php endforeach; ?>

          <?php if (!$lista_servicios): ?>
          <tr><td colspan="6" class="text-center text-muted">No hay servicios registrados.</td></tr>
          <?php endif; ?>

        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include("../../templates/footer.php"); ?>
