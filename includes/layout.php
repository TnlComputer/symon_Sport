<?php
// Estructura común de todas las páginas: <head>, encabezado con menú y pie.
require_once __DIR__ . '/bootstrap.php';

const WHATSAPP_URL = 'https://wa.me/541167993863';
const FACEBOOK_URL = 'https://www.facebook.com/Symon-Sport-308309696321701';

// Ítems del menú: [archivo, texto, ícono, quién lo ve]
// quién: todos | publico (visitantes y clientes, no admin) | cliente (con sesión, no admin) | admin | invitado (sin sesión)
const MENU = [
  ['index.php', 'Inicio', 'icons8-casa-24.png', 'publico'],
  ['index.php#servicios', 'Servicios', 'reparaciones.png', 'publico'],
  ['mi-garage.php', 'Mi garage', 'reparaciones.png', 'cliente'],
  ['turnos-admin.php', 'Agenda', 'calendario.svg', 'admin'],
  ['presupuestos.php', 'Presupuestos', 'icons8-costo-24.png', 'admin'],
  ['clientes.php', 'Clientes', 'icons8-contrato-de-trabajo-24.png', 'admin'],
  ['reparaciones.php', 'Reparaciones', 'reparaciones.png', 'admin'],
  ['repuestos.php', 'Repuestos', 'repuestos.svg', 'admin'],
  ['usuarios.php', 'Usuarios', 'icons8-usuario-de-genero-neutro-24.png', 'admin'],
  ['turnos.php', 'Turnos', 'calendario.svg', 'publico'],
  ['contacto.php', 'Contacto', 'icons8-mensaje-relleno-24.png', 'publico'],
];

// Menú de la cuenta (desplegable con el nombre en escritorio; dentro de la hamburguesa en celular)
const MENU_CUENTA = [
  'admin' => [
    ['index.php', 'Ver el sitio', 'icons8-casa-24.png'],
    ['turnos-config.php', 'Configuración de turnos', 'calendario.svg'],
    ['contacto.php', 'Contacto', 'icons8-mensaje-relleno-24.png'],
  ],
  'usuario' => [
    ['mi-garage.php', 'Mi garage y perfil', 'icons8-usuario-de-genero-neutro-24.png'],
    ['turnos.php', 'Mis turnos', 'calendario.svg'],
  ],
];

// Páginas para usuarios con sesión iniciada (clientes o admin)
function requiereSesion()
{
  if (!usuarioActual()) {
    $volver = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: 'index.php');
    header('Location: login.php?volver=' . rawurlencode($volver));
    exit;
  }
}

// Páginas solo para administradores: si no es admin, al login (ni se envía el contenido)
function requiereAdmin()
{
  global $conexionApi; // la librería crea la conexión: tiene que quedar global aunque se incluya desde esta función
  require_once __DIR__ . '/../php/auth/lib.php'; // esAdmin() confirma en la base que sigue activo y siendo admin
  if (!esAdmin()) {
    $volver = basename(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: 'index.php');
    header('Location: ' . (usuarioActual() ? 'index.php' : 'login.php?volver=' . rawurlencode($volver)));
    exit;
  }
}

function asset($ruta)
{
  return $ruta . '?v=' . VERSION_ASSETS;
}

/**
 * $o: titulo, descripcion, pagina (archivo actual, para marcar el menú), noindex,
 *     fuenteTitulos (Barlow Condensed), head (HTML extra en <head>), canonical
 */
function paginaInicio(array $o)
{
  $titulo = $o['titulo'] ?? 'Symon Sport';
  $descripcion = $o['descripcion'] ?? 'Taller mecánico integral en San Martín: diagnóstico, mecánica, inyección, frenos, suspensión, tren delantero y aire acondicionado.';
  $pagina = $o['pagina'] ?? '';
  $canonical = SITIO_URL . '/' . ($o['canonical'] ?? ($pagina === 'index.php' ? '' : $pagina));
  $rol = rolActual();
  ?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($titulo) ?></title>
  <meta name="description" content="<?= e($descripcion) ?>">
  <meta name="csrf-token" content="<?= e(csrfToken()) ?>">
<?php if (!empty($o['noindex'])): ?>
  <meta name="robots" content="noindex">
<?php else: ?>
  <link rel="canonical" href="<?= e($canonical) ?>">
<?php endif; ?>
  <meta property="og:title" content="<?= e($titulo) ?>">
  <meta property="og:site_name" content="Symon Sport">
  <meta property="og:url" content="<?= e($canonical) ?>">
  <meta property="og:description" content="<?= e($descripcion) ?>">
  <meta property="og:type" content="website">
  <meta property="og:image" content="<?= SITIO_URL ?>/assets/img/logo_symon_sport-512.png">
  <meta name="twitter:card" content="summary">
  <meta name="theme-color" content="#0c0d0f">
  <link rel="icon" href="assets/img/favicon/favicon.ico" sizes="any">
  <link rel="icon" type="image/png" sizes="32x32" href="assets/img/favicon/favicon-32x32.png">
  <link rel="icon" type="image/png" sizes="192x192" href="assets/img/favicon/android-icon-192x192.png">
  <link rel="apple-touch-icon" sizes="180x180" href="assets/img/favicon/apple-icon-180x180.png">
  <link rel="manifest" href="assets/img/favicon/manifest.json">
<?php if (!empty($o['fuenteTitulos'])): ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&display=swap">
<?php endif; ?>
  <link rel="stylesheet" href="<?= asset('css/style.css') ?>">
<?= $o['head'] ?? '' ?>
</head>

<body>
  <a class="skip-link" href="#contenido">Saltar al contenido</a>
  <header class="main-nav">
    <div class="main-nav__inner">
      <a href="index.php" class="brand"><img src="assets/img/logo_symon_sport-200x127.webp" alt="Symon Sport - Inicio"
          width="69" height="44"></a>

      <button class="menu-toggle" id="toggle-menu" type="button" aria-label="Abrir menú" aria-expanded="false"
        aria-controls="main-menu">
        <span class="menu-toggle__bar"></span>
        <span class="menu-toggle__bar"></span>
        <span class="menu-toggle__bar"></span>
      </button>

      <nav aria-label="Principal">
        <ul class="main-menu" id="main-menu">
<?php foreach (MENU as [$archivo, $texto, $icono, $quien]):
    $visible = $quien === 'todos' || ($quien === 'admin' && $rol === 'admin')
      || ($quien === 'publico' && $rol !== 'admin') || ($quien === 'cliente' && $rol && $rol !== 'admin')
      || ($quien === 'invitado' && !$rol);
    if (!$visible) continue; ?>
          <li class="main-menu__item">
            <a href="<?= $archivo ?>" class="main-menu__link"<?= $archivo === $pagina ? ' aria-current="page"' : '' ?>><img
                src="assets/img/menu/<?= e($icono) ?>" alt="" width="24" height="24"><span><?= e($texto) ?></span></a>
          </li>
<?php endforeach; ?>
<?php if ($rol): // en celular la cuenta va al final de la lista ?>
<?php foreach (MENU_CUENTA[$rol] ?? [] as [$archivo, $texto, $icono]): ?>
          <li class="main-menu__item main-menu__item--movil">
            <a href="<?= $archivo ?>" class="main-menu__link"<?= $archivo === $pagina ? ' aria-current="page"' : '' ?>><img
                src="assets/img/menu/<?= e($icono) ?>" alt="" width="24" height="24"><span><?= e($texto) ?></span></a>
          </li>
<?php endforeach; ?>
          <li class="main-menu__item main-menu__item--movil">
            <a href="#" class="main-menu__link" data-salir><img src="assets/img/menu/icons8-salida-24.png" alt=""
                width="24" height="24"><span>Salir</span></a>
          </li>
<?php else: ?>
          <li class="main-menu__item main-menu__item--movil">
            <a href="login.php" class="main-menu__link"<?= $pagina === 'login.php' ? ' aria-current="page"' : '' ?>><img
                src="assets/img/menu/icons8-usuario-de-genero-neutro-24.png" alt="" width="24" height="24"><span>Ingresar</span></a>
          </li>
<?php endif; ?>
        </ul>
      </nav>

<?php if ($rol !== 'admin'): ?>
      <ul class="main-menu-social">
        <li class="social">
          <a href="<?= FACEBOOK_URL ?>" target="_blank" rel="noopener noreferrer" class="social__link"><img
              src="assets/img/menu/icons8-facebook-24.png" alt="Facebook" title="Facebook" width="24" height="24"></a>
          <a href="<?= WHATSAPP_URL ?>" target="_blank" rel="noopener noreferrer" class="social__link"><img
              src="assets/img/menu/icons8-whatsapp-24.png" alt="WhatsApp" title="WhatsApp" width="24" height="24"></a>
        </li>
      </ul>
<?php endif; ?>

<?php if ($rol):
    $u = usuarioActual();
    $nombre = trim((string)($u['nombre'] ?? '')) ?: (string)($u['email'] ?? 'Mi cuenta');
    $primerNombre = explode(' ', $nombre)[0];
    $iniciales = mb_strtoupper(implode('', array_map(fn($p) => mb_substr($p, 0, 1), array_slice(preg_split('/\s+/', $nombre), 0, 2)))); ?>
      <div class="cuenta">
        <button type="button" class="cuenta__boton" id="cuenta-boton" aria-expanded="false" aria-controls="cuenta-menu"
          aria-haspopup="true"><span class="cuenta__avatar" aria-hidden="true"><?php if (!empty($u['avatar'])): ?><img
              src="<?= e($u['avatar']) ?>" alt="" width="32" height="32" referrerpolicy="no-referrer"><?php else: ?><?= e($iniciales) ?><?php endif; ?></span><span
            class="cuenta__nombre"><?= e($primerNombre) ?></span></button>
        <div class="cuenta__menu" id="cuenta-menu" hidden>
          <p class="cuenta__quien"><strong><?= e($nombre) ?></strong><small><?= $rol === 'admin' ? 'Administrador' : e($u['email'] ?? '') ?></small></p>
<?php foreach (MENU_CUENTA[$rol] ?? [] as [$archivo, $texto]): ?>
          <a href="<?= $archivo ?>" class="cuenta__item"<?= $archivo === $pagina ? ' aria-current="page"' : '' ?>><?= e($texto) ?></a>
<?php endforeach; ?>
          <a href="#" class="cuenta__item cuenta__item--salir" data-salir>Salir</a>
        </div>
      </div>
<?php else: ?>
      <a class="nav-ingresar" href="login.php"<?= $pagina === 'login.php' ? ' aria-current="page"' : '' ?>>Ingresar</a>
<?php endif; ?>

<?php if ($rol === 'admin'): ?>
      <a class="nav-cta nav-cta--admin" href="presupuestos.php?nuevo=1" aria-label="Nuevo presupuesto"><span>+<span class="nav-cta__extra"> Presupuesto</span></span></a>
<?php else: ?>
      <a class="nav-cta" href="turnos.php"><img src="assets/img/menu/calendario.svg" alt="" width="20"
          height="20"><span>Reservar<span class="nav-cta__extra"> turno</span></span></a>
<?php endif; ?>
    </div>
  </header>
<?php
}

/**
 * $o: scripts (nombres en js/ sin .js), whatsapp (botón flotante)
 */
function paginaFin(array $o = [])
{
  ?>
<?php if (!empty($o['whatsapp'])): ?>
  <a class="whatsapp-float" href="<?= WHATSAPP_URL ?>" target="_blank" rel="noopener noreferrer"
    aria-label="Escribinos por WhatsApp"><img src="assets/img/menu/icons8-whatsapp-48.png" alt="" width="30" height="30"></a>
<?php endif; ?>

  <footer class="footer">
    <div class="tres_columnas">
      <div class="columna-1">
        <a class="afip" href="http://qr.afip.gob.ar/?qr=FQNxsrMFMu2Jj0XuTMYpsQ,," target="_F960AFIPInfo"
          rel="noopener"><img src="https://www.afip.gob.ar/images/f960/DATAWEB.jpg" alt="Data fiscal AFIP"
            class="logo-afip" width="120" height="112" loading="lazy" decoding="async"></a>
      </div>

      <div class="columna-2">
        <ul class="social__menu">
          <li class="social__items">Seguinos
            <a href="<?= FACEBOOK_URL ?>" target="_blank" rel="noopener noreferrer" class="social__link"><img
                src="assets/img/menu/icons8-facebook-24.png" alt="Facebook" title="Facebook" width="24" height="24"
                loading="lazy"></a>
          </li>
          <li class="social__items">Escribinos
            <a href="<?= WHATSAPP_URL ?>" target="_blank" rel="noopener noreferrer" class="social__link"><img
                src="assets/img/menu/icons8-whatsapp-24.png" alt="WhatsApp" title="WhatsApp" width="24" height="24"
                loading="lazy"></a>
          </li>
        </ul>
      </div>

      <div class="columna-3">
        <p>
          <a href="https://maps.google.com/?q=Primera+Junta+2261,+San+Mart%C3%ADn,+Buenos+Aires" target="_blank"
            rel="noopener noreferrer">Primera Junta 2261 (esq. San Vicente)<br>San Martín, Provincia de Buenos Aires</a><br>
          Tel: <a href="tel:+5491167993863">011 15-6799-3863</a>
        </p>
      </div>
    </div>
    <div class="design">
      <p class="origen">
        Diseñado por <a href="https://tnlcomputer.com.ar" target="_blank" rel="noopener">@TNLCOMPUTER</a>
      </p>
    </div>
  </footer>

  <script src="<?= asset('js/scripts.js') ?>" defer></script>
<?php foreach ($o['scripts'] ?? [] as $s): ?>
  <script src="<?= asset('js/' . $s . '.js') ?>" defer></script>
<?php endforeach; ?>
</body>

</html>
<?php
}
