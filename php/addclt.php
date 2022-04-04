<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
   session_start(); 

  //  header('Access-Control-Allow-Origin: *');
  //  header('Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept');
  //  header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');

  //  $metodo= $_SERVER['REQUEST_METHOD'];
  //  $resultado = array();

  
    //  var_dump( $cliente.'<br>');
    //  var_dump( $cuit.'<br>');
    //  var_dump( $empre.'<br>');
    //  var_dump( $dire.'<br>');
    //  var_dump( $loc.'<br>');
    //  var_dump( $pcia.'<br>');
    //  var_dump( $cp.'<br>');
    //  var_dump( $contacto.'<br>');
    //  var_dump( $tel.'<br>');
    //  var_dump( $cel.'<br>');
    //  var_dump( $mail.'<br>');
    //  var_dump( $metodo);
    
    //  if ($metodo==='POST') {
      
      $cuit = $_POST['cuit_txt']; 
      $empre = $_POST['empers_txt'];
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
        
        $datos="INSERT INTO clientes (empresa_nyp, direccion, localidad, provincia, telefono,  cuit, celular, cpost, mail, contacto) VALUES ('$empre', '$dire', '$loc', '$pcia', '$tel', '$cuit', '$cel','$cp',  '$mail', '$contacto')";
        
        $conexionApi->query($datos); 
      }
    // }
    // if ($metodo==='PUT') {
    //   $cliente = $_PUT['id_cliente_txt']; 
    //   $cuit = $_PUT['cuit_txt']; 
    //   $empre = $_PUT['empers_txt'];
    //   $dire = $_PUT['dire_txt'];
    //   $loc = $_PUT['loc_txt'];
    //   $pcia = $_PUT['pcia_txt'];
    //   $cp = $_PUT['cp_txt'];
    //   $contacto = $_PUT['contacto_txt'];
    //   $tel = $_PUT['tel_txt'];
    //   $cel = $_PUT['cel_txt'];
    //   $mail = $_PUT['mail_txt'];

    //        $datos="UPDATE clientes SET empresa_nyp='".$empre."', direccion='".$dire."', localidad='".$loc."', provincia='".$pcia."',  telefono='".$tel."', cuit='".$cuit."', celular='".$cel."', cpost='".$cp."', mail='".$mail."', contacto='".$contacto."' WHERE  id_cliente='".$cliente."'";

    // $conexionApi->query($datos);
    //  var_dump( $cliente.'<br>');
    //  var_dump( $cuit.'<br>');
    //  var_dump( $empre.'<br>');
    //  var_dump( $dire.'<br>');
    //  var_dump( $loc.'<br>');
    //  var_dump( $pcia.'<br>');
    //  var_dump( $cp.'<br>');
    //  var_dump( $contacto.'<br>');
    //  var_dump( $tel.'<br>');
    //  var_dump( $cel.'<br>');
    //  var_dump( $mail.'<br>');
    //  var_dump( $metodo);

    //    var_dump($datos);
    // }      

    if(!empty($datos)){
        echo json_encode( '200');
      }else{
        echo json_encode('400');
      }   
?>