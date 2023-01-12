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



  public function ticketNominaFiscal($semana, $empresaId, $registro_patronal, $anio=null, $diaComienza=4)
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

    $pdf->SetXY(0, 3);
    foreach ($empleados as $key => $empleado) {
      $pdf->AddPage();
      $pdf->AddFont($pdf->fount_num, '');

      $tareas = $this->db->query("SELECT t2.fecha, t2.costo, t2.avance,
          t2.importe, sl.nombre AS labor, sl.codigo
        FROM nomina_trabajos_dia2 t2
          INNER JOIN compras_salidas_labores sl ON sl.id_labor = t2.id_labor
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
      $pdf->SetAligns(array('C'));
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('ACTIVIDADES'), false, false, 5);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('--------------------------------------------------------------------------'), false, false, 5);

      foreach ($tareas as $keyt => $tarea) {
        $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_txt, $pdf->fount_num), array(-2, -2, -2));
        $pdf->SetWidths(array(13, 34, 15));
        $pdf->SetAligns(array('L', 'L', 'R'));

        $pdf->SetXY(0, $pdf->GetY()-2);
        $pdf->Row2(array($tarea->fecha, $tarea->labor, MyString::formatoNumero($tarea->importe, 2, '$', false)), false, false, 5);
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
    $pdf->Output('nomina_ticket', 'I');
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