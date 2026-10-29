<?php
// Presupuestos (solo administrador)
// GET  accion=listar&q=            -> presupuestos con cliente y vehículo
// GET  accion=detalle&id=          -> presupuesto + repuestos + desglose interno
// GET  accion=clientes&q=          -> búsqueda de clientes
// GET  accion=vehiculos&id_cliente -> patentes activas del cliente
// GET  accion=repuestos&q=         -> catálogo con precio
// POST accion=guardar              -> crea o actualiza (totales calculados en el servidor)
// POST accion=vehiculo             -> agrega un vehículo al cliente (con traspaso si era de otro)
// POST accion=anular, id           -> baja del presupuesto (no se borra)
require_once "repuestos_lib.php";
require_once "admin_lib.php"; // asignarVehiculo(): traspaso de patente con historial

if (!esAdmin()) {
  responderJSON(['error' => 'No autorizado'], 403);
}

$db = $conexionApi;
$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';
$num = fn($v) => is_numeric(str_replace(',', '.', (string)$v)) ? (float)str_replace(',', '.', (string)$v) : 0.0;

function consulta($db, $sql, $tipos = '', ...$params)
{
  $st = $db->prepare($sql);
  if ($tipos !== '') $st->bind_param($tipos, ...$params);
  $st->execute();
  $res = $st->get_result();
  return $res ? $res->fetch_all(MYSQLI_ASSOC) : $st->affected_rows;
}

switch ($accion) {
  case 'listar':
    $q = '%' . trim($_GET['q'] ?? '') . '%';
    $filas = consulta($db, "SELECT p.id_presu, p.fecha_presu, p.descripcion_presu, p.usado, p.total_presu, p.senia_presu,
        p.lavado_presu, p.lavado_hecho, p.respuesta_cliente, p.respuesta_fecha, p.respuesta_motivo, p.id_cliente, p.id_vehiculo, c.empresa_nyp, v.patente, v.marca, v.modelo
      FROM presupuestos p
      LEFT JOIN clientes c ON c.id_cliente = p.id_cliente
      LEFT JOIN vehiculos v ON v.id_vehiculo = p.id_vehiculo
      WHERE p.baja IS NULL AND (c.empresa_nyp LIKE ? OR v.patente LIKE ? OR p.descripcion_presu LIKE ? OR p.id_presu = ?)
      ORDER BY p.id_presu DESC LIMIT 200", 'sssi', $q, $q, $q, (int)trim($_GET['q'] ?? '0'));
    responderJSON(['presupuestos' => $filas]);

  case 'detalle':
    $id = (int)($_GET['id'] ?? 0);
    $p = consulta($db, "SELECT p.*, c.empresa_nyp, c.celular, c.mail, v.patente, v.marca, v.modelo, v.anio
      FROM presupuestos p LEFT JOIN clientes c ON c.id_cliente = p.id_cliente
      LEFT JOIN vehiculos v ON v.id_vehiculo = p.id_vehiculo WHERE p.id_presu = ?", 'i', $id)[0] ?? null;
    if (!$p) responderJSON(['error' => 'No existe el presupuesto'], 404);
    $items = consulta($db, "SELECT id_repuesto, descripcion, cantidad, provisto, costo_unit, precio_unit
      FROM presupuestos_items WHERE id_presu = ? AND baja IS NULL ORDER BY id_item", 'i', $id);
    responderJSON(['presupuesto' => $p, 'items' => $items, 'config' => tallerConfig($db)]);

  case 'clientes':
    if (!empty($_GET['id'])) { // un cliente puntual (presupuesto nuevo desde la ficha del cliente)
      responderJSON(['clientes' => consulta($db, "SELECT id_cliente, empresa_nyp, cuit, celular, mail FROM clientes
        WHERE id_cliente = ? AND baja IS NULL", 'i', (int)$_GET['id'])]);
    }
    $q = '%' . trim($_GET['q'] ?? '') . '%';
    responderJSON(['clientes' => consulta($db, "SELECT id_cliente, empresa_nyp, cuit, celular, mail FROM clientes
      WHERE baja IS NULL AND (empresa_nyp LIKE ? OR cuit LIKE ? OR celular LIKE ? OR mail LIKE ?) ORDER BY empresa_nyp LIMIT 15",
      'ssss', $q, $q, $q, $q)]);

  case 'vehiculos':
    $id = (int)($_GET['id_cliente'] ?? 0);
    responderJSON(['vehiculos' => consulta($db, "SELECT id_vehiculo, patente, marca, modelo, anio FROM vehiculos
      WHERE id_cliente = ? AND baja IS NULL ORDER BY patente", 'i', $id)]);

  case 'repuestos':
    $config = tallerConfig($db);
    $q = '%' . trim($_GET['q'] ?? '') . '%';
    // el mismo repuesto puede estar en la lista de varios proveedores: se muestra de quién es cada uno
    $filas = consulta($db, REPUESTO_SELECT . " WHERE r.baja IS NULL AND (r.descripcion LIKE ? OR r.codigo LIKE ? OR r.marca LIKE ?)
      ORDER BY r.descripcion, r.costo LIMIT 25", 'sss', $q, $q, $q);
    foreach ($filas as &$r) $r['precio'] = precioRepuesto($r, $config);
    responderJSON(['repuestos' => $filas, 'config' => $config]);

  case 'vehiculo':
    // alta de un vehículo para el cliente; si la patente es de otro cliente, pide confirmar el traspaso
    $r = altaVehiculo((int)($_POST['id_cliente'] ?? 0), $_POST['patente'] ?? '', $_POST['marca'] ?? '',
      $_POST['modelo'] ?? '', $_POST['anio'] ?? '', !empty($_POST['confirmar_traspaso']));
    responderJSON($r, isset($r['error']) ? 422 : 200);

  case 'guardar':
    $id = (int)($_POST['id_presu'] ?? 0);
    $idCliente = (int)($_POST['id_cliente'] ?? 0);
    $idVehiculo = (int)($_POST['id_vehiculo'] ?? 0);
    $fecha = $_POST['fecha'] ?? date('Y-m-d');
    $descripcion = mb_substr(trim($_POST['descripcion'] ?? ''), 0, 5000);
    $dias = max(0, (int)($_POST['dias'] ?? 10));
    $manoObraBase = max(0, $num($_POST['mano_obra'] ?? 0));
    $lavado = ($_POST['lavado'] ?? '') === '' ? null : max(0, $num($_POST['lavado']));
    $senia = max(0, $num($_POST['senia'] ?? 0));
    $items = json_decode($_POST['items'] ?? '[]', true) ?: [];

    $vehiculo = consulta($db, "SELECT patente FROM vehiculos WHERE id_vehiculo = ? AND id_cliente = ? AND baja IS NULL", 'ii', $idVehiculo, $idCliente)[0] ?? null;
    if (!$vehiculo) responderJSON(['error' => 'Elegí el cliente y uno de sus vehículos.'], 422);
    if (!fechaValidaPresu($fecha)) responderJSON(['error' => 'La fecha no es válida.'], 422);
    if ($descripcion === '') responderJSON(['error' => 'Escribí el detalle del trabajo.'], 422);

    // normalizo los repuestos y tomo el costo del catálogo en este momento
    $config = tallerConfig($db);
    $limpios = [];
    foreach ($items as $it) {
      $desc = mb_substr(trim((string)($it['descripcion'] ?? '')), 0, 150);
      if ($desc === '') continue;
      $idRep = (int)($it['id_repuesto'] ?? 0) ?: null;
      $costo = 0.0;
      if ($idRep) {
        $rep = consulta($db, "SELECT costo FROM repuestos WHERE id_repuesto = ?", 'i', $idRep)[0] ?? null;
        $costo = $rep ? (float)$rep['costo'] : 0.0;
      }
      $provisto = ($it['provisto'] ?? 'taller') === 'cliente' ? 'cliente' : 'taller';
      $limpios[] = [
        'id_repuesto' => $idRep,
        'descripcion' => $desc,
        'cantidad' => max(0.01, $num($it['cantidad'] ?? 1)),
        'provisto' => $provisto,
        'costo_unit' => $provisto === 'cliente' ? 0.0 : $costo,
        'precio_unit' => $provisto === 'cliente' ? 0.0 : max(0, $num($it['precio_unit'] ?? 0)),
      ];
    }
    $t = totalesPresupuesto($limpios, $manoObraBase, $config, $lavado);
    $saldo = max(0, round($t['total'] - $senia, 2));
    $patente = $vehiculo['patente'];

    if ($id) {
      $st = $db->prepare("UPDATE presupuestos SET fecha_presu = ?, descripcion_presu = ?, patente_presu = ?, mobra_presu = ?,
        repuestos_presu = ?, total_presu = ?, senia_presu = ?, abonado_presu = ?, dias_presu = ?, id_cliente = ?, id_vehiculo = ?,
        mobra_base_presu = ?, recargo_mo_presu = ?, lavado_presu = ?,
        respuesta_cliente = NULL, respuesta_fecha = NULL, respuesta_id_user = NULL, respuesta_motivo = NULL
        WHERE id_presu = ? AND baja IS NULL"); // si cambia, el cliente lo tiene que volver a aprobar
      $st->bind_param('sssdddddiiidddi', $fecha, $descripcion, $patente, $t['mano_obra_cliente'], $t['repuestos'], $t['total'],
        $senia, $saldo, $dias, $idCliente, $idVehiculo, $t['mano_obra_base'], $t['recargo_mano_obra'], $t['lavado'], $id);
      $st->execute();
      consulta($db, "UPDATE presupuestos_items SET baja = NOW() WHERE id_presu = ? AND baja IS NULL", 'i', $id);
    } else {
      $st = $db->prepare("INSERT INTO presupuestos (fecha_presu, descripcion_presu, usado, patente_presu, mobra_presu, repuestos_presu,
        total_presu, senia_presu, abonado_presu, dias_presu, id_cliente, id_vehiculo, mobra_base_presu, recargo_mo_presu, lavado_presu)
        VALUES (?, ?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
      $st->bind_param('sssdddddiiiddd', $fecha, $descripcion, $patente, $t['mano_obra_cliente'], $t['repuestos'], $t['total'],
        $senia, $saldo, $dias, $idCliente, $idVehiculo, $t['mano_obra_base'], $t['recargo_mano_obra'], $t['lavado']);
      $st->execute();
      $id = $db->insert_id;
    }

    $st = $db->prepare("INSERT INTO presupuestos_items (id_presu, id_repuesto, descripcion, cantidad, provisto, costo_unit, precio_unit)
      VALUES (?, ?, ?, ?, ?, ?, ?)");
    foreach ($limpios as $it) {
      $st->bind_param('iisdsdd', $id, $it['id_repuesto'], $it['descripcion'], $it['cantidad'], $it['provisto'], $it['costo_unit'], $it['precio_unit']);
      $st->execute();
    }
    responderJSON(['ok' => true, 'id' => $id, 'totales' => $t, 'saldo' => $saldo]);

  case 'anular':
    $id = (int)($_POST['id'] ?? 0);
    consulta($db, "UPDATE presupuestos SET baja = NOW() WHERE id_presu = ? AND usado = 0 AND baja IS NULL", 'i', $id);
    responderJSON(['ok' => true]);

  case 'lavado_hecho':
    $id = (int)($_POST['id'] ?? 0);
    consulta($db, "UPDATE presupuestos SET lavado_hecho = IF(lavado_hecho IS NULL, NOW(), NULL) WHERE id_presu = ?", 'i', $id);
    responderJSON(['ok' => true]);
}

responderJSON(['error' => 'Acción desconocida'], 400);

function fechaValidaPresu($fecha)
{
  $d = DateTime::createFromFormat('Y-m-d', $fecha);
  return $d && $d->format('Y-m-d') === $fecha;
}
