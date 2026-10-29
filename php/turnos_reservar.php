<?php
// POST: servicio, fecha, hora, nombre, telefono, email, patente, vehiculo, comentario
require_once "turnos_lib.php";
require_once "auth/lib.php"; // para vincular el turno al usuario si tiene sesión iniciada

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  responder(['error' => t('Método no permitido')], 405);
}

$campo = fn($k, $max) => mb_substr(trim($_POST[$k] ?? ''), 0, $max);

$idServicio = (int)($_POST['servicio'] ?? 0);
$fecha = $campo('fecha', 10);
$hora = $campo('hora', 5);
$nombre = $campo('nombre', 100);
$telefono = $campo('telefono', 30);
$email = $campo('email', 120);
$patente = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $campo('patente', 15)));
$vehiculo = $campo('vehiculo', 100);
$comentario = $campo('comentario', 500);

// honeypot anti-spam: un humano no completa este campo oculto
if (!empty($_POST['web'])) {
  responder(['ok' => true]);
}

$errores = [];
if (!fechaValida($fecha)) $errores[] = t('Elegí una fecha válida.');
if (!preg_match('/^\d{2}:\d{2}$/', $hora)) $errores[] = t('Elegí un horario.');
if (mb_strlen($nombre) < 2) $errores[] = t('Ingresá tu nombre.');
if (!preg_match('/^[0-9 +()-]{8,30}$/', $telefono)) $errores[] = t('Ingresá un teléfono válido.');
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) $errores[] = t('El email no es válido.');
if (!preg_match('/^[A-Z0-9]{6,8}$/', $patente)) $errores[] = t('Ingresá una patente válida (ej. AB123CD).');
if ($errores) {
  responder(['error' => implode(' ', $errores)], 422);
}

$servicio = buscarServicio($conexionApi, $idServicio);
if (!$servicio) {
  responder(['error' => t('El servicio elegido no existe.')], 422);
}

$config = turnosConfig($conexionApi);
if ($fecha > date('Y-m-d', strtotime("+{$config['dias_max']} days"))) {
  responder(['error' => t('Esa fecha todavía no está habilitada.')], 422);
}

// bloqueo general para que dos personas no tomen el mismo lugar a la vez
// (un trabajo largo ocupa varios días, por eso no alcanza con bloquear la fecha)
$conexionApi->query("SELECT GET_LOCK('ssp_turnos', 10)");

$idUser = !empty($_SESSION['datos']['id_user']) ? (int)$_SESSION['datos']['id_user'] : null;

$idTurno = 0;
$fechaFin = $fecha;
$horaFin = $hora;
$turnoExistente = null;
try {
  // una patente no puede tener dos turnos vigentes
  $st = $conexionApi->prepare("SELECT DATE_FORMAT(fecha, '%d/%m') AS fecha, TIME_FORMAT(hora_inicio, '%H:%i') AS hora
    FROM turnos WHERE patente = ? AND estado IN ('pendiente', 'confirmado') AND TIMESTAMP(fecha_fin, hora_fin) >= NOW()
    ORDER BY fecha LIMIT 1");
  $st->bind_param('s', $patente);
  $st->execute();
  $turnoExistente = $st->get_result()->fetch_assoc();

  $dia = $turnoExistente ? ['slots' => []] : slotsDelDia($conexionApi, $fecha, minutosServicio($servicio), $config);
  foreach ($dia['slots'] as $s) {
    if ($s['hora'] === $hora && $s['estado'] === 'libre') {
      $fechaFin = $s['fecha_fin'];
      $horaFin = $s['hora_fin'];
      $st = $conexionApi->prepare("INSERT INTO turnos (id_servicio, id_user, fecha, hora_inicio, fecha_fin, hora_fin, nombre, telefono, email, patente, vehiculo, comentario)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NULLIF(?, ''), ?, NULLIF(?, ''), NULLIF(?, ''))");
      $st->bind_param('iissssssssss', $idServicio, $idUser, $fecha, $hora, $fechaFin, $horaFin, $nombre, $telefono, $email, $patente, $vehiculo, $comentario);
      $st->execute();
      $idTurno = $conexionApi->insert_id;
      break;
    }
  }
} finally {
  $conexionApi->query("SELECT RELEASE_LOCK('ssp_turnos')");
}

if ($turnoExistente) {
  responder([
    'error' => t('La patente {0} ya tiene un turno el {1} a las {2}.', $patente, $turnoExistente['fecha'], $turnoExistente['hora'])
      . ' ' . t('Si necesitás cambiarlo, cancelalo primero o escribinos.'),
    'ya_tiene_turno' => true,
  ], 409);
}

// el auto queda en la lista de vehículos del usuario (Mi garage)
if ($idTurno && $idUser) {
  $st = $conexionApi->prepare("INSERT INTO usuarios_vehiculos (id_user, patente, descripcion) VALUES (?, ?, NULLIF(?, ''))
    ON DUPLICATE KEY UPDATE baja = NULL, descripcion = COALESCE(VALUES(descripcion), descripcion)");
  $st->bind_param('iss', $idUser, $patente, $vehiculo);
  $st->execute();
}

if (!$idTurno) {
  responder(['error' => t('Ese horario se acaba de ocupar. Elegí otro, por favor.'), 'ocupado' => true], 409);
}

// aviso por mail al taller (si falla, el turno igual queda guardado)
if (!empty($config['email_aviso'])) {
  $h = fn($v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
  $fechaAr = date('d/m/Y', strtotime($fecha));
  $fechaFinAr = date('d/m/Y', strtotime($fechaFin));
  $mensaje = "<h2>Nuevo turno #$idTurno</h2>
    <p><b>{$h($servicio['nombre'])}</b><br>
    Ingreso: $fechaAr $hora<br>
    Salida estimada: $fechaFinAr $horaFin</p>
    <ul>
      <li>Nombre: {$h($nombre)}</li>
      <li>Teléfono: {$h($telefono)}</li>
      <li>Email: {$h($email)}</li>
      <li>Patente: {$h($patente)} {$h($vehiculo)}</li>
      <li>Comentario: {$h($comentario)}</li>
    </ul>";
  $headers = "MIME-Version: 1.0\r\nContent-Type:text/html; charset=utf-8\r\nFrom: Envio Automatico No Responder <no-replay@symonsport.com.ar>";
  @mail($config['email_aviso'], "Nuevo turno $fechaAr $hora - $patente", $mensaje, $headers);
}

responder([
  'ok' => true,
  'turno' => [
    'id' => $idTurno,
    'servicio' => $servicio['nombre'],
    'fecha' => $fecha,
    'hora_inicio' => $hora,
    'fecha_fin' => $fechaFin,
    'hora_fin' => $horaFin,
  ],
]);
