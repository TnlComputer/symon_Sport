<?php
// Copiar este archivo a php/connections/oauth.php y completar las credenciales.
// (php/connections/ está en .gitignore: las claves no se suben a GitHub)
//
// URL de retorno a registrar en cada proveedor:
//   https://symonsport.com.ar/php/auth/callback.php
//   (en local: http://localhost:8000/php/auth/callback.php)
//
// Un proveedor sin client_id queda desactivado y su botón no aparece.

return [
  'google' => [
    // https://console.cloud.google.com/apis/credentials -> Crear credenciales -> ID de cliente de OAuth (Aplicación web)
    'client_id' => '',
    'client_secret' => '',
  ],
  'microsoft' => [
    // https://entra.microsoft.com -> Registros de aplicaciones -> Nuevo registro
    // Tipos de cuenta: "Cuentas de cualquier directorio y cuentas personales de Microsoft"
    'client_id' => '',
    'client_secret' => '',
  ],
  'facebook' => [
    // https://developers.facebook.com/apps -> Crear app -> Inicio de sesión con Facebook
    'client_id' => '',      // ID de la app
    'client_secret' => '',  // Clave secreta de la app
  ],
];
