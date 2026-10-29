-- Vehículos de cada usuario ("Mi garage") y turnos vinculados al usuario.
-- Ejecutar UNA vez en cada base, después de turnos.sql y usuarios_auth.sql.
-- Nada se borra: los vehículos se dan de baja (columna baja).

CREATE TABLE IF NOT EXISTS usuarios_vehiculos (
  id_vehiculo_usuario INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_user INT NOT NULL,
  patente VARCHAR(10) NOT NULL,
  marca VARCHAR(60) NULL,
  modelo VARCHAR(80) NULL,
  anio SMALLINT UNSIGNED NULL,
  km INT UNSIGNED NULL,                           -- último kilometraje conocido
  descripcion VARCHAR(100) NULL,                  -- texto libre que cargó el cliente (ej. "Peugeot 208 gris")
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  baja DATETIME NULL,
  PRIMARY KEY (id_vehiculo_usuario),
  UNIQUE KEY uq_user_patente (id_user, patente),
  KEY idx_patente (patente)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- (la columna turnos.id_user ya viene en turnos.sql; en bases que tenían la versión anterior:
--  ALTER TABLE turnos ADD COLUMN id_user INT NULL AFTER id_servicio, ADD KEY idx_user (id_user), ADD KEY idx_patente (patente);)
