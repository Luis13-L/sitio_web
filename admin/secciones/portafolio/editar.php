<?php
// admin/secciones/portafolio/editar.php

// 1) Guardias
require_once __DIR__ . '/../../auth_guard.php';
require_role(['admin','user']); // ambos pueden entrar a ver
$isAdmin = (current_role() === 'admin');

// 2) DB
require_once __DIR__ . '/../../bd.php';

// 3) Rutas
$IMG_DIR = __DIR__ . "/../../../assets/img/portfolio";
$IMG_URL = "../../../assets/img/portfolio/";

// 4) CSRF (solo si es admin y va a postear)
if ($isAdmin && empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'] ?? '';

// ===== CARGA INICIAL =====
if (empty($_GET['txtID']) || !ctype_digit($_GET['txtID'])) {
  header("Location: index.php?mensaje=" . urlencode("ID inválido.")); exit;
}
$txtID = (int)$_GET['txtID'];

$st = $conexion->prepare("SELECT * FROM `tbl_portafolio` WHERE id=:id LIMIT 1");
$st->bindParam(":id", $txtID, PDO::PARAM_INT);
$st->execute();
$reg = $st->fetch(PDO::FETCH_ASSOC);

if (!$reg) {
  header("Location: index.php?mensaje=" . urlencode("Registro no encontrado.")); exit;
}

$titulo      = $reg['titulo']      ?? '';
$subtitulo   = $reg['subtitulo']   ?? '';
$imagen      = $reg['imagen']      ?? '';
$descripcion = $reg['descripcion'] ?? '';
$categoria   = $reg['categoria']   ?? '';
$url         = $reg['url']         ?? '';

$mensaje = "";

// ===== ACTUALIZAR (solo admin) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!$isAdmin) {
    http_response_code(403);
    $mensaje = "Acceso denegado: solo lectura para usuarios.";
  } else {
    // CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
      $mensaje = "Token CSRF inválido. Recarga la página e inténtalo de nuevo.";
    } else {
      $txtID       = (int)($_POST['txtID'] ?? 0);
      $tituloNew   = trim($_POST['titulo'] ?? '');
      $subNew      = trim($_POST['subtitulo'] ?? '');
      $descNew     = trim($_POST['descripcion'] ?? '');
      $catNew      = trim($_POST['categoria'] ?? '');
      $urlNew      = trim($_POST['url'] ?? '');

      // Update de texto
      if (!$mensaje) {
        $up = $conexion->prepare("
          UPDATE tbl_portafolio SET
            titulo=:titulo, subtitulo=:subtitulo, descripcion=:descripcion,
            categoria=:categoria, url=:url
          WHERE id=:id
        ");
        $up->bindParam(":titulo", $tituloNew);
        $up->bindParam(":subtitulo", $subNew);
        $up->bindParam(":descripcion", $descNew);
        $up->bindParam(":categoria", $catNew);
        $up->bindParam(":url", $urlNew);
        $up->bindParam(":id", $txtID, PDO::PARAM_INT);
        $up->execute();
      }

      // Imagen nueva (opcional)
      if (!$mensaje && !empty($_FILES['imagen']['name'])) {
        $f   = $_FILES['imagen'];
        $tmp = $f['tmp_name'];
        $err = (int)$f['error'];
        $sz  = (int)$f['size'];
        $nm  = $f['name'];

        if ($err !== UPLOAD_ERR_OK) {
          $mensaje = "Error al subir la imagen (código $err).";
        } elseif ($sz > 4 * 1024 * 1024) { // 4MB
          $mensaje = "La imagen supera el tamaño permitido (4 MB).";
        } else {
          $ext = strtolower(pathinfo($nm, PATHINFO_EXTENSION));
          $permitidas = ['jpg','jpeg','png','webp','gif'];
          if (!in_array($ext, $permitidas, true)) {
            $mensaje = "Formato no permitido. Usa JPG/PNG/WEBP/GIF.";
          } else {
            if (!is_dir($IMG_DIR)) { @mkdir($IMG_DIR, 0775, true); }
            $base  = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($nm, PATHINFO_FILENAME));
            $nuevo = time() . "_" . $base . "." . $ext;
            $dest  = $IMG_DIR . "/" . $nuevo;

            if (!move_uploaded_file($tmp, $dest)) {
              $mensaje = "No se pudo guardar la imagen en el servidor.";
            } else {
              // borrar anterior si existía
              if (!empty($imagen)) {
                $old = $IMG_DIR . "/" . $imagen;
                if (is_file($old)) { @unlink($old); }
              }
              // actualizar DB
              $qi = $conexion->prepare("UPDATE tbl_portafolio SET imagen=:img WHERE id=:id");
              $qi->bindParam(":img", $nuevo);
              $qi->bindParam(":id", $txtID, PDO::PARAM_INT);
              $qi->execute();
              $imagen = $nuevo;
            }
          }
        }
      }

      if (!$mensaje) {
        // renovar CSRF
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        header("Location: index.php?mensaje=" . urlencode("Registro actualizado con éxito."));
        exit;
      }

      // refrescar valores (si hubo error)
      $titulo      = $tituloNew;
      $subtitulo   = $subNew;
      $descripcion = $descNew;
      $categoria   = $catNew;
      $url         = $urlNew;
    }
  }
}

include("../../templates/header.php");
?>
<div class="card">
  <div class="card-header">Editar noticia</div>
  <div class="card-body">

    <?php if (!empty($mensaje)): ?>
      <div class="alert alert-<?= $isAdmin ? 'danger' : 'warning' ?> py-2"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data" <?= $isAdmin ? '' : 'onsubmit="return false;"' ?>>
      <?php if ($isAdmin): ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <?php endif; ?>

      <div class="mb-3">
        <label class="form-label">ID</label>
        <input type="text" class="form-control" name="txtID" id="txtID"
               value="<?= htmlspecialchars((string)$txtID) ?>" readonly>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label for="titulo" class="form-label">Título:</label>
          <input type="text" class="form-control" name="titulo" id="titulo"
                 value="<?= htmlspecialchars($titulo) ?>" placeholder="Título" <?= $isAdmin ? '' : 'readonly' ?>>
        </div>

        <div class="col-md-6">
          <label for="subtitulo" class="form-label">Subtítulo:</label>
          <input type="text" class="form-control" name="subtitulo" id="subtitulo"
                 value="<?= htmlspecialchars($subtitulo) ?>" placeholder="Subtítulo" <?= $isAdmin ? '' : 'readonly' ?>>
        </div>

        <div class="col-md-6">
          <label for="categoria" class="form-label">Categoría:</label>
          <input type="text" class="form-control" name="categoria" id="categoria"
                 value="<?= htmlspecialchars($categoria) ?>" placeholder="Categoría" <?= $isAdmin ? '' : 'readonly' ?>>
        </div>

        <div class="col-12">
          <label for="url" class="form-label">URL:</label>
          <input type="url" class="form-control" name="url" id="url"
                 value="<?= htmlspecialchars($url) ?>" placeholder="https://…" <?= $isAdmin ? '' : 'readonly' ?>>
        </div>

        <div class="col-12">
          <label for="descripcion" class="form-label">Descripción:</label>
          <textarea class="form-control" name="descripcion" id="descripcion" rows="3"
                    placeholder="Descripción" <?= $isAdmin ? '' : 'readonly' ?>><?= htmlspecialchars($descripcion) ?></textarea>
        </div>

        <div class="col-md-6">
          <label class="form-label d-block">Imagen actual:</label>
          <?php if ($imagen): ?>
            <img src="<?= $IMG_URL . htmlspecialchars($imagen) ?>"
                 alt="Imagen actual"
                 style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
          <?php else: ?>
            <span class="text-muted">Sin imagen</span>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <label for="imagen" class="form-label">Reemplazar imagen (opcional):</label>
          <input type="file" class="form-control" name="imagen" id="imagen"
                 accept=".jpg,.jpeg,.png,.webp,.gif" <?= $isAdmin ? '' : 'disabled' ?>>
          <div class="form-text">Formatos: JPG, PNG, WEBP, GIF (máx. 4 MB)</div>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2 mt-4">
        <?php if ($isAdmin): ?>
          <button type="submit"
                  class="btn btn-icon btn-brand-outline"
                  data-bs-toggle="tooltip" data-bs-placement="top"
                  title="Actualizar">
            <i class="fa-solid fa-floppy-disk"></i>
          </button>
        <?php endif; ?>

        <a class="btn btn-icon btn-outline-danger"
           href="index.php"
           data-bs-toggle="tooltip" data-bs-placement="top"
           title="Regresar">
          <i class="fa-solid fa-arrow-left"></i>
        </a>
      </div>
    </form>
  </div>
  <div class="card-footer text-muted"></div>
</div>

<script>
// Confirmación SweetAlert al guardar (solo admin)
<?php if ($isAdmin): ?>
(function(){
  const form = document.querySelector('form[action=""]');
  if (!form) return;

  if (window.bootstrap) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      new bootstrap.Tooltip(el);
    });
  }

  form.addEventListener('submit', function(e){
    if (!form.checkValidity()) return;
    e.preventDefault();

    Swal.fire({
      icon: 'question',
      title: 'Guardar cambios',
      text: '¿Estás seguro que quieres guardar los cambios?',
      showCancelButton: true,
      confirmButtonText: 'Sí, guardar',
      cancelButtonText: 'No, volver',
      confirmButtonColor: '#0d6efd',
      cancelButtonColor: '#6c757d'
    }).then((r)=>{
      if (r.isConfirmed){
        Swal.fire({title:'Guardando…', allowOutsideClick:false, allowEscapeKey:false, didOpen:()=>Swal.showLoading()});
        form.submit();
      }
    });
  }, {passive:false});
})();
<?php endif; ?>
</script>

<?php include("../../templates/footer.php"); ?>
