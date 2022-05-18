<?php
  require_once "connections/conexion.php";
  error_reporting(E_ALL ^ E_NOTICE);
   session_start(); 

//  $metodo= $_SERVER['REQUEST_METHOD'];
// $id = json_decode(file_get_contents("php://input"), true);

  $id = $_POST['id'];
//   $id = 86;
// echo "<br>ID: ".$id."<br>";

  $consPre=$conexionApi->query("SELECT * FROM presupuestos WHERE id_presu='".$id."' ");
  $filaP = mysqli_fetch_array($consPre);
  $id_cliente=$filaP['id_cliente'];
  // $fecha_presu=$filaP['fecha_presu'];
  $id_vehiculo=$filaP['id_vehiculo'];
  $mobra=$filaP['mobra_presu'];
  $repuestos=$filaP['repuestos_presu'];
  $total=$filaP['total_presu'];
  $dias=$filaP['dias_presu'];
  $descripcion=$filaP['descripcion_presu'];

	$fecPre	=$filaP['fecha_presu'];
	$fechaPr=explode("-",$fecPre,3);
	$fecha_presu =($fechaPr[2]."-".$fechaPr[1]."-".$fechaPr[0]);  		
  $consVeh=$conexionApi->query("SELECT patente, marca, modelo, anio FROM vehiculos WHERE id_vehiculo='".$id_vehiculo."' ");
  $fila = mysqli_fetch_array($consVeh);
  $patente=$fila['patente'];
  $marca=$fila['marca'];
  $modelo=$fila['modelo'];
  $anio=$fila['anio'];

  // echo "clie ".$id_cliente."<br>";
  $consClie=$conexionApi->query("SELECT empresa_nyp, mail FROM clientes WHERE id_cliente='".$id_cliente."' ");
  $filaClie = mysqli_fetch_array($consClie);
  $nombre_cliente=$filaClie['empresa_nyp'];
  $email_cliente=$filaClie['mail'];

  $to = $email_cliente;
  $subject_mail="Presupuestos Vehículo ".$patente;
  $message= "
<!DOCTYPE html>
<html lang='es'>

<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <title>Mensaje</title>
</head>

<body>
  <div style='width:100%'>
    <div style=' overflow:hidden; max-width:100%; background-color: #48494a; margin:0 auto'>
      <div style='overflow:hidden; width:80%; border-radius:3px; margin:30px auto; text-align:center'>
        <table
          style='table-layout:auto; overflow:hidden; background-color: white; width:600px; text-align:center;  border-radius:3px; padding: 20px'
          ;'>
          <tr>
            <td colspan='4' style='margin:0 auto;'>
              <img src='https://symonsport.com.ar/assets/img/logo_ssp150_trans.png'>
            </td>
          </tr>
          <tr>
            <td style='text-align: left;margin:20px 30px;line-height:18px; padding: 20px 0;'>Fecha</td>
            <td style='text-align: left;margin:12px 30px;line-height:18px; padding: 20px 0;'>$fecha_presu</td>
            <td style='text-align: right; margin:12px 30px;line-height:18px; padding: 20px 0;'>Presupuesto</td>
            <td style='text-align: left;margin:12px 30px;line-height:18px'>$id</td>
          </tr>
          <tr>
            <td style='text-align:left;margin:12px 30px;line-height:18px; padding: 20px 0;'>Cliente</td>
            <td style='text-align:left;margin:12px 30px;line-height:18px; padding: 20px 0;'>$nombre_cliente</td>
          </tr>
          <tr>
            <td style='text-align:left;margin:12px 30px;line-height:18px'>
              Patente</td>
            <td style='text-align:left;margin:12px 30px;line-height:18px'>
              Marca </td>
            <td style='text-align:left;margin:12px 30px;line-height:18px'>
              Modelo </td>
            <td style='text-align:left;margin:12px 30px;line-height:18px'>
              Año
            </td>
          </tr>
          <tr>
            <td style='text-align:left;margin:12px 30px;line-height:18px'>
              $patente
            </td>
            <td style='text-align:left;margin:12px 30px;line-height:18px'>
              $marca
            </td>
            <td style='text-align:left;margin:12px 30px;line-height:18px'>
              $modelo
            </td>
            <td style='text-align:left;margin:12px 30px;line-height:18px'>
              $anio
            </td>
          </tr>
          <tr>
            <td style='text-align:left;margin:12px 30px;line-height:18px; padding: 20px 0;'>
              Descripción
            </td>
          </tr>
          <tr>
            <td colspan='4' style='text-align:left; margin:5px 30px; line-height:18px; padding:0 0 20px 0;'>
              $descripcion aca pongo todo lo del presupuestos con lo que se me ocurre para que salga todo en varias
              lineas
              y
              por lo que estoy poniento todo este texto y no hice un lorem como un boludo
            </td>
          </tr>
        </table>
        <table
          style='table-layout:auto; overflow:hidden; background-color: white; width:600px; text-align:center;  border-radius:3px; padding: 20px'
          ;'>
          <tr>
            <td style='text-align:left;margin:12px 30px;line-height:18px'>
              Respuestos
            </td>
            <td style='text-align:left;margin:12px 20px;line-height:18px'>
              Mano de Obra
            </td>
            <td style='text-align:left;margin:12px 30px;line-height:18px'>
              Total
            </td>
          </tr>
          <tr>
            <td style='text-align:left;margin:12px 30px;line-height:18px'>
              $ $repuestos
            </td>
            <td style='text-align:left;margin:12px 30px;line-height:18px'>
              $ $mobra
            </td>
            <td style='text-align:left;margin:12px 30px;line-height:18px'>
              $ $total
            </td>
          </tr>
          <tr>
            <td colspan='3' style='text-align:left;margin:12px 30px;line-height:18px; padding: 20px ;'>Valido por $dias
              días</td>
          </tr>
          <tr>
            <td colspan='4'>Sitio web
              <a href='https://symonsport.com.ar' target='_blank'>https://symonsport.com.ar</a>
            </td>
          </tr>
          <tr>
            <td colspan='3'
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

  if(send_mail){
    $res = [
      "err" => false,
      "message" => "Tus datos han sido enviados"
    ];
  }else{
    $res = [
      "err" => true,
      "message" => "Error an enviar tus datos. Intenta nuevamente"
    ];
  }

  header("Access-Control-Allow-Origin: https://symonsport.com.ar");
  header(`Content-type: application/json`);
  echo json_encode($res);
  exit;
  
  