-- Ficha técnica completa de cada vehículo (taller mecánico + lubricentro + gomería),
-- plan de mantenimiento, historial, neumáticos y lecturas de kilometraje.
-- Todo se guarda POR PATENTE: la historia acompaña al auto aunque cambie de dueño.
-- Ejecutar UNA vez en cada base, después de garage.sql. Nada se borra: columna baja.

-- =========================================================
-- 1) FICHA DEL VEHÍCULO
-- =========================================================
CREATE TABLE IF NOT EXISTS fichas_vehiculo (
  patente VARCHAR(10) NOT NULL,

  -- identificación
  tipo ENUM('auto', 'camioneta', 'suv', 'utilitario', 'pickup', 'moto', 'camion', 'otro') NULL,
  marca VARCHAR(60) NULL,
  modelo VARCHAR(80) NULL,
  version VARCHAR(80) NULL,                       -- ej. "1.6 Allure"
  anio SMALLINT UNSIGNED NULL,
  color VARCHAR(40) NULL,
  vin VARCHAR(20) NULL,                           -- número de chasis
  numero_motor VARCHAR(30) NULL,

  -- motor y transmisión
  combustible ENUM('nafta', 'diesel', 'gnc', 'nafta_gnc', 'hibrido', 'electrico') NULL,
  cilindrada_cc SMALLINT UNSIGNED NULL,
  turbo TINYINT(1) NULL,
  transmision ENUM('manual', 'automatica', 'cvt', 'doble_embrague') NULL,
  marchas TINYINT UNSIGNED NULL,
  traccion ENUM('delantera', 'trasera', '4x4', 'awd') NULL,
  distribucion ENUM('correa', 'cadena', 'engranajes') NULL,

  -- lubricentro
  aceite_motor_tipo ENUM('mineral', 'semisintetico', 'sintetico') NULL,
  aceite_motor_viscosidad VARCHAR(15) NULL,       -- ej. 5W30
  aceite_motor_norma VARCHAR(40) NULL,            -- ej. API SN / ACEA C3 / VW 504.00
  aceite_motor_litros DECIMAL(4,1) NULL,
  aceite_motor_marca VARCHAR(40) NULL,
  filtro_aceite VARCHAR(50) NULL,                 -- código
  filtro_aire VARCHAR(50) NULL,
  filtro_combustible VARCHAR(50) NULL,
  filtro_habitaculo VARCHAR(50) NULL,
  aceite_caja VARCHAR(60) NULL,                   -- tipo y litros, ej. "75W90 2.2 L" / "ATF Dexron VI"
  refrigerante VARCHAR(60) NULL,                  -- ej. "Orgánico rosa G12 6 L"
  liquido_frenos VARCHAR(20) NULL,                -- ej. DOT 4
  liquido_direccion VARCHAR(40) NULL,             -- vacío si es dirección eléctrica

  -- gomería
  neumatico_medida_del VARCHAR(25) NULL,          -- ej. 205/55 R16 91V
  neumatico_medida_tras VARCHAR(25) NULL,         -- si difiere de la delantera
  presion_del_psi DECIMAL(4,1) NULL,
  presion_tras_psi DECIMAL(4,1) NULL,
  presion_cargado_psi DECIMAL(4,1) NULL,
  llantas ENUM('chapa', 'aleacion') NULL,
  rodado TINYINT UNSIGNED NULL,
  auxilio ENUM('igual', 'temporal', 'kit_reparacion', 'run_flat', 'no_tiene') NULL,
  tuerca_seguridad TINYINT(1) NULL,               -- tiene bulón antirrobo (dónde está la llave)
  torque_ruedas_nm SMALLINT UNSIGNED NULL,

  -- batería y electricidad
  bateria_marca VARCHAR(40) NULL,
  bateria_amperes SMALLINT UNSIGNED NULL,          -- Ah
  bateria_fecha DATE NULL,                         -- colocación

  -- frenos
  frenos_traseros ENUM('disco', 'campana') NULL,
  abs TINYINT(1) NULL,

  -- documentación (vencimientos para avisos)
  vtv_vencimiento DATE NULL,
  seguro_compania VARCHAR(60) NULL,
  seguro_poliza VARCHAR(40) NULL,
  seguro_vencimiento DATE NULL,
  gnc_oblea_vencimiento DATE NULL,
  gnc_prueba_hidraulica DATE NULL,                 -- vencimiento de la PH del cilindro
  matafuego_vencimiento DATE NULL,

  -- kilometraje
  km_actual INT UNSIGNED NULL,
  km_fecha DATE NULL,

  observaciones TEXT NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  baja DATETIME NULL,
  PRIMARY KEY (patente),
  KEY idx_vtv (vtv_vencimiento),
  KEY idx_seguro (seguro_vencimiento)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 2) COMPONENTES DE MANTENIMIENTO (catálogo general)
-- Intervalos ORIENTATIVOS: cada vehículo puede tener los suyos (según manual del fabricante).
-- =========================================================
CREATE TABLE IF NOT EXISTS mantenimiento_componentes (
  id_componente INT UNSIGNED NOT NULL AUTO_INCREMENT,
  categoria ENUM('lubricentro', 'gomeria', 'mecanica', 'frenos', 'electricidad', 'climatizacion', 'documentacion') NOT NULL,
  nombre VARCHAR(80) NOT NULL,
  cada_km INT UNSIGNED NULL,                       -- NULL = no depende de los km
  cada_meses SMALLINT UNSIGNED NULL,               -- NULL = no depende del tiempo
  aviso_previo_km INT UNSIGNED NOT NULL DEFAULT 1000,
  aviso_previo_dias SMALLINT UNSIGNED NOT NULL DEFAULT 15,
  orden SMALLINT NOT NULL DEFAULT 0,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (id_componente),
  UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO mantenimiento_componentes (categoria, nombre, cada_km, cada_meses, orden) VALUES
  -- lubricentro
  ('lubricentro', 'Aceite de motor', 10000, 12, 10),
  ('lubricentro', 'Filtro de aceite', 10000, 12, 11),
  ('lubricentro', 'Filtro de aire', 20000, 24, 12),
  ('lubricentro', 'Filtro de combustible', 30000, 24, 13),
  ('lubricentro', 'Filtro de habitáculo', 15000, 12, 14),
  ('lubricentro', 'Aceite de caja', 60000, 48, 15),
  ('lubricentro', 'Aceite de diferencial', 60000, 48, 16),
  ('lubricentro', 'Líquido refrigerante', 40000, 24, 17),
  ('lubricentro', 'Líquido de frenos', NULL, 24, 18),
  ('lubricentro', 'Líquido de dirección hidráulica', 60000, 48, 19),
  ('lubricentro', 'Engrase', 10000, 12, 20),
  -- gomería
  ('gomeria', 'Control de presión', NULL, 1, 30),
  ('gomeria', 'Rotación de neumáticos', 10000, 12, 31),
  ('gomeria', 'Alineación', 10000, 12, 32),
  ('gomeria', 'Balanceo', 10000, 12, 33),
  ('gomeria', 'Cambio de neumáticos', 50000, 60, 34),
  ('gomeria', 'Control de auxilio', NULL, 6, 35),
  -- mecánica
  ('mecanica', 'Correa de distribución', 60000, 48, 40),
  ('mecanica', 'Bomba de agua', 60000, 48, 41),
  ('mecanica', 'Correa de accesorios', 40000, 36, 42),
  ('mecanica', 'Bujías', 30000, 36, 43),
  ('mecanica', 'Amortiguadores', 60000, 60, 44),
  ('mecanica', 'Tren delantero (bujes y rótulas)', 40000, 36, 45),
  ('mecanica', 'Embrague', 100000, NULL, 46),
  ('mecanica', 'Limpieza de inyectores', 40000, 36, 47),
  -- frenos
  ('frenos', 'Pastillas delanteras', 30000, NULL, 50),
  ('frenos', 'Pastillas / cintas traseras', 50000, NULL, 51),
  ('frenos', 'Discos de freno', 60000, NULL, 52),
  -- electricidad
  ('electricidad', 'Batería', NULL, 36, 60),
  ('electricidad', 'Escobillas', NULL, 12, 61),
  ('electricidad', 'Luces (control)', NULL, 6, 62),
  -- climatización
  ('climatizacion', 'Carga de aire acondicionado', NULL, 24, 70),
  -- documentación
  ('documentacion', 'VTV', NULL, 12, 80),
  ('documentacion', 'Seguro', NULL, 12, 81),
  ('documentacion', 'Oblea GNC', NULL, 12, 82),
  ('documentacion', 'Prueba hidráulica GNC', NULL, 60, 83),
  ('documentacion', 'Matafuego', NULL, 12, 84)
ON DUPLICATE KEY UPDATE nombre = nombre;

-- =========================================================
-- 3) PLAN DE MANTENIMIENTO DE CADA VEHÍCULO (de acá salen los recordatorios)
-- =========================================================
CREATE TABLE IF NOT EXISTS fichas_mantenimiento (
  id_plan INT UNSIGNED NOT NULL AUTO_INCREMENT,
  patente VARCHAR(10) NOT NULL,
  id_componente INT UNSIGNED NOT NULL,
  especificacion VARCHAR(120) NULL,                -- ej. "Gates K015603XS", "5W30 sintético 4 L"
  cada_km INT UNSIGNED NULL,                       -- NULL = usa el del catálogo
  cada_meses SMALLINT UNSIGNED NULL,
  ultimo_km INT UNSIGNED NULL,
  ultima_fecha DATE NULL,
  avisar TINYINT(1) NOT NULL DEFAULT 1,            -- el cliente puede apagar avisos de un componente
  ultimo_aviso DATETIME NULL,
  baja DATETIME NULL,
  PRIMARY KEY (id_plan),
  UNIQUE KEY uq_patente_componente (patente, id_componente),
  KEY idx_componente (id_componente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 4) HISTORIAL: todo lo que se le hizo al vehículo
-- =========================================================
CREATE TABLE IF NOT EXISTS fichas_historial (
  id_historial INT UNSIGNED NOT NULL AUTO_INCREMENT,
  patente VARCHAR(10) NOT NULL,
  fecha DATE NOT NULL,
  km INT UNSIGNED NULL,
  id_componente INT UNSIGNED NULL,                 -- NULL si es un trabajo que no está en el catálogo
  descripcion VARCHAR(255) NOT NULL,
  detalle TEXT NULL,                               -- repuestos usados, mediciones, observaciones del mecánico
  realizado_por ENUM('symon', 'otro_taller', 'cliente') NOT NULL DEFAULT 'symon',
  taller_nombre VARCHAR(80) NULL,                  -- si lo hizo otro taller
  costo DECIMAL(12,2) NULL,
  id_turno INT UNSIGNED NULL,
  id_presu INT NULL,
  id_user_carga INT NULL,                          -- quién lo cargó
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  baja DATETIME NULL,
  PRIMARY KEY (id_historial),
  KEY idx_patente_fecha (patente, fecha),
  KEY idx_componente (id_componente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 5) NEUMÁTICOS POR POSICIÓN (gomería)
-- posición: DI = delantera izquierda, DD, TI, TD, AUX = auxilio. Al cambiar una cubierta,
-- la anterior se da de baja y queda como historia.
-- =========================================================
CREATE TABLE IF NOT EXISTS fichas_neumaticos (
  id_neumatico INT UNSIGNED NOT NULL AUTO_INCREMENT,
  patente VARCHAR(10) NOT NULL,
  posicion ENUM('DI', 'DD', 'TI', 'TD', 'AUX') NOT NULL,
  medida VARCHAR(25) NULL,
  marca VARCHAR(40) NULL,
  modelo VARCHAR(60) NULL,
  dot VARCHAR(4) NULL,                             -- semana y año de fabricación, ej. 2324
  fecha_colocacion DATE NULL,
  km_colocacion INT UNSIGNED NULL,
  profundidad_mm DECIMAL(3,1) NULL,                -- última medición del dibujo (mínimo legal 1,6 mm)
  fecha_medicion DATE NULL,
  observaciones VARCHAR(255) NULL,
  baja DATETIME NULL,
  PRIMARY KEY (id_neumatico),
  KEY idx_patente (patente, posicion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 6) LECTURAS DE KILOMETRAJE (para estimar uso mensual y próximos services)
-- =========================================================
CREATE TABLE IF NOT EXISTS fichas_km (
  id_lectura INT UNSIGNED NOT NULL AUTO_INCREMENT,
  patente VARCHAR(10) NOT NULL,
  km INT UNSIGNED NOT NULL,
  fecha DATE NOT NULL,
  origen ENUM('taller', 'cliente', 'turno') NOT NULL DEFAULT 'taller',
  id_user_carga INT NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  baja DATETIME NULL,
  PRIMARY KEY (id_lectura),
  KEY idx_patente_fecha (patente, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =========================================================
-- 7) TITULARES: quién fue dueño de cada patente y desde cuándo.
-- Cuando el auto pasa a otro cliente, se cierra el titular anterior (hasta) y se abre el nuevo.
-- El historial sigue con la patente: el dueño nuevo ve los trabajos anteriores
-- (sin el nombre ni los importes del dueño anterior).
-- =========================================================
CREATE TABLE IF NOT EXISTS vehiculos_titulares (
  id_titular INT UNSIGNED NOT NULL AUTO_INCREMENT,
  patente VARCHAR(10) NOT NULL,
  id_cliente INT NULL,                             -- cliente del sistema del taller
  id_user INT NULL,                                -- usuario de la página (Mi garage)
  desde DATE NOT NULL,
  hasta DATE NULL,                                 -- NULL = dueño actual
  observaciones VARCHAR(255) NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  baja DATETIME NULL,
  PRIMARY KEY (id_titular),
  KEY idx_patente (patente, hasta),
  KEY idx_cliente (id_cliente),
  KEY idx_user (id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
