<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
   session_start(); 

  $fecha_txt = $_POST['fecha_txt']; 
  $descripcion = $_POST['desc_txt'];
  $repuestos = $_POST['repuestos_txt'];
  $mobra = $_POST['mobra_txt'];
  $total = $_POST['total_txt'];
  $senia = $_POST['senia_txt'];
  $abonado = $_POST['abonado_txt'];
  $dias = $_POST['dias_txt'];
  $idVehiculo = $_POST['patente_slc'];
  $idPresu = $_POST['id_presu_txt'];
  $idCliente = $_POST['id_cliente_txt'];

  //   if (empty($_POST['fecIni_txt'])){ $fecIni_txt= "0000-00-00"; }else{
  //   $fecIni_txt = $_POST['fecIni_txt'];
  // }

  // if (empty($fecRet_txt)){ $fecRet_txt= "0000-00-00"; }else{
  //   $fecRet_txt = $_POST['fecRet_txt']; 
  // }

    $consVeh=$conexionApi->query("SELECT id_cliente, patente FROM vehiculos WHERE id_vehiculo='".$idVehiculo."' ");
  $fila = mysqli_fetch_array($consVeh);
  $patente=$fila['patente'];


     $datosU="UPDATE presupuestos SET fecha_presu='".$fecha_txt."', descripcion_presu='".$descripcion."',  patente_presu='".$patente."', mobra_presu='".$mobra."', repuestos_presu='".$repuestos."',  total_presu='".$total."', senia_presu='".$senia."', abonado_presu='".$abonado."',  dias_presu='".$dias."', id_cliente='".$idCliente."',  id_vehiculo='".$idVehiculo."' WHERE  id_presu='".$idPresu."'";

    $conexionApi->query($datosU);

    //  var_dump($datosU);
      
    if(!empty($datosU)){
        echo json_encode( '200');
      }else{
        echo json_encode('400');
      } 
?>