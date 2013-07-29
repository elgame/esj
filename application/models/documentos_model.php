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
  private function updateDocumento(Array $data, $idFactura, $idDocumento)
  {
    // Convierte los datos del documento a json.
    $json = json_encode($data);

    $data = array(
      'data'   => $json,
      'status' => 't'
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
  public function getClienteDocs($idFactura)
  {
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
       WHERE fd.id_factura = {$idFactura} AND rd.status = true
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

        // Si el chofer cuenta con la licencia o ife.
        if ($data['chofer']['info']->url_licencia !== null || $data['chofer']['info']->url_ife !== null)
        {
          $this->load->model('facturacion_model');

          // Obtiene la informacion de la factura.
          $factura = $this->facturacion_model->getInfoFactura($idFactura);

          // Obtiene la ruta donde se guardan los documentos del cliente.
          $path = $this->creaDirectorioDocsCliente($factura['info']->cliente->nombre_fiscal, $factura['info']->folio);

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
    $idEmbarque = $this->db->insert_id();

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
          'temperatura' => $_POST['pid_pallet'][$key] !== '' ? $_POST['ptemperatura'][$key] : null,
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
    $path = $this->creaDirectorioDocsCliente($factura['info']->cliente->nombre_fiscal, $factura['info']->folio);

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

    $data = array();
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
                string_agg(clasi.nombre::text, ', '::text) AS clasificaciones
         FROM facturacion_doc_embarque_pallets fep
         LEFT JOIN rastria_pallets rp ON rp.id_pallet = fep.id_pallet
         LEFT JOIN (
            SELECT rpr.id_pallet, cl.nombre
            FROM rastria_pallets_rendimiento rpr
            INNER JOIN clasificaciones cl ON rpr.id_clasificacion = cl.id_clasificacion
            GROUP BY rpr.id_pallet, rpr.id_clasificacion, cl.nombre
            ORDER BY rpr.id_pallet
         ) AS clasi ON clasi.id_pallet = fep.id_pallet
         WHERE id_embarque = {$data['info'][0]->id_embarque}
         GROUP BY fep.no_posicion, fep.id_pallet, fep.id_pallet, fep.marca, fep.descripcion, fep.temperatura, rp.no_cajas
         ORDER BY fep.no_posicion ASC"
      );

      if ($sql->num_rows() > 0)
        $data['pallets'] = $sql->result();
    }

    return $data;
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
  public function creaDirectorioDocsCliente($clienteNombre, $folioFactura)
  {
    $path = APPPATH.'documentos/CLIENTES/';

    if ( ! file_exists($path))
    {
      // echo $path.'<br>';
      mkdir($path, 0777);
    }

    $path .= strtoupper($clienteNombre).'/';
    if ( ! file_exists($path))
    {
      // echo $path;
      mkdir($path, 0777);
    }

    $path .= date('Y').'/';
    if ( ! file_exists($path))
    {
      // echo $path;
      mkdir($path, 0777);
    }

    $path .= $this->mesToString(date('m')).'/';
    if ( ! file_exists($path))
    {
      // echo $path;
      mkdir($path, 0777);
    }

    $path .= 'FACT-'.$folioFactura.'/';
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
    $path = $this->creaDirectorioDocsCliente($factura['info']->cliente->nombre_fiscal, $factura['info']->folio);

    // Llama el metodo que ejecuta la funcion dependiendo del documento que se
    // esta actualizando y los guarda en disco.
    $this->generaDoc($idFactura, $idDocumento, $path);

    return array('passes' => true);
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
  private function getManifiestoChoferData($idFactura, $idDocumento)
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
   * Visualiza el PDF del Manifiesto Chofer.
   *
   * @param  string $idFactura
   * @param  string $idDocumento
   * @return void
   */
  public function pdfManifiestoDelChofer($idFactura, $idDocumento)
  {
    $data = $this->getManifiestoChoferData($idFactura, $idDocumento);

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
    $pdf->Cell(80, 6, 'FOLIO FACTURA :' . $data->folio, 0, 0, 'C');

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
    $pdf->Cell(35, 6, '', 1, 0, 'C', 1);

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
    $pdf->Cell(70, 6, 'TECOMAN, COL A ' . $fecha[2] . ' ' . strtoupper(String::mes($fecha[1])) . ' ' . $fecha[0], 0, 0, 'C', 1);

    $chofer = strtoupper(str_replace(" ", "_", $data->chofer));
    $fecha = str_replace(" ", "_", $data->fecha);

    return array('pdf' => $pdf, 'texto' => 'MANIFIESTO_CHOFER_'.$chofer.'_'.$fecha.'.pdf');
  }

  public function pdfAcomodoDelEmbarque($idFactura, $idDocumento)
  {
    $data = $this->getEmbarqueData($idFactura, $idDocumento);

    $result = $this->db
      ->select("data")
      ->from("facturacion_documentos")
      ->where('id_factura', $idFactura)
      ->where('id_documento', $idDocumento)
      ->get()->row()->data;

    $jsonData = json_decode($result);

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
    $pdf->SetFillColor(34, 34, 34);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(40, 6, 'CTRL. DE EMBARQUE', 1, 0, 'C', 1);

    $pdf->SetXY(167, 16);
    $pdf->Cell(40, 6, 'FECHA', 1, 0, 'C', 1);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetXY(167, 8);
    $pdf->Cell(40, 6, $data['info'][0]->ctrl_embarque, 1, 0, 'C', 1);

    $pdf->SetXY(167, 22);
    $pdf->Cell(40, 6, $jsonData->fecha, 1, 0, 'C', 1);

    $pdf->SetXY(7, 33);
    $pdf->SetFillColor(34, 34, 34);
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
            $txtTrack1 = $pallet->cajas;
          else
            $txtTrack1 = $pallet->descripcion;
        }

        if ($pallet->no_posicion == $i+1)
        {
          if ($pallet->id_pallet != null)
            $txtTrack2 = $pallet->cajas;
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

    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(50, 52);
    $pdf->SetFont('Arial','B',9);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(10, 30, 72, 15, 30));
    $pdf->Row(array('#', 'MARCA', 'CLASIFICACION', 'CAJAS', 'TEMPERATURA'), true);

    $pdf->SetFont('Arial','',9);
    for ($i = 1; $i < 25 ; $i++)
    {
      $marca         = '';
      $clasificacion = '';
      $cajas         = '';
      $temperatura   = '';

        foreach ($data['pallets'] as $key => $pallet)
        {
          if ($pallet->no_posicion == $i)
          {
            $marca         = $pallet->id_pallet != null ? $pallet->marca : $pallet->descripcion;
            $clasificacion = $pallet->clasificaciones;
            $cajas         = $pallet->cajas;
            $temperatura   = $pallet->temperatura;
            break;
          }
        }

      $pdf->SetX(50);
        $pdf->Row(array(
          $i,
          $marca,
          $clasificacion,
          $cajas,
          $temperatura,
        ), false);
    }

    $pdf->SetFont('Arial','B',8);
    $y = $pdf->GetY();

    $pdf->SetXY(50, $y + 6);
    $pdf->Cell(50, 6, 'FECHA DE CARGA: ' . $data['info'][0]->fecha_carga, 0, 0, 'L', 1);

    $pdf->SetXY(50, $y + 13);
    $pdf->Cell(50, 6, 'INICIO: ' . $jsonData->inicio, 0, 0, 'L', 1);

    $pdf->SetXY(105, $y + 13);
    $pdf->Cell(50, 6, 'TERMINO: ' . $jsonData->termino, 0, 0, 'L', 1);

    $pdf->SetXY(50, $y + 20);
    $pdf->Cell(105, 6, 'FECHA DE EMPAQUE: ' . $data['info'][0]->fecha_embarque, 0, 0, 'L', 1);

    $pdf->SetXY(50, $y + 27);
    $pdf->Cell(105, 6, 'ELABORO: ' . strtoupper($jsonData->elaboro), 0, 0, 'L', 1);

    $pdf->SetXY(50, $y + 34);
    $pdf->Cell(105, 6, 'DESTINO: ' . strtoupper($jsonData->destino), 0, 0, 'L', 1);

    $pdf->SetXY(50, $y + 41);
    $pdf->Cell(157, 6, 'DESTINATARIO: ' . strtoupper($jsonData->destinatario), 0, 0, 'L', 1);

    return array('pdf' => $pdf, 'texto' => 'DATOS_EMBARQUE.pdf');
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */