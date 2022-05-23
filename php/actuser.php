<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
   session_start(); 

      $idUser = $_POST['id_user_txt']; 
      $clave = $_POST['clave_txt']; 
      $idCliente = $_POST['id_cliente_txt'];
      $usuario = $_POST['usuario_txt'];
      $nombre = $_POST['nombre_txt'];
      $apellido = $_POST['ape_txt'];
      $dni = $_POST['dni_txt'];
      $nivel = 10;

      if ($clave === "" OR $clave === NULL ){
        $str = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz1234567890";
        //   $password = "";
        //Reconstruimos la contraseña segun la longitud que se quiera
        for($i=0;$i<10;$i++) {
          //obtenemos un caracter aleatorio escogido de la cadena de caracteres
          $clave .= substr($str,rand(0,62),1);
          $reenvio=1;
        }
      }

     $datosU="UPDATE usuarios SET clave='".$clave."', id_cliente='".$idCliente."', usuario='".$usuario."', nombre='".$nombre."',  apellido='".$apellido."', dni='".$dni."', nivel='".$nivel."' WHERE  id_user='".$idUser."'";

    // $datosU="UPDATE clientes SET empresa_nyp='".$empre."', direccion='".$dire."' WHERE  id_cliente='".$cliente."'";
	   
    $conexionApi->query($datosU);

    // var_dump( $metodo);
    // var_dump( $cliente);
    // var_dump($datosU);

    if ($reenvio===1){
      // enviar mail
        $id = $idUser;

        $consMUC=$conexionApi->query("SELECT * FROM usuarios WHERE id_user='".$id."' ");
        $fila = mysqli_fetch_array($consMUC);
        $id_cliente=$fila['id_cliente'];
        $usuario=$fila['usuario'];
        $clave=$fila['clave'];
        $nombre=$fila['apellido'];  
        $nombre=$fila['apellido'];
        $dni=$fila['dni'];

        $consClie=$conexionApi->query("SELECT empresa_nyp, mail FROM clientes WHERE id_cliente='".$id_cliente."' ");
        $filaClie = mysqli_fetch_array($consClie);
        $nombre_cliente=$filaClie['empresa_nyp'];
        $email_cliente=$filaClie['mail'];

        $to = $email_cliente;
        $subject_mail="Usuario acceso web Symon Sport";
        $message= "
      <!DOCTYPE html>
      <html lang='es'>

      <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Mensaje</title>
      </head>

      <body>
        <div style='width:100%;'>
          <div style=' overflow:hidden; max-width:100%; background-color: #48494a; margin:0 auto'>
            <div style='overflow:hidden; width:80%; border-radius:3px;    margin:30px auto; text-align:center'>
              <table
                style='table-layout:auto; overflow:hidden; background-color: white; width: 300px; text-align:center;  border-radius:3px; padding: 20px;'
                ;'>
                <tr>
                  <td colspan='2' style='margin:0 auto'>
                    <img src='https://symonsport.com.ar/assets/img/logo_ssp150_trans.png'>
                  </td>
                </tr>
                <tr>
                  <td style='text-align:left;margin:12px 30px;line-height:18px'>Cliente</td>
                  <td style='text-align:left;margin:12px 30px;line-height:18px'>$nombre_cliente</td>
                </tr>
                <tr>
                  <td style='text-align:left;margin:12px 30px;line-height:18px'>
                    Usuario
                  </td>
                  <td style='text-align:left;margin:12px 30px;line-height:18px'>$usuario
                  </td>
                </tr>
                <tr>
                  <td style='text-align:left;margin:12px 20px;line-height:18px'>
                    Contraseña
                  </td>
                  <td style='text-align:left;margin:12px 30px;line-height:18px'>
                    $clave
                  </td>
                </tr>
                <tr>
                  <td></td>
                  <td></td>
                </tr>
                <tr>
                  <td colspan='2' style='text-align:left;margin:20px 30px;line-height:18px'>Sitio web
                    <a href='https://symonsport.com.ar' target='_blank'>https://symonsport.com.ar</a>
                  </td>
                </tr>
                <tr>
                  <td colspan='2'
                    style='margin:12px 30px;line-height:18px;overflow:hidden;font-family:Lato,sans-serif;color:#010101;font-size:11px;margin-top:20px;text-align:center;padding: 40px 0 0 0;'>
                    Copyright © Tnlcomputer, Todos los derechos reservados</td>
                </tr>
              </table>
            </div>
          </div>
      </body>

      </html>
      ";

        $headers = "MIME-Version: 1.0" . "\r\n";
        $headers .= "Content-Type:text/html; charset=UTF-8" . "\r\n";
        $headers .= "From: Envio Automatico No Responder <no-replay@symonsport.com.ar>" . "\r\n";
        // $headers .= "Cc: tnlcomputer@gmail.com" . "\r\n";
        $send_mail = mail($to, $subject_mail, $message, $headers);

    }
       
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