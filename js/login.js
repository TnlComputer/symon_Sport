const formLogin = document.getElementById("form__login");
const respuesta = document.getElementById("respuesta");
const ell = document.querySelector(".login");
const elo = document.querySelector(".logout");
const elu = document.querySelectorAll(".user");
const ela = document.querySelectorAll(".adm");

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
