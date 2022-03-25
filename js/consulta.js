// const d = document;
const patente = document.getElementById("cons-formulario");

patente.addEventListener("submit", async function (e) {
  e.preventDefault();

  let datosp = new FormData(patente);

  // console.log(datosp.get("patente"));

  await fetch("./php/consultas.php", {
    method: "Post",
    body: datosp,
  })
    .then((res) => res.json())
    .then((datap) => {
      // console.log(datap);
      if (datap === "inexistente") {
        respuesta.innerHTML = `
        <div class="alert alert-danger" role="alert">
          Patente incorrecta
        </div>`;
        setTimeout(() => {
          respuesta.innerHTML = ``;
        }, 3000);
      } else {
        // console.log(datap.id_cliente);
        respuesta.innerHTML = `
        <div class="alert alert-primary" role="alert">
        <p> Bienvenido ${datap.patente}</p>
        </div>`;
      }
    });
});
