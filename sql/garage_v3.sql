-- Mi garage v3: el cliente aprueba o rechaza sus presupuestos online.
-- Se ejecuta UNA vez en producción. Ejecutar después de usuarios_v2.sql.
-- Los trabajos de otros talleres que carga el cliente van a fichas_historial (realizado_por = 'otro_taller' o 'cliente'):
-- esa tabla ya tiene todo lo necesario.
-- Las fotos quitadas se mueven a uploads/baja/ (no se publican, pero el archivo se conserva): esa carpeta lleva su .htaccess.

-- Respuesta del cliente. Si el taller modifica el presupuesto, la respuesta se borra (hay que volver a aprobarlo).
ALTER TABLE presupuestos
  ADD COLUMN respuesta_cliente ENUM('aprobado', 'rechazado') NULL,
  ADD COLUMN respuesta_fecha DATETIME NULL,
  ADD COLUMN respuesta_id_user INT NULL,           -- qué usuario de Mi garage respondió
  ADD COLUMN respuesta_motivo VARCHAR(255) NULL;   -- comentario opcional (sobre todo al rechazar)
