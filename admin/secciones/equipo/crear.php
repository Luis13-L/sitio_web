<?php

    include("../../templates/header.php");
    include("../../bd.php");

    if($_POST){
    
        //recibir los valores del formulario
        $imagen=(isset($_FILES["imagen"]["name"]))?$_FILES["imagen"]["name"]:"";
        $nombrecompleto=(isset($_POST['nombrecompleto']))?$_POST['nombrecompleto']:"";
        $puesto=(isset($_POST['puesto']))?$_POST['puesto']:"";
        $correo=(isset($_POST['correo']))?$_POST['correo']:"";
        $linkedin=(isset($_POST['linkedin']))?$_POST['linkedin']:"";

        $fecha_imagen=new DateTime();
        $nombre_archivo_imagen=($imagen!="")? $fecha_imagen->getTimestamp()."_".$imagen:"";

        //insertar

        $tmp_imagen=$_FILES["imagen"]["tmp_name"];

        if($tmp_imagen!=""){

          move_uploaded_file($tmp_imagen,"../../../assets/img/team/".$nombre_archivo_imagen);
          echo "se guardó la img";

        }

        $sentencia=$conexion->prepare("INSERT INTO `tbl_equipo` 
        (`ID`, `imagen`, `nombrecompleto`, `puesto`, `correo`, `linkedin` ) 
        VALUES (NULL, :imagen, :nombrecompleto, :puesto, :correo, :linkedin);");
        
        $sentencia->bindParam(":imagen",$nombre_archivo_imagen);
        $sentencia->bindParam(":nombrecompleto",$nombrecompleto);
        $sentencia->bindParam(":puesto",$puesto);
        $sentencia->bindParam(":correo",$correo);
        $sentencia->bindParam(":linkedin",$linkedin);

        $sentencia->execute();
        $mensaje="Registro agregado con éxito";
        header("Location:index.php?mensaje=".$mensaje);

    }

?>


<div class="card">
    <div class="card-header">
    Datos del Personal
    </div>
    <div class="card-body">
    <form action="" method="post" enctype="multipart/form-data">
    <div class="mb-3">
      <label for="imagen" class="form-label">Imagen:</label>
      <input type="file"
        class="form-control" name="imagen" id="imagen" aria-describedby="helpId" placeholder="Imagen">
 
    </div>

    <div class="mb-3">
      <label for="nombrecompleto" class="form-label">Nombre Completo:</label>
      <input type="text"
        class="form-control" name="nombrecompleto" id="nombrecompleto" aria-describedby="helpId" placeholder="Nombre">
      
    </div>

    <div class="mb-3">
      <label for="puesto" class="form-label">Puesto:</label>
      <input type="text"
        class="form-control" name="puesto" id="puesto" aria-describedby="helpId" placeholder="Puesto">
      
    </div>

    <div class="mb-3">
      <label for="correo" class="form-label">Correo:</label>
      <input type="text"
        class="form-control" name="correo" id="correo" aria-describedby="helpId" placeholder="Correo">
      
    </div>

    <div class="mb-3">
      <label for="linkedin" class="form-label">LinkedIn:</label>
      <input type="text"
        class="form-control" name="linkedin" id="linkedin" aria-describedby="helpId" placeholder="LinkedIn">
      
    </div>

    <button type="submit" class="btn btn-success">Agregar</button>
        <a name="" id="" class="btn btn-primary" href="index.php" role="button">Cancelar</a>
    </form>   
    

    </div>
    <div class="card-footer text-muted">
        
    </div>
</div>


<?phpinclude("../../templates/footer.php");?>