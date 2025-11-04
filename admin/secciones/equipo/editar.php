<?php
// admin/secciones/equipo/editar.php

/* ==== GUARDS ==== */
require_once __DIR__ . '/../../auth_guard.php';
require_role('admin'); // solo admins

require_once __DIR__ . '/../../bd.php';

/* ==== Rutas ==== */
$IMG_DIR = __DIR__ . "/../../../assets/img/team";
$IMG_URL = "../../../assets/img/team/";

/* ==== CSRF ==== */
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

/* ==== CARGA INICIAL ==== */
if (!isset($_GET['txtID']) || !ctype_digit($_GET['txtID'])) {
  header("Location: index.php?mensaje=" . urlencode("ID inválido"));
  exit;
}
$txtID = (int)$_GET['txtID'];

$st = $conexion->prepare("SELECT * FROM tbl_equipo WHERE id = :id");
$st->bindParam(":id", $txtID, PDO::PARAM_INT);
$st->execute();
$reg = $st->fetch(PDO::FETCH_ASSOC);
if (!$reg) { header("Location: index.php?mensaje=" . urlencode("Registro no encontrado")); exit; }

$imagen         = $reg["imagen"]         ?? '';
$nombrecompleto = $reg["nombrecompleto"] ?? '';
$puesto         = $reg["puesto"]         ?? '';
$correo         = $reg["correo"]         ?? '';
$linkedin       = $reg["linkedin"]       ?? '';

$errores = [];

/* ==== ACTUALIZAR ==== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF
  if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errores[] = "Token CSRF inválido. Recarga la página.";
  }

  // Inputs
  $txtID_post     = isset($_POST['txtID']) ? (int)$_POST['txtID'] : 0;
  $nombrecompleto = trim($_POST['nombrecompleto'] ?? '');
  $puesto         = trim($_POST['puesto'] ?? '');
  $correo         = trim($_POST['correo'] ?? '');
  $linkedin       = trim($_POST['linkedin'] ?? '');

  // Validaciones
  if ($txtID_post !== $txtID)                        { $errores[] = "ID inconsistente."; }
  if ($nombrecompleto === '' || mb_strlen($nombrecompleto) < 3) { $errores[] = "El nombre completo es obligatorio (mínimo 3 caracteres)."; }
  if ($correo   !== '' && !filter_var($correo,   FILTER_VALIDATE_EMAIL)) { $errores[] = "Correo electrónico inválido."; }
  if ($linkedin !== '' && !filter_var($linkedin, FILTER_VALIDATE_URL))   { $errores[] = "URL de LinkedIn inválida."; }

  // Imagen (opcional)
  $nuevoNombreImagen = null;
  if (!empty($_FILES['imagen']['name'])) {
    $f     = $_FILES['imagen'];
    $tmp   = $f['tmp_name'];
    $name  = $f['name'];
    $size  = (int)$f['size'];
    $error = (int)$f['error'];

    if ($error !== UPLOAD_ERR_OK) {
      $errores[] = "Error al subir la imagen (código $error).";
    } elseif ($size > 3 * 1024 * 1024) {
      $errores[] = "La imagen supera el tamaño permitido (3 MB).";
    } else {
      $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
      $permitidas = ['jpg','jpeg','png','webp','gif'];
      if (!in_array($ext, $permitidas, true)) {
        $errores[] = "Formato no permitido. Usa JPG/PNG/WebP/GIF.";
      } else {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($tmp);
        $mimes_ok = ['image/jpeg','image/png','image/webp','image/gif'];
        if (!in_array($mime, $mimes_ok, true)) {
          $errores[] = "El archivo no parece ser una imagen válida.";
        } else {
          if (!is_dir($IMG_DIR)) { @mkdir($IMG_DIR, 0775, true); }
          $base  = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($name, PATHINFO_FILENAME));
          $nuevoNombreImagen = time() . "_" . $base . "." . $ext;
          $dest  = $IMG_DIR . "/" . $nuevoNombreImagen;
          if (!move_uploaded_file($tmp, $dest)) {
            $errores[] = "No se pudo guardar la imagen en el servidor.";
            $nuevoNombreImagen = null;
          }
        }
      }
    }
  }

  if (!$errores) {
    // Actualizar datos
    $up = $conexion->prepare(
      "UPDATE tbl_equipo SET 
         nombrecompleto=:nombrecompleto,
         puesto=:puesto,
         correo=:correo,
         linkedin=:linkedin
       WHERE id=:id"
    );
    $up->bindParam(":nombrecompleto", $nombrecompleto);
    $up->bindParam(":puesto",         $puesto);
    $up->bindParam(":correo",         $correo);
    $up->bindParam(":linkedin",       $linkedin);
    $up->bindParam(":id",             $txtID, PDO::PARAM_INT);
    $up->execute();

    // Reemplazo de imagen
    if ($nuevoNombreImagen) {
      if (!empty($imagen)) {
        $old = $IMG_DIR . "/" . $imagen;
        if (is_file($old)) { @unlink($old); }
      }
      $qi = $conexion->prepare("UPDATE tbl_equipo SET imagen=:img WHERE id=:id");
      $qi->bindParam(":img", $nuevoNombreImagen);
      $qi->bindParam(":id",  $txtID, PDO::PARAM_INT);
      $qi->execute();
      $imagen = $nuevoNombreImagen;
    }

    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // nuevo token
    header("Location: index.php?mensaje=" . urlencode("Registro actualizado con éxito"));
    exit;
  }
}
?>

<?php include("../../templates/header.php"); ?>
<div class="card">
  <div class="card-header">Editar datos del personal</div>

  <div class="card-body">
    <?php if (!empty($errores)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errores as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data" autocomplete="off" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

      <div class="mb-3">
        <label class="form-label">ID</label>
        <input type="text" class="form-control" name="txtID" id="txtID"
               value="<?= htmlspecialchars((string)$txtID) ?>" readonly>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label for="nombrecompleto" class="form-label">Nombre completo</label>
          <input type="text" class="form-control" name="nombrecompleto" id="nombrecompleto"
                 value="<?= htmlspecialchars($nombrecompleto) ?>" minlength="3" required>
        </div>

        <div class="col-md-6">
          <label for="puesto" class="form-label">Puesto</label>
          <input type="text" class="form-control" name="puesto" id="puesto"
                 value="<?= htmlspecialchars($puesto) ?>">
        </div>

        <div class="col-md-6">
          <label for="correo" class="form-label">Correo</label>
          <input type="email" class="form-control" name="correo" id="correo"
                 value="<?= htmlspecialchars($correo) ?>" placeholder="correo@dominio.com">
        </div>

        <div class="col-md-6">
          <label for="linkedin" class="form-label">LinkedIn (URL)</label>
          <input type="url" class="form-control" name="linkedin" id="linkedin"
                 value="<?= htmlspecialchars($linkedin) ?>" placeholder="https://www.linkedin.com/in/usuario">
        </div>

        <div class="col-md-6">
          <label class="form-label d-block">Imagen actual</label>
          <?php if ($imagen): ?>
            <img src="<?= $IMG_URL . htmlspecialchars($imagen) ?>"
                 alt="Imagen actual"
                 style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
          <?php else: ?>
            <span class="text-muted">Sin imagen</span>
          <?php endif; ?>
        </div>

        <div class="col-md-6">
          <label for="imagen" class="form-label">Nueva imagen (opcional)</label>
          <input type="file" class="form-control" name="imagen" id="imagen"
                 accept=".jpg,.jpeg,.png,.webp,.gif" aria-describedby="fileHelpId">
          <div id="fileHelpId" class="form-text">Máx. 3MB. JPG, PNG, WEBP o GIF.</div>
        </div>
      </div>

      <!-- Botonera con iconos -->
      <div class="d-flex align-items-center gap-2 mt-4">
        <button type="submit"
                class="btn btn-icon btn-outline-primary"
                data-bs-toggle="tooltip" data-bs-placement="top"
                title="Actualizar">
          <i class="fa-solid fa-floppy-disk"></i>
        </button>

        <a href="index.php"
           class="btn btn-icon btn-outline-danger"
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
(function () {
  // Tooltips
  if (window.bootstrap) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
  }

  // Confirmación estilo Servicios
  const form = document.querySelector('form[action=""]');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    if (!form.checkValidity()) return; // deja validar HTML5
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
    }).then((r) => {
      if (r.isConfirmed) {
        Swal.fire({title:'Guardando…', allowOutsideClick:false, allowEscapeKey:false, didOpen:()=>Swal.showLoading()});
        form.submit();
      }
    });
  }, { passive:false });
})();
</script>

<?php include("../../templates/footer.php"); ?>

