<?php
// Funciones compartidas de autenticación
require_once __DIR__ . "/../../includes/bootstrap.php"; // sesión segura, cabeceras y CSRF
require_once __DIR__ . "/../connections/conexion.php";

$conexionApi->set_charset('utf8mb4');

// roles: admin = administrador del taller, usuario = cliente / persona registrada en la página
const ROL_ADMIN = 'admin';
const ROL_USUARIO = 'usuario';
const MAX_INTENTOS = 8;          // ingresos fallidos permitidos por cuenta...
const MAX_INTENTOS_IP = 40;      // ...y por IP (varias personas pueden compartir la misma red)
const MAX_ACCIONES_IP = 10;      // registros / pedidos de recuperación por IP
const VENTANA_INTENTOS_MIN = 15; // en esta cantidad de minutos

function responderJSON($datos, $codigo = 200)
{
  http_response_code($codigo);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($datos, JSON_UNESCAPED_UNICODE);
  exit;
}

function urlSitio()
{
  $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
  return ($https ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
}

function ipCliente()
{
  return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function configOAuth()
{
  $archivo = __DIR__ . '/../connections/oauth.php';
  return is_file($archivo) ? require $archivo : [];
}

function proveedorActivo($proveedor)
{
  $c = configOAuth()[$proveedor] ?? [];
  return !empty($c['client_id']) && !empty($c['client_secret']);
}

function buscarUsuario($db, $campo, $valor)
{
  $permitidos = ['id_user', 'email', 'usuario', 'google_id', 'microsoft_id', 'facebook_id'];
  if (!in_array($campo, $permitidos, true)) return null;
  $st = $db->prepare("SELECT * FROM usuarios WHERE $campo = ? ORDER BY (baja IS NOT NULL) LIMIT 1");
  $st->bind_param('s', $valor);
  $st->execute();
  return $st->get_result()->fetch_assoc();
}

// Guarda en sesión solo lo necesario (nunca claves)
function iniciarSesion($db, $usuario)
{
  if (!$usuario || !empty($usuario['baja'])) return null; // dado de baja: no entra
  session_regenerate_id(true);
  $_SESSION['datos'] = [
    'id_user' => (int)$usuario['id_user'],
    'usuario' => $usuario['usuario'],
    'email' => $usuario['email'] ?? null,
    'nombre' => trim(($usuario['nombre'] ?? '') . ' ' . ($usuario['apellido'] ?? '')),
    'rol' => $usuario['rol'] === ROL_ADMIN ? ROL_ADMIN : ROL_USUARIO,
    'id_cliente' => $usuario['id_cliente'] ?? null,
    'avatar' => !empty($usuario['foto']) ? 'uploads/' . $usuario['foto'] : ($usuario['avatar'] ?? null),
  ];
  $st = $db->prepare("UPDATE usuarios SET ultimo_login = NOW() WHERE id_user = ?");
  $id = (int)$usuario['id_user'];
  $st->bind_param('i', $id);
  $st->execute();
  return $_SESSION['datos'];
}

// Además del rol guardado en la sesión, confirma en la base que la cuenta sigue activa y sigue siendo admin:
// si la deshabilitan o le quitan el rol, pierde el acceso en el próximo pedido (no hace falta que cierre sesión).
function esAdmin()
{
  global $conexionApi;
  static $resultado = null;
  if ($resultado !== null) return $resultado;
  if (($_SESSION['datos']['rol'] ?? '') !== ROL_ADMIN) return $resultado = false;
  $id = (int)$_SESSION['datos']['id_user'];
  $st = $conexionApi->prepare("SELECT 1 FROM usuarios WHERE id_user = ? AND rol = 'admin' AND baja IS NULL");
  $st->bind_param('i', $id);
  $st->execute();
  $resultado = (bool)$st->get_result()->fetch_row();
  if (!$resultado) $_SESSION = []; // la sesión ya no corresponde: se cierra
  return $resultado;
}

function destinoSegunRol($rol)
{
  return $rol === ROL_ADMIN ? 'index.php' : 'mi-garage.php';
}

/* ---------- Límite de intentos ---------- */
function contarIntentos($db, $campo, $valor)
{
  $ventana = VENTANA_INTENTOS_MIN;
  $sql = $campo === 'ip'
    ? "SELECT COUNT(*) FROM login_intentos WHERE ip = ? AND identificador NOT LIKE 'accion:%' AND creado > NOW() - INTERVAL ? MINUTE"
    : "SELECT COUNT(*) FROM login_intentos WHERE identificador = ? AND creado > NOW() - INTERVAL ? MINUTE";
  $st = $db->prepare($sql);
  $st->bind_param('si', $valor, $ventana);
  $st->execute();
  return (int)$st->get_result()->fetch_row()[0];
}

// Ingreso: bloquea una cuenta tras varios fallos, o una IP con muchísimos fallos
function demasiadosIntentos($db, $identificador)
{
  return contarIntentos($db, 'identificador', $identificador) >= MAX_INTENTOS
    || contarIntentos($db, 'ip', ipCliente()) >= MAX_INTENTOS_IP;
}

// Registro / recuperación: límite propio por IP, separado de los ingresos fallidos
function demasiadasAcciones($db, $accion)
{
  return contarIntentos($db, 'identificador', "accion:$accion:" . ipCliente()) >= MAX_ACCIONES_IP;
}

function registrarAccion($db, $accion)
{
  registrarIntentoFallido($db, "accion:$accion:" . ipCliente());
}

function registrarIntentoFallido($db, $identificador)
{
  $ip = ipCliente();
  $st = $db->prepare("INSERT INTO login_intentos (ip, identificador) VALUES (?, ?)");
  $st->bind_param('ss', $ip, $identificador);
  $st->execute();
  // limpieza de registros viejos
  $db->query("DELETE FROM login_intentos WHERE creado < NOW() - INTERVAL 1 DAY");
}

function limpiarIntentos($db, $identificador)
{
  $st = $db->prepare("DELETE FROM login_intentos WHERE identificador = ?");
  $st->bind_param('s', $identificador);
  $st->execute();
}

/* ---------- Tokens (verificación de mail / recuperar contraseña) ---------- */
function crearToken($db, $idUser, $tipo, $horas)
{
  $token = bin2hex(random_bytes(32));
  $hash = hash('sha256', $token);
  $st = $db->prepare("UPDATE usuarios_tokens SET usado = 1 WHERE id_user = ? AND tipo = ? AND usado = 0");
  $st->bind_param('is', $idUser, $tipo);
  $st->execute();
  $st = $db->prepare("INSERT INTO usuarios_tokens (id_user, tipo, token_hash, expira) VALUES (?, ?, ?, NOW() + INTERVAL ? HOUR)");
  $st->bind_param('issi', $idUser, $tipo, $hash, $horas);
  $st->execute();
  return $token;
}

// Devuelve el id_user si el token es válido y lo marca como usado
function consumirToken($db, $token, $tipo)
{
  if (!preg_match('/^[a-f0-9]{64}$/', $token)) return null;
  $hash = hash('sha256', $token);
  $st = $db->prepare("SELECT id_token, id_user FROM usuarios_tokens
    WHERE token_hash = ? AND tipo = ? AND usado = 0 AND expira > NOW()");
  $st->bind_param('ss', $hash, $tipo);
  $st->execute();
  $fila = $st->get_result()->fetch_assoc();
  if (!$fila) return null;
  $st = $db->prepare("UPDATE usuarios_tokens SET usado = 1 WHERE id_token = ?");
  $st->bind_param('i', $fila['id_token']);
  $st->execute();
  return (int)$fila['id_user'];
}

/* ---------- Mails ---------- */
function enviarMail($para, $asunto, $titulo, $texto, $boton = null, $url = null)
{
  $h = fn($v) => htmlspecialchars($v, ENT_QUOTES, 'UTF-8');
  $btn = $boton && $url
    ? "<p style='margin:28px 0'><a href='{$h($url)}' style='background:#e3262c;color:#fff;padding:12px 24px;border-radius:999px;text-decoration:none;font-weight:bold'>{$h($boton)}</a></p>
       <p style='font-size:12px;color:#777'>{$h(t('Si el botón no funciona, copiá este enlace:'))}<br>{$h($url)}</p>"
    : '';
  $html = "<!DOCTYPE html><html lang='" . idiomaActual() . "'><body style='margin:0;background:#f2f2f2;font-family:Arial,sans-serif'>
    <div style='max-width:520px;margin:24px auto;background:#fff;border-radius:10px;overflow:hidden'>
      <div style='background:#121416;border-bottom:4px solid #e3262c;padding:16px 24px;color:#fff;font-weight:bold;font-size:18px'>SYMON SPORT</div>
      <div style='padding:24px;color:#222;line-height:1.5'>
        <h2 style='margin-top:0'>{$h($titulo)}</h2>
        <p>{$texto}</p>
        $btn
      </div>
    </div></body></html>";
  $headers = "MIME-Version: 1.0\r\nContent-Type: text/html; charset=utf-8\r\nFrom: Symon Sport <no-replay@symonsport.com.ar>";
  return @mail($para, '=?UTF-8?B?' . base64_encode($asunto) . '?=', $html, $headers);
}

// en la PC de desarrollo no hay servidor de mail: los links se muestran en pantalla para probar
function esLocal()
{
  return (bool)preg_match('/^(localhost|127\.0\.0\.1)(:\d+)?$/', $_SERVER['HTTP_HOST'] ?? '');
}

// Devuelve el link (para mostrarlo solo en local)
function enviarVerificacion($db, $usuario)
{
  $token = crearToken($db, (int)$usuario['id_user'], 'verificar', 48);
  $url = urlSitio() . '/php/auth/verificar.php?t=' . $token;
  enviarMail($usuario['email'], t('Confirmá tu mail - Symon Sport'), t('Confirmá tu mail'),
    e(t('Gracias por registrarte. Para activar tu cuenta confirmá tu dirección de mail. El enlace vence en 48 horas.')),
    t('Confirmar mail'), $url);
  return $url;
}

// Usuario creado desde el admin para un cliente: le llega un link para elegir su contraseña
// (al mail del usuario o, si no tiene, al de la ficha del cliente). Devuelve [mail, link] o null.
function enviarAccesoCliente($db, $idUser)
{
  $st = $db->prepare("SELECT u.id_user, u.usuario, u.email, c.mail, c.empresa_nyp
    FROM usuarios u LEFT JOIN clientes c ON c.id_cliente = u.id_cliente
    WHERE u.id_user = ? AND u.baja IS NULL");
  $st->bind_param('i', $idUser);
  $st->execute();
  $u = $st->get_result()->fetch_assoc();
  $destino = $u ? ($u['email'] ?: $u['mail']) : null;
  if (!$destino || !filter_var($destino, FILTER_VALIDATE_EMAIL)) return null;

  $token = crearToken($db, (int)$u['id_user'], 'recuperar', 72);
  $url = urlSitio() . '/restablecer.php?t=' . $token;
  $h = fn($v) => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
  $para = $u['empresa_nyp'] ? " para {$h($u['empresa_nyp'])}" : '';
  enviarMail($destino, 'Tu acceso a Symon Sport', 'Tu acceso a Symon Sport',
    "Se creó tu acceso{$para}. Tu usuario es <b>{$h($u['usuario'])}</b>. "
    . 'Elegí tu contraseña con el botón (el enlace vence en 72 horas).', 'Elegir contraseña', $url);
  return [$destino, $url];
}

/* ---------- Validaciones ---------- */
function claveValida($clave)
{
  return mb_strlen($clave) >= 8 && preg_match('/[A-Za-z]/', $clave) && preg_match('/\d/', $clave);
}
