<?php
include("../../templates/header.php");
include("../../bd.php");

$mensaje = "";

/* ===== CARGAR REGISTRO ===== */
if (isset($_GET['txtID'])) {
    $txtID = (int)$_GET['txtID'];

    $sentencia = $conexion->prepare("SELECT * FROM `tbl_servicios` WHERE id=:id");
    $sentencia->bindParam(":id", $txtID, PDO::PARAM_INT);
    $sentencia->execute();

    $registro = $sentencia->fetch(PDO::FETCH_ASSOC);
    if (!$registro) {
        header("Location: index.php?mensaje=" . urlencode("Servicio no encontrado."));
        exit;
    }

    $icono       = $registro['icono'];
    $titulo      = $registro['titulo'];
    $descripcion = $registro['descripcion'];
} else {
    header("Location: index.php");
    exit;
}

/* ===== ACTUALIZAR ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $txtID       = (int)($_POST['txtID'] ?? 0);
    $tituloNew   = trim($_POST['titulo'] ?? '');
    $descNew     = trim($_POST['descripcion'] ?? '');
    $iconoActual = $icono;   // nombre de archivo actual
    $nuevoIcono  = null;

    // 1) Si subieron una nueva imagen, validar y mover
    if (!empty($_FILES['icono']['name'])) {

        $f     = $_FILES['icono'];
        $tmp   = $f['tmp_name'];
        $name  = $f['name'];
        $size  = (int)$f['size'];
        $error = (int)$f['error'];

        if ($error !== UPLOAD_ERR_OK) {
            $mensaje = "Error al subir el archivo (código $error).";
        } elseif ($size > 2 * 1024 * 1024) {
            $mensaje = "El archivo supera el tamaño permitido (2 MB).";
        } else {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $permitidas = ['png','jpg','jpeg','webp','svg'];
            if (!in_array($ext, $permitidas, true)) {
                $mensaje = "Formato no permitido. Usa PNG/JPG/WebP/SVG.";
            } else {
                // carpeta destino: /assets/img/services
                $destDir = __DIR__ . "/../../../assets/img/services";
                if (!is_dir($destDir)) { @mkdir($destDir, 0775, true); }

                // nombre seguro
                $base  = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($name, PATHINFO_FILENAME));
                $fileN = time() . "_" . $base . "." . $ext;
                $dest  = $destDir . "/" . $fileN;

                if (!move_uploaded_file($tmp, $dest)) {
                    $mensaje = "No se pudo guardar el archivo en el servidor.";
                } else {
                    $nuevoIcono = $fileN;
                }
            }
        }
    }

    // 2) Si no hubo errores, actualizar DB
    if (empty($mensaje)) {
        if ($nuevoIcono) {
            // borrar anterior (si existe)
            $oldPath = __DIR__ . "/../../../assets/img/services/" . $iconoActual;
            if ($iconoActual && is_file($oldPath)) { @unlink($oldPath); }
            $iconoActual = $nuevoIcono;
        }

        $up = $conexion->prepare("UPDATE `tbl_servicios`
                                  SET icono=:icono, titulo=:titulo, descripcion=:descripcion
                                  WHERE id=:id");
        $up->bindParam(":icono", $iconoActual);
        $up->bindParam(":titulo", $tituloNew);
        $up->bindParam(":descripcion", $descNew);
        $up->bindParam(":id", $txtID, PDO::PARAM_INT);
        $up->execute();

        header("Location: index.php?mensaje=" . urlencode("Servicio actualizado con éxito."));
        exit;
    }

    // actualizar variables para que se refleje en el preview si falló algo menor
    $icono       = $iconoActual;
    $titulo      = $tituloNew;
    $descripcion = $descNew;
}
?>

<div class="card">
  <div class="card-header">Editar información de servicios</div>
  <div class="card-body">

    <?php if (!empty($mensaje)): ?>
      <div class="alert alert-danger py-2"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data">
      <div class="mb-3">
        <label for="txtID" class="form-label">ID:</label>
        <input readonly value="<?= htmlspecialchars($txtID) ?>" class="form-control" name="txtID" id="txtID">
      </div>

      <div class="mb-3">
        <label class="form-label d-block">Imagen actual:</label>
        <?php if (!empty($icono)): ?>
          <img src="../../../assets/img/services/<?= htmlspecialchars($icono) ?>"
               alt="Icono actual"
               style="width:88px;height:88px;object-fit:contain;border:1px solid #e5e7eb;border-radius:8px;">
        <?php else: ?>
          <span class="text-muted">Sin imagen</span>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label for="icono" class="form-label">Nueva imagen (opcional):</label>
        <input type="file" class="form-control" name="icono" id="icono"
               accept=".png,.jpg,.jpeg,.webp,.svg">
        <small class="text-muted">Deja vacío para mantener la imagen actual. Máximo 2 MB.</small>
      </div>

      <div class="mb-3">
        <label for="titulo" class="form-label">Título:</label>
        <input value="<?= htmlspecialchars($titulo) ?>" class="form-control" name="titulo" id="titulo" required>
      </div>

      <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción:</label>
        <input value="<?= htmlspecialchars($descripcion) ?>" class="form-control" name="descripcion" id="descripcion">
      </div>

      <button type="submit" class="btn btn-success">Actualizar</button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
    </form>

  </div>
  <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
