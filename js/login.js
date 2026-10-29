/* Ingreso, registro y recuperación de contraseña */
const auAviso = document.getElementById("au-aviso");
const auVistas = document.querySelectorAll(".au-form");
const auTabs = document.querySelectorAll(".au-tab");

const AVISOS = {
  link_vencido: [t("El enlace venció o ya fue usado. Pedí uno nuevo."), true],
  sesion_vencida: [t("La sesión de ingreso venció. Probá de nuevo."), true],
  cancelado: [t("Se canceló el ingreso."), true],
  error_proveedor: [t("No pudimos validar tu cuenta con el proveedor. Probá de nuevo."), true],
  sin_mail: [t("Tu cuenta no compartió un mail. Probá con otro método."), true],
  mail_existente: [t("Ese mail ya tiene una cuenta. Ingresá con tu contraseña."), true],
  mail_no_verificado: [t("Ese proveedor no confirma tu mail. Registrate con mail y contraseña."), true],
  proveedor: [t("Ese método de ingreso no está disponible."), true],
  cuenta_baja: [t("Esa cuenta está dada de baja. Comunicate con el taller."), true],
};

function avisar(texto, error = false, link = null) {
  auAviso.textContent = texto;
  auAviso.classList.toggle("au-aviso--error", error);
  if (link) {
    // solo en la PC de desarrollo: el servidor local no manda mails
    const a = document.createElement("a");
    a.href = link;
    a.textContent = t("Abrir enlace (solo en pruebas locales)");
    auAviso.append(document.createElement("br"), a);
  }
  auAviso.hidden = false;
}

function mostrarVista(nombre) {
  auVistas.forEach((f) => (f.hidden = f.dataset.vista !== nombre));
  auTabs.forEach((tab) => {
    const activo = tab.dataset.vista === nombre;
    tab.classList.toggle("is-activo", activo);
    tab.setAttribute("aria-selected", String(activo));
  });
  document.querySelector(".au-tabs").hidden = nombre === "recuperar";
  document.getElementById("au-social").classList.toggle("au-oculto", nombre === "recuperar");
  const primero = document.querySelector(`.au-form[data-vista="${nombre}"] input`);
  if (primero) primero.focus();
}

auTabs.forEach((tab) => tab.addEventListener("click", () => mostrarVista(tab.dataset.vista)));
document.querySelectorAll("[data-ir]").forEach((b) => b.addEventListener("click", () => mostrarVista(b.dataset.ir)));

async function enviarForm(form, url, alOk) {
  if (!form.checkValidity()) {
    form.reportValidity();
    return;
  }
  const btn = form.querySelector(".au-submit");
  const texto = btn.textContent;
  btn.disabled = true;
  btn.textContent = t("Un momento…");
  auAviso.hidden = true;
  try {
    const res = await fetch(url, { method: "POST", body: new FormData(form) });
    const datos = await res.json().catch(() => ({}));
    if (!res.ok) throw Object.assign(new Error(datos.error || t("Ocurrió un error. Probá de nuevo.")), { datos });
    alOk(datos);
  } catch (err) {
    avisar(err.message, true);
    if (err.datos && err.datos.sin_verificar) ofrecerReenvio(form.email.value);
  } finally {
    btn.disabled = false;
    btn.textContent = texto;
  }
}

function ofrecerReenvio(email) {
  const b = document.createElement("button");
  b.type = "button";
  b.className = "au-link";
  b.textContent = t("Reenviar mail de confirmación");
  b.addEventListener("click", async () => {
    const fd = new FormData();
    fd.append("accion", "reenviar");
    fd.append("email", email);
    const res = await fetch("php/auth/recuperar.php", { method: "POST", body: fd });
    const datos = await res.json().catch(() => ({}));
    avisar(datos.mensaje || datos.error || t("Listo."), !res.ok, datos.link_prueba);
  });
  auAviso.append(document.createElement("br"), b);
}

document.getElementById("au-ingresar").addEventListener("submit", (e) => {
  e.preventDefault();
  enviarForm(e.target, "php/auth/login.php", (datos) => {
    // si venía de una página del sitio (ej. login.php?volver=presupuestos.php), vuelvo ahí
    const volver = new URLSearchParams(location.search).get("volver") || "";
    document.location.href = /^[a-z-]+\.php$/.test(volver) && datos.usuario.rol === "admin" ? volver : datos.destino;
  });
});

document.getElementById("au-registro").addEventListener("submit", (e) => {
  e.preventDefault();
  enviarForm(e.target, "php/auth/registro.php", (datos) => {
    avisar(datos.mensaje, false, datos.link_prueba);
    e.target.reset();
    mostrarVista("ingresar");
  });
});

document.getElementById("au-recuperar").addEventListener("submit", (e) => {
  e.preventDefault();
  enviarForm(e.target, "php/auth/recuperar.php", (datos) => avisar(datos.mensaje, false, datos.link_prueba));
});

// botones sociales: solo los configurados
fetch("php/auth/proveedores.php")
  .then((r) => r.json())
  .then((activos) => {
    let alguno = false;
    document.querySelectorAll(".au-social__btn").forEach((a) => {
      a.hidden = !activos[a.dataset.p];
      alguno ||= !a.hidden;
    });
    document.getElementById("au-social").hidden = !alguno;
  })
  .catch(() => {});

// avisos que vienen en la URL (?aviso=...)
const aviso = new URLSearchParams(location.search).get("aviso");
if (AVISOS[aviso]) avisar(...AVISOS[aviso]);
if (new URLSearchParams(location.search).get("vista") === "registro") mostrarVista("registro");
