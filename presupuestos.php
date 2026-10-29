<?php
require __DIR__ . '/includes/layout.php';
requiereAdmin();
paginaInicio([
  'titulo' => 'Presupuestos | Symon Sport',
  'descripcion' => 'Taller Mecánico en San Martín, reparación integral de vehiculos',
  'pagina' => 'presupuestos.php',
  'noindex' => true,
  'fuenteTitulos' => true,
]);
?>

  <main class="main" id="contenido">
    <section class="lp-section tn">
      <div class="lp-container">
        <header class="lp-section__head">
          <p class="lp-eyebrow">Administración</p>
          <h1 class="lp-section__title">Presupuestos</h1>
        </header>

        <div class="cf-aviso" id="ps-aviso" role="status" hidden></div>

        <!-- LISTADO -->
        <section class="tn-card rp-listado" id="ps-listado">
          <div class="rp-barra">
            <label class="rp-buscar"><span class="sr-only">Buscar</span>
              <input type="search" id="ps-q" placeholder="Buscar por cliente, patente, trabajo o número">
            </label>
            <button type="button" class="btn btn--accent" id="ps-nuevo">+ Nuevo presupuesto</button>
          </div>
          <div class="ps-lista" id="ps-lista"></div>
        </section>

        <!-- EDITOR -->
        <form class="ps-editor" id="ps-editor" hidden novalidate>
          <input type="hidden" name="id_presu">
          <input type="hidden" name="id_cliente">
          <input type="hidden" name="id_vehiculo">

          <div class="ps-editor__cabecera">
            <h2 class="tn-card__title" id="ps-titulo">Nuevo presupuesto</h2>
            <button type="button" class="btn btn--ghost ag-btn" id="ps-cerrar">← Volver al listado</button>
          </div>

          <div class="ps-columnas">
            <div class="ps-principal">
              <!-- 1. CLIENTE -->
              <section class="tn-card ps-paso">
                <h3 class="ps-paso__titulo"><span>1</span> Cliente</h3>
                <div class="ps-elegido" id="ps-cliente-elegido" hidden>
                  <strong id="ps-cliente-nombre"></strong>
                  <button type="button" class="au-link" id="ps-cliente-cambiar">Cambiar</button>
                </div>
                <div id="ps-cliente-buscar">
                  <input type="search" class="ps-input" id="ps-cliente-q" placeholder="Nombre, CUIT, celular o mail"
                    autocomplete="off">
                  <ul class="ps-resultados" id="ps-cliente-resultados"></ul>
                </div>
              </section>

              <!-- 2. VEHÍCULO -->
              <section class="tn-card ps-paso" id="ps-paso-vehiculo" hidden>
                <h3 class="ps-paso__titulo"><span>2</span> Vehículo</h3>
                <div class="ps-autos" id="ps-autos"></div>
                <details class="ps-nuevo-auto" id="ps-nuevo-auto">
                  <summary>+ Agregar otro vehículo</summary>
                  <div class="ps-fila">
                    <label>Patente <input class="ps-input tn-patente" id="ps-auto-patente" maxlength="10"
                        placeholder="AB123CD"></label>
                    <label>Marca <input class="ps-input" id="ps-auto-marca" maxlength="40"></label>
                    <label>Modelo <input class="ps-input" id="ps-auto-modelo" maxlength="60"></label>
                    <label>Año <input class="ps-input" id="ps-auto-anio" maxlength="4" inputmode="numeric"></label>
                  </div>
                  <div class="ps-traspaso" id="ps-traspaso" hidden></div>
                  <button type="button" class="btn btn--primary ag-btn" id="ps-auto-agregar">Agregar vehículo</button>
                </details>
              </section>

              <!-- 3. TRABAJO -->
              <section class="tn-card ps-paso" id="ps-paso-trabajo" hidden>
                <h3 class="ps-paso__titulo"><span>3</span> Trabajo</h3>
                <div class="ps-fila">
                  <label>Fecha <input type="date" class="ps-input" name="fecha" required></label>
                  <label>Validez (días) <input type="number" class="ps-input" name="dias" min="0" value="10"></label>
                </div>
                <label class="ps-bloque">Detalle del trabajo
                  <textarea class="ps-input" name="descripcion" rows="4" required
                    placeholder="Qué se va a hacer en el vehículo"></textarea>
                </label>
              </section>

              <!-- 4. REPUESTOS -->
              <section class="tn-card ps-paso" id="ps-paso-repuestos" hidden>
                <h3 class="ps-paso__titulo"><span>4</span> Repuestos</h3>
                <div class="ps-buscar-rep">
                  <input type="search" class="ps-input" id="ps-rep-q" placeholder="Buscar en el catálogo de repuestos"
                    autocomplete="off">
                  <ul class="ps-resultados" id="ps-rep-resultados"></ul>
                </div>
                <button type="button" class="au-link" id="ps-rep-manual">+ Agregar un repuesto que no está en el catálogo</button>
                <div class="ps-items" id="ps-items"></div>
              </section>
            </div>

            <!-- TOTALES -->
            <aside class="ps-lateral" id="ps-lateral" hidden>
              <section class="tn-card ps-totales">
                <h3 class="ps-paso__titulo"><span>5</span> Importes</h3>
                <label>Mano de obra ($) <input type="number" class="ps-input" name="mano_obra" min="0" step="100"
                    value="0"></label>
                <label>Lavado antes de entregar ($) <input type="number" class="ps-input" name="lavado" min="0"
                    step="100"></label>
                <label>Seña ($) <input type="number" class="ps-input" name="senia" min="0" step="100" value="0"></label>

                <div class="ps-cliente-ve">
                  <p class="ps-etiqueta">Lo que ve el cliente</p>
                  <dl>
                    <dt>Repuestos</dt><dd id="ps-t-repuestos">$ 0</dd>
                    <dt>Mano de obra</dt><dd id="ps-t-mo">$ 0</dd>
                    <dt class="ps-total">Total</dt><dd class="ps-total" id="ps-t-total">$ 0</dd>
                    <dt>Seña</dt><dd id="ps-t-senia">$ 0</dd>
                    <dt>Saldo</dt><dd id="ps-t-saldo">$ 0</dd>
                  </dl>
                </div>
                <div class="ps-interno">
                  <p class="ps-etiqueta">Desglose interno (no lo ve el cliente)</p>
                  <dl>
                    <dt>Mano de obra base</dt><dd id="ps-i-base">$ 0</dd>
                    <dt>Recargo repuestos del cliente</dt><dd id="ps-i-recargo">$ 0</dd>
                    <dt>Lavado</dt><dd id="ps-i-lavado">$ 0</dd>
                    <dt>Ganancia en repuestos</dt><dd id="ps-i-ganancia">$ 0</dd>
                  </dl>
                </div>
                <p class="tn-error" id="ps-error" role="alert" hidden></p>
                <button type="submit" class="btn btn--accent btn--lg ps-guardar">Guardar presupuesto</button>
                <label class="cf-check rp-check ps-mail"><input type="checkbox" id="ps-enviar-mail" checked> Enviar por
                  mail al cliente (presupuestos nuevos)</label>
              </section>
            </aside>
          </div>
        </form>
      </div>
    </section>
  </main>

<?php paginaFin(['scripts' => ['presupuestos']]);
