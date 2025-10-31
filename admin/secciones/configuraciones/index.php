<?php
include("../../bd.php");
include("../../templates/header.php");

/* ---- ELIMINAR (opcional): si decides permitirlo, descomenta el botón abajo ---- */
if (isset($_GET['txtID']) && is_numeric($_GET['txtID'])) {
  $txtID = (int)$_GET['txtID'];
  $del = $conexion->prepare("DELETE FROM `tbl_confifiguraciones` WHERE id = :id");
  $del->bindParam(":id", $txtID, PDO::PARAM_INT);
  $del->execute();
  header("Location: index.php?mensaje=" . urlencode("Registro eliminado."));
  exit;
}

/* ---- LISTAR ---- */
$st = $conexion->prepare("SELECT * FROM `tbl_confifiguraciones` ORDER BY id DESC");
$st->execute();
$lista_configuraciones = $st->fetchAll(PDO::FETCH_ASSOC);

$mensaje = $_GET['mensaje'] ?? '';
?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span>Configuración</span>
    <!-- Si quieres permitir crear nuevas, habilita esto:
    <a class="btn btn-primary" href="crear.php" role="button">Agregar registro</a>
    -->
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
            <th scope="col" style="min-width:240px;">Nombre de la configuración</th>
            <th scope="col">Valor</th>
            <th scope="col" style="width:180px;">Acciones</th>
          </tr>
        </thead>
        <tbody>
        <?php foreach ($lista_configuraciones as $reg): ?>
          <?php
            $id   = (int)$reg['ID'];
            $name = htmlspecialchars($reg['nombreConfiguracion'] ?? '');
            $val  = $reg['valor'] ?? '';
            $val_safe = htmlspecialchars($val);
            // versión truncada para no romper la tabla
            $val_short = mb_strimwidth($val, 0, 120, '…', 'UTF-8');
          ?>
          <tr>
            <td><?= $id ?></td>
            <td class="fw-semibold"><?= $name ?></td>
            <td class="text-wrap">
              <span title="<?= $val_safe ?>"><?= htmlspecialchars($val_short) ?></span>
            </td>
            <td>
              <a class="btn btn-info btn-sm" href="editar.php?txtID=<?= $id ?>">Editar</a>
              <!-- Para permitir borrar, descomenta:
              <a class="btn btn-danger btn-sm"
                 href="index.php?txtID=<?= $id ?>"
                 onclick="return confirm('¿Eliminar este registro?');">
                 Eliminar
              </a>
              -->
            </td>
          </tr>
        <?php endforeach; ?>

        <?php if (!$lista_configuraciones): ?>
          <tr><td colspan="4" class="text-center text-muted">No hay registros.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include("../../templates/footer.php"); ?>
