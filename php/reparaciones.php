<?php
require_once "connections/conexion.php";
error_reporting(E_ALL ^ E_NOTICE);

      $cons="SELECT clientes.empresa_nyp, vehiculos.marca, vehiculos.modelo, reparaciones.* 
      FROM reparaciones INNER JOIN clientes ON reparaciones.id_cliente=clientes.id_cliente INNER JOIN vehiculos ON reparaciones.patente=vehiculos.patente ORDER BY reparaciones.fecha_trab DESC";
        $resultado = mysqli_query($conexionApi,$cons);
        $datos = mysqli_fetch_all($resultado,MYSQLI_ASSOC);

        // var_dump($datos);

if(!empty($datos)){
    echo json_encode($datos);
}else{
    echo json_encode('inexistente');
}
?>