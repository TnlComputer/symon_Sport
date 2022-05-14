/* Menu */
// const toggleMenuElement = document.getElementById("toggle-menu");
const mainMenuElement = document.getElementById("main-menu");
const ell = document.querySelector(".login");
const elo = document.querySelector(".logout");
const elu = document.querySelectorAll(".user");
const ela = document.querySelectorAll(".adm");

/* Consultas */
const cPatente = document.getElementById("form_cPat");
const $vehiculos = document.getElementById("cli-vehiculos");
const datosPatentes = document.getElementById("temp__datos-patentes");

/* Login */
const formLogin = document.getElementById("form__login");

/* Alertas de error */
const respuesta = document.getElementById("respuesta");

/************* MENU oculto o muestro en Moviles ***************/

// toggleMenuElement.addEventListener("click", () => {
//   mainMenuElement.classList.toggle("main-menu--show");
// });

/************* Valido y restringimos menues ***************/
const $pathname = window.location.pathname;
fetch("./php/valido.php")
  .then((res) => res.json())
  .then((dataU) => {
    // console.log(data);
    if (dataU === "inexistente") {
      if (
        $pathname != "/index.html" &&
        $pathname != "/contacto.html" &&
        $pathname != "/login.html"
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
  const templateCons = document.getElementById("temp__datos-patentes").content;
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
            // templateCons.querySelector(".patente__t").textContent = ele.patente;
            templateCons.querySelector(".fecha__t").textContent =
              ele.fecha_trab;
            templateCons.querySelector(".fecha__e").textContent =
              ele.fecha_entrega;
            // templateCons.querySelector(".client__t").textContent =
            //   ele.empresa_nyp;
            templateCons.querySelector(".marca__t").textContent = ele.marca;
            templateCons.querySelector(".modelo__t").textContent = ele.modelo;
            templateCons.querySelector(".km__t").textContent = ele.km;
            templateCons.querySelector(".presu__t").textContent =
              ele.presupuesto;
            templateCons.querySelector(
              ".total__t"
            ).textContent = `$ ${ele.total_presu}
            `;
            // templateCons.querySelector(".factu__t").textContent = ele.factura;
            templateCons.querySelector(".descript__t").textContent =
              ele.observaciones;
            const clone = templateCons.cloneNode(true);
            fragmentCons.appendChild(clone);
          });
          consPatDatos.appendChild(fragmentCons);
        }
      });
  });
}
