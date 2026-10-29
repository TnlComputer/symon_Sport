/* Repuestos y precios (administrador) */
const URL_REP = "./php/repuestos_admin.php";
const rpAviso = document.getElementById("rp-aviso");
const rpCuerpo = document.getElementById("rp-cuerpo");
const rpForm = document.getElementById("rp-form");
const rpQ = document.getElementById("rp-q");
const rpFiltroCat = document.getElementById("rp-filtro-categoria");
const rpBajas = document.getElementById("rp-bajas");
let rpConfig = { ganancia_repuestos_pct: 30 };
let rpAvisoTimer;

const pesos = (n) =>
  "$ " + Number(n).toLocaleString("es-AR", { minimumFractionDigits: 0, maximumFractionDigits: 2 });

const rpAvisar = (msg, error = false) => {
  rpAviso.textContent = msg;
  rpAviso.classList.toggle("cf-aviso--error", error);
  rpAviso.hidden = false;
  clearTimeout(rpAvisoTimer);
  rpAvisoTimer = setTimeout(() => (rpAviso.hidden = true), 3500);
};

const rpEnviar = async (accion, datos) => {
  const fd = datos instanceof FormData ? datos : new FormData();
  if (!(datos instanceof FormData)) Object.entries(datos || {}).forEach(([k, v]) => fd.append(k, v));
  fd.append("accion", accion);
  const res = await fetch(URL_REP, { method: "POST", body: fd });
  const json = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(json.error || "No se pudo guardar");
  return json;
};

const celda = (texto, clase) => {
  const td = document.createElement("td");
  if (clase) td.className = clase;
  td.textContent = texto;
  return td;
};

function llenarOpciones(selector, valores, primero) {
  document.querySelectorAll(selector).forEach((sel) => {
    const actual = sel.value;
    sel.innerHTML = "";
    sel.append(new Option(primero || sel.dataset.primero || "Todas", ""));
    valores.forEach((v) => sel.append(new Option(v, v)));
    sel.value = valores.includes(actual) ? actual : "";
  });
}

async function cargar() {
  const params = new URLSearchParams({ q: rpQ.value.trim(), categoria: rpFiltroCat.value, bajas: rpBajas.checked ? 1 : "" });
  try {
    const res = await fetch(`${URL_REP}?${params}`);
    const datos = await res.json();
    if (!res.ok) throw new Error(datos.error || "Error al cargar");
    rpConfig = datos.config;

    const fc = document.getElementById("rp-config");
    fc.ganancia_repuestos_pct.value = datos.config.ganancia_repuestos_pct;
    fc.recargo_mo_repuesto_cliente_pct.value = datos.config.recargo_mo_repuesto_cliente_pct;
    fc.lavado_precio.value = datos.config.lavado_precio ?? 0;
    fc.lavado_por_defecto.checked = Number(datos.config.lavado_por_defecto) === 1;

    llenarOpciones("#rp-aumento .rp-sel-categoria", datos.categorias, "Todas");
    llenarOpciones("#rp-aumento .rp-sel-proveedor", datos.proveedores, "Todos");
    llenarOpciones("#rp-filtro-categoria", datos.categorias, "Todas las categorías");
    document.getElementById("rp-lista-categorias").innerHTML = datos.categorias.map((c) => `<option value="${c.replace(/"/g, "&quot;")}">`).join("");
    document.getElementById("rp-lista-proveedores").innerHTML = datos.proveedores.map((c) => `<option value="${c.replace(/"/g, "&quot;")}">`).join("");

    pintar(datos.repuestos);
  } catch (e) {
    rpAvisar(e.message, true);
  }
}

function pintar(repuestos) {
  rpCuerpo.innerHTML = "";
  document.getElementById("rp-vacio").hidden = repuestos.length > 0;
  repuestos.forEach((r) => {
    const tr = document.createElement("tr");
    if (r.baja) tr.classList.add("is-baja");

    const tdDesc = document.createElement("td");
    tdDesc.className = "rp-desc";
    const titulo = document.createElement("strong");
    titulo.textContent = r.descripcion;
    const detalle = document.createElement("small");
    detalle.textContent = [r.codigo, r.marca].filter(Boolean).join(" · ");
    tdDesc.append(titulo, detalle);

    const general = r.ganancia_pct === null;
    const tdGan = celda(`${Number(general ? rpConfig.ganancia_repuestos_pct : r.ganancia_pct)}%`, "rp-num");
    if (general) tdGan.append(Object.assign(document.createElement("small"), { textContent: " general" }));

    const tdAcc = document.createElement("td");
    tdAcc.className = "rp-acciones";
    if (r.baja) {
      const b = Object.assign(document.createElement("button"), { type: "button", className: "btn btn--ghost ag-btn", textContent: "Reactivar" });
      b.addEventListener("click", () => cambiarEstado("reactivar", r));
      tdAcc.append(b);
    } else {
      const e = Object.assign(document.createElement("button"), { type: "button", className: "btn btn--primary ag-btn", textContent: "Editar" });
      e.addEventListener("click", () => abrirForm(r));
      const b = Object.assign(document.createElement("button"), { type: "button", className: "btn btn--ghost ag-btn", textContent: "Dar de baja" });
      b.addEventListener("click", () => cambiarEstado("baja", r));
      tdAcc.append(e, b);
    }

    tr.append(
      tdDesc,
      celda(r.categoria || "—"),
      celda(r.proveedor || "—"),
      celda(pesos(r.costo), "rp-num"),
      tdGan,
      celda(pesos(r.precio), "rp-num rp-precio-celda"),
      tdAcc
    );
    tr.children[1].dataset.label = "Categoría";
    tr.children[2].dataset.label = "Proveedor";
    tr.children[3].dataset.label = "Costo";
    tr.children[4].dataset.label = "Ganancia";
    tr.children[5].dataset.label = "Precio";
    rpCuerpo.append(tr);
  });
}

async function cambiarEstado(accion, r) {
  if (accion === "baja" && !confirm(`¿Dar de baja "${r.descripcion}"? Queda oculto y se puede reactivar.`)) return;
  try {
    await rpEnviar(accion, { id: r.id_repuesto });
    rpAvisar(accion === "baja" ? "Repuesto dado de baja." : "Repuesto reactivado.");
    cargar();
  } catch (e) {
    rpAvisar(e.message, true);
  }
}

/* ---------- Alta / edición ---------- */
function vistaPrecio() {
  const costo = parseFloat(rpForm.costo.value) || 0;
  const propio = rpForm.ganancia_pct.value;
  const pct = propio !== "" ? parseFloat(propio) : Number(rpConfig.ganancia_repuestos_pct);
  document.getElementById("rp-precio-vista").textContent = `${pesos(Math.round(costo * (1 + pct / 100) * 100) / 100)} (${pct}%${propio === "" ? " general" : ""})`;
}

function abrirForm(r = null) {
  rpForm.reset();
  document.getElementById("rp-form-titulo").textContent = r ? "Editar repuesto" : "Nuevo repuesto";
  if (r) {
    ["id_repuesto", "descripcion", "codigo", "marca", "categoria", "proveedor", "costo"].forEach((k) => (rpForm[k].value = r[k] ?? ""));
    rpForm.ganancia_pct.value = r.ganancia_pct ?? "";
  } else {
    rpForm.id_repuesto.value = "";
  }
  rpForm.hidden = false;
  vistaPrecio();
  rpForm.scrollIntoView({ behavior: "smooth", block: "center" });
  rpForm.descripcion.focus({ preventScroll: true });
}

rpForm.costo.addEventListener("input", vistaPrecio);
rpForm.ganancia_pct.addEventListener("input", vistaPrecio);
document.getElementById("rp-nuevo").addEventListener("click", () => abrirForm());
document.getElementById("rp-cancelar").addEventListener("click", () => (rpForm.hidden = true));

rpForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  try {
    await rpEnviar("guardar", new FormData(rpForm));
    rpAvisar("Repuesto guardado.");
    rpForm.hidden = true;
    cargar();
  } catch (err) {
    rpAvisar(err.message, true);
  }
});

/* ---------- Configuración y aumentos ---------- */
document.getElementById("rp-config").addEventListener("submit", async (e) => {
  e.preventDefault();
  try {
    await rpEnviar("config", new FormData(e.target));
    rpAvisar("Precios generales guardados.");
    cargar();
  } catch (err) {
    rpAvisar(err.message, true);
  }
});

document.getElementById("rp-aumento").addEventListener("submit", async (e) => {
  e.preventDefault();
  const f = e.target;
  const alcance = [f.categoria.value && `categoría ${f.categoria.value}`, f.proveedor.value && `proveedor ${f.proveedor.value}`]
    .filter(Boolean).join(" y ") || "todos los repuestos";
  if (!confirm(`¿Aplicar ${f.pct.value}% al costo de ${alcance}?`)) return;
  try {
    const { actualizados } = await rpEnviar("aumento", new FormData(f));
    rpAvisar(`Se actualizó el costo de ${actualizados} repuestos.`);
    f.pct.value = "";
    cargar();
  } catch (err) {
    rpAvisar(err.message, true);
  }
});

/* ---------- Filtros ---------- */
let rpBusquedaTimer;
rpQ.addEventListener("input", () => {
  clearTimeout(rpBusquedaTimer);
  rpBusquedaTimer = setTimeout(cargar, 300);
});
rpFiltroCat.addEventListener("change", cargar);
rpBajas.addEventListener("change", cargar);

cargar();
