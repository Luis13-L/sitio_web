<?php
require_once __DIR__ . '/_admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
$pdo = db();
$errores = [];
$mensaje = '';

// Alta / edición
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $tipo   = $_POST['tipo'] ?? '';
    $nombre = trim($_POST['nombre'] ?? '');
    $desc   = trim($_POST['descripcion'] ?? '');
    $activo = isset($_POST['activo']) ? 1 : 0;

    if (!in_array($tipo, ['vehiculo','salon'], true)) {
        $errores[] = 'Tipo inválido.';
    }
    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    }

    if (!$errores) {
        if ($id > 0) {
            $sql = "UPDATE recursos
                    SET tipo = :tipo, nombre = :nombre, descripcion = :descripcion, activo = :activo
                    WHERE id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':tipo' => $tipo,
                ':nombre' => $nombre,
                ':descripcion' => $desc,
                ':activo' => $activo,
                ':id' => $id,
            ]);
            $mensaje = 'Recurso actualizado correctamente.';
        } else {
            $sql = "INSERT INTO recursos (tipo, nombre, descripcion, activo)
                    VALUES (:tipo, :nombre, :descripcion, :activo)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':tipo' => $tipo,
                ':nombre' => $nombre,
                ':descripcion' => $desc,
                ':activo' => $activo,
            ]);
            $mensaje = 'Recurso creado correctamente.';
        }
    }
}

// Eliminar (desactivar suave)
if (isset($_GET['del'])) {
    $id = (int)$_GET['del'];
    $pdo->prepare("UPDATE recursos SET activo = 0 WHERE id = ?")->execute([$id]);
    $mensaje = 'Recurso desactivado.';
}

$recursos = $pdo->query("SELECT * FROM recursos ORDER BY tipo, nombre")->fetchAll();
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Administrar recursos</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Vehículos y salones</h4>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">← Volver</a>
  </div>

  <?php if ($errores): ?>
    <div class="alert alert-danger">
      <ul class="mb-0">
        <?php foreach($errores as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php elseif ($mensaje): ?>
    <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
  <?php endif; ?>

  <!-- Formulario de alta/edición -->
  <div class="card mb-3">
    <div class="card-body">
      <form method="post">
        <input type="hidden" name="id" id="id" value="">
        <div class="row g-2">
          <div class="col-md-3">
            <label class="form-label">Tipo</label>
            <select name="tipo" id="tipo" class="form-select" required>
              <option value="vehiculo">Vehículo</option>
              <option value="salon">Salón</option>
            </select>
          </div>
          <div class="col-md-5">
            <label class="form-label">Nombre</label>
            <input type="text" name="nombre" id="nombre" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Activo</label><br>
            <input type="checkbox" name="activo" id="activo" checked>
            <label for="activo">Disponible</label>
          </div>
          <div class="col-12">
            <label class="form-label">Descripción</label>
            <textarea name="descripcion" id="descripcion" class="form-control" rows="2"></textarea>
          </div>
        </div>
        <div class="mt-3 text-end">
          <button type="reset" class="btn btn-outline-secondary btn-sm">Limpiar</button>
          <button type="submit" class="btn btn-primary btn-sm">Guardar</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Tabla -->
  <div class="card">
    <div class="card-body">
      <table class="table table-sm align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Tipo</th>
            <th>Nombre</th>
            <th>Activo</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($recursos as $r): ?>
          <tr>
            <td><?= (int)$r['id'] ?></td>
            <td><?= htmlspecialchars($r['tipo']) ?></td>
            <td><?= htmlspecialchars($r['nombre']) ?></td>
            <td><?= $r['activo'] ? 'Sí' : 'No' ?></td>
            <td class="text-end">
              <button class="btn btn-sm btn-outline-primary"
                      onclick='cargarRecurso(<?= json_encode($r) ?>)'>Editar</button>
              <a href="?del=<?= (int)$r['id'] ?>" class="btn btn-sm btn-outline-danger"
                 onclick="return confirm('¿Desactivar este recurso?');">Desactivar</a>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
function cargarRecurso(r) {
  document.getElementById('id').value = r.id;
  document.getElementById('tipo').value = r.tipo;
  document.getElementById('nombre').value = r.nombre;
  document.getElementById('descripcion').value = r.descripcion || '';
  document.getElementById('activo').checked = (r.activo === '1' || r.activo === 1);
}
</script>
</body>
</html>