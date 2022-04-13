<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);

  $idCliente = file_get_contents("php://input");
  
    $consUC=$conexionApi->query("SELECT * FROM usuarios WHERE id_cliente='".$idCliente."'");

    $datosUC = mysqli_fetch_array($consUC);

    if(!empty($datosUC)){
      echo json_encode($datosUC);
        }else{
      echo json_encode('inexistente');
    }   
?>