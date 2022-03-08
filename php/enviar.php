<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);

  $usuario = $_POST['user_txt'];
  $clave = $_POST['pass_txt'];

  // if($usuario === '' || $clave === ''){
  //   echo json_encode('error');
  // }else {

    $cons=$conexionApi->query("SELECT * FROM usuarios WHERE nombre='".$usuario."' || clave='".$clave."' ");
    $cantR = mysqli_num_rows($cons);
    // $datos = mysqli_fetch_all($cons);
    $datos = mysqli_fetch_array($cons);

    if (!isset($_SESSION)) { session_start(); }
      $_SESSION['datos']=$datos;

    if ($cantR > 0) {
      echo json_encode($datos);
        }else{
      echo json_encode('inexistente');
    }   
?>