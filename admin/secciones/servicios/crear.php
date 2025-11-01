
<?php
include("../../templates/header.php");
include("../../bd.php");

$errores = [];
$mensajeOk = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $titulo      = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
  $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';

  $iconoNombre = '';
  $pdfNombre   = null; // <- nuevo campo para almacenar el PDF

  /* === Subida de ICONO (obligatorio) === */
  if (!empty($_FILES['icono']['name'])) {
    $file  = $_FILES['icono'];
    $tmp   = $file['tmp_name'];
    $name  = $file['name'];
    $size  = (int)$file['size'];
    $error = (int)$file['error'];

    if ($error !== UPLOAD_ERR_OK) {
      $errores[] = "Error al subir el ícono (código $error).";
    } elseif ($size > 2 * 1024 * 1024) { // 2 MB
      $errores[] = "El ícono supera el tamaño permitido (2 MB).";
    } else {
      $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
      $permitidas = ['png','jpg','jpeg','webp','svg'];
      if (!in_array($ext, $permitidas, true)) {
        $errores[] = "Formato de ícono no permitido. Usa PNG/JPG/WebP/SVG.";
      } else {
        $destDir = __DIR__ . "/../../../assets/img/services";
        if (!is_dir($destDir)) { @mkdir($destDir, 0775, true); }

        $base   = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($name, PATHINFO_FILENAME));
        $nuevo  = time() . "_" . $base . "." . $ext;
        $dest   = $destDir . "/" . $nuevo;

        if (!move_uploaded_file($tmp, $dest)) {
          $errores[] = "No se pudo guardar el ícono en el servidor.";
        } else {
          $iconoNombre = $nuevo;
        }
      }
    }
  } else {
    $errores[] = "Selecciona un archivo de imagen/ícono.";
  }

  /* === Subida de PDF (opcional) === */
  if (!empty($_FILES['archivo']['name'])) {
    $pdf  = $_FILES['archivo'];
    $tmp  = $pdf['tmp_name'];
    $name = $pdf['name'];
    $size = (int)$pdf['size'];
    $err  = (int)$pdf['error'];

    if ($err !== UPLOAD_ERR_OK) {
      $errores[] = "Error al subir el PDF (código $err).";
    } elseif ($size > 10 * 1024 * 1024) { // 10 MB
      $errores[] = "El PDF supera el tamaño permitido (10 MB).";
    } else {
      // Validación de tipo real con finfo
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mime  = $finfo->file($tmp);
      if ($mime !== 'application/pdf') {
        $errores[] = "Solo se permite subir archivos PDF.";
      } else {
        $docsDir = __DIR__ . "/../../../assets/docs/services";
        if (!is_dir($docsDir)) { @mkdir($docsDir, 0775, true); }

        $safeBase = bin2hex(random_bytes(6));            // nombre único
        $pdfNombre = time() . "_" . $safeBase . ".pdf";  // ej: 1730412345_ab12cd34ef56.pdf
        $destPdf   = $docsDir . "/" . $pdfNombre;

        if (!move_uploaded_file($tmp, $destPdf)) {
          $errores[] = "No se pudo guardar el PDF en el servidor.";
          $pdfNombre = null;
        }
      }
    }
  }

  /* === Insertar si todo OK === */
  if (empty($errores)) {
    $sql = "INSERT INTO `tbl_servicios` (`ID`,`icono`,`titulo`,`descripcion`,`archivo`)
            VALUES (NULL, :icono, :titulo, :descripcion, :archivo)";
    $sentencia = $conexion->prepare($sql);
    $sentencia->bindParam(":icono", $iconoNombre);
    $sentencia->bindParam(":titulo", $titulo);
    $sentencia->bindParam(":descripcion", $descripcion);
    $sentencia->bindParam(":archivo", $pdfNombre);
    $sentencia->execute();

    header("Location: index.php?mensaje=" . urlencode("Registro creado con éxito."));
    exit;
  }
}
?>

<div class="card">
  <div class="card-header">Crear Servicios</div>
  <div class="card-body">

    <?php if (!empty($errores)): ?>
      <div class="alert alert-danger py-2 mb-3">
        <ul class="mb-0">
          <?php foreach ($errores as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
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

      <!-- NUEVO: PDF opcional -->
      <div class="mb-3">
        <label for="archivo" class="form-label">Documento PDF (opcional) — máx. 10MB</label>
        <input type="file" class="form-control" name="archivo" id="archivo" accept="application/pdf">
      </div>

      <button type="submit" class="btn btn-success">Agregar</button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
    </form>

  </div>
  <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
