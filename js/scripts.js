const toggleMenuElement = document.getElementById("toggle-menu");
/*Login y Menu*/
const mainMenuElement = document.getElementById("main-menu");
const formLogin = document.getElementById("form__login");
const respuesta = document.getElementById("respuesta");
const ell = document.querySelector(".login");
const elo = document.querySelector(".logout");
const elu = document.querySelectorAll(".user");
const ela = document.querySelectorAll(".adm");
/* Consultas*/
const cPatente = document.getElementById("form_cPat");
/* Clientes*/
const $clientes = document.getElementById("id_cliente");
const $form = document.getElementById("cli-formulario");
const $vehiculos = document.getElementById("cli-vehiculos");

/************* MENU oculto o muestro en Moviles ***************/

toggleMenuElement.addEventListener("click", () => {
  mainMenuElement.classList.toggle("main-menu--show");
});

/************* Valido y restringimos menues *****************/

const pathname = window.location.hostname;
if (pathname === "tnlcomputer.com.ar") {
  // alert(pathname);

  fetch("./php/valido.php")
    .then((res) => res.json())
    .then((data) => {
      if (data === "inexistente") {
        // El usuario no existe
      } else {
        if (data.nivel === "99") {
          // acceso = "Administrador";
          for (let i = 0; i < ela.length; i++) {
            ela[i].classList.remove("none");
            // console.log(ela[i]);
          }
          ell.classList.add("none");
          elo.classList.remove("none");
        } else {
          // acceso = "Usuario";
          for (let i = 0; i < elu.length; i++) {
            elu[i].classList.remove("none");
          }
          ell.classList.add("none");
          elo.classList.remove("none");
        }
      }
    });

  elo.addEventListener("click", (e) => {
    e.preventDefault();
    fetch("./php/desconecto.php")
      .then((res) => res.json())
      .then((data) => {
        if (data === "inexistente") {
          // El usuario no existe
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
}

/************* Login *****************/

if (formLogin) {
  formLogin.addEventListener("submit", async function (e) {
    e.preventDefault();

    var datos = new FormData(formLogin);

    await fetch("./php/enviar.php", {
      method: "Post",
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

    await fetch("./php/consultas.php", {
      method: "Post",
      body: datos,
    })
      .then((res) => (res.ok ? res.json() : Promise.reject(res)))
      .then((dataP) => {
        console.log(dataP);
        if (dataP === "inexistente") {
          respuesta.innerHTML = `
        <div class="alert alert-danger" role="alert">
          Patente incorrecta
          </div>`;
          setTimeout(() => {
            respuesta.innerHTML = ``;
          }, 3000);
        } else {
          console.log(dataP.id_cliente);
          respuesta.innerHTML = `
        <div class="consTrab">
        <table>
        <th> Patente: ${dataP.patente} </th>
        <td>
        <tr>Trabajo</tr>
        <tr>Fecha Trabajo</tr>
        <tr>Presupuesto</tr>
        <tr>Factura</tr>
        <tr>Fecha Entrega</tr>
        </td>
        <td>
        <tr> ${dataP.id_trabajo} </tr>
        <tr> ${dataP.fecha_trab}</tr>
        <tr> ${dataP.presupuesto} </td>
        <tr> ${dataP.factura} </tr>
        <tr> ${dataP.fecha_entrega} </tr>
        <tr>${dataP.observaciones}</tr>
        </td>
        </table>
        </div>`;
        }
      })
      .catch((err) => {
        let message = err.statusText || "Ocurrio un error";
        respuesta.innerHTML = `
          <div class="alert alert-primary" role="alert">
          <p> Error de Datos enviados </p>
          <p> ${err.status}:${message}</p>
          </div>`;
      });
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
