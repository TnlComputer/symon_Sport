-- Presupuestos v2: detalle interno (mano de obra base, recargo, lavado) y vehículos con baja.
-- Se ejecuta UNA vez en producción, sobre las tablas existentes del sistema (presupuestos, vehiculos).
-- Ejecutar después de repuestos.sql.

-- Lo que ve el cliente sigue en mobra_presu / repuestos_presu / total_presu.
-- El desglose interno queda aparte (el recargo y el lavado NO se nombran al cliente).
ALTER TABLE presupuestos
  ADD COLUMN mobra_base_presu DECIMAL(12,2) NULL,      -- mano de obra sin recargo ni lavado
  ADD COLUMN recargo_mo_presu DECIMAL(12,2) NOT NULL DEFAULT 0,  -- recargo porque el cliente trae repuestos
  ADD COLUMN lavado_presu DECIMAL(12,2) NOT NULL DEFAULT 0,      -- adicional de lavado antes de entregar
  ADD COLUMN lavado_hecho DATETIME NULL,               -- cuándo se lavó (control de entrega)
  ADD COLUMN baja DATETIME NULL;                       -- presupuesto anulado (no se borra)

-- Un auto que pasa a otro cliente: el registro del dueño anterior se da de baja (no se borra)
ALTER TABLE vehiculos
  ADD COLUMN baja DATETIME NULL,
  ADD KEY idx_patente (patente);

-- Titulares iniciales: el dueño actual de cada patente (para el historial de dueños)
INSERT INTO vehiculos_titulares (patente, id_cliente, desde)
  SELECT v.patente, v.id_cliente, CURDATE()
  FROM vehiculos v
  WHERE v.baja IS NULL
    AND NOT EXISTS (SELECT 1 FROM vehiculos_titulares t WHERE t.patente = v.patente AND t.hasta IS NULL);
