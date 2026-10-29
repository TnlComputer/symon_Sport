<?php
// Usuarios (solo administrador): todas las cuentas del sitio, clientes y administradores
// GET  accion=listar&q=&rol=&estado=activos|baja|todos&sin_cliente=1
// GET  accion=detalle&id=           -> usuario + sucursales de su cliente (para los tildes)
// GET  accion=clientes&q=           -> búsqueda de clientes para vincular
// POST accion=guardar               -> alta / modificación (datos, rol, cliente y sucursales)
// POST accion=baja | reactivar, id  -> deshabilitar / habilitar la cuenta (no se borra)
// POST accion=acceso, id            -> le envía el link para elegir (o recuperar) la contraseña
// POST accion=desbloquear, id       -> limpia los intentos fallidos de ingreso
// Las cuentas protegidas (la del desarrollador) no aparecen acá ni se pueden tocar: para esta pantalla no existen.
require_once "admin_lib.php";

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';
$yo = (int)usuarioActual()['id_user'];

function responder($datos, $codigo = 200)
{
  http_response_code($codigo);
  salir($datos);
}

const SELECT_USUARIO = "SELECT u.id_user, u.usuario, u.email, u.nombre, u.apellido, u.dni, u.celular, u.rol, u.id_cliente,
    u.todas_sucursales, u.verificado, u.creado, u.ultimo_login, u.baja, u.foto, u.avatar,
    (u.clave_hash IS NOT NULL OR u.clave IS NOT NULL) AS tiene_clave,
    (u.google_id IS NOT NULL) AS con_google, (u.microsoft_id IS NOT NULL) AS con_microsoft, (u.facebook_id IS NOT NULL) AS con_facebook,
    c.empresa_nyp,
    (SELECT GROUP_CONCAT(us.id_sucursal) FROM usuarios_sucursales us WHERE us.id_user = u.id_user AND us.baja IS NULL) AS sucursales,
    (SELECT COUNT(*) FROM login_intentos li WHERE li.identificador IN (LOWER(u.email), LOWER(u.usuario))
      AND li.creado > NOW() - INTERVAL " . VENTANA_INTENTOS_MIN . " MINUTE) AS intentos
  FROM usuarios u LEFT JOIN clientes c ON c.id_cliente = u.id_cliente";

function usuarioPorId($id)
{
  $u = q(SELECT_USUARIO . " WHERE u.id_user = ? AND u.protegido = 0", 'i', $id)[0] ?? null;
  if (!$u) responder(['error' => 'No existe el usuario.'], 404);
  return $u;
}

function publico($u)
{
  $u['foto'] = $u['foto'] ? 'uploads/' . $u['foto'] : $u['avatar'];
  $u['bloqueado'] = (int)$u['intentos'] >= MAX_INTENTOS;
  unset($u['avatar'], $u['intentos']);
  return $u;
}

// siempre tiene que quedar al menos un administrador activo del taller (la cuenta protegida no cuenta)
function quedanOtrosAdmins($idUser)
{
  return (bool)q("SELECT 1 FROM usuarios WHERE rol = 'admin' AND baja IS NULL AND protegido = 0 AND id_user <> ?", 'i', $idUser);
}

switch ($accion) {
  case 'listar':
    $texto = trim($_GET['q'] ?? '');
    $q = '%' . $texto . '%';
    $rol = in_array($_GET['rol'] ?? '', ['admin', 'usuario'], true) ? $_GET['rol'] : '';
    $estado = $_GET['estado'] ?? 'activos';
    $filtroEstado = ['baja' => 'u.baja IS NOT NULL', 'todos' => '1'][$estado] ?? 'u.baja IS NULL';
    $filas = q(SELECT_USUARIO . " WHERE u.protegido = 0 AND $filtroEstado AND (? = '' OR u.rol = ?)
        AND (? = 0 OR (u.id_cliente IS NULL AND u.rol = 'usuario'))
        AND (u.nombre LIKE ? OR u.apellido LIKE ? OR u.email LIKE ? OR u.usuario LIKE ? OR u.dni LIKE ? OR c.empresa_nyp LIKE ?)
      ORDER BY u.rol = 'admin' DESC, u.baja IS NOT NULL, u.nombre, u.apellido LIMIT 500",
      'ssissssss', $rol, $rol, (int)!empty($_GET['sin_cliente']), $q, $q, $q, $q, $q, $q);
    responder(['usuarios' => array_map('publico', $filas), 'yo' => $yo]);

  case 'detalle':
    $u = publico(usuarioPorId((int)($_GET['id'] ?? 0)));
    responder(['usuario' => $u, 'sucursales' => $u['id_cliente'] ? sucursalesDe((int)$u['id_cliente']) : [], 'yo' => $yo]);

  case 'sucursales':
    responder(['sucursales' => sucursalesDe((int)($_GET['id_cliente'] ?? 0))]);

  case 'clientes':
    $q = '%' . trim($_GET['q'] ?? '') . '%';
    responder(['clientes' => q("SELECT id_cliente, empresa_nyp, cuit, mail FROM clientes
      WHERE baja IS NULL AND (empresa_nyp LIKE ? OR cuit LIKE ? OR mail LIKE ?) ORDER BY empresa_nyp LIMIT 15", 'sss', $q, $q, $q)]);

  case 'guardar':
    $idUser = (int)($_POST['id_user'] ?? 0);
    $email = mb_strtolower(campo('email', 190));
    $nombre = campo('nombre', 50);
    $rol = ($_POST['rol'] ?? '') === 'admin' ? 'admin' : 'usuario';
    $idCliente = $rol === 'admin' ? 0 : (int)($_POST['id_cliente'] ?? 0);
    $dni = preg_replace('/[^0-9]/', '', campo('dni', 20));
    $celular = campo('celular', 30);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) responder(['error' => 'Cargá un mail válido: con él ingresa.'], 422);
    if ($nombre === '') responder(['error' => 'Falta el nombre.'], 422);
    if ($idCliente && !q("SELECT 1 FROM clientes WHERE id_cliente = ? AND baja IS NULL", 'i', $idCliente)) {
      responder(['error' => 'Ese cliente no existe o está dado de baja.'], 422);
    }
    if (q("SELECT 1 FROM usuarios WHERE (email = ? OR usuario = ?) AND baja IS NULL AND id_user <> ?", 'ssi', $email, $email, $idUser)) {
      responder(['error' => 'Ese mail ya lo usa otra cuenta.'], 422);
    }

    if ($idUser) {
      $actual = usuarioPorId($idUser);
      if ($actual['baja']) responder(['error' => 'La cuenta está deshabilitada: habilitala primero.'], 422);
      if ($idUser === $yo && $rol !== 'admin') responder(['error' => 'No podés quitarte a vos mismo el rol de administrador.'], 422);
      if ($actual['rol'] === 'admin' && $rol !== 'admin' && !quedanOtrosAdmins($idUser)) {
        responder(['error' => 'Tiene que quedar al menos un administrador.'], 422);
      }
      // el usuario de ingreso acompaña al mail, salvo que sea un usuario del sistema anterior
      $usuario = ($actual['usuario'] === '' || $actual['usuario'] === $actual['email']) ? $email : $actual['usuario'];
      q("UPDATE usuarios SET usuario = ?, email = ?, nombre = ?, apellido = ?, dni = ?, celular = NULLIF(?, ''), rol = ?,
        id_cliente = NULLIF(?, 0) WHERE id_user = ?", 'sssssssii',
        $usuario, $email, $nombre, campo('apellido', 50), $dni, $celular, $rol, $idCliente, $idUser);
      if ($idCliente) {
        guardarPermisos($idUser, $idCliente);
      } else { // sin cliente (o admin): sin tildes de sucursales
        q("UPDATE usuarios SET todas_sucursales = 1 WHERE id_user = ?", 'i', $idUser);
        q("UPDATE usuarios_sucursales SET baja = NOW() WHERE id_user = ? AND baja IS NULL", 'i', $idUser);
      }
      if ($idUser === $yo) $_SESSION['datos']['nombre'] = trim($nombre . ' ' . campo('apellido', 50));
      responder(['ok' => true, 'id' => $idUser]);
    }

    q("INSERT INTO usuarios (usuario, email, nombre, apellido, dni, celular, rol, id_cliente, verificado)
      VALUES (?, ?, ?, ?, ?, NULLIF(?, ''), ?, NULLIF(?, 0), 1)", 'sssssssi',
      $email, $email, $nombre, campo('apellido', 50), $dni, $celular, $rol, $idCliente);
    $idUser = $conexionApi->insert_id;
    if ($idCliente) guardarPermisos($idUser, $idCliente);
    $envio = !empty($_POST['enviar_acceso']) ? enviarAccesoCliente($conexionApi, $idUser) : null;
    responder(['ok' => true, 'id' => $idUser, 'enviado' => $envio[0] ?? null, 'link_prueba' => ($envio && esLocal()) ? $envio[1] : null]);

  case 'baja':
    $u = usuarioPorId((int)($_POST['id'] ?? 0));
    if ((int)$u['id_user'] === $yo) responder(['error' => 'No podés deshabilitar tu propia cuenta.'], 422);
    if ($u['rol'] === 'admin' && !quedanOtrosAdmins((int)$u['id_user'])) responder(['error' => 'Tiene que quedar al menos un administrador.'], 422);
    q("UPDATE usuarios SET baja = NOW() WHERE id_user = ? AND baja IS NULL", 'i', $u['id_user']);
    responder(['ok' => true]);

  case 'reactivar':
    $u = usuarioPorId((int)($_POST['id'] ?? 0));
    if (q("SELECT 1 FROM usuarios WHERE (email = ? OR usuario = ?) AND baja IS NULL AND id_user <> ?", 'ssi',
      (string)$u['email'], (string)$u['usuario'], $u['id_user'])) {
      responder(['error' => 'Hay otra cuenta activa con ese mail: cambiale el mail a una de las dos.'], 422);
    }
    q("UPDATE usuarios SET baja = NULL WHERE id_user = ?", 'i', $u['id_user']);
    responder(['ok' => true]);

  case 'acceso':
    $u = usuarioPorId((int)($_POST['id'] ?? 0));
    $envio = enviarAccesoCliente($conexionApi, (int)$u['id_user']);
    if (!$envio) responder(['error' => 'La cuenta no tiene un mail válido o está deshabilitada.'], 422);
    responder(['ok' => true, 'enviado' => $envio[0], 'link_prueba' => esLocal() ? $envio[1] : null]);

  case 'desbloquear':
    $u = usuarioPorId((int)($_POST['id'] ?? 0));
    foreach (array_unique(array_filter([mb_strtolower((string)$u['email']), mb_strtolower((string)$u['usuario'])])) as $identificador) {
      limpiarIntentos($conexionApi, $identificador);
    }
    responder(['ok' => true]);
}

responder(['error' => 'Acción desconocida'], 400);
