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

  public function paleta_pdf($id_paleta){
    // Obtiene los datos del reporte.
    $this->load->model('empresas_model');
    $data = $this->getInfoPaleta($id_paleta, false, true);
    $empresa = $this->empresas_model->getInfoEmpresa($data['paleta']->id_empresa);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('L', 'mm');
    $pdf->show_head = false;
    $pdf->limiteY = 190;

    $pdf->AliasNbPages();
    $pdf->AddPage();
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
            $pdf->AddPage();

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


    $pdf->SetWidths(array(16, 75, 18, 35, 16, 45, 18, 40));
    $pdf->SetAligns(array('R', 'L', 'R', 'L', 'R', 'L', 'R', 'L'));
    $pdf->SetFounts(array($pdf->fount_txt), [0], ['B', '', 'B', '', 'B', '', 'B', '']);
    $pdf->SetXY(6, $pdf->GetY()+2);
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

    $pdf->Output('papeleta_salida.pdf', 'I');
  }

  public function palletBig_pdf($id_pallet)
  {
    // Obtiene los datos del reporte.
    $data = $this->getInfoPallet($id_pallet, false, false);

    // echo "<pre>";
    //   var_dump($data);
    // echo "</pre>";exit;

    $certificados = $data;
    $noCertificados = $data;

    $certificados['rendimientos'] = array();
    $certificados['info'] = clone $data['info'];
    $certificados['info']->cajas = 0;
    $certificados['info']->kilos_pallet = 0;

    $noCertificados['rendimientos'] = array();
    $noCertificados['info'] = clone $data['info'];
    $noCertificados['info']->cajas = 0;
    $noCertificados['info']->kilos_pallet = 0;

    foreach ($data['rendimientos'] as $rendimiento)
    {
      if ($rendimiento->certificado === 't')
      {
        $certificados['rendimientos'][] = $rendimiento;
        $certificados['info']->cajas += $rendimiento->cajas;
        $certificados['info']->kilos_pallet += $rendimiento->kilos * $rendimiento->cajas;
      }
      else
      {
        $noCertificados['rendimientos'][] = $rendimiento;
        $noCertificados['info']->cajas += $rendimiento->cajas;
        $noCertificados['info']->kilos_pallet += $rendimiento->kilos * $rendimiento->cajas;
      }
    }

    // echo "<pre>";
    //   var_dump($data, $certificados, $noCertificados);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm');
    $pdf->show_head = false;

    $this->mergePalletsPdf($pdf, $data);

    if (count($certificados['rendimientos']) > 0)
    {
      $this->mergePalletsPdf($pdf, $certificados, true);
    }

    if (count($noCertificados['rendimientos']) > 0 && count($certificados['rendimientos']) > 0)
    {
      $this->mergePalletsPdf($pdf, $noCertificados);
    }
    // $pdf->AliasNbPages();
  //   $pdf->AddPage();
    // $pdf->SetFont('helvetica','B', 14);

    // $pdf->SetAligns(array('L'));
    // $pdf->SetWidths(array(90));

    // $pdf->Rect(6, 8, 100, 60, '');
    // $pdf->SetXY(23, 23);
    // $pdf->Image(APPPATH.'images/logo.png', null, null, 65);

    // $pdf->Rect(106, 8, 100, 20, '');
    // $pdf->SetXY(110, 13);
    // $pdf->Cell(33, 10, 'PALLET No. ', 0);
    // $pdf->SetFont('helvetica','B', 22);
    // $pdf->Cell(90, 10, $data['info']->folio, 0);
    // $pdf->SetFont('helvetica','B', 14);

    // $pdf->Rect(106, 28, 100, 40, '');
    // $pdf->SetXY(109, 30);
    // $pdf->Cell(90, 10, 'PACKING DATE/ FECHA DE EMPAQUE', 0);
    // $pdf->SetFont('helvetica','B', 22);
    // $pdf->SetXY(109, 45);
    // $pdf->Row(array( date("d/m/Y", strtotime($data['info']->fecha)) ), false, false);
    // $pdf->SetFont('helvetica','B', 14);

    // $pdf->Rect(6, 68, 100, 80, '');
    // $pdf->SetXY(9, 70);
    // $pdf->Cell(90, 10, 'SIZE/ CALIBRE', 0);
    // $nombre_calibres = array();
  //   if($data['info']->calibre_fijo == '')
  //   {
  //    foreach ($data['rendimientos'] as $key => $value)
  //    {
  //      if( ! isset($nombre_calibres[$value->size]) )
  //        $nombre_calibres[$value->size] = 1;
  //    }
  //     $data['info']->calibre_fijo = implode(',', array_keys($nombre_calibres));
  //   }
    // $pdf->SetFont('helvetica','B', 22);
    // $pdf->SetXY(9, 80);
    // $pdf->Row(array( $data['info']->calibre_fijo ), false, false);
    // $pdf->SetFont('helvetica','B', 14);

    // $pdf->Rect(6, 108, 100, 40, '');
    // $pdf->SetXY(9, 110);
    // $pdf->Cell(90, 10, 'KILOS', 0);
    // $pdf->SetFont('helvetica','B', 30);
    // $pdf->SetXY(9, 120);
    // $pdf->Row(array($data['info']->kilos_pallet), false, false);
    // $pdf->SetFont('helvetica','B', 14);

    // $pdf->Rect(106, 68, 100, 40, '');
    // $pdf->SetXY(109, 70);
    // $pdf->Cell(90, 10, 'CLIENTE', 0);
    // $pdf->SetXY(109, 80);

  //   $nombreFiscal = isset($data['cliente']->nombre_fiscal) ? $data['cliente']->nombre_fiscal : '';
    // $pdf->Row(array($nombreFiscal), false, false);

    // $pdf->Rect(106, 108, 100, 40, '');
    // $pdf->SetXY(109, 110);
    // $pdf->Cell(90, 10, 'BOXES/ CAJAS', 0);
    // $pdf->SetFont('helvetica','B', 30);
    // $pdf->SetXY(109, 120);
    // $pdf->Row(array($data['info']->cajas), false, false);
    // $pdf->SetFont('helvetica','B', 14);

    // $pdf->Rect(6, 148, 20, 111, '');
    // $pdf->RotatedText(16, 240, 'LISTA DE LOTIFICACION', 90);

    // $pdf->SetXY(26, 148);
    // $pdf->SetTextColor(0,0,0);
    // // $pdf->SetX(6);
    // $pdf->SetAligns(array('C', 'C'));
    // $pdf->SetWidths(array(80, 100));
    // $pdf->Row(array('No. LOTE', "No. DE CAJAS"), false);
    // $filas = 13;
    // foreach ($data['rendimientos'] as $key => $value) {
    //  $fecha = strtotime($value->fecha);
    //  $pdf->SetX(26);
    //  $pdf->Row(array(date("Ww", $fecha).'-'.$value->lote_ext, $value->cajas), false);
    //  $filas--;
    // }
    // for ($i = $filas; $i > 0; $i--)
    // {
    //  $pdf->SetX(26);
    //  $pdf->Row(array('', ''), false);
    // }

    $pdf->Output('REPORTE_DIARIO.pdf', 'I');
  }

  private function mergePalletsPdf(&$pdf, $data, $certificado = false)
  {
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('helvetica','B', 14);

    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(90));

    $pdf->Rect(6, 8, 100, 20, '');
    $pdf->SetXY(35, 10);
    $pdf->Cell(45, 10, 'BITACORA 19A', 0);
    $pdf->SetXY(8, 16);
    $pdf->Cell(100, 10, 'CONTROL DE PRODUCTO EMPACADO', 0);

    $pdf->Rect(6, 8, 100, 60, '');
    $pdf->SetXY(23, 38);
    $pdf->Image(APPPATH.'images/logo.png', null, null, 65);

    $pdf->Rect(106, 8, 100, 20, '');
    $pdf->SetXY(110, 13);
    $pdf->Cell(33, 10, 'PALLET No. ', 0);
    $pdf->SetFont('helvetica','B', 22);
    $pdf->Cell(90, 10, $data['info']->folio, 0);
    $pdf->SetFont('helvetica','B', 14);

    $pdf->Rect(106, 28, 100, 40, '');
    $pdf->SetXY(109, 30);
    $pdf->Cell(90, 10, 'PACKING DATE/ FECHA DE EMPAQUE', 0);
    $pdf->SetFont('helvetica','B', 22);
    $pdf->SetXY(109, 45);
    $pdf->Row(array( date("d/m/Y", strtotime($data['info']->fecha)) ), false, false);
    $pdf->SetFont('helvetica','B', 14);

    $pdf->Rect(6, 68, 100, 80, '');
    $pdf->SetXY(9, 70);
    $pdf->Cell(90, 10, 'SIZE/ CALIBRE', 0);
    $nombre_calibres = array();
    if($data['info']->calibre_fijo == '')
    {
      foreach ($data['rendimientos'] as $key => $value)
      {
        if( ! isset($nombre_calibres[$value->size]) )
          $nombre_calibres[$value->size] = 1;
      }
      $data['info']->calibre_fijo = implode(',', array_keys($nombre_calibres));
    }
    $pdf->SetFont('helvetica','B', 22);
    $pdf->SetXY(9, 80);
    $pdf->Row(array( $data['info']->calibre_fijo ), false, false);
    $pdf->SetFont('helvetica','B', 14);

    $pdf->Rect(6, 108, 100, 40, '');
    $pdf->SetXY(9, 110);
    $pdf->Cell(90, 10, 'KILOS', 0);
    $pdf->SetFont('helvetica','B', 30);
    $pdf->SetXY(9, 120);
    $pdf->Row(array($data['info']->kilos_pallet), false, false);
    $pdf->SetFont('helvetica','B', 14);

    $pdf->Rect(106, 68, 100, 40, '');
    $pdf->SetXY(109, 70);
    $pdf->Cell(90, 10, 'CLIENTE', 0);
    $pdf->SetXY(109, 80);

    $nombreFiscal = isset($data['cliente']->nombre_fiscal) ? $data['cliente']->nombre_fiscal : '';
    $pdf->Row(array($nombreFiscal), false, false);

    $pdf->Rect(106, 108, 100, 40, '');
    $pdf->SetXY(109, 110);
    $pdf->Cell(90, 10, 'BOXES/ CAJAS', 0);
    $pdf->SetFont('helvetica','B', 30);
    $pdf->SetXY(109, 120);
    $pdf->Row(array($data['info']->cajas), false, false);
    $pdf->SetFont('helvetica','B', 14);

    $pdf->Rect(6, 148, 20, 111, '');
    $pdf->RotatedText(12, 240, 'LISTA DE LOTIFICACION', 90);
    $pdf->RotatedText(18, 240, $data['info']->nombre_area, 90);

    if ($certificado)
    {
      $pdf->SetFont('helvetica','B', 15);
      $pdf->RotatedText(24, 240, 'PROD. CERTIFICADO', 90);
    }

    $pdf->SetFont('helvetica','B', 14);
    $pdf->SetXY(26, 148);
    $pdf->SetTextColor(0,0,0);
    // $pdf->SetX(6);
    $pdf->SetAligns(array('C', 'C'));
    $pdf->SetWidths(array(80, 100));
    $pdf->Row(array('No. LOTE', "No. DE CAJAS"), false);
    $filas = 13;
    foreach ($data['rendimientos'] as $key => $value) {
      $fecha = strtotime($value->fecha);
      $pdf->SetX(26);
      $pdf->Row(array(date("Ww", $fecha).'-'.$value->lote_ext, $value->cajas), false);
      $filas--;
    }
    for ($i = $filas; $i > 0; $i--)
    {
      $pdf->SetX(26);
      $pdf->Row(array('', ''), false);
    }
  }

  public function addPalletRendimiento($id_clasificacion){
    $pallets = $this->db->query("SELECT
                  id_pallet, id_clasificacion, folio, fecha, no_cajas, status, nombre, cajas, cajas_faltantes
                FROM rastria_pallets_lista
                WHERE id_clasificacion = {$id_clasificacion} AND cajas_faltantes > 0
                ORDER BY id_pallet ASC");
    foreach ($pallets->result() as $key => $pallet) {
      $cajas_faltantes = $pallet->cajas_faltantes;
      $cajas_pallet = $pallet->cajas;
      $pallets_rendimiento = array();
      $cajas = $this->db->query("SELECT
                    id_rendimiento, id_clasificacion, rendimiento, cajas, libres
                  FROM rastria_cajas_libres
                  WHERE id_clasificacion = {$id_clasificacion}
                  ORDER BY id_rendimiento ASC");
      foreach ($cajas->result() as $key => $caja) {
        if($cajas_pallet < $pallet->cajas_faltantes){
          $cajas_agregar = ($caja->libres>=$cajas_faltantes? $cajas_faltantes: $caja->libres);
          $pallets_rendimiento[] = array(
                'id_pallet'        => $pallet->id_pallet,
                'id_rendimiento'   => $caja->id_rendimiento,
                'id_clasificacion' => $pallet->id_clasificacion,
                'cajas'            => $cajas_agregar,
            );
          $cajas_pallet   += $cajas_agregar;
          $cajas_faltantes -= $cajas_agregar;
        }else
          break;
      }
      $cajas->free_result();

      if(count($pallets_rendimiento) > 0)
        $this->db->insert_batch('rastria_pallets_rendimiento', $pallets_rendimiento);
    }
    $pallets->free_result();
    return true;
  }

  public function checkPalletPendiente($id_clasificacion){
    $result = $this->db->query("SELECT Count(id_pallet) AS num
                               FROM rastria_pallets_lista
                               WHERE id_clasificacion = {$id_clasificacion} AND cajas_faltantes > 0")->row();
    return ($result->num == 0? true: false);
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */