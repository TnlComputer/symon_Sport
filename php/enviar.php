<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
   session_start(); 

$_SESSION['datos']="";

$metodo= $_SERVER['REQUEST_METHOD'];
$resultado = array();

  $usuario = $_POST['user_txt'];
  $clave = $_POST['pass_txt'];

    // $consUsr=$conexionApi->query("SELECT id_user, id_cliente, nivel, nombre, usuario, apellido, dni FROM usuarios WHERE nombre='".$usuario."' AND clave = '".$clave."' ");
    $consUsr= "SELECT * FROM usuarios WHERE usuario='".$usuario."' AND clave = '".$clave."' ";

    // $datos = mysqli_fetch_array($consUsr);
        $resultado = mysqli_query($conexionApi,$consUsr);
        $datos = mysqli_fetch_array($resultado,MYSQLI_ASSOC);

    $_SESSION['datos']=$datos;

       if(!empty($datos)){
      echo json_encode($datos);
        }else{
      echo json_encode('inexistente');
    }   
?>