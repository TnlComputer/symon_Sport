<?php
// header('Access-Control-Allow-Origin: *');
// header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
// header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

require_once "connections/conexion.php";
error_reporting(E_ALL ^ E_NOTICE);
 
if ($SERVER['REQUEST_METHOD'] == 'GET' ){

  $cons= $conexionApi -> prepare ("SELECT * FROM vehiculos ORDER BY id_vehiculo ASC");
  $cons->execute();
  $cons->setFechMode(PDO::FETCH_ASSOC);
  header("http/1.1 200 ok")  ;
 echo json_encode($con->fetchAll); 
 exit;
}
