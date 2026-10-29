<?php
// Envía el presupuesto por mail al cliente (admin). Respuesta: { err, message }
// El lavado y el recargo NO se nombran: están incluidos en la mano de obra.
require_once "admin_lib.php";

$id = (int)($_POST['id'] ?? 0);
$p = q("SELECT p.*, c.empresa_nyp, c.mail, v.patente, v.marca, v.modelo, v.anio
  FROM presupuestos p
  JOIN clientes c ON c.id_cliente = p.id_cliente
  JOIN vehiculos v ON v.id_vehiculo = p.id_vehiculo
  WHERE p.id_presu = ? AND p.baja IS NULL", 'i', $id)[0] ?? null;

if (!$p || !filter_var($p['mail'], FILTER_VALIDATE_EMAIL)) {
  salir(['err' => 'true', 'message' => 'El cliente no tiene un mail válido cargado.']);
}

$items = q("SELECT descripcion, cantidad, provisto, precio_unit FROM presupuestos_items
  WHERE id_presu = ? AND baja IS NULL ORDER BY id_item", 'i', $id);

$pesos = fn($n) => '$ ' . number_format((float)$n, 0, ',', '.');
$filas = '';
foreach ($items as $it) {
  $cant = rtrim(rtrim(number_format((float)$it['cantidad'], 2, ',', '.'), '0'), ',');
  $importe = $it['provisto'] === 'cliente' ? 'Aportado por el cliente' : $pesos($it['cantidad'] * $it['precio_unit']);
  $filas .= '<tr><td style="padding:6px 0;border-bottom:1px solid #eee">' . e($cant) . ' × ' . e($it['descripcion'])
    . '</td><td style="padding:6px 0;border-bottom:1px solid #eee;text-align:right">' . e($importe) . '</td></tr>';
}

$fecha = $p['fecha_presu'] ? date('d/m/Y', strtotime($p['fecha_presu'])) : '';
$vehiculo = trim($p['marca'] . ' ' . $p['modelo'] . ' ' . $p['anio']);
$texto = 'Hola ' . e($p['empresa_nyp']) . ', te enviamos el presupuesto <b>#' . (int)$p['id_presu'] . '</b> del ' . e($fecha)
  . ' para tu vehículo <b>' . e($p['patente']) . '</b> (' . e($vehiculo) . ').</p>'
  . '<p style="white-space:pre-line">' . e($p['descripcion_presu']) . '</p>'
  . ($filas ? '<table style="width:100%;border-collapse:collapse;margin:12px 0">' . $filas . '</table>' : '')
  . '<table style="width:100%;border-collapse:collapse;margin-top:8px">'
  . '<tr><td>Repuestos</td><td style="text-align:right">' . $pesos($p['repuestos_presu']) . '</td></tr>'
  . '<tr><td>Mano de obra</td><td style="text-align:right">' . $pesos($p['mobra_presu']) . '</td></tr>'
  . '<tr><td style="padding-top:6px;border-top:1px solid #ccc"><b>Total</b></td><td style="padding-top:6px;border-top:1px solid #ccc;text-align:right"><b>' . $pesos($p['total_presu']) . '</b></td></tr>'
  . ((float)$p['senia_presu'] > 0 ? '<tr><td>Seña</td><td style="text-align:right">' . $pesos($p['senia_presu']) . '</td></tr>'
    . '<tr><td>Saldo</td><td style="text-align:right">' . $pesos($p['abonado_presu']) . '</td></tr>' : '')
  . '</table><p style="color:#777;font-size:13px">Presupuesto válido por ' . (int)$p['dias_presu'] . ' días.';

$ok = enviarMail($p['mail'], "Presupuesto #{$p['id_presu']} - {$p['patente']} - Symon Sport", 'Tu presupuesto', $texto);
salir($ok
  ? ['err' => 'false', 'message' => 'Presupuesto enviado a ' . $p['mail']]
  : ['err' => 'true', 'message' => 'No se pudo enviar el mail.']);
