<?php
require __DIR__ . '/includes/layout.php';
paginaInicio([
  'titulo' => 'Ingresar | Symon Sport',
  'descripcion' => 'Taller Mecánico en San Martín, reparación integral de vehiculos',
  'pagina' => 'login.php',
  'noindex' => true,
  'fuenteTitulos' => true,
]);
?>

  <main class="main" id="contenido">
    <section class="lp-section tn au">
      <div class="lp-container">
        <div class="au-card">
          <img class="au-logo" src="assets/img/logo_symon_sport-200x127.webp" alt="Symon Sport" width="120" height="76">

          <div class="au-aviso" id="au-aviso" role="status" hidden></div>

          <!-- PESTAÑAS -->
          <div class="au-tabs" role="tablist">
            <button type="button" class="au-tab is-activo" role="tab" aria-selected="true" data-vista="ingresar">Ingresar</button>
            <button type="button" class="au-tab" role="tab" aria-selected="false" data-vista="registro">Crear cuenta</button>
          </div>

          <!-- SOCIAL -->
          <div class="au-social" id="au-social" hidden>
            <a class="au-social__btn" href="php/auth/oauth.php?p=google" data-p="google" hidden>
              <svg viewBox="0 0 48 48" aria-hidden="true">
                <path fill="#FFC107" d="M43.6 20.5H42V20H24v8h11.3C33.7 32.7 29.2 36 24 36c-6.6 0-12-5.4-12-12s5.4-12 12-12c3 0 5.8 1.1 7.9 3l5.7-5.7C34 6.1 29.3 4 24 4 12.9 4 4 12.9 4 24s8.9 20 20 20 20-8.9 20-20c0-1.3-.1-2.4-.4-3.5z" />
                <path fill="#FF3D00" d="M6.3 14.7l6.6 4.8C14.7 15.1 19 12 24 12c3 0 5.8 1.1 7.9 3l5.7-5.7C34 6.1 29.3 4 24 4 16.3 4 9.7 8.3 6.3 14.7z" />
                <path fill="#4CAF50" d="M24 44c5.2 0 9.9-2 13.4-5.2l-6.2-5.2C29.2 35.1 26.7 36 24 36c-5.2 0-9.6-3.3-11.3-8l-6.5 5C9.5 39.6 16.2 44 24 44z" />
                <path fill="#1976D2" d="M43.6 20.5H42V20H24v8h11.3c-.8 2.2-2.2 4.2-4.1 5.6l6.2 5.2C37 39.2 44 34 44 24c0-1.3-.1-2.4-.4-3.5z" />
              </svg>Continuar con Google</a>
            <a class="au-social__btn" href="php/auth/oauth.php?p=microsoft" data-p="microsoft" hidden>
              <svg viewBox="0 0 23 23" aria-hidden="true">
                <path fill="#f35325" d="M1 1h10v10H1z" />
                <path fill="#81bc06" d="M12 1h10v10H12z" />
                <path fill="#05a6f0" d="M1 12h10v10H1z" />
                <path fill="#ffba08" d="M12 12h10v10H12z" />
              </svg>Continuar con Hotmail / Outlook</a>
            <a class="au-social__btn" href="php/auth/oauth.php?p=facebook" data-p="facebook" hidden>
              <svg viewBox="0 0 24 24" aria-hidden="true">
                <path fill="#1877F2" d="M24 12a12 12 0 1 0-13.9 11.9v-8.4H7.1V12h3V9.4c0-3 1.8-4.7 4.5-4.7 1.3 0 2.7.2 2.7.2v3h-1.5c-1.5 0-2 .9-2 1.9V12h3.4l-.5 3.5h-2.9v8.4A12 12 0 0 0 24 12z" />
              </svg>Continuar con Facebook</a>
            <p class="au-sep"><span>o con tu mail</span></p>
          </div>

          <!-- INGRESAR -->
          <form class="au-form" id="au-ingresar" data-vista="ingresar" novalidate>
            <label>Mail o usuario
              <input type="text" name="email" autocomplete="username" autocapitalize="none" required>
            </label>
            <label>Contraseña
              <input type="password" name="clave" autocomplete="current-password" required>
            </label>
            <button type="button" class="au-link" data-ir="recuperar">¿Olvidaste tu contraseña?</button>
            <button type="submit" class="btn btn--accent btn--lg au-submit">Ingresar</button>
          </form>

          <!-- REGISTRO -->
          <form class="au-form" id="au-registro" data-vista="registro" novalidate hidden>
            <div class="au-fila">
              <label>Nombre <input type="text" name="nombre" autocomplete="given-name" required maxlength="50"></label>
              <label>Apellido <input type="text" name="apellido" autocomplete="family-name" maxlength="50"></label>
            </div>
            <label>Mail <input type="email" name="email" autocomplete="email" required></label>
            <label>Contraseña
              <input type="password" name="clave" autocomplete="new-password" required minlength="8">
              <small>Mínimo 8 caracteres, con letras y números.</small>
            </label>
            <label class="au-hp" aria-hidden="true">Web <input type="text" name="web" tabindex="-1" autocomplete="off"></label>
            <button type="submit" class="btn btn--accent btn--lg au-submit">Crear cuenta</button>
            <p class="au-nota">Te vamos a enviar un mail para confirmar tu dirección.</p>
          </form>

          <!-- RECUPERAR -->
          <form class="au-form" id="au-recuperar" data-vista="recuperar" novalidate hidden>
            <h2 class="tn-card__title">Recuperar contraseña</h2>
            <p class="au-nota">Ingresá tu mail y te enviamos un enlace para elegir una contraseña nueva.</p>
            <label>Mail <input type="email" name="email" autocomplete="email" required></label>
            <button type="submit" class="btn btn--accent btn--lg au-submit">Enviar enlace</button>
            <button type="button" class="au-link" data-ir="ingresar">&larr; Volver a ingresar</button>
          </form>
        </div>
      </div>
    </section>
  </main>

<?php paginaFin(['scripts' => ['login']]);
