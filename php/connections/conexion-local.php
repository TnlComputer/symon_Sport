<?php
function conectarse()
{
	$servidor = "localhost";
	$usuario_conexion = "root";
	$password_conexion = "";
	$db = "tnlcom55_ssp";

	$conectar = new mysqli($servidor,$usuario_conexion,$password_conexion,$db); 

	return $conectar;
}

$conexionApi = conectarse();
//  echo "conectado <br><br><br><br><br><br>"
?>