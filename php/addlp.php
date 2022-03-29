<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
   session_start(); 

  $fecha_txt = $_POST['fecha_txt']; 
  $id_vehiculo = $_POST['patente_slc'];
  $descripcion = $_POST['desc_txt'];
  $repuestos_txt = $_POST['repuestos_txt'];
  $mobra_txt = $_POST['mobra_txt'];
  $total_txt = $_POST['total_txt'];
  $senia_txt = $_POST['senia_txt'];
  $abonado_txt = $_POST['abonado_txt'];
  $dias_txt = $_POST['dias_txt'];
  $usado = 0;
  $nro_presu = 0;
  
  if (empty($_POST['fecIni_txt'])){ $fecIni_txt= "0000-00-00"; }else{
    $fecIni_txt = $_POST['fecIni_txt'];
  }

  if (empty($fecRet_txt)){ $fecRet_txt= "0000-00-00"; }else{
    $fecRet_txt = $_POST['fecRet_txt']; 
  }


  $consVeh=$conexionApi->query("SELECT id_cliente, patente FROM vehiculos WHERE id_vehiculo='".$id_vehiculo."' ");
  $fila = mysqli_fetch_array($consVeh);
  $id_cliente=$fila['id_cliente'];
  $patente=$fila['patente'];

  $datos="INSERT INTO presupuestos (fecha_presu, descripcion_presu, usado, patente_presu, mobra_presu,  repuestos_presu, total_presu, senia_presu, abonado_presu, dias_presu, fec_ini_presu, fec_ret_presu,  nro_presu, id_cliente, id_vehiculo) VALUES ('$fecha_txt', '$descripcion', '$usado', '$patente', '$mobra_txt', '$repuestos_txt', '$total_txt','$senia_txt', '$abonado_txt', '$dias_txt', '$fecIni_txt', '$fecRet_txt', '$nro_presu', '$id_cliente', '$id_vehiculo' )";
  $conexionApi->query($datos); 

if(!empty($datos)){
  echo json_encode('200');
 }else{
  echo json_encode('400');
 }   
?>