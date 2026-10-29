<?php
require __DIR__ . '/includes/layout.php';
requiereAdmin();
paginaInicio([
  'titulo' => 'Configuración de turnos | Symon Sport',
  'descripcion' => 'Reservá tu turno online en Symon Sport, taller mecánico en San Martín. Elegí el servicio y el horario libre.',
  'pagina' => 'turnos-config.php',
  'noindex' => true,
  'fuenteTitulos' => true,
]);
?>

  <main class="main" id="contenido">
    <section class="lp-section tn">
      <div class="lp-container">
        <header class="lp-section__head">
          <p class="lp-eyebrow">Administración</p>
          <h1 class="lp-section__title">Configuración de turnos</h1>
          <p class="lp-section__lead"><a href="turnos-admin.php" class="cf-link">&larr; Volver a la agenda</a></p>
        </header>

        <div class="cf-aviso" id="cf-aviso" role="status" hidden></div>

        <!-- GENERAL -->
        <form class="tn-card cf-form" id="cf-general">
          <h2 class="tn-card__title">General</h2>
          <div class="cf-grid">
            <label>Autos al mismo tiempo
              <input type="number" name="capacidad" min="1" max="20" required>
              <small>Cuántos autos entran en el taller a la vez (boxes / elevadores). Cada auto ocupa su lugar
                desde que ingresa hasta su salida estimada, aunque sean varios días.</small>
            </label>
            <label>Intervalo entre horarios
              <select name="intervalo_min">
                <option value="15">Cada 15 min</option>
                <option value="30">Cada 30 min</option>
                <option value="60">Cada 1 hora</option>
              </select>
              <small>Cada cuánto se ofrece un horario en el calendario.</small>
            </label>
            <label>Días hacia adelante
              <input type="number" name="dias_max" min="1" max="30" required>
              <small>Hasta cuántos días en el futuro se puede reservar (máximo 30).</small>
            </label>
            <label>Anticipación mínima (horas)
              <input type="number" name="anticipacion_min_horas" min="0" max="72" required>
              <small>Ej. 2 = no se puede pedir un turno para dentro de menos de 2 horas.</small>
            </label>
            <label class="cf-full">Mail que recibe el aviso de cada turno
              <input type="email" name="email_aviso">
            </label>
          </div>
          <div class="cf-acciones"><button type="submit" class="btn btn--accent">Guardar</button></div>
        </form>

        <!-- SERVICIOS -->
        <section class="tn-card">
          <h2 class="tn-card__title">Servicios y duración</h2>
          <p class="cf-ayuda"><b>Horas trabajo</b>: tiempo estimado del trabajo. Si supera el horario del día, se
            reparte en los días hábiles siguientes y el auto ocupa un lugar hasta terminar (ej. motor: 24 h ≈ 3 días).
            <b>Margen</b>: horas extra reservadas por imprevistos o repuestos. Los servicios desactivados no se
            muestran a los clientes (útil para trabajos que necesitan diagnóstico previo).</p>
          <div class="cf-servicios" id="cf-servicios"></div>
          <button type="button" class="btn btn--ghost" id="cf-nuevo-servicio">+ Agregar servicio</button>
        </section>

        <!-- HORARIOS -->
        <section class="tn-card">
          <h2 class="tn-card__title">Horario de atención</h2>
          <p class="cf-ayuda">Podés cargar varias franjas por día (ej. mañana y tarde). Los días sin franjas
            aparecen como cerrados.</p>
          <div class="cf-horarios" id="cf-horarios"></div>
          <form class="cf-inline" id="cf-horario-form">
            <label>Día
              <select name="dia_semana">
                <option value="1">Lunes</option>
                <option value="2">Martes</option>
                <option value="3">Miércoles</option>
                <option value="4">Jueves</option>
                <option value="5">Viernes</option>
                <option value="6">Sábado</option>
                <option value="7">Domingo</option>
              </select>
            </label>
            <label>Desde <input type="time" name="hora_desde" required step="900"></label>
            <label>Hasta <input type="time" name="hora_hasta" required step="900"></label>
            <button type="submit" class="btn btn--primary">Agregar franja</button>
          </form>
        </section>

        <!-- BLOQUEOS -->
        <section class="tn-card">
          <h2 class="tn-card__title">Feriados y cierres</h2>

          <form class="cf-inline cf-importar" id="cf-feriados-form">
            <p class="cf-ayuda cf-importar__texto"><b>Feriados nacionales:</b> se traen del listado oficial
              (ArgentinaDatos) y se cargan como días cerrados. Los que ya estén cargados no se duplican.</p>
            <label>Año <select name="anio" id="cf-anio"></select></label>
            <label class="cf-check"><input type="checkbox" name="puentes" value="1"> Incluir puentes turísticos</label>
            <button type="submit" class="btn btn--accent">Importar feriados nacionales</button>
          </form>

          <p class="cf-ayuda">Feriados locales, decretos de último momento, vacaciones o cierres parciales: cargalos
            acá. Para cerrar todo el día dejá "desde" y "hasta" vacíos.</p>
          <form class="cf-inline" id="cf-bloqueo-form">
            <label>Fecha <input type="date" name="fecha" required></label>
            <label>Desde <input type="time" name="hora_desde" step="900"></label>
            <label>Hasta <input type="time" name="hora_hasta" step="900"></label>
            <label class="cf-crece">Motivo <input type="text" name="motivo" maxlength="100" placeholder="Feriado, vacaciones…"></label>
            <button type="submit" class="btn btn--primary">Agregar cierre</button>
          </form>
          <ul class="cf-lista" id="cf-bloqueos"></ul>
        </section>
      </div>
    </section>
  </main>

<?php paginaFin(['scripts' => ['turnos-config']]);
