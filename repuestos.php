<?php
require __DIR__ . '/includes/layout.php';
requiereAdmin();
paginaInicio([
  'titulo' => 'Repuestos | Symon Sport',
  'descripcion' => 'Reservá tu turno online en Symon Sport, taller mecánico en San Martín. Elegí el servicio y el horario libre.',
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
          <p class="lp-section__lead">Cargá el costo de cada repuesto: el precio al cliente se calcula con el % de
            ganancia.</p>
        </header>

        <div class="cf-aviso" id="rp-aviso" role="status" hidden></div>

        <!-- CONFIGURACIÓN -->
        <div class="rp-dos">
          <form class="tn-card cf-form" id="rp-config">
            <h2 class="tn-card__title">Precios generales</h2>
            <div class="cf-grid rp-grid-1">
              <label>% de ganancia general
                <input type="number" name="ganancia_repuestos_pct" min="0" max="1000" step="0.5" required>
                <small>Se aplica a los repuestos que no tienen un % propio. Ej.: costo $10.000 + 30% = $13.000.</small>
              </label>
              <label>% de recargo en mano de obra si el cliente trae los repuestos
                <input type="number" name="recargo_mo_repuesto_cliente_pct" min="0" max="300" step="0.5" required>
                <small>El repuesto que trae el cliente queda registrado sin precio. El recargo se suma a la mano de obra
                  <b>sin nombrarlo</b>: el cliente no lo ve como recargo.</small>
              </label>
              <label>Adicional de lavado antes de entregar ($)
                <input type="number" name="lavado_precio" min="0" step="1" required>
                <small>Se suma a la mano de obra del presupuesto <b>sin nombrarlo</b>: el cliente no lo ve como
                  lavado. Se puede cambiar o quitar en cada presupuesto.</small>
              </label>
              <label class="cf-check rp-check"><input type="checkbox" name="lavado_por_defecto" value="1"> Incluir el
                lavado en los presupuestos nuevos</label>
            </div>
            <div class="cf-acciones"><button type="submit" class="btn btn--accent">Guardar</button></div>
          </form>

          <form class="tn-card cf-form" id="rp-aumento">
            <h2 class="tn-card__title">Aumento de costos</h2>
            <div class="cf-grid rp-grid-1">
              <label>% de aumento
                <input type="number" name="pct" step="0.5" required placeholder="Ej. 8">
                <small>Usá un número negativo si el proveedor bajó precios.</small>
              </label>
              <label>Solo categoría <select name="categoria" class="rp-sel-categoria">
                  <option value="">Todas</option>
                </select></label>
              <label>Solo proveedor <select name="proveedor" class="rp-sel-proveedor">
                  <option value="">Todos</option>
                </select></label>
            </div>
            <div class="cf-acciones"><button type="submit" class="btn btn--primary">Aplicar aumento</button></div>
          </form>
        </div>

        <!-- LISTADO -->
        <section class="tn-card rp-listado">
          <div class="rp-barra">
            <label class="rp-buscar"><span class="sr-only">Buscar</span>
              <input type="search" id="rp-q" placeholder="Buscar por descripción, código, marca o proveedor">
            </label>
            <select id="rp-filtro-categoria" class="rp-sel-categoria" aria-label="Filtrar por categoría">
              <option value="">Todas las categorías</option>
            </select>
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
              <label>Categoría <input name="categoria" maxlength="60" list="rp-lista-categorias"
                  placeholder="Ej. Filtros"></label>
              <label>Proveedor <input name="proveedor" maxlength="80" list="rp-lista-proveedores"></label>
              <label>Costo ($) <input type="number" name="costo" min="0" step="0.01" required></label>
              <label>% ganancia propio <input type="number" name="ganancia_pct" min="0" max="1000" step="0.5"
                  placeholder="Vacío = general"></label>
              <p class="rp-precio">Precio al cliente: <strong id="rp-precio-vista">$ 0</strong></p>
            </div>
            <datalist id="rp-lista-categorias"></datalist>
            <datalist id="rp-lista-proveedores"></datalist>
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
                  <th>Categoría</th>
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
          <p class="tn-cargando" id="rp-vacio" hidden>No hay repuestos cargados todavía.</p>
        </section>
      </div>
    </section>
  </main>

<?php paginaFin(['scripts' => ['repuestos']]);
