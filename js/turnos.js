/* Turnos online: servicio -> calendario (libre/ocupado) -> datos */
const tn = {
  servicio: null,
  desde: null, // primer día de la semana mostrada (YYYY-MM-DD)
  hoy: null,
  ultimo: null,
  diaActivo: 0,
  elegido: null, // { fecha, hora }
};

const $tn = (id) => document.getElementById(id);
const pasoServicio = $tn("tn-paso-servicio");
const pasoHorario = $tn("tn-paso-horario");
const formTurno = $tn("tn-form");
const okTurno = $tn("tn-ok");

/* ---------- utilidades de fecha (sin zonas horarias) ---------- */
const aFecha = (iso) => {
  const [y, m, d] = iso.split("-").map(Number);
  return new Date(y, m - 1, d);
};
const aISO = (f) =>
  `${f.getFullYear()}-${String(f.getMonth() + 1).padStart(2, "0")}-${String(f.getDate()).padStart(2, "0")}`;
const sumarDias = (iso, n) => {
  const f = aFecha(iso);
  f.setDate(f.getDate() + n);
  return aISO(f);
};
const fmtDiaCorto = (iso) =>
  aFecha(iso).toLocaleDateString("es-AR", { weekday: "short", day: "numeric" }).replace(".", "");
const fmtDiaLargo = (iso) =>
  aFecha(iso).toLocaleDateString("es-AR", { weekday: "long", day: "numeric", month: "long" });
const fmtDuracion = (min) => {
  const h = Math.floor(min / 60);
  const m = min % 60;
  return [h ? `${h} h` : "", m ? `${m} min` : ""].filter(Boolean).join(" ");
};

const pedirJSON = async (url, opciones) => {
  const res = await fetch(url, opciones);
  const datos = await res.json().catch(() => ({}));
  if (!res.ok) throw Object.assign(new Error(datos.error || "Error de conexión"), { status: res.status, datos });
  return datos;
};

const marcarPaso = (n) => {
  document.querySelectorAll(".tn-pasos__item").forEach((li) => {
    const p = Number(li.dataset.paso);
    li.classList.toggle("is-activo", p === n);
    li.classList.toggle("is-hecho", p < n);
  });
};

/* ---------- PASO 1: servicios ---------- */
async function cargarServicios() {
  const cont = $tn("tn-servicios");
  try {
    const { servicios } = await pedirJSON("./php/turnos_servicios.php");
    if (!servicios.length) {
      cont.innerHTML = `<p class="tn-cargando">Por el momento no hay turnos online. Escribinos por WhatsApp.</p>`;
      return;
    }
    cont.innerHTML = "";
    servicios.forEach((s) => {
      const btn = document.createElement("button");
      btn.type = "button";
      btn.className = "tn-servicio";
      btn.setAttribute("role", "radio");
      btn.setAttribute("aria-checked", "false");
      btn.innerHTML = `<strong></strong><span class="tn-servicio__dur"></span><small></small>`;
      btn.querySelector("strong").textContent = s.nombre;
      btn.querySelector(".tn-servicio__dur").textContent = fmtDuracion(Number(s.duracion_min));
      btn.querySelector("small").textContent = s.descripcion || "";
      btn.addEventListener("click", () => elegirServicio(s, btn));
      cont.appendChild(btn);
    });
  } catch (e) {
    cont.innerHTML = `<p class="tn-cargando">No pudimos cargar los turnos. Probá de nuevo o escribinos por WhatsApp.</p>`;
  }
}

function elegirServicio(servicio, btn) {
  document.querySelectorAll(".tn-servicio").forEach((b) => b.setAttribute("aria-checked", "false"));
  btn.setAttribute("aria-checked", "true");
  tn.servicio = servicio;
  tn.elegido = null;
  tn.desde = null;
  tn.diaActivo = 0;
  formTurno.hidden = true;
  pasoHorario.hidden = false;
  $tn("tn-servicio-elegido").textContent = `${servicio.nombre} · ${fmtDuracion(Number(servicio.duracion_min))}`;
  marcarPaso(2);
  cargarSemana();
  pasoHorario.scrollIntoView({ behavior: "smooth", block: "start" });
}

/* ---------- PASO 2: calendario ---------- */
async function cargarSemana() {
  const semana = $tn("tn-semana");
  semana.innerHTML = `<p class="tn-cargando">Buscando horarios…</p>`;
  $tn("tn-dias").innerHTML = "";

  const params = new URLSearchParams({ servicio: tn.servicio.id_servicio, dias: 7 });
  if (tn.desde) params.set("desde", tn.desde);

  try {
    const datos = await pedirJSON(`./php/turnos_disponibilidad.php?${params}`);
    tn.hoy = datos.hoy;
    tn.ultimo = datos.ultimo;
    tn.desde = datos.dias[0].fecha;
    pintarSemana(datos.dias);
  } catch (e) {
    semana.innerHTML = `<p class="tn-cargando">No pudimos cargar los horarios. Probá de nuevo.</p>`;
  }
}

function pintarSemana(dias) {
  const semana = $tn("tn-semana");
  const tabs = $tn("tn-dias");
  semana.innerHTML = "";
  tabs.innerHTML = "";

  $tn("tn-rango").textContent = `${fmtDiaCorto(dias[0].fecha)} – ${fmtDiaCorto(dias[dias.length - 1].fecha)}`;
  $tn("tn-prev").disabled = tn.desde <= tn.hoy;
  $tn("tn-next").disabled = sumarDias(tn.desde, 7) > tn.ultimo;

  // en móvil arranco en el primer día con lugar
  const primeroLibre = dias.findIndex((d) => d.slots.some((s) => s.estado === "libre"));
  if (tn.diaActivo === 0 && primeroLibre > 0) tn.diaActivo = primeroLibre;

  dias.forEach((dia, i) => {
    const libres = dia.slots.filter((s) => s.estado === "libre").length;

    const tab = document.createElement("button");
    tab.type = "button";
    tab.className = "tn-dia-tab";
    tab.setAttribute("role", "tab");
    tab.innerHTML = `<span></span><small></small>`;
    tab.querySelector("span").textContent = fmtDiaCorto(dia.fecha);
    tab.querySelector("small").textContent = dia.abierto ? (libres ? `${libres} libres` : "Completo") : "Cerrado";
    if (!libres) tab.classList.add("is-sin-lugar");
    tab.addEventListener("click", () => activarDia(i));
    tabs.appendChild(tab);

    const col = document.createElement("div");
    col.className = "tn-dia";
    col.setAttribute("role", "tabpanel");
    col.innerHTML = `<h3 class="tn-dia__titulo"></h3><div class="tn-slots"></div>`;
    col.querySelector("h3").textContent = fmtDiaCorto(dia.fecha);
    const cont = col.querySelector(".tn-slots");

    if (!dia.abierto) {
      cont.innerHTML = `<p class="tn-dia__vacio"></p>`;
      cont.firstChild.textContent = dia.motivo || "Cerrado";
    } else {
      dia.slots.forEach((s) => {
        const b = document.createElement("button");
        b.type = "button";
        b.className = `tn-slot tn-slot--${s.estado}`;
        b.textContent = s.hora;
        if (s.estado === "libre") {
          b.setAttribute("aria-label", `${fmtDiaLargo(dia.fecha)} a las ${s.hora}, libre`);
          b.addEventListener("click", () => elegirHorario(dia.fecha, s, b));
          if (tn.elegido && tn.elegido.fecha === dia.fecha && tn.elegido.hora === s.hora) {
            b.classList.add("is-elegido");
          }
        } else {
          b.disabled = true;
          const txt = { ocupado: "ocupado", no_alcanza: "no alcanza el tiempo para este servicio", pasado: "ya no disponible" };
          b.title = txt[s.estado] || "";
          b.setAttribute("aria-label", `${s.hora}, ${txt[s.estado]}`);
        }
        cont.appendChild(b);
      });
    }
    semana.appendChild(col);
  });

  activarDia(Math.min(tn.diaActivo, dias.length - 1));
}

function activarDia(i) {
  tn.diaActivo = i;
  document.querySelectorAll(".tn-dia-tab").forEach((t, j) => {
    t.classList.toggle("is-activo", j === i);
    t.setAttribute("aria-selected", String(j === i));
  });
  document.querySelectorAll(".tn-dia").forEach((d, j) => d.classList.toggle("is-activo", j === i));
}

// texto de retiro estimado solo cuando el auto queda en el taller otro día
const textoRetiro = (fecha, fechaFin, horaFin) =>
  fechaFin && fechaFin !== fecha ? `Retiro estimado: ${fmtDiaLargo(fechaFin)} ${horaFin} hs` : "";

function elegirHorario(fecha, slot, btn) {
  const hora = slot.hora;
  document.querySelectorAll(".tn-slot.is-elegido").forEach((b) => b.classList.remove("is-elegido"));
  btn.classList.add("is-elegido");
  tn.elegido = { fecha, hora };

  const resumen = $tn("tn-resumen");
  resumen.innerHTML = `<strong></strong><span></span><small></small>`;
  resumen.querySelector("strong").textContent = tn.servicio.nombre;
  resumen.querySelector("span").textContent = `Ingreso: ${fmtDiaLargo(fecha)} · ${hora} hs`;
  resumen.querySelector("small").textContent = textoRetiro(fecha, slot.fecha_fin, slot.hora_fin);
  $tn("tn-error").hidden = true;
  formTurno.hidden = false;
  marcarPaso(3);
  formTurno.scrollIntoView({ behavior: "smooth", block: "start" });
  formTurno.querySelector("input[name=nombre]").focus({ preventScroll: true });
}

$tn("tn-prev").addEventListener("click", () => {
  tn.desde = sumarDias(tn.desde, -7) < tn.hoy ? tn.hoy : sumarDias(tn.desde, -7);
  tn.diaActivo = 0;
  cargarSemana();
});
$tn("tn-next").addEventListener("click", () => {
  tn.desde = sumarDias(tn.desde, 7);
  tn.diaActivo = 0;
  cargarSemana();
});
$tn("tn-volver").addEventListener("click", () => {
  marcarPaso(2);
  pasoHorario.scrollIntoView({ behavior: "smooth", block: "start" });
});

/* ---------- PASO 3: datos y reserva ---------- */
formTurno.patente.addEventListener("input", (e) => {
  e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9]/g, "");
});

formTurno.addEventListener("submit", async (e) => {
  e.preventDefault();
  const error = $tn("tn-error");
  error.hidden = true;

  if (!formTurno.checkValidity()) {
    formTurno.reportValidity();
    return;
  }

  const datos = new FormData(formTurno);
  datos.append("servicio", tn.servicio.id_servicio);
  datos.append("fecha", tn.elegido.fecha);
  datos.append("hora", tn.elegido.hora);

  const btn = $tn("tn-confirmar");
  btn.disabled = true;
  btn.textContent = "Reservando…";

  try {
    const { turno } = await pedirJSON("./php/turnos_reservar.php", { method: "POST", body: datos });
    const retiro = textoRetiro(turno.fecha, turno.fecha_fin, turno.hora_fin);
    const detalle = `${turno.servicio} — ${fmtDiaLargo(turno.fecha)} a las ${turno.hora_inicio} hs` + (retiro ? `. ${retiro}` : "");
    $tn("tn-ok-detalle").textContent = detalle;
    const msg = `Hola Symon Sport, reservé un turno online: ${detalle}. Patente ${datos.get("patente")}.`;
    $tn("tn-ok-wa").href = `https://wa.me/541167993863?text=${encodeURIComponent(msg)}`;

    pasoServicio.hidden = true;
    pasoHorario.hidden = true;
    formTurno.hidden = true;
    document.querySelector(".tn-pasos").hidden = true;
    okTurno.hidden = false;
    okTurno.focus();
    okTurno.scrollIntoView({ behavior: "smooth", block: "center" });
    cargarMisTurnos();
  } catch (err) {
    error.textContent = err.message;
    error.hidden = false;
    if (err.status === 409 && !(err.datos && err.datos.ya_tiene_turno)) {
      // alguien tomó el horario: refresco el calendario
      tn.elegido = null;
      cargarSemana();
    }
  } finally {
    btn.disabled = false;
    btn.textContent = "Confirmar turno";
  }
});

/* ---------- Usuario con sesión: sus turnos y vehículos ---------- */
const misTurnos = document.getElementById("tn-mios");

function crear(tag, clase, texto) {
  const el = document.createElement(tag);
  if (clase) el.className = clase;
  if (texto !== undefined) el.textContent = texto;
  return el;
}

const mayuscula = (s) => s.charAt(0).toUpperCase() + s.slice(1);
const textoTurno = (t) => `${mayuscula(fmtDiaLargo(t.fecha))} · ${t.hora_inicio} hs · ${t.servicio}`;

async function cargarMisTurnos() {
  let datos;
  try {
    datos = await pedirJSON("./php/mis_turnos.php");
  } catch {
    return;
  }
  if (!datos.sesion) return;

  // datos del usuario en el formulario
  const nombre = datos.usuario.nombre || "";
  $tn("tn-hola").textContent = nombre ? `Hola, ${nombre.split(" ")[0]}` : "Hola";
  if (!formTurno.nombre.value) formTurno.nombre.value = nombre;
  if (!formTurno.email.value) formTurno.email.value = datos.usuario.email || "";
  if (!formTurno.telefono.value) formTurno.telefono.value = datos.usuario.telefono || "";

  // turnos vigentes
  const lista = $tn("tn-mis-turnos");
  lista.innerHTML = "";
  if (datos.turnos.length) {
    lista.append(crear("h3", "tn-mios__sub", datos.turnos.length > 1 ? "Tus próximos turnos" : "Tu próximo turno"));
    datos.turnos.forEach((t) => {
      const fila = crear("div", "tn-mio");
      const info = crear("div", "tn-mio__info");
      info.append(crear("strong", "", textoTurno(t)), crear("span", "tn-placa", t.patente));
      const retiro = textoRetiro(t.fecha, t.fecha_fin, t.hora_fin);
      if (retiro) info.append(crear("small", "", retiro));
      info.append(crear("small", `tn-mio__estado tn-mio__estado--${t.estado}`, t.estado === "confirmado" ? "Confirmado" : "Pendiente de confirmación"));
      const cancelar = crear("button", "btn btn--ghost ag-btn", "Cancelar turno");
      cancelar.type = "button";
      cancelar.addEventListener("click", () => cancelarTurno(t));
      fila.append(info, cancelar);
      lista.append(fila);
    });
  } else {
    lista.append(crear("p", "tn-mios__ayuda", "No tenés turnos vigentes."));
  }

  // vehículos
  const cont = $tn("tn-vehiculos");
  cont.innerHTML = "";
  $tn("tn-vehiculos-ayuda").hidden = !datos.vehiculos.length;
  datos.vehiculos.forEach((v) => {
    const card = crear("div", "tn-auto" + (v.turno ? " tn-auto--con-turno" : ""));
    const desc = [v.marca, v.modelo, v.anio].filter(Boolean).join(" ") || v.descripcion || "Vehículo";
    card.append(crear("span", "tn-placa", v.patente), crear("strong", "", desc));
    if (v.turno) {
      card.append(crear("small", "", `Ya tiene turno: ${fmtDiaLargo(v.turno.fecha)} ${v.turno.hora_inicio} hs`));
    } else {
      const pedir = crear("button", "btn btn--accent ag-btn", "Pedir turno");
      pedir.type = "button";
      pedir.addEventListener("click", () => {
        formTurno.patente.value = v.patente;
        formTurno.vehiculo.value = desc === "Vehículo" ? "" : desc;
        cont.querySelectorAll(".tn-auto").forEach((c) => c.classList.remove("is-elegido"));
        card.classList.add("is-elegido");
        pasoServicio.scrollIntoView({ behavior: "smooth", block: "start" });
      });
      card.append(pedir);
    }
    cont.append(card);
  });
  if (!datos.vehiculos.length) {
    cont.append(crear("p", "tn-mios__ayuda", "Todavía no tenés vehículos. Se agregan solos cuando pedís tu primer turno."));
  }
  misTurnos.hidden = false;
}

async function cancelarTurno(t) {
  if (!confirm(`¿Cancelar el turno del ${textoTurno(t)} (${t.patente})?`)) return;
  const fd = new FormData();
  fd.append("accion", "cancelar");
  fd.append("id", t.id_turno);
  try {
    await pedirJSON("./php/mis_turnos.php", { method: "POST", body: fd });
    cargarMisTurnos();
    if (tn.servicio) cargarSemana(); // se liberó lugar en el calendario
  } catch (e) {
    alert(e.message);
  }
}

cargarServicios();
cargarMisTurnos();
