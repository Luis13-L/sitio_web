<?php
// admin/secciones/usuarios/index.php

// 1) Guards y setup (ANTES de imprimir HTML)
require_once __DIR__ . "/../../auth_guard.php";
require_role(['admin']); // solo admin
require_once __DIR__ . "/../../bd.php";

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// CSRF para acciones (eliminar)
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

/* ======= ELIMINAR (POST + CSRF) ======= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
  $error = '';

  // 1) CSRF
  if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $error = "Token CSRF inválido.";
  }

  // 2) ID válido
  $id = $_POST['id'] ?? '';
  if (!$error && (!ctype_digit((string)$id) || (int)$id <= 0)) {
    $error = "ID inválido.";
  }

  // 3) Evitar borrarte a ti mismo
  if (!$error && (int)$id === (int)($_SESSION['user_id'] ?? 0)) {
    $error = "No puedes eliminar tu propio usuario.";
  }

  if ($error) {
    header("Location: index.php?error=" . urlencode($error));
    exit;
  }

  $del = $conexion->prepare("DELETE FROM tbl_usuarios WHERE id = :id");
  $del->bindParam(":id", $id, PDO::PARAM_INT);
  $del->execute();

  // Rotar token para evitar reenvíos
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
  header("Location: index.php?mensaje=" . urlencode("Usuario eliminado."));
  exit;
}

/* ======= LISTAR ======= */
$st = $conexion->prepare("
  SELECT id, usuario, correo, rol, is_active, created_at
  FROM tbl_usuarios
  ORDER BY id ASC
");
$st->execute();
$usuarios = $st->fetchAll(PDO::FETCH_ASSOC);

$mensaje = $_GET['mensaje'] ?? '';
$error   = $_GET['error'] ?? '';

// 2) Header (después de toda la lógica anterior)
include("../../templates/header.php");
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span style="font-weight:700; font-size:1.25rem;">Usuarios</span>
    <a class="btn btn-primary" href="crear.php">Agregar registro</a>
  </div>

  <div class="card-body">
    <?php if ($mensaje): ?>
      <div class="alert alert-success py-2 mb-3"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
      <div class="alert alert-danger py-2 mb-3"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Correo</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Creado</th>
            <th class="icon-col" style="width:150px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><?= (int)$u['id'] ?></td>
            <td><strong><?= htmlspecialchars($u['usuario']) ?></strong></td>
            <td><?= htmlspecialchars($u['correo']) ?></td>
            <td>
              <?php if ($u['rol'] === 'admin'): ?>
                <span class="badge bg-primary">admin</span>
              <?php else: ?>
                <span class="badge bg-secondary">user</span>
              <?php endif; ?>
            </td>
            <td>
              <?php if ((int)$u['is_active'] === 1): ?>
                <span class="badge bg-success">Activo</span>
              <?php else: ?>
                <span class="badge bg-danger">Inactivo</span>
              <?php endif; ?>
            </td>
            <td><?= $u['created_at'] ? htmlspecialchars($u['created_at']) : '—' ?></td>

            <td class="cell-center">
              <div class="action-group">
                <!-- Editar -->
                <a class="btn btn-brand-outline btn-icon"
                   href="editar.php?txtID=<?= (int)$u['id'] ?>"
                   data-bs-toggle="tooltip" data-bs-placement="top" title="Editar">
                  <i class="fa-solid fa-user-pen"></i>
                </a>

                <!-- Eliminar por POST + CSRF -->
                <form method="post" class="d-inline js-delete">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <button type="submit"
                          class="btn btn-danger btn-icon"
                          data-bs-toggle="tooltip" data-bs-placement="top"
                          title="<?= ((int)$u['id'] === (int)($_SESSION['user_id'] ?? 0)) ? 'No puedes eliminar tu usuario' : 'Eliminar' ?>"
                          <?= ((int)$u['id'] === (int)($_SESSION['user_id'] ?? 0)) ? 'disabled' : '' ?>>
                    <i class="fa-solid fa-user-xmark"></i>
                  </button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (!$usuarios): ?>
          <tr>
            <td colspan="7" class="text-center text-muted">No hay usuarios registrados.</td>
          </tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Activa confirm de borrado y tooltips -->
<script>
  AdminUX.attachDeleteConfirms(); // SweetAlert en formularios .js-delete
</script>

<?php include("../../templates/footer.php"); ?>
