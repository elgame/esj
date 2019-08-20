<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class existencias_limon_model extends CI_Model {

  public function get_saldos($fecha, $noCaja)
  {
    $info = array(
      'fondos_caja'     => 0,
      'prestamos_lp_fi' => 0,
      'prestamos_lp_ef' => 0,
      'prestamos_cp'    => 0,
      'saldo_caja'      => 0,
    );

    $fondos_caja = $this->db->query(
      "SELECT tipo_movimiento, Sum(monto) AS monto
      FROM otros.cajaprestamo_fondo
      WHERE fecha <= '{$fecha}' AND no_caja = {$noCaja}
      GROUP BY tipo_movimiento"
    );

    if ($fondos_caja->num_rows() > 0)
    {
      foreach ($fondos_caja->result() as $key => $value) {
        if ($value->tipo_movimiento == 't') {
          $info['fondos_caja'] += $value->monto;
        } else {
          $info['fondos_caja'] -= $value->monto;
        }
      }
    }

    // Prestamos a largo plazo
    $prestamos = $this->db->query(
      "SELECT np.tipo,
        Sum(np.prestado) AS monto,
        Sum(np.prestado-COALESCE(pai.saldo_ini, 0)) AS saldo_ini
      FROM nomina_prestamos np
      INNER JOIN usuarios u ON u.id = np.id_usuario
      INNER JOIN empresas e ON e.id_empresa = u.id_empresa
      LEFT JOIN cajachica_categorias cc ON cc.id_empresa = e.id_empresa AND cc.status = 't'
      LEFT JOIN (
        SELECT np.id_prestamo, Sum(nfp.monto) AS saldo_ini, Count(*) AS no_pagos
        FROM nomina_fiscal_prestamos nfp
          INNER JOIN nomina_prestamos np ON np.id_prestamo = nfp.id_prestamo
        WHERE nfp.fecha <= '{$fecha}'
        GROUP BY np.id_prestamo
      ) pai ON np.id_prestamo = pai.id_prestamo
      WHERE np.close = 'f' AND Date(np.fecha) >= '2016-02-11' AND Date(np.fecha) <= '{$fecha}'
      GROUP BY np.tipo"
    );

    if ($prestamos->num_rows() > 0)
    {
      foreach ($prestamos->result() as $key => $value) {
        if ($value->tipo == 'fi') {
          $info['prestamos_lp_fi'] += $value->saldo_ini;
        } else {
          $info['prestamos_lp_ef'] += $value->saldo_ini;
        }
      }
    }

    // Prestamo a corto plazo
    $prestamos = $this->db->query(
      "SELECT Sum(cp.monto-COALESCE(pai.saldo_ini, 0)) AS saldo_ini
      FROM otros.cajaprestamo_prestamos cp
        INNER JOIN usuarios u ON u.id = cp.id_empleado
        INNER JOIN cajachica_categorias cc ON cc.id_categoria = cp.id_categoria AND cc.status = 't'
        LEFT JOIN (
          SELECT np.id_prestamo, Sum(nfp.monto) AS saldo_ini, Count(*) AS no_pagos
          FROM otros.cajaprestamo_pagos nfp
            INNER JOIN otros.cajaprestamo_prestamos np ON np.id_prestamo = nfp.id_prestamo_caja
          WHERE nfp.fecha <= '{$fecha}'
          GROUP BY np.id_prestamo
        ) pai ON cp.id_prestamo = pai.id_prestamo
      WHERE cp.fecha <= '{$fecha}' AND cp.no_caja = {$noCaja}
        AND (cp.monto-COALESCE(pai.saldo_ini, 0)) > 0"
    );

    if ($prestamos->num_rows() > 0)
    {
      $info['prestamos_cp'] = $prestamos->row()->saldo_ini;
    }

    $info['saldo_caja'] = $info['fondos_caja']-$info['prestamos_lp_ef']-$info['prestamos_cp'];

    return $info;
  }

  public function get($fecha, $noCaja, $id_area)
  {
    $id_empresa = 2;

    $info = array(
      'saldo_inicial'       => 0,
      'ventas'              => array(),
      'compra_fruta'        => array(),
      'produccion'          => array(),
      'existencia_anterior' => [],
      'existencia'          => [],
    );

    $ventas = $this->db->query(
      "SELECT f.serie, f.folio, cl.nombre_fiscal, c.id_clasificacion, c.nombre AS clasificacion, Sum(fp.cantidad) AS cantidad,
        (Sum(fp.importe) / Sum(fp.cantidad)) AS precio, Sum(fp.importe) AS importe, u.id_unidad,
        Coalesce(u.codigo, u.nombre) AS unidad, u.cantidad AS unidad_cantidad, (Sum(fp.cantidad) * u.cantidad) AS kilos
      FROM facturacion f
        INNER JOIN clientes cl ON cl.id_cliente = f.id_cliente
        INNER JOIN facturacion_productos fp ON f.id_factura = fp.id_factura
        INNER JOIN clasificaciones c ON c.id_clasificacion = fp.id_clasificacion
        INNER JOIN unidades u ON u.id_unidad = fp.id_unidad
      WHERE f.id_empresa = {$id_empresa} AND f.status <> 'ca' AND f.status <> 'b' AND f.is_factura = 'f'
        AND c.id_area = {$id_area} AND Date(f.fecha) = '{$fecha}'
      GROUP BY c.id_clasificacion, cl.id_cliente, f.id_factura, u.id_unidad
      ORDER BY folio ASC"
    );

    if ($ventas->num_rows() > 0)
    {
      $info['ventas'] = $ventas->result();
    }


    $compra_fruta = $this->db->query(
      "SELECT -- b.folio,
        -- pr.nombre_fiscal,
        c.id_calidad, c.nombre AS calidad, Sum(bc.kilos) AS kilos,
        (Sum(bc.importe) / Sum(bc.kilos)) AS precio, Sum(bc.importe) AS importe, (NULLIF(c.id_calidad, 2) IS NULL) AS is_fruta
      FROM bascula b
        INNER JOIN bascula_compra bc ON b.id_bascula = bc.id_bascula
        INNER JOIN calidades c ON c.id_calidad = bc.id_calidad
        --INNER JOIN proveedores pr ON pr.id_proveedor = b.id_proveedor
      WHERE b.id_empresa = {$id_empresa} AND b.status = 't'
        AND b.id_area = {$id_area} AND Date(b.fecha_bruto) = '{$fecha}'
      GROUP BY --b.id_bascula,
        c.id_calidad
        --, pr.id_proveedor
      ORDER BY id_calidad ASC"
    );

    if ($compra_fruta->num_rows() > 0)
    {
      $info['compra_fruta'] = $compra_fruta->result();
    }


    $produccion = $this->db->query(
      "SELECT c.id_clasificacion, c.nombre AS clasificacion, Sum(rrc.rendimiento) AS cantidad,
        Coalesce(elp.costo, 0) AS costo, (Coalesce(elp.costo, 0)*Sum(rrc.rendimiento)) AS importe, u.id_unidad,
        Coalesce(u.codigo, u.nombre) AS unidad, u.cantidad AS unidad_cantidad, (Sum(rrc.rendimiento) * u.cantidad) AS kilos,
        elp.id AS id_produccion
      FROM rastria_rendimiento rr
        INNER JOIN rastria_rendimiento_clasif rrc ON rr.id_rendimiento = rrc.id_rendimiento
        INNER JOIN clasificaciones c ON c.id_clasificacion = rrc.id_clasificacion
        INNER JOIN unidades u ON u.id_unidad = rrc.id_unidad
        LEFT JOIN otros.existencias_limon_produccion elp ON (elp.id_clasificacion = c.id_clasificacion
          AND elp.id_unidad = u.id_unidad AND Date(elp.fecha) = '{$fecha}')
      WHERE rr.status = 't' AND c.id_area = {$id_area}
        AND Date(rr.fecha) = '{$fecha}' AND (elp.no_caja = {$noCaja} OR elp.no_caja IS NULL)
      GROUP BY c.id_clasificacion, u.id_unidad, elp.id
      ORDER BY tipo ASC, id_clasificacion ASC, id_unidad ASC"
    );

    if ($produccion->num_rows() > 0)
    {
      $info['produccion'] = $produccion->result();
    }


    $fecha_anterior = $this->db->query(
      "SELECT Date(fecha) AS fecha
      FROM otros.existencias_limon_existencia
      WHERE Date(fecha) < '{$fecha}' AND no_caja = {$noCaja}
      ORDER BY fecha DESC
      LIMIT 1"
    )->row();
    $fecha_anterior = isset($fecha_anterior->fecha)? $fecha_anterior->fecha: MyString::suma_fechas($fecha, -1);

    $existencia_anterior = $this->db->query(
      "SELECT ele.id_clasificacion, ele.id_unidad, ele.fecha, ele.no_caja, ele.costo, ele.kilos,
        ele.cantidad, ele.importe, c.nombre AS clasificacion, Coalesce(u.codigo, u.nombre) AS unidad
      FROM otros.existencias_limon_existencia ele
        INNER JOIN clasificaciones c ON c.id_clasificacion = ele.id_clasificacion
        INNER JOIN unidades u ON u.id_unidad = ele.id_unidad
      WHERE Date(ele.fecha) = '{$fecha_anterior}' AND ele.no_caja = {$noCaja}"
    );

    if ($existencia_anterior->num_rows() > 0)
    {
      $info['existencia_anterior'] = $existencia_anterior->result();
    }


    $existencia = [];
    foreach ($info['existencia_anterior'] as $key => $item) {
      if (isset($existencia[$item->id_clasificacion.$item->id_unidad])) {
        $existencia[$item->id_clasificacion.$item->id_unidad]->cantidad += $item->cantidad;
        $existencia[$item->id_clasificacion.$item->id_unidad]->kilos    += $item->kilos;
        $existencia[$item->id_clasificacion.$item->id_unidad]->importe  += $item->importe;
      } else {
        $existencia[$item->id_clasificacion.$item->id_unidad]                   = new stdClass;
        $existencia[$item->id_clasificacion.$item->id_unidad]->id_clasificacion = $item->id_clasificacion;
        $existencia[$item->id_clasificacion.$item->id_unidad]->id_unidad        = $item->id_unidad;
        $existencia[$item->id_clasificacion.$item->id_unidad]->clasificacion    = $item->clasificacion;
        $existencia[$item->id_clasificacion.$item->id_unidad]->unidad           = $item->unidad;
        $existencia[$item->id_clasificacion.$item->id_unidad]->cantidad         = $item->cantidad;
        $existencia[$item->id_clasificacion.$item->id_unidad]->kilos            = $item->kilos;
        $existencia[$item->id_clasificacion.$item->id_unidad]->costo            = $item->costo;
        $existencia[$item->id_clasificacion.$item->id_unidad]->importe          = $item->importe;
        $existencia[$item->id_clasificacion.$item->id_unidad]->no_caja          = $noCaja;
        $existencia[$item->id_clasificacion.$item->id_unidad]->fecha            = $fecha;
      }
    }
    foreach ($info['produccion'] as $key => $item) {
      if (isset($existencia[$item->id_clasificacion.$item->id_unidad])) {
        $existencia[$item->id_clasificacion.$item->id_unidad]->cantidad += $item->cantidad;
        $existencia[$item->id_clasificacion.$item->id_unidad]->kilos    += $item->kilos;
        $existencia[$item->id_clasificacion.$item->id_unidad]->importe  += $item->importe;
      } else {
        $existencia[$item->id_clasificacion.$item->id_unidad]                   = new stdClass;
        $existencia[$item->id_clasificacion.$item->id_unidad]->id_clasificacion = $item->id_clasificacion;
        $existencia[$item->id_clasificacion.$item->id_unidad]->id_unidad        = $item->id_unidad;
        $existencia[$item->id_clasificacion.$item->id_unidad]->clasificacion    = $item->clasificacion;
        $existencia[$item->id_clasificacion.$item->id_unidad]->unidad           = $item->unidad;
        $existencia[$item->id_clasificacion.$item->id_unidad]->cantidad         = $item->cantidad;
        $existencia[$item->id_clasificacion.$item->id_unidad]->kilos            = $item->kilos;
        $existencia[$item->id_clasificacion.$item->id_unidad]->costo            = $item->costo;
        $existencia[$item->id_clasificacion.$item->id_unidad]->importe          = $item->importe;
        $existencia[$item->id_clasificacion.$item->id_unidad]->no_caja          = $noCaja;
        $existencia[$item->id_clasificacion.$item->id_unidad]->fecha            = $fecha;
      }
    }
    foreach ($info['ventas'] as $key => $item) {
      if (isset($existencia[$item->id_clasificacion.$item->id_unidad])) {
        $existencia[$item->id_clasificacion.$item->id_unidad]->cantidad -= $item->cantidad;
        $existencia[$item->id_clasificacion.$item->id_unidad]->kilos    -= $item->kilos;
        $existencia[$item->id_clasificacion.$item->id_unidad]->importe  -= $item->importe;
      } else {
        $existencia[$item->id_clasificacion.$item->id_unidad]                   = new stdClass;
        $existencia[$item->id_clasificacion.$item->id_unidad]->id_clasificacion = $item->id_clasificacion;
        $existencia[$item->id_clasificacion.$item->id_unidad]->id_unidad        = $item->id_unidad;
        $existencia[$item->id_clasificacion.$item->id_unidad]->clasificacion    = $item->clasificacion;
        $existencia[$item->id_clasificacion.$item->id_unidad]->unidad           = $item->unidad;
        $existencia[$item->id_clasificacion.$item->id_unidad]->cantidad         = $item->cantidad*-1;
        $existencia[$item->id_clasificacion.$item->id_unidad]->kilos            = $item->kilos*-1;
        $existencia[$item->id_clasificacion.$item->id_unidad]->costo            = 0;
        $existencia[$item->id_clasificacion.$item->id_unidad]->importe          = $item->importe*-1;
        $existencia[$item->id_clasificacion.$item->id_unidad]->no_caja          = $noCaja;
        $existencia[$item->id_clasificacion.$item->id_unidad]->fecha            = $fecha;
      }
    }
    $info['existencia'] = $existencia;

    $guardado = $this->db->query(
      "SELECT id_clasificacion
      FROM otros.existencias_limon_existencia
      WHERE Date(fecha) = '{$fecha}' AND no_caja = {$noCaja}
      LIMIT 1"
    )->row();
    $info['guardado'] = isset($guardado->id_clasificacion)? true: false;


    return $info;
  }

  public function guardar($data)
  {
    $produccion_inst = array();
    $produccion_updt = array();

    // Produccion
    foreach ($data['produccion_costo'] as $key => $id_cat)
    {
      if ($data['produccion_id_produccion'][$key] > 0) {
        $produccion_updt = array(
          'id_clasificacion' => $data['produccion_id_clasificacion'][$key],
          'id_unidad'        => $data['produccion_id_unidad'][$key],
          'costo'            => $data['produccion_costo'][$key],
          'importe'          => $data['produccion_importe'][$key],
          'fecha'            => $data['fecha_caja_chica'],
          'no_caja'          => $data['fno_caja'],
        );
        $this->db->update('otros.existencias_limon_produccion', $produccion_updt, "id = ".$data['produccion_id_produccion'][$key]);
      } else {
        $produccion_inst[] = array(
          'id_clasificacion' => $data['produccion_id_clasificacion'][$key],
          'id_unidad'        => $data['produccion_id_unidad'][$key],
          'costo'            => $data['produccion_costo'][$key],
          'importe'          => $data['produccion_importe'][$key],
          'fecha'            => $data['fecha_caja_chica'],
          'no_caja'          => $data['fno_caja'],
        );
      }
    }

    if (count($produccion_inst) > 0)
    {
      $this->db->insert_batch('otros.existencias_limon_produccion', $produccion_inst);
    }


    // Existencia
    $existencia_inst = array();
    foreach ($data['existencia_id_clasificacion'] as $key => $id_cat)
    {
      $this->db->delete('existencias_limon_existencia', "fecha = '{$data['fecha_caja_chica']}' AND no_caja = {$data['fno_caja']}");
      $existencia_inst[] = array(
        'id_clasificacion' => $data['existencia_id_clasificacion'][$key],
        'id_unidad'        => $data['existencia_id_unidad'][$key],
        'fecha'            => $data['fecha_caja_chica'],
        'no_caja'          => $data['fno_caja'],
        'costo'            => $data['existencia_costo'][$key],
        'kilos'            => $data['existencia_kilos'][$key],
        'cantidad'         => $data['existencia_cantidad'][$key],
        'importe'          => $data['existencia_importe'][$key],
      );
    }
    if (count($existencia_inst) > 0)
    {
      $this->db->insert_batch('otros.existencias_limon_existencia', $existencia_inst);
    }


    return true;
  }


  public function cerrarCaja($idCaja, $noCajas)
  {
    $this->db->update('otros.cajaprestamo_efectivo', array('status' => 'f'), array('id_efectivo' => $idCaja));
    return true;
  }


  public function printCaja($fecha, $noCajas, $id_area)
  {
    $this->load->model('compras_areas_model');
    $this->load->model('catalogos_sft_model');

    $caja = $this->get($fecha, $noCajas, $id_area);

    // echo "<pre>";
    //   var_dump($caja);
    // echo "</pre>";exit;
    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    $pdf->show_head = false;
    $pdf->titulo2 = "Existencias del {$fecha}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $pdf->limiteY = 235; //limite de alto

    // Reporte caja
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('REPORTE EXISTENCIAS DE LIMON'), true, true, null, 3);

    $pdf->Image(APPPATH.(str_replace(APPPATH, '', '/images/logo.png')), 6, 15, 50);
    $pdf->Ln(20);

    $pdf->SetFont('Arial','B', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);

    // Fecha
    $pdf->SetXY(6, $pdf->GetY() - 20);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('FECHA ' . $fecha), false, false);

    $pdf->auxy = $pdf->GetY();
    $page_aux = $pdf->page;

    // Ventas
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, 32);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('VENTAS'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(20, 55, 35, 16, 20, 20, 15, 23));
    $pdf->Row(array('FOLIO', 'CLIENTE', 'CLASIF', 'UNIDAD', 'KILOS', 'CANTIDAD', 'PRECIO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'L', 'C', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(20, 55, 35, 16, 20, 20, 15, 23));

    $venta_importe = $venta_kilos = $venta_cantidad = 0;
    foreach ($caja['ventas'] as $venta) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $venta_importe += floatval($venta->importe);
      $venta_kilos += floatval($venta->kilos);
      $venta_cantidad += floatval($venta->cantidad);

      $pdf->SetX(6);
      $pdf->Row(array(
        $venta->serie.$venta->folio,
        $venta->nombre_fiscal,
        $venta->clasificacion,
        $venta->unidad,
        MyString::formatoNumero($venta->kilos, 2, '', false),
        MyString::formatoNumero($venta->cantidad, 2, '', false),
        MyString::formatoNumero($venta->precio, 2, '', false),
        MyString::formatoNumero($venta->importe, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(6);
    $pdf->Row(array(
      '',
      '',
      '',
      '',
      MyString::formatoNumero($venta_kilos, 2, '', false),
      MyString::formatoNumero($venta_cantidad, 2, '', false),
      MyString::formatoNumero(($venta_importe/($venta_cantidad==0? 1: $venta_cantidad)), 2, '', false),
      MyString::formatoNumero($venta_importe, 2, '', false),
    ), false, 'B');


    // Existencia
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('EXISTENCIA'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(75, 25, 25, 25, 25, 30));
    $pdf->Row(array('CLASIF', 'UNIDAD', 'KILOS', 'CANTIDAD', 'COSTO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(75, 25, 25, 25, 25, 30));

    $existencia_kilos = $existencia_cantidad = $existencia_importe = 0;
    foreach ($caja['existencia'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $existencia_kilos    += floatval($existencia->kilos);
      $existencia_cantidad += floatval($existencia->cantidad);
      $existencia_importe  += floatval($existencia->importe);

      $pdf->SetX(6);
      $pdf->Row(array(
        $existencia->clasificacion,
        $existencia->unidad,
        MyString::formatoNumero($existencia->kilos, 2, '', false),
        MyString::formatoNumero($existencia->cantidad, 2, '', false),
        MyString::formatoNumero($existencia->costo, 2, '', false),
        MyString::formatoNumero($existencia->importe, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(6);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($existencia_kilos, 2, '', false),
      MyString::formatoNumero($existencia_cantidad, 2, '', false),
      MyString::formatoNumero(($existencia_importe/($existencia_cantidad==0? 1: $existencia_cantidad)), 2, '', false),
      MyString::formatoNumero($existencia_importe, 2, '', false),
    ), false, 'B');


    // COMPARA DE LIMON
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('COMPRA DE FRUTA'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'C', 'C', 'C'));
    $pdf->SetWidths(array(100, 30, 30, 40));
    $pdf->Row(array('CLASIF', 'KILOS', 'PRECIO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(100, 30, 30, 40));

    $compra_fruta_kilos = $compra_fruta_importe = 0;
    foreach ($caja['compra_fruta'] as $com_fruta) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $compra_fruta_kilos += floatval($com_fruta->kilos);
      $compra_fruta_importe += floatval($com_fruta->importe);

      $pdf->SetX(6);
      $pdf->Row(array(
        $com_fruta->calidad,
        MyString::formatoNumero($com_fruta->kilos, 2, '', false),
        MyString::formatoNumero($com_fruta->precio, 2, '', false),
        MyString::formatoNumero($com_fruta->importe, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(6);
    $pdf->Row(array(
      '',
      MyString::formatoNumero($compra_fruta_kilos, 2, '', false),
      MyString::formatoNumero(($compra_fruta_importe/($compra_fruta_kilos==0? 1: $compra_fruta_kilos)), 2, '', false),
      MyString::formatoNumero($compra_fruta_importe, 2, '', false),
    ), false, 'B');


    // Produccion
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('PRODUCCION'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(75, 25, 25, 25, 25, 30));
    $pdf->Row(array('CLASIF', 'UNIDAD', 'KILOS', 'CANTIDAD', 'COSTO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(75, 25, 25, 25, 25, 30));

    $produccion_kilos = $produccion_cantidad = $produccion_importe = 0;
    foreach ($caja['produccion'] as $produccion) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $produccion_kilos    += floatval($produccion->kilos);
      $produccion_cantidad += floatval($produccion->cantidad);
      $produccion_importe  += floatval($produccion->importe);

      $pdf->SetX(6);
      $pdf->Row(array(
        $produccion->clasificacion,
        $produccion->unidad,
        MyString::formatoNumero($produccion->kilos, 2, '', false),
        MyString::formatoNumero($produccion->cantidad, 2, '', false),
        MyString::formatoNumero($produccion->costo, 2, '', false),
        MyString::formatoNumero($produccion->importe, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(6);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($produccion_kilos, 2, '', false),
      MyString::formatoNumero($produccion_cantidad, 2, '', false),
      MyString::formatoNumero(($produccion_importe/($produccion_cantidad==0? 1: $produccion_cantidad)), 2, '', false),
      MyString::formatoNumero($produccion_importe, 2, '', false),
    ), false, 'B');


    // Existencia Anterior
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('EXISTENCIA ANTERIOR'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(75, 25, 25, 25, 25, 30));
    $pdf->Row(array('CLASIF', 'UNIDAD', 'KILOS', 'CANTIDAD', 'COSTO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(75, 25, 25, 25, 25, 30));

    $existencia_ant_kilos = $existencia_ant_cantidad = $existencia_ant_importe = 0;
    foreach ($caja['existencia_anterior'] as $existencia_ant) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $existencia_ant_kilos    += floatval($existencia_ant->kilos);
      $existencia_ant_cantidad += floatval($existencia_ant->cantidad);
      $existencia_ant_importe  += floatval($existencia_ant->importe);

      $pdf->SetX(6);
      $pdf->Row(array(
        $existencia_ant->clasificacion,
        $existencia_ant->unidad,
        MyString::formatoNumero($existencia_ant->kilos, 2, '', false),
        MyString::formatoNumero($existencia_ant->cantidad, 2, '', false),
        MyString::formatoNumero($existencia_ant->costo, 2, '', false),
        MyString::formatoNumero($existencia_ant->importe, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(6);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($existencia_ant_kilos, 2, '', false),
      MyString::formatoNumero($existencia_ant_cantidad, 2, '', false),
      MyString::formatoNumero(($existencia_ant_importe/($existencia_ant_cantidad==0? 1: $existencia_ant_cantidad)), 2, '', false),
      MyString::formatoNumero($existencia_ant_importe, 2, '', false),
    ), false, 'B');


    $pdf->page = count($pdf->pages);
    $pdf->Output('REPORTE_EXISTENCIAS.pdf', 'I');

  }

  public function saltaPag(&$pdf)
  {
    if($pdf->GetY() >= $pdf->limiteY){
      if (count($pdf->pages) > $pdf->page) {
        $pdf->page++;
        $pdf->SetXY(63, 10);
      } else
        $pdf->AddPage();
    }
  }


}

/* End of file caja_chica_model.php */
/* Location: ./application/models/caja_chica_model.php */