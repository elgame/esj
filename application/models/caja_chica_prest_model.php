<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class caja_chica_prest_model extends CI_Model {

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

  public function get($fecha, $noCaja, $all = false)
  {
    $info = array(
      'saldo_inicial'    => 0,
      'fondos_caja'      => array(),
      'prestamos_lp'     => array(),
      'prestamos'        => array(),
      'prestamos_dia'    => array(),
      'pagos'            => array(),
      'denominaciones'   => array(),
      'categorias'       => array(),
      'saldos_empleados' => array(),
      'traspasos'        => 0,
      'saldo_prest_fijo' => 0,
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

    $fondos_caja = $this->db->query(
      "SELECT cf.id_fondo, cf.fecha, cf.referencia, cf.tipo_movimiento, cf.no_caja, cf.monto, cf.no_impresiones, cc.id_categoria,
        cc.abreviatura AS categoria, e.nombre_fiscal AS empresa
      FROM otros.cajaprestamo_fondo cf
      INNER JOIN cajachica_categorias cc ON cc.id_categoria = cf.id_categoria
      LEFT JOIN empresas e ON e.id_empresa = cc.id_empresa
      WHERE cf.fecha <= '{$fecha}' AND cf.no_caja = {$noCaja}
      ORDER BY cf.fecha ASC"
    );

    if ($fondos_caja->num_rows() > 0)
    {
      $info['fondos_caja'] = $fondos_caja->result();
    }

    // Prestamos a largo plazo
    $prestamos = $this->db->query(
      "SELECT np.id_prestamo AS id_prestamo_nom, np.id_usuario AS id_empleado, cc.id_categoria,
        COALESCE(cc.abreviatura, e.nombre_fiscal) AS categoria,
        ('PTMO NOM ' || u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS empleado,
        Date(np.fecha) AS fecha, np.prestado AS monto, ceil(np.prestado/np.pago_semana) AS tno_pagos, np.referencia,
        (np.prestado-COALESCE(pai.saldo_ini, 0)) AS saldo_ini, COALESCE(pai.no_pagos, 0) AS no_pagos,
        COALESCE(abd.pago_dia, 0) AS pago_dia, abd.no_ticket, np.tipo
      FROM nomina_prestamos np
      INNER JOIN usuarios u ON u.id = np.id_usuario
      INNER JOIN empresas e ON e.id_empresa = u.id_empresa
      LEFT JOIN cajachica_categorias cc ON cc.id_empresa = e.id_empresa AND cc.status = 't'
      LEFT JOIN (
        SELECT np.id_prestamo, Sum(nfp.monto) AS saldo_ini, Count(*) AS no_pagos
        FROM nomina_fiscal_prestamos nfp
          INNER JOIN nomina_prestamos np ON np.id_prestamo = nfp.id_prestamo
        WHERE nfp.fecha < '{$fecha}'
        GROUP BY np.id_prestamo
      ) pai ON np.id_prestamo = pai.id_prestamo
      LEFT JOIN (
        SELECT id_prestamo, no_ticket, monto AS pago_dia
        FROM nomina_fiscal_prestamos
        WHERE fecha = '{$fecha}'
      ) abd ON np.id_prestamo = abd.id_prestamo
      WHERE np.close = 'f' AND Date(np.fecha) >= '2016-02-11' AND Date(np.fecha) < '{$fecha}'
      ORDER BY tipo ASC, categoria ASC, fecha ASC, id_prestamo_nom ASC"
    );

    if ($prestamos->num_rows() > 0)
    {
      $info['prestamos_lp'] = $prestamos->result();
      foreach ($info['prestamos_lp'] as $key => $item) {
        $item->tipo_nombre = $item->tipo==='fi'? 'Fiscal': 'Efectivo';
        $item->saldo_fin = $item->saldo_ini - $item->pago_dia;
        if ($item->pago_dia > 0)
          ++$item->no_pagos;
        if ($item->saldo_ini == 0)
          unset($info['prestamos_lp'][$key]);
      }
    }

    // Prestamo a corto plazo
    $prestamos = $this->db->query(
      "SELECT cp.id_prestamo, '' AS id_prestamo_nom, cp.id_empleado, cc.id_categoria, COALESCE(cc.abreviatura, '') AS categoria,
        (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS empleado,
        Date(cp.fecha) AS fecha, cp.monto, cp.concepto,
        (cp.monto-COALESCE(pai.saldo_ini, 0)) AS saldo_ini, COALESCE(pai.no_pagos, 0) AS no_pagos,
        COALESCE(abd.pago_dia, 0) AS pago_dia, abd.id_pago
      FROM otros.cajaprestamo_prestamos cp
        INNER JOIN usuarios u ON u.id = cp.id_empleado
        INNER JOIN cajachica_categorias cc ON cc.id_categoria = cp.id_categoria AND cc.status = 't'
        LEFT JOIN (
          SELECT np.id_prestamo, Sum(nfp.monto) AS saldo_ini, Count(*) AS no_pagos
          FROM otros.cajaprestamo_pagos nfp
            INNER JOIN otros.cajaprestamo_prestamos np ON np.id_prestamo = nfp.id_prestamo_caja
          WHERE nfp.fecha < '{$fecha}'
          GROUP BY np.id_prestamo
        ) pai ON cp.id_prestamo = pai.id_prestamo
        LEFT JOIN (
          SELECT id_prestamo_caja AS id_prestamo, id_pago, monto AS pago_dia
          FROM otros.cajaprestamo_pagos
          WHERE fecha = '{$fecha}'
        ) abd ON cp.id_prestamo = abd.id_prestamo
      WHERE cp.fecha < '{$fecha}' AND cp.no_caja = {$noCaja}
        AND ((cp.monto-COALESCE(pai.saldo_ini, 0))-COALESCE(abd.pago_dia, 0)) > 0
      ORDER BY fecha ASC, id_prestamo ASC"
    );

    if ($prestamos->num_rows() > 0)
    {
      $info['prestamos'] = $prestamos->result();
      foreach ($info['prestamos'] as $key => $item) {
        $item->saldo_fin = $item->saldo_ini-$item->pago_dia;
      }
    }

    // Prestamos a largo plazo de ese dia
    $prestamos = $this->db->query(
      "SELECT 0 AS id_prestamo, np.id_prestamo AS id_prestamo_nom, np.id_usuario AS id_empleado, cc.id_categoria, COALESCE(cc.abreviatura, e.nombre_fiscal) AS categoria,
        ('PTMO NOM ' || u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS empleado,
        Date(np.fecha) AS fecha, np.prestado AS monto, ceil(np.prestado/np.pago_semana) AS tno_pagos, np.referencia,
        (np.prestado-COALESCE(pai.saldo_ini, 0)) AS saldo_ini, COALESCE(pai.no_pagos, 0) AS no_pagos,
        COALESCE(abd.pago_dia, 0) AS pago_dia, abd.no_ticket, np.tipo
      FROM nomina_prestamos np
      INNER JOIN usuarios u ON u.id = np.id_usuario
      INNER JOIN empresas e ON e.id_empresa = u.id_empresa
      LEFT JOIN cajachica_categorias cc ON cc.id_empresa = e.id_empresa AND cc.status = 't'
      LEFT JOIN (
        SELECT np.id_prestamo, Sum(nfp.monto) AS saldo_ini, Count(*) AS no_pagos
        FROM nomina_fiscal_prestamos nfp
          INNER JOIN nomina_prestamos np ON np.id_prestamo = nfp.id_prestamo
        WHERE nfp.fecha < '{$fecha}'
        GROUP BY np.id_prestamo
      ) pai ON np.id_prestamo = pai.id_prestamo
      LEFT JOIN (
        SELECT id_prestamo, no_ticket, monto AS pago_dia
        FROM nomina_fiscal_prestamos
        WHERE fecha = '{$fecha}'
      ) abd ON np.id_prestamo = abd.id_prestamo
      WHERE Date(np.fecha) >= '2016-02-11' AND Date(np.fecha) = '{$fecha}'
      ORDER BY id_prestamo_nom ASC"
    );

    if ($prestamos->num_rows() > 0)
    {
      $info['prestamos_dia'] = $prestamos->result();
      foreach ($info['prestamos_dia'] as $key => $item) {
        $item->tipo_nombre = $item->tipo==='fi'? 'Fiscal': 'Efectivo';
        $item->saldo_fin = $item->saldo_ini-$item->pago_dia;
        if ($item->pago_dia > 0)
          ++$item->no_pagos;
        if ($item->saldo_fin == 0)
          unset($info['prestamos_dia'][$key]);
      }
    }

    // Prestamo a corto plazo de ese dia
    $prestamos = $this->db->query(
      "SELECT cp.id_prestamo, '' AS id_prestamo_nom, cp.id_empleado, cc.id_categoria, COALESCE(cc.abreviatura, '') AS categoria,
        (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS empleado,
        Date(cp.fecha) AS fecha, cp.monto, cp.concepto,
        (cp.monto-COALESCE(pai.saldo_ini, 0)) AS saldo_ini, COALESCE(pai.no_pagos, 0) AS no_pagos,
        COALESCE(abd.pago_dia, 0) AS pago_dia, abd.id_pago, 'ef' AS tipo
      FROM otros.cajaprestamo_prestamos cp
        INNER JOIN usuarios u ON u.id = cp.id_empleado
        INNER JOIN cajachica_categorias cc ON cc.id_categoria = cp.id_categoria AND cc.status = 't'
        LEFT JOIN (
          SELECT np.id_prestamo, Sum(nfp.monto) AS saldo_ini, Count(*) AS no_pagos
          FROM otros.cajaprestamo_pagos nfp
            INNER JOIN otros.cajaprestamo_prestamos np ON np.id_prestamo = nfp.id_prestamo_caja
          WHERE nfp.fecha < '{$fecha}'
          GROUP BY np.id_prestamo
        ) pai ON cp.id_prestamo = pai.id_prestamo
        LEFT JOIN (
          SELECT id_prestamo_caja AS id_prestamo, id_pago, monto AS pago_dia
          FROM otros.cajaprestamo_pagos
          WHERE fecha = '{$fecha}'
        ) abd ON cp.id_prestamo = abd.id_prestamo
      WHERE cp.fecha = '{$fecha}' AND cp.no_caja = {$noCaja}
        AND ((cp.monto-COALESCE(pai.saldo_ini, 0))-COALESCE(abd.pago_dia, 0)) >= 0
      ORDER BY id_prestamo ASC"
    );

    if ($prestamos->num_rows() > 0)
    {
      $prestamos_dia = $prestamos->result();
      foreach ($prestamos_dia as $key => $item) {
        $item->saldo_fin = $item->saldo_ini-$item->pago_dia;
        $info['prestamos_dia'][] = $item;
      }
    }

    // $pagos = $this->db->query(
    //   "SELECT id_pago, id_empleado, id_empresa, anio, semana, id_prestamo, id_categoria, concepto, monto, fecha, id_nomenclatura, categoria, nomenclatura
    //   FROM (
    //     SELECT cp.id_pago, cp.id_empleado, cp.id_empresa, cp.anio, cp.semana, cp.id_prestamo, cp.id_categoria, cp.concepto, cp.monto, cp.fecha,
    //       cp.id_nomenclatura, cc.abreviatura as categoria, cn.nomenclatura
    //     FROM otros.cajaprestamo_pagos cp
    //     INNER JOIN cajachica_categorias cc ON cc.id_categoria = cp.id_categoria
    //     INNER JOIN cajachica_nomenclaturas cn ON cn.id = cp.id_nomenclatura
    //     WHERE cp.fecha = '{$fecha}' AND cp.no_caja = {$noCaja}
    //     UNION
    //     SELECT cp.id_pago, np.id_empleado, np.id_empresa, np.anio, np.semana, np.id_prestamo, cp.id_categoria,
    //       (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno || '; Sem ' || np.semana) AS concepto,
    //       np.monto, np.fecha, cp.id_nomenclatura, null AS categoria, null AS nomenclatura
    //     FROM nomina_fiscal_prestamos np
    //     INNER JOIN nomina_prestamos npp ON npp.id_prestamo = np.id_prestamo
    //     INNER JOIN usuarios u ON u.id = np.id_empleado
    //     LEFT JOIN otros.cajaprestamo_pagos cp ON (cp.id_empleado = cp.id_empleado AND np.id_empresa = cp.id_empresa AND np.anio = cp.anio AND np.semana = cp.semana AND np.id_prestamo = cp.id_prestamo)
    //     WHERE np.saldado = 'f' AND (npp.tipo = 'ef') AND np.fecha = '{$fecha}' AND cp.id_pago IS NULL
    //   ) AS t
    //   ORDER BY id_pago ASC"
    // );

    // if ($pagos->num_rows() > 0)
    // {
    //   $info['pagos'] = $pagos->result();
    // }

    $saldos = $this->db->query("SELECT u.id, (u.nombre || ' ' || u.apellido_paterno || '' || u.apellido_materno) AS nombre,
        COALESCE(p.prestado, 0) AS prestado, COALESCE(pa.pagado, 0) AS pagado,
        (COALESCE(p.prestado, 0) - COALESCE(pa.pagado, 0)) AS saldo
      FROM usuarios u
      LEFT JOIN (
        SELECT np.id_usuario, Sum(np.prestado) AS prestado
        FROM nomina_prestamos np
          INNER JOIN usuarios u ON u.id = np.id_usuario
        WHERE (np.tipo = 'ef') AND Date(np.fecha) < '{$fecha}'
        GROUP BY np.id_usuario
      ) p ON u.id = p.id_usuario
      LEFT JOIN (
        SELECT nfp.id_empleado, Sum(nfp.monto) AS pagado
        FROM nomina_fiscal_prestamos nfp
          INNER JOIN nomina_prestamos np ON np.id_prestamo = nfp.id_prestamo
          INNER JOIN usuarios u ON u.id = np.id_usuario
        WHERE (np.tipo = 'ef') AND (Date(nfp.fecha) <= '{$fecha}' OR Date(nfp.fecha) IS NULL)
        GROUP BY nfp.id_empleado
      ) pa ON u.id = pa.id_empleado
      WHERE (COALESCE(p.prestado, 0) - COALESCE(pa.pagado, 0)) > 0");

    if ($saldos->num_rows() > 0)
    {
      $info['saldos_empleados'] = $saldos->result();
    }

    // Saldo prestamos fijos anterior
    $prest_fijo_sald = $this->db->query(
      "SELECT *
       FROM otros.cajaprestamo_efectivo
       WHERE fecha < '{$fecha}' AND no_caja = {$noCaja}
       ORDER BY fecha DESC LIMIT 1"
    )->row();
    if (isset($prest_fijo_sald->saldo_prest_fijo)) {
      $info['saldo_prest_fijo'] = $prest_fijo_sald->saldo_prest_fijo;
    }

    // Traspasos
    $traspasos = $this->getTraspasos($fecha, 'caja_prestamo', true, (!$all? " AND bt.status = 't'": ''));
    $info['traspasos'] = $traspasos;

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

  public function getTraspasos($fecha, $tno_caja, $total=false, $sql = '')
  {
    if ($total) {
      $traspaso = $this->db->query(
        "SELECT Sum(bt.monto) AS monto
         FROM public.cajachica_traspasos bt
         WHERE bt.fecha <= '{$fecha}' AND bt.tipo_caja = '{$tno_caja}' {$sql}"
      )->row();
      return floatval($traspaso->monto);
    }

    $traspaso = $this->db->query(
      "SELECT bt.id_traspaso, bt.concepto, bt.monto, bt.fecha, bt.no_caja, bt.no_impresiones,
        bt.id_usuario, bt.fecha_creacion, bt.afectar_fondo, bt.folio, bt.status,
        (NOT bt.tipo) AS tipo,
        false AS guardado, bt.tipo_caja
       FROM public.cajachica_traspasos bt
       WHERE bt.fecha = '{$fecha}' AND bt.tipo_caja = '{$tno_caja}' {$sql}
       ORDER BY bt.folio ASC"
    );

    return $traspaso->result();
  }

  public function guardar($data)
  {
    $fondoc = array();
    $fondoc_updt = array();

    // fondo caja DEUDORES DIVERSOS
    foreach ($data['fondo_id_categoria'] as $key => $id_cat)
    {
      if (isset($data['fondo_del'][$key]) && $data['fondo_del'][$key] == 'true' &&
        isset($data['fondo_id_fondo'][$key]) && floatval($data['fondo_id_fondo'][$key]) > 0) {
        // $gastos_ids['delets'][] = $this->getDataGasto($data['fondo_id_fondo'][$key]);

        $this->db->delete('otros.cajaprestamo_fondo', "id_fondo = ".$data['fondo_id_fondo'][$key]);
      } elseif ($data['fondo_id_fondo'][$key] > 0) {
        $prestamos_updt = array(
          'id_categoria'    => $id_cat,
          'fecha'           => $data['fondo_fecha'][$key],
          'referencia'      => $data['fondo_referencia'][$key],
          'tipo_movimiento' => ($data['fondo_ingreso'][$key]>0? 't': 'f'),
          'no_caja'         => $data['fno_caja'],
          'monto'           => ($data['fondo_ingreso'][$key]>0? $data['fondo_ingreso'][$key]: $data['fondo_egreso'][$key]),
        );
        $this->db->update('otros.cajaprestamo_fondo', $prestamos_updt, "id_fondo = ".$data['fondo_id_fondo'][$key]);
      } else {
        $fondoc[] = array(
          'id_empleado'     => $this->session->userdata('id_usuario'),
          'id_categoria'    => $id_cat,
          'fecha'           => $data['fondo_fecha'][$key],
          'referencia'      => $data['fondo_referencia'][$key],
          'tipo_movimiento' => ($data['fondo_ingreso'][$key]>0? 't': 'f'),
          'no_caja'         => $data['fno_caja'],
          'monto'           => ($data['fondo_ingreso'][$key]>0? $data['fondo_ingreso'][$key]: $data['fondo_egreso'][$key]),
        );
      }
    }

    if (count($fondoc) > 0)
    {
      $this->db->insert_batch('otros.cajaprestamo_fondo', $fondoc);
    }

    $prestamos = array();
    $prestamos_updt = array();

    // prestamos
    foreach ($data['prestamo_monto'] as $key => $ingreso)
    {
      if (isset($data['prestamo_del'][$key]) && $data['prestamo_del'][$key] == 'true' &&
        isset($data['prestamo_id_prestamo'][$key]) && floatval($data['prestamo_id_prestamo'][$key]) > 0) {
        // $gastos_ids['delets'][] = $this->getDataGasto($data['prestamo_id_prestamo'][$key]);

        $this->db->delete('otros.cajaprestamo_prestamos', "id_prestamo = ".$data['prestamo_id_prestamo'][$key]);
      } elseif ($data['prestamo_id_prestamo'][$key] > 0) {
        $prestamos_updt = array(
          'id_prestamo_nom' => ($data['prestamo_id_prestamo_nom'][$key]!=''? $data['prestamo_id_prestamo_nom'][$key]: NULL),
          'id_empleado'     => ($data['prestamo_empleado_id'][$key]!=''? $data['prestamo_empleado_id'][$key]: NULL),
          'id_categoria'    => $data['prestamo_empresa_id'][$key],
          // 'id_nomenclatura' => $data['prestamo_nomenclatura'][$key],
          'concepto'        => $data['prestamo_concepto'][$key],
          // 'fecha'           => $data['fecha_caja_chica'],
          'monto'           => $data['prestamo_monto'][$key],
          // 'no_caja'         => $data['fno_caja'],
        );
        $this->db->update('otros.cajaprestamo_prestamos', $prestamos_updt, "id_prestamo = ".$data['prestamo_id_prestamo'][$key]);
      } else {
        $prestamos[] = array(
          // 'id_prestamo' => $data['prestamo_id_prestamo'][$key],
          'id_prestamo_nom' => ($data['prestamo_id_prestamo_nom'][$key]!=''? $data['prestamo_id_prestamo_nom'][$key]: NULL),
          'id_empleado'     => ($data['prestamo_empleado_id'][$key]!=''? $data['prestamo_empleado_id'][$key]: NULL),
          'id_categoria'    => $data['prestamo_empresa_id'][$key],
          // 'id_nomenclatura' => $data['prestamo_nomenclatura'][$key],
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
      if (isset($data['pago_del'][$key]) && $data['pago_del'][$key] == 'true' &&
        isset($data['pago_id'][$key]) && floatval($data['pago_id'][$key]) > 0) {
        // $gastos_ids['delets'][] = $this->getDataGasto($data['pago_id'][$key]);

        $this->db->delete('otros.cajaprestamo_pagos', "id_pago = ".$data['pago_id'][$key]);
      } elseif ($data['pago_id'][$key] > 0) {
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

  public function guardarPago($data)
  {
    $pagos = array(
          // 'id_empleado'     => ($data['pago_id_empleado'][$key]!=''? $data['pago_id_empleado'][$key]: NULL),
          // 'id_empresa'      => ($data['pago_id_empresa'][$key]!=''? $data['pago_id_empresa'][$key]: NULL),
          // 'anio'            => ($data['pago_anio'][$key]!=''? $data['pago_anio'][$key]: NULL),
          // 'semana'          => ($data['pago_semana'][$key]!=''? $data['pago_semana'][$key]: NULL),
          'id_prestamo_caja' => $data['id_prestamo_caja'],
          'id_categoria'     => $data['id_categoria'],
          'concepto'         => $data['concepto'],
          'monto'            => $data['monto'],
          'fecha'            => $data['fecha'],
          'no_caja'          => $data['no_caja'],
          // 'id_nomenclatura' => $data['pago_nomenclatura'][$key],
        );
    $this->db->insert('otros.cajaprestamo_pagos', $pagos);
  }

  public function cerrarCaja($idCaja, $noCajas)
  {
    $this->db->update('otros.cajaprestamo_efectivo', array('status' => 'f'), array('id_efectivo' => $idCaja));
    return true;
  }

  public function saldarPrestamosEmpleado($empleadoId, $fecha)
  {
    $prestamos = $this->db->query("SELECT np.id_prestamo, u.id_empresa, np.id_usuario, np.prestado,
        COALESCE(Sum(nfp.monto), 0) AS pagado, (np.prestado-COALESCE(Sum(nfp.monto), 0)) AS saldo
      FROM nomina_prestamos np
        INNER JOIN usuarios u ON u.id = np.id_usuario
        LEFT JOIN nomina_fiscal_prestamos nfp ON np.id_prestamo = nfp.id_prestamo
      WHERE np.id_usuario = {$empleadoId} AND (np.tipo = 'ef') AND Date(np.fecha) < '{$fecha}'
      GROUP BY np.id_prestamo, u.id
      HAVING (np.prestado-COALESCE(Sum(nfp.monto), 0)) > 0")->result();

    $semana = MyString::obtenerSemanaDeFecha($fecha);

    $prestamosEmpleados = array();
    foreach ($prestamos as $key => $value) {
      $prestamosEmpleados[] = array(
              'id_empleado' => $empleadoId,
              'id_empresa'  => $value->id_empresa,
              'anio'        => $semana['anio'],
              'semana'      => $semana['semana'],
              'id_prestamo' => $value->id_prestamo,
              'monto'       => $value->saldo,
              'fecha'       => $fecha,
              'saldado'     => 't',
            );
      $this->db->update('nomina_prestamos', array('status' => 'f'), "id_prestamo = {$value->id_prestamo}");
    }
    if (count($prestamosEmpleados) > 0)
      $this->db->insert_batch('nomina_fiscal_prestamos', $prestamosEmpleados);
  }

  public function saldarPrestamo($prestamoId, $fecha)
  {
    $prestamos = $this->db->query("SELECT np.id_prestamo, u.id_empresa, np.id_usuario, np.prestado,
        COALESCE(Sum(nfp.monto), 0) AS pagado, (np.prestado-COALESCE(Sum(nfp.monto), 0)) AS saldo
      FROM nomina_prestamos np
        INNER JOIN usuarios u ON u.id = np.id_usuario
        LEFT JOIN nomina_fiscal_prestamos nfp ON np.id_prestamo = nfp.id_prestamo
      WHERE np.id_prestamo = {$prestamoId}
      GROUP BY np.id_prestamo, u.id
      HAVING (np.prestado-COALESCE(Sum(nfp.monto), 0)) > 0")->result();

    $semana = MyString::obtenerSemanaDeFecha($fecha);

    $prestamosEmpleados = array();
    foreach ($prestamos as $key => $value) {
      $prestamosEmpleados[] = array(
              'id_empleado' => $value->id_usuario,
              'id_empresa'  => $value->id_empresa,
              'anio'        => $semana['anio'],
              'semana'      => $semana['semana'],
              'id_prestamo' => $value->id_prestamo,
              'monto'       => $value->saldo,
              'fecha'       => $fecha,
              'saldado'     => 't',
            );
      $this->db->update('nomina_prestamos', array('status' => 'f'), "id_prestamo = {$value->id_prestamo}");
    }
    if (count($prestamosEmpleados) > 0) {
      $this->db->insert_batch('nomina_fiscal_prestamos', $prestamosEmpleados);
    }
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
    $pdf->SetWidths(array(204));
    $pdf->Row(array('REPORTE CAJA PRESTAMOS'), true, true, null, 3);

    $pdf->Image(APPPATH.(str_replace(APPPATH, '', '/images/logo.png')), 6, 15, 50);
    $pdf->Ln(20);

    $pdf->SetFont('Arial','B', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(240, 240, 240);

    // Fecha
    $pdf->SetXY(6, $pdf->GetY() - 20);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('FECHA ' . $fecha), false, false);

    $fondo_cajaa = 0;
    foreach ($caja['fondos_caja'] as $fondoc) {
      $fondo_cajaa = ($fondoc->tipo_movimiento=='t'? $fondo_cajaa+$fondoc->monto: $fondo_cajaa-$fondoc->monto);
    }

    // Saldo inicial
    $pdf->SetXY(6, $pdf->GetY() + 5);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('FONDO DE CAJA '.MyString::formatoNumero($fondo_cajaa, 2, '$', false)), false, false);

    $pdf->auxy = $pdf->GetY();
    $page_aux = $pdf->page;

    // Deudores diversos
    $pdf->SetFont('Arial','B', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, 32);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('DEUDORES DIVERSOS'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'C', 'C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(25, 55, 16, 35, 20, 20, 20, 13));
    $pdf->Row(array('EMPRESA', 'FONDO DE CAJA', 'FECHA', 'REFERENCIA', 'INGRESOS', 'EGRESOS', 'SALDOS', 'TICKET'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'C', 'C', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(25, 55, 16, 35, 20, 20, 20, 13));

    $totalfondo = $total_prestamos = 0;
    $saldofc = 0;
    foreach ($caja['fondos_caja'] as $fondoc) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $totalfondo += floatval($fondoc->monto);
      $saldofc = ($fondoc->tipo_movimiento=='t'? $saldofc+$fondoc->monto: $saldofc-$fondoc->monto);

      $pdf->SetX(6);
      $pdf->Row(array(
        $fondoc->categoria,
        $fondoc->empresa,
        $fondoc->fecha,
        $fondoc->referencia,
        MyString::formatoNumero(($fondoc->tipo_movimiento=='t'? $fondoc->monto: ''), 2, '', false),
        MyString::formatoNumero(($fondoc->tipo_movimiento=='f'? $fondoc->monto: ''), 2, '', false),
        MyString::formatoNumero($saldofc, 2, '', false),
        $fondoc->id_fondo
      ), false, 'B');
    }

    // PRESTAMOS A LARGO PLAZO
    $pdf->SetFont('Arial','B', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(206));
    $pdf->SetXY(6, $pdf->GetY()+3);
    $pdf->Row(array('PRESTAMOS A LARGO PLAZO'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(20, 48, 16, 30, 18, 18, 18, 10, 10, 18));
    $pdf->Row(array('EMPRESA', 'TRABAJADOR', 'FECHA', 'REFERENCIA', 'CARGO PRESTAMOS', 'SALDOS INICIALES', 'ABONO DEL DIA', 'No.', 'TICKET', 'SALDOS FINALES'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'C', 'C', 'R', 'R', 'R', 'C', 'R', 'R'));
    $pdf->SetWidths(array(20, 48, 16, 30, 18, 18, 18, 10, 10, 18));

    $tipoo = '';
    $totalpreslp_salini = $totalpreslp_pago_dia = 0;
    $totalpreslp_salfin = 0;
    $totalpreslp_salini_fi = $totalpreslp_pago_dia_fi = $totalpreslp_salfin_fi = 0;
    $totalpreslp_salini_ef = $totalpreslp_pago_dia_ef = $totalpreslp_salfin_ef = 0;
    $totalpreslp_salini_efd = $totalpreslp_pago_dia_efd = $totalpreslp_salfin_efd = 0;
    $totalpreslp_ef_rec = [];
    foreach ($caja['prestamos_lp'] as $prestamo) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $totalpreslp_salini += floatval($prestamo->saldo_ini);
      $totalpreslp_pago_dia += floatval($prestamo->pago_dia);
      $totalpreslp_salfin      += floatval($prestamo->saldo_fin);
      if ($prestamo->tipo == 'efd') {
        $totalpreslp_salini_efd += floatval($prestamo->saldo_ini);
        $totalpreslp_pago_dia_efd += floatval($prestamo->pago_dia);
        $totalpreslp_salfin_efd += floatval($prestamo->saldo_fin);

        if (isset($totalpreslp_ef_rec[$prestamo->categoria])) {
          $totalpreslp_ef_rec[$prestamo->categoria] += $prestamo->pago_dia;
        } else {
          $totalpreslp_ef_rec[$prestamo->categoria] = $prestamo->pago_dia;
        }
      } elseif ($prestamo->tipo == 'ef') {
        $totalpreslp_salini_ef += floatval($prestamo->saldo_ini);
        $totalpreslp_pago_dia_ef += floatval($prestamo->pago_dia);
        $totalpreslp_salfin_ef += floatval($prestamo->saldo_fin);

        // if (isset($totalpreslp_ef_rec[$prestamo->categoria])) {
        //   $totalpreslp_ef_rec[$prestamo->categoria] += $prestamo->pago_dia;
        // } else {
        //   $totalpreslp_ef_rec[$prestamo->categoria] = $prestamo->pago_dia;
        // }
      } else {
        $totalpreslp_salini_fi += floatval($prestamo->saldo_ini);
        $totalpreslp_pago_dia_fi += floatval($prestamo->pago_dia);
        $totalpreslp_salfin_fi += floatval($prestamo->saldo_fin);
      }

      if ($tipoo != $prestamo->tipo && $prestamo->tipo != 'mt') {
        switch ($prestamo->tipo) {
          case 'efd': $tipo = 'Efectivo Fijo'; break;
          case 'ef': $tipo = 'Efectivo'; break;
          default: $tipo = 'Fiscal'; break;
        }
        $tipoo = $prestamo->tipo;

        $pdf->SetFillColor(240, 240, 240);
        $pdf->SetWidths(array(206));
        $pdf->SetFont('Arial', 'B', 7);
        $pdf->SetX(6);
        $pdf->Row(array($tipo), true, 'B');
      }

      $pdf->SetWidths(array(20, 48, 16, 30, 18, 18, 18, 10, 10, 18));
      $pdf->SetFont('Arial','', 7);
      $pdf->SetX(6);
      $pdf->Row(array(
        $prestamo->categoria,
        $prestamo->empleado,
        MyString::fechaAT($prestamo->fecha),
        $prestamo->referencia.' '.($prestamo->tipo_nombre),
        MyString::formatoNumero($prestamo->monto, 2, '', false),
        MyString::formatoNumero($prestamo->saldo_ini, 2, '', false),
        MyString::formatoNumero($prestamo->pago_dia, 2, '', false),
        $prestamo->no_pagos.'/'.$prestamo->tno_pagos,
        $prestamo->no_ticket,
        MyString::formatoNumero($prestamo->saldo_fin, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(120);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetAligns(array('R', 'R', 'R', 'C', 'R', 'R'));
    $pdf->SetWidths(array(18, 18, 18, 10, 10, 18));
    $pdf->Row(array('SUMAS',
      MyString::formatoNumero($totalpreslp_salini, 2, '$', false),
      MyString::formatoNumero($totalpreslp_pago_dia, 2, '$', false),
      '', '',
      MyString::formatoNumero($totalpreslp_salfin, 2, '$', false),
      ), true, 'B');
    $pdf->SetX(120);
    $pdf->Row(array('Fiscal',
      MyString::formatoNumero($totalpreslp_salini_fi, 2, '$', false),
      MyString::formatoNumero($totalpreslp_pago_dia_fi, 2, '$', false),
      '', '',
      MyString::formatoNumero($totalpreslp_salfin_fi, 2, '$', false),
      ), true, 'B');
    $pdf->SetX(120);
    $pdf->Row(array('Efectivo',
      MyString::formatoNumero($totalpreslp_salini_ef, 2, '$', false),
      MyString::formatoNumero($totalpreslp_pago_dia_ef, 2, '$', false),
      '', '',
      MyString::formatoNumero($totalpreslp_salfin_ef, 2, '$', false),
      ), true, 'B');
    $pdf->SetX(120);
    $pdf->Row(array('Efectivo Fijo',
      MyString::formatoNumero($totalpreslp_salini_efd, 2, '$', false),
      MyString::formatoNumero($totalpreslp_pago_dia_efd, 2, '$', false),
      '', '',
      MyString::formatoNumero($totalpreslp_salfin_efd, 2, '$', false),
      ), true, 'B');


    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(68));
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(6);
    $pdf->Row(array('Recuperar Efectivo Fijo'), true, 'B');

    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(20, 48));
    $pdf->SetX(6);
    $pdf->Row(array('Saldo Anterior', MyString::formatoNumero($caja['saldo_prest_fijo'], 2, '$', false)), false, 'B');

    $total_prestamos_recuperar = 0;
    if (count($totalpreslp_ef_rec) > 0) {
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(68));
      $pdf->SetX(6);
      $pdf->Row(array('Cobro Prestamos Fijos'), true, 'B');

      foreach ($totalpreslp_ef_rec as $key => $value) {
        if ($value > 0) {
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(20, 48));
          $pdf->SetFont('Arial', 'B', 7);
          $pdf->SetX(6);
          $pdf->Row(array($key, MyString::formatoNumero($value, 2, '$', false)), false, 'B');

          $total_prestamos_recuperar += $value;
        }
      }
    }

    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(20, 48));
    $pdf->SetX(6);
    $pdf->Row(array('Traspasos', MyString::formatoNumero($caja['traspasos'], 2, '$', false)), false, 'B');

    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(20, 48));
    $pdf->SetX(6);
    $pdf->Row(array('Saldo', MyString::formatoNumero(($caja['saldo_prest_fijo']+$total_prestamos_recuperar-$caja['traspasos']), 2, '$', false) ), false, 'B');


    // PRESTAMOS A CORTO PLAZO
    $pdf->SetFont('Arial','B', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->SetXY(6, $pdf->GetY()+3);
    $pdf->Row(array('PRESTAMOS A CORTO PLAZO'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(20, 48, 16, 30, 18, 18, 18, 10, 10, 18));
    $pdf->Row(array('EMPRESA', 'TRABAJADOR', 'FECHA', 'REFERENCIA', 'CARGO PRESTAMOS', 'SALDOS INICIALES', 'ABONO DEL DIA', 'No.', 'TICKET', 'SALDOS FINALES'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'C', 'C', 'R', 'R', 'R', 'C', 'R', 'R'));
    $pdf->SetWidths(array(20, 48, 16, 30, 18, 18, 18, 10, 10, 18));

    $totalprestamos = $totalprescp_salini = 0;
    $totalprescp_pago_dia = $totalprescp_salfin = 0;
    foreach ($caja['prestamos'] as $prestamo) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $totalprestamos += floatval($prestamo->monto);
      $totalprescp_salini += floatval($prestamo->saldo_ini);
      $totalprescp_pago_dia += floatval($prestamo->pago_dia);
      $totalprescp_salfin += floatval($prestamo->saldo_fin);

      $pdf->SetX(6);
      $pdf->Row(array(
        $prestamo->categoria,
        $prestamo->empleado,
        MyString::fechaAT($prestamo->fecha),
        $prestamo->concepto,
        MyString::formatoNumero($prestamo->monto, 2, '', false),
        MyString::formatoNumero($prestamo->saldo_ini, 2, '', false),
        MyString::formatoNumero($prestamo->pago_dia, 2, '', false),
        $prestamo->no_pagos,
        $prestamo->id_pago,
        MyString::formatoNumero($prestamo->saldo_fin, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(120);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetAligns(array('R', 'R', 'R', 'C', 'R', 'R'));
    $pdf->SetWidths(array(18, 18, 18, 10, 10, 18));
    $pdf->Row(array('SUMAS',
      MyString::formatoNumero($totalprescp_salini, 2, '$', false),
      MyString::formatoNumero($totalprescp_pago_dia, 2, '$', false),
      '', '',
      MyString::formatoNumero($totalprescp_salfin, 2, '$', false),
      ), true, 'B');

    // PRESTAMOS DEL DIA
    $pdf->SetFont('Arial','B', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->SetXY(6, $pdf->GetY()+3);
    $pdf->Row(array('PRESTAMOS DEL DIA'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(20, 48, 16, 30, 18, 18, 18, 10, 10, 18));
    $pdf->Row(array('EMPRESA', 'TRABAJADOR', 'FECHA', 'REFERENCIA', 'CARGO PRESTAMOS', 'SALDOS INICIALES', 'ABONO DEL DIA', 'No.', 'TICKET', 'SALDOS FINALES'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'C', 'C', 'R', 'R', 'R', 'C', 'R', 'R'));
    $pdf->SetWidths(array(20, 48, 16, 30, 18, 18, 18, 10, 10, 18));

    $totalpreslgcp_monto = $totalpreslgcp_salini = $totalpreslgcp_pago_dia = $totalpreslgcp_salfin_fi = $totalpreslgcp_salfin_ef = 0;
    foreach ($caja['prestamos_dia'] as $prestamo) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $totalpreslgcp_monto    += floatval($prestamo->monto);
      $totalpreslgcp_salini   += floatval($prestamo->saldo_ini);
      $totalpreslgcp_pago_dia += floatval($prestamo->pago_dia);
      if ($prestamo->tipo == 'fi') {
        $totalpreslgcp_salfin_fi   += floatval($prestamo->saldo_fin); // fiscal
      } else {
        $totalpreslgcp_salfin_ef   += floatval($prestamo->saldo_fin); // efectivo
      }

      $pdf->SetX(6);

      if (isset($prestamo->id_prestamo) && $prestamo->id_prestamo > 0) { // corto plazo
        $pdf->Row(array(
          $prestamo->categoria,
          $prestamo->empleado,
          MyString::fechaAT($prestamo->fecha),
          $prestamo->concepto,
          MyString::formatoNumero($prestamo->monto, 2, '', false),
          MyString::formatoNumero($prestamo->saldo_ini, 2, '', false),
          MyString::formatoNumero($prestamo->pago_dia, 2, '', false),
          $prestamo->no_pagos,
          $prestamo->id_pago,
          MyString::formatoNumero($prestamo->saldo_fin, 2, '', false),
        ), false, 'B');
      } else {
        $pdf->Row(array(
          $prestamo->categoria,
          $prestamo->empleado,
          MyString::fechaAT($prestamo->fecha),
          $prestamo->referencia.' '.($prestamo->tipo_nombre),
          MyString::formatoNumero($prestamo->monto, 2, '', false),
          MyString::formatoNumero($prestamo->saldo_ini, 2, '', false),
          MyString::formatoNumero($prestamo->pago_dia, 2, '', false),
          $prestamo->no_pagos.'/'.$prestamo->tno_pagos,
          $prestamo->no_ticket,
          MyString::formatoNumero($prestamo->saldo_fin, 2, '', false),
        ), false, 'B');
      }
    }

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(120);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetAligns(array('R', 'R', 'R', 'C', 'R', 'R'));
    $pdf->SetWidths(array(18, 18, 18, 10, 10, 18));
    $pdf->Row(array('SUMAS',
      MyString::formatoNumero($totalpreslgcp_salini, 2, '$', false),
      MyString::formatoNumero($totalpreslgcp_pago_dia, 2, '$', false),
      '', '',
      MyString::formatoNumero(($totalpreslgcp_salfin_fi+$totalpreslgcp_salfin_ef), 2, '$', false),
      ), true, 'B');

    $tt_saldo_inicial       = $totalpreslp_salini+$totalprescp_salini;
    $tt_saldo_finales       = $totalpreslp_salfin+$totalprescp_salfin+$totalpreslgcp_salfin_fi+$totalpreslgcp_salfin_ef;
    $tt_efectivo_anterior   = $saldofc-$tt_saldo_inicial;
    $tt_caja_ingreso        = $totalpreslp_pago_dia+$totalprescp_pago_dia+$totalpreslgcp_pago_dia;
    $tt_caja_egreso         = $totalpreslgcp_monto;
    $tt_efectivo_disponible = $tt_efectivo_anterior+$tt_caja_ingreso-$tt_caja_egreso;

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetXY(120, $pdf->GetY()+3);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetAligns(array('R', 'R', 'R', 'C', 'R', 'R'));
    $pdf->SetWidths(array(18, 18, 18, 10, 10, 18));
    $pdf->Row(array('TOTALES',
      MyString::formatoNumero($tt_saldo_inicial, 2, '$', false),
      MyString::formatoNumero($tt_caja_ingreso, 2, '$', false),
      '', '',
      MyString::formatoNumero($tt_saldo_finales, 2, '$', false),
      ), true, 'B');

    $y_aux2 = $pdf->GetY();
    $page_aux2 = $pdf->page;
    // Tabulaciones
    $pdf->SetFont('Arial','B', 6);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(210, 210, 210);
    $pdf->SetXY(6, $pdf->GetY() + 5);
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
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      // $pdf->SetFont('Helvetica','', 7);
      $pdf->SetX(6);

      $pdf->Row(array(
        $denominacion['cantidad'],
        $denominacion['denominacion'],
        MyString::formatoNumero($denominacion['total'], 2, '', false)), false, true);

      $totalEfectivo += floatval($denominacion['total']);
    }

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C', 'R'));
    $pdf->SetWidths(array(31, 25));
    $pdf->Row(array('TOTAL EFECTIVO', MyString::formatoNumero($totalEfectivo, 2, '$', false)), false, true);

    $pdf->page = $page_aux2;
    $pdf->SetY($y_aux2);
    $pdf->SetFont('Arial', 'B', 6);
    $pdf->SetAligns(array('R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(25, 19, 40, 19));

    if($pdf->GetY() >= $pdf->limiteY){
      if (count($pdf->pages) > $pdf->page) {
        $pdf->page++;
        $pdf->SetXY(63, 10);
      } else
        $pdf->AddPage();
    }
    $pdf->SetXY(63, $pdf->GetY()+15);
    $pdf->Row(array('SALDO INICIAL', MyString::formatoNumero($tt_saldo_inicial, 2, '$', false),
                    'PTMO A LARGO PLAZO', MyString::formatoNumero($totalpreslp_salfin_ef, 2, '$', false)), false, false);
    $this->saltaPag($pdf);
    $pdf->SetX(63);
    $pdf->Row(array('EFECTIVO ANTERIOR', MyString::formatoNumero($tt_efectivo_anterior, 2, '$', false),
                    'PTMO A CORTO PLAZO', MyString::formatoNumero($totalprescp_salfin, 2, '$', false)), false, false);
    $this->saltaPag($pdf);
    $pdf->SetX(63);
    $pdf->Row(array('CAJA INGRESOS ', MyString::formatoNumero($tt_caja_ingreso, 2, '$', false),
                    'PTMO DEL DIA FI', MyString::formatoNumero($totalpreslgcp_salfin_fi, 2, '$', false)), false, false);
    $this->saltaPag($pdf);
    $pdf->SetX(63);
    $pdf->Row(array('CAJA EGRESOS', MyString::formatoNumero($totalpreslgcp_monto, 2, '$', false),
                    'PTMO DEL DIA EF', MyString::formatoNumero($totalpreslgcp_salfin_ef, 2, '$', false)), false, false);
    $this->saltaPag($pdf);
    $pdf->SetX(63);
    $pdf->Row(array('EFECTIVO DISPONIBLE', MyString::formatoNumero($tt_efectivo_disponible, 2, '$', false),
                    'TABULACION DE EFECTIVO', MyString::formatoNumero($totalEfectivo, 2, '$', false)), false, false);
    $this->saltaPag($pdf);
    $pdf->SetX(63);
    $pdf->Row(array('DIFERENCIA DEL CORTE', MyString::formatoNumero($tt_efectivo_disponible-$totalEfectivo, 2, '$', false),
                    'TOTAL', MyString::formatoNumero($totalpreslp_salfin_ef+$totalprescp_salfin+$totalEfectivo+$totalpreslgcp_salfin_ef, 2, '$', false)), false, false);

    $pdf->SetWidths(array(25, 19));
    $this->saltaPag($pdf);
    $pdf->SetX(63);
    $pdf->Row(array('FONDO DE CAJA', MyString::formatoNumero(($totalEfectivo+($tt_efectivo_disponible-$totalEfectivo)+$tt_saldo_finales), 2, '$', false)), false, false);

    $pdf->page = count($pdf->pages);
    $pdf->Output('CAJA_CHICA.pdf', 'I');

    // // Saldos
    // $pdf->SetFont('Arial','B', 7);
    // $pdf->SetTextColor(0, 0, 0);
    // $pdf->SetFillColor(230, 230, 230);
    // $pdf->SetXY(111, $pdf->auxy);
    // $pdf->SetAligns(array('L', 'C'));
    // $pdf->SetWidths(array(79, 25));
    // $pdf->Row(array('SALDO EMPLEADOS', 'IMPORTE'), true, true);

    // $pdf->SetFont('Arial','', 6);
    // $pdf->SetX(111);
    // $pdf->SetAligns(array('C', 'C', 'C', 'C'));
    // $pdf->SetWidths(array(44, 20, 20, 20));
    // $pdf->Row(array('NOMBRE', 'PRESTADO', 'PAGADO', 'SALDO'), true, true);

    // $pdf->SetFont('Arial','', 6);
    // $pdf->SetXY(111, $pdf->GetY());
    // $pdf->SetAligns(array('L', 'R', 'R', 'R'));
    // $pdf->SetWidths(array(44, 20, 20, 20));

    // $totalempsaldos = 0;
    // foreach ($caja['saldos_empleados'] as $key => $empsaldo)
    // {
    //   if($pdf->GetY() >= $pdf->limiteY){
    //     if (count($pdf->pages) > $pdf->page) {
    //       $pdf->page++;
    //       $pdf->SetXY(111, 10);
    //     } else
    //       $pdf->AddPage();
    //   }

    //   $pdf->SetX(111);

    //   $pdf->Row(array(
    //     $empsaldo->nombre,
    //     MyString::formatoNumero($empsaldo->prestado, 2, '', false),
    //     MyString::formatoNumero($empsaldo->pagado, 2, '', false),
    //     MyString::formatoNumero($empsaldo->saldo, 2, '', false)), false, true);

    //   $totalempsaldos += floatval($empsaldo->saldo);
    // }

    // $pdf->SetFont('Arial', 'B', 7);
    // $pdf->SetX(111);
    // $pdf->SetFillColor(255, 255, 255);
    // $pdf->SetAligns(array('L', 'R', 'L', 'R'));
    // $pdf->Row(array('', '', 'TOTAL', MyString::formatoNumero($totalempsaldos, 2, '$', false)), true, true);

    // $pdf->Output('CAJA_CHICA.pdf', 'I');
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
          COALESCE(cca.nombre, ca.nombre) AS nombre_codigo,
          COALESCE((CASE WHEN cca.codigo <> '' THEN cca.codigo ELSE cca.nombre END), ca.codigo_fin) AS codigo_fin,
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
    $pdf->Row(array('Caja: '.$gastos->no_caja, MyString::formatoNumero($gastos->monto, 2, '$', false) ), false, false);

    $pdf->SetX(0);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->Row(array('CANTIDAD:'), false, false);
    $pdf->SetX(0);
    $pdf->Row(array(MyString::num2letras($gastos->monto)), false, false);
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
    $pdf->Row(array('', '', MyString::fechaAT($gastos->fecha)), false, false);
    $pdf->Line(0, $pdf->GetY()+4, 62, $pdf->GetY()+4);
    $pdf->Line(21, $pdf->GetY()-12, 21, $pdf->GetY()+4);
    $pdf->Line(42, $pdf->GetY()-12, 42, $pdf->GetY()+4);

    $pdf->AutoPrint(true);
    $pdf->Output();
  }

  public function printFondo($id_fondo)
  {
    $fondoc = $this->db->query(
      "SELECT cf.id_fondo, cf.fecha, cf.referencia, cf.tipo_movimiento, cf.no_caja, cf.monto, cf.no_impresiones, cc.id_categoria,
        cc.abreviatura AS categoria, e.nombre_fiscal AS empresa, (u.nombre || ' ' || u.apellido_paterno) AS registro
      FROM otros.cajaprestamo_fondo cf
      INNER JOIN usuarios u ON u.id = cf.id_empleado
      INNER JOIN cajachica_categorias cc ON cc.id_categoria = cf.id_categoria
      LEFT JOIN empresas e ON e.id_empresa = cc.id_empresa
      WHERE cf.id_fondo = {$id_fondo}"
    )->row();

    // echo "<pre>";
    //   var_dump($fondoc);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 130));
    $pdf->show_head = false;

    // $pdf->AddPage();
    $pdf->SetFont('helvetica','', 8);
    $pdf->SetXY(0, 0);

    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()-5);
    $pdf->Row(array('       CAJA DE PRESTAMOS'), false, false);
    $pdf->SetAligns(array('C'));
    $pdf->SetXY(0, $pdf->GetY()-3);
    $pdf->Row(array('TICKET FONDO DE CAJA'), false, false);
    $pdf->SetXY(0, $pdf->GetY()-3);
    $pdf->Row(array( ($fondoc->tipo_movimiento=='t'? 'INGRESO': 'EGRESO') ), false, false);

    $pdf->SetWidths(array(30, 33));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('Folio: '.$fondoc->id_fondo, MyString::fechaAT($fondoc->fecha)), false, false);

    $pdf->SetWidths(array(20, 43));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetX(0);
    $pdf->Row(array('Caja: '.$fondoc->no_caja, MyString::formatoNumero($fondoc->monto, 2, '$', false) ), false, false);

    $pdf->SetX(0);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->Row(array('CANTIDAD:'), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array(MyString::num2letras($fondoc->monto)), false, false);
    $pdf->SetX(0);
    $pdf->Line(0, $pdf->GetY()-1, 62, $pdf->GetY()-1);

    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->Row(array('EMPRESA:'), false, false);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array($fondoc->empresa." ({$fondoc->categoria})"), false, false);

    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('REFERENCIA: '. $fondoc->referencia), false, false);

    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('REGISTRO: '. $fondoc->registro), false, false);

    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row(array(($fondoc->no_impresiones>0? 'COPIA No '. $fondoc->no_impresiones: 'ORIGINAL')), false, false);

    $pdf->AutoPrint(true);
    $pdf->Output();
  }

  public function printPrestamoLp($ticket, $fecha)
  {
    $fondoc = $this->db->query(
      "SELECT np.id_prestamo AS id_prestamo_nom, np.id_usuario AS id_empleado, cc.id_categoria, COALESCE(cc.abreviatura, e.nombre_fiscal) AS categoria,
        ('PTMO NOM ' || u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS empleado,
        Date(abd.fecha) AS fecha, np.prestado AS monto, (np.prestado/np.pago_semana) AS tno_pagos, np.referencia,
        (np.prestado-COALESCE(pai.saldo_ini, 0)) AS saldo_ini, COALESCE(pai.no_pagos, 0) AS no_pagos,
        COALESCE(abd.pago_dia, 0) AS pago_dia, abd.no_ticket, np.tipo
      FROM nomina_prestamos np
      INNER JOIN usuarios u ON u.id = np.id_usuario
      INNER JOIN empresas e ON e.id_empresa = u.id_empresa
      LEFT JOIN cajachica_categorias cc ON cc.id_empresa = e.id_empresa
      LEFT JOIN (
        SELECT np.id_prestamo, Sum(nfp.monto) AS saldo_ini, Count(*) AS no_pagos
        FROM nomina_fiscal_prestamos nfp
          INNER JOIN nomina_prestamos np ON np.id_prestamo = nfp.id_prestamo
        WHERE nfp.fecha < '{$fecha}'
        GROUP BY np.id_prestamo
      ) pai ON np.id_prestamo = pai.id_prestamo
      INNER JOIN (
        SELECT id_prestamo, fecha, no_ticket, monto AS pago_dia
        FROM nomina_fiscal_prestamos
        WHERE no_ticket = {$ticket}
      ) abd ON np.id_prestamo = abd.id_prestamo
      WHERE abd.no_ticket = {$ticket}
      ORDER BY id_prestamo_nom ASC"
    )->row();

    // echo "<pre>";
    //   var_dump($fondoc);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 130));
    $pdf->show_head = false;

    // $pdf->AddPage();
    $pdf->SetFont('helvetica','', 8);
    $pdf->SetXY(0, 0);

    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()-5);
    $pdf->Row(array('       CAJA DE PRESTAMOS'), false, false);
    $pdf->SetAligns(array('C'));
    $pdf->SetXY(0, $pdf->GetY()-3);
    $pdf->Row(array('TICKET PRESTAMO LARGO PLAZO'), false, false);

    $pdf->SetWidths(array(30, 33));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('Folio: '.$fondoc->no_ticket, MyString::fechaAT($fondoc->fecha)), false, false);

    $pdf->SetWidths(array(30, 33));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('PRESTAMO: ', MyString::formatoNumero($fondoc->monto, 2)), false, false);

    $pdf->SetWidths(array(30, 33));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('SALDO INICIAL: ', MyString::formatoNumero($fondoc->saldo_ini, 2)), false, false);

    $pdf->SetWidths(array(30, 33));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('ABONO '.(($fondoc->no_pagos+1).'/'.$fondoc->tno_pagos).':', MyString::formatoNumero($fondoc->pago_dia, 2)), false, false);

    $pdf->SetWidths(array(30, 33));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('SALDO: ', MyString::formatoNumero($fondoc->saldo_ini-$fondoc->pago_dia, 2)), false, false);

    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('EMPRESA: '.$fondoc->categoria), false, false);

    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('EMPLEADO: '.$fondoc->empleado), false, false);

    $pdf->AutoPrint(true);
    $pdf->Output();
  }

  public function printPrestamoCp($id_pago, $fecha)
  {
    $fondoc = $this->db->query(
      "SELECT cp.id_prestamo, '' AS id_prestamo_nom, cp.id_empleado, cc.id_categoria, COALESCE(cc.abreviatura, '') AS categoria,
        (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS empleado,
        Date(abd.fecha) AS fecha, cp.monto, cp.concepto,
        (cp.monto-COALESCE(pai.saldo_ini, 0)) AS saldo_ini, COALESCE(pai.no_pagos, 0) AS no_pagos,
        COALESCE(abd.pago_dia, 0) AS pago_dia, abd.id_pago
      FROM otros.cajaprestamo_prestamos cp
        INNER JOIN usuarios u ON u.id = cp.id_empleado
        INNER JOIN cajachica_categorias cc ON cc.id_categoria = cp.id_categoria
        LEFT JOIN (
          SELECT np.id_prestamo, Sum(nfp.monto) AS saldo_ini, Count(*) AS no_pagos
          FROM otros.cajaprestamo_pagos nfp
            INNER JOIN otros.cajaprestamo_prestamos np ON np.id_prestamo = nfp.id_prestamo
          WHERE nfp.fecha < '{$fecha}'
          GROUP BY np.id_prestamo
        ) pai ON cp.id_prestamo = pai.id_prestamo
        LEFT JOIN (
          SELECT id_prestamo, id_pago, fecha, monto AS pago_dia
          FROM otros.cajaprestamo_pagos
          WHERE fecha = '{$fecha}'
        ) abd ON cp.id_prestamo = abd.id_prestamo
      WHERE abd.id_pago = {$id_pago} AND cp.fecha <= '{$fecha}'"
    )->row();

    // echo "<pre>";
    //   var_dump($fondoc);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 130));
    $pdf->show_head = false;

    // $pdf->AddPage();
    $pdf->SetFont('helvetica','', 8);
    $pdf->SetXY(0, 0);

    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()-5);
    $pdf->Row(array('       CAJA DE PRESTAMOS'), false, false);
    $pdf->SetAligns(array('C'));
    $pdf->SetXY(0, $pdf->GetY()-3);
    $pdf->Row(array('TICKET PRESTAMO CORTO PLAZO'), false, false);

    $pdf->SetWidths(array(30, 33));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('Folio: '.$fondoc->id_pago, MyString::fechaAT($fondoc->fecha)), false, false);

    $pdf->SetWidths(array(30, 33));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('PRESTAMO: ', MyString::formatoNumero($fondoc->monto, 2)), false, false);

    $pdf->SetWidths(array(30, 33));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('SALDO INICIAL: ', MyString::formatoNumero($fondoc->saldo_ini, 2)), false, false);

    $pdf->SetWidths(array(30, 33));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('ABONO '.($fondoc->no_pagos+1).':', MyString::formatoNumero($fondoc->pago_dia, 2)), false, false);

    $pdf->SetWidths(array(30, 33));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('SALDO: ', MyString::formatoNumero($fondoc->saldo_ini-$fondoc->pago_dia, 2)), false, false);

    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('EMPRESA: '.$fondoc->categoria), false, false);

    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row(array('EMPLEADO: '.$fondoc->empleado), false, false);

    $pdf->AutoPrint(true);
    $pdf->Output();
  }


}

/* End of file caja_chica_model.php */
/* Location: ./application/models/caja_chica_model.php */