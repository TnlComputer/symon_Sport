<?php
require __DIR__ . '/includes/layout.php';
requiereAdmin();
paginaInicio([
  'titulo' => 'Agenda | Symon Sport',
  'descripcion' => 'Reservá tu turno online en Symon Sport, taller mecánico en San Martín. Elegí el servicio y el horario libre.',
  'pagina' => 'turnos-admin.php',
  'noindex' => true,
  'fuenteTitulos' => true,
]);
?>

  <main class="main" id="contenido">
    <section class="lp-section tn">
      <div class="lp-container">
        <header class="lp-section__head">
          <p class="lp-eyebrow">Administración</p>
          <h1 class="lp-section__title">Agenda de turnos</h1>
          <p class="lp-section__lead"><a href="turnos-config.php" class="cf-link">Configuración: capacidad, servicios, horarios y feriados &rarr;</a></p>
        </header>

        <form class="tn-card ag-filtros" id="ag-filtros">
          <label>Desde <input type="date" name="desde" required></label>
          <label>Hasta <input type="date" name="hasta" required></label>
          <label>Estado
            <select name="estado">
              <option value="activos">Pendientes y confirmados</option>
              <option value="">Todos</option>
              <option value="pendiente">Pendientes</option>
              <option value="confirmado">Confirmados</option>
              <option value="atendido">Atendidos</option>
              <option value="cancelado">Cancelados</option>
            </select>
          </label>
          <button type="submit" class="btn btn--primary">Ver</button>
        </form>

        <div class="ag-alta-wrap">
          <button type="button" class="btn btn--accent" id="ag-alta-abrir" aria-expanded="false"
            aria-controls="ag-alta">+ Auto en el taller sin turno</button>
        </div>
        <form class="tn-card ag-alta" id="ag-alta" hidden>
          <h2 class="tn-card__title">Auto en el taller sin turno</h2>
          <p class="cf-ayuda">Cargalo para que ocupe su lugar y el calendario público no ofrezca ese espacio.</p>
          <div class="ag-alta__grid">
            <label>Patente <input name="patente" required maxlength="10" placeholder="AB123CD"></label>
            <label>Nombre <input name="nombre" required maxlength="100"></label>
            <label>Teléfono <input type="tel" name="telefono" maxlength="30"></label>
            <label>Vehículo <input name="vehiculo" maxlength="100" placeholder="Marca y modelo"></label>
            <label class="ag-alta__full">Trabajo <select name="id_servicio" required></select></label>
            <label>Ingreso <input type="date" name="fecha" required></label>
            <label>Hora <input type="time" name="hora_inicio" required step="900" value="08:00"></label>
            <label>Salida estimada <input type="date" name="fecha_fin" required></label>
            <label>Hora <input type="time" name="hora_fin" required step="900" value="17:00"></label>
            <label class="ag-alta__full">Nota / demora <input name="motivo_demora" maxlength="255"
                placeholder="Ej. esperando repuesto"></label>
          </div>
          <div class="cf-acciones"><button type="submit" class="btn btn--accent">Guardar</button></div>
        </form>

        <p class="tn-error" id="ag-error" role="alert" hidden></p>
        <div id="ag-lista" class="ag-lista" aria-live="polite"></div>
      </div>
    </section>
  </main>

<?php paginaFin(['scripts' => ['turnos-admin']]);
