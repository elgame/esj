<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class nomina_trabajos_model extends CI_Model {

  public function save($datos)
  {
    $this->load->model('nomina_fiscal_model');

    $data = array(
      'fecha'           => $datos['fecha'],
      'id_empresa'      => $datos['id_empresa'],
      // 'id_area'         => $datos['id_area'],
      'id_usuario'      => $datos['id_usuario'],
      'sueldo_diario'   => floatval($datos['sueldo_diario']),
      'hrs_extra'       => 0,
      'descripcion'     => $datos['descripcion'],
      'importe'         => floatval($datos['importe']),
      'horas'           => floatval($datos['horas']),
      'importe_trabajo' => floatval($datos['importe_trabajo']),
      'importe_extra'   => floatval($datos['importe_extra']),
      'tipo_asistencia' => $datos['tipo_asistencia'],
    );

    $data_labores = isset($datos['arealhr'])? $datos['arealhr']: [];
    $hrs_extra = isset($datos['hrs_extra'])? $datos['hrs_extra']: [];

    // echo "<pre>";
    //   var_dump($data_labores, $hrs_extra);
    // echo "</pre>";exit;
    // total de hrs extras
    $total_hrsext = 0;
    $insert_hrs_extras = [];
    foreach ($hrs_extra as $key => $value) {
      if ($value['fhoras'] > 0) {
        $total_hrsext += $value['fhoras'];
        $insert_hrs_extras[] = array(
            'id_usuario' => $data['id_usuario'],
            'id_empresa' => $data['id_empresa'],
            'fecha'      => $data['fecha'],
            'id_area'    => $value['id_area'],
            'horas'      => $value['fhoras'],
            'importe'    => $value['fimporte'],
            );
      }
    }
    $data['hrs_extra'] = $total_hrsext;

    // si existe el registro
    $existe = $this->db->query("SELECT Count(*) AS num FROM nomina_trabajos_dia WHERE id_usuario = {$data['id_usuario']} AND fecha = '{$data['fecha']}' AND id_empresa = {$data['id_empresa']}")->row();

    if ($data['horas'] > 5 && $data['importe_trabajo'] > 0 &&
      $data['fecha'] != '' && $data['id_empresa'] > 0 && count($data_labores) > 0 &&
      $data['tipo_asistencia'] == 'a') {

      if ($existe->num > 0)
        $this->db->update('nomina_trabajos_dia', $data, "id_usuario = {$data['id_usuario']} AND fecha = '{$data['fecha']}' AND id_empresa = {$data['id_empresa']}");
      else {
        $this->db->insert('nomina_trabajos_dia', $data);
      }

      $this->db->delete('nomina_trabajos_dia_labores', "id_usuario = {$data['id_usuario']} AND fecha = '{$data['fecha']}' AND id_empresa = {$data['id_empresa']}");
      if (count($data_labores) > 0) {
        $dias_labores = array();
        foreach ($data_labores as $key => $value) {
          foreach ($value['flabor_id'] as $lkey => $labor) {
            if (isset($labor{0}) && isset($value['fhoras'][$lkey]{0})) {
              $dias_labores[] = array(
                'id_usuario' => $data['id_usuario'],
                'id_empresa' => $data['id_empresa'],
                'fecha'      => $data['fecha'],
                'id_area'    => $value['id_area'],
                'id_labor'   => $labor,
                'horas'      => $value['fhoras'][$lkey],
                'importe'    => round(($value['fhoras'][$lkey] * $data['importe_trabajo'] / $data['horas']), 4),
                );
            }
          }
        }
        if (count($dias_labores) > 0) {
          $this->db->insert_batch('nomina_trabajos_dia_labores', $dias_labores);
        }
      }

      // registra las hrs extras
      $this->db->delete('nomina_trabajos_dia_hrsext', "id_usuario = {$data['id_usuario']} AND fecha = '{$data['fecha']}' AND id_empresa = {$data['id_empresa']}");
      if (count($insert_hrs_extras) > 0) {
        $this->db->insert_batch('nomina_trabajos_dia_hrsext', $insert_hrs_extras);
      }

      // Registra los Bonos
      if($data['importe_extra'] > 0) {
        // si esta igual o cambio el bono
        $existe_bono = $this->db->query("SELECT Count(*) AS num FROM nomina_percepciones_ext WHERE id_usuario = {$data['id_usuario']} AND fecha = '{$data['fecha']}' AND bono = {$data['importe_extra']}")->row();
        if ($existe_bono->num == 0) {
          $this->db->delete('nomina_percepciones_ext', array('id_usuario' => $data['id_usuario'], 'fecha' => $data['fecha']));
          $this->db->insert('nomina_percepciones_ext', array(
                'id_usuario' => $data['id_usuario'],
                'fecha'      => $data['fecha'],
                'bono'       => $data['importe_extra'],
                'otro'       => 0,
                'domingo'    => 0,
              ));
        }
      }

      // Quita la falta al trabajador
      $this->db->delete('nomina_asistencia', "id_usuario = {$data['id_usuario']} AND Date(fecha_ini) = '{$data['fecha']}' AND tipo = 'f'");

      return array('passess' => true);
    } else {
      if ($existe->num > 0)
        $this->db->update('nomina_trabajos_dia', $data, "id_usuario = {$data['id_usuario']} AND fecha = '{$data['fecha']}' AND id_empresa = {$data['id_empresa']}");
      else {
        $this->db->insert('nomina_trabajos_dia', $data);
      }

      $tipo = explode('-', $data['tipo_asistencia']);
      if ($tipo[0] == 'a')
        $tipo[0] = 'f';
      // Registra falta al trabajador
      $this->db->delete('nomina_asistencia', "id_usuario = {$data['id_usuario']} AND Date(fecha_ini) = '{$data['fecha']}' AND tipo = 'f'");
      $this->db->insert('nomina_asistencia', array(
            'id_usuario' => $data['id_usuario'],
            'fecha_ini'  => $data['fecha'],
            'fecha_fin'  => $data['fecha'],
            'tipo'       => $tipo[0],
            'id_clave'   => isset($tipo[1])? $tipo[1]: null,
          ));
      return array('passess' => false);
    }

  }

  /**
   * Elimina una clasificacion de la BDD.
   *
   * @return array
   */
  public function delete($datos)
  {
    $this->db->delete('compras_salidas_combustible', "id_combustible = {$datos['id_combustible']}");

    return array('passess' => true);
  }

  public function info($fecha, $id_empresa)
  {
    $data = array();

    $sql = $this->db->query(
      "SELECT ntd.id_usuario, ntd.fecha, cca.id_cat_codigos AS id_area, ntd.horas AS total_horas, ntd.hrs_extra, ntd.descripcion,
        ntd.importe, ntd.sueldo_diario, ntd.id_empresa, ntd.importe_trabajo, ntd.importe_extra,
        cca.nombre AS area, cca.codigo AS codigo_fin, e.nombre_fiscal, ntdl.id_labor, csl.nombre AS labor, ntdl.horas,
        ntd.tipo_asistencia
      FROM nomina_trabajos_dia ntd
        INNER JOIN empresas e ON e.id_empresa = ntd.id_empresa
        LEFT JOIN nomina_trabajos_dia_labores ntdl ON (ntd.id_usuario = ntdl.id_usuario AND ntd.fecha = ntdl.fecha AND ntd.id_empresa = ntdl.id_empresa)
        LEFT JOIN otros.cat_codigos AS cca ON cca.id_cat_codigos = ntdl.id_area
        LEFT JOIN compras_salidas_labores csl ON csl.id_labor = ntdl.id_labor
      WHERE ntd.fecha = '{$fecha}' AND e.id_empresa = {$id_empresa}
      ORDER BY ntd.id_usuario ASC, cca.id_cat_codigos ASC
      ");

    $response = array();
    if ($sql->num_rows() > 0) {
      // $data = $sql->result();
      $aux = '';
      $aux_area = '';
      foreach ($sql->result() as $key => $value) {
        if ($aux != $value->id_usuario) {
          $response[$value->id_usuario] = array();
          // $response[$value->id_usuario]['info'] = $value;
          $response[$value->id_usuario][$value->id_area] = $value;
          $response[$value->id_usuario][$value->id_area]->labores = array();

          $response[$value->id_usuario]['hrs_extra'] = $this->db->query(
            "SELECT ntd.id_usuario, ntd.fecha, ntd.id_empresa, cca.id_cat_codigos AS id_area, ntdl.horas, ntdl.importe,
              cca.nombre AS area, cca.codigo AS codigo_fin
            FROM nomina_trabajos_dia ntd
              LEFT JOIN nomina_trabajos_dia_hrsext ntdl ON (ntd.id_usuario = ntdl.id_usuario AND ntd.fecha = ntdl.fecha AND ntd.id_empresa = ntdl.id_empresa)
              LEFT JOIN otros.cat_codigos AS cca ON cca.id_cat_codigos = ntdl.id_area
            WHERE ntd.id_usuario = {$value->id_usuario} AND ntd.fecha = '{$fecha}' AND ntd.id_empresa = {$id_empresa}
            ORDER BY ntd.id_usuario ASC, cca.id_cat_codigos ASC
            ")->result();

          $aux = $value->id_usuario;
        }elseif ($aux_area != $value->id_area) {
          $response[$value->id_usuario][$value->id_area] = $value;
          $response[$value->id_usuario][$value->id_area]->labores = array();
        }
        $aux_area = $value->id_area;

        $response[$value->id_usuario][$value->id_area]->labores[] = array('id_labor' => $value->id_labor, 'labor' => $value->labor, 'horas' => $value->horas);
      }
    }

    return $response;
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
        String::formatoNumero($vehiculo->lts_combustible, 2, '', false),
        String::formatoNumero($vehiculo->horas_totales, 2, '', false),
        String::formatoNumero(($vehiculo->lts_combustible/($vehiculo->horas_totales>0?$vehiculo->horas_totales:1)), 2, '', false),
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
            String::formatoNumero($item->lts_combustible, 2, '', false),
            String::formatoNumero($item->horas_totales, 2, '', false),
            String::formatoNumero(($item->lts_combustible/($item->horas_totales>0?$item->horas_totales:1)), 2, '', false),
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
        String::formatoNumero($lts_combustible, 2, '', false),
        String::formatoNumero($horas_totales, 2, '', false),
        String::formatoNumero(($lts_combustible/($horas_totales>0?$horas_totales:1)), 2, '', false) ),
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
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.String::formatoNumero(($vehiculo->lts_combustible/($vehiculo->horas_totales>0?$vehiculo->horas_totales:1)), 2, '', false).'</td>
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
              <td style="width:150px;border:1px solid #000;">'.String::formatoNumero(($lts_combustible/($horas_totales>0?$horas_totales:1)), 2, '', false).'</td>
            </tr>';
        }
      }

    }

    $html .= '
        <tr style="font-weight:bold">
          <td>TOTALES</td>
          <td style="border:1px solid #000;">'.$lts_combustible.'</td>
          <td style="border:1px solid #000;">'.$horas_totales.'</td>
          <td style="border:1px solid #000;">'.String::formatoNumero(($lts_combustible/($horas_totales>0?$horas_totales:1)), 2, '', false).'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

}
/* End of file nomina_fiscal_model.php */
/* Location: ./application/models/nomina_fiscal_model.php */