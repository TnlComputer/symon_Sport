const consPresuDatos = document.querySelector(".line__tabla");
const templatePresu = document.getElementById("template__Presupuestos").content;
const fragmentPresu = document.createDocumentFragment();
const tablaGenerator = document.querySelector(".tablagen");
const addTrabajo = document.querySelector(".form__add");
const editTrabajo = document.querySelector(".form__edit");
const delTrabajo = document.querySelector(".form__del");

async function loadPresupuestos() {
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
      ).textContent = `$ ${el.monto_presu}`;
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
  console.log(event.target);

  // chequeo si se presiono el boton de agregar tarea
  if (event.target.closest(".add__form")) {
    //ejecutar funcion de agregar tarea
    alert("quiere agregar una nuevo Presupuesto");
  }

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
    alert("esta seguro que quiere editar el Presupuesto");
  }
});
