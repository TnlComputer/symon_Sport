<?php
require "connections/conexion.php";
error_reporting(E_ALL ^ E_NOTICE);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

$metodo= $_SERVER['REQUEST_METHOD'];
$resultado = array();

 if ($metodo==='POST') {

      // $id_cliente = json_decode(file_get_contents("php://input"), true);
      $id_cliente = file_get_contents("php://input");
      
//       $id_cliente = $_POST['id'];
// echo 'ID'.$id_cliente.'<br>';

      $cons="SELECT * FROM vehiculos WHERE id_cliente='".$id_cliente."'  ";
        $resultado = mysqli_query($conexionApi,$cons);
        $datos = mysqli_fetch_all($resultado,MYSQLI_ASSOC);
  }

      // var_dump($id_cliente); // hasta aca llegan bien todos los datos

      if(!empty($datos)){
        echo json_encode($datos);
      }else{
        echo json_encode('inexistente');
      }

?>