<?php
require __DIR__ . '/includes/layout.php';
paginaInicio([
  'titulo' => t('Symon Sport | Taller mecánico en San Martín'),
  'descripcion' => t('Taller mecánico integral en San Martín: diagnóstico electrónico, mecánica, inyección, frenos, suspensión, tren delantero y aire acondicionado. Pedí tu turno por WhatsApp.'),
  'pagina' => 'index.php',
  'fuenteTitulos' => true,
  'head' => <<<'HTML'
  <link rel="preload" as="image" href="assets/img/logo_symon_sport-440.webp" type="image/webp">
  <script type="application/ld+json">{"@context":"https://schema.org","@type":"AutoRepair","name":"Symon Sport","url":"https://symonsport.com.ar/","image":"https://symonsport.com.ar/assets/img/logo_symon_sport-512.png","telephone":"+54 9 11 6799-3863","address":{"@type":"PostalAddress","streetAddress":"Primera Junta 2261","addressLocality":"San Martín","addressRegion":"Buenos Aires","addressCountry":"AR"},"sameAs":["https://www.facebook.com/Symon-Sport-308309696321701"]}</script>
HTML,
]);
?>

  <main class="main" id="contenido">

    <!-- HERO -->
    <section class="lp-hero">
      <div class="lp-container lp-hero__grid">
        <div class="lp-hero__text">
          <p class="lp-eyebrow"><?= e(t('Taller mecánico integral · San Martín')) ?></p>
          <h1 class="lp-hero__title"><?= e(t('Tu auto en manos de')) ?> <em><?= e(t('especialistas')) ?></em></h1>
          <p class="lp-hero__lead"><?= e(t('Diagnóstico, mecánica, electrónica y mantenimiento con atención personalizada.')) ?>
            <?= e(t('Queremos que visitar el taller deje de ser un trámite y se convierta en una buena experiencia.')) ?></p>
          <div class="cta-group lp-hero__cta">
            <a class="btn btn--accent btn--lg" href="turnos.php"><img src="assets/img/menu/calendario.svg" alt=""
                width="24" height="24"><?= e(t('Reservá tu turno')) ?></a>
            <a class="btn btn--ghost btn--lg"
              href="https://wa.me/541167993863?text=Hola%20Symon%20Sport%2C%20quiero%20hacer%20una%20consulta"
              target="_blank" rel="noopener noreferrer"><img src="assets/img/menu/icons8-whatsapp-24.png" alt=""
                width="24" height="24">WhatsApp</a>
          </div>
          <ul class="lp-hero__facts">
            <li><svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M12 21s-7-6.2-7-11.5A7 7 0 0 1 19 9.5C19 14.8 12 21 12 21z" />
                <circle cx="12" cy="9.5" r="2.5" />
              </svg><?= e(t('Primera Junta 2261, esq. San Vicente')) ?></li>
            <li><svg viewBox="0 0 24 24" aria-hidden="true">
                <path
                  d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2" />
              </svg><a href="tel:+5491167993863">011 15-6799-3863</a></li>
          </ul>
        </div>
        <div class="lp-hero__visual">
          <div class="lp-hero__badge">
            <img src="assets/img/logo_symon_sport-440.webp" alt="Symon Sport" width="440" height="280"
              fetchpriority="high">
          </div>
        </div>
      </div>
    </section>

    <!-- PROPUESTA DE VALOR -->
    <section class="lp-values" aria-label="<?= e(t('Por qué elegirnos')) ?>">
      <div class="lp-container lp-values__grid">
        <article class="lp-value">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <rect x="3" y="4" width="18" height="12" rx="2" />
            <path d="M8 20h8M12 16v4M7 11l2.5-3 2.5 4 2-2 3 1" />
          </svg>
          <h3><?= e(t('Diagnóstico preciso')) ?></h3>
          <p><?= e(t('Escaneo de sistemas electrónicos para encontrar la falla real antes de reparar.')) ?></p>
        </article>
        <article class="lp-value">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M7 3h7l5 5v13H7z" />
            <path d="M14 3v5h5M10 13h6M10 17h6" />
          </svg>
          <h3><?= e(t('Presupuesto previo')) ?></h3>
          <p><?= e(t('Te enviamos el presupuesto detallado por mail y no tocamos nada sin tu aprobación.')) ?></p>
        </article>
        <article class="lp-value">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <circle cx="11" cy="11" r="6" />
            <path d="M20 20l-4.5-4.5" />
          </svg>
          <h3><?= e(t('Seguimiento online')) ?></h3>
          <p><?= e(t('Consultá el historial y el estado de las reparaciones de tu vehículo con tu patente.')) ?></p>
        </article>
        <article class="lp-value">
          <svg viewBox="0 0 24 24" aria-hidden="true">
            <path d="M12 3l7 3v6c0 4.5-3 7.5-7 9-4-1.5-7-4.5-7-9V6z" />
            <path d="M9 12l2 2 4-4" />
          </svg>
          <h3><?= e(t('Calidad total')) ?></h3>
          <p><?= e(t('Trayectoria en el mercado automotriz y un estándar de atención de post venta.')) ?></p>
        </article>
      </div>
    </section>

    <!-- SERVICIOS -->
    <section class="lp-section" id="servicios">
      <div class="lp-container">
        <header class="lp-section__head">
          <p class="lp-eyebrow"><?= e(t('Qué hacemos')) ?></p>
          <h2 class="lp-section__title"><?= e(t('Servicios')) ?></h2>
          <p class="lp-section__lead"><?= e(t('Todo lo que tu vehículo necesita, en un solo lugar.')) ?></p>
        </header>
        <div class="lp-services">
          <article class="lp-service">
            <div class="lp-service__icon"><img src="assets/img/servicios/mecanica-trans.png" alt="" width="150"
                height="150" loading="lazy" decoding="async"></div>
            <h3><?= e(t('Mecánica')) ?></h3>
            <ul>
              <li><?= e(t('Mecánica ligera y de motor')) ?></li>
              <li><?= e(t('Filtros y correas')) ?></li>
              <li><?= e(t('Cambio de aceite y fluidos')) ?></li>
              <li><?= e(t('Embrague y caja de velocidades')) ?></li>
            </ul>
          </article>
          <article class="lp-service">
            <div class="lp-service__icon"><img src="assets/img/servicios/electronica-trans.png" alt="" width="150"
                height="150" loading="lazy" decoding="async"></div>
            <h3><?= e(t('Electrónica')) ?></h3>
            <ul>
              <li><?= e(t('Diagnóstico de sistemas electrónicos')) ?></li>
              <li><?= e(t('Electricidad')) ?></li>
              <li><?= e(t('Baterías')) ?></li>
            </ul>
          </article>
          <article class="lp-service">
            <div class="lp-service__icon"><img src="assets/img/servicios/inyeccion-trans.png" alt="" width="150"
                height="150" loading="lazy" decoding="async"></div>
            <h3><?= e(t('Inyección')) ?></h3>
            <ul>
              <li><?= e(t('Inyección de nafta')) ?></li>
              <li><?= e(t('Inyección diésel (diagnóstico)')) ?></li>
              <li><?= e(t('Limpieza y calibración de inyectores')) ?></li>
            </ul>
          </article>
          <article class="lp-service">
            <div class="lp-service__icon"><img src="assets/img/servicios/frenos-trans.png" alt="" width="150"
                height="150" loading="lazy" decoding="async"></div>
            <h3><?= e(t('Frenos')) ?></h3>
            <ul>
              <li><?= e(t('Pastillas y discos de freno')) ?></li>
              <li><?= e(t('Cilindros y bombas de freno')) ?></li>
              <li><?= e(t('Módulo ABS')) ?></li>
            </ul>
          </article>
          <article class="lp-service">
            <div class="lp-service__icon"><img src="assets/img/servicios/suspension-trans.png" alt="" width="150"
                height="150" loading="lazy" decoding="async"></div>
            <h3><?= e(t('Suspensión')) ?></h3>
            <ul>
              <li><?= e(t('Cambio de amortiguadores')) ?></li>
              <li><?= e(t('Cambio de bujes')) ?></li>
              <li><?= e(t('Parrilla de suspensión')) ?></li>
            </ul>
          </article>
          <article class="lp-service">
            <div class="lp-service__icon"><img src="assets/img/servicios/tren_delantero-trans.png" alt="" width="150"
                height="150" loading="lazy" decoding="async"></div>
            <h3><?= e(t('Tren delantero')) ?></h3>
            <ul>
              <li><?= e(t('Alineación y balanceo')) ?></li>
              <li><?= e(t('Caja de dirección hidráulica')) ?></li>
              <li><?= e(t('Tren trasero y neumáticos')) ?></li>
            </ul>
          </article>
          <article class="lp-service">
            <div class="lp-service__icon"><img src="assets/img/servicios/encendido-trans.png" alt="" width="150"
                height="150" loading="lazy" decoding="async"></div>
            <h3><?= e(t('Encendido')) ?></h3>
            <ul>
              <li><?= e(t('Cambio de bujías')) ?></li>
              <li><?= e(t('Bobinas de encendido')) ?></li>
              <li><?= e(t('Cables de bujías')) ?></li>
            </ul>
          </article>
          <article class="lp-service">
            <div class="lp-service__icon"><img src="assets/img/servicios/climatizacion-trans.png" alt="" width="150"
                height="150" loading="lazy" decoding="async"></div>
            <h3><?= e(t('Climatización')) ?></h3>
            <ul>
              <li><?= e(t('Aire acondicionado y carga de gas')) ?></li>
              <li><?= e(t('Reparación de A/C')) ?></li>
              <li><?= e(t('Cambio de radiadores')) ?></li>
            </ul>
          </article>
          <article class="lp-service">
            <div class="lp-service__icon"><img src="assets/img/servicios/accesorios-trans.png" alt="" width="150"
                height="150" loading="lazy" decoding="async"></div>
            <h3><?= e(t('Accesorios')) ?></h3>
            <ul>
              <li><?= e(t('Escobillas y sistemas de escape')) ?></li>
              <li><?= e(t('Polarizado y tratamiento teflón')) ?></li>
              <li><?= e(t('Limpieza de tapizados · Sacabollos')) ?></li>
            </ul>
          </article>
        </div>
      </div>
    </section>

    <!-- PROCESO -->
    <section class="lp-section lp-section--alt" id="como-trabajamos">
      <div class="lp-container">
        <header class="lp-section__head">
          <p class="lp-eyebrow"><?= e(t('Cómo trabajamos')) ?></p>
          <h2 class="lp-section__title"><?= e(t('Simple y transparente')) ?></h2>
        </header>
        <ol class="lp-steps">
          <li class="lp-step">
            <h3><?= e(t('Pedís tu turno')) ?></h3>
            <p><?= e(t('Escribinos por WhatsApp o llamanos y coordinamos el día que te quede cómodo.')) ?></p>
          </li>
          <li class="lp-step">
            <h3><?= e(t('Diagnóstico')) ?></h3>
            <p><?= e(t('Revisamos el vehículo y registramos su estado y kilometraje al ingreso.')) ?></p>
          </li>
          <li class="lp-step">
            <h3><?= e(t('Presupuesto')) ?></h3>
            <p><?= e(t('Recibís el detalle de repuestos y mano de obra por mail para que lo apruebes.')) ?></p>
          </li>
          <li class="lp-step">
            <h3><?= e(t('Reparación y entrega')) ?></h3>
            <p><?= e(t('Hacemos el trabajo y te entregamos el auto. El historial queda disponible online.')) ?></p>
          </li>
        </ol>
      </div>
    </section>

    <!-- NOSOTROS -->
    <section class="lp-section">
      <div class="lp-container lp-about">
        <div>
          <p class="lp-eyebrow"><?= e(t('Quiénes somos')) ?></p>
          <h2 class="lp-section__title"><?= e(t('Un nuevo estándar de atención')) ?></h2>
          <p><?= e(t('Poseemos una gran trayectoria en el mercado automotriz y nuestros logros profesionales están directamente relacionados con el incesante foco en la calidad total.')) ?></p>
          <p><?= e(t('Es un anhelo de muchos años para quienes formamos parte de Symon Sport generar un espacio donde podamos implementar procesos que nos aseguren crear un nuevo estándar de atención en post venta.')) ?></p>
        </div>
        <blockquote class="lp-quote">
          <p>“<?= e(t('Un espacio donde manejarnos con cordialidad sea la premisa y la satisfacción de nuestros clientes, la meta.')) ?>”</p>
          <cite><?= e(t('Equipo Symon Sport')) ?></cite>
        </blockquote>
      </div>
    </section>

    <!-- CONSEJOS -->
    <section class="lp-section lp-section--alt" id="consejos">
      <div class="lp-container">
        <header class="lp-section__head">
          <p class="lp-eyebrow"><?= e(t('Mantenimiento')) ?></p>
          <h2 class="lp-section__title"><?= e(t('Consejos para tu auto')) ?></h2>
        </header>
        <div class="lp-faq">
          <details>
            <summary><?= e(t('Neumáticos')) ?></summary>
            <p><?= e(t('Deben tener una profundidad mínima de 1,6 mm. Controlá la presión periódicamente y verificá que no tengan grietas ni fisuras.')) ?></p>
          </details>
          <details>
            <summary><?= e(t('Frenos')) ?></summary>
            <p><?= e(t('Controlá periódicamente el desgaste de pastillas, discos y cintas, y el nivel del líquido de frenos.')) ?></p>
          </details>
          <details>
            <summary><?= e(t('Motor')) ?></summary>
            <p><?= e(t('Revisá el nivel de líquido refrigerante y de aceite, la correa de distribución y las correas de servicio.')) ?></p>
          </details>
          <details>
            <summary><?= e(t('Amortiguadores')) ?></summary>
            <p><?= e(t('Revisalos cada 20.000 km: influyen en la estabilidad, la maniobrabilidad, la distancia de frenado y el desgaste de los neumáticos.')) ?></p>
          </details>
          <details>
            <summary><?= e(t('Dirección')) ?></summary>
            <p><?= e(t('Revisá la alineación y los terminales de dirección cada 10.000 km. Es vital para tu seguridad y para cuidar los neumáticos.')) ?></p>
          </details>
          <details>
            <summary><?= e(t('Batería')) ?></summary>
            <p><?= e(t('Revisá el estado de la batería, el régimen de carga y el nivel de líquido.')) ?></p>
          </details>
          <details>
            <summary><?= e(t('Si te quedaste sin batería')) ?></summary>
            <p><?= e(t('No empujes el auto para arrancarlo: el combustible no quemado se deposita en el catalizador y puede dañarlo, sobre todo en motores que consumen aceite.')) ?></p>
          </details>
        </div>
      </div>
    </section>

    <!-- CTA -->
    <section class="lp-cta">
      <div class="lp-container lp-cta__inner">
        <div>
          <h2><?= e(t('¿Tu auto necesita un service?')) ?></h2>
          <p><?= e(t('Reservá online en un minuto o escribinos y coordinamos.')) ?></p>
        </div>
        <div class="cta-group">
          <a class="btn btn--light btn--lg" href="turnos.php"><?= e(t('Reservá tu turno')) ?></a>
          <a class="btn btn--whatsapp btn--lg"
            href="https://wa.me/541167993863?text=Hola%20Symon%20Sport%2C%20quiero%20pedir%20un%20turno"
            target="_blank" rel="noopener noreferrer"><img src="assets/img/menu/icons8-whatsapp-24.png" alt=""
              width="24" height="24">WhatsApp</a>
        </div>
      </div>
    </section>

    <!-- UBICACION -->
    <section class="lp-section" id="ubicacion">
      <div class="lp-container lp-location">
        <div class="lp-location__info">
          <p class="lp-eyebrow"><?= e(t('Dónde estamos')) ?></p>
          <h2 class="lp-section__title"><?= e(t('Visitanos')) ?></h2>
          <p class="lp-location__addr">Primera Junta 2261<br><?= e(t('Esquina San Vicente')) ?><br>San Martín, Buenos Aires</p>
          <p><a href="tel:+5491167993863">011 15-6799-3863</a></p>
          <a class="btn btn--primary"
            href="https://maps.google.com/?q=Primera+Junta+2261,+San+Mart%C3%ADn,+Buenos+Aires" target="_blank"
            rel="noopener noreferrer"><?= e(t('Cómo llegar')) ?></a>
        </div>
        <iframe class="lp-location__map" title="<?= e(t('Mapa de Symon Sport')) ?>"
          src="https://maps.google.com/maps?q=Primera+Junta+2261,+San+Mart%C3%ADn,+Buenos+Aires&z=16&output=embed"
          loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
    </section>
  </main>

<?php paginaFin(['whatsapp' => true, 'scripts' => []]);
