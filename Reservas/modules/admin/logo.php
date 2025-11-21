<?php
require_once __DIR__ . '/_admin_guard.php';
require_once __DIR__ . '/../../config/db.php';
$pdo = db();

$mensaje = '';
$error   = '';

// leer valor actual
$stmt = $pdo->prepare("SELECT valor FROM config_reservas WHERE clave = 'logo_path'");
$stmt->execute();
$logoRow = $stmt->fetch();
$logoPath = $logoRow ? $logoRow['valor'] : 'assets/img/solola_emblem_32.png';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['logo'])) {
    if ($_FILES['logo']['error'] === UPLOAD_ERR_OK) {
        $tmp  = $_FILES['logo']['tmp_name'];
        $name = $_FILES['logo']['name'];

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg','jpeg','png','gif'], true)) {
            $error = 'Formato no permitido. Solo JPG, PNG o GIF.';
        } else {
            $nuevoNombre = 'logo_reservas.' . $ext;
            $destinoRel  = 'assets/img/' . $nuevoNombre;
            $destinoAbs  = realpath(__DIR__ . '/../../') . '/assets/img/' . $nuevoNombre;

            if (!move_uploaded_file($tmp, $destinoAbs)) {
                $error = 'No se pudo guardar el archivo.';
            } else {
                // actualizar en BD
                $stmt = $pdo->prepare("INSERT INTO config_reservas (clave, valor)
                                       VALUES ('logo_path', :valor)
                                       ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
                $stmt->execute([':valor' => $destinoRel]);
                $logoPath = $destinoRel;
                $mensaje = 'Logo actualizado correctamente.';
            }
        }
    } else {
        $error = 'Error al subir el archivo.';
    }
}
?>

<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Logo del sistema</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="mb-0">Logo del sistema de reservas</h4>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">← Volver</a>
  </div>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($mensaje): ?>
    <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
  <?php endif; ?>

  <div class="card">
    <div class="card-body">
      <p>Logo actual que se muestra en el panel:</p>
      <div class="mb-3">
        <img src="../../<?= htmlspecialchars($logoPath) ?>" alt="Logo actual" style="max-height:80px;">
      </div>

      <form method="post" enctype="multipart/form-data">
        <div class="mb-3">
          <label class="form-label">Selecciona una nueva imagen</label>
          <input type="file" name="logo" class="form-control" accept=".jpg,.jpeg,.png,.gif" required>
        </div>
        <button type="submit" class="btn btn-primary">Guardar logo</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
