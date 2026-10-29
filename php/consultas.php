<?php
// Historial de reparaciones de una patente (solo administradores)
require_once "auth/lib.php";

if (!esAdmin()) {
  responderJSON('inexistente', 403);
}

$datos = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $patente = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['patente_txt'] ?? ''));

  $st = $conexionApi->prepare("SELECT clientes.empresa_nyp, vehiculos.marca, vehiculos.modelo, presupuestos.total_presu, reparaciones.*
    FROM reparaciones
    LEFT JOIN clientes ON reparaciones.id_cliente = clientes.id_cliente
    LEFT JOIN vehiculos ON reparaciones.id_vehiculo = vehiculos.id_vehiculo
    LEFT JOIN presupuestos ON reparaciones.presupuesto = presupuestos.id_presu
    WHERE reparaciones.patente = ?
    ORDER BY reparaciones.fecha_trab DESC");
  $st->bind_param('s', $patente);
  $st->execute();
  $datos = $st->get_result()->fetch_all(MYSQLI_ASSOC);
}

responderJSON($datos ?: 'inexistente');
