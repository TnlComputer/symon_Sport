<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
   session_start(); 

$_SESSION['datos']='';

  $usuario = $_POST['user_txt'];
  $clave = $_POST['pass_txt'];

  //  echo $usuario.'<br>';
  //  echo $clave.'<br>';

  // if($usuario === '' || $clave === ''){
  //   echo json_encode('error');
  // }else {

    // $consUsr=$conexionApi->query("SELECT id_user, nivel, nombre FROM usuarios WHERE nombre='".$usuario."' AND clave='".$clave."' ");

    $consUsr=$conexionApi->query("SELECT id_user, id_cliente, nivel, nombre FROM usuarios WHERE nombre='".$usuario."' AND clave = '".$clave."' ");

    // $cantR = mysqli_num_rows($consUsr);
    $datos = mysqli_fetch_array($consUsr);
    // $filas=mysqli_fetch_array($consUsr);
	  //   $id_user			=$filas['id_user'];
		// 	$nombre		=$filas['nombre'];
		// 	$permiso	=$filas['nivel'];

    // 	$datos=array('id_user'=>$id_user, 'nombre'=>$nombre,
		// 	'nivel'=>$permiso);

    $_SESSION['datos']=$datos;
    // var_dump ($datos);

    if ($datos) {
      echo json_encode($datos);
        }else{
      echo json_encode('inexistente');
    }   
?>