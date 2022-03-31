const $clientes = document.getElementById("list__clie");
const $formClie = document.getElementById("cli-formulario");
const datosClie = document.querySelector(".line__tabla");
const templateClie = document.getElementById("template__clientes").content;
const fragmentClie = document.createDocumentFragment();
const tablaGenerator = document.querySelector(".tablagen");
const addClie = document.querySelector(".form__add");
const editClie = document.querySelector(".form__edit");
// const delClie = document.querySelector(".form__del");
// const vPreClie = document.querySelector(".form__vPre");
// const vRepClie = document.querySelector(".form__vRep");

const modal = document.querySelector(".modal");
const closeModal = document.querySelector(".modal__close");
const cancelModal = document.querySelector(".modal__cancel");
const agregoCliente = document.getElementById("form__alt-clie");
const alertas = document.getElementById("modal__alert");
let inputs = document.querySelectorAll("input");

async function loadClientes() {
  datosClie.innerHTML = ``;
  await fetch("php/clientes.php")
    .then((respuestas) => respuestas.json())
    .then((resultado) => {
      // let $options = `<option value="">Elige un Cliente</option>`;
      resultado.forEach((el) => {
        templateClie.querySelector(".edit__form").dataset.id_cliente =
          el.id_cliente;
        templateClie.querySelector(".edit__form").dataset.cuit = el.cuit;
        templateClie.querySelector(".edit__form").dataset.empresa_nyp =
          el.empresa_nyp;
        templateClie.querySelector(".edit__form").dataset.direccion =
          el.direccion;
        templateClie.querySelector(".edit__form").dataset.localidad =
          el.localidad;
        templateClie.querySelector(".edit__form").dataset.provincia =
          el.provincia;
        templateClie.querySelector(".edit__form").dataset.cpost = el.cpost;
        templateClie.querySelector(".edit__form").dataset.contacto =
          el.contacto;
        templateClie.querySelector(".edit__form").dataset.telefono =
          el.telefono;
        templateClie.querySelector(".edit__form").dataset.celular = el.celular;
        templateClie.querySelector(".edit__form").dataset.mail = el.mail;
        templateClie.querySelector(".vVeh__form").dataset.id_cliente =
          el.id_cliente;
        templateClie.querySelector(".cuit__c").textContent = el.cuit;
        templateClie.querySelector(".cliente__c").textContent = el.empresa_nyp;
        templateClie.querySelector(".direccion__c").textContent = el.direccion;
        templateClie.querySelector(".localidad__c").textContent = el.localidad;
        templateClie.querySelector(".provincia__c").textContent = el.provincia;
        templateClie.querySelector(".cpost__c").textContent = el.cpost;
        templateClie.querySelector(".contacto__c").textContent = el.contacto;
        templateClie.querySelector(".telefono__c").textContent = el.telefono;
        templateClie.querySelector(".celular__c").textContent = el.celular;
        templateClie.querySelector(".mail__c").textContent = el.mail;
        const clone = templateClie.cloneNode(true);
        fragmentClie.appendChild(clone);
      });

      datosClie.appendChild(fragmentClie);
    });
}
loadClientes();

tablaGenerator.addEventListener("click", async (event) => {
  // console.log(event.target);

  if (event.target.closest(".add__form")) {
    //ejecutar funcion de agregar tarea
    // alert("ADD Cliente");
    modal.classList.add("modal--show");
  }

  if (event.target.closest(".edit__form")) {
    //ejecutar funcion de agregar tarea
    alert("modificacion Cliente");
  }

  if (event.target.closest(".del__form")) {
    //ejecutar funcion de agregar tarea
    alert("borrar Cliente");
  }
  if (event.target.closest(".vVeh__form")) {
    //ejecutar funcion de agregar tarea
    // console.log(event.target, dataset(key, value));
    alert("Ver Vehiculos del Cliente");
  }
  // if (event.target.closest(".vPre__form")) {
  //   //ejecutar funcion de agregar tarea
  //   alert("Ver Presupuestos del Cliente");
  // }
  // if (event.target.closest(".vRep__form")) {
  //   //ejecutar funcion de agregar tarea
  //   alert("Ver Reparaciones del Cliente");
  // }
});

agregoCliente.addEventListener("submit", async function (ac) {
  ac.preventDefault();
  // alert("agergo linea al prespuesto");
  const datosAC = new FormData(agregoCliente);
  // console.log(addlinePresu);
  // console.log(datosALP);
  const opciones = {
    method: "POST",
    body: datosAC,
  };
  fetch("php/addclt.php", opciones)
    .then((respLP) => respLP.json())
    .then((dataadLP) => {
      if (dataadLP === "400") {
        alertas.innerHTML = `<div class="alert alert-danger" role="alert">El CUIT/CUIL/DNI ya está registrado</div>`;
        setTimeout(() => {
          alertas.innerHTML = ``;
        }, 3000);
      } else {
        alertas.innerHTML = `<div class="alert alert-danger" role="alert">Cliente Registrado</div>`;
        setTimeout(() => {
          alertas.innerHTML = ``;
        }, 3000);
        agregoCliente.reset();
        // inputs.forEach((input) => (input.value = ""));
        modal.classList.remove("modal--show");
        loadClientes();
      }
    });
});

agregoCliente.addEventListener("reset", (cac) => {
  // console.log(cancelModal);
  // cac.preventDefault();
  // inputs.forEach((input) => (input.value = ""));
  agregoCliente.reset();
  modal.classList.remove("modal--show");
});

//   $clientes.addEventListener("change", (e) => loadVehiculos(e.target.value));

//   // console.log("datos ===>", datos);
//   async function loadVehiculos(datos) {
//     const opciones = {
//       method: "POST",
//       body: datos,
//     };
//     resp = await fetch("php/vehiculos.php", opciones)
//       .then((resp) => resp.json())
//       .then((data) => {
//         let $options = "";
//         if (data === "error") {
//           $options += `<p class="error__vehiculo">El Cliente no tiene Vehiculos asociados</p>`;
//         } else {
//           data.forEach((ele) => {
//             $options += `<div class="cli-vehiculos-main">

//           <div class="cli-vehiculos-linea">
//           <div class="cli-vehiculos-titulo">Patentes:</div>
//           <div class="cli-vehiculos-detalle">${ele.patente}</div>
//           </div>

//           <div class="cli-vehiculos-linea">
//           <div class="cli-vehiculos-titulo">Marca:</div>
//           <div class="cli-vehiculos-detalle">${ele.marca}</div>
//           </div>

//           <div class="cli-vehiculos-linea">
//           <div class="cli-vehiculos-titulo">Modelo:</div>
//           <div class="cli-vehiculos-detalle">${ele.modelo}</div>
//           </div>

//           <div class="cli-vehiculos-linea">
//           <div class="cli-vehiculos-titulo">KM:</div>
//           <div class="cli-vehiculos-detalle">${ele.km} km</div>
//           </div>

//           <div class="cli-vehiculos-linea">
//           <div class="cli-vehiculos-titulo">Tanque:</div>
//           <div class="cli-vehiculos-detalle">${ele.tanque}</div>
//           </div>

//           <div class="cli-vehiculos-linea">
//           <div class="cli-vehiculos-titulo">Detalles: </div>
//           <div class="cli-vehiculos-detalle">${ele.detalle}</div>
//           </div>

//           </div>`;
//           });
//         }
//         $vehiculos.innerHTML = $options;
//       });
//   }
// }

// loadClientes();

// $clientes.addEventListener("change", (e) => loadVehiculos(e.target.value));

// // console.log("datos ===>", datos);
// async function loadVehiculos(datos) {
//   const opciones = {
//     method: "POST",
//     body: datos,
//   };
//   respuesta = await fetch("php/vehiculos.php", opciones)
//     .then((respuesta) => respuesta.json())
//     .then((data) => {
//       let $options = "";
//       if (data === "error") {
//         $options += `<p class="error__vehiculo">El Cliente no tiene Vehiculos asociados</p>`;
//       } else {
//         data.forEach((ele) => {
//           $options += `<div class="cli-vehiculos-main">

//           <div class="cli-vehiculos-linea">
//           <div class="cli-vehiculos-titulo">Patentes:</div>
//           <div class="cli-vehiculos-detalle">${ele.patente}</div>
//           </div>

//           <div class="cli-vehiculos-linea">
//           <div class="cli-vehiculos-titulo">Marca:</div>
//           <div class="cli-vehiculos-detalle">${ele.marca}</div>
//           </div>

//           <div class="cli-vehiculos-linea">
//           <div class="cli-vehiculos-titulo">Modelo:</div>
//           <div class="cli-vehiculos-detalle">${ele.modelo}</div>
//           </div>

//           <div class="cli-vehiculos-linea">
//           <div class="cli-vehiculos-titulo">KM:</div>
//           <div class="cli-vehiculos-detalle">${ele.km} km</div>
//           </div>

//           <div class="cli-vehiculos-linea">
//           <div class="cli-vehiculos-titulo">Tanque:</div>
//           <div class="cli-vehiculos-detalle">${ele.tanque}</div>
//           </div>

//           <div class="cli-vehiculos-linea">
//           <div class="cli-vehiculos-titulo">Detalles: </div>
//           <div class="cli-vehiculos-detalle">${ele.detalle}</div>
//           </div>

//           </div>`;
//         });
//       }
//       $vehiculos.innerHTML = $options;
//     });

async function loadVC() {
  const respuestas = await fetch("php/vehiclie.php");
  const resultados = await respuestas.json();
  if (resultados === "400") {
    consPresuDatos.innerHTML = `
        <div class="alert alert-danger" role="alert">
          No hay presupuestos emitidos
          </div>`;
  } else {
    resultados.forEach((el) => {
      templatePresu.querySelector(".presu__p").textContent = el.id_presu;
      templatePresu.querySelector(".client__p").textContent = el.empresa_nyp;
      templatePresu.querySelector(".patente__p").textContent = el.patente;
      templatePresu.querySelector(".marca__p").textContent = el.marca;
      templatePresu.querySelector(".modelo__p").textContent = el.modelo;
      templatePresu.querySelector(".fecha__p").textContent = el.fecha_presu;
      templatePresu.querySelector(
        ".monto__p"
      ).textContent = `$ ${el.total_presu}`;
      templatePresu.querySelector(".descript__p").textContent =
        el.descripcion_presu;
      const clone = templatePresu.cloneNode(true);
      fragmentPresu.appendChild(clone);
    });
    consPresuDatos.appendChild(fragmentPresu);
  }
}
loadVC();
