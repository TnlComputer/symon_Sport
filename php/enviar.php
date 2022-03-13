<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
   session_start(); 

$_SESSION['datos']="";

  $usuario = $_POST['user_txt'];
  $clave = $_POST['pass_txt'];

    $consUsr=$conexionApi->query("SELECT id_user, id_cliente, nivel, nombre FROM usuarios WHERE nombre='".$usuario."' AND clave = '".$clave."' ");

    $datos = mysqli_fetch_array($consUsr);

    $_SESSION['datos']=$datos;

       if(!empty($datos)){
      echo json_encode($datos);
        }else{
      echo json_encode('inexistente');
    }   
?>