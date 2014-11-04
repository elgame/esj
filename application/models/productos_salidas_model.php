<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class productos_salidas_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
  }

  /**
   * Obtiene el listado de facturas
   *
   * @return
   */
  public function getSalidas($perpage = '40')
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
    if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '')
      $sql = " AND Date(cs.fecha_creacion) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffecha1') != '')
      $sql = " AND Date(cs.fecha_creacion) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffecha2') != '')
      $sql = " AND Date(cs.fecha_creacion) = '".$this->input->get('ffecha2')."'";


    if($this->input->get('ffolio') != '')
    {
      $sql .= " AND cs.folio = '".$this->input->get('ffolio')."'";
    }

    if($this->input->get('did_empresa') != '')
    {
      $sql .= " AND e.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if ($this->input->get('fstatus') != '')
    {
      $sql .= " AND cs.status = '".$this->input->get('fstatus')."'";
    }
    else
    {
      $sql .= " AND cs.status in ('s', 'ca')";
    }

    $query = BDUtil::pagination(
        "SELECT cs.id_salida,
                cs.id_empresa, e.nombre_fiscal AS empresa,
                cs.id_empleado, u.nombre AS empleado,
                cs.folio, cs.fecha_creacion AS fecha, cs.fecha_registro,
                cs.status, cs.concepto
        FROM compras_salidas AS cs
        INNER JOIN empresas AS e ON e.id_empresa = cs.id_empresa
        INNER JOIN usuarios AS u ON u.id = cs.id_empleado
        WHERE 1 = 1 AND cs.concepto is null {$sql}
        ORDER BY (cs.fecha_creacion, cs.folio) DESC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'salidas'           => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['salidas'] = $res->result();

    return $response;
  }

  /**
   * Agrega la info de una salida sin productos.
   *
   * @return array
   */
  public function agregar($data = null)
  {
    if ( ! $data)
    {
      $data = array(
        'id_empresa'      => $_POST['empresaId'],
        'id_empleado'     => $this->session->userdata('id_usuario'),
        'folio'           => $_POST['folio'],
        'fecha_creacion'  => str_replace('T', ' ', $_POST['fecha']),
        'fecha_registro'  => str_replace('T', ' ', $_POST['fecha']),
        // 'concepto'        => '', //$_POST['conceptoSalida']
        'status'          => 's',
      );
    }

    $this->db->insert('compras_salidas', $data);

    return array('passes' => true, 'msg' => 3, 'id_salida' => $this->db->insert_id());
  }

  /**
   * Agrega los productos de una salida.
   *
   * @return array
   */
  public function agregarProductos($idSalida, $productos = null)
  {
    if ( ! $productos)
    {
      $this->load->model('inventario_model');

      $productos = array();
      foreach ($_POST['concepto'] as $key => $concepto)
      {
        if($_POST['precioUnit'][$key] <= 0) {
          $res = $this->inventario_model->promedioData($_POST['productoId'][$key], date('Y-m-d'), date('Y-m-d'));
          $saldo = array_shift($res);
          $saldo = $saldo['saldo'][1];
        }else
          $saldo = $_POST['precioUnit'][$key];

        $productos[] = array(
          'id_salida'       => $idSalida,
          'id_producto'     => $_POST['productoId'][$key],
          'no_row'          => $key,
          'cantidad'        => $_POST['cantidad'][$key],
          'precio_unitario' => $saldo,
          'id_area'         => $_POST['codigoAreaId'][$key],
          'tipo_orden'      => $_POST['tipoProducto'][$key],
        );
      }
    }

    $this->db->insert_batch('compras_salidas_productos', $productos);

    return array('passes' => true, 'msg' => 3);
  }

  /**
   * Modificar los productos de una salida.
   *
   * @return array
   */
  public function modificarProductos($idSalida)
  {
    foreach ($_POST['id_producto'] as $key => $producto)
    {
      $this->db->update('compras_salidas_productos',
        array(
          'cantidad' => $_POST['cantidad'][$key],
        ),
        array('id_salida' => $idSalida, 'id_producto' => $producto));
    }

    return array('passes' => true, 'msg' => 5);
  }

  public function cancelar($idOrden)
  {
    $this->db->update('compras_salidas', array('status' => 'ca'), array('id_salida' => $idOrden));

    return array('passes' => true);
  }

  public function info($idSalida, $full = false)
  {
    $query = $this->db->query(
      "SELECT cs.id_salida,
              cs.id_empresa, e.nombre_fiscal AS empresa, e.logo,
              cs.id_empleado, (u.nombre || ' ' || u.apellido_paterno) AS empleado,
              cs.folio, cs.fecha_creacion AS fecha, cs.fecha_registro,
              cs.status, cs.concepto, cs.solicito
        FROM compras_salidas AS cs
        INNER JOIN empresas AS e ON e.id_empresa = cs.id_empresa
        INNER JOIN usuarios AS u ON u.id = cs.id_empleado
        WHERE cs.id_salida = {$idSalida}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->result();

      $query->free_result();
      if ($full)
      {
        $query = $this->db->query(
          "SELECT csp.id_salida, csp.no_row,
                  csp.id_producto, pr.nombre AS producto, pr.codigo,
                  pu.abreviatura, pu.nombre as unidad,
                  csp.cantidad, csp.precio_unitario, csp.tipo_orden,
                  ca.id_area, ca.nombre AS nombre_codigo, ca.codigo_fin
           FROM compras_salidas_productos AS csp
             INNER JOIN productos AS pr ON pr.id_producto = csp.id_producto
             LEFT JOIN productos_unidades AS pu ON pu.id_unidad = pr.id_unidad
             LEFT JOIN compras_areas AS ca ON ca.id_area = csp.id_area
           WHERE csp.id_salida = {$data['info'][0]->id_salida}");

        $data['info'][0]->productos = array();
        if ($query->num_rows() > 0)
        {
          $data['info'][0]->productos = $query->result();
        }
      }

    }

    return $data;
  }

  public function folio($tipo = 'p')
  {
    $res = $this->db->select('folio')
      ->from('compras_salidas')
      ->where('concepto', null)
      ->order_by('folio', 'DESC')
      ->limit(1)->get()->row();

    $folio = (isset($res->folio) ? $res->folio : 0) + 1;

    return $folio;
  }


  /**
  * Visualiza/Descarga el PDF de la orden de compra.
  *
  * @return void
  */
  public function print_orden_compra($salidaID, $path = null)
  {
    $this->load->model('compras_areas_model');

    $orden = $this->info($salidaID, true);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    // $pdf->show_head = true;
    $pdf->titulo1 = $orden['info'][0]->empresa;
    $tipo_orden = 'SALIDA DE PRODUCTOS';
    // if($orden['info'][0]->tipo_orden == 'd')
    //   $tipo_orden = 'ORDEN DE SERVICIO';
    // elseif($orden['info'][0]->tipo_orden == 'f')
    //   $tipo_orden = 'ORDEN DE FLETE';

    $pdf->logo = $orden['info'][0]->logo!=''? (file_exists($orden['info'][0]->logo)? $orden['info'][0]->logo: '') : '';

    $pdf->AliasNbPages();
    $pdf->AddPage();

    $pdf->SetXY(6, $pdf->GetY()-10);

    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(150, 50));
    $pdf->Row(array(
      $tipo_orden,
      'No '.String::formatoNumero($orden['info'][0]->folio, 2, ''),
    ), false, false);
    $pdf->SetFont('helvetica','', 8);
    $pdf->SetX(6);
    $pdf->Row(array(
      'PROVEEDOR: ' . $orden['info'][0]->empleado,
      String::fechaATexto($orden['info'][0]->fecha, '/c'),
    ), false, false);

    $aligns = array('C', 'C', 'L', 'R', 'R');
    $widths = array(35, 25, 94, 25, 25);
    $header = array('CANT.', 'CODIGO', 'DESCRIPCION', 'PRECIO', 'IMPORTE');

    $subtotal = $iva = $total = $retencion = $ieps = 0;

    $tipoCambio = 0;
    $codigoAreas = array();

    foreach ($orden['info'][0]->productos as $key => $prod)
    {
      $tipoCambio = 1;

      $band_head = false;
      if($pdf->GetY() >= $pdf->limiteY || $key==0) { //salta de pagina si exede el max
        if($pdf->GetY()+5 >= $pdf->limiteY)
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
        $prod->cantidad.' '.$prod->abreviatura,
        $prod->codigo.'/'.$prod->codigo_fin,
        $prod->producto,
        String::formatoNumero($prod->precio_unitario, 2, '$', false),
        String::formatoNumero(($prod->precio_unitario*$prod->cantidad), 2, '$', false),
      );

      $pdf->SetX(6);
      $pdf->Row($datos, false);

      $total     += floatval($prod->precio_unitario*$prod->cantidad);

      if($prod->id_area != '' && !array_key_exists($prod->id_area, $codigoAreas))
        $codigoAreas[$prod->id_area] = $this->compras_areas_model->getDescripCodigo($prod->id_area);
    }

    $yy = $pdf->GetY();

    //Otros datos
    // $pdf->SetXY(6, $yy);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(154));

    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(104, 50));
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('REGISTRO: '.strtoupper($orden['info'][0]->empleado), '' ), false, false);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(154));
    $pdf->SetXY(6, $pdf->GetY()-2);
    $pdf->Row(array('SOLICITA: '.strtoupper($orden['info'][0]->solicito)), false, false);

    $pdf->SetXY(6, $pdf->GetY()+6);
    $pdf->Row(array('________________________________________________________________________________________________'), false, false);
    $yy2 = $pdf->GetY();
    if(count($codigoAreas) > 0){
      // $yy2 -= 9;
      // $pdf->SetXY(160, $yy2);
      // $pdf->Row(array('_______________________________'), false, false);
      $yy2 = $pdf->GetY();
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetWidths(array(155));
      $pdf->Row(array('COD/AREA: ' . implode(' - ', $codigoAreas)), false, false);
    }
    // ($tipoCambio ? "TIPO DE CAMBIO: " . $tipoCambio : ''),

    // $pdf->SetXY(6, $pdf->GetY());
    // $pdf->Row(array('OBSERVACIONES: '.$orden['info'][0]->descripcion), false, false);
    // if($orden['info'][0]->tipo_orden == 'f'){
    //   $pdf->SetWidths(array(205));
    //   $pdf->SetX(6);
    //   $pdf->Row(array(substr($clientessss, 2)), false, false);
    //   $pdf->SetXY(6, $pdf->GetY()-3);
    //   $pdf->Row(array('_________________________________________________________________________________________________________________________________'), false, false);
    // }

    $y_compras = $pdf->GetY();

    //Totales
    $pdf->SetFont('Arial','',8);
    $pdf->SetXY(160, $yy);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(25, 25));
    $pdf->SetX(160);
    $pdf->Row(array('TOTAL', String::formatoNumero($total, 2, '$', false)), false, true);

    if ($path)
    {
      $file = $path.'SALIDA_PRODUCTO'.date('Y-m-d').'.pdf';
      $pdf->Output($file, 'F');
      return $file;
    }
    else
    {
      $pdf->Output('SALIDA_PRODUCTO'.date('Y-m-d').'.pdf', 'I');
    }
  }


  /**
   * Reportes
   *******************************
   * @return void
   */
  public function getDataGastos()
  {
    $this->load->model('compras_areas_model');
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

    $response = array();


    if (isset($_GET['dareas']) && count($_GET['dareas']) > 0)
    {
      foreach ($_GET['dareas'] as $key => $value) {
        $ids_hijos = $value.$this->compras_areas_model->getHijos($value);
        $result = $this->db->query("SELECT ca.nombre, COALESCE(
                                      (SELECT (Sum(csp.cantidad) * Sum(csp.precio_unitario)) AS importe
                                      FROM compras_salidas_productos csp
                                      WHERE csp.id_area In({$ids_hijos}))
                                    , 0) AS importe
                                    FROM compras_areas ca
                                    WHERE ca.id_area = {$value}");
        $response[] = $result->row();
        $result->free_result();

        if (isset($_GET['dmovimientos']{0}) && $_GET['dmovimientos'] == '1' && $response[count($response)-1]->importe == 0)
          array_pop($response);
        else {
          // Si es desglosado carga independientes
          if (isset($_GET['ddesglosado']{0}) && $_GET['ddesglosado'] == '1') {
            $response[count($response)-1]->detalle = $this->db->query(
                "SELECT ca.id_area, ca.nombre, Date(cs.fecha_creacion) AS fecha, cs.folio, p.nombre AS producto, (csp.cantidad * csp.precio_unitario) AS importe
                FROM compras_salidas cs
                  INNER JOIN compras_salidas_productos csp ON cs.id_salida = csp.id_salida
                  INNER JOIN compras_areas ca ON ca.id_area = csp.id_area
                  INNER JOIN productos p ON p.id_producto = csp.id_producto
                WHERE ca.id_area In({$ids_hijos})
                ORDER BY nombre")->result();
          }
        }

      }
    }

    return $response;
  }
  public function rpt_gastos_pdf()
  {
    $combustible = $this->getDataGastos();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = "Reporte de Gastos";

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
    $aligns = array('L', 'R');
    $widths = array(170, 35);
    $header = array('Nombre', 'Importe');
    $aligns2 = array('L', 'L', 'L', 'L', 'R', 'R');
    $widths2 = array(18, 22, 65, 65, 35);
    $header2 = array('Fecha', 'Folio', 'C Costo', 'Producto', 'Importe');

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

        if (isset($vehiculo->detalle)) {
          $pdf->SetX(6);
          $pdf->SetAligns($aligns2);
          $pdf->SetWidths($widths2);
          $pdf->Row($header2, true);
        }
      }
      $pdf->SetFont('Arial','B',8);
      $pdf->SetTextColor(0,0,0);
      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row(array(
        $vehiculo->nombre,
        String::formatoNumero($vehiculo->importe, 2, '', false),
      ), false, false);

      $lts_combustible += floatval($vehiculo->importe);

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
            $item->folio,
            $item->nombre,
            $item->producto,
            String::formatoNumero($item->importe, 2, '', false),
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
        String::formatoNumero($lts_combustible, 2, '', false) ),
    true, false);

    $pdf->Output('reporte_gasto_codigo.pdf', 'I');
  }

  public function rpt_gastos_xls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=reporte_gasto_codigo.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $combustible = $this->getDataGastos();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "Reporte de Gastos";
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
          <td colspan="4" style="border:1px solid #000;background-color: #cccccc;">Nombre</td>
          <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">Importe</td>
        </tr>';
    if (isset($combustible[0]->detalle)) {
      $html .= '<tr style="font-weight:bold">
        <td></td>
        <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Fecha</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio</td>
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">C Costo</td>
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Producto</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Importe</td>
      </tr>';
    }
    $lts_combustible = $horas_totales = 0;
    foreach ($combustible as $key => $vehiculo)
    {
      $lts_combustible += floatval($vehiculo->importe);

      $html .= '<tr style="font-weight:bold">
          <td colspan="4" style="border:1px solid #000;background-color: #cccccc;">'.$vehiculo->nombre.'</td>
          <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">'.$vehiculo->importe.'</td>
        </tr>';
      if (isset($vehiculo->detalle)) {
        foreach ($vehiculo->detalle as $key2 => $item)
        {
          $html .= '<tr>
              <td></td>
              <td style="width:100px;border:1px solid #000;background-color: #cccccc;">'.$item->fecha.'</td>
              <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$item->folio.'</td>
              <td style="width:400px;border:1px solid #000;background-color: #cccccc;">'.$item->nombre.'</td>
              <td style="width:400px;border:1px solid #000;background-color: #cccccc;">'.$item->producto.'</td>
              <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$item->importe.'</td>
            </tr>';
        }
      }

    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="4">TOTALES</td>
          <td colspan="2" style="border:1px solid #000;">'.$lts_combustible.'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

}