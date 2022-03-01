<?php
require_once "connections/conexion.php";
error_reporting(E_ALL ^ E_NOTICE);

$cons="SELECT * FROM clientes ORDER BY empresa_nyp ASC";
$resultado = mysqli_query($conexionApi,$cons);
$datos = mysqli_fetch_all($resultado,MYSQLI_ASSOC);


if(!empty($datos)){
    echo json_encode($datos);
}else{
    echo json_encode([]);
}
?>