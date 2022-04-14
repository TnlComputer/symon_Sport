<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
   session_start(); 

   $idCliente = file_get_contents("php://input");
  
    $consUC="SELECT * FROM usuarios WHERE id_cliente='".$idCliente."' ";

      $resultado = mysqli_query($conexionApi,$consUC);
      $datos = mysqli_fetch_all($resultado,MYSQLI_ASSOC);

    if(!empty($datos)){
      echo json_encode($datos);
        }else{
      echo json_encode('inexistente');
    }   
?>