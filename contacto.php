<?php
require __DIR__ . '/includes/layout.php';
paginaInicio([
  'titulo' => 'Contacto | Symon Sport',
  'descripcion' => 'Taller Mecánico en San Martín, reparación integral de vehiculos',
  'pagina' => 'contacto.php',
]);
?>

  <main class="main" id="contenido">
    <section class="main-section">
      <h1><span>S</span>YMON <span>S</span>PORT</h1>
      <h2>Contacto</h2>
      <div class="contact-layout">
        <aside class="contact-info">
          <h3>Hablemos</h3>
          <p>La forma más rápida de pedir un turno o un presupuesto es por WhatsApp.</p>
          <div class="cta-group">
            <a class="btn btn--whatsapp" href="https://wa.me/541167993863" target="_blank" rel="noopener noreferrer"><img
                src="assets/img/menu/icons8-whatsapp-24.png" alt="" width="24" height="24">WhatsApp</a>
            <a class="btn btn--primary" href="tel:+5491167993863">Llamar</a>
          </div>
          <ul>
            <li><strong>Dirección:</strong> Primera Junta 2261, esquina San Vicente, San Martín, Buenos Aires</li>
            <li><strong>Teléfono:</strong> <a href="tel:+5491167993863">011 15-6799-3863</a></li>
          </ul>
          <a class="btn btn--ghost" href="https://maps.google.com/?q=Primera+Junta+2261,+San+Mart%C3%ADn,+Buenos+Aires" target="_blank"
            rel="noopener noreferrer">Cómo llegar</a>
        </aside>

        <form action="" class="contact-form">
          <legend>Envianos tus comentarios</legend>
          <label for="c-name">Nombre</label>
          <input id="c-name" type="text" name="name" placeholder="Escribí tu nombre" autocomplete="name"
            title="Nombre solo acepta letras y espacios en blanco" pattern="^[A-Za-zÑñÁáÉéÍíÓóÚúÜü\s]+$" required>
          <label for="c-email">Email</label>
          <input id="c-email" type="email" name="email" placeholder="tu@email.com" autocomplete="email"
            title="Email incorrecto" pattern="^[a-z0-9]+(\.[_a-z0-9]+)*@[a-z0-9-]+(\.[a-z0-9-]+)*(\.[a-z]{2,15})$"
            required>
          <label for="c-subject">Asunto</label>
          <input id="c-subject" type="text" name="subject" placeholder="Asunto a tratar" title="El asunto es requerido"
            required>
          <label for="c-comments">Comentarios</label>
          <textarea id="c-comments" name="comments" cols="50" rows="6" maxlength="255" placeholder="Escribí tus comentarios"
            title="Tu Comentario no debe exceder los 255 caracteres" data-pattern="^.{1,255}$" required></textarea>
          <input type="submit" value="Enviar">
          <div class="contact-form-loader none">
            <img src="assets/img/svg/oval.svg" alt="Enviando..." width="48" height="48">
          </div>
          <div class="contact-form-response none" role="status">
            <p>Los datos han sido enviados</p>
          </div>
        </form>
      </div>
    </section>
    <section class="goo-map">
      <h2>Ubicación</h2>
      <iframe class="mapa" title="Mapa de Symon Sport"
        src="https://maps.google.com/maps?q=Primera+Junta+2261,+San+Mart%C3%ADn,+Buenos+Aires&z=16&output=embed"
        allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </section>
  </main>

<?php paginaFin(['whatsapp' => true, 'scripts' => ['contacto']]);
