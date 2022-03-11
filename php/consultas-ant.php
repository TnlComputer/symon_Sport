<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
  session_start(); 

  $datos=$_SESSION['datos'];

  $_SESSION['Patente']='';

  $patIng =$_POST['patente_txt'];  

  $id_clie = $datos['id_cliente'];
  // echo 'patente-1  '.$patIng.'<br>';
  // echo 'clie-1  '.$id_clie.'<br>';

  $consvih=$conexionApi->query(" SELECT * FROM vehiculos WHERE id_cliente='".$id_clie."' AND patente='".$patIng."' ");
   
  $cant_vih=mysqli_num_rows($consvih);
  //  echo 'reg-vih  '.$cant_vih.'<br>';

  $filav = mysqli_fetch_array($consvih);
  $id_vehiv=$filav['id_vehiculo'];

  // $id_clie=$filav['id_cliente'];
  // echo 'vih  '.$id_vehiv.'<br>';
  // echo 'clie  '.$id_clie.'<br>';

  $constrb=$conexionApi->query("SELECT * FROM trabajos WHERE id_vehiculo='".$id_vehiv."'");

  
  // $datos = mysqli_fetch_all($constrb);
  
  $cant_trb=mysqli_num_rows($constrb);
  // echo 'reg-trb  '.$cant_trb.'<br>';

  
  	for($ia=0;$ia<$cant_trb;$ia++){ 
    $filap = mysqli_fetch_array($constrb);
			$observ =$filap['observaciones'];
			$fecha_trab = $filap['fecha_trab'];
			$fecha_entr = $filap['fecha_entrega'];
			$factura = $filap['factura'];
			$presupuesto = $filap['presupuesto'];
      
      // echo  'Vih '.$id_vehiv.'<br>';
      // echo 'clie '.$id_clie.'<br>';
      // echo 'pat '.$patIng.'<br>';
      // echo 'obs '.$observ.'<br>';
      // echo 'fec T '.$fecha_trab.'<br>';
      // echo 'fec E '.$fecha_entr.'<br>';
      // echo 'FC '.$factura.'<br>';
      // echo 'pres '.$presupuesto.'<br>';

      $datos = array('id_vehiculo' => $id_vehiv,
        'id_cliente' => $id_clie,
        'patente' => $patIng,
        'observ' => $observ,
        'fecha_trab' => $fecha_trab, 
        'fecha_entrega' => $fecha_entr, 
        'factura' => $factura, 
        'presupuesto' => $presupuesto 
        );
      }
      
      // var_dump ($datos);
      //  echo  'Vih '.$id_vehiv.'<br>';
      //  echo 'clie '.$id_clie.'<br>';
      //  echo 'pat '.$patIng.'<br>';
      //  echo 'obs '.$observ.'<br>';
      //  echo 'fec T '.$fecha_trab.'<br>';
      //  echo 'fec E '.$fecha_entr.'<br>';
      //  echo 'FC '.$factura.'<br>';
      //  echo 'pres '.$presupuesto.'<br>';
      // echo '<br> Datos P '.$datosp.'<br>';

      $_SESSION['patente']=$datos;
      
    if ($datos) {
      echo json_encode($datos);
        }else{
      echo json_encode('inexistente');
    }   
    ?>