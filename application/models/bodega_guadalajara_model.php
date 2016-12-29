<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class bodega_guadalajara_model extends CI_Model {

  public function get($fecha, $noCaja)
  {
    $info = array(
      'cts_cobrar'     => $this->bodega_guadalajara_model->getAbonosVentasPasadas($fecha),
      'ingresos'       => array(),
      'remisiones'     => $this->bodega_guadalajara_model->getRemisiones($fecha),
      'ventas'         => $this->bodega_guadalajara_model->getVentas($fecha),
      'prestamos'      => $this->bodega_guadalajara_model->getPrestamos($fecha),
      'existencia_ant' => array(),
      'existencia_dia' => array(),
      'denominaciones' => array(),
      'gastos'         => array(),
      'categorias'     => array(),
    );

    $ingresos = $this->db->query(
      "SELECT ci.*, cc.abreviatura as categoria, cn.nomenclatura
       FROM otros.bodega_ingresos ci
       INNER JOIN cajachica_categorias cc ON cc.id_categoria = ci.id_categoria
       INNER JOIN cajachica_nomenclaturas cn ON cn.id = ci.id_nomenclatura
       WHERE ci.fecha = '{$fecha}' AND ci.otro = 'f' AND ci.no_caja = {$noCaja}
       ORDER BY ci.id_ingresos ASC"
    );

    if ($ingresos->num_rows() > 0)
    {
      $info['ingresos'] = $ingresos->result();
    }

    // Exsistencia anterior
    $info['existencia_ant'] = $this->db->query(
      "SELECT f.id_factura, e.nombre_fiscal, DATE(f.fecha) as fecha, f.serie, f.folio, total, c.nombre_fiscal as cliente,
        be.descripcion, be.cantidad, be.precio_unitario, be.importe, u.nombre AS unidad, cl.id_clasificacion,
        (cl.codigo || '-' || u.codigo) AS codigo, u.cantidad AS cantidadu, u.id_unidad
      FROM otros.bodega_existencia be
        INNER JOIN facturacion f ON be.id_factura = f.id_factura
        INNER JOIN clientes c ON c.id_cliente = f.id_cliente
        INNER JOIN empresas e ON e.id_empresa = f.id_empresa
        INNER JOIN unidades u ON u.id_unidad = be.id_unidad
        INNER JOIN clasificaciones cl ON cl.id_clasificacion = be.id_clasificacion
      WHERE Date(be.fecha) = Date(date '{$fecha}' - interval '1 day')
      ORDER BY (f.fecha, f.serie, f.folio, be.descripcion) ASC"
    )->result();

    // Agregamos la existencia anterior
    foreach ($info['existencia_ant'] as $key => $value) {
      if ($value->id_clasificacion != '49' AND $value->id_clasificacion != '50' AND
          $value->id_clasificacion != '51' AND $value->id_clasificacion != '52' AND
          $value->id_clasificacion != '53')
      {
        $info['existencia_dia'][$value->id_factura.'-'.$value->id_clasificacion.'-'.$value->id_unidad.'-'.$key] = clone $value;
      }
    }

    // Agregamos los ingresos del dia
    foreach ($info['remisiones'] as $key => $value) {
      if ($value->id_clasificacion != '49' AND $value->id_clasificacion != '50' AND
          $value->id_clasificacion != '51' AND $value->id_clasificacion != '52' AND
          $value->id_clasificacion != '53')
      {
        $info['existencia_dia'][$value->id_factura.'-'.$value->id_clasificacion.'-'.$value->id_unidad.'-'.$key] = clone $value;
      }
    }

    // sumamos o restamos los prestamos de ese dia
    foreach ($info['prestamos'] as $key => $value) {
      if ($value->id_clasificacion != '49' AND $value->id_clasificacion != '50' AND
          $value->id_clasificacion != '51' AND $value->id_clasificacion != '52' AND
          $value->id_clasificacion != '53' AND $value->tipo == 't')
      {
        $info['existencia_dia'][$value->id_factura.'-'.$value->id_clasificacion.'-'.$value->id_unidad.'-'.$key] = clone $value;
      }
    }
    foreach ($info['prestamos'] as $key => $value) {
      if ($value->tipo == 'f')
      {
        $keys = preg_grep( '/^[0-9]+-'.$value->id_clasificacion.'-'.$value->id_unidad.'-[0-9]+/i', array_keys( $info['existencia_dia'] ) );
        $cantidad = $value->cantidad;
        foreach ($keys as $k) {
          if ($info['existencia_dia'][$k]->cantidad < $cantidad) {
            $cantidad -= $info['existencia_dia'][$k]->cantidad;
            unset($info['existencia_dia'][$k]);
          } else {
            $info['existencia_dia'][$k]->cantidad -= $cantidad;
            $info['existencia_dia'][$k]->importe -= $cantidad*$info['existencia_dia'][$k]->precio_unitario;
            $cantidad = 0;

            if ($info['existencia_dia'][$k]->cantidad === 0)
              unset($info['existencia_dia'][$k]);

            break;
          }
        }
        if ($cantidad > 0) {
          $info['existencia_dia'][$key]           = clone $value;
          $info['existencia_dia'][$key]->cantidad = $cantidad*-1;
          $info['existencia_dia'][$key]->importe  = $info['existencia_dia'][$key]->cantidad*$info['existencia_dia'][$key]->precio_unitario;
        }
      }
    }

    // restamos las ventas del dia
    foreach ($info['ventas'] as $key => $value) {
      $keys = preg_grep( '/^[0-9]+-'.$value->id_clasificacion.'-'.$value->id_unidad.'-[0-9]+/i', array_keys( $info['existencia_dia'] ) );
      $cantidad = $value->cantidad;
      foreach ($keys as $k) {
        if ($info['existencia_dia'][$k]->cantidad < $cantidad) {
          $cantidad -= $info['existencia_dia'][$k]->cantidad;
          unset($info['existencia_dia'][$k]);
        } else {
          $info['existencia_dia'][$k]->cantidad -= $cantidad;
          $info['existencia_dia'][$k]->importe -= $cantidad*$info['existencia_dia'][$k]->precio_unitario;
          $cantidad = 0;

          if ($info['existencia_dia'][$k]->cantidad === 0)
            unset($info['existencia_dia'][$k]);

          break;
        }
      }
      if ($cantidad > 0) {
        $info['existencia_dia'][$key]           = clone $value;
        $info['existencia_dia'][$key]->cantidad = $cantidad*-1;
        $info['existencia_dia'][$key]->importe  = $info['existencia_dia'][$key]->cantidad*$info['existencia_dia'][$key]->precio_unitario;
      }
    }

    // denominaciones
    $denominaciones = $this->db->query(
      "SELECT *
       FROM otros.bodega_efectivo
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
          cg.folio, cg.id_nomenclatura, cn.nomenclatura, ca.id_area, ca.nombre AS nombre_codigo, ca.codigo_fin,
          'id_area' AS campo
       FROM otros.bodega_gastos cg
         INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
         INNER JOIN cajachica_nomenclaturas cn ON cn.id = cg.id_nomenclatura
         LEFT JOIN bodega_catalogo ca ON ca.id_area = cg.id_area
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

    // ingresos
    if (isset($data['ingreso_concepto']) && is_array($data['ingreso_concepto'])) {
      foreach ($data['ingreso_concepto'] as $key => $ingreso)
      {
        if (isset($data['ingreso_del'][$key]) && $data['ingreso_del'][$key] == 'true' &&
          isset($data['ingreso_id_ingresos'][$key]) && floatval($data['ingreso_id_ingresos'][$key]) > 0) {

          $this->db->delete('otros.bodega_ingresos', "id_ingresos = ".$data['ingreso_id_ingresos'][$key]);
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

          $this->db->update('otros.bodega_ingresos', $ingreso_udt, "id_ingresos = ".$data['ingreso_id_ingresos'][$key]);
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

          $this->db->insert('otros.bodega_ingresos', $ingresos);
        }
      }
    }

    // prestamos
    $this->db->delete('otros.bodega_prestamos', array('fecha' => $data['fecha_caja_chica'], 'no_caja' => $data['fno_caja']));
    if (isset($data['prestamo_id_prod']))
    {
      $prestamos = array();

      foreach ($data['prestamo_id_prod'] as $key => $remi)
      {
        $prestamos[] = array(
          'fecha'            => $data['fecha_caja_chica'],
          'row'              => $key,
          'id_unidad'        => $data['prestamo_umedida'][$key],
          'id_clasificacion' => $data['prestamo_id_prod'][$key],
          'descripcion'      => $data['prestamo_descripcion'][$key],
          'concepto'         => $data['prestamo_concepto'][$key],
          'cantidad'         => $data['prestamo_cantidad'][$key],
          'precio_unitario'  => $data['prestamo_precio'][$key],
          'importe'          => $data['prestamo_importe'][$key],
          'no_caja'          => $data['fno_caja'],
          'tipo'             => $data['prestamo_tipo'][$key],
        );
      }

      $this->db->insert_batch('otros.bodega_prestamos', $prestamos);
    }

    // ventas
    $ventas = array();
    if (isset($data['venta_id_factura'])) {
      foreach ($data['venta_id_factura'] as $key => $venta)
      {
        $ventas[] = array(
          'fecha'       => $data['fecha_caja_chica'],
          'id_remision' => $venta,
          'no_caja'     => $data['fno_caja'],
          'row'         => $key
        );
      }
    }
    $this->db->delete('otros.bodega_ventas', array('fecha' => $data['fecha_caja_chica'], 'no_caja' => $data['fno_caja']));
    if (count($ventas) > 0)
    {
      $this->db->insert_batch('otros.bodega_ventas', $ventas);
    }

    // Remisiones
    // //Elimina los movimientos de banco y cuentas por cobrar si ya se cerro el corte
    // $this->load->model('banco_cuentas_model');
    // $corte_caja = $this->get($data['fecha_caja_chica'], $data['fno_caja']);
    // foreach ($corte_caja['remisiones'] as $key => $value)
    // {
    //   if($value->id_movimiento != '')
    //     $this->banco_cuentas_model->deleteMovimiento($value->id_movimiento);
    // }
    $this->db->delete('otros.bodega_remisiones', array('fecha' => $data['fecha_caja_chica'], 'no_caja' => $data['fno_caja']));
    if (isset($data['remision_id_factura']))
    {
      $remisiones = array();

      foreach ($data['remision_id_factura'] as $key => $remi)
      {
        $remisiones[] = array(
          'fecha'       => $data['fecha_caja_chica'],
          'id_remision' => $remi,
          'no_caja'     => $data['fno_caja'],
          'row'         => $key
        );
      }

      $this->db->insert_batch('otros.bodega_remisiones', $remisiones);
    }

    // Existencias del dia
    $caja = $this->bodega_guadalajara_model->get($data['fecha_caja_chica'], $data['fno_caja']);
    $this->db->delete('otros.bodega_existencia', array('fecha' => $data['fecha_caja_chica'], 'no_caja' => $data['fno_caja']));
    // if (isset($data['exisd_id_factura']))
    if (count($caja['existencia_dia']) > 0)
    {
      $existencia_dia = array();

      $key = 0;
      foreach ($caja['existencia_dia'] as $exk => $exist) {
        $existencia_dia[] = array(
          'fecha'           => $data['fecha_caja_chica'],
          'id_factura'      => $exist->id_factura,
          'no_caja'         => $data['fno_caja'],
          'row'             => $key,
          'id_unidad'       => $exist->id_unidad,
          'descripcion'     => $exist->descripcion,
          'cantidad'        => $exist->cantidad,
          'precio_unitario' => $exist->precio_unitario,
          'importe'         => $exist->importe,
          'id_clasificacion'=> $exist->id_clasificacion,
        );
        ++$key;
      }

      // foreach ($data['exisd_id_factura'] as $key => $id_factura)
      // {
      //   $existencia_dia[] = array(
      //     'fecha'           => $data['fecha_caja_chica'],
      //     'id_factura'      => $id_factura,
      //     'no_caja'         => $data['fno_caja'],
      //     'row'             => $key,
      //     'id_unidad'       => $data['exisd_id_unidad'][$key],
      //     'descripcion'     => $data['exisd_descripcion'][$key],
      //     'cantidad'        => $data['exisd_cantidad'][$key],
      //     'precio_unitario' => $data['exisd_precio_unitario'][$key],
      //     'importe'         => $data['exisd_importe'][$key],
      //     'id_clasificacion'=> $data['exisd_id_clasificacion'][$key],
      //   );
      // }

      $this->db->insert_batch('otros.bodega_existencia', $existencia_dia);
    }

    // Denominaciones
    $this->db->delete('otros.bodega_efectivo', array('fecha' => $data['fecha_caja_chica'], 'no_caja' => $data['fno_caja']));
    $efectivo = array();
    foreach ($data['denom_abrev'] as $key => $denominacion)
    {
      $efectivo[$denominacion] = $data['denominacion_cantidad'][$key];
    }

    $efectivo['fecha']   = $data['fecha_caja_chica'];
    $efectivo['saldo']   = 0; //$data['saldo_corte']
    $efectivo['no_caja'] = $data['fno_caja'];

    $this->db->insert('otros.bodega_efectivo', $efectivo);

    // Gastos del dia
    // $this->db->delete('otros.bodega_gastos', array('fecha' => $data['fecha_caja_chica'], 'no_caja' => $data['fno_caja']));
    if (isset($data['gasto_concepto']))
    {
      $gastos_ids = array('adds' => array(), 'delets' => array(), 'updates' => array());
      $gastos_udt = $gastos = array();
      foreach ($data['gasto_concepto'] as $key => $gasto)
      {
        if (isset($data['gasto_del'][$key]) && $data['gasto_del'][$key] == 'true' &&
          isset($data['gasto_id_gasto'][$key]) && floatval($data['gasto_id_gasto'][$key]) > 0) {
          $gastos_ids['delets'][] = $this->getDataGasto($data['gasto_id_gasto'][$key]);

          $this->db->delete('otros.bodega_gastos', "id_gasto = ".$data['gasto_id_gasto'][$key]);
        } elseif (isset($data['gasto_id_gasto'][$key]) && floatval($data['gasto_id_gasto'][$key]) > 0) {
          $gastos_udt = array(
            'id_categoria'    => $data['gasto_empresa_id'][$key],
            'id_nomenclatura' => $data['gasto_nomenclatura'][$key],
            'folio'           => $data['gasto_folio'][$key],
            'concepto'        => $gasto,
            'monto'           => $data['gasto_importe'][$key],
            'fecha'           => $data['fecha_caja_chica'],
            'no_caja'         => $data['fno_caja'],
            'id_area'         => (isset($data['codigoAreaId'][$key]{0})? $data['codigoAreaId'][$key]: NULL),
          );

          $this->db->update('otros.bodega_gastos', $gastos_udt, "id_gasto = ".$data['gasto_id_gasto'][$key]);
        } else {
          $gastos = array(
            'id_categoria'    => $data['gasto_empresa_id'][$key],
            'id_nomenclatura' => $data['gasto_nomenclatura'][$key],
            'folio'           => $data['gasto_folio'][$key],
            'concepto'        => $gasto,
            'monto'           => $data['gasto_importe'][$key],
            'fecha'           => $data['fecha_caja_chica'],
            'no_caja'         => $data['fno_caja'],
            'id_area'         => (isset($data['codigoAreaId'][$key]{0})? $data['codigoAreaId'][$key]: NULL),
            'id_usuario'      => $this->session->userdata('id_usuario'),
          );
          $this->db->insert('otros.bodega_gastos', $gastos);
          $gastos_ids['adds'][] = $this->db->insert_id();
        }
      }


      // $gastos = array();
      // foreach ($data['gasto_concepto'] as $key => $gasto)
      // {
      //   $gastos[] = array(
      //     'id_categoria'    => $data['gasto_empresa_id'][$key],
      //     'id_nomenclatura' => $data['gasto_nomenclatura'][$key],
      //     'folio'           => $data['gasto_folio'][$key],
      //     'concepto'        => $gasto,
      //     'monto'           => $data['gasto_importe'][$key],
      //     'fecha'           => $data['fecha_caja_chica'],
      //     'no_caja'         => $data['fno_caja'],
      //     'id_area'         => (isset($data['codigoAreaId'][$key]{0})? $data['codigoAreaId'][$key]: NULL),
      //     'id_usuario'      => $this->session->userdata('id_usuario'),
      //   );
      // }

      // $this->db->insert_batch('otros.bodega_gastos', $gastos);
    }

    return true;
  }

  public function getRemisiones($fecha)
  {
    $this->load->model('cuentas_cobrar_model');

    $remisiones = $this->db->query(
      "SELECT f.id_factura, e.nombre_fiscal, DATE(f.fecha) as fecha, f.serie, f.folio, total, c.nombre_fiscal as cliente,
        fp.descripcion, fp.cantidad, fp.precio_unitario, (fp.importe+fp.iva) AS importe, u.nombre AS unidad, cl.id_clasificacion,
        (cl.codigo || '-' || u.codigo) AS codigo, u.cantidad AS cantidadu, u.id_unidad
      FROM facturacion f
        INNER JOIN clientes c ON c.id_cliente = f.id_cliente
        INNER JOIN empresas e ON e.id_empresa = f.id_empresa
        INNER JOIN facturacion_productos fp ON f.id_factura = fp.id_factura
        INNER JOIN clasificaciones cl ON cl.id_clasificacion = fp.id_clasificacion
        INNER JOIN unidades u ON u.id_unidad = fp.id_unidad
      WHERE f.status <> 'ca' AND f.is_factura = 'f' AND f.serie = 'R' AND c.nombre_fiscal = 'BODEGA DE GUADALAJARA'
        AND Date(f.fecha + interval '1 day') = '{$fecha}' AND f.folio <> 4487
      ORDER BY (f.fecha, f.serie, f.folio, fp.descripcion) ASC"
    );
    // COALESCE(cr.id_remision, 0) = 0

    $response = $remisiones->result();
    // $aux = 0;
    // foreach ($response as $key => $value)
    // {
    //   if ($aux == $value->id_factura) {
    //     $value->nombre_fiscal = '';
    //     $value->serie = '';
    //     $value->folio = '';
    //   } else
    //     $aux = $value->id_factura;
    //   // $inf_factura = $this->cuentas_cobrar_model->getDetalleVentaFacturaData($value->id_factura, 'f');
    //   // $value->saldo = $inf_factura['saldo'];
    // }

    return $response;
  }

  public function getVentas($fecha)
  {
    $this->load->model('empresas_model');

    // $defaultEmpresa = $this->empresas_model->getDefaultEmpresa();
    //  AND bc.id_empresa = {$defaultEmpresa->id_empresa}

    $ventas = $this->db->query(
      "SELECT f.id_factura, e.nombre_fiscal, DATE(f.fecha) as fecha, f.serie, f.folio, total, c.nombre_fiscal as cliente,
        fp.descripcion, fp.cantidad, fp.precio_unitario, (fp.importe+fp.iva) AS importe, u.nombre AS unidad, cl.id_clasificacion,
        (cl.codigo || '-' || u.codigo) AS codigo, u.cantidad AS cantidadu, u.id_unidad
      FROM facturacion f
        INNER JOIN clientes c ON c.id_cliente = f.id_cliente
        INNER JOIN empresas e ON e.id_empresa = f.id_empresa
        INNER JOIN facturacion_productos fp ON f.id_factura = fp.id_factura
        INNER JOIN clasificaciones cl ON cl.id_clasificacion = fp.id_clasificacion
        INNER JOIN unidades u ON u.id_unidad = fp.id_unidad
      WHERE f.status <> 'ca' AND f.is_factura = 'f' AND f.serie = 'RB' AND e.nombre_fiscal = 'ESJ BODEGA'
        AND Date(f.fecha) = '{$fecha}'
      ORDER BY (f.fecha, f.serie, f.folio, fp.descripcion) ASC
    ");

    $response = $ventas->result();
    $_GET['ffecha1'] = $_GET['ffecha2'] = $fecha;
    $aux = 0;
    foreach ($response as $key => $value)
    {
      if ($aux == $value->id_factura) {
        $value->cliente = '';
        $value->serie = '';
        $value->folio = '';
        $value->abonos = '';
        $value->saldo = '';
        $value->abonos_hoy = '';
      } else {
        $inf_factura = $this->cuentas_cobrar_model->getDetalleVentaFacturaData($value->id_factura, 'f');
        $value->abonos_hoy = 0;
        foreach ($inf_factura['abonos'] as $abn) {
          if ($abn->fecha == $fecha)
            $value->abonos_hoy += $abn->abono;
        }
        $value->abonos = $inf_factura['cobro'][0]->total-$inf_factura['saldo'];
        $value->saldo = $inf_factura['saldo'];

        $aux = $value->id_factura;
      }

    }

    return $response;
  }

  public function getPrestamos($fecha)
  {
    // Obtener los prestamos de otras bodegas
    $prestamos = $this->db->query(
      "SELECT DATE(bp.fecha) as fecha, bp.descripcion, bp.cantidad, bp.precio_unitario, bp.importe, u.nombre AS unidad, cl.id_clasificacion,
        (cl.codigo || '-' || u.codigo) AS codigo, u.cantidad AS cantidadu, u.id_unidad, bp.concepto, bp.tipo, 0 AS id_factura
      FROM otros.bodega_prestamos bp
        INNER JOIN unidades u ON u.id_unidad = bp.id_unidad
        INNER JOIN clasificaciones cl ON cl.id_clasificacion = bp.id_clasificacion
      WHERE Date(bp.fecha) = '{$fecha}'
      ORDER BY (bp.fecha, bp.descripcion) ASC"
    )->result();

    return $prestamos;
  }

  public function getAbonosVentasPasadas($fecha)
  {
    $this->load->model('cuentas_cobrar_model');

    // // $defaultEmpresa = $this->empresas_model->getDefaultEmpresa();
    // //  AND bc.id_empresa = {$defaultEmpresa->id_empresa}

    // // INNER JOIN facturacion_abonos fa ON f.id_factura = fa.id_factura
    // // AND Date(fa.fecha) = '{$fecha}'
    // $ventas = $this->db->query(
    //   "SELECT id_factura, nombre_fiscal, fecha, serie, folio, total, cliente
    //   FROM (
    //     SELECT f.id_factura, e.nombre_fiscal, DATE(f.fecha) as fecha, f.serie, f.folio, f.total, c.nombre_fiscal as cliente,
    //        f.status, (f.total-Coalesce(Sum(fa.total),0)) AS saldo
    //         FROM facturacion f
    //           INNER JOIN clientes c ON c.id_cliente = f.id_cliente
    //           INNER JOIN empresas e ON e.id_empresa = f.id_empresa
    //           LEFT JOIN facturacion_abonos fa ON (f.id_factura = fa.id_factura AND Date(fa.fecha) <= '{$fecha}')
    //         WHERE f.is_factura = 'f' AND f.serie = 'RB' AND e.nombre_fiscal = 'ESJ BODEGA'
    //           AND Date(f.fecha) < '{$fecha}'
    //         GROUP BY f.id_factura, e.id_empresa, c.id_cliente
    //         HAVING f.status = 'p' OR (f.status='pa' AND (f.total-Coalesce(Sum(fa.total),0)) > 0)
    //     union
    //     SELECT f.id_factura, e.nombre_fiscal, DATE(f.fecha) as fecha, f.serie, f.folio, f.total, c.nombre_fiscal as cliente,
    //     f.status, 0 AS saldo
    //         FROM facturacion f
    //           INNER JOIN clientes c ON c.id_cliente = f.id_cliente
    //           INNER JOIN empresas e ON e.id_empresa = f.id_empresa
    //           LEFT JOIN facturacion_abonos fa ON (f.id_factura = fa.id_factura AND Date(fa.fecha) = '{$fecha}')
    //         WHERE f.is_factura = 'f' AND f.serie = 'RB' AND e.nombre_fiscal = 'ESJ BODEGA'
    //           AND Date(f.fecha) < '{$fecha}' AND f.status = 'pa' AND Date(fa.fecha) = '{$fecha}'
    //         GROUP BY f.id_factura, e.id_empresa, c.id_cliente, fa.fecha
    //   ) t
    //   ORDER BY (fecha, serie, folio) DESC
    // ");

    $ventas = $this->db->query(
      "SELECT id_factura, nombre_fiscal, fecha, serie, folio, total, cliente
      FROM (
        SELECT f.id_factura, e.nombre_fiscal, DATE(f.fecha) as fecha, f.serie, f.folio, f.total, c.nombre_fiscal as cliente,
           f.status, (f.total-Coalesce(Sum(fa.total),0)) AS saldo, '1' AS tipo
            FROM facturacion f
              INNER JOIN clientes c ON c.id_cliente = f.id_cliente
              INNER JOIN empresas e ON e.id_empresa = f.id_empresa
              LEFT JOIN facturacion_abonos fa ON (f.id_factura = fa.id_factura AND Date(fa.fecha) <= '{$fecha}')
            WHERE f.is_factura = 'f' AND f.serie = 'RB' AND e.nombre_fiscal = 'ESJ BODEGA'
              AND Date(f.fecha) < '{$fecha}'
            GROUP BY f.id_factura, e.id_empresa, c.id_cliente
            HAVING f.status = 'p' OR (f.status='pa' AND (f.total-Coalesce(Sum(fa.total),0)) > 0)
        union
        SELECT f.id_factura, e.nombre_fiscal, DATE(f.fecha) as fecha, f.serie, f.folio, f.total, c.nombre_fiscal as cliente,
          f.status, (f.total-Coalesce((SELECT Sum(faa.total) FROM facturacion_abonos faa WHERE faa.id_factura = f.id_factura AND Date(faa.fecha) <= '{$fecha}'),0)) AS saldo,
          '2' AS tipo
            FROM facturacion f
              INNER JOIN clientes c ON c.id_cliente = f.id_cliente
              INNER JOIN empresas e ON e.id_empresa = f.id_empresa
              LEFT JOIN facturacion_abonos fa ON (f.id_factura = fa.id_factura AND Date(fa.fecha) = '{$fecha}')
            WHERE f.is_factura = 'f' AND f.serie = 'RB' AND e.nombre_fiscal = 'ESJ BODEGA'
              AND Date(f.fecha) < '{$fecha}' AND f.status = 'pa' AND Date(fa.fecha) = '{$fecha}'
            GROUP BY f.id_factura, e.id_empresa, c.id_cliente, fa.fecha
      ) t
      WHERE (tipo = '1' OR (tipo = '2' AND saldo = 0))
      ORDER BY (fecha, serie, folio) DESC
    ");

    $response = $ventas->result();
    $_GET['ffecha1'] = $_GET['ffecha2'] = $fecha;
    $aux = 0;
    foreach ($response as $key => $value)
    {
      $inf_factura = $this->cuentas_cobrar_model->getDetalleVentaFacturaData($value->id_factura, 'f');
      $value->abonos_hoy = 0;
      foreach ($inf_factura['abonos'] as $abn) {
        if ($abn->fecha == $fecha)
          $value->abonos_hoy += $abn->abono;
      }
      $value->saldo_ant = $value->abonos_hoy+$inf_factura['saldo'];
      $value->saldo = $inf_factura['saldo'];

      if ($value->saldo_ant == 0 && $value->saldo == 0 && $value->abonos_hoy == 0) {
        unset($response[$key]);
      }

      $aux = $value->id_factura;

    }

    return $response;
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
    $this->db->update('otros.bodega_efectivo', array('status' => 'f'), array('id_efectivo' => $idCaja));
    $caja = $this->db->query("SELECT fecha FROM otros.bodega_efectivo WHERE id_efectivo = {$idCaja}")->row();

    // $this->load->model('cuentas_cobrar_model');
    // $banco_cuenta = $this->db->query("SELECT id_cuenta FROM banco_cuentas WHERE UPPER(alias) LIKE '%PAGO REMISIONADO%'")->row();
    // $corte_caja = $this->get($caja->fecha, $noCajas);
    // foreach ($corte_caja['remisiones'] as $key => $value)
    // {
    //   $_POST['fmetodo_pago'] = 'efectivo';
    //   $_GET['tipo'] = 'r';
    //   $data = array('fecha'  => $caja->fecha,
    //         'concepto'       => 'Pago en caja chica',
    //         'total'          => $value->monto, //$total,
    //         'id_cuenta'      => $banco_cuenta->id_cuenta,
    //         'ref_movimiento' => 'Caja '.$noCajas,
    //         'saldar'         => 'no' );
    //   $resp = $this->cuentas_cobrar_model->addAbono($data, $value->id_remision);
    //   $this->db->update('otros.bodega_remisiones', array('id_movimiento' => $resp['id_movimiento']),
    //     "fecha = '{$value->fecha}' AND id_remision = {$value->id_remision} AND row = {$value->row}");
    // }

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
    $this->load->model('bodega_catalogo_model');

    $caja = $this->get($fecha, $noCajas);
    $nomenclaturas = $this->nomenclaturas();

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
    $pdf->titulo2 = "Bodega guadalajara del {$fecha}";
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
    $pdf->Row(array('REPORTE BODEGA GUADALAJARA'), true, true, null, 3);

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

    // // Saldo inicial
    // $pdf->SetXY(6, $pdf->GetY() + 5);
    // $pdf->SetAligns(array('R'));
    // $pdf->SetWidths(array(104));
    // $pdf->Row(array('SALDO INICIAL '.String::formatoNumero($caja['saldo_inicial'], 2, '$', false)), false, false);

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

    // CUENTAS POR COBRAR
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, 32);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(140, 55));
    $pdf->Row(array('CUENTAS POR COBRAR', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(25, 65, 25, 25, 25, 30));
    $pdf->Row(array('FECHA', 'CLIENTE', 'REM No.', 'S/ANTERIOR.', 'CONTADO', 'S/ACTUAL'), true, true);

    $totalSalAnt = $totalCont = $totalSal = 0;
    foreach ($caja['cts_cobrar'] as $ct_cobrar) {
      $totalSalAnt += floatval($ct_cobrar->saldo_ant);
      $totalCont += floatval($ct_cobrar->abonos_hoy);
      $totalSal += floatval($ct_cobrar->saldo);

      $pdf->SetX(6);
      $pdf->Row(array(
        $ct_cobrar->fecha,
        $ct_cobrar->cliente,
        $ct_cobrar->serie.$ct_cobrar->folio,
        String::formatoNumero($ct_cobrar->saldo_ant, 2, '', false),
        String::formatoNumero($ct_cobrar->abonos_hoy, 2, '', false),
        String::formatoNumero($ct_cobrar->saldo, 2, '', false)
        ), false, true);
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(6);
    $pdf->Row(array('', '', '',
      String::formatoNumero($totalSalAnt, 2, '', false),
      String::formatoNumero($totalCont, 2, '', false),
      String::formatoNumero($totalSal, 2, '', false)), false, true);

    // Ingresos por reposicion
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(165, 30));
    $pdf->Row(array('INGRESOS POR REPOSICION', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(40, 25, 30, 70, 30));
    $pdf->Row(array('EMPRESA', 'NOM', 'POLIZA', 'NOMBRE Y/O CONCEPTO', 'ABONO'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'R', 'L', 'L', 'R'));
    $pdf->SetWidths(array(40, 25, 30, 70, 30));

    $totalIngresosExt = 0;
    foreach ($caja['ingresos'] as $key => $ingreso)
    {
      $pdf->SetX(6);

      $pdf->Row(array(
        $ingreso->categoria,
        $ingreso->nomenclatura,
        $ingreso->poliza,
        $ingreso->concepto,
        String::formatoNumero($ingreso->monto, 2, '', false)), false, true);

      $totalIngresosExt += floatval($ingreso->monto);
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(6);
    $pdf->Row(array('', '', '','',
      String::formatoNumero($totalIngresosExt, 2, '', false)), false, true);

    // EXISTENCIA ANTERIOR
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(140, 55));
    $pdf->Row(array('EXISTENCIA ANTERIOR', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(25, 65, 25, 25, 25, 30));
    $pdf->Row(array('REM No.', 'PROVEEDOR', 'CLASIF.', 'BULTOS', 'PRECIO', 'IMPORTE'), true, true);

    $totalExisAnt = $bultosExisAnt = $aux = 0;
    foreach ($caja['existencia_ant'] as $exis_ant) {
      if ($aux == $exis_ant->id_factura) {
        $exis_ant->nombre_fiscal = '';
        $exis_ant->serie = '';
        $exis_ant->folio = '';
      } else
        $aux = $exis_ant->id_factura;
      $totalExisAnt += floatval($exis_ant->importe);
      $bultosExisAnt += floatval($exis_ant->cantidad);

      $pdf->SetX(6);
      $pdf->Row(array(
        $exis_ant->serie.$exis_ant->folio,
        $exis_ant->nombre_fiscal,
        $exis_ant->codigo,
        String::formatoNumero($exis_ant->cantidad, 2, '', false),
        String::formatoNumero($exis_ant->precio_unitario, 2, '', false),
        String::formatoNumero($exis_ant->importe, 2, '', false)), false, true);
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(6);
    $pdf->Row(array('', '', '',
      String::formatoNumero($bultosExisAnt, 2, '', false),
      String::formatoNumero($totalExisAnt/($bultosExisAnt>0?$bultosExisAnt:1), 2, '', false),
      String::formatoNumero($totalExisAnt, 2, '', false)), false, true);

    // INGRESOS DE MERCANCIAS
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(140, 55));
    $pdf->Row(array('INGRESOS DE MERCANCIAS', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(25, 65, 25, 25, 25, 30));
    $pdf->Row(array('REM No.', 'PROVEEDOR', 'CLASIF.', 'BULTOS', 'PRECIO', 'IMPORTE'), true, true);

    $totalIngresos = $bultosIngresos = $aux = 0;
    foreach ($caja['remisiones'] as $remision) {
      if ($aux == $remision->id_factura) {
        $remision->nombre_fiscal = '';
        $remision->serie = '';
        $remision->folio = '';
      } else
        $aux = $remision->id_factura;
      $totalIngresos += floatval($remision->importe);
      $bultosIngresos += floatval($remision->cantidad);

      $pdf->SetX(6);
      $pdf->Row(array(
        $remision->serie.$remision->folio,
        $remision->nombre_fiscal,
        $remision->codigo,
        String::formatoNumero($remision->cantidad, 2, '', false),
        String::formatoNumero($remision->precio_unitario, 2, '', false),
        String::formatoNumero($remision->importe, 2, '', false)), false, true);
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(6);
    $pdf->Row(array('', '', '',
      String::formatoNumero($bultosIngresos, 2, '', false),
      String::formatoNumero($totalIngresos/($bultosIngresos>0?$bultosIngresos:1), 2, '', false),
      String::formatoNumero($totalIngresos, 2, '', false)), false, true);

    // PRESTAMOS Y DEVOLUCIONES
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(140, 55));
    $pdf->Row(array('PRESTAMOS Y DEVOLUCIONES', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'L'));
    $pdf->SetWidths(array(60, 35, 25, 25, 25, 25));
    $pdf->Row(array('CONCEPTO', 'CLASIF', 'BULTOS', 'PRECIO', 'IMPORTE', 'TIPO'), true, true);

    $totalPrestamos = $totalPrestamosBultos = 0;
    foreach ($caja['prestamos'] as $prestamo) {
      $totalPrestamos += floatval($prestamo->importe);
      $totalPrestamosBultos += floatval($prestamo->cantidad);

      $pdf->SetX(6);
      $pdf->Row(array(
        $prestamo->concepto,
        $prestamo->codigo,
        String::formatoNumero($prestamo->cantidad, 2, '', false),
        String::formatoNumero($prestamo->precio_unitario, 2, '', false),
        String::formatoNumero($prestamo->importe, 2, '', false),
        ($prestamo->tipo=='t'? 'Prestamo': 'Pago')
      ), false, true);
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(6);
    $pdf->Row(array('', '', '',
      String::formatoNumero($totalPrestamosBultos, 2, '', false),
      String::formatoNumero($totalPrestamos/($totalPrestamosBultos>0?$totalPrestamosBultos:1), 2, '', false),
      String::formatoNumero($totalPrestamos, 2, '', false)), false, true);

    // VENTAS DEL DIA
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(140, 55));
    $pdf->Row(array('VENTAS DEL DIA', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(15, 50, 15, 20, 15, 20, 20, 20, 20));
    $pdf->Row(array('REM No.', 'CLIENTE', 'CLASIF.', 'BULTOS', 'PRECIO', 'IMPORTE', 'ABONOS HOY', 'T. ABONOS', 'SALDO'), true, true);

    $bultosVentas = $totalVentas = $abonoshVentas = $abonosVentas = $saldoVentas = 0;
    foreach ($caja['ventas'] as $venta) {
      $totalVentas += floatval($venta->importe);
      $bultosVentas += floatval($venta->cantidad);
      $abonoshVentas += floatval($venta->abonos_hoy);
      $abonosVentas += floatval($venta->abonos);
      $saldoVentas += floatval($venta->saldo);

      $pdf->SetX(6);
      $pdf->Row(array(
        $venta->serie.$venta->folio,
        $venta->cliente,
        $venta->codigo,
        String::formatoNumero($venta->cantidad, 2, '', false),
        String::formatoNumero($venta->precio_unitario, 2, '', false),
        String::formatoNumero($venta->importe, 2, '', false),
        String::formatoNumero($venta->abonos_hoy, 2, '', false),
        String::formatoNumero($venta->abonos, 2, '', false),
        String::formatoNumero($venta->saldo, 2, '', false),
        ), false, true);
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(6);
    $pdf->Row(array('', '', '',
      String::formatoNumero($bultosVentas, 2, '', false),
      String::formatoNumero($totalVentas/($bultosVentas>0?$bultosVentas:1), 2, '', false),
      String::formatoNumero($totalVentas, 2, '', false),
      String::formatoNumero($abonoshVentas, 2, '', false),
      String::formatoNumero($abonosVentas, 2, '', false),
      String::formatoNumero($saldoVentas, 2, '', false),
      ), false, true);

    // EXISTENCIA DEL DIA
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(140, 55));
    $pdf->Row(array('EXISTENCIA DEL DIA', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R'));
    $pdf->SetWidths(array(25, 65, 25, 25, 25, 30));
    $pdf->Row(array('REM No.', 'PROVEEDOR', 'CLASIF.', 'BULTOS', 'PRECIO', 'IMPORTE'), true, true);

    $bultosExisD = $totalExisD = 0; $aux = 0;
    foreach ($caja['existencia_dia'] as $exis_dia) {
      if ($aux == $exis_dia->id_factura) {
        $exis_dia->nombre_fiscal = '';
        $exis_dia->serie = '';
        $exis_dia->folio = '';
      } else
        $aux = $exis_dia->id_factura;
      $totalExisD += floatval($exis_dia->importe);
      $bultosExisD += floatval($exis_dia->cantidad);

      $pdf->SetX(6);
      $pdf->Row(array(
        $exis_dia->serie.$exis_dia->folio,
        $exis_dia->nombre_fiscal,
        $exis_dia->codigo,
        String::formatoNumero($exis_dia->cantidad, 2, '', false),
        String::formatoNumero($exis_dia->precio_unitario, 2, '', false),
        String::formatoNumero($exis_dia->importe, 2, '', false)), false, true);
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(6);
    $pdf->Row(array('', '', '',
      String::formatoNumero($bultosExisD, 2, '', false),
      String::formatoNumero($totalExisD/($bultosExisD>0?$bultosExisD:1), 2, '', false),
      String::formatoNumero($totalExisD, 2, '', false)), false, true);

    // $ttotalGastos = 0;
    // foreach ($caja['gastos'] as $gasto)
    // {
    //   $ttotalGastos += floatval($gasto->monto);
    // }

    // Gastos del Dia
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(170, 25));
    $pdf->Row(array('GASTOS DEL DIA', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R', 'L', 'R'));
    $pdf->SetWidths(array(30, 65, 25, 50, 25));
    $pdf->Row(array('COD', 'EMPRESA', 'NOM', 'CONCEPTO', 'CARGO'), true, true);

    $codigoAreas = array();
    $totalGastos = 0;
    foreach ($caja['gastos'] as $key => $gasto)
    {
      if ($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        // nomenclatura
        $this->printCajaNomenclatura($pdf, $nomenclaturas);
        $pdf->SetFont('Helvetica','B', 7);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R', 'L', 'R'));
        $pdf->SetWidths(array(30, 65, 25, 50, 25));
        $pdf->Row(array('COD', 'EMPRESA', 'NOM', 'CONCEPTO', 'CARGO'), true, true);
      }

      $totalGastos += floatval($gasto->monto);

      $pdf->SetX(6);
      $pdf->Row(array(
        $gasto->codigo_fin.' '.$this->bodega_catalogo_model->getDescripCodigo($gasto->id_area),
        $gasto->empresa,
        $gasto->nomenclatura,
        // $gasto->folio,
        $gasto->concepto,
        String::float(String::formatoNumero($gasto->monto, 2, '', false))), false, true);

      // if($gasto->id_area != '' && !array_key_exists($gasto->id_area, $codigoAreas))
      //   $codigoAreas[$gasto->id_area] = $this->bodega_catalogo_model->getDescripCodigo($gasto->id_area);
    }

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(6);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetAligns(array('L', 'R', 'L', 'L', 'R'));
    $pdf->Row(array('', '', '', 'TOTAL', String::formatoNumero($totalGastos, 2, '$', false)), true, true);

    // Tabulaciones
    $pdf->SetFont('Arial','B', 6);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(210, 210, 210);
    $pdf->SetXY(6, $pdf->GetY() + 3);
    $auxy = $pdf->GetY();
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
    $pdf->Row(array('SALDO AL CORTE', String::formatoNumero( ($totalCont+$abonoshVentas-$totalGastos) , 2, '$', false)), false, false);

    $pdf->SetX(6);
    $pdf->Row(array('TOTAL EFECTIVO', String::formatoNumero($totalEfectivo, 2, '$', false)), false, false);

    $pdf->SetX(6);
    $pdf->Row(array('DIFERENCIA', String::formatoNumero( ($totalCont+$abonoshVentas-$totalGastos)-$totalEfectivo , 2, '$', false)), false, false);

    // $pdf->SetFont('Arial', 'B', 6);
    // $pdf->SetXY(168, $pdf->GetY() - 32);
    // $pdf->SetAligns(array('R', 'R'));
    // $pdf->SetWidths(array(25, 19));
    // $pdf->Row(array('SALDO INICIAL', String::formatoNumero($caja['saldo_inicial'], 2, '$', false)), false, false);

    // $pdf->SetX(168);
    // $pdf->Row(array('TOTAL INGRESOS', String::formatoNumero($totalRemisiones + $totalIngresos, 2, '$', false)), false, false);
    // $pdf->SetX(168);
    // $pdf->Row(array('PAGO TOT LIMON ', String::formatoNumero($totalBoletas, 2, '$', false)), false, false);
    // $pdf->SetX(168);
    // $pdf->Row(array('PAGO TOT GASTOS', String::formatoNumero($ttotalGastos, 2, '$', false)), false, false);
    // $pdf->SetX(168);
    // $pdf->Row(array('EFECT. DEL CORTE', String::formatoNumero($caja['saldo_inicial'] + $totalRemisiones + $totalIngresos - $totalBoletas - $ttotalGastos, 2, '$', false)), false, false);

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
          cg.folio, cg.id_nomenclatura, cn.nomenclatura, COALESCE(ca.id_area) AS id_area,
          COALESCE(ca.nombre) AS nombre_codigo,
          COALESCE(ca.codigo_fin) AS codigo_fin,
          'id_area' AS campo, cg.no_caja, cg.no_impresiones, cg.fecha_creacion,
          (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS usuario_creo
       FROM otros.bodega_gastos cg
         INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
         INNER JOIN cajachica_nomenclaturas cn ON cn.id = cg.id_nomenclatura
         LEFT JOIN bodega_catalogo ca ON ca.id_area = cg.id_area
         LEFT JOIN usuarios AS u ON u.id = cg.id_usuario
       WHERE cg.id_gasto = '{$id_gasto}'
       ORDER BY cg.id_gasto ASC"
    )->row();

    return $gastos;
  }

  public function printVale($id_gasto)
  {
    $this->load->model('bodega_catalogo_model');

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
    $pdf->Row(array('VALE PROVISIONAL'), false, false);

    $pdf->SetAligns(array('L'));
    // $pdf->SetWidths(array(63));
    $pdf->SetXY(0, $pdf->GetY()-6);
    $pdf->Row(array('Folio: '.$gastos->id_gasto), false, false);

    $pdf->SetWidths(array(25, 38));
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetX(0);
    $pdf->Row(array('Caja: BODEGA '.$gastos->no_caja, String::formatoNumero($gastos->monto, 2, '$', false) ), false, false);

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
    $cod_sof = $gastos->codigo_fin.' '.$this->bodega_catalogo_model->getDescripCodigo($gastos->id_area);
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
    $pdf->Row(array('', '', $gastos->fecha), false, false);
    $pdf->Line(0, $pdf->GetY()+4, 62, $pdf->GetY()+4);
    $pdf->Line(21, $pdf->GetY()-12, 21, $pdf->GetY()+4);
    $pdf->Line(42, $pdf->GetY()-12, 42, $pdf->GetY()+4);

    $pdf->SetXY(0, $pdf->GetY()+5);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(21, 42));
    $pdf->Row(array('Creado por:', $gastos->usuario_creo), false, false);
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row(array('Creado:', $gastos->fecha_creacion), false, false);

    $this->db->where('id_gasto', $gastos->id_gasto)->set('no_impresiones', 'no_impresiones+1', false)->update('otros.bodega_gastos');

    // $pdf->AutoPrint(true);
    $pdf->Output('vale_gastos.pdf', 'I');
  }

  public function getDataValeIngresos($id_ingresos, $noCaja)
  {
    $ingreso = $this->db->query(
      "SELECT ci.*, cc.abreviatura as abr_empresa, cc.nombre AS empresa, cn.nomenclatura,
        (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS usuario_creo
       FROM otros.bodega_ingresos ci
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
    $pdf->Row(array('', '', $ingreso->fecha), false, false);
    $pdf->Line(0, $pdf->GetY()+4, 62, $pdf->GetY()+4);
    $pdf->Line(21, $pdf->GetY()-12, 21, $pdf->GetY()+4);
    $pdf->Line(42, $pdf->GetY()-12, 42, $pdf->GetY()+4);

    $pdf->SetXY(0, $pdf->GetY()+5);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(21, 42));
    $pdf->Row(array('Creado por:', $ingreso->usuario_creo), false, false);
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row(array('Creado:', $ingreso->fecha_creacion), false, false);

    $this->db->update('otros.bodega_ingresos', ['no_impresiones' => $ingreso->no_impresiones+1],
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
    $sql = '';
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
    }

    if ($this->input->get('fnomenclatura') != '')
      $sql .= " AND cn.id = ".$this->input->get('fnomenclatura');

    $response = array();
    $gastos = $this->db->query("SELECT cg.id_gasto, cc.id_categoria, cc.nombre AS categoria,
          cn.nombre AS nombre_nomen, cn.nomenclatura, cg.concepto, cg.monto, cg.fecha, cg.folio,
          cn.id AS id_nomenclatura, ca.codigo_fin, ca.id_area
        FROM otros.bodega_gastos cg
          INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
          INNER JOIN cajachica_nomenclaturas cn ON cn.id = cg.id_nomenclatura
          LEFT JOIN bodega_catalogo ca ON ca.id_area = cg.id_area
        WHERE fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
          {$sql}
        ORDER BY id_categoria ASC, fecha ASC");
    $response = $gastos->result();

    return $response;
  }
  /**
   * Reporte existencias por unidad pdf
   */
  public function getRptGastosPdf(){
    $res = $this->getRptGastosData();

    $this->load->model('bodega_catalogo_model');

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

    $aligns = array('L', 'L', 'L', 'C', 'L', 'R');
    $widths = array(20, 24, 22, 20, 80, 35);
    $header = array('Fecha', 'Codigo', 'Nomenclatura', 'Folio', 'Concepto', 'Importe');

    $codigoAreas = array();
    $aux_categoria = '';
    $total_nomenclatura = array();
    $proveedor_cantidad = $proveedor_importe = $proveedor_impuestos = $proveedor_total = 0;
    foreach($res as $key => $producto){
      if($pdf->GetY() >= $pdf->limiteY || $key==0 || $aux_categoria != $producto->id_categoria){ //salta de pagina si exede el max
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

        if($aux_categoria != $producto->id_categoria && $key > 0)
        {
          $this->getRptGastosTotales($pdf, $proveedor_total, $total_nomenclatura, $aux_categoria, $producto);
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
          $codigoAreas[$producto->id_area] = $this->bodega_catalogo_model->getDescripCodigo($producto->id_area);

      $proveedor_total += $producto->monto;
    }

    if(isset($producto))
      $this->getRptGastosTotales($pdf, $proveedor_total, $total_nomenclatura, $aux_categoria, $producto);

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

  public function getRptGastosTotales(&$pdf, &$proveedor_total, &$total_nomenclatura, &$aux_categoria, &$producto)
  {
    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B',8);
    $datos = array('Total General',
      String::formatoNumero(($proveedor_total), 2, '', false),
    );
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(166, 35));
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
    $sql = $sql2 = '';
      $idsproveedores = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    if($this->input->get('did_empresa') != ''){
      $sql .= " AND cc.id_categoria = '".$this->input->get('did_empresa')."'";
    }

    if ($this->input->get('fnomenclatura') != '')
      $sql2 .= " AND cn.id = ".$this->input->get('fnomenclatura');

    $response = array('movimientos' => array(), 'remisiones' => array());

    $movimientos = $this->db->query("SELECT ci.id_ingresos, cc.id_categoria, cc.nombre AS categoria,
          cn.nombre AS nombre_nomen, cn.nomenclatura, ci.concepto, ci.monto, ci.fecha, ci.poliza,
          cn.id AS id_nomenclatura
        FROM otros.bodega_ingresos ci
          INNER JOIN cajachica_categorias cc ON cc.id_categoria = ci.id_categoria
          INNER JOIN cajachica_nomenclaturas cn ON cn.id = ci.id_nomenclatura
        WHERE ci.fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
          {$sql} {$sql2}
        ORDER BY id_categoria ASC, fecha ASC");
    $response['movimientos'] = $movimientos->result();

    $remisiones = $this->db->query("SELECT cr.id_remision, cc.id_categoria, cc.nombre AS categoria,
          f.folio, f.serie, cr.observacion, cr.monto, cr.fecha, cr.folio_factura
        FROM otros.bodega_remisiones cr
          INNER JOIN cajachica_categorias cc ON cc.id_categoria = cr.id_categoria
          INNER JOIN facturacion f ON f.id_factura = cr.id_remision
        WHERE cr.fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
          {$sql}
        ORDER BY id_categoria ASC, fecha ASC");
    $response['remisiones'] = $remisiones->result();

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

/* End of file bodega_guadalajara_model.php */
/* Location: ./application/models/bodega_guadalajara_model.php */