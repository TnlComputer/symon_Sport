<?php
require __DIR__ . '/includes/layout.php';
requiereSesion();
paginaInicio([
  'titulo' => 'Mi garage | Symon Sport',
  'descripcion' => 'Tus autos, su historial en el taller, vencimientos y tu perfil.',
  'pagina' => 'mi-garage.php',
  'noindex' => true,
  'fuenteTitulos' => true,
]);
?>

  <main class="main" id="contenido">
    <section class="lp-section tn">
      <div class="lp-container">
        <header class="lp-section__head">
          <p class="lp-eyebrow" id="mg-hola">Mi garage</p>
          <h1 class="lp-section__title">Mis autos</h1>
        </header>

        <div class="cf-aviso" id="mg-aviso" role="status" hidden></div>

        <div class="mg-columnas">
          <div class="mg-principal">
            <!-- AUTOS ACTIVOS -->
            <section id="mg-garage">
              <div class="mg-autos" id="mg-autos"></div>

              <section class="tn-card mg-agregar" id="mg-agregar" hidden>
                <h2 class="ps-paso__titulo">Agregar un auto</h2>
                <form class="cl-form" id="mg-form-agregar" novalidate>
                  <div class="ps-fila">
                    <label>Patente * <input class="ps-input tn-patente" name="patente" maxlength="10" placeholder="AB123CD"
                        autocomplete="off" required></label>
                    <label>Marca <input class="ps-input" name="marca" maxlength="60"></label>
                    <label>Modelo <input class="ps-input" name="modelo" maxlength="80"></label>
                    <label>Año <input class="ps-input" name="anio" maxlength="4" inputmode="numeric"></label>
                  </div>
                  <p class="tn-error" id="mg-error-agregar" role="alert" hidden></p>
                  <div class="cl-botones">
                    <button type="button" class="btn btn--ghost ag-btn" id="mg-agregar-cancelar">Cancelar</button>
                    <button type="submit" class="btn btn--accent ag-btn">Agregar</button>
                  </div>
                </form>
              </section>
            </section>

            <!-- DETALLE DE UN AUTO -->
            <section class="mg-detalle" id="mg-detalle" hidden>
              <div class="ps-editor__cabecera">
                <div class="mg-detalle__titulo">
                  <span class="tn-placa" id="mg-d-patente"></span>
                  <h2 class="tn-card__title" id="mg-d-nombre"></h2>
                </div>
                <button type="button" class="btn btn--ghost ag-btn" id="mg-volver">← Mis autos</button>
              </div>
              <p class="mg-bloqueo" id="mg-d-bloqueo" hidden></p>
              <div class="mg-acciones" id="mg-d-acciones"></div>

              <section class="tn-card">
                <h3 class="ps-paso__titulo">Fotos</h3>
                <div id="mg-d-fotos"></div>
              </section>

              <section class="tn-card" id="mg-d-sec-vencimientos" hidden>
                <h3 class="ps-paso__titulo">Vencimientos</h3>
                <div class="mg-vencimientos" id="mg-d-vencimientos"></div>
              </section>

              <section class="tn-card">
                <div class="cl-historial__cab">
                  <h3 class="ps-paso__titulo">Datos del auto</h3>
                  <button type="button" class="au-link" id="mg-d-editar">Completar / editar</button>
                </div>
                <div id="mg-d-datos"></div>
                <form id="mg-d-form" hidden novalidate>
                  <div id="mg-d-campos"></div>
                  <label class="fv-campo fv-campo--ancho mg-notas"><span>Notas para vos</span><input class="ps-input"
                      name="descripcion" maxlength="100" placeholder="Ej. el auto de Laura"></label>
                  <p class="tn-error" id="mg-d-error" role="alert" hidden></p>
                  <div class="cl-botones">
                    <button type="button" class="btn btn--ghost ag-btn" id="mg-d-cancelar">Cancelar</button>
                    <button type="submit" class="btn btn--accent ag-btn">Guardar</button>
                  </div>
                </form>
              </section>

              <section class="tn-card" id="mg-d-sec-presupuestos" hidden>
                <h3 class="ps-paso__titulo">Presupuestos para aprobar</h3>
                <div class="mg-presus" id="mg-d-presupuestos"></div>
              </section>

              <section class="tn-card">
                <h3 class="ps-paso__titulo">Historial en el taller</h3>
                <ol class="cl-linea" id="mg-d-historial"></ol>
              </section>

              <div class="mg-zona-baja" id="mg-d-baja"></div>
            </section>
          </div>

          <aside class="mg-lateral">
            <!-- PERFIL -->
            <section class="tn-card mg-perfil">
              <div class="mg-perfil__cab">
                <label class="mg-avatar" title="Cambiar foto">
                  <img id="mg-avatar-img" alt="" hidden>
                  <span id="mg-avatar-iniciales" aria-hidden="true"></span>
                  <input type="file" id="mg-avatar-archivo" accept="image/jpeg,image/png,image/webp" class="sr-only">
                  <span class="mg-avatar__cambiar">Cambiar</span>
                </label>
                <div class="mg-perfil__quien">
                  <strong id="mg-p-nombre"></strong>
                  <span id="mg-p-email"></span>
                  <button type="button" class="au-link" id="mg-avatar-quitar" hidden>Quitar foto</button>
                </div>
              </div>
              <dl class="cl-datos" id="mg-p-datos"></dl>
              <form class="cl-form" id="mg-form-perfil" hidden novalidate>
                <div class="ps-fila">
                  <label>Nombre * <input class="ps-input" name="nombre" maxlength="50" required></label>
                  <label>Apellido <input class="ps-input" name="apellido" maxlength="50"></label>
                </div>
                <div class="ps-fila">
                  <label>Celular <input class="ps-input" name="celular" type="tel" maxlength="30"></label>
                  <label>DNI <input class="ps-input" name="dni" maxlength="20" inputmode="numeric"></label>
                </div>
                <p class="tn-error" id="mg-error-perfil" role="alert" hidden></p>
                <div class="cl-botones">
                  <button type="button" class="btn btn--ghost ag-btn" id="mg-perfil-cancelar">Cancelar</button>
                  <button type="submit" class="btn btn--accent ag-btn">Guardar</button>
                </div>
              </form>
              <div class="mg-perfil__links">
                <button type="button" class="au-link" id="mg-perfil-editar">Editar mis datos</button>
                <button type="button" class="au-link" id="mg-clave-abrir">Cambiar contraseña</button>
              </div>
              <form class="cl-form mg-clave" id="mg-form-clave" hidden novalidate>
                <label id="mg-clave-actual">Contraseña actual <input class="ps-input" type="password" name="actual"
                    autocomplete="current-password"></label>
                <label>Nueva contraseña <input class="ps-input" type="password" name="nueva" autocomplete="new-password"
                    minlength="8"></label>
                <label>Repetila <input class="ps-input" type="password" name="repetir" autocomplete="new-password"></label>
                <p class="tn-error" id="mg-error-clave" role="alert" hidden></p>
                <div class="cl-botones">
                  <button type="button" class="btn btn--ghost ag-btn" id="mg-clave-cancelar">Cancelar</button>
                  <button type="submit" class="btn btn--accent ag-btn">Cambiar</button>
                </div>
              </form>
            </section>

            <!-- AUTOS QUE TUVO -->
            <section class="tn-card mg-vendidos" id="mg-sec-vendidos" hidden>
              <h2 class="ps-etiqueta">Autos que tuve</h2>
              <div class="mg-vendidos__lista" id="mg-vendidos"></div>
            </section>
          </aside>
        </div>
      </div>
    </section>
  </main>

<?php paginaFin(['scripts' => ['ficha-vehiculo', 'mi-garage']]);
