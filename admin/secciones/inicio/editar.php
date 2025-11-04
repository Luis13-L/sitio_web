<?php
// admin/secciones/inicio/editar.php

require_once __DIR__ . '/../../auth_guard.php';
require_role(['admin']); // solo admin edita

require_once __DIR__ . '/../../bd.php';

// === CSRF ===
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$mensaje = "";

/* ========== CARGAR REGISTRO ========== */
if (!isset($_GET['txtID']) || !ctype_digit($_GET['txtID'])) {
  header("Location: index.php?mensaje=" . urlencode("ID inválido.")); exit;
}
$txtID = (int)$_GET['txtID'];

$st = $conexion->prepare("SELECT id, componente, imagen FROM tbl_inicioo WHERE id = :id LIMIT 1");
$st->bindParam(":id", $txtID, PDO::PARAM_INT);
$st->execute();
$registro = $st->fetch(PDO::FETCH_ASSOC);

if (!$registro) {
  header("Location: index.php?mensaje=" . urlencode("Registro no encontrado.")); exit;
}

$componente = $registro['componente'];
$imagen     = $registro['imagen'];

/* ========== ACTUALIZAR ========== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF
  if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $mensaje = "Token CSRF inválido. Recarga la página e inténtalo nuevamente.";
  } else {
    $txtID      = (int)($_POST['txtID'] ?? 0);
    $componente = trim($_POST['componente'] ?? $componente); // readonly en UI, pero resguardamos
    $oldImagen  = $imagen;
    $newImagen  = null;

    // 1) Actualizar componente (por coherencia)
    $up = $conexion->prepare("UPDATE tbl_inicioo SET componente = :componente WHERE id = :id");
    $up->bindParam(":componente", $componente);
    $up->bindParam(":id", $txtID, PDO::PARAM_INT);
    $up->execute();

    // 2) Si suben nueva imagen
    if (!empty($_FILES["imagen"]["name"])) {
      $file  = $_FILES["imagen"];
      $tmp   = $file["tmp_name"];
      $name  = $file["name"];
      $size  = (int)$file["size"];
      $error = (int)$file["error"];

      if ($error !== UPLOAD_ERR_OK) {
        $mensaje = "Error al subir el archivo (código $error).";
      } elseif ($size > 3 * 1024 * 1024) {
        $mensaje = "El archivo supera el tamaño permitido (3 MB).";
      } else {
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $permitidas = ["png","jpg","jpeg","webp","svg"];
        if (!in_array($ext, $permitidas, true)) {
          $mensaje = "Formato no permitido. Usa PNG/JPG/WEBP/SVG.";
        } else {
          $destDir = __DIR__ . "/../../../assets/img";
          if (!is_dir($destDir)) { @mkdir($destDir, 0775, true); }

          $base    = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($name, PATHINFO_FILENAME));
          $newName = time() . "_" . $base . "." . $ext;
          $dest    = $destDir . "/" . $newName;

          if (!move_uploaded_file($tmp, $dest)) {
            $mensaje = "No se pudo guardar el archivo en el servidor.";
          } else {
            $newImagen = $newName;
          }
        }
      }

      // 3) Persistir nueva imagen y borrar la anterior
      if (!$mensaje && $newImagen) {
        $up2 = $conexion->prepare("UPDATE tbl_inicioo SET imagen = :img WHERE id = :id");
        $up2->bindParam(":img", $newImagen);
        $up2->bindParam(":id", $txtID, PDO::PARAM_INT);
        $up2->execute();

        if ($oldImagen && $oldImagen !== $newImagen) {
          $oldPath = __DIR__ . "/../../../assets/img/" . $oldImagen;
          if (is_file($oldPath)) { @unlink($oldPath); }
        }
        $imagen = $newImagen; // para reflejar en el preview si hay error menor
      }
    }

    if (!$mensaje) {
      // renovar token para evitar doble-submit
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
      header("Location: index.php?mensaje=" . urlencode("Registro modificado con éxito")); exit;
    }
  }
}

include("../../templates/header.php");
?>

<div class="card">
  <div class="card-header"><span style="font-weight:700; font-size:1.1rem;">Editar Portada / Logo</span></div>
  <div class="card-body">

    <?php if (!empty($mensaje)): ?>
      <div class="alert alert-danger py-2"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <form action="" method="post" enctype="multipart/form-data" id="form-editar-inicio">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

      <div class="mb-3">
        <label for="txtID" class="form-label">ID</label>
        <input type="text" class="form-control" readonly name="txtID" id="txtID"
               value="<?= htmlspecialchars($txtID) ?>">
      </div>

      <div class="mb-3">
        <label for="componente" class="form-label">Componente</label>
        <input type="text" class="form-control" readonly
               value="<?= htmlspecialchars($componente) ?>"
               name="componente" id="componente">
      </div>

      <div class="mb-3">
        <label class="form-label d-block">Imagen actual</label>
        <?php if (!empty($imagen)): ?>
          <img src="../../../assets/img/<?= htmlspecialchars($imagen) ?>" alt="Imagen actual"
               style="width:120px;height:120px;object-fit:contain;border-radius:8px;border:1px solid #e5e7eb;">
        <?php else: ?>
          <span class="text-muted">Sin imagen</span>
        <?php endif; ?>
      </div>

      <div class="mb-3">
        <label for="imagen" class="form-label">Nueva imagen (opcional)</label>
        <input type="file" class="form-control" name="imagen" id="imagen"
               accept=".png,.jpg,.jpeg,.webp,.svg">
        <small class="text-muted">Deja vacío para mantener la imagen actual. Máx. 3 MB.</small>
      </div>

      <div class="d-flex align-items-center gap-2 mt-3">
        <!-- Guardar -->
        <button type="submit"
                class="btn btn-icon btn-outline-primary"
                data-bs-toggle="tooltip" data-bs-placement="top"
                title="Guardar">
          <i class="fa-solid fa-floppy-disk"></i>
        </button>

        <!-- Volver -->
        <a href="index.php"
           class="btn btn-icon btn-outline-danger"
           data-bs-toggle="tooltip" data-bs-placement="top"
           title="Volver">
          <i class="fa-solid fa-arrow-left"></i>
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

// Confirmación SweetAlert al guardar
(function(){
  const form = document.getElementById('form-editar-inicio');
  if (!form) return;

  form.addEventListener('submit', function(e){
    // permitir validaciones HTML5 primero
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
