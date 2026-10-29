<?php
require __DIR__ . '/includes/layout.php';
requiereAdmin();
paginaInicio([
  'titulo' => 'Clientes | Symon Sport',
  'descripcion' => 'Taller Mecánico en San Martín, reparación integral de vehiculos',
  'pagina' => 'clientes.php',
  'noindex' => true,
  'fuenteTitulos' => true,
]);
?>

  <main class="main" id="contenido">
    <section class="lp-section tn">
      <div class="lp-container">
        <header class="lp-section__head">
          <p class="lp-eyebrow">Administración</p>
          <h1 class="lp-section__title">Clientes</h1>
        </header>

        <div class="cf-aviso" id="cl-aviso" role="status" hidden></div>

        <!-- LISTADO -->
        <section class="tn-card rp-listado" id="cl-listado">
          <div class="rp-barra">
            <label class="rp-buscar"><span class="sr-only">Buscar</span>
              <input type="search" id="cl-q" placeholder="Buscar por nombre, CUIT, celular, mail o patente">
            </label>
            <label class="cf-check rp-check"><input type="checkbox" id="cl-bajas"> Ver dados de baja</label>
            <button type="button" class="btn btn--accent" id="cl-nuevo">+ Nuevo cliente</button>
          </div>
          <p class="cl-cuenta" id="cl-cuenta"></p>
          <div class="ps-lista" id="cl-lista"></div>
        </section>

        <!-- FICHA DEL CLIENTE -->
        <section class="cl-ficha" id="cl-ficha" hidden>
          <div class="ps-editor__cabecera">
            <div>
              <h2 class="tn-card__title cl-ficha__nombre" id="cl-nombre">Nuevo cliente</h2>
              <div class="cl-contactar" id="cl-contactar"></div>
            </div>
            <button type="button" class="btn btn--ghost ag-btn" id="cl-volver">← Volver al listado</button>
          </div>
          <p class="cl-baja-aviso" id="cl-baja-aviso" hidden>Este cliente está dado de baja. Sus datos y su historial
            se conservan. <button type="button" class="au-link" id="cl-reactivar">Reactivarlo</button></p>

          <div class="ps-columnas">
            <div class="ps-principal">
              <!-- VEHÍCULOS -->
              <section class="tn-card" id="cl-sec-vehiculos" hidden>
                <h3 class="ps-paso__titulo">Vehículos</h3>
                <div class="cl-autos" id="cl-autos"></div>
                <details class="ps-nuevo-auto" id="cl-nuevo-auto">
                  <summary>+ Agregar vehículo</summary>
                  <div class="ps-fila cl-form">
                    <label>Patente <input class="ps-input tn-patente" id="cl-auto-patente" maxlength="10"
                        placeholder="AB123CD" autocomplete="off"></label>
                    <label>Marca <input class="ps-input" id="cl-auto-marca" maxlength="40"></label>
                    <label>Modelo <input class="ps-input" id="cl-auto-modelo" maxlength="60"></label>
                    <label>Año <input class="ps-input" id="cl-auto-anio" maxlength="4" inputmode="numeric"></label>
                  </div>
                  <div class="ps-traspaso" id="cl-traspaso" hidden></div>
                  <button type="button" class="btn btn--primary ag-btn" id="cl-auto-agregar">Agregar vehículo</button>
                </details>
                <div class="cl-anteriores" id="cl-anteriores" hidden></div>
              </section>

              <!-- HISTORIAL DEL VEHÍCULO ELEGIDO -->
              <section class="tn-card" id="cl-sec-historial" hidden>
                <div class="cl-historial__cab">
                  <h3 class="ps-paso__titulo">Historial <b class="tn-placa" id="cl-hist-patente"></b></h3>
                  <button type="button" class="au-link" id="cl-hist-cerrar">Cerrar</button>
                </div>
                <p class="cl-titulares" id="cl-titulares"></p>
                <ol class="cl-linea" id="cl-linea"></ol>
              </section>

              <!-- FICHA Y FOTOS DEL VEHÍCULO ELEGIDO -->
              <section class="tn-card" id="cl-sec-vficha" hidden>
                <div class="cl-historial__cab">
                  <h3 class="ps-paso__titulo">Ficha <b class="tn-placa" id="cl-vf-patente"></b></h3>
                  <button type="button" class="au-link" id="cl-vf-cerrar">Cerrar</button>
                </div>
                <div id="cl-vf-fotos"></div>
                <div class="cl-historial__cab cl-vf-datos-cab">
                  <h4 class="ps-etiqueta">Datos del vehículo</h4>
                  <button type="button" class="au-link" id="cl-vf-editar">Completar / editar</button>
                </div>
                <div id="cl-vf-datos"></div>
                <form id="cl-vf-form" hidden novalidate>
                  <div id="cl-vf-campos"></div>
                  <p class="tn-error" id="cl-vf-error" role="alert" hidden></p>
                  <div class="cl-ficha-acciones">
                    <button type="button" class="btn btn--ghost ag-btn" id="cl-vf-cancelar">Cancelar</button>
                    <button type="submit" class="btn btn--accent ag-btn">Guardar ficha</button>
                  </div>
                </form>
              </section>

              <!-- PRESUPUESTOS -->
              <section class="tn-card" id="cl-sec-presupuestos" hidden>
                <div class="cl-historial__cab">
                  <h3 class="ps-paso__titulo">Presupuestos</h3>
                  <a class="btn btn--accent ag-btn" id="cl-presu-nuevo" href="presupuestos.php">+ Nuevo presupuesto</a>
                </div>
                <div class="cl-presus" id="cl-presus"></div>
              </section>
            </div>

            <aside class="ps-lateral">
              <!-- RESUMEN -->
              <section class="tn-card cl-resumen" id="cl-sec-resumen" hidden>
                <dl id="cl-resumen"></dl>
              </section>

              <!-- DATOS -->
              <section class="tn-card">
                <div class="cl-historial__cab">
                  <h3 class="ps-paso__titulo">Datos</h3>
                  <button type="button" class="au-link" id="cl-editar">Editar</button>
                </div>
                <dl class="cl-datos" id="cl-datos"></dl>
                <form class="cl-form" id="cl-form" hidden novalidate>
                  <input type="hidden" name="id_cliente">
                  <label>Nombre o empresa * <input class="ps-input" name="empresa_nyp" maxlength="100" required></label>
                  <div class="ps-fila">
                    <label>CUIT / DNI <input class="ps-input" name="cuit" maxlength="13" inputmode="numeric"></label>
                    <label>Contacto <input class="ps-input" name="contacto" maxlength="60"
                        placeholder="Con quién hablar"></label>
                  </div>
                  <div class="ps-fila">
                    <label>Celular <input class="ps-input" name="celular" type="tel" maxlength="30"></label>
                    <label>Teléfono <input class="ps-input" name="telefono" type="tel" maxlength="30"></label>
                  </div>
                  <label>Mail <input class="ps-input" name="mail" type="email" maxlength="120"></label>
                  <label>Dirección <input class="ps-input" name="direccion" maxlength="150"></label>
                  <div class="ps-fila">
                    <label>Localidad <input class="ps-input" name="localidad" maxlength="60"></label>
                    <label>Provincia <input class="ps-input" name="provincia" maxlength="60"></label>
                    <label>C. postal <input class="ps-input" name="cpost" maxlength="10"></label>
                  </div>
                  <p class="tn-error" id="cl-error" role="alert" hidden></p>
                  <div class="cl-botones">
                    <button type="button" class="btn btn--ghost ag-btn" id="cl-cancelar">Cancelar</button>
                    <button type="submit" class="btn btn--accent ag-btn">Guardar</button>
                  </div>
                </form>
              </section>

              <!-- USUARIOS -->
              <section class="tn-card" id="cl-sec-usuarios" hidden>
                <h3 class="ps-paso__titulo">Acceso a Mi garage</h3>
                <p class="cf-ayuda cl-ayuda">Personas que pueden entrar a la página y ver los autos e historial de
                  este cliente. Ingresan con su mail; la contraseña la eligen con el link que les llega.</p>
                <div class="cl-usuarios" id="cl-usuarios"></div>
                <details class="ps-nuevo-auto" id="cl-nuevo-usuario">
                  <summary>+ Dar acceso a una persona</summary>
                  <form class="cl-form" id="cl-form-usuario" novalidate>
                    <input type="hidden" name="id_user">
                    <div class="ps-fila">
                      <label>Nombre * <input class="ps-input" name="nombre" maxlength="50" required></label>
                      <label>Apellido <input class="ps-input" name="apellido" maxlength="50"></label>
                    </div>
                    <label>Mail * <input class="ps-input" name="email" type="email" maxlength="190" required></label>
                    <label>DNI <input class="ps-input" name="dni" maxlength="20" inputmode="numeric"></label>
                    <label class="cf-check rp-check cl-check"><input type="checkbox" name="enviar_acceso" checked>
                      Enviarle ahora el mail para elegir su contraseña</label>
                    <p class="tn-error" id="cl-error-usuario" role="alert" hidden></p>
                    <div class="cl-botones">
                      <button type="button" class="btn btn--ghost ag-btn" id="cl-usuario-cancelar">Cancelar</button>
                      <button type="submit" class="btn btn--primary ag-btn">Guardar</button>
                    </div>
                  </form>
                </details>
              </section>

              <button type="button" class="cl-dar-baja" id="cl-dar-baja" hidden>Dar de baja al cliente</button>
            </aside>
          </div>
        </section>
      </div>
    </section>
  </main>

<?php paginaFin(['scripts' => ['ficha-vehiculo', 'clientes']]);
