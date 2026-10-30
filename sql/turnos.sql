-- Turnos online - Symon Sport
-- Ejecutar una vez en la base (phpMyAdmin > Importar, o HeidiSQL).

-- Servicios que se pueden reservar y cuánto tiempo ocupan
CREATE TABLE IF NOT EXISTS turnos_servicios (
  id_servicio INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255) NULL,
  duracion_min SMALLINT UNSIGNED NOT NULL,           -- horas de trabajo estimadas, en minutos
  margen_min SMALLINT UNSIGNED NOT NULL DEFAULT 0,  -- tiempo extra reservado por imprevistos
  activo TINYINT(1) NOT NULL DEFAULT 1,
  orden SMALLINT NOT NULL DEFAULT 0,
  PRIMARY KEY (id_servicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Horario de atención. Puede haber varias franjas por día (ej. mañana y tarde).
-- dia_semana: 1=lunes ... 7=domingo
CREATE TABLE IF NOT EXISTS turnos_horarios (
  id_horario INT UNSIGNED NOT NULL AUTO_INCREMENT,
  dia_semana TINYINT UNSIGNED NOT NULL,
  hora_desde TIME NOT NULL,
  hora_hasta TIME NOT NULL,
  baja DATETIME NULL,                               -- franja dada de baja (no se borra)
  PRIMARY KEY (id_horario),
  KEY idx_dia (dia_semana)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Feriados, vacaciones o franjas bloqueadas.
-- Si hora_desde/hora_hasta son NULL se bloquea el día completo.
CREATE TABLE IF NOT EXISTS turnos_bloqueos (
  id_bloqueo INT UNSIGNED NOT NULL AUTO_INCREMENT,
  fecha DATE NOT NULL,
  hora_desde TIME NULL,
  hora_hasta TIME NULL,
  motivo VARCHAR(100) NULL,
  baja DATETIME NULL,                               -- cierre quitado (no se borra; tampoco se reimporta)
  PRIMARY KEY (id_bloqueo),
  KEY idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Parámetros generales
CREATE TABLE IF NOT EXISTS turnos_config (
  clave VARCHAR(40) NOT NULL,
  valor VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255) NULL,
  PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Turnos pedidos
CREATE TABLE IF NOT EXISTS turnos (
  id_turno INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_servicio INT UNSIGNED NOT NULL,
  id_user INT NULL,                                 -- usuario que lo pidió con sesión iniciada
  fecha DATE NOT NULL,
  hora_inicio TIME NOT NULL,
  fecha_fin DATE NOT NULL,                          -- salida estimada (puede ser otro día)
  hora_fin TIME NOT NULL,
  nombre VARCHAR(100) NOT NULL,
  telefono VARCHAR(30) NOT NULL,
  email VARCHAR(120) NULL,
  patente VARCHAR(10) NOT NULL,
  vehiculo VARCHAR(100) NULL,
  comentario VARCHAR(500) NULL,
  motivo_demora VARCHAR(255) NULL,                  -- ej. esperando repuesto
  estado ENUM('pendiente', 'confirmado', 'cancelado', 'atendido') NOT NULL DEFAULT 'pendiente',
  origen ENUM('web', 'taller') NOT NULL DEFAULT 'web',
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_turno),
  KEY idx_fecha_estado (fecha, estado),
  KEY idx_fin (fecha_fin, estado),
  KEY idx_user (id_user),
  KEY idx_patente (patente),
  CONSTRAINT fk_turnos_servicio FOREIGN KEY (id_servicio) REFERENCES turnos_servicios (id_servicio)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===== Datos iniciales (ajustar a la realidad del taller) =====

INSERT INTO turnos_config (clave, valor, descripcion) VALUES
  ('intervalo_min', '30', 'Cada cuántos minutos se ofrece un horario'),
  ('capacidad', '3', 'Cuántos vehículos se pueden atender al mismo tiempo'),
  ('dias_max', '30', 'Hasta cuántos días hacia adelante se puede reservar'),
  ('anticipacion_min_horas', '2', 'Mínimo de horas de anticipación para pedir un turno'),
  ('email_aviso', 'anacoretomarcelo72@gmail.com', 'Mail que recibe el aviso de cada turno nuevo')
ON DUPLICATE KEY UPDATE clave = clave;

INSERT INTO turnos_horarios (dia_semana, hora_desde, hora_hasta) VALUES
  (1, '08:00', '12:00'), (1, '13:00', '17:00'),
  (2, '08:00', '12:00'), (2, '13:00', '17:00'),
  (3, '08:00', '12:00'), (3, '13:00', '17:00'),
  (4, '08:00', '12:00'), (4, '13:00', '17:00'),
  (5, '08:00', '12:00'), (5, '13:00', '17:00'),
  (6, '08:00', '12:00');

INSERT INTO turnos_servicios (nombre, descripcion, duracion_min, orden) VALUES
  ('Cambio de aceite y filtros', 'Aceite, filtro de aceite, aire y habitáculo', 60, 1),
  ('Service completo', 'Fluidos, filtros, correas y revisión general', 120, 2),
  ('Diagnóstico electrónico', 'Escaneo de sistemas y lectura de fallas', 60, 3),
  ('Frenos', 'Pastillas, discos y revisión del sistema', 90, 4),
  ('Alineación y balanceo', NULL, 60, 5),
  ('Suspensión / tren delantero', 'Revisión y cambio de amortiguadores, bujes y terminales', 120, 6),
  ('Aire acondicionado', 'Revisión, carga de gas y control de pérdidas', 90, 7),
  ('Limpieza de inyectores', NULL, 120, 8),
  ('Encendido', 'Bujías, bobinas y cables', 60, 9),
  ('Revisión / consulta general', 'Si no sabés qué tiene el auto', 30, 10);

-- trabajo largo: queda desactivado para que no se reserve online sin diagnóstico previo
INSERT INTO turnos_servicios (nombre, descripcion, duracion_min, margen_min, activo, orden) VALUES
  ('Reparación de motor', 'Requiere diagnóstico previo. El auto queda en el taller.', 1440, 480, 0, 20);
