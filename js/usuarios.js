/* Usuarios (administrador): todas las cuentas, habilitar / deshabilitar, rol, cliente y sucursales */
const API = "./php/usuarios_api.php";
const $ = (id) => document.getElementById(id);
const form = $("us-form");
const usAviso = $("us-aviso");
let actual = null; // usuario abierto (null = nuevo)
let sucursales = []; // del cliente elegido
let yo = 0;
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
const fmtFecha = (iso) => {
  if (!iso) return "";
  const [y, m, d] = iso.slice(0, 10).split("-").map(Number);
  return new Date(y, m - 1, d).toLocaleDateString("es-AR", { day: "numeric", month: "short", year: "numeric" });
};
const nombreDe = (u) => [u.nombre, u.apellido].filter(Boolean).join(" ") || u.email || u.usuario;
const iniciales = (u) => nombreDe(u).split(/\s+/).slice(0, 2).map((x) => x[0] || "").join("").toUpperCase();

function avisar(msg, error = false) {
  usAviso.textContent = msg;
  usAviso.classList.toggle("cf-aviso--error", error);
  usAviso.hidden = false;
  clearTimeout(avisoTimer);
  avisoTimer = setTimeout(() => (usAviso.hidden = true), 6000);
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

function avatar(u, clase = "us-avatar") {
  const a = el("span", { className: clase });
  if (u.foto) a.append(el("img", { src: u.foto, alt: "", referrerPolicy: "no-referrer" }));
  else a.textContent = iniciales(u);
  return a;
}

function chips(u) {
  const c = [];
  c.push(el("span", { className: "ps-chip" + (u.rol === "admin" ? " us-chip--admin" : ""), textContent: u.rol === "admin" ? "Administrador" : "Cliente" }));
  if (u.baja) c.push(el("span", { className: "ps-chip us-chip--baja", textContent: "Deshabilitado" }));
  if (u.bloqueado) c.push(el("span", { className: "ps-chip us-chip--baja", textContent: "Bloqueado por intentos" }));
  if (!u.baja && !Number(u.tiene_clave) && !Number(u.con_google) && !Number(u.con_microsoft) && !Number(u.con_facebook)) {
    c.push(el("span", { className: "ps-chip", textContent: "Sin contraseña todavía" }));
  }
  if (Number(u.id_user) === yo) c.push(el("span", { className: "ps-chip ps-chip--ok", textContent: "Vos" }));
  return c;
}

/* =================== LISTADO =================== */
async function cargar() {
  try {
    const r = await api({ accion: "listar", q: $("us-q").value.trim(), rol: $("us-rol").value, estado: $("us-estado").value,
      sin_cliente: $("us-sin-cliente").checked ? 1 : "" });
    yo = Number(r.yo);
    $("us-cuenta").textContent = r.usuarios.length === 1 ? "1 usuario" : `${r.usuarios.length} usuarios`;
    $("us-lista").replaceChildren(...r.usuarios.map((u) => {
      const fila = el("button", { type: "button", className: "us-fila" + (u.baja ? " us-fila--baja" : "") },
        avatar(u),
        el("span", { className: "us-fila__quien" }, el("strong", { textContent: nombreDe(u) }), el("small", { textContent: u.email || u.usuario })),
        el("span", { className: "us-fila__cliente", textContent: u.rol === "admin" ? "Taller" : u.empresa_nyp || "Sin cliente vinculado" }),
        el("span", { className: "us-fila__ingreso", textContent: u.ultimo_login ? `Entró el ${fmtFecha(u.ultimo_login)}` : "Nunca entró" }),
        el("span", { className: "ps-card__estado" }, ...chips(u)));
      fila.addEventListener("click", () => abrir(u.id_user));
      return fila;
    }));
    if (!r.usuarios.length) $("us-lista").append(el("p", { className: "ps-sin", textContent: "No hay usuarios con esos filtros." }));
  } catch (e) {
    avisar(e.message, true);
  }
}
$("us-q").addEventListener("input", demorar(cargar, 300));
["us-rol", "us-estado", "us-sin-cliente"].forEach((id) => $(id).addEventListener("change", cargar));

/* =================== EDICIÓN =================== */
function mostrarEditor(mostrar) {
  $("us-editor").hidden = !mostrar;
  $("us-listado").hidden = mostrar;
  if (!mostrar) history.replaceState(null, "", "usuarios.php");
  window.scrollTo({ top: 0, behavior: "smooth" });
}

function nuevo() {
  actual = null;
  form.reset();
  form.id_user.value = "";
  form.rol.value = "usuario";
  elegirCliente(null);
  $("us-e-titulo").textContent = "Nuevo usuario";
  $("us-e-avatar").textContent = "+";
  $("us-e-chips").replaceChildren();
  $("us-enviar-label").hidden = false;
  $("us-cuenta-panel").hidden = true;
  $("us-error").hidden = true;
  form.querySelectorAll("input, button").forEach((i) => (i.disabled = false));
  actualizarRol();
  mostrarEditor(true);
  form.nombre.focus();
}

async function abrir(id) {
  let r;
  try {
    r = await api({ accion: "detalle", id });
  } catch (e) {
    avisar(e.message, true);
    return;
  }
  actual = r.usuario;
  yo = Number(r.yo);
  const u = actual;
  history.replaceState(null, "", `usuarios.php?id=${u.id_user}`);
  form.reset();
  ["id_user", "nombre", "apellido", "email", "celular", "dni"].forEach((k) => (form[k].value = u[k] ?? ""));
  form.rol.value = u.rol;
  sucursales = r.sucursales;
  elegirCliente(u.id_cliente ? { id_cliente: u.id_cliente, empresa_nyp: u.empresa_nyp } : null, u);
  $("us-e-titulo").textContent = nombreDe(u);
  $("us-e-avatar").replaceChildren(...avatar(u, "x").childNodes);
  $("us-e-chips").replaceChildren(...chips(u));
  $("us-enviar-label").hidden = true;
  $("us-error").hidden = true;
  // una cuenta deshabilitada no se edita: primero se habilita
  form.querySelectorAll("input, button").forEach((i) => (i.disabled = !!u.baja));
  actualizarRol();
  pintarCuenta();
  mostrarEditor(true);
}

function actualizarRol() {
  const esAdmin = form.rol.value === "admin";
  form.querySelectorAll(".us-rol .ps-provisto__op").forEach((l) => l.classList.toggle("is-activo", l.querySelector("input").checked));
  $("us-bloque-cliente").hidden = esAdmin;
  $("us-rol-ayuda").textContent = esAdmin
    ? "Administrador: entra al panel del taller (agenda, presupuestos, clientes, reparaciones, repuestos y usuarios)."
    : "Cliente: entra a Mi garage y ve los autos del cliente vinculado (de las sucursales que le habilites).";
}
form.querySelectorAll('input[name="rol"]').forEach((r) => r.addEventListener("change", actualizarRol));

/* ---------- cliente y sucursales ---------- */
async function elegirCliente(c, u = null) {
  form.id_cliente.value = c ? c.id_cliente : "";
  $("us-cliente-elegido").hidden = !c;
  $("us-cliente-buscar").hidden = !!c;
  $("us-cliente-nombre").textContent = c ? c.empresa_nyp : "";
  $("us-cliente-q").value = "";
  $("us-cliente-resultados").innerHTML = "";
  if (!c) {
    sucursales = [];
  } else if (!u) {
    sucursales = (await api({ accion: "sucursales", id_cliente: c.id_cliente })).sucursales;
  }
  pintarPermisos(u);
}

function pintarPermisos(u = null) {
  $("us-permisos").hidden = !sucursales.length;
  const ids = String(u?.sucursales || "").split(",").map(Number);
  form.todas_sucursales.checked = !!u && !!Number(u.todas_sucursales);
  $("us-sucursales").replaceChildren(...sucursales.map((s) =>
    el("label", { className: "cf-check rp-check cl-check" },
      el("input", { type: "checkbox", value: s.id_sucursal, checked: ids.includes(Number(s.id_sucursal)) }), ` ${s.nombre}`)));
  const sincronizar = () => $("us-sucursales").querySelectorAll("input").forEach((c) => (c.disabled = form.todas_sucursales.checked || !!actual?.baja));
  form.todas_sucursales.onchange = sincronizar;
  sincronizar();
}

$("us-cliente-q").addEventListener("input", demorar(async () => {
  const q = $("us-cliente-q").value.trim();
  const lista = $("us-cliente-resultados");
  if (q.length < 2) {
    lista.innerHTML = "";
    return;
  }
  const { clientes } = await api({ accion: "clientes", q });
  lista.replaceChildren(...clientes.map((c) => {
    const b = el("button", { type: "button", className: "ps-res" },
      el("strong", { textContent: c.empresa_nyp }), el("small", { textContent: [c.cuit, c.mail].filter(Boolean).join(" · ") }));
    b.addEventListener("click", () => elegirCliente(c));
    return el("li", {}, b);
  }));
  if (!clientes.length) lista.append(el("li", { className: "ps-sin", textContent: "No hay clientes con esa búsqueda." }));
}));
$("us-cliente-quitar").addEventListener("click", () => elegirCliente(null));

form.addEventListener("submit", async (e) => {
  e.preventDefault();
  const error = $("us-error");
  error.hidden = true;
  const datos = Object.fromEntries(new FormData(form));
  const elegidas = [...$("us-sucursales").querySelectorAll("input:checked")].map((c) => c.value).join(",");
  try {
    const r = await api(null, formData({ ...datos, accion: "guardar", rol: form.rol.value, sucursales: elegidas,
      todas_sucursales: form.todas_sucursales.checked ? 1 : "", enviar_acceso: !datos.id_user && form.enviar_acceso.checked ? 1 : "" }));
    let msg = datos.id_user ? "Usuario guardado." : "Usuario creado.";
    if (r.enviado) msg += ` Se le envió el link a ${r.enviado}.` + (r.link_prueba ? ` (Prueba local: ${r.link_prueba})` : "");
    avisar(msg);
    abrir(r.id);
    cargar();
  } catch (err) {
    error.textContent = err.message;
    error.hidden = false;
  }
});

/* ---------- panel de la cuenta: estado y acciones ---------- */
function pintarCuenta() {
  const u = actual;
  const ingresos = [Number(u.tiene_clave) ? "mail y contraseña" : "", Number(u.con_google) ? "Google" : "",
    Number(u.con_microsoft) ? "Microsoft" : "", Number(u.con_facebook) ? "Facebook" : ""].filter(Boolean).join(", ");
  const dl = $("us-e-datos");
  dl.replaceChildren();
  [["Creada", fmtFecha(u.creado)], ["Último ingreso", u.ultimo_login ? fmtFecha(u.ultimo_login) : "Nunca"],
    ["Ingresa con", ingresos || "Todavía no eligió contraseña"], ["Deshabilitada", u.baja ? fmtFecha(u.baja) : ""]]
    .filter(([, v]) => v)
    .forEach(([t, v]) => dl.append(el("dt", { textContent: t }), el("dd", { textContent: v })));

  const acc = $("us-e-acciones");
  acc.replaceChildren();
  const accion = async (datos, mensaje) => {
    try {
      const r = await api(null, formData({ ...datos, id: u.id_user }));
      avisar(mensaje + (r.link_prueba ? ` (Prueba local: ${r.link_prueba})` : ""));
      abrir(u.id_user);
      cargar();
    } catch (e) {
      avisar(e.message, true);
    }
  };
  if (u.baja) {
    acc.append(boton("Habilitar la cuenta", "btn btn--accent ag-btn", () => accion({ accion: "reactivar" }, `${nombreDe(u)} está habilitado otra vez.`)));
    return;
  }
  acc.append(boton(Number(u.tiene_clave) ? "Enviar link para cambiar la contraseña" : "Enviar link para elegir la contraseña", "btn btn--ghost ag-btn",
    () => accion({ accion: "acceso" }, `Se envió el link a ${u.email}.`)));
  if (u.bloqueado) acc.append(boton("Desbloquear ingreso", "btn btn--primary ag-btn", () => accion({ accion: "desbloquear" }, "Listo: ya puede volver a intentar ingresar.")));
  if (Number(u.id_user) !== yo) {
    acc.append(boton("Deshabilitar la cuenta", "cl-dar-baja", () => {
      if (confirm(`¿Deshabilitar a ${nombreDe(u)}?\n\nNo va a poder entrar (si tiene la sesión abierta, se le cierra). No se borra nada y se puede volver a habilitar.`)) {
        accion({ accion: "baja" }, `${nombreDe(u)} quedó deshabilitado.`);
      }
    }));
  }
  $("us-cuenta-panel").hidden = false;
}

/* =================== INICIO =================== */
$("us-nuevo").addEventListener("click", nuevo);
$("us-volver").addEventListener("click", () => {
  mostrarEditor(false);
  cargar();
});
cargar();
const pedido = new URLSearchParams(location.search);
if (pedido.get("id")) abrir(pedido.get("id"));
