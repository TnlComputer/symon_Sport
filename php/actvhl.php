<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
   session_start(); 

      $vehiculo = $_POST['id_vehiculo_txt']; 
      $cliente = $_POST['id_cliente_txt']; 
      $patente = $_POST['patente_txt'];
      $marca = $_POST['marca_txt'];
      $modelo = $_POST['modelo_txt'];
      $anio = $_POST['anio_txt'];

     $datosU="UPDATE vehiculos SET id_cliente='".$cliente."', patente='".$patente."', marca='".$marca."', modelo='".$modelo."',  anio='".$anio."' WHERE  id_vehiculo='".$vehiculo."'";
  
    $conexionApi->query($datosU);
      
        if(!empty($datosU)){
        echo json_encode($cliente);
        }else{
          echo json_encode('error');
        }
?>