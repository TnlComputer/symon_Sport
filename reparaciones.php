<?php
require __DIR__ . '/includes/layout.php';
requiereAdmin();
paginaInicio([
  'titulo' => 'Reparaciones | Symon Sport',
  'descripcion' => 'Taller Mecánico en San Martín, reparación integral de vehiculos',
  'pagina' => 'reparaciones.php',
  'noindex' => true,
]);
?>

  <main class="main" id="contenido">
    <section class="main-section">
      <h1><span>S</span>YMON <span>S</span>PORT</h1>
      <article class="sec-clientes">
        <h2>REPARACIONES</h2>
      </article>
    </section>

    <section class="tablagen div__reparaciones">
      <article class="fila tit__tabla">
        <div class="col accion">
          <input type="image" class="add__form" src="./assets/img/acciones/action_add.png" alt="Nuevo Reparación">
        </div>
        <div class="col fechaR">Ingreso</div>
        <div class="col fechaR">Entrega</div>
        <div class="col patente tabla__show-48">Patente</div>
        <div class="col marca tabla__show-60">Marca</div>
        <div class="col modelo tabla__show-76">Modelo</div>
        <div class="col km tabla__show-76">KM</div>
        <div class="col presu tabla__show-102">Presupuesto</div>
        <div class="col factu tabla__show-102">Factura</div>
        <div class="col client tabla__show-120">Cliente</div>
      </article>
      <article class="line__tabla"></article>
    </section>
  </main>

  <template id="template__Trabajos">
    <article class="fila ">
      <div class="col accion">
        <input type="image" class="edit__form" src="./assets/img/acciones/reply.gif" alt="Edit Presupuesto">
      </div>
      <div class="col fechaR fecha__t"></div>
      <div class="col fechaR fecha__e"></div>
      <div class="col patente patente__t tabla__show-48"></div>
      <div class="col marca marca__t tabla__show-60"></div>
      <div class="col modelo modelo__t tabla__show-76"></div>
      <div class="col km km__t tabla__show-76"></div>
      <div class="col presu presu__t tabla__show-102"></div>
      <div class="col factu factu__t tabla__show-102"></div>
      <div class="col client client__t tabla__show-120"></div>
    </article>
    <article>
      <div class="col accion"></div>
      <div class="col descript-t">Observaciones</div>
      <div class="col descript-d descript__t"></div>
      <br>
    </article>
  </template>

  <section class="modal">
    <div class="modal__container">
      <h2 class="modal__title">Reparación</h2>
      <form id="form__ARep">
        <label class="modal__item" for="presuAR_slc">Presupuesto</label>
        <select class="modal__input slc-ARpresu" name="presuAR_slc" id="presuAR_slc" required>
        </select>
        <label class="modal__item" for="patente_txt">Patente</label>
        <span class="modal__input mPatente"></span>
        <br>
        <label class="modal__item" for="marca_txt">Marca</label>
        <span class="modal__input mMarca"></span>
        <br>
        <label class="modal__item" for="modelo_txt">Modelo</label>
        <span class="modal__input mModelo"></span>
        <br>
        <label class=" modal__item" for="fecha_txt">Ingreso</label>
        <input class="modal__input" type="date" name="fecha_txt" required>
        <label class="modal__item" for="km_txt">Kilometraje</label>
        <input class="modal__input" type="text" name="km_txt" required>
        <input class="repId_txt" type="hidden" name="repId_txt">
        <label class="modal__item modal__textareaI-item" for=" obs_txt">Observaciones del Vehiculo</label>
        <textarea class="modal__input modal__textarea-input" name="obs_txt" cols="30" rows="5" required></textarea>
        <button class="modal__cancel raclt" type="reset">Cancelar</button>
        <button class="modal__close saclt" type="submit">Aceptar</button>
        <div id="modal__alert"></div>
      </form>
    </div>
  </section>

  <!-- EDITAR REPARACIONES -->
  <section class="modal__modificacion">
    <div class="modal__container">
      <h2 class="modal__title">Reparación</h2>
      <form id="form__ERep">
        <label class="modal__item" for="presuER_slc">Presupuesto</label>
        <input class="modal__input" type="text" name="presuER_slc" readonly>
        <label class="modal__item" for="patente_txt">Patente</label>
        <input class="modal__input" type="text" name="patente_txt" readonly>
        <label class="modal__item" for="marca_txt">Marca</label>
        <input class="modal__input" type="text" name="marca_txt" readonly>
        <label class="modal__item" for="modelo_txt">Modelo</label>
        <input class="modal__input" type="text" name="modelo_txt" readonly>
        <label class=" modal__item" for="fecha_txt">Ingreso</label>
        <input class="modal__input" type="date" name="fecha_txt" required>
        <label class=" modal__item" for="fechaEnt_txt">Egreso</label>
        <input class="modal__input" type="date" name="fechaEnt_txt">
        <label class=" modal__item" for="factura_txt">Factura</label>
        <input class="modal__input" type="text" name="factura_txt">
        <label class="modal__item" for="km_txt">Kilometraje</label>
        <input class="modal__input" type="text" name="km_txt" required>
        <input class="repId_txt" type="hidden" name="repId_txt">
        <label class="modal__item modal__textareaI-item" for=" obs_txt">Observaciones del Vehiculo</label>
        <textarea class="modal__input modal__textarea-input" name="obs_txt" cols="30" rows="5" required></textarea>

        <button class="modal__cancel reclt" type="reset">Cancelar</button>
        <button class="modal__close reclt" type="submit">Aceptar</button>
        <div id="modal__alert"></div>
      </form>
    </div>
  </section>

<?php paginaFin(['scripts' => ['reparaciones']]);
