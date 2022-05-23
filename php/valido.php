<?php
 session_start(); 
 
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