<?php
// Cerrar sesión (solo por POST: lleva el token CSRF, así otro sitio no puede desloguear al usuario)
require_once __DIR__ . "/../includes/bootstrap.php";
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode('metodo');
  exit;
}
$_SESSION = [];
$p = session_get_cookie_params();
setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => $p['path'], 'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax']);
session_destroy();
echo json_encode('inexistente');
