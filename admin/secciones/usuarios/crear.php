<?php
// admin/secciones/usuarios/crear.php

// 1) GUARDS
require_once __DIR__ . '/../../auth_guard.php';
require_role(['admin']); // solo administradores

// 2) DB
require_once __DIR__ . '/../../bd.php';

// 3) CSRF (ya hay sesión por auth_guard)
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // 3.1) CSRF
  if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errores[] = "Token CSRF inválido. Recarga la página e inténtalo de nuevo.";
  }

  // 3.2) Inputs
  $usuario  = trim($_POST['usuario'] ?? '');
  $password = (string)($_POST['password'] ?? '');
  $correo   = trim($_POST['correo'] ?? '');
  $rol      = $_POST['rol'] ?? 'user';

  // 3.3) Validaciones
  if ($usuario === '' || strlen($usuario) < 3) {
    $errores[] = "El usuario es obligatorio (mínimo 3 caracteres).";
  }
  if ($password === '' || strlen($password) < 8) {
    $errores[] = "La contraseña es obligatoria (mínimo 8 caracteres).";
  }
  if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "Correo inválido.";
  }
  if (!in_array($rol, ['admin','user'], true)) {
    $errores[] = "Rol inválido.";
  }

  // 3.4) Duplicados
  if (!$errores) {
    $st = $conexion->prepare("SELECT 1 FROM tbl_usuarios WHERE usuario = :u OR correo = :c LIMIT 1");
    $st->execute([':u' => $usuario, ':c' => $correo]);
    if ($st->fetch()) {
      $errores[] = "El usuario o correo ya existe.";
    }
  }

  // 3.5) Insert
  if (!$errores) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO tbl_usuarios (usuario, correo, password_hash, rol, is_active)
            VALUES (:usuario, :correo, :password_hash, :rol, 1)";
    $ins = $conexion->prepare($sql);
    $ins->execute([
      ':usuario'       => $usuario,
      ':correo'        => $correo,
      ':password_hash' => $hash,
      ':rol'           => $rol
    ]);

    // Rotar CSRF para evitar doble submit
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    header("Location: index.php?mensaje=" . urlencode("Usuario creado con éxito"));
    exit;
  }
}

include __DIR__ . "/../../templates/header.php";
?>

<div class="card">
  <div class="card-header"><span class="panel-title">Crear usuario</span></div>

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

    <form action="" method="post" autocomplete="off" novalidate id="form-create-user">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label fw-bold">Nombre de usuario</label>
          <input type="text" class="form-control" name="usuario" id="usuario"
                 placeholder="Nombre del usuario" minlength="3" required
                 value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-bold">Correo</label>
          <input type="email" class="form-control" name="correo" id="correo"
                 placeholder="correo@dominio.com" required
                 value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label fw-bold">Contraseña</label>
          <div class="input-group">
            <input type="password" class="form-control" name="password" id="password"
                   placeholder="Mínimo 8 caracteres" minlength="8" required>
            <button class="btn btn-outline-secondary" type="button" id="togglePwd" title="Mostrar/ocultar">
              <i class="fa-regular fa-eye"></i>
            </button>
          </div>
          <div class="form-text">Usa mayúsculas, minúsculas, números y símbolos.</div>
        </div>

        <div class="col-md-6">
          <label class="form-label fw-bold">Rol</label>
          <select class="form-select" name="rol" id="rol" required>
            <option value="user"  <?= (($_POST['rol'] ?? '') === 'user')  ? 'selected' : '' ?>>Usuario</option>
            <option value="admin" <?= (($_POST['rol'] ?? '') === 'admin') ? 'selected' : '' ?>>Administrador</option>
          </select>
        </div>
      </div>

      <div class="d-flex align-items-center gap-2 mt-4">
        <!-- Agregar -->
        <button type="submit"
                class="btn btn-icon btn-brand-outline"
                data-bs-toggle="tooltip" data-bs-placement="top"
                title="Crear usuario">
          <i class="fa-solid fa-user-plus"></i>
          <span class="visually-hidden">Crear</span>
        </button>

        <!-- Cancelar -->
        <a class="btn btn-icon btn-outline-danger"
           href="index.php"
           data-bs-toggle="tooltip" data-bs-placement="top"
           title="Cancelar y volver">
          <i class="fa-solid fa-arrow-left"></i>
          <span class="visually-hidden">Cancelar</span>
        </a>
      </div>
    </form>
  </div>

  <div class="card-footer text-muted"></div>
</div>

<script>
(() => {
  // Tooltips (si Bootstrap está cargado en el header)
  if (window.bootstrap) {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(el => new bootstrap.Tooltip(el));
  }

  // Mostrar/ocultar contraseña
  const toggleBtn = document.getElementById('togglePwd');
  const pwdInput  = document.getElementById('password');
  if (toggleBtn && pwdInput) {
    toggleBtn.addEventListener('click', () => {
      const show = pwdInput.type === 'password';
      pwdInput.type = show ? 'text' : 'password';
      toggleBtn.querySelector('i').className = show ? 'fa-regular fa-eye-slash' : 'fa-regular fa-eye';
    });
  }

  // Confirmación SweetAlert antes de crear
  const form = document.getElementById('form-create-user');
  if (!form) return;

  form.addEventListener('submit', function(e) {
    if (!form.checkValidity()) return; // deja validación nativa
    e.preventDefault();

    const rolSel = document.getElementById('rol')?.value || 'user';
    const msg = (rolSel === 'admin')
      ? 'Se creará un usuario con rol ADMIN. ¿Deseas continuar?'
      : '¿Deseas crear este usuario?';

    Swal.fire({
      icon: 'question',
      title: 'Crear usuario',
      text: msg,
      showCancelButton: true,
      confirmButtonText: 'Sí, crear',
      cancelButtonText: 'No, volver',
      confirmButtonColor: '#0d6efd',
      cancelButtonColor: '#6c757d'
    }).then((r) => {
      if (r.isConfirmed) {
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

<?php include __DIR__ . "/../../templates/footer.php"; ?>
