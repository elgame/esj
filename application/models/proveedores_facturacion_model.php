<?php
class proveedores_facturacion_model extends privilegios_model{

  function __construct(){
    parent::__construct();
  }

  /*
   |-------------------------------------------------------------------------
   |  FACTURACION
   |-------------------------------------------------------------------------
   */
  /**
   * Obtiene los saldos de proveedores
   * @return [type] [description]
   */
  public function getProveedores($fecha)
  {
    $fechae = explode('-', $fecha);

    $this->load->model('proveedores_model');
    $data = $this->proveedores_model->getProveedores();
    foreach ($data['proveedores'] as $key => $value)
    {
      $limite           = $this->getLimiteProveedores($value->id_proveedor, $fechae[0], $fecha);
      $value->facturado = $limite['facturado'];
      $value->limite    = $limite['limite'];
      $value->saldo     = ($limite['limite']-$limite['facturado']);

      $data['proveedores'][$key] = $value;
    }
    return $data;
  }

  public function getLimiteProveedores($id_proveedor, $anio, $fecha='')
  {
    $data_salario = $this->db->query("SELECT zona_a AS salario FROM nomina_salarios_minimos WHERE anio = {$anio}")->row();
    $response['limite'] = 900000; // $data_salario->salario * 40 * 30.4 * 12;

    $sql_fecha = $fecha!=''? " AND Date(fecha) <= '{$fecha}'": '';
    $data_saldo = $this->db->query("SELECT Sum(total) AS total
      FROM proveedores_facturacion
      WHERE id_proveedor = {$id_proveedor} AND status IN('p', 'pa')
        AND status_timbrado <> 'ca' AND date_part('year', fecha) = {$anio} {$sql_fecha}")->row();

    $response['facturado'] = $data_saldo->total;
    return $response;
  }

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
        'result_page'       => (isset($_GET['pag'])? $_GET['pag']: 0)
    );
    if($params['result_page'] % $params['result_items_per_page'] == 0)
      $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

    //Filtros para buscar
    if($this->input->get('ffecha1') != '')
      $sql = " AND Date(pf.fecha) <= '".$this->input->get('ffecha1')."'";

    // if($this->input->get('fserie') != '')
    //  $sql .= " AND c.serie = '".$this->input->get('fserie')."'";
    if($this->input->get('ffolio') != '')
      $sql .= " AND pf.folio = '".$this->input->get('ffolio')."'";
    if($this->input->get('fstatus') != '')
      $sql .= " AND pf.status = '".$this->input->get('fstatus')."'";
    if($this->input->get('did_empresa') != '')
      $sql .= " AND pf.id_empresa = '".$this->input->get('did_empresa')."'";
    if($this->input->get('fid_proveedor') != '')
      $sql .= " AND pf.id_proveedor = '".$this->input->get('fid_proveedor')."'";

    $query = BDUtil::pagination("
        SELECT pf.id_factura, Date(pf.fecha) AS fecha, pf.serie, pf.folio, e.nombre_fiscal,
                p.nombre_fiscal as proveedor, pf.condicion_pago, pf.forma_pago, pf.status, pf.total, pf.id_nc,
                pf.status_timbrado, pf.uuid
        FROM proveedores_facturacion AS pf
        INNER JOIN proveedores AS p ON p.id_proveedor = pf.id_proveedor
        INNER JOIN empresas AS e ON e.id_empresa = pf.id_empresa
        WHERE pf.status <> 'b' ".$sql.$sql2."
        ORDER BY (pf.fecha, pf.folio) DESC
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
  public function getInfoFactura($idFactura, $info_basic=false)
  {
    $res = $this->db
            ->select("*")
            ->from('proveedores_facturacion')
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

      // Carga la info del proveedor.
      $this->load->model('proveedores_model');
      $prov = $this->proveedores_model->getProveedorInfo($response['info']->id_proveedor);
      $response['info']->proveedor = $prov['info'];

      // Carga la info de la empresa.
      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($response['info']->id_empresa);
      $response['info']->empresa = $empresa['info'];

      $res = $this->db
        ->select('pfp.id_factura, pfp.id_bascula, pfp.num_row')
        ->from('proveedores_facturacion_productos as pfp')
        ->where('id_factura = ' . $idFactura)
        ->get();

      $productos = $res->result();

      foreach ($productos as $key => $prod)
      {
        $query = $this->db->select('bc.*, ca.nombre, ba.id_area, ba.folio')
          ->from('bascula_compra bc')
          ->join('calidades ca', 'bc.id_calidad = ca.id_calidad', 'inner')
          ->join('bascula ba', 'bc.id_bascula = ba.id_bascula', 'inner')
          ->where('bc.id_bascula', $prod->id_bascula)
          ->get();

        $productos[$key]->productos = $query->result();

        $query->free_result();
      }

      $response['productos'] = $productos;

      return $response;
    }else
      return false;
  }

  public function getInfoPredatosFactura($id_proveedor, $id_empresa)
  {
    if($id_proveedor > 0 && $id_empresa > 0)
    {
      $response['info'] = new stdClass;

      // Carga la info del proveedor.
      $this->load->model('proveedores_model');
      $prov = $this->proveedores_model->getProveedorInfo($id_proveedor);
      $response['info']->proveedor = $prov['info'];

      // Carga la info de la empresa.
      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($id_empresa);
      $response['info']->empresa = $empresa['info'];

      return $response;
    }else
      return false;
  }

  /**
   * Obtiene el folio de acuerdo a la serie seleccionada
     *
     * @param string $serie
     * @param string $proveedor
   */
  public function getFolioSerie($serie, $proveedor, $sqlX = null)
  {
    $res = $this->db->select('folio')
      ->from('proveedores_facturacion')
      ->where("serie = '".$serie."' AND id_proveedor = ".$proveedor." AND status != 'b'")
      ->order_by('folio', 'DESC')
      ->limit(1)->get()->row();

    $folio = (isset($res->folio)? $res->folio: 0)+1;

    if ( ! is_null($sqlX))
      $this->db->where($sqlX);

    $res = $this->db->select('*')
      ->from('proveedores_series_folios')
      ->where("serie = '".$serie."' AND id_proveedor = ".$proveedor)
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
    public function getSeriesProveedor($id_proveedor, $sqlX = null)
    {
        if ( is_null($sqlX))
          $this->db->where("es_nota_credito = 'f' ");
        else
          $this->db->where($sqlX);

        $query = $this->db
          ->select('id_serie_folio, id_proveedor, serie, leyenda')
          ->from('proveedores_series_folios')
          ->where("id_proveedor = ".$id_proveedor."")
          ->order_by('serie', 'ASC')
          ->get();

        $res = array();
        if($query->num_rows() > 0)
        {
          $res = $query->result();
          $msg = 'ok';
        }
        else
          $msg = 'El proveedor seleccionada no cuenta con Series y Folios.';

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
        $empresa = $this->empresas_model->getInfoEmpresa($_POST['did_empresa'], true);

        // Array con los datos necesarios para generar la cadena original.
        $data = array(
          'id'                => $this->input->post('did_proveedor'),
          'table'             => 'proveedores',

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

          'rfc'               => $empresa['info']->rfc,
          'nombre'            => $empresa['info']->nombre_fiscal,
          'calle'             => $empresa['info']->calle,
          'noExterior'        => $empresa['info']->no_exterior,
          'noInterior'        => $empresa['info']->no_interior,
          'colonia'           => $empresa['info']->colonia,
          'localidad'         => $empresa['info']->localidad,
          'municipio'         => $empresa['info']->municipio,
          'estado'            => $empresa['info']->estado,
          'pais'              => 'MEXICO',
          'codigoPostal'      => $empresa['info']->cp,

          'concepto'          => array(),

          'retencion'         => array(),
          'totalImpuestosRetenidos' => 0,

          'traslado'          => array(),
          'totalImpuestosTrasladados' => 0
        );

        return $data;
    }

  /**
   * Agrega una Factura.
     *
     * @return  array
   */
    public function addFactura($borrador = false)
    {
        $this->load->library('cfdi');
        $this->load->model('empresas_model');
        // $this->load->model('clientes_model');

        $anoAprobacion = explode('-', $_POST['dano_aprobacion']);

        // Obtiene la forma de pago, si es en parcialidades entonces la forma de
        // pago son las parcialidades "Parcialidad 1 de X".
        $formaPago = ($_POST['dforma_pago'] == 'Pago en parcialidades') ? $this->input->post('dforma_pago_parcialidad') : 'Pago en una sola exhibición';

        $datosFactura = array(
          'id_empresa'          => $this->input->post('did_empresa'),
          'id_proveedor'        => $this->input->post('did_proveedor'),
          'version'             => $this->input->post('dversion'),
          'serie'               => $this->input->post('dserie'),
          'folio'               => $this->input->post('dfolio'),
          'fecha'               => str_replace('T', ' ', $_POST['dfecha']),
          'subtotal'            => $this->input->post('total_subtotal'),
          'importe_iva'         => $this->input->post('total_iva'),
          'total'               => $this->input->post('total_totfac'),
          'total_letra'         => $this->input->post('dttotal_letra'),
          'no_aprobacion'       => intval($this->input->post('dno_aprobacion')),
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
          'plazo_credito'       => $_POST['dcondicion_pago'] === 'co' ? 0 : intval($this->input->post('dplazo_credito')),
          'observaciones'       => $this->input->post('dobservaciones'),
          'status'              => isset($_POST['timbrar']) ? 'p' : 'b',
          // 'status'              => $_POST['dcondicion_pago'] === 'co' ? 'pa' : 'p',
          'retencion_iva'       => $this->input->post('total_retiva'),
        );

        // Si el tipo de comprobante es "egreso" o una nota de credito.
        if ($_POST['dtipo_comprobante'] === 'egreso')
          $datosFactura['id_nc'] = $_GET['id'];
        // Inserta los datos de la factura y obtiene el Id.
        $this->db->insert('proveedores_facturacion', $datosFactura);
        $idFactura = $this->db->insert_id('proveedores_facturacion_id_factura_seq');

        // Productos e Impuestos
        $productosCadOri    = array(); // Productos para la CadOriginal
        $productosFactura   = array(); // Productos para la Factura

        $impuestosTraslados = array(); // Traslados
        $traslado0  = true; // Total de traslado 0%
        $traslado11 = 0; // Total de traslado 11%
        $traslado16 = 0; // Total de traslado 16%

        $anterior = 0;
        // Ciclo para obtener los impuestos traslados, tambien construye
        // los datos de  los productos a insertar tanto en la cadena original como
        // en la factura.
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

            // if ($_POST['prod_diva_porcent'][$key] == '11')
            //   $traslado11 += floatval($_POST['prod_diva_total'][$key]);
            // else if ($_POST['prod_diva_porcent'][$key] == '16')
            //   $traslado16 += floatval($_POST['prod_diva_total'][$key]);
            // else
            //   $traslado0 = true;

            if ($anterior != floatval($_POST['prod_did_prod'][$key]))
            {
              $productosFactura[] = array(
                'id_factura' => $idFactura,
                'id_bascula' => $_POST['prod_did_prod'][$key],
                'num_row'    => intval($key),
              );
            }

            $anterior = $_POST['prod_did_prod'][$key];
          }
        }

        if (count($productosFactura) > 0)
          $this->db->insert_batch('proveedores_facturacion_productos', $productosFactura);

        // Si es un borrador
        if ($borrador) return true;

        // Obtiene los datos para la cadena original
        $datosCadOrig = $this->datosCadenaOriginal();

        // // Si es un ingreso o una factura.
        // if ($_POST['dtipo_comprobante'] === 'ingreso')
        // {
        //   $pathDocs = $this->creaDirectorio($datosFactura['id_proveedor'], $datosFactura['serie'], $datosFactura['folio']);
        // }
        // else
        // {
        //   $pathDocs = $this->creaDirectorio($datosFactura['id_proveedor'], $datosFactura['serie'], $datosFactura['folio']);
        // }

        $dataEmpresa = array(
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
        $this->db->insert('proveedores_facturacion_empresa', $dataEmpresa);

        // // Asignamos los productos o conceptos a los datos de la cadena original.
        // $datosCadOrig['concepto']  = $productosCadOri;

        // // Asignamos las retenciones a los datos de la cadena original.
        //  $impuestosRetencion = array(
        //   'impuesto' => 'IVA',
        //   'importe'  => '0',
        // );

        // $datosCadOrig['retencion'][] = $impuestosRetencion;
        // $datosCadOrig['totalImpuestosRetenidos'] = '0';

        // // Si hay conceptos con traslado 0% lo agrega.
        // if ($traslado0 && $traslado11 === 0 && $traslado16 === 0)
        // {
        //     $impuestosTraslados[] = array(
        //         'Impuesto' => 'IVA',
        //         'tasa'     => '0',
        //         'importe'  => '0',
        //     );
        // }

        // // Asigna los impuestos traslados.
        // $datosCadOrig['traslado']  = $impuestosTraslados;
        // $datosCadOrig['totalImpuestosTrasladados'] = '0';

        // // Genera la cadena original y el sello.
        // $cadenaOriginal = $this->cfdi->obtenCadenaOriginal($datosCadOrig);
        // $sello          = $this->cfdi->obtenSello($cadenaOriginal['cadenaOriginal']);

        // // Obtiene el contentido del certificado.
        // $certificado = $this->cfdi->obtenCertificado($this->db
        //   ->select('cer')
        //   ->from("proveedores")
        //   ->where("id_proveedor", $_POST['did_proveedor'])
        //   ->get()->row()->cer
        // );

        // // Datos que actualizara de la factura
        // $updateFactura = array(
        //   'cadena_original' => $cadenaOriginal['cadenaOriginal'],
        //   'sello'           => $sello,
        //   'certificado'     => $certificado,
        // );
        // $this->db->update('proveedores_facturacion', $updateFactura, array('id_factura' => $idFactura));

        // // Datos para el XML3.2
        // $datosXML               = $cadenaOriginal['datos'];
        // $datosXML['id']         = $this->input->post('did_proveedor');
        // $datosXML['table']      = 'proveedores';
        // $datosXML['comprobante']['serie']         = $this->input->post('dserie');
        // $datosXML['comprobante']['folio']         = $this->input->post('dfolio');
        // $datosXML['comprobante']['sello']         = $sello;
        // $datosXML['comprobante']['noCertificado'] = $this->input->post('dno_certificado');
        // $datosXML['comprobante']['certificado']   = $certificado;
        // $datosXML['concepto']                     = $productosCadOri;

        // $datosXML['domicilio']['calle']        = $dataEmpresa['calle'];
        // $datosXML['domicilio']['noExterior']   = $dataEmpresa['no_exterior'];
        // $datosXML['domicilio']['noInterior']   = $dataEmpresa['no_interior'];
        // $datosXML['domicilio']['colonia']      = $dataEmpresa['colonia'];
        // $datosXML['domicilio']['localidad']    = $dataEmpresa['localidad'];
        // $datosXML['domicilio']['municipio']    = $dataEmpresa['municipio'];
        // $datosXML['domicilio']['estado']       = $dataEmpresa['estado'];
        // $datosXML['domicilio']['pais']         = $dataEmpresa['pais'];
        // $datosXML['domicilio']['codigoPostal'] = $dataEmpresa['cp'];

        // $datosXML['totalImpuestosRetenidos']   = $this->input->post('total_retiva');
        // $datosXML['totalImpuestosTrasladados'] = $this->input->post('total_iva');

        // $datosXML['retencion'] = $impuestosRetencion;
        // $datosXML['traslado']  = $impuestosTraslados;

        // // Genera el archivo XML y lo guarda en disco.
        // $archivos = $this->cfdi->generaArchivos($datosXML);

        // // Timbrado de la factura.
        // $result = $this->timbrar($archivos['pathXML'], $idFactura);

        // if ($result['passes'])
        // {
        //   $this->generaFacturaPdf($idFactura, $pathDocs);

        //   $xmlName = explode('/', $archivos['pathXML']);

        //   copy($archivos['pathXML'], $pathDocs.end($xmlName));

        //   if (isset($_GET['id']))
        //     $this->db->delete('proveedores_facturacion', array('id_factura' => $_GET['id']));
        // }
        // else rmdir($pathDocs);

        // // $datosFactura, $cadenaOriginal, $sello, $productosFactura,
        // // echo "<pre>";
        // //   var_dump($datosXML);
        // // echo "</pre>";exit;

        return ['passes' => true];
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

          $this->db->update('proveedores_facturacion', $dataTimbrado, array('id_factura' => $idFactura));

          $result['passes'] = true;
        }
        else
        {
          // Si es true $delFile entonces elimina todo lo relacionado con la factura.
          if ($delFiles)
          {
            $this->db->delete('proveedores_facturacion_empresa', array('id_factura' => $idFactura));
            $this->db->delete('proveedores_facturacion', array('id_factura' => $idFactura));
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
          ->from('proveedores_facturacion')
          ->where('id_factura', $idFactura)
          ->get()->row()->uuid;

        $this->facturartebarato_api->setUUID($uuid);

        // Reliza la peticion para verificar el stutus de la factura.
        $result = $this->facturartebarato_api->verificarPendiente();

        // Si el status es Finished entonces ya se timbro correctamente.
        if ($result->data->status === 'F')
        {
          $this->db->update('proveedores_facturacion',
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

    // // Obtenemos la info de la factura a cancelar.
    // $factura = $this->getInfoFactura($idFactura);

    // // Carga los datos fiscales de la empresa dentro de la lib CFDI.
    // $this->cfdi->cargaDatosFiscales($factura['info']->id_proveedor, 'proveedores');

    // // Parametros que necesita el webservice para la cancelacion.
    // $params = array(
    //   'rfc'   => $factura['info']->proveedor->rfc,
    //   'uuids' => $factura['info']->uuid,
    //   'cer'   => $this->cfdi->obtenCer(),
    //   'key'   => $this->cfdi->obtenKey(),
    // );

    // // Lama el metodo cancelar para que realiza la peticion al webservice.
    // $result = $this->facturartebarato_api->cancelar($params);

    // if ($result->data->status_uuid == '201' || $result->data->status_uuid == '202')
    // {
      $this->db->update('proveedores_facturacion',
        array('status' => 'ca', 'status_timbrado' => 'ca'),
        "id_factura = {$idFactura}"
      );

    //   // Regenera el PDF de la factura.
    //   // $pathDocs = $this->documentos_model->creaDirectorioDocsCliente($factura['info']->empresa->nombre_fiscal, $factura['info']->serie, $factura['info']->folio);
    //   $pathDocs = $this->creaDirectorio($factura['info']->id_proveedor, $factura['info']->serie, $factura['info']->folio);
    //   $this->generaFacturaPdf($idFactura, $pathDocs);

    //   $this->enviarEmail($idFactura);
    // }else
    // {
    //   $result->data->status_uuid = 'error';
    // }

    return array('msg' => '201');
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

    public function getTicketInfo($ticket, $areaId)
    {
      // Obtiene el total de productos del ticket especificado.
      $total_productos = $this->db
        ->select('COUNT(pfp.id_bascula) as total_productos')
        ->from('proveedores_facturacion_productos pfp')
        ->join('bascula ba', 'pfp.id_bascula = ba.id_bascula', 'inner')
        ->join('proveedores_facturacion pf', 'pfp.id_factura = pf.id_factura', 'inner')
        ->where('ba.id_area', $areaId)
        ->where('ba.folio', $ticket)
        ->where('pf.status !=', 'ca')
        ->where('pf.status !=', 'b')
        ->get()->row()->total_productos;

      // Si el total de productos es 0 significa que el ticket esta disponible.
      if (intval($total_productos) === 0)
      {
        $sql_res = $this->db
          ->select("bc.*, c.nombre as calidad, b.folio")
          ->from("bascula_compra AS bc")
          ->join("bascula AS b", "b.id_bascula = bc.id_bascula", "inner")
          ->join("calidades AS c", "c.id_calidad = bc.id_calidad", "inner")
          ->where("b.folio", $ticket)
          ->where("b.tipo", 'en')
          ->where("b.status", 't')
          ->where("b.id_area", $areaId)
          ->order_by('num_registro', 'ASC')
          ->get();

        if ($sql_res->num_rows() > 0)
          return $sql_res->result();
      }

      return false;
    }

    /**
   * Crea el directorio por cliente donde se guardara los documentos.
   *
   * @param  string $idProveedor
   * @param  string $serieFactura
   * @param  string $folioFactura
   * @return string
   */
  public function creaDirectorio($idProveedor, $serieFactura, $folioFactura)
  {
    $path = APPPATH.'media/cfdi/proveedores/';

    if ( ! file_exists($path))
    {
      // echo $path.'<br>';
      mkdir($path, 0777);
    }

    $path .= strtoupper($idProveedor).'/';
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
          'id'          => $factura['info']->id_proveedor,
          'table'       => 'proveedores',
          'comprobante' => array('serie' => $factura['info']->serie, 'folio' => $factura['info']->folio)
        );

        $fecha = explode('-', $factura['info']->fecha);
        $ano   = $fecha[0];
        $mes   = strtoupper(MyString::mes(floatval($fecha[1])));
        $rfc   = $factura['info']->proveedor->rfc;
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

        // $cliente = strtoupper($factura['info']->empresa->nombre_fiscal);
        $idProveedor = strtoupper($factura['info']->id_proveedor);
        $fecha   = explode('-', $factura['info']->fecha);
        $ano     = $fecha[0];
        $mes     = strtoupper(MyString::mes(floatval($fecha[1])));
        $serie   = $factura['info']->serie !== '' ? $factura['info']->serie.'-' : '';
        $folio   = $factura['info']->folio;

        $pathDocs = APPPATH."media/cfdi/proveedores/{$idProveedor}/{$ano}/{$mes}/FACT-{$serie}{$folio}/";

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
        if ( ! is_null($factura['info']->proveedor->email) && ! empty($factura['info']->empresa->email))
        {
          //////////////////
          // Datos Correo //
          //////////////////

            $asunto = "Ha recibido una COMPROBANTE FISCAL DIGITAL de {$factura['info']->proveedor->nombre_fiscal}";

            $tipoFactura = is_null($factura['info']->id_nc) ? 'Factura': 'Nota de Crédito';

            // Si la factura esta timbrada
            if ($factura['info']->status_timbrado === "t")
            {
                $altBody = "Estimado Cliente: {$factura['info']->empresa->nombre_fiscal}. Usted está recibiendo un comprobante fiscal digital ({$tipoFactura} {$factura['info']->serie}-{$factura['info']->folio}) de
                {$factura['info']->proveedor->nombre_fiscal}]";
                $body = "
                <p>Estimado Cliente: <strong>{$factura['info']->empresa->nombre_fiscal}</strong></p>
                <p>Usted está recibiendo un comprobante fiscal digital ({$tipoFactura} {$factura['info']->serie}-{$factura['info']->folio}) de {$factura['info']->proveedor->nombre_fiscal}</p>
                ";
            }
            elseif ($factura['info']->status_timbrado === "ca")
            {
                $altBody = "HEMOS CANCELADO EL COMPROBANTE FISCAL DIGITAL {$tipoFactura} {$factura['info']->serie}-{$factura['info']->folio}, HA QUEDADO SIN EFECTOS FISCALES PARA SU EMPRESA, POR LO QUE PEDIMOS ELIMINARLO Y NO INCLUIRLO EN SU CONTABILIDAD, YA QUE PUEDE REPRESENTAR UN PROBLEMA FISCAL PARA USTED O SU EMPRESA CUANDO EL SAT REALICE UNA FUTURA AUDITORIA EN SU CONTABILIDAD.";
                $body = "
                <p>Estimado Cliente: <strong>{$factura['info']->empresa->nombre_fiscal}</strong></p>
                <p>HEMOS CANCELADO EL COMPROBANTE FISCAL DIGITAL {$tipoFactura} {$factura['info']->serie}-{$factura['info']->folio}, HA QUEDADO SIN EFECTOS FISCALES PARA SU EMPRESA, POR LO QUE PEDIMOS ELIMINARLO Y NO INCLUIRLO EN SU CONTABILIDAD, YA QUE PUEDE REPRESENTAR UN PROBLEMA FISCAL PARA USTED O SU EMPRESA CUANDO EL SAT REALICE UNA FUTURA AUDITORIA EN SU CONTABILIDAD.</p>
                ";
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
                  <br><strong>Sistema de facturacion electrónica - Facturacion "'.$factura['info']->proveedor->nombre_fiscal.'"</strong></p>
                ';

            //////////////////////
            // Datos del Emisor //
            //////////////////////

            $correoEmisorEm = "empaquesanjorge@hotmail.com"; // Correo con el q se emitira el correo.
            $nombreEmisor   = $factura['info']->proveedor->nombre_fiscal;
            $correoEmisor   = "postmaster@empaquesanjorge.com"; // Correo para el auth. empaquesanjorgemx@gmail.com (mandrill)
            $contrasena     = "2b9f25bc4737f34edada0b29a56ff682"; // Contraseña de $correEmisor S4nj0rg3V14n3y

            ////////////////////////
            // Datos del Receptor //
            ////////////////////////

            $correoDestino = array();

            if ($_POST['pextras'] !== '')
              $correoDestino += explode(',', $_POST['pextras']);

            if (isset($_POST['emails']))
            {
              foreach ($_POST['emails'] as $email)
              {
                array_push($correoDestino, $email);
              }
            }

            $nombreDestino = strtoupper($factura['info']->empresa->nombre_fiscal);

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
                'cc'             => $factura['info']->proveedor->email,
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
                    $idProveedor = strtoupper($factura['info']->id_proveedor);
                    $fecha   = explode('-', $factura['info']->fecha);
                    $ano     = $fecha[0];
                    $mes     = strtoupper(MyString::mes(floatval($fecha[1])));
                    $serie   = $factura['info']->serie !== '' ? $factura['info']->serie.'-' : '';
                    $folio   = $factura['info']->folio;

                    // $pathDocs = APPPATH."documentos/CLIENTES/{$empresa}/{$ano}/{$mes}/FACT-{$serie}{$folio}/";
                    $pathDocs = APPPATH."media/cfdi/proveedores/{$idProveedor}/{$ano}/{$mes}/FACT-{$serie}{$folio}/";

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
        'id_empresa'          => $this->input->post('did_empresa'),
        'id_proveedor'        => $this->input->post('did_proveedor'),
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
        'plazo_credito'       => $_POST['dcondicion_pago'] === 'co' ? 0 : floatval($this->input->post('dplazo_credito')),
        'observaciones'       => $this->input->post('dobservaciones'),
        'status'              => isset($_POST['timbrar']) ? 'p' : 'b',
        'retencion_iva'       => $this->input->post('total_retiva'),
      );

      // Si el tipo de comprobante es "egreso" o una nota de credito.
      if ($_POST['dtipo_comprobante'] === 'egreso')
        $datosFactura['id_nc'] = $_GET['id'];

      // Inserta los datos de la factura y obtiene el Id.
      $this->db->update('proveedores_facturacion', $datosFactura, array('id_factura' => $idBorrador));

      // Productos
      $productosFactura   = array(); // Productos para la Factura

      $anterior = 0;
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

          if ($anterior != floatval($_POST['prod_did_prod'][$key]))
          {
            $productosFactura[] = array(
              'id_factura' => $idBorrador,
              'id_bascula' => $_POST['prod_did_prod'][$key],
              'num_row'    => intval($key),
            );
          }

          $anterior = $_POST['prod_did_prod'][$key];
        }
      }

      $this->db->delete('proveedores_facturacion_productos', array('id_factura' => $idBorrador));

      if (count($productosFactura) > 0)
        $this->db->insert_batch('proveedores_facturacion_productos', $productosFactura);
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
        ->from('proveedores_facturacion')
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

    $query = BDUtil::pagination("SELECT psf.id_serie_folio, psf.id_proveedor, psf.serie, psf.no_aprobacion, psf.folio_inicio,
          psf.folio_fin, psf.leyenda, psf.leyenda1, psf.leyenda2, psf.ano_aprobacion, p.nombre_fiscal AS proveedor
        FROM proveedores_series_folios AS psf
        INNER JOIN proveedores AS p ON p.id_proveedor = psf.id_proveedor
                {$sql}
        ORDER BY psf.serie", $params, true);

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

    $res = $this->db->select('psf.id_serie_folio, psf.id_proveedor, psf.serie, psf.no_aprobacion, psf.folio_inicio,
        psf.folio_fin, psf.leyenda, psf.leyenda1, psf.leyenda2, psf.ano_aprobacion, psf.es_nota_credito, p.nombre_fiscal AS proveedor')
      ->from('proveedores_series_folios AS psf')
      ->join('proveedores AS p', 'p.id_proveedor = psf.id_proveedor', 'inner')
      ->where('psf.id_serie_folio', $id_serie_folio)->get()->result();
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
        'id_proveedor'   => $this->input->post('fid_proveedor'),
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

    $this->db->insert('proveedores_series_folios', $data);

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
    //  if($upload_res[0] == false)
    //    return array(false, $upload_res[1]);
    //  $path_img = $upload_res[1]['file_name']; //APPPATH.'images/series_folios/'.$upload_res[1]['file_name'];

    //  /*$old_img = $this->db->select('imagen')->from('facturas_series_folios')->where('id_serie_folio',$id_serie_folio)->get()->row()->imagen;

    //  UploadFiles::deleteFile($old_img);*/
    // }

    $data = array(
        'id_proveedor'   => $this->input->post('fid_proveedor'),
        'serie'          => strtoupper($this->input->post('fserie')),
        'no_aprobacion'  => $this->input->post('fno_aprobacion'),
        'folio_inicio'   => $this->input->post('ffolio_inicio'),
        'folio_fin'      => $this->input->post('ffolio_fin'),
        'ano_aprobacion' => $this->input->post('fano_aprobacion')
    );

    // if($path_img!='')
    //  $data['imagen'] = $path_img;

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

    $this->db->update('proveedores_series_folios', $data, array('id_serie_folio'=>$id_serie_folio));

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
    public function getFacProveedoresAjax()
    {
        $sql = '';

        if ($this->input->get('did_empresa')) {
          $sql .= " AND id_empresa = {$_GET['did_empresa']}";
        }

        $res = $this->db->query("
            SELECT p.id_proveedor, p.nombre_fiscal, p.cer_caduca, p.cfdi_version, p.cer_org
            FROM proveedores AS p
            WHERE lower(nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' AND
                  status = 'ac' {$sql}
            ORDER BY nombre_fiscal ASC
            LIMIT 20");

        $this->load->library('cfdi');

        $response = array();
        if($res->num_rows() > 0){
          foreach($res->result() as $itm){

            if ($itm->cer_org !== '')
              $itm->no_certificado = $this->cfdi->obtenNoCertificado($itm->cer_org);

            $response[] = array(
              'id'    => $itm->id_proveedor,
              'label' => $itm->nombre_fiscal,
              'value' => $itm->nombre_fiscal,
              'item'  => $itm,
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
            $datos = array($item->fecha, $item->serie, $item->folio, $item->nombre_fiscal, $item->empresa, $condicion_pago, $estado, MyString::formatoNumero($item->total));
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

        $datos = array($item->codigo, $item->producto, $item->total_cantidad, MyString::formatoNumero($item->total_importe));

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false);
      }

      $pdf->Output('Reporte_Ventas_Productos.pdf', 'I');
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

        // $pdf->SetXY(30, 2);
        // $pdf->Image(APPPATH.'images/logo.png');

        //////////////////////////
        // Rfc y Regimen Fiscal //
        //////////////////////////

        // 0, 171, 72 = verde

        $pdf->SetFont('helvetica','B', 18);
        // $pdf->SetFillColor(0, 171, 72);
        $pdf->SetTextColor(255, 255, 255);
        // $pdf->SetXY(0, 0);
        // $pdf->Cell(108, 15, "Factura Electrónica (CFDI)", 0, 0, 'C', 1);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(0, $pdf->GetY() + 3);
        $pdf->Cell(108, 14, "RFC: {$xml->Emisor[0]['rfc']}", 0, 0, 'C', 0);

        $pdf->SetFont('helvetica','B', 14);
        $pdf->SetFillColor(242, 242, 242);
        $pdf->SetTextColor(0, 171, 72);
        $pdf->SetXY(0, $pdf->GetY() + 14);
        $pdf->Cell(108, 8, "Régimen Fiscal:", 0, 0, 'L', 1);

        $pdf->SetFont('helvetica','', 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(0, $pdf->GetY() + 8);
        $pdf->MultiCell(108, 6, $xml->Emisor->RegimenFiscal[0]['Regimen'], 0, 'C', 0);

        /////////////////////////////////////
        // Folio Fisca, CSD, Lugar y Fecha //
        /////////////////////////////////////

        $pdf->SetFont('helvetica','B', 14);
        $pdf->SetFillColor(242, 242, 242);
        $pdf->SetTextColor(0, 171, 72);
        $pdf->SetXY(109, 0);
        $pdf->Cell(108, 8, "Folio Fiscal:", 0, 0, 'R', 1);

        $pdf->SetXY(109, 0);
        $pdf->Cell(50, 8, $factura['info']->id_nc === null ? 'Factura' : 'Nota de Crédito', 0, 0, 'L', 1);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(109, 10);
        $pdf->Cell(108, 8, $xml->Complemento->TimbreFiscalDigital[0]['UUID'], 0, 0, 'C', 0);

        $pdf->SetTextColor(0, 171, 72);
        $pdf->SetXY(109, $pdf->GetY() + 8);
        $pdf->Cell(108, 8, "No de Serie del Certificado del CSD:", 0, 0, 'R', 1);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(109, $pdf->GetY() + 8);
        $pdf->Cell(108, 8, $xml->Complemento->TimbreFiscalDigital[0]['noCertificadoSAT'], 0, 0, 'C', 0);

        $pdf->SetFillColor(242, 242, 242);
        $pdf->SetTextColor(0, 171, 72);
        $pdf->SetXY(109, $pdf->GetY() + 9);
        $pdf->Cell(108, 8, "Lugar. fecha y hora de emisión:", 0, 0, 'R', 1);

        $pdf->SetFont('helvetica','', 12);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(109, $pdf->GetY() + 8);

        $pais   = strtoupper($xml->Emisor->DomicilioFiscal[0]['pais']);
        $estado = strtoupper($xml->Emisor->DomicilioFiscal[0]['estado']);
        $fecha = $xml[0]['fecha'];

        $pdf->Cell(108, 8, "{$pais} {$estado} {$fecha}", 0, 0, 'R', 0);

        //////////////////
        // Rfc Receptor //
        //////////////////

        $pdf->SetFillColor(0, 171, 72);
        $pdf->SetXY(0, $pdf->GetY() + 13);
        $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

        $pdf->SetFont('helvetica','B', 13);
        $pdf->SetFillColor(242, 242, 242);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(0, $pdf->GetY() + 1);
        $pdf->Cell(216, 8, "RFC Receptor:", 0, 0, 'L', 1);

        $pdf->SetFont('helvetica','B', 13);
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(0, $pdf->GetY() + 8);
        $pdf->Cell(216, 8, $xml->Receptor[0]['rfc'], 0, 0, 'C', 0);

        ///////////////
        // Productos //
        ///////////////

        $pdf->SetFillColor(0, 171, 72);
        $pdf->SetXY(0, $pdf->GetY() + 8);
        $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

        $pdf->SetXY(0, $pdf->GetY());
        $aligns = array('C', 'C', 'C', 'C','C');
        $widths = array(30, 35, 91, 30, 30);
        $header = array('Cantidad', 'Unidad de Medida', 'Descripcion', 'Precio Unitario', 'Importe');

        $conceptos = current($xml->Conceptos);

        // for ($i=0; $i < 3; $i++)
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

            $pdf->SetFont('Arial', 'B', 10);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFillColor(242, 242, 242);
            $pdf->SetX(0);
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->Row($header, true);
          }

          $pdf->SetFont('Arial', '', 10);
          $pdf->SetTextColor(0,0,0);

          $pdf->SetX(0);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row(array(
            $item[0]['cantidad'],
            $item[0]['unidad'],
            $item[0]['descripcion'],
            MyString::formatoNumero($item[0]['valorUnitario'], 3),
            MyString::formatoNumero($item[0]['importe'], 3),
          ), false);
        }

        /////////////
        // Totales //
        /////////////

        if($pdf->GetY() + 30 >= $pdf->limiteY) //salta de pagina si exede el max
            $pdf->AddPage();

        $pdf->SetFillColor(0, 171, 72);
        $pdf->SetXY(0, $pdf->GetY());
        $pdf->Cell(216, 1, "", 0, 0, 'L', 1);

        $pdf->SetFillColor(242, 242, 242);
        $pdf->SetXY(0, $pdf->GetY() + 1);
        $pdf->Cell(156, 30, "", 1, 0, 'L', 1);

        $pdf->SetFont('helvetica','B', 9);
        $pdf->SetXY(1, $pdf->GetY() + 1);
        $pdf->Cell(154, 8, "Total con letra:", 0, 0, 'L', 1);

        $pdf->SetFont('helvetica', '', 11);
        $pdf->SetXY(0, $pdf->GetY() + 8);
        $pdf->MultiCell(156, 8, $factura['info']->total_letra, 0, 'C', 0);

        $pdf->SetFont('helvetica','B', 10);
        $pdf->SetXY(1, $pdf->GetY() + 6);
        $pdf->Cell(78, 5, $xml[0]['formaDePago'], 0, 0, 'L', 1);

        $pdf->SetFont('helvetica','B', 10);
        $pdf->SetXY(78, $pdf->GetY());
        $pdf->Cell(78, 5, "Pago en {$xml[0]['metodoDePago']}", 0, 0, 'L', 1);


        $pdf->SetFont('helvetica','B', 10);
        $pdf->SetXY(156, $pdf->GetY() - 23);
        $pdf->Cell(30, 6, "Subtotal", 1, 0, 'C', 1);

        $pdf->SetXY(186, $pdf->GetY());
        $pdf->Cell(30, 6, MyString::formatoNumero($xml[0]['subTotal']), 1, 0, 'C', 1);

        // Traslados | IVA
        $ivas = current($xml->Impuestos->Traslados);
        $traslado11 = 0;
        $traslado16 = 0;
        foreach ($ivas as $key => $iva)
        {
          if ($iva[0]['tasa'] == '11')
            $traslado11 = $iva[0]['importe'];
          elseif ($iva[0]['tasa'] == '16')
            $traslado16 = $iva[0]['importe'];
        }

        $pdf->SetXY(156, $pdf->GetY() + 6);
        $pdf->Cell(30, 6, "IVA(11%)", 1, 0, 'C', 1);

        $pdf->SetXY(186, $pdf->GetY());
        $pdf->Cell(30, 6,MyString::formatoNumero($traslado11, 2), 1, 0, 'C', 1);

        $pdf->SetXY(156, $pdf->GetY() + 6);
        $pdf->Cell(30, 6, "IVA(16%)", 1, 0, 'C', 1);

        $pdf->SetXY(186, $pdf->GetY());
        $pdf->Cell(30, 6,MyString::formatoNumero($traslado16, 2), 1, 0, 'C', 1);

        $pdf->SetXY(156, $pdf->GetY() + 6);
        $pdf->Cell(30, 6, "IVA Retenido", 1, 0, 'C', 1);

        $pdf->SetXY(186, $pdf->GetY());
        $pdf->Cell(30, 6,MyString::formatoNumero($xml->Retenciones->Retencion[0]['importe'], 2), 1, 0, 'C', 1);

        $pdf->SetXY(156, $pdf->GetY() + 6);
        $pdf->Cell(30, 6, "TOTAL", 1, 0, 'C', 1);

        $pdf->SetXY(186, $pdf->GetY());
        $pdf->Cell(30, 6,MyString::formatoNumero($xml[0]['total'], 2), 1, 0, 'C', 1);

        ///////////////////
        // Observaciones //
        ///////////////////

        $pdf->SetXY(0, $pdf->GetY() + 6);

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

        ////////////////////
        // Timbrado Datos //
        ////////////////////

        if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
            $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY(10, $pdf->GetY());
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(196));
        $pdf->Row(array('Sello Digital del CFDI:'), false, 0);

        $pdf->SetFont('helvetica', '', 9);
        $pdf->SetY($pdf->GetY() - 3);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(196));
        $pdf->Row(array($xml->Complemento->TimbreFiscalDigital[0]['selloCFD']), false, 0);

        if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
            $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY(10, $pdf->GetY());
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(196));
        $pdf->Row(array('Sello Digital del SAT:'), false, 0);

        $pdf->SetFont('helvetica', '', 9);
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

        QRcode::png($code, APPPATH.'media/qrtemp.png', 'H', 4);

        if($pdf->GetY() + 50 >= $pdf->limiteY) //salta de pagina si exede el max
            $pdf->AddPage();

        $pdf->SetXY(0, $pdf->GetY());
        $pdf->Image(APPPATH.'media/qrtemp.png');

        // Elimina el QR generado temporalmente.
        unlink(APPPATH.'media/qrtemp.png');

        ////////////////////
        // Timbrado Datos //
        ////////////////////

        $pdf->SetFont('helvetica', 'B', 9);
        $pdf->SetXY(60, $pdf->GetY() - 59);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(145));
        $pdf->Row(array('Cadena Original del complemento de certificación digital del SAT:'), false, 0);

        $pdf->SetFont('helvetica', '', 9);
        $cadenaOriginalSAT = "||{$xml->Complemento->TimbreFiscalDigital[0]['version']}|{$xml->Complemento->TimbreFiscalDigital[0]['UUID']}|{$xml->Complemento->TimbreFiscalDigital[0]['FechaTimbrado']}|{$xml->Complemento->TimbreFiscalDigital[0]['selloCFD']}|{$xml->Complemento->TimbreFiscalDigital[0]['noCertificadoSAT']}||";
        $pdf->SetXY(60, $pdf->GetY() - 3);
        $pdf->Row(array($cadenaOriginalSAT), false, 0);

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetFillColor(242, 242, 242);
        $pdf->SetTextColor(0, 171, 72);
        $pdf->SetXY(60, $pdf->GetY() + 1);
        $pdf->Cell(80, 8, "No de Serie del Certificado del SAT:", 0, 0, 'R', 1);

        $pdf->SetXY(140, $pdf->GetY());
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(65, 8, $xml->Complemento->TimbreFiscalDigital[0]['noCertificadoSAT'], 0, 0, 'C', 0);

        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->SetFillColor(242, 242, 242);
        $pdf->SetTextColor(0, 171, 72);
        $pdf->SetXY(60, $pdf->GetY() + 16);
        $pdf->Cell(80, 8, "Fecha y hora de certificación:", 0, 0, 'R', 1);

        $pdf->SetXY(140, $pdf->GetY());
        $pdf->SetTextColor(0, 0, 0);
        $pdf->Cell(65, 8, $xml->Complemento->TimbreFiscalDigital[0]['FechaTimbrado'], 0, 0, 'C', 0);


        //------------ IMAGEN CANDELADO --------------------

        if($factura['info']->status === 'ca'){
          $pdf->Image(APPPATH.'/images/cancelado.png', 20, 40, 190, 190, "PNG");
        }

        if ($path)
          $pdf->Output($path.'Factura.pdf', 'F');
        else
          $pdf->Output('Factura', 'I');
    }
}