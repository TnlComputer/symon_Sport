<?php
// GET p=google|microsoft|facebook -> redirige al proveedor para iniciar sesión
require_once "oauth_lib.php";

$proveedor = $_GET['p'] ?? '';
$datos = proveedoresOAuth()[$proveedor] ?? null;
if (!$datos || !proveedorActivo($proveedor)) {
  header('Location: /login.php?aviso=proveedor');
  exit;
}

// "state" evita que otro sitio inicie el login por el usuario (CSRF)
$state = bin2hex(random_bytes(16));
$_SESSION['oauth'] = ['state' => $state, 'proveedor' => $proveedor];

$params = [
  'client_id' => configOAuth()[$proveedor]['client_id'],
  'redirect_uri' => urlCallback(),
  'response_type' => 'code',
  'scope' => $datos['scope'],
  'state' => $state,
];
if ($proveedor === 'google') $params['prompt'] = 'select_account';
if ($proveedor === 'microsoft') $params['prompt'] = 'select_account';

header('Location: ' . $datos['auth'] . '?' . http_build_query($params));
