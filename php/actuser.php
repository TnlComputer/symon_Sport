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

    // if ($metodo==='POST') {
      
    //   $cuit = $_POST['cuit_txt']; 
    //   $empre = $_POST['empers_txt'];
    //   $dire = $_POST['dire_txt'];
    //   $loc = $_POST['loc_txt'];
    //   $pcia = $_POST['pcia_txt'];
    //   $cp = $_POST['cp_txt'];
    //   $contacto = $_POST['contacto_txt'];
    //   $tel = $_POST['tel_txt'];
    //   $cel = $_POST['cel_txt'];
    //   $mail = $_POST['mail_txt'];

    //   $consClie=$conexionApi->query("SELECT * FROM clientes WHERE cuit='".$cuit."' ");
    //   $nroreg = mysqli_num_rows($consClie);
      
    //   if ($nroreg === 0){
    //     $fila = mysqli_fetch_array($consClie);
    //     $id_cliente=$fila['id_cliente'];
        
    //     $datos="INSERT INTO clientes (empresa_nyp, direccion, localidad, provincia, telefono,  cuit, celular, cpost, mail, contacto) VALUES ('$empre', '$dire', '$loc', '$pcia', '$tel', '$cuit', '$cel','$cp',  '$mail', '$contacto')";
        
    //     $conexionApi->query($datos); 
    //   }
          
    //   // var_dump($datos);
    //   if(!empty($datos)){
    //     echo json_encode( '200');
    //   }else{
    //     echo json_encode('400');
    //   }   

    // }
  
  // if ($metodo==='PUT') {
      $idUser = $_POST['id_user_txt']; 
      $clave = $_POST['clave_txt']; 
      $idCliente = $_POST['id_cliente_txt'];
      $usuario = $_POST['usuario_txt'];
      $nombre = $_POST['nombre_txt'];
      $apellido = $_POST['ape_txt'];
      $dni = $_POST['dni_txt'];
      $nivel = 10;



     $datosU="UPDATE usuarios SET clave='".$clave."', id_cliente='".$idCliente."', usuario='".$usuario."', nombre='".$nombre."',  apellido='".$apellido."', dni='".$dni."', nivel='".$nivel."' WHERE  id_user='".$idUser."'";

    // $datosU="UPDATE clientes SET empresa_nyp='".$empre."', direccion='".$dire."' WHERE  id_cliente='".$cliente."'";
	   
    $conexionApi->query($datosU);

    // var_dump( $metodo);
    // var_dump( $cliente);
    // var_dump($datosU);

       
    if(!empty($datosU)){
        echo json_encode( $idCliente);
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