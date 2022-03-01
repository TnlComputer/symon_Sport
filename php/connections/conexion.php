<?php
function conectarse()
{
	$hostDb = "localhost";
	$nomDb = "tnlcom55_ssp";
	$userDb = "tnlcom55_ssp";
	$passDb = "Camisa00!QW";

	$conectar = new mysqli($hostDb,$userDb,$passDb,$nomDb); 

	return $conectar;
}

$conexionApi = conectarse();
// echo "conectado <br><br><br><br><br><br>"
?>