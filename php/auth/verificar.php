<?php
// GET t=token (link del mail) -> activa la cuenta e inicia sesión
require_once "lib.php";

$idUser = consumirToken($conexionApi, $_GET['t'] ?? '', 'verificar');
if (!$idUser) {
  header('Location: /login.php?aviso=link_vencido');
  exit;
}

$st = $conexionApi->prepare("UPDATE usuarios SET verificado = 1 WHERE id_user = ?");
$st->bind_param('i', $idUser);
$st->execute();

$usuario = buscarUsuario($conexionApi, 'id_user', (string)$idUser);
$datos = iniciarSesion($conexionApi, $usuario);
if (!$datos) {
  header('Location: /login.php?aviso=cuenta_baja');
  exit;
}
header('Location: /' . destinoSegunRol($datos['rol']) . '?bienvenido=1');
