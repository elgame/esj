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
  public function getPolizas($perpage = '40')
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
      $next_folio = $this->folio($_POST['empresaId']);

      $data = array(
        'id_empresa' => $_POST['empresaId'],
        'folio'      => $next_folio,
        'fecha'      => str_replace('T', ' ', $_POST['fecha']),
        'concepto'   => $_POST['concepto'],
      );
    }
    $this->db->insert('otros.polizas', $data);
    $id_poliza = $this->db->insert_id('otros.polizas_id_seq');

    $movimiento = $this->agregarMovimientos($id_poliza);

    return array('passes' => true, 'msg' => 3, 'id_poliza' => $id_poliza, 'movimiento' => $movimiento);
  }

  public function modificar($id_poliza, $data = null)
  {
    if ( ! $data)
    {
      $data = array(
        'id_empresa' => $_POST['empresaId'],
        'fecha'      => str_replace('T', ' ', $_POST['fecha']),
        'concepto'   => $_POST['concepto'],
      );
    }

    $this->db->update('otros.polizas', $data, ['id' => $id_poliza]);
    $this->agregarMovimientos($id_poliza);

    return array('passes' => true, 'msg' => 3, 'id_poliza' => $id_poliza);
  }

  /**
   * Agrega los productos de una salida.
   *
   * @return array
   */
  public function agregarMovimientos($id_poliza)
  {
    $cheques = [];
    $this->load->model('centros_costos_model');
    $this->load->model('banco_cuentas_model');

    $this->db->delete('otros.polizas_movimientos', "id_poliza = {$id_poliza}");

    $movimiento = $movimientos = array();
    foreach ($_POST['centroCostoId'] as $key => $centroCostoId)
    {
      $movimiento = array(
        'id_poliza'       => $id_poliza,
        'row'             => $key,
        'id_centro_costo' => $_POST['centroCostoId'][$key],
        'tipo'            => $_POST['tipo'][$key],
        'monto'           => $_POST['cantidad'][$key],
        'cuenta_cpi'      => $_POST['cuentaCtp'][$key],
      );

      // Cuando es de tipo banco inserta el mov en bancos
      $centro = $this->centros_costos_model->getCentroCostoInfo($centroCostoId, true);
      if ($centro['info']->tipo == 'banco' && intval($centro['info']->id_cuenta) > 0) {
        $cuenta = $this->banco_cuentas_model->getCuentaInfo($centro['info']->id_cuenta, true);

        $data = array(
          'id_cuenta'   => $centro['info']->id_cuenta,
          'id_banco'    => $cuenta['info']->id_banco,
          'fecha'       => str_replace('T', ' ', $_POST['fecha']).':'.date("H:i:s"),
          'numero_ref'  => '',
          'concepto'    => substr($_POST['conceptoMov'][$key], 0, 120),
          'monto'       => $_POST['cantidad'][$key],
          'tipo'        => $_POST['tipo'][$key],
          'entransito'  => 'f',
          'metodo_pago' => $_POST['metodoPago'][$key],
          'a_nombre_de' => $_POST['cliente'][$key],
          'abono_cuenta' => ($_POST['abonoCuenta'][$key]=='true')? 't': 'f',
        );
        if(is_numeric($_POST['idCliente'][$key]))
          $data['id_cliente'] = $_POST['idCliente'][$key];
        if($_POST['cuentaCtp'][$key] != '')
          $data['cuenta_cpi'] = $_POST['cuentaCtp'][$key];

        if ($_POST['tipo'][$key] == 't') {
          $movBanco = $this->banco_cuentas_model->addDeposito($data);
        } else {
          $movBanco = $this->banco_cuentas_model->addRetiro($data);
        }

        // agrega el id del movimiento de banco para cuando se cancele la poliza cancelar en bancos
        if (isset($movBanco['id_movimiento']) && $movBanco['id_movimiento'] > 0) {
          $movimiento['id_movimiento'] = $movBanco['id_movimiento'];
          // si es cheque se agrega para mostrar la impresión
          if ($_POST['metodoPago'][$key] == 'cheque') {
            $cheques[] = $movimiento['id_movimiento'];
          }
        }
      }

      $movimientos[] = $movimiento;
      $this->db->insert('otros.polizas_movimientos', $movimiento);
    }

    return $movimientos;
  }


  public function cancelar($id_poliza)
  {
    $this->db->update('otros.polizas', array('status' => 'f'), array('id' => $id_poliza));

    $this->load->model('banco_cuentas_model');
    $data = $this->info($id_poliza, true);
    if (is_array($data['info']->movimientos)) {
      foreach ($data['info']->movimientos as $key => $value) {
        // Si hay un movimiento ligado de bancos se elimina
        if ($value->id_movimiento > 0) {
          $this->banco_cuentas_model->deleteMovimiento($value->id_movimiento);
        }
      }
    }
    return array('passes' => true);
  }

  public function info($id_poliza, $full = false)
  {
    $query = $this->db->query(
      "SELECT p.id, p.id_empresa, e.nombre_fiscal AS empresa, e.logo, e.dia_inicia_semana,
              p.folio, p.fecha, p.status, p.concepto
        FROM otros.polizas AS p
          INNER JOIN empresas AS e ON e.id_empresa = p.id_empresa
        WHERE p.id = {$id_poliza}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->row();

      $query->free_result();
      if ($full)
      {
        $query = $this->db->query(
          "SELECT pm.id_poliza, pm.row, pm.id_centro_costo, cc.nombre AS centro_costo, pm.tipo, pm.monto,
            pm.cuenta_cpi, pm.id_movimiento
          FROM otros.polizas_movimientos AS pm
            INNER JOIN otros.centro_costo AS cc ON cc.id_centro_costo = pm.id_centro_costo
          WHERE pm.id_poliza = {$data['info']->id}");

        $data['info']->movimientos = array();
        if ($query->num_rows() > 0)
        {
          $data['info']->movimientos = $query->result();
        }
      }

    }

    return $data;
  }

  public function folio($empresa_id)
  {
    $res = $this->db->select('folio')
      ->from('otros.polizas')
      ->where('id_empresa', $empresa_id)
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