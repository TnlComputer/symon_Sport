<?php
// Clientes (solo administrador)
// GET  accion=listar&q=&bajas=1     -> clientes con sus patentes y cantidad de usuarios
// GET  accion=ficha&id=             -> cliente + vehículos + usuarios + presupuestos + resumen
// GET  accion=historial&patente=&id_cliente= -> reparaciones y presupuestos de la patente (todos sus dueños)
// POST accion=guardar               -> alta / modificación del cliente
// POST accion=baja | reactivar, id  -> baja lógica del cliente (no se borra)
// POST accion=vehiculo              -> alta de vehículo (con traspaso si era de otro cliente)
// GET  accion=ficha_vehiculo&patente= -> ficha técnica completa (todos los campos) y fotos
// POST accion=ficha_vehiculo_guardar, patente -> guarda la ficha (y marca/modelo/año en el vehículo)
// POST accion=vehiculo_foto, patente, archivo / accion=vehiculo_foto_quitar, id_foto
// POST accion=vehiculo_baja         -> el vehículo deja de ser del cliente (queda en el historial)
// POST accion=sucursal / sucursal_baja -> sucursales del cliente (empresas con varias sedes)
// POST accion=vehiculo_sucursal, id_vehiculo, id_sucursal -> a qué sucursal pertenece el vehículo
// POST accion=usuario               -> alta / modificación de un usuario del cliente (con las sucursales que puede ver)
// POST accion=usuario_acceso, id    -> le envía el link para elegir contraseña
// POST accion=usuario_baja, id      -> baja lógica del usuario
require_once "admin_lib.php";
require_once "vehiculo_lib.php";

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

function responder($datos, $codigo = 200)
{
  http_response_code($codigo);
  salir($datos);
}

function clienteActivo($id)
{
  return q("SELECT * FROM clientes WHERE id_cliente = ? AND baja IS NULL", 'i', $id)[0] ?? null;
}

switch ($accion) {
  case 'listar':
    $texto = trim($_GET['q'] ?? '');
    $q = '%' . $texto . '%';
    $patente = '%' . patenteLimpia($texto) . '%';
    $bajas = !empty($_GET['bajas']);
    responder(['clientes' => q("SELECT c.id_cliente, c.empresa_nyp, c.cuit, c.celular, c.telefono, c.mail, c.localidad,
        c.contacto, c.baja,
        (SELECT GROUP_CONCAT(v.patente ORDER BY v.patente SEPARATOR ' ') FROM vehiculos v
          WHERE v.id_cliente = c.id_cliente AND v.baja IS NULL) AS patentes,
        (SELECT COUNT(*) FROM usuarios u WHERE u.id_cliente = c.id_cliente AND u.baja IS NULL) AS usuarios,
        (SELECT MAX(r.fecha_trab) FROM reparaciones r WHERE r.id_cliente = c.id_cliente AND r.baja IS NULL) AS ultima_visita
      FROM clientes c
      WHERE (c.baja IS NULL) <> ? AND (c.empresa_nyp LIKE ? OR c.cuit LIKE ? OR c.celular LIKE ? OR c.telefono LIKE ?
        OR c.mail LIKE ? OR c.contacto LIKE ? OR (? <> '%%' AND EXISTS (SELECT 1 FROM vehiculos v
          WHERE v.id_cliente = c.id_cliente AND v.baja IS NULL AND v.patente LIKE ?)))
      ORDER BY c.empresa_nyp LIMIT 300", 'issssssss', (int)$bajas, $q, $q, $q, $q, $q, $q, $patente, $patente)]);

  case 'ficha':
    $id = (int)($_GET['id'] ?? 0);
    $c = q("SELECT * FROM clientes WHERE id_cliente = ?", 'i', $id)[0] ?? null; // también los dados de baja
    if (!$c) responder(['error' => 'No existe el cliente.'], 404);
    $vehiculos = q("SELECT v.id_vehiculo, v.patente, v.marca, v.modelo, v.anio, v.id_sucursal, f.km_actual, f.km_fecha,
        (SELECT MAX(r.fecha_trab) FROM reparaciones r WHERE r.patente = v.patente AND r.baja IS NULL) AS ultima_visita,
        (SELECT COUNT(*) FROM reparaciones r WHERE r.patente = v.patente AND r.baja IS NULL) AS reparaciones,
        (SELECT f.archivo FROM vehiculos_fotos f WHERE f.patente = v.patente AND f.baja IS NULL ORDER BY f.subida_por = 'taller' DESC, f.id_foto LIMIT 1) AS foto,
        (SELECT COUNT(*) FROM vehiculos_fotos f WHERE f.patente = v.patente AND f.baja IS NULL) AS fotos,
        (SELECT MAX(uv.vendido) FROM usuarios_vehiculos uv JOIN usuarios u ON u.id_user = uv.id_user
          WHERE uv.patente = v.patente AND u.id_cliente = v.id_cliente AND uv.baja IS NULL) AS vendido_cliente
      FROM vehiculos v LEFT JOIN fichas_vehiculo f ON f.patente = v.patente
      WHERE v.id_cliente = ? AND v.baja IS NULL ORDER BY v.patente", 'i', $id);
    foreach ($vehiculos as &$v) $v['foto'] = urlFoto($v['foto']);
    unset($v);
    // autos que fueron del cliente y pasaron a otro dueño (o los dio de baja)
    $anteriores = q("SELECT v.patente, v.marca, v.modelo, DATE(MAX(v.baja)) AS hasta FROM vehiculos v
      WHERE v.id_cliente = ? AND v.baja IS NOT NULL
        AND NOT EXISTS (SELECT 1 FROM vehiculos a WHERE a.id_cliente = v.id_cliente AND a.patente = v.patente AND a.baja IS NULL)
      GROUP BY v.patente, v.marca, v.modelo ORDER BY hasta DESC", 'i', $id);
    $usuarios = q("SELECT id_user, usuario, email, nombre, apellido, dni, ultimo_login, todas_sucursales,
        (SELECT GROUP_CONCAT(us.id_sucursal) FROM usuarios_sucursales us WHERE us.id_user = usuarios.id_user AND us.baja IS NULL) AS sucursales,
        (clave_hash IS NOT NULL OR google_id IS NOT NULL OR microsoft_id IS NOT NULL OR facebook_id IS NOT NULL) AS tiene_acceso
      FROM usuarios WHERE id_cliente = ? AND rol = 'usuario' AND baja IS NULL ORDER BY nombre, apellido", 'i', $id);
    $presupuestos = q("SELECT p.id_presu, p.fecha_presu, p.descripcion_presu, p.usado, p.total_presu, p.respuesta_cliente, v.patente
      FROM presupuestos p LEFT JOIN vehiculos v ON v.id_vehiculo = p.id_vehiculo
      WHERE p.id_cliente = ? AND p.baja IS NULL ORDER BY p.id_presu DESC LIMIT 30", 'i', $id);
    $resumen = q("SELECT
        (SELECT COUNT(*) FROM reparaciones WHERE id_cliente = ? AND baja IS NULL) AS reparaciones,
        (SELECT COUNT(*) FROM presupuestos WHERE id_cliente = ? AND baja IS NULL AND usado = 0) AS pendientes,
        (SELECT COALESCE(SUM(total_presu), 0) FROM presupuestos WHERE id_cliente = ? AND baja IS NULL AND usado = 1) AS trabajado,
        (SELECT MAX(fecha_trab) FROM reparaciones WHERE id_cliente = ? AND baja IS NULL) AS ultima_visita", 'iiii', $id, $id, $id, $id)[0];
    responder(['cliente' => $c, 'sucursales' => sucursalesDe($id), 'vehiculos' => $vehiculos, 'anteriores' => $anteriores, 'usuarios' => $usuarios,
      'presupuestos' => $presupuestos, 'resumen' => $resumen]);

  case 'historial':
    // todo lo que se le hizo al auto, también con dueños anteriores (el admin ve de quién era)
    $patente = patenteLimpia($_GET['patente'] ?? '');
    $idCliente = (int)($_GET['id_cliente'] ?? 0);
    $reparaciones = q("SELECT r.id_trabajo, r.fecha_trab, r.fecha_entrega, r.km, r.factura, r.observaciones, r.trabajo_realizado, r.listo, r.presupuesto,
        r.id_cliente, c.empresa_nyp, p.descripcion_presu, p.total_presu
      FROM reparaciones r LEFT JOIN clientes c ON c.id_cliente = r.id_cliente
      LEFT JOIN presupuestos p ON p.id_presu = r.presupuesto
      WHERE r.patente = ? AND r.baja IS NULL ORDER BY r.fecha_trab DESC, r.id_trabajo DESC", 's', $patente);
    $pendientes = q("SELECT p.id_presu, p.fecha_presu, p.descripcion_presu, p.total_presu, p.respuesta_cliente, p.respuesta_motivo
      FROM presupuestos p JOIN vehiculos v ON v.id_vehiculo = p.id_vehiculo
      WHERE v.patente = ? AND p.usado = 0 AND p.baja IS NULL ORDER BY p.id_presu DESC", 's', $patente);
    $titulares = q("SELECT t.id_cliente, c.empresa_nyp, t.desde, t.hasta FROM vehiculos_titulares t
      LEFT JOIN clientes c ON c.id_cliente = t.id_cliente
      WHERE t.patente = ? AND t.baja IS NULL ORDER BY t.desde DESC", 's', $patente);
    // lo que cargaron los clientes desde Mi garage (trabajos de otros talleres o hechos por ellos)
    $otros = q("SELECT h.fecha, h.km, h.descripcion, h.detalle, h.realizado_por, h.taller_nombre, h.costo,
        TRIM(CONCAT(COALESCE(u.nombre, ''), ' ', COALESCE(u.apellido, ''))) AS quien
      FROM fichas_historial h LEFT JOIN usuarios u ON u.id_user = h.id_user_carga
      WHERE h.patente = ? AND h.realizado_por <> 'symon' AND h.baja IS NULL ORDER BY h.fecha DESC", 's', $patente);
    foreach ($reparaciones as &$r) $r['dueno_anterior'] = (int)$r['id_cliente'] !== $idCliente;
    responder(['reparaciones' => $reparaciones, 'pendientes' => $pendientes, 'otros' => $otros, 'titulares' => $titulares]);

  case 'guardar':
    $id = (int)($_POST['id_cliente'] ?? 0);
    $nombre = campo('empresa_nyp', 100);
    $cuit = preg_replace('/[^0-9]/', '', campo('cuit', 20));
    $mail = mb_strtolower(campo('mail', 120));
    $celular = campo('celular', 30);
    if ($nombre === '') responder(['error' => 'Falta el nombre o la empresa.'], 422);
    if ($celular === '' && campo('telefono', 30) === '' && $mail === '') {
      responder(['error' => 'Cargá al menos un celular, teléfono o mail para poder contactarlo.'], 422);
    }
    if ($cuit !== '' && !preg_match('/^\d{7,11}$/', $cuit)) responder(['error' => 'El CUIT / DNI debe tener entre 7 y 11 números.'], 422);
    if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) responder(['error' => 'El mail no es válido.'], 422);
    if ($cuit !== '' && ($otro = q("SELECT empresa_nyp FROM clientes WHERE cuit = ? AND id_cliente <> ? AND baja IS NULL", 'si', $cuit, $id))) {
      responder(['error' => "Ese CUIT / DNI ya es de {$otro[0]['empresa_nyp']}."], 422);
    }
    $datos = [$nombre, campo('direccion', 150), campo('localidad', 60), campo('provincia', 60), campo('telefono', 30),
      $cuit, $celular, campo('cpost', 10), $mail, campo('contacto', 60)];
    if ($id) {
      if (!clienteActivo($id)) responder(['error' => 'No existe el cliente.'], 404);
      q("UPDATE clientes SET empresa_nyp = ?, direccion = ?, localidad = ?, provincia = ?, telefono = ?, cuit = ?, celular = ?,
        cpost = ?, mail = ?, contacto = ? WHERE id_cliente = ?", 'ssssssssssi', ...[...$datos, $id]);
    } else {
      q("INSERT INTO clientes (empresa_nyp, direccion, localidad, provincia, telefono, cuit, celular, cpost, mail, contacto)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", 'ssssssssss', ...$datos);
      $id = $conexionApi->insert_id;
    }
    responder(['ok' => true, 'id' => $id]);

  case 'baja':
  case 'reactivar':
    $id = (int)($_POST['id'] ?? 0);
    q($accion === 'baja'
      ? "UPDATE clientes SET baja = NOW() WHERE id_cliente = ? AND baja IS NULL"
      : "UPDATE clientes SET baja = NULL WHERE id_cliente = ?", 'i', $id);
    responder(['ok' => true]);

  case 'vehiculo':
    $idCliente = (int)($_POST['id_cliente'] ?? 0);
    if (!clienteActivo($idCliente)) responder(['error' => 'No existe el cliente.'], 404);
    $idSucursal = (int)($_POST['id_sucursal'] ?? 0);
    if ($idSucursal && !sucursalDelCliente($idSucursal, $idCliente)) responder(['error' => 'Esa sucursal no es de este cliente.'], 422);
    $r = altaVehiculo($idCliente, $_POST['patente'] ?? '', $_POST['marca'] ?? '', $_POST['modelo'] ?? '',
      $_POST['anio'] ?? '', !empty($_POST['confirmar_traspaso']));
    if (!empty($r['ok']) && $idSucursal) q("UPDATE vehiculos SET id_sucursal = ? WHERE id_vehiculo = ?", 'ii', $idSucursal, $r['id_vehiculo']);
    responder($r, isset($r['error']) ? 422 : 200);

  case 'vehiculo_sucursal':
    $idVehiculo = (int)($_POST['id_vehiculo'] ?? 0);
    $v = q("SELECT id_cliente FROM vehiculos WHERE id_vehiculo = ? AND baja IS NULL", 'i', $idVehiculo)[0] ?? null;
    if (!$v) responder(['error' => 'No existe el vehículo.'], 404);
    $idSucursal = (int)($_POST['id_sucursal'] ?? 0) ?: null;
    if ($idSucursal && !sucursalDelCliente($idSucursal, (int)$v['id_cliente'])) responder(['error' => 'Esa sucursal no es de este cliente.'], 422);
    q("UPDATE vehiculos SET id_sucursal = ? WHERE id_vehiculo = ?", 'ii', $idSucursal, $idVehiculo);
    responder(['ok' => true]);

  case 'sucursal':
    $idSucursal = (int)($_POST['id_sucursal'] ?? 0);
    $idCliente = (int)($_POST['id_cliente'] ?? 0);
    $nombre = campo('nombre', 80);
    if (!clienteActivo($idCliente)) responder(['error' => 'No existe el cliente.'], 404);
    if ($nombre === '') responder(['error' => 'Falta el nombre de la sucursal.'], 422);
    if (q("SELECT 1 FROM clientes_sucursales WHERE id_cliente = ? AND nombre = ? AND id_sucursal <> ? AND baja IS NULL", 'isi', $idCliente, $nombre, $idSucursal)) {
      responder(['error' => 'Ya hay una sucursal con ese nombre.'], 422);
    }
    $datos = [$nombre, campo('direccion', 150), campo('localidad', 60), campo('telefono', 30), campo('contacto', 60)];
    if ($idSucursal) {
      if (!sucursalDelCliente($idSucursal, $idCliente)) responder(['error' => 'No existe la sucursal.'], 404);
      q("UPDATE clientes_sucursales SET nombre = ?, direccion = NULLIF(?, ''), localidad = NULLIF(?, ''), telefono = NULLIF(?, ''),
        contacto = NULLIF(?, '') WHERE id_sucursal = ?", 'sssssi', ...[...$datos, $idSucursal]);
    } else {
      q("INSERT INTO clientes_sucursales (id_cliente, nombre, direccion, localidad, telefono, contacto)
        VALUES (?, ?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''))", 'isssss', $idCliente, ...$datos);
      $idSucursal = $conexionApi->insert_id;
    }
    responder(['ok' => true, 'id' => $idSucursal]);

  case 'sucursal_baja':
    $idSucursal = (int)($_POST['id_sucursal'] ?? 0);
    $suc = q("SELECT nombre FROM clientes_sucursales WHERE id_sucursal = ? AND baja IS NULL", 'i', $idSucursal)[0] ?? null;
    if (!$suc) responder(['error' => 'No existe la sucursal.'], 404);
    if (q("SELECT 1 FROM vehiculos WHERE id_sucursal = ? AND baja IS NULL", 'i', $idSucursal)) {
      responder(['error' => 'La sucursal ' . $suc['nombre'] . ' todavía tiene vehículos: pasalos a otra sucursal primero.'], 422);
    }
    q("UPDATE clientes_sucursales SET baja = NOW() WHERE id_sucursal = ?", 'i', $idSucursal);
    q("UPDATE usuarios_sucursales SET baja = NOW() WHERE id_sucursal = ? AND baja IS NULL", 'i', $idSucursal);
    responder(['ok' => true]);

  case 'ficha_vehiculo':
    $patente = patenteLimpia($_GET['patente'] ?? '');
    if (!q("SELECT 1 FROM vehiculos WHERE patente = ?", 's', $patente)) responder(['error' => 'No existe el vehículo.'], 404);
    responder(['campos' => camposFichaPublicos(true), 'ficha' => leerFicha($conexionApi, $patente),
      'fotos' => fotosVehiculo($conexionApi, $patente), 'max_fotos' => MAX_FOTOS]);

  case 'ficha_vehiculo_guardar':
    $patente = patenteLimpia($_POST['patente'] ?? '');
    if (!q("SELECT 1 FROM vehiculos WHERE patente = ? AND baja IS NULL", 's', $patente)) responder(['error' => 'No existe el vehículo.'], 404);
    if ($error = guardarFicha($conexionApi, $patente, $_POST, true)) responder(['error' => $error], 422);
    $f = leerFicha($conexionApi, $patente);
    q("UPDATE vehiculos SET marca = COALESCE(?, marca), modelo = COALESCE(?, modelo), anio = COALESCE(?, anio)
      WHERE patente = ? AND baja IS NULL", 'ssss', $f['marca'], $f['modelo'], $f['anio'], $patente);
    responder(['ok' => true]);

  case 'vehiculo_foto':
    $patente = patenteLimpia($_POST['patente'] ?? '');
    if (!q("SELECT 1 FROM vehiculos WHERE patente = ? AND baja IS NULL", 's', $patente)) responder(['error' => 'No existe el vehículo.'], 404);
    [$foto, $error] = agregarFotoVehiculo($conexionApi, $patente, (int)usuarioActual()['id_user'], 'taller');
    responder($error ? ['error' => $error] : ['ok' => true, 'foto' => $foto], $error ? 422 : 200);

  case 'vehiculo_foto_quitar':
    // el taller puede quitar cualquier foto (por ejemplo, una que no corresponde)
    if (!quitarFotoVehiculo($conexionApi, (int)($_POST['id_foto'] ?? 0), '1')) responder(['error' => 'No existe la foto.'], 404);
    responder(['ok' => true]);

  case 'vehiculo_baja':
    // lo vendió o ya no lo trae: deja de figurar en su lista, el historial queda con la patente
    $idVehiculo = (int)($_POST['id_vehiculo'] ?? 0);
    $v = q("SELECT patente, id_cliente FROM vehiculos WHERE id_vehiculo = ? AND baja IS NULL", 'i', $idVehiculo)[0] ?? null;
    if (!$v) responder(['error' => 'No existe el vehículo.'], 404);
    q("UPDATE vehiculos SET baja = NOW() WHERE id_vehiculo = ?", 'i', $idVehiculo);
    q("UPDATE vehiculos_titulares SET hasta = CURDATE() WHERE patente = ? AND id_cliente = ? AND hasta IS NULL",
      'si', $v['patente'], $v['id_cliente']);
    responder(['ok' => true]);

  case 'usuario':
    // usuario del cliente para ver Mi garage: entra con su mail; la contraseña la elige él con el link
    $idUser = (int)($_POST['id_user'] ?? 0);
    $idCliente = (int)($_POST['id_cliente'] ?? 0);
    $email = mb_strtolower(campo('email', 190));
    $nombre = campo('nombre', 50);
    $apellido = campo('apellido', 50);
    $dni = preg_replace('/[^0-9]/', '', campo('dni', 20));
    if (!clienteActivo($idCliente)) responder(['error' => 'No existe el cliente.'], 404);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) responder(['error' => 'Cargá un mail válido: con él va a ingresar.'], 422);
    if ($nombre === '') responder(['error' => 'Falta el nombre.'], 422);

    $existente = q("SELECT id_user, id_cliente, rol FROM usuarios WHERE (email = ? OR usuario = ?) AND baja IS NULL AND id_user <> ?",
      'ssi', $email, $email, $idUser)[0] ?? null;
    if ($existente && ($existente['rol'] !== 'usuario' || ($existente['id_cliente'] && (int)$existente['id_cliente'] !== $idCliente))) {
      responder(['error' => 'Ese mail ya lo usa otra cuenta.'], 422);
    }

    if ($idUser) {
      $actual = q("SELECT usuario, email FROM usuarios WHERE id_user = ? AND id_cliente = ? AND rol = 'usuario' AND baja IS NULL",
        'ii', $idUser, $idCliente)[0] ?? null;
      if (!$actual) responder(['error' => 'No existe el usuario.'], 404);
      if ($existente) responder(['error' => 'Ese mail ya lo usa otra cuenta.'], 422);
      // el usuario de ingreso acompaña al mail, salvo que sea un usuario del sistema anterior
      $usuario = ($actual['usuario'] === '' || $actual['usuario'] === $actual['email']) ? $email : $actual['usuario'];
      q("UPDATE usuarios SET usuario = ?, email = ?, nombre = ?, apellido = ?, dni = ? WHERE id_user = ?",
        'sssssi', $usuario, $email, $nombre, $apellido, $dni, $idUser);
      guardarPermisos($idUser, $idCliente);
      responder(['ok' => true, 'id' => $idUser]);
    }

    if ($existente) {
      // ya se había registrado en la página: se lo vincula al cliente
      q("UPDATE usuarios SET id_cliente = ?, nombre = IF(nombre = '' OR nombre IS NULL, ?, nombre),
        apellido = IF(apellido = '' OR apellido IS NULL, ?, apellido), dni = IF(dni = '' OR dni IS NULL, ?, dni)
        WHERE id_user = ?", 'isssi', $idCliente, $nombre, $apellido, $dni, $existente['id_user']);
      guardarPermisos((int)$existente['id_user'], $idCliente);
      responder(['ok' => true, 'id' => (int)$existente['id_user'], 'vinculado' => true]);
    }

    q("INSERT INTO usuarios (usuario, email, nombre, apellido, dni, rol, id_cliente, verificado)
      VALUES (?, ?, ?, ?, ?, 'usuario', ?, 1)", 'sssssi', $email, $email, $nombre, $apellido, $dni, $idCliente);
    $idUser = $conexionApi->insert_id;
    guardarPermisos($idUser, $idCliente);
    $envio = !empty($_POST['enviar_acceso']) ? enviarAccesoCliente($conexionApi, $idUser) : null;
    responder(['ok' => true, 'id' => $idUser, 'enviado' => $envio[0] ?? null,
      'link_prueba' => ($envio && esLocal()) ? $envio[1] : null]);

  case 'usuario_acceso':
    $idUser = (int)($_POST['id'] ?? 0);
    if (!q("SELECT 1 FROM usuarios WHERE id_user = ? AND rol = 'usuario' AND baja IS NULL", 'i', $idUser)) {
      responder(['error' => 'No existe el usuario.'], 404);
    }
    $envio = enviarAccesoCliente($conexionApi, $idUser);
    if (!$envio) responder(['error' => 'El usuario no tiene un mail válido cargado.'], 422);
    responder(['ok' => true, 'enviado' => $envio[0], 'link_prueba' => esLocal() ? $envio[1] : null]);

  case 'usuario_baja':
    q("UPDATE usuarios SET baja = NOW() WHERE id_user = ? AND rol = 'usuario' AND baja IS NULL", 'i', (int)($_POST['id'] ?? 0));
    responder(['ok' => true]);
}

responder(['error' => 'Acción desconocida'], 400);
