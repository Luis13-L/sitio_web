crear servicios

<?php

    include("../../templates/header.php");
    include("../../bd.php");


    if($_POST){


        //recibir los valores del formulario

        $usuario=(isset($_POST['usuario']))?$_POST['usuario']:"";
        $password=(isset($_POST['password']))?$_POST['password']:"";
        $correo=(isset($_POST['correo']))?$_POST['correo']:"";



        $sentencia=$conexion->prepare("INSERT INTO `tbl_usuarios` 
        (`ID`, `usuario`, `password`, `correo`) 
        VALUES (NULL, :usuario, :password, :correo);");

        $sentencia->bindParam(":usuario",$usuario);
        $sentencia->bindParam(":password",$password);
        $sentencia->bindParam(":correo",$correo);




        $sentencia->execute();

        $mensaje="Registro agregado con éxito";
        header("Locatio:index.php?mensaje=".$mensaje);
    }

?>

<div class="card">
    <div class="card-header">
        Usuario
    </div>
    <div class="card-body">
        
    <form action="" enctype= "multipart/form-data" method="post">

    <div class="mb-3">
      <label for="" class="form-label">Nombre del Usuario:</label>
      <input type="text"
        class="form-control" name="usuario" id="usuario" aria-describedby="helpId" placeholder="Nombre del usuario">
      
    </div>

    <div class="mb-3">
      <label for="" class="form-label">Password</label>
      <input type="password"
        class="form-control" name="password" id="password" aria-describedby="helpId" placeholder="Contraseña">
      
    </div>

    <div class="mb-3">
      <label for="correo" class="form-label">Correo</label>
      <input type="email" class="form-control" name="correo" id="correo" aria-describedby="emailHelpId" placeholder="correo">
      
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