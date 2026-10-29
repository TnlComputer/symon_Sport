-- Repuestos v2: proveedores con su lista de precios, rubros con % de ganancia, importación de listas
-- e historial de costos. Se ejecuta UNA vez en cada base. Ejecutar después de garage_v3.sql.
--
-- Ganancia que se aplica a cada repuesto (la primera que exista):
--   la propia del repuesto -> la del rubro -> la del proveedor -> la general (taller_config.ganancia_repuestos_pct)
-- Costo de un repuesto importado = precio de lista x (1 - bonificación %) x (1 + IVA %)

CREATE TABLE IF NOT EXISTS proveedores (
  id_proveedor INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(80) NOT NULL,
  contacto VARCHAR(80) NULL,
  telefono VARCHAR(30) NULL,
  mail VARCHAR(120) NULL,
  ganancia_pct DECIMAL(6,2) NULL,                  -- NULL = usa la general
  bonificacion_pct DECIMAL(5,2) NOT NULL DEFAULT 0, -- descuento sobre el precio de lista
  iva_pct DECIMAL(5,2) NOT NULL DEFAULT 0,         -- se suma si la lista viene sin IVA (ej. 21)
  columnas TEXT NULL,                              -- cómo es su planilla (JSON), para la próxima importación
  notas VARCHAR(255) NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  baja DATETIME NULL,
  PRIMARY KEY (id_proveedor),
  UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rubros (categorías): cada uno puede tener su % de ganancia
CREATE TABLE IF NOT EXISTS repuestos_rubros (
  id_rubro INT UNSIGNED NOT NULL AUTO_INCREMENT,
  nombre VARCHAR(60) NOT NULL,
  ganancia_pct DECIMAL(6,2) NULL,                  -- NULL = usa la del proveedor o la general
  baja DATETIME NULL,
  PRIMARY KEY (id_rubro),
  UNIQUE KEY uq_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cada importación de una lista
CREATE TABLE IF NOT EXISTS repuestos_importaciones (
  id_importacion INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_proveedor INT UNSIGNED NOT NULL,
  archivo VARCHAR(150) NULL,
  filas INT UNSIGNED NOT NULL DEFAULT 0,
  nuevos INT UNSIGNED NOT NULL DEFAULT 0,
  actualizados INT UNSIGNED NOT NULL DEFAULT 0,
  sin_cambio INT UNSIGNED NOT NULL DEFAULT 0,
  bajas INT UNSIGNED NOT NULL DEFAULT 0,           -- los que ya no venían en la lista (si se pidió darlos de baja)
  variacion_pct DECIMAL(7,2) NULL,                 -- aumento promedio de los que cambiaron
  id_user INT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_importacion),
  KEY idx_proveedor (id_proveedor, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Historial de costos: cada cambio (importación, aumento masivo o edición a mano)
CREATE TABLE IF NOT EXISTS repuestos_costos (
  id_cambio INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_repuesto INT UNSIGNED NOT NULL,
  costo_anterior DECIMAL(12,2) NULL,
  costo_nuevo DECIMAL(12,2) NOT NULL,
  origen ENUM('importacion', 'aumento', 'manual') NOT NULL,
  id_importacion INT UNSIGNED NULL,
  detalle VARCHAR(150) NULL,                       -- ej. "Aumento 8% rubro Filtros"
  id_user INT NULL,
  fecha DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id_cambio),
  KEY idx_repuesto (id_repuesto, fecha),
  KEY idx_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Los repuestos pasan a apuntar a su proveedor y rubro.
-- Las columnas de texto proveedor / categoria quedan como estaban (ya no se usan; no se borra nada).
ALTER TABLE repuestos
  ADD COLUMN id_proveedor INT UNSIGNED NULL,
  ADD COLUMN id_rubro INT UNSIGNED NULL,
  ADD COLUMN precio_lista DECIMAL(12,2) NULL,      -- precio en la lista del proveedor (antes de bonificación e IVA)
  ADD COLUMN id_importacion INT UNSIGNED NULL,     -- última importación que lo tocó
  ADD KEY idx_proveedor_codigo (id_proveedor, codigo),
  ADD KEY idx_rubro (id_rubro);

-- Se pasan los proveedores y categorías que ya había
INSERT IGNORE INTO proveedores (nombre)
  SELECT DISTINCT TRIM(proveedor) FROM repuestos WHERE proveedor IS NOT NULL AND TRIM(proveedor) <> '';
INSERT IGNORE INTO repuestos_rubros (nombre)
  SELECT DISTINCT TRIM(categoria) FROM repuestos WHERE categoria IS NOT NULL AND TRIM(categoria) <> '';
UPDATE repuestos r JOIN proveedores p ON p.nombre = TRIM(r.proveedor) SET r.id_proveedor = p.id_proveedor;
UPDATE repuestos r JOIN repuestos_rubros c ON c.nombre = TRIM(r.categoria) SET r.id_rubro = c.id_rubro;
