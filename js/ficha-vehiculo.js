/* Ficha técnica y galería de fotos de un vehículo: compartido por Mi garage (cliente) y Clientes (admin).
   El catálogo de campos viene del servidor: [{ campo, grupo, etiqueta, tipo, max, opciones, editable }] */
const FichaVehiculo = (() => {
  const el = (tag, props = {}, ...hijos) => {
    const e = Object.assign(document.createElement(tag), props);
    hijos.forEach((h) => h !== null && h !== undefined && h !== false && e.append(h));
    return e;
  };

  const fmtFecha = (iso) => {
    if (!iso) return "";
    const [y, m, d] = iso.slice(0, 10).split("-").map(Number);
    return new Date(y, m - 1, d).toLocaleDateString("es-AR", { day: "numeric", month: "short", year: "numeric" });
  };

  // valor para mostrar
  function texto(c, v) {
    if (v === null || v === undefined || v === "") return "";
    if (c.tipo === "opcion") return c.opciones?.[v] ?? v;
    if (c.tipo === "si_no") return Number(v) ? "Sí" : "No";
    if (c.tipo === "fecha") return fmtFecha(v);
    if (c.tipo === "decimal") return Number(v).toLocaleString("es-AR");
    return String(v);
  }

  const porGrupo = (campos) =>
    campos.reduce((g, c) => ((g[c.grupo] ||= []).push(c), g), {});

  // Resumen de solo lectura (solo lo que tiene datos). Devuelve null si no hay nada cargado.
  function resumen(campos, ficha, { soloNoEditables = false } = {}) {
    const cont = el("div", { className: "fv-resumen" });
    Object.entries(porGrupo(campos)).forEach(([grupo, lista]) => {
      const filas = lista.filter((c) => (!soloNoEditables || !c.editable) && texto(c, ficha[c.campo]));
      if (!filas.length) return;
      const dl = el("dl");
      filas.forEach((c) => dl.append(el("dt", { textContent: c.etiqueta }), el("dd", { textContent: texto(c, ficha[c.campo]) })));
      cont.append(el("section", { className: "fv-grupo" }, el("h4", { textContent: grupo }), dl));
    });
    return cont.children.length ? cont : null;
  }

  function control(c, valor) {
    const v = valor ?? "";
    if (c.tipo === "opcion" || c.tipo === "si_no") {
      const opciones = c.tipo === "si_no" ? { 1: "Sí", 0: "No" } : c.opciones;
      const s = el("select", { className: "ps-input", name: c.campo }, el("option", { value: "", textContent: "—" }));
      Object.entries(opciones).forEach(([val, txt]) => s.append(el("option", { value: val, textContent: txt })));
      s.value = String(v);
      return s;
    }
    if (c.tipo === "texto" && Number(c.max) >= 500) {
      return el("textarea", { className: "ps-input", name: c.campo, rows: 3, maxLength: Number(c.max), value: v });
    }
    const props = { className: "ps-input", name: c.campo, value: v };
    if (c.tipo === "fecha") props.type = "date";
    else if (c.tipo === "entero" || c.tipo === "decimal") {
      props.inputMode = c.tipo === "entero" ? "numeric" : "decimal";
      props.autocomplete = "off";
    } else {
      props.maxLength = Number(c.max) || 255;
      if (["vin", "numero_motor", "neumatico_medida_del", "neumatico_medida_tras"].includes(c.campo)) props.className += " fv-mayus";
    }
    return el("input", props);
  }

  // Formulario con los campos editables, agrupados
  function formulario(campos, ficha) {
    const cont = el("div", { className: "fv-form" });
    Object.entries(porGrupo(campos.filter((c) => c.editable))).forEach(([grupo, lista]) => {
      const grid = el("div", { className: "fv-campos" });
      lista.forEach((c) => {
        const ancho = c.tipo === "texto" && Number(c.max) >= 500 ? " fv-campo--ancho" : "";
        grid.append(el("label", { className: "fv-campo" + ancho }, el("span", { textContent: c.etiqueta }), control(c, ficha[c.campo])));
      });
      cont.append(el("fieldset", { className: "fv-grupo" }, el("legend", { textContent: grupo }), grid));
    });
    return cont;
  }

  // Valores del formulario (solo los campos del catálogo que son editables)
  function leer(raiz, campos) {
    const datos = {};
    campos.filter((c) => c.editable).forEach((c) => {
      const input = raiz.querySelector(`[name="${c.campo}"]`);
      if (input) datos[c.campo] = input.value.trim();
    });
    return datos;
  }

  /* ---------------- Galería ---------------- */
  let visor;
  function ampliar(fotos, indice) {
    if (!visor) {
      visor = el("dialog", { className: "fv-visor" });
      visor.addEventListener("click", (e) => e.target === visor && visor.close());
      document.body.append(visor);
    }
    let i = indice;
    const img = el("img", { alt: "" });
    const pintar = () => {
      img.src = fotos[i].url;
      img.alt = `Foto ${i + 1} de ${fotos.length}`;
      cuenta.textContent = `${i + 1} / ${fotos.length}`;
    };
    const mover = (d) => {
      i = (i + d + fotos.length) % fotos.length;
      pintar();
    };
    const cuenta = el("span", { className: "fv-visor__cuenta" });
    const boton = (txt, clase, fn, etiqueta) => {
      const b = el("button", { type: "button", className: clase, textContent: txt });
      b.setAttribute("aria-label", etiqueta);
      b.addEventListener("click", fn);
      return b;
    };
    visor.replaceChildren(img, cuenta, boton("×", "fv-visor__cerrar", () => visor.close(), "Cerrar"));
    if (fotos.length > 1) {
      visor.append(boton("‹", "fv-visor__ant", () => mover(-1), "Anterior"), boton("›", "fv-visor__sig", () => mover(1), "Siguiente"));
    }
    visor.onkeydown = (e) => {
      if (e.key === "ArrowLeft") mover(-1);
      if (e.key === "ArrowRight") mover(1);
    };
    pintar();
    visor.showModal();
  }

  /**
   * opciones: fotos, max, puedeSubir, propias (cuántas cuentan para el máximo), portada (id),
   *           subir(archivo) -> Promise, quitar(foto) -> Promise, elegirPortada(foto) -> Promise (opcional),
   *           puedeQuitar(foto), alTerminar() (recarga), avisar(msg, error)
   */
  function galeria(o) {
    const cont = el("div", { className: "fv-galeria" });
    const grilla = el("div", { className: "fv-fotos" });
    o.fotos.forEach((f, i) => {
      const fig = el("figure", { className: "fv-foto" + (Number(f.id_foto) === Number(o.portada) ? " is-portada" : "") });
      const ver = el("button", { type: "button", className: "fv-foto__ver" }, el("img", { src: f.url, alt: `Foto ${i + 1}`, loading: "lazy" }));
      ver.addEventListener("click", () => ampliar(o.fotos, i));
      fig.append(ver);
      const acciones = el("figcaption", { className: "fv-foto__acciones" });
      if (f.subida_por === "taller") acciones.append(el("span", { className: "fv-foto__chip", textContent: "Taller" }));
      if (o.elegirPortada && i > 0) {
        const b = el("button", { type: "button", className: "fv-foto__accion", textContent: "★" });
        b.title = "Usar como portada";
        b.setAttribute("aria-label", "Usar como portada");
        b.addEventListener("click", () => o.elegirPortada(f).then(o.alTerminar).catch((e) => o.avisar(e.message, true)));
        acciones.append(b);
      }
      if (o.puedeQuitar(f)) {
        const b = el("button", { type: "button", className: "fv-foto__accion", textContent: "×" });
        b.title = "Quitar foto";
        b.setAttribute("aria-label", "Quitar foto");
        b.addEventListener("click", async () => {
          if (!confirm("¿Quitar esta foto?")) return;
          try {
            await o.quitar(f);
            o.alTerminar();
          } catch (e) {
            o.avisar(e.message, true);
          }
        });
        acciones.append(b);
      }
      if (acciones.children.length) fig.append(acciones);
      grilla.append(fig);
    });

    const lugar = o.max - o.propias;
    if (o.puedeSubir && lugar > 0) {
      const input = el("input", { type: "file", accept: "image/jpeg,image/png,image/webp", multiple: true, className: "sr-only" });
      const sumar = el("label", { className: "fv-fotos__sumar" }, input, el("strong", { textContent: "+" }),
        el("span", { textContent: o.fotos.length ? "Sumar fotos" : "Subir fotos" }));
      input.addEventListener("change", async () => {
        const archivos = [...input.files].slice(0, lugar);
        if (input.files.length > lugar) o.avisar(`Se suben ${lugar} (el máximo es ${o.max} fotos).`, true);
        const estado = sumar.querySelector("span");
        let errores = 0;
        for (let i = 0; i < archivos.length; i++) {
          estado.textContent = `Subiendo ${i + 1} de ${archivos.length}…`;
          try {
            await o.subir(archivos[i]);
          } catch (e) {
            errores++;
            o.avisar(`${archivos[i].name}: ${e.message}`, true);
          }
        }
        if (!errores && archivos.length) o.avisar(archivos.length === 1 ? "Foto subida." : `${archivos.length} fotos subidas.`);
        o.alTerminar();
      });
      grilla.append(sumar);
    }
    if (!o.fotos.length && !(o.puedeSubir && lugar > 0)) grilla.append(el("p", { className: "ps-sin", textContent: "Sin fotos." }));
    cont.append(grilla);
    if (o.puedeSubir) cont.append(el("p", { className: "fv-ayuda", textContent: `Hasta ${o.max} fotos (JPG, PNG o WEBP, máx. 8 MB cada una).` }));
    return cont;
  }

  return { resumen, formulario, leer, galeria, texto, fmtFecha };
})();
