-- Clientes v2: baja lógica de clientes (no se borran: quedan ocultos con la fecha de baja).
-- Se ejecuta UNA vez en producción. Ejecutar después de presupuestos_v2.sql.
ALTER TABLE clientes
  ADD COLUMN baja DATETIME NULL;
