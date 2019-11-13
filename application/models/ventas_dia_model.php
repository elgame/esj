<?php
class ventas_dia_model extends privilegios_model{

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
                f.status_timbrado, f.uuid, f.docs_finalizados, f.observaciones, f.refacturada, f.total,
                f.id_factura_asignada
				FROM facturacionv AS f
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
      ->from('facturacionv')
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
                u.id_unidad, fp.kilos, fp.cajas, fp.id_unidad_rendimiento, fp.ids_remisiones, fp.clase, fp.peso, fp.certificado, fp.id_size_rendimiento,
                cla.id_clasificacion AS id_asignada, cla.nombre AS asignada')
        ->from('facturacionv_productos as fp')
        ->join('clasificaciones as cl', 'cl.id_clasificacion = fp.id_clasificacion', 'left')
        ->join('unidades as u', 'u.nombre = fp.unidad', 'left')
        ->join('clasificaciones as cla', 'cla.id_clasificacion = fp.id_clasificacion_asigna', 'left')
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

        FROM facturacionv_pallets fp
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
        FROM facturacionv AS f INNER JOIN facturacionv_ventas_remision_pivot AS fvr ON f.id_factura = fvr.id_venta
        WHERE fvr.id_factura = {$idFactura}")->result();

      $remitente = $this->db->query(
        "SELECT nombre, direccion, rfc, placas, modelo, chofer, marca
         FROM facturacionv_remitente
         WHERE id_factura = $idFactura");

      $destinatario = $this->db->query(
       "SELECT nombre, direccion, rfc
        FROM facturacionv_destinatario
        WHERE id_factura = $idFactura");

      if ($remitente->num_rows() > 0 || $destinatario->num_rows() > 0)
      {
        $response['carta_porte']['remitente'] = $remitente->result();
        $response['carta_porte']['destinatario'] = $destinatario->result();
      }

      $res->free_result();
      $res = $this->db->query("SELECT fsc.*, p.nombre_fiscal as proveedor
         FROM facturacionv_seg_cert fsc
         INNER JOIN proveedores p ON p.id_proveedor = fsc.id_proveedor
         WHERE id_factura = {$idFactura}");

      foreach ($res->result() as $tipo)
      {
        if ($tipo->id_clasificacion == 49)
        {
          $response['seguro'] = $tipo;
        } elseif ($tipo->id_clasificacion == 53)
        {
          $response['supcarga'] = $tipo;
        }else
        { // Certificados 51 o 52
          $response['certificado'.$tipo->id_clasificacion] = $tipo;
        }
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
      ->from('facturacionv')
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
            $this->db->delete('facturacionv_pallets', array('id_factura' => $idFactura));
          }

          $this->db->insert_batch('facturacionv_pallets', $pallets);
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
            $this->db->delete('facturacionv_ventas_remision_pivot', array('id_factura' => $idFactura));
          }

          $this->db->insert_batch('facturacionv_ventas_remision_pivot', $remisiones);
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

    // // indica q es una factura en parcialidades de un abono
    // if (isset($_POST['id_abono_factura'])) {
    //   $datosFactura['id_abono_factura'] = $_POST['id_abono_factura'];
    // }

    // Tipo de cambio y moneda
    if ($datosFactura['moneda'] !== 'M.N.')
      $datosFactura['tipo_cambio'] = $_POST['tipoCambio'];
    else
      $datosFactura['tipo_cambio'] = '1';

    // Si el tipo de comprobante es "egreso" o una nota de credito.
    $bitacora_accion = 'la factura';
    if ($_POST['dtipo_comprobante'] === 'egreso') {
      $datosFactura['id_nc'] = $_GET['id'];
      $bitacora_accion = 'la nota de credito';
    }

    // Inserta los datos de la factura y obtiene el Id. Este en caso
    // de que se este timbrando una factura que no sea un borrador.
    if (( ! isset($_GET['idb']) && ! $borrador) || $borrador)
    {
      $this->db->insert('facturacionv', $datosFactura);
      $idFactura = $this->db->insert_id('facturacionv', 'id_factura');

      $msg = '3';
      // // Bitacora
      // $this->bitacora_model->_insert('facturacion', $idFactura,
      //                                 array(':accion'    => $bitacora_accion, ':seccion' => 'facturas',
      //                                       ':folio'     => $datosFactura['serie'].$datosFactura['folio'],
      //                                       ':id_empresa' => $datosFactura['id_empresa'],
      //                                       ':empresa'   => 'en '.$this->input->post('dempresa')));
    }

    // Si es un borrador que se esta timbrando entonces actualiza sus datos.
    else
    {
      $idFactura = $_GET['idb'];
      $this->db->update('facturacionv', $datosFactura, array('id_factura' => $idFactura));
      $msg = '7&idb='.$idFactura;
    }

    // Productos e Impuestos
    $productosCadOri    = array(); // Productos para la CadOriginal
    $productosFactura   = array(); // Productos para la Factura

    $impuestosTraslados = array(); // Traslados
    $traslado0  = false; // Total de traslado 0%
    $traslado11 = 0; // Total de traslado 11%
    $traslado16 = 0; // Total de traslado 16%

    $dataSeguroCerti = array();
    $serieFolio = $datosFactura['serie'].$datosFactura['folio'];

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
        else if ($_POST['prod_diva_porcent'][$key] == '16'){
          if($datosFactura['sin_costo'] == 't'){
            if ($_POST['prod_did_prod'][$key] != '49' AND $_POST['prod_did_prod'][$key] != '50' AND
                $_POST['prod_did_prod'][$key] != '51' AND $_POST['prod_did_prod'][$key] != '52' AND
                $_POST['prod_did_prod'][$key] != '53')
              $traslado16 += floatval($_POST['prod_diva_total'][$key]);
          }else
            $traslado16 += floatval($_POST['prod_diva_total'][$key]);
        }
        else
          $traslado0 = true;

        $productosFactura[] = array(
          'id_factura'              => $idFactura,
          'id_clasificacion'        => $_POST['prod_did_prod'][$key] !== '' ? $_POST['prod_did_prod'][$key] : null,
          'num_row'                 => intval($key),
          'cantidad'                => $_POST['prod_dcantidad'][$key],
          'descripcion'             => $descripcion,
          'precio_unitario'         => $_POST['prod_dpreciou'][$key],
          'importe'                 => $_POST['prod_importe'][$key],
          'iva'                     => $_POST['prod_diva_total'][$key],
          'unidad'                  => $_POST['prod_dmedida'][$key],
          'retencion_iva'           => $_POST['prod_dreten_iva_total'][$key],
          'porcentaje_iva'          => $_POST['prod_diva_porcent'][$key],
          'porcentaje_retencion'    => $_POST['prod_dreten_iva_porcent'][$key],
          'ids_pallets'             => isset($_POST['pallets_id'][$key]) && $_POST['pallets_id'][$key] !== '' ? $_POST['pallets_id'][$key] : null,
          'ids_remisiones'          => isset($_POST['remisiones_id'][$key]) && $_POST['remisiones_id'][$key] !== '' ? $_POST['remisiones_id'][$key] : null,
          'kilos'                   => isset($_POST['prod_dkilos'][$key]) ? $_POST['prod_dkilos'][$key] : 0,
          'cajas'                   => isset($_POST['prod_dcajas'][$key]) ? $_POST['prod_dcajas'][$key] : 0,
          'id_unidad_rendimiento'   => isset($_POST['id_unidad_rendimiento'][$key]) && $_POST['id_unidad_rendimiento'][$key] !== '' ? $_POST['id_unidad_rendimiento'][$key] : null,
          'id_size_rendimiento'     => isset($_POST['id_size_rendimiento'][$key]) && $_POST['id_size_rendimiento'][$key] !== '' ? $_POST['id_size_rendimiento'][$key] : null,
          'clase'                   => isset($_POST['prod_dclase'][$key]) ? $_POST['prod_dclase'][$key] : '',
          'peso'                    => isset($_POST['prod_dpeso'][$key]) && $_POST['prod_dpeso'][$key] !== '' ? $_POST['prod_dpeso'][$key] : 0,
          'certificado'             => $_POST['isCert'][$key] === '1' ? 't' : 'f',
          'id_clasificacion_asigna' => ($_POST['prod_did_asigna'][$key] !== '' ? $_POST['prod_did_asigna'][$key] : ($_POST['prod_did_prod'][$key] !== '' ? $_POST['prod_did_prod'][$key] : null)),
        );

        if ($_POST['prod_did_prod'][$key] === '49')
        {
          $dataSeguroCerti[] = array(
            'id_factura'       => $idFactura,
            'id_clasificacion' => $_POST['prod_did_prod'][$key],
            'id_proveedor'     => $_POST['seg_id_proveedor'],
            'pol_seg'          => $_POST['seg_poliza'],
            'folio'            => $serieFolio,
            'bultos'           => 0,
            'certificado'      => null,
          );
        }

        if ($_POST['prod_did_prod'][$key] === '51' || $_POST['prod_did_prod'][$key] === '52')
        {
          $dataSeguroCerti[] = array(
            'id_factura'       => $idFactura,
            'id_clasificacion' => $_POST['prod_did_prod'][$key],
            'id_proveedor'     => $_POST['cert_id_proveedor'.$_POST['prod_did_prod'][$key]],
            'certificado'      => $_POST['cert_certificado'.$_POST['prod_did_prod'][$key]],
            'folio'            => $serieFolio,
            'bultos'           => $_POST['cert_bultos'.$_POST['prod_did_prod'][$key]],
            'pol_seg'          => null,
          );
        }

        if ($_POST['prod_did_prod'][$key] === '53')
        {
          $dataSeguroCerti[] = array(
            'id_factura'       => $idFactura,
            'id_clasificacion' => $_POST['prod_did_prod'][$key],
            'id_proveedor'     => $_POST['supcarga_id_proveedor'],
            'certificado'      => $_POST['supcarga_numero'],
            'folio'            => $serieFolio,
            'bultos'           => $_POST['supcarga_bultos'],
            'pol_seg'          => null,
          );
        }
      }
    }

    if (count($productosFactura) > 0)
    {
      if ((isset($_GET['idb']) && ! $borrador) || $borrador)
      {
        $this->db->delete('facturacionv_productos', array('id_factura' => $idFactura));
      }

      $this->db->insert_batch('facturacionv_productos', $productosFactura);
    }

    // Inserta los pallests y las remisiones a la factura
    $this->addPallestRemisiones($idFactura, $borrador);

    if (isset($_POST['es_carta_porte']))
    {
      if (isset($_POST['es_carta_porte']) || $borrador)
      {
        $this->db->delete('facturacionv_remitente', array('id_factura' => $idFactura));
        $this->db->delete('facturacionv_destinatario', array('id_factura' => $idFactura));
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

      $this->db->insert('facturacionv_remitente', $remitente);
      $this->db->insert('facturacionv_destinatario', $destinatario);
    }

    if (count($dataSeguroCerti) > 0)
    {
      $this->db->delete('facturacionv_seg_cert', array('id_factura' => $idFactura));
      $this->db->insert_batch('facturacionv_seg_cert', $dataSeguroCerti);
    }

    // // Obtiene los datos del cliente.
    // $cliente = $this->clientes_model->getClienteInfo($this->input->post('did_cliente'), true);
    // $dataCliente = array(
    //   'id_factura'    => $idFactura,
    //   'nombre'      => $cliente['info']->nombre_fiscal,
    //   'rfc'         => $cliente['info']->rfc,
    //   'calle'       => $cliente['info']->calle,
    //   'no_exterior' => $cliente['info']->no_exterior,
    //   'no_interior' => $cliente['info']->no_interior,
    //   'colonia'     => $cliente['info']->colonia,
    //   'localidad'   => $cliente['info']->localidad,
    //   'municipio'   => $cliente['info']->municipio,
    //   'estado'      => $cliente['info']->estado,
    //   'cp'          => $cliente['info']->cp,
    //   'pais'        => 'MEXICO',
    // );
    // $this->db->insert( 'facturacion_cliente', $dataCliente );

    // Si es un borrador

    return array('pass' => true, 'msg' => $msg);
	}

  public function idFacturaVenta($data, $quita=false)
  {
    if ($quita) {
      $this->db->update('facturacionv', array('id_factura_asignada' => NULL), "id_factura_asignada = {$data['id_factura']}");
    } else {
      $this->db->update('facturacionv', array('id_factura_asignada' => $data['id_factura']), "id_factura = {$data['id_venta']}");
    }
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
    $this->db->update('facturacionv',
      array('status' => 'ca', 'status_timbrado' => 'ca'),
      "id_factura = {$idFactura}"
    );

    return array('msg' => 4);
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
            $correoEmisor   = "postmaster@empaquesanjorge.com"; // Correo para el auth. empaquesanjorgemx@gmail.com (mandrill)
            $contrasena     = "2b9f25bc4737f34edada0b29a56ff682"; // Contraseña de $correEmisor S4nj0rg3V14n3y

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

      $dataSeguroCerti = array();
      $serieFolio = $datosFactura['serie'].$datosFactura['folio'];

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
            'id_size_rendimiento'   => isset($_POST['id_size_rendimiento'][$key]) && $_POST['id_size_rendimiento'][$key] !== '' ? $_POST['id_size_rendimiento'][$key] : null,
            'clase' => isset($_POST['prod_dclase'][$key]) ? $_POST['prod_dclase'][$key] : null,
            'peso' => isset($_POST['prod_dpeso'][$key]) && $_POST['prod_dpeso'][$key] !== '' ? $_POST['prod_dpeso'][$key] : null,
            'certificado' => $_POST['isCert'][$key] === '1' ? 't' : 'f',
          );

          if ($_POST['prod_did_prod'][$key] === '49')
          {
            $dataSeguroCerti[] = array(
              'id_factura'       => $idBorrador,
              'id_clasificacion' => $_POST['prod_did_prod'][$key],
              'id_proveedor'     => $_POST['seg_id_proveedor'],
              'pol_seg'          => $_POST['seg_poliza'],
              'folio'            => $serieFolio,
              'bultos'           => 0,
              'certificado'      => null,
            );
          }

          if ($_POST['prod_did_prod'][$key] === '51' || $_POST['prod_did_prod'][$key] === '52')
          {
            $dataSeguroCerti[] = array(
              'id_factura'       => $idBorrador,
              'id_clasificacion' => $_POST['prod_did_prod'][$key],
              'id_proveedor'     => $_POST['cert_id_proveedor'.$_POST['prod_did_prod'][$key]],
              'certificado'      => $_POST['cert_certificado'.$_POST['prod_did_prod'][$key]],
              'folio'            => $serieFolio,
              'bultos'           => $_POST['cert_bultos'.$_POST['prod_did_prod'][$key]],
              'pol_seg'          => null,
            );
          }

          if ($_POST['prod_did_prod'][$key] === '53')
          {
            $dataSeguroCerti[] = array(
              'id_factura'       => $idBorrador,
              'id_clasificacion' => $_POST['prod_did_prod'][$key],
              'id_proveedor'     => $_POST['supcarga_id_proveedor'],
              'certificado'      => $_POST['supcarga_numero'],
              'folio'            => $serieFolio,
              'bultos'           => $_POST['supcarga_bultos'],
              'pol_seg'          => null,
            );
          }
        }
      }

      $this->db->delete('facturacion_productos', array('id_factura' => $idBorrador));
      $this->db->delete('facturacion_pallets', array('id_factura' => $idBorrador));
      $this->db->delete('facturacion_ventas_remision_pivot', array('id_factura' => $idBorrador));
      $this->db->delete('facturacion_seg_cert', array('id_factura' => $idBorrador));

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

      if (count($dataSeguroCerti) > 0)
      {
        $this->db->insert_batch('facturacion_seg_cert', $dataSeguroCerti);
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

    /**
   * REPORTE DE RENDIMIENTO
   * @return [type] [description]
   */
  public function rvd_data()
  {
      $response = array('lotes' => array(), 'rendimientos' => array());
      $sql1 = $sql2 = '';

      if (empty($_GET['ffecha1'])){
        $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      }
      if (!empty($_GET['ffecha1'])){
        $response['titulo3'] = "Del ".$_GET['ffecha1'];
        $sql1 .= " AND Date(b.fecha_tara) = '".$_GET['ffecha1']."'";
        $sql2 .= " AND Date(f.fecha) = '".$_GET['ffecha1']."'";
      }
      //Filtros de area
      if ($this->input->get('farea') != ''){
        $sql1 .= " AND b.id_area = " . $_GET['farea'];
        $sql2 .= " AND a.id_area = " . $_GET['farea'];
      }else {
        $sql1 .= " AND b.id_area = 0";
        $sql2 .= " AND a.id_area = 0";
      }

      // Obtenemos los rendimientos en los lotes de ese dia
      $query = $this->db->query(
        "SELECT c.id_clasificacion,
                Sum(fp.cantidad) AS rendimiento,
                Sum(fp.kilos*fp.cantidad) AS kilos_total,
                c.nombre AS clasificacion, a.nombre AS area,
                string_agg(distinct f.serie||f.folio::character varying, ',') AS folios
        FROM facturacionv f
          INNER JOIN facturacionv_productos fp ON f.id_factura = fp.id_factura
          INNER JOIN clasificaciones c ON c.id_clasificacion = fp.id_clasificacion_asigna
          INNER JOIN facturacionv_pallets fpa ON f.id_factura = fpa.id_factura
          INNER JOIN rastria_pallets rp ON rp.id_pallet = fpa.id_pallet
          INNER JOIN areas a ON a.id_area = rp.id_area
        WHERE f.status <> 'ca' {$sql2}
        GROUP BY c.id_clasificacion, a.nombre
        HAVING Sum(fp.kilos*fp.cantidad) > 0
        ORDER BY clasificacion ASC");
      // "SELECT c.id_clasificacion,
      //     Sum(rrc.rendimiento) AS rendimiento,
      //     Sum(rrc.rendimiento*rrc.kilos) AS kilos_total,
      //     c.nombre AS clasificacion, u.nombre AS unidad,
      //     ca.nombre AS calibre, e.nombre AS etiqueta,
      //     cas.nombre AS size, a.nombre AS area
      //   FROM rastria_rendimiento AS rr
      //     INNER JOIN rastria_rendimiento_clasif AS rrc ON rr.id_rendimiento = rrc.id_rendimiento
      //     INNER JOIN clasificaciones AS c ON c.id_clasificacion = rrc.id_clasificacion
      //     INNER JOIN unidades AS u ON u.id_unidad = rrc.id_unidad
      //     INNER JOIN calibres AS ca ON ca.id_calibre = rrc.id_calibre
      //     INNER JOIN etiquetas AS e ON e.id_etiqueta = rrc.id_etiqueta
      //     INNER JOIN calibres AS cas ON cas.id_calibre = rrc.id_size
      //     INNER JOIN areas AS a ON a.id_area = rr.id_area
      //   WHERE rr.status = 't' {$sql2}
      //   GROUP BY c.id_clasificacion, u.nombre, ca.nombre, e.nombre, cas.nombre, a.nombre
      //   ORDER BY c.nombre ASC"
      if($query->num_rows() > 0){
        $response['rendimientos'] = $query->result();
      }
      $query->free_result();


      return $response;
   }

   /**
    * Reporte de rendimientos de fruta
    * @return void
    */
   public function rvd_pdf()
   {
      // Obtiene los datos del reporte.
      $data = $this->rvd_data();
      // echo "<pre>";
      //   var_dump($data);
      // echo "</pre>";exit;

      $fecha = new DateTime($_GET['ffecha1']);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->titulo2 = "REPORTE RENDIMIENTO VENTAS";
      if(isset($data['rendimientos'][0]))
        $pdf->titulo3 = "DEL {$fecha->format('d/m/Y')} | AREA: {$data['rendimientos'][0]->area}\n";
      // $lote = isset($data['data'][count($data['data'])-1]->no_lote)? $data['data'][count($data['data'])-1]->no_lote: '1';
      // $pdf->titulo3 .= "Estado: 6 | Municipio: 9 | Semana {$fecha->format('W')} | NUMERADOR: 69{$fecha->format('Ww')}/1 Al ".$lote;

      $pdf->AliasNbPages();

      // Listado de Rendimientos
      $pdf->SetFont('helvetica','', 8);
      $pdf->SetY($pdf->GetY()+2);

      $aligns = array('L', 'L', 'R', 'R', 'R', 'C');
      $widths = array(60, 80, 20, 30);
      $header = array('Clasificacion', 'Ventas', 'Rendimiento', 'T Kilos');

      $total_rendimiento = 0;
      $total_kilos_total = 0;

      foreach($data['rendimientos'] as $key => $boleta)
      {
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetFont('helvetica','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(200,200,200);
          // $pdf->SetY($pdf->GetY()-2);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('helvetica','', 8);
        $pdf->SetTextColor(0,0,0);

        // $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row(array(
            $boleta->clasificacion,
            // $boleta->unidad.' '.$boleta->calibre.' '.$boleta->size.' '.$boleta->etiqueta,
            $boleta->folios,
            String::formatoNumero($boleta->rendimiento, 2, '', false),
            String::formatoNumero($boleta->kilos_total, 2, '', false),
          ), false);

        $total_rendimiento += $boleta->rendimiento;
        $total_kilos_total += $boleta->kilos_total;
      }

      //total general
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetTextColor(0 ,0 ,0 );
      $pdf->SetAligns(array('R', 'R', 'R', 'R', 'R'));
      $pdf->SetWidths(array(20, 30));
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(146);
      $pdf->Row(array(
              String::formatoNumero($total_rendimiento, 2, '', false), String::formatoNumero($total_kilos_total, 2, '', false)
            ), false);



      $pdf->Output('reporte_rastreabilidad_'.$fecha->format('d/m/Y').'.pdf', 'I');
   }

   public function rvd_xls(){
      $data = $this->rvd_data();

      header('Content-type: application/vnd.ms-excel; charset=utf-8');
      header("Content-Disposition: attachment; filename=reporte_rastreabilidad.xls");
      header("Pragma: no-cache");
      header("Expires: 0");

      $fecha = new DateTime($_GET['ffecha1']);

      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa(2);

      $titulo1 = $empresa['info']->nombre_fiscal;
      $titulo2 = "REPORTE RENDIMIENTO";
      $titulo3 = "DEL {$fecha->format('d/m/Y')} | AREA: {$data['rendimientos'][0]->area}\n";

      $html = '<table>
        <tbody>
          <tr>
            <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
          </tr>
          <tr>
            <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
          </tr>
          <tr>
            <td colspan="6" style="text-align:center;">'.$titulo3.'</td>
          </tr>
          <tr>
            <td colspan="6"></td>
          </tr>
          <tr style="font-weight:bold">
            <td style="border:1px solid #000;background-color: #cccccc;">LOTE</td>
            <td style="border:1px solid #000;background-color: #cccccc;">B CAJAS</td>
            <td style="border:1px solid #000;background-color: #cccccc;">B K NETOS</td>
            <td style="border:1px solid #000;background-color: #cccccc;">B K INDUSTRIAL</td>
            <td style="border:1px solid #000;background-color: #cccccc;">REND BULTOS</td>
            <td style="border:1px solid #000;background-color: #cccccc;">REND K NETOS</td>
            <td style="border:1px solid #000;background-color: #cccccc;">K INDUSTRIAL</td>
          </tr>';
      $totales = array(0,0,0,0,0,0);
      foreach($data['lotes'] as $key => $lote)
      {

        $html .= '<tr>
            <td style="border:1px solid #000;">'.$lote->no_lote.'</td>
            <td style="border:1px solid #000;">'.$lote->btotal_cajas.'</td>
            <td style="border:1px solid #000;">'.($lote->btotal_kilos-$lote->bkilos_inds).'</td>
            <td style="border:1px solid #000;">'.$lote->bkilos_inds.'</td>
            <td style="border:1px solid #000;">'.$lote->rtotal_cajas.'</td>
            <td style="border:1px solid #000;">'.$lote->rtotal_kilos.'</td>
            <td style="border:1px solid #000;">'.($lote->btotal_kilos-$lote->bkilos_inds-$lote->rtotal_kilos).'</td>
          </tr>';

        $totales[0] += $lote->btotal_cajas;
        $totales[1] += $lote->btotal_kilos-$lote->bkilos_inds;
        $totales[2] += $lote->bkilos_inds;
        $totales[3] += $lote->rtotal_cajas;
        $totales[4] += $lote->rtotal_kilos;
        $totales[5] += $lote->btotal_kilos-$lote->bkilos_inds-$lote->rtotal_kilos;
      }
      $html .= '<tr style="font-weight:bold">
            <td>Totales</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.$totales[0].'</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.$totales[1].'</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.$totales[2].'</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.$totales[3].'</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.$totales[4].'</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.$totales[5].'</td>
          </tr>
          <tr>
            <td colspan="7"></td>
          </tr>';

      $html .= '
          <tr style="font-weight:bold">
            <td style="border:1px solid #000;background-color: #cccccc;">Clasificacion</td>
            <td style="border:1px solid #000;background-color: #cccccc;">Otros</td>
            <td style="border:1px solid #000;background-color: #cccccc;">Rendimiento</td>
            <td style="border:1px solid #000;background-color: #cccccc;">T Kilos</td>
          </tr>';
      $total_rendimiento = 0;
      $total_kilos_total = 0;
      foreach($data['rendimientos'] as $key => $boleta)
      {

        $html .= '<tr>
            <td style="border:1px solid #000;">'.$boleta->clasificacion.'</td>
            <td style="border:1px solid #000;">'.$boleta->size.'</td>
            <td style="border:1px solid #000;">'.$boleta->rendimiento.'</td>
            <td style="border:1px solid #000;">'.$boleta->kilos_total.'</td>
          </tr>';

        $total_rendimiento += $boleta->rendimiento;
        $total_kilos_total += $boleta->kilos_total;
      }
      $html .= '<tr style="font-weight:bold">
            <td></td>
            <td></td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.$total_rendimiento.'</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.$total_kilos_total.'</td>
          </tr>
          <tr>
            <td colspan="7"></td>
          </tr>';


      $html .= '
          <tr style="font-weight:bold">
            <td>B K NETOS</td>
            <td style="border:1px solid #000;">'.$totales[1].'</td>
          </tr>
          <tr style="font-weight:bold">
            <td>REND K NETOS</td>
            <td style="border:1px solid #000;">'.$totales[4].'</td>
          </tr>
          <tr style="font-weight:bold">
            <td>K INDUSTRIAL</td>
            <td style="border:1px solid #000;">'.($totales[2]+$totales[5]).'</td>
          </tr>
        </tbody>
      </table>';

      echo $html;
    }


  public function vd_data()
  {
    $this->load->model('empresas_model');
    $this->load->model('cuentas_cobrar_model');

    $response = array('ventas' => array());
    $sql1 = $sql2 = '';

    if (empty($_GET['ffecha1'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
    }
    if (empty($_GET['did_empresa'])){
      $empresa = $this->empresas_model->getDefaultEmpresa();
      $_GET['did_empresa'] = $empresa->id_empresa;
    }
    $sql2 .= " AND f.id_empresa = ".$_GET['did_empresa']." AND Date(f.fecha) = '".$_GET['ffecha1']."'";

    // Obtenemos las ventas
    $query = $this->db->query(
      "SELECT f.id_factura, f.serie, f.folio, (CASE f.is_factura WHEN true THEN 'FACTURA ELECTRONICA' ELSE 'REMISION' END) AS tipo, f.subtotal, f.total,
        (f.importe_iva-f.retencion_iva) AS impuestos, Sum(fp.cantidad) AS cantidad, c.id_cliente, c.nombre_fiscal
      FROM facturacion f
        INNER JOIN clientes c ON c.id_cliente = f.id_cliente
        LEFT JOIN facturacion_productos fp ON f.id_factura = fp.id_factura
      WHERE f.status <> 'ca' {$sql2}
      GROUP BY f.id_factura, c.id_cliente, c.nombre_fiscal
      ORDER BY (nombre_fiscal, serie, folio) ASC");
    if($query->num_rows() > 0){
      $response['ventas'] = $query->result();
      foreach ($response['ventas'] as $key => $value) {
        $info = $this->cuentas_cobrar_model->getDetalleVentaFacturaData($value->id_factura, 'f', true, true);
        $value->saldo = $info['saldo'];
      }
    }
    $query->free_result();


    return $response;
  }

   /**
    * Reporte de ventas de dia
    * @return void
    */
   public function vd_pdf()
   {
      // Obtiene los datos del reporte.
      $data = $this->vd_data();
      // echo "<pre>";
      //   var_dump($data);
      // echo "</pre>";exit;

      $fecha = new DateTime($_GET['ffecha1']);

      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($_GET['did_empresa']);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->titulo1 = $empresa['info']->nombre_fiscal;
      $pdf->titulo2 = "REPORTE VENTAS DEL DIA";
      $pdf->titulo3 = "DEL {$fecha->format('d/m/Y')}\n";
      // $lote = isset($data['data'][count($data['data'])-1]->no_lote)? $data['data'][count($data['data'])-1]->no_lote: '1';
      // $pdf->titulo3 .= "Estado: 6 | Municipio: 9 | Semana {$fecha->format('W')} | NUMERADOR: 69{$fecha->format('Ww')}/1 Al ".$lote;

      $pdf->AliasNbPages();

      // Listado de Rendimientos
      $pdf->SetFont('helvetica','', 8);
      $pdf->SetY($pdf->GetY()+2);

      $aligns = array('C', 'L', 'L', 'L', 'C', 'R', 'R', 'R', 'R');
      $widths = array(10, 11, 15, 45, 17, 30, 18, 30, 30);
      $header = array('','SERIE','FOLIO','TIPO','CANTIDAD','NETO','IMPUESTO','TOTAL','SALDO');

      $total_saldo = 0;
      $total_cantidad = 0;
      $total_subtotal = 0;
      $total_impuestos = 0;
      $total_total = 0;

      $auxcliente = 0;
      foreach($data['ventas'] as $key => $venta)
      {
        if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
        {
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetFont('helvetica','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(200,200,200);
          // $pdf->SetY($pdf->GetY()-2);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        if ($auxcliente != $venta->id_cliente) {
          $pdf->SetFont('helvetica','B', 8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetX(6);
          $pdf->SetAligns('L');
          $pdf->SetWidths(array(200));
          $pdf->Row(array(
              $venta->nombre_fiscal
            ), false, false);
          $auxcliente = $venta->id_cliente;
        }

        $pdf->SetFont('helvetica','', 8);
        $pdf->SetTextColor(0,0,0);

        // $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row(array(
            $venta->id_cliente,
            $venta->serie,
            $venta->folio,
            $venta->tipo,
            String::formatoNumero($venta->cantidad, 2, '', true),
            String::formatoNumero($venta->subtotal, 2, '', false),
            String::formatoNumero($venta->impuestos, 2, '', false),
            String::formatoNumero($venta->total, 2, '', false),
            String::formatoNumero($venta->saldo, 2, '', false),
          ), false);

        $total_saldo += $venta->saldo;
        $total_cantidad += $venta->cantidad;
        $total_subtotal += $venta->subtotal;
        $total_impuestos += $venta->impuestos;
        $total_total += $venta->total;
      }

      //total general
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetTextColor(0 ,0 ,0 );
      $pdf->SetAligns(array('C', 'R', 'R', 'R', 'R'));
      $pdf->SetWidths(array(17, 30, 18, 30, 30));
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(87);
      $pdf->Row(array(
              String::formatoNumero($total_cantidad, 2, '', true),
              String::formatoNumero($total_subtotal, 2, '', false),
              String::formatoNumero($total_impuestos, 2, '', false),
              String::formatoNumero($total_total, 2, '', false),
              String::formatoNumero($total_saldo, 2, '', false),
            ), false);



      $pdf->Output('ventas'.$fecha->format('d/m/Y').'.pdf', 'I');
   }

   public function vd_xls(){
      $data = $this->vd_data();

      header('Content-type: application/vnd.ms-excel; charset=utf-8');
      header("Content-Disposition: attachment; filename=ventas_del_dia.xls");
      header("Pragma: no-cache");
      header("Expires: 0");

      $fecha = new DateTime($_GET['ffecha1']);

      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($_GET['did_empresa']);

      $titulo1 = $empresa['info']->nombre_fiscal;
      $titulo2 = "REPORTE VENTAS DEL DIA";
      $titulo3 = "DEL {$fecha->format('d/m/Y')}\n";

      $html = '<table>
        <tbody>
          <tr>
            <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
          </tr>
          <tr>
            <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
          </tr>
          <tr>
            <td colspan="6" style="text-align:center;">'.$titulo3.'</td>
          </tr>
          <tr>
            <td colspan="6"></td>
          </tr>
          <tr style="font-weight:bold">
            <td style="border:1px solid #000;background-color: #cccccc;"></td>
            <td style="border:1px solid #000;background-color: #cccccc;">SERIE</td>
            <td style="border:1px solid #000;background-color: #cccccc;">FOLIO</td>
            <td style="border:1px solid #000;background-color: #cccccc;">TIPO</td>
            <td style="border:1px solid #000;background-color: #cccccc;">CANTIDAD</td>
            <td style="border:1px solid #000;background-color: #cccccc;">NETO</td>
            <td style="border:1px solid #000;background-color: #cccccc;">IMPUESTO</td>
            <td style="border:1px solid #000;background-color: #cccccc;">TOTAL</td>
            <td style="border:1px solid #000;background-color: #cccccc;">SALDO</td>
          </tr>';
      $total_saldo = 0;
      $total_cantidad = 0;
      $total_subtotal = 0;
      $total_impuestos = 0;
      $total_total = 0;

      $auxcliente = 0;
      foreach($data['ventas'] as $key => $venta)
      {

        $html .= '<tr>
            <td style="border:1px solid #000;">'.$venta->id_cliente.'</td>
            <td style="border:1px solid #000;">'.$venta->serie.'</td>
            <td style="border:1px solid #000;">'.$venta->folio.'</td>
            <td style="border:1px solid #000;">'.$venta->tipo.'</td>
            <td style="border:1px solid #000;">'.$venta->cantidad.'</td>
            <td style="border:1px solid #000;">'.$venta->subtotal.'</td>
            <td style="border:1px solid #000;">'.$venta->impuestos.'</td>
            <td style="border:1px solid #000;">'.$venta->total.'</td>
            <td style="border:1px solid #000;">'.$venta->saldo.'</td>
          </tr>';

        $total_saldo += $venta->saldo;
        $total_cantidad += $venta->cantidad;
        $total_subtotal += $venta->subtotal;
        $total_impuestos += $venta->impuestos;
        $total_total += $venta->total;
      }
      $html .= '<tr style="font-weight:bold">
            <td colspan="4">Totales</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.$total_saldo.'</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.$total_cantidad.'</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.$total_subtotal.'</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.$total_impuestos.'</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.$total_total.'</td>
          </tr>
          <tr>
            <td colspan="7"></td>
          </tr>';

      $html .= '
        </tbody>
      </table>';

      echo $html;
    }


  /*
  |------------------------------------------------------------------------
  | FACTURA PDF
  |------------------------------------------------------------------------
  */

  public function generaFacturaPdf($idFactura, $path = null)
  {
    include_once(APPPATH.'libraries/phpqrcode/qrlib.php');

    $factura = $this->getInfoFactura($idFactura);

    // echo "<pre>";
    //   var_dump($factura);
    // echo "</pre>";exit;

    $xml = simplexml_load_string(str_replace(array('cfdi:', 'tfd:'), '', $factura['info']->xml));

    // echo "<pre>";
    //   var_dump($factura, $xml);
    // echo "</pre>";exit;

    $this->load->library('mypdf');

    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    $pdf->show_head = false;

    $pdf->AliasNbPages();
    $pdf->AddPage();

    //////////
    // Logo //
    //////////

    $pdf->SetXY(0, 0);
    // $pdf->SetXY(30, 2);
    $logo = (file_exists($factura['info']->empresa->logo)) ? $factura['info']->empresa->logo : 'application/images/logo2.png' ;
    $pdf->Image($logo, 10, null, 0, 21);

    //////////////////////////
    // Rfc y Regimen Fiscal //
    //////////////////////////

    // 0, 171, 72 = verde

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(0, $pdf->GetY() + 4);
    $pdf->Cell(108, 4, "Serie - Folio:", 0, 0, 'R', 1);

    // $pdf->SetXY(109, 0);
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Cell(50, 4, ($factura['info']->id_nc==''? 'Venta del Dia': 'Nota de Credito'), 0, 0, 'L', 1);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(0, $pdf->GetY() + 4);
    $pdf->Cell(108, 4, $factura['info']->serie.'-'.$factura['info']->folio , 0, 0, 'C', 0);

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
    $pdf->Row(array('RFC:', $factura['info']->empresa->rfc), false, false, null, 2, 1);
    $pdf->SetWidths(array(19, 196));
    $pdf->SetX(0);
    $pdf->Row(array('NOMBRE:', $factura['info']->empresa->nombre_fiscal), false, false, null, 2, 1);
    $pdf->SetX(0);
    $pdf->Row(array('DOMICILIO:', $factura['info']->empresa->calle.' No. '.$factura['info']->empresa->no_exterior.
                                          ((isset($factura['info']->empresa->no_interior)) ? ' Int. '.$factura['info']->empresa->no_interior : '') ), false, false, null, 2, 1);
    $pdf->SetWidths(array(19, 83, 19, 83));
    $pdf->SetX(0);
    $pdf->Row(array('COLONIA:', $factura['info']->empresa->colonia, 'LOCALIDAD:', $factura['info']->empresa->localidad), false, false, null, 2, 1);
    $pdf->SetWidths(array(19, 65, 11, 65, 11, 40));
    $pdf->SetX(0);
    $pdf->Row(array('ESTADO:', $factura['info']->empresa->estado, 'PAIS:', $factura['info']->empresa->pais, 'CP:', $factura['info']->empresa->cp), false, false, null, 2, 1);

    $end_y = $pdf->GetY();

    /////////////////////////////////////
    // Folio Fisca, CSD, Lugar y Fecha //
    /////////////////////////////////////

    // $pdf->SetFont('helvetica','B', 9);
    // $pdf->SetFillColor(242, 242, 242);
    // $pdf->SetTextColor(0, 171, 72);
    // $pdf->SetXY(109, 0);
    // $pdf->Cell(108, 4, "Serie - Folio:", 0, 0, 'R', 1);

    // $pdf->SetXY(109, 0);
    // $pdf->Cell(50, 4, ($factura['info']->id_nc==''? 'Venta de Remisión': 'Nota de Credito'), 0, 0, 'L', 1);

    // $pdf->SetTextColor(0, 0, 0);
    // $pdf->SetXY(109, 6);
    // $pdf->Cell(108, 4, $factura['info']->serie.'-'.$factura['info']->folio , 0, 0, 'C', 0);

    $pdf->SetXY(109, 0);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(109, $pdf->GetY() + 4);
    $pdf->Cell(108, 4, "Lugar. fecha y hora de emisión:", 0, 0, 'R', 1);

    $pdf->SetFont('helvetica','', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(109, $pdf->GetY() + 4);

    $municipio   = strtoupper($factura['info']->empresa->municipio);
    $estado = strtoupper($factura['info']->empresa->estado);
    $fecha = String::fechaATexto($factura['info']->fecha);

    $pdf->Cell(108, 4, "{$municipio}, {$estado} | {$fecha}", 0, 0, 'R', 0);

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
    // $domicilioReceptor = '';
    // $domicilioReceptor .= (isset($xml->Receptor->Domicilio[0]['calle']) ? $xml->Receptor->Domicilio[0]['calle'] : '');
    // $domicilioReceptor .= (isset($xml->Receptor->Domicilio[0]['noExterior']) ? ' #'.$xml->Receptor->Domicilio[0]['noExterior'] : '');
    // $domicilioReceptor .= (isset($xml->Receptor->Domicilio[0]['noInterior'])) ? ' Int. '.$xml->Receptor->Domicilio[0]['noInterior'] : '';
    // $domicilioReceptor .= (isset($xml->Receptor->Domicilio[0]['colonia']) ? ', '.$xml->Receptor->Domicilio[0]['colonia'] : '');
    // $domicilioReceptor .= (isset($xml->Receptor->Domicilio[0]['localidad']) ? ', '.$xml->Receptor->Domicilio[0]['localidad'] : '');
    // $domicilioReceptor .= (isset($xml->Receptor->Domicilio[0]['municipio'])) ? ', '.$xml->Receptor->Domicilio[0]['municipio'] : '';
    // $domicilioReceptor .= (isset($xml->Receptor->Domicilio[0]['estado']) ? ', '.$xml->Receptor->Domicilio[0]['estado'] : '');

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
    $pdf->Row(array('RFC:', $factura['info']->cliente->rfc), false, false, null, 2, 1);
    $pdf->SetWidths(array(19, 196));
    $pdf->SetX(0);
    $pdf->Row(array('NOMBRE:', $factura['info']->cliente->nombre_fiscal), false, false, null, 2, 1);
    $pdf->SetX(0);
    $pdf->Row(array('DOMICILIO:', (isset($factura['info']->cliente->calle) ? $factura['info']->cliente->calle : '').
              ' No. '.(isset($factura['info']->cliente->no_exterior) ? $factura['info']->cliente->no_exterior : '').
              ((isset($factura['info']->cliente->no_interior)) ? ' Int. '.$factura['info']->cliente->no_interior : '') ), false, false, null, 2, 1);
    $pdf->SetWidths(array(19, 83, 19, 83));
    $pdf->SetX(0);
    $pdf->Row(array('COLONIA:', (isset($factura['info']->cliente->colonia) ? $factura['info']->cliente->colonia : ''),
              'LOCALIDAD:', (isset($factura['info']->cliente->localidad) ? $factura['info']->cliente->localidad : '')), false, false, null, 2, 1);
    $pdf->SetWidths(array(19, 65, 11, 65, 11, 40));
    $pdf->SetX(0);
    $pdf->Row(array('ESTADO:', (isset($factura['info']->cliente->estado) ? $factura['info']->cliente->estado : ''),
            'PAIS:', (isset($factura['info']->cliente->pais) ? $factura['info']->cliente->pais : ''),
            'CP:', (isset($factura['info']->cliente->cp) ? $factura['info']->cliente->cp : '') ), false, false, null, 2, 1);


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
    $aligns = array('C', 'C', 'C', 'C', 'C','C');
    $aligns2 = array('C', 'C', 'C', 'C', 'R','R');
    $widths = array(30, 35, 71, 20, 30, 30);
    $header = array('Cantidad', 'Unidad de Medida', 'Descripcion', 'Cert.', 'Precio Unitario', 'Importe');

    $conceptos = $factura['productos'];

    // for ($i=0; $i < 30; $i++)
    //   $conceptos[] = $conceptos[$i];

    // echo "<pre>";
    //   var_dump($conceptos, is_array($conceptos));
    // echo "</pre>";exit;

    // if (! is_array($conceptos))
    //   $conceptos = array($conceptos);

    $traslado11 = 0;
    $traslado16 = 0;

    $pdf->limiteY = 250;

    $pdf->setY($pdf->GetY() + 1);
    $hay_prod_certificados = false;
    $gastos = array();
    $bultoss = 0;
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

      $printRow = true;
      if ($item->id_clasificacion == '49' || $item->id_clasificacion == '50' ||
          $item->id_clasificacion == '51' || $item->id_clasificacion == '52' ||
          $item->id_clasificacion == '53') {
        $printRow = false;
        $gastos[] = $item;
      }

      if ($item->certificado === 't')
        $hay_prod_certificados = true;

      if($printRow)
      {
        if ($item->porcentaje_iva == '11')
          $traslado11 += $item->iva;
        elseif ($item->porcentaje_iva == '16')
          $traslado16 += $item->iva;

        $bultoss += $item->cantidad;

        $pdf->Row(array(
          $item->cantidad,
          $item->unidad,
          $item->descripcion,
          $item->certificado === 't' ? 'Certificado' : '',
          String::formatoNumero($item->precio_unitario, 2, '$', false),
          String::formatoNumero($item->importe, 2, '$', false),
        ), false, true, null, 2, 1);
      }
    }

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetX(0);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths(array(30));
    $pdf->Row(array($bultoss), true, true, null, 2, 1);
    $pdf->SetY($pdf->GetY()+2);

    foreach($gastos as $key => $item)
    {
      if($factura['info']->sin_costo == 'f')
      {
        if ($item->porcentaje_iva == '11')
          $traslado11 += $item->iva;
        elseif ($item->porcentaje_iva == '16')
          $traslado16 += $item->iva;
      }
      $band_head = false;

      if($pdf->GetY() >= $pdf->limiteY || $key === 0) //salta de pagina si exede el max
      {
        if($key > 0) $pdf->AddPage();

        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFillColor(242, 242, 242);
        $pdf->SetX(0);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths(array(216));
        $pdf->Row(array('Gastos'), true, true, null, 2, 1);
      }

      $pdf->SetFont('Arial', '', 8);
      $pdf->SetTextColor(0,0,0);

      $pdf->SetX(0);
      $pdf->SetAligns($aligns2);
      $pdf->SetWidths($widths);

      if ($item->certificado === 't')
        $hay_prod_certificados = true;

      $pdf->Row(array(
        $item->cantidad,
        $item->unidad,
        $item->descripcion,
        $item->certificado === 't' ? 'Certificado' : '',
        String::formatoNumero($item->precio_unitario, 2, '$', false),
        String::formatoNumero($item->importe, 2, '$', false),
      ), false, true, null, 2, 1);
    }

    /////////////
    // Totales //
    /////////////

    if($pdf->GetY() + 30 >= $pdf->limiteY) //salta de pagina si exede el max
        $pdf->AddPage();


    $pdf->SetFillColor(0, 171, 72);
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

    $h = 25 - ($traslado11 == 0 ? 5 : 0);
    $h = $h - ($factura['info']->retencion_iva == 0 ? 5 : 0);

    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetXY(0, $pdf->GetY() + 1);
    $pdf->Cell(156, $h, "", 1, 0, 'L', 1);

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetXY(1, $pdf->GetY() + 1);
    $pdf->Cell(154, 4, "Total con letra:", 0, 0, 'L', 1);

    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(0, $pdf->GetY() + 4);
    $pdf->MultiCell(156, 6, $factura['info']->total_letra, 0, 'C', 0);

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetXY(1, $pdf->GetY());
    $pdf->Cell(78, 4, $factura['info']->forma_pago, 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetXY(78, $pdf->GetY());
    $pdf->Cell(78, 4, "Pago en {$factura['info']->metodo_pago}", 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetXY(156, $pdf->GetY() - 11);
    $pdf->Cell(30, 5, "Subtotal", 1, 0, 'C', 1);

    $pdf->SetXY(186, $pdf->GetY());
    $pdf->Cell(30, 5, String::formatoNumero($factura['info']->subtotal, 2, '$', false), 1, 0, 'R', 1);

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

    if ($factura['info']->retencion_iva != 0)
    {
      $pdf->SetXY(156, $pdf->GetY() + 5);
      $pdf->Cell(30, 5, "IVA Retenido", 1, 0, 'C', 1);

      $pdf->SetXY(186, $pdf->GetY());
      $pdf->Cell(30, 5,String::formatoNumero($factura['info']->retencion_iva, 2, '$', false), 1, 0, 'R', 1);
    }

    $pdf->SetXY(156, $pdf->GetY() + 5);
    $pdf->Cell(30, 5, "TOTAL", 1, 0, 'C', 1);

    $pdf->SetXY(186, $pdf->GetY());
    $pdf->Cell(30, 5,String::formatoNumero($factura['info']->total, 2, '$', false), 1, 0, 'R', 1);

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

    if($hay_prod_certificados)
    {
      if($pdf->GetY() + 12 >= $pdf->limiteY) //salta de pagina si exede el max
          $pdf->AddPage();

      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(10, $pdf->GetY());
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array('GGN4052852866927 PRODUCTO CERTIFICADO'), false, 0);
    }

    ////////////////////
    // Timbrado Datos //
    ////////////////////

    if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
        $pdf->AddPage();

    //------------ IMAGEN CANDELADO --------------------

    if($factura['info']->status === 'ca'){
      $pdf->Image(APPPATH.'/images/cancelado.png', 20, 40, 190, 190, "PNG");
    }

    if ($path)
      $pdf->Output($path.'venta.pdf', 'F');
    else
      $pdf->Output('venta', 'I');
  }

  public function palletsCliente($clienteId)
  {
    $query = $this->db->query(
      "SELECT rp.*,
              (SELECT count(f.id_factura)
               FROM facturacionv AS f
               INNER JOIN facturacionv_pallets AS fp ON fp.id_factura = f.id_factura
               WHERE f.status != 'ca' AND f.status != 'b' AND fp.id_pallet = rp.id_pallet) AS existe
       FROM rastria_pallets AS rp
       WHERE id_cliente = {$_GET['id']} AND
             no_cajas > 0 AND
             (SELECT count(f.id_factura) FROM facturacionv AS f INNER JOIN facturacionv_pallets AS fp ON fp.id_factura = f.id_factura WHERE f.status != 'ca' AND f.status != 'b' AND fp.id_pallet = rp.id_pallet) = 0
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