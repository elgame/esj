<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

// include(APPPATH."libraries/PHPMailer/class.phpmailer.php");
require_once(APPPATH."libraries/PHPMailer/class.phpmailer.php");

/**
 * My CodeIgniter Library
 *
 * @author  Indigo Dev Team
 * @since   Version 1.0
 * @filesource
 */

class my_email {

  /**
   * Contiene los datos del email. Array asociativo.
   *
   *    correoEmisor
   *    contrasena
   *    correoEmisorEm
   *    nombreEmisor
   *    correoDestino
   *    nombreDestino
   *    asunto
   *    body
   *    altBody
   *    adjuntos => array()
   *
   * @var array
   */
  protected $data;

  /**
   * Indica si los archivos adjuntos se meteran en un archivo zip.
   *
   * @var boolean
   */
  protected $zip = false;

  /**
   * Constructor
   * @param  array $params=array()
   */
  public function __construct($params=array())
  {
    // $this->CI =& get_instance();

    // log_message('debug', "Email Class Initialized");
  }

  public function send()
  {
    $mail = new PHPMailer;

    $mail->IsSMTP();
    $mail->SMTPDebug  = 0;
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = $this->data['correoEmisor'];
    $mail->Password   = $this->data['contrasena'];
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    $mail->From     = $this->data['correoEmisorEm'];
    $mail->FromName = $this->data['nombreEmisor'];

    foreach ($this->data['correoDestino'] as $correoDestino)
      $mail->AddAddress(trim($correoDestino), $this->data['nombreDestino']);

    $mail->AddReplyTo($this->data['correoEmisorEm'], $this->data['nombreEmisor']);
    $mail->AddCC($this->data['cc']);

    $mail->IsHTML(true);

    $mail->Subject = $this->data['asunto'];
    $mail->MsgHTML($this->data['body']);
    $mail->AltBody = $this->data['altBody'];

    // Si se enviaran archivos en el mail los adjunta.
    if (isset($this->data['adjuntos']))
    {
      if (count($this->data['adjuntos'] > 0))
      {
        // Si quiere que los archivos se manden en un zip.
        if ($this->zip)
        {
          $pathZip = $this->makeZip();

          $mail->AddAttachment($pathZip, 'Documentos.zip');
        }
        else
        {
          foreach ($this->data['adjuntos'] as $fileName => $pathFile)
          {
            $mail->AddAttachment($pathFile, $fileName);
          }
        }
      }
    }

    $result = array(
      'msg' => 'Mensaje enviado correctamente.'
    );

    if( ! $mail->Send())
    {
      $result['msg']   = 'El email no se pudo enviar.';
      $result['error'] = $mail->ErrorInfo;
    }

    if ($this->zip)
      unlink($pathZip);

    return $result;
  }

  /**
   * Asigna los parametros para el correo.
   *
   * @param Array $emailData
   * @return obj this
   */
  public function setData(Array $emailData)
  {
    $this->data = $emailData;

    return $this;
  }

  /**
   * Crea un zip e inserta todo los archivos en el.
   *
   * @return void
   */
  private function makeZip()
  {
    $zip = new ZipArchive;
    if ($zip->open(APPPATH.'media/temp.zip', ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === true)
    {
      foreach ($this->data['adjuntos'] as $key => $pathFile)
        $zip->addFile($pathFile, $key);

      $zip->close();

      return APPPATH.'media/temp.zip';
    }
    else
    {
      exit('Error al intentar crear el ZIP.');
    }
  }

  /**
   * Especifica que los adjuntos se zipearan.
   *
   * @param  boolean $zip
   * @return Obj this
   */
  public function zip($zip = true)
  {
    $this->zip = $zip;

    return $this;
  }

}
/* End of file my_email.php */
/* Location: ./application/libraries/my_email.php */