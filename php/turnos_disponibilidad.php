<?php
// GET ?servicio=ID&desde=YYYY-MM-DD&dias=7
require_once "turnos_lib.php";

$idServicio = (int)($_GET['servicio'] ?? 0);
$desde = $_GET['desde'] ?? date('Y-m-d');
$dias = min(14, max(1, (int)($_GET['dias'] ?? 7)));

$servicio = buscarServicio($conexionApi, $idServicio);
if (!$servicio) {
  responder(['error' => t('Servicio inexistente')], 400);
}
if (!fechaValida($desde)) {
  responder(['error' => t('Fecha inválida')], 400);
}

$config = turnosConfig($conexionApi);
$hoy = date('Y-m-d');
$ultimo = date('Y-m-d', strtotime("+{$config['dias_max']} days"));
if ($desde < $hoy) {
  $desde = $hoy;
}

$resultado = [];
for ($i = 0; $i < $dias; $i++) {
  $fecha = date('Y-m-d', strtotime("$desde +$i days"));
  if ($fecha > $ultimo) {
    $resultado[] = ['fecha' => $fecha, 'abierto' => false, 'motivo' => t('Sin agenda'), 'slots' => []];
    continue;
  }
  $resultado[] = ['fecha' => $fecha] + slotsDelDia($conexionApi, $fecha, minutosServicio($servicio), $config);
}

responder([
  'servicio' => $servicio,
  'intervalo_min' => $config['intervalo_min'],
  'hoy' => $hoy,
  'ultimo' => $ultimo,
  'dias' => $resultado,
]);
