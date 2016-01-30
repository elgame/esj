<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class caja_chica_prest_model extends CI_Model {

  public function get($fecha, $noCaja)
  {
    $info = array(
      'saldo_inicial' => 0,
      'prestamos'      => array(),
      'pagos'         => array(),
      'denominaciones' => array(),
      'categorias'    => array(),
    );

    // Obtiene el saldo incial.
    $ultimoSaldo = $this->db->query(
      "SELECT saldo
       FROM otros.cajaprestamo_efectivo
       WHERE fecha < '{$fecha}' AND no_caja = {$noCaja}
       ORDER BY fecha DESC
       LIMIT 1"
    );

    if ($ultimoSaldo->num_rows() > 0)
    {
      $info['saldo_inicial'] = $ultimoSaldo->result()[0]->saldo;
    }

    $prestamos = $this->db->query(
      "SELECT id_prestamo, id_prestamo_nom, id_empleado, id_categoria, id_nomenclatura, concepto, fecha, monto, categoria, nomenclatura
      FROM (
        SELECT cp.id_prestamo, cp.id_prestamo_nom, cp.id_empleado, cp.id_categoria, cp.id_nomenclatura, cp.concepto, cp.fecha, cp.monto,
          cc.abreviatura as categoria, cn.nomenclatura
        FROM otros.cajaprestamo_prestamos cp
        INNER JOIN cajachica_categorias cc ON cc.id_categoria = cp.id_categoria
        INNER JOIN cajachica_nomenclaturas cn ON cn.id = cp.id_nomenclatura
        WHERE cp.fecha = '{$fecha}' AND cp.no_caja = {$noCaja}
        UNION
        SELECT cp.id_prestamo AS id_prestamo, np.id_prestamo AS id_prestamo_nom, np.id_usuario AS id_empleado, null AS id_categoria,
          null AS id_nomenclatura, null AS concepto, Date(np.fecha) AS fecha,
          np.prestado AS monto, null AS categoria, null AS nomenclatura
        FROM nomina_prestamos np
        LEFT JOIN otros.cajaprestamo_prestamos cp ON np.id_prestamo = cp.id_prestamo_nom
        WHERE np.fecha = '{$fecha}' AND cp.id_prestamo IS NULL
      ) AS t
      ORDER BY id_prestamo_nom ASC"
    );

    if ($prestamos->num_rows() > 0)
    {
      $info['prestamos'] = $prestamos->result();
    }

    $pagos = $this->db->query(
      "SELECT id_pago, id_empleado, id_empresa, anio, semana, id_prestamo, id_categoria, concepto, monto, fecha, id_nomenclatura, categoria, nomenclatura
      FROM (
        SELECT cp.id_pago, cp.id_empleado, cp.id_empresa, cp.anio, cp.semana, cp.id_prestamo, cp.id_categoria, cp.concepto, cp.monto, cp.fecha,
          cp.id_nomenclatura, cc.abreviatura as categoria, cn.nomenclatura
        FROM otros.cajaprestamo_pagos cp
        INNER JOIN cajachica_categorias cc ON cc.id_categoria = cp.id_categoria
        INNER JOIN cajachica_nomenclaturas cn ON cn.id = cp.id_nomenclatura
        WHERE cp.fecha = '{$fecha}' AND cp.no_caja = {$noCaja}
        UNION
        SELECT cp.id_pago, np.id_empleado, np.id_empresa, np.anio, np.semana, np.id_prestamo, cp.id_categoria, cp.concepto, np.monto, np.fecha,
          cp.id_nomenclatura, null AS categoria, null AS nomenclatura
        FROM nomina_fiscal_prestamos np
        LEFT JOIN otros.cajaprestamo_pagos cp ON (cp.id_empleado = cp.id_empleado AND np.id_empresa = cp.id_empresa AND np.anio = cp.anio AND np.semana = cp.semana AND np.id_prestamo = cp.id_prestamo)
        WHERE np.fecha = '{$fecha}' AND cp.id_pago IS NULL
      ) AS t
      ORDER BY id_pago ASC"
    );

    if ($pagos->num_rows() > 0)
    {
      $info['pagos'] = $pagos->result();
    }

    // denominaciones
    $denominaciones = $this->db->query(
      "SELECT *
       FROM otros.cajaprestamo_efectivo
       WHERE fecha = '{$fecha}' AND no_caja = {$noCaja}"
    );

    if ($denominaciones->num_rows() === 0)
    {
      $denominaciones = new StdClass;
      $denominaciones->den_05 = 0;
      $denominaciones->den_1 = 0;
      $denominaciones->den_2 = 0;
      $denominaciones->den_5 = 0;
      $denominaciones->den_10 = 0;
      $denominaciones->den_20 = 0;
      $denominaciones->den_50 = 0;
      $denominaciones->den_100 = 0;
      $denominaciones->den_200 = 0;
      $denominaciones->den_500 = 0;
      $denominaciones->den_1000 = 0;
    }
    else
    {
      $denominaciones = $denominaciones->result()[0];
      $info['status'] = $denominaciones->status;
      $info['id'] = $denominaciones->id_efectivo;
    }

    foreach ($denominaciones as $den => $cantidad)
    {
      if (strrpos($den, 'den_') !== false)
      {
        switch ($den)
        {
          case 'den_05':
            $denominacion = '0.50';
            break;
          case 'den_1':
            $denominacion = '1.00';
            break;
          case 'den_2':
            $denominacion = '2.00';
            break;
          case 'den_5':
            $denominacion = '5.00';
            break;
          case 'den_10':
            $denominacion = '10.00';
            break;
          case 'den_20':
            $denominacion = '20.00';
            break;
          case 'den_50':
            $denominacion = '50.00';
            break;
          case 'den_100':
            $denominacion = '100.00';
            break;
          case 'den_200':
            $denominacion = '200.00';
            break;
          case 'den_500':
            $denominacion = '500.00';
            break;
          case 'den_1000':
            $denominacion = '1000.00';
            break;
        }

        $info['denominaciones'][] = array(
          'denominacion' => $denominacion,
          'cantidad'     => $cantidad,
          'total'        => floatval($denominacion) * $cantidad,
          'denom_abrev'  => $den,
        );
      }
    }

    $info['categorias'] = $this->db->query(
    "SELECT id_categoria, nombre, abreviatura
     FROM cajachica_categorias
     WHERE status = 't'")->result();

    return $info;
  }

  public function guardar($data)
  {
    $prestamos = array();
    $prestamos_updt = array();

    // prestamos
    foreach ($data['prestamo_monto'] as $key => $ingreso)
    {
      if ($data['prestamo_id_prestamo'][$key] > 0) {
        $prestamos_updt = array(
          'id_prestamo_nom' => ($data['prestamo_id_prestamo_nom'][$key]!=''? $data['prestamo_id_prestamo_nom'][$key]: NULL),
          'id_empleado'     => ($data['prestamo_id_empleado'][$key]!=''? $data['prestamo_id_empleado'][$key]: NULL),
          'id_categoria'    => $data['prestamo_empresa_id'][$key],
          'id_nomenclatura' => $data['prestamo_nomenclatura'][$key],
          'concepto'        => $data['prestamo_concepto'][$key],
          'fecha'           => $data['fecha_caja_chica'],
          'monto'           => $data['prestamo_monto'][$key],
          'no_caja'         => $data['fno_caja'],
        );
        $this->db->update('otros.cajaprestamo_prestamos', $prestamos_updt, "id_prestamo = ".$data['prestamo_id_prestamo'][$key]);
      } else {
        $prestamos[] = array(
          // 'id_prestamo' => $data['prestamo_id_prestamo'][$key],
          'id_prestamo_nom' => ($data['prestamo_id_prestamo_nom'][$key]!=''? $data['prestamo_id_prestamo_nom'][$key]: NULL),
          'id_empleado'     => ($data['prestamo_id_empleado'][$key]!=''? $data['prestamo_id_empleado'][$key]: NULL),
          'id_categoria'    => $data['prestamo_empresa_id'][$key],
          'id_nomenclatura' => $data['prestamo_nomenclatura'][$key],
          'concepto'        => $data['prestamo_concepto'][$key],
          'fecha'           => $data['fecha_caja_chica'],
          'monto'           => $data['prestamo_monto'][$key],
          'no_caja'         => $data['fno_caja'],
        );
      }
    }

    if (count($prestamos) > 0)
    {
      $this->db->insert_batch('otros.cajaprestamo_prestamos', $prestamos);
    }

    $pagos = array();
    $pagos_updt = array();

    // pagos
    foreach ($data['pago_importe'] as $key => $ingreso)
    {
      if ($data['pago_id'][$key] > 0) {
        $pagos_updt = array(
          // 'id_pago' => $data['pago_id'][$key],
          'id_empleado'     => ($data['pago_id_empleado'][$key]!=''? $data['pago_id_empleado'][$key]: NULL),
          'id_empresa'      => ($data['pago_id_empresa'][$key]!=''? $data['pago_id_empresa'][$key]: NULL),
          'anio'            => ($data['pago_anio'][$key]!=''? $data['pago_anio'][$key]: NULL),
          'semana'          => ($data['pago_semana'][$key]!=''? $data['pago_semana'][$key]: NULL),
          'id_prestamo'     => ($data['pago_id_prestamo'][$key]!=''? $data['pago_id_prestamo'][$key]: NULL),
          'id_categoria'    => $data['pago_empresa_id'][$key],
          'concepto'        => $data['pago_concepto'][$key],
          'monto'           => $data['pago_importe'][$key],
          'fecha'           => $data['fecha_caja_chica'],
          'id_nomenclatura' => $data['pago_nomenclatura'][$key],
          'no_caja'         => $data['fno_caja'],
        );
        $this->db->update('otros.cajaprestamo_pagos', $pagos_updt, "id_pago = ".$data['pago_id'][$key]);
      } else {
        $pagos[] = array(
          'id_empleado'     => ($data['pago_id_empleado'][$key]!=''? $data['pago_id_empleado'][$key]: NULL),
          'id_empresa'      => ($data['pago_id_empresa'][$key]!=''? $data['pago_id_empresa'][$key]: NULL),
          'anio'            => ($data['pago_anio'][$key]!=''? $data['pago_anio'][$key]: NULL),
          'semana'          => ($data['pago_semana'][$key]!=''? $data['pago_semana'][$key]: NULL),
          'id_prestamo'     => ($data['pago_id_prestamo'][$key]!=''? $data['pago_id_prestamo'][$key]: NULL),
          'id_categoria'    => $data['pago_empresa_id'][$key],
          'concepto'        => $data['pago_concepto'][$key],
          'monto'           => $data['pago_importe'][$key],
          'fecha'           => $data['fecha_caja_chica'],
          'id_nomenclatura' => $data['pago_nomenclatura'][$key],
          'no_caja'         => $data['fno_caja'],
        );
      }
    }

    if (count($pagos) > 0)
    {
      $this->db->insert_batch('otros.cajaprestamo_pagos', $pagos);
    }

    // Denominaciones
    $this->db->delete('otros.cajaprestamo_efectivo', array('fecha' => $data['fecha_caja_chica'], 'no_caja' => $data['fno_caja']));
    $efectivo = array();
    foreach ($data['denom_abrev'] as $key => $denominacion)
    {
      $efectivo[$denominacion] = $data['denominacion_cantidad'][$key];
    }

    $efectivo['fecha']   = $data['fecha_caja_chica'];
    $efectivo['saldo']   = $data['saldo_corte'];
    $efectivo['no_caja'] = $data['fno_caja'];

    $this->db->insert('otros.cajaprestamo_efectivo', $efectivo);


    return true;
  }

  public function cerrarCaja($idCaja, $noCajas)
  {
    $this->db->update('otros.cajaprestamo_efectivo', array('status' => 'f'), array('id_efectivo' => $idCaja));
    return true;
  }

  public function printCajaNomenclatura(&$pdf, $nomenclaturas)
  {
    // nomenclatura
    $pdf->SetFont('Arial','', 6);
    $pdf->SetXY(111, 9);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(30));
    $pdf->Row(array('NOMENCLATURA INGRESOS'), false, false);

    $pdf->SetXY(150, 9);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(30));
    foreach ($nomenclaturas as $n)
    {
      $pdf->SetX(150);
      $pdf->Row(array($n->nomenclatura.' '.$n->nombre), false, false, null, 1, 1);
    }
  }
  public function printCaja($fecha, $noCajas)
  {
    $this->load->model('compras_areas_model');
    $this->load->model('catalogos_sft_model');

    $caja = $this->get($fecha, $noCajas);
    // $nomenclaturas = $this->nomenclaturas();

    // echo "<pre>";
    //   var_dump($caja);
    // echo "</pre>";exit;
    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    $pdf->show_head = false;
    // $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    // $pdf->titulo1 S= $empresa['info']->nombre_fiscal;
    // $pdf->logo = $empresa['info']->logo;
    // $pdf->titulo2 = $empleado['info'][0]->nombre;
    $pdf->titulo2 = "Caja Prestamos del {$fecha}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $pdf->limiteY = 235; //limite de alto

    // Reporte caja
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(104));
    $pdf->Row(array('REPORTE CAJA PRESTAMOS'), true, true, null, 3);

    $pdf->Image(APPPATH.(str_replace(APPPATH, '', '/images/logo.png')), 6, 15, 50);
    $pdf->Ln(20);

    $pdf->SetFont('Arial','B', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);

    // Fecha
    $pdf->SetXY(6, $pdf->GetY() - 20);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(104));
    $pdf->Row(array('FECHA ' . $fecha), false, false);

    // Saldo inicial
    $pdf->SetXY(6, $pdf->GetY() + 5);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(104));
    $pdf->Row(array('SALDO INICIAL '.String::formatoNumero($caja['saldo_inicial'], 2, '$', false)), false, false);

    // nomenclatura
    // $this->printCajaNomenclatura($pdf, $nomenclaturas);
    // $pdf->SetFont('Arial','', 6);
    // $pdf->SetXY(111, 9);
    // $pdf->SetAligns(array('C'));
    // $pdf->SetWidths(array(30));
    // $pdf->Row(array('NOMENCLATURA INGRESOS'), false, false);

    // $pdf->SetXY(150, 9);
    // $pdf->SetAligns(array('L'));
    // $pdf->SetWidths(array(30));
    // foreach ($nomenclaturas as $n)
    // {
    //   $pdf->SetX(150);
    //   $pdf->Row(array($n->nomenclatura.' '.$n->nombre), false, false, null, 1, 1);
    // }

    // $ttotalGastos = 0;
    // foreach ($caja['gastos'] as $gasto)
    // {
    //   $ttotalGastos += floatval($gasto->monto);
    // }

    // Prestamos
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, 32);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(79, 25));
    $pdf->Row(array('PRESTAMOS', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(20, 15, 44, 25));
    $pdf->Row(array('EMPRESA', 'NOM', 'NOMBRE Y/O CONCEPTO', 'CARGO'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'R', 'L', 'R'));
    $pdf->SetWidths(array(20, 15, 44, 25));

    $total_prestamos = 0;
    foreach ($caja['prestamos'] as $key => $ingreso)
    {
      $pdf->SetX(6);

      $pdf->Row(array(
        $ingreso->categoria,
        $ingreso->nomenclatura,
        $ingreso->concepto,
        String::formatoNumero($ingreso->monto, 2, '', false)), false, true);

      $total_prestamos += floatval($ingreso->monto);
    }

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(6);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetAligns(array('L', 'R', 'L', 'R'));
    $pdf->Row(array('', '', 'TOTAL', String::formatoNumero($total_prestamos, 2, '$', false)), true, true);
    $pdf->auxy = $pdf->GetY();

    // Pagos
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(111, 32);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(79, 25));
    $pdf->Row(array('PAGOS DEL DIA', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(111);
    $pdf->SetAligns(array('C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(20, 15, 44, 25));
    $pdf->Row(array('EMPRESA', 'NOM', 'NOMBRE Y/O CONCEPTO', 'ABONO'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetXY(111, $pdf->GetY());
    $pdf->SetAligns(array('L', 'R', 'L', 'R'));
    $pdf->SetWidths(array(20, 15, 44, 25));

    $total_pagos = 0;
    foreach ($caja['pagos'] as $key => $ingreso)
    {
      $pdf->SetX(111);

      $pdf->Row(array(
        $ingreso->categoria,
        $ingreso->nomenclatura,
        $ingreso->concepto,
        String::formatoNumero($ingreso->monto, 2, '', false)), false, true);

      $total_pagos += floatval($ingreso->monto);
    }

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(111);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetAligns(array('L', 'R', 'L', 'R'));
    $pdf->Row(array('', '', 'TOTAL', String::formatoNumero($total_pagos, 2, '$', false)), true, true);

    // Tabulaciones
    $pdf->SetFont('Arial','B', 6);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(210, 210, 210);
    $pdf->SetXY(6, $pdf->auxy + 5);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(56));
    $pdf->Row(array('TABULACION DE EFECTIVO'), true, true);

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(255, 255, 255);
    // $pdf->SetXY(131, $boletasY - 5.4);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('C', 'C', 'C'));
    $pdf->SetWidths(array(15, 16, 25));
    $pdf->Row(array('NUMERO', 'DENOMIN.', 'TOTAL'), true, true);

    $pdf->SetAligns(array('R', 'R', 'R'));
    $pdf->SetFont('Arial','', 7);
    $totalEfectivo = 0;
    foreach ($caja['denominaciones'] as $key => $denominacion)
    {
      // if($pdf->GetY() >= $pdf->limiteY){
      //   $pdf->AddPage();
      //   $pdf->SetFont('Helvetica','B', 7);
      //   $pdf->SetXY(6, $pdf->GetY());
      //   $pdf->Row(array('BOLETA', 'PRODUCTOR', 'IMPORTE', ''), true, true);
      // }

      // $pdf->SetFont('Helvetica','', 7);
      $pdf->SetX(6);

      $pdf->Row(array(
        $denominacion['cantidad'],
        $denominacion['denominacion'],
        String::formatoNumero($denominacion['total'], 2, '', false)), false, true);

      $totalEfectivo += floatval($denominacion['total']);
    }

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C', 'R'));
    $pdf->SetWidths(array(31, 25));
    $pdf->Row(array('TOTAL EFECTIVO', String::formatoNumero($totalEfectivo, 2, '$', false)), false, true);

    $pdf->SetX(6);
    $pdf->Row(array('DIFERENCIA', String::formatoNumero($totalEfectivo - ($caja['saldo_inicial'] - $total_prestamos + $total_pagos) , 2, '$', false)), false, false);

    $pdf->SetFont('Arial', 'B', 6);
    $pdf->SetXY(63, $pdf->GetY() - 32);
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(25, 19));
    $pdf->Row(array('SALDO INICIAL', String::formatoNumero($caja['saldo_inicial'], 2, '$', false)), false, false);
    $pdf->SetX(63);
    $pdf->Row(array('TOTAL PRESTAMOS', String::formatoNumero($total_prestamos, 2, '$', false)), false, false);
    $pdf->SetX(63);
    $pdf->Row(array('TOTAL PAGOS ', String::formatoNumero($total_pagos, 2, '$', false)), false, false);
    $pdf->SetX(63);
    $pdf->Row(array('SALDO DEL CORTE', String::formatoNumero($caja['saldo_inicial'] - $total_prestamos + $total_pagos, 2, '$', false)), false, false);

    // if(count($codigoAreas) > 0){
    //   $pdf->SetFont('Arial', '', 6);
    //   $pdf->SetXY(6, $pdf->GetY()+7);
    //   $pdf->SetWidths(array(205));
    //   $pdf->SetAligns('L');
    //   $pdf->Row(array('COD/AREA: ' . implode(' - ', $codigoAreas)), false, false);
    // }

    $pdf->Output('CAJA_CHICA.pdf', 'I');
  }

  public function nomenclaturas()
  {
    $res = $this->db->query("
        SELECT *
        FROM cajachica_nomenclaturas
        ORDER BY nomenclatura ASC");

    return $res->result();
  }

  public function printVale($id_gasto)
  {
    $gastos = $this->db->query(
      "SELECT cg.id_gasto, cg.concepto, cg.fecha, cg.monto, cc.id_categoria, cc.abreviatura as empresa,
          cg.folio, cg.id_nomenclatura, cn.nomenclatura, COALESCE(cca.id_cat_codigos, ca.id_area) AS id_area,
          COALESCE(cca.nombre, ca.nombre) AS nombre_codigo, COALESCE(cca.codigo, ca.codigo_fin) AS codigo_fin,
          (CASE WHEN cca.id_cat_codigos IS NULL THEN 'id_area' ELSE 'id_cat_codigos' END) AS campo,
          cg.no_caja
       FROM cajachica_gastos cg
         INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
         INNER JOIN cajachica_nomenclaturas cn ON cn.id = cg.id_nomenclatura
         LEFT JOIN compras_areas ca ON ca.id_area = cg.id_area
         LEFT JOIN otros.cat_codigos AS cca ON cca.id_cat_codigos = cg.id_cat_codigos
       WHERE cg.id_gasto = '{$id_gasto}'
       ORDER BY cg.id_gasto ASC"
    )->row();

    // echo "<pre>";
    //   var_dump($gastos);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 130));
    $pdf->show_head = false;

    // $pdf->AddPage();
    $pdf->SetFont('helvetica','', 8);
    $pdf->SetXY(0, 0);

    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()-5);
    $pdf->Row(array('VALE PROVISIONAL DE CAJA'), false, false);

    $pdf->SetAligns(array('L'));
    // $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()-6);
    $pdf->Row(array('Folio: '.$gastos->id_gasto), false, false);

    $pdf->SetWidths(array(20, 43));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetX(0);
    $pdf->Row(array('Caja: '.$gastos->no_caja, String::formatoNumero($gastos->monto, 2, '$', false) ), false, false);

    $pdf->SetX(0);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->Row(array('CANTIDAD:'), false, false);
    $pdf->SetX(0);
    $pdf->Row(array(String::num2letras($gastos->monto)), false, false);
    $pdf->SetX(0);
    $pdf->Line(0, $pdf->GetY()-1, 62, $pdf->GetY()-1);

    $pdf->SetX(0);
    // $pdf->SetAligns(array('L'));
    // $pdf->SetWidths(array(63));
    $pdf->Row(array('COD. AREA:'), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array($gastos->codigo_fin.' '.$gastos->nombre_codigo), false, false);
    $pdf->SetX(0);
    $pdf->Line(0, $pdf->GetY()-1, 62, $pdf->GetY()-1);

    $pdf->SetX(0);
    $pdf->Row(array($gastos->concepto), false, false);
    $pdf->Line(0, $pdf->GetY()-1, 62, $pdf->GetY()-1);

    $pdf->SetX(0);
    $pdf->SetAligns(array('C', 'C', 'C'));
    $pdf->SetWidths(array(21, 21, 21));
    $pdf->Row(array('AUTORIZA', 'RECIBIO', 'FECHA'), false, false);
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row(array('', '', $gastos->fecha), false, false);
    $pdf->Line(0, $pdf->GetY()+4, 62, $pdf->GetY()+4);
    $pdf->Line(21, $pdf->GetY()-12, 21, $pdf->GetY()+4);
    $pdf->Line(42, $pdf->GetY()-12, 42, $pdf->GetY()+4);

    $pdf->AutoPrint(true);
    $pdf->Output();
  }



}

/* End of file caja_chica_model.php */
/* Location: ./application/models/caja_chica_model.php */