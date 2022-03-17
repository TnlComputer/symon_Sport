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

// /* Trabajos */
// const $trabajos = document.getElementById("trabajos");

/* Presupuestos */
// const $presupuestos = document.getElementById("presupuestos");

/* Consultas */
const cPatente = document.getElementById("form_cPat");
const $vehiculos = document.getElementById("cli-vehiculos");
const datosPatentes = document.querySelector("#temp__datos-patentes");

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

    var datos = new FormData(formLogin);

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
  cPatente.addEventListener("submit", async function (e) {
    e.preventDefault();

    var datos = new FormData(cPatente);

    // console.log(datos);

    const opciones = {
      method: "POST",
      body: datos,
    };

    await fetch("./php/consultas.php", opciones)
      .then((resp) => resp.json())
      .then((dataP) => {
        let $options = "";
        if (dataP === "inexistente") {
          respuesta.innerHTML = `
        <div class="alert alert-danger" role="alert">
          Patente incorrecta
          </div>`;
          setTimeout(() => {
            respuesta.innerHTML = ``;
          }, 3000);
        } else {
          // console.log(dataP[0].id_trabajo);
          // templateRespuesta = ``;
          dataP.forEach((ele) => {
            $options += `
        <p>
        <tr> ${ele.patente} </tr>
        <tr> ${ele.nro_trabajo} </tr>
        <tr> ${ele.fecha_trab}</tr>
        <tr> ${ele.presupuesto} </td>
        <tr> ${ele.factura} </tr>
        <tr> ${ele.fecha_entrega} </tr>
        <tr>${ele.observaciones}</tr>
        </td>
        </p>
        </div>`;
          });
        }
        respuesta.innerHTML = $options;
      });
    // .catch((err) => {
    //   let message = err.statusText || "Ocurrio un error";
    //   respuesta.innerHTML = `
    //     <div class="alert alert-primary" role="alert">
    //     <p> Error de Datos enviados </p>
    //     <p> ${err.status}:${message}</p>
    //     </div>`;
    // });
  });
}

/************** CLIENTES *******************/
if ($clientes) {
  async function loadClientes() {
    // const opciones = {
    //   method: "POST",
    // };
    // fetch("php/clientes.php", opciones);
    respuestas = await fetch("php/clientes.php")
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

/************* Trabajos *****************/

// if ($trabajos) {
//   const template = document.getElementById("template__Trabajos").content;
//   const fragment = document.createDocumentFragment();

//   async function loadTrabajos() {
//     await fetch("php/trabajos.php")
//       .then((respuestas) => respuestas.json())
//       .then((resultado) => {
//         if (resultado === "inexistente") {
//           respuesta.innerHTML = `
//         <div class="alert alert-danger" role="alert">
//           No hay trabajos Cargados
//           </div>`;
//         } else {
//           resultado.forEach((el) => {
//             template.querySelector(".client").textContent = el.id_cliente;
//             template.querySelector(".vehic").textContent = el.id_vehiculo;
//             template.querySelector(".fecha").textContent = el.fecha_trab;
//             template.querySelector(".patente").textContent = el.patente;
//             template.querySelector(".descript").textContent = el.observaciones;
//             template.querySelector(".presu").textContent = el.presupuesto;
//             template.querySelector(".factu").textContent = el.factura;
//             const clone = template.cloneNode(true);
//             fragment.appendChild(clone);
//           });
//           $trabajos.appendChild(fragment);
//         }
//       });
//   }
//   loadTrabajos();
// }

// if ($trabajos) {
//   async function loadTrabajos() {
//     await fetch("php/trabajos.php")
//       .then((respuestas) => respuestas.json())
//       .then((resultado) => {
//         console.log("resultado =>", resultado);
//         if (resultado === "inexistente") {
//           $trabajos.innerHTML = `
//         <div class="alert alert-danger" role="alert">
//           No hay presupuestos emitidos
//           </div>`;
//           // setTimeout(() => {
//           //   $presupuestos.innerHTML = ``;
//           // }, 3000);
//         } else {
//           let $options = ``;
//           resultado.forEach((el) => {
//             $options += `<div>${el.id_cliente} - ${el.id_vehiculo} - ${el.fecha_trab} - ${el.patente} -  $${el.observaciones} - ${el.factura}</div>
//           `;
//           });
//           $trabajos.innerHTML = $options;
//         }
//       });
//   }
//   loadTrabajos();
// }

/************* Prespuestos *****************/

// if ($presupuestos) {
//   const template = document.getElementById("template__Presupuestos").content;
//   const fragment = document.createDocumentFragment();
//   // let $options = ``;

//   async function loadPresupuestos() {
//     await fetch("php/presupuestos.php")
//       .then((respuestas) => respuestas.json())
//       .then((resultado) => {
//         if (resultado === "inexistente") {
//           respuesta.innerHTML = `
//         <div class="alert alert-danger" role="alert">
//           No hay presupuestos emitidos
//           </div>`;
//         } else {
//           resultado.forEach((el) => {
//             template.querySelector(".presu").textContent = el.id_presu;
//             template.querySelector(".client").textContent = el.id_cliente;
//             template.querySelector(".vehic").textContent = el.id_vehiculo;
//             template.querySelector(".fecha").textContent = el.fecha_presu;
//             template.querySelector(".monto").textContent = el.monto_presu;
//             template.querySelector(".descript").textContent =
//               el.descripcion_presu;
//             const clone = template.cloneNode(true);
//             fragment.appendChild(clone);
//           });
//           $presupuestos.appendChild(fragment);
//         }
//       });
//   }
//   loadPresupuestos();
// }
