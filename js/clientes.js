/* Clientes (administrador): listado → ficha con datos, vehículos e historial, presupuestos y accesos */
const API = "./php/clientes_api.php";
const $ = (id) => document.getElementById(id);
const form = $("cl-form");
const formUsuario = $("cl-form-usuario");
const clAviso = $("cl-aviso");
let ficha = null; // { cliente, vehiculos, anteriores, usuarios, presupuestos, resumen }
let patenteAbierta = null;
let fichaVehiculo = null; // { patente, campos, ficha, fotos, max_fotos }
let avisoTimer;

const pesos = (n) => "$ " + Number(n || 0).toLocaleString("es-AR", { maximumFractionDigits: 0 });
const fmtFecha = (iso) => {
  if (!iso) return "";
  const [y, m, d] = iso.slice(0, 10).split("-").map(Number);
  return new Date(y, m - 1, d).toLocaleDateString("es-AR", { day: "numeric", month: "short", year: "numeric" });
};
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
const soloNumeros = (v) => String(v || "").replace(/\D/g, "");
const vehiculoTexto = (v) => [v.marca, v.modelo].filter(Boolean).join(" ") || "Vehículo";

function avisar(msg, error = false) {
  clAviso.textContent = msg;
  clAviso.classList.toggle("cf-aviso--error", error);
  clAviso.hidden = false;
  clearTimeout(avisoTimer);
  avisoTimer = setTimeout(() => (clAviso.hidden = true), 5000);
}

async function api(params, cuerpo) {
  const url = cuerpo ? API : `${API}?${new URLSearchParams(params)}`;
  const res = await fetch(url, cuerpo ? { method: "POST", body: cuerpo } : undefined);
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
  const lista = $("cl-lista");
  try {
    const { clientes } = await api({ accion: "listar", q: $("cl-q").value.trim(), bajas: $("cl-bajas").checked ? 1 : "" });
    lista.innerHTML = "";
    $("cl-cuenta").textContent = clientes.length === 1 ? "1 cliente" : `${clientes.length} clientes`;
    if (!clientes.length) {
      lista.append(el("p", { className: "tn-cargando", textContent: "No hay clientes con esa búsqueda." }));
      return;
    }
    clientes.forEach((c) => {
      const patentes = (c.patentes || "").split(" ").filter(Boolean);
      const autos = el("div", { className: "cl-card__autos" });
      patentes.slice(0, 4).forEach((p) => autos.append(el("span", { className: "tn-placa cl-placa-chica", textContent: p })));
      if (patentes.length > 4) autos.append(el("span", { className: "cl-mas", textContent: `+${patentes.length - 4}` }));
      if (!patentes.length) autos.append(el("span", { className: "cl-mas", textContent: "Sin vehículos" }));

      const contacto = [c.celular || c.telefono, c.mail].filter(Boolean).join(" · ");
      const card = el("article", { className: "ps-card cl-card" + (c.baja ? " cl-card--baja" : "") },
        el("div", { className: "ps-card__cab" },
          el("strong", { className: "cl-card__nombre", textContent: c.empresa_nyp }),
          c.baja ? el("span", { className: "ps-chip", textContent: "De baja" }) : null),
        el("p", { className: "cl-card__linea", textContent: contacto || "Sin datos de contacto" }),
        autos,
        el("div", { className: "ps-card__pie" },
          el("span", { textContent: [c.localidad, c.cuit ? `CUIT/DNI ${c.cuit}` : ""].filter(Boolean).join(" · ") }),
          el("span", { textContent: c.ultima_visita ? `Última visita ${fmtFecha(c.ultima_visita)}` : "" })));
      card.tabIndex = 0;
      card.addEventListener("click", () => abrirCliente(c.id_cliente));
      card.addEventListener("keydown", (e) => e.key === "Enter" && abrirCliente(c.id_cliente));
      lista.append(card);
    });
  } catch (e) {
    avisar(e.message, true);
  }
}

/* =================== FICHA =================== */
function mostrarFicha(mostrar) {
  $("cl-ficha").hidden = !mostrar;
  $("cl-listado").hidden = mostrar;
  if (!mostrar) history.replaceState(null, "", "clientes.php");
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function nuevoCliente() {
  ficha = null;
  form.reset();
  form.id_cliente.value = "";
  $("cl-nombre").textContent = "Nuevo cliente";
  $("cl-contactar").innerHTML = "";
  $("cl-baja-aviso").hidden = true;
  ["cl-sec-vehiculos", "cl-sec-historial", "cl-sec-presupuestos", "cl-sec-resumen", "cl-sec-usuarios", "cl-dar-baja"]
    .forEach((id) => ($(id).hidden = true));
  editarDatos(true);
  $("cl-editar").hidden = true;
  mostrarFicha(true);
  form.empresa_nyp.focus();
}

async function abrirCliente(id, { mantenerHistorial = false } = {}) {
  try {
    ficha = await api({ accion: "ficha", id });
  } catch (e) {
    avisar(e.message, true);
    return;
  }
  const c = ficha.cliente;
  const deBaja = !!c.baja;
  history.replaceState(null, "", `clientes.php?id=${c.id_cliente}`);
  $("cl-nombre").textContent = c.empresa_nyp;
  pintarContactar(c);
  $("cl-baja-aviso").hidden = !deBaja;
  $("cl-dar-baja").hidden = deBaja;
  $("cl-editar").hidden = false;
  ["cl-sec-vehiculos", "cl-sec-presupuestos", "cl-sec-resumen", "cl-sec-usuarios"].forEach((s) => ($(s).hidden = false));
  $("cl-nuevo-auto").hidden = deBaja;
  $("cl-nuevo-usuario").hidden = deBaja;
  $("cl-presu-nuevo").hidden = deBaja;
  $("cl-presu-nuevo").href = `presupuestos.php?cliente=${c.id_cliente}`;

  editarDatos(false);
  pintarDatos(c);
  pintarResumen(ficha.resumen, ficha.vehiculos.length);
  pintarVehiculos();
  pintarPresupuestos();
  pintarUsuarios();
  if (!mantenerHistorial || !ficha.vehiculos.some((v) => v.patente === patenteAbierta)) cerrarHistorial();
  if (!mantenerHistorial) {
    fichaVehiculo = null;
    $("cl-sec-vficha").hidden = true;
  }
  mostrarFicha(true);
}

function pintarContactar(c) {
  const cont = $("cl-contactar");
  cont.innerHTML = "";
  const cel = soloNumeros(c.celular);
  if (cel) {
    // celulares argentinos: 54 9 + característica sin 0 + número sin 15
    const wa = cel.startsWith("54") ? cel : "549" + cel.replace(/^0/, "").replace(/^(\d{2,4})15/, "$1");
    cont.append(el("a", { className: "cl-accion cl-accion--wa", href: `https://wa.me/${wa}`, target: "_blank", rel: "noopener", textContent: "WhatsApp" }));
  }
  const tel = soloNumeros(c.celular || c.telefono);
  if (tel) cont.append(el("a", { className: "cl-accion", href: `tel:${tel}`, textContent: "Llamar" }));
  if (c.mail) cont.append(el("a", { className: "cl-accion", href: `mailto:${c.mail}`, textContent: "Mail" }));
}

function pintarDatos(c) {
  const dl = $("cl-datos");
  dl.innerHTML = "";
  const domicilio = [c.direccion, c.localidad, c.provincia, c.cpost ? `CP ${c.cpost}` : ""].filter(Boolean).join(", ");
  const filas = [["CUIT / DNI", c.cuit], ["Contacto", c.contacto], ["Celular", c.celular], ["Teléfono", c.telefono],
    ["Mail", c.mail], ["Domicilio", domicilio]];
  filas.filter(([, v]) => v).forEach(([t, v]) => dl.append(el("dt", { textContent: t }), el("dd", { textContent: v })));
  if (!dl.children.length) dl.append(el("dd", { className: "ps-sin", textContent: "Sin datos cargados." }));
}

function editarDatos(editar) {
  form.hidden = !editar;
  $("cl-datos").hidden = editar;
  $("cl-editar").hidden = editar;
  $("cl-error").hidden = true;
  if (editar && ficha) {
    const c = ficha.cliente;
    ["id_cliente", "empresa_nyp", "cuit", "contacto", "celular", "telefono", "mail", "direccion", "localidad", "provincia", "cpost"]
      .forEach((k) => (form[k].value = c[k] ?? ""));
  }
}

function pintarResumen(r, autos) {
  const dl = $("cl-resumen");
  dl.innerHTML = "";
  [["Vehículos", autos], ["Reparaciones", r.reparaciones], ["Presupuestos pendientes", r.pendientes],
    ["Trabajos aprobados", pesos(r.trabajado)], ["Última visita", r.ultima_visita ? fmtFecha(r.ultima_visita) : "—"]]
    .forEach(([t, v]) => dl.append(el("div", {}, el("dt", { textContent: t }), el("dd", { textContent: v }))));
}

/* ---------- Vehículos ---------- */
function pintarVehiculos() {
  const cont = $("cl-autos");
  cont.innerHTML = "";
  const deBaja = !!ficha.cliente.baja;
  if (!ficha.vehiculos.length) cont.append(el("p", { className: "ps-sin", textContent: "Todavía no tiene vehículos cargados." }));
  $("cl-nuevo-auto").open = !ficha.vehiculos.length && !deBaja;

  ficha.vehiculos.forEach((v) => {
    const card = el("article", { className: "tn-auto cl-auto" + (v.patente === patenteAbierta ? " is-elegido" : "") });
    const detalle = [v.anio, v.km_actual ? `${Number(v.km_actual).toLocaleString("es-AR")} km` : ""].filter(Boolean).join(" · ");
    const visitas = Number(v.reparaciones)
      ? `${v.reparaciones} ${Number(v.reparaciones) === 1 ? "reparación" : "reparaciones"} · última ${fmtFecha(v.ultima_visita)}`
      : "Sin reparaciones";
    const acciones = el("div", { className: "cl-auto__acciones" },
      boton("Historial", "au-link", () => abrirHistorial(v.patente)));
    if (!deBaja) {
      acciones.append(
        el("a", { className: "au-link", href: `presupuestos.php?cliente=${ficha.cliente.id_cliente}&vehiculo=${v.id_vehiculo}`, textContent: "Presupuestar" }),
        boton("Ficha y fotos", "au-link", () => abrirFichaVehiculo(v.patente)),
        boton("Quitar", "au-link cl-peligro", () => quitarVehiculo(v)));
    }
    if (v.foto) card.append(el("div", { className: "cl-auto__foto" }, el("img", { src: v.foto, alt: "", loading: "lazy" })));
    // append() escribiría "null" con los opcionales vacíos: los filtro
    card.append(...[el("span", { className: "tn-placa", textContent: v.patente }),
      el("strong", { textContent: vehiculoTexto(v) }),
      detalle ? el("small", { textContent: detalle }) : null,
      el("small", { textContent: visitas }),
      v.vendido_cliente ? el("span", { className: "ps-chip", textContent: `El cliente avisó que lo vendió el ${fmtFecha(v.vendido_cliente)}` }) : null,
      Number(v.fotos) ? el("small", { textContent: `${v.fotos} ${Number(v.fotos) === 1 ? "foto" : "fotos"}` }) : null,
      acciones].filter(Boolean));
    cont.append(card);
  });

  const ant = $("cl-anteriores");
  ant.innerHTML = "";
  ant.hidden = !ficha.anteriores.length;
  if (ficha.anteriores.length) {
    ant.append(el("p", { className: "ps-etiqueta", textContent: "Vehículos que tuvo" }));
    ficha.anteriores.forEach((v) => {
      const fila = el("div", { className: "cl-anterior" },
        el("span", { className: "tn-placa cl-placa-chica", textContent: v.patente }),
        el("span", { textContent: `${vehiculoTexto(v)} · hasta ${fmtFecha(v.hasta)}` }),
        boton("Historial", "au-link", () => abrirHistorial(v.patente)));
      ant.append(fila);
    });
  }
}

async function quitarVehiculo(v) {
  if (!confirm(`¿Quitar ${v.patente} de ${ficha.cliente.empresa_nyp}?\n\nEs para cuando lo vendió o ya no lo trae. No se borra nada: el historial queda con la patente.`)) return;
  try {
    await api(null, formData({ accion: "vehiculo_baja", id_vehiculo: v.id_vehiculo }));
    avisar(`${v.patente} ya no figura entre sus vehículos.`);
    abrirCliente(ficha.cliente.id_cliente);
  } catch (e) {
    avisar(e.message, true);
  }
}

$("cl-auto-patente").addEventListener("input", (e) => {
  e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
  $("cl-traspaso").hidden = true;
});

async function agregarVehiculo(confirmarTraspaso = false) {
  const datos = {
    accion: "vehiculo",
    id_cliente: ficha.cliente.id_cliente,
    patente: $("cl-auto-patente").value,
    marca: $("cl-auto-marca").value,
    modelo: $("cl-auto-modelo").value,
    anio: $("cl-auto-anio").value,
  };
  if (confirmarTraspaso) datos.confirmar_traspaso = 1;
  const btn = $("cl-auto-agregar");
  btn.disabled = true;
  try {
    const r = await api(null, formData(datos));
    if (r.traspaso) {
      const caja = $("cl-traspaso");
      caja.innerHTML = "";
      caja.append(
        el("p", {}, "La patente ", el("strong", { textContent: datos.patente }), ` (${vehiculoTexto(r)}) figura a nombre de `,
          el("strong", { textContent: r.cliente_anterior || "otro cliente" }), ". ¿La pasás a este cliente? El historial sigue con el auto."),
        boton("Sí, pasarla a este cliente", "btn btn--accent ag-btn", () => agregarVehiculo(true)));
      caja.hidden = false;
      return;
    }
    ["cl-auto-patente", "cl-auto-marca", "cl-auto-modelo", "cl-auto-anio"].forEach((id) => ($(id).value = ""));
    $("cl-traspaso").hidden = true;
    $("cl-nuevo-auto").open = false;
    avisar(r.traspasado ? `${datos.patente} pasó a este cliente con su historial.` : `${datos.patente} agregado.`);
    abrirCliente(ficha.cliente.id_cliente, { mantenerHistorial: true });
  } catch (e) {
    avisar(e.message, true);
  } finally {
    btn.disabled = false;
  }
}
$("cl-auto-agregar").addEventListener("click", () => agregarVehiculo(false));

/* ---------- Ficha y fotos del vehículo ---------- */
async function abrirFichaVehiculo(patente) {
  try {
    fichaVehiculo = { patente, ...(await api({ accion: "ficha_vehiculo", patente })) };
  } catch (e) {
    avisar(e.message, true);
    return;
  }
  $("cl-vf-patente").textContent = patente;
  pintarFichaVehiculo();
  $("cl-sec-vficha").hidden = false;
  $("cl-sec-vficha").scrollIntoView({ behavior: "smooth", block: "start" });
}

function pintarFichaVehiculo() {
  const { patente, campos, fotos, max_fotos: max } = fichaVehiculo;
  const deBaja = !!ficha.cliente.baja;
  const recargar = async () => {
    await abrirFichaVehiculo(patente);
    abrirCliente(ficha.cliente.id_cliente, { mantenerHistorial: true });
  };
  $("cl-vf-fotos").replaceChildren(FichaVehiculo.galeria({
    fotos, max, propias: fotos.filter((f) => f.subida_por === "taller").length, portada: null,
    puedeSubir: !deBaja,
    puedeQuitar: () => !deBaja, // el taller puede quitar cualquier foto (por ejemplo, una que no corresponde)
    subir: (archivo) => {
      const fd = formData({ accion: "vehiculo_foto", patente });
      fd.append("archivo", archivo);
      return api(null, fd);
    },
    quitar: (f) => api(null, formData({ accion: "vehiculo_foto_quitar", id_foto: f.id_foto })),
    alTerminar: recargar,
    avisar,
  }));
  $("cl-vf-datos").replaceChildren(FichaVehiculo.resumen(campos, fichaVehiculo.ficha)
    || el("p", { className: "ps-sin", textContent: "La ficha está vacía. Tocá “Completar / editar”." }));
  $("cl-vf-datos").hidden = false;
  $("cl-vf-form").hidden = true;
  $("cl-vf-editar").hidden = deBaja;
}

$("cl-vf-editar").addEventListener("click", () => {
  $("cl-vf-campos").replaceChildren(FichaVehiculo.formulario(fichaVehiculo.campos, fichaVehiculo.ficha));
  $("cl-vf-error").hidden = true;
  $("cl-vf-datos").hidden = true;
  $("cl-vf-form").hidden = false;
  $("cl-vf-editar").hidden = true;
});
$("cl-vf-cancelar").addEventListener("click", pintarFichaVehiculo);
$("cl-vf-cerrar").addEventListener("click", () => {
  fichaVehiculo = null;
  $("cl-sec-vficha").hidden = true;
});
$("cl-vf-form").addEventListener("submit", async (e) => {
  e.preventDefault();
  const error = $("cl-vf-error");
  error.hidden = true;
  try {
    await api(null, formData({ ...FichaVehiculo.leer($("cl-vf-form"), fichaVehiculo.campos), accion: "ficha_vehiculo_guardar", patente: fichaVehiculo.patente }));
    avisar(`Ficha de ${fichaVehiculo.patente} guardada.`);
    await abrirFichaVehiculo(fichaVehiculo.patente);
    abrirCliente(ficha.cliente.id_cliente, { mantenerHistorial: true });
  } catch (err) {
    error.textContent = err.message;
    error.hidden = false;
  }
});

/* ---------- Historial del vehículo ---------- */
async function abrirHistorial(patente) {
  try {
    const h = await api({ accion: "historial", patente, id_cliente: ficha.cliente.id_cliente });
    patenteAbierta = patente;
    $("cl-hist-patente").textContent = patente;
    $("cl-titulares").textContent = h.titulares.length > 1
      ? "Dueños: " + h.titulares.map((t) => `${t.empresa_nyp || "—"} (${t.hasta ? `hasta ${fmtFecha(t.hasta)}` : "actual"})`).join(" · ")
      : "";
    const linea = $("cl-linea");
    linea.innerHTML = "";
    h.pendientes.forEach((p) => linea.append(itemLinea({
      clase: "cl-linea__item--pendiente",
      fecha: p.fecha_presu,
      titulo: `Presupuesto #${p.id_presu} pendiente`,
      texto: p.descripcion_presu,
      monto: p.total_presu,
      href: `presupuestos.php?id=${p.id_presu}`,
    })));
    h.reparaciones.forEach((r) => linea.append(itemLinea({
      clase: r.dueno_anterior ? "cl-linea__item--anterior" : "",
      fecha: r.fecha_trab,
      titulo: r.descripcion_presu || "Reparación",
      texto: r.observaciones,
      datos: [r.km ? `${Number(r.km).toLocaleString("es-AR")} km` : "", r.fecha_entrega ? `entregado ${fmtFecha(r.fecha_entrega)}` : "en el taller",
        r.factura ? `factura ${r.factura}` : "", r.dueno_anterior ? `dueño anterior: ${r.empresa_nyp || "—"}` : ""].filter(Boolean).join(" · "),
      monto: r.total_presu,
      href: r.presupuesto ? `presupuestos.php?id=${r.presupuesto}` : null,
    })));
    if (!linea.children.length) linea.append(el("li", { className: "ps-sin", textContent: "Este vehículo todavía no tiene trabajos registrados." }));
    $("cl-sec-historial").hidden = false;
    pintarVehiculos();
    $("cl-sec-historial").scrollIntoView({ behavior: "smooth", block: "start" });
  } catch (e) {
    avisar(e.message, true);
  }
}

function itemLinea({ clase = "", fecha, titulo, texto, datos, monto, href }) {
  return el("li", { className: "cl-linea__item " + clase },
    el("span", { className: "cl-linea__fecha", textContent: fmtFecha(fecha) }),
    el("div", { className: "cl-linea__cuerpo" },
      el("strong", { textContent: titulo }),
      texto ? el("p", { textContent: texto }) : null,
      datos ? el("small", { textContent: datos }) : null),
    el("div", { className: "cl-linea__monto" },
      Number(monto) ? el("span", { textContent: pesos(monto) }) : null,
      href ? el("a", { className: "au-link", href, textContent: "Ver" }) : null));
}

function cerrarHistorial() {
  patenteAbierta = null;
  $("cl-sec-historial").hidden = true;
}
$("cl-hist-cerrar").addEventListener("click", () => {
  cerrarHistorial();
  pintarVehiculos();
});

/* ---------- Presupuestos ---------- */
function pintarPresupuestos() {
  const cont = $("cl-presus");
  cont.innerHTML = "";
  if (!ficha.presupuestos.length) {
    cont.append(el("p", { className: "ps-sin", textContent: "No tiene presupuestos." }));
    return;
  }
  ficha.presupuestos.forEach((p) => {
    cont.append(el("a", { className: "cl-presu", href: `presupuestos.php?id=${p.id_presu}` },
      el("span", { className: "ps-card__num", textContent: `#${p.id_presu}` }),
      el("span", { className: "tn-placa cl-placa-chica", textContent: p.patente || "—" }),
      el("span", { className: "cl-presu__desc", textContent: p.descripcion_presu || "" }),
      el("span", { className: "ps-chip" + (Number(p.usado) ? " ps-chip--ok" : ""), textContent: Number(p.usado) ? "Aprobado" : "Pendiente" }),
      el("strong", { className: "cl-presu__total", textContent: pesos(p.total_presu) })));
  });
}

/* ---------- Usuarios (acceso a Mi garage) ---------- */
function pintarUsuarios() {
  const cont = $("cl-usuarios");
  cont.innerHTML = "";
  const deBaja = !!ficha.cliente.baja;
  if (!ficha.usuarios.length) cont.append(el("p", { className: "ps-sin", textContent: "Nadie tiene acceso todavía." }));
  ficha.usuarios.forEach((u) => {
    const nombre = [u.nombre, u.apellido].filter(Boolean).join(" ") || u.usuario;
    const estado = u.ultimo_login ? `Entró por última vez el ${fmtFecha(u.ultimo_login)}`
      : Number(u.tiene_acceso) ? "Tiene contraseña, todavía no entró" : "Todavía no eligió su contraseña";
    const acciones = el("div", { className: "cl-auto__acciones" });
    if (!deBaja) {
      acciones.append(
        boton(Number(u.tiene_acceso) ? "Reenviar acceso" : "Enviar acceso", "au-link", () => enviarAcceso(u)),
        boton("Editar", "au-link", () => editarUsuario(u)),
        boton("Quitar acceso", "au-link cl-peligro", () => quitarUsuario(u, nombre)));
    }
    cont.append(el("article", { className: "cl-usuario" },
      el("strong", { textContent: nombre }),
      el("span", { textContent: u.email || u.usuario }),
      el("small", { textContent: estado }),
      acciones));
  });
}

function editarUsuario(u) {
  formUsuario.reset();
  ["id_user", "nombre", "apellido", "email", "dni"].forEach((k) => (formUsuario[k].value = u[k] ?? ""));
  formUsuario.enviar_acceso.closest("label").hidden = true;
  $("cl-error-usuario").hidden = true;
  $("cl-nuevo-usuario").open = true;
  formUsuario.nombre.focus();
}

function limpiarUsuario() {
  formUsuario.reset();
  formUsuario.id_user.value = "";
  formUsuario.enviar_acceso.closest("label").hidden = false;
  $("cl-error-usuario").hidden = true;
  $("cl-nuevo-usuario").open = false;
}

async function enviarAcceso(u) {
  try {
    const r = await api(null, formData({ accion: "usuario_acceso", id: u.id_user }));
    avisar(`Se envió a ${r.enviado} el link para elegir la contraseña.` + (r.link_prueba ? ` (Prueba local: ${r.link_prueba})` : ""));
  } catch (e) {
    avisar(e.message, true);
  }
}

async function quitarUsuario(u, nombre) {
  if (!confirm(`¿Quitarle el acceso a ${nombre}?\n\nYa no va a poder entrar a ver los autos de este cliente.`)) return;
  try {
    await api(null, formData({ accion: "usuario_baja", id: u.id_user }));
    avisar(`${nombre} ya no tiene acceso.`);
    abrirCliente(ficha.cliente.id_cliente, { mantenerHistorial: true });
  } catch (e) {
    avisar(e.message, true);
  }
}

formUsuario.addEventListener("submit", async (e) => {
  e.preventDefault();
  const error = $("cl-error-usuario");
  error.hidden = true;
  const datos = Object.fromEntries(new FormData(formUsuario));
  try {
    const r = await api(null, formData({ ...datos, accion: "usuario", id_cliente: ficha.cliente.id_cliente,
      enviar_acceso: formUsuario.enviar_acceso.checked && !datos.id_user ? 1 : "" }));
    let msg = datos.id_user ? "Datos actualizados." : r.vinculado
      ? "Esa persona ya tenía cuenta en la página: quedó vinculada a este cliente."
      : "Acceso creado.";
    if (r.enviado) msg += ` Se le envió el link a ${r.enviado}.` + (r.link_prueba ? ` (Prueba local: ${r.link_prueba})` : "");
    avisar(msg);
    limpiarUsuario();
    abrirCliente(ficha.cliente.id_cliente, { mantenerHistorial: true });
  } catch (err) {
    error.textContent = err.message;
    error.hidden = false;
  }
});
$("cl-usuario-cancelar").addEventListener("click", limpiarUsuario);

/* ---------- Datos del cliente ---------- */
form.addEventListener("submit", async (e) => {
  e.preventDefault();
  const error = $("cl-error");
  error.hidden = true;
  try {
    const nuevo = !form.id_cliente.value;
    const r = await api(null, formData({ ...Object.fromEntries(new FormData(form)), accion: "guardar" }));
    avisar(nuevo ? "Cliente creado. Ahora podés agregarle vehículos." : "Datos guardados.");
    await abrirCliente(r.id, { mantenerHistorial: true });
    if (nuevo) $("cl-nuevo-auto").open = true;
    cargarListado();
  } catch (err) {
    error.textContent = err.message;
    error.hidden = false;
  }
});

$("cl-editar").addEventListener("click", () => {
  editarDatos(true);
  form.empresa_nyp.focus();
});
$("cl-cancelar").addEventListener("click", () => (ficha ? editarDatos(false) : mostrarFicha(false)));

async function cambiarBaja(accion) {
  const c = ficha.cliente;
  if (accion === "baja" && !confirm(`¿Dar de baja a ${c.empresa_nyp}?\n\nDeja de aparecer en el listado y en los presupuestos. No se borra nada y se puede reactivar.`)) return;
  try {
    await api(null, formData({ accion, id: c.id_cliente }));
    avisar(accion === "baja" ? `${c.empresa_nyp} quedó dado de baja.` : `${c.empresa_nyp} está activo otra vez.`);
    abrirCliente(c.id_cliente);
    cargarListado();
  } catch (e) {
    avisar(e.message, true);
  }
}
$("cl-dar-baja").addEventListener("click", () => cambiarBaja("baja"));
$("cl-reactivar").addEventListener("click", () => cambiarBaja("reactivar"));

/* =================== INICIO =================== */
$("cl-nuevo").addEventListener("click", nuevoCliente);
$("cl-volver").addEventListener("click", () => {
  mostrarFicha(false);
  cargarListado();
});
$("cl-q").addEventListener("input", demorar(cargarListado, 300));
$("cl-bajas").addEventListener("change", cargarListado);

cargarListado();
const idInicial = new URLSearchParams(location.search).get("id");
if (idInicial) abrirCliente(idInicial);
