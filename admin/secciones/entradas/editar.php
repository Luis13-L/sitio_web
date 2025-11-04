<?php
// admin/secciones/historia/editar.php

// ==== GUARDS Y DEPENDENCIAS (antes de imprimir HTML) ====
require_once __DIR__ . '/../../auth_guard.php';
require_role('admin');                 // solo administradores
require_once __DIR__ . '/../../bd.php';

// CSRF
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Rutas
$IMG_DIR = __DIR__ . "/../../../assets/img/about";
$IMG_URL = "../../../assets/img/about/";

$errores = [];
$exito   = "";

/* ===== CARGA INICIAL ===== */
$txtID = '';
$fecha = $titulo = $descripcion = $imagen = '';

if (!isset($_GET['txtID']) || !ctype_digit($_GET['txtID'])) {
  header("Location: index.php?error=" . urlencode("ID inválido"));
  exit;
}
$txtID = (int)$_GET['txtID'];

$st = $conexion->prepare("SELECT id, fecha, titulo, descripcion, imagen FROM tbl_entradas WHERE id = :id LIMIT 1");
$st->bindParam(":id", $txtID, PDO::PARAM_INT);
$st->execute();
$reg = $st->fetch(PDO::FETCH_ASSOC);
if (!$reg) {
  header("Location: index.php?error=" . urlencode("Registro no encontrado"));
  exit;
}

$fecha       = $reg['fecha'] ?? '';
$titulo      = $reg['titulo'] ?? '';
$descripcion = $reg['descripcion'] ?? '';
$imagen      = $reg['imagen'] ?? '';

/* ===== ACTUALIZAR ===== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // 1) CSRF
  if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errores[] = "Token CSRF inválido. Recarga la página e inténtalo de nuevo.";
  }

  // 2) Inputs
  $txtID       = isset($_POST['txtID']) ? (int)$_POST['txtID'] : 0;
  $fechaNew    = trim($_POST['fecha'] ?? '');
  $tituloNew   = trim($_POST['titulo'] ?? '');
  $descNew     = trim($_POST['descripcion'] ?? '');

  // 3) Validaciones básicas
  if ($fechaNew === '')   { $errores[] = "La fecha es obligatoria."; }
  if ($tituloNew === '')  { $errores[] = "El título es obligatorio."; }
  if (strlen($tituloNew) > 255) { $errores[] = "El título no debe exceder 255 caracteres."; }

  // 4) Actualizar campos de texto si no hay errores hasta aquí
  if (!$errores) {
    $up = $conexion->prepare("
      UPDATE tbl_entradas
      SET fecha = :fecha, titulo = :titulo, descripcion = :descripcion
      WHERE id = :id
    ");
    $up->bindParam(":fecha",       $fechaNew);
    $up->bindParam(":titulo",      $tituloNew);
    $up->bindParam(":descripcion", $descNew);
    $up->bindParam(":id",          $txtID, PDO::PARAM_INT);
    $up->execute();

    // actualizar variables mostradas si luego hay error de imagen
    $fecha       = $fechaNew;
    $titulo      = $tituloNew;
    $descripcion = $descNew;
  }

  // 5) Imagen (opcional) — valida tamaño y MIME real
  if (!$errores && !empty($_FILES['imagen']['name'])) {
    $file  = $_FILES['imagen'];
    $tmp   = $file['tmp_name'] ?? '';
    $name  = $file['name'] ?? '';
    $size  = (int)($file['size'] ?? 0);
    $err   = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);

    if ($err !== UPLOAD_ERR_OK) {
      $errores[] = "Error al subir la imagen (código $err).";
    } elseif ($size > 2 * 1024 * 1024) { // 2 MB
      $errores[] = "La imagen supera el tamaño permitido (2 MB).";
    } else {
      // MIME real
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mime  = $finfo->file($tmp);
      $permitidos = ['image/jpeg','image/png','image/webp','image/gif'];
      if (!in_array($mime, $permitidos, true)) {
        $errores[] = "Formato no permitido. Usa JPG, PNG, WEBP o GIF.";
      } else {
        if (!is_dir($IMG_DIR)) { @mkdir($IMG_DIR, 0775, true); }

        // nombre seguro
        $extByMime = [
          'image/jpeg' => 'jpg',
          'image/png'  => 'png',
          'image/webp' => 'webp',
          'image/gif'  => 'gif',
        ];
        $ext   = $extByMime[$mime] ?? 'bin';
        $safe  = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($name, PATHINFO_FILENAME));
        $fname = time() . "_" . bin2hex(random_bytes(4)) . "_" . $safe . "." . $ext;
        $dest  = $IMG_DIR . "/" . $fname;

        if (!move_uploaded_file($tmp, $dest)) {
          $errores[] = "No se pudo guardar la imagen en el servidor.";
        } else {
          // borrar imagen anterior si existe
          if ($imagen) {
            $old = $IMG_DIR . "/" . $imagen;
            if (is_file($old)) { @unlink($old); }
          }
          // guardar nueva en DB
          $up2 = $conexion->prepare("UPDATE tbl_entradas SET imagen = :img WHERE id = :id");
          $up2->bindParam(":img", $fname);
          $up2->bindParam(":id",  $txtID, PDO::PARAM_INT);
          $up2->execute();

          $imagen = $fname;
        }
      }
    }
  }

  if (!$errores) {
    // rotar CSRF para evitar reenvíos con F5
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    header("Location: index.php?mensaje=" . urlencode("Registro actualizado con éxito"));
    exit;
  }
}

// ==== A partir de aquí puedes imprimir HTML ====
include("../../templates/header.php");
?>
<div class="card">
  <div class="card-header">
    Editar entrada (Historia / Timeline)
  </div>
  <div class="card-body">

    <?php if ($errores): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errores as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
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
        <div class="col-md-4">
          <label for="fecha" class="form-label">Fecha</label>
          <input type="date" class="form-control" name="fecha" id="fecha"
                 value="<?= htmlspecialchars($fecha) ?>" required>
        </div>

        <div class="col-md-8">
          <label for="titulo" class="form-label">Título</label>
          <input type="text" class="form-control" name="titulo" id="titulo"
                 value="<?= htmlspecialchars($titulo) ?>" placeholder="Título" required maxlength="255">
        </div>

        <div class="col-12">
          <label for="descripcion" class="form-label">Descripción</label>
          <textarea class="form-control" name="descripcion" id="descripcion" rows="4"
                    placeholder="Descripción"><?= htmlspecialchars($descripcion) ?></textarea>
        </div>

        <div class="col-md-6">
          <label for="imagen" class="form-label">Imagen (opcional, máx. 2 MB)</label>
          <input type="file" class="form-control" name="imagen" id="imagen"
                 accept=".jpg,.jpeg,.png,.webp,.gif">
          <div class="form-text">Permitidos: JPG, PNG, WEBP, GIF. Tamaño máx: 2 MB.</div>
        </div>

        <div class="col-md-6 d-flex align-items-end">
          <?php if ($imagen): ?>
            <div class="d-flex align-items-center gap-3">
              <img src="<?= $IMG_URL . htmlspecialchars($imagen) ?>"
                   alt="Imagen actual"
                   style="width:120px;height:120px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
              <small class="text-muted">Vista previa</small>
            </div>
          <?php else: ?>
            <span class="text-muted">Sin imagen</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Botones como íconos -->
      <div class="d-flex align-items-center gap-2 mt-4">
        <!-- Guardar -->
        <button type="submit"
                class="btn btn-icon btn-outline-primary"
                data-bs-toggle="tooltip" data-bs-placement="top"
                title="Guardar cambios">
          <i class="fa-solid fa-floppy-disk"></i>
          <span class="visually-hidden">Guardar</span>
        </button>

        <!-- Regresar -->
        <a href="index.php"
           class="btn btn-icon btn-outline-danger"
           data-bs-toggle="tooltip" data-bs-placement="top"
           title="Regresar">
          <i class="fa-solid fa-arrow-left"></i>
          <span class="visually-hidden">Regresar</span>
        </a>
      </div>
    </form>
  </div>
  <div class="card-footer text-muted"></div>
</div>

<script>
(function () {
  const form = document.querySelector('form[action=""]');
  if (!form) return;

  // Tooltips en iconos
  if (window.bootstrap) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      new bootstrap.Tooltip(el);
    });
  }

  form.addEventListener('submit', function (e) {
    if (!form.checkValidity()) return; // valida HTML5 primero
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


