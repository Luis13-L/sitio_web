<?php
include("../../templates/header.php");
include("../../bd.php");

/* ==== CARGA INICIAL ==== */
$txtID = '';
$titulo = $subtitulo = $imagen = $descripcion = $cliente = $categoria = $url = '';

if (isset($_GET['txtID']) && is_numeric($_GET['txtID'])) {
  $txtID = (int)$_GET['txtID'];

  $st = $conexion->prepare("SELECT * FROM `tbl_portafolio` WHERE id = :id");
  $st->bindParam(":id", $txtID, PDO::PARAM_INT);
  $st->execute();

  if ($reg = $st->fetch(PDO::FETCH_ASSOC)) {
    $titulo      = $reg['titulo']      ?? '';
    $subtitulo   = $reg['subtitulo']   ?? '';
    $imagen      = $reg['imagen']      ?? '';
    $descripcion = $reg['descripcion'] ?? '';
    $cliente     = $reg['cliente']     ?? '';
    $categoria   = $reg['categoria']   ?? '';
    $url         = $reg['url']         ?? '';
  }
}

/* ==== ACTUALIZAR ==== */
if ($_POST) {
  $txtID       = isset($_POST['txtID'])      ? (int)$_POST['txtID'] : 0;
  $titulo      = $_POST['titulo']            ?? '';
  $subtitulo   = $_POST['subtitulo']         ?? '';
  $descripcion = $_POST['descripcion']       ?? '';
  $cliente     = $_POST['cliente']           ?? '';
  $categoria   = $_POST['categoria']         ?? '';  // <— ojo: sin tilde
  $url         = $_POST['url']               ?? '';

  // Update de campos de texto
  $up = $conexion->prepare(
    "UPDATE tbl_portafolio SET 
      titulo = :titulo,
      subtitulo = :subtitulo,
      descripcion = :descripcion,
      cliente = :cliente,
      categoria = :categoria,
      url = :url
     WHERE id = :id"
  );
  $up->bindParam(":titulo", $titulo);
  $up->bindParam(":subtitulo", $subtitulo);
  $up->bindParam(":descripcion", $descripcion);
  $up->bindParam(":cliente", $cliente);
  $up->bindParam(":categoria", $categoria);
  $up->bindParam(":url", $url);
  $up->bindParam(":id", $txtID, PDO::PARAM_INT);
  $up->execute();

  // Reemplazo de imagen (si se sube una nueva)
  if (!empty($_FILES["imagen"]["tmp_name"])) {

    $origName = $_FILES["imagen"]["name"] ?? '';
    $tmpFile  = $_FILES["imagen"]["tmp_name"] ?? '';

    $stamp = (new DateTime())->getTimestamp();
    $nombre_archivo_imagen = $stamp . "_" . preg_replace('/\s+/', '_', $origName);

    $dest = __DIR__ . "/../../../assets/img/portfolio/" . $nombre_archivo_imagen;

    if (move_uploaded_file($tmpFile, $dest)) {

      // Borrar imagen anterior
      $q = $conexion->prepare("SELECT imagen FROM `tbl_portafolio` WHERE id = :id");
      $q->bindParam(":id", $txtID, PDO::PARAM_INT);
      $q->execute();
      $old = $q->fetch(PDO::FETCH_ASSOC);

      if ($old && !empty($old['imagen'])) {
        $oldPath = __DIR__ . "/../../../assets/img/portfolio/" . $old['imagen'];
        if (is_file($oldPath)) { @unlink($oldPath); }
      }

      // Guardar nueva imagen
      $qi = $conexion->prepare("UPDATE tbl_portafolio SET imagen = :imagen WHERE id = :id");
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
    Editar elemento del portafolio
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
          <label for="titulo" class="form-label">Título:</label>
          <input type="text" class="form-control" name="titulo" id="titulo"
                 value="<?= htmlspecialchars($titulo) ?>" placeholder="Título">
        </div>

        <div class="col-md-6">
          <label for="subtitulo" class="form-label">Subtítulo:</label>
          <input type="text" class="form-control" name="subtitulo" id="subtitulo"
                 value="<?= htmlspecialchars($subtitulo) ?>" placeholder="Subtítulo">
        </div>

        <div class="col-md-6">
          <label for="cliente" class="form-label">Cliente:</label>
          <input type="text" class="form-control" name="cliente" id="cliente"
                 value="<?= htmlspecialchars($cliente) ?>" placeholder="Cliente">
        </div>

        <div class="col-md-6">
          <label for="categoria" class="form-label">Categoría:</label>
          <input type="text" class="form-control" name="categoria" id="categoria"
                 value="<?= htmlspecialchars($categoria) ?>" placeholder="Categoría">
        </div>

        <div class="col-12">
          <label for="url" class="form-label">URL:</label>
          <input type="url" class="form-control" name="url" id="url"
                 value="<?= htmlspecialchars($url) ?>" placeholder="https://…">
        </div>

        <div class="col-12">
          <label for="descripcion" class="form-label">Descripción:</label>
          <textarea class="form-control" name="descripcion" id="descripcion" rows="3"
                    placeholder="Descripción"><?= htmlspecialchars($descripcion) ?></textarea>
        </div>

        <div class="col-md-6">
          <label for="imagen" class="form-label">Imagen:</label>
          <input type="file" class="form-control" name="imagen" id="imagen"
                 accept=".jpg,.jpeg,.png,.webp,.gif" aria-describedby="fileHelpId">
          <div id="fileHelpId" class="form-text">Formatos: JPG, PNG, WEBP, GIF</div>
        </div>

        <div class="col-md-6 d-flex align-items-end">
          <?php if ($imagen): ?>
            <div class="d-flex align-items-center gap-3">
              <img src="../../../assets/img/portfolio/<?= htmlspecialchars($imagen) ?>"
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
</div>

<?php include("../../templates/footer.php"); ?>
