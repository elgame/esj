<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class produccion_almacen_model extends CI_Model {

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
      $sql = " AND Date(co.fecha_creacion) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffecha1') != '')
      $sql = " AND Date(co.fecha_creacion) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffecha2') != '')
      $sql = " AND Date(co.fecha_creacion) = '".$this->input->get('ffecha2')."'";


    if($this->input->get('ffolio') != '')
    {
      $sql .= " AND csp.folio = '".$this->input->get('ffolio')."'";
    }

    if($this->input->get('did_producto') != '')
    {
      $sql .= " AND imp.id_producto = '".$this->input->get('did_producto')."'";
    }

    if($this->input->get('did_empresa') != '')
    {
      $sql .= " AND co.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if($this->input->get('fstatus') != '')
    {
      $sql .= " AND co.status IN('a', 'f', 'n') AND cs.status IN('s', 'n')";
    }

    $query = BDUtil::pagination(
        "SELECT csp.id_salida, csp.id_orden, csp.folio, Date(co.fecha_creacion) AS fecha,
            co.descripcion, u.usuario, imp.costo, imp.cantidad, imp.producto, co.status,
            e.nombre_fiscal
          FROM compras_salidas_produccion csp
            INNER JOIN compras_ordenes co ON co.id_orden = csp.id_orden
            INNER JOIN compras_salidas cs ON cs.id_salida = csp.id_salida
            INNER JOIN usuarios u ON u.id = co.id_empleado
            INNER JOIN (
              SELECT cp.id_orden, p.id_producto, p.nombre AS producto,
                Sum(cp.importe) AS costo, Sum(cp.cantidad) AS cantidad
              FROM compras_productos cp
                INNER JOIN productos p ON p.id_producto = cp.id_producto
              GROUP BY cp.id_orden, p.id_producto
            ) imp ON imp.id_orden = co.id_orden
            INNER JOIN empresas e ON e.id_empresa = co.id_empresa
          WHERE 1 = 1 {$sql}
          ORDER BY fecha DESC
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

    // Se registra la salida de almacen con la materia prima
    $res = $this->productos_salidas_model->agregar(array(
      'id_empresa'      => $this->input->post('empresaId'),
      'id_almacen'      => ($this->input->post('id_almacen')>0?$this->input->post('id_almacen'):1),
      'id_empleado'     => $this->session->userdata('id_usuario'),
      'folio'           => $this->productos_salidas_model->folio(),
      'concepto'        => 'Salida generada automáticamente en Producción de soluciones',
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


    // Se registra la orden de compra para ingresar los productos
    $this->load->model('compras_ordenes_model');
    $fecha = date("Y-m-d");
    $proveedor = $this->db->query("SELECT id_proveedor FROM proveedores WHERE UPPER(nombre_fiscal)='FICTICIO' LIMIT 1")->row();
    $departamento = $this->db->query("SELECT id_departamento FROM compras_departamentos WHERE UPPER(nombre)='FICTICIO' LIMIT 1")->row();
    $data = array(
      'id_empresa'         => $this->input->post('empresaId'),
      'id_proveedor'       => $proveedor->id_proveedor,
      'id_departamento'    => $departamento->id_departamento,
      'id_empleado'        => $this->session->userdata('id_usuario'),
      'id_almacen'         => ($this->input->post('id_almacen_produc')>0?$this->input->post('id_almacen_produc'):1),
      'folio'              => 0,
      'status'             => 'n',
      'tipo_orden'         => 'p',
      'autorizado'         => 't',
      'fecha_autorizacion' => $fecha,
      'fecha_aceptacion'   => $fecha,
      'fecha_creacion'     => $fecha,
      'descripcion'        => 'Entrada generada automáticamente en Producción de soluciones'
    );

    $res = $this->compras_ordenes_model->agregarData($data);
    $id_orden = $res['id_orden'];

    $rows_compras = 0;
    $compra_prods = [];
    $presenta = $this->db->query("SELECT id_presentacion FROM productos_presentaciones WHERE status = 'ac' AND id_producto = {$produto} AND cantidad = 1 LIMIT 1")->row();
    $compra_prods[] = array(
      'id_orden'         => $id_orden,
      'num_row'          => $rows_compras,
      'id_producto'      => $_POST['id_prod_producir'],
      'id_presentacion'  => (count($presenta)>0? $presenta->id_presentacion: NULL),
      'descripcion'      => $_POST['prod_producir'],
      'cantidad'         => abs($_POST['cantidad_produccion']),
      'precio_unitario'  => ($_POST['costo']/($_POST['cantidad_produccion']>0? $_POST['cantidad_produccion']: 1)),
      'importe'          => $_POST['costo'],
      'status'           => 'a',
      'fecha_aceptacion' => $fecha,
    );
    $this->compras_ordenes_model->agregarProductosData($compra_prods);


    // Se registra para indicar la producción
    $data = array(
      'id_orden'  => $id_orden,
      'id_salida' => $id_salida,
      'folio'     => $this->getFolioNext(),
    );
    $this->db->insert('compras_salidas_produccion', $data);

    return array('passes' => true, 'msg' => 3, 'id_salida' => $id_salida, 'id_orden' => $id_orden);
  }

  public function getFolioNext()
  {
    $folio = $this->db->query("SELECT folio FROM compras_salidas_produccion ORDER BY folio DESC LIMIT 1")->row();
    if (isset($folio->folio)) {
      $next = $folio->folio+1;
    } else {
      $next = 1;
    }
    return $next;
  }

  public function info($id_salida, $id_orden)
  {
    $query = $this->db->query("SELECT * FROM compras_salidas_produccion WHERE id_salida = {$id_salida} AND id_orden = {$id_orden}");
    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->row();

      $this->load->model('compras_ordenes_model');
      $data['info']->orden = $this->compras_ordenes_model->info($id_orden, true)['info'][0];

      $this->load->model('productos_salidas_model');
      $data['info']->salida = $this->productos_salidas_model->info($id_salida, true)['info'][0];
    }

    return $data;
  }

  public function cancelar($id_salida, $id_orden)
  {
    $this->load->model('compras_ordenes_model');
    $this->compras_ordenes_model->cancelar($id_orden);

    $this->load->model('productos_salidas_model');
    $this->productos_salidas_model->cancelar($id_salida);

    return array('passes' => true);
  }


  public function imprimir_ticket($id_salida, $id_orden, $path = null)
  {
    $orden = $this->info($id_salida, $id_orden);
    // echo "<pre>";
    //   var_dump($orden['info']);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 130));
    $pdf->show_head = false;
    $pdf->AddPage();
    $pdf->AddFont($pdf->fount_num, '');

    $tituloo = 'PRODUCCION DE SOLUCION';

    // Título
    $pdf->SetFont($pdf->fount_txt, 'B', 8.5);
    $pdf->SetXY(0, 3);
    $pdf->MultiCell($pdf->pag_size[0], 4, $tituloo, 0, 'C');
    $pdf->SetFont($pdf->fount_txt, '', 8);
    $pdf->SetX(0);
    $pdf->MultiCell($pdf->pag_size[0], 4, $orden['info']->orden->empresa, 0, 'C');
    $pdf->SetFont($pdf->fount_txt, '', 7);

    $pdf->SetWidths(array(10, 20, 11, 20));
    $pdf->SetAligns(array('L','L', 'R', 'R'));
    $pdf->SetFounts(array($pdf->fount_txt));
    $pdf->SetX(0);
    $pdf->Row2(array('Folio: ', $orden['info']->folio, 'Fecha: ', MyString::fechaAT( substr($orden['info']->orden->fecha, 0, 10) )), false, false, 5);

    $pdf->SetWidths(array(32, 32));
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Almacen: '.$orden['info']->orden->almacen, 'Fecha A: '.MyString::fechaAT($orden['info']->orden->fecha) ), false, false);
    // if (isset($orden['info'][0]->traspaso)) {
    //   $pdf->SetXY(0, $pdf->GetY()-2);
    //   $pdf->Row2(array('Traspaso: '.$orden['info'][0]->traspaso->almacen, 'Fecha: '.MyString::fechaAT($orden['info'][0]->traspaso->fecha) ), false, false);
    // }
    $pdf->SetWidths(array(65));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Producto: '.$orden['info']->orden->productos[0]->producto ), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Cantidad: '.$orden['info']->orden->productos[0]->cantidad ), false, false);

    $pdf->SetFont($pdf->fount_txt, '', 7);
    $pdf->SetX(0);
    $pdf->MultiCell($pdf->pag_size[0], 2, '--------------------------------------------------------------------------', 0, 'L');
    $pdf->SetFont($pdf->fount_txt, '', $pdf->font_size-1);

    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Almacen: '.$orden['info']->salida->almacen ), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Folio Salida: '.$orden['info']->salida->folio ), false, false);

    $pdf->SetWidths(array(10, 28, 11, 14));
    $pdf->SetAligns(array('L','L','R','R'));
    $pdf->SetFounts(array($pdf->fount_txt), array(-1,-2,-2,-2));
    $pdf->SetX(0);
    $pdf->Row2(array('CANT.', 'DESCRIPCION', 'P.U.', 'IMPORTE'), false, true, 5);

    $pdf->SetFounts(array($pdf->fount_num,$pdf->fount_txt,$pdf->fount_num,$pdf->fount_num),
                   array(0,-1.5,-1.3,-1.2));
    $subtotal = $iva = $total = $retencion = $ieps = 0;
    $tipoCambio = 0;
    foreach ($orden['info']->salida->productos as $key => $prod) {
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array(
        $prod->cantidad.' '.$prod->abreviatura,
        $prod->producto,
        MyString::formatoNumero($prod->precio_unitario, 2, '', true),
        MyString::formatoNumero(($prod->precio_unitario*$prod->cantidad), 2, '', true),), false, false);

      $total += floatval($prod->precio_unitario*$prod->cantidad);
    }

    // $pdf->SetX(29);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(13, 20));
    // $pdf->SetX(29);
    // $pdf->Row(array('TOTAL', MyString::formatoNumero($total, 2, '$', false)), false, true);
    $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_num), array(-1,-1));
    $pdf->SetX(30);
    $pdf->Row2(array('COSTO', MyString::formatoNumero($total, 2, '', true)), false, true, 5);

    $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_num), array(-1,-1));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(66, 0));
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row2(array('REGISTRO: '.strtoupper($orden['info']->orden->empleado), '' ), false, false);
    // $pdf->SetXY(0, $pdf->GetY()-2);
    // $pdf->Row2(array('SOLICITA: '.strtoupper($orden['info'][0]->solicito)), false, false);
    // $pdf->SetXY(0, $pdf->GetY()-2);
    // $pdf->Row2(array('RECIBE: '.strtoupper($orden['info'][0]->recibio)), false, false);

    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row2(array('_____________________________________________'), false, false);

    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Expedido el: '.MyString::fechaAT(date("Y-m-d"))), false, false);

    $pdf->SetX(0);
    $pdf->Row(array( 'Impresión '.($orden['info']->no_impresiones_tk==0? 'ORIGINAL': 'COPIA '.$orden['info']->no_impresiones_tk)), false, false);
    $pdf->Line(0, $pdf->GetY()-1, 62, $pdf->GetY()-1);

    $this->db->update('compras_salidas_produccion', ['no_impresiones_tk' => $orden['info']->no_impresiones_tk+1], "id_salida = {$orden['info']->id_salida} AND id_orden = {$orden['info']->id_orden}");

    $pdf->AutoPrint(true);
    $pdf->Output();
  }

}