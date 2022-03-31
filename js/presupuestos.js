const consPresuDatos = document.querySelector(".line__tabla");
const templatePresu = document.getElementById("template__Presupuestos").content;
const fragmentPresu = document.createDocumentFragment();
const tablaGenerator = document.querySelector(".tablagen");
const addTrabajo = document.querySelector(".form__add");
const editTrabajo = document.querySelector(".form__edit");
const delTrabajo = document.querySelector(".form__del");

/** MODAL**/
const modal = document.querySelector(".modal");
const closeModal = document.querySelector(".modal__close");
const cancelModal = document.querySelector(".modal__cancel");
const patenteSlc = document.getElementById("patente_slc");
const formAPres = document.getElementById("form__APres");
const alertas = document.getElementById("modal__alert");

const presuCliente = document.querySelector(".modal__cliente-presu");
const presuMarca = document.querySelector(".modal__marca-presu");
const presuModelo = document.querySelector(".modal__modelo-presu");

const addlinePresu = document.querySelector(".alp__form");
let inputs = document.querySelectorAll("input");

async function loadPresupuestos() {
  consPresuDatos.innerHTML = ``;
  const respuestas = await fetch("php/presupuestos.php");
  const resultados = await respuestas.json();
  if (resultados === "inexistente") {
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
loadPresupuestos();

//agrego un listener unico al contenedor en donde se encuentra el boton de agregar reparación y toda la tabla de los productos
tablaGenerator.addEventListener("click", async (event) => {
  // console.log(event.target);

  // chequeo si se presiono el boton de agregar tarea
  if (event.target.closest(".add__form")) {
    //ejecutar funcion de agregar tarea
    // alert("quiere agregar una nuevo Presupuesto");
    event.preventDefault();
    modal.classList.add("modal--show");

    const respPsv = await fetch("php/patentes.php");
    const resultPsv = await respPsv.json();
    if (resultPsv === "error") {
      alert("No hay vehiculos cargados!!!");
      setTimeout(() => {
        alertas.innerHTML = ``;
      }, 3000);
    } else {
      let $options = `<option value="">Elige una Patente</option>`;

      resultPsv.forEach((elPat) => {
        $options += `<option value = "${elPat.id_vehiculo}">${elPat.patente}</option>`;
      });
      patenteSlc.innerHTML = $options;
    }
  }

  /** relleno los campos de Cliente, Marca y Modelo * */
  patenteSlc.addEventListener("change", (emm) =>
    loadVehiculos(emm.target.value)
  );
  async function loadVehiculos(datos) {
    const opciones = {
      method: "POST",
      body: datos,
    };
    fetch("php/patmm.php", opciones)
      .then((resp) => resp.json())
      .then((data) => {
        // const resultPmm = await resPmm.json();

        presuMarca.innerHTML = `${data.marca}`;
        presuModelo.innerHTML = `${data.modelo}`;
        presuCliente.innerHTML = `${data.empresa_nyp}`;
      });
  }

  addlinePresu.addEventListener("submit", async function (ealp) {
    ealp.preventDefault();
    // alert("agergo linea al prespuesto");
    const datosALP = new FormData(addlinePresu);
    // console.log(addlinePresu);
    // console.log(datosALP);
    const opciones = {
      method: "POST",
      body: datosALP,
    };
    fetch("php/addlp.php", opciones)
      .then((respLP) => respLP.json())
      .then((dataadLP) => {
        if (dataadLP === "400") {
          alertas.innerHTML = `<div class="alert alert-danger" role="alert"> Error al guardar los datos del Presupuesto</div>`;
        } else {
          // inputs.forEach((input) => (input.value = ""));
          addlinePresu.reset();
          modal.classList.remove("modal--show");
          loadPresupuestos();
        }
      });
  });

  //chequeo si se presiono algun boton de eliminar tarea
  if (event.target.closest(".del__form")) {
    // console.log(event.target.closest(".del__form").parentElement.parentElement);
    const elementToDelete =
      event.target.closest(".del__form").parentElement.parentElement; // aca uso dos parentElement, porque el boton (o tu formulario) estaba dentro de un div, y luego dentro de un article

    if (confirm("esta seguro que quiere borrar el Presupuesto")) {
      // la siguiente linea solo borra el item del DOM
      // console.log(
      //   event.target.closest(".del__form").parentElement.parentElement
      // );
      consPresuDatos.removeChild(elementToDelete);
      // console.log(consTrabDatos.removeChild(elementToDelete));
      // llamar al back para eliminar de la base de datos la reparacion
    }
  }

  //chequeo si se presiono algun boton de editar tarea
  if (event.target.closest(".edit__form")) {
    //ejecutar la funcion de editar tarea
    alert("esta seguro que quiere editar la tarea");
  }
});

addlinePresu.addEventListener("reset", (ecam) => {
  // console.log(cancelModal);
  // ecam.preventDefault();
  // inputs.forEach((input) => (input.value = ""));
  addlinePresu.reset();
  modal.classList.remove("modal--show");
});

/** Formulario datos para agregar**/
addlinePresu.addEventListener("submit", async function (efr) {
  efr.preventDefault();

  // const datosAPres = new FormData(addlinePresu);
  // console.log(formAPres);
  // await fetch("./php/addPres.php", {
  //   method: "Post",
  //   body: datosAPres,
  // })
  //   .then((resAPres) => resAPres.json())
  //   .then((dataAP) => {
  //     if (dataAP === "error") {
  //       alertas.innerHTML = `<div class="alert alert-danger" role="alert"> Error al guardar los datos del Presupuesto</div>`;
  //       setTimeout(() => {
  //         alertas.innerHTML = ``;
  //       }, 3000);
  //     } else if (dataAP === "correcto") {
  // console.log(dataAR);
  // modal.classList.remove("modal--show");
  // agrego registro a la lista de reparaciones
  //   loadPresupuestos();
  // }
  // });
});
