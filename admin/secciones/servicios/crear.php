<?php
// admin/secciones/servicios/crear.php

// 1) GUARDS: SIEMPRE antes de imprimir HTML
require_once __DIR__ . '/../../auth_guard.php';
require_role('admin'); // Solo administradores

// 2) DB
require_once __DIR__ . '/../../bd.php';

// 3) CSRF (la sesión ya está abierta por auth_guard)
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$errores = [];
$mensajeOk = '';

// Rutas destino
$IMG_DIR = __DIR__ . "/../../../assets/img/services";
$DOC_DIR = __DIR__ . "/../../../assets/docs/services";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // 3.1) CSRF
  if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errores[] = "Token CSRF inválido. Recarga la página e inténtalo de nuevo.";
  }

  $titulo      = isset($_POST['titulo']) ? trim($_POST['titulo']) : '';
  $descripcion = isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '';

  if ($titulo === '') {
    $errores[] = "El título es obligatorio.";
  }

  $iconoNombre = '';
  $pdfNombre   = null; // PDF opcional

  /* === Subida de ICONO (obligatorio) === */
  if (!empty($_FILES['icono']['name'])) {
    $file  = $_FILES['icono'];
    $tmp   = $file['tmp_name'];
    $name  = $file['name'];
    $size  = (int)$file['size'];
    $error = (int)$file['error'];

    if ($error !== UPLOAD_ERR_OK) {
      $errores[] = "Error al subir el ícono (código $error).";
    } elseif ($size > 2 * 1024 * 1024) { // 2 MB
      $errores[] = "El ícono supera el tamaño permitido (2 MB).";
    } else {
      $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
      $permitidas = ['png','jpg','jpeg','webp','svg'];
      if (!in_array($ext, $permitidas, true)) {
        $errores[] = "Formato de ícono no permitido. Usa PNG/JPG/WebP/SVG.";
      } else {
        if (!is_dir($IMG_DIR)) { @mkdir($IMG_DIR, 0775, true); }

        // Nombre seguro
        $base  = preg_replace('/[^a-z0-9_\-]/i', '_', pathinfo($name, PATHINFO_FILENAME));
        $nuevo = time() . "_" . $base . "." . $ext;
        $dest  = $IMG_DIR . "/" . $nuevo;

        if (!move_uploaded_file($tmp, $dest)) {
          $errores[] = "No se pudo guardar el ícono en el servidor.";
        } else {
          $iconoNombre = $nuevo;
        }
      }
    }
  } else {
    $errores[] = "Selecciona un archivo de imagen/ícono.";
  }

  /* === Subida de PDF (opcional) === */
  if (!empty($_FILES['archivo']['name'])) {
    $pdf  = $_FILES['archivo'];
    $tmp  = $pdf['tmp_name'];
    $name = $pdf['name'];
    $size = (int)$pdf['size'];
    $err  = (int)$pdf['error'];

    if ($err !== UPLOAD_ERR_OK) {
      $errores[] = "Error al subir el PDF (código $err).";
    } elseif ($size > 10 * 1024 * 1024) { // 10 MB
      $errores[] = "El PDF supera el tamaño permitido (10 MB).";
    } else {
      // Validación de tipo real con finfo
      $finfo = new finfo(FILEINFO_MIME_TYPE);
      $mime  = $finfo->file($tmp);
      if ($mime !== 'application/pdf') {
        $errores[] = "Solo se permite subir archivos PDF.";
      } else {
        if (!is_dir($DOC_DIR)) { @mkdir($DOC_DIR, 0775, true); }
        $safeBase = bin2hex(random_bytes(6));            // nombre único
        $pdfNombre = time() . "_" . $safeBase . ".pdf";  // ej: 1730412345_ab12cd.pdf
        $destPdf   = $DOC_DIR . "/" . $pdfNombre;

        if (!move_uploaded_file($tmp, $destPdf)) {
          $errores[] = "No se pudo guardar el PDF en el servidor.";
          $pdfNombre = null;
        }
      }
    }
  }

  /* === Insertar si todo OK === */
  if (empty($errores)) {
    $sql = "INSERT INTO `tbl_servicios` (`icono`,`titulo`,`descripcion`,`archivo`)
            VALUES (:icono, :titulo, :descripcion, :archivo)";
    $sentencia = $conexion->prepare($sql);
    $sentencia->bindParam(":icono", $iconoNombre);
    $sentencia->bindParam(":titulo", $titulo);
    $sentencia->bindParam(":descripcion", $descripcion);
    $sentencia->bindParam(":archivo", $pdfNombre);
    $sentencia->execute();

    // Renovar token para evitar reenvíos accidentales
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header("Location: index.php?mensaje=" . urlencode("Registro creado con éxito."));
    exit;
  }
}

// 4) Render
include("../../templates/header.php");
?>
<!-- SweetAlert2 SOLO para confirmación de CREAR -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<div class="card">
  <div class="card-header"><span style="font-weight:700; font-size:1.25rem;">Crear Servicios</span></div>
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

    <form action="" method="post" enctype="multipart/form-data" autocomplete="off" novalidate class="js-confirm-save">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

      <div class="mb-3">
        <label for="icono" class="form-label">Imagen / ícono (PNG, JPG, WebP, SVG) máx. 2MB</label>
        <input type="file" class="form-control" name="icono" id="icono"
               accept=".png,.jpg,.jpeg,.webp,.svg" required>
      </div>

      <div class="mb-3">
        <label for="titulo" class="form-label">Título:</label>
        <input type="text" class="form-control" name="titulo" id="titulo" required
               value="<?= htmlspecialchars($_POST['titulo'] ?? '') ?>">
      </div>

      <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción:</label>
        <input type="text" class="form-control" name="descripcion" id="descripcion"
               value="<?= htmlspecialchars($_POST['descripcion'] ?? '') ?>">
      </div>

      <!-- PDF opcional -->
      <div class="mb-3">
        <label for="archivo" class="form-label">Documento PDF (opcional) — máx. 10MB</label>
        <input type="file" class="form-control" name="archivo" id="archivo" accept="application/pdf">
      </div>

      <div class="d-flex align-items-center gap-2 mt-3">
        <!-- Agregar -->
        <button type="submit"
                class="btn btn-icon btn-outline-primary btn-submit-create"
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
  // Confirmación "Guardar/Crear" con SweetAlert2; fallback a confirm() si no carga
  (function attachSwalSave(){
    document.querySelectorAll('form.js-confirm-save').forEach(function(form){
      if (form.dataset.saveBound === '1') return;
      form.dataset.saveBound = '1';

      form.addEventListener('submit', async function(ev){
        // Evita doble confirmación si ya fue aprobada
        if (this.dataset.confirmed === '1') return;
        ev.preventDefault();

        // Mensaje dinámico con el título si existe
        const tituloInput = this.querySelector('#titulo');
        const nombre = tituloInput && tituloInput.value ? tituloInput.value : 'este registro';

        let ok = false;
        if (window.Swal) {
          const res = await Swal.fire({
            icon: 'question',
            title: 'Guardar cambios',
            html: `¿Estás seguro que quieres crear <b>${nombre}</b>?`,
            showCancelButton: true,
            confirmButtonText: 'Sí, crear',
            cancelButtonText: 'No, volver',
            reverseButtons: true,
            focusCancel: true
          });
          ok = res.isConfirmed;
        } else {
          ok = window.confirm('¿Crear el registro?');
        }

        if (!ok) return;

        // Marcar como confirmado y bloquear botón para evitar doble envío
        this.dataset.confirmed = '1';
        const btn = this.querySelector('.btn-submit-create');
        if (btn) {
          btn.disabled = true;
          btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i>';
        }
        this.submit();
      });
    });
  })();
</script>

<?php include("../../templates/footer.php"); ?>
