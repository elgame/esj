<?php
class gastos_model extends privilegios_model{

  function __construct(){
    parent::__construct();
  }

  /**
   * Argegar un gasto.
   *
   * @param  array $data
   * @return array
   */
  public function agregar($data)
  {
    // datos del gasto.
    $datos = array(
      'id_empresa'      => $data['empresaId'],
      'id_sucursal'     => (is_numeric($_POST['sucursalId'])? $_POST['sucursalId']: NULL),
      'id_proveedor'    => $data['proveedorId'],
      'id_empleado'     => $this->session->userdata('id_usuario'),
      'serie'           => $data['serie'],
      'folio'           => $data['folio'],
      'condicion_pago'  => $data['condicionPago'],
      'plazo_credito'   => $data['plazoCredito'] !== '' ? $data['plazoCredito'] : 0,
      'tipo_documento'  => $data['tipo_documento'],
      'fecha'           => str_replace('T', ' ', $data['fecha']), // fecha de la poliza y cuentas pagar
      'fecha_factura'   => str_replace('T', ' ', $data['fecha_factura']), // fecha real de la factura
      'subtotal'        => $data['subtotal'],
      'importe_iva'     => $data['iva'],
      'total'           => $data['total'],
      'concepto'        => $data['concepto'],
      'isgasto'         => 't',
      'status'          => $data['condicionPago'] ===  'co' ? 'p' : 'p',
      'retencion_iva'   => $data['ret_iva'],
      'retencion_isr'   => $data['ret_isr'],
      'importe_ieps'    => $data['ieps'],
      'id_area'         => ($data['areaId']? $data['areaId']: NULL),
      'id_cat_codigos'  => (!empty($data['codigoAreaId'])? $data['codigoAreaId']: NULL),
      // 'id_rancho'       => ($data['ranchoId']? $data['ranchoId']: NULL),
      // 'id_centro_costo' => ($data['centroCostoId']? $data['centroCostoId']: NULL),
      'id_activo'       => ($data['activoId']? $data['activoId']: NULL),
      'intangible'      => (isset($data['intangible']) && $data['intangible'] == 'si'? 't': 'f'),

      'id_proyecto'     => (!empty($data['proyecto'])? $data['proyecto']: NULL),
    );
    $datos['uuid']           = $this->input->post('uuid');
    $datos['no_certificado'] = $this->input->post('noCertificado');

    //Cuenta espesifica al gasto
    if(is_numeric($data['did_cuentacpi']))
        $datos['cuenta_cpi_gst'] = $data['did_cuentacpi'];

    //si se registra a un vehiculo
    if (isset($data['es_vehiculo']))
    {
      $datos['tipo_vehiculo'] = $data['tipo_vehiculo'];
      $datos['id_vehiculo'] = $data['vehiculoId'];
    }

    // //si es contado, se verifica que la cuenta tenga saldo
    // if ($datos['condicion_pago'] == 'co')
    // {
    //   $this->load->model('banco_cuentas_model');
    //   $cuenta = $this->banco_cuentas_model->getCuentas(false, $_POST['dcuenta']);
    //   if ($cuenta['cuentas'][0]->saldo < $datos['total'])
    //     return array('passes' => false, 'msg' => 30);
    // }

    // // Realiza el upload del XML.
    // if ($xml && $xml['tmp_name'] !== '')
    // {
    //   $this->load->library("my_upload");
    //   $this->load->model('proveedores_model');

    //   $proveedor = $this->proveedores_model->getProveedorInfo($datos['id_proveedor']);
    //   $path      = $this->creaDirectorioProveedorCfdi($proveedor['info']->nombre_fiscal);

    //   $xmlName   = ($_POST['serie'] !== '' ? $_POST['serie'].'-' : '') . $_POST['folio'].'.xml';

    //   $config_upload = array(
    //     'upload_path'     => $path,
    //     'allowed_types'   => '*',
    //     'max_size'        => '2048',
    //     'encrypt_name'    => FALSE,
    //     'file_name'       => $xmlName,
    //   );
    //   $this->my_upload->initialize($config_upload);

    //   $xmlData = $this->my_upload->do_upload('xml');

    //   $xmlFile     = explode('application', $xmlData['full_path']);
    //   $datos['xml'] = 'application'.$xmlFile[1];
    // }

    // inserta la compra
    $this->db->insert('compras', $datos);

    // obtiene el id de la compra insertada.
    $compraId = $this->db->insert_id('compras_id_compra_seq');

    // Bitacora
    $this->bitacora_model->_insert('compras', $compraId,
                                    array(':accion'     => 'el gasto', ':seccion' => 'compras',
                                          ':folio'      => $datos['serie'].$datos['folio'],
                                          ':id_empresa' => $datos['id_empresa'],
                                          ':empresa'    => 'en '.$this->input->post('empresa')));

    // Si es un gasto son requeridos los campos de catálogos
    // Inserta los ranchos
    if (isset($_POST['ranchoId']) && count($_POST['ranchoId']) > 0) {
      foreach ($_POST['ranchoId'] as $keyr => $id_rancho) {
        $this->db->insert('compras_rancho', [
          'id_rancho' => $id_rancho,
          'id_compra' => $compraId,
          'num'       => count($_POST['ranchoId'])
        ]);
      }
    }

    // Inserta los centros de costo
    if (isset($_POST['centroCostoId']) && count($_POST['centroCostoId']) > 0) {
      foreach ($_POST['centroCostoId'] as $keyr => $id_centro_costo) {
        $this->db->insert('compras_centro_costo', [
          'id_centro_costo' => $id_centro_costo,
          'id_compra'       => $compraId,
          'num'             => count($_POST['centroCostoId'])
        ]);
      }
    }

    $respons = array();
    // //si es contado, se registra el abono y el retiro del banco
    // if ($datos['condicion_pago'] == 'co')
    // {
    //   $this->load->model('cuentas_pagar_model');
    //   $data_abono = array('fecha'             => $data['fecha'],
    //                     'concepto'            => substr($data['concepto'], 0, 119),
    //                     'total'               => $data['total'],
    //                     'id_cuenta'           => $this->input->post('dcuenta'),
    //                     'ref_movimiento'      => $this->input->post('dreferencia'),
    //                     'id_cuenta_proveedor' => $this->input->post('fcuentas_proveedor') );
    //   $_GET['tipo'] = 'f';
    //   $respons = $this->cuentas_pagar_model->addAbono($data_abono, $compraId);
    // }

    //si se registra a un vehiculo
    if (isset($data['es_vehiculo']))
    {
      //si es de tipo gasolina se registra los litros
      if($data['tipo_vehiculo'] == 'g')
      {
        $this->db->insert('compras_vehiculos_gasolina', array(
          'id_compra'  => $compraId,
          'kilometros' => $data['dkilometros'],
          'litros'     => $data['dlitros'],
          'precio'     => $data['dprecio'],
          ));
      }
    }

    //Si el gasto trae ordenes logadas
    if (isset($data['ordenes']))
    {
      foreach ($data['ordenes'] as $key => $orden)
      {
        $this->db->insert('compras_facturas', array(
          'id_compra'  => $compraId,
          'id_orden' => $orden,
          ));
        $this->db->update('compras_ordenes', array('status' => 'f'), array('id_orden' => $orden));
      }
    }

    return array('passes' => true, 'id_compra' => $compraId, 'banco' => $respons);
  }

  // public function cancelar($compraId)
  // {
  //   // cambia el status de la compra a cancelado.
  //   $this->db->update('compras', array('status' => 'ca'), array('id_compra' => $compraId));

  //   // obtiene las ordenes de compra que estan ligadas a la compra.
  //   $ordenes = $this->db->select('id_orden')->from('compras_facturas')->where('id_compra', $compraId)->get()->result();

  //   // recorre las ordenes y les cambia el status a aceptadas para que esten
  //   // disponibles y puedan ser ligadas a otra compra.
  //   foreach ($ordenes as $orden)
  //   {
  //     $this->db->update('compras_ordenes', array('status' => 'a'), array('id_orden' => $orden->id_orden));
  //   }

  //   return true;
  // }

  public function updateXml($compraId, $proveedorId, $xml)
  {
    $compra = array(
      'subtotal'      => MyString::float($this->input->post('subtotal')),
      'importe_iva'   => MyString::float($this->input->post('iva')),
      'retencion_iva' => MyString::float($this->input->post('ret_iva')),
      'retencion_isr' => MyString::float($this->input->post('ret_isr')),
      'total'         => MyString::float($this->input->post('total')),
      'fecha'         => $this->input->post('fecha'), // fecha de la poliza y cuentas pagar
      'fecha_factura' => $this->input->post('fecha_factura'), // fecha real de la factura
    );

    // Realiza el upload del XML.
    if ($xml && $xml['tmp_name'] !== '')
    {
      $this->load->library("my_upload");
      $this->load->model('proveedores_model');

      $proveedor = $this->proveedores_model->getProveedorInfo($proveedorId);
      $path      = $this->creaDirectorioProveedorCfdi($proveedor['info']->nombre_fiscal);

      $xmlName   = ($_POST['serie'] !== '' ? $_POST['serie'].'-' : '') . $_POST['folio'].'.xml';

      $config_upload = array(
        'upload_path'     => $path,
        'allowed_types'   => '*',
        'max_size'        => '2048',
        'encrypt_name'    => FALSE,
        'file_name'       => $xmlName,
      );
      $this->my_upload->initialize($config_upload);

      $xmlData = $this->my_upload->do_upload('xml');

      $xmlFile     = explode('application', $xmlData['full_path']);

      $compra['xml'] = 'application'.$xmlFile[1];
    }

    // Bitacora
    $this->load->model('compras_model');
    $datoscompra = $this->compras_model->getInfoCompra($compraId);
    $id_bitacora = $this->bitacora_model->_update('compras', $compraId, $compra,
                              array(':accion'       => 'la compra', ':seccion' => 'compras',
                                    ':folio'        => $datoscompra['info']->serie.$datoscompra['info']->folio,
                                    ':id_empresa'   => $datoscompra['info']->id_empresa,
                                    ':empresa'      => 'en '.$datoscompra['info']->empresa->nombre_fiscal,
                                    ':id'           => 'id_compra',
                                    ':titulo'       => 'Compra'));

    $compra['uuid']           = $this->input->post('uuid');
    $compra['no_certificado'] = $this->input->post('noCertificado');

    $this->db->update('compras', $compra, array('id_compra' => $compraId));
  }


  public function saveLigarFactura($datos)
  {
    $data = array();
    foreach ($datos['idclasif'] as $key => $value)
    {
      $data[] = array(
        'id_compra'        => $datos['id_compra'],
        'id_factura'       => ($datos['idfactura'][$key]>0? $datos['idfactura'][$key]: NULL),
        'id_clasificacion' => $value,
        'fecha'            => (isset($datos['fecha'][$key]{0})? $datos['fecha'][$key]: NULL),
        'id_cliente'       => (isset($datos['id_cliente'][$key]{0})? $datos['id_cliente'][$key]: NULL),
        'costo'            => (isset($datos['costo'][$key]{0})? $datos['costo'][$key]: NULL),
        );
    }
    if(count($data) > 0){
      $this->db->delete('compras_facturacion_prodc', "id_compra = {$datos['id_compra']}");
      $this->db->insert_batch('compras_facturacion_prodc', $data);
    }
  }

  public function getFacturasLigadas($params, $multiple=false)
  {
    $sql = $multiple? "cf.id_compra IN(".implode(',', $params['idc']).")": "cf.id_compra = {$params['idc']}";
    $result = $this->db->query("SELECT cf.id_compra, f.id_empresa, f.id_factura, f.serie, f.folio, Date(f.fecha) AS fecha,
            c.nombre_fiscal AS cliente, fp.id_clasificacion, fp.nombre, c.id_cliente, ffp.importe AS importe, ffp.iva AS iva,
            ffp.pol_seg, ffp.certificado, ffp.num_operacion
            FROM compras_facturacion_prodc AS cf
              INNER JOIN facturacion AS f ON f.id_factura = cf.id_factura
              INNER JOIN clasificaciones AS fp ON cf.id_clasificacion = fp.id_clasificacion
              INNER JOIN clientes AS c ON f.id_cliente = c.id_cliente
              INNER JOIN (
                SELECT fsc.id_factura, fsc.id_clasificacion, fsc.folio, Sum(ffp.importe) AS importe, Sum(iva) AS iva, string_agg(fsc.id_proveedor::text, ', ') AS id_proveedor,
                  string_agg(fsc.pol_seg, ', ') AS pol_seg, string_agg(fsc.certificado, ', ') AS certificado,
                  string_agg(fsc.bultos::text, ', ') AS bultos, string_agg(fsc.num_operacion, ', ') AS num_operacion
                FROM (
                  SELECT id_factura, id_clasificacion, folio, string_agg(id_proveedor::text, ', ') AS id_proveedor,
                  string_agg(pol_seg, ', ') AS pol_seg, string_agg(certificado, ', ') AS certificado,
                  string_agg(bultos::text, ', ') AS bultos, string_agg(num_operacion, ', ') AS num_operacion
                  FROM facturacion_seg_cert
                  GROUP BY id_factura, id_clasificacion, folio
                ) fsc
                INNER JOIN (
                  SELECT id_factura, id_clasificacion, Sum(importe) AS importe, Sum(iva) AS iva
                  FROM facturacion_productos
                  GROUP BY id_factura, id_clasificacion
                ) AS ffp ON (ffp.id_factura = fsc.id_factura AND ffp.id_clasificacion = fsc.id_clasificacion)
                GROUP BY fsc.id_factura, fsc.id_clasificacion, fsc.folio
              ) AS ffp ON cf.id_factura = ffp.id_factura AND cf.id_clasificacion = ffp.id_clasificacion
            WHERE {$sql}
            GROUP BY cf.id_compra, f.id_factura, fp.id_clasificacion, c.id_cliente, ffp.pol_seg,
              ffp.certificado, ffp.num_operacion, ffp.importe, ffp.iva
            ORDER BY fp.id_clasificacion ASC");
    // $result = $this->db->query("SELECT cf.id_compra, f.id_empresa, f.id_factura, f.serie, f.folio, Date(f.fecha) AS fecha,
    //         c.nombre_fiscal AS cliente, fp.id_clasificacion, fp.nombre, ffp.importe, ffp.iva, c.id_cliente,
    //         string_agg(fsc.pol_seg, ', ') AS pol_seg, string_agg(fsc.certificado, ', ') AS certificado, string_agg(fsc.num_operacion, ', ') AS num_operacion
    //       FROM compras_facturacion_prodc AS cf
    //         INNER JOIN facturacion AS f ON f.id_factura = cf.id_factura
    //         INNER JOIN clasificaciones AS fp ON cf.id_clasificacion = fp.id_clasificacion
    //         INNER JOIN clientes AS c ON f.id_cliente = c.id_cliente
    //         INNER JOIN facturacion_productos AS ffp ON f.id_factura = ffp.id_factura AND fp.id_clasificacion = ffp.id_clasificacion
    //         INNER JOIN facturacion_seg_cert AS fsc ON (cf.id_clasificacion = fsc.id_clasificacion AND f.id_factura = fsc.id_factura)
    //       WHERE {$sql}
    //       GROUP BY cf.id_compra, f.id_factura, fp.id_clasificacion, c.id_cliente, ffp.id_factura, ffp.num_row
    //       ORDER BY fp.id_clasificacion ASC");
    $response = array('ligadas' => array(), 'canceladas' => array());
    if($result->num_rows() > 0)
      $response['ligadas'] = $result->result();

    $result->free_result();

    $result = $this->db->query("SELECT cf.id_compra, '' AS id_empresa, cf.id_factura, '' AS serie, '' AS folio, Date(cf.fecha) AS fecha,
            c.nombre_fiscal AS cliente, fp.id_clasificacion, fp.nombre, cf.costo AS importe, 0 AS iva, c.id_cliente
          FROM compras_facturacion_prodc AS cf
            INNER JOIN clasificaciones AS fp ON cf.id_clasificacion = fp.id_clasificacion
            INNER JOIN clientes AS c ON cf.id_cliente = c.id_cliente
          WHERE {$sql}
          ORDER BY fp.id_clasificacion ASC");
    if($result->num_rows() > 0)
      $response['canceladas'] = $result->result();

    $result->free_result();

    return $response;
  }

  public function getFacturasLibre($datos){
    $sql = '';
    $sql .= isset($datos['id_cliente']{0})? " AND f.id_cliente = {$datos['id_cliente']}": '';
    $sql .= isset($datos['folio']{0})? " AND f.folio = '{$datos['folio']}'": '';
    $sql .= isset($datos['fechaf']{0})? " AND f.fecha >= '{$datos['fechaf']}'": '';
    $result = $this->db->query("SELECT cf.id_compra, f.id_factura, f.serie, f.folio, f.fecha, f.cliente
          FROM compras c
            INNER JOIN compras_facturacion_prodc AS cf ON (c.id_compra = cf.id_compra AND c.status <> 'ca')
            RIGHT JOIN
            (
              SELECT f.id_factura, f.serie, f.folio, Date(f.fecha) AS fecha, c.nombre_fiscal AS cliente
              FROM facturacion AS f
                INNER JOIN facturacion_productos AS fp ON f.id_factura = fp.id_factura
                INNER JOIN clientes AS c ON f.id_cliente = c.id_cliente
              WHERE f.id_empresa = {$datos['id_empresa']} AND fp.id_clasificacion = {$datos['id_clasificacion']}
                {$sql}
              GROUP BY f.id_factura, c.nombre_fiscal
              ORDER BY f.folio DESC
            ) AS f ON f.id_factura = cf.id_factura AND cf.id_clasificacion = {$datos['id_clasificacion']}
          WHERE cf.id_compra IS NULL");
    $response = array();
    if($result->num_rows() > 0)
      $response = $result->result();

    $result->free_result();

    return $response;
  }


  public function print($ordenId, $path = null)
  {
    $this->load->model('compras_areas_model');
    $this->load->model('catalogos_sft_model');
    $this->load->model('proveedores_model');
    $this->load->model('banco_cuentas_model');
    $this->load->model('compras_model');

    $gasto     = $this->compras_model->getInfoCompra($_GET['id'], false);
    $proveedor = $this->proveedores_model->getProveedorInfo($gasto['info']->id_proveedor, true);
    $empresa   = $this->empresas_model->getInfoEmpresa($gasto['info']->id_empresa, true);
    $emp_cuenta = $this->banco_cuentas_model->getCuentaConcentradora($gasto['info']->id_empresa);
    $proveedor_cuentas = $this->proveedores_model->getCuentas($gasto['info']->id_proveedor);
    // echo "<pre>";
    //   var_dump($empresa);
    // echo "</pre>";exit;

    // $orden = $this->info($ordenId, true);
    // $ordenPago = $this->infoPago($ordenId);
    // $ordenHistImp = $this->infoHistNoImpreciones($ordenId);
    // $almacen = $this->almacenes_model->getAlmacenInfo($orden['info'][0]->id_almacen);
    // $proveedor = $this->proveedores_model->getProveedorInfo($orden['info'][0]->id_proveedor);
    // echo "<pre>";
    //   var_dump($orden);
    // echo "</pre>";exit;

    $orientacion = 'P';
    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf($orientacion, 'mm', 'Letter');
    $pdf->show_head = false;
    $pdf->noShowPagesPos = ($orientacion==='L'? 265: 205);
    $pdf->titulo1 = "{$empresa['info']->nombre_fiscal}";

    // $pdf->titulo3 = 'Almacen: '.$orden['info'][0]->almacen;
    $tipo_orden = 'COMPRA DIRECTA';
    // if($orden['info'][0]->tipo_orden == 'd') {
    //   $tipo_orden = 'ORDEN DE SERVICIO';
    //   if (count($orden['info'][0]->comprasligadas) > 0) {
    //     $tipo_orden = 'SERVICIO INTERNO';
    //   }
    // }
    // elseif($orden['info'][0]->tipo_orden == 'f')
    //   $tipo_orden = 'ORDEN DE FLETE';
    // // $pdf->titulo2 = $tipo_orden;
    // // $pdf->titulo2 = 'Proveedor: ' . $orden['info'][0]->proveedor;
    // // $pdf->titulo3 = " Fecha: ". date('Y-m-d') . ' Orden: ' . $orden['info'][0]->folio;

    $pdf->SetLeftMargin(5);
    $pdf->AliasNbPages();
    $pdf->limiteY = 235;
    $pdf->AddPage();

    $pdf->logo = $empresa['info']->logo!=''? (file_exists($empresa['info']->logo)? $empresa['info']->logo: '') : '';
    if($pdf->logo != '')
      $pdf->Image(APPPATH.(str_replace(APPPATH, '', $pdf->logo)), 6, 5, 50);

    $pdf->SetXY(150, $pdf->GetY());
    $pdf->SetFillColor(200,200,200);
    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(60));
    $pdf->Row(array($tipo_orden), true, true);
    $pdf->SetXY(150, $pdf->GetY());
    $pdf->Row(array($gasto['info']->serie.' '.MyString::formatoNumero($gasto['info']->folio, 2, '')."\n \n "), false, true);
    $pdf->SetFont('helvetica','B', 8.5);
    $pdf->SetXY(150, $pdf->GetY()-8);
    $pdf->Row(array(MyString::fechaATexto($gasto['info']->fecha, '/c', true)), false, false);
    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetXY(150, $pdf->GetY());

    $pdf->SetFont('helvetica','', 9);
    $pdf->SetXY(80, $pdf->GetY()-20);
    $pdf->Row(array('Impresión '.($gasto['info']->no_impresiones==0? 'ORIGINAL': ($gasto['info']->no_impresiones==1? 'COPIA ARCHIVO': 'COPIA '.$gasto['info']->no_impresiones)).
      "\n".MyString::fechaATexto(date("Y-m-d H:i:s"), '/c', true)), false, false);

    $pdf->SetXY(95, $pdf->GetY()+4);
    $aux_y1 = $pdf->getY();

    $pdf->SetFont('helvetica','B', 8);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(100));
    $pdf->Row(array('Modo de Facturación'), false, false);
    $pdf->SetFont('helvetica','', 8);
    $pdf->SetWidths(array(30, 50));
    $pdf->SetXY(95, $pdf->GetY()-1.5);
    $pdf->Row(array('Condiciones:', ($proveedor['info']->condicion_pago=='co'? 'Contado': "Crédito {$proveedor['info']->dias_credito} DIAS")), false, false);
    $pdf->SetXY(95, $pdf->GetY()-1.5);
    $formaPago = "99 (Por Definir)";
    // if ($orden['info'][0]->forma_pago_all) { // agroinsumos
    //   $formaPago = "{$orden['info'][0]->forma_pago_all['key']} ({$orden['info'][0]->forma_pago_all['value']})";
    // }
    $pdf->Row(array('Forma de Pago:', $formaPago), false, false);
    $pdf->SetXY(95, $pdf->GetY()-1.5);
    $pdf->Row(array('Método de Pago:', "PPD (Pago Parcialidades/Diferido)"), false, false);
    $usoCFDI = 'G03 (Gastos en General)';
    // if ($orden['info'][0]->uso_cfdi_all) { // agroinsumos
    //   $usoCFDI = "{$orden['info'][0]->uso_cfdi_all['key']} ({$orden['info'][0]->uso_cfdi_all['value']})";
    // }

    $pdf->SetXY(95, $pdf->GetY()-1.5);
    $pdf->Row(array('Uso del CFDI:', $usoCFDI), false, false);
    // $pdf->SetXY(95, $pdf->GetY()-1.5);
    // $pdf->Row(array('Almacén:', $orden['info'][0]->almacen), false, false);

    $pdf->SetXY(95, $pdf->GetY()+3);
    $pdf->SetFont('helvetica','B', 8);
    $pdf->SetAligns(array('L', 'L', 'L'));
    $pdf->SetWidths(array(100));
    $pdf->Row(array('Complementos de Pago'), false, false);
    $pdf->SetFont('helvetica','', 8);
    $pdf->SetWidths(array(30, 40));
    $pdf->SetXY(95, $pdf->GetY()-1.5);
    $pdf->Row(array('Método de Pago:', 'Transferencia'), false, false);
    $pdf->SetWidths(array(30, 40, 40));
    if (isset($emp_cuenta['info']->id_cuenta)) {
      $pdf->SetXY(95, $pdf->GetY()-1.5);
      $pdf->Row(array('Cta. Ordenante:', $emp_cuenta['info']->banco, $emp_cuenta['info']->cuenta), false, false);
    }
    if (count($proveedor_cuentas) > 0) {
      $pdf->SetXY(95, $pdf->GetY()-1.5);
      $pdf->Row(array('Cta. Beneficiario:', $proveedor_cuentas[0]->banco, $proveedor_cuentas[0]->cuenta), false, false);
      $pdf->SetWidths(array(30, 40));
      $pdf->SetXY(95, $pdf->GetY()-1.5);
      $pdf->Row(array('Ref Bancaria:', $proveedor_cuentas[0]->referencia), false, false);
    }

    $pdf->SetXY(95, $pdf->GetY()+3);
    $pdf->SetFont('helvetica','B', 8);
    $pdf->SetAligns(array('L', 'L', 'L'));
    $pdf->SetWidths(array(100));
    $pdf->Row(array('Requisitos para la Entrega de Mercancía'), false, false);
    $pdf->SetFont('helvetica','', 8);
    $pdf->SetXY(95, $pdf->GetY()-1.5);
    $pdf->Row(array('( No ) Pasar a Bascula a pesar la mercancía y entregar Boleta a almacén.'), false, false);
    $pdf->SetXY(95, $pdf->GetY()-1.5);
    $pdf->Row(array('( No ) Entregar la mercancía al almacenista, referenciando la presente Orden de Compra, así como anexarla a su Factura.'), false, false);

    $aux_y2 = $pdf->GetY();

    $pdf->SetXY(5, $aux_y1+15);

    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetXY(5, $pdf->GetY());
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(150));
    $pdf->Row(array($empresa['info']->nombre_fiscal), false, false);
    if(!empty($empresa['info']->sucursal)){
      $pdf->Row(array($empresa['info']->sucursal), false, false);
    }

    $pdf->SetFont('helvetica','B', 8);
    $pdf->SetAligns(array('L', 'L', 'L'));
    $pdf->SetWidths(array(90));
    $pdf->Row(array('Proveedor / Beneficiario'), false, false);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetXY(5, $pdf->GetY()-1.5);
    $pdf->Row(array($proveedor['info']->nombre_fiscal), false, false);
    $pdf->SetXY(5, $pdf->GetY()-1.5);
    $direccion = ($proveedor['info']->calle!=''? $proveedor['info']->calle: '').
      ($proveedor['info']->no_exterior!=''? " No {$proveedor['info']->no_exterior}": '').
      ($proveedor['info']->no_interior!=''? " {$proveedor['info']->no_interior}": '').
      ($proveedor['info']->cp!=''? ", CP {$proveedor['info']->cp}": '').
      ($proveedor['info']->colonia!=''? ", Col. {$proveedor['info']->colonia}": '').
      ($proveedor['info']->municipio!=''? " {$proveedor['info']->municipio}": '').
      ($proveedor['info']->estado!=''? " {$proveedor['info']->estado}": '');
    $pdf->Row(array($direccion), false, false);
    $pdf->SetXY(5, $pdf->GetY()-1.5);
    $pdf->Row(array("RFC {$proveedor['info']->rfc} / Tel. {$proveedor['info']->telefono}"), false, false);

    // $pdf->SetXY(5, $pdf->GetY()+3);
    // $pdf->SetFont('helvetica','B', 8);
    // $pdf->SetAligns(array('L', 'L', 'L'));
    // $pdf->SetWidths(array(90));
    // $pdf->Row(array('Dirección de Entrega'), false, false);
    // $pdf->SetFont('helvetica', '', 8);
    // $pdf->SetXY(5, $pdf->GetY()-1.5);
    // $pdf->Row(array($proveedor['info']->nombre_fiscal), false, false);
    // $pdf->SetXY(5, $pdf->GetY()-1.5);
    // $direccion = ($almacen['info']->calle!=''? $almacen['info']->calle: '').
    //   ($almacen['info']->no_exterior!=''? " No {$almacen['info']->no_exterior}": '').
    //   ($almacen['info']->no_interior!=''? " {$almacen['info']->no_interior}": '').
    //   ($almacen['info']->cp!=''? ", CP {$almacen['info']->cp}": '').
    //   ($almacen['info']->colonia!=''? ", Col. {$almacen['info']->colonia}": '').
    //   ($almacen['info']->municipio!=''? " {$almacen['info']->municipio}": '').
    //   ($almacen['info']->estado!=''? " {$almacen['info']->estado}": '');
    // $pdf->Row(array($direccion), false, false);
    // $pdf->SetFont('helvetica','B', 8);
    // $pdf->SetXY(5, $pdf->GetY()-1.5);
    // $pdf->Row(array("Horario de Entrega: {$almacen['info']->horario}"), false, false);

    // // Pagos de la orden
    // if (count($ordenPago) > 0) {
    //   // $aux_y2 = $pdf->GetY();
    //   $pdf->SetXY(215, $aux_y1);
    //   $pdf->SetFont('helvetica','B', 8);
    //   $pdf->SetAligns(array('C', 'C', 'C'));
    //   $pdf->SetWidths(array(55));

    //   if ($ordenPago[0]->status == 'pa') {
    //     $pdf->Row(array('Orden Cerrada'), true, true);
    //     $pdf->SetXY(215, $pdf->GetY());
    //   }

    //   $pdf->Row(array('Datos del Pago'), false, false);
    //   $pdf->SetFont('helvetica', '', 8);
    //   // $pdf->SetWidths(array(20, 25));
    //   $pdf->SetAligns(array('L'));
    //   $pdf->SetXY(215, $pdf->GetY());
    //   foreach ($ordenPago as $key => $value) {
    //     $pdf->SetXY(215, $pdf->GetY());
    //     $pdf->Row(array(
    //       "Fecha: {$value->fecha}\nFactura: {$value->serie}{$value->folio}\nCuenta: {$value->alias}\nImporte: ".MyString::formatoNumero($value->total, 2, '$', false).""), false, true);
    //     $pdf->Line(215, $pdf->GetY(), 250, $pdf->GetY());
    //   }
    // }

    // // Boletas ligadas
    // if (isset($orden['info'][0]->boletas_lig) && count($orden['info'][0]->boletas_lig) > 0 && $orientacion === 'L') {
    //   // $aux_y2 = $pdf->GetY();
    //   $pdf->SetXY(221, $pdf->GetY());
    //   if (count($ordenPago) === 0) {
    //     $pdf->SetXY(221, $aux_y1+10);
    //   }

    //   $pdf->SetFont('helvetica','B', 8);
    //   $pdf->SetAligns(array('C', 'C', 'C'));
    //   $pdf->SetWidths(array(45));
    //   $pdf->Row(array('Bascula'), false, false);
    //   $pdf->SetFont('helvetica', '', 8);
    //   $pdf->SetWidths(array(20, 25));
    //   $pdf->SetXY(221, $pdf->GetY());
    //   $pdf->Row(array('Fecha', 'Boleta'), true, true);
    //   foreach ($orden['info'][0]->boletas_lig as $key => $value) {
    //     $pdf->SetXY(221, $pdf->GetY());
    //     $pdf->Row(array(substr($value->fecha_bruto, 0, 10), $value->folio), false, true);
    //   }
    // }

    if ($aux_y2 > $pdf->getY()) {
      $pdf->SetY($aux_y2);
    }

    $pdf->SetY($pdf->getY()+5);

    $aligns = array('L', 'R');
    $widths = array(170, 35);
    $header = array('DESCRIPCION', 'IMPORTE');

    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->Row($header, true);

    $pdf->SetFont('Arial','',8);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->Row([
      $gasto['info']->concepto,
      MyString::formatoNumero($gasto['info']->subtotal, 2, '$', false)
    ], false);

    $yy = $pdf->GetY();

    //Totales
    $pdf->SetFont('Arial','',8);
    $pdf->SetXY(140, $pdf->GetY());
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(35, 35));
    $pdf->Row(array('SUB-TOTAL', MyString::formatoNumero($gasto['info']->subtotal, 2, '$', false)), false, true);
    $pdf->SetX(140);
    $pdf->Row(array('IVA', MyString::formatoNumero($gasto['info']->importe_iva, 2, '$', false)), false, true);
    if ($gasto['info']->importe_ieps > 0)
    {
      $pdf->SetX(140);
      $pdf->Row(array('IEPS', MyString::formatoNumero($gasto['info']->importe_ieps, 2, '$', false)), false, true);
    }
    if ($gasto['info']->retencion_iva > 0)
    {
      $pdf->SetX(140);
      $pdf->Row(array('Ret. IVA', MyString::formatoNumero($gasto['info']->retencion_iva, 2, '$', false)), false, true);
    }
    if ($gasto['info']->retencion_isr > 0)
    {
      $pdf->SetX(140);
      $pdf->Row(array('Ret. ISR', MyString::formatoNumero($gasto['info']->retencion_isr, 2, '$', false)), false, true);
    }
    $pdf->SetX(140);
    $pdf->Row(array('TOTAL', MyString::formatoNumero($gasto['info']->total, 2, '$', false)), false, true);

    // //Otros datos
    // $pdf->SetXY(6, $yy);
    // $pdf->SetX(6);
    // $pdf->SetAligns(array('L', 'L'));
    // $pdf->SetWidths(array(154));
    // if($orden['info'][0]->tipo_orden == 'f'){
    //   // $this->load->model('facturacion_model');
    //   $this->load->model('documentos_model');
    //   // $facturasss = explode('|', $orden['info'][0]->ids_facrem);
    //   $info_bascula = false;
    //   if (count($orden['info'][0]->facturasligadas) > 0 || count($orden['info'][0]->boletasligadas) > 0 || count($orden['info'][0]->comprasligadas) > 0)
    //   {
    //     $tituloclientt = $clientessss = $facturassss = $tituloclient = '';
    //     if ($orden['info'][0]->flete_de == 'v') {
    //       foreach ($orden['info'][0]->facturasligadas as $key => $value)
    //       {
    //         $facturassss .= ' / '.$value->serie.$value->folio.' '.$value->fechaT;
    //         $clientessss .= ', '.$value->cliente->nombre_fiscal;

    //         if($info_bascula === false)
    //         {
    //           $info_bascula = $this->documentos_model->getClienteDocs($value->id_factura, 1);
    //           if(!isset($info_bascula[0]) || $info_bascula[0]->data == 'NULL' )
    //             $info_bascula = false;
    //         }
    //       }
    //       $tituloclient = 'FOLIO: ';
    //       $tituloclientt = 'Clientes: ';
    //     } else {
    //       foreach ($orden['info'][0]->boletasligadas as $key => $value)
    //       {
    //         $facturassss .= ' / '.$value->folio.' '.substr($value->fecha_tara, 0, 10);
    //         $clientessss .= ', '.$value->proveedor;
    //       }
    //       $tituloclient = 'BOLETAS: ';
    //       $tituloclientt = 'Proveedores: ';
    //     }

    //     // array_pop($facturasss);
    //     // foreach ($facturasss as $key => $value)
    //     // {
    //     //   $facturaa = explode(':', $value);
    //     //   $facturaa = $this->facturacion_model->getInfoFactura($facturaa[1]);
    //     //   $facturassss .= '/'.$facturaa['info']->serie.$facturaa['info']->folio.' '.$facturaa['info']->fechaT;
    //     //   $clientessss .= ', '.$facturaa['info']->cliente->nombre_fiscal;

    //     //   if($info_bascula === false)
    //     //   {
    //     //     $info_bascula = $this->documentos_model->getClienteDocs($facturaa['info']->id_factura, 1);
    //     //     if(!isset($info_bascula[0]) || $info_bascula[0]->data == 'NULL' )
    //     //       $info_bascula = false;
    //     //   }
    //     // }
    //     $pdf->SetXY(6, $pdf->GetY());
    //     $pdf->Row(array($tituloclient.substr($facturassss, 3) ), false, false);
    //   }
    //   $pdf->SetX(6);
    //   $pdf->Row(array('CLIENTE: '.$orden['info'][0]->cliente), false, false);
    //   $pdf->SetXY(6, $pdf->GetY()+6);
    //   $pdf->Row(array('________________________________________________________________________________________________'), false, false);
    //   $pdf->SetXY(6, $pdf->GetY()-2);
    //   $pdf->Row(array('CHOFER: '.strtoupper($orden['info'][0]->empleado_solicito)), false, false);
    // } elseif ($orden['info'][0]->tipo_orden == 'd' && count($orden['info'][0]->comprasligadas) > 0) {
    //   $facturassss = $clientessss = '';
    //   foreach ($orden['info'][0]->comprasligadas as $key => $value)
    //   {
    //     $facturassss .= ' / '.$value->serie.$value->folio.' '.substr($value->fecha, 0, 10);
    //     $clientessss .= ', '.$value->proveedor->nombre_fiscal;
    //   }
    //   $tituloclient = 'FOLIO: ';
    //   $tituloclientt = 'Proveedor: ';
    //   $pdf->SetXY(6, $pdf->GetY());
    //   $pdf->Row(array($tituloclient.substr($facturassss, 3) ), false, false);
    //   $pdf->SetX(6);
    //   $pdf->Row(array('PROVEEDOR: '.$orden['info'][0]->proveedor), false, false);
    //   $pdf->SetXY(6, $pdf->GetY()+6);
    //   $pdf->Row(array('________________________________________________________________________________________________'), false, false);
    //   $pdf->SetXY(6, $pdf->GetY()-2);
    //   $pdf->Row(array('SOLICITA: '.strtoupper($orden['info'][0]->empleado_solicito)), false, false);
    // } else {
    //   $pdf->SetAligns(array('L', 'R'));
    //   $pdf->SetWidths(array(104, 50));
    //   $pdf->SetXY(6, $pdf->GetY());
    //   $pdf->Row(array('REGISTRO: '.strtoupper($orden['info'][0]->empleado), ($tipoCambio>1 ? "TIPO DE CAMBIO: " . $tipoCambio : '') ), false, false);
    //   $pdf->SetAligns(array('L', 'L'));
    //   $pdf->SetWidths(array(154));
    //   $pdf->SetXY(6, $pdf->GetY()-2);
    //   $pdf->Row(array('SOLICITA: '.strtoupper($orden['info'][0]->empleado_solicito)), false, false);
    // }

    $pdf->SetWidths(array(154));
    $pdf->SetXY(6, $pdf->GetY()+6);
    $pdf->Row(array('________________________________________________________________________________________________'), false, false);
    $pdf->SetXY(6, $pdf->GetY()-2);
    $pdf->Row(array('AUTORIZA'), false, false);
    $yy2 = $pdf->GetY();

    $yy2 = $pdf->GetY();
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetWidths(array(155));
    $pdf->Row(array('COD/AREA: ' . $gasto['info']->codigo_area), false, false);

    $pdf->SetWidths(array(205));
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetXY(5, $pdf->GetY());
    $pdf->Row(array('ESTIMADO PROVEEDOR: PARA QUE PROCEDA SU PAGO, LE SOLICITAMOS REALIZAR SU FACTURA CON LAS ESPECIFICACIONES ARRIBA SEÑALADAS, CUMPLIENDO CON LOS REQUISITOS DE ENTREGA Y ENVIARLA AL CORREO: compras@empaquesanjorge.com'), true, true);

    $pdf->SetXY(6, $pdf->GetY()+2);
    $y_compras = $pdf->GetY();

    if (!empty($gasto['info']->area)) {
      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(5, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(25, 70));
      $areas = [];
      foreach ([$gasto['info']->area] as $key => $value) {
        $areas[] = $value->nombre;
      }
      $pdf->Row(array('Cultivo / Actividad / Producto', implode(' | ', $areas)), false, true);
    }
    if (!empty($gasto['info']->rancho)) {
      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(5, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(25, 70));
      $ranchos = [];
      foreach ($gasto['info']->rancho as $key => $value) {
        $ranchos[] = $value->nombre;
      }
      $pdf->Row(array('Areas / Ranchos / Lineas', implode(' | ', $ranchos)), false, true);
    }
    if (!empty($gasto['info']->centroCosto)) {
      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(5, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(25, 70));
      $centroCosto = [];
      foreach ($gasto['info']->centroCosto as $key => $value) {
        $centroCosto[] = $value->nombre;
      }
      $pdf->Row(array('Centro de costo', implode(' | ', $centroCosto)), false, true);
    }
    if (!empty($gasto['info']->activo)) {
      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(5, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(25, 70));
      $activos = [];
      foreach ($gasto['info']->activo as $key => $value) {
        $activos[] = $value->nombre;
      }
      $pdf->Row(array('Activo', implode(' | ', $activos)), false, true);
    }
    if ($gasto['info']->intangible = 't') {
      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(5, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(25, 70));
      $pdf->Row(array('Intangible', 'Si'), false, true);
    }


    $this->db->where('id_compra', $gasto['info']->id_compra)->set('no_impresiones', 'no_impresiones+1', false)->update('compras');

    if ($path)
    {
      $file = $path.'compra_directa'.date('Y-m-d').'.pdf';
      $pdf->Output($file, 'F');
      return $file;
    }
    else
    {
      $pdf->Output('compra_directa'.date('Y-m-d').'.pdf', 'I');
    }
  }


  /*
   |------------------------------------------------------------------------
   | HELPERS
   |------------------------------------------------------------------------
   */

  /**
   * Crea el directorio por proveedor.
   *
   * @param  string $clienteNombre
   * @param  string $folioFactura
   * @return string
   */
  public function creaDirectorioProveedorCfdi($proveedor)
  {
    $path = APPPATH.'media/gastos/cfdi/';

    if ( ! file_exists($path))
    {
      // echo $path.'<br>';
      mkdir($path, 0777);
    }

    $path .= strtoupper($proveedor).'/';
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

    // $path .= ($serie !== '' ? $serie.'-' : '').$folio.'/';
    // if ( ! file_exists($path))
    // {
    //   // echo $path;
    //   mkdir($path, 0777);
    // }

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
}