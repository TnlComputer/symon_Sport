<?php
  if (!isset($_SESSION)) { session_start(); }
    $_SESSION['datos']='';
    unset($_SESSION['datos']);
    $datos=$_SESSION['datos'];

        if ($datos) {
      echo json_encode($datos);
        }else{
      echo json_encode('inexistente');
    }   
?>