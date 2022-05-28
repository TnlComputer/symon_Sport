<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
   session_start(); 

$reparacion = $_POST["repId_txt"];
$presu = $_POST['presuER_slc'];
$fecIni = $_POST["fecha_txt"];
$obs = $_POST['obs_txt'];
$km = $_POST['km_txt'];
$fechaEnt = $_POST['fechaEnt_txt'];
$factura = $_POST['factura_txt'];

if (empty($_POST['fechaEnt_txt'])){ 
 $datosU="UPDATE reparaciones SET fecha_trab='".$fecIni."', km='".$km."',  factura='".$factura."',  observaciones='".$obs."' WHERE  id_trabajo='".$reparacion."'";

}else{
 $datosU="UPDATE reparaciones SET fecha_trab='".$fecIni."', fecha_entrega='".$fechaEnt."', km='".$km."',  factura='".$factura."',  observaciones='".$obs."' WHERE  id_trabajo='".$reparacion."'";

}

// id_trabajo
// id_cliente
// id_vehiculo
// patente
// observaciones
// fecha_trab
// presupuesto
// factura
// fecha_entrega
// km

     
// $datosU="UPDATE reparaciones SET fecha_trab='".$fecIni."', fecha_entrega='".$fechaEnt."', km='".$km."',  factura='".$factura."',  observaciones='".$obs."' WHERE  id_trabajo='".$reparacion."'";
    
$conexionApi->query($datosU);

if(!empty($datosU)){      
  echo json_encode($datosU);    
}else{    
  echo json_encode('400');      
} 
?>