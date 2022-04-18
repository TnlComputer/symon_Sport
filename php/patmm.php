<?php
require "connections/conexion.php";
error_reporting(E_ALL ^ E_NOTICE);

// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

$metodo= $_SERVER['REQUEST_METHOD'];
$resultado = array();

if ($metodo==='POST') {
      $datos = json_decode(file_get_contents("php://input"), true);
      $resultado['datos'] = json_encode($datos);
      $cliente=$resultado['datos'];

      $consPmm="SELECT clientes.empresa_nyp, vehiculos.marca, vehiculos.modelo, vehiculos.patente FROM vehiculos INNER JOIN clientes ON vehiculos.id_cliente=clientes.id_cliente WHERE vehiculos.id_vehiculo = '".$cliente."' ";

      // $datos = mysqli_fetch_array($consPmm);
        $resultado = mysqli_query($conexionApi,$consPmm);
        $datos = mysqli_fetch_array($resultado,MYSQLI_ASSOC);


}
      if(!empty($datos)){
        echo json_encode($datos);
      }else{
        echo json_encode('error');
      }
 ?>