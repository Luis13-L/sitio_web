<?php
// admin/secciones/historia/index.php

/* ==== GUARDS ==== */
require_once __DIR__ . '/../../auth_guard.php';
require_role(['admin','user']);
$isAdmin = (current_role() === 'admin');

require_once __DIR__ . '/../../bd.php';

/* ==== CSRF (para acciones POST) ==== */
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

/* ==== ELIMINAR: solo admin, POST + CSRF ==== */
if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  // 1) CSRF
  if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    header("Location: index.php?error=" . urlencode("Token CSRF inválido."));
    exit;
  }

  // 2) ID válido
  $id = $_POST['id'] ?? '';
  if (!ctype_digit((string)$id) || (int)$id <= 0) {
    header("Location: index.php?error=" . urlencode("ID inválido."));
    exit;
  }
  $txtID = (int)$id;

  // 3) traer filename y borrar
  $st = $conexion->prepare("SELECT imagen FROM `tbl_entradas` WHERE id = :id");
  $st->bindParam(":id", $txtID, PDO::PARAM_INT);
  $st->execute();
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if ($row) {
    $file = __DIR__ . "/../../../assets/img/about/" . $row['imagen'];
    if (is_file($file)) { @unlink($file); }

    $del = $conexion->prepare("DELETE FROM `tbl_entradas` WHERE id = :id");
    $del->bindParam(":id", $txtID, PDO::PARAM_INT);
    $del->execute();

    // Rotar token para evitar reenvíos
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header("Location: index.php?mensaje=" . urlencode("Registro eliminado."));
    exit;
  } else {
    header("Location: index.php?error=" . urlencode("Registro no encontrado."));
    exit;
  }
}

/* ==== LISTAR ==== */
$st = $conexion->prepare("SELECT * FROM `tbl_entradas` ORDER BY id DESC");
$st->execute();
$lista_entradas = $st->fetchAll(PDO::FETCH_ASSOC);

$mensaje = $_GET['mensaje'] ?? '';
$error   = $_GET['error'] ?? '';

include("../../templates/header.php");
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span style="font-weight:700; font-size:1.25rem;">Entradas (Historia / Timeline)</span>

    <?php if ($isAdmin): ?>
      <a class="btn btn-primary" href="crear.php" role="button">Agregar registro</a>
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
            <th scope="col" style="width:70px;">ID</th>
            <th scope="col" style="width:140px;">Fecha</th>
            <th scope="col" style="min-width:220px;">Título</th>
            <th scope="col">Descripción</th>
            <th scope="col" style="width:130px;">Imagen</th>
            <th scope="col" class="icon-col" style="width:<?= $isAdmin ? '160px' : '80px' ?>;">Acción</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($lista_entradas as $reg): ?>
          <?php
            $id    = (int)$reg['ID'];
            $fecha = htmlspecialchars($reg['fecha'] ?? '');
            $tit   = htmlspecialchars($reg['titulo'] ?? '');
            $desc  = htmlspecialchars($reg['descripcion'] ?? '');
            $img   = htmlspecialchars($reg['imagen'] ?? '');
          ?>
          <tr>
            <td><?= $id ?></td>
            <td><?= $fecha ?></td>
            <td class="fw-semibold"><?= $tit ?></td>
            <td class="text-wrap" style="max-width:520px;"><?= nl2br($desc) ?></td>

            <td>
              <?php if ($img): ?>
                <img
                  src="../../../assets/img/about/<?= $img ?>"
                  alt="<?= $tit ?>"
                  style="width:88px;height:88px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
              <?php else: ?>
                <span class="text-muted">Sin imagen</span>
              <?php endif; ?>
            </td>

            <td class="cell-center">
              <?php if ($isAdmin): ?>
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
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (!$lista_entradas): ?>
          <tr><td colspan="6" class="text-center text-muted">No hay registros.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include("../../templates/footer.php"); ?>
