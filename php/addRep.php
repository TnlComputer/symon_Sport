<?php
require_once "connections/conexion.php";
error_reporting(E_ALL ^ E_NOTICE);

$fecha = $_POST["fecha_txt"];
$id_vehiculo = $_POST["patente_slc"];
$presu = $_POST['presu_txt'];
$obs = $_POST['obs_txt'];
$km = $_POST['km_txt'];

// validando datos

    $consPresu=$conexionApi->query("SELECT * FROM presupuestos WHERE id_presu='".$presu."' ");
    $contReg = mysqli_num_rows($consPresu);
    // echo "Reg ".$contReg."<br>";

  if ($contReg != 1){  
       echo json_encode('presu');
     }else{
       $consRep=$conexionApi->query("SELECT * FROM reparaciones WHERE presupuesto='".$presu."' ");
        $contRegP= mysqli_num_rows($consRep);
        // echo "Reg ".$contRegP."<br>";
        if ($contRegP > 0){  
             echo json_encode('usado');

        // chequear que el prusuesto sea para ese vehiculo - JOIN
        // }else{
        //   $consRepV=$conexionApi->query("SELECT * FROM reparaciones WHERE presupuesto='".$presu."' AND id_vehiculo='".$id_vehiculo."' ");
        //   $contRegPV= mysqli_num_rows($consRepV);
        //   // echo "Reg ".$contRegP."<br>";
        // if ($contRegPV > 0){  
        //      echo json_encode('usado');
        }else{

          // pido datos del vehiculo
          $cons=$conexionApi->query("SELECT * FROM vehiculos WHERE id_vehiculo='".$id_vehiculo."' ");
          
          $fila = mysqli_fetch_array($cons);
          $id_vehiculo		=$fila['id_vehiculo'];
          $Id_cliente		=$fila['Id_cliente'];
          $marca=$fila['marca'];
          $modelo=$fila['modelo'];
          $patenteVeh=$fila['patente'];
          
          $datos="INSERT INTO reparaciones (id_cliente, id_vehiculo, patente, observaciones, fecha_trab, presupuesto, km) 
              VALUES ('$id_vehiculo', '$id_vehiculo', '$patenteVeh', '$obs', '$fecha', '$presu', '$km' )";

          $conexionApi->query($datos); 

        if(!empty($datos)){
        echo json_encode("correcto");
        }else{
          echo json_encode('error');
        }
      }
    } 
?>