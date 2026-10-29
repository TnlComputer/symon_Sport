/* Agenda de turnos (administrador) */
const agFiltros = document.getElementById("ag-filtros");
const agLista = document.getElementById("ag-lista");
const agError = document.getElementById("ag-error");

const ESTADOS = { pendiente: "Pendiente", confirmado: "Confirmado", atendido: "Entregado", cancelado: "Cancelado" };
let servicios = [];

const isoLocal = (f) =>
  `${f.getFullYear()}-${String(f.getMonth() + 1).padStart(2, "0")}-${String(f.getDate()).padStart(2, "0")}`;
const aFecha = (iso) => {
  const [y, m, d] = iso.split("-").map(Number);
  return new Date(y, m - 1, d);
};
const fmtDia = (iso) => aFecha(iso).toLocaleDateString("es-AR", { weekday: "long", day: "numeric", month: "long" });
const fmtCorto = (iso) => aFecha(iso).toLocaleDateString("es-AR", { weekday: "short", day: "numeric", month: "short" });

const hoy = new Date();
const en14 = new Date();
en14.setDate(hoy.getDate() + 14);
agFiltros.desde.value = isoLocal(hoy);
agFiltros.hasta.value = isoLocal(en14);

// "011 15-1234-5678" / "11 1234-5678" -> "5491112345678"
const numeroWhatsApp = (digitos) => {
  if (digitos.startsWith("54")) return digitos;
  let n = digitos.replace(/^0/, "");
  if (/^1115\d{8}$/.test(n)) n = "11" + n.slice(4);
  return "549" + n;
};

const el = (tag, props = {}, ...hijos) => {
  const e = Object.assign(document.createElement(tag), props);
  hijos.forEach((h) => e.append(h));
  return e;
};

const mostrarError = (msg) => {
  agError.textContent = msg;
  agError.hidden = false;
};

const post = async (datos) => {
  const res = await fetch("./php/turnos_admin.php", { method: "POST", body: datos });
  const json = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(json.error || "No se pudo guardar");
  return json;
};

async function cargarAgenda() {
  agError.hidden = true;
  agLista.innerHTML = `<p class="tn-cargando">Cargando…</p>`;
  const params = new URLSearchParams({ desde: agFiltros.desde.value, hasta: agFiltros.hasta.value });

  try {
    const res = await fetch(`./php/turnos_admin.php?${params}`);
    const datos = await res.json();
    if (!res.ok) throw new Error(datos.error || "Error al cargar");
    servicios = datos.servicios;
    llenarServiciosAlta();

    const filtro = agFiltros.estado.value;
    const turnos = datos.turnos.filter((t) =>
      filtro === "activos" ? t.estado === "pendiente" || t.estado === "confirmado" : !filtro || t.estado === filtro
    );

    agLista.innerHTML = "";
    const desde = agFiltros.desde.value;

    // autos que ingresaron antes del período y siguen en el taller
    const enTaller = turnos.filter((t) => t.fecha < desde);
    const nuevos = turnos.filter((t) => t.fecha >= desde);

    if (!turnos.length) {
      agLista.innerHTML = `<p class="tn-cargando">No hay turnos en ese período.</p>`;
      return;
    }

    if (enTaller.length) {
      agLista.append(el("h2", { className: "ag-dia", textContent: "En el taller (ingresaron antes)" }));
      const grupo = el("div", { className: "ag-grupo" });
      enTaller.forEach((t) => grupo.append(tarjeta(t)));
      agLista.append(grupo);
    }

    let diaActual = "";
    let grupo;
    nuevos.forEach((t) => {
      if (t.fecha !== diaActual) {
        diaActual = t.fecha;
        agLista.append(el("h2", { className: "ag-dia", textContent: fmtDia(t.fecha) }));
        grupo = el("div", { className: "ag-grupo" });
        agLista.append(grupo);
      }
      grupo.append(tarjeta(t));
    });
  } catch (e) {
    agLista.innerHTML = "";
    mostrarError(e.message);
  }
}

function tarjeta(t) {
  const card = el("article", { className: `ag-turno ag-turno--${t.estado}` });

  const varios = t.fecha_fin !== t.fecha;
  const hora = el("div", { className: "ag-turno__hora" },
    el("span", { textContent: t.hora_inicio }),
    el("small", { className: "ag-turno__salida", textContent: `Salida: ${varios ? fmtCorto(t.fecha_fin) + " " : ""}${t.hora_fin}` }));

  const cuerpo = el("div", { className: "ag-turno__cuerpo" });
  const titulo = el("strong", { textContent: t.servicio });
  if (t.origen === "taller") titulo.append(el("em", { className: "ag-tag", textContent: "Sin turno" }));
  cuerpo.append(titulo, el("span", { className: "ag-turno__patente", textContent: t.patente }));
  if (t.vehiculo) cuerpo.append(el("span", { textContent: t.vehiculo }));
  cuerpo.append(el("span", { textContent: t.nombre }));

  const contacto = el("div", { className: "ag-turno__contacto" });
  const tel = (t.telefono || "").replace(/[^0-9]/g, "");
  if (tel) {
    contacto.append(
      el("a", { href: `tel:${tel}`, textContent: t.telefono }),
      el("a", { href: `https://wa.me/${numeroWhatsApp(tel)}`, target: "_blank", rel: "noopener", textContent: "WhatsApp" })
    );
  }
  if (t.email) contacto.append(el("a", { href: `mailto:${t.email}`, textContent: t.email }));
  cuerpo.append(contacto);
  if (t.comentario) cuerpo.append(el("p", { className: "ag-turno__coment", textContent: t.comentario }));
  if (t.motivo_demora) cuerpo.append(el("p", { className: "ag-turno__demora", textContent: `Demora: ${t.motivo_demora}` }));

  // acciones: estado + extender salida
  const acciones = el("div", { className: "ag-turno__acciones" });
  const sel = el("select", { className: "ag-turno__estado" });
  sel.setAttribute("aria-label", "Estado del turno");
  Object.entries(ESTADOS).forEach(([valor, texto]) =>
    sel.append(el("option", { value: valor, textContent: texto, selected: valor === t.estado }))
  );
  sel.addEventListener("change", async () => {
    const fd = new FormData();
    fd.append("accion", "estado");
    fd.append("id", t.id_turno);
    fd.append("estado", sel.value);
    sel.disabled = true;
    try {
      await post(fd);
      card.className = `ag-turno ag-turno--${sel.value}`;
    } catch (e) {
      mostrarError(e.message);
    } finally {
      sel.disabled = false;
    }
  });

  const btnExt = el("button", { type: "button", className: "btn btn--ghost ag-btn", textContent: "Extender salida" });
  btnExt.addEventListener("click", () => {
    btnExt.hidden = true;
    cuerpo.append(formExtender(t, () => (btnExt.hidden = false)));
  });
  acciones.append(sel, btnExt);

  card.append(hora, cuerpo, acciones);
  return card;
}

function formExtender(t, alCerrar) {
  const f = el("form", { className: "ag-extender" },
    el("label", {}, "Nueva salida", el("input", { type: "date", name: "fecha_fin", value: t.fecha_fin, required: true })),
    el("label", {}, "Hora", el("input", { type: "time", name: "hora_fin", value: t.hora_fin, required: true, step: 900 })),
    el("label", { className: "ag-crece" }, "Motivo",
      el("input", { name: "motivo_demora", value: t.motivo_demora || "", maxLength: 255, placeholder: "Esperando repuesto, trabajo adicional…" })),
    el("button", { type: "submit", className: "btn btn--primary ag-btn", textContent: "Guardar" }),
    el("button", { type: "button", className: "btn btn--ghost ag-btn", textContent: "Cancelar", onclick: () => { f.remove(); alCerrar(); } })
  );
  f.addEventListener("submit", async (e) => {
    e.preventDefault();
    const fd = new FormData(f);
    fd.append("accion", "extender");
    fd.append("id", t.id_turno);
    try {
      await post(fd);
      cargarAgenda();
    } catch (err) {
      mostrarError(err.message);
    }
  });
  return f;
}

/* ---------- Alta de auto sin turno ---------- */
const formAlta = document.getElementById("ag-alta");

function llenarServiciosAlta() {
  const sel = formAlta.id_servicio;
  if (sel.options.length) return;
  servicios.forEach((s) => sel.append(el("option", { value: s.id_servicio, textContent: s.nombre })));
}

document.getElementById("ag-alta-abrir").addEventListener("click", (e) => {
  formAlta.hidden = !formAlta.hidden;
  e.currentTarget.setAttribute("aria-expanded", String(!formAlta.hidden));
  if (!formAlta.hidden) {
    const ahora = new Date();
    formAlta.fecha.value ||= isoLocal(ahora);
    formAlta.fecha_fin.value ||= isoLocal(ahora);
    formAlta.patente.focus();
  }
});

formAlta.patente.addEventListener("input", (e) => {
  e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
});

formAlta.addEventListener("submit", async (e) => {
  e.preventDefault();
  const fd = new FormData(formAlta);
  fd.append("accion", "alta");
  try {
    await post(fd);
    formAlta.reset();
    formAlta.hidden = true;
    cargarAgenda();
  } catch (err) {
    mostrarError(err.message);
  }
});

agFiltros.addEventListener("submit", (e) => {
  e.preventDefault();
  cargarAgenda();
});
agFiltros.estado.addEventListener("change", cargarAgenda);

cargarAgenda();
