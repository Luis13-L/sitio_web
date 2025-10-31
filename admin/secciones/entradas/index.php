<?php
include("../../bd.php");
include("../../templates/header.php");

/* ---- ELIMINAR: borra archivo + registro y redirige con mensaje ---- */
if (isset($_GET['txtID']) && is_numeric($_GET['txtID'])) {
  $txtID = (int)$_GET['txtID'];

  // traer filename
  $st = $conexion->prepare("SELECT imagen FROM `tbl_entradas` WHERE id = :id");
  $st->bindParam(":id", $txtID, PDO::PARAM_INT);
  $st->execute();
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if ($row) {
    $file = __DIR__ . "/../../../assets/img/about/" . $row['imagen'];
    if (is_file($file)) { @unlink($file); }

    $del = $conexion->prepare("DELETE FROM `tbl_entradas` WHERE id = :id");
    $del->bindParam(":id", $txtID, PDO::PARAM_INT);
    $del->execute();

    header("Location: index.php?mensaje=" . urlencode("Registro eliminado."));
    exit;
  }
}

/* ---- LISTAR ---- */
$st = $conexion->prepare("SELECT * FROM `tbl_entradas` ORDER BY id DESC");
$st->execute();
$lista_entradas = $st->fetchAll(PDO::FETCH_ASSOC);

$mensaje = isset($_GET['mensaje']) ? $_GET['mensaje'] : '';
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Entradas (Historia / Timeline)</span>
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
            <th scope="col" style="width:140px;">Fecha</th>
            <th scope="col" style="min-width:220px;">Título</th>
            <th scope="col">Descripción</th>
            <th scope="col" style="width:130px;">Imagen</th>
            <th scope="col" style="width:180px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($lista_entradas as $reg): ?>
          <?php
            $id    = (int)$reg['ID'];
            $fecha = htmlspecialchars($reg['fecha'] ?? '');
            $tit   = htmlspecialchars($reg['titulo'] ?? '');
            $desc  = htmlspecialchars($reg['descripcion'] ?? '');
            $img   = htmlspecialchars($reg['imagen'] ?? '');
          ?>
          <tr>
            <td><?= $id ?></td>
            <td><?= $fecha ?></td>
            <td class="fw-semibold"><?= $tit ?></td>
            <td class="text-wrap" style="max-width:520px;"><?= nl2br($desc) ?></td>
            <td>
              <?php if ($img): ?>
                <img
                  src="../../../assets/img/about/<?= $img ?>"
                  alt="<?= $tit ?>"
                  style="width:88px;height:88px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
              <?php else: ?>
                <span class="text-muted">Sin imagen</span>
              <?php endif; ?>
            </td>
            <td>
              <a class="btn btn-info btn-sm" href="editar.php?txtID=<?= $id ?>">Editar</a>
              <a class="btn btn-danger btn-sm"
                 href="index.php?txtID=<?= $id ?>"
                 onclick="return confirm('¿Eliminar este registro? También se borrará la imagen.');">
                 Eliminar
              </a>
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (!$lista_entradas): ?>
          <tr><td colspan="6" class="text-center text-muted">No hay registros.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include("../../templates/footer.php"); ?>
