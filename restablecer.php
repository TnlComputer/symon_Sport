<?php
require __DIR__ . '/includes/layout.php';
paginaInicio([
  'titulo' => t('Nueva contraseña') . ' | Symon Sport',
  'descripcion' => t('Taller Mecánico en San Martín, reparación integral de vehiculos'),
  'pagina' => 'restablecer.php',
  'noindex' => true,
  'fuenteTitulos' => true,
]);
?>

  <main class="main" id="contenido">
    <section class="lp-section tn au">
      <div class="lp-container">
        <div class="au-card">
          <img class="au-logo" src="assets/img/logo_symon_sport-200x127.webp" alt="Symon Sport" width="120" height="76">
          <h1 class="tn-card__title au-titulo"><?= e(t('Elegí tu contraseña nueva')) ?></h1>
          <div class="au-aviso" id="au-aviso" role="status" hidden></div>
          <form class="au-form" id="rs-form" novalidate>
            <label><?= e(t('Contraseña nueva')) ?>
              <input type="password" name="clave" autocomplete="new-password" required minlength="8">
              <small><?= e(t('Mínimo 8 caracteres, con letras y números.')) ?></small>
            </label>
            <label><?= e(t('Repetir contraseña')) ?>
              <input type="password" name="clave2" autocomplete="new-password" required minlength="8">
            </label>
            <button type="submit" class="btn btn--accent btn--lg au-submit"><?= e(t('Guardar contraseña')) ?></button>
          </form>
        </div>
      </div>
    </section>
  </main>

<?php paginaFin(['scripts' => ['restablecer']]);
