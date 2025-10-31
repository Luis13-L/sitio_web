<?php
include("../../templates/header.php");
include("../../bd.php");

if ($_POST) {
  // Campos
  $fecha       = $_POST['fecha']       ?? '';
  $titulo      = $_POST['titulo']      ?? '';
  $descripcion = $_POST['descripcion'] ?? '';

  // Imagen (opcional)
  $nombre_archivo_imagen = '';
  if (!empty($_FILES['imagen']['tmp_name'])) {
    $origName = $_FILES['imagen']['name'] ?? '';
    $tmpFile  = $_FILES['imagen']['tmp_name'] ?? '';

    // nombre único + saneado
    $stamp  = (new DateTime())->getTimestamp();
    $limpio = preg_replace('/[^A-Za-z0-9_\.-]/', '_', $origName);
    $nombre_archivo_imagen = $stamp . "_" . $limpio;

    $dest = __DIR__ . "/../../../assets/img/about/" . $nombre_archivo_imagen;
    @move_uploaded_file($tmpFile, $dest);
  }

  // Insert
  $sql = "INSERT INTO `tbl_entradas` (`fecha`,`titulo`,`descripcion`,`imagen`)
          VALUES (:fecha,:titulo,:descripcion,:imagen)";
  $st = $conexion->prepare($sql);
  $st->bindParam(":fecha",       $fecha);
  $st->bindParam(":titulo",      $titulo);
  $st->bindParam(":descripcion", $descripcion);
  $st->bindParam(":imagen",      $nombre_archivo_imagen);
  $st->execute();

  $mensaje = "Registro agregado con éxito";
  header("Location: index.php?mensaje=" . urlencode($mensaje));
  exit;
}
?>

<div class="card">
  <div class="card-header">Nueva entrada (Historia / Timeline)</div>

  <div class="card-body">
    <form action="" method="post" enctype="multipart/form-data">
      <div class="row g-3">
        <div class="col-md-4">
          <label for="fecha" class="form-label">Fecha</label>
          <input type="date" class="form-control" name="fecha" id="fecha" required>
        </div>

        <div class="col-md-8">
          <label for="titulo" class="form-label">Título</label>
          <input type="text" class="form-control" name="titulo" id="titulo" placeholder="Título" required>
        </div>

        <div class="col-12">
          <label for="descripcion" class="form-label">Descripción</label>
          <textarea class="form-control" name="descripcion" id="descripcion" rows="3" placeholder="Descripción"></textarea>
        </div>

        <div class="col-md-6">
          <label for="imagen" class="form-label">Imagen</label>
          <input type="file" class="form-control" name="imagen" id="imagen"
                 accept=".jpg,.jpeg,.png,.webp,.gif" aria-describedby="fileHelpId">
          <div id="fileHelpId" class="form-text">Formatos: JPG, PNG, WEBP, GIF</div>
        </div>
      </div>

      <div class="mt-4">
        <button type="submit" class="btn btn-success">Agregar</button>
        <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
      </div>
    </form>
  </div>

  <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
