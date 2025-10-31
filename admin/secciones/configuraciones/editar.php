<?php
include("../../templates/header.php");
include("../../bd.php");

/* ===== CARGA INICIAL ===== */
$txtID = '';
$nombreConfiguracion = $valor = '';

if (isset($_GET['txtID']) && is_numeric($_GET['txtID'])) {
  $txtID = (int)$_GET['txtID'];

  $st = $conexion->prepare("SELECT * FROM `tbl_confifiguraciones` WHERE id = :id");
  $st->bindParam(":id", $txtID, PDO::PARAM_INT);
  $st->execute();

  if ($reg = $st->fetch(PDO::FETCH_ASSOC)) {
    $nombreConfiguracion = $reg['nombreConfiguracion'] ?? '';
    $valor               = $reg['valor'] ?? '';
  }
}

/* ===== ACTUALIZAR ===== */
if ($_POST) {
  $txtID               = isset($_POST['txtID']) ? (int)$_POST['txtID'] : 0;
  $nombreConfiguracion = $_POST['nombreConfiguracion'] ?? '';
  $valor               = $_POST['valor'] ?? '';

  $up = $conexion->prepare(
    "UPDATE `tbl_confifiguraciones`
     SET nombreConfiguracion = :nombreConfiguracion,
         valor               = :valor
     WHERE id = :id"
  );
  $up->bindParam(":nombreConfiguracion", $nombreConfiguracion);
  $up->bindParam(":valor",               $valor);
  $up->bindParam(":id",                  $txtID, PDO::PARAM_INT);
  $up->execute();

  $mensaje = "Registro modificado con éxito";
  header("Location: index.php?mensaje=" . urlencode($mensaje));
  exit;
}
?>

<div class="card">
  <div class="card-header">
    Editar configuración
  </div>

  <div class="card-body">
    <form action="" method="post">
      <div class="mb-3">
        <label for="txtID" class="form-label">ID</label>
        <input type="text" class="form-control" id="txtID" name="txtID"
               value="<?= htmlspecialchars((string)$txtID) ?>" readonly>
      </div>

      <div class="mb-3">
        <label for="nombreConfiguracion" class="form-label">Nombre</label>
        <input type="text" class="form-control" id="nombreConfiguracion" name="nombreConfiguracion"
               value="<?= htmlspecialchars($nombreConfiguracion) ?>" placeholder="Nombre de la configuración" required>
      </div>

      <!-- Si usas valores largos (p. ej., textos de portada), puedes cambiar a textarea -->
      <div class="mb-3">
        <label for="valor" class="form-label">Valor</label>
        <textarea class="form-control" id="valor" name="valor" rows="3"
                  placeholder="Valor de la configuración"><?= htmlspecialchars($valor) ?></textarea>
      </div>

      <button type="submit" class="btn btn-success">Actualizar</button>
      <a class="btn btn-primary" href="index.php" role="button">Cancelar</a>
    </form>
  </div>

  <div class="card-footer text-muted"></div>
</div>

<?php include("../../templates/footer.php"); ?>
