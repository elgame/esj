<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class documentos_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getDocumentos($orderby='nombre ASC')
	{
		$sql = '';
		$res = $this->db->query("SELECT
					id_documento, nombre, url_form, url_print, status, orden
				FROM rastria_documentos
				WHERE status = true
				ORDER BY {$orderby}
				");

		$response = array(
				'documentos'    => array(),
		);
		if($res->num_rows() > 0){
			$response['documentos'] = $res->result();
		}

		return $response;
	}

  /**
    * Actualiza un documento en la bdd.
    *
    * @param  Array  $data
    * @param  string $idDocumento
    * @return boolean
    */
  public function updateDocumento($data, $idFactura, $idDocumento, $status = 't')
  {
    // Convierte los datos del documento a json.
    if (is_array($data))
      $data = json_encode($data);

    $data = array(
      'data'   => $data,
      'status' => $status
    );

    // Actualiza los datos del documento.
    $this->db->update('facturacion_documentos', $data, array(
      'id_factura'   => $idFactura,
      'id_documento' => $idDocumento,
    ));

    return true;
  }

  public function finalizar_docs($idFactura)
  {
    $this->db->update('facturacion', array('docs_finalizados' => 't'), array('id_factura' => $idFactura));
  }

  /**
   * Obtiene los documentos que se asignaron a la factura cuando se agrego.
   *
   * @return mixed array|boolean
   */
  public function getClienteDocs($idFactura, $id_documento=null)
  {
    $sql = ($id_documento!==null? " AND fd.id_documento = {$id_documento}": '');
    $query = $this->db->query(
      "SELECT fd.id_documento,
              fd.data,
              fd.status,
              rd.nombre,
              rd.url_form,
              rd.url_print,
              rd.status AS status_rastria,
              rd.orden
       FROM rastria_documentos AS rd
       INNER JOIN facturacion_documentos AS fd ON fd.id_documento = rd.id_documento
       WHERE fd.id_factura = {$idFactura} AND rd.status = true {$sql}
       ORDER BY rd.orden ASC"
    );

    if ($query->num_rows() > 0)
      return $query->result();

    return false;
  }

  /**
   * Obtiene la informacion del chofer y camion del ticket|folio de la
   * bascula.
   *
   * @param  string $idArea
   * @param  string $ticket
   * @return mixed array|boolean
   */
  public function getChoferCamionInfo($idArea, $ticket, $idFactura)
  {
    $sql = $this->db
      ->select('id_chofer, id_camion')
      ->from('bascula')
      ->where('folio', $ticket)
      ->where('tipo', 'sa')
      ->where('id_area', $idArea)
      ->get();

    if ($sql->num_rows() > 0)
    {
      $res = $sql->row();

      $data = array();

      if ($res->id_chofer !== null)
      {
        $this->load->model('choferes_model');

        $data['chofer'] = $this->choferes_model->getChoferInfo($res->id_chofer);

        // echo "<pre>";
        //   var_dump($data['chofer']['info']->url_licencia);
        // echo "</pre>";exit;

        $this->load->model('facturacion_model');

        // Obtiene la informacion de la factura.
        $factura = $this->facturacion_model->getInfoFactura($idFactura);

        // Obtiene la ruta donde se guardan los documentos del cliente.
        $path = $this->creaDirectorioDocsCliente($factura['info']->cliente->nombre_fiscal, $factura['info']->serie, $factura['info']->folio, substr($factura['info']->fecha, 0, 10));

        // Si ya existe los documentos en la carpeta de la factura entonces
        // los elimina.
        $files = array_diff(scandir($path), array('..', '.'));
        foreach ($files as $f)
        {
          $ff = explode('.', $f);

          if ($ff[0] === 'CHOFER COPIA LICENCIA' || $ff[0] === 'CHOFER COPIA DEL IFE')
            unlink($path.$ff[0].'.'.$ff[1]);
        }

        // Si el chofer cuenta con la licencia o ife.
        if ($data['chofer']['info']->url_licencia !== null || $data['chofer']['info']->url_ife !== null)
        {
          // Si tiene la licencia la copea.
          if ($data['chofer']['info']->url_licencia)
          {
            $ext = explode('.', $data['chofer']['info']->url_licencia);
            copy($data['chofer']['info']->url_licencia, $path.'CHOFER COPIA LICENCIA.'.$ext[1]);

            $licencia = array(
              'url' => $path.'CHOFER COPIA LICENCIA.'.$ext[1],
            );

            // Actualiza el documento copia licencia para la factura.
            $this->updateDocumento($licencia, $idFactura, 4);
          }
          else
            $this->updateDocumento(null, $idFactura, 4, 'f');

          // Si tiene la ife la copea.
          if ($data['chofer']['info']->url_ife)
          {
            $ext = explode('.', $data['chofer']['info']->url_ife);
            copy($data['chofer']['info']->url_ife, $path.'CHOFER COPIA DEL IFE.'.$ext[1]);

            $ife = array(
              'url' => $path.'CHOFER COPIA DEL IFE.'.$ext[1],
            );

            // Actualiza el documento copia ife para la factura.
            $this->updateDocumento($ife, $idFactura, 3);
          }
          else
            $this->updateDocumento(null, $idFactura, 3, 'f');
        }
      }

      if ($res->id_camion !== null)
      {
        $this->load->model('camiones_model');

        $data['camion'] = $this->camiones_model->getCamionInfo($res->id_camion);
      }

      return $data;
    }

    return false;
  }

  /*
   |-------------------------------------------------------------------------
   |  EMBARQUE
   |-------------------------------------------------------------------------
   */

  /**
   * Guarda la informacion del embarque y genera el documento PDF.
   *
   * @return array
   */
  public function storeEmbarque()
  {

    // Si el POST embId (id del embarque) es diferente de nada entonces elimina
    // todo de ese embarque para insertar uno nuevo.
    if ($_POST['embId'] != '')
    {
      $this->db->delete('facturacion_doc_embarque', array(
        'id_embarque' => $_POST['embId']));

      $this->db->delete('facturacion_doc_embarque_pallets', array(
        'id_embarque' => $_POST['embId']));
    }

    $data = array(
      'id_documento'   => $this->input->post('embIdDoc'),
      'id_factura'     => $this->input->post('embIdFac'),
      'fecha_carga'    => $this->input->post('pfecha_carga'),
      'fecha_embarque' => $this->input->post('pfecha_empaque'),
      'ctrl_embarque'  => $this->input->post('pctrl_embarque'),
    );

    $this->db->insert('facturacion_doc_embarque', $data);
    $idEmbarque = $this->db->insert_id('facturacion_doc_embarque_id_embarque_seq');

    $pallets = array();

    $otros = isset($_POST['potro']) ? $_POST['potro'] : array();

    foreach ($_POST['pno_posicion'] as $key => $track)
    {
      if (in_array($track, $otros) || $_POST['pid_pallet'][$key] !== '')
      {
        $pallets[] = array(
          'id_embarque' => $idEmbarque,
          'no_posicion' => $track,
          'id_pallet'   => $_POST['pid_pallet'][$key] !== '' ? $_POST['pid_pallet'][$key] : null ,
          'marca'       => $_POST['pid_pallet'][$key] !== '' ? $_POST['pmarca'][$key] : null,
          'descripcion' => $_POST['pid_pallet'][$key] === '' ? $_POST['pmarca'][$key] : null,
          // 'temperatura' => $_POST['pid_pallet'][$key] !== '' ? $_POST['ptemperatura'][$key] : null,
          'temperatura' => $_POST['ptemperatura'][$key],
        );
      }
    }

    if (count($pallets) !== 0)
      $this->db->insert_batch('facturacion_doc_embarque_pallets', $pallets);

    $dataJson = array(
      'fecha'        => $this->input->post('pfecha'),
      'inicio'       => $this->input->post('pinicio'),
      'termino'      => $this->input->post('ptermino'),
      'elaboro'      => $this->input->post('pelaboro'),
      'destino'      => $this->input->post('pdestino'),
      'destinatario' => $this->input->post('pdestinatario'),
    );
    $this->updateDocumento($dataJson, $data['id_factura'], $data['id_documento']);

    $this->load->model('facturacion_model');
    // Obtiene la informacion de la factura.
    $factura = $this->facturacion_model->getInfoFactura($data['id_factura']);

    // Obtiene la ruta donde se guardan los documentos del cliente.
    $path = $this->creaDirectorioDocsCliente($factura['info']->cliente->nombre_fiscal, $factura['info']->serie, $factura['info']->folio, substr($factura['info']->fecha, 0, 10));

    // Genera el documento de embarque.
    $this->generaDoc($data['id_factura'], $data['id_documento'], $path);

    return array('passes' => true);
  }

  /**
   * Obtiene la informacion de un embarque incluyendo pallets.
   *
   * @param  string $idFactura
   * @param  string $idDocumento
   * @return array
   */
  public function getEmbarqueData($idFactura, $idDocumento)
  {
    $sql = $this->db->query(
      "SELECT id_embarque,
              id_factura,
              DATE(fecha_carga) AS fecha_carga,
              DATE(fecha_embarque) AS fecha_embarque,
              ctrl_embarque
       FROM facturacion_doc_embarque
       WHERE id_factura = {$idFactura} AND
             id_documento = {$idDocumento}"
    );

    $data = array('info' => array(), 'pallets' => array());
    if ($sql->num_rows() > 0)
    {
      $data['info'] = $sql->result();

      $sql->free_result();

      $sql = $this->db->query(
        "SELECT fep.no_posicion,
                fep.id_pallet,
                fep.marca,
                fep.descripcion,
                fep.temperatura,
                rp.no_cajas AS cajas,
                rp.kilos_pallet,
                string_agg(clasi.nombre::text, ', '::text) AS clasificaciones,
                cali.calibres

         FROM facturacion_doc_embarque_pallets fep

         LEFT JOIN rastria_pallets rp ON rp.id_pallet = fep.id_pallet

         LEFT JOIN (
            SELECT rpr.id_pallet, cl.nombre
            FROM rastria_pallets_rendimiento rpr
            INNER JOIN clasificaciones cl ON rpr.id_clasificacion = cl.id_clasificacion
            GROUP BY rpr.id_pallet, rpr.id_clasificacion, cl.nombre
            ORDER BY rpr.id_pallet
         ) AS clasi ON clasi.id_pallet = fep.id_pallet

        LEFT JOIN (
            SELECT rpc.id_pallet, string_agg(cal.nombre::text, ', '::text) AS calibres
            FROM rastria_pallets_calibres rpc
            INNER JOIN calibres cal ON  rpc.id_calibre = cal.id_calibre
            GROUP BY rpc.id_pallet
            ORDER BY rpc.id_pallet
        ) cali ON cali.id_pallet = fep.id_pallet

        WHERE id_embarque = {$data['info'][0]->id_embarque}
        GROUP BY fep.no_posicion, fep.id_pallet, fep.id_pallet, fep.marca, fep.descripcion, fep.temperatura, rp.no_cajas, cali.calibres, rp.kilos_pallet
        ORDER BY fep.no_posicion ASC"
      );

      if ($sql->num_rows() > 0)
        $data['pallets'] = $sql->result();
    }

    // Obtiene los kilos de la pesada segun el ticket seleccionado en el documento
    // manifiesto de chofer.
    $infoManifiesto = $this->getJsonDataDocus($idFactura, 1);
    $data['kilos_pesada'] = 'Ticket no asignado';
    if ($infoManifiesto && $infoManifiesto->no_ticket !== '')
    {
      $data['kilos_pesada'] = $this->db->select('kilos_neto')
        ->from('bascula')
        ->where('id_area', $infoManifiesto->area_id)
        ->where('folio', $infoManifiesto->no_ticket)
        ->get()->row()->kilos_neto;
    }

    // echo "<pre>";
    //   var_dump($data['kilos_pesada']);
    // echo "</pre>";exit;

    return $data;
  }

  /*
   |-------------------------------------------------------------------------
   |  CERTIFICADO TLC
   |-------------------------------------------------------------------------
   */

  /**
  * Guarda la informacion del Certificado y el PDF.
  *
  * @return array
  */
  public function storeCertificadoTlc($idFactura, $idDocumento)
  {
    $dataJson = array(
      'empresa'               => $this->input->post('dempresa'),
      'dempresa_id'           => $this->input->post('empresa_id'),
      'domicilio'             => $this->input->post('ddomicilio'),
      'registro_fiscal'       => $this->input->post('dregistroFiscal'),
      'fecha1'                => $this->input->post('dfecha1'),
      'fecha2'                => $this->input->post('dfecha2'),
      'cliente'               => $this->input->post('dcliente_tlc'),
      'cliente_id'            => $this->input->post('dcliente_id_tlc'),
      'cliente_domicilio'     => $this->input->post('dcliente_domicilio'),
      'cliente_no_reg_fiscal' => $this->input->post('dcliente_no_reg_fiscal_tlc'),
      'telefono'              => $this->input->post('dtelefono'),
      'fax'                   => $this->input->post('dfax'),
    );

    $this->updateDocumento($dataJson, $idFactura, $idDocumento);

    $this->load->model('facturacion_model');
    // Obtiene la informacion de la factura.
    $factura = $this->facturacion_model->getInfoFactura($idFactura);

    // Obtiene la ruta donde se guardan los documentos del cliente.
    $path = $this->creaDirectorioDocsCliente($factura['info']->cliente->nombre_fiscal, $factura['info']->serie, $factura['info']->folio, substr($factura['info']->fecha, 0, 10));

    // Genera el documento de embarque.
    $this->generaDoc($idFactura, $idDocumento, $path);

    return array('passes' => true);
  }

  /*
   |-------------------------------------------------------------------------
   |  MANIFIESTO DEL CAMION
   |-------------------------------------------------------------------------
   */

  /**
   * Almacena la informacion.
   *
   * @param  string $idFactura
   * @param  string $idDocumento
   * @return array
   */
  public function storeManifiestoCamion($idFactura, $idDocumento)
  {
    $dataJson = array(
      'remitente'        => $this->input->post('dremitente'),
      'consignatorio'    => $this->input->post('dconsignatorio'),
      'fecha_embarque'   => $this->input->post('dfecha_embarque'),
      'camion_placas'    => $this->input->post('dmc_camion_placas'),
      'caja_no'          => $this->input->post('dmc_caja_no'),
      'linea_transporte' => $this->input->post('dmc_linea_transporte'),
      'razon_social'     => $this->input->post('dmc_razon_social'),
      'domicilio_fiscal' => $this->input->post('dmc_domicilio_fiscal'),
      'rfc'              => $this->input->post('dmc_rfc'),
      'curp'             => $this->input->post('dmc_curp'),
    );

    $this->updateDocumento($dataJson, $idFactura, $idDocumento);

    $this->load->model('facturacion_model');
    // Obtiene la informacion de la factura.
    $factura = $this->facturacion_model->getInfoFactura($idFactura);

    // Obtiene la ruta donde se guardan los documentos del cliente.
    $path = $this->creaDirectorioDocsCliente($factura['info']->cliente->nombre_fiscal, $factura['info']->serie, $factura['info']->folio, substr($factura['info']->fecha, 0, 10));

    // Genera el documento de embarque.
    $this->generaDoc($idFactura, $idDocumento, $path);

    return array('passes' => true);
  }

  /**
   * Obtiene los pallets del embarque pero por clasificaciones.
   *
   * @param  string $idEmbarque
   * @return array
   */
  public function getEmbarqueClasifi($idEmbarque, $id_factura=null)
  {
    $query = $this->db->query(
      "SELECT rpr.id_clasificacion, SUM(rpr.cajas) AS cajas, cl.nombre AS clasificacion
        FROM facturacion_doc_embarque_pallets fep
        INNER JOIN rastria_pallets_rendimiento rpr ON rpr.id_pallet = fep.id_pallet
        INNER JOIN clasificaciones cl ON cl.id_clasificacion = rpr.id_clasificacion
        WHERE fep.id_embarque = {$idEmbarque}
        GROUP BY rpr.id_clasificacion, cl.nombre"
    );

    $data['clasificaciones'] = array();
    if ($query->num_rows() > 0)
      $data['clasificaciones'] = $query->result();
    elseif($id_factura != null)
    {
      $query = $this->db->query(
        "SELECT fep.id_clasificacion, SUM(fep.cantidad) AS cajas, fep.descripcion AS clasificacion
          FROM facturacion_productos fep
          WHERE fep.id_factura = {$id_factura}
          GROUP BY fep.id_clasificacion, fep.descripcion"
      );
      if ($query->num_rows() > 0)
        $data['clasificaciones'] = $query->result();
    }

    return $data;
  }

  /*
   |-------------------------------------------------------------------------
   |  CHOFER COPIA IFE
   |-------------------------------------------------------------------------
   */
  public function saveChoferCopiaIfe($idFactura, $idDocumento)
  {
    if ($_FILES['pife_file']['tmp_name'] !== '' || isset($_POST['pife_check']))
    {
      if ($_FILES['pife_file']['tmp_name'] !== '') {
        $this->load->library('my_upload');

        $this->load->model('facturacion_model');
        // Obtiene la informacion de la factura.
        $factura = $this->facturacion_model->getInfoFactura($idFactura);

        // Obtiene la ruta donde se guardan los documentos del cliente.
        $path = $this->creaDirectorioDocsCliente($factura['info']->cliente->nombre_fiscal, $factura['info']->serie, $factura['info']->folio, substr($factura['info']->fecha, 0, 10));

        $dataJson = $this->db
          ->select('data')
          ->from('facturacion_documentos')
          ->where('id_factura', $idFactura)
          ->where('id_documento', $idDocumento)
          ->get()->row()->data;

        if ($dataJson !== '')
        {
          $dataJson = json_decode($dataJson);
          unlink(str_replace('\\', '', $dataJson->url));
        }

        $config_upload = array(
          'upload_path'     => $path, //APPPATH.$path_lic
          'allowed_types'   => '*',
          'max_size'        => '2048',
          'encrypt_name'    => FALSE,
          'file_name'       => 'CHOFER COPIA DEL IFE',
          'remove_spaces'   => false,
        );

        $this->my_upload->initialize($config_upload);
        $data_doc = $this->my_upload->do_upload('pife_file');

        $path = explode('application/', $data_doc['full_path']);
        $path = APPPATH.$path[1];
      }

      $dataJson = array(
        'url'   => $path,
        'check' => (isset($_POST['pife_check'])? true: false)
      );

      $this->updateDocumento($dataJson, $idFactura, $idDocumento);
    }
  }

  /*
   |-------------------------------------------------------------------------
   |  CHOFER COPIA LICENCIA
   |-------------------------------------------------------------------------
   */
  public function saveChoferCopiaLicencia($idFactura, $idDocumento)
  {
    if ($_FILES['plicencia_file']['tmp_name'] !== '' || isset($_POST['plicencia_check']))
    {
      $path = '';
      if ($_FILES['plicencia_file']['tmp_name'] !== '') {
        $this->load->library('my_upload');

        $this->load->model('facturacion_model');
        // Obtiene la informacion de la factura.
        $factura = $this->facturacion_model->getInfoFactura($idFactura);

        // Obtiene la ruta donde se guardan los documentos del cliente.
        $path = $this->creaDirectorioDocsCliente($factura['info']->cliente->nombre_fiscal, $factura['info']->serie, $factura['info']->folio, substr($factura['info']->fecha, 0, 10));

        $dataJson = $this->db
          ->select('data')
          ->from('facturacion_documentos')
          ->where('id_factura', $idFactura)
          ->where('id_documento', $idDocumento)
          ->get()->row()->data;

        if ($dataJson !== '')
        {
          $dataJson = json_decode($dataJson);
          unlink(str_replace('\\', '', $dataJson->url));
        }

        $config_upload = array(
          'upload_path'     => $path,
          'allowed_types'   => '*',
          'max_size'        => '2048',
          'encrypt_name'    => FALSE,
          'file_name'       => 'CHOFER COPIA LICENCIA',
          'remove_spaces'   => false,
        );

        $this->my_upload->initialize($config_upload);
        $data_doc = $this->my_upload->do_upload('plicencia_file');

        $path = explode('application/', $data_doc['full_path']);
        $path = APPPATH.$path[1];
      }

      $dataJson = array(
        'url'   => $path,
        'check' => (isset($_POST['plicencia_check'])? true: false)
      );

      $this->updateDocumento($dataJson, $idFactura, $idDocumento);
    }
  }

  /*
   |-------------------------------------------------------------------------
   |  SEGURO CAMION
   |-------------------------------------------------------------------------
   */
  public function saveSeguroCamion($idFactura, $idDocumento)
  {
    if ($_FILES['fseguro_camion']['tmp_name'] !== '' || isset($_POST['fseguro_check']))
    {
      if ($_FILES['fseguro_camion']['tmp_name'] !== '') {
        $this->load->library('my_upload');

        $this->load->model('facturacion_model');
        // Obtiene la informacion de la factura.
        $factura = $this->facturacion_model->getInfoFactura($idFactura);

        // Obtiene la ruta donde se guardan los documentos del cliente.
        $path = $this->creaDirectorioDocsCliente($factura['info']->cliente->nombre_fiscal, $factura['info']->serie, $factura['info']->folio, substr($factura['info']->fecha, 0, 10));

        $dataJson = $this->db
          ->select('data')
          ->from('facturacion_documentos')
          ->where('id_factura', $idFactura)
          ->where('id_documento', $idDocumento)
          ->get()->row()->data;

        if ($dataJson !== '')
        {
          $dataJson = json_decode($dataJson);
          unlink(str_replace('\\', '', $dataJson->url));
        }

        $config_upload = array(
          'upload_path'     => $path,
          'allowed_types'   => '*',
          'max_size'        => '2048',
          'encrypt_name'    => FALSE,
          'file_name'       => 'SEGURO CAMION',
          'remove_spaces'   => false,
        );

        $this->my_upload->initialize($config_upload);
        $data_doc = $this->my_upload->do_upload('fseguro_camion');

        $path = explode('application/', $data_doc['full_path']);
        $path = APPPATH.$path[1];
      }

      $dataJson = array(
        'url' => $path,
        'check' => (isset($_POST['fseguro_check'])? true: false)
      );

      $this->updateDocumento($dataJson, $idFactura, $idDocumento);
    }
  }


  /*
   |-------------------------------------------------------------------------
   |  METODOS PARA CREAR LOS DIRECTORIOS DE LOS CLIENTES PARA GUARDAR LOS
   |  DOCUMENTOS.
   |-------------------------------------------------------------------------
   */

  /**
   * Crea el directorio por cliente donde se guardara los documentos.
   *
   * @param  string $clienteNombre
   * @param  string $folioFactura
   * @return string
   */
  public function creaDirectorioDocsCliente($clienteNombre, $serieFactura, $folioFactura, $fecha=null)
  {
    $fecha = explode('-', ($fecha? $fecha: date("Y-m-d")));
    $path = APPPATH.'documentos/CLIENTES/';

    if ( ! file_exists($path))
    {
      // echo $path.'<br>';
      mkdir($path, 0777);
    }

    $path .= MyString::quitAcentos(strtoupper($clienteNombre)).'/';
    if ( ! file_exists($path))
    {
      // echo $path;
      mkdir($path, 0777);
    }

    $path .= $fecha[0].'/';
    if ( ! file_exists($path))
    {
      // echo $path;
      mkdir($path, 0777);
    }

    $path .= $this->mesToString($fecha[1]).'/';
    if ( ! file_exists($path))
    {
      // echo $path;
      mkdir($path, 0777);
    }

    $path .= 'FACT-'.($serieFactura !== '' ? $serieFactura.'-' : '').$folioFactura.'/';
    if ( ! file_exists($path))
    {
      // echo $path;
      mkdir($path, 0777);
    }

    return $path;
  }

  /**
   * Regresa el MES que corresponde en texto.
   *
   * @param  int $mes
   * @return string
   */
  private function mesToString($mes)
  {
    switch(floatval($mes))
    {
      case 1: return 'ENERO'; break;
      case 2: return 'FEBRERO'; break;
      case 3: return 'MARZO'; break;
      case 4: return 'ABRIL'; break;
      case 5: return 'MAYO'; break;
      case 6: return 'JUNIO'; break;
      case 7: return 'JULIO'; break;
      case 8: return 'AGOSTO'; break;
      case 9: return 'SEPTIEMBRE'; break;
      case 10: return 'OCTUBRE'; break;
      case 11: return 'NOVIEMBRE'; break;
      case 12: return 'DICIEMBRE'; break;
    }
  }

  private function acomodaStringClasificacion($clasifi)
  {
    if ( ! $clasifi) return '';

    $arrayPalabras = explode(' ', $clasifi);

    $newArrayPalabras = array();

    foreach ($arrayPalabras as $key => $palabra)
    {
      if ($key === 0)
      {
        array_push($newArrayPalabras, strtoupper(substr($arrayPalabras[0], 0 , 1)).'.');
      }
      else
      {
        $abreviacion = '';

        switch ($palabra)
        {
          case 'LIMON':
            $abreviacion = 'LMON.';
            break;
          case 'ALIMONADO':
            $abreviacion = 'ALIM.';
            break;
          case 'VERDE':
            $abreviacion = 'VER.';
            break;
          case 'INDUSTRIAL':
            $abreviacion = 'INDUS.';
            break;
          case 'ECONOMICO':
            $abreviacion = 'ECON.';
            break;
          default:
            $abreviacion = $palabra;
            break;
        }

        array_push($newArrayPalabras, $abreviacion);
      }
    }

    return implode(' ', $newArrayPalabras);
  }

  /*
   |-------------------------------------------------------------------------
   |  AJAX
   |-------------------------------------------------------------------------
   */

  /**
   * Actualiza los datos de un documento por ajax.
   *
   * @param  string $idFactura
   * @param  string $idDocumento
   * @return array
   */
  public function ajaxUpdateDocumento($idFactura, $idDocumento)
  {
    $this->updateDocumento($_POST, $idFactura, $idDocumento);

    $this->load->model('facturacion_model');

    // Obtiene la informacion de la factura.
    $factura = $this->facturacion_model->getInfoFactura($idFactura);

    // Obtiene la ruta donde se guardan los documentos del cliente.
    $path = $this->creaDirectorioDocsCliente($factura['info']->cliente->nombre_fiscal, $factura['info']->serie, $factura['info']->folio, substr($factura['info']->fecha, 0, 10));

    // Llama el metodo que ejecuta la funcion dependiendo del documento que se
    // esta actualizando y los guarda en disco.
    $this->generaDoc($idFactura, $idDocumento, $path);

    return array('passes' => true);
  }

  public function ajaxGetPalletsLibres($sqlX = null){
    $sql = '';
    if ($this->input->get('folios') != '') {
      $folios = explode('al', $this->input->get('folios'));
      if (isset($folios[0]) && isset($folios[1]) && is_numeric(trim($folios[0])) && is_numeric(trim($folios[1]))) {
        $sql = " AND folio BETWEEN ".trim($folios[0])." AND ".trim($folios[1])."";
      } elseif (is_numeric(trim($folios[0]))) {
        $sql = " AND folio = ".trim($folios[0])."";
      }
    }
    if ($this->input->get('term') != '')
      $sql .= " AND (lower(c.clasificaciones) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' )";

    if ( ! is_null($sqlX))
      $sql .= $sqlX;

    $res = $this->db->query(
        "SELECT *
        FROM embarque_pallets_libres AS c
        WHERE 1 = 1
          {$sql}
        ORDER BY c.folio DESC
        LIMIT 30"
    );

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $response[] = array(
            'id'    => $itm->id_pallet,
            'label' => $itm->clasificaciones,
            'value' => $itm->clasificaciones,
            'item'  => $itm,
        );
      }
    }

    return $response;
  }

  /*
   |-------------------------------------------------------------------------
   |  PDF'S DOCUMENTOS
   |-------------------------------------------------------------------------
   */

  /**
   * Esta funcion permite visualizar el pdf o guardarlo en disco en la ruta
   * especificada.
   *
   * @param  string $idFactura
   * @param  string $idDocumento
   * @param  string $path
   * @return void
   */
  public function generaDoc($idFactura, $idDocumento, $path=null)
  {
    // Obtiene el nombre del documento que se actualizo.
    $nombreDoc = $this->db
      ->select('nombre')
      ->from('rastria_documentos')
      ->where('id_documento', $idDocumento)
      ->get()
      ->row()
      ->nombre;

    // Convierte le nombre del documento en camelCase y elimina espacios.
    $metodo = "pdf".preg_replace('/\s/', '', ucwords(strtolower($nombreDoc)));

    // Verifica si existe un metodo para hacer el pdf del documento.
    if (method_exists($this, $metodo))
    {
      // Llama el metodo del documento.
      $pdfData = $this->{$metodo}($idFactura, $idDocumento);

      $pdf   = $pdfData['pdf'];
      $texto = $pdfData['texto'];

      // Si $path es diferente a null entonces los guarda en la ruta espedificada
      // si no lo visualiza.
      if ($path)
        $pdf->Output($path.$nombreDoc.'.pdf', 'F');
      else
        $pdf->Output($texto, 'I');
    }
  }

  /**
   * Obtiene la informacion para el documento manifiesto chofer.
   *
   * @param  string $idFactura
   * @param  string $idDocumento
   * @return array
   */
  public function getJsonDataDocus($idFactura, $idDocumento)
  {
    $sql = $this->db
    ->select('data')
    ->from('facturacion_documentos')
    ->where('id_factura', $idFactura)
    ->where('id_documento', $idDocumento)
    ->get();

    $data = array();
    if ($sql->num_rows() > 0)
      $data = json_decode($sql->row()->data);

    return $data;
  }

  /**
   * Contruye el PDF del Manifiesto Chofer.
   *
   * @param  string $idFactura
   * @param  string $idDocumento
   * @return void
   */
  public function pdfManifiestoDelChofer($idFactura, $idDocumento)
  {
    $this->load->model('facturacion_model');

    $data = $this->getJsonDataDocus($idFactura, $idDocumento);

    $dataFactura = $this->facturacion_model->getInfoFactura($idFactura);

    // echo "<pre>";
    //   var_dump($data);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    $pdf->show_head = false;

    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('helvetica','', 8);

    $pdf->SetXY(7, 3);
    $pdf->Image(APPPATH.'images/logo2.png');

    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial','',8);

    $pdf->SetXY(130, 3);
    $pdf->Cell(80, 6, 'KM.8 CARRETERA TECOMAN PLAYA AZUL  C.P. 28935', 0, 0, 'C');

    $pdf->SetXY(130, 10);
    $pdf->Cell(80, 6, 'TECOMAN, COLIMA R.F.C. ESJ 970527 63A', 0, 0, 'C');

    $pdf->SetXY(130, 17);
    $pdf->Cell(80, 6, 'TELS 313 324 4420  FAX : 313 324 5402  CEL : 313 113 0317', 0, 0, 'C');

    $pdf->SetXY(130, 24);
    $pdf->Cell(80, 6, 'TECOMAN, COLIMA R.F.C. ESJ 970527 63A', 0, 0, 'C');

    $pdf->SetFont('Arial','B',12);
    $pdf->SetXY(115, 35);
    $pdf->Cell(80, 6, 'FOLIO FACTURA: ' . $dataFactura['info']->serie . $data->folio, 0, 0, 'C');

    $pdf->SetXY(10, 45);
    $pdf->SetFillColor(146,208,80);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(130, 6, 'CONDICIONES DEL FLETE', 1, 0, 'C', 1);

    $pdf->SetXY(140, 45);
    $pdf->Cell(70, 6, 'COMPROMISO DE ENTREGA', 1, 0, 'C', 1);

    $pdf->SetXY(10, 51);
    $pdf->Cell(130, 6, 'DESTINO', 1, 0, 'C', 1);

    $pdf->SetXY(140, 51);
    $pdf->Cell(35, 6, 'FECHA', 1, 0, 'C', 1);

    $pdf->SetXY(175, 51);
    $pdf->Cell(35, 6, 'HORA', 1, 0, 'C', 1);

    $pdf->SetXY(10, 57);
    $pdf->SetFont('Arial','',9);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(130, 6, 'DIRECCION : ' . $data->direccion, 1, 0, 'L', 1);

    $pdf->SetXY(140, 57);
    $pdf->Cell(35, 6, date('Y-m-d'), 1, 0, 'C', 1);

    $pdf->SetXY(175, 57);
    $pdf->Cell(35, 6, '', 1, 0, 'C', 1);

    $pdf->SetXY(10, 63);
    $pdf->Cell(130, 6, 'NOMBRE DEL CLIENTE : ' . $data->cliente, 1, 0, 'L', 1);

    $pdf->SetXY(140, 63);
    $pdf->MultiCell(70, 6, 'TELS : ', 1, 'L', 1);

    $pdf->SetXY(10, 69);
    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(146,208,80);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(130, 6, 'DATOS DE LINEA TRANSPORTISTA', 1, 0, 'C', 1);

    $pdf->SetXY(140, 69);
    $pdf->Cell(70, 6, 'PESADA BASCULA', 1, 0, 'C', 1);

    $pdf->SetXY(10, 75);
    $pdf->SetFont('Arial','',9);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(130, 6, 'NOMBRE DE LINEA : ' . strtoupper($data->linea_trans), 1, 0, 'L', 1);

    $pdf->SetXY(140, 75);
    $pdf->Cell(70, 66, '', 1, 0, 'C', 1);

    $pdf->SetXY(10, 81);
    $pdf->Cell(65, 6, 'TELS : ' . $data->linea_tel, 1, 0, 'L', 1);

    $pdf->SetXY(75, 81);
    $pdf->Cell(65, 6, 'ID : ' . $data->linea_ID, 1, 0, 'L', 1);

    $pdf->SetXY(10, 87);
    $pdf->Cell(65, 6, 'No. CARTA PORTE : ' . $data->no_carta_porte, 1, 0, 'L', 1);

    $pdf->SetXY(75, 87);
    $pdf->Cell(65, 6, 'IMPORTE : ' . $data->importe, 1, 0, 'L', 1);

    $pdf->SetXY(10, 93);
    $pdf->SetFont('Arial','B',12);
    $pdf->SetFillColor(146,208,80);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(130, 6, 'DATOS DEL CHOFER', 1, 0, 'C', 1);

    $pdf->SetXY(10, 99);
    $pdf->SetFont('Arial','',9);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(130, 6, 'NOMBRE CHOFER : ' . strtoupper($data->chofer), 1, 0, 'L', 1);

    $pdf->SetXY(10, 105);
    $pdf->Cell(65, 6, 'TELS : ' . $data->chofer_tel, 1, 0, 'L', 1);

    $pdf->SetXY(75, 105);
    $pdf->Cell(65, 6, 'ID : ' . $data->chofer_ID, 1, 0, 'L', 1);

    $pdf->SetXY(10, 111);
    $pdf->Cell(65, 6, 'No. LICENCIA : ' . $data->chofer_no_licencia, 1, 0, 'L', 1);

    $pdf->SetXY(75, 111);
    $pdf->Cell(65, 6, 'No. IFE : ' . $data->chofer_ife, 1, 0, 'L', 1);

    $pdf->SetXY(10, 117);
    $pdf->Cell(65, 6, 'PLACAS CAMION : ' . $data->camion_placas, 1, 0, 'L', 1);

    $pdf->SetXY(75, 117);
    $pdf->Cell(65, 6, 'No. ECON : ' . $data->camion_placas_econ, 1, 0, 'L', 1);

    $pdf->SetXY(10, 123);
    $pdf->Cell(65, 6, 'PLACAS TERMO : ' . $data->camion_placas_termo, 1, 0, 'L', 1);

    $pdf->SetXY(75, 123);
    $pdf->Cell(65, 6, 'No. ECON : ' . $data->camion_placas_termo_econ, 1, 0, 'L', 1);

    $pdf->SetXY(10, 129);
    $pdf->Cell(65, 6, 'MARCA : ' . strtoupper($data->camion_marca), 1, 0, 'L', 1);

    $pdf->SetXY(75, 129);
    $pdf->Cell(65, 6, 'MODELO : ' . $data->camion_model, 1, 0, 'L', 1);

    $pdf->SetXY(10, 135);
    $pdf->Cell(65, 6, 'COLOR : ' . strtoupper($data->camion_color), 1, 0, 'L', 1);

    $pdf->SetXY(75, 135);
    $pdf->Cell(65, 6, 'OTROS : ', 1, 0, 'L', 1);

    $pdf->SetXY(10, 135);
    $pdf->Cell(130, 6, 'No. TICKET PESADA BASCULA : ' . $data->no_ticket, 1, 0, 'L', 1);

    $pdf->SetXY(10, 141);
    $pdf->SetFont('Arial','B',15);
    $pdf->SetFillColor(146,208,80);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(200, 8, 'MANIFIESTO DEL CHOFER', 1, 0, 'C', 1);

    $pdf->SetXY(10, 149);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(200, 105, '', 1, 0, 'C', 1);

    $txt = "COMO CHOFER DEL CAMION ARRIBA DESCRITO, MANIFIESTO EN EL PRESENTE DOCUMENTO, QUE EL (LOS) PRODUCTO(S) TRANSPORTADO(S) FUE CARGADO EN MI PRESENCIA Y VERIFIQUE QUE VA LIBRE DE CUALQUIER TIPO DE SUSTANCIA U OBJETO PROHIBIDO (ARMAS O NARCÓTICOS), DE TAL MANERA QUE EN CASO DE QUE ALGUNA AUTORIDAD EN FUNCIONES EFECTÚE LA REVISIÓN CORRESPONDIENTE AL INTERIOR Y ENCUENTRE ALGUN OBJETO NO AMPARADO EN LA FACTURA, PEDIDO, EMBARQUE O CARTA PORTE CORRESPONDIENTE AL PRESENTE FLETE. POR LO QUE EXIMO DE TODA RESPONSABILIDAD AL (LOS) CONTRATANTE(S) EMPAQUE SAN JORGE, SA DE CV; Y AL (LOS) DESTINATARIO(S); TENIENDO PROHIBIDO LLEVAR Y/O TRANSPORTAR OTRA MERCANCIA Y SI POR ALGUNA CIRCUNSTANCIA LO HAGO, ASUMO LAS CONSECUENCIAS DERIVADAS DE LA VIOLACION A ESTAS DISPOSICIONES.

      ME COMPROMETO A TRANSPORTAR LA FRUTA A UNA TEMPERATURA DE : 45 GRADOS FAHRENHEIT, EN PARADAS DE DESCANSO Y COMIDAS IR GASEANDO LA FRUTA Y LLEGAR A MI DESTINO EN TIEMPO Y FORMA.

      ACEPTO TENER REPERCUCIONES EN EL PAGO DEL FLETE, SI NO ENTREGO LA MERCANCIA CONFORME A LA FECHA Y HORA DE ENTREGA ARRIBA ESTIPULADA Y TAMBIEN SI NO CUMPLO CON LA TEMPERATURA INDICADA  Y POR MOTIVOS QUE SE RELACIONEN DIRECTAMENTE CON EL MAL ESTADO MECÁNICO DE MI UNIDAD (CAMIÓN ARRIBA DESCRITO), SE ME DESCONTARA UN 20% (VEINTE POR CIENTO) DEL VALOR DEL FLETE, ASI COMO CUALQUIER DIFERENCIA O ANORMALIDAD EN LA ENTREGA DE LA MERCANCIA.
      ";

    $pdf->SetXY(11, 150);
    $pdf->SetFont('Arial','',7);
    $pdf->MultiCell(198, 4, $txt, 0, 'L', 1);

    $pdf->SetXY(25, 207);
    $pdf->Cell(30, 37, '', 1, 0, '', 1);

    $pdf->SetXY(26, 245);
    $pdf->Cell(28, 6, 'HUELLA DEL CHOFER', 0, 0, 'C', 1);

    $pdf->SetXY(26, 237);
    $pdf->SetFont('Arial','B',7);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(166, 166, 166);
    $pdf->Cell(28, 6, 'PULGAR DERECHO', 0, 0, 'C', 1);

    $pdf->SetXY(80, 210);
    $pdf->SetFont('Arial','B',11);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(70, 6, 'RECIBO Y ACEPTO DE CONFORMIDAD :', 0, 0, 'C', 1);

    $pdf->SetXY(80, 237);
    $pdf->Cell(70, 6, 'NOMBRE Y FIRMA DEL CHOFER', 0, 0, 'C', 1);

    $pdf->SetXY(80, 232);
    $pdf->Cell(70, 6, '__________________________________', 0, 0, 'C', 1);

    $pdf->SetXY(80, 230);
    $pdf->Cell(70, 6, strtoupper($data->chofer), 0, 0, 'C', 1);

    $fecha = explode('-', $data->fecha);

    $pdf->SetXY(80, 247);
    $pdf->SetFont('Arial','',11);
    $pdf->Cell(70, 6, 'TECOMAN, COL A ' . $fecha[2] . ' ' . strtoupper(MyString::mes($fecha[1])) . ' ' . $fecha[0], 0, 0, 'C', 1);

    $chofer = strtoupper(str_replace(" ", "_", $data->chofer));
    $fecha = str_replace(" ", "_", $data->fecha);

    return array('pdf' => $pdf, 'texto' => 'MANIFIESTO DEL CHOFER.pdf');
  }

  /**
   * Contruye el PDF del Manifiesto Chofer.
   *
   * @param  string $idFactura
   * @param  string $idDocumento
   * @return void
   */
  public function pdfAcomodoDelEmbarque($idFactura, $idDocumento)
  {
    $data = $this->getEmbarqueData($idFactura, $idDocumento);

    // $result = $this->db
    //   ->select("data")
    //   ->from("facturacion_documentos")
    //   ->where('id_factura', $idFactura)
    //   ->where('id_documento', $idDocumento)
    //   ->get()->row()->data;

    // $jsonData = json_decode($result);

    $jsonData = $this->getJsonDataDocus($idFactura, $idDocumento);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    $pdf->show_head = false;

    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('helvetica','', 8);

    $pdf->SetXY(7, 3);
    $pdf->Image(APPPATH.'images/logo.png');

    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial','',7);

    $pdf->SetXY(60, 3);
    $pdf->Cell(80, 4, 'KM.8 CARRETERA TECOMAN PLAYA AZUL  C.P. 28935', 0, 0, 'L');

    $pdf->SetXY(60, 8);
    $pdf->Cell(80, 4, 'TECOMAN, COLIMA R.F.C. ESJ 970527 63A', 0, 0, 'L');

    $pdf->SetXY(60, 13);
    $pdf->Cell(80, 4, 'TELS 313 324 4420  FAX : 313 324 5402  CEL : 313 113 0317', 0, 0, 'L');

    $pdf->SetXY(60, 18);
    $pdf->Cell(80, 4, 'NEXTEL: 313 120 05 81   I.D: 62*15*32723', 0, 0, 'L');

    $pdf->SetXY(167, 3);
    $pdf->SetFont('Arial','B',9);
    $pdf->SetFillColor(146,208,80);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(40, 6, 'CTRL. DE EMBARQUE', 1, 0, 'C', 1);

    $pdf->SetXY(167, 16);
    $pdf->Cell(40, 6, 'FECHA', 1, 0, 'C', 1);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetXY(167, 8);
    $pdf->Cell(40, 6, $data['info'][0]->ctrl_embarque, 1, 0, 'C', 1);

    $pdf->SetXY(167, 22);
    $pdf->Cell(40, 6, MyString::fechaAT($jsonData->fecha), 1, 0, 'C', 1);

    $pdf->SetXY(7, 33);
    $pdf->SetFillColor(146,208,80);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(200, 6, 'DATOS DEL EMBARQUE', 1, 0, 'C', 1);

    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetXY(7, 43);
    $pdf->Cell(40, 6, 'TRACK', 1, 0, 'C', 1);

    $pdf->SetXY(7, 53);
    $pdf->Cell(40, 6, 'PALLET Nos.', 0, 0, 'C', 1);

    $pdf->SetFont('Arial','',9);
    // TRACK
    $totalKilosPallets = 0;
    for ($i=1; $i < 24 ; $i = $i + 2)
    {
      $y = $pdf->GetY();

      $pdf->SetXY(7, $y + 6);
      $pdf->Cell(20, 6, $i, 1, 0, 'L', 1);

      $pdf->SetXY(27, $y + 6);
      $pdf->Cell(20, 6, $i+1, 1, 0, 'L', 1);

      $txtTrack1 = 'Vacio';
      $txtTrack2 = 'Vacio';

      foreach ($data['pallets'] as $key => $pallet)
      {
        if ($pallet->no_posicion == $i)
        {
          if ($pallet->id_pallet != null)
          {
            $txtTrack1 = $pallet->cajas;
            $totalKilosPallets += floatval($pallet->kilos_pallet);
          }
          else
            $txtTrack1 = $pallet->descripcion;
        }

        if ($pallet->no_posicion == $i+1)
        {
          if ($pallet->id_pallet != null)
          {
            $txtTrack2 = $pallet->cajas;
            $totalKilosPallets += floatval($pallet->kilos_pallet);
          }
          else
            $txtTrack2 = $pallet->descripcion;
        }

        if ($txtTrack1 !== 'Vacio' && $txtTrack2 !== 'Vacio')
         break;
      }

      $pdf->SetXY(7, $y + 12);
      $pdf->Cell(20, 6, $txtTrack1, 1, 0, 'C', 1);

      $pdf->SetXY(27, $y + 12);
      $pdf->Cell(20, 6, $txtTrack2, 1, 0, 'C', 1);
    }

    $pdf->SetFont('Arial','B', 12);
    $pdf->SetXY(104, 44);
    $pdf->Cell(100, 6, 'Total Kilos: ' . $totalKilosPallets, 0, 0, 'R', 1);

    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(50, 52);
    $pdf->SetFont('Arial','B',9);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetAligns(array('C', 'C', 'L', 'L', 'C', 'C'));
    $pdf->SetWidths(array(10, 30, 60, 29, 13, 12));
    $pdf->Row(array('#', 'MARCA', 'CLASIFICACION', 'CALIBRE', 'CAJAS', 'TEMP'), true);

    $pdf->SetFont('Arial','',7);
    for ($i = 1; $i < 25 ; $i++)
    {
      $marca         = '';
      $clasificacion = '';
      $calibres      = '';
      $cajas         = '';
      $temperatura   = '';

        foreach ($data['pallets'] as $key => $pallet)
        {
          if ($pallet->no_posicion == $i)
          {
            $marca         = $pallet->id_pallet != null ? $pallet->marca : $pallet->descripcion;
            $clasificacion = $pallet->clasificaciones;
            $calibres      = $pallet->calibres;
            $cajas         = $pallet->cajas;
            $temperatura   = $pallet->temperatura;
            break;
          }
        }

      $clasificacion = $this->acomodaStringClasificacion($clasificacion);

      $pdf->SetX(50);
        $pdf->Row(array(
          $i,
          $marca,
          $clasificacion,
          $calibres,
          $cajas,
          $temperatura,
        ), false);
    }

    $pdf->SetFont('Arial','B',8);
    $y = $pdf->GetY();

    $pdf->SetXY(50, $y + 2);
    $pdf->Cell(50, 6, 'FECHA DE CARGA: ' . MyString::fechaAT($data['info'][0]->fecha_carga), 0, 0, 'L', 1);

    $pdf->SetXY(50, $y + 7);
    $pdf->Cell(50, 6, 'INICIO: ' . $jsonData->inicio, 0, 0, 'L', 1);

    $pdf->SetXY(105, $y + 7);
    $pdf->Cell(50, 6, 'TERMINO: ' . $jsonData->termino, 0, 0, 'L', 1);

    $pdf->SetXY(50, $y + 12);
    $pdf->Cell(105, 6, 'FECHA DE EMPAQUE: ' . MyString::fechaAT($data['info'][0]->fecha_embarque), 0, 0, 'L', 1);

    $pdf->SetXY(50, $y + 17);
    $pdf->Cell(105, 6, 'ELABORO: ' . strtoupper($jsonData->elaboro), 0, 0, 'L', 1);

    $pdf->SetXY(50, $y + 22);
    $pdf->Cell(105, 6, 'DESTINO: ' . strtoupper($jsonData->destino), 0, 0, 'L', 1);

    $pdf->SetXY(50, $y + 27);
    $pdf->Cell(157, 6, 'DESTINATARIO: ' . strtoupper($jsonData->destinatario), 0, 0, 'L', 1);

    return array('pdf' => $pdf, 'texto' => 'ACOMODO DEL EMBARQUE.pdf');
  }

  /**
   * Contruye el PDF Certificado TLC.
   *
   * @param  string $idFactura
   * @param  string $idDocumento
   * @return void
   */
  public function pdfCertificadoDeTlc($idFactura, $idDocumento)
  {
    $jsonData = $this->getJsonDataDocus($idFactura, $idDocumento);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    $pdf->show_head = false;

    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('helvetica','', 8);

    // $pdf->SetXY(7, 3);
    // $pdf->Image(APPPATH.'images/logo.png');

    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial','B',12);

    $pdf->SetXY(10, 5);
    $pdf->MultiCell(200, 4, "TRATADO DE LIBRE COMERCIO DE AMERICA DEL NORTE \n CERTIFICADO DE ORIGEN \n (Instrucciones al Reverso)", 0, 'C', 0);

    $pdf->SetFont('Arial','',7);
    $pdf->SetXY(10, 20);
    $pdf->Cell(200, 6, 'Llenar a máquina o con letra de molde. Este documento no será válido si presenta alguna raspadura, tachadura o enmendadura', 0, 0, 'L');

    // ---------------------

    $pdf->SetXY(10, 28);
    $pdf->Cell(90, 30, '', 1, 0, 'L');

    $pdf->SetFont('Arial','',7);
    $pdf->SetXY(11, 29);
    $pdf->Cell(80, 4, '1. Nombre y Domicilio del Exportador', 0, 0, 'L');

    $pdf->SetFont('Arial','',9);
    $pdf->SetXY(11, 33);
    $pdf->MultiCell(88, 4, $jsonData->empresa, 0, 'L', 0);

    $pdf->SetXY(11, $pdf->GetY() + 1);
    $pdf->MultiCell(88, 4, $jsonData->domicilio, 0, 'L', 0);

    $pdf->SetXY(11, 53);
    $pdf->Cell(88, 4, 'Número de Registro Fiscal:' . $jsonData->registro_fiscal,0, 0, 'L');

    //  --------------------

    $pdf->SetXY(100, 28);
    $pdf->Cell(110, 30, '', 1, 0, 'L');

    $pdf->SetFont('Arial','',7);
    $pdf->SetXY(101, 29);
    $pdf->Cell(80, 4, '2. Período que cubre:', 0, 0, 'L');

    $fechaDe = explode('-', $jsonData->fecha1);
    $fechaDeDia = $fechaDe[2];
    $fechaDeMes = $fechaDe[1];
    $fechaDeAno = $fechaDe[0];

    $pdf->SetXY(101, 45);
    $pdf->Cell(5, 4, 'De:', 0, 0, 'C');

    $pdf->SetXY(110, 41);
    $pdf->Cell(5, 4, 'D', 0, 0, 'C');

    $pdf->SetXY(110, 45);
    $pdf->Cell(5, 4, $fechaDeDia[0], 1, 0, 'C');

    $pdf->SetXY(115, 41);
    $pdf->Cell(5, 4, 'D', 0, 0, 'C');

    $pdf->SetXY(115, 45);
    $pdf->Cell(5, 4, $fechaDeDia[1], 1, 0, 'C');

    $pdf->SetXY(120, 41);
    $pdf->Cell(5, 4, 'M', 0, 0, 'C');

    $pdf->SetXY(120, 45);
    $pdf->Cell(5, 4, $fechaDeMes[0], 1, 0, 'C');

    $pdf->SetXY(125, 41);
    $pdf->Cell(5, 4, 'M', 0, 0, 'C');

    $pdf->SetXY(125, 45);
    $pdf->Cell(5, 4, $fechaDeMes[1], 1, 0, 'C');

    $pdf->SetXY(130, 41);
    $pdf->Cell(5, 4, 'A', 0, 0, 'C');

    $pdf->SetXY(130, 45);
    $pdf->Cell(5, 4, $fechaDeAno[2], 1, 0, 'C');

    $pdf->SetXY(135, 41);
    $pdf->Cell(5, 4, 'A', 0, 0, 'C');

    $pdf->SetXY(135, 45);
    $pdf->Cell(5, 4, $fechaDeAno[3], 1, 0, 'C');

    //  --------------------

    $fechaA = explode('-', $jsonData->fecha2);
    $fechaADia = $fechaA[2];
    $fechaAMes = $fechaA[1];
    $fechaAAno = $fechaA[0];

    $pdf->SetXY(145, 45);
    $pdf->Cell(5, 4, 'A:', 0, 0, 'C');

    $pdf->SetXY(155, 41);
    $pdf->Cell(5, 4, 'D', 0, 0, 'C');

    $pdf->SetXY(155, 45);
    $pdf->Cell(5, 4, $fechaADia[0], 1, 0, 'C');

    $pdf->SetXY(160, 41);
    $pdf->Cell(5, 4, 'D', 0, 0, 'C');

    $pdf->SetXY(160, 45);
    $pdf->Cell(5, 4, $fechaADia[1], 1, 0, 'C');

    $pdf->SetXY(165, 41);
    $pdf->Cell(5, 4, 'M', 0, 0, 'C');

    $pdf->SetXY(165, 45);
    $pdf->Cell(5, 4, $fechaAMes[0], 1, 0, 'C');

    $pdf->SetXY(170, 41);
    $pdf->Cell(5, 4, 'M', 0, 0, 'C');

    $pdf->SetXY(170, 45);
    $pdf->Cell(5, 4, $fechaAMes[1], 1, 0, 'C');

    $pdf->SetXY(175, 41);
    $pdf->Cell(5, 4, 'A', 0, 0, 'C');

    $pdf->SetXY(175, 45);
    $pdf->Cell(5, 4, $fechaAAno[2], 1, 0, 'C');

    $pdf->SetXY(180, 41);
    $pdf->Cell(5, 4, 'A', 0, 0, 'C');

    $pdf->SetXY(180, 45);
    $pdf->Cell(5, 4, $fechaAAno[3], 1, 0, 'C');

    //  --------------------

    $pdf->SetXY(10, 58);
    $pdf->Cell(90, 30, '', 1, 0, 'L');

    $pdf->SetFont('Arial','',7);
    $pdf->SetXY(11, 59);
    $pdf->Cell(80, 4, '3. Nombre y Domicilio del Productor:', 0, 0, 'L');

    $pdf->SetFont('Arial','',9);
    $pdf->SetXY(11, 63);
    $pdf->MultiCell(88, 4, $jsonData->empresa, 0, 'L', 0);

    $pdf->SetXY(11, $pdf->GetY() + 1);
    $pdf->MultiCell(88, 4, $jsonData->domicilio, 0, 'L', 0);

    $pdf->SetXY(11, 83);
    $pdf->Cell(88, 4, 'Número de Registro Fiscal:' . $jsonData->registro_fiscal,0, 0, 'L');

    //  --------------------

    $pdf->SetXY(100, 58);
    $pdf->Cell(110, 30, '', 1, 0, 'L');

    $pdf->SetFont('Arial','',7);
    $pdf->SetXY(101, 59);
    $pdf->Cell(80, 4, '4. Nombre y Domicilio del Importador:', 0, 0, 'L');

    $pdf->SetFont('Arial','',9);
    $pdf->SetXY(101, 63);
    $pdf->Cell(105, 4, $jsonData->cliente, 0, 0, 'L');

    $pdf->SetXY(101, 68);
    $pdf->MultiCell(105, 4, $jsonData->cliente_domicilio, 0, 'L', 0);

    $pdf->SetXY(101, 83);
    $pdf->Cell(105, 4, 'Número de Registro Fiscal:' . $jsonData->cliente_no_reg_fiscal, 0, 0, 'L');

    //  --------------------

    $pdf->SetXY(10, 88);
    $pdf->Cell(90, 15, '', 1, 0, 'L');

    $pdf->SetXY(10, 90);
    $pdf->Cell(90, 4, '5. Descripción del (los) bien(es): LIMON MEXICANO', 0, 0, 'L');

    //  --------------------

    $pdf->SetXY(100, 88);
    $pdf->Cell(22, 15, '', 1, 0, 'L');

    $pdf->SetXY(100, 88);
    $pdf->MultiCell(22, 4, '6. Clasificación Arancelaria', 0, 'L', 0);

    $pdf->SetXY(122, 88);
    $pdf->Cell(22, 15, '', 1, 0, 'L');

    $pdf->SetXY(122, 88);
    $pdf->MultiCell(22, 4, '7. Criterio para trato preferencial', 0, 'L', 0);

    $pdf->SetXY(144, 88);
    $pdf->Cell(22, 15, '', 1, 0, 'L');

    $pdf->SetXY(144, 88);
    $pdf->MultiCell(22, 4, '8. Productor', 0, 'L', 0);

    $pdf->SetXY(166, 88);
    $pdf->Cell(22, 15, '', 1, 0, 'L');

    $pdf->SetXY(166, 88);
    $pdf->MultiCell(22, 4, '9. Costo Neto', 0, 'L', 0);

    $pdf->SetXY(188, 88);
    $pdf->Cell(22, 15, '', 1, 0, 'L');

    $pdf->SetXY(188, 88);
    $pdf->MultiCell(22, 4, '10. País de Origen', 0, 'L', 0);

    // -------------------

    $pdf->SetXY(10, 103);
    $pdf->Cell(90, 50, '', 1, 0, 'L');

    $pdf->SetXY(10, 103);
    $pdf->Cell(90, 4, '0', 0, 0, 'L');

    // -------------------

    $pdf->SetXY(100, 103);
    $pdf->Cell(22, 50, '', 1, 0, 'C');

    $pdf->SetXY(100, 103);
    $pdf->MultiCell(22, 4, '0805.50', 0, 'C', 0);

    $pdf->SetXY(122, 103);
    $pdf->Cell(22, 50, '', 1, 0, 'C');

    $pdf->SetXY(122, 103);
    $pdf->MultiCell(22, 4, 'A', 0, 'C', 0);

    $pdf->SetXY(144, 103);
    $pdf->Cell(22, 50, '', 1, 0, 'C');

    $pdf->SetXY(144, 103);
    $pdf->MultiCell(22, 4, 'YES', 0, 'C', 0);

    $pdf->SetXY(166, 103);
    $pdf->Cell(22, 50, '', 1, 0, 'C');

    $pdf->SetXY(166, 103);
    $pdf->MultiCell(22, 4, '-0-', 0, 'C', 0);

    $pdf->SetXY(188, 103);
    $pdf->Cell(22, 50, '', 1, 0, 'C');

    $pdf->SetXY(188, 103);
    $pdf->MultiCell(22, 4, 'MEXICO', 0, 'C', 0);

    // --------------------------

    $pdf->SetXY(10, 153);
    $pdf->Cell(200, 42, '', 1, 0, 'L');

    $txt = "11. Declaro bajo protesta de decir verdad que:
      La información contenida en este documento es verdadera y exacta, y me hago responsable de comprobar lo aquí declarado. Estoy consciente que seré responsable por cualquier declaración falsa u omisión hecha o relacionada con el presente documento.
      Me comprometo a conservar y presentar, en caso de ser requerido, los documentos necesarios que respalden el contenido del presente certificado, así como a notificar por escrito a todas las personas a quienes haya entregado el presente certificado, de cualquier cambio que pudiera afectar la exactitud o validez del mismo.
      Los bienes son originarios y cumplen con los requisitos que les son aplicables conforme al Tratado de Libre Comercio de América del Norte, y no han sido objeto de procesamiento ulterior o de cualquier otra operación fuera de los territorios de las Partes, salvo en los casos permitidos en el artículo 411 o en el Anexo 401:
      Este certificado se compone de 1 hojas, incluyendo todos sus anexos.";

    $pdf->SetXY(11, 154);
    $pdf->MultiCell(199, 4, $txt, 0, 'L', 0);

    // --------------------------

    $pdf->SetXY(10, 195);
    $pdf->Cell(90, 20, '', 1, 0, 'L');

    // $pdf->SetFont('Arial','',7);
    $pdf->SetXY(10, 195);
    $pdf->Cell(90, 4, 'Firma Autorizada:', 0, 0, 'L');

    $pdf->SetXY(100, 195);
    $pdf->Cell(110, 20, '', 1, 0, 'L');

    $pdf->SetXY(100, 195);
    $pdf->MultiCell(110, 4, 'Empresa: ' . strtoupper($jsonData->empresa), 0, 'L', 0);

    // --------------------------

    $pdf->SetXY(10, 215);
    $pdf->Cell(90, 20, '', 1, 0, 'L');

    $pdf->SetXY(10, 215);
    $pdf->Cell(90, 4, 'Nombre: RAUL  JORGE  GOMEZ TERRONES', 0, 0, 'L');

    $pdf->SetXY(100, 215);
    $pdf->Cell(110, 20, '', 1, 0, 'L');

    $pdf->SetXY(100, 215);
    $pdf->Cell(110, 4, 'Cargo:   REPRESENTANTE  LEGAL', 0, 0, 'L');

    // --------------------------

    $pdf->SetXY(10, 235);
    $pdf->Cell(90, 20, '', 1, 0, 'L');

    $pdf->SetXY(10, 240);
    $pdf->Cell(15, 4, 'Fecha:', 0, 0, 'C');

    $pdf->SetXY(25, 240);
    $pdf->Cell(5, 4, 'D', 0, 0, 'C');

    $pdf->SetXY(25, 244);
    $pdf->Cell(5, 4, $fechaDeDia[0], 1, 0, 'C');

    $pdf->SetXY(30, 240);
    $pdf->Cell(5, 4, 'D', 0, 0, 'C');

    $pdf->SetXY(30, 244);
    $pdf->Cell(5, 4, $fechaDeDia[1], 1, 0, 'C');

    $pdf->SetXY(35, 240);
    $pdf->Cell(5, 4, 'M', 0, 0, 'C');

    $pdf->SetXY(35, 244);
    $pdf->Cell(5, 4, $fechaDeMes[0], 1, 0, 'C');

    $pdf->SetXY(40, 240);
    $pdf->Cell(5, 4, 'M', 0, 0, 'C');

    $pdf->SetXY(40, 244);
    $pdf->Cell(5, 4, $fechaDeMes[1], 1, 0, 'C');

    $pdf->SetXY(45, 240);
    $pdf->Cell(5, 4, 'A', 0, 0, 'C');

    $pdf->SetXY(45, 244);
    $pdf->Cell(5, 4, $fechaDeAno[2], 1, 0, 'C');

    $pdf->SetXY(50, 240);
    $pdf->Cell(5, 4, 'A', 0, 0, 'C');

    $pdf->SetXY(50, 244);
    $pdf->Cell(5, 4, $fechaDeAno[3], 1, 0, 'C');

    // ------------------------------

    $pdf->SetXY(100, 235);
    $pdf->Cell(110, 20, '', 1, 0, 'L');

    $pdf->SetXY(105, 243);
    $pdf->Cell(50, 4, 'Teléfono: ' . $jsonData->telefono, 0, 0, 'L');

    $pdf->SetXY(155, 243);
    $pdf->Cell(50, 4, 'Fax: ' . $jsonData->fax, 0, 0, 'L');

    return array('pdf' => $pdf, 'texto' => 'CERTIFICADO DE TLC.pdf');
  }

  /**
   * Contruye el PDF Certificado TLC.
   *
   * @param  string $idFactura
   * @param  string $idDocumento
   * @return void
   */
  public function pdfManifiestoDelCamion($idFactura, $idDocumento)
  {
    $jsonData = $this->getJsonDataDocus($idFactura, $idDocumento);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    $pdf->show_head = false;

    $pdf->AliasNbPages();
    $pdf->AddPage();
    // $pdf->SetFont('helvetica','', 8);

    $pdf->SetXY(60, 3);
    $pdf->Image(APPPATH.'images/logo_mayer_martinez.jpg');

    // LADO IZQUIERDO

    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial','B',7);

    $pdf->SetXY(10, 22);
    $pdf->Cell(100, 4, "Remitente: " . strtoupper($jsonData->remitente), 0, 0, 'L');

    $pdf->SetXY(10, $pdf->GetY() + 4);
    $pdf->MultiCell(100, 4, "Consignatorio: " . strtoupper($jsonData->consignatorio), 0, 'L', 0);

    $fecha = explode('-', $jsonData->fecha_embarque);

    $pdf->SetXY(10, $pdf->GetY());
    $pdf->Cell(100, 4, "Fecha de Embarque: " . $fecha[2] .' DE ' . strtoupper(MyString::mes($fecha[1])) . ' DEL ' . $fecha[0], 0, 0, 'L');

    // -------------------------

    $pdf->SetXY(110, $pdf->GetY() - 8);
    $pdf->Cell(100, 4, "Camion Placas No: " . $jsonData->camion_placas, 0, 0, 'L');

    $pdf->SetXY(110, $pdf->GetY() + 4);
    $pdf->Cell(100, 4, "Caja No: " . $jsonData->caja_no, 0, 0, 'L');

    $pdf->SetXY(110, $pdf->GetY() + 4);
    $pdf->Cell(100, 4, "Linea de Transporte: " . $jsonData->linea_transporte, 0, 0, 'L');

    // ------------------------

    $pdf->SetFillColor(184, 78, 78);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);

    $pdf->SetXY(10, 35);
    $pdf->Cell(95, 4, "TOMATES", 1, 0, 'C', 1);

    $pdf->SetXY(10, $pdf->GetY() + 4);
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetAligns(array('C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(40, 20, 20, 15));
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Row(array('', 'Flats', '3 Tandas', 'Cartons'), true);

    $tomates = array('Max/Large 44A', 'Ex. Lg./45A', 'Ex. Large 55A', 'Large/Ex. Lg. 56A',
      '', 'Madium/Large', 'Medium/Small', 'Sm./Ex. Sm.', 'Ex./Sm.', 'Tomatillo', 'TOTAL:');

    $pdf->SetAligns(array('L'));
    $pdf->SetFont('Arial', '', 7);
    foreach ($tomates as $t)
    {
      $pdf->SetX(10);
      $pdf->Row(array(
        $t,
        '',
        '',
        '',
      ), false);
    }

    $pdf->SetX(10);
    $pdf->Cell(95, 6, "TOMATE CHERRY                         TOMATE ROMA", 1, 0, 'C', 1);

    $pdf->SetXY(10, $pdf->GetY() + 6);
    $pdf->SetFillColor(184, 78, 78);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(95, 4, "PEPINOS", 1, 0, 'C', 1);

    $pdf->SetXY(10, $pdf->GetY() + 4);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);

    $pepinos = array(
      array('Super select', 'Total jabas'),
      array('Super', 'Select'),
      array('Large', 'Sups/small'),
      array('Cartons 24s', '30s'),
    );

    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(48, 47));
    $pdf->SetFont('Arial', '', 7);
    foreach ($pepinos as $p)
    {
      $pdf->SetX(10);
      $pdf->Row(array(
        $p[0],
        $p[1],
      ), false);
    }

    $pdf->SetXY(10, $pdf->GetY());
    $pdf->SetFillColor(184, 78, 78);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(95, 4, "BERENJENA", 1, 0, 'C', 1);

    $pdf->SetFont('Arial', '', 7);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetXY(10, $pdf->GetY() + 4);
    $pdf->Cell(35, 6, "16s", 1, 0, 'L', 1);

    $pdf->SetX(45);
    $pdf->Cell(35, 6, "24s", 1, 0, 'L', 1);

    $pdf->SetXY(10, $pdf->GetY() + 6);
    $pdf->Cell(35, 6, "18s", 1, 0, 'L', 1);

    $pdf->SetX(45);
    $pdf->Cell(35, 6, "32s", 1, 0, 'L', 1);

    $pdf->SetXY(80, $pdf->GetY() - 6);
    $pdf->Cell(25, 12, "Jabas", 1, 0, 'C', 1);

    $pdf->SetXY(10, $pdf->GetY() + 12);
    $pdf->SetFillColor(184, 78, 78);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(95, 4, "MELON", 1, 0, 'C', 1);

    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetXY(10, $pdf->GetY() + 4);
    $pdf->Cell(60, 6, "Cantaloupe", 1, 0, 'L', 1);

    $pdf->SetX(70);
    $pdf->Cell(35, 6, "Honey Dew", 1, 0, 'L', 1);

    $melones = array(
      array('18s', '9s', '8s'),
      array('23s', '12s', '9s'),
      array('27s', '15s', '10s'),
      array('36s', '18s', ''),
      array('45s', '23s', '8s'),
      array('56s', '30s', '9s'),
      array('64s', '36s', '10s'),
      array('72s', '42s', ''),
      array('JABAS', 'CTNS.', 'TOTAL'),
    );

    $pdf->SetAligns(array('L', 'L', 'L'));
    $pdf->SetWidths(array(32, 28, 35));
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetY($pdf->GetY() + 6);
    foreach ($melones as $m)
    {
      $pdf->SetX(10);
      $pdf->Row(array(
        $m[0],
        $m[1],
        $m[2],
      ), false);
    }

    // LADO DERECHO

    $pdf->SetXY(110, 35);
    $pdf->SetFillColor(184, 78, 78);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(95, 4, "CHILES", 1, 0, 'C', 1);

    $pdf->SetFont('Arial', '', 7);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetXY(110, $pdf->GetY() + 4);
    $pdf->Cell(60, 6, "Bells", 1, 0, 'L', 1);

    $pdf->SetXY(110, $pdf->GetY() + 6);
    $pdf->Cell(60, 6, "X-Large", 1, 0, 'L', 1);

    $pdf->SetXY(110, $pdf->GetY() + 6);
    $pdf->Cell(60, 6, "Large", 1, 0, 'L', 1);

    $pdf->SetXY(110, $pdf->GetY() + 6);
    $pdf->Cell(60, 6, "Md.", 1, 0, 'L', 1);

    $pdf->SetXY(110, $pdf->GetY() + 6);
    $pdf->Cell(60, 6, "Sm.", 1, 0, 'L', 1);

    $pdf->SetXY(170, $pdf->GetY() - 24);
    $pdf->Cell(35, 30, "TOTAL JABAS", 1, 0, 'C', 1);

    $pdf->SetXY(110, $pdf->GetY() + 30);
    $pdf->Cell(95, 6, "TOTAL:", 1, 0, 'L', 1);

    $pdf->SetXY(110, $pdf->GetY() + 6);
    $pdf->Cell(95, 6, "Anaheim", 1, 0, 'L', 1);

    $pdf->SetXY(110, $pdf->GetY() + 6);
    $pdf->Cell(95, 6, "Caribe", 1, 0, 'L', 1);

    $pdf->SetXY(110, $pdf->GetY() + 6);
    $pdf->Cell(95, 6, "Fresno", 1, 0, 'L', 1);

    $pdf->SetXY(110, $pdf->GetY() + 6);
    $pdf->Cell(95, 6, "Jalapeño", 1, 0, 'L', 1);

    $pdf->SetXY(110, $pdf->GetY() + 6);
    $pdf->Cell(95, 6, "Pimiento", 1, 0, 'L', 1);

    $pdf->SetXY(110, $pdf->GetY() + 6);
    $pdf->SetAligns(array('C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(25, 25, 25, 20));
    $pdf->Row(array('', 'Jabas', 'Cajas', 'Canastos'), false);

    $pdf->SetX(110);
    $pdf->Row(array('Ejote K/L', '', '', ''), false);

    $pdf->SetX(110);
    $pdf->Row(array('Ejote Val.', '', '', ''), false);

    $pdf->SetX(110);
    $pdf->Row(array('Ejote Bush', '', '', ''), false);

    $pdf->SetXY(110, $pdf->GetY());
    $pdf->SetFillColor(184, 78, 78);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(95, 4, "CALABAZAS", 1, 0, 'C', 1);

    $pdf->SetFont('Arial', '', 7);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetXY(110, $pdf->GetY() + 4);
    $pdf->SetAligns(array('C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(25, 15, 15, 15));
    $pdf->Row(array('', 'X-Fancy', 'Fancy', 'Large'), false);

    $pdf->SetX(110);
    $pdf->Row(array('Italiana', '', '', ''), false);

    $pdf->SetX(110);
    $pdf->Row(array('Summer', '', '', ''), false);

    $pdf->SetX(110);
    $pdf->Row(array('Amarilla', '', '', ''), false);

    $pdf->SetX(110);
    $pdf->Row(array('Calabaza China', '', '', ''), false);

    $pdf->SetXY(180, $pdf->GetY() - 27);
    $pdf->Cell(25, 27, "TOTAL CAJAS", 1, 0, 'C', 1);

    // Obtenemos las clasificaciones de los pallets que se seleccionaron
    // para el cliente.

    $idEmbarque = $this->db
      ->select('id_embarque')
      ->from('facturacion_doc_embarque')
      ->where('id_documento', 2)
      ->where('id_factura', $idFactura)
      ->get()->row()->id_embarque;

    $clasificaciones = $this->getEmbarqueClasifi($idEmbarque, $idFactura);

    $pdf->SetXY(110, $pdf->GetY() + 27);
    $pdf->SetFillColor(184, 78, 78);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(95, 4, "LIMONES", 1, 0, 'C', 1);

    $pdf->SetFont('Arial', '', 7);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetXY(110, $pdf->GetY() + 4);
    $pdf->SetAligns(array('C', 'C', 'C'));
    $pdf->SetWidths(array(32, 32, 31));
    $pdf->Row(array('', 'Cajas', ''), false);

    foreach ($clasificaciones['clasificaciones'] as $clas)
    {
      $pdf->SetX(110);
      $pdf->Row(array($clas->clasificacion, $clas->cajas, ''), false);
    }

    $pdf->SetXY(110, $pdf->GetY());
    $pdf->SetFillColor(184, 78, 78);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->Cell(95, 4, "UVAS", 1, 0, 'C', 1);

    $pdf->SetFont('Arial', '', 7);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetXY(110, $pdf->GetY() + 4);
    $pdf->SetAligns(array('C', 'C'));
    $pdf->SetWidths(array(40, 55));
    $pdf->Row(array('', 'Cajas'), false);

    $pdf->SetX(110);
    $pdf->Row(array('Perlette', ''), false);

    $pdf->SetX(110);
    $pdf->Row(array('Flame', ''), false);

    $pdf->SetX(110);
    $pdf->Row(array('Cardinal', ''), false);

    $pdf->SetX(110);
    $pdf->Row(array('Thompson', ''), false);

    $pdf->SetFont('Arial', 'B', 10);

    $pdf->SetXY(10, 215);
    $pdf->Cell(95, 4, "Datos Adicionales al Transportista", 0, 0, 'C', 1);

    $pdf->SetFont('Arial', '', 10);
    $pdf->SetXY(10, $pdf->GetY() + 4);
    $pdf->Cell(95, 4, "Nombre o Razon Social: " . $jsonData->linea_transporte , 0, 0, 'L', 1);

    $pdf->SetXY(10, $pdf->GetY() + 4);
    $pdf->MultiCell(95, 4, "Domicilio Fiscal: " . $jsonData->domicilio_fiscal , 0, 'L', 0);

    $pdf->SetXY(10, $pdf->GetY());
    $pdf->Cell(95, 4, "RFC: " . $jsonData->rfc , 0, 0, 'L', 1);

    $pdf->SetXY(10, $pdf->GetY() + 4);
    $pdf->Cell(95, 4, "CURP: " . $jsonData->curp , 0, 0, 'L', 1);

    $pdf->SetXY(115, 216);
    $pdf->Image(APPPATH.'images/logo_mayer_martinez_pie.jpg');

    return array('pdf' => $pdf, 'texto' => 'MANIFIESTO DEL CAMION.pdf');
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */
