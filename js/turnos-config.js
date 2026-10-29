/* Configuración de turnos (administrador) */
const URL_CONFIG = "./php/turnos_config_admin.php";
const DIAS = ["", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"];
const cfAviso = document.getElementById("cf-aviso");
let avisoTimer;

const avisar = (msg, error = false) => {
  cfAviso.textContent = msg;
  cfAviso.classList.toggle("cf-aviso--error", error);
  cfAviso.hidden = false;
  clearTimeout(avisoTimer);
  avisoTimer = setTimeout(() => (cfAviso.hidden = true), 3500);
};

const enviar = async (accion, datos) => {
  const fd = datos instanceof FormData ? datos : new FormData();
  if (!(datos instanceof FormData)) Object.entries(datos || {}).forEach(([k, v]) => fd.append(k, v));
  fd.append("accion", accion);
  const res = await fetch(URL_CONFIG, { method: "POST", body: fd });
  const json = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(json.error || "No se pudo guardar");
  return json;
};

const el = (tag, props = {}, ...hijos) => {
  const e = Object.assign(document.createElement(tag), props);
  hijos.forEach((h) => e.append(h));
  return e;
};

async function cargar() {
  try {
    const res = await fetch(URL_CONFIG);
    const datos = await res.json();
    if (!res.ok) throw new Error(datos.error || "Error al cargar");
    pintarGeneral(datos.config);
    pintarServicios(datos.servicios);
    pintarHorarios(datos.horarios);
    pintarBloqueos(datos.bloqueos);
  } catch (e) {
    avisar(e.message, true);
  }
}

/* ---------- General ---------- */
const formGeneral = document.getElementById("cf-general");

function pintarGeneral(config) {
  config.forEach(({ clave, valor }) => {
    if (formGeneral.elements[clave]) formGeneral.elements[clave].value = valor;
  });
}

formGeneral.addEventListener("submit", async (e) => {
  e.preventDefault();
  try {
    await enviar("config", new FormData(formGeneral));
    avisar("Configuración guardada.");
  } catch (err) {
    avisar(err.message, true);
  }
});

/* ---------- Servicios ---------- */
const contServicios = document.getElementById("cf-servicios");

function filaServicio(s = { id_servicio: "", nombre: "", descripcion: "", duracion_min: 60, margen_min: 0, activo: 1, orden: 0 }) {
  const horas = (min) => Math.round((Number(min) / 60) * 100) / 100;
  const f = el("form", { className: "cf-servicio" });
  f.append(
    el("input", { type: "hidden", name: "id_servicio", value: s.id_servicio }),
    el("label", { className: "cf-crece" }, "Servicio",
      el("input", { name: "nombre", value: s.nombre, required: true, maxLength: 100 })),
    el("label", { className: "cf-crece" }, "Descripción",
      el("input", { name: "descripcion", value: s.descripcion || "", maxLength: 255 })),
    el("label", { className: "cf-corto" }, "Horas trabajo",
      el("input", { type: "number", name: "horas", value: horas(s.duracion_min), min: 0.25, max: 80, step: 0.25, required: true })),
    el("label", { className: "cf-corto" }, "Margen (h)",
      el("input", { type: "number", name: "margen_horas", value: horas(s.margen_min || 0), min: 0, max: 80, step: 0.25 })),
    el("label", { className: "cf-corto" }, "Orden",
      el("input", { type: "number", name: "orden", value: s.orden })),
    el("label", { className: "cf-check" },
      el("input", { type: "checkbox", name: "activo", value: "1", checked: Number(s.activo) === 1 }), "Activo"),
    el("button", { type: "submit", className: "btn btn--primary", textContent: s.id_servicio ? "Guardar" : "Crear" })
  );
  if (!Number(s.activo) && s.id_servicio) f.classList.add("is-inactivo");

  f.addEventListener("submit", async (e) => {
    e.preventDefault();
    try {
      await enviar("servicio", new FormData(f));
      avisar("Servicio guardado.");
      cargar();
    } catch (err) {
      avisar(err.message, true);
    }
  });
  return f;
}

function pintarServicios(servicios) {
  contServicios.innerHTML = "";
  servicios.forEach((s) => contServicios.appendChild(filaServicio(s)));
}

document.getElementById("cf-nuevo-servicio").addEventListener("click", () => {
  const f = filaServicio();
  contServicios.appendChild(f);
  f.nombre.focus();
});

/* ---------- Horarios ---------- */
const contHorarios = document.getElementById("cf-horarios");

function pintarHorarios(horarios) {
  contHorarios.innerHTML = "";
  for (let d = 1; d <= 7; d++) {
    const franjas = horarios.filter((h) => Number(h.dia_semana) === d);
    const fila = el("div", { className: "cf-dia" }, el("strong", { textContent: DIAS[d] }));
    const chips = el("div", { className: "cf-chips" });
    if (!franjas.length) chips.append(el("span", { className: "cf-cerrado", textContent: "Cerrado" }));
    franjas.forEach((h) => {
      const borrar = el("button", { type: "button", className: "cf-chip__x", textContent: "×", title: "Quitar franja" });
      borrar.setAttribute("aria-label", `Quitar ${DIAS[d]} ${h.hora_desde} a ${h.hora_hasta}`);
      borrar.addEventListener("click", async () => {
        if (!confirm(`¿Quitar ${DIAS[d]} de ${h.hora_desde} a ${h.hora_hasta}?`)) return;
        try {
          await enviar("horario_borrar", { id: h.id_horario });
          cargar();
        } catch (err) {
          avisar(err.message, true);
        }
      });
      chips.append(el("span", { className: "cf-chip" }, `${h.hora_desde} – ${h.hora_hasta}`, borrar));
    });
    fila.append(chips);
    contHorarios.append(fila);
  }
}

const formHorario = document.getElementById("cf-horario-form");
formHorario.addEventListener("submit", async (e) => {
  e.preventDefault();
  try {
    await enviar("horario_agregar", new FormData(formHorario));
    avisar("Franja agregada.");
    formHorario.hora_desde.value = "";
    formHorario.hora_hasta.value = "";
    cargar();
  } catch (err) {
    avisar(err.message, true);
  }
});

/* ---------- Bloqueos ---------- */
const contBloqueos = document.getElementById("cf-bloqueos");

const fmtFecha = (iso) => {
  const [y, m, d] = iso.split("-").map(Number);
  return new Date(y, m - 1, d).toLocaleDateString("es-AR", { weekday: "short", day: "numeric", month: "short", year: "numeric" });
};

function pintarBloqueos(bloqueos) {
  contBloqueos.innerHTML = "";
  if (!bloqueos.length) {
    contBloqueos.append(el("li", { className: "cf-vacio", textContent: "No hay cierres cargados." }));
    return;
  }
  bloqueos.forEach((b) => {
    const franja = b.hora_desde ? `${b.hora_desde} – ${b.hora_hasta}` : "Todo el día";
    const borrar = el("button", { type: "button", className: "btn btn--ghost cf-borrar", textContent: "Quitar" });
    borrar.addEventListener("click", async () => {
      if (!confirm(`¿Quitar el cierre del ${fmtFecha(b.fecha)}?`)) return;
      try {
        await enviar("bloqueo_borrar", { id: b.id_bloqueo });
        cargar();
      } catch (err) {
        avisar(err.message, true);
      }
    });
    contBloqueos.append(
      el("li", {},
        el("strong", { textContent: fmtFecha(b.fecha) }),
        el("span", { textContent: franja }),
        el("span", { className: "cf-motivo", textContent: b.motivo || "" }),
        borrar)
    );
  });
}

const formBloqueo = document.getElementById("cf-bloqueo-form");
formBloqueo.addEventListener("submit", async (e) => {
  e.preventDefault();
  try {
    await enviar("bloqueo_agregar", new FormData(formBloqueo));
    avisar("Cierre agregado.");
    formBloqueo.reset();
    cargar();
  } catch (err) {
    avisar(err.message, true);
  }
});

/* ---------- Importar feriados nacionales ---------- */
const formFeriados = document.getElementById("cf-feriados-form");
const anioActual = new Date().getFullYear();
[anioActual, anioActual + 1].forEach((a) => formFeriados.anio.append(el("option", { value: a, textContent: a })));

formFeriados.addEventListener("submit", async (e) => {
  e.preventDefault();
  const btn = formFeriados.querySelector("button");
  btn.disabled = true;
  btn.textContent = "Importando…";
  try {
    const { agregados } = await enviar("feriados_importar", new FormData(formFeriados));
    avisar(agregados ? `Se agregaron ${agregados} feriados.` : "No había feriados nuevos para agregar.");
    cargar();
  } catch (err) {
    avisar(err.message, true);
  } finally {
    btn.disabled = false;
    btn.textContent = "Importar feriados nacionales";
  }
});

cargar();
