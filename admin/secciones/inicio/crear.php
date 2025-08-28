
<?php
    include("../../bd.php");

    include("../../templates/header.php");
    
if($_POST){


        //recibir los valores del formulario

        $componente=(isset($_POST['componente']))?$_POST['componente']:"";

        $imagen=(isset($_FILES["imagen"]["name"]))?$_FILES["imagen"]["name"]:"";




        //Agregar la imagen

        $fecha_imagen=new DateTime();
        $nombre_archivo_imagen=($imagen!="")? $fecha_imagen->getTimestamp()."_".$imagen:"";

        $tmp_imagen=$_FILES["imagen"]["tmp_name"];

        if($tmp_imagen!=""){

          move_uploaded_file($tmp_imagen,"../../../assets/img/".$nombre_archivo_imagen);
          

        }




        $sentencia=$conexion->prepare("INSERT INTO `tbl_inicioo` 
        (`ID`, `componente`, `imagen`) 
        VALUES (NULL, :componente, :imagen);");

        $sentencia->bindParam(":componente",$componente);
        
        $sentencia->bindParam(":imagen",$nombre_archivo_imagen);
       



        $sentencia->execute();

        $mensaje="Registro agregado con éxito";
        header("Locatio:index.php?mensaje=".$mensaje);


}

?>

<div class="card">
    <div class="card-header">
        Portada
    </div>
    <div class="card-body">
    <form action="" enctype= "multipart/form-data" method="post">

<div class="mb-3">
  <label for="componente" class="form-label">Componente:</label>
  <input type="text"
    class="form-control" name="componente" id="componente" aria-describedby="helpId" placeholder="componente">
</div>



<div class="mb-3">
  <label for="imagen" class="form-label">Imagen:</label>
  <input type="file" class="form-control" name="imagen" id="imagen" placeholder="imagen" aria-describedby="fileHelpId">
</div>


<button type="submit" class="btn btn-success">Agregar</button>
<a name="" id="" class="btn btn-primary" href="index.php" role="button">Cancelar</a>

</form>

    </div>
    <div class="card-footer text-muted">
        
    </div>
</div>






<?php

    include("../../templates/footer.php");
?>