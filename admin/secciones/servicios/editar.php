editar servicios

<?php

    include("../../templates/header.php");
    include("../../bd.php");

    if(isset($_GET['txtID'])){
        //Consultamos el registros

        $txtID=(isset($_GET['txtID']) )?$_GET['txtID']:"";

        $sentencia=$conexion->prepare("SELECT * FROM `tbl_servicios` WHERE id=:id");

        $sentencia->bindParam(":id",$txtID);
       
    
        $sentencia->execute();

        $registro=$sentencia->fetch(PDO::FETCH_LAZY);

        $icono=$registro['icono'];
        $titulo=$registro['titulo'];
        $descripcion=$registro['descripcion'];

    }


    if($_POST){

        //recibimos los campos
        $txtID=(isset($_POST['txtID']))?$_POST['txtID']:"";
        $icono=(isset($_POST['icono']))?$_POST['icono']:"";
        $titulo=(isset($_POST['titulo']))?$_POST['titulo']:"";
        $descripcion=(isset($_POST['descripcion']))?$_POST['descripcion']:"";

        $sentencia=$conexion->prepare("UPDATE `tbl_servicios`
        SET
        icono=:icono,
        titulo=:titulo,
        descripcion=:descripcion
        WHERE id=:id");

        $sentencia->bindParam(":icono",$icono);
        $sentencia->bindParam(":titulo",$titulo);
        $sentencia->bindParam(":descripcion",$descripcion);
        $sentencia->bindParam(":id",$txtID);

    
        $sentencia->execute();

        //$mensaje="Registro modificado con xito.";
        //header("Location:index.ph?mensaje=".$mensaje);


    }

  


?>


<div class="card">
    <div class="card-header">
        Editando informacion de servicios
    </div>
    <div class="card-body">
        
    <form action="" enctype="multipar/form-data" method="post">

    <div class="mb-3">
      <label for="txtID" class="form-label">ID:</label>
      <input readonly value="<?php echo $txtID; ?>"
        class="form-control" name="txtID" id="txtID" aria-describedby="helpId" placeholder="">
      
    </div>

    <div class="mb-3">
      <label for="imagen" class="form-label">Imagen:</label>
      <input value="<?php echo $icono; ?>"
        class="form-control" name="icono" id="icono" aria-describedby="helpId" placeholder="">
    </div>

    <div class="mb-3">
      <label for="titulo" class="form-label">Título:</label>
      <input value="<?php echo $titulo; ?>"
        class="form-control" name="titulo" id="titulo" aria-describedby="helpId" placeholder="">
    </div>

    <div class="mb-3">
      <label for="descripcion" class="form-label">Descripción:</label>
      <input value="<?php echo $descripcion; ?>"
        class="form-control" name="descripcion" id="descripcion" aria-describedby="helpId" placeholder="">
    </div>

    <button type="submit" class="btn btn-success">Actualizar</button>
    <a name="" id="" class="btn btn-primary" href="index.php" role="button">Cancelar</a>

    </form>

    </div>
    <div class="card-footer text-muted">
        
    </div>
</div>

<?php

    include("../../templates/footer.php");
?>