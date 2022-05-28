const consTrabDatos = document.querySelector(".line__tabla");
const templateTrab = document.getElementById("template__Trabajos").content;
const fragmentTrab = document.createDocumentFragment();
const tablaGenerator = document.querySelector(".tablagen");
const addTrabajo = document.querySelector(".form__add");
const editTrabajo = document.querySelector(".form__edit");
const delTrabajo = document.querySelector(".form__del");

/** MODAL REPARACIONES **/
const modal = document.querySelector(".modal");
const modalE = document.querySelector(".modal__modificacion");
const closeModal = document.querySelector(".modal__close");
/** ALTA REPARACIONES **/
// const cancelModal = document.querySelector(".modal__cancel");
const presuARSlc = document.getElementById("presuAR_slc");
const formARep = document.getElementById("form__ARep");
const formERep = document.getElementById("form__ERep");
// let inputs = document.querySelectorAll("input");

/** ERROR **/
const alertas = document.getElementById("modal__alert");

/** PATENTE, MARCA Y MODELO **/
const mPatente = document.querySelector(".mPatente");
const mMarca = document.querySelector(".mMarca");
const mModelo = document.querySelector(".mModelo");

/** MUESTRO REPARACIONES REALIZADAS O EN CURSO **/
async function loadTrabajos() {
  consTrabDatos.innerHTML = ``;
  const respuestas = await fetch("php/reparaciones.php");
  const resultados = await respuestas.json();
  if (resultados === "inexistente") {
    consTrabDatos.innerHTML = `<div class="alert alert-danger" role="alert"> No hay trabajos Cargados </div>`;
  } else {
    resultados.forEach((el) => {
      templateTrab.querySelector(".edit__form").dataset.id_trabajo =
        el.id_trabajo;
      templateTrab.querySelector(".edit__form").dataset.presupuesto =
        el.presupuesto;
      templateTrab.querySelector(".edit__form").dataset.patente = el.patente;
      templateTrab.querySelector(".edit__form").dataset.marca = el.marca;
      templateTrab.querySelector(".edit__form").dataset.modelo = el.modelo;
      templateTrab.querySelector(".edit__form").dataset.fecha_trab =
        el.fecha_trab;
      templateTrab.querySelector(".edit__form").dataset.km = el.km;
      templateTrab.querySelector(".edit__form").dataset.observaciones =
        el.observaciones;

      templateTrab.querySelector(".fecha__t").textContent = el.fecha_trab;
      templateTrab.querySelector(".fecha__e").textContent = el.fecha_entrega;
      templateTrab.querySelector(".patente__t").textContent = el.patente;
      templateTrab.querySelector(".marca__t").textContent = el.marca;
      templateTrab.querySelector(".modelo__t").textContent = el.modelo;
      templateTrab.querySelector(".km__t").textContent = el.km;
      templateTrab.querySelector(".descript__t").textContent = el.observaciones;
      templateTrab.querySelector(".presu__t").textContent = el.presupuesto;
      templateTrab.querySelector(".factu__t").textContent = el.factura;
      templateTrab.querySelector(".client__t").textContent = el.empresa_nyp;
      // templateTrab.querySelector(".del__form").dataset.delid = el.id_trabajo;
      const clone = templateTrab.cloneNode(true);
      fragmentTrab.appendChild(clone);
    });
    consTrabDatos.appendChild(fragmentTrab);
  }
}
loadTrabajos();

/** CONTROLO SI HAY PRESUPUESTOS SIN LA REPARACION **/

tablaGenerator.addEventListener("click", async (event) => {
  if (event.target.closest(".add__form")) {
    //ejecutar funcion de agregar tarea
    const respPsu = await fetch("php/psu.php");
    const resultPsu = await respPsu.json();
    if (resultPsu === "sinPresu") {
      alert(
        "No hay presupuestos disponibles!!!, Será redirigido a Presupuestos para que ingrese uno nuevo"
      );
      // setTimeout(() => {
      //   alertas.innerHTML = ``;
      // }, 6000);
      document.location.href = "presupuestos.html";
    } else {
      event.preventDefault();
      modal.classList.add("modal--show");
      // consTrabDatos.innerHTML = ``;
      let $options = `<option value="">Elige un Presupuesto</option>`;

      resultPsu.forEach((elPus) => {
        //  con estas instrucciones me posiciono en el select que habia elegido
        // const miSelect = document.getElementById("idSelect");
        // const seleecionado = miSelect.options[miSelect.selectedIndex].value;
        $options += `<option value = "${elPus.id_presu}">${elPus.id_presu} - ${elPus.patente}</option>`;
      });
      presuARSlc.innerHTML = $options;
    }
  }

  if (event.target.closest(".edit__form")) {
    event.stopPropagation();
    event.preventDefault();
    data = event.target.dataset.presupuesto;
    modalE.classList.add("modal--show");
    formERep.presuER_slc.value = event.target.dataset.presupuesto;
    formERep.patente_txt.value = event.target.dataset.patente;
    formERep.marca_txt.value = event.target.dataset.marca;
    formERep.modelo_txt.value = event.target.dataset.modelo;
    formERep.fecha_txt.value = event.target.dataset.fecha_trab;
    formERep.km_txt.value = event.target.dataset.km;
    formERep.obs_txt.value = event.target.dataset.observaciones;
    formERep.repId_txt.value = event.target.dataset.id_trabajo;
    // }
  }
});

async function loadPresupuestos(datos) {
  const opciones = {
    method: "POST",
    body: datos,
  };
  // console.log(datos);
  await fetch("php/psv.php", opciones)
    .then((resARep) => resARep.json())
    .then((dataAR) => {
      if (dataAR === "error") {
        console.log(dataAR);
      } else {
        mPatente.innerHTML = `${dataAR.patente}`;
        mMarca.innerHTML = `${dataAR.marca}`;
        mModelo.innerHTML = `${dataAR.modelo}`;
      }
    });
}

formARep.addEventListener("submit", async function (efr) {
  efr.preventDefault();

  const datosARep = new FormData(formARep);

  await fetch("php/addRep.php", {
    method: "POST",
    body: datosARep,
  })
    .then((resARep) => resARep.json())
    .then((dataAR) => {
      if (dataAR === "400") {
        alertas.innerHTML = `<div class="alert alert-danger" role="alert"> Error al guardar los datos de las reparación</div>`;
        setTimeout(() => {
          alertas.innerHTML = ``;
        }, 3000);
      } else {
        formARep.reset();
        mPatente.innerHTML = ``;
        mMarca.innerHTML = ``;
        mModelo.innerHTML = ``;

        // inputs.forEach((input) => (input.value = ""));
        modal.classList.remove("modal--show");
        // agrego registro a la lista de reparaciones
        loadTrabajos();
      }
    });
});

/** Limpio campos cargados al oprimir el boton cancelar **/
formARep.addEventListener("reset", (ecam) => {
  mPatente.innerHTML = ``;
  mMarca.innerHTML = ``;
  mModelo.innerHTML = ``;

  formARep.reset();
  // ecam.stopPropagation();
  // ecam.preventDefault();
  modal.classList.remove("modal--show");
});

/** relleno los campos de Patente, Marca y Modelo * */
presuARSlc.addEventListener("change", (eAR) =>
  loadPresupuestos(eAR.target.value)
);

/** Formulario de datos para agregar**/
formERep.addEventListener("submit", async function (efer) {
  efer.preventDefault();

  const datosERep = new FormData(formERep);
  const opciones = {
    method: "POST",
    body: datosERep,
  };
  let resERep = await fetch("php/actrep.php", opciones);
  let dataER = await resERep.json();
  if (dataER === "400") {
    alertas.innerHTML = `<div class="alert alert-danger" role="alert"> Error al guardar los datos de las reparación</div>`;
    setTimeout(() => {
      alertas.innerHTML = ``;
    }, 3000);
  } else {
    formERep.reset();
    modalE.classList.remove("modal--show");
    // consTrabDatos.innerHTML = ``;
  }
  // document.location.href = "reparaciones.html";
  loadTrabajos();
});

/** Limpio campos cargados al cancelar **/
formERep.addEventListener("reset", (ecame) => {
  formERep.reset();
  // ecame.stopPropagation();
  // ecame.preventDefault();
  modalE.classList.remove("modal--show");
  // document.location.href = "reparaciones.html";
});
