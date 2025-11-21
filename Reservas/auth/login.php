<?php
// auth/login.php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth_guard.php';

$pdo      = db();
$errores  = [];
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf_or_fail($_POST['csrf_token'] ?? null);

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $errores[] = 'Usuario y contraseña son obligatorios.';
    } else {
        $stmt = $pdo->prepare(
            "SELECT id, username, pass_hash, rol, activo
             FROM usuarios
             WHERE username = :u
             LIMIT 1"
        );
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if (!$user || !(int)$user['activo']) {
            $errores[] = 'Usuario o contraseña incorrectos.';
        } elseif (!password_verify($password, $user['pass_hash'])) {
            $errores[] = 'Usuario o contraseña incorrectos.';
        } else {
            // OK: login
            session_regenerate_id(true);
            $_SESSION['user_id']  = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['rol']      = $user['rol'];

            // generar nuevo token CSRF
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

            header('Location: ../index.php'); // panel principal de reservas
            exit;
        }
    }
}

$csrf = ensure_csrf_token();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Iniciar sesión · Reservas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="row justify-content-center">
    <div class="col-12 col-md-5">
      <div class="card shadow-sm">
        <div class="card-body">
          <h4 class="mb-3 text-center">Sistema de Reservas</h4>
          <p class="text-muted text-center mb-4">Ingrese sus credenciales institucionales.</p>

          <?php if ($errores): ?>
            <div class="alert alert-danger">
              <?php foreach ($errores as $e): ?>
                <div><?= htmlspecialchars($e) ?></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <form method="post" novalidate>
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

            <div class="mb-3">
              <label for="username" class="form-label">Usuario</label>
              <input
                type="text"
                class="form-control"
                id="username"
                name="username"
                value="<?= htmlspecialchars($username) ?>"
                required
              >
            </div>

            <div class="mb-3">
              <label for="password" class="form-label">Contraseña</label>
              <input
                type="password"
                class="form-control"
                id="password"
                name="password"
                required
              >
            </div>

            <div class="d-grid">
              <button type="submit" class="btn btn-primary">
                Iniciar sesión
              </button>
            </div>
          </form>
        </div>
      </div>
      <p class="text-center text-muted small mt-3">
        © <?= date('Y') ?> Dirección Departamental de Educación de Sololá
      </p>
    </div>
  </div>
</div>
</body>
</html>
