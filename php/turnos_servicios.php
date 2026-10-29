<?php
require_once "turnos_lib.php";

$config = turnosConfig($conexionApi);
$res = $conexionApi->query("SELECT id_servicio, nombre, descripcion, duracion_min, margen_min FROM turnos_servicios WHERE activo = 1 ORDER BY orden, nombre");

responder([
  'servicios' => $res->fetch_all(MYSQLI_ASSOC),
  'dias_max' => $config['dias_max'],
]);
