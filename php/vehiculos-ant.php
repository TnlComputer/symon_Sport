<?php
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

require_once "connections/conexion.php";
error_reporting(E_ALL ^ E_NOTICE);
 
//  $metodo= $_SERVER['REQUEST_METHOD'];

//   $datos = json_decode(file_get_contents($metodo), true);
//  $resultado['datos'] = json_decode($datos);
  

 $id_cliente=$_POST['id_cliente'];

// $id_cliente=$_GET['id_cliente'];

$cons="SELECT * FROM vehiculos where id_cliente='$id_cliente' ORDER BY id_vehiculo ASC";
$resultado = mysqli_query($conexionApi,$cons);
$datos = mysqli_fetch_all($resultado,MYSQLI_ASSOC);


if(!empty($datos)){
    echo json_encode($datos);
}else{
    echo json_encode('error');
}
?>