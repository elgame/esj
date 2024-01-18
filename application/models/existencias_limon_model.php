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
      'saldo_inicial'         => 0,
      'ventas'                => array(),
      'certificados'          => array(),
      'ventas_industrial'     => array(),
      'compra_fruta'          => array(),
      'produccion'            => array(),
      'gastos'                => array(),
      'existencia_anterior'   => [],
      'existencia'            => [],
      'existencia_piso'       => [],
      'existencia_piso_anterior' => [],
      'existencia_reproceso'  => [],
      'compra_fruta_empacada' => [],
      'devolucion_fruta'      => [],
      'devolucion_fruta_indust' => [],
      'manooInsumos'          => [],
      'costo_ventas'          => [],
      'costo_ventas_fletes'   => [],
      'comision_terceros'     => [],
      'industrial'            => [],
    );

    $fecha_anterior = $this->db->query(
      "SELECT Date(fecha) AS fecha
      FROM otros.existencias_limon
      WHERE Date(fecha) < '{$fecha}' AND no_caja = {$noCaja} AND id_area = {$id_area}
      ORDER BY fecha DESC
      LIMIT 1"
    )->row();
    $fecha_anterior = isset($fecha_anterior->fecha)? $fecha_anterior->fecha: MyString::suma_fechas($fecha, -1);


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

    $ids_ventas = [];
    if ($ventas->num_rows() > 0)
    {
      $info['ventas'] = $ventas->result();

      if ($id_area == 2) { // limon
        foreach ($info['ventas'] as $key => $value) {
          $ids_ventas[] = $value->id_factura;
          if ($value->id_calibre == 133) { // INDUSTRIAL
            $info['ventas_industrial'][] = $value;
            unset($info['ventas'][$key]);
          }
        }
      }
    }

    if (count($ids_ventas) > 0) {
      $certificados = $this->db->query(
        "SELECT f.id_factura, f.serie, f.folio, cl.nombre_fiscal, c.id_clasificacion,
          c.nombre AS clasificacion, fp.cantidad, (fp.importe / fp.cantidad) AS precio,
          fp.importe, fc.proveedores, fc.certificado, fc.no_certificado
        FROM facturacion f
          INNER JOIN clientes cl ON cl.id_cliente = f.id_cliente
          INNER JOIN facturacion_productos fp ON f.id_factura = fp.id_factura
          INNER JOIN clasificaciones c ON c.id_clasificacion = fp.id_clasificacion
          LEFT JOIN (
            SELECT id_factura, id_clasificacion, STRING_AGG(Distinct p.nombre_fiscal, ',') AS proveedores,
              STRING_AGG(Distinct fsc.certificado, ',') AS certificado,
              STRING_AGG(Distinct fsc.no_certificado, ',') AS no_certificado
            FROM facturacion_seg_cert fsc
              INNER JOIN proveedores p ON p.id_proveedor = fsc.id_proveedor
            GROUP BY id_factura, id_clasificacion
          ) fc ON (f.id_factura = fc.id_factura AND  c.id_clasificacion = fc.id_clasificacion)
        WHERE f.id_factura In(".implode(',', $ids_ventas).")
        AND c.id_clasificacion in(49,52,51,53)
        ORDER BY folio ASC"
      );

      if ($certificados->num_rows() > 0)
      {
        $info['certificados'] = $certificados->result();
      }
    }

    $compra_fruta = $this->db->query(
      "SELECT e.nombre_fiscal,
        c.id_calidad, c.nombre AS calidad, Sum(bc.kilos) AS kilos,
        (Sum(bc.importe) / Coalesce(NULLIF(Sum(bc.kilos), 0), 1)) AS precio,
        (Sum(bc.kilos) / Coalesce(NULLIF(Sum(bc.cajas), 0), 1)) AS peso_prom,
        (Sum(bc.cajas)) AS cajas,
        Sum(bc.importe) AS importe, (NULLIF(c.id_calidad, 2) IS NULL) AS is_fruta
      FROM bascula b
        INNER JOIN bascula_compra bc ON b.id_bascula = bc.id_bascula
        INNER JOIN calidades c ON c.id_calidad = bc.id_calidad
        INNER JOIN empresas e ON e.id_empresa = b.id_empresa
      WHERE b.id_empresa in({$id_empresa}, 15) AND b.status = 't' AND b.intangible = 'f'
        AND b.id_area = {$id_area} AND Date(b.fecha_bruto) = '{$fecha}'
        AND b.id_bonificacion IS NULL
      GROUP BY c.id_calidad, e.id_empresa
      ORDER BY id_calidad ASC"
    );

    if ($compra_fruta->num_rows() > 0)
    {
      $info['compra_fruta'] = $compra_fruta->result();
    }


    $produccion = $this->db->query(
      "SELECT STRING_AGG(Distinct c.nombre, ', ') AS clasificacion,
        Sum(rrc.rendimiento) AS cantidad,
        -- Sum(CASE WHEN u.codigo <> 'KILOS' THEN rrc.rendimiento ELSE 0 END) AS cantidad,
        Coalesce(elp.costo, 0) AS costo, (Coalesce(elp.costo, 0)*Sum(rrc.rendimiento)) AS importe, u.id_unidad,
        Coalesce(u.codigo, u.nombre) AS unidad, u.cantidad AS unidad_cantidad,
        (Sum(rrc.rendimiento) * u.cantidad) AS kilos,
        elp.id AS id_produccion, ca.id_calibre, ca.nombre AS calibre
      FROM rastria_rendimiento rr
        INNER JOIN rastria_rendimiento_clasif rrc ON rr.id_rendimiento = rrc.id_rendimiento
        INNER JOIN calibres ca ON ca.id_calibre = rrc.id_size
        INNER JOIN clasificaciones c ON c.id_clasificacion = rrc.id_clasificacion
        INNER JOIN unidades u ON u.id_unidad = rrc.id_unidad
        LEFT JOIN otros.existencias_limon_produccion elp ON (elp.id_calibre = ca.id_calibre
          AND elp.id_unidad = u.id_unidad AND Date(elp.fecha) = '{$fecha}')
      WHERE rr.status = 't' AND c.id_area = {$id_area} AND rrc.fruta_com = 'f'
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

    // Existencia piso anterior
    $existencia_piso_ant = $this->db->query(
      "SELECT ele.id_calibre, ele.id_unidad, ele.fecha, ele.no_caja, ele.costo, ele.kilos,
        ele.cantidad, ele.importe, c.nombre AS calibre, Coalesce(u.codigo, u.nombre) AS unidad,
        u.cantidad AS unidad_cantidad, '' AS clasificacion
      FROM otros.existencias_limon_existencia_piso ele
        INNER JOIN calibres c ON c.id_calibre = ele.id_calibre
        INNER JOIN unidades u ON u.id_unidad = ele.id_unidad
      WHERE Date(ele.fecha) = '{$fecha_anterior}' AND ele.no_caja = {$noCaja}
        AND ele.id_area = {$id_area}"
    );

    if ($existencia_piso_ant->num_rows() > 0)
    {
      $info['existencia_piso_anterior'] = $existencia_piso_ant->result();
    }

    // Compra de fruta empacada
    $id_calibree = ($id_area == 2? 135: 135); // A granel
    $compra_fruta_empacada = $this->db->query(
      "SELECT ele.id_calibre, ele.id_unidad, ele.fecha, ele.no_caja, ele.costo, ele.kilos,
        ele.cantidad, ele.importe, c.nombre AS calibre, Coalesce(u.codigo, u.nombre) AS unidad,
        u.cantidad AS unidad_cantidad, '' AS clasificacion
      FROM otros.existencias_limon_compra_fruta ele
        INNER JOIN calibres c ON c.id_calibre = ele.id_calibre
        INNER JOIN unidades u ON u.id_unidad = ele.id_unidad
      WHERE Date(ele.fecha) = '{$fecha}' AND ele.no_caja = {$noCaja}
        AND ele.id_area = {$id_area}"
    );

    if ($compra_fruta_empacada->num_rows() > 0)
    {
      $info['compra_fruta_empacada'] = $compra_fruta_empacada->result();
    }

    // Devolucion fruta
    $id_calibree = ($id_area == 2? 135: 135); // A granel
    $devolucion_fruta = $this->db->query(
      "SELECT ele.id_calibre, ele.id_unidad, ele.fecha, ele.no_caja, ele.costo, ele.kilos,
        ele.cantidad, ele.importe, c.nombre AS calibre, Coalesce(u.codigo, u.nombre) AS unidad,
        u.cantidad AS unidad_cantidad, '' AS clasificacion
      FROM otros.existencias_limon_devolucion_fruta ele
        INNER JOIN calibres c ON c.id_calibre = ele.id_calibre
        INNER JOIN unidades u ON u.id_unidad = ele.id_unidad
      WHERE ele.tipo = 'frut' AND Date(ele.fecha) = '{$fecha}' AND ele.no_caja = {$noCaja}
        AND ele.id_area = {$id_area}"
    );

    if ($devolucion_fruta->num_rows() > 0)
    {
      $info['devolucion_fruta'] = $devolucion_fruta->result();
    }

    // Devolucion LIMON AL INDUSTRIAL
    $id_calibree = ($id_area == 2? 135: 135); // A granel
    $devolucion_fruta_indust = $this->db->query(
      "SELECT ele.id_calibre, ele.id_unidad, ele.fecha, ele.no_caja, ele.costo, ele.kilos,
        ele.cantidad, ele.importe, c.nombre AS calibre, Coalesce(u.codigo, u.nombre) AS unidad,
        u.cantidad AS unidad_cantidad, '' AS clasificacion
      FROM otros.existencias_limon_devolucion_fruta ele
        INNER JOIN calibres c ON c.id_calibre = ele.id_calibre
        INNER JOIN unidades u ON u.id_unidad = ele.id_unidad
      WHERE ele.tipo = 'inds' AND Date(ele.fecha) = '{$fecha}' AND ele.no_caja = {$noCaja}
        AND ele.id_area = {$id_area}"
    );

    if ($devolucion_fruta_indust->num_rows() > 0)
    {
      $info['devolucion_fruta_indust'] = $devolucion_fruta_indust->result();
    }

    // Mano de obra e insumos
    $manooInsumos = $this->db->query(
      "SELECT ele.descripcion, ele.id_unidad, ele.fecha, ele.no_caja, ele.costo, ele.kilos,
        ele.cantidad, ele.importe, Coalesce(u.codigo, u.nombre) AS unidad,
        u.cantidad AS unidad_cantidad, '' AS clasificacion
      FROM otros.existencias_limon_manoobra_insumo ele
        INNER JOIN unidades u ON u.id_unidad = ele.id_unidad
      WHERE Date(ele.fecha) = '{$fecha}' AND ele.no_caja = {$noCaja}
        AND ele.id_area = {$id_area}"
    );

    if ($manooInsumos->num_rows() > 0)
    {
      $info['manooInsumos'] = $manooInsumos->result();
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
          Sum(cp.cantidad) AS cantidad, Sum(cp.importe) AS importe, Sum(cp.total) AS total, co.ids_facrem
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
        foreach ($info['costo_ventas_fletes'] as $key => $value) {
          $ids_facrem = str_replace(['f:', 't:', '|'], ['', '', ','], $value->ids_facrem);
          $ids_facrem = substr($ids_facrem, 0, strlen($ids_facrem)-1);
          if ($ids_facrem != '') {
            $facturas_fletes = $this->db->query(
              "SELECT String_agg((f.serie || f.folio::text), ', ') AS facturas
              FROM facturacion f
              WHERE f.id_factura in({$ids_facrem})"
            )->row();
          }

          $info['costo_ventas_fletes'][$key]->facturas = (isset($facturas_fletes->facturas)? $facturas_fletes->facturas: '');
        }
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


    $existencia_anterior = $this->db->query(
      "SELECT ele.id_calibre, ele.id_unidad, ele.fecha, ele.no_caja, ele.costo, ele.kilos,
        ele.cantidad, ele.importe, c.nombre AS calibre, Coalesce(u.codigo, u.nombre) AS unidad,
        u.cantidad AS unidad_cantidad, '' AS clasificacion
      FROM otros.existencias_limon_existencia ele
        INNER JOIN calibres c ON c.id_calibre = ele.id_calibre
        INNER JOIN unidades u ON u.id_unidad = ele.id_unidad
      WHERE Date(ele.fecha) = '{$fecha_anterior}' AND ele.no_caja = {$noCaja}
        AND ele.id_area = {$id_area}
      ORDER BY c.order ASC"
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
    foreach ($info['compra_fruta_empacada'] as $key => $item) {
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
    foreach ($info['devolucion_fruta'] as $key => $item) {
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
    foreach ($info['devolucion_fruta_indust'] as $key => $item) {
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

    // gastos
    $info['gastos'] = $this->getCajaGastos($fecha, $noCaja);

    // Dia guardado
    $count_save = $this->db->query("SELECT Count(*) AS num
      FROM otros.existencias_limon
      WHERE fecha = '{$fecha}' AND no_caja = {$noCaja} AND id_area = {$id_area}")->row();
    $info['guardado'] = $count_save->num > 0? true: false;


    return $info;
  }

  public function guardar($data)
  {
    $anio = date("Y");

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

    // Compra de fruta empacada
    $this->db->delete('otros.existencias_limon_compra_fruta', "fecha = '{$data['fecha_caja_chica']}' AND no_caja = {$data['fno_caja']} AND id_area = {$data['farea']}");
    $compra_fruta_empacada = [];
    if (!empty($data['frutaCompra_id_unidad'])) {
      foreach ($data['frutaCompra_id_unidad'] as $key => $id_cat)
      {
        $compra_fruta_empacada[] = array(
          'id_area'    => $data['farea'],
          'id_calibre' => $data['frutaCompra_id_calibre'][$key],
          'id_unidad'  => $data['frutaCompra_id_unidad'][$key],
          'costo'      => $data['frutaCompra_costo'][$key],
          'kilos'      => $data['frutaCompra_kilos'][$key],
          'cantidad'   => $data['frutaCompra_cantidad'][$key],
          'importe'    => $data['frutaCompra_importe'][$key],
          'fecha'      => $data['fecha_caja_chica'],
          'no_caja'    => $data['fno_caja'],
        );
      }
      if (count($compra_fruta_empacada) > 0)
      {
        $this->db->insert_batch('otros.existencias_limon_compra_fruta', $compra_fruta_empacada);
      }
    }

    // Devolucion de fruta
    $this->db->delete('otros.existencias_limon_devolucion_fruta',
      "tipo = 'frut' AND fecha = '{$data['fecha_caja_chica']}' AND no_caja = {$data['fno_caja']} AND id_area = {$data['farea']}");
    $devolucion_fruta = [];
    if (!empty($data['devFruta_id_unidad'])) {
      foreach ($data['devFruta_id_unidad'] as $key => $id_cat)
      {
        $devolucion_fruta[] = array(
          'id_area'    => $data['farea'],
          'id_calibre' => $data['devFruta_id_calibre'][$key],
          'id_unidad'  => $data['devFruta_id_unidad'][$key],
          'costo'      => $data['devFruta_costo'][$key],
          'kilos'      => $data['devFruta_kilos'][$key],
          'cantidad'   => $data['devFruta_cantidad'][$key],
          'importe'    => $data['devFruta_importe'][$key],
          'fecha'      => $data['fecha_caja_chica'],
          'no_caja'    => $data['fno_caja'],
          'tipo'       => 'frut',
        );
      }
      if (count($devolucion_fruta) > 0)
      {
        $this->db->insert_batch('otros.existencias_limon_devolucion_fruta', $devolucion_fruta);
      }
    }

    // Devolucion de fruta al industrial
    $this->db->delete('otros.existencias_limon_devolucion_fruta',
      "tipo = 'inds' AND fecha = '{$data['fecha_caja_chica']}' AND no_caja = {$data['fno_caja']} AND id_area = {$data['farea']}");
    $devolucion_fruta = [];
    if (!empty($data['devFrutaInds_id_unidad'])) {
      foreach ($data['devFrutaInds_id_unidad'] as $key => $id_cat)
      {
        $devolucion_fruta[] = array(
          'id_area'    => $data['farea'],
          'id_calibre' => $data['devFrutaInds_id_calibre'][$key],
          'id_unidad'  => $data['devFrutaInds_id_unidad'][$key],
          'costo'      => $data['devFrutaInds_costo'][$key],
          'kilos'      => $data['devFrutaInds_kilos'][$key],
          'cantidad'   => $data['devFrutaInds_cantidad'][$key],
          'importe'    => $data['devFrutaInds_importe'][$key],
          'fecha'      => $data['fecha_caja_chica'],
          'no_caja'    => $data['fno_caja'],
          'tipo'       => 'inds',
        );
      }
      if (count($devolucion_fruta) > 0)
      {
        $this->db->insert_batch('otros.existencias_limon_devolucion_fruta', $devolucion_fruta);
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

    // Gastos
    if (isset($data['gasto_concepto']))
    {
      $data_folio = $this->db->query("SELECT COALESCE( (SELECT folio_sig FROM otros.existencias_limon_gastos
        WHERE folio_sig IS NOT NULL AND no_caja = {$data['fno_caja']} AND date_part('year', fecha) = {$anio}
        ORDER BY folio_sig DESC LIMIT 1), 0 ) AS folio")->row();

      $gastos_ids = array('adds' => array(), 'delets' => array(), 'updates' => array());
      $gastos_udt = $gastos = array();
      foreach ($data['gasto_concepto'] as $key => $gasto)
      {
        if (isset($data['gasto_del'][$key]) && $data['gasto_del'][$key] == 'true' &&
          isset($data['gasto_id_gasto'][$key]) && floatval($data['gasto_id_gasto'][$key]) > 0) {
          $gastos_ids['delets'][] = $this->getDataGasto($data['gasto_id_gasto'][$key]);

          // $this->db->delete('otros.existencias_limon_gastos', "id_gasto = ".$data['gasto_id_gasto'][$key]);
          $this->db->update('otros.existencias_limon_gastos', ['status' => 'f', 'fecha_cancelado' => $data['fecha_caja_chica']], "id_gasto = ".$data['gasto_id_gasto'][$key]);
        } elseif (isset($data['gasto_id_gasto'][$key]) && floatval($data['gasto_id_gasto'][$key]) > 0) {
          $gastos_udt = array(
            // 'folio'           => $data['gasto_folio'][$key],
            'concepto'        => $gasto,
            'nombre'          => $data['gasto_nombre'][$key],
            'monto'           => $data['gasto_importe'][$key],
            'fecha'           => $data['fecha_caja_chica'],
            'no_caja'         => $data['fno_caja'],
            'id_area'         => $data['farea'],
            $data['codigoCampo'][$key] => (isset($data['codigoAreaId'][$key]{0})? $data['codigoAreaId'][$key]: NULL),
          );

          $this->db->update('otros.existencias_limon_gastos', $gastos_udt, "id_gasto = ".$data['gasto_id_gasto'][$key]);
        } else {
          $data_folio->folio += 1;
          $gastos = array(
            'folio_sig'                => $data_folio->folio,
            'folio'                    => $data_folio->folio, //$data['gasto_folio'][$key],
            'concepto'                 => $gasto,
            'nombre'                   => $data['gasto_nombre'][$key],
            'monto'                    => $data['gasto_importe'][$key],
            'fecha'                    => $data['fecha_caja_chica'],
            'no_caja'                  => $data['fno_caja'],
            'id_area'                  => $data['farea'],
            $data['codigoCampo'][$key] => (isset($data['codigoAreaId'][$key]{0})? $data['codigoAreaId'][$key]: NULL),
            'id_usuario'               => $this->session->userdata('id_usuario'),
          );
          $this->db->insert('otros.existencias_limon_gastos', $gastos);
          $gastooidd = $this->db->insert_id('otros.existencias_limon_gastos_id_gasto_seq');
          $gastos_ids['adds'][] = $gastooidd;
        }
      }

      // if (count($gastos_ids['adds']) > 0 || count($gastos_ids['delets']) > 0) {
      //   $this->enviarEmail($gastos_ids);
      //   // $this->db->insert_batch('otros.existencias_limon_gastos', $gastos);
      // }
    }

    // mano de obra e insumos
    $this->db->delete('otros.existencias_limon_manoobra_insumo', "fecha = '{$data['fecha_caja_chica']}' AND no_caja = {$data['fno_caja']} AND id_area = {$data['farea']}");
    $manoo_insumos = [];
    if (!empty($data['manooInsumos_id_unidad'])) {
      foreach ($data['manooInsumos_id_unidad'] as $key => $id_cat)
      {
        $manoo_insumos[] = array(
          'id_area'     => $data['farea'],
          'id_unidad'   => $data['manooInsumos_id_unidad'][$key],
          'descripcion' => $data['manooInsumos_descripcion'][$key],
          'costo'       => $data['manooInsumos_costo'][$key],
          'kilos'       => $data['manooInsumos_kilos'][$key],
          'cantidad'    => $data['manooInsumos_cantidad'][$key],
          'importe'     => $data['manooInsumos_importe'][$key],
          'fecha'       => $data['fecha_caja_chica'],
          'no_caja'     => $data['fno_caja'],
        );
      }
      if (count($manoo_insumos) > 0)
      {
        $this->db->insert_batch('otros.existencias_limon_manoobra_insumo', $manoo_insumos);
      }
    }

    return true;
  }

  public function getCajaGastos($fecha, $noCaja)
  {
    $sql = '';
    $sql .= " AND cg.fecha = '{$fecha}'";

    $response = [];
    $gastos = $this->db->query(
      "SELECT cg.id_gasto, cg.concepto, cg.fecha, cg.monto AS monto, cg.nombre,
          cg.folio, ca.id_cat_codigos, ca.nombre AS cat_codigos, ca.codigo AS codigo_fin,
          ar.id_area, ar.nombre AS area
       FROM otros.existencias_limon_gastos cg
         LEFT JOIN otros.cat_codigos AS ca ON ca.id_cat_codigos = cg.id_cat_codigos
         LEFT JOIN areas AS ar ON ar.id_area = cg.id_area
       WHERE cg.no_caja = {$noCaja} {$sql}
       ORDER BY cg.id_gasto ASC"
    );

    if ($gastos->num_rows() > 0)
    {
      $response = $gastos->result();
    }

    return $response;
  }


  public function cerrarCaja($idCaja, $noCajas)
  {
    $this->db->update('otros.cajaprestamo_efectivo', array('status' => 'f'), array('id_efectivo' => $idCaja));
    return true;
  }


  public function printCajaOld($fecha, $noCajas, $id_area)
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

    $pdf->SetXY(6, $pdf->GetY());
    $fechaTime = new DateTime($fecha);
    // Obtiene la semana [01 - 52/53] y el dia de la semana [1 - 7]
    $pdf->Row(array('SEMANA ' . $fechaTime->format("W")), false, false);

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
    $pdf->SetAligns(array('L', 'L', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(65, 30, 25, 25, 25, 35));
    $pdf->Row(array('CALIBRE', 'UNIDAD', 'CANTIDAD', 'KILOS', 'COSTO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(65, 30, 25, 25, 25, 35));

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
        $existencia->calibre,
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

    // Compra de fruta empacada
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('COMPRA DE FRUTA EMPACADA'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(65, 30, 25, 25, 25, 35));
    $pdf->Row(array('CALIBRE', 'UNIDAD', 'CANTIDAD', 'KILOS', 'COSTO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(65, 30, 25, 25, 25, 35));

    $frutaCompra_kilos = $frutaCompra_cantidad = $frutaCompra_importe = 0;
    foreach ($caja['compra_fruta_empacada'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $frutaCompra_kilos    += floatval($existencia->kilos);
      $frutaCompra_cantidad += floatval($existencia->cantidad);
      $frutaCompra_importe  += floatval($existencia->importe);

      $pdf->SetX(6);
      $pdf->Row(array(
        $existencia->calibre,
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
      '',
      MyString::formatoNumero($frutaCompra_kilos, 2, '', false),
      MyString::formatoNumero($frutaCompra_cantidad, 2, '', false),
      MyString::formatoNumero(($frutaCompra_importe/($frutaCompra_cantidad==0? 1: $frutaCompra_cantidad)), 2, '', false),
      MyString::formatoNumero($frutaCompra_importe, 2, '', false),
    ), false, 'B');

    // Devolucion de fruta
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('DEVOLUCIÓN DE FRUTA'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(65, 30, 25, 25, 25, 35));
    $pdf->Row(array('CALIBRE', 'UNIDAD', 'CANTIDAD', 'KILOS', 'COSTO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(65, 30, 25, 25, 25, 35));

    $devFruta_kilos = $devFruta_cantidad = $devFruta_importe = 0;
    foreach ($caja['devolucion_fruta'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $devFruta_kilos    += floatval($existencia->kilos);
      $devFruta_cantidad += floatval($existencia->cantidad);
      $devFruta_importe  += floatval($existencia->importe);

      $pdf->SetX(6);
      $pdf->Row(array(
        $existencia->calibre,
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
      '',
      MyString::formatoNumero($devFruta_kilos, 2, '', false),
      MyString::formatoNumero($devFruta_cantidad, 2, '', false),
      MyString::formatoNumero(($devFruta_importe/($devFruta_cantidad==0? 1: $devFruta_cantidad)), 2, '', false),
      MyString::formatoNumero($devFruta_importe, 2, '', false),
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

    // GASTOS GENERALES
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('GASTOS GENERALES'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'C'));
    $pdf->SetWidths(array(80, 95, 30));
    $pdf->Row(array('NOMBRE', 'CONCEPTO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(80, 95, 30));

    $totalGastos = 0;
    foreach ($caja['gastos'] as $gasto) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $totalGastos  += floatval($gasto->monto);

      $pdf->SetX(6);
      $pdf->Row(array(
        $gasto->nombre,
        $gasto->concepto,
        MyString::formatoNumero($gasto->monto, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(6);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($totalGastos, 2, '', false),
    ), false, 'B');


    // TOTALES
    $resultado_importe = $venta_importe - ($compra_fruta_importe + $existencia_ant_importe - $existencia_importe) - $produccion_importe - $frutaCompra_importe - $devFruta_importe - ($costoVentas_importe + $descuentoVentasFletes_importe + $comisionTerceros_importe) + $industrial_importe;
    $resultado_kilos = $existencia_ant_kilos + $compra_fruta_kilos - $existencia_kilos - $venta_kilos + $frutaCompra_kilos + $devFruta_kilos;
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('ESTADO DE RESULTADO'), true, 'B');

    $pdf->SetXY(6, $pdf->GetY()+0.3);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(60, 30));

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
    $pdf->Row(array('(=) KGS PROCESADOS', MyString::formatoNumero($existencia_ant_kilos + $compra_fruta_kilos - $existencia_kilos, 2, '', false)), true, 'B');

    ($pdf->GetY()+10 >= $pdf->limiteY)? $pdf->AddPage(): '';
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('(+) KGS COMPRADOS EMPACADOS', MyString::formatoNumero($frutaCompra_kilos, 2, '', false)), true, 'B');

    ($pdf->GetY()+10 >= $pdf->limiteY)? $pdf->AddPage(): '';
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('(+) KGS DEVOLUCION DE FRUTA', MyString::formatoNumero($devFruta_kilos, 2, '', false)), true, 'B');

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
    $pdf->SetWidths(array(60, 30));

    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(+) VENTAS', MyString::formatoNumero($venta_importe, 2, '', false)), true, 'B');

    $pdf->SetFont('Arial', '', 8);
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(+) EXISTENCIA ANTERIOR', MyString::formatoNumero($existencia_ant_importe, 2, '', false)), true, false);
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(+) COMPRA DE FRUTA', MyString::formatoNumero($compra_fruta_importe, 2, '', false)), true, false);
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(-) EXISTENCIA EMPACADA', MyString::formatoNumero($existencia_importe, 2, '', false)), true, false);
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(-) COSTO DE MATERIA PRIMA', MyString::formatoNumero($compra_fruta_importe + $existencia_ant_importe - $existencia_importe, 2, '', false)), true, 'B');

    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(-) COSTO DE PRODUCCION', MyString::formatoNumero($produccion_importe, 2, '', false)), true, 'B');

    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(-) COMPRA DE FRUTA EMPACADA', MyString::formatoNumero($frutaCompra_importe, 2, '', false)), true, 'B');

    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(-) DEVOLUCION DE FRUTA', MyString::formatoNumero($devFruta_importe, 2, '', false)), true, 'B');

    $pdf->SetFont('Arial', '', 8);
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(+) COSTO DE VENTAS', MyString::formatoNumero($costoVentas_importe, 2, '', false)), true, false);
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(+) FLETES', MyString::formatoNumero($descuentoVentasFletes_importe, 2, '', false)), true, false);
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(+) COMISION A TERCEROS', MyString::formatoNumero($comisionTerceros_importe, 2, '', false)), true, false);
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(-) GASTOS DE VENTAS', MyString::formatoNumero($costoVentas_importe + $descuentoVentasFletes_importe + $comisionTerceros_importe, 2, '', false)), true, 'B');
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(+) INDUSTRIAL', MyString::formatoNumero($industrial_importe, 2, '', false)), true, 'B');

    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(=) RESULTADO DEL DIA', MyString::formatoNumero($resultado_importe, 2, '', false)), true, 'B');

    $pdf->page = count($pdf->pages);
    $pdf->Output('REPORTE_EXISTENCIAS.pdf', 'I');

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
    $pdf->titulo2 = "REPORTE EXISTENCIAS DE LIMON";
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
    $pdf->Row(array($pdf->titulo2), true, true, null, 3);

    $pdf->Image(APPPATH.(str_replace(APPPATH, '', '/images/logo.png')), 6, 15, 50);
    $pdf->Ln(20);

    $pdf->SetFont('Arial','B', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);

    // Fecha
    $pdf->SetXY(6, $pdf->GetY() - 20);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array(MyString::dia($fecha) . ': ' . MyString::fechaATexto($fecha, '/c')), false, false);

    $pdf->SetXY(6, $pdf->GetY());
    $fechaTime = new DateTime($fecha);
    // Obtiene la semana [01 - 52/53] y el dia de la semana [1 - 7]
    $pdf->Row(array('SEMANA ' . $fechaTime->format("W")), false, false);

    $pdf->auxy = $pdf->GetY();
    $page_aux = $pdf->page;


    // Existencia Anterior
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, 32);
    // $pdf->SetAligns(array('L'));
    // $pdf->SetWidths(array(204));
    // $pdf->Row(array('EXISTENCIA ANTERIOR'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 30, 20, 20, 18, 18, 22));
    $pdf->Row(array('EXISTENCIA ANTERIOR', 'CALIBRE', 'UNIDAD', 'KILOS', 'BULTOS', 'PRECIO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 20, 20, 18, 18, 22));

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

      $pdf->SetX(60);
      $pdf->Row(array(
        $existencia_ant->calibre,
        // $existencia_ant->clasificacion,
        $existencia_ant->unidad,
        MyString::formatoNumero($existencia_ant->kilos, 2, '', false),
        MyString::formatoNumero($existencia_ant->cantidad, 2, '', false),
        MyString::formatoNumero($existencia_ant->costo, 2, '', false),
        MyString::formatoNumero($existencia_ant->importe, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($existencia_ant_kilos, 2, '', false),
      MyString::formatoNumero($existencia_ant_cantidad, 2, '', false),
      MyString::formatoNumero(($existencia_ant_importe/($existencia_ant_cantidad==0? 1: $existencia_ant_cantidad)), 2, '', false),
      MyString::formatoNumero($existencia_ant_importe, 2, '', false),
    ), false, 'B');


    // Existencia anterior de piso
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    // $pdf->Row(array('EXISTENCIA DE PISO'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 30, 20, 18, 17, 17, 25, 22));
    $pdf->Row(array('EXISTENCIA DE PISO ANTERIOR', 'CALIBRE', 'UNIDAD', 'KILOS', 'BULTOS', 'COSTO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(60, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 20, 18, 17, 17, 25));

    $existenciaPisoAnt_kilos = $existenciaPisoAnt_cantidad = $existenciaPisoAnt_importe = 0;
    foreach ($caja['existencia_piso_anterior'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(60, 10);
        } else
          $pdf->AddPage();
      }

      $existenciaPisoAnt_kilos    += floatval($existencia->kilos);
      $existenciaPisoAnt_cantidad += floatval($existencia->cantidad);
      $existenciaPisoAnt_importe  += floatval($existencia->importe);

      $pdf->SetX(60);
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
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($existenciaPisoAnt_cantidad, 2, '', false),
      MyString::formatoNumero($existenciaPisoAnt_kilos, 2, '', false),
      MyString::formatoNumero(($existenciaPisoAnt_importe/($existenciaPisoAnt_cantidad==0? 1: $existenciaPisoAnt_cantidad)), 2, '', false),
      MyString::formatoNumero($existenciaPisoAnt_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('EXIST ANT'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero(($existenciaPisoAnt_importe+$existencia_ant_importe), 2, '', false)), false, 'B');


    // Compra de fruta empacada
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    // $pdf->SetAligns(array('L'));
    // $pdf->SetWidths(array(204));
    // $pdf->Row(array('COMPRA DE FRUTA EMPACADA'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 30, 20, 18, 18, 18, 24));
    $pdf->Row(array('COMPRA DE FRUTA EMPACADA', 'CALIBRE', 'UNIDAD', 'KILOS', 'BULTOS', 'COSTO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 20, 18, 18, 18, 24));

    $frutaCompra_kilos = $frutaCompra_cantidad = $frutaCompra_importe = 0;
    foreach ($caja['compra_fruta_empacada'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $frutaCompra_kilos    += floatval($existencia->kilos);
      $frutaCompra_cantidad += floatval($existencia->cantidad);
      $frutaCompra_importe  += floatval($existencia->importe);

      $pdf->SetX(60);
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
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($frutaCompra_kilos, 2, '', false),
      MyString::formatoNumero($frutaCompra_cantidad, 2, '', false),
      MyString::formatoNumero(($frutaCompra_importe/($frutaCompra_cantidad==0? 1: $frutaCompra_cantidad)), 2, '', false),
      MyString::formatoNumero($frutaCompra_importe, 2, '', false),
    ), false, 'B');
    // $pdf->SetAligns(array('R'));
    // $pdf->SetWidths(array(22));
    // $pdf->SetXY(188, $pdf->GetY()-11);
    // $pdf->Row(array('COMPRAS'), false, 'B');
    // $pdf->SetXY(188, $pdf->GetY());
    // $pdf->Row(array(MyString::formatoNumero($frutaCompra_importe, 2, '', false)), false, 'B');

    // COMPARA DE LIMON
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    // $pdf->SetAligns(array('L'));
    // $pdf->SetWidths(array(204));
    // $pdf->Row(array('COMPRA DE FRUTA'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 30, 20, 20, 18, 18, 22));
    $pdf->Row(array('MATERIA PRIMA:', 'CALIBRE', 'PESO PROM', 'KILOS', 'CAJAS', 'PRECIO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 20, 20, 18, 18, 22));

    $compra_fruta_kilos = $compra_fruta_cajas = $compra_fruta_importe = 0;
    foreach ($caja['compra_fruta'] as $com_fruta) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $compra_fruta_kilos += floatval($com_fruta->kilos);
      $compra_fruta_cajas += floatval(0);
      $compra_fruta_importe += floatval($com_fruta->importe);

      $pdf->SetX(60);
      $pdf->Row(array(
        $com_fruta->calidad,
        MyString::formatoNumero($com_fruta->peso_prom, 2, '', false),
        MyString::formatoNumero($com_fruta->kilos, 2, '', false),
        MyString::formatoNumero($com_fruta->cajas, 2, '', false),
        MyString::formatoNumero($com_fruta->precio, 2, '', false),
        MyString::formatoNumero($com_fruta->importe, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($compra_fruta_kilos, 2, '', false),
      MyString::formatoNumero($compra_fruta_cajas, 2, '', false),
      MyString::formatoNumero(($compra_fruta_importe/($compra_fruta_kilos==0? 1: $compra_fruta_kilos)), 2, '', false),
      MyString::formatoNumero($compra_fruta_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('COMPRAS'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero(($compra_fruta_importe+$frutaCompra_importe), 2, '', false)), false, 'B');


    // Devolucion de fruta
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    // $pdf->SetAligns(array('L'));
    // $pdf->SetWidths(array(204));
    // $pdf->Row(array('DEVOLUCIÓN DE FRUTA'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 30, 20, 18, 18, 18, 24));
    $pdf->Row(array('DEVOLUCIÓN DE FRUTA AL INDUSTRIAL', 'CALIBRE', 'UNIDAD', 'CANTIDAD', 'KILOS', 'COSTO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 20, 18, 18, 18, 24));

    $devFrutaIndus_kilos = $devFrutaIndus_cantidad = $devFrutaIndus_importe = 0;
    foreach ($caja['devolucion_fruta_indust'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $devFrutaIndus_kilos    += floatval($existencia->kilos);
      $devFrutaIndus_cantidad += floatval($existencia->cantidad);
      $devFrutaIndus_importe  += floatval($existencia->importe);

      $pdf->SetX(60);
      $pdf->Row(array(
        $existencia->calibre,
        $existencia->unidad,
        MyString::formatoNumero($existencia->cantidad, 2, '', false),
        MyString::formatoNumero($existencia->kilos, 2, '', false),
        MyString::formatoNumero($existencia->costo, 2, '', false),
        MyString::formatoNumero($existencia->importe, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($devFrutaIndus_kilos, 2, '', false),
      MyString::formatoNumero($devFrutaIndus_cantidad, 2, '', false),
      MyString::formatoNumero(($devFrutaIndus_importe/($devFrutaIndus_cantidad==0? 1: $devFrutaIndus_cantidad)), 2, '', false),
      MyString::formatoNumero($devFrutaIndus_importe, 2, '', false),
    ), false, 'B');
    // $pdf->SetAligns(array('R'));
    // $pdf->SetWidths(array(22));
    // $pdf->SetXY(188, $pdf->GetY()-11);
    // $pdf->Row(array('DEVOLUCIÓN'), false, 'B');
    // $pdf->SetXY(188, $pdf->GetY());
    // $pdf->Row(array(MyString::formatoNumero($devFruta_importe, 2, '', false)), false, 'B');

    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 30, 20, 18, 18, 18, 24));
    $pdf->Row(array('DEVOLUCIÓN DE FRUTA', 'CALIBRE', 'UNIDAD', 'CANTIDAD', 'KILOS', 'COSTO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 20, 18, 18, 18, 24));

    $devFruta_kilos = $devFruta_cantidad = $devFruta_importe = 0;
    foreach ($caja['devolucion_fruta'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $devFruta_kilos    += floatval($existencia->kilos);
      $devFruta_cantidad += floatval($existencia->cantidad);
      $devFruta_importe  += floatval($existencia->importe);

      $pdf->SetX(60);
      $pdf->Row(array(
        $existencia->calibre,
        $existencia->unidad,
        MyString::formatoNumero($existencia->cantidad, 2, '', false),
        MyString::formatoNumero($existencia->kilos, 2, '', false),
        MyString::formatoNumero($existencia->costo, 2, '', false),
        MyString::formatoNumero($existencia->importe, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($devFruta_kilos, 2, '', false),
      MyString::formatoNumero($devFruta_cantidad, 2, '', false),
      MyString::formatoNumero(($devFruta_importe/($devFruta_cantidad==0? 1: $devFruta_cantidad)), 2, '', false),
      MyString::formatoNumero($devFruta_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('DEVOLUCIÓN'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero(($devFruta_importe+$devFrutaIndus_importe), 2, '', false)), false, 'B');


    // Ventas
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(182));
    $pdf->Row(array('VENTAS'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(18, 18, 35, 25, 16, 18, 18, 12, 20, 22));
    $pdf->Row(array('FOLIO', 'SF', 'CLIENTE', 'CALIBRE PROD.', 'UNIDAD', 'KILOS', 'BULTOS', 'PRECIO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'L', 'L', 'C', 'R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(20, 18, 35, 25, 16, 18, 18, 12, 20, 22));

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
        // $venta->clasificacion,
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
      // '',
      '',
      '',
      '',
      MyString::formatoNumero($venta_kilos, 2, '', false),
      MyString::formatoNumero($venta_cantidad, 2, '', false),
      MyString::formatoNumero(($venta_importe/($venta_cantidad==0? 1: $venta_cantidad)), 2, '', false),
      MyString::formatoNumero($venta_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('VENTAS'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero($venta_importe, 2, '', false)), false, 'B');


    // Existencia
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    // $pdf->Row(array('EXISTENCIA EMPACADA'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 30, 20, 18, 17, 17, 25, 22));
    $pdf->Row(array('EXISTENCIA EMPACADA', 'CALIBRE', 'UNIDAD', 'KILOS', 'BULTOS', 'COSTO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(60, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 20, 18, 17, 17, 25, 22));

    $existencia_kilos = $existencia_cantidad = $existencia_importe = 0;
    foreach ($caja['existencia'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(60, 10);
        } else
          $pdf->AddPage();
      }

      $existencia_kilos    += floatval($existencia->kilos);
      $existencia_cantidad += floatval($existencia->cantidad);
      $existencia_importe  += floatval($existencia->importe);

      if ($existencia->cantidad != 0 ) {
        $pdf->SetX(60);
        $pdf->Row(array(
          $existencia->calibre,
          // $existencia->clasificacion,
          $existencia->unidad,
          MyString::formatoNumero($existencia->kilos, 2, '', false),
          MyString::formatoNumero($existencia->cantidad, 2, '', false),
          MyString::formatoNumero($existencia->costo, 2, '', false),
          MyString::formatoNumero($existencia->importe, 2, '', false),
        ), false, 'B');
      }
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($existencia_kilos, 2, '', false),
      MyString::formatoNumero($existencia_cantidad, 2, '', false),
      MyString::formatoNumero(($existencia_importe/($existencia_cantidad==0? 1: $existencia_cantidad)), 2, '', false),
      MyString::formatoNumero($existencia_importe, 2, '', false),
    ), false, 'B');
    // $pdf->SetAligns(array('R'));
    // $pdf->SetWidths(array(22));
    // $pdf->SetXY(188, $pdf->GetY()-11);
    // $pdf->Row(array('EXISTENCIA E.'), false, 'B');
    // $pdf->SetXY(188, $pdf->GetY());
    // $pdf->Row(array(MyString::formatoNumero($existencia_importe, 2, '', false)), false, 'B');


    // Existencia de piso
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    // $pdf->Row(array('EXISTENCIA DE PISO'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 30, 20, 18, 17, 17, 25, 22));
    $pdf->Row(array('EXISTENCIA DE PISO', 'CALIBRE', 'UNIDAD', 'KILOS', 'BULTOS', 'COSTO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(60, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 20, 18, 17, 17, 25));

    $existenciaPiso_kilos = $existenciaPiso_cantidad = $existenciaPiso_importe = 0;
    foreach ($caja['existencia_piso'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(60, 10);
        } else
          $pdf->AddPage();
      }

      $existenciaPiso_kilos    += floatval($existencia->kilos);
      $existenciaPiso_cantidad += floatval($existencia->cantidad);
      $existenciaPiso_importe  += floatval($existencia->importe);

      $pdf->SetX(60);
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
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($existenciaPiso_cantidad, 2, '', false),
      MyString::formatoNumero($existenciaPiso_kilos, 2, '', false),
      MyString::formatoNumero(($existenciaPiso_importe/($existenciaPiso_cantidad==0? 1: $existenciaPiso_cantidad)), 2, '', false),
      MyString::formatoNumero($existenciaPiso_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('EXISTENCIA'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero(($existenciaPiso_importe+$existencia_importe), 2, '', false)), false, 'B');


    // EXISTENCIA REPROCESO
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    // $pdf->Row(array('EXISTENCIA REPROCESO'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 30, 20, 18, 17, 17, 25, 22));
    // $pdf->Row(array('EXISTENCIA REPROCESO', 'CALIBRE', 'UNIDAD', 'KILOS', 'BULTOS', 'COSTO', 'IMPORTE'), true, 'B');
    $pdf->Row(array('EXISTENCIA REPROCESO', 'CALIBRE', 'UNIDAD', 'KILOS', 'BULTOS'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(60, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 20, 18, 17, 17, 25));

    $existenciaRePro_kilos = $existenciaRePro_cantidad = $existenciaRePro_importe = 0;
    $grupByUnidad = [];
    foreach ($caja['existencia_reproceso'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(60, 10);
        } else
          $pdf->AddPage();
      }

      $existenciaRePro_kilos    += floatval($existencia->kilos);
      $existenciaRePro_cantidad += floatval($existencia->cantidad);
      $existenciaRePro_importe  += floatval($existencia->importe);

      $pdf->SetX(60);
      $pdf->Row(array(
        $existencia->calibre,
        $existencia->unidad,
        MyString::formatoNumero($existencia->kilos, 2, '', false),
        MyString::formatoNumero($existencia->cantidad, 2, '', false),
        // MyString::formatoNumero($existencia->costo, 2, '', false),
        // MyString::formatoNumero($existencia->importe, 2, '', false),
      ), false, 'B');

      if (!isset($grupByUnidad[$existencia->unidad])) {
        $grupByUnidad[$existencia->unidad] = [
          'cantidad' => $existencia->cantidad * -1,
          'kilos'    => $existencia->kilos * -1,
          'unidad'   => $existencia->unidad,
          'costo'    => $existencia->costo,
          'importe'  => $calibre->importe * -1,
        ];
      } else {
        $grupByUnidad[$existencia->unidad]['cantidad'] += $existencia->cantidad * -1;
        $grupByUnidad[$existencia->unidad]['kilos']    += $existencia->kilos * -1;
        $grupByUnidad[$existencia->unidad]['importe']  += $existencia->importe * -1;
        $grupByUnidad[$existencia->unidad]['costo']  = $grupByUnidad[$existencia->unidad]['importe'] / ($grupByUnidad[$existencia->unidad]['cantidad'] > 0 ? $grupByUnidad[$existencia->unidad]['cantidad'] : 1);
      }
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($existenciaRePro_kilos, 2, '', false),
      MyString::formatoNumero($existenciaRePro_cantidad, 2, '', false),
      // MyString::formatoNumero(($existenciaRePro_importe/($existenciaRePro_cantidad==0? 1: $existenciaRePro_cantidad)), 2, '', false),
      // MyString::formatoNumero($existenciaRePro_importe, 2, '', false),
    ), false, 'B');
    // $pdf->SetAligns(array('R'));
    // $pdf->SetWidths(array(22));
    // $pdf->SetXY(188, $pdf->GetY()-11);
    // $pdf->Row(array('EXISTENCIA RE.'), false, 'B');
    // $pdf->SetXY(188, $pdf->GetY());
    // $pdf->Row(array(MyString::formatoNumero($existenciaRePro_importe, 2, '', false)), false, 'B');


    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(60, 30));
    $pdf->SetXY(75, $pdf->GetY()+5);
    $pdf->Row(array('RENDIMIENTO LIMON FRUTA:', MyString::formatoNumero($existenciaPiso_kilos+$existencia_kilos+$venta_kilos-$existenciaPisoAnt_kilos-$frutaCompra_kilos, 2, '', false)), false, 'B');


    // industrial
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->chkSaltaPag([6, $pdf->GetY()]);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    // $pdf->Row(array('INDUSTRIAL'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->chkSaltaPag([6, $pdf->GetY()]);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 46, 21, 18, 17, 25));
    $pdf->Row(array('INDUSTRIAL', 'CALIBRE', 'UNIDAD', 'KILOS', 'COSTO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(46, 20, 18, 17, 25));

    $industrial_importe = $industrial_kilos = 0;
    if (isset($caja['industrial']->costo)) {
      $industrial_importe = $caja['industrial']->importe;
      $industrial_kilos = $caja['industrial']->kilos;

      $pdf->SetFont('Arial','B', 7);
      $pdf->SetX(60);
      $pdf->Row(array(
        '', '',
        MyString::formatoNumero($caja['industrial']->kilos, 2, '', false),
        MyString::formatoNumero($caja['industrial']->costo, 2, '', false),
        MyString::formatoNumero($caja['industrial']->importe, 2, '', false),
      ), false, 'B');
    }
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-6);
    $pdf->Row(array('INDUSTRIAL'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero($industrial_importe, 2, '', false)), false, 'B');


    // Produccion o mano de obra
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+10);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    // $pdf->Row(array('COSTO DE PRODUCCIÓN'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 30, 20, 18, 17, 17, 25));
    $pdf->Row(array('MANO DE OBRA E INSUMOS', 'CALIBRE', 'UNIDAD', 'KILOS', 'BULTOS', 'COSTO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(60, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 20, 18, 17, 17, 25));

    $this->load->model('calibres_model');
    $produccionPercent = [];
    $produccion_kilos = $produccion_cantidad = $produccion_importe = 0;
    foreach ($caja['produccion'] as $produccion) {

      $produccion_kilos    += floatval($produccion->kilos);
      $produccion_cantidad += floatval($produccion->cantidad);
      $produccion_importe  += floatval($produccion->importe);

      if (!isset($produccionPercent[$produccion->id_calibre])) {
        $calibre = $this->calibres_model->getCalibre($produccion->id_calibre);
        $produccionPercent[$produccion->id_calibre] = [
          'cantidad' => $produccion->cantidad,
          'kilos'    => $produccion->kilos,
          'calibre'  => $produccion->calibre,
          'clasificacion' => $produccion->clasificacion,
          'order'  => $calibre->order,
          'grupo'  => $calibre->grupo,
        ];
      } else {
        $produccionPercent[$produccion->id_calibre]['cantidad'] += $produccion->cantidad;
        $produccionPercent[$produccion->id_calibre]['kilos'] += $produccion->kilos;
      }

      if (!isset($grupByUnidad[$produccion->unidad])) {
        $grupByUnidad[$produccion->unidad] = [
          'cantidad' => $produccion->cantidad,
          'kilos'    => $produccion->kilos,
          'unidad'   => $produccion->unidad,
          'costo'    => $produccion->costo,
          'importe'  => $calibre->importe,
        ];
      } else {
        $grupByUnidad[$produccion->unidad]['cantidad'] += $produccion->cantidad;
        $grupByUnidad[$produccion->unidad]['kilos']    += $produccion->kilos;
        $grupByUnidad[$produccion->unidad]['importe']  += $produccion->importe;
        $grupByUnidad[$produccion->unidad]['costo']  = $grupByUnidad[$produccion->unidad]['importe'] / ($grupByUnidad[$produccion->unidad]['cantidad'] > 0 ? $grupByUnidad[$produccion->unidad]['cantidad'] : 1);
      }
    }
    // Se imprime agrupado por unidad
    foreach ($grupByUnidad as $key => $produccion) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(60, 10);
        } else
          $pdf->AddPage();
      }

      $pdf->SetX(60);
      $pdf->Row(array(
        // $produccion->calibre,
        // $produccion->clasificacion,
        '',
        $produccion['unidad'],
        MyString::formatoNumero($produccion['kilos'], 2, '', false),
        MyString::formatoNumero($produccion['cantidad'], 2, '', false),
        MyString::formatoNumero($produccion['costo'], 2, '', false),
        MyString::formatoNumero($produccion['importe'], 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      // '',
      '',
      MyString::formatoNumero($produccion_kilos, 2, '', false),
      MyString::formatoNumero($produccion_cantidad, 2, '', false),
      MyString::formatoNumero(($produccion_importe/($produccion_cantidad==0? 1: $produccion_cantidad)), 2, '', false),
      MyString::formatoNumero($produccion_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('COSTO PROD'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero($produccion_importe, 2, '', false)), false, 'B');


    // COSTO DE VENTAS
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    // $pdf->Row(array('COSTO DE VENTAS'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R'));
    $pdf->SetWidths(array(54, 52, 50, 25));
    $pdf->Row(array('COSTO DE VENTAS', 'NOMBRE', 'DESCRIPCION', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(60, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(52, 50, 25));

    $costoVentas_importe = 0;
    foreach ($caja['costo_ventas'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(60, 10);
        } else
          $pdf->AddPage();
      }

      $costoVentas_importe += floatval($existencia->importe);

      $pdf->SetX(60);
      $pdf->Row(array(
        $existencia->nombre,
        $existencia->descripcion,
        MyString::formatoNumero($existencia->importe, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($costoVentas_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('COSTO VENTAS'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero($costoVentas_importe, 2, '', false)), false, 'B');


    // FLETES
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+1);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    // $pdf->Row(array('FLETES'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'L', 'R', 'R'));
    $pdf->SetWidths(array(54, 15, 15, 57, 17, 23));
    $pdf->Row(array('FLETE CONTRATADO', 'FOLIO', 'REM/FAC', 'PROVEEDOR', 'CANTIDAD', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(60, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'L', 'L', 'R', 'R'));
    $pdf->SetWidths(array(15, 15, 57, 17, 23));

    $descuentoVentasFletes_cantidad = $descuentoVentasFletes_importe = 0;
    foreach ($caja['costo_ventas_fletes'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY) {
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(60, 10);
        } else
          $pdf->AddPage();
      }

      $descuentoVentasFletes_cantidad += floatval($existencia->cantidad);
      $descuentoVentasFletes_importe += floatval($existencia->importe);

      $pdf->SetX(60);
      $pdf->Row(array(
        $existencia->folio,
        $existencia->facturas,
        $existencia->proveedor,
        MyString::formatoNumero($existencia->cantidad, 2, '', false),
        MyString::formatoNumero($existencia->importe, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($descuentoVentasFletes_cantidad, 2, '', false),
      MyString::formatoNumero($descuentoVentasFletes_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('FLETES'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero($descuentoVentasFletes_importe, 2, '', false)), false, 'B');


    // Certificados
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(182));
    $pdf->Row(array('CERTIFICADOS'), true, 'B');

    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(22, 60, 40, 15, 20, 25));
    $pdf->Row(array('FOLIO', 'PROVEEDORES', 'TIPO', 'CANTIDAD', 'PRECIO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(22, 60, 40, 15, 20, 25));

    $cert_importe = $cert_kilos = $cert_cantidad = 0;
    foreach ($caja['certificados'] as $venta) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $cert_importe += floatval($venta->importe);
      $cert_cantidad += floatval($venta->cantidad);

      $pdf->SetX(6);
      $pdf->Row(array(
        $venta->serie.$venta->folio,
        $venta->proveedores,
        $venta->clasificacion,
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
      MyString::formatoNumero($cert_cantidad, 2, '', false),
      MyString::formatoNumero(($cert_importe/($cert_cantidad==0? 1: $cert_cantidad)), 2, '', false),
      MyString::formatoNumero($cert_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('CERTIFICADOS'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero($cert_importe, 2, '$', false)), false, 'B');


    // COMISIONES A TERCEROS
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    // $pdf->Row(array('COMISIONES A TERCEROS'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 35, 38, 17, 17, 20));
    $pdf->Row(array('COMISIONES A TERCEROS', 'NOMBRE', 'DESCRIPCION', 'CANTIDAD', 'COSTO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(60, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(35, 38, 17, 17, 20));

    $comisionTerceros_cantidad = $comisionTerceros_importe = 0;
    foreach ($caja['comision_terceros'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(60, 10);
        } else
          $pdf->AddPage();
      }

      $comisionTerceros_cantidad  += floatval($existencia->cantidad);
      $comisionTerceros_importe  += floatval($existencia->importe);

      $pdf->SetX(60);
      $pdf->Row(array(
        $existencia->nombre,
        $existencia->descripcion,
        MyString::formatoNumero($existencia->cantidad, 2, '', false),
        MyString::formatoNumero($existencia->costo, 2, '', false),
        MyString::formatoNumero($existencia->importe, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($comisionTerceros_cantidad, 2, '', false),
      MyString::formatoNumero(($comisionTerceros_importe/($comisionTerceros_cantidad>0? $comisionTerceros_cantidad: 1)), 2, '', false),
      MyString::formatoNumero($comisionTerceros_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('COMISIONES'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero($comisionTerceros_importe, 2, '', false)), false, 'B');


    // Ventas industrial
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->chkSaltaPag([6, $pdf->GetY()]);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(182));
    $pdf->Row(array('VENTAS INDUSTRIAL'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->chkSaltaPag([6, $pdf->GetY()]);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(18, 18, 35, 24, 16, 18, 18, 12, 20));
    $pdf->Row(array('FOLIO', 'SF', 'CLIENTE', 'CALIBRE PROD.', 'UNIDAD', 'KILOS', 'CANTIDAD', 'PRECIO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(18, 18, 35, 24, 16, 18, 18, 12, 20));

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
        // $venta->clasificacion,
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
      MyString::formatoNumero($venta_kilos_ind, 2, '', false),
      MyString::formatoNumero($venta_cantidad_ind, 2, '', false),
      MyString::formatoNumero(($venta_importe_ind/($venta_cantidad_ind==0? 1: $venta_cantidad_ind)), 2, '', false),
      MyString::formatoNumero($venta_importe_ind, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('V. INDUSTRIAL'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero($venta_importe_ind, 2, '', false)), false, 'B');


    // GASTOS GENERALES
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    // $pdf->Row(array('GASTOS GENERALES'), true, 'B');

    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R'));
    $pdf->SetWidths(array(54, 50, 52, 25));
    $pdf->Row(array('GASTOS GENERALES', 'NOMBRE', 'CONCEPTO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(60, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(50, 52, 25));

    $totalGastos = 0;
    foreach ($caja['gastos'] as $gasto) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(60, 10);
        } else
          $pdf->AddPage();
      }

      $totalGastos  += floatval($gasto->monto);

      $pdf->SetX(60);
      $pdf->Row(array(
        $gasto->nombre,
        $gasto->concepto,
        MyString::formatoNumero($gasto->monto, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($totalGastos, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('GASTOS'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero($totalGastos, 2, '', false)), false, 'B');


    // TOTALES
    $resultado_importe = $venta_importe - ($compra_fruta_importe + $existencia_ant_importe - $existencia_importe) - $produccion_importe - $frutaCompra_importe - $devFruta_importe - ($costoVentas_importe + $descuentoVentasFletes_importe + $cert_importe + $comisionTerceros_importe) - $totalGastos + $industrial_importe;
    $resultado_kilos = $existencia_ant_kilos + $compra_fruta_kilos - $existencia_kilos - $venta_kilos + $frutaCompra_kilos + $devFruta_kilos;
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array('ESTADO DE RESULTADO'), true, 'B');

    $pdf->SetXY(6, $pdf->GetY()+0.3);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(60, 30));

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
    $pdf->Row(array('(=) KGS PROCESADOS', MyString::formatoNumero($existencia_ant_kilos + $compra_fruta_kilos - $existencia_kilos, 2, '', false)), true, 'B');

    ($pdf->GetY()+10 >= $pdf->limiteY)? $pdf->AddPage(): '';
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('(+) KGS COMPRADOS EMPACADOS', MyString::formatoNumero($frutaCompra_kilos, 2, '', false)), true, 'B');

    ($pdf->GetY()+10 >= $pdf->limiteY)? $pdf->AddPage(): '';
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('(+) KGS DEVOLUCION DE FRUTA', MyString::formatoNumero($devFruta_kilos, 2, '', false)), true, 'B');

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
    $pdf->SetWidths(array(60, 30));

    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(+) VENTAS', MyString::formatoNumero($venta_importe, 2, '', false)), true, 'B');

    $pdf->SetFont('Arial', '', 8);
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(+) EXISTENCIA ANTERIOR', MyString::formatoNumero($existencia_ant_importe, 2, '', false)), true, false);
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(+) COMPRA DE FRUTA', MyString::formatoNumero($compra_fruta_importe, 2, '', false)), true, false);
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(-) EXISTENCIA EMPACADA', MyString::formatoNumero($existencia_importe, 2, '', false)), true, false);
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(-) COSTO DE MATERIA PRIMA', MyString::formatoNumero($compra_fruta_importe + $existencia_ant_importe - $existencia_importe, 2, '', false)), true, 'B');

    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(-) COSTO DE PRODUCCION', MyString::formatoNumero($produccion_importe, 2, '', false)), true, 'B');

    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(-) COMPRA DE FRUTA EMPACADA', MyString::formatoNumero($frutaCompra_importe, 2, '', false)), true, 'B');

    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(-) DEVOLUCION DE FRUTA', MyString::formatoNumero($devFruta_importe, 2, '', false)), true, 'B');

    $pdf->SetFont('Arial', '', 8);
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(+) COSTO DE VENTAS', MyString::formatoNumero($costoVentas_importe, 2, '', false)), true, false);
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(+) FLETES', MyString::formatoNumero($descuentoVentasFletes_importe, 2, '', false)), true, false);
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(+) CERTIFICADOS', MyString::formatoNumero($cert_importe, 2, '', false)), true, false);
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(+) COMISION A TERCEROS', MyString::formatoNumero($comisionTerceros_importe, 2, '', false)), true, false);
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(-) GASTOS DE VENTAS', MyString::formatoNumero($costoVentas_importe + $descuentoVentasFletes_importe + $cert_importe + $comisionTerceros_importe, 2, '', false)), true, 'B');
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(-) GASTOS GENERALES', MyString::formatoNumero($totalGastos, 2, '', false)), true, 'B');
    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(+) INDUSTRIAL', MyString::formatoNumero($industrial_importe, 2, '', false)), true, 'B');

    $pdf->chkSaltaPag([120, 10]);
    $pdf->SetFont('Arial', 'B', 9);
    $pdf->SetXY(120, $pdf->GetY());
    $pdf->Row(array('(=) RESULTADO DEL DIA', MyString::formatoNumero($resultado_importe, 2, '', false)), true, 'B');

    $pdf->page = count($pdf->pages);
    $pdf->SetXY(6, $pdf->GetY()+5);

    $pagaux = $pdf->page;
    $yaux = $pdf->GetY();
    $keyValuesProduc = array_column($produccionPercent, 'order');
    $kilosTotaless = $produccion_kilos; //  + $industrial_kilos
    array_multisort($keyValuesProduc, SORT_DESC, $produccionPercent);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(90));
    $pdf->Row(array('Rendimiento'), true, true);
    $pdf->SetAligns(array('L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(20, 25, 30, 15));
    $pdf->SetXY(6, $pdf->GetY());
    $totalesRendimientos = [];
    $pdf->Row(array('Calibre', 'Bultos', 'Kilos', '%'), true, true);
    foreach ($produccionPercent as $key => $value) {
      $pdf->chkSaltaPag([6, 10]);
      $pdf->SetFont('Arial', 'B', 9);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array(
        $value['calibre'],
        MyString::formatoNumero($value['cantidad'], 2, '', false),
        MyString::formatoNumero($value['kilos'], 2, '', false),
        MyString::formatoNumero(($value['kilos']*100/($kilosTotaless>0? $kilosTotaless: 1)), 2, '', false),
      ), true, 'B');

      if (isset($totalesRendimientos[$value['grupo']])) {
        $totalesRendimientos[$value['grupo']] += $value['kilos'];
      } else {
        $totalesRendimientos[$value['grupo']] = $value['kilos'];
      }
    }

    $pdf->page = $pagaux;
    $pdf->SetXY(100, $yaux);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(35));
    $pdf->Row(array('Rendimiento Totales'), true, true);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(20, 15));
    $pdf->SetXY(100, $pdf->GetY());
    $pdf->Row(array('Calibre', '%'), true, true);
    foreach ($totalesRendimientos as $key => $value) {
      $pdf->chkSaltaPag([100, 10]);
      $pdf->SetFont('Arial', 'B', 9);
      $pdf->SetXY(100, $pdf->GetY());
      $pdf->Row(array(
        $key,
        MyString::formatoNumero(($value*100/($kilosTotaless>0? $kilosTotaless: 1)), 2, '', false),
      ), true, 'B');
    }

    $pdf->Output('REPORTE_EXISTENCIAS.pdf', 'I');

  }

  public function printCaja2($fecha, $noCajas, $id_area)
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
    $pdf->titulo2 = "REPORTES RENDIMIENTOS DE LIMON";
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
    $pdf->Row(array($pdf->titulo2), true, true, null, 3);

    $pdf->Image(APPPATH.(str_replace(APPPATH, '', '/images/logo.png')), 6, 15, 50);
    $pdf->Ln(20);

    $pdf->SetFont('Arial','B', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);

    // Fecha
    $pdf->SetXY(6, $pdf->GetY() - 20);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(204));
    $pdf->Row(array(MyString::dia($fecha) . ': ' . MyString::fechaATexto($fecha, '/c')), false, false);

    $pdf->SetXY(6, $pdf->GetY());
    $fechaTime = new DateTime($fecha);
    // Obtiene la semana [01 - 52/53] y el dia de la semana [1 - 7]
    $pdf->Row(array('SEMANA ' . $fechaTime->format("W")), false, false);

    $pdf->auxy = $pdf->GetY();
    $page_aux = $pdf->page;


    // Existencia Anterior
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, 32);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 30, 20, 20, 18, 18, 22));
    $pdf->Row(array('EXISTENCIA ANTERIOR', 'CALIBRE', 'UNIDAD', 'KILOS', 'CANTIDAD', 'COSTO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 20, 20, 18, 18, 22));

    $existencia_ant_kilos = $existencia_ant_cantidad = $existencia_ant_importe = 0;
    foreach ($caja['existencia_anterior'] as $existencia_ant) {
      if ($existencia_ant->unidad !== 'CPV28') {
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

        $pdf->SetX(60);
        $pdf->Row(array(
          $existencia_ant->calibre,
          // $existencia_ant->clasificacion,
          $existencia_ant->unidad,
          MyString::formatoNumero($existencia_ant->kilos, 2, '', false),
          MyString::formatoNumero($existencia_ant->cantidad, 2, '', false),
          MyString::formatoNumero($existencia_ant->costo, 2, '', false),
          MyString::formatoNumero($existencia_ant->importe, 2, '', false),
        ), false, 'B');
      }
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(182));
    $pdf->Row(array('EXIST. PISO ANTERIOR'), false, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 20, 20, 18, 18, 22));
    foreach ($caja['existencia_anterior'] as $existencia_ant) {
      if ($existencia_ant->unidad == 'CPV28') {
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

        $pdf->SetX(60);
        $pdf->Row(array(
          $existencia_ant->calibre,
          // $existencia_ant->clasificacion,
          $existencia_ant->unidad,
          MyString::formatoNumero($existencia_ant->kilos, 2, '', false),
          MyString::formatoNumero($existencia_ant->cantidad, 2, '', false),
          MyString::formatoNumero($existencia_ant->costo, 2, '', false),
          MyString::formatoNumero($existencia_ant->importe, 2, '', false),
        ), false, 'B');
      }
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($existencia_ant_kilos, 2, '', false),
      MyString::formatoNumero($existencia_ant_cantidad, 2, '', false),
      MyString::formatoNumero(($existencia_ant_importe/($existencia_ant_cantidad==0? 1: $existencia_ant_cantidad)), 2, '', false),
      MyString::formatoNumero($existencia_ant_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('EXIST ANT'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero($existencia_ant_importe, 2, '', false)), false, 'B');


    // MATERIA PRIMA DE LIMON
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 38, 20, 20, 20, 30));
    $pdf->Row(array('MATERIA PRIMA:', 'CALIBRE', 'KILOS', 'CAJAS', 'PRECIO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(38, 20, 20, 20, 30));

    $compra_fruta_kilos = $compra_fruta_cajas = $compra_fruta_importe = 0;
    foreach ($caja['compra_fruta'] as $com_fruta) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $compra_fruta_kilos += floatval($com_fruta->kilos);
      $compra_fruta_cajas += floatval(0);
      $compra_fruta_importe += floatval($com_fruta->importe);

      $pdf->SetX(60);
      $pdf->Row(array(
        $com_fruta->calidad,
        MyString::formatoNumero($com_fruta->kilos, 2, '', false),
        MyString::formatoNumero('0', 2, '', false),
        MyString::formatoNumero($com_fruta->precio, 2, '', false),
        MyString::formatoNumero($com_fruta->importe, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      MyString::formatoNumero($compra_fruta_kilos, 2, '', false),
      MyString::formatoNumero($compra_fruta_cajas, 2, '', false),
      MyString::formatoNumero(($compra_fruta_importe/($compra_fruta_kilos==0? 1: $compra_fruta_kilos)), 2, '', false),
      MyString::formatoNumero($compra_fruta_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('COMPRAS'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero($compra_fruta_importe, 2, '', false)), false, 'B');


    // Compra de fruta empacada
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 30, 20, 18, 18, 18, 24));
    $pdf->Row(array('COMPRA EMPACADA', 'CALIBRE', 'UNIDAD', 'KILOS', 'CANTIDAD', 'COSTO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 20, 18, 18, 18, 24));

    $frutaCompra_kilos = $frutaCompra_cantidad = $frutaCompra_importe = 0;
    foreach ($caja['compra_fruta_empacada'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $frutaCompra_kilos    += floatval($existencia->kilos);
      $frutaCompra_cantidad += floatval($existencia->cantidad);
      $frutaCompra_importe  += floatval($existencia->importe);

      $pdf->SetX(60);
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
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($frutaCompra_kilos, 2, '', false),
      MyString::formatoNumero($frutaCompra_cantidad, 2, '', false),
      MyString::formatoNumero(($frutaCompra_importe/($frutaCompra_cantidad==0? 1: $frutaCompra_cantidad)), 2, '', false),
      MyString::formatoNumero($frutaCompra_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('COMPRAS'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero($frutaCompra_importe, 2, '', false)), false, 'B');


    // Devolucion de fruta
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 30, 20, 18, 18, 18, 24));
    $pdf->Row(array('DEVOLUCIÓN DE FRUTA', 'CALIBRE', 'UNIDAD', 'KILOS', 'CANTIDAD', 'COSTO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 20, 18, 18, 18, 24));

    $devFruta_kilos = $devFruta_cantidad = $devFruta_importe = 0;
    foreach ($caja['devolucion_fruta'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $devFruta_kilos    += floatval($existencia->kilos);
      $devFruta_cantidad += floatval($existencia->cantidad);
      $devFruta_importe  += floatval($existencia->importe);

      $pdf->SetX(60);
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
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($devFruta_kilos, 2, '', false),
      MyString::formatoNumero($devFruta_cantidad, 2, '', false),
      MyString::formatoNumero(($devFruta_importe/($devFruta_cantidad==0? 1: $devFruta_cantidad)), 2, '', false),
      MyString::formatoNumero($devFruta_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('DEVOLUCIÓN'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero($devFruta_importe, 2, '', false)), false, 'B');


    // Ventas
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(182));
    $pdf->Row(array('VENTAS'), true, 'B');

    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(18, 18, 35, 25, 16, 18, 18, 12, 20, 22));
    $pdf->Row(array('FOLIO', 'SF', 'CLIENTE', 'CALIBRE PROD.', 'UNIDAD', 'KILOS', 'BULTOS', 'PRECIO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'L', 'L', 'C', 'R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(20, 18, 35, 25, 16, 18, 18, 12, 20, 22));

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
        // $venta->clasificacion,
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
      // '',
      '',
      '',
      '',
      MyString::formatoNumero($venta_kilos, 2, '', false),
      MyString::formatoNumero($venta_cantidad, 2, '', false),
      MyString::formatoNumero(($venta_importe/($venta_cantidad==0? 1: $venta_cantidad)), 2, '', false),
      MyString::formatoNumero($venta_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('VENTAS'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero($venta_importe, 2, '', false)), false, 'B');


    // Existencia
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 30, 20, 18, 17, 17, 25, 22));
    $pdf->Row(array('EXISTENCIA EMPACADA', 'CALIBRE', 'UNIDAD', 'KILOS', 'BULTOS', 'COSTO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(60, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 20, 18, 17, 17, 25, 22));

    $existencia_kilos = $existencia_cantidad = $existencia_importe = 0;
    foreach ($caja['existencia'] as $existencia) {
      if ($existencia->unidad !== 'CPV28') {
        if($pdf->GetY() >= $pdf->limiteY){
          if (count($pdf->pages) > $pdf->page) {
            $pdf->page++;
            $pdf->SetXY(60, 10);
          } else
            $pdf->AddPage();
        }

        $existencia_kilos    += floatval($existencia->kilos);
        $existencia_cantidad += floatval($existencia->cantidad);
        $existencia_importe  += floatval($existencia->importe);

        if ($existencia->cantidad != 0 ) {
          $pdf->SetX(60);
          $pdf->Row(array(
            $existencia->calibre,
            // $existencia->clasificacion,
            $existencia->unidad,
            MyString::formatoNumero($existencia->kilos, 2, '', false),
            MyString::formatoNumero($existencia->cantidad, 2, '', false),
            MyString::formatoNumero($existencia->costo, 2, '', false),
            MyString::formatoNumero($existencia->importe, 2, '', false),
          ), false, 'B');
        }
      }
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(182));
    $pdf->Row(array('EXISTENCIA EN PISO'), false, 'B');
    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(60, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 20, 18, 17, 17, 25, 22));
    foreach ($caja['existencia'] as $existencia) {
      if ($existencia->unidad == 'CPV28') {
        if($pdf->GetY() >= $pdf->limiteY){
          if (count($pdf->pages) > $pdf->page) {
            $pdf->page++;
            $pdf->SetXY(60, 10);
          } else
            $pdf->AddPage();
        }

        $existencia_kilos    += floatval($existencia->kilos);
        $existencia_cantidad += floatval($existencia->cantidad);
        $existencia_importe  += floatval($existencia->importe);

        if ($existencia->cantidad != 0 ) {
          $pdf->SetX(60);
          $pdf->Row(array(
            $existencia->calibre,
            // $existencia->clasificacion,
            $existencia->unidad,
            MyString::formatoNumero($existencia->kilos, 2, '', false),
            MyString::formatoNumero($existencia->cantidad, 2, '', false),
            MyString::formatoNumero($existencia->costo, 2, '', false),
            MyString::formatoNumero($existencia->importe, 2, '', false),
          ), false, 'B');
        }
      }
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($existencia_kilos, 2, '', false),
      MyString::formatoNumero($existencia_cantidad, 2, '', false),
      MyString::formatoNumero(($existencia_importe/($existencia_cantidad==0? 1: $existencia_cantidad)), 2, '', false),
      MyString::formatoNumero($existencia_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('EXISTENCIA E.'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero($existencia_importe, 2, '', false)), false, 'B');


    // RENDIMIENTO DE FRUTA
    $pdf->SetFont('Arial','B', 8);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(84, 40));
    $tRendimientoFrutaKg = $existencia_kilos + $venta_kilos - $existencia_ant_kilos - $frutaCompra_kilos;
    $tIndustrialPKg = $compra_fruta_kilos + $devFruta_kilos - $tRendimientoFrutaKg;
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->Row(array('RENDIMIENTO DE FRUTA', MyString::formatoNumero($tRendimientoFrutaKg, 2, '', false)." Kg"), false, 'B');

    // INDUSTRIAL DEL PROCESO
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetAligns(array('L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(84, 40, 30, 50));
    $pdf->SetXY(6, $pdf->GetY());
    $precioIndustrial = (count($caja['ventas_industrial']) > 0 ? $caja['ventas_industrial'][0]->precio : 0);
    $industrial_importe = $tIndustrialPKg * $precioIndustrial;
    $pdf->Row(array(
      'INDUSTRIAL DEL PROCESO',
      MyString::formatoNumero($tIndustrialPKg, 2, '', false)." Kg",
      MyString::formatoNumero($precioIndustrial, 2, '', false),
      MyString::formatoNumero($industrial_importe, 2, '$', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('INDUSTRIAL'), false, 'B');
    $pdf->SetXY(6, $pdf->GetY()+11);


    // MANO DE OBRA E INSUMOS
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 44, 15, 16, 16, 15, 22));
    $pdf->Row(array('MANO DE OBRA E INSUMOS', 'DESCRIPCION', 'UNIDAD', 'KILOS', 'CANTIDAD', 'COSTO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 6);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(44, 15, 16, 16, 15, 22));

    $manooInsumos_kilos = $manooInsumos_cantidad = $manooInsumos_importe = 0;
    foreach ($caja['manooInsumos'] as $manoinsumos) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $manooInsumos_kilos    += floatval($manoinsumos->kilos);
      $manooInsumos_cantidad += floatval($manoinsumos->cantidad);
      $manooInsumos_importe  += floatval($manoinsumos->importe);

      $pdf->SetX(60);
      $pdf->Row(array(
        $manoinsumos->descripcion,
        $manoinsumos->unidad,
        MyString::formatoNumero($manoinsumos->kilos, 2, '', false),
        MyString::formatoNumero($manoinsumos->cantidad, 2, '', false),
        MyString::formatoNumero($manoinsumos->costo, 2, '', false),
        MyString::formatoNumero($manoinsumos->importe, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($manooInsumos_kilos, 2, '', false),
      MyString::formatoNumero($manooInsumos_cantidad, 2, '', false),
      MyString::formatoNumero(($manooInsumos_importe/($manooInsumos_cantidad==0? 1: $manooInsumos_cantidad)), 2, '', false),
      MyString::formatoNumero($manooInsumos_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('CTO PROD'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero($manooInsumos_importe, 2, '$', false)), false, 'B');

    // FLETES CONTRATADOs
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 50, 20, 17, 17, 23));
    $pdf->Row(array('FLETE CONTRATADO', 'PROVEEDOR', 'FOLIO', 'CANTIDAD', 'PRECIO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(60, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(50, 20, 17, 17, 23));

    $descuentoVentasFletes_cantidad = $descuentoVentasFletes_importe = 0;
    foreach ($caja['costo_ventas_fletes'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY) {
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(60, 10);
        } else
          $pdf->AddPage();
      }

      $descuentoVentasFletes_cantidad += floatval($existencia->cantidad);
      $descuentoVentasFletes_importe += floatval($existencia->importe);

      $pdf->SetX(60);
      $pdf->Row(array(
        $existencia->proveedor,
        $existencia->folio,
        MyString::formatoNumero($existencia->cantidad, 2, '', false),
        MyString::formatoNumero($existencia->importe / ($existencia->cantidad != 0? $existencia->cantidad: 1), 2, '', false),
        MyString::formatoNumero($existencia->importe, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($descuentoVentasFletes_cantidad, 2, '', false),
      MyString::formatoNumero($descuentoVentasFletes_importe / ($descuentoVentasFletes_cantidad != 0? $descuentoVentasFletes_cantidad: 1), 2, '', false),
      MyString::formatoNumero($descuentoVentasFletes_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('FLETES'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero($descuentoVentasFletes_importe, 2, '$', false)), false, 'B');


    // COMISIONES A TERCEROS
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(204));
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(54, 35, 38, 17, 17, 20));
    $pdf->Row(array('COMISIONES A TERCEROS', 'NOMBRE', 'DESCRIPCION', 'CANTIDAD', 'COSTO', 'IMPORTE'), true, 'B');

    $pdf->SetFont('Arial','', 7);
    $pdf->SetXY(60, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(35, 38, 17, 17, 20));

    $comisionTerceros_cantidad = $comisionTerceros_importe = 0;
    foreach ($caja['comision_terceros'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(60, 10);
        } else
          $pdf->AddPage();
      }

      $comisionTerceros_cantidad  += floatval($existencia->cantidad);
      $comisionTerceros_importe  += floatval($existencia->importe);

      $pdf->SetX(60);
      $pdf->Row(array(
        $existencia->nombre,
        $existencia->descripcion,
        MyString::formatoNumero($existencia->cantidad, 2, '', false),
        MyString::formatoNumero($existencia->costo, 2, '', false),
        MyString::formatoNumero($existencia->importe, 2, '', false),
      ), false, 'B');
    }

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetX(60);
    $pdf->Row(array(
      '',
      '',
      MyString::formatoNumero($comisionTerceros_cantidad, 2, '', false),
      MyString::formatoNumero(($comisionTerceros_importe/($comisionTerceros_cantidad>0? $comisionTerceros_cantidad: 1)), 2, '', false),
      MyString::formatoNumero($comisionTerceros_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('COMISIONES'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero($comisionTerceros_importe, 2, '$', false)), false, 'B');


    // Certificados
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(182));
    $pdf->Row(array('CERTIFICADOS'), true, 'B');

    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(22, 60, 40, 15, 20, 25));
    $pdf->Row(array('FOLIO', 'PROVEEDORES', 'TIPO', 'CANTIDAD', 'PRECIO', 'IMPORTE'), FALSE, FALSE);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(22, 60, 40, 15, 20, 25));

    $cert_importe = $cert_kilos = $cert_cantidad = 0;
    foreach ($caja['certificados'] as $venta) {
      if($pdf->GetY() >= $pdf->limiteY){
        if (count($pdf->pages) > $pdf->page) {
          $pdf->page++;
          $pdf->SetXY(6, 10);
        } else
          $pdf->AddPage();
      }

      $cert_importe += floatval($venta->importe);
      $cert_cantidad += floatval($venta->cantidad);

      $pdf->SetX(6);
      $pdf->Row(array(
        $venta->serie.$venta->folio,
        $venta->proveedores,
        $venta->clasificacion,
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
      MyString::formatoNumero($cert_cantidad, 2, '', false),
      MyString::formatoNumero(($cert_importe/($cert_cantidad==0? 1: $cert_cantidad)), 2, '', false),
      MyString::formatoNumero($cert_importe, 2, '', false),
    ), false, 'B');
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(22));
    $pdf->SetXY(188, $pdf->GetY()-11);
    $pdf->Row(array('CERTIFICADOS'), false, 'B');
    $pdf->SetXY(188, $pdf->GetY());
    $pdf->Row(array(MyString::formatoNumero($cert_importe, 2, '$', false)), false, 'B');

    $resultadoto = $devFruta_importe + $venta_importe + $existencia_importe + $industrial_importe
      - $existencia_ant_importe - $compra_fruta_importe - $frutaCompra_importe - $manooInsumos_importe
      - $descuentoVentasFletes_importe - $comisionTerceros_importe - $cert_importe;
    $pdf->SetFont('Arial','B', 8);
    $pdf->SetAligns(array('C', 'R'));
    $pdf->SetWidths(array(25, 25));
    $pdf->SetXY(160, $pdf->GetY()+5);
    $pdf->Row(array('RESULTADO', MyString::formatoNumero($resultadoto, 2, '$', false)), true, false);





    $pdf->page = count($pdf->pages);
    $pdf->Output('REPORTE_EXISTENCIAS.pdf', 'I');

  }




}

/* End of file caja_chica_model.php */
/* Location: ./application/models/caja_chica_model.php */