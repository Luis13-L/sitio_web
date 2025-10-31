<?php
include("../../templates/header.php");
include("../../bd.php");

/* ===== CARGA INICIAL ===== */
$txtID = '';
$fecha = $titulo = $descripcion = $imagen = '';

if (isset($_GET['txtID']) && is_numeric($_GET['txtID'])) {
  $txtID = (int)$_GET['txtID'];

  $st = $conexion->prepare("SELECT * FROM `tbl_entradas` WHERE id = :id");
  $st->bindParam(":id", $txtID, PDO::PARAM_INT);
  $st->execute();

  if ($reg = $st->fetch(PDO::FETCH_ASSOC)) {
    $fecha       = $reg['fecha']        ?? '';
    $titulo      = $reg['titulo']       ?? '';
    $descripcion = $reg['descripcion']  ?? '';
    $imagen      = $reg['imagen']       ?? '';
  }
}

/* ===== ACTUALIZAR ===== */
if ($_POST) {
  $txtID       = isset($_POST['txtID']) ? (int)$_POST['txtID'] : 0;
  $fecha       = $_POST['fecha']        ?? '';
  $titulo      = $_POST['titulo']       ?? '';
  $descripcion = $_POST['descripcion']  ?? '';

  // Update de campos de texto
  $up = $conexion->prepare(
    "UPDATE tbl_entradas
     SET fecha = :fecha, titulo = :titulo, descripcion = :descripcion
     WHERE id = :id"
  );
  $up->bindParam(":fecha",       $fecha);
  $up->bindParam(":titulo",      $titulo);
  $up->bindParam(":descripcion", $descripcion);
  $up->bindParam(":id",          $txtID, PDO::PARAM_INT);
  $up->execute();

  // Reemplazo de imagen (si se sube una nueva)
  if (!empty($_FILES["imagen"]["tmp_name"])) {
    $origName = $_FILES["imagen"]["name"] ?? '';
    $tmpFile  = $_FILES["imagen"]["tmp_name"] ?? '';

    // nombre único + saneado
    $stamp  = (new DateTime())->getTimestamp();
    $limpio = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $origName);
    $nombre_archivo_imagen = $stamp . "_" . $limpio;

    $dest = __DIR__ . "/../../../assets/img/about/" . $nombre_archivo_imagen;

    if (move_uploaded_file($tmpFile, $dest)) {
      // borrar imagen anterior
      $q = $conexion->prepare("SELECT imagen FROM `tbl_entradas` WHERE id = :id");
      $q->bindParam(":id", $txtID, PDO::PARAM_INT);
      $q->execute();
      $old = $q->fetch(PDO::FETCH_ASSOC);

      if ($old && !empty($old['imagen'])) {
        $oldPath = __DIR__ . "/../../../assets/img/about/" . $old['imagen'];
        if (is_file($oldPath)) { @unlink($oldPath); }
      }

      // guardar nueva imagen
      $qi = $conexion->prepare("UPDATE tbl_entradas SET imagen = :imagen WHERE id = :id");
      $qi->bindParam(":imagen", $nombre_archivo_imagen);
      $qi->bindParam(":id", $txtID, PDO::PARAM_INT);
      $qi->execute();

      $imagen = $nombre_archivo_imagen;
    }
  }

  $mensaje = "Registro actualizado con éxito";
  header("Location: index.php?mensaje=" . urlencode($mensaje));
  exit;
}
?>

<div class="card">
  <div class="card-header">
    Editar entrada (Historia / Timeline)
  </div>
  <div class="card-body">
    <form action="" method="post" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">ID</label>
        <input type="text" class="form-control" name="txtID" id="txtID"
               value="<?= htmlspecialchars((string)$txtID) ?>" readonly>
      </div>

      <div class="row g-3">
        <div class="col-md-4">
          <label for="fecha" class="form-label">Fecha</label>
          <input type="date" class="form-control" name="fecha" id="fecha"
                 value="<?= htmlspecialchars($fecha) ?>" required>
        </div>

        <div class="col-md-8">
          <label for="titulo" class="form-label">Título</label>
          <input type="text" class="form-control" name="titulo" id="titulo"
                 value="<?= htmlspecialchars($titulo) ?>" placeholder="Título" required>
        </div>

        <div class="col-12">
          <label for="descripcion" class="form-label">Descripción</label>
          <textarea class="form-control" name="descripcion" id="descripcion" rows="3"
                    placeholder="Descripción"><?= htmlspecialchars($descripcion) ?></textarea>
        </div>

        <div class="col-md-6">
          <label for="imagen" class="form-label">Imagen</label>
          <input type="file" class="form-control" name="imagen" id="imagen"
                 accept=".jpg,.jpeg,.png,.webp,.gif" aria-describedby="fileHelpId">
          <div id="fileHelpId" class="form-text">Formatos: JPG, PNG, WEBP, GIF</div>
        </div>

        <div class="col-md-6 d-flex align-items-end">
          <?php if ($imagen): ?>
            <div class="d-flex align-items-center gap-3">
              <img src="../../../assets/img/about/<?= htmlspecialchars($imagen) ?>"
                   alt="Imagen actual"
                   style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
              <small class="text-muted">Vista previa</small>
            </div>
          <?php else: ?>
            <span class="text-muted">Sin imagen</span>
          <?php endif; ?>
        </div>
      </div>

      <div class="mt-4">
        <button type="submit" class="btn btn-success">Actualizar</button>
        <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
      </div>
    </form>
  </div>
  <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
