const d = document;
const $clientes = d.getElementById("id_cliente");
const $form = d.getElementById("cli-formulario");
const $vehiculos = d.getElementById("cli-vehiculos");

async function loadClientes() {
  // const opciones = {
  //   method: "POST",
  // };
  // fetch("php/clientes.php", opciones);
  respuesta = await fetch("php/clientes.php")
    .then((respuesta) => respuesta.json())
    .then((resultado) => {
      let $options = `<option value="">Elige un Cliente</option>`;
      resultado.forEach((el) => {
        $options += `<option value = "${el.id_cliente}">${el.empresa_nyp}</option>`;
      });
      $clientes.innerHTML = $options;
    });
}

loadClientes();

$clientes.addEventListener("change", (e) => loadVehiculos(e.target.value));

// console.log("datos ===>", datos);
async function loadVehiculos(datos) {
  const opciones = {
    method: "POST",
    body: datos,
  };
  respuesta = await fetch("php/vehiculos.php", opciones)
    .then((respuesta) => respuesta.json())
    .then((data) => {
      let $options = "";
      if (data === "error") {
        $options += `<p class="error__vehiculo">El Cliente no tiene Vehiculos asociados</p>`;
      } else {
        data.forEach((ele) => {
          $options += `<div class="cli-vehiculos-main">

          <div class="cli-vehiculos-linea">
          <div class="cli-vehiculos-titulo">Patentes:</div>
          <div class="cli-vehiculos-detalle">${ele.patente}</div>
          </div>

          <div class="cli-vehiculos-linea">
          <div class="cli-vehiculos-titulo">Marca:</div>
          <div class="cli-vehiculos-detalle">${ele.marca}</div>
          </div>

          <div class="cli-vehiculos-linea">
          <div class="cli-vehiculos-titulo">Modelo:</div>
          <div class="cli-vehiculos-detalle">${ele.modelo}</div>
          </div>

          <div class="cli-vehiculos-linea">
          <div class="cli-vehiculos-titulo">KM:</div>
          <div class="cli-vehiculos-detalle">${ele.km} km</div>
          </div>

          <div class="cli-vehiculos-linea">
          <div class="cli-vehiculos-titulo">Tanque:</div>
          <div class="cli-vehiculos-detalle">${ele.tanque}</div>
          </div>

          <div class="cli-vehiculos-linea">
          <div class="cli-vehiculos-titulo">Detalles: </div>
          <div class="cli-vehiculos-detalle">${ele.detalle}</div>
          </div>

          </div>`;
        });
      }
      $vehiculos.innerHTML = $options;
    });
}
