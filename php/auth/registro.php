<?php
// POST nombre, apellido, email, clave -> crea la cuenta y manda el mail de confirmación
require_once "lib.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  responderJSON(['error' => t('Método no permitido')], 405);
}
// honeypot anti-spam
if (!empty($_POST['web'])) {
  responderJSON(['ok' => true]);
}

$nombre = mb_substr(trim($_POST['nombre'] ?? ''), 0, 50);
$apellido = mb_substr(trim($_POST['apellido'] ?? ''), 0, 50);
$email = mb_strtolower(trim($_POST['email'] ?? ''));
$clave = (string)($_POST['clave'] ?? '');

$errores = [];
if (mb_strlen($nombre) < 2) $errores[] = t('Ingresá tu nombre.');
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) $errores[] = t('Ingresá un mail válido.');
if (!claveValida($clave)) $errores[] = t('La contraseña debe tener al menos 8 caracteres, con letras y números.');
if ($errores) {
  responderJSON(['error' => implode(' ', $errores)], 422);
}
if (demasiadasAcciones($conexionApi, 'registro')) {
  responderJSON(['error' => t('Demasiados intentos. Esperá unos minutos.')], 429);
}
registrarAccion($conexionApi, 'registro');

$respuesta = ['ok' => true, 'mensaje' => t('Te enviamos un mail a {0}. Abrí el enlace para activar tu cuenta.', $email)];

$existente = buscarUsuario($conexionApi, 'email', $email);
if ($existente) {
  if (!empty($existente['baja'])) responderJSON($respuesta); // cuenta dada de baja: no se reactiva sola
  // no revelo si el mail existe: si no estaba verificado, reenvío la confirmación
  if (!(int)$existente['verificado']) {
    $link = enviarVerificacion($conexionApi, $existente);
    if (esLocal()) $respuesta['link_prueba'] = $link;
  }
  responderJSON($respuesta);
}

$hash = password_hash($clave, PASSWORD_DEFAULT);
$rol = ROL_USUARIO;
$st = $conexionApi->prepare("INSERT INTO usuarios (usuario, email, clave_hash, nombre, apellido, rol, verificado)
  VALUES (?, ?, ?, ?, NULLIF(?, ''), ?, 0)");
$st->bind_param('ssssss', $email, $email, $hash, $nombre, $apellido, $rol);
$st->execute();

$usuario = buscarUsuario($conexionApi, 'id_user', (string)$conexionApi->insert_id);
$link = enviarVerificacion($conexionApi, $usuario);
if (esLocal()) $respuesta['link_prueba'] = $link;

responderJSON($respuesta);
