# Symon Sport — sitio web y sistema del taller

Sitio público y sistema de gestión de **Symon Sport**, taller mecánico integral en San Martín (Buenos Aires).

- **Visitantes:** conocen el taller y sus servicios y piden turno online.
- **Clientes:** con su cuenta ven sus autos, el historial de trabajos, presupuestos y vencimientos (**Mi garage**).
- **Taller (admin):** maneja agenda, clientes, vehículos, presupuestos, reparaciones y catálogo de repuestos.

Producción: <https://symonsport.com.ar>

---

## Índice

1. [Tecnologías y requisitos](#tecnologías-y-requisitos)
2. [Qué hace cada rol](#qué-hace-cada-rol)
3. [Estructura del proyecto](#estructura-del-proyecto)
4. [Cómo está armado](#cómo-está-armado)
5. [Seguridad](#seguridad)
6. [Reglas del negocio](#reglas-del-negocio)
7. [Base de datos](#base-de-datos)
8. [Puesta en marcha en local](#puesta-en-marcha-en-local)
9. [Pase a producción](#pase-a-producción)
10. [Endpoints (API)](#endpoints-api)
11. [Convenciones para seguir trabajando](#convenciones-para-seguir-trabajando)
12. [Pendientes conocidos](#pendientes-conocidos)

---

## Tecnologías y requisitos

| Pieza | Detalle |
|---|---|
| Servidor | PHP **8.0 o superior** (se usa `str_contains`, `str_starts_with`, `match`), probado con 8.3 |
| Extensiones PHP | `mysqli`, `gd` **con soporte WebP** (fotos), `exif` (opcional: orientación de fotos del celular), `openssl` |
| Base de datos | MySQL 8 / MariaDB 10.4+ (`utf8mb4`) |
| Web | Apache con `mod_rewrite` (hosting cPanel). En local, el servidor embebido de PHP con `router.php` |
| Mail | `mail()` de PHP (el del hosting) |
| Front | HTML + CSS + JavaScript sin frameworks ni build. Una sola hoja `css/style.css` |
| Login social | OAuth de Google, Microsoft y Facebook (opcional, cada uno se activa con sus credenciales) |

No hay dependencias de Composer ni de npm: se sube la carpeta tal cual.

---

## Qué hace cada rol

### Visitante (sin sesión)
- **Inicio** con servicios, cómo trabaja el taller, consejos de mantenimiento y ubicación.
- **Turnos online:** elige servicio, día y horario libre según la capacidad del taller.
- **Contacto** por formulario, WhatsApp o teléfono.
- **Registro / ingreso** con mail y contraseña o con Google, Microsoft o Facebook. Incluye recuperación de contraseña.

### Cliente (rol `usuario`)
- **Mi garage** (`mi-garage.php`):
  - **Perfil:** foto, nombre, celular, DNI y cambio de contraseña.
  - **Autos activos:** foto de portada, km, sucursal, si está *en el taller* o *listo para retirar*, avisos de VTV o seguro vencido o por vencer y turno pendiente.
  - **Ficha de cada auto:**
    - hasta **10 fotos** propias;
    - datos de identificación, neumáticos y documentación;
    - actualización de km;
    - presupuestos pendientes;
    - **historial del taller**.
  - **"Lo vendí":** el auto pasa a *Autos que tuve* (chico, al costado, con una cruz) y queda **bloqueado**.
  - Los autos que el taller tiene a nombre de su cliente aparecen solos.
- **Turnos:** pide y cancela sus turnos.

### Taller (rol `admin`)

| Pantalla | Qué hace |
|---|---|
| **Agenda** (`turnos-admin.php`) | Turnos del día y de la semana, confirmación y alta manual |
| **Configuración de turnos** (`turnos-config.php`) | Servicios y duraciones, horarios, capacidad, bloqueos y feriados (importación automática) |
| **Presupuestos** (`presupuestos.php`) | Cliente → sus vehículos → trabajo → repuestos → importes. Calcula lo que ve el cliente y el desglose interno, y envía por mail |
| **Clientes** (`clientes.php`) | Listado con buscador (también por patente) y ficha del cliente (detalle abajo) |
| **Reparaciones** (`reparaciones.php`) | Tablero *En el taller → Listo para retirar → Entregado*. Ingreso desde un presupuesto aprobado (km y estado del auto), trabajo realizado, control de entrega (terminado, lavado, aviso por WhatsApp, cobro y factura) y anulación de ingresos cargados por error |
| **Repuestos** (`repuestos.php`) | Catálogo con costo y % de ganancia, aumentos masivos y precios generales (lavado, recargos) |
| **Usuarios** (`usuarios.php`) | Todas las cuentas (clientes y administradores): habilitar / deshabilitar, editar, rol, cliente vinculado, sucursales que ve, reenviar acceso y desbloquear tras intentos fallidos |

La ficha del cliente en **Clientes** reúne:
- **Sucursales** (empresas con varias sedes): cada vehículo se asigna a una.
- **Vehículos**, agrupados por sucursal, con traspaso de patente entre clientes.
- **Historial por auto**, con los trabajos de dueños anteriores.
- **Ficha técnica completa y fotos** de cada auto.
- **Presupuestos** del cliente.
- **Accesos a Mi garage**: un cliente puede tener varios usuarios, cada uno con **tildes de las sucursales que puede ver**. Alta y reenvío del link para elegir contraseña.
- **Baja lógica** del cliente.

---

## Estructura del proyecto

```
/
├── index.php, turnos.php, contacto.php, login.php, restablecer.php   páginas públicas
├── mi-garage.php                                                      cliente con sesión
├── clientes.php, presupuestos.php, reparaciones.php, repuestos.php,
│   usuarios.php, turnos-admin.php, turnos-config.php                  panel del taller (admin)
├── includes/
│   ├── bootstrap.php     núcleo: sesión segura, cabeceras (CSP, HSTS), CSRF, usuario actual
│   └── layout.php        <head>, menú según el rol y pie (paginaInicio / paginaFin)
├── php/                  API JSON que consume el JavaScript de cada página
│   ├── auth/             login, registro, OAuth, recuperación de clave, verificación de mail
│   ├── connections/      conexión a la base  ← NO está en git (tiene contraseñas)
│   ├── admin_lib.php     base de los PHP de admin (exige rol admin) + traspaso de patente
│   ├── vehiculo_lib.php  ficha técnica (catálogo de campos) y fotos, compartida cliente/admin
│   ├── repuestos_lib.php cálculo de precios y totales de presupuestos
│   ├── turnos_lib.php    disponibilidad y reglas de turnos
│   └── *_api.php, ...    endpoints (ver más abajo)
├── js/                   un archivo por página + scripts.js (común) + ficha-vehiculo.js (compartido)
├── css/style.css         estilos de todo el sitio
├── assets/img/           logos, íconos, favicon
├── uploads/              fotos subidas (no está en git, salvo su .htaccess)
├── sql/                  scripts de creación / migración de la base
├── deploy/               reglas para el .htaccess del hosting y plantilla de conexión
└── router.php            enrutador SOLO para desarrollo local (php -S)
```

---

## Cómo está armado

- **Páginas PHP + layout común.** Cada página llama a `paginaInicio([...])` y `paginaFin([...])` de `includes/layout.php`.
  - El menú se arma **en el servidor según el rol**.
  - Las páginas de admin usan `requiereAdmin()` y las de cliente `requiereSesion()`: a quien no corresponde **no se le envía el contenido**.
- **API JSON.** El JavaScript de cada página llama a `php/*.php` con `fetch`. Las APIs nuevas reciben una `accion` (por GET o POST) y responden JSON. Ante un error devuelven `{ "error": "mensaje para mostrar" }` con un código HTTP 4xx.
- **Sin JavaScript ni estilos dentro del HTML.** Es lo que permite una CSP estricta (`script-src 'self'`). Todo va en `js/` y `css/`.
- **Direcciones viejas.** Las `.html` anteriores redirigen con 301 a su `.php`, así no se pierde lo indexado por Google.
- **Caché de CSS/JS.** Se sirven con `?v=VERSION_ASSETS` (constante en `includes/bootstrap.php`). **Subila cada vez que cambies CSS o JS** para que los navegadores tomen la versión nueva.

---

## Seguridad

| Tema | Cómo se resuelve |
|---|---|
| Sesión | Cookie `HttpOnly`, `SameSite=Lax`, `Secure` con HTTPS, modo estricto, id regenerado al ingresar y **cierre automático tras 2 horas sin uso** (`SESION_INACTIVA_MIN` en `includes/bootstrap.php`) |
| HTML inyectado | Los datos se muestran con `textContent` o creando nodos, nunca armando HTML con datos de la base o del usuario. Además, la CSP bloquea cualquier script inyectado |
| Dependencias | **No hay librerías de terceros** (ni npm, ni Composer, ni jQuery/Bootstrap), así que no hay paquetes que actualizar por vulnerabilidades. Lo que sí hay que mantener al día es **la versión de PHP y de MySQL del hosting** |
| CSRF | Todo POST lleva el token. `js/scripts.js` lo agrega solo a cada `fetch` (cabecera `X-CSRF-Token`) y `bootstrap.php` rechaza el envío si falta |
| Cabeceras | CSP estricta, HSTS, `X-Frame-Options`, `nosniff`, `Referrer-Policy`, `Permissions-Policy` |
| SQL | **Solo consultas preparadas.** Los nombres de columna dinámicos salen siempre de listas fijas del código |
| Contraseñas | `password_hash` / `password_verify`. Las claves viejas en texto plano se encriptan al ingresar o con `php/auth/migrar_claves.php` |
| Intentos | Límite de ingresos fallidos por cuenta y por IP, y de registros o recuperaciones por IP |
| Permisos | Cada API controla el rol en el servidor. `esAdmin()` además **confirma en la base** que la cuenta sigue activa y sigue siendo admin: si la deshabilitan o le quitan el rol, pierde el acceso en el siguiente pedido. El cliente solo ve y toca **sus** autos, y solo los de las sucursales que tiene habilitadas |
| Cuentas protegidas | Una cuenta con `protegido = 1` (la del desarrollador) no aparece en *Usuarios* y nadie la puede editar ni deshabilitar desde el sitio. Para la regla "siempre queda al menos un admin" no cuenta |
| Errores | En el servidor real, PHP no muestra errores en pantalla (irían rutas internas): van al log. En local sí se ven |
| Fotos | Se valida que sea JPG, PNG o WEBP y que pese menos de 8 MB. **Se reconvierte a WebP** con GD (así se descarta cualquier contenido que no sea imagen) y se guarda con nombre aleatorio. `uploads/.htaccess` impide ejecutar código ahí |
| Carpetas internas | `includes/`, `sql/`, `deploy/`, `php/connections/` y archivos ocultos bloqueados por `.htaccess` (y por `router.php` en local) |
| Secretos | `php/connections/` (base y OAuth) **no se sube a git**. Hay plantillas en `deploy/conexion-ejemplo.php` y `php/auth/config-ejemplo.php` |

---

## Reglas del negocio

### Nunca se borra nada: baja lógica
Usuarios, clientes, vehículos, presupuestos, repuestos y fotos **no se eliminan**: se marcan con la columna `baja` (fecha) y se ocultan. Así se conserva el historial y se puede reactivar.

En las fotos, el registro queda pero **el archivo se elimina del disco**, para que una foto quitada no siga publicada.

### La historia va con el auto (por patente)
La ficha técnica, el historial, los km y las fotos se guardan **por patente**, no por cliente.

Cuando una patente pasa a otro cliente (**traspaso**):
- el registro del dueño anterior queda dado de baja;
- se cierra su titularidad en `vehiculos_titulares`, con fecha;
- el historial sigue con el auto.

### Qué ve el cliente en Mi garage
- **El historial del taller solo aparece cuando el taller confirmó que el auto es suyo**, es decir, cuando la patente está en `vehiculos` a nombre de su cliente. Si no, cualquiera podría cargar una patente ajena y ver lo que se le hizo.
- De los **dueños anteriores** ve qué se hizo, la fecha y los km, **sin nombre, observaciones ni importes**.
- Si la patente figura a nombre de **otra persona**, la puede agregar pero **no editarla**. Tiene que pedirle al taller el traspaso.
- **Fotos:** ve las suyas y, si el auto está confirmado, las del taller. Las de otros dueños no.
- **Vendido:** el auto queda en solo lectura (datos, km y fotos). No se puede marcar si tiene un turno pendiente.

### Sucursales y permisos por usuario
- Un cliente (empresa) puede tener **sucursales**, y cada vehículo se asigna a una (o queda *sin sucursal*).
- Un cliente puede tener **varios usuarios**. Cada uno tiene:
  - **"Todas las sucursales"**: ve todo, incluidas las sucursales nuevas y los autos sin sucursal. Es el valor de los usuarios que ya existían, para que nadie pierda acceso.
  - O **tildes por sucursal**: ve solo los autos de esas sucursales. Un usuario nuevo de un cliente con sucursales arranca **sin ver nada** hasta que se le tilde alguna.
- Un usuario no puede llegar a un auto de otra sucursal por ningún camino: no le aparece, no lo puede abrir por su número y si carga la patente a mano se le avisa que no la tiene habilitada.
- Una sucursal con vehículos no se puede dar de baja: primero se pasan a otra.

### Reparaciones
- Estados: **en el taller** → **listo para retirar** (`listo`) → **entregado** (`fecha_entrega`).
- `observaciones` es cómo llegó el auto; `trabajo_realizado` es lo que se hizo, y **lo ve el cliente** en Mi garage.
- Al entregar se registra lo cobrado y la factura. Si falta lavar, no está terminado o se cobra menos que el saldo, se pide confirmación.
- **Anular un ingreso** (cargado por error) no borra nada: la reparación queda con `baja` y el presupuesto vuelve a pendiente.

### Quién edita cada dato de la ficha
El catálogo de campos está en `camposFicha()` (`php/vehiculo_lib.php`). Define grupo, etiqueta, tipo, validación y quién lo edita. El formulario se arma solo a partir de ese catálogo, tanto en Mi garage como en Clientes.

- **Cliente:** identificación (marca, modelo, versión, año, color, VIN, motor), combustible, caja, medidas y presión de neumáticos, y vencimientos (VTV, seguro, GNC, matafuego).
- **Solo el taller:** lubricentro (aceite, filtros, fluidos), detalles de motor, gomería, batería, frenos y observaciones. El cliente los ve, pero no los cambia.

### Presupuestos: lo que ve el cliente y lo interno
- El cliente ve **repuestos + mano de obra = total**, más la seña y el saldo.
- **Dentro de la mano de obra** van, sin nombrarse, el **recargo** cuando el cliente trae sus repuestos y el **lavado** antes de entregar.
- El desglose interno (mano de obra base, recargo, lavado y ganancia en repuestos) se guarda aparte y solo lo ve el admin.
- Los porcentajes y el precio del lavado se configuran en **Repuestos → Precios generales**.
- Los repuestos guardan el **costo del momento**, así un aumento posterior no cambia presupuestos viejos.

---

## Base de datos

Los scripts están en `sql/`. **Orden de ejecución en una base nueva o en producción:**

| # | Script | Qué agrega |
|---|---|---|
| 1 | `turnos.sql` | Turnos, servicios, horarios, bloqueos y configuración (instalación completa) |
| 2 | `usuarios_auth.sql` | Login moderno: mail, `clave_hash`, roles, redes sociales, tokens, intentos |
| 3 | `repuestos.sql` | Catálogo con costo y % de ganancia, `taller_config` e ítems de presupuesto |
| 4 | `garage.sql` | `usuarios_vehiculos` (autos del cliente) |
| 5 | `fichas.sql` | Ficha técnica por patente, plan de mantenimiento, historial, neumáticos y km |
| 6 | `presupuestos_v2.sql` | Desglose interno, baja de presupuestos y vehículos, titulares |
| 7 | `clientes_v2.sql` | Baja lógica de clientes |
| 8 | `garage_v2.sql` | Celular y foto de perfil, `vendido`, portada y galería `vehiculos_fotos` |
| 9 | `reparaciones_v2.sql` | Estados de la reparación (`listo`), trabajo realizado, cobro, aviso al cliente y anulación |
| 10 | `sucursales.sql` | Sucursales de clientes, sucursal de cada vehículo y qué sucursales ve cada usuario |
| 11 | `usuarios_v2.sql` | Cuentas protegidas (después, marcar la del desarrollador con el `UPDATE` comentado en el script) |

> **Atención:**
> - `turnos_v2.sql` era solo para una base local vieja. **No usarlo.**
> - `legado_local.sql` recrea las tablas históricas (`clientes`, `vehiculos`, `presupuestos`, `reparaciones`) con **datos ficticios** para desarrollar sin la base real. **Nunca ejecutarlo en producción.**

**Tablas principales:**
- `clientes` y `vehiculos`: dueños y autos según el taller.
- `clientes_sucursales` y `usuarios_sucursales`: sedes de cada cliente y qué sedes ve cada usuario.
- `vehiculos_titulares`: historial de dueños.
- `usuarios`: cuentas; rol `admin` o `usuario`, vinculadas a un cliente con `id_cliente`.
- `usuarios_vehiculos`: el garage de cada cuenta.
- `fichas_vehiculo`: la ficha técnica, por patente.
- `fichas_km`: lecturas de kilometraje.
- `vehiculos_fotos`: fotos de los autos.
- `presupuestos` y `presupuestos_items`.
- `reparaciones`.
- `repuestos` y `taller_config`.
- `turnos_*`.

---

## Puesta en marcha en local

Probado con **Laragon** (PHP 8.3 + MySQL 8) en Windows.

1. **Base de datos:** crear una base vacía (por ejemplo `tnlcom55_ssp`). Después:
   - Para trabajar sin la base real, ejecutar primero `sql/legado_local.sql` (crea las tablas viejas con datos ficticios).
   - Luego ejecutar los scripts en el orden de la tabla de arriba.
2. **Conexión:** copiar `deploy/conexion-ejemplo.php` a `php/connections/conexion.php`.
   - Cuando el sitio corre en `localhost`, `127.0.0.1` o `*.test`, ese archivo carga `php/connections/conexion-local.php`. Armalo con los datos de la base local: host `127.0.0.1`, puerto de MySQL, usuario, clave y nombre.
   - Se pueden pisar con las variables de entorno `SSP_DB_PORT`, `SSP_DB_PASS` y `SSP_DB_NAME`.
3. **Levantar el sitio** desde la carpeta del proyecto:
   ```bash
   php -S localhost:8000 router.php
   ```
   y abrir <http://localhost:8000>.
4. **Crear un admin:** registrarse en `login.php` y después, en la base:
   ```sql
   UPDATE usuarios SET rol = 'admin' WHERE email = 'tu-mail@ejemplo.com';
   ```
5. **Mails en local:** no hay servidor de correo. Los links de verificación y de recuperación de contraseña **se muestran en pantalla** como "Prueba local".

---

## Pase a producción

Checklist para cuando se migre el hosting o el dominio:

1. **Backup** de la base de producción antes de tocar nada.
2. Ejecutar los **scripts SQL en orden** (ver [Base de datos](#base-de-datos)).
3. Subir los archivos y crear `php/connections/conexion.php` a partir de `deploy/conexion-ejemplo.php`, con los datos de la base del hosting.
4. **Cambiar la contraseña de la base de producción.** Estuvo publicada en versiones viejas del repositorio. Con un hosting nuevo alcanza con crear la base con una contraseña nueva.
5. **Migrar las contraseñas viejas** (una sola vez, por SSH o con una tarea cron única en cPanel):
   ```bash
   php php/auth/migrar_claves.php
   ```
6. **OAuth (opcional):** copiar `php/auth/config-ejemplo.php` a `php/connections/oauth.php` y completar las credenciales.
   - URL de retorno a registrar en cada proveedor: `https://symonsport.com.ar/php/auth/callback.php`.
   - Un proveedor sin credenciales queda oculto.
7. **`.htaccess`:** agregar las reglas de `deploy/htaccess.txt` **debajo** de las del SSL, sin reemplazar el archivo existente. Bloquean carpetas internas, redirigen `.html` → `.php` y activan compresión y caché.
8. **Carpeta `uploads/`:** tiene que existir, poder escribirse por PHP y conservar su `.htaccess`. PHP necesita **GD con WebP**.
9. **Admins:** dar rol `admin` a las cuentas del taller (ver [Puesta en marcha en local](#puesta-en-marcha-en-local), paso 4) y marcar como `protegido` la cuenta del desarrollador (ver `sql/usuarios_v2.sql`). Desde ahí, el resto de los usuarios se maneja desde la pantalla **Usuarios**.
10. **Agenda → Configuración:** importar feriados (año actual y siguiente), cargar los no laborables propios y revisar horarios, capacidad y duraciones.

---

## Endpoints (API)

Todos responden JSON. Los POST requieren el token CSRF, que `js/scripts.js` agrega solo.

| Archivo | Quién | Acciones principales |
|---|---|---|
| `php/auth/login.php`, `registro.php`, `recuperar.php`, `restablecer.php`, `verificar.php`, `oauth.php`, `callback.php` | público | Ingreso, registro, recuperación de clave, verificación de mail, login social |
| `php/valido.php` / `php/desconecto.php` | sesión | Usuario actual / cerrar sesión (POST) |
| `php/turnos_servicios.php`, `turnos_disponibilidad.php`, `turnos_reservar.php` | público | Servicios, horarios libres, reserva |
| `php/mis_turnos.php` | sesión | Turnos y autos del usuario, cancelar turno |
| `php/garage_api.php` | sesión | `datos`, `auto`, `perfil`, `clave`, `foto_perfil`, `auto_agregar`, `auto_guardar`, `auto_km`, `auto_foto`, `auto_portada`, `auto_vendido`, `auto_quitar` |
| `php/clientes_api.php` | admin | `listar`, `ficha`, `historial`, `guardar`, `baja`/`reactivar`, `vehiculo`, `vehiculo_baja`, `vehiculo_sucursal`, `sucursal`, `sucursal_baja`, `ficha_vehiculo`, `ficha_vehiculo_guardar`, `vehiculo_foto`, `usuario`, `usuario_acceso`, `usuario_baja` |
| `php/reparaciones_api.php` | admin | `listar`, `detalle`, `pendientes`, `ingresar`, `guardar`, `listo`, `lavado`, `avisado`, `entregar`, `reabrir`, `anular` |
| `php/usuarios_api.php` | admin | `listar`, `detalle`, `sucursales`, `clientes`, `guardar`, `baja`/`reactivar`, `acceso`, `desbloquear` |
| `php/presupuestos_api.php` | admin | `listar`, `detalle`, `clientes`, `vehiculos`, `repuestos`, `guardar`, `vehiculo`, `anular`, `lavado_hecho` |
| `php/send_PresuMail.php` | admin | Envía el presupuesto por mail al cliente |
| `php/repuestos_admin.php` | admin | Catálogo: `guardar`, `baja`/`reactivar`, `aumento`, `config` |
| `php/turnos_admin.php`, `turnos_config_admin.php` | admin | Agenda y configuración (servicios, horarios, bloqueos, feriados) |
| `php/send_mail.php` | público | Formulario de contacto |

---

## Convenciones para seguir trabajando

- **Idioma:** código, comentarios, nombres de tablas y columnas, y textos en **español rioplatense**.
- **Nunca `DELETE`:** usar la columna `baja` (ver [Reglas del negocio](#reglas-del-negocio)).
- **Nada de JS o CSS dentro del HTML** (lo bloquea la CSP): todo en `js/` y `css/`.
- **Página nueva:**
  1. Crear `pagina.php` con `paginaInicio()` / `paginaFin(['scripts' => [...]])`.
  2. Si es de admin, llamar a `requiereAdmin()`; si es de cliente, a `requiereSesion()`.
  3. Si va en el menú, sumarla a `MENU` en `includes/layout.php`.
- **API nueva:**
  - Si es de admin, incluir `php/admin_lib.php` (ya exige el rol) y usar `q()` para consultas preparadas.
  - Responder siempre JSON y, ante un error, `{ error }` con un código 4xx.
- **Al cambiar CSS o JS**, subir `VERSION_ASSETS` en `includes/bootstrap.php`.
- **Base de datos:** cada cambio va en un script nuevo en `sql/`, que se suma a la tabla de orden de este README.
- **Estilos:** reutilizar las variables de `:root` (`--ancho-max`, `--lp-*`, `--text-ssp`, etc.) y los componentes existentes (`tn-card`, `ps-*`, `cl-*`, `fv-*`, `mg-*`).

---

## Pendientes conocidos

- **Agenda y Configuración de turnos** mantienen su diseño propio (ya con el estilo nuevo); falta conectar un turno con el ingreso al taller.
- **Mi garage:**
  - el cliente ve los presupuestos pendientes, pero todavía no puede **aprobarlos online**;
  - todavía no puede cargar trabajos hechos en otros talleres.
- **Recordatorios automáticos** de service y vencimientos por mail o WhatsApp: la base ya tiene el plan de mantenimiento (`fichas_mantenimiento`), pero falta la tarea programada que envía los avisos.
- **Idiomas** (español, portugués e inglés) y **modo claro / oscuro**: planificado.

---

Diseño y desarrollo: [TNL Computer](https://tnlcomputer.com.ar).
