<?php
include("../../templates/header.php");
include("../../bd.php");

$mensaje = "";

/* ========== CARGAR REGISTRO ========== */
if (isset($_GET['txtID'])) {
    $txtID = $_GET['txtID'];
    $st = $conexion->prepare("SELECT * FROM `tbl_inicioo` WHERE id = :id");
    $st->bindParam(":id", $txtID, PDO::PARAM_INT);
    $st->execute();
    $registro = $st->fetch(PDO::FETCH_ASSOC);

    if (!$registro) {
        header("Location: index.php?mensaje=" . urlencode("Registro no encontrado."));
        exit;
    }

    $componente = $registro['componente'];
    $imagen     = $registro['imagen'];
} else {
    // Acceso directo sin ID
    header("Location: index.php");
    exit;
}

/* ========== ACTUALIZAR ========== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $txtID      = isset($_POST['txtID']) ? (int)$_POST['txtID'] : 0;
    $componente = isset($_POST['componente']) ? trim($_POST['componente']) : $componente; // readonly pero por si acaso
    $oldImagen  = $imagen; // guardar el nombre actual para posible borrado
    $newImagen  = null;

    // 1) Actualizar componente (aunque sea readonly en el form, mantenemos la lógica)
    $up = $conexion->prepare("UPDATE tbl_inicioo SET componente = :componente WHERE id = :id");
    $up->bindParam(":componente", $componente);
    $up->bindParam(":id", $txtID, PDO::PARAM_INT);
    $up->execute();

    // 2) Si subieron nueva imagen, validarla y moverla
    if (!empty($_FILES["imagen"]["name"])) {

        $file  = $_FILES["imagen"];
        $tmp   = $file["tmp_name"];
        $name  = $file["name"];
        $size  = (int)$file["size"];
        $error = (int)$file["error"];

        if ($error !== UPLOAD_ERR_OK) {
            $mensaje = "Error al subir el archivo (código $error).";
        } elseif ($size > 3 * 1024 * 1024) {
            $mensaje = "El archivo supera el tamaño permitido (3 MB).";
        } else {
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            $permitidas = ["png","jpg","jpeg","webp","svg"];
            if (!in_array($ext, $permitidas, true)) {
                $mensaje = "Formato no permitido. Usa PNG/JPG/WEBP/SVG.";
            } else {
                // Carpeta destino
                $destDir = __DIR__ . "/../../../assets/img/";
                if (!is_dir($destDir)) { @mkdir($destDir, 0775, true); }

                // Nombre seguro
                $base   = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($name, PATHINFO_FILENAME));
                $newName = time() . "_" . $base . "." . $ext;
                $dest    = $destDir . "/" . $newName;

                if (!move_uploaded_file($tmp, $dest)) {
                    $mensaje = "No se pudo guardar el archivo en el servidor.";
                } else {
                    $newImagen = $newName;
                }
            }
        }

        // 3) Si todo OK con el archivo, actualizar en BD y borrar la anterior
        if (empty($mensaje) && $newImagen) {
            $up2 = $conexion->prepare("UPDATE tbl_inicioo SET imagen = :img WHERE id = :id");
            $up2->bindParam(":img", $newImagen);
            $up2->bindParam(":id", $txtID, PDO::PARAM_INT);
            $up2->execute();

            // borrar imagen anterior SOLO si existía y no es la misma
            if ($oldImagen && $oldImagen !== $newImagen) {
                $oldPath = __DIR__ . "/../../../assets/img/" . $oldImagen;
                if (is_file($oldPath)) { @unlink($oldPath); }
            }

            // actualizar variable en memoria para que el preview muestre la nueva
            $imagen = $newImagen;
        }
    }

    if (empty($mensaje)) {
        $mensaje = "Registro modificado con éxito";
        header("Location: index.php?mensaje=" . urlencode($mensaje));
        exit;
    }
}
?>

<div class="card">
  <div class="card-header">
    Puedes cambiar la imagen del componente...
  </div>
  <div class="card-body">

    <?php if (!empty($mensaje)): ?>
      <div class="alert alert-danger py-2"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data">
      <div class="mb-3">
        <label for="txtID" class="form-label">ID</label>
        <input type="text" class="form-control" readonly name="txtID" id="txtID"
               value="<?= htmlspecialchars($txtID) ?>">
      </div>

      <div class="mb-3">
        <label for="componente" class="form-label">Componente:</label>
        <input type="text" class="form-control" readonly
               value="<?= htmlspecialchars($componente) ?>"
               name="componente" id="componente">
      </div>

      <div class="mb-3">
        <label for="imagen" class="form-label">Imagen actual:</label><br>
        <?php if (!empty($imagen)): ?>
          <img src="../../../assets/img/<?= htmlspecialchars($imagen) ?>" alt="Imagen actual"
               style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
        <?php else: ?>
          <span class="text-muted">Sin imagen</span>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label for="imagen" class="form-label">Nueva imagen (opcional):</label>
        <input type="file" class="form-control" name="imagen" id="imagen"
               accept=".png,.jpg,.jpeg,.webp,.svg">
        <small class="text-muted">Deja vacío para mantener la imagen actual. Máx. 3 MB.</small>
      </div>

      <button type="submit" class="btn btn-success">Actualizar</button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
    </form>
  </div>

  <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
