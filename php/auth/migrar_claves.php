<?php
// Encripta TODAS las contraseñas viejas (texto plano) y borra la copia legible.
// Se ejecuta UNA vez, solo desde la consola del servidor (no desde el navegador):
//   php php/auth/migrar_claves.php
// En local:  php php/auth/migrar_claves.php --local
if (PHP_SAPI !== 'cli') {
  http_response_code(404);
  exit;
}

// conexion.php elige la base local según el host
if (in_array('--local', $argv, true)) {
  $_SERVER['HTTP_HOST'] = 'localhost';
}
require_once __DIR__ . "/../connections/conexion.php";
$conexionApi->set_charset('utf8mb4');

$res = $conexionApi->query("SELECT id_user, clave, clave_hash FROM usuarios WHERE clave IS NOT NULL AND clave <> ''");
$encriptadas = 0;
$limpiadas = 0;
$st = $conexionApi->prepare("UPDATE usuarios SET clave_hash = ?, clave = NULL WHERE id_user = ?");
$limpiar = $conexionApi->prepare("UPDATE usuarios SET clave = NULL WHERE id_user = ?");

while ($u = $res->fetch_assoc()) {
  $id = (int)$u['id_user'];
  if (!empty($u['clave_hash'])) {
    // ya tenía la clave encriptada: solo borro la copia en texto plano
    $limpiar->bind_param('i', $id);
    $limpiar->execute();
    $limpiadas++;
    continue;
  }
  $hash = password_hash($u['clave'], PASSWORD_DEFAULT);
  $st->bind_param('si', $hash, $id);
  $st->execute();
  $encriptadas++;
}

$quedan = $conexionApi->query("SELECT COUNT(*) FROM usuarios WHERE clave IS NOT NULL AND clave <> ''")->fetch_row()[0];
echo "Claves encriptadas: $encriptadas\n";
echo "Copias en texto plano borradas: $limpiadas\n";
echo "Claves que quedan en texto plano: $quedan\n";
