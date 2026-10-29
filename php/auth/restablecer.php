<?php
// POST t=token, clave -> guarda la contraseña nueva e inicia sesión
require_once "lib.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  responderJSON(['error' => t('Método no permitido')], 405);
}

$clave = (string)($_POST['clave'] ?? '');
if (!claveValida($clave)) {
  responderJSON(['error' => t('La contraseña debe tener al menos 8 caracteres, con letras y números.')], 422);
}

$idUser = consumirToken($conexionApi, $_POST['t'] ?? '', 'recuperar');
$cuenta = $idUser ? buscarUsuario($conexionApi, 'id_user', (string)$idUser) : null;
if (!$cuenta || !empty($cuenta['baja'])) {
  responderJSON(['error' => t('El enlace venció o ya fue usado. Pedí uno nuevo.')], 410);
}

// la clave vieja en texto plano se borra; el mail queda verificado porque llegó al link
$hash = password_hash($clave, PASSWORD_DEFAULT);
$st = $conexionApi->prepare("UPDATE usuarios SET clave_hash = ?, clave = NULL, verificado = 1 WHERE id_user = ?");
$st->bind_param('si', $hash, $idUser);
$st->execute();

$usuario = buscarUsuario($conexionApi, 'id_user', (string)$idUser);
limpiarIntentos($conexionApi, $usuario['email'] ?? '');
$datos = iniciarSesion($conexionApi, $usuario);
responderJSON(['ok' => true, 'destino' => destinoSegunRol($datos['rol'])]);
