<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
   session_start(); 

$reparacion = $_POST["id_txt"];
$fecIni = $_POST["fecha_txt"];
$presu = $_POST['presuAR_slc'];
$obs = $_POST['obs_txt'];
$km = $_POST['km_txt'];

     $datosU="UPDATE clientes SET empresa_nyp='".$empre."', direccion='".$dire."', localidad='".$loc."', provincia='".$pcia."',  telefono='".$tel."', cuit='".$cuit."', celular='".$cel."', cpost='".$cp."', mail='".$mail."', contacto='".$contacto."' WHERE  id_cliente='".$cliente."'";

    $conexionApi->query($datosU);

    // var_dump($datosU);

       
    if(!empty($datosU)){
        echo json_encode( '200');
      }else{
        echo json_encode('400');
      }   

   

  //  var_dump( $cliente.'<br>');
  //  var_dump( $cuit.'<br>');
  //  var_dump( $empers.'<br>');
  //  var_dump( $dire.'<br>');
  //  var_dump( $loc.'<br>');
  //  var_dump( $pcia.'<br>');
  //  var_dump($cp.'<br>');
  //  var_dump( $contacto.'<br>');
  //  var_dump( $tel.'<br>');
  //  var_dump( $cel.'<br>');
  //  var_dump( $mail.'<br>');
  //  var_dump( $metodo);
  //  var_dump($datos);
  // $cliente = $_POST['id_cliente_txt']; 
  // $cuit = $_POST['cuit_txt']; 
  // $empers = $_POST['empers_txt'];
  // $dire = $_POST['dire_txt'];
  // $loc = $_POST['loc_txt'];
  // $pcia = $_POST['pcia_txt'];
  // $cp = $_POST['cp_txt'];
  // $contacto = $_POST['contacto_txt'];
  // $tel = $_POST['tel_txt'];
  // $cel = $_POST['cel_txt'];
  // $mail = $_POST['mail_txt'];
 
    // id_cliente
    // empresa_nyp
    // direccion
    // localidad
    // provincia
    // telefono
    // cuit
    // celular
    // cpost
    // mail
    // contacto
?>