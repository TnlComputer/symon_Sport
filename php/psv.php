<?php
require_once "connections/conexion.php";
error_reporting(E_ALL ^ E_NOTICE);

$metodo= $_SERVER['REQUEST_METHOD'];
$resultado = array();

if ($metodo==='POST') {
      $datos = json_decode(file_get_contents("php://input"), true);
      $resultado['datos'] = json_encode($datos);
      $presu=$resultado['datos'];

$cons=$conexionApi->query("SELECT clientes.empresa_nyp, vehiculos.marca, vehiculos.modelo, vehiculos.patente, presupuestos.id_presu 
FROM presupuestos INNER JOIN clientes ON presupuestos.id_cliente=clientes.id_cliente INNER JOIN vehiculos ON presupuestos.id_vehiculo=vehiculos.id_vehiculo WHERE presupuestos.id_presu='".$presu."'");
      $datos = mysqli_fetch_array($cons);
}
if(!empty($datos)){
    echo json_encode($datos);
}else{
    echo json_encode('error'); 
}
?>