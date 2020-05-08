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
      'lts_combustible' => $datos['lts_combustible'],
      'precio'          => $datos['precio'],
      'horometro'       => $datos['horometro'],
      'horometro_fin'   => $datos['horometro_fin'],
      'odometro'        => $datos['odometro'],
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
        csc.lts_combustible, csc.precio, csc.implemento, csl.id_labor, csl.nombre AS labor, cs.observaciones,
        ran.rancho, (Coalesce(csc.horometro_fin, 0) - Coalesce(csc.horometro)) AS horas_totales,
        csc.horometro, csc.horometro_fin, csc.odometro, csc.odometro_fin
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

    if (isset($_GET['did_empresa']{0})) {
      $sql .= " AND cs.id_empresa_ap = {$_GET['did_empresa']}";
    }

    // vehiculos
    if (isset($_GET['activoId']) && intval($_GET['activoId']) > 0)
    {
      $sql .= " AND cs.id_activo = {$_GET['activoId']}";
    }

    $response = array();

    // Totales de vehiculos
    $response = $this->db->query(
      "SELECT cs.id_activo, e.nombre_fiscal, e.rfc, p.nombre AS activo, csc.fecha, csc.hora_carga, cs.folio, cs.solicito AS operador,
        csc.lts_combustible, csc.precio, csc.implemento, csl.nombre AS labor, cs.observaciones,
        ran.rancho, csc.odometro, csc.odometro_fin, csc.horometro, csc.horometro_fin
      FROM compras_salidas cs
        INNER JOIN compras_salidas_combustible csc ON cs.id_salida = csc.id_salida
        INNER JOIN productos p On p.id_producto = cs.id_activo
        INNER JOIN compras_salidas_labores csl ON csl.id_labor = csc.id_labor
        INNER JOIN empresas e ON e.id_empresa = cs.id_empresa_ap
        LEFT JOIN (
          SELECT csr.id_salida, string_agg(r.nombre, ', ') AS rancho
          FROM compras_salidas_rancho csr
            INNER JOIN otros.ranchos r ON r.id_rancho = csr.id_rancho
          GROUP BY csr.id_salida
        ) ran ON ran.id_salida = cs.id_salida
      WHERE cs.status <> 'ca' {$sql}
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
    $pdf = new MYpdf('L', 'mm', 'Legal'); // Letter
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
    $widths = array(150, 62);
    $header = array('Vehiculo');
    $aligns2 = array('L', 'L', 'L', 'C', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'R', 'L', 'L');
    $widths2 = array(12, 13, 11, 16, 40, 40, 16, 16, 16, 14, 12, 17, 29, 26);
    $header2 = array('Emp', 'Fecha', 'Hr Carga', 'Folio Salida', 'Rancho', 'Operador', 'Hor Ini', 'Hor Fin', 'Hor Total',
        'Litros', 'Precio', 'Total', 'Implemento', 'Observa');

    $aligns3 = array('R', 'R', 'R', 'R');
    $widths3 = array(14, 18, 14, 18);
    $header3 = array('Rendi lt/Hr', 'Kilómetros', 'Rendi Km/lt', 'Acumulado');

    $alignst = [['R', 'R', 'R', 'R', 'R'], $aligns3];
    $widthst = [[164, 16, 14, 12, 17], $widths3];

    $costoacumulado = 0;
    $auxvehi = '';
    $total_kms = $total_hrs = $total_litros = $total_importe = 0;
    $ttotal_kms = $ttotal_hrs = $ttotal_litros = $ttotal_importe = 0;

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
        if ($key != 0) {
          // ------
          $auxy = $pdf->GetY();
          $pdf->SetX(6);
          $pdf->SetAligns($alignst[0]);
          $pdf->SetWidths($widthst[0]);

          $pdf->SetFont('Arial', 'B',  7.5);
          $pdf->SetTextColor(0, 0, 0);
          $pdf->Row(array('TOTALES',
              MyString::formatoNumero($total_hrs, 2, '', false),
              MyString::formatoNumero($total_litros, 2, '', false),
              '',
              MyString::formatoNumero($total_importe, 2, '', false)
            ),
            true, false
          );

          $pdf->SetY($auxy);

          $pdf->SetX(285);
          $pdf->SetAligns($aligns3);
          $pdf->SetWidths($widths3);
          $pdf->Row([
            MyString::formatoNumero($total_litros/($total_hrs>0? $total_hrs: 1), 2, '', false),
            MyString::formatoNumero($total_kms, 2, '', false),
            MyString::formatoNumero($total_kms/($total_litros>0? $total_litros: 1), 2, '', false),
            ''
          ], false, false);
          // ------

          $pdf->SetY($pdf->GetY()+2);
        }
        $total_kms = $total_hrs = $total_litros = $total_importe = 0;

        $pdf->SetFont('Arial','B', 7.5);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row([$vehiculo->activo, $vehiculo->labor], false);

        // ------
        $auxy = $pdf->GetY();
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(180,180,180);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns2);
        $pdf->SetWidths($widths2);
        $pdf->Row($header2, true);

        $pdf->SetY($auxy);

        $pdf->SetX(285);
        $pdf->SetAligns($aligns3);
        $pdf->SetWidths($widths3);
        $pdf->Row($header3, true);
        // ------

        $auxvehi = $vehiculo->id_activo.$vehiculo->labor;
        $costoacumulado = 0;
      }

      $hrs = ($vehiculo->horometro_fin - $vehiculo->horometro);
      $kms = ($vehiculo->odometro_fin - $vehiculo->odometro);
      $costoacumulado += $vehiculo->precio*$vehiculo->lts_combustible;
      $total_hrs      += $hrs;
      $total_kms      += $kms;
      $total_litros   += $vehiculo->lts_combustible;
      $total_importe  += ($vehiculo->precio*$vehiculo->lts_combustible);

      $ttotal_hrs      += $hrs;
      $ttotal_kms      += $kms;
      $ttotal_litros   += $vehiculo->lts_combustible;
      $ttotal_importe  += ($vehiculo->precio*$vehiculo->lts_combustible);

      // ------
      $auxy = $pdf->GetY();
      $pdf->SetFont('Arial','', 7.5);
      $pdf->SetTextColor(0,0,0);
      $pdf->SetX(6);
      $pdf->SetAligns($aligns2);
      $pdf->SetWidths($widths2);
      $pdf->Row(array(
        substr($vehiculo->rfc, 0, 3),
        MyString::fechaATexto($vehiculo->fecha, 'inm'),
        substr($vehiculo->hora_carga, 0, 5),
        $vehiculo->folio,
        $vehiculo->rancho,
        $vehiculo->operador,
        MyString::formatoNumero($vehiculo->horometro, 2, ''),
        MyString::formatoNumero($vehiculo->horometro_fin, 2, ''),
        MyString::formatoNumero($hrs, 2, '', false),
        MyString::formatoNumero($vehiculo->lts_combustible, 2, '', false),
        MyString::formatoNumero($vehiculo->precio, 2, '', false),
        MyString::formatoNumero($vehiculo->precio*$vehiculo->lts_combustible, 2, '', false),
        // MyString::formatoNumero(($vehiculo->lts_combustible/($hrs>0? $hrs: 1)), 2, '', false),
        // MyString::formatoNumero($costoacumulado, 2, '', false),
        $vehiculo->implemento,
        $vehiculo->observaciones,
      ), false, false);

      $pdf->SetY($auxy);

      $pdf->SetX(285);
      $pdf->SetAligns($aligns3);
      $pdf->SetWidths($widths3);
      $pdf->Row(array(
        MyString::formatoNumero(($vehiculo->lts_combustible/($hrs>0? $hrs: 1)), 2, '', false),
        MyString::formatoNumero($kms, 2, '', false),
        MyString::formatoNumero(($kms/($vehiculo->lts_combustible>0? $vehiculo->lts_combustible: 1)), 2, '', false),
        MyString::formatoNumero($costoacumulado, 2, '', false),
      ), false, false);
      // ------

    }

    // ------
    $auxy = $pdf->GetY();
    $pdf->SetX(6);
    $pdf->SetAligns($alignst[0]);
    $pdf->SetWidths($widthst[0]);

    $pdf->SetFont('Arial', 'B',  7.5);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Row(array('TOTALES',
        MyString::formatoNumero($total_hrs, 2, '', false),
        MyString::formatoNumero($total_litros, 2, '', false),
        '',
        MyString::formatoNumero($total_importe, 2, '', false)
      ),
      true, false
    );

    $pdf->SetY($auxy);

    $pdf->SetX(285);
    $pdf->SetAligns($aligns3);
    $pdf->SetWidths($widths3);
    $pdf->Row([
      MyString::formatoNumero($total_litros/($total_hrs>0? $total_hrs: 1), 2, '', false),
      MyString::formatoNumero($total_kms, 2, '', false),
      MyString::formatoNumero($total_kms/($total_litros>0? $total_litros: 1), 2, '', false),
      ''
    ], false, false);
    // ------

    $pdf->SetY($pdf->GetY()+2);

    // ------
    $auxy = $pdf->GetY();
    $pdf->SetX(6);
    $pdf->SetAligns($alignst[0]);
    $pdf->SetWidths($widthst[0]);

    $pdf->SetFont('Arial','B', 7.5);
    $pdf->SetTextColor(0,0,0);
    $pdf->Row(array('TOTALES GENERALES',
        MyString::formatoNumero($ttotal_hrs, 2, '', false),
        MyString::formatoNumero($ttotal_litros, 2, '', false),
        '',
        MyString::formatoNumero($ttotal_importe, 2, '', false)
      ),
      true, false
    );

    $pdf->SetY($auxy);

    $pdf->SetX(285);
    $pdf->SetAligns($aligns3);
    $pdf->SetWidths($widths3);
    $pdf->Row([
      MyString::formatoNumero($ttotal_litros/($ttotal_hrs>0? $ttotal_hrs: 1), 2, '', false),
      MyString::formatoNumero($ttotal_kms, 2, '', false),
      MyString::formatoNumero($ttotal_kms/($ttotal_litros>0? $ttotal_litros: 1), 2, '', false),
      ''
    ], false, false);
    // ------

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
        </tr>';

    $costoacumulado = 0;
    $auxvehi = '';
    $total_kms = $total_hrs = $total_litros = $total_importe = 0;
    $ttotal_kms = $ttotal_hrs = $ttotal_litros = $ttotal_importe = 0;

    $header2 = array('Fecha', 'Hr Carga', 'Folio Salida', 'Rancho', 'Operador', 'Hor Ini', 'Hor Fin', 'Hor Total',
        'Litros', 'Precio', 'Total', 'Implemento', 'Observa', '', 'Rendi lt/Hr', 'Kilómetros', 'Rendi Km/lt', 'Acumulado');

    foreach ($combustible as $key => $vehiculo)
    {
      if ($auxvehi != ($vehiculo->id_activo.$vehiculo->labor)) {
        if ($key != 0) {
          $html .= '<tr style="font-weight:bold">
            <td colspan="7" style="border:1px solid #000;background-color: #cccccc;">TOTALES</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.MyString::formatoNumero($total_hrs, 2, '', false).'</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.MyString::formatoNumero($total_litros, 2, '', false).'</td>
            <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">'.MyString::formatoNumero($total_importe, 2, '', false).'</td>
            <td colspan="2" style=""></td>
            <td style=""> </td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.MyString::formatoNumero($total_litros/($total_hrs>0? $total_hrs: 1), 2, '', false).'</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.MyString::formatoNumero($total_kms, 2, '', false).'</td>
            <td style="border:1px solid #000;background-color: #cccccc;">'.MyString::formatoNumero($total_kms/($total_litros>0? $total_litros: 1), 2, '', false).'</td>
            <td style=""></td>
          </tr>';
        }
        $total_kms = $total_hrs = $total_litros = $total_importe = 0;

        $html .= '<tr style="font-weight:bold">
            <td colspan="9" style="border:1px solid #000;background-color: #ffffff;">'.$vehiculo->activo.'</td>
            <td colspan="4" style="border:1px solid #000;background-color: #ffffff;">'.$vehiculo->labor.'</td>
          </tr>';

        $html .= '<tr style="font-weight:bold">';
        foreach ($header2 as $keyhh => $head) {
          $html .= '<td style="border:1px solid #000;background-color: #cccccc;">'.$head.'</td>';
        }
        $html .= '</tr>';

        $auxvehi = $vehiculo->id_activo.$vehiculo->labor;
        $costoacumulado = 0;
      }

      $hrs = ($vehiculo->horometro_fin - $vehiculo->horometro);
      $kms = ($vehiculo->odometro_fin - $vehiculo->odometro);
      $costoacumulado += $vehiculo->precio*$vehiculo->lts_combustible;
      $total_hrs      += $hrs;
      $total_kms      += $kms;
      $total_litros   += $vehiculo->lts_combustible;
      $total_importe  += ($vehiculo->precio*$vehiculo->lts_combustible);

      $ttotal_hrs      += $hrs;
      $ttotal_kms      += $kms;
      $ttotal_litros   += $vehiculo->lts_combustible;
      $ttotal_importe  += ($vehiculo->precio*$vehiculo->lts_combustible);

      $html .= '<tr style="">
          <td style="width:150px;border:1px solid #000;">'.$vehiculo->fecha.'</td>
          <td style="width:150px;border:1px solid #000;">'.substr($vehiculo->hora_carga, 0, 5).'</td>
          <td style="width:150px;border:1px solid #000;">'.$vehiculo->folio.'</td>
          <td style="width:250px;border:1px solid #000;">'.$vehiculo->rancho.'</td>
          <td style="width:250px;border:1px solid #000;">'.$vehiculo->operador.'</td>
          <td style="width:150px;border:1px solid #000;">'.MyString::formatoNumero($vehiculo->horometro, 2, '').'</td>
          <td style="width:150px;border:1px solid #000;">'.MyString::formatoNumero($vehiculo->horometro_fin, 2, '').'</td>
          <td style="width:150px;border:1px solid #000;">'.MyString::formatoNumero($hrs, 2, '', false).'</td>
          <td style="width:150px;border:1px solid #000;">'.MyString::formatoNumero($vehiculo->lts_combustible, 2, '', false).'</td>
          <td style="width:150px;border:1px solid #000;">'.MyString::formatoNumero($vehiculo->precio, 2, '', false).'</td>
          <td style="width:150px;border:1px solid #000;">'.MyString::formatoNumero($vehiculo->precio*$vehiculo->lts_combustible, 2, '', false).'</td>
          <td style="width:250px;border:1px solid #000;">'.$vehiculo->implemento.'</td>
          <td style="width:250px;border:1px solid #000;">'.$vehiculo->observaciones.'</td>
          <td style="width:250px;border:1px solid #000;"> </td>
          <td style="width:150px;border:1px solid #000;">'.MyString::formatoNumero(($vehiculo->lts_combustible/($hrs>0? $hrs: 1)), 2, '', false).'</td>
          <td style="width:150px;border:1px solid #000;">'.MyString::formatoNumero($kms, 2, '', false).'</td>
          <td style="width:150px;border:1px solid #000;">'.MyString::formatoNumero(($kms/($vehiculo->lts_combustible>0? $vehiculo->lts_combustible: 1)), 2, '', false).'</td>
          <td style="width:150px;border:1px solid #000;">'.MyString::formatoNumero($costoacumulado, 2, '', false).'</td>
        </tr>';
    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="7" style="border:1px solid #000;background-color: #cccccc;">TOTALES</td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.MyString::formatoNumero($total_hrs, 2, '', false).'</td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.MyString::formatoNumero($total_litros, 2, '', false).'</td>
          <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">'.MyString::formatoNumero($total_importe, 2, '', false).'</td>
          <td colspan="2" style=""></td>
          <td style=""> </td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.MyString::formatoNumero($total_litros/($total_hrs>0? $total_hrs: 1), 2, '', false).'</td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.MyString::formatoNumero($total_kms, 2, '', false).'</td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.MyString::formatoNumero($total_kms/($total_litros>0? $total_litros: 1), 2, '', false).'</td>
          <td style=""></td>
        </tr>

        <tr style="font-weight:bold">
          <td colspan="7" style="border:1px solid #000;background-color: #cccccc;">TOTALES GENERALES</td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.MyString::formatoNumero($ttotal_hrs, 2, '', false).'</td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.MyString::formatoNumero($ttotal_litros, 2, '', false).'</td>
          <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">'.MyString::formatoNumero($ttotal_importe, 2, '', false).'</td>
          <td colspan="2" style=""></td>
          <td style=""> </td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.MyString::formatoNumero($ttotal_litros/($ttotal_hrs>0? $ttotal_hrs: 1), 2, '', false).'</td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.MyString::formatoNumero($ttotal_kms, 2, '', false).'</td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.MyString::formatoNumero($ttotal_kms/($ttotal_litros>0? $ttotal_litros: 1), 2, '', false).'</td>
          <td style=""></td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }


}

/* End of file bascula_model.php */
/* Location: ./application/models/bascula_model.php */