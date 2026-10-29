<?php
// Presupuestos todavía sin reparación (admin). Respuesta: lista o 'sinPresu'
require_once "admin_lib.php";

$datos = q("SELECT c.empresa_nyp, v.marca, v.modelo, v.patente, p.id_presu
  FROM presupuestos p
  JOIN clientes c ON c.id_cliente = p.id_cliente
  JOIN vehiculos v ON v.id_vehiculo = p.id_vehiculo
  WHERE p.usado = 0 AND p.baja IS NULL ORDER BY p.fecha_presu DESC");
salir($datos ?: 'sinPresu');
