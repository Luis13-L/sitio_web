<?php
include("../../templates/header.php");
include("../../bd.php");

if ($_POST) {

  // ---- Recibir campos ----
  $nombrecompleto = $_POST['nombrecompleto'] ?? '';
  $puesto         = $_POST['puesto'] ?? '';
  $correo         = $_POST['correo'] ?? '';
  $linkedin       = $_POST['linkedin'] ?? '';

  // Normalizaciones básicas (opcional)
  $correo   = trim($correo);
  $linkedin = trim($linkedin);

  // ---- Imagen (opcional, pero recomendado) ----
  $nombre_archivo_imagen = '';
  if (!empty($_FILES['imagen']['tmp_name'])) {
    $origName = $_FILES['imagen']['name'] ?? '';
    $tmpFile  = $_FILES['imagen']['tmp_name'] ?? '';

    // nombre único y saneado
    $stamp = (new DateTime())->getTimestamp();
    $limpio = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $origName);
    $nombre_archivo_imagen = $stamp . '_' . $limpio;

    $dest = __DIR__ . "/../../../assets/img/team/" . $nombre_archivo_imagen;
    @move_uploaded_file($tmpFile, $dest);
  }

  // ---- Insertar ----
  $sql = "INSERT INTO `tbl_equipo`
          (`imagen`, `nombrecompleto`, `puesto`, `correo`, `linkedin`)
          VALUES
          (:imagen, :nombrecompleto, :puesto, :correo, :linkedin)";
  $st = $conexion->prepare($sql);
  $st->bindParam(":imagen",         $nombre_archivo_imagen);
  $st->bindParam(":nombrecompleto", $nombrecompleto);
  $st->bindParam(":puesto",         $puesto);
  $st->bindParam(":correo",         $correo);
  $st->bindParam(":linkedin",       $linkedin);
  $st->execute();

  $mensaje = "Registro agregado con éxito";
  header("Location: index.php?mensaje=" . urlencode($mensaje));
  exit;
}
?>

<div class="card">
  <div class="card-header">
    Datos del personal
  </div>

  <div class="card-body">
    <form action="" method="post" enctype="multipart/form-data">
      <div class="mb-3">
        <label for="imagen" class="form-label">Imagen</label>
        <input type="file" class="form-control" name="imagen" id="imagen"
               accept=".jpg,.jpeg,.png,.webp,.gif" aria-describedby="fileHelpId">
        <div id="fileHelpId" class="form-text">Formatos permitidos: JPG, PNG, WEBP, GIF</div>
      </div>

      <div class="mb-3">
        <label for="nombrecompleto" class="form-label">Nombre completo</label>
        <input type="text" class="form-control" name="nombrecompleto" id="nombrecompleto"
               placeholder="Nombre" required>
      </div>

      <div class="mb-3">
        <label for="puesto" class="form-label">Puesto</label>
        <input type="text" class="form-control" name="puesto" id="puesto"
               placeholder="Puesto">
      </div>

      <div class="mb-3">
        <label for="correo" class="form-label">Correo</label>
        <input type="email" class="form-control" name="correo" id="correo"
               placeholder="correo@dominio.com">
      </div>

      <div class="mb-3">
        <label for="linkedin" class="form-label">LinkedIn (URL)</label>
        <input type="url" class="form-control" name="linkedin" id="linkedin"
               placeholder="https://www.linkedin.com/in/usuario">
      </div>

      <button type="submit" class="btn btn-success">Agregar</button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
    </form>
  </div>

  <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
