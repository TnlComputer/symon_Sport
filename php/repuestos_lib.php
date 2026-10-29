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

/**
 * % de ganancia de un repuesto: el propio -> el del rubro -> el del proveedor -> el general.
 * Devuelve [pct, origen] con origen 'propio' | 'rubro' | 'proveedor' | 'general'.
 */
function gananciaRepuesto($fila, $config)
{
  foreach (['ganancia_pct' => 'propio', 'pct_rubro' => 'rubro', 'pct_proveedor' => 'proveedor'] as $campo => $origen) {
    if (isset($fila[$campo]) && $fila[$campo] !== '') return [(float)$fila[$campo], $origen];
  }
  return [(float)$config['ganancia_repuestos_pct'], 'general'];
}

// Precio al cliente = costo + % de ganancia. $fila: repuesto leído con REPUESTO_SELECT
function precioRepuesto($fila, $config)
{
  return round((float)$fila['costo'] * (1 + gananciaRepuesto($fila, $config)[0] / 100), 2);
}

// Repuesto con su proveedor y rubro (y los % de cada uno). Uso: REPUESTO_SELECT . " WHERE ..."
const REPUESTO_SELECT = "SELECT r.id_repuesto, r.codigo, r.descripcion, r.marca, r.costo, r.precio_lista, r.ganancia_pct,
    r.id_proveedor, r.id_rubro, r.actualizado, r.baja, p.nombre AS proveedor, c.nombre AS rubro,
    c.ganancia_pct AS pct_rubro, p.ganancia_pct AS pct_proveedor
  FROM repuestos r
  LEFT JOIN proveedores p ON p.id_proveedor = r.id_proveedor
  LEFT JOIN repuestos_rubros c ON c.id_rubro = r.id_rubro";

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
