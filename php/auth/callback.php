<?php
// Retorno de Google / Microsoft / Facebook: valida, busca o crea la cuenta e inicia sesión
require_once "oauth_lib.php";

$volver = function ($aviso) {
  header('Location: /login.php?aviso=' . urlencode($aviso));
  exit;
};

$oauth = $_SESSION['oauth'] ?? null;
unset($_SESSION['oauth']);

if (!$oauth || !hash_equals($oauth['state'], (string)($_GET['state'] ?? ''))) {
  $volver('sesion_vencida');
}
if (!empty($_GET['error']) || empty($_GET['code'])) {
  $volver('cancelado');
}

$proveedor = $oauth['proveedor'];
$datos = proveedoresOAuth()[$proveedor];
$cred = configOAuth()[$proveedor];

$token = pedirHTTP($datos['token'], [
  'client_id' => $cred['client_id'],
  'client_secret' => $cred['client_secret'],
  'code' => $_GET['code'],
  'redirect_uri' => urlCallback(),
  'grant_type' => 'authorization_code',
]);
if (empty($token['access_token'])) {
  $volver('error_proveedor');
}

$perfil = perfilNormalizado($proveedor, pedirHTTP($datos['perfil'], null, $token['access_token']) ?? [], $token['id_token'] ?? null);
if (empty($perfil['id'])) {
  $volver('error_proveedor');
}
$email = $perfil['email'] ? mb_strtolower($perfil['email']) : null;
if (!$email) {
  $volver('sin_mail');
}

$campoId = $proveedor . '_id';
$db = $conexionApi;

// 1) ya entró antes con este proveedor
$usuario = buscarUsuario($db, $campoId, $perfil['id']);

// 2) existe una cuenta con ese mail: la vinculo solo si el proveedor garantiza que el mail es suyo
if (!$usuario) {
  $existente = buscarUsuario($db, 'email', $email);
  if ($existente) {
    if (!empty($existente['baja'])) {
      $volver('cuenta_baja');
    }
    if (!$perfil['email_verificado']) {
      $volver('mail_existente');
    }
    $st = $db->prepare("UPDATE usuarios SET $campoId = ?, verificado = 1, avatar = COALESCE(avatar, ?) WHERE id_user = ?");
    $st->bind_param('ssi', $perfil['id'], $perfil['avatar'], $existente['id_user']);
    $st->execute();
    $usuario = buscarUsuario($db, 'id_user', (string)$existente['id_user']);
  }
}

// 3) cuenta nueva (solo con un mail confirmado por el proveedor)
if (!$usuario && !$perfil['email_verificado']) {
  $volver('mail_no_verificado');
}
if (!$usuario) {
  $rol = ROL_USUARIO;
  $nombre = mb_substr($perfil['nombre'] ?: strtok($email, '@'), 0, 50);
  $apellido = mb_substr($perfil['apellido'], 0, 50);
  $st = $db->prepare("INSERT INTO usuarios (usuario, email, nombre, apellido, rol, verificado, $campoId, avatar)
    VALUES (?, ?, ?, NULLIF(?, ''), ?, 1, ?, ?)");
  $st->bind_param('sssssss', $email, $email, $nombre, $apellido, $rol, $perfil['id'], $perfil['avatar']);
  $st->execute();
  $usuario = buscarUsuario($db, 'id_user', (string)$db->insert_id);
}

$sesion = iniciarSesion($db, $usuario);
if (!$sesion) {
  $volver('cuenta_baja');
}
header('Location: /' . destinoSegunRol($sesion['rol']));
