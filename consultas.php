<?php
require __DIR__ . '/includes/layout.php';
requiereAdmin();
paginaInicio([
  'titulo' => 'Consultas | Symon Sport',
  'descripcion' => 'Taller Mecánico en San Martín, reparación integral de vehiculos',
  'pagina' => 'consultas.php',
  'noindex' => true,
]);
?>

  <main class="main" id="contenido">
    <section class="main-section">
      <h1><span>S</span>YMON <span>S</span>PORT</h1>
      <article class="sec-clientes">
        <h2>CONSULTAS</h2>
        <form id="form_cPat" class="form__consulta" action="">
          <label for="patente">Patente
            <input type="text" id="patente" name="patente_txt" placeholder="AB123CD" autocomplete="off" required>
          </label>
          <input type="submit" value="Consultar">
        </form>
        <div id="respuesta" class="respuesta" role="alert"></div>
      </article>
    </section>

    <section class="consultas__patentes">
      <table class="tit__tabla none">
        <tr>
          <td class="col fecha">Ingreso</td>
          <td class="col fecha tabla__show-48">Entrega</td>
          <td class="col marca tabla__show-76">Marca</td>
          <td class="col modelo tabla__show-76">Modelo</td>
          <td class="col km tabla__show-102">KM</td>
          <td class="col presu tabla__show-102">Presupuesto </td>
          <td class="col total tabla__show-102">Total </td>
        </tr>
      </table>
      <article class="line__tabla"></article>
    </section>
  </main>

  <template id="temp__datos-patentes">
    <table class="dat__tabla ">
      <tr>
        <td class="col fecha fecha__t"></td>
        <td class="col fecha fecha__e tabla__show-48"></td>
        <td class="col marca marca__t tabla__show-76"></td>
        <td class="col modelo modelo__t tabla__show-76"></td>
        <td class="col km km__t tabla__show-102"></td>
        <td class="col presu presu__t tabla__show-102"></td>
        <td class="col total total__t tabla__show-102"></td>
      </tr>
    </table>
    <table>
      <tr>
        <td class="col accion tabla__show-60"></td>
        <td class="col descript-t tabla__show-60">Observaciones</td>
        <td class="col descript-d descript__t tabla__show-60"></td>
      </tr>
    </table>
  </template>

<?php paginaFin(['scripts' => []]);
