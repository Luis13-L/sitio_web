<?php
require_once __DIR__ . '/_admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
$pdo = db();

$errores = [];
$mensaje = '';

// Alta de usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id       = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $username = trim($_POST['username'] ?? '');
    $rol      = $_POST['rol'] ?? 'user';
    $activo   = isset($_POST['activo']) ? 1 : 0;
    $clave    = $_POST['clave'] ?? '';

    if ($username === '') $errores[] = 'El nombre de usuario es obligatorio.';
    if (!in_array($rol, ['admin','user'], true)) $errores[] = 'Rol inválido.';

    if (!$errores) {
        if ($id > 0) {
            // editar: solo cambio clave si viene algo
            $params = [
                ':username' => $username,
                ':rol'      => $rol,
                ':activo'   => $activo,
                ':id'       => $id,
            ];
            $setPass = '';
            if ($clave !== '') {
                $setPass = ', pass_hash = :pass_hash';
                $params[':pass_hash'] = password_hash($clave, PASSWORD_BCRYPT);
            }

            $sql = "UPDATE usuarios
                    SET username = :username, rol = :rol, activo = :activo $setPass
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $mensaje = 'Usuario actualizado.';
        } else {
            if ($clave === '') {
                $errores[] = 'Debe ingresar una contraseña para el nuevo usuario.';
            } else {
                $sql = "INSERT INTO usuarios (username, pass_hash, rol, activo, creado_en)
                        VALUES (:username, :pass_hash, :rol, :activo, NOW())";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':username' => $username,
                    ':pass_hash'=> password_hash($clave, PASSWORD_BCRYPT),
                    ':rol'      => $rol,
                    ':activo'   => $activo,
                ]);
                $mensaje = 'Usuario creado correctamente.';
            }
        }
    }
}

// desactivar rápido
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $pdo->prepare("UPDATE usuarios SET activo = 1 - activo WHERE id = ?")->execute([$id]);
    $mensaje = 'Estado del usuario actualizado.';
}

$usuarios = $pdo->query("SELECT * FROM usuarios ORDER BY id")->fetchAll();
?>


<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Usuarios</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Usuarios del sistema</h4>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">← Volver</a>
  </div>

  <?php if ($errores): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach($errores as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul>
    </div>
  <?php elseif ($mensaje): ?>
    <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
  <?php endif; ?>

  <div class="card mb-3">
    <div class="card-body">
      <form method="post">
        <input type="hidden" name="id" id="id" value="">
        <div class="row g-2">
          <div class="col-md-4">
            <label class="form-label">Usuario</label>
            <input type="text" name="username" id="username" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Rol</label>
            <select name="rol" id="rol" class="form-select">
              <option value="user">Usuario</option>
              <option value="admin">Administrador</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Activo</label><br>
            <input type="checkbox" name="activo" id="activo" checked> <label for="activo">Sí</label>
          </div>
          <div class="col-12">
            <label class="form-label">Contraseña
              <small class="text-muted">(en edición, deja en blanco para no cambiar)</small>
            </label>
            <input type="password" name="clave" id="clave" class="form-control">
          </div>
        </div>
        <div class="mt-3 text-end">
          <button type="reset" class="btn btn-outline-secondary btn-sm">Limpiar</button>
          <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <table class="table table-sm align-middle">
        <thead>
          <tr><th>ID</th><th>Usuario</th><th>Rol</th><th>Activo</th><th></th></tr>
        </thead>
        <tbody>
        <?php foreach($usuarios as $u): ?>
          <tr>
            <td><?= (int)$u['id'] ?></td>
            <td><?= htmlspecialchars($u['username']) ?></td>
            <td><?= htmlspecialchars($u['rol']) ?></td>
            <td><?= $u['activo'] ? 'Sí' : 'No' ?></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary"
                      onclick='cargarUsuario(<?= json_encode($u) ?>)'>Editar</button>
              <a href="?toggle=<?= (int)$u['id'] ?>" class="btn btn-sm btn-outline-warning">
                Activar/desactivar
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>
<script>
function cargarUsuario(u){
  document.getElementById('id').value = u.id;
  document.getElementById('username').value = u.username;
  document.getElementById('rol').value = u.rol;
  document.getElementById('activo').checked = (u.activo === '1' || u.activo === 1);
  document.getElementById('clave').value = '';
}
</script>
</body>
</html>
