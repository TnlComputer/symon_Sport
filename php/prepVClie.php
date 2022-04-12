<?php
require "connections/conexion.php";
error_reporting(E_ALL ^ E_NOTICE);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

$metodo= $_SERVER['REQUEST_METHOD'];
$resultado = array();

    // para el php a ver si resuelvo lo del PUT
    // if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // parse_str(file_get_contents("php://input"),$put_vars);
    // echo $put_vars['foo'];

 if ($metodo==='POST') {

      // $patente = json_decode(file_get_contents("php://input"), true);
      $presupuesto = file_get_contents("php://input");
      
      //       $id_cliente = $_POST['id'];
      //  echo 'ID'.$presupuesto.'<br>';

      $cons="SELECT * FROM presupuestos WHERE id_presu='".$presupuesto."'  ";

       $resultado = mysqli_query($conexionApi,$cons);
       $datos = mysqli_fetch_all($resultado,MYSQLI_ASSOC);

        // var_dump($datos); // hasta aca llegan bien todos los datos
      
}
      if(!empty($datos)){
        echo json_encode($datos);
      }else{
        echo json_encode('inexistente');
      }

?>