<?php
include("../../bd.php");
include("../../templates/header.php");

/* ---- ELIMINAR: borra archivo + registro y redirige con mensaje ---- */
if (isset($_GET['txtID']) && is_numeric($_GET['txtID'])) {
  $txtID = (int)$_GET['txtID'];

  // traer filename
  $st = $conexion->prepare("SELECT imagen FROM `tbl_equipo` WHERE id = :id");
  $st->bindParam(":id", $txtID, PDO::PARAM_INT);
  $st->execute();
  $row = $st->fetch(PDO::FETCH_ASSOC);

  if ($row) {
    $file = __DIR__ . "/../../../assets/img/team/" . $row['imagen'];
    if (is_file($file)) { @unlink($file); }

    $del = $conexion->prepare("DELETE FROM `tbl_equipo` WHERE id = :id");
    $del->bindParam(":id", $txtID, PDO::PARAM_INT);
    $del->execute();

    header("Location: index.php?mensaje=" . urlencode("Registro eliminado."));
    exit;
  }
}

/* ---- LISTAR ---- */
$st = $conexion->prepare("SELECT * FROM `tbl_equipo` ORDER BY id DESC");
$st->execute();
$lista_equipo = $st->fetchAll(PDO::FETCH_ASSOC);

$mensaje = isset($_GET['mensaje']) ? $_GET['mensaje'] : '';
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Equipo</span>
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
            <th scope="col" style="width:130px;">Imagen</th>
            <th scope="col" style="min-width:260px;">Nombre y puesto</th>
            <th scope="col" style="min-width:260px;">Contacto</th>
            <th scope="col" style="width:180px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($lista_equipo as $reg): ?>
          <?php
            $id    = (int)$reg['ID'];
            $img   = htmlspecialchars($reg['imagen'] ?? '');
            $nom   = htmlspecialchars($reg['nombrecompleto'] ?? '');
            $puesto= htmlspecialchars($reg['puesto'] ?? '');
            $mail  = trim($reg['correo'] ?? '');
            $link  = trim($reg['linkedin'] ?? '');
          ?>
          <tr>
            <td><?= $id ?></td>

            <td>
              <?php if ($img): ?>
                <img
                  src="../../../assets/img/team/<?= $img ?>"
                  alt="<?= $nom ?>"
                  style="width:88px;height:88px;object-fit:cover;border-radius:8px;border:1px solid #e5e7eb;">
              <?php else: ?>
                <span class="text-muted">Sin imagen</span>
              <?php endif; ?>
            </td>

            <td>
              <div class="fw-semibold"><?= $nom ?></div>
              <div class="text-muted small"><?= $puesto ?></div>
            </td>

            <td>
              <?php if ($mail): ?>
                <div class="small">
                  <i class="fa-solid fa-envelope me-1"></i>
                  <a href="mailto:<?= htmlspecialchars($mail) ?>"><?= htmlspecialchars($mail) ?></a>
                </div>
              <?php endif; ?>
              <?php if ($link): ?>
                <div class="small mt-1">
                  <i class="fab fa-linkedin-in me-1"></i>
                  <a href="<?= htmlspecialchars($link) ?>" target="_blank" rel="noopener noreferrer">
                    <?= htmlspecialchars($link) ?>
                  </a>
                </div>
              <?php endif; ?>
              <?php if (!$mail && !$link): ?>
                <span class="text-muted small">Sin datos</span>
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

        <?php if (!$lista_equipo): ?>
          <tr><td colspan="5" class="text-center text-muted">No hay registros.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include("../../templates/footer.php"); ?>
