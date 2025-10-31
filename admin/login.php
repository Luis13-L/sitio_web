<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require __DIR__ . '/bd.php';

    $usuario  = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    // Trae usuario por nombre. Incluye password_hash y, si existe, el campo legacy `password`.
    $sql = "SELECT id, usuario, correo, rol,
                   password_hash,
                   /* si no tienes esta columna, quítala: */
                   password AS legacy_password
            FROM tbl_usuarios
            WHERE usuario = :usuario
            LIMIT 1";
    $st = $conexion->prepare($sql);
    $st->execute([':usuario' => $usuario]);
    $u = $st->fetch(PDO::FETCH_ASSOC);

    $ok = false;

    if ($u) {
        // 1) Caso moderno: password_hash lleno
        if (!empty($u['password_hash']) && password_verify($password, $u['password_hash'])) {
            $ok = true;

            // Rehash si cambia el algoritmo por defecto (opcional)
        } elseif (!empty($u['legacy_password']) && hash_equals($u['legacy_password'], $password)) {
            // 2) Caso legado: coincidió con la columna antigua en texto plano.
            // Migra a hash seguro inmediatamente:
            $nuevoHash = password_hash($password, PASSWORD_DEFAULT);
            $up = $conexion->prepare("UPDATE tbl_usuarios
                                      SET password_hash = :h
                                      WHERE id = :id");
            $up->execute([':h' => $nuevoHash, ':id' => $u['id']]);

            $ok = true;
        }
    }

    if ($ok) {
        session_regenerate_id(true);
        $_SESSION['user_id']  = (int)$u['id'];
        $_SESSION['usuario']  = $u['usuario'];
        $_SESSION['rol']      = $u['rol'] ?? 'user';
        $_SESSION['logueado'] = true;
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
