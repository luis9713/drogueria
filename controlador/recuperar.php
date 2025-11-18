<?php
include_once '../modelo/Usuario.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// Load Composer's autoloader
require '../vendor/autoload.php';
$usuario = new Usuario();
if($_POST['funcion']=='verificar'){
    $email = $_POST['email'];
    $dni = $_POST['dni'];
    $usuario->verificar($email,$dni);
}
if($_POST['funcion']=='recuperar'){
    $email = $_POST['email'];
    $dni = $_POST['dni'];
    $codigo = generar(10);
    $usuario->remplazar($codigo,$email,$dni);
    date_default_timezone_set('America/Bogota');
    $fecha=date('Y-m-d H:i:s');
    $mail = new PHPMailer(true);

try {
    //Server settings
    $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      // Enable verbose debug output
    $mail->isSMTP();                                            // Send using SMTP
    $mail->Host       = 'smtp-mail.outlook.com';                    // Set the SMTP server to send through
    $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
    $mail->Username   = 'su correo @hotmail.com';                     // SMTP username
    $mail->Password   = 'su contrasena';                               // SMTP password
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption; `PHPMailer::ENCRYPTION_SMTPS` encouraged
    $mail->Port       = 587;                                    // TCP port to connect to, use 465 for `PHPMailer::ENCRYPTION_SMTPS` above

    //Recipients
    $mail->setFrom('juan.diego.polo.cosme@hotmail.com', 'Sistema administrativo');
    $mail->addAddress($email);     // Add a recipient
    //$mail->addAddress('ellen@example.com');               // Name is optional
    //$mail->addReplyTo('info@example.com', 'Information');
    //$mail->addCC('cc@example.com');
    //$mail->addBCC('bcc@example.com');

    // Attachments
   // $mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
   // $mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name

    // Content
    $mensaje='
    <!DOCTYPE>
<html>
<head>
  <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Narrative Invitation Email</title>
  <style type="text/css">

  /* Take care of image borders and formatting */

  img {
    max-width: 600px;
    outline: none;
    text-decoration: none;
    -ms-interpolation-mode: bicubic;
  }

  a {
    border: 0;
    outline: none;
  }

  a img {
    border: none;
  }

  /* General styling */

  td, h1, h2, h3  {
    font-family: Helvetica, Arial, sans-serif;
    font-weight: 400;
  }

  td {
    font-size: 13px;
    line-height: 19px;
    text-align: left;
  }

  body {
    -webkit-font-smoothing:antialiased;
    -webkit-text-size-adjust:none;
    width: 100%;
    height: 100%;
    color: #37302d;
    background: #ffffff;
  }

  table {
    border-collapse: collapse !important;
  }


  h1, h2, h3, h4 {
    padding: 0;
    margin: 0;
    color: #444444;
    font-weight: 400;
    line-height: 110%;
  }

  h1 {
    font-size: 35px;
  }

  h2 {
    font-size: 30px;
  }

  h3 {
    font-size: 24px;
  }

  h4 {
    font-size: 18px;
    font-weight: normal;
  }


  </style>

  <style type="text/css" media="screen">
      @media screen {
        @import url(http://fonts.googleapis.com/css?family=Open+Sans:400);

        /* Thanks Outlook 2013! */
        td, h1, h2, h3 {
          font-family: Open Sans, Helvetica Neue, Arial, sans-serif !important;
        }
      }
  </style>

  </style>
</head>
<body class="body" style="padding:0; margin:0; display:block; background:#ffffff; -webkit-text-size-adjust:none" bgcolor="#ffffff">
<table align="center" cellpadding="0" cellspacing="0" width="100%" height="100%">
  <tr>
    <td align="center" valign="top" bgcolor="#ffffff"  width="100%">

    <table cellspacing="0" cellpadding="0" width="100%">
      <tr>
        <td style="background:#1f1f1f" width="100%">

          <center>
            <table cellspacing="0" cellpadding="0" width="600" class="w320">
              <tr>
                <td valign="top" width="270" style="background:#1f1f1f; text-align:left;">
                  <a href="#" style="text-decoration:none;">
                    <img src="https://i.pinimg.com/originals/e6/01/a8/e601a882de5917b8c378d57d596472ec.png" width="50" height="50" alt="Your Logo"/>
                  </a>
                 
                </td>
                <td>
                  <h2 style="color: #f8f8f8; text-align:left;">
                    Farmacia code War
                  </h2>
                </td>
               
              </tr>
            </table>
          </center>

        </td>
      </tr>
      <tr>
        <td style="border-bottom:1px solid #e7e7e7;">

          <center>
            <table cellpadding="0" cellspacing="0" width="600" class="w320">
              <tr>
                <td align="left" class="mobile-padding" style="padding:20px 20px 30px">

                  <br class="mobile-hide" />

                  <h1>Farmacia Code War<br></h1>
                  <h3> Centro de recuperación de constraseñas</h3>

                  <br>
                  Hola, <strong>'.$dni.'.</strong>
                  <br>
                  Recibimos una solicitud de nueva contraseña para su cuenta en el sistema de farmacia.
                  <br>
                  Hemos generado una contraseña nueva automática, la cual usted debe ir al sistema de inmediato a cambiarla.
                  <br>
                  La contrasena es:
                  <br>
                  <br>
                  <h3>'.$codigo.'</h3>

                  <br>
                  Esta contraseña debera cambiarse una vez cumplida su función de contraseña alternativa.
                  <br>
                  <br>
                  <table cellspacing="0" cellpadding="0" width="100%" bgcolor="#ffffff">
                    <tr>
                      <td style="width:130px;background:#D84A38;">
                        <div>
                        <a href="http://localhost/farmacia/index.php"
                        style="background-color:#D84A38;color:#ffffff;display:inline-block;font-family:sans-serif;font-size:13px;font-weight:bold;line-height:33px;text-align:center;text-decoration:none;width:130px;-webkit-text-size-adjust:none;">Ir al sistema!</a>
                          
                        </div>
                      </td>
                      <td width="316" style="background-color:#ffffff; font-size:0; line-height:0;">&nbsp;</td>
                    </tr>
                  </table>
                </td>
                <td class="mobile-hide" style="padding-top:20px;padding-bottom:0;">
                  <table cellspacing="0" cellpadding="0" width="100%">
                    <tr>
                      <td align="right" width="220" style="padding-right:20px; vertical-align:middle;">
                        <table width="94" align="right" cellpadding="0" cellspacing="0">
                          <tr>
                            <td style="border:2px solid #000000">
                              <img src="https://i.pinimg.com/736x/8a/8d/3f/8a8d3f62663f719adc1b4402d1ce9d8f.jpg" style="display:block" width="120" height="90" alt="profile"/>
                            </td>
                          </tr>
                        </table>
                      </td>
                    </tr>
                    <tr>
                     <td style="font-size:12px; padding-top:5px; text-align:center; padding-right:20px">
                       Usuario: <a style="color:red; font-size: 20px;">'.$dni.'</a>
                      </td>
                    </tr>
                  </table>
                </td>
              </tr>
            </table>
          </center>

        </td>
      </tr>
      <tr>
        <td valign="top" style="background-color:#f8f8f8;border-bottom:1px solid #e7e7e7;">

          <center>
            <table border="0" cellpadding="0" cellspacing="0" width="600" class="w320" style="height:100%;">
              <tr>
                <td valign="top" class="mobile-padding" style="padding:20px;">
                  <h2>Centro de ayuda</h2>
                  <br>
                  * Si usted tiene problemas para recuperar su contrasena puede volver a generar la solicitud de cambio de contrasena.
                  <br>
                  * Si el problema persiste pongase en contacto con el administrador del sistema.
                  <br><br>
                  Atentamente. <strong>Centro de recuperacion de constrasenas</strong><br>
                  '.$fecha.'
                  <br>
                </td>
              </tr>
            </table>
          </center>

        </td>
      </tr>
     
      <tr>
        <td style="background-color:#1f1f1f;">
          <center>
            <table border="0" cellpadding="0" cellspacing="0" width="600" class="w320" style="height:100%;color:#ffffff" bgcolor="#1f1f1f" >
              <tr>
                <td align="right" valign="middle" class="mobile-padding" style="font-size:12px;padding:20px; background-color:#1f1f1f; color:#ffffff; text-align:left; ">
                  <h4 style="color:#ffffff;"  href="#">Creado por Code War | 2020</h4>
                 
                </td>
              </tr>
            </table>
          </center>
        </td>
      </tr>
    </table>

    </td>
  </tr>
</table>
</body>
</html>

    ';
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'Restablecer contrasena';
    $mail->Body    = $mensaje;
    //$mail->AltBody = 'This is the body in plain text for non-HTML mail clients';
    $mail->SMTPDebug = false;
    $mail->do_debug=false;
    $mail->send();
    echo 'enviado';
} catch (Exception $e) {
    echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
}
}
function generar($longitud){
    $key='';
    $patron='1234567890abcdefghijklmnopqrstuvwxyz';
    $max=strlen($patron)-1;
    for($i=0;$i<$longitud;$i++){
        $key.=$patron{mt_rand(0,$max)};
    }
    return $key;
}