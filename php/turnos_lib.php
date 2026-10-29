<?php
// Funciones compartidas del sistema de turnos
require_once __DIR__ . "/../includes/bootstrap.php"; // sesión segura, cabeceras y CSRF
require_once __DIR__ . "/connections/conexion.php";

$conexionApi->set_charset('utf8mb4');
header('Content-Type: application/json; charset=utf-8');

function responder($datos, $codigo = 200)
{
  http_response_code($codigo);
  echo json_encode($datos, JSON_UNESCAPED_UNICODE);
  exit;
}

function turnosConfig($db)
{
  $config = [
    'intervalo_min' => 30,
    'capacidad' => 1,
    'dias_max' => 30,
    'anticipacion_min_horas' => 2,
    'email_aviso' => '',
  ];
  $res = $db->query("SELECT clave, valor FROM turnos_config");
  while ($fila = $res->fetch_assoc()) {
    $config[$fila['clave']] = is_numeric($fila['valor']) ? (int)$fila['valor'] : $fila['valor'];
  }
  $config['intervalo_min'] = max(5, $config['intervalo_min']);
  $config['capacidad'] = max(1, $config['capacidad']);
  $config['dias_max'] = min(30, max(1, $config['dias_max']));
  return $config;
}

// tiempo que reserva un servicio en la agenda: trabajo estimado + margen por imprevistos
function minutosServicio($servicio)
{
  return (int)$servicio['duracion_min'] + (int)$servicio['margen_min'];
}

function buscarServicio($db, $idServicio)
{
  $st = $db->prepare("SELECT id_servicio, nombre, duracion_min, margen_min FROM turnos_servicios WHERE id_servicio = ? AND activo = 1");
  $st->bind_param('i', $idServicio);
  $st->execute();
  return $st->get_result()->fetch_assoc();
}

// "08:30" o "08:30:00" -> minutos desde las 00:00
function aMinutos($hora)
{
  $p = explode(':', $hora);
  return (int)$p[0] * 60 + (int)$p[1];
}

function aHora($minutos)
{
  return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
}

function fechaValida($fecha)
{
  $d = DateTime::createFromFormat('Y-m-d', $fecha);
  return $d && $d->format('Y-m-d') === $fecha;
}

// minutos absolutos (desde 1970) para comparar momentos de distintos días
function minutoAbs($fecha, $minutoDelDia)
{
  return intdiv(strtotime($fecha), 60) + $minutoDelDia;
}

/**
 * Tramos de trabajo de un día (cada $intervalo minutos dentro del horario de atención).
 * Los tramos dentro de un cierre parcial quedan marcados como bloqueados.
 */
function tramosDelDia($db, $fecha, $intervalo)
{
  static $cache = [];
  if (isset($cache[$fecha])) return $cache[$fecha];

  $st = $db->prepare("SELECT hora_desde, hora_hasta, motivo FROM turnos_bloqueos WHERE fecha = ? AND baja IS NULL");
  $st->bind_param('s', $fecha);
  $st->execute();
  $bloqueos = [];
  foreach ($st->get_result()->fetch_all(MYSQLI_ASSOC) as $b) {
    if ($b['hora_desde'] === null || $b['hora_hasta'] === null) {
      return $cache[$fecha] = ['abierto' => false, 'motivo' => $b['motivo'] ?: 'Cerrado', 'tramos' => []];
    }
    $bloqueos[] = [aMinutos($b['hora_desde']), aMinutos($b['hora_hasta'])];
  }

  $diaSemana = (int)date('N', strtotime($fecha));
  $st = $db->prepare("SELECT hora_desde, hora_hasta FROM turnos_horarios WHERE dia_semana = ? AND baja IS NULL ORDER BY hora_desde");
  $st->bind_param('i', $diaSemana);
  $st->execute();
  $franjas = $st->get_result()->fetch_all(MYSQLI_ASSOC);
  if (!$franjas) {
    return $cache[$fecha] = ['abierto' => false, 'motivo' => 'Cerrado', 'tramos' => []];
  }

  $tramos = [];
  foreach ($franjas as $f) {
    $hasta = aMinutos($f['hora_hasta']);
    for ($m = aMinutos($f['hora_desde']); $m + $intervalo <= $hasta; $m += $intervalo) {
      $bloqueado = false;
      foreach ($bloqueos as $b) {
        if ($b[0] < $m + $intervalo && $b[1] > $m) $bloqueado = true;
      }
      $tramos[] = ['m' => $m, 'bloqueado' => $bloqueado];
    }
  }
  return $cache[$fecha] = ['abierto' => true, 'tramos' => $tramos];
}

// Autos que ocupan un lugar en el taller: turnos pendientes/confirmados que todavía no salieron
function ocupaciones($db)
{
  static $ocupaciones = null;
  if ($ocupaciones !== null) return $ocupaciones;

  $res = $db->query("SELECT fecha, hora_inicio, fecha_fin, hora_fin FROM turnos
    WHERE estado IN ('pendiente', 'confirmado') AND fecha_fin >= CURDATE() - INTERVAL 1 DAY");
  $ocupaciones = [];
  while ($t = $res->fetch_assoc()) {
    $ocupaciones[] = [
      minutoAbs($t['fecha'], aMinutos($t['hora_inicio'])),
      minutoAbs($t['fecha_fin'], aMinutos($t['hora_fin'])),
    ];
  }
  return $ocupaciones;
}

/**
 * Reparte un trabajo de $minutos desde ($fecha, $m) en los tramos hábiles siguientes
 * (salta noches, días cerrados y cierres). Devuelve los tramos usados y el momento de salida.
 */
function recorrerTrabajo($db, $fecha, $m, $minutos, $intervalo)
{
  $necesarios = max(1, (int)ceil($minutos / $intervalo));
  $tramos = [];
  $dia = $fecha;
  for ($i = 0; $i < 60 && count($tramos) < $necesarios; $i++) {
    foreach (tramosDelDia($db, $dia, $intervalo)['tramos'] as $t) {
      if (($i === 0 && $t['m'] < $m) || $t['bloqueado']) continue;
      $tramos[] = minutoAbs($dia, $t['m']);
      if (count($tramos) >= $necesarios) break;
    }
    $dia = date('Y-m-d', strtotime("$dia +1 day"));
  }
  if (count($tramos) < $necesarios) return null;

  $fin = (end($tramos) + $intervalo) * 60;
  return ['tramos' => $tramos, 'fecha_fin' => date('Y-m-d', $fin), 'hora_fin' => date('H:i', $fin)];
}

/**
 * Calcula los horarios de ingreso de un día para un servicio.
 * $minutos = horas de trabajo + margen del servicio.
 * Estados: libre (se puede reservar), ocupado, no_alcanza (hay lugar pero no durante todo el trabajo), pasado.
 */
function slotsDelDia($db, $fecha, $minutos, $config)
{
  $intervalo = $config['intervalo_min'];
  $capacidad = $config['capacidad'];

  $dia = tramosDelDia($db, $fecha, $intervalo);
  if (!$dia['abierto']) {
    return ['abierto' => false, 'motivo' => $dia['motivo'], 'slots' => []];
  }

  // un tramo está lleno si la cantidad de autos en el taller llegó a la capacidad
  $ocupaciones = ocupaciones($db);
  $lleno = function ($t) use ($ocupaciones, $intervalo, $capacidad) {
    $cant = 0;
    foreach ($ocupaciones as $o) {
      if ($o[0] < $t + $intervalo && $o[1] > $t) $cant++;
    }
    return $cant >= $capacidad;
  };

  // antes de este minuto del día ya no se puede reservar (anticipación mínima)
  $limite = (new DateTime())->modify('+' . $config['anticipacion_min_horas'] . ' hours');
  $fechaLimite = $limite->format('Y-m-d');
  if ($fechaLimite > $fecha) {
    $minLimite = 24 * 60;
  } elseif ($fechaLimite === $fecha) {
    $minLimite = aMinutos($limite->format('H:i'));
  } else {
    $minLimite = 0;
  }

  // solo los trabajos más largos que la jornada pueden seguir al día siguiente
  $minutosJornada = 0;
  foreach ($dia['tramos'] as $t) {
    if (!$t['bloqueado']) $minutosJornada += $intervalo;
  }
  $variosDias = $minutos > $minutosJornada;

  $slots = [];
  foreach ($dia['tramos'] as $t) {
    $slot = ['hora' => aHora($t['m'])];
    if ($t['bloqueado'] || $lleno(minutoAbs($fecha, $t['m']))) {
      $slot['estado'] = 'ocupado';
    } elseif ($t['m'] < $minLimite) {
      $slot['estado'] = 'pasado';
    } else {
      // el auto tiene que tener lugar durante todo el trabajo, aunque dure varios días
      $trabajo = recorrerTrabajo($db, $fecha, $t['m'], $minutos, $intervalo);
      $slot['estado'] = 'libre';
      if (!$trabajo || (!$variosDias && $trabajo['fecha_fin'] !== $fecha)) {
        $slot['estado'] = 'no_alcanza';
      } else {
        foreach ($trabajo['tramos'] as $abs) {
          if ($lleno($abs)) {
            $slot['estado'] = 'no_alcanza';
            break;
          }
        }
      }
      if ($slot['estado'] === 'libre') {
        $slot['fecha_fin'] = $trabajo['fecha_fin'];
        $slot['hora_fin'] = $trabajo['hora_fin'];
      }
    }
    $slots[] = $slot;
  }

  return ['abierto' => true, 'slots' => $slots];
}
