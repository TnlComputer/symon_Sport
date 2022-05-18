<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
   session_start(); 

  $fecha_txt = $_POST['fecha_txt']; 
  $id_vehiculo = $_POST['patente_slc'];
  // $id_vehiculo = 1;
  $descripcion = $_POST['desc_txt'];
  $repuestos_txt = $_POST['repuestos_txt'];
  $mobra_txt = $_POST['mobra_txt'];
  $total_txt = $_POST['total_txt'];
  $senia_txt = $_POST['senia_txt'];
  $abonado_txt = $_POST['abonado_txt'];
  $dias_txt = $_POST['dias_txt'];
  
  // if (empty($_POST['fecIni_txt'])){ $fecIni_txt= "0000-00-00"; }else{
  //   $fecIni_txt = $_POST['fecIni_txt'];
  // }

  // if (empty($fecRet_txt)){ $fecRet_txt= "0000-00-00"; }else{
  //   $fecRet_txt = $_POST['fecRet_txt']; 
  // }


  // echo "veh ".$id_vehiculo."<br>";

  $consVeh=$conexionApi->query("SELECT id_cliente, patente, marca, modelo, anio FROM vehiculos WHERE id_vehiculo='".$id_vehiculo."' ");
  $fila = mysqli_fetch_array($consVeh);
  $id_cliente=$fila['id_cliente'];
  $patente=$fila['patente'];
  $marca=$fila['marca'];
  $modelo=$fila['modelo'];
  $anio=$fila['anio'];

  // echo "clie ".$id_cliente."<br>";
  $consClie=$conexionApi->query("SELECT empresa_nyp, mail FROM clientes WHERE id_cliente='".$id_cliente."' ");
  $filaClie = mysqli_fetch_array($consClie);
  $nombre_cliente=$filaClie['empresa_nyp'];
  $email_cliente=$filaClie['mail'];

  $datos="INSERT INTO presupuestos (fecha_presu, descripcion_presu, usado, patente_presu, mobra_presu,  repuestos_presu, total_presu, senia_presu, abonado_presu, dias_presu, id_cliente, id_vehiculo) VALUES ('$fecha_txt', '$descripcion', '0', '$patente', '$mobra_txt', '$repuestos_txt', '$total_txt','$senia_txt', '$abonado_txt', '$dias_txt',  '$id_cliente', '$id_vehiculo' )";
  $conexionApi->query($datos); 

   $newId=mysqli_insert_id($conexionApi);
    // var_dump($datos);
    //  var_dump($newId);
    // echo "nombre ".$nombre_cliente."<br>";
    // echo "email ".$email_cliente."<br>";
    // echo "modelo ".$modelo."<br>";
    // echo "marca ".$marca."<br>";
    // echo "año ".$anio."<br>";
    // var_dump($datos);
    
if(!empty($datos)){
  echo json_encode($newId);
 }else{
  echo json_encode('400');
 }   

exit
?>