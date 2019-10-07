<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class recetas_formulas_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
    $this->load->model('bitacora_model');
  }

  /**
   * Recetas
   *
   * @return
   */
  public function getRecetas($perpage = '100', $autorizadas = true)
  {
    $sql = '';
    //paginacion
    $params = array(
        'result_items_per_page' => $perpage,
        'result_page'       => (isset($_GET['pag'])? $_GET['pag']: 0)
    );

    if($params['result_page'] % $params['result_items_per_page'] == 0)
      $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

    if($this->input->get('fbuscar') != '')
    {
      $sqlfolio = is_numeric($this->input->get('fbuscar'))? "f.folio = '".$this->input->get('fbuscar')."' OR ": '';
      $sql .= " AND ({$sqlfolio} f.nombre LIKE '%".$this->input->get('fbuscar')."%')";
    }

    if($this->input->get('ftipo') != '')
    {
      $sql .= " AND f.tipo = '".$this->input->get('ftipo')."'";
    }

    if($this->input->get('did_empresa') != '')
    {
      $sql .= "  AND f.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if($this->input->get('did_area') != '')
    {
      $sql .= " AND f.id_area = '".$this->input->get('did_area')."'";
    }

    if($this->input->get('fstatus') != '')
    {
      $sql .= " AND f.status = '".$this->input->get('fstatus')."'";
    }

    $query = BDUtil::pagination(
        "SELECT f.id_formula, f.id_empresa, f.id_area, a.nombre AS area, f.nombre, f.folio, f.tipo, f.status
        FROM otros.formulas f
          INNER JOIN areas a ON a.id_area = f.id_area
        WHERE 1 = 1 {$sql}
        ORDER BY folio DESC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'formulas'       => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['formulas'] = $res->result();

    return $response;
  }

  /**
   * Recetas agregar
   *
   * @return array
   */
  public function agregar()
  {
    $data = array(
      'id_empresa' => $_POST['empresaId'],
      'id_area'    => $_POST['areaId'],
      'folio'      => $_POST['folio'],
      'nombre'     => $_POST['nombre'],
      'tipo'       => $_POST['tipo'],
    );

    $this->db->insert('otros.formulas', $data);
    $formulaId = $this->db->insert_id();

    $productos = array();
    foreach ($_POST['concepto'] as $key => $concepto)
    {
      $productos[] = array(
        'id_formula'   => $formulaId,
        'id_producto'  => $_POST['productoId'][$key],
        'rows'         => $key,
        'dosis_mezcla' => $_POST['cantidad'][$key],
        'percent'      => $_POST['percent'][$key],
      );
    }

    if(count($productos) > 0)
      $this->db->insert_batch('otros.formulas_productos', $productos);

    return array('passes' => true, 'msg' => 3);
  }

  /**
   * Actualiza los datos de una orden de compra junton con sus productos.
   *
   * @param  string $idOrden
   * @param  mixed $orden
   * @param  mixed $productos
   * @return array
   */
  public function actualizar($formulaId)
  {
    $data = array(
      'id_empresa' => $_POST['empresaId'],
      'id_area'    => $_POST['areaId'],
      'folio'      => $_POST['folio'],
      'nombre'     => $_POST['nombre'],
      'tipo'       => $_POST['tipo'],
    );

    $this->db->update('otros.formulas', $data, "id_formula = {$formulaId}");

    $productos = array();
    $this->db->delete('otros.formulas_productos', "id_formula = {$formulaId}");
    foreach ($_POST['concepto'] as $key => $concepto)
    {
      $productos[] = array(
        'id_formula'   => $formulaId,
        'id_producto'  => $_POST['productoId'][$key],
        'rows'         => $key,
        'dosis_mezcla' => $_POST['cantidad'][$key],
        'percent'      => $_POST['percent'][$key],
      );
    }

    if(count($productos) > 0)
      $this->db->insert_batch('otros.formulas_productos', $productos);


    return array('passes' => true, 'msg' => 7);
  }


  public function cancelar($formulaId)
  {
    $data = array('status' => 'f');
    $this->db->update('otros.formulas', $data, "id_formula = {$formulaId}");

    return array('passes' => true);
  }

  public function activar($formulaId)
  {
    $data = array('status' => 't');
    $this->db->update('otros.formulas', $data, "id_formula = {$formulaId}");

    return array('passes' => true);
  }

  public function info($formulaId, $full = false, $prodAcep=false, $idCompra=NULL)
  {
    $query = $this->db->query(
      "SELECT f.id_formula, f.id_empresa, f.id_area, a.nombre AS area, f.nombre, f.folio, f.tipo, f.status
        FROM otros.formulas f
          INNER JOIN areas a ON a.id_area = f.id_area
        WHERE id_formula = {$formulaId}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->row();

      $query->free_result();
      if ($full)
      {
        $query = $this->db->query(
          "SELECT fp.id_formula, fp.rows, pr.id_producto, pr.nombre AS producto,
                  pr.codigo, pr.id_unidad, fp.dosis_mezcla, fp.percent
           FROM otros.formulas_productos AS fp
            INNER JOIN productos AS pr ON pr.id_producto = fp.id_producto
           WHERE fp.id_formula = {$data['info']->id_formula}
           ORDER BY fp.rows ASC");

        $data['info']->productos = $query->result();
        $query->free_result();

        $this->load->model('empresas_model');
        $data['info']->empresa = $this->empresas_model->getInfoEmpresa($data['info']->id_empresa)['info'];
      }
    }
    return $data;
  }

  public function folio($empresaId, $tipo = 'kg')
  {
    $res = $this->db->select('folio')
      ->from('otros.formulas')
      ->where('tipo', $tipo)
      ->where('id_empresa', $empresaId)
      ->order_by('folio', 'DESC')
      ->limit(1)->get()->row();

    $folio = (isset($res->folio) ? $res->folio : 0) + 1;

    return $folio;
  }

  /*
   |------------------------------------------------------------------------
   |
   |------------------------------------------------------------------------
   */

  public function getProductoAjax($idEmpresa = null, $tipo, $term, $def = 'codigo'){
    $sql = '';

    $this->load->model('inventario_model');
    $sqlEmpresa = "";
    if ($idEmpresa)
    {
      $sqlEmpresa = "p.id_empresa = {$idEmpresa} AND";
      $_GET['did_empresa'] = $idEmpresa;
    }

    $res = $this->db->query(
       "SELECT p.*,
              pf.nombre as familia, pf.codigo as codigo_familia,
              pu.nombre as unidad, pu.abreviatura as unidad_abreviatura,
              (SELECT precio_unitario FROM compras_productos WHERE id_producto = p.id_producto ORDER BY id_orden DESC LIMIT 1) AS precio_unitario
        FROM productos AS p
        INNER JOIN productos_familias pf ON pf.id_familia = p.id_familia
        INNER JOIN productos_unidades pu ON pu.id_unidad = p.id_unidad
        WHERE p.status = 'ac' AND
              {$term}
              {$sqlEmpresa}
              pf.tipo = '{$tipo}' AND
              pf.status = 'ac'
        ORDER BY p.nombre ASC
        LIMIT 20");

    $response = array();
    if($res->num_rows() > 0)
    {
      foreach($res->result() as $itm)
      {
        if(isset($_GET['did_empresa']{0}))
        {
          $_GET['fid_producto'] = $itm->id_producto;
          $itm->inventario = $this->inventario_model->getEPUData();
          $itm->inventario = isset($itm->inventario[0])? $itm->inventario[0]: false;
        }

        $query = $this->db->select('*')
          ->from("productos_presentaciones")
          ->where("id_producto", $itm->id_producto)
          ->where("status", "ac")
          ->get();

        $itm->presentaciones = array();
        if ($query->num_rows() > 0)
        {
          $itm->presentaciones = $query->result();
        }

        if ($def == 'codigo')
        {
          $labelValue = $itm->codigo;
        }
        else
        {
          $labelValue = $itm->nombre;
        }

        $response[] = array(
            'id' => $itm->id_producto,
            'label' => $labelValue,
            'value' => $labelValue,
            'item' => $itm,
        );
      }
    }

    return $response;
  }

  public function getProductoByCodigoAjax($idEmpresa, $tipo, $codigo)
  {
    $sql = '';

    $term = "lower(p.codigo) = '".mb_strtolower($codigo, 'UTF-8')."'";

    $res = $this->db->query(
       "SELECT p.*,
              pf.nombre as familia, pf.codigo as codigo_familia,
              pu.nombre as unidad, pu.abreviatura as unidad_abreviatura
        FROM productos as p
        INNER JOIN productos_familias pf ON pf.id_familia = p.id_familia
        INNER JOIN productos_unidades pu ON pu.id_unidad = p.id_unidad
        WHERE p.status = 'ac' AND
              {$term} AND
              p.id_empresa = {$idEmpresa} AND
              pf.tipo = '{$tipo}' AND
              pf.status = 'ac'
        ORDER BY p.nombre ASC
        LIMIT 20");

    $prod = array();
    if($res->num_rows() > 0)
    {
      $prod = $res->result();

      $query = $this->db->select('*')
        ->from("productos_presentaciones")
        ->where("id_producto", $prod[0]->id_producto)
        ->where("status", "ac")
        ->get();

      $prod[0]->presentaciones = array();
      if ($query->num_rows() > 0)
      {
        $prod[0]->presentaciones = $query->result();
      }
    }

    return $prod;
  }

  public function getFactRem($datos)
  {
    $tipo = $datos['tipo'] == 'f'? 't': 'f';
    $filtro = isset($datos['filtro']{0})? " AND f.folio = '{$datos['filtro']}'": '';
    $query = $this->db->query("SELECT f.id_factura, Date(f.fecha) AS fecha, f.serie, f.folio, f.is_factura, c.nombre_fiscal AS cliente
                               FROM facturacion AS f INNER JOIN clientes AS c ON c.id_cliente = f.id_cliente
                               WHERE c.id_cliente = {$datos['clienteId']} AND f.is_factura = '{$tipo}' AND f.status IN('p', 'pa') AND f.id_nc IS NULL
                                {$filtro} AND f.fecha >= (now() - interval '5 months')
                               ORDER BY f.fecha DESC, f.folio DESC");
    $response = array();
    if($query->num_rows() > 0)
      $response = $query->result();
    $query->free_result();
    return $response;
  }

  /*
   |------------------------------------------------------------------------
   | PDF's
   |------------------------------------------------------------------------
   */

  /**
    * Visualiza/Descarga el PDF de la orden de compra.
    *
    * @return void
    */
   public function print_orden_compra($ordenId, $path = null)
   {
      $orden = $this->info($ordenId, true);

      $this->load->model('compras_ordenes_model');
      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('L', 'mm', 'Letter');
      // $pdf->show_head = true;
      $pdf->titulo1 = $orden['info'][0]->empresa;

      $tipo_requisicion = count($orden['info'][0]->productos)>0? true: false; // requisicion, pre-requisicion

      $tipo_orden = $tipo_requisicion? 'ORDEN DE REQUISICION': 'PRE REQUISICION';


      $pdf->logo = $orden['info'][0]->logo!=''? (file_exists($orden['info'][0]->logo)? $orden['info'][0]->logo: '') : '';

      $pdf->AliasNbPages();
      $pdf->AddPage();

      $pdf->SetXY(6, $pdf->GetY()-10);

      $pdf->SetFont('helvetica','B', 10);
      $pdf->SetAligns(array('L', 'C', 'R'));
      $pdf->SetWidths(array(50, 160, 50));
      $pdf->Row(array(
        MyString::fechaATexto($orden['info'][0]->fecha, '/c'),
        $tipo_orden,
        'No '.MyString::formatoNumero($orden['info'][0]->folio, 2, ''),
      ), false, false);

      $yyy = $pdf->GetY();
      $pdf->SetFont('helvetica','B', 8);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(100));
      $pdf->Row(array('Modo de Facturación'), false, false);
      $pdf->SetFont('helvetica','', 8);
      $pdf->SetWidths(array(30, 50));
      $pdf->SetXY(6, $pdf->GetY()-1.5);
      $pdf->Row(array('Condiciones:', "Crédito"), false, false);
      $pdf->SetXY(6, $pdf->GetY()-1.5);
      $pdf->Row(array('Forma de Pago:', "99 (Por Definir)"), false, false);
      $pdf->SetXY(6, $pdf->GetY()-1.5);
      $pdf->Row(array('Método de Pago:', "PPD (Pago Parcialidades/Diferido)"), false, false);
      $pdf->SetXY(6, $pdf->GetY()-1.5);
      $pdf->Row(array('Uso del CFDI:', "G03 (Gastos en General)"), false, false);
      $pdf->SetXY(6, $pdf->GetY()-1.5);
      $pdf->Row(array('Almacén:', $orden['info'][0]->almacen), false, false);
      $yyy1 = $pdf->GetY();

      $pdf->SetXY(95, $yyy);
      $pdf->SetFont('helvetica','B', 8);
      $pdf->SetAligns(array('L', 'L', 'L'));
      $pdf->SetWidths(array(100));
      $pdf->Row(array('Requisitos para la Entrega de Mercancía'), false, false);
      $pdf->SetFont('helvetica','', 8);
      $pdf->SetXY(95, $pdf->GetY()-1.5);
      $pdf->Row(array('( '.(isset($orden['info'][0]->otros_datos->infPasarBascula)? 'Si': 'No').' ) Pasar a Bascula a pesar la mercancía y entregar Boleta a almacén.'), false, false);
      $pdf->SetXY(95, $pdf->GetY()-1.5);
      $pdf->Row(array('( '.(isset($orden['info'][0]->otros_datos->infEntOrdenCom)? 'Si': 'No').' ) Entregar la mercancía al almacenista, referenciando la presente Orden de Compra, así como anexarla a su Factura.'), false, false);

      $pdf->SetY($yyy1+2);

      $subtotal = $iva = $total = $retencion = 0;

      if ($tipo_requisicion) {
        $aligns = array('L', 'L', 'L', 'L', 'R', 'R', 'R', 'R', 'L', 'L');
        $widths2 = array(43, 43, 43);
        $widths = array(10, 20, 46, 65, 18, 25, 18, 25, 40);
        $header = array('PROD', 'CANT', 'PROVEEDOR', 'PRODUCTO', 'P.U.', 'IMPORTE', 'ULTIMA/COM', 'PRECIO', 'Activos');

        $orden['info'][0]->totales['subtotal']  = 0;
        $orden['info'][0]->totales['iva']       = 0;
        $orden['info'][0]->totales['ieps']      = 0;
        $orden['info'][0]->totales['total']     = 0;
        $orden['info'][0]->totales['retencion'] = 0;

        $tipoCambio = 0;
        $first = true;
        $pdf->SetXY(6, $pdf->GetY()+2);
        foreach ($orden['info'][0]->productos as $key => $prod)
        {
          $tipoCambio = 1;
          if ($prod->tipo_cambio != 0)
          {
            $tipoCambio = $prod->tipo_cambio;
          }

          $band_head = false;
          if($pdf->GetY() >= $pdf->limiteY || $first) { //salta de pagina si exede el max
            $first = false;
            if($pdf->GetY()+5 >= $pdf->limiteY)
              $pdf->AddPage();
            $pdf->SetFont('Arial','B',7);
            // $pdf->SetTextColor(255,255,255);
            // $pdf->SetFillColor(160,160,160);

            // $pdf->SetX(144);
            // $pdf->SetAligns($aligns);
            // $pdf->SetWidths($widths2);
            // $pdf->Row(array($orden['info'][0]->proveedores[0]['nombre_fiscal'],
            //                 $orden['info'][0]->proveedores[1]['nombre_fiscal'],
            //                 $orden['info'][0]->proveedores[2]['nombre_fiscal']), false);

            $pdf->SetX(6);
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->Row($header, false);
          }


          $precio_unitario1 = $prod->{'precio_unitario'.$prod->id_proveedor}/$tipoCambio*($prod->presen_cantidad>0?$prod->presen_cantidad:1);
          $activos = '';
          if (isset($prod->activos) && !empty($prod->activos)) {
            foreach ($prod->activos as $keya => $act) {
              $activos .= '-'.$act->text." \n";
            }
          }

          $ultimaCompra = $this->compras_ordenes_model->getUltimaCompra($prod->id_producto);

          $pdf->SetFont('Arial','',7);
          $pdf->SetTextColor(0,0,0);
          $datos = array(
            $prod->codigo,
            ($prod->cantidad/($prod->presen_cantidad>0?$prod->presen_cantidad:1)).''.($prod->presentacion==''? $prod->unidad: $prod->presentacion),
            $prod->proveedor,
            $prod->descripcion.($prod->observacion!=''? " ({$prod->observacion})": ''),
            MyString::formatoNumero($precio_unitario1, 2, '$', false),
            MyString::formatoNumero($prod->{'importe'.$prod->id_proveedor}/$tipoCambio, 2, '$', false),
            (isset($ultimaCompra->fecha_creacion)? substr($ultimaCompra->fecha_creacion, 0, 10): ''),
            (isset($ultimaCompra->precio_unitario)? MyString::formatoNumero($ultimaCompra->precio_unitario, 2, '$', false): ''),
            $activos,
          );

          $pdf->SetX(6);
          $pdf->SetWidths($widths);
          $pdf->Row($datos, false);

          $orden['info'][0]->totales['subtotal']  += floatval($prod->{'importe'.$prod->id_proveedor}/$tipoCambio);
          $orden['info'][0]->totales['iva']       += floatval($prod->{'iva'.$prod->id_proveedor}/$tipoCambio);
          $orden['info'][0]->totales['ieps']      += floatval($prod->{'ieps'.$prod->id_proveedor}/$tipoCambio);
          $orden['info'][0]->totales['total']     += floatval($prod->{'total'.$prod->id_proveedor}/$tipoCambio);
          $orden['info'][0]->totales['retencion'] += floatval($prod->{'retencion_iva'.$prod->id_proveedor}/$tipoCambio);
        }

        // Totales
        $pdf->SetFont('Arial','B',7);
        $pdf->SetX(82);
        $pdf->SetAligns(array('R', 'R', 'R', 'R'));
        $pdf->SetWidths(array(65, 43, 43, 43));
        $pdf->Row(array('SUB-TOTAL', MyString::formatoNumero($orden['info'][0]->totales['subtotal'], 2, '$', false)), false, true);
        $pdf->SetX(82);
        $pdf->Row(array('IVA', MyString::formatoNumero($orden['info'][0]->totales['iva'], 2, '$', false)), false, true);
        if ($orden['info'][0]->totales['ieps'] > 0)
        {
          $pdf->SetX(82);
          $pdf->Row(array('IEPS', MyString::formatoNumero($orden['info'][0]->totales['ieps'], 2, '$', false)), false, true);
        }
        if ($orden['info'][0]->totales['retencion'] > 0)
        {
          $pdf->SetX(82);
          $pdf->Row(array('Ret. IVA', MyString::formatoNumero($orden['info'][0]->totales['retencion'], 2, '$', false)), false, true);
        }
        $pdf->SetX(82);
        $pdf->Row(array('TOTAL', MyString::formatoNumero($orden['info'][0]->totales['total'], 2, '$', false)), false, true);
      }

      $pdf->SetAligns(array('L', 'R'));
      $pdf->SetWidths(array(60, 50));
      $pdf->SetXY(6, $pdf->GetY()-15);
      $pdf->Row(array(($tipoCambio>0? "TIPO DE CAMBIO: " . $tipoCambio : ($tipoCambio==''? 'TIPO DE CAMBIO: ': '')) ), false, false);

      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(75));
      $pdf->SetXY(6, $pdf->GetY()+6);
      $pdf->Row(array('SOLICITA: __________________________________________'), false, false);
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetAligns(array('C', 'L'));
      $pdf->Row(array(strtoupper($orden['info'][0]->empleado_solicito)), false, false);

      // $pdf->SetAligns(array('L', 'R'));
      // $pdf->SetWidths(array(104, 50));
      // $pdf->SetXY(6, $pdf->GetY());
      // $pdf->Row(array('REGISTRO: '.strtoupper($orden['info'][0]->empleado), ($tipoCambio>0? "TIPO DE CAMBIO: " . $tipoCambio : '') ), false, false);

      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(250));
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array('OBSERVACIONES: '.$orden['info'][0]->descripcion), false, false);
      $pdf->SetXY(6, $pdf->GetY()+4);

      // if ($tipo_requisicion) {
      //   $pdf->SetWidths(array(250));
      //   $pdf->SetXY(6, $pdf->GetY());
      //   $pdf->Row(array('DESCRIPCION DE CODIGOS: '.implode(', ', $orden['info'][0]->data_desCodigos)), false, false);
      // } else {
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(5, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(25, 80));
        $pdf->Row(array('EMPRESA', $orden['info'][0]->empresa), false, true);

        // El dato de la requisicion
        // if (!empty($orden['info'][0]->folio_requisicion)) {
        //   $pdf->SetFont('Arial','',8);
        //   $pdf->SetXY(5, $pdf->GetY());
        //   $pdf->SetAligns(array('L', 'L'));
        //   $pdf->SetWidths(array(25, 80));
        //   $pdf->Row(array('ENLACE', "Requisicion No {$orden['info'][0]->folio_requisicion}"), false, true);
        // }
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(5, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(25, 80));
        $pdf->Row(array('Cultivo / Actividad / Producto',
          (!empty($orden['info'][0]->area)? $orden['info'][0]->area->nombre: '')), false, true);

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(5, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(25, 80));
        $ranchos = [];
        foreach ($orden['info'][0]->rancho as $key => $value) {
          $ranchos[] = $value->nombre;
        }
        $pdf->Row(array('Areas / Ranchos / Lineas',
          (!empty($orden['info'][0]->rancho)? implode(' | ', $ranchos): '')), false, true);

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(5, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(25, 80));
        $centroCosto = [];
        foreach ($orden['info'][0]->centroCosto as $key => $value) {
          $centroCosto[] = $value->nombre;
        }
        $pdf->Row(array('Centro de costo',
          (!empty($orden['info'][0]->centroCosto)? implode(' | ', $centroCosto): '')), false, true);

        // $pdf->SetFont('Arial','',8);
        // $pdf->SetXY(5, $pdf->GetY());
        // $pdf->SetAligns(array('L', 'L'));
        // $pdf->SetWidths(array(25, 80));
        // $pdf->Row(array('Activo',
        //   (!empty($orden['info'][0]->activo)? $orden['info'][0]->activo->nombre: '')), false, true);
      // }


      if ($path)
      {
        $file = $path.'ORDEN_COMPRA_'.date('Y-m-d').'.pdf';
        $pdf->Output($file, 'F');
        return $file;
      }
      else
      {
        $pdf->Output('ORDEN_COMPRA_'.date('Y-m-d').'.pdf', 'I');
      }
   }

  public function print_pre_orden_compra($ordenId, $path = null)
  {
    $orden = $this->info($ordenId, true);
    $tipo_requisicion = count($orden['info'][0]->productos)>0? true: false; // requisicion, pre-requisicion
    if (!$tipo_requisicion && $orden['info'][0]->es_receta == 't') {
      return $this->print_pre_receta($orden, $path); // pre recetas
    } elseif ($tipo_requisicion) {
      return $this->print_orden_compra($ordenId, $path); // requisiciones con datos
    }
    // else imprime la pre orden requisición sin datos

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('L', 'mm', 'Letter');
    // $pdf->show_head = true;
    $pdf->titulo1 = $orden['info'][0]->empresa;

    $tipo_orden = $tipo_requisicion? 'ORDEN DE REQUISICION': 'PRE REQUISICION';


    $pdf->logo = $orden['info'][0]->logo!=''? (file_exists($orden['info'][0]->logo)? $orden['info'][0]->logo: '') : '';

    $pdf->AliasNbPages();
    $pdf->AddPage();

    $pdf->SetXY(6, $pdf->GetY()-10);

    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetAligns(array('L', 'C', 'R'));
    $pdf->SetWidths(array(50, 160, 50));
    $pdf->Row(array(
      MyString::fechaATexto($orden['info'][0]->fecha, '/c'),
      $tipo_orden,
      'No '.MyString::formatoNumero($orden['info'][0]->folio, 2, ''),
    ), false, false);

    $subtotal = $iva = $total = $retencion = 0;

    if ($tipo_requisicion) {
      $aligns = array('L', 'L', 'L', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'R');
      $widths2 = array(43, 43, 43);
      $widths = array(15, 10, 18, 30, 65, 18, 25, 18, 25, 18, 25);
      $header = array('AREA', 'PROD', 'CANT', 'UNIDAD', 'PRODUCTO', 'P.U.', 'IMPORTE', 'P.U.', 'IMPORTE', 'P.U.', 'IMPORTE');

      foreach ($orden['info'][0]->proveedores as $keypp => $value)
      {
        $orden['info'][0]->proveedores[$keypp]['subtotal']  = 0;
        $orden['info'][0]->proveedores[$keypp]['iva']       = 0;
        $orden['info'][0]->proveedores[$keypp]['ieps']      = 0;
        $orden['info'][0]->proveedores[$keypp]['total']     = 0;
        $orden['info'][0]->proveedores[$keypp]['retencion'] = 0;
      }

      $tipoCambio = 0;
      $first = true;
      $pdf->SetXY(6, $pdf->GetY()+2);
      foreach ($orden['info'][0]->productos as $key => $prod)
      {
        $tipoCambio = 1;
        if ($prod->tipo_cambio != 0)
        {
          $tipoCambio = $prod->tipo_cambio;
        }

        $band_head = false;
        if($pdf->GetY() >= $pdf->limiteY || $first) { //salta de pagina si exede el max
          $first = false;
          if($pdf->GetY()+5 >= $pdf->limiteY)
            $pdf->AddPage();
          $pdf->SetFont('Arial','B',7);
          // $pdf->SetTextColor(255,255,255);
          // $pdf->SetFillColor(160,160,160);

          $pdf->SetX(144);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths2);
          $pdf->Row(array($orden['info'][0]->proveedores[0]['nombre_fiscal'],
                          $orden['info'][0]->proveedores[1]['nombre_fiscal'],
                          $orden['info'][0]->proveedores[2]['nombre_fiscal']), false);

          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, false);
        }


        $precio_unitario1 = $prod->{'precio_unitario'.$orden['info'][0]->proveedores[0]['id_proveedor']}/$tipoCambio*($prod->presen_cantidad>0?$prod->presen_cantidad:1);
        $precio_unitario2 = $prod->{'precio_unitario'.$orden['info'][0]->proveedores[1]['id_proveedor']}/$tipoCambio*($prod->presen_cantidad>0?$prod->presen_cantidad:1);
        $precio_unitario3 = $prod->{'precio_unitario'.$orden['info'][0]->proveedores[2]['id_proveedor']}/$tipoCambio*($prod->presen_cantidad>0?$prod->presen_cantidad:1);

        $pdf->SetFont('Arial','',7);
        $pdf->SetTextColor(0,0,0);
        $datos = array(
          $prod->codigo_fin,
          $prod->codigo,
          ($prod->cantidad/($prod->presen_cantidad>0?$prod->presen_cantidad:1)),
          ($prod->presentacion==''? $prod->unidad: $prod->presentacion),
          $prod->descripcion.($prod->observacion!=''? " ({$prod->observacion})": ''),
          MyString::formatoNumero($precio_unitario1, 2, '$', false),
          MyString::formatoNumero($prod->{'importe'.$orden['info'][0]->proveedores[0]['id_proveedor']}/$tipoCambio, 2, '$', false),
          MyString::formatoNumero($precio_unitario2, 2, '$', false),
          MyString::formatoNumero($prod->{'importe'.$orden['info'][0]->proveedores[1]['id_proveedor']}/$tipoCambio, 2, '$', false),
          MyString::formatoNumero($precio_unitario3, 2, '$', false),
          MyString::formatoNumero($prod->{'importe'.$orden['info'][0]->proveedores[2]['id_proveedor']}/$tipoCambio, 2, '$', false),
        );

        $pdf->SetX(6);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false);

        foreach ($orden['info'][0]->proveedores as $keypp => $value)
        {
          $orden['info'][0]->proveedores[$keypp]['subtotal']  += floatval($prod->{'importe'.$orden['info'][0]->proveedores[$keypp]['id_proveedor']}/$tipoCambio);
          $orden['info'][0]->proveedores[$keypp]['iva']       += floatval($prod->{'iva'.$orden['info'][0]->proveedores[$keypp]['id_proveedor']}/$tipoCambio);
          $orden['info'][0]->proveedores[$keypp]['ieps']      += floatval($prod->{'ieps'.$orden['info'][0]->proveedores[$keypp]['id_proveedor']}/$tipoCambio);
          $orden['info'][0]->proveedores[$keypp]['total']     += floatval($prod->{'total'.$orden['info'][0]->proveedores[$keypp]['id_proveedor']}/$tipoCambio);
          $orden['info'][0]->proveedores[$keypp]['retencion'] += floatval($prod->{'retencion_iva'.$orden['info'][0]->proveedores[$keypp]['id_proveedor']}/$tipoCambio);
        }
      }

      // Totales
      $pdf->SetFont('Arial','B',7);
      $pdf->SetX(79);
      $pdf->SetAligns(array('R', 'R', 'R', 'R'));
      $pdf->SetWidths(array(65, 43, 43, 43));
      $pdf->Row(array('SUB-TOTAL', MyString::formatoNumero($orden['info'][0]->proveedores[0]['subtotal'], 2, '$', false),
                                  MyString::formatoNumero($orden['info'][0]->proveedores[1]['subtotal'], 2, '$', false),
                                  MyString::formatoNumero($orden['info'][0]->proveedores[2]['subtotal'], 2, '$', false)), false, true);
      $pdf->SetX(79);
      $pdf->Row(array('IVA', MyString::formatoNumero($orden['info'][0]->proveedores[0]['iva'], 2, '$', false),
                            MyString::formatoNumero($orden['info'][0]->proveedores[1]['iva'], 2, '$', false),
                            MyString::formatoNumero($orden['info'][0]->proveedores[2]['iva'], 2, '$', false)), false, true);
      if ($orden['info'][0]->proveedores[0]['ieps'] > 0)
      {
        $pdf->SetX(79);
        $pdf->Row(array('IEPS', MyString::formatoNumero($orden['info'][0]->proveedores[0]['ieps'], 2, '$', false),
                                    MyString::formatoNumero($orden['info'][0]->proveedores[1]['ieps'], 2, '$', false),
                                    MyString::formatoNumero($orden['info'][0]->proveedores[2]['ieps'], 2, '$', false)), false, true);
      }
      if ($orden['info'][0]->proveedores[0]['retencion'] > 0)
      {
        $pdf->SetX(79);
        $pdf->Row(array('Ret. IVA', MyString::formatoNumero($orden['info'][0]->proveedores[0]['retencion'], 2, '$', false),
                                    MyString::formatoNumero($orden['info'][0]->proveedores[1]['retencion'], 2, '$', false),
                                    MyString::formatoNumero($orden['info'][0]->proveedores[2]['retencion'], 2, '$', false)), false, true);
      }
      $pdf->SetX(79);
      $pdf->Row(array('TOTAL', MyString::formatoNumero($orden['info'][0]->proveedores[0]['total'], 2, '$', false),
                              MyString::formatoNumero($orden['info'][0]->proveedores[1]['total'], 2, '$', false),
                              MyString::formatoNumero($orden['info'][0]->proveedores[2]['total'], 2, '$', false)), false, true);
    } else { // *********** cuando es una pre requisición
      $aligns = array('L', 'L', 'L', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'R');
      $aligns2 = array('R', 'L', 'L', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'R');
      $widths2 = array(20, 51, 51, 51);
      $widths = array(18, 30, 65, 21, 30, 21, 30, 21, 30);
      $header = array('CANT', 'UNIDAD', 'PRODUCTO', 'P.U.', 'IMPORTE', 'P.U.', 'IMPORTE', 'P.U.', 'IMPORTE');

      for ($i=0; $i < 15; $i++) {
        if($pdf->GetY() >= $pdf->limiteY || $i === 0) { //salta de pagina si exede el max
          if($pdf->GetY()+5 >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetXY(6, $pdf->GetY()+3);
          $pdf->SetFont('Arial','B',7);
          // $pdf->SetTextColor(255,255,255);
          // $pdf->SetFillColor(160,160,160);

          $pdf->SetX(99);
          $pdf->SetAligns($aligns2);
          $pdf->SetWidths($widths2);
          $pdf->Row(array('Cotizaciones:', '', '', ''), false);
          $pdf->SetX(99);
          $pdf->Row(array('Proveedores:', '', '', ''), false, true, null, 6);

          $pdf->SetFont('Arial','',8);
          $pdf->SetXY(6, $pdf->GetY()-6);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(17, 70));
          $pdf->Row(array('Almacén:', $orden['info'][0]->almacen), false, false);

          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, false);
        }

        $pdf->SetFont('Arial','',7);
        $pdf->SetTextColor(0,0,0);
        $datos = array('','','','','','','','','');

        $pdf->SetX(6);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false);
      }

      // Totales
      $pdf->SetFont('Arial','B',7);
      $pdf->SetX(99);
      $pdf->SetAligns(array('R', 'R', 'R', 'R'));
      $pdf->SetWidths(array(20, 51, 51, 51));
      $pdf->Row(array('SUB-TOTAL', '', '', ''), false, true);
      $pdf->SetX(99);
      $pdf->Row(array('IVA', '', '', ''), false, true);
      $pdf->SetX(99);
      $pdf->Row(array('TOTAL', '', '', ''), false, true);

      $tipoCambio = '';
    }

    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(60, 50));
    $pdf->SetXY(6, $pdf->GetY()-15);
    $pdf->Row(array(($tipoCambio>0? "TIPO DE CAMBIO: " . $tipoCambio : ($tipoCambio==''? 'TIPO DE CAMBIO: ': '')) ), false, false);

    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(75));
    $pdf->SetXY(6, $pdf->GetY()+6);
    $pdf->Row(array('SOLICITA: __________________________________________'), false, false);
    $pdf->SetXY(6, $pdf->GetY()-2);
    $pdf->SetAligns(array('C', 'L'));
    $pdf->Row(array(strtoupper($orden['info'][0]->empleado_solicito)), false, false);

    // $pdf->SetAligns(array('L', 'R'));
    // $pdf->SetWidths(array(104, 50));
    // $pdf->SetXY(6, $pdf->GetY());
    // $pdf->Row(array('REGISTRO: '.strtoupper($orden['info'][0]->empleado), ($tipoCambio>0? "TIPO DE CAMBIO: " . $tipoCambio : '') ), false, false);

    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(250));
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('OBSERVACIONES: '.$orden['info'][0]->descripcion), false, false);
    $pdf->SetXY(6, $pdf->GetY()+4);

    // if ($tipo_requisicion) {
    //   $pdf->SetWidths(array(250));
    //   $pdf->SetXY(6, $pdf->GetY());
    //   $pdf->Row(array('DESCRIPCION DE CODIGOS: '.implode(', ', $orden['info'][0]->data_desCodigos)), false, false);
    // } else {
      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(5, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(25, 80));
      $pdf->Row(array('EMPRESA', $orden['info'][0]->empresa), false, true);

      // El dato de la requisicion
      // if (!empty($orden['info'][0]->folio_requisicion)) {
      //   $pdf->SetFont('Arial','',8);
      //   $pdf->SetXY(5, $pdf->GetY());
      //   $pdf->SetAligns(array('L', 'L'));
      //   $pdf->SetWidths(array(25, 80));
      //   $pdf->Row(array('ENLACE', "Requisicion No {$orden['info'][0]->folio_requisicion}"), false, true);
      // }
      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(5, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(25, 80));
      $pdf->Row(array('Cultivo / Actividad / Producto',
        (!empty($orden['info'][0]->area)? $orden['info'][0]->area->nombre: '')), false, true);

      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(5, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(25, 80));
      $ranchos = [];
      foreach ($orden['info'][0]->rancho as $key => $value) {
        $ranchos[] = $value->nombre;
      }
      $pdf->Row(array('Areas / Ranchos / Lineas',
        (!empty($orden['info'][0]->rancho)? implode(' | ', $ranchos): '')), false, true);

      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(5, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(25, 80));
      $centroCosto = [];
      foreach ($orden['info'][0]->centroCosto as $key => $value) {
        $centroCosto[] = $value->nombre;
      }
      $pdf->Row(array('Centro de costo',
        (!empty($orden['info'][0]->centroCosto)? implode(' | ', $centroCosto): '')), false, true);

      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(5, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(25, 80));
      $pdf->Row(array('Activo', (!empty($orden['info'][0]->activo)? $orden['info'][0]->activo->nombre: '')), false, true);

    // }


    if ($path)
    {
      $file = $path.'ORDEN_COMPRA_'.date('Y-m-d').'.pdf';
      $pdf->Output($file, 'F');
      return $file;
    }
    else
    {
      $pdf->Output('ORDEN_COMPRA_'.date('Y-m-d').'.pdf', 'I');
    }
  }

  public function print_pre_receta($orden, $path = null)
  {
    // $orden = $this->info($ordenId, true);
    $tipo_requisicion = count($orden['info'][0]->productos)>0? true: false; // requisicion, pre-requisicion

    $tipo_orden = 'PRE REQUISICION';

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('L', 'mm', 'Letter');
    // $pdf->show_head = true;
    $pdf->titulo1 = ''; // $orden['info'][0]->empresa;
    $pdf->titulo2 = $tipo_orden;

    $pdf->logo = $orden['info'][0]->logo!=''? (file_exists($orden['info'][0]->logo)? $orden['info'][0]->logo: '') : '';

    $pdf->noShowPages = false;
    $pdf->noShowDate = false;
    $pdf->AddPage();

    // /home/gama/www/sanjorge/application/images/mas
    $pdf->Image(APPPATH.'images/mas/pre-recetas.png', 205, 72, 60);

    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetAligns(array('L', 'C', 'R'));
    $pdf->SetWidths(array(50));
    $pdf->SetXY(222, $pdf->GetY()-20);
    $pdf->Row(array('PRE-REQ: '.MyString::formatoNumero($orden['info'][0]->folio, 2, '')), false, true);
    $pdf->SetXY(222, $pdf->GetY());
    $pdf->Row(array('RECETA: '.(isset($orden['info'][0]->otros_datos->noRecetas)? implode(',', $orden['info'][0]->otros_datos->noRecetas): '')), false, true);

    $pdf->SetFont('helvetica','B', 7);
    $pdf->SetAligns(array('L', 'L', 'R', 'L', 'R', 'L', 'R', 'L', ));
    $pdf->SetWidths(array(16, 63, 16, 25, 20, 25, 12, 25, ));
    $pdf->SetXY(6, $pdf->GetY()+12);
    $auxt = $pdf->GetY();
    $pdf->Row(array(
      'EMPRESA:', $orden['info'][0]->empresa,
      '# HAS:', '______________',
      'PLANTA X HA:', '______________',
      'SEM:', '______________',
    ), false, false);

    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->Row(array(
      'RANCHO:', '____________________________________________',
      '# CARGAS:', '______________',
      'KG PLANTAS:', '______________',
      'CICLO:', '______________',
    ), false, false);

    $pdf->SetWidths(array(16, 63, 20, 21, 20, 25, 12, 25, ));
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->Row(array(
      'SECCION:', '____________________________________________',
      '_____________', '____________',
      'PH FORMULA:', '______________',
      '', '',
    ), false, false);
    $pdf->SetTextColor(200, 200, 200);
    $pdf->SetXY(6, $pdf->GetY()-6);
    $pdf->Row(array(
      '', '',
      'COMPLETA', 'MEDIA',
      '', '',
      '', '',
    ), false, false);

    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(45));
    $pdf->SetXY(220, $auxt-5);
    $pdf->Row(array('FECHA'), false, TRUE);
    $pdf->SetWidths(array(15, 15, 15));
    $pdf->SetXY(220, $auxt+1);
    $pdf->Row(array('', '', ''), false, TRUE);

    $pdf->SetWidths(array(11));
    $pdf->SetXY(209, $pdf->GetY()+5);
    $pdf->Row(array('ETAPA'), false, false);
    $pdf->SetWidths(array(11, 34));
    $pdf->SetXY(220, $pdf->GetY()-9);
    $pdf->Row(array('DP', ''), false, true);
    $pdf->SetXY(220, $pdf->GetY()+1);
    $pdf->Row(array('DF', ''), false, true);

    $pdf->SetFont('helvetica','B', 7);
    $pdf->SetWidths(array(16, 81));
    $pdf->SetXY(170, $pdf->GetY()+1);
    $pdf->Row(array('OBJETIVO: ', '_________________________________________________________'), false, false);

    $pdf->SetXY(6, $pdf->GetY()+5);

    $subtotal = $iva = $total = $retencion = 0;

    $aligns = array('C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C');
    $aligns2 = array('R', 'L', 'L', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'R');
    $widths2 = array(20, 51, 51, 51);
    $widths = array(18, 65, 21, 22, 25, 30, 85);
    $header = array('(%) (Pz) MEZCLA', 'ORDEN DE APLICACION', 'DOSIS', 'APLICACIÓN TOTAL', 'PRECIO', 'IMPORTE', 'DATOS DE APLICACION');

    $auxy = $pdf->GetY();
    for ($i=0; $i < 15; $i++) {
      if($pdf->GetY() >= $pdf->limiteY || $i === 0) { //salta de pagina si exede el max
        if($pdf->GetY()+5 >= $pdf->limiteY)
          $pdf->AddPage();

        $pdf->SetXY(6, $pdf->GetY()+3);
        $pdf->SetFont('Arial','B',7);

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, false);
      }

      $pdf->SetFont('Arial','',7);
      $pdf->SetTextColor(0,0,0);
      $datos = array('','','','','','');

      $pdf->SetX(6);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false);
    }

    // Totales
    $pdf->SetFont('Arial','B',7);
    $pdf->SetX(6);
    $pdf->SetAligns(array('R', 'R', 'R', 'R', 'C', 'C'));
    $pdf->SetWidths(array(83, 21, 22, 25, 30, 85));
    $pdf->Row(array('SUMAS', '', '', 'TOTAL', '', 'FIRMA'), false, true);

    $pdf->SetY($auxy+11);
    $pdf->SetFont('Arial','',7);
    $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(15, 21, 15, 17, 17, 15, 15));
    $pdf->SetX(187);
    $pdf->Row(array("   TURNO    ", 'AMANECER', 'DIA', 'TARDE', 'NOCHE'), false, true);
    $pdf->SetWidths(array(15, 14, 12, 15, 14, 15, 15));
    $pdf->SetX(187);
    $pdf->Row(array('VIA', 'SISTEMA', 'FOLIAR', 'SOLIDA PISO', 'DRENCH', 'UNTADO'), false, true);
    $pdf->SetWidths(array(17, 17, 15, 20, 16, 14, 12));
    $pdf->SetX(187);
    $pdf->Row(array('APLICACION', 'MANUAL', 'RUEGO', 'TERRESTRE', 'ACERO'), false, true);
    $pdf->SetWidths(array(17, 17, 15, 20, 16, 14, 12));
    $pdf->SetX(187);
    $pdf->Row(array('EQUIPOS', 'BOOM', 'PIPA', 'AGUILON', 'TANQUITA'), false, true);
    $pdf->SetX(187);
    $pdf->Row(array('', 'DRON', 'TAMBO', 'MOCHILA', 'OTROS'), false, true);

    $pdf->SetWidths(array(17, 50));
    $pdf->SetX(187);
    $pdf->Row(array('LITROS DE AGUA', '__________________________________'), false, false);
    $pdf->SetWidths(array(17, 68));
    $pdf->SetX(187);
    $pdf->Row(array('OBSERVACIONES', '________________________________________________'), false, false);
    $pdf->SetX(187);
    $pdf->Row(array('', '________________________________________________'), false, false);
    $pdf->SetXY(187, $pdf->GetY()+2);
    $pdf->Row(array('', '________________________________________________'), false, false);
    $pdf->SetXY(187, $pdf->GetY()+2);
    $pdf->Row(array('', '________________________________________________'), false, false);

    $pdf->SetXY(187, $pdf->GetY()+2);
    $pdf->Row(array('SOLICITA', '________________________________________________'), false, false);

    if ($path)
    {
      $file = $path.'ORDEN_COMPRA_'.date('Y-m-d').'.pdf';
      $pdf->Output($file, 'F');
      return $file;
    }
    else
    {
      $pdf->Output('ORDEN_COMPRA_'.date('Y-m-d').'.pdf', 'I');
    }
  }

  public function getInfoEntrada($folio, $empresa, $id_orden=null)
  {
    $sql = $id_orden? " AND cea.id_orden = {$id_orden} ": " AND cea.folio = {$folio} AND cea.id_empresa = {$empresa} ";
    $query = $this->db->query("SELECT cea.folio AS folio_almacen, Date(cea.fecha) AS fecha, cea.almacen,
                                  co.folio, e.nombre_fiscal AS empresa, p.nombre_fiscal AS proveeor,
                                  (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS recibio,
                                  (SELECT Coalesce(Sum(total), 0) FROM compras_productos WHERE id_orden = co.id_orden GROUP BY id_orden) AS total
                               FROM compras_entradas_almacen cea
                                INNER JOIN compras_ordenes co ON co.id_orden = cea.id_orden
                                INNER JOIN empresas e ON e.id_empresa = cea.id_empresa
                                INNER JOIN proveedores p ON p.id_proveedor = co.id_proveedor
                                INNER JOIN usuarios u ON u.id = cea.id_recibio
                               WHERE cea.status = 't' {$sql} ")->row();
    return $query;
  }

  public function imprimir_entrada($folio, $empresa)
  {
    $data = $this->getInfoEntrada($folio, $empresa);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 130));
    $pdf->show_head = false;

    $pdf->AddPage();
    $pdf->SetFont('helvetica','B', 8);
    $pdf->SetXY(0, 1);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(63));
    $pdf->Row(array('INGRESO ALMACEN '.$data->almacen), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array($data->empresa), false, false);
    $pdf->SetFont('helvetica','', 8);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(30, 30));
    $pdf->Row(array('FECHA: '.MyString::fechaATexto($data->fecha, '/c'), 'REG. No '.$data->folio_almacen), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->Row(array($data->proveeor), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(25, 40));
    $pdf->Row(array('FOLIO: '.MyString::formatoNumero($data->folio, 2, ''), 'IMPORTE: '.MyString::formatoNumero($data->total)), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->Row(array('RECIBI: '.$data->recibio), false, false);

    $pdf->Rect(0.5, 0.5, 62, $pdf->GetY()+4);

    $pdf->AutoPrint(true);
    $pdf->Output();
  }

   /**
    * Visualiza/Descarga el PDF de la orden de compra.
    *
    * @return void
    */
   public function print_recibo_faltantes($ordenId)
   {
      $orden = $this->info($ordenId, true);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      // $pdf->show_head = true;
      $pdf->titulo2 = 'Proveedor: ' . $orden['info'][0]->proveedor;
      $pdf->titulo3 = " Fecha: ". date('Y-m-d') . ' Orden: ' . $orden['info'][0]->id_orden." \n RECIBO DE FALTANTES";

      $pdf->AliasNbPages();
      // $pdf->AddPage();
      $pdf->SetFont('helvetica','', 8);

      $aligns = array('C', 'C', 'C', 'C');
      $widths = array(25, 25, 129, 25);
      $header = array('CANT.', 'CODIGO', 'DESCRIPCION', 'FALTANTES');

      $subtotal = $iva = $total = 0;
      foreach ($orden['info'][0]->productos as $key => $prod)
      {
        $band_head = false;
        if($pdf->GetY() >= $pdf->limiteY || $key==0) { //salta de pagina si exede el max
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

        if ($prod->faltantes > 0)
        {
          $datos = array(
            $prod->cantidad,
            $prod->codigo,
            $prod->descripcion,
            $prod->faltantes,
          );

          $pdf->SetX(6);
          $pdf->Row($datos, false);
        }
      }

      $x = $pdf->GetX();
      $y = $pdf->GetY();

      $pdf->SetXY($x - 4, $y + 5);
      $pdf->cell(203, 6, '"PROVEEDOR: ES INDISPENSABLE PRESENTAR ESTA ORDEN DE COMPRA JUNTO CON SU FACTURA PAR QUE PROCEDA SU PAGO, GRACIAS"', false, false, 'L');

      $pdf->SetAligns(array('C', 'C', 'C'));
      $pdf->SetWidths(array(65, 65, 65));
      $pdf->SetX(6);
      $pdf->SetY($y + 11);
      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->Row(array(
        'SOLICITA',
        'AUTORIZA',
        'REGISTRO',
      ), false, false);

      $pdf->SetY($y + 20);
      $pdf->Row(array(
        '____________________________________',
        '____________________________________',
        '____________________________________',
      ), false, false);

      $pdf->SetY($y + 30);
      $pdf->Row(array(
        strtoupper($orden['info'][0]->empleado_solicito),
        strtoupper($orden['info'][0]->autorizo),
        strtoupper($orden['info'][0]->empleado),
      ), false, false);

      // $pdf->AutoPrint(true);
      $pdf->Output('ORDEN_COMPRA_FALTANTES_'.date('Y-m-d').'.pdf', 'I');
   }

  /*
   |------------------------------------------------------------------------
   | HELPERS
   |------------------------------------------------------------------------
   */

  /**
   * Crea el directorio por proveedor.
   *
   * @param  string $clienteNombre
   * @param  string $folioFactura
   * @return string
   */
  public function creaDirectorioProveedorCfdi($proveedor)
  {
    $path = APPPATH.'media/compras/cfdi/';

    if ( ! file_exists($path))
    {
      // echo $path.'<br>';
      mkdir($path, 0777);
    }

    $path .= strtoupper($proveedor).'/';
    if ( ! file_exists($path))
    {
      // echo $path;
      mkdir($path, 0777);
    }

    $path .= date('Y').'/';
    if ( ! file_exists($path))
    {
      // echo $path;
      mkdir($path, 0777);
    }

    $path .= $this->mesToString(date('m')).'/';
    if ( ! file_exists($path))
    {
      // echo $path;
      mkdir($path, 0777);
    }

    // $path .= ($serie !== '' ? $serie.'-' : '').$folio.'/';
    // if ( ! file_exists($path))
    // {
    //   // echo $path;
    //   mkdir($path, 0777);
    // }

    return $path;
  }

  /**
   * Regresa el MES que corresponde en texto.
   *
   * @param  int $mes
   * @return string
   */
  private function mesToString($mes)
  {
    switch(floatval($mes))
    {
      case 1: return 'ENERO'; break;
      case 2: return 'FEBRERO'; break;
      case 3: return 'MARZO'; break;
      case 4: return 'ABRIL'; break;
      case 5: return 'MAYO'; break;
      case 6: return 'JUNIO'; break;
      case 7: return 'JULIO'; break;
      case 8: return 'AGOSTO'; break;
      case 9: return 'SEPTIEMBRE'; break;
      case 10: return 'OCTUBRE'; break;
      case 11: return 'NOVIEMBRE'; break;
      case 12: return 'DICIEMBRE'; break;
    }
  }

  public function email($ordenId)
  {
    $this->load->model('proveedores_model');

    $orden = $this->info($ordenId);
    $proveedor = $this->proveedores_model->getProveedorInfo($orden['info'][0]->id_proveedor);

    if ($proveedor['info']->email !== '')
    {
      // Si el proveedor tiene email asigando le envia la orden.
      $this->load->library('my_email');

      $correoEmisorEm = "empaquesanjorge@hotmail.com"; // Correo con el q se emitira el correo.
      $nombreEmisor   = 'Empaque San Jorge';
      $correoEmisor   = "empaquesanjorgemx@gmail.com"; // Correo para el auth.
      $contrasena     = "2x02pxeexCUpiKncoWI50Q"; // Contraseña de $correEmisor

      $path = APPPATH . 'media/temp/';

      $file = $this->print_orden_compra($ordenId, $path);

      $datosEmail = array(
        'correoEmisorEm' => $correoEmisorEm,
        'correoEmisor'   => $correoEmisor,
        'nombreEmisor'   => $nombreEmisor,
        'contrasena'     => $contrasena,
        'asunto'         => 'Nueva orden de compra ' . date('Y-m-d H:m'),
        'altBody'        => 'Nueva orden de compra.',
        'body'           => 'Nueva orden de compra.',
        'correoDestino'  => array($proveedor['info']->email),
        'nombreDestino'  => $proveedor['info']->nombre_fiscal,
        'cc'             => '',
        'adjuntos'       => array('ORDEN_COMPRA_'.$orden['info'][0]->folio.'.pdf' => $file)
      );

      $result = $this->my_email->setData($datosEmail)->send();
      unlink($file);

      $msg = 10;
    }
    else
    {
      $msg = 11;
    }

    return array('passes' => true, 'msg' => $msg);
  }
}