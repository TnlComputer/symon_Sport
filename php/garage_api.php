<?php
// Mi garage (usuario con sesión iniciada): su perfil y sus autos
// GET  accion=datos                  -> perfil + autos activos + autos vendidos
// GET  accion=auto&id=               -> ficha (con qué campos puede editar), fotos, historial y presupuestos pendientes
// POST accion=perfil                 -> nombre, apellido, DNI, celular
// POST accion=clave                  -> cambio de contraseña (pide la actual si tiene)
// POST accion=foto_perfil, archivo / accion=foto_perfil_quitar
// POST accion=auto_agregar           -> patente, marca, modelo, año
// POST accion=auto_guardar, id       -> datos de la ficha que edita el cliente + notas
// POST accion=auto_km, id, km        -> nueva lectura de kilometraje
// POST accion=auto_foto, id, archivo -> suma una foto a la galería (hasta 10 propias)
// POST accion=auto_foto_quitar, id, id_foto / accion=auto_portada, id, id_foto
// POST accion=auto_vendido, id, fecha -> queda en "Autos que tuve" y no se puede modificar más
// POST accion=auto_quitar, id        -> lo saca del garage (cargado por error; no se borra)
require_once "auth/lib.php";
require_once "vehiculo_lib.php";

$sesion = usuarioActual();
if (!$sesion) {
  responderJSON(['error' => 'Iniciá sesión para ver tu garage.'], 401);
}
$db = $conexionApi;
$idUser = (int)$sesion['id_user'];
$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

function q($sql, $tipos = '', ...$params)
{
  global $db;
  return dbq($db, $sql, $tipos, ...$params);
}

function texto($nombre, $max)
{
  return mb_substr(trim((string)($_POST[$nombre] ?? '')), 0, $max);
}

function usuario()
{
  global $idUser;
  return q("SELECT id_user, usuario, email, nombre, apellido, dni, celular, foto, avatar, id_cliente,
      (clave_hash IS NOT NULL) AS tiene_clave, (google_id IS NOT NULL) AS con_google,
      (microsoft_id IS NOT NULL) AS con_microsoft, (facebook_id IS NOT NULL) AS con_facebook
    FROM usuarios WHERE id_user = ? AND baja IS NULL", 'i', $idUser)[0] ?? null;
}

$yo = usuario();
if (!$yo) responderJSON(['error' => 'Tu cuenta no está activa.'], 403);
$idCliente = (int)$yo['id_cliente']; // se lee de la base: el taller puede vincularlo después de iniciar sesión

// el taller confirmó que la patente es de este cliente
function confirmado($patente)
{
  global $idCliente;
  return $idCliente && (bool)q("SELECT 1 FROM vehiculos WHERE patente = ? AND id_cliente = ? AND baja IS NULL", 'si', $patente, $idCliente);
}

// por qué no puede modificar el auto (null = puede)
function motivoBloqueo($auto)
{
  global $idUser, $idCliente;
  if ($auto['vendido']) return 'Lo marcaste como vendido: sus datos ya no se pueden modificar.';
  $patente = $auto['patente'];
  $deOtroCliente = q("SELECT 1 FROM vehiculos WHERE patente = ? AND baja IS NULL AND id_cliente <> ?", 'si', $patente, $idCliente);
  $deOtraCuenta = q("SELECT 1 FROM usuarios_vehiculos o JOIN usuarios u ON u.id_user = o.id_user
    WHERE o.patente = ? AND o.id_user <> ? AND o.baja IS NULL AND o.vendido IS NULL
      AND (u.id_cliente IS NULL OR u.id_cliente <> ?)", 'sii', $patente, $idUser, $idCliente);
  if ($deOtroCliente || $deOtraCuenta) {
    return 'Este auto figura a nombre de otra persona. Si lo compraste, avisale al taller para que lo pase a tu nombre.';
  }
  return null;
}

function autoPropio($id)
{
  global $idUser;
  return q("SELECT * FROM usuarios_vehiculos WHERE id_vehiculo_usuario = ? AND id_user = ? AND baja IS NULL", 'ii', $id, $idUser)[0] ?? null;
}

function autoEditable($id)
{
  $auto = autoPropio($id);
  if (!$auto) responderJSON(['error' => 'No existe ese auto en tu garage.'], 404);
  if ($motivo = motivoBloqueo($auto)) responderJSON(['error' => $motivo], 409);
  return $auto;
}

// fotos que ve el usuario de su auto, con la portada primero
function fotosDelAuto($auto)
{
  global $db, $idUser;
  $fotos = fotosVehiculo($db, $auto['patente'], $idUser, confirmado($auto['patente']));
  usort($fotos, fn($a, $b) => ((int)$b['id_foto'] === (int)$auto['foto_portada']) <=> ((int)$a['id_foto'] === (int)$auto['foto_portada']));
  return $fotos;
}

function refrescarSesion($yo)
{
  $_SESSION['datos']['nombre'] = trim($yo['nombre'] . ' ' . $yo['apellido']);
  $_SESSION['datos']['avatar'] = urlFoto($yo['foto']) ?: $yo['avatar'];
}

function perfilPublico($yo)
{
  return [
    'nombre' => $yo['nombre'], 'apellido' => $yo['apellido'], 'dni' => $yo['dni'], 'celular' => $yo['celular'],
    'email' => $yo['email'] ?: $yo['usuario'], 'foto' => urlFoto($yo['foto']) ?: $yo['avatar'],
    'foto_propia' => (bool)$yo['foto'], 'tiene_clave' => (bool)$yo['tiene_clave'],
    'ingresos' => array_keys(array_filter(['Google' => $yo['con_google'], 'Microsoft' => $yo['con_microsoft'], 'Facebook' => $yo['con_facebook']])),
  ];
}

switch ($accion) {
  case 'datos':
    // los autos que el taller tiene a nombre de su cliente aparecen solos en su garage
    if ($idCliente) {
      q("INSERT IGNORE INTO usuarios_vehiculos (id_user, patente, marca, modelo, anio)
        SELECT ?, v.patente, NULLIF(v.marca, ''), NULLIF(v.modelo, ''), IF(v.anio REGEXP '^[0-9]{4}$', v.anio, NULL)
        FROM vehiculos v WHERE v.id_cliente = ? AND v.baja IS NULL", 'ii', $idUser, $idCliente);
    }
    $autos = q("SELECT uv.id_vehiculo_usuario AS id, uv.patente, uv.foto_portada, COALESCE(f.marca, uv.marca) AS marca,
        COALESCE(f.modelo, uv.modelo) AS modelo, f.version, COALESCE(f.anio, uv.anio) AS anio, f.color,
        GREATEST(COALESCE(f.km_actual, 0), COALESCE(uv.km, 0)) AS km, uv.vendido,
        f.vtv_vencimiento, f.seguro_vencimiento, f.gnc_oblea_vencimiento, f.matafuego_vencimiento,
        EXISTS (SELECT 1 FROM vehiculos v WHERE v.patente = uv.patente AND v.id_cliente = ? AND v.baja IS NULL) AS confirmado,
        (SELECT CONCAT(t.fecha, ' ', TIME_FORMAT(t.hora_inicio, '%H:%i')) FROM turnos t
          WHERE t.patente = uv.patente AND t.id_user = uv.id_user AND t.estado IN ('pendiente', 'confirmado')
            AND TIMESTAMP(t.fecha_fin, t.hora_fin) >= NOW() ORDER BY t.fecha LIMIT 1) AS turno
      FROM usuarios_vehiculos uv LEFT JOIN fichas_vehiculo f ON f.patente = uv.patente
      WHERE uv.id_user = ? AND uv.baja IS NULL
      ORDER BY uv.vendido IS NOT NULL, uv.vendido DESC, uv.creado", 'ii', $idCliente, $idUser);
    foreach ($autos as &$a) {
      $a['confirmado'] = (bool)$a['confirmado'];
      $fotos = fotosVehiculo($db, $a['patente'], $idUser, $a['confirmado']);
      $portada = array_values(array_filter($fotos, fn($f) => (int)$f['id_foto'] === (int)$a['foto_portada']))[0] ?? $fotos[0] ?? null;
      $a['foto'] = $portada['url'] ?? null;
      $a['fotos'] = count($fotos);
      unset($a['foto_portada']);
    }
    unset($a);
    responderJSON(['perfil' => perfilPublico($yo),
      'autos' => array_values(array_filter($autos, fn($a) => !$a['vendido'])),
      'vendidos' => array_values(array_filter($autos, fn($a) => (bool)$a['vendido']))]);

  case 'auto':
    $auto = autoPropio((int)($_GET['id'] ?? 0));
    if (!$auto) responderJSON(['error' => 'No existe ese auto en tu garage.'], 404);
    $patente = $auto['patente'];
    $ficha = leerFicha($db, $patente);
    $ficha['marca'] = $ficha['marca'] ?? $auto['marca'];
    $ficha['modelo'] = $ficha['modelo'] ?? $auto['modelo'];
    $ficha['anio'] = $ficha['anio'] ?? $auto['anio'];
    $ficha['km_actual'] = max((int)($ficha['km_actual'] ?? 0), (int)$auto['km']) ?: null;

    $esConfirmado = confirmado($patente);
    $historial = [];
    $presupuestos = [];
    if ($esConfirmado) {
      // trabajos del taller; de los dueños anteriores solo qué se hizo (sin nombre, observaciones ni importes)
      $historial = q("SELECT r.fecha_trab AS fecha, r.fecha_entrega, r.km, p.descripcion_presu AS trabajo,
          IF(r.id_cliente = ?, r.observaciones, NULL) AS observaciones,
          IF(r.id_cliente = ?, p.total_presu, NULL) AS total, r.id_cliente <> ? AS anterior
        FROM reparaciones r LEFT JOIN presupuestos p ON p.id_presu = r.presupuesto
        WHERE r.patente = ? ORDER BY r.fecha_trab DESC, r.id_trabajo DESC", 'iiis', $idCliente, $idCliente, $idCliente, $patente);
      foreach ($historial as &$h) $h['anterior'] = (bool)$h['anterior'];
      unset($h);
      $presupuestos = q("SELECT p.id_presu, p.fecha_presu, p.dias_presu, p.descripcion_presu, p.repuestos_presu,
          p.mobra_presu, p.total_presu, p.senia_presu
        FROM presupuestos p JOIN vehiculos v ON v.id_vehiculo = p.id_vehiculo
        WHERE v.patente = ? AND p.id_cliente = ? AND p.usado = 0 AND p.baja IS NULL ORDER BY p.id_presu DESC", 'si', $patente, $idCliente);
    }
    responderJSON(['auto' => ['id' => (int)$auto['id_vehiculo_usuario'], 'patente' => $patente, 'vendido' => $auto['vendido'],
      'descripcion' => $auto['descripcion'], 'confirmado' => $esConfirmado, 'portada' => (int)$auto['foto_portada']],
      'campos' => camposFichaPublicos(false), 'ficha' => $ficha, 'fotos' => fotosDelAuto($auto), 'max_fotos' => MAX_FOTOS,
      'bloqueo' => motivoBloqueo($auto), 'historial' => $historial, 'presupuestos' => $presupuestos]);

  case 'perfil':
    $nombre = texto('nombre', 50);
    $celular = texto('celular', 30);
    $dni = preg_replace('/[^0-9]/', '', texto('dni', 20));
    if ($nombre === '') responderJSON(['error' => 'Falta tu nombre.'], 422);
    if ($celular !== '' && strlen(preg_replace('/\D/', '', $celular)) < 8) responderJSON(['error' => 'Revisá el celular (con característica).'], 422);
    q("UPDATE usuarios SET nombre = ?, apellido = ?, dni = ?, celular = NULLIF(?, '') WHERE id_user = ?",
      'ssssi', $nombre, texto('apellido', 50), $dni, $celular, $idUser);
    refrescarSesion(usuario());
    responderJSON(['ok' => true, 'perfil' => perfilPublico(usuario())]);

  case 'clave':
    $nueva = (string)($_POST['nueva'] ?? '');
    if ($yo['tiene_clave']) {
      $hash = q("SELECT clave_hash FROM usuarios WHERE id_user = ?", 'i', $idUser)[0]['clave_hash'];
      if (!password_verify((string)($_POST['actual'] ?? ''), $hash)) responderJSON(['error' => 'La contraseña actual no es correcta.'], 422);
    }
    if (!claveValida($nueva)) responderJSON(['error' => 'La contraseña nueva necesita al menos 8 caracteres, con letras y números.'], 422);
    if ($nueva !== (string)($_POST['repetir'] ?? '')) responderJSON(['error' => 'Las contraseñas nuevas no coinciden.'], 422);
    q("UPDATE usuarios SET clave_hash = ?, clave = NULL WHERE id_user = ?", 'si', password_hash($nueva, PASSWORD_DEFAULT), $idUser);
    session_regenerate_id(true);
    responderJSON(['ok' => true]);

  case 'foto_perfil':
  case 'foto_perfil_quitar':
    $nueva = null;
    if ($accion === 'foto_perfil') {
      [$nueva, $error] = guardarFoto('archivo', 400, true);
      if ($error) responderJSON(['error' => $error], 422);
    }
    q("UPDATE usuarios SET foto = ? WHERE id_user = ?", 'si', $nueva, $idUser);
    borrarArchivoFoto($yo['foto']);
    refrescarSesion(usuario());
    responderJSON(['ok' => true, 'foto' => urlFoto($nueva) ?: $yo['avatar']]);

  case 'auto_agregar':
    $patente = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string)($_POST['patente'] ?? '')));
    if (!preg_match('/^[A-Z0-9]{6,8}$/', $patente)) responderJSON(['error' => 'Revisá la patente (ej. AB123CD o ABC123).'], 422);
    $anio = texto('anio', 4);
    if ($anio !== '' && (!preg_match('/^\d{4}$/', $anio) || $anio < 1950 || $anio > date('Y') + 1)) responderJSON(['error' => 'Revisá el año.'], 422);
    $existe = q("SELECT id_vehiculo_usuario, baja, vendido FROM usuarios_vehiculos WHERE id_user = ? AND patente = ?", 'is', $idUser, $patente)[0] ?? null;
    if ($existe && $existe['vendido']) responderJSON(['error' => 'Ese auto lo marcaste como vendido.'], 422);
    if ($existe && !$existe['baja']) responderJSON(['error' => 'Ese auto ya está en tu garage.'], 422);
    if ($existe) {
      q("UPDATE usuarios_vehiculos SET baja = NULL, marca = COALESCE(NULLIF(?, ''), marca), modelo = COALESCE(NULLIF(?, ''), modelo),
        anio = COALESCE(NULLIF(?, ''), anio) WHERE id_vehiculo_usuario = ?", 'sssi', texto('marca', 60), texto('modelo', 80), $anio, $existe['id_vehiculo_usuario']);
      $id = (int)$existe['id_vehiculo_usuario'];
    } else {
      q("INSERT INTO usuarios_vehiculos (id_user, patente, marca, modelo, anio) VALUES (?, ?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''))",
        'issss', $idUser, $patente, texto('marca', 60), texto('modelo', 80), $anio);
      $id = $db->insert_id;
    }
    $auto = autoPropio($id);
    $aviso = motivoBloqueo($auto);
    if (!$aviso) { // la ficha arranca con lo que cargó (sin pisar lo que ya tenga el taller)
      q("INSERT INTO fichas_vehiculo (patente, marca, modelo, anio) VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE marca = COALESCE(marca, VALUES(marca)), modelo = COALESCE(modelo, VALUES(modelo)), anio = COALESCE(anio, VALUES(anio))",
        'sssi', $patente, $auto['marca'], $auto['modelo'], $auto['anio']);
    }
    responderJSON(['ok' => true, 'id' => $id, 'aviso' => $aviso]);

  case 'auto_guardar':
    $auto = autoEditable((int)($_POST['id'] ?? 0));
    if ($error = guardarFicha($db, $auto['patente'], $_POST, false)) responderJSON(['error' => $error], 422);
    $f = leerFicha($db, $auto['patente']);
    q("UPDATE usuarios_vehiculos SET marca = ?, modelo = ?, anio = ?, descripcion = NULLIF(?, '') WHERE id_vehiculo_usuario = ?",
      'ssisi', $f['marca'], $f['modelo'], $f['anio'], texto('descripcion', 100), $auto['id_vehiculo_usuario']);
    responderJSON(['ok' => true]);

  case 'auto_km':
    $auto = autoEditable((int)($_POST['id'] ?? 0));
    $km = (int)preg_replace('/\D/', '', (string)($_POST['km'] ?? ''));
    if ($km < 1 || $km > 2000000) responderJSON(['error' => 'Revisá el kilometraje.'], 422);
    $ultimo = (int)(q("SELECT MAX(km) AS km FROM fichas_km WHERE patente = ? AND baja IS NULL", 's', $auto['patente'])[0]['km'] ?? 0);
    if ($km < $ultimo) responderJSON(['error' => 'Es menor al último kilometraje registrado (' . number_format($ultimo, 0, ',', '.') . ' km).'], 422);
    q("INSERT INTO fichas_km (patente, km, fecha, origen, id_user_carga) VALUES (?, ?, CURDATE(), 'cliente', ?)", 'sii', $auto['patente'], $km, $idUser);
    q("INSERT INTO fichas_vehiculo (patente, km_actual, km_fecha) VALUES (?, ?, CURDATE())
      ON DUPLICATE KEY UPDATE km_fecha = IF(km_actual IS NULL OR VALUES(km_actual) >= km_actual, CURDATE(), km_fecha),
      km_actual = IF(km_actual IS NULL OR VALUES(km_actual) >= km_actual, VALUES(km_actual), km_actual)", 'si', $auto['patente'], $km);
    q("UPDATE usuarios_vehiculos SET km = ? WHERE id_vehiculo_usuario = ?", 'ii', $km, $auto['id_vehiculo_usuario']);
    responderJSON(['ok' => true, 'km' => $km]);

  case 'auto_foto':
    $auto = autoEditable((int)($_POST['id'] ?? 0));
    [$foto, $error] = agregarFotoVehiculo($db, $auto['patente'], $idUser, 'cliente');
    if ($error) responderJSON(['error' => $error], 422);
    responderJSON(['ok' => true, 'foto' => $foto]);

  case 'auto_foto_quitar':
    $auto = autoEditable((int)($_POST['id'] ?? 0));
    $idFoto = (int)($_POST['id_foto'] ?? 0);
    // solo las fotos que subió él (las del taller las maneja el taller)
    if (!quitarFotoVehiculo($db, $idFoto, "patente = ? AND id_user = ? AND subida_por = 'cliente'", 'si', $auto['patente'], $idUser)) {
      responderJSON(['error' => 'Esa foto no se puede quitar.'], 404);
    }
    if ((int)$auto['foto_portada'] === $idFoto) q("UPDATE usuarios_vehiculos SET foto_portada = NULL WHERE id_vehiculo_usuario = ?", 'i', $auto['id_vehiculo_usuario']);
    responderJSON(['ok' => true]);

  case 'auto_portada':
    $auto = autoEditable((int)($_POST['id'] ?? 0));
    $idFoto = (int)($_POST['id_foto'] ?? 0);
    if (!in_array($idFoto, array_map(fn($f) => (int)$f['id_foto'], fotosDelAuto($auto)), true)) responderJSON(['error' => 'Esa foto no es de este auto.'], 404);
    q("UPDATE usuarios_vehiculos SET foto_portada = ? WHERE id_vehiculo_usuario = ?", 'ii', $idFoto, $auto['id_vehiculo_usuario']);
    responderJSON(['ok' => true]);

  case 'auto_vendido':
    $auto = autoPropio((int)($_POST['id'] ?? 0));
    if (!$auto || $auto['vendido']) responderJSON(['error' => 'No existe ese auto en tu garage.'], 404);
    $fecha = trim((string)($_POST['fecha'] ?? '')) ?: date('Y-m-d');
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    if (!$d || $d->format('Y-m-d') !== $fecha) responderJSON(['error' => 'Revisá la fecha de venta.'], 422);
    if ($fecha > date('Y-m-d')) responderJSON(['error' => 'La fecha de venta no puede ser futura.'], 422);
    if (q("SELECT 1 FROM turnos WHERE patente = ? AND id_user = ? AND estado IN ('pendiente', 'confirmado')
        AND TIMESTAMP(fecha_fin, hora_fin) >= NOW()", 'si', $auto['patente'], $idUser)) {
      responderJSON(['error' => 'Este auto tiene un turno pendiente. Cancelalo primero desde Turnos.'], 422);
    }
    q("UPDATE usuarios_vehiculos SET vendido = ? WHERE id_vehiculo_usuario = ?", 'si', $fecha, $auto['id_vehiculo_usuario']);
    responderJSON(['ok' => true]);

  case 'auto_quitar':
    $auto = autoPropio((int)($_POST['id'] ?? 0));
    if (!$auto || $auto['vendido']) responderJSON(['error' => 'No existe ese auto en tu garage.'], 404);
    if (confirmado($auto['patente'])) responderJSON(['error' => 'Este auto está registrado en el taller a tu nombre. Si lo vendiste, marcalo como vendido.'], 422);
    q("UPDATE usuarios_vehiculos SET baja = NOW() WHERE id_vehiculo_usuario = ?", 'i', $auto['id_vehiculo_usuario']);
    responderJSON(['ok' => true]);
}

responderJSON(['error' => 'Acción desconocida'], 400);
