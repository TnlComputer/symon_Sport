<?php
require_once "connections/conexion.php";
error_reporting(E_ALL ^ E_NOTICE);


$cons="SELECT clientes.empresa_nyp, vehiculos.marca, vehiculos.modelo, vehiculos.patente, presupuestos.id_presu 
FROM presupuestos INNER JOIN clientes ON presupuestos.id_cliente=clientes.id_cliente INNER JOIN vehiculos ON presupuestos.id_vehiculo=vehiculos.id_vehiculo WHERE usado=0 ORDER BY presupuestos.fecha_presu DESC";
$resultado = mysqli_query($conexionApi,$cons);
$datos = mysqli_fetch_all($resultado,MYSQLI_ASSOC);



if(!empty($datos)){
    echo json_encode($datos);
}else{
    echo json_encode('sinPresu'); 
}
?>