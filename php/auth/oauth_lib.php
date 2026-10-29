<?php
// Datos de cada proveedor de login social (OAuth 2.0 / OpenID Connect)
require_once "lib.php";

function proveedoresOAuth()
{
  return [
    'google' => [
      'auth' => 'https://accounts.google.com/o/oauth2/v2/auth',
      'token' => 'https://oauth2.googleapis.com/token',
      'perfil' => 'https://openidconnect.googleapis.com/v1/userinfo',
      'scope' => 'openid email profile',
    ],
    'microsoft' => [
      'auth' => 'https://login.microsoftonline.com/common/oauth2/v2.0/authorize',
      'token' => 'https://login.microsoftonline.com/common/oauth2/v2.0/token',
      'perfil' => 'https://graph.microsoft.com/oidc/userinfo',
      'scope' => 'openid email profile',
    ],
    'facebook' => [
      'auth' => 'https://www.facebook.com/v19.0/dialog/oauth',
      'token' => 'https://graph.facebook.com/v19.0/oauth/access_token',
      'perfil' => 'https://graph.facebook.com/v19.0/me?fields=id,first_name,last_name,email,picture.type(large)',
      'scope' => 'email,public_profile',
    ],
  ];
}

function urlCallback()
{
  return urlSitio() . '/php/auth/callback.php';
}

function pedirHTTP($url, $post = null, $token = null)
{
  $ch = curl_init($url);
  $opciones = [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_HTTPHEADER => ['Accept: application/json']];
  if ($token) $opciones[CURLOPT_HTTPHEADER][] = 'Authorization: Bearer ' . $token;
  if ($post !== null) {
    $opciones[CURLOPT_POST] = true;
    $opciones[CURLOPT_POSTFIELDS] = http_build_query($post);
  }
  curl_setopt_array($ch, $opciones);
  $respuesta = curl_exec($ch);
  curl_close($ch);
  return $respuesta ? json_decode($respuesta, true) : null;
}

// Normaliza el perfil de cada proveedor a: id, email, email_verificado, nombre, apellido, avatar
function perfilNormalizado($proveedor, $p, $idToken = null)
{
  switch ($proveedor) {
    case 'google':
      return [
        'id' => $p['sub'] ?? null,
        'email' => $p['email'] ?? null,
        'email_verificado' => !empty($p['email_verified']),
        'nombre' => $p['given_name'] ?? ($p['name'] ?? ''),
        'apellido' => $p['family_name'] ?? '',
        'avatar' => $p['picture'] ?? null,
      ];
    case 'microsoft':
      // solo confío en el mail de cuentas personales (Hotmail/Outlook/Live): tenant de consumidores
      $tid = null;
      if ($idToken && count(explode('.', $idToken)) === 3) {
        $claims = json_decode(base64_decode(strtr(explode('.', $idToken)[1], '-_', '+/')), true);
        $tid = $claims['tid'] ?? null;
      }
      return [
        'id' => $p['sub'] ?? null,
        'email' => $p['email'] ?? null,
        'email_verificado' => $tid === '9188040d-6c67-4c5b-b112-36a304b66dad',
        'nombre' => $p['given_name'] ?? ($p['name'] ?? ''),
        'apellido' => $p['family_name'] ?? '',
        'avatar' => null,
      ];
    case 'facebook':
      return [
        'id' => $p['id'] ?? null,
        'email' => $p['email'] ?? null,
        'email_verificado' => !empty($p['email']), // Facebook solo entrega mails confirmados
        'nombre' => $p['first_name'] ?? '',
        'apellido' => $p['last_name'] ?? '',
        'avatar' => $p['picture']['data']['url'] ?? null,
      ];
  }
  return null;
}
