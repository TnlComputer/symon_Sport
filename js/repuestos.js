/* Repuestos, proveedores, listas de precios, ganancias y aumentos (administrador) */
const URL_REP = "./php/repuestos_admin.php";
const $ = (id) => document.getElementById(id);
const rpAviso = $("rp-aviso");
const rpForm = $("rp-form");
let maestros = { config: { ganancia_repuestos_pct: 30 }, rubros: [], proveedores: [], importaciones: [], aumentos: [] };
let rpAvisoTimer;

const el = (tag, props = {}, ...hijos) => {
  const e = Object.assign(document.createElement(tag), props);
  hijos.forEach((h) => h !== null && h !== undefined && h !== false && e.append(h));
  return e;
};
const pesos = (n) => "$ " + Number(n).toLocaleString("es-AR", { minimumFractionDigits: 0, maximumFractionDigits: 2 });
const pct = (n) => `${Number(n).toLocaleString("es-AR", { maximumFractionDigits: 2 })}%`;
const fmtFecha = (iso) => (iso ? new Date(iso.replace(" ", "T")).toLocaleDateString("es-AR", { day: "numeric", month: "short", year: "numeric" }) : "");
const ORIGEN = { propio: "propio", rubro: "del rubro", proveedor: "del proveedor", general: "general" };

const rpAvisar = (msg, error = false) => {
  rpAviso.textContent = msg;
  rpAviso.classList.toggle("cf-aviso--error", error);
  rpAviso.hidden = false;
  clearTimeout(rpAvisoTimer);
  rpAvisoTimer = setTimeout(() => (rpAviso.hidden = true), 5000);
};

async function rpEnviar(accion, datos) {
  const fd = datos instanceof FormData ? datos : new FormData();
  if (!(datos instanceof FormData)) Object.entries(datos || {}).forEach(([k, v]) => fd.append(k, v ?? ""));
  fd.set("accion", accion);
  const res = await fetch(URL_REP, { method: "POST", body: fd });
  const json = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(json.error || "No se pudo guardar");
  return json;
}

async function rpLeer(params) {
  const res = await fetch(`${URL_REP}?${new URLSearchParams(params)}`);
  const json = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(json.error || "Error al cargar");
  return json;
}

/* =================== PESTAÑAS =================== */
const tabs = document.querySelectorAll(".rp-tabs .au-tab");
function mostrarPestana(nombre) {
  tabs.forEach((b) => {
    const activa = b.dataset.pestana === nombre;
    b.classList.toggle("is-activo", activa);
    b.setAttribute("aria-selected", String(activa));
  });
  document.querySelectorAll("[data-panel]").forEach((p) => (p.hidden = p.dataset.panel !== nombre));
  history.replaceState(null, "", nombre === "catalogo" ? "repuestos.php" : `repuestos.php#${nombre}`);
}
tabs.forEach((b) => b.addEventListener("click", () => mostrarPestana(b.dataset.pestana)));

/* =================== MAESTROS (rubros, proveedores, config) =================== */
function llenarSelects(clase, items, id, texto) {
  document.querySelectorAll(clase).forEach((sel) => {
    const actual = sel.value;
    sel.replaceChildren(new Option(sel.dataset.primero || "Todos", ""), ...items.map((i) => new Option(texto(i), i[id])));
    sel.value = items.some((i) => String(i[id]) === actual) ? actual : "";
  });
}

async function cargarMaestros() {
  try {
    maestros = await rpLeer({ accion: "maestros" });
  } catch (e) {
    rpAvisar(e.message, true);
    return;
  }
  llenarSelects(".rp-sel-rubro", maestros.rubros, "id_rubro", (r) => r.nombre);
  llenarSelects(".rp-sel-proveedor", maestros.proveedores, "id_proveedor", (p) => p.nombre);
  $("rp-lista-rubros").replaceChildren(...maestros.rubros.map((r) => el("option", { value: r.nombre })));
  const fc = $("rp-config");
  fc.ganancia_repuestos_pct.value = maestros.config.ganancia_repuestos_pct;
  fc.recargo_mo_repuesto_cliente_pct.value = maestros.config.recargo_mo_repuesto_cliente_pct;
  fc.lavado_precio.value = maestros.config.lavado_precio ?? 0;
  fc.lavado_por_defecto.checked = Number(maestros.config.lavado_por_defecto) === 1;
  pintarProveedores();
  pintarRubros();
  pintarHistoriales();
}

/* =================== CATÁLOGO =================== */
async function cargarCatalogo() {
  try {
    const datos = await rpLeer({
      accion: "listar", q: $("rp-q").value.trim(), id_rubro: $("rp-filtro-rubro").value,
      id_proveedor: $("rp-filtro-proveedor").value, bajas: $("rp-bajas").checked ? 1 : "",
    });
    pintarCatalogo(datos.repuestos);
    $("rp-limite").hidden = !datos.limite;
  } catch (e) {
    rpAvisar(e.message, true);
  }
}

function celda(texto, clase, etiqueta) {
  const td = el("td", { className: clase || "", textContent: texto });
  if (etiqueta) td.dataset.label = etiqueta;
  return td;
}

function pintarCatalogo(repuestos) {
  const cuerpo = $("rp-cuerpo");
  cuerpo.innerHTML = "";
  $("rp-vacio").hidden = repuestos.length > 0;
  repuestos.forEach((r) => {
    const acciones = el("td", { className: "rp-acciones" });
    const boton = (texto, clase, accion) => {
      const b = el("button", { type: "button", className: `btn ${clase} ag-btn`, textContent: texto });
      b.addEventListener("click", accion);
      acciones.append(b);
    };
    if (r.baja) boton("Reactivar", "btn--ghost", () => cambiarEstado("reactivar", r));
    else {
      boton("Editar", "btn--primary", () => abrirForm(r));
      boton("Dar de baja", "btn--ghost", () => cambiarEstado("baja", r));
    }
    const ganancia = celda(pct(r.ganancia), "rp-num", "Ganancia");
    ganancia.append(el("small", { textContent: ` ${ORIGEN[r.ganancia_origen]}` }));
    const tr = el("tr", { className: r.baja ? "is-baja" : "" },
      el("td", { className: "rp-desc" }, el("strong", { textContent: r.descripcion }),
        el("small", { textContent: [r.codigo, r.marca].filter(Boolean).join(" · ") })),
      celda(r.rubro || "—", "", "Rubro"),
      celda(r.proveedor || "—", "", "Proveedor"),
      celda(pesos(r.costo), "rp-num", "Costo"),
      ganancia,
      celda(pesos(r.precio), "rp-num rp-precio-celda", "Precio"),
      acciones);
    cuerpo.append(tr);
  });
}

async function cambiarEstado(accion, r) {
  if (accion === "baja" && !confirm(`¿Dar de baja "${r.descripcion}"? Queda oculto y se puede reactivar.`)) return;
  try {
    await rpEnviar(accion, { id: r.id_repuesto });
    rpAvisar(accion === "baja" ? "Repuesto dado de baja." : "Repuesto reactivado.");
    cargarCatalogo();
  } catch (e) {
    rpAvisar(e.message, true);
  }
}

// % que tendría el repuesto según lo cargado en el formulario
function gananciaFormulario() {
  if (rpForm.ganancia_pct.value !== "") return [parseFloat(rpForm.ganancia_pct.value), "propio"];
  const rubro = maestros.rubros.find((r) => r.nombre.toLowerCase() === rpForm.rubro.value.trim().toLowerCase());
  if (rubro && rubro.ganancia_pct !== null) return [Number(rubro.ganancia_pct), "rubro"];
  const prov = maestros.proveedores.find((p) => String(p.id_proveedor) === rpForm.id_proveedor.value);
  if (prov && prov.ganancia_pct !== null) return [Number(prov.ganancia_pct), "proveedor"];
  return [Number(maestros.config.ganancia_repuestos_pct), "general"];
}

function vistaPrecio() {
  const costo = parseFloat(rpForm.costo.value) || 0;
  const [p, origen] = gananciaFormulario();
  $("rp-precio-vista").textContent = `${pesos(Math.round(costo * (1 + p / 100) * 100) / 100)} (${pct(p)} ${ORIGEN[origen]})`;
}

function abrirForm(r = null) {
  rpForm.reset();
  $("rp-form-titulo").textContent = r ? "Editar repuesto" : "Nuevo repuesto";
  rpForm.id_repuesto.value = r?.id_repuesto ?? "";
  if (r) {
    ["descripcion", "codigo", "marca", "costo"].forEach((k) => (rpForm[k].value = r[k] ?? ""));
    rpForm.rubro.value = r.rubro ?? "";
    rpForm.id_proveedor.value = r.id_proveedor ?? "";
    rpForm.ganancia_pct.value = r.ganancia_pct ?? "";
  }
  rpForm.hidden = false;
  vistaPrecio();
  rpForm.scrollIntoView({ behavior: "smooth", block: "center" });
  rpForm.descripcion.focus({ preventScroll: true });
}

["costo", "ganancia_pct", "rubro", "id_proveedor"].forEach((k) => rpForm[k].addEventListener("input", vistaPrecio));
$("rp-nuevo").addEventListener("click", () => abrirForm());
$("rp-cancelar").addEventListener("click", () => (rpForm.hidden = true));
rpForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  try {
    await rpEnviar("guardar", new FormData(rpForm));
    rpAvisar("Repuesto guardado.");
    rpForm.hidden = true;
    cargarMaestros();
    cargarCatalogo();
  } catch (err) {
    rpAvisar(err.message, true);
  }
});

let busquedaTimer;
$("rp-q").addEventListener("input", () => {
  clearTimeout(busquedaTimer);
  busquedaTimer = setTimeout(cargarCatalogo, 300);
});
["rp-filtro-rubro", "rp-filtro-proveedor", "rp-bajas"].forEach((id) => $(id).addEventListener("change", cargarCatalogo));

/* =================== PROVEEDORES =================== */
const provForm = $("rp-prov-form");

function pintarProveedores() {
  const cont = $("rp-proveedores");
  cont.innerHTML = "";
  if (!maestros.proveedores.length) cont.append(el("p", { className: "ps-sin", textContent: "Todavía no cargaste proveedores." }));
  maestros.proveedores.forEach((p) => {
    const datos = [
      `Ganancia ${p.ganancia_pct !== null ? pct(p.ganancia_pct) : "general"}`,
      Number(p.bonificacion_pct) ? `bonificación ${pct(p.bonificacion_pct)}` : "",
      Number(p.iva_pct) ? `+ IVA ${pct(p.iva_pct)}` : "",
    ].filter(Boolean).join(" · ");
    const editar = el("button", { type: "button", className: "au-link", textContent: "Editar" });
    editar.addEventListener("click", () => abrirProveedor(p));
    const importar = el("button", { type: "button", className: "au-link", textContent: "Importar lista" });
    importar.addEventListener("click", () => {
      $("rp-lista-form").id_proveedor.value = p.id_proveedor;
      $("rp-lista-form").archivo.click();
    });
    const verRepuestos = el("button", { type: "button", className: "au-link", textContent: `${p.repuestos} repuestos` });
    verRepuestos.addEventListener("click", () => {
      $("rp-filtro-proveedor").value = p.id_proveedor;
      mostrarPestana("catalogo");
      cargarCatalogo();
    });
    const baja = el("button", { type: "button", className: "cl-dar-baja", textContent: "Dar de baja" });
    baja.addEventListener("click", async () => {
      if (!confirm(`¿Dar de baja a ${p.nombre}?`)) return;
      try {
        await rpEnviar("proveedor_baja", { id: p.id_proveedor });
        rpAvisar("Proveedor dado de baja.");
        cargarMaestros();
      } catch (e) {
        rpAvisar(e.message, true);
      }
    });
    cont.append(el("article", { className: "rp-tarjeta" },
      el("strong", { textContent: p.nombre }),
      el("small", { textContent: datos }),
      el("small", { textContent: p.ultima_lista ? `Última lista: ${fmtFecha(p.ultima_lista)}` : "Sin listas importadas" }),
      [p.contacto, p.telefono, p.mail].some(Boolean) ? el("small", { textContent: [p.contacto, p.telefono, p.mail].filter(Boolean).join(" · ") }) : null,
      el("div", { className: "rp-tarjeta__acciones" }, verRepuestos, importar, editar, Number(p.repuestos) ? null : baja)));
  });
}

function abrirProveedor(p = null) {
  provForm.reset();
  provForm.id_proveedor.value = p?.id_proveedor ?? "";
  if (p) ["nombre", "contacto", "telefono", "mail", "ganancia_pct", "bonificacion_pct", "iva_pct", "notas"].forEach((k) => (provForm[k].value = p[k] ?? ""));
  provForm.hidden = false;
  provForm.nombre.focus();
}
$("rp-prov-nuevo").addEventListener("click", () => abrirProveedor());
$("rp-prov-cancelar").addEventListener("click", () => (provForm.hidden = true));
provForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  const anterior = maestros.proveedores.find((p) => String(p.id_proveedor) === provForm.id_proveedor.value);
  const cambiaCosto = anterior && (Number(anterior.bonificacion_pct) !== Number(provForm.bonificacion_pct.value || 0)
    || Number(anterior.iva_pct) !== Number(provForm.iva_pct.value || 0));
  if (cambiaCosto && !confirm("Cambiaste la bonificación o el IVA: se va a recalcular el costo de todos los repuestos de su lista. ¿Seguimos?")) return;
  try {
    await rpEnviar("proveedor", new FormData(provForm));
    rpAvisar("Proveedor guardado.");
    provForm.hidden = true;
    cargarMaestros();
  } catch (err) {
    rpAvisar(err.message, true);
  }
});

/* =================== IMPORTAR LISTA =================== */
const listaForm = $("rp-lista-form");
const mapeo = $("rp-mapeo");
let lista = null; // respuesta de lista_leer

listaForm.archivo.addEventListener("change", () => {
  if (listaForm.archivo.files.length && listaForm.id_proveedor.value) listaForm.requestSubmit();
});
listaForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  if (!listaForm.id_proveedor.value) {
    rpAvisar("Elegí el proveedor de la lista.", true);
    return;
  }
  const boton = listaForm.querySelector("button[type=submit]");
  boton.disabled = true;
  boton.textContent = "Leyendo…";
  try {
    lista = await rpEnviar("lista_leer", new FormData(listaForm));
    abrirMapeo();
  } catch (err) {
    rpAvisar(err.message, true);
  } finally {
    boton.disabled = false;
    boton.textContent = "Leer la lista";
  }
});

function abrirMapeo() {
  const nombreProv = listaForm.id_proveedor.selectedOptions[0].textContent;
  $("rp-mapeo-info").textContent = `${nombreProv}: ${lista.filas.toLocaleString("es-AR")} filas.`
    + (lista.conocida ? " Usamos las mismas columnas que la lista anterior: revisalas." : " Indicá qué columna es cada dato (las reconocimos por los títulos).");
  mapeo.fila_titulos.value = lista.fila_titulos + 1;
  const columnas = $("rp-mapeo-columnas");
  columnas.innerHTML = "";
  Object.entries(lista.campos).forEach(([campo, texto]) => {
    const sel = el("select", { name: `col_${campo}` });
    columnas.append(el("label", {}, `${texto}${["descripcion", "precio"].includes(campo) ? " *" : ""}`, sel));
  });
  actualizarColumnas(true);
  mapeo.hidden = false;
  $("rp-resumen").hidden = true;
  $("rp-importar").disabled = true;
  mapeo.scrollIntoView({ behavior: "smooth", block: "start" });
}

// opciones de columna según la fila de títulos elegida
function actualizarColumnas(inicial = false) {
  const fila = Math.max(0, (parseInt(mapeo.fila_titulos.value, 10) || 1) - 1);
  const titulos = lista.muestra[fila] || [];
  const ancho = Math.max(...lista.muestra.map((f) => f.length));
  const letra = (i) => (i >= 26 ? String.fromCharCode(64 + Math.floor(i / 26)) : "") + String.fromCharCode(65 + (i % 26));
  Object.keys(lista.campos).forEach((campo) => {
    const sel = mapeo[`col_${campo}`];
    const actual = inicial ? lista.columnas[campo] : sel.value;
    sel.replaceChildren(new Option("— No viene —", ""),
      ...Array.from({ length: ancho }, (_, i) => new Option(`${letra(i)}: ${String(titulos[i] ?? "").slice(0, 30) || "(sin título)"}`, i)));
    sel.value = actual ?? "";
  });
  pintarMuestra(fila);
}

function pintarMuestra(fila) {
  const tabla = $("rp-muestra");
  tabla.innerHTML = "";
  const usadas = Object.fromEntries(Object.entries(lista.campos).map(([c, t]) => [mapeo[`col_${c}`].value, t]));
  const ancho = Math.max(...lista.muestra.map((f) => f.length));
  const cab = el("tr", {}, ...Array.from({ length: ancho }, (_, i) => el("th", { textContent: usadas[i] || "", className: usadas[i] ? "is-usada" : "" })));
  tabla.append(el("thead", {}, cab));
  const cuerpo = el("tbody");
  lista.muestra.slice(fila, fila + 9).forEach((f, n) => {
    cuerpo.append(el("tr", { className: n === 0 ? "is-titulos" : "" },
      ...Array.from({ length: ancho }, (_, i) => el("td", { textContent: String(f[i] ?? ""), className: usadas[i] ? "is-usada" : "" }))));
  });
  tabla.append(cuerpo);
}

mapeo.addEventListener("change", (e) => {
  if (e.target.name === "fila_titulos") actualizarColumnas();
  else pintarMuestra(Math.max(0, (parseInt(mapeo.fila_titulos.value, 10) || 1) - 1));
  $("rp-importar").disabled = true; // cambió algo: hay que volver a ver qué cambia
  $("rp-resumen").hidden = true;
});

function datosMapeo(simular) {
  const fd = new FormData(mapeo);
  fd.set("fila_titulos", Math.max(0, (parseInt(mapeo.fila_titulos.value, 10) || 1) - 1));
  fd.set("token", lista.token);
  if (simular) fd.set("simular", 1);
  return fd;
}

function pintarResumen(r, aplicado) {
  const cont = $("rp-resumen");
  cont.innerHTML = "";
  const linea = (texto, clase = "") => cont.append(el("p", { className: clase, textContent: texto }));
  linea(aplicado ? "Lista importada:" : "Si importás esta lista:", "rp-resumen__titulo");
  linea(`${r.nuevos} repuestos nuevos · ${r.actualizados} con precio nuevo · ${r.sin_cambio} sin cambios`);
  if (r.variacion_pct !== null) linea(`Variación promedio de los que cambiaron: ${r.variacion_pct > 0 ? "+" : ""}${pct(r.variacion_pct)}`, r.variacion_pct > 0 ? "rp-sube" : "rp-baja");
  if (r.ausentes) linea(r.bajas ? `${r.bajas} repuestos que ya no vienen en la lista quedan dados de baja.` : `${r.ausentes} repuestos de este proveedor no vienen en esta lista (quedan como están).`);
  if (r.rubros_nuevos.length) linea(`Rubros nuevos: ${r.rubros_nuevos.slice(0, 10).join(", ")}${r.rubros_nuevos.length > 10 ? "…" : ""}`);
  if (r.ignoradas) linea(`${r.ignoradas} filas sin descripción o sin precio se ignoran (títulos, subtotales, repetidos).`, "fv-ayuda");
  if (r.cambios.length) {
    const tabla = el("table", { className: "rp-tabla rp-tabla--cambios" },
      el("thead", {}, el("tr", {}, el("th", { textContent: "Repuesto" }), el("th", { className: "rp-num", textContent: "Costo antes" }),
        el("th", { className: "rp-num", textContent: "Ahora" }), el("th", { className: "rp-num", textContent: "Variación" }))));
    const cuerpo = el("tbody");
    r.cambios.forEach((c) => {
      const v = (c.ahora / c.antes - 1) * 100;
      cuerpo.append(el("tr", {}, el("td", { textContent: [c.codigo, c.descripcion].filter(Boolean).join(" · ") }),
        el("td", { className: "rp-num", textContent: pesos(c.antes) }), el("td", { className: "rp-num", textContent: pesos(c.ahora) }),
        el("td", { className: "rp-num " + (v > 0 ? "rp-sube" : "rp-baja"), textContent: `${v > 0 ? "+" : ""}${pct(v)}` })));
    });
    tabla.append(cuerpo);
    cont.append(el("div", { className: "rp-tabla-wrap" }, tabla));
  }
  cont.hidden = false;
}

$("rp-simular").addEventListener("click", async () => {
  try {
    const { simulacion } = await rpEnviar("lista_importar", datosMapeo(true));
    pintarResumen(simulacion, false);
    $("rp-importar").disabled = !(simulacion.nuevos || simulacion.actualizados || simulacion.bajas);
  } catch (e) {
    rpAvisar(e.message, true);
  }
});

$("rp-importar").addEventListener("click", async () => {
  const boton = $("rp-importar");
  boton.disabled = true;
  boton.textContent = "Importando…";
  try {
    const { resumen } = await rpEnviar("lista_importar", datosMapeo(false));
    pintarResumen(resumen, true);
    rpAvisar("Lista importada.");
    listaForm.reset();
    lista = null;
    mapeo.querySelectorAll(".rp-botones button:not(#rp-mapeo-cancelar)").forEach((b) => (b.hidden = true));
    $("rp-mapeo-cancelar").textContent = "Cerrar";
    cargarMaestros();
    cargarCatalogo();
  } catch (e) {
    rpAvisar(e.message, true);
    boton.disabled = false;
  } finally {
    boton.textContent = "Importar";
  }
});

$("rp-mapeo-cancelar").addEventListener("click", () => {
  mapeo.hidden = true;
  mapeo.querySelectorAll(".rp-botones button").forEach((b) => (b.hidden = false));
  $("rp-mapeo-cancelar").textContent = "Cancelar";
  listaForm.reset();
});

/* =================== GANANCIAS =================== */
function pintarRubros() {
  const cont = $("rp-rubros");
  cont.innerHTML = "";
  if (!maestros.rubros.length) cont.append(el("p", { className: "ps-sin", textContent: "Todavía no hay rubros." }));
  maestros.rubros.forEach((r) => cont.append(filaRubro(r)));
}

function filaRubro(r) {
  const form = el("form", { className: "rp-rubro" });
  const nombre = el("input", { name: "nombre", value: r.nombre, maxLength: 60, required: true, ariaLabel: "Nombre del rubro" });
  const ganancia = el("input", { name: "ganancia_pct", type: "number", min: 0, max: 1000, step: 0.5, value: r.ganancia_pct ?? "",
    placeholder: "—", ariaLabel: `% de ganancia de ${r.nombre || "este rubro"}` });
  const guardar = el("button", { type: "submit", className: "btn btn--primary ag-btn", textContent: "Guardar" });
  form.append(nombre, el("span", { className: "rp-rubro__pct" }, ganancia, "%"),
    el("small", { textContent: r.id_rubro ? `${r.repuestos} rep.` : "nuevo" }), guardar);
  if (r.id_rubro && !Number(r.repuestos)) {
    const baja = el("button", { type: "button", className: "au-link", textContent: "Quitar" });
    baja.addEventListener("click", async () => {
      try {
        await rpEnviar("rubro_baja", { id: r.id_rubro });
        cargarMaestros();
      } catch (e) {
        rpAvisar(e.message, true);
      }
    });
    form.append(baja);
  }
  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    try {
      await rpEnviar("rubro", { id_rubro: r.id_rubro || "", nombre: nombre.value, ganancia_pct: ganancia.value });
      rpAvisar(`Rubro ${nombre.value} guardado.`);
      cargarMaestros();
      cargarCatalogo();
    } catch (err) {
      rpAvisar(err.message, true);
    }
  });
  return form;
}
$("rp-rubro-nuevo").addEventListener("click", () => {
  const fila = filaRubro({ id_rubro: null, nombre: "", ganancia_pct: null, repuestos: 0 });
  $("rp-rubros").prepend(fila);
  fila.nombre.focus();
});

$("rp-config").addEventListener("submit", async (e) => {
  e.preventDefault();
  try {
    await rpEnviar("config", new FormData(e.target));
    rpAvisar("Precios generales guardados.");
    cargarMaestros();
    cargarCatalogo();
  } catch (err) {
    rpAvisar(err.message, true);
  }
});

/* =================== AUMENTOS =================== */
const aumento = $("rp-aumento");
const alcanceAumento = () => [aumento.id_rubro.value && `rubro ${aumento.id_rubro.selectedOptions[0].textContent}`,
  aumento.id_proveedor.value && `proveedor ${aumento.id_proveedor.selectedOptions[0].textContent}`].filter(Boolean).join(" y ") || "todos los repuestos";

aumento.addEventListener("input", () => {
  $("rp-aumento-aplicar").disabled = true;
  $("rp-aumento-vista").hidden = true;
});

$("rp-aumento-simular").addEventListener("click", async () => {
  if (!aumento.reportValidity()) return;
  try {
    const fd = new FormData(aumento);
    fd.set("simular", 1);
    const r = await rpEnviar("aumento", fd);
    const cont = $("rp-aumento-vista");
    cont.innerHTML = "";
    cont.append(el("p", { className: "rp-resumen__titulo", textContent: `${pct(aumento.pct.value)} a ${alcanceAumento()}: ${r.repuestos} repuestos.` }));
    if (r.muestra.length) {
      const cuerpo = el("tbody");
      r.muestra.forEach((m) => cuerpo.append(el("tr", {}, el("td", { textContent: m.descripcion }),
        el("td", { className: "rp-num", textContent: `${pesos(m.costo)} → ${pesos(m.costo_nuevo)}` }),
        el("td", { className: "rp-num", textContent: `${pesos(m.precio)} → ${pesos(m.precio_nuevo)}` }))));
      cont.append(el("div", { className: "rp-tabla-wrap" }, el("table", { className: "rp-tabla rp-tabla--cambios" },
        el("thead", {}, el("tr", {}, el("th", { textContent: "Ejemplos" }), el("th", { className: "rp-num", textContent: "Costo" }),
          el("th", { className: "rp-num", textContent: "Precio al cliente" }))), cuerpo)));
    }
    cont.hidden = false;
    $("rp-aumento-aplicar").disabled = !r.repuestos;
  } catch (e) {
    rpAvisar(e.message, true);
  }
});

aumento.addEventListener("submit", async (e) => {
  e.preventDefault();
  if (!confirm(`¿Aplicar ${pct(aumento.pct.value)} al costo de ${alcanceAumento()}?`)) return;
  try {
    const { actualizados } = await rpEnviar("aumento", new FormData(aumento));
    rpAvisar(`Se actualizó el costo de ${actualizados} repuestos.`);
    aumento.reset();
    $("rp-aumento-aplicar").disabled = true;
    $("rp-aumento-vista").hidden = true;
    cargarMaestros();
    cargarCatalogo();
  } catch (err) {
    rpAvisar(err.message, true);
  }
});

/* =================== HISTORIALES =================== */
function pintarHistoriales() {
  const imp = $("rp-importaciones");
  imp.replaceChildren(...maestros.importaciones.map((i) => el("li", {},
    el("strong", { textContent: `${i.proveedor} · ${fmtFecha(i.fecha)}` }),
    el("small", { textContent: [i.archivo, `${i.nuevos} nuevos`, `${i.actualizados} actualizados`, i.bajas > 0 ? `${i.bajas} de baja` : "",
      i.variacion_pct !== null ? `variación ${Number(i.variacion_pct) > 0 ? "+" : ""}${pct(i.variacion_pct)}` : ""].filter(Boolean).join(" · ") }))));
  if (!imp.children.length) imp.append(el("li", { className: "ps-sin", textContent: "Todavía no se importó ninguna lista." }));

  const aum = $("rp-aumentos");
  aum.replaceChildren(...maestros.aumentos.map((a) => el("li", {},
    el("strong", { textContent: a.detalle }), el("small", { textContent: `${fmtFecha(a.fecha)} · ${a.repuestos} repuestos` }))));
  if (!aum.children.length) aum.append(el("li", { className: "ps-sin", textContent: "Todavía no se aplicaron aumentos." }));
}

/* =================== INICIO =================== */
cargarMaestros().then(() => {
  const pestana = location.hash.slice(1);
  if (["proveedores", "ganancias", "aumentos"].includes(pestana)) mostrarPestana(pestana);
  cargarCatalogo();
});
