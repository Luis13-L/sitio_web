
<?php
include("../../bd.php");
include("../../templates/header.php");

/* ---- ELIMINAR: borra archivo + registro y redirige con mensaje ---- */
if (isset($_GET['txtID']) && is_numeric($_GET['txtID'])) {
  $txtID = (int)$_GET['txtID'];

  // traer filename
  $st = $conexion->prepare("SELECT imagen FROM `tbl_inicioo` WHERE id = :id");
  $st->bindParam(":id", $txtID, PDO::PARAM_INT);
  $st->execute();
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if ($row) {
    // borrar archivo físico si existe
    $file = __DIR__ . "/../../../assets/img/" . $row['imagen'];
    if (is_file($file)) { @unlink($file); }

    // borrar registro
    $del = $conexion->prepare("DELETE FROM `tbl_inicioo` WHERE id = :id");
    $del->bindParam(":id", $txtID, PDO::PARAM_INT);
    $del->execute();

    header("Location: index.php?mensaje=" . urlencode("Registro eliminado."));
    exit;
  }
}

/* ---- LISTAR ---- */
$sentencia = $conexion->prepare("SELECT * FROM `tbl_inicioo` ORDER BY id DESC");
$sentencia->execute();
$lista_inicioo = $sentencia->fetchAll(PDO::FETCH_ASSOC);

$mensaje = isset($_GET['mensaje']) ? $_GET['mensaje'] : '';
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Puedes editar los componentes…</span>
    <a class="btn btn-primary" href="crear.php" role="button">Agregar registro</a>
  </div>

  <div class="card-body">
    <?php if ($mensaje): ?>
      <div class="alert alert-success py-2 mb-3"><?= htmlspecialchars($mensaje) ?></div>
    <?php endif; ?>

    <div class="table-responsive-sm">
      <table class="table align-middle">
        <thead>
          <tr>
            <th scope="col" style="width:70px;">ID</th>
            <th scope="col" style="width:220px;">Componente</th>
            <th scope="col" style="width:130px;">Imagen</th>
            <th scope="col" style="width:180px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($lista_inicioo as $reg): ?>
          <tr>
            <td><?= (int)$reg['ID']; ?></td>
            <td><?= htmlspecialchars($reg['componente']); ?></td>
            <td>
              <?php if (!empty($reg['imagen'])): ?>
                <img
                  src="../../../assets/img/<?= htmlspecialchars($reg['imagen']) ?>"
                  alt="<?= htmlspecialchars($reg['componente']) ?>"
                  style="width:88px;height:88px;object-fit:contain;border:1px solid #e5e7eb;border-radius:8px;">
              <?php else: ?>
                <span class="text-muted">Sin imagen</span>
              <?php endif; ?>
            </td>
            <td>
              <a class="btn btn-info btn-sm" href="editar.php?txtID=<?= (int)$reg['ID']; ?>">Editar</a>
              <a class="btn btn-danger btn-sm"
                 href="index.php?txtID=<?= (int)$reg['ID']; ?>"
                 onclick="return confirm('¿Eliminar este registro? También se borrará la imagen.');">
                 Eliminar
              </a>
            </td>
          </tr>
          <?php endforeach; ?>

          <?php if (!$lista_inicioo): ?>
          <tr><td colspan="4" class="text-center text-muted">No hay registros.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include("../../templates/footer.php"); ?>
