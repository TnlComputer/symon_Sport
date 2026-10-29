<?php
// Catálogo de repuestos (solo administrador)
// GET  ?q=texto&categoria=&bajas=1        -> listado con precio calculado
// POST accion=guardar | baja | reactivar | aumento | config
require_once "repuestos_lib.php";

if (!esAdmin()) {
  responderJSON(['error' => 'No autorizado'], 403);
}

$db = $conexionApi;
$num = fn($k) => is_numeric(str_replace(',', '.', $_POST[$k] ?? '')) ? (float)str_replace(',', '.', $_POST[$k]) : null;
$txt = fn($k, $max) => mb_substr(trim($_POST[$k] ?? ''), 0, $max);

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  $config = tallerConfig($db);
  $q = '%' . trim($_GET['q'] ?? '') . '%';
  $categoria = trim($_GET['categoria'] ?? '');
  $conBajas = !empty($_GET['bajas']);

  $sql = "SELECT id_repuesto, codigo, descripcion, marca, categoria, proveedor, costo, ganancia_pct, actualizado, baja
    FROM repuestos
    WHERE (descripcion LIKE ? OR codigo LIKE ? OR marca LIKE ? OR proveedor LIKE ?)
      AND (? = '' OR categoria = ?)" . ($conBajas ? '' : " AND baja IS NULL") . "
    ORDER BY baja IS NOT NULL, categoria, descripcion LIMIT 500";
  $st = $db->prepare($sql);
  $st->bind_param('ssssss', $q, $q, $q, $q, $categoria, $categoria);
  $st->execute();
  $repuestos = $st->get_result()->fetch_all(MYSQLI_ASSOC);
  foreach ($repuestos as &$r) {
    $r['precio'] = precioRepuesto($r['costo'], $r['ganancia_pct'], $config);
  }

  $lista = fn($campo) => array_column($db->query("SELECT DISTINCT $campo AS v FROM repuestos
    WHERE baja IS NULL AND $campo IS NOT NULL AND $campo <> '' ORDER BY $campo")->fetch_all(MYSQLI_ASSOC), 'v');

  responderJSON([
    'config' => $config,
    'repuestos' => $repuestos,
    'categorias' => $lista('categoria'),
    'proveedores' => $lista('proveedor'),
  ]);
}

switch ($_POST['accion'] ?? '') {
  case 'guardar':
    $id = (int)($_POST['id_repuesto'] ?? 0);
    $descripcion = $txt('descripcion', 150);
    $codigo = $txt('codigo', 50);
    $marca = $txt('marca', 60);
    $categoria = $txt('categoria', 60);
    $proveedor = $txt('proveedor', 80);
    $costo = $num('costo');
    $ganancia = $num('ganancia_pct'); // vacío = usa el % general
    if ($descripcion === '' || $costo === null || $costo < 0) {
      responderJSON(['error' => 'Completá la descripción y un costo válido.'], 422);
    }
    if ($ganancia !== null && ($ganancia < 0 || $ganancia > 1000)) {
      responderJSON(['error' => 'El % de ganancia debe estar entre 0 y 1000.'], 422);
    }
    if ($id) {
      $st = $db->prepare("UPDATE repuestos SET codigo = NULLIF(?, ''), descripcion = ?, marca = NULLIF(?, ''),
        categoria = NULLIF(?, ''), proveedor = NULLIF(?, ''), costo = ?, ganancia_pct = ? WHERE id_repuesto = ?");
      $st->bind_param('sssssddi', $codigo, $descripcion, $marca, $categoria, $proveedor, $costo, $ganancia, $id);
    } else {
      $st = $db->prepare("INSERT INTO repuestos (codigo, descripcion, marca, categoria, proveedor, costo, ganancia_pct)
        VALUES (NULLIF(?, ''), ?, NULLIF(?, ''), NULLIF(?, ''), NULLIF(?, ''), ?, ?)");
      $st->bind_param('sssssdd', $codigo, $descripcion, $marca, $categoria, $proveedor, $costo, $ganancia);
    }
    $st->execute();
    responderJSON(['ok' => true, 'id' => $id ?: $db->insert_id]);

  case 'baja':
  case 'reactivar':
    $id = (int)($_POST['id'] ?? 0);
    $sql = $_POST['accion'] === 'baja'
      ? "UPDATE repuestos SET baja = NOW() WHERE id_repuesto = ? AND baja IS NULL"
      : "UPDATE repuestos SET baja = NULL WHERE id_repuesto = ?";
    $st = $db->prepare($sql);
    $st->bind_param('i', $id);
    $st->execute();
    responderJSON(['ok' => true]);

  case 'aumento':
    // actualización masiva de costos (ej. lista nueva del proveedor)
    $pct = $num('pct');
    $categoria = $txt('categoria', 60);
    $proveedor = $txt('proveedor', 80);
    if ($pct === null || $pct <= -90 || $pct > 500 || $pct == 0) {
      responderJSON(['error' => 'Indicá un % de aumento válido (puede ser negativo para una baja de precio).'], 422);
    }
    $factor = 1 + $pct / 100;
    $st = $db->prepare("UPDATE repuestos SET costo = ROUND(costo * ?, 2)
      WHERE baja IS NULL AND (? = '' OR categoria = ?) AND (? = '' OR proveedor = ?)");
    $st->bind_param('dssss', $factor, $categoria, $categoria, $proveedor, $proveedor);
    $st->execute();
    responderJSON(['ok' => true, 'actualizados' => $st->affected_rows]);

  case 'config':
    $ganancia = $num('ganancia_repuestos_pct');
    $recargo = $num('recargo_mo_repuesto_cliente_pct');
    $lavado = $num('lavado_precio') ?? 0.0;
    $lavadoDefecto = !empty($_POST['lavado_por_defecto']) ? 1 : 0;
    if ($ganancia === null || $ganancia < 0 || $ganancia > 1000 || $recargo === null || $recargo < 0 || $recargo > 300) {
      responderJSON(['error' => 'Revisá los porcentajes.'], 422);
    }
    if ($lavado < 0) {
      responderJSON(['error' => 'El valor del lavado no puede ser negativo.'], 422);
    }
    $st = $db->prepare("INSERT INTO taller_config (clave, valor) VALUES (?, ?) ON DUPLICATE KEY UPDATE valor = VALUES(valor)");
    $guardar = [
      'ganancia_repuestos_pct' => $ganancia,
      'recargo_mo_repuesto_cliente_pct' => $recargo,
      'lavado_precio' => $lavado,
      'lavado_por_defecto' => $lavadoDefecto,
    ];
    foreach ($guardar as $clave => $valor) {
      $valor = (string)$valor;
      $st->bind_param('ss', $clave, $valor);
      $st->execute();
    }
    responderJSON(['ok' => true]);
}

responderJSON(['error' => 'Acción desconocida'], 400);
