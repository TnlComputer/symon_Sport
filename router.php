<?php
// Enrutador SOLO para desarrollo local:  php -S localhost:8000 router.php
// (en el hosting hace lo mismo el .htaccess: ver deploy/htaccess.txt)
$ruta = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// carpetas internas: no se sirven
if (preg_match('#^/(includes|sql|deploy|php/connections)(/|$)#', $ruta) || preg_match('#/\.#', $ruta) || $ruta === '/router.php') {
  http_response_code(404);
  exit('No encontrado');
}

// fotos subidas: solo imágenes webp (igual que uploads/.htaccess en el hosting)
if (str_starts_with($ruta, '/uploads/') && !preg_match('#^/uploads/[a-f0-9]{32}\.webp$#', $ruta)) {
  http_response_code(404);
  exit('No encontrado');
}

// las direcciones viejas .html redirigen a la página nueva
if (preg_match('#^/(.+)\.html$#', $ruta, $m) && is_file(__DIR__ . '/' . $m[1] . '.php')) {
  $qs = $_SERVER['QUERY_STRING'] ?? '';
  header('Location: /' . $m[1] . '.php' . ($qs !== '' ? '?' . $qs : ''), true, 301);
  exit;
}

if ($ruta === '/') {
  require __DIR__ . '/index.php';
  return true;
}

return false; // archivo estático o PHP existente: lo sirve el servidor
