<?php
// admin/secciones/portafolio/crear.php

// 1) Guardias
require_once __DIR__ . '/../../auth_guard.php';
require_role(['admin']); // solo administradores

// 2) DB
require_once __DIR__ . '/../../bd.php';

// 3) CSRF (auth_guard ya abrió la sesión)
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// 4) Rutas
$IMG_DIR = __DIR__ . "/../../../assets/img/portfolio";
$IMG_URL = "../../../assets/img/portfolio/";

// 5) Lógica POST
$errores = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // CSRF
  if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errores[] = "Token CSRF inválido. Recarga la página e inténtalo de nuevo.";
  }

  // Inputs
  $titulo      = trim($_POST['titulo']      ?? '');
  $subtitulo   = trim($_POST['subtitulo']   ?? '');
  $descripcion = trim($_POST['descripcion'] ?? '');
  $categoria   = trim($_POST['categoria']   ?? '');
  $url         = trim($_POST['url']         ?? '');

  // Validaciones mínimas
  if ($titulo === '') {
    $errores[] = "El título es obligatorio.";
  }
  if ($url !== '' && !filter_var($url, FILTER_VALIDATE_URL)) {
    $errores[] = "La URL no es válida.";
  }

  // Imagen (opcional) — 4MB máx., tipos permitidos
  $imagenNombre = '';
  if (!empty($_FILES['imagen']['name'])) {
    $f   = $_FILES['imagen'];
    $tmp = $f['tmp_name'] ?? '';
    $err = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);
    $sz  = (int)($f['size'] ?? 0);
    $nm  = $f['name'] ?? '';

    if ($err !== UPLOAD_ERR_OK) {
      $errores[] = "Error al subir la imagen (código $err).";
    } elseif ($sz > 4 * 1024 * 1024) {
      $errores[] = "La imagen supera el tamaño permitido (4 MB).";
    } else {
      $ext = strtolower(pathinfo($nm, PATHINFO_EXTENSION));
      $permitidas = ['jpg','jpeg','png','webp','gif'];
      if (!in_array($ext, $permitidas, true)) {
        $errores[] = "Formato de imagen no permitido. Usa JPG/PNG/WEBP/GIF.";
      } else {
        if (!is_dir($IMG_DIR)) { @mkdir($IMG_DIR, 0775, true); }
        $base  = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($nm, PATHINFO_FILENAME));
        $nuevo = time() . "_" . $base . "." . $ext;
        $dest  = $IMG_DIR . "/" . $nuevo;

        if (!move_uploaded_file($tmp, $dest)) {
          $errores[] = "No se pudo guardar la imagen en el servidor.";
        } else {
          $imagenNombre = $nuevo;
        }
      }
    }
  }

  // Insert
  if (!$errores) {
    $sql = "INSERT INTO `tbl_portafolio`
              (titulo, subtitulo, imagen, descripcion, categoria, url)
            VALUES
              (:titulo, :subtitulo, :imagen, :descripcion, :categoria, :url)";
    $ins = $conexion->prepare($sql);
    $ins->bindParam(':titulo',      $titulo);
    $ins->bindParam(':subtitulo',   $subtitulo);
    $ins->bindParam(':imagen',      $imagenNombre);
    $ins->bindParam(':descripcion', $descripcion);
    $ins->bindParam(':categoria',   $categoria);
    $ins->bindParam(':url',         $url);
    $ins->execute();

    // Regenerar CSRF para evitar reenvíos
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header("Location: index.php?mensaje=" . urlencode("Noticia creada con éxito."));
    exit;
  }
}

// 6) Render
include("../../templates/header.php");
?>
<div class="card">
  <div class="card-header">
    <span style="font-weight:700; font-size:1.25rem;">Crear noticia</span>
  </div>

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

    <form action="" method="post" enctype="multipart/form-data" autocomplete="off" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

      <div class="row g-3">

        <div class="col-md-6">
          <label for="titulo" class="form-label">Título <span class="text-danger">*</span></label>
          <input
            type="text" class="form-control" name="titulo" id="titulo" required
            value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>"
            placeholder="Título de la noticia">
        </div>

        <div class="col-md-6">
          <label for="subtitulo" class="form-label">Subtítulo</label>
          <input
            type="text" class="form-control" name="subtitulo" id="subtitulo"
            value="<?= htmlspecialchars($_POST['subtitulo'] ?? '') ?>"
            placeholder="Subtítulo (opcional)">
        </div>

        <div class="col-md-6">
          <label for="categoria" class="form-label">Categoría</label>
          <input
            type="text" class="form-control" name="categoria" id="categoria"
            value="<?= htmlspecialchars($_POST['categoria'] ?? '') ?>"
            placeholder="Ej. Comunicación, Evento, Noticia">
        </div>

        <div class="col-12">
          <label for="url" class="form-label">URL</label>
          <input
            type="url" class="form-control" name="url" id="url"
            value="<?= htmlspecialchars($_POST['url'] ?? '') ?>"
            placeholder="https://… (opcional)">
        </div>

        <div class="col-12">
          <label for="descripcion" class="form-label">Descripción</label>
          <textarea class="form-control" name="descripcion" id="descripcion" rows="3"
                    placeholder="Descripción breve de la noticia / actividad"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
        </div>

        <div class="col-md-6">
          <label for="imagen" class="form-label">Imagen principal (opcional)</label>
          <input type="file" class="form-control" name="imagen" id="imagen"
                 accept=".jpg,.jpeg,.png,.webp,.gif" aria-describedby="fileHelpId">
          <div id="fileHelpId" class="form-text">Formatos: JPG, PNG, WEBP, GIF (máx. 4 MB)</div>
        </div>

      </div><!-- /.row -->

      <div class="d-flex align-items-center gap-2 mt-4">
        <!-- Crear -->
        <button type="submit"
                class="btn btn-icon btn-brand-outline"
                data-bs-toggle="tooltip" data-bs-placement="top"
                title="Crear">
          <i class="fa-solid fa-paper-plane"></i>
          <span class="visually-hidden">Crear</span>
        </button>

        <!-- Cancelar -->
        <a class="btn btn-icon btn-outline-danger"
           href="index.php"
           data-bs-toggle="tooltip" data-bs-placement="top"
           title="Cancelar">
          <i class="fa-solid fa-arrow-left"></i>
          <span class="visually-hidden">Cancelar</span>
        </a>
      </div>
    </form>
  </div>

  <div class="card-footer text-muted"></div>
</div>

<script>
// Tooltips + Confirmación SweetAlert antes de crear
(function(){
  if (window.bootstrap) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
  }

  const form = document.querySelector('form[action=""]');
  if (!form) return;

  form.addEventListener('submit', function(e){
    if (!form.checkValidity()) return; // deja que HTML5 muestre los mensajes
    e.preventDefault();

    Swal.fire({
      icon: 'question',
      title: 'Crear noticia',
      text: '¿Deseas crear esta noticia con los datos ingresados?',
      showCancelButton: true,
      confirmButtonText: 'Sí, crear',
      cancelButtonText: 'No, volver',
      confirmButtonColor: '#0d6efd',
      cancelButtonColor: '#6c757d'
    }).then((r)=>{
      if (r.isConfirmed) {
        Swal.fire({title:'Guardando…', allowOutsideClick:false, allowEscapeKey:false, didOpen:()=>Swal.showLoading()});
        form.submit();
      }
    });
  }, {passive:false});
})();
</script>

<?php include("../../templates/footer.php"); ?>
