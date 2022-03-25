/* Menu */
const toggleMenuElement = document.getElementById("toggle-menu");
const mainMenuElement = document.getElementById("main-menu");
const ell = document.querySelector(".login");
const elo = document.querySelector(".logout");
const elu = document.querySelectorAll(".user");
const ela = document.querySelectorAll(".adm");

/* Clientes */
const $clientes = document.getElementById("id_cliente");
const $form = document.getElementById("cli-formulario");

/* Trabajos */
// const $trabajos = document.querySelector(".div__trabajos");
// const consTrabDatos = document.querySelector(".line__tabla");
// const addTrabajo = document.querySelector(".form__add");
// const editTrabajo = document.querySelector(".form__edit");
// const delTrabajo = document.querySelector(".form__del");
// const tablaGenerator = document.querySelector(".tablagen");

/* Presupuestos */
// const $presupuestos = document.getElementById("presupuestos");

/* Consultas */
const cPatente = document.getElementById("form_cPat");
const $vehiculos = document.getElementById("cli-vehiculos");
const datosPatentes = document.getElementById("temp__datos-patentes");

/* Login */
const formLogin = document.getElementById("form__login");

/* Alertas de error */
const respuesta = document.getElementById("respuesta");

/************* MENU oculto o muestro en Moviles ***************/

toggleMenuElement.addEventListener("click", () => {
  mainMenuElement.classList.toggle("main-menu--show");
});

/************* Valido y restringimos menues ***************/
// const $hostname = window.location.hostname;
// if ($hostname === "tnlcomputer.com.ar") {
// alert(pathname);

const $pathname = window.location.pathname;
fetch("./php/valido.php")
  .then((res) => res.json())
  .then((dataU) => {
    // console.log(data);
    if (dataU === "inexistente") {
      if (
        $pathname != "/ssp/index.html" &&
        $pathname != "/ssp/contacto.html" &&
        $pathname != "/ssp/login.html"
      ) {
        document.location.href = "index.html";
      }
    }
    if (dataU.nivel === "99") {
      // acceso = "Administrador";
      for (let i = 0; i < ela.length; i++) {
        ela[i].classList.remove("none");
        // console.log(ela[i]);
      }
      ell.classList.add("none");
      elo.classList.remove("none");
    }
    if (dataU.nivel === "10") {
      // acceso = "Usuario";
      for (let i = 0; i < elu.length; i++) {
        elu[i].classList.remove("none");
      }
      ell.classList.add("none");
      elo.classList.remove("none");
    }
  });
// }

elo.addEventListener("click", (e) => {
  e.preventDefault();
  fetch("./php/desconecto.php")
    .then((res) => res.json())
    .then((data) => {
      if (data === "inexistente") {
        for (let i = 0; i < ela.length; i++) {
          ela[i].classList.add("none");
        }
        ell.classList.remove("none");
        elo.classList.add("none");
        for (let i = 0; i < elu.length; i++) {
          elu[i].classList.add("none");
        }
      }
      document.location.href = "login.html";
    });
});
// }

/************* Login *****************/

if (formLogin) {
  formLogin.addEventListener("submit", async function (e) {
    e.preventDefault();

    let datos = new FormData(formLogin);

    await fetch("./php/enviar.php", {
      method: "POST",
      body: datos,
    })
      .then((res) => res.json())
      .then((data) => {
        if (data === "inexistente") {
          respuesta.innerHTML = `
        <div class="alert alert-danger" role="alert">
          El usuario o contraseña es erroneo
        </div>`;
          setTimeout(() => {
            respuesta.innerHTML = ``;
          }, 3000);
        } else {
          if (data.nivel === "99") {
            acceso = "Administrador";
            document.location.href = "index.html";
          } else {
            acceso = "Usuario";
            document.location.href = "consultas.html";
          }
        }
      });
  });
}

/************* Consulto Patente *****************/
if (cPatente) {
  const templateCons = document.querySelector(".temp__datos-patentes").content;
  const fragmentCons = document.createDocumentFragment();
  const consPat = document.querySelector(".tit__tabla");
  const consPatDatos = document.querySelector(".line__tabla");

  cPatente.addEventListener("submit", async function (e) {
    e.preventDefault();

    let datos = new FormData(cPatente);
    consPatDatos.innerHTML = ``;
    consPat.classList.add("none");
    const opciones = {
      method: "POST",
      body: datos,
    };

    await fetch("./php/consultas.php", opciones)
      .then((resp) => resp.json())
      .then((dataP) => {
        if (dataP === "inexistente") {
          respuesta.innerHTML = `
          <div class="alert alert-danger" role="alert">
          Patente incorrecta
          </div>`;
          setTimeout(() => {
            respuesta.innerHTML = ``;
          }, 3000);
        } else {
          consPat.classList.remove("none");

          consPatDatos.innerHTML = ``;
          dataP.forEach((ele) => {
            templateCons.querySelector(".patente__t").textContent = ele.patente;
            templateCons.querySelector(".fecha__t").textContent =
              ele.fecha_trab;
            templateCons.querySelector(".fecha__e").textContent =
              ele.fecha_entrega;
            templateCons.querySelector(".client__t").textContent =
              ele.empresa_nyp;
            templateCons.querySelector(".marca__t").textContent = ele.marca;
            templateCons.querySelector(".modelo__t").textContent = ele.modelo;
            templateCons.querySelector(".descript__t").textContent =
              ele.observaciones;
            templateCons.querySelector(".factu__t").textContent = ele.factura;
            templateCons.querySelector(".presu__t").textContent =
              ele.presupuesto;
            const clone = templateCons.cloneNode(true);
            fragmentCons.appendChild(clone);
          });
          consPatDatos.appendChild(fragmentCons);
        }
      });
  });
}

/************** CLIENTES *******************/
if ($clientes) {
  async function loadClientes() {
    await fetch("php/clientes.php")
      .then((respuestas) => respuestas.json())
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
    resp = await fetch("php/vehiculos.php", opciones)
      .then((resp) => resp.json())
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
}
