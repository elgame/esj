<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class bodega_guadalajara_model extends CI_Model {

  public function get($fecha, $noCaja)
  {
    $info = array(
      'cts_cobrar'        => $this->bodega_guadalajara_model->getAbonosVentasPasadas($fecha),
      'ingresos'          => array(),
      'remisiones'        => $this->bodega_guadalajara_model->getRemisiones($fecha),
      'ventas'            => $this->bodega_guadalajara_model->getVentas($fecha),
      'prestamos'         => $this->bodega_guadalajara_model->getPrestamos($fecha),
      'existencia_ant'    => array(),
      'existencia_dia'    => array(),
      'denominaciones'    => array(),
      'gastos'            => array(),
      'traspasos'         => array(),
      'categorias'        => array(),
      'a_bultos_vendidos' => $this->getVentas($fecha, true),
      'costo_venta'       => 0,
      'utilidad'          => 0,
      'a_gastos'          => 0,
      'a_utilidad'        => 0,
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
      "SELECT be.id_factura, COALESCE(e.nombre_fiscal, 'Pago prestamo') AS nombre_fiscal, DATE(f.fecha) as fecha,
        COALESCE(f.serie, 'PP') AS serie, COALESCE(f.folio, 0) AS folio, total, COALESCE(c.nombre_fiscal, '') as cliente,
        be.descripcion, be.cantidad, be.precio_unitario, be.importe, u.nombre AS unidad, cl.id_clasificacion,
        (cl.codigo || '-' || u.codigo) AS codigo, u.cantidad AS cantidadu, u.id_unidad, (u.cantidad*be.cantidad) AS kilos
      FROM otros.bodega_existencia be
        INNER JOIN unidades u ON u.id_unidad = be.id_unidad
        INNER JOIN clasificaciones cl ON cl.id_clasificacion = be.id_clasificacion
        LEFT JOIN facturacion f ON be.id_factura = f.id_factura
        LEFT JOIN clientes c ON c.id_cliente = f.id_cliente
        LEFT JOIN empresas e ON e.id_empresa = f.id_empresa
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
    // echo "<pre>";
    //   var_dump($info['prestamos']);
    // echo "</pre>";exit;
    foreach ($info['prestamos'] as $key => $value) {
      if ($value->id_clasificacion != '49' AND $value->id_clasificacion != '50' AND
          $value->id_clasificacion != '51' AND $value->id_clasificacion != '52' AND
          $value->id_clasificacion != '53' AND $value->tipo == 'false')
      {
        $info['existencia_dia'][$value->id_factura.'-'.$value->id_clasificacion.'-'.$value->id_unidad.'-'.$key] = clone $value;
      }
    }
    foreach ($info['prestamos'] as $key => $value) {
      if ($value->tipo == 'true' || $value->tipo == 'dev')
      {
        $keys = preg_grep( '/^[0-9]+-'.$value->id_clasificacion.'-'.$value->id_unidad.'-[0-9]+/i', array_keys( $info['existencia_dia'] ) );
        $cantidad = $value->cantidad;
        $kilos    = $value->kilos;
        foreach ($keys as $k) {
          if ($info['existencia_dia'][$k]->cantidad < $cantidad) { // si se termina de ese registro se elimina
            $cantidad -= $info['existencia_dia'][$k]->cantidad;
            unset($info['existencia_dia'][$k]);
          } else { // si es mayor le resta al registro
            $info['existencia_dia'][$k]->kilos    -= $kilos;
            $info['existencia_dia'][$k]->cantidad -= $cantidad;
            $info['existencia_dia'][$k]->importe  -= $cantidad*$info['existencia_dia'][$k]->precio_unitario;
            $cantidad = 0;
            $kilos    = 0;

            if ($info['existencia_dia'][$k]->cantidad === 0)
              unset($info['existencia_dia'][$k]);

            break;
          }
        }
        if ($cantidad > 0) {
          $info['existencia_dia'][$key]           = clone $value;
          $info['existencia_dia'][$key]->cantidad = $cantidad*-1;
          $info['existencia_dia'][$key]->kilos    = $kilos*-1;
          $info['existencia_dia'][$key]->importe  = $info['existencia_dia'][$key]->cantidad*$info['existencia_dia'][$key]->precio_unitario;
        }
      }
    }

    // restamos las ventas del dia
    foreach ($info['ventas'] as $key => $value) {
      $keys = preg_grep( '/^[0-9]+-'.$value->id_clasificacion.'-'.$value->id_unidad.'-[0-9]+/i', array_keys( $info['existencia_dia'] ) );
      $cantidad = $value->cantidad;
      $kilos    = $value->kilos;
      foreach ($keys as $k) {
        if ($info['existencia_dia'][$k]->cantidad < $cantidad) {
          $cantidad -= $info['existencia_dia'][$k]->cantidad;
          unset($info['existencia_dia'][$k]);
        } else {
          $info['existencia_dia'][$k]->kilos    -= $kilos;
          $info['existencia_dia'][$k]->cantidad -= $cantidad;
          $info['existencia_dia'][$k]->importe  -= $cantidad*$info['existencia_dia'][$k]->precio_unitario;
          $cantidad = 0;

          if ($info['existencia_dia'][$k]->cantidad === 0)
            unset($info['existencia_dia'][$k]);

          break;
        }
      }
      if ($cantidad > 0) {
        $info['existencia_dia'][$key]           = clone $value;
        $info['existencia_dia'][$key]->cantidad = $cantidad*-1;
        $info['existencia_dia'][$key]->kilos    = $kilos*-1;
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

      // $info['a_bultos_vendidos'] = 0;
      // $info['a_kilos_vendidos']  = 0;
      // $info['a_gastos']          = 0;
      // $info['a_utilidad']        = 0;
      // $info['costo_venta']       = 0;
    }
    else
    {
      $denominaciones            = $denominaciones->result()[0];
      $info['status']            = $denominaciones->status;
      $info['id']                = $denominaciones->id_efectivo;
      $info['a_bultos_vendidos'] = $denominaciones->status? $info['a_bultos_vendidos']: $denominaciones->a_bultos_vendidos;
      // $info['a_kilos_vendidos']  = $denominaciones->a_kilos_vendidos;
      $info['a_gastos']          = $denominaciones->status? $this->getGastos($fecha, $noCaja, true): $denominaciones->a_gastos;
      $info['utilidad']          = $denominaciones->utilidad;
      $info['costo_venta']       = $denominaciones->costo_venta;
      $info['a_utilidad']        = $denominaciones->status? $this->getUtilidades($fecha, $noCaja): $denominaciones->a_utilidad;
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
    $gastos = $this->getGastos($fecha, $noCaja);
    if (count($gastos) > 0)
    {
      $info['gastos'] = $gastos;
    }

    // Traspasos
    $traspasos = $this->getTraspasos($fecha, $noCaja);
    if (count($traspasos) > 0)
    {
      $info['traspasos'] = $traspasos;
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

    $efectivo['fecha']             = $data['fecha_caja_chica'];
    $efectivo['saldo']             = 0; //$data['saldo_corte']
    $efectivo['no_caja']           = $data['fno_caja'];
    $efectivo['a_bultos_vendidos'] = $data['a_bultos_vendidos'];
    $efectivo['a_gastos']          = $data['a_gastos'];
    $efectivo['utilidad']          = $data['utilidad'];
    $efectivo['costo_venta']       = $data['costo_venta'];
    $efectivo['a_utilidad']        = $data['a_utilidad'];

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

    // Traspasos
    if (isset($data['traspaso_concepto']))
    {
      $traspasos_ids = array('adds' => array(), 'delets' => array(), 'updates' => array());
      $gastos_udt = $gastos = array();
      foreach ($data['traspaso_concepto'] as $key => $concepto)
      {
        if (isset($data['traspaso_del'][$key]) && $data['traspaso_del'][$key] == 'true' &&
          isset($data['traspaso_id_traspaso'][$key]) && floatval($data['traspaso_id_traspaso'][$key]) > 0) {
          // $traspasos_ids['delets'][] = $this->getDataGasto($data['traspaso_id_traspaso'][$key]);

          $this->db->delete('otros.bodega_traspasos', "id_traspaso = ".$data['traspaso_id_traspaso'][$key]);
        } elseif (isset($data['traspaso_id_traspaso'][$key]) && floatval($data['traspaso_id_traspaso'][$key]) > 0) {
          $gastos_udt = array(
            'concepto' => $concepto,
            'monto'    => $data['traspaso_importe'][$key],
            'tipo'     => $data['traspaso_tipo'][$key],
          );

          $this->db->update('otros.bodega_traspasos', $gastos_udt, "id_traspaso = ".$data['traspaso_id_traspaso'][$key]);
        } else {
          $traspaso = array(
            'concepto'   => $concepto,
            'monto'      => $data['traspaso_importe'][$key],
            'tipo'       => $data['traspaso_tipo'][$key],
            'fecha'      => $data['fecha_caja_chica'],
            'no_caja'    => $data['fno_caja'],
            'id_usuario' => $this->session->userdata('id_usuario'),
          );
          $this->db->insert('otros.bodega_traspasos', $traspaso);
          $traspasos_ids['adds'][] = $this->db->insert_id();
        }
      }
    }

    return true;
  }

  public function getRemisiones($fecha)
  {
    $this->load->model('cuentas_cobrar_model');

    $remisiones = $this->db->query(
      "SELECT f.id_factura, e.nombre_fiscal, DATE(f.fecha) as fecha, f.serie, f.folio, total, c.nombre_fiscal as cliente,
        fp.descripcion, fp.cantidad, fp.precio_unitario, (fp.importe+fp.iva) AS importe, u.nombre AS unidad, cl.id_clasificacion,
        (cl.codigo || '-' || u.codigo) AS codigo, u.cantidad AS cantidadu, u.id_unidad, (u.cantidad*fp.cantidad) AS kilos
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

  public function getVentas($fecha, $bultos=false)
  {
    $this->load->model('empresas_model');

    // $defaultEmpresa = $this->empresas_model->getDefaultEmpresa();
    //  AND bc.id_empresa = {$defaultEmpresa->id_empresa}

    if ($bultos) {
      $ventas = $this->db->query(
        "SELECT Sum(fp.cantidad) AS cantidad
        FROM facturacion f
          INNER JOIN facturacion_productos fp ON f.id_factura = fp.id_factura
          INNER JOIN empresas e ON e.id_empresa = f.id_empresa
        WHERE f.status <> 'ca' AND f.is_factura = 'f' AND f.serie = 'RB' AND e.nombre_fiscal = 'ESJ BODEGA'
          AND Date(f.fecha) <= '{$fecha}'
      ")->row();

      return $ventas->cantidad;
    }
    $ventas = $this->db->query(
      "SELECT f.id_factura, e.nombre_fiscal, DATE(f.fecha) as fecha, f.serie, f.folio, total, c.nombre_fiscal as cliente,
        fp.descripcion, fp.cantidad, fp.precio_unitario, (fp.importe+fp.iva) AS importe, u.nombre AS unidad, cl.id_clasificacion,
        (cl.codigo || '-' || u.codigo) AS codigo, u.cantidad AS cantidadu, u.id_unidad, (u.cantidad*fp.cantidad) AS kilos
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
        $value->tickets_hoy = [];
      } else {
        $inf_factura = $this->cuentas_cobrar_model->getDetalleVentaFacturaData($value->id_factura, 'f');
        $value->abonos_hoy = 0;
        $value->tickets_hoy = [];
        foreach ($inf_factura['abonos'] as $abn) {
          if ($abn->fecha == $fecha)
            $value->abonos_hoy += $abn->abono;
            $value->tickets_hoy[] = $abn->id_abono;
        }
        $value->abonos = $inf_factura['cobro'][0]->total-$inf_factura['saldo'];
        $value->saldo = $inf_factura['saldo'];

        $aux = $value->id_factura;
      }

      $value->tickets_hoy = implode(', ', $value->tickets_hoy);
    }

    return $response;
  }

  public function getGastos($fecha, $noCaja, $total=false)
  {
    if ($total) {
      $gastos = $this->db->query(
        "SELECT Sum(cg.monto) AS monto
         FROM otros.bodega_gastos cg
           INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
           INNER JOIN cajachica_nomenclaturas cn ON cn.id = cg.id_nomenclatura
           LEFT JOIN bodega_catalogo ca ON ca.id_area = cg.id_area
         WHERE cg.fecha <= '{$fecha}' AND cg.no_caja = {$noCaja}"
      )->row();
      return $gastos->monto;
    }

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

    return $gastos->result();
  }

  public function getTraspasos($fecha, $noCaja, $total=false)
  {
    if ($total) {
      $traspaso = $this->db->query(
        "SELECT Sum(bt.monto) AS monto
         FROM otros.bodega_traspasos bt
         WHERE bt.fecha <= '{$fecha}' AND bt.no_caja = {$noCaja}"
      )->row();
      return $traspaso->monto;
    }

    $traspaso = $this->db->query(
      "SELECT bt.id_traspaso, bt.concepto, bt.monto, bt.fecha, bt.no_caja, bt.no_impresiones, bt.id_usuario, bt.fecha_creacion, bt.tipo
       FROM otros.bodega_traspasos bt
       WHERE bt.fecha = '{$fecha}' AND bt.no_caja = {$noCaja}
       ORDER BY bt.id_traspaso ASC"
    );

    return $traspaso->result();
  }

  public function getUtilidades($fecha, $noCaja)
  {
    $gastos = $this->db->query(
      "SELECT Sum(utilidad) AS utilidad
       FROM otros.bodega_efectivo
       WHERE fecha <= '{$fecha}' AND no_caja = {$noCaja}"
    )->row();
    return $gastos->utilidad;
  }

  public function getPrestamos($fecha)
  {
    // Obtener los prestamos de otras bodegas
    $prestamos = $this->db->query(
      "SELECT DATE(bp.fecha) as fecha, bp.descripcion, bp.cantidad, bp.precio_unitario, bp.importe, u.nombre AS unidad, cl.id_clasificacion,
        (cl.codigo || '-' || u.codigo) AS codigo, u.cantidad AS cantidadu, u.id_unidad, (u.cantidad*bp.cantidad) AS kilos,
        bp.concepto, bp.tipo, 0 AS id_factura, 'PP' AS serie, '' AS folio, 'Pago prestamo' AS nombre_fiscal
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
      "SELECT t.id_factura, t.nombre_fiscal, t.fecha, t.serie, t.folio, t.total, t.cliente, t.id_cliente, rh.folio_factura
      FROM (
        SELECT f.id_factura, e.nombre_fiscal, DATE(f.fecha) as fecha, f.serie, f.folio, f.total, c.nombre_fiscal as cliente,
           c.id_cliente, f.status, (f.total-Coalesce(Sum(fa.total),0)) AS saldo, '1' AS tipo
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
          c.id_cliente, f.status, (f.total-Coalesce((SELECT Sum(faa.total) FROM facturacion_abonos faa WHERE faa.id_factura = f.id_factura AND Date(faa.fecha) <= '{$fecha}'),0)) AS saldo,
          '2' AS tipo
            FROM facturacion f
              INNER JOIN clientes c ON c.id_cliente = f.id_cliente
              INNER JOIN empresas e ON e.id_empresa = f.id_empresa
              LEFT JOIN facturacion_abonos fa ON (f.id_factura = fa.id_factura AND Date(fa.fecha) = '{$fecha}')
            WHERE f.is_factura = 'f' AND f.serie = 'RB' AND e.nombre_fiscal = 'ESJ BODEGA'
              AND Date(f.fecha) < '{$fecha}' AND f.status = 'pa' AND Date(fa.fecha) = '{$fecha}'
            GROUP BY f.id_factura, e.id_empresa, c.id_cliente, fa.fecha
      ) t
      LEFT JOIN (
        SELECT fr.id_remision, (f.serie || f.folio) AS folio_factura
        FROM facturacion f
          INNER JOIN facturacion_remision_hist fr ON f.id_factura = fr.id_factura
        WHERE f.status <> 'b' AND f.status <> 'ca'
        GROUP BY fr.id_remision, f.serie, f.folio
        HAVING Count(fr.id_remision) = 1
      ) rh ON t.id_factura = rh.id_remision
      WHERE (t.tipo = '1' OR (t.tipo = '2' AND t.saldo = 0))
      ORDER BY (t.id_cliente, t.fecha, t.serie, t.folio) DESC
    ");

    $response = $ventas->result();
    $_GET['ffecha1'] = $_GET['ffecha2'] = $fecha;
    $aux = 0;
    foreach ($response as $key => $value)
    {
      $inf_factura = $this->cuentas_cobrar_model->getDetalleVentaFacturaData($value->id_factura, 'f');
      $value->abonos_hoy = 0;
      $value->tickets_hoy = [];
      foreach ($inf_factura['abonos'] as $abn) {
        if ($abn->fecha == $fecha) {
          $value->abonos_hoy += $abn->abono;
          $value->tickets_hoy[] = $abn->id_abono;
        }
      }
      $value->saldo_ant = $value->abonos_hoy+$inf_factura['saldo'];
      $value->saldo = $inf_factura['saldo'];

      $value->tickets_hoy = implode(', ', $value->tickets_hoy);

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
    foreach ($nomenclaturas as $key => $nom) {
      $nomenclaturas['n'.$nom->nomenclatura] = $nom;
      unset($nomenclaturas[$key]);
    }

    // echo "<pre>";
    //   var_dump($caja);
    // echo "</pre>";exit;
    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->SetLeftMargin(6);

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
    $pdf->SetFont('Arial','B', 9);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetX(36);
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(150));
    $pdf->Row(array('BODEGA MERCADO DE ABASTOS GUADALAJARA'), false, false, null, 3);

    $pdf->Image(APPPATH.(str_replace(APPPATH, '', '/images/logo.png')), 6, 6, 50);
    $pdf->Ln(20);

    $pdf->SetFont('Arial','B', 8);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);

    // Fecha
    $pdf->SetXY(36, $pdf->GetY() - 22);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(62));
    $pdf->Row(array('FECHA ' . MyString::fechaAT($fecha)), false, false);

    // // Saldo inicial
    // $pdf->SetXY(6, $pdf->GetY() + 5);
    // $pdf->SetAligns(array('R'));
    // $pdf->SetWidths(array(104));
    // $pdf->Row(array('SALDO INICIAL '.MyString::formatoNumero($caja['saldo_inicial'], 2, '$', false)), false, false);

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
    $pdf->SetXY(6, 25);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(140, 55));
    $pdf->Row(array('CUENTAS POR COBRAR', ''), false, false);

    $pdf->SetFont('Arial','B', 6.5);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'L', 'R', 'R', 'C', 'R'));
        $pdf->SetWidths(array(75, 16, 18, 16, 18, 18, 20, 25));
    $pdf->Row(array('CLIENTE', 'FECHA', 'REM No.', 'FAC No.', 'S/INICIAL.', 'INGRESOS', 'No Ticket', 'S/FINAL'), false, 'B');

    $pdf->SetFont('Arial','', 6);
    $totalSalAnt = $totalCont = $totalSal = 0;
    $aux_client = 0;
    foreach ($caja['cts_cobrar'] as $ct_cobrar) {
      $totalSalAnt += floatval($ct_cobrar->saldo_ant);
      $totalCont += floatval($ct_cobrar->abonos_hoy);
      $totalSal += floatval($ct_cobrar->saldo);

      if ($ct_cobrar->id_cliente != $aux_client) {
        $pdf->SetAligns(array('L', 'L', 'L', 'L', 'R', 'R', 'C', 'R'));
        $pdf->SetWidths(array(75, 16, 18, 16, 18, 18, 20, 25));
        $pdf->SetX(6);
        $pdf->Row(array(
          $ct_cobrar->cliente,
          MyString::fechaAT($ct_cobrar->fecha),
          $ct_cobrar->serie.$ct_cobrar->folio,
          $ct_cobrar->folio_factura,
          MyString::formatoNumero($ct_cobrar->saldo_ant, 2, '', false),
          MyString::formatoNumero($ct_cobrar->abonos_hoy, 2, '', false),
          $ct_cobrar->tickets_hoy,
          MyString::formatoNumero($ct_cobrar->saldo, 2, '', false),
        ), false, 'B');
        $aux_client = $ct_cobrar->id_cliente;
      } else {
        $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'C', 'R'));
        $pdf->SetWidths(array(16, 18, 16, 18, 18, 20, 25));
        $pdf->SetX(81);
        $pdf->Row(array(
          MyString::fechaAT($ct_cobrar->fecha),
          $ct_cobrar->serie.$ct_cobrar->folio,
          $ct_cobrar->folio_factura,
          MyString::formatoNumero($ct_cobrar->saldo_ant, 2, '', false),
          MyString::formatoNumero($ct_cobrar->abonos_hoy, 2, '', false),
          $ct_cobrar->tickets_hoy,
          MyString::formatoNumero($ct_cobrar->saldo, 2, '', false),
        ), false, 'B');
      }
    }

    $pdf->SetAligns(array('L', 'R', 'R', 'C', 'R', 'R'));
    $pdf->SetWidths(array(36, 20, 20, 28, 25));
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(81);
    $pdf->Row(array('SUMAS: ',
      MyString::formatoNumero($totalSalAnt, 2, '', false),
      MyString::formatoNumero($totalCont, 2, '', false),
      '',
      MyString::formatoNumero($totalSal, 2, '', false)), false, false);

    // EXISTENCIA ANTERIOR
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY()+1);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(140));
    $pdf->Row(array('EXISTENCIA ANTERIOR'), false, false);

    $pdf->SetFont('Arial','B', 6.5);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(67, 20, 13, 22, 19, 18, 18, 27));
    $pdf->Row(array('PROVEEDOR', 'REM No.', 'FECHA', 'CLASIF.', 'KILOS', 'BULTOS', 'PRECIO', 'IMPORTE'), false, 'B');
    $pdf->SetFont('Arial','', 6);

    $totalExisAnt = $bultosExisAnt = $kilosExisAnt = $aux = 0;
    foreach ($caja['existencia_ant'] as $exis_ant) {
      if ($aux != $exis_ant->id_factura) {
        $pdf->SetAligns(array('L', 'L', 'L', 'L', 'R', 'R', 'R', 'R'));
        $pdf->SetWidths(array(67, 20, 13, 22, 19, 18, 18, 27));
        $pdf->SetX(6);
        $pdf->Row(array(
          $exis_ant->nombre_fiscal,
          $exis_ant->serie.$exis_ant->folio,
          MyString::fechaAT($exis_ant->fecha),
          $exis_ant->codigo,
          $exis_ant->kilos,
          MyString::formatoNumero($exis_ant->cantidad, 2, '', false),
          MyString::formatoNumero($exis_ant->precio_unitario, 2, '', false),
          MyString::formatoNumero($exis_ant->importe, 2, '', false)), false, 'B');
        $aux = $exis_ant->id_factura;
      } else {
        $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R'));
        $pdf->SetWidths(array(22, 19, 18, 18, 27));
        $pdf->SetX(106);
        $pdf->Row(array(
          $exis_ant->codigo,
          MyString::formatoNumero($exis_ant->kilos, 2, '', false),
          MyString::formatoNumero($exis_ant->cantidad, 2, '', false),
          MyString::formatoNumero($exis_ant->precio_unitario, 2, '', false),
          MyString::formatoNumero($exis_ant->importe, 2, '', false)), false, 'B');
      }
      $totalExisAnt += floatval($exis_ant->importe);
      $bultosExisAnt += floatval($exis_ant->cantidad);
      $kilosExisAnt += floatval($exis_ant->kilos);
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(22, 19, 18, 18, 27));
    $pdf->SetX(106);
    $pdf->Row(array('SUMAS: ',
      MyString::formatoNumero($kilosExisAnt, 2, '', false),
      MyString::formatoNumero($bultosExisAnt, 2, '', false),
      MyString::formatoNumero($totalExisAnt/($bultosExisAnt>0?$bultosExisAnt:1), 2, '', false),
      MyString::formatoNumero($totalExisAnt, 2, '', false)), false, 'B');

    // INGRESOS DE MERCANCIAS
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY()+1);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(140));
    $pdf->Row(array('INGRESOS DE MERCANCIAS'), false, false);

    $pdf->SetFont('Arial','B', 6.5);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(67, 20, 13, 22, 19, 18, 18, 27));
    $pdf->Row(array('PROVEEDOR', 'REM No.', 'FECHA', 'CLASIF.', 'KILOS', 'BULTOS', 'PRECIO', 'IMPORTE'), false, 'B');
    $pdf->SetFont('Arial','', 6);

    $totalIngresos = $bultosIngresos = $kilosIngresos = $aux = 0;
    foreach ($caja['remisiones'] as $remision) {
      if ($aux != $remision->id_factura) {
        $pdf->SetAligns(array('L', 'L', 'L', 'L', 'R', 'R', 'R', 'R'));
        $pdf->SetWidths(array(67, 20, 13, 22, 19, 18, 18, 27));
        $pdf->SetX(6);
        $pdf->Row(array(
          $remision->nombre_fiscal,
          $remision->serie.$remision->folio,
          MyString::fechaAT($remision->fecha),
          $remision->codigo,
          MyString::formatoNumero($remision->kilos, 2, '', false),
          MyString::formatoNumero($remision->cantidad, 2, '', false),
          MyString::formatoNumero($remision->precio_unitario, 2, '', false),
          MyString::formatoNumero($remision->importe, 2, '', false)), false, 'B');
        $aux = $remision->id_factura;
      } else {
        $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R'));
        $pdf->SetWidths(array(22, 19, 18, 18, 27));
        $pdf->SetX(106);
        $pdf->Row(array(
          $remision->codigo,
          MyString::formatoNumero($remision->kilos, 2, '', false),
          MyString::formatoNumero($remision->cantidad, 2, '', false),
          MyString::formatoNumero($remision->precio_unitario, 2, '', false),
          MyString::formatoNumero($remision->importe, 2, '', false)), false, 'B');
      }
      $totalIngresos += floatval($remision->importe);
      $bultosIngresos += floatval($remision->cantidad);
      $kilosIngresos += floatval($remision->kilos);
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(22, 19, 18, 18, 27));
    $pdf->SetX(106);
    $pdf->Row(array('SUMAS: ',
      MyString::formatoNumero($kilosIngresos, 2, '', false),
      MyString::formatoNumero($bultosIngresos, 2, '', false),
      MyString::formatoNumero($totalIngresos/($bultosIngresos>0?$bultosIngresos:1), 2, '', false),
      MyString::formatoNumero($totalIngresos, 2, '', false)), false, false);

    // Ingresos por reposicion
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY()+1);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(165));
    $pdf->Row(array('OTROS INGRESOS'), false, false);

    $pdf->SetFont('Arial','B', 6.5);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C', 'C', 'C', 'C', 'C'));
    $pdf->SetWidths(array(25, 50, 28, 70, 30));
    $pdf->Row(array('NOM', 'EMPRESA', 'No TICKET', 'CONCEPTO', 'IMPORTE'), false, 'B');
    $pdf->SetFont('Arial','', 6);

    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'L', 'L', 'R'));
    $pdf->SetWidths(array(25, 50, 28, 70, 30));

    $totalIngresosExt = 0;
    foreach ($caja['ingresos'] as $key => $ingreso)
    {
      $pdf->SetX(6);

      $pdf->Row(array(
        $nomenclaturas['n'.$ingreso->nomenclatura]->nombre,
        $ingreso->categoria,
        $ingreso->id_ingresos,
        $ingreso->concepto,
        MyString::formatoNumero($ingreso->monto, 2, '', false)), false, 'B');

      $totalIngresosExt += floatval($ingreso->monto);
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(70, 30));
    $pdf->SetX(109);
    $pdf->Row(array('SUMA: ',
      MyString::formatoNumero($totalIngresosExt, 2, '', false)), false, false);

    // VENTAS DEL DIA
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(140));
    $pdf->Row(array('VENTAS DEL DIA'), false, false);

    $pdf->SetFont('Arial','B', 6.5);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'C', 'R'));
    $pdf->SetWidths(array(45, 17, 17, 16, 16, 15, 20, 20, 20, 20));
    $pdf->Row(array('CLIENTE', 'REM No.', 'CLASIF', 'KILOS', 'BULTOS', 'PRECIO', 'IMPORTE', 'INGRESOS', 'No TICKET', 'SALDO'), false, 'B');
    $pdf->SetFont('Arial','', 6);

    $bultosVentas = $totalVentas = $abonoshVentas = $abonosVentas = $saldoVentas = $kilosVentas = 0;
    $aux = 0;
    foreach ($caja['ventas'] as $venta) {
      $totalVentas += floatval($venta->importe);
      $bultosVentas += floatval($venta->cantidad);
      $kilosVentas += floatval($venta->kilos);
      $abonoshVentas += floatval($venta->abonos_hoy);
      $abonosVentas += floatval($venta->abonos);
      $saldoVentas += floatval($venta->saldo);

      if ($aux != $venta->id_factura) {
        $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R', 'R', 'R', 'R', 'C', 'R'));
        $pdf->SetWidths(array(45, 17, 17, 16, 16, 15, 20, 20, 20, 20));
        $pdf->SetX(6);
        $pdf->Row(array(
          $venta->cliente,
          $venta->serie.$venta->folio,
          $venta->codigo,
          MyString::formatoNumero($venta->kilos, 2, '', false),
          MyString::formatoNumero($venta->cantidad, 2, '', false),
          MyString::formatoNumero($venta->precio_unitario, 2, '', false),
          MyString::formatoNumero($venta->importe, 2, '', false),
          MyString::formatoNumero($venta->abonos_hoy, 2, '', false),
          // MyString::formatoNumero($venta->abonos, 2, '', false),
          $venta->tickets_hoy,
          MyString::formatoNumero($venta->saldo, 2, '', false),
        ), false, 'B');
        $aux = $venta->id_factura;
      } else {
        $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R', 'R', 'C', 'R'));
        $pdf->SetWidths(array(17, 16, 16, 15, 20, 20, 20, 20));
        $pdf->SetX(68);
        $pdf->Row(array(
          $venta->codigo,
          MyString::formatoNumero($venta->kilos, 2, '', false),
          MyString::formatoNumero($venta->cantidad, 2, '', false),
          MyString::formatoNumero($venta->precio_unitario, 2, '', false),
          MyString::formatoNumero($venta->importe, 2, '', false),
          '',
          // MyString::formatoNumero($venta->abonos, 2, '', false),
          $venta->tickets_hoy,
          '',
        ), false, 'B');
      }
    }
    $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R', 'R', 'C', 'R'));
    $pdf->SetWidths(array(17, 16, 16, 15, 20, 20, 20, 20));
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(68);
    $pdf->Row(array('SUMAS: ',
      MyString::formatoNumero($kilosVentas, 2, '', false),
      MyString::formatoNumero($bultosVentas, 2, '', false),
      MyString::formatoNumero($totalVentas/($bultosVentas>0?$bultosVentas:1), 2, '', false),
      MyString::formatoNumero($totalVentas, 2, '', false),
      MyString::formatoNumero($abonoshVentas, 2, '', false),
      // MyString::formatoNumero($abonosVentas, 2, '', false),
      '',
      MyString::formatoNumero($saldoVentas, 2, '', false),
    ), false, false);

    // PRESTAMOS Y DEVOLUCIONES
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(140));
    $pdf->Row(array('PRESTAMOS Y DEVOLUCIONES'), false, false);

    $pdf->SetFont('Arial','B', 6.5);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R', 'L'));
    $pdf->SetWidths(array(60, 30, 23, 23, 23, 23, 23));
    $pdf->Row(array('CONCEPTO', 'CLASIF', 'KILOS', 'BULTOS', 'PRECIO', 'IMPORTE', 'TIPO'), false, 'B');
    $pdf->SetFont('Arial','', 6);

    $totalPrestamos = $totalPrestamosRestas = $totalPrestamosBultos = $totalPrestamosKilos = 0;
    foreach ($caja['prestamos'] as $prestamo) {
      if ($prestamo->tipo=='true' || $prestamo->tipo=='dev')
        $totalPrestamosRestas += floatval($prestamo->importe);
      $totalPrestamos += floatval($prestamo->importe);
      $totalPrestamosBultos += floatval($prestamo->cantidad);
      $totalPrestamosKilos += floatval($prestamo->kilos);

      $pdf->SetX(6);
      $pdf->Row(array(
        $prestamo->concepto,
        $prestamo->codigo,
        MyString::formatoNumero($prestamo->kilos, 2, '', false),
        MyString::formatoNumero($prestamo->cantidad, 2, '', false),
        MyString::formatoNumero($prestamo->precio_unitario, 2, '', false),
        MyString::formatoNumero($prestamo->importe, 2, '', false),
        ($prestamo->tipo=='true'? 'Prestamo': ($prestamo->tipo=='false'? 'Pago': 'Devolucion'))
      ), false, 'B');
    }
    $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R', 'L'));
    $pdf->SetWidths(array(30, 23, 23, 23, 23, 23));
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(66);
    $pdf->Row(array('SUMAS:',
      MyString::formatoNumero($totalPrestamosKilos, 2, '', false),
      MyString::formatoNumero($totalPrestamosBultos, 2, '', false),
      MyString::formatoNumero($totalPrestamos/($totalPrestamosBultos>0?$totalPrestamosBultos:1), 2, '', false),
      MyString::formatoNumero($totalPrestamos, 2, '', false),
      MyString::formatoNumero($totalPrestamosRestas, 2, '', false)), false, false);

    // EXISTENCIA DEL DIA
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(140));
    $pdf->Row(array('EXISTENCIA DEL DIA'), false, false);

    $pdf->SetFont('Arial','B', 6.5);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(67, 20, 13, 22, 19, 18, 18, 27));
    $pdf->Row(array('PROVEEDOR', 'REM No.', 'FECHA', 'CLASIF.', 'KILOS', 'BULTOS', 'PRECIO', 'IMPORTE'), false, 'B');
    $pdf->SetFont('Arial','', 6);

    $bultosExisD = $totalExisD = $kilosExisD = 0; $aux = 0;
    foreach ($caja['existencia_dia'] as $exis_dia) {
      if ($aux != $exis_dia->id_factura) {
        $pdf->SetAligns(array('L', 'L', 'L', 'L', 'R', 'R', 'R', 'R'));
        $pdf->SetWidths(array(67, 20, 13, 22, 19, 18, 18, 27));
        $pdf->SetX(6);
        $pdf->Row(array(
          $exis_dia->nombre_fiscal,
          $exis_dia->serie.$exis_dia->folio,
          MyString::fechaAT($exis_dia->fecha),
          $exis_dia->codigo,
          MyString::formatoNumero($exis_dia->kilos, 2, '', false),
          MyString::formatoNumero($exis_dia->cantidad, 2, '', false),
          MyString::formatoNumero($exis_dia->precio_unitario, 2, '', false),
          MyString::formatoNumero($exis_dia->importe, 2, '', false)), false, 'B');
        $aux = $exis_dia->id_factura;
      } else {
        $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R'));
        $pdf->SetWidths(array(22, 19, 18, 18, 27));
        $pdf->SetX(106);
        $pdf->Row(array(
          $exis_dia->codigo,
          MyString::formatoNumero($exis_dia->kilos, 2, '', false),
          MyString::formatoNumero($exis_dia->cantidad, 2, '', false),
          MyString::formatoNumero($exis_dia->precio_unitario, 2, '', false),
          MyString::formatoNumero($exis_dia->importe, 2, '', false)), false, 'B');
      }
      $totalExisD += floatval($exis_dia->importe);
      $bultosExisD += floatval($exis_dia->cantidad);
      $kilosExisD += floatval($exis_dia->kilos);
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(22, 19, 18, 18, 27));
    $pdf->SetX(106);
    $pdf->Row(array('SUMAS: ',
      MyString::formatoNumero($kilosExisD, 2, '', false),
      MyString::formatoNumero($bultosExisD, 2, '', false),
      MyString::formatoNumero($totalExisD/($bultosExisD>0?$bultosExisD:1), 2, '', false),
      MyString::formatoNumero($totalExisD, 2, '', false)), false, false);

    // $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetXY(135, $pdf->GetY()+3);
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(50, 25));

    if ($this->usuarios_model->tienePrivilegioDe('', 'bodega_guadalajara/show_totales_c/')) {
      $pdf->Row(array('COSTO DE VENTA DEL DIA:', MyString::formatoNumero( $caja['costo_venta'] , 2, '$', false)), false, false);
    }

    // Gastos del Dia
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(170, 25));
    $pdf->Row(array('GASTOS DEL DIA'), false, false);

    $pdf->SetFont('Arial','B', 6.5);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'L', 'C', 'L', 'R'));
    $pdf->SetWidths(array(50, 30, 25, 18, 50, 30));
    $pdf->Row(array('CENTRO COSTO', 'EMPRESA', 'NOM', 'No TICKET', 'CONCEPTO', 'IMPORTE'), false, 'B');
    $pdf->SetFont('Arial','', 6);

    $codigoAreas = array();
    $totalGastos = 0;
    foreach ($caja['gastos'] as $key => $gasto)
    {
      // if ($pdf->GetY() >= $pdf->limiteY)
      // {
      //   $pdf->AddPage();
      //   // nomenclatura
      //   $this->printCajaNomenclatura($pdf, $nomenclaturas);
      //   $pdf->SetFont('Helvetica','B', 7);
      //   $pdf->SetXY(6, $pdf->GetY());
      //   $pdf->SetAligns(array('L', 'L', 'R', 'L', 'R'));
      //   $pdf->SetWidths(array(30, 65, 25, 50, 25));
      //   $pdf->Row(array('COD', 'EMPRESA', 'NOM', 'CONCEPTO', 'CARGO'), true, true);
      // }

      $totalGastos += floatval($gasto->monto);
      $pdf->SetX(6);
      $pdf->Row(array(
        $gasto->codigo_fin.' '.$this->bodega_catalogo_model->getDescripCodigo($gasto->id_area),
        $gasto->empresa,
        $nomenclaturas['n'.$gasto->nomenclatura]->nombre,
        $gasto->id_gasto,
        $gasto->concepto,
        MyString::float(MyString::formatoNumero($gasto->monto, 2, '', false))), false, 'B');

      // if($gasto->id_area != '' && !array_key_exists($gasto->id_area, $codigoAreas))
      //   $codigoAreas[$gasto->id_area] = $this->bodega_catalogo_model->getDescripCodigo($gasto->id_area);
    }

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(129);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(50, 30));
    $pdf->Row(array('SUMA: ', MyString::formatoNumero($totalGastos, 2, '$', false)), false, false);

    // Traspasos
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(170, 25));
    $pdf->Row(array('TRASPASOS'), false, false);

    $pdf->SetFont('Arial','B', 6.5);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(50, 123, 30));
    $pdf->Row(array('TIPO', 'CONCEPTO', 'IMPORTE'), false, 'B');
    $pdf->SetFont('Arial','', 6);

    $codigoAreas = array();
    $totalTraspasos = 0;
    foreach ($caja['traspasos'] as $key => $traspaso)
    {
      $totalTraspasos += floatval($traspaso->monto);
      $pdf->SetX(6);
      $pdf->Row(array(
        ($traspaso->tipo=='t'? 'Ingreso': 'Egreso'),
        $traspaso->concepto,
        MyString::float(MyString::formatoNumero($traspaso->monto, 2, '', false))), false, 'B');
    }

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(129);
    $pdf->SetFillColor(255, 255, 255);
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(50, 30));
    $pdf->Row(array('SUMA: ', MyString::formatoNumero($totalTraspasos, 2, '$', false)), false, false);

    // Tabulaciones
    $pdf->SetFont('Arial','B', 6);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(210, 210, 210);
    $pdf->SetXY(6, $pdf->GetY() + 3);
    $auxy = $pdf->GetY();
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(57));
    $pdf->Row(array('TABULACION DE EFECTIVO'), false, 'B');

    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(255, 255, 255);
    // $pdf->SetXY(131, $boletasY - 5.4);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('C', 'C', 'C'));
    $pdf->SetWidths(array(16, 16, 25));
    $pdf->Row(array('DENOMIN.', 'NUMERO', 'TOTAL'), true, true);

    $pdf->SetAligns(array('R', 'R', 'R'));
    $pdf->SetFont('Arial','', 7);
    $totalEfectivo = 0;
    $auxy = $pdf->GetY();
    foreach ($caja['denominaciones'] as $key => $denominacion)
    {
      if ($pdf->GetY() < $auxy)
        $auxy = $pdf->GetY();

      $pdf->SetX(6);
      $pdf->Row(array(
        $denominacion['denominacion'],
        $denominacion['cantidad'],
        MyString::formatoNumero($denominacion['total'], 2, '', false)), false, 'B');

      $totalEfectivo += floatval($denominacion['total']);
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(6);
    $pdf->SetAligns(array('C', 'R'));
    $pdf->SetWidths(array(32, 25));
    $pdf->Row(array('TOTAL EFECTIVO', MyString::formatoNumero($totalEfectivo, 2, '$', false)), false, true);

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetXY(80, $auxy);
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(50, 25));

    if ($this->usuarios_model->tienePrivilegioDe('', 'bodega_guadalajara/show_totales_c/')) {
      $pdf->Row(array('UTILIDAD O PERDIDA:', MyString::formatoNumero( $caja['utilidad'] , 2, '$', false)), false, false);
      $pdf->SetXY(80, $pdf->GetY()+5);
      $pdf->Row(array('ACUMULADO BULTOS VENDIDOS:', MyString::formatoNumero( $caja['a_bultos_vendidos'] , 2, '$', false)), false, false);
      $pdf->SetX(80);
      $pdf->Row(array('ACUMULADO DE GASTOS:', MyString::formatoNumero( $caja['a_gastos'] , 2, '$', false)), false, false);
      $pdf->SetX(80);
      $pdf->Row(array('PRECIO PROMEDIO BULTOS:', MyString::formatoNumero( $caja['a_gastos']/($caja['a_bultos_vendidos']>0? $caja['a_bultos_vendidos'] : 1) , 2, '$', false)), false, false);
      $pdf->SetX(80);
      $pdf->Row(array('UTILIDAD ACUMULADA', MyString::formatoNumero( $caja['a_utilidad'] , 2, '$', false)), false, false);
    }
    $pdf->SetXY(80, $pdf->GetY()+5);
    $pdf->Row(array('SALDO DE CLIENTES', MyString::formatoNumero( $totalSal+$saldoVentas , 2, '$', false)), false, false);

    $pdf->SetXY(80, $pdf->GetY()+10);
    $pdf->Row(array('SALDO AL CORTE', MyString::formatoNumero( ($totalCont+$totalIngresosExt+$abonoshVentas-$totalGastos) , 2, '$', false)), false, false);

    $pdf->SetX(80);
    $pdf->Row(array('DIFERENCIA', MyString::formatoNumero( ($totalCont+$totalIngresosExt+$abonoshVentas-$totalGastos)-$totalEfectivo , 2, '$', false)), false, false);

    // $pdf->SetFont('Arial', 'B', 6);
    // $pdf->SetXY(168, $pdf->GetY() - 32);
    // $pdf->SetAligns(array('R', 'R'));
    // $pdf->SetWidths(array(25, 19));
    // $pdf->Row(array('SALDO INICIAL', MyString::formatoNumero($caja['saldo_inicial'], 2, '$', false)), false, false);

    // $pdf->SetX(168);
    // $pdf->Row(array('TOTAL INGRESOS', MyString::formatoNumero($totalRemisiones + $totalIngresos, 2, '$', false)), false, false);
    // $pdf->SetX(168);
    // $pdf->Row(array('PAGO TOT LIMON ', MyString::formatoNumero($totalBoletas, 2, '$', false)), false, false);
    // $pdf->SetX(168);
    // $pdf->Row(array('PAGO TOT GASTOS', MyString::formatoNumero($ttotalGastos, 2, '$', false)), false, false);
    // $pdf->SetX(168);
    // $pdf->Row(array('EFECT. DEL CORTE', MyString::formatoNumero($caja['saldo_inicial'] + $totalRemisiones + $totalIngresos - $totalBoletas - $ttotalGastos, 2, '$', false)), false, false);

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
    $pdf->Row(array('Caja: BODEGA '.$gastos->no_caja, MyString::formatoNumero($gastos->monto, 2, '$', false) ), false, false);

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
    $pdf->Row(array('', '', MyString::fechaAT($gastos->fecha)), false, false);
    $pdf->Line(0, $pdf->GetY()+4, 62, $pdf->GetY()+4);
    $pdf->Line(21, $pdf->GetY()-12, 21, $pdf->GetY()+4);
    $pdf->Line(42, $pdf->GetY()-12, 42, $pdf->GetY()+4);

    $pdf->SetXY(0, $pdf->GetY()+5);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(21, 42));
    $pdf->Row(array('Creado por:', $gastos->usuario_creo), false, false);
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row(array('Creado:', MyString::fechaAT($gastos->fecha_creacion)), false, false);

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
    $pdf->Row(array('CANTIDAD:', MyString::formatoNumero($ingreso->monto, 2, '$', false)), false, false);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(63));
    $pdf->SetX(0);
    $pdf->Row(array(MyString::num2letras($ingreso->monto)), false, false);
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
    $pdf->Row(array('', '', MyString::fechaAT($ingreso->fecha)), false, false);
    $pdf->Line(0, $pdf->GetY()+4, 62, $pdf->GetY()+4);
    $pdf->Line(21, $pdf->GetY()-12, 21, $pdf->GetY()+4);
    $pdf->Line(42, $pdf->GetY()-12, 42, $pdf->GetY()+4);

    $pdf->SetXY(0, $pdf->GetY()+5);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(21, 42));
    $pdf->Row(array('Creado por:', $ingreso->usuario_creo), false, false);
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row(array('Creado:', MyString::fechaAT($ingreso->fecha_creacion)), false, false);

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
        MyString::formatoNumero($producto->monto, 2, '', false),
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
      MyString::formatoNumero(($proveedor_total), 2, '', false),
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
      $pdf->Row(array($nomen[2], $nomen[1], MyString::formatoNumero($nomen[0], 2, '', false) ), false, false);
    }
    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(75, 50));
    $pdf->Row(array('', MyString::formatoNumero(($proveedor_total), 2, '', false)), false);

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
        WHERE ci.no_caja = 1 AND ci.fecha BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'
          {$sql} {$sql2}
        ORDER BY id_categoria ASC, fecha ASC");
    $response['movimientos'] = $movimientos->result();

    $ventas = $this->db->query(
      "SELECT f.id_factura, e.nombre_fiscal, DATE(f.fecha) as fecha, f.serie, f.folio, total, c.nombre_fiscal as cliente,
        fp.descripcion, fp.cantidad, fp.precio_unitario, (fp.importe+fp.iva) AS importe, u.nombre AS unidad, cl.id_clasificacion,
        (cl.codigo || '-' || u.codigo) AS codigo, u.cantidad AS cantidadu, u.id_unidad, cc.id_categoria, cc.nombre AS categoria
      FROM facturacion f
        INNER JOIN otros.bodega_ventas bv ON f.id_factura = bv.id_remision
        INNER JOIN clientes c ON c.id_cliente = f.id_cliente
        INNER JOIN empresas e ON e.id_empresa = f.id_empresa
        INNER JOIN facturacion_productos fp ON f.id_factura = fp.id_factura
        INNER JOIN clasificaciones cl ON cl.id_clasificacion = fp.id_clasificacion
        INNER JOIN unidades u ON u.id_unidad = fp.id_unidad
        INNER JOIN cajachica_categorias cc ON cc.id_empresa = f.id_empresa
      WHERE bv.no_caja = 1 AND Date(f.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}' {$sql}
      GROUP BY f.id_factura, e.id_empresa, c.id_cliente, fp.descripcion, fp.cantidad, fp.precio_unitario,
        fp.importe, fp.iva, u.id_unidad, cl.id_clasificacion, cc.id_categoria
      ORDER BY (f.fecha, f.serie, f.folio, fp.descripcion) ASC
    ");
    $response['remisiones'] = $ventas->result();

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
        MyString::formatoNumero($producto->monto, 2, '', false),
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

    $pdf->Output('ingresos_bodega.pdf', 'I');
  }

  public function getRptIngresosXls(){
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=ingresos_bodega.xls");
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
      MyString::formatoNumero(($proveedor_total), 2, '', false),
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
      $pdf->Row(array($nomen[2], $nomen[1], MyString::formatoNumero($nomen[0], 2, '', false) ), false, false);
    }
    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(75, 50));
    $pdf->Row(array('', MyString::formatoNumero(($proveedor_total), 2, '', false)), false);

    $aux_categoria      = $producto->id_categoria;
    $proveedor_total    = 0;
    $total_nomenclatura = array();

    $pdf->SetXY(6, $pdf->GetY()+8);
  }

  public function getRptRemisionesTotales(&$pdf, &$remisiones, $proveedor_total, $id_categoria)
  {
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'L', 'L', 'R', 'R', 'R');
    $widths = array(18, 20, 65, 15, 25, 30, 30);
    $header = array('Fecha', 'Rem No.', 'Cliente', 'Clasif', 'Bultos', 'Precio', 'Importe');
    $entro = false;
    $total_nomenclatura = array();
    $remisiones_total = 0;
    $factu_aux = 0;
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

        if ($producto->id_factura == $factu_aux) {
          $producto->fecha = '';
          $producto->serie = '';
          $producto->folio = '';
          $producto->cliente = '';
        } else
          $factu_aux = $producto->id_factura;

        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','',8);
        $datos = array($producto->fecha,
          $producto->serie.$producto->folio,
          $producto->cliente,
          $producto->codigo,
          MyString::formatoNumero($producto->cantidad, 2, '', false),
          MyString::formatoNumero($producto->precio_unitario, 2, '', false),
          MyString::formatoNumero($producto->importe, 2, '', false),
          );
        $pdf->SetXY(6, $pdf->GetY()-2);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false, false);

        $remisiones_total += $producto->importe;

        unset($remisiones[$key]);
      }
    }

    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B',8);
    $datos = array('Total Remisiones',
      MyString::formatoNumero(($remisiones_total), 2, '', false),
    );
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(167, 35));
    $pdf->Row($datos, false);

    if($pdf->GetY()+6 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B',8);
    $datos = array('Total General',
      MyString::formatoNumero(($remisiones_total+$proveedor_total), 2, '', false),
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
      <td style="border:1px solid #000;background-color: #cccccc;">Rem No.</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Cliente</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Clasif</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Bultos</td>
      <td style="border:1px solid #000;background-color: #cccccc;">Precio</td>
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
        <td style="border:1px solid #000;">'.$producto->cliente.'</td>
        <td style="border:1px solid #000;">'.$producto->codigo.'</td>
        <td style="border:1px solid #000;">'.$producto->cantidad.'</td>
        <td style="border:1px solid #000;">'.$producto->precio_unitario.'</td>
        <td style="border:1px solid #000;">'.$producto->importe.'</td>
      </tr>';

      $remisiones_total += $producto->importe;
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