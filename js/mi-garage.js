/* Mi garage (cliente): perfil, autos activos, autos vendidos y detalle de cada auto */
const API = "./php/garage_api.php";
const $ = (id) => document.getElementById(id);
const mgAviso = $("mg-aviso");
let datos = null; // { perfil, autos, vendidos }
let detalle = null; // respuesta de accion=auto
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
const { fmtFecha } = FichaVehiculo;
const pesos = (n) => "$ " + Number(n || 0).toLocaleString("es-AR", { maximumFractionDigits: 0 });
const kms = (n) => `${Number(n).toLocaleString("es-AR")} km`;
const nombreAuto = (a) => [a.marca, a.modelo].filter(Boolean).join(" ") || "Mi auto";
const hoyISO = () => {
  const d = new Date();
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, "0")}-${String(d.getDate()).padStart(2, "0")}`;
};

function avisar(msg, error = false) {
  mgAviso.textContent = msg;
  mgAviso.classList.toggle("cf-aviso--error", error);
  mgAviso.hidden = false;
  clearTimeout(avisoTimer);
  avisoTimer = setTimeout(() => (mgAviso.hidden = true), 5000);
}

async function api(params, cuerpo) {
  const url = cuerpo ? API : `${API}?${new URLSearchParams(params)}`;
  const res = await fetch(url, cuerpo ? { method: "POST", body: cuerpo } : undefined);
  const r = await res.json().catch(() => ({}));
  if (res.status === 401) location.href = "login.php?volver=mi-garage.php";
  if (!res.ok) throw new Error(r.error || "Ocurrió un error");
  return r;
}

const formData = (obj) => {
  const fd = new FormData();
  Object.entries(obj).forEach(([k, v]) => fd.append(k, v ?? ""));
  return fd;
};

// días que faltan para una fecha (negativo = vencida)
const diasHasta = (iso) => Math.round((new Date(iso + "T00:00:00") - new Date(hoyISO() + "T00:00:00")) / 86400000);

const VENCIMIENTOS = [
  ["vtv_vencimiento", "VTV"],
  ["seguro_vencimiento", "Seguro"],
  ["gnc_oblea_vencimiento", "Oblea GNC"],
  ["gnc_prueba_hidraulica", "Prueba hidráulica GNC"],
  ["matafuego_vencimiento", "Matafuego"],
];

function estadoVencimiento(iso) {
  const d = diasHasta(iso);
  if (d < 0) return ["vencido", `Vencido el ${fmtFecha(iso)}`];
  if (d <= 30) return ["pronto", d === 0 ? "Vence hoy" : `Vence en ${d} ${d === 1 ? "día" : "días"}`];
  return ["ok", `Vence el ${fmtFecha(iso)}`];
}

/* =================== GARAGE =================== */
async function cargar() {
  try {
    datos = await api({ accion: "datos" });
  } catch (e) {
    avisar(e.message, true);
    return;
  }
  pintarPerfil();
  pintarAutos();
  pintarVendidos();
}

function pintarAutos() {
  const cont = $("mg-autos");
  cont.innerHTML = "";
  datos.autos.forEach((a) => {
    const chips = el("div", { className: "mg-auto__chips" });
    if (a.confirmado) chips.append(el("span", { className: "ps-chip ps-chip--ok", textContent: "Registrado en el taller" }));
    if (a.sucursal) chips.append(el("span", { className: "ps-chip", textContent: a.sucursal }));
    if (a.en_taller) chips.append(el("span", { className: "ps-chip" + (a.en_taller === "listo" ? " ps-chip--ok" : ""), textContent: a.en_taller === "listo" ? "¡Listo para retirar!" : "En el taller" }));
    if (a.turno) chips.append(el("span", { className: "ps-chip ps-chip--lavado", textContent: `Turno ${fmtFecha(a.turno)} ${a.turno.slice(11)} hs` }));
    VENCIMIENTOS.forEach(([campo, nombre]) => {
      if (!a[campo]) return;
      const [estado] = estadoVencimiento(a[campo]);
      if (estado !== "ok") chips.append(el("span", { className: "ps-chip mg-chip--" + estado, textContent: `${nombre} ${estado === "vencido" ? "vencida" : "por vencer"}` }));
    });
    const card = el("button", { type: "button", className: "mg-auto" },
      el("div", { className: "mg-auto__foto" + (a.foto ? "" : " mg-auto__foto--vacia") },
        a.foto ? el("img", { src: a.foto, alt: "", loading: "lazy" }) : el("span", { textContent: "Sin fotos" })),
      el("div", { className: "mg-auto__cuerpo" },
        el("span", { className: "tn-placa", textContent: a.patente }),
        el("strong", { className: "mg-auto__nombre", textContent: nombreAuto(a) }),
        el("small", { textContent: [a.version, a.anio, a.color, Number(a.km) ? kms(a.km) : ""].filter(Boolean).join(" · ") }),
        chips.children.length ? chips : null));
    card.addEventListener("click", () => abrirAuto(a.id));
    cont.append(card);
  });
  const sumar = el("button", { type: "button", className: "mg-auto mg-auto--sumar" },
    el("strong", { textContent: "+" }), el("span", { textContent: "Agregar un auto" }));
  sumar.addEventListener("click", () => {
    $("mg-agregar").hidden = false;
    $("mg-form-agregar").patente.focus();
  });
  cont.append(sumar);
  if (!datos.autos.length) $("mg-agregar").hidden = false;
}

function pintarVendidos() {
  const cont = $("mg-vendidos");
  cont.innerHTML = "";
  $("mg-sec-vendidos").hidden = !datos.vendidos.length;
  datos.vendidos.forEach((a) => {
    const card = el("button", { type: "button", className: "mg-vendido" },
      el("div", { className: "mg-vendido__foto" + (a.foto ? "" : " mg-auto__foto--vacia") },
        a.foto ? el("img", { src: a.foto, alt: "", loading: "lazy" }) : null),
      el("div", { className: "mg-vendido__texto" },
        el("span", { className: "tn-placa cl-placa-chica", textContent: a.patente }),
        el("strong", { textContent: nombreAuto(a) }),
        el("small", { textContent: `Vendido el ${fmtFecha(a.vendido)}` })));
    card.title = "Ver (solo lectura)";
    card.addEventListener("click", () => abrirAuto(a.id));
    cont.append(card);
  });
}

$("mg-form-agregar").addEventListener("submit", async (e) => {
  e.preventDefault();
  const f = e.target;
  const error = $("mg-error-agregar");
  error.hidden = true;
  try {
    const r = await api(null, formData({ ...Object.fromEntries(new FormData(f)), accion: "auto_agregar" }));
    f.reset();
    $("mg-agregar").hidden = true;
    if (r.aviso) avisar(r.aviso, true);
    else avisar("Auto agregado. Completá sus datos y subí fotos.");
    await cargar();
    abrirAuto(r.id);
  } catch (err) {
    error.textContent = err.message;
    error.hidden = false;
  }
});
$("mg-form-agregar").patente.addEventListener("input", (e) => (e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, "")));
$("mg-agregar-cancelar").addEventListener("click", () => {
  $("mg-form-agregar").reset();
  $("mg-agregar").hidden = true;
});

/* =================== DETALLE DE UN AUTO =================== */
function mostrarDetalle(mostrar) {
  $("mg-detalle").hidden = !mostrar;
  $("mg-garage").hidden = mostrar;
  if (!mostrar) history.replaceState(null, "", "mi-garage.php");
  window.scrollTo({ top: 0, behavior: "smooth" });
}

async function abrirAuto(id, { mantenerScroll = false } = {}) {
  try {
    detalle = await api({ accion: "auto", id });
  } catch (e) {
    avisar(e.message, true);
    return;
  }
  const { auto, ficha, bloqueo } = detalle;
  const editable = !bloqueo;
  history.replaceState(null, "", `mi-garage.php?auto=${auto.id}`);
  $("mg-d-patente").textContent = auto.patente;
  $("mg-d-nombre").textContent = [nombreAuto(ficha), ficha.anio].filter(Boolean).join(" ");
  $("mg-detalle").classList.toggle("is-vendido", !!auto.vendido);
  $("mg-d-bloqueo").textContent = auto.vendido ? `Vendido el ${fmtFecha(auto.vendido)}. ${bloqueo}` : bloqueo || "";
  $("mg-d-bloqueo").hidden = !bloqueo;
  $("mg-d-editar").hidden = !editable;
  pintarAcciones(editable);
  pintarFotos(editable);
  pintarVencimientos();
  pintarDatos();
  pintarPresupuestos();
  pintarHistorial();
  pintarZonaBaja();
  if (!mantenerScroll) mostrarDetalle(true);
  else $("mg-detalle").hidden = false;
}

function pintarAcciones(editable) {
  const cont = $("mg-d-acciones");
  cont.innerHTML = "";
  const { auto, ficha } = detalle;
  if (auto.vendido) return;
  cont.append(el("a", { className: "btn btn--accent ag-btn", href: "turnos.php", textContent: "Pedir turno" }));
  if (!editable) return;
  const km = el("form", { className: "mg-km" },
    el("label", {}, el("span", { textContent: ficha.km_actual ? `Kilometraje (último: ${kms(ficha.km_actual)})` : "Kilometraje actual" }),
      el("input", { className: "ps-input", name: "km", inputMode: "numeric", placeholder: "Ej. 85000", autocomplete: "off" })),
    el("button", { type: "submit", className: "btn btn--ghost ag-btn", textContent: "Actualizar km" }));
  km.addEventListener("submit", async (e) => {
    e.preventDefault();
    try {
      const r = await api(null, formData({ accion: "auto_km", id: auto.id, km: km.km.value }));
      avisar(`Kilometraje actualizado: ${kms(r.km)}.`);
      abrirAuto(auto.id, { mantenerScroll: true });
      cargar();
    } catch (err) {
      avisar(err.message, true);
    }
  });
  cont.append(km);
}

function pintarFotos(editable) {
  const { auto, fotos, max_fotos: max } = detalle;
  const propias = fotos.filter((f) => f.propia && f.subida_por === "cliente").length;
  const recargar = () => {
    abrirAuto(auto.id, { mantenerScroll: true });
    cargar();
  };
  $("mg-d-fotos").replaceChildren(FichaVehiculo.galeria({
    fotos, max, propias, portada: auto.portada || fotos[0]?.id_foto, puedeSubir: editable,
    puedeQuitar: (f) => editable && f.propia && f.subida_por === "cliente",
    subir: (archivo) => {
      const fd = formData({ accion: "auto_foto", id: auto.id });
      fd.append("archivo", archivo);
      return api(null, fd);
    },
    quitar: (f) => api(null, formData({ accion: "auto_foto_quitar", id: auto.id, id_foto: f.id_foto })),
    elegirPortada: editable ? (f) => api(null, formData({ accion: "auto_portada", id: auto.id, id_foto: f.id_foto })) : null,
    alTerminar: recargar,
    avisar,
  }));
}

function pintarVencimientos() {
  const cont = $("mg-d-vencimientos");
  cont.innerHTML = "";
  VENCIMIENTOS.forEach(([campo, nombre]) => {
    const fecha = detalle.ficha[campo];
    if (!fecha) return;
    const [estado, texto] = estadoVencimiento(fecha);
    cont.append(el("div", { className: "mg-venc mg-venc--" + estado }, el("strong", { textContent: nombre }), el("span", { textContent: texto })));
  });
  $("mg-d-sec-vencimientos").hidden = !cont.children.length;
}

function pintarDatos() {
  const { campos, ficha, auto } = detalle;
  const res = FichaVehiculo.resumen(campos, ficha);
  const cont = $("mg-d-datos");
  cont.replaceChildren(res || el("p", { className: "ps-sin", textContent: "Todavía no hay datos cargados. Tocá “Completar / editar”." }));
  if (auto.descripcion) cont.prepend(el("p", { className: "mg-nota", textContent: auto.descripcion }));
  $("mg-d-form").hidden = true;
  cont.hidden = false;
}

$("mg-d-editar").addEventListener("click", () => {
  const { campos, ficha, auto } = detalle;
  $("mg-d-campos").replaceChildren(FichaVehiculo.formulario(campos, ficha));
  const tallerCargo = FichaVehiculo.resumen(campos, ficha, { soloNoEditables: true });
  if (tallerCargo) {
    $("mg-d-campos").append(el("details", { className: "mg-del-taller" },
      el("summary", { textContent: "Datos técnicos que carga el taller" }), tallerCargo));
  }
  $("mg-d-form").descripcion.value = auto.descripcion || "";
  $("mg-d-error").hidden = true;
  $("mg-d-datos").hidden = true;
  $("mg-d-form").hidden = false;
  $("mg-d-editar").hidden = true;
});
$("mg-d-cancelar").addEventListener("click", () => {
  pintarDatos();
  $("mg-d-editar").hidden = false;
});
$("mg-d-form").addEventListener("submit", async (e) => {
  e.preventDefault();
  const error = $("mg-d-error");
  error.hidden = true;
  try {
    await api(null, formData({ ...FichaVehiculo.leer($("mg-d-form"), detalle.campos), descripcion: $("mg-d-form").descripcion.value,
      accion: "auto_guardar", id: detalle.auto.id }));
    avisar("Datos guardados.");
    abrirAuto(detalle.auto.id, { mantenerScroll: true });
    cargar();
  } catch (err) {
    error.textContent = err.message;
    error.hidden = false;
  }
});

function pintarPresupuestos() {
  const cont = $("mg-d-presupuestos");
  cont.innerHTML = "";
  detalle.presupuestos.forEach((p) => {
    const vence = p.dias_presu ? new Date(new Date(p.fecha_presu + "T00:00:00").getTime() + p.dias_presu * 86400000) : null;
    cont.append(el("article", { className: "mg-presu" },
      el("div", { className: "mg-presu__cab" },
        el("strong", { textContent: `Presupuesto #${p.id_presu}` }),
        el("span", { textContent: fmtFecha(p.fecha_presu) + (vence ? ` · válido hasta ${vence.toLocaleDateString("es-AR", { day: "numeric", month: "short" })}` : "") })),
      el("p", { textContent: p.descripcion_presu || "" }),
      el("dl", {},
        el("dt", { textContent: "Repuestos" }), el("dd", { textContent: pesos(p.repuestos_presu) }),
        el("dt", { textContent: "Mano de obra" }), el("dd", { textContent: pesos(p.mobra_presu) }),
        el("dt", { className: "mg-presu__total", textContent: "Total" }), el("dd", { className: "mg-presu__total", textContent: pesos(p.total_presu) }),
        Number(p.senia_presu) ? el("dt", { textContent: "Seña" }) : null, Number(p.senia_presu) ? el("dd", { textContent: pesos(p.senia_presu) }) : null),
      el("p", { className: "fv-ayuda", textContent: "Para aprobarlo o consultarlo, escribinos por WhatsApp o llamanos." })));
  });
  $("mg-d-sec-presupuestos").hidden = !detalle.presupuestos.length;
}

function pintarHistorial() {
  const lista = $("mg-d-historial");
  lista.innerHTML = "";
  if (!detalle.auto.confirmado) {
    lista.append(el("li", { className: "ps-sin", textContent: "El historial aparece cuando el taller confirma que el auto es tuyo. Se hace en tu próxima visita." }));
    return;
  }
  if (!detalle.historial.length) {
    lista.append(el("li", { className: "ps-sin", textContent: "Todavía no hay trabajos registrados." }));
    return;
  }
  detalle.historial.forEach((h) => {
    const datosLinea = [h.km ? kms(h.km) : "", h.fecha_entrega ? `entregado ${fmtFecha(h.fecha_entrega)}` : h.estado === "listo" ? "listo para retirar" : "en el taller",
      h.anterior ? "con el dueño anterior" : ""].filter(Boolean).join(" · ");
    lista.append(el("li", { className: "cl-linea__item" + (h.anterior ? " cl-linea__item--anterior" : "") },
      el("span", { className: "cl-linea__fecha", textContent: fmtFecha(h.fecha) }),
      el("div", { className: "cl-linea__cuerpo" },
        el("strong", { textContent: h.trabajo || "Trabajo en el taller" }),
        h.observaciones ? el("p", { textContent: h.observaciones }) : null,
        el("small", { textContent: datosLinea })),
      el("div", { className: "cl-linea__monto" }, Number(h.total) ? el("span", { textContent: pesos(h.total) }) : null)));
  });
}

function pintarZonaBaja() {
  const cont = $("mg-d-baja");
  cont.innerHTML = "";
  const { auto } = detalle;
  if (auto.vendido) return;

  const vender = el("details", { className: "mg-vender" },
    el("summary", { textContent: "Lo vendí" }));
  const fecha = el("input", { type: "date", className: "ps-input", value: hoyISO(), max: hoyISO() });
  vender.append(
    el("p", { textContent: "El auto pasa a “Autos que tuve”, con una cruz. Su historial y sus fotos quedan, pero ya no vas a poder cambiar sus datos." }),
    el("label", { className: "fv-campo" }, el("span", { textContent: "Fecha de venta" }), fecha),
    boton("Marcar como vendido", "btn btn--accent ag-btn", async () => {
      if (!confirm(`¿Marcar ${auto.patente} como vendido? Después no se puede deshacer desde acá.`)) return;
      try {
        await api(null, formData({ accion: "auto_vendido", id: auto.id, fecha: fecha.value }));
        avisar(`${auto.patente} quedó como vendido.`);
        mostrarDetalle(false);
        cargar();
      } catch (e) {
        avisar(e.message, true);
      }
    }));
  cont.append(vender);

  if (!auto.confirmado) {
    cont.append(boton("Lo cargué por error: quitarlo", "cl-dar-baja", async () => {
      if (!confirm(`¿Quitar ${auto.patente} de tu garage?`)) return;
      try {
        await api(null, formData({ accion: "auto_quitar", id: auto.id }));
        avisar(`${auto.patente} ya no está en tu garage.`);
        mostrarDetalle(false);
        cargar();
      } catch (e) {
        avisar(e.message, true);
      }
    }));
  }
}

$("mg-volver").addEventListener("click", () => mostrarDetalle(false));

/* =================== PERFIL =================== */
function pintarPerfil() {
  const p = datos.perfil;
  const nombre = [p.nombre, p.apellido].filter(Boolean).join(" ") || p.email;
  $("mg-hola").textContent = `Hola, ${p.nombre || "bienvenido"}`;
  $("mg-p-nombre").textContent = nombre;
  $("mg-p-email").textContent = p.email;
  const img = $("mg-avatar-img");
  img.hidden = !p.foto;
  if (p.foto) img.src = p.foto;
  $("mg-avatar-iniciales").hidden = !!p.foto;
  $("mg-avatar-iniciales").textContent = nombre.split(/\s+/).slice(0, 2).map((x) => x[0]).join("").toUpperCase();
  $("mg-avatar-quitar").hidden = !p.foto_propia;

  const dl = $("mg-p-datos");
  dl.innerHTML = "";
  [["Celular", p.celular], ["DNI", p.dni], ["Ingresás con", ["mail y contraseña", ...p.ingresos].filter((x, i) => i || p.tiene_clave).join(", ")]]
    .filter(([, v]) => v)
    .forEach(([t, v]) => dl.append(el("dt", { textContent: t }), el("dd", { textContent: v })));
  $("mg-clave-actual").hidden = !p.tiene_clave;
  $("mg-clave-abrir").textContent = p.tiene_clave ? "Cambiar contraseña" : "Crear una contraseña";
}

function editarPerfil(editar) {
  const f = $("mg-form-perfil");
  f.hidden = !editar;
  $("mg-p-datos").hidden = editar;
  $("mg-perfil-editar").hidden = editar;
  $("mg-error-perfil").hidden = true;
  if (editar) ["nombre", "apellido", "celular", "dni"].forEach((k) => (f[k].value = datos.perfil[k] || ""));
}
$("mg-perfil-editar").addEventListener("click", () => editarPerfil(true));
$("mg-perfil-cancelar").addEventListener("click", () => editarPerfil(false));
$("mg-form-perfil").addEventListener("submit", async (e) => {
  e.preventDefault();
  try {
    const r = await api(null, formData({ ...Object.fromEntries(new FormData(e.target)), accion: "perfil" }));
    datos.perfil = r.perfil;
    editarPerfil(false);
    pintarPerfil();
    avisar("Tus datos se guardaron.");
  } catch (err) {
    $("mg-error-perfil").textContent = err.message;
    $("mg-error-perfil").hidden = false;
  }
});

$("mg-clave-abrir").addEventListener("click", () => {
  $("mg-form-clave").hidden = false;
  $("mg-error-clave").hidden = true;
});
$("mg-clave-cancelar").addEventListener("click", () => {
  $("mg-form-clave").reset();
  $("mg-form-clave").hidden = true;
});
$("mg-form-clave").addEventListener("submit", async (e) => {
  e.preventDefault();
  try {
    await api(null, formData({ ...Object.fromEntries(new FormData(e.target)), accion: "clave" }));
    e.target.reset();
    e.target.hidden = true;
    datos.perfil.tiene_clave = true;
    pintarPerfil();
    avisar("Contraseña actualizada.");
  } catch (err) {
    $("mg-error-clave").textContent = err.message;
    $("mg-error-clave").hidden = false;
  }
});

$("mg-avatar-archivo").addEventListener("change", async (e) => {
  const archivo = e.target.files[0];
  if (!archivo) return;
  const fd = formData({ accion: "foto_perfil" });
  fd.append("archivo", archivo);
  try {
    await api(null, fd);
    avisar("Foto actualizada.");
    location.reload(); // también cambia la foto del menú
  } catch (err) {
    avisar(err.message, true);
  }
  e.target.value = "";
});
$("mg-avatar-quitar").addEventListener("click", async () => {
  if (!confirm("¿Quitar tu foto?")) return;
  try {
    await api(null, formData({ accion: "foto_perfil_quitar" }));
    location.reload();
  } catch (err) {
    avisar(err.message, true);
  }
});

/* =================== INICIO =================== */
cargar().then(() => {
  const id = new URLSearchParams(location.search).get("auto");
  if (id) abrirAuto(id);
});
