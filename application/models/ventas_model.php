<?php
class Ventas_model extends privilegios_model{

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
                f.status_timbrado, f.uuid, f.docs_finalizados, f.observaciones, f.refacturada
        FROM facturacion AS f
        INNER JOIN empresas AS e ON e.id_empresa = f.id_empresa
        INNER JOIN clientes AS c ON c.id_cliente = f.id_cliente
        WHERE 1 = 1 AND f.is_factura = 'f' AND f.status != 'b' ".$sql.$sql2."
        ORDER BY  f.folio DESC, f.fecha DESC, f.serie DESC
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
	public function getInfoVenta($id, $info_basic=false)
  {
		$res = $this->db
            ->select("*")
            ->from('facturacion')
            ->where("id_factura = {$id}")
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
                u.id_unidad, fp.kilos, fp.cajas, fp.id_unidad_rendimiento, fp.certificado, fp.id_size_rendimiento')
        ->from('facturacion_productos as fp')
        ->join('clasificaciones as cl', 'cl.id_clasificacion = fp.id_clasificacion', 'left')
        ->join('unidades as u', 'u.nombre = fp.unidad', 'left')
        ->where('id_factura = ' . $id)->order_by('fp.num_row', 'asc')
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
          $response['seguro'] = $tipo;
        }
        else
        { // Certificados 51 o 52
          $response['certificado'.$tipo->id_clasificacion] = $tipo;
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
      'cadena_original'      => '',
      'sello'                => '',
      'certificado'          => '',
      'condicion_pago'      => $this->input->post('dcondicion_pago'),
      'plazo_credito'       => $_POST['dcondicion_pago'] === 'co' ? 0 : $this->input->post('dplazo_credito'),
      'observaciones'       => $this->input->post('dobservaciones'),
      'status'              => 'p', //$_POST['dcondicion_pago'] === 'co' ? 'pa' : 'p',
      'status_timbrado'     => 'p',
      'sin_costo'           => isset($_POST['dsincosto']) ? 't' : 'f',
      'is_factura'          => 'f'
    );
    //Si existe el parametro es una nota de credito de la factura
    $bitacora_accion = 'la nota de remision';
    if(isset($_POST['id_nrc']{0}))
    {
      $datosFactura['id_nc'] = $_POST['id_nrc'];
      $datosFactura['status'] = 'pa';
      $bitacora_accion = 'la nota de credito';
    }

    $this->db->insert('facturacion', $datosFactura);
    $id_venta = $this->db->insert_id();

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
    $serieFolio = $datosFactura['serie'].$datosFactura['folio'];

    // Productos
    $productosFactura   = array();
    foreach ($_POST['prod_ddescripcion'] as $key => $descripcion)
    {
      if ($_POST['prod_dcantidad'][$key] > 0)
      {
        $productosFactura[] = array(
          'id_factura'       => $id_venta,
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
          'kilos'             => $_POST['prod_dkilos'][$key],
          'cajas'             => $_POST['prod_dcajas'][$key],
          'id_unidad_rendimiento' => $_POST['id_unidad_rendimiento'][$key] !== '' ? $_POST['id_unidad_rendimiento'][$key] : null,
          'id_size_rendimiento'   => isset($_POST['id_size_rendimiento'][$key]) && $_POST['id_size_rendimiento'][$key] !== '' ? $_POST['id_size_rendimiento'][$key] : null,
          'certificado' => $_POST['isCert'][$key] === '1' ? 't' : 'f',
        );

        if ($_POST['prod_did_prod'][$key] === '49')
        {
          $dataSeguroCerti[] = array(
            'id_factura'       => $id_venta,
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
            'id_factura'       => $id_venta,
            'id_clasificacion' => $_POST['prod_did_prod'][$key],
            'id_proveedor'     => $_POST['cert_id_proveedor'.$_POST['prod_did_prod'][$key]],
            'certificado'      => $_POST['cert_certificado'.$_POST['prod_did_prod'][$key]],
            'folio'            => $serieFolio,
            'bultos'           => $_POST['cert_bultos'.$_POST['prod_did_prod'][$key]],
            'pol_seg'          => null,
          );
        }
      }
    }
    if(count($productosFactura) > 0)
      $this->db->insert_batch('facturacion_productos', $productosFactura);

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
    $pathDocs = $this->documentos_model->creaDirectorioDocsCliente($dataCliente['nombre'], $datosFactura['serie'], $datosFactura['folio']);

    // Inserta los documentos del cliente con un status false.
    if ($docsCliente)
      $this->db->insert_batch('facturacion_documentos', $docsCliente);
    else
      $datosFactura['docs_finalizados'] = 't';

    $this->generaNotaRemisionPdf($id_venta, $pathDocs);

		return array('passes' => true, 'id_venta' => $id_venta);
	}

  public function updateNotaVenta($id_venta)
  {
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
      'cadena_original'      => '',
      'sello'                => '',
      'certificado'          => '',
      'condicion_pago'      => $this->input->post('dcondicion_pago'),
      'plazo_credito'       => $_POST['dcondicion_pago'] === 'co' ? 0 : $this->input->post('dplazo_credito'),
      'observaciones'       => $this->input->post('dobservaciones'),
      'status'              => 'p', //$_POST['dcondicion_pago'] === 'co' ? 'pa' : 'p',
      'status_timbrado'     => 'p',
      'sin_costo'           => isset($_POST['dsincosto']) ? 't' : 'f',
      'is_factura'          => 'f'
    );

    // Bitacora
    $id_bitacora = $this->bitacora_model->_update('facturacion', $id_venta, $datosFactura,
                              array(':accion'       => 'la nota de remision', ':seccion' => 'nota de remision',
                                    ':folio'        => $datosFactura['serie'].$datosFactura['folio'],
                                    ':id_empresa'   => $datosFactura['id_empresa'],
                                    ':empresa'      => 'de '.$this->input->post('dempresa'),
                                    ':id'           => 'id_factura',
                                    ':titulo'       => 'Venta'));
    $this->db->update('facturacion', $datosFactura, "id_factura = {$id_venta}");
    // $id_venta = $this->db->insert_id();

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
    foreach ($_POST['prod_ddescripcion'] as $key => $descripcion)
    {
      if ($_POST['prod_dcantidad'][$key] > 0)
      {
        $productosFactura[] = array(
          'id_factura'       => $id_venta,
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
          'kilos'             => $_POST['prod_dkilos'][$key],
          'cajas'             => $_POST['prod_dcajas'][$key],
          'id_unidad_rendimiento' => $_POST['id_unidad_rendimiento'][$key] !== '' ? $_POST['id_unidad_rendimiento'][$key] : null,
          'id_size_rendimiento'   => isset($_POST['id_size_rendimiento'][$key]) && $_POST['id_size_rendimiento'][$key] !== '' ? $_POST['id_size_rendimiento'][$key] : null,
          'certificado' => $_POST['isCert'][$key] === '1' ? 't' : 'f',
        );

        if ($_POST['prod_did_prod'][$key] === '49')
        {
          $dataSeguroCerti[] = array(
            'id_factura'       => $id_venta,
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
            'id_factura'       => $id_venta,
            'id_clasificacion' => $_POST['prod_did_prod'][$key],
            'id_proveedor'     => $_POST['cert_id_proveedor'],
            'certificado'      => $_POST['cert_certificado'],
            'folio'            => $serieFolio,
            'bultos'           => $_POST['cert_bultos'],
            'pol_seg'          => null,
          );
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
    $pathDocs = $this->documentos_model->creaDirectorioDocsCliente($dataCliente['nombre'], $datosFactura['serie'], $datosFactura['folio']);

    // // Inserta los documentos del cliente con un status false.
    // if ($docsCliente)
    //   $this->db->insert_batch('facturacion_documentos', $docsCliente);
    // else
    //   $datosFactura['docs_finalizados'] = 't';

    $this->generaNotaRemisionPdf($id_venta, $pathDocs);

    return array('passes' => true, 'id_venta' => $id_venta);
  }

	/**
	 * Cancela una nota, la elimina
	 */
	public function cancelaNotaRemison($id_venta){
    $this->load->model('documentos_model');

    $this->db->update('facturacion', array('status' => 'ca'), "id_factura = '{$id_venta}'");
    $remision = $this->getInfoVenta($id_venta);

    // Regenera el PDF de la factura.
    $pathDocs = $this->documentos_model->creaDirectorioDocsCliente($remision['info']->cliente->nombre_fiscal, $remision['info']->serie, $remision['info']->folio);
    $this->generaNotaRemisionPdf($id_venta, $pathDocs);

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
 //        String::formatoNumero($item->precio_unitario, 3),
 //        String::formatoNumero(floatval($item->importe) + floatval($item->iva), 3),
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
 //    // $pdf->Cell(30, 6, String::formatoNumero($venta['info']->subtotal), 1, 0, 'C', 1);

 //    $pdf->SetXY(156, $pdf->GetY() - 23);
 //    $pdf->Cell(30, 6, "TOTAL", 1, 0, 'C', 1);

 //    $pdf->SetXY(186, $pdf->GetY());
 //    $pdf->Cell(30, 6,String::formatoNumero($venta['info']->total, 2), 1, 0, 'C', 1);

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

  public function generaNotaRemisionPdf($idVenta, $path = null)
  {
    // include(APPPATH.'libraries/phpqrcode/qrlib.php');

    $factura = $this->getInfoVenta($idVenta);

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
    $pdf->Cell(50, 4, ($factura['info']->id_nc==''? 'Venta de Remisión': 'Nota de Credito'), 0, 0, 'L', 1);
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
      if($factura['info']->sin_costo == 't')
      {
        if ($item->id_clasificacion == '49' || $item->id_clasificacion == '50' ||
            $item->id_clasificacion == '51' || $item->id_clasificacion == '52' ||
            $item->id_clasificacion == '53')
          $printRow = false;
      }

      if ($item->certificado === 't')
        $hay_prod_certificados = true;

      if($printRow)
      {
        if ($item->porcentaje_iva == '11')
          $traslado11 += $item->iva;
        elseif ($item->porcentaje_iva == '16')
          $traslado16 += $item->iva;

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
      $pdf->Output($path.'Venta_Remision.pdf', 'F');
    else
      $pdf->Output('Venta_Remision', 'I');
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
          (COALESCE(f.total, 0) - COALESCE(ac.abono, 0))::numeric(100,2) AS saldo
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
      $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
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

        $total_saldo += ($factura->status=='ca'?0:$factura->saldo);
        $total_total += ($factura->status=='ca'?0:$factura->cargo);

        $datos = array(String::fechaATexto($factura->fecha, '/c'),
                $factura->serie,
                $factura->folio,
                $factura->nombre_fiscal.($factura->status=='ca'?' (Cancelada)':''),
                String::formatoNumero($factura->status=='ca'? 0:$factura->cargo, 2, '', false),
                String::formatoNumero( ($factura->status=='ca'?0:$factura->saldo) , 2, '', false),
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
          String::formatoNumero($total_total, 2, '', false),
          String::formatoNumero($total_saldo, 2, '', false)), false);


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
          f.id_nc
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
      $pdf->titulo2 = String::mes( intval(substr($this->input->get('ffecha1'), 5, 2)) );
      $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
      // $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
      $pdf->AliasNbPages();
      // $pdf->AddPage();
      $pdf->SetFont('Arial','',8);

      $aligns = array('L', 'L', 'R', 'L', 'R', 'R', 'C');
      $widths = array(32, 15, 20, 25, 25, 25, 20);
      $header = array('RFC', 'Serie', 'Folio', 'Fecha', 'Operacion', 'IVA', 'Estado');

      $total_nc_cancel = $total_nc = $total_factura_cancel = $total_iva_cancel = $total_factura = $total_iva = 0;
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
                String::fechaATexto($factura->fecha, '/c'),
                String::formatoNumero($factura->cargo, 2, '', false),
                String::formatoNumero( $factura->iva , 2, '', false),
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
      if($pdf->GetY()+8 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(80);
      $pdf->Row(array('FACTURAS ADMIN', String::formatoNumero( $total_factura+$total_factura_cancel , 2, '', false) ), false);
      if($pdf->GetY()+8 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(80);
      $pdf->Row(array('IVA ADMIN', String::formatoNumero( $total_iva+$total_iva_cancel , 2, '', false) ), false);
      if($pdf->GetY()+8 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(80);
      $pdf->Row(array('FACT  CANCELADAS', String::formatoNumero( $total_factura_cancel , 2, '', false) ), false);
      if($pdf->GetY()+8 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(80);
      $pdf->Row(array('IVA CANCELADO', String::formatoNumero( $total_iva_cancel , 2, '', false) ), false);
      if($pdf->GetY()+8 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(80);
      $pdf->Row(array('FACT. CONTAB', String::formatoNumero( $total_factura-$total_iva , 2, '', false) ), false);
      if($pdf->GetY()+8 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(80);
      $pdf->Row(array('IVA TRASLADADO CONTAB', String::formatoNumero( $total_iva , 2, '', false) ), false);
      if($pdf->GetY()+8 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(80);
      $pdf->Row(array('NOTAS DE CREDITO', String::formatoNumero( $total_nc , 2, '', false) ), false);
      if($pdf->GetY()+8 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(80);
      $pdf->Row(array('NC CANCELADAS', String::formatoNumero( $total_nc_cancel , 2, '', false) ), false);


      $pdf->Output('reporte_ventas.pdf', 'I');
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
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
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
      $saldoo = String::float( String::formatoNumero($factura->saldo_anterior+$factura->saldo, 2, '', false) );

      if ($con_saldo==false || $saldoo > 0)
      {
        $pdf->SetFont('Arial','',8);

        $total_saldo    += $factura->saldo_anterior;
        $total_total    += $factura->saldo;

        $datos = array($factura->nombre_fiscal,
                String::formatoNumero($factura->saldo_anterior, 2, '', false),
                String::formatoNumero($factura->saldo, 2, '', false),
                String::formatoNumero($factura->saldo_anterior+$factura->saldo, 2, '', false),
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
        String::formatoNumero($total_saldo, 2, '', false),
        String::formatoNumero($total_total, 2, '', false),
        String::formatoNumero($total_total+$total_saldo, 2, '', false),
        ), false);

    $pdf->Output('reporte_ventas.pdf', 'I');
  }
}