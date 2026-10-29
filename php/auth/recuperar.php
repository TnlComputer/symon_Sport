<?php
// POST email -> manda un link para elegir una contraseña nueva
// POST accion=reenviar, email -> reenvía el mail de confirmación
require_once "lib.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  responderJSON(['error' => t('Método no permitido')], 405);
}

$email = mb_strtolower(trim($_POST['email'] ?? ''));
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
  responderJSON(['error' => t('Ingresá un mail válido.')], 422);
}
if (demasiadasAcciones($conexionApi, 'recuperar')) {
  responderJSON(['error' => t('Demasiados pedidos. Esperá unos minutos.')], 429);
}
registrarAccion($conexionApi, 'recuperar');

$usuario = buscarUsuario($conexionApi, 'email', $email);
$respuesta = ['ok' => true, 'mensaje' => t('Si {0} tiene una cuenta, te llegará un mail en unos minutos.', $email)];
if (!$usuario || !empty($usuario['baja'])) {
  responderJSON($respuesta); // no revelo qué mails están registrados
}

if (($_POST['accion'] ?? '') === 'reenviar') {
  if (!(int)$usuario['verificado']) {
    $link = enviarVerificacion($conexionApi, $usuario);
    if (esLocal()) $respuesta['link_prueba'] = $link;
  }
  responderJSON($respuesta);
}

$token = crearToken($conexionApi, (int)$usuario['id_user'], 'recuperar', 2);
$url = urlSitio() . '/restablecer.php?t=' . $token;
enviarMail($email, t('Elegí una contraseña nueva - Symon Sport'), t('Recuperar contraseña'),
  t('Recibimos un pedido para cambiar tu contraseña. El enlace vence en 2 horas. Si no lo pediste, ignorá este mail.'),
  t('Elegir contraseña nueva'), $url);
if (esLocal()) $respuesta['link_prueba'] = $url;

responderJSON($respuesta);
