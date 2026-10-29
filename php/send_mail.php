<?php
// Formulario de contacto (público): valida, limita envíos y avisa al taller. Respuesta: { err, message }
require_once __DIR__ . "/auth/lib.php";
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit;
}

$nombre = mb_substr(trim($_POST['name'] ?? ''), 0, 100);
$email = mb_substr(trim($_POST['email'] ?? ''), 0, 120);
$asunto = mb_substr(trim($_POST['subject'] ?? ''), 0, 150);
$comentario = mb_substr(trim($_POST['comments'] ?? ''), 0, 1000);

if ($nombre === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $asunto === '' || $comentario === '') {
  http_response_code(422);
  echo json_encode(['err' => true, 'message' => 'Completá todos los campos con datos válidos.'], JSON_UNESCAPED_UNICODE);
  exit;
}
if (demasiadasAcciones($conexionApi, 'contacto')) {
  http_response_code(429);
  echo json_encode(['err' => true, 'message' => 'Recibimos varios mensajes seguidos. Probá de nuevo en unos minutos.'], JSON_UNESCAPED_UNICODE);
  exit;
}
registrarAccion($conexionApi, 'contacto');

// destino: el mail de avisos configurado en turnos (o el del taller por defecto)
$destino = $conexionApi->query("SELECT valor FROM turnos_config WHERE clave = 'email_aviso'")->fetch_row()[0] ?? '';
if (!filter_var($destino, FILTER_VALIDATE_EMAIL)) $destino = 'anacoretomarcelo72@gmail.com';

$texto = 'Mensaje enviado desde el formulario de contacto de la página.</p><ul>'
  . '<li><b>Nombre:</b> ' . e($nombre) . '</li>'
  . '<li><b>Email:</b> ' . e($email) . '</li>'
  . '<li><b>Asunto:</b> ' . e($asunto) . '</li></ul>'
  . '<p style="white-space:pre-line">' . e($comentario);
$ok = enviarMail($destino, 'Contacto web: ' . preg_replace('/[\r\n]+/', ' ', $asunto), 'Nuevo mensaje de contacto', $texto);

echo json_encode($ok
  ? ['err' => false, 'message' => '¡Gracias! Recibimos tu mensaje y te respondemos a la brevedad.']
  : ['err' => true, 'message' => 'No pudimos enviar el mensaje. Escribinos por WhatsApp.'], JSON_UNESCAPED_UNICODE);
