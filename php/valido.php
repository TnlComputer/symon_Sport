<?php
 session_start(); 
    $datos=$_SESSION['datos'];

        if ($datos) {
      echo json_encode($datos);
        }else{
      echo json_encode('inexistente');
    }   
?>