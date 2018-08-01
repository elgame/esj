<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class caja_chica_model extends CI_Model {

  public function get($fecha, $noCaja)
  {
    $info = array(
      'saldo_inicial'         => 0,
      'ingresos'              => array(),
      'otros'                 => array(),
      'remisiones'            => array(),
      'boletas'               => array(),
      'boletas_arecuperar'    => array(),
      'boletas_ch_entransito' => array(),
      'saldo_clientes'        => array(),
      'denominaciones'        => array(),
      'gastos'                => array(),
      'categorias'            => array(),
    );

    // Obtiene el saldo incial.
    $ultimoSaldo = $this->db->query(
      "SELECT saldo
       FROM cajachica_efectivo
       WHERE fecha < '{$fecha}' AND no_caja = {$noCaja}
       ORDER BY fecha DESC
       LIMIT 1"
    );

    if ($ultimoSaldo->num_rows() > 0)
    {
      $info['saldo_inicial'] = $ultimoSaldo->result()[0]->saldo;
    }

    $ingresos = $this->db->query(
      "SELECT ci.*, cc.abreviatura as categoria, cn.nomenclatura
       FROM cajachica_ingresos ci
       INNER JOIN cajachica_categorias cc ON cc.id_categoria = ci.id_categoria
       INNER JOIN cajachica_nomenclaturas cn ON cn.id = ci.id_nomenclatura
       WHERE ci.fecha = '{$fecha}' AND ci.otro = 'f' AND ci.no_caja = {$noCaja}
       ORDER BY ci.id_ingresos ASC"
    );

    if ($ingresos->num_rows() > 0)
    {
      $info['ingresos'] = $ingresos->result();
    }

    // $otros = $this->db->query(
    //   "SELECT *
    //    FROM cajachica_ingresos
    //    WHERE fecha = '$fecha' AND otro = 't'
    //    ORDER BY id_ingresos ASC"
    // );

    // if ($otros->num_rows() > 0)
    // {
    //   $info['otros'] = $otros->result();
    // }

    // remisiones
    $remisiones = $this->db->query(
      "SELECT cr.id_remision, cr.monto, cr.observacion, f.folio, cr.id_categoria, cc.abreviatura as empresa,
              COALESCE((select (serie || folio) as folio from facturacion where id_factura = fvr.id_factura), cr.folio_factura) as folio_factura,
              cr.id_movimiento, cr.row, cr.fecha, cr.no_caja
       FROM cajachica_remisiones cr
       INNER JOIN facturacion f ON f.id_factura = cr.id_remision
       INNER JOIN cajachica_categorias cc ON cc.id_categoria = cr.id_categoria
       LEFT JOIN facturacion_ventas_remision_pivot fvr ON fvr.id_venta = f.id_factura
       WHERE cr.fecha = '{$fecha}' AND cr.no_caja = {$noCaja}"
    );

    if ($remisiones->num_rows() > 0)
    {
      $info['remisiones'] = $remisiones->result();
    }

    // boletas
    if($noCaja == '1' || $noCaja == '3')
    {
      $sql = ' AND b.id_area <> 7';
      if ($noCaja == '3') { // Caja de coco
        $sql = " AND b.id_area = 7";
      }
      $boletas = $this->db->query(
        "SELECT b.id_bascula, b.folio as boleta, DATE(b.fecha_bruto) as fecha, pr.nombre_fiscal as proveedor,
          b.importe, cb.folio as folio_caja_chica, p.nombre_fiscal as productor,
          DATE(b.fecha_pago) AS fecha_pago
        FROM bascula b
        INNER JOIN proveedores pr ON pr.id_proveedor = b.id_proveedor
        LEFT JOIN cajachica_boletas cb ON cb.id_bascula = b.id_bascula
        LEFT JOIN otros.productor p ON p.id_productor = b.id_productor
        WHERE (DATE(b.fecha_pago) = '{$fecha}' OR DATE(b.fecha_bruto) = '{$fecha}') AND
        (b.accion = 'p' OR (b.metodo_pago = 'co' AND b.accion <> 'b')) AND b.status = 't'{$sql}
        ORDER BY (b.folio) ASC"
      );

      if ($boletas->num_rows() > 0)
      {
        $boletas = $boletas->result();
        foreach ($boletas as $key => $boleta) {
          if ($boleta->fecha_pago == '') { // pendiente de pago
            $boleta->importe_pendiente = $boleta->importe;
            $boleta->importe_pagada    = 0;
          } elseif (strtotime($boleta->fecha) == strtotime($boleta->fecha_pago) ||
                    strtotime($fecha) == strtotime($boleta->fecha_pago)) { // se pago el mismo dia
            $boleta->importe_pendiente = 0;
            $boleta->importe_pagada    = $boleta->importe;
          } elseif (strtotime($fecha) < strtotime($boleta->fecha_pago)) { // se pago dias despues
            $boleta->importe_pendiente = $boleta->importe;
            $boleta->importe_pagada    = 0;
          } elseif (strtotime($fecha) > strtotime($boleta->fecha_pago)) { // se pago dias antes
            $boleta->importe_pendiente = 0;
            $boleta->importe_pagada    = $boleta->importe;
          }
        }
        $info['boletas'] = $boletas;
      }

      // Boletas pendientes de recuperar dinero a caja
      $boletas = $this->db->query(
        "SELECT pr.id_proveedor, pr.nombre_fiscal as proveedor, Sum(b.importe) AS importe
        FROM bascula b
          INNER JOIN proveedores pr ON pr.id_proveedor = b.id_proveedor
          LEFT JOIN cajachica_boletas cb ON cb.id_bascula = b.id_bascula
          LEFT JOIN otros.productor p ON p.id_productor = b.id_productor
          LEFT JOIN bascula_pagos_basculas bpb ON b.id_bascula = bpb.id_bascula
        WHERE DATE(b.fecha_pago) <= '{$fecha}' AND DATE(b.fecha_pago) >= '2017-01-01'
          AND b.accion = 'p' AND b.status = 't' AND bpb.id_bascula IS NULL{$sql}
        GROUP BY pr.id_proveedor
        ORDER BY proveedor ASC"
      );

      if ($boletas->num_rows() > 0)
      {
        $info['boletas_arecuperar'] = $boletas->result();
      }

      // Cheques de bletas en transito
      $boletas = $this->db->query(
        "SELECT bm.id_movimiento, Date(bm.fecha) AS fecha, bm.numero_ref, bm.monto, p.nombre_fiscal, string_agg(b.folio::text, ', ') as boleta
        FROM banco_movimientos AS bm
          INNER JOIN banco_movimientos_bascula AS bmb ON bm.id_movimiento = bmb.id_movimiento
          INNER JOIN proveedores AS p ON p.id_proveedor = bm.id_proveedor
          INNER JOIN bascula_pagos AS bp ON bp.id_pago = bmb.id_bascula_pago
          INNER JOIN bascula_pagos_basculas bpb ON bp.id_pago = bpb.id_pago
          INNER JOIN bascula b ON b.id_bascula = bpb.id_bascula
        WHERE Date(bm.fecha) <= '{$fecha}' AND bm.entransito = 't' AND bm.metodo_pago = 'cheque'
          AND bm.status = 't' AND b.accion = 'p'
        GROUP BY bm.id_movimiento, p.id_proveedor"
      );

      if ($boletas->num_rows() > 0)
      {
        $info['boletas_ch_entransito'] = $boletas->result();
      }
    }

    if ($noCaja == '2') {
      // saldo de clientes
      $empresas = $this->db->query("SELECT id_empresa, nombre_fiscal
        FROM empresas WHERE status = 't'
        ORDER BY nombre_fiscal ASC");
      $empresas = $empresas->result();
      foreach ($empresas as $key => $empresa) {
        $empresa->clientes = [];
        $sql = " AND f.id_empresa = '".$empresa->id_empresa."'";
        $sql .= " AND f.is_factura = 'f'";
        $saldo_clientes = $this->db->query(
          "SELECT
            id_cliente, show_saldo,
            nombre_fiscal as nombre,
            Sum(total) AS total,
            Sum(iva) AS iva,
            Sum(abonos) AS abonos,
            Sum(saldo)::numeric(12, 2) AS saldo,
            SUM(saldo_cambio) as saldo_cambio
          FROM
            (
              SELECT
                c.id_cliente, c.show_saldo,
                c.nombre_fiscal,
                Sum(f.total) AS total,
                Sum(f.importe_iva) AS iva,
                COALESCE(Sum(faa.abonos),0) as abonos,
                COALESCE(Sum(f.total) - COALESCE(Sum(faa.abonos),0), 0) AS saldo,
                (CASE WHEN f.tipo_cambio > 1 THEN COALESCE(Sum(f.total/f.tipo_cambio) - COALESCE(faa.abonos/f.tipo_cambio, 0), 0) ELSE 0 END) AS saldo_cambio
              FROM
                clientes AS c
                INNER JOIN facturacion AS f ON c.id_cliente = f.id_cliente
                LEFT JOIN (
                  SELECT
                    d.id_cliente,
                    d.id_factura,
                    Sum(d.abonos) AS abonos
                  FROM
                  (
                    SELECT
                      f.id_cliente,
                      f.id_factura,
                      Sum(fa.total) AS abonos
                    FROM
                      facturacion AS f
                        INNER JOIN facturacion_abonos AS fa ON f.id_factura = fa.id_factura
                    WHERE f.status <> 'ca' AND f.status <> 'b' AND f.id_abono_factura IS NULL
                      AND Date(fa.fecha) >= '2017-01-01' AND Date(fa.fecha) <= '{$fecha}'{$sql}
                    GROUP BY f.id_cliente, f.id_factura

                    UNION

                    SELECT
                      f.id_cliente,
                      f.id_nc AS id_factura,
                      Sum(f.total) AS abonos
                    FROM
                      facturacion AS f
                    WHERE f.status <> 'ca' AND f.status <> 'b' AND f.id_nc IS NOT NULL AND f.id_abono_factura IS NULL
                      AND Date(f.fecha) >= '2017-01-01' AND Date(f.fecha) <= '{$fecha}'{$sql}
                    GROUP BY f.id_cliente, f.id_factura
                  ) AS d
                  GROUP BY d.id_cliente, d.id_factura
                ) AS faa ON f.id_cliente = faa.id_cliente AND f.id_factura = faa.id_factura
                LEFT JOIN (SELECT id_remision, id_factura, status
                          FROM remisiones_historial WHERE status <> 'ca' AND status <> 'b'
                ) fh ON f.id_factura = fh.id_remision
              WHERE f.status <> 'ca' AND f.status <> 'b' AND f.status <> 'pa'
                 AND f.id_abono_factura IS NULL AND id_nc IS NULL
                 AND Date(f.fecha) >= '2017-01-01' AND Date(f.fecha) <= '{$fecha}'{$sql}
                 AND COALESCE(fh.id_remision, 0) = 0
              GROUP BY c.id_cliente, c.nombre_fiscal, faa.abonos, f.tipo_cambio
            ) AS sal
          GROUP BY id_cliente, show_saldo, nombre_fiscal
          HAVING Sum(saldo)::numeric(12, 2) > 0"
        );

        if ($saldo_clientes->num_rows() > 0)
        {
          $empresa->clientes = $saldo_clientes->result();
        }
      }
      $info['saldo_clientes'] = $empresas;
    }

    // denominaciones
    $denominaciones = $this->db->query(
      "SELECT *
       FROM cajachica_efectivo
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

    // gastos
    $gastos = $this->db->query(
      "SELECT cg.id_gasto, cg.concepto, cg.fecha, cg.monto, cc.id_categoria, cc.abreviatura as empresa,
          cg.folio, cg.id_nomenclatura, cn.nomenclatura, COALESCE(cca.id_cat_codigos, ca.id_area) AS id_area,
          COALESCE(cca.nombre, ca.nombre) AS nombre_codigo,
          COALESCE((CASE WHEN cca.codigo <> '' THEN cca.codigo ELSE cca.nombre END), ca.codigo_fin) AS codigo_fin,
          (CASE WHEN cca.id_cat_codigos IS NULL THEN 'id_area' ELSE 'id_cat_codigos' END) AS campo,
          cg.reposicion
       FROM cajachica_gastos cg
         INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
         INNER JOIN cajachica_nomenclaturas cn ON cn.id = cg.id_nomenclatura
         LEFT JOIN compras_areas ca ON ca.id_area = cg.id_area
         LEFT JOIN otros.cat_codigos AS cca ON cca.id_cat_codigos = cg.id_cat_codigos
       WHERE cg.fecha = '{$fecha}' AND cg.no_caja = {$noCaja}
       ORDER BY cg.id_gasto ASC"
    );

    if ($gastos->num_rows() > 0)
    {
      $info['gastos'] = $gastos->result();
    }

    $info['categorias'] = $this->db->query(
    "SELECT id_categoria, nombre, abreviatura
     FROM cajachica_categorias
     WHERE status = 't'")->result();

    foreach ($info['categorias'] as $key => $categoria)
    {
      $categoria->importe = 0;
      foreach ($info['gastos'] as $gasto)
      {
        if ($gasto->id_categoria == $categoria->id_categoria)
        {
          $categoria->importe += floatval($gasto->monto);
        }
      }
    }

    return $info;
  }

  public function guardar($data)
  {
    $ingresos = array();

    $nombresCajas = ['1' => 'Caja Limon', '2' => 'Caja Gastos', '3' => 'Caja Coco', '4' => 'Caja Venta de Contado'];

    // ingresos
    if (isset($data['ingreso_concepto']) && is_array($data['ingreso_concepto'])) {
      foreach ($data['ingreso_concepto'] as $key => $ingreso)
      {
        if (isset($data['ingreso_del'][$key]) && $data['ingreso_del'][$key] == 'true' &&
          isset($data['ingreso_id_ingresos'][$key]) && floatval($data['ingreso_id_ingresos'][$key]) > 0) {

          $this->db->delete('cajachica_ingresos', "id_ingresos = ".$data['ingreso_id_ingresos'][$key]);
        } elseif (isset($data['ingreso_id_ingresos'][$key]) && floatval($data['ingreso_id_ingresos'][$key]) > 0) {
          $ingreso_udt = array(
            'concepto'        => $ingreso,
            'monto'           => $data['ingreso_monto'][$key],
            'fecha'           => $data['fecha_caja_chica'],
            'otro'            => 'f',
            'id_categoria'    => $data['ingreso_empresa_id'][$key],
            'id_nomenclatura' => $data['ingreso_nomenclatura'][$key],
            'poliza'          => empty($data['ingreso_poliza'][$key]) ? null : $data['ingreso_poliza'][$key],
            'id_movimiento'   => is_numeric($data['ingreso_concepto_id'][$key]) ? $data['ingreso_concepto_id'][$key] : null,
            'no_caja'         => $data['fno_caja'],
            'id_usuario'      => $this->session->userdata('id_usuario'),
          );

          // Bitacora
          $id_bitacora = $this->bitacora_model->_update('cajachica_ingresos', $data['ingreso_id_ingresos'][$key], $ingreso_udt,
                          array(':accion'       => 'el ingreso por reposicion', ':seccion' => 'caja chica',
                                ':folio'        => '',
                                // ':id_empresa'   => $datosFactura['id_empresa'],
                                ':empresa'      => '', // .$this->input->post('dempresa')
                                ':id'           => 'id_ingresos',
                                ':titulo'       => $nombresCajas[$data['fno_caja']])
                        );

          $this->db->update('cajachica_ingresos', $ingreso_udt, "id_ingresos = ".$data['ingreso_id_ingresos'][$key]);
        } else {
          $ingresos = array(
            'concepto'        => $ingreso,
            'monto'           => $data['ingreso_monto'][$key],
            'fecha'           => $data['fecha_caja_chica'],
            'otro'            => 'f',
            'id_categoria'    => $data['ingreso_empresa_id'][$key],
            'id_nomenclatura' => $data['ingreso_nomenclatura'][$key],
            'poliza'          => empty($data['ingreso_poliza'][$key]) ? null : $data['ingreso_poliza'][$key],
            'id_movimiento'   => is_numeric($data['ingreso_concepto_id'][$key]) ? $data['ingreso_concepto_id'][$key] : null,
            'no_caja'         => $data['fno_caja'],
            'id_usuario'      => $this->session->userdata('id_usuario'),
          );

          $this->db->insert('cajachica_ingresos', $ingresos);
          $idingresoo = $this->db->insert_id();

          // Bitacora
          $this->bitacora_model->_insert('cajachica_ingresos', $idingresoo,
                        array(':accion'    => 'el ingreso por reposicion', ':seccion' => 'caja chica',
                              ':folio'     => "Concepto: {$ingreso} | Monto: {$data['ingreso_monto'][$key]}",
                              // ':id_empresa' => $datosFactura['id_empresa'],
                              ':empresa'   => ''));
        }
      }
    }

    // Otros
    // if (isset($data['otros_concepto']))
    // {
    //   foreach ($data['otros_concepto'] as $key => $otro)
    //   {
    //     $ingresos[] = array(
    //       'concepto' => $otro,
    //       'monto'    => $data['otros_monto'][$key],
    //       'fecha'    => $data['fecha_caja_chica'],
    //       'otro'    => 't'
    //     );
    //   }
    // }

    // $this->db->delete('cajachica_ingresos', array('fecha' => $data['fecha_caja_chica'], 'no_caja' => $data['fno_caja']));
    // if (count($ingresos) > 0)
    // {
    //   $this->db->insert_batch('cajachica_ingresos', $ingresos);
    // }

    // Remisiones
    //Elimina los movimientos de banco y cuentas por cobrar si ya se cerro el corte
    $this->load->model('banco_cuentas_model');
    // $corte_caja = $this->get($data['fecha_caja_chica'], $data['fno_caja']);
    // foreach ($corte_caja['remisiones'] as $key => $value)
    // {
    //   if($value->id_movimiento != '')
    //     $this->banco_cuentas_model->deleteMovimiento($value->id_movimiento);
    // }
    // $this->db->delete('cajachica_remisiones', array('fecha' => $data['fecha_caja_chica'], 'no_caja' => $data['fno_caja']));
    if (isset($data['remision_concepto']))
    {
      $data_folio = $this->db->query("SELECT COALESCE( (SELECT folio FROM cajachica_remisiones ORDER BY folio DESC LIMIT 1), 0 ) AS folio")->row();
      $remisiones = array();

      foreach ($data['remision_concepto'] as $key => $concepto)
      {
        if (isset($data['remision_del'][$key]) && $data['remision_del'][$key] == 'true' &&
          isset($data['remision_row'][$key]) && is_numeric($data['remision_row'][$key]) &&
          isset($data['remision_id'][$key]) && $data['remision_id'][$key] > 0) {

          $data_mov = $this->db->query("SELECT id_movimiento FROM cajachica_remisiones
                                     WHERE fecha = '{$data['fecha_caja_chica']}' AND id_remision = {$data['remision_id'][$key]}
                                      AND row = {$data['remision_row'][$key]} AND no_caja = {$data['fno_caja']}")->row();
          if($data_mov->id_movimiento != '')
            $this->banco_cuentas_model->deleteMovimiento($data_mov->id_movimiento);
          $this->db->delete('cajachica_remisiones', array('fecha' => $data['fecha_caja_chica'], 'no_caja' => $data['fno_caja'],
                                                          'id_remision' => $data['remision_id'][$key], 'row' => $data['remision_row'][$key]));
        } elseif (isset($data['remision_row'][$key]) && $data['remision_row'][$key] == '') {
          $data_folio->folio += 1;
          $remisiones[] = array(
            'observacion'   => $concepto,
            'id_remision'   => $data['remision_id'][$key],
            'fecha'         => $data['fecha_caja_chica'],
            'monto'         => $data['remision_importe'][$key],
            'row'           => $key,
            'id_categoria'  => $data['remision_empresa_id'][$key],
            'folio_factura' => empty($data['remision_folio'][$key]) ? null : $data['remision_folio'][$key],
            'no_caja'       => $data['fno_caja'],
            'folio'         => $data_folio->folio,
            'id_usuario'    => $this->session->userdata('id_usuario'),
          );
        }
      }

      if (count($remisiones) > 0)
        $this->db->insert_batch('cajachica_remisiones', $remisiones);
    }

    // Boletas
    $this->db->delete('cajachica_boletas', array('fecha' => $data['fecha_caja_chica'], 'no_caja' => $data['fno_caja']));
    if (isset($data['boletas_id']))
    {
      $boletas = array();

      foreach ($data['boletas_id'] as $key => $idBoleta)
      {
        $boletas[] = array(
          'fecha'      => $data['fecha_caja_chica'],
          'id_bascula' => $idBoleta,
          'row'        => $key,
          'folio'      => null, //empty($data['boletas_folio'][$key]) ? null : $data['boletas_folio'][$key],
          'no_caja'    => $data['fno_caja'],
        );
      }

      $this->db->insert_batch('cajachica_boletas', $boletas);
    }

    // Denominaciones
    $this->db->delete('cajachica_efectivo', array('fecha' => $data['fecha_caja_chica'], 'no_caja' => $data['fno_caja']));
    $efectivo = array();
    foreach ($data['denom_abrev'] as $key => $denominacion)
    {
      $efectivo[$denominacion] = $data['denominacion_cantidad'][$key];
    }

    $efectivo['fecha']   = $data['fecha_caja_chica'];
    $efectivo['saldo']   = $data['saldo_corte'];
    $efectivo['no_caja'] = $data['fno_caja'];

    $this->db->insert('cajachica_efectivo', $efectivo);

    // Gastos del dia
    // $this->db->delete('cajachica_gastos', array('fecha' => $data['fecha_caja_chica'], 'no_caja' => $data['fno_caja']));
    if (isset($data['gasto_concepto']))
    {
      $gastos_ids = array('adds' => array(), 'delets' => array(), 'updates' => array());
      $gastos_udt = $gastos = array();
      foreach ($data['gasto_concepto'] as $key => $gasto)
      {
        if (isset($data['gasto_del'][$key]) && $data['gasto_del'][$key] == 'true' &&
          isset($data['gasto_id_gasto'][$key]) && floatval($data['gasto_id_gasto'][$key]) > 0) {
          $gastos_ids['delets'][] = $this->getDataGasto($data['gasto_id_gasto'][$key]);

          $this->db->delete('cajachica_gastos', "id_gasto = ".$data['gasto_id_gasto'][$key]);
        } elseif (isset($data['gasto_id_gasto'][$key]) && floatval($data['gasto_id_gasto'][$key]) > 0) {
          $gastos_udt = array(
            'id_categoria'    => $data['gasto_empresa_id'][$key],
            'id_nomenclatura' => $data['gasto_nomenclatura'][$key],
            'folio'           => $data['gasto_folio'][$key],
            'concepto'        => $gasto,
            'monto'           => $data['gasto_importe'][$key],
            'fecha'           => $data['fecha_caja_chica'],
            'no_caja'         => $data['fno_caja'],
            // 'id_area'         => (isset($data['codigoAreaId'][$key]{0})? $data['codigoAreaId'][$key]: NULL),
            $data['codigoCampo'][$key] => (isset($data['codigoAreaId'][$key]{0})? $data['codigoAreaId'][$key]: NULL),
            'reposicion'      => ($data['gasto_reposicion'][$key]=='t'? 't': 'f'),
          );

          // Bitacora
          $id_bitacora = $this->bitacora_model->_update('cajachica_gastos', $data['gasto_id_gasto'][$key], $gastos_udt,
                          array(':accion'       => 'el gasto del dia', ':seccion' => 'caja chica',
                                ':folio'        => '',
                                // ':id_empresa'   => $datosFactura['id_empresa'],
                                ':empresa'      => '', // .$this->input->post('dempresa')
                                ':id'           => 'id_gasto',
                                ':titulo'       => $nombresCajas[$data['fno_caja']])
                        );

          $this->db->update('cajachica_gastos', $gastos_udt, "id_gasto = ".$data['gasto_id_gasto'][$key]);
        } else {
          $gastos = array(
            'id_categoria'    => $data['gasto_empresa_id'][$key],
            'id_nomenclatura' => $data['gasto_nomenclatura'][$key],
            'folio'           => $data['gasto_folio'][$key],
            'concepto'        => $gasto,
            'monto'           => $data['gasto_importe'][$key],
            'fecha'           => $data['fecha_caja_chica'],
            'no_caja'         => $data['fno_caja'],
            // 'id_area'         => (isset($data['codigoAreaId'][$key]{0})? $data['codigoAreaId'][$key]: NULL),
            $data['codigoCampo'][$key] => (isset($data['codigoAreaId'][$key]{0})? $data['codigoAreaId'][$key]: NULL),
            'reposicion'      => ($data['gasto_reposicion'][$key]=='t'? 't': 'f'),
            'id_usuario'      => $this->session->userdata('id_usuario'),
          );
          $this->db->insert('cajachica_gastos', $gastos);
          $gastooidd = $this->db->insert_id();
          $gastos_ids['adds'][] = $gastooidd;

          // Bitacora
          $this->bitacora_model->_insert('cajachica_gastos', $gastooidd,
                        array(':accion'    => 'el gasto del dia', ':seccion' => 'caja chica',
                              ':folio'     => "Concepto: {$gasto} | Monto: {$data['gasto_importe'][$key]}",
                              // ':id_empresa' => $datosFactura['id_empresa'],
                              ':empresa'   => ''));
        }
      }

      if (count($gastos_ids['adds']) > 0 || count($gastos_ids['delets']) > 0) {
        $this->enviarEmail($gastos_ids);
        // $this->db->insert_batch('cajachica_gastos', $gastos);
      }
    }

    return true;
  }

  public function getRemisiones()
  {
    $this->load->model('cuentas_cobrar_model');

    $this->db->query("SELECT refreshallmaterializedviews();");

    $remisiones = $this->db->query(
      "SELECT f.id_factura, DATE(f.fecha) as fecha, f.serie, f.folio, f.total, c.nombre_fiscal as cliente,
            COALESCE((select (serie || folio) as folio from facturacion where id_factura = fvr.id_factura), null) as folio_factura,
            sfr.saldo
       FROM facturacion f
       INNER JOIN clientes c ON c.id_cliente = f.id_cliente
       INNER JOIN saldos_facturas_remisiones sfr ON f.id_factura = sfr.id_factura
       LEFT JOIN cajachica_remisiones cr ON cr.id_remision = f.id_factura
       LEFT JOIN facturacion_ventas_remision_pivot fvr ON fvr.id_venta = f.id_factura
       WHERE f.is_factura = 'f' AND f.status = 'p'
       ORDER BY (f.fecha, f.serie, f.folio) DESC"
    );
    // COALESCE(cr.id_remision, 0) = 0

    $response = $remisiones->result();
    // foreach ($response as $key => $value)
    // {
    //   $inf_factura = $this->cuentas_cobrar_model->saldoFactura($value->id_factura);
    //   echo "<pre>";
    //     var_dump($value->id_factura, $inf_factura);
    //   echo "</pre>";
    //   $value->saldo = $inf_factura->saldo;
    // }

    return $response;
  }

  public function getMovimientos()
  {
    $this->load->model('empresas_model');

    $defaultEmpresa = $this->empresas_model->getDefaultEmpresa();
    //  AND bc.id_empresa = {$defaultEmpresa->id_empresa}

    $movimientos = $this->db->query(
      "SELECT bm.id_movimiento, COALESCE(p.nombre_fiscal, bm.a_nombre_de) as proveedor, bm.numero_ref, ba.nombre as banco, bm.monto, DATE(bm.fecha) as fecha
       FROM banco_movimientos bm
       INNER JOIN banco_cuentas bc ON bc.id_cuenta = bm.id_cuenta
       LEFT JOIN proveedores p ON p.id_proveedor = bm.id_proveedor
       INNER JOIN banco_bancos as ba ON ba.id_banco = bm.id_banco
       LEFT JOIN cajachica_ingresos ci ON ci.id_movimiento = bm.id_movimiento
       WHERE bm.tipo = 'f' AND COALESCE(ci.id_ingresos, 0) = 0 AND DATE(bm.fecha) > (Now() - interval '3 months')
       ORDER BY bm.fecha ASC, ci.id_ingresos ASC
    ");

    return $movimientos->result();
  }

  public function getCategorias($perpage = '40')
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
    if ($this->input->get('fstatus') != '')
    {
      $sql .= " AND status = '".$this->input->get('fstatus')."'";
    }

    $query = BDUtil::pagination(
        "SELECT cc.id_categoria, cc.nombre, cc.status, cc.abreviatura, e.nombre_fiscal as empresa
        FROM cajachica_categorias cc
        LEFT JOIN empresas e ON e.id_empresa = cc.id_empresa
        WHERE 1 = 1 {$sql}
        ORDER BY (nombre) ASC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'categorias'     => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['categorias'] = $res->result();

    return $response;
  }

  public function agregarCategoria($data)
  {
    $insertData = array(
      'nombre' => $data['nombre'],
      'abreviatura' => $data['abreviatura'],
    );

    if (isset($data['pid_empresa']) && is_numeric($data['pid_empresa']))
    {
      $insertData['id_empresa'] = $data['pid_empresa'];
    }

    $this->db->insert('cajachica_categorias', $insertData);

    return true;
  }

  public function info($idCategoria)
  {
    $query = $this->db->query(
      "SELECT cc.*, e.nombre_fiscal as empresa
        FROM cajachica_categorias cc
        LEFT JOIN empresas e ON e.id_empresa = cc.id_empresa
        WHERE id_categoria = {$idCategoria}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->result();
    }

    return $data;
  }

  public function modificarCategoria($categoriaId, $data)
  {
    $updateData = array(
      'nombre'      => $data['nombre'],
      'abreviatura' => $data['abreviatura'],
      'id_empresa'  => is_numeric($data['pid_empresa']) ? $data['pid_empresa'] : null,
    );

    $this->db->update('cajachica_categorias', $updateData, array('id_categoria' => $categoriaId));

    return true;
  }

  public function elimimnarCategoria($categoriaId)
  {
    $this->db->update('cajachica_categorias', array('status' => 'f'), array('id_categoria' => $categoriaId));

    return true;
  }

  public function ajaxCategorias()
  {
    $sql = '';
    $res = $this->db->query("
        SELECT *
        FROM cajachica_categorias
        WHERE status = 't' AND lower(abreviatura) LIKE '%".mb_strtolower($_GET['term'], 'UTF-8')."%'
        ORDER BY abreviatura ASC
        LIMIT 20");

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $response[] = array(
          'id' => $itm->id_categoria,
          'label' => $itm->abreviatura,
          'value' => $itm->abreviatura,
          'item' => $itm,
        );
      }
    }

    return $response;
  }

  public function enviarEmail($gastos_ids)
  {
      $this->load->library('my_email');

      // Obtiene la informacion de la factura.
      $html_adds = $txt_adds = '';
      $caja = '';
      foreach ($gastos_ids['adds'] as $key => $value) {
        $gasto = $this->getDataGasto($value);
        $html_adds .= '<table width="652" border="0">
        <tbody><tr>
        <td align="left"><b>Fecha:</b></td><td align="left">'.$gasto->fecha.'</td>
        </tr>
        <tr>
        <td align="left" width="142"><b>Operacion:</b></td><td align="left" width="510">Se agrego gasto</td>
        </tr>
        <tr>
        <td align="left"><b>Concepto:</b></td><td align="left">'.$gasto->concepto.'</td>
        </tr>
        <tr>
        <td align="left"><b>Importe:</b></td><td align="left">'.String::formatoNumero($gasto->monto).'</td>
        </tr>
        <tr>
        <td align="left"><b>Codigo gasto:</b></td><td align="left">'.$gasto->codigo_fin.'/'.$gasto->nombre_codigo.'</td>
        </tr>
        </tbody></table>';
        $txt_adds .= "Fecha: ".$gasto->fecha.", Operacion: Se elimino el gasto {$gasto->id_gasto}, Concepto: {$gasto->concepto}, Importe: ".String::formatoNumero($gasto->monto)."\r\n";
        $caja = $gasto->no_caja;
      }
      foreach ($gastos_ids['delets'] as $key => $gasto) {
        $html_adds .= '<table width="652" border="0">
        <tbody><tr>
        <td align="left"><b>Fecha:</b></td><td align="left">'.date("Y-m-d").'</td>
        </tr>
        <tr>
        <td align="left" width="142"><b>Operacion:</b></td><td align="left" width="510">Se elimino el gasto '.$gasto->id_gasto.'</td>
        </tr>
        <tr>
        <td align="left"><b>Concepto:</b></td><td align="left">'.$gasto->concepto.'</td>
        </tr>
        <tr>
        <td align="left"><b>Importe:</b></td><td align="left">'.String::formatoNumero($gasto->monto).'</td>
        </tr>
        <tr>
        <td align="left"><b>Codigo gasto:</b></td><td align="left">'.$gasto->codigo_fin.'/'.$gasto->nombre_codigo.'</td>
        </tr>
        </tbody></table>';
        $txt_adds .= "Fecha: ".date("Y-m-d").", Operacion: Se elimino el gasto {$gasto->id_gasto}, Concepto: {$gasto->concepto}, Importe: ".String::formatoNumero($gasto->monto)."\r\n";
        $caja = $gasto->no_caja;
      }

      if ($caja == '3') {
        //////////////////
        // Datos Correo //
        //////////////////

        $asunto = "Operacion realizada en Caja {$caja}";
        $altBody = "Notificacion, se registro un movimiento en la Caja {$caja}";

        $body = '<p>Notificacion, se registro un movimiento en la Caja '.$caja.'</p>
          <table border="0" width="652">
          <tbody>
          <tr>
          <td align="left" style="font-family:Arial,Helvetica,sans-serif;font-weight:bold;font-size:22px;color:#004785">Datos de las operaciones
            </td>
          </tr>
          <tr>
          <td height="25">&nbsp;</td>
          </tr>
          <tr>
          <td width="652">
          '.$html_adds.'
          </td>
          </tr>
          </tbody></table>
          <br>
          <p>EMPAQUE SAN JORGE</p>';

        //////////////////////
        // Datos del Emisor //
        //////////////////////

        $correoEmisorEm = "empaquesanjorge@hotmail.com"; // Correo con el q se emitira el correo.
        $nombreEmisor   = 'EMPAQUE SAN JORGE';
        $correoEmisor   = "postmaster@empaquesanjorge.com"; // Correo para el auth. empaquesanjorgemx@gmail.com (mandrill)
        $contrasena     = "2b9f25bc4737f34edada0b29a56ff682"; // Contraseña de $correEmisor S4nj0rg3V14n3y

        ////////////////////////
        // Datos del Receptor //
        ////////////////////////

        $correoDestino = array('ili-loren-gud@hotmail.com');

        $nombreDestino = 'Coco';
        $datosEmail = array(
            'correoEmisorEm' => $correoEmisorEm,
            'correoEmisor'   => $correoEmisor,
            'nombreEmisor'   => $nombreEmisor,
            'contrasena'     => $contrasena,
            'asunto'         => $asunto,
            'altBody'        => $altBody,
            'body'           => $body,
            'correoDestino'  => $correoDestino,
            'nombreDestino'  => $nombreDestino,
            'cc'             => '',
            'adjuntos'       => array()
        );

        // Envia el email.
        $result = $this->my_email->setData($datosEmail)->send();

        $response = array(
            'passes' => true,
            'msg'    => 10
        );

        if (isset($result['error']))
        {
            $response = array(
            'passes' => false,
            'msg'    => 9
            );
        }

        return $response;
      }
  }


  /**
   * NOMENCLATURAS
   */
  public function getNomenclaturas($perpage = '40')
  {
    $sql = '';
    // //paginacion
    // $params = array(
    //     'result_items_per_page' => $perpage,
    //     'result_page'       => (isset($_GET['pag'])? $_GET['pag']: 0)
    // );

    // if($params['result_page'] % $params['result_items_per_page'] == 0)
    //   $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

    //Filtros para buscar
    if ($this->input->get('fstatus') == '')
      $sql .= " AND cc.status = 't'";
    else
      $sql .= " AND cc.status = '".$this->input->get('fstatus')."'";

    $res = $this->db->query("SELECT cc.id, cc.nombre, cc.status, cc.nomenclatura
        FROM cajachica_nomenclaturas cc
        WHERE 1 = 1 {$sql}
        ORDER BY cc.nomenclatura::integer ASC
        ");

    $response = $res->result();

    return $response;
  }

  public function agregarNomenclaturas($data)
  {
    $nom_res = $this->db->query("SELECT nomenclatura
                               FROM cajachica_nomenclaturas
                               ORDER BY nomenclatura::integer DESC LIMIT 1")->row();
    $insertData = array(
      'nombre' => $data['nombre'],
      'nomenclatura' => $nom_res->nomenclatura+1,
    );

    $this->db->insert('cajachica_nomenclaturas', $insertData);

    return true;
  }

  public function infoNomenclaturas($idNomenclatura)
  {
    $query = $this->db->query(
      "SELECT cc.*
        FROM cajachica_nomenclaturas cc
        WHERE id = {$idNomenclatura}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->result();
    }

    return $data;
  }

  public function modificarNomenclaturas($idNomenclatura, $data)
  {
    $updateData = array(
      'nombre'      => $data['nombre'],
    );

    $this->db->update('cajachica_nomenclaturas', $updateData, array('id' => $idNomenclatura));

    return true;
  }

  public function elimimnarNomenclaturas($idNomenclatura, $val)
  {
    $this->db->update('cajachica_nomenclaturas', array('status' => $val), array('id' => $idNomenclatura));

    return true;
  }


  public function cerrarCaja($idCaja, $noCajas)
  {
    $this->db->update('cajachica_efectivo', array('status' => 'f'), array('id_efectivo' => $idCaja));
    $caja = $this->db->query("SELECT fecha FROM cajachica_efectivo WHERE id_efectivo = {$idCaja}")->row();

    $this->load->model('cuentas_cobrar_model');
    $banco_cuenta = $this->db->query("SELECT id_cuenta FROM banco_cuentas WHERE UPPER(alias) LIKE '%PAGO REMISIONADO%'")->row();
    $corte_caja = $this->get($caja->fecha, $noCajas);
    foreach ($corte_caja['remisiones'] as $key => $value)
    {
      if ($value->id_movimiento == '') {
        $_POST['fmetodo_pago'] = 'efectivo';
        $_GET['tipo'] = 'r';
        $data = array('fecha'  => $caja->fecha,
              'concepto'       => 'Pago en caja chica',
              'total'          => $value->monto, //$total,
              'id_cuenta'      => $banco_cuenta->id_cuenta,
              'ref_movimiento' => 'Caja '.$noCajas,
              'saldar'         => 'no' );
        $resp = $this->cuentas_cobrar_model->addAbono($data, $value->id_remision);
        $this->db->update('cajachica_remisiones', array('id_movimiento' => $resp['id_movimiento']),
          "fecha = '{$value->fecha}' AND id_remision = {$value->id_remision} AND row = {$value->row}");
      }
    }

    return true;
  }

  public function printCajaNomenclatura(&$pdf, $nomenclaturas)
  {
    // nomenclatura
    $pdf->SetFont('Arial','', 6);
    $pdf->SetXY(111, 9);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(20));
    $pdf->Row(array('NOMENCLATURA INGRESOS'), false, false);

    $pdf->SetXY(133, 5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(30));
    $xx = 133;
    foreach ($nomenclaturas as $key => $n)
    {
      if ($key % 7 == 0 && $key !== 0) {
        $xx += 30;
        $pdf->SetY(5);
      }
      $pdf->SetX($xx);
      $pdf->Row(array($n->nomenclatura.' '.$n->nombre), false, false, null, 1, 1);
    }
  }
  public function printCaja($fecha, $noCajas)
  {
    $this->load->model('compras_areas_model');
    $this->load->model('catalogos_sft_model');

    $privilegio = $this->usuarios_model->tienePrivilegioDe('', 'caja_chica/'.($noCajas==1? '': "caja{$noCajas}/"), true);

    $caja = $this->get($fecha, $noCajas);
    $nomenclaturas = $this->nomenclaturas();

    $subtitulo = '';
    if ($noCajas == 1)
      $subtitulo = ' LIMON';
    elseif ($noCajas == 2)
      $subtitulo = ' GASTOS';

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
    $pdf->titulo2 = "Caja Chica del {$fecha}";
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
    $pdf->Row(array(mb_strtoupper($privilegio->nombre.$subtitulo, 'UTF-8')), true, true, null, 3);

    $pdf->Image(APPPATH.(str_replace(APPPATH, '', '/images/logo.png')), 6, 15, 50);
    $pdf->Ln(20);

    $pdf->SetFont('Arial','B', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);

    // Fecha
    $pdf->SetXY(6, $pdf->GetY() - 20);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(104));
    $pdf->Row(array('FECHA ' . String::fechaAT($fecha)), false, false);

    // Saldo inicial
    $pdf->SetXY(6, $pdf->GetY() + 5);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(104));
    $pdf->Row(array('SALDO INICIAL '.String::formatoNumero($caja['saldo_inicial'], 2, '$', false)), false, false);

    // nomenclatura
    $this->printCajaNomenclatura($pdf, $nomenclaturas);
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

    $ttotalGastos = 0;
    foreach ($caja['gastos'] as $gasto)
    {
      $ttotalGastos += floatval($gasto->monto);
    }

    // Ingresos por reposicion
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pag_aux = $pdf->page;
    $pag_yaux = $pdf->GetY();
    $pdf->SetXY(6, 32);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(84, 20));
    $pdf->Row(array('INGRESOS POR REPOSICION', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(15, 15, 15, 39, 20));
    $pdf->Row(array('EMPRESA', 'NOM', 'POLIZA', 'NOMBRE Y/O CONCEPTO', 'ABONO'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'R', 'L', 'L', 'R'));
    $pdf->SetWidths(array(15, 15, 15, 39, 20));

    $totalIngresos = 0;
    foreach ($caja['ingresos'] as $key => $ingreso)
    {
      $pdf->SetX(6);

      $pdf->Row(array(
        $ingreso->categoria,
        $ingreso->nomenclatura,
        $ingreso->poliza,
        $ingreso->concepto,
        String::formatoNumero($ingreso->monto, 2, '', false)), false, true);

      $totalIngresos += floatval($ingreso->monto);
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY()+3);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(84, 20));
    $pdf->Row(array('INGRESOS CLIENTES', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(15, 15, 15, 39, 20));
    $pdf->Row(array('EMPRESA', 'REM', 'FOLIO', 'NOMBRE', 'ABONO'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetWidths(array(15, 15, 15, 39, 20));

    $totalRemisiones = 0;
    foreach ($caja['remisiones'] as $key => $remision)
    {
      $pdf->SetX(6);

      $pdf->SetAligns(array('L', 'R', 'R', 'L', 'R'));

      $pdf->Row(array(
        $remision->empresa,
        $remision->folio,
        $remision->folio_factura,
        $remision->observacion,
        String::formatoNumero($remision->monto, 2, '', false)), false, true);

      $totalRemisiones += floatval($remision->monto);
    }

    $ttotalIngresos = $totalRemisiones + $totalIngresos + $caja['saldo_inicial'];

    $pdf->SetX(6);
    $pdf->Row(array('', '', '', '', String::formatoNumero($totalRemisiones + $totalIngresos, 2, '', false)), false, true);

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(6);
    $pdf->Row(array('', '', '', 'TOTAL', String::formatoNumero($ttotalIngresos, 2, '$', false)), false, true);

    // if ($comprasY >= $pdf->GetY())
    // {
    //   $pdf->SetY($comprasY);
    // }


    // Boletas
    $pdf->SetFont('Arial','', 6);
    $pdf->SetXY(6, $pdf->GetY()+3);
    $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(15, 15, 22, 22, 15, 15));
    $pdf->Row(array('BOLETA', 'FECHA', 'FACTURADOR', 'SUPERVISOR', 'PAGADO', 'PENDIENTE'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetAligns(array('C', 'C', 'C', 'C', 'R', 'R'));
    $pdf->SetWidths(array(15, 15, 22, 22, 15, 15));

    // $caja['boletas'] = array_merge($caja['boletas'], $caja['boletas']);

    // $pdf->SetFont('Arial','', 7);
    // $boletasY = $pdf->GetY();

    $totalBoletasPagadas = $totalBoletasPendientes = $totalBoletas = 0;
    foreach ($caja['boletas'] as $key => $boleta)
    {
      if($pdf->GetY() >= $pdf->limiteY) {

        $pdf->AddPage();
        // // nomenclatura
        // $this->printCajaNomenclatura($pdf, $nomenclaturas);
        $pdf->SetAligns(array('C', 'C', 'C', 'C', 'R', 'R'));
        $pdf->SetWidths(array(15, 15, 22, 22, 15, 15));
        $pdf->SetFont('Helvetica','B', 7);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row(array('BOLETA', 'FECHA', 'FACTURADOR', 'SUPERVISOR', 'PAGADO', 'PENDIENTE'), true, true);

        $boletasY = $pdf->GetY();
      }

      $pdf->SetFont('Helvetica','', 7);
      $pdf->SetX(6);

      $pdf->SetAligns(array('C', 'C', 'C', 'C', 'R', 'R'));
      $pdf->Row(array(
        $boleta->boleta,
        $boleta->fecha,
        $boleta->proveedor,
        $boleta->productor,
        String::formatoNumero($boleta->importe_pagada, 2, '', false),
        String::formatoNumero($boleta->importe_pendiente, 2, '', false)
      ), false, true);

      $totalBoletas           += floatval($boleta->importe);
      $totalBoletasPagadas    += floatval($boleta->importe_pagada);
      $totalBoletasPendientes += floatval($boleta->importe_pendiente);
    }

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(6);
    $pdf->Row(array('', '', '', 'TOTAL',
      String::formatoNumero($totalBoletasPagadas, 2, '$', false),
      String::formatoNumero($totalBoletasPendientes, 2, '$', false)
    ), false, true);

    // Gastos del Dia
    $pag_aux2 = $pdf->page;
    $pdf->page = $pag_aux;
    $pdf->SetY($pag_yaux);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(111, 32);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(83, 17));
    $pdf->Row(array('GASTOS DEL DIA', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(111);
    $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(25, 15, 7, 36, 17));
    $pdf->Row(array('COD', 'EMPRESA', 'NOM', 'CONCEPTO', 'CARGO'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetAligns(array('C', 'C', 'C', 'C', 'R'));
    $pdf->SetWidths(array(25, 15, 7, 36, 17));

    $codigoAreas = array();
    $totalGastos = 0;
    foreach ($caja['gastos'] as $key => $gasto)
    {
      if ($pdf->GetY() >= $pdf->limiteY)
      {
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(111, 10);
        } else
          $pdf->AddPage();
        // // nomenclatura
        // $this->printCajaNomenclatura($pdf, $nomenclaturas);
        $pdf->SetFont('Helvetica','B', 7);
        $pdf->SetXY(111, $pdf->GetY());
        $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C'));
        $pdf->SetWidths(array(25, 15, 7, 36, 17));
        $pdf->Row(array('COD', 'EMPRESA', 'NOM', 'CONCEPTO', 'CARGO'), true, true);
      }

      $totalGastos += floatval($gasto->monto);

      $pdf->SetAligns(array('L', 'L', 'R', 'L', 'R'));
      $pdf->SetX(111);
      $pdf->Row(array(
        $gasto->codigo_fin.' '.$this->{($gasto->campo=='id_area'? 'compras_areas_model': 'catalogos_sft_model')}->getDescripCodigoSim($gasto->id_area),
        $gasto->empresa,
        $gasto->nomenclatura,
        // $gasto->folio,
        $gasto->concepto,
        String::float(String::formatoNumero($gasto->monto, 2, '', false))), false, true);

      // if($gasto->id_area != '' && !array_key_exists($gasto->id_area, $codigoAreas))
      //   $codigoAreas[$gasto->id_area] = $this->compras_areas_model->getDescripCodigo($gasto->id_area);
    }

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(111);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetAligns(array('L', 'R', 'L', 'L', 'R'));
    $pdf->Row(array('', '', '', 'TOTAL', String::formatoNumero($totalGastos, 2, '$', false)), true, true);

    // Boletas pendientes x recuperar
    $totalBoletas2 = 0;
    if ($noCajas == 1 || $noCajas == 3) {
      $pdf->SetLeftMargin(111);
      $pdf->SetFillColor(230, 230, 230);
      $pdf->SetXY(111, $pdf->GetY()+3);
      $pdf->SetAligns(array('L', 'C'));
      $pdf->SetWidths(array(75, 25));
      $pdf->Row(array('PENDIENTES DE RECUPERAR', 'IMPORTE'), true, true);

      $pdf->SetFont('Arial','', 6);
      $pdf->SetXY(111, $pdf->GetY());
      $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C'));
      $pdf->SetWidths(array(75, 25));
      $pdf->Row(array('FACTURADOR', 'IMPORTE'), true, true);

      $pdf->SetFont('Arial','', 6);
      $pdf->SetAligns(array('C', 'R'));
      $pdf->SetWidths(array(75, 25));

      foreach ($caja['boletas_arecuperar'] as $key => $boleta)
      {
        if($pdf->GetY() >= $pdf->limiteY) {

          if (count($pdf->pages) > $pdf->page) {
            $pdf->page++;
            $pdf->SetXY(111, 10);
          } else
            $pdf->AddPage();
          // // nomenclatura
          // $this->printCajaNomenclatura($pdf, $nomenclaturas);
          $pdf->SetFont('Helvetica','B', 7);
          $pdf->SetXY(111, $pdf->GetY());
          $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C'));
          $pdf->SetWidths(array(75, 25));
          $pdf->Row(array('FACTURADOR', 'IMPORTE'), true, true);

          $boletasY = $pdf->GetY();
        }

        $pdf->SetFont('Helvetica','', 7);
        $pdf->SetX(111);

        $pdf->SetWidths(array(75, 25));
        $pdf->Row(array(
          $boleta->proveedor,
          String::formatoNumero($boleta->importe, 2, '', false)), false, true);

        $totalBoletas2 += floatval($boleta->importe);
      }

      $pdf->SetFont('Arial', 'B', 7);
      $pdf->SetAligns(array('R', 'R'));
      $pdf->SetX(111);
      $pdf->Row(array('TOTAL', String::formatoNumero($totalBoletas2, 2, '$', false)), false, true);
      // $pdf->Row(array('', '', 'TOTAL', String::formatoNumero($totalBoletas2, 2, '$', false)), false, true);
    }

    $totalBoletasTransito = 0;
    if ($noCajas == 1 || $noCajas == 3) {
      // cheques de boletas en transito
      $pdf->SetLeftMargin(111);
      $pdf->SetFillColor(230, 230, 230);
      $pdf->SetXY(111, $pdf->GetY()+3);
      $pdf->SetAligns(array('L', 'C'));
      $pdf->SetWidths(array(83, 17));
      $pdf->Row(array('CHEQUES EN TRANSITO', 'IMPORTE'), true, true);

      $pdf->SetFont('Arial','', 6);
      $pdf->SetXY(111, $pdf->GetY());
      $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C'));
      $pdf->SetWidths(array(18, 28, 37, 17));
      $pdf->Row(array('FECHA', 'REF', 'PRODUTOR', 'IMPORTE'), true, true);

      $pdf->SetFont('Arial','', 6);
      $pdf->SetAligns(array('C', 'C', 'C', 'R'));
      $pdf->SetWidths(array(18, 28, 37, 17));

      foreach ($caja['boletas_ch_entransito'] as $key => $boleta)
      {
        if($pdf->GetY() >= $pdf->limiteY) {

          if (count($pdf->pages) > $pdf->page) {
            $pdf->page++;
            $pdf->SetXY(111, 10);
          } else
            $pdf->AddPage();
          // // nomenclatura
          // $this->printCajaNomenclatura($pdf, $nomenclaturas);
          $pdf->SetFont('Helvetica','B', 7);
          $pdf->SetXY(111, $pdf->GetY());
          $pdf->SetAligns(array('C', 'C', 'C', 'C'));
          $pdf->SetWidths(array(18, 28, 37, 17));
          $pdf->Row(array('FECHA', 'REF', 'PRODUTOR', 'IMPORTE'), true, true);

          $boletasY = $pdf->GetY();
        }

        $pdf->SetFont('Helvetica','', 7);
        $pdf->SetX(111);

        $pdf->SetAligns(array('C', 'C', 'C', 'R'));
        $pdf->Row(array(
          String::fechaAT($boleta->fecha),
          $boleta->numero_ref,
          $boleta->nombre_fiscal,
          String::formatoNumero($boleta->monto, 2, '', false)), false, true);

        $totalBoletasTransito += floatval($boleta->monto);
      }

      $pdf->SetFont('Arial', 'B', 7);
      $pdf->SetX(111);
      // $pdf->Row(array('', '', '', 'TOTAL', String::formatoNumero($totalBoletasTransito, 2, '$', false)), false, true);
      $pdf->Row(array('', '', 'TOTAL', String::formatoNumero($totalBoletasTransito, 2, '$', false)), false, true);
    }

    $totalSaldoClientes = 0;
    if ($noCajas == 2) {
      // Saldo de clientes remisiones
      $pdf->SetLeftMargin(111);
      $pdf->SetFillColor(230, 230, 230);
      $pdf->SetXY(111, $pdf->GetY()+3);
      $pdf->SetAligns(array('L', 'C'));
      $pdf->SetWidths(array(80, 20));
      $pdf->Row(array('SALDO DE CLIENTES', 'IMPORTE'), true, true);

      $pdf->SetFont('Arial','', 6);
      $pdf->SetXY(111, $pdf->GetY());
      $pdf->SetAligns(array('L', 'R'));
      $pdf->SetWidths(array(80, 20));
      $pdf->Row(array('CLIENTE', 'SALDO'), true, true);
      $pdf->SetFont('Arial','', 6);
      $pdf->SetAligns(array('L', 'R'));
      $pdf->SetWidths(array(80, 20));

      foreach ($caja['saldo_clientes'] as $key => $empresa)
      {
        if (count($empresa->clientes) > 0) {
          $pdf->Row(array(
            $empresa->nombre_fiscal,
            ''), true, true);
          foreach ($empresa->clientes as $key => $cliente)
          {
            if($pdf->GetY() >= $pdf->limiteY) {

              if (count($pdf->pages) > $pdf->page) {
                $pdf->page++;
                $pdf->SetXY(111, 10);
              } else
                $pdf->AddPage();
              // // nomenclatura
              // $this->printCajaNomenclatura($pdf, $nomenclaturas);
              $pdf->SetFont('Helvetica','B', 7);
              $pdf->SetXY(111, $pdf->GetY());
              $pdf->SetAligns(array('L', 'R'));
              $pdf->SetWidths(array(80, 20));
              $pdf->Row(array('CLIENTE', 'SALDO'), true, true);

              $boletasY = $pdf->GetY();
            }

            $pdf->SetFont('Helvetica','', 7);
            $pdf->SetX(111);

            $pdf->SetAligns(array('L', 'R'));
            $pdf->Row(array(
              $cliente->nombre,
              String::formatoNumero($cliente->saldo, 2, '', false)), false, true);

            $totalSaldoClientes += floatval($cliente->saldo);
          }
        }
      }

      $pdf->SetFont('Arial', 'B', 7);
      $pdf->SetX(111);
      // $pdf->Row(array('', '', '', 'TOTAL', String::formatoNumero($totalSaldoClientes, 2, '$', false)), false, true);
      $pdf->Row(array('TOTAL', String::formatoNumero($totalSaldoClientes, 2, '$', false)), false, true);
    }

    // Tabulaciones
    $pdf->SetFont('Arial','B', 6);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(210, 210, 210);
    $pdf->SetXY(111, $pdf->GetY() + 5);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(56));
    $pdf->Row(array('TABULACION DE EFECTIVO'), true, true);

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(255, 255, 255);
    // $pdf->SetXY(131, $boletasY - 5.4);
    $pdf->SetXY(111, $pdf->GetY());
    $pdf->SetAligns(array('C', 'C', 'C'));
    $pdf->SetWidths(array(15, 16, 25));
    $pdf->Row(array('NUMERO', 'DENOMIN.', 'TOTAL'), true, true);

    $page_aux = $pdf->page;
    $y_aux = $pdf->GetY();

    $pdf->SetAligns(array('R', 'R', 'R'));
    $pdf->SetFont('Arial','', 7);
    $totalEfectivo = 0;
    foreach ($caja['denominaciones'] as $key => $denominacion)
    {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(111, 10);
        } else
          $pdf->AddPage();
      }

      // $pdf->SetFont('Helvetica','', 7);
      $pdf->SetX(111);

      $pdf->Row(array(
        $denominacion['cantidad'],
        $denominacion['denominacion'],
        String::formatoNumero($denominacion['total'], 2, '', false)), false, true);

      $totalEfectivo += floatval($denominacion['total']);
    }

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(111);
    $pdf->SetAligns(array('C', 'R'));
    $pdf->SetWidths(array(31, 25));
    $pdf->Row(array('TOTAL EFECTIVO', String::formatoNumero($totalEfectivo, 2, '$', false)), false, true);

    $pdf->SetX(111);
    $pdf->Row(array('DIFERENCIA', String::formatoNumero($totalEfectivo - ($caja['saldo_inicial'] + $totalRemisiones + $totalIngresos - $totalBoletas - $ttotalGastos) , 2, '$', false)), false, false);

    // ajuste de pagina para imprimir los totales
    if ( $pdf->GetY()-$y_aux < 0 ) {
      $pdf->page = $page_aux;
    }
    $pdf->SetY($y_aux);

    $pdf->SetFont('Arial', 'B', 6);
    $pdf->SetXY(168, $pdf->GetY() );
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(25, 19));
    $pdf->Row(array('SALDO INICIAL', String::formatoNumero($caja['saldo_inicial'], 2, '$', false)), false, false);

    $pdf->SetX(168);
    $pdf->Row(array('TOTAL INGRESOS', String::formatoNumero($totalRemisiones + $totalIngresos, 2, '$', false)), false, false);
    $pdf->SetX(168);
    $pdf->Row(array('PAGO TOT LIMON ', String::formatoNumero($totalBoletasPagadas, 2, '$', false)), false, false);
    $pdf->SetX(168);
    $pdf->Row(array('PAGO TOT GASTOS', String::formatoNumero($ttotalGastos, 2, '$', false)), false, false);
    $pdf->SetX(168);
    $pdf->Row(array('EFECT. DEL CORTE', String::formatoNumero($caja['saldo_inicial'] + $totalRemisiones + $totalIngresos - $totalBoletasPagadas - $ttotalGastos, 2, '$', false)), false, false);
    $pdf->SetX(168);
    $pdf->Row(array('FONDO DE CAJA', String::formatoNumero($totalBoletas2 + $totalBoletasTransito + $totalEfectivo, 2, '$', false)), false, false);

    // $page_aux = $pdf->page;
    $pdf->page = 1;
    $pdf->SetFont('Arial','B', 8);
    $pdf->SetXY(110, 26.5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(104));
    $pdf->Row(array('FONDO DE CAJA '.String::formatoNumero($totalBoletas2 + $totalBoletasTransito + $totalEfectivo , 2, '$', false)), false, false);
    $pdf->page = count($pdf->pages); //$page_aux>$pag_aux2? $page_aux: $pag_aux2;

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

  public function getDataGasto($id_gasto)
  {
    $gastos = $this->db->query(
      "SELECT cg.id_gasto, cg.concepto, cg.fecha, cg.monto, cc.id_categoria, cc.abreviatura as empresa, cc.nombre as empresal,
          cg.folio, cg.id_nomenclatura, cn.nomenclatura, COALESCE(cca.id_cat_codigos, ca.id_area) AS id_area,
          COALESCE(cca.nombre, ca.nombre) AS nombre_codigo,
          COALESCE((CASE WHEN cca.codigo <> '' THEN cca.codigo ELSE cca.nombre END), ca.codigo_fin) AS codigo_fin,
          (CASE WHEN cca.id_cat_codigos IS NULL THEN 'id_area' ELSE 'id_cat_codigos' END) AS campo,
          cg.no_caja, cg.no_impresiones, cg.fecha_creacion, (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS usuario_creo
       FROM cajachica_gastos cg
         INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
         INNER JOIN cajachica_nomenclaturas cn ON cn.id = cg.id_nomenclatura
         LEFT JOIN compras_areas ca ON ca.id_area = cg.id_area
         LEFT JOIN otros.cat_codigos AS cca ON cca.id_cat_codigos = cg.id_cat_codigos
         LEFT JOIN usuarios AS u ON u.id = cg.id_usuario
       WHERE cg.id_gasto = '{$id_gasto}'
       ORDER BY cg.id_gasto ASC"
    )->row();

    return $gastos;
  }

  public function printVale($id_gasto)
  {
    $this->load->model('compras_areas_model');
    $this->load->model('catalogos_sft_model');

    $gastos = $this->getDataGasto($id_gasto);

    // echo "<pre>";
    //   var_dump($gastos);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 130));
    $pdf->limiteY = 50;
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $pdf->show_head = false;

    $pdf->SetFont('helvetica','B', 8);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()-5);
    $pdf->Row(array($gastos->empresal), false, false);

    $pdf->SetFont('helvetica','', 8);
    $pdf->SetXY(0, 0);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()+4);
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
    $cod_sof = $gastos->codigo_fin.' '.$this->{($gastos->campo=='id_area'? 'compras_areas_model': 'catalogos_sft_model')}->getDescripCodigo($gastos->id_area);
    $pdf->Row(array($cod_sof), false, false);
    $pdf->SetX(0);
    $pdf->Line(0, $pdf->GetY()-1, 62, $pdf->GetY()-1);

    $pdf->SetX(0);
    $pdf->Row(array($gastos->concepto), false, false);

    $pdf->SetX(0);
    $pdf->Row(array( 'Impresión '.($gastos->no_impresiones==0? 'ORIGINAL': 'COPIA '.$gastos->no_impresiones)), false, false);
    $pdf->Line(0, $pdf->GetY()-1, 62, $pdf->GetY()-1);

    $pdf->SetX(0);
    $pdf->SetAligns(array('C', 'C', 'C'));
    $pdf->SetWidths(array(21, 21, 21));
    $pdf->Row(array('AUTORIZA', 'RECIBIO', 'FECHA'), false, false);
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row(array('', '', String::fechaAT($gastos->fecha)), false, false);
    $pdf->Line(0, $pdf->GetY()+4, 62, $pdf->GetY()+4);
    $pdf->Line(21, $pdf->GetY()-12, 21, $pdf->GetY()+4);
    $pdf->Line(42, $pdf->GetY()-12, 42, $pdf->GetY()+4);

    $pdf->SetXY(0, $pdf->GetY()+5);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(21, 42));
    $pdf->Row(array('Creado por:', $gastos->usuario_creo), false, false);
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row(array('Creado:', String::fechaAT($gastos->fecha_creacion)), false, false);

    $this->db->where('id_gasto', $gastos->id_gasto)->set('no_impresiones', 'no_impresiones+1', false)->update('cajachica_gastos');

    // $pdf->AutoPrint(true);
    $pdf->Output('vale_gastos.pdf', 'I');
  }

  public function getDataRemision($fecha, $id_remision, $row, $noCaja)
  {
    $remisiones = $this->db->query(
      "SELECT cr.id_remision, cr.monto, cr.observacion, f.folio, cr.id_categoria, cc.abreviatura as empresa,
              COALESCE((select (serie || folio) as folio from facturacion where id_factura = fvr.id_factura), cr.folio_factura) as folio_factura,
              cr.id_movimiento, cr.row, cr.fecha, c.nombre_fiscal AS cliente, cc.nombre AS empresar, cr.folio AS folio_caja,
              cr.no_impresiones, cr.no_caja, cr.fecha_creacion, (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS usuario_creo,
              c.id_cliente
       FROM cajachica_remisiones cr
       INNER JOIN facturacion f ON f.id_factura = cr.id_remision
       INNER JOIN clientes c ON c.id_cliente = f.id_cliente
       INNER JOIN empresas e ON e.id_empresa = f.id_empresa
       INNER JOIN cajachica_categorias cc ON cc.id_categoria = cr.id_categoria
       LEFT JOIN facturacion_ventas_remision_pivot fvr ON fvr.id_venta = f.id_factura
       LEFT JOIN usuarios u ON u.id = cr.id_usuario
       WHERE cr.fecha = '{$fecha}' AND cr.id_remision = '{$id_remision}' AND cr.row = {$row} AND cr.no_caja = {$noCaja}"
    )->row();

    $caja = $this->get($fecha, $noCaja );

    $remisiones->caja_abierta = $caja['status'];
    foreach ($caja['saldo_clientes'] as $key => $empresa) {
      foreach ($empresa->clientes as $key2 => $cliente) {
        if ($cliente->id_cliente === $remisiones->id_cliente) {
          $remisiones->saldo = $cliente;
        }
      }
    }

    return $remisiones;
  }

  public function printValeRemision($fecha, $id_remision, $row, $noCaja)
  {

    $remisiones = $this->getDataRemision($fecha, $id_remision, $row, $noCaja);

    // echo "<pre>";
    //   var_dump($remisiones);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 130));
    $pdf->limiteY = 50;
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $pdf->show_head = false;

    $pdf->SetFont('helvetica','B', 8);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()-5);
    $pdf->Row(array($remisiones->empresar), false, false);

    $pdf->SetFont('helvetica','', 8);
    $pdf->SetXY(0, 0);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()+4);
    $pdf->Row(array('PAGO DE REMISION EN CAJA'), false, false);

    $pdf->SetAligns(array('L'));
    // $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()-6);
    $pdf->Row(array('Folio: '.$remisiones->folio_caja), false, false);

    $pdf->SetWidths(array(20, 43));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetX(0);
    $pdf->Row(array('Caja: '.$remisiones->no_caja, 'Remision: '.$remisiones->folio ), false, false);

    $pdf->SetX(0);
    $pdf->Row(array('CANTIDAD:', String::formatoNumero($remisiones->monto, 2, '$', false)), false, false);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->SetX(0);
    $pdf->Row(array(String::num2letras($remisiones->monto)), false, false);
    $pdf->SetX(0);
    $pdf->Line(0, $pdf->GetY()-1, 62, $pdf->GetY()-1);

    $pdf->SetX(0);
    $pdf->Row(array($remisiones->observacion), false, false);

    $pdf->SetX(0);
    $pdf->Row(array( 'Impresión '.($remisiones->no_impresiones==0? 'ORIGINAL': 'COPIA '.$remisiones->no_impresiones)), false, false);
    $pdf->Line(0, $pdf->GetY()-1, 62, $pdf->GetY()-1);

    $pdf->SetX(0);
    $pdf->SetAligns(array('C', 'C', 'C'));
    $pdf->SetWidths(array(21, 21, 21));
    $pdf->Row(array('AUTORIZA', 'RECIBIO', 'FECHA'), false, false);
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row(array('', '', String::fechaAT($remisiones->fecha)), false, false);
    $pdf->Line(0, $pdf->GetY()+4, 62, $pdf->GetY()+4);
    $pdf->Line(21, $pdf->GetY()-12, 21, $pdf->GetY()+4);
    $pdf->Line(42, $pdf->GetY()-12, 42, $pdf->GetY()+4);

    $pdf->SetXY(0, $pdf->GetY()+5);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(21, 42));
    $pdf->Row(array('Creado por:', $remisiones->usuario_creo), false, false);
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row(array('Creado:', String::fechaAT($remisiones->fecha_creacion)), false, false);

    if (isset($remisiones->saldo) && $remisiones->saldo->show_saldo == 't') {
      $saldo = $remisiones->saldo->saldo - ($remisiones->caja_abierta==='t'? $remisiones->monto: 0);
      $pdf->SetXY(0, $pdf->GetY());
      $pdf->Row(array('SALDO DEUDOR ACTUALIZADO: ', String::formatoNumero($saldo, 2, '$', false)), false, false);
    }

    $this->db->update('cajachica_remisiones', ['no_impresiones' => $remisiones->no_impresiones+1],
        "fecha = '{$fecha}' AND id_remision = '{$id_remision}' AND row = {$row} AND no_caja = {$noCaja}");

    // $pdf->AutoPrint(true);
    $pdf->Output('vale_remision.pdf', 'I');
  }

  public function getDataValeIngresos($id_ingresos, $noCaja)
  {
    $ingreso = $this->db->query(
      "SELECT ci.*, cc.abreviatura as abr_empresa, cc.nombre AS empresa, cn.nomenclatura,
        (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS usuario_creo
       FROM cajachica_ingresos ci
       INNER JOIN cajachica_categorias cc ON cc.id_categoria = ci.id_categoria
       INNER JOIN cajachica_nomenclaturas cn ON cn.id = ci.id_nomenclatura
       LEFT JOIN banco_movimientos bm ON bm.id_movimiento = ci.id_movimiento
       LEFT JOIN usuarios u ON u.id = ci.id_usuario
       WHERE ci.id_ingresos = {$id_ingresos} AND ci.no_caja = {$noCaja}"
    )->row();

    return $ingreso;
  }

  public function printValeIngresos($id_ingresos, $noCaja)
  {

    $ingreso = $this->getDataValeIngresos($id_ingresos, $noCaja);

    // echo "<pre>";
    //   var_dump($ingreso);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 130));
    $pdf->limiteY = 50;
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);
    $pdf->show_head = false;

    $pdf->SetFont('helvetica','B', 8);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()-5);
    $pdf->Row(array($ingreso->empresa), false, false);

    $pdf->SetFont('helvetica','', 8);
    $pdf->SetXY(0, 0);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()+4);
    $pdf->Row(array('INGRESOS'), false, false);

    $pdf->SetAligns(array('L'));
    // $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()-6);
    $pdf->Row(array('Folio: '.$ingreso->id_ingresos), false, false);

    $pdf->SetWidths(array(20, 43));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetX(0);
    $pdf->Row(array('Caja: '.$ingreso->no_caja, '' ), false, false);

    $pdf->SetX(0);
    $pdf->Row(array('CANTIDAD:', String::formatoNumero($ingreso->monto, 2, '$', false)), false, false);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->SetX(0);
    $pdf->Row(array(String::num2letras($ingreso->monto)), false, false);
    $pdf->SetX(0);
    $pdf->Line(0, $pdf->GetY()-1, 62, $pdf->GetY()-1);

    $pdf->SetX(0);
    $pdf->Row(array($ingreso->concepto), false, false);

    $pdf->SetX(0);
    $pdf->Row(array( 'Impresión '.($ingreso->no_impresiones==0? 'ORIGINAL': 'COPIA '.$ingreso->no_impresiones)), false, false);
    $pdf->Line(0, $pdf->GetY()-1, 62, $pdf->GetY()-1);

    $pdf->SetX(0);
    $pdf->SetAligns(array('C', 'C', 'C'));
    $pdf->SetWidths(array(21, 21, 21));
    $pdf->Row(array('AUTORIZA', 'RECIBIO', 'FECHA'), false, false);
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row(array('', '', String::fechaAT($ingreso->fecha)), false, false);
    $pdf->Line(0, $pdf->GetY()+4, 62, $pdf->GetY()+4);
    $pdf->Line(21, $pdf->GetY()-12, 21, $pdf->GetY()+4);
    $pdf->Line(42, $pdf->GetY()-12, 42, $pdf->GetY()+4);

    $pdf->SetXY(0, $pdf->GetY()+5);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(21, 42));
    $pdf->Row(array('Creado por:', $ingreso->usuario_creo), false, false);
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row(array('Creado:', String::fechaAT($ingreso->fecha_creacion)), false, false);

    $this->db->update('cajachica_ingresos', ['no_impresiones' => $ingreso->no_impresiones+1],
        "id_ingresos = '{$id_ingresos}' AND no_caja = {$noCaja}");

    // $pdf->AutoPrint(true);
    $pdf->Output('vale_ingreso.pdf', 'I');
  }


  /**
   * Reporte gastos caja chica
   *
   * @return
   */
  public function getRptGastosData()
  {
    $sqlprs = $sqlprs1 = $sql = '';
      $idsproveedores = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    // $this->load->model('empresas_model');
    // $client_default = $this->empresas_model->getDefaultEmpresa();
    // $_GET['did_empresa'] = (isset($_GET['did_empresa']{0}) ? $_GET['did_empresa'] : $client_default->id_empresa);
    // $_GET['dempresa']    = (isset($_GET['dempresa']{0}) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND cc.id_categoria = '".$this->input->get('did_empresa')."'";
      $sqlprs1 .= " AND cc.id_categoria = '".$this->input->get('did_empresa')."'";
    }

    if ($this->input->get('fnomenclatura') != '') {
      $sql .= " AND cn.id = ".$this->input->get('fnomenclatura');
      $sqlprs1 .= " AND cn.id = ".$this->input->get('fnomenclatura');
    }

    if ($this->input->get('fno_caja') != '') {
      $sql .= " AND cg.no_caja = ".$this->input->get('fno_caja');
    }

    if ($this->input->get('dprov_clien') != '') {
      $sql .= " AND cg.concepto LIKE '%".$this->input->get('dprov_clien')."%'";
      $sqlprs .= " AND concepto LIKE '%".$this->input->get('dprov_clien')."%'";
    }

    $response = array();
    if ($this->input->get('fno_caja') == 'prest1') {
      $gastos = $this->db->query(
        "SELECT id_prestamo, id_prestamo_nom, id_empleado, id_categoria, id_nomenclatura, concepto, fecha, monto, categoria, nombre_nomen, nomenclatura,
          null AS folio, null AS id_area, null AS codigo_fin, null AS campo, null AS reposicion
        FROM (
          SELECT cp.id_prestamo, cp.id_prestamo_nom, cp.id_empleado, cp.id_categoria, cp.id_nomenclatura, cp.concepto, cp.fecha, cp.monto,
            cc.abreviatura as categoria, cn.nombre AS nombre_nomen, cn.nomenclatura
          FROM otros.cajaprestamo_prestamos cp
          INNER JOIN cajachica_categorias cc ON cc.id_categoria = cp.id_categoria
          INNER JOIN cajachica_nomenclaturas cn ON cn.id = cp.id_nomenclatura
          WHERE cp.fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}' AND cp.no_caja = 1 {$sqlprs1}
          UNION
          SELECT cp.id_prestamo AS id_prestamo, np.id_prestamo AS id_prestamo_nom, np.id_usuario AS id_empleado, null AS id_categoria,
            null AS id_nomenclatura, ('PTMO NOM ' || u.nombre || ' ' || u.apellido_paterno) AS concepto, Date(np.fecha) AS fecha,
            np.prestado AS monto, null AS categoria, null AS nombre_nomen, null AS nomenclatura
          FROM nomina_prestamos np
          INNER JOIN usuarios u ON u.id = np.id_usuario
          LEFT JOIN otros.cajaprestamo_prestamos cp ON np.id_prestamo = cp.id_prestamo_nom
          WHERE (np.tipo = 'ef') AND np.fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}' AND cp.id_prestamo IS NULL
        ) AS t
        WHERE 1 = 1 {$sqlprs}
        ORDER BY id_prestamo_nom ASC"
      );
    } else {
      $gastos = $this->db->query("SELECT cg.id_gasto, cc.id_categoria, cc.nombre AS categoria,
          cn.nombre AS nombre_nomen, cn.nomenclatura, cg.concepto, cg.monto, cg.fecha, cg.folio,
          cn.id AS id_nomenclatura, COALESCE(cca.id_cat_codigos, ca.id_area) AS id_area,
          COALESCE((CASE WHEN cca.codigo <> '' THEN cca.codigo ELSE cca.nombre END), ca.codigo_fin) AS codigo_fin,
          (CASE WHEN cca.id_cat_codigos IS NULL THEN 'id_area' ELSE 'id_cat_codigos' END) AS campo,
          cg.reposicion
        FROM cajachica_gastos cg
          INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
          INNER JOIN cajachica_nomenclaturas cn ON cn.id = cg.id_nomenclatura
          LEFT JOIN compras_areas ca ON ca.id_area = cg.id_area
          LEFT JOIN otros.cat_codigos AS cca ON cca.id_cat_codigos = cg.id_cat_codigos
        WHERE fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
          {$sql}
        ORDER BY id_categoria ASC, fecha ASC");
    }
    $response = $gastos->result();

    return $response;
  }
  /**
   * Reporte existencias por unidad pdf
   */
  public function getRptGastosPdf(){
    $res = $this->getRptGastosData();

    $this->load->model('compras_areas_model');
    $this->load->model('catalogos_sft_model');

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    $pdf->titulo2 = 'Reporte de Gastos';
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'L', 'C', 'L', 'L', 'R');
    $widths = array(18, 24, 22, 20, 75, 12, 30);
    $header = array('Fecha', 'Codigo', 'Nomenclatura', 'Folio', 'Concepto', 'Rep', 'Importe');

    $codigoAreas = array();
    $aux_categoria = '';
    $total_nomenclatura = array();
    $proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = $reposicion_total = 0;
    foreach($res as $key => $producto){
      if($pdf->GetY() >= $pdf->limiteY || $key==0 || $aux_categoria != $producto->id_categoria){ //salta de pagina si exede el max
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

        if($aux_categoria != $producto->id_categoria && $key > 0)
        {
          $this->getRptGastosTotales($pdf, $proveedor_total, $reposicion_total, $total_nomenclatura, $aux_categoria, $producto);
        }elseif($key == 0)
          $aux_categoria = $producto->id_categoria;

        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','B',10);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(150));
        $pdf->Row(array($producto->categoria), false, false);

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
        $pdf->SetY($pdf->GetY()+2);
      }

      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','',8);
      $datos = array($producto->fecha,
        $producto->codigo_fin,
        $producto->nomenclatura,
        $producto->folio,
        $producto->concepto,
        $producto->reposicion=='t'? 'Si': 'No',
        String::formatoNumero($producto->monto, 2, '', false),
        );
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false, false);

      if(array_key_exists($producto->id_nomenclatura, $total_nomenclatura))
        $total_nomenclatura[$producto->id_nomenclatura][0] += $producto->monto;
      else
        $total_nomenclatura[$producto->id_nomenclatura] = array($producto->monto, $producto->nombre_nomen, $producto->nomenclatura);

      if($producto->id_area != '' && !array_key_exists($producto->id_area, $codigoAreas))
          $codigoAreas[$producto->id_area] = $this->{($producto->campo=='id_area'? 'compras_areas_model': 'catalogos_sft_model')}->getDescripCodigo($producto->id_area);

      $proveedor_total += $producto->monto;
      $reposicion_total += $producto->reposicion=='t'? $producto->monto: 0;
    }

    if(isset($producto))
      $this->getRptGastosTotales($pdf, $proveedor_total, $reposicion_total, $total_nomenclatura, $aux_categoria, $producto);

    if(count($codigoAreas) > 0){
      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(200));
      $pdf->Row(array('COD/AREA: ' . implode(' - ', $codigoAreas)), false, false);
    }

    $pdf->Output('compras_proveedor.pdf', 'I');
  }
  public function getRptGastosXls(){
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=reporte_ventas.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getRptGastosData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte de Gastos';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";

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
          <td style="border:1px solid #000;background-color: #cccccc;">Fecha</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Nomenclatura</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Folio</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Concepto</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Importe</td>
        </tr>';
    $aux_categoria = '';
    $total_nomenclatura = array();
    $proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
    foreach($res as $key => $producto) {

      if($aux_categoria != $producto->id_categoria && $key > 0)
      {
        $html .= $this->getRptGastosTotalesXls($proveedor_total, $total_nomenclatura, $aux_categoria, $producto);
      }elseif($key == 0)
        $aux_categoria = $producto->id_categoria;

      $html .= '<tr style="">
          <td style="border:1px solid #000;">'.$producto->fecha.'</td>
          <td style="border:1px solid #000;">'.$producto->nomenclatura.'</td>
          <td style="border:1px solid #000;">'.$producto->folio.'</td>
          <td style="border:1px solid #000;">'.$producto->concepto.'</td>
          <td style="border:1px solid #000;">'.$producto->monto.'</td>
        </tr>';

      if(array_key_exists($producto->id_nomenclatura, $total_nomenclatura))
        $total_nomenclatura[$producto->id_nomenclatura][0] += $producto->monto;
      else
        $total_nomenclatura[$producto->id_nomenclatura] = array($producto->monto, $producto->nombre_nomen, $producto->nomenclatura);

      $proveedor_total += $producto->monto;
    }

    if(isset($producto))
      $html .= $this->getRptGastosTotalesXls($proveedor_total, $total_nomenclatura, $aux_categoria, $producto);

    $html .= '
      </tbody>
    </table>';

    echo $html;
  }

  public function getRptGastosTotales(&$pdf, &$proveedor_total, &$reposicion_total, &$total_nomenclatura, &$aux_categoria, &$producto)
  {
    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B',8);
    $datos = array('Total General',
      String::formatoNumero(($proveedor_total), 2, '', false),
    );
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(171, 30));
    $pdf->Row($datos, false);

    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(150));
    $pdf->Row(array('DESGLOSE DE GASTOS'), false, false);

    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('C', 'L', 'R', 'R'));
    $pdf->SetWidths(array(25, 50, 50, 50));
    $pdf->Row(array('Nomenclatura', 'Concepto', 'Total por concepto', 'Total reposicion'), false, false);
    foreach ($total_nomenclatura as $keyn => $nomen)
    {
      if($pdf->GetY()+6 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->Row(array($nomen[2], $nomen[1], String::formatoNumero($nomen[0], 2, '', false) ), false, false);
    }
    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('R', 'R', 'R'));
    $pdf->SetWidths(array(75, 50, 50));
    $pdf->Row(array('', String::formatoNumero(($proveedor_total), 2, '', false), String::formatoNumero(($reposicion_total), 2, '', false)), false);

    $aux_categoria      = $producto->id_categoria;
    $proveedor_total    = 0;
    $total_nomenclatura = array();

    $pdf->SetXY(6, $pdf->GetY()+8);
  }

  public function getRptGastosTotalesXls(&$proveedor_total, &$total_nomenclatura, &$aux_categoria, &$producto)
  {
    $html = '
    <tr style="font-weight:bold">
      <td colspan="4">Total General</td>
      <td style="border:1px solid #000;">'.($proveedor_total).'</td>
    </tr>
    <tr style="font-weight:bold">
      <td colspan="5">DESGLOSE DE GASTOS</td>
    </tr>
    <tr style="font-weight:bold">
      <td style="border:1px solid #000;background-color: #cccccc;">Nomenclatura</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Concepto</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Total por concepto</td>
    </tr>
    ';

    foreach ($total_nomenclatura as $keyn => $nomen)
    {
      $html .= '<tr style="font-weight:bold">
        <td style="border:1px solid #000;">'.$nomen[2].'</td>
        <td style="border:1px solid #000;">'.$nomen[1].'</td>
        <td style="border:1px solid #000;">'.$nomen[0].'</td>
      </tr>';
    }

    $html .= '<tr style="font-weight:bold">
        <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">Total</td>
        <td style="border:1px solid #000;background-color: #cccccc;">'.$proveedor_total.'</td>
      </tr>';

    $aux_categoria      = $producto->id_categoria;
    $proveedor_total    = 0;
    $total_nomenclatura = array();
    return $html;
  }

  /**
   * Reporte gastos caja chica
   *
   * @return
   */
  public function getRptIngresosData()
  {
    $sql3 = $sql = $sql2 = '';
    $sqlpres1 = $sqlpres = '';
      $idsproveedores = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    if($this->input->get('did_empresa') != ''){
      $sql .= " AND cc.id_categoria = '".$this->input->get('did_empresa')."'";
      $sqlpres .= " AND id_categoria = '".$this->input->get('did_empresa')."'";
    }

    if ($this->input->get('fnomenclatura') != '') {
      $sql2 .= " AND cn.id = ".$this->input->get('fnomenclatura');
      $sqlpres1 .= " AND cn.id = ".$this->input->get('fnomenclatura');
    }

    if ($this->input->get('fno_caja') != '') {
      $sql2 .= " AND ci.no_caja = ".$this->input->get('fno_caja');
      $sql3 .= " AND cr.no_caja = ".$this->input->get('fno_caja');
    }

    if ($this->input->get('dprov_clien') != '') {
      $sql2 .= " AND Lower(ci.concepto) LIKE '%".mb_strtolower($this->input->get('dprov_clien'))."%'";
      $sql3 .= " AND Lower(cr.observacion) LIKE '%".mb_strtolower($this->input->get('fno_caja'))."%'";
      $sqlpres .= " AND Lower(concepto) LIKE '%".mb_strtolower($this->input->get('dprov_clien'))."%'";
    }

    $response = array('movimientos' => array(), 'remisiones' => array());

    if ($this->input->get('fno_caja') == 'prest1') {
      $movimientos = $this->db->query(
        "SELECT id_pago, id_empleado, id_empresa, anio, semana, id_prestamo, id_categoria, concepto, monto, fecha, id_nomenclatura, categoria,
          nombre_nomen, nomenclatura, null AS poliza
        FROM (
          SELECT cp.id_pago, cp.id_empleado, cp.id_empresa, cp.anio, cp.semana, cp.id_prestamo, cp.id_categoria, cp.concepto, cp.monto, cp.fecha,
            cp.id_nomenclatura, cc.abreviatura as categoria, cn.nombre AS nombre_nomen, cn.nomenclatura
          FROM otros.cajaprestamo_pagos cp
            INNER JOIN cajachica_categorias cc ON cc.id_categoria = cp.id_categoria
            INNER JOIN cajachica_nomenclaturas cn ON cn.id = cp.id_nomenclatura
          WHERE cp.fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}' AND cp.no_caja = 1 {$sqlpres1}
          UNION
          SELECT cp.id_pago, np.id_empleado, np.id_empresa, np.anio, np.semana, np.id_prestamo, cp.id_categoria,
            (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno || '; Sem ' || np.semana) AS concepto,
            np.monto, np.fecha, cp.id_nomenclatura, null AS categoria, null AS nombre_nomen, null AS nomenclatura
          FROM nomina_fiscal_prestamos np
            INNER JOIN nomina_prestamos npp ON npp.id_prestamo = np.id_prestamo
            INNER JOIN usuarios u ON u.id = np.id_empleado
            LEFT JOIN otros.cajaprestamo_pagos cp ON (cp.id_empleado = cp.id_empleado AND np.id_empresa = cp.id_empresa AND np.anio = cp.anio AND np.semana = cp.semana AND np.id_prestamo = cp.id_prestamo)
          WHERE np.saldado = 'f' AND (npp.tipo = 'ef') AND np.fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}' AND cp.id_pago IS NULL
        ) AS t
        WHERE 1 = 1 {$sqlpres}
        ORDER BY id_pago ASC"
      );
      $response['movimientos'] = $movimientos->result();
    } else {
      $movimientos = $this->db->query("SELECT ci.id_ingresos, cc.id_categoria, cc.nombre AS categoria,
            cn.nombre AS nombre_nomen, cn.nomenclatura, ci.concepto, ci.monto, ci.fecha, ci.poliza,
            cn.id AS id_nomenclatura
          FROM cajachica_ingresos ci
            INNER JOIN cajachica_categorias cc ON cc.id_categoria = ci.id_categoria
            INNER JOIN cajachica_nomenclaturas cn ON cn.id = ci.id_nomenclatura
          WHERE ci.fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
            {$sql} {$sql2}
          ORDER BY id_categoria ASC, fecha ASC");
      $response['movimientos'] = $movimientos->result();

      $remisiones = $this->db->query("SELECT cr.id_remision, cc.id_categoria, cc.nombre AS categoria,
            f.folio, f.serie, cr.observacion, cr.monto, cr.fecha, cr.folio_factura
          FROM cajachica_remisiones cr
            INNER JOIN cajachica_categorias cc ON cc.id_categoria = cr.id_categoria
            INNER JOIN facturacion f ON f.id_factura = cr.id_remision
          WHERE cr.fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
            {$sql} {$sql3}
          ORDER BY id_categoria ASC, fecha ASC");
      $response['remisiones'] = $remisiones->result();
    }

    return $response;
  }
  /**
   * Reporte existencias por unidad pdf
   */
  public function getRptIngresosPdf(){
    $res = $this->getRptIngresosData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    $pdf->titulo2 = 'Reporte de Ingresos';
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'L', 'L', 'R');
    $widths = array(20, 22, 45, 80, 35);
    $header = array('Fecha', 'Nomenclatura', 'Poliza', 'Concepto', 'Importe');

    $aux_categoria = '';
    $total_nomenclatura = array();
    $aux_proveedor_total = $proveedor_total = 0;
    foreach($res['movimientos'] as $key => $producto){
      if($pdf->GetY() >= $pdf->limiteY || $key==0 || $aux_categoria != $producto->id_categoria){ //salta de pagina si exede el max
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

        if($aux_categoria != $producto->id_categoria && $key > 0)
        {
          $aux_proveedor_total = $proveedor_total;
          $this->getRptMovimientosTotales($pdf, $proveedor_total, $total_nomenclatura, $aux_categoria, $producto);

          $this->getRptRemisionesTotales($pdf, $res['remisiones'], $aux_proveedor_total, $producto->id_categoria);
        }elseif($key == 0)
          $aux_categoria = $producto->id_categoria;

        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','B',12);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(150));
        $pdf->Row(array($producto->categoria), false, false);

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
        $pdf->SetY($pdf->GetY()+2);
      }

      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','',8);
      $datos = array($producto->fecha,
        $producto->nomenclatura,
        $producto->poliza,
        $producto->concepto,
        String::formatoNumero($producto->monto, 2, '', false),
        );
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false, false);

      if(array_key_exists($producto->id_nomenclatura, $total_nomenclatura))
        $total_nomenclatura[$producto->id_nomenclatura][0] += $producto->monto;
      else
        $total_nomenclatura[$producto->id_nomenclatura] = array($producto->monto, $producto->nombre_nomen, $producto->nomenclatura);

      $proveedor_total += $producto->monto;
    }

    if(isset($producto))
    {
      $aux_proveedor_total = $proveedor_total;
      $this->getRptMovimientosTotales($pdf, $proveedor_total, $total_nomenclatura, $aux_categoria, $producto);

      $this->getRptRemisionesTotales($pdf, $res['remisiones'], $aux_proveedor_total, $producto->id_categoria);
    }

    $pdf->Output('ingresos_caja.pdf', 'I');
  }

  public function getRptIngresosXls(){
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=ingresos_caja.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getRptIngresosData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte de Ingresos';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";

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
    $aux_categoria = '';
    $total_nomenclatura = array();
    $aux_proveedor_total = $proveedor_total = 0;
    foreach($res['movimientos'] as $key => $producto){

      if($key==0 || $aux_categoria != $producto->id_categoria) {
        if($aux_categoria != $producto->id_categoria && $key > 0)
        {
          $aux_proveedor_total = $proveedor_total;
          $html .= $this->getRptMovimientosTotalesXls($proveedor_total, $total_nomenclatura, $aux_categoria, $producto);

          $html .= $this->getRptRemisionesTotalesXls($res['remisiones'], $aux_proveedor_total, $producto->id_categoria);
        }elseif($key == 0)
          $aux_categoria = $producto->id_categoria;

        $html .= '<tr style="font-weight:bold">
          <td colspan="6"></td>
        </tr>
        <tr style="font-weight:bold">
          <td colspan="6" style="background-color: #cccccc;">'.$producto->categoria.'</td>
        </tr>
        <tr style="font-weight:bold">
          <td style="border:1px solid #000;background-color: #cccccc;">Fecha</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Nomenclatura</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Poliza</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Concepto</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Importe</td>
        </tr>';
      }

      $html .= '<tr style="">
          <td style="border:1px solid #000;">'.$producto->fecha.'</td>
          <td style="border:1px solid #000;">'.$producto->nomenclatura.'</td>
          <td style="border:1px solid #000;">'.$producto->poliza.'</td>
          <td style="border:1px solid #000;">'.$producto->concepto.'</td>
          <td style="border:1px solid #000;">'.$producto->monto.'</td>
        </tr>';

      if(array_key_exists($producto->id_nomenclatura, $total_nomenclatura))
        $total_nomenclatura[$producto->id_nomenclatura][0] += $producto->monto;
      else
        $total_nomenclatura[$producto->id_nomenclatura] = array($producto->monto, $producto->nombre_nomen, $producto->nomenclatura);

      $proveedor_total += $producto->monto;
    }

    if(isset($producto))
    {
      $aux_proveedor_total = $proveedor_total;
      $html .= $this->getRptMovimientosTotalesXls($proveedor_total, $total_nomenclatura, $aux_categoria, $producto);

      $html .= $this->getRptRemisionesTotalesXls($res['remisiones'], $aux_proveedor_total, $producto->id_categoria);
    }

    $html .= '
      </tbody>
    </table>';

    echo $html;
  }

  /**
   * Reporte ingresos/gastos caja chica
   *
   * @return
   */
  public function getRptIngresosGastosData()
  {
    $sqlpres = $sql = $sql1 = $sql3 = $sql2 = '';
      $idsproveedores = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    if($this->input->get('did_empresa') != ''){
      $sql .= " AND cc.id_categoria = '".$this->input->get('did_empresa')."'";
      $sqlpres .= " AND id_categoria = '".$this->input->get('did_empresa')."'";
    }

    // if ($this->input->get('fnomenclatura') != '')
    //   $sql2 .= " AND cn.id = ".$this->input->get('fnomenclatura');

    if ($this->input->get('fno_caja') != '') {
      $sql1 .= " AND ci.no_caja = ".$this->input->get('fno_caja');
      $sql2 .= " AND cr.no_caja = ".$this->input->get('fno_caja');
      $sql3 .= " AND cg.no_caja = ".$this->input->get('fno_caja');
    }

    if ($this->input->get('dprov_clien') != '') {
      $sql1 .= " AND Upper(ci.concepto) LIKE '%".mb_strtoupper($this->input->get('dprov_clien'), 'UTF-8')."%'";
      $sql2 .= " AND Upper(cr.observacion) LIKE '%".mb_strtoupper($this->input->get('dprov_clien'), 'UTF-8')."%'";
      $sql3 .= " AND Upper(cg.concepto) LIKE '%".mb_strtoupper($this->input->get('fno_caja'), 'UTF-8')."%'";
      $sqlpres .= " AND Upper(observacion) LIKE '%".mb_strtoupper($this->input->get('dprov_clien'), 'UTF-8')."%'";
    }

    $response = array();

    if ($this->input->get('fno_caja') == 'prest1') {
      $movimientos = $this->db->query(
        "SELECT * FROM (
          SELECT id_pago AS id, id_categoria, categoria, 'ingreso' AS tipo, otro, concepto AS observacion, monto, fecha, null AS folio2,
            1 AS ingreso, 1 AS no_caja
          FROM (
            SELECT cp.id_pago, cp.id_empleado, cp.id_empresa, cp.anio, cp.semana, cp.id_prestamo, cp.id_categoria, cp.concepto, cp.monto, cp.fecha,
              cp.id_nomenclatura, cc.nombre as categoria, cn.nombre AS otro, cn.nomenclatura
            FROM otros.cajaprestamo_pagos cp
              INNER JOIN cajachica_categorias cc ON cc.id_categoria = cp.id_categoria
              INNER JOIN cajachica_nomenclaturas cn ON cn.id = cp.id_nomenclatura
            WHERE cp.fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}' AND cp.no_caja = 1
            UNION
            SELECT cp.id_pago, np.id_empleado, np.id_empresa, np.anio, np.semana, np.id_prestamo, cp.id_categoria,
              (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno || '; Sem ' || np.semana) AS concepto,
              np.monto, np.fecha, cp.id_nomenclatura, null AS categoria, null AS otro, null AS nomenclatura
            FROM nomina_fiscal_prestamos np
              INNER JOIN nomina_prestamos npp ON npp.id_prestamo = np.id_prestamo
              INNER JOIN usuarios u ON u.id = np.id_empleado
              LEFT JOIN otros.cajaprestamo_pagos cp ON (cp.id_empleado = cp.id_empleado AND np.id_empresa = cp.id_empresa AND np.anio = cp.anio AND np.semana = cp.semana AND np.id_prestamo = cp.id_prestamo)
            WHERE np.saldado = 'f' AND (npp.tipo = 'ef') AND np.fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}' AND cp.id_pago IS NULL
          ) AS t
          WHERE 1 = 1 {$sqlpres}
          UNION
          SELECT id_prestamo AS id, id_categoria, categoria, 'gasto' AS tipo, otro, concepto AS observacion, monto, fecha, null AS folio2,
            1 AS ingreso, 1 AS no_caja
          FROM (
            SELECT cp.id_prestamo, cp.id_prestamo_nom, cp.id_empleado, cp.id_categoria, cp.id_nomenclatura, cp.concepto, cp.fecha, cp.monto,
              cc.nombre as categoria, cn.nombre AS otro, cn.nomenclatura
            FROM otros.cajaprestamo_prestamos cp
            INNER JOIN cajachica_categorias cc ON cc.id_categoria = cp.id_categoria
            INNER JOIN cajachica_nomenclaturas cn ON cn.id = cp.id_nomenclatura
            WHERE cp.fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}' AND cp.no_caja = 1 {$sqlprs1}
            UNION
            SELECT cp.id_prestamo AS id_prestamo, np.id_prestamo AS id_prestamo_nom, np.id_usuario AS id_empleado, null AS id_categoria,
              null AS id_nomenclatura, ('PTMO NOM ' || u.nombre || ' ' || u.apellido_paterno) AS concepto, Date(np.fecha) AS fecha,
              np.prestado AS monto, null AS categoria, null AS otro, null AS nomenclatura
            FROM nomina_prestamos np
            INNER JOIN usuarios u ON u.id = np.id_usuario
            LEFT JOIN otros.cajaprestamo_prestamos cp ON np.id_prestamo = cp.id_prestamo_nom
            WHERE (np.tipo = 'ef') AND np.fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}' AND cp.id_prestamo IS NULL
          ) AS t
          WHERE 1 = 1 {$sqlprs}
        ) AS t"
      );
    } else {
      $movimientos = $this->db->query("SELECT * FROM (
          SELECT ci.id_ingresos AS id, cc.id_categoria, cc.nombre AS categoria, 'ingreso' AS tipo,
            cn.nombre AS otro, ci.concepto AS observacion, ci.monto, ci.fecha, ci.poliza AS folio2,
            1 AS ingreso, ci.no_caja
          FROM cajachica_ingresos ci
            INNER JOIN cajachica_categorias cc ON cc.id_categoria = ci.id_categoria
            INNER JOIN cajachica_nomenclaturas cn ON cn.id = ci.id_nomenclatura
          WHERE ci.fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}' {$sql} {$sql1}
          UNION
          SELECT cr.id_remision AS id, cc.id_categoria, cc.nombre AS categoria, 'remision' AS tipo,
            (f.folio || f.serie) AS otro, cr.observacion, cr.monto, cr.fecha, cr.folio_factura AS folio2,
            1 AS ingreso, cr.no_caja
          FROM cajachica_remisiones cr
            INNER JOIN cajachica_categorias cc ON cc.id_categoria = cr.id_categoria
            INNER JOIN facturacion f ON f.id_factura = cr.id_remision
          WHERE cr.fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}' {$sql} {$sql2}
          UNION
          SELECT cg.id_gasto AS id, cc.id_categoria, cc.nombre AS categoria, 'gasto' AS tipo,
            cn.nombre AS otro, cg.concepto AS observacion, cg.monto, cg.fecha, cg.folio AS folio2,
            0 AS ingreso, cg.no_caja
          FROM cajachica_gastos cg
            INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
            INNER JOIN cajachica_nomenclaturas cn ON cn.id = cg.id_nomenclatura
            LEFT JOIN compras_areas ca ON ca.id_area = cg.id_area
            LEFT JOIN otros.cat_codigos AS cca ON cca.id_cat_codigos = cg.id_cat_codigos
          WHERE fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}' {$sql} {$sql3}
        ) AS t
        ORDER BY fecha ASC, ingreso DESC");
    }
    $response = $movimientos->result();

    return $response;
  }

  public function getRptIngresosGastosPdf(){
    $res = $this->getRptIngresosGastosData();

    $this->load->model('empresas_model');
    $id_empresa = $this->input->get('did_empresa');
    $empresa = $this->empresas_model->getInfoEmpresa(($id_empresa>0? $id_empresa: 2));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    $pdf->titulo2 = 'Reporte de Ingresos / Gastos';
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    if ($this->input->get('fno_caja') != '')
      $pdf->titulo3 .= 'Caja #'.$this->input->get('fno_caja')."\n";
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'L', 'L', 'L', 'R', 'R');
    $widths = array(18, 50, 15, 66, 8, 24, 24);
    $header = array('Fecha', 'Empresa', 'Tipo', 'Observacion', 'Caja', 'Ingreso', 'Gasto');

    $total_ingresos = $total_gastos = 0;
    foreach($res as $key => $item){
      if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        // if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
        $pdf->SetY($pdf->GetY()+2);
      }

      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','',8);
      $datos = array($item->fecha,
        $item->categoria,
        ucfirst($item->tipo),
        $item->observacion,
        $item->no_caja,
        '', '',
        );
      if ($item->ingreso == 1) {
        $datos[5] = String::formatoNumero($item->monto, 2, '', false);
        $total_ingresos += $item->monto;
      } else {
        $datos[6] = String::formatoNumero($item->monto, 2, '', false);
        $total_gastos += $item->monto;
      }
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false, false);
    }

    $pdf->SetFont('Arial','B',8);
    $pdf->SetXY(6, $pdf->GetY()-2);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->Row(array('', '', '', '', '',
      String::formatoNumero($total_ingresos, 2, '', false),
      String::formatoNumero($total_gastos, 2, '', false)
    ), false, false);

    $pdf->Output('ingresos_gastos_caja.pdf', 'I');
  }

  public function getRptIngresosGastosXls(){
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=ingresos_caja.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getRptIngresosData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte de Ingresos';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";

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
    $aux_categoria = '';
    $total_nomenclatura = array();
    $aux_proveedor_total = $proveedor_total = 0;
    foreach($res['movimientos'] as $key => $producto){

      if($key==0 || $aux_categoria != $producto->id_categoria) {
        if($aux_categoria != $producto->id_categoria && $key > 0)
        {
          $aux_proveedor_total = $proveedor_total;
          $html .= $this->getRptMovimientosTotalesXls($proveedor_total, $total_nomenclatura, $aux_categoria, $producto);

          $html .= $this->getRptRemisionesTotalesXls($res['remisiones'], $aux_proveedor_total, $producto->id_categoria);
        }elseif($key == 0)
          $aux_categoria = $producto->id_categoria;

        $html .= '<tr style="font-weight:bold">
          <td colspan="6"></td>
        </tr>
        <tr style="font-weight:bold">
          <td colspan="6" style="background-color: #cccccc;">'.$producto->categoria.'</td>
        </tr>
        <tr style="font-weight:bold">
          <td style="border:1px solid #000;background-color: #cccccc;">Fecha</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Nomenclatura</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Poliza</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Concepto</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Importe</td>
        </tr>';
      }

      $html .= '<tr style="">
          <td style="border:1px solid #000;">'.$producto->fecha.'</td>
          <td style="border:1px solid #000;">'.$producto->nomenclatura.'</td>
          <td style="border:1px solid #000;">'.$producto->poliza.'</td>
          <td style="border:1px solid #000;">'.$producto->concepto.'</td>
          <td style="border:1px solid #000;">'.$producto->monto.'</td>
        </tr>';

      if(array_key_exists($producto->id_nomenclatura, $total_nomenclatura))
        $total_nomenclatura[$producto->id_nomenclatura][0] += $producto->monto;
      else
        $total_nomenclatura[$producto->id_nomenclatura] = array($producto->monto, $producto->nombre_nomen, $producto->nomenclatura);

      $proveedor_total += $producto->monto;
    }

    if(isset($producto))
    {
      $aux_proveedor_total = $proveedor_total;
      $html .= $this->getRptMovimientosTotalesXls($proveedor_total, $total_nomenclatura, $aux_categoria, $producto);

      $html .= $this->getRptRemisionesTotalesXls($res['remisiones'], $aux_proveedor_total, $producto->id_categoria);
    }

    $html .= '
      </tbody>
    </table>';

    echo $html;
  }

  public function getRptMovimientosTotales(&$pdf, &$proveedor_total, &$total_nomenclatura, &$aux_categoria, &$producto)
  {
    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B',8);
    $datos = array('Total Reposicion',
      String::formatoNumero(($proveedor_total), 2, '', false),
    );
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(167, 35));
    $pdf->Row($datos, false);

    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(150));
    $pdf->Row(array('DESGLOSE DE INGRESOS'), false, false);

    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('C', 'L', 'R'));
    $pdf->SetWidths(array(25, 50, 50));
    $pdf->Row(array('Nomenclatura', 'Concepto', 'Total por concepto'), false, false);
    foreach ($total_nomenclatura as $keyn => $nomen)
    {
      if($pdf->GetY()+6 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->Row(array($nomen[2], $nomen[1], String::formatoNumero($nomen[0], 2, '', false) ), false, false);
    }
    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(75, 50));
    $pdf->Row(array('', String::formatoNumero(($proveedor_total), 2, '', false)), false);

    $aux_categoria      = $producto->id_categoria;
    $proveedor_total    = 0;
    $total_nomenclatura = array();

    $pdf->SetXY(6, $pdf->GetY()+8);
  }

  public function getRptRemisionesTotales(&$pdf, &$remisiones, $proveedor_total, $id_categoria)
  {
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'L', 'L', 'R');
    $widths = array(20, 25, 96, 25, 35);
    $header = array('Fecha', 'Remision', 'Nombre', 'Folio', 'Importe');

    $entro = false;
    $total_nomenclatura = array();
    $remisiones_total = 0;
    foreach($remisiones as $key => $producto){
      if($producto->id_categoria == $id_categoria)
      {
        if($pdf->GetY() >= $pdf->limiteY || !$entro){ //salta de pagina si exede el max
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetFont('Arial','B',8);
          $pdf->SetTextColor(255,255,255);
          $pdf->SetFillColor(160,160,160);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
          $pdf->SetY($pdf->GetY()+2);
          $entro = true;
        }

        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','',8);
        $datos = array($producto->fecha,
          $producto->serie.$producto->folio,
          $producto->observacion,
          $producto->folio_factura,
          String::formatoNumero($producto->monto, 2, '', false),
          );
        $pdf->SetXY(6, $pdf->GetY()-2);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false, false);

        $remisiones_total += $producto->monto;

        unset($remisiones[$key]);
      }
    }

    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B',8);
    $datos = array('Total Remisiones',
      String::formatoNumero(($remisiones_total), 2, '', false),
    );
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(167, 35));
    $pdf->Row($datos, false);

    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B',8);
    $datos = array('Total General',
      String::formatoNumero(($remisiones_total+$proveedor_total), 2, '', false),
    );
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(167, 35));
    $pdf->Row($datos, false);

    $pdf->SetXY(6, $pdf->GetY()+8);
  }

  public function getRptMovimientosTotalesXls(&$proveedor_total, &$total_nomenclatura, &$aux_categoria, &$producto)
  {
    $html = '
    <tr style="font-weight:bold">
      <td colspan="4">Total Reposicion</td>
      <td style="border:1px solid #000;">'.($proveedor_total).'</td>
    </tr>
    <tr style="font-weight:bold">
      <td colspan="5">DESGLOSE DE INGRESOS</td>
    </tr>
    <tr style="font-weight:bold">
      <td style="border:1px solid #000;background-color: #cccccc;">Nomenclatura</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Concepto</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Total por concepto</td>
    </tr>
    ';

    foreach ($total_nomenclatura as $keyn => $nomen)
    {
      $html .= '<tr style="font-weight:bold">
        <td style="border:1px solid #000;">'.$nomen[2].'</td>
        <td style="border:1px solid #000;">'.$nomen[1].'</td>
        <td style="border:1px solid #000;">'.$nomen[0].'</td>
      </tr>';
    }

    $html .= '<tr style="font-weight:bold">
        <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">Total</td>
        <td style="border:1px solid #000;background-color: #cccccc;">'.$proveedor_total.'</td>
      </tr>';

    $aux_categoria      = $producto->id_categoria;
    $proveedor_total    = 0;
    $total_nomenclatura = array();
    return $html;
  }

  public function getRptRemisionesTotalesXls(&$remisiones, $proveedor_total, $id_categoria)
  {
    $html = '
    <tr style="font-weight:bold">
      <td style="border:1px solid #000;background-color: #cccccc;">Fecha</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Remision</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Nombre</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Folio</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Importe</td>
    </tr>
    ';

    $entro = false;
    $total_nomenclatura = array();
    $remisiones_total = 0;
    foreach($remisiones as $key => $producto)
    {
      $html .= '<tr style="font-weight:bold">
        <td style="border:1px solid #000;">'.$producto->fecha.'</td>
        <td style="border:1px solid #000;">'.$producto->serie.$producto->folio.'</td>
        <td style="border:1px solid #000;">'.$producto->observacion.'</td>
        <td style="border:1px solid #000;">'.$producto->folio_factura.'</td>
        <td style="border:1px solid #000;">'.$producto->monto.'</td>
      </tr>';

      $remisiones_total += $producto->monto;
      unset($remisiones[$key]);
    }

    $html .= '<tr style="font-weight:bold">
        <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">Total Remisiones</td>
        <td style="border:1px solid #000;background-color: #cccccc;">'.$remisiones_total.'</td>
      </tr>
      <tr style="font-weight:bold">
        <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">Total General</td>
        <td style="border:1px solid #000;background-color: #cccccc;">'.($remisiones_total+$proveedor_total).'</td>
      </tr>';

    // $aux_categoria      = $producto->id_categoria;
    // $proveedor_total    = 0;
    // $total_nomenclatura = array();
    return $html;
  }


}

/* End of file caja_chica_model.php */
/* Location: ./application/models/caja_chica_model.php */