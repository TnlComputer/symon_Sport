const $trabajos = document.getElementById("trabajos");
const template = document.getElementById("template__Trabajos").content;
const fragment = document.createDocumentFragment();

async function loadTrabajos() {
  await fetch("php/trabajos.php")
    .then((respuestas) => respuestas.json())
    .then((resultado) => {
      if (resultado === "inexistente") {
        respuesta.innerHTML = `
        <div class="alert alert-danger" role="alert">
          No hay trabajos Cargados
          </div>`;
      } else {
        resultado.forEach((el) => {
          template.querySelector(".client").textContent = el.id_cliente;
          template.querySelector(".vehic").textContent = el.id_vehiculo;
          template.querySelector(".fecha").textContent = el.fecha_trab;
          template.querySelector(".patente").textContent = el.patente;
          template.querySelector(".descript").textContent = el.observaciones;
          template.querySelector(".presu").textContent = el.presupuesto;
          template.querySelector(".factu").textContent = el.factura;
          const clone = template.cloneNode(true);
          fragment.appendChild(clone);
        });
        $trabajos.appendChild(fragment);
      }
    });
}
loadTrabajos();
