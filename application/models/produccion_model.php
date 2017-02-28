<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class produccion_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
  }

  /**
   * Obtiene el listado de facturas
   *
   * @return
   */
  public function getProduccion($perpage = '100')
  {
    $sql = '';
    //paginacion
    $params = array(
        'result_items_per_page' => $perpage,
        'result_page'           => (isset($_GET['pag'])? $_GET['pag']: 0)
    );

    if($params['result_page'] % $params['result_items_per_page'] == 0)
      $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

    //Filtros para buscar
    if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '')
      $sql = " AND Date(ph.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffecha1') != '')
      $sql = " AND Date(ph.fecha) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffecha2') != '')
      $sql = " AND Date(ph.fecha) = '".$this->input->get('ffecha2')."'";


    if($this->input->get('ffolio') != '')
    {
      $sql .= " AND ph.id = '".$this->input->get('ffolio')."'";
    }

    if($this->input->get('did_clasificacion') != '')
    {
      $sql .= " AND c.id_clasificacion = '".$this->input->get('did_clasificacion')."'";
    }

    if($this->input->get('did_empresa') != '')
    {
      $sql .= " AND e.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if($this->input->get('fstatus') != '')
    {
      $sql .= " AND ph.status = '".$this->input->get('fstatus')."'";
    }

    $query = BDUtil::pagination(
        "SELECT ph.id, ph.fecha, ph.fecha_produccion, ph.costo, ph.cantidad, ph.status, e.nombre_fiscal, c.nombre, ph.id_salida
        FROM otros.produccion_historial ph
          INNER JOIN clasificaciones c ON c.id_clasificacion = ph.id_clasificacion
          INNER JOIN empresas e ON e.id_empresa = ph.id_empresa
        WHERE ph.tipo = 't' {$sql}
        ORDER BY ph.id DESC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'produccion'     => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['produccion'] = $res->result();

    return $response;
  }

  /**
   * Agrega la info de una salida sin productos.
   *
   * @return array
   */
  public function agregar($data = null)
  {
    $this->load->model('productos_salidas_model');

    $res = $this->productos_salidas_model->agregar(array(
      'id_empresa'      => $this->input->post('empresaId'),
      'id_almacen'      => 1, //($this->input->post('id_almacen')>0?$this->input->post('id_almacen'):1),
      'id_empleado'     => $this->session->userdata('id_usuario'),
      'folio'           => $this->productos_salidas_model->folio(),
      'concepto'        => 'Salida generada automaticamente en Produccion',
      'status'          => 's',
      'fecha_creacion'  => str_replace('T', ' ', $_POST['fecha_produccion']),
      'fecha_registro'  => date("Y-m-d H:i:s"),
    ));
    $id_salida = $res['id_salida'];

    $salida = array();
    foreach ($_POST['productoId'] as $key => $produto)
    {
      $salida[] = array(
        'id_salida'       => $id_salida,
        'no_row'          => $key,
        'id_producto'     => $produto,
        'cantidad'        => abs($_POST['cantidad'][$key]),
        'precio_unitario' => $_POST['precioUnit'][$key],
      );
    }
    $this->productos_salidas_model->agregarProductos($id_salida, $salida);

    $data = array(
      'id_clasificacion' => $this->input->post('id_clasificacion'),
      'id_empresa'       => $this->input->post('empresaId'),
      'id_empleado'      => $this->session->userdata('id_usuario'),
      'fecha_produccion' => $this->input->post('fecha_produccion'),
      'cantidad'         => $this->input->post('cantidad_produccion'),
      'costo_materiap'   => $this->input->post('costo_materiap'),
      'costo_adicional'  => $this->input->post('costo_adicional'),
      'costo'            => $this->input->post('costo'),
      'tipo'             => 't',
      'id_salida'        => $id_salida,
    );
    $this->db->insert('otros.produccion_historial', $data);

    return array('passes' => true, 'msg' => 3, 'id_salida' => $id_salida);
  }

  public function info($idProduccion)
  {
    $query = $this->db->query("SELECT * FROM otros.produccion_historial WHERE id = {$idProduccion}");
    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->row();
    }

    return $data;
  }

  public function cancelar($idProduccion)
  {
    $this->db->update('otros.produccion_historial', array('status' => 'f'), "id = {$idProduccion}");

    $produccion = $this->info($idProduccion);

    $this->load->model('productos_salidas_model');
    $this->productos_salidas_model->cancelar($produccion['info']->id_salida);

    return array('passes' => true);
  }


  /**
   * Reporte existencias por unidad
   *
   * @return
   */
  public function getInventarioData($id_producto=null, $id_almacen=null)
  {
    $sqlall = $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    if($this->input->get('fid_producto') != '' || $id_producto > 0){
      $id_producto = $id_producto>0? $id_producto: $this->input->get('fid_producto');
      $sql .= " AND c.id_clasificacion = ".$id_producto;
    }

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sqlall .= " AND ph.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if ($this->input->get('con_existencia') == 'si') {
      $sql .= " AND ((Coalesce(sprd.produccion, 0)-Coalesce(svnt.ventas, 0))+Coalesce(prd.produccion, 0)-Coalesce(vnt.ventas, 0)) > 0";
    }

    if ($this->input->get('con_movimiento') == 'si') {
      $sql .= " AND (Coalesce(vnt.ventas, 0) > 0 OR Coalesce(prd.produccion, 0) > 0)";
    }

    // $id_almacen = $id_almacen>0? $id_almacen: $this->input->get('did_almacen');
    // if ($id_almacen > 0) {
    //   $sql_com .= " AND co.id_almacen = ".$id_almacen;
    //   $sql_sal .= " AND sa.id_almacen = ".$id_almacen;
    // }

    $res = $this->db->query(
      "SELECT c.id_clasificacion, c.nombre, Coalesce(vnt.ventas, 0) AS ventas, Coalesce(vnt.precio_prom, 0) AS precio_prom,
        Coalesce(prd.produccion, 0) AS produccion, Coalesce(prd.costo_prom, 0) AS costo_prom,
        (Coalesce(sprd.produccion, 0)-Coalesce(svnt.ventas, 0)) AS existencia_ant,
        ((Coalesce(sprd.produccion, 0)-Coalesce(svnt.ventas, 0))+Coalesce(prd.produccion, 0)-Coalesce(vnt.ventas, 0)) AS existencia
      FROM clasificaciones c LEFT JOIN
      (
        SELECT ph.id_clasificacion, Sum(ph.cantidad) AS ventas, (Sum(ph.cantidad*ph.precio_venta)/Sum(ph.cantidad)) AS precio_prom
        FROM otros.produccion_historial ph
        WHERE ph.status = 't' AND ph.tipo = 'f' AND Date(ph.fecha_produccion) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}' {$sqlall}
        GROUP BY ph.id_clasificacion
      ) vnt ON c.id_clasificacion = vnt.id_clasificacion
      LEFT JOIN (
        SELECT ph.id_clasificacion, Sum(ph.cantidad) AS produccion, (Sum(ph.costo)/Sum(ph.cantidad)) AS costo_prom
        FROM otros.produccion_historial ph
        WHERE ph.status = 't' AND ph.tipo = 't' AND Date(ph.fecha_produccion) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}' {$sqlall}
        GROUP BY ph.id_clasificacion
      ) prd ON c.id_clasificacion = prd.id_clasificacion
      LEFT JOIN (
        SELECT ph.id_clasificacion, Sum(ph.cantidad) AS ventas, (Sum(ph.cantidad*ph.precio_venta)/Sum(ph.cantidad)) AS precio_prom
        FROM otros.produccion_historial ph
        WHERE ph.status = 't' AND ph.tipo = 'f' AND Date(ph.fecha_produccion) < '{$fecha}' {$sqlall}
        GROUP BY ph.id_clasificacion
      ) svnt ON c.id_clasificacion = svnt.id_clasificacion
      LEFT JOIN (
        SELECT ph.id_clasificacion, Sum(ph.cantidad) AS produccion, (Sum(ph.costo)/Sum(ph.cantidad)) AS costo_prom
        FROM otros.produccion_historial ph
        WHERE ph.status = 't' AND ph.tipo = 't' AND Date(ph.fecha_produccion) < '{$fecha}' {$sqlall}
        GROUP BY ph.id_clasificacion
      ) sprd ON c.id_clasificacion = sprd.id_clasificacion
      WHERE c.status = 't' AND c.inventario = 't' {$sql}
      ORDER BY c.nombre ASC
      ");

    $response = array();
    if($res->num_rows() > 0)
      $response = $res->result();

    return $response;
  }
  /**
   * Reporte existencias por unidad pdf
   */
  public function getInventarioPdf(){
    $res = $this->getInventarioData();

    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    // $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

      if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'Inventario de produccion';
    $pdf->titulo3 = 'Del: '.String::fechaAT($this->input->get('ffecha1'))." Al ".String::fechaAT($this->input->get('ffecha2'))."\n";
    $pdf->titulo3 .= ($this->input->get('fproducto')? $this->input->get('fproducto'): '');
    // $pdf->titulo3 .= (isset($almacen['info']->nombre)? 'Almacen '.$almacen['info']->nombre: '');
    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'R', 'R', 'R', 'R', 'R', 'R');
    $widths = array(65, 20, 20, 18, 20, 18, 20);
    $header = array('Clasificacion', 'Saldo', 'Produccion', 'Costo P.', 'Ventas', 'Precio P.', 'Existencia');

    $familia = '';
    $totales = array('familia' => array(0,0,0,0), 'general' => array(0,0,0,0));
    $total_cargos = $total_abonos = $total_saldo = 0;
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

      $datos = array($item->nombre,
        String::formatoNumero($item->existencia_ant, 2, '', false),
        String::formatoNumero($item->produccion, 2, '', false),
        String::formatoNumero($item->costo_prom, 2, '', false),
        String::formatoNumero($item->ventas, 2, '', false),
        String::formatoNumero($item->precio_prom, 2, '', false),
        String::formatoNumero($item->existencia, 2, '', false),
        );

      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false);
    }

    $pdf->SetFont('Arial','B',8);

    $pdf->Output('inventario.pdf', 'I');
  }

  public function getInventarioXls(){
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=inventario.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getInventarioData();

    $this->load->model('empresas_model');
    $this->load->model('almacenes_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $almacen = $this->almacenes_model->getAlmacenInfo(intval($this->input->get('did_almacen')));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Inventario de produccion';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $titulo3 .= ($this->input->get('fproducto')? $this->input->get('fproducto'): '');

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
          <td style="width:30px;border:1px solid #000;background-color: #cccccc;"></td>
          <td style="width:350px;border:1px solid #000;background-color: #cccccc;">Clasificacion</td>
          <td style="width:160px;border:1px solid #000;background-color: #cccccc;">Saldo</td>
          <td style="width:160px;border:1px solid #000;background-color: #cccccc;">Produccion</td>
          <td style="width:160px;border:1px solid #000;background-color: #cccccc;">Costo P.</td>
          <td style="width:160px;border:1px solid #000;background-color: #cccccc;">Ventas</td>
          <td style="width:160px;border:1px solid #000;background-color: #cccccc;">Precio P.</td>
          <td style="width:160px;border:1px solid #000;background-color: #cccccc;">Existencia</td>
        </tr>';

    $familia = '';
    $totales = array('familia' => array(0,0,0,0), 'general' => array(0,0,0,0));
    $total_cargos = $total_abonos = $total_saldo = 0;
    foreach($res as $key => $item){
      $band_head = false;
      // if($key==0){

      //   if ($key == 0)
      //   {
      //     $familia = $item->nombre;
      //     $html .= '<tr>
      //         <td colspan="6" style="font-size:16px;border:1px solid #000;">'.$familia.'</td>
      //       </tr>';
      //   }
      // }

      // if ($familia <> $item->nombre)
      // {
      //   if($key > 0){
      //     $html .= '
      //       <tr style="font-weight:bold">
      //         <td></td>
      //         <td style="border:1px solid #000;">TOTALES</td>
      //         <td style="border:1px solid #000;">'.$totales['familia'][0].'</td>
      //         <td style="border:1px solid #000;">'.$totales['familia'][1].'</td>
      //         <td style="border:1px solid #000;">'.$totales['familia'][2].'</td>
      //         <td style="border:1px solid #000;">'.$totales['familia'][3].'</td>
      //       </tr>
      //       <tr>
      //         <td colspan="6"></td>
      //       </tr>
      //       <tr>
      //         <td colspan="6"></td>
      //       </tr>';
      //   }
      //   $totales['familia'] = array(0,0,0,0);

      //   $familia = $item->nombre;
      //   $html .= '<tr>
      //         <td colspan="6" style="font-size:16px;border:1px solid #000;">'.$familia.'</td>
      //       </tr>';
      // }

      // $imprimir = true;
      // $existencia = $item->saldo_anterior+$item->entradas-$item->salidas;
      // if($this->input->get('con_existencia') == 'si')
      //   if($existencia <= 0)
      //     $imprimir = false;
      // if($this->input->get('con_movimiento') == 'si')
      //   if($item->entradas <= 0 && $item->salidas <= 0)
      //     $imprimir = false;


      // if($imprimir)
      // {
      //   $totales['familia'][0] += $item->saldo_anterior;
      //   $totales['familia'][1] += $item->entradas;
      //   $totales['familia'][2] += $item->salidas;
      //   $totales['familia'][3] += $existencia;

      //   $totales['general'][0] += $item->saldo_anterior;
      //   $totales['general'][1] += $item->entradas;
      //   $totales['general'][2] += $item->salidas;
      //   $totales['general'][3] += $existencia;

        $html .= '<tr>
              <td style="width:30px;border:1px solid #000;"></td>
              <td style="width:300px;border:1px solid #000;">'.$item->nombre.'</td>
              <td style="width:200px;border:1px solid #000;">'.$item->existencia_ant.'</td>
              <td style="width:200px;border:1px solid #000;">'.$item->produccion.'</td>
              <td style="width:200px;border:1px solid #000;">'.$item->costo_prom.'</td>
              <td style="width:200px;border:1px solid #000;">'.$item->ventas.'</td>
              <td style="width:200px;border:1px solid #000;">'.$item->precio_prom.'</td>
              <td style="width:200px;border:1px solid #000;">'.$item->existencia.'</td>
            </tr>';
      // }
    }

    // $html .= '
    //         <tr style="font-weight:bold">
    //           <td></td>
    //           <td style="border:1px solid #000;">TOTALES</td>
    //           <td style="border:1px solid #000;">'.$totales['familia'][0].'</td>
    //           <td style="border:1px solid #000;">'.$totales['familia'][1].'</td>
    //           <td style="border:1px solid #000;">'.$totales['familia'][2].'</td>
    //           <td style="border:1px solid #000;">'.$totales['familia'][3].'</td>
    //         </tr>
    //         <tr>
    //           <td colspan="6"></td>
    //         </tr>
    //         <tr>
    //           <td colspan="6"></td>
    //         </tr>
    //         <tr style="font-weight:bold">
    //           <td></td>
    //           <td style="border:1px solid #000;">GENERAL</td>
    //           <td style="border:1px solid #000;">'.$totales['general'][0].'</td>
    //           <td style="border:1px solid #000;">'.$totales['general'][1].'</td>
    //           <td style="border:1px solid #000;">'.$totales['general'][2].'</td>
    //           <td style="border:1px solid #000;">'.$totales['general'][3].'</td>
    //         </tr>';

    $html .= '</tbody>
    </table>';

    echo $html;
  }

}