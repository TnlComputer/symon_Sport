<?php
// Datos del usuario con sesión iniciada (o "inexistente")
require_once __DIR__ . "/../includes/bootstrap.php";
header('Content-Type: application/json; charset=utf-8');
echo json_encode(usuarioActual() ?: 'inexistente', JSON_UNESCAPED_UNICODE);
