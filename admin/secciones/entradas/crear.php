<?php
// admin/secciones/historia/crear.php

// ==== GUARDS Y DEPENDENCIAS (antes de imprimir HTML) ====
require_once __DIR__ . '/../../auth_guard.php';
require_role('admin');                 // solo administradores
require_once __DIR__ . '/../../bd.php';

// CSRF (auth_guard ya abrió la sesión)
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Rutas
$IMG_DIR = __DIR__ . "/../../../assets/img/about";
$IMG_URL = "../../../assets/img/about/";

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // 1) CSRF
  if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errores[] = "Token CSRF inválido. Recarga la página e inténtalo de nuevo.";
  }

  // 2) Inputs
  $fecha       = trim($_POST['fecha']       ?? '');
  $titulo      = trim($_POST['titulo']      ?? '');
  $descripcion = trim($_POST['descripcion'] ?? '');

  // 3) Validaciones básicas
  if ($fecha === '')  { $errores[] = "La fecha es obligatoria."; }
  if ($titulo === '') { $errores[] = "El título es obligatorio."; }
  if (strlen($titulo) > 255) { $errores[] = "El título no debe exceder 255 caracteres."; }

  // 4) Insert preliminar si no hay errores (sin imagen aún)
  if (!$errores) {
    $st = $conexion->prepare("
      INSERT INTO tbl_entradas (fecha, titulo, descripcion, imagen)
      VALUES (:fecha, :titulo, :descripcion, NULL)
    ");
    $st->bindParam(":fecha",       $fecha);
    $st->bindParam(":titulo",      $titulo);
    $st->bindParam(":descripcion", $descripcion);
    $st->execute();

    $newId = (int)$conexion->lastInsertId();

    // 5) Imagen opcional — valida tamaño y MIME real
    if (!empty($_FILES['imagen']['name'])) {
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
            $up = $conexion->prepare("UPDATE tbl_entradas SET imagen = :img WHERE id = :id");
            $up->bindParam(":img", $fname);
            $up->bindParam(":id",  $newId, PDO::PARAM_INT);
            $up->execute();
          }
        }
      }
    }

    if (!$errores) {
      // rotar CSRF y redirigir
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
      header("Location: index.php?mensaje=" . urlencode("Registro agregado con éxito"));
      exit;
    }
    // Si hubo error en la imagen, puedes hacer rollback si lo prefieres:
    // $conexion->prepare("DELETE FROM tbl_entradas WHERE id = :id")->execute([':id' => $newId]);
  }
}

// ==== A partir de aquí imprimimos HTML ====
include("../../templates/header.php");
?>
<div class="card">
  <div class="card-header"><span style="font-weight:700; font-size:1.25rem;">Nueva entrada (Historia / Timeline)</span></div>

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

    <form action="" method="post" enctype="multipart/form-data" autocomplete="off" novalidate id="form-crear-entrada">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

      <div class="row g-3">
        <div class="col-md-4">
          <label for="fecha" class="form-label">Fecha</label>
          <input type="date" class="form-control" name="fecha" id="fecha"
                 value="<?= htmlspecialchars($_POST['fecha'] ?? '') ?>" required>
        </div>

        <div class="col-md-8">
          <label for="titulo" class="form-label">Título</label>
          <input type="text" class="form-control" name="titulo" id="titulo"
                 value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>"
                 placeholder="Título" required maxlength="255">
        </div>

        <div class="col-12">
          <label for="descripcion" class="form-label">Descripción</label>
          <textarea class="form-control" name="descripcion" id="descripcion" rows="3"
                    placeholder="Descripción"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
        </div>

        <div class="col-md-6">
          <label for="imagen" class="form-label">Imagen (opcional, máx. 2 MB)</label>
          <input type="file" class="form-control" name="imagen" id="imagen"
                 accept=".jpg,.jpeg,.png,.webp,.gif">
          <div class="form-text">Permitidos: JPG, PNG, WEBP, GIF. Tamaño máx: 2 MB.</div>
        </div>
      </div>

      <!-- Botones con íconos -->
      <div class="d-flex align-items-center gap-2 mt-4">
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
(function () {
  const form = document.getElementById('form-crear-entrada');
  if (!form) return;

  // Tooltips en iconos
  if (window.bootstrap) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      new bootstrap.Tooltip(el);
    });
  }

  form.addEventListener('submit', function (e) {
    if (!form.checkValidity()) return; // deja que HTML5 muestre errores
    e.preventDefault();

    Swal.fire({
      icon: 'question',
      title: 'Crear entrada',
      text: '¿Estás seguro que quieres guardar este registro?',
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


