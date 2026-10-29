<?php
require __DIR__ . '/includes/layout.php';
requiereAdmin();
paginaInicio([
  'titulo' => 'Repuestos | Symon Sport',
  'descripcion' => 'Catálogo de repuestos, listas de precios de proveedores y ganancias.',
  'pagina' => 'repuestos.php',
  'noindex' => true,
  'fuenteTitulos' => true,
]);
?>

  <main class="main" id="contenido">
    <section class="lp-section tn">
      <div class="lp-container">
        <header class="lp-section__head">
          <p class="lp-eyebrow">Administración</p>
          <h1 class="lp-section__title">Repuestos y precios</h1>
          <p class="lp-section__lead">Importá las listas de tus proveedores y definí la ganancia: el precio al cliente se
            calcula solo.</p>
        </header>

        <div class="cf-aviso" id="rp-aviso" role="status" hidden></div>

        <div class="au-tabs rp-tabs" role="tablist">
          <button type="button" class="au-tab is-activo" role="tab" aria-selected="true" data-pestana="catalogo">Catálogo</button>
          <button type="button" class="au-tab" role="tab" aria-selected="false" data-pestana="proveedores">Proveedores y listas</button>
          <button type="button" class="au-tab" role="tab" aria-selected="false" data-pestana="ganancias">Ganancias</button>
          <button type="button" class="au-tab" role="tab" aria-selected="false" data-pestana="aumentos">Aumentos</button>
        </div>

        <!-- ============ CATÁLOGO ============ -->
        <section class="tn-card rp-listado" data-panel="catalogo">
          <div class="rp-barra">
            <label class="rp-buscar"><span class="sr-only">Buscar</span>
              <input type="search" id="rp-q" placeholder="Buscar por descripción, código o marca">
            </label>
            <select id="rp-filtro-rubro" class="rp-sel-rubro" data-primero="Todos los rubros" aria-label="Filtrar por rubro"></select>
            <select id="rp-filtro-proveedor" class="rp-sel-proveedor" data-primero="Todos los proveedores" aria-label="Filtrar por proveedor"></select>
            <label class="cf-check rp-check"><input type="checkbox" id="rp-bajas"> Ver dados de baja</label>
            <button type="button" class="btn btn--accent" id="rp-nuevo">+ Nuevo repuesto</button>
          </div>

          <form class="rp-form" id="rp-form" hidden>
            <input type="hidden" name="id_repuesto">
            <h2 class="tn-card__title" id="rp-form-titulo">Nuevo repuesto</h2>
            <div class="rp-form__grid">
              <label class="rp-ancho">Descripción <input name="descripcion" required maxlength="150"
                  placeholder="Ej. Filtro de aceite"></label>
              <label>Código <input name="codigo" maxlength="50"></label>
              <label>Marca <input name="marca" maxlength="60"></label>
              <label>Rubro <input name="rubro" maxlength="60" list="rp-lista-rubros" placeholder="Ej. Filtros"></label>
              <label>Proveedor <select name="id_proveedor" class="rp-sel-proveedor" data-primero="Sin proveedor"></select></label>
              <label>Costo ($) <input type="number" name="costo" min="0" step="0.01" required></label>
              <label>% ganancia propio <input type="number" name="ganancia_pct" min="0" max="1000" step="0.5"
                  placeholder="Vacío = la del rubro / proveedor / general"></label>
              <p class="rp-precio">Precio al cliente: <strong id="rp-precio-vista">$ 0</strong></p>
            </div>
            <datalist id="rp-lista-rubros"></datalist>
            <div class="cf-acciones rp-form__acciones">
              <button type="button" class="btn btn--ghost" id="rp-cancelar">Cancelar</button>
              <button type="submit" class="btn btn--accent">Guardar</button>
            </div>
          </form>

          <div class="rp-tabla-wrap">
            <table class="rp-tabla">
              <thead>
                <tr>
                  <th>Repuesto</th>
                  <th>Rubro</th>
                  <th>Proveedor</th>
                  <th class="rp-num">Costo</th>
                  <th class="rp-num">Ganancia</th>
                  <th class="rp-num">Precio</th>
                  <th><span class="sr-only">Acciones</span></th>
                </tr>
              </thead>
              <tbody id="rp-cuerpo"></tbody>
            </table>
          </div>
          <p class="tn-cargando" id="rp-vacio" hidden>No hay repuestos con esa búsqueda.</p>
          <p class="cf-ayuda" id="rp-limite" hidden>Se muestran los primeros 500: usá el buscador o los filtros.</p>
        </section>

        <!-- ============ PROVEEDORES Y LISTAS ============ -->
        <div data-panel="proveedores" hidden>
          <section class="tn-card rp-seccion">
            <div class="rp-seccion__cab">
              <h2 class="tn-card__title">Importar una lista de precios</h2>
            </div>
            <p class="cf-ayuda">Subí la planilla del proveedor (Excel .xlsx o .csv). Te mostramos qué cambia antes de aplicarla.
              Los repuestos se reconocen por el código: los que ya estaban se actualizan y los nuevos se agregan.</p>
            <form class="cf-form rp-importar" id="rp-lista-form">
              <div class="rp-fila">
                <label>Proveedor <select name="id_proveedor" class="rp-sel-proveedor" data-primero="Elegí el proveedor" required></select></label>
                <label>Planilla <input type="file" name="archivo" accept=".xlsx,.csv,.txt" required></label>
              </div>
              <div class="cf-acciones"><button type="submit" class="btn btn--accent">Leer la lista</button></div>
            </form>

            <form class="cf-form rp-mapeo" id="rp-mapeo" hidden>
              <p class="rp-mapeo__info" id="rp-mapeo-info"></p>
              <div class="rp-fila rp-fila--columnas" id="rp-mapeo-columnas"></div>
              <div class="rp-fila">
                <label>Fila de títulos <input type="number" name="fila_titulos" min="1" step="1">
                  <small>La fila donde están los nombres de las columnas.</small></label>
                <label>Rubro para los que no traen rubro <select name="id_rubro" class="rp-sel-rubro" data-primero="Sin rubro"></select></label>
              </div>
              <label class="cf-check rp-check"><input type="checkbox" name="bajas_ausentes" value="1"> Dar de baja los repuestos de
                este proveedor que ya no vienen en la lista</label>
              <div class="rp-tabla-wrap rp-muestra"><table class="rp-tabla rp-tabla--muestra" id="rp-muestra"></table></div>
              <div class="rp-resumen" id="rp-resumen" hidden></div>
              <div class="cf-acciones rp-botones">
                <button type="button" class="btn btn--ghost" id="rp-mapeo-cancelar">Cancelar</button>
                <button type="button" class="btn btn--primary" id="rp-simular">Ver qué cambia</button>
                <button type="button" class="btn btn--accent" id="rp-importar" disabled>Importar</button>
              </div>
            </form>
          </section>

          <section class="tn-card rp-seccion">
            <div class="rp-seccion__cab">
              <h2 class="tn-card__title">Proveedores</h2>
              <button type="button" class="btn btn--accent ag-btn" id="rp-prov-nuevo">+ Proveedor</button>
            </div>
            <form class="rp-form" id="rp-prov-form" hidden>
              <input type="hidden" name="id_proveedor">
              <div class="rp-form__grid">
                <label class="rp-ancho">Nombre <input name="nombre" required maxlength="80"></label>
                <label>Contacto <input name="contacto" maxlength="80"></label>
                <label>Teléfono <input name="telefono" maxlength="30"></label>
                <label>Mail <input type="email" name="mail" maxlength="120"></label>
                <label>% de ganancia <input type="number" name="ganancia_pct" min="0" max="1000" step="0.5"
                    placeholder="Vacío = general"></label>
                <label>% de bonificación sobre la lista <input type="number" name="bonificacion_pct" min="0" max="99" step="0.5"
                    placeholder="0"></label>
                <label>% de IVA a sumar <input type="number" name="iva_pct" min="0" max="50" step="0.5" placeholder="0">
                  <small>Si la lista viene sin IVA (ej. 21).</small></label>
                <label class="rp-ancho">Notas <input name="notas" maxlength="255"></label>
              </div>
              <div class="cf-acciones rp-form__acciones">
                <button type="button" class="btn btn--ghost" id="rp-prov-cancelar">Cancelar</button>
                <button type="submit" class="btn btn--accent">Guardar</button>
              </div>
            </form>
            <div class="rp-tarjetas" id="rp-proveedores"></div>
          </section>

          <section class="tn-card rp-seccion">
            <h2 class="tn-card__title">Últimas listas importadas</h2>
            <ul class="rp-historial" id="rp-importaciones"></ul>
          </section>
        </div>

        <!-- ============ GANANCIAS ============ -->
        <div data-panel="ganancias" hidden>
          <section class="tn-card rp-seccion">
            <h2 class="tn-card__title">Cómo se calcula el precio</h2>
            <ol class="rp-orden">
              <li><strong>% propio del repuesto</strong>, si le pusiste uno.</li>
              <li>Si no, el <strong>% del rubro</strong> (Filtros, Frenos…).</li>
              <li>Si no, el <strong>% del proveedor</strong>.</li>
              <li>Si no, el <strong>% general</strong>.</li>
            </ol>
            <p class="cf-ayuda">Cambiar un % actualiza al instante el precio de todos los repuestos que lo usan.</p>
          </section>

          <div class="rp-dos">
            <form class="tn-card cf-form" id="rp-config">
              <h2 class="tn-card__title">Precios generales</h2>
              <div class="cf-grid rp-grid-1">
                <label>% de ganancia general
                  <input type="number" name="ganancia_repuestos_pct" min="0" max="1000" step="0.5" required>
                  <small>Para los repuestos sin % propio, de rubro ni de proveedor. Ej.: costo $10.000 + 30% = $13.000.</small>
                </label>
                <label>% de recargo en mano de obra si el cliente trae los repuestos
                  <input type="number" name="recargo_mo_repuesto_cliente_pct" min="0" max="300" step="0.5" required>
                  <small>El repuesto que trae el cliente queda registrado sin precio. El recargo se suma a la mano de obra
                    <b>sin nombrarlo</b>: el cliente no lo ve como recargo.</small>
                </label>
                <label>Adicional de lavado antes de entregar ($)
                  <input type="number" name="lavado_precio" min="0" step="1" required>
                  <small>Se suma a la mano de obra del presupuesto <b>sin nombrarlo</b>. Se puede cambiar o quitar en cada
                    presupuesto.</small>
                </label>
                <label class="cf-check rp-check"><input type="checkbox" name="lavado_por_defecto" value="1"> Incluir el
                  lavado en los presupuestos nuevos</label>
              </div>
              <div class="cf-acciones"><button type="submit" class="btn btn--accent">Guardar</button></div>
            </form>

            <section class="tn-card">
              <div class="rp-seccion__cab">
                <h2 class="tn-card__title">Ganancia por rubro</h2>
                <button type="button" class="btn btn--ghost ag-btn" id="rp-rubro-nuevo">+ Rubro</button>
              </div>
              <p class="cf-ayuda">Vacío = usa la del proveedor o la general.</p>
              <div class="rp-rubros" id="rp-rubros"></div>
            </section>
          </div>
        </div>

        <!-- ============ AUMENTOS ============ -->
        <div data-panel="aumentos" hidden>
          <form class="tn-card cf-form rp-seccion" id="rp-aumento">
            <h2 class="tn-card__title">Aumento (o baja) de costos</h2>
            <p class="cf-ayuda">Para cuando un proveedor avisa un aumento sin mandar la lista nueva. Se aplica al costo; el precio
              al cliente se recalcula con su ganancia.</p>
            <div class="rp-fila">
              <label>% de aumento <input type="number" name="pct" step="0.5" required placeholder="Ej. 8">
                <small>Negativo si bajaron los precios.</small></label>
              <label>Rubro <select name="id_rubro" class="rp-sel-rubro" data-primero="Todos los rubros"></select></label>
              <label>Proveedor <select name="id_proveedor" class="rp-sel-proveedor" data-primero="Todos los proveedores"></select></label>
            </div>
            <div class="rp-resumen" id="rp-aumento-vista" hidden></div>
            <div class="cf-acciones rp-botones">
              <button type="button" class="btn btn--primary" id="rp-aumento-simular">Ver qué cambia</button>
              <button type="submit" class="btn btn--accent" id="rp-aumento-aplicar" disabled>Aplicar</button>
            </div>
          </form>

          <section class="tn-card rp-seccion">
            <h2 class="tn-card__title">Aumentos aplicados</h2>
            <ul class="rp-historial" id="rp-aumentos"></ul>
          </section>
        </div>
      </div>
    </section>
  </main>

<?php paginaFin(['scripts' => ['repuestos']]);
