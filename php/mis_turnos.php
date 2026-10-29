<?php
// Turnos vigentes y vehículos del usuario con sesión iniciada
// GET                              -> { sesion, usuario, turnos, vehiculos }
// POST accion=cancelar, id         -> cancela un turno propio que todavía no pasó
// POST accion=vehiculo_baja, id    -> quita un vehículo de su lista (baja, no se borra)
require_once "turnos_lib.php";
require_once "auth/lib.php";

$sesion = $_SESSION['datos'] ?? null;
if (!$sesion || empty($sesion['id_user'])) {
  responder(['sesion' => false]);
}
$idUser = (int)$sesion['id_user'];
$email = (string)($sesion['email'] ?? '');
$db = $conexionApi;

// un turno es "vigente" si no fue cancelado/atendido y el auto todavía no salió
const TURNO_VIGENTE = "t.estado IN ('pendiente', 'confirmado') AND TIMESTAMP(t.fecha_fin, t.hora_fin) >= NOW()";
// turnos propios: los pedidos con la cuenta o, antes de tenerla, con el mismo mail
const TURNO_PROPIO = "(t.id_user = ? OR (t.id_user IS NULL AND ? <> '' AND t.email = ?))";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $id = (int)($_POST['id'] ?? 0);
  switch ($_POST['accion'] ?? '') {
    case 'cancelar':
      $st = $db->prepare("UPDATE turnos t SET t.estado = 'cancelado'
        WHERE t.id_turno = ? AND " . TURNO_PROPIO . " AND " . TURNO_VIGENTE);
      $st->bind_param('iiss', $id, $idUser, $email, $email);
      $st->execute();
      if ($st->affected_rows === 0) {
        responder(['error' => 'No se pudo cancelar ese turno.'], 422);
      }
      responder(['ok' => true]);

    case 'vehiculo_baja':
      $st = $db->prepare("UPDATE usuarios_vehiculos SET baja = NOW() WHERE id_vehiculo_usuario = ? AND id_user = ? AND baja IS NULL");
      $st->bind_param('ii', $id, $idUser);
      $st->execute();
      responder(['ok' => true]);
  }
  responder(['error' => 'Acción desconocida'], 400);
}

$st = $db->prepare("SELECT t.id_turno, t.fecha, TIME_FORMAT(t.hora_inicio, '%H:%i') AS hora_inicio,
    t.fecha_fin, TIME_FORMAT(t.hora_fin, '%H:%i') AS hora_fin, t.patente, t.vehiculo, t.estado, s.nombre AS servicio
  FROM turnos t JOIN turnos_servicios s ON s.id_servicio = t.id_servicio
  WHERE " . TURNO_PROPIO . " AND " . TURNO_VIGENTE . "
  ORDER BY t.fecha, t.hora_inicio");
$st->bind_param('iss', $idUser, $email, $email);
$st->execute();
$turnos = $st->get_result()->fetch_all(MYSQLI_ASSOC);

$st = $db->prepare("SELECT id_vehiculo_usuario, patente, marca, modelo, anio, descripcion
  FROM usuarios_vehiculos WHERE id_user = ? AND baja IS NULL AND vendido IS NULL ORDER BY creado");
$st->bind_param('i', $idUser);
$st->execute();
$vehiculos = $st->get_result()->fetch_all(MYSQLI_ASSOC);

// a cada vehículo le asocio su turno vigente (si tiene)
$porPatente = [];
foreach ($turnos as $t) {
  $porPatente[$t['patente']] ??= $t;
}
$patentes = array_column($vehiculos, 'patente');
foreach ($vehiculos as &$v) {
  $v['turno'] = $porPatente[$v['patente']] ?? null;
}
unset($v);
// turnos pedidos antes de tener cuenta: su auto también aparece en la lista
foreach ($turnos as $t) {
  if (!in_array($t['patente'], $patentes, true)) {
    $vehiculos[] = ['id_vehiculo_usuario' => null, 'patente' => $t['patente'], 'descripcion' => $t['vehiculo'], 'turno' => $t];
    $patentes[] = $t['patente'];
  }
}

// teléfono del último turno para completar el formulario
$st = $db->prepare("SELECT t.telefono FROM turnos t WHERE " . TURNO_PROPIO . " ORDER BY t.creado DESC LIMIT 1");
$st->bind_param('iss', $idUser, $email, $email);
$st->execute();
$telefono = $st->get_result()->fetch_row()[0] ?? '';

responder([
  'sesion' => true,
  'usuario' => ['nombre' => $sesion['nombre'] ?? '', 'email' => $email, 'telefono' => $telefono],
  'turnos' => $turnos,
  'vehiculos' => $vehiculos,
]);
