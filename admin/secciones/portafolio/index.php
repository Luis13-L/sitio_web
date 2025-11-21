<?php
// admin/secciones/portafolio/index.php

require_once __DIR__ . "/../../auth_guard.php";
require_login();                               // listado accesible a quien esté logueado
require_once __DIR__ . "/../../bd.php";

/* Rutas (carpeta portfolio) */
$IMG_DIR = __DIR__ . "/../../../assets/img/portfolio";
$IMG_URL = "../../../assets/img/portfolio/";

/* Permisos por acción (según tu auth_guard) */
$canView   = can_access('portafolio', 'view');
$canCreate = can_access('portafolio', 'create');
$canEdit   = can_access('portafolio', 'edit');
$canDelete = can_access('portafolio', 'delete');

/* CSRF */
$csrf_token = ensure_csrf_token();

/* ===== ELIMINAR: POST + CSRF, solo si tiene permiso ===== */
if ($canDelete && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  verify_csrf_or_die($_POST['csrf_token'] ?? null);

  $id = $_POST['id'] ?? '';
  if (!ctype_digit((string)$id) || (int)$id <= 0) {
    header("Location: index.php?error=" . urlencode("ID inválido."));
    exit;
  }
  $txtID = (int)$id;

  // traer filename
  $st = $conexion->prepare("SELECT imagen FROM tbl_portafolio WHERE id = :id");
  $st->bindParam(":id", $txtID, PDO::PARAM_INT);
  $st->execute();
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if ($row) {
    if (!empty($row['imagen'])) {
      $imgPath = $IMG_DIR . "/" . $row['imagen'];
      if (is_file($imgPath)) { @unlink($imgPath); }
    }
    $del = $conexion->prepare("DELETE FROM tbl_portafolio WHERE id = :id");
    $del->bindParam(":id", $txtID, PDO::PARAM_INT);
    $del->execute();

    header("Location: index.php?mensaje=" . urlencode("Registro eliminado."));
    exit;
  } else {
    header("Location: index.php?error=" . urlencode("Registro no encontrado."));
    exit;
  }
}

/* ---- LISTAR ---- */
$st = $conexion->prepare("SELECT * FROM `tbl_portafolio` ORDER BY id DESC");
$st->execute();
$lista_portafolio = $st->fetchAll(PDO::FETCH_ASSOC);

$mensaje = $_GET['mensaje'] ?? '';
$error   = $_GET['error'] ?? '';

include("../../templates/header.php");
?>

<!-- SweetAlert2 solo para confirmación de ELIMINAR -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span style="font-weight:700; font-size:1.25rem;">Noticias</span>

    <?php if ($canCreate): ?>
      <a class="btn btn-primary" href="crear.php" role="button">
        <i class="fa-solid fa-plus me-1"></i> Agregar registro
      </a>
    <?php endif; ?>
  </div>

  <div class="card-body">
    <?php if ($mensaje): ?>
      <div class="alert alert-success py-2 mb-3"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger py-2 mb-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if (!$canCreate && !$canEdit && !$canDelete): ?>
      <div class="alert alert-info py-2 mb-3">
        Modo solo lectura para tu usuario.
      </div>
    <?php endif; ?>

    <div class="table-responsive-sm">
      <table class="table align-middle">
        <thead>
          <tr>
            <th scope="col" style="width:70px;">ID</th>
            <th scope="col" style="min-width:260px;">Texto</th>
            <th scope="col" style="width:130px;">Imagen</th>
            <th scope="col">Descripción</th>
            <th scope="col" style="width:160px;">Categoría</th>
            <?php if ($canEdit || $canDelete): ?>
              <th scope="col" class="icon-col" style="width:160px;">Acción</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($lista_portafolio as $reg): ?>
          <?php
            $id   = (int)($reg['ID'] ?? $reg['id'] ?? 0);
            $tit  = htmlspecialchars($reg['titulo'] ?? '');
            $sub  = htmlspecialchars($reg['subtitulo'] ?? '');
            $url  = trim($reg['url'] ?? '');
            $img  = htmlspecialchars($reg['imagen'] ?? '');
            $cat  = htmlspecialchars($reg['categoria'] ?? '');
            $desc = htmlspecialchars($reg['descripcion'] ?? '');
          ?>
          <tr>
            <td><?= $id ?></td>

            <td>
              <h6 class="mb-1 fw-semibold"><?= $tit ?></h6>
              <?php if ($sub): ?>
                <div class="text-muted small mb-1"><?= $sub ?></div>
              <?php endif; ?>
              <?php if ($url): ?>
                <a class="small" href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener noreferrer">
                  <?= htmlspecialchars($url) ?>
                </a>
              <?php endif; ?>
            </td>

            <td>
              <?php if (!empty($img)): ?>
                <img
                  src="<?= $IMG_URL . $img ?>"
                  alt="<?= $tit ?>"
                  style="width:88px;height:88px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
              <?php else: ?>
                <span class="text-muted">Sin imagen</span>
              <?php endif; ?>
            </td>

            <td class="text-wrap" style="max-width:520px;">
              <?= nl2br($desc) ?>
            </td>

            <td><?= $cat ?></td>

            <?php if ($canEdit || $canDelete): ?>
            <td class="cell-center">
              <div class="action-group">
                <?php if ($canEdit): ?>
                  <a class="btn btn-brand-outline btn-icon"
                     href="editar.php?txtID=<?= $id ?>" title="Editar">
                    <i class="fa-solid fa-pen"></i>
                  </a>
                <?php endif; ?>

                <?php if ($canDelete): ?>
                  <!-- Eliminación con Swal: añadimos data-item con el título -->
                  <form method="post" class="d-inline js-delete" data-item="<?= $tit ?>">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                    <input type="hidden" name="id" value="<?= $id ?>">
                    <button type="submit" class="btn btn-danger btn-icon" title="Eliminar">
                      <i class="fa-solid fa-trash"></i>
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>

        <?php if (!$lista_portafolio): ?>
          <tr><td colspan="<?= ($canEdit || $canDelete) ? 6 : 5 ?>" class="text-center text-muted">No hay registros.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Confirmación SweetAlert2 SOLO para eliminar -->
<script>
  (function attachSwalDelete(){
    document.querySelectorAll('form.js-delete').forEach(function(form){
      if (form.dataset.confirmBound === '1') return;
      form.dataset.confirmBound = '1';

      form.addEventListener('submit', async function(ev){
        ev.preventDefault();
        const titulo = this.dataset.item || 'este registro';

        const r = await Swal.fire({
          icon: 'warning',
          title: 'Eliminar noticia',
          html: `¿Seguro que deseas eliminar <b>${titulo}</b>?<br><small>También se borrará la imagen asociada.</small>`,
          showCancelButton: true,
          confirmButtonText: 'Sí, eliminar',
          cancelButtonText: 'Cancelar',
          reverseButtons: true,
          focusCancel: true,
          confirmButtonColor: '#d33'
        });

        if (!r.isConfirmed) return;

        const btn = this.querySelector('button[type="submit"]');
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
