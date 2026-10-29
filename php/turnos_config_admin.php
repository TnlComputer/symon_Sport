<?php
// Configuración de turnos (solo administrador)
// GET                      -> config, servicios, horarios y bloqueos
// POST accion=...          -> guardar cambios
require_once "turnos_lib.php";
require_once "auth/lib.php";

if (!esAdmin()) { // confirma en la base que sigue activo y siendo admin
  responder(['error' => 'No autorizado'], 403);
}

$db = $conexionApi;

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $config = $db->query("SELECT clave, valor, descripcion FROM turnos_config ORDER BY clave")->fetch_all(MYSQLI_ASSOC);
  $servicios = $db->query("SELECT id_servicio, nombre, descripcion, duracion_min, margen_min, activo, orden FROM turnos_servicios ORDER BY orden, nombre")->fetch_all(MYSQLI_ASSOC);
  $horarios = $db->query("SELECT id_horario, dia_semana, TIME_FORMAT(hora_desde, '%H:%i') AS hora_desde, TIME_FORMAT(hora_hasta, '%H:%i') AS hora_hasta FROM turnos_horarios WHERE baja IS NULL ORDER BY dia_semana, hora_desde")->fetch_all(MYSQLI_ASSOC);
  $bloqueos = $db->query("SELECT id_bloqueo, fecha, TIME_FORMAT(hora_desde, '%H:%i') AS hora_desde, TIME_FORMAT(hora_hasta, '%H:%i') AS hora_hasta, motivo FROM turnos_bloqueos WHERE fecha >= CURDATE() AND baja IS NULL ORDER BY fecha, hora_desde")->fetch_all(MYSQLI_ASSOC);
  responder(compact('config', 'servicios', 'horarios', 'bloqueos'));
}

$accion = $_POST['accion'] ?? '';
$hora = fn($k) => preg_match('/^\d{2}:\d{2}$/', $_POST[$k] ?? '') ? $_POST[$k] : null;

switch ($accion) {
  case 'config':
    // solo claves conocidas, con sus límites
    $limites = [
      'capacidad' => [1, 20],
      'intervalo_min' => [10, 120],
      'dias_max' => [1, 30],
      'anticipacion_min_horas' => [0, 72],
    ];
    // primero valido todo, después guardo (para no dejar cambios a medias)
    $guardar = [];
    foreach ($limites as $clave => [$min, $max]) {
      if (!isset($_POST[$clave])) continue;
      $valor = (int)$_POST[$clave];
      if ($valor < $min || $valor > $max) {
        responder(['error' => "El valor de \"$clave\" debe estar entre $min y $max."], 422);
      }
      $guardar[$clave] = (string)$valor;
    }
    if (isset($_POST['email_aviso'])) {
      $email = trim($_POST['email_aviso']);
      if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        responder(['error' => 'El mail de aviso no es válido.'], 422);
      }
      $guardar['email_aviso'] = $email;
    }
    $st = $db->prepare("INSERT INTO turnos_config (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
    foreach ($guardar as $clave => $valor) {
      $st->bind_param('ss', $clave, $valor);
      $st->execute();
    }
    responder(['ok' => true]);

  case 'servicio':
    $id = (int)($_POST['id_servicio'] ?? 0);
    $nombre = mb_substr(trim($_POST['nombre'] ?? ''), 0, 100);
    $descripcion = mb_substr(trim($_POST['descripcion'] ?? ''), 0, 255);
    // la pantalla manda horas; se guardan minutos
    $duracion = (int)round((float)str_replace(',', '.', $_POST['horas'] ?? '0') * 60);
    $margen = (int)round((float)str_replace(',', '.', $_POST['margen_horas'] ?? '0') * 60);
    $activo = !empty($_POST['activo']) ? 1 : 0;
    $orden = (int)($_POST['orden'] ?? 0);
    if ($nombre === '' || $duracion < 10 || $duracion > 4800 || $margen < 0 || $margen > 4800) {
      responder(['error' => 'Completá el nombre y las horas de trabajo (hasta 80 h).'], 422);
    }
    if ($id) {
      $st = $db->prepare("UPDATE turnos_servicios SET nombre = ?, descripcion = NULLIF(?, ''), duracion_min = ?, margen_min = ?, activo = ?, orden = ? WHERE id_servicio = ?");
      $st->bind_param('ssiiiii', $nombre, $descripcion, $duracion, $margen, $activo, $orden, $id);
    } else {
      $st = $db->prepare("INSERT INTO turnos_servicios (nombre, descripcion, duracion_min, margen_min, activo, orden) VALUES (?, NULLIF(?, ''), ?, ?, ?, ?)");
      $st->bind_param('ssiiii', $nombre, $descripcion, $duracion, $margen, $activo, $orden);
    }
    $st->execute();
    responder(['ok' => true]);

  case 'horario_agregar':
    $dia = (int)($_POST['dia_semana'] ?? 0);
    $desde = $hora('hora_desde');
    $hasta = $hora('hora_hasta');
    if ($dia < 1 || $dia > 7 || !$desde || !$hasta || $desde >= $hasta) {
      responder(['error' => 'Elegí el día y un horario válido (desde menor que hasta).'], 422);
    }
    $st = $db->prepare("SELECT COUNT(*) FROM turnos_horarios WHERE dia_semana = ? AND baja IS NULL AND hora_desde < ? AND hora_hasta > ?");
    $st->bind_param('iss', $dia, $hasta, $desde);
    $st->execute();
    if ($st->get_result()->fetch_row()[0] > 0) {
      responder(['error' => 'Esa franja se superpone con otra del mismo día.'], 422);
    }
    $st = $db->prepare("INSERT INTO turnos_horarios (dia_semana, hora_desde, hora_hasta) VALUES (?, ?, ?)");
    $st->bind_param('iss', $dia, $desde, $hasta);
    $st->execute();
    responder(['ok' => true]);

  case 'horario_borrar':
    $id = (int)($_POST['id'] ?? 0);
    $st = $db->prepare("UPDATE turnos_horarios SET baja = NOW() WHERE id_horario = ?");
    $st->bind_param('i', $id);
    $st->execute();
    responder(['ok' => true]);

  case 'bloqueo_agregar':
    $fecha = $_POST['fecha'] ?? '';
    $desde = $hora('hora_desde');
    $hasta = $hora('hora_hasta');
    $motivo = mb_substr(trim($_POST['motivo'] ?? ''), 0, 100);
    if (!fechaValida($fecha)) {
      responder(['error' => 'Elegí una fecha.'], 422);
    }
    if (($desde xor $hasta) || ($desde && $desde >= $hasta)) {
      responder(['error' => 'Para cerrar una franja completá desde y hasta; para todo el día dejalos vacíos.'], 422);
    }
    $st = $db->prepare("INSERT INTO turnos_bloqueos (fecha, hora_desde, hora_hasta, motivo) VALUES (?, ?, ?, NULLIF(?, ''))");
    $st->bind_param('ssss', $fecha, $desde, $hasta, $motivo);
    $st->execute();
    responder(['ok' => true]);

  case 'feriados_importar':
    // feriados nacionales oficiales desde la API pública de ArgentinaDatos
    $anio = (int)($_POST['anio'] ?? 0);
    $conPuentes = !empty($_POST['puentes']);
    if ($anio < (int)date('Y') || $anio > (int)date('Y') + 1) {
      responder(['error' => 'Elegí el año actual o el siguiente.'], 422);
    }
    $url = "https://api.argentinadatos.com/v1/feriados/$anio";
    $json = false;
    if (function_exists('curl_init')) {
      $ch = curl_init($url);
      curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15, CURLOPT_FOLLOWLOCATION => true]);
      $json = curl_exec($ch);
      $ok = curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
      curl_close($ch);
      if (!$ok) $json = false;
    } else {
      $json = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 15]]));
    }
    $feriados = $json ? json_decode($json, true) : null;
    if (!is_array($feriados)) {
      responder(['error' => 'No se pudo consultar el listado de feriados. Probá más tarde o cargalos a mano.'], 502);
    }
    if (!$feriados) {
      responder(['error' => "Todavía no están publicados los feriados de $anio."], 404);
    }

    $existe = $db->prepare("SELECT COUNT(*) FROM turnos_bloqueos WHERE fecha = ? AND hora_desde IS NULL /* incluye dados de baja: no se reimportan */");
    $alta = $db->prepare("INSERT INTO turnos_bloqueos (fecha, motivo) VALUES (?, ?)");
    $agregados = 0;
    foreach ($feriados as $f) {
      $fecha = $f['fecha'] ?? '';
      if (!fechaValida($fecha) || $fecha < date('Y-m-d')) continue;
      if (($f['tipo'] ?? '') === 'puente' && !$conPuentes) continue;
      $existe->bind_param('s', $fecha);
      $existe->execute();
      if ($existe->get_result()->fetch_row()[0] > 0) continue;
      $motivo = mb_substr('Feriado: ' . ($f['nombre'] ?? ''), 0, 100);
      $alta->bind_param('ss', $fecha, $motivo);
      $alta->execute();
      $agregados++;
    }
    responder(['ok' => true, 'agregados' => $agregados]);

  case 'bloqueo_borrar':
    $id = (int)($_POST['id'] ?? 0);
    $st = $db->prepare("UPDATE turnos_bloqueos SET baja = NOW() WHERE id_bloqueo = ?");
    $st->bind_param('i', $id);
    $st->execute();
    responder(['ok' => true]);
}

responder(['error' => 'Acción desconocida'], 400);
