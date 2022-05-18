<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
  session_start(); 

  $_SESSION['datos']="";

  $metodo= $_SERVER['REQUEST_METHOD'];
  $resultado = array();

  $usuario = $_POST['user_txt'];
  $clave = $_POST['pass_txt'];

  $consUsr= "SELECT * FROM usuarios WHERE usuario='".$usuario."' AND clave = '".$clave."' ";

  $resultado = mysqli_query($conexionApi,$consUsr);
  $datos = mysqli_fetch_array($resultado,MYSQLI_ASSOC);

  $_SESSION['datos']=$datos;

  if(!empty($datos)){
    echo json_encode($datos);     
  }else{
    echo json_encode('inexistente');
  }
?>