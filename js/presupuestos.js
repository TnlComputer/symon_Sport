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
const valor1 = document.querySelector(".valor1");
const valor2 = document.querySelector(".valor2");
// let inputs = document.querySelectorAll("input");

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
      templatePresu.querySelector(".edit__form").dataset.id_presu = el.id_presu;
      templatePresu.querySelector(".edit__form").dataset.id_vehiculo =
        el.id_vehiculo;
      templatePresu.querySelector(".edit__form").dataset.id_cliente =
        el.id_cliente;
      templatePresu.querySelector(".edit__form").dataset.empresa_nyp =
        el.empresa_nyp;
      templatePresu.querySelector(".edit__form").dataset.patente = el.patente;
      templatePresu.querySelector(".edit__form").dataset.marca = el.marca;
      templatePresu.querySelector(".edit__form").dataset.modelo = el.modelo;
      templatePresu.querySelector(".edit__form").dataset.fecha_presu =
        el.fecha_presu;
      templatePresu.querySelector(".edit__form").dataset.total_presu =
        el.total_presu;
      templatePresu.querySelector(".edit__form").dataset.descripcion_presu =
        el.descripcion_presu;
      templatePresu.querySelector(".edit__form").dataset.repuestos_presu =
        el.repuestos_presu;
      templatePresu.querySelector(".edit__form").dataset.mobra_presu =
        el.mobra_presu;
      templatePresu.querySelector(".edit__form").dataset.dias_presu =
        el.dias_presu;
      templatePresu.querySelector(".edit__form").dataset.senia_presu =
        el.senia_presu;
      templatePresu.querySelector(".edit__form").dataset.abonado_presu =
        el.abonado_presu;

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
tablaGenerator.addEventListener("click", (event) => {
  // console.log(event.target);

  // chequeo si se presiono el boton de agregar tarea
  if (event.target.closest(".add__form")) {
    event.preventDefault();
    modal.classList.add("modal--show");
    loadPatentes();
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
    const resp = await fetch("php/patmm.php", opciones);
    const data = await resp.json();

    presuMarca.innerHTML = `${data.marca}`;
    presuModelo.innerHTML = `${data.modelo}`;
    presuCliente.innerHTML = `Cliente: ${data.empresa_nyp}`;
  }

  addlinePresu.addEventListener("submit", async function (ealp) {
    ealp.preventDefault();
    if (!ealp.target.id_cliente_txt.value) {
      const datosALP = new FormData(addlinePresu);

      const opciones = {
        method: "POST",
        body: datosALP,
      };
      respLP = await fetch("php/addlp.php", opciones);
    } else {
      const datosELP = new FormData(addlinePresu);

      const opciones = {
        method: "POST",
        body: datosELP,
      };
      respLP = await fetch("php/actlp.php", opciones);
      ealp.target.id_cliente_txt.value = "";
    }
    dataadLP = await respLP.json();
    if (dataadLP === "400") {
      alertas.innerHTML = `<div class="alert alert-danger" role="alert"> Error al guardar los datos del Presupuesto</div>`;
    } else {
      addlinePresu.reset();
      modal.classList.remove("modal--show");
      loadPresupuestos();
    }
  });

  //chequeo si se presiono algun boton de editar tarea
  if (event.target.closest(".edit__form")) {
    event.preventDefault();
    loadPatentes(event.target.dataset.id_vehiculo);
    data = event.target.dataset.id_presu;
    // console.log(data);
    modal.classList.add("modal--show");
    addlinePresu.fecha_txt.value = event.target.dataset.fecha_presu;
    loadVehiculos(event.target.dataset.id_vehiculo);
    addlinePresu.id_presu_txt.value = event.target.dataset.id_presu;
    addlinePresu.id_cliente_txt.value = event.target.dataset.id_cliente;
    addlinePresu.id_vehiculo_txt.value = event.target.dataset.id_vehiculo;
    addlinePresu.desc_txt.value = event.target.dataset.descripcion_presu;

    let $saldo = 0;
    let $total = 0;
    let $repuestos = 0;
    let $mobra = 0;
    let $senia = 0;
    let $abonado = 0;

    // $repuestos = Number(el.repuestos_presu);
    if (
      event.target.dataset.repuestos_presu === "NaN" ||
      event.target.dataset.repuestos_presu === "undefined" ||
      event.target.dataset.repuestos_presu === "null"
    ) {
      $repuestos = 0;
    } else {
      $repuestos = Number(event.target.dataset.repuestos_presu);
    }
    // console.log("REPU", el.repuestos_presu);
    // console.log($repuestos);

    // $mobra = el.mobra_presu;
    if (
      event.target.dataset.mobra_presu === "NaN" ||
      event.target.dataset.mobra_presu === "undefined" ||
      event.target.dataset.mobra_presu === "null"
    ) {
      $mobra = 0;
    } else {
      $mobra = Number(event.target.dataset.mobra_presu);
    }

    if (
      event.target.dataset.senia_presu === "NaN" ||
      event.target.dataset.senia_presu === "undefined" ||
      event.target.dataset.senia_presu === "null"
    ) {
      $senia = 0;
      // console.log("$senia", $senia);
    } else {
      $senia = Number(event.target.dataset.senia_presu);
      // console.log("el.senia", el.senia_presu);
    }
    // console.log("el.senia", el.senia_presu);
    if (
      event.target.dataset.abonado_presu === "NaN" ||
      event.target.dataset.abonado_presu === "undefined" ||
      event.target.dataset.abonado_presu === "null"
    ) {
      $abonado = 0;
    } else {
      $abonado = Number(event.target.dataset.abonado_presu);
    }

    $total = Number($repuestos) + Number($mobra);
    // $total = $repuestos + $mobra;

    // $saldo = $total - $senia - $abonado;
    $saldo = Number($total) - Number($senia) - Number($abonado);

    addlinePresu.repuestos_txt.value = $repuestos;
    addlinePresu.mobra_txt.value = $mobra;
    addlinePresu.total_txt.value = $total;
    addlinePresu.senia_txt.value = $senia;
    addlinePresu.abonado_txt.value = $saldo;
    addlinePresu.dias_txt.value = event.target.dataset.dias_presu;
  }
  // if (event.target.closest(".sumar")) {
  //   calcular(event.target.repuestos_txt.value, event.target.mobra_txt.value);
  //   // addlinePresu.total_txt.value =
  // }
});

addlinePresu.addEventListener("reset", (ecam) => {
  addlinePresu.reset();
  // ecam.preventDefault();
  modal.classList.remove("modal--show");
});

// leo lista de patentes para el select
function loadPatentes(evptt) {
  fetch("php/patentes.php")
    .then((respPtt) => respPtt.json())
    .then((resultPsv) => {
      if (resultPsv === "error") {
        alert("No hay vehiculos cargados!!!");
        setTimeout(() => {
          alertas.innerHTML = ``;
        }, 3000);
      } else {
        let $options = `<option value="">Elige una Patente</option>`;

        resultPsv.forEach((elPat) => {
          let seleccionado = "";
          if (evptt === elPat.id_vehiculo) {
            seleccionado = "selected";
          }

          $options += `<option value = "${elPat.id_vehiculo}" ${seleccionado}>${elPat.patente}</option>`;
        });
        patenteSlc.innerHTML = $options;
      }
    });
}

// function calcular(repuestos, mobra) {
// valor1.addEventListener("change", (evento1) => {
//   const valor1 = evento1.target.value;
//   console.log(valor1);
//   addlinePresu.repuestos_txt.value = event.target.dataset.repuestos_presu;
//   addlinePresu.mobra_txt.value = event.target.dataset.mobra_presu;
//   if (valor1 != "" || valor2 != "") {
//     // result = eval(operando1 + operacion + operando2);
//     let total = Number(valor1) + Number(valor2);
//   }
//   addlinePresu.total_txt.value = total;
// });

// valor2.addEventListener("change", (evento2) => {
//   const valor2 = evento2.target.value;

//   console.log(valor2);
//   if (valor1 != "" || valor2 != "") {
//     // result = eval(operando1 + operacion + operando2);
//     let total = Number(valor1) + Number(valor2);
//   }
//   addlinePresu.total_txt.value = total;
// });
// // }
