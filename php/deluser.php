<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
   session_start(); 

    $idUser = json_decode(file_get_contents("php://input"), true);

     $consDU=$conexionApi->query("SELECT * FROM usuarios WHERE id_user='".$idUser."' ");
     $fila = mysqli_fetch_array($consDU); 
     $idCliente=$fila['id_cliente'];

     $datosD="DELETE FROM usuarios WHERE  id_user='".$idUser."'"; 

     $conexionApi->query($datosD);     

     if(!empty($datosD)){
        echo json_encode( $idCliente);
      }else{
        echo json_encode('400');
      }   
?>