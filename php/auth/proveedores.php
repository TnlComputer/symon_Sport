<?php
// Qué botones de login social mostrar (solo los configurados)
require_once "lib.php";

responderJSON([
  'google' => proveedorActivo('google'),
  'microsoft' => proveedorActivo('microsoft'),
  'facebook' => proveedorActivo('facebook'),
]);
