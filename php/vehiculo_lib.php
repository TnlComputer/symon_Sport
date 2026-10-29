<?php
// Ficha técnica y fotos de un vehículo (por patente). La usan Mi garage (cliente) y Clientes (admin).

const DIR_FOTOS = __DIR__ . '/../uploads/';
const MAX_FOTOS = 10; // por auto y por quien las sube (el cliente o el taller)

/**
 * Campos de fichas_vehiculo: [grupo, etiqueta, tipo, opciones, lo edita el cliente]
 * tipos: texto:max | entero:min:max | decimal:min:max | fecha | si_no | opcion (con opciones [valor => texto])
 * Lo que no edita el cliente lo completa el taller; el cliente lo ve igual.
 */
function camposFicha()
{
  $anioMax = (int)date('Y') + 1;
  return [
    'tipo' => ['Identificación', 'Tipo', 'opcion', ['auto' => 'Auto', 'camioneta' => 'Camioneta', 'suv' => 'SUV', 'utilitario' => 'Utilitario', 'pickup' => 'Pick-up', 'moto' => 'Moto', 'camion' => 'Camión', 'otro' => 'Otro'], true],
    'marca' => ['Identificación', 'Marca', 'texto:60', null, true],
    'modelo' => ['Identificación', 'Modelo', 'texto:80', null, true],
    'version' => ['Identificación', 'Versión', 'texto:80', null, true],
    'anio' => ['Identificación', 'Año', "entero:1950:$anioMax", null, true],
    'color' => ['Identificación', 'Color', 'texto:40', null, true],
    'vin' => ['Identificación', 'N° de chasis (VIN)', 'texto:20', null, true],
    'numero_motor' => ['Identificación', 'N° de motor', 'texto:30', null, true],

    'combustible' => ['Motor y transmisión', 'Combustible', 'opcion', ['nafta' => 'Nafta', 'diesel' => 'Diésel', 'gnc' => 'GNC', 'nafta_gnc' => 'Nafta + GNC', 'hibrido' => 'Híbrido', 'electrico' => 'Eléctrico'], true],
    'transmision' => ['Motor y transmisión', 'Caja', 'opcion', ['manual' => 'Manual', 'automatica' => 'Automática', 'cvt' => 'CVT', 'doble_embrague' => 'Doble embrague'], true],
    'cilindrada_cc' => ['Motor y transmisión', 'Cilindrada (cc)', 'entero:50:9000', null, false],
    'turbo' => ['Motor y transmisión', 'Turbo', 'si_no', null, false],
    'marchas' => ['Motor y transmisión', 'Marchas', 'entero:1:12', null, false],
    'traccion' => ['Motor y transmisión', 'Tracción', 'opcion', ['delantera' => 'Delantera', 'trasera' => 'Trasera', '4x4' => '4x4', 'awd' => 'Integral (AWD)'], false],
    'distribucion' => ['Motor y transmisión', 'Distribución', 'opcion', ['correa' => 'Correa', 'cadena' => 'Cadena', 'engranajes' => 'Engranajes'], false],

    'aceite_motor_tipo' => ['Lubricentro', 'Aceite de motor', 'opcion', ['mineral' => 'Mineral', 'semisintetico' => 'Semisintético', 'sintetico' => 'Sintético'], false],
    'aceite_motor_viscosidad' => ['Lubricentro', 'Viscosidad', 'texto:15', null, false],
    'aceite_motor_norma' => ['Lubricentro', 'Norma', 'texto:40', null, false],
    'aceite_motor_litros' => ['Lubricentro', 'Litros de aceite', 'decimal:0.5:30', null, false],
    'aceite_motor_marca' => ['Lubricentro', 'Marca de aceite', 'texto:40', null, false],
    'filtro_aceite' => ['Lubricentro', 'Filtro de aceite', 'texto:50', null, false],
    'filtro_aire' => ['Lubricentro', 'Filtro de aire', 'texto:50', null, false],
    'filtro_combustible' => ['Lubricentro', 'Filtro de combustible', 'texto:50', null, false],
    'filtro_habitaculo' => ['Lubricentro', 'Filtro de habitáculo', 'texto:50', null, false],
    'aceite_caja' => ['Lubricentro', 'Aceite de caja', 'texto:60', null, false],
    'refrigerante' => ['Lubricentro', 'Refrigerante', 'texto:60', null, false],
    'liquido_frenos' => ['Lubricentro', 'Líquido de frenos', 'texto:20', null, false],
    'liquido_direccion' => ['Lubricentro', 'Líquido de dirección', 'texto:40', null, false],

    'neumatico_medida_del' => ['Neumáticos', 'Medida delantera', 'texto:25', null, true],
    'neumatico_medida_tras' => ['Neumáticos', 'Medida trasera', 'texto:25', null, true],
    'presion_del_psi' => ['Neumáticos', 'Presión delantera (psi)', 'decimal:10:80', null, true],
    'presion_tras_psi' => ['Neumáticos', 'Presión trasera (psi)', 'decimal:10:80', null, true],
    'presion_cargado_psi' => ['Neumáticos', 'Presión cargado (psi)', 'decimal:10:80', null, false],
    'llantas' => ['Neumáticos', 'Llantas', 'opcion', ['chapa' => 'Chapa', 'aleacion' => 'Aleación'], false],
    'rodado' => ['Neumáticos', 'Rodado', 'entero:10:24', null, false],
    'auxilio' => ['Neumáticos', 'Auxilio', 'opcion', ['igual' => 'Igual a las demás', 'temporal' => 'Temporal', 'kit_reparacion' => 'Kit de reparación', 'run_flat' => 'Run flat', 'no_tiene' => 'No tiene'], false],
    'tuerca_seguridad' => ['Neumáticos', 'Bulón de seguridad', 'si_no', null, false],
    'torque_ruedas_nm' => ['Neumáticos', 'Torque de ruedas (Nm)', 'entero:50:400', null, false],

    'bateria_marca' => ['Batería y frenos', 'Batería', 'texto:40', null, false],
    'bateria_amperes' => ['Batería y frenos', 'Amperes (Ah)', 'entero:20:250', null, false],
    'bateria_fecha' => ['Batería y frenos', 'Batería colocada', 'fecha', null, false],
    'frenos_traseros' => ['Batería y frenos', 'Frenos traseros', 'opcion', ['disco' => 'Disco', 'campana' => 'Campana'], false],
    'abs' => ['Batería y frenos', 'ABS', 'si_no', null, false],

    'vtv_vencimiento' => ['Documentación', 'Vence la VTV', 'fecha', null, true],
    'seguro_compania' => ['Documentación', 'Compañía de seguro', 'texto:60', null, true],
    'seguro_poliza' => ['Documentación', 'Póliza', 'texto:40', null, true],
    'seguro_vencimiento' => ['Documentación', 'Vence el seguro', 'fecha', null, true],
    'gnc_oblea_vencimiento' => ['Documentación', 'Vence la oblea GNC', 'fecha', null, true],
    'gnc_prueba_hidraulica' => ['Documentación', 'Prueba hidráulica GNC', 'fecha', null, true],
    'matafuego_vencimiento' => ['Documentación', 'Vence el matafuego', 'fecha', null, true],

    'observaciones' => ['Observaciones del taller', 'Observaciones', 'texto:2000', null, false],
  ];
}

// Catálogo para armar el formulario en el navegador
function camposFichaPublicos($esTaller)
{
  $salida = [];
  foreach (camposFicha() as $campo => [$grupo, $etiqueta, $tipo, $opciones, $cliente]) {
    $salida[] = ['campo' => $campo, 'grupo' => t($grupo), 'etiqueta' => t($etiqueta), 'tipo' => explode(':', $tipo)[0],
      'max' => explode(':', $tipo)[1] ?? null, 'opciones' => $opciones ? array_map('t', $opciones) : $opciones, 'editable' => $esTaller || $cliente];
  }
  return $salida;
}

function dbq($db, $sql, $tipos = '', ...$params)
{
  $st = $db->prepare($sql);
  if ($tipos !== '') $st->bind_param($tipos, ...$params);
  $st->execute();
  $res = $st->get_result();
  return $res ? $res->fetch_all(MYSQLI_ASSOC) : $st->affected_rows;
}

function leerFicha($db, $patente)
{
  $fila = dbq($db, "SELECT * FROM fichas_vehiculo WHERE patente = ?", 's', $patente)[0] ?? [];
  $ficha = [];
  foreach (array_keys(camposFicha()) as $campo) $ficha[$campo] = $fila[$campo] ?? null;
  $ficha['km_actual'] = $fila['km_actual'] ?? null;
  $ficha['km_fecha'] = $fila['km_fecha'] ?? null;
  return $ficha;
}

/**
 * Valida y guarda los campos de la ficha que vienen en $datos (los que no vienen no se tocan).
 * El cliente solo puede cambiar los suyos. Devuelve null si está todo bien, o el mensaje de error.
 */
function guardarFicha($db, $patente, array $datos, $esTaller)
{
  $valores = [];
  foreach (camposFicha() as $campo => [, $etiqueta, $tipo, $opciones, $cliente]) {
    if (!array_key_exists($campo, $datos) || (!$esTaller && !$cliente)) continue;
    $v = trim((string)$datos[$campo]);
    $p = explode(':', $tipo);
    if ($v === '') {
      $valores[$campo] = null;
      continue;
    }
    switch ($p[0]) {
      case 'texto':
        $v = mb_substr($v, 0, (int)$p[1]);
        if (in_array($campo, ['vin', 'numero_motor', 'neumatico_medida_del', 'neumatico_medida_tras'], true)) $v = mb_strtoupper($v);
        break;
      case 'entero':
      case 'decimal':
        $v = str_replace(',', '.', $v);
        if (!is_numeric($v) || $v < $p[1] || $v > $p[2] || ($p[0] === 'entero' && (int)$v != $v)) return t('Revisá: {0}.', t($etiqueta));
        break;
      case 'fecha':
        $d = DateTime::createFromFormat('Y-m-d', $v);
        if (!$d || $d->format('Y-m-d') !== $v) return t('Revisá la fecha: {0}.', t($etiqueta));
        break;
      case 'si_no':
        if (!in_array($v, ['0', '1'], true)) return t('Revisá: {0}.', t($etiqueta));
        break;
      case 'opcion':
        if (!array_key_exists($v, $opciones)) return t('Revisá: {0}.', t($etiqueta));
        break;
    }
    $valores[$campo] = $v;
  }
  if (!$valores) return null;

  $columnas = array_keys($valores); // nombres fijos del catálogo, nunca vienen del usuario
  $sql = "INSERT INTO fichas_vehiculo (patente, " . implode(', ', $columnas) . ") VALUES (?" . str_repeat(', ?', count($columnas)) . ")
    ON DUPLICATE KEY UPDATE " . implode(', ', array_map(fn($c) => "$c = VALUES($c)", $columnas));
  dbq($db, $sql, str_repeat('s', count($columnas) + 1), $patente, ...array_values($valores));
  return null;
}

/* ---------------- Fotos ---------------- */

function urlFoto($archivo)
{
  return $archivo ? 'uploads/' . $archivo : null;
}

/**
 * Recibe la imagen subida en $_FILES[$campo], la valida y la guarda reconvertida a webp
 * (así se descarta cualquier cosa que no sea una imagen). Devuelve [nombre, null] o [null, error].
 */
function guardarFoto($campo, $lado, $cuadrada)
{
  $f = $_FILES[$campo] ?? null;
  if (!$f || $f['error'] !== UPLOAD_ERR_OK) {
    $grande = $f && in_array($f['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
    return [null, $grande ? t('La foto es muy pesada (máximo 8 MB).') : t('No llegó la foto. Probá de nuevo.')];
  }
  if ($f['size'] > 8 * 1024 * 1024) return [null, t('La foto es muy pesada (máximo 8 MB).')];
  $info = @getimagesize($f['tmp_name']);
  if (!$info || !in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
    return [null, t('Usá fotos JPG, PNG o WEBP.')];
  }
  if ($info[0] * $info[1] > 40000000) return [null, t('La foto tiene demasiada resolución.')];
  $img = @imagecreatefromstring(file_get_contents($f['tmp_name']));
  if (!$img) return [null, t('No se pudo leer la foto.')];

  // fotos del celular: respetar la orientación con la que se sacaron
  if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
    $giro = [3 => 180, 6 => -90, 8 => 90][(int)(@exif_read_data($f['tmp_name'])['Orientation'] ?? 1)] ?? 0;
    if ($giro) $img = imagerotate($img, $giro, 0);
  }

  $ancho = imagesx($img);
  $alto = imagesy($img);
  $sx = $sy = 0;
  if ($cuadrada) { // recorte centrado
    $menor = min($ancho, $alto);
    $sx = intdiv($ancho - $menor, 2);
    $sy = intdiv($alto - $menor, 2);
    $ancho = $alto = $menor;
  }
  $escala = min(1, $lado / max($ancho, $alto));
  $nw = max(1, (int)round($ancho * $escala));
  $nh = max(1, (int)round($alto * $escala));
  $salida = imagecreatetruecolor($nw, $nh);
  imagecopyresampled($salida, $img, 0, 0, $sx, $sy, $nw, $nh, $ancho, $alto);

  if (!is_dir(DIR_FOTOS)) mkdir(DIR_FOTOS, 0755, true);
  $nombre = bin2hex(random_bytes(16)) . '.webp';
  if (!imagewebp($salida, DIR_FOTOS . $nombre, 82)) return [null, t('No se pudo guardar la foto.')];
  return [$nombre, null];
}

// El registro queda (baja lógica) y el archivo se conserva en uploads/baja/: deja de estar publicado pero se puede recuperar
function archivarFoto($archivo)
{
  if ($archivo && preg_match('/^[a-f0-9]{32}\.webp$/', $archivo) && is_file(DIR_FOTOS . $archivo)) {
    if (!is_dir(DIR_FOTOS . 'baja')) mkdir(DIR_FOTOS . 'baja', 0755, true);
    rename(DIR_FOTOS . $archivo, DIR_FOTOS . 'baja/' . $archivo);
  }
}

/**
 * Fotos de una patente. Taller: todas. Cliente: las suyas y, si el taller confirmó que el auto es suyo, las del taller.
 */
function fotosVehiculo($db, $patente, $idUser = null, $verDelTaller = true)
{
  $filas = $idUser === null
    ? dbq($db, "SELECT f.id_foto, f.archivo, f.subida_por, f.creado, TRIM(CONCAT(COALESCE(u.nombre, ''), ' ', COALESCE(u.apellido, ''))) AS quien
        FROM vehiculos_fotos f LEFT JOIN usuarios u ON u.id_user = f.id_user
        WHERE f.patente = ? AND f.baja IS NULL ORDER BY f.id_foto", 's', $patente)
    : dbq($db, "SELECT id_foto, archivo, subida_por, creado, id_user FROM vehiculos_fotos
        WHERE patente = ? AND baja IS NULL AND (id_user = ? OR (subida_por = 'taller' AND ?))
        ORDER BY id_foto", 'sii', $patente, $idUser, (int)$verDelTaller);
  foreach ($filas as &$f) {
    $f['url'] = urlFoto($f['archivo']);
    $f['propia'] = $idUser === null || (int)($f['id_user'] ?? 0) === $idUser;
    unset($f['archivo'], $f['id_user']);
  }
  return $filas;
}

// Sube una foto a la galería del auto respetando el máximo. Devuelve [foto, null] o [null, error].
function agregarFotoVehiculo($db, $patente, $idUser, $subidaPor)
{
  $cantidad = (int)dbq($db, "SELECT COUNT(*) AS n FROM vehiculos_fotos WHERE patente = ? AND baja IS NULL AND "
    . ($subidaPor === 'taller' ? "subida_por = 'taller'" : "id_user = ?"),
    $subidaPor === 'taller' ? 's' : 'si', ...($subidaPor === 'taller' ? [$patente] : [$patente, $idUser]))[0]['n'];
  if ($cantidad >= MAX_FOTOS) return [null, t('Ya hay {0} fotos de este auto. Quitá alguna para subir otra.', MAX_FOTOS)];
  [$archivo, $error] = guardarFoto('archivo', 1600, false);
  if ($error) return [null, $error];
  dbq($db, "INSERT INTO vehiculos_fotos (patente, archivo, subida_por, id_user) VALUES (?, ?, ?, ?)", 'sssi', $patente, $archivo, $subidaPor, $idUser);
  return [['id_foto' => $db->insert_id, 'url' => urlFoto($archivo), 'subida_por' => $subidaPor, 'propia' => true], null];
}

function quitarFotoVehiculo($db, $idFoto, $condicion, $tipos = '', ...$params)
{
  $foto = dbq($db, "SELECT archivo FROM vehiculos_fotos WHERE id_foto = ? AND baja IS NULL AND $condicion", 'i' . $tipos, $idFoto, ...$params)[0] ?? null;
  if (!$foto) return false;
  dbq($db, "UPDATE vehiculos_fotos SET baja = NOW() WHERE id_foto = ?", 'i', $idFoto);
  archivarFoto($foto['archivo']);
  return true;
}
