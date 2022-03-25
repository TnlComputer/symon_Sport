<?php
require "connections/conexion.php";
error_reporting(E_ALL ^ E_NOTICE);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

$metodo= $_SERVER['REQUEST_METHOD'];
$resultado = array();

 if ($metodo==='POST') {

      $datos = json_decode(file_get_contents("php://input"), true);
      
      $patente = $_POST['patente_txt'];

      $cons="SELECT clientes.empresa_nyp, vehiculos.marca, vehiculos.modelo, reparaciones.* 
      FROM reparaciones INNER JOIN clientes ON reparaciones.id_cliente=clientes.id_cliente INNER JOIN vehiculos ON reparaciones.patente=vehiculos.patente WHERE reparaciones.patente='".$patente."'  ORDER BY reparaciones.fecha_trab DESC";


        $resultado = mysqli_query($conexionApi,$cons);
        $datos = mysqli_fetch_all($resultado,MYSQLI_ASSOC);
  }

      // var_dump($datos); // hasta aca llegan bien todos los datos

      if(!empty($datos)){
        echo json_encode($datos);
      }else{
        echo json_encode('inexistente');
      }

?>