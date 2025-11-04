<?php
// admin/secciones/inicio/crear.php

require_once __DIR__ . '/../../auth_guard.php';
require_role(['admin']); // Solo administradores

require_once __DIR__ . '/../../bd.php';

// Helpers
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function nombre_unico(string $ext): string {
  return 'img_'.bin2hex(random_bytes(4)).'_'.time().'.'.$ext;
}

// CSRF (auth_guard ya abrió sesión)
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$errores = [];
$okMsg   = '';
$componente_post = $_POST['componente'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // 1) CSRF
  if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errores[] = "Token CSRF inválido. Recarga la página e inténtalo de nuevo.";
  }

  // 2) Inputs
  $componente = isset($_POST['componente']) ? strtolower(trim($_POST['componente'])) : '';
  $f = $_FILES['imagen'] ?? null;

  if ($componente === '' || !in_array($componente, ['logo','hero'], true)) {
    $errores[] = "Selecciona un componente válido (logo o portada/hero).";
  }
  if (!$f || empty($f['name'])) {
    $errores[] = "Selecciona un archivo de imagen.";
  }

  // 3) Validación de archivo
  $nombre = '';
  if (!$errores && $f) {
    if ((int)$f['error'] !== UPLOAD_ERR_OK) {
      $errores[] = "Error de subida (código {$f['error']}).";
    } else {
      // Límite de tamaño: 3MB (puedes ajustar)
      if ((int)$f['size'] > 3 * 1024 * 1024) {
        $errores[] = "La imagen supera el tamaño permitido (3 MB).";
      } else {
        // MIME real
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);
        $ext = match($mime){
          'image/jpeg' => 'jpg',
          'image/png'  => 'png',
          'image/webp' => 'webp',
          'image/svg+xml' => 'svg', // opcional: permitir SVG si quieres
          default => null
        };
        if (!$ext) {
          $errores[] = "Formato no permitido. Usa JPG/PNG/WebP" . (true ? "/SVG" : "") . ".";
        } else {
          // Mover a destino
          $destDir = __DIR__."/../../../assets/img";
          if (!is_dir($destDir)) { @mkdir($destDir, 0775, true); }

          $nombre = nombre_unico($ext);
          $dest   = rtrim($destDir,'/').'/'.$nombre;

          if (!move_uploaded_file($f['tmp_name'], $dest)) {
            $errores[] = "No se pudo guardar la imagen en el servidor.";
          }
        }
      }
    }
  }

  // 4) Insert
  if (!$errores) {
    $sql = "INSERT INTO `tbl_inicioo` (`componente`, `imagen`) VALUES (:componente, :imagen)";
    $st  = $conexion->prepare($sql);
    $st->bindParam(":componente", $componente);
    $st->bindParam(":imagen", $nombre);
    $st->execute();

    // Renovar CSRF (evita doble submit con back/refresh)
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header("Location: index.php?mensaje=" . urlencode("Registro agregado con éxito"));
    exit;
  }
}

// Header después de la lógica
include("../../templates/header.php");
?>

<div class="card">
  <div class="card-header">
    <span style="font-weight:700; font-size:1.1rem;">Agregar Portada / Logo</span>
  </div>

  <div class="card-body">
    <?php if (!empty($errores)): ?>
      <div class="alert alert-danger py-2 mb-3">
        <ul class="mb-0">
          <?php foreach ($errores as $e): ?>
            <li><?= h($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="" enctype="multipart/form-data" method="post" id="form-crear-inicio" autocomplete="off" novalidate>
      <input type="hidden" name="csrf_token" value="<?= h($csrf_token) ?>">

      <div class="mb-3">
        <label for="componente" class="form-label">Componente:</label>
        <select class="form-select" name="componente" id="componente" required>
          <option value="">Selecciona…</option>
          <option value="logo" <?= ($componente_post==='logo' ? 'selected' : '') ?>>Logo</option>
          <option value="hero" <?= ($componente_post==='hero' ? 'selected' : '') ?>>Portada (Hero)</option>
        </select>
      </div>

      <div class="mb-3">
        <label for="imagen" class="form-label">Imagen:</label>
        <input
          type="file"
          class="form-control"
          name="imagen"
          id="imagen"
          accept="image/jpeg,image/png,image/webp,image/svg+xml"
          required
        >
        <small class="text-muted d-block mt-1">
          JPG/PNG/WebP<?= ' o SVG' ?>. Máx. 3&nbsp;MB. Para **Portada (Hero)** sube imágenes anchas (≥1600px).
        </small>
      </div>

      <div class="d-flex align-items-center gap-2 mt-3">
        <!-- Agregar -->
        <button type="submit"
                class="btn btn-icon btn-outline-primary"
                data-bs-toggle="tooltip" data-bs-placement="top"
                title="Agregar">
          <i class="fa-solid fa-paper-plane"></i>
          <span class="visually-hidden">Agregar</span>
        </button>

        <!-- Cancelar -->
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
// Tooltips
if (window.bootstrap) {
  document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
}

// Confirmación SweetAlert al enviar
(function(){
  const form = document.getElementById('form-crear-inicio');
  if (!form) return;

  form.addEventListener('submit', function(e){
    if (!form.checkValidity()) return; // deja que el navegador muestre validación HTML5
    e.preventDefault();

    Swal.fire({
      icon: 'question',
      title: 'Crear registro',
      text: '¿Deseas agregar este componente?',
      showCancelButton: true,
      confirmButtonText: 'Sí, agregar',
      cancelButtonText: 'No, volver',
      confirmButtonColor: '#0d6efd',
      cancelButtonColor: '#6c757d'
    }).then((result) => {
      if (result.isConfirmed) {
        Swal.fire({
          title: 'Guardando…',
          allowOutsideClick: false,
          allowEscapeKey: false,
          didOpen: () => Swal.showLoading()
        });
        form.submit();
      }
    });
  }, { passive: false });
})();
</script>

<?php include("../../templates/footer.php"); ?>
