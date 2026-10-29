<?php
// Consultas por patente se unificó en Clientes (busca por patente y muestra todo el historial del auto)
$q = isset($_GET['patente']) ? '?q=' . rawurlencode((string)$_GET['patente']) : '';
header('Location: clientes.php' . $q, true, 301);
exit;
