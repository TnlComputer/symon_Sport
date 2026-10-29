<?php
// Plantilla de php/connections/conexion.php (ese archivo NO se sube a git: tiene las contraseñas).
// Copiarlo a php/connections/conexion.php y completar los datos de la base.
// En local (localhost / *.test) usa php/connections/conexion-local.php, armado igual con la base de Laragon.
$hostActual = $_SERVER['HTTP_HOST'] ?? '';
if (preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', $hostActual) || str_ends_with($hostActual, '.test')) {
	require __DIR__ . '/conexion-local.php';
} else {
	function conectarse()
	{
		$hostDb = "localhost";
		$nomDb = "NOMBRE_DE_LA_BASE";
		$userDb = "USUARIO_DE_LA_BASE";
		$passDb = "CONTRASEÑA_DE_LA_BASE";

		$conectar = new mysqli($hostDb, $userDb, $passDb, $nomDb);

		return $conectar;
	}

	$conexionApi = conectarse();
}
