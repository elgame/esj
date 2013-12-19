<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class facturartebarato_api {

  /**
   * Usuario en facturarteBarato.
   *
   * @var string
   */
  protected $user = 'admin'; //sanjorge

  /**
   * Password.
   *
   * @var string
   */
  protected $password = 'A12345678'; //S4nj0rg3

  /**
   * URL base de la api.
   *
   * @var string
   */
  protected $apiURL = 'development.facturartebarato.com/api/v1/'; //facturartebarato.com/api/v1/

  /**
   * Almacena la informacion de la peticion por curl.
   *
   * @var array
   */
  protected $statusRequest;

  /**
   * Ruta del XML.
   *
   * @var string
   */
  protected $pathXML;

  /**
   * Contenido del XML.
   *
   * @var string
   */
  protected $xml;

  /**
   * XML en Base64.
   *
   * @var string
   */
  protected $xml64;

  /**
   * API result.
   *
   * @var Object(stdClass)
   */
  protected $resultAPI;

  /**
   * UUID.
   *
   * @var string
   */
  protected $uuid;

  /**
   * Constructor.
   *
   * @return void
   */
  public function __construct()
  {

  }

  /**
   * Realiza el timbrado.
   *
   * @param strin $xml
   * @return mixed boolean|object
   */
  public function timbrar()
  {
    libxml_use_internal_errors(true);

    // Obtiene el contenido del XML.
    $this->xml = $this->getContentXML();

    $is_xml = simplexml_load_string($this->xml);


    // Verifica si es un XML valido.
    if ($is_xml)
    {
      $this->xml64 = base64_encode($this->xml);

      $postData = array('xml' => $this->xml64);

      $apiURL = "http://{$this->user}:{$this->password}@{$this->apiURL}timbre";

      // Checa si ahi conexion a internet.
      $this->resultAPI = $this->post($apiURL, $postData);

      $checkResult = $this->checkResultTimbrado();

      // echo "<pre>";
      //   var_dump($this->resultAPI, $postData, $this->xml);
      // echo "</pre>";exit;

      return $checkResult;
    }
    else
    {
      return json_decode(json_encode(array(
        'status' => false,
        'msg'    => 'El XML proporcionado no es un XML.'
      )));
    }
  }

  /**
   * Verifica el resultado del timbrado, si no hubo errores entonces
   * sobrescribe el XML con el que retorna el webservice.
   *
   * @return object(stdClass)
   */
  private function checkResultTimbrado()
  {
    // Si es null significa que hubo un error de conexion de internet.
    if (is_null($this->resultAPI))
    {
      $incidencias =  array(
        'status'  => false,
      );

      switch ($this->statusRequest['http_code']) {
        case 0:
          $incidencias['codigo'] = 'ERR_INTERNET_DISCONNECTED';
          $incidencias['mensaje'] = 'Error: Internet Desconectado. Verifique su conexión.';
          break;
        case 500:
          $incidencias['codigo'] = '500';
          $incidencias['mensaje'] = 'Error en el servidor.';
          break;
        default:
          break;
      }
    }
    else
    {
      // Obtiene el codigo de incidencia.
      $codigo = $this->resultAPI->msg->incidencias[0]->codigo;

      // Si hay un error en el timbrado.
      if ($this->resultAPI->msg->error)
      {
        $incidencias =  array(
          'status'  => false,
          'codigo'  => $codigo,
          'mensaje' => $this->resultAPI->msg->incidencias[0]->mensaje
        );
      }
      else
      {
        // Entra Si no hubo un error en el timbrado. Este caso tambien aplica
        // para cuando el timbrado queda "pendiente".

        // Obtiene el XML ya timbrado.
        $this->xml  = $this->resultAPI->data->xml;

        // Obtiene el UUID del timbrado.
        $this->uuid = $this->resultAPI->data->uuid;

        // Sobreescriobe el XML.
        $this->rewriteXML();

        $incidencias =  array(
          'status'  => true,
          'codigo'  => $codigo,
          'mensaje' => $this->resultAPI->msg->incidencias[0]->mensaje
        );
      }
    }

    return json_decode(json_encode($incidencias));
  }

  /**
   * Verifica el status de un timbrado pendiente.
   *
   * @return void
   */
  public function verificarPendiente()
  {
    $apiURL = "http://{$this->user}:{$this->password}@{$this->apiURL}timbre/{$this->uuid}";

    // Obtiene la respues del webservice.
    $this->resultAPI = $this->get($apiURL);

    return $this->resultAPI;
  }

  /**
   * Cancela una factura.
   *
   * @param  array $params
   * @return void
   */
  public function cancelar(Array $params)
  {
    $apiURL = "http://{$this->user}:{$this->password}@{$this->apiURL}cancel";
    
    $resultAPI = $this->post($apiURL, $params);

    return $resultAPI;
  }

  /*
   |------------------------------------------------------------------------
   | HELPERS
   |------------------------------------------------------------------------
   */

  /**
   * Obtiene el contenido del XML.
   *
   * @return string
   */
  private function getContentXML()
  {
    return file_get_contents($this->pathXML);
  }

  /**
   * Sobreescribe el contenido del XML.
   *
   * @return mixed int|boolean
   */
  private function rewriteXML()
  {
    return file_put_contents($this->pathXML, $this->xml, LOCK_EX);
  }

  /**
   * Detecta si ahi conexion a internet.
   *
   * @return boolean
   */
  private function isConnected()
  {
    $connected = @fsockopen("www.google.com", 80); //website and port
    if ($connected)
    {
      $isConn = true;
      fclose($connected);
    }
    else
    {
      $isConn = false;
    }
    return $isConn;
  }

  /*
   |------------------------------------------------------------------------
   | PETICIONES.
   |------------------------------------------------------------------------
   */

  /**
   * Realiza una peticion GET.
   *
   * @param  $apiURL
   * @return object(stdClass)
   */
  public function get($apiURL)
  {
    return json_decode(file_get_contents($apiURL));
  }

  /**
   * Realiza una peticion POST.
   *
   * @param  $apiURL
   * @param  $data
   * @return object(stdClass)
   */
  public function post($apiURL, $data)
  {
    return $this->curlExec($apiURL, $data);
  }

  /**
   * Ejecuta CURL para enviar los datos mediante POST.
   *
   * @return mixed boolean|json
   */
  private function curlExec($apiURL, $data=null)
  {
    if ( ! function_exists('curl_init'))
    {
      exit('Se necesita la extension CURL PHP.');
    }
    else
    {
      $curl = curl_init($apiURL);
      curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

      curl_setopt($curl, CURLOPT_HTTPHEADER, array('Expect:'));
      curl_setopt($curl, CURLOPT_POST, true);
      curl_setopt($curl, CURLOPT_POSTFIELDS, $data);

      // Obtiene el resultado de la peticion.
      $response = json_decode(curl_exec($curl));

      // Obtiene la informacion de la peticion.
      $this->statusRequest = curl_getinfo($curl);

      // echo "<pre>";
      //   var_dump($response, $this->statusRequest);
      // echo "</pre>";exit;

      curl_close($curl);

      return $response;
    }
  }

  /*
   |------------------------------------------------------------------------
   | SETTERS
   |------------------------------------------------------------------------
   */

  /**
   * Establece la ruta del XML.
   *
   * @param string $pathXML
   * @return void
   */
  public function setPathXML($pathXML)
  {
    $this->pathXML = $pathXML;
  }

  /**
   * Asigna el uuid.
   *
   * @param string $uuid
   * @return void
   */
  public function setUUID($uuid)
  {
    $this->uuid = $uuid;
  }

  /*
   |------------------------------------------------------------------------
   | GETTERS
   |------------------------------------------------------------------------
   */

  /**
   * Obtiene la ruta del XML.
   *
   * @return string
   */
  public function getPathXML()
  {
    return $this->pathXML;
  }

  /**
   * Obtiene el XML.
   *
   * @return string.
   */
  public function getXML()
  {
    return $this->xml;
  }

  public function getResult()
  {
    return $this->resultAPI;
  }

  /**
   * Obtiene el UUID del timbrado.
   *
   * @return string
   */
  public function getUUID()
  {
    return $this->uuid;
  }
}