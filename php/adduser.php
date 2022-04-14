<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
   session_start(); 
  
      $clave = $_POST['clave_txt']; 
      $idCliente = $_POST['id_cliente_txt'];
      $usuario = $_POST['usuario_txt'];
      $nombre = $_POST['nombre_txt'];
      $apellido = $_POST['ape_txt'];
      $dni = $_POST['dni_txt'];
      $nivel = 10;

      $consUser=$conexionApi->query("SELECT * FROM usuarios WHERE dni='".$dni."' AND id_cliente='".$idCliente."' ");
      $nroreg = mysqli_num_rows($consUser);
      
      if ($nroreg === 0){

        $datos="INSERT INTO usuarios (usuario, clave, nombre, apellido, dni,  nivel, id_cliente) VALUES ('$usuario', '$clave', '$nombre', '$apellido', '$dni', '$nivel', '$idCliente')";
        
        $conexionApi->query($datos); 
      }
      // var_dump($idCliente);
      // var_dump($nroreg);
      // var_dump($datos);
    if(!empty($datos)){
        echo json_encode($idCliente);
      }else{
        echo json_encode('400');
      }   
?>