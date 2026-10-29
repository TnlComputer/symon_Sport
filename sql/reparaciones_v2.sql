-- Reparaciones v2: estados (en el taller -> listo -> entregado), trabajo realizado, cobro y anulación.
-- Se ejecuta UNA vez en producción. Ejecutar después de garage_v2.sql.
-- observaciones = cómo llegó el auto (ingreso); trabajo_realizado = lo que se hizo (lo ve el cliente en Mi garage)
ALTER TABLE reparaciones
  ADD COLUMN trabajo_realizado TEXT NULL,
  ADD COLUMN listo DATETIME NULL,                  -- trabajo terminado: listo para retirar
  ADD COLUMN cobrado DECIMAL(12,2) NULL,           -- lo que se cobró al entregar
  ADD COLUMN aviso_listo DATETIME NULL,            -- cuándo se le avisó al cliente que estaba listo
  ADD COLUMN baja DATETIME NULL,                   -- ingreso anulado (el presupuesto vuelve a pendiente; no se borra)
  ADD KEY idx_patente (patente),
  ADD KEY idx_estado (baja, fecha_entrega);
