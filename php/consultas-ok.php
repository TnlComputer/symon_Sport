<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
// header('Content-type:application/json;charset=utf-8');

  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
   session_start(); 
   $patente = $_POST['patente_txt'];
   $datos = array();
     
  $consUsr=$conexionApi->query("SELECT * FROM trabajos WHERE patente='".$patente."' ");

  $cant_trb=mysqli_num_rows($consUsr);
  for($ia=0; $ia<$cant_trb; $ia++) { 
    $filap=mysqli_fetch_array($consUsr);
			$id_trabajo =   $filap['id_trabajo'];
			$id_clie =           $filap['id_cliente'];
			$id_vehiculo =   $filap['id_vehiculo'];
			$observ =          $filap['observaciones'];
			$fecha_trab =    $filap['fecha_trab'];
			$fecha_entr =    $filap['fecha_entrega'];
			$factura =          $filap['factura'];
			$presupuesto = $filap['presupuesto'];
   
      // $id_trabajo=
      $datos=
      array('id_vehiculo'=>$id_vehiculo, 'id_cliente'=>$id_clie, 'patente'=>$patente, 'observ'=>$observ, 'fecha_trab'=>$fecha_trab, 'fecha_entrega'=>$fecha_entr, 'factura'=>$factura, 'presupuesto'=>$presupuesto);

      // $datos=array($id_trabajo);

     }

    //  var_dump ($datos);
     if(!empty($datos)){
        echo json_encode($datos);
        }else{
        echo json_encode('inexistente');
      }   
?>