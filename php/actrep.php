<?php
// Modificación de una reparación (admin): fechas, km, factura y observaciones. Respuestas: 'ok' / '400'
require_once "admin_lib.php";

$id = (int)($_POST['repId_txt'] ?? 0);
$fecha = campo('fecha_txt', 10);
$entrega = campo('fechaEnt_txt', 10);
if (!$id || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) || ($entrega !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $entrega))) salir('400');

q("UPDATE reparaciones SET fecha_trab = ?, fecha_entrega = NULLIF(?, ''), km = ?, factura = ?, observaciones = ?
  WHERE id_trabajo = ?", 'sssssi', $fecha, $entrega, preg_replace('/[^0-9]/', '', campo('km_txt', 10)),
  campo('factura_txt', 30), campo('obs_txt', 5000), $id);
salir('ok');
