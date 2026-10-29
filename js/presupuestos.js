/* Presupuestos (administrador): cliente → sus vehículos → trabajo → repuestos → importes */
const API = "./php/presupuestos_api.php";
const $ = (id) => document.getElementById(id);
const editor = $("ps-editor");
const psAviso = $("ps-aviso");
let config = { ganancia_repuestos_pct: 30, recargo_mo_repuesto_cliente_pct: 0, lavado_precio: 0, lavado_por_defecto: 1 };
let items = []; // { id_repuesto, descripcion, cantidad, provisto, precio_unit, costo }
let avisoTimer;

const pesos = (n) => "$ " + Number(n || 0).toLocaleString("es-AR", { maximumFractionDigits: 2 });
const num = (v) => {
  const n = parseFloat(String(v).replace(",", "."));
  return Number.isFinite(n) ? n : 0;
};
const hoyISO = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};
const fmtFecha = (iso) => {
  if (!iso) return "";
  const [y, m, d] = iso.split("-").map(Number);
  return new Date(y, m - 1, d).toLocaleDateString("es-AR", { day: "numeric", month: "short", year: "numeric" });
};
const el = (tag, props = {}, ...hijos) => {
  const e = Object.assign(document.createElement(tag), props);
  hijos.forEach((h) => h !== null && h !== undefined && e.append(h));
  return e;
};

function avisar(msg, error = false) {
  psAviso.textContent = msg;
  psAviso.classList.toggle("cf-aviso--error", error);
  psAviso.hidden = false;
  clearTimeout(avisoTimer);
  avisoTimer = setTimeout(() => (psAviso.hidden = true), 4000);
}

async function api(params, cuerpo) {
  const url = cuerpo ? API : `${API}?${new URLSearchParams(params)}`;
  const opciones = cuerpo ? { method: "POST", body: cuerpo } : undefined;
  const res = await fetch(url, opciones);
  const datos = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(datos.error || "Ocurrió un error");
  return datos;
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

/* =================== LISTADO =================== */
async function cargarListado() {
  const lista = $("ps-lista");
  try {
    const { presupuestos } = await api({ accion: "listar", q: $("ps-q").value.trim() });
    lista.innerHTML = "";
    if (!presupuestos.length) {
      lista.append(el("p", { className: "tn-cargando", textContent: "No hay presupuestos." }));
      return;
    }
    presupuestos.forEach((p) => {
      const card = el("article", { className: "ps-card" + (Number(p.usado) ? " ps-card--usado" : "") });
      const cab = el("div", { className: "ps-card__cab" },
        el("span", { className: "ps-card__num", textContent: `#${p.id_presu}` }),
        el("span", { className: "tn-placa", textContent: p.patente || "—" }),
        el("strong", { textContent: p.empresa_nyp || "Sin cliente" }),
        el("span", { className: "ps-card__fecha", textContent: fmtFecha(p.fecha_presu) }));
      const desc = el("p", { className: "ps-card__desc", textContent: p.descripcion_presu || "" });
      const pie = el("div", { className: "ps-card__pie" },
        el("span", { textContent: [p.marca, p.modelo].filter(Boolean).join(" ") }),
        el("strong", { className: "ps-card__total", textContent: pesos(p.total_presu) }));
      const estado = el("div", { className: "ps-card__estado" });
      estado.append(el("span", { className: "ps-chip" + (Number(p.usado) ? " ps-chip--ok" : ""), textContent: Number(p.usado) ? "En reparación / aprobado" : "Pendiente" }));
      if (!Number(p.usado) && p.respuesta_cliente) {
        const chip = el("span", { className: "ps-chip " + (p.respuesta_cliente === "aprobado" ? "ps-chip--ok" : "mg-chip--vencido"),
          textContent: p.respuesta_cliente === "aprobado" ? "Aprobado por el cliente" : "Rechazado por el cliente" });
        chip.title = [fmtFecha(p.respuesta_fecha), p.respuesta_motivo].filter(Boolean).join(" · ");
        estado.append(chip);
      }
      if (Number(p.lavado_presu) > 0) {
        const lav = el("button", { type: "button", className: "ps-chip ps-chip--lavado" + (p.lavado_hecho ? " ps-chip--ok" : ""), textContent: p.lavado_hecho ? "Lavado ✓" : "Lavar antes de entregar" });
        lav.title = "Marcar / desmarcar lavado hecho";
        lav.addEventListener("click", async (e) => {
          e.stopPropagation();
          await api(null, formData({ accion: "lavado_hecho", id: p.id_presu }));
          cargarListado();
        });
        estado.append(lav);
      }
      card.append(cab, desc, estado, pie);
      card.tabIndex = 0;
      card.addEventListener("click", () => abrirPresupuesto(p.id_presu));
      card.addEventListener("keydown", (e) => e.key === "Enter" && abrirPresupuesto(p.id_presu));
      lista.append(card);
    });
  } catch (e) {
    avisar(e.message, true);
  }
}

/* =================== EDITOR =================== */
function mostrarEditor(mostrar) {
  editor.hidden = !mostrar;
  $("ps-listado").hidden = mostrar;
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function pasos({ cliente, vehiculo }) {
  $("ps-paso-vehiculo").hidden = !cliente;
  ["ps-paso-trabajo", "ps-paso-repuestos", "ps-lateral"].forEach((id) => ($(id).hidden = !vehiculo));
}

function nuevoPresupuesto() {
  editor.reset();
  editor.id_presu.value = "";
  editor.id_cliente.value = "";
  editor.id_vehiculo.value = "";
  editor.fecha.value = hoyISO();
  editor.lavado.value = Number(config.lavado_por_defecto) ? config.lavado_precio : 0;
  items = [];
  $("ps-titulo").textContent = "Nuevo presupuesto";
  $("ps-cliente-elegido").hidden = true;
  $("ps-cliente-buscar").hidden = false;
  $("ps-cliente-resultados").innerHTML = "";
  $("ps-enviar-mail").closest("label").hidden = false;
  pasos({ cliente: false, vehiculo: false });
  pintarItems();
  mostrarEditor(true);
  $("ps-cliente-q").focus();
}

async function abrirPresupuesto(id) {
  try {
    const d = await api({ accion: "detalle", id });
    config = d.config;
    const p = d.presupuesto;
    editor.reset();
    editor.id_presu.value = p.id_presu;
    editor.fecha.value = p.fecha_presu || hoyISO();
    editor.dias.value = p.dias_presu ?? 10;
    editor.descripcion.value = p.descripcion_presu || "";
    // presupuestos viejos no tienen desglose: la mano de obra base es la que figuraba
    editor.mano_obra.value = p.mobra_base_presu !== null ? num(p.mobra_base_presu) : num(p.mobra_presu);
    editor.lavado.value = num(p.lavado_presu);
    editor.senia.value = num(p.senia_presu);
    items = d.items.map((i) => ({ ...i, cantidad: num(i.cantidad), precio_unit: num(i.precio_unit), costo: num(i.costo_unit) }));
    // presupuestos viejos: el importe de repuestos sin detalle queda como un ítem
    if (!items.length && num(p.repuestos_presu) > 0) {
      items.push({ id_repuesto: null, descripcion: "Repuestos (importe anterior)", cantidad: 1, provisto: "taller", precio_unit: num(p.repuestos_presu), costo: 0 });
    }
    $("ps-titulo").textContent = `Presupuesto #${p.id_presu}`;
    $("ps-enviar-mail").closest("label").hidden = true;
    await elegirCliente({ id_cliente: p.id_cliente, empresa_nyp: p.empresa_nyp }, p.id_vehiculo);
    pintarItems();
    mostrarEditor(true);
  } catch (e) {
    avisar(e.message, true);
  }
}

/* ---------- 1. Cliente ---------- */
const buscarClientes = demorar(async () => {
  const q = $("ps-cliente-q").value.trim();
  const ul = $("ps-cliente-resultados");
  ul.innerHTML = "";
  if (q.length < 2) return;
  const { clientes } = await api({ accion: "clientes", q });
  if (!clientes.length) ul.append(el("li", { className: "ps-sin", textContent: "Sin resultados. Cargá el cliente en Clientes." }));
  clientes.forEach((c) => {
    const li = el("li", {},
      el("button", { type: "button", className: "ps-res" },
        el("strong", { textContent: c.empresa_nyp }),
        el("small", { textContent: [c.cuit, c.celular, c.mail].filter(Boolean).join(" · ") })));
    li.firstChild.addEventListener("click", () => elegirCliente(c));
    ul.append(li);
  });
});
$("ps-cliente-q").addEventListener("input", buscarClientes);

async function elegirCliente(c, idVehiculo = null) {
  editor.id_cliente.value = c.id_cliente;
  $("ps-cliente-nombre").textContent = c.empresa_nyp;
  $("ps-cliente-elegido").hidden = false;
  $("ps-cliente-buscar").hidden = true;
  editor.id_vehiculo.value = "";
  pasos({ cliente: true, vehiculo: false });
  await cargarVehiculos(idVehiculo);
}

$("ps-cliente-cambiar").addEventListener("click", () => {
  editor.id_cliente.value = "";
  editor.id_vehiculo.value = "";
  $("ps-cliente-elegido").hidden = true;
  $("ps-cliente-buscar").hidden = false;
  $("ps-cliente-q").value = "";
  $("ps-cliente-resultados").innerHTML = "";
  pasos({ cliente: false, vehiculo: false });
  $("ps-cliente-q").focus();
});

/* ---------- 2. Vehículos del cliente ---------- */
async function cargarVehiculos(elegir = null) {
  const cont = $("ps-autos");
  cont.innerHTML = "";
  const { vehiculos } = await api({ accion: "vehiculos", id_cliente: editor.id_cliente.value });
  $("ps-nuevo-auto").open = !vehiculos.length;
  if (!vehiculos.length) cont.append(el("p", { className: "tn-mios__ayuda", textContent: "Este cliente no tiene vehículos. Agregá uno." }));
  vehiculos.forEach((v) => {
    const b = el("button", { type: "button", className: "tn-auto ps-auto" },
      el("span", { className: "tn-placa", textContent: v.patente }),
      el("strong", { textContent: [v.marca, v.modelo].filter(Boolean).join(" ") || "Vehículo" }),
      el("small", { textContent: v.anio || "" }));
    b.addEventListener("click", () => elegirVehiculo(v.id_vehiculo));
    b.dataset.id = v.id_vehiculo;
    cont.append(b);
  });
  if (elegir) elegirVehiculo(elegir);
}

function elegirVehiculo(id) {
  editor.id_vehiculo.value = id;
  $("ps-autos").querySelectorAll(".ps-auto").forEach((b) => b.classList.toggle("is-elegido", b.dataset.id == id));
  pasos({ cliente: true, vehiculo: true });
  calcular();
}

$("ps-auto-patente").addEventListener("input", (e) => {
  e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
  $("ps-traspaso").hidden = true;
});

async function agregarVehiculo(confirmarTraspaso = false) {
  const datos = {
    accion: "vehiculo",
    id_cliente: editor.id_cliente.value,
    patente: $("ps-auto-patente").value,
    marca: $("ps-auto-marca").value,
    modelo: $("ps-auto-modelo").value,
    anio: $("ps-auto-anio").value,
  };
  if (confirmarTraspaso) datos.confirmar_traspaso = 1;
  try {
    const r = await api(null, formData(datos));
    if (r.traspaso) {
      // la patente es de otro cliente: pido confirmación
      const caja = $("ps-traspaso");
      caja.innerHTML = "";
      const btn = el("button", { type: "button", className: "btn btn--accent ag-btn", textContent: "Sí, pasar el vehículo a este cliente" });
      btn.addEventListener("click", () => agregarVehiculo(true));
      caja.append(
        el("p", { textContent: `La patente ${datos.patente} (${[r.marca, r.modelo, r.anio].filter(Boolean).join(" ")}) figura a nombre de ${r.cliente_anterior}.` }),
        el("p", { textContent: "Si la pasás, deja de aparecer en la lista del cliente anterior y su historial de reparaciones queda con el auto." }),
        btn);
      caja.hidden = false;
      return;
    }
    $("ps-traspaso").hidden = true;
    ["ps-auto-patente", "ps-auto-marca", "ps-auto-modelo", "ps-auto-anio"].forEach((id) => ($(id).value = ""));
    avisar(r.traspasado ? "Vehículo traspasado al cliente con su historial." : "Vehículo agregado.");
    await cargarVehiculos(r.id_vehiculo);
  } catch (e) {
    avisar(e.message, true);
  }
}
$("ps-auto-agregar").addEventListener("click", () => agregarVehiculo(false));

/* ---------- 4. Repuestos ---------- */
const buscarRepuestos = demorar(async () => {
  const q = $("ps-rep-q").value.trim();
  const ul = $("ps-rep-resultados");
  ul.innerHTML = "";
  if (q.length < 2) return;
  const r = await api({ accion: "repuestos", q });
  config = r.config;
  if (!r.repuestos.length) ul.append(el("li", { className: "ps-sin", textContent: "No está en el catálogo. Usá «Agregar un repuesto que no está»." }));
  r.repuestos.forEach((rep) => {
    const li = el("li", {},
      el("button", { type: "button", className: "ps-res" },
        el("strong", { textContent: rep.descripcion }),
        el("small", { textContent: `${[rep.codigo, rep.marca, rep.proveedor].filter(Boolean).join(" · ")}  —  ${pesos(rep.precio)}` })));
    li.firstChild.addEventListener("click", () => {
      items.push({ id_repuesto: rep.id_repuesto, descripcion: rep.descripcion, cantidad: 1, provisto: "taller", precio_unit: num(rep.precio), costo: num(rep.costo) });
      $("ps-rep-q").value = "";
      ul.innerHTML = "";
      pintarItems();
    });
    ul.append(li);
  });
});
$("ps-rep-q").addEventListener("input", buscarRepuestos);
$("ps-rep-manual").addEventListener("click", () => {
  items.push({ id_repuesto: null, descripcion: "", cantidad: 1, provisto: "taller", precio_unit: 0, costo: 0 });
  pintarItems();
  const ultimo = $("ps-items").querySelector(".ps-item:last-child input");
  if (ultimo) ultimo.focus();
});

function pintarItems() {
  const cont = $("ps-items");
  cont.innerHTML = "";
  if (!items.length) {
    cont.append(el("p", { className: "tn-mios__ayuda", textContent: "Sin repuestos (solo mano de obra)." }));
  }
  items.forEach((it, i) => {
    const desc = el("input", { className: "ps-input", value: it.descripcion, placeholder: "Descripción", maxLength: 150 });
    desc.addEventListener("input", () => (it.descripcion = desc.value));
    desc.readOnly = !!it.id_repuesto;

    const cant = el("input", { className: "ps-input ps-cant", type: "number", min: 0.01, step: 1, value: it.cantidad });
    cant.setAttribute("aria-label", "Cantidad");
    cant.addEventListener("input", () => { it.cantidad = num(cant.value); calcular(); subtotal.textContent = sub(); });

    const precio = el("input", { className: "ps-input ps-precio", type: "number", min: 0, step: 100, value: it.precio_unit });
    precio.setAttribute("aria-label", "Precio unitario");
    precio.addEventListener("input", () => { it.precio_unit = num(precio.value); calcular(); subtotal.textContent = sub(); });

    const sub = () => (it.provisto === "cliente" ? "Lo trae el cliente" : pesos(it.cantidad * it.precio_unit));
    const subtotal = el("span", { className: "ps-subtotal", textContent: sub() });

    // quién pone el repuesto
    const grupo = el("div", { className: "ps-provisto", role: "radiogroup" });
    grupo.setAttribute("aria-label", "Quién pone el repuesto");
    [["taller", "Lo compra Symon Sport"], ["cliente", "Lo trae el cliente"]].forEach(([valor, texto]) => {
      const b = el("button", { type: "button", className: "ps-provisto__op" + (it.provisto === valor ? " is-activo" : ""), textContent: texto });
      b.setAttribute("role", "radio");
      b.setAttribute("aria-checked", String(it.provisto === valor));
      b.addEventListener("click", () => { it.provisto = valor; pintarItems(); });
      grupo.append(b);
    });

    const quitar = el("button", { type: "button", className: "ps-quitar", textContent: "×", title: "Quitar repuesto" });
    quitar.setAttribute("aria-label", "Quitar repuesto");
    quitar.addEventListener("click", () => { items.splice(i, 1); pintarItems(); });

    precio.disabled = it.provisto === "cliente";
    cont.append(el("div", { className: "ps-item" + (it.provisto === "cliente" ? " ps-item--cliente" : "") },
      el("div", { className: "ps-item__fila" }, desc, quitar),
      el("div", { className: "ps-item__fila" }, el("label", { className: "ps-mini" }, "Cant.", cant), el("label", { className: "ps-mini" }, "Precio unit.", precio), subtotal),
      grupo));
  });
  calcular();
}

/* ---------- 5. Importes (misma cuenta que el servidor) ---------- */
function calcular() {
  let repuestos = 0;
  let costo = 0;
  let traeCliente = false;
  items.forEach((it) => {
    if (it.provisto === "cliente") traeCliente = true;
    else {
      repuestos += it.cantidad * it.precio_unit;
      costo += it.cantidad * (it.costo || 0);
    }
  });
  const base = num(editor.mano_obra.value);
  const lavado = Math.max(0, num(editor.lavado.value));
  const recargo = traeCliente ? Math.round(base * num(config.recargo_mo_repuesto_cliente_pct)) / 100 : 0;
  const moCliente = base + recargo + lavado;
  const total = repuestos + moCliente;
  const senia = num(editor.senia.value);

  $("ps-t-repuestos").textContent = pesos(repuestos);
  $("ps-t-mo").textContent = pesos(moCliente);
  $("ps-t-total").textContent = pesos(total);
  $("ps-t-senia").textContent = pesos(senia);
  $("ps-t-saldo").textContent = pesos(Math.max(0, total - senia));
  $("ps-i-base").textContent = pesos(base);
  $("ps-i-recargo").textContent = pesos(recargo) + (traeCliente ? ` (${num(config.recargo_mo_repuesto_cliente_pct)}%)` : "");
  $("ps-i-lavado").textContent = pesos(lavado);
  $("ps-i-ganancia").textContent = costo > 0 ? pesos(repuestos - costo) : "—";
}
["mano_obra", "lavado", "senia"].forEach((n) => editor[n].addEventListener("input", calcular));

/* ---------- Guardar ---------- */
editor.addEventListener("submit", async (e) => {
  e.preventDefault();
  const error = $("ps-error");
  error.hidden = true;
  const sinDescripcion = items.find((it) => !it.descripcion.trim());
  if (sinDescripcion) {
    error.textContent = "Completá la descripción de todos los repuestos o quitá los vacíos.";
    error.hidden = false;
    return;
  }
  const esNuevo = !editor.id_presu.value;
  const btn = editor.querySelector(".ps-guardar");
  btn.disabled = true;
  try {
    const r = await api(null, formData({
      accion: "guardar",
      id_presu: editor.id_presu.value,
      id_cliente: editor.id_cliente.value,
      id_vehiculo: editor.id_vehiculo.value,
      fecha: editor.fecha.value,
      dias: editor.dias.value,
      descripcion: editor.descripcion.value,
      mano_obra: editor.mano_obra.value,
      lavado: editor.lavado.value,
      senia: editor.senia.value,
      items: JSON.stringify(items),
    }));
    if (esNuevo && $("ps-enviar-mail").checked) {
      fetch("php/send_PresuMail.php", { method: "POST", body: formData({ id: r.id }) }).catch(() => {});
    }
    avisar(`Presupuesto #${r.id} guardado. Total ${pesos(r.totales.total)}.`);
    mostrarEditor(false);
    cargarListado();
  } catch (err) {
    error.textContent = err.message;
    error.hidden = false;
  } finally {
    btn.disabled = false;
  }
});

$("ps-nuevo").addEventListener("click", nuevoPresupuesto);
$("ps-cerrar").addEventListener("click", () => mostrarEditor(false));
$("ps-q").addEventListener("input", demorar(cargarListado, 300));

// configuración de precios (lavado por defecto, % de recargo)
const configLista = api({ accion: "repuestos", q: "" }).then((r) => (config = r.config)).catch(() => {});
cargarListado();

// ?id=N abre un presupuesto; ?nuevo empieza uno; ?cliente=N[&vehiculo=N] empieza uno para ese cliente (desde su ficha)
const pedido = new URLSearchParams(location.search);
if (pedido.get("id")) {
  abrirPresupuesto(pedido.get("id"));
} else if (pedido.has("nuevo")) {
  configLista.then(nuevoPresupuesto);
} else if (pedido.get("cliente")) {
  configLista.then(async () => {
    nuevoPresupuesto();
    const { clientes } = await api({ accion: "clientes", id: pedido.get("cliente") });
    if (clientes[0]) elegirCliente(clientes[0], pedido.get("vehiculo"));
  }).catch((e) => avisar(e.message, true));
}
