<?php
require "connections/conexion.php";
error_reporting(E_ALL ^ E_NOTICE);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

$metodo= $_SERVER['REQUEST_METHOD'];
$resultado = array();

switch ($metodo) {
    case 'POST' :
      $datos = json_decode(file_get_contents("php://input"), true);
      $resultado['datos'] = json_encode($datos);

      $cons="SELECT * FROM vehiculos where id_cliente='".$resultado['datos']."' ORDER BY id_vehiculo ASC";
        $resultado = mysqli_query($conexionApi,$cons);
        $datos = mysqli_fetch_all($resultado,MYSQLI_ASSOC);
      breack;
    }
      if(!empty($datos)){
        echo json_encode($datos);
      }else{
        echo json_encode('error');
      }
    exit;



          // echo "Datos :".$datos;
      // echo "Resultado :".$resultado['datos'];
      // $cons="SELECT * FROM vehiculos where id_cliente='".$resultado['datos']."' ORDER BY id_vehiculo ASC";

    // default:
    //   echo "llego vacio :".$metodo;
    //   breack;
  






































// // $id_cliente=$_GET['id_cliente'];
// // $cons="SELECT * FROM vehiculos where id_cliente='$id_cliente' ORDER BY id_vehiculo ASC";

// $cons="SELECT * FROM vehiculos ORDER BY id_vehiculo ASC";
// $resultado = mysqli_query($conexionApi,$cons);
// $datos = mysqli_fetch_all($resultado,MYSQLI_ASSOC);

// if(!empty($datos)){
//   echo json_encode($datos);
// }else{
//   echo json_encode('error');
// }
// // header('Access-Control-Allow-Origin: *');
// // header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
// // header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

// //  $metodo= $_SERVER['REQUEST_METHOD'];
// // echo 'Medoto -> '.$metodo.'<br>';
// //   $datos = json_decode(file_get_contents($metodo), true);
// //  $resultado['datos'] = json_decode($datos);
// //  $resultado=array();

// // echo 'datos -> '.$datos.'<br>';
// // echo 'Res -> '.$resultado.'<br>';
// //  $id_cliente=$_POST['id_cliente'];

// ?>