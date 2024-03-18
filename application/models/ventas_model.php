<?php
class ventas_model extends privilegios_model{

	function __construct(){
		parent::__construct();
    $this->load->model('bitacora_model');
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
	public function getVentas($perpage = '40', $sql2='')
  {
		$sql = '';
    //paginacion
    $params = array(
        'result_items_per_page' => $perpage,
        'result_page'       => (isset($_GET['pag'])? $_GET['pag']: 0)
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
    //  $sql .= " AND c.serie = '".$this->input->get('fserie')."'";
    if($this->input->get('ffolio') != '')
      $sql .= " AND f.folio = '".$this->input->get('ffolio')."'";
    if($this->input->get('fstatus') != '')
      $sql .= " AND f.status = '".$this->input->get('fstatus')."'";
    if($this->input->get('fid_cliente') != '')
      $sql .= " AND f.id_cliente = '".$this->input->get('fid_cliente')."'";
    if($this->input->get('did_empresa') != '')
      $sql .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";

    if($this->input->get('dobserv') != '')
      $sql .= " AND lower(f.Observaciones) LIKE '%".$this->input->get('dobserv')."%'";

    $query = BDUtil::pagination("
        SELECT f.id_factura, Date(f.fecha) AS fecha, f.serie, f.folio, c.nombre_fiscal,
                e.nombre_fiscal as empresa, f.condicion_pago, f.forma_pago, f.status, f.total, f.id_nc,
                f.status_timbrado, f.uuid, f.docs_finalizados, f.observaciones, f.refacturada,
                COALESCE(fh.id_remision, 0) AS facturada, f.cfdi_ext
        FROM facturacion AS f
        INNER JOIN empresas AS e ON e.id_empresa = f.id_empresa
        INNER JOIN clientes AS c ON c.id_cliente = f.id_cliente
        LEFT JOIN (SELECT id_remision, id_factura, status
                  FROM remisiones_historial WHERE status <> 'ca' AND status <> 'b'
        ) fh ON f.id_factura = fh.id_remision
        WHERE 1 = 1 AND f.is_factura = 'f' AND f.status != 'b' ".$sql.$sql2."
        ORDER BY f.fecha DESC, f.folio DESC, f.serie DESC
        ", $params, true);
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
	public function getInfoVenta($id, $info_basic=false, $moneda=false)
  {
		$res = $this->db
      ->select("f.*, fo.no_trazabilidad, fo.id_paleta_salida, fo.no_salida_fruta, fo.extras")
      ->from('facturacion as f')
      ->join('facturacion_otrosdatos as fo', 'f.id_factura = fo.id_factura', 'left')
      ->where("f.id_factura = {$id}")
      ->get();

    if($res->num_rows() > 0)
    {
			$response['info'] = $res->row();
      $response['info']->fechaT = str_replace(' ', 'T', substr($response['info']->fecha, 0, 16));
      $response['info']->fecha = substr($response['info']->fecha, 0, 10);

      //si hay que hacer conversion de moneda
      if ($moneda) {
        $response['info']->subtotal      = ($response['info']->subtotal/($response['info']->tipo_cambio>0? $response['info']->tipo_cambio: 1));
        $response['info']->importe_iva   = ($response['info']->importe_iva/($response['info']->tipo_cambio>0? $response['info']->tipo_cambio: 1));
        $response['info']->retencion_iva = ($response['info']->retencion_iva/($response['info']->tipo_cambio>0? $response['info']->tipo_cambio: 1));
        $response['info']->total         = ($response['info']->total/($response['info']->tipo_cambio>0? $response['info']->tipo_cambio: 1));
      }

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
                u.id_unidad, u.cantidad AS und_kg, fp.kilos, fp.cajas, fp.id_unidad_rendimiento, fp.certificado, fp.id_size_rendimiento,
                ac.nombre AS areas_calidad, ac.id_calidad, at.nombre AS areas_tamanio, at.id_tamanio, fp.descripcion2, fp.cfdi_ext,
                fp.ieps, fp.porcentaje_ieps, cal.nombre AS areas_calibre, cal.id_calibre, fp.porcentaje_iva_real,
                fp.isr, fp.porcentaje_isr, fp.ieps_subtotal')
        ->from('facturacion_productos as fp')
        ->join('clasificaciones as cl', 'cl.id_clasificacion = fp.id_clasificacion', 'left')
        ->join('unidades_unq as u', 'u.nombre = fp.unidad', 'left')
        ->join('otros.areas_calidades as ac', 'ac.id_calidad = fp.id_calidad', 'left')
        ->join('otros.areas_tamanios as at', 'at.id_tamanio = fp.id_tamanio', 'left')
        ->join('calibres as cal', 'cal.id_calibre = fp.id_calibres', 'left')
        ->where('id_factura = ' . $id)->order_by('fp.num_row', 'asc')
        ->get();

      $response['productos'] = $res->result();
      //si hay que hacer conversion de moneda
      if ($moneda) {
        foreach ($response['productos'] as $key => $value) {
          $value->precio_unitario = ($value->precio_unitario/($response['info']->tipo_cambio>0? $response['info']->tipo_cambio: 1));
          $value->importe = ($value->importe/($response['info']->tipo_cambio>0? $response['info']->tipo_cambio: 1));
          $value->iva = ($value->iva/($response['info']->tipo_cambio>0? $response['info']->tipo_cambio: 1));
          $value->retencion_iva = ($value->retencion_iva/($response['info']->tipo_cambio>0? $response['info']->tipo_cambio: 1));
        }
      }

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

        WHERE id_factura = {$id}
        GROUP BY fp.id_pallet, rp.folio, rp.no_cajas, rp.kilos_pallet, cali.calibres
        ORDER BY fp.id_pallet ASC;");

      $response['pallets'] = $res->result();

      $res = $this->db->query("SELECT fsc.*, p.nombre_fiscal as proveedor
         FROM facturacion_seg_cert fsc
         INNER JOIN proveedores p ON p.id_proveedor = fsc.id_proveedor
         WHERE id_factura = {$id}");

      foreach ($res->result() as $tipo)
      {
        if ($tipo->id_clasificacion == 49)
        {
          $response['seguro'][] = $tipo;
        } elseif ($tipo->id_clasificacion == 53)
        {
          $response['supcarga'][] = $tipo;
        }else
        { // Certificados 51 o 52
          $response['certificado'.$tipo->id_clasificacion][] = $tipo;
        }
      }

			return $response;
		}else
			return false;
	}

	/**
	 * Obtiene el folio de acuerdo a la serie seleccionada
	 */
	public function getFolio($empresa, $serie)
  {
		$res = $this->db->select('folio')->
                      from('facturacion')->
                      where("id_empresa = {$empresa}")->
                      where("serie = '{$serie}'")->
                      where("is_factura = 'f'")->
                      order_by('folio', 'DESC')->
                      limit(1)->get()->row();

    $folio      = (isset($res->folio)? $res->folio: 0)+1;
    // $res = new stdClass();
    // $res->folio = $folio;
    // $msg        = 'ok';

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
	 * Agrega una nota remison a la bd
	 */
	public function addNotaVenta()
  {
    $this->load->model('clientes_model');
    $this->load->model('clasificaciones_model');

    $anoAprobacion = explode('-', $_POST['dano_aprobacion']);

    // Obtiene la forma de pago, si es en parcialidades entonces la forma de
    // pago son las parcialidades "Parcialidad 1 de X".
    // $formaPago = ($_POST['dforma_pago'] == 'Pago en parcialidades') ? $this->input->post('dforma_pago_parcialidad') : 'Pago en una sola exhibición';

    $cfdi_ext = [
      'tipoDeComprobante' => ($this->input->post('dtipo_comprobante')=='ingreso'? 'I': 'E'),
      'usoCfdi'           => $this->input->post('duso_cfdi'),
    ];

    if ($this->input->post('cerrarVenta') == 'true') {
      $cfdi_ext['cerrarVenta'] = true;
    }

    $datosFactura = array(
      'id_cliente'          => $this->input->post('did_cliente'),
      'id_empresa'          => $this->input->post('did_empresa'),
      'version'             => $this->input->post('dversion'),
      'serie'               => $this->input->post('dserie'),
      'folio'               => $this->input->post('dfolio'),
      'fecha'               => str_replace('T', ' ', $_POST['dfecha']),
      'subtotal'            => $this->input->post('total_subtotal'),
      'importe_iva'         => $this->input->post('total_iva'),
      'retencion_iva'       => $this->input->post('total_retiva'),
      'ieps'                => floatval($this->input->post('total_ieps')),
      'isr'                 => floatval($this->input->post('total_isr')),
      'total'               => $this->input->post('total_totfac'),
      'total_letra'         => $this->input->post('dttotal_letra'),
      'no_aprobacion'       => $this->input->post('dno_aprobacion'),
      'ano_aprobacion'      => $anoAprobacion[0],
      'tipo_comprobante'    => $this->input->post('dtipo_comprobante'),
      'forma_pago'          => $this->input->post('dforma_pago'),
      'metodo_pago'         => $this->input->post('dmetodo_pago'),
      'metodo_pago_digitos' => ($_POST['dmetodo_pago'] === 'efectivo') ? 'No identificado' : ($_POST['dmetodo_pago_digitos'] !== '' ? $_POST['dmetodo_pago_digitos']  : 'No identificado'),
      'no_certificado'      => $this->input->post('dno_certificado'),
      'cadena_original'      => '',
      'sello'                => '',
      'certificado'          => '',
      'condicion_pago'      => $this->input->post('dcondicion_pago'),
      'plazo_credito'       => $_POST['dcondicion_pago'] === 'co' ? 0 : $this->input->post('dplazo_credito'),
      'observaciones'       => $this->input->post('dobservaciones'),
      'status'              => 'p', //$_POST['dcondicion_pago'] === 'co' ? 'pa' : 'p',
      'status_timbrado'     => 'p',
      'sin_costo'           => isset($_POST['dsincosto']) ? 't' : 'f',
      'is_factura'          => 'f',
      'sin_costo_nover'     => isset($_POST['dsincosto_nover']) ? 't' : 'f',
      'moneda'              => $_POST['moneda'],
      'cfdi_ext'            => json_encode($cfdi_ext),
    );
    //Si existe el parametro es una nota de credito de la factura
    $bitacora_accion = 'la nota de remision';
    if(isset($_POST['id_nrc']{0}))
    {
      $datosFactura['id_nc'] = $_POST['id_nrc'];
      $datosFactura['status'] = 'pa';
      $bitacora_accion = 'la nota de credito';
    }

    // Tipo de cambio y moneda
    if ($datosFactura['moneda'] !== 'MXN')
      $datosFactura['tipo_cambio'] = $_POST['tipoCambio'];
    else
      $datosFactura['tipo_cambio'] = '1';

    $this->db->insert('facturacion', $datosFactura);
    $id_venta = $this->db->insert_id('facturacion_id_factura_seq');

    // Si tiene el # de trazabilidad
    if ($this->input->post('dno_trazabilidad') !== false || $this->input->post('dno_salida_fruta') !== false) {
      $this->db->insert('facturacion_otrosdatos', [
        'id_factura'       => $id_venta,
        'no_trazabilidad'  => $this->input->post('dno_trazabilidad'),
        'id_paleta_salida' => ($this->input->post('id_paleta_salida')? $this->input->post('id_paleta_salida'): NULL),
        'no_salida_fruta'  => $this->input->post('dno_salida_fruta'),
      ]);
    }

    // Si tiene el # de Salida de fruta
    if ($this->input->post('dno_salida_fruta') !== false && $this->input->post('dno_salida_fruta') != '') {
      $this->db->insert('facturacion_otrosdatos', [
        'id_factura'       => $id_venta,

        'id_paleta_salida' => $this->input->post('id_paleta_salida')
      ]);
    }

    // si probiene de una venta se asigna
    if (isset($_GET['id_vd'])) {
      $this->load->model('ventas_dia_model');
      $this->ventas_dia_model->idFacturaVenta(array('id_factura' => $id_venta, 'id_venta' => $_GET['id_vd']));
    }

    // Bitacora
    $this->bitacora_model->_insert('facturacion', $id_venta,
                                    array(':accion'    => $bitacora_accion, ':seccion' => 'nota de remision',
                                          ':folio'     => $datosFactura['serie'].$datosFactura['folio'],
                                          ':id_empresa' => $datosFactura['id_empresa'],
                                          ':empresa'   => 'en '.$this->input->post('dempresa')));

    // Obtiene los datos del cliente.
    $cliente = $this->clientes_model->getClienteInfo($this->input->post('did_cliente'), true);
    $dataCliente = array(
      'id_factura'    => $id_venta,
      'nombre'      => $cliente['info']->nombre_fiscal,
      'rfc'         => $cliente['info']->rfc,
      'calle'       => $cliente['info']->calle,
      'no_exterior' => $cliente['info']->no_exterior,
      'no_interior' => $cliente['info']->no_interior,
      'colonia'     => $cliente['info']->colonia,
      'localidad'   => $cliente['info']->localidad,
      'municipio'   => $cliente['info']->municipio,
      'estado'      => $cliente['info']->estado,
      'cp'          => $cliente['info']->cp,
      'pais'        => 'MEXICO',
    );
    $this->db->insert('facturacion_cliente', $dataCliente);

    $dataSeguroCerti = array();
    $nrow_seg_cer = 0;
    $seg_cer_entro = array();
    $serieFolio = $datosFactura['serie'].$datosFactura['folio'];

    // Productos
    $productosFactura   = array();
    $produccionFactura  = array();
    foreach ($_POST['prod_ddescripcion'] as $key => $descripcion)
    {
      if ($_POST['prod_dcantidad'][$key] > 0)
      {
        $did_unidad = (isset($_POST['prod_dmedida_id'][$key])? $_POST['prod_dmedida_id'][$key]: NULL);
        $dunidad_c = NULL;
        if ($did_unidad > 0) { // obtenemos la cantidad de la unidad
          $data_unidad = $this->db->query("SELECT cantidad FROM unidades WHERE id_unidad = {$did_unidad}")->row();
          $dunidad_c = $data_unidad->cantidad>0? $data_unidad->cantidad: NULL;
        }

        // Para descontar del inventario de productos de produccion
        if (isset($_POST['prod_did_prod'][$key]{0})) {
          $clasificacion = $this->clasificaciones_model->getClasificacionInfo($_POST['prod_did_prod'][$key], true);
          if ($clasificacion['info']->inventario == 't' && $_POST['prod_did_prod'][$key] !== '') {
            $produccionFactura[] = array(
              'id_factura'       => $id_venta,
              'id_empresa'       => $datosFactura['id_empresa'],
              'id_empleado'      => $this->session->userdata('id_usuario'),
              'id_clasificacion' => $_POST['prod_did_prod'][$key],
              'cantidad'         => $_POST['prod_dcantidad'][$key],
              'fecha_produccion' => $datosFactura['fecha'],
              'precio_venta'     => $_POST['prod_dpreciou'][$key],
              'tipo'             => 'f',
            );
          }
        }

        $cfdi_ext = [
          'clave_unidad' => [
            'key'   => $_POST['pclave_unidad_cod'][$key],
            'value' => $_POST['pclave_unidad'][$key],
          ]
        ];

        $productosFactura[] = array(
          'id_factura'            => $id_venta,
          'id_clasificacion'      => $_POST['prod_did_prod'][$key] !== '' ? $_POST['prod_did_prod'][$key] : null,
          'num_row'               => intval($key),
          'cantidad'              => $_POST['prod_dcantidad'][$key],
          'descripcion'           => $descripcion,
          'precio_unitario'       => $_POST['prod_dpreciou'][$key],
          'importe'               => $_POST['prod_importe'][$key],
          'iva'                   => $_POST['prod_diva_total'][$key],
          'unidad'                => $_POST['prod_dmedida'][$key],
          'retencion_iva'         => $_POST['prod_dreten_iva_total'][$key],
          'porcentaje_iva'        => ($_POST['prod_diva_porcent'][$key]=='exento'? 0: $_POST['prod_diva_porcent'][$key]),
          'porcentaje_iva_real'   => $_POST['prod_diva_porcent'][$key],
          'porcentaje_retencion'  => $_POST['prod_dreten_iva_porcent'][$key],
          'ieps'                  => $_POST['dieps_total'][$key],
          'porcentaje_ieps'       => $_POST['dieps'][$key],
          'isr'                   => (isset($_POST['disr_total'][$key])? floatval($_POST['disr_total'][$key]): 0),
          'porcentaje_isr'        => (isset($_POST['disr'][$key])? floatval($_POST['disr'][$key]): 0),
          'ids_pallets'           => $_POST['pallets_id'][$key] !== '' ? $_POST['pallets_id'][$key] : null,
          'kilos'                 => ($this->input->post('did_empresa') == 24? $_POST['prod_dkilos'][$key]: ($_POST['prod_dcantidad'][$key] * $dunidad_c)), //$_POST['prod_dkilos'][$key],
          'cajas'                 => $_POST['prod_dcajas'][$key],
          'id_unidad_rendimiento' => $_POST['id_unidad_rendimiento'][$key] !== '' ? $_POST['id_unidad_rendimiento'][$key] : null,
          'id_size_rendimiento'   => isset($_POST['id_size_rendimiento'][$key]) && $_POST['id_size_rendimiento'][$key] !== '' ? $_POST['id_size_rendimiento'][$key] : null,
          'certificado'           => $_POST['isCert'][$key] === '1' ? 't' : 'f',
          'id_unidad'             => $did_unidad,
          'unidad_c'              => $dunidad_c,
          'id_calidad'            => ($_POST['prod_did_calidad'][$key] !== ''? $_POST['prod_did_calidad'][$key]: NULL),
          'id_tamanio'            => ($_POST['prod_did_tamanio'][$key] !== ''? $_POST['prod_did_tamanio'][$key]: NULL),
          'id_calibres'           => ($_POST['prod_did_tamanio_prod'][$key] !== ''? $_POST['prod_did_tamanio_prod'][$key]: NULL),
          'descripcion2'          => $_POST['prod_ddescripcion2'][$key],
          'cfdi_ext'              => json_encode($cfdi_ext),
          'ieps_subtotal'         => $_POST['prod_ieps_subtotal'][$key] === 't' ? 't' : 'f',
        );

        if ($_POST['prod_did_prod'][$key] === '49' && !isset($seg_cer_entro['49']))
        {
          foreach ($_POST['seg_id_proveedor'] as $keysecer => $data_secer) {
            if ($_POST['seg_id_proveedor'][$keysecer] > 0) {
              $dataSeguroCerti[] = array(
                'id_factura'       => $id_venta,
                'id_clasificacion' => $_POST['prod_did_prod'][$key],
                'nrow'             => $nrow_seg_cer,
                'id_proveedor'     => $_POST['seg_id_proveedor'][$keysecer],
                'pol_seg'          => $_POST['seg_poliza'][$keysecer],
                'folio'            => $serieFolio,
                'bultos'           => 0,
                'certificado'      => null,
                'num_operacion'    => null,
                'id_orden'         => null,
                'no_certificado'   => null,
              );
              ++$nrow_seg_cer;
            }
          }
          $seg_cer_entro[$_POST['prod_did_prod'][$key]] = true;
        }

        // Certificados
        if (($_POST['prod_did_prod'][$key] === '51' && !isset($seg_cer_entro['51'])) ||
          ($_POST['prod_did_prod'][$key] === '52' && !isset($seg_cer_entro['52'])) ||
          ($_POST['prod_did_prod'][$key] === '1603' && !isset($seg_cer_entro['1603']))
        ) {
          foreach ($_POST['cert_id_proveedor'.$_POST['prod_did_prod'][$key]] as $keysecer => $data_secer) {
            if ($_POST['cert_id_proveedor'.$_POST['prod_did_prod'][$key]][$keysecer] > 0) {
              $dataSeguroCerti[] = array(
                'id_factura'       => $id_venta,
                'id_clasificacion' => $_POST['prod_did_prod'][$key],
                'nrow'             => $nrow_seg_cer,
                'id_proveedor'     => $_POST['cert_id_proveedor'.$_POST['prod_did_prod'][$key]][$keysecer],
                'certificado'      => $_POST['cert_certificado'.$_POST['prod_did_prod'][$key]][$keysecer],
                'folio'            => $serieFolio,
                'bultos'           => $_POST['cert_bultos'.$_POST['prod_did_prod'][$key]][$keysecer],
                'pol_seg'          => null,
                'num_operacion'    => $_POST['cert_num_operacion'.$_POST['prod_did_prod'][$key]][$keysecer],
                'id_orden'         => (!empty($_POST['cert_id_orden'.$_POST['prod_did_prod'][$key]][$keysecer])? $_POST['cert_id_orden'.$_POST['prod_did_prod'][$key]][$keysecer]: null),
                'no_certificado'   => (!empty($_POST['cert_no_certificado'.$_POST['prod_did_prod'][$key]][$keysecer])? $_POST['cert_no_certificado'.$_POST['prod_did_prod'][$key]][$keysecer]: null),
              );
              ++$nrow_seg_cer;
            }
          }
          $seg_cer_entro[$_POST['prod_did_prod'][$key]] = true;
        }

        if ($_POST['prod_did_prod'][$key] === '53' && !isset($seg_cer_entro['53']))
        {
          foreach ($_POST['supcarga_id_proveedor'] as $keysecer => $data_secer) {
            if ($_POST['supcarga_id_proveedor'][$keysecer] > 0) {
              $dataSeguroCerti[] = array(
                'id_factura'       => $id_venta,
                'id_clasificacion' => $_POST['prod_did_prod'][$key],
                'nrow'             => $nrow_seg_cer,
                'id_proveedor'     => $_POST['supcarga_id_proveedor'][$keysecer],
                'certificado'      => $_POST['supcarga_numero'][$keysecer],
                'folio'            => $serieFolio,
                'bultos'           => $_POST['supcarga_bultos'][$keysecer],
                'pol_seg'          => null,
                'num_operacion'    => $_POST['supcarga_num_operacion'][$keysecer],
                'id_orden'         => null,
                'no_certificado'   => null,
              );
              ++$nrow_seg_cer;
            }
          }
          $seg_cer_entro[$_POST['prod_did_prod'][$key]] = true;
        }

      }
    }
    if(count($productosFactura) > 0)
      $this->db->insert_batch('facturacion_productos', $productosFactura);
    log_message('error', var_export($productosFactura, true));


    if(count($produccionFactura) > 0)
      $this->db->insert_batch('otros.produccion_historial', $produccionFactura);

    if (count($dataSeguroCerti) > 0)
    {
      $this->db->delete('facturacion_seg_cert', array('id_factura' => $id_venta));
      $this->db->insert_batch('facturacion_seg_cert', $dataSeguroCerti);
    }

    if (isset($_POST['palletsIds']))
    {
      $pallets = array(); // Ids de los pallets cargados en la factura.
      // Crea el array de los pallets a insertar.
      foreach ($_POST['palletsIds'] as $palletId)
      {
        $pallets[] = array(
          'id_factura' => $id_venta,
          'id_pallet'  => $palletId
        );
      }

      if (count($pallets) > 0)
      {
        $this->db->insert_batch('facturacion_pallets', $pallets);
      }
    }

    $this->load->model('documentos_model');
    $this->load->model('facturacion_model');

    // Obtiene los documentos que el cliente tiene asignados.
    $docsCliente = $this->facturacion_model->getClienteDocs($datosFactura['id_cliente'], $id_venta);
    $pathDocs = $this->documentos_model->creaDirectorioDocsCliente($dataCliente['nombre'], $datosFactura['serie'], $datosFactura['folio'], substr($datosFactura['fecha'], 0, 10));

    // Inserta los documentos del cliente con un status false.
    if ($docsCliente)
      $this->db->insert_batch('facturacion_documentos', $docsCliente);
    else
      $datosFactura['docs_finalizados'] = 't';

    //Si es otra moneda actualiza al tipo de cambio
    if($datosFactura['moneda'] !== 'MXN')
    {
      $datosFactura1 = array();
      $datosFactura1['total']         = number_format($datosFactura['total']*$datosFactura['tipo_cambio'], 2, '.', '');
      $datosFactura1['subtotal']      = number_format($datosFactura['subtotal']*$datosFactura['tipo_cambio'], 2, '.', '');
      $datosFactura1['importe_iva']   = number_format($datosFactura['importe_iva']*$datosFactura['tipo_cambio'], 2, '.', '');
      $datosFactura1['retencion_iva'] = number_format($datosFactura['retencion_iva']*$datosFactura['tipo_cambio'], 2, '.', '');
      $this->db->update('facturacion', $datosFactura1, array('id_factura' => $id_venta));

      foreach ($productosFactura as $key => $value)
      {
        $value['precio_unitario'] = number_format($value['precio_unitario']*$datosFactura['tipo_cambio'], 2, '.', '');
        $value['importe']         = number_format($value['importe']*$datosFactura['tipo_cambio'], 2, '.', '');
        $value['iva']             = number_format($value['iva']*$datosFactura['tipo_cambio'], 2, '.', '');
        $value['retencion_iva']   = number_format($value['retencion_iva']*$datosFactura['tipo_cambio'], 2, '.', '');
        $this->db->update('facturacion_productos', $value, "id_factura = {$value['id_factura']} AND num_row = {$value['num_row']}");
      }
    }

    // Si es al contado y es la bodega de guadalajara Paga la factura automaticamente
    if ($this->input->post('dcondicion_pago') == 'co' && preg_match("/bodega/i", $this->input->post('dempresa')) === 1 ) {
      $this->load->model('cuentas_cobrar_model');
      $this->load->model('banco_cuentas_model');

      $cuentas = $this->db->query("SELECT * FROM banco_cuentas
                                 WHERE status = 'ac' AND id_empresa = ".$this->input->post('did_empresa'))->row();

      $_GET['tipo'] = 'v';
      $data = array('fecha'  => substr($_POST['dfecha'], 0, 10),
            'concepto'       => 'Pago de contado '.$this->input->post('dserie').$this->input->post('dfolio'),
            'total'          => $this->input->post('total_totfac'), //$total,
            'id_cuenta'      => $cuentas->id_cuenta,
            'ref_movimiento' => $this->input->post('dserie').$this->input->post('dfolio'),
            'saldar'         => 'no' );
      $resa = $this->cuentas_cobrar_model->addAbono($data, $id_venta);
    }

    $this->db->query("SELECT refreshallmaterializedviews();");

    $this->generaNotaRemisionPdf($id_venta, $pathDocs);

    // // Registra la salida de productos si tiene pallets
    // $this->addSalidaProductosPallets($id_venta, $_POST['did_empresa']);

    // Agregar datos a la caja de existencia de limon EMPAQUE, PACHITA BRAND, GUBALU PRODUCE
    if ($datosFactura['id_empresa'] == 2 || $datosFactura['id_empresa'] == 46 || $datosFactura['id_empresa'] == 15) {
      $this->load->model('existencias_limon_model');
      $datosFactura['id_venta'] = $id_venta;
      $this->existencias_limon_model->guardarRemisionesGastos($datosFactura, $productosFactura);
    }

		return array('passes' => true, 'id_venta' => $id_venta);
	}

  public function addNotaVentaData($data)
  {
    foreach ($data as $key => $remision) {
      $serfolio = $this->getFolio($remision['remision']['id_empresa'], $remision['remision']['serie']);
      $remision['remision']['serie']          = $serfolio[0]->serie;
      $remision['remision']['folio']          = $serfolio[0]->folio;
      $remision['remision']['no_aprobacion']  = $serfolio[0]->no_aprobacion;
      $remision['remision']['ano_aprobacion'] = substr($serfolio[0]->ano_aprobacion, 0, 4);

      $this->db->insert('facturacion', $remision['remision']);
      $id_venta = $this->db->insert_id('facturacion_id_factura_seq');

      $remision['otrosdatos']['id_factura'] = $id_venta;
      $this->db->insert('facturacion_otrosdatos', $remision['otrosdatos']);

      $remision['cliente']['id_factura'] = $id_venta;
      $this->db->insert('facturacion_cliente', $remision['cliente']);

      foreach ($remision['productos'] as $keyp => $producto) {
        $producto['id_factura'] = $id_venta;
        $this->db->insert('facturacion_productos', $producto);
      }
    }

    return true;
  }

  public function updateNotaVenta($id_venta)
  {
    $this->load->model('clientes_model');
    $this->load->model('clasificaciones_model');
    $infoVenta = $this->getInfoVenta($id_venta);

    $anoAprobacion = explode('-', $_POST['dano_aprobacion']);

    // Obtiene la forma de pago, si es en parcialidades entonces la forma de
    // pago son las parcialidades "Parcialidad 1 de X".
    // $formaPago = ($_POST['dforma_pago'] == 'Pago en parcialidades') ? $this->input->post('dforma_pago_parcialidad') : 'Pago en una sola exhibición';

    $cfdi_ext = [
      'tipoDeComprobante' => ($this->input->post('dtipo_comprobante')=='ingreso'? 'I': 'E'),
      'usoCfdi'           => $this->input->post('duso_cfdi'),
    ];

    if ($this->input->post('cerrarVenta') == 'true') {
      $cfdi_ext['cerrarVenta'] = true;
    }

    $datosFactura = array(
      'id_cliente'          => $this->input->post('did_cliente'),
      'id_empresa'          => $this->input->post('did_empresa'),
      'version'             => $this->input->post('dversion'),
      'serie'               => $this->input->post('dserie'),
      'folio'               => $this->input->post('dfolio'),
      'fecha'               => str_replace('T', ' ', $_POST['dfecha']),
      'subtotal'            => $this->input->post('total_subtotal'),
      'importe_iva'         => $this->input->post('total_iva'),
      'retencion_iva'       => $this->input->post('total_retiva'),
      'ieps'                => floatval($this->input->post('total_ieps')),
      'isr'                 => floatval($this->input->post('total_isr')),
      'total'               => $this->input->post('total_totfac'),
      'total_letra'         => $this->input->post('dttotal_letra'),
      'no_aprobacion'       => $this->input->post('dno_aprobacion'),
      'ano_aprobacion'      => $anoAprobacion[0],
      'tipo_comprobante'    => $this->input->post('dtipo_comprobante'),
      'forma_pago'          => $this->input->post('dforma_pago'),
      'metodo_pago'         => $this->input->post('dmetodo_pago'),
      'metodo_pago_digitos' => ($_POST['dmetodo_pago'] === 'efectivo') ? 'No identificado' : ($_POST['dmetodo_pago_digitos'] !== '' ? $_POST['dmetodo_pago_digitos']  : 'No identificado'),
      'no_certificado'      => $this->input->post('dno_certificado'),
      'cadena_original'      => '',
      'sello'                => '',
      'certificado'          => '',
      'condicion_pago'      => $this->input->post('dcondicion_pago'),
      'plazo_credito'       => $_POST['dcondicion_pago'] === 'co' ? 0 : $this->input->post('dplazo_credito'),
      'observaciones'       => $this->input->post('dobservaciones'),
      'status'              => 'p', //$_POST['dcondicion_pago'] === 'co' ? 'pa' : 'p',
      'status_timbrado'     => 'p',
      'sin_costo'           => isset($_POST['dsincosto']) ? 't' : 'f',
      'is_factura'          => 'f',
      'sin_costo_nover'     => isset($_POST['dsincosto_nover']) ? 't' : 'f',
      'moneda'              => $_POST['moneda'],
      'cfdi_ext'            => json_encode($cfdi_ext),
    );

    //Si es otra moneda actualiza al tipo de cambio
    if($datosFactura['moneda'] !== 'MXN')
    {
      $datosFactura['tipo_cambio']   = $_POST['tipoCambio'];
      $datosFactura['total']         = number_format($datosFactura['total']*$datosFactura['tipo_cambio'], 2, '.', '');
      $datosFactura['subtotal']      = number_format($datosFactura['subtotal']*$datosFactura['tipo_cambio'], 2, '.', '');
      $datosFactura['importe_iva']   = number_format($datosFactura['importe_iva']*$datosFactura['tipo_cambio'], 2, '.', '');
      $datosFactura['retencion_iva'] = number_format($datosFactura['retencion_iva']*$datosFactura['tipo_cambio'], 2, '.', '');
    } else
      $datosFactura['tipo_cambio'] = '1';

    // Bitacora
    $id_bitacora = $this->bitacora_model->_update('facturacion', $id_venta, $datosFactura,
                              array(':accion'       => 'la nota de remision', ':seccion' => 'nota de remision',
                                    ':folio'        => $datosFactura['serie'].$datosFactura['folio'],
                                    ':id_empresa'   => $datosFactura['id_empresa'],
                                    ':empresa'      => 'de '.$this->input->post('dempresa'),
                                    ':id'           => 'id_factura',
                                    ':titulo'       => 'Venta'));
    $this->db->update('facturacion', $datosFactura, "id_factura = {$id_venta}");
    // $id_venta = $this->db->insert_id('facturacion_id_factura_seq');

    // Si tiene el # de trazabilidad
    if ($this->input->post('dno_trazabilidad') !== false || $this->input->post('dno_salida_fruta') !== false) {
      $extras = $infoVenta['info']->extras;

      $this->db->delete('facturacion_otrosdatos', "id_factura = {$id_venta}");
      $this->db->insert('facturacion_otrosdatos', [
        'id_factura'       => $id_venta,
        'no_trazabilidad'  => $this->input->post('dno_trazabilidad'),
        'id_paleta_salida' => ($this->input->post('id_paleta_salida')? $this->input->post('id_paleta_salida'): NULL),
        'no_salida_fruta'  => $this->input->post('dno_salida_fruta'),
        'extras'           => $extras,
      ]);
    }

    // Obtiene los datos del cliente.
    $cliente = $this->clientes_model->getClienteInfo($this->input->post('did_cliente'), true);
    $dataCliente = array(
      'nombre'      => $cliente['info']->nombre_fiscal,
      'rfc'         => $cliente['info']->rfc,
      'calle'       => $cliente['info']->calle,
      'no_exterior' => $cliente['info']->no_exterior,
      'no_interior' => $cliente['info']->no_interior,
      'colonia'     => $cliente['info']->colonia,
      'localidad'   => $cliente['info']->localidad,
      'municipio'   => $cliente['info']->municipio,
      'estado'      => $cliente['info']->estado,
      'cp'          => $cliente['info']->cp,
      'pais'        => 'MEXICO',
    );
    $this->db->update('facturacion_cliente', $dataCliente, "id_factura = {$id_venta}");

    $dataSeguroCerti = array();
    $serieFolio = $datosFactura['serie'].$datosFactura['folio'];

    // Productos
    $productosFactura   = array();
    $produccionFactura   = array();
    $nrow_seg_cer = 0;
    $seg_cer_entro = array();
    foreach ($_POST['prod_ddescripcion'] as $key => $descripcion)
    {
      if ($_POST['prod_dcantidad'][$key] > 0)
      {
        $did_unidad = (isset($_POST['prod_dmedida_id'][$key])? $_POST['prod_dmedida_id'][$key]: NULL);
        $dunidad_c = NULL;
        if ($did_unidad > 0) { // obtenemos la cantidad de la unidad
          $data_unidad = $this->db->query("SELECT cantidad FROM unidades WHERE id_unidad = {$did_unidad}")->row();
          $dunidad_c = $data_unidad->cantidad>0? $data_unidad->cantidad: NULL;
        }

        // Para descontar del inventario de productos de produccion
        $clasificacion = $this->clasificaciones_model->getClasificacionInfo($_POST['prod_did_prod'][$key], true);
        if ($clasificacion['info']->inventario == 't' && $_POST['prod_did_prod'][$key] !== '') {
          $produccionFactura[] = array(
            'id_factura'       => $id_venta,
            'id_empresa'       => $datosFactura['id_empresa'],
            'id_empleado'      => $this->session->userdata('id_usuario'),
            'id_clasificacion' => $_POST['prod_did_prod'][$key],
            'cantidad'         => $_POST['prod_dcantidad'][$key],
            'fecha_produccion' => $datosFactura['fecha'],
            'precio_venta'     => $_POST['prod_dpreciou'][$key],
            'tipo'             => 'f',
          );
        }

        $cfdi_ext = [
          'clave_unidad' => [
            'key'   => $_POST['pclave_unidad_cod'][$key],
            'value' => $_POST['pclave_unidad'][$key],
          ]
        ];

        $productosFactura[] = array(
          'id_factura'            => $id_venta,
          'id_clasificacion'      => $_POST['prod_did_prod'][$key] !== '' ? $_POST['prod_did_prod'][$key] : null,
          'num_row'               => intval($key),
          'cantidad'              => $_POST['prod_dcantidad'][$key],
          'descripcion'           => $descripcion,
          'precio_unitario'       => $_POST['prod_dpreciou'][$key],
          'importe'               => $_POST['prod_importe'][$key],
          'iva'                   => $_POST['prod_diva_total'][$key],
          'unidad'                => $_POST['prod_dmedida'][$key],
          'retencion_iva'         => $_POST['prod_dreten_iva_total'][$key],
          'porcentaje_iva'        => ($_POST['prod_diva_porcent'][$key]=='exento'? 0: $_POST['prod_diva_porcent'][$key]),
          'porcentaje_iva_real'   => $_POST['prod_diva_porcent'][$key],
          'porcentaje_retencion'  => $_POST['prod_dreten_iva_porcent'][$key],
          'ieps'                  => $_POST['dieps_total'][$key],
          'porcentaje_ieps'       => $_POST['dieps'][$key],
          'isr'                   => (isset($_POST['disr_total'][$key])? floatval($_POST['disr_total'][$key]): 0),
          'porcentaje_isr'        => (isset($_POST['disr'][$key])? floatval($_POST['disr'][$key]): 0),
          'ids_pallets'           => $_POST['pallets_id'][$key] !== '' ? $_POST['pallets_id'][$key] : null,
          'kilos'                 => ($this->input->post('did_empresa') == 24? $_POST['prod_dkilos'][$key]: ($_POST['prod_dcantidad'][$key] * $dunidad_c)), //$_POST['prod_dkilos'][$key],
          'cajas'                 => $_POST['prod_dcajas'][$key],
          'id_unidad_rendimiento' => $_POST['id_unidad_rendimiento'][$key] !== '' ? $_POST['id_unidad_rendimiento'][$key] : null,
          'id_size_rendimiento'   => isset($_POST['id_size_rendimiento'][$key]) && $_POST['id_size_rendimiento'][$key] !== '' ? $_POST['id_size_rendimiento'][$key] : null,
          'certificado'           => $_POST['isCert'][$key] === '1' ? 't' : 'f',
          'id_unidad'             => $did_unidad,
          'unidad_c'              => $dunidad_c,
          'id_calidad'            => (isset($_POST['prod_did_calidad'][$key])? $_POST['prod_did_calidad'][$key]: NULL),
          'id_tamanio'            => (isset($_POST['prod_did_tamanio'][$key])? $_POST['prod_did_tamanio'][$key]: NULL),
          'id_calibres'           => ($_POST['prod_did_tamanio_prod'][$key] !== ''? $_POST['prod_did_tamanio_prod'][$key]: NULL),
          'descripcion2'          => $_POST['prod_ddescripcion2'][$key],
          'cfdi_ext'              => json_encode($cfdi_ext),
          'ieps_subtotal'         => $_POST['prod_ieps_subtotal'][$key] === 't' ? 't' : 'f',
        );

        if ($_POST['prod_did_prod'][$key] === '49' && !isset($seg_cer_entro['49']))
        {
          foreach ($_POST['seg_id_proveedor'] as $keysecer => $data_secer) {
            if ($_POST['seg_id_proveedor'][$keysecer] > 0) {
              $dataSeguroCerti[] = array(
                'id_factura'       => $id_venta,
                'id_clasificacion' => $_POST['prod_did_prod'][$key],
                'nrow'             => $nrow_seg_cer,
                'id_proveedor'     => $_POST['seg_id_proveedor'][$keysecer],
                'pol_seg'          => $_POST['seg_poliza'][$keysecer],
                'folio'            => $serieFolio,
                'bultos'           => 0,
                'certificado'      => null,
                'num_operacion'    => null,
                'id_orden'         => null,
                'no_certificado'   => null,
              );
              ++$nrow_seg_cer;
            }
          }
          $seg_cer_entro[$_POST['prod_did_prod'][$key]] = true;
        }

        if (($_POST['prod_did_prod'][$key] === '51' && !isset($seg_cer_entro['51'])) || ($_POST['prod_did_prod'][$key] === '52' && !isset($seg_cer_entro['52'])))
        {
          foreach ($_POST['cert_id_proveedor'.$_POST['prod_did_prod'][$key]] as $keysecer => $data_secer) {
            if ($_POST['cert_id_proveedor'.$_POST['prod_did_prod'][$key]][$keysecer] > 0) {
              $dataSeguroCerti[] = array(
                'id_factura'       => $id_venta,
                'id_clasificacion' => $_POST['prod_did_prod'][$key],
                'nrow'             => $nrow_seg_cer,
                'id_proveedor'     => $_POST['cert_id_proveedor'.$_POST['prod_did_prod'][$key]][$keysecer],
                'certificado'      => $_POST['cert_certificado'.$_POST['prod_did_prod'][$key]][$keysecer],
                'folio'            => $serieFolio,
                'bultos'           => $_POST['cert_bultos'.$_POST['prod_did_prod'][$key]][$keysecer],
                'pol_seg'          => null,
                'num_operacion'    => $_POST['cert_num_operacion'.$_POST['prod_did_prod'][$key]][$keysecer],
                'id_orden'         => (!empty($_POST['cert_id_orden'.$_POST['prod_did_prod'][$key]][$keysecer])? $_POST['cert_id_orden'.$_POST['prod_did_prod'][$key]][$keysecer]: null),
                'no_certificado'   => (!empty($_POST['cert_no_certificado'.$_POST['prod_did_prod'][$key]][$keysecer])? $_POST['cert_no_certificado'.$_POST['prod_did_prod'][$key]][$keysecer]: null),
              );
              ++$nrow_seg_cer;
            }
          }
          $seg_cer_entro[$_POST['prod_did_prod'][$key]] = true;
        }

        if ($_POST['prod_did_prod'][$key] === '53' && !isset($seg_cer_entro['53']))
        {
          foreach ($_POST['supcarga_id_proveedor'] as $keysecer => $data_secer) {
            if ($_POST['supcarga_id_proveedor'][$keysecer] > 0) {
              $dataSeguroCerti[] = array(
                'id_factura'       => $id_venta,
                'id_clasificacion' => $_POST['prod_did_prod'][$key],
                'nrow'             => $nrow_seg_cer,
                'id_proveedor'     => $_POST['supcarga_id_proveedor'][$keysecer],
                'certificado'      => $_POST['supcarga_numero'][$keysecer],
                'folio'            => $serieFolio,
                'bultos'           => $_POST['supcarga_bultos'][$keysecer],
                'pol_seg'          => null,
                'num_operacion'    => $_POST['supcarga_num_operacion'][$keysecer],
                'id_orden'         => null,
                'no_certificado'   => null,
              );
              ++$nrow_seg_cer;
            }
          }
          $seg_cer_entro[$_POST['prod_did_prod'][$key]] = true;
        }
      }
    }
    // Bitacora
    $this->bitacora_model->_updateExt($id_bitacora, 'facturacion_productos', $id_venta, $productosFactura,
                              array(':id'     => 'id_factura',
                                    ':titulo' => 'Productos',
                                    ':updates_fields' => 'facturacion_productos_ventas'));
    $this->db->delete('facturacion_productos', "id_factura = {$id_venta}");
    if(count($productosFactura) > 0)
      $this->db->insert_batch('facturacion_productos', $productosFactura);
    log_message('error', var_export($productosFactura, true));


    $this->db->delete('otros.produccion_historial', "id_factura = {$id_venta}");
    if(count($produccionFactura) > 0)
      $this->db->insert_batch('otros.produccion_historial', $produccionFactura);

    if (count($dataSeguroCerti) > 0)
    {
      $this->db->delete('facturacion_seg_cert', array('id_factura' => $id_venta));
      $this->db->insert_batch('facturacion_seg_cert', $dataSeguroCerti);
    }

    if (isset($_POST['palletsIds']))
    {
      $pallets = array(); // Ids de los pallets cargados en la factura.
      // Crea el array de los pallets a insertar.
      foreach ($_POST['palletsIds'] as $palletId)
      {
        $pallets[] = array(
          'id_factura' => $id_venta,
          'id_pallet'  => $palletId
        );
      }

      $this->db->delete('facturacion_pallets', "id_factura = {$id_venta}");
      if (count($pallets) > 0)
      {
        $this->db->insert_batch('facturacion_pallets', $pallets);
      }
    }

    $this->load->model('documentos_model');
    // $this->load->model('facturacion_model');

    // // Obtiene los documentos que el cliente tiene asignados.
    // $docsCliente = $this->facturacion_model->getClienteDocs($datosFactura['id_cliente'], $id_venta);
    $pathDocs = $this->documentos_model->creaDirectorioDocsCliente($dataCliente['nombre'], $datosFactura['serie'], $datosFactura['folio'], substr($datosFactura['fecha'], 0, 10));

    // // Inserta los documentos del cliente con un status false.
    // if ($docsCliente)
    //   $this->db->insert_batch('facturacion_documentos', $docsCliente);
    // else
    //   $datosFactura['docs_finalizados'] = 't';

    //Si es otra moneda actualiza al tipo de cambio
    if($datosFactura['moneda'] !== 'MXN')
    {
      foreach ($productosFactura as $key => $value)
      {
        $value['precio_unitario'] = number_format($value['precio_unitario']*$datosFactura['tipo_cambio'], 2, '.', '');
        $value['importe']         = number_format($value['importe']*$datosFactura['tipo_cambio'], 2, '.', '');
        $value['iva']             = number_format($value['iva']*$datosFactura['tipo_cambio'], 2, '.', '');
        $value['retencion_iva']   = number_format($value['retencion_iva']*$datosFactura['tipo_cambio'], 2, '.', '');
        $this->db->update('facturacion_productos', $value, "id_factura = {$value['id_factura']} AND num_row = {$value['num_row']}");
      }
    }

    $this->generaNotaRemisionPdf($id_venta, $pathDocs);

    $this->db->query("SELECT refreshallmaterializedviews();");

    // // Registra la salida de productos si tiene pallets
    // $this->addSalidaProductosPallets($id_venta, $_POST['did_empresa']);

    // Agregar datos a la caja de existencia de limon EMPAQUE, PACHITA BRAND, GUBALU PRODUCE
    if ($datosFactura['id_empresa'] == 2 || $datosFactura['id_empresa'] == 46 || $datosFactura['id_empresa'] == 15) {
      $this->load->model('existencias_limon_model');
      $datosFactura['id_venta'] = $id_venta;
      $this->existencias_limon_model->guardarRemisionesGastos($datosFactura, $productosFactura);
    }

    return array('passes' => true, 'id_venta' => $id_venta);
  }

  // public function addSalidaProductosPallets($id_venta, $id_empresa)
  // {
  //   // Elimina la salida si tiene
  //   $this->db->delete('compras_salidas', array('id_factura' => $id_venta));

  //   // Procesa la salida
  //   $this->load->model('unidades_model');
  //   $this->load->model('productos_salidas_model');
  //   $this->load->model('inventario_model');

  //   $infoSalida      = array();
  //   $productosSalida = array(); // contiene los productos que se daran salida.
  //   // Se obtienen los pallets ligados a la factura
  //   $listaPallets = $this->db->query("SELECT id_pallet FROM facturacion_pallets WHERE id_factura = {$id_venta}")->result();
  //   // Si hay pallets ligados
  //   if(count($listaPallets) > 0)
  //   {
  //     $lipallets = array();
  //     foreach ($listaPallets as $keylp => $lipallet) {
  //       $lipallets[] = $lipallet->id_pallet;
  //     }
  //     $productosPallets = $this->db->query("SELECT id_pallet, id_producto, cantidad, nom_row
  //                         FROM rastria_pallets_salidas WHERE id_pallet IN(".implode(',', $lipallets).") AND id_producto IS NOT NULL")->result();
  //     if (count($productosPallets))
  //     {
  //       $infoSalida = array(
  //         'id_empresa'      => $id_empresa,
  //         'id_empleado'     => $this->session->userdata('id_usuario'),
  //         'folio'           => $this->productos_salidas_model->folio(),
  //         'fecha_creacion'  => date('Y-m-d H:i:s'),
  //         'fecha_registro'  => date('Y-m-d H:i:s'),
  //         'status'          => 's',
  //         'id_factura'      => $id_venta,
  //       );

  //       $ress = $this->productos_salidas_model->agregar($infoSalida);

  //       $row = 0;
  //       foreach ($productosPallets as $keypp => $prodspp) {
  //         $inv   = $this->inventario_model->promedioData($prodspp->id_producto, date('Y-m-d'), date('Y-m-d'));
  //         $saldo = array_shift($inv);
  //         $productosSalida[] = array(
  //               'id_salida'       => $ress['id_salida'],
  //               'id_producto'     => $prodspp->id_producto,
  //               'no_row'          => $row,
  //               'cantidad'        => $prodspp->cantidad,
  //               'precio_unitario' => $saldo['saldo'][1],
  //             );

  //         $row++;
  //       }
  //     }

  //     // Si hay al menos 1 producto para las salidas lo inserta.
  //     if (count($productosSalida) > 0)
  //     {
  //       $this->productos_salidas_model->agregarProductos(null, $productosSalida);
  //     }

  //     // Si no hay productos para ninguna de las medidas elimina la salida.
  //     else
  //     {
  //       $this->db->delete('compras_salidas', array('id_salida' => $ress['id_salida']));
  //     }
  //   }
  // }

	/**
	 * Cancela una nota, la elimina
	 */
	public function cancelaNotaRemison($id_venta){
    $this->load->model('documentos_model');

    $this->db->update('facturacion', array('status' => 'ca'), "id_factura = '{$id_venta}'");
    $remision = $this->getInfoVenta($id_venta);

    // Cancela los productos de produccion historial
    $this->db->update('otros.produccion_historial', array('status' => 'f'), "id_factura = '{$id_venta}'");

    // Quita la asignacion de la factura a la venta del dia
    $this->load->model('ventas_dia_model');
    $this->ventas_dia_model->idFacturaVenta(array('id_factura' => $id_venta), true);

    // Regenera el PDF de la factura.
    $pathDocs = $this->documentos_model->creaDirectorioDocsCliente($remision['info']->cliente->nombre_fiscal, $remision['info']->serie, $remision['info']->folio, substr($remision['info']->fecha, 0, 10));
    $this->generaNotaRemisionPdf($id_venta, $pathDocs);

    // Elimina la salida de productos q se dio si se ligaron pallets
    $this->db->delete('compras_salidas', array('id_factura' => $id_venta));

    $this->db->query("SELECT refreshallmaterializedviews();");

    // Bitacora
    $bitacora_accion = 'la nota de remision';
    if($remision['info']->id_nc > 0)
      $bitacora_accion = 'la nota de credito';
    $this->bitacora_model->_cancel('facturacion', $id_venta,
                                    array(':accion'     => $bitacora_accion, ':seccion' => 'nota de remision',
                                          ':folio'      => $remision['info']->serie.$remision['info']->folio,
                                          ':id_empresa' => $remision['info']->id_empresa,
                                          ':empresa'    => 'de '.$remision['info']->empresa->nombre_fiscal));


		return array(true, '');
	}

  /**
   * Paga una nota
   */
  public function pagaNotaRemison($id_venta){
    $this->db->update('facturacion', array('status' => 'pa'), "id_factura = '{$id_venta}'");
    return array(true, '');
  }

	// public function generaNotaRemisionPdf2($id_venta, $path = null)
	// {
 //    // include(APPPATH.'libraries/phpqrcode/qrlib.php');
 //    $venta = $this->getInfoVenta($id_venta);

 //    // echo "<pre>";
 //    //   var_dump($venta);
 //    // echo "</pre>";exit;

 //    $this->load->library('mypdf');

 //    // Creación del objeto de la clase heredada
 //    $pdf = new MYpdf('P', 'mm', 'Letter');

 //    $pdf->show_head = false;

 //    $pdf->AliasNbPages();
 //    $pdf->AddPage();

 //    //////////
 //    // Logo //
 //    //////////

 //    $pdf->SetXY(30, 2);
 //    $pdf->Image(APPPATH.'images/logo.png');

 //    //////////////////////////
 //    // Rfc y Regimen Fiscal //
 //    //////////////////////////

 //    // 0, 171, 72 = verde

 //    $pdf->SetFont('helvetica','B', 18);
 //    // $pdf->SetFillColor(0, 171, 72);
 //    $pdf->SetTextColor(255, 255, 255);
 //    // $pdf->SetXY(0, 0);
 //    // $pdf->Cell(108, 15, "Factura Electrónica (CFDI)", 0, 0, 'C', 1);

 //    $pdf->SetTextColor(0, 0, 0);
 //    $pdf->SetXY(3, $pdf->GetY());
 //    $pdf->Cell(108, 14, "RFC: {$venta['info']->empresa->rfc}", 0, 0, 'L', 0);

 //    $pdf->SetFont('helvetica','B', 13);
 //    $pdf->SetTextColor(0, 0, 0);
 //    $pdf->SetXY(3, $pdf->GetY() + 10);
 //    $pdf->Cell(216, 8, $venta['info']->empresa->nombre_fiscal, 0, 0, 'L', 0);

 //    // $pdf->SetFont('helvetica','B', 14);
 //    // $pdf->SetFillColor(242, 242, 242);
 //    // $pdf->SetTextColor(0, 171, 72);
 //    // $pdf->SetXY(0, $pdf->GetY() + 14);
 //    // $pdf->Cell(108, 8, "Régimen Fiscal:", 0, 0, 'L', 1);

 //    // $pdf->SetFont('helvetica','', 12);
 //    // $pdf->SetTextColor(0, 0, 0);
 //    // $pdf->SetXY(0, $pdf->GetY() + 8);
 //    // $pdf->MultiCell(108, 6, $venta['info']->empresa->regimen_fiscal, 0, 'C', 0);

 //    /////////////////////////////////////
 //    // Folio Fisca, CSD, Lugar y Fecha //
 //    /////////////////////////////////////

 //    $pdf->SetFont('helvetica','B', 14);
 //    $pdf->SetFillColor(242, 242, 242);
 //    $pdf->SetTextColor(0, 171, 72);
 //    $pdf->SetXY(109, 0);
 //    $pdf->Cell(108, 8, "Folio:", 0, 0, 'L', 1);

 //    $pdf->SetTextColor(0, 0, 0);
 //    $pdf->SetXY(109, 0);
 //    $pdf->Cell(108, 8, $venta['info']->folio, 0, 0, 'C', 0);

 //    // $pdf->SetTextColor(0, 171, 72);
 //    // $pdf->SetXY(109, $pdf->GetY() + 8);
 //    // $pdf->Cell(108, 8, "No de Serie del Certificado del CSD:", 0, 0, 'R', 1);

 //    // $pdf->SetTextColor(0, 0, 0);
 //    // $pdf->SetXY(109, $pdf->GetY() + 8);
 //    // $pdf->Cell(108, 8, $xml->Complemento->TimbreFiscalDigital[0]['noCertificadoSAT'], 0, 0, 'C', 0);

 //    $pdf->SetFillColor(242, 242, 242);
 //    $pdf->SetTextColor(0, 171, 72);
 //    $pdf->SetXY(109, $pdf->GetY() + 9);
 //    $pdf->Cell(108, 8, "Lugar. fecha y hora de emisión:", 0, 0, 'R', 1);

 //    $pdf->SetFont('helvetica','', 12);
 //    $pdf->SetTextColor(0, 0, 0);
 //    $pdf->SetXY(109, $pdf->GetY() + 8);

 //    $pais   = strtoupper($venta['info']->empresa->pais);
 //    $estado = strtoupper($venta['info']->empresa->estado);
 //    $fecha = $venta['info']->fecha;

 //    $pdf->Cell(108, 8, "{$pais}, {$estado} {$fecha}", 0, 0, 'R', 0);

 //    //////////////////
 //    // Rfc Receptor //
 //    //////////////////

 //    $pdf->SetFillColor(0, 171, 72);
 //    $pdf->SetXY(0, $pdf->GetY() + 20);
 //    $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

 //    $pdf->SetFont('helvetica','B', 13);
 //    $pdf->SetFillColor(242, 242, 242);
 //    $pdf->SetTextColor(0, 0, 0);
 //    $pdf->SetXY(0, $pdf->GetY() + 1);
 //    $pdf->Cell(216, 8, "RFC Receptor: {$venta['info']->cliente->rfc}", 0, 0, 'L', 1);

 //    $pdf->SetFont('helvetica','B', 12);
 //    $pdf->SetTextColor(0, 0, 0);
 //    $pdf->SetXY(0, $pdf->GetY() + 8);
 //    $pdf->Cell(216, 8, $venta['info']->cliente->nombre_fiscal, 0, 0, 'L', 1);

 //    ///////////////
 //    // Productos //
 //    ///////////////

 //    $pdf->SetFillColor(0, 171, 72);
 //    $pdf->SetXY(0, $pdf->GetY() + 8);
 //    $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

 //    $pdf->SetXY(0, $pdf->GetY());
 //    $aligns = array('C', 'C', 'C', 'C','C');
 //    $widths = array(30, 35, 91, 30, 30);
 //    $header = array('Cantidad', 'Unidad de Medida', 'Descripcion', 'Precio Unitario', 'Importe');

 //    // $conceptos = current($xml->Conceptos);

 //    // for ($i=0; $i < 3; $i++)
 //    //   $conceptos[] = $conceptos[$i];

 //    // echo "<pre>";
 //    //   var_dump($conceptos, is_array($conceptos));
 //    // echo "</pre>";exit;

 //    // if (! is_array($conceptos))
 //    //   $conceptos = array($conceptos);

 //    $pdf->limiteY = 250;

 //    $pdf->setY($pdf->GetY() + 1);
 //    foreach($venta['productos'] as $key => $item)
 //    {
 //      $band_head = false;

 //      if($pdf->GetY() >= $pdf->limiteY || $key === 0) //salta de pagina si exede el max
 //      {
 //        if($key > 0) $pdf->AddPage();

 //        $pdf->SetFont('Arial', 'B', 10);
 //        $pdf->SetTextColor(0, 0, 0);
 //        $pdf->SetFillColor(242, 242, 242);
 //        $pdf->SetX(0);
 //        $pdf->SetAligns($aligns);
 //        $pdf->SetWidths($widths);
 //        $pdf->Row($header, true);
 //      }

 //      $pdf->SetFont('Arial', '', 10);
 //      $pdf->SetTextColor(0,0,0);

 //      $pdf->SetX(0);
 //      $pdf->SetAligns($aligns);
 //      $pdf->SetWidths($widths);
 //      $pdf->Row(array(
 //        $item->cantidad,
 //        $item->unidad,
 //        $item->descripcion,
 //        MyString::formatoNumero($item->precio_unitario, 3),
 //        MyString::formatoNumero(floatval($item->importe) + floatval($item->iva), 3),
 //      ), false);
 //    }

 //    /////////////
 //    // Totales //
 //    /////////////

 //    if($pdf->GetY() + 30 >= $pdf->limiteY) //salta de pagina si exede el max
 //        $pdf->AddPage();

 //    $pdf->SetFillColor(0, 171, 72);
 //    $pdf->SetXY(0, $pdf->GetY());
 //    $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

 //    $pdf->SetFillColor(242, 242, 242);
 //    $pdf->SetXY(0, $pdf->GetY() + 1);
 //    $pdf->Cell(156, 20, "", 1, 0, 'L', 1);

 //    $pdf->SetFont('helvetica','B', 9);
 //    $pdf->SetXY(1, $pdf->GetY() + 1);
 //    $pdf->Cell(154, 8, "Total con letra:", 0, 0, 'L', 1);

 //    $pdf->SetFont('helvetica', '', 11);
 //    $pdf->SetXY(0, $pdf->GetY() + 8);
 //    $pdf->MultiCell(156, 8, $venta['info']->total_letra, 0, 'C', 0);

 //    $pdf->SetFont('helvetica','B', 10);
 //    $pdf->SetXY(1, $pdf->GetY() + 6);
 //    $pdf->Cell(78, 5, $venta['info']->forma_pago, 0, 0, 'L', 0);

 //    $pdf->SetFont('helvetica','B', 10);
 //    $pdf->SetXY(78, $pdf->GetY());
 //    $pdf->Cell(78, 5, "Pago en {$venta['info']->metodo_pago}", 0, 0, 'L', 0);

 //    // $pdf->SetFont('helvetica','B', 10);
 //    // $pdf->SetXY(156, $pdf->GetY() - 23);
 //    // $pdf->Cell(30, 6, "Subtotal", 1, 0, 'C', 1);

 //    // $pdf->SetXY(186, $pdf->GetY());
 //    // $pdf->Cell(30, 6, MyString::formatoNumero($venta['info']->subtotal), 1, 0, 'C', 1);

 //    $pdf->SetXY(156, $pdf->GetY() - 23);
 //    $pdf->Cell(30, 6, "TOTAL", 1, 0, 'C', 1);

 //    $pdf->SetXY(186, $pdf->GetY());
 //    $pdf->Cell(30, 6,MyString::formatoNumero($venta['info']->total, 2), 1, 0, 'C', 1);

 //    ///////////////////
 //    // Observaciones //
 //    ///////////////////

 //    $pdf->SetXY(0, $pdf->GetY() + 25);

 //    $width = (($pdf->GetStringWidth($venta['info']->observaciones) / 216) * 8) + 9;

 //    if($pdf->GetY() + $width >= $pdf->limiteY) //salta de pagina si exede el max
 //        $pdf->AddPage();

 //    if ( ! empty($venta['info']->observaciones))
 //    {
 //        $pdf->SetXY(0, $pdf->GetY() + 3);
 //        $pdf->SetFont('helvetica','B', 10);
 //        $pdf->SetAligns(array('L'));
 //        $pdf->SetWidths(array(216));
 //        $pdf->Row(array('Observaciones'), true);

 //        $pdf->SetFont('helvetica','', 9);
 //        $pdf->SetXY(0, $pdf->GetY());
 //        $pdf->SetAligns(array('L'));
 //        $pdf->SetWidths(array(216));
 //        $pdf->Row(array($venta['info']->observaciones), true, 1);
 //    }

 //    if ($path)
 //      $pdf->Output($path.'Factura.pdf', 'F');
 //    else
 //      $pdf->Output('Factura', 'I');
	// }

  public function getHistRemision($idVenta)
  {
    $this->load->model('cuentas_cobrar_model');
    $response['facturas'] = $this->db->query("SELECT id_factura, status, serie, folio, Date(fecha) AS fecha
                               FROM remisiones_historial
                               WHERE id_remision = {$idVenta}
                               ORDER BY (serie, folio) ASC")->result();
    $response['abonos_remision'] = $this->cuentas_cobrar_model->getDetalleVentaFacturaData($idVenta, 'r', true, true);
    $response['abonos_factura'] = false;
    foreach ($response['facturas'] as $key => $value) {
      if ($value->status != 'ca' && $value->status != 'b') {
        $response['abonos_factura'] = $this->cuentas_cobrar_model->getDetalleVentaFacturaData($value->id_factura, 'r', true, true);
      }
    }
    return $response;
  }

  public function generaNotaRemisionPdf($idVenta, $path = null)
  {
    // include(APPPATH.'libraries/phpqrcode/qrlib.php');

    $factura = $this->getInfoVenta($idVenta, false, true);
    $hist    = $this->getHistRemision($idVenta);

    if ($factura['info']->version > 3.2) {
      $metodosPago = new MetodosPago();
      $formaPago   = new FormaPago();
      $usoCfdi     = new UsoCfdi();
      $factura['info']->cfdi_ext = $factura['info']->cfdi_ext? json_decode($factura['info']->cfdi_ext): false;
      $factura['info']->metodosPago = $metodosPago->search($factura['info']->metodo_pago);
      $factura['info']->formaPago = $formaPago->search($factura['info']->forma_pago);
      $factura['info']->usoCfdi = $usoCfdi->search($factura['info']->cfdi_ext->usoCfdi);
    }

    $this->load->model('cuentas_cobrar_model');
    $_GET['did_empresa'] = $factura['info']->id_empresa;
    $_GET['fid_cliente'] = $factura['info']->id_cliente;
    $saldo = $this->cuentas_cobrar_model->getCuentasCobrarData();

    $this->load->model('documentos_model');
    $manifiesto_chofer = $this->documentos_model->getJsonDataDocus($idVenta, 1);
    if (isset($manifiesto_chofer->chofer_id)) {
      $data_chofer = $this->db->query("SELECT * FROM choferes WHERE id_chofer = {$manifiesto_chofer->chofer_id}")->row();
    }

    // echo "<pre>";
    //   var_dump($hist);
    // echo "</pre>";exit;

    // echo "<pre>";
    //   var_dump($factura, $factura['info']->cliente->rfc);
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

    $pdf->SetFont('Arial','B', 70);
    $pdf->SetTextColor(160,160,160);
    $pdf->RotatedText(65, 120, ($factura['info']->no_impresiones==0? 'ORIGINAL': 'COPIA #'.$factura['info']->no_impresiones), 45);

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
    $pdf->Cell(50, 4, ($factura['info']->id_nc==''? ($factura['info']->condicion_pago=='co'? 'Venta de Contado': 'Venta de Credito'): 'Nota de Credito'), 0, 0, 'L', 1);
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
    $pdf->Cell(108, 4, "Fecha y hora de impresión:", 0, 0, 'R', 1);

    $pdf->SetFont('helvetica','', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(109, $pdf->GetY() + 4);
    $pdf->Cell(108, 4, MyString::fechaATexto(date("Y-m-d")).' '.date("H:i:s"), 0, 0, 'R', 0);

    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(109, $pdf->GetY() + 4);
    $pdf->Cell(108, 4, "Lugar. fecha y hora de emisión:", 0, 0, 'R', 1);

    $pdf->SetFont('helvetica','', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(109, $pdf->GetY() + 4);

    $municipio   = strtoupper($factura['info']->empresa->municipio);
    $estado = strtoupper($factura['info']->empresa->estado);
    $cp = strtoupper($factura['info']->empresa->cp);
    $fecha = MyString::fechaATexto($factura['info']->fecha);

    $pdf->Cell(108, 4, "{$municipio}, {$estado} ({$cp}) | {$fecha}", 0, 0, 'R', 0);

    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(109, $pdf->GetY() + 4);
    $pdf->Cell(108, 4, "Uso de CFDI:", 0, 0, 'R', 1);

    $pdf->SetFont('helvetica','', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(109, $pdf->GetY() + 4);
    if (isset($factura['info']->usoCfdi)) {
      $pdf->Cell(108, 4, "{$factura['info']->usoCfdi['key']} - {$factura['info']->usoCfdi['value']}", 0, 0, 'R', 0);
    }

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
    $widths = array(25, 35, 71, 25, 30, 30);
    $header = array('Cantidad', 'Unidad de Medida', 'Descripcion', 'Kg.', 'Precio Unitario', 'Importe');

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
    $tkiloss = $bultoss = 0;
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
      if ( GastosProductos::searchGastosProductos($item->id_clasificacion) ){
        if($factura['info']->sin_costo_nover == 'f')
        {
          $printRow = false;
          $gastos[] = $item;
        } else {
          $printRow = false;
        }
      }

      if ($item->certificado === 't')
        $hay_prod_certificados = true;

      if($printRow)
      {
        if ($item->porcentaje_iva == '11')
          $traslado11 += $item->iva;
        elseif ($item->porcentaje_iva == '16')
          $traslado16 += $item->iva;

        $descripcion_ext = strlen($item->descripcion2)>0? " ({$item->descripcion2})": '';
        if ( GastosProductos::searchGastosProductos($item->id_clasificacion) ){
          if($item->id_clasificacion == '49' && isset($factura['seguro']))
            $descripcion_ext .= " (No {$factura['seguro']->pol_seg})";
          elseif(($item->id_clasificacion == '51' || $item->id_clasificacion == '52') && isset($factura['certificado'.$item->id_clasificacion]))
            $descripcion_ext .= " (No {$factura['certificado'.$item->id_clasificacion]->certificado})";
          elseif($item->id_clasificacion == '53' && isset($factura['supcarga']))
            $descripcion_ext .= " (No {$factura['supcarga']->certificado})";
        }else {
          $bultoss += $item->cantidad;
          $tkiloss += ($item->und_kg*$item->cantidad);
        }

        $pdf->Row(array(
          $item->cantidad,
          $item->unidad,
          $item->descripcion.$descripcion_ext,
          ($item->kilos>0? $item->kilos: ($item->und_kg*$item->cantidad)),
          // $item->certificado === 't' ? 'Certificado' : '',
          MyString::formatoNumero( $item->precio_unitario, 2, '$', false),
          MyString::formatoNumero( $item->importe, 2, '$', false),
        ), false, true, null, 2, 1);
      }
    }

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetX(0);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths(array(25, 106, 25));
    $pdf->Row(array($bultoss, '', $tkiloss), true, true, null, 2, 1);
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

      $descripcion_ext = strlen($item->descripcion2)>0? " ({$item->descripcion2})": '';
      if ( GastosProductos::searchGastosProductos($item->id_clasificacion) ){
        if($item->id_clasificacion == '49' && isset($factura['seguro'])){
          $seguro = array_map(function($e) {
              return $e->pol_seg;
          }, $factura['seguro']);
          $descripcion_ext .= " (No ".implode(', ', $seguro).")";
        }
        elseif(($item->id_clasificacion == '51' || $item->id_clasificacion == '52') && isset($factura['certificado'.$item->id_clasificacion])){
          $certificado = array_map(function($e) {
              return $e->certificado;
          }, $factura['certificado'.$item->id_clasificacion]);
          $descripcion_ext .= " (No ".implode(', ', $certificado).")";
        }
        elseif($item->id_clasificacion == '53' && isset($factura['supcarga'])){
          $supcarga = array_map(function($e) {
              return $e->certificado;
          }, $factura['supcarga']);
          $descripcion_ext .= " (No ".implode(', ', $supcarga).")";
        }
      }

      $pdf->Row(array(
        $item->cantidad,
        $item->unidad,
        $item->descripcion.$descripcion_ext,
        ($item->kilos>0? $item->kilos: ($item->und_kg*$item->cantidad)),
        // $item->certificado === 't' ? 'Certificado' : '',
        MyString::formatoNumero( $item->precio_unitario, 2, '$', false),
        MyString::formatoNumero( $item->importe, 2, '$', false),
      ), false, true, null, 2, 1);
    }

    /////////////
    // Totales //
    /////////////

    if($pdf->GetY() + 30 >= $pdf->limiteY) //salta de pagina si exede el max
        $pdf->AddPage();

    // Traslados | IVA
    // $ivas = current($xml->Impuestos->Traslados);

    // if ( ! is_array($ivas))
    // {
    //   $ivas = array($ivas);
    // }

    // $traslado11 = 0;
    // $traslado16 = 0;
    // foreach ($conceptos as $key => $c)
    // {
    //   if ($c->porcentaje_iva == '11')
    //     $traslado11 += $c->iva;
    //   elseif ($c->porcentaje_iva == '16')
    //     $traslado16 += $c->iva;
    // }

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
    if ($factura['info']->version > 3.2)
      $pdf->Cell(78, 4, "{$factura['info']->formaPago['key']} - {$factura['info']->formaPago['value']}", 0, 0, 'L', 1);
    else
      $pdf->Cell(78, 4, $factura['info']->forma_pago, 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetXY(78, $pdf->GetY());
    if ($factura['info']->version > 3.2)
      $pdf->Cell(78, 4, "{$factura['info']->metodosPago['key']} - {$factura['info']->metodosPago['value']}", 0, 0, 'L', 1);
    else
      $pdf->Cell(78, 4, "Pago en ".MyString::getMetodoPago($factura['info']->metodo_pago), 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetXY(156, $pdf->GetY() - 11);
    $pdf->Cell(30, 5, "Subtotal", 1, 0, 'C', 1);

    $pdf->SetXY(186, $pdf->GetY());
    $pdf->Cell(30, 5, MyString::formatoNumero( $factura['info']->subtotal, 2, '$', false), 1, 0, 'R', 1);
    // Pinta traslados, retenciones

    if ($traslado11 != 0)
    {
      $pdf->SetXY(156, $pdf->GetY() + 5);
      $pdf->Cell(30, 5, "IVA(11%)", 1, 0, 'C', 1);

      $pdf->SetXY(186, $pdf->GetY());
      $pdf->Cell(30, 5,MyString::formatoNumero( $traslado11, 2, '$', false), 1, 0, 'R', 1);
    }

    $pdf->SetXY(156, $pdf->GetY() + 5);
    $pdf->Cell(30, 5, "IVA(16%)", 1, 0, 'C', 1);

    $pdf->SetXY(186, $pdf->GetY());
    $pdf->Cell(30, 5,MyString::formatoNumero( $traslado16, 2, '$', false), 1, 0, 'R', 1);

    if ($factura['info']->retencion_iva != 0)
    {
      $pdf->SetXY(156, $pdf->GetY() + 5);
      $pdf->Cell(30, 5, "IVA Retenido", 1, 0, 'C', 1);

      $pdf->SetXY(186, $pdf->GetY());
      $pdf->Cell(30, 5,MyString::formatoNumero( $factura['info']->retencion_iva, 2, '$', false), 1, 0, 'R', 1);
    }

    $pdf->SetXY(156, $pdf->GetY() + 5);
    $pdf->Cell(30, 5, "TOTAL", 1, 0, 'C', 1);

    $pdf->SetXY(186, $pdf->GetY());
    $pdf->Cell(30, 5,MyString::formatoNumero( $factura['info']->total, 2, '$', false), 1, 0, 'R', 1);

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

    if($pdf->GetY() + 12 >= $pdf->limiteY) //salta de pagina si exede el max
        $pdf->AddPage();

    if ($factura['info']->id_paleta_salida > 0) {
      $this->load->model('rastreabilidad_paletas_model');
      $paleta = $this->rastreabilidad_paletas_model->getInfoPaleta($factura['info']->id_paleta_salida);
      $paleta_extra = json_decode($factura['info']->extras);
      $proporcion_bultos = $bultoss * $paleta['paleta']->kilos_neto / (isset($paleta_extra->cajas_totales) && $paleta_extra->cajas_totales > 0? $paleta_extra->cajas_totales: 1);
      // echo "<pre>";
      // var_dump($bultoss, $proporcion_bultos, $paleta_extra);
      // echo "</pre>";exit;

      $pdf->SetXY(0, $pdf->GetY() + 2);
      $pdf->SetFont('Arial', 'B', 9);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetX(0);
      $pdf->SetAligns(['L', 'L', 'L', 'L', 'L', 'L', 'L', 'L']);
      $pdf->SetWidths(array(21, 25, 21, 25, 21, 25, 21, 25));
      $pdf->Row([
        'Boleta', $paleta['paleta']->folio,
        'Kgs Boleta', MyString::formatoNumero($paleta['paleta']->kilos_neto, 2, '', true),
        // 'Proporción', MyString::formatoNumero($proporcion_bultos, 2, '', true),
        'Peso Promedio', MyString::formatoNumero($paleta['paleta']->kilos_neto/(isset($paleta_extra->cajas_totales) && $paleta_extra->cajas_totales > 0? $paleta_extra->cajas_totales: 1), 2, '', true),
        'Cajas papeleta', MyString::formatoNumero((isset($paleta_extra->cajas_totales) && $paleta_extra->cajas_totales > 0? $paleta_extra->cajas_totales: 0), 2, '', true),
      ], true, true, null, 2, 1);
      $pdf->SetX(0);
      $pdf->Row([
        'Kg Tarimas', MyString::formatoNumero((isset($paleta_extra->tarimas_kg)? $paleta_extra->tarimas_kg: ''), 2, '', true),
        'Kg Cajas', MyString::formatoNumero((isset($paleta_extra->cajas_kg)? $paleta_extra->cajas_kg: ''), 2, '', true),
        'Total kg', MyString::formatoNumero((
          $proporcion_bultos -
          (isset($paleta_extra->tarimas_kg)? $paleta_extra->tarimas_kg: 0) -
          (isset($paleta_extra->cajas_kg)? $paleta_extra->cajas_kg: 0)
        ), 2, '', true),
        '', ''
      ], true, true, null, 2, 1);
    }

    if($pdf->GetY() + 12 >= $pdf->limiteY) //salta de pagina si exede el max
        $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetXY(10, $pdf->GetY());
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(196));
    if($hay_prod_certificados)
    {
      $pdf->Row(array('GGN4052852866927 PRODUCTO CERTIFICADO'), false, 0);
      $pdf->SetXY(10, $pdf->GetY());
    }
    // $pdf->SetXY(10, $pdf->GetY()+3);

    ////////////////////
    // pagare      //
    ////////////////////
    $pdf->SetWidths(array($pdf->pag_size[0]));
    $pdf->SetAligns(array('L'));
    if ($factura['info']->condicion_pago == 'cr') {
      $pdf->SetFounts(array($pdf->fount_txt), array(-1));
      $pdf->SetXY(10, $pdf->GetY()+3);
      $pdf->Row2(array('PAGARE No. '.$factura['info']->folio.' Bueno por: '.MyString::formatoNumero($factura['info']->total, 2, '', true).' VENCE: '.MyString::suma_fechas(substr($factura['info']->fecha, 0, 10), $factura['info']->plazo_credito).' Por este pagare reconozco(amos) deber y me(nos) obligo(amos) a pagar incondicionalmente a '.$factura['info']->empresa->nombre_fiscal.', en esta ciudad o en cualquier otra que se nos requiera el pago por la cantidad: '.$factura['info']->total_letra.'  Valor recibido en mercancía a mi(nuestra) entera satisfacción. Este pagare es mercantil y esta regido por la Ley General de Títulos y Operaciones de Crédito en su articulo 173 parte final y artículos correlativos por no ser pagare domiciliado. De no verificarse el pago de la cantidad que este pagare expresa el día de su vencimiento, causara intereses moratorios a 3 % mensual por todo el tiempo que este insoluto, sin perjuicio al cobro mas los gastos que por ello se originen. Reconociendo como obligación incondicional la de pagar la cantidad pactada y los intereses generados así como sus accesorios.' ), false, false, 18);

      if($pdf->GetY() + 15 >= $pdf->limiteY) //salta de pagina si exede el max
        $pdf->AddPage();
      $pdf->SetWidths(array(120));
      $yaux = $pdf->GetY();
      $pdf->SetXY(10, $pdf->GetY());
      $pdf->SetAligns(array('L'));
      $pdf->Row2(array( "OTORGANTE: ".$factura['info']->cliente->nombre_fiscal ), false, false, 5);
      // $pdf->SetFounts(array($pdf->fount_txt), array(-1));
      $pdf->SetXY(10, $pdf->GetY());
      $pdf->Row2(array(
          'DOMICILIO: '.(isset($factura['info']->cliente->calle) ? $factura['info']->cliente->calle : '').
          ' No. '.(isset($factura['info']->cliente->no_exterior) ? $factura['info']->cliente->no_exterior : '').
          ((isset($factura['info']->cliente->no_interior)) ? ' Int. '.$factura['info']->cliente->no_interior : '').
          ((isset($factura['info']->cliente->colonia)) ? ' Col. '.$factura['info']->cliente->colonia : '').
          ((isset($factura['info']->cliente->estado)) ? ', '.$factura['info']->cliente->estado : '').
          ((isset($factura['info']->cliente->pais)) ? ', '.$factura['info']->cliente->pais : '')
       ), false, false);
      $pdf->SetXY(10, $pdf->GetY());
      $pdf->SetAligns(array('L'));
      $pdf->Row2(array('CIUDAD: '.$factura['info']->cliente->municipio.', '.$factura['info']->cliente->estado.', '.MyString::fechaATexto(date("Y-m-d")) ), false, false);

      if ($factura['info']->cliente->show_saldo == 't') {
        $pdf->SetXY(10, $pdf->GetY());
        $saldodelcliente = count($saldo['cuentas']) > 0? $saldo['cuentas'][0]->saldo : 0;
        $pdf->Row2(array('SALDO DEUDOR ACTUALIZADO: '. MyString::formatoNumero($saldodelcliente, 2, '$', false)), false, false);
      }

      $pdf->SetWidths(array(70));
      $pdf->SetXY(130, $yaux+10);
      $pdf->SetAligns(array('C'));
      $pdf->Row2(array('______________________________________________'), false, false);
      $pdf->SetXY(130, $pdf->GetY());
      $pdf->Row2(array('FIRMA'), false, false);
    }

    // datos del camion y chofer
    if (isset($manifiesto_chofer->factura_id)) {
      $pdf->SetY($pdf->GetY()+5);
      if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
        $pdf->AddPage();
      $pdf->SetWidths(array(95));
      $yaux = $pdf->GetY();
      $pdf->SetAligns(array('C'));
      $pdf->SetFounts(array($pdf->fount_txt), array(1));
      $pdf->Row2(array('DATOS DEL CAMION'), false, false);
      $pdf->SetAligns(array('L'));
      $pdf->SetFounts(array($pdf->fount_txt), array(-1));
      $pdf->SetX(10);
      $pdf->Row2(array('Camion Placas: '.$manifiesto_chofer->camion_placas), false, false, 4);
      $pdf->SetX(10);
      $pdf->Row2(array('Placas Termo: '.$manifiesto_chofer->camion_placas_termo), false, false, 4);
      $pdf->SetX(10);
      $pdf->Row2(array('Marca: '.$manifiesto_chofer->camion_marca), false, false, 4);
      $pdf->SetX(10);
      $pdf->Row2(array('Modelo: '.$manifiesto_chofer->camion_model), false, false, 4);
      $pdf->SetX(10);
      $pdf->Row2(array('Color: '.$manifiesto_chofer->camion_color), false, false, 4);
      $yaux1 = $pdf->GetY();
      $pdf->SetXY(105, $yaux);
      $pdf->SetAligns(array('C'));
      $pdf->SetFounts(array($pdf->fount_txt), array(1));
      $pdf->Row2(array('DATOS DEL CHOFER'), false, false);
      $pdf->SetAligns(array('L'));
      $pdf->SetFounts(array($pdf->fount_txt), array(-1));
      $pdf->SetX(105);
      $pdf->Row2(array('Chofer: '.$manifiesto_chofer->chofer), false, false, 4);
      $pdf->SetX(105);
      $pdf->Row2(array('Teléfono: '.$manifiesto_chofer->chofer_tel), false, false, 4);
      $pdf->SetX(105);
      $pdf->Row2(array('No. Licencia: '.$manifiesto_chofer->chofer_no_licencia), false, false, 4);
      $pdf->SetX(105);
      $pdf->Row2(array('No. IFE: '.$manifiesto_chofer->chofer_ife), false, false, 4);
      $pdf->SetY($yaux1);
    }

    $pdf->SetWidths(array($pdf->pag_size[0]));
    $pdf->SetAligns(array('L'));
    if($pdf->GetY() + 50 >= $pdf->limiteY) //salta de pagina si exede el max
      $pdf->AddPage();
    $pdf->SetY($pdf->GetY()+3);
    $pdf->SetAligns(array('C'));
    $pdf->SetFounts(array($pdf->fount_txt), array(1));
    $pdf->Row2(array('MANIFIESTO DEL CHOFER'), false, false);
    $pdf->SetAligns(array('L'));
    $pdf->SetFounts(array($pdf->fount_txt), array(-1));
    $pdf->Row2(array('COMO CHOFER DEL CAMION ARRIBA DESCRITO, MANIFIESTO EN EL PRESENTE DOCUMENTO, QUE EL (LOS) PRODUCTO(S) TRASPORTADO FUE CARGADO EN MI PRESENCIA Y VERIFIQUE QUE VA LIBRE DE CUALQUIER TIPO DE ESTUPEFACIENTE (DROGAS) POR LO QUE EXIMO DE TODA RESPONSABILIDAD AL (LOS) CONTRATANTE(S) '.$factura['info']->empresa->nombre_fiscal.', Y AL (LOS) DESTINATARIO(S) DE CUALQUIER MERCANCIA NO DESCRITA EN EL PRESENTE EMBARQUE, FACTURA O PEDIDO., TENIENDO PROHIBIDO LLEVAR Y/O TRASPORTAR OTRA MERCANCIA Y SI POR ALGUNA CIRCUNSTANCIA LO HAGO, ASUMO LAS CONSECUENCIAS DERIVADAS DE LA VIOLACION A ESTAS DISPOSICIONES.'."\n".
                      'ACEPTO TENER REPERCUCIONES EN EL PAGO DEL FLETE SI NO ENTREGO LA MERCANCIA CONFORME FECHA Y HORA DE ENTREGA Y TAMBIEN SI NO CUMPLO CON LA TEMPERATURA INDICADA, POR MOTIVOS QUE SE RELACIONEN DIRECTAMENTE CON EL MAL ESTADO MECANICO DE MI UNIDAD (CAMION ARRIBA DESCRITO), SE  ME  DESCONTARA  UN  20%  (VEINTE PORCIENTO) DEL  VALOR  DEL  FLETE,  ASI  COMO  CUALQUIER DIFERENCIA O ANORMALIDAD EN LA ENTREGA DE LA MERCANCIA TRASPORTADA.'), false, false, 30);
    $pdf->SetFont($pdf->fount_txt, '', $pdf->font_size-2.3);
    $pdf->Rect($pdf->GetX()+20, $pdf->GetY()-2, 23, 28, 'D');
    $pdf->Text($pdf->GetX()+21, $pdf->GetY()+0.5, 'HUELLA DEL CHOFER');

    $pdf->SetFounts(array($pdf->fount_txt), array(-1));
    $pdf->SetY($pdf->GetY()+3);
    $pdf->SetAligns(array('C'));
    $pdf->Row2(array('______________________________________________'), false, false);
    $pdf->Row2(array( (isset($data_chofer->nombre{0}) ? $data_chofer->nombre : 'FIRMA') ), false, false);

    ////////////////////
    // historial      //
    ////////////////////

    if($pdf->GetY() + 15 >= $pdf->limiteY) //salta de pagina si exede el max
        $pdf->AddPage();

    $yaux = $pdf->GetY()+5;
    if (count($hist['facturas']) > 0) {
      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(5, $pdf->GetY()+5);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(19, 20, 30));
      $pdf->Row(array('Fecha', 'Folio', 'Status'), false, true);
      $pdf->SetFont('helvetica', '', 7.5);
      $pdf->SetXY(5, $pdf->GetY()-1);
      foreach ($hist['facturas'] as $key => $value) {
        $status = 'Pendiente';
        if ($value->status == 'pa')
          $status = 'Pagada';
        elseif($value->status == 'ca')
          $status = 'Cancelada';
        elseif($value->status == 'b')
          $status = 'Borrador';
        $pdf->Row(array(MyString::fechaAT($value->fecha), $value->serie.$value->folio, $status), false, false);
        $pdf->SetXY(5, $pdf->GetY()-2);
      }
    }

    if (count($hist['abonos_remision']['abonos']) > 0 || count($hist['abonos_factura']['abonos']) > 0) {
      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(75, $yaux);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(19, 20, 70, 25));
      $pdf->Row(array('Fecha', 'Folio', 'Concepto', 'Abono'), false, true);
      $pdf->SetFont('helvetica', '', 7.5);
      $pdf->SetXY(75, $pdf->GetY()-1);
      $total_abanos = 0;
      if (count($hist['abonos_remision']['abonos']) > 0)
        foreach($hist['abonos_remision']['abonos'] as $key => $value) {
          $pdf->Row(array(
              $value->fecha,
              $hist['abonos_remision']['cobro'][0]->serie.$hist['abonos_remision']['cobro'][0]->folio,
              $value->concepto,
              MyString::formatoNumero($value->abono, 2, '$', false)), false, false);
          $total_abanos += $value->abono;
          $pdf->SetXY(75, $pdf->GetY()-2);
        }
      if (count($hist['abonos_factura']['abonos']) > 0)
        foreach ($hist['abonos_factura']['abonos'] as $key => $value) {
          $pdf->Row(array(
              $value->fecha,
              $hist['abonos_factura']['cobro'][0]->serie.$hist['abonos_factura']['cobro'][0]->folio,
              $value->concepto,
              MyString::formatoNumero($value->abono, 2, '$', false)), false, false);
          $total_abanos += $value->abono;
          $pdf->SetXY(75, $pdf->GetY()-2);
        }
      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetX(184);
      $pdf->SetWidths(array(19, 20, 70, 25));
      $pdf->Row(array(MyString::formatoNumero($total_abanos, 2, '$', false)), false, false);
    }

    //------------ IMAGEN CANDELADO --------------------

    if($factura['info']->status === 'ca'){
      $pdf->Image(APPPATH.'/images/cancelado.png', 20, 40, 190, 190, "PNG");
    }

    // $this->printValeSalida($factura, $conceptos, $pdf);

    // if (!empty($factura['info']->id_paleta_salida)) {
    //   $this->load->model('rastreabilidad_paletas_model');
    //   $this->rastreabilidad_paletas_model->paleta_pdf($factura['info']->id_paleta_salida, $pdf);
    // }

    if ($path) {
      $pdf->Output($path.'Venta_Remision.pdf', 'F');
    } else {
      // Actualiza el # de impresion
      $this->db->update('facturacion', ['no_impresiones' => $factura['info']->no_impresiones+1], "id_factura = ".$factura['info']->id_factura);
      $file_name = 'Venta_Remision'.rand(0, 1000).'.pdf';
      if (!isset($data_chofer->url_licencia{0}) || !isset($data_chofer->url_ife{0})) {
        $pdf->Output('Venta_Remision', 'I');
      } else {
        if (isset($data_chofer->url_licencia{0})){
          $ext_lic = exif_imagetype($data_chofer->url_licencia);
          if ($ext_lic == IMAGETYPE_GIF || $ext_lic == IMAGETYPE_JPEG || $ext_lic == IMAGETYPE_PNG) {
            $pdf->AddPage();
            $pdf->Image($data_chofer->url_licencia, 10, 10, 200);
          }
        }
        if (isset($data_chofer->url_ife{0})){
          $ext_ife = exif_imagetype($data_chofer->url_ife);
          if ($ext_ife == IMAGETYPE_GIF || $ext_ife == IMAGETYPE_JPEG || $ext_ife == IMAGETYPE_PNG) {
            $pdf->AddPage();
            $pdf->Image($data_chofer->url_ife, 10, 10, 200);
          }
        }

        $pdf->Output(APPPATH.'media/temp/'.$file_name, 'F');

        $this->load->library('MyMergePdf');
        // Creación del objeto de la clase heredada
        $pdf = new MyMergePdf();
        $pdf->addPDF(APPPATH.'media/temp/'.$file_name, 'all');
        if (isset($data_chofer->url_licencia{0}) && $ext_lic === false)
          $pdf->addPDF($data_chofer->url_licencia, 'all');
        if (isset($data_chofer->url_ife{0}) && $ext_ife === false)
          $pdf->addPDF($data_chofer->url_ife, 'all');

        $pdf->merge('browser', 'Venta_Remision.pdf');
        // unlink(APPPATH.'media/temp/'.$file_name);
      }
    }
  }

  public function printValeSalida($factura, $conceptos, &$pdf)
  {
    $pdf->AddPage();
    $pdf->SetXY(0, 0);
    $logo = (file_exists($factura['info']->empresa->logo)) ? $factura['info']->empresa->logo : 'application/images/logo2.png' ;
    $pdf->Image($logo, 10, null, 0, 21);
  }

  public function ticketNotaRemisionPdf($idVenta, $path = null)
  {
    // include(APPPATH.'libraries/phpqrcode/qrlib.php');

    $factura = $this->getInfoVenta($idVenta, false, true);
    $hist    = $this->getHistRemision($idVenta);

    $this->load->model('cuentas_cobrar_model');
    $_GET['did_empresa'] = $factura['info']->id_empresa;
    $_GET['fid_cliente'] = $factura['info']->id_cliente;
    $saldo = $this->cuentas_cobrar_model->getCuentasCobrarData();

    // echo "<pre>";
    //   var_dump($hist);
    // echo "</pre>";exit;

    // echo "<pre>";
    //   var_dump($factura, $factura['info']->cliente->rfc);
    // echo "</pre>";exit;

    $xml = simplexml_load_string(str_replace(array('cfdi:', 'tfd:'), '', $factura['info']->xml));

    // echo "<pre>";
    //   var_dump($factura, $xml);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 270));
    $pdf->show_head = false;
    $pdf->AddPage();
    $pdf->AddFont($pdf->fount_num, '');


    // Título
    $pdf->SetFont($pdf->fount_txt, '', 8);
    $pdf->SetXY(0, 3);
    $pdf->MultiCell($pdf->pag_size[0], 4, $factura['info']->empresa->nombre_fiscal, 0, 'C');
    $pdf->SetFont($pdf->fount_txt, '', 8);
    $pdf->SetX(0);
    $pdf->MultiCell($pdf->pag_size[0], 4, 'RFC: '.$factura['info']->empresa->rfc, 0, 'C');

    $pdf->SetWidths(array($pdf->pag_size[0]));
    $pdf->SetAligns(array('C'));
    $pdf->SetFounts(array($pdf->fount_txt), array(-1));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array(
        ($factura['info']->id_empresa==11? 'Sucursal: Bodega Mercado de Abastos, ': '').
        $factura['info']->empresa->calle.' No. '.$factura['info']->empresa->no_exterior.
        ((isset($factura['info']->empresa->no_interior)) ? ' Int. '.$factura['info']->empresa->no_interior : '').
        ((isset($factura['info']->empresa->colonia)) ? ' Col. '.$factura['info']->empresa->colonia : '').
        ((isset($factura['info']->empresa->estado)) ? ', '.$factura['info']->empresa->estado : '').
        ((isset($factura['info']->empresa->pais)) ? ', '.$factura['info']->empresa->pais : '')
      ), false, false, 7);

    $pdf->SetXY(0, $pdf->GetY()+2);
    $pdf->SetFont($pdf->fount_txt, '', 8);
    $pdf->MultiCell($pdf->pag_size[0], 2, '----------------------------------------------------------------', 0, 'L');

    $pdf->SetWidths(array($pdf->pag_size[0]-31, 30));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_txt), array(1, 1));
    $pdf->SetXY(0, $pdf->GetY()-1);
    $pdf->Row2(array(($factura['info']->id_nc==''? 'Venta '.($factura['info']->condicion_pago=='cr'? 'a Credito': 'al Contado'): 'Nota de Credito'),
                  "Folio: ".$factura['info']->serie.'-'.$factura['info']->folio ), false, false, 5);

    $pdf->SetXY(0, $pdf->GetY());
    $pdf->SetFont($pdf->fount_txt, '', 8);
    $pdf->MultiCell($pdf->pag_size[0], 2, '----------------------------------------------------------------', 0, 'L');

    $pdf->SetWidths(array($pdf->pag_size[0]));
    $pdf->SetAligns(array('L'));
    $pdf->SetFounts(array($pdf->fount_txt), array(-1));
    $pdf->SetX(0);
    $pdf->Row2(array( "FECHA: ".MyString::fechaATexto($factura['info']->fecha, '/c') ), false, false, 4);
    $pdf->SetX(0);
    $pdf->Row2(array( "CLIENTE: ".$factura['info']->cliente->nombre_fiscal ), false, false, 5);

    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row2(array( "RFC: ".$factura['info']->cliente->rfc ), false, false, 5);

    $pdf->SetFounts(array($pdf->fount_txt), array(-1));
    $pdf->SetXY(0, $pdf->GetY()-1);
    $pdf->Row2(array(
        (isset($factura['info']->cliente->calle) ? $factura['info']->cliente->calle : '').
        ' No. '.(isset($factura['info']->cliente->no_exterior) ? $factura['info']->cliente->no_exterior : '').
        ((isset($factura['info']->cliente->no_interior)) ? ' Int. '.$factura['info']->cliente->no_interior : '').
        ((isset($factura['info']->cliente->colonia)) ? ' Col. '.$factura['info']->cliente->colonia : '').
        ((isset($factura['info']->cliente->estado)) ? ', '.$factura['info']->cliente->estado : '').
        ((isset($factura['info']->cliente->pais)) ? ', '.$factura['info']->cliente->pais : '')
     ), false, false, 7);

    $pdf->SetXY(0, $pdf->GetY()+1);
    $pdf->Row2(array( "TEL: ".$factura['info']->cliente->telefono ), false, false, 5);


    ///////////////
    // Productos //
    ///////////////
    $conceptos = $factura['productos'];

    $pdf->SetWidths(array(11, 27, 11, 14));
    $pdf->SetAligns(array('L','L','R','R'));
    $pdf->SetFounts(array($pdf->fount_txt), array(-1.5,-1.5,-1.5,-1.5));
    $pdf->SetXY(0, $pdf->GetY()+1);
    $pdf->Row2(array('CANT.', 'DESCRIPCION', 'PRECIO', 'IMPORTE'), false, true, 4.5);

    $pdf->SetFounts(array($pdf->fount_num,$pdf->fount_txt,$pdf->fount_num,$pdf->fount_num),
                   array(-1,-1.5,-1,-1));
    $traslado11 = 0;
    $traslado16 = 0;

    $pdf->limiteY = 250;

    $pdf->setY($pdf->GetY() + 1);
    $hay_prod_certificados = false;
    $gastos = array();
    $bultoss = 0;
    foreach($conceptos as $key => $item)
    {
      $printRow = true;
      if ( GastosProductos::searchGastosProductos($item->id_clasificacion) ){
        if($factura['info']->sin_costo_nover == 'f')
        {
          $printRow = false;
          $gastos[] = $item;
        } else {
          $printRow = false;
        }
      }

      if ($item->certificado === 't')
        $hay_prod_certificados = true;

      if($printRow)
      {
        if ($item->porcentaje_iva == '11')
          $traslado11 += $item->iva;
        elseif ($item->porcentaje_iva == '16')
          $traslado16 += $item->iva;

        $descripcion_ext = '';
        if ( GastosProductos::searchGastosProductos($item->id_clasificacion) ){
          if($item->id_clasificacion == '49' && isset($factura['seguro']))
            $descripcion_ext = " (No {$factura['seguro']->pol_seg})";
          elseif(($item->id_clasificacion == '51' || $item->id_clasificacion == '52') && isset($factura['certificado'.$item->id_clasificacion]))
            $descripcion_ext = " (No {$factura['certificado'.$item->id_clasificacion]->certificado})";
          elseif($item->id_clasificacion == '53' && isset($factura['supcarga']))
            $descripcion_ext = " (No {$factura['supcarga']->certificado})";
        }else
          $bultoss += $item->cantidad;

        $pdf->SetXY(0, $pdf->GetY()-1.5);
        $pdf->Row2(array(
          $item->cantidad, //.' '.$item->unidad
          substr($item->descripcion, 0, 25), //.$descripcion_ext
          MyString::formatoNumero($item->precio_unitario, 2, '', true),
          MyString::formatoNumero($item->importe, 2, '', true),), false, false);
      }
    }

    $pdf->SetXY(0, $pdf->GetY());
    $pdf->SetFont($pdf->fount_txt, '', 8);
    $pdf->MultiCell($pdf->pag_size[0], 2, '----------------------------------------------------------------', 0, 'L');

    foreach($gastos as $key => $item)
    {
      if($factura['info']->sin_costo == 'f')
      {
        if ($item->porcentaje_iva == '11')
          $traslado11 += $item->iva;
        elseif ($item->porcentaje_iva == '16')
          $traslado16 += $item->iva;
      }

      if ($item->certificado === 't')
        $hay_prod_certificados = true;

      $descripcion_ext = '';
      if ( GastosProductos::searchGastosProductos($item->id_clasificacion) ){
        if($item->id_clasificacion == '49' && isset($factura['seguro'])){
          $seguro = array_map(function($e) {
              return $e->pol_seg;
          }, $factura['seguro']);
          $descripcion_ext .= " (No ".implode(', ', $seguro).")";
        }
        elseif(($item->id_clasificacion == '51' || $item->id_clasificacion == '52') && isset($factura['certificado'.$item->id_clasificacion])){
          $certificado = array_map(function($e) {
              return $e->certificado;
          }, $factura['certificado'.$item->id_clasificacion]);
          $descripcion_ext .= " (No ".implode(', ', $certificado).")";
        }
        elseif($item->id_clasificacion == '53' && isset($factura['supcarga'])){
          $supcarga = array_map(function($e) {
              return $e->certificado;
          }, $factura['supcarga']);
          $descripcion_ext .= " (No ".implode(', ', $supcarga).")";
        }
      }

      $pdf->SetXY(0, $pdf->GetY()-1.5);
      $pdf->Row2(array(
        $item->cantidad, //.' '.$item->unidad
        substr($item->descripcion, 0, 25), //.$descripcion_ext
        MyString::formatoNumero($item->precio_unitario, 2, '', true),
        MyString::formatoNumero($item->importe, 2, '', true),), false, false);
    }

    // /////////////
    // // Totales //
    // /////////////

    $pdf->SetXY(0, $pdf->GetY()+2);
    $pdf->SetFont($pdf->fount_txt, '', 8);
    $pdf->MultiCell($pdf->pag_size[0], 2, '----------------------------------------------------------------', 0, 'L');

    $pdf->SetWidths(array($pdf->pag_size[0]-31, 30));
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_txt), array(0, 0));
    $pdf->SetXY(0, $pdf->GetY()-1);
    $pdf->Row2(array("Subtotal", MyString::formatoNumero($factura['info']->subtotal, 2, '', true) ), false, false, 5);

    // Pinta traslados, retenciones

    if ($traslado11 != 0)
    {
      $pdf->SetXY(0, $pdf->GetY()-1);
      $pdf->Row2(array("IVA(11%)", MyString::formatoNumero($traslado11, 2, '', true) ), false, false, 5);
    }

    $pdf->SetXY(0, $pdf->GetY()-1);
    $pdf->Row2(array("IVA(16%)", MyString::formatoNumero($traslado16, 2, '', true) ), false, false, 5);

    if ($factura['info']->retencion_iva != 0)
    {
      $pdf->SetXY(0, $pdf->GetY()-1);
      $pdf->Row2(array("IVA Retenido", MyString::formatoNumero($factura['info']->retencion_iva, 2, '', true) ), false, false, 5);
    }

    $pdf->SetXY(0, $pdf->GetY()-1);
    $pdf->Row2(array("TOTAL", MyString::formatoNumero($factura['info']->total, 2, '', true) ), false, false, 5);

    $pdf->SetXY(0, $pdf->GetY());
    $pdf->SetFont($pdf->fount_txt, '', 9);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->Row( array(($factura['info']->no_impresiones_tk==0? 'ORIGINAL': 'COPIA #'.$factura['info']->no_impresiones_tk), ''), false, false );
    $pdf->SetXY(0, $pdf->GetY()+2);
    $pdf->SetFont($pdf->fount_txt, '', 8);
    $pdf->MultiCell($pdf->pag_size[0], 2, '----------------------------------------------------------------', 0, 'L');

    $pdf->SetWidths(array($pdf->pag_size[0]));
    $pdf->SetAligns(array('L'));
    if ($factura['info']->condicion_pago == 'cr') {
      $pdf->SetFounts(array($pdf->fount_txt), array(-1));
      $pdf->SetXY(0, $pdf->GetY()-1);
      $pdf->Row2(array('PAGARE No. '.$factura['info']->folio.' Bueno por: '.MyString::formatoNumero($factura['info']->total, 2, '', true).' VENCE: _____________ Por este pagare reconozco(amos) deber y me(nos) obligo(amos) a pagar incondicionalmente a '.$factura['info']->empresa->nombre_fiscal.', en esta ciudad o en cualquier otra que se nos requiera el pago por la cantidad: '.$factura['info']->total_letra.'  Valor recibido en mercancía a mi(nuestra) entera satisfacción. Este pagare es mercantil y esta regido por la Ley General de Títulos y Operaciones de Crédito en su articulo 173 parte final y artículos correlativos por no ser pagare domiciliado. De no verificarse el pago de la cantidad que este pagare expresa el día de su vencimiento, causara intereses moratorios a ____ % mensual por todo el tiempo que este insoluto, sin perjuicio al cobro mas los gastos que por ello se originen. Reconociendo como obligación incondicional la de pagar la cantidad pactada y los intereses generados así como sus accesorios.' ), false, false);
      $pdf->SetXY(0, $pdf->GetY()-18);
      $pdf->SetAligns(array('R'));
      $pdf->Row2(array($factura['info']->cliente->municipio.', '.$factura['info']->cliente->estado.', '.MyString::fechaATexto(date("Y-m-d")) ), false, false);
      $pdf->SetAligns(array('L'));
      $pdf->SetXY(0, $pdf->GetY()-1);
      $pdf->Row2(array( "OTORGANTE: ".$factura['info']->cliente->nombre_fiscal ), false, false, 5);
      // $pdf->SetFounts(array($pdf->fount_txt), array(-1));
      $pdf->SetXY(0, $pdf->GetY());
      $pdf->Row2(array(
          'DOMICILIO: '.(isset($factura['info']->cliente->calle) ? $factura['info']->cliente->calle : '').
          ' No. '.(isset($factura['info']->cliente->no_exterior) ? $factura['info']->cliente->no_exterior : '').
          ((isset($factura['info']->cliente->no_interior)) ? ' Int. '.$factura['info']->cliente->no_interior : '').
          ((isset($factura['info']->cliente->colonia)) ? ' Col. '.$factura['info']->cliente->colonia : '').
          ((isset($factura['info']->cliente->estado)) ? ', '.$factura['info']->cliente->estado : '').
          ((isset($factura['info']->cliente->pais)) ? ', '.$factura['info']->cliente->pais : '')
       ), false, false);
    } else {
      $pdf->SetFounts(array($pdf->fount_txt), array(0));

      $pdf->SetXY(0, $pdf->GetY()-1);
      $pdf->Row2(array($factura['info']->total_letra ), false, false, 6);

      $pdf->SetXY(0, $pdf->GetY());
      $pdf->Row2(array($factura['info']->forma_pago ), false, false, 5);

      $pdf->SetXY(0, $pdf->GetY()-1);
      $pdf->Row2(array(MyString::getMetodoPago($factura['info']->metodo_pago) ), false, false, 5);
    }

    if ($factura['info']->cliente->show_saldo == 't') {
      $pdf->SetAligns(array('L'));
      $pdf->SetXY(0, $pdf->GetY());
      $pdf->Row2(array('SALDO DEUDOR ACTUALIZADO: '. MyString::formatoNumero($saldo['cuentas'][0]->saldo, 2, '$', false)), false, false);
    }


    // ///////////////////
    // // Observaciones //
    // ///////////////////
    $pdf->SetAligns(array('L'));
    if ( ! empty($factura['info']->observaciones)){
      $pdf->SetXY(0, $pdf->GetY()+2);
      $pdf->Row2(array("Observaciones: ".$factura['info']->observaciones ), false, false, 5);
    }


    $pdf->SetXY(0, $pdf->GetY()+7);
    $pdf->SetFont($pdf->fount_txt, '', 8);
    $pdf->MultiCell($pdf->pag_size[0], 2, '_____________________________________', 0, 'C');
    $pdf->SetXY(0, $pdf->GetY()+1.5);
    $pdf->MultiCell($pdf->pag_size[0], 2, 'FIRMA', 0, 'C');

    // Actualiza el # de impresion
    $this->db->update('facturacion', ['no_impresiones_tk' => $factura['info']->no_impresiones_tk+1], "id_factura = ".$factura['info']->id_factura);

    if ($path)
      $pdf->Output($path.'Venta_Remision.pdf', 'F');
    else {
      $pdf->AutoPrint(true);
      $pdf->Output('Venta_Remision', 'I');
    }
  }

  public function imprimir_salidaticket($salidaID, $path = null)
  {
    $this->load->model('compras_areas_model');

    $orden = $this->info($salidaID, true);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 130));
    $pdf->show_head = false;
    $pdf->AddPage();
    $pdf->AddFont($pdf->fount_num, '');

    // Título
    $pdf->SetFont($pdf->fount_txt, '', 8);
    $pdf->SetXY(0, 3);
    $pdf->MultiCell($pdf->pag_size[0], 4, $pdf->titulo1, 0, 'C');
    $pdf->SetFont($pdf->fount_txt, '', 7);
    $pdf->SetX(0);
    $pdf->MultiCell($pdf->pag_size[0], 4, $pdf->reg_fed, 0, 'C');
    $pdf->SetX(0);
    $pdf->MultiCell($pdf->pag_size[0], 4, 'SALIDA DE PRODUCTOS', 0, 'C');

    $pdf->SetX(0);
    $pdf->MultiCell($pdf->pag_size[0], 2, '--------------------------------------------------------------------------', 0, 'L');
    $pdf->SetFont($pdf->fount_txt, '', $pdf->font_size);

    $pdf->SetWidths(array(12, 27, 13, 14));
    $pdf->SetAligns(array('L','L','R','R'));
    $pdf->SetFounts(array($pdf->fount_txt), array(-1,-1,-1,-1));
    $pdf->SetX(0);
    $pdf->Row2(array('CANT.', 'DESCRIPCION', 'PRECIO', 'IMPORTE'), false, true, 5);

    $pdf->SetFounts(array($pdf->fount_num,$pdf->fount_txt,$pdf->fount_num,$pdf->fount_num),
                   array(.5,-1,-1,-1));
    $subtotal = $iva = $total = $retencion = $ieps = 0;
    $tipoCambio = 0;
    $codigoAreas = array();
    foreach ($orden['info'][0]->productos as $key => $prod) {
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array(
        $prod->cantidad.' '.$prod->abreviatura,
        $prod->producto,
        MyString::formatoNumero($prod->precio_unitario, 2, '', true),
        MyString::formatoNumero(($prod->precio_unitario*$prod->cantidad), 2, '', true),), false, false);

      $total += floatval($prod->precio_unitario*$prod->cantidad);

      if($prod->id_area != '' && !array_key_exists($prod->id_area, $codigoAreas))
        $codigoAreas[$prod->id_area] = $this->compras_areas_model->getDescripCodigo($prod->id_area);
    }

    // $pdf->SetX(29);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(13, 20));
    // $pdf->SetX(29);
    // $pdf->Row(array('TOTAL', MyString::formatoNumero($total, 2, '$', false)), false, true);
    $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_num), array(-1,-1));
    $pdf->SetX(30);
    $pdf->Row2(array('TOTAL', MyString::formatoNumero($total, 2, '', true)), false, true, 5);

    $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_num), array(-1,-1));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(66, 0));
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row2(array('REGISTRO: '.strtoupper($orden['info'][0]->empleado), '' ), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('SOLICITA: '.strtoupper($orden['info'][0]->solicito)), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('RECIBE: '.strtoupper($orden['info'][0]->recibio)), false, false);

    $pdf->SetXY(0, $pdf->GetY()+3);
    $pdf->Row2(array('_____________________________________________'), false, false);
    $yy2 = $pdf->GetY();
    if(count($codigoAreas) > 0){
      $yy2 = $pdf->GetY();
      $pdf->SetXY(0, $pdf->GetY());
      $pdf->Row2(array('COD/AREA: ' . implode(' - ', $codigoAreas)), false, false);
    }

    if ($orden['info'][0]->trabajador != '') {
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('Se asigno a: '.strtoupper($orden['info'][0]->trabajador)), false, false);
    }

    $pdf->AutoPrint(true);
    $pdf->Output();
  }

  /*
   |-------------------------------------------------------------------------
   |  REPORTES
   |-------------------------------------------------------------------------
   */

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
      $fecha = $_GET['ffecha1'] < $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

      $this->load->model('empresas_model');
      $client_default = $this->empresas_model->getDefaultEmpresa();
      $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
      $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
      if($this->input->get('did_empresa') != ''){
        $sql .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
      }

      $_GET['dtipo_factura'] = isset($_GET['dtipo_factura'])? $_GET['dtipo_factura']: 'f';
      $tipo_factura = array('', '');
      if($this->input->get('dtipo_factura') != '')
        $tipo_factura = array(" AND f.is_factura='".$this->input->get('dtipo_factura')."'", " AND is_factura='".$this->input->get('dtipo_factura')."'");

      $sql_clientes = '';
      if(is_array($this->input->get('ids_clientes')))
        $sql_clientes = " AND c.id_cliente IN(".implode(',', $this->input->get('ids_clientes')).")";


      $res = $this->db->query(
        "SELECT
          f.id_factura,
          c.nombre_fiscal,
          f.serie,
          f.folio,
          f.status,
          Date(f.fecha) AS fecha,
          COALESCE(f.total, 0) AS cargo,
          COALESCE(f.importe_iva, 0) AS iva,
          COALESCE(ac.abono, 0) AS abono,
          (COALESCE(f.total, 0) - COALESCE(ac.abono, 0))::numeric(100,2) AS saldo,
          (CASE WHEN fh.id_remision IS NOT NULL THEN 't' ELSE 'f' END) facturada
        FROM
          facturacion AS f
          INNER JOIN clientes AS c ON c.id_cliente = f.id_cliente
          LEFT JOIN (
            SELECT id_factura, Sum(abono) AS abono
            FROM (
              (
                SELECT
                  id_factura,
                  Sum(total) AS abono
                FROM
                  facturacion_abonos as fa
                WHERE Date(fecha) <= '{$_GET['ffecha2']}'
                GROUP BY id_factura
              )
              UNION
              (
                SELECT
                  id_nc AS id_factura,
                  Sum(total) AS abono
                FROM
                  facturacion
                WHERE status <> 'ca' AND status <> 'b' AND id_nc IS NOT NULL
                  AND Date(fecha) <= '{$_GET['ffecha2']}'
                GROUP BY id_nc
              )
            ) AS ffs
            GROUP BY id_factura
          ) AS ac ON f.id_factura = ac.id_factura
          LEFT JOIN (SELECT id_remision, id_factura, status
                    FROM remisiones_historial WHERE status <> 'ca' AND status <> 'b'
          ) fh ON f.id_factura = fh.id_remision
        WHERE f.status <> 'b' AND id_nc IS NULL
          AND (Date(f.fecha) >= '{$_GET['ffecha1']}' AND Date(f.fecha) <= '{$_GET['ffecha2']}')
          {$sql}{$tipo_factura[0]}{$sql_clientes}
        ORDER BY folio ASC
        ");

      $response = $res->result();

      return $response;
    }
    /**
    * Reporte compras x cliente pdf
    */
    public function getRVentasrPdf(){
      $res = $this->getRVentascData();

      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');

      if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;

      $pdf->titulo1 = $empresa['info']->nombre_fiscal;
      $pdf->titulo2 = $_GET['dtipo_factura']=='f'? 'REMISIONES': 'FACTURAS';
      $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
      // $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
      $pdf->AliasNbPages();
      // $pdf->AddPage();
      $pdf->SetFont('Arial','',8);

      $aligns = array('L', 'L', 'R', 'L', 'R', 'R');
      $widths = array(20, 15, 15, 90, 30, 30);
      $header = array('Fecha', 'Serie', 'Folio', 'Razon Social', 'Total', 'Pendiente');

      $total_total = 0;
      $total_saldo = 0;
      foreach($res as $key => $factura){
        if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
          $pdf->AddPage();

          $pdf->SetFont('Arial','B',9);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true, false);
        }

        $pdf->SetFont('Arial','',8);

        $txt = '';
        if ($factura->status=='ca') {
          $txt = ' (Cancelada)';
        } elseif ($factura->facturada=='t') {
          $txt = ' (Facturada)';
        } else {
          $total_saldo += $factura->saldo;
          $total_total += $factura->cargo;
        }

        $datos = array(MyString::fechaATexto($factura->fecha, '/c'),
                $factura->serie,
                $factura->folio,
                $factura->nombre_fiscal.$txt,
                MyString::formatoNumero($txt!=''? 0:$factura->cargo, 2, '', false),
                MyString::formatoNumero( ($txt!=''?0:$factura->saldo) , 2, '', false),
              );

        $pdf->SetXY(6, $pdf->GetY()-1);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false, false);

        // $pdf->SetTextColor(255,255,255);
      }

      $pdf->SetX(146);
      $pdf->SetFont('Arial','B',8);
      $pdf->SetAligns(array('R', 'R'));
      $pdf->SetWidths(array(30, 30));
      $pdf->Row(array(
          MyString::formatoNumero($total_total, 2, '', false),
          MyString::formatoNumero($total_saldo, 2, '', false)), false);


      $pdf->Output('reporte_ventas.pdf', 'I');
    }

    public function getRVentasrXLS()
    {
      header('Content-type: application/vnd.ms-excel; charset=utf-8');
      header("Content-Disposition: attachment; filename=ventas_facturas.xls");
      header("Pragma: no-cache");
      header("Expires: 0");

      $res = $this->getRVentascData();

      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

      $titulo1 = $empresa['info']->nombre_fiscal;
      $titulo2 = $_GET['dtipo_factura']=='f'? 'REMISIONES': 'FACTURAS';
      $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";

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
            <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Fecha</td>
            <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Serie</td>
            <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Folio</td>
            <td style="width:300px;border:1px solid #000;background-color: #cccccc;">Razon Social</td>
            <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Total</td>
            <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Pendiente</td>
          </tr>';
      $total_saldo = 0;
      $total_total = 0;
      foreach($res as $key => $factura)
      {
        $total_saldo += ($factura->status=='ca'?0:$factura->saldo);
        $total_total += ($factura->status=='ca'?0:$factura->cargo);

        $html .= '<tr>
            <td style="width:100px;border:1px solid #000;">'.$factura->fecha.'</td>
            <td style="width:100px;border:1px solid #000;">'.$factura->serie.'</td>
            <td style="width:100px;border:1px solid #000;">'.$factura->folio.'</td>
            <td style="width:300px;border:1px solid #000;">'.$factura->nombre_fiscal.($factura->status=='ca'?' (Cancelada)':'').'</td>
            <td style="width:100px;border:1px solid #000;">'.($factura->status=='ca'? 0:$factura->cargo).'</td>
            <td style="width:100px;border:1px solid #000;">'.($factura->status=='ca'?0:$factura->saldo).'</td>
          </tr>';
      }
      $html .= '
          <tr style="font-weight:bold">
            <td colspan="4">TOTALES</td>
            <td>'.$total_total.'</td>
            <td>'.$total_saldo.'</td>
          </tr>
        </tbody>
      </table>';

      echo $html;
    }

    public function getRNotasCredData()
    {
      $sql = '';

      //Filtros para buscar
      $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
      $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
      $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];
      $sql = " AND Date(f.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'";

      $this->load->model('empresas_model');
      $client_default   = $this->empresas_model->getDefaultEmpresa();
      $did_empresa      = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : array($client_default->id_empresa));
      $_GET['dempresa'] = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
      if(is_array($did_empresa))
        $sql .= " AND e.id_empresa IN(".implode(',', $did_empresa).")";

      if(is_array($this->input->get('ids_clientes')))
        $sql .= " AND c.id_cliente IN(".implode(',', $this->input->get('ids_clientes')).")";

      $result = $this->db->query("SELECT f.id_factura, f.serie, f.folio, Date(f.fecha) AS fecha, f.total, c.id_cliente,
          c.nombre_fiscal AS cliente, c.cuenta_cpi, (fa.serie||fa.folio) AS factura, fa.is_factura,
          e.id_empresa, e.nombre_fiscal AS empresa, fp.cantidad, fp.descripcion, f.status
        FROM facturacion f
          INNER JOIN clientes c ON c.id_cliente = f.id_cliente
          INNER JOIN empresas e ON e.id_empresa = f.id_empresa
          INNER JOIN facturacion fa ON fa.id_factura = f.id_nc
          INNER JOIN (SELECT id_factura, Sum(cantidad) AS cantidad, string_agg(descripcion, ',') AS descripcion
            FROM facturacion_productos GROUP BY id_factura
          ) fp ON f.id_factura = fp.id_factura
        WHERE f.id_nc IS NOT NULL {$sql}
        ORDER BY empresa ASC, cliente ASC, fecha ASC");

      $empresa = '';
      $cliente = '';
      $response = array();
      foreach ($result->result() as $key => $value) {
        if ($empresa !== $value->id_empresa) {
          $empresa = $value->id_empresa;
          $cliente = $value->id_cliente;
          $response[$empresa] = array('empresa' => $value->empresa, 'clientes' => array());
          $response[$empresa]['clientes'][$cliente] = array('cliente' => $value->cliente, 'cuenta_cpi' => $value->cuenta_cpi, 'facturas' => array());
          $response[$empresa]['clientes'][$cliente]['facturas'][] = $value;
        }elseif ($cliente !== $value->id_cliente) {
          $cliente = $value->id_cliente;
          $response[$empresa]['clientes'][$cliente] = array('cliente' => $value->cliente, 'cuenta_cpi' => $value->cuenta_cpi, 'facturas' => array());
          $response[$empresa]['clientes'][$cliente]['facturas'][] = $value;
        }else{
          $response[$empresa]['clientes'][$cliente]['facturas'][] = $value;
        }
      }

      return $response;
    }
    /**
    * Reporte compras x cliente pdf
    */
    public function getRNotasCredPdf() {
      $res = $this->getRNotasCredData();

      $con_mov = $this->input->get('dcon_mov')=='si'? false: true;

      $this->load->model('empresas_model');
      // $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');

      // if ($empresa['info']->logo !== '')
      //   $pdf->logo = $empresa['info']->logo;

      // $pdf->titulo1 = $empresa['info']->nombre_fiscal;
      $pdf->titulo2 = 'Notas de credito';
      $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
      // $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
      $pdf->AliasNbPages();
      $pdf->AddPage();
      $pdf->SetFont('Arial','',8);

      $aligns = array('L', 'L', 'R', 'L', 'R', 'R', 'R');
      $widths = array(25, 11, 15, 70, 23, 23, 23);
      $header = array('Fecha', 'Serie', 'Folio', 'Concepto', 'Cantidad', 'Total', 'Factura');

      $total_subtotal_g2 = 0;
      $total_impuesto_g2 = 0;
      $total_total_g2 = 0;
      $total_cantidad_g2 = 0;
      $total_saldo_g2 = 0;
      $total_saldo_cliente_g2 = 0;
      $auxEmpresa = '';
      $auxcliente = '';
      foreach ($res as $keye => $dempresa) {
        $total_subtotal_g = 0;
        $total_impuesto_g = 0;
        $total_total_g = 0;
        $total_cantidad_g = 0;
        $total_saldo_g = 0;
        $total_saldo_cliente_g = 0;

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetX(6);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(180));
        $pdf->Row(array($dempresa['empresa']), false, false);

        foreach($dempresa['clientes'] as $key => $item) {
          if (count($item['facturas']) > 0 || $con_mov)
          {
            $total_total = 0;
            $total_cantidad = 0;

            if($pdf->GetY()+10 >= $pdf->limiteY || $total_total==0){ //salta de pagina si exede el max
              if($total_total > 0)
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
            $pdf->Row(array('CLIENTE:', $item['cuenta_cpi']), false, false);
            $pdf->SetXY(6, $pdf->GetY()-2);
            $pdf->Row(array('NOMBRE:', $item['cliente']), false, false);

            $pdf->SetXY(6, $pdf->GetY()+3);

            foreach ($item['facturas'] as $keyf => $factura)
            {
              $txt_cancelado = ' (Cancelada)';
              if ($factura->status != 'ca') {
                $total_cantidad += $factura->cantidad;
                $total_total += $factura->total;

                $total_cantidad_g += $factura->cantidad;
                $total_total_g += $factura->total;

                $total_cantidad_g2 += $factura->cantidad;
                $total_total_g2 += $factura->total;

                $txt_cancelado = '';
              }

              $datos = array(MyString::fechaATexto($factura->fecha, '/c'),
                      $factura->serie,
                      $factura->folio,
                      $factura->descripcion.$txt_cancelado,
                      MyString::formatoNumero($factura->cantidad, 2, '', false),
                      MyString::formatoNumero($factura->total, 2, '', false),
                      $factura->factura,
                    );

              if($pdf->GetY()+10 >= $pdf->limiteY)
                $pdf->AddPage();
              $pdf->SetXY(6, $pdf->GetY()-1);
              $pdf->SetAligns($aligns);
              $pdf->SetWidths($widths);
              $pdf->Row($datos, false, false);
            }

            if($pdf->GetY()+10 >= $pdf->limiteY)
              $pdf->AddPage();
            $pdf->SetX(127);
            $pdf->SetFont('Arial','B',8);
            // $pdf->SetTextColor(255,255,255);
            $pdf->SetAligns(array('R', 'R'));
            $pdf->SetWidths(array(23, 23));
            $pdf->Row(array(
                MyString::formatoNumero($total_cantidad, 2, '', false),
                MyString::formatoNumero($total_total, 2, '', false)), false);

            // $total_saldo_cliente += $saldo_cliente;
          }
        }
        if($pdf->GetY()+10 >= $pdf->limiteY)
          $pdf->AddPage();
        $pdf->SetX(97);
        $pdf->SetAligns(array('R', 'R', 'R'));
        $pdf->SetWidths(array(30, 23, 23));
        $pdf->Row(array('TOTAL EMPRESA',
            MyString::formatoNumero($total_cantidad_g, 2, '', false),
            MyString::formatoNumero($total_total_g, 2, '', false)), false);
      }

      if($pdf->GetY()+10 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(97);
      $pdf->SetAligns(array('R', 'R', 'R'));
      $pdf->SetWidths(array(30, 23, 23));
      $pdf->Row(array('TOTAL GRAL',
          MyString::formatoNumero($total_cantidad_g2, 2, '', false),
          MyString::formatoNumero($total_total_g2, 2, '', false)), false);


      // $pdf->SetXY(66, $pdf->GetY()+4);
      // $pdf->Row(array('TOTAL SALDO DE CLIENTES', MyString::formatoNumero( $total_saldo_cliente , 2, '', false)), false);


      $pdf->Output('reporte_ventas.pdf', 'I');
    }
    public function getRNotasCredXls()
    {
      header('Content-type: application/vnd.ms-excel; charset=utf-8');
      header("Content-Disposition: attachment; filename=Notas_credito.xls");
      header("Pragma: no-cache");
      header("Expires: 0");

       $res = $this->getRNotasCredData();

      $con_mov = $this->input->get('dcon_mov')=='si'? false: true;

      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa(2);

      $titulo1 = $empresa['info']->nombre_fiscal;
      $titulo2 = 'Notas de credito';
      $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";


      $html = '<table>
        <tbody>
          <tr>
            <td colspan="7" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
          </tr>
          <tr>
            <td colspan="7" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
          </tr>
          <tr>
            <td colspan="7" style="text-align:center;">'.$titulo3.'</td>
          </tr>
          <tr>
            <td colspan="7"></td>
          </tr>';
        $html .= '<tr style="font-weight:bold">
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Fecha</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Serie</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio</td>
          <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Concepto</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Cantidad</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Total</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Factura</td>
        </tr>';

      $total_subtotal_g2 = 0;
      $total_impuesto_g2 = 0;
      $total_total_g2 = 0;
      $total_cantidad_g2 = 0;
      $total_saldo_g2 = 0;
      $total_saldo_cliente_g2 = 0;
      $auxEmpresa = '';
      $auxcliente = '';
      foreach ($res as $keye => $dempresa) {
        $total_subtotal_g = 0;
        $total_impuesto_g = 0;
        $total_total_g = 0;
        $total_cantidad_g = 0;
        $total_saldo_g = 0;
        $total_saldo_cliente_g = 0;

        $html .= '<tr style="font-weight:bold">
            <td colspan="7">'.$dempresa['empresa'].'</td>
          </tr>';

        foreach($dempresa['clientes'] as $key => $item) {
          if (count($item['facturas']) > 0 || $con_mov)
          {
            $total_total = 0;
            $total_cantidad = 0;

            $html .= '<tr style="font-weight:bold">
                <td colspan="1">CLIENTE:</td>
                <td colspan="6">'.$item['cuenta_cpi'].'</td>
              </tr>
              <tr style="font-weight:bold">
                <td colspan="1">NOMBRE:</td>
                <td colspan="6">'.$item['cliente'].'</td>
              </tr>';

            foreach ($item['facturas'] as $keyf => $factura)
            {
              $txt_cancelado = ' (Cancelada)';
              if ($factura->status != 'ca') {
                $total_cantidad += $factura->cantidad;
                $total_total += $factura->total;

                $total_cantidad_g += $factura->cantidad;
                $total_total_g += $factura->total;

                $total_cantidad_g2 += $factura->cantidad;
                $total_total_g2 += $factura->total;

                $txt_cancelado = '';
              }

              $html .= '<tr>
                  <td style="width:150px;border:1px solid #000;">'.MyString::fechaATexto($factura->fecha, '/c').'</td>
                  <td style="width:150px;border:1px solid #000;">'.$factura->serie.'</td>
                  <td style="width:150px;border:1px solid #000;">'.$factura->folio.'</td>
                  <td style="width:400px;border:1px solid #000;">'.$factura->descripcion.$txt_cancelado.'</td>
                  <td style="width:150px;border:1px solid #000;">'.$factura->cantidad.'</td>
                  <td style="width:150px;border:1px solid #000;">'.$factura->total.'</td>
                  <td style="width:150px;border:1px solid #000;">'.$factura->factura.'</td>
                </tr>';
            }
            $html .= '
                <tr style="font-weight:bold">
                  <td colspan="4"></td>
                  <td style="border:1px solid #000;">'.$total_cantidad.'</td>
                  <td style="border:1px solid #000;">'.$total_total.'</td>
                  <td></td>
                </tr>';
          }
        }

        $html .= '
            <tr style="font-weight:bold">
              <td colspan="4">TOTAL EMPRESA</td>
              <td style="border:1px solid #000;">'.$total_cantidad_g.'</td>
              <td style="border:1px solid #000;">'.$total_total_g.'</td>
              <td></td>
            </tr>';
      }

      $html .= '
          <tr style="font-weight:bold">
            <td colspan="4">TOTAL GRAL</td>
            <td style="border:1px solid #000;">'.$total_cantidad_g2.'</td>
            <td style="border:1px solid #000;">'.$total_total_g2.'</td>
            <td></td>
          </tr>
        </tbody>
      </table>';

      echo $html;
    }

    /**
     * Reporte de facturas y notas de credito
     * @return
     */
    public function getRFacturasNCData()
    {
      $sql = '';

      //Filtros para buscar
      $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
      $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
      $fecha = $_GET['ffecha1'] < $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

      $this->load->model('empresas_model');
      $client_default = $this->empresas_model->getDefaultEmpresa();
      $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
      $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
      if($this->input->get('did_empresa') != ''){
        $sql .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
      }

      $res = $this->db->query(
        "SELECT
          f.id_factura,
          fc.rfc,
          f.serie,
          f.folio,
          f.status,
          Date(f.fecha) AS fecha,
          COALESCE(f.total, 0) AS cargo,
          COALESCE(f.importe_iva, 0) AS iva,
          f.id_nc,
          f.id_abono_factura
        FROM
          facturacion AS f
          INNER JOIN facturacion_cliente AS fc ON fc.id_factura = f.id_factura
        WHERE f.status <> 'b' AND f.is_factura = 't'
          AND (Date(f.fecha) >= '{$_GET['ffecha1']}' AND Date(f.fecha) <= '{$_GET['ffecha2']}')
          {$sql}
        ORDER BY f.fecha ASC
        ");

      $response = $res->result();

      return $response;
    }
    /**
    * Reporte compras x cliente pdf
    */
    public function getRFacturasNCPdf(){
      $res = $this->getRFacturasNCData();

      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');

      if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;

      $pdf->titulo1 = $empresa['info']->nombre_fiscal;
      $pdf->titulo2 = MyString::mes( intval(substr($this->input->get('ffecha1'), 5, 2)) );
      $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
      // $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
      $pdf->AliasNbPages();
      // $pdf->AddPage();
      $pdf->SetFont('Arial','',8);

      $aligns = array('L', 'L', 'R', 'L', 'R', 'R', 'C');
      $widths = array(32, 15, 20, 25, 25, 25, 20);
      $header = array('RFC', 'Serie', 'Folio', 'Fecha', 'Operacion', 'IVA', 'Estado');

      $total_nc_cancel = $total_nc = $total_factura_cancel = $total_iva_cancel = $total_factura = $total_iva = 0;
      $total_pp_cancel = $total_pp = 0;
      foreach($res as $key => $factura){
        if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
          $pdf->AddPage();

          $pdf->SetFont('Arial','B',9);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true, false);
        }

        $pdf->SetFont('Arial','',8);

        if(is_numeric($factura->id_nc))
        {
          $factura->status=='ca'? $total_nc_cancel += $factura->cargo : $total_nc += $factura->cargo ;
        }elseif(is_numeric($factura->id_abono_factura))
        {
          $factura->status=='ca'? $total_pp_cancel += $factura->cargo : $total_pp += $factura->cargo ;
        }else
        {
          if ($factura->status=='ca')
          {
            $total_factura_cancel += $factura->cargo;
            $total_iva_cancel += $factura->iva;
          }else
          {
            $total_factura += $factura->cargo;
            $total_iva += $factura->iva;
          }
        }

        $datos = array($factura->rfc,
                $factura->serie,
                $factura->folio,
                MyString::fechaATexto($factura->fecha, '/c'),
                MyString::formatoNumero($factura->cargo, 2, '', false),
                MyString::formatoNumero( $factura->iva , 2, '', false),
                ($factura->status=='ca'? '0': '1'),
              );

        $pdf->SetXY(6, $pdf->GetY()-1);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false, false);

        // $pdf->SetTextColor(255,255,255);
      }

      $pdf->SetFont('Arial','B',8);
      $pdf->SetAligns(array('R', 'R'));
      $pdf->SetWidths(array(60, 40));
      if($pdf->GetY()+7 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(80);
      $pdf->Row(array('FACTURAS ADMIN', MyString::formatoNumero( $total_factura+$total_factura_cancel , 2, '', false) ), false);
      if($pdf->GetY()+7 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(80);
      $pdf->Row(array('IVA ADMIN', MyString::formatoNumero( $total_iva+$total_iva_cancel , 2, '', false) ), false);
      if($pdf->GetY()+7 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(80);
      $pdf->Row(array('FACT  CANCELADAS', MyString::formatoNumero( $total_factura_cancel , 2, '', false) ), false);
      if($pdf->GetY()+7 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(80);
      $pdf->Row(array('IVA CANCELADO', MyString::formatoNumero( $total_iva_cancel , 2, '', false) ), false);
      if($pdf->GetY()+7 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(80);
      $pdf->Row(array('FACT. CONTAB', MyString::formatoNumero( $total_factura-$total_iva , 2, '', false) ), false);
      if($pdf->GetY()+7 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(80);
      $pdf->Row(array('IVA TRASLADADO CONTAB', MyString::formatoNumero( $total_iva , 2, '', false) ), false);
      if($pdf->GetY()+7 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(80);
      $pdf->Row(array('NOTAS DE CREDITO', MyString::formatoNumero( $total_nc , 2, '', false) ), false);
      if($pdf->GetY()+7 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(80);
      $pdf->Row(array('NC CANCELADAS', MyString::formatoNumero( $total_nc_cancel , 2, '', false) ), false);
      if($pdf->GetY()+7 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(80);
      $pdf->Row(array('PAGO PARCIALIDADES', MyString::formatoNumero( $total_pp , 2, '', false) ), false);
      if($pdf->GetY()+7 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(80);
      $pdf->Row(array('PAGO PARCIA CANCELADAS', MyString::formatoNumero( $total_pp_cancel , 2, '', false) ), false);


      $pdf->Output('reporte_ventas.pdf', 'I');
    }
    public function getRFacturasNCXls(){
      $res = $this->getRFacturasNCData();

      header('Content-type: application/vnd.ms-excel; charset=utf-8');
      header("Content-Disposition: attachment; filename=reporte_ventas.xls");
      header("Pragma: no-cache");
      header("Expires: 0");

      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

      $titulo1 = $empresa['info']->nombre_fiscal;
      $titulo2 = MyString::mes( intval(substr($this->input->get('ffecha1'), 5, 2)) );
      $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";

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
            <td style="border:1px solid #000;background-color: #cccccc;">RFC</td>
            <td style="border:1px solid #000;background-color: #cccccc;">Serie</td>
            <td style="border:1px solid #000;background-color: #cccccc;">Folio</td>
            <td style="border:1px solid #000;background-color: #cccccc;">Fecha</td>
            <td style="border:1px solid #000;background-color: #cccccc;">Operacion</td>
            <td style="border:1px solid #000;background-color: #cccccc;">IVA</td>
            <td style="border:1px solid #000;background-color: #cccccc;">Estado</td>
          </tr>';
      $total_nc_cancel = $total_nc = $total_factura_cancel = $total_iva_cancel = $total_factura = $total_iva = 0;
      $total_pp_cancel = $total_pp = 0;
      foreach($res as $key => $factura)
      {
        if(is_numeric($factura->id_nc))
        {
          $factura->status=='ca'? $total_nc_cancel += $factura->cargo : $total_nc += $factura->cargo ;
        }elseif(is_numeric($factura->id_abono_factura))
        {
          $factura->status=='ca'? $total_pp_cancel += $factura->cargo : $total_pp += $factura->cargo ;
        }else
        {
          if ($factura->status=='ca')
          {
            $total_factura_cancel += $factura->cargo;
            $total_iva_cancel += $factura->iva;
          }else
          {
            $total_factura += $factura->cargo;
            $total_iva += $factura->iva;
          }
        }

        $html .= '<tr style="font-weight:bold">
            <td style="border:1px solid #000;background-color: #cccccc;">'.$factura->rfc.'</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.$factura->serie.'</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.$factura->folio.'</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.$factura->fecha.'</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.$factura->cargo.'</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.$factura->iva.'</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.($factura->status=='ca'? '0': '1').'</td>
          </tr>';
      }

      $html .= '
          <tr>
            <td colspan="7"></td>
          </tr>
          <tr style="font-weight:bold">
            <td colspan="4">FACTURAS ADMIN</td>
            <td colspan="3" style="border:1px solid #000;">'.($total_factura+$total_factura_cancel).'</td>
          </tr>
          <tr style="font-weight:bold">
            <td colspan="4">IVA ADMIN</td>
            <td colspan="3" style="border:1px solid #000;">'.($total_iva+$total_iva_cancel).'</td>
          </tr>
          <tr style="font-weight:bold">
            <td colspan="4">FACT  CANCELADAS</td>
            <td colspan="3" style="border:1px solid #000;">'.$total_factura_cancel.'</td>
          </tr>
          <tr style="font-weight:bold">
            <td colspan="4">IVA CANCELADO</td>
            <td colspan="3" style="border:1px solid #000;">'.$total_iva_cancel.'</td>
          </tr>
          <tr style="font-weight:bold">
            <td colspan="4">FACT. CONTAB</td>
            <td colspan="3" style="border:1px solid #000;">'.($total_factura-$total_iva).'</td>
          </tr>
          <tr style="font-weight:bold">
            <td colspan="4">IVA TRASLADADO CONTAB</td>
            <td colspan="3" style="border:1px solid #000;">'.$total_iva.'</td>
          </tr>
          <tr style="font-weight:bold">
            <td colspan="4">NOTAS DE CREDITO</td>
            <td colspan="3" style="border:1px solid #000;">'.$total_nc.'</td>
          </tr>
          <tr style="font-weight:bold">
            <td colspan="4">NC CANCELADAS</td>
            <td colspan="3" style="border:1px solid #000;">'.$total_nc_cancel.'</td>
          </tr>
          <tr style="font-weight:bold">
            <td colspan="4">PAGO PARCIALIDADES</td>
            <td colspan="3" style="border:1px solid #000;">'.$total_pp.'</td>
          </tr>
          <tr style="font-weight:bold">
            <td colspan="4">PAGO PARCIA CANCELADAS</td>
            <td colspan="3" style="border:1px solid #000;">'.$total_pp_cancel.'</td>
          </tr>
        </tbody>
      </table>';

      echo $html;
    }


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
        $pdf->titulo3 = "Del ".MyString::fechaAT($_GET['ffecha1'])." al ".MyString::fechaAT($_GET['ffecha2'])."";
      elseif (!empty($_GET['ffecha1']))
        $pdf->titulo3 = "Del ".MyString::fechaAT($_GET['ffecha1']);
      elseif (!empty($_GET['ffecha2']))
        $pdf->titulo3 = "Del ".MyString::fechaAT($_GET['ffecha2']);

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
        $datos = array(MyString::fechaAT($item->fecha), $item->serie, $item->folio, $item->nombre_fiscal, $item->empresa, $condicion_pago, $estado, MyString::formatoNumero($item->total));
        $total += floatval($item->total);

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false);
      }

      $pdf->SetX(6);
      $pdf->SetFont('Arial','B',8);
      $pdf->SetTextColor(255,255,255);
      $pdf->Row(array('', '', '', '', '', '', 'Total:', MyString::formatoNumero($total)), true);

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
        $pdf->titulo3 = "Del ".MyString::fechaAT($_GET['ffecha1'])." al ".MyString::fechaAT($_GET['ffecha2'])."";
      elseif (!empty($_GET['ffecha1']))
        $pdf->titulo3 = "Del ".MyString::fechaAT($_GET['ffecha1']);
      elseif (!empty($_GET['ffecha2']))
        $pdf->titulo3 = "Del ".MyString::fechaAT($_GET['ffecha2']);

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

        $datos = array($item->codigo, $item->producto, $item->total_cantidad, MyString::formatoNumero($item->total_importe));

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false);
      }

      $pdf->Output('Reporte_Ventas_Productos.pdf', 'I');
  }

  /**
   * Reporte compras x cliente
   *
   * @return
   */
  public function getRVencidasData()
  {
    $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] < $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    $_GET['dtipo_factura'] = isset($_GET['dtipo_factura'])? $_GET['dtipo_factura']: 'f';
    $tipo_factura = array('', '');
    if($this->input->get('dtipo_factura') != '')
      $tipo_factura = array(" AND f.is_factura='".$this->input->get('dtipo_factura')."'", " AND is_factura='".$this->input->get('dtipo_factura')."'");

    $sql_clientes = '';
    if(is_array($this->input->get('ids_clientes')))
      $sql_clientes = " AND c.id_cliente IN(".implode(',', $this->input->get('ids_clientes')).")";


    $res = $this->db->query(
      "SELECT
        c.id_cliente,
        c.nombre_fiscal,
        Sum(COALESCE(f.total, 0)) AS cargo,
        Sum(COALESCE(f.importe_iva, 0)) AS iva,
        Sum(COALESCE(ac.abono, 0)) AS abono,
        Sum((COALESCE(f.total, 0) - COALESCE(ac.abono, 0))::numeric(100,2)) AS saldo
      FROM
        facturacion AS f
        INNER JOIN clientes AS c ON c.id_cliente = f.id_cliente
        LEFT JOIN (
          SELECT id_factura, Sum(abono) AS abono
          FROM (
            (
              SELECT
                id_factura,
                Sum(total) AS abono
              FROM
                facturacion_abonos as fa
              WHERE Date(fecha) <= '{$_GET['ffecha2']}'
              GROUP BY id_factura
            )
            UNION
            (
              SELECT
                id_nc AS id_factura,
                Sum(total) AS abono
              FROM
                facturacion
              WHERE status <> 'ca' AND status <> 'b' AND id_nc IS NOT NULL
                AND Date(fecha) <= '{$_GET['ffecha2']}'
              GROUP BY id_nc
            )
          ) AS ffs
          GROUP BY id_factura
        ) AS ac ON f.id_factura = ac.id_factura
      WHERE f.status <> 'ca' AND f.status <> 'b' AND id_nc IS NULL
        AND (Date(f.fecha) >= '{$_GET['ffecha1']}' AND Date(f.fecha) <= '{$_GET['ffecha2']}')
        AND (Date('{$fecha}'::timestamp with time zone)-Date(f.fecha)) > f.plazo_credito
        {$sql}{$tipo_factura[0]}{$sql_clientes}
      GROUP BY c.id_cliente, c.nombre_fiscal
      ORDER BY c.nombre_fiscal ASC
      ");
    $response = $res->result();
    foreach ($response as $key => $value)
    {
      $res_anterio = $this->db->query(
      "SELECT
        c.id_cliente,
        c.nombre_fiscal,
        Sum(COALESCE(f.total, 0)) AS cargo,
        Sum(COALESCE(f.importe_iva, 0)) AS iva,
        Sum(COALESCE(ac.abono, 0)) AS abono,
        Sum((COALESCE(f.total, 0) - COALESCE(ac.abono, 0))::numeric(100,2)) AS saldo
      FROM
        facturacion AS f
        INNER JOIN clientes AS c ON c.id_cliente = f.id_cliente
        LEFT JOIN (
          SELECT id_factura, Sum(abono) AS abono
          FROM (
            (
              SELECT
                id_factura,
                Sum(total) AS abono
              FROM
                facturacion_abonos as fa
              WHERE Date(fecha) <= '{$_GET['ffecha2']}'
              GROUP BY id_factura
            )
            UNION
            (
              SELECT
                id_nc AS id_factura,
                Sum(total) AS abono
              FROM
                facturacion
              WHERE status <> 'ca' AND status <> 'b' AND id_nc IS NOT NULL
                AND Date(fecha) <= '{$_GET['ffecha2']}'
              GROUP BY id_nc
            )
          ) AS ffs
          GROUP BY id_factura
        ) AS ac ON f.id_factura = ac.id_factura
      WHERE c.id_cliente = {$value->id_cliente} AND f.status <> 'ca' AND f.status <> 'b' AND id_nc IS NULL
        AND Date(f.fecha) < '{$_GET['ffecha1']}'
        AND (Date('{$fecha}'::timestamp with time zone)-Date(f.fecha)) > f.plazo_credito
        {$sql}{$tipo_factura[0]}{$sql_clientes}
      GROUP BY c.id_cliente, c.nombre_fiscal
      ");
      $value->saldo_anterior = $res_anterio->num_rows()>0? $res_anterio->row()->saldo: 0;
      $res_anterio->free_result();
    }

    return $response;
  }
  /**
  * Reporte compras x cliente pdf
  */
  public function getRVencidasPdf(){
    $res = $this->getRVencidasData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;
    else
      $pdf->logo = '';

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = ($_GET['dtipo_factura']=='f'? 'REMISIONES': 'FACTURAS').' VENCIDAS';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    // $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
    $pdf->AliasNbPages();
    // $pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $con_saldo = isset($_GET['con_saldo']{0})? true: false;

    $aligns = array('L', 'R', 'R', 'R');
    $widths = array(100, 30, 30, 30);
    $header = array('Cliente', 'S. Anterior', 'S. En fechas', 'S. Total');

    $total_total = 0;
    $total_saldo = 0;
    foreach($res as $key => $factura){
      if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',9);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true, false);
      }
      $saldoo = MyString::float( MyString::formatoNumero($factura->saldo_anterior+$factura->saldo, 2, '', false) );

      if ($con_saldo==false || $saldoo > 0)
      {
        $pdf->SetFont('Arial','',8);

        $total_saldo    += $factura->saldo_anterior;
        $total_total    += $factura->saldo;

        $datos = array($factura->nombre_fiscal,
                MyString::formatoNumero($factura->saldo_anterior, 2, '', false),
                MyString::formatoNumero($factura->saldo, 2, '', false),
                MyString::formatoNumero($factura->saldo_anterior+$factura->saldo, 2, '', false),
              );

        $pdf->SetXY(6, $pdf->GetY()-1);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false, false);

        // $pdf->SetTextColor(255,255,255);
      }
    }

    $pdf->SetX(106);
    $pdf->SetFont('Arial','B',8);
    $pdf->SetAligns(array('R', 'R', 'R'));
    $pdf->SetWidths(array(30, 30, 30));
    $pdf->Row(array(
        MyString::formatoNumero($total_saldo, 2, '', false),
        MyString::formatoNumero($total_total, 2, '', false),
        MyString::formatoNumero($total_total+$total_saldo, 2, '', false),
        ), false);

    $pdf->Output('reporte_ventas.pdf', 'I');
  }
  public function getRVencidasXls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=reporte_ventas.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getRVencidasData();

    $con_saldo = isset($_GET['con_saldo']{0})? true: false;

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = ($_GET['dtipo_factura']=='f'? 'REMISIONES': 'FACTURAS').' VENCIDAS';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";


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
        </tr>';

    $html .= '<tr style="font-weight:bold">
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Cliente</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">S. Anterior</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">S. En fechas</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">S. Total</td>
      </tr>';
    $total_total = 0;
    $total_saldo = 0;
    foreach($res as $key => $factura){
      $saldoo = MyString::float( MyString::formatoNumero($factura->saldo_anterior+$factura->saldo, 2, '', false) );

      if ($con_saldo==false || $saldoo > 0)
      {
        $total_saldo += $factura->saldo_anterior;
        $total_total += $factura->saldo;

        $html .= '<tr>
            <td style="width:400px;border:1px solid #000;">'.$factura->nombre_fiscal.'</td>
            <td style="width:150px;border:1px solid #000;">'.$factura->saldo_anterior.'</td>
            <td style="width:150px;border:1px solid #000;">'.$factura->saldo.'</td>
            <td style="width:150px;border:1px solid #000;">'.($factura->saldo_anterior+$factura->saldo).'</td>
          </tr>';
      }
    }

    $html .= '
        <tr style="font-weight:bold">
          <td></td>
          <td style="border:1px solid #000;">'.$total_saldo.'</td>
          <td style="border:1px solid #000;">'.$total_total.'</td>
          <td style="border:1px solid #000;">'.($total_total+$total_saldo).'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }
}