<?php


$servidor="localhost:3306";
$baseDeDatos="sitio-web";
$usuario="root";
$contrasenia="";


try{

    $conexion=new PDO("mysql:host=$servidor;dbname=$baseDeDatos",$usuario,$contrasenia);
    //echo "conexion realizada";

}catch(Exception $error){
    echo $error->getMessage();
}

?>