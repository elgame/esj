<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class rastreabilidad_paletas_model extends privilegios_model {


  function __construct()
  {
    parent::__construct();
    $this->load->model('bitacora_model');
  }

  public function getPaletas($paginados = true)
  {
    $sql = '';
    //paginacion
    if($paginados)
    {
      $this->load->library('pagination');
      $params = array(
          'result_items_per_page' => '40',
          'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
      );
      if($params['result_page'] % $params['result_items_per_page'] == 0)
        $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);
    }

    //Filtros para buscar
    if($this->input->get('fnombre') != '')
      $sql = "WHERE ( lower(c.nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%'
                ".(is_numeric($this->input->get('fnombre'))? " OR b.folio = '".$this->input->get('fnombre')."'": '')." )";

    if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '')
      $sql .= ($sql==''? 'WHERE': ' AND')." Date(ps.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffecha1') != '')
      $sql .= ($sql==''? 'WHERE': ' AND')." Date(ps.fecha) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffecha2') != '')
      $sql .= ($sql==''? 'WHERE': ' AND')." Date(ps.fecha) = '".$this->input->get('ffecha2')."'";

    if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
      $sql .= ($sql==''? 'WHERE': ' AND')." ps.status = '".$this->input->get('fstatus')."'";

    if($this->input->get('ftipo') != '' && $this->input->get('ftipo') != 'todos')
      $sql .= ($sql==''? 'WHERE': ' AND')." ps.tipo = '".$this->input->get('ftipo')."'";

    $empresa = $this->empresas_model->getDefaultEmpresa();
    if( ! $this->input->get('did_empresa') != '')
    {
      $_GET['did_empresa'] = $empresa->id_empresa;
      $_GET['dempresa'] = $empresa->nombre_fiscal;
    }
    $sql .= ($sql==''? 'WHERE': ' AND')." ps.id_empresa = '".$this->input->get('did_empresa')."'";

    $query = BDUtil::pagination("SELECT
          ps.id_paleta_salida, b.folio, Date(ps.fecha) AS fecha, ps.status, ps.tipo,
          string_agg(distinct c.nombre_fiscal, ', ') AS clientes
        FROM otros.paletas_salidas ps
          INNER JOIN public.bascula b ON b.id_bascula = ps.id_bascula
          LEFT JOIN otros.paletas_salidas_productos psp ON ps.id_paleta_salida = psp.id_paleta_salida
          LEFT JOIN clientes AS c ON psp.id_cliente = c.id_cliente
        {$sql}
        GROUP BY ps.id_paleta_salida, b.id_bascula
        ORDER BY folio DESC
        ", $params, true);
    $res = $this->db->query($query['query']);

    $response = array(
      'paletas'        => array(),
      'total_rows'     => $query['total_rows'],
      'items_per_page' => $params['result_items_per_page'],
      'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['paletas'] = $res->result();

    $res->free_result();

    return $response;
  }

  public function getInfoPaleta($id_paleta, $basic_info=FALSE, $all=false){
    $result = $this->db->query("SELECT ps.id_paleta_salida, ps.fecha, ps.fecha_registro, ps.id_empresa, e.nombre_fiscal AS empresa,
        b.id_bascula, b.folio, b.kilos_neto, b.no_trazabilidad, ps.tipo, ps.status, b.id_chofer, b.id_camion
      FROM otros.paletas_salidas ps
        INNER JOIN empresas e ON e.id_empresa = ps.id_empresa
        INNER JOIN bascula b ON b.id_bascula = ps.id_bascula
      WHERE ps.id_paleta_salida =  {$id_paleta}");

    $response = [];
    if($result->num_rows() > 0){
      $response['paleta'] = $result->row();
      $result->free_result();

      if (!$basic_info || $all) {
        $result = $this->db->query("SELECT psp.id_paleta_salida, psp.num_rows, psp.id_cliente, psp.id_clasificacion,
            psp.clasificacion, psp.id_unidad, psp.unidad, u.cantidad AS cantidad_unidad, psp.cantidad, psp.kilos,
            psp.id_pallet, c.nombre_fiscal AS cliente
          FROM otros.paletas_salidas_productos psp
            INNER JOIN clientes c ON c.id_cliente = psp.id_cliente
            INNER JOIN clasificaciones cl ON cl.id_clasificacion = psp.id_clasificacion
            INNER JOIN unidades u ON u.id_unidad = psp.id_unidad
          WHERE psp.id_paleta_salida = {$id_paleta}");
        $response['clasificaciones'] = $result->result();
        $result->free_result();

        $result = $this->db->query("SELECT p.id_pallet, psp.posicion, p.no_cajas, p.folio,
            string_agg(distinct c.codigo, ', ') AS clasificaciones, string_agg(distinct u.codigo, ', ') AS unidades
          FROM otros.paletas_salidas_pallets psp
            INNER JOIN rastria_pallets p ON p.id_pallet = psp.id_pallet
            INNER JOIN rastria_pallets_rendimiento pr ON p.id_pallet = pr.id_pallet
            INNER JOIN clasificaciones c ON c.id_clasificacion = pr.id_clasificacion
            INNER JOIN unidades u ON u.id_unidad = pr.id_unidad
          WHERE psp.id_paleta_salida = {$id_paleta}
          GROUP BY p.id_pallet, psp.posicion");
        $pallets = $result->result();
        $response['pallets'] = [];
        foreach ($pallets as $key => $value) {
          $response['pallets'][$value->posicion] = $value;
        }
        $result->free_result();

        if ($all) {
          $listClientes = array_unique(array_column($response['clasificaciones'], 'id_cliente'));
          $response['paleta']->carga_compartida = (count($listClientes)>0? 'Si': 'No');

          $response['paleta']->total_tarimas = count($response['pallets']);
          $response['paleta']->total_bultos = 0;
          $response['paleta']->total_kg = 0;
          foreach ($response['clasificaciones'] as $key => $value) {
            $response['paleta']->total_bultos += $value->cantidad;
            $response['paleta']->total_kg += $value->kilos;
          }

          $result = $this->db->query("SELECT id_chofer, nombre, telefono, no_licencia, no_ife
            FROM choferes WHERE id_chofer = {$response['paleta']->id_chofer}");
          $response['paleta']->chofer = $result->row();
          $result->free_result();

          $result = $this->db->query("SELECT id_camion, placa, modelo, marca, color
            FROM camiones WHERE id_camion = {$response['paleta']->id_camion}");
          $response['paleta']->camion = $result->row();
          $result->free_result();

          $result = $this->db->query("SELECT f.id_factura, f.id_cliente, (f.serie || f.folio) AS folio_rem,
              f.fecha, f.total AS total_rem, c.nombre_fiscal AS cliente,
              (ff.serie || ff.folio) AS folio_fact, ff.total AS total_fact
            FROM facturacion f
              INNER JOIN clientes c ON c.id_cliente = f.id_cliente
              LEFT JOIN facturacion_otrosdatos fo ON f.id_factura = fo.id_factura
              LEFT JOIN facturacion_remision_hist fh ON f.id_factura = fh.id_remision
              LEFT JOIN facturacion ff ON (ff.id_factura = fh.id_factura AND ff.status <> 'ca' AND ff.status <> 'pf')
            WHERE f.is_factura = 'f' AND f.status <> 'ca' AND f.status <> 'pf' AND fo.id_paleta_salida = {$id_paleta}
            ORDER BY f.id_factura ASC");
          $response['facturacion'] = $result->result();
          $result->free_result();

          $result = $this->db->query("SELECT c.nombre AS clasificacion, string_agg(DISTINCT p.nombre_fiscal, ', ') AS proveedor,
              string_agg(DISTINCT(CASE WHEN fsc.pol_seg IS NULL THEN 'cer' ELSE 'seg' END), '') AS tipo,
              string_agg(fsc.pol_seg, ', ') AS seg_certs,
              string_agg(fsc.certificado, ', ') AS certificados
            FROM facturacion f
              LEFT JOIN facturacion_otrosdatos fo ON f.id_factura = fo.id_factura
              LEFT JOIN facturacion_seg_cert fsc ON f.id_factura = fsc.id_factura
              LEFT JOIN proveedores p ON p.id_proveedor = fsc.id_proveedor
              LEFT JOIN clasificaciones c ON c.id_clasificacion = fsc.id_clasificacion
            WHERE f.is_factura = 'f' AND f.status <> 'ca' AND f.status <> 'pf' AND fo.id_paleta_salida = {$id_paleta}
            GROUP BY c.id_clasificacion
            HAVING c.nombre IS NOT NULL");
          $response['certificados'] = $result->result();
          $result->free_result();
        }
      }
    }

    return $response;
  }

  /**
   * Agregar un pallet a la bd
   * @param [type] $data array con los valores a insertar
   */
  public function addPaletaSalida($data=NULL)
  {
    if ($data==NULL)
    {
      $data = array(
        'id_empresa'     => $this->input->post('empresaId'),
        'id_registro'    => $this->session->userdata('id_usuario'),
        'fecha'          => $this->input->post('fecha'),
        'status'         => 'r',
        'id_bascula'     => $this->input->post('boletasSalidasId'),
        'tipo'           => $this->input->post('tipo'),
      );
    }

    $this->db->insert('otros.paletas_salidas', $data);
    $id_paleta = $this->db->insert_id('otros.paletas_salidas', 'id_paleta_salida');

    $this->saveClasificaciones($id_paleta);

    $this->savePallets($id_paleta);

    return array('msg' => 3, 'id' => $id_paleta);
  }

  /**
   * Modifica un pallet a la bd
   * @param [type] $data array con los valores a insertar
   */
  public function updatePallet($id_paleta, $data=NULL){
    if ($data==NULL)
    {
      $data = array(
        'id_empresa'     => $this->input->post('empresaId'),
        'id_registro'    => $this->session->userdata('id_usuario'),
        'fecha'          => $this->input->post('fecha'),
        'status'         => $this->input->post('status'),
        'id_bascula'     => $this->input->post('boletasSalidasId'),
        'tipo'           => $this->input->post('tipo'),
      );
    }

    $this->db->update('otros.paletas_salidas', $data, "id_paleta_salida = {$id_paleta}");

    $this->saveClasificaciones($id_paleta);

    $this->savePallets($id_paleta);

    return array('msg' => 5, 'id' => $id_paleta);
  }

  public function saveClasificaciones($id_paleta, $data=NULL, $id_bitacora=0){
    if ($data==NULL)
    {
      if(is_array($this->input->post('prod_id_cliente')))
      {
        foreach ($this->input->post('prod_id_cliente') as $key => $cajas)
        {
          $data[] = array(
            'id_paleta_salida' => $id_paleta,
            'num_rows'         => $key,
            'id_cliente'       => $this->input->post('prod_id_cliente')[$key],
            'id_clasificacion' => $this->input->post('prod_did_prod')[$key],
            'clasificacion'    => $this->input->post('prod_ddescripcion')[$key],
            'id_unidad'        => $this->input->post('prod_dmedida_id')[$key],
            'unidad'           => $this->input->post('prod_dmedida')[$key],
            'cantidad'         => $this->input->post('prod_dcantidad')[$key],
            'kilos'            => $this->input->post('prod_dmedida_kilos')[$key],
            'id_pallet'        => ($this->input->post('prod_id_pallet')[$key]!==''? $this->input->post('prod_id_pallet')[$key]: NULL),
          );
        }
      }
    }

    $this->db->delete('otros.paletas_salidas_productos', ['id_paleta_salida' => $id_paleta]);
    if(count($data) > 0) {
      $this->db->insert_batch('otros.paletas_salidas_productos', $data);
    }

    return true;
  }

  public function savePallets($id_paleta, $data=null)
  {
    if ($data==NULL)
    {
      $data = array();
      if(is_array($this->input->post('pallets_id')))
      {
        foreach ($this->input->post('pallets_id') as $key => $id_pallet)
        {
          if ($id_pallet !== '') {
            $data[] = array(
              'id_paleta_salida' => $id_paleta,
              'id_pallet'        => $id_pallet,
              'posicion'         => $this->input->post('pallets_posicion')[$key],
            );
          }
        }
      }
    }

    $this->db->delete('otros.paletas_salidas_pallets', ['id_paleta_salida' => $id_paleta]);
    if(count($data) > 0) {
      $this->db->insert_batch('otros.paletas_salidas_pallets', $data);
    }

    return true;
  }

  public function deletePaleta($id_paleta)
  {
    $this->db->update('otros.paletas_salidas', ['status' => 'ca'], "id_paleta_salida = {$id_paleta}");
    $response = array('passes' => true, 'msg' => '7');
    return $response;
  }

  public function remisionarPapeleta($id_paleta)
  {
    $serie = $this->input->get('serie');

    $this->load->model('ventas_model');
    $this->load->model('clientes_model');

    $result = $this->db->query("SELECT ps.id_paleta_salida, ps.id_empresa,
        psp.id_cliente, psp.id_clasificacion, psp.clasificacion, psp.id_unidad,
        psp.unidad, Sum(psp.cantidad) AS cantidad, Sum(psp.kilos) AS kilos,
        (Sum(u.cantidad)/Coalesce(NULLIF(Count(u.cantidad), 0), 1)) AS cantidad_c
      FROM otros.paletas_salidas ps
        INNER JOIN otros.paletas_salidas_productos psp ON ps.id_paleta_salida = psp.id_paleta_salida
        INNER JOIN unidades u ON u.id_unidad = psp.id_unidad
      WHERE ps.id_paleta_salida =  {$id_paleta}
      GROUP BY ps.id_paleta_salida, psp.id_cliente, psp.id_clasificacion, psp.clasificacion, psp.id_unidad, psp.unidad
      ORDER BY id_cliente ASC");

    $datos = $result->result();
    $remisiones = [];
    foreach ($datos as $key => $value) {
      if (!isset($remisiones[$value->id_cliente])) {
        $cfdi_ext = [
          'tipoDeComprobante' => 'I',
          'usoCfdi'           => 'G01',
        ];
        $remisiones[$value->id_cliente]['remision'] = [
          'id_cliente'          => $value->id_cliente,
          'id_empresa'          => $value->id_empresa,
          'version'             => empresas_model::$version,
          'serie'               => $serie,
          'folio'               => '',
          'fecha'               => date("Y-m-d H:i:s"),
          'subtotal'            => 0,
          'importe_iva'         => 0,
          'retencion_iva'       => 0,
          'total'               => 0,
          'total_letra'         => '',
          'no_aprobacion'       => '',
          'ano_aprobacion'      => '',
          'tipo_comprobante'    => 'ingreso',
          'forma_pago'          => '01',
          'metodo_pago'         => 'PUE',
          'metodo_pago_digitos' => 'No identificado',
          'no_certificado'      => '',
          'cadena_original'     => '',
          'sello'               => '',
          'certificado'         => '',
          'condicion_pago'      => 'cr',
          'plazo_credito'       => '0',
          'observaciones'       => '',
          'status'              => 'p', //$_POST['dcondicion_pago'] === 'co' ? 'pa' : 'p',
          'status_timbrado'     => 'p',
          'sin_costo'           => 'f',
          'is_factura'          => 'f',
          'sin_costo_nover'     => 'f',
          'moneda'              => 'MXN',
          'cfdi_ext'            => json_encode($cfdi_ext),
          'tipo_cambio'         => '1',
        ];

        $remisiones[$value->id_cliente]['otrosdatos'] = [
          'id_factura'       => '',
          'no_trazabilidad'  => '',
          'id_paleta_salida' => $id_paleta,
        ];

        $cliente = $this->clientes_model->getClienteInfo($value->id_cliente, true);
        $remisiones[$value->id_cliente]['cliente'] = [
          'id_factura'    => '',
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
        ];
      }

      $cfdi_ext = [
        'clave_unidad' => [
          'key'   => '',
          'value' => '',
        ]
      ];
      $remisiones[$value->id_cliente]['productos'][] = [
        'id_factura'            => '',
        'id_clasificacion'      => $value->id_clasificacion,
        'num_row'               => intval($key),
        'cantidad'              => $value->cantidad,
        'descripcion'           => $value->clasificacion,
        'precio_unitario'       => 0,
        'importe'               => 0,
        'iva'                   => 0,
        'unidad'                => $value->unidad,
        'retencion_iva'         => 0,
        'porcentaje_iva'        => 0,
        'porcentaje_retencion'  => 0,
        'ids_pallets'           => null,
        'kilos'                 => $value->kilos,
        'cajas'                 => $value->cantidad,
        'id_unidad_rendimiento' => null,
        'id_size_rendimiento'   => null,
        'certificado'           => 'f',
        'id_unidad'             => $value->id_unidad,
        'unidad_c'              => $value->cantidad_c,
        'id_calidad'            => NULL,
        'id_tamanio'            => NULL,
        'descripcion2'          => '',
        'cfdi_ext'              => json_encode($cfdi_ext),
      ];
    }

    $this->load->model('ventas_model');
    $this->ventas_model->addNotaVentaData($remisiones);
    $this->db->update('otros.paletas_salidas', ['status' => 'f'], "id_paleta_salida = {$id_paleta}");
  }

  public function paleta_pdf($id_paleta, $pdf=null){
    // Obtiene los datos del reporte.
    $this->load->model('empresas_model');
    $data = $this->getInfoPaleta($id_paleta, false, true);
    $empresa = $this->empresas_model->getInfoEmpresa($data['paleta']->id_empresa);
    // echo "<pre>";
    // var_dump($data);
    // echo "</pre>";exit;

    // Creación del objeto de la clase heredada
    if (empty($pdf)) {
      $this->load->library('mypdf');
      $pdf = new MYpdf('L', 'mm');
    } else {
      // $pdf->CurOrientation = 'L';
    }
    $pdf->show_head = false;
    $pdf->limiteY = 190;

    $pdf->AliasNbPages();
    $pdf->AddPage('L');
    $pdf->SetFont('helvetica','', 8);

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;
    else
      $pdf->logo = 'images/logo.png';

    $pdf->Image($pdf->logo, 6, 5, 20);

    $pdf->SetFillColor(200, 200, 200);

    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(50));
    $pdf->SetXY(80, 3);
    $pdf->font_bold = 'B';
    $pdf->SetFounts(array($pdf->fount_txt), array(2));
    $pdf->Row2(array('PAPELETA DE SALIDA'), false, false);
    $pdf->SetXY(80, $pdf->GetY()-1);
    $pdf->Row2(array($data['paleta']->folio), false, false);

    $pdf->SetFounts(array($pdf->fount_txt), array(0));
    $pdf->SetWidths(array(40));
    $pdf->SetXY(130, 3);
    $pdf->Row2(array('FECHA'), true, true);
    $pdf->SetXY(130, $pdf->GetY());
    $pdf->Row2(array(strtoupper(MyString::fechaATexto($data['paleta']->fecha, '/c'))), false, true);
    $pdf->SetFounts(array($pdf->fount_txt), array(-1.5));
    $pdf->SetXY(130, $pdf->GetY()+2);
    $pdf->Row2(array("CARGA COMPARTIDA: {$data['paleta']->carga_compartida}"), false, false);

    $pdf->SetFounts(array($pdf->fount_txt), array(1));
    $pdf->SetWidths(array(80));
    $pdf->SetXY(180, 3);
    $pdf->Row2(array('CASETA DE VIGILANCIA'), true, true);
    $pdf->SetFounts(array($pdf->fount_txt), array(-1));
    $pdf->SetXY(180, $pdf->GetY());
    $pdf->Row2(array(''), false, true, 14);
    $pdf->Text(210, $pdf->GetY()-1, 'Reloj Checador');

    $pdf->font_bold = '';
    $pdf->SetWidths(array(15, 50));
    $pdf->SetFounts(array($pdf->fount_txt), [0], ['B']);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row2(array('Empresa:', $empresa['info']->nombre_fiscal), false, false);

    $pdf->Line(6, $pdf->GetY()+2, 270, $pdf->GetY()+2);

    $pdf->SetWidths(array(18, 120, 18, 45, 18, 45, 18, 40));
    $pdf->SetFounts(array($pdf->fount_txt), [0], ['B', '', 'B', '', 'B', '', 'B', '']);
    foreach ($data['facturacion'] as $key => $value) {
      $pdf->SetXY(6, $pdf->GetY()+2);
      $pdf->Row2([
        'CLIENTE:', $value->cliente,
        'REMISION:', "{$value->folio_rem}/{$value->total_rem}",
        'FACTURA:', "{$value->folio_fact}/{$value->total_fact}",
      ], false, false);

      $data['otros_facturas'][] = $value->folio_fact;
      $data['otros_clientes'][] = $value->cliente;
    }

    $pdf->Line(6, $pdf->GetY()+2, 270, $pdf->GetY()+2);

    $pdf->SetWidths(array(30, 45, 18, 35, 16, 35, 18, 40));
    $pdf->SetFounts(array($pdf->fount_txt), [0], ['B', '', 'B', '', 'B', '', 'B', '']);
    $pdf->SetXY(6, $pdf->GetY()+2);
    $pdf->Row2([
      'RASTREABILIDAD:', $data['paleta']->no_trazabilidad,
      'TARIMAS:', MyString::formatoNumero($data['paleta']->total_tarimas, 0, '', false),
      'BULTOS:', MyString::formatoNumero($data['paleta']->total_bultos, 0, '', false),
      'BASCULA:', MyString::formatoNumero($data['paleta']->kilos_neto, 2, '', false).' Kg',
    ], false, false);

    // Si la papeleta es local o nacional
    if ($data['paleta']->tipo == 'lo' || $data['paleta']->tipo == 'na') {
      $pdf->SetXY(6, $pdf->GetY()+2);
      foreach ($data['clasificaciones'] as $key => $item) {
        if($pdf->GetY() >= $pdf->limiteY || $key === 0)
        {
          if($key > 0)
            $pdf->AddPage('L');

          $pdf->SetFont('Arial', 'B', 8);
          $pdf->SetX(15);
          $pdf->SetAligns(['L', 'L', 'R', 'R']);
          $pdf->SetWidths([70, 110, 30, 35]);
          $pdf->Row(['UNIDAD', 'DESCRIPCION', 'CANTIDAD', 'KILOS'], true, true);
        }

        $pdf->SetFont('Arial', '', 8);
        $pdf->SetX(15);
        $pdf->Row(array(
          $item->unidad,
          $item->clasificacion,
          MyString::formatoNumero($item->cantidad, 2, '', false),
          MyString::formatoNumero($item->kilos, 2, '', false),
        ), false, true, null, 2, 1);
      }

      $pdf->SetX(85);
      $pdf->SetAligns(['R', 'R', 'R']);
      $pdf->SetWidths([110, 30, 35]);
      $pdf->Row(array(
        'TOTAL',
        MyString::formatoNumero($data['paleta']->total_bultos, 2, '', false),
        MyString::formatoNumero($data['paleta']->total_kg, 2, '', false),
      ), false, true, null, 2, 1);
    } else { // exportación
      $pdf->SetXY(6, $pdf->GetY()+2);
      $pallets = [];
      for ($i=0; $i < 12; $i++) {
        $exist = isset($data['pallets'][($i*2)+1]);
        $pallets[0][$i] = ($i*2)+1;
        $pallets[1][$i] = ($exist? $data['pallets'][($i*2)+1]->clasificaciones: '');
        $pallets[2][$i] = ($exist? $data['pallets'][($i*2)+1]->no_cajas." {$data['pallets'][($i*2)+1]->unidades}": '');

        $exist = isset($data['pallets'][($i+1)*2]);
        $pallets[3][$i] = ($exist? $data['pallets'][($i+1)*2]->clasificaciones: '');
        $pallets[4][$i] = ($exist? $data['pallets'][($i+1)*2]->no_cajas." {$data['pallets'][($i+1)*2]->unidades}": '');
        $pallets[5][$i] = ($i+1)*2;
      }
      $pdf->SetAligns(['C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C']);
      $pdf->SetWidths([22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22, 22]);
      $pdf->SetFont('Arial', '', 8);
      $pdf->SetX(6);
      $pdf->Row($pallets[0], false, false);
      $pdf->SetFont('Arial', '', 6);
      $pdf->SetX(6);
      $pdf->Row($pallets[1], true, true);
      $pdf->SetX(6);
      $pdf->Row($pallets[2], true, true);
      $pdf->SetFillColor(230, 230, 230);
      $pdf->SetX(6);
      $pdf->Row($pallets[3], true, true);
      $pdf->SetX(6);
      $pdf->Row($pallets[4], true, true);
      $pdf->SetFillColor(200, 200, 200);
      $pdf->SetFont('Arial', '', 8);
      $pdf->SetX(6);
      $pdf->Row($pallets[5], false, false);
    }

    if (count($data['certificados']) > 0) {
      $pdf->SetAligns(['L', 'L', 'L', 'R']);
      $pdf->SetWidths([100, 65, 100]);
      $pdf->SetXY(6, $pdf->GetY()+4);
      foreach ($data['certificados'] as $key => $item) {
        if($pdf->GetY() >= $pdf->limiteY || $key === 0) {
          if($key > 0)
            $pdf->AddPage('L');
        }

        $pdf->SetFont('Arial', '', 8);
        $pdf->SetX(6);
        $pdf->Row(array(
          $item->proveedor,
          $item->clasificacion,
          ($item->tipo === 'cer'? $item->certificados: $item->seg_certs),
        ), false, false, null, 2, 1);
      }
    }

    $pdf->SetWidths(array(16, 75, 18, 35, 16, 45, 18, 40));
    $pdf->SetAligns(array('R', 'L', 'R', 'L', 'R', 'L', 'R', 'L'));
    $pdf->SetFounts(array($pdf->fount_txt), [0], ['B', '', 'B', '', 'B', '', 'B', '']);
    $pdf->SetXY(6, $pdf->GetY()+4);
    $pdf->Row2([
      'CHOFER:', $data['paleta']->chofer->nombre,
      'LICENCIA:', $data['paleta']->chofer->no_licencia,
      'CAMION:', "{$data['paleta']->camion->marca} {$data['paleta']->camion->color}",
      'PLACAS:', $data['paleta']->camion->placa,
    ], false, false);


    $pdf->SetWidths(array(90, 90, 90));
    $pdf->SetAligns(array('C', 'C', 'C'));
    $pdf->SetFounts(array($pdf->fount_txt), [], []);
    $pdf->SetXY(6, $pdf->GetY()+10);
    $pdf->Row2([
      '______________________________________',
      '______________________________________',
      '______________________________________',
    ], false, false);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row2([
      'LOGISTICA',
      'VIGILANCIA',
      'AUDITORIA',
    ], false, false);

    $this->pdfManifiestoDelChofer($data, $empresa, $pdf);

    $pdf->Output('papeleta_salida.pdf', 'I');
  }

  public function pdfManifiestoDelChofer($data, $empresa, $pdf)
  {

    $pdf->show_head = false;

    // $pdf->AliasNbPages();
    $pdf->AddPage('L');
    $pdf->SetFont('helvetica','', 8);

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;
    else
      $pdf->logo = 'images/logo.png';

    $pdf->Image($pdf->logo, 6, 5, 20);

    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial','',8);

    $pdf->SetAligns(['C']);
    $pdf->SetWidths([100]);
    $pdf->SetXY(80, 8);
    $pdf->Row(['KM.8 CARRETERA TECOMAN PLAYA AZUL  C.P. 28935'], false, false);
    $pdf->SetXY(80, $pdf->GetY()-1);
    $pdf->Row(['COL COFRADIA DE MORELOS EN TECOMAN, COLIMA'], false, false);
    $pdf->SetXY(80, $pdf->GetY()-1);
    $pdf->Row(['TEL: 313 324 4420  CEL: 313 113 0040'], false, false);

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetXY(180, $pdf->GetY()-16);
    $pdf->Row(['MANIFIESTO'], false, false);

    $pdf->SetXY(6, $pdf->GetY()+20);
    $pdf->SetAligns(['C', 'C']);
    $pdf->SetWidths([180, 80]);
    $pdf->Row(['CONDICIONES DEL FLETE', 'DESTINO'], true, true);
    $pdf->SetAligns(['L', 'L']);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(['EMPRESA CONTRATANTE:', 'No FACTURA:'.implode(', ',  (!empty($data['otros_facturas'])? $data['otros_facturas']: []))]);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(['CLIENTE DESTINO:', 'DIA DE LLEGADA:']);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(['DIRECCION:', 'HR DE ENTREGA:']);

    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(['C', 'C']);
    $pdf->SetWidths([180, 80]);
    $pdf->Row(['DATOS DE LINEA Y CHOFER', 'ANEXOS'], true, true);
    $pdf->SetWidths([120, 60, 80]);
    $pdf->SetAligns(['L', 'L', 'L']);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(['Chofer: '.$data['paleta']->chofer->nombre,
      'Cel: '.$data['paleta']->chofer->telefono,
      'No. Licencia: '.$data['paleta']->chofer->no_licencia
    ]);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(['Linea: '.$data['paleta']->chofer->nombre,
      'Tel: '.$data['paleta']->chofer->telefono,
      'No. IFE: '.$data['paleta']->chofer->no_ife
    ]);
    $pdf->SetWidths([60, 60, 60, 80]);
    $pdf->SetAligns(['L', 'L', 'L', 'L']);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(['Placas Camión: '.$data['paleta']->camion->placa,
      'Modelo: '.$data['paleta']->camion->modelo,
      'Marca: '.$data['paleta']->camion->marca,
      'No Pesada: '.$data['paleta']->folio,
    ]);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(['Placas Termo: ',
      'Color: '.$data['paleta']->camion->color,
      'Temp: ',
      'Orden Flete: '
    ]);

    $pdf->SetFont('Arial', '', 8);
    $txt = "MANIFIESTO DEL CHOFER: COMO CHOFER DEL CAMION ARRIBA DESCRITO, ME COMPROMETO A TRANSPORTAR LA(S) A LA TEMPERATURA ARRIBA INDICADA. EN PARADAS DE DESCANSO Y COMIDAS IR GASEANDO LA FRUTA Y LLEGAR A MI DESTINO EN TIEMPO Y FORMA.".
      "MANIFIESTO EN EL PRESENTE DOCUMENTO, QUE EL (LOS) PRODUCTO(S) TRANSPORTADO(S) FUE CARGADO EN MI PRESENCIA Y VERIFIQUE QUE VA LIBRE DE CUALQUIER TIPO DE ESTUPEFACIENTE (DROGAS) POR LO QUE EXIMO DE TODA RESPONSABILIDAD AL (LOS) CONTRATANTE(S) Y AL (LOS) DESTINATARIO(S) DE CUALQUIER MERCANCIA NO DESCRITA EN EL PRESENTE EMBARQUE, FACTURA O PEDIDO. TENIENDO PROHIBIDO LLEVAR Y/O TRANSPORTAR OTRA MERCANCIA Y SI POR ALGUNA CIRCUNSTANCIA LO HAGO, ASUMO LAS CONSECUENCIAS DERIVADAS DE LA VIOLACION A ESTAS DISPOSICIONES.".
      "ACEPTO TENER REPERCUCIONES EN EL PAGO DEL FLETE, SI NO ENTREGO LA MERCANCIA CONFORME A LA FECHA Y HORA DE ENTREGA Y TAMBIEN SI NO CUMPLO CON LA TEMPERATURA INDICADA, POR MOTIVOS QUE SE RELACIONEN DIRECTAMENTE CON EL MAL ESTADO MECÁNICO DE MI UNIDAD (CAMIÓN ARRIBA DESCRITO), SE ME DESCONTARA UN 20% (VEINTE POR CIENTO) DEL VALOR DEL FLETE, ASI COMO CUALQUIER DIFERENCIA O ANORMALIDAD EN LA ENTREGA DE LA MERCANCIA TRANSPORTADA.";
    $pdf->SetWidths([260]);
    $pdf->SetAligns(['L']);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->Row([$txt]);

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetAligns(['C']);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->Row(['RECIBO Y ACEPTO DE CONFORMIDAD'], false, false);
    $pdf->SetXY(6, $pdf->GetY()+10);
    $pdf->Row(['___________________________________________'], false, false);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(['NOMBRE Y FIRMA DEL CHOFER'], false, false);

    $pdf->SetXY(6, $pdf->GetY()+15);
    $pdf->Row(['TECOMAN, COL. A ____________________________ DE _________________________________ DE _________________________'], false, false);

    $pdf->SetFont('Arial', '', 7);
    $pdf->SetWidths([30]);
    $pdf->SetXY(45, $pdf->GetY()-45);
    $pdf->Row(['HUELLA DEL CHOFER'], false, true, null, 30, 30);
  }


}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */