<?php
require __DIR__ . '/includes/layout.php';
paginaInicio([
  'titulo' => 'Pedí tu turno | Symon Sport',
  'descripcion' => 'Reservá tu turno online en Symon Sport, taller mecánico en San Martín. Elegí el servicio y el horario libre.',
  'pagina' => 'turnos.php',
  'fuenteTitulos' => true,
]);
?>

  <main class="main" id="contenido">
    <section class="lp-section tn">
      <div class="lp-container">
        <header class="lp-section__head">
          <p class="lp-eyebrow">Turnos online</p>
          <h1 class="lp-section__title">Pedí tu turno</h1>
          <p class="lp-section__lead">Elegí el servicio, mirá los horarios libres y reservá en un minuto.</p>
        </header>

        <!-- USUARIO CON SESIÓN: sus turnos y vehículos -->
        <section class="tn-card tn-mios" id="tn-mios" hidden aria-labelledby="tn-hola">
          <h2 class="tn-card__title" id="tn-hola">Hola</h2>
          <div id="tn-mis-turnos"></div>
          <h3 class="tn-mios__sub">Tus vehículos</h3>
          <p class="tn-mios__ayuda" id="tn-vehiculos-ayuda">Elegí un vehículo para pedirle turno. Los autos que ya
            tienen un turno vigente no se pueden volver a reservar.</p>
          <div class="tn-vehiculos" id="tn-vehiculos"></div>
        </section>

        <ol class="tn-pasos" aria-label="Pasos">
          <li class="tn-pasos__item is-activo" data-paso="1"><span>1</span>Servicio</li>
          <li class="tn-pasos__item" data-paso="2"><span>2</span>Día y horario</li>
          <li class="tn-pasos__item" data-paso="3"><span>3</span>Tus datos</li>
        </ol>

        <!-- PASO 1 -->
        <section class="tn-card" id="tn-paso-servicio" aria-labelledby="tn-tit-1">
          <h2 class="tn-card__title" id="tn-tit-1">¿Qué necesita tu auto?</h2>
          <div class="tn-servicios" id="tn-servicios" role="radiogroup" aria-labelledby="tn-tit-1">
            <p class="tn-cargando">Cargando servicios…</p>
          </div>
        </section>

        <!-- PASO 2 -->
        <section class="tn-card" id="tn-paso-horario" aria-labelledby="tn-tit-2" hidden>
          <div class="tn-card__head">
            <h2 class="tn-card__title" id="tn-tit-2">Elegí día y horario</h2>
            <p class="tn-elegido" id="tn-servicio-elegido"></p>
          </div>

          <div class="tn-cal-nav">
            <button type="button" class="tn-cal-nav__btn" id="tn-prev" aria-label="Semana anterior">&#8249;</button>
            <strong id="tn-rango" aria-live="polite"></strong>
            <button type="button" class="tn-cal-nav__btn" id="tn-next" aria-label="Semana siguiente">&#8250;</button>
          </div>

          <ul class="tn-leyenda">
            <li><i class="tn-dot tn-dot--libre"></i>Libre</li>
            <li><i class="tn-dot tn-dot--ocupado"></i>Ocupado</li>
            <li><i class="tn-dot tn-dot--no_alcanza"></i>No alcanza el tiempo</li>
          </ul>

          <div class="tn-dias" id="tn-dias" role="tablist" aria-label="Días de la semana"></div>
          <div class="tn-semana" id="tn-semana"></div>
        </section>

        <!-- PASO 3 -->
        <form class="tn-card tn-form" id="tn-form" hidden novalidate>
          <h2 class="tn-card__title">Tus datos</h2>
          <div class="tn-resumen" id="tn-resumen"></div>

          <div class="tn-form__grid">
            <label>Nombre y apellido
              <input type="text" name="nombre" autocomplete="name" required maxlength="100">
            </label>
            <label>Teléfono / WhatsApp
              <input type="tel" name="telefono" autocomplete="tel" inputmode="tel" required maxlength="30"
                placeholder="11 1234-5678">
            </label>
            <label>Email <small>(opcional)</small>
              <input type="email" name="email" autocomplete="email" maxlength="120">
            </label>
            <label>Patente
              <input type="text" name="patente" required maxlength="10" placeholder="AB123CD" autocapitalize="characters"
                class="tn-patente">
            </label>
            <label class="tn-form__full">Marca y modelo <small>(opcional)</small>
              <input type="text" name="vehiculo" maxlength="100" placeholder="Ej. Peugeot 208">
            </label>
            <label class="tn-form__full">Comentario <small>(opcional)</small>
              <textarea name="comentario" rows="3" maxlength="500"
                placeholder="Contanos qué le pasa al auto o qué querés hacerle"></textarea>
            </label>
            <label class="tn-hp" aria-hidden="true">Web <input type="text" name="web" tabindex="-1"
                autocomplete="off"></label>
          </div>

          <p class="tn-error" id="tn-error" role="alert" hidden></p>

          <div class="tn-form__acciones">
            <button type="button" class="btn btn--ghost" id="tn-volver">Cambiar horario</button>
            <button type="submit" class="btn btn--whatsapp btn--lg" id="tn-confirmar">Confirmar turno</button>
          </div>
        </form>

        <!-- OK -->
        <section class="tn-card tn-ok" id="tn-ok" hidden tabindex="-1">
          <div class="tn-ok__icono" aria-hidden="true">&#10003;</div>
          <h2 class="tn-card__title">¡Turno reservado!</h2>
          <p id="tn-ok-detalle"></p>
          <p class="tn-ok__nota">Te vamos a contactar para confirmarlo. Si necesitás cambiarlo, escribinos.</p>
          <div class="cta-group">
            <a class="btn btn--whatsapp" id="tn-ok-wa" href="https://wa.me/541167993863" target="_blank"
              rel="noopener noreferrer">Avisar por WhatsApp</a>
            <a class="btn btn--ghost" href="index.php">Volver al inicio</a>
          </div>
        </section>
      </div>
    </section>
  </main>

<?php paginaFin(['whatsapp' => true, 'scripts' => ['turnos']]);
