<?php
function conectarse()
{
	$hostDb = "localhost";
	$nomDb = "symonsport_db";
	$userDb = "symonsport_db";
	$passDb = "Camisa00!QW";

	$conectar = new mysqli($hostDb,$userDb,$passDb,$nomDb); 

	return $conectar;
}

$conexionApi = conectarse();
// echo "conectado <br><br><br><br><br><br>"
?>