<?php
// Ingreso de una reparación a partir de un presupuesto (admin). Respuestas: 'ok' / '400'
// Además queda en el historial del vehículo (por patente) con su kilometraje.
require_once "admin_lib.php";

$idPresu = (int)($_POST['presuAR_slc'] ?? 0);
$fecha = campo('fecha_txt', 10);
$obs = campo('obs_txt', 5000);
$km = preg_replace('/[^0-9]/', '', campo('km_txt', 10));
if (!$idPresu || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) salir('400');

$p = q("SELECT p.id_presu, p.descripcion_presu, p.id_vehiculo, v.id_cliente, v.patente
  FROM presupuestos p JOIN vehiculos v ON v.id_vehiculo = p.id_vehiculo
  WHERE p.id_presu = ? AND p.usado = 0", 'i', $idPresu)[0] ?? null;
if (!$p) salir('400');

q("INSERT INTO reparaciones (id_cliente, id_vehiculo, patente, observaciones, fecha_trab, presupuesto, km)
  VALUES (?, ?, ?, ?, ?, ?, ?)", 'iisssis', $p['id_cliente'], $p['id_vehiculo'], $p['patente'], $obs, $fecha, $idPresu, $km);
q("UPDATE presupuestos SET usado = 1, fec_ini_presu = ? WHERE id_presu = ?", 'si', $fecha, $idPresu);

// historial y kilometraje del vehículo
$kmNum = $km !== '' ? (int)$km : null;
$idUsuario = (int)usuarioActual()['id_user'];
$descripcion = mb_substr($p['descripcion_presu'] ?: 'Reparación', 0, 255);
q("INSERT INTO fichas_historial (patente, fecha, km, descripcion, detalle, realizado_por, id_presu, id_user_carga)
  VALUES (?, ?, ?, ?, NULLIF(?, ''), 'symon', ?, ?)", 'ssissii', $p['patente'], $fecha, $kmNum, $descripcion, $obs, $idPresu, $idUsuario);
if ($kmNum) {
  q("INSERT INTO fichas_km (patente, km, fecha, origen, id_user_carga) VALUES (?, ?, ?, 'taller', ?)", 'sisi', $p['patente'], $kmNum, $fecha, $idUsuario);
  q("INSERT INTO fichas_vehiculo (patente, km_actual, km_fecha) VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE km_actual = IF(km_actual IS NULL OR VALUES(km_actual) >= km_actual, VALUES(km_actual), km_actual),
    km_fecha = IF(km_actual = VALUES(km_actual), VALUES(km_fecha), km_fecha)", 'sis', $p['patente'], $kmNum, $fecha);
}
salir('ok');
