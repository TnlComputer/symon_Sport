<?php
require_once "connections/conexion.php";
error_reporting(E_ALL ^ E_NOTICE);

$reparacion = $_POST["id_txt"];
$fecIni = $_POST["fecha_txt"];
$presu = $_POST['presuAR_slc'];
$obs = $_POST['obs_txt'];
$km = $_POST['km_txt'];

    
$consPresu=$conexionApi->query("SELECT * FROM presupuestos WHERE id_presu='".$presu."' ");      
$filaP = mysqli_fetch_array($consPresu);          
$vehiculo		=$filaP['id_vehiculo'];
          
// pido datos del vehiculo
$consV=$conexionApi->query("SELECT * FROM vehiculos WHERE id_vehiculo='".$vehiculo."' ");      
$filaV = mysqli_fetch_array($consV);
$id_vehiculo		=$filaV['id_vehiculo'];
$Id_cliente		=$filaV['id_cliente'];
$marca=$filaV['marca'];
$modelo=$filaV['modelo'];
$patenteVeh=$filaV['patente'];
    
$datos="INSERT INTO reparaciones (id_cliente, id_vehiculo, patente, observaciones, fecha_trab, presupuesto, km) VALUES ('$Id_cliente', '$id_vehiculo', '$patenteVeh', '$obs', '$fecIni', '$presu', '$km' )";
      
$conexionApi->query($datos); 

if(!empty($datos)){                    
  $udpPresu="UPDATE presupuestos SET usado=1, fec_ini_presu='".$fecIni."' WHERE  id_presu='".$presu."' ";       
  $conexionApi->query($udpPresu);
  echo json_encode($datos);
}else{        
  echo json_encode("400");        
}
?>