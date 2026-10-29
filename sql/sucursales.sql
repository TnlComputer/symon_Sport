-- Sucursales de clientes (empresas) y qué sucursales puede ver cada usuario del cliente.
-- Se ejecuta UNA vez en producción. Ejecutar después de reparaciones_v2.sql. Nada se borra: columna baja.

CREATE TABLE IF NOT EXISTS clientes_sucursales (
  id_sucursal INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_cliente INT NOT NULL,
  nombre VARCHAR(80) NOT NULL,
  direccion VARCHAR(150) NULL,
  localidad VARCHAR(60) NULL,
  telefono VARCHAR(30) NULL,
  contacto VARCHAR(60) NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  baja DATETIME NULL,
  PRIMARY KEY (id_sucursal),
  KEY idx_cliente (id_cliente, baja)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- sucursal a la que pertenece cada vehículo (NULL = sin sucursal asignada)
ALTER TABLE vehiculos
  ADD COLUMN id_sucursal INT UNSIGNED NULL,
  ADD KEY idx_sucursal (id_sucursal);

-- 1 = ve todas las sucursales del cliente (también las nuevas y los vehículos sin sucursal).
-- Los usuarios existentes quedan en 1 para no perder acceso; los nuevos de clientes con sucursales arrancan en 0.
ALTER TABLE usuarios
  ADD COLUMN todas_sucursales TINYINT(1) NOT NULL DEFAULT 1;

-- los tildes: qué sucursales puede ver cada usuario (cuando todas_sucursales = 0)
CREATE TABLE IF NOT EXISTS usuarios_sucursales (
  id_user INT NOT NULL,
  id_sucursal INT UNSIGNED NOT NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  baja DATETIME NULL,                              -- tilde quitado (no se borra)
  PRIMARY KEY (id_user, id_sucursal),
  KEY idx_sucursal (id_sucursal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
