<?php
// admin/secciones/usuarios/editar.php

// 1) Guards: solo ADMIN puede editar (ajusta si quieres permitir self-edit)
require_once __DIR__ . '/../../auth_guard.php';
require_role(['admin']);

require_once __DIR__ . "/../../bd.php";

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// CSRF
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$errores = [];

// ====== CARGAR REGISTRO ======
if (!isset($_GET['txtID']) || !ctype_digit($_GET['txtID'])) {
  header("Location: index.php?mensaje=" . urlencode("ID inválido"));
  exit;
}
$txtID = (int)$_GET['txtID'];

$st = $conexion->prepare("
  SELECT id, usuario, correo, rol, is_active
  FROM tbl_usuarios
  WHERE id = :id
  LIMIT 1
");
$st->bindParam(":id", $txtID, PDO::PARAM_INT);
$st->execute();
$u = $st->fetch(PDO::FETCH_ASSOC);

if (!$u) {
  header("Location: index.php?mensaje=" . urlencode("Usuario no encontrado"));
  exit;
}

// Variables para el form
$usuario   = $u['usuario'];
$correo    = $u['correo'];
$rol       = $u['rol'];
$is_active = (int)$u['is_active'];

// ====== ACTUALIZAR ======
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // 1) CSRF
  if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errores[] = "Token CSRF inválido. Recarga la página e inténtalo de nuevo.";
  }

  // 2) Inputs
  $usuario   = trim($_POST['usuario'] ?? '');
  $correo    = trim($_POST['correo'] ?? '');
  $rol       = $_POST['rol'] ?? 'user';
  $is_active = isset($_POST['is_active']) ? 1 : 0;

  $password_new = $_POST['password_new'] ?? '';
  $password_cnf = $_POST['password_cnf'] ?? '';

  // 3) Validaciones
  if ($usuario === '' || strlen($usuario) < 3) {
    $errores[] = "El usuario es obligatorio (mínimo 3 caracteres).";
  }
  if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "Correo inválido.";
  }
  if (!in_array($rol, ['admin','user'], true)) {
    $errores[] = "Rol inválido.";
  }
  if ($password_new !== '') {
    if (strlen($password_new) < 8) { $errores[] = "La nueva contraseña debe tener al menos 8 caracteres."; }
    if ($password_new !== $password_cnf) { $errores[] = "Las contraseñas no coinciden."; }
  }

  // 4) Unicidad (usuario/correo) excluyendo el propio ID
  if (!$errores) {
    $chk = $conexion->prepare("
      SELECT 1 FROM tbl_usuarios
      WHERE (usuario = :u OR correo = :c) AND id <> :id
      LIMIT 1
    ");
    $chk->bindParam(":u", $usuario);
    $chk->bindParam(":c", $correo);
    $chk->bindParam(":id", $txtID, PDO::PARAM_INT);
    $chk->execute();
    if ($chk->fetch()) {
      $errores[] = "El usuario o correo ya está en uso por otro registro.";
    }
  }

  // 5) Actualizar
  if (!$errores) {
    $sql = "
      UPDATE tbl_usuarios
      SET usuario = :usuario,
          correo  = :correo,
          rol     = :rol,
          is_active = :is_active
    ";
    if ($password_new !== '') { $sql .= ", password_hash = :password_hash"; }
    $sql .= " WHERE id = :id";

    $up = $conexion->prepare($sql);
    $up->bindParam(":usuario", $usuario);
    $up->bindParam(":correo",  $correo);
    $up->bindParam(":rol",     $rol);
    $up->bindParam(":is_active", $is_active, PDO::PARAM_INT);
    $up->bindParam(":id", $txtID, PDO::PARAM_INT);
    if ($password_new !== '') {
      $hash = password_hash($password_new, PASSWORD_DEFAULT);
      $up->bindParam(":password_hash", $hash);
    }
    $up->execute();

    // Rotar CSRF
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header("Location: index.php?mensaje=" . urlencode("Usuario actualizado con éxito"));
    exit;
  }
}

include("../../templates/header.php");
?>

<div class="card">
  <div class="card-header"><span class="panel-title">Editar usuario</span></div>

  <div class="card-body">
    <?php if (!empty($errores)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errores as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="" method="post" autocomplete="off" novalidate id="form-edit-user">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

      <div class="mb-3">
        <label class="form-label fw-bold">ID</label>
        <input type="text" class="form-control" value="<?= (int)$txtID ?>" readonly>
      </div>

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-bold">Nombre de usuario</label>
          <input type="text" class="form-control" name="usuario" id="usuario"
                 minlength="3" required value="<?= htmlspecialchars($usuario) ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-bold">Correo</label>
          <input type="email" class="form-control" name="correo" id="correo"
                 required value="<?= htmlspecialchars($correo) ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-bold">Rol</label>
          <select class="form-select" name="rol" id="rol" required>
            <option value="user"  <?= ($rol === 'user')  ? 'selected' : '' ?>>Usuario</option>
            <option value="admin" <?= ($rol === 'admin') ? 'selected' : '' ?>>Administrador</option>
          </select>
        </div>

        <div class="col-md-6 d-flex align-items-end">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" <?= $is_active ? 'checked' : '' ?>>
            <label class="form-check-label fw-bold" for="is_active">Activo</label>
          </div>
        </div>
      </div>

      <hr class="my-4">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-bold">Nueva contraseña (opcional)</label>
          <div class="input-group">
            <input type="password" class="form-control" name="password_new" id="password_new"
                   placeholder="Déjalo vacío para no cambiarla" minlength="8">
            <button class="btn btn-outline-secondary" type="button" id="togglePwd1" title="Mostrar/ocultar">
              <i class="fa-regular fa-eye"></i>
            </button>
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label fw-bold">Confirmar nueva contraseña</label>
          <div class="input-group">
            <input type="password" class="form-control" name="password_cnf" id="password_cnf" minlength="8">
            <button class="btn btn-outline-secondary" type="button" id="togglePwd2" title="Mostrar/ocultar">
              <i class="fa-regular fa-eye"></i>
            </button>
          </div>
        </div>
      </div>

      <div class="form-text mt-2">Si no completas la contraseña, se mantendrá la actual.</div>

      <div class="d-flex align-items-center gap-2 mt-4">
        <!-- Guardar -->
        <button type="submit"
                class="btn btn-icon btn-outline-primary"
                data-bs-toggle="tooltip" data-bs-placement="top"
                title="Actualizar">
          <i class="fa-solid fa-floppy-disk"></i>
        </button>

        <!-- Volver -->
        <a class="btn btn-icon btn-outline-danger"
           href="index.php"
           data-bs-toggle="tooltip" data-bs-placement="top"
           title="Cancelar y volver">
          <i class="fa-solid fa-arrow-left"></i>
        </a>
      </div>
    </form>
  </div>

  <div class="card-footer text-muted"></div>
</div>

<script>
(() => {
  // Tooltips (si Bootstrap está cargado)
  if (window.bootstrap) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
  }

  // Mostrar/ocultar contraseña
  const toggle = (btnId, inputId) => {
    const btn = document.getElementById(btnId);
    const inp = document.getElementById(inputId);
    if (!btn || !inp) return;
    btn.addEventListener('click', () => {
      const isPwd = inp.type === 'password';
      inp.type = isPwd ? 'text' : 'password';
      btn.querySelector('i').className = isPwd ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    });
  };
  toggle('togglePwd1','password_new');
  toggle('togglePwd2','password_cnf');

  // Confirmación al guardar (SweetAlert)
  const form = document.getElementById('form-edit-user');
  if (!form) return;

  form.addEventListener('submit', function (e) {
    if (!form.checkValidity()) return; // deja que HTML5 valide
    e.preventDefault();

    const cambiarPwd = (document.getElementById('password_new')?.value || '').length > 0;
    const msg = cambiarPwd
      ? 'Se actualizará la información del usuario y su contraseña. ¿Deseas continuar?'
      : '¿Estás seguro que quieres guardar los cambios del usuario?';

    Swal.fire({
      icon: 'question',
      title: 'Guardar cambios',
      text: msg,
      showCancelButton: true,
      confirmButtonText: 'Sí, guardar',
      cancelButtonText: 'No, volver',
      confirmButtonColor: '#0d6efd',
      cancelButtonColor: '#6c757d'
    }).then((r) => {
      if (r.isConfirmed) {
        Swal.fire({ title:'Guardando…', allowOutsideClick:false, allowEscapeKey:false, didOpen: () => Swal.showLoading() });
        form.submit();
      }
    });
  }, { passive: false });
})();
</script>

<?php include("../../templates/footer.php"); ?>
