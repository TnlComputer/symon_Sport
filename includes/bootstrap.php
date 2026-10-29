<?php
// Núcleo común de todo el sitio: sesión segura, cabeceras de seguridad y protección CSRF.
// Lo incluyen todas las páginas y todos los PHP de la API.
if (defined('SSP_BOOT')) return;
define('SSP_BOOT', true);

const VERSION_ASSETS = '20261108';
const SITIO_URL = 'https://symonsport.com.ar';

date_default_timezone_set('America/Argentina/Buenos_Aires');

function esHttps()
{
  return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
    || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
}

/* ---------- Sesión ---------- */
if (session_status() === PHP_SESSION_NONE) {
  ini_set('session.use_strict_mode', '1');   // no acepta ids de sesión inventados
  ini_set('session.use_only_cookies', '1');
  ini_set('session.cookie_httponly', '1');
  session_name('ssp_sesion');
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,                       // JavaScript no puede leer la cookie
    'samesite' => 'Lax',                      // no viaja en envíos desde otros sitios
    'secure' => esHttps(),
  ]);
  session_start();
}

/* ---------- Vencimiento por inactividad ---------- */
// Si nadie usa la sesión durante este tiempo, se cierra (ej. la PC del taller quedó abierta)
const SESION_INACTIVA_MIN = 120;
if (!empty($_SESSION['datos'])) {
  if (isset($_SESSION['ultimo_uso']) && time() - $_SESSION['ultimo_uso'] > SESION_INACTIVA_MIN * 60) {
    $_SESSION = [];
    session_regenerate_id(true);
  }
  $_SESSION['ultimo_uso'] = time();
}

/* ---------- Cabeceras de seguridad ---------- */
if (!headers_sent()) {
  $csp = implode('; ', [
    "default-src 'self'",
    "script-src 'self'",
    "style-src 'self' https://fonts.googleapis.com",
    "font-src 'self' https://fonts.gstatic.com",
    "img-src 'self' data: https://www.afip.gob.ar https://*.googleusercontent.com https://*.fbcdn.net",
    "frame-src https://www.google.com https://maps.google.com",
    "connect-src 'self'",
    "form-action 'self'",
    "base-uri 'self'",
    "object-src 'none'",
    "frame-ancestors 'self'",
  ]);
  header("Content-Security-Policy: $csp");
  header('X-Content-Type-Options: nosniff');
  header('X-Frame-Options: SAMEORIGIN');
  header('Referrer-Policy: strict-origin-when-cross-origin');
  header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
  header('Cross-Origin-Opener-Policy: same-origin');
  if (esHttps()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
  }
}

/* ---------- CSRF ---------- */
// Cada sesión tiene un token; todo envío (POST) debe traerlo en la cabecera X-CSRF-Token
// (lo agrega solo js/scripts.js) o en el campo "csrf". Así otro sitio no puede hacer
// acciones en nombre de un usuario logueado.
function csrfToken()
{
  if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(32));
  }
  return $_SESSION['csrf'];
}

function verificarCsrf()
{
  $enviado = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? ($_POST['csrf'] ?? '');
  if (!is_string($enviado) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $enviado)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'La sesión venció. Recargá la página e intentá de nuevo.', 'csrf' => true], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && PHP_SAPI !== 'cli') {
  verificarCsrf();
}

/* ---------- Usuario actual ---------- */
function usuarioActual()
{
  return !empty($_SESSION['datos']['id_user']) ? $_SESSION['datos'] : null;
}

function rolActual()
{
  return usuarioActual()['rol'] ?? null;
}

// HTML seguro
function e($texto)
{
  return htmlspecialchars((string)$texto, ENT_QUOTES, 'UTF-8');
}
