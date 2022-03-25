const consTrabDatos = document.querySelector(".line__tabla");
const templateTrab = document.getElementById("template__Trabajos").content;
const fragmentTrab = document.createDocumentFragment();
const tablaGenerator = document.querySelector(".tablagen");
const addTrabajo = document.querySelector(".form__add");
const editTrabajo = document.querySelector(".form__edit");
const delTrabajo = document.querySelector(".form__del");

const modal = document.querySelector(".modal");
const closeModal = document.querySelector(".modal__close");
const cancelModal = document.querySelector(".modal__cancel");
const patenteSlc = document.getElementById("patente_slc");
const formARep = document.getElementById("form__ARep");
const alertas = document.getElementById("modal__alert");

async function loadTrabajos() {
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
      templateTrab.querySelector(".del__form").dataset.delid = el.id_trabajo;
      const clone = templateTrab.cloneNode(true);
      fragmentTrab.appendChild(clone);
    });
    consTrabDatos.appendChild(fragmentTrab);
  }
}

loadTrabajos();

//agrego un listener unico al contenedor en donde se encuentra el boton de agregar reparación y toda la tabla de los productos
tablaGenerator.addEventListener("click", async (event) => {
  // console.log(event.target);

  // chequeo si se presiono el boton de agregar tarea
  if (event.target.closest(".add__form")) {
    //ejecutar funcion de agregar tarea
    // alert("quiere agregar una nueva reparación");
    event.preventDefault();
    modal.classList.add("modal--show");

    const respPat = await fetch("php/patentes.php");
    const resultPat = await respPat.json();

    let $options = `<option value="">Elige un Cliente</option>`;

    resultPat.forEach((elPat) => {
      $options += `<option value = "${elPat.id_vehiculo}">${elPat.patente}</option>`;
    });

    patenteSlc.innerHTML = $options;
  }

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

formARep.addEventListener("reset", (ecam) => {
  // console.log(cancelModal);
  // ecam.preventDefault();
  modal.classList.remove("modal--show");
});

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
      if (dataAR === "error") {
        alertas.innerHTML = `<div class="alert alert-danger" role="alert"> Error al guardar los datos de las reparación</div>`;
        setTimeout(() => {
          alertas.innerHTML = ``;
        }, 3000);
      } else if (dataAR === "presu") {
        alertas.innerHTML = `<div class="alert alert-danger" role="alert"> El presupuesto no existe</div>`;
        setTimeout(() => {
          alertas.innerHTML = ``;
        }, 3000);
      } else if (dataAR === "usado") {
        alertas.innerHTML = `<div class="alert alert-danger" role="alert"> El presupuesto ya está asociado al vehiculo anteriormemte, ingrese el presupuesto correcto</div>`;
        setTimeout(() => {
          alertas.innerHTML = ``;
        }, 5000);
      } else if (dataAR === "correcto") {
        // console.log(dataAR);
        modal.classList.remove("modal--show");
        // agrego registro a la lista de reparaciones
        loadTrabajos();
      }
    });
});
