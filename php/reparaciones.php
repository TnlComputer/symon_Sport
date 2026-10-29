<?php
// Listado de reparaciones (admin). Respuesta: lista o 'inexistente'
require_once "admin_lib.php";

$datos = q("SELECT c.empresa_nyp, v.marca, v.modelo, r.*
  FROM reparaciones r
  LEFT JOIN clientes c ON c.id_cliente = r.id_cliente
  LEFT JOIN vehiculos v ON v.id_vehiculo = r.id_vehiculo
  ORDER BY r.fecha_trab DESC");
salir($datos ?: 'inexistente');
