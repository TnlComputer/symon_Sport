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
const pesos = (n) => "$ " + Number(n || 0).toLocaleString(LOCALE, { maximumFractionDigits: 0 });
const kms = (n) => `${Number(n).toLocaleString(LOCALE)} km`;
const nombreAuto = (a) => [a.marca, a.modelo].filter(Boolean).join(" ") || t("Mi auto");
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
  if (!res.ok) throw new Error(r.error || t("Ocurrió un error"));
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
  ["vtv_vencimiento", t("VTV")],
  ["seguro_vencimiento", t("Seguro")],
  ["gnc_oblea_vencimiento", t("Oblea GNC")],
  ["gnc_prueba_hidraulica", t("Prueba hidráulica GNC")],
  ["matafuego_vencimiento", t("Matafuego")],
];

function estadoVencimiento(iso) {
  const d = diasHasta(iso);
  if (d < 0) return ["vencido", t("Vencido el {0}", fmtFecha(iso))];
  if (d <= 30) return ["pronto", d === 0 ? t("Vence hoy") : d === 1 ? t("Vence en {0} día", d) : t("Vence en {0} días", d)];
  return ["ok", t("Vence el {0}", fmtFecha(iso))];
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
    if (a.confirmado) chips.append(el("span", { className: "ps-chip ps-chip--ok", textContent: t("Registrado en el taller") }));
    if (a.sucursal) chips.append(el("span", { className: "ps-chip", textContent: a.sucursal }));
    if (a.en_taller) chips.append(el("span", { className: "ps-chip" + (a.en_taller === "listo" ? " ps-chip--ok" : ""), textContent: a.en_taller === "listo" ? t("¡Listo para retirar!") : t("En el taller") }));
    if (a.turno) chips.append(el("span", { className: "ps-chip ps-chip--lavado", textContent: t("Turno {0} {1} hs", fmtFecha(a.turno), a.turno.slice(11)) }));
    VENCIMIENTOS.forEach(([campo, nombre]) => {
      if (!a[campo]) return;
      const [estado] = estadoVencimiento(a[campo]);
      if (estado !== "ok") chips.append(el("span", { className: "ps-chip mg-chip--" + estado, textContent: estado === "vencido" ? t("{0} vencida", nombre) : t("{0} por vencer", nombre) }));
    });
    const card = el("button", { type: "button", className: "mg-auto" },
      el("div", { className: "mg-auto__foto" + (a.foto ? "" : " mg-auto__foto--vacia") },
        a.foto ? el("img", { src: a.foto, alt: "", loading: "lazy" }) : el("span", { textContent: t("Sin fotos") })),
      el("div", { className: "mg-auto__cuerpo" },
        el("span", { className: "tn-placa", textContent: a.patente }),
        el("strong", { className: "mg-auto__nombre", textContent: nombreAuto(a) }),
        el("small", { textContent: [a.version, a.anio, a.color, Number(a.km) ? kms(a.km) : ""].filter(Boolean).join(" · ") }),
        chips.children.length ? chips : null));
    card.addEventListener("click", () => abrirAuto(a.id));
    cont.append(card);
  });
  const sumar = el("button", { type: "button", className: "mg-auto mg-auto--sumar" },
    el("strong", { textContent: "+" }), el("span", { textContent: t("Agregar un auto") }));
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
        el("small", { textContent: t("Vendido el {0}", fmtFecha(a.vendido)) })));
    card.title = t("Ver (solo lectura)");
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
    else avisar(t("Auto agregado. Completá sus datos y subí fotos."));
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
  $("mg-d-bloqueo").textContent = auto.vendido ? `${t("Vendido el {0}", fmtFecha(auto.vendido))}. ${bloqueo}` : bloqueo || "";
  $("mg-d-bloqueo").hidden = !bloqueo;
  $("mg-d-editar").hidden = !editable;
  pintarAcciones(editable);
  pintarFotos(editable);
  pintarVencimientos();
  pintarDatos();
  pintarPresupuestos(editable);
  pintarHistorial(editable);
  pintarZonaBaja();
  if (!mantenerScroll) mostrarDetalle(true);
  else $("mg-detalle").hidden = false;
}

function pintarAcciones(editable) {
  const cont = $("mg-d-acciones");
  cont.innerHTML = "";
  const { auto, ficha } = detalle;
  if (auto.vendido) return;
  cont.append(el("a", { className: "btn btn--accent ag-btn", href: "turnos.php", textContent: t("Pedir turno") }));
  if (!editable) return;
  const km = el("form", { className: "mg-km" },
    el("label", {}, el("span", { textContent: ficha.km_actual ? t("Kilometraje (último: {0})", kms(ficha.km_actual)) : t("Kilometraje actual") }),
      el("input", { className: "ps-input", name: "km", inputMode: "numeric", placeholder: t("Ej. 85000"), autocomplete: "off" })),
    el("button", { type: "submit", className: "btn btn--ghost ag-btn", textContent: t("Actualizar km") }));
  km.addEventListener("submit", async (e) => {
    e.preventDefault();
    try {
      const r = await api(null, formData({ accion: "auto_km", id: auto.id, km: km.km.value }));
      avisar(t("Kilometraje actualizado: {0}.", kms(r.km)));
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
  cont.replaceChildren(res || el("p", { className: "ps-sin", textContent: t("Todavía no hay datos cargados. Tocá “Completar / editar”.") }));
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
      el("summary", { textContent: t("Datos técnicos que carga el taller") }), tallerCargo));
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
    avisar(t("Datos guardados."));
    abrirAuto(detalle.auto.id, { mantenerScroll: true });
    cargar();
  } catch (err) {
    error.textContent = err.message;
    error.hidden = false;
  }
});

async function responderPresupuesto(p, respuesta, motivo = "") {
  try {
    await api(null, formData({ accion: "presu_responder", id: detalle.auto.id, id_presu: p.id_presu, respuesta, motivo }));
    avisar(respuesta === "aprobado" ? t("¡Gracias! Le avisamos al taller que lo aprobaste. Te contactamos para coordinar el ingreso.") : t("Listo, le avisamos al taller que no lo aprobás."));
    abrirAuto(detalle.auto.id, { mantenerScroll: true });
  } catch (e) {
    avisar(e.message, true);
  }
}

// aprobar / rechazar (puede cambiar de opinión mientras el auto no haya entrado al taller)
function respuestaPresupuesto(p, editable) {
  const cont = el("div", { className: "mg-presu__respuesta" });
  if (p.respuesta_cliente) {
    cont.append(el("p", { className: "mg-presu__estado mg-presu__estado--" + p.respuesta_cliente,
      textContent: (p.respuesta_cliente === "aprobado" ? t("Lo aprobaste el {0}.", fmtFecha(p.respuesta_fecha)) : t("No lo aprobaste el {0}.", fmtFecha(p.respuesta_fecha))) + (p.respuesta_motivo ? ` “${p.respuesta_motivo}”` : "") }));
  }
  if (!editable) return cont;
  if (p.vencido && p.respuesta_cliente !== "aprobado") {
    cont.append(el("p", { className: "fv-ayuda", textContent: t("Este presupuesto venció. Escribinos y te lo actualizamos.") }));
    return cont;
  }
  const botones = el("div", { className: "cl-botones" });
  if (p.respuesta_cliente !== "rechazado") {
    botones.append(boton(p.respuesta_cliente === "aprobado" ? t("Me arrepentí: no aprobarlo") : t("No lo apruebo"), "btn btn--ghost ag-btn", () => {
      const motivo = prompt(t("Si querés, contanos por qué (opcional):"), "");
      if (motivo !== null) responderPresupuesto(p, "rechazado", motivo);
    }));
  }
  if (p.respuesta_cliente !== "aprobado") {
    botones.append(boton(t("Aprobar"), "btn btn--accent ag-btn", () => {
      if (confirm(t("¿Aprobás el presupuesto #{0} por {1}?", p.id_presu, pesos(p.total_presu)))) responderPresupuesto(p, "aprobado");
    }));
  }
  cont.append(botones);
  return cont;
}

function pintarPresupuestos(editable) {
  const cont = $("mg-d-presupuestos");
  cont.innerHTML = "";
  detalle.presupuestos.forEach((p) => {
    const vence = p.dias_presu ? new Date(new Date(p.fecha_presu + "T00:00:00").getTime() + p.dias_presu * 86400000) : null;
    cont.append(el("article", { className: "mg-presu" + (p.respuesta_cliente ? " mg-presu--" + p.respuesta_cliente : "") },
      el("div", { className: "mg-presu__cab" },
        el("strong", { textContent: t("Presupuesto #{0}", p.id_presu) }),
        el("span", { textContent: fmtFecha(p.fecha_presu) + (vence ? " · " + t("válido hasta {0}", vence.toLocaleDateString(LOCALE, { day: "numeric", month: "short" })) : "") })),
      el("p", { textContent: p.descripcion_presu || "" }),
      el("dl", {},
        el("dt", { textContent: t("Repuestos") }), el("dd", { textContent: pesos(p.repuestos_presu) }),
        el("dt", { textContent: t("Mano de obra") }), el("dd", { textContent: pesos(p.mobra_presu) }),
        el("dt", { className: "mg-presu__total", textContent: t("Total") }), el("dd", { className: "mg-presu__total", textContent: pesos(p.total_presu) }),
        Number(p.senia_presu) ? el("dt", { textContent: t("Seña") }) : null, Number(p.senia_presu) ? el("dd", { textContent: pesos(p.senia_presu) }) : null),
      respuestaPresupuesto(p, editable),
      el("p", { className: "fv-ayuda", textContent: t("¿Dudas? Escribinos por WhatsApp o llamanos.") })));
  });
  $("mg-d-sec-presupuestos").hidden = !detalle.presupuestos.length;
}

function pintarHistorial(editable) {
  const lista = $("mg-d-historial");
  lista.innerHTML = "";
  $("mg-d-trabajo-abrir").hidden = !editable;
  $("mg-d-trabajo").hidden = true;
  // trabajos del taller y los que cargó él de otros talleres, del más nuevo al más viejo
  const items = [
    ...detalle.historial.map((h) => ({ fecha: h.fecha || "", nodo: el("li", { className: "cl-linea__item" + (h.anterior ? " cl-linea__item--anterior" : "") },
      el("span", { className: "cl-linea__fecha", textContent: fmtFecha(h.fecha) }),
      el("div", { className: "cl-linea__cuerpo" },
        el("strong", { textContent: h.trabajo || t("Trabajo en el taller") }),
        h.observaciones ? el("p", { textContent: h.observaciones }) : null,
        el("small", { textContent: ["Symon Sport", h.km ? kms(h.km) : "", h.fecha_entrega ? t("entregado {0}", fmtFecha(h.fecha_entrega)) : h.estado === "listo" ? t("listo para retirar") : t("en el taller"),
          h.anterior ? t("con el dueño anterior") : ""].filter(Boolean).join(" · ") })),
      el("div", { className: "cl-linea__monto" }, Number(h.total) ? el("span", { textContent: pesos(h.total) }) : null)) })),
    ...detalle.otros.map((o) => ({ fecha: o.fecha, nodo: el("li", { className: "cl-linea__item cl-linea__item--otro" },
      el("span", { className: "cl-linea__fecha", textContent: fmtFecha(o.fecha) }),
      el("div", { className: "cl-linea__cuerpo" },
        el("strong", { textContent: o.descripcion }),
        o.detalle ? el("p", { textContent: o.detalle }) : null,
        el("small", { textContent: [o.realizado_por === "cliente" ? t("Lo hiciste vos") : o.taller_nombre || t("Otro taller"), o.km ? kms(o.km) : ""].filter(Boolean).join(" · ") })),
      el("div", { className: "cl-linea__monto" },
        Number(o.costo) ? el("span", { textContent: pesos(o.costo) }) : null,
        editable ? boton(t("Quitar"), "au-link", () => quitarTrabajo(o)) : null)) })),
  ].sort((a, b) => b.fecha.localeCompare(a.fecha));
  lista.append(...items.map((i) => i.nodo));
  if (!detalle.auto.confirmado) {
    lista.append(el("li", { className: "ps-sin", textContent: t("Los trabajos de Symon Sport aparecen cuando el taller confirma que el auto es tuyo. Se hace en tu próxima visita.") }));
  } else if (!items.length) {
    lista.append(el("li", { className: "ps-sin", textContent: t("Todavía no hay trabajos registrados.") }));
  }
}

async function quitarTrabajo(o) {
  if (!confirm(t("¿Quitar “{0}” del historial?", o.descripcion))) return;
  try {
    await api(null, formData({ accion: "trabajo_quitar", id: detalle.auto.id, id_historial: o.id_historial }));
    avisar(t("Trabajo quitado del historial."));
    abrirAuto(detalle.auto.id, { mantenerScroll: true });
  } catch (e) {
    avisar(e.message, true);
  }
}

const formTrabajo = $("mg-d-trabajo");
$("mg-d-trabajo-abrir").addEventListener("click", () => {
  formTrabajo.reset();
  formTrabajo.fecha.value = hoyISO();
  formTrabajo.fecha.max = hoyISO();
  $("mg-d-trabajo-taller").hidden = false;
  $("mg-d-trabajo-error").hidden = true;
  formTrabajo.hidden = false;
  formTrabajo.descripcion.focus();
});
formTrabajo.realizado_por.addEventListener("change", (e) => ($("mg-d-trabajo-taller").hidden = e.target.value === "cliente"));
$("mg-d-trabajo-cancelar").addEventListener("click", () => (formTrabajo.hidden = true));
formTrabajo.addEventListener("submit", async (e) => {
  e.preventDefault();
  const error = $("mg-d-trabajo-error");
  error.hidden = true;
  try {
    await api(null, formData({ ...Object.fromEntries(new FormData(formTrabajo)), accion: "trabajo_agregar", id: detalle.auto.id }));
    avisar(t("Trabajo agregado al historial."));
    abrirAuto(detalle.auto.id, { mantenerScroll: true });
  } catch (err) {
    error.textContent = err.message;
    error.hidden = false;
  }
});

function pintarZonaBaja() {
  const cont = $("mg-d-baja");
  cont.innerHTML = "";
  const { auto } = detalle;
  if (auto.vendido) return;

  const vender = el("details", { className: "mg-vender" },
    el("summary", { textContent: t("Lo vendí") }));
  const fecha = el("input", { type: "date", className: "ps-input", value: hoyISO(), max: hoyISO() });
  vender.append(
    el("p", { textContent: t("El auto pasa a “Autos que tuve”, con una cruz. Su historial y sus fotos quedan, pero ya no vas a poder cambiar sus datos.") }),
    el("label", { className: "fv-campo" }, el("span", { textContent: t("Fecha de venta") }), fecha),
    boton(t("Marcar como vendido"), "btn btn--accent ag-btn", async () => {
      if (!confirm(t("¿Marcar {0} como vendido? Después no se puede deshacer desde acá.", auto.patente))) return;
      try {
        await api(null, formData({ accion: "auto_vendido", id: auto.id, fecha: fecha.value }));
        avisar(t("{0} quedó como vendido.", auto.patente));
        mostrarDetalle(false);
        cargar();
      } catch (e) {
        avisar(e.message, true);
      }
    }));
  cont.append(vender);

  if (!auto.confirmado) {
    cont.append(boton(t("Lo cargué por error: quitarlo"), "cl-dar-baja", async () => {
      if (!confirm(t("¿Quitar {0} de tu garage?", auto.patente))) return;
      try {
        await api(null, formData({ accion: "auto_quitar", id: auto.id }));
        avisar(t("{0} ya no está en tu garage.", auto.patente));
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
  $("mg-hola").textContent = t("Hola, {0}", p.nombre || t("bienvenido"));
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
  [[t("Celular"), p.celular], [t("DNI"), p.dni], [t("Ingresás con"), [t("mail y contraseña"), ...p.ingresos].filter((x, i) => i || p.tiene_clave).join(", ")]]
    .filter(([, v]) => v)
    .forEach(([etiqueta, v]) => dl.append(el("dt", { textContent: etiqueta }), el("dd", { textContent: v })));
  $("mg-clave-actual").hidden = !p.tiene_clave;
  $("mg-clave-abrir").textContent = p.tiene_clave ? t("Cambiar contraseña") : t("Crear una contraseña");
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
    avisar(t("Tus datos se guardaron."));
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
    avisar(t("Contraseña actualizada."));
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
    avisar(t("Foto actualizada."));
    location.reload(); // también cambia la foto del menú
  } catch (err) {
    avisar(err.message, true);
  }
  e.target.value = "";
});
$("mg-avatar-quitar").addEventListener("click", async () => {
  if (!confirm(t("¿Quitar tu foto?"))) return;
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
