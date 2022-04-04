const $clientes = document.getElementById("list__clie");
const $formClie = document.getElementById("cli-formulario");
const datosClie = document.querySelector(".line__tabla");
const templateClie = document.getElementById("template__clientes").content;
const fragmentClie = document.createDocumentFragment();
const tablaGenerator = document.querySelector(".tablagen");
const addClie = document.querySelector(".form__add");
const editClie = document.querySelector(".form__edit");
// const delClie = document.querySelector(".form__del");
const vehClie = document.querySelector(".form__veh");
// const vRepClie = document.querySelector(".form__vRep");

const modal = document.querySelector(".modal");
const closeModal = document.querySelector(".modal__close");
const cancelModal = document.querySelector(".modal__cancel");
const agregoCliente = document.getElementById("form__alt-clie");
const alertas = document.querySelector("modal__alert");
let inputs = document.querySelectorAll("input");

// const vehtitclientes = document.getElementById("vehTit__clientes").content;
// const fragVehTClie = document.createDocumentFragment();

const lvtclie = document.querySelector(".vehTit__clientes");

const vehdatclientes = document.getElementById("vehDatos__clientes").content;
const fragVehDClie = document.createDocumentFragment();

// const vehDatos__cliente = document.querySelector(".vehDatos__clientes");
const lvdclie = document.querySelector(".line__VCDtabla");

const rvdclientes = document.getElementById("RVDatos__clientes").content;
const fragRVDClie = document.createDocumentFragment();
const lrvtclie = document.querySelector(".RepTit__clientes");
const lrvdclie = document.querySelector(".line__RVCDtabla");

const prvdclientes = document.getElementById("PRVDatos__clientes").content;
const fragPRVDClie = document.createDocumentFragment();
const lprvtclie = document.querySelector(".PRepTit__clientes");
const lprvdclie = document.querySelector(".line__PRVCDtabla");

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
        templateClie.querySelector(".veh__form").dataset.id_cliente =
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
    // alert("modificacion Cliente");
    modal.classList.add("modal--show");
    agregoCliente.id_cliente_txt.value = event.target.dataset.id_cliente;
    agregoCliente.cuit_txt.value = event.target.dataset.cuit;
    agregoCliente.empers_txt.value = event.target.dataset.empresa_nyp;
    agregoCliente.dire_txt.value = event.target.dataset.direccion;
    agregoCliente.loc_txt.value = event.target.dataset.localidad;
    agregoCliente.pcia_txt.value = event.target.dataset.provincia;
    agregoCliente.cp_txt.value = event.target.dataset.cpost;
    agregoCliente.contacto_txt.value = event.target.dataset.contacto;
    agregoCliente.tel_txt.value = event.target.dataset.telefono;
    agregoCliente.cel_txt.value = event.target.dataset.celular;
    agregoCliente.mail_txt.value = event.target.dataset.mail;
  }

  if (event.target.matches(".veh__form")) {
    id = event.target.dataset.id_cliente;
    lvtclie.classList.remove("show");
    lvdclie.innerHTML = ``;
    lrvtclie.classList.remove("show");
    lrvdclie.innerHTML = ``;
    lprvtclie.classList.remove("show");
    lprvdclie.innerHTML = ``;
    loadVC(id);
  }

  if (event.target.matches(".cerrar__VC")) {
    lvtclie.classList.remove("show");
    lvdclie.innerHTML = ``;
    lrvtclie.classList.remove("show");
    lrvdclie.innerHTML = ``;
    lprvtclie.classList.remove("show");
    lprvdclie.innerHTML = ``;
  }

  if (event.target.matches(".reparaciones__form")) {
    ptt = event.target.dataset.patente;
    loadRVC(ptt);
  }

  if (event.target.matches(".cerrar__RVC")) {
    lrvtclie.classList.remove("show");
    lrvdclie.innerHTML = ``;
    lprvtclie.classList.remove("show");
    lprvdclie.innerHTML = ``;
  }

  if (event.target.matches(".presupuesto__form")) {
    prc = event.target.dataset.presupuesto;
    loadPRVC(prc);
  }

  if (event.target.matches(".cerrar__PRVC")) {
    lprvtclie.classList.remove("show");
    lprvdclie.innerHTML = ``;
  }
});

agregoCliente.addEventListener("submit", async function (ac) {
  ac.preventDefault();
  // alert("agergo linea al prespuesto");
  // const datosAC = new FormData(agregoCliente);
  // console.log(addlinePresu);
  // console.log(datosALP);

  // console.log(ac.target.id_cliente_txt.value);
  if (!ac.target.id_cliente_txt.value) {
    const datosAC = new FormData(agregoCliente);

    let opciones = {
      method: "POST",
      body: datosAC,
    };
    respLP = await fetch("php/addclt.php", opciones);
  } else {
    const datosEC = new FormData(agregoCliente);
    let opciones = {
      method: "POST",
      body: datosEC,
    };
    respLP = await fetch("php/actclt.php", opciones);
    ac.target.id_cliente_txt.value = "";
  }
  // fetch("php/addclt.php", opciones)
  dataadLP = await respLP.json();

  if (dataadLP === "400") {
    alertas.innerHTML = `<div class="alert alert-danger" role="alert">El CUIT/CUIL/DNI ya está registrado</div>`;
    setTimeout(() => {
      alertas.innerHTML = ``;
    }, 3000);
  }
  // else {
  //   alertas.innerHTML = `<div class="alert alert-danger" role="alert">Grabado</div>`;
  //   setTimeout(() => {
  //     alertas.innerHTML = ``;
  //   }, 3000);
  agregoCliente.reset();
  modal.classList.remove("modal--show");
  loadClientes();
  // }
  // });
});

agregoCliente.addEventListener("reset", (cac) => {
  agregoCliente.reset();
  modal.classList.remove("modal--show");
});

// TEMPLATE vehiculo del Cliente
async function loadVC(id) {
  lvdclie.innerHTML = ``;

  let datosAC = id;
  const opciones = {
    method: "POST",
    body: datosAC,
  };

  const respuestas = await fetch("php/vehClie.php", opciones);
  const resultados = await respuestas.json();
  if (resultados === "inexistente") {
    lvdclie.innerHTML = `
        <div class="alert alert-danger" role="alert">
          No hay vehículos del cliente aun cargados
          </div>`;
  } else {
    resultados.forEach((el) => {
      vehdatclientes.querySelector(".reparaciones__form").dataset.patente =
        el.patente;
      vehdatclientes.querySelector(".patente__c").textContent = el.patente;
      vehdatclientes.querySelector(".marca__c").textContent = el.marca;
      vehdatclientes.querySelector(".modelo__c").textContent = el.modelo;
      vehdatclientes.querySelector(".anio__c").textContent = el.anio;
      const clone = vehdatclientes.cloneNode(true);
      fragVehDClie.appendChild(clone);
    });
    lvtclie.classList.add("show");
    lvdclie.appendChild(fragVehDClie);
    // lvdclie.insertBefore(fragVehDClie);
  }
}

// TEMPLATE reparaciones del vehiculo del Cliente
async function loadRVC(ptt) {
  lrvdclie.innerHTML = ``;
  lprvtclie.classList.remove("show");
  lprvdclie.innerHTML = ``;

  // console.log(datosRC);
  let datosRC = ptt;
  const opciones = {
    method: "POST",
    body: datosRC,
  };

  const respuestasrv = await fetch("php/repVClie.php", opciones);
  const resultadosrv = await respuestasrv.json();
  if (resultadosrv === "inexistente") {
    lrvdclie.innerHTML = `
        <div class="alert alert-danger" role="alert">
          No hay reparaciones del vehículo del cliente aun cargados
          </div>`;
  } else {
    resultadosrv.forEach((el) => {
      rvdclientes.querySelector(".presupuesto__form").dataset.presupuesto =
        el.presupuesto;
      rvdclientes.querySelector(".fecIng__rc").textContent = el.fecha_trab;
      rvdclientes.querySelector(".fecEnt__rc").textContent = el.fecha_entrega;
      rvdclientes.querySelector(".km__rc").textContent = el.km;
      rvdclientes.querySelector(".obs__rc").textContent = el.observaciones;
      rvdclientes.querySelector(".presu__rc").textContent = el.presupuesto;
      rvdclientes.querySelector(".fac__rc").textContent = el.factura;
      const clone = rvdclientes.cloneNode(true);
      fragRVDClie.appendChild(clone);
    });
    lrvtclie.classList.add("show");
    lrvdclie.appendChild(fragRVDClie);
    // lvdclie.insertBefore(fragVehDClie);
  }
}

// TEMPLATE Prespuesto reparaciones del vehiculo del Cliente
async function loadPRVC(prc) {
  lprvdclie.innerHTML = ``;

  let datosPRC = prc;
  console.log(datosPRC);
  const opciones = {
    method: "POST",
    body: datosPRC,
  };

  const respuestasprv = await fetch("php/prepVClie.php", opciones);
  const resultadosprv = await respuestasprv.json();
  if (resultadosprv === "inexistente") {
    lprvdclie.innerHTML = `
        <div class="alert alert-danger" role="alert">
          No hay reparaciones del vehículo del cliente aun cargados
          </div>`;
  } else {
    resultadosprv.forEach((el) => {
      let saldo = 0;
      saldo =
        Number(el.total_presu) -
        Number(el.senia_presu) -
        Number(el.abonado_presu);
      prvdclientes.querySelector(".presu__prc").textContent = el.id_presu;
      prvdclientes.querySelector(".fecpresu__prc").textContent = el.fecha_presu;
      prvdclientes.querySelector(".fecIng__prc").textContent = el.fec_ini_presu;
      prvdclientes.querySelector(".fecIng__prc").textContent = el.fec_ret_presu;
      prvdclientes.querySelector(".descript__prc").textContent =
        el.descripcion_presu;
      prvdclientes.querySelector(".repuestos__prc").textContent =
        el.repuestos_presu;
      prvdclientes.querySelector(".mobra__prc").textContent = el.mobra_presu;
      prvdclientes.querySelector(".total__prc").textContent = el.total_presu;
      prvdclientes.querySelector(".mobra__prc").textContent = el.senia_presu;
      prvdclientes.querySelector(".abonado__prc").textContent =
        el.abonado_presu;
      prvdclientes.querySelector(".saldo__prc").textContent = saldo;
      const clone = prvdclientes.cloneNode(true);
      fragPRVDClie.appendChild(clone);
    });
    lprvtclie.classList.add("show");
    lprvdclie.appendChild(fragPRVDClie);
    // lvdclie.insertBefore(fragVehDClie);
  }
}
