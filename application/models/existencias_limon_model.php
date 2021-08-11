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
    $fechaa_inicioo = '2020-02-22';

    $info = array(
      'saldo_inicial'        => 0,
      'ventas'               => array(),
      'ventas_industrial'    => array(),
      'compra_fruta'         => array(),
      'produccion'           => array(),
      'existencia_anterior'  => [],
      'existencia'           => [],
      'existencia_piso'      => [],
      'existencia_reproceso' => [],
      'costo_ventas'         => [],
      'costo_ventas_fletes'  => [],
      'comision_terceros'    => [],
      'industrial'           => [],
    );

    $ventas = $this->db->query(
      "SELECT f.id_factura, f.serie, f.folio, cl.nombre_fiscal, STRING_AGG(Distinct c.nombre, ', ') AS clasificacion, Sum(fp.cantidad) AS cantidad,
        (Sum(fp.importe) / Sum(fp.cantidad)) AS precio, Sum(fp.importe) AS importe, u.id_unidad,
        Coalesce(u.codigo, u.nombre) AS unidad, u.cantidad AS unidad_cantidad, (Sum(fp.cantidad) * u.cantidad) AS kilos,
        fo.no_salida_fruta, ca.id_calibre, ca.nombre AS calibre, string_agg(fh.id_factura::text, ',') AS facturas
      FROM facturacion f
        INNER JOIN clientes cl ON cl.id_cliente = f.id_cliente
        INNER JOIN facturacion_productos fp ON f.id_factura = fp.id_factura
        INNER JOIN calibres ca ON ca.id_calibre = fp.id_calibres
        INNER JOIN clasificaciones c ON c.id_clasificacion = fp.id_clasificacion
        INNER JOIN unidades u ON u.id_unidad = fp.id_unidad
        LEFT JOIN facturacion_otrosdatos fo ON f.id_factura = fo.id_factura
        LEFT JOIN (SELECT id_remision, id_factura, status
          FROM remisiones_historial WHERE status <> 'ca' AND status <> 'b'
        ) fh ON f.id_factura = fh.id_remision
      WHERE f.id_empresa = {$id_empresa} AND f.status <> 'ca' AND f.status <> 'b' AND f.is_factura = 'f'
        AND c.id_area = {$id_area} AND Date(f.fecha) = '{$fecha}'
        AND Date(f.fecha) >= '{$fechaa_inicioo}'
      GROUP BY ca.id_calibre, cl.id_cliente, f.id_factura, u.id_unidad, fo.id_factura
      ORDER BY folio ASC"
    );

    if ($ventas->num_rows() > 0)
    {
      $info['ventas'] = $ventas->result();

      if ($id_area == 2) { // limon
        foreach ($info['ventas'] as $key => $value) {
          if ($value->id_calibre == 133) { // INDUSTRIAL
            $info['ventas_industrial'][] = $value;
            unset($info['ventas'][$key]);
          }
        }
      }
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
      WHERE b.id_empresa = {$id_empresa} AND b.status = 't' AND b.intangible = 'f'
        AND b.id_area = {$id_area} AND Date(b.fecha_bruto) = '{$fecha}'
        AND b.id_bonificacion IS NULL
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
      "SELECT STRING_AGG(Distinct c.nombre, ', ') AS clasificacion, Sum(rrc.rendimiento) AS cantidad,
        Coalesce(elp.costo, 0) AS costo, (Coalesce(elp.costo, 0)*Sum(rrc.rendimiento)) AS importe, u.id_unidad,
        Coalesce(u.codigo, u.nombre) AS unidad, u.cantidad AS unidad_cantidad, (Sum(rrc.rendimiento) * u.cantidad) AS kilos,
        elp.id AS id_produccion, ca.id_calibre, ca.nombre AS calibre
      FROM rastria_rendimiento rr
        INNER JOIN rastria_rendimiento_clasif rrc ON rr.id_rendimiento = rrc.id_rendimiento
        INNER JOIN calibres ca ON ca.id_calibre = rrc.id_size
        INNER JOIN clasificaciones c ON c.id_clasificacion = rrc.id_clasificacion
        INNER JOIN unidades u ON u.id_unidad = rrc.id_unidad
        LEFT JOIN otros.existencias_limon_produccion elp ON (elp.id_calibre = ca.id_calibre
          AND elp.id_unidad = u.id_unidad AND Date(elp.fecha) = '{$fecha}')
      WHERE rr.status = 't' AND c.id_area = {$id_area}
        AND Date(rr.fecha) = '{$fecha}' AND (elp.no_caja = {$noCaja} OR elp.no_caja IS NULL)
        AND Date(rr.fecha) >= '{$fechaa_inicioo}'
      GROUP BY ca.id_calibre, u.id_unidad, elp.id
      ORDER BY id_calibre ASC, id_unidad ASC"
    );

    if ($produccion->num_rows() > 0)
    {
      $info['produccion'] = $produccion->result();
    }

    // Existencia piso
    $id_calibree = ($id_area == 2? 135: 135); // A granel
    $existencia_piso = $this->db->query(
      "SELECT ele.id_calibre, ele.id_unidad, ele.fecha, ele.no_caja, ele.costo, ele.kilos,
        ele.cantidad, ele.importe, c.nombre AS calibre, Coalesce(u.codigo, u.nombre) AS unidad,
        u.cantidad AS unidad_cantidad, '' AS clasificacion
      FROM otros.existencias_limon_existencia_piso ele
        INNER JOIN calibres c ON c.id_calibre = ele.id_calibre
        INNER JOIN unidades u ON u.id_unidad = ele.id_unidad
      WHERE Date(ele.fecha) = '{$fecha}' AND ele.no_caja = {$noCaja}
        AND ele.id_area = {$id_area}"
    );

    if ($existencia_piso->num_rows() > 0)
    {
      $info['existencia_piso'] = $existencia_piso->result();
    }

    // Existencia reproceso
    $existencia_reproceso = $this->db->query(
      "SELECT ele.id_calibre, ele.id_unidad, ele.fecha, ele.no_caja, ele.costo, ele.kilos,
        ele.cantidad, ele.importe, c.nombre AS calibre, Coalesce(u.codigo, u.nombre) AS unidad,
        u.cantidad AS unidad_cantidad, '' AS clasificacion
      FROM otros.existencias_limon_existencia_reproceso ele
        INNER JOIN calibres c ON c.id_calibre = ele.id_calibre
        INNER JOIN unidades u ON u.id_unidad = ele.id_unidad
      WHERE Date(ele.fecha) = '{$fecha}' AND ele.no_caja = {$noCaja}
        AND ele.id_area = {$id_area}"
    );

    if ($existencia_reproceso->num_rows() > 0)
    {
      $info['existencia_reproceso'] = $existencia_reproceso->result();
    }

    // Costo de ventas
    $costo_ventas = $this->db->query(
      "SELECT ele.id, ele.fecha, ele.no_caja, ele.nombre, ele.descripcion, ele.costo,
        ele.cantidad, ele.importe
      FROM otros.existencias_limon_descuentos_ventas ele
      WHERE ele.tipo = 'cv' AND Date(ele.fecha) = '{$fecha}' AND ele.no_caja = {$noCaja}
        AND ele.id_area = {$id_area}"
    );

    if ($costo_ventas->num_rows() > 0)
    {
      $info['costo_ventas'] = $costo_ventas->result();
    }

    // costo ventas fletes
    if (count($info['ventas']) > 0) {
      $raidss = $faidss = [];
      foreach ($info['ventas'] as $keyv => $venta) {
        $raidss[$venta->id_factura] = $venta->id_factura;

        $ffacturas = explode(',', $venta->facturas);
        if (isset($ffacturas) && count($ffacturas) > 0) {
          foreach ($ffacturas as $keyf => $fac) {
            if ($fac > 0) {
              $faidss[$fac] = $fac;
            }
          }
        }
      }
      $ridss = 'f:'.implode('\||f:', $raidss).'\|';
      $fidss = 't:'.implode('\||t:', $faidss).'\|';
      $costo_ventas_fletes = $this->db->query(
        "SELECT co.id_orden, p.nombre_fiscal AS proveedor, co.folio, string_agg(distinct(cp.descripcion), ', ') AS descripcion,
          Sum(cp.cantidad) AS cantidad, Sum(cp.importe) AS importe, Sum(cp.total) AS total
        FROM compras_ordenes co
          INNER JOIN compras_productos cp ON co.id_orden = cp.id_orden
          INNER JOIN proveedores p ON p.id_proveedor = co.id_proveedor
        WHERE co.status in('a', 'f') AND co.tipo_orden = 'f' and co.flete_de = 'v'
          AND (co.ids_facrem SIMILAR TO '%({$ridss})%' OR co.ids_facrem SIMILAR TO '%({$fidss})%')
        GROUP BY co.id_orden, p.nombre_fiscal"
      );

      if ($costo_ventas_fletes->num_rows() > 0)
      {
        $info['costo_ventas_fletes'] = $costo_ventas_fletes->result();
      }
    }

    // Comisiones terceros
    $comision_terceros = $this->db->query(
      "SELECT ele.id, ele.fecha, ele.no_caja, ele.nombre, ele.descripcion, ele.costo,
        ele.cantidad, ele.importe
      FROM otros.existencias_limon_descuentos_ventas ele
      WHERE ele.tipo = 'ct' AND Date(ele.fecha) = '{$fecha}' AND ele.no_caja = {$noCaja}
        AND ele.id_area = {$id_area}"
    );

    if ($comision_terceros->num_rows() > 0)
    {
      $info['comision_terceros'] = $comision_terceros->result();
    }


    // industrial
    $industrial = $this->db->query(
      "SELECT eli.id_calibre, eli.id_unidad, eli.fecha, eli.no_caja, eli.costo, eli.kilos,
        eli.importe, c.nombre AS calibre, Coalesce(u.codigo, u.nombre) AS unidad,
        u.cantidad AS unidad_cantidad, '' AS clasificacion
      FROM otros.existencias_limon_industrial eli
        INNER JOIN calibres c ON c.id_calibre = eli.id_calibre
        INNER JOIN unidades u ON u.id_unidad = eli.id_unidad
      WHERE Date(eli.fecha) = '{$fecha}' AND eli.no_caja = {$noCaja}
        AND eli.id_area = {$id_area}"
    );

    if ($industrial->num_rows() > 0)
    {
      $info['industrial'] = $industrial->row();
    }


    $fecha_anterior = $this->db->query(
      "SELECT Date(fecha) AS fecha
      FROM otros.existencias_limon
      WHERE Date(fecha) < '{$fecha}' AND no_caja = {$noCaja} AND id_area = {$id_area}
      ORDER BY fecha DESC
      LIMIT 1"
    )->row();
    $fecha_anterior = isset($fecha_anterior->fecha)? $fecha_anterior->fecha: MyString::suma_fechas($fecha, -1);

    $existencia_anterior = $this->db->query(
      "SELECT ele.id_calibre, ele.id_unidad, ele.fecha, ele.no_caja, ele.costo, ele.kilos,
        ele.cantidad, ele.importe, c.nombre AS calibre, Coalesce(u.codigo, u.nombre) AS unidad,
        u.cantidad AS unidad_cantidad, '' AS clasificacion
      FROM otros.existencias_limon_existencia ele
        INNER JOIN calibres c ON c.id_calibre = ele.id_calibre
        INNER JOIN unidades u ON u.id_unidad = ele.id_unidad
      WHERE Date(ele.fecha) = '{$fecha_anterior}' AND ele.no_caja = {$noCaja}
        AND ele.id_area = {$id_area}"
    );

    if ($existencia_anterior->num_rows() > 0)
    {
      $info['existencia_anterior'] = $existencia_anterior->result();
    }


    $existencia = [];
    foreach ($info['existencia_anterior'] as $key => $item) {
      if (isset($existencia[$item->id_calibre.$item->id_unidad])) {
        $existencia[$item->id_calibre.$item->id_unidad]->cantidad += round($item->cantidad, 2);
        $existencia[$item->id_calibre.$item->id_unidad]->kilos    += round($item->kilos, 2);
        $existencia[$item->id_calibre.$item->id_unidad]->importe  += round($item->importe, 2);
      } else {
        $existencia[$item->id_calibre.$item->id_unidad]                  = new stdClass;
        $existencia[$item->id_calibre.$item->id_unidad]->id_calibre      = $item->id_calibre;
        $existencia[$item->id_calibre.$item->id_unidad]->id_unidad       = $item->id_unidad;
        $existencia[$item->id_calibre.$item->id_unidad]->calibre         = $item->calibre;
        $existencia[$item->id_calibre.$item->id_unidad]->clasificacion   = $item->clasificacion;
        $existencia[$item->id_calibre.$item->id_unidad]->unidad          = $item->unidad;
        $existencia[$item->id_calibre.$item->id_unidad]->unidad_cantidad = $item->unidad_cantidad;
        $existencia[$item->id_calibre.$item->id_unidad]->cantidad        = $item->cantidad;
        $existencia[$item->id_calibre.$item->id_unidad]->kilos           = $item->kilos;
        $existencia[$item->id_calibre.$item->id_unidad]->costo           = $item->costo;
        $existencia[$item->id_calibre.$item->id_unidad]->importe         = $item->importe;
        $existencia[$item->id_calibre.$item->id_unidad]->no_caja         = $noCaja;
        $existencia[$item->id_calibre.$item->id_unidad]->fecha           = $fecha;
      }
    }
    foreach ($info['produccion'] as $key => $item) {
      if (isset($existencia[$item->id_calibre.$item->id_unidad])) {
        if ($existencia[$item->id_calibre.$item->id_unidad]->costo == 0) {
          $existencia[$item->id_calibre.$item->id_unidad]->costo         = $item->costo;
        }

        $existencia[$item->id_calibre.$item->id_unidad]->cantidad += round($item->cantidad, 2);
        $existencia[$item->id_calibre.$item->id_unidad]->kilos    += round($item->kilos, 2);
        $existencia[$item->id_calibre.$item->id_unidad]->importe  += round($item->importe, 2);
      } else {
        $existencia[$item->id_calibre.$item->id_unidad]                  = new stdClass;
        $existencia[$item->id_calibre.$item->id_unidad]->id_calibre      = $item->id_calibre;
        $existencia[$item->id_calibre.$item->id_unidad]->id_unidad       = $item->id_unidad;
        $existencia[$item->id_calibre.$item->id_unidad]->calibre         = $item->calibre;
        $existencia[$item->id_calibre.$item->id_unidad]->clasificacion   = $item->clasificacion;
        $existencia[$item->id_calibre.$item->id_unidad]->unidad          = $item->unidad;
        $existencia[$item->id_calibre.$item->id_unidad]->unidad_cantidad = $item->unidad_cantidad;
        $existencia[$item->id_calibre.$item->id_unidad]->cantidad        = $item->cantidad;
        $existencia[$item->id_calibre.$item->id_unidad]->kilos           = $item->kilos;
        $existencia[$item->id_calibre.$item->id_unidad]->costo           = $item->costo;
        $existencia[$item->id_calibre.$item->id_unidad]->importe         = $item->importe;
        $existencia[$item->id_calibre.$item->id_unidad]->no_caja         = $noCaja;
        $existencia[$item->id_calibre.$item->id_unidad]->fecha           = $fecha;
      }
    }
    foreach ($info['ventas'] as $key => $item) {
      if (isset($existencia[$item->id_calibre.$item->id_unidad])) {
        $existencia[$item->id_calibre.$item->id_unidad]->cantidad -= round($item->cantidad, 2);
        $existencia[$item->id_calibre.$item->id_unidad]->kilos    -= round($item->kilos, 2);
        $existencia[$item->id_calibre.$item->id_unidad]->importe  -= round($item->importe, 2);
      } else {
        $existencia[$item->id_calibre.$item->id_unidad]                  = new stdClass;
        $existencia[$item->id_calibre.$item->id_unidad]->id_calibre      = $item->id_calibre;
        $existencia[$item->id_calibre.$item->id_unidad]->id_unidad       = $item->id_unidad;
        $existencia[$item->id_calibre.$item->id_unidad]->calibre         = $item->calibre;
        $existencia[$item->id_calibre.$item->id_unidad]->clasificacion   = $item->clasificacion;
        $existencia[$item->id_calibre.$item->id_unidad]->unidad          = $item->unidad;
        $existencia[$item->id_calibre.$item->id_unidad]->unidad_cantidad = $item->unidad_cantidad;
        $existencia[$item->id_calibre.$item->id_unidad]->cantidad        = $item->cantidad*-1;
        $existencia[$item->id_calibre.$item->id_unidad]->kilos           = $item->kilos*-1;
        $existencia[$item->id_calibre.$item->id_unidad]->costo           = 0;
        $existencia[$item->id_calibre.$item->id_unidad]->importe         = $item->importe*-1;
        $existencia[$item->id_calibre.$item->id_unidad]->no_caja         = $noCaja;
        $existencia[$item->id_calibre.$item->id_unidad]->fecha           = $fecha;
      }
    }
    foreach ($info['existencia_reproceso'] as $key => $item) {
      if (isset($existencia[$item->id_calibre.$item->id_unidad])) {
        $existencia[$item->id_calibre.$item->id_unidad]->cantidad -= round($item->cantidad);
        $existencia[$item->id_calibre.$item->id_unidad]->kilos    -= round($item->kilos);
        $existencia[$item->id_calibre.$item->id_unidad]->importe  -= round($item->importe);
      } else {
        $existencia[$item->id_calibre.$item->id_unidad]                  = new stdClass;
        $existencia[$item->id_calibre.$item->id_unidad]->id_calibre      = $item->id_calibre;
        $existencia[$item->id_calibre.$item->id_unidad]->id_unidad       = $item->id_unidad;
        $existencia[$item->id_calibre.$item->id_unidad]->calibre         = $item->calibre;
        $existencia[$item->id_calibre.$item->id_unidad]->clasificacion   = $item->clasificacion;
        $existencia[$item->id_calibre.$item->id_unidad]->unidad          = $item->unidad;
        $existencia[$item->id_calibre.$item->id_unidad]->unidad_cantidad = $item->unidad_cantidad;
        $existencia[$item->id_calibre.$item->id_unidad]->cantidad        = $item->cantidad*-1;
        $existencia[$item->id_calibre.$item->id_unidad]->kilos           = $item->kilos*-1;
        $existencia[$item->id_calibre.$item->id_unidad]->costo           = $item->costo;
        $existencia[$item->id_calibre.$item->id_unidad]->importe         = $item->importe*-1;
        $existencia[$item->id_calibre.$item->id_unidad]->no_caja         = $noCaja;
        $existencia[$item->id_calibre.$item->id_unidad]->fecha           = $fecha;
      }
    }
    foreach ($info['existencia_piso'] as $key => $item) {
      if (isset($existencia[$item->id_calibre.$item->id_unidad])) {
        $existencia[$item->id_calibre.$item->id_unidad]->cantidad += round($item->cantidad);
        $existencia[$item->id_calibre.$item->id_unidad]->kilos    += round($item->kilos);
        $existencia[$item->id_calibre.$item->id_unidad]->importe  += round($item->importe);
      } else {
        $existencia[$item->id_calibre.$item->id_unidad]                  = new stdClass;
        $existencia[$item->id_calibre.$item->id_unidad]->id_calibre      = $item->id_calibre;
        $existencia[$item->id_calibre.$item->id_unidad]->id_unidad       = $item->id_unidad;
        $existencia[$item->id_calibre.$item->id_unidad]->calibre         = $item->calibre;
        $existencia[$item->id_calibre.$item->id_unidad]->clasificacion   = $item->clasificacion;
        $existencia[$item->id_calibre.$item->id_unidad]->unidad          = $item->unidad;
        $existencia[$item->id_calibre.$item->id_unidad]->unidad_cantidad = $item->unidad_cantidad;
        $existencia[$item->id_calibre.$item->id_unidad]->cantidad        = $item->cantidad;
        $existencia[$item->id_calibre.$item->id_unidad]->kilos           = $item->kilos;
        $existencia[$item->id_calibre.$item->id_unidad]->costo           = $item->costo;
        $existencia[$item->id_calibre.$item->id_unidad]->importe         = $item->importe;
        $existencia[$item->id_calibre.$item->id_unidad]->no_caja         = $noCaja;
        $existencia[$item->id_calibre.$item->id_unidad]->fecha           = $fecha;
      }
    }


    $existencia_data = $this->db->query(
      "SELECT id_calibre, id_unidad, costo
      FROM otros.existencias_limon_existencia
      WHERE Date(fecha) = '{$fecha}' AND no_caja = {$noCaja} AND id_area = {$id_area}")->result();
    $existencias_costos = [];
    foreach ($existencia_data as $key => $item) {
      $existencias_costos[$item->id_calibre.$item->id_unidad] = $item->costo;
    }
    foreach ($existencia as $key => $item) {
      $existencia[$key]->costo   = round((isset($existencias_costos[$item->id_calibre.$item->id_unidad])? $existencias_costos[$item->id_calibre.$item->id_unidad]: 0), 2);
      $existencia[$key]->importe = round($existencia[$key]->costo * $item->cantidad, 2);
      $existencia[$key]->kilos   = round($item->unidad_cantidad*$item->cantidad, 2);
    }
    $info['existencia'] = $existencia;


    $info['guardado'] = isset($existencia_data[0])? true: false;


    return $info;
  }

  public function guardar($data)
  {
    // Dia guardado
    $count_save = $this->db->query("SELECT Count(*) AS num
      FROM otros.existencias_limon
      WHERE fecha = '{$data['fecha_caja_chica']}' AND no_caja = {$data['fno_caja']} AND id_area = {$data['farea']}")->row();
    if ($count_save->num == 0) {
      $this->db->insert('otros.existencias_limon', ['fecha' => $data['fecha_caja_chica'], 'no_caja' => $data['fno_caja'], 'id_area' => $data['farea']]);
    }

    // Existencia de piso
    $this->db->delete('otros.existencias_limon_existencia_piso', "fecha = '{$data['fecha_caja_chica']}' AND no_caja = {$data['fno_caja']} AND id_area = {$data['farea']}");
    $existencias_piso = [];
    if (!empty($data['existenciaPiso_id_unidad'])) {
      foreach ($data['existenciaPiso_id_unidad'] as $key => $id_cat)
      {
        $existencias_piso[] = array(
          'id_area'    => $data['farea'],
          'id_calibre' => $data['existenciaPiso_id_calibre'][$key],
          'id_unidad'  => $data['existenciaPiso_id_unidad'][$key],
          'costo'      => $data['existenciaPiso_costo'][$key],
          'kilos'      => $data['existenciaPiso_kilos'][$key],
          'cantidad'   => $data['existenciaPiso_cantidad'][$key],
          'importe'    => $data['existenciaPiso_importe'][$key],
          'fecha'      => $data['fecha_caja_chica'],
          'no_caja'    => $data['fno_caja'],
        );
      }
      if (count($existencias_piso) > 0)
      {
        $this->db->insert_batch('otros.existencias_limon_existencia_piso', $existencias_piso);
      }
    }

    // Existencia de Reproceso
    $this->db->delete('otros.existencias_limon_existencia_reproceso', "fecha = '{$data['fecha_caja_chica']}' AND no_caja = {$data['fno_caja']} AND id_area = {$data['farea']}");
    $existencias_reproceso = [];
    if (!empty($data['existenciaRepro_id_calibre'])) {
      foreach ($data['existenciaRepro_id_calibre'] as $key => $id_cat)
      {
        $existencias_reproceso[] = array(
          'id_area'    => $data['farea'],
          'id_calibre' => $data['existenciaRepro_id_calibre'][$key],
          'id_unidad'  => $data['existenciaRepro_id_unidad'][$key],
          'costo'      => $data['existenciaRepro_costo'][$key],
          'kilos'      => $data['existenciaRepro_kilos'][$key],
          'cantidad'   => $data['existenciaRepro_cantidad'][$key],
          'importe'    => $data['existenciaRepro_importe'][$key],
          'fecha'      => $data['fecha_caja_chica'],
          'no_caja'    => $data['fno_caja'],
        );
      }
      if (count($existencias_reproceso) > 0)
      {
        $this->db->insert_batch('otros.existencias_limon_existencia_reproceso', $existencias_reproceso);
      }
    }

    // Costo de venta
    $descuentos_ventas = [];
    if (!empty($data['descuentoVentas_nombre'])) {
      foreach ($data['descuentoVentas_nombre'] as $key => $id_cat)
      {
        if (intval($data['descuentoVentas_id'][$key]) > 0 && $data['descuentoVentas_delete'][$key] == 'true') {
          $this->db->delete('otros.existencias_limon_descuentos_ventas', "id = {$data['descuentoVentas_id'][$key]}");
        } elseif (intval($data['descuentoVentas_id'][$key]) > 0) {
          $this->db->update('otros.existencias_limon_descuentos_ventas', [
            'id_area'     => $data['farea'],
            'nombre'      => $data['descuentoVentas_nombre'][$key],
            'descripcion' => $data['descuentoVentas_descripcion'][$key],
            'importe'     => $data['descuentoVentas_importe'][$key],
            'tipo'        => 'cv',
            'fecha'       => $data['fecha_caja_chica'],
            'no_caja'     => $data['fno_caja'],
          ], "id = {$data['descuentoVentas_id'][$key]}");
        } else {
          $descuentos_ventas[] = array(
            'id_area'     => $data['farea'],
            'nombre'      => $data['descuentoVentas_nombre'][$key],
            'descripcion' => $data['descuentoVentas_descripcion'][$key],
            'importe'     => $data['descuentoVentas_importe'][$key],
            'tipo'        => 'cv',
            'fecha'       => $data['fecha_caja_chica'],
            'no_caja'     => $data['fno_caja'],
          );
        }
      }
      if (count($descuentos_ventas) > 0)
      {
        $this->db->insert_batch('otros.existencias_limon_descuentos_ventas', $descuentos_ventas);
      }
    }

    // Comisiones terceros
    $comisiones_terceros = [];
    if (!empty($data['comisionTerceros_nombre'])) {
      foreach ($data['comisionTerceros_nombre'] as $key => $id_cat)
      {
        if (intval($data['comisionTerceros_id'][$key]) > 0 && $data['comisionTerceros_delete'][$key] == 'true') {
          $this->db->delete('otros.existencias_limon_descuentos_ventas', "id = {$data['comisionTerceros_id'][$key]}");
        } elseif (intval($data['comisionTerceros_id'][$key]) > 0) {
          $this->db->update('otros.existencias_limon_descuentos_ventas', [
            'id_area'     => $data['farea'],
            'nombre'      => $data['comisionTerceros_nombre'][$key],
            'descripcion' => $data['comisionTerceros_descripcion'][$key],
            'cantidad'    => $data['comisionTerceros_cantidad'][$key],
            'costo'       => $data['comisionTerceros_costo'][$key],
            'importe'     => $data['comisionTerceros_importe'][$key],
            'tipo'        => 'ct',
            'fecha'       => $data['fecha_caja_chica'],
            'no_caja'     => $data['fno_caja'],
          ], "id = {$data['comisionTerceros_id'][$key]}");
        } else {
          $comisiones_terceros[] = array(
            'id_area'     => $data['farea'],
            'nombre'      => $data['comisionTerceros_nombre'][$key],
            'descripcion' => $data['comisionTerceros_descripcion'][$key],
            'cantidad'    => $data['comisionTerceros_cantidad'][$key],
            'costo'       => $data['comisionTerceros_costo'][$key],
            'importe'     => $data['comisionTerceros_importe'][$key],
            'tipo'        => 'ct',
            'fecha'       => $data['fecha_caja_chica'],
            'no_caja'     => $data['fno_caja'],
          );
        }
      }
      if (count($comisiones_terceros) > 0)
      {
        $this->db->insert_batch('otros.existencias_limon_descuentos_ventas', $comisiones_terceros);
      }
    }

    // Produccion
    $produccion_inst = array();
    $produccion_updt = array();
    if (isset($data['produccion_costo'])) {
      foreach ($data['produccion_costo'] as $key => $id_cat)
      {
        if ($data['produccion_id_produccion'][$key] > 0) {
          $produccion_updt = array(
            'id_calibre' => $data['produccion_id_calibre'][$key],
            'id_unidad'  => $data['produccion_id_unidad'][$key],
            'costo'      => $data['produccion_costo'][$key],
            'importe'    => $data['produccion_importe'][$key],
            'fecha'      => $data['fecha_caja_chica'],
            'no_caja'    => $data['fno_caja'],
          );
          $this->db->update('otros.existencias_limon_produccion', $produccion_updt, "id = ".$data['produccion_id_produccion'][$key]);
        } else {
          $produccion_inst[] = array(
            'id_area'    => $data['farea'],
            'id_calibre' => $data['produccion_id_calibre'][$key],
            'id_unidad'  => $data['produccion_id_unidad'][$key],
            'costo'      => $data['produccion_costo'][$key],
            'importe'    => $data['produccion_importe'][$key],
            'fecha'      => $data['fecha_caja_chica'],
            'no_caja'    => $data['fno_caja'],
          );
        }
      }
    }

    if (count($produccion_inst) > 0)
    {
      $this->db->insert_batch('otros.existencias_limon_produccion', $produccion_inst);
    }


    // Existencia
    $existencia_inst = array();
    $this->db->delete('otros.existencias_limon_existencia', "fecha = '{$data['fecha_caja_chica']}' AND no_caja = {$data['fno_caja']} AND id_area = {$data['farea']}");
    if (isset($data['existencia_id_calibre'])) {
      foreach ($data['existencia_id_calibre'] as $key => $id_cat)
      {
        if ($data['existencia_cantidad'][$key] != 0) {
          $existencia_inst[] = array(
            'id_area'    => $data['farea'],
            'id_calibre' => $data['existencia_id_calibre'][$key],
            'id_unidad'  => $data['existencia_id_unidad'][$key],
            'fecha'      => $data['fecha_caja_chica'],
            'no_caja'    => $data['fno_caja'],
            'costo'      => $data['existencia_costo'][$key],
            'kilos'      => $data['existencia_kilos'][$key],
            'cantidad'   => $data['existencia_cantidad'][$key],
            'importe'    => $data['existencia_importe'][$key],
          );
        }
      }
    }
    if (count($existencia_inst) > 0)
    {
      $this->db->insert_batch('otros.existencias_limon_existencia', $existencia_inst);
    }

    // industrial
    $industrial_inst = array();
    $this->db->delete('otros.existencias_limon_industrial', "fecha = '{$data['fecha_caja_chica']}' AND no_caja = {$data['fno_caja']} AND id_area = {$data['farea']}");
    if (isset($data['industrial_kilos'])) {
      foreach ($data['industrial_kilos'] as $key => $id_cat)
      {
        if ($data['industrial_costo'][$key] > 0) {
          $industrial_inst[] = array(
            'id_area'    => $data['farea'],
            'id_calibre' => 133, // industrial
            'id_unidad'  => 49, // kilos a granel
            'fecha'      => $data['fecha_caja_chica'],
            'no_caja'    => $data['fno_caja'],
            'costo'      => $data['industrial_costo'][$key],
            'kilos'      => $data['industrial_kilos'][$key],
            'importe'    => $data['industrial_importe'][$key],
          );
        }
      }
    }
    if (count($industrial_inst) > 0)
    {
      $this->db->insert_batch('otros.existencias_limon_industrial', $industrial_inst);
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
    $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(18, 18, 35, 20, 27, 16, 18, 18, 12, 20));
    $pdf->Row(array('FOLIO', 'SF', 'CLIENTE', 'CALIBRE PROD.', 'CLASIF', 'UNIDAD', 'KILOS', 'CANTIDAD', 'PRECIO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'C', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(20, 18, 35, 20, 27, 16, 18, 18, 12, 20));

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
        $venta->no_salida_fruta,
        $venta->nombre_fiscal,
        $venta->calibre,
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
    $pdf->Row(array('EXISTENCIA EMPACADA'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(30, 65, 20, 20, 20, 20, 30));
    $pdf->Row(array('CALIBRE', 'CLASIF', 'UNIDAD', 'KILOS', 'CANTIDAD', 'COSTO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 65, 20, 20, 20, 20, 30));

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

      if ($existencia->cantidad != 0 ) {
        $pdf->SetX(6);
        $pdf->Row(array(
          $existencia->calibre,
          $existencia->clasificacion,
          $existencia->unidad,
          MyString::formatoNumero($existencia->kilos, 2, '', false),
          MyString::formatoNumero($existencia->cantidad, 2, '', false),
          MyString::formatoNumero($existencia->costo, 2, '', false),
          MyString::formatoNumero($existencia->importe, 2, '', false),
        ), false, 'B');
      }
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(6);
    $pdf->Row(array(
      '',
      '',
      '',
      MyString::formatoNumero($existencia_kilos, 2, '', false),
      MyString::formatoNumero($existencia_cantidad, 2, '', false),
      MyString::formatoNumero(($existencia_importe/($existencia_cantidad==0? 1: $existencia_cantidad)), 2, '', false),
      MyString::formatoNumero($existencia_importe, 2, '', false),
    ), false, 'B');

    // Existencia de piso
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('EXISTENCIA DE PISO'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(65, 35, 35, 35, 35));
    $pdf->Row(array('UNIDAD', 'CANTIDAD', 'KILOS', 'COSTO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(65, 35, 35, 35, 35));

    $existenciaPiso_kilos = $existenciaPiso_cantidad = $existenciaPiso_importe = 0;
    foreach ($caja['existencia_piso'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $existenciaPiso_kilos    += floatval($existencia->kilos);
      $existenciaPiso_cantidad += floatval($existencia->cantidad);
      $existenciaPiso_importe  += floatval($existencia->importe);

      $pdf->SetX(6);
      $pdf->Row(array(
        $existencia->unidad,
        MyString::formatoNumero($existencia->cantidad, 2, '', false),
        MyString::formatoNumero($existencia->kilos, 2, '', false),
        MyString::formatoNumero($existencia->costo, 2, '', false),
        MyString::formatoNumero($existencia->importe, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(6);
    $pdf->Row(array(
      '',
      MyString::formatoNumero($existenciaPiso_kilos, 2, '', false),
      MyString::formatoNumero($existenciaPiso_cantidad, 2, '', false),
      MyString::formatoNumero(($existenciaPiso_importe/($existenciaPiso_cantidad==0? 1: $existenciaPiso_cantidad)), 2, '', false),
      MyString::formatoNumero($existenciaPiso_importe, 2, '', false),
    ), false, 'B');

    // EXISTENCIA REPROCESO
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('EXISTENCIA REPROCESO'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(65, 30, 25, 25, 25, 35));
    $pdf->Row(array('CALIBRE', 'UNIDAD', 'CANTIDAD', 'KILOS', 'COSTO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(65, 30, 25, 25, 25, 35));

    $existenciaRePro_kilos = $existenciaRePro_cantidad = $existenciaRePro_importe = 0;
    foreach ($caja['existencia_reproceso'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $existenciaRePro_kilos    += floatval($existencia->kilos);
      $existenciaRePro_cantidad += floatval($existencia->cantidad);
      $existenciaRePro_importe  += floatval($existencia->importe);

      $pdf->SetX(6);
      $pdf->Row(array(
        $existencia->calibre,
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
      MyString::formatoNumero($existenciaRePro_kilos, 2, '', false),
      MyString::formatoNumero($existenciaRePro_cantidad, 2, '', false),
      MyString::formatoNumero(($existenciaRePro_importe/($existenciaRePro_cantidad==0? 1: $existenciaRePro_cantidad)), 2, '', false),
      MyString::formatoNumero($existenciaRePro_importe, 2, '', false),
    ), false, 'B');

    // COMPARA DE LIMON
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('COMPRA DE FRUTA'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'C', 'C', 'C'));
    $pdf->SetWidths(array(40, 70, 25, 25, 40));
    $pdf->Row(array('CALIBRE', 'CLASIF', 'KILOS', 'PRECIO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(40, 70, 25, 25, 40));

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
    $pdf->Row(array('COSTO DE PRODUCCIÓN'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array( 30, 65, 20, 20, 20, 20, 30));
    $pdf->Row(array('CALIBRE', 'CLASIF', 'UNIDAD', 'KILOS', 'CANTIDAD', 'COSTO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 65, 20, 20, 20, 20, 30));

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
        $produccion->calibre,
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
    $pdf->SetAligns(array('L', 'L', 'L', 'L', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(30, 65, 20, 20, 20, 20, 30));
    $pdf->Row(array('CALIBRE', 'CLASIF', 'UNIDAD', 'KILOS', 'CANTIDAD', 'COSTO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 65, 20, 20, 20, 20, 30));

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
        $existencia_ant->calibre,
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
      '',
      MyString::formatoNumero($existencia_ant_kilos, 2, '', false),
      MyString::formatoNumero($existencia_ant_cantidad, 2, '', false),
      MyString::formatoNumero(($existencia_ant_importe/($existencia_ant_cantidad==0? 1: $existencia_ant_cantidad)), 2, '', false),
      MyString::formatoNumero($existencia_ant_importe, 2, '', false),
    ), false, 'B');


    // COSTO DE VENTAS
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('COSTO DE VENTAS'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'C'));
    $pdf->SetWidths(array(75, 95, 35));
    $pdf->Row(array('NOMBRE', 'DESCRIPCION', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(75, 95, 35));

    $costoVentas_importe = 0;
    foreach ($caja['costo_ventas'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $costoVentas_importe += floatval($existencia->importe);

      $pdf->SetX(6);
      $pdf->Row(array(
        $existencia->nombre,
        $existencia->descripcion,
        MyString::formatoNumero($existencia->importe, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(6);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($costoVentas_importe, 2, '', false),
    ), false, 'B');

    // FLETES
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+1);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('FLETES'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'C', 'C'));
    $pdf->SetWidths(array(25, 100, 40, 40));
    $pdf->Row(array('FOLIO', 'PROVEEDOR', 'CANTIDAD', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R'));
    $pdf->SetWidths(array(25, 100, 40, 40));

    $descuentoVentasFletes_cantidad = $descuentoVentasFletes_importe = 0;
    foreach ($caja['costo_ventas_fletes'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY) {
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $descuentoVentasFletes_cantidad += floatval($existencia->cantidad);
      $descuentoVentasFletes_importe += floatval($existencia->importe);

      $pdf->SetX(6);
      $pdf->Row(array(
        $existencia->folio,
        $existencia->proveedor,
        MyString::formatoNumero($existencia->cantidad, 2, '', false),
        MyString::formatoNumero($existencia->importe, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(6);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($descuentoVentasFletes_cantidad, 2, '', false),
      MyString::formatoNumero($descuentoVentasFletes_importe, 2, '', false),
    ), false, 'B');


    // COMISIONES A TERCEROS
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('COMISIONES A TERCEROS'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'C', 'C', 'C'));
    $pdf->SetWidths(array(50, 65, 30, 30, 30));
    $pdf->Row(array('NOMBRE', 'DESCRIPCION', 'CANTIDAD', 'COSTO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(50, 65, 30, 30, 30));

    $comisionTerceros_cantidad = $comisionTerceros_importe = 0;
    foreach ($caja['comision_terceros'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $comisionTerceros_cantidad  += floatval($existencia->cantidad);
      $comisionTerceros_importe  += floatval($existencia->importe);

      $pdf->SetX(6);
      $pdf->Row(array(
        $existencia->nombre,
        $existencia->descripcion,
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
      MyString::formatoNumero($comisionTerceros_cantidad, 2, '', false),
      MyString::formatoNumero(($comisionTerceros_importe/($comisionTerceros_cantidad>0? $comisionTerceros_cantidad: 1)), 2, '', false),
      MyString::formatoNumero($comisionTerceros_importe, 2, '', false),
    ), false, 'B');


    // Ventas industrial
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->chkSaltaPag([6, $pdf->GetY()]);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('VENTAS INDUSTRIAL'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->chkSaltaPag([6, $pdf->GetY()]);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(18, 18, 35, 20, 27, 16, 18, 18, 12, 20));
    $pdf->Row(array('FOLIO', 'SF', 'CLIENTE', 'CALIBRE PROD.', 'CLASIF', 'UNIDAD', 'KILOS', 'CANTIDAD', 'PRECIO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'C', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(20, 18, 35, 20, 27, 16, 18, 18, 12, 20));

    $venta_importe_ind = $venta_kilos_ind = $venta_cantidad_ind = 0;
    foreach ($caja['ventas_industrial'] as $venta) {
      if($pdf->GetY()+10 >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $venta_importe_ind += floatval($venta->importe);
      $venta_kilos_ind += floatval($venta->kilos);
      $venta_cantidad_ind += floatval($venta->cantidad);

      $pdf->SetX(6);
      $pdf->Row(array(
        $venta->serie.$venta->folio,
        $venta->no_salida_fruta,
        $venta->nombre_fiscal,
        $venta->calibre,
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
      '',
      '',
      MyString::formatoNumero($venta_kilos_ind, 2, '', false),
      MyString::formatoNumero($venta_cantidad_ind, 2, '', false),
      MyString::formatoNumero(($venta_importe_ind/($venta_cantidad_ind==0? 1: $venta_cantidad_ind)), 2, '', false),
      MyString::formatoNumero($venta_importe_ind, 2, '', false),
    ), false, 'B');


    // industrial
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->chkSaltaPag([6, $pdf->GetY()]);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('INDUSTRIAL'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->chkSaltaPag([6, $pdf->GetY()]);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'C', 'C', 'C'));
    $pdf->SetWidths(array(56, 56, 30, 30, 30));
    $pdf->Row(array('CALIBRE', 'UNIDAD', 'KILOS', 'COSTO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(56, 56, 30, 30, 30));

    $industrial_importe = $industrial_kilos = 0;
    if (isset($caja['industrial']->costo)) {
      $industrial_importe = $caja['industrial']->importe;
      $industrial_kilos = $caja['industrial']->kilos;

      $pdf->SetFont('Arial','B', 7);
      $pdf->SetX(6);
      $pdf->Row(array(
        '', '',
        MyString::formatoNumero($caja['industrial']->kilos, 2, '', false),
        MyString::formatoNumero($caja['industrial']->costo, 2, '', false),
        MyString::formatoNumero($caja['industrial']->importe, 2, '', false),
      ), false, 'B');
    }


    // TOTALES
    $resultado_importe = $venta_importe - ($compra_fruta_importe + $existencia_ant_importe - $existencia_importe) - $produccion_importe - ($costoVentas_importe + $descuentoVentasFletes_importe + $comisionTerceros_importe) + $industrial_importe;
    $resultado_kilos = $existencia_ant_kilos + $compra_fruta_kilos - $existencia_kilos - $venta_kilos;
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('ESTADO DE RESULTADO'), true, 'B');

    $pdf->SetXY(6, $pdf->GetY()+0.3);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(50, 30));

    ($pdf->GetY()+10 >= $pdf->limiteY)? $pdf->AddPage(): '';
    $yaux = $pdf->GetY();
    $pageaux = $pdf->page;
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('(+) KGS EXISTENCIA ANTERIOR', MyString::formatoNumero($existencia_ant_kilos, 2, '', false)), true, false);
    ($pdf->GetY()+10 >= $pdf->limiteY)? $pdf->AddPage(): '';
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('(+) KGS COMPRADOS', MyString::formatoNumero($compra_fruta_kilos, 2, '', false)), true, false);
    ($pdf->GetY()+10 >= $pdf->limiteY)? $pdf->AddPage(): '';
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('(-) KGS EXISTENCIA EMPACADA', MyString::formatoNumero($existencia_kilos, 2, '', false)), true, false);
    ($pdf->GetY()+10 >= $pdf->limiteY)? $pdf->AddPage(): '';
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('(+) KGS PROCESADOS', MyString::formatoNumero($existencia_ant_kilos + $compra_fruta_kilos - $existencia_kilos, 2, '', false)), true, 'B');

    ($pdf->GetY()+10 >= $pdf->limiteY)? $pdf->AddPage(): '';
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('(-) KGS VENDIDOS', MyString::formatoNumero($venta_kilos, 2, '', false)), true, 'B');

    ($pdf->GetY()+10 >= $pdf->limiteY)? $pdf->AddPage(): '';
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('INDUSTRIAL KGS', MyString::formatoNumero($resultado_kilos, 2, '', false)), true, 'B');

    if (count($pdf->pages) > $pageaux) {
      $pdf->page = $pageaux;
    }
    $pdf->SetXY(130, $yaux);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(50, 30));

    $pdf->chkSaltaPag([130, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(130, $pdf->GetY());
    $pdf->Row(array('(+) VENTAS', MyString::formatoNumero($venta_importe, 2, '', false)), true, 'B');

    $pdf->SetFont('Arial', '', 8);
    $pdf->chkSaltaPag([130, 10]);
    $pdf->SetXY(130, $pdf->GetY());
    $pdf->Row(array('(+) EXISTENCIA ANTERIOR', MyString::formatoNumero($existencia_ant_importe, 2, '', false)), true, false);
    $pdf->chkSaltaPag([130, 10]);
    $pdf->SetXY(130, $pdf->GetY());
    $pdf->Row(array('(+) COMPRA DE FRUTA', MyString::formatoNumero($compra_fruta_importe, 2, '', false)), true, false);
    $pdf->chkSaltaPag([130, 10]);
    $pdf->SetXY(130, $pdf->GetY());
    $pdf->Row(array('(-) EXISTENCIA EMPACADA', MyString::formatoNumero($existencia_importe, 2, '', false)), true, false);
    $pdf->chkSaltaPag([130, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(130, $pdf->GetY());
    $pdf->Row(array('(-) COSTO DE MATERIA PRIMA', MyString::formatoNumero($compra_fruta_importe + $existencia_ant_importe - $existencia_importe, 2, '', false)), true, 'B');

    $pdf->chkSaltaPag([130, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(130, $pdf->GetY());
    $pdf->Row(array('(-) COSTO DE PRODUCCION', MyString::formatoNumero($produccion_importe, 2, '', false)), true, 'B');

    $pdf->SetFont('Arial', '', 8);
    $pdf->chkSaltaPag([130, 10]);
    $pdf->SetXY(130, $pdf->GetY());
    $pdf->Row(array('(+) COSTO DE VENTAS', MyString::formatoNumero($costoVentas_importe, 2, '', false)), true, false);
    $pdf->chkSaltaPag([130, 10]);
    $pdf->SetXY(130, $pdf->GetY());
    $pdf->Row(array('(+) FLETES', MyString::formatoNumero($descuentoVentasFletes_importe, 2, '', false)), true, false);
    $pdf->chkSaltaPag([130, 10]);
    $pdf->SetXY(130, $pdf->GetY());
    $pdf->Row(array('(+) COMISION A TERCEROS', MyString::formatoNumero($comisionTerceros_importe, 2, '', false)), true, false);
    $pdf->chkSaltaPag([130, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(130, $pdf->GetY());
    $pdf->Row(array('(-) GASTOS DE VENTAS', MyString::formatoNumero($costoVentas_importe + $descuentoVentasFletes_importe + $comisionTerceros_importe, 2, '', false)), true, 'B');
    $pdf->chkSaltaPag([130, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(130, $pdf->GetY());
    $pdf->Row(array('(+) INDUSTRIAL', MyString::formatoNumero($industrial_importe, 2, '', false)), true, 'B');

    $pdf->chkSaltaPag([130, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(130, $pdf->GetY());
    $pdf->Row(array('(=) RESULTADO DEL DIA', MyString::formatoNumero($resultado_importe, 2, '', false)), true, 'B');

    $pdf->page = count($pdf->pages);
    $pdf->Output('REPORTE_EXISTENCIAS.pdf', 'I');

  }




}

/* End of file caja_chica_model.php */
/* Location: ./application/models/caja_chica_model.php */