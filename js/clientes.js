// cliente
const $clientes = document.getElementById("list__clie");
const $formClie = document.getElementById("cli-formulario");
const datosClie = document.querySelector(".line__tabla");
const templateClie = document.getElementById("template__clientes").content;
const fragmentClie = document.createDocumentFragment();
const tablaGenerator = document.querySelector(".tablagen");
// const addClie = document.querySelector(".form__add");
// const editClie = document.querySelector(".form__edit");
// const vehClie = document.querySelector(".form__veh");
const modal = document.querySelector(".modal");
const agregoCliente = document.getElementById("form__alt-clie");

//  user clientes
const modalUsr = document.querySelector(".modal__userClie");
const utclie = document.querySelector(".usrTit__clientes");
const tempUsrClie = document.getElementById("usrDatos__clientes").content;
const fragUsrClie = document.createDocumentFragment();
const agregoUserCliente = document.getElementById("form__alt-user-clie");
const udclie = document.querySelector(".line__UCDtabla");

//  vehiculo cliente
const agregoVehiculo = document.getElementById("form__Altavehiculo");
const fragVehTClie = document.createDocumentFragment();
const lvtclie = document.querySelector(".vehTit__clientes");
const vehdatclientes = document.getElementById("vehDatos__clientes").content;
const fragVehDClie = document.createDocumentFragment();
const lvdclie = document.querySelector(".line__VCDtabla");
const clienteTit = document.querySelector(".clienteTit");

//  reparaciones vehiculos clientes
const rvdclientes = document.getElementById("RVDatos__clientes").content;
const fragRVDClie = document.createDocumentFragment();
const lrvtclie = document.querySelector(".RepTit__clientes");
const lrvdclie = document.querySelector(".line__RVCDtabla");
const nvc = document.querySelector(".newVeh__Clientes");

//  presupuestos reparaciones vehiculos cliente
const prvdclientes = document.getElementById("PRVDatos__clientes").content;
const fragPRVDClie = document.createDocumentFragment();
const lprvtclie = document.querySelector(".PRepTit__clientes");
const lprvdclie = document.querySelector(".line__PRVCDtabla");

// alertas
const alertas = document.querySelector("modal__alert");
const alertasV = document.getElementById("modal__alertV");
async function loadClientes() {
  datosClie.innerHTML = ``;
  fetch("php/clientes.php")
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
        templateClie.querySelector(".usrCli__form").dataset.id_cliente =
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
    // event.defaultPrevented();
    modal.classList.add("modal--show");
  }

  if (event.target.closest(".edit__form")) {
    // event.defaultPrevented();
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
    // event.defaultPrevented();
    id = event.target.dataset.id_cliente;
    lvtclie.classList.add("modal--show");
    lvdclie.innerHTML = ``;
    lrvtclie.classList.remove("show");
    lrvdclie.innerHTML = ``;
    lprvtclie.classList.remove("show");
    lprvdclie.innerHTML = ``;
    loadVC(id);
  }

  if (event.target.matches(".usrCli__form")) {
    id = event.target.dataset.id_cliente;
    utclie.classList.add("modal--show");
    loadUC(id);
  }
});

agregoCliente.addEventListener("submit", async function (ac) {
  ac.preventDefault();
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
  dataadLP = await respLP.json();

  if (dataadLP === "400") {
    alertas.innerHTML = `<div class="alert alert-danger" role="alert">El CUIT/CUIL/DNI ya está registrado</div>`;
    setTimeout(() => {
      alertas.innerHTML = ``;
    }, 3000);
  }
  agregoCliente.reset();
  modal.classList.remove("modal--show");
  loadClientes();
});

agregoCliente.addEventListener("reset", (cac) => {
  agregoCliente.reset();
  // cac.preventDefault();
  modal.classList.remove("modal--show");
});

//  Usuarios Cliente
utclie.addEventListener("click", async (eUserClientes) => {
  if (eUserClientes.target.matches(".add__UC")) {
    eUserClientes.preventDefault();
    modalUsr.classList.add("modal--show");
    agregoUserCliente.id_cliente_txt.value =
      eUserClientes.target.dataset.id_cliente;
  }
  if (eUserClientes.target.matches(".cerrar__UC")) {
    eUserClientes.preventDefault();
    utclie.classList.remove("modal--show");
  }

  if (eUserClientes.target.matches(".edit__UC")) {
    eUserClientes.preventDefault();
    console.log("click edit usuario del cliente");
    // utclie.classList.remove("modal--show");

    agregoUserCliente.id_user_txt.value = eUserClientes.target.dataset.id_user;
    agregoUserCliente.id_cliente_txt.value =
      eUserClientes.target.dataset.id_cliente;
    agregoUserCliente.usuario_txt.value = eUserClientes.target.dataset.usuario;
    agregoUserCliente.clave_txt.value = eUserClientes.target.dataset.clave;
    agregoUserCliente.nombre_txt.value = eUserClientes.target.dataset.nombre;
    agregoUserCliente.ape_txt.value = eUserClientes.target.dataset.apellido;
    agregoUserCliente.dni_txt.value = eUserClientes.target.dataset.dni;
    modalUsr.classList.add("modal--show");
  }
});

agregoUserCliente.addEventListener("reset", (cau) => {
  agregoUserCliente.reset();
  // cau.preventDefault();
  modalUsr.classList.remove("modal--show");
});

agregoUserCliente.addEventListener("submit", async function (auc) {
  auc.preventDefault();
  const datosAUC = new FormData(agregoUserCliente);
  if (!auc.target.id_cliente_txt.value) {
    let opciones = {
      method: "POST",
      body: datosAUC,
    };
    respAU = await fetch("php/adduser.php", opciones);
  } else {
    const datosEUC = new FormData(agregoUserCliente);
    let opciones = {
      method: "POST",
      body: datosEUC,
    };
    respAU = await fetch("php/actuser.php", opciones);
    // auc.target.id_cliente_txt.value = "";
  }

  datausr = await respAU.json();

  if (datausr === "400") {
    alertas.innerHTML = `<div class="alert alert-danger" role="alert">El usuario ya está registrado</div>`;
    setTimeout(() => {
      alertas.innerHTML = ``;
    }, 3000);
  }
  modalUsr.classList.remove("modal--show");
  loadUC(datausr);
});

// Vehiculos Clientes
lvtclie.addEventListener("click", async function (cclie) {
  if (cclie.target.matches(".cerrar__VC")) {
    lvtclie.classList.remove("modal--show");
  }

  if (cclie.target.matches(".add__VC")) {
    id = cclie.target.dataset.id_cliente;
    // console.log(id);
    agregoVehiculo.id_cliente_txt.value = id;
    nvc.classList.add("modal--show");
  }

  if (cclie.target.matches(".editVeh__form")) {
    vec = cclie.target.dataset.id_cliente;
    // console.log(vec);
    nvc.classList.add("modal--show");
    agregoVehiculo.id_vehiculo_txt.value = cclie.target.dataset.id_vehiculo;
    agregoVehiculo.id_cliente_txt.value = cclie.target.dataset.id_cliente;
    agregoVehiculo.patente_txt.value = cclie.target.dataset.patente;
    agregoVehiculo.marca_txt.value = cclie.target.dataset.marca;
    agregoVehiculo.modelo_txt.value = cclie.target.dataset.modelo;
    agregoVehiculo.anio_txt.value = cclie.target.dataset.anio;
  }

  //  Reparaciones Vehiculos cliente
  if (cclie.target.matches(".reparaciones__form")) {
    ptt = cclie.target.dataset.patente;
    lrvtclie.classList.remove("show");
    lrvdclie.innerHTML = ``;
    lprvtclie.classList.remove("show");
    lprvdclie.innerHTML = ``;
    // console.log(ptt);
    // loadPatente(ptt);
    loadRVC(ptt);
  }

  if (cclie.target.matches(".cerrar__RVC")) {
    lrvtclie.classList.remove("show");
    lrvdclie.innerHTML = ``;
    lprvtclie.classList.remove("show");
    lprvdclie.innerHTML = ``;
  }

  // Presupuestos reparaciones Vehiculos Cliente
  if (cclie.target.matches(".presupuesto__form")) {
    prc = cclie.target.dataset.presupuesto;
    // console.log(prc);
    loadPRVC(prc);
  }

  if (cclie.target.matches(".cerrar__PRVC")) {
    lprvtclie.classList.remove("show");
    lprvdclie.innerHTML = ``;
  }
});

// alta Vehiculos - Botones Aceptar y Cancelar
agregoVehiculo.addEventListener("submit", async function (vac) {
  console.log("click submit");
  // console.log(vac);
  vac.preventDefault();
  id = vac.target.id_cliente_txt.value;
  idV = vac.target.id_vehiculo_txt.value;

  console.log("id cliente ", id);
  console.log("id Vehiculo ", idV);

  if (!idV) {
    const datosVAC = new FormData(agregoVehiculo);
    let opciones = {
      method: "POST",
      body: datosVAC,
    };
    respvac = await fetch("php/addvhl.php", opciones);
  } else {
    const datosVEC = new FormData(agregoVehiculo);
    let opciones = {
      method: "POST",
      body: datosVEC,
    };
    respvac = await fetch("php/actvhl.php", opciones);
  }
  datavac = await respvac.json();
  if (datavac === "error") {
    alertasV.innerHTML = `<div class="alert alert-danger" role="alert">Error al grabar</div>`;
    setTimeout(() => {
      alertasV.innerHTML = ``;
    }, 3000);
  }
  if (datavac === "existe") {
    alertasV.innerHTML = `<div class="alert alert-danger" role="alert">Patente ya está registrada</div>`;
    setTimeout(() => {
      alertasV.innerHTML = ``;
    }, 3000);
  }
  // id =datavac
  agregoVehiculo.reset();
  nvc.classList.remove("modal--show");
  // console.log("antes de recargar ", datavac);
  loadVC(datavac);
});

agregoVehiculo.addEventListener("reset", (avc) => {
  agregoVehiculo.reset();
  nvc.classList.remove("modal--show");
});

async function loadUC(idcli) {
  udclie.innerHTML = ``;
  try {
    let datosUC = idcli;
    // console.log(datosUC);
    utclie.querySelector(".add__UC").dataset.id_cliente = datosUC;
    const opciones = {
      method: "POST",
      body: datosUC,
    };

    let respuc = await fetch("php/usrclie.php", opciones);
    let resuc = await respuc.json();
    if (resuc === "inexistente") {
      udclie.innerHTML = `
        <div class="alert alert-danger" role="alert">
        No hay usuarios del cliente
        </div>`;
      setTimeout(() => {
        udclie.innerHTML = ``;
      }, 3000);
    } else {
      resuc.forEach((eluc) => {
        tempUsrClie.querySelector(".edit__UC").dataset.id_user = eluc.id_user;
        tempUsrClie.querySelector(".edit__UC").dataset.id_cliente =
          eluc.id_cliente;
        tempUsrClie.querySelector(".edit__UC").dataset.usuario = eluc.usuario;
        tempUsrClie.querySelector(".edit__UC").dataset.clave = eluc.clave;
        tempUsrClie.querySelector(".edit__UC").dataset.nombre = eluc.nombre;
        tempUsrClie.querySelector(".edit__UC").dataset.apellido = eluc.apellido;
        tempUsrClie.querySelector(".edit__UC").dataset.dni = eluc.dni;

        tempUsrClie.querySelector(".usuario__uc").textContent = eluc.usuario;

        tempUsrClie.querySelector(".clave__uc").textContent = "**********";
        // tempUsrClie.querySelector(".clave__uc").textContent = eluc.clave;
        tempUsrClie.querySelector(".nombre__uc").textContent = eluc.nombre;
        tempUsrClie.querySelector(".apellido__uc").textContent = eluc.apellido;
        tempUsrClie.querySelector(".dni__uc").textContent = eluc.dni;

        const clone = tempUsrClie.cloneNode(true);
        fragUsrClie.appendChild(clone);
      });
      udclie.appendChild(fragUsrClie);
    }
  } catch (err) {
    console.log("Error ", err);
  }
}

//  vehiculo del Cliente
async function loadVC(id) {
  lvdclie.innerHTML = ``;
  lvtclie.querySelector(".add__VC").dataset.id_cliente = id;
  // console.log(id);
  let datosAC = id;
  const opciones = {
    method: "POST",
    body: datosAC,
  };

  const respuestas = await fetch("php/vehClie.php", opciones);
  const resultados = await respuestas.json();
  if (resultados === "inexistente") {
    console.log(datosAC);
    lvdclie.innerHTML = `
        <div class="alert alert-danger" role="alert">
          No hay vehículos del cliente aun cargados
          </div>`;
  } else {
    resultados.forEach((el) => {
      vehdatclientes.querySelector(".reparaciones__form").dataset.patente =
        el.patente;
      vehdatclientes.querySelector(".editVeh__form").dataset.id_vehiculo =
        el.id_vehiculo;
      vehdatclientes.querySelector(".editVeh__form").dataset.id_cliente =
        el.id_cliente;
      vehdatclientes.querySelector(".editVeh__form").dataset.patente =
        el.patente;
      vehdatclientes.querySelector(".editVeh__form").dataset.marca = el.marca;
      vehdatclientes.querySelector(".editVeh__form").dataset.modelo = el.modelo;
      vehdatclientes.querySelector(".editVeh__form").dataset.anio = el.anio;

      vehdatclientes.querySelector(".patente__c").textContent = el.patente;
      vehdatclientes.querySelector(".marca__c").textContent = el.marca;
      vehdatclientes.querySelector(".modelo__c").textContent = el.modelo;
      vehdatclientes.querySelector(".anio__c").textContent = el.anio;
      const clone = vehdatclientes.cloneNode(true);
      fragVehDClie.appendChild(clone);
    });
    // lvtclie.classList.add("show");
    lvdclie.appendChild(fragVehDClie);
    // lvdclie.insertBefore(fragVehDClie);
  }
}

//  reparaciones del vehiculo del Cliente
async function loadRVC(ptt) {
  let datosRC = ptt;
  const opciones = {
    method: "POST",
    body: datosRC,
  };

  const respuestasrv = await fetch("php/repVClie.php", opciones);
  const resultadosrv = await respuestasrv.json();
  if (resultadosrv === "inexistente") {
    lrvtclie.classList.add("show");
    lrvdclie.innerHTML = `
          <div class="alert alert-danger" role="alert">
            No hay reparaciones del vehículo del cliente aun cargados
            </div>`;
    setTimeout(() => {
      lrvtclie.classList.remove("show");
      lrvdclie.innerHTML = ``;
    }, 3000);
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
  }
}
//  Prespuesto reparaciones del vehiculo del Cliente
async function loadPRVC(prc) {
  lprvdclie.innerHTML = ``;

  let datosPRC = prc;
  // console.log(datosPRC);
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
      let total = 0;
      total = Number(el.repuestosl_presu) - Number(el.mobra_presu);
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
      prvdclientes.querySelector(".total__prc").textContent = total;
      // prvdclientes.querySelector(".total__prc").textContent = el.total_presu;
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
