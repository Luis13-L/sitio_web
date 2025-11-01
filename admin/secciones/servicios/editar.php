<?php
include("../../templates/header.php");
include("../../bd.php");

$mensaje = "";

/* Rutas (coinciden con crear.php) */
$IMG_DIR = __DIR__ . "/../../../assets/img/services";
$IMG_URL = "../../../assets/img/services/";
$DOC_DIR = __DIR__ . "/../../../assets/docs/services";
$DOC_URL = "../../../assets/docs/services/";

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
    $archivo     = $registro['archivo']; // <-- PDF actual (puede ser null)
} else {
    header("Location: index.php");
    exit;
}

/* ===== ACTUALIZAR ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $txtID       = (int)($_POST['txtID'] ?? 0);
    $tituloNew   = trim($_POST['titulo'] ?? '');
    $descNew     = trim($_POST['descripcion'] ?? '');

    $iconoActual   = $icono;   // nombre de archivo de imagen actual
    $nuevoIcono    = null;

    $archivoActual = $archivo; // nombre de archivo pdf actual
    $nuevoPdf      = null;

    /* === IMAGEN: opcional (máx. 2MB) === */
    if (!empty($_FILES['icono']['name'])) {
        $f     = $_FILES['icono'];
        $tmp   = $f['tmp_name'];
        $name  = $f['name'];
        $size  = (int)$f['size'];
        $error = (int)$f['error'];

        if ($error !== UPLOAD_ERR_OK) {
            $mensaje = "Error al subir la imagen (código $error).";
        } elseif ($size > 2 * 1024 * 1024) {
            $mensaje = "La imagen supera el tamaño permitido (2 MB).";
        } else {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $permitidas = ['png','jpg','jpeg','webp','svg'];
            if (!in_array($ext, $permitidas, true)) {
                $mensaje = "Formato de imagen no permitido. Usa PNG/JPG/WebP/SVG.";
            } else {
                if (!is_dir($IMG_DIR)) { @mkdir($IMG_DIR, 0775, true); }
                $base  = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($name, PATHINFO_FILENAME));
                $fileN = time() . "_" . $base . "." . $ext;
                $dest  = $IMG_DIR . "/" . $fileN;

                if (!move_uploaded_file($tmp, $dest)) {
                    $mensaje = "No se pudo guardar la imagen en el servidor.";
                } else {
                    $nuevoIcono = $fileN;
                }
            }
        }
    }

    /* === PDF: reemplazar / eliminar === */
    // 1) Eliminar PDF actual si marcan el checkbox
    $eliminarPdf = isset($_POST['eliminar_archivo']) && $_POST['eliminar_archivo'] === '1';

    if ($eliminarPdf) {
        if ($archivoActual) {
            $old = $DOC_DIR . "/" . $archivoActual;
            if (is_file($old)) { @unlink($old); }
            $archivoActual = null;
        }
    }

    // 2) Subir un PDF nuevo (opcional, máx. 10MB)
    if (!empty($_FILES['archivo']['name'])) {
        $pdf  = $_FILES['archivo'];
        $tmp  = $pdf['tmp_name'];
        $name = $pdf['name'];
        $size = (int)$pdf['size'];
        $err  = (int)$pdf['error'];

        if ($err !== UPLOAD_ERR_OK) {
            $mensaje = "Error al subir el PDF (código $err).";
        } elseif ($size > 10 * 1024 * 1024) { // 10MB
            $mensaje = "El PDF supera el tamaño permitido (10 MB).";
        } else {
            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $mime  = $finfo->file($tmp);
            if ($mime !== 'application/pdf') {
                $mensaje = "Solo se permite subir archivos PDF.";
            } else {
                if (!is_dir($DOC_DIR)) { @mkdir($DOC_DIR, 0775, true); }
                $safeBase = bin2hex(random_bytes(6));
                $nuevoPdf = time() . "_" . $safeBase . ".pdf";
                $destPdf  = $DOC_DIR . "/" . $nuevoPdf;

                if (!move_uploaded_file($tmp, $destPdf)) {
                    $mensaje = "No se pudo guardar el PDF en el servidor.";
                    $nuevoPdf = null;
                } else {
                    // si había uno anterior y no marcaron eliminar (o aunque lo marcaran), lo borramos
                    if ($archivoActual) {
                        $old = $DOC_DIR . "/" . $archivoActual;
                        if (is_file($old)) { @unlink($old); }
                    }
                    $archivoActual = $nuevoPdf;
                }
            }
        }
    }

    /* === Actualizar DB si todo OK === */
    if (empty($mensaje)) {
        // si subieron nueva imagen, borrar anterior y usar la nueva
        if ($nuevoIcono) {
            $oldPath = $IMG_DIR . "/" . $iconoActual;
            if ($iconoActual && is_file($oldPath)) { @unlink($oldPath); }
            $iconoActual = $nuevoIcono;
        }

        $up = $conexion->prepare(
            "UPDATE `tbl_servicios`
             SET icono=:icono, titulo=:titulo, descripcion=:descripcion, archivo=:archivo
             WHERE id=:id"
        );
        $up->bindParam(":icono", $iconoActual);
        $up->bindParam(":titulo", $tituloNew);
        $up->bindParam(":descripcion", $descNew);
        $up->bindParam(":archivo", $archivoActual); // puede ser null
        $up->bindParam(":id", $txtID, PDO::PARAM_INT);
        $up->execute();

        header("Location: index.php?mensaje=" . urlencode("Servicio actualizado con éxito."));
        exit;
    }

    // refrescar valores tras error
    $icono       = $iconoActual;
    $titulo      = $tituloNew;
    $descripcion = $descNew;
    $archivo     = $archivoActual;
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

      <!-- Imagen -->
      <div class="mb-3">
        <label class="form-label d-block">Imagen actual:</label>
        <?php if (!empty($icono)): ?>
          <img src="<?= $IMG_URL . htmlspecialchars($icono) ?>"
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

      <!-- PDF -->
      <div class="mb-2">
        <label class="form-label d-block">Documento PDF:</label>
        <?php if (!empty($archivo)): ?>
          <a class="btn btn-sm btn-outline-secondary" href="<?= $DOC_URL . htmlspecialchars($archivo) ?>" target="_blank" rel="noopener">
            Ver PDF actual
          </a>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" value="1" id="eliminar_archivo" name="eliminar_archivo">
            <label class="form-check-label" for="eliminar_archivo">
              Eliminar PDF actual
            </label>
          </div>
        <?php else: ?>
          <span class="text-muted">Sin PDF adjunto</span>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label for="archivo" class="form-label">Reemplazar / subir nuevo PDF (opcional):</label>
        <input type="file" class="form-control" name="archivo" id="archivo" accept="application/pdf">
        <small class="text-muted">Máximo 10 MB. Si subes uno nuevo, el anterior se eliminará.</small>
      </div>

      <!-- Texto -->
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
