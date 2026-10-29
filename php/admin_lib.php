<?php
// Base para los PHP del panel de administración: sesión de admin obligatoria,
// token CSRF en los envíos (lo controla bootstrap) y consultas preparadas.
require_once __DIR__ . "/auth/lib.php";

header('Content-Type: application/json; charset=utf-8');

if (!esAdmin()) {
  http_response_code(403);
  echo json_encode('no_autorizado');
  exit;
}

// Ejecuta una consulta preparada; devuelve las filas (SELECT) o la cantidad de filas afectadas
function q($sql, $tipos = '', ...$params)
{
  global $conexionApi;
  $st = $conexionApi->prepare($sql);
  if ($tipos !== '') $st->bind_param($tipos, ...$params);
  $st->execute();
  $res = $st->get_result();
  return $res ? $res->fetch_all(MYSQLI_ASSOC) : $st->affected_rows;
}

// Campo de texto recibido por POST, recortado
function campo($nombre, $max = 255)
{
  return mb_substr(trim((string)($_POST[$nombre] ?? '')), 0, $max);
}

function patenteLimpia($valor)
{
  return strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)$valor));
}

function salir($datos)
{
  echo json_encode($datos, JSON_UNESCAPED_UNICODE);
  exit;
}

/**
 * Alta de un vehículo para un cliente. Si la patente está activa a nombre de otro cliente,
 * sin $confirmarTraspaso devuelve ['traspaso' => true, ...] para que el admin lo confirme.
 * Devuelve ['error'] / ['traspaso', cliente_anterior, marca, modelo, anio] / ['ok', id_vehiculo, traspasado]
 */
function altaVehiculo($idCliente, $patente, $marca, $modelo, $anio, $confirmarTraspaso)
{
  $patente = patenteLimpia($patente);
  $marca = mb_substr(trim((string)$marca), 0, 40);
  $modelo = mb_substr(trim((string)$modelo), 0, 60);
  $anio = mb_substr(trim((string)$anio), 0, 4);
  if (!$idCliente || !preg_match('/^[A-Z0-9]{6,8}$/', $patente)) {
    return ['error' => 'Revisá la patente (ej. AB123CD).'];
  }
  $propio = q("SELECT id_vehiculo FROM vehiculos WHERE patente = ? AND id_cliente = ? AND baja IS NULL", 'si', $patente, $idCliente);
  if ($propio) return ['ok' => true, 'id_vehiculo' => (int)$propio[0]['id_vehiculo'], 'traspasado' => false];

  $otro = q("SELECT v.marca, v.modelo, v.anio, c.empresa_nyp FROM vehiculos v
    LEFT JOIN clientes c ON c.id_cliente = v.id_cliente
    WHERE v.patente = ? AND v.id_cliente <> ? AND v.baja IS NULL LIMIT 1", 'si', $patente, $idCliente)[0] ?? null;
  if ($otro && !$confirmarTraspaso) {
    return ['traspaso' => true, 'cliente_anterior' => $otro['empresa_nyp'],
      'marca' => $otro['marca'], 'modelo' => $otro['modelo'], 'anio' => $otro['anio']];
  }
  if ($otro) {
    // en un traspaso se conservan los datos del vehículo si no se cargaron nuevos
    $marca = $marca ?: (string)$otro['marca'];
    $modelo = $modelo ?: (string)$otro['modelo'];
    $anio = $anio ?: (string)$otro['anio'];
  }
  return ['ok' => true, 'id_vehiculo' => asignarVehiculo($idCliente, $patente, $marca, $modelo, $anio), 'traspasado' => (bool)$otro];
}

/**
 * Asigna una patente a un cliente. Si estaba activa a nombre de otro cliente, se la traspasa:
 * el registro anterior queda dado de baja (no se borra) y se cierra su titularidad.
 * Devuelve el id_vehiculo del cliente.
 */
function asignarVehiculo($idCliente, $patente, $marca, $modelo, $anio)
{
  global $conexionApi;
  $propio = q("SELECT id_vehiculo FROM vehiculos WHERE patente = ? AND id_cliente = ? AND baja IS NULL", 'si', $patente, $idCliente);
  if ($propio) return (int)$propio[0]['id_vehiculo'];

  q("UPDATE vehiculos SET baja = NOW() WHERE patente = ? AND id_cliente <> ? AND baja IS NULL", 'si', $patente, $idCliente);
  q("UPDATE vehiculos_titulares SET hasta = CURDATE() WHERE patente = ? AND hasta IS NULL AND (id_cliente IS NULL OR id_cliente <> ?)", 'si', $patente, $idCliente);
  q("INSERT INTO vehiculos (id_cliente, patente, marca, modelo, anio) VALUES (?, ?, ?, ?, ?)", 'issss', $idCliente, $patente, $marca, $modelo, $anio);
  $id = $conexionApi->insert_id;
  if (!q("SELECT 1 FROM vehiculos_titulares WHERE patente = ? AND id_cliente = ? AND hasta IS NULL", 'si', $patente, $idCliente)) {
    q("INSERT INTO vehiculos_titulares (patente, id_cliente, desde) VALUES (?, ?, CURDATE())", 'si', $patente, $idCliente);
  }
  $anioNum = (int)$anio ?: null;
  q("INSERT INTO fichas_vehiculo (patente, marca, modelo, anio) VALUES (?, NULLIF(?, ''), NULLIF(?, ''), ?)
    ON DUPLICATE KEY UPDATE marca = COALESCE(marca, VALUES(marca)), modelo = COALESCE(modelo, VALUES(modelo)),
    anio = COALESCE(anio, VALUES(anio))", 'sssi', $patente, $marca, $modelo, $anioNum);
  return $id;
}

/* ---------- Sucursales de clientes y qué sucursales ve cada usuario (Clientes y Usuarios) ---------- */
function sucursalesDe($idCliente)
{
  return q("SELECT s.id_sucursal, s.nombre, s.direccion, s.localidad, s.telefono, s.contacto,
      (SELECT COUNT(*) FROM vehiculos v WHERE v.id_sucursal = s.id_sucursal AND v.baja IS NULL) AS vehiculos
    FROM clientes_sucursales s WHERE s.id_cliente = ? AND s.baja IS NULL ORDER BY s.nombre", 'i', $idCliente);
}

function sucursalDelCliente($idSucursal, $idCliente)
{
  return (bool)q("SELECT 1 FROM clientes_sucursales WHERE id_sucursal = ? AND id_cliente = ? AND baja IS NULL", 'ii', $idSucursal, $idCliente);
}

// Tildes de sucursales del usuario: todas_sucursales (0/1) y sucursales (ids separados por coma).
// Si el cliente no tiene sucursales, ve todo.
function guardarPermisos($idUser, $idCliente)
{
  $sucursales = array_map(fn($s) => (int)$s['id_sucursal'], sucursalesDe($idCliente));
  $todas = !$sucursales || !empty($_POST['todas_sucursales']) ? 1 : 0;
  $elegidas = array_intersect($sucursales, array_map('intval', explode(',', (string)($_POST['sucursales'] ?? ''))));
  q("UPDATE usuarios SET todas_sucursales = ? WHERE id_user = ?", 'ii', $todas, $idUser);
  // tildes que quedaron de otro cliente (si se lo cambió de cliente): se dan de baja
  q("UPDATE usuarios_sucursales SET baja = NOW() WHERE id_user = ? AND baja IS NULL
    AND id_sucursal NOT IN (SELECT id_sucursal FROM clientes_sucursales WHERE id_cliente = ?)", 'ii', $idUser, $idCliente);
  foreach ($sucursales as $id) {
    if (in_array($id, $elegidas, true)) {
      q("INSERT INTO usuarios_sucursales (id_user, id_sucursal) VALUES (?, ?) ON DUPLICATE KEY UPDATE baja = NULL", 'ii', $idUser, $id);
    } else {
      q("UPDATE usuarios_sucursales SET baja = NOW() WHERE id_user = ? AND id_sucursal = ? AND baja IS NULL", 'ii', $idUser, $id);
    }
  }
}
