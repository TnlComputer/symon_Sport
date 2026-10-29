<?php
// POST email (o usuario), clave -> inicia sesión
require_once "lib.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  responderJSON(['error' => t('Método no permitido')], 405);
}

$identificador = mb_strtolower(trim($_POST['email'] ?? $_POST['user_txt'] ?? ''));
$clave = (string)($_POST['clave'] ?? $_POST['pass_txt'] ?? '');

if ($identificador === '' || $clave === '') {
  responderJSON(['error' => t('Ingresá tu mail y contraseña.')], 422);
}
if (demasiadosIntentos($conexionApi, $identificador)) {
  responderJSON(['error' => t('Demasiados intentos. Esperá unos minutos y volvé a probar.')], 429);
}

// se puede ingresar con el mail o con el usuario del sistema anterior
$usuario = str_contains($identificador, '@')
  ? buscarUsuario($conexionApi, 'email', $identificador)
  : buscarUsuario($conexionApi, 'usuario', $identificador);

if ($usuario && !empty($usuario['baja'])) $usuario = null; // dado de baja: como si no existiera

$ok = false;
if ($usuario) {
  if (!empty($usuario['clave_hash'])) {
    $ok = password_verify($clave, $usuario['clave_hash']);
  } elseif ($usuario['clave'] !== null && $usuario['clave'] !== '' && hash_equals((string)$usuario['clave'], $clave)) {
    // usuario del sistema anterior: encripto su clave y borro la copia en texto plano
    $ok = true;
    $hash = password_hash($clave, PASSWORD_DEFAULT);
    $st = $conexionApi->prepare("UPDATE usuarios SET clave_hash = ?, clave = NULL WHERE id_user = ?");
    $st->bind_param('si', $hash, $usuario['id_user']);
    $st->execute();
  }
}

if (!$ok) {
  registrarIntentoFallido($conexionApi, $identificador);
  responderJSON(['error' => t('El mail o la contraseña no son correctos.')], 401);
}

if (!(int)$usuario['verificado']) {
  responderJSON([
    'error' => t('Todavía no confirmaste tu mail. Revisá tu casilla (y el correo no deseado).'),
    'sin_verificar' => true,
  ], 403);
}

if (!empty($usuario['clave_hash']) && password_needs_rehash($usuario['clave_hash'], PASSWORD_DEFAULT)) {
  $hash = password_hash($clave, PASSWORD_DEFAULT);
  $st = $conexionApi->prepare("UPDATE usuarios SET clave_hash = ? WHERE id_user = ?");
  $st->bind_param('si', $hash, $usuario['id_user']);
  $st->execute();
}

limpiarIntentos($conexionApi, $identificador);
$datos = iniciarSesion($conexionApi, $usuario);
responderJSON(['ok' => true, 'usuario' => $datos, 'destino' => destinoSegunRol($datos['rol'])]);
