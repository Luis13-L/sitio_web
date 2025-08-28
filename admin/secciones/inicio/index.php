
<?php
    include("../../bd.php");
    include("../../templates/header.php");

    if(isset($_GET['txtID'])){

    
        $txtID=(isset($_GET['txtID']) )?$_GET['txtID']:"";

        //borrado de imagen

        $sentencia=$conexion->prepare("SELECT imagen FROM `tbl_inicioo` WHERE id=:id");
        $sentencia->bindParam(":id",$txtID);
        $sentencia->execute();

        $registro_imagen=$sentencia->fetch(PDO::FETCH_LAZY);

        if(isset($registro_imagen["imagen"])){

            if(file_exists("../../../assets/img/".$registro_imagen["imagen"])){

                unlink("../../../assets/img/".$registro_imagen["imagen"]);
            }


        }


        //botrrar registro
        $sentencia=$conexion->prepare("DELETE FROM `tbl_inicioo` WHERE id=:id");
        $sentencia->bindParam(":id",$txtID);
        $sentencia->execute();

    }


    //Seleccionar registros
    $sentencia=$conexion->prepare("SELECT * FROM `tbl_inicioo`");
    $sentencia->execute();
    $lista_inicioo=$sentencia->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="card-header">
    <a name="" id="" class="btn btn-primary" href="crear.php" role="button">Agregar registro</a>
        Puedes editar los componentes...
    </div>
    <div class="card-body">
        <div class="table-responsive-sm">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Componente</th>
                        <th scope="col">Imagen</th>
                        <th scope="col">Acciones</th>



                    </tr>
                </thead>
                <tbody>

                <?php foreach ($lista_inicioo as $registros){ ?>

                    <tr class="">
                        <td scope="col"><?php echo $registros['ID'];?></td>
                        <td scope="col"><?php echo $registros['componente'];?></td>
                        
                        <td scope="col">
                            
                            <img width= "50" src="../../../assets/img/<?php echo $registros['imagen'];?>" />
                    
                    
                        </td>
                        
                        <td scope="col">

                            <a name="" id="" class="btn btn-info" href="editar.php?txtID=<?php echo $registros['ID']; ?>" role="button">Editar</a>
                            <a name="" id="" class="btn btn-danger" href="index.php?txtID=<?php echo $registros['ID']; ?>" role="button">Eliminar</a>
                        </td>
                    </tr>
                   <?php } ?>
                </tbody>
            </table>
        </div>
        
       
    </div>
   
</div>


<?php

    include("../../templates/footer.php");
?>