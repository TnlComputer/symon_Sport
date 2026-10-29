<?php
// Reparaciones (solo administrador): en el taller -> listo para retirar -> entregado
// GET  accion=listar&q=&dias=          -> reparaciones activas y entregadas en los últimos N días
// GET  accion=detalle&id=              -> reparación + presupuesto + cliente + vehículo
// GET  accion=pendientes&q=            -> presupuestos aprobables (sin reparación) para ingresar
// POST accion=ingresar                 -> id_presu, fecha, km, observaciones
// POST accion=guardar, id              -> fecha, km, observaciones, trabajo_realizado, factura
// POST accion=listo, id, listo=1|0     -> trabajo terminado / volver a "en el taller"
// POST accion=lavado, id               -> marca o desmarca el lavado hecho
// POST accion=avisado, id              -> registra que se le avisó al cliente
// POST accion=entregar, id             -> fecha_entrega, cobrado, factura
// POST accion=reabrir, id              -> deshace la entrega (por error)
// POST accion=anular, id               -> anula el ingreso: el presupuesto vuelve a pendiente (no se borra nada)
require_once "admin_lib.php";

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

function responder($datos, $codigo = 200)
{
  http_response_code($codigo);
  salir($datos);
}

function fechaValida($valor)
{
  $d = DateTime::createFromFormat('Y-m-d', (string)$valor);
  return $d && $d->format('Y-m-d') === $valor;
}

function importe($campo)
{
  $v = trim((string)($_POST[$campo] ?? ''));
  if (str_contains($v, ',')) $v = str_replace(['.', ','], ['', '.'], $v); // 12.500,50 -> 12500.50
  if ($v === '') return null;
  return is_numeric($v) && $v >= 0 ? round((float)$v, 2) : false;
}

const SELECT_REPARACION = "SELECT r.id_trabajo, r.id_cliente, r.id_vehiculo, r.patente, r.observaciones, r.trabajo_realizado,
    r.fecha_trab, r.fecha_entrega, r.km, r.factura, r.presupuesto, r.listo, r.cobrado, r.aviso_listo,
    CASE WHEN r.fecha_entrega IS NOT NULL THEN 'entregado' WHEN r.listo IS NOT NULL THEN 'listo' ELSE 'taller' END AS estado,
    DATEDIFF(COALESCE(r.fecha_entrega, CURDATE()), r.fecha_trab) AS dias,
    c.empresa_nyp, c.celular, c.telefono, c.mail, v.marca, v.modelo, v.anio,
    p.descripcion_presu, p.total_presu, p.senia_presu, p.lavado_presu, p.lavado_hecho
  FROM reparaciones r
  LEFT JOIN clientes c ON c.id_cliente = r.id_cliente
  LEFT JOIN vehiculos v ON v.id_vehiculo = r.id_vehiculo
  LEFT JOIN presupuestos p ON p.id_presu = r.presupuesto";

function reparacion($id)
{
  return q(SELECT_REPARACION . " WHERE r.id_trabajo = ? AND r.baja IS NULL", 'i', $id)[0] ?? null;
}

function reparacionActiva($id)
{
  $r = reparacion($id);
  if (!$r) responder(['error' => 'No existe la reparación.'], 404);
  return $r;
}

// el historial del vehículo (fichas_historial) acompaña a la reparación
function actualizarHistorial($r)
{
  $descripcion = mb_substr($r['descripcion_presu'] ?: 'Reparación', 0, 255);
  $detalle = trim((string)($r['trabajo_realizado'] ?: $r['observaciones']));
  $km = $r['km'] !== '' && $r['km'] !== null ? (int)$r['km'] : null;
  q("UPDATE fichas_historial SET fecha = ?, km = ?, descripcion = ?, detalle = NULLIF(?, '')
    WHERE id_presu = ? AND patente = ? AND baja IS NULL", 'sissis', $r['fecha_trab'], $km, $descripcion, $detalle, $r['presupuesto'], $r['patente']);
}

function registrarKm($patente, $km, $fecha)
{
  if (!$km) return;
  $idUsuario = (int)usuarioActual()['id_user'];
  q("INSERT INTO fichas_km (patente, km, fecha, origen, id_user_carga) VALUES (?, ?, ?, 'taller', ?)", 'sisi', $patente, $km, $fecha, $idUsuario);
  q("INSERT INTO fichas_vehiculo (patente, km_actual, km_fecha) VALUES (?, ?, ?)
    ON DUPLICATE KEY UPDATE km_fecha = IF(km_actual IS NULL OR VALUES(km_actual) >= km_actual, VALUES(km_fecha), km_fecha),
    km_actual = IF(km_actual IS NULL OR VALUES(km_actual) >= km_actual, VALUES(km_actual), km_actual)", 'sis', $patente, $km, $fecha);
}

switch ($accion) {
  case 'listar':
    $texto = trim($_GET['q'] ?? '');
    $q = '%' . $texto . '%';
    $patente = '%' . patenteLimpia($texto) . '%';
    $dias = max(7, min(3650, (int)($_GET['dias'] ?? 60)));
    $filas = q(SELECT_REPARACION . "
      WHERE r.baja IS NULL AND (r.fecha_entrega IS NULL OR r.fecha_entrega >= CURDATE() - INTERVAL ? DAY)
        AND (? = '' OR c.empresa_nyp LIKE ? OR (? <> '%%' AND r.patente LIKE ?) OR p.descripcion_presu LIKE ? OR r.factura LIKE ?
          OR r.id_trabajo = ? OR r.presupuesto = ?)
      ORDER BY r.fecha_entrega IS NOT NULL, r.fecha_entrega DESC, r.listo IS NULL, r.fecha_trab, r.id_trabajo",
      'issssssii', $dias, $texto, $q, $patente, $patente, $q, $q, (int)$texto, (int)$texto);
    responder(['reparaciones' => $filas]);

  case 'detalle':
    responder(['reparacion' => reparacionActiva((int)($_GET['id'] ?? 0))]);

  case 'pendientes':
    $q = '%' . trim($_GET['q'] ?? '') . '%';
    responder(['presupuestos' => q("SELECT p.id_presu, p.fecha_presu, p.descripcion_presu, p.total_presu, p.senia_presu,
        p.respuesta_cliente, p.respuesta_motivo, c.empresa_nyp, v.patente, v.marca, v.modelo, f.km_actual
      FROM presupuestos p
      JOIN clientes c ON c.id_cliente = p.id_cliente
      JOIN vehiculos v ON v.id_vehiculo = p.id_vehiculo
      LEFT JOIN fichas_vehiculo f ON f.patente = v.patente
      WHERE p.usado = 0 AND p.baja IS NULL
        AND (c.empresa_nyp LIKE ? OR v.patente LIKE ? OR p.descripcion_presu LIKE ? OR p.id_presu = ?)
      ORDER BY p.respuesta_cliente = 'aprobado' DESC, p.fecha_presu DESC, p.id_presu DESC LIMIT 50", 'sssi', $q, $q, $q, (int)trim($_GET['q'] ?? '0'))]);

  case 'ingresar':
    $idPresu = (int)($_POST['id_presu'] ?? 0);
    $fecha = campo('fecha', 10);
    $km = (int)preg_replace('/\D/', '', campo('km', 10));
    if (!fechaValida($fecha)) responder(['error' => 'Revisá la fecha de ingreso.'], 422);
    if ($fecha > date('Y-m-d')) responder(['error' => 'La fecha de ingreso no puede ser futura.'], 422);
    $p = q("SELECT p.id_presu, p.descripcion_presu, p.id_vehiculo, v.id_cliente, v.patente
      FROM presupuestos p JOIN vehiculos v ON v.id_vehiculo = p.id_vehiculo
      WHERE p.id_presu = ? AND p.usado = 0 AND p.baja IS NULL", 'i', $idPresu)[0] ?? null;
    if (!$p) responder(['error' => 'Ese presupuesto ya no está pendiente.'], 422);

    $observaciones = campo('observaciones', 5000);
    q("INSERT INTO reparaciones (id_cliente, id_vehiculo, patente, observaciones, fecha_trab, presupuesto, km)
      VALUES (?, ?, ?, NULLIF(?, ''), ?, ?, NULLIF(?, '0'))", 'iisssis',
      $p['id_cliente'], $p['id_vehiculo'], $p['patente'], $observaciones, $fecha, $idPresu, (string)$km);
    $id = $conexionApi->insert_id;
    q("UPDATE presupuestos SET usado = 1, fec_ini_presu = ? WHERE id_presu = ?", 'si', $fecha, $idPresu);

    // queda en el historial del vehículo (por patente) con su kilometraje
    $idUsuario = (int)usuarioActual()['id_user'];
    q("INSERT INTO fichas_historial (patente, fecha, km, descripcion, detalle, realizado_por, id_presu, id_user_carga)
      VALUES (?, ?, NULLIF(?, 0), ?, NULLIF(?, ''), 'symon', ?, ?)", 'ssissii',
      $p['patente'], $fecha, $km, mb_substr($p['descripcion_presu'] ?: 'Reparación', 0, 255), $observaciones, $idPresu, $idUsuario);
    registrarKm($p['patente'], $km, $fecha);
    responder(['ok' => true, 'id' => $id]);

  case 'guardar':
    $r = reparacionActiva((int)($_POST['id'] ?? 0));
    $fecha = campo('fecha_trab', 10);
    $km = (int)preg_replace('/\D/', '', campo('km', 10));
    if (!fechaValida($fecha)) responder(['error' => 'Revisá la fecha de ingreso.'], 422);
    if ($r['fecha_entrega'] && $fecha > $r['fecha_entrega']) responder(['error' => 'El ingreso no puede ser posterior a la entrega.'], 422);
    q("UPDATE reparaciones SET fecha_trab = ?, km = NULLIF(?, '0'), observaciones = NULLIF(?, ''), trabajo_realizado = NULLIF(?, ''),
      factura = NULLIF(?, '') WHERE id_trabajo = ?", 'sssssi', $fecha, (string)$km, campo('observaciones', 5000),
      campo('trabajo_realizado', 5000), campo('factura', 30), $r['id_trabajo']);
    if ($km && $km !== (int)$r['km']) registrarKm($r['patente'], $km, $fecha);
    actualizarHistorial(reparacion($r['id_trabajo']));
    responder(['ok' => true]);

  case 'listo':
    $r = reparacionActiva((int)($_POST['id'] ?? 0));
    if ($r['fecha_entrega']) responder(['error' => 'Ya fue entregado.'], 422);
    q("UPDATE reparaciones SET listo = " . (!empty($_POST['listo']) ? 'NOW()' : 'NULL') . " WHERE id_trabajo = ?", 'i', $r['id_trabajo']);
    responder(['ok' => true]);

  case 'lavado':
    $r = reparacionActiva((int)($_POST['id'] ?? 0));
    q("UPDATE presupuestos SET lavado_hecho = IF(lavado_hecho IS NULL, NOW(), NULL) WHERE id_presu = ?", 'i', $r['presupuesto']);
    responder(['ok' => true]);

  case 'avisado':
    $r = reparacionActiva((int)($_POST['id'] ?? 0));
    q("UPDATE reparaciones SET aviso_listo = NOW() WHERE id_trabajo = ?", 'i', $r['id_trabajo']);
    responder(['ok' => true]);

  case 'entregar':
    $r = reparacionActiva((int)($_POST['id'] ?? 0));
    if ($r['fecha_entrega']) responder(['error' => 'Ya fue entregado.'], 422);
    $fecha = campo('fecha_entrega', 10);
    $cobrado = importe('cobrado');
    if (!fechaValida($fecha)) responder(['error' => 'Revisá la fecha de entrega.'], 422);
    if ($fecha < $r['fecha_trab']) responder(['error' => 'La entrega no puede ser anterior al ingreso.'], 422);
    if ($fecha > date('Y-m-d')) responder(['error' => 'La fecha de entrega no puede ser futura.'], 422);
    if ($cobrado === false) responder(['error' => 'Revisá el importe cobrado.'], 422);
    q("UPDATE reparaciones SET fecha_entrega = ?, cobrado = ?, factura = COALESCE(NULLIF(?, ''), factura), listo = COALESCE(listo, NOW())
      WHERE id_trabajo = ?", 'sdsi', $fecha, $cobrado, campo('factura', 30), $r['id_trabajo']);
    q("UPDATE presupuestos SET fec_ret_presu = ? WHERE id_presu = ?", 'si', $fecha, $r['presupuesto']);
    responder(['ok' => true]);

  case 'reabrir':
    $r = reparacionActiva((int)($_POST['id'] ?? 0));
    q("UPDATE reparaciones SET fecha_entrega = NULL, cobrado = NULL WHERE id_trabajo = ?", 'i', $r['id_trabajo']);
    q("UPDATE presupuestos SET fec_ret_presu = NULL WHERE id_presu = ?", 'i', $r['presupuesto']);
    responder(['ok' => true]);

  case 'anular':
    // ingreso cargado por error: no se borra; se da de baja y el presupuesto vuelve a estar pendiente
    $r = reparacionActiva((int)($_POST['id'] ?? 0));
    if ($r['fecha_entrega']) responder(['error' => 'Ya fue entregado: primero deshacé la entrega.'], 422);
    q("UPDATE reparaciones SET baja = NOW() WHERE id_trabajo = ?", 'i', $r['id_trabajo']);
    q("UPDATE presupuestos SET usado = 0, fec_ini_presu = NULL WHERE id_presu = ?", 'i', $r['presupuesto']);
    q("UPDATE fichas_historial SET baja = NOW() WHERE id_presu = ? AND patente = ? AND baja IS NULL", 'is', $r['presupuesto'], $r['patente']);
    responder(['ok' => true]);
}

responder(['error' => 'Acción desconocida'], 400);
