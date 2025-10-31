
<?php
include("../../bd.php");
include("../../templates/header.php");

// helper
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nombre_unico(string $ext): string {
  return 'img_'.bin2hex(random_bytes(4)).'_'.time().'.'.$ext;
}

if($_POST){
  $componente = isset($_POST['componente']) ? strtolower(trim($_POST['componente'])) : '';
  $f = $_FILES['imagen'] ?? null;

  if(!$componente || !$f || empty($f['name'])){
    header("Location: index.php?mensaje=".urlencode("Completa componente e imagen."));
    exit;
  }

  if($f['error'] !== UPLOAD_ERR_OK){
    header("Location: index.php?mensaje=".urlencode("Error de subida (".$f['error'].")."));
    exit;
  }

  // validar tipo y extensión
  $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
  $ext = match($mime){
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    default => null
  };
  if(!$ext){
    header("Location: index.php?mensaje=".urlencode("Formato no permitido. Usa JPG/PNG/WebP."));
    exit;
  }

  // mover archivo
  $destDir = realpath(__DIR__."/../../../assets/img");
  if($destDir===false) $destDir = __DIR__."/../../../assets/img";
  if(!is_dir($destDir)) mkdir($destDir,0755,true);

  $nombre = nombre_unico($ext);
  $dest   = rtrim($destDir,'/').'/'.$nombre;

  if(!move_uploaded_file($f['tmp_name'], $dest)){
    header("Location: index.php?mensaje=".urlencode("No se pudo mover el archivo."));
    exit;
  }

  // guardar en BD
  $sql = "INSERT INTO `tbl_inicioo` (`ID`, `componente`, `imagen`) VALUES (NULL, :componente, :imagen)";
  $st  = $conexion->prepare($sql);
  $st->bindParam(":componente", $componente);
  $st->bindParam(":imagen", $nombre);
  $st->execute();

  header("Location: index.php?mensaje=".urlencode("Registro agregado con éxito"));
  exit;
}
?>

<div class="card">
  <div class="card-header">Portada</div>
  <div class="card-body">
    <form action="" enctype="multipart/form-data" method="post">
      <div class="mb-3">
        <label for="componente" class="form-label">Componente:</label>
        <select class="form-control" name="componente" id="componente" required>
          <option value="">Selecciona…</option>
          <option value="logo">Logo</option>
          <option value="hero">Portada (Hero)</option>
        </select>
      </div>

      <div class="mb-3">
        <label for="imagen" class="form-label">Imagen:</label>
        <input type="file" class="form-control" name="imagen" id="imagen"
               accept="image/jpeg,image/png,image/webp" required>
        <small class="text-muted d-block mt-1">
          Se guardará tal cual (JPG/PNG/WebP). Sube imágenes grandes para portada (≥1600 px ancho).
        </small>
      </div>

      <button type="submit" class="btn btn-success">Agregar</button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
    </form>
  </div>
  <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
