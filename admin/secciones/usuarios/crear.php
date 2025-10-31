

<?php
include("../../bd.php");
include("../../templates/header.php");

// Asegura que solo admin cree usuarios:
// crear.php (está en admin/secciones/usuarios)
require_once __DIR__ . '/../../auth_guard.php';
require_role(['admin']); // Solo admins pueden crear usuarios


// CSRF token
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

$errores = [];
$mensaje_ok = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

  // 1) CSRF
  if (empty($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    $errores[] = "Token CSRF inválido. Recarga la página e inténtalo de nuevo.";
  }

  // 2) Inputs
  $usuario = trim($_POST['usuario'] ?? '');
  $password = $_POST['password'] ?? '';
  $correo = trim($_POST['correo'] ?? '');
  $rol = $_POST['rol'] ?? 'user'; // 'admin' o 'user'

  // 3) Validaciones
  if ($usuario === '' || strlen($usuario) < 3) {
    $errores[] = "El usuario es obligatorio (mínimo 3 caracteres).";
  }
  if ($password === '' || strlen($password) < 8) {
    $errores[] = "La contraseña es obligatoria (mínimo 8 caracteres).";
  }
  if (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
    $errores[] = "Correo inválido.";
  }
  if (!in_array($rol, ['admin', 'user'], true)) {
    $errores[] = "Rol inválido.";
  }

  // 4) Revisar duplicados
  if (!$errores) {
    $st = $conexion->prepare("SELECT 1 FROM tbl_usuarios WHERE usuario = :u OR correo = :c LIMIT 1");
    $st->bindParam(":u", $usuario);
    $st->bindParam(":c", $correo);
    $st->execute();
    if ($st->fetch()) {
      $errores[] = "El usuario o correo ya existe.";
    }
  }

  // 5) Insertar con password_hash
  if (!$errores) {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO tbl_usuarios (usuario, correo, password_hash, rol, is_active)
            VALUES (:usuario, :correo, :password_hash, :rol, 1)";
    $ins = $conexion->prepare($sql);
    $ins->bindParam(":usuario", $usuario);
    $ins->bindParam(":correo", $correo);
    $ins->bindParam(":password_hash", $hash);
    $ins->bindParam(":rol", $rol);
    $ins->execute();

    // Regenerar token CSRF para siguiente formulario
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

    $mensaje = "Usuario creado con éxito";
    header("Location: index.php?mensaje=" . urlencode($mensaje));
    exit;
  }
}
?>

<div class="card">
  <div class="card-header">
    Crear usuario
  </div>
  <div class="card-body">
    <?php if (!empty($errores)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0">
          <?php foreach ($errores as $e): ?>
            <li><?= htmlspecialchars($e) ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form action="" method="post" autocomplete="off" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombre de usuario</label>
          <input type="text" class="form-control" name="usuario" id="usuario"
                 placeholder="Nombre del usuario" minlength="3" required
                 value="<?= htmlspecialchars($_POST['usuario'] ?? '') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label">Correo</label>
          <input type="email" class="form-control" name="correo" id="correo"
                 placeholder="correo@dominio.com" required
                 value="<?= htmlspecialchars($_POST['correo'] ?? '') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label">Contraseña</label>
          <input type="password" class="form-control" name="password" id="password"
                 placeholder="Mínimo 8 caracteres" minlength="8" required>
          <div class="form-text">Usa mayúsculas, minúsculas, números y símbolos.</div>
        </div>

        <div class="col-md-6">
          <label class="form-label">Rol</label>
          <select class="form-select" name="rol" id="rol" required>
            <option value="user"  <?= (($_POST['rol'] ?? '') === 'user')  ? 'selected' : '' ?>>Usuario</option>
            <option value="admin" <?= (($_POST['rol'] ?? '') === 'admin') ? 'selected' : '' ?>>Administrador</option>
          </select>
        </div>
      </div>

      <div class="mt-4">
        <button type="submit" class="btn btn-success">Agregar</button>
        <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
      </div>
    </form>
  </div>

  <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
