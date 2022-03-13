<?php
  if (!isset($_SESSION)) { session_start(); }
    $_SESSION['datos']='';
    unset($_SESSION['datos']);
   if ($_SESSION) { 
   $datos=$_SESSION['datos']; 
  }else{
    $datos='';
  }

        if ($datos) {
      echo json_encode($datos);
        }else{
      echo json_encode('inexistente');
    }   
?>