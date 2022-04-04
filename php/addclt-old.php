<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
   session_start(); 

   header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

$metodo= $_SERVER['REQUEST_METHOD'];
$resultado = array();

//  if ($metodo==='POST') {
   $cuit = $_POST['cuit_txt']; 
   $empers = $_POST['empers_txt'];
   $dire = $_POST['dire_txt'];
   $loc = $_POST['loc_txt'];
   $pcia = $_POST['pcia_txt'];
   $cp = $_POST['cp_txt'];
   $contacto = $_POST['contacto_txt'];
   $tel = $_POST['tel_txt'];
   $cel = $_POST['cel_txt'];
   $mail = $_POST['mail_txt'];

    $consClie=$conexionApi->query("SELECT * FROM clientes WHERE cuit='".$cuit."' ");
    $nroreg = mysqli_num_rows($consClie);
    
    if ($nroreg === 0){
      $fila = mysqli_fetch_array($consClie);
      $id_cliente=$fila['id_cliente'];
      
      $datos="INSERT INTO clientes (empresa_nyp, direccion, localidad, provincia, telefono,  cuit, celular, cpost, mail, contacto) VALUES ('$empers', '$dire', '$loc', '$pcia', '$tel', '$cuit', '$cel','$cp', '$mail' , '$contacto')";
      $conexionApi->query($datos); 
    }
  // }

  // if ($metodo==='PUT') {
  //    $cliente = $_POST['id_cliente_txt']; 
  //     $cuit = $_POST['cuit_txt']; 
  //     $empers = $_POST['empers_txt'];
  //     $dire = $_POST['dire_txt'];
  //     $loc = $_POST['loc_txt'];
  //     $pcia = $_POST['pcia_txt'];
  //     $cp = $_POST['cp_txt'];
  //     $contacto = $_POST['contacto_txt'];
  //     $tel = $_POST['tel_txt'];
  //     $cel = $_POST['cel_txt'];
  //     $mail = $_POST['mail_txt'];

  //     $datos="UPDATE clientes SET empresa_nyp='".$empresa_nyp."', direccion='".$dire."', localidad='".$loc."', provincia='".$pcia."', telefono='".$tel."', cuit='".$cuit."', celular='".$cel."', cpost='".$cp."' , mail='".$mail."' , contacto='".$contacto."' WHERE  id_cliente='".$cliente."' ";
	    
  //     $conexionApi->query($datos);
  //  }

    if(!empty($datos)){
        echo json_encode( '200');
      }else{
        echo json_encode('400');
      }   
?>