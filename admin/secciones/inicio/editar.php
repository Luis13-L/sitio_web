<?php include("../../templates/header.php"); 
include("../../bd.php");


if(isset($_GET['txtID'])){

    
    $txtID=(isset($_GET['txtID']) )?$_GET['txtID']:"";

    $sentencia=$conexion->prepare("SELECT * FROM `tbl_inicioo` WHERE id=:id");

    $sentencia->bindParam(":id",$txtID);
   

    $sentencia->execute();

    $registro=$sentencia->fetch(PDO::FETCH_LAZY);

    //recuperar registros

    $componente=$registro['componente'];
    $imagen=$registro['imagen'];
   

}

if($_POST){

            //recibir los valores del formulario
            $txtID=(isset($_POST['txtID']))?$_POST['txtID']:"";//importante incluir
            $componente=(isset($_POST['componente']))?$_POST['componente']:"";
            $imagen=(isset($_FILES["imagen"]["name"]))?$_FILES["imagen"]["name"]:"";
    
            


            $sentencia=$conexion->prepare("UPDATE tbl_inicioo SET 
            componente= :componente
            WHERE id=:id");

            $sentencia->bindParam(":componente",$componente);
            $sentencia->bindParam(":id",$txtID);
            $sentencia->execute();


            if($_FILES["imagen"]["tmp_name"]!=""){

              $imagen=(isset($_FILES["imagen"]["name"]))?$_FILES["imagen"]["name"]:"";


              $fecha_imagen=new DateTime();
              $nombre_archivo_imagen=($imagen!="")? $fecha_imagen->getTimestamp()."_".$imagen:"";
      
              $tmp_imagen=$_FILES["imagen"]["tmp_name"];
      
              
      
                move_uploaded_file($tmp_imagen,"../../../assets/img/".$nombre_archivo_imagen);
                
                

                //borrado de imagen anterior
        
                $sentencia=$conexion->prepare("SELECT imagen FROM `tbl_inicioo` WHERE id=:id");
                $sentencia->bindParam(":id",$txtID);
                $sentencia->execute();
        
                $registro_imagen=$sentencia->fetch(PDO::FETCH_LAZY);
        
                if(isset($registro_imagen["imagen"])){
        
                    if(file_exists("../../../assets/img/".$registro_imagen["imagen"])){
        
                        unlink("../../../assets/img/".$registro_imagen["imagen"]);
                    }
        
        
                }
            
                //actualizar la imagen 
              $sentencia=$conexion->prepare("UPDATE tbl_inicioo SET imagen= :imagen WHERE id=:id");
              $sentencia->bindParam(":imagen",$nombre_archivo_imagen);
              $sentencia->bindParam(":id",$txtID);
              $sentencia->execute();
              $imagen=$nombre_archivo_imagen;
              



            }
            $mensaje="Registro modificado con éxito";
            header("Location:index.php?mensaje=".$mensaje);



}

?>


<div class="card">
    <div class="card-header">
        Puedes cambiar la imagen del componente...
    </div>
    <div class="card-body">
    <form action="" enctype= "multipart/form-data" method="post">

    <div class="mb-3">
      <label for="" class="form-label">ID</label>
      <input type="text"
        class="form-control"
        readonly 
        name="txtID" 
        id="txtID"
        value="<?php echo $txtID;?>" 
        aria-describedby="helpId" placeholder="">
      
    </div>


<div class="mb-3">
  <label for="componente" class="form-label">Componente:</label>
  <input type="text"
    class="form-control" readonly value="<?php echo $componente;?>" name="componente" id="componente" aria-describedby="helpId" placeholder="componente">
</div>



<div class="mb-3">
  <label for="imagen" class="form-label">Imagen:</label>
  
  <img width= "50" src="../../../assets/img/<?php echo $imagen?>" />
  <input type="file" class="form-control" name="imagen" id="imagen" placeholder="imagen" aria-describedby="fileHelpId">
</div>


<button type="submit" class="btn btn-success">Actualizar</button>
<a name="" id="" class="btn btn-primary" href="index.php" role="button">Cancelar</a>

</form>

    </div>
    <div class="card-footer text-muted">
        
    </div>
</div>


<?php include("../../templates/footer.php"); ?>