<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class control_maquinaria_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
  }

  public function save($datos)
  {
    $data = array(
      'id_labor'        => $datos['id_labor'],
      'fecha'           => $datos['fecha'],
      'implemento'      => $datos['implemento'],
      'hora_carga'      => $datos['hora_carga'],
      'odometro'        => $datos['odometro'],
      'lts_combustible' => $datos['lts_combustible'],
      'precio'          => $datos['precio'],
      'odometro_fin'    => $datos['odometro_fin']
    );

    if (isset($datos['id_combustible']{0}))
      $this->db->update('compras_salidas_combustible', $data, array('id_combustible' => $datos['id_combustible']));
    else {
      $this->db->insert('compras_salidas_combustible', $data);
      $datos['id_combustible'] = $this->db->insert_id('compras_salidas_combustible_id_combustible_seq');
    }

    return array('passess' => true, 'id_combustible' => $datos['id_combustible'] );
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

  public function info($fecha)
  {
    $data = array(
      "combustible" => array(),
    );

    //cs.id_empresa_ap = {$empresaId}
    $sql = $this->db->query(
      "SELECT csc.id_combustible, cs.id_activo, p.nombre AS activo, csc.fecha, csc.hora_carga, cs.folio, cs.solicito AS operador,
        csc.odometro, csc.lts_combustible, csc.precio, csc.implemento, csl.id_labor, csl.nombre AS labor, cs.observaciones,
        ran.rancho, csc.odometro_fin, (Coalesce(csc.odometro_fin, 0) - Coalesce(csc.odometro)) AS horas_totales
      FROM compras_salidas cs
        INNER JOIN compras_salidas_combustible csc ON cs.id_salida = csc.id_salida
        INNER JOIN productos p On p.id_producto = cs.id_activo
        INNER JOIN compras_salidas_labores csl ON csl.id_labor = csc.id_labor
        LEFT JOIN (
          SELECT csr.id_salida, string_agg(r.nombre, ', ') AS rancho
          FROM compras_salidas_rancho csr
            INNER JOIN otros.ranchos r ON r.id_rancho = csr.id_rancho
          GROUP BY csr.id_salida
        ) ran ON ran.id_salida = cs.id_salida
      WHERE csc.fecha = '{$fecha}'
      ORDER BY id_activo ASC, labor ASC, fecha ASC, hora_carga ASC
      ");

    if ($sql->num_rows() > 0)
      $data['combustible'] = $sql->result();

    return $data;
  }

  public function ajaxImplementos()
  {
    $sql = '';
    $res = $this->db->query("
        SELECT DISTINCT ON (upper(implemento)) implemento
        FROM compras_salidas_combustible
        WHERE upper(implemento) LIKE '%".mb_strtoupper($_GET['term'], 'UTF-8')."%'
        ORDER BY upper(implemento) ASC
        LIMIT 20");

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $response[] = array(
          'id' => $itm->implemento,
          'label' => $itm->implemento,
          'value' => $itm->implemento,
          'item' => $itm,
        );
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

    $empresaId = isset($_GET['did_empresa']{0})? $_GET['did_empresa']: 2;

    // vehiculos
    if (isset($_GET['activoId']) && intval($_GET['activoId']) > 0)
    {
      $sql .= " AND cs.id_activo = {$_GET['activoId']}";
    }

    $response = array();

    // Totales de vehiculos
    $response = $this->db->query(
      "SELECT cs.id_activo, p.nombre AS activo, csc.fecha, csc.hora_carga, cs.folio, cs.solicito AS operador,
        csc.odometro, csc.lts_combustible, csc.precio, csc.implemento, csl.nombre AS labor, cs.observaciones,
        ran.rancho, csc.odometro_fin
      FROM compras_salidas cs
        INNER JOIN compras_salidas_combustible csc ON cs.id_salida = csc.id_salida
        INNER JOIN productos p On p.id_producto = cs.id_activo
        INNER JOIN compras_salidas_labores csl ON csl.id_labor = csc.id_labor
        LEFT JOIN (
          SELECT csr.id_salida, string_agg(r.nombre, ', ') AS rancho
          FROM compras_salidas_rancho csr
            INNER JOIN otros.ranchos r ON r.id_rancho = csr.id_rancho
          GROUP BY csr.id_salida
        ) ran ON ran.id_salida = cs.id_salida
      WHERE cs.id_empresa_ap = {$empresaId} {$sql}
      ORDER BY id_activo ASC, labor ASC, fecha ASC, hora_carga ASC")->result();

    return $response;
  }
  public function rptcombustible_pdf()
  {
    $combustible = $this->getDataCombutible();

    $empresaId = isset($_GET['did_empresa']{0})? $_GET['did_empresa']: 2;
    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId);

    // echo "<pre>";
    //   var_dump($combustible);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('L', 'mm', 'Letter');
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
    $widths = array(153, 40);
    $header = array('Vehiculo');
    $aligns2 = array('L', 'L', 'C', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'L', 'L');
    $widths2 = array(15, 12, 15, 35, 35, 10, 10, 11, 10, 10, 15, 15, 18, 20, 35);
    $header2 = array('Fecha', 'Hr Carga', 'Folio Salida', 'Rancho', 'Operador', 'Hor Ini', 'Hor Fin', 'Hor Total',
        'Litros', 'Precio', 'Total', 'Rendim lt/Hr', 'Acumulado', 'Implemento', 'Observaciones');

    $costoacumulado = 0;
    $auxvehi = '';

    $entro = false;
    foreach($combustible as $key => $vehiculo)
    {
      $cantidad = 0;
      $importe = 0;
      if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
      {
        if ($pdf->GetY() >= $pdf->limiteY || $key==0) {
          $pdf->AddPage();
        }
      }

      if ($auxvehi != ($vehiculo->id_activo.$vehiculo->labor)) {
        $pdf->SetFont('Arial','B',7);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row([$vehiculo->activo, $vehiculo->labor], false);

        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(180,180,180);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns2);
        $pdf->SetWidths($widths2);
        $pdf->Row($header2, true);

        $auxvehi = $vehiculo->id_activo.$vehiculo->labor;
        $costoacumulado = 0;
      }

      $hrs = ($vehiculo->odometro_fin - $vehiculo->odometro);
      $costoacumulado += $vehiculo->precio*$vehiculo->lts_combustible;

      $pdf->SetFont('Arial','',7);
      $pdf->SetTextColor(0,0,0);
      $pdf->SetX(6);
      $pdf->SetAligns($aligns2);
      $pdf->SetWidths($widths2);
      $pdf->Row(array(
        $vehiculo->fecha,
        substr($vehiculo->hora_carga, 0, 8),
        $vehiculo->folio,
        $vehiculo->rancho,
        $vehiculo->operador,
        $vehiculo->odometro,
        $vehiculo->odometro_fin,
        $hrs,
        MyString::formatoNumero($vehiculo->lts_combustible, 2, '', false),
        MyString::formatoNumero($vehiculo->precio, 2, '', false),
        MyString::formatoNumero($vehiculo->precio*$vehiculo->lts_combustible, 2, '', false),
        MyString::formatoNumero(($vehiculo->lts_combustible/($hrs>0? $hrs: 1)), 2, '', false),
        MyString::formatoNumero($costoacumulado, 2, '', false),
        $vehiculo->implemento,
        $vehiculo->observaciones,
      ), false, false);

    }

    // $pdf->SetX(6);
    // $pdf->SetAligns($aligns);
    // $pdf->SetWidths($widths);

    // $pdf->SetFont('Arial','B',9);
    // $pdf->SetTextColor(0,0,0);
    // $pdf->Row(array('TOTALES',
    //     MyString::formatoNumero($lts_combustible, 2, '', false),
    //     MyString::formatoNumero($horas_totales, 2, '', false),
    //     MyString::formatoNumero(($lts_combustible/($horas_totales>0?$horas_totales:1)), 2, '', false) ),
    // true, false);

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

/* End of file bascula_model.php */
/* Location: ./application/models/bascula_model.php */