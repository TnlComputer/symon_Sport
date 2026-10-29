<?php
// Agenda de turnos para el administrador
// GET ?desde=YYYY-MM-DD&hasta=YYYY-MM-DD   -> turnos/autos en el taller en ese período
// POST accion=estado    id, estado
// POST accion=extender  id, fecha_fin, hora_fin, motivo_demora   (demora, espera de repuesto, etc.)
// POST accion=alta      auto que llegó sin turno: id_servicio, fecha, hora_inicio, fecha_fin, hora_fin, nombre, telefono, patente...
require_once "turnos_lib.php";

if (($_SESSION['datos']['rol'] ?? '') !== 'admin') {
  responder(['error' => 'No autorizado'], 403);
}

$hora = fn($k) => preg_match('/^\d{2}:\d{2}$/', $_POST[$k] ?? '') ? $_POST[$k] : null;
$texto = fn($k, $max) => mb_substr(trim($_POST[$k] ?? ''), 0, $max);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $accion = $_POST['accion'] ?? 'estado';
  $id = (int)($_POST['id'] ?? 0);

  if ($accion === 'estado') {
    $estado = $_POST['estado'] ?? '';
    if (!in_array($estado, ['pendiente', 'confirmado', 'cancelado', 'atendido'], true)) {
      responder(['error' => 'Estado inválido'], 422);
    }
    $st = $conexionApi->prepare("UPDATE turnos SET estado = ? WHERE id_turno = ?");
    $st->bind_param('si', $estado, $id);
    $st->execute();
    responder(['ok' => true]);
  }

  if ($accion === 'extender') {
    $fechaFin = $_POST['fecha_fin'] ?? '';
    $horaFin = $hora('hora_fin');
    $motivo = $texto('motivo_demora', 255);
    if (!fechaValida($fechaFin) || !$horaFin) {
      responder(['error' => 'Indicá la nueva fecha y hora de salida.'], 422);
    }
    $st = $conexionApi->prepare("UPDATE turnos SET fecha_fin = ?, hora_fin = ?, motivo_demora = NULLIF(?, '')
      WHERE id_turno = ? AND TIMESTAMP(fecha, hora_inicio) < TIMESTAMP(?, ?)");
    $st->bind_param('sssiss', $fechaFin, $horaFin, $motivo, $id, $fechaFin, $horaFin);
    $st->execute();
    if ($st->affected_rows === 0) {
      responder(['error' => 'La salida tiene que ser posterior al ingreso.'], 422);
    }
    responder(['ok' => true]);
  }

  if ($accion === 'alta') {
    $idServicio = (int)($_POST['id_servicio'] ?? 0);
    $fecha = $_POST['fecha'] ?? '';
    $horaIni = $hora('hora_inicio');
    $fechaFin = $_POST['fecha_fin'] ?? '';
    $horaFin = $hora('hora_fin');
    $nombre = $texto('nombre', 100);
    $telefono = $texto('telefono', 30);
    $patente = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $texto('patente', 15)));
    $vehiculo = $texto('vehiculo', 100);
    $motivo = $texto('motivo_demora', 255);
    if (!fechaValida($fecha) || !$horaIni || !fechaValida($fechaFin) || !$horaFin || "$fecha $horaIni" >= "$fechaFin $horaFin") {
      responder(['error' => 'Revisá el ingreso y la salida estimada.'], 422);
    }
    if ($nombre === '' || $patente === '') {
      responder(['error' => 'Completá al menos nombre y patente.'], 422);
    }
    $st = $conexionApi->prepare("INSERT INTO turnos (id_servicio, fecha, hora_inicio, fecha_fin, hora_fin, nombre, telefono, patente, vehiculo, motivo_demora, estado, origen)
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), 'confirmado', 'taller')");
    $st->bind_param('isssssssss', $idServicio, $fecha, $horaIni, $fechaFin, $horaFin, $nombre, $telefono, $patente, $vehiculo, $motivo);
    $st->execute();
    responder(['ok' => true, 'id' => $conexionApi->insert_id]);
  }

  responder(['error' => 'Acción desconocida'], 400);
}

$desde = $_GET['desde'] ?? date('Y-m-d');
$hasta = $_GET['hasta'] ?? date('Y-m-d', strtotime('+14 days'));
if (!fechaValida($desde) || !fechaValida($hasta)) {
  responder(['error' => 'Fecha inválida'], 400);
}

// turnos que ingresan en el período o autos que siguen en el taller durante el período
$st = $conexionApi->prepare("SELECT t.id_turno, t.fecha, TIME_FORMAT(t.hora_inicio, '%H:%i') AS hora_inicio,
    t.fecha_fin, TIME_FORMAT(t.hora_fin, '%H:%i') AS hora_fin, t.nombre, t.telefono, t.email, t.patente,
    t.vehiculo, t.comentario, t.motivo_demora, t.estado, t.origen, t.creado, s.nombre AS servicio
  FROM turnos t
  JOIN turnos_servicios s ON s.id_servicio = t.id_servicio
  WHERE t.fecha <= ? AND t.fecha_fin >= ?
  ORDER BY t.fecha, t.hora_inicio");
$st->bind_param('ss', $hasta, $desde);
$st->execute();
$turnos = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$servicios = $conexionApi->query("SELECT id_servicio, nombre FROM turnos_servicios ORDER BY orden, nombre")->fetch_all(MYSQLI_ASSOC);
$config = turnosConfig($conexionApi);

responder(['turnos' => $turnos, 'servicios' => $servicios, 'capacidad' => $config['capacidad']]);
