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
                f.status_timbrado, f.uuid, f.docs_finalizados, f.observaciones, f.refacturada, f.total,
                f.tipo_comprobante
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
      ->select("f.*, fo.no_trazabilidad, fo.id_paleta_salida")
      ->from('facturacion as f')
      ->join('facturacion_otrosdatos as fo', 'f.id_factura = fo.id_factura', 'left')
      ->where("f.id_factura = {$idFactura}")
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

      $this->load->library('cfdi');
      if ($response['info']->empresa->cer_org !== '')
        $response['info']->no_certificado = $this->cfdi->obtenNoCertificado($response['info']->empresa->cer_org);

      // Carga la info del cliente.
			$this->load->model('clientes_model');
			$prov = $this->clientes_model->getClienteInfo($response['info']->id_cliente);
			$response['info']->cliente = $prov['info'];

      $res = $this->db
        ->select("fp.id_factura, fp.id_clasificacion, fp.num_row, fp.cantidad, fp.descripcion, fp.precio_unitario,
                fp.importe, fp.iva, fp.unidad, fp.retencion_iva, cl.cuenta_cpi, cl.cuenta_cpi2, fp.porcentaje_iva, fp.porcentaje_retencion, fp.ids_pallets,
                u.id_unidad, fp.kilos, fp.cajas, fp.id_unidad_rendimiento, fp.ids_remisiones, fp.clase, fp.peso, fp.certificado, fp.id_size_rendimiento,
                ac.nombre AS areas_calidad, ac.id_calidad, at.nombre AS areas_tamanio, at.id_tamanio, fp.descripcion2, fp.no_identificacion,
                cl.clave_prod_serv, fp.cfdi_ext->'clave_unidad'->>'key' AS clave_unidad, fp.cfdi_ext, fp.ieps, fp.porcentaje_ieps,
                cal.nombre AS areas_calibre, cal.id_calibre, fp.porcentaje_iva_real", false)
        ->from('facturacion_productos as fp')
        ->join('clasificaciones as cl', 'cl.id_clasificacion = fp.id_clasificacion', 'left')
        ->join('unidades_unq as u', "u.nombre = fp.unidad", 'left')
        ->join('otros.areas_calidades as ac', 'ac.id_calidad = fp.id_calidad', 'left')
        ->join('otros.areas_tamanios as at', 'at.id_tamanio = fp.id_tamanio', 'left')
        ->join('calibres as cal', 'cal.id_calibre = fp.id_calibres', 'left')
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

      $res->free_result();
      $res = $this->db->query("SELECT fsc.*, p.nombre_fiscal as proveedor
         FROM facturacion_seg_cert fsc
         INNER JOIN proveedores p ON p.id_proveedor = fsc.id_proveedor
         WHERE id_factura = {$idFactura}");

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

      // Comercio exterior
      $comercioe = $this->db->query(
        "SELECT id, id_factura, version, tipo_operacion, clave_pedimento, certificado_origen, num_certificado_origen,
          numero_exportador_confiable, incoterm, subdivision, observaciones, \"tipocambio_USD\", \"total_USD\", emisor_curp,
          receptor_numregidtrib, receptor_curp, motivo_traslado, extras
         FROM facturacion_ce
         WHERE id_factura = {$idFactura}");
      if ($comercioe->num_rows() > 0) {
        $response['ce'] = $comercioe->row();

        $response['ce']->destinatario = $this->db->query(
          "SELECT numregidtrib, rfc, curp, nombre, calle, numero_exterior, numero_interior, colonia,
            localidad, referencia, municipio, estado, pais, codigo_postal
           FROM facturacion_ce_destinatario
           WHERE comercio_exterior_id = {$response['ce']->id}")->row();

        $response['ce']->mercancias = $this->db->query(
          "SELECT row, noidentificacion, fraccionar_ancelaria, cantidad_aduana, unidad_aduana, valor_unitario_aduana, valor_dolares
           FROM facturacion_ce_mercancias
           WHERE comercio_exterior_id = {$response['ce']->id}")->result();

        foreach ($response['ce']->mercancias as $key => $mercancia) {
          $response['ce']->mercancias[$key]->esp = $this->db->query(
            "SELECT row, row2, marca, modelo, submodelo, numeroserie
             FROM facturacion_ce_mercancias_esp
             WHERE comercio_exterior_id = {$response['ce']->id} AND row = {$mercancia->row}")->result();
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
          ->select('id_serie_folio, id_empresa, serie, leyenda, default_serie')
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
          'pais'              => $cliente['info']->pais,
          'codigoPostal'      => $cliente['info']->cp,

          'concepto'          => array(),

          'retencion'         => array(),
          'totalImpuestosRetenidos' => 0,

          'traslado'          => array(),
          'totalImpuestosTrasladados' => 0
        );

        if (!empty($this->input->post('comercioExterior')['clave_pedimento']) ||
            !empty($this->input->post('comercioExterior')['numero_exportador_confiable']) ||
            !empty($this->input->post('comercioExterior')['numero_exportador_confiable']) ||
            !empty($this->input->post('comercioExterior')['incoterm']) ) {
          $data['comercioExterior'] = $this->input->post('comercioExterior');
        }

        return $data;
    }

    public function addPallestRemisiones($idFactura, $borrador, $ligarRemisiones=false)
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


      if (isset($_POST['remisionesIds']) && count($_POST['remisionesIds']) > 0 && $ligarRemisiones) {
        foreach ($_POST['remisionesIds'] as $kerr => $remmm) {
          // Agrega al historial de remisiones
          $existe = $this->db->query("SELECT Count(*) AS num FROM facturacion_remision_hist
                                     WHERE id_remision = {$remmm} AND id_factura = {$idFactura}")->row();
          if ($existe->num == 0) {
            $this->db->insert('facturacion_remision_hist', array('id_remision' => $remmm, 'id_factura' => $idFactura));
          }

          // Productos de produccion
          $productos = $this->db->query("SELECT * FROM otros.produccion_historial
                                     WHERE id_factura = {$remmm}")->result();
          if (count($productos) > 0) {
            foreach ($productos as $kpp => $prod) {
              unset($prod->id);
              $prod->id_factura = $idFactura;
              $this->db->insert('otros.produccion_historial', $prod);
            }
          }
          $this->db->update('otros.produccion_historial', array('status' => 'f'), "id_factura = '{$remmm}'");
        }

        $this->db->query("SELECT refreshallmaterializedviews();");
      }


      return array('passes' => true, 'msg' => 'Se ligaron las remisiones correctamente');
    }

    public function removePallestRemisiones($id_remision, $id_factura)
    {
      $this->db->delete('facturacion_ventas_remision_pivot', ['id_factura' => $id_factura, 'id_venta' => $id_remision]);
      $this->db->delete('facturacion_remision_hist', ['id_factura' => $id_factura, 'id_remision' => $id_remision]);

      return array('passes' => true, 'msg' => 'Se quito la remisión correctamente');
    }

    public function addComercioExterior($idFactura, $borrador)
    {
      if ((isset($_GET['idb']) && ! $borrador) || $borrador)
      {
        $this->db->delete('facturacion_ce', array('id_factura' => $idFactura));
      }

      $inputce = $this->input->post('comercioExterior');

      $extras = [
        'version'                   => '1.1',
        'motivoTraslado'            => isset($inputce['motivoTraslado'])? $inputce['motivoTraslado'] : '',
        'tipoOperacion'             => isset($inputce['tipoOperacion'])? $inputce['tipoOperacion'] : '',
        'clavePedimento'            => isset($inputce['clavePedimento'])? $inputce['clavePedimento'] : '',
        'certificadoOrigen'         => isset($inputce['certificadoOrigen'])? $inputce['certificadoOrigen'] : '',
        'numCertificadoOrigen'      => isset($inputce['numCertificadoOrigen'])? $inputce['numCertificadoOrigen'] : '',
        'numeroExportadorConfiable' => isset($inputce['numeroExportadorConfiable'])? $inputce['numeroExportadorConfiable'] : '',
        'incoterm'                  => isset($inputce['incoterm'])? $inputce['incoterm'] : '',
        'subdivision'               => isset($inputce['subdivision'])? $inputce['subdivision'] : '',
        'observaciones'             => isset($inputce['observaciones'])? $inputce['observaciones'] : '',
        'tipoCambioUSD'             => isset($inputce['tipoCambioUSD'])? floatval($inputce['tipoCambioUSD']) : 0,
        'totalUSD'                  => isset($inputce['totalUSD'])? floatval($inputce['totalUSD']) : 0,

        'emisor'                    => $inputce['emisor'],
        'receptor'                  => $inputce['receptor'],
        'propietario'               => [$inputce['propietario']],
        'destinatario'              => [$inputce['destinatario']],
        'mercancias'                => [],
      ];
      $ce = array(
        'id_factura'                  => $idFactura,
        'version'                     => $extras['version'],
        'motivo_traslado'             => $extras['motivoTraslado'],
        'tipo_operacion'              => $extras['tipoOperacion'],
        'clave_pedimento'             => $extras['clavePedimento'],
        'certificado_origen'          => $extras['certificadoOrigen'],
        'num_certificado_origen'      => $extras['numCertificadoOrigen'],
        'numero_exportador_confiable' => $extras['numeroExportadorConfiable'],
        'incoterm'                    => $extras['incoterm'],
        'subdivision'                 => $extras['subdivision'],
        'observaciones'               => $extras['observaciones'],
        'tipocambio_USD'              => $extras['tipoCambioUSD'],
        'total_USD'                   => $extras['totalUSD'],
        'emisor_curp'                 => '',
        'receptor_curp'               => '',
        'receptor_numregidtrib'       => '',
        'extras'                      => json_encode($extras),
        'created_at'                  => date("Y-m-d H:i:s"),
        'updated_at'                  => date("Y-m-d H:i:s")
        );
      $this->db->insert('facturacion_ce', $ce);
      $idce = $this->db->insert_id('facturacion_ce_id_seq');

      $ce = array(
        'comercio_exterior_id' => $idce,
        'numregidtrib'         => isset($inputce['destinatario']['numRegIdTrib']) ? $inputce['destinatario']['numRegIdTrib'] : '',
        'rfc'                  => '',
        'curp'                 => '',
        'nombre'               => isset($inputce['destinatario']['nombre']) ? $inputce['destinatario']['nombre'] : '',
        'calle'                => isset($inputce['destinatario']['domicilio']['calle']) ? $inputce['destinatario']['domicilio']['calle'] : '',
        'numero_exterior'      => isset($inputce['destinatario']['domicilio']['numeroExterior']) ? $inputce['destinatario']['domicilio']['numeroExterior'] : '',
        'numero_interior'      => isset($inputce['destinatario']['domicilio']['numeroInterior']) ? $inputce['destinatario']['domicilio']['numeroInterior'] : '',
        'colonia'              => isset($inputce['destinatario']['domicilio']['colonia']) ? $inputce['destinatario']['domicilio']['colonia'] : '',
        'localidad'            => isset($inputce['destinatario']['domicilio']['localidad']) ? $inputce['destinatario']['domicilio']['localidad'] : '',
        'referencia'           => isset($inputce['destinatario']['domicilio']['referencia']) ? $inputce['destinatario']['domicilio']['referencia'] : '',
        'municipio'            => isset($inputce['destinatario']['domicilio']['municipio']) ? $inputce['destinatario']['domicilio']['municipio'] : '',
        'estado'               => isset($inputce['destinatario']['domicilio']['estado']) ? $inputce['destinatario']['domicilio']['estado'] : '',
        'pais'                 => isset($inputce['destinatario']['domicilio']['pais']) ? $inputce['destinatario']['domicilio']['pais'] : '',
        'codigo_postal'        => isset($inputce['destinatario']['domicilio']['codigoPostal']) ? $inputce['destinatario']['domicilio']['codigoPostal'] : '',
        'created_at'           => date("Y-m-d H:i:s"),
        'updated_at'           => date("Y-m-d H:i:s")
        );
      $this->db->insert('facturacion_ce_destinatario', $ce);

      if (isset($inputce['mercancias'])) {
        $count = 0;
        foreach ($inputce['mercancias']['noIdentificacion'] as $key => $value) {
          $count2 = 0;
          $extras['mercancias'][$key] = [
            'noIdentificacion'    => $inputce['mercancias']['noIdentificacion'][$key],
            'fraccionArancelaria' => $inputce['mercancias']['fraccionArancelaria'][$key].'',
            'cantidadAduana'      => $inputce['mercancias']['cantidadAduana'][$key],
            'unidadAduana'        => $inputce['mercancias']['unidadAduana'][$key],
            'valorUnitarioAduana' => $inputce['mercancias']['valorUnitarioAduana'][$key],
            'valorDolares'        => $inputce['mercancias']['valorDolares'][$key],
          ];
          $mercancia = array(
            'comercio_exterior_id'  => $idce,
            'row'                   => $count,
            'noidentificacion'      => $inputce['mercancias']['noIdentificacion'][$key],
            'fraccionar_ancelaria'  => $inputce['mercancias']['fraccionArancelaria'][$key].'',
            'cantidad_aduana'       => $inputce['mercancias']['cantidadAduana'][$key],
            'unidad_aduana'         => $inputce['mercancias']['unidadAduana'][$key],
            'valor_unitario_aduana' => $inputce['mercancias']['valorUnitarioAduana'][$key],
            'valor_dolares'         => $inputce['mercancias']['valorDolares'][$key],
            'created_at'           => date("Y-m-d H:i:s"),
            'updated_at'           => date("Y-m-d H:i:s")
          );
          $this->db->insert('facturacion_ce_mercancias', $mercancia);


          if ( isset($inputce['mercancias']['descripcionesEspecificas'][$key]) && is_array($inputce['mercancias']['descripcionesEspecificas'][$key])) {
            foreach ($inputce['mercancias']['descripcionesEspecificas'][$key]['marca'] as $key2 => $value2) {
              $extras['mercancias'][$key]['descripcionesEspecificas'][] = [
                'marca'       => $inputce['mercancias']['descripcionesEspecificas'][$key]['marca'][$key2],
                'modelo'      => $inputce['mercancias']['descripcionesEspecificas'][$key]['modelo'][$key2],
                'subModelo'   => $inputce['mercancias']['descripcionesEspecificas'][$key]['subModelo'][$key2],
                'numeroSerie' => $inputce['mercancias']['descripcionesEspecificas'][$key]['numeroSerie'][$key2],
              ];
              $mercancia_esp = array(
                'comercio_exterior_id' => $idce,
                'row'                  => $count,
                'row2'                 => $count2,
                'marca'                => $inputce['mercancias']['descripcionesEspecificas'][$key]['marca'][$key2],
                'modelo'               => $inputce['mercancias']['descripcionesEspecificas'][$key]['modelo'][$key2],
                'submodelo'            => $inputce['mercancias']['descripcionesEspecificas'][$key]['subModelo'][$key2],
                'numeroserie'          => $inputce['mercancias']['descripcionesEspecificas'][$key]['numeroSerie'][$key2],
                'created_at'           => date("Y-m-d H:i:s"),
                'updated_at'           => date("Y-m-d H:i:s")
              );
              $this->db->insert('facturacion_ce_mercancias_esp', $mercancia_esp);
              ++$count2;
            }
          }
          ++$count;
        }
      }
      return $extras;
    }

  /**
	 * Agrega una Factura.
   *
   * @return  array
	 */
  public function addFactura($borrador = false, $facturaId=null)
  {
    $this->load->library('cfdi');
    $this->load->model('clientes_model');
    $this->load->model('clasificaciones_model');

    $anoAprobacion = explode('-', $_POST['dano_aprobacion']);

    // Obtiene la forma de pago, si es en parcialidades entonces la forma de
    // pago son las parcialidades "Parcialidad 1 de X".
    // $formaPago = ($_POST['dforma_pago'] == 'Pago en parcialidades') ? $this->input->post('dforma_pago_parcialidad') : 'Pago en una sola exhibición';

    $tipoDeComprobante = 'I';
    if ($this->input->post('dtipo_comprobante')=='egreso')
      $tipoDeComprobante = 'E';
    elseif ($this->input->post('dserie')=='T'){
      $_POST['dtipo_comprobante'] = 'traslado';
      $tipoDeComprobante = 'T';
    }

    $cfdi_ext = [
      'tipoDeComprobante' => $tipoDeComprobante,
      'usoCfdi'           => $this->input->post('duso_cfdi'),
    ];

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
      'forma_pago'          => $this->input->post('dforma_pago'),
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
      'ieps'                => floatval($this->input->post('total_ieps')),
      'sin_costo'           => isset($_POST['dsincosto']) ? 't' : 'f',
      'moneda'              => $_POST['moneda'],
      'cfdi_ext'            => json_encode($cfdi_ext),
    );

    // Si la factura es = 0 se pone pagada
    if ($datosFactura['total'] == 0) {
      $datosFactura['status'] = 'pa';
    }

    // indica q es una factura en parcialidades de un abono
    if (isset($_POST['id_abono_factura'])) {
      $datosFactura['id_abono_factura'] = $_POST['id_abono_factura'];
    }

    // Tipo de cambio y moneda
    if ($datosFactura['moneda'] !== 'MXN')
      $datosFactura['tipo_cambio'] = $_POST['tipoCambio'];
    else {
      $_POST['tipoCambio'] = '1';
      $datosFactura['tipo_cambio'] = '1';
    }

    // Si el tipo de comprobante es "egreso" o una nota de credito.
    $bitacora_accion = 'la factura';
    $cid_nc = false;
    if ($_POST['dtipo_comprobante'] === 'egreso') {
      $datosFactura['id_nc'] = $_GET['id'];
      $cid_nc = $datosFactura['id_nc'];
      $bitacora_accion = 'la nota de credito';
    }

    if ($facturaId > 0) { // Para traslados
      $cid_nc = $facturaId;
    }

    // Inserta los datos de la factura y obtiene el Id. Este en caso
    // de que se este timbrando una factura que no sea un borrador.
    if (( ! isset($_GET['idb']) && ! $borrador) || $borrador)
    {
      $this->db->insert('facturacion', $datosFactura);
      $idFactura = $this->db->insert_id('facturacion_id_factura_seq');

      // Si tiene el # de trazabilidad
      if ($this->input->post('dno_trazabilidad') !== false) {
        $this->db->insert('facturacion_otrosdatos', [
          'id_factura'      => $idFactura,
          'no_trazabilidad' => $this->input->post('dno_trazabilidad')
        ]);
      }

      // Bitacora
      $this->bitacora_model->_insert('facturacion', $idFactura,
                                      array(':accion'    => $bitacora_accion, ':seccion' => 'facturas',
                                            ':folio'     => $datosFactura['serie'].$datosFactura['folio'],
                                            ':id_empresa' => $datosFactura['id_empresa'],
                                            ':empresa'   => 'en '.$this->input->post('dempresa')));
    }

    // Si es un borrador que se esta timbrando entonces actualiza sus datos.
    else
    {
      $idFactura = $_GET['idb'];
      $this->db->update('facturacion', $datosFactura, array('id_factura' => $idFactura));

      // Si tiene el # de trazabilidad
      if ($this->input->post('dno_trazabilidad') !== false) {
        $this->db->update('facturacion_otrosdatos', [
          'id_factura'      => $idFactura,
          'no_trazabilidad' => $this->input->post('dno_trazabilidad')
        ], "id_factura = {$idFactura}");
      }
    }

    // Productos e Impuestos
    $productosCadOri    = array(); // Productos para la CadOriginal
    $productosFactura   = array(); // Productos para la Factura
    $productosApi       = array();
    $produccionFactura  = array(); // inventario de produccion

    $impuestosTraslados = array(); // Traslados
    $traslado0  = false; // Total de traslado 0%
    $traslado11 = 0; // Total de traslado 11%
    $traslado16 = 0; // Total de traslado 16%

    $dataSeguroCerti = array();
    $nrow_seg_cer = 0;
    $seg_cer_entro = array();
    $serieFolio = $datosFactura['serie'].$datosFactura['folio'];

    // Ciclo para obtener los impuestos traslados, tambien construye
    // los datos de  los productos a insertar tanto en la cadena original como
    // en la factura.
    foreach ($_POST['prod_ddescripcion'] as $key => $descripcion)
    {
      if ($_POST['prod_importe'][$key] > 0 || ($tipoDeComprobante == 'T' && $_POST['prod_importe'][$key] == 0))
      {
        $descripcioncad = strlen($_POST['prod_ddescripcion2'][$key])>0? " ({$_POST['prod_ddescripcion2'][$key]})": '';
        $descripcioncad .= ((isset($_POST['prod_dclase'][$key]) && $_POST['prod_dclase'][$key] !== '') ? ' Clase '.$_POST['prod_dclase'][$key] : '') . ((isset($_POST['prod_dpeso'][$key]) && $_POST['prod_dpeso'][$key] !== '0' && $_POST['prod_dpeso'][$key] !== '') ? ' Peso '.$_POST['prod_dpeso'][$key] : '');
        $productosCadOri[] = array(
          'cantidad'         => $_POST['prod_dcantidad'][$key],
          'unidad'           => $_POST['prod_dmedida'][$key],
          'noIdentificacion' => $_POST['no_identificacion'][$key],
          'descripcion'      => $descripcion.$descripcioncad,
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
            if ( !GastosProductos::searchGastosProductos($_POST['prod_did_prod'][$key]) )
              $traslado16 += floatval($_POST['prod_diva_total'][$key]);
          }else
            $traslado16 += floatval($_POST['prod_diva_total'][$key]);
        }
        else
          $traslado0 = true;

        $did_unidad = (isset($_POST['prod_dmedida_id'][$key])? $_POST['prod_dmedida_id'][$key]: NULL);
        $dunidad_c = NULL;
        if ($did_unidad > 0) { // obtenemos la cantidad de la unidad
          $data_unidad = $this->db->query("SELECT cantidad FROM unidades WHERE id_unidad = {$did_unidad}")->row();
          $dunidad_c = $data_unidad->cantidad>0? $data_unidad->cantidad: NULL;
        }

        // Para descontar del inventario de productos de produccion
        if (isset($_POST['prod_did_prod'][$key]{0})) {
          $clasificacion = $this->clasificaciones_model->getClasificacionInfo($_POST['prod_did_prod'][$key], true, false);
          if ($clasificacion['info']->inventario == 't' && $_POST['prod_did_prod'][$key] !== '' && $tipoDeComprobante == 'I') {
            $produccionFactura[] = array(
              'id_factura'       => $idFactura,
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

        $cfdi_extpp = [
          'clave_unidad' => [
            'key'   => $_POST['pclave_unidad_cod'][$key],
            'value' => $_POST['pclave_unidad'][$key],
          ]
        ];

        $productosFactura[] = array(
          'id_factura'            => $idFactura,
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
          'ieps'                  => (isset($_POST['dieps_total'][$key])? $_POST['dieps_total'][$key]: 0),
          'porcentaje_ieps'       => (isset($_POST['dieps'][$key])? $_POST['dieps'][$key]: 0),
          'ids_pallets'           => isset($_POST['pallets_id'][$key]) && $_POST['pallets_id'][$key] !== '' ? $_POST['pallets_id'][$key] : null,
          'ids_remisiones'        => isset($_POST['remisiones_id'][$key]) && $_POST['remisiones_id'][$key] !== '' ? $_POST['remisiones_id'][$key] : null,
          'kilos'                 => isset($_POST['prod_dkilos'][$key]) ? $_POST['prod_dkilos'][$key] : 0,
          'cajas'                 => isset($_POST['prod_dcajas'][$key]) ? $_POST['prod_dcajas'][$key] : 0,
          'id_unidad_rendimiento' => isset($_POST['id_unidad_rendimiento'][$key]) && $_POST['id_unidad_rendimiento'][$key] !== '' ? $_POST['id_unidad_rendimiento'][$key] : null,
          'id_size_rendimiento'   => isset($_POST['id_size_rendimiento'][$key]) && $_POST['id_size_rendimiento'][$key] !== '' ? $_POST['id_size_rendimiento'][$key] : null,
          'clase'                 => isset($_POST['prod_dclase'][$key]) ? $_POST['prod_dclase'][$key] : '',
          'peso'                  => isset($_POST['prod_dpeso'][$key]) && $_POST['prod_dpeso'][$key] !== '' ? $_POST['prod_dpeso'][$key] : 0,
          'certificado'           => (isset($_POST['isCert'][$key])? ($_POST['isCert'][$key]=== '1' ? 't' : 'f'): 'f'),
          'id_unidad'             => $did_unidad,
          'unidad_c'              => $dunidad_c,
          'id_calidad'            => ($_POST['prod_did_calidad'][$key] !== ''? $_POST['prod_did_calidad'][$key]: NULL),
          'id_tamanio'            => ($_POST['prod_did_tamanio'][$key] !== ''? $_POST['prod_did_tamanio'][$key]: NULL),
          'id_calibres'           => ($_POST['prod_did_tamanio_prod'][$key] !== ''? $_POST['prod_did_tamanio_prod'][$key]: NULL),
          'descripcion2'          => $_POST['prod_ddescripcion2'][$key],
          'no_identificacion'     => $_POST['no_identificacion'][$key],
          'cfdi_ext'              => json_encode($cfdi_extpp),
        );

        $addProdApi = true;
        if($datosFactura['sin_costo'] == 't')
        {
          if ( GastosProductos::searchGastosProductos($_POST['prod_did_prod'][$key]) )
            $addProdApi = false;
        }
        if ($addProdApi) {
          $productosApi[] = array(
            'claveProdServ'           => isset($clasificacion['info'])? $clasificacion['info']->clave_prod_serv : '',
            'claveUnidad'             => $_POST['pclave_unidad_cod'][$key],
            'unidad'                  => $_POST['prod_dmedida'][$key],
            'cantidad'                => ($_POST['prod_dcantidad'][$key]>0? $_POST['prod_dcantidad'][$key]: 1),
            'concepto'                => $descripcion,
            'cuentaPredial'           => '',
            'descuentoProd'           => '0',
            'descuentoProdPorcent'    => '0',
            'importe'                 => $_POST['prod_importe'][$key],
            'noIdentificacion'        => $_POST['no_identificacion'][$key],
            'retencionCedular'        => '0',
            'retencionCedularPorcent' => '0',
            'retencionIsr'            => '0',
            'retencionIsrPorcent'     => '0',
            'retencionIva'            => $_POST['prod_dreten_iva_total'][$key],
            'retencionIvaPorcent'     => ($_POST['prod_dreten_iva_porcent'][$key]*100),
            'retencionIvc'            => '0',
            'retencionIvcPorcent'     => '0',
            'trasladoCedular'         => '0',
            'trasladoCedularPorcent'  => '0',
            'trasladoIeps'            => floatval((isset($_POST['dieps_total'][$key])? $_POST['dieps_total'][$key]: 0)),
            'trasladoIepsPorcent'     => floatval((isset($_POST['dieps'][$key])? $_POST['dieps'][$key]: 0)),
            'trasladoIsh'             => '0',
            'trasladoIshPorcent'      => '0',
            'trasladoIva'             => $_POST['prod_diva_total'][$key],
            'trasladoIvaPorcent'      => ($tipoDeComprobante !== 'T'? $_POST['prod_diva_porcent'][$key]: '16'),
            'valorUnitario'           => $_POST['prod_dpreciou'][$key],
          );
        }

        if ($_POST['prod_did_prod'][$key] === '49' && !isset($seg_cer_entro['49']))
        {
          foreach ($_POST['seg_id_proveedor'] as $keysecer => $data_secer) {
            $dataSeguroCerti[] = array(
              'id_factura'       => $idFactura,
              'id_clasificacion' => $_POST['prod_did_prod'][$key],
              'nrow'             => $nrow_seg_cer,
              'id_proveedor'     => $_POST['seg_id_proveedor'][$keysecer],
              'pol_seg'          => $_POST['seg_poliza'][$keysecer],
              'folio'            => $serieFolio,
              'bultos'           => 0,
              'certificado'      => null,
              'num_operacion'    => null,
            );
            ++$nrow_seg_cer;
          }
          $seg_cer_entro[$_POST['prod_did_prod'][$key]] = true;
        }

        if (($_POST['prod_did_prod'][$key] === '51' && !isset($seg_cer_entro['51'])) || ($_POST['prod_did_prod'][$key] === '52' && !isset($seg_cer_entro['52'])))
        {
          foreach ($_POST['cert_id_proveedor'.$_POST['prod_did_prod'][$key]] as $keysecer => $data_secer) {
            $dataSeguroCerti[] = array(
              'id_factura'       => $idFactura,
              'id_clasificacion' => $_POST['prod_did_prod'][$key],
              'nrow'             => $nrow_seg_cer,
              'id_proveedor'     => $_POST['cert_id_proveedor'.$_POST['prod_did_prod'][$key]][$keysecer],
              'certificado'      => $_POST['cert_certificado'.$_POST['prod_did_prod'][$key]][$keysecer],
              'folio'            => $serieFolio,
              'bultos'           => $_POST['cert_bultos'.$_POST['prod_did_prod'][$key]][$keysecer],
              'pol_seg'          => null,
              'num_operacion'    => $_POST['cert_num_operacion'.$_POST['prod_did_prod'][$key]][$keysecer],
            );
            ++$nrow_seg_cer;
          }
          $seg_cer_entro[$_POST['prod_did_prod'][$key]] = true;
        }

        if ($_POST['prod_did_prod'][$key] === '53' && !isset($seg_cer_entro['53']))
        {
          foreach ($_POST['seg_id_proveedor'] as $keysecer => $data_secer) {
            $dataSeguroCerti[] = array(
              'id_factura'       => $idFactura,
              'id_clasificacion' => $_POST['prod_did_prod'][$key],
              'nrow'             => $nrow_seg_cer,
              'id_proveedor'     => $_POST['supcarga_id_proveedor'][$keysecer],
              'certificado'      => $_POST['supcarga_numero'][$keysecer],
              'folio'            => $serieFolio,
              'bultos'           => $_POST['supcarga_bultos'][$keysecer],
              'pol_seg'          => null,
              'num_operacion'    => $_POST['supcarga_num_operacion'][$keysecer],
            );
            ++$nrow_seg_cer;
          }
          $seg_cer_entro[$_POST['prod_did_prod'][$key]] = true;
        }
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

    if(count($produccionFactura) > 0) {
      // Cancela los productos de produccion historial
      // if (isset($_POST['id_nr']) && $_POST['id_nr'] > 0) {
      //   $this->db->update('otros.produccion_historial', array('status' => 'f'), "id_factura = '{$_POST['id_nr']}'");
      if (isset($_POST['remisionesIds']) && count($_POST['remisionesIds']) > 0) {
        foreach ($_POST['remisionesIds'] as $kerr => $remmm) {
          $this->db->update('otros.produccion_historial', array('status' => 'f'), "id_factura = '{$remmm}'");
        }
      }

      if ((isset($_GET['idb']) && ! $borrador) || $borrador)
      {
        $this->db->delete('otros.produccion_historial', array('id_factura' => $idFactura));
      }

      $this->db->insert_batch('otros.produccion_historial', $produccionFactura);
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

    if (count($dataSeguroCerti) > 0)
    {
      $this->db->delete('facturacion_seg_cert', array('id_factura' => $idFactura));
      $this->db->insert_batch('facturacion_seg_cert', $dataSeguroCerti);
    }

    // Agrega al historial de remisiones
    if (isset($_POST['remisionesIds']) && count($_POST['remisionesIds']) > 0) {
      foreach ($_POST['remisionesIds'] as $kerr => $remmm) {
        $this->db->insert('facturacion_remision_hist', array('id_remision' => $remmm, 'id_factura' => $idFactura));
      }
    }
    // if (isset($_POST['id_nr']) && $_POST['id_nr'] > 0) {
    //   $this->db->insert('facturacion_remision_hist', array('id_remision' => $_POST['id_nr'], 'id_factura' => $idFactura));
    // }

    if (!empty($this->input->post('comercioExterior')['tipoOperacion']) ||
        !empty($this->input->post('comercioExterior')['clavePedimento']) ||
        !empty($this->input->post('comercioExterior')['certificadoOrigen']) ) {
      $_POST['comercioExteriorPros'] = $this->addComercioExterior($idFactura, $borrador);
    }


    // Si es un borrador
    if ($borrador) return true;


    // xml 3.3
    $datosApi = $this->cfdi->obtenDatosCfdi33($_POST, $productosApi, $cid_nc);
    // echo "<pre>";
    //   var_dump($datosApi);
    // echo "</pre>";exit;

    // Obtiene los datos para la cadena original
    // $datosCadOrig = $this->datosCadenaOriginal();
    // $datosCadOrig['sinCosto']   =  isset($_POST['dsincosto']) ? true : false;

    // Si es un ingreso o una factura.
    if ($_POST['dtipo_comprobante'] === 'ingreso')
    {
      // Obtiene los documentos que el cliente tiene asignados.
      $docsCliente = $this->getClienteDocs($datosFactura['id_cliente'], $idFactura);

      $this->load->model('documentos_model');
      $pathDocs = $this->documentos_model->creaDirectorioDocsCliente($datosApi['receptor']['nombreFiscal'], $datosFactura['serie'], $datosFactura['folio'], substr($datosFactura['fecha'], 0, 10));

      // Inserta los documentos del cliente con un status false.
      if ($docsCliente)
        $this->db->insert_batch('facturacion_documentos', $docsCliente);
      else
        $datosFactura['docs_finalizados'] = 't';
    }
    else
    {
        $this->load->model('documentos_model');
        $pathDocs = $this->documentos_model->creaDirectorioDocsCliente($datosApi['receptor']['nombreFiscal'], $datosFactura['serie'], $datosFactura['folio'], substr($datosFactura['fecha'], 0, 10));
        $datosFactura['docs_finalizados'] = 't';
    }

    $dataCliente = array(
      'id_factura'  => $idFactura,
      'nombre'      => $datosApi['receptor']['nombreFiscal'],
      'rfc'         => $datosApi['receptor']['rfc'],
      'calle'       => $datosApi['receptor']['calle'],
      'no_exterior' => $datosApi['receptor']['noExterior'],
      'no_interior' => $datosApi['receptor']['noInterior'],
      'colonia'     => $datosApi['receptor']['colonia'],
      'localidad'   => $datosApi['receptor']['localidad'],
      'municipio'   => $datosApi['receptor']['municipio'],
      'estado'      => $datosApi['receptor']['estado'],
      'cp'          => $datosApi['receptor']['cp'],
      'pais'        => $datosApi['receptor']['pais'],
    );
    // Inserta los datos del cliente.
    $this->db->insert('facturacion_cliente', $dataCliente);

    // echo "<pre>";
    //   var_dump($datosApi);
    // echo "</pre>";exit;

    // Timbrado de la factura.
    log_message('error', "Timbre");
    log_message('error', json_encode($datosApi));
    $result = $this->timbrar($datosApi, $idFactura);
    log_message('error', json_encode($result));

    if ($result['passes'])
    {

      // $xmlName = explode('/', $archivos['pathXML']);

      // copy($archivos['pathXML'], $pathDocs.end($xmlName));

      //Si es otra moneda actualiza al tipo de cambio
      if($datosFactura['moneda'] !== 'MXN')
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

      $this->db->query("SELECT refreshallmaterializedviews();");

      $this->generaFacturaPdf($idFactura, $pathDocs);

      // si probiene de una venta se asigna
      if (isset($_GET['id_vd'])) {
        $this->load->model('ventas_dia_model');
        $this->ventas_dia_model->idFacturaVenta(array('id_factura' => $idFactura, 'id_venta' => $_GET['id_vd']));
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
  private function timbrar($dataXml, $idFactura, $delFiles = true)
  {
    $this->load->library('facturartebarato_api');

    // $this->facturartebarato_api->setPathXML($pathXML);

    // Realiza el timbrado usando la libreria.
    $timbrado = $this->facturartebarato_api->timbrar($dataXml);

    // echo "<pre>";
    //   var_dump($timbrado);
    // echo "</pre>";exit;
    if (isset($timbrado->codigo)) {
      $result = array(
        'id_factura' => $idFactura,
        'codigo'     => $timbrado->codigo
      );
    } else {
      $msg = isset($timbrado->msg)? $timbrado->msg: 'Error Timbrado: Internet Desconectado. Verifique su conexión para realizar el timbrado.';
      $result = array(
        'id_factura' => $idFactura,
        'codigo'     => 'ERR_INTERNET_DISCONNECTED',
        'msg'        => $msg,
        'passes'     => false,
      );

      // Si es true $delFile entonces elimina todo lo relacionado con la factura.
      if ($delFiles)
      {
        $this->db->delete('facturacion_cliente', array('id_factura' => $idFactura));
        $this->db->delete('facturacion', array('id_factura' => $idFactura));
        // unlink($pathXML);
      }

      return $result;
    }

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
        'xml'             => $timbrado->data->xml,
        'status_timbrado' => $statusTimbrado,
        'uuid'            => $timbrado->data->uuid,
        'cadena_original' => $timbrado->data->cadenaOriginal,
        'sello'           => $timbrado->data->sello,
        'certificado'     => $dataXml['emisor']['cer'],
        'cfdi_ext'        => json_encode($dataXml),
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
        // unlink($pathXML);
      }

      // Entra si hubo un algun tipo de error de conexion a internet.
      if ($timbrado->codigo === 'ERR_INTERNET_DISCONNECTED')
        $result['msg'] = 'Error Timbrado: Internet Desconectado. Verifique su conexión para realizar el timbrado.';
      elseif ($timbrado->codigo === '500')
        $result['msg'] = 'Error en el servidor del timbrado. Pongase en contacto con el equipo de desarrollo del sistema.';
      else
        $result['msg'] = $timbrado->mensaje;

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

    if ($factura['info']->uuid != '')
    {
      $status_uuid = '708';
      // Carga los datos fiscales de la empresa dentro de la lib CFDI.
      $this->cfdi->cargaDatosFiscales($factura['info']->id_empresa);

      // Parametros que necesita el webservice para la cancelacion.
      $params = array(
        'rfc'    => $factura['info']->empresa->rfc,
        'rfcRec' => $factura['info']->cliente->rfc,
        'uuids'  => $factura['info']->uuid,
        'cer'    => $this->cfdi->obtenCer(),
        'key'    => $this->cfdi->obtenKey(),
        'total'  => $factura['info']->total,
        'sello'  => $factura['info']->sello,
      );

      // Lama el metodo cancelar para que realiza la peticion al webservice.
      $result = $this->facturartebarato_api->cancelar($params);

      if ($result->data->status_uuid == '201' || $result->data->status_uuid == '202')
      {
        $status_uuid = $result->data->status_uuid;

        $this->cancelaFacturaClear($factura, $idFactura);

        // Regenera el PDF de la factura.
        $pathDocs = $this->documentos_model->creaDirectorioDocsCliente($factura['info']->cliente->nombre_fiscal, $factura['info']->serie, $factura['info']->folio, substr($factura['info']->fecha, 0, 10));
        $this->generaFacturaPdf($idFactura, $pathDocs);

        $this->enviarEmail($idFactura);

      }
    }else{
      $this->cancelaFacturaClear($factura, $idFactura);
      // $this->db->update('facturacion',
      //     array('status' => 'ca', 'status_timbrado' => 'ca'),
      //     "id_factura = {$idFactura}"
      //   );
      $status_uuid = '201';
    }

    return array('msg' => $status_uuid);
	}

  private function cancelaFacturaClear($factura, $idFactura)
  {
    $this->db->update('facturacion',
      array('status' => 'ca', 'status_timbrado' => 'ca'),
      "id_factura = {$idFactura}"
    );

    // Quita la asignacion de la factura a la venta del dia
    $this->load->model('ventas_dia_model');
    $this->ventas_dia_model->idFacturaVenta(array('id_factura' => $idFactura), true);

    // Bitacora
    $bitacora_accion = 'la factura';
    if($factura['info']->id_nc > 0)
      $bitacora_accion = 'la nota de credito';
    $this->bitacora_model->_cancel('facturacion', $idFactura,
                                    array(':accion'     => $bitacora_accion, ':seccion' => 'facturas',
                                          ':folio'      => $factura['info']->serie.$factura['info']->folio,
                                          ':id_empresa' => $factura['info']->id_empresa,
                                          ':empresa'    => 'de '.$factura['info']->empresa->nombre_fiscal));

    // Elimina la salida de productos q se dio si se ligaron pallets
    $this->db->delete('compras_salidas', array('id_factura' => $idFactura));

    // Cancela los productos de produccion historial
    $this->db->update('otros.produccion_historial', array('status' => 'f'), "id_factura = '{$idFactura}'");

    $this->db->query("SELECT refreshallmaterializedviews();");
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
        $mes   = strtoupper(MyString::mes(floatval($fecha[1])));
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

        $cliente = MyString::quitAcentos(strtoupper($factura['info']->cliente->nombre_fiscal));
        $fecha   = explode('-', $factura['info']->fecha);
        $ano     = $fecha[0];
        $mes     = strtoupper(MyString::mes(floatval($fecha[1])));
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

    public function descargarMasiva($id_empresa, $fecha1, $fecha2)
    {
      $fecha1 = substr($fecha1, 0, 10);
      $fecha2 = substr($fecha2, 0, 10);

      $res = $this->db->query("SELECT f.serie, f.folio, Date(f.fecha) AS fecha, c.nombre_fiscal
         FROM facturacion as f
          INNER JOIN clientes as c ON f.id_cliente = c.id_cliente
         WHERE f.id_empresa = {$id_empresa} AND
           Date(f.fecha) >= '{$fecha1}' AND Date(f.fecha) <= '{$fecha2}' AND
           id_nc IS NULL AND id_abono_factura IS NULL AND
           is_factura = 't'
         ")->result();

      $num_files = 0;
      if ($res && count($res) > 0) {
        $zip = new ZipArchive;
        if ($zip->open(APPPATH.'media/facturas_masivas.zip', ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === true)
        {
          foreach ($res as $key => $value) {
            $cliente = MyString::quitAcentos(strtoupper($value->nombre_fiscal));
            $fecha   = explode('-', $value->fecha);
            $ano     = $fecha[0];
            $mes     = strtoupper(MyString::mes(floatval($fecha[1])));
            $serie   = $value->serie !== '' ? $value->serie.'-' : '';
            $folio   = $value->folio;

            $pathDocs = APPPATH."documentos/CLIENTES/{$cliente}/{$ano}/{$mes}/FACT-{$serie}{$folio}/";

            if (file_exists($pathDocs)) {
              // Scanea el directorio para obtener los archivos.
              $archivos = array_diff(scandir($pathDocs), array('..', '.'));

              foreach ($archivos as $archivo){
                $zip->addFile($pathDocs.$archivo, $archivo);
                ++$num_files;
              }
            }
          }
          $zip->close();
        } else {
          exit('Error al intentar crear el ZIP.');
        }
      }

      if ($num_files > 0) {
        header('Content-Type: application/zip');
        header('Content-disposition: attachment; filename=facturas_masivas.zip');
        readfile(APPPATH.'media/facturas_masivas.zip');
        unlink(APPPATH.'media/facturas_masivas.zip');
      } else {
        echo "No hay archivos";
      }

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
            $altBody = $body = '';
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

            $correoEmisorEm = "facturacion@empaquesanjorge.com"; // Correo con el q se emitira el correo.
            $nombreEmisor   = $factura['info']->empresa->nombre_fiscal;
            $correoEmisor   = 'SMTP_Injection'; // "postmaster@empaquesanjorge.com"; // Correo para el auth. empaquesanjorgemx@gmail.com (mandrill)
            $contrasena     = "a35b970bfd921b8dd4300e51ade387c19a5e60db"; // "2b9f25bc4737f34edada0b29a56ff682"; // Contraseña de $correEmisor S4nj0rg3V14n3y

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
                    $mes     = strtoupper(MyString::mes(floatval($fecha[1])));
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
        "SELECT rd.id_documento, rd.nombre
         FROM clientes_documentos cd INNER JOIN rastria_documentos rd ON rd.id_documento = cd.id_documento
         WHERE cd.id_cliente = {$idCliente}
         ORDER BY rd.id_documento ASC"
      );

      if ($query->num_rows() > 0)
      {
        $docs = array();
        foreach ($query->result()  as $objDoc)
        {
          if (is_null($idFactura))
            $docs[] = $objDoc->id_documento;
          else {
            $status = 'f';
            if ($objDoc->nombre == "REMISION Y/O FACTURA")
              $status = 't';
            $docs[] = array('id_factura' => $idFactura, 'id_documento' => $objDoc->id_documento, 'status' => $status);
          }
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
      // $formaPago = ($_POST['dforma_pago'] == 'Pago en parcialidades') ? $this->input->post('dforma_pago_parcialidad') : 'Pago en una sola exhibición';

      $cfdi_ext = [
        'tipoDeComprobante' => ($this->input->post('dtipo_comprobante')=='ingreso'? 'I': 'E'),
        'usoCfdi'           => $this->input->post('duso_cfdi'),
      ];

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
        'forma_pago'          => $this->input->post('dforma_pago'),
        'metodo_pago'         => $this->input->post('dmetodo_pago'),
        'metodo_pago_digitos' => ($_POST['dmetodo_pago'] === 'efectivo') ? 'No identificado' : ($_POST['dmetodo_pago_digitos'] !== '' ? $_POST['dmetodo_pago_digitos']  : 'No identificado'),
        'no_certificado'      => $this->input->post('dno_certificado'),
        'condicion_pago'      => $this->input->post('dcondicion_pago'),
        'plazo_credito'       => $_POST['dcondicion_pago'] === 'co' ? 0 : $this->input->post('dplazo_credito'),
        'observaciones'       => $this->input->post('dobservaciones'),
        'status'              => isset($_POST['timbrar']) ? 'p' : 'b',
        'retencion_iva'       => $this->input->post('total_retiva'),
        'ieps'                => floatval($this->input->post('total_ieps')),
        'cfdi_ext'            => json_encode($cfdi_ext),
      );

      // Si el tipo de comprobante es "egreso" o una nota de credito.
      if ($_POST['dtipo_comprobante'] === 'egreso')
        $datosFactura['id_nc'] = $_GET['id'];

      // Inserta los datos de la factura y obtiene el Id.
      $this->db->update('facturacion', $datosFactura, array('id_factura' => $idBorrador));

      // Si tiene el # de trazabilidad
      if ($this->input->post('dno_trazabilidad') !== false) {
        $this->db->update('facturacion_otrosdatos', [
          'id_factura'      => $idBorrador,
          'no_trazabilidad' => $this->input->post('dno_trazabilidad')
        ], "id_factura = {$idBorrador}");
      }

      // Productos e Impuestos
      $productosFactura   = array(); // Productos para la Factura
      $pallets = array(); // Ids de los pallets cargados en la factura.
      $lastPalletId = 0;

      $dataSeguroCerti = array();
      $nrow_seg_cer = 0;
      $seg_cer_entro = array();
      $serieFolio = $datosFactura['serie'].$datosFactura['folio'];

      foreach ($_POST['prod_ddescripcion'] as $key => $descripcion)
      {
        if ($_POST['prod_importe'][$key] != 0)
        {
          $did_unidad = (isset($_POST['prod_dmedida_id'][$key])? $_POST['prod_dmedida_id'][$key]: NULL);
          $dunidad_c = NULL;
          if ($did_unidad > 0) { // obtenemos la cantidad de la unidad
            $data_unidad = $this->db->query("SELECT cantidad FROM unidades WHERE id_unidad = {$did_unidad}")->row();
            $dunidad_c = $data_unidad->cantidad>0? $data_unidad->cantidad: NULL;
          }

          $cfdi_extpp = [
            'clave_unidad' => [
              'key'   => $_POST['pclave_unidad_cod'][$key],
              'value' => $_POST['pclave_unidad'][$key],
            ]
          ];

          $productosFactura[] = array(
            'id_factura'            => $idBorrador,
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
            'ids_pallets'           => $_POST['pallets_id'][$key] !== '' ? $_POST['pallets_id'][$key] : null,
            'ids_remisiones'        => $_POST['remisiones_id'][$key] !== '' ? $_POST['remisiones_id'][$key] : null,
            'kilos'                 => $_POST['prod_dkilos'][$key],
            'cajas'                 => $_POST['prod_dcajas'][$key],
            'id_unidad_rendimiento' => $_POST['id_unidad_rendimiento'][$key] !== '' ? $_POST['id_unidad_rendimiento'][$key] : null,
            'id_size_rendimiento'   => isset($_POST['id_size_rendimiento'][$key]) && $_POST['id_size_rendimiento'][$key] !== '' ? $_POST['id_size_rendimiento'][$key] : null,
            'clase'                 => isset($_POST['prod_dclase'][$key]) ? $_POST['prod_dclase'][$key] : null,
            'peso'                  => isset($_POST['prod_dpeso'][$key]) && $_POST['prod_dpeso'][$key] !== '' ? $_POST['prod_dpeso'][$key] : null,
            'certificado'           => $_POST['isCert'][$key] === '1' ? 't' : 'f',
            'id_unidad'             => $did_unidad,
            'unidad_c'              => $dunidad_c,
            'id_calidad'            => ($_POST['prod_did_calidad'][$key] !== ''? $_POST['prod_did_calidad'][$key]: NULL),
            'id_tamanio'            => ($_POST['prod_did_tamanio'][$key] !== ''? $_POST['prod_did_tamanio'][$key]: NULL),
            'id_calibres'           => ($_POST['prod_did_tamanio_prod'][$key] !== ''? $_POST['prod_did_tamanio_prod'][$key]: NULL),
            'descripcion2'          => $_POST['prod_ddescripcion2'][$key],
            'no_identificacion'     => $_POST['no_identificacion'][$key],
            'cfdi_ext'              => json_encode($cfdi_extpp),
          );

          if ($_POST['prod_did_prod'][$key] === '49' && !isset($seg_cer_entro['49']))
          {
            foreach ($_POST['seg_id_proveedor'] as $keysecer => $data_secer) {
              $dataSeguroCerti[] = array(
                'id_factura'       => $idBorrador,
                'id_clasificacion' => $_POST['prod_did_prod'][$key],
                'nrow'             => $nrow_seg_cer,
                'id_proveedor'     => $_POST['seg_id_proveedor'][$keysecer],
                'pol_seg'          => $_POST['seg_poliza'][$keysecer],
                'folio'            => $serieFolio,
                'bultos'           => 0,
                'certificado'      => null,
                'num_operacion'    => null,
              );
              ++$nrow_seg_cer;
            }
            $seg_cer_entro[$_POST['prod_did_prod'][$key]] = true;
          }

          if (($_POST['prod_did_prod'][$key] === '51' && !isset($seg_cer_entro['51'])) || ($_POST['prod_did_prod'][$key] === '52' && !isset($seg_cer_entro['52'])))
          {
            foreach ($_POST['cert_id_proveedor'.$_POST['prod_did_prod'][$key]] as $keysecer => $data_secer) {
              $dataSeguroCerti[] = array(
                'id_factura'       => $idBorrador,
                'id_clasificacion' => $_POST['prod_did_prod'][$key],
                'nrow'             => $nrow_seg_cer,
                'id_proveedor'     => $_POST['cert_id_proveedor'.$_POST['prod_did_prod'][$key]][$keysecer],
                'certificado'      => $_POST['cert_certificado'.$_POST['prod_did_prod'][$key]][$keysecer],
                'folio'            => $serieFolio,
                'bultos'           => $_POST['cert_bultos'.$_POST['prod_did_prod'][$key]][$keysecer],
                'pol_seg'          => null,
                'num_operacion'    => $_POST['cert_num_operacion'.$_POST['prod_did_prod'][$key]][$keysecer],
              );
              ++$nrow_seg_cer;
            }
            $seg_cer_entro[$_POST['prod_did_prod'][$key]] = true;
          }

          if ($_POST['prod_did_prod'][$key] === '53' && !isset($seg_cer_entro['53']))
          {
            foreach ($_POST['seg_id_proveedor'] as $keysecer => $data_secer) {
              $dataSeguroCerti[] = array(
                'id_factura'       => $idBorrador,
                'id_clasificacion' => $_POST['prod_did_prod'][$key],
                'nrow'             => $nrow_seg_cer,
                'id_proveedor'     => $_POST['supcarga_id_proveedor'][$keysecer],
                'certificado'      => $_POST['supcarga_numero'][$keysecer],
                'folio'            => $serieFolio,
                'bultos'           => $_POST['supcarga_bultos'][$keysecer],
                'pol_seg'          => null,
                'num_operacion'    => $_POST['supcarga_num_operacion'][$keysecer],
              );
              ++$nrow_seg_cer;
            }
            $seg_cer_entro[$_POST['prod_did_prod'][$key]] = true;
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

      if (!empty($this->input->post('comercioExterior')['tipoOperacion']) ||
          !empty($this->input->post('comercioExterior')['clavePedimento']) ||
          !empty($this->input->post('comercioExterior')['certificadoOrigen']) ) {
        $_POST['comercioExteriorPros'] = $this->addComercioExterior($idBorrador, true);
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
	public function getSeriesFolios($per_pag='30', $sql2='')
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
        $sql .= " AND lower(serie) LIKE '".mb_strtolower($this->input->get('fserie'), 'UTF-8')."'";

		$query = BDUtil::pagination("SELECT fsf.id_serie_folio, fsf.id_empresa, fsf.serie, fsf.no_aprobacion, fsf.folio_inicio,
					fsf.folio_fin, fsf.leyenda, fsf.leyenda1, fsf.leyenda2, fsf.ano_aprobacion, e.nombre_fiscal AS empresa
				FROM facturacion_series_folios AS fsf
				INNER JOIN empresas AS e ON e.id_empresa = fsf.id_empresa
        WHERE 1 = 1 {$sql} {$sql2}
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

  public function get_series($ide, $tipof)
  {
    if(isset($ide))
    {
      $tipo = $tipof;
      $this->load->model('facturacion_model');
      $res = $this->getSeriesEmpresa($ide);
      $quit = array('f' => array('NCR' => 0, 'R' => 0, 'AB' => 0, 'VD' => 0), 'r' => array('D' => 0, 'AB' => 0, 'VD' => 0),
                    'vd' => array('F' => 0, 'NCR' => 0, 'R' => 0, 'AB' => 0, 'D' => 0, 'RE' => 0));
      foreach ($res[0] as $key => $value)
      {
        if(isset($quit[$tipo][$value->serie]) && $value->serie == $quit[$tipo][$value->serie])
          unset($res[0][$key]);
      }

      $param['data'] = $res[0];

      return $param;
    }
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

        $ids = $this->usuarios_model->getEmpresasPermiso('ids');
        if (count($ids) > 0) {
          $sql .= " AND id_empresa in(".implode(',', $ids).")";
        }

        $res = $this->db->query("
            SELECT e.id_empresa, e.nombre_fiscal, e.cer_caduca, e.cfdi_version, e.cer_org, e.calle, e.colonia, e.cp, e.estado, e.localidad, e.municipio, e.pais,
                  e.no_exterior, e.no_interior, e.rfc, e.sucursal
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
                  {$sql}
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
                'label' => $itm->nombre_fiscal . (!empty($itm->sucursal)? " - $itm->sucursal": ''),
                'value' => $itm->nombre_fiscal . (!empty($itm->sucursal)? " - $itm->sucursal": ''),
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

    public function getRPF()
    {
      $sql = '';
      $response = array();

      if ( !is_array($this->input->get('ids_productos')) && $this->input->get('dcontiene') == '')
      {
        exit();
      }

      if ($this->input->get('dcontiene') != '') {
        $_GET['ids_productos'] = array($this->input->get('dcontiene'));
      }

      foreach ($this->input->get('ids_productos') as $key => $prod)
      {
        $sql = "WHERE 1 = 1";
        if (is_numeric($prod)) {
          $sql .= " AND fp.id_clasificacion = {$prod}";
        } else {
          $prod = mb_strtoupper($prod, 'UTF-8');
          $sql .= " AND UPPER(fp.descripcion) LIKE '%{$prod}%'";
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

          if ($this->input->get('did_empresa') == 12) {
            $sql .= " AND f.id_factura <> 23525";
          }
        }

        if ($this->input->get('dtipo') != '')
        {
          $sql .= " AND f.is_factura = '" . $this->input->get('dtipo') . "'";
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
                    SUM(fp.importe) as importe, COALESCE(fc.pol_seg, fc.certificado) AS poliza
            FROM facturacion f
            INNER JOIN facturacion_productos fp ON fp.id_factura = f.id_factura
            INNER JOIN clientes c ON c.id_cliente= f.id_cliente
            LEFT JOIN facturacion_seg_cert fc ON f.id_factura = fc.id_factura AND fp.id_clasificacion = fc.id_clasificacion
            {$sql}
            GROUP BY f.id_factura, f.fecha, f.serie, f.folio, c.nombre_fiscal, fp.precio_unitario, fc.pol_seg, fc.certificado
            ORDER BY f.fecha ASC");

        if (is_numeric($prod)) {
          $prodcto = $this->db->query(
              "SELECT id_clasificacion, nombre FROM clasificaciones WHERE id_clasificacion = ".$prod)->row();
        } else {
          $prodcto = $this->db->query(
              "SELECT 0 AS id_clasificacion, '{$prod}' AS nombre")->row();
        }

        $response[] = array('producto' => $prodcto, 'listado' => $query->result());
        $query->free_result();
      }

      return $response;
    }

    public function getRPF2()
    {
      $response = array();

      $sql = "WHERE 1 = 1";

      $prod = null;
      if ($this->input->get('dcontiene') != '') {
        $prod = mb_strtoupper($prod, 'UTF-8');
        $sql .= " AND UPPER(fp.descripcion) LIKE '%{$prod}%'";
      }

      if (is_array($this->input->get('ids_productos'))) {
        $sql .= " AND fp.id_clasificacion in(".implode(', ', $this->input->get('ids_productos')).")";
      }

      //Filtro de fecha.
      if($this->input->get('ffecha1') == '' && $this->input->get('ffecha2') == '')
        $sql .= " AND Date(f.fecha) BETWEEN '".date("Y-m")."-01' AND '".date("Y-m-d")."'";
      elseif($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '')
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

      if ($this->input->get('dtipo') != '')
      {
        $sql .= " AND f.is_factura = '" . $this->input->get('dtipo') . "'";
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
                  SUM(fp.importe) as importe, COALESCE(fc.pol_seg, fc.certificado) AS poliza,
                  cl.id_clasificacion, cl.nombre
          FROM facturacion f
            INNER JOIN facturacion_productos fp ON fp.id_factura = f.id_factura
            INNER JOIN clientes c ON c.id_cliente = f.id_cliente
            INNER JOIN clasificaciones cl ON cl.id_clasificacion = fp.id_clasificacion
            LEFT JOIN facturacion_seg_cert fc ON f.id_factura = fc.id_factura AND fp.id_clasificacion = fc.id_clasificacion
          {$sql}
          GROUP BY f.id_factura, c.id_cliente, fp.precio_unitario, fc.pol_seg, fc.certificado, cl.id_clasificacion
          ORDER BY cl.nombre ASC, f.fecha ASC");

      $productos = $query->result();
      if (count($productos) > 0) {
        $auxp = 0;
        $lista = [];
        $prodcto = ['id_clasificacion' => $productos[0]->id_clasificacion, 'nombre' => $productos[0]->nombre];
        foreach ($productos as $key => $prod)
        {
          if ($prod->id_clasificacion != $auxp) {
            if ($key > 0) {
              $response[] = array('producto' => (object)$prodcto, 'listado' => $lista);
            }

            $auxp = $prod->id_clasificacion;
            $lista = [];
          } else {
            $prodcto['nombre'] = $prod->nombre;
            $prodcto['id_clasificacion'] = $prod->id_clasificacion;
          }

          $lista[] = $prod;
        }
        $response[] = array('producto' => (object)$prodcto, 'listado' => $lista);
      }

      return $response;
    }

    /**
     * Reportes Productos Facturados.
     *
     * @return void
     */
    public function prodfact_pdf()
    {
      // if (isset($_GET['did_producto']))
      // {
        if (empty($_GET['did_empresa'])) {
          $empresaDef = $this->empresas_model->getDefaultEmpresa();
          $_GET['did_empresa'] = $empresaDef->id_empresa;
        }

        $facturas = $this->getRPF2();

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

        // $pdf->titulo3 = "{$_GET['dproducto']} \n";
        if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
            $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1'])." al ".MyString::fechaAT($_GET['ffecha2'])."";
        elseif (!empty($_GET['ffecha1']))
            $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1']);
        elseif (!empty($_GET['ffecha2']))
            $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha2']);

        $pdf->AliasNbPages();
        // $links = array('', '', '', '');
        $pdf->SetY(30);
        $aligns = array('C', 'C', 'L', 'L', 'R','R', 'R');
        $widths = array(18, 17, 90, 22, 12, 20, 25);
        $header = array('Fecha', 'Serie/Folio', 'Cliente', 'Poliza', 'Cantidad', 'Precio', 'Importe');

        $cantidad = 0;
        $importe = 0;
        $cantidadt = 0;
        $importet = 0;
        $promedio = 0;

        foreach($facturas as $key => $product)
        {
          $cantidad = 0;
          $importe = 0;
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
          $pdf->SetFont('Arial','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetX(6);
          $pdf->SetAligns(array('L'));
          $pdf->SetWidths(array(180));
          $pdf->Row(array($product['producto']->nombre), false, false);

          foreach ($product['listado'] as $key2 => $item)
          {
            $band_head = false;
            if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
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
              $item->poliza,
              $item->cantidad,
              MyString::formatoNumero($item->precio_unitario, 2, '$', false),
              MyString::formatoNumero($item->importe, 2, '$', false)
            );

            $cantidad += floatval($item->cantidad);
            $importe  += floatval($item->importe);

            $cantidadt += floatval($item->cantidad);
            $importet  += floatval($item->importe);

            $pdf->SetX(6);
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->Row($datos, false);
          }

          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);

          $pdf->SetFont('Arial','B',8);
          $pdf->SetTextColor(255,255,255);
          $pdf->Row(array('', '', '', '',
              $cantidad,
              $cantidad == 0 ? 0 : MyString::formatoNumero($importe/$cantidad, 2, '$', false),
              MyString::formatoNumero($importe, 2, '$', false) ), true);
        }

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->Row(array('', '', '', '',
            $cantidadt,
            $cantidadt == 0 ? 0 : MyString::formatoNumero($importet/$cantidadt, 2, '$', false),
            MyString::formatoNumero($importet, 2, '$', false) ), true);

        $pdf->Output('Reporte_Productos_Facturados.pdf', 'I');
      // }
    }

    public function prodfact_xls()
    {
      header('Content-type: application/vnd.ms-excel; charset=utf-8');
      header("Content-Disposition: attachment; filename=productos_facturados.xls");
      header("Pragma: no-cache");
      header("Expires: 0");

      $facturas = $this->getRPF2();

      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

      $titulo1 = $empresa['info']->nombre_fiscal;
      $titulo2 = "Reporte Productos Facturados";
      $titulo3 = "";
      if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
          $titulo3 .= "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      elseif (!empty($_GET['ffecha1']))
          $titulo3 .= "Del ".$_GET['ffecha1'];
      elseif (!empty($_GET['ffecha2']))
          $titulo3 .= "Del ".$_GET['ffecha2'];

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
            <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Fecha</td>
            <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Serie/Folio</td>
            <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Cliente</td>
            <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Cantidad</td>
            <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Precio</td>
            <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Importe</td>
          </tr>';
      $total_importe = $total_cantidad = 0;
      $total_importet = $total_cantidadt = 0;
      foreach ($facturas as $key => $produc)
      {
        $total_importe = $total_cantidad = 0;

        $html .= '<tr>
              <td colspan="6" style="font-size:14px;border:1px solid #000;">'.$produc['producto']->nombre.'</td>
            </tr>';
        foreach ($produc['listado'] as $key2 => $value)
        {
          $html .= '<tr>
              <td style="width:150px;border:1px solid #000;">'.$value->fecha.'</td>
              <td style="width:100px;border:1px solid #000;">'.$value->serie.$value->folio.'</td>
              <td style="width:400px;border:1px solid #000;">'.$value->cliente.'</td>
              <td style="width:100px;border:1px solid #000;">'.$value->cantidad.'</td>
              <td style="width:150px;border:1px solid #000;">'.$value->precio_unitario.'</td>
              <td style="width:150px;border:1px solid #000;">'.$value->importe.'</td>
            </tr>';
            $total_importe += $value->importe;
            $total_cantidad += $value->cantidad;
            $total_importet += $value->importe;
            $total_cantidadt += $value->cantidad;
        }
        $html .= '
          <tr style="font-weight:bold">
            <td colspan="3">TOTAL</td>
            <td style="border:1px solid #000;">'.$total_cantidad.'</td>
            <td style="border:1px solid #000;">'.($total_cantidad == 0 ? 0 : $total_importe/$total_cantidad).'</td>
            <td style="border:1px solid #000;">'.$total_importe.'</td>
          </tr>
          <tr>
            <td colspan="6"></td>
          </tr>
          <tr>
            <td colspan="6"></td>
          </tr>';
      }

      $html .= '
          <tr style="font-weight:bold">
            <td colspan="3">TOTALES</td>
            <td style="border:1px solid #000;">'.$total_cantidadt.'</td>
            <td style="border:1px solid #000;">'.($total_cantidadt == 0 ? 0 : $total_importet/$total_cantidadt).'</td>
            <td style="border:1px solid #000;">'.$total_importet.'</td>
          </tr>
        </tbody>
      </table>';

      echo $html;
    }

    /**
     * Reporte compras x cliente
     *
     * @return
     */
    public function getRVentascData()
    {
      $sql = '';
      $this->load->model('cuentas_cobrar_model');

      //Filtros para buscar
      $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
      $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
      $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

      // if($this->input->get('fid_producto') != ''){
      //   $sql .= " AND cp.id_producto = ".$this->input->get('fid_producto');
      // }

      $this->load->model('empresas_model');
      $client_default   = $this->empresas_model->getDefaultEmpresa();
      $did_empresa      = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : array($client_default->id_empresa));
      $_GET['dempresa'] = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
      // if($this->input->get('did_empresa') != ''){
      //   $sql .= " AND c.id_empresa = '".$this->input->get('did_empresa')."'";
      // }
      $tipo_factura = array('', '');
      if($this->input->get('dtipo_factura') != '')
        $tipo_factura = array(" AND f.is_factura='".$this->input->get('dtipo_factura')."'", " AND is_factura='".$this->input->get('dtipo_factura')."'");

      $sql_clientes = '';
      if(is_array($this->input->get('ids_clientes')))
        $sql_clientes = " AND id_cliente IN(".implode(',', $this->input->get('ids_clientes')).")";

      foreach ($did_empresa as $key => $value) {
        $_GET['did_empresa'] = $value;
        $facturas = $this->db->query("SELECT id_factura, serie, folio, id_cliente, nombre_fiscal, id_empresa, empresa,
            subtotal, total, iva AS importe_iva, abonos, saldo, tipo, is_factura, fecha, cantidad_productos,
            (CASE is_factura WHEN true THEN 'FACTURA ELECTRONICA' ELSE 'REMISION' END) AS concepto
          FROM saldos_facturas_remisiones
          WHERE id_empresa = {$value} AND fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}' {$tipo_factura[1]} {$sql_clientes}
          ORDER BY id_empresa ASC, id_cliente ASC, serie ASC, folio ASC")->result();
        $clientes = array();
        $aux=0;
        foreach ($facturas as $keyf => $fact) {
          if ($aux != $fact->id_cliente) {
            $clientes[] = (object) array(
              'id_cliente' => $fact->id_cliente,
              'nombre_fiscal' => $fact->nombre_fiscal,
              'cuenta_cpi' => '',
              'facturas' => array(),
              );
            $aux = $fact->id_cliente;
          }
          $clientes[count($clientes)-1]->facturas[] = $fact;
        }

        $response[] = array('facturas' => $clientes,
          'empresa' => $this->empresas_model->getInfoEmpresa($value));
      }

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
      $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
      // $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
      $pdf->AliasNbPages();
      $pdf->AddPage();
      $pdf->SetFont('Arial','',8);

      $aligns = array('L', 'L', 'R', 'L', 'R', 'R', 'R', 'R', 'R');
      $widths = array(20, 11, 15, 40, 23, 23, 23, 23, 23);
      $header = array('Fecha', 'Serie', 'Folio', 'Concepto', 'Cantidad', 'Neto', 'Impuesto', 'Total', 'Saldo');
      $links = array('', '', '', '', '', '', '', '', '');

      $total_subtotal_g2 = 0;
      $total_impuesto_g2 = 0;
      $total_total_g2 = 0;
      $total_cantidad_g2 = 0;
      $total_saldo_g2 = 0;
      $total_saldo_cliente_g2 = 0;
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
        $pdf->Row(array($dempresa['empresa']['info']->nombre_fiscal), false, false);

        foreach($dempresa['facturas'] as $key => $item) {
          if (count($item->facturas) > 0 || $con_mov)
          {
            $total_subtotal = 0;
            $total_impuesto = 0;
            $total_total = 0;
            $total_cantidad = 0;
            $total_saldo = 0;

            if($pdf->GetY()+10 >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
              if($key > 0)
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

              $total_subtotal_g += $factura->subtotal;
              $total_saldo_g += $factura->saldo;
              $total_cantidad_g += $factura->cantidad_productos;
              $total_impuesto_g += $factura->importe_iva;
              $total_total_g += $factura->total;

              $total_subtotal_g2 += $factura->subtotal;
              $total_saldo_g2 += $factura->saldo;
              $total_cantidad_g2 += $factura->cantidad_productos;
              $total_impuesto_g2 += $factura->importe_iva;
              $total_total_g2 += $factura->total;

              $links[3] = base_url('panel/facturacion/rventasc_detalle_pdf?venta='.$factura->id_factura.'&did_empresa='.$empresa['info']->id_empresa);
              $datos = array(MyString::fechaATexto($factura->fecha, '/c'),
                      $factura->serie,
                      $factura->folio,
                      $factura->concepto,
                      MyString::formatoNumero($factura->cantidad_productos, 2, '', false),
                      MyString::formatoNumero($factura->subtotal, 2, '', false),
                      MyString::formatoNumero($factura->importe_iva, 2, '', false),
                      MyString::formatoNumero($factura->total, 2, '', false),
                      MyString::formatoNumero( ($factura->saldo) , 2, '', false),
                      // MyString::fechaATexto($factura->fecha_vencimiento, '/c'),
                    );

              if($pdf->GetY()+10 >= $pdf->limiteY)
                $pdf->AddPage();
              $pdf->SetXY(6, $pdf->GetY()-1);
              $pdf->SetAligns($aligns);
              $pdf->SetWidths($widths);
              $pdf->SetMyLinks($links);
              $pdf->Row($datos, false, false);
            }
            $pdf->SetMyLinks(array());

            if($pdf->GetY()+10 >= $pdf->limiteY)
              $pdf->AddPage();
            $pdf->SetX(93);
            $pdf->SetFont('Arial','B',8);
            // $pdf->SetTextColor(255,255,255);
            $pdf->SetAligns(array('R', 'R', 'R', 'R', 'R', 'R'));
            $pdf->SetWidths(array(23, 23, 23, 23, 23));
            $pdf->Row(array(
                MyString::formatoNumero($total_cantidad, 2, '', false),
                MyString::formatoNumero($total_subtotal, 2, '', false),
                MyString::formatoNumero($total_impuesto, 2, '', false),
                MyString::formatoNumero($total_total, 2, '', false),
                MyString::formatoNumero($total_saldo, 2, '', false)), false);

            // $total_saldo_cliente += $saldo_cliente;
          }
        }
        if($pdf->GetY()+10 >= $pdf->limiteY)
          $pdf->AddPage();
        $pdf->SetX(93);
        $pdf->SetAligns(array('R', 'R', 'R', 'R', 'R', 'R'));
        $pdf->SetWidths(array(23, 23, 23, 23, 23));
        $pdf->Row(array(
            MyString::formatoNumero($total_cantidad_g, 2, '', false),
            MyString::formatoNumero($total_subtotal_g, 2, '', false),
            MyString::formatoNumero($total_impuesto_g, 2, '', false),
            MyString::formatoNumero($total_total_g, 2, '', false),
            MyString::formatoNumero($total_saldo_g, 2, '', false)), false);
      }

      if($pdf->GetY()+10 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetX(63);
      $pdf->SetAligns(array('R', 'R', 'R', 'R', 'R', 'R', 'R'));
      $pdf->SetWidths(array(30, 23, 23, 23, 23, 23));
      $pdf->Row(array('TOTAL GRAL',
          MyString::formatoNumero($total_cantidad_g2, 2, '', false),
          MyString::formatoNumero($total_subtotal_g2, 2, '', false),
          MyString::formatoNumero($total_impuesto_g2, 2, '', false),
          MyString::formatoNumero($total_total_g2, 2, '', false),
          MyString::formatoNumero($total_saldo_g2, 2, '', false)), false);


      // $pdf->SetXY(66, $pdf->GetY()+4);
      // $pdf->Row(array('TOTAL SALDO DE CLIENTES', MyString::formatoNumero( $total_saldo_cliente , 2, '', false)), false);


      $pdf->Output('reporte_ventas.pdf', 'I');
    }

    public function getRVentascXls()
    {
      header('Content-type: application/vnd.ms-excel; charset=utf-8');
      header("Content-Disposition: attachment; filename=compras_x_producto.xls");
      header("Pragma: no-cache");
      header("Expires: 0");

      $res = $this->getRVentascData();

      $con_mov = $this->input->get('dcon_mov')=='si'? false: true;

      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

      $titulo1 = $empresa['info']->nombre_fiscal;
      $titulo2 = 'Ventas por Cliente';
      $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";


      $html = '<table>
        <tbody>
          <tr>
            <td colspan="9" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
          </tr>
          <tr>
            <td colspan="9" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
          </tr>
          <tr>
            <td colspan="9" style="text-align:center;">'.$titulo3.'</td>
          </tr>
          <tr>
            <td colspan="9"></td>
          </tr>';
        $html .= '<tr style="font-weight:bold">
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Fecha</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Serie</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio</td>
          <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Concepto</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Cantidad</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Neto</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Impuesto</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Total</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Saldo</td>
        </tr>';

      $total_subtotal_g2 = 0;
      $total_impuesto_g2 = 0;
      $total_total_g2 = 0;
      $total_cantidad_g2 = 0;
      $total_saldo_g2 = 0;
      $total_saldo_cliente_g2 = 0;
      foreach ($res as $keye => $dempresa) {
        $total_subtotal_g = 0;
        $total_impuesto_g = 0;
        $total_total_g = 0;
        $total_cantidad_g = 0;
        $total_saldo_g = 0;
        $total_saldo_cliente_g = 0;

        $html .= '<tr style="font-weight:bold">
            <td colspan="9">'.$dempresa['empresa']['info']->nombre_fiscal.'</td>
          </tr>';

        foreach($dempresa['facturas'] as $key => $item) {
          if (count($item->facturas) > 0 || $con_mov)
          {
            $total_subtotal = 0;
            $total_impuesto = 0;
            $total_total = 0;
            $total_cantidad = 0;
            $total_saldo = 0;

            $html .= '<tr style="font-weight:bold">
                <td colspan="1">CLIENTE:</td>
                <td colspan="8">'.$item->cuenta_cpi.'</td>
              </tr>
              <tr style="font-weight:bold">
                <td colspan="1">NOMBRE:</td>
                <td colspan="8">'.$item->nombre_fiscal.'</td>
              </tr>';

            foreach ($item->facturas as $keyf => $factura)
            {
              $total_subtotal += $factura->subtotal;
              $total_saldo += $factura->saldo;
              $total_cantidad += $factura->cantidad_productos;
              $total_impuesto += $factura->importe_iva;
              $total_total += $factura->total;

              $total_subtotal_g += $factura->subtotal;
              $total_saldo_g += $factura->saldo;
              $total_cantidad_g += $factura->cantidad_productos;
              $total_impuesto_g += $factura->importe_iva;
              $total_total_g += $factura->total;

              $total_subtotal_g2 += $factura->subtotal;
              $total_saldo_g2 += $factura->saldo;
              $total_cantidad_g2 += $factura->cantidad_productos;
              $total_impuesto_g2 += $factura->importe_iva;
              $total_total_g2 += $factura->total;

              $html .= '<tr>
                  <td style="width:150px;border:1px solid #000;">'.MyString::fechaATexto($factura->fecha, '/c').'</td>
                  <td style="width:150px;border:1px solid #000;">'.$factura->serie.'</td>
                  <td style="width:150px;border:1px solid #000;">'.$factura->folio.'</td>
                  <td style="width:400px;border:1px solid #000;">'.$factura->concepto.'</td>
                  <td style="width:150px;border:1px solid #000;">'.$factura->cantidad_productos.'</td>
                  <td style="width:150px;border:1px solid #000;">'.$factura->subtotal.'</td>
                  <td style="width:150px;border:1px solid #000;">'.$factura->importe_iva.'</td>
                  <td style="width:150px;border:1px solid #000;">'.$factura->total.'</td>
                  <td style="width:150px;border:1px solid #000;">'.$factura->saldo.'</td>
                </tr>';
            }
            $html .= '
                <tr style="font-weight:bold">
                  <td colspan="4"></td>
                  <td style="border:1px solid #000;">'.$total_cantidad.'</td>
                  <td style="border:1px solid #000;">'.$total_subtotal.'</td>
                  <td style="border:1px solid #000;">'.$total_impuesto.'</td>
                  <td style="border:1px solid #000;">'.$total_total.'</td>
                  <td style="border:1px solid #000;">'.$total_saldo.'</td>
                </tr>';
          }
        }

        $html .= '
            <tr style="font-weight:bold">
              <td colspan="4"></td>
              <td style="border:1px solid #000;">'.$total_cantidad_g.'</td>
              <td style="border:1px solid #000;">'.$total_subtotal_g.'</td>
              <td style="border:1px solid #000;">'.$total_impuesto_g.'</td>
              <td style="border:1px solid #000;">'.$total_total_g.'</td>
              <td style="border:1px solid #000;">'.$total_saldo_g.'</td>
            </tr>';
      }

      $html .= '
          <tr style="font-weight:bold">
            <td colspan="4">TOTAL GRAL</td>
            <td style="border:1px solid #000;">'.$total_cantidad_g2.'</td>
            <td style="border:1px solid #000;">'.$total_subtotal_g2.'</td>
            <td style="border:1px solid #000;">'.$total_impuesto_g2.'</td>
            <td style="border:1px solid #000;">'.$total_total_g2.'</td>
            <td style="border:1px solid #000;">'.$total_saldo_g2.'</td>
          </tr>
        </tbody>
      </table>';

      echo $html;
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
          MyString::fechaATexto($res['factura']->fecha, '/c'),
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
            MyString::formatoNumero($item->cantidad, 2, '', false),
            MyString::formatoNumero($item->precio_unitario, 2, '', false),
            MyString::formatoNumero($item->importe, 2, '', false),
            MyString::formatoNumero($item->iva, 2, '', false),
            MyString::formatoNumero(($item->importe+$item->iva), 2, '', false)), false);
        $total_cantidad += $item->cantidad;
      }

      $pdf->SetX(80);
      $pdf->SetFont('Arial','B',8);
      // $pdf->SetTextColor(255,255,255);
      $pdf->SetAligns(array('R', 'R', 'R', 'R', 'R', 'R'));
      $pdf->SetWidths(array(25, 25, 25, 25, 25));
      $pdf->Row(array(
          MyString::formatoNumero($total_cantidad, 2, '', false),
          '',
          MyString::formatoNumero($res['factura']->subtotal, 2, '', false),
          MyString::formatoNumero($res['factura']->importe_iva, 2, '', false),
          MyString::formatoNumero($res['factura']->total, 2, '', false)), false);


      $pdf->Output('reporte_ventas.pdf', 'I');
    }


  /*
  |------------------------------------------------------------------------
  | FACTURA PDF
  |------------------------------------------------------------------------
  */

  public function generaFacturaPdf($idFactura, $path = null) {
    $factura = $this->getInfoFactura($idFactura);
    $version = str_replace('.', '', $factura['info']->version);
    $this->{"generaFacturaPdf{$version}"}($factura, $path);
  }

  public function generaFacturaPdf32($factura, $path = null)
  {
    $this->load->library('cfdi');
    include_once(APPPATH.'libraries/phpqrcode/qrlib.php');
    $this->load->model('nomina_catalogos_model');

    // $factura = $this->getInfoFactura($idFactura);

    $this->load->model('documentos_model');
    $manifiesto_chofer = $this->documentos_model->getJsonDataDocus($factura['info']->id_factura, 1);
    if (isset($manifiesto_chofer->chofer_id)) {
      $data_chofer = $this->db->query("SELECT * FROM choferes WHERE id_chofer = {$manifiesto_chofer->chofer_id}")->row();
    }

    // echo "<pre>";
    //   var_dump($factura);
    // echo "</pre>";exit;

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

    $pdf->SetFont('Arial','B', 70);
    $pdf->SetTextColor(160,160,160);
    $pdf->RotatedText(65, 130, ($factura['info']->no_impresiones==0? 'ORIGINAL': 'COPIA #'.$factura['info']->no_impresiones), 45);

    $pdf->SetXY(0, 0);
    /////////////////////////////////////
    // Folio Fisca, CSD, Lugar y Fecha //
    /////////////////////////////////////

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(0, $pdf->GetY() + 2);
    $pdf->Cell(108, 4, "Folio Fiscal:", 0, 0, 'R', 1);

    $titulo_comprobante = '                 '.($factura['info']->condicion_pago=='co'? 'Factura al contado': 'Factura a credito');
    if($factura['info']->id_nc != '')
      $titulo_comprobante = '                 Nota de Crédito';
    elseif($factura['info']->id_abono_factura != '')
      $titulo_comprobante = '                 Abono del Cliente';
    elseif (isset($factura['carta_porte']))
      $titulo_comprobante = '                 Carta Porte - Factura';
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Cell(50, 4, $titulo_comprobante.':  '.($factura['info']->serie.$factura['info']->folio) , 0, 0, 'L', 1);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(0, $pdf->GetY() + 6);
    $pdf->Cell(108, 4, $xml->Complemento->TimbreFiscalDigital[0]['UUID'], 0, 0, 'C', 0);

    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(0, $pdf->GetY() + 4);
    $pdf->Cell(48, 4, "Fecha y hora de impresión:", 0, 0, 'L', 1);
    $pdf->SetXY(48, $pdf->GetY());
    $pdf->Cell(60, 4, "No de Serie del Certificado del CSD:", 0, 0, 'R', 1);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica','', 9);
    $pdf->SetXY(0, $pdf->GetY() + 4);
    $pdf->Cell(48, 4, MyString::fechaATexto(date("Y-m-d")).' '.date("H:i:s"), 0, 0, 'L', 0);
    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetXY(48, $pdf->GetY());
    $pdf->Cell(60, 4, $xml->Complemento->TimbreFiscalDigital[0]['noCertificadoSAT'], 0, 0, 'R', 0);

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
    $fecha = MyString::fechaATexto($fecha[0]);

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
    $pdf->SetWidths(array(19, 46, 19, 40, 11, 46, 11, 30));
    $pdf->SetX(0);
    $pdf->Row(array('MUNICIPIO:', $xml->Emisor->DomicilioFiscal[0]['municipio'], 'ESTADO:', $xml->Emisor->DomicilioFiscal[0]['estado'], 'PAIS:', $xml->Emisor->DomicilioFiscal[0]['pais'], 'CP:', $xml->Emisor->DomicilioFiscal[0]['codigoPostal']), false, false, null, 2, 1);

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

    $cod_rf = $this->nomina_catalogos_model->findByClave($xml->Emisor->RegimenFiscal[0]['Regimen'], 'rgf');
    $regimen_fiscal = isset($cod_rf->nombre_corto)? $cod_rf->nombre_corto: $xml->Emisor->RegimenFiscal[0]['Regimen'];

    $pdf->SetFont('helvetica','', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(109, $pdf->GetY() + 4);
    $pdf->MultiCell(108, 4, $regimen_fiscal, 0, 'C', 0);

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
    $aligns = array('C', 'C', 'C', 'C', 'C', 'C');
    $aligns2 = array('C', 'C', 'L', 'C', 'R', 'R');
    $widths = array(30, 35, 71, 20, 30, 30);
    $header = array('Cantidad', 'Unidad de Medida', 'Descripcion', 'Cert.', 'Precio Unitario', 'Importe');

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
    $hay_prod_certificados = false;
    foreach($factura['productos'] as $key => $item)
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
        if ( GastosProductos::searchGastosProductos($item->id_clasificacion) )
          $printRow = false;
      }

      if ($item->certificado === 't')
        $hay_prod_certificados = true;

      $descripcion_ext = strlen($item->descripcion2)>0? " ({$item->descripcion2})": '';

      if($printRow)
        $pdf->Row(array(
          MyString::formatoNumero($item->cantidad, 2, ''),
          $item->unidad,
          $this->cfdi->replaceSpecialChars($item->descripcion.$descripcion_ext, true),
          $item->certificado === 't' ? 'Certificado' : '',
          MyString::formatoNumero( ($item->precio_unitario/($factura['info']->tipo_cambio>0? $factura['info']->tipo_cambio: 1)), 2, '$', false),
          MyString::formatoNumero( ($item->importe/($factura['info']->tipo_cambio>0? $factura['info']->tipo_cambio: 1)), 2, '$', false),
        ), false, true, null, 2, 1);
    }

    // foreach($conceptos as $key => $item)
    // {
    //   $band_head = false;

    //   if($pdf->GetY() >= $pdf->limiteY || $key === 0) //salta de pagina si exede el max
    //   {
    //     if($key > 0) $pdf->AddPage();

    //     $pdf->SetFont('Arial', 'B', 8);
    //     $pdf->SetTextColor(0, 0, 0);
    //     $pdf->SetFillColor(242, 242, 242);
    //     $pdf->SetX(0);
    //     $pdf->SetAligns($aligns);
    //     $pdf->SetWidths($widths);
    //     $pdf->Row($header, true, true, null, 2, 1);
    //   }

    //   $pdf->SetFont('Arial', '', 8);
    //   $pdf->SetTextColor(0,0,0);

    //   $pdf->SetX(0);
    //   $pdf->SetAligns($aligns2);
    //   $pdf->SetWidths($widths);
    //   $pdf->Row(array(
    //     MyString::formatoNumero($item[0]['cantidad'], 2, ''),
    //     $item[0]['unidad'],
    //     $item[0]['descripcion'],
    //     $item[0]['certificado'] === 't' ? 'Si' : 'No',
    //     MyString::formatoNumero($item[0]['valorUnitario'], 2, '$', false),
    //     MyString::formatoNumero($item[0]['importe'], 2, '$', false),
    //   ), false, true, null, 2, 1);
    // }

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
    $text_condicionpago = 'Condicion de Pago: '.($factura['info']->condicion_pago=='co'? 'Contado': 'Credito');
    if($factura['info']->id_abono_factura != '')
      $text_condicionpago = '';
    $pdf->Cell(78, 4, $text_condicionpago, 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetXY(1, $pdf->GetY()+5);
    $pdf->Cell(78, 4, "Metodo de Pago: ".MyString::getMetodoPago($xml[0]['metodoDePago']), 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetXY(78, $pdf->GetY());
    $pdf->Cell(76, 4, "Cuenta de Pago: {$factura['info']->metodo_pago_digitos}", 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetXY(156, $pdf->GetY() - 16);
    $pdf->Cell(30, 5, "Subtotal", 1, 0, 'C', 1);

    $pdf->SetXY(186, $pdf->GetY());
    $pdf->Cell(30, 5, MyString::formatoNumero($xml[0]['subTotal'], 2, '$', false), 1, 0, 'R', 1);

    // Pinta traslados, retenciones

    if ($traslado11 != 0)
    {
      $pdf->SetXY(156, $pdf->GetY() + 5);
      $pdf->Cell(30, 5, "IVA(11%)", 1, 0, 'C', 1);

      $pdf->SetXY(186, $pdf->GetY());
      $pdf->Cell(30, 5,MyString::formatoNumero($traslado11, 2, '$', false), 1, 0, 'R', 1);
    }

    $pdf->SetXY(156, $pdf->GetY() + 5);
    $pdf->Cell(30, 5, "IVA(16%)", 1, 0, 'C', 1);

    $pdf->SetXY(186, $pdf->GetY());
    $pdf->Cell(30, 5,MyString::formatoNumero($traslado16, 2, '$', false), 1, 0, 'R', 1);

    if ($xml->Impuestos->Retenciones->Retencion[0]['importe'] != 0)
    {
      $pdf->SetXY(156, $pdf->GetY() + 5);
      $pdf->Cell(30, 5, "IVA Retenido", 1, 0, 'C', 1);

      $pdf->SetXY(186, $pdf->GetY());
      $pdf->Cell(30, 5,MyString::formatoNumero($xml->Impuestos->Retenciones->Retencion[0]['importe'], 2, '$', false), 1, 0, 'R', 1);
    }

    $pdf->SetXY(156, $pdf->GetY() + 5);
    $pdf->Cell(30, 5, "TOTAL", 1, 0, 'C', 1);

    $pdf->SetXY(186, $pdf->GetY());
    $pdf->Cell(30, 5,MyString::formatoNumero($xml[0]['total'], 2, '$', false), 1, 0, 'R', 1);

    ///////////////////
    // Observaciones //
    ///////////////////

    $pdf->SetXY(0, $pdf->GetY() + 5);

    $width = (($pdf->GetStringWidth($factura['info']->observaciones) / 216) * 8) + 9;

    if($pdf->GetY() + $width >= $pdf->limiteY) //salta de pagina si exede el max
        $pdf->AddPage();

    if ( ! empty($factura['info']->observaciones))
    {
        $pdf->SetXY(0, $pdf->GetY() + 5);
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
      if($pdf->GetY() + 10 >= $pdf->limiteY) //salta de pagina si exede el max
          $pdf->AddPage();

      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(10, $pdf->GetY()+5);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array('GGN4052852866927 PRODUCTO CERTIFICADO'), false, 0);
    }

    if (isset($xml[0]['TipoCambio']))
    {
      if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
          $pdf->AddPage();
      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(10, $pdf->GetY() + 5 );
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array('Tasa de Cambio: '.MyString::formatoNumero($xml[0]['TipoCambio'], 4) ), false, 0);
    }else
      $pdf->SetXY(10, $pdf->GetY() + 5);


    ////////////////////////
    // Comercio Exterior //
    ///////////////////////
    if (isset($factura['ce'])) {
      $pdf->SetFillColor(0, 171, 72);
      $pdf->SetXY(0, $pdf->GetY() + 1);
      $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetXY(0, $pdf->GetY() + 1);
      $pdf->Cell(216, 4, "Comercio Exterior:", 0, 0, 'L', 1);

      $pdf->SetFont('helvetica','', 8);

      $pdf->SetXY(0, $pdf->GetY()+4);
      $pdf->SetAligns(array('L', 'L', 'L', 'L'));
      $pdf->SetWidths(array(35, 73, 35, 73));
      $pdf->Row(array(
            'Tipo Operacion', $factura['ce']->tipo_operacion,
            'Incoterm', $factura['ce']->incoterm
          ), false, true, null, 2, 1);
      $pdf->SetX(0);
      $pdf->Row(array(
            'Clave de pedimento', $factura['ce']->clave_pedimento,
            'Subdivision', $factura['ce']->subdivision
          ), false, true, null, 2, 1);
      $pdf->SetX(0);
      $pdf->Row(array(
            'Cer de origen', $factura['ce']->certificado_origen,
            'Observaciones', $factura['ce']->observaciones
          ), false, true, null, 2, 1);
      $pdf->SetX(0);
      $pdf->Row(array(
            '# cer de origen', $factura['ce']->num_certificado_origen,
            'Tipo Cambio USD', $factura['ce']->tipocambio_USD
          ), false, true, null, 2, 1);
      $pdf->SetX(0);
      $pdf->Row(array(
            '# Expt confiable', $factura['ce']->numero_exportador_confiable,
            'Total USD', $factura['ce']->total_USD
          ), false, true, null, 2, 1);

      $pdf->SetX(0);
      $pdf->Row(array(
            'Emisor CURP', $factura['ce']->emisor_curp,
            'Receptor Num Id Trib ', $factura['ce']->receptor_numregidtrib
          ), false, true, null, 2, 1);
      $pdf->SetX(0);
      $pdf->Row(array(
            '', '',
            'Receptor CURP', $factura['ce']->receptor_curp
          ), false, true, null, 2, 1);

      $pdf->SetXY(0, $pdf->GetY() + 1);
      $pdf->SetFont('helvetica','B', 8);
      $pdf->Cell(216, 4, "Destinatario", 0, 0, 'L', 1);
      $pdf->SetFont('helvetica','', 8);
      $pdf->SetXY(0, $pdf->GetY() + 4);
      $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'L', 'L', 'L'));
      $pdf->SetWidths(array(17, 27, 10, 27, 10, 31, 17, 77));
      $pdf->SetX(0);
      $pdf->Row(array(
            'Num Id Trib', $factura['ce']->destinatario->numregidtrib,
            'RFC', $factura['ce']->destinatario->rfc,
            'CURP', $factura['ce']->destinatario->curp,
            'Nombre', $factura['ce']->destinatario->nombre,
          ), false, true, null, 2, 1);
      $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'L'));
      $pdf->SetWidths(array(20, 64, 20, 56, 20, 36));
      $pdf->SetX(0);
      $pdf->Row(array(
            'Calle', $factura['ce']->destinatario->calle,
            'No. Exterior', $factura['ce']->destinatario->numero_exterior,
            'No. Interior', $factura['ce']->destinatario->numero_interior,
          ), false, true, null, 2, 1);
      $pdf->SetX(0);
      $pdf->Row(array(
            'Colonia', $factura['ce']->destinatario->colonia,
            'Localidad', $factura['ce']->destinatario->localidad,
            'Codigo Postal', $factura['ce']->destinatario->codigo_postal,
          ), false, true, null, 2, 1);
      $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'L', 'L', 'L'));
      $pdf->SetWidths(array(17, 37, 17, 37, 17, 27, 17, 47));
      $pdf->SetX(0);
      $pdf->Row(array(
            'Municipio', $factura['ce']->destinatario->municipio,
            'Estado', $factura['ce']->destinatario->estado,
            'Pais', $factura['ce']->destinatario->pais,
            'Referencia', $factura['ce']->destinatario->referencia,
          ), false, true, null, 2, 1);

      $aligns = array('C', 'C', 'C', 'C', 'C', 'C');
      $aligns2 = array('C', 'C', 'L', 'C', 'R', 'R');
      $aligns3 = array('L', 'L', 'L', 'L');
      $widths = array(36, 36, 36, 36, 36, 36);
      $widths3 = array(50, 50, 50, 50);
      $header = array('No Ident', 'Frac Aran', 'Cantidad', 'Unidad', 'Valor Unitario', 'Valor Dolares');
      $pdf->setY($pdf->GetY() + 1);
      $hay_prod_certificados = false;
      foreach($factura['ce']->mercancias as $key => $item)
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
          $item->noidentificacion,
          $item->fraccionar_ancelaria,
          $item->cantidad_aduana,
          $item->unidad_aduana,
          MyString::formatoNumero($item->valor_unitario_aduana, 2, '$', false),
          MyString::formatoNumero($item->valor_dolares, 2, '$', false),
        ), false, true, null, 2, 1);

        if (count($item->esp) > 0) {
          foreach($item->esp as $key2 => $esp)
          {
            if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
            {
              $pdf->AddPage();
            }

            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(0,0,0);

            $pdf->SetX(0);
            $pdf->SetAligns($aligns3);
            $pdf->SetWidths($widths3);
            $pdf->Row(array(
              'Marca: '.$esp->marca,
              'Modelo: '.$esp->modelo,
              'Sub Modelo: '.$esp->submodelo,
              'Numero Serie: '.$esp->numeroserie,
            ), false, true, null, 2, 1);
          }
        }
      }
    }


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
    $pdf->SetXY(10, $pdf->GetY()+3);

    //------------ IMAGEN CANDELADO --------------------

    if($factura['info']->status === 'ca'){
      $pdf->Image(APPPATH.'/images/cancelado.png', 20, 40, 190, 190, "PNG");
    }

    ////////////////////
    // pagare      //
    ////////////////////
    $pdf->SetWidths(array(190));
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
      $pdf->SetWidths(array(70));
      $pdf->SetXY(130, $yaux+3);
      $pdf->SetAligns(array('C'));
      $pdf->Row2(array('______________________________________________'), false, false);
      $pdf->SetXY(130, $pdf->GetY());
      $pdf->Row2(array('FIRMA'), false, false);

      $pdf->SetWidths(array(190));
      $pdf->SetAligns(array('L'));
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

    if (isset($factura['carta_porte']))
    {
      $pdf->AliasNbPages();
      $pdf->AddPage();

      $pdf->SetFont('helvetica', '', 9);
      $pdf->SetWidths(array(200));
      $pdf->SetAligns(array('L'));

      $pdf->Row(array('PRIMERA.- Para los efectos del presente contrato de transporte se denomina "Transportista" al que realiza el servicio de transportación y "Remitente" o "Expedidor" al usuario que contrate el servicio o remite la mercancía.'), false, false, false);
      $pdf->Row(array('SEGUNDA.- El "Remitente" o "Expedidor" es responsable de que la información proporcionada al "Transportista" sea veraz y que la documentación que entregue para efectos del transporte sea la correcta.'), false, false, false);
      $pdf->Row(array('TERCERA.- El "Remitente" o "Expedidor" debe declarar al "Transportista" el tipo de mercancía o efectos de que se trate, peso, medidas y/o número de la carga que entrega para su transporte y, en su caso, el valor de la misma. La carga que se entregue a granel será pesada por el "Transportista" en el primer punto donde haya báscula apropiada o, en su defecto, aforada en metros cúbicos con la conformidad del "Remitente" o "Expedidor".'), false, false, false);
      $pdf->Row(array('CUARTA.- Para efectos del transporte, el "Remitente" o "Expedidor" deberá entregar al "Transportista" los documentos que las leyes y reglamentos exijan para llevar a cabo el servicio, en caso de no cumplirse con estos requisitos el "Transportista" está obligado a rehusar el transporte de las mercancías.'), false, false, false);
      $pdf->Row(array('QUINTA.- Si por sospecha de falsedad en la declaración del contenido de un bulto el "Transportista" deseare proceder a su reconocimiento, podrá hacerlo ante testigos y con asistencia del "Remitente" o "Expedidor" o del consignatario. Si este último no concurriere, se solicitará la presencia de un inspector de la Secretaría de Comunicaciones y Transportes, y se levantará el acta correspondiente. El "Transportista" tendrá en todo caso, la obligación de dejar los bultos en el estado en que se encontraban antes del reconocimiento.'), false, false, false);
      $pdf->Row(array('SEXTA.- El "Transportista" deberá recoger y entregar la carga precisamente en los domicilios que señale el "Remitente" o "Expedidor", ajustándose a los términos y condiciones convenidos. El "Transportista" sólo está obligado a llevar la carga al domicilio del consignatario para su entrega una sola vez. Si ésta no fuera recibida, se dejará aviso de que la mercancía queda a disposición del interesado en las bodegas que indique el "Transportista".'), false, false, false);
      $pdf->Row(array('SÉPTIMA.- Si la carga no fuere retirada dentro de los 30 días hábiles siguientes a aquél en que hubiere sido puesta a disposición del consignatario, el "Transportista" podrá solicitar la venta en subasta pública con arreglo a lo que dispone el Código de Comercio.'), false, false, false);
      $pdf->Row(array('OCTAVA.- El "Transportista" y el "Remitente" o "Expedidor" negociarán libremente el precio del servicio, tomando en cuenta su tipo, característica de los embarques, volumen, regularidad, clase de carga y sistema de pago.'), false, false, false);
      $pdf->Row(array('NOVENA.- Si el "Remitente" o "Expedidor" desea que el "Transportista" asuma la responsabilidad por el valor de las mercancías o efectos que él declare y que cubra toda clase de riesgos, inclusive los derivados de caso fortuito o de fuerza mayor, las partes deberán convenir un cargo adicional, equivalente al valor de la prima del seguro que se contrate, el cual se deberá expresar en la Carta de Porte.'), false, false, false);
      $pdf->Row(array('DÉCIMA.- Cuando el importe del flete no incluya el cargo adicional, la responsabilidad del "Transportista" queda expresamente limitada a la cantidad equivalente a 15 días del salario mínimo vigente en el Distrito Federal por tonelada o cuando se trate de embarques cuyo peso sea mayor de 200 kg., pero menor de 1000 kg; y a 4 días de salario mínimo por remesa cuando se trate de embarques con peso hasta de 200 kg.'), false, false, false);
      $pdf->Row(array('DÉCIMA PRIMERA.- El precio del transporte deberá pagarse en origen, salvo convenio entre las partes de pago en destino. Cuando el transporte se hubiere concertado "Flete por Cobrar", la entrega de las mercancías o efectos se hará contra el pago del flete y el "Transportista" tendrá derecho a retenerlos mientras no se le cubra el precio convenido.'), false, false, false);
      $pdf->Row(array('DÉCIMA SEGUNDA.- Si al momento de la entrega resultare algún faltante o avería, el consignatario deberá hacerla constar en ese acto en la Carta de Porte y formular su reclamación por escrito al "Transportista", dentro de las 24 horas siguientes.'), false, false, false);
      $pdf->Row(array('DÉCIMA TERCERA.- El "Transportista" queda eximido de la obligación de recibir mercancías o efectos para su transporte, en los siguientes casos:'), false, false, false);
      $pdf->Row(array('a) Cuando se trate de carga que por su naturaleza, peso, volumen, embalaje defectuoso o cualquier otra circunstancia no pueda transportarse sin destruirse o sin causar daño a los demás artículos o al material rodante, salvo que la empresa de que se trate tenga el equipo adecuado.'), false, false, false);
      $pdf->Row(array('b) Las mercancías cuyo transporte haya sido prohibido por disposiciones legales o reglamentarias. Cuando tales disposiciones no prohíban precisamente el transporte de determinadas mercancías, pero sí ordenen la presentación de ciertos documentos para que puedan ser transportadas, el "Remitente" o "Expedidor" estará obligado a entregar al "Transportista" los documentos correspondientes.'), false, false, false);
      $pdf->Row(array('DÉCIMA CUARTA.- Los casos no previstos en las presentes condiciones y las quejas derivadas de su aplicación se someterán por la vía administrativa a la Secretaría de Comunicaciones y Transportes.'), false, false, false);
      $pdf->Row(array('DÉCIMA QUINTA.- Para el caso de que el "Remitente" o "Expedidor" contrate carro por entero, este aceptará la responsabilidad solidaria para con el "Transportista" mediante la figura de la corresponsabilidad que contempla el artículo 10 del Reglamento Sobre el Peso, Dimensiones y Capacidad de los Vehículos de Autotransporte que Transitan en los Caminos y Puentes de Jurisdicción Federal, por lo que el "Remitente" o "Expedidor" queda obligado a verificar que la carga y el vehículo que la transporta, cumplan con el peso y dimensiones máximas establecidos en la NOM-012 SCT-2-2014.'), false, false, false);
    }

    if ($path) {
      $pdf->Output($path.'Factura.pdf', 'F');
    } else {
      // Actualiza el # de impresion
      $this->db->update('facturacion', ['no_impresiones' => $factura['info']->no_impresiones+1], "id_factura = ".$factura['info']->id_factura);

      $file_name = 'Factura'.rand(0, 1000).'.pdf';
      if (!isset($data_chofer->url_licencia{0}) || !isset($data_chofer->url_ife{0})) {
        $pdf->Output('Factura', 'I');
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

        $pdf->merge('browser', 'Factura.pdf');
        // unlink(APPPATH.'media/temp/'.$file_name);
      }
    }
  }

  public function generaFacturaPdf33($factura, $path = null)
  {
    $this->load->library('cfdi');
    include_once(APPPATH.'libraries/phpqrcode/qrlib.php');
    $this->load->model('nomina_catalogos_model');

    // $factura = $this->getInfoFactura($idFactura);
    $factura['info']->cfdi_ext = json_decode($factura['info']->cfdi_ext);

    $this->load->model('documentos_model');
    $manifiesto_chofer = $this->documentos_model->getJsonDataDocus($factura['info']->id_factura, 1);
    if (isset($manifiesto_chofer->chofer_id)) {
      $data_chofer = $this->db->query("SELECT * FROM choferes WHERE id_chofer = {$manifiesto_chofer->chofer_id}")->row();
    }

    // echo "<pre>";
    //   var_dump($factura);
    // echo "</pre>";exit;

    $this->load->model('catalogos33_model');
    $metodosPago       = new MetodosPago();
    $formaPago         = new FormaPago();
    $usoCfdi           = new UsoCfdi();
    $tipoDeComprobante = new TipoDeComprobante();
    $regimenFiscal     = $this->catalogos33_model->regimenFiscales($factura['info']->cfdi_ext->emisor->regimenFiscal);

    $xml = simplexml_load_string(str_replace(array('cfdi:', 'tfd:'), '', $factura['info']->xml));
    if($xml === false)
      return false;
    // echo "<pre>";
    //   var_dump($factura, $xml);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    if (!empty($_GET['lang']) && $_GET['lang'] === 'en') {
      $this->lang->load('factura', 'english');
    }

    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    $pdf->show_head = false;

    $pdf->AliasNbPages();
    $pdf->AddPage();

    $pdf->SetFont('Arial','B', 70);
    $pdf->SetTextColor(160,160,160);
    $pdf->RotatedText(65, 130, ($factura['info']->no_impresiones==0? 'ORIGINAL': $this->lang->line('factura_copy', 'COPIA').' #'.$factura['info']->no_impresiones), 45);

    $pdf->SetXY(0, 0);
    /////////////////////////////////////
    // Folio Fisca, CSD, Lugar y Fecha //
    /////////////////////////////////////

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(0, $pdf->GetY() + 2);
    $pdf->Cell(108, 4, "{$this->lang->line('factura_folio_fiscal', 'Folio Fiscal')}:", 0, 0, 'R', 1);

    $titulo_comprobante = '                 '.($factura['info']->condicion_pago=='co'? $this->lang->line('factura_factura_contado', 'Factura al contado'): $this->lang->line('factura_factura_credito', 'Factura a crédito'));
    if($factura['info']->id_nc != '')
      $titulo_comprobante = '                 '.$this->lang->line('factura_nota_credito', 'Nota de Crédito');
    elseif($factura['info']->id_abono_factura != '')
      $titulo_comprobante = '                 '.$this->lang->line('factura_abono_cliente', 'Abono del Cliente');
    elseif (isset($factura['carta_porte']))
      $titulo_comprobante = '                 '.$this->lang->line('factura_carta_porte', 'Carta Porte - Factura');
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Cell(50, 4, $titulo_comprobante.':  '.($factura['info']->serie.$factura['info']->folio) , 0, 0, 'L', 1);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(0, $pdf->GetY() + 6);
    $pdf->Cell(108, 4, $xml->Complemento->TimbreFiscalDigital[0]['UUID'], 0, 0, 'C', 0);

    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(0, $pdf->GetY() + 4);
    $pdf->Cell(48, 4, "{$this->lang->line('factura_fecha_hr_impresion', 'Fecha y hora de impresión')}:", 0, 0, 'L', 1);
    $pdf->SetXY(48, $pdf->GetY());
    $pdf->Cell(60, 4, "{$this->lang->line('factura_no_certificado_csd', 'No de Serie del Certificado del CSD')}:", 0, 0, 'R', 1);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica','', 9);
    $pdf->SetXY(0, $pdf->GetY() + 4);
    $pdf->Cell(48, 4, MyString::fechaATexto(date("Y-m-d")).' '.date("H:i:s"), 0, 0, 'L', 0);
    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetXY(48, $pdf->GetY());
    $pdf->Cell(60, 4, $factura['info']->no_certificado, 0, 0, 'R', 0);

    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(0, $pdf->GetY() + 4);
    $pdf->Cell(108, 4, "{$this->lang->line('factura_lugar_fecha_emision', 'Lugar. fecha y hora de emisión')}:", 0, 0, 'R', 1);

    $pdf->SetFont('helvetica','', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(0, $pdf->GetY() + 4);

    $municipio   = strtoupper($factura['info']->cfdi_ext->emisor->municipio);
    $estado = strtoupper($factura['info']->cfdi_ext->emisor->estado);
    $fecha = explode('T', $factura['info']->cfdi_ext->fecha);
    $fecha = MyString::fechaATexto($fecha[0]);

    $pdf->Cell(108, 4, "{$municipio}, {$estado} ({$factura['info']->cfdi_ext->emisor->cp}) | {$fecha}", 0, 0, 'R', 0);


    // $pdf->SetXY(30, 2);

    //////////////////////////
    // Rfc y Regimen Fiscal //
    //////////////////////////

    // 0, 171, 72 = verde

    $pdf->SetFont('helvetica','B', 9);
    // $pdf->SetFillColor(0, 171, 72);
    $pdf->SetTextColor(255, 255, 255);


    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(0, $pdf->GetY() + 4);
    $pdf->Cell(108, 4, "{$this->lang->line('factura_emisor', 'Emisor')}:", 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(0, $pdf->GetY() + 4);

    $pdf->SetX(0);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(19, 93));
    $pdf->Row(array($this->lang->line('factura_emisor_rfc', 'RFC').':', $factura['info']->cfdi_ext->emisor->rfc), false, false, null, 2, 1);
    $pdf->SetWidths(array(19, 196));
    $pdf->SetX(0);
    $pdf->Row(array($this->lang->line('factura_emisor_nombre', 'NOMBRE').':', $factura['info']->cfdi_ext->emisor->nombreFiscal), false, false, null, 2, 1);
    $pdf->SetX(0);
    $pdf->Row(array($this->lang->line('factura_emisor_domicilio', 'DOMICILIO').':', $factura['info']->cfdi_ext->emisor->calle.' No. '.$factura['info']->cfdi_ext->emisor->noExterior.
          ((isset($factura['info']->cfdi_ext->emisor->noInterior)) ? ' Int. '.$factura['info']->cfdi_ext->emisor->noInterior : '') ), false, false, null, 2, 1);
    $pdf->SetWidths(array(19, 83, 19, 83));
    $pdf->SetX(0);
    $pdf->Row(array($this->lang->line('factura_emisor_colonia', 'COLONIA').':', $factura['info']->cfdi_ext->emisor->colonia,
        $this->lang->line('factura_emisor_localidad', 'LOCALIDAD').':', $factura['info']->cfdi_ext->emisor->localidad), false, false, null, 2, 1);
    $pdf->SetWidths(array(19, 46, 19, 40, 11, 46, 11, 30));
    $pdf->SetX(0);
    $pdf->Row(array($this->lang->line('factura_emisor_municipio', 'MUNICIPIO').':', $factura['info']->cfdi_ext->emisor->municipio,
        $this->lang->line('factura_emisor_estado', 'ESTADO').':', $factura['info']->cfdi_ext->emisor->estado,
        $this->lang->line('factura_emisor_pais', 'PAIS').':', $factura['info']->cfdi_ext->emisor->pais,
        $this->lang->line('factura_emisor_cp', 'CP').':', $factura['info']->cfdi_ext->emisor->cp), false, false, null, 2, 1);

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
    $pdf->Cell(108, 4, "{$this->lang->line('factura_emisor_regimen_fiscal', 'Régimen Fiscal')}: ", 0, 0, 'R', 1);

    $regimen_fiscal = "{$regimenFiscal->c_RegimenFiscal} - {$regimenFiscal->nombre} ";
    $uso_cfdi = $usoCfdi->search($factura['info']->cfdi_ext->usoCfdi);
    $tipo_comprobante = $tipoDeComprobante->search($factura['info']->cfdi_ext->tipoDeComprobante);

    $pdf->SetFont('helvetica','', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(109, $pdf->GetY() + 4);
    $pdf->MultiCell(108, 4, $regimen_fiscal, 0, 'R', 0);
    $pdf->SetXY(119, $pdf->GetY() + 1);
    $pdf->MultiCell(98, 4, "{$this->lang->line('factura_emisor_tipo_compro', 'TIPO DE COMPROBANTE')} : {$tipo_comprobante['key']} - {$tipo_comprobante['value']} ", 0, 'R', 0);
    $pdf->SetXY(119, $pdf->GetY() + 1);
    $pdf->MultiCell(98, 4, "{$this->lang->line('factura_emisor_uso', 'USO')}: {$uso_cfdi['key']} - {$uso_cfdi['value']} ", 0, 'R', 0);

    //////////////////
    // Datos Receptor //
    //////////////////
    $pdf->setY($end_y);
    $domicilioReceptor = '';
    $domicilioReceptor .= (isset($factura['info']->cfdi_ext->receptor->calle) ? $factura['info']->cfdi_ext->receptor->calle : '');
    $domicilioReceptor .= (isset($factura['info']->cfdi_ext->receptor->noExterior) ? ' #'.$factura['info']->cfdi_ext->receptor->noExterior : '');
    $domicilioReceptor .= (isset($factura['info']->cfdi_ext->receptor->noInterior)) ? ' Int. '.$factura['info']->cfdi_ext->receptor->noInterior : '';
    $domicilioReceptor .= (isset($factura['info']->cfdi_ext->receptor->colonia) ? ', '.$factura['info']->cfdi_ext->receptor->colonia : '');
    $domicilioReceptor .= (isset($factura['info']->cfdi_ext->receptor->localidad) ? ', '.$factura['info']->cfdi_ext->receptor->localidad : '');
    $domicilioReceptor .= (isset($factura['info']->cfdi_ext->receptor->municipio)) ? ', '.$factura['info']->cfdi_ext->receptor->municipio : '';
    $domicilioReceptor .= (isset($factura['info']->cfdi_ext->receptor->estado) ? ', '.$factura['info']->cfdi_ext->receptor->estado : '');

    $pdf->SetFillColor(0, 171, 72);
    $pdf->SetXY(0, $pdf->GetY() + 4);
    $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(0, $pdf->GetY() + 1);
    $pdf->Cell(216, 4, "{$this->lang->line('factura_receptor', 'Receptor')}:", 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(0, $pdf->GetY() + 4);

    $pdf->SetX(0);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(19, 93));
    $pdf->Row(array($this->lang->line('factura_emisor_rfc', 'RFC').':', $factura['info']->cfdi_ext->receptor->rfc), false, false, null, 2, 1);
    $pdf->SetWidths(array(19, 196));
    $pdf->SetX(0);
    $pdf->Row(array($this->lang->line('factura_emisor_nombre', 'NOMBRE').':', $factura['info']->cfdi_ext->receptor->nombreFiscal), false, false, null, 2, 1);
    $pdf->SetX(0);
    $pdf->Row(array($this->lang->line('factura_emisor_domicilio', 'DOMICILIO').':', (isset($factura['info']->cfdi_ext->receptor->calle) ? $factura['info']->cfdi_ext->receptor->calle : '').
              ' No. '.(isset($factura['info']->cfdi_ext->receptor->noExterior) ? $factura['info']->cfdi_ext->receptor->noExterior : '').
              ((isset($factura['info']->cfdi_ext->receptor->noInterior)) ? ' Int. '.$factura['info']->cfdi_ext->receptor->noInterior : '') ), false, false, null, 2, 1);
    $pdf->SetWidths(array(19, 83, 19, 83));
    $pdf->SetX(0);
    $pdf->Row(array($this->lang->line('factura_emisor_colonia', 'COLONIA').':', (isset($factura['info']->cfdi_ext->receptor->colonia) ? $factura['info']->cfdi_ext->receptor->colonia : ''),
              $this->lang->line('factura_emisor_localidad', 'LOCALIDAD').':', (isset($factura['info']->cfdi_ext->receptor->localidad) ? $factura['info']->cfdi_ext->receptor->localidad : '')), false, false, null, 2, 1);
    $pdf->SetWidths(array(19, 65, 11, 65, 11, 40));
    $pdf->SetX(0);
    $pdf->Row(array($this->lang->line('factura_emisor_estado', 'ESTADO').':', (isset($factura['info']->cfdi_ext->receptor->estado) ? $factura['info']->cfdi_ext->receptor->estado : ''),
            $this->lang->line('factura_emisor_pais', 'PAIS').':', (isset($factura['info']->cfdi_ext->receptor->pais) ? $factura['info']->cfdi_ext->receptor->pais : ''),
            $this->lang->line('factura_emisor_cp', 'CP').':', (isset($factura['info']->cfdi_ext->receptor->cp) ? $factura['info']->cfdi_ext->receptor->cp : '') ), false, false, null, 2, 1);

    if (isset($factura['carta_porte']))
    {
      // Remitente
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 171, 72);
      $pdf->SetXY(0, $pdf->GetY() + 4);
      $y_aux = $pdf->GetY();
      $pdf->Cell(108, 4, "{$this->lang->line('factura_remitente_remitente', 'Remitente')}:", 0, 0, 'L', 1);

      $pdf->SetFont('helvetica','', 8);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetXY(0, $pdf->GetY() + 4);

      $pdf->SetX(0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(19, 93));
      $pdf->Row(array($this->lang->line('factura_emisor_rfc', 'RFC').':', $factura['carta_porte']['remitente'][0]->rfc), false, false, null, 2, 1);
      $pdf->SetWidths(array(19, 196));
      $pdf->SetX(0);
      $pdf->Row(array($this->lang->line('factura_emisor_nombre', 'NOMBRE').':', $factura['carta_porte']['remitente'][0]->nombre), false, false, null, 2, 1);
      $pdf->SetX(0);
      $pdf->Row(array($this->lang->line('factura_emisor_domicilio', 'DOMICILIO').':', $factura['carta_porte']['remitente'][0]->direccion ), false, false, null, 2, 1);

      // $pdf->SetAligns(array('L', 'L', 'L', 'L'));
      $pdf->SetWidths(array(80, 50, 35, 45));
      $pdf->SetX(0);
      $pdf->Row(array(
        $this->lang->line('factura_remitente_operador', 'OPERADOR').': ' . $factura['carta_porte']['remitente'][0]->chofer,
        $this->lang->line('factura_remitente_marca', 'MARCA').': ' . $factura['carta_porte']['remitente'][0]->marca,
        $this->lang->line('factura_remitente_modelo', 'MODELO').':' . $factura['carta_porte']['remitente'][0]->modelo,
        $this->lang->line('factura_remitente_placas', 'PLACAS').':' . $factura['carta_porte']['remitente'][0]->placas
      ), false, false, null, 2, 1);

      $end_y = $pdf->GetY();

      // Destinatario
      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetXY(110, $y_aux);
      $pdf->Cell(216, 4, "{$this->lang->line('factura_remitente_remitente', 'Destinatario')}:", 0, 0, 'L', 1);

      $pdf->SetFont('helvetica','', 8);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetXY(110, $pdf->GetY() + 4);

      $pdf->SetX(110);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(19, 93));
      $pdf->Row(array($this->lang->line('factura_emisor_rfc', 'RFC').':', $factura['carta_porte']['destinatario'][0]->rfc), false, false, null, 2, 1);
      $pdf->SetWidths(array(19, 196));
      $pdf->SetX(110);
      $pdf->Row(array($this->lang->line('factura_emisor_nombre', 'NOMBRE').':', $factura['carta_porte']['destinatario'][0]->nombre), false, false, null, 2, 1);
      $pdf->SetX(110);
      $pdf->Row(array($this->lang->line('factura_emisor_domicilio', 'DOMICILIO').':', $factura['carta_porte']['destinatario'][0]->direccion ), false, false, null, 2, 1);
    }

    ///////////////
    // Productos //
    ///////////////

    $pdf->SetFillColor(0, 171, 72);
    $pdf->SetXY(0, $pdf->GetY() + 5);
    $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

    $pdf->SetXY(0, $pdf->GetY());
    $aligns = array('C', 'C', 'C', 'C', 'C', 'C', 'C');
    $aligns2 = array('C', 'C', 'L', 'L', 'C', 'R', 'R');
    $widths = array(25, 35, 16, 19, 61, 30, 30);
    $header = array(
      $this->lang->line('factura_productos_cantidad', 'Cantidad'),
      $this->lang->line('factura_productos_unidad_medida', 'Unidad de Medida'),
      $this->lang->line('factura_productos_c_unidad', 'C. Unidad'),
      $this->lang->line('factura_productos_c_prodserv', 'C. ProdServ'),
      $this->lang->line('factura_productos_descripcion', 'Descripcion'),
      $this->lang->line('factura_productos_precio_unitario', 'Precio Unitario'),
      $this->lang->line('factura_productos_importe', 'Importe'),
    );

    $pdf->limiteY = 250;

    $pdf->setY($pdf->GetY() + 1);
    $hay_prod_certificados = false;
    foreach($factura['productos'] as $key => $item)
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
        if ( GastosProductos::searchGastosProductos($item->id_clasificacion) )
          $printRow = false;
      }

      if ($item->certificado === 't')
        $hay_prod_certificados = true;

      $descripcion_ext = strlen($item->descripcion2)>0? " ({$item->descripcion2})": '';

      if($printRow)
        $pdf->Row(array(
          MyString::formatoNumero($item->cantidad, 2, ''),
          $item->unidad,
          $item->clave_unidad,
          $item->clave_prod_serv,
          $this->cfdi->replaceSpecialChars($item->descripcion.$descripcion_ext, true),
          // $item->certificado === 't' ? 'Certificado' : '',
          MyString::formatoNumero( ($item->precio_unitario/($factura['info']->tipo_cambio>0? $factura['info']->tipo_cambio: 1)), 2, '$', false),
          MyString::formatoNumero( ($item->importe/($factura['info']->tipo_cambio>0? $factura['info']->tipo_cambio: 1)), 2, '$', false),
        ), false, true, null, 2, 1);
    }

    /////////////
    // Totales //
    /////////////

    if($pdf->GetY() + 30 >= $pdf->limiteY) //salta de pagina si exede el max
        $pdf->AddPage();

    $h = 20;
    $h = $h - ($factura['info']->retencion_iva == 0 ? 5 : 0);
    $h += 6;

    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetXY(0, $pdf->GetY() + 1);
    $pdf->Cell(156, $h, "", 1, 0, 'L', 1);

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetXY(1, $pdf->GetY() + 1);
    $pdf->Cell(154, 4, "{$this->lang->line('factura_totales_total_letra', 'Total con letra')}:", 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','', 9);
    $pdf->SetXY(90, $pdf->GetY());
    $pdf->Cell(39, 4, "{$this->lang->line('factura_totales_moneda', 'Moneda')}: ".$factura['info']->moneda, 0, 0, 'L', 1);
    $pdf->SetXY(115, $pdf->GetY());
    $pdf->Cell(39, 4, "{$this->lang->line('factura_totales_tipo_cambio', 'Tipo de Cambio')}: ".MyString::formatoNumero($factura['info']->tipo_cambio, 4), 0, 0, 'L', 1);

    $pdf->SetFont('helvetica', '', 10);
    $pdf->SetXY(0, $pdf->GetY() + 4);
    $pdf->MultiCell(156, 6, $factura['info']->total_letra, 0, 'C', 0);

    $pdf->Line(1, $pdf->GetY(), 200, $pdf->GetY());

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetXY(1, $pdf->GetY()+1);
    $frmPago = $formaPago->search($factura['info']->cfdi_ext->formaDePago);
    $pdf->Cell(91, 4, "{$this->lang->line('factura_totales_forma_pago', 'Forma de Pago')}: {$frmPago['key']} - {$frmPago['value']}", 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetXY(91, $pdf->GetY());
    $text_condicionpago = $this->lang->line('factura_totales_condicion_pago', 'Condición de Pago').': '.($factura['info']->condicion_pago=='co'? 'Contado': 'Credito');
    if($factura['info']->id_abono_factura != '')
      $text_condicionpago = '';
    $pdf->Cell(65, 4, $text_condicionpago, 0, 0, 'L', 1);

    $pdf->SetFont('helvetica','B', 9);
    $pdf->SetXY(1, $pdf->GetY()+4.5);
    $metPago = $metodosPago->search($factura['info']->cfdi_ext->metodoDePago);
    $pdf->Cell(148, 4, "{$this->lang->line('factura_totales_metodo_pago', 'Metodo de Pago')}: {$metPago['key']} - {$metPago['value']}", 0, 0, 'L', 1);


    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetXY(156, $pdf->GetY() - 16);
    $pdf->Cell(30, 5, "{$this->lang->line('factura_totales_subtotal', 'Subtotal')}", 1, 0, 'C', 1);

    $pdf->SetXY(186, $pdf->GetY());
    $pdf->Cell(30, 5, MyString::formatoNumero(($factura['info']->subtotal/($factura['info']->tipo_cambio>0? $factura['info']->tipo_cambio: 1)), 2, '$', false), 1, 0, 'R', 1);

    // Pinta traslados, retenciones

    $pdf->SetXY(156, $pdf->GetY() + 5);
    $pdf->Cell(30, 5, "{$this->lang->line('factura_totales_iva', 'IVA')}", 1, 0, 'C', 1);

    $pdf->SetXY(186, $pdf->GetY());
    $pdf->Cell(30, 5,MyString::formatoNumero(($factura['info']->importe_iva/($factura['info']->tipo_cambio>0? $factura['info']->tipo_cambio: 1)), 2, '$', false), 1, 0, 'R', 1);

    if ($factura['info']->ieps > 0)
    {
      $pdf->SetXY(156, $pdf->GetY() + 5);
      $pdf->Cell(30, 5, "{$this->lang->line('factura_totales_ieps', 'IEPS')}", 1, 0, 'C', 1);

      $pdf->SetXY(186, $pdf->GetY());
      $pdf->Cell(30, 5,MyString::formatoNumero(($factura['info']->ieps/($factura['info']->tipo_cambio>0? $factura['info']->tipo_cambio: 1)), 2, '$', false), 1, 0, 'R', 1);
    }

    if ($factura['info']->retencion_iva > 0)
    {
      $pdf->SetXY(156, $pdf->GetY() + 5);
      $pdf->Cell(30, 5, "{$this->lang->line('factura_totales_iva_retenido', 'IVA Retenido')}", 1, 0, 'C', 1);

      $pdf->SetXY(186, $pdf->GetY());
      $pdf->Cell(30, 5,MyString::formatoNumero(($factura['info']->retencion_iva/($factura['info']->tipo_cambio>0? $factura['info']->tipo_cambio: 1)), 2, '$', false), 1, 0, 'R', 1);
    }

    $pdf->SetXY(156, $pdf->GetY() + 5);
    $pdf->Cell(30, 5, "{$this->lang->line('factura_totales_total', 'TOTAL')}", 1, 0, 'C', 1);

    $pdf->SetXY(186, $pdf->GetY());
    $pdf->Cell(30, 5,MyString::formatoNumero(($factura['info']->total/($factura['info']->tipo_cambio>0? $factura['info']->tipo_cambio: 1)), 2, '$', false), 1, 0, 'R', 1);

    ///////////////////
    // Observaciones //
    ///////////////////

    $pdf->SetXY(0, $pdf->GetY() + 10);

    $width = (($pdf->GetStringWidth($factura['info']->observaciones) / 216) * 8) + 9;

    if($pdf->GetY() + $width >= $pdf->limiteY) //salta de pagina si exede el max
        $pdf->AddPage();

    if ( ! empty($factura['info']->observaciones))
    {
        $pdf->SetXY(0, $pdf->GetY() + 5);
        $pdf->SetFont('helvetica','B', 10);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(216));
        $pdf->Row(array($this->lang->line('factura_otros_observaciones', 'Observaciones')), true);

        $pdf->SetFont('helvetica','', 9);
        $pdf->SetXY(0, $pdf->GetY());
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(216));
        $pdf->Row(array($factura['info']->observaciones), true, 1);
    }

    if($hay_prod_certificados)
    {
      if($pdf->GetY() + 10 >= $pdf->limiteY) //salta de pagina si exede el max
          $pdf->AddPage();

      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(10, $pdf->GetY()+5);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array('GGN4052852866927 PRODUCTO CERTIFICADO'), false, 0);
    }

    ////////////////////////
    // CFDI Relacionados //
    ///////////////////////
    if (isset($factura['info']->cfdi_ext->cfdiRelacionados)) {
      $tipoRelacion = new TipoRelacion();
      $tipo_rel = $tipoRelacion->search($factura['info']->cfdi_ext->cfdiRelacionados->tipoRelacion);
      $pdf->SetFillColor(0, 171, 72);
      $pdf->SetXY(0, $pdf->GetY() + 1);
      $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetXY(0, $pdf->GetY() + 1);
      $pdf->Cell(216, 4, "{$this->lang->line('factura_otros_cfdi_relacionados', 'CFDI Relacionados')}:", 0, 0, 'L', 1);

      $pdf->SetFont('helvetica','', 8);

      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(216));

      $pdf->SetXY(0, $pdf->GetY()+4);
      $pdf->Row(array("{$this->lang->line('factura_otros_tipo_relacion', 'Tipo de Relación')}: {$tipo_rel['key']} - {$tipo_rel['value']}" ), false, true, null, 2, 1);

      foreach ($factura['info']->cfdi_ext->cfdiRelacionados->cfdiRelacionado as $key => $value) {
        $pdf->SetXY(0, $pdf->GetY());
        $pdf->Row(array("UUID: {$value->uuid}"), false, true, null, 2, 1);
      }
    }


    ////////////////////////
    // Comercio Exterior //
    ///////////////////////
    if (isset($factura['ce'])) {
      $ceExtras = json_decode($factura['ce']->extras);
      $pdf->SetFillColor(0, 171, 72);
      $pdf->SetXY(0, $pdf->GetY() + 1);
      $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

      $pdf->SetFont('helvetica','B', 10);
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 0, 0);

      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(216));
      $pdf->SetXY(0, $pdf->GetY() + 1);
      $pdf->Row(array(
        "{$this->lang->line('factura_ce', 'Comercio Exterior')} V{$ceExtras->version}"
      ), false, true, null, 2, 1);

      $pdf->SetFont('helvetica','', 8);

      $pdf->SetXY(0, $pdf->GetY()+4);
      $pdf->SetAligns(array('L', 'L', 'L', 'L'));
      $pdf->SetWidths(array(35, 73, 35, 73));
      $pdf->Row(array(
            $this->lang->line('factura_ce_motivo_traslado', 'Motivo traslado'), $factura['ce']->motivo_traslado,
            $this->lang->line('factura_ce_incoterm', 'Incoterm'), $factura['ce']->incoterm
          ), false, true, null, 2, 1);
      $pdf->SetX(0);
      $pdf->Row(array(
            $this->lang->line('factura_ce_tipo_operacion', 'Tipo Operacion'), $factura['ce']->tipo_operacion,
            $this->lang->line('factura_ce_subdivision', 'Subdivision'), $factura['ce']->subdivision
          ), false, true, null, 2, 1);
      $pdf->SetX(0);
      $pdf->Row(array(
            $this->lang->line('factura_ce_clave_pedimento', 'Clave de pedimento'), $factura['ce']->clave_pedimento,
            $this->lang->line('factura_ce_observaciones', 'Observaciones'), $factura['ce']->observaciones
          ), false, true, null, 2, 1);
      $pdf->SetX(0);
      $pdf->Row(array(
            $this->lang->line('factura_ce_cer_origen', 'Cer de origen'), $factura['ce']->certificado_origen,
            $this->lang->line('factura_ce_tipo_cambio_usd', 'Tipo Cambio USD'), $factura['ce']->tipocambio_USD
          ), false, true, null, 2, 1);
      $pdf->SetX(0);
      $pdf->Row(array(
            $this->lang->line('factura_ce_cer_origen', '# cer de origen'), $factura['ce']->num_certificado_origen,
            $this->lang->line('factura_ce_total_usd', 'Total USD'), $factura['ce']->total_USD
          ), false, true, null, 2, 1);
      $pdf->SetX(0);
      $pdf->Row(array(
            $this->lang->line('factura_ce_expt_confiable', '# Expt confiable'), $factura['ce']->numero_exportador_confiable,
            '', ''
          ), false, true, null, 2, 1);

      if ($pdf->GetY()+10 >= $pdf->limiteY) {
        $pdf->AddPage();
      }
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(216));
      $pdf->SetXY(0, $pdf->GetY() + 1);
      $pdf->SetFont('helvetica','B', 10);
      $pdf->Row(array(
        "{$this->lang->line('factura_ce_emisor', 'Emisor')} ".(!empty($ceExtras->emisor->curp)? "(CURP: {$ceExtras->emisor->curp})": '')
      ), false, true, null, 2, 1);
      $pdf->SetFont('helvetica','', 8);
      $pdf->SetXY(0, $pdf->GetY() + 4);
      $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'L'));
      $pdf->SetWidths(array(20, 64, 20, 56, 20, 36));
      $pdf->SetX(0);
      $pdf->Row(array(
            $this->lang->line('factura_ce_calle', 'Calle'), $ceExtras->emisor->domicilio->calle,
            $this->lang->line('factura_ce_no_exterior', 'No. Exterior'), $ceExtras->emisor->domicilio->numeroExterior,
            $this->lang->line('factura_ce_no_interior', 'No. Interior'), $ceExtras->emisor->domicilio->numeroInterior,
          ), false, true, null, 2, 1);
      $pdf->SetX(0);

      $this->load->model('clocalidad_model');
      $localidad = $this->clocalidad_model->getLocalidadKey($ceExtras->emisor->domicilio->localidad, $ceExtras->emisor->domicilio->estado);
      $pdf->Row(array(
            $this->lang->line('factura_ce_colonia', 'Colonia'), $ceExtras->emisor->domicilio->colonia,
            $this->lang->line('factura_ce_localidad', 'Localidad'), ($localidad? $localidad."({$ceExtras->emisor->domicilio->localidad})" : $ceExtras->emisor->domicilio->localidad),
            $this->lang->line('factura_ce_codigo', 'Codigo Postal'), $ceExtras->emisor->domicilio->codigoPostal,
          ), false, true, null, 2, 1);
      $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'L', 'L', 'L'));
      $pdf->SetWidths(array(17, 37, 17, 37, 17, 27, 17, 47));
      $pdf->SetX(0);

      $this->load->model('cmunicipio_model');
      $municipio = $this->cmunicipio_model->getMunicipioKey($ceExtras->emisor->domicilio->municipio, $ceExtras->emisor->domicilio->estado);
      $pdf->Row(array(
            $this->lang->line('factura_ce_municipio', 'Municipio'), ($municipio? $municipio."({$ceExtras->emisor->domicilio->municipio})" : $ceExtras->emisor->domicilio->municipio),
            $this->lang->line('factura_ce_estado', 'Estado'), $ceExtras->emisor->domicilio->estado,
            $this->lang->line('factura_ce_pais', 'Pais'), $ceExtras->emisor->domicilio->pais,
            $this->lang->line('factura_ce_referencia', 'Referencia'), '',
          ), false, true, null, 2, 1);

      if ($ceExtras->propietario[0]->numRegIdTrib != '') {
        $pdf->SetFont('helvetica','B', 8);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(216));
        $pdf->SetXY(0, $pdf->GetY() + 1);
        $pdf->Row(array(
          "Propietario "
        ), false, true, null, 2, 1);

        $pdf->SetFont('helvetica','', 8);
        $pdf->SetXY(0, $pdf->GetY() + 4);
        $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'L', 'L', 'L'));
        $pdf->SetWidths(array(17, 64, 17, 118));
        $pdf->SetX(0);
        $pdf->Row(array(
              $this->lang->line('factura_ce_num_trib', 'Num Id Trib'), $ceExtras->propietario[0]->numRegIdTrib,
              $this->lang->line('factura_ce_nombre', 'Nombre'), $ceExtras->propietario[0]->residenciaFiscal,
            ), false, true, null, 2, 1);
      }

      $pdf->SetFont('helvetica','B', 8);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(216));
      $pdf->SetXY(0, $pdf->GetY() + 1);
      $pdf->Row(array(
        "{$this->lang->line('factura_ce_receptor', 'Receptor')} ".(!empty($ceExtras->receptor->numRegIdTrib)? "(Num Id Trib: {$ceExtras->receptor->numRegIdTrib})": '')
      ), false, true, null, 2, 1);

      $pdf->SetFont('helvetica','', 8);
      $pdf->SetXY(0, $pdf->GetY() + 4);
      $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'L'));
      $pdf->SetWidths(array(20, 64, 20, 56, 20, 36));
      $pdf->SetX(0);
      $pdf->Row(array(
            $this->lang->line('factura_ce_calle', 'Calle'), $ceExtras->receptor->domicilio->calle,
            $this->lang->line('factura_ce_no_exterior', 'No. Exterior'), $ceExtras->receptor->domicilio->numeroExterior,
            $this->lang->line('factura_ce_no_interior', 'No. Interior'), $ceExtras->receptor->domicilio->numeroInterior,
          ), false, true, null, 2, 1);
      $pdf->SetX(0);
      $pdf->Row(array(
            $this->lang->line('factura_ce_colonia', 'Colonia'), $ceExtras->receptor->domicilio->colonia,
            $this->lang->line('factura_ce_localidad', 'Localidad'), $ceExtras->receptor->domicilio->localidad,
            $this->lang->line('factura_ce_codigo', 'Codigo Postal'), $ceExtras->receptor->domicilio->codigoPostal,
          ), false, true, null, 2, 1);
      $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'L', 'L', 'L'));
      $pdf->SetWidths(array(17, 37, 17, 37, 17, 27, 17, 47));
      $pdf->SetX(0);
      $pdf->Row(array(
            $this->lang->line('factura_ce_municipio', 'Municipio'), $ceExtras->receptor->domicilio->municipio,
            $this->lang->line('factura_ce_estado', 'Estado'), $ceExtras->receptor->domicilio->estado,
            $this->lang->line('factura_ce_pais', 'Pais'), $ceExtras->receptor->domicilio->pais,
            $this->lang->line('factura_ce_referencia', 'Referencia'), '',
          ), false, true, null, 2, 1);

      $pdf->SetFont('helvetica','B', 8);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(216));
      $pdf->SetXY(0, $pdf->GetY() + 1);
      $pdf->Row(array(
        "Destinatario"
      ), false, true, null, 2, 1);


      $pdf->SetFont('helvetica','', 8);
      $pdf->SetXY(0, $pdf->GetY() + 4);
      $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'L', 'L', 'L'));
      $pdf->SetWidths(array(17, 64, 17, 118));
      $pdf->SetX(0);
      $pdf->Row(array(
            $this->lang->line('factura_ce_num_trib', 'Num Id Trib'), $factura['ce']->destinatario->numregidtrib,
            $this->lang->line('factura_ce_nombre', 'Nombre'), $factura['ce']->destinatario->nombre,
          ), false, true, null, 2, 1);
      $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'L'));
      $pdf->SetWidths(array(20, 64, 20, 56, 20, 36));
      $pdf->SetX(0);
      $pdf->Row(array(
            $this->lang->line('factura_ce_calle', 'Calle'), $factura['ce']->destinatario->calle,
            $this->lang->line('factura_ce_no_exterior', 'No. Exterior'), $factura['ce']->destinatario->numero_exterior,
            $this->lang->line('factura_ce_no_interior', 'No. Interior'), $factura['ce']->destinatario->numero_interior,
          ), false, true, null, 2, 1);
      $pdf->SetX(0);
      $pdf->Row(array(
            $this->lang->line('factura_ce_colonia', 'Colonia'), $factura['ce']->destinatario->colonia,
            $this->lang->line('factura_ce_localidad', 'Localidad'), $factura['ce']->destinatario->localidad,
            $this->lang->line('factura_ce_codigo', 'Codigo Postal'), $factura['ce']->destinatario->codigo_postal,
          ), false, true, null, 2, 1);
      $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'L', 'L', 'L'));
      $pdf->SetWidths(array(17, 37, 17, 37, 17, 27, 17, 47));
      $pdf->SetX(0);
      $pdf->Row(array(
            $this->lang->line('factura_ce_municipio', 'Municipio'), $factura['ce']->destinatario->municipio,
            $this->lang->line('factura_ce_estado', 'Estado'), $factura['ce']->destinatario->estado,
            $this->lang->line('factura_ce_pais', 'Pais'), $factura['ce']->destinatario->pais,
            $this->lang->line('factura_ce_referencia', 'Referencia'), $factura['ce']->destinatario->referencia,
          ), false, true, null, 2, 1);

      $aligns = array('C', 'C', 'C', 'C', 'C', 'C');
      $aligns2 = array('C', 'C', 'L', 'C', 'R', 'R');
      $aligns3 = array('L', 'L', 'L', 'L');
      $widths = array(36, 36, 36, 36, 36, 36);
      $widths3 = array(50, 50, 50, 50);
      $header = array('No Ident', 'Frac Aran',
        $this->lang->line('factura_ce_cantidad', 'Cantidad'),
        $this->lang->line('factura_ce_unidad', 'Unidad'),
        $this->lang->line('factura_ce_valor_unitario', 'Valor Unitario'),
        $this->lang->line('factura_ce_valor_dolares', 'Valor Dolares'),
      );
      $pdf->setY($pdf->GetY() + 1);
      $hay_prod_certificados = false;
      foreach($factura['ce']->mercancias as $key => $item)
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
          $item->noidentificacion,
          $item->fraccionar_ancelaria,
          $item->cantidad_aduana,
          $item->unidad_aduana,
          MyString::formatoNumero($item->valor_unitario_aduana, 2, '$', false),
          MyString::formatoNumero($item->valor_dolares, 2, '$', false),
        ), false, true, null, 2, 1);

        if (count($item->esp) > 0) {
          foreach($item->esp as $key2 => $esp)
          {
            if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
            {
              $pdf->AddPage();
            }

            $pdf->SetFont('Arial', '', 8);
            $pdf->SetTextColor(0,0,0);

            $pdf->SetX(0);
            $pdf->SetAligns($aligns3);
            $pdf->SetWidths($widths3);
            $pdf->Row(array(
              $this->lang->line('factura_ce_marca', 'Marca').': '.$esp->marca,
              $this->lang->line('factura_ce_modelo', 'Modelo').': '.$esp->modelo,
              $this->lang->line('factura_ce_sub_modelo', 'Sub Modelo').': '.$esp->submodelo,
              $this->lang->line('factura_ce_numero_serie', 'Numero Serie').': '.$esp->numeroserie,
            ), false, true, null, 2, 1);
          }
        }
      }
    }


    ////////////////////
    // Timbrado Datos //
    ////////////////////

    if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
        $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetXY(3, $pdf->GetY());
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(196));
    $pdf->Row(array($this->lang->line('factura_otros_sello_digital_cfdi', 'Sello Digital del CFDI').':'), false, 0);

    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetXY(3, $pdf->GetY() - 3);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(211));
    $pdf->Row(array($xml->Complemento->TimbreFiscalDigital[0]['SelloCFD']), false, false);

    if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
        $pdf->AddPage();

    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetXY(3, $pdf->GetY() - 2);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(196));
    $pdf->Row(array($this->lang->line('factura_otros_sello_digital_sat', 'Sello Digital del SAT').':'), false, 0);

    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetXY(3, $pdf->GetY() - 3);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(211));
    $pdf->Row(array($xml->Complemento->TimbreFiscalDigital[0]['SelloSAT']), false, 0);

    /////////////
    // QR CODE //
    /////////////

    // Genera Qr.
    $cad_sello = substr($factura['info']->sello, -8);
    $cadenaOriginalSAT = "https://verificacfdi.facturaelectronica.sat.gob.mx/default.aspx?&id={$factura['info']->uuid}&re={$factura['info']->cfdi_ext->emisor->rfc}&rr={$factura['info']->cfdi_ext->receptor->rfc}&tt={$factura['info']->cfdi_ext->total}&fe={$cad_sello}";

    // echo "<pre>";
    //   var_dump($cadenaOriginalSAT, $total, $diff);
    // echo "</pre>";exit;

    QRcode::png($cadenaOriginalSAT, APPPATH.'media/qrtemp.png', 'H', 3);

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

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(45, $pdf->GetY() + 1);
    $pdf->Cell(80, 6, $this->lang->line('factura_otros_rfc_prov_certif', 'RFC Prov Certif').":", 0, 0, 'R', 1);

    $pdf->SetXY(125, $pdf->GetY());
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['RfcProvCertif'], 0, 0, 'C', 0);

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(45, $pdf->GetY() + 10);
    $pdf->Cell(80, 6, $this->lang->line('factura_otros_no_serie_cer_sat', 'No de Serie del Certificado del SAT').":", 0, 0, 'R', 1);

    $pdf->SetXY(125, $pdf->GetY());
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['NoCertificadoSAT'], 0, 0, 'C', 0);

    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(242, 242, 242);
    $pdf->SetTextColor(0, 171, 72);
    $pdf->SetXY(45, $pdf->GetY() + 10);
    $pdf->Cell(80, 6, $this->lang->line('factura_otros_fecha_hora_certificacion', 'Fecha y hora de certificación').":", 0, 0, 'R', 1);

    $pdf->SetXY(125, $pdf->GetY());
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['FechaTimbrado'], 0, 0, 'C', 0);

    $pdf->SetXY(0, $pdf->GetY()+13);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(220, 6, $this->lang->line('factura_otros_leyenda_cfdi', 'ESTE DOCUMENTO ES UNA REPRESENTACION IMPRESA DE UN CFDI'), 0, 0, 'C', 0);
    $pdf->SetXY(10, $pdf->GetY()+3);

    //------------ IMAGEN CANDELADO --------------------

    if($factura['info']->status === 'ca'){
      $pdf->Image(APPPATH.'/images/cancelado.png', 20, 40, 190, 190, "PNG");
    }

    ////////////////////
    // pagare      //
    ////////////////////
    $pdf->SetWidths(array(190));
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
      $pdf->SetWidths(array(70));
      $pdf->SetXY(130, $yaux+3);
      $pdf->SetAligns(array('C'));
      $pdf->Row2(array('______________________________________________'), false, false);
      $pdf->SetXY(130, $pdf->GetY());
      $pdf->Row2(array('FIRMA'), false, false);

      $pdf->SetWidths(array(190));
      $pdf->SetAligns(array('L'));
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

    if (isset($factura['carta_porte']))
    {
      $pdf->AliasNbPages();
      $pdf->AddPage();

      $pdf->SetFont('helvetica', '', 9);
      $pdf->SetWidths(array(200));
      $pdf->SetAligns(array('L'));

      $pdf->Row(array('PRIMERA.- Para los efectos del presente contrato de transporte se denomina "Transportista" al que realiza el servicio de transportación y "Remitente" o "Expedidor" al usuario que contrate el servicio o remite la mercancía.'), false, false, false);
      $pdf->Row(array('SEGUNDA.- El "Remitente" o "Expedidor" es responsable de que la información proporcionada al "Transportista" sea veraz y que la documentación que entregue para efectos del transporte sea la correcta.'), false, false, false);
      $pdf->Row(array('TERCERA.- El "Remitente" o "Expedidor" debe declarar al "Transportista" el tipo de mercancía o efectos de que se trate, peso, medidas y/o número de la carga que entrega para su transporte y, en su caso, el valor de la misma. La carga que se entregue a granel será pesada por el "Transportista" en el primer punto donde haya báscula apropiada o, en su defecto, aforada en metros cúbicos con la conformidad del "Remitente" o "Expedidor".'), false, false, false);
      $pdf->Row(array('CUARTA.- Para efectos del transporte, el "Remitente" o "Expedidor" deberá entregar al "Transportista" los documentos que las leyes y reglamentos exijan para llevar a cabo el servicio, en caso de no cumplirse con estos requisitos el "Transportista" está obligado a rehusar el transporte de las mercancías.'), false, false, false);
      $pdf->Row(array('QUINTA.- Si por sospecha de falsedad en la declaración del contenido de un bulto el "Transportista" deseare proceder a su reconocimiento, podrá hacerlo ante testigos y con asistencia del "Remitente" o "Expedidor" o del consignatario. Si este último no concurriere, se solicitará la presencia de un inspector de la Secretaría de Comunicaciones y Transportes, y se levantará el acta correspondiente. El "Transportista" tendrá en todo caso, la obligación de dejar los bultos en el estado en que se encontraban antes del reconocimiento.'), false, false, false);
      $pdf->Row(array('SEXTA.- El "Transportista" deberá recoger y entregar la carga precisamente en los domicilios que señale el "Remitente" o "Expedidor", ajustándose a los términos y condiciones convenidos. El "Transportista" sólo está obligado a llevar la carga al domicilio del consignatario para su entrega una sola vez. Si ésta no fuera recibida, se dejará aviso de que la mercancía queda a disposición del interesado en las bodegas que indique el "Transportista".'), false, false, false);
      $pdf->Row(array('SÉPTIMA.- Si la carga no fuere retirada dentro de los 30 días hábiles siguientes a aquél en que hubiere sido puesta a disposición del consignatario, el "Transportista" podrá solicitar la venta en subasta pública con arreglo a lo que dispone el Código de Comercio.'), false, false, false);
      $pdf->Row(array('OCTAVA.- El "Transportista" y el "Remitente" o "Expedidor" negociarán libremente el precio del servicio, tomando en cuenta su tipo, característica de los embarques, volumen, regularidad, clase de carga y sistema de pago.'), false, false, false);
      $pdf->Row(array('NOVENA.- Si el "Remitente" o "Expedidor" desea que el "Transportista" asuma la responsabilidad por el valor de las mercancías o efectos que él declare y que cubra toda clase de riesgos, inclusive los derivados de caso fortuito o de fuerza mayor, las partes deberán convenir un cargo adicional, equivalente al valor de la prima del seguro que se contrate, el cual se deberá expresar en la Carta de Porte.'), false, false, false);
      $pdf->Row(array('DÉCIMA.- Cuando el importe del flete no incluya el cargo adicional, la responsabilidad del "Transportista" queda expresamente limitada a la cantidad equivalente a 15 días del salario mínimo vigente en el Distrito Federal por tonelada o cuando se trate de embarques cuyo peso sea mayor de 200 kg., pero menor de 1000 kg; y a 4 días de salario mínimo por remesa cuando se trate de embarques con peso hasta de 200 kg.'), false, false, false);
      $pdf->Row(array('DÉCIMA PRIMERA.- El precio del transporte deberá pagarse en origen, salvo convenio entre las partes de pago en destino. Cuando el transporte se hubiere concertado "Flete por Cobrar", la entrega de las mercancías o efectos se hará contra el pago del flete y el "Transportista" tendrá derecho a retenerlos mientras no se le cubra el precio convenido.'), false, false, false);
      $pdf->Row(array('DÉCIMA SEGUNDA.- Si al momento de la entrega resultare algún faltante o avería, el consignatario deberá hacerla constar en ese acto en la Carta de Porte y formular su reclamación por escrito al "Transportista", dentro de las 24 horas siguientes.'), false, false, false);
      $pdf->Row(array('DÉCIMA TERCERA.- El "Transportista" queda eximido de la obligación de recibir mercancías o efectos para su transporte, en los siguientes casos:'), false, false, false);
      $pdf->Row(array('a) Cuando se trate de carga que por su naturaleza, peso, volumen, embalaje defectuoso o cualquier otra circunstancia no pueda transportarse sin destruirse o sin causar daño a los demás artículos o al material rodante, salvo que la empresa de que se trate tenga el equipo adecuado.'), false, false, false);
      $pdf->Row(array('b) Las mercancías cuyo transporte haya sido prohibido por disposiciones legales o reglamentarias. Cuando tales disposiciones no prohíban precisamente el transporte de determinadas mercancías, pero sí ordenen la presentación de ciertos documentos para que puedan ser transportadas, el "Remitente" o "Expedidor" estará obligado a entregar al "Transportista" los documentos correspondientes.'), false, false, false);
      $pdf->Row(array('DÉCIMA CUARTA.- Los casos no previstos en las presentes condiciones y las quejas derivadas de su aplicación se someterán por la vía administrativa a la Secretaría de Comunicaciones y Transportes.'), false, false, false);
      $pdf->Row(array('DÉCIMA QUINTA.- Para el caso de que el "Remitente" o "Expedidor" contrate carro por entero, este aceptará la responsabilidad solidaria para con el "Transportista" mediante la figura de la corresponsabilidad que contempla el artículo 10 del Reglamento Sobre el Peso, Dimensiones y Capacidad de los Vehículos de Autotransporte que Transitan en los Caminos y Puentes de Jurisdicción Federal, por lo que el "Remitente" o "Expedidor" queda obligado a verificar que la carga y el vehículo que la transporta, cumplan con el peso y dimensiones máximas establecidos en la NOM-012 SCT-2-2014.'), false, false, false);
    }

    if ($path) {
      $folio = $this->cfdi->acomodarFolio($factura['info']->folio);
      $file = $factura['info']->cfdi_ext->emisor->rfc.'-'.$factura['info']->serie.$folio;
      file_put_contents($path."{$file}.xml", $factura['info']->xml);
      $this->cfdi->guardarXMLFactura($factura['info']->xml, $factura['info']->cfdi_ext->emisor->rfc, $factura['info']->serie, $factura['info']->folio, $factura['info']->fecha);
      $pdf->Output($path."{$file}.pdf", 'F');
    } else {
      // Actualiza el # de impresion
      $this->db->update('facturacion', ['no_impresiones' => $factura['info']->no_impresiones+1], "id_factura = ".$factura['info']->id_factura);

      $file_name = 'Factura'.rand(0, 1000).'.pdf';
      if (!isset($data_chofer->url_licencia{0}) || !isset($data_chofer->url_ife{0})) {
        $pdf->Output('Factura', 'I');
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

        $pdf->merge('browser', 'Factura.pdf');
        // unlink(APPPATH.'media/temp/'.$file_name);
      }
    }
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

    $resultTimbrado =  $this->addFactura(false, $facturaId);

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

    if ($this->input->get('empresaId') > 0)
    {
      $sql .= " AND f.id_empresa = {$this->input->get('empresaId')}";
    }

    $remisiones = $this->db->query(
      "SELECT f.id_factura, f.serie, f.folio, c.nombre_fiscal, f.is_factura, DATE(f.fecha) as fecha
       FROM facturacion f
       INNER JOIN clientes c ON c.id_cliente = f.id_cliente
       LEFT JOIN (SELECT id_remision, id_factura, status
                  FROM remisiones_historial WHERE status <> 'ca' AND status <> 'b'
        ) fh ON f.id_factura = fh.id_remision
       WHERE f.status <> 'ca' AND f.status <> 'b' AND f.is_factura = false
        AND COALESCE(fh.id_remision, 0) = 0 {$sql}
       ORDER BY (DATE(f.fecha), f.serie, f.folio) DESC")->result();

    // $remisiones = $this->db->query(
    //   "SELECT f.id_factura, f.serie, f.folio, c.nombre_fiscal, f.is_factura, DATE(f.fecha) as fecha
    //    FROM facturacion f
    //    INNER JOIN clientes c ON c.id_cliente = f.id_cliente
    //    LEFT JOIN facturacion_ventas_remision_pivot fvp ON fvp.id_venta = f.id_factura
    //    WHERE f.status = 'ca' AND f.is_factura = false AND (
    //     (SELECT status FROM facturacion WHERE id_factura = fvp.id_factura) in ('ca', 'b') OR COALESCE(fvp.id_factura, 0) = 0) {$sql}
    //    ORDER BY (DATE(f.fecha), f.serie, f.folio) DESC")->result();

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

  public function remisionesDetalleData(&$filtros) {
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
    return ['rem' => $remisiones, 'titulo2' => $titulo2];
  }
  public function remisionesDetallePdf($filtros)
  {
    $remisiones = $this->remisionesDetalleData($filtros);

    $empresa = $this->empresas_model->getInfoEmpresa($filtros['did_empresa']);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = $remisiones['titulo2'];
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($filtros['ffecha1'])." Al ".MyString::fechaAT($filtros['ffecha2'])."\n";
    // $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
    $pdf->AliasNbPages();
    // $pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'R', 'R', 'R');
    $widths = array(122, 28, 11, 20, 23);
    $header = array('Cliente', 'Fecha', 'Serie', 'Folio', 'Importe');

    $total = 0;
    foreach($remisiones['rem'] as $key => $item)
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
        MyString::fechaATexto($item->fecha, '/c'),
        $item->serie,
        $item->folio,
        MyString::formatoNumero($item->total, 2, '', false),
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
              MyString::fechaATexto($remi->fecha, '/c'),
              $remi->serie,
              $remi->folio,
              '('.MyString::formatoNumero($remi->total, 2, '', false).')',
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
    $pdf->Row(array('TOTAL', MyString::formatoNumero($total, 2, '', false)), false);

    $pdf->Output('reporte_remisiones.pdf', 'I');
  }
  public function remisionesDetalleXls($filtros)
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=reporte_remisiones.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $remisiones = $this->remisionesDetalleData($filtros);

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = $remisiones['titulo2'];
    $titulo3 = 'Del: '.$filtros['ffecha1']." Al ".$filtros['ffecha2']."\n";


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
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Fecha</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Serie</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Importe</td>
      </tr>';

    $total = 0;
    foreach($remisiones['rem'] as $key => $item)
    {
      $color = 'FFFFFF';
      if ($item->tipo === 'factura')
      {
        $color = 'FFFFCC';
      }

      $html .= '<tr>
          <td style="width:400px;border:1px solid #000;background-color: #'.$color.';">'.$item->cliente.'</td>
          <td style="width:150px;border:1px solid #000;background-color: #'.$color.';">'.$item->fecha.'</td>
          <td style="width:150px;border:1px solid #000;background-color: #'.$color.';">'.$item->serie.'</td>
          <td style="width:150px;border:1px solid #000;background-color: #'.$color.';">'.$item->folio.'</td>
          <td style="width:150px;border:1px solid #000;background-color: #'.$color.';">'.$item->total.'</td>
        </tr>';

      if (isset($item->remisiones))
      {
        foreach ($item->remisiones as $keya => $cliente)
        {
          $html .= '<tr>
              <td style="width:400px;border:1px solid #000;">     '.$cliente['cliente'].'</td>
              <td style="width:150px;border:1px solid #000;"></td>
              <td style="width:150px;border:1px solid #000;"></td>
              <td style="width:150px;border:1px solid #000;"></td>
              <td style="width:150px;border:1px solid #000;"></td>
            </tr>';

          foreach ($cliente['remisiones'] as $remi)
          {
            $html .= '<tr>
                <td style="width:400px;border:1px solid #000;"></td>
                <td style="width:150px;border:1px solid #000;">'.MyString::fechaATexto($remi->fecha, '/c').'</td>
                <td style="width:150px;border:1px solid #000;">'.$remi->serie.'</td>
                <td style="width:150px;border:1px solid #000;">'.$remi->folio.'</td>
                <td style="width:150px;border:1px solid #000;">('.$remi->total.')</td>
              </tr>';
          }
        }
      }
      $total += floatval($item->total);
    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="4">TOTAL</td>
          <td style="border:1px solid #000;">'.$total.'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }
}