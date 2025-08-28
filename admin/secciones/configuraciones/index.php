listar configuraciones

<?php

    include("../../templates/header.php");
    include("../../bd.php");

    if(isset($_GET['txtID'])){
        //borar un servicio

        $txtID=(isset($_GET['txtID']) )?$_GET['txtID']:"";

        $sentencia=$conexion->prepare("DELETE FROM `tbl_confifiguraciones` WHERE id=:id");

        $sentencia->bindParam(":id",$txtID);
       
    
        $sentencia->execute();
    }

        //Seleccionar registros
        $sentencia=$conexion->prepare("SELECT * FROM `tbl_confifiguraciones`");
        $sentencia->execute();
        $lista_configuraciones=$sentencia->fetchAll(PDO::FETCH_ASSOC);

?>


<div class="card">
    <div class="card-header">
        <!--<a name="" id="" class="btn btn-primary" href="crear.php" role="button">Agregar registro</a>-->
        Configuración.
    </div>
    <div class="card-body">
        

        <div class="table-responsive-sm">
            <table class="table">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Nombre de la Configuración</th>
                        <th scope="col">Valor</th>
                        <th scope="col">Aciiones</th>

                    </tr>
                </thead>
                <tbody>
                <?php foreach ($lista_configuraciones as $registros){ ?>
                    <tr class="">
                        <td><?php echo $registros['ID'];?></td>
                        <td><?php echo $registros['nombreConfiguracion'];?></td>
                        <td><?php echo $registros['valor'];?></td>
                        <td>
                            <a name="" id="" class="btn btn-info" href="editar.php?txtID=<?php echo $registros['ID']; ?>" role="button">Editar</a> 
                            
                            <!--<a name="" id="" class="btn btn-danger" href="index.php?txtID=<?php echo $registros['ID']; ?>" role="button">Eliminar</a>-->
                        </td>

                    </tr>
                    <?php } ?>
                </tbody>
            </table>
        </div>
        
    </div>
    <div class="card-footer text-muted">
        
    </div>
</div>
<?php

    include("../../templates/footer.php");
?>