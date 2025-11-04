<?php
// admin/secciones/equipo/index.php

require_once __DIR__ . '/../../auth_guard.php';
require_role(['admin','user']);                 // ambos ven el listado
$isAdmin = (current_role() === 'admin');        // solo admin modifica

require_once __DIR__ . '/../../bd.php';

/* CSRF para acciones (usa el helper si lo tienes, si no, fallback) */
if (function_exists('ensure_csrf_token')) {
  $csrf_token = ensure_csrf_token();
} else {
  if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  }
  $csrf_token = $_SESSION['csrf_token'];
}

/* ===== ELIMINAR (solo admin, POST + CSRF) ===== */
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  $error = '';

  if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $error = "Token CSRF inválido.";
  }

  $id = $_POST['id'] ?? '';
  if (!$error && (!ctype_digit((string)$id) || (int)$id <= 0)) {
    $error = "ID inválido.";
  }

  if (!$error) {
    // obtener filename para borrar imagen
    $st = $conexion->prepare("SELECT imagen FROM tbl_equipo WHERE id = :id");
    $st->bindParam(":id", $id, PDO::PARAM_INT);
    $st->execute();
    $row = $st->fetch(PDO::FETCH_ASSOC);

    if ($row) {
      $file = __DIR__ . "/../../../assets/img/team/" . $row['imagen'];
      if (is_file($file)) { @unlink($file); }

      $del = $conexion->prepare("DELETE FROM tbl_equipo WHERE id = :id");
      $del->bindParam(":id", $id, PDO::PARAM_INT);
      $del->execute();

      // Regenera CSRF para la próxima acción
      $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

      header("Location: index.php?mensaje=" . urlencode("Registro eliminado."));
      exit;
    } else {
      $error = "Registro no encontrado.";
    }
  }

  header("Location: index.php?error=" . urlencode($error));
  exit;
}

/* ===== LISTAR ===== */
$st = $conexion->prepare("SELECT * FROM tbl_equipo ORDER BY id DESC");
$st->execute();
$lista_equipo = $st->fetchAll(PDO::FETCH_ASSOC);

$mensaje = $_GET['mensaje'] ?? '';
$error   = $_GET['error'] ?? '';

include("../../templates/header.php");
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span style="font-weight:700; font-size:1.25rem;">Equipo</span>
    <?php if ($isAdmin): ?>
      <a class="btn btn-primary" href="crear.php">Agregar registro</a>
    <?php else: ?>
      <button class="btn btn-primary" type="button" disabled title="Solo administradores pueden agregar">
        Agregar registro
      </button>
    <?php endif; ?>
  </div>

  <div class="card-body">
    <?php if (!$isAdmin): ?>
      <div class="alert alert-info py-2 mb-3">
        Modo solo lectura. Si necesitas editar, contacta a un administrador.
      </div>
    <?php endif; ?>
    <?php if ($mensaje): ?>
      <div class="alert alert-success py-2 mb-3"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger py-2 mb-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="table-responsive-sm">
      <table class="table align-middle">
        <thead>
          <tr>
            <th style="width:70px;">ID</th>
            <th style="width:130px;">Imagen</th>
            <th style="min-width:260px;">Nombre y puesto</th>
            <th style="min-width:260px;">Contacto</th>
            <?php if ($isAdmin): ?>
              <th class="icon-col" style="width:160px;">Acciones</th>
            <?php endif; ?>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_equipo as $reg): ?>
            <?php
              $id     = (int)($reg['ID'] ?? $reg['id']);
              $img    = htmlspecialchars($reg['imagen'] ?? '', ENT_QUOTES, 'UTF-8');
              $nom    = htmlspecialchars($reg['nombrecompleto'] ?? '', ENT_QUOTES, 'UTF-8');
              $puesto = htmlspecialchars($reg['puesto'] ?? '', ENT_QUOTES, 'UTF-8');
              $mail   = trim($reg['correo'] ?? '');
              $link   = trim($reg['linkedin'] ?? '');
            ?>
            <tr>
              <td><?= $id ?></td>

              <td>
                <?php if ($img): ?>
                  <img src="../../../assets/img/team/<?= $img ?>"
                       alt="<?= $nom ?>"
                       style="width:88px;height:88px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
                <?php else: ?>
                  <span class="text-muted">Sin imagen</span>
                <?php endif; ?>
              </td>

              <td>
                <div class="fw-semibold"><?= $nom ?></div>
                <div class="text-muted small"><?= $puesto ?></div>
              </td>

              <td>
                <?php if ($mail): ?>
                  <div class="small">
                    <i class="fa-solid fa-envelope me-1"></i>
                    <a href="mailto:<?= htmlspecialchars($mail) ?>"><?= htmlspecialchars($mail) ?></a>
                  </div>
                <?php endif; ?>
                <?php if ($link): ?>
                  <div class="small mt-1">
                    <i class="fab fa-linkedin-in me-1"></i>
                    <a href="<?= htmlspecialchars($link) ?>" target="_blank" rel="noopener noreferrer">
                      <?= htmlspecialchars($link) ?>
                    </a>
                  </div>
                <?php endif; ?>
                <?php if (!$mail && !$link): ?>
                  <span class="text-muted small">Sin datos</span>
                <?php endif; ?>
              </td>

              <?php if ($isAdmin): ?>
                <td class="cell-center">
                  <div class="action-group">
                    <a class="btn btn-brand-outline btn-icon"
                       href="editar.php?txtID=<?= $id ?>" title="Editar">
                      <i class="fa-solid fa-pen"></i>
                    </a>

                    <form method="post" class="d-inline"
                          onsubmit="return confirm('¿Eliminar este registro? También se borrará la imagen.');">
                      <input type="hidden" name="action" value="delete">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                      <input type="hidden" name="id" value="<?= $id ?>">
                      <button type="submit" class="btn btn-danger btn-icon" title="Eliminar">
                        <i class="fa-solid fa-trash"></i>
                      </button>
                    </form>
                  </div>
                </td>
              <?php endif; ?>
            </tr>
          <?php endforeach; ?>

          <?php if (!$lista_equipo): ?>
            <tr>
              <td colspan="<?= $isAdmin ? 5 : 4 ?>" class="text-center text-muted">No hay registros.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include("../../templates/footer.php"); ?>
