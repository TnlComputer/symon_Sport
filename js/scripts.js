/************* Seguridad: token CSRF en todos los envíos ***************/
// El servidor rechaza cualquier POST sin este token (protege contra envíos desde otros sitios).
const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || "";
const fetchOriginal = window.fetch.bind(window);
window.fetch = (recurso, opciones = {}) => {
  const metodo = (opciones.method || "GET").toUpperCase();
  const url = new URL(typeof recurso === "string" ? recurso : recurso.url, location.href);
  if (metodo !== "GET" && url.origin === location.origin) {
    const cabeceras = new Headers(opciones.headers || {});
    cabeceras.set("X-CSRF-Token", CSRF);
    opciones = { ...opciones, headers: cabeceras, credentials: "same-origin" };
  }
  return fetchOriginal(recurso, opciones);
};

/************* Idioma ***************/
// El texto en castellano es la clave: t("Reservar turno"). Con datos: t("Hola, {0}", nombre).
// El diccionario del idioma elegido lo pone el servidor en <script id="ssp-textos"> (vacío en castellano).
const TEXTOS = JSON.parse(document.getElementById("ssp-textos")?.textContent || "{}");
const t = (texto, ...datos) => (TEXTOS[texto] ?? texto).replace(/\{(\d+)\}/g, (_, i) => datos[i] ?? "");
const IDIOMA = document.documentElement.lang || "es";
const LOCALE = { es: "es-AR", pt: "pt-BR", en: "en-US" }[IDIOMA] || "es-AR"; // fechas y números

/************* Modo claro / oscuro ***************/
document.querySelectorAll("[data-tema-elegir]").forEach((boton) =>
  boton.addEventListener("click", () => {
    const tema = boton.dataset.temaElegir;
    if (tema === "claro") document.documentElement.dataset.tema = "claro";
    else delete document.documentElement.dataset.tema;
    document.querySelectorAll("[data-tema-elegir]").forEach((b) => b.setAttribute("aria-pressed", String(b === boton)));
    document.cookie = `ssp_tema=${tema}; path=/; max-age=31536000; samesite=lax${location.protocol === "https:" ? "; secure" : ""}`;
  })
);

/* Menu */
const mainMenuElement = document.getElementById("main-menu");

/************* MENU oculto o muestro en Moviles ***************/
const toggleMenuElement = document.getElementById("toggle-menu");

const cerrarMenu = () => {
  mainMenuElement.classList.remove("main-menu--show");
  toggleMenuElement.setAttribute("aria-expanded", "false");
  toggleMenuElement.setAttribute("aria-label", t("Abrir menú"));
};

if (toggleMenuElement && mainMenuElement) {
  toggleMenuElement.addEventListener("click", () => {
    const abierto = mainMenuElement.classList.toggle("main-menu--show");
    toggleMenuElement.setAttribute("aria-expanded", String(abierto));
    toggleMenuElement.setAttribute("aria-label", abierto ? t("Cerrar menú") : t("Abrir menú"));
  });

  // cierro el menu al tocar fuera o con Escape
  document.addEventListener("click", (e) => {
    if (!e.target.closest(".main-nav")) cerrarMenu();
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") cerrarMenu();
  });
}

/************* Mostrar / ocultar contraseña (todos los campos) ***************/
const OJO_ABIERTO =
  '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z"/><circle cx="12" cy="12" r="3"/></svg>';
const OJO_CERRADO =
  '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"/><path d="M10.6 5.1A10.5 10.5 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4.1M6.6 6.6A17.6 17.6 0 0 0 2 12s3.6 7 10 7a9.7 9.7 0 0 0 5.4-1.6"/><path d="M9.9 9.9a3 3 0 0 0 4.2 4.2"/></svg>';

document.querySelectorAll('input[type="password"]').forEach((input) => {
  const envoltorio = document.createElement("span");
  envoltorio.className = "campo-clave";
  input.parentNode.insertBefore(envoltorio, input);
  envoltorio.appendChild(input);

  const boton = document.createElement("button");
  boton.type = "button";
  boton.className = "campo-clave__ver";
  boton.setAttribute("aria-label", t("Mostrar contraseña"));
  boton.innerHTML = OJO_ABIERTO;
  boton.addEventListener("click", () => {
    const mostrar = input.type === "password";
    input.type = mostrar ? "text" : "password";
    boton.innerHTML = mostrar ? OJO_CERRADO : OJO_ABIERTO;
    boton.setAttribute("aria-label", mostrar ? t("Ocultar contraseña") : t("Mostrar contraseña"));
  });
  envoltorio.appendChild(boton);
});

/************* Cerrar sesión ***************/
// El menú (y qué páginas puede ver cada rol) lo arma el servidor.
document.querySelectorAll("[data-salir]").forEach((enlace) =>
  enlace.addEventListener("click", async (e) => {
    e.preventDefault();
    await fetch("./php/desconecto.php", { method: "POST" }).catch(() => {});
    document.location.href = "login.php";
  })
);

/************* Idioma y tema (globo) ***************/
const ajustes = document.getElementById("ajustes");
if (ajustes) {
  document.addEventListener("click", (e) => {
    if (!e.target.closest("#ajustes")) ajustes.open = false;
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") ajustes.open = false;
  });
}

/************* Menú de la cuenta (escritorio) ***************/
const cuentaBoton = document.getElementById("cuenta-boton");
const cuentaMenu = document.getElementById("cuenta-menu");

if (cuentaBoton && cuentaMenu) {
  const abrirCuenta = (abrir) => {
    cuentaMenu.hidden = !abrir;
    cuentaBoton.setAttribute("aria-expanded", String(abrir));
  };
  cuentaBoton.addEventListener("click", () => abrirCuenta(cuentaMenu.hidden));
  document.addEventListener("click", (e) => {
    if (!e.target.closest(".cuenta")) abrirCuenta(false);
  });
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && !cuentaMenu.hidden) {
      abrirCuenta(false);
      cuentaBoton.focus();
    }
  });
}
