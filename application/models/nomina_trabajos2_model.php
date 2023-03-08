<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class nomina_trabajos2_model extends CI_Model {
  private $pren_destajo = [];
  private $pren_descuentos = [];

  public function save($datos)
  {
    $this->load->model('nomina_fiscal_model');

    $data = array(
      'id_empresa'  => $datos['id_empresa'],
      'id_usuario'  => $datos['id_empleado'],
      'fecha'       => $datos['fecha'],
      'rows'        => isset($datos['rows'])? $datos['rows']: uniqid(),
      'id_labor'    => $datos['id_labor'],
      'id_area'     => $datos['id_area'],
      'anio'        => $datos['anio'],
      'semana'      => $datos['semana'],
      'costo'       => floatval($datos['costo']),
      'avance'      => floatval($datos['avance']),
      'avance_real' => (floatval($datos['avance_real'])>0? floatval($datos['avance_real']): floatval($datos['avance'])),
      'importe'     => floatval($datos['importe']),
    );

    if (isset($datos['rows'])){
      $this->db->update('nomina_trabajos_dia2', $data,
        "id_empresa = {$data['id_empresa']} AND id_usuario = {$data['id_usuario']} AND fecha = '{$data['fecha']}' AND rows = '{$data['rows']}'");

      $this->db->delete('nomina_trabajos_dia2_rancho',
        "id_empresa = {$data['id_empresa']} AND id_usuario = {$data['id_usuario']} AND fecha = '{$data['fecha']}' AND rows = '{$data['rows']}'");
      $this->db->delete('nomina_trabajos_dia2_centro_costo',
        "id_empresa = {$data['id_empresa']} AND id_usuario = {$data['id_usuario']} AND fecha = '{$data['fecha']}' AND rows = '{$data['rows']}'");
    } else {
      $this->db->insert('nomina_trabajos_dia2', $data);
    }

    $ranchos = [];
    if (isset($datos['ranchos']) && count($datos['ranchos']) > 0) {
      foreach ($datos['ranchos'] as $key => $value) {
        $ranchos[] = array(
          'id_empresa' => $data['id_empresa'],
          'id_usuario' => $data['id_usuario'],
          'fecha'      => $data['fecha'],
          'rows'       => $data['rows'],
          'id_rancho'  => $value['id'],
          'num'        => count($datos['ranchos']),
        );
      }

      if (count($ranchos) > 0) {
        $this->db->insert_batch('nomina_trabajos_dia2_rancho', $ranchos);
      }
    }

    $centros_costos = [];
    if (isset($datos['centros_costos']) && count($datos['centros_costos']) > 0) {
      foreach ($datos['centros_costos'] as $key => $value) {
        $centros_costos[] = array(
          'id_empresa'      => $data['id_empresa'],
          'id_usuario'      => $data['id_usuario'],
          'fecha'           => $data['fecha'],
          'rows'            => $data['rows'],
          'id_centro_costo' => $value['id'],
          'num'             => count($datos['centros_costos']),
        );
      }

      if (count($centros_costos) > 0) {
        $this->db->insert_batch('nomina_trabajos_dia2_centro_costo', $centros_costos);
      }
    }

    return array('passess' => true, 'data' => $this->getActividad($data));
  }

  /**
   * Elimina una actividad de la BDD.
   *
   * @return array
   */
  public function delete($datos)
  {
    $this->db->delete('nomina_trabajos_dia2', "id_empresa = {$datos['empresaId']}
      AND id_usuario = {$datos['id_usuario']} AND fecha = '{$datos['ffecha']}'
      AND rows = '{$datos['rows']}'");
    $this->db->delete('nomina_trabajos_dia2_centro_costo', "id_empresa = {$datos['empresaId']}
      AND id_usuario = {$datos['id_usuario']} AND fecha = '{$datos['ffecha']}'
      AND rows = '{$datos['rows']}'");
    $this->db->delete('nomina_trabajos_dia2_rancho', "id_empresa = {$datos['empresaId']}
      AND id_usuario = {$datos['id_usuario']} AND fecha = '{$datos['ffecha']}'
      AND rows = '{$datos['rows']}'");

    return array('passess' => true);
  }

  public function getActividades($fecha, $id_empresa, $filtros = [])
  {
    //paginacion
    $this->load->library('pagination');
    $params = array(
        'result_items_per_page' => '100',
        'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
    );
    if($params['result_page'] % $params['result_items_per_page'] == 0)
      $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);


    $data = array();

    $sql_str = '';
    if (isset($filtros['id_trabajador']) && $filtros['id_trabajador'] > 0) {
      $sql_str .= " AND u.id = {$filtros['id_trabajador']}";
    }

    if (isset($filtros['buscar']) && $filtros['buscar'] != '') {
      $sql_str .= " AND Lower(u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) LIKE '%".mb_strtolower($filtros['buscar'], 'UTF-8')."%'";
    }

    $sql =
      "SELECT nt2.id_empresa, e.nombre_fiscal AS empresa, nt2.id_usuario,
        (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS trabajador,
        nt2.fecha, nt2.rows, nt2.id_labor, l.nombre AS labor, nt2.id_area, a.nombre AS cultivo,
        nt2.anio, nt2.semana, nt2.costo, nt2.avance, nt2.importe
      FROM nomina_trabajos_dia2 nt2
        INNER JOIN empresas e ON e.id_empresa = nt2.id_empresa
        INNER JOIN usuarios u ON u.id = nt2.id_usuario
        INNER JOIN compras_salidas_labores l ON l.id_labor = nt2.id_labor
        LEFT JOIN areas a ON a.id_area = nt2.id_area
      WHERE e.id_empresa = {$id_empresa} AND nt2.fecha = '{$fecha}' {$sql_str}
      ORDER BY trabajador ASC, rows ASC";
    $sql = BDUtil::pagination($sql, $params, true);
    $query = $this->db->query($sql['query']);

    $response = array();
    if ($query->num_rows() > 0) {
      $dataa = $query->result();
      $aux = '';
      $aux_area = '';
      foreach ($dataa as $key => $value) {
        $value->centros_costos = $this->db->query(
          "SELECT cc.id_centro_costo, cc.nombre, ntd.num
          FROM nomina_trabajos_dia2_centro_costo ntd
            INNER JOIN otros.centro_costo cc ON cc.id_centro_costo = ntd.id_centro_costo
          WHERE ntd.id_empresa = {$id_empresa} AND ntd.id_usuario = {$value->id_usuario}
            AND ntd.fecha = '{$fecha}' AND ntd.rows = '{$value->rows}'
          ORDER BY nombre ASC
          ")->result();

        $value->ranchos = $this->db->query(
          "SELECT r.id_rancho, r.nombre, ntd.num
          FROM nomina_trabajos_dia2_rancho ntd
            INNER JOIN otros.ranchos r ON r.id_rancho = ntd.id_rancho
          WHERE ntd.id_empresa = {$id_empresa} AND ntd.id_usuario = {$value->id_usuario}
            AND ntd.fecha = '{$fecha}' AND ntd.rows = '{$value->rows}'
          ORDER BY nombre ASC
          ")->result();

        $response[] = $value;
      }
    }

    $response = array(
        'tareas_dia'     => $response,
        'total_rows'     => isset($sql['total_rows'])? $sql['total_rows']: 0,
        'items_per_page' => isset($params['result_items_per_page'])? $params['result_items_per_page']: 0,
        'result_page'    => isset($params['result_page'])? $params['result_page']: 0
    );
    return $response;
  }

  /**
   * [getActividad description]
   * @param  array(
      'id_empresa',
      'id_usuario',
      'fecha',
      'rows'
    )
   * @return [type]             [description]
   */
  public function getActividad($params)
  {
    $data = array();

    $sql = $this->db->query(
      "SELECT nt2.id_empresa, e.nombre_fiscal AS empresa, nt2.id_usuario,
        (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS trabajador,
        nt2.fecha, nt2.rows, nt2.id_labor, l.nombre AS labor, nt2.id_area, a.nombre AS cultivo,
        nt2.anio, nt2.semana, nt2.costo, nt2.avance, nt2.importe
      FROM nomina_trabajos_dia2 nt2
        INNER JOIN empresas e ON e.id_empresa = nt2.id_empresa
        INNER JOIN usuarios u ON u.id = nt2.id_usuario
        INNER JOIN compras_salidas_labores l ON l.id_labor = nt2.id_labor
        LEFT JOIN areas a ON a.id_area = nt2.id_area
      WHERE e.id_empresa = {$params['id_empresa']} AND nt2.fecha = '{$params['fecha']}'
        AND u.id = {$params['id_usuario']} AND nt2.rows = '{$params['rows']}'
      ");

    $response = new stdClass();
    if ($sql->num_rows() > 0) {
      $response = $sql->row();

      $response->centros_costos = $this->db->query(
        "SELECT cc.id_centro_costo, cc.nombre, ntd.num
        FROM nomina_trabajos_dia2_centro_costo ntd
          INNER JOIN otros.centro_costo cc ON cc.id_centro_costo = ntd.id_centro_costo
        WHERE ntd.id_empresa = {$params['id_empresa']} AND ntd.id_usuario = {$params['id_usuario']}
          AND ntd.fecha = '{$params['fecha']}' AND ntd.rows = '{$params['rows']}'
        ORDER BY nombre ASC
        ")->result();

      $response->ranchos = $this->db->query(
        "SELECT r.id_rancho, r.nombre, ntd.num
        FROM nomina_trabajos_dia2_rancho ntd
          INNER JOIN otros.ranchos r ON r.id_rancho = ntd.id_rancho
        WHERE ntd.id_empresa = {$params['id_empresa']} AND ntd.id_usuario = {$params['id_usuario']}
          AND ntd.fecha = '{$params['fecha']}' AND ntd.rows = '{$params['rows']}'
        ORDER BY nombre ASC
        ")->result();
    }

    return $response;
  }

  /**
   * @param  array(
      'id_empresa',
      'anio',
      'semana',
    )
   * @return [type]
   */
  public function totalesXTrabajador($filtros)
  {
    $query = $this->db->query("SELECT u.id AS id_usuario,
        (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS trabajador,
        nt2.anio, nt2.semana, Sum(nt2.avance) AS avance, Sum(nt2.importe) AS importe,
        (Sum(nt2.importe) / Coalesce(Nullif(Sum(nt2.avance), 0), 1))::Numeric(12, 2) AS costo,
        nfmr.id AS id_reg, nt2.id_empresa
      FROM nomina_trabajos_dia2 nt2
        INNER JOIN usuarios u ON u.id = nt2.id_usuario
        LEFT JOIN nomina_fiscal_monto_real nfmr ON (nfmr.id_empleado = u.id AND
          nfmr.id_empresa = nt2.id_empresa AND nfmr.anio = nt2.anio AND
          nfmr.semana = nt2.semana)
      WHERE nt2.id_empresa = {$filtros['id_empresa']}
        AND nt2.anio = {$filtros['anio']} and nt2.semana = {$filtros['semana']}
      GROUP BY u.id, nt2.anio, nt2.semana, nt2.id_empresa, nfmr.id
      ORDER BY trabajador ASC");
    $response = $query->result();

    return $response;
  }



  public function ticketNominaFiscal($semana, $empresaId, $registro_patronal, $anio=null, $diaComienza=4)
  {
    $anio = $anio==null? date("Y"): $anio;
    $this->load->model('empresas_model');
    $this->load->model('nomina_fiscal_model');
    $this->load->model('usuarios_departamentos_model');

    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);

    if ($empresaId !== '') {
      $diaComienza = $empresa['info']->dia_inicia_semana;
    }

    $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($semana, $anio, $diaComienza);
    $empleados = $this->db->query("SELECT nf.*, (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS trabajador
      FROM nomina_fiscal nf INNER JOIN usuarios u ON u.id = nf.id_empleado
      WHERE nf.id_empresa = {$empresaId} AND nf.anio = {$anio}
        AND nf.semana = {$semana['semana']} AND nf.registro_patronal = '{$registro_patronal}'
      ")->result();
    // echo "<pre>";
    //   var_dump($empleados);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 270));
    $pdf->show_head = false;

    $descuentoss = [
      'infonavit' => ['r', 'Infonavit'], 'dePensionAlimenticia' => ['od', 'Pensión Alimenticia'],
      'fonacot' => ['od', 'Fonacot'], 'fondo_ahorro' => ['r', 'Fondo de Ahorro'],
      'descuento_playeras' => ['r', 'Playeras'], 'descuento_cocina' => ['r', 'Cocina'],
      'descuento_otros' => ['r', 'Otros'], 'totalDescuentoMaterial' => ['od', 'Material'],
      'totalPrestamosEf' => ['od', 'Prestamos Efectivo'], 'prestamos' => ['r', 'Prestamos']
    ];
    $bonoss = ['bonos', 'otros', 'domingo'];

    $pdf->SetXY(0, 3);
    foreach ($empleados as $key => $empleado) {
      $empleado->otros_datos = json_decode($empleado->otros_datos);
      $pdf->AddPage();
      $pdf->AddFont($pdf->fount_num, '');

      $tareas = $this->db->query("SELECT t2.fecha, t2.costo, t2.avance, t2.avance_real,
          t2.importe, sl.nombre AS labor, sl.codigo, nr2.ranchos
        FROM nomina_trabajos_dia2 t2
          INNER JOIN compras_salidas_labores sl ON sl.id_labor = t2.id_labor
          INNER JOIN (
            SELECT dr2.id_empresa, dr2.id_usuario, dr2.fecha, dr2.rows,
              String_agg(r.codigo, ',') AS ranchos
            FROM public.nomina_trabajos_dia2_rancho dr2
              INNER JOIN otros.ranchos r ON r.id_rancho = dr2.id_rancho
            GROUP BY dr2.id_empresa, dr2.id_usuario, dr2.fecha, dr2.rows
          ) nr2 ON nr2.id_empresa = t2.id_empresa AND nr2.id_usuario = t2.id_usuario AND nr2.fecha = t2.fecha AND nr2.rows = t2.rows
        WHERE t2.anio = {$anio} AND t2.semana = {$semana['semana']}
          AND t2.id_empresa = {$empresaId} AND t2.id_usuario = {$empleado->id_empleado}
        ORDER BY fecha ASC")->result();

      // Título
      $pdf->SetWidths(array($pdf->pag_size[0]));
      $pdf->SetAligns(array('C'));
      $pdf->SetFont($pdf->fount_txt, '', 8);
      $pdf->SetFounts(array($pdf->fount_txt), array(0));
      $pdf->SetXY(0, $pdf->GetY());
      $pdf->Row2(array($empresa['info']->nombre_fiscal), false, false, 5);

      $pdf->SetFounts(array($pdf->fount_txt), array(-1));
      $pdf->SetXY(0, $pdf->GetY()-1);
      $pdf->Row2(array("Recibo de Raya de {$semana['fecha_inicio']} al {$semana['fecha_final']}"), false, false, 5);

      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->SetFounts(array($pdf->fount_txt), array(-1));
      $pdf->Row2(array('--------------------------------------------------------------------------'), false, false, 5);

      $pdf->SetAligns(array('L'));
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array("{$empleado->trabajador}"), false, false, 5);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('--------------------------------------------------------------------------'), false, false, 5);
      $pdf->SetFounts(array($pdf->fount_txt), array(-2));
      $pdf->SetAligns(array('C'));
      $pdf->SetXY(0, $pdf->GetY()-3);
      $pdf->Row2(array('ACTIVIDADES'), false, false, 3);
      $pdf->SetFounts(array($pdf->fount_txt), array(-1));
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('--------------------------------------------------------------------------'), false, false, 5);

      $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_txt, $pdf->fount_txt, $pdf->fount_num, $pdf->fount_num), array(-3, -3, -3, -3, -3));
      $pdf->SetWidths(array(9, 7, 25, 10, 12));
      $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R'));
      $total_ingresos = 0;
      foreach ($tareas as $keyt => $tarea) {
        $pdf->SetXY(0, $pdf->GetY()-2);
        $pdf->Row2(array(
          MyString::fechaATexto($tarea->fecha, 'inm'),
          $tarea->ranchos, $tarea->labor, $tarea->avance_real,
          MyString::formatoNumero($tarea->importe, 2, '$', false)), false, false, 5);
        $total_ingresos += $tarea->importe;
      }

      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->SetWidths(array($pdf->pag_size[0]));
      $pdf->SetFounts(array($pdf->fount_txt), array(-1));
      $pdf->Row2(array('--------------------------------------------------------------------------'), false, false, 5);
      $pdf->SetFounts(array($pdf->fount_txt), array(-2));
      $pdf->SetAligns(array('C'));
      $pdf->SetXY(0, $pdf->GetY()-3);
      $pdf->Row2(array('BONOS'), false, false, 3);
      $pdf->SetFounts(array($pdf->fount_txt), array(-1));
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('--------------------------------------------------------------------------'), false, false, 5);
      $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_num), array(-3, -3));
      $pdf->SetWidths(array(51, 12));
      $pdf->SetAligns(array('L', 'R'));
      $total_bonos = 0;
      foreach ($bonoss as $key => $fields) {
        $value = isset($empleado->{$fields})? $empleado->{$fields}: 0;
        if ($value > 0) {
          $pdf->SetXY(0, $pdf->GetY()-2);
          $pdf->Row2(array(ucfirst($fields), MyString::formatoNumero($value, 2, '$', false)), false, false, 4);
          $total_bonos += $value;
        }
      }

      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->SetWidths(array($pdf->pag_size[0]));
      $pdf->SetFounts(array($pdf->fount_txt), array(-1));
      $pdf->Row2(array('--------------------------------------------------------------------------'), false, false, 5);
      $pdf->SetFounts(array($pdf->fount_txt), array(-2));
      $pdf->SetAligns(array('C'));
      $pdf->SetXY(0, $pdf->GetY()-3);
      $pdf->Row2(array('DESCUENTOS'), false, false, 3);
      $pdf->SetFounts(array($pdf->fount_txt), array(-1));
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('--------------------------------------------------------------------------'), false, false, 5);
      $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_num), array(-3, -3));
      $pdf->SetWidths(array(51, 12));
      $pdf->SetAligns(array('L', 'R'));
      $total_descuentos = 0;
      foreach ($descuentoss as $fields => $tipo) {
        $value = $tipo[0] === 'od'? (isset($empleado->otros_datos->{$fields})? $empleado->otros_datos->{$fields}: 0): $empleado->{$fields};
        if ($value > 0) {
          $pdf->SetXY(0, $pdf->GetY()-2);
          $pdf->Row2(array($tipo[1], MyString::formatoNumero($value, 2, '$', false)), false, false, 4);
          $total_descuentos += $value;
        }
      }

      $pdf->SetFounts(array($pdf->fount_txt), array(-1));
      $pdf->SetWidths(array($pdf->pag_size[0]));
      $pdf->SetAligns(array('C'));
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('--------------------------------------------------------------------------'), false, false, 5);

      $pdf->SetFounts(array($pdf->fount_txt), array(0));
      $pdf->SetWidths(array(31, 31));
      $pdf->SetAligns(array('L', 'R'));
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('Total', MyString::formatoNumero($total_ingresos + $total_bonos - $total_descuentos, 2, '$', false)), false, false, 5);

      $pdf->SetFounts(array($pdf->fount_txt), array(-1));
      $pdf->SetWidths(array($pdf->pag_size[0]));
      $pdf->SetAligns(array('C'));
      $pdf->SetXY(0, $pdf->GetY()+3);
      $pdf->Row2(array('____________________________'), false, false, 5);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('FIRMA'), false, false, 5);

      $pdf->SetXY(0, $pdf->GetY()+10);
    }

    // $pdf->AutoPrint(true);
    $pdf->Output('nomina_ticket', 'I');
  }



  /**
   * Reportes
   *******************************
   * @return void
   */
  public function rptCostoLaboresData()
  {
    $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $sql .= " AND Date(t2.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'";

    if($this->input->get('dlaborId') != ''){
      $dlaborId = $this->input->get('dlaborId');
      $sql .= " AND t2.id_labor = ".$dlaborId;
    }

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND t2.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if ($this->input->get('areaId') > 0) {
      $sql .= " AND t2.id_area = ".$this->input->get('areaId');
    }

    if ($this->input->get('dempleadoId') > 0) {
      $sql .= " AND t2.id_usuario = ".$this->input->get('dempleadoId');
    }

    if(is_array($this->input->get('ranchoId'))){
      $sql .= " AND cc.id_rancho IN (".implode(',', $this->input->get('ranchoId')).")";
    }

    if(is_array($this->input->get('centroCostoId'))){
      $sql .= " AND cc.id_centro_costo IN (".implode(',', $this->input->get('centroCostoId')).")";
    }

    $res = $this->db->query(
      "SELECT cc.id_centro_costo, cc.tabla, Sum(cc.hectareas)/Count(cc.hectareas) AS hectareas,
        Sum(t2.avance/cc.num) AS avance, Sum(t2.avance_real/cc.num) AS avance_real,
        Sum(t2.importe/cc.num) AS importe
      FROM nomina_trabajos_dia2 t2
        INNER JOIN nomina_trabajos_dia2_centros cc ON (t2.id_empresa = cc.id_empresa AND
            t2.id_usuario = cc.id_usuario AND t2.fecha = cc.fecha AND t2.rows = cc.rows)
      WHERE 1 = 1 {$sql}
      GROUP BY cc.id_centro_costo, cc.tabla
      ");

    $response = array();
    if($res->num_rows() > 0)
      $response = $res->result();

    return $response;
  }
  public function rptCostoLaboresPdf(){
    $res = $this->rptCostoLaboresData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

      if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'Reporte Costos por Labor';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $pdf->titulo3 .= ($this->input->get('area')? "Cultivo: {$this->input->get('area')} | " : '');
    $pdf->titulo3 .= ($this->input->get('ranchoText')? "Ranchos: ".implode(', ', $this->input->get('ranchoText'))." | " : '');
    $pdf->titulo3 .= ($this->input->get('centroCostoText')? "Centros: ".implode(', ', $this->input->get('centroCostoText'))." | " : '');
    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'R', 'R', 'R', 'R');
    $widths = array(95, 25, 25, 25, 30);
    $header = array('Tabla', 'Superficie', 'Avance', 'Avance R.', 'Importe');

    $total_avance = $total_avancerr = $total_importe = 0;
    foreach($res as $key => $item){
      $band_head = false;
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

      $datos = array(
        $item->tabla,
        $item->hectareas,
        MyString::formatoNumero($item->avance, 2, '', false),
        MyString::formatoNumero($item->avance_real, 2, '', false),
        MyString::formatoNumero($item->importe, 2, '', false),
      );
      $total_avance += $item->avance;
      $total_avancerr += $item->avance_real;
      $total_importe += $item->importe;

      $_GET['id_centro_costo'] = $item->id_centro_costo;
      $pdf->SetMyLinks([base_url('panel/nomina_trabajos2/rpt_costo_labores_desg_pdf?'.MyString::getVarsLink([]))]);
      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false);
    }

    $pdf->SetMyLinks([]);
    $pdf->SetFont('Arial','B',8);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(['R', 'R', 'R']);
    $pdf->SetWidths([120, 25, 25, 30]);
    $pdf->Row(array('TOTAL',
      MyString::formatoNumero($total_avance, 2, '', false),
      MyString::formatoNumero($total_avancerr, 2, '', false),
      MyString::formatoNumero($total_importe, 2, '', false),
      ), false, true);

    $pdf->Output('costos_labor.pdf', 'I');
  }
  public function rptCostoLaboresXls(){
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=costos_labor.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->rptCostoLaboresData();

    $this->load->model('empresas_model');

    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte Costos por Labor';
    $titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $titulo3 .= ($this->input->get('area')? "Cultivo: {$this->input->get('area')} | " : '');
    $titulo3 .= ($this->input->get('ranchoText')? "Ranchos: ".implode(', ', $this->input->get('ranchoText'))." | " : '');
    $titulo3 .= ($this->input->get('centroCostoText')? "Centros: ".implode(', ', $this->input->get('centroCostoText'))." | " : '');

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
        </tr>
        <tr style="font-weight:bold">
          <td colspan="3" style="width:200px;border:1px solid #000;background-color: #cccccc;">Tabla</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Superficie</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Avance</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Avance R.</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Importe</td>
        </tr>';

    $total_avance = $total_avancerr = $total_importe = 0;
    foreach($res as $key => $item){
      $html .= '<tr>
          <td colspan="3" style="width:200px;border:1px solid #000;">'.$item->tabla.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->hectareas.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->avance.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->avance_real.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->importe.'</td>
        </tr>';
      $total_avance += $item->avance;
      $total_avancerr += $item->avance_real;
      $total_importe += $item->importe;
    }

    $html .= '
            <tr style="font-weight:bold">
              <td colspan="4"></td>
              <td style="border:1px solid #000;">'.$total_avance.'</td>
              <td style="border:1px solid #000;">'.$total_avancerr.'</td>
              <td style="border:1px solid #000;">'.$total_importe.'</td>
            </tr>';

    $html .= '</tbody>
    </table>';

    echo $html;
  }

  public function rptCostoLaboresDesglosadoData()
  {
    $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $sql .= " AND Date(t2.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'";

    if($this->input->get('dlaborId') != ''){
      $dlaborId = $this->input->get('dlaborId');
      $sql .= " AND t2.id_labor = ".$dlaborId;
    }

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND t2.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if ($this->input->get('areaId') > 0) {
      $sql .= " AND t2.id_area = ".$this->input->get('areaId');
    }

    if ($this->input->get('dempleadoId') > 0) {
      $sql .= " AND t2.id_usuario = ".$this->input->get('dempleadoId');
    }

    if(is_array($this->input->get('ranchoId'))){
      $sql .= " AND cc.id_rancho IN (".implode(',', $this->input->get('ranchoId')).")";
    }

    if($this->input->get('id_centro_costo') > 0){
      $sql .= " AND cc.id_centro_costo = ".$this->input->get('id_centro_costo')."";
    }

    $res = $this->db->query(
      "SELECT cc.id_centro_costo, t2.fecha, cc.tabla, (cc.hectareas) AS hectareas,
        (t2.avance/cc.num) AS avance, (t2.avance_real/cc.num) AS avance_real, (t2.importe/cc.num) AS importe,
        (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS trabajador,
        sl.nombre AS labor
      FROM nomina_trabajos_dia2 t2
        INNER JOIN usuarios u ON u.id = t2.id_usuario
        INNER JOIN compras_salidas_labores sl ON sl.id_labor = t2.id_labor
        INNER JOIN nomina_trabajos_dia2_centros cc ON (t2.id_empresa = cc.id_empresa AND
            t2.id_usuario = cc.id_usuario AND t2.fecha = cc.fecha AND t2.rows = cc.rows)
      WHERE 1 = 1 {$sql}
      ");

    $response = array();
    if($res->num_rows() > 0)
      $response = $res->result();

    return $response;
  }
  public function rptCostoLaboresDesglosadoPdf(){
    $res = $this->rptCostoLaboresDesglosadoData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

      if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'Reporte Costos por Labor Detallado';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $pdf->titulo3 .= ($this->input->get('area')? "Cultivo: {$this->input->get('area')} | " : '');
    $pdf->titulo3 .= ($this->input->get('ranchoText')? "Ranchos: ".implode(', ', $this->input->get('ranchoText'))." | " : '');
    $pdf->titulo3 .= (isset($res[0]->tabla)? "Centro: ".$res[0]->tabla : '');
    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $pdf->SetX(6);
    $pdf->SetAligns(['L']);
    $pdf->SetWidths([110]);
    $pdf->SetMyLinks([base_url('panel/nomina_trabajos2/rpt_costo_labores_desg_xls?'.MyString::getVarsLink([]))]);
    $pdf->Row(['Excel'], false, false);
    $pdf->SetMyLinks([]);

    $aligns = array('L', 'L', 'L', 'R', 'R', 'R');
    $widths = array(17, 70, 50, 20, 20, 25);
    $header = array('Fecha', 'Trabajador', 'Labor', 'Avance', 'Avance R.', 'Importe');

    $total_avance = $total_avancerr = $total_importe = 0;
    foreach($res as $key => $item){
      $band_head = false;
      if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        if ($pdf->GetY() >= $pdf->limiteY) {
          $pdf->AddPage();
        }

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
        $item->trabajador,
        $item->labor,
        MyString::formatoNumero($item->avance, 2, '', false),
        MyString::formatoNumero($item->avance_real, 2, '', false),
        MyString::formatoNumero($item->importe, 2, '', false),
      );
      $total_avance += $item->avance;
      $total_avancerr += $item->avance_real;
      $total_importe += $item->importe;

      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false);
    }

    $pdf->SetMyLinks([]);
    $pdf->SetFont('Arial','B',8);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(['R', 'R', 'R', 'R']);
    $pdf->SetWidths([137, 20, 20, 25]);
    $pdf->Row(array('TOTAL',
      MyString::formatoNumero($total_avance, 2, '', false),
      MyString::formatoNumero($total_avancerr, 2, '', false),
      MyString::formatoNumero($total_importe, 2, '', false),
      ), false, true);

    $pdf->Output('costos_labor_detallado.pdf', 'I');
  }
  public function rptCostoLaboresDesglosadoXls(){
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=costos_labor_detallado.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->rptCostoLaboresDesglosadoData();

    $this->load->model('empresas_model');

    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte Costos por Labor Detallado';
    $titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $titulo3 .= ($this->input->get('area')? "Cultivo: {$this->input->get('area')} | " : '');
    $titulo3 .= ($this->input->get('ranchoText')? "Ranchos: ".implode(', ', $this->input->get('ranchoText'))." | " : '');
    $titulo3 .= (isset($res[0]->tabla)? "Centro: ".$res[0]->tabla : '');

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
        </tr>
        <tr style="font-weight:bold">
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Fecha</td>
          <td colspan="3" style="width:200px;border:1px solid #000;background-color: #cccccc;">Trabajador</td>
          <td colspan="2" style="width:200px;border:1px solid #000;background-color: #cccccc;">Labor</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Avance</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Avance R.</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Importe</td>
        </tr>';

    $total_avance = $total_avancerr = $total_importe = 0;
    foreach($res as $key => $item){
      $html .= '<tr>
          <td style="width:200px;border:1px solid #000;">'.$item->fecha.'</td>
          <td colspan="3" style="width:200px;border:1px solid #000;">'.$item->trabajador.'</td>
          <td colspan="2" style="width:200px;border:1px solid #000;">'.$item->labor.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->avance.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->avance_real.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->importe.'</td>
        </tr>';
      $total_avance += $item->avance;
      $total_avancerr += $item->avance_real;
      $total_importe += $item->importe;
    }

    $html .= '
            <tr style="font-weight:bold">
              <td colspan="6"></td>
              <td style="border:1px solid #000;">'.$total_avance.'</td>
              <td style="border:1px solid #000;">'.$total_avancerr.'</td>
              <td style="border:1px solid #000;">'.$total_importe.'</td>
            </tr>';

    $html .= '</tbody>
    </table>';

    echo $html;
  }


  public function rptAuditoriaCostosData()
  {
    $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $sql .= " AND Date(t2.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'";

    if($this->input->get('dlaborId') != ''){
      $dlaborId = $this->input->get('dlaborId');
      $sql .= " AND t2.id_labor = ".$dlaborId;
    }

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND t2.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if ($this->input->get('areaId') > 0) {
      $sql .= " AND t2.id_area = ".$this->input->get('areaId');
    }

    if ($this->input->get('dempleadoId') > 0) {
      $sql .= " AND t2.id_usuario = ".$this->input->get('dempleadoId');
    }

    if(is_array($this->input->get('ranchoId'))){
      $sql .= " AND cc.id_rancho IN (".implode(',', $this->input->get('ranchoId')).")";
    }

    if(is_array($this->input->get('centroCostoId'))){
      $sql .= " AND cc.id_centro_costo IN (".implode(',', $this->input->get('centroCostoId')).")";
    }

    $res = $this->db->query(
      "SELECT cc.id_centro_costo, cc.tabla, sl.nombre AS labor,
        Sum(t2.avance/cc.num) AS avance, Sum(t2.importe/cc.num) AS importe
      FROM nomina_trabajos_dia2 t2
        INNER JOIN compras_salidas_labores sl ON sl.id_labor = t2.id_labor
        INNER JOIN nomina_trabajos_dia2_centros cc ON (t2.id_empresa = cc.id_empresa AND
            t2.id_usuario = cc.id_usuario AND t2.fecha = cc.fecha AND t2.rows = cc.rows)
      WHERE 1 = 1 {$sql}
      GROUP BY cc.id_centro_costo, cc.tabla, sl.nombre
      ORDER BY tabla ASC, labor ASC
      ");

    $response = array();
    if($res->num_rows() > 0)
      $response = $res->result();

    return $response;
  }
  public function rptAuditoriaCostosXls(){
    // header('Content-type: application/vnd.ms-excel; charset=utf-8');
    // header("Content-Disposition: attachment; filename=auditoria_costos.xls");
    // header("Pragma: no-cache");
    // header("Expires: 0");

    $res = $this->rptAuditoriaCostosData();

    $this->load->model('empresas_model');

    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte Auditoria de costos';
    $titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $titulo3 .= ($this->input->get('area')? "Cultivo: {$this->input->get('area')} | " : '');
    $titulo3 .= ($this->input->get('ranchoText')? "Ranchos: ".implode(', ', $this->input->get('ranchoText'))." | " : '');
    $titulo3 .= ($this->input->get('centroCostoText')? "Centros: ".implode(', ', $this->input->get('centroCostoText'))." | " : '');

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
        </tr>
        <tr style="font-weight:bold">
          <td colspan="2" style="width:200px;border:1px solid #000;background-color: #cccccc;">Tabla</td>
          <td colspan="2" style="width:200px;border:1px solid #000;background-color: #cccccc;">Labor</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Avance</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Importe</td>
          <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Imp/Avance</td>
        </tr>';

    $total_avance = $total_importe = 0;
    foreach($res as $key => $item){
      $html .= '<tr>
          <td colspan="2" style="width:200px;border:1px solid #000;">'.$item->tabla.'</td>
          <td colspan="2" style="width:200px;border:1px solid #000;">'.$item->labor.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->avance.'</td>
          <td style="width:200px;border:1px solid #000;">'.$item->importe.'</td>
          <td style="width:200px;border:1px solid #000;">'.($item->importe/$item->avance).'</td>
        </tr>';
      $total_avance += $item->avance;
      $total_importe += $item->importe;
    }

    $html .= '
            <tr style="font-weight:bold">
              <td colspan="4"></td>
              <td style="border:1px solid #000;">'.$total_avance.'</td>
              <td style="border:1px solid #000;">'.$total_importe.'</td>
              <td style="border:1px solid #000;">'.number_format($total_importe/$total_avance, 2, '.', '').'</td>
            </tr>';

    $html .= '</tbody>
    </table>';

    echo $html;
  }


  private function getDestajoTrabFecha($trabajador_id, $fecha)
  {
    $total = 0;
    $resultado = array_filter($this->pren_destajo, function($v) use(&$total, $trabajador_id, $fecha){
      if ($v->id_usuario == $trabajador_id && $v->fecha == $fecha) {
        $total += $v->importe;
        return true;
      }
      return false;
    });
    return $total;
  }
  private function getDestajoTrabDescuentos($trabajador_id)
  {
    $resultado = array_filter($this->pren_descuentos, function($v) use($trabajador_id){
      return ($v->id_empleado == $trabajador_id);
    });
    $resempty = (object)[
      'horas_extras' => 0,
      'desc_playeras' => 0,
      'desc_otros' => 0,
      'desc_cocina' => 0,
    ];
    return empty($resultado)? $resempty: $resultado[0];
  }
  public function rptPreNominaData()
  {
    if ($this->input->get('semana') <= 0 || !is_numeric($this->input->get('anio')) || $this->input->get('fregistro_patronal') == '') {
      return false;
    }

    $this->load->model('nomina_fiscal_model');

    $sql = '';

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND t2.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    $filtros = array(
      'calcMes'     => false,
      'semana'      => $_GET['semana'],
      'anio'        => $_GET['anio'],
      'empresaId'   => $_GET['did_empresa'],
      'puestoId'    => '',
      'regPatronal' => $_GET['fregistro_patronal'],
      'tipo_nomina' => ['tipo' => 'se', 'con_vacaciones' => '0', 'con_aguinaldo' => '0']
    );
    if ($filtros['empresaId'] !== '') {
      $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $filtros['empresaId'])->get()->row()->dia_inicia_semana;
    } else {
      $dia = '4';
    }
    $filtros['dia_inicia_semana'] = $dia;

    $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($filtros['semana'], $filtros['anio'], $dia);
    // $params['dias'] = MyString::obtenerSiguientesXDias($semana['fecha_inicio'], ($dia == 15? 15: 7));

     $_GET['cid_empresa'] = $filtros['empresaId']; //para las cuentas del contpaq
    $configuraciones = $this->nomina_fiscal_model->configuraciones($filtros['anio']);
    $nomina_empleados = $this->nomina_fiscal_model->nomina($configuraciones, $filtros);
    // echo "<pre>";
    // var_dump($nomina_empleados);
    // echo "</pre>";exit;

    $diasSemana = [];
    $fechaini = new DateTime($semana['fecha_inicio']);
    $fechaend = new DateTime($semana['fecha_final']);
    while ($fechaini <= $fechaend) {
      $diasSemana[$fechaini->format("Y-m-d")] = 0;
      $fechaini->modify('+1 day');
    }

    $this->pren_destajo = $this->db->query(
      "SELECT id_usuario, fecha, Sum(importe) AS importe
      FROM nomina_trabajos_dia2 t2
      WHERE t2.id_empresa = {$filtros['empresaId']} AND
        t2.fecha BETWEEN '{$semana['fecha_inicio']}' AND '{$semana['fecha_final']}'
      GROUP BY id_usuario, fecha
      ")->result();
    $this->pren_descuentos = $this->nomina_fiscal_model->getDescPreNomina($filtros['empresaId'], $filtros['anio'], $filtros['semana']);

    foreach ($nomina_empleados as $key => $trabajador) {
      $nomina_empleados[$key]->destajo = $diasSemana;
      $nomina_empleados[$key]->pre_descuentos = $this->getDestajoTrabDescuentos($trabajador->id);

      foreach ($diasSemana as $f => $value) {
        $nomina_empleados[$key]->destajo[$f] = $this->getDestajoTrabFecha($trabajador->id, $f);
      }

      $nomina_empleados[$key]->total_descuento_material = 0;
      $nomina_empleados[$key]->total_descuento_prestamos = 0;
      if (count($trabajador->prestamos) > 0) {
        foreach ($trabajador->prestamos as $f => $value) {
          if ($value['tipo'] == 'mt') {
            $nomina_empleados[$key]->total_descuento_material += $value['pago_semana'];
          } else {
            $nomina_empleados[$key]->total_descuento_prestamos += $value['pago_semana'];
          }
        }
      }
    }

    return ['data' => $nomina_empleados, 'semana' => $semana];
  }
  public function rptPreNominaPdf(){
    $res = $this->rptPreNominaData();
    if ($res === false) {
      return false;
    }

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('L', 'mm', 'Letter');

      if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'Reporte Pre Nomina';
    $pdf->titulo3 = "Año: {$res['semana']['anio']} | Semana: {$res['semana']['semana']} \n";
    $pdf->titulo3 .= "{$res['semana']['fecha_inicio']} Al {$res['semana']['fecha_final']}";
    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('Arial','',7);

    $totales_destajo = [];
    $aligns = array('L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R');
    $widths = array(60, 17, 17, 17, 17, 17, 17, 17, 17, 17, 17, 17, 17, 17, 17);
    $header = array('Trabajador');
    foreach ($res['data'][0]->destajo as $key => $value) {
      $header[] = $key;
      $totales_destajo[$key] = 0;
    }
    $header[] = 'T. Perce';
    $header[] = 'D. Fiscales';
    $header[] = 'Bonos';
    $header[] = 'D. Fuera';
    // $header[] = 'D. Material';
    // $header[] = 'D. Presta';
    $header[] = 'Total';
    $totales_destajo['tperce'] = 0;
    $totales_destajo['td_fiscales'] = 0;
    $totales_destajo['tbonos'] = 0;
    $totales_destajo['td_otros'] = 0;
    // $totales_destajo['td_mater'] = 0;
    // $totales_destajo['td_prest'] = 0;
    $totales_destajo['total_nom'] = 0;

    foreach($res['data'] as $key => $item){
      $total_destajo = $total_importe = 0;

      $band_head = false;
      if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',7);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
      }

      $pdf->SetFont('Arial','',7);
      $pdf->SetTextColor(0,0,0);

      $datos = array(
        $item->nombre
      );
      foreach ($item->destajo as $keyf => $valdestajo) {
        $datos[] = MyString::formatoNumero($valdestajo, 2, '', false);
        $total_destajo += $valdestajo;
        $totales_destajo[$keyf] += $valdestajo;
      }
      $datos[] = MyString::formatoNumero($total_destajo, 2, '', false);
      $totales_destajo['tperce'] += $total_destajo;

      $datos[] = MyString::formatoNumero(($item->infonavit + $item->p_alimenticia + $item->fonacot + $item->fondo_ahorro), 2, '', false);
      $totales_destajo['td_fiscales'] += ($item->infonavit + $item->p_alimenticia + $item->fonacot + $item->fondo_ahorro);

      $datos[] = MyString::formatoNumero(($item->bonos + $item->otros + $item->domingo), 2, '', false);
      $totales_destajo['tbonos'] += ($item->bonos + $item->otros + $item->domingo);

      $datos[] = MyString::formatoNumero(($item->pre_descuentos->desc_playeras + $item->pre_descuentos->desc_cocina + $item->pre_descuentos->desc_otros + $item->total_descuento_material + $item->total_descuento_prestamos), 2, '', false);
      $totales_destajo['td_otros'] += ($item->pre_descuentos->desc_playeras + $item->pre_descuentos->desc_cocina + $item->pre_descuentos->desc_otros + $item->total_descuento_material + $item->total_descuento_prestamos);

      $total_nom_trab = $total_destajo - ($item->infonavit + $item->p_alimenticia + $item->fonacot + $item->fondo_ahorro) +
        ($item->bonos + $item->otros + $item->domingo) - ($item->pre_descuentos->desc_playeras + $item->pre_descuentos->desc_cocina + $item->pre_descuentos->desc_otros + $item->total_descuento_material + $item->total_descuento_prestamos);
      $datos[] = MyString::formatoNumero($total_nom_trab, 2, '', false);
      $totales_destajo['total_nom'] += $total_nom_trab;

      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false);
    }

    $pdf->SetFont('Arial','B', 8);
    $pdf->SetXY(6, $pdf->GetY());
    $aligns = array('L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R');
    $widths = array(60, 17, 17, 17, 17, 17, 17, 17, 17, 17, 17, 17, 17, 17, 17);
    $row_totales = ['TOTALES'];
    foreach ($totales_destajo as $keyf => $tcol) {
      $row_totales[] = MyString::formatoNumero($tcol, 2, '', false);
    }
    $pdf->Row($row_totales, false, true);

    $pdf->Output('pre_nomina.pdf', 'I');
  }


}
/* End of file nomina_fiscal_model.php */
/* Location: ./application/models/nomina_fiscal_model.php */