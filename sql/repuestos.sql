-- Repuestos: catálogo con costo y % de ganancia, configuración general y detalle por presupuesto.
-- Ejecutar UNA vez en cada base (producción: symonsport_db / local: tnlcom55_ssp).
-- Nada se borra: los repuestos se dan de baja (columna baja).

-- Parámetros generales del taller
CREATE TABLE IF NOT EXISTS taller_config (
  clave VARCHAR(40) NOT NULL,
  valor VARCHAR(100) NOT NULL,
  descripcion VARCHAR(255) NULL,
  PRIMARY KEY (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO taller_config (clave, valor, descripcion) VALUES
  ('ganancia_repuestos_pct', '30', '% de ganancia general sobre el costo de los repuestos'),
  ('recargo_mo_repuesto_cliente_pct', '15', '% de recargo en la mano de obra cuando el cliente trae los repuestos'),
  ('lavado_precio', '0', 'Adicional de lavado antes de entregar (se suma a la mano de obra, sin nombrarlo)'),
  ('lavado_por_defecto', '1', '1 = los presupuestos nuevos incluyen el lavado')
ON DUPLICATE KEY UPDATE clave = clave;

-- Catálogo de repuestos
CREATE TABLE IF NOT EXISTS repuestos (
  id_repuesto INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo VARCHAR(50) NULL,                        -- código del proveedor / fabricante
  descripcion VARCHAR(150) NOT NULL,
  marca VARCHAR(60) NULL,
  categoria VARCHAR(60) NULL,                     -- ej. Filtros, Frenos, Encendido
  proveedor VARCHAR(80) NULL,
  costo DECIMAL(12,2) NOT NULL DEFAULT 0,         -- lo que le cuesta al taller
  ganancia_pct DECIMAL(6,2) NULL,                 -- % propio; NULL = usa el % general
  actualizado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  baja DATETIME NULL,
  PRIMARY KEY (id_repuesto),
  KEY idx_descripcion (descripcion),
  KEY idx_codigo (codigo),
  KEY idx_categoria (categoria)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Detalle de repuestos de cada presupuesto
-- provisto: 'taller' = lo compra Symon Sport (se cobra) / 'cliente' = lo trae el cliente (sin precio, recargo en mano de obra)
CREATE TABLE IF NOT EXISTS presupuestos_items (
  id_item INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_presu INT NOT NULL,
  id_repuesto INT UNSIGNED NULL,                  -- NULL si se cargó a mano, fuera del catálogo
  descripcion VARCHAR(150) NOT NULL,
  cantidad DECIMAL(10,2) NOT NULL DEFAULT 1,
  provisto ENUM('taller', 'cliente') NOT NULL DEFAULT 'taller',
  costo_unit DECIMAL(12,2) NOT NULL DEFAULT 0,    -- se guarda el valor del momento (el catálogo puede cambiar)
  precio_unit DECIMAL(12,2) NOT NULL DEFAULT 0,
  baja DATETIME NULL,
  PRIMARY KEY (id_item),
  KEY idx_presu (id_presu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
