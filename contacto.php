<?php
require __DIR__ . '/includes/layout.php';
paginaInicio([
  'titulo' => t('Contacto | Symon Sport'),
  'descripcion' => t('Taller Mecánico en San Martín, reparación integral de vehiculos'),
  'pagina' => 'contacto.php',
]);
?>

  <main class="main" id="contenido">
    <section class="main-section">
      <h1><span>S</span>YMON <span>S</span>PORT</h1>
      <h2><?= e(t('Contacto')) ?></h2>
      <div class="contact-layout">
        <aside class="contact-info">
          <h3><?= e(t('Hablemos')) ?></h3>
          <p><?= e(t('La forma más rápida de pedir un turno o un presupuesto es por WhatsApp.')) ?></p>
          <div class="cta-group">
            <a class="btn btn--whatsapp" href="https://wa.me/541167993863" target="_blank" rel="noopener noreferrer"><img
                src="assets/img/menu/icons8-whatsapp-24.png" alt="" width="24" height="24">WhatsApp</a>
            <a class="btn btn--primary" href="tel:+5491167993863"><?= e(t('Llamar')) ?></a>
          </div>
          <ul>
            <li><strong><?= e(t('Dirección:')) ?></strong> <?= e(t('Primera Junta 2261, esquina San Vicente, San Martín, Buenos Aires')) ?></li>
            <li><strong><?= e(t('Teléfono:')) ?></strong> <a href="tel:+5491167993863">011 15-6799-3863</a></li>
          </ul>
          <a class="btn btn--ghost" href="https://maps.google.com/?q=Primera+Junta+2261,+San+Mart%C3%ADn,+Buenos+Aires" target="_blank"
            rel="noopener noreferrer"><?= e(t('Cómo llegar')) ?></a>
        </aside>

        <form action="" class="contact-form">
          <legend><?= e(t('Envianos tus comentarios')) ?></legend>
          <label for="c-name"><?= e(t('Nombre')) ?></label>
          <input id="c-name" type="text" name="name" placeholder="<?= e(t('Escribí tu nombre')) ?>" autocomplete="name"
            title="<?= e(t('Nombre solo acepta letras y espacios en blanco')) ?>" pattern="^[\p{L}\s'.\-]+$" required>
          <label for="c-email"><?= e(t('Email')) ?></label>
          <input id="c-email" type="email" name="email" placeholder="<?= e(t('tu@email.com')) ?>" autocomplete="email"
            title="<?= e(t('Email incorrecto')) ?>" pattern="^[a-z0-9]+(\.[_a-z0-9]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,15})$"
            required>
          <label for="c-subject"><?= e(t('Asunto')) ?></label>
          <input id="c-subject" type="text" name="subject" placeholder="<?= e(t('Asunto a tratar')) ?>" title="<?= e(t('El asunto es requerido')) ?>"
            required>
          <label for="c-comments"><?= e(t('Comentarios')) ?></label>
          <textarea id="c-comments" name="comments" cols="50" rows="6" maxlength="255" placeholder="<?= e(t('Escribí tus comentarios')) ?>"
            title="<?= e(t('Tu Comentario no debe exceder los 255 caracteres')) ?>" data-pattern="^.{1,255}$" required></textarea>
          <input type="submit" value="<?= e(t('Enviar')) ?>">
          <div class="contact-form-loader none">
            <img src="assets/img/svg/oval.svg" alt="<?= e(t('Enviando...')) ?>" width="48" height="48">
          </div>
          <div class="contact-form-response none" role="status">
            <p><?= e(t('Los datos han sido enviados')) ?></p>
          </div>
        </form>
      </div>
    </section>
    <section class="goo-map">
      <h2><?= e(t('Ubicación')) ?></h2>
      <iframe class="mapa" title="<?= e(t('Mapa de Symon Sport')) ?>"
        src="https://maps.google.com/maps?q=Primera+Junta+2261,+San+Mart%C3%ADn,+Buenos+Aires&z=16&output=embed"
        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </section>
  </main>

<?php paginaFin(['whatsapp' => true, 'scripts' => ['contacto']]);
