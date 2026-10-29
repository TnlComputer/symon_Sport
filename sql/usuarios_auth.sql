-- Login con mail, contraseña encriptada y redes sociales (Google, Microsoft, Facebook).
-- Ejecutar UNA vez en cada base (producción: symonsport_db / local: tnlcom55_ssp).

-- 1) Tabla de usuarios del sistema actual (solo se crea si no existe, ej. en una base local vacía)
CREATE TABLE IF NOT EXISTS usuarios (
  id_user INT NOT NULL AUTO_INCREMENT,
  usuario VARCHAR(50) NOT NULL,
  clave VARCHAR(100) NULL,
  nombre VARCHAR(50) NULL,
  apellido VARCHAR(50) NULL,
  dni VARCHAR(20) NULL,
  nivel VARCHAR(2) NOT NULL DEFAULT '10',
  id_cliente INT NULL,
  PRIMARY KEY (id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2) Columnas nuevas
--    nivel: 99 = administrador, 10 = usuario vinculado a un cliente, 1 = registrado (sin datos de cliente)
ALTER TABLE usuarios
  ADD COLUMN rol ENUM('admin', 'usuario') NOT NULL DEFAULT 'usuario',
  ADD COLUMN email VARCHAR(190) NULL,
  ADD COLUMN clave_hash VARCHAR(255) NULL,
  ADD COLUMN verificado TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN google_id VARCHAR(64) NULL,
  ADD COLUMN microsoft_id VARCHAR(64) NULL,
  ADD COLUMN facebook_id VARCHAR(64) NULL,
  ADD COLUMN avatar VARCHAR(500) NULL,
  ADD COLUMN creado DATETIME NULL DEFAULT CURRENT_TIMESTAMP,
  ADD COLUMN ultimo_login DATETIME NULL,
  ADD COLUMN baja DATETIME NULL,                    -- usuario dado de baja: queda oculto, nunca se borra
  ADD UNIQUE KEY uq_email (email),
  ADD UNIQUE KEY uq_google (google_id),
  ADD UNIQUE KEY uq_microsoft (microsoft_id),
  ADD UNIQUE KEY uq_facebook (facebook_id);

-- la clave vieja (texto plano) pasa a ser opcional: al ingresar se encripta en clave_hash
-- y el usuario se amplía porque las cuentas nuevas usan el mail como usuario
ALTER TABLE usuarios
  MODIFY clave VARCHAR(100) NULL,
  MODIFY usuario VARCHAR(190) NOT NULL;

-- los usuarios que ya existían quedan verificados
UPDATE usuarios SET verificado = 1;

-- los administradores del sistema anterior (nivel 99) pasan a rol admin
UPDATE usuarios SET rol = 'admin' WHERE nivel = '99';

-- 3) Tokens de verificación de mail y de recuperación de contraseña (se guarda solo el hash)
CREATE TABLE IF NOT EXISTS usuarios_tokens (
  id_token INT UNSIGNED NOT NULL AUTO_INCREMENT,
  id_user INT NOT NULL,
  tipo ENUM('verificar', 'recuperar') NOT NULL,
  token_hash CHAR(64) NOT NULL,
  expira DATETIME NOT NULL,
  usado TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (id_token),
  UNIQUE KEY uq_token (token_hash),
  KEY idx_user (id_user)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4) Intentos de login fallidos (para frenar ataques de fuerza bruta)
CREATE TABLE IF NOT EXISTS login_intentos (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  ip VARCHAR(45) NOT NULL,
  identificador VARCHAR(190) NOT NULL,
  creado DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_ip (ip, creado),
  KEY idx_ident (identificador, creado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5) Cargá el mail de tu usuario administrador para poder entrar con Google/Microsoft con ese mail
-- UPDATE usuarios SET email = 'tu@mail.com' WHERE usuario = 'tu_usuario';
