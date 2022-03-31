const consTrabDatos = document.querySelector(".line__tabla");
const templateTrab = document.getElementById("template__Trabajos").content;
const fragmentTrab = document.createDocumentFragment();
const tablaGenerator = document.querySelector(".tablagen");
const addTrabajo = document.querySelector(".form__add");
const editTrabajo = document.querySelector(".form__edit");
const delTrabajo = document.querySelector(".form__del");

/** MODAL REPARACIONES **/
const modal = document.querySelector(".modal");
const closeModal = document.querySelector(".modal__close");
/** ALTA REPARACIONES **/
const cancelModal = document.querySelector(".modal__cancel");
const presuARSlc = document.getElementById("presuAR_slc");
const formARep = document.getElementById("form__ARep");
let inputs = document.querySelectorAll("input");

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
      templateTrab.querySelector(".edit__form").dataset.editid = el.id_trabajo;
      templateTrab.querySelector(".fecha__t").textContent = el.fecha_trab;
      templateTrab.querySelector(".fecha__e").textContent = el.fecha_entrega;
      templateTrab.querySelector(".patente__t").textContent = el.patente;
      templateTrab.querySelector(".marca__t").textContent = el.marca;
      templateTrab.querySelector(".modelo__t").textContent = el.modelo;
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
  // console.log(event.target);

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
        $options += `<option value = "${elPus.id_presu}">${elPus.id_presu} - ${elPus.patente}</option>`;
      });
      presuARSlc.innerHTML = $options;
    }
  }

  /** relleno los campos de Patente, Marca y Modelo * */
  presuARSlc.addEventListener("change", (eAR) =>
    loadPresupuestos(eAR.target.value)
  );
  // console.log(presuARSlc);
  // console.log(loadPresupuestos(eAR.target.value));
  async function loadPresupuestos(datos) {
    const opciones = {
      method: "POST",
      body: datos,
    };
    console.log(datos);
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

  /** Formulario datos para agregar**/
  formARep.addEventListener("submit", async function (efr) {
    efr.preventDefault();

    const datosARep = new FormData(formARep);

    await fetch("./php/addRep.php", {
      method: "Post",
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
          addlinePresu.reset();
          // inputs.forEach((input) => (input.value = ""));
          modal.classList.remove("modal--show");
          // agrego registro a la lista de reparaciones
          loadTrabajos();
        }
      });
  });

  formARep.addEventListener("reset", (ecam) => {
    // console.log(cancelModal);
    // ecam.preventDefault();
    // inputs.forEach((input) => (input.value = ""));
    addlinePresu.reset();
    modal.classList.remove("modal--show");
  });

  //chequeo si se presiono algun boton de eliminar tarea
  if (event.target.closest(".del__form")) {
    // console.log(event.target.closest(".del__form").parentElement.parentElement);
    const elementToDelete =
      event.target.closest(".del__form").parentElement.parentElement; // aca uso dos parentElement, porque el boton (o tu formulario) estaba dentro de un div, y luego dentro de un article

    if (confirm("esta seguro que quiere borrar la tarea")) {
      // la siguiente linea solo borra el item del DOM
      // console.log(
      event.target.closest(".del__form").parentElement.parentElement;
      // );
      consTrabDatos.removeChild(elementToDelete);
      // llamar al back para eliminar de la base de datos la reparacion
    }
  }
  //chequeo si se presiono algun boton de editar tarea
  if (event.target.closest(".edit__form")) {
    //ejecutar la funcion de editar tarea
    alert("esta seguro que quiere editar la tarea");
  }
});
