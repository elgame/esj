<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class cuentas_rendimiento_model extends CI_Model {

  public function get($fecha, $id_area=null)
  {
    if (is_null($id_area))
      $id_area = $this->areas_model->getAreaDefault();

    $info = array(
      'info'                         => null,
      'saldo_inicial'                => 0,
      'facturas'                     => array(),
      'existencia'                   => array(),
      'otros_ingresos'               => array(),
      'existencia_anterior'          => array(),
      'compras_empacadas'            => array(),
      'compras_bascula'              => array(),
      'compras_bascula_bonifica'     => array(),
      'ingresos_movimientos_bascula' => array(),
      'compras_apatzingan'           => array(),
      'costo_venta'                  => array(),
      'industrial'                   => 0,
      'rendimientos'                 => array(),
    );

    $info['info'] = $this->db->query("SELECT *
                               FROM cuentas_rendimiento
                               WHERE id_area = {$id_area} AND fecha = '{$fecha}'")->row();

    // facturas del dia
    $facturas = $this->db->query(
      "SELECT f.id_factura, f.serie, f.folio, Date(f.fecha) AS fecha,
        fp.cantidad, fp.precio_unitario, c.id_clasificacion, c.nombre AS cnombre, c.codigo AS ccodigo,
        u.id_unidad, u.codigo AS ucodigo, u.cantidad AS ucantidad, u.nombre AS unombre,
        (fp.cantidad*fp.precio_unitario) AS importe,
        CASE u.codigo WHEN 'KILOS' THEN fp.cantidad ELSE (fp.cantidad*u.cantidad) END AS kgs,
        cl.nombre_fiscal
      FROM facturacion f
        INNER JOIN clientes cl ON cl.id_cliente = f.id_cliente
        INNER JOIN facturacion_productos fp ON f.id_factura = fp.id_factura
        INNER JOIN clasificaciones c ON c.id_clasificacion = fp.id_clasificacion
        INNER JOIN unidades u ON u.id_unidad = fp.id_unidad
        LEFT JOIN (SELECT id_remision, id_factura, status
                  FROM remisiones_historial WHERE status <> 'ca' AND status <> 'b'
        ) fh ON f.id_factura = fh.id_remision
      WHERE f.id_abono_factura IS NULL
        AND f.status <> 'ca' AND f.status <> 'b'
        AND f.id_nc IS NULL AND Date(f.fecha) = '{$fecha}'
        AND c.id_area = {$id_area}
        AND COALESCE(fh.id_remision, 0) = 0
      ORDER BY f.id_factura ASC, f.serie ASC, f.folio ASC "
    );
    $info['facturas'] = $facturas->result();
    $aux_fact = '';
    foreach ($info['facturas'] as $key => $value) {
      if ($aux_fact == $value->id_factura) {
        $value->nombre_fiscal = $value->serie = $value->folio = '';
      }
      $aux_fact = $value->id_factura;
    }

    $existencia = $this->db->query(
      "SELECT c.id_clasificacion, c.nombre AS cnombre, c.codigo AS ccodigo,
        u.codigo AS ucodigo, u.cantidad AS ucantidad, u.nombre AS unombre,
        cre.id_unidad, cre.bultos, cre.precio, (cre.bultos*cre.precio) AS importe,
        CASE u.codigo WHEN 'KILOS' THEN cre.bultos ELSE (cre.bultos*u.cantidad) END AS kgs
      FROM cuentas_rendimiento_existencia cre
        INNER JOIN clasificaciones c ON c.id_clasificacion = cre.id_clasificacion
        INNER JOIN unidades u ON u.id_unidad = cre.id_unidad
      WHERE cre.id_area = {$id_area} AND cre.fecha = '{$fecha}'"
    );
    $info['existencia'] = $existencia->result();

    $otros_ingresos = $this->db->query(
      "SELECT Sum(fp.cantidad) AS cantidad, fp.precio_unitario, c.nombre AS cnombre, c.codigo AS ccodigo,
        Sum(fp.cantidad*fp.precio_unitario) AS importe, fp.unidad
      FROM facturacion f
        INNER JOIN clientes cl ON cl.id_cliente = f.id_cliente
        INNER JOIN facturacion_productos fp ON f.id_factura = fp.id_factura
        INNER JOIN clasificaciones c ON c.id_clasificacion = fp.id_clasificacion
      WHERE f.id_abono_factura IS NULL
        AND f.status <> 'ca' AND f.status <> 'b'
        AND f.id_nc IS NULL AND Date(f.fecha) = '{$fecha}'
        AND c.id_clasificacion in(49,50,51,52,53)
      GROUP BY c.id_clasificacion, fp.precio_unitario, fp.unidad
      ORDER BY c.nombre ASC"
    );
    $info['otros_ingresos'] = $otros_ingresos->result();

    // **************************

    $existencia_anterior = $this->db->query(
      "SELECT c.id_clasificacion, c.nombre AS cnombre, c.codigo AS ccodigo,
        u.id_unidad, u.codigo AS ucodigo, u.cantidad AS ucantidad, u.nombre AS unombre,
        cre.bultos AS cantidad, cre.precio,
        (cre.bultos*cre.precio) AS importe,
        CASE u.codigo WHEN 'KILOS' THEN cre.bultos ELSE (cre.bultos*u.cantidad) END AS kgs
      FROM cuentas_rendimiento_existencia cre
        INNER JOIN clasificaciones c ON c.id_clasificacion = cre.id_clasificacion
        INNER JOIN unidades u ON u.id_unidad = cre.id_unidad
      WHERE cre.id_area = {$id_area} AND cre.fecha = Date(date '{$fecha}' - interval '1 day')"
    );
    $info['existencia_anterior'] = $existencia_anterior->result();

    $compras_empacadas = $this->db->query(
      "SELECT c.id_clasificacion, c.nombre AS cnombre, c.codigo AS ccodigo,
        u.codigo AS ucodigo, u.cantidad AS ucantidad, u.nombre AS unombre,
        cre.id_unidad, cre.bultos, cre.precio, (cre.bultos*cre.precio) AS importe,
        CASE u.codigo WHEN 'KILOS' THEN cre.bultos ELSE (cre.bultos*u.cantidad) END AS kgs
      FROM cuentas_rendimiento_compras_empacadas cre
        INNER JOIN clasificaciones c ON c.id_clasificacion = cre.id_clasificacion
        INNER JOIN unidades u ON u.id_unidad = cre.id_unidad
      WHERE cre.id_area = {$id_area} AND cre.fecha = '{$fecha}'"
    );
    $info['compras_empacadas'] = $compras_empacadas->result();

    $compras_bascula = $this->db->query(
      "SELECT
        c.nombre, Sum(bc.cajas) cajas, Sum(bc.kilos) kilos,
        ROUND((Sum(bc.importe) / Sum(bc.kilos))::numeric, 2) precio, Sum(bc.importe) importe
      FROM bascula b
        INNER JOIN bascula_compra bc ON b.id_bascula = bc.id_bascula
        INNER JOIN calidades c ON c.id_calidad = bc.id_calidad
      WHERE b.id_area = {$id_area} AND bc.precio > 0 AND b.id_bonificacion IS NULL
        AND b.status = 't' AND b.tipo = 'en' AND Date(b.fecha_tara) = '{$fecha}'
      GROUP BY c.id_calidad
      ORDER BY c.nombre ASC"
    );
    $info['compras_bascula'] = $compras_bascula->result();

    $compras_bascula_bonifica = $this->db->query(
      "SELECT
        'BONIFICACION' nombre, Sum(bc.cajas) cajas, Sum(bc.kilos) kilos,
        ROUND((Sum(bc.importe) / Sum(bc.kilos))::numeric, 2) precio, Sum(bc.importe) importe
      FROM bascula b
        INNER JOIN bascula_compra bc ON b.id_bascula = bc.id_bascula
        INNER JOIN calidades c ON c.id_calidad = bc.id_calidad
      WHERE b.id_area = {$id_area} AND bc.precio > 0 AND b.id_bonificacion IS NOT NULL
        AND b.status = 't' AND b.tipo = 'en' AND Date(b.fecha_tara) = '{$fecha}'
      GROUP BY c.id_calidad"
    );
    $info['compras_bascula_bonifica'] = $compras_bascula_bonifica->row();

    $ingresos_movimientos_bascula = $this->db->query(
      "SELECT bultos cajas, kilos, precio, importe
      FROM cuentas_rendimiento_compras_tecoman
      WHERE id_area = {$id_area} AND fecha = '{$fecha}'"
    );
    $info['ingresos_movimientos_bascula'] = $ingresos_movimientos_bascula->result();

    $compras_apatzingan = $this->db->query(
      "SELECT cre.nombre, cre.unidad, cre.precio, cre.cantidad, (cre.precio*cre.cantidad) importe
      FROM cuentas_rendimiento_apatzingan cre
      WHERE cre.id_area = {$id_area} AND cre.fecha = '{$fecha}'"
    );
    $info['compras_apatzingan'] = $compras_apatzingan->result();

    $industrial = $this->db->query(
      "SELECT cre.fecha, cre.id_area, cre.precio
      FROM cuentas_rendimiento_industrial cre
      WHERE cre.id_area = {$id_area} AND cre.fecha = '{$fecha}'"
    );
    $info['industrial'] = $industrial->row();


    // Costos
    foreach ($info['facturas'] as $key => $factura) {
      if (isset($info['costo_venta'][$factura->id_unidad]) ) {
        $info['costo_venta'][$factura->id_unidad]->bultos += $factura->cantidad;
        $info['costo_venta'][$factura->id_unidad]->kilos  += $factura->cantidad*$factura->ucantidad;
      } else {
        $precio = $this->db->query("SELECT precio FROM cuentas_rendimiento_costo_venta
          WHERE id_area = {$id_area} AND fecha = '{$fecha}' AND id_unidad = {$factura->id_unidad}")->row();
        $info['costo_venta'][$factura->id_unidad] = new stdClass();
        $info['costo_venta'][$factura->id_unidad]->id_unidad = $factura->id_unidad;
        $info['costo_venta'][$factura->id_unidad]->ucodigo   = $factura->ucodigo;
        $info['costo_venta'][$factura->id_unidad]->bultos    = $factura->cantidad;
        $info['costo_venta'][$factura->id_unidad]->kilos     = $factura->cantidad*$factura->ucantidad;
        $info['costo_venta'][$factura->id_unidad]->precio    = isset($precio->precio)? $precio->precio: 0;
      }

      if (isset($info['rendimientos'][$factura->id_clasificacion]) ) {
        $info['rendimientos'][$factura->id_clasificacion]->bultos += $factura->cantidad;
        $info['rendimientos'][$factura->id_clasificacion]->kilos  += $factura->cantidad*$factura->ucantidad;
      } else {
        $info['rendimientos'][$factura->id_clasificacion] = new stdClass();
        $info['rendimientos'][$factura->id_clasificacion]->id_clasificacion = $factura->id_clasificacion;
        $info['rendimientos'][$factura->id_clasificacion]->cnombre = $factura->cnombre;
        $info['rendimientos'][$factura->id_clasificacion]->ccodigo = $factura->ccodigo;
        $info['rendimientos'][$factura->id_clasificacion]->bultos  = $factura->cantidad;
        $info['rendimientos'][$factura->id_clasificacion]->kilos   = $factura->cantidad*$factura->ucantidad;
      }
    }
    foreach ($info['existencia'] as $key => $existencia) {
      if (isset($info['costo_venta'][$existencia->id_unidad]) ) {
        $info['costo_venta'][$existencia->id_unidad]->bultos += $existencia->bultos;
        $info['costo_venta'][$existencia->id_unidad]->kilos  += $existencia->bultos*$existencia->ucantidad;
      } else {
        $precio = $this->db->query("SELECT precio FROM cuentas_rendimiento_costo_venta
          WHERE id_area = {$id_area} AND fecha = '{$fecha}' AND id_unidad = {$existencia->id_unidad}")->row();
        $info['costo_venta'][$existencia->id_unidad] = new stdClass();
        $info['costo_venta'][$existencia->id_unidad]->id_unidad = $existencia->id_unidad;
        $info['costo_venta'][$existencia->id_unidad]->ucodigo   = $existencia->ucodigo;
        $info['costo_venta'][$existencia->id_unidad]->bultos    = $existencia->bultos;
        $info['costo_venta'][$existencia->id_unidad]->kilos     = $existencia->bultos*$existencia->ucantidad;
        $info['costo_venta'][$existencia->id_unidad]->precio    = isset($precio->precio)? $precio->precio: 0;
      }

      if (isset($info['rendimientos'][$existencia->id_clasificacion]) ) {
        $info['rendimientos'][$existencia->id_clasificacion]->bultos += $existencia->bultos;
        $info['rendimientos'][$existencia->id_clasificacion]->kilos  += $existencia->bultos*$existencia->ucantidad;
      } else {
        $info['rendimientos'][$existencia->id_clasificacion] = new stdClass();
        $info['rendimientos'][$existencia->id_clasificacion]->id_clasificacion = $existencia->id_clasificacion;
        $info['rendimientos'][$existencia->id_clasificacion]->cnombre = $existencia->cnombre;
        $info['rendimientos'][$existencia->id_clasificacion]->ccodigo = $existencia->ccodigo;
        $info['rendimientos'][$existencia->id_clasificacion]->bultos  = $existencia->bultos;
        $info['rendimientos'][$existencia->id_clasificacion]->kilos   = $existencia->bultos*$existencia->ucantidad;
      }
    }
    foreach ($info['existencia_anterior'] as $key => $existencia) {
      if (isset($info['costo_venta'][$existencia->id_unidad]) ) {
        $info['costo_venta'][$existencia->id_unidad]->bultos -= $existencia->cantidad;
        $info['costo_venta'][$existencia->id_unidad]->kilos  -= $existencia->cantidad*$existencia->ucantidad;
      } else {
        $precio = $this->db->query("SELECT precio FROM cuentas_rendimiento_costo_venta
          WHERE id_area = {$id_area} AND fecha = '{$fecha}' AND id_unidad = {$existencia->id_unidad}")->row();
        $info['costo_venta'][$existencia->id_unidad] = new stdClass();
        $info['costo_venta'][$existencia->id_unidad]->id_unidad = $existencia->id_unidad;
        $info['costo_venta'][$existencia->id_unidad]->ucodigo   = $existencia->ucodigo;
        $info['costo_venta'][$existencia->id_unidad]->bultos    = -1*$existencia->cantidad;
        $info['costo_venta'][$existencia->id_unidad]->kilos     = -1*$existencia->cantidad*$existencia->ucantidad;
        $info['costo_venta'][$existencia->id_unidad]->precio    = isset($precio->precio)? $precio->precio: 0;
      }

      if (isset($info['rendimientos'][$existencia->id_clasificacion]) ) {
        $info['rendimientos'][$existencia->id_clasificacion]->bultos -= $existencia->cantidad;
        $info['rendimientos'][$existencia->id_clasificacion]->kilos  -= $existencia->cantidad*$existencia->ucantidad;
      } else {
        $info['rendimientos'][$existencia->id_clasificacion] = new stdClass();
        $info['rendimientos'][$existencia->id_clasificacion]->id_clasificacion = $existencia->id_clasificacion;
        $info['rendimientos'][$existencia->id_clasificacion]->cnombre          = $existencia->cnombre;
        $info['rendimientos'][$existencia->id_clasificacion]->ccodigo          = $existencia->ccodigo;
        $info['rendimientos'][$existencia->id_clasificacion]->bultos           = -1*$existencia->cantidad;
        $info['rendimientos'][$existencia->id_clasificacion]->kilos            = -1*$existencia->cantidad*$existencia->ucantidad;
      }
    }
    foreach ($info['compras_empacadas'] as $key => $empacada) {
      if (isset($info['costo_venta'][$empacada->id_unidad]) ) {
        $info['costo_venta'][$empacada->id_unidad]->bultos -= $empacada->bultos;
        $info['costo_venta'][$empacada->id_unidad]->kilos  -= $empacada->bultos*$empacada->ucantidad;
      } else {
        $precio = $this->db->query("SELECT precio FROM cuentas_rendimiento_costo_venta
          WHERE id_area = {$id_area} AND fecha = '{$fecha}' AND id_unidad = {$empacada->id_unidad}")->row();
        $info['costo_venta'][$empacada->id_unidad] = new stdClass();
        $info['costo_venta'][$empacada->id_unidad]->id_unidad = $empacada->id_unidad;
        $info['costo_venta'][$empacada->id_unidad]->ucodigo   = $empacada->ucodigo;
        $info['costo_venta'][$empacada->id_unidad]->bultos    = -1*$empacada->bultos;
        $info['costo_venta'][$empacada->id_unidad]->kilos     = -1*$empacada->bultos*$empacada->ucantidad;
        $info['costo_venta'][$empacada->id_unidad]->precio    = isset($precio->precio)? $precio->precio: 0;
      }

      if ($info['costo_venta'][$empacada->id_unidad]->bultos <= 0)
        unset($info['costo_venta'][$empacada->id_unidad]);

      if (isset($info['rendimientos'][$empacada->id_clasificacion]) ) {
        $info['rendimientos'][$empacada->id_clasificacion]->bultos -= $empacada->bultos;
        $info['rendimientos'][$empacada->id_clasificacion]->kilos  -= $empacada->bultos*$empacada->ucantidad;
      } else {
        $info['rendimientos'][$empacada->id_clasificacion] = new stdClass();
        $info['rendimientos'][$empacada->id_clasificacion]->id_clasificacion = $empacada->id_clasificacion;
        $info['rendimientos'][$empacada->id_clasificacion]->cnombre          = $empacada->cnombre;
        $info['rendimientos'][$empacada->id_clasificacion]->ccodigo          = $empacada->ccodigo;
        $info['rendimientos'][$empacada->id_clasificacion]->bultos           = -1*$empacada->bultos;
        $info['rendimientos'][$empacada->id_clasificacion]->kilos            = -1*$empacada->bultos*$empacada->ucantidad;
      }

      if ($info['rendimientos'][$empacada->id_clasificacion]->bultos <= 0)
        unset($info['rendimientos'][$empacada->id_clasificacion]);
    }


    return $info;
  }

  public function guardar($data)
  {
    // informacion del dia
    $result = $this->db->query("SELECT id
                               FROM cuentas_rendimiento
                               WHERE fecha = '{$data['fecha_caja_chica']}' AND id_area = {$data['id_area']}")->row();
    if (isset($result->id) && $result->id > 0) {
      $this->db->update('cuentas_rendimiento', array('descuento_parcial' => $data['descuento_parcial_ventas']),
        "fecha = '{$data['fecha_caja_chica']}' AND id_area = {$data['id_area']}");
    } else {
      $this->db->insert('cuentas_rendimiento',
        array('fecha' => $data['fecha_caja_chica'], 'id_area' => $data['id_area'],
          'status' => $data['cerrar_dia'], 'descuento_parcial' => $data['descuento_parcial_ventas']));
    }

    // exitencias
    $exitencias = array();
    if (isset($data['prod_did_prod']) && count($data['prod_did_prod']) > 0) {
      foreach ($data['prod_did_prod'] as $key => $ingreso)
      {
        $exitencias[] = array(
          'fecha'            => $data['fecha_caja_chica'],
          'id_area'          => $data['id_area'],
          'id_clasificacion' => $data['prod_did_prod'][$key],
          'row'              => $key,
          'id_unidad'        => $data['prod_dmedida'][$key],
          'bultos'           => $data['prod_bultos'][$key],
          'precio'           => $data['prod_precio'][$key],
        );
      }
    }

    $this->db->delete('cuentas_rendimiento_existencia', array('fecha' => $data['fecha_caja_chica'], 'id_area' => $data['id_area']));
    if (count($exitencias) > 0)
    {
      $this->db->insert_batch('cuentas_rendimiento_existencia', $exitencias);
    }

    // compras
    $compras = array();
    if (isset($data['prod_did_prod']) && count($data['prod_did_prod']) > 0) {
      foreach ($data['compe_did_prod'] as $key => $ingreso)
      {
        $compras[] = array(
          'fecha'            => $data['fecha_caja_chica'],
          'id_area'          => $data['id_area'],
          'id_clasificacion' => $data['compe_did_prod'][$key],
          'row'              => $key,
          'id_unidad'        => $data['compe_dmedida'][$key],
          'bultos'           => $data['compe_bultos'][$key],
          'precio'           => $data['compe_precio'][$key],
        );
      }
    }

    $this->db->delete('cuentas_rendimiento_compras_empacadas', array('fecha' => $data['fecha_caja_chica'], 'id_area' => $data['id_area']));
    if (count($compras) > 0)
    {
      $this->db->insert_batch('cuentas_rendimiento_compras_empacadas', $compras);
    }

    // compra tecoman ingresos x mov
    $movimientos_bascula = array();
    if (isset($data['ingresos_movimientos_bascula_importe']) && count($data['ingresos_movimientos_bascula_importe']) > 0) {
      foreach ($data['ingresos_movimientos_bascula_importe'] as $key => $ingreso)
      {
        if ($data['ingresos_movimientos_bascula_caja'][$key] > 0 &&
            $data['ingresos_movimientos_bascula_kilos'][$key] > 0 &&
            $data['ingresos_movimientos_bascula_precio'][$key] > 0 &&
            $data['ingresos_movimientos_bascula_importe'][$key] > 0) {
          $movimientos_bascula[] = array(
            'fecha'   => $data['fecha_caja_chica'],
            'id_area' => $data['id_area'],
            'row'     => $key,
            'bultos'  => $data['ingresos_movimientos_bascula_caja'][$key],
            'kilos'   => $data['ingresos_movimientos_bascula_kilos'][$key],
            'precio'  => $data['ingresos_movimientos_bascula_precio'][$key],
            'importe' => $data['ingresos_movimientos_bascula_importe'][$key],
          );
        }
      }
    }

    $this->db->delete('cuentas_rendimiento_compras_tecoman', array('fecha' => $data['fecha_caja_chica'], 'id_area' => $data['id_area']));
    if (count($movimientos_bascula) > 0)
    {
      $this->db->insert_batch('cuentas_rendimiento_compras_tecoman', $movimientos_bascula);
    }

    // apatzingan
    $apatzingan = array();
    if (isset($data['prod_did_prod']) && count($data['prod_did_prod']) > 0) {
      foreach ($data['apatzin_ddescripcion'] as $key => $ingreso)
      {
        $apatzingan[] = array(
          'fecha'   => $data['fecha_caja_chica'],
          'id_area' => $data['id_area'],
          'row'     => $key,
          'unidad'  => $data['apatzin_dmedida'][$key],
          'precio'  => $data['apatzin_precio'][$key],
          'nombre'  => $data['apatzin_ddescripcion'][$key],
        );
      }
    }

    $this->db->delete('cuentas_rendimiento_apatzingan', array('fecha' => $data['fecha_caja_chica'], 'id_area' => $data['id_area']));
    if (count($apatzingan) > 0)
    {
      $this->db->insert_batch('cuentas_rendimiento_apatzingan', $apatzingan);
    }

    // costo_venta
    $costo_venta = array();
    if (isset($data['prod_did_prod']) && count($data['prod_did_prod']) > 0) {
      foreach ($data['costo_venta_id_unidad'] as $key => $ingreso)
      {
        $costo_venta[] = array(
          'fecha'     => $data['fecha_caja_chica'],
          'id_area'   => $data['id_area'],
          'row'       => $key,
          'id_unidad' => $data['costo_venta_id_unidad'][$key],
          'precio'    => $data['costo_venta_precio'][$key],
        );
      }
    }

    $this->db->delete('cuentas_rendimiento_costo_venta', array('fecha' => $data['fecha_caja_chica'], 'id_area' => $data['id_area']));
    if (count($costo_venta) > 0)
    {
      $this->db->insert_batch('cuentas_rendimiento_costo_venta', $costo_venta);
    }

    // industrial
    $this->db->delete('cuentas_rendimiento_industrial', array('fecha' => $data['fecha_caja_chica'], 'id_area' => $data['id_area']));
    $this->db->insert('cuentas_rendimiento_industrial', array(
      'fecha'     => $data['fecha_caja_chica'],
      'id_area'   => $data['id_area'],
      'precio'    => $data['industrial_precio'],
    ));



    return true;
  }


  public function printRendimiento($fecha, $area)
  {
    $this->load->model('compras_areas_model');

    $rpt = $this->get($fecha, $area);

    // echo "<pre>";
    //   var_dump($caja);
    // echo "</pre>";exit;

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    $pdf->titulo2 = 'Reporte de cuentas y rendimientos '.$fecha;
    $pdf->AliasNbPages();
    $pdf->AddPage();



    $pdf->limiteY = 235; //limite de alto

    // Ingresos facturas
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetXY(6, 32);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array('INGRESOS'), true, true);

    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(15, 50, 27, 28, 20, 20, 20, 20));
    $pdf->Row(array('REMISION', 'CLIENTE', 'CLASIF', 'CODIGO', 'BULTOS', 'KGS', 'PRECIO', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);

    $total_facturas = $total_bultos = $total_kilos = 0;
    foreach ($rpt['facturas'] as $key => $factura) {
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $total_facturas += floatval($factura->importe);
      $total_bultos += floatval($factura->cantidad);
      $total_kilos += floatval($factura->kgs);

      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array(
        $factura->serie.$factura->folio,
        $factura->nombre_fiscal,
        $factura->ccodigo,
        $factura->ucodigo,
        String::formatoNumero($factura->cantidad, 2, ''),
        String::formatoNumero($factura->kgs, 2, ''),
        String::formatoNumero($factura->precio_unitario, 2, '$'),
        String::formatoNumero($factura->importe, 2, '$'),
        ), false, true);
    }
    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(126);
    $pdf->SetAligns(array('R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(20, 20, 20, 20));
    $pdf->Row(array(
        String::formatoNumero($total_bultos, 2, ''),
        String::formatoNumero($total_kilos, 2, ''),
        '',
        String::formatoNumero($total_facturas, 2, '$'),
        ), true, true);

    // EXISTENCIA
    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array('EXISTENCIA DEL DIA'), true, true);

    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(50, 50, 25, 25, 25, 25));
    $pdf->Row(array('CLASIF', 'CODIGO', 'BULTOS', 'KGS', 'PRECIO', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);

    $total_existencias = $total_exis_bultos = $total_exis_kilos = 0;
    foreach ($rpt['existencia'] as $existencia) {
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $total_existencias += $existencia->importe;
      $total_exis_bultos += $existencia->bultos;
      $total_exis_kilos  += $existencia->kgs;

      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array(
        $existencia->cnombre,
        $existencia->ucodigo,
        String::formatoNumero($existencia->bultos, 2, ''),
        String::formatoNumero($existencia->kgs, 2, ''),
        String::formatoNumero($existencia->precio, 2, '$'),
        String::formatoNumero($existencia->importe, 2, '$'),
        ), false, true);
    }
    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(100, 25, 25, 25, 25));
    $pdf->Row(array('TOTAL',
        String::formatoNumero($total_exis_bultos, 2, ''),
        String::formatoNumero($total_exis_kilos, 2, ''),
        '--',
        String::formatoNumero($total_existencias, 2, '$'),
        ), true, true);
    $pdf->SetX(6);
    $descuento_parcial_ventas = (isset($rpt['info']->descuento_parcial)? $rpt['info']->descuento_parcial: 0);
    $pdf->Row(array('DESCUENTO PARCIAL S/O VENTA',
        '--',
        '--',
        '--',
        String::formatoNumero($descuento_parcial_ventas, 2, '$'),
        ), true, true);
    $pdf->SetX(6);
    $pdf->Row(array('SUMA TOTALES',
        String::formatoNumero($total_exis_bultos+$total_bultos, 2, ''),
        String::formatoNumero($total_exis_kilos+$total_kilos, 2, ''),
        '--',
        String::formatoNumero($total_existencias+$total_facturas-$descuento_parcial_ventas, 2, '$'),
        ), true, true);

    // OTROS INGRESOS
    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array('OTROS INGRESOS'), true, true);

    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'R', 'L', 'R', 'R'));
    $pdf->SetWidths(array(60, 30, 50, 30, 30));
    $pdf->Row(array('CLASIF', 'BULTOS', 'UNIDAD', 'PRECIO', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);

    $total_otsingr = $total_otsingr_bultos = 0;
    foreach ($rpt['otros_ingresos'] as $key => $otr_ingreso) {
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $total_otsingr += floatval($otr_ingreso->importe);
      $total_otsingr_bultos += floatval($otr_ingreso->cantidad);

      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array(
        "({$otr_ingreso->ccodigo}) ".$otr_ingreso->cnombre,
        String::formatoNumero($otr_ingreso->cantidad, 2, ''),
        $otr_ingreso->unidad,
        String::formatoNumero($otr_ingreso->precio_unitario, 2, '$'),
        String::formatoNumero($otr_ingreso->importe, 2, '$')
        ), false, true);
    }
    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(66);
    $pdf->SetAligns(array('R', 'L', 'R', 'R'));
    $pdf->SetWidths(array(30, 50, 30, 30));
    $pdf->Row(array(
        String::formatoNumero($total_otsingr_bultos, 2, ''),
        '',
        '',
        String::formatoNumero($total_otsingr, 2, '$'),
        ), true, true);

    // TOTAL DE INGRESOS
    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('R', 'R', 'R'));
    $pdf->SetWidths(array(120, 40, 40));
    $pdf->Row(array('TOTAL DE INGRESOS',
      String::formatoNumero($total_otsingr_bultos+$total_exis_bultos+$total_bultos, 2, ''),
      String::formatoNumero($total_otsingr+$total_existencias+$total_facturas, 2, '$')
    ), true, true);

    // ************************************************

    // EXISTENCIA ANTERIOR
    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array('EXISTENCIA ANTERIOR'), true, true);

    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(50, 50, 25, 25, 25, 25));
    $pdf->Row(array('CLASIF', 'CODIGO', 'BULTOS', 'KGS', 'PRECIO', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);

    $total_exis_anterior = $total_exis_anterior_bultos = $total_exis_anterior_kgs = 0;
    foreach ($rpt['existencia_anterior'] as $key => $existen_anterior) {
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $total_exis_anterior        += floatval($existen_anterior->importe);
      $total_exis_anterior_bultos += floatval($existen_anterior->cantidad);
      $total_exis_anterior_kgs    += floatval($existen_anterior->kgs);

      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array(
        $existen_anterior->ccodigo,
        $existen_anterior->ucodigo,
        String::formatoNumero($existen_anterior->cantidad, 2, ''),
        String::formatoNumero($existen_anterior->kgs, 2, ''),
        String::formatoNumero($existen_anterior->precio, 2, '$'),
        String::formatoNumero($existen_anterior->importe, 2, '$'),
        ), false, true);
    }
    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(106);
    $pdf->SetAligns(array('R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(25, 25, 25, 25));
    $pdf->Row(array(
        String::formatoNumero($total_exis_anterior_bultos, 2, ''),
        String::formatoNumero($total_exis_anterior_kgs, 2, ''),
        '',
        String::formatoNumero($total_exis_anterior, 2, '$'),
        ), true, true);

    // COMPRA EMPACADA
    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array('COMPRA EMPACADA'), true, true);

    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(50, 50, 25, 25, 25, 25));
    $pdf->Row(array('CLASIF', 'CODIGO', 'BULTOS', 'KGS', 'PRECIO', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);

    $total_compra_empa = $total_compra_empa_bultos = $total_compra_empa_kilos = 0;
    foreach ($rpt['compras_empacadas'] as $comp_emp) {
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $total_compra_empa        += $comp_emp->importe;
      $total_compra_empa_bultos += $comp_emp->bultos;
      $total_compra_empa_kilos  += $comp_emp->kgs;

      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array(
        $comp_emp->ccodigo,
        $comp_emp->ucodigo,
        String::formatoNumero($comp_emp->bultos, 2, ''),
        String::formatoNumero($comp_emp->kgs, 2, ''),
        String::formatoNumero($comp_emp->precio, 2, '$'),
        String::formatoNumero($comp_emp->importe, 2, '$'),
        ), false, true);
    }
    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(100, 25, 25, 25, 25));
    $pdf->Row(array('TOTAL',
        String::formatoNumero($total_compra_empa_bultos, 2, ''),
        String::formatoNumero($total_compra_empa_kilos, 2, ''),
        '--',
        String::formatoNumero($total_compra_empa, 2, '$'),
        ), true, true);
    $pdf->SetX(6);
    $descuento_parcial_ventas = (isset($rpt['info']->descuento_parcial)? $rpt['info']->descuento_parcial: 0);
    $pdf->Row(array('SUMA TOTALES',
        String::formatoNumero($total_exis_anterior_bultos+$total_compra_empa_bultos, 2, ''),
        String::formatoNumero($total_exis_anterior_kgs+$total_compra_empa_kilos, 2, ''),
        '--',
        String::formatoNumero($total_exis_anterior+$total_compra_empa, 2, '$'),
        ), true, true);
    $pdf->SetX(6);
    $pdf->Row(array('VENTA NETA DEL DIA',
        String::formatoNumero(($total_exis_bultos+$total_bultos)-($total_exis_anterior_bultos+$total_compra_empa_bultos), 2, ''),
        String::formatoNumero(($total_exis_kilos+$total_kilos)-($total_exis_anterior_kgs+$total_compra_empa_kilos), 2, ''),
        '--',
        String::formatoNumero(($total_otsingr+$total_existencias+$total_facturas-$descuento_parcial_ventas)-($total_exis_anterior+$total_compra_empa), 2, '$'),
        ), true, true);

    // COMPRAS LIMON TECOMAN
    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array('COMPRAS LIMON TECOMAN'), true, true);

    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(80, 30, 30, 30, 30));
    $pdf->Row(array('NOMBRE', 'BULTOS', 'KGS', 'PRECIO', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);

    $total_compra_bascula = $total_compra_bascula_bultos = $total_compra_bascula_kgs = 0;
    foreach ($rpt['compras_bascula'] as $key => $compra) {
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $total_compra_bascula        += floatval($compra->importe);
      $total_compra_bascula_bultos += floatval($compra->cajas);
      $total_compra_bascula_kgs    += floatval($compra->kilos);

      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array(
        $compra->nombre,
        String::formatoNumero($compra->cajas, 2, ''),
        String::formatoNumero($compra->kilos, 2, ''),
        String::formatoNumero($compra->precio, 2, '$'),
        String::formatoNumero($compra->importe, 2, '$')
        ), false, true);
    }
    if(count($rpt['ingresos_movimientos_bascula']) > 0) {
      foreach ($rpt['ingresos_movimientos_bascula'] as $key => $compra) {
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

        $total_compra_bascula        += floatval($compra->importe);
        $total_compra_bascula_bultos += floatval($compra->cajas);
        $total_compra_bascula_kgs    += floatval($compra->kilos);

        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row(array(
          'INGRESOS X MOVIMIENTOS',
          String::formatoNumero($compra->cajas, 2, ''),
          String::formatoNumero($compra->kilos, 2, ''),
          String::formatoNumero($compra->precio, 2, '$'),
          String::formatoNumero($compra->importe, 2, '$')
          ), false, true);
      }
    }
    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(80, 30, 30, 30, 30));
    $pdf->Row(array('TOTAL',
        String::formatoNumero($total_compra_bascula_bultos, 2, ''),
        String::formatoNumero($total_compra_bascula_kgs, 2, ''),
        '',
        String::formatoNumero($total_compra_bascula, 2, '$'),
        ), true, true);

    // OTROS EGRESOS DE APATZINGAN
    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array('OTROS EGRESOS DE APATZINGAN'), true, true);

    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'L', 'R', 'R'));
    $pdf->SetWidths(array(80, 60, 30, 30));
    $pdf->Row(array('NOMBRE', 'UNIDAD', 'PRECIO', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);

    $total_apatzin = 0;
    foreach ($rpt['compras_apatzingan'] as $apatzin) {
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $total_apatzin += $apatzin->importe;

      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array(
        $apatzin->nombre,
        $apatzin->unidad,
        String::formatoNumero($apatzin->precio, 2, '$'),
        String::formatoNumero($apatzin->importe, 2, '$'),
        ), false, true);
    }
    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(176);
    $pdf->SetAligns(array( 'R'));
    $pdf->SetWidths(array( 30));
    $pdf->Row(array(
        String::formatoNumero($total_apatzin, 2, '$'),
        ), true, true);

    // COSTO DE VENTA
    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array('COSTO DE VENTA'), true, true);

    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(50, 30, 30, 30, 30, 30));
    $pdf->Row(array('CODIGO', 'KGS/BULTOS', 'BULTOS', 'KGS', 'PRECIO', 'IMPORTE'), true, true);

    $pdf->SetFont('Arial','', 6);

    $total_costo_venta = $total_costo_venta_bultos = $total_costo_venta_kgs = 0;
    foreach ($rpt['costo_venta'] as $key => $costo_venta) {
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $importe = $costo_venta->bultos*$costo_venta->precio;
      $total_costo_venta        += $importe;
      $total_costo_venta_bultos += floatval($costo_venta->bultos);
      $total_costo_venta_kgs    += floatval($costo_venta->kilos);

      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array(
        $costo_venta->ucodigo,
        $costo_venta->kilos/$costo_venta->bultos,
        String::formatoNumero($costo_venta->bultos, 2, ''),
        String::formatoNumero($costo_venta->kilos, 2, ''),
        String::formatoNumero($costo_venta->precio, 2, '$'),
        String::formatoNumero($importe, 2, '$'),
        ), false, true);
    }
    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(86);
    $pdf->SetAligns(array('R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(30, 30, 30, 30));
    $pdf->Row(array(
        String::formatoNumero($total_costo_venta_bultos, 2, ''),
        String::formatoNumero($total_costo_venta_kgs, 2, ''),
        '',
        String::formatoNumero($total_costo_venta, 2, '$'),
        ), true, true);

    // INDUSTRIAL PROCESO
    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array('INDUSTRIAL PROCESO'), true, true);

    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('R', 'R', 'R'));
    $pdf->SetWidths(array(67, 67, 66));
    $pdf->Row(array('KGS', 'PRECIO', 'IMPORTE'), true, true);

    $total_industrial_kilos  = abs($total_kilos+$total_exis_kilos-$total_exis_anterior_kgs-$total_compra_empa_kilos-$total_compra_bascula_kgs);
    $total_industrial_precio = isset($rpt['industrial']->precio)? $rpt['industrial']->precio : 0;
    $total_industrial        = $total_industrial_precio*$total_industrial_kilos;

    $total_porsn_kilos = $total_costo_venta_kgs+$total_industrial_kilos;

    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('R', 'R', 'R'));
    $pdf->SetWidths(array(67, 67, 66));
    $pdf->Row(array(
        String::formatoNumero($total_industrial_kilos, 2, ''),
        String::formatoNumero($total_industrial_precio, 2, ''),
        String::formatoNumero($total_industrial, 2, '$'),
        ), true, true);

    // TABLA GENERAL DE RENDIMIENTOS
    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','B', 7);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetFillColor(230, 230, 230);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'C'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array('TABLA GENERAL DE RENDIMIENTOS'), true, true);

    if($pdf->GetY() >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Arial','', 6);
    $pdf->SetX(6);
    $pdf->SetAligns(array('L', 'R', 'L', 'R', 'R'));
    $pdf->SetWidths(array(60, 30, 50, 30, 30));
    $pdf->Row(array('CLASIF', '%', 'CODIGO', 'BULTOS', 'KILOS'), true, true);

    $pdf->SetFont('Arial','', 6);

    foreach ($rpt['rendimientos'] as $key => $rendimiento) {
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array(
        $rendimiento->cnombre,
        String::formatoNumero($rendimiento->kilos*100/$total_porsn_kilos, 2, '').' %',
        $rendimiento->ccodigo,
        String::formatoNumero($rendimiento->bultos, 2, ''),
        String::formatoNumero($rendimiento->kilos, 2, ''),
        ), false, true);
    }


    $pdf->Output('rendimiento.pdf', 'I');
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
        FROM cajachica_gastos cg
          INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
          INNER JOIN cajachica_nomenclaturas cn ON cn.id = cg.id_nomenclatura
          LEFT JOIN compras_areas ca ON ca.id_area = cg.id_area
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

    $this->load->model('compras_areas_model');

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
          $codigoAreas[$producto->id_area] = $this->compras_areas_model->getDescripCodigo($producto->id_area);

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

/* End of file caja_chica_model.php */
/* Location: ./application/models/caja_chica_model.php */