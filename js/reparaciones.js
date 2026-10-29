/* Reparaciones (administrador): tablero en el taller -> listo para retirar -> entregado */
const API = "./php/reparaciones_api.php";
const $ = (id) => document.getElementById(id);
const reAviso = $("re-aviso");
const form = $("re-form");
const formIngreso = $("re-form-ingreso");
let reparaciones = [];
let actual = null; // reparación abierta en el detalle
let presuElegido = null;
let avisoTimer;

const el = (tag, props = {}, ...hijos) => {
  const e = Object.assign(document.createElement(tag), props);
  hijos.forEach((h) => h !== null && h !== undefined && h !== false && e.append(h));
  return e;
};
const boton = (texto, clase, accion) => {
  const b = el("button", { type: "button", className: clase, textContent: texto });
  b.addEventListener("click", accion);
  return b;
};
const pesos = (n) => "$ " + Number(n || 0).toLocaleString("es-AR", { maximumFractionDigits: 2 });
const kms = (n) => `${Number(n).toLocaleString("es-AR")} km`;
const hoyISO = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};
const fmtFecha = (iso) => {
  if (!iso) return "";
  const [y, m, d] = iso.slice(0, 10).split("-").map(Number);
  return new Date(y, m - 1, d).toLocaleDateString("es-AR", { day: "numeric", month: "short", year: "numeric" });
};
const vehiculo = (r) => [r.marca, r.modelo].filter(Boolean).join(" ") || "Vehículo";
const saldo = (r) => Math.max(0, Number(r.total_presu || 0) - Number(r.senia_presu || 0));
const debeLavar = (r) => Number(r.lavado_presu) > 0;
const diasTexto = (n) => (Number(n) === 0 ? "hoy" : `hace ${n} ${Number(n) === 1 ? "día" : "días"}`);
const ESTADOS = { taller: "En el taller", listo: "Listo para retirar", entregado: "Entregado" };

function avisar(msg, error = false) {
  reAviso.textContent = msg;
  reAviso.classList.toggle("cf-aviso--error", error);
  reAviso.hidden = false;
  clearTimeout(avisoTimer);
  avisoTimer = setTimeout(() => (reAviso.hidden = true), 5000);
}

async function api(params, cuerpo) {
  const url = cuerpo ? API : `${API}?${new URLSearchParams(params)}`;
  const res = await fetch(url, cuerpo ? { method: "POST", body: cuerpo } : undefined);
  const r = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(r.error || "Ocurrió un error");
  return r;
}

const formData = (obj) => {
  const fd = new FormData();
  Object.entries(obj).forEach(([k, v]) => fd.append(k, v ?? ""));
  return fd;
};

const demorar = (fn, ms = 250) => {
  let t;
  return (...a) => {
    clearTimeout(t);
    t = setTimeout(() => fn(...a), ms);
  };
};

// WhatsApp del cliente (celulares argentinos: 54 9 + característica sin 0 + número sin 15)
function whatsapp(r, texto) {
  const cel = String(r.celular || "").replace(/\D/g, "");
  if (!cel) return null;
  const numero = cel.startsWith("54") ? cel : "549" + cel.replace(/^0/, "").replace(/^(\d{2,4})15/, "$1");
  return `https://wa.me/${numero}?text=${encodeURIComponent(texto)}`;
}

/* =================== TABLERO =================== */
function mostrar(panel) {
  $("re-tablero").hidden = panel !== "tablero";
  $("re-ingreso").hidden = panel !== "ingreso";
  $("re-detalle").hidden = panel !== "detalle";
  if (panel === "tablero") history.replaceState(null, "", "reparaciones.php");
  window.scrollTo({ top: 0, behavior: "smooth" });
}

async function cargar() {
  try {
    ({ reparaciones } = await api({ accion: "listar", q: $("re-q").value.trim(), dias: $("re-dias").value }));
  } catch (e) {
    avisar(e.message, true);
    return;
  }
  ["taller", "listo", "entregado"].forEach((estado) => {
    const lista = $("re-" + estado);
    const filas = reparaciones.filter((r) => r.estado === estado);
    $("re-n-" + estado).textContent = filas.length;
    lista.replaceChildren(...filas.map(tarjeta));
    if (!filas.length) lista.append(el("p", { className: "ps-sin", textContent: estado === "taller" ? "No hay vehículos en el taller." : "Ninguno." }));
  });
}

function tarjeta(r) {
  const chips = el("div", { className: "ps-card__estado" });
  if (debeLavar(r)) {
    chips.append(el("span", { className: "ps-chip ps-chip--lavado" + (r.lavado_hecho ? " ps-chip--ok" : ""), textContent: r.lavado_hecho ? "Lavado ✓" : "Falta lavar" }));
  }
  if (r.estado === "listo") {
    chips.append(el("span", { className: "ps-chip" + (r.aviso_listo ? " ps-chip--ok" : ""), textContent: r.aviso_listo ? "Cliente avisado" : "Avisar al cliente" }));
  }
  if (r.estado !== "entregado" && saldo(r) > 0) chips.append(el("span", { className: "ps-chip", textContent: `Saldo ${pesos(saldo(r))}` }));

  const cuando = r.estado === "entregado" ? `Entregado el ${fmtFecha(r.fecha_entrega)}`
    : r.estado === "listo" ? `Listo · ingresó ${diasTexto(r.dias)}`
    : `Ingresó ${diasTexto(r.dias)}`;
  const card = el("article", { className: `ps-card re-card re-card--${r.estado}` + (r.estado === "taller" && Number(r.dias) > 7 ? " re-card--demorado" : "") },
    el("div", { className: "ps-card__cab" },
      el("span", { className: "tn-placa", textContent: r.patente || "—" }),
      el("strong", { textContent: vehiculo(r) })),
    el("p", { className: "re-card__cliente", textContent: r.empresa_nyp || "Sin cliente" }),
    r.descripcion_presu ? el("p", { className: "ps-card__desc", textContent: r.descripcion_presu }) : null,
    chips.children.length ? chips : null,
    el("div", { className: "ps-card__pie" },
      el("span", { textContent: cuando }),
      el("span", { textContent: r.km ? kms(r.km) : "" })));
  card.tabIndex = 0;
  card.addEventListener("click", () => abrir(r.id_trabajo));
  card.addEventListener("keydown", (e) => e.key === "Enter" && abrir(r.id_trabajo));
  return card;
}

$("re-q").addEventListener("input", demorar(cargar, 300));
$("re-dias").addEventListener("change", cargar);
document.querySelectorAll("[data-volver]").forEach((b) => b.addEventListener("click", () => {
  mostrar("tablero");
  cargar();
}));

/* =================== INGRESO =================== */
function abrirIngreso() {
  presuElegido = null;
  formIngreso.reset();
  formIngreso.hidden = true;
  $("re-presu-elegido").hidden = true;
  $("re-presu-buscar").hidden = false;
  $("re-presu-q").value = "";
  $("re-error-ingreso").hidden = true;
  buscarPresupuestos();
  mostrar("ingreso");
  $("re-presu-q").focus();
}
$("re-ingresar").addEventListener("click", abrirIngreso);

async function buscarPresupuestos() {
  const lista = $("re-presu-lista");
  try {
    const { presupuestos } = await api({ accion: "pendientes", q: $("re-presu-q").value.trim() });
    lista.replaceChildren(...presupuestos.map((p) => {
      const b = el("button", { type: "button", className: "ps-res" },
        el("strong", {}, `#${p.id_presu} · `, el("span", { className: "tn-placa cl-placa-chica", textContent: p.patente }), ` ${p.empresa_nyp}`),
        el("small", { textContent: [vehiculo(p), p.descripcion_presu, pesos(p.total_presu)].filter(Boolean).join(" · ") }));
      b.addEventListener("click", () => elegirPresupuesto(p));
      return el("li", {}, b);
    }));
    if (!presupuestos.length) lista.append(el("li", { className: "ps-sin", textContent: "No hay presupuestos pendientes con esa búsqueda." }));
  } catch (e) {
    avisar(e.message, true);
  }
}
$("re-presu-q").addEventListener("input", demorar(buscarPresupuestos, 250));

function elegirPresupuesto(p) {
  presuElegido = p;
  $("re-presu-texto").replaceChildren(
    el("strong", {}, el("span", { className: "tn-placa cl-placa-chica", textContent: p.patente }), ` ${vehiculo(p)} · ${p.empresa_nyp}`),
    el("p", { className: "fv-ayuda", textContent: `Presupuesto #${p.id_presu} · ${p.descripcion_presu || ""} · ${pesos(p.total_presu)}` }));
  $("re-presu-elegido").hidden = false;
  $("re-presu-buscar").hidden = true;
  formIngreso.hidden = false;
  formIngreso.fecha.value = hoyISO();
  formIngreso.fecha.max = hoyISO();
  $("re-km-ayuda").hidden = !p.km_actual;
  $("re-km-ayuda").textContent = p.km_actual ? `Último kilometraje registrado: ${kms(p.km_actual)}.` : "";
  formIngreso.km.focus();
}
$("re-presu-cambiar").addEventListener("click", () => {
  presuElegido = null;
  formIngreso.hidden = true;
  $("re-presu-elegido").hidden = true;
  $("re-presu-buscar").hidden = false;
  $("re-presu-q").focus();
});

formIngreso.addEventListener("submit", async (e) => {
  e.preventDefault();
  const error = $("re-error-ingreso");
  error.hidden = true;
  const km = Number(formIngreso.km.value.replace(/\D/g, ""));
  if (presuElegido.km_actual && km && km < Number(presuElegido.km_actual)
    && !confirm(`El kilometraje es menor al último registrado (${kms(presuElegido.km_actual)}). ¿Ingresarlo igual?`)) return;
  try {
    const r = await api(null, formData({ ...Object.fromEntries(new FormData(formIngreso)), accion: "ingresar", id_presu: presuElegido.id_presu }));
    avisar(`${presuElegido.patente} ingresó al taller.`);
    abrir(r.id);
  } catch (err) {
    error.textContent = err.message;
    error.hidden = false;
  }
});

/* =================== DETALLE =================== */
async function abrir(id) {
  try {
    ({ reparacion: actual } = await api({ accion: "detalle", id }));
  } catch (e) {
    avisar(e.message, true);
    return;
  }
  const r = actual;
  history.replaceState(null, "", `reparaciones.php?id=${r.id_trabajo}`);
  $("re-d-patente").textContent = r.patente;
  $("re-d-vehiculo").textContent = [vehiculo(r), r.anio].filter(Boolean).join(" ");
  $("re-d-estado").textContent = ESTADOS[r.estado];
  $("re-d-estado").className = "ps-chip" + (r.estado === "taller" ? "" : " ps-chip--ok");
  $("re-d-sub").replaceChildren(
    el("a", { className: "cf-link", href: `clientes.php?id=${r.id_cliente}`, textContent: r.empresa_nyp || "Cliente" }),
    r.celular ? ` · ${r.celular}` : "",
    ` · Reparación #${r.id_trabajo}`);
  $("re-d-presu").replaceChildren(
    el("a", { className: "cf-link", href: `presupuestos.php?id=${r.presupuesto}`, textContent: `Presupuesto #${r.presupuesto}` }),
    ` · ${r.descripcion_presu || ""}`);
  ["fecha_trab", "km", "factura", "observaciones", "trabajo_realizado"].forEach((k) => (form[k].value = r[k] ?? ""));
  form.fecha_trab.max = hoyISO();
  $("re-error").hidden = true;
  pintarEntrega();
  pintarZonaBaja();
  mostrar("detalle");
}

form.addEventListener("submit", async (e) => {
  e.preventDefault();
  const error = $("re-error");
  error.hidden = true;
  try {
    await api(null, formData({ ...Object.fromEntries(new FormData(form)), accion: "guardar", id: actual.id_trabajo }));
    avisar("Reparación guardada.");
    abrir(actual.id_trabajo);
  } catch (err) {
    error.textContent = err.message;
    error.hidden = false;
  }
});

async function accion(datos, mensaje) {
  try {
    await api(null, formData({ ...datos, id: actual.id_trabajo }));
    if (mensaje) avisar(mensaje);
    await abrir(actual.id_trabajo);
  } catch (e) {
    avisar(e.message, true);
  }
}

// Control de entrega: terminado -> lavado -> aviso al cliente -> cobro y entrega
function pintarEntrega() {
  const r = actual;
  const cont = $("re-entrega");
  const paso = (hecho, titulo, detalle, ...controles) =>
    el("li", { className: "re-paso" + (hecho ? " is-hecho" : "") },
      el("span", { className: "re-paso__marca", textContent: hecho ? "✓" : "" }),
      el("div", { className: "re-paso__cuerpo" }, el("strong", { textContent: titulo }), detalle ? el("small", { textContent: detalle }) : null, ...controles));
  const pasos = el("ol", { className: "re-pasos" });

  pasos.append(paso(true, "Ingresó al taller", `${fmtFecha(r.fecha_trab)}${r.km ? " · " + kms(r.km) : ""}`));

  const entregado = r.estado === "entregado";
  pasos.append(paso(!!r.listo, "Trabajo terminado", r.listo ? fmtFecha(r.listo) : "Cuando esté listo para retirar",
    entregado ? null : r.listo
      ? boton("Volver a “en el taller”", "au-link", () => accion({ accion: "listo", listo: 0 }))
      : boton("Marcar como listo", "btn btn--primary ag-btn", () => accion({ accion: "listo", listo: 1 }, "Listo para retirar."))));

  if (debeLavar(r)) {
    pasos.append(paso(!!r.lavado_hecho, "Lavado antes de entregar", r.lavado_hecho ? fmtFecha(r.lavado_hecho) : "Incluido en el presupuesto",
      entregado ? null : boton(r.lavado_hecho ? "Desmarcar" : "Marcar lavado", r.lavado_hecho ? "au-link" : "btn btn--ghost ag-btn",
        () => accion({ accion: "lavado" }))));
  }

  const texto = `¡Hola ${r.empresa_nyp || ""}! Te escribimos de Symon Sport: tu ${vehiculo(r)} (${r.patente}) ya está listo para retirar.`
    + (saldo(r) > 0 ? ` El saldo es de ${pesos(saldo(r))}.` : "") + " ¡Te esperamos!";
  const wa = whatsapp(r, texto);
  let avisoCtrl = null;
  if (!entregado && r.listo) {
    if (wa) {
      avisoCtrl = el("a", { className: "btn btn--whatsapp ag-btn", href: wa, target: "_blank", rel: "noopener", textContent: r.aviso_listo ? "Volver a avisar por WhatsApp" : "Avisar por WhatsApp" });
      avisoCtrl.addEventListener("click", () => api(null, formData({ accion: "avisado", id: r.id_trabajo })).then(() => abrir(r.id_trabajo)).catch(() => {}));
    } else {
      avisoCtrl = el("span", {}, "El cliente no tiene celular cargado. ", boton("Marcar como avisado", "au-link", () => accion({ accion: "avisado" })));
    }
  }
  pasos.append(paso(!!r.aviso_listo, "Aviso al cliente", r.aviso_listo ? `Avisado el ${fmtFecha(r.aviso_listo)}` : (r.listo ? "Avisale que puede pasar a retirarlo" : "Cuando el trabajo esté terminado"), avisoCtrl));

  // cobro y entrega
  const resumen = el("dl", { className: "re-cobro" },
    el("dt", { textContent: "Total" }), el("dd", { textContent: pesos(r.total_presu) }),
    el("dt", { textContent: "Seña" }), el("dd", { textContent: pesos(r.senia_presu) }),
    el("dt", { className: "re-cobro__saldo", textContent: "Saldo" }), el("dd", { className: "re-cobro__saldo", textContent: pesos(saldo(r)) }));
  let entregaCtrl;
  if (entregado) {
    entregaCtrl = el("div", { className: "re-entregado" },
      el("small", { textContent: [r.cobrado !== null ? `Cobrado ${pesos(r.cobrado)}` : "", r.factura ? `Factura ${r.factura}` : ""].filter(Boolean).join(" · ") }),
      boton("Deshacer entrega", "au-link", () => confirm("¿Deshacer la entrega? Vuelve a “listo para retirar”.") && accion({ accion: "reabrir" }, "Entrega deshecha.")));
  } else {
    const fecha = el("input", { type: "date", className: "ps-input", value: hoyISO(), max: hoyISO() });
    const cobrado = el("input", { className: "ps-input", inputMode: "decimal", value: saldo(r) || "", autocomplete: "off" });
    const factura = el("input", { className: "ps-input", maxLength: 30, value: r.factura || "" });
    const entregar = boton("Entregar vehículo", "btn btn--accent btn--lg re-entregar", async () => {
      const faltan = [];
      if (!r.listo) faltan.push("todavía no está marcado como terminado");
      if (debeLavar(r) && !r.lavado_hecho) faltan.push("falta el lavado");
      const cobradoNum = Number(String(cobrado.value).replace(/\./g, "").replace(",", ".")) || 0;
      if (cobradoNum < saldo(r)) faltan.push(`se cobra ${pesos(cobradoNum)} y el saldo es ${pesos(saldo(r))}`);
      if (faltan.length && !confirm(`Atención: ${faltan.join(", ")}.\n\n¿Entregar igual?`)) return;
      accion({ accion: "entregar", fecha_entrega: fecha.value, cobrado: cobrado.value, factura: factura.value }, `${r.patente} entregado.`);
    });
    entregaCtrl = el("div", { className: "re-entregar__form" },
      el("label", { className: "fv-campo" }, el("span", { textContent: "Fecha de entrega" }), fecha),
      el("label", { className: "fv-campo" }, el("span", { textContent: "Cobrado ($)" }), cobrado),
      el("label", { className: "fv-campo" }, el("span", { textContent: "Factura" }), factura),
      entregar);
  }
  pasos.append(paso(entregado, "Cobro y entrega", entregado ? `Entregado el ${fmtFecha(r.fecha_entrega)}` : null, resumen, entregaCtrl));

  cont.replaceChildren(el("h3", { className: "ps-paso__titulo", textContent: "Control de entrega" }), pasos);
}

function pintarZonaBaja() {
  const cont = $("re-zona-baja");
  cont.innerHTML = "";
  if (actual.estado === "entregado") return;
  cont.append(boton("Anular este ingreso", "cl-dar-baja", async () => {
    if (!confirm(`¿Anular el ingreso de ${actual.patente}?\n\nEs para un ingreso cargado por error: no se borra nada y el presupuesto #${actual.presupuesto} vuelve a quedar pendiente.`)) return;
    try {
      await api(null, formData({ accion: "anular", id: actual.id_trabajo }));
      avisar("Ingreso anulado. El presupuesto volvió a pendiente.");
      mostrar("tablero");
      cargar();
    } catch (e) {
      avisar(e.message, true);
    }
  }));
}

/* =================== INICIO =================== */
cargar();
const pedido = new URLSearchParams(location.search);
if (pedido.get("id")) abrir(pedido.get("id"));
else if (pedido.has("ingresar")) abrirIngreso();
