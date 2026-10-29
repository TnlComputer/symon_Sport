<?php
require __DIR__ . '/includes/layout.php';
requiereAdmin();
paginaInicio([
  'titulo' => 'Reparaciones | Symon Sport',
  'descripcion' => 'Taller Mecánico en San Martín, reparación integral de vehiculos',
  'pagina' => 'reparaciones.php',
  'noindex' => true,
  'fuenteTitulos' => true,
]);
?>

  <main class="main" id="contenido">
    <section class="lp-section tn">
      <div class="lp-container">
        <header class="lp-section__head">
          <p class="lp-eyebrow">Administración</p>
          <h1 class="lp-section__title">Reparaciones</h1>
        </header>

        <div class="cf-aviso" id="re-aviso" role="status" hidden></div>

        <!-- TABLERO -->
        <section id="re-tablero">
          <div class="rp-barra re-barra">
            <label class="rp-buscar"><span class="sr-only">Buscar</span>
              <input type="search" id="re-q" placeholder="Buscar por patente, cliente, trabajo, factura o número">
            </label>
            <label class="re-dias"><span>Entregados de los últimos</span>
              <select id="re-dias">
                <option value="30">30 días</option>
                <option value="60" selected>60 días</option>
                <option value="180">6 meses</option>
                <option value="365">1 año</option>
                <option value="3650">todos</option>
              </select>
            </label>
            <button type="button" class="btn btn--accent" id="re-ingresar">+ Ingresar vehículo</button>
          </div>

          <div class="re-columnas">
            <section class="re-col re-col--taller">
              <h2 class="re-col__titulo">En el taller <span id="re-n-taller">0</span></h2>
              <div class="re-lista" id="re-taller"></div>
            </section>
            <section class="re-col re-col--listo">
              <h2 class="re-col__titulo">Listo para retirar <span id="re-n-listo">0</span></h2>
              <div class="re-lista" id="re-listo"></div>
            </section>
            <section class="re-col re-col--entregado">
              <h2 class="re-col__titulo">Entregados <span id="re-n-entregado">0</span></h2>
              <div class="re-lista" id="re-entregado"></div>
            </section>
          </div>
        </section>

        <!-- INGRESO DE UN VEHÍCULO -->
        <section class="re-panel" id="re-ingreso" hidden>
          <div class="ps-editor__cabecera">
            <h2 class="tn-card__title">Ingresar un vehículo</h2>
            <button type="button" class="btn btn--ghost ag-btn" data-volver>← Volver al tablero</button>
          </div>
          <div class="ps-columnas">
            <section class="tn-card">
              <h3 class="ps-paso__titulo"><span>1</span> Presupuesto aprobado</h3>
              <div class="ps-elegido" id="re-presu-elegido" hidden>
                <div id="re-presu-texto"></div>
                <button type="button" class="au-link" id="re-presu-cambiar">Cambiar</button>
              </div>
              <div id="re-presu-buscar">
                <input type="search" class="ps-input" id="re-presu-q" placeholder="Buscar por patente, cliente, trabajo o número"
                  autocomplete="off">
                <ul class="ps-resultados" id="re-presu-lista"></ul>
                <p class="fv-ayuda">¿No está? Primero cargá el presupuesto en <a class="cf-link" href="presupuestos.php?nuevo=1">Presupuestos</a>.</p>
              </div>
            </section>
            <form class="tn-card cl-form" id="re-form-ingreso" hidden novalidate>
              <h3 class="ps-paso__titulo"><span>2</span> Ingreso</h3>
              <div class="ps-fila">
                <label>Fecha de ingreso <input type="date" class="ps-input" name="fecha" required></label>
                <label>Kilometraje <input class="ps-input" name="km" inputmode="numeric" autocomplete="off"></label>
              </div>
              <p class="fv-ayuda" id="re-km-ayuda" hidden></p>
              <label>Cómo llega el vehículo
                <textarea class="ps-input" name="observaciones" rows="4"
                  placeholder="Estado, ruidos, golpes, nivel de combustible, objetos que deja el cliente…"></textarea></label>
              <p class="tn-error" id="re-error-ingreso" role="alert" hidden></p>
              <button type="submit" class="btn btn--accent btn--lg">Ingresar al taller</button>
            </form>
          </div>
        </section>

        <!-- DETALLE DE UNA REPARACIÓN -->
        <section class="re-panel" id="re-detalle" hidden>
          <div class="ps-editor__cabecera">
            <div class="mg-detalle__titulo">
              <span class="tn-placa" id="re-d-patente"></span>
              <h2 class="tn-card__title" id="re-d-vehiculo"></h2>
              <span class="ps-chip" id="re-d-estado"></span>
            </div>
            <button type="button" class="btn btn--ghost ag-btn" data-volver>← Volver al tablero</button>
          </div>
          <p class="re-d-sub" id="re-d-sub"></p>

          <div class="ps-columnas">
            <form class="tn-card cl-form" id="re-form" novalidate>
              <h3 class="ps-paso__titulo">Trabajo</h3>
              <p class="re-d-presu" id="re-d-presu"></p>
              <div class="ps-fila">
                <label>Fecha de ingreso <input type="date" class="ps-input" name="fecha_trab" required></label>
                <label>Kilometraje <input class="ps-input" name="km" inputmode="numeric" autocomplete="off"></label>
                <label>Factura <input class="ps-input" name="factura" maxlength="30"></label>
              </div>
              <label>Cómo llegó el vehículo
                <textarea class="ps-input" name="observaciones" rows="3"></textarea></label>
              <label>Trabajo realizado <small class="re-nota">(lo ve el cliente en Mi garage)</small>
                <textarea class="ps-input" name="trabajo_realizado" rows="5"
                  placeholder="Qué se hizo, repuestos cambiados, mediciones, recomendaciones…"></textarea></label>
              <p class="tn-error" id="re-error" role="alert" hidden></p>
              <div class="cl-botones">
                <button type="submit" class="btn btn--primary ag-btn">Guardar</button>
              </div>
            </form>

            <aside class="ps-lateral">
              <section class="tn-card re-entrega" id="re-entrega"></section>
              <div class="re-zona-baja" id="re-zona-baja"></div>
            </aside>
          </div>
        </section>
      </div>
    </section>
  </main>

<?php paginaFin(['scripts' => ['reparaciones']]);
