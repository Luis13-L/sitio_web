editar configuraciones

<?php

    include("../../templates/header.php");
    include("../../bd.php");

    if(isset($_GET['txtID'])){

    
        $txtID=(isset($_GET['txtID']) )?$_GET['txtID']:"";
    
        $sentencia=$conexion->prepare("SELECT * FROM `tbl_confifiguraciones` WHERE id=:id");
    
        $sentencia->bindParam(":id",$txtID);
    
        $sentencia->execute();
    
        $registro=$sentencia->fetch(PDO::FETCH_LAZY);
    
        //recuperar registros
    
        $nombreConfiguracion=$registro['nombreConfiguracion'];
        $valor=$registro['valor'];
    }

    if($_POST){

        //recibir los valores del formulario
        $txtID=(isset($_POST['txtID']))?$_POST['txtID']:"";//importante incluir
        $nombreConfiguracion=(isset($_POST['nombreConfiguracion']))?$_POST['nombreConfiguracion']:"";
        $valor=(isset($_POST['valor']))?$_POST['valor']:"";
      
        $sentencia=$conexion->prepare("UPDATE tbl_confifiguraciones 
        SET 
        nombreConfiguracion= :nombreConfiguracion,
        valor= :valor
        WHERE id=:id ");

        $sentencia->bindParam(":nombreConfiguracion",$nombreConfiguracion);
        $sentencia->bindParam(":valor",$valor);
        $sentencia->bindParam(":id",$txtID);

        $sentencia->execute();


      
        $mensaje="Registro modificado con éxito";
        header("Location:index.php?mensaje=".$mensaje);
    }

?>


<div class="card">
    <div class="card-header">
        Confirguración
    </div>
    <div class="card-body">


    <form action="" method="post">

    <div class="mb-3">
      <label for="txtID" class="form-label">ID:</label>
      <input readonly type="text"
        class="form-control" value="<?php echo $txtID;?>" name="txtID" id="txtID" aria-describedby="helpId" placeholder="">
    </div>

    <div class="mb-3">
      <label for="nombreConfiguracion" class="form-label">Nombre:</label>
      <input type="text"
        class="form-control" value="<?php echo $nombreConfiguracion;?>" name="nombreConfiguracion" id="nombreConfiguracion" aria-describedby="helpId" placeholder="Nombre de la configuración">
    </div>

    <div class="mb-3">
      <label for="valor" class="form-label">Valor:</label>
      <input type="text"
        class="form-control" value="<?php echo $valor;?>" name="valor" id="valor" aria-describedby="helpId" placeholder="Valor de la configuración">
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