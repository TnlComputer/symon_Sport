<?php

function conexion(){
    return mysqli_connect('localhost','root','','contactos');
}


$conexion = conexion();
$sql = "SELECT id_contacto,nombre_contacto,telefono,correo FROM contacto";
$resultado = mysqli_query($conexion,$sql);
$datos = mysqli_fetch_all($resultado,MYSQLI_ASSOC);

if(!empty($datos)){
    echo json_encode($datos);
}else{
    echo json_encode([]);
}