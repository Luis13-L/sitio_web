<?php
// admin/secciones/equipo/crear.php

/* ==== GUARDS ==== */
require_once __DIR__ . '/../../auth_guard.php';
require_role('admin'); // solo administradores

require_once __DIR__ . '/../../bd.php';

/* ==== Rutas ==== */
$IMG_DIR = __DIR__ . "/../../../assets/img/team";

/* ==== CSRF ==== */
if (function_exists('ensure_csrf_token')) {
  $csrf_token = ensure_csrf_token();
} else {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  $csrf_token = $_SESSION['csrf_token'];
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF
  if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errores[] = "Token CSRF inválido. Recarga la página.";
  }

  // Inputs
  $nombrecompleto = trim($_POST['nombrecompleto'] ?? '');
  $puesto         = trim($_POST['puesto'] ?? '');
  $correo         = trim($_POST['correo'] ?? '');
  $linkedin       = trim($_POST['linkedin'] ?? '');

  // Validaciones
  if ($nombrecompleto === '' || mb_strlen($nombrecompleto) < 3) {
    $errores[] = "El nombre completo es obligatorio (mínimo 3 caracteres).";
  }
  if ($correo !== '' && !filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "Correo inválido.";
  }
  if ($linkedin !== '' && !filter_var($linkedin, FILTER_VALIDATE_URL)) {
    $errores[] = "URL de LinkedIn inválida.";
  }

  // Imagen (opcional)
  $nuevoNombreImagen = '';
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
            $nuevoNombreImagen = '';
          }
        }
      }
    }
  }

  // Insert
  if (!$errores) {
    $sql = "INSERT INTO `tbl_equipo`
            (`imagen`, `nombrecompleto`, `puesto`, `correo`, `linkedin`)
            VALUES
            (:imagen, :nombrecompleto, :puesto, :correo, :linkedin)";
    $st = $conexion->prepare($sql);
    $st->bindParam(":imagen",         $nuevoNombreImagen);
    $st->bindParam(":nombrecompleto", $nombrecompleto);
    $st->bindParam(":puesto",         $puesto);
    $st->bindParam(":correo",         $correo);
    $st->bindParam(":linkedin",       $linkedin);
    $st->execute();

    // Rotar token para la siguiente operación
    if (function_exists('ensure_csrf_token')) {
      $csrf_token = ensure_csrf_token(); // regenera
    } else {
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    header("Location: index.php?mensaje=" . urlencode("Registro agregado con éxito"));
    exit;
  }
}
?>

<?php include("../../templates/header.php"); ?>
<!-- SweetAlert2 (confirmación de CREAR) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<div class="card">
  <div class="card-header"><span style="font-weight:700; font-size:1.25rem;">Datos del personal</span></div>

  <div class="card-body">
    <?php if (!empty($errores)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errores as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data" autocomplete="off" novalidate class="js-confirm-create">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

      <div class="mb-3">
        <label for="imagen" class="form-label">Imagen (opcional)</label>
        <input type="file" class="form-control" name="imagen" id="imagen"
               accept=".jpg,.jpeg,.png,.webp,.gif">
        <div class="form-text">Máx. 3MB. Formatos: JPG, PNG, WEBP, GIF</div>
      </div>

      <div class="mb-3">
        <label for="nombrecompleto" class="form-label">Nombre completo</label>
        <input type="text" class="form-control" name="nombrecompleto" id="nombrecompleto"
               placeholder="Nombre" minlength="3" required
               value="<?= htmlspecialchars($_POST['nombrecompleto'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label for="puesto" class="form-label">Puesto</label>
        <input type="text" class="form-control" name="puesto" id="puesto" placeholder="Puesto"
               value="<?= htmlspecialchars($_POST['puesto'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label for="correo" class="form-label">Correo</label>
        <input type="email" class="form-control" name="correo" id="correo" placeholder="correo@dominio.com"
               value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label for="linkedin" class="form-label">LinkedIn (URL)</label>
        <input type="url" class="form-control" name="linkedin" id="linkedin"
               placeholder="https://www.linkedin.com/in/usuario"
               value="<?= htmlspecialchars($_POST['linkedin'] ?? '') ?>">
      </div>

      <!-- Botonera con iconos (coherente con otras secciones) -->
      <div class="d-flex align-items-center gap-2 mt-3">
        <button type="submit"
                class="btn btn-icon btn-outline-primary btn-submit-create"
                data-bs-toggle="tooltip" data-bs-placement="top"
                title="Agregar">
          <i class="fa-solid fa-paper-plane"></i>
          <span class="visually-hidden">Agregar</span>
        </button>

        <a href="index.php"
           class="btn btn-icon btn-outline-danger"
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
  // Tooltips si está Bootstrap
  if (window.bootstrap) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
  }

  // Confirmación al CREAR (Swal con fallback) + anti doble envío
  (function attachCreateConfirm(){
    const form = document.querySelector('form.js-confirm-create');
    if (!form || form.dataset.bound === '1') return;
    form.dataset.bound = '1';

    form.addEventListener('submit', async function(e){
      if (this.dataset.confirmed === '1') return;       // evita doble confirmación
      if (!this.checkValidity()) return;                 // primero validar HTML5
      e.preventDefault();

      const nombre = (this.querySelector('#nombrecompleto')?.value || 'este registro').trim();

      let ok = false;
      if (window.Swal) {
        const res = await Swal.fire({
          icon: 'question',
          title: 'Agregar miembro del equipo',
          html: `¿Deseas crear a <b>${nombre}</b> con la información ingresada?`,
          showCancelButton: true,
          confirmButtonText: 'Sí, crear',
          cancelButtonText: 'No, volver',
          reverseButtons: true,
          focusCancel: true,
          confirmButtonColor: '#0d6efd'
        });
        ok = res.isConfirmed;
      } else {
        ok = window.confirm('¿Crear el registro?');
      }

      if (!ok) return;

      // bloquear botón + spinner
      this.dataset.confirmed = '1';
      const btn = this.querySelector('.btn-submit-create');
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
      }

      if (window.Swal) {
        Swal.fire({title:'Guardando…', allowOutsideClick:false, allowEscapeKey:false, didOpen:()=>Swal.showLoading()});
      }

      this.submit();
    }, {passive:false});
  })();
</script>

<?php include("../../templates/footer.php"); ?>
