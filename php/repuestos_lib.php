<?php
// Cálculo de precios de repuestos y de presupuestos (compartido)
require_once __DIR__ . "/auth/lib.php";

function tallerConfig($db)
{
  $config = ['ganancia_repuestos_pct' => 30.0, 'recargo_mo_repuesto_cliente_pct' => 0.0];
  $res = $db->query("SELECT clave, valor FROM taller_config");
  while ($fila = $res->fetch_assoc()) {
    $config[$fila['clave']] = is_numeric($fila['valor']) ? (float)$fila['valor'] : $fila['valor'];
  }
  return $config;
}

// Precio al cliente = costo + % de ganancia (el propio del repuesto o el general)
function precioRepuesto($costo, $gananciaPropia, $config)
{
  $pct = $gananciaPropia !== null && $gananciaPropia !== '' ? (float)$gananciaPropia : (float)$config['ganancia_repuestos_pct'];
  return round((float)$costo * (1 + $pct / 100), 2);
}

/**
 * Totales de un presupuesto a partir de sus repuestos y la mano de obra base.
 * Los repuestos que trae el cliente no se cobran, pero agregan un recargo a la mano de obra.
 * El recargo y el lavado NO se nombran al cliente: van sumados dentro de la mano de obra
 * (mano_obra_cliente). El desglose (recargo_mano_obra, lavado) es solo para uso interno.
 * $items: [['cantidad' => .., 'precio_unit' => .., 'provisto' => 'taller'|'cliente'], ...]
 * $lavado: null = precio configurado (si está activado por defecto), 0 = sin lavado, otro = ese valor
 */
function totalesPresupuesto($items, $manoObraBase, $config, $lavado = null)
{
  if ($lavado === null) {
    $lavado = !empty($config['lavado_por_defecto']) ? (float)($config['lavado_precio'] ?? 0) : 0.0;
  }
  $lavado = max(0.0, round((float)$lavado, 2));

  $repuestos = 0.0;
  $traeCliente = false;
  foreach ($items as $it) {
    if (($it['provisto'] ?? 'taller') === 'cliente') {
      $traeCliente = true;
    } else {
      $repuestos += (float)$it['cantidad'] * (float)$it['precio_unit'];
    }
  }
  $recargo = $traeCliente ? round((float)$manoObraBase * (float)$config['recargo_mo_repuesto_cliente_pct'] / 100, 2) : 0.0;
  $manoObra = round((float)$manoObraBase + $recargo, 2);
  $manoObraCliente = round($manoObra + $lavado, 2); // lo único que ve el cliente como "mano de obra"
  return [
    'repuestos' => round($repuestos, 2),
    'mano_obra_base' => round((float)$manoObraBase, 2),
    'recargo_mano_obra' => $recargo,
    'mano_obra' => $manoObra,
    'lavado' => $lavado,                       // interno
    'mano_obra_cliente' => $manoObraCliente,
    'total' => round($repuestos + $manoObraCliente, 2),
  ];
}
