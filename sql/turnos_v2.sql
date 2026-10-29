-- Actualización de turnos v2: trabajos de varios días, margen y demoras.
-- Ejecutar SOLO donde ya se había ejecutado turnos.sql anterior (ej. la base local).

ALTER TABLE turnos_servicios
  ADD COLUMN margen_min SMALLINT UNSIGNED NOT NULL DEFAULT 0 AFTER duracion_min;

ALTER TABLE turnos
  ADD COLUMN fecha_fin DATE NULL AFTER hora_inicio,
  ADD COLUMN motivo_demora VARCHAR(255) NULL AFTER comentario,
  ADD COLUMN origen ENUM('web', 'taller') NOT NULL DEFAULT 'web' AFTER estado;

UPDATE turnos SET fecha_fin = fecha WHERE fecha_fin IS NULL;

ALTER TABLE turnos
  MODIFY fecha_fin DATE NOT NULL,
  ADD KEY idx_fin (fecha_fin, estado);

-- máximo 30 días hacia adelante
UPDATE turnos_config SET valor = '30' WHERE clave = 'dias_max';

-- trabajo largo de ejemplo: el auto queda en el taller varios días
INSERT INTO turnos_servicios (nombre, descripcion, duracion_min, margen_min, activo, orden) VALUES
  ('Reparación de motor', 'Requiere diagnóstico previo. El auto queda en el taller.', 1440, 480, 0, 20);
