<?php
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
// // header('Content-type:application/json;charset=utf-8');

//   require_once "connections/conexion.php";
//   error_reporting(E_ALL ^ E_NOTICE);
//    session_start(); 
//    $patente = $_POST['patente_txt'];
//    $datos = array();
     
//   $consTrb=$conexionApi->query("SELECT * FROM trabajos WHERE patente='".$patente."' ");

//   // $datos=mysqli_fetch_array($consTrb);
//   // $datos=mysqli_fetch_object($consTrb);
//   // $datos=mysqli_fetch_all($consTrb);
  
//   $cant_trb=mysqli_num_rows($consTrb);
//   for($ia=0; $ia<$cant_trb; $ia++) { 
//     $filap=mysqli_fetch_array($consTrb);
// 			$id_trabajo =   $filap['id_trabajo'];
// 			$id_clie =           $filap['id_cliente'];
// 			$id_vehiculo = $filap['id_vehiculo'];
// 			$observaciones= $filap['observaciones'];
// 			$fecha_trab    = $filap['fecha_trab'];
// 			$fecha_entr    = $filap['fecha_entrega'];
// 			$factura          = $filap['factura'];
// 			$presupuesto = $filap['presupuesto'];
   
//       // $id_trabajo=
//       $datos=
//       array('id_trabajo'=>$id_trabajo, 'id_vehiculo'=>$id_vehiculo, 'id_cliente'=>$id_clie,
//        'patente'=>$patente, 
//        'observ'=>$observaciones, 'fecha_trab'=>$fecha_trab, 'fecha_entrega'=>$fecha_entr, 'factura'=>$factura, 'presupuesto'=>$presupuesto
//       );

//      }

//     //  var_dump ($datos);
//      if(!empty($datos)){
//         echo json_encode($datos);
//         }else{
//         echo json_encode('inexistente');
//       }   


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
      $resultado['datos'] =$patente;      
 
      $cons="SELECT * FROM trabajos where patente='".$resultado['datos']."' ORDER BY id_trabajo ASC";
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