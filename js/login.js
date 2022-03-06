const d = document;
const formLogin = d.getElementById("form__login");
const respuesta = d.getElementById("respuesta");
formLogin.addEventListener("submit", function (e) {
  e.preventDefault();
  // console.log("click");

  var datos = new FormData(formLogin);

  var datosCrip = sha256(datos);
  // console.log(datos);
  // console.log(datos.get("user_txt"));
  // console.log(datos.get("pass_txt"));

  fetch("./php/enviar.php", {
    method: "Post",
    body: datos,
  })
    .then((res) => res.json())
    .then((data) => {
      // console.log(data);
      if (data === "inexistente") {
        respuesta.innerHTML = `
          <div class="alert alert-danger" role="alert">
            El usuario no existe
          </div>`;
      } else {
        if (data.nivel === "99") {
          acceso = "Administrador";
        } else {
          acceso = "Usuario";
        }
        respuesta.innerHTML = `
          <div class="alert alert-primary" role="alert">
            <p> Bienvenido ${data.nombre}, cuenta: ${acceso}
         </p>
          </div>`;
        // aca tendriamos que hacer que el login desaparezca y aparezca Logout regresando al inicio y mostrando los menues que tienen que ver segun el usuario
      }
    });
});
