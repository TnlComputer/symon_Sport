<?php
function conectarse()
{
	$servidor = "localhost";
	$usuario_conexion = "tnlcom55_ssp";
	$password_conexion = "Camisa00!QW";
	$db = "tnlcom55_ssp";

	$conectar = new mysqli($servidor,$usuario_conexion,$password_conexion,$db); 

	return $conectar;
}

$conexionApi = conectarse();
// echo "conectado <br><br><br><br><br><br>"
?>