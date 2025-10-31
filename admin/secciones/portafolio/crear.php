<?php
include("../../bd.php");
include("../../templates/header.php");

if ($_POST) {

  // 1. Recibir campos
  $titulo      = $_POST['titulo']      ?? '';
  $subtitulo   = $_POST['subtitulo']   ?? '';
  $descripcion = $_POST['descripcion'] ?? '';
  $cliente     = $_POST['cliente']     ?? '';
  $categoria   = $_POST['categoria']   ?? ''; // <-- sin tilde
  $url         = $_POST['url']         ?? '';

  // 2. Imagen (opcional)
  $nombre_archivo_imagen = "";
  if (!empty($_FILES["imagen"]["tmp_name"])) {

    $original   = $_FILES["imagen"]["name"] ?? '';
    $tmp_imagen = $_FILES["imagen"]["tmp_name"] ?? '';

    // timestamp + nombre saneado
    $fecha_imagen = new DateTime();
    $nombre_archivo_imagen =
      $fecha_imagen->getTimestamp() . "_" . preg_replace('/\s+/', '_', $original);

    $destino = __DIR__ . "/../../../assets/img/portfolio/" . $nombre_archivo_imagen;

    if ($tmp_imagen !== "") {
      move_uploaded_file($tmp_imagen, $destino);
    }
  }

  // 3. Insert en BD
  $sentencia = $conexion->prepare(
    "INSERT INTO `tbl_portafolio`
      (`titulo`, `subtitulo`, `imagen`, `descripcion`, `cliente`, `categoria`, `url`)
     VALUES
      (:titulo, :subtitulo, :imagen, :descripcion, :cliente, :categoria, :url);"
  );

  $sentencia->bindParam(":titulo",      $titulo);
  $sentencia->bindParam(":subtitulo",   $subtitulo);
  $sentencia->bindParam(":imagen",      $nombre_archivo_imagen);
  $sentencia->bindParam(":descripcion", $descripcion);
  $sentencia->bindParam(":cliente",     $cliente);
  $sentencia->bindParam(":categoria",   $categoria);
  $sentencia->bindParam(":url",         $url);

  $sentencia->execute();

  // 4. Redirigir con mensaje
  $mensaje = "Registro agregado con éxito";
  header("Location: index.php?mensaje=" . urlencode($mensaje));
  exit;
}
?>

<div class="card">
  <div class="card-header">
    Nuevo elemento del portafolio
  </div>

  <div class="card-body">
    <form action="" method="post" enctype="multipart/form-data">
      <div class="row g-3">

        <div class="col-md-6">
          <label for="titulo" class="form-label">Título:</label>
          <input
            type="text"
            class="form-control"
            name="titulo"
            id="titulo"
            placeholder="Título del elemento"
            required
          >
        </div>

        <div class="col-md-6">
          <label for="subtitulo" class="form-label">Subtítulo:</label>
          <input
            type="text"
            class="form-control"
            name="subtitulo"
            id="subtitulo"
            placeholder="Subtítulo (opcional)"
          >
        </div>

        <div class="col-md-6">
          <label for="cliente" class="form-label">Cliente:</label>
          <input
            type="text"
            class="form-control"
            name="cliente"
            id="cliente"
            placeholder="Cliente / Responsable"
          >
        </div>

        <div class="col-md-6">
          <label for="categoria" class="form-label">Categoría:</label>
          <input
            type="text"
            class="form-control"
            name="categoria"
            id="categoria"
            placeholder="Ej. Comunicación, Evento, Noticia"
          >
        </div>

        <div class="col-12">
          <label for="url" class="form-label">URL:</label>
          <input
            type="url"
            class="form-control"
            name="url"
            id="url"
            placeholder="https://… (opcional)"
          >
        </div>

        <div class="col-12">
          <label for="descripcion" class="form-label">Descripción:</label>
          <textarea
            class="form-control"
            name="descripcion"
            id="descripcion"
            rows="3"
            placeholder="Descripción breve de la noticia / actividad"
          ></textarea>
        </div>

        <div class="col-md-6">
          <label for="imagen" class="form-label">Imagen principal:</label>
          <input
            type="file"
            class="form-control"
            name="imagen"
            id="imagen"
            accept=".jpg,.jpeg,.png,.webp,.gif"
            aria-describedby="fileHelpId"
          >
          <div id="fileHelpId" class="form-text">
            Formatos permitidos: JPG, PNG, WEBP, GIF
          </div>
        </div>

      </div><!-- /.row -->

      <div class="mt-4">
        <button type="submit" class="btn btn-success">Agregar</button>
        <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
      </div>
    </form>
  </div>

  <div class="card-footer text-muted">
  </div>
</div>

<?php
include("../../templates/footer.php");
?>
