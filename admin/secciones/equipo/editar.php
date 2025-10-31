<?php
include("../../templates/header.php");
include("../../bd.php");

/* ===== CARGA INICIAL ===== */
$txtID = '';
$imagen = $nombrecompleto = $puesto = $correo = $linkedin = '';

if (isset($_GET['txtID']) && is_numeric($_GET['txtID'])) {
  $txtID = (int)$_GET['txtID'];

  $st = $conexion->prepare("SELECT * FROM `tbl_equipo` WHERE id = :id");
  $st->bindParam(":id", $txtID, PDO::PARAM_INT);
  $st->execute();

  if ($reg = $st->fetch(PDO::FETCH_ASSOC)) {
    $imagen         = $reg["imagen"]         ?? '';
    $nombrecompleto = $reg["nombrecompleto"] ?? '';
    $puesto         = $reg["puesto"]         ?? '';
    $correo         = $reg["correo"]         ?? '';
    $linkedin       = $reg["linkedin"]       ?? '';
  }
}

/* ===== ACTUALIZAR ===== */
if ($_POST) {
  $txtID          = isset($_POST['txtID']) ? (int)$_POST['txtID'] : 0;
  $nombrecompleto = $_POST['nombrecompleto'] ?? '';
  $puesto         = $_POST['puesto'] ?? '';
  $correo         = trim($_POST['correo'] ?? '');
  $linkedin       = trim($_POST['linkedin'] ?? '');

  // Actualiza campos de texto
  $up = $conexion->prepare(
    "UPDATE tbl_equipo SET 
       nombrecompleto = :nombrecompleto,
       puesto         = :puesto,
       correo         = :correo,
       linkedin       = :linkedin
     WHERE id = :id"
  );
  $up->bindParam(":nombrecompleto", $nombrecompleto);
  $up->bindParam(":puesto",         $puesto);
  $up->bindParam(":correo",         $correo);
  $up->bindParam(":linkedin",       $linkedin);
  $up->bindParam(":id",             $txtID, PDO::PARAM_INT);
  $up->execute();

  // Si suben nueva imagen, reemplazar
  if (!empty($_FILES["imagen"]["tmp_name"])) {
    $origName = $_FILES["imagen"]["name"] ?? '';
    $tmpFile  = $_FILES["imagen"]["tmp_name"] ?? '';

    // nombre único y saneado
    $stamp = (new DateTime())->getTimestamp();
    $limpio = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $origName);
    $nombre_archivo_imagen = $stamp . '_' . $limpio;

    $dest = __DIR__ . "/../../../assets/img/team/" . $nombre_archivo_imagen;

    if (move_uploaded_file($tmpFile, $dest)) {
      // borrar imagen anterior
      $q = $conexion->prepare("SELECT imagen FROM `tbl_equipo` WHERE id = :id");
      $q->bindParam(":id", $txtID, PDO::PARAM_INT);
      $q->execute();
      $old = $q->fetch(PDO::FETCH_ASSOC);

      if ($old && !empty($old['imagen'])) {
        $oldPath = __DIR__ . "/../../../assets/img/team/" . $old['imagen'];
        if (is_file($oldPath)) { @unlink($oldPath); }
      }

      // guardar nueva imagen
      $qi = $conexion->prepare("UPDATE tbl_equipo SET imagen = :imagen WHERE id = :id");
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
    Editar datos del personal
  </div>

  <div class="card-body">
    <form action="" method="post" enctype="multipart/form-data">
      <div class="mb-3">
        <label class="form-label">ID</label>
        <input type="text" class="form-control" name="txtID" id="txtID"
               value="<?= htmlspecialchars((string)$txtID) ?>" readonly>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label for="nombrecompleto" class="form-label">Nombre completo</label>
          <input type="text" class="form-control" name="nombrecompleto" id="nombrecompleto"
                 value="<?= htmlspecialchars($nombrecompleto) ?>" placeholder="Nombre" required>
        </div>

        <div class="col-md-6">
          <label for="puesto" class="form-label">Puesto</label>
          <input type="text" class="form-control" name="puesto" id="puesto"
                 value="<?= htmlspecialchars($puesto) ?>" placeholder="Puesto">
        </div>

        <div class="col-md-6">
          <label for="correo" class="form-label">Correo</label>
          <input type="email" class="form-control" name="correo" id="correo"
                 value="<?= htmlspecialchars($correo) ?>" placeholder="correo@dominio.com">
        </div>

        <div class="col-md-6">
          <label for="linkedin" class="form-label">LinkedIn (URL)</label>
          <input type="url" class="form-control" name="linkedin" id="linkedin"
                 value="<?= htmlspecialchars($linkedin) ?>" placeholder="https://www.linkedin.com/in/usuario">
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
              <img src="../../../assets/img/team/<?= htmlspecialchars($imagen) ?>"
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
