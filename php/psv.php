<?php
// Datos de un presupuesto para la pantalla de reparaciones (admin). Respuesta: objeto o 'error'
require_once "admin_lib.php";

$id = (int)json_decode(file_get_contents("php://input"), true);
$fila = q("SELECT c.empresa_nyp, v.marca, v.modelo, v.patente, p.id_presu
  FROM presupuestos p
  JOIN clientes c ON c.id_cliente = p.id_cliente
  JOIN vehiculos v ON v.id_vehiculo = p.id_vehiculo
  WHERE p.id_presu = ?", 'i', $id)[0] ?? null;
salir($fila ?: 'error');
