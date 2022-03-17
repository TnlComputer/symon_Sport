const $presupuestos = document.getElementById("presupuestos");

const template = document.getElementById("template__Presupuestos").content;
const fragment = document.createDocumentFragment();
// let $options = ``;

async function loadPresupuestos() {
  await fetch("php/presupuestos.php")
    .then((respuestas) => respuestas.json())
    .then((resultado) => {
      if (resultado === "inexistente") {
        respuesta.innerHTML = `
        <div class="alert alert-danger" role="alert">
          No hay presupuestos emitidos
          </div>`;
      } else {
        resultado.forEach((el) => {
          template.querySelector(".presu").textContent = el.id_presu;
          template.querySelector(".client").textContent = el.id_cliente;
          template.querySelector(".vehic").textContent = el.id_vehiculo;
          template.querySelector(".fecha").textContent = el.fecha_presu;
          template.querySelector(".monto").textContent = el.monto_presu;
          template.querySelector(".descript").textContent =
            el.descripcion_presu;
          const clone = template.cloneNode(true);
          fragment.appendChild(clone);
        });
        $presupuestos.appendChild(fragment);
      }
    });
}
loadPresupuestos();
