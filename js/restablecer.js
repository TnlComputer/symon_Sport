/* Elegir contraseña nueva (link del mail de recuperación) */
const rsForm = document.getElementById("rs-form");
const rsAviso = document.getElementById("au-aviso");
const rsToken = new URLSearchParams(location.search).get("t") || "";

const rsAvisar = (texto, error) => {
  rsAviso.textContent = texto;
  rsAviso.classList.toggle("au-aviso--error", error);
  rsAviso.hidden = false;
};

if (!/^[a-f0-9]{64}$/.test(rsToken)) {
  rsAvisar(t("El enlace no es válido. Pedí uno nuevo desde «¿Olvidaste tu contraseña?»."), true);
  rsForm.hidden = true;
}

rsForm.addEventListener("submit", async (e) => {
  e.preventDefault();
  if (rsForm.clave.value !== rsForm.clave2.value) {
    rsAvisar(t("Las contraseñas no coinciden."), true);
    return;
  }
  if (!rsForm.checkValidity()) {
    rsForm.reportValidity();
    return;
  }
  const fd = new FormData();
  fd.append("t", rsToken);
  fd.append("clave", rsForm.clave.value);
  const btn = rsForm.querySelector("button[type=submit]");
  btn.disabled = true;
  try {
    const res = await fetch("php/auth/restablecer.php", { method: "POST", body: fd });
    const datos = await res.json().catch(() => ({}));
    if (!res.ok) throw new Error(datos.error || t("Ocurrió un error."));
    rsAvisar(t("¡Listo! Tu contraseña se actualizó."), false);
    setTimeout(() => (document.location.href = datos.destino), 1200);
  } catch (err) {
    rsAvisar(err.message, true);
    btn.disabled = false;
  }
});
