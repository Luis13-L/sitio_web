<?php
include("../../bd.php");
include("../../templates/header.php");

// (Opcional pero recomendado) Solo admin puede ver/gestionar usuarios
// require_once __DIR__ . "/../auth_guard.php";

// Obtener usuarios (NO seleccionar password)
$st = $conexion->prepare("SELECT ID, usuario, correo, rol, is_active, created_at FROM tbl_usuarios ORDER BY ID");
$st->execute();
$usuarios = $st->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <a class="btn btn-primary" href="crear.php">Agregar registro</a>
    <strong>Usuarios</strong>
  </div>

  <div class="card-body">
    <div class="table-responsive">
      <table class="table align-middle">
        <thead>
          <tr>
            <th>ID</th>
            <th>Usuario</th>
            <th>Correo</th>
            <th>Contraseña</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($usuarios as $u): ?>
          <tr>
            <td><?= (int)$u['ID'] ?></td>
            <td><?= htmlspecialchars($u['usuario']) ?></td>
            <td><?= htmlspecialchars($u['correo']) ?></td>

            <!-- NUNCA muestres el hash. Solo un placeholder -->
            <td><span class="text-muted">••••••••</span></td>

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

            <td class="d-flex gap-2">
              <a class="btn btn-info btn-sm" href="editar.php?txtID=<?= (int)$u['ID'] ?>">Editar</a>

              <!-- Sugerencia: elimina con POST + CSRF; si mantienes GET, al menos pide confirmación -->
              <a class="btn btn-danger btn-sm"
                 href="index.php?txtID=<?= (int)$u['ID'] ?>"
                 onclick="return confirm('¿Eliminar este usuario?');">Eliminar</a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include("../../templates/footer.php"); ?>
