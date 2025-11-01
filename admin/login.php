<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/bd.php';

    $usuario  = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    // 1) Trae solo por usuario; NO uses password en el WHERE
    $sql = "SELECT id, usuario, correo, /* quita 'rol' si no existe */
                   password_hash
            FROM tbl_usuarios
            WHERE usuario = :usuario
            LIMIT 1";
    $st = $conexion->prepare($sql);
    $st->execute([':usuario' => $usuario]);
    $u = $st->fetch(PDO::FETCH_ASSOC);

    // 2) Verifica el hash
    $ok = $u && !empty($u['password_hash']) && password_verify($password, $u['password_hash']);

    if ($ok) {
        session_regenerate_id(true);
        $_SESSION['user_id']  = (int)$u['id'];
        $_SESSION['usuario']  = $u['usuario'];
        $_SESSION['logueado'] = true;           // añade $_SESSION['rol'] si tienes columna 'rol'
        header('Location: index.php');
        exit;
    } else {
        $mensaje = "Usuario o contraseña son incorrectos";
    }
}
?>


<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Login</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<main class="container">
  <div class="row justify-content-center">
    <div class="col-12 col-sm-8 col-md-5">
      <div class="py-5"></div>
      <?php if (!empty($mensaje)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <strong><?= htmlspecialchars($mensaje) ?></strong>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      <div class="card shadow-sm">
        <div class="card-header">Login</div>
        <div class="card-body">
          <form method="post" autocomplete="off">
            <div class="mb-3">
              <label for="usuario" class="form-label">Usuario</label>
              <input type="text" class="form-control" id="usuario" name="usuario" required>
            </div>
            <div class="mb-3">
              <label for="password" class="form-label">Contraseña</label>
              <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button class="btn btn-primary w-100" type="submit">Entrar</button>
          </form>
        </div>
      </div>
      <div class="py-4"></div>
    </div>
  </div>
</main>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
