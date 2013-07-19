<?php
class facturacion_model extends privilegios_model{

	function __construct(){
		parent::__construct();
	}

  /*
   |-------------------------------------------------------------------------
   |  FACTURACION
   |-------------------------------------------------------------------------
   */

	/**
	 * Obtiene el listado de facturas
   *
   * @return
	 */
	public function getFacturas($perpage = '40', $sql2='')
  {
		$sql = '';
		//paginacion
		$params = array(
				'result_items_per_page' => $perpage,
				'result_page' 			=> (isset($_GET['pag'])? $_GET['pag']: 0)
		);
		if($params['result_page'] % $params['result_items_per_page'] == 0)
			$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

		//Filtros para buscar
		if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '')
			$sql = " AND Date(f.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
		elseif($this->input->get('ffecha1') != '')
			$sql = " AND Date(f.fecha) = '".$this->input->get('ffecha1')."'";
		elseif($this->input->get('ffecha2') != '')
			$sql = " AND Date(f.fecha) = '".$this->input->get('ffecha2')."'";

		// if($this->input->get('fserie') != '')
		// 	$sql .= " AND c.serie = '".$this->input->get('fserie')."'";
		if($this->input->get('ffolio') != '')
			$sql .= " AND f.folio = '".$this->input->get('ffolio')."'";
		if($this->input->get('fstatus') != '')
			$sql .= " AND f.status = '".$this->input->get('fstatus')."'";
		if($this->input->get('fid_cliente') != '')
			$sql .= " AND f.id_cliente = '".$this->input->get('fid_cliente')."'";
    if($this->input->get('did_empresa') != '')
      $sql .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";

		$query = BDUtil::pagination("
				SELECT f.id_factura, Date(f.fecha) AS fecha, f.serie, f.folio, c.nombre_fiscal,
                e.nombre_fiscal as empresa, f.condicion_pago, forma_pago,  f.status, f.total
				FROM facturas AS f
        INNER JOIN empresas AS e ON e.id_empresa = f.id_empresa
        INNER JOIN clientes AS c ON c.id_cliente = f.id_cliente
				WHERE 1 = 1".$sql.$sql2."
				ORDER BY (Date(f.fecha)) DESC
				", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'fact' => array(),
				'total_rows' 		=> $query['total_rows'],
				'items_per_page' 	=> $params['result_items_per_page'],
				'result_page' 		=> $params['result_page']
		);
		if($res->num_rows() > 0)
			$response['fact'] = $res->result();

		return $response;
	}

	/**
	 * Obtiene la informacion de una factura
	 */
	public function getInfoFactura($id, $info_basic=false)
  {
		$res = $this->db->select("*")->
                      from('facturas')->
                      where("id_factura = '".$id."'")->get();

    if($res->num_rows() > 0)
    {
			$response['info'] = $res->row();
			$response['info']->fecha = substr($response['info']->fecha, 0, 10);
			$res->free_result();
			if($info_basic)
				return $response;

			$this->load->model('clientes_model');
			$prov = $this->clientes_model->getInfoCliente($response['info']->id_cliente, true);
			$response['info']->cliente = $prov['info'];

      $res = $this->db->select('fp.id_fac_prod, fp.id_factura, fp.id_producto, fp.descripcion, fp.taza_iva, fp.cantidad, fp.precio_unitario,
                                fp.importe, fp.importe_iva, fp.total, fp.descuento, fp.retencion, pu.abreviatura as unidad, fp.unidad as unidad2')->
                        from('facturas_productos as fp')->
                        join('productos as p', 'p.id_producto = fp.id_producto', 'left')->
                        join('productos_unidades as pu', 'pu.id_unidad = p.id_unidad', 'left')->
                        where('id_factura = '.$id)->get();

      $response['productos'] = $res->result();

			return $response;
		}
    else
			return false;
	}

	/**
	 * Obtiene el folio de acuerdo a la serie seleccionada
	 */
	public function getFolioSerie($serie, $empresa)
  {
		$res = $this->db->select('folio')->
                      from('facturacion')->
                      where("serie = '".$serie."' AND id_empresa = ".$empresa)->
                      order_by('folio', 'DESC')->
                      limit(1)->get()->row();

		$folio = (isset($res->folio)? $res->folio: 0)+1;

		$res = $this->db->select('*')->
                      from('facturacion_series_folios')->
                      where("serie = '".$serie."' AND id_empresa = ".$empresa)->
                      limit(1)->get()->row();

		if(is_object($res)){
			if($folio < $res->folio_inicio)
				$folio = $res->folio_inicio;

			$res->folio = $folio;
			$msg = 'ok';

			if($folio > $res->folio_fin || $folio < $res->folio_inicio)
				$msg = "El folio ".$folio." está fuera del rango de folios para la serie ".$serie.". <br>
					Verifique las configuraciones para asignar un nuevo rango de folios";
		}else
			$msg = 'La serie no existe.';

		return array($res, $msg);
	}

  /**
   * Obtiene el folio de acuerdo a la serie seleccionada
   */
  public function get_series_empresa($ide)
  {
    $query = $this->db->select('id_serie_folio, id_empresa, serie, leyenda')->
                      from('facturacion_series_folios')->
                      where("id_empresa = ".$ide."")->
                      order_by('serie', 'ASC')->get();

    $res = array();
    if($query->num_rows() > 0)
    {
      $res = $query->result();
      $msg = 'ok';
    } else
      $msg = 'La empresa seleccionada no cuenta con Series y Folios.';

    return array($res, $msg);
  }

  /**
   * Inicializa los datos que serviran para generar la cadena original
   *
   * @return array
   */
  private function datosCadenaOriginal()
  {
    $anoAprobacion = explode('-', $_POST['dano_aprobacion']);

    // Obtiene la forma de pago, si es en parcialidades entonces la forma de
    // pago son las parcialidades "Parcialidad 1 de X".
    $formaPago = ($_POST['dforma_pago'] == 'Pago en parcialidades') ? $this->input->post('dforma_pago_parcialidad') : 'Pago en una sola exhibición';

    // Si el metodo de pago no es en "efectivo" entonces obtiene los digitos.
    $noCtaPago = '';
    if($_POST['dmetodo_pago'] !== 'efectivo')
    {
      if($_POST['dmetodo_pago_digitos'] !== '' || $_POST['dmetodo_pago_digitos'] === 'No identificado')
      {
        $noCtaPago =  $this->input->post('dmetodo_pago_digitos');
      }
    }

    // Obtiene los datos del receptor.
    $cliente = $this->clientes_model->getClienteInfo($_POST['did_cliente'], true);

    // Array con los datos necesarios para generar la cadena original.
    $data = array(
      'id_empresa'        => $this->input->post('did_empresa'),
      'version'           => $this->input->post('dversion'),
      'serie'             => $this->input->post('dserie'),
      'folio'             => $this->input->post('dfolio'),
      'fecha'             => $this->input->post('dfecha').date(':s'),
      'noAprobacion'      => $this->input->post('dno_aprobacion'),
      'anoAprobacion'     => $anoAprobacion[0],
      'tipoDeComprobante' => $this->input->post('dtipo_comprobante'),
      'formaDePago'       => $formaPago, //$this->input->post('dforma_pago'),
      'condicionesDePago' => $this->input->post('dcondicion_pago'),
      'subTotal'          => $this->input->post('total_subtotal'), //total_importe
      'total'             => $this->input->post('total_totfac'),
      'metodoDePago'      => $this->input->post('dmetodo_pago'),
      'NumCtaPago'        => $noCtaPago,

      'rfc'               => $cliente['info']->rfc,
      'nombre'            => $cliente['info']->nombre_fiscal,
      'calle'             => $cliente['info']->calle,
      'noExterior'        => $cliente['info']->no_exterior,
      'noInterior'        => $cliente['info']->no_interior,
      'colonia'           => $cliente['info']->colonia,
      'localidad'         => $cliente['info']->localidad,
      'municipio'         => $cliente['info']->municipio,
      'estado'            => $cliente['info']->estado,
      'pais'              => 'MEXICO',
      'codigoPostal'      => $cliente['info']->cp,

      'concepto'          => array(),

      'retencion'         => array(),
      'totalImpuestosRetenidos' => 0,

      'traslado'          => array(),
      'totalImpuestosTrasladados' => 0
    );

    return $data;
  }

	/**
	 * Agrega una factura a la bd
	 */
	public function addFactura()
  {
    $this->load->library('cfdi');
    $this->load->model('clientes_model');

    $anoAprobacion = explode('-', $_POST['dano_aprobacion']);

    // Si el metodo de pago no es en "efectivo" entonces obtiene los digitos.
    $noCtaPago = '';
    if($_POST['dmetodo_pago'] !== 'efectivo')
    {
      if($_POST['dmetodo_pago_digitos'] !== '' || $_POST['dmetodo_pago_digitos'] === 'No identificado')
      {
        $noCtaPago =  $this->input->post('dmetodo_pago_digitos');
      }
    }

    // Obtiene la forma de pago, si es en parcialidades entonces la forma de
    // pago son las parcialidades "Parcialidad 1 de X".
    $formaPago = ($_POST['dforma_pago'] == 'Pago en parcialidades') ? $this->input->post('dforma_pago_parcialidad') : 'Pago en una sola exhibición';

    $datosFactura = array(
      'id_cliente'          => $this->input->post('did_cliente'),
      'id_empresa'          => $this->input->post('did_empresa'),
      'version'             => $this->input->post('dversion'),
      'serie'               => $this->input->post('dserie'),
      'folio'               => $this->input->post('dfolio'),
      'fecha'               => str_replace('T', ' ', $_POST['dfecha']),
      'subtotal'            => $this->input->post('total_subtotal'),
      'importe_iva'         => $this->input->post('total_iva'),
      'total'               => $this->input->post('total_totfac'),
      'total_letra'         => $this->input->post('dttotal_letra'),
      'no_aprobacion'       => $this->input->post('dno_aprobacion'),
      'ano_aprobacion'      => $anoAprobacion[0],
      'tipo_comprobante'    => $this->input->post('dtipo_comprobante'),
      'forma_pago'          => $formaPago,
      'metodo_pago'         => $this->input->post('dmetodo_pago'),
      'metodo_pago_digitos' => $noCtaPago,
      'no_certificado'      => $this->input->post('dno_certificado'),
      'cadena_original'     => '',
      'sello'               => '',
      'certificado'         => '',
      'condicion_pago'      => $this->input->post('dcondicion_pago'),
      'plazo_credito'       => $_POST['dcondicion_pago'] === 'co' ? 0 : $this->input->post('dplazo_credito'),
      'observaciones'       => '',
      'status'              => $_POST['dcondicion_pago'] === 'co' ? 'pa' : 'p',
      'retencion_iva'       => $this->input->post('total_retiva'),
    );

    $this->db->insert('facturacion', $datosFactura);
    $idFactura = $this->db->insert_id();

    // Obtiene los datos para la cadena original
    $datosCadOrig = $this->datosCadenaOriginal();


    $dataCliente = array(
      'id_factura'  => $idFactura,
      'nombre'      => $datosCadOrig['nombre'],
      'rfc'         => $datosCadOrig['rfc'],
      'calle'       => $datosCadOrig['calle'],
      'no_exterior' => $datosCadOrig['noExterior'],
      'no_interior' => $datosCadOrig['noInterior'],
      'colonia'     => $datosCadOrig['colonia'],
      'localidad'   => $datosCadOrig['localidad'],
      'municipio'   => $datosCadOrig['municipio'],
      'estado'      => $datosCadOrig['estado'],
      'cp'          => $datosCadOrig['codigoPostal'],
      'pais'        => $datosCadOrig['pais'],
    );
    $this->db->insert('facturacion_cliente', $dataCliente);

    // Productos e Impuestos
    $productosCadOri    = array(); // Productos para la CadOriginal
    $productosFactura   = array(); // Productos para la Factura
    $impuestosRetencion = array(); // Retencion
    $impuestosTraslados = array(); // Traslados

    foreach ($_POST['prod_ddescripcion'] as $key => $descripcion)
    {
      if ($_POST['prod_importe'][$key] != 0)
      {
        $productosCadOri[] = array(
          'cantidad'      => $_POST['prod_dcantidad'][$key],
          'unidad'        => $_POST['prod_dmedida'][$key],
          'descripcion'   => $descripcion,
          'valorUnitario' => $_POST['prod_dpreciou'][$key],
          'importe'       => $_POST['prod_importe'][$key],
        );

        // if ($_POST['prod_dreten_iva_porcent'][$key] != 0)
        // {
        $impuestosRetencion[] = array(
          'impuesto' => 'IVA',
          'importe' => $_POST['prod_dreten_iva_total'][$key],
        );
        // }

        $impuestosTraslados[] = array(
          'Impuesto' => 'IVA',
          'tasa'     => $_POST['prod_diva_porcent'][$key],
          'importe'  => $_POST['prod_diva_total'][$key],
        );

        $productosFactura[] = array(
          'id_factura'       => $idFactura,
          'id_clasificacion' => $_POST['prod_did_prod'][$key] !== '' ? $_POST['prod_did_prod'][$key] : null,
          'num_row'          => intval($key),
          'cantidad'         => $_POST['prod_dcantidad'][$key],
          'descripcion'      => $descripcion,
          'precio_unitario'  => $_POST['prod_dpreciou'][$key],
          'importe'          => $_POST['prod_importe'][$key],
          'iva'              => $_POST['prod_diva_total'][$key],
          'unidad'           => $_POST['prod_dmedida'][$key],
          'retencion_iva'    => $_POST['prod_dreten_iva_total'][$key],
        );
      }
    }

    $datosCadOrig['concepto']  = $productosCadOri;
    $datosCadOrig['retencion'] = $impuestosRetencion;
    $datosCadOrig['traslado']  = $impuestosTraslados;
    $datosCadOrig['totalImpuestosRetenidos']   = $this->input->post('total_retiva');
    $datosCadOrig['totalImpuestosTrasladados'] = $this->input->post('total_iva');

    // Genera la cadena original y el sello
    $cadenaOriginal = $this->cfdi->obtenCadenaOriginal($datosCadOrig);
    $sello          = $this->cfdi->obtenSello($cadenaOriginal);

    $updateFactura = array(
      'cadena_original' => $cadenaOriginal,
      'sello'           => $sello,
      // 'certificado'     => ,
    );
    $this->db->update('facturacion', $updateFactura, array('id_factura' => $idFactura));

    $this->db->insert_batch('facturacion_productos', $productosFactura);

    echo "<pre>";
      var_dump($datosFactura, $cadenaOriginal, $sello, $productosFactura);
    echo "</pre>";exit;

		return array(true, $status, $id_factura);
	}

	/**
	 * Cancela una factura, la elimina
	 */
	public function cancelaFactura(){
		$this->db->update('facturas', array('status' => 'ca'), "id_factura = '".$_GET['id']."'");

		return array(true, '');
	}

  /**
   * Paga una factura
   */
  public function pagaFactura(){
    $this->db->update('facturas', array('status' => 'pa'), "id_factura = '".$_GET['id']."'");
    return array(true, '');
  }

	/**
	 * Actualiza los digitos del metodo de pago de una factura
	 */
	public function metodo_pago(){
		$this->db->update('facturas', array('metodo_pago_digitos' => $_POST['mp_digitos']), "id_factura = '".$_POST['id_factura']."'");
		return array(true, '');
	}

  /*
   |-------------------------------------------------------------------------
   |  ABONOS
   |-------------------------------------------------------------------------
   */

	/**
	 * Agrega abono a una factura
   *
	 * @param unknown_type $id_factura
	 * @param unknown_type $concepto
	 */
	public function addAbono($id_factura=null, $concepto=null, $registr_bancos=true)
  {
		$id_factura = $id_factura==null? $this->input->get('id'): $id_factura;
		$concepto = $concepto==null? $this->input->post('dconcepto'): $concepto;

		$data = $this->obtenTotalAbonosC($id_factura);
		if($data->abonos < $data->total){ //Evitar que se agreguen abonos si esta pagada
			$pagada = false;
			//compruebo si se pasa el abono al total de la factura y activa a pagado
			if(($this->input->post('dmonto')+$data->abonos) >= $data->total){
				if(($this->input->post('dmonto')+$data->abonos) > $data->total)
					$_POST['dmonto'] = $this->input->post('dmonto') - (($this->input->post('dmonto')+$data->abonos) - $data->total);
				$pagada = true;
			}

			$id_abono = BDUtil::getId();
			$data_abono = array(
					'id_abono' => $id_abono,
					'id_factura' => $id_factura,
					'fecha' => $this->input->post('dfecha'),
					'concepto' => $concepto,
					'total' => $this->input->post('dmonto')
			);
			$this->db->insert('facturas_abonos', $data_abono);

			if($pagada){
				$this->db->update('facturas', array('status' => 'pa'), "id_factura = '".$id_factura."'");
			}

			if($registr_bancos){
				//Registramos la Operacion en Bancos
				$this->load->model('banco_model');
				$respons = $this->banco_model->addOperacion($this->input->post('dcuenta'));
			}

			return array(true, 'Se agregó el abono correctamente.', $id_abono);
		}
		return array(true, 'La orden de trabajo ya esta pagada.', '');
	}

	/**
	 * Elimina abonos de cobranza (de una factura)
	 * @param unknown_type $id_abono
	 * @param unknown_type $id_factura
	 */
	public function deleteAbono($id_abono, $id_factura)
  {
		$this->db->delete('facturas_abonos', "id_abono = '".$id_abono."'");

		$data = $this->obtenTotalAbonosC($id_factura);
		if($data->abonos >= $data->total){ //si abonos es = a la factura se pone pagada
			$this->db->update('facturas', array('status' => 'pa'), "id_factura = '".$id_factura."'");
		}else{ //si abonos es menor se pone pendiente
			$this->db->update('facturas', array('status' => 'p'), "id_factura = '".$id_factura."'");
		}

		return array(true, '');
	}

	private function obtenTotalAbonosC($id)
  {
		$data = $this->db->query("
				SELECT
						c.total,
						COALESCE(ab.abonos, 0) AS abonos
				FROM facturas AS c
					LEFT JOIN (
						SELECT id_factura, Sum(total) AS abonos
						FROM facturas_abonos
						WHERE id_factura = '".$id."' AND tipo <> 'ca'
						GROUP BY id_factura
					) AS ab ON c.id_factura = ab.id_factura
				WHERE c.id_factura = '".$id."'", true);
		return $data->row();
	}

  /*
   |-------------------------------------------------------------------------
   |  SERIES Y FOLIOS
   |-------------------------------------------------------------------------
   */

	/**
	 * Obtiene el listado de series y folios para administrarlos
   *
   * @return array
	 */
	public function getSeriesFolios($per_pag='30')
  {
		//paginacion
		$params = array(
				'result_items_per_page' => $per_pag,
				'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
		);
		if($params['result_page'] % $params['result_items_per_page'] == 0)
			$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

		$sql = '';
		// if($this->input->get('fserie')!='')
		// 	$this->db->where('serie',$this->input->get('fserie'));

		$query = BDUtil::pagination("SELECT fsf.id_serie_folio, fsf.id_empresa, fsf.serie, fsf.no_aprobacion, fsf.folio_inicio,
					fsf.folio_fin, fsf.leyenda, fsf.leyenda1, fsf.leyenda2, fsf.ano_aprobacion, e.nombre_fiscal AS empresa
				FROM facturacion_series_folios AS fsf
					INNER JOIN empresas AS e ON e.id_empresa = fsf.id_empresa
				WHERE lower(serie) LIKE '".mb_strtolower($this->input->get('fserie'), 'UTF-8')."' ".$sql."
				ORDER BY fsf.serie", $params, true);

    $res = $this->db->query($query['query']);

		$data = array(
      'series'         => array(),
      'total_rows'     => $query['total_rows'],
      'items_per_page' => $params['result_items_per_page'],
      'result_page'    => $params['result_page']
		);

		if($res->num_rows() > 0)
			$data['series'] = $res->result();

		return $data;
	}

	/**
	 * Obtiene la informacion de una serie/folio
   *
	 * @param array
	 */
	public function getInfoSerieFolio($id_serie_folio = '')
  {
		$id_serie_folio = ($id_serie_folio != '') ? $id_serie_folio : $this->input->get('id');

		$res = $this->db->select('fsf.id_serie_folio, fsf.id_empresa, fsf.serie, fsf.no_aprobacion, fsf.folio_inicio,
				fsf.folio_fin, fsf.leyenda, fsf.leyenda1, fsf.leyenda2, fsf.ano_aprobacion, e.nombre_fiscal AS empresa')
			->from('facturacion_series_folios AS fsf')
				->join('empresas AS e', 'e.id_empresa = fsf.id_empresa', 'inner')
			->where('fsf.id_serie_folio', $id_serie_folio)->get()->result();
		return $res;
	}

	/**
	 * Agrega una serie/folio a la base de datos
   *
   * @return array
	 */
	public function addSerieFolio()
  {
    $data	= array(
			'id_empresa'     => $this->input->post('fid_empresa'),
			'serie'          => strtoupper($this->input->post('fserie')),
			'no_aprobacion'  => $this->input->post('fno_aprobacion'),
			'folio_inicio'   => $this->input->post('ffolio_inicio'),
			'folio_fin'      => $this->input->post('ffolio_fin'),
			'ano_aprobacion' => $this->input->post('fano_aprobacion'),
		);

		if($this->input->post('fleyenda') !== '')
			$data['leyenda'] = $this->input->post('fleyenda');

		if($this->input->post('fleyenda1') !== '')
			$data['leyenda1'] = $this->input->post('fleyenda1');

		if($this->input->post('fleyenda2') !== '')
			$data['leyenda2'] = $this->input->post('fleyenda2');

		$this->db->insert('facturacion_series_folios', $data);

		return array('passes' => true);
	}

	/**
	 * Modifica la informacion de un serie/folio.
	 *
   * @param string $id_serie_folio
   * @return array
	 */
	public function editSerieFolio($id_serie_folio = '')
  {
		$id_serie_folio = ($id_serie_folio != '') ? $id_serie_folio : $this->input->get('id');

		// $path_img = '';
		//valida la imagen
		// $upload_res = UploadFiles::uploadImgSerieFolio();

		// if(is_array($upload_res)){
		// 	if($upload_res[0] == false)
		// 		return array(false, $upload_res[1]);
		// 	$path_img = $upload_res[1]['file_name']; //APPPATH.'images/series_folios/'.$upload_res[1]['file_name'];

		// 	/*$old_img = $this->db->select('imagen')->from('facturas_series_folios')->where('id_serie_folio',$id_serie_folio)->get()->row()->imagen;

		// 	UploadFiles::deleteFile($old_img);*/
		// }

		$data	= array(
				'id_empresa'     => $this->input->post('fid_empresa'),
				'serie'          => strtoupper($this->input->post('fserie')),
				'no_aprobacion'  => $this->input->post('fno_aprobacion'),
				'folio_inicio'   => $this->input->post('ffolio_inicio'),
				'folio_fin'      => $this->input->post('ffolio_fin'),
				'ano_aprobacion' => $this->input->post('fano_aprobacion')
		);

		// if($path_img!='')
		// 	$data['imagen'] = $path_img;

		if($this->input->post('fleyenda')!='')
			$data['leyenda'] = $this->input->post('fleyenda');

		if($this->input->post('fleyenda1')!='')
			$data['leyenda1'] = $this->input->post('fleyenda1');

		if($this->input->post('fleyenda2')!='')
			$data['leyenda2'] = $this->input->post('fleyenda2');

		$this->db->update('facturacion_series_folios', $data, array('id_serie_folio'=>$id_serie_folio));

		return array('passes' => true);
	}

  /*
   |-------------------------------------------------------------------------
   |  HELPERS
   |-------------------------------------------------------------------------
   */

	public function exist($table, $sql, $return_res=false)
  {
		$res = $this->db->get_where($table, $sql);
		if($res->num_rows() > 0){
			if($return_res)
				return $res->row();
			return TRUE;
		}
		return FALSE;
	}

  /*
   |-------------------------------------------------------------------------
   |  AJAX
   |-------------------------------------------------------------------------
   */

  /**
   * Obtiene el listado de empresas para usar en peticiones Ajax.
   */
  public function getFacEmpresasAjax(){
    $sql = '';
    $res = $this->db->query("
        SELECT e.id_empresa, e.nombre_fiscal, e.cer_caduca, ef.version, e.cer_org
        FROM empresas AS e
        INNER JOIN empresas_fiscal AS ef ON ef.id_empresa = e.id_empresa
        WHERE lower(nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'
        ORDER BY nombre_fiscal ASC
        LIMIT 20");

    $this->load->library('cfdi');

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){

        if ($itm->cer_org !== '')
          $itm->no_certificado = $this->cfdi->obtenNoCertificado($itm->cer_org);

        $response[] = array(
            'id' => $itm->id_empresa,
            'label' => $itm->nombre_fiscal,
            'value' => $itm->nombre_fiscal,
            'item' => $itm,
        );
      }
    }

    return $response;
  }

  /*
   |-------------------------------------------------------------------------
   |  REPORTES
   |-------------------------------------------------------------------------
   */

  public function getRVP()
  {
    $sql = '';
    //Filtros para buscar
    if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '')
      $sql = " AND Date(f.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffecha1') != '')
      $sql = " AND Date(f.fecha) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffecha2') != '')
      $sql = " AND Date(f.fecha) = '".$this->input->get('ffecha2')."'";

    if ($this->input->get('dfamilia') != '')
      $sql .= " AND p.id_familia = " . $this->input->get('dfamilia');

    // var_dump($sql);exit;

    $query = $this->db->query("SELECT fp.id_producto, SUM(fp.cantidad) AS total_cantidad, SUM(fp.importe) AS total_importe, p.codigo, p.nombre as producto
                                FROM facturas_productos AS fp
                                INNER JOIN facturas AS f ON f.id_factura = fp.id_factura
                                INNER JOIN productos AS p ON p.id_producto = fp.id_producto
                                WHERE f.status != 'ca' $sql
                                GROUP BY fp.id_producto");

    return $query->result();

  }

   public function rvc_pdf()
   {
      $_GET['ffecha1'] = date("Y-m").'-01';
      $_GET['ffecha2'] = date("Y-m-d");

      $data = $this->getFacturas('10000');

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->show_head = true;
      $pdf->titulo2 = 'Reporte Ventas Cliente';


      if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
        $pdf->titulo3 = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      elseif (!empty($_GET['ffecha1']))
        $pdf->titulo3 = "Del ".$_GET['ffecha1'];
      elseif (!empty($_GET['ffecha2']))
        $pdf->titulo3 = "Del ".$_GET['ffecha2'];

      $pdf->AliasNbPages();
      // $links = array('', '', '', '');
      $pdf->SetY(30);
      $aligns = array('C', 'C', 'C', 'C','C', 'C', 'C', 'C');
      $widths = array(20, 25, 13, 51, 30, 25, 18, 22);
      $header = array('Fecha', 'Serie', 'Folio', 'Cliente', 'Empresa', 'Forma de pago', 'Estado', 'Total');
      $total = 0;

      foreach($data['fact'] as $key => $item)
      {
        $band_head = false;
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          $pdf->AddPage();

          $pdf->SetFont('Arial','B',8);
          $pdf->SetTextColor(255,255,255);
          $pdf->SetFillColor(160,160,160);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('Arial','',8);
        $pdf->SetTextColor(0,0,0);

        $estado = ($item->status === 'p') ? 'Pendiente' : (($item->status === 'pa') ? 'Pagada' : 'Cancelada');
        $condicion_pago = ($item->condicion_pago === 'co') ? 'Contado' : 'Credito';
        $datos = array($item->fecha, $item->serie, $item->folio, $item->nombre_fiscal, $item->empresa, $condicion_pago, $estado, String::formatoNumero($item->total));
        $total += floatval($item->total);

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false);
      }

      $pdf->SetX(6);
      $pdf->SetFont('Arial','B',8);
      $pdf->SetTextColor(255,255,255);
      $pdf->Row(array('', '', '', '', '', '', 'Total:', String::formatoNumero($total)), true);

      $pdf->Output('Reporte_Ventas_Cliente.pdf', 'I');
  }

  public function rvp_pdf()
  {
      $data = $this->getRVP();

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->show_head = true;
      $pdf->titulo2 = 'Reporte Ventas Productos';

      if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
        $pdf->titulo3 = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      elseif (!empty($_GET['ffecha1']))
        $pdf->titulo3 = "Del ".$_GET['ffecha1'];
      elseif (!empty($_GET['ffecha2']))
        $pdf->titulo3 = "Del ".$_GET['ffecha2'];

      $pdf->AliasNbPages();
      // $links = array('', '', '', '');
      $pdf->SetY(30);
      $aligns = array('C', 'C', 'C', 'C','C', 'C', 'C', 'C', 'C');
      $widths = array(20, 120, 20, 44);
      $header = array('Codigo', 'Producto', 'Cantidad', 'Importe');
      $total = 0;

      foreach($data as $key => $item)
      {
        $band_head = false;
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          $pdf->AddPage();

          $pdf->SetFont('Arial','B',8);
          $pdf->SetTextColor(255,255,255);
          $pdf->SetFillColor(160,160,160);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('Arial','',8);
        $pdf->SetTextColor(0,0,0);

        $datos = array($item->codigo, $item->producto, $item->total_cantidad, String::formatoNumero($item->total_importe));

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false);
      }

      $pdf->Output('Reporte_Ventas_Productos.pdf', 'I');
  }


}