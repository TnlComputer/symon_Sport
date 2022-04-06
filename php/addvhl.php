<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
   session_start(); 

     
      $cliente = $_POST['id_cliente_txt']; 
      $patente = $_POST['patente_txt'];
      $marca = $_POST['marca_txt'];
      $modelo = $_POST['modelo_txt'];
      $anio = $_POST['anio_txt'];

      $consVClie=$conexionApi->query("SELECT * FROM vehiculos WHERE patente='".$patente."'AND id_cliente='".$cliente."'");
      $nroreg = mysqli_num_rows($consVClie);
      
      if ($nroreg === 0){
         $fila = mysqli_fetch_array($consVClie);
        $id_cliente=$fila['id_cliente'];
        
         $datos="INSERT INTO vehiculos (id_cliente, patente, marca, modelo, anio) VALUES ('$cliente','$patente', '$marca', '$modelo', '$anio')";
        
        $conexionApi->query($datos); 

      //   var_dump($nroreg);
      //   var_dump($datos);
            
        if(!empty($datos)){
        echo json_encode($cliente);
        }else{
          echo json_encode('error');
        }
      }else{
      echo json_encode('existe');
    }   
      //   if(!empty($datos)){
      //   echo json_encode( '200');
      // }else{
      //   echo json_encode('400');
      // }  
?>