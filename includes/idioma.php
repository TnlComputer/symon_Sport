<?php
// Idiomas del sitio público y de Mi garage (las pantallas del taller quedan en castellano).
// El texto en castellano es la clave: t('Reservar turno') devuelve la traducción o, si falta, el mismo texto.
// Diccionarios: includes/idiomas/<idioma>/*.php, cada uno devuelve ['texto en castellano' => 'traducción'].
// Los textos con datos llevan {0}, {1}...: t('Hola, {0}', $nombre)

const IDIOMAS = ['es' => 'Español', 'pt' => 'Português', 'en' => 'English'];
const IDIOMA_LOCALE = ['es' => 'es-AR', 'pt' => 'pt-BR', 'en' => 'en-US'];

function idiomaActual()
{
  static $idioma = null;
  if ($idioma) return $idioma;

  // ?idioma=pt lo elige y queda guardado un año
  $pedido = $_GET['idioma'] ?? null;
  if (is_string($pedido) && isset(IDIOMAS[$pedido])) {
    if (!headers_sent()) {
      setcookie('ssp_idioma', $pedido, ['expires' => time() + 365 * 86400, 'path' => '/', 'samesite' => 'Lax', 'secure' => esHttps()]);
    }
    return $idioma = $pedido;
  }
  $guardado = $_COOKIE['ssp_idioma'] ?? null;
  if (is_string($guardado) && isset(IDIOMAS[$guardado])) return $idioma = $guardado;

  // primera visita: el idioma del navegador, si es uno de los nuestros
  foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '') as $parte) {
    $codigo = strtolower(substr(trim($parte), 0, 2));
    if (isset(IDIOMAS[$codigo])) return $idioma = $codigo;
  }
  return $idioma = 'es';
}

function diccionario()
{
  static $dic = null;
  if ($dic !== null) return $dic;
  $dic = [];
  if (idiomaActual() !== 'es') {
    foreach (glob(__DIR__ . '/idiomas/' . idiomaActual() . '/*.php') ?: [] as $archivo) {
      $dic += require $archivo;
    }
  }
  return $dic;
}

function t($texto, ...$datos)
{
  $traducido = diccionario()[$texto] ?? $texto;
  return $datos ? preg_replace_callback('/\{(\d+)\}/', fn($m) => (string)($datos[(int)$m[1]] ?? ''), $traducido) : $traducido;
}

// La misma página en otro idioma (conserva la dirección y los parámetros)
function urlIdioma($codigo)
{
  $partes = parse_url($_SERVER['REQUEST_URI'] ?? '/');
  parse_str($partes['query'] ?? '', $params);
  $params['idioma'] = $codigo;
  return ($partes['path'] ?? '/') . '?' . http_build_query($params);
}

// Tema elegido por el usuario (lo guarda js/scripts.js en una cookie): oscuro por defecto
function temaActual()
{
  return ($_COOKIE['ssp_tema'] ?? '') === 'claro' ? 'claro' : 'oscuro';
}
