<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class nomina_trabajos2_model extends CI_Model {

  public function save($datos)
  {
    $this->load->model('nomina_fiscal_model');

    $data = array(
      'id_empresa' => $datos['id_empresa'],
      'id_usuario' => $datos['id_empleado'],
      'fecha'      => $datos['fecha'],
      'rows'       => isset($datos['rows'])? $datos['rows']: uniqid(),
      'id_labor'   => $datos['id_labor'],
      'id_area'    => $datos['id_area'],
      'anio'       => $datos['anio'],
      'semana'     => $datos['semana'],
      'costo'      => floatval($datos['costo']),
      'avance'     => floatval($datos['avance']),
      'importe'    => floatval($datos['importe']),
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



  public function ticketNominaFiscal($semana, $empresaId, $anio=null, $diaComienza=4)
  {
    $anio = $anio==null? date("Y"): $anio;
    $this->load->model('empresas_model');
    $this->load->model('nomina_fiscal_model');
    $this->load->model('usuarios_departamentos_model');

    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);

    if ($empresaId !== '')
      $diaComienza = $empresa['info']->dia_inicia_semana;
      // $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $empresaId)->get()->row()->dia_inicia_semana;
    else
      $diaComienza = '4';

    $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($semana, $anio, $diaComienza);
    $empleados = $this->db->query("SELECT nf.*, (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS trabajador
      FROM nomina_fiscal nf INNER JOIN usuarios u ON u.id = nf.id_empleado
      WHERE nf.id_empresa = {$empresaId} AND nf.anio = {$anio} AND nf.semana = {$semana['semana']}
        ")->result();
    // echo "<pre>";
    //   var_dump($empleados);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 270));
    $pdf->show_head = false;
    $pdf->AddPage();
    $pdf->AddFont($pdf->fount_num, '');

    $pdf->SetXY(0, 3);
    foreach ($empleados as $key => $empleado) {
      $tareas = $this->db->query("SELECT ntd.*, ntdl.horas AS horas_tarea, ntdl.importe AS importe_tarea, csl.nombre AS labor
        FROM nomina_trabajos_dia ntd
          INNER JOIN nomina_trabajos_dia_labores ntdl ON (ntd.id_usuario = ntdl.id_usuario AND ntd.fecha = ntdl.fecha AND ntd.id_empresa = ntdl.id_empresa)
          INNER JOIN compras_salidas_labores csl ON csl.id_labor = ntdl.id_labor
        WHERE ntd.id_empresa = {$empresaId} AND Date(ntd.fecha) BETWEEN '{$semana['fecha_inicio']}' AND '{$semana['fecha_final']}'
          AND ntd.id_usuario = {$empleado->id_empleado}
        ORDER BY ntd.fecha ASC")->result();

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
      $pdf->SetAligns(array('C'));
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('ACTIVIDADES'), false, false, 5);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('--------------------------------------------------------------------------'), false, false, 5);

      $aux = '';
      $check_hrsext = false;
      foreach ($tareas as $keyt => $tarea) {
        if ($aux != $tarea->id_usuario.$tarea->fecha) {
          $pdf->font_bold = 'B';
          $pdf->SetWidths(array(31, 31));
          $pdf->SetAligns(array('L', 'R'));
          $pdf->SetXY(0, $pdf->GetY()-2);
          $pdf->Row2(array($tarea->fecha, MyString::formatoNumero($tarea->importe, 2, '$', false)), false, false, 5);
          $pdf->font_bold = '';
          $aux = $tarea->id_usuario.$tarea->fecha;

          $check_hrsext = true;
        }

        $pdf->SetWidths(array(37, 10, 15));
        $pdf->SetAligns(array('L', 'C', 'R'));

        if ($check_hrsext && $tarea->hrs_extra > 0) {
          $pdf->SetXY(0, $pdf->GetY()-2);
          $pdf->Row2(array('Hrs extras', $tarea->hrs_extra.' hrs', MyString::formatoNumero($tarea->importe_extra, 2, '$', false)), false, false, 5);

          $check_hrsext = false;
        }

        $pdf->SetXY(0, $pdf->GetY()-2);
        $pdf->Row2(array($tarea->labor, $tarea->horas_tarea.' hrs', MyString::formatoNumero($tarea->importe_tarea, 2, '$', false)), false, false, 5);
      }

      $pdf->SetWidths(array($pdf->pag_size[0]));
      $pdf->SetAligns(array('C'));
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('--------------------------------------------------------------------------'), false, false, 5);

      $pdf->SetFounts(array($pdf->fount_txt), array(0));
      $pdf->SetWidths(array(31, 31));
      $pdf->SetAligns(array('L', 'R'));
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('Total Percepciones', MyString::formatoNumero($empleado->total_percepcion - $empleado->subsidio, 2, '$', false)), false, false, 5);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('Total Deducciones', MyString::formatoNumero($empleado->total_deduccion - $empleado->subsidio, 2, '$', false)), false, false, 5);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('Total Neto', MyString::formatoNumero($empleado->total_neto, 2, '$', false)), false, false, 5);

      $pdf->SetXY(0, $pdf->GetY()+10);
    }

    // $pdf->AutoPrint(true);
    $pdf->Output('Venta_Remision', 'I');



    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = "Lista de Raya de {$semana['fecha_inicio']} al {$semana['fecha_final']}";
    $pdf->titulo3 = "Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $pdf->SetFont('Helvetica','', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(6, 27);
    $pdf->Cell(100, 6, "Reg. Pat. IMSS: {$empresa['info']->registro_patronal}", 0, 0, 'L', 0);

    $pdf->SetFont('Helvetica','B', 10);
    $pdf->SetXY(6, $pdf->GetY() + 6);
    $pdf->Cell(100, 6, "ADMINISTRACION Reg. Pat. IMSS: {$empresa['info']->registro_patronal}", 0, 0, 'L', 0);

    $total_gral = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0,
      'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'fondo_ahorro' => 0, 'isr' => 0,
      'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0, 'pasistencia' => 0, 'despensa' => 0);

    $numero_trabajadores2 = 0;
    $empleados_sin_departamento = [];
    foreach ($empleados as $key => $empleado) {
      $empleados_sin_departamento[$empleado->id] = $empleado;
      $numero_trabajadores2++;
    }

    // $departamentos = $this->usuarios_model->departamentos();
    $numero_trabajadores = 0;
    $_GET['did_empresa'] = $empresaId;
    $departamentos = $this->usuarios_departamentos_model->getPuestos(false)['puestos'];
    foreach ($departamentos as $keyd => $departamento)
    {
      $total_dep = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0,
        'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'fondo_ahorro' => 0, 'isr' => 0,
        'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0, 'pasistencia' => 0, 'despensa' => 0);

      $dep_tiene_empleados = true;
      $y = $pdf->GetY();
      foreach ($empleados as $key => $empleado)
      {
        if($departamento->id_departamento == $empleado->id_departamente)
        {
          if($dep_tiene_empleados)
          {
            $pdf->SetFont('Helvetica','B', 10);
            $pdf->SetXY(6, $pdf->GetY()+6);
            $pdf->Cell(130, 6, $departamento->nombre, 0, 0, 'L', 0);

            $pdf->SetFont('Helvetica','', 10);
            $pdf->SetXY(6, $pdf->GetY() + 8);
            $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
            $pdf->Row(array('', 'Percepción', 'Importe', '', 'Deducción', 'Importe'), false, false, null, 2, 1);

            $pdf->SetFont('Helvetica','', 10);
            $pdf->SetXY(6, $pdf->GetY() - 2);
            $pdf->Cell(200, 2, "________________________________________________________________________________________________________", 0, 0, 'L', 0);
            $dep_tiene_empleados = false;
          }

          $pdf->SetFont('Helvetica','B', 9);
          $pdf->SetXY(6, $pdf->GetY() + 4);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(15, 100));
          $pdf->Row(array($empleado->no_empleado, $empleado->nombre), false, false, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetFont('Helvetica','', 9);
          $pdf->SetXY(6, $pdf->GetY() + 0);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(50, 70, 50));
          $pdf->Row(array($empleado->puesto, "RFC: {$empleado->rfc}", "Afiliciación IMSS: {$empleado->no_seguro}"), false, false, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetXY(6, $pdf->GetY() + 0);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(50, 35, 35, 35, 30));
          $pdf->Row(array("Fecha Ingr: {$empleado->fecha_entrada}", "Sal. diario: {$empleado->salario_diario}", "S.D.I: {$empleado->nomina->salario_diario_integrado}", "S.B.C: {$empleado->nomina->salario_diario_integrado}", 'Cotiza fijo'), false, false, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $horasExtras = 0;
          if ($empleado->horas_extras_dinero > 0)
          {
            $pagoXHora = $empleado->salario_diario / 8;
            $horasExtras = $empleado->horas_extras_dinero / $pagoXHora;
          }

          $pdf->SetXY(6, $pdf->GetY() + 0);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(35, 35, 25, 35, 70));
          $pdf->Row(array("Dias Pagados: {$empleado->dias_trabajados}", "Tot Hrs trab: " . $empleado->dias_trabajados * 8, 'Hrs dia: 8.00', "Hrs extras: " . number_format($horasExtras, 2), "CURP: {$empleado->curp}"), false, false, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $y2 = $pdf->GetY();

          // Percepciones
          $percepciones = $empleado->nomina->percepciones;

          // Sueldo
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Sueldo', MyString::formatoNumero($percepciones['sueldo']['total'], 2, '$', false)), false, 0, null, 1, 1);
          $total_dep['sueldo'] += $percepciones['sueldo']['total'];
          $total_gral['sueldo'] += $percepciones['sueldo']['total'];
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }

          // P ASISTENCIA
          if ($empleado->pasistencia > 0)
          {
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'P Asistencia', MyString::formatoNumero($empleado->pasistencia, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['pasistencia'] += $empleado->pasistencia;
            $total_gral['pasistencia'] += $empleado->pasistencia;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y2 = $pdf->GetY();
            }
          }

          // DESPENSA
          if ($empleado->despensa > 0)
          {
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Despensa', MyString::formatoNumero($empleado->despensa, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['despensa'] += $empleado->despensa;
            $total_gral['despensa'] += $empleado->despensa;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y2 = $pdf->GetY();
            }
          }

          // Horas Extras
          if ($empleado->horas_extras_dinero > 0)
          {
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Horas Extras', MyString::formatoNumero($empleado->horas_extras_dinero, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['horas_extras'] += $empleado->horas_extras_dinero;
            $total_gral['horas_extras'] += $empleado->horas_extras_dinero;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y2 = $pdf->GetY();
            }
          }

          // Vacaciones y prima vacacional
          if ($empleado->nomina_fiscal_vacaciones > 0)
          {
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Vacaciones', MyString::formatoNumero($empleado->nomina_fiscal_vacaciones, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['vacaciones'] += $empleado->nomina_fiscal_vacaciones;
            $total_gral['vacaciones'] += $empleado->nomina_fiscal_vacaciones;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y2 = $pdf->GetY();
            }

            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Prima vacacional', MyString::formatoNumero($empleado->nomina->prima_vacacional, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['prima_vacacional'] += $empleado->nomina->prima_vacacional;
            $total_gral['prima_vacacional'] += $empleado->nomina->prima_vacacional;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y2 = $pdf->GetY();
            }
          }

          // // PTU
          // if ($empleado->nomina_fiscal_ptu > 0)
          // {
          //   $pdf->SetXY(6, $pdf->GetY());
          //   $pdf->SetAligns(array('L', 'L', 'R'));
          //   $pdf->SetWidths(array(15, 62, 25));
          //   $pdf->Row(array('', 'PTU', MyString::formatoNumero($empleado->nomina_fiscal_ptu, 2, '$', false)), false, 0, null, 1, 1);
          //   $total_dep['ptu'] += $empleado->nomina_fiscal_ptu;
          //   $total_gral['ptu'] += $empleado->nomina_fiscal_ptu;
          //   if($pdf->GetY() >= $pdf->limiteY)
          //   {
          //     $pdf->AddPage();
          //     $y2 = $pdf->GetY();
          //   }
          // }

          // Aguinaldo
          if ($empleado->nomina_fiscal_aguinaldo > 0)
          {
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Aguinaldo', MyString::formatoNumero($empleado->nomina_fiscal_aguinaldo, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['aguinaldo'] += $empleado->nomina_fiscal_aguinaldo;
            $total_gral['aguinaldo'] += $empleado->nomina_fiscal_aguinaldo;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y2 = $pdf->GetY();
            }
          }

          $y = $pdf->GetY();

          // Deducciones
          $deducciones = $empleado->nomina->deducciones;
          $pdf->SetFont('Helvetica','', 9);

          $pdf->SetY($y2);

          // Subsidio
          if ($empleado->nomina_fiscal_subsidio > 0)
          {
            $pdf->SetXY(108, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Subsidio', MyString::formatoNumero(-1*$empleado->nomina_fiscal_subsidio, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['subsidio'] += $empleado->nomina_fiscal_subsidio;
            $total_gral['subsidio'] += $empleado->nomina_fiscal_subsidio;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y2 = $pdf->GetY();
            }
          }

          if ($empleado->infonavit > 0)
          {
            $pdf->SetXY(108, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Infonavit', MyString::formatoNumero($deducciones['infonavit']['total'], 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['infonavit'] += $deducciones['infonavit']['total'];
            $total_gral['infonavit'] += $deducciones['infonavit']['total'];
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y = $pdf->GetY();
            }
          }

          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'I.M.S.S.', MyString::formatoNumero($deducciones['imss']['total'] + $deducciones['rcv']['total'], 2, '$', false)), false, 0, null, 1, 1);
          $total_dep['imms'] += $deducciones['imss']['total'] + $deducciones['rcv']['total'];
          $total_gral['imms'] += $deducciones['imss']['total'] + $deducciones['rcv']['total'];
          if($pdf->GetY() >= $pdf->limiteY)
          {
              $pdf->AddPage();
              $y = $pdf->GetY();
            }

          if ($empleado->nomina_fiscal_prestamos > 0)
          {
            $pdf->SetXY(108, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Prestamos', MyString::formatoNumero($empleado->nomina_fiscal_prestamos, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['prestamos'] += $empleado->nomina_fiscal_prestamos;
            $total_gral['prestamos'] += $empleado->nomina_fiscal_prestamos;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y = $pdf->GetY();
            }
          }

          if ($empleado->fondo_ahorro > 0)
          {
            $pdf->SetXY(108, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Caja Ahorro', MyString::formatoNumero($empleado->fondo_ahorro, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['fondo_ahorro'] += $empleado->fondo_ahorro;
            $total_gral['fondo_ahorro'] += $empleado->fondo_ahorro;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y = $pdf->GetY();
            }
          }

          // if ($empleado->descuento_playeras > 0)
          // {
          //   $pdf->SetXY(108, $pdf->GetY());
          //   $pdf->SetAligns(array('L', 'L', 'R'));
          //   $pdf->SetWidths(array(15, 62, 25));
          //   $pdf->Row(array('', 'Desc. Playeras', MyString::formatoNumero($empleado->descuento_playeras, 2, '$', false)), false, 0, null, 1, 1);
          //   if($pdf->GetY() >= $pdf->limiteY)
          //   {
          //     $pdf->AddPage();
          //     $y = $pdf->GetY();
          //   }
          // }

          if ($empleado->nomina_fiscal_isr > 0)
          {
            $pdf->SetXY(108, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'ISR', MyString::formatoNumero($empleado->nomina_fiscal_isr, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['isr'] += $empleado->nomina_fiscal_isr;
            $total_gral['isr'] += $empleado->nomina_fiscal_isr;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y = $pdf->GetY();
            }
          }

          if ($y < $pdf->GetY())
          {
            $y = $pdf->GetY();
          }

          // Total percepciones y deducciones
          $pdf->SetXY(6, $y + 2);
          $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));

          $empleado->nomina_fiscal_total_percepciones -= $empleado->nomina_fiscal_subsidio;
          $empleado->nomina_fiscal_total_deducciones -= $empleado->nomina_fiscal_subsidio;

          $total_dep['total_percepcion'] += $empleado->nomina_fiscal_total_percepciones;
          $total_gral['total_percepcion'] += $empleado->nomina_fiscal_total_percepciones;
          $total_dep['total_deduccion'] += $empleado->nomina_fiscal_total_deducciones;
          $total_gral['total_deduccion'] += $empleado->nomina_fiscal_total_deducciones;
          $pdf->Row(array('', 'Total Percepciones', MyString::formatoNumero($empleado->nomina_fiscal_total_percepciones, 2, '$', false), '', 'Total Deducciones', MyString::formatoNumero($empleado->nomina_fiscal_total_deducciones, 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
              $pdf->AddPage();

          $pdf->SetFont('Helvetica','B', 9);
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $total_dep['total_neto'] += $empleado->nomina_fiscal_total_neto;
          $total_gral['total_neto'] += $empleado->nomina_fiscal_total_neto;
          $pdf->Row(array('', 'Total Neto', MyString::formatoNumero($empleado->nomina_fiscal_total_neto, 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
              $pdf->AddPage();

          $pdf->SetFont('Helvetica', '', 9);
          $pdf->SetXY(120, $pdf->GetY()+3);
          $pdf->Cell(200, 2, "--------------------------------------------------------------------------------------", 0, 0, 'L', 0);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $numero_trabajadores++;
          unset($empleados_sin_departamento[intval($empleado->id)]);
        }
      }

      //****** Total departamento ******
      if($dep_tiene_empleados == false)
      {
        if($pdf->GetY()+10 >= $pdf->limiteY)
          $pdf->AddPage();
        $pdf->SetFont('Helvetica','B', 10);
        $pdf->SetXY(6, $pdf->GetY()+2);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(200));
        $pdf->Row(array("Total Departamento {$departamento->nombre}"), false, 0, null, 1, 1);
        $pdf->Row(array("____________________________________________________________________________________________________"), false, 0, null, 1, 1);

        $pdf->SetFont('Helvetica','', 9);
        $y2 = $pdf->GetY();
        // Sueldo
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Sueldo', MyString::formatoNumero($total_dep['sueldo'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        // P Asistencia
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'P Asistencia', MyString::formatoNumero($total_dep['pasistencia'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        // Despensa
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Despensa', MyString::formatoNumero($total_dep['despensa'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        // Horas Extras
        if ($total_dep['horas_extras'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Horas Extras', MyString::formatoNumero($total_dep['horas_extras'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        // Vacaciones y prima vacacional
        if ($total_dep['vacaciones'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Vacaciones', MyString::formatoNumero($total_dep['vacaciones'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }

          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Prima vacacional', MyString::formatoNumero($total_dep['prima_vacacional'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        // // Subsidio
        // if ($total_dep['subsidio'] > 0)
        // {
        //   $pdf->SetXY(6, $pdf->GetY());
        //   $pdf->SetAligns(array('L', 'L', 'R'));
        //   $pdf->SetWidths(array(15, 62, 25));
        //   $pdf->Row(array('', 'Subsidio', MyString::formatoNumero($total_dep['subsidio'], 2, '$', false)), false, 0, null, 1, 1);
        //   if($pdf->GetY() >= $pdf->limiteY)
        //   {
        //     $pdf->AddPage();
        //     $y2 = $pdf->GetY();
        //   }
        // }

        // PTU
        if ($total_dep['ptu'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'PTU', MyString::formatoNumero($total_dep['ptu'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        // Aguinaldo
        if ($total_dep['aguinaldo'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Aguinaldo', MyString::formatoNumero($total_dep['aguinaldo'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        $y = $pdf->GetY();

        // Deducciones
        $deducciones = $empleado->nomina->deducciones;
        $pdf->SetFont('Helvetica','', 9);

        $pdf->SetY($y2);
        // Subsidio
        if ($total_dep['subsidio'] > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Subsidio', MyString::formatoNumero($total_dep['subsidio']*-1, 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        if ($total_dep['infonavit'] > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Infonavit', MyString::formatoNumero($total_dep['infonavit'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'I.M.M.S.', MyString::formatoNumero($total_dep['imms'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }

        if ($total_dep['prestamos'] > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Prestamos', MyString::formatoNumero($total_dep['prestamos'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        if ($total_dep['fondo_ahorro'] > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Caja Ahorro', MyString::formatoNumero($total_dep['fondo_ahorro'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        if ($total_dep['isr'] > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'ISR', MyString::formatoNumero($total_dep['isr'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        if ($y < $pdf->GetY())
        {
          $y = $pdf->GetY();
        }

        // Total percepciones y deducciones
        $pdf->SetXY(6, $y + 2);
        $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
        $pdf->Row(array('', 'Total Percepciones', MyString::formatoNumero($total_dep['total_percepcion'], 2, '$', false), '', 'Total Deducciones', MyString::formatoNumero($total_dep['total_deduccion'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

        $pdf->SetFont('Helvetica','B', 9);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Total Neto', MyString::formatoNumero($total_dep['total_neto'], 2, '$', false)), false, 0, null, 1, 1);
      }

      $pdf->SetFont('Helvetica','', 10);
    }

    // $_GET['did_empresa'] = $empresaId;
    if (count($empleados_sin_departamento) > 0)
    {
      $total_dep = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0,
        'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'fondo_ahorro' => 0, 'isr' => 0,
        'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0, 'pasistencia' => 0, 'despensa' => 0);

      $dep_tiene_empleados = true;
      $y = $pdf->GetY();
      foreach ($empleados_sin_departamento as $key => $empleado)
      {
        if($dep_tiene_empleados)
        {
          $pdf->SetFont('Helvetica','B', 10);
          $pdf->SetXY(6, $pdf->GetY()+6);
          $pdf->Cell(130, 6, 'Sin departamento', 0, 0, 'L', 0);

          $pdf->SetFont('Helvetica','', 10);
          $pdf->SetXY(6, $pdf->GetY() + 8);
          $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
          $pdf->Row(array('', 'Percepción', 'Importe', '', 'Deducción', 'Importe'), false, false, null, 2, 1);

          $pdf->SetFont('Helvetica','', 10);
          $pdf->SetXY(6, $pdf->GetY() - 2);
          $pdf->Cell(200, 2, "________________________________________________________________________________________________________", 0, 0, 'L', 0);
          $dep_tiene_empleados = false;
        }

        $pdf->SetFont('Helvetica','B', 9);
        $pdf->SetXY(6, $pdf->GetY() + 4);
        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(15, 100));
        $pdf->Row(array($empleado->no_empleado, $empleado->nombre), false, false, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

        $pdf->SetFont('Helvetica','', 9);
        $pdf->SetXY(6, $pdf->GetY() + 0);
        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(50, 70, 50));
        $pdf->Row(array($empleado->puesto, "RFC: {$empleado->rfc}", "Afiliciación IMSS: {$empleado->no_seguro}"), false, false, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

        $pdf->SetXY(6, $pdf->GetY() + 0);
        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(50, 35, 35, 35, 30));
        $pdf->Row(array("Fecha Ingr: {$empleado->fecha_entrada}", "Sal. diario: {$empleado->salario_diario}", "S.D.I: {$empleado->nomina->salario_diario_integrado}", "S.B.C: {$empleado->nomina->salario_diario_integrado}", 'Cotiza fijo'), false, false, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

        $horasExtras = 0;
        if ($empleado->horas_extras_dinero > 0)
        {
          $pagoXHora = $empleado->salario_diario / 8;
          $horasExtras = $empleado->horas_extras_dinero / $pagoXHora;
        }

        $pdf->SetXY(6, $pdf->GetY() + 0);
        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(35, 35, 25, 35, 70));
        $pdf->Row(array("Dias Pagados: {$empleado->dias_trabajados}", "Tot Hrs trab: " . $empleado->dias_trabajados * 8, 'Hrs dia: 8.00', "Hrs extras: " . number_format($horasExtras, 2), "CURP: {$empleado->curp}"), false, false, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

        $y2 = $pdf->GetY();

        // Percepciones
        $percepciones = $empleado->nomina->percepciones;

        // Sueldo
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Sueldo', MyString::formatoNumero($percepciones['sueldo']['total'], 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['sueldo'] += $percepciones['sueldo']['total'];
        $total_gral['sueldo'] += $percepciones['sueldo']['total'];
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        // P ASISTENCIA
        if ($empleado->pasistencia > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'P Asistencia', MyString::formatoNumero($empleado->pasistencia, 2, '$', false)), false, 0, null, 1, 1);
          $total_dep['pasistencia'] += $empleado->pasistencia;
          $total_gral['pasistencia'] += $empleado->pasistencia;
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        // DESPENSA
        if ($empleado->despensa > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Despensa', MyString::formatoNumero($empleado->despensa, 2, '$', false)), false, 0, null, 1, 1);
          $total_dep['despensa'] += $empleado->despensa;
          $total_gral['despensa'] += $empleado->despensa;
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        // Horas Extras
        if ($empleado->horas_extras_dinero > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Horas Extras', MyString::formatoNumero($empleado->horas_extras_dinero, 2, '$', false)), false, 0, null, 1, 1);
          $total_dep['horas_extras'] += $empleado->horas_extras_dinero;
          $total_gral['horas_extras'] += $empleado->horas_extras_dinero;
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        // Vacaciones y prima vacacional
        if ($empleado->nomina_fiscal_vacaciones > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Vacaciones', MyString::formatoNumero($empleado->nomina_fiscal_vacaciones, 2, '$', false)), false, 0, null, 1, 1);
          $total_dep['vacaciones'] += $empleado->nomina_fiscal_vacaciones;
          $total_gral['vacaciones'] += $empleado->nomina_fiscal_vacaciones;
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }

          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Prima vacacional', MyString::formatoNumero($empleado->nomina->prima_vacacional, 2, '$', false)), false, 0, null, 1, 1);
          $total_dep['prima_vacacional'] += $empleado->nomina->prima_vacacional;
          $total_gral['prima_vacacional'] += $empleado->nomina->prima_vacacional;
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        // // PTU
        // if ($empleado->nomina_fiscal_ptu > 0)
        // {
        //   $pdf->SetXY(6, $pdf->GetY());
        //   $pdf->SetAligns(array('L', 'L', 'R'));
        //   $pdf->SetWidths(array(15, 62, 25));
        //   $pdf->Row(array('', 'PTU', MyString::formatoNumero($empleado->nomina_fiscal_ptu, 2, '$', false)), false, 0, null, 1, 1);
        //   $total_dep['ptu'] += $empleado->nomina_fiscal_ptu;
        //   $total_gral['ptu'] += $empleado->nomina_fiscal_ptu;
        //   if($pdf->GetY() >= $pdf->limiteY)
        //   {
        //     $pdf->AddPage();
        //     $y2 = $pdf->GetY();
        //   }
        // }

        // Aguinaldo
        if ($empleado->nomina_fiscal_aguinaldo > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Aguinaldo', MyString::formatoNumero($empleado->nomina_fiscal_aguinaldo, 2, '$', false)), false, 0, null, 1, 1);
          $total_dep['aguinaldo'] += $empleado->nomina_fiscal_aguinaldo;
          $total_gral['aguinaldo'] += $empleado->nomina_fiscal_aguinaldo;
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        $y = $pdf->GetY();

        // Deducciones
        $deducciones = $empleado->nomina->deducciones;
        $pdf->SetFont('Helvetica','', 9);

        $pdf->SetY($y2);

        // Subsidio
        if ($empleado->nomina_fiscal_subsidio > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Subsidio', MyString::formatoNumero(-1*$empleado->nomina_fiscal_subsidio, 2, '$', false)), false, 0, null, 1, 1);
          $total_dep['subsidio'] += $empleado->nomina_fiscal_subsidio;
          $total_gral['subsidio'] += $empleado->nomina_fiscal_subsidio;
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        if ($empleado->infonavit > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Infonavit', MyString::formatoNumero($deducciones['infonavit']['total'], 2, '$', false)), false, 0, null, 1, 1);
          $total_dep['infonavit'] += $deducciones['infonavit']['total'];
          $total_gral['infonavit'] += $deducciones['infonavit']['total'];
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'I.M.S.S.', MyString::formatoNumero($deducciones['imss']['total'] + $deducciones['rcv']['total'], 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['imms'] += $deducciones['imss']['total'] + $deducciones['rcv']['total'];
        $total_gral['imms'] += $deducciones['imss']['total'] + $deducciones['rcv']['total'];
        if($pdf->GetY() >= $pdf->limiteY)
        {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }

        if ($empleado->nomina_fiscal_prestamos > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Prestamos', MyString::formatoNumero($empleado->nomina_fiscal_prestamos, 2, '$', false)), false, 0, null, 1, 1);
          $total_dep['prestamos'] += $empleado->nomina_fiscal_prestamos;
          $total_gral['prestamos'] += $empleado->nomina_fiscal_prestamos;
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        if ($empleado->fondo_ahorro > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Caja Ahorro', MyString::formatoNumero($empleado->fondo_ahorro, 2, '$', false)), false, 0, null, 1, 1);
          $total_dep['fondo_ahorro'] += $empleado->fondo_ahorro;
          $total_gral['fondo_ahorro'] += $empleado->fondo_ahorro;
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        // if ($empleado->descuento_playeras > 0)
        // {
        //   $pdf->SetXY(108, $pdf->GetY());
        //   $pdf->SetAligns(array('L', 'L', 'R'));
        //   $pdf->SetWidths(array(15, 62, 25));
        //   $pdf->Row(array('', 'Desc. Playeras', MyString::formatoNumero($empleado->descuento_playeras, 2, '$', false)), false, 0, null, 1, 1);
        //   if($pdf->GetY() >= $pdf->limiteY)
        //   {
        //     $pdf->AddPage();
        //     $y = $pdf->GetY();
        //   }
        // }

        if ($empleado->nomina_fiscal_isr > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'ISR', MyString::formatoNumero($empleado->nomina_fiscal_isr, 2, '$', false)), false, 0, null, 1, 1);
          $total_dep['isr'] += $empleado->nomina_fiscal_isr;
          $total_gral['isr'] += $empleado->nomina_fiscal_isr;
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        if ($y < $pdf->GetY())
        {
          $y = $pdf->GetY();
        }

        // Total percepciones y deducciones
        $pdf->SetXY(6, $y + 2);
        $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));

        $empleado->nomina_fiscal_total_percepciones -= $empleado->nomina_fiscal_subsidio;
        $empleado->nomina_fiscal_total_deducciones -= $empleado->nomina_fiscal_subsidio;

        $total_dep['total_percepcion'] += $empleado->nomina_fiscal_total_percepciones;
        $total_gral['total_percepcion'] += $empleado->nomina_fiscal_total_percepciones;
        $total_dep['total_deduccion'] += $empleado->nomina_fiscal_total_deducciones;
        $total_gral['total_deduccion'] += $empleado->nomina_fiscal_total_deducciones;
        $pdf->Row(array('', 'Total Percepciones', MyString::formatoNumero($empleado->nomina_fiscal_total_percepciones, 2, '$', false), '', 'Total Deducciones', MyString::formatoNumero($empleado->nomina_fiscal_total_deducciones, 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

        $pdf->SetFont('Helvetica','B', 9);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $total_dep['total_neto'] += $empleado->nomina_fiscal_total_neto;
        $total_gral['total_neto'] += $empleado->nomina_fiscal_total_neto;
        $pdf->Row(array('', 'Total Neto', MyString::formatoNumero($empleado->nomina_fiscal_total_neto, 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

        $numero_trabajadores++;

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetXY(120, $pdf->GetY()+3);
        $pdf->Cell(200, 2, "--------------------------------------------------------------------------------------", 0, 0, 'L', 0);
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();
      }

      //****** Total departamento ******
      if($dep_tiene_empleados == false)
      {
        if($pdf->GetY()+10 >= $pdf->limiteY)
          $pdf->AddPage();
        $pdf->SetFont('Helvetica','B', 10);
        $pdf->SetXY(6, $pdf->GetY()+2);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(200));
        $pdf->Row(array("Total Sin Departamento"), false, 0, null, 1, 1);
        $pdf->Row(array("____________________________________________________________________________________________________"), false, 0, null, 1, 1);

        $pdf->SetFont('Helvetica','', 9);
        $y2 = $pdf->GetY();
        // Sueldo
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Sueldo', MyString::formatoNumero($total_dep['sueldo'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        // P Asistencia
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'P Asistencia', MyString::formatoNumero($total_dep['pasistencia'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        // Despensa
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Despensa', MyString::formatoNumero($total_dep['despensa'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        // Horas Extras
        if ($total_dep['horas_extras'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Horas Extras', MyString::formatoNumero($total_dep['horas_extras'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        // Vacaciones y prima vacacional
        if ($total_dep['vacaciones'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Vacaciones', MyString::formatoNumero($total_dep['vacaciones'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }

          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Prima vacacional', MyString::formatoNumero($total_dep['prima_vacacional'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        // // Subsidio
        // if ($total_dep['subsidio'] > 0)
        // {
        //   $pdf->SetXY(6, $pdf->GetY());
        //   $pdf->SetAligns(array('L', 'L', 'R'));
        //   $pdf->SetWidths(array(15, 62, 25));
        //   $pdf->Row(array('', 'Subsidio', MyString::formatoNumero($total_dep['subsidio'], 2, '$', false)), false, 0, null, 1, 1);
        //   if($pdf->GetY() >= $pdf->limiteY)
        //   {
        //     $pdf->AddPage();
        //     $y2 = $pdf->GetY();
        //   }
        // }

        // PTU
        if ($total_dep['ptu'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'PTU', MyString::formatoNumero($total_dep['ptu'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        // Aguinaldo
        if ($total_dep['aguinaldo'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Aguinaldo', MyString::formatoNumero($total_dep['aguinaldo'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        $y = $pdf->GetY();

        // Deducciones
        $deducciones = $empleado->nomina->deducciones;
        $pdf->SetFont('Helvetica','', 9);

        $pdf->SetY($y2);
        // Subsidio
        if ($total_dep['subsidio'] > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Subsidio', MyString::formatoNumero($total_dep['subsidio']*-1, 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        if ($total_dep['infonavit'] > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Infonavit', MyString::formatoNumero($total_dep['infonavit'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'I.M.M.S.', MyString::formatoNumero($total_dep['imms'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }

        if ($total_dep['prestamos'] > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Prestamos', MyString::formatoNumero($total_dep['prestamos'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        if ($total_dep['fondo_ahorro'] > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Caja Ahorro', MyString::formatoNumero($total_dep['fondo_ahorro'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        if ($total_dep['isr'] > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'ISR', MyString::formatoNumero($total_dep['isr'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        if ($y < $pdf->GetY())
        {
          $y = $pdf->GetY();
        }

        // Total percepciones y deducciones
        $pdf->SetXY(6, $y + 2);
        $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
        $pdf->Row(array('', 'Total Percepciones', MyString::formatoNumero($total_dep['total_percepcion'], 2, '$', false), '', 'Total Deducciones', MyString::formatoNumero($total_dep['total_deduccion'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

        $pdf->SetFont('Helvetica','B', 9);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Total Neto', MyString::formatoNumero($total_dep['total_neto'], 2, '$', false)), false, 0, null, 1, 1);
      }

      $pdf->SetFont('Helvetica','', 10);
    }


    //finiquito
    $total_dep = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0,
        'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'isr' => 0,
        'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0);
    $dep_tiene_empleados = true;
    $y = $pdf->GetY();
    foreach ($finiquitos as $key => $empleado)
    {
      if($dep_tiene_empleados)
      {
        $pdf->SetFont('Helvetica','B', 10);
        $pdf->SetXY(6, $pdf->GetY()+6);
        $pdf->Cell(130, 6, 'Finiquitos', 0, 0, 'L', 0);

        $pdf->SetFont('Helvetica','', 10);
        $pdf->SetXY(6, $pdf->GetY() + 8);
        $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
        $pdf->Row(array('', 'Percepción', 'Importe', '', 'Deducción', 'Importe'), false, false, null, 2, 1);

        $pdf->SetFont('Helvetica','', 10);
        $pdf->SetXY(6, $pdf->GetY() - 2);
        $pdf->Cell(200, 2, "________________________________________________________________________________________________________", 0, 0, 'L', 0);
        $dep_tiene_empleados = false;
      }

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY() + 4);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(15, 100));
      $pdf->Row(array($empleado->id, $empleado->apellido_paterno.' '.$empleado->apellido_materno.' '.$empleado->nombre), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetFont('Helvetica','', 9);
      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(50, 70, 50));
      $pdf->Row(array('Sin Puesto', "RFC: {$empleado->rfc}", "Afiliciación IMSS: {$empleado->no_seguro}"), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(50, 35, 35, 35, 30));
      $pdf->Row(array("Fecha Ingr: {$empleado->fecha_entrada}", "Sal. diario: {$empleado->salario_diario}", "S.D.I: 0", "S.B.C: 0", 'Cotiza fijo'), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $horasExtras = 0;

      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(35, 35, 25, 35, 70));
      $pdf->Row(array("Dias Pagados: {$empleado->dias_trabajados}", "Tot Hrs trab: " . $empleado->dias_trabajados * 8, 'Hrs dia: 8.00', "Hrs extras: " . number_format('0', 2), "CURP: {$empleado->curp}"), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $y2 = $pdf->GetY();

      // Percepciones
      // $percepciones = $empleado->nomina->percepciones;

      // Sueldo
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Sueldo', MyString::formatoNumero($empleado->sueldo_semanal, 2, '$', false)), false, 0, null, 1, 1);
      $total_dep['sueldo'] += $empleado->sueldo_semanal;
      $total_gral['sueldo'] += $empleado->sueldo_semanal;
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }

      // // Horas Extras
      // if ($empleado->horas_extras_dinero > 0)
      // {
      //   $pdf->SetXY(6, $pdf->GetY());
      //   $pdf->SetAligns(array('L', 'L', 'R'));
      //   $pdf->SetWidths(array(15, 62, 25));
      //   $pdf->Row(array('', 'Horas Extras', MyString::formatoNumero($empleado->horas_extras_dinero, 2, '$', false)), false, 0, null, 1, 1);
      //   $total_dep['horas_extras'] += $empleado->horas_extras_dinero;
      //   $total_gral['horas_extras'] += $empleado->horas_extras_dinero;
      //   if($pdf->GetY() >= $pdf->limiteY)
      //   {
      //     $pdf->AddPage();
      //     $y2 = $pdf->GetY();
      //   }
      // }

      // Vacaciones y prima vacacional
      if ($empleado->vacaciones > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Vacaciones', MyString::formatoNumero($empleado->vacaciones, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['vacaciones'] += $empleado->vacaciones;
        $total_gral['vacaciones'] += $empleado->vacaciones;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Prima vacacional', MyString::formatoNumero($empleado->prima_vacacional, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['prima_vacacional'] += $empleado->prima_vacacional;
        $total_gral['prima_vacacional'] += $empleado->prima_vacacional;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // // PTU
      // if ($empleado->nomina_fiscal_ptu > 0)
      // {
      //   $pdf->SetXY(6, $pdf->GetY());
      //   $pdf->SetAligns(array('L', 'L', 'R'));
      //   $pdf->SetWidths(array(15, 62, 25));
      //   $pdf->Row(array('', 'PTU', MyString::formatoNumero($empleado->nomina_fiscal_ptu, 2, '$', false)), false, 0, null, 1, 1);
      //   $total_dep['ptu'] += $empleado->nomina_fiscal_ptu;
      //   $total_gral['ptu'] += $empleado->nomina_fiscal_ptu;
      //   if($pdf->GetY() >= $pdf->limiteY)
      //   {
      //     $pdf->AddPage();
      //     $y2 = $pdf->GetY();
      //   }
      // }

      // Aguinaldo
      if ($empleado->aguinaldo > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Aguinaldo', MyString::formatoNumero($empleado->aguinaldo, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['aguinaldo'] += $empleado->aguinaldo;
        $total_gral['aguinaldo'] += $empleado->aguinaldo;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      $y = $pdf->GetY();

      // Deducciones
      // $deducciones = $empleado->nomina->deducciones;
      $pdf->SetFont('Helvetica','', 9);

      $pdf->SetY($y2);
      // Subsidio
      if ($empleado->subsidio > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Subsidio', MyString::formatoNumero($empleado->subsidio*-1, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['subsidio'] += $empleado->subsidio;
        $total_gral['subsidio'] += $empleado->subsidio;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      if ($empleado->isr != 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'ISR', MyString::formatoNumero($empleado->isr, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['isr'] += $empleado->isr;
        $total_gral['isr'] += $empleado->isr;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      if ($y < $pdf->GetY())
      {
        $y = $pdf->GetY();
      }

      // Total percepciones y deducciones
      $pdf->SetXY(6, $y + 2);
      $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));

      $empleado->total_percepcion -= $empleado->subsidio;
      $empleado->total_deduccion -= $empleado->subsidio;

      $total_dep['total_percepcion'] += $empleado->total_percepcion;
      $total_gral['total_percepcion'] += $empleado->total_percepcion;
      $total_dep['total_deduccion'] += $empleado->total_deduccion;
      $total_gral['total_deduccion'] += $empleado->total_deduccion;
      $pdf->Row(array('', 'Total Percepciones', MyString::formatoNumero($empleado->total_percepcion, 2, '$', false), '', 'Total Deducciones', MyString::formatoNumero($empleado->total_deduccion, 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $total_dep['total_neto'] += $empleado->total_neto;
      $total_gral['total_neto'] += $empleado->total_neto;
      $pdf->Row(array('', 'Total Neto', MyString::formatoNumero($empleado->total_neto, 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica', '', 9);
      $pdf->SetXY(120, $pdf->GetY()+3);
      $pdf->Cell(200, 2, "--------------------------------------------------------------------------------------", 0, 0, 'L', 0);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $numero_trabajadores++;
    }

    //****** Total finiquito ******
    if($dep_tiene_empleados == false)
    {
      if($pdf->GetY()+10 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetFont('Helvetica','B', 10);
      $pdf->SetXY(6, $pdf->GetY()+2);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(200));
      $pdf->Row(array("Total Departamento Finiquito"), false, 0, null, 1, 1);
      $pdf->Row(array("____________________________________________________________________________________________________"), false, 0, null, 1, 1);

      $pdf->SetFont('Helvetica','', 9);
      $y2 = $pdf->GetY();
      // Sueldo
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Sueldo', MyString::formatoNumero($total_dep['sueldo'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }

      // Horas Extras
      if ($total_dep['horas_extras'] > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Horas Extras', MyString::formatoNumero($total_dep['horas_extras'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // Vacaciones y prima vacacional
      if ($total_dep['vacaciones'] > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Vacaciones', MyString::formatoNumero($total_dep['vacaciones'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Prima vacacional', MyString::formatoNumero($total_dep['prima_vacacional'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // PTU
      if ($total_dep['ptu'] > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'PTU', MyString::formatoNumero($total_dep['ptu'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // Aguinaldo
      if ($total_dep['aguinaldo'] > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Aguinaldo', MyString::formatoNumero($total_dep['aguinaldo'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      $y = $pdf->GetY();

      // Deducciones
      // $deducciones = $empleado->nomina->deducciones;
      $pdf->SetFont('Helvetica','', 9);

      $pdf->SetY($y2);

      // Subsidio
      if ($total_dep['subsidio'] > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Subsidio', MyString::formatoNumero($total_dep['subsidio']*-1, 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      if ($total_dep['infonavit'] > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Infonavit', MyString::formatoNumero($total_dep['infonavit'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'I.M.M.S.', MyString::formatoNumero($total_dep['imms'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }

      if ($total_dep['prestamos'] > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Prestamos', MyString::formatoNumero($total_dep['prestamos'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      if ($total_dep['isr'] > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'ISR', MyString::formatoNumero($total_dep['isr'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      if ($y < $pdf->GetY())
      {
        $y = $pdf->GetY();
      }

      // Total percepciones y deducciones
      $pdf->SetXY(6, $y + 2);
      $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
      $pdf->Row(array('', 'Total Percepciones', MyString::formatoNumero($total_dep['total_percepcion'], 2, '$', false), '', 'Total Deducciones', MyString::formatoNumero($total_dep['total_deduccion'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Total Neto', MyString::formatoNumero($total_dep['total_neto'], 2, '$', false)), false, 0, null, 1, 1);
    }

    //********* Total general ***************
    if($pdf->GetY()+10 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Helvetica','B', 10);
    $pdf->SetXY(6, $pdf->GetY()+2);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array("Total General"), false, 0, null, 1, 1);
    $pdf->Row(array("____________________________________________________________________________________________________"), false, 0, null, 1, 1);

    $pdf->SetFont('Helvetica','', 9);
    $y2 = $pdf->GetY();
    // Sueldo
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25));
    $pdf->Row(array('', 'Sueldo', MyString::formatoNumero($total_gral['sueldo'], 2, '$', false)), false, 0, null, 1, 1);
    if($pdf->GetY() >= $pdf->limiteY)
    {
      $pdf->AddPage();
      $y2 = $pdf->GetY();
    }

    // P Asistencia
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25));
    $pdf->Row(array('', 'P Asistencia', MyString::formatoNumero($total_gral['pasistencia'], 2, '$', false)), false, 0, null, 1, 1);
    if($pdf->GetY() >= $pdf->limiteY)
    {
      $pdf->AddPage();
      $y2 = $pdf->GetY();
    }

    // Despensa
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25));
    $pdf->Row(array('', 'Despensa', MyString::formatoNumero($total_gral['despensa'], 2, '$', false)), false, 0, null, 1, 1);
    if($pdf->GetY() >= $pdf->limiteY)
    {
      $pdf->AddPage();
      $y2 = $pdf->GetY();
    }

    // Horas Extras
    if ($total_gral['horas_extras'] > 0)
    {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Horas Extras', MyString::formatoNumero($total_gral['horas_extras'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }
    }

    // Vacaciones y prima vacacional
    if ($total_gral['vacaciones'] > 0)
    {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Vacaciones', MyString::formatoNumero($total_gral['vacaciones'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }

      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Prima vacacional', MyString::formatoNumero($total_gral['prima_vacacional'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }
    }

    // // Subsidio
    // if ($total_gral['subsidio'] > 0)
    // {
    //   $pdf->SetXY(6, $pdf->GetY());
    //   $pdf->SetAligns(array('L', 'L', 'R'));
    //   $pdf->SetWidths(array(15, 62, 25));
    //   $pdf->Row(array('', 'Subsidio', MyString::formatoNumero($total_gral['subsidio'], 2, '$', false)), false, 0, null, 1, 1);
    //   if($pdf->GetY() >= $pdf->limiteY)
    //   {
    //     $pdf->AddPage();
    //     $y2 = $pdf->GetY();
    //   }
    // }

    // PTU
    if ($total_gral['ptu'] > 0)
    {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'PTU', MyString::formatoNumero($total_gral['ptu'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }
    }

    // Aguinaldo
    if ($total_gral['aguinaldo'] > 0)
    {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Aguinaldo', MyString::formatoNumero($total_gral['aguinaldo'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }
    }

    $y = $pdf->GetY();

    // Deducciones
    // $deducciones = $empleado->nomina->deducciones;
    $pdf->SetFont('Helvetica','', 9);

    $pdf->SetY($y2);
    // Subsidio
    if ($total_gral['subsidio'] > 0)
    {
      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Subsidio', MyString::formatoNumero($total_gral['subsidio']*-1, 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }
    }

    if ($total_gral['infonavit'] > 0)
    {
      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Infonavit', MyString::formatoNumero($total_gral['infonavit'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y = $pdf->GetY();
      }
    }

    $pdf->SetXY(108, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25));
    $pdf->Row(array('', 'I.M.M.S.', MyString::formatoNumero($total_gral['imms'], 2, '$', false)), false, 0, null, 1, 1);
    if($pdf->GetY() >= $pdf->limiteY)
    {
        $pdf->AddPage();
        $y = $pdf->GetY();
      }

    if ($total_gral['prestamos'] > 0)
    {
      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Prestamos', MyString::formatoNumero($total_gral['prestamos'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y = $pdf->GetY();
      }
    }

    if ($total_gral['fondo_ahorro'] > 0)
    {
      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Caja Ahorro', MyString::formatoNumero($total_gral['fondo_ahorro'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y = $pdf->GetY();
      }
    }

    if ($total_gral['isr'] > 0)
    {
      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'ISR', MyString::formatoNumero($total_gral['isr'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y = $pdf->GetY();
      }
    }

    if ($y < $pdf->GetY())
    {
      $y = $pdf->GetY();
    }

    // Total percepciones y deducciones
    $pdf->SetXY(6, $y + 2);
    $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
    $pdf->Row(array('', 'Total Percepciones', MyString::formatoNumero($total_gral['total_percepcion'], 2, '$', false), '', 'Total Deducciones', MyString::formatoNumero($total_gral['total_deduccion'], 2, '$', false)), false, 0, null, 1, 1);
    if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

    $pdf->SetFont('Helvetica','B', 9);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25));
    $pdf->Row(array('', 'Total Neto ('.$numero_trabajadores.' - '.$numero_trabajadores2.')', MyString::formatoNumero($total_gral['total_neto'], 2, '$', false)), false, 0, null, 1, 1);

    $pdf->Output('Nomina.pdf', 'I');
  }



  /**
   * Reportes
   *******************************
   * @return void
   */
  public function getDataCombutible()
  {
    $sql = $sql2 = '';

    //Filtro de fecha.
    if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '')
      $sql .= " AND Date(csc.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffecha1') != '')
      $sql .= " AND Date(csc.fecha) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffecha2') != '')
      $sql .= " AND Date(csc.fecha) = '".$this->input->get('ffecha2')."'";

    $sql2 = $sql;

    // vehiculos
    if (isset($_GET['dareas']) && count($_GET['dareas']) > 0)
    {
      $sql .= " AND i.id_area In(".implode(',', $_GET['dareas']).")";
    }

    $response = array();

    // Totales de vehiculos
    $response = $this->db->query(
        "SELECT Sum(csc.horas_totales) AS horas_totales, Sum(csc.lts_combustible) AS lts_combustible,
          i.nombre AS implemento, i.codigo_fin AS codigo_implemento, i.id_area
        FROM compras_salidas_combustible AS csc
          INNER JOIN compras_areas AS i ON i.id_area = csc.id_implemento
        WHERE 1 = 1 {$sql}
        GROUP BY i.id_area
        ORDER BY implemento ASC")->result();

    // Si es desglosado carga independientes
    if (isset($_GET['ddesglosado']{0}) && $_GET['ddesglosado'] == '1') {
      foreach ($response as $key => $value) {
        $value->detalle = $this->db->query(
            "SELECT csc.id_combustible, csc.fecha, csc.hora_inicial, csc.hora_final, csc.horas_totales, csc.lts_combustible,
              l.id_labor, l.nombre AS labor, l.codigo, csc.id_centro_costo, cc.nombre AS centro_costo, cc.codigo_fin AS codigo_centro_costo,
              csc.id_implemento, i.nombre AS implemento, i.codigo_fin AS codigo_implemento
            FROM compras_salidas_combustible AS csc
              INNER JOIN compras_areas AS cc ON cc.id_area = csc.id_centro_costo
              INNER JOIN compras_areas AS i ON i.id_area = csc.id_implemento
              INNER JOIN compras_salidas_labores AS l ON l.id_labor = csc.id_labor
            WHERE i.id_area = {$value->id_area} {$sql2}
            ORDER BY (csc.fecha, csc.id_combustible) ASC")->result();
      }
    }

    return $response;
  }
  public function rptcombustible_pdf()
  {
    $combustible = $this->getDataCombutible();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = "Reporte de Combustible";

    $pdf->titulo3 = ''; //"{$_GET['dproducto']} \n";
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
        $pdf->titulo3 .= "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
    elseif (!empty($_GET['ffecha1']))
        $pdf->titulo3 .= "Del ".$_GET['ffecha1'];
    elseif (!empty($_GET['ffecha2']))
        $pdf->titulo3 .= "Del ".$_GET['ffecha2'];

    $pdf->AliasNbPages();
    // $links = array('', '', '', '');
    $pdf->SetY(30);
    $aligns = array('L', 'R', 'R', 'R');
    $widths = array(115, 30, 30, 30);
    $header = array('Vehiculo', 'Lts Combustible', 'Total Hrs', 'Lts/Hrs');
    $aligns2 = array('L', 'L', 'L', 'R', 'R', 'R');
    $widths2 = array(19, 48, 48, 30, 30, 30);
    $header2 = array('Fecha', 'Centro Costo', 'Labor', 'Lts Combustible', 'Total Hrs', 'Lts/Hrs');

    $lts_combustible = 0;
    $horas_totales = 0;

    $entro = false;
    foreach($combustible as $key => $vehiculo)
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
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row(array(
        $vehiculo->implemento,
        MyString::formatoNumero($vehiculo->lts_combustible, 2, '', false),
        MyString::formatoNumero($vehiculo->horas_totales, 2, '', false),
        MyString::formatoNumero(($vehiculo->lts_combustible/($vehiculo->horas_totales>0?$vehiculo->horas_totales:1)), 2, '', false),
      ), false, false);

      $lts_combustible += floatval($vehiculo->lts_combustible);
      $horas_totales   += floatval($vehiculo->horas_totales);

      if (isset($vehiculo->detalle)) {
        foreach ($vehiculo->detalle as $key2 => $item)
        {
          $band_head = false;
          if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
          {
            $pdf->AddPage();

            $pdf->SetFont('Arial','B',8);
            $pdf->SetTextColor(255,255,255);
            $pdf->SetFillColor(160,160,160);
            $pdf->SetX(6);
            $pdf->SetAligns($aligns2);
            $pdf->SetWidths($widths2);
            $pdf->Row($header2, true);
          }

          $pdf->SetFont('Arial','',8);
          $pdf->SetTextColor(0,0,0);

          $datos = array(
            $item->fecha,
            $item->centro_costo,
            $item->labor,
            MyString::formatoNumero($item->lts_combustible, 2, '', false),
            MyString::formatoNumero($item->horas_totales, 2, '', false),
            MyString::formatoNumero(($item->lts_combustible/($item->horas_totales>0?$item->horas_totales:1)), 2, '', false),
          );

          $pdf->SetX(6);
          $pdf->SetAligns($aligns2);
          $pdf->SetWidths($widths2);
          $pdf->Row($datos, false, false);
        }
      }

    }

    $pdf->SetX(6);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);

    $pdf->SetFont('Arial','B',9);
    $pdf->SetTextColor(0,0,0);
    $pdf->Row(array('TOTALES',
        MyString::formatoNumero($lts_combustible, 2, '', false),
        MyString::formatoNumero($horas_totales, 2, '', false),
        MyString::formatoNumero(($lts_combustible/($horas_totales>0?$horas_totales:1)), 2, '', false) ),
    true, false);

    $pdf->Output('reporte_combustible.pdf', 'I');
  }

  public function rptcombustible_xls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=reporte_combustible.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $combustible = $this->getDataCombutible();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "Reporte de Combustible";
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
          <td style="width:500px;border:1px solid #000;background-color: #cccccc;">Vehiculo</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Lts Combustible</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Total Hrs</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Lts/Hrs</td>
        </tr>';
    $lts_combustible = $horas_totales = 0;
    foreach ($combustible as $key => $vehiculo)
    {
      $lts_combustible += floatval($vehiculo->lts_combustible);
      $horas_totales   += floatval($vehiculo->horas_totales);

      $html .= '<tr style="font-weight:bold">
          <td style="width:500px;border:1px solid #000;background-color: #cccccc;">'.$vehiculo->implemento.'</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$vehiculo->lts_combustible.'</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$vehiculo->horas_totales.'</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.MyString::formatoNumero(($vehiculo->lts_combustible/($vehiculo->horas_totales>0?$vehiculo->horas_totales:1)), 2, '', false).'</td>
        </tr>';
      if (isset($vehiculo->detalle)) {
        foreach ($vehiculo->detalle as $key2 => $item)
        {
          $html .= '<tr>
              <td colspan="3" style="width:500px;border:1px solid #000;">
                <table>
                  <tr>
                    <td style="width:80px;border:1px solid #000;">'.$item->fecha.'</td>
                    <td style="width:210px;border:1px solid #000;">'.$item->centro_costo.'</td>
                    <td style="width:210px;border:1px solid #000;">'.$item->labor.'</td>
                  </tr>
                </table>
              </td>
              <td style="width:150px;border:1px solid #000;">'.$item->lts_combustible.'</td>
              <td style="width:150px;border:1px solid #000;">'.$item->horas_totales.'</td>
              <td style="width:150px;border:1px solid #000;">'.MyString::formatoNumero(($lts_combustible/($horas_totales>0?$horas_totales:1)), 2, '', false).'</td>
            </tr>';
        }
      }

    }

    $html .= '
        <tr style="font-weight:bold">
          <td>TOTALES</td>
          <td style="border:1px solid #000;">'.$lts_combustible.'</td>
          <td style="border:1px solid #000;">'.$horas_totales.'</td>
          <td style="border:1px solid #000;">'.MyString::formatoNumero(($lts_combustible/($horas_totales>0?$horas_totales:1)), 2, '', false).'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

}
/* End of file nomina_fiscal_model.php */
/* Location: ./application/models/nomina_fiscal_model.php */