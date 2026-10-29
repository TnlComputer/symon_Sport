-- Mi garage v2: perfil del cliente (celular y foto), autos con galería de fotos y marca de vendido.
-- Se ejecuta UNA vez en producción. Ejecutar después de clientes_v2.sql.
-- Las fotos se guardan en la carpeta uploads/ (debe poder escribirse; uploads/.htaccess impide ejecutar código ahí).
ALTER TABLE usuarios
  ADD COLUMN celular VARCHAR(30) NULL,
  ADD COLUMN foto VARCHAR(80) NULL;               -- archivo en uploads/ (tiene prioridad sobre el avatar de Google/Facebook)

-- vendido: el auto queda en "Autos que tuve" y el usuario ya no puede cambiar sus datos (no se borra)
ALTER TABLE usuarios_vehiculos
  ADD COLUMN foto_portada INT UNSIGNED NULL,       -- id de vehiculos_fotos elegida como portada
  ADD COLUMN vendido DATE NULL;

-- Fotos de cada auto (hasta 10 por quien las sube). Van por patente, como la ficha.
-- Las del cliente solo las ve él (y el taller); las del taller las ve el dueño confirmado.
CREATE TABLE IF NOT EXISTS vehiculos_fotos (
  id_foto INT UNSIGNED NOT NULL AUTO_INCREMENT,
  patente VARCHAR(10) NOT NULL,
  archivo VARCHAR(80) NOT NULL,                    -- nombre aleatorio .webp en uploads/
  subida_por ENUM('cliente', 'taller') NOT NULL,
  id_user INT NOT NULL,                            -- quién la subió
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  baja DATETIME NULL,
  PRIMARY KEY (id_foto),
  KEY idx_patente (patente, baja)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
