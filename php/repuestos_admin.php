<?php
// Repuestos, proveedores y listas de precios (solo administrador)
// GET  accion=listar&q=&id_rubro=&id_proveedor=&bajas=1 -> repuestos con su % de ganancia y precio
// GET  accion=maestros                         -> configuración, rubros, proveedores e historial reciente
// POST accion=guardar | baja | reactivar        -> un repuesto
// POST accion=config                           -> % general, recargo y lavado
// POST accion=rubro (id_rubro, nombre, ganancia_pct) | rubro_baja (id)
// POST accion=proveedor (id_proveedor, nombre, ...) | proveedor_baja (id)
// POST accion=aumento (pct, id_rubro, id_proveedor, simular) -> aumento de costos; con simular=1 solo muestra qué cambia
// POST accion=lista_leer (id_proveedor, archivo) -> sube la lista y devuelve títulos, muestra y columnas reconocidas
// POST accion=lista_importar (token, columnas, fila_titulos, id_rubro, bajas_ausentes, simular) -> con simular=1 solo muestra qué cambia
require_once "repuestos_lib.php";
require_once "listas_lib.php";

if (!esAdmin()) {
  responderJSON(['error' => 'No autorizado'], 403);
}

$db = $conexionApi;
$idUser = (int)usuarioActual()['id_user'];
$num = fn($k) => is_numeric(str_replace(',', '.', $_POST[$k] ?? '')) ? (float)str_replace(',', '.', $_POST[$k]) : null;
$txt = fn($k, $max) => mb_substr(trim((string)($_POST[$k] ?? '')), 0, $max);

function q($sql, $tipos = '', ...$params)
{
  global $db;
  $st = $db->prepare($sql);
  if ($tipos !== '') $st->bind_param($tipos, ...$params);
  $st->execute();
  $res = $st->get_result();
  return $res ? $res->fetch_all(MYSQLI_ASSOC) : $st->affected_rows;
}

// % válido o vacío (null = usa el siguiente nivel)
function pctOpcional($valor)
{
  $valor = trim(str_replace(',', '.', (string)$valor));
  if ($valor === '') return [null, null];
  if (!is_numeric($valor) || $valor < 0 || $valor > 1000) return [null, 'El % de ganancia debe estar entre 0 y 1000.'];
  return [(float)$valor, null];
}

// Rubro por nombre (lo crea si no existe). Devuelve el id o null si el nombre está vacío.
function rubroPorNombre($nombre, &$cache = null)
{
  global $db;
  $nombre = mb_substr(trim((string)$nombre), 0, 60);
  if ($nombre === '') return null;
  $clave = mb_strtolower($nombre);
  if (is_array($cache) && isset($cache[$clave])) return $cache[$clave];
  $fila = q("SELECT id_rubro, baja FROM repuestos_rubros WHERE nombre = ?", 's', $nombre)[0] ?? null;
  if ($fila) {
    if ($fila['baja']) q("UPDATE repuestos_rubros SET baja = NULL WHERE id_rubro = ?", 'i', $fila['id_rubro']);
    $id = (int)$fila['id_rubro'];
  } else {
    q("INSERT INTO repuestos_rubros (nombre) VALUES (?)", 's', $nombre);
    $id = $db->insert_id;
  }
  if (is_array($cache)) $cache[$clave] = $id;
  return $id;
}

function proveedorLista($id)
{
  return q("SELECT * FROM proveedores WHERE id_proveedor = ? AND baja IS NULL", 'i', $id)[0] ?? null;
}

// Costo a partir del precio de lista del proveedor
function costoDeLista($precioLista, $proveedor)
{
  return round($precioLista * (1 - (float)$proveedor['bonificacion_pct'] / 100) * (1 + (float)$proveedor['iva_pct'] / 100), 2);
}

$accion = $_GET['accion'] ?? $_POST['accion'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $config = tallerConfig($db);
  if ($accion === 'maestros') {
    responderJSON([
      'config' => $config,
      'rubros' => q("SELECT c.id_rubro, c.nombre, c.ganancia_pct,
          (SELECT COUNT(*) FROM repuestos r WHERE r.id_rubro = c.id_rubro AND r.baja IS NULL) AS repuestos
        FROM repuestos_rubros c WHERE c.baja IS NULL ORDER BY c.nombre"),
      'proveedores' => q("SELECT p.id_proveedor, p.nombre, p.contacto, p.telefono, p.mail, p.ganancia_pct, p.bonificacion_pct, p.iva_pct, p.notas,
          (p.columnas IS NOT NULL) AS planilla_conocida,
          (SELECT COUNT(*) FROM repuestos r WHERE r.id_proveedor = p.id_proveedor AND r.baja IS NULL) AS repuestos,
          (SELECT MAX(i.fecha) FROM repuestos_importaciones i WHERE i.id_proveedor = p.id_proveedor) AS ultima_lista
        FROM proveedores p WHERE p.baja IS NULL ORDER BY p.nombre"),
      'importaciones' => q("SELECT i.fecha, i.archivo, i.filas, i.nuevos, i.actualizados, i.sin_cambio, i.bajas, i.variacion_pct, p.nombre AS proveedor
        FROM repuestos_importaciones i JOIN proveedores p ON p.id_proveedor = i.id_proveedor ORDER BY i.fecha DESC LIMIT 15"),
      'aumentos' => q("SELECT MIN(fecha) AS fecha, detalle, COUNT(*) AS repuestos FROM repuestos_costos
        WHERE origen = 'aumento' GROUP BY detalle, DATE_FORMAT(fecha, '%Y-%m-%d %H:%i') ORDER BY fecha DESC LIMIT 15"),
    ]);
  }

  // listado
  $q = '%' . trim($_GET['q'] ?? '') . '%';
  $idRubro = (int)($_GET['id_rubro'] ?? 0);
  $idProveedor = (int)($_GET['id_proveedor'] ?? 0);
  $conBajas = !empty($_GET['bajas']);
  $repuestos = q(REPUESTO_SELECT . " WHERE (r.descripcion LIKE ? OR r.codigo LIKE ? OR r.marca LIKE ?)
      AND (? = 0 OR r.id_rubro = ?) AND (? = 0 OR r.id_proveedor = ?)" . ($conBajas ? '' : " AND r.baja IS NULL") . "
    ORDER BY r.baja IS NOT NULL, c.nombre, r.descripcion LIMIT 500", 'sssiiii', $q, $q, $q, $idRubro, $idRubro, $idProveedor, $idProveedor);
  foreach ($repuestos as &$r) {
    [$r['ganancia'], $r['ganancia_origen']] = gananciaRepuesto($r, $config);
    $r['precio'] = precioRepuesto($r, $config);
  }
  unset($r);
  responderJSON(['repuestos' => $repuestos, 'limite' => count($repuestos) === 500]);
}

switch ($accion) {
  case 'guardar':
    $id = (int)($_POST['id_repuesto'] ?? 0);
    $descripcion = $txt('descripcion', 150);
    $costo = $num('costo');
    [$ganancia, $error] = pctOpcional($_POST['ganancia_pct'] ?? '');
    if ($descripcion === '' || $costo === null || $costo < 0) responderJSON(['error' => 'Completá la descripción y un costo válido.'], 422);
    if ($error) responderJSON(['error' => $error], 422);
    $idProveedor = (int)($_POST['id_proveedor'] ?? 0) ?: null;
    if ($idProveedor && !proveedorLista($idProveedor)) responderJSON(['error' => 'Elegí un proveedor de la lista.'], 422);
    $idRubro = rubroPorNombre($_POST['rubro'] ?? '');
    $codigo = $txt('codigo', 50);
    $marca = $txt('marca', 60);
    if ($id) {
      $antes = q("SELECT costo FROM repuestos WHERE id_repuesto = ?", 'i', $id)[0] ?? null;
      if (!$antes) responderJSON(['error' => 'No existe el repuesto.'], 404);
      q("UPDATE repuestos SET codigo = NULLIF(?, ''), descripcion = ?, marca = NULLIF(?, ''), id_rubro = ?, id_proveedor = ?, costo = ?, ganancia_pct = ?
        WHERE id_repuesto = ?", 'sssiiddi', $codigo, $descripcion, $marca, $idRubro, $idProveedor, $costo, $ganancia, $id);
      if ((float)$antes['costo'] !== $costo) {
        q("INSERT INTO repuestos_costos (id_repuesto, costo_anterior, costo_nuevo, origen, id_user) VALUES (?, ?, ?, 'manual', ?)",
          'iddi', $id, $antes['costo'], $costo, $idUser);
      }
    } else {
      q("INSERT INTO repuestos (codigo, descripcion, marca, id_rubro, id_proveedor, costo, ganancia_pct)
        VALUES (NULLIF(?, ''), ?, NULLIF(?, ''), ?, ?, ?, ?)", 'sssiidd', $codigo, $descripcion, $marca, $idRubro, $idProveedor, $costo, $ganancia);
      $id = $db->insert_id;
    }
    responderJSON(['ok' => true, 'id' => $id]);

  case 'baja':
  case 'reactivar':
    q($accion === 'baja' ? "UPDATE repuestos SET baja = NOW() WHERE id_repuesto = ? AND baja IS NULL"
      : "UPDATE repuestos SET baja = NULL WHERE id_repuesto = ?", 'i', (int)($_POST['id'] ?? 0));
    responderJSON(['ok' => true]);

  case 'config':
    $ganancia = $num('ganancia_repuestos_pct');
    $recargo = $num('recargo_mo_repuesto_cliente_pct');
    $lavado = $num('lavado_precio') ?? 0.0;
    if ($ganancia === null || $ganancia < 0 || $ganancia > 1000 || $recargo === null || $recargo < 0 || $recargo > 300) {
      responderJSON(['error' => 'Revisá los porcentajes.'], 422);
    }
    if ($lavado < 0) responderJSON(['error' => 'El valor del lavado no puede ser negativo.'], 422);
    $guardar = ['ganancia_repuestos_pct' => $ganancia, 'recargo_mo_repuesto_cliente_pct' => $recargo,
      'lavado_precio' => $lavado, 'lavado_por_defecto' => !empty($_POST['lavado_por_defecto']) ? 1 : 0];
    foreach ($guardar as $clave => $valor) {
      q("INSERT INTO taller_config (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)", 'ss', $clave, (string)$valor);
    }
    responderJSON(['ok' => true]);

  case 'rubro':
    $id = (int)($_POST['id_rubro'] ?? 0);
    $nombre = $txt('nombre', 60);
    [$ganancia, $error] = pctOpcional($_POST['ganancia_pct'] ?? '');
    if ($error) responderJSON(['error' => $error], 422);
    if ($nombre === '') responderJSON(['error' => 'Falta el nombre del rubro.'], 422);
    if (q("SELECT 1 FROM repuestos_rubros WHERE nombre = ? AND id_rubro <> ?", 'si', $nombre, $id)) {
      responderJSON(['error' => 'Ya hay un rubro con ese nombre.'], 422);
    }
    if ($id) q("UPDATE repuestos_rubros SET nombre = ?, ganancia_pct = ? WHERE id_rubro = ?", 'sdi', $nombre, $ganancia, $id);
    else q("INSERT INTO repuestos_rubros (nombre, ganancia_pct) VALUES (?, ?)", 'sd', $nombre, $ganancia);
    responderJSON(['ok' => true]);

  case 'rubro_baja':
    $id = (int)($_POST['id'] ?? 0);
    if (q("SELECT 1 FROM repuestos WHERE id_rubro = ? AND baja IS NULL LIMIT 1", 'i', $id)) {
      responderJSON(['error' => 'El rubro tiene repuestos: pasalos a otro rubro antes de darlo de baja.'], 422);
    }
    q("UPDATE repuestos_rubros SET baja = NOW() WHERE id_rubro = ?", 'i', $id);
    responderJSON(['ok' => true]);

  case 'proveedor':
    $id = (int)($_POST['id_proveedor'] ?? 0);
    $nombre = $txt('nombre', 80);
    [$ganancia, $error] = pctOpcional($_POST['ganancia_pct'] ?? '');
    $bonificacion = $num('bonificacion_pct') ?? 0.0;
    $iva = $num('iva_pct') ?? 0.0;
    $mail = mb_strtolower($txt('mail', 120));
    if ($nombre === '') responderJSON(['error' => 'Falta el nombre del proveedor.'], 422);
    if ($error) responderJSON(['error' => $error], 422);
    if ($bonificacion < 0 || $bonificacion >= 100) responderJSON(['error' => 'La bonificación debe estar entre 0 y 99%.'], 422);
    if ($iva < 0 || $iva > 50) responderJSON(['error' => 'Revisá el % de IVA.'], 422);
    if ($mail !== '' && !filter_var($mail, FILTER_VALIDATE_EMAIL)) responderJSON(['error' => 'El mail no es válido.'], 422);
    if (q("SELECT 1 FROM proveedores WHERE nombre = ? AND id_proveedor <> ?", 'si', $nombre, $id)) {
      responderJSON(['error' => 'Ya hay un proveedor con ese nombre.'], 422);
    }
    $datos = [$nombre, $txt('contacto', 80), $txt('telefono', 30), $mail, $ganancia, $bonificacion, $iva, $txt('notas', 255)];
    if ($id) {
      $antes = proveedorLista($id);
      if (!$antes) responderJSON(['error' => 'No existe el proveedor.'], 404);
      q("UPDATE proveedores SET nombre = ?, contacto = NULLIF(?, ''), telefono = NULLIF(?, ''), mail = NULLIF(?, ''), ganancia_pct = ?,
        bonificacion_pct = ?, iva_pct = ?, notas = NULLIF(?, '') WHERE id_proveedor = ?", 'ssssdddsi', ...[...$datos, $id]);
      // si cambia la bonificación o el IVA, se recalcula el costo de lo que vino en su lista
      if ((float)$antes['bonificacion_pct'] !== $bonificacion || (float)$antes['iva_pct'] !== $iva) {
        $detalle = "Bonificación / IVA de {$nombre}";
        $factor = (1 - $bonificacion / 100) * (1 + $iva / 100);
        q("INSERT INTO repuestos_costos (id_repuesto, costo_anterior, costo_nuevo, origen, detalle, id_user)
          SELECT id_repuesto, costo, ROUND(precio_lista * ?, 2), 'aumento', ?, ? FROM repuestos
          WHERE id_proveedor = ? AND precio_lista IS NOT NULL AND baja IS NULL AND ROUND(precio_lista * ?, 2) <> costo",
          'dsiid', $factor, $detalle, $idUser, $id, $factor);
        q("UPDATE repuestos SET costo = ROUND(precio_lista * ?, 2) WHERE id_proveedor = ? AND precio_lista IS NOT NULL AND baja IS NULL",
          'di', $factor, $id);
      }
    } else {
      q("INSERT INTO proveedores (nombre, contacto, telefono, mail, ganancia_pct, bonificacion_pct, iva_pct, notas)
        VALUES (?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), ?, ?, ?, NULLIF(?, ''))", 'ssssddds', ...$datos);
      $id = $db->insert_id;
    }
    responderJSON(['ok' => true, 'id' => $id]);

  case 'proveedor_baja':
    $id = (int)($_POST['id'] ?? 0);
    if (q("SELECT 1 FROM repuestos WHERE id_proveedor = ? AND baja IS NULL LIMIT 1", 'i', $id)) {
      responderJSON(['error' => 'El proveedor tiene repuestos activos: dalos de baja o pasalos a otro proveedor primero.'], 422);
    }
    q("UPDATE proveedores SET baja = NOW() WHERE id_proveedor = ?", 'i', $id);
    responderJSON(['ok' => true]);

  case 'aumento':
    // actualización masiva de costos: todos, un rubro, un proveedor o un rubro de un proveedor
    $pct = $num('pct');
    $idRubro = (int)($_POST['id_rubro'] ?? 0);
    $idProveedor = (int)($_POST['id_proveedor'] ?? 0);
    if ($pct === null || $pct <= -90 || $pct > 500 || $pct == 0) {
      responderJSON(['error' => 'Indicá un % válido (puede ser negativo para una baja de precio).'], 422);
    }
    $factor = 1 + $pct / 100;
    $donde = "baja IS NULL AND (? = 0 OR id_rubro = ?) AND (? = 0 OR id_proveedor = ?)";
    $filtro = [$idRubro, $idRubro, $idProveedor, $idProveedor];
    if (!empty($_POST['simular'])) {
      $config = tallerConfig($db);
      $muestra = q(REPUESTO_SELECT . " WHERE r.baja IS NULL AND (? = 0 OR r.id_rubro = ?) AND (? = 0 OR r.id_proveedor = ?)
        ORDER BY r.descripcion LIMIT 8", 'iiii', ...$filtro);
      foreach ($muestra as &$r) {
        $r['precio'] = precioRepuesto($r, $config);
        $r['costo_nuevo'] = round($r['costo'] * $factor, 2);
        $r['precio_nuevo'] = precioRepuesto(['costo' => $r['costo_nuevo']] + $r, $config);
      }
      unset($r);
      responderJSON(['repuestos' => (int)q("SELECT COUNT(*) AS n FROM repuestos WHERE $donde", 'iiii', ...$filtro)[0]['n'], 'muestra' => $muestra]);
    }
    $partes = array_filter([
      $idRubro ? 'rubro ' . (q("SELECT nombre FROM repuestos_rubros WHERE id_rubro = ?", 'i', $idRubro)[0]['nombre'] ?? '') : '',
      $idProveedor ? 'proveedor ' . (q("SELECT nombre FROM proveedores WHERE id_proveedor = ?", 'i', $idProveedor)[0]['nombre'] ?? '') : '',
    ]);
    $detalle = mb_substr(($pct > 0 ? 'Aumento ' : 'Baja ') . rtrim(rtrim(number_format(abs($pct), 2, ',', ''), '0'), ',') . '% ' . ($partes ? implode(', ', $partes) : 'general'), 0, 150);
    $db->begin_transaction();
    q("INSERT INTO repuestos_costos (id_repuesto, costo_anterior, costo_nuevo, origen, detalle, id_user)
      SELECT id_repuesto, costo, ROUND(costo * ?, 2), 'aumento', ?, ? FROM repuestos WHERE $donde", 'dsiiiii', $factor, $detalle, $idUser, ...$filtro);
    // también el precio de lista, así la próxima lista del proveedor se compara bien
    $cambiados = q("UPDATE repuestos SET costo = ROUND(costo * ?, 2), precio_lista = ROUND(precio_lista * ?, 2) WHERE $donde",
      'ddiiii', $factor, $factor, ...$filtro);
    $db->commit();
    responderJSON(['ok' => true, 'actualizados' => $cambiados]);

  case 'lista_leer':
    $proveedor = proveedorLista((int)($_POST['id_proveedor'] ?? 0));
    if (!$proveedor) responderJSON(['error' => 'Elegí el proveedor de la lista.'], 422);
    $f = $_FILES['archivo'] ?? null;
    if (!$f || $f['error'] !== UPLOAD_ERR_OK) responderJSON(['error' => 'No llegó el archivo. Probá de nuevo.'], 422);
    if ($f['size'] > LISTA_MAX_MB * 1024 * 1024) responderJSON(['error' => 'El archivo pesa más de ' . LISTA_MAX_MB . ' MB.'], 422);
    [$filas, $error] = leerPlanilla($f['tmp_name'], $f['name']);
    if ($error) responderJSON(['error' => $error], 422);
    if (count($filas) < 2) responderJSON(['error' => 'La planilla está vacía.'], 422);

    // queda guardada (fuera de la web) mientras se revisa; se borra al importar
    $token = bin2hex(random_bytes(12));
    file_put_contents(sys_get_temp_dir() . "/ssp_lista_$token.json", json_encode($filas));
    $_SESSION['listas'][$token] = ['id_proveedor' => (int)$proveedor['id_proveedor'], 'archivo' => mb_substr($f['name'], 0, 150)];

    // si ya se importó una lista de este proveedor, se usan las mismas columnas
    $guardado = json_decode((string)$proveedor['columnas'], true);
    $filaTitulos = $guardado['fila_titulos'] ?? filaTitulos($filas);
    $columnas = $guardado['columnas'] ?? adivinarColumnas($filas[$filaTitulos] ?? []);
    responderJSON(['token' => $token, 'filas' => count($filas), 'fila_titulos' => $filaTitulos, 'columnas' => $columnas,
      'conocida' => (bool)$guardado, 'muestra' => array_slice($filas, 0, 30), 'campos' => LISTA_CAMPOS]);

  case 'lista_importar':
    $token = preg_replace('/[^a-f0-9]/', '', (string)($_POST['token'] ?? ''));
    $sesion = $_SESSION['listas'][$token] ?? null;
    $ruta = sys_get_temp_dir() . "/ssp_lista_$token.json";
    if (!$sesion || !is_file($ruta)) responderJSON(['error' => 'La lista ya no está disponible. Volvé a subirla.'], 422);
    $proveedor = proveedorLista($sesion['id_proveedor']);
    if (!$proveedor) responderJSON(['error' => 'El proveedor ya no existe.'], 422);
    $filas = json_decode(file_get_contents($ruta), true);
    $filaTitulos = max(0, (int)($_POST['fila_titulos'] ?? 0));
    $columnas = [];
    foreach (array_keys(LISTA_CAMPOS) as $campo) {
      $v = $_POST['col_' . $campo] ?? '';
      $columnas[$campo] = $v === '' ? null : (int)$v;
    }
    if ($columnas['descripcion'] === null || $columnas['precio'] === null) {
      responderJSON(['error' => 'Indicá al menos qué columna es la descripción y cuál es el precio.'], 422);
    }
    $idRubroDefecto = (int)($_POST['id_rubro'] ?? 0) ?: null;
    $bajasAusentes = !empty($_POST['bajas_ausentes']) && $columnas['codigo'] !== null;
    $simular = !empty($_POST['simular']);

    // lo que ya hay de este proveedor: por código (o por descripción si la lista no trae código)
    $existentes = [];
    foreach (q("SELECT id_repuesto, codigo, descripcion, costo, precio_lista, baja FROM repuestos WHERE id_proveedor = ?", 'i', $proveedor['id_proveedor']) as $r) {
      $clave = $columnas['codigo'] !== null ? mb_strtolower(trim((string)$r['codigo'])) : mb_strtolower(trim($r['descripcion']));
      if ($clave !== '' && (!isset($existentes[$clave]) || !$r['baja'])) $existentes[$clave] = $r;
    }

    $celda = fn($fila, $campo) => $columnas[$campo] === null ? '' : trim((string)($fila[$columnas[$campo]] ?? ''));
    $resumen = ['filas' => 0, 'nuevos' => 0, 'actualizados' => 0, 'sin_cambio' => 0, 'ignoradas' => 0, 'bajas' => 0, 'rubros_nuevos' => []];
    $cambios = [];
    $variaciones = [];
    $vistos = [];
    $rubros = [];
    $rubrosExistentes = array_column(q("SELECT LOWER(nombre) AS n FROM repuestos_rubros WHERE baja IS NULL"), 'n', 'n');

    if (!$simular) {
      $db->begin_transaction();
      q("INSERT INTO repuestos_importaciones (id_proveedor, archivo, id_user) VALUES (?, ?, ?)", 'isi', $proveedor['id_proveedor'], $sesion['archivo'], $idUser);
      $idImportacion = $db->insert_id;
      $stNuevo = $db->prepare("INSERT INTO repuestos (codigo, descripcion, marca, id_rubro, id_proveedor, costo, precio_lista, id_importacion)
        VALUES (NULLIF(?, ''), ?, NULLIF(?, ''), ?, ?, ?, ?, ?)");
      $stActualizar = $db->prepare("UPDATE repuestos SET descripcion = ?, marca = COALESCE(NULLIF(?, ''), marca), id_rubro = COALESCE(?, id_rubro),
        costo = ?, precio_lista = ?, id_importacion = ?, baja = NULL WHERE id_repuesto = ?");
      $stHistorial = $db->prepare("INSERT INTO repuestos_costos (id_repuesto, costo_anterior, costo_nuevo, origen, id_importacion, id_user)
        VALUES (?, ?, ?, 'importacion', ?, ?)");
    }

    foreach (array_slice($filas, $filaTitulos + 1) as $fila) {
      $descripcion = mb_substr($celda($fila, 'descripcion'), 0, 150);
      $precio = numeroLista($fila[$columnas['precio']] ?? '');
      if ($descripcion === '' || $precio === null || $precio <= 0) { // títulos repetidos, subtotales, filas vacías
        $resumen['ignoradas']++;
        continue;
      }
      $resumen['filas']++;
      $codigo = mb_substr($celda($fila, 'codigo'), 0, 50);
      $marca = mb_substr($celda($fila, 'marca'), 0, 60);
      $nombreRubro = mb_substr($celda($fila, 'rubro'), 0, 60);
      if ($nombreRubro !== '' && !isset($rubrosExistentes[mb_strtolower($nombreRubro)])) $resumen['rubros_nuevos'][mb_strtolower($nombreRubro)] = $nombreRubro;
      $costo = costoDeLista($precio, $proveedor);
      $clave = mb_strtolower($columnas['codigo'] !== null ? $codigo : $descripcion);
      if ($clave === '' || isset($vistos[$clave])) { // sin código o repetido en la misma lista
        $resumen['ignoradas']++;
        $resumen['filas']--;
        continue;
      }
      $vistos[$clave] = true;
      $actual = $existentes[$clave] ?? null;

      if (!$actual) {
        $resumen['nuevos']++;
        if (!$simular) {
          $idRubro = $nombreRubro !== '' ? rubroPorNombre($nombreRubro, $rubros) : $idRubroDefecto;
          $stNuevo->bind_param('sssiiddi', $codigo, $descripcion, $marca, $idRubro, $proveedor['id_proveedor'], $costo, $precio, $idImportacion);
          $stNuevo->execute();
        }
        continue;
      }
      $anterior = (float)$actual['costo'];
      if (abs($anterior - $costo) < 0.005 && !$actual['baja']) {
        $resumen['sin_cambio']++;
      } else {
        $resumen['actualizados']++;
        if ($anterior > 0) {
          $variaciones[] = ($costo / $anterior - 1) * 100;
          if (count($cambios) < 12) $cambios[] = ['codigo' => $codigo, 'descripcion' => $descripcion, 'antes' => $anterior, 'ahora' => $costo];
        }
      }
      if (!$simular) {
        $idRubro = $nombreRubro !== '' ? rubroPorNombre($nombreRubro, $rubros) : null;
        $stActualizar->bind_param('ssiddii', $descripcion, $marca, $idRubro, $costo, $precio, $idImportacion, $actual['id_repuesto']);
        $stActualizar->execute();
        if (abs($anterior - $costo) >= 0.005) {
          $stHistorial->bind_param('iddii', $actual['id_repuesto'], $anterior, $costo, $idImportacion, $idUser);
          $stHistorial->execute();
        }
      }
    }

    // los que ya no vienen en la lista
    $ausentes = array_filter($existentes, fn($r, $clave) => !$r['baja'] && !isset($vistos[$clave]), ARRAY_FILTER_USE_BOTH);
    $resumen['ausentes'] = count($ausentes);
    if ($bajasAusentes) {
      $resumen['bajas'] = count($ausentes);
      if (!$simular && $ausentes) {
        $ids = implode(',', array_map(fn($r) => (int)$r['id_repuesto'], $ausentes));
        $db->query("UPDATE repuestos SET baja = NOW() WHERE id_repuesto IN ($ids)");
      }
    }
    $resumen['variacion_pct'] = $variaciones ? round(array_sum($variaciones) / count($variaciones), 2) : null;
    $resumen['rubros_nuevos'] = array_values($resumen['rubros_nuevos']);
    $resumen['cambios'] = $cambios;

    if ($simular) responderJSON(['simulacion' => $resumen]);

    q("UPDATE repuestos_importaciones SET filas = ?, nuevos = ?, actualizados = ?, sin_cambio = ?, bajas = ?, variacion_pct = ? WHERE id_importacion = ?",
      'iiiiidi', $resumen['filas'], $resumen['nuevos'], $resumen['actualizados'], $resumen['sin_cambio'], $resumen['bajas'], $resumen['variacion_pct'], $idImportacion);
    // la próxima lista de este proveedor se lee igual
    q("UPDATE proveedores SET columnas = ? WHERE id_proveedor = ?", 'si', json_encode(['fila_titulos' => $filaTitulos, 'columnas' => $columnas]), $proveedor['id_proveedor']);
    $db->commit();
    unlink($ruta);
    unset($_SESSION['listas'][$token]);
    responderJSON(['ok' => true, 'resumen' => $resumen]);
}

responderJSON(['error' => 'Acción desconocida'], 400);
