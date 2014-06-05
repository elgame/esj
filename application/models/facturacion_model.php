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

    $empresa = $this->empresas_model->getDefaultEmpresa();
    if( ! $this->input->get('did_empresa') != '')
    {
      $_GET['did_empresa'] = $empresa->id_empresa;
      $_GET['dempresa'] = $empresa->nombre_fiscal;
    }
      $sql .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";

    if($this->input->get('dobserv') != '')
      $sql .= " AND lower(f.Observaciones) LIKE '%".$this->input->get('dobserv')."%'";

    // $data_series = $this->db->query("SELECT serie FROM facturacion_series_folios WHERE id_empresa = '".$this->input->get('did_empresa')."' AND es_nota_credito = '".($tipo == 'facturas'? 'f': 't')."'")->result();
    // $seriess = '';
    // foreach ($data_series as $key => $value)
    // {
    //   $seriess .= ",'".$value->serie."'";
    // }
    // if($seriess != '')
    //   $sql .= " AND f.serie IN(".(substr($seriess, 1)).")";

		$query = BDUtil::pagination("
				SELECT f.id_factura, Date(f.fecha) AS fecha, f.serie, f.folio, c.nombre_fiscal,
                e.nombre_fiscal as empresa, f.condicion_pago, f.forma_pago, f.status, f.total, f.id_nc,
                f.status_timbrado, f.uuid, f.docs_finalizados, f.observaciones, f.refacturada, f.total
				FROM facturacion AS f
        INNER JOIN empresas AS e ON e.id_empresa = f.id_empresa
        INNER JOIN clientes AS c ON c.id_cliente = f.id_cliente
				WHERE f.is_factura = 't'".$sql.$sql2."
				ORDER BY Date(f.fecha) DESC, f.serie ASC, f.folio DESC
				", $params, true); //AND f.status != 'b'
		$res = $this->db->query($query['query']);

		$response = array(
        'fact'           => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0)
			$response['fact'] = $res->result();

		return $response;
	}

	/**
	 * Obtiene la informacion de una factura
	 */
	public function getInfoFactura($idFactura, $info_basic=false)
  {
		$res = $this->db
            ->select("*")
            ->from('facturacion')
            ->where("id_factura = {$idFactura}")
            ->get();

    if($res->num_rows() > 0)
    {
			$response['info'] = $res->row();
      $response['info']->fechaT = str_replace(' ', 'T', substr($response['info']->fecha, 0, 16));
      $response['info']->fecha = substr($response['info']->fecha, 0, 10);

			$res->free_result();

      if($info_basic)
				return $response;

      // Carga la info de la empresa.
      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($response['info']->id_empresa);
      $response['info']->empresa = $empresa['info'];

      // Carga la info del cliente.
			$this->load->model('clientes_model');
			$prov = $this->clientes_model->getClienteInfo($response['info']->id_cliente);
			$response['info']->cliente = $prov['info'];

      $res = $this->db
        ->select('fp.id_factura, fp.id_clasificacion, fp.num_row, fp.cantidad, fp.descripcion, fp.precio_unitario,
                fp.importe, fp.iva, fp.unidad, fp.retencion_iva, cl.cuenta_cpi, fp.porcentaje_iva, fp.porcentaje_retencion, fp.ids_pallets,
                u.id_unidad, fp.kilos, fp.cajas, fp.id_unidad_rendimiento, fp.ids_remisiones, fp.clase, fp.peso')
        ->from('facturacion_productos as fp')
        ->join('clasificaciones as cl', 'cl.id_clasificacion = fp.id_clasificacion', 'left')
        ->join('unidades as u', 'u.nombre = fp.unidad', 'left')
        ->where('id_factura = ' . $idFactura)->order_by('fp.num_row', 'asc')
        ->get();

      $response['productos'] = $res->result();

      // Obtiene los pallets que tiene la factura.
      $response['pallets'] = array();
      $res = $this->db->query(
        "SELECT fp.id_pallet, rp.folio, rp.no_cajas, rp.kilos_pallet, string_agg(clasi.nombre::text, ', '::text) AS clasificaciones, cali.calibres,
          (
            SELECT string_agg(etiq.nombre::text, ', '::text) AS etiquetas
            FROM rastria_pallets rp2
            JOIN (
            SELECT rpr.id_pallet, et.nombre
            FROM rastria_pallets_rendimiento rpr
            JOIN etiquetas et ON rpr.id_etiqueta = et.id_etiqueta
            GROUP BY rpr.id_pallet, rpr.id_etiqueta, et.nombre
            ORDER BY rpr.id_pallet
            ) etiq ON etiq.id_pallet = rp2.id_pallet
            WHERE etiq.id_pallet = fp.id_pallet
          ) AS etiquetas

        FROM facturacion_pallets fp
        INNER JOIN rastria_pallets rp ON rp.id_pallet = fp.id_pallet

        INNER JOIN (
          SELECT rpr.id_pallet, cl.nombre
          FROM rastria_pallets_rendimiento rpr
          JOIN clasificaciones cl ON rpr.id_clasificacion = cl.id_clasificacion
          GROUP BY rpr.id_pallet, rpr.id_clasificacion, cl.nombre
          ORDER BY rpr.id_pallet
        ) clasi ON clasi.id_pallet = fp.id_pallet

        LEFT JOIN (
          SELECT rpc.id_pallet, string_agg(cal.nombre::text, ', '::text) AS calibres
          FROM rastria_pallets_calibres rpc
          JOIN calibres cal ON rpc.id_calibre = cal.id_calibre
          GROUP BY rpc.id_pallet
          ORDER BY rpc.id_pallet
        ) cali ON cali.id_pallet = fp.id_pallet

        WHERE id_factura = {$idFactura}
        GROUP BY fp.id_pallet, rp.folio, rp.no_cajas, rp.kilos_pallet, cali.calibres
        ORDER BY fp.id_pallet ASC;");

      $response['pallets'] = $res->result();

      $response['remisiones'] = $this->db->query(
        "SELECT fvr.id_venta, f.serie, f.folio
        FROM facturacion AS f INNER JOIN facturacion_ventas_remision_pivot AS fvr ON f.id_factura = fvr.id_venta
        WHERE fvr.id_factura = {$idFactura}")->result();

      $remitente = $this->db->query(
        "SELECT nombre, direccion, rfc, placas, modelo, chofer, marca
         FROM facturacion_remitente
         WHERE id_factura = $idFactura");

      $destinatario = $this->db->query(
       "SELECT nombre, direccion, rfc
        FROM facturacion_destinatario
        WHERE id_factura = $idFactura");

      if ($remitente->num_rows() > 0 || $destinatario->num_rows() > 0)
      {
        $response['carta_porte']['remitente'] = $remitente->result();
        $response['carta_porte']['destinatario'] = $destinatario->result();
      }

      // echo "<pre>";
      //   var_dump($response);
      // echo "</pre>";exit;

			return $response;
		}else
			return false;
	}

	/**
	 * Obtiene el folio de acuerdo a la serie seleccionada
     *
     * @param string $serie
     * @param string $empresa
	 */
	public function getFolioSerie($serie, $empresa, $sqlX = null)
  {
		$res = $this->db->select('folio')
      ->from('facturacion')
      ->where("serie = '".$serie."' AND id_empresa = ".$empresa."") // AND status != 'b'
      ->where('is_factura', 't')
      ->order_by('folio', 'DESC')
      ->limit(1)->get()->row();

		$folio = (isset($res->folio)? $res->folio: 0)+1;

    if ( ! is_null($sqlX))
      $this->db->where($sqlX);

		$res = $this->db->select('*')
      ->from('facturacion_series_folios')
      ->where("serie = '".$serie."' AND id_empresa = ".$empresa)
      ->limit(1)->get()->row();

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
    * Obtiene el folio de acuerdo a la serie seleccionada.
    *
    * @param string $ide
    */
    public function getSeriesEmpresa($id_empresa, $sqlX = null)
    {
        if ( is_null($sqlX))
          $this->db->where("es_nota_credito = 'f' ");
        else
          $this->db->where($sqlX);

        $query = $this->db
          ->select('id_serie_folio, id_empresa, serie, leyenda')
          ->from('facturacion_series_folios')
          ->where("id_empresa = ".$id_empresa."")
          ->order_by('serie', 'ASC')
          ->get();

        $res = array();
        if($query->num_rows() > 0)
        {
          $res = $query->result();
          $msg = 'ok';
        }
        else
          $msg = 'La empresa seleccionada no cuenta con Series y Folios.';

        return array($res, $msg);
    }

    /**
    * Inicializa los datos que serviran para generar la cadena original.
    *
    * @return array
    */
    private function datosCadenaOriginal()
    {
        $anoAprobacion = explode('-', $_POST['dano_aprobacion']);

        // Obtiene la forma de pago, si es en parcialidades entonces la forma de
        // pago son las parcialidades "Parcialidad 1 de X".
        $formaPago = ($_POST['dforma_pago'] == 'Pago en parcialidades') ? $this->input->post('dforma_pago_parcialidad') : 'Pago en una sola exhibición';

        // Obtiene los datos del receptor.
        $cliente = $this->clientes_model->getClienteInfo($_POST['did_cliente'], true);

        // Array con los datos necesarios para generar la cadena original.
        $data = array(
          'id'                => $this->input->post('did_empresa'),
          'table'             => 'empresas',

          'Moneda'            => $this->input->post('moneda'),
          'TipoCambio'        => $this->input->post('tipoCambio'),
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
          'NumCtaPago'        => ($_POST['dmetodo_pago'] === 'efectivo') ? 'No identificado' : ($_POST['dmetodo_pago_digitos'] !== '' ? $_POST['dmetodo_pago_digitos']  : 'No identificado'),

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

    public function addPallestRemisiones($idFactura, $borrador)
    {
      if (isset($_POST['palletsIds']))
      {
        $pallets = array(); // Ids de los pallets cargados en la factura.
        // Crea el array de los pallets a insertar.
        foreach ($_POST['palletsIds'] as $palletId)
        {
          $pallets[] = array(
            'id_factura' => $idFactura,
            'id_pallet'  => $palletId
          );
        }

        if (count($pallets) > 0)
        {
          if ((isset($_GET['idb']) && ! $borrador)  || $borrador)
          {
            $this->db->delete('facturacion_pallets', array('id_factura' => $idFactura));
          }

          $this->db->insert_batch('facturacion_pallets', $pallets);
        }
      }

      if (isset($_POST['remisionesIds']))
      {
        $remisiones = array(); // Ids de los pallets cargados en la factura.
        // Crea el array de los pallets a insertar.
        foreach ($_POST['remisionesIds'] as $remisionId)
        {
          $remisiones[] = array(
            'id_factura' => $idFactura,
            'id_venta'  => $remisionId
          );
        }

        if (count($remisiones) > 0)
        {
          if ((isset($_GET['idb']) && ! $borrador)  || $borrador)
          {
            $this->db->delete('facturacion_ventas_remision_pivot', array('id_factura' => $idFactura));
          }

          $this->db->insert_batch('facturacion_ventas_remision_pivot', $remisiones);
        }
      }
      return array('passes' => true, 'msg' => 'Se ligaron las remisiones correctamente');
    }

  /**
	 * Agrega una Factura.
   *
   * @return  array
	 */
  public function addFactura($borrador = false)
  {
    $this->load->library('cfdi');
    $this->load->model('clientes_model');

    $anoAprobacion = explode('-', $_POST['dano_aprobacion']);

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
      'metodo_pago_digitos' => ($_POST['dmetodo_pago'] === 'efectivo') ? 'No identificado' : ($_POST['dmetodo_pago_digitos'] !== '' ? $_POST['dmetodo_pago_digitos']  : 'No identificado'),
      'no_certificado'      => $this->input->post('dno_certificado'),
      'cadena_original'     => '',
      'sello'               => '',
      'certificado'         => '',
      'condicion_pago'      => $this->input->post('dcondicion_pago'),
      'plazo_credito'       => $_POST['dcondicion_pago'] === 'co' ? 0 : $this->input->post('dplazo_credito'),
      'observaciones'       => $this->input->post('dobservaciones'),
      'status'              => $borrador ? 'b' : 'p',
      // 'status'              => $_POST['dcondicion_pago'] === 'co' ? 'pa' : 'p',
      'retencion_iva'       => $this->input->post('total_retiva'),
      'sin_costo'           => isset($_POST['dsincosto']) ? 't' : 'f',
      'moneda'              => $_POST['moneda'],
    );

    // Tipo de cambio y moneda
    if ($datosFactura['moneda'] !== 'M.N.')
      $datosFactura['tipo_cambio'] = $_POST['tipoCambio'];
    else
      $datosFactura['tipo_cambio'] = '1';

    // Si el tipo de comprobante es "egreso" o una nota de credito.
    if ($_POST['dtipo_comprobante'] === 'egreso')
      $datosFactura['id_nc'] = $_GET['id'];

    // Inserta los datos de la factura y obtiene el Id. Este en caso
    // de que se este timbrando una factura que no sea un borrador.
    if (( ! isset($_GET['idb']) && ! $borrador) || $borrador)
    {
      $this->db->insert('facturacion', $datosFactura);
      $idFactura = $this->db->insert_id('facturacion', 'id_factura');
    }

    // Si es un borrador que se esta timbrando entonces actualiza sus datos.
    else
    {
      $idFactura = $_GET['idb'];
      $this->db->update('facturacion', $datosFactura, array('id_factura' => $idFactura));
    }

    // Productos e Impuestos
    $productosCadOri    = array(); // Productos para la CadOriginal
    $productosFactura   = array(); // Productos para la Factura

    $impuestosTraslados = array(); // Traslados
    $traslado0  = false; // Total de traslado 0%
    $traslado11 = 0; // Total de traslado 11%
    $traslado16 = 0; // Total de traslado 16%

    // Ciclo para obtener los impuestos traslados, tambien construye
    // los datos de  los productos a insertar tanto en la cadena original como
    // en la factura.
    foreach ($_POST['prod_ddescripcion'] as $key => $descripcion)
    {
      if ($_POST['prod_importe'][$key] != 0)
      {
        $productosCadOri[] = array(
          'cantidad'         => $_POST['prod_dcantidad'][$key],
          'unidad'           => $_POST['prod_dmedida'][$key],
          'descripcion'      => $descripcion . ((isset($_POST['prod_dclase'][$key]) && $_POST['prod_dclase'][$key] !== '') ? ' Clase '.$_POST['prod_dclase'][$key] : '') . ((isset($_POST['prod_dpeso'][$key]) && $_POST['prod_dpeso'][$key] !== '0' && $_POST['prod_dpeso'][$key] !== '') ? ' Peso '.$_POST['prod_dpeso'][$key] : ''),
          'valorUnitario'    => $_POST['prod_dpreciou'][$key],
          'importe'          => $_POST['prod_importe'][$key],
          'idClasificacion' => $_POST['prod_did_prod'][$key] !== '' ? $_POST['prod_did_prod'][$key] : null,
          // 'clase'           => isset($_POST['prod_dclase'][$key]) ? ' Clase '.$_POST['prod_dclase'][$key] : '',
          // 'peso'            => (isset($_POST['prod_dpeso'][$key]) && $_POST['prod_dpeso'][$key] !== '') ? ' Peso '.$_POST['prod_dpeso'][$key].' '.$_POST['prod_dmedida'][$key] : '',
        );

        if ($_POST['prod_diva_porcent'][$key] == '11')
          $traslado11 += floatval($_POST['prod_diva_total'][$key]);
        else if ($_POST['prod_diva_porcent'][$key] == '16')
          $traslado16 += floatval($_POST['prod_diva_total'][$key]);
        else
          $traslado0 = true;

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
          'porcentaje_iva'   => $_POST['prod_diva_porcent'][$key],
          'porcentaje_retencion' => $_POST['prod_dreten_iva_porcent'][$key],
          'ids_pallets' => isset($_POST['pallets_id'][$key]) && $_POST['pallets_id'][$key] !== '' ? $_POST['pallets_id'][$key] : null,
          'ids_remisiones' => isset($_POST['remisiones_id'][$key]) && $_POST['remisiones_id'][$key] !== '' ? $_POST['remisiones_id'][$key] : null,
          'kilos' => isset($_POST['prod_dkilos'][$key]) ? $_POST['prod_dkilos'][$key] : 0,
          'cajas' => isset($_POST['prod_dcajas'][$key]) ? $_POST['prod_dcajas'][$key] : 0,
          'id_unidad_rendimiento' => isset($_POST['id_unidad_rendimiento'][$key]) && $_POST['id_unidad_rendimiento'][$key] !== '' ? $_POST['id_unidad_rendimiento'][$key] : null,
          'id_size_rendimiento'   => isset($_POST['id_size_rendimiento'][$key]) && $_POST['id_size_rendimiento'][$key] !== '' ? $_POST['id_size_rendimiento'][$key] : null,
          'clase' => isset($_POST['prod_dclase'][$key]) ? $_POST['prod_dclase'][$key] : '',
          'peso' => isset($_POST['prod_dpeso'][$key]) && $_POST['prod_dpeso'][$key] !== '' ? $_POST['prod_dpeso'][$key] : 0,
        );
      }
    }

    if (count($productosFactura) > 0)
    {
      if ((isset($_GET['idb']) && ! $borrador) || $borrador)
      {
        $this->db->delete('facturacion_productos', array('id_factura' => $idFactura));
      }

      $this->db->insert_batch('facturacion_productos', $productosFactura);
    }

    // Inserta los pallests y las remisiones a la factura
    $this->addPallestRemisiones($idFactura, $borrador);

    if (isset($_POST['es_carta_porte']))
    {
      if (isset($_POST['es_carta_porte']) || $borrador)
      {
        $this->db->delete('facturacion_remitente', array('id_factura' => $idFactura));
        $this->db->delete('facturacion_destinatario', array('id_factura' => $idFactura));
      }

      $remitente = array(
        'id_factura' => $idFactura,
        'nombre'    => $_POST['remitente_nombre'],
        'rfc'       => $_POST['remitente_rfc'],
        'direccion' => $_POST['remitente_domicilio'],
        'chofer'    => $_POST['remitente_chofer'],
        'marca'     => $_POST['remitente_marca'],
        'modelo'    => $_POST['remitente_modelo'],
        'placas'    => $_POST['remitente_placas'],
      );

      $destinatario = array(
        'id_factura' => $idFactura,
        'nombre'    => $_POST['destinatario_nombre'],
        'rfc'       => $_POST['destinatario_rfc'],
        'direccion' => $_POST['destinatario_domicilio'],
      );

      $this->db->insert('facturacion_remitente', $remitente);
      $this->db->insert('facturacion_destinatario', $destinatario);
    }

    // Si es un borrador
    if ($borrador) return true;

    // Obtiene los datos para la cadena original
    $datosCadOrig = $this->datosCadenaOriginal();
    $datosCadOrig['sinCosto']   =  isset($_POST['dsincosto']) ? true : false;

    // Si es un ingreso o una factura.
    if ($_POST['dtipo_comprobante'] === 'ingreso')
    {
      // Obtiene los documentos que el cliente tiene asignados.
      $docsCliente = $this->getClienteDocs($datosFactura['id_cliente'], $idFactura);

      $this->load->model('documentos_model');
      $pathDocs = $this->documentos_model->creaDirectorioDocsCliente($datosCadOrig['nombre'], $datosFactura['serie'], $datosFactura['folio']);

      // Inserta los documentos del cliente con un status false.
      if ($docsCliente)
        $this->db->insert_batch('facturacion_documentos', $docsCliente);
      else
        $datosFactura['docs_finalizados'] = 't';
    }
    else
    {
        $this->load->model('documentos_model');
        $pathDocs = $this->documentos_model->creaDirectorioDocsCliente($datosCadOrig['nombre'], $datosFactura['serie'], $datosFactura['folio']);
        $datosFactura['docs_finalizados'] = 't';
    }

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
    // Inserta los datos del cliente.
    $this->db->insert('facturacion_cliente', $dataCliente);

    // Asignamos los productos o conceptos a los datos de la cadena original.
    $datosCadOrig['concepto']  = $productosCadOri;

    // Asignamos las retenciones a los datos de la cadena original.
     $impuestosRetencion = array(
      'impuesto' => 'IVA',
      'importe'  => $this->input->post('total_retiva'),
    );

    $datosCadOrig['retencion'][] = $impuestosRetencion;
    $datosCadOrig['totalImpuestosRetenidos'] = $this->input->post('total_retiva');

    // Si hay conceptos con traslado 0% lo agrega.
    if ($traslado0 && $traslado11 === 0 && $traslado16 === 0)
    {
        $impuestosTraslados[] = array(
            'Impuesto' => 'IVA',
            'tasa'     => '0',
            'importe'  => '0',
        );
    }

    // Si hay conceptos con traslado 11% lo agrega.
    if ($traslado11 !== 0)
    {
      $impuestosTraslados[] = array(
        'Impuesto' => 'IVA',
        'tasa'     => '11',
        'importe'  => $traslado11,
      );
    }

    // Si hay conceptos con traslado 16% lo agrega.
    if ($traslado16 !== 0)
    {
      $impuestosTraslados[] = array(
        'Impuesto' => 'IVA',
        'tasa'     => '16',
        'importe'  => $traslado16,
      );
    }

    // Asigna los impuestos traslados.
    $datosCadOrig['traslado']  = $impuestosTraslados;
    $datosCadOrig['totalImpuestosTrasladados'] = $this->input->post('total_iva');

    // Genera la cadena original y el sello.
    $cadenaOriginal = $this->cfdi->obtenCadenaOriginal($datosCadOrig);
    $sello          = $this->cfdi->obtenSello($cadenaOriginal['cadenaOriginal']);

    // Obtiene el contentido del certificado.
    $certificado = $this->cfdi->obtenCertificado($this->db
      ->select('cer')
      ->from("empresas")
      ->where("id_empresa", $_POST['did_empresa'])
      ->get()->row()->cer
    );

    // Datos que actualizara de la factura
    $updateFactura = array(
      'cadena_original' => $cadenaOriginal['cadenaOriginal'],
      'sello'           => $sello,
      'certificado'     => $certificado,
    );
    $this->db->update('facturacion', $updateFactura, array('id_factura' => $idFactura));

    // Datos para el XML3.2
    $datosXML               = $cadenaOriginal['datos'];
    $datosXML['id']         = $this->input->post('did_empresa');
    $datosXML['sinCosto']   =  isset($_POST['dsincosto']) ? true : false;
    $datosXML['table']      = 'empresas';
    $datosXML['comprobante']['serie']         = $this->input->post('dserie');
    $datosXML['comprobante']['folio']         = $this->input->post('dfolio');
    $datosXML['comprobante']['sello']         = $sello;
    $datosXML['comprobante']['noCertificado'] = $this->input->post('dno_certificado');
    $datosXML['comprobante']['certificado']   = $certificado;
    $datosXML['concepto']                     = $productosCadOri;

    $datosXML['domicilio']['calle']        = $dataCliente['calle'];
    $datosXML['domicilio']['noExterior']   = $dataCliente['no_exterior'];
    $datosXML['domicilio']['noInterior']   = $dataCliente['no_interior'];
    $datosXML['domicilio']['colonia']      = $dataCliente['colonia'];
    $datosXML['domicilio']['localidad']    = $dataCliente['localidad'];
    $datosXML['domicilio']['municipio']    = $dataCliente['municipio'];
    $datosXML['domicilio']['estado']       = $dataCliente['estado'];
    $datosXML['domicilio']['pais']         = $dataCliente['pais'];
    $datosXML['domicilio']['codigoPostal'] = $dataCliente['cp'];

    $datosXML['totalImpuestosRetenidos']   = $this->input->post('total_retiva');
    $datosXML['totalImpuestosTrasladados'] = $this->input->post('total_iva');

    $datosXML['retencion'] = $impuestosRetencion;
    $datosXML['traslado']  = $impuestosTraslados;

    // Genera el archivo XML y lo guarda en disco.
    $archivos = $this->cfdi->generaArchivos($datosXML);

    // Timbrado de la factura.
    $result = $this->timbrar($archivos['pathXML'], $idFactura);

    if ($result['passes'])
    {
      $this->generaFacturaPdf($idFactura, $pathDocs);

      $xmlName = explode('/', $archivos['pathXML']);

      copy($archivos['pathXML'], $pathDocs.end($xmlName));

      //Si es otra moneda actualiza al tipo de cambio
      if($datosFactura['moneda'] !== 'M.N.')
      {
        $datosFactura1 = array();
        $datosFactura1['total']         = number_format($datosFactura['total']*$datosFactura['tipo_cambio'], 2, '.', '');
        $datosFactura1['subtotal']      = number_format($datosFactura['subtotal']*$datosFactura['tipo_cambio'], 2, '.', '');
        $datosFactura1['importe_iva']   = number_format($datosFactura['importe_iva']*$datosFactura['tipo_cambio'], 2, '.', '');
        $datosFactura1['retencion_iva'] = number_format($datosFactura['retencion_iva']*$datosFactura['tipo_cambio'], 2, '.', '');
        $this->db->update('facturacion', $datosFactura1, array('id_factura' => $idFactura));

        foreach ($productosFactura as $key => $value)
        {
          $value['precio_unitario'] = number_format($value['precio_unitario']*$datosFactura['tipo_cambio'], 2, '.', '');
          $value['importe']         = number_format($value['importe']*$datosFactura['tipo_cambio'], 2, '.', '');
          $value['iva']             = number_format($value['iva']*$datosFactura['tipo_cambio'], 2, '.', '');
          $value['retencion_iva']   = number_format($value['retencion_iva']*$datosFactura['tipo_cambio'], 2, '.', '');
          $this->db->update('facturacion_productos', $value, "id_factura = {$value['id_factura']} AND num_row = {$value['num_row']}");
        }
      }

      // Elimina el borrador.
      // if (isset($_GET['idb']))
      //   $this->db->delete('facturacion', array('id_factura' => $_GET['idb']));

      // Procesa la salida
      $this->load->model('unidades_model');
      $this->load->model('productos_salidas_model');
      $this->load->model('inventario_model');

      $infoSalida      = array();
      $productosSalida = array(); // contiene los productos que se daran salida.

      $infoSalida = array(
        'id_empresa'      => $_POST['did_empresa'],
        'id_empleado'     => $this->session->userdata('id_usuario'),
        'folio'           => $this->productos_salidas_model->folio(),
        'fecha_creacion'  => date('Y-m-d H:i:s'),
        'fecha_registro'  => date('Y-m-d H:i:s'),
        'status'          => 's',
      );

      $res = $this->productos_salidas_model->agregar($infoSalida);

      $row = 0;
      foreach ($_POST['prod_ddescripcion'] as $key => $descripcion)
      {
        if ($_POST['prod_importe'][$key] != 0)
        {
          if (isset($_POST['prod_dmedida_id'][$key]) && $_POST['prod_dmedida_id'][$key] !== '')
          {
            $unidad = $this->unidades_model->info($_POST['prod_dmedida_id'][$key], true);

            foreach ($unidad['info'][0]->productos as $uniProd)
            {
              $inv   = $this->inventario_model->promedioData($uniProd->id_producto, date('Y-m-d'), date('Y-m-d'));
              $saldo = array_shift($inv);

              $productosSalida[] = array(
                'id_salida'       => $res['id_salida'],
                'id_producto'     => $uniProd->id_producto,
                'no_row'          => $row,
                'cantidad'        => floatval($_POST['prod_dcantidad'][$key]) * floatval($uniProd->cantidad),
                'precio_unitario' => $saldo['saldo'][1],
              );

              $row++;
            }
          }
        }
      }

      // Si hay al menos 1 producto para las salidas lo inserta.
      if (count($productosSalida) > 0)
      {
        $this->productos_salidas_model->agregarProductos(null, $productosSalida);
      }

      // Si no hay productos para ninguna de las medidas elimina la salida.
      else
      {
        $this->db->delete('compras_salidas', array('id_salida' => $res['id_salida']));
      }
    }
    else
    {
      rmdir($pathDocs);
    }

    // $datosFactura, $cadenaOriginal, $sello, $productosFactura,
    // echo "<pre>";
    //   var_dump($datosXML);
    // echo "</pre>";exit;

    return $result;
	}

  /**
  * Realiza el timbrado de una factura.
  *
  * @param  string $xml
  * @param  string $idFactura
  * @param  boolean $delFiles
  * @return void
  */
  private function timbrar($pathXML, $idFactura, $delFiles = true)
  {
    $this->load->library('facturartebarato_api');

    $this->facturartebarato_api->setPathXML($pathXML);

    // Realiza el timbrado usando la libreria.
    $timbrado = $this->facturartebarato_api->timbrar();

    // echo "<pre>";
    //   var_dump($timbrado);
    // echo "</pre>";exit;

    $result = array(
      'id_factura' => $idFactura,
      'codigo'     => $timbrado->codigo
    );

    // Si no hubo errores al momento de realizar el timbrado.
    if ($timbrado->status)
    {
      // Si el codigo es 501:Autenticación no válida o 708:No se pudo conectar al SAT,
      // significa que el timbrado esta pendiente.
      if ($timbrado->codigo === '501' || $timbrado->codigo === '708')
      {
        // Se coloca el status de timbre de la factura como pendiente.
        $statusTimbrado = 'p';
      }
      else
      {
        // Si el timbrado se realizo correctamente.

        // Se coloca el status de timbre de la factura como timbrado.
        $statusTimbrado = 't';
      }

      // Actualiza los datos en la BDD.
      $dataTimbrado = array(
        'xml'             => $this->facturartebarato_api->getXML(),
        'status_timbrado' => $statusTimbrado,
        'uuid'            => $this->facturartebarato_api->getUUID(),
      );

      $this->db->update('facturacion', $dataTimbrado, array('id_factura' => $idFactura));

      $result['passes'] = true;
    }
    else
    {
      // Si es true $delFile entonces elimina todo lo relacionado con la factura.
      if ($delFiles)
      {
        $this->db->delete('facturacion_cliente', array('id_factura' => $idFactura));
        $this->db->delete('facturacion', array('id_factura' => $idFactura));
        unlink($pathXML);
      }

      // Entra si hubo un algun tipo de error de conexion a internet.
      if ($timbrado->codigo === 'ERR_INTERNET_DISCONNECTED')
        $result['msg'] = 'Error Timbrado: Internet Desconectado. Verifique su conexión para realizar el timbrado.';
      elseif ($timbrado->codigo === '500')
        $result['msg'] = 'Error en el servidor del timbrado. Pongase en contacto con el equipo de desarrollo del sistema.';
      else
        $result['msg'] = 'Ocurrio un error al intentar timbrar la factura, verifique los datos fiscales de la empresa y/o cliente.';

      $result['passes'] = false;
      }

      // echo "<pre>";
      //   var_dump($timbrado);
      // echo "</pre>";exit;

      return $result;
  }

  /**
  * Verifica que el timbrado de la factura se ha realiza. Esto es en caso
  * de que el timbrado alla quedado pendiente.
  *
  * @param  string $idFactura
  * @return boolean
  */
  public function verificarTimbrePendiente($idFactura)
  {
      $this->load->library('facturartebarato_api');

      // Obtenemos el uuid de la factura pendiente a timbrar.
      $uuid = $this->db
        ->select('uuid')
        ->from('facturacion')
        ->where('id_factura', $idFactura)
        ->get()->row()->uuid;

      $this->facturartebarato_api->setUUID($uuid);

      // Reliza la peticion para verificar el stutus de la factura.
      $result = $this->facturartebarato_api->verificarPendiente();

      // Si el status es Finished entonces ya se timbro correctamente.
      if ($result->data->status === 'F')
      {
        $this->db->update('facturacion',
          array('status_timbrado' => 't'),
          array('id_factura' => $idFactura)
        );
      }

      return $result->data->status === 'F' ? true : false;
  }

	/**
	 * Cancela una factura. Cambia el status a 'ca'.
   *
   * @return array
	 */
	public function cancelaFactura($idFactura)
  {
    $this->load->library('cfdi');
    $this->load->library('facturartebarato_api');
    $this->load->model('documentos_model');

    // Obtenemos la info de la factura a cancelar.
    $factura = $this->getInfoFactura($idFactura);

    // Carga los datos fiscales de la empresa dentro de la lib CFDI.
    $this->cfdi->cargaDatosFiscales($factura['info']->id_empresa);

    // Parametros que necesita el webservice para la cancelacion.
    $params = array(
      'rfc'   => $factura['info']->empresa->rfc,
      'uuids' => $factura['info']->uuid,
      'cer'   => $this->cfdi->obtenCer(),
      'key'   => $this->cfdi->obtenKey(),
    );

    // Lama el metodo cancelar para que realiza la peticion al webservice.
    $result = $this->facturartebarato_api->cancelar($params);

    if ($result->data->status_uuid === '201' || $result->data->status_uuid === '202')
    {
      $this->db->update('facturacion',
        array('status' => 'ca', 'status_timbrado' => 'ca'),
        "id_factura = {$idFactura}"
      );

      // Regenera el PDF de la factura.
      $pathDocs = $this->documentos_model->creaDirectorioDocsCliente($factura['info']->cliente->nombre_fiscal, $factura['info']->serie, $factura['info']->folio);
      $this->generaFacturaPdf($idFactura, $pathDocs);

      $this->enviarEmail($idFactura);
    }

    return array('msg' => $result->data->status_uuid);
	}

   /**
    * Paga una factura cambiando su status a pagada 'pa'.
    *
    * @return array
    */
    public function pagaFactura()
    {
        $this->db->update('facturacion', array('status' => 'pa'), "id_factura = '".$_GET['id']."'");
        return array(true, '');
    }

   /**
    * Descarga el XML.
    *
    * @param  string $idFactura
    * @return void
    */
    public function descargarXML($idFactura)
    {
        $this->load->library('cfdi');

        // Obtiene la info de la factura.
        $factura = $this->getInfoFactura($idFactura);

        $data = array(
          'id'          => $factura['info']->id_empresa,
          'table'       => 'empresa',
          'comprobante' => array('serie' => $factura['info']->serie, 'folio' => $factura['info']->folio)
        );

        $fecha = explode('-', $factura['info']->fecha);
        $ano   = $fecha[0];
        $mes   = strtoupper(String::mes(floatval($fecha[1])));
        $rfc   = $factura['info']->empresa->rfc;
        $serie = $factura['info']->serie;
        $folio = $this->cfdi->acomodarFolio($factura['info']->folio);

        $pathXML = APPPATH."media/cfdi/facturasXML/{$ano}/{$mes}/{$rfc}-{$serie}-{$folio}.xml";

        $this->cfdi->descargarXML($data, $pathXML);
    }

    /**
    * Descarga el ZIP con los documentos.
    *
    * @param  string $idFactura
    * @return void
    */
    public function descargarZip($idFactura)
    {
        $this->load->library('cfdi');

        // Obtiene la info de la factura.
        $factura = $this->getInfoFactura($idFactura);

        $cliente = strtoupper($factura['info']->cliente->nombre_fiscal);
        $fecha   = explode('-', $factura['info']->fecha);
        $ano     = $fecha[0];
        $mes     = strtoupper(String::mes(floatval($fecha[1])));
        $serie   = $factura['info']->serie !== '' ? $factura['info']->serie.'-' : '';
        $folio   = $factura['info']->folio;

        $pathDocs = APPPATH."documentos/CLIENTES/{$cliente}/{$ano}/{$mes}/FACT-{$serie}{$folio}/";

        // Scanea el directorio para obtener los archivos.
        $archivos = array_diff(scandir($pathDocs), array('..', '.'));

        $zip = new ZipArchive;
        if ($zip->open(APPPATH.'media/documentos.zip', ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === true)
        {
          foreach ($archivos as $archivo)
            $zip->addFile($pathDocs.$archivo, $archivo);

          $zip->close();
        }
        else
        {
          exit('Error al intentar crear el ZIP.');
        }

        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename=documentos.zip');
        readfile(APPPATH.'media/documentos.zip');

        unlink(APPPATH.'media/documentos.zip');
    }

   /**
    * Envia el email al ciente con todos los documentos que tiene asignados.
    *
    * @param  string $idFactura
    * @return void
    */
    public function enviarEmail($idFactura)
    {
        $this->load->library('my_email');

        // Obtiene la informacion de la factura.
        $factura = $this->getInfoFactura($idFactura);

        // El cliente necesita tener un email para poderle enviar los documentos.
        if ( (! is_null($factura['info']->cliente->email) && ! empty($factura['info']->cliente->email)) || $_POST['pextras'] !== '')
        {
          //////////////////
          // Datos Correo //
          //////////////////

            $asunto = "Ha recibido una COMPROBANTE FISCAL DIGITAL de {$factura['info']->empresa->nombre_fiscal}";

            $tipoFactura = is_null($factura['info']->id_nc) ? 'Factura': 'Nota de Crédito';

            // Si la factura esta timbrada
            if ($factura['info']->status_timbrado === "t")
            {
                $altBody = "Estimado Cliente: {$factura['info']->cliente->nombre_fiscal}. Usted está recibiendo un comprobante fiscal digital ({$tipoFactura} {$factura['info']->serie}-{$factura['info']->folio}) de
                {$factura['info']->empresa->nombre_fiscal}]";
                $body = "
                <p>Estimado Cliente: <strong>{$factura['info']->cliente->nombre_fiscal}</strong></p>
                <p>Usted está recibiendo un comprobante fiscal digital ({$tipoFactura} {$factura['info']->serie}-{$factura['info']->folio}) de {$factura['info']->empresa->nombre_fiscal}</p>
                ";
            }
            elseif ($factura['info']->status_timbrado === "ca")
            {
                $altBody = "HEMOS CANCELADO EL COMPROBANTE FISCAL DIGITAL {$tipoFactura} {$factura['info']->serie}-{$factura['info']->folio}, HA QUEDADO SIN EFECTOS FISCALES PARA SU EMPRESA, POR LO QUE PEDIMOS ELIMINARLO Y NO INCLUIRLO EN SU CONTABILIDAD, YA QUE PUEDE REPRESENTAR UN PROBLEMA FISCAL PARA USTED O SU EMPRESA CUANDO EL SAT REALICE UNA FUTURA AUDITORIA EN SU CONTABILIDAD.";
                $body = "
                <p>Estimado Cliente: <strong>{$factura['info']->cliente->nombre_fiscal}</strong></p>
                <p>HEMOS CANCELADO EL COMPROBANTE FISCAL DIGITAL {$tipoFactura} {$factura['info']->serie}-{$factura['info']->folio}, HA QUEDADO SIN EFECTOS FISCALES PARA SU EMPRESA, POR LO QUE PEDIMOS ELIMINARLO Y NO INCLUIRLO EN SU CONTABILIDAD, YA QUE PUEDE REPRESENTAR UN PROBLEMA FISCAL PARA USTED O SU EMPRESA CUANDO EL SAT REALICE UNA FUTURA AUDITORIA EN SU CONTABILIDAD.</p>
                ";
            }

            if(isset($_POST['dcomentario']{0}))
            {
               $body .= "<strong>COMENTARIO: </strong> ".$_POST['dcomentario'];
            }

            /*<p>Si por algun motivo, desea obtener nuevamente su factura puede descargarla directamente de nuestra pagina en la seccion Facturación.<br>
                <a href="http://www.chonitabananas.com/es/facturacion/">www.chonitabananas.com</a></p>*/
            $body .= '
                <p>Si usted desea que llegue el comprobante fiscal a otro correo electronico notifiquelo a: <br>
                  empaquesanjorge@hotmail.com</p>

                <br><br>
                <p>De acuerdo a la reglamentación del Servicio de Administración Tributaria (SAT) publicada en el Diario Oficial de la Federación (RMISC 2004) el 31 de mayo del 2004, la factura electrónica es 100% valida y legal.
                  A partir de ahora la entrega del documento fiscal (FACTURA ELECTRONICA) será emitida y entregada por correo electrónico a nuestros socios de negocio.
                  Cabe destacar que la factura electrónica se entregará en formato PDF y archivo XML, el cual podrá imprimir libremente e incluirla en su contabilidad (Articulo 29, Fracción IV de CFF), resguardar la impresión y archivo XML por un periodo de 5 años.
                  Importante: Contenido de la Factura Electrónica
                  En el anexo 20 del Diario Oficial de la Federación, publicado el 1 de septiembre de 2004, en párrafo 2.22.8, se estipula que la impresión de la factura electrónica, que además de los datos fiscales y comerciales, deberá contener la cadena original, el certificado de sello digital, el sello digital y la leyenda: “Este documento es una representación impresa de un CFD”.
                  <br><strong>Sistema de facturacion electrónica - Facturacion "'.$factura['info']->empresa->nombre_fiscal.'"</strong></p>
                ';

            //////////////////////
            // Datos del Emisor //
            //////////////////////

            $correoEmisorEm = "empaquesanjorge@hotmail.com"; // Correo con el q se emitira el correo.
            $nombreEmisor   = $factura['info']->empresa->nombre_fiscal;
            $correoEmisor   = "empaquesanjorgemx@gmail.com"; // Correo para el auth. empaquesanjorgemx@gmail.com (mandrill)
            $contrasena     = "2x02pxeexCUpiKncoWI50Q"; // Contraseña de $correEmisor S4nj0rg3V14n3y

            ////////////////////////
            // Datos del Receptor //
            ////////////////////////

            $correoDestino = array();

            if (isset($_POST['pextras']))
            {
              if ($_POST['pextras'] !== '')
                $correoDestino += explode(',', $_POST['pextras']);
            }

            if (isset($_POST['emails']))
            {
              foreach ($_POST['emails'] as $email)
              {
                array_push($correoDestino, $email);
              }
            }

            $nombreDestino = strtoupper($factura['info']->cliente->nombre_fiscal);
            $datosEmail = array(
                'correoEmisorEm' => $correoEmisorEm,
                'correoEmisor'   => $correoEmisor,
                'nombreEmisor'   => $nombreEmisor,
                'contrasena'     => $contrasena,
                'asunto'         => $asunto,
                'altBody'        => $altBody,
                'body'           => $body,
                'correoDestino'  => $correoDestino,
                'nombreDestino'  => $nombreDestino,
                'cc'             => $factura['info']->empresa->email,
                'adjuntos'       => array()
            );

            // Adjuntos.
            // if ($factura['info']->docs_finalizados === 't' || $factura['info']->id_nc !== null)
            // {
                $this->load->model('documentos_model');
                // $docs = $this->documentos_model->getClienteDocs($factura['info']->id_factura);

                // Si tiene documentos
                // if ($docs)
                // {
                    $cliente = strtoupper($factura['info']->cliente->nombre_fiscal);
                    $fecha   = explode('-', $factura['info']->fecha);
                    $ano     = $fecha[0];
                    $mes     = strtoupper(String::mes(floatval($fecha[1])));
                    $serie   = $factura['info']->serie !== '' ? $factura['info']->serie.'-' : '';
                    $folio   = $factura['info']->folio;

                    $pathDocs = APPPATH."documentos/CLIENTES/{$cliente}/{$ano}/{$mes}/FACT-{$serie}{$folio}/";

                    // echo "<pre>";
                    //   var_dump($pathDocs);
                    // echo "</pre>";exit;

                    // Scanea el directorio para obtener los archivos.
                    $archivos = array_diff(scandir($pathDocs), array('..', '.'));

                    $adjuntos = array();
                    foreach ($archivos as $arch)
                        $adjuntos[$arch] = $pathDocs.$arch;

                    $datosEmail['adjuntos'] = $adjuntos;
                // }
            // }

            // Envia el email.
            $result = $this->my_email->setData($datosEmail)->zip()->send();

            $response = array(
                'passes' => true,
                'msg'    => 10
            );

            if (isset($result['error']))
            {
                $response = array(
                'passes' => false,
                'msg'    => 9
                );
            }
        }
        else
        {
          $response = array(
            'passes' => false,
            'msg'    => 8
          );
        }

        return $response;
    }

	/**
	 * Actualiza los digitos del metodo de pago de una factura
	 */
	public function metodo_pago()
  {
		$this->db->update('facturas', array('metodo_pago_digitos' => $_POST['mp_digitos']), "id_factura = '".$_POST['id_factura']."'");
		return array(true, '');
	}

  /**
   * Obtiene los documentos que el cliente tiene asignados.
   *
   * @param  string
   * @return mixed array|boolean
   */
    public function getClienteDocs($idCliente, $idFactura = null)
    {
        $query = $this->db->query(
          "SELECT id_documento
           FROM clientes_documentos
           WHERE id_cliente = {$idCliente}
           ORDER BY id_documento ASC"
        );

        if ($query->num_rows() > 0)
        {
          $docs = array();
          foreach ($query->result()  as $objDoc)
          {
            if (is_null($idFactura))
              $docs[] = $objDoc->id_documento;
            else
              $docs[] = array('id_factura' => $idFactura, 'id_documento' => $objDoc->id_documento);
          }
        }

        return isset($docs) ? $docs : false;
    }

    public function addFacturaBorrador()
    {
      return $this->addFactura(true);
    }

    public function updateFacturaBorrador($idBorrador)
    {
      $anoAprobacion = explode('-', $_POST['dano_aprobacion']);

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
        'metodo_pago_digitos' => ($_POST['dmetodo_pago'] === 'efectivo') ? 'No identificado' : ($_POST['dmetodo_pago_digitos'] !== '' ? $_POST['dmetodo_pago_digitos']  : 'No identificado'),
        'no_certificado'      => $this->input->post('dno_certificado'),
        'condicion_pago'      => $this->input->post('dcondicion_pago'),
        'plazo_credito'       => $_POST['dcondicion_pago'] === 'co' ? 0 : $this->input->post('dplazo_credito'),
        'observaciones'       => $this->input->post('dobservaciones'),
        'status'              => isset($_POST['timbrar']) ? 'p' : 'b',
        'retencion_iva'       => $this->input->post('total_retiva'),
      );

      // Si el tipo de comprobante es "egreso" o una nota de credito.
      if ($_POST['dtipo_comprobante'] === 'egreso')
        $datosFactura['id_nc'] = $_GET['id'];

      // Inserta los datos de la factura y obtiene el Id.
      $this->db->update('facturacion', $datosFactura, array('id_factura' => $idBorrador));

      // Productos e Impuestos
      $productosFactura   = array(); // Productos para la Factura
      $pallets = array(); // Ids de los pallets cargados en la factura.
      $lastPalletId = 0;

      foreach ($_POST['prod_ddescripcion'] as $key => $descripcion)
      {
        if ($_POST['prod_importe'][$key] != 0)
        {
          $productosFactura[] = array(
            'id_factura'       => $idBorrador,
            'id_clasificacion' => $_POST['prod_did_prod'][$key] !== '' ? $_POST['prod_did_prod'][$key] : null,
            'num_row'          => intval($key),
            'cantidad'         => $_POST['prod_dcantidad'][$key],
            'descripcion'      => $descripcion,
            'precio_unitario'  => $_POST['prod_dpreciou'][$key],
            'importe'          => $_POST['prod_importe'][$key],
            'iva'              => $_POST['prod_diva_total'][$key],
            'unidad'           => $_POST['prod_dmedida'][$key],
            'retencion_iva'    => $_POST['prod_dreten_iva_total'][$key],
            'porcentaje_iva'   => $_POST['prod_diva_porcent'][$key],
            'porcentaje_retencion' => $_POST['prod_dreten_iva_porcent'][$key],
            'ids_pallets'       => $_POST['pallets_id'][$key] !== '' ? $_POST['pallets_id'][$key] : null,
            'ids_remisiones'    => $_POST['remisiones_id'][$key] !== '' ? $_POST['remisiones_id'][$key] : null,
            'kilos'             => $_POST['prod_dkilos'][$key],
            'cajas'             => $_POST['prod_dcajas'][$key],
            'id_unidad_rendimiento' => $_POST['id_unidad_rendimiento'][$key] !== '' ? $_POST['id_unidad_rendimiento'][$key] : null,
            'clase' => isset($_POST['prod_dclase'][$key]) ? $_POST['prod_dclase'][$key] : null,
            'peso' => isset($_POST['prod_dpeso'][$key]) ? $_POST['prod_dpeso'][$key] : null,
          );
        }
      }
      $this->db->delete('facturacion_productos', array('id_factura' => $idBorrador));
      $this->db->delete('facturacion_pallets', array('id_factura' => $idBorrador));
      $this->db->delete('facturacion_ventas_remision_pivot', array('id_factura' => $idBorrador));

      if (count($productosFactura) > 0)
        $this->db->insert_batch('facturacion_productos', $productosFactura);

      if (isset($_POST['palletsIds']))
      {
        $pallets = array(); // Ids de los pallets cargados en la factura.
        // Crea el array de los pallets a insertar.
        foreach ($_POST['palletsIds'] as $palletId)
        {
          $pallets[] = array(
            'id_factura' => $idBorrador,
            'id_pallet'  => $palletId
          );
        }

        if (count($pallets) > 0)
          $this->db->insert_batch('facturacion_pallets', $pallets);
      }

      if (isset($_POST['remisionesIds']))
      {
        $remisiones = array(); // Ids de los pallets cargados en la factura.
        // Crea el array de los pallets a insertar.
        foreach ($_POST['remisionesIds'] as $remisionId)
        {
          $remisiones[] = array(
            'id_factura' => $idBorrador,
            'id_venta'  => $remisionId
          );
        }

        if (count($remisiones) > 0)
          $this->db->insert_batch('facturacion_ventas_remision_pivot', $remisiones);
      }

      $this->db->delete('facturacion_remitente', array('id_factura' => $idBorrador));
      $this->db->delete('facturacion_destinatario', array('id_factura' => $idBorrador));
      if (isset($_POST['es_carta_porte']))
      {
        $remitente = array(
          'id_factura' => $idBorrador,
          'nombre'    => $_POST['remitente_nombre'],
          'rfc'       => $_POST['remitente_rfc'],
          'direccion' => $_POST['remitente_domicilio'],
          'chofer'    => $_POST['remitente_chofer'],
          'marca'     => $_POST['remitente_marca'],
          'modelo'    => $_POST['remitente_modelo'],
          'placas'    => $_POST['remitente_placas'],
        );

        $destinatario = array(
          'id_factura' => $idBorrador,
          'nombre'    => $_POST['destinatario_nombre'],
          'rfc'       => $_POST['destinatario_rfc'],
          'direccion' => $_POST['destinatario_domicilio'],
        );

        $this->db->insert('facturacion_remitente', $remitente);
        $this->db->insert('facturacion_destinatario', $destinatario);
      }
    }

    /**
     * Obtiene la ultima factura que este en status "b" o como borrador.
     *
     * @return mixed
     */
    public function getBorradorFactura()
    {
      $query = $this->db
        ->select('id_factura')
        ->from('facturacion')
        ->where('status', 'b')
        ->order_by('id_factura', 'DESC')
        ->limit(1)->get()->row();

      return count($query) > 0 ? $query->id_factura : null;
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
    if($this->input->get('fserie') != '')
        $sql .= "WHERE lower(serie) LIKE '".mb_strtolower($this->input->get('fserie'), 'UTF-8')."'";

		$query = BDUtil::pagination("SELECT fsf.id_serie_folio, fsf.id_empresa, fsf.serie, fsf.no_aprobacion, fsf.folio_inicio,
					fsf.folio_fin, fsf.leyenda, fsf.leyenda1, fsf.leyenda2, fsf.ano_aprobacion, e.nombre_fiscal AS empresa
				FROM facturacion_series_folios AS fsf
				INNER JOIN empresas AS e ON e.id_empresa = fsf.id_empresa
        {$sql}
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
				fsf.folio_fin, fsf.leyenda, fsf.leyenda1, fsf.leyenda2, fsf.ano_aprobacion, fsf.es_nota_credito, e.nombre_fiscal AS empresa')
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
    $data = array(
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

        if(isset($_POST['fnota_credito']))
            $data['es_nota_credito'] = 't';

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

    if(isset($_POST['fnota_credito']))
      $data['es_nota_credito'] = 't';
    else
      $data['es_nota_credito'] = 'f';

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
    public function getFacEmpresasAjax()
    {
        $sql = '';
        $res = $this->db->query("
            SELECT e.id_empresa, e.nombre_fiscal, e.cer_caduca, e.cfdi_version, e.cer_org, e.calle, e.colonia, e.cp, e.estado, e.localidad, e.municipio, e.pais,
                  e.no_exterior, e.no_interior, e.rfc
            FROM empresas AS e
            WHERE lower(nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' AND
                  rfc != '' AND
                  calle != '' AND
                  no_exterior != '' AND
                  colonia != '' AND
                  localidad != '' AND
                  municipio != '' AND
                  estado != '' AND
                  regimen_fiscal != '' AND
                  cer_org != '' AND
                  cer != '' AND
                  key_path != '' AND
                  pass != '' AND
                  cfdi_version != '' AND
                  status = true
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

    public function getRPF()
    {
      $sql = '';

       // Filtra por el producto.
      if ($this->input->get('did_producto'))
      {
        $sql .= "WHERE fp.id_clasificacion = " . $_GET['did_producto'];
      }

      //Filtro de fecha.
      if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '')
        $sql .= " AND Date(f.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
      elseif($this->input->get('ffecha1') != '')
        $sql .= " AND Date(f.fecha) = '".$this->input->get('ffecha1')."'";
      elseif($this->input->get('ffecha2') != '')
        $sql .= " AND Date(f.fecha) = '".$this->input->get('ffecha2')."'";

      if ($this->input->get('fid_cliente') != '')
      {
        $sql .= " AND f.id_cliente = " . $this->input->get('fid_cliente');
      }

      if ($this->input->get('did_empresa') != '')
      {
        $sql .= " AND f.id_empresa = " . $this->input->get('did_empresa');
      }

      // filtra por pagadas
      if (isset($_GET['dpagadas']))
      {
        $sql .= " AND f.status = 'pa'";
      }

      // filtra por las que esten pendientes y pagadas.
      else
      {
        $sql .= " AND f.status != 'ca'";
      }

      $query = $this->db->query(
          "SELECT f.id_factura, DATE(f.fecha) as fecha, f.serie, f.folio, c.nombre_fiscal as cliente,
                  SUM(cantidad) as cantidad, fp.precio_unitario,
                  SUM(fp.importe) as importe
          FROM facturacion f
          INNER JOIN facturacion_productos fp ON fp.id_factura = f.id_factura
          INNER JOIN clientes c ON c.id_cliente= f.id_cliente
          $sql
          GROUP BY f.id_factura, f.fecha, f.serie, f.folio, c.nombre_fiscal, fp.precio_unitario
          ORDER BY f.fecha ASC");

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
    /**
     * Reportes Productos Facturados.
     *
     * @return void
     */
    public function prodfact_pdf()
    {
      if (isset($_GET['did_producto']))
      {
        $facturas = $this->getRPF();

        $this->load->model('empresas_model');
        $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

        $this->load->library('mypdf');
        // Creación del objeto de la clase heredada
        $pdf = new MYpdf('P', 'mm', 'Letter');
        $pdf->show_head = true;

        if ($empresa['info']->logo !== '')
          $pdf->logo = $empresa['info']->logo;

        $pdf->titulo1 = $empresa['info']->nombre_fiscal;
        $pdf->titulo2 = "Reporte Productos Facturados";

        $pdf->titulo3 = "{$_GET['dproducto']} \n";
        if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
            $pdf->titulo3 .= "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
        elseif (!empty($_GET['ffecha1']))
            $pdf->titulo3 .= "Del ".$_GET['ffecha1'];
        elseif (!empty($_GET['ffecha2']))
            $pdf->titulo3 .= "Del ".$_GET['ffecha2'];

        $pdf->AliasNbPages();
        // $links = array('', '', '', '');
        $pdf->SetY(30);
        $aligns = array('C', 'C', 'L', 'R','R', 'R');
        $widths = array(20, 17, 108, 15, 22, 22);
        $header = array('Fecha', 'Serie/Folio', 'Cliente', 'Cantidad', 'Precio', 'Importe');

        $cantidad = 0;
        $importe = 0;
        $promedio = 0;

        foreach($facturas as $key => $item)
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

            $datos = array(
              $item->fecha,
              $item->serie.'-'.$item->folio,
              $item->cliente,
              $item->cantidad,
              String::formatoNumero($item->precio_unitario, 2, '$', false),
              String::formatoNumero($item->importe, 2, '$', false)
            );

            $cantidad += floatval($item->cantidad);
            $importe  += floatval($item->importe);

            $pdf->SetX(6);
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->Row($datos, false);
        }

        $pdf->SetX(6);
        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->Row(array('', '', '',
            $cantidad,
            $cantidad == 0 ? 0 : String::formatoNumero($importe/$cantidad, 2, '$', false),
            String::formatoNumero($importe, 2, '$', false) ), true);

        $pdf->Output('Reporte_Productos_Facturados.pdf', 'I');
      }
    }

    /**
     * Reporte compras x cliente
     *
     * @return
     */
    public function getRVentascData()
    {
      $sql = '';

      //Filtros para buscar
      $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
      $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
      $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

      if($this->input->get('fid_producto') != ''){
        $sql .= " AND cp.id_producto = ".$this->input->get('fid_producto');
      }
      $this->load->model('empresas_model');
      $client_default = $this->empresas_model->getDefaultEmpresa();
      $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
      $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
      if($this->input->get('did_empresa') != ''){
        $sql .= " AND c.id_empresa = '".$this->input->get('did_empresa')."'";
      }
      $tipo_factura = array('', '');
      if($this->input->get('dtipo_factura') != '')
        $tipo_factura = array(" AND f.is_factura='".$this->input->get('dtipo_factura')."'", " AND is_factura='".$this->input->get('dtipo_factura')."'");

      $sql_clientes = '';
      if(is_array($this->input->get('ids_clientes')))
        $sql_clientes = " AND id_cliente IN(".implode(',', $this->input->get('ids_clientes')).")";

      $this->load->model('cuentas_cobrar_model');
      $response = $this->cuentas_cobrar_model->getEstadoCuentaData($sql_clientes, true, true, $tipo_factura);

      return $response;
    }
    /**
    * Reporte compras x cliente pdf
    */
    public function getRVentascPdf(){
      $res = $this->getRVentascData();

      $con_mov = $this->input->get('dcon_mov')=='si'? false: true;

      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');

      if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;

      $pdf->titulo1 = $empresa['info']->nombre_fiscal;
      $pdf->titulo2 = 'Ventas por Cliente';
      $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
      // $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
      $pdf->AliasNbPages();
      // $pdf->AddPage();
      $pdf->SetFont('Arial','',8);

      $aligns = array('L', 'L', 'R', 'L', 'R', 'R', 'R', 'R', 'R');
      $widths = array(20, 11, 15, 40, 23, 23, 23, 23, 23);
      $header = array('Fecha', 'Serie', 'Folio', 'Concepto', 'Cantidad', 'Neto', 'Impuesto', 'Total', 'Saldo');
      $links = array('', '', '', '', '', '', '', '', '');

      $total_saldo_cliente = 0;
      foreach($res as $key => $item){
        if (count($item->facturas) > 0 || $con_mov)
        {
          $total_subtotal = 0;
          $total_impuesto = 0;
          $total_total = 0;
          $total_cantidad = 0;
          $total_saldo = 0;

          if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
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

          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(20, 170));
          $pdf->Row(array('CLIENTE:', $item->cuenta_cpi), false, false);
          $pdf->SetXY(6, $pdf->GetY()-2);
          $pdf->Row(array('NOMBRE:', $item->nombre_fiscal), false, false);

          $pdf->SetXY(6, $pdf->GetY()+3);

          foreach ($item->facturas as $keyf => $factura)
          {
            $total_subtotal += $factura->subtotal;
            $total_saldo += $factura->saldo;
            $total_cantidad += $factura->cantidad_productos;
            $total_impuesto += $factura->importe_iva;
            $total_total += $factura->total;

            $links[3] = base_url('panel/facturacion/rventasc_detalle_pdf?venta='.$factura->id_factura.'&did_empresa='.$empresa['info']->id_empresa);
            $datos = array(String::fechaATexto($factura->fecha, '/c'),
                    $factura->serie,
                    $factura->folio,
                    $factura->concepto,
                    String::formatoNumero($factura->cantidad_productos, 2, '', false),
                    String::formatoNumero($factura->subtotal, 2, '', false),
                    String::formatoNumero($factura->importe_iva, 2, '', false),
                    String::formatoNumero($factura->total, 2, '', false),
                    String::formatoNumero( ($factura->saldo) , 2, '', false),
                    // String::fechaATexto($factura->fecha_vencimiento, '/c'),
                  );

            $pdf->SetXY(6, $pdf->GetY()-1);
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->SetMyLinks($links);
            $pdf->Row($datos, false, false);
          }
          $pdf->SetMyLinks(array());

          $pdf->SetX(93);
          $pdf->SetFont('Arial','B',8);
          // $pdf->SetTextColor(255,255,255);
          $pdf->SetAligns(array('R', 'R', 'R', 'R', 'R', 'R'));
          $pdf->SetWidths(array(23, 23, 23, 23, 23));
          $pdf->Row(array(
              String::formatoNumero($total_cantidad, 2, '', false),
              String::formatoNumero($total_subtotal, 2, '', false),
              String::formatoNumero($total_impuesto, 2, '', false),
              String::formatoNumero($total_total, 2, '', false),
              String::formatoNumero($total_saldo, 2, '', false)), false);

          // $total_saldo_cliente += $saldo_cliente;
        }
      }

      // $pdf->SetXY(66, $pdf->GetY()+4);
      // $pdf->Row(array('TOTAL SALDO DE CLIENTES', String::formatoNumero( $total_saldo_cliente , 2, '', false)), false);


      $pdf->Output('reporte_ventas.pdf', 'I');
    }

    /**
     * Reporte compras x cliente
     *
     * @return
     */
    public function getRVentasDetalleData()
    {
      $sql = '';

      //Filtros para buscar
      $response['factura'] = $this->db->query("SELECT *
        FROM facturacion AS f INNER JOIN clientes AS c ON c.id_cliente = f.id_cliente
        WHERE f.id_factura = ".floatval($this->input->get('venta')))->row();
      $response['productos'] = $this->db->query("SELECT * FROM facturacion_productos WHERE id_factura = ".floatval($this->input->get('venta')))->result();

      return $response;
    }
    /**
    * Reporte compras x cliente pdf
    */
    public function getRVentasDetallePdf(){
      $res = $this->getRVentasDetalleData();

      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');

      if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;

      $pdf->titulo1 = $empresa['info']->nombre_fiscal;
      $pdf->titulo2 = 'Detalle de venta';
      // $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
      // $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
      $pdf->AliasNbPages();
      $pdf->AddPage();
      $pdf->SetFont('Arial','',8);

      $aligns = array('L', 'R', 'C', 'L', 'L');
      $widths = array(25, 25, 25, 50, 70);
      $header = array('Serie', 'Folio', 'Fecha', 'Concepto', 'Cliente');

      $pdf->SetFont('Arial','B',8);
      $pdf->SetTextColor(255,255,255);
      $pdf->SetFillColor(160,160,160);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($header, true);

      $pdf->SetFont('Arial','',8);
      $pdf->SetTextColor(0,0,0);
      $pdf->Row(array(
          $res['factura']->serie,
          $res['factura']->folio,
          String::fechaATexto($res['factura']->fecha, '/c'),
          ($res['factura']->is_factura=='t'? 'FACTURA ELECTRONICA': 'REMISION'),
          $res['factura']->nombre_fiscal,
          ), false);

      $aligns = array('L', 'R', 'R', 'R', 'R', 'R');
      $widths = array(70, 25, 25, 25, 25, 25);
      $header = array('Nombre', 'Cantidad', 'Precio', 'Neto', 'Impuesto', 'Total');

      $total_cantidad = 0;
      foreach($res['productos'] as $key => $item){

        if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetFont('Arial','B',8);
          $pdf->SetTextColor(255,255,255);
          $pdf->SetFillColor(160,160,160);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('Arial','',8);
        $pdf->SetTextColor(0,0,0);

        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row(array(
            $item->descripcion,
            String::formatoNumero($item->cantidad, 2, '', false),
            String::formatoNumero($item->precio_unitario, 2, '', false),
            String::formatoNumero($item->importe, 2, '', false),
            String::formatoNumero($item->iva, 2, '', false),
            String::formatoNumero(($item->importe+$item->iva), 2, '', false)), false);
        $total_cantidad += $item->cantidad;
      }

      $pdf->SetX(80);
      $pdf->SetFont('Arial','B',8);
      // $pdf->SetTextColor(255,255,255);
      $pdf->SetAligns(array('R', 'R', 'R', 'R', 'R', 'R'));
      $pdf->SetWidths(array(25, 25, 25, 25, 25));
      $pdf->Row(array(
          String::formatoNumero($total_cantidad, 2, '', false),
          '',
          String::formatoNumero($res['factura']->subtotal, 2, '', false),
          String::formatoNumero($res['factura']->importe_iva, 2, '', false),
          String::formatoNumero($res['factura']->total, 2, '', false)), false);


      $pdf->Output('reporte_ventas.pdf', 'I');
    }


    /*
    |------------------------------------------------------------------------
    | FACTURA PDF
    |------------------------------------------------------------------------
    */

    public function generaFacturaPdf($idFactura, $path = null)
    {
        include(APPPATH.'libraries/phpqrcode/qrlib.php');

        $factura = $this->getInfoFactura($idFactura);

        $xml = simplexml_load_string(str_replace(array('cfdi:', 'tfd:'), '', $factura['info']->xml));
        if($xml === false)
          return false;
        // echo "<pre>";
        //   var_dump($factura, $xml);
        // echo "</pre>";exit;

        $this->load->library('mypdf');

        // Creación del objeto de la clase heredada
        $pdf = new MYpdf('P', 'mm', 'Letter');

        $pdf->show_head = false;

        $pdf->AliasNbPages();
        $pdf->AddPage();



        $pdf->SetXY(0, 0);
        /////////////////////////////////////
        // Folio Fisca, CSD, Lugar y Fecha //
        /////////////////////////////////////

        $pdf->SetFont('helvetica','B', 9);
        $pdf->SetFillColor(242, 242, 242);
        $pdf->SetTextColor(0, 171, 72);
        $pdf->SetXY(0, $pdf->GetY() + 2);
        $pdf->Cell(108, 4, "Folio Fiscal:", 0, 0, 'R', 1);

        $pdf->SetXY(0, $pdf->GetY());
        $pdf->Cell(50, 4, ($factura['info']->id_nc === null ? '                 Factura' : '                 Nota de Crédito').':  '.($factura['info']->serie.$factura['info']->folio) , 0, 0, 'L', 1);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(0, $pdf->GetY() + 6);
        $pdf->Cell(108, 4, $xml->Complemento->TimbreFiscalDigital[0]['UUID'], 0, 0, 'C', 0);

        $pdf->SetTextColor(0, 171, 72);
        $pdf->SetXY(0, $pdf->GetY() + 4);
        $pdf->Cell(108, 4, "No de Serie del Certificado del CSD:", 0, 0, 'R', 1);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(0, $pdf->GetY() + 4);
        $pdf->Cell(108, 4, $xml->Complemento->TimbreFiscalDigital[0]['noCertificadoSAT'], 0, 0, 'C', 0);

        $pdf->SetFillColor(242, 242, 242);
        $pdf->SetTextColor(0, 171, 72);
        $pdf->SetXY(0, $pdf->GetY() + 4);
        $pdf->Cell(108, 4, "Lugar. fecha y hora de emisión:", 0, 0, 'R', 1);

        $pdf->SetFont('helvetica','', 9);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(0, $pdf->GetY() + 4);

        $municipio   = strtoupper($xml->Emisor->DomicilioFiscal[0]['municipio']);
        $estado = strtoupper($xml->Emisor->DomicilioFiscal[0]['estado']);
        $fecha = explode('T', $xml[0]['fecha']);
        $fecha = String::fechaATexto($fecha[0]);

        $pdf->Cell(108, 4, "{$municipio}, {$estado} | {$fecha}", 0, 0, 'R', 0);


        // $pdf->SetXY(30, 2);

        //////////////////////////
        // Rfc y Regimen Fiscal //
        //////////////////////////

        // 0, 171, 72 = verde

        $pdf->SetFont('helvetica','B', 9);
        // $pdf->SetFillColor(0, 171, 72);
        $pdf->SetTextColor(255, 255, 255);
        // $pdf->SetXY(0, 0);
        // $pdf->Cell(108, 15, "Factura Electrónica (CFDI)", 0, 0, 'C', 1);

        // $pdf->SetTextColor(0, 0, 0);
        // $pdf->SetXY(0, $pdf->GetY());
        // $pdf->Cell(108, 4, "RFC: {$xml->Emisor[0]['rfc']}", 0, 0, 'C', 0);

        // $pdf->SetFont('helvetica','B', 12);
        $pdf->SetFillColor(242, 242, 242);
        $pdf->SetTextColor(0, 171, 72);
        $pdf->SetXY(0, $pdf->GetY() + 4);
        $pdf->Cell(108, 4, "Emisor:", 0, 0, 'L', 1);

        $pdf->SetFont('helvetica','', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(0, $pdf->GetY() + 4);

        $pdf->SetX(0);
        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(19, 93));
        $pdf->Row(array('RFC:', $xml->Emisor[0]['rfc']), false, false, null, 2, 1);
        $pdf->SetWidths(array(19, 196));
        $pdf->SetX(0);
        $pdf->Row(array('NOMBRE:', $xml->Emisor[0]['nombre']), false, false, null, 2, 1);
        $pdf->SetX(0);
        $pdf->Row(array('DOMICILIO:', $xml->Emisor->DomicilioFiscal[0]['calle'].' No. '.$xml->Emisor->DomicilioFiscal[0]['noExterior'].
                                              ((isset($xml->Emisor->DomicilioFiscal[0]['noInterior'])) ? ' Int. '.$xml->Emisor->DomicilioFiscal[0]['noInterior'] : '') ), false, false, null, 2, 1);
        $pdf->SetWidths(array(19, 83, 19, 83));
        $pdf->SetX(0);
        $pdf->Row(array('COLONIA:', $xml->Emisor->DomicilioFiscal[0]['colonia'], 'LOCALIDAD:', $xml->Emisor->DomicilioFiscal[0]['localidad']), false, false, null, 2, 1);
        $pdf->SetWidths(array(19, 65, 11, 65, 11, 40));
        $pdf->SetX(0);
        $pdf->Row(array('ESTADO:', $xml->Emisor->DomicilioFiscal[0]['estado'], 'PAIS:', $xml->Emisor->DomicilioFiscal[0]['pais'], 'CP:', $xml->Emisor->DomicilioFiscal[0]['codigoPostal']), false, false, null, 2, 1);

        $end_y = $pdf->GetY();

        //////////
        // Logo //
        //////////
        $logo = (file_exists($factura['info']->empresa->logo)) ? $factura['info']->empresa->logo : '' ;
        if($logo != '')
          $pdf->Image($logo, 115, 2, 0, 21);
        $pdf->SetXY(0, 25);


        $pdf->SetFont('helvetica','b', 9);
        $pdf->SetFillColor(242, 242, 242);
        $pdf->SetTextColor(0, 171, 72);
        $pdf->SetXY(109, $pdf->GetY() + 4);
        $pdf->Cell(108, 4, "Régimen Fiscal:", 0, 0, 'R', 1);

        $pdf->SetFont('helvetica','', 9);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(109, $pdf->GetY() + 4);
        $pdf->MultiCell(108, 4, $xml->Emisor->RegimenFiscal[0]['Regimen'], 0, 'C', 0);

        //////////////////
        // domicilioEmisor //
        //////////////////

        // $domicilioEmisor = '';
        // $domicilioEmisor .= (isset($xml->Emisor->DomicilioFiscal[0]['calle'])) ? $xml->Emisor->DomicilioFiscal[0]['calle'] : '';
        // $domicilioEmisor .= (isset($xml->Emisor->DomicilioFiscal[0]['noExterior'])) ? ' #'.$xml->Emisor->DomicilioFiscal[0]['noExterior'] : '';
        // $domicilioEmisor .= (isset($xml->Emisor->DomicilioFiscal[0]['noInterior'])) ? ' Int. '.$xml->Emisor->DomicilioFiscal[0]['noInterior'] : '';
        // $domicilioEmisor .= (isset($xml->Emisor->DomicilioFiscal[0]['colonia'])) ? ', '.$xml->Emisor->DomicilioFiscal[0]['colonia'] : '';
        // $domicilioEmisor .= (isset($xml->Emisor->DomicilioFiscal[0]['localidad'])) ? ', '.$xml->Emisor->DomicilioFiscal[0]['localidad'] : '';
        // $domicilioEmisor .= (isset($xml->Emisor->DomicilioFiscal[0]['municipio'])) ? ', '.$xml->Emisor->DomicilioFiscal[0]['municipio'] : '';
        // $domicilioEmisor .= (isset($xml->Emisor->DomicilioFiscal[0]['estado'])) ? ', '.$xml->Emisor->DomicilioFiscal[0]['estado'] : '';

        // $pdf->SetFont('helvetica','B', 9);
        // $pdf->SetFillColor(242, 242, 242);
        // $pdf->SetTextColor(0, 171, 72);
        // $pdf->SetXY(0, $pdf->GetY() + 4);
        // $pdf->Cell(216, 4, "Domicilio:", 0, 0, 'L', 1);

        // $pdf->SetFont('helvetica','', 9);
        // $pdf->SetTextColor(0, 0, 0);
        // $pdf->SetXY(0, $pdf->GetY() + 4);
        // $pdf->Cell(216, 4, $domicilioEmisor, 0, 0, 'C', 0);

        //////////////////
        // Datos Receptor //
        //////////////////
        $pdf->setY($end_y);
        $domicilioReceptor = '';
        $domicilioReceptor .= (isset($xml->Receptor->Domicilio[0]['calle']) ? $xml->Receptor->Domicilio[0]['calle'] : '');
        $domicilioReceptor .= (isset($xml->Receptor->Domicilio[0]['noExterior']) ? ' #'.$xml->Receptor->Domicilio[0]['noExterior'] : '');
        $domicilioReceptor .= (isset($xml->Receptor->Domicilio[0]['noInterior'])) ? ' Int. '.$xml->Receptor->Domicilio[0]['noInterior'] : '';
        $domicilioReceptor .= (isset($xml->Receptor->Domicilio[0]['colonia']) ? ', '.$xml->Receptor->Domicilio[0]['colonia'] : '');
        $domicilioReceptor .= (isset($xml->Receptor->Domicilio[0]['localidad']) ? ', '.$xml->Receptor->Domicilio[0]['localidad'] : '');
        $domicilioReceptor .= (isset($xml->Receptor->Domicilio[0]['municipio'])) ? ', '.$xml->Receptor->Domicilio[0]['municipio'] : '';
        $domicilioReceptor .= (isset($xml->Receptor->Domicilio[0]['estado']) ? ', '.$xml->Receptor->Domicilio[0]['estado'] : '');

        $pdf->SetFillColor(0, 171, 72);
        $pdf->SetXY(0, $pdf->GetY() + 4);
        $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

        $pdf->SetFont('helvetica','B', 9);
        $pdf->SetFillColor(242, 242, 242);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(0, $pdf->GetY() + 1);
        $pdf->Cell(216, 4, "Receptor:", 0, 0, 'L', 1);

        $pdf->SetFont('helvetica','', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(0, $pdf->GetY() + 4);


        $pdf->SetX(0);
        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(19, 93));
        $pdf->Row(array('RFC:', $xml->Receptor[0]['rfc']), false, false, null, 2, 1);
        $pdf->SetWidths(array(19, 196));
        $pdf->SetX(0);
        $pdf->Row(array('NOMBRE:', $xml->Receptor[0]['nombre']), false, false, null, 2, 1);
        $pdf->SetX(0);
        $pdf->Row(array('DOMICILIO:', (isset($xml->Receptor->Domicilio[0]['calle']) ? $xml->Receptor->Domicilio[0]['calle'] : '').
                  ' No. '.(isset($xml->Receptor->Domicilio[0]['noExterior']) ? $xml->Receptor->Domicilio[0]['noExterior'] : '').
                  ((isset($xml->Receptor->Domicilio[0]['noInterior'])) ? ' Int. '.$xml->Receptor->Domicilio[0]['noInterior'] : '') ), false, false, null, 2, 1);
        $pdf->SetWidths(array(19, 83, 19, 83));
        $pdf->SetX(0);
        $pdf->Row(array('COLONIA:', (isset($xml->Receptor->Domicilio[0]['colonia']) ? $xml->Receptor->Domicilio[0]['colonia'] : ''),
                  'LOCALIDAD:', (isset($xml->Receptor->Domicilio[0]['localidad']) ? $xml->Receptor->Domicilio[0]['localidad'] : '')), false, false, null, 2, 1);
        $pdf->SetWidths(array(19, 65, 11, 65, 11, 40));
        $pdf->SetX(0);
        $pdf->Row(array('ESTADO:', (isset($xml->Receptor->Domicilio[0]['estado']) ? $xml->Receptor->Domicilio[0]['estado'] : ''),
                'PAIS:', (isset($xml->Receptor->Domicilio[0]['pais']) ? $xml->Receptor->Domicilio[0]['pais'] : ''),
                'CP:', (isset($xml->Receptor->Domicilio[0]['codigoPostal']) ? $xml->Receptor->Domicilio[0]['codigoPostal'] : '') ), false, false, null, 2, 1);



        if (isset($factura['carta_porte']))
        {
          // Remitente
          $pdf->SetFillColor(242, 242, 242);
          $pdf->SetTextColor(0, 171, 72);
          $pdf->SetXY(0, $pdf->GetY() + 4);
          $y_aux = $pdf->GetY();
          $pdf->Cell(108, 4, "Remitente:", 0, 0, 'L', 1);

          $pdf->SetFont('helvetica','', 8);
          $pdf->SetTextColor(0, 0, 0);
          $pdf->SetXY(0, $pdf->GetY() + 4);

          $pdf->SetX(0);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(19, 93));
          $pdf->Row(array('RFC:', $factura['carta_porte']['remitente'][0]->rfc), false, false, null, 2, 1);
          $pdf->SetWidths(array(19, 196));
          $pdf->SetX(0);
          $pdf->Row(array('NOMBRE:', $factura['carta_porte']['remitente'][0]->nombre), false, false, null, 2, 1);
          $pdf->SetX(0);
          $pdf->Row(array('DOMICILIO:', $factura['carta_porte']['remitente'][0]->direccion ), false, false, null, 2, 1);

          // $pdf->SetAligns(array('L', 'L', 'L', 'L'));
          $pdf->SetWidths(array(80, 50, 35, 45));
          $pdf->SetX(0);
          $pdf->Row(array(
            'OPERADOR: ' . $factura['carta_porte']['remitente'][0]->chofer,
            'MARCA: ' . $factura['carta_porte']['remitente'][0]->marca,
            'MODELO:' . $factura['carta_porte']['remitente'][0]->modelo,
            'PLACAS:' . $factura['carta_porte']['remitente'][0]->placas
          ), false, false, null, 2, 1);

          $end_y = $pdf->GetY();

          // Destinatario
          $pdf->SetFont('helvetica','B', 9);
          $pdf->SetFillColor(242, 242, 242);
          $pdf->SetTextColor(0, 0, 0);
          $pdf->SetXY(110, $y_aux);
          $pdf->Cell(216, 4, "Destinatario:", 0, 0, 'L', 1);

          $pdf->SetFont('helvetica','', 8);
          $pdf->SetTextColor(0, 0, 0);
          $pdf->SetXY(110, $pdf->GetY() + 4);

          $pdf->SetX(110);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(19, 93));
          $pdf->Row(array('RFC:', $factura['carta_porte']['destinatario'][0]->rfc), false, false, null, 2, 1);
          $pdf->SetWidths(array(19, 196));
          $pdf->SetX(110);
          $pdf->Row(array('NOMBRE:', $factura['carta_porte']['destinatario'][0]->nombre), false, false, null, 2, 1);
          $pdf->SetX(110);
          $pdf->Row(array('DOMICILIO:', $factura['carta_porte']['destinatario'][0]->direccion ), false, false, null, 2, 1);
        }

        // $pdf->Cell(216, 4, "Nombre: {$xml->Receptor[0]['nombre']} RFC: {$xml->Receptor[0]['rfc']}", 0, 0, 'L', 0);

        // $pdf->SetFont('helvetica','', 9);
        // $pdf->SetTextColor(0, 0, 0);
        // $pdf->SetXY(0, $pdf->GetY() + 4);
        // $pdf->Cell(216, 4, "Domicilio: {$domicilioReceptor}", 0, 0, 'L', 0);

        ///////////////
        // Productos //
        ///////////////

        $pdf->SetFillColor(0, 171, 72);
        $pdf->SetXY(0, $pdf->GetY() + 5);
        $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

        $pdf->SetXY(0, $pdf->GetY());
        $aligns = array('C', 'C', 'C', 'C','C');
        $aligns2 = array('C', 'C', 'L', 'R','R');
        $widths = array(30, 35, 91, 30, 30);
        $header = array('Cantidad', 'Unidad de Medida', 'Descripcion', 'Precio Unitario', 'Importe');

        $conceptos = current($xml->Conceptos);
        if(count($conceptos) == 0)
          $conceptos = array($conceptos);
        elseif(count($conceptos) == 1){
          $conceptos = current($conceptos);
          $conceptos = array($conceptos);
        }

        // for ($i=0; $i < 30; $i++)
        //   $conceptos[] = $conceptos[$i];

        // echo "<pre>";
        //   var_dump($conceptos, is_array($conceptos));
        // echo "</pre>";exit;

        if (! is_array($conceptos))
          $conceptos = array($conceptos);

        $pdf->limiteY = 250;

        $pdf->setY($pdf->GetY() + 1);
        foreach($conceptos as $key => $item)
        {
          $band_head = false;

          if($pdf->GetY() >= $pdf->limiteY || $key === 0) //salta de pagina si exede el max
          {
            if($key > 0) $pdf->AddPage();

            $pdf->SetFont('Arial', 'B', 8);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFillColor(242, 242, 242);
            $pdf->SetX(0);
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->Row($header, true, true, null, 2, 1);
          }

          $pdf->SetFont('Arial', '', 8);
          $pdf->SetTextColor(0,0,0);

          $pdf->SetX(0);
          $pdf->SetAligns($aligns2);
          $pdf->SetWidths($widths);
          $pdf->Row(array(
            String::formatoNumero($item[0]['cantidad'], 2, ''),
            $item[0]['unidad'],
            $item[0]['descripcion'],
            String::formatoNumero($item[0]['valorUnitario'], 2, '$', false),
            String::formatoNumero($item[0]['importe'], 2, '$', false),
          ), false, true, null, 2, 1);
        }

        /////////////
        // Totales //
        /////////////

        if($pdf->GetY() + 30 >= $pdf->limiteY) //salta de pagina si exede el max
            $pdf->AddPage();

        // Traslados | IVA
        $ivas = current($xml->Impuestos->Traslados);
        if(count($ivas) == 1)
          $ivas = current($ivas);

        if ( ! is_array($ivas))
        {
          $ivas = array($ivas);
        }

        $traslado11 = 0;
        $traslado16 = 0;
        foreach ($ivas as $key => $iva)
        {
          if ($iva[0]['tasa'] == '11')
            $traslado11 = $iva[0]['importe'];
          elseif ($iva[0]['tasa'] == '16')
            $traslado16 = $iva[0]['importe'];
        }

        $pdf->SetFillColor(0, 171, 72);
        $pdf->SetXY(0, $pdf->GetY());
        $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

        $h = 25 - ($traslado11 == 0 ? 5 : 0);
        $h = $h - ($xml->Impuestos->Retenciones->Retencion[0]['importe'] == 0 ? 5 : 0);
        $h += 6;

        $pdf->SetFillColor(242, 242, 242);
        $pdf->SetXY(0, $pdf->GetY() + 1);
        $pdf->Cell(156, $h, "", 1, 0, 'L', 1);

        $pdf->SetFont('helvetica','B', 9);
        $pdf->SetXY(1, $pdf->GetY() + 1);
        $pdf->Cell(154, 4, "Total con letra:", 0, 0, 'L', 1);

        $pdf->SetFont('helvetica', '', 10);
        $pdf->SetXY(0, $pdf->GetY() + 4);
        $pdf->MultiCell(156, 6, $factura['info']->total_letra, 0, 'C', 0);

        $pdf->Line(1, $pdf->GetY(), 200, $pdf->GetY());

        $pdf->SetFont('helvetica','B', 9);
        $pdf->SetXY(1, $pdf->GetY()+1);
        $pdf->Cell(78, 4, 'Forma de Pago: '.$xml[0]['formaDePago'], 0, 0, 'L', 1);

        $pdf->SetFont('helvetica','B', 9);
        $pdf->SetXY(78, $pdf->GetY());
        $pdf->Cell(78, 4, 'Condicion de Pago: '.($factura['info']->condicion_pago=='co'? 'Contado': 'Credito'), 0, 0, 'L', 1);

        $pdf->SetFont('helvetica','B', 9);
        $pdf->SetXY(1, $pdf->GetY()+5);
        $pdf->Cell(78, 4, "Metodo de Pago: {$xml[0]['metodoDePago']}", 0, 0, 'L', 1);

        $pdf->SetFont('helvetica','B', 9);
        $pdf->SetXY(78, $pdf->GetY());
        $pdf->Cell(76, 4, "Cuenta de Pago: {$factura['info']->metodo_pago_digitos}", 0, 0, 'L', 1);

        $pdf->SetFont('helvetica','B', 10);
        $pdf->SetXY(156, $pdf->GetY() - 16);
        $pdf->Cell(30, 5, "Subtotal", 1, 0, 'C', 1);

        $pdf->SetXY(186, $pdf->GetY());
        $pdf->Cell(30, 5, String::formatoNumero($xml[0]['subTotal'], 2, '$', false), 1, 0, 'R', 1);

        // Pinta traslados, retenciones

        if ($traslado11 != 0)
        {
          $pdf->SetXY(156, $pdf->GetY() + 5);
          $pdf->Cell(30, 5, "IVA(11%)", 1, 0, 'C', 1);

          $pdf->SetXY(186, $pdf->GetY());
          $pdf->Cell(30, 5,String::formatoNumero($traslado11, 2, '$', false), 1, 0, 'R', 1);
        }

        $pdf->SetXY(156, $pdf->GetY() + 5);
        $pdf->Cell(30, 5, "IVA(16%)", 1, 0, 'C', 1);

        $pdf->SetXY(186, $pdf->GetY());
        $pdf->Cell(30, 5,String::formatoNumero($traslado16, 2, '$', false), 1, 0, 'R', 1);

        if ($xml->Impuestos->Retenciones->Retencion[0]['importe'] != 0)
        {
          $pdf->SetXY(156, $pdf->GetY() + 5);
          $pdf->Cell(30, 5, "IVA Retenido", 1, 0, 'C', 1);

          $pdf->SetXY(186, $pdf->GetY());
          $pdf->Cell(30, 5,String::formatoNumero($xml->Impuestos->Retenciones->Retencion[0]['importe'], 2, '$', false), 1, 0, 'R', 1);
        }

        $pdf->SetXY(156, $pdf->GetY() + 5);
        $pdf->Cell(30, 5, "TOTAL", 1, 0, 'C', 1);

        $pdf->SetXY(186, $pdf->GetY());
        $pdf->Cell(30, 5,String::formatoNumero($xml[0]['total'], 2, '$', false), 1, 0, 'R', 1);

        ///////////////////
        // Observaciones //
        ///////////////////

        $pdf->SetXY(0, $pdf->GetY() + 5);

        $width = (($pdf->GetStringWidth($factura['info']->observaciones) / 216) * 8) + 9;

        if($pdf->GetY() + $width >= $pdf->limiteY) //salta de pagina si exede el max
            $pdf->AddPage();

        if ( ! empty($factura['info']->observaciones))
        {
            $pdf->SetX(0);
            $pdf->SetFont('helvetica','B', 10);
            $pdf->SetAligns(array('L'));
            $pdf->SetWidths(array(216));
            $pdf->Row(array('Observaciones'), true);

            $pdf->SetFont('helvetica','', 9);
            $pdf->SetXY(0, $pdf->GetY());
            $pdf->SetAligns(array('L'));
            $pdf->SetWidths(array(216));
            $pdf->Row(array($factura['info']->observaciones), true, 1);
        }

        if (isset($xml[0]['TipoCambio']))
        {
          if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
              $pdf->AddPage();
          $pdf->SetFont('helvetica', 'B', 8);
          $pdf->SetXY(10, $pdf->GetY() + 5);
          $pdf->SetAligns(array('L'));
          $pdf->SetWidths(array(196));
          $pdf->Row(array('Tasa de Cambio: '.String::formatoNumero($xml[0]['TipoCambio'], 4) ), false, 0);
        }else
          $pdf->SetXY(10, $pdf->GetY() + 5);

        ////////////////////
        // Timbrado Datos //
        ////////////////////

        if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
            $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY(10, $pdf->GetY());
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(196));
        $pdf->Row(array('Sello Digital del CFDI:'), false, 0);

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetY($pdf->GetY() - 3);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(196));
        $pdf->Row(array($xml->Complemento->TimbreFiscalDigital[0]['selloCFD']), false, 0);

        if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
            $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY(10, $pdf->GetY() - 2);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(196));
        $pdf->Row(array('Sello Digital del SAT:'), false, 0);

        $pdf->SetFont('helvetica', '', 8);
        $pdf->SetY($pdf->GetY() - 3);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(196));
        $pdf->Row(array($xml->Complemento->TimbreFiscalDigital[0]['selloSAT']), false, 0);

        /////////////
        // QR CODE //
        /////////////

        // formato
        // ?re=XAXX010101000&rr=XAXX010101000&tt=1234567890.123456&id=ad662d33-6934-459c-a128-BDf0393f0f44
        // 0000001213.520000

        $total = explode('.', $xml[0]['total']);

        // Obtiene la diferencia de caracteres en la parte entera.
        $diff = 10 - strlen($total[0]);

        // Agrega los 0 faltantes  a la parte entera.
        for ($i=0; $i < $diff; $i++)
          $total[0] = "0{$total[0]}";

        // Si el total no contiene decimales le asigna en la parte decimal 6 ceros.
        if (count($total) === 1)
        {
          $total[1] = '000000';
        }
        else
        {
          // Obtiene la diferencia de caracteres en la parte decimal.
          $diff = 6 - strlen($total[1]);

          // Agregar los 0 restantes en la parte decimal.
          for ($i=0; $i < $diff; $i++)
            $total[1] = "{$total[1]}0";
        }

        $code = "?re={$xml->Emisor[0]['rfc']}";
        $code .= "&rr={$xml->Receptor[0]['rfc']}";
        $code .= "&tt={$total[0]}.{$total[1]}";
        $code .= "&id={$xml->Complemento->TimbreFiscalDigital[0]['UUID']}";

        // echo "<pre>";
        //   var_dump($code, $total, $diff);
        // echo "</pre>";exit;

        QRcode::png($code, APPPATH.'media/qrtemp.png', 'H', 3);

        if($pdf->GetY() + 50 >= $pdf->limiteY) //salta de pagina si exede el max
            $pdf->AddPage();

        $pdf->SetXY(0, $pdf->GetY());
        $pdf->Image(APPPATH.'media/qrtemp.png', null, null, 40);

        // Elimina el QR generado temporalmente.
        unlink(APPPATH.'media/qrtemp.png');

        ////////////////////
        // Timbrado Datos //
        ////////////////////

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetXY(45, $pdf->GetY() - 39);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(160));
        $pdf->Row(array('Cadena Original del complemento de certificación digital del SAT:'), false, 0);

        $pdf->SetFont('helvetica', '', 8);
        $cadenaOriginalSAT = "||{$xml->Complemento->TimbreFiscalDigital[0]['version']}|{$xml->Complemento->TimbreFiscalDigital[0]['UUID']}|{$xml->Complemento->TimbreFiscalDigital[0]['FechaTimbrado']}|{$xml->Complemento->TimbreFiscalDigital[0]['selloCFD']}|{$xml->Complemento->TimbreFiscalDigital[0]['noCertificadoSAT']}||";
        $pdf->SetXY(45, $pdf->GetY() - 3);
        $pdf->Row(array($cadenaOriginalSAT), false, 0);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(242, 242, 242);
        $pdf->SetTextColor(0, 171, 72);
        $pdf->SetXY(45, $pdf->GetY() + 1);
        $pdf->Cell(80, 6, "No de Serie del Certificado del SAT:", 0, 0, 'R', 1);

        $pdf->SetXY(125, $pdf->GetY());
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['noCertificadoSAT'], 0, 0, 'C', 0);

        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->SetFillColor(242, 242, 242);
        $pdf->SetTextColor(0, 171, 72);
        $pdf->SetXY(45, $pdf->GetY() + 10);
        $pdf->Cell(80, 6, "Fecha y hora de certificación:", 0, 0, 'R', 1);

        $pdf->SetXY(125, $pdf->GetY());
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['FechaTimbrado'], 0, 0, 'C', 0);

        $pdf->SetXY(0, $pdf->GetY()+13);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->Cell(220, 6, 'ESTE DOCUMENTO ES UNA REPRESENTACION IMPRESA DE UN CFDI.', 0, 0, 'C', 0);

        //------------ IMAGEN CANDELADO --------------------

        if($factura['info']->status === 'ca'){
          $pdf->Image(APPPATH.'/images/cancelado.png', 20, 40, 190, 190, "PNG");
        }

        if (isset($factura['carta_porte']))
        {
          $pdf->AliasNbPages();
          $pdf->AddPage();

          $pdf->SetFont('helvetica', '', 9);
          $pdf->SetWidths(array(200));
          $pdf->SetAligns(array('L'));

          $pdf->Row(array('PRIMERA.- Para los efectos del presente contrato de transporte se denomina "Porteador" al transportista y "Remitente" al usuario que contrate el servicio.'), false, false, false);
          $pdf->Row(array('SEGUNDA.- El "Remitente" es responsable de que la información proporcionada al "Porteador" sea veráz y que la documentación que entregue para efectos del transporte sea la correcta.'), false, false, false);
          $pdf->Row(array('TERCERA.- El "Remitente" debe declarar al "Porteador" el tipo de mercancía o efectos de que se trate, peso, medidas y/o número de la carga que entrega para su transporte y en su caso, el valor de la misma. La carga que se entregue a granel será pesada por el "Porteador" en el primer punto donde haya báscula apropiada o en su defecto, aforada en metros cúbicos con la conformidad del "Remitente".'), false, false, false);
          $pdf->Row(array('CUARTA.- Para efectos del transporte, el "Remitente" deberá entregar al "Porteador" los documentos que las leyes y reglamentos que exijan para llevar a cabo el servicio, en caso de no cumplirse con estos requisitos el "Porteador" está obligado a rehusar el transporte de las mercancías.'), false, false, false);
          $pdf->Row(array('QUINTA.- Si por sospecha de falsedad en la declaración del contenido de un bulto el "Porteador" deseare proceder a su reconocimiento, podrá hacerlo ante testigos y con asistencia del "Remitente" o del consignatario. Si este último no concurriere, se solicitará la presencia de un inspector de la Secretaría de Comunicaciones y Transportes y se levantará el acta correspondiente. El "Porteador" tendrá en todo caso la obligación de dejar los bultos en el estado en que se encontraban antes del reconocimiento.'), false, false, false);
          $pdf->Row(array('SEXTA.- El "Porteador" deberá recoger y entregar la carga precisamente en los domicilios que señale el "Remitente", ajustándose a los términos y condiciones convenidos. El "Porteador" solo está obligado a llevar la carga al domicilio del consignatario para su entrega una sola vez. Si ésta no fuere recibida, se dejará aviso de que la mercancía queda a disposición del interesado en las bodegas que indique el "Porteador".'), false, false, false);
          $pdf->Row(array('SEPTIMA.- Si la carga no fuere retirada en los 30 días siguientes a aquel en que hubiere sido puesta a disposición del consignatario, el "Porteador" podrá solicitar la venta en pública subasta con arreglo a lo que dispone el Código de Comercio.'), false, false, false);
          $pdf->Row(array('OCTAVA.- El "Porteador" y el "Remitente" negociarán libremente el precio del servicio, tomando en cuenta su tipo, característicamente de los embarques, volumen, regularidad, clase de carga y sistema de pago.'), false, false, false);
          $pdf->Row(array('NOVENA.- Si el "Remitente" desea que el "Porteador" asuma la responsabilidad por el valor de las mercancías o efectos que él declare y que cubra toda clase de riesgos, inclusive los derivados de caso fortuito o de fuerza mayor, las partes deberán convenir un cargo adicional, equivalente al valor de la prima del seguro que se contrate, el cual se deberá expresar en la carta de porte.'), false, false, false);
          $pdf->Row(array('DECIMA.- Cuando el importe del flete no incluya el cargo adicional, la responsabilidad del "Porteador" queda expresamente limitada a la cantidad equivalente a 15 días del salario mínimo vigente en el Distrito Federal por tonelada o cuando se trate de embarques cuyo peso sea mayor a los 200Kg. Pero menor a los 1000Kg y a 4 días de salario mínimo por remesa cuando se trate de embarques con peso hasta de 200Kg.'), false, false, false);
          $pdf->Row(array('DECIMA PRIMERA.- El precio del transporte deberá pagarse en origen, salvo convenio entre las partes de pago en destino. Cuando el transporte se hubiera concertado "Flete por Cobrar", la entrega de las mercancías o efectos se hará contra el pago del flete y el "Porteador" tendrá derecho a retenerlos mientras no se le cubra el precio convenido.'), false, false, false);
          $pdf->Row(array('DECIMA SEGUNDA.- Si al momento de la entrega resultare algún faltante o avería, el consignatario deberá hacerla constar en ese acto en la carta de porte y formular su reclamación por escrito al "Porteador" dentro de las 24 Hrs. Siguientes.'), false, false, false);
          $pdf->Row(array('DECIMA TERCERA.- El "Porteador" queda eximido de la obligación de recibir mercancías o efectos para su transporte, en los siguientes casos:'), false, false, false);
          $pdf->Row(array('a) Cuando se trate de carga que por su naturaleza, peso, volumen, embalaje defectuoso o cualquier otra circunstancia no pueda transportarse sin destruirse o sin causar daño a los demás artículos o al material rodante, salvo que la empresa de que se trate tenga el equipo adecuado. '), false, false, false);
          $pdf->Row(array('b) Las mercancías cuyo transporte haya sido prohibido por disposiciones legales o reglamentarias. Cuando tales disposiciones no prohíban precisamente el transporte de determinadas mercancías, pero si ordenen la presentación de ciertos documentos para que puedan ser transportadas, el "Remitente" estará obligado a entregar al "Porteador" los documentos correspondientes.'), false, false, false);
          $pdf->Row(array('DECIMA CUARTA.- Los casos no previstos en las presentes condiciones y las quejas derivadas de su aplicación se someterán por la vía administrativa a la Secretaría de Comunicaciones y Transportes.'), false, false, false);
        }

        if ($path)
          $pdf->Output($path.'Factura.pdf', 'F');
        else
          $pdf->Output('Factura', 'I');
    }

  public function palletsCliente($clienteId)
  {
    $query = $this->db->query(
      "SELECT rp.*,
              (SELECT count(f.id_factura)
               FROM facturacion AS f
               INNER JOIN facturacion_pallets AS fp ON fp.id_factura = f.id_factura
               WHERE f.status != 'ca' AND f.status != 'b' AND fp.id_pallet = rp.id_pallet) AS existe
       FROM rastria_pallets AS rp
       WHERE id_cliente = {$_GET['id']} AND
             no_cajas > 0 AND
             (SELECT count(f.id_factura) FROM facturacion AS f INNER JOIN facturacion_pallets AS fp ON fp.id_factura = f.id_factura WHERE f.status != 'ca' AND f.status != 'b' AND fp.id_pallet = rp.id_pallet) = 0
      ");

    $palletsInfo = array();
    if ($query->num_rows() > 0)
    {
      $this->load->model('rastreabilidad_pallets_model');
      $pallets = $query->result();

      foreach ($pallets as $pallet)
      {
        $palletsInfo[] = $this->rastreabilidad_pallets_model->getInfoPallet($pallet->id_pallet);
      }
    }

    return $palletsInfo;
  }

  public function refacturar($facturaId)
  {
    $this->db->update('facturacion', array('refacturada' => 't'), array('id_factura' => $facturaId));

    $resultTimbrado =  $this->addFactura();

    if ($resultTimbrado['passes'])
    {
      // Actualiza el cliente de los pallets.
      foreach ($_POST['palletsIds'] as $palletId)
      {
        $this->db->update('rastria_pallets', array('id_cliente' => $_POST['did_cliente']), array('id_pallet' => $palletId));
      }

      return $resultTimbrado;
    }
  }

  public function getRemisiones($remisionId = null)
  {
    $this->load->model('rastreabilidad_pallets_model');

    $sql = '';
    if ($remisionId)
    {
      $sql = " AND f.id_factura = {$remisionId}";
    }

    $remisiones = $this->db->query(
      "SELECT f.id_factura, f.serie, f.folio, c.nombre_fiscal, f.is_factura, DATE(f.fecha) as fecha
       FROM facturacion f
       INNER JOIN clientes c ON c.id_cliente = f.id_cliente
       LEFT JOIN facturacion_ventas_remision_pivot fvp ON fvp.id_venta = f.id_factura
       WHERE f.status = 'ca' AND f.is_factura = false AND ((SELECT status FROM facturacion WHERE id_factura = fvp.id_factura) in ('ca', 'b') OR COALESCE(fvp.id_factura, 0) = 0) {$sql}
       ORDER BY (DATE(f.fecha), f.serie, f.folio) DESC")->result();

    // echo "<pre>";
    //   var_dump($remisiones);
    // echo "</pre>";exit;

    foreach ($remisiones as $remision)
    {
      $remision->pallets = array();

      $pallets = $this->db->query("SELECT id_pallet
                                 FROM facturacion_pallets
                                 WHERE id_factura = {$remision->id_factura}");

      if ($pallets->num_rows() > 0)
      {
        foreach ($pallets->result() as $pallet)
        {
          $remision->pallets[] = $this->rastreabilidad_pallets_model->getInfoPallet($pallet->id_pallet);
        }
      }
    }
    // echo "<pre>";
    //   var_dump($remisiones);
    // echo "</pre>";exit;

    return $remisiones;
  }

  public function remisionesDetallePdf($filtros)
  {
    // echo "<pre>";
    //   var_dump($filtros);
    // echo "</pre>";exit;
    $this->load->model('empresas_model');

    if ($filtros['did_empresa'] === false || $filtros['did_empresa'] === '')
    {
      $default = $this->empresas_model->getDefaultEmpresa();
      $filtros['did_empresa'] = $default->id_empresa;
    }

    if ($filtros['ffacturadas'] === false)
    {
      $titulo2 = 'Reporte Remisiones';

      // Obtiene las remisiones no facturadas.
      $remisiones = $this->db->query(
        "SELECT f.id_factura, DATE(f.fecha) as fecha, f.serie, f.folio, c.nombre_fiscal as cliente, f.total, 'remision' as tipo
         FROM facturacion f
         INNER JOIN clientes c ON c.id_cliente = f.id_cliente
         LEFT JOIN facturacion_ventas_remision_pivot fvp ON fvp.id_venta = f.id_factura
         WHERE f.is_factura = false AND
              f.status != 'ca' AND
              ((SELECT status FROM facturacion WHERE id_factura = fvp.id_factura) in ('ca', 'b') OR COALESCE(fvp.id_factura, 0) = 0) AND
              f.id_empresa = {$filtros['did_empresa']} AND
              DATE(f.fecha) >= '{$filtros['ffecha1']}' AND
              DATE(f.fecha) <= '{$filtros['ffecha2']}'
         ORDER BY (f.fecha, f.serie, f.folio) ASC")->result();
    }
    else
    {
      $titulo2 = 'Reporte Remisiones Facturadas';

      $remisiones = $this->db->query(
        "SELECT f.id_factura, DATE(f.fecha) as fecha, f.serie, f.folio, c.nombre_fiscal as cliente, f.total, 'factura' as tipo
         FROM facturacion f
         INNER JOIN clientes c ON c.id_cliente = f.id_cliente
         INNER JOIN facturacion_ventas_remision_pivot fvp ON fvp.id_factura = f.id_factura
         WHERE f.is_factura = true AND
               f.status != 'ca' AND
               f.status != 'b' AND
               f.id_empresa = {$filtros['did_empresa']} AND
               DATE(f.fecha) >= '{$filtros['ffecha1']}' AND
               DATE(f.fecha) <= '{$filtros['ffecha2']}'
         GROUP BY f.id_factura, c.nombre_fiscal
         ORDER BY (f.fecha, f.serie, f.folio) ASC")->result();

      foreach ($remisiones as $remision)
      {
        $rems = $this->db->query(
          "SELECT f.id_factura, DATE(f.fecha) as fecha, f.serie, f.folio, c.nombre_fiscal as cliente, f.total, c.id_cliente
           FROM facturacion f
           INNER JOIN clientes c ON c.id_cliente = f.id_cliente
           INNER JOIN facturacion_ventas_remision_pivot fvp ON fvp.id_venta = f.id_factura
           WHERE DATE(f.fecha) >= '{$filtros['ffecha1']}' AND
                 DATE(f.fecha) <= '{$filtros['ffecha2']}' AND
                 fvp.id_factura = {$remision->id_factura}
           ORDER BY (f.fecha, f.serie, f.folio) ASC")->result();

        $fRemisiones = array();
        foreach ($rems as $r)
        {
          if (isset($fRemisiones[$r->id_cliente]))
          {
            $fRemisiones[$r->id_cliente]['remisiones'][] = $r;
          }
          else
          {
            $fRemisiones[$r->id_cliente]['cliente'] = $r->cliente;
            $fRemisiones[$r->id_cliente]['remisiones'][] = $r;
          }
        }

        $remision->remisiones = $fRemisiones;
      }
    }

    // $remisiones = array_merge($remisiones, $remisiones);
    // $remisiones = array_merge($remisiones, $remisiones);

    // echo "<pre>";
    //   var_dump($remisiones);
    // echo "</pre>";exit;

    $empresa = $this->empresas_model->getInfoEmpresa($filtros['did_empresa']);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = $titulo2;
    $pdf->titulo3 = 'Del: '.$filtros['ffecha1']." Al ".$filtros['ffecha2']."\n";
    // $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
    $pdf->AliasNbPages();
    // $pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'R', 'R', 'R');
    $widths = array(122, 28, 11, 20, 23);
    $header = array('Cliente', 'Fecha', 'Serie', 'Folio', 'Importe');

    $total = 0;
    foreach($remisiones as $key => $item)
    {
      if($pdf->GetY() >= $pdf->limiteY || $key==0) { //salta de pagina si exede el max
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
      }

      $pdf->SetFont('Arial','B',8);
      $pdf->SetTextColor(0,0,0);

      $pdf->SetFont('Arial','',8);

      $datos = array(
        $item->cliente,
        String::fechaATexto($item->fecha, '/c'),
        $item->serie,
        $item->folio,
        String::formatoNumero($item->total, 2, '', false),
      );

      if ($item->tipo === 'factura')
      {
        $pdf->SetFillColor(255,255,204);
      }
      else
      {
        $pdf->SetFillColor(255,255,255);
      }

      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, true, true);

      if (isset($item->remisiones))
      {
        foreach ($item->remisiones as $keya => $cliente)
        {
          if($pdf->GetY() >= $pdf->limiteY) { //salta de pagina si exede el max
            $pdf->AddPage();

            $pdf->SetFont('Arial','B',8);
            $pdf->SetTextColor(255,255,255);
            $pdf->SetFillColor(160,160,160);
            $pdf->SetX(6);
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->Row($header, true);
          }

          $datos = array(
            '     ' . $cliente['cliente'],
            '',
            '',
            '',
            '',
          );

          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($datos, false, true);

          foreach ($cliente['remisiones'] as $remi)
          {
            if($pdf->GetY() >= $pdf->limiteY) { //salta de pagina si exede el max
              $pdf->AddPage();

              $pdf->SetFont('Arial','B',8);
              $pdf->SetTextColor(255,255,255);
              $pdf->SetFillColor(160,160,160);
              $pdf->SetX(6);
              $pdf->SetAligns($aligns);
              $pdf->SetWidths($widths);
              $pdf->Row($header, true);
            }

            $datos = array(
              '',
              String::fechaATexto($remi->fecha, '/c'),
              $remi->serie,
              $remi->folio,
              '('.String::formatoNumero($remi->total, 2, '', false).')',
            );

            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->Row($datos, false, true);
          }
        }
      }

      $total += floatval($item->total);
    }

    $pdf->SetXY(157, $pdf->GetY() + 2);
    $pdf->SetFont('Arial','B',8);
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(30, 23));
    $pdf->Row(array('TOTAL', String::formatoNumero($total, 2, '', false)), false);

    $pdf->Output('reporte_remisiones.pdf', 'I');
  }
}