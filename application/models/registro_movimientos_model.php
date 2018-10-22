<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class registro_movimientos_model extends CI_Model {

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
      $sql = " AND Date(p.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffecha1') != '')
      $sql = " AND Date(p.fecha) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffecha2') != '')
      $sql = " AND Date(p.fecha) = '".$this->input->get('ffecha2')."'";


    if($this->input->get('ffolio') != '')
    {
      $sql .= " AND p.folio = '".$this->input->get('ffolio')."'";
    }

    if($this->input->get('did_empresa') != '')
    {
      $sql .= " AND e.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if ($this->input->get('fstatus') != '')
    {
      $sql .= " AND p.status = '".$this->input->get('fstatus')."'";
    }

    $query = BDUtil::pagination(
        "SELECT p.id, p.id_empresa, e.nombre_fiscal AS empresa,
                p.folio, p.fecha, p.concepto, p.status
        FROM otros.polizas AS p
        INNER JOIN empresas AS e ON e.id_empresa = p.id_empresa
        WHERE 1 = 1 {$sql}
        ORDER BY p.fecha DESC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'polizas'        => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['polizas'] = $res->result();

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
        'id_empresa'        => $_POST['empresaId'],
        'id_empleado'       => $this->session->userdata('id_usuario'),
        'folio'             => $_POST['folio'],
        'fecha_creacion'    => str_replace('T', ' ', $_POST['fecha']),
        'fecha_registro'    => date("Y-m-d H:i:s"),
        // 'concepto'       => '', //$_POST['conceptoSalida']
        'status'            => 's',
        'solicito'          => $_POST['solicito'],
        'recibio'           => $_POST['recibio'],
        'id_almacen'        => $_POST['id_almacen'],
        // 'id_traspaso'    => intval($this->input->post('tid_almacen')),
        'no_receta'         => $this->input->post('no_receta')? $_POST['no_receta']: NULL,
        'etapa'             => $this->input->post('etapa')? $_POST['etapa']: NULL,
        'rancho'            => $this->input->post('ranchoC_id')? $_POST['ranchoC_id']: NULL,
        'centro_costo'      => $this->input->post('centro_costo_id')? $_POST['centro_costo_id']: NULL,
        'hectareas'         => $this->input->post('hectareas')? $_POST['hectareas']: NULL,
        'grupo'             => $this->input->post('grupo')? $_POST['grupo']: NULL,
        'no_secciones'      => $this->input->post('no_secciones')? $_POST['no_secciones']: NULL,
        'dias_despues_de'   => $this->input->post('dias_despues_de')? $_POST['dias_despues_de']: NULL,
        'metodo_aplicacion' => $this->input->post('metodo_aplicacion')? $_POST['metodo_aplicacion']: NULL,
        'ciclo'             => $this->input->post('ciclo')? $_POST['ciclo']: NULL,
        'tipo_aplicacion'   => $this->input->post('tipo_aplicacion')? $_POST['tipo_aplicacion']: NULL,
        'observaciones'     => $this->input->post('observaciones')? $_POST['observaciones']: NULL,
        'fecha_aplicacion'  => $this->input->post('fecha_aplicacion')? $_POST['fecha_aplicacion']: NULL,

        'id_area'           => ($this->input->post('areaId')? $_POST['areaId']: NULL),
        'id_rancho'         => ($this->input->post('ranchoId')? $_POST['ranchoId']: NULL),
        'id_centro_costo'   => ($this->input->post('centroCostoId')? $_POST['centroCostoId']: NULL),
        'id_activo'         => ($this->input->post('activoId')? $_POST['activoId']: NULL)
      );

      if (isset($_POST['fid_trabajador']{0})) {
        $data['id_usuario'] = $_POST['fid_trabajador'];
      }
    }

    $this->db->insert('compras_salidas', $data);

    return array('passes' => true, 'msg' => 3, 'id_salida' => $this->db->insert_id());
  }

  public function modificar($id_salida, $data = null)
  {
    if ( ! $data)
    {
      $data = array(
        'id_area'           => ($this->input->post('areaId')? $_POST['areaId']: NULL),
        'id_rancho'         => ($this->input->post('ranchoId')? $_POST['ranchoId']: NULL),
        'id_centro_costo'   => ($this->input->post('centroCostoId')? $_POST['centroCostoId']: NULL),
        'id_activo'         => ($this->input->post('activoId')? $_POST['activoId']: NULL)
      );
    }

    $this->db->update('compras_salidas', $data, ['id_salida' => $id_salida]);

    return array('passes' => true, 'msg' => 3, 'id_salida' => $id_salida);
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
          'id_salida'                    => $idSalida,
          'id_producto'                  => $_POST['productoId'][$key],
          'no_row'                       => $key,
          'cantidad'                     => $_POST['cantidad'][$key],
          'precio_unitario'              => $saldo,
          // 'id_area'                   => $_POST['codigoAreaId'][$key],
          // $_POST['codigoCampo'][$key] => $_POST['codigoAreaId'][$key] !== '' ? $_POST['codigoAreaId'][$key] : null,
          'id_cat_codigos'               => $this->input->post('centro_costo_id')? $_POST['centro_costo_id']: NULL,
          'tipo_orden'                   => $_POST['tipoProducto'][$key],
        );
      }
    }

    $this->db->insert_batch('compras_salidas_productos', $productos);

    // si es transferencia de almacenes
    if ($this->input->post('tid_almacen') > 0) {
      $this->load->model('compras_ordenes_model');

      $fecha = date("Y-m-d H:i:s");
      $rows_compras = 0;
      $proveedor = $this->db->query("SELECT id_proveedor FROM proveedores WHERE UPPER(nombre_fiscal)='FICTICIO' LIMIT 1")->row();
      $departamento = $this->db->query("SELECT id_departamento FROM compras_departamentos WHERE UPPER(nombre)='FICTICIO' LIMIT 1")->row();
      $data = array(
        'id_empresa'         => $_POST['empresaId'],
        'id_proveedor'       => $proveedor->id_proveedor,
        'id_departamento'    => $departamento->id_departamento,
        'id_empleado'        => $this->session->userdata('id_usuario'),
        'folio'              => $this->compras_ordenes_model->folio('t'),
        'tipo_orden'         => 't',
        'status'             => 'n',
        'autorizado'         => 't',
        'fecha_autorizacion' => $fecha,
        'fecha_aceptacion'   => $fecha,
        'fecha_creacion'     => $fecha,
        'id_almacen'         => $this->input->post('tid_almacen')
      );

      $res = $this->compras_ordenes_model->agregarData($data);
      $id_orden = $res['id_orden'];
      $compra = array();
      foreach ($productos as $key => $produto)
      {
        $ultima_compra = $this->compras_ordenes_model->getUltimaCompra($produto['id_producto']);
        $precio_unitario = (isset($ultima_compra->precio_unitario)? $ultima_compra->precio_unitario: 0);
        $presenta = $this->db->query("SELECT p.nombre, pp.id_presentacion
            FROM productos p LEFT JOIN (
              SELECT * FROM productos_presentaciones WHERE status = 'ac' AND cantidad = 1
            ) pp ON p.id_producto = pp.id_producto
            WHERE p.id_producto = {$produto['id_producto']}")->row();
        $compra[] = array(
          'id_orden'         => $id_orden,
          'num_row'          => $rows_compras,
          'id_producto'      => $produto['id_producto'],
          'id_presentacion'  => ($presenta->id_presentacion>0? $presenta->id_presentacion: NULL),
          'descripcion'      => $presenta->nombre,
          'cantidad'         => abs($produto['cantidad']),
          'precio_unitario'  => $precio_unitario,
          'importe'          => (abs($produto['cantidad'])*$precio_unitario),
          'status'           => 'a',
          'fecha_aceptacion' => $fecha,
        );
        $rows_compras++;
      }
      $this->compras_ordenes_model->agregarProductosData($compra);

      // actualiza el campo traspaso, de la salida
      $this->db->update('compras_salidas',
        array('id_traspaso' => $id_orden),
        array('id_salida' => $idSalida));

      $this->db->insert('compras_transferencias', array('id_salida' => $idSalida, 'id_orden' => $id_orden));
    }

    $this->db->query("SELECT refreshallmaterializedviews();");

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

    $this->db->query("SELECT refreshallmaterializedviews();");

    return array('passes' => true, 'msg' => 5);
  }

  public function cancelar($idOrden)
  {
    $this->db->update('compras_salidas', array('status' => 'ca'), array('id_salida' => $idOrden));

    $orden = $this->db->query("SELECT id_orden FROM compras_transferencias WHERE id_salida = ".$idOrden)->row();
    $this->db->update('compras_ordenes', array('status' => 'ca'), array('id_orden' => $orden->id_orden));

    $this->db->query("SELECT refreshallmaterializedviews();");

    return array('passes' => true);
  }

  public function info($idSalida, $full = false)
  {
    $query = $this->db->query(
      "SELECT cs.id_salida,
              cs.id_empresa, e.nombre_fiscal AS empresa, e.logo, e.dia_inicia_semana,
              cs.id_empleado, (u.nombre || ' ' || u.apellido_paterno) AS empleado,
              cs.folio, cs.fecha_creacion AS fecha, cs.fecha_registro,
              cs.status, cs.concepto, cs.solicito, cs.recibio,
              cs.id_usuario, (t.nombre || ' ' || t.apellido_paterno) AS trabajador,
              cs.no_impresiones, cs.no_impresiones_tk, ca.nombre AS almacen, cs.id_traspaso,
              cs.no_receta, cs.etapa, cs.rancho, cs.centro_costo, cs.hectareas, cs.grupo,
              cs.no_secciones, cs.dias_despues_de, cs.metodo_aplicacion, cs.ciclo,
              cs.tipo_aplicacion, cs.observaciones, cs.fecha_aplicacion,
              ccr.nombre AS rancho_n, ccc.nombre AS centro_c,
              cs.id_area, cs.id_rancho, cs.id_centro_costo, cs.id_activo
        FROM compras_salidas AS cs
        INNER JOIN empresas AS e ON e.id_empresa = cs.id_empresa
        INNER JOIN usuarios AS u ON u.id = cs.id_empleado
        INNER JOIN compras_almacenes AS ca ON ca.id_almacen = cs.id_almacen
        LEFT JOIN usuarios AS t ON t.id = cs.id_usuario
        LEFT JOIN otros.cat_codigos ccr ON ccr.id_cat_codigos = cs.rancho
        LEFT JOIN otros.cat_codigos ccc ON ccc.id_cat_codigos = cs.centro_costo
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
                  COALESCE(cca.id_cat_codigos, ca.id_area) AS id_area,
                  COALESCE(cca.nombre, ca.nombre) AS nombre_codigo,
                  COALESCE((CASE WHEN cca.codigo <> '' THEN cca.codigo ELSE cca.nombre END), ca.codigo_fin) AS codigo_fin,
                  (CASE WHEN cca.id_cat_codigos IS NULL THEN 'id_area' ELSE 'id_cat_codigos' END) AS campo
           FROM compras_salidas_productos AS csp
             INNER JOIN productos AS pr ON pr.id_producto = csp.id_producto
             LEFT JOIN productos_unidades AS pu ON pu.id_unidad = pr.id_unidad
             LEFT JOIN compras_areas AS ca ON ca.id_area = csp.id_area
             LEFT JOIN otros.cat_codigos AS cca ON cca.id_cat_codigos = csp.id_cat_codigos
           WHERE csp.id_salida = {$data['info'][0]->id_salida}");

        $data['info'][0]->productos = array();
        if ($query->num_rows() > 0)
        {
          $data['info'][0]->productos = $query->result();
        }

        $data['info'][0]->area = null;
        if ($data['info'][0]->id_area)
        {
          $this->load->model('areas_model');
          $data['info'][0]->area = $this->areas_model->getAreaInfo($data['info'][0]->id_area, true)['info'];
        }

        $data['info'][0]->rancho = null;
        if ($data['info'][0]->id_rancho)
        {
          $this->load->model('ranchos_model');
          $data['info'][0]->rancho = $this->ranchos_model->getRanchoInfo($data['info'][0]->id_rancho, true)['info'];
        }

        $data['info'][0]->centroCosto = null;
        if ($data['info'][0]->id_centro_costo)
        {
          $this->load->model('centros_costos_model');
          $data['info'][0]->centroCosto = $this->centros_costos_model->getCentroCostoInfo($data['info'][0]->id_centro_costo, true)['info'];
        }

        $data['info'][0]->activo = null;
        if ($data['info'][0]->id_activo)
        {
          $this->load->model('productos_model');
          $data['info'][0]->activo = $this->productos_model->getProductosInfo($data['info'][0]->id_activo, true)['info'];
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
    $this->load->model('catalogos_sft_model');

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
      'No '.MyString::formatoNumero($orden['info'][0]->folio, 2, ''),
    ), false, false);
    // $pdf->SetFont('helvetica','', 8);
    // $pdf->SetX(6);
    // $pdf->Row(array(
    //   'PROVEEDOR: ' . $orden['info'][0]->empleado,
    //   MyString::fechaATexto($orden['info'][0]->fecha, '/c'),
    // ), false, false);

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
        MyString::formatoNumero($prod->precio_unitario, 2, '$', false),
        MyString::formatoNumero(($prod->precio_unitario*$prod->cantidad), 2, '$', false),
      );

      $pdf->SetX(6);
      $pdf->Row($datos, false);

      $total     += floatval($prod->precio_unitario*$prod->cantidad);

      if($prod->id_area != '' && !array_key_exists($prod->id_area, $codigoAreas))
        $codigoAreas[$prod->id_area] = $this->{($prod->campo=='id_area'? 'compras_areas_model': 'catalogos_sft_model')}->getDescripCodigo($prod->id_area);
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
    $pdf->SetXY(6, $pdf->GetY()-2);
    $pdf->Row(array('RECIBE: '.strtoupper($orden['info'][0]->recibio)), false, false);

    $pdf->SetXY(6, $pdf->GetY()+4);
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

    if ($orden['info'][0]->trabajador != '') {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetWidths(array(155));
      $pdf->Row(array('Se asigno a: ' . $orden['info'][0]->trabajador), false, false);
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

    $pdf->SetX(6);
    $pdf->SetWidths(array(100, 100));
    $pdf->Row(array( 'Impresión '.($orden['info'][0]->no_impresiones==0? 'ORIGINAL': 'COPIA '.$orden['info'][0]->no_impresiones),
                    'Almacen: '.$orden['info'][0]->almacen.($orden['info'][0]->id_traspaso>0? ' | Traspaso de almacen': '') ), false, false);

    //Totales
    $pdf->SetFont('Arial','',8);
    $pdf->SetXY(160, $yy);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(25, 25));
    $pdf->SetX(160);
    $pdf->Row(array('TOTAL', MyString::formatoNumero($total, 2, '$', false)), false, true);

    $this->db->update('compras_salidas', ['no_impresiones' => $orden['info'][0]->no_impresiones+1], "id_salida = ".$orden['info'][0]->id_salida);

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

  public function imprimir_salidaticket($salidaID, $path = null)
  {
    $this->load->model('compras_areas_model');
    $this->load->model('catalogos_sft_model');

    $orden = $this->info($salidaID, true);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 130));
    $pdf->show_head = false;
    $pdf->AddPage();
    $pdf->AddFont($pdf->fount_num, '');

    // Título
    $pdf->SetFont($pdf->fount_txt, 'B', 8.5);
    $pdf->SetXY(0, 3);
    $pdf->MultiCell($pdf->pag_size[0], 4, 'SALIDA DE PRODUCTOS'.($orden['info'][0]->id_traspaso>0? '(Traspaso)': ''), 0, 'C');
    $pdf->SetFont($pdf->fount_txt, '', 8);
    $pdf->SetX(0);
    $pdf->MultiCell($pdf->pag_size[0], 4, $pdf->titulo1, 0, 'C');
    $pdf->SetFont($pdf->fount_txt, '', 7);
    $pdf->SetX(0);
    $pdf->MultiCell($pdf->pag_size[0], 4, $pdf->reg_fed, 0, 'C');

    $pdf->SetWidths(array(10, 20, 11, 20));
    $pdf->SetAligns(array('L','L', 'R', 'R'));
    $pdf->SetFounts(array($pdf->fount_txt));
    $pdf->SetX(0);
    $pdf->Row2(array('Folio: ', $orden['info'][0]->folio, 'Fecha: ', MyString::fechaAT( substr($orden['info'][0]->fecha, 0, 10) )), false, false, 5);

    $semana = MyString::obtenerSemanaDeFecha(substr($orden['info'][0]->fecha, 0, 10), $orden['info'][0]->dia_inicia_semana);

    $pdf->SetWidths(array(32, 32));
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('No Receta: '.$orden['info'][0]->no_receta, 'Semana: '.$semana['semana'] ), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Etapa: '.$orden['info'][0]->etapa, 'Rancho: '.$orden['info'][0]->rancho_n ), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('CC: '.$orden['info'][0]->centro_c, 'Hectareas: '.$orden['info'][0]->hectareas ), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Grupo: '.$orden['info'][0]->grupo, 'No melgas: '.$orden['info'][0]->no_secciones ), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('DD FS: '.$orden['info'][0]->dias_despues_de, 'Metodo A: '.$orden['info'][0]->metodo_aplicacion ), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Ciclo: '.$orden['info'][0]->ciclo, 'Tipo A: '.$orden['info'][0]->tipo_aplicacion ), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Almacen: '.$orden['info'][0]->almacen, 'Fecha A: '.MyString::fechaAT($orden['info'][0]->fecha_aplicacion) ), false, false);
    $pdf->SetWidths(array(65));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Observaciones: '.$orden['info'][0]->observaciones ), false, false);

    $pdf->SetFont($pdf->fount_txt, '', 7);
    $pdf->SetX(0);
    $pdf->MultiCell($pdf->pag_size[0], 2, '--------------------------------------------------------------------------', 0, 'L');
    $pdf->SetFont($pdf->fount_txt, '', $pdf->font_size-1);

    $pdf->SetWidths(array(10, 28, 11, 14));
    $pdf->SetAligns(array('L','L','R','R'));
    $pdf->SetFounts(array($pdf->fount_txt), array(-1,-2,-2,-2));
    $pdf->SetX(0);
    $pdf->Row2(array('CANT.', 'DESCRIPCION', 'P.U.', 'IMPORTE'), false, true, 5);

    $pdf->SetFounts(array($pdf->fount_num,$pdf->fount_txt,$pdf->fount_num,$pdf->fount_num),
                   array(0,-1.5,-1.3,-1.2));
    $subtotal = $iva = $total = $retencion = $ieps = 0;
    $tipoCambio = 0;
    $codigoAreas = array();
    foreach ($orden['info'][0]->productos as $key => $prod) {
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array(
        $prod->cantidad.' '.$prod->abreviatura,
        $prod->producto,
        MyString::formatoNumero($prod->precio_unitario, 2, '', true),
        MyString::formatoNumero(($prod->precio_unitario*$prod->cantidad), 2, '', true),), false, false);

      $total += floatval($prod->precio_unitario*$prod->cantidad);

      if($prod->id_area != '' && !array_key_exists($prod->id_area, $codigoAreas))
        $codigoAreas[$prod->id_area] = $this->{($prod->campo=='id_area'? 'compras_areas_model': 'catalogos_sft_model')}->getDescripCodigo($prod->id_area);
      $this->load->model('catalogos_sft_model');
    }

    // $pdf->SetX(29);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(13, 20));
    // $pdf->SetX(29);
    // $pdf->Row(array('TOTAL', MyString::formatoNumero($total, 2, '$', false)), false, true);
    $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_num), array(-1,-1));
    $pdf->SetX(30);
    $pdf->Row2(array('TOTAL', MyString::formatoNumero($total, 2, '', true)), false, true, 5);

    if ($orden['info'][0]->concepto != '') {
      $pdf->SetFounts(array($pdf->fount_txt), array(-1));
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(66));
      $pdf->SetXY(0, $pdf->GetY());
      $pdf->Row2(array($orden['info'][0]->concepto), false, false);
    }

    $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_num), array(-1,-1));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(66, 0));
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row2(array('REGISTRO: '.strtoupper($orden['info'][0]->empleado), '' ), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('SOLICITA: '.strtoupper($orden['info'][0]->solicito)), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('RECIBE: '.strtoupper($orden['info'][0]->recibio)), false, false);

    $pdf->SetXY(0, $pdf->GetY()+3);
    $pdf->Row2(array('_____________________________________________'), false, false);
    $yy2 = $pdf->GetY();
    if(count($codigoAreas) > 0){
      $yy2 = $pdf->GetY();
      $pdf->SetXY(0, $pdf->GetY());
      $pdf->Row2(array('COD/AREA: ' . implode(' - ', $codigoAreas)), false, false);
    }

    if ($orden['info'][0]->trabajador != '') {
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('Se asigno a: '.strtoupper($orden['info'][0]->trabajador)), false, false);
    }

    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array('Expedido el: '.MyString::fechaAT(date("Y-m-d"))), false, false);

    $pdf->SetX(0);
    $pdf->Row(array( 'Impresión '.($orden['info'][0]->no_impresiones_tk==0? 'ORIGINAL': 'COPIA '.$orden['info'][0]->no_impresiones_tk)), false, false);
    $pdf->Line(0, $pdf->GetY()-1, 62, $pdf->GetY()-1);

    $this->db->update('compras_salidas', ['no_impresiones_tk' => $orden['info'][0]->no_impresiones_tk+1], "id_salida = ".$orden['info'][0]->id_salida);

    $pdf->AutoPrint(true);
    $pdf->Output();
  }


  /**
   * Reportes
   *******************************
   * @return void
   */
  public function getDataGastos()
  {
    $this->load->model('compras_areas_model');
    $sql_compras = $sql_caja = $sql = $sql2 = '';

    //Filtro de fecha.
    if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '') {
      $sql_caja .= " AND Date(cg.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
      $sql_compras .= " AND Date(cp.fecha_aceptacion) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    }
    elseif($this->input->get('ffecha1') != '') {
      $sql_caja .= " AND Date(cg.fecha) = '".$this->input->get('ffecha1')."'";
      $sql_compras .= " AND Date(cp.fecha_aceptacion) = '".$this->input->get('ffecha1')."'";
      $sql .= " AND Date(csc.fecha) = '".$this->input->get('ffecha1')."'";
    }
    elseif($this->input->get('ffecha2') != ''){
      $sql_caja .= " AND Date(cg.fecha) = '".$this->input->get('ffecha2')."'";
      $sql_compras .= " AND Date(cp.fecha_aceptacion) = '".$this->input->get('ffecha2')."'";
      $sql .= " AND Date(csc.fecha) = '".$this->input->get('ffecha2')."'";
    }

    $sql2 = $sql;

    // vehiculos

    $response = array();


    if (isset($_GET['dareas']) && count($_GET['dareas']) > 0)
    {
      foreach ($_GET['dareas'] as $key => $value) {
        $ids_hijos = $value.$this->compras_areas_model->getHijos($value);
        $result = $this->db->query("SELECT ca.nombre, (
            SELECT Sum(importe) importe
            FROM (
              SELECT Sum(cp.total) importe
              FROM compras_productos cp INNER JOIN compras_ordenes co ON co.id_orden = cp.id_orden
              WHERE cp.id_area In({$ids_hijos}) {$sql_compras} AND cp.status = 'a' AND co.status <> 'ca'
              UNION
              SELECT Sum(cg.monto) importe
              FROM cajachica_gastos cg
              WHERE cg.id_area In({$ids_hijos}) {$sql_caja}
            ) t
          ) importe
          FROM compras_areas ca
          WHERE ca.id_area = {$value}");
        $response[] = $result->row();
        $result->free_result();

        // $result = $this->db->query("SELECT ca.nombre, COALESCE(
        //                               (SELECT (Sum(csp.cantidad * csp.precio_unitario)) AS importe
        //                               FROM compras_salidas_productos csp
        //                               WHERE csp.id_area In({$ids_hijos}))
        //                             , 0) AS importe
        //                             FROM compras_areas ca
        //                             WHERE ca.id_area = {$value}");
        // $response[] = $result->row();
        // $result->free_result();

        // // Se obtienen los costos de nomina
        // $result = $this->db->query("SELECT Sum(importe) AS importe
        //                             FROM nomina_trabajos_dia
        //                             WHERE id_area In({$ids_hijos})")->row();
        // $response[count($response)-1]->importe += $result->importe;

        // // Se obtienen los costos de los gastos de caja chica
        // $result = $this->db->query("SELECT Sum(cg.monto) AS importe
        //                             FROM cajachica_gastos cg
        //                             WHERE cg.id_area In({$ids_hijos}) {$sql_caja}")->row();
        // $response[count($response)-1]->importe += $result->importe;


        if (isset($_GET['dmovimientos']{0}) && $_GET['dmovimientos'] == '1' && $response[count($response)-1]->importe == 0)
          array_pop($response);
        else {
          if (isset($_GET['ddesglosado']{0}) && $_GET['ddesglosado'] == '1') {
            $response[count($response)-1]->detalle = $this->db->query(
            "SELECT *
              FROM (
                SELECT
                  ca.id_area, ca.nombre, Date(cp.fecha_aceptacion) fecha_orden, co.folio::text folio_orden,
                  Date(c.fecha) fecha_compra, (c.serie || c.folio) folio_compra, cp.descripcion producto,
                  cp.total importe
                FROM compras_ordenes co
                  INNER JOIN compras_productos cp ON co.id_orden = cp.id_orden
                  INNER JOIN compras_areas ca ON ca.id_area = cp.id_area
                  LEFT JOIN compras c ON c.id_compra = cp.id_compra
                WHERE ca.id_area In({$ids_hijos}) {$sql_compras}
                  AND cp.status = 'a' AND co.status <> 'ca'
                UNION
                SELECT ca.id_area, ca.nombre, Date(cg.fecha) fecha_orden, cg.folio::text folio_orden,
                  NULL fecha_compra, NULL folio_compra,
                  ('Caja #' || cg.no_caja || ' ' || cg.concepto) producto,
                  cg.monto AS importe
                FROM cajachica_gastos cg
                  INNER JOIN compras_areas ca ON ca.id_area = cg.id_area
                WHERE ca.id_area In({$ids_hijos}) {$sql_caja}
              ) t
              ORDER BY fecha_orden ASC")->result();

            // // Si es desglosado carga independientes de compras salidas
            // $response[count($response)-1]->detalle = $this->db->query(
            //     "SELECT ca.id_area, ca.nombre, Date(cs.fecha_creacion) AS fecha, cs.folio, p.nombre AS producto, (csp.cantidad * csp.precio_unitario) AS importe
            //     FROM compras_salidas cs
            //       INNER JOIN compras_salidas_productos csp ON cs.id_salida = csp.id_salida
            //       INNER JOIN compras_areas ca ON ca.id_area = csp.id_area
            //       INNER JOIN productos p ON p.id_producto = csp.id_producto
            //     WHERE ca.id_area In({$ids_hijos})
            //     ORDER BY nombre")->result();

            // // Si es desglosado carga los gastos de las nominas
            // $response[count($response)-1]->detalle = array_merge(
            //   $response[count($response)-1]->detalle,
            //   $this->db->query(
            //     "SELECT ca.id_area, ca.nombre, Date(cs.fecha) AS fecha, 'NOM' AS folio,
            //       (u.apellido_paterno || ' ' || u.apellido_materno || ' ' || u.nombre || ' - ' ||
            //         (SELECT string_agg(css.nombre, ',') FROM nomina_trabajos_dia_labores nt
            //           INNER JOIN compras_salidas_labores css ON css.id_labor = nt.id_labor
            //           WHERE nt.id_area = ca.id_area AND nt.fecha = cs.fecha AND nt.id_usuario = u.id)) AS producto, cs.importe
            //     FROM nomina_trabajos_dia cs
            //       INNER JOIN usuarios u ON cs.id_usuario = u.id
            //       INNER JOIN compras_areas ca ON ca.id_area = cs.id_area
            //     WHERE ca.id_area In({$ids_hijos})
            //     ORDER BY nombre")->result()
            // );

            // // Si es desglosado carga los gastos de caja chica
            // $response[count($response)-1]->detalle = array_merge(
            //   $response[count($response)-1]->detalle,
            //   $this->db->query(
            //     "SELECT ca.id_area, ca.nombre, Date(cg.fecha) AS fecha, cg.folio AS folio,
            //       ('Caja #' || cg.no_caja || ' ' || cg.concepto) AS producto, cg.monto AS importe
            //     FROM cajachica_gastos cg
            //       INNER JOIN compras_areas ca ON ca.id_area = cg.id_area
            //     WHERE ca.id_area In({$ids_hijos}) {$sql_caja}
            //     ORDER BY nombre")->result()
            // );
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
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1'])." al ".MyString::fechaAT($_GET['ffecha2'])."";
    elseif (!empty($_GET['ffecha1']))
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1']);
    elseif (!empty($_GET['ffecha2']))
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha2']);

    $pdf->AliasNbPages();
    // $links = array('', '', '', '');
    $pdf->SetY(30);
    $aligns = array('L', 'R');
    $widths = array(170, 35);
    $header = array('Nombre', 'Importe');
    $aligns2 = array('L', 'L', 'L', 'L', 'L', 'L', 'R', 'R');
    $widths2 = array(18, 18, 18, 18, 60, 45, 29);
    $header2 = array('Fecha O', 'Folio O', 'Fecha C', 'Folio C', 'C Costo', 'Producto', 'Importe');

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
        MyString::formatoNumero($vehiculo->importe, 2, '', false),
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
            MyString::fechaAT($item->fecha_orden),
            $item->folio_orden,
            MyString::fechaAT($item->fecha_compra),
            $item->folio_compra,
            $item->nombre,
            $item->producto,
            MyString::formatoNumero($item->importe, 2, '', false),
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
        MyString::formatoNumero($lts_combustible, 2, '', false) ),
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
          <td colspan="8" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="8" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="8" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="8"></td>
        </tr>
        <tr style="font-weight:bold">
          <td colspan="6" style="border:1px solid #000;background-color: #cccccc;">Nombre</td>
          <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">Importe</td>
        </tr>';
    if (isset($combustible[0]->detalle)) {
      $html .= '<tr style="font-weight:bold">
        <td></td>
        <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Fecha O</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio O</td>
        <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Fecha C</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio C</td>
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
          <td colspan="6" style="border:1px solid #000;background-color: #cccccc;">'.$vehiculo->nombre.'</td>
          <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">'.$vehiculo->importe.'</td>
        </tr>';
      if (isset($vehiculo->detalle)) {
        foreach ($vehiculo->detalle as $key2 => $item)
        {
          $html .= '<tr>
              <td></td>
              <td style="width:100px;border:1px solid #000;background-color: #cccccc;">'.$item->fecha_orden.'</td>
              <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$item->folio_orden.'</td>
              <td style="width:100px;border:1px solid #000;background-color: #cccccc;">'.$item->fecha_compra.'</td>
              <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$item->folio_compra.'</td>
              <td style="width:400px;border:1px solid #000;background-color: #cccccc;">'.$item->nombre.'</td>
              <td style="width:400px;border:1px solid #000;background-color: #cccccc;">'.$item->producto.'</td>
              <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$item->importe.'</td>
            </tr>';
        }
      }

    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="6">TOTALES</td>
          <td colspan="2" style="border:1px solid #000;">'.$lts_combustible.'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

}