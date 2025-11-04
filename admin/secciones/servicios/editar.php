<?php
// admin/secciones/servicios/editar.php

// 1) Guards (antes de cualquier output)
require_once __DIR__ . '/../../auth_guard.php';
require_role(['admin','user']); // ambos pueden entrar a ver
$isAdmin = (current_role() === 'admin');

// 2) DB
require_once __DIR__ . '/../../bd.php';

// 3) Rutas coherentes (con "services")
$IMG_DIR = __DIR__ . "/../../../assets/img/services";
$IMG_URL = "../../../assets/img/services/";
$DOC_DIR = __DIR__ . "/../../../assets/docs/services";
$DOC_URL = "../../../assets/docs/services/";

// 4) CSRF (solo lo usaremos si es admin y va a POST)
if ($isAdmin && empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'] ?? '';

// ===== CARGAR REGISTRO =====
if (empty($_GET['txtID']) || !ctype_digit($_GET['txtID'])) {
  header("Location: index.php?mensaje=" . urlencode("ID inválido.")); exit;
}
$txtID = (int)$_GET['txtID'];

$st = $conexion->prepare("SELECT * FROM `tbl_servicios` WHERE id=:id LIMIT 1");
$st->bindParam(":id", $txtID, PDO::PARAM_INT);
$st->execute();
$registro = $st->fetch(PDO::FETCH_ASSOC);

if (!$registro) {
  header("Location: index.php?mensaje=" . urlencode("Servicio no encontrado.")); exit;
}

$icono       = $registro['icono'];
$titulo      = $registro['titulo'];
$descripcion = $registro['descripcion'];
$archivo     = $registro['archivo']; // PDF (puede ser null)

$mensaje = "";

// ===== ACTUALIZAR (solo admin) =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  if (!$isAdmin) {
    // Bloquea cualquier intento de POST de usuarios no-admin
    http_response_code(403);
    $mensaje = "Acceso denegado: solo lectura para usuarios.";
  } else {
    // CSRF
    if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
      $mensaje = "Token CSRF inválido. Recarga la página e inténtalo de nuevo.";
    } else {
      $txtID       = (int)($_POST['txtID'] ?? 0);
      $tituloNew   = trim($_POST['titulo'] ?? '');
      $descNew     = trim($_POST['descripcion'] ?? '');

      $iconoActual   = $icono;
      $archivoActual = $archivo;
      $nuevoIcono    = null;

      // === Imagen (opcional, 2MB) ===
      if (!empty($_FILES['icono']['name'])) {
        $f     = $_FILES['icono'];
        $tmp   = $f['tmp_name'];
        $name  = $f['name'];
        $size  = (int)$f['size'];
        $error = (int)$f['error'];

        if ($error !== UPLOAD_ERR_OK) {
          $mensaje = "Error al subir la imagen (código $error).";
        } elseif ($size > 2 * 1024 * 1024) {
          $mensaje = "La imagen supera el tamaño permitido (2 MB).";
        } else {
          $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
          $permitidas = ['png','jpg','jpeg','webp','svg'];
          if (!in_array($ext, $permitidas, true)) {
            $mensaje = "Formato de imagen no permitido. Usa PNG/JPG/WebP/SVG.";
          } else {
            if (!is_dir($IMG_DIR)) { @mkdir($IMG_DIR, 0775, true); }
            $base  = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($name, PATHINFO_FILENAME));
            $fileN = time() . "_" . $base . "." . $ext;
            $dest  = $IMG_DIR . "/" . $fileN;

            if (!move_uploaded_file($tmp, $dest)) {
              $mensaje = "No se pudo guardar la imagen en el servidor.";
            } else {
              $nuevoIcono = $fileN;
            }
          }
        }
      }

      // === PDF: eliminar o reemplazar (10MB) ===
      $eliminarPdf = isset($_POST['eliminar_archivo']) && $_POST['eliminar_archivo'] === '1';
      if (!$mensaje && $eliminarPdf && $archivoActual) {
        $old = $DOC_DIR . "/" . $archivoActual;
        if (is_file($old)) { @unlink($old); }
        $archivoActual = null;
      }

      if (!$mensaje && !empty($_FILES['archivo']['name'])) {
        $pdf  = $_FILES['archivo'];
        $tmp  = $pdf['tmp_name'];
        $name = $pdf['name'];
        $size = (int)$pdf['size'];
        $err  = (int)$pdf['error'];

        if ($err !== UPLOAD_ERR_OK) {
          $mensaje = "Error al subir el PDF (código $err).";
        } elseif ($size > 10 * 1024 * 1024) {
          $mensaje = "El PDF supera el tamaño permitido (10 MB).";
        } else {
          $finfo = new finfo(FILEINFO_MIME_TYPE);
          $mime  = $finfo->file($tmp);
          if ($mime !== 'application/pdf') {
            $mensaje = "Solo se permite subir archivos PDF.";
          } else {
            if (!is_dir($DOC_DIR)) { @mkdir($DOC_DIR, 0775, true); }
            $safeBase = bin2hex(random_bytes(6));
            $nuevoPdf = time() . "_" . $safeBase . ".pdf";
            $destPdf  = $DOC_DIR . "/" . $nuevoPdf;

            if (!move_uploaded_file($tmp, $destPdf)) {
              $mensaje = "No se pudo guardar el PDF en el servidor.";
            } else {
              // borrar anterior si existía
              if ($archivoActual) {
                $old = $DOC_DIR . "/" . $archivoActual;
                if (is_file($old)) { @unlink($old); }
              }
              $archivoActual = $nuevoPdf;
            }
          }
        }
      }

      // === Persistir si no hubo errores ===
      if (!$mensaje) {
        if ($nuevoIcono) {
          $oldPath = $IMG_DIR . "/" . $iconoActual;
          if ($iconoActual && is_file($oldPath)) { @unlink($oldPath); }
          $iconoActual = $nuevoIcono;
        }

        $up = $conexion->prepare(
          "UPDATE `tbl_servicios`
             SET icono=:icono, titulo=:titulo, descripcion=:descripcion, archivo=:archivo
           WHERE id=:id"
        );
        $up->bindParam(":icono", $iconoActual);
        $up->bindParam(":titulo", $tituloNew);
        $up->bindParam(":descripcion", $descNew);
        $up->bindParam(":archivo", $archivoActual);
        $up->bindParam(":id", $txtID, PDO::PARAM_INT);
        $up->execute();

        // Regenerar CSRF para siguiente post
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

        header("Location: index.php?mensaje=" . urlencode("Servicio actualizado con éxito."));
        exit;
      }

      // refrescar variables tras error
      $icono       = $iconoActual;
      $titulo      = $tituloNew;
      $descripcion = $descNew;
      $archivo     = $archivoActual;
    }
  }
}

// 5) Render
include("../../templates/header.php");
?>
<div class="card">
  <div class="card-header">
    <?= $isAdmin ? "Editar información de servicios" : "Detalle del servicio (solo lectura)" ?>
  </div>
  <div class="card-body">

    <?php if (!empty($mensaje)): ?>
      <div class="alert alert-<?= $isAdmin ? 'danger' : 'warning' ?> py-2"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data" class="js-save" <?= $isAdmin ? '' : 'onsubmit="return false;"' ?>>

      <?php if ($isAdmin): ?>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
      <?php endif; ?>

      <div class="mb-3">
        <label for="txtID" class="form-label">ID:</label>
        <input readonly value="<?= htmlspecialchars($txtID) ?>" class="form-control" name="txtID" id="txtID">
      </div>

      <!-- Imagen -->
      <div class="mb-3">
        <label class="form-label d-block">Imagen actual:</label>
        <?php if (!empty($icono)): ?>
          <img src="<?= $IMG_URL . htmlspecialchars($icono) ?>"
               alt="Icono actual"
               style="width:88px;height:88px;object-fit:contain;border:1px solid #e5e7eb;border-radius:8px;">
        <?php else: ?>
          <span class="text-muted">Sin imagen</span>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label for="icono" class="form-label">Nueva imagen (opcional):</label>
        <input type="file" class="form-control" name="icono" id="icono"
               accept=".png,.jpg,.jpeg,.webp,.svg" <?= $isAdmin ? '' : 'disabled' ?>>
        <small class="text-muted">Máximo 2 MB.</small>
      </div>

      <!-- PDF -->
      <div class="mb-2">
        <label class="form-label d-block">Documento PDF:</label>
        <?php if (!empty($archivo)): ?>
          <a class="btn btn-sm btn-outline-secondary"
             href="<?= $DOC_URL . htmlspecialchars($archivo) ?>" target="_blank" rel="noopener">
            Ver PDF actual
          </a>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" value="1" id="eliminar_archivo" name="eliminar_archivo"
                   <?= $isAdmin ? '' : 'disabled' ?>>
            <label class="form-check-label" for="eliminar_archivo">Eliminar PDF actual</label>
          </div>
        <?php else: ?>
          <span class="text-muted">Sin PDF adjunto</span>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label for="archivo" class="form-label">Reemplazar / subir nuevo PDF (opcional):</label>
        <input type="file" class="form-control" name="archivo" id="archivo"
               accept="application/pdf" <?= $isAdmin ? '' : 'disabled' ?>>
        <small class="text-muted">Máximo 10 MB.</small>
      </div>

      <!-- Texto -->
      <div class="mb-3">
        <label for="titulo" class="form-label">Título:</label>
        <input value="<?= htmlspecialchars($titulo) ?>" class="form-control" name="titulo" id="titulo"
               <?= $isAdmin ? '' : 'readonly' ?> required>
      </div>

      <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción:</label>
        <input value="<?= htmlspecialchars($descripcion) ?>" class="form-control" name="descripcion" id="descripcion"
               <?= $isAdmin ? '' : 'readonly' ?>>
      </div>

      <div class="d-flex align-items-center gap-2 mt-3">
        <?php if ($isAdmin): ?>
          <!-- Actualizar -->
          <button type="submit"
                  class="btn btn-icon btn-outline-primary"
                  data-bs-toggle="tooltip" data-bs-placement="top"
                  title="Actualizar">
            <i class="fa-solid fa-floppy-disk"></i>
          </button>
        <?php endif; ?>

        <!-- Regresar -->
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
  // Solo aplicar en la vista de editar y cuando el usuario es admin
  <?php if ($isAdmin): ?>
  const form = document.querySelector('form[action=""]'); // tu form de editar
  if (!form) return;

  // Por si usas tooltips en los iconos
  if (window.bootstrap) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => {
      new bootstrap.Tooltip(el);
    });
  }

  form.addEventListener('submit', function (e) {
    // Si hay errores de validación HTML5, deja que el navegador los muestre.
    if (!form.checkValidity()) return;

    e.preventDefault(); // detenemos envío por defecto

    // Mensaje contextual si va a eliminar el PDF
    const borrarPdf = document.getElementById('eliminar_archivo')?.checked;
    const msg = borrarPdf
      ? 'También se eliminará el PDF actual. ¿Deseas continuar?'
      : '¿Estás seguro que quieres guardar los cambios?';

    Swal.fire({
      icon: 'question',
      title: 'Guardar cambios',
      text: msg,
      showCancelButton: true,
      confirmButtonText: 'Sí, guardar',
      cancelButtonText: 'No, volver',
      confirmButtonColor: '#0d6efd', // azul vivo
      cancelButtonColor: '#6c757d'
    }).then((result) => {
      if (result.isConfirmed) {
        // feedback mientras envía
        Swal.fire({
          title: 'Guardando…',
          allowOutsideClick: false,
          allowEscapeKey: false,
          didOpen: () => Swal.showLoading()
        });
        form.submit(); // ahora sí enviamos
      }
    });
  }, { passive: false });
  <?php endif; ?>
})();
</script>
<?php if ($isAdmin): ?>
<script> AdminUX.attachSaveConfirm('.js-save'); </script>
<?php endif; ?>
<?php include("../../templates/footer.php"); ?>


