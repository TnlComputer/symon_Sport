const d = document;

function contactForm() {
  const $form = d.querySelector(".contact-form"),
    $inputs = d.querySelectorAll(".contact-form [required]");
  // console.log($inputs);

  $inputs.forEach((input) => {
    const $span = d.createElement("span");
    $span.id = input.name;
    $span.textContent = input.title;
    $span.classList.add("contact-form-error", "none");
    input.insertAdjacentElement("afterend", $span);
  });

  d.addEventListener("keyup", (e) => {
    if (e.target.matches(".contact-form [required]")) {
      let $input = e.target,
        pattern = $input.pattern || $input.dataset.pattern;

      //console.log($input, pattern);

      if (pattern && $input.value !== "") {
        //console.log("El input tiene patron");

        let regex = new RegExp(pattern, "u");
        return !regex.exec($input.value)
          ? d.getElementById($input.name).classList.add("is-active")
          : d.getElementById($input.name).classList.remove("is-active");
      }

      if (!pattern) {
        //console.log("El input NO tiene patron");
        return $input.value === ""
          ? d.getElementById($input.name).classList.add("is-active")
          : d.getElementById($input.name).classList.remove("is-active");
      }
    }
  });

  d.addEventListener("submit", (e) => {
    e.preventDefault();
    // alert("Enviando Formulario");

    const $loader = d.querySelector(".contact-form-loader"),
      $response = d.querySelector(".contact-form-response ");

    $loader.classList.remove("none");

    fetch("./php/send_mail.php", {
      method: "POST",
      body: new FormData(e.target),
      mode: "cors",
    })
      // ante un error, el servidor manda el motivo (ya traducido) en "message"
      .then(async (res) => (res.ok ? res.json() : Promise.reject({ status: res.status, message: (await res.json().catch(() => ({}))).message })))
      .then((json) => {
        // console.log(json);
        $loader.classList.add("none");
        $response.classList.remove("none");
        $response.replaceChildren(Object.assign(document.createElement("p"), { textContent: json.message }));
        // console.log(res);
        $form.reset();
      })
      .catch((err) => {
        // console.log(err);
        $response.replaceChildren(Object.assign(document.createElement("p"), {
          textContent: err.message || t("Error {0}: {1}", err.status ?? "", t("Ocurrió un error al enviar, intenta nuevamente")),
        }));
      })
      .finally(() => {
        $loader.classList.add("none");
        $response.classList.remove("none");
        setTimeout(() => {
          $response.classList.add("none");
          $response.innerHTML = "";
        }, 4000);
      });
  });
}
d.addEventListener("DOMContentLoaded", contactForm);
