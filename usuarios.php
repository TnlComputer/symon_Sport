<?php
require __DIR__ . '/includes/layout.php';
requiereAdmin();
paginaInicio([
  'titulo' => 'Usuarios | Symon Sport',
  'descripcion' => 'Cuentas de clientes y administradores',
  'pagina' => 'usuarios.php',
  'noindex' => true,
  'fuenteTitulos' => true,
]);
?>

  <main class="main" id="contenido">
    <section class="lp-section tn">
      <div class="lp-container">
        <header class="lp-section__head">
          <p class="lp-eyebrow">Administración</p>
          <h1 class="lp-section__title">Usuarios</h1>
        </header>

        <div class="cf-aviso" id="us-aviso" role="status" hidden></div>

        <!-- LISTADO -->
        <section class="tn-card rp-listado" id="us-listado">
          <div class="rp-barra">
            <label class="rp-buscar"><span class="sr-only">Buscar</span>
              <input type="search" id="us-q" placeholder="Buscar por nombre, mail, DNI o cliente">
            </label>
            <select id="us-rol" aria-label="Rol">
              <option value="">Todos los roles</option>
              <option value="usuario">Clientes</option>
              <option value="admin">Administradores</option>
            </select>
            <select id="us-estado" aria-label="Estado">
              <option value="activos">Habilitados</option>
              <option value="baja">Deshabilitados</option>
              <option value="todos">Todos</option>
            </select>
            <label class="cf-check rp-check"><input type="checkbox" id="us-sin-cliente"> Sin cliente vinculado</label>
            <button type="button" class="btn btn--accent" id="us-nuevo">+ Nuevo usuario</button>
          </div>
          <p class="cl-cuenta" id="us-cuenta"></p>
          <div class="us-lista" id="us-lista"></div>
        </section>

        <!-- EDICIÓN -->
        <section class="us-editor" id="us-editor" hidden>
          <div class="ps-editor__cabecera">
            <div class="us-editor__quien">
              <span class="us-avatar us-avatar--grande" id="us-e-avatar"></span>
              <div>
                <h2 class="tn-card__title" id="us-e-titulo">Nuevo usuario</h2>
                <div class="ps-card__estado" id="us-e-chips"></div>
              </div>
            </div>
            <button type="button" class="btn btn--ghost ag-btn" id="us-volver">← Volver al listado</button>
          </div>

          <div class="ps-columnas">
            <form class="tn-card cl-form" id="us-form" novalidate>
              <input type="hidden" name="id_user">
              <h3 class="ps-paso__titulo">Datos</h3>
              <div class="ps-fila">
                <label>Nombre * <input class="ps-input" name="nombre" maxlength="50" required></label>
                <label>Apellido <input class="ps-input" name="apellido" maxlength="50"></label>
              </div>
              <label>Mail * (con él ingresa) <input class="ps-input" name="email" type="email" maxlength="190" required></label>
              <div class="ps-fila">
                <label>Celular <input class="ps-input" name="celular" type="tel" maxlength="30"></label>
                <label>DNI <input class="ps-input" name="dni" maxlength="20" inputmode="numeric"></label>
              </div>

              <h3 class="ps-paso__titulo us-subtitulo">Rol</h3>
              <div class="ps-provisto us-rol" role="radiogroup" aria-label="Rol">
                <label class="ps-provisto__op"><input type="radio" name="rol" value="usuario" class="sr-only"> Cliente</label>
                <label class="ps-provisto__op"><input type="radio" name="rol" value="admin" class="sr-only"> Administrador</label>
              </div>
              <p class="fv-ayuda" id="us-rol-ayuda"></p>

              <div id="us-bloque-cliente">
                <h3 class="ps-paso__titulo us-subtitulo">Cliente del taller</h3>
                <input type="hidden" name="id_cliente">
                <div class="ps-elegido" id="us-cliente-elegido" hidden>
                  <strong id="us-cliente-nombre"></strong>
                  <button type="button" class="au-link" id="us-cliente-quitar">Desvincular</button>
                </div>
                <div id="us-cliente-buscar">
                  <input type="search" class="ps-input" id="us-cliente-q" placeholder="Buscar cliente para vincular" autocomplete="off">
                  <ul class="ps-resultados" id="us-cliente-resultados"></ul>
                  <p class="fv-ayuda">Sin cliente vinculado ve solo los autos que cargue él mismo, sin el historial del taller.</p>
                </div>
                <fieldset class="cl-permisos" id="us-permisos" hidden>
                  <legend>Qué sucursales puede ver</legend>
                  <label class="cf-check rp-check cl-check"><input type="checkbox" name="todas_sucursales" value="1">
                    Todas (también las nuevas y los vehículos sin sucursal)</label>
                  <div class="cl-permisos__lista" id="us-sucursales"></div>
                </fieldset>
              </div>

              <label class="cf-check rp-check cl-check" id="us-enviar-label"><input type="checkbox" name="enviar_acceso" checked>
                Enviarle ahora el mail para elegir su contraseña</label>
              <p class="tn-error" id="us-error" role="alert" hidden></p>
              <div class="cl-botones">
                <button type="submit" class="btn btn--accent ag-btn">Guardar</button>
              </div>
            </form>

            <aside class="ps-lateral">
              <section class="tn-card" id="us-cuenta-panel" hidden>
                <h3 class="ps-paso__titulo">Cuenta</h3>
                <dl class="cl-datos" id="us-e-datos"></dl>
                <div class="us-acciones" id="us-e-acciones"></div>
              </section>
            </aside>
          </div>
        </section>
      </div>
    </section>
  </main>

<?php paginaFin(['scripts' => ['usuarios']]);
