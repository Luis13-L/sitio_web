

<?php
include("../../templates/header.php");
include("../../bd.php");

$mensaje = '';

if ($_POST) {
  $titulo      = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
  $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';
  $iconoNombre = '';

  // Validar archivo
  if (!empty($_FILES['icono']['name'])) {
    $file  = $_FILES['icono'];
    $tmp   = $file['tmp_name'];
    $name  = $file['name'];
    $size  = (int)$file['size'];
    $error = (int)$file['error'];

    if ($error !== UPLOAD_ERR_OK) {
      $mensaje = "Error al subir el archivo (código $error).";
    } elseif ($size > 2 * 1024 * 1024) { // 2 MB
      $mensaje = "El archivo supera el tamaño permitido (2 MB).";
    } else {
      $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
      $permitidas = ['png','jpg','jpeg','webp','svg'];
      if (!in_array($ext, $permitidas, true)) {
        $mensaje = "Formato no permitido. Usa PNG/JPG/WebP/SVG.";
      } else {
        // Carpeta destino (…/assets/img/services)
        $destDir = __DIR__ . "/../../../assets/img/servicios";
        if (!is_dir($destDir)) { @mkdir($destDir, 0775, true); }

        // Nombre seguro
        $base   = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($name, PATHINFO_FILENAME));
        $nuevo  = time() . "_" . $base . "." . $ext;
        $dest   = $destDir . "/" . $nuevo;

        if (!move_uploaded_file($tmp, $dest)) {
          $mensaje = "No se pudo guardar el archivo en el servidor.";
        } else {
          $iconoNombre = $nuevo; // guardar en DB
        }
      }
    }
  } else {
    $mensaje = "Selecciona un archivo de imagen/ícono.";
  }

  // Insertar si todo OK
  if (empty($mensaje)) {
    $sql = "INSERT INTO `tbl_servicios` (`ID`,`icono`,`titulo`,`descripcion`)
            VALUES (NULL, :icono, :titulo, :descripcion)";
    $sentencia = $conexion->prepare($sql);
    $sentencia->bindParam(":icono", $iconoNombre);
    $sentencia->bindParam(":titulo", $titulo);
    $sentencia->bindParam(":descripcion", $descripcion);
    $sentencia->execute();

    header("Location: index.php?mensaje=" . urlencode("Registro creado con éxito."));
    exit;
  }
}
?>

<div class="card">
  <div class="card-header">Crear Servicios</div>
  <div class="card-body">

    <?php if (!empty($mensaje)): ?>
      <div class="alert alert-danger py-2"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data">
      <div class="mb-3">
        <label for="icono" class="form-label">Imagen / ícono (PNG, JPG, WebP, SVG) máx. 2MB</label>
        <input type="file" class="form-control" name="icono" id="icono"
               accept=".png,.jpg,.jpeg,.webp,.svg" required>
      </div>

      <div class="mb-3">
        <label for="titulo" class="form-label">Título:</label>
        <input type="text" class="form-control" name="titulo" id="titulo" required>
      </div>

      <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción:</label>
        <input type="text" class="form-control" name="descripcion" id="descripcion">
      </div>

      <button type="submit" class="btn btn-success">Agregar</button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
    </form>

  </div>
  <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
