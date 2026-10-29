-- ⚠ SOLO PARA LA BASE LOCAL DE PRUEBAS. NO EJECUTAR EN PRODUCCIÓN.
-- Reconstruye las tablas del sistema anterior (clientes, vehiculos, presupuestos, reparaciones)
-- a partir de las columnas que usa el código, para poder probar sin el export real.
-- Cuando llegue el export del hosting, se reemplazan por las tablas reales.

CREATE TABLE IF NOT EXISTS clientes (
  id_cliente INT NOT NULL AUTO_INCREMENT,
  empresa_nyp VARCHAR(100) NOT NULL,
  direccion VARCHAR(150) NULL,
  localidad VARCHAR(60) NULL,
  provincia VARCHAR(60) NULL,
  telefono VARCHAR(30) NULL,
  cuit VARCHAR(13) NULL,
  celular VARCHAR(30) NULL,
  cpost VARCHAR(10) NULL,
  mail VARCHAR(120) NULL,
  contacto VARCHAR(60) NULL,
  PRIMARY KEY (id_cliente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS vehiculos (
  id_vehiculo INT NOT NULL AUTO_INCREMENT,
  id_cliente INT NOT NULL,
  patente VARCHAR(10) NOT NULL,
  marca VARCHAR(40) NULL,
  modelo VARCHAR(60) NULL,
  anio VARCHAR(4) NULL,
  PRIMARY KEY (id_vehiculo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS presupuestos (
  id_presu INT NOT NULL AUTO_INCREMENT,
  fecha_presu DATE NULL,
  descripcion_presu TEXT NULL,
  usado TINYINT NOT NULL DEFAULT 0,
  patente_presu VARCHAR(10) NULL,
  mobra_presu DECIMAL(12,2) NULL,
  repuestos_presu DECIMAL(12,2) NULL,
  total_presu DECIMAL(12,2) NULL,
  senia_presu DECIMAL(12,2) NULL,
  abonado_presu DECIMAL(12,2) NULL,
  dias_presu INT NULL,
  id_cliente INT NULL,
  id_vehiculo INT NULL,
  fec_ini_presu DATE NULL,
  fec_ret_presu DATE NULL,
  PRIMARY KEY (id_presu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS reparaciones (
  id_trabajo INT NOT NULL AUTO_INCREMENT,
  id_cliente INT NULL,
  id_vehiculo INT NULL,
  patente VARCHAR(10) NULL,
  observaciones TEXT NULL,
  fecha_trab DATE NULL,
  fecha_entrega DATE NULL,
  km VARCHAR(10) NULL,
  factura VARCHAR(30) NULL,
  presupuesto INT NULL,
  PRIMARY KEY (id_trabajo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Datos ficticios de prueba: un auto (PRU001AA) que pasó de "Cliente Uno" a "Cliente Dos"
INSERT INTO clientes (empresa_nyp, localidad, celular, mail, contacto) VALUES
  ('Cliente Uno (prueba)', 'San Martín', '1155550001', 'uno@ejemplo.test', 'Uno'),
  ('Cliente Dos (prueba)', 'Caseros', '1155550002', 'dos@ejemplo.test', 'Dos');
INSERT INTO vehiculos (id_cliente, patente, marca, modelo, anio) VALUES
  (1, 'PRU001AA', 'Volkswagen', 'Gol Trend', '2015'),
  (1, 'PRU002BB', 'Ford', 'Ka', '2018');
INSERT INTO presupuestos (fecha_presu, descripcion_presu, usado, patente_presu, mobra_presu, repuestos_presu, total_presu,
  senia_presu, abonado_presu, dias_presu, id_cliente, id_vehiculo, fec_ini_presu) VALUES
  ('2025-03-10', 'Cambio de correa de distribución y bomba de agua', 1, 'PRU001AA', 90000, 120000, 210000, 0, 210000, 10, 1, 1, '2025-03-12');
INSERT INTO reparaciones (id_cliente, id_vehiculo, patente, observaciones, fecha_trab, fecha_entrega, km, factura, presupuesto) VALUES
  (1, 1, 'PRU001AA', 'Correa de distribución, tensor y bomba de agua', '2025-03-12', '2025-03-13', '98500', 'B-0001', 1);
