<?php
require __DIR__ . '/includes/layout.php';
requiereSesion();
paginaInicio([
  'titulo' => t('Mi garage') . ' | Symon Sport',
  'descripcion' => t('Tus autos, su historial en el taller, vencimientos y tu perfil.'),
  'pagina' => 'mi-garage.php',
  'noindex' => true,
  'fuenteTitulos' => true,
]);
?>

  <main class="main" id="contenido">
    <section class="lp-section tn">
      <div class="lp-container">
        <header class="lp-section__head">
          <p class="lp-eyebrow" id="mg-hola"><?= e(t('Mi garage')) ?></p>
          <h1 class="lp-section__title"><?= e(t('Mis autos')) ?></h1>
        </header>

        <div class="cf-aviso" id="mg-aviso" role="status" hidden></div>

        <div class="mg-columnas">
          <div class="mg-principal">
            <!-- AUTOS ACTIVOS -->
            <section id="mg-garage">
              <div class="mg-autos" id="mg-autos"></div>

              <section class="tn-card mg-agregar" id="mg-agregar" hidden>
                <h2 class="ps-paso__titulo"><?= e(t('Agregar un auto')) ?></h2>
                <form class="cl-form" id="mg-form-agregar" novalidate>
                  <div class="ps-fila">
                    <label><?= e(t('Patente')) ?> * <input class="ps-input tn-patente" name="patente" maxlength="10" placeholder="AB123CD"
                        autocomplete="off" required></label>
                    <label><?= e(t('Marca')) ?> <input class="ps-input" name="marca" maxlength="60"></label>
                    <label><?= e(t('Modelo')) ?> <input class="ps-input" name="modelo" maxlength="80"></label>
                    <label><?= e(t('Año')) ?> <input class="ps-input" name="anio" maxlength="4" inputmode="numeric"></label>
                  </div>
                  <p class="tn-error" id="mg-error-agregar" role="alert" hidden></p>
                  <div class="cl-botones">
                    <button type="button" class="btn btn--ghost ag-btn" id="mg-agregar-cancelar"><?= e(t('Cancelar')) ?></button>
                    <button type="submit" class="btn btn--accent ag-btn"><?= e(t('Agregar')) ?></button>
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
                <button type="button" class="btn btn--ghost ag-btn" id="mg-volver">← <?= e(t('Mis autos')) ?></button>
              </div>
              <p class="mg-bloqueo" id="mg-d-bloqueo" hidden></p>
              <div class="mg-acciones" id="mg-d-acciones"></div>

              <section class="tn-card">
                <h3 class="ps-paso__titulo"><?= e(t('Fotos')) ?></h3>
                <div id="mg-d-fotos"></div>
              </section>

              <section class="tn-card" id="mg-d-sec-vencimientos" hidden>
                <h3 class="ps-paso__titulo"><?= e(t('Vencimientos')) ?></h3>
                <div class="mg-vencimientos" id="mg-d-vencimientos"></div>
              </section>

              <section class="tn-card">
                <div class="cl-historial__cab">
                  <h3 class="ps-paso__titulo"><?= e(t('Datos del auto')) ?></h3>
                  <button type="button" class="au-link" id="mg-d-editar"><?= e(t('Completar / editar')) ?></button>
                </div>
                <div id="mg-d-datos"></div>
                <form id="mg-d-form" hidden novalidate>
                  <div id="mg-d-campos"></div>
                  <label class="fv-campo fv-campo--ancho mg-notas"><span><?= e(t('Notas para vos')) ?></span><input class="ps-input"
                      name="descripcion" maxlength="100" placeholder="<?= e(t('Ej. el auto de Laura')) ?>"></label>
                  <p class="tn-error" id="mg-d-error" role="alert" hidden></p>
                  <div class="cl-botones">
                    <button type="button" class="btn btn--ghost ag-btn" id="mg-d-cancelar"><?= e(t('Cancelar')) ?></button>
                    <button type="submit" class="btn btn--accent ag-btn"><?= e(t('Guardar')) ?></button>
                  </div>
                </form>
              </section>

              <section class="tn-card" id="mg-d-sec-presupuestos" hidden>
                <h3 class="ps-paso__titulo"><?= e(t('Presupuestos')) ?></h3>
                <div class="mg-presus" id="mg-d-presupuestos"></div>
              </section>

              <section class="tn-card">
                <div class="cl-historial__cab">
                  <h3 class="ps-paso__titulo"><?= e(t('Historial')) ?></h3>
                  <button type="button" class="au-link" id="mg-d-trabajo-abrir"><?= e(t('Cargar un trabajo de otro taller')) ?></button>
                </div>
                <form class="cl-form mg-trabajo" id="mg-d-trabajo" hidden novalidate>
                  <div class="ps-fila">
                    <label><?= e(t('Fecha')) ?> * <input class="ps-input" type="date" name="fecha" required></label>
                    <label><?= e(t('Kilometraje')) ?> <input class="ps-input" name="km" inputmode="numeric" maxlength="10"></label>
                    <label><?= e(t('Lo hizo')) ?>
                      <select class="ps-input" name="realizado_por">
                        <option value="otro_taller"><?= e(t('Otro taller')) ?></option>
                        <option value="cliente"><?= e(t('Yo mismo')) ?></option>
                      </select>
                    </label>
                    <label id="mg-d-trabajo-taller"><?= e(t('Taller')) ?> <input class="ps-input" name="taller" maxlength="80"></label>
                  </div>
                  <label><?= e(t('Qué se le hizo')) ?> * <input class="ps-input" name="descripcion" maxlength="255" required
                      placeholder="<?= e(t('Ej. cambio de aceite y filtros')) ?>"></label>
                  <div class="ps-fila">
                    <label><?= e(t('Detalle')) ?> <input class="ps-input" name="detalle" maxlength="2000" placeholder="<?= e(t('Repuestos, marcas, observaciones')) ?>"></label>
                    <label><?= e(t('Costo ($)')) ?> <input class="ps-input" name="costo" inputmode="decimal" maxlength="15"></label>
                  </div>
                  <p class="tn-error" id="mg-d-trabajo-error" role="alert" hidden></p>
                  <div class="cl-botones">
                    <button type="button" class="btn btn--ghost ag-btn" id="mg-d-trabajo-cancelar"><?= e(t('Cancelar')) ?></button>
                    <button type="submit" class="btn btn--accent ag-btn"><?= e(t('Guardar')) ?></button>
                  </div>
                </form>
                <ol class="cl-linea" id="mg-d-historial"></ol>
              </section>

              <div class="mg-zona-baja" id="mg-d-baja"></div>
            </section>
          </div>

          <aside class="mg-lateral">
            <!-- PERFIL -->
            <section class="tn-card mg-perfil">
              <div class="mg-perfil__cab">
                <label class="mg-avatar" title="<?= e(t('Cambiar foto')) ?>">
                  <img id="mg-avatar-img" alt="" hidden>
                  <span id="mg-avatar-iniciales" aria-hidden="true"></span>
                  <input type="file" id="mg-avatar-archivo" accept="image/jpeg,image/png,image/webp" class="sr-only">
                  <span class="mg-avatar__cambiar"><?= e(t('Cambiar')) ?></span>
                </label>
                <div class="mg-perfil__quien">
                  <strong id="mg-p-nombre"></strong>
                  <span id="mg-p-email"></span>
                  <button type="button" class="au-link" id="mg-avatar-quitar" hidden><?= e(t('Quitar foto')) ?></button>
                </div>
              </div>
              <dl class="cl-datos" id="mg-p-datos"></dl>
              <form class="cl-form" id="mg-form-perfil" hidden novalidate>
                <div class="ps-fila">
                  <label><?= e(t('Nombre')) ?> * <input class="ps-input" name="nombre" maxlength="50" required></label>
                  <label><?= e(t('Apellido')) ?> <input class="ps-input" name="apellido" maxlength="50"></label>
                </div>
                <div class="ps-fila">
                  <label><?= e(t('Celular')) ?> <input class="ps-input" name="celular" type="tel" maxlength="30"></label>
                  <label><?= e(t('DNI')) ?> <input class="ps-input" name="dni" maxlength="20" inputmode="numeric"></label>
                </div>
                <p class="tn-error" id="mg-error-perfil" role="alert" hidden></p>
                <div class="cl-botones">
                  <button type="button" class="btn btn--ghost ag-btn" id="mg-perfil-cancelar"><?= e(t('Cancelar')) ?></button>
                  <button type="submit" class="btn btn--accent ag-btn"><?= e(t('Guardar')) ?></button>
                </div>
              </form>
              <div class="mg-perfil__links">
                <button type="button" class="au-link" id="mg-perfil-editar"><?= e(t('Editar mis datos')) ?></button>
                <button type="button" class="au-link" id="mg-clave-abrir"><?= e(t('Cambiar contraseña')) ?></button>
              </div>
              <form class="cl-form mg-clave" id="mg-form-clave" hidden novalidate>
                <label id="mg-clave-actual"><?= e(t('Contraseña actual')) ?> <input class="ps-input" type="password" name="actual"
                    autocomplete="current-password"></label>
                <label><?= e(t('Nueva contraseña')) ?> <input class="ps-input" type="password" name="nueva" autocomplete="new-password"
                    minlength="8"></label>
                <label><?= e(t('Repetila')) ?> <input class="ps-input" type="password" name="repetir" autocomplete="new-password"></label>
                <p class="tn-error" id="mg-error-clave" role="alert" hidden></p>
                <div class="cl-botones">
                  <button type="button" class="btn btn--ghost ag-btn" id="mg-clave-cancelar"><?= e(t('Cancelar')) ?></button>
                  <button type="submit" class="btn btn--accent ag-btn"><?= e(t('Cambiar')) ?></button>
                </div>
              </form>
            </section>

            <!-- AUTOS QUE TUVO -->
            <section class="tn-card mg-vendidos" id="mg-sec-vendidos" hidden>
              <h2 class="ps-etiqueta"><?= e(t('Autos que tuve')) ?></h2>
              <div class="mg-vendidos__lista" id="mg-vendidos"></div>
            </section>
          </aside>
        </div>
      </div>
    </section>
  </main>

<?php paginaFin(['scripts' => ['ficha-vehiculo', 'mi-garage']]);
