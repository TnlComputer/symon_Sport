-- Usuarios v2: cuentas protegidas (la del desarrollador / soporte del sitio).
-- Una cuenta protegida no aparece en la pantalla Usuarios y nadie la puede editar ni deshabilitar desde el sitio.
-- Se ejecuta UNA vez en producción. Ejecutar después de sucursales.sql.
ALTER TABLE usuarios
  ADD COLUMN protegido TINYINT(1) NOT NULL DEFAULT 0;

-- Después, marcar la cuenta del desarrollador (completar el mail; no se deja escrito acá porque el repositorio es público):
-- UPDATE usuarios SET protegido = 1 WHERE email = 'MAIL_DEL_DESARROLLADOR';
