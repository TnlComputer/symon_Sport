<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
    if (!isset($_SESSION)) { session_start(); }

  // $datos=$_SESSION['datos'];
  // $_SESSION['Patente']='';

  $patente_txt =$_POST['patente_txt'];  
  // $id_clie = $datos['id_cliente'];

  // echo 'patente-1  '.$patIng.'<br>';
  // echo 'clie-1  '.$id_clie.'<br>';

  // $consvih=$conexionApi->query(" SELECT * FROM vehiculos WHERE id_cliente='".$id_clie."' AND patente='".$patIng."' ");
   
  // $cant_vih=mysqli_num_rows($consvih);
    // echo 'reg-vih  '.$cant_vih.'<br>';

  // $filav = mysqli_fetch_array($consvih);
  // $id_vehiv=$filav['id_vehiculo'];

  //  $id_clie=$filav['id_cliente'];
  //  echo 'vih  '.$id_vehiv.'<br>';
  //  echo 'clie-2  '.$id_clie.'<br>';

//  $consP="SELECT * FROM trabajos WHERE patente='.patente_txt.' ";
//  $resultadoP = mysqli_query($conexionApi,$consP);
//  $datosP = mysqli_fetch_all($resultadoP,MYSQLI_ASSOC);

    $constrb=$conexionApi->query(" SELECT * FROM trabajos WHERE patente='".$patente_txt."' ");
      $datosP=mysqli_fetch_all($constrb);

    // $cant_trb=mysqli_num_rows($constrb);
    //  echo 'reg-trb  '.$cant_trb.'<br>';

  	// for($ia=0; $ia<$cant_trb; $ia++) { 
    // $filap=mysqli_fetch_array($constrb);
		// 	$id_clie =$filap['id_cliente'];
		// 	$id_vehiculo =$filap['id_vehiculo'];
		// 	$observ =$filap['observaciones'];
		// 	$fecha_trab = $filap['fecha_trab'];
		// 	$fecha_entr = $filap['fecha_entrega'];
		// 	$factura = $filap['factura'];
		// 	$presupuesto = $filap['presupuesto'];
      
    //   echo  '<br>Vih '.$id_vehiculo.'<br>';
      // echo 'clie '.$id_clie.'<br>';
      // echo 'pat '.$patente_txt.'<br>';
      // echo 'obs '.$observ.'<br>';
      // echo 'fec T '.$fecha_trab.'<br>';
      // echo 'fec E '.$fecha_entr.'<br>';
      // echo 'FC '.$factura.'<br>';
      // echo 'pres '.$presupuesto.'<br><br>';

      // $datos[$ia]=
      // array('id_vehiculo'=>$id_vehiculo, 'id_cliente'=>$id_clie, 'patente'=>$patente_txt, 'observ'=>$observ, 'fecha_trab'=>$fecha_trab, 'fecha_entrega'=>$fecha_entr, 'factura'=>$factura, 'presupuesto'=>$presupuesto);
      // }
      // // $datosP=$datosVP;
      // $_SESSION['patente']=$datosP;
      // // var_dump ($_SESSION['patente']);
      
        var_dump ($datosP);

       if(!empty($datosP)){
          echo json_encode($datosP);
          }else{
          echo json_encode('inexistente');
        }   
    ?>