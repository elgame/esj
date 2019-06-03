<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class recetas_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
    $this->load->model('bitacora_model');
  }

  /**
   * Obtiene el listado de facturas
   *
   * @return
   */
  public function getProductosFaltantes($aplicar=false)
  {
    $sql = '';

    $joins = $fields = '';
    if ($aplicar) {
      $joins = "LEFT JOIN compras_requisicion_centro_costo crc ON cr.id_requisicion = crc.id_requisicion
        LEFT JOIN compras_requisicion_rancho crr ON cr.id_requisicion = crr.id_requisicion";
      $fields = ", (Sum(crq.precio_unitario)/Sum(crq.cantidad)) AS precio_unitario, Sum(crq.importe) AS importe,
        Sum(crq.iva) AS iva, Sum(crq.retencion_iva) AS retencion_iva, Sum(crq.total) AS total,
        (Sum(crq.porcentaje_iva)/Sum(crq.cantidad)) AS porcentaje_iva, (Sum(crq.porcentaje_retencion)/Sum(crq.cantidad)) AS porcentaje_retencion,
        Sum(crq.ieps) AS ieps, (Sum(crq.porcentaje_ieps)/Sum(crq.cantidad)) AS porcentaje_ieps,
        Sum(crq.retencion_isr) AS retencion_isr, (Sum(crq.porcentaje_isr)/Sum(crq.cantidad)) AS porcentaje_isr,
        string_agg(crc.id_centro_costo::text, ', ') AS ids_centros_costos, string_agg(crr.id_rancho::text, ', ') AS ids_ranchos,
        string_agg(cr.id_area::text, ', ') AS ids_areas, string_agg(cr.id_activo::text, ', ') AS ids_activos,
        string_agg(cr.solicito, ', ') AS empleado_solicito";
    }

    if($this->input->get('did_empresa') != '')
    {
      // $sql .= " AND cr.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    $res = $this->db->query("SELECT string_agg(cr.id_requisicion::text, ', ') AS ids_requisicion, p.id_producto, cr.id_almacen,
        e.id_empresa, e.nombre_fiscal AS empresa, string_agg(cr.folio::text, ', ') AS folio,
        p.nombre AS producto, string_agg(crq.num_row::text, ', ') AS num_rows,
        pr.id_proveedor, pr.nombre_fiscal AS proveedor, Sum(crq.cantidad) AS cantidad, pu.abreviatura AS unidad
        {$fields}
      FROM compras_requisicion cr
        INNER JOIN compras_requisicion_productos crq ON cr.id_requisicion = crq.id_requisicion
        INNER JOIN productos p ON p.id_producto = crq.id_producto
        INNER JOIN productos_unidades pu ON pu.id_unidad = p.id_unidad
        INNER JOIN proveedores pr ON pr.id_proveedor = crq.id_proveedor
        INNER JOIN empresas e ON e.id_empresa = cr.id_empresa
        {$joins}
      WHERE cr.status = 'p' AND cr.tipo_orden = 'p' AND cr.autorizado = 'f' AND cr.id_autorizo IS NULL
        AND cr.es_receta = 't' AND crq.importe > 0
        {$sql}
      GROUP BY p.id_producto, cr.id_almacen, e.id_empresa, pr.id_proveedor, pu.id_unidad
      ORDER BY (e.id_empresa, cr.id_almacen, p.id_producto) ASC
    ");

    $productos = array();
    if($res->num_rows() > 0) {
      $this->load->model('inventario_model');
      $data = $res->result();
      foreach ($data as $key => $value) {
        $item = $this->inventario_model->getEPUData($value->id_producto, $value->id_almacen, true, ['empresa' => true]);
        $existencia = MyString::float( $item[0]->saldo_anterior+$item[0]->entradas-$item[0]->salidas-$item[0]->con_req );
        if ( MyString::float($existencia) < 0) {
          $value->faltantes = $existencia * -1;
          $productos[] = $value;
        }
      }
    }

    return $productos;
  }

  public function crearOrdenesFaltantes()
  {
    $this->load->model('compras_ordenes_model');

    $data = $this->info($idOrden, true, false)['info'][0];

    $ordenes = [];
    $productos = [];

    $keyorden = '';
    $rows_compras = 0;
    foreach ($data as $key => $value)
    {
      $keyaux = $value->id_empresa.$value->id_almacen.$value->id_proveedor;
      if ($keyorden !== $keyaux) {
        $ordenes[$keyaux] = [
          'id_empresa'         => $value->id_empresa,
          'id_proveedor'       => $value->id_proveedor,
          'id_departamento'    => 1, // <-
          'id_empleado'        => $this->session->userdata('id_usuario'),
          'folio'              => $this->compras_ordenes_model->folio('p'),
          'status'             => 'p',
          'autorizado'         => 't',
          'fecha_autorizacion' => date("Y-m-d H:i:s"),
          'fecha_aceptacion'   => date("Y-m-d H:i:s"),
          'fecha_creacion'     => date("Y-m-d H:i:s"),
          'tipo_pago'          => 'cr',
          'tipo_orden'         => 'p',
          'solicito'           => $value->empleado_solicito,
          'id_cliente'         => NULL,
          'descripcion'        => 'Orden creada de productos faltantes para surtir recetas',
          'id_autorizo'        => $this->session->userdata('id_usuario'),
          'id_almacen'         => $value->id_almacen,
        ];

        $keyorden = $keyaux;
        $rows_compras = 0;
      }

      $productos[$keyaux][] = [
        'id_orden'             => '',
        'num_row'              => $rows_compras,
        'id_producto'          => $value->id_producto,
        'id_presentacion'      => null, // <-
        'descripcion'          => $value->producto,
        'cantidad'             => $value->cantidad,
        'precio_unitario'      => $value->precio_unitario,
        'importe'              => $value->importe,
        'iva'                  => $value->iva,
        'retencion_iva'        => $value->retencion_iva,
        'total'                => $value->total,
        'porcentaje_iva'       => $value->porcentaje_iva,
        'porcentaje_retencion' => $value->porcentaje_retencion,
        'faltantes'            => '0',
        'observacion'          => '',
        'ieps'                 => $value->ieps,
        'porcentaje_ieps'      => $value->porcentaje_ieps,
        'tipo_cambio'          => $prod->tipo_cambio,
        // 'id_area'              => $prod->id_area,
        'id_cat_codigos'       => '', // <-
        'retencion_isr'        => $value->retencion_isr,
        'porcentaje_isr'       => $value->porcentaje_isr,
      ];

      $rows_compras++;
    }




    // Se crean los ordenes de compra con productos por proveedor
    foreach ($data->proveedores as $key => $value)
    {
      if(isset($value['productos']) && count($value['productos']) > 0)
      {
        $dataOrdenCats = null;
        $dataOrden = array(
          'id_empresa'         => $data->id_empresa,
          'id_proveedor'       => $value['id_proveedor'],
          'id_departamento'    => $data->id_departamento,
          'id_empleado'        => $data->id_empleado,
          'folio'              => $this->compras_ordenes_model->folio($data->tipo_orden),
          'status'             => 'p',
          'autorizado'         => 't',
          'fecha_autorizacion' => $data->fecha_autorizacion,
          'fecha_aceptacion'   => substr($data->fecha_aceptacion, 0, 19),
          'fecha_creacion'     => $data->fecha,
          'tipo_pago'          => $data->tipo_pago,
          'tipo_orden'         => $data->tipo_orden,
          'solicito'           => $data->empleado_solicito,
          'id_cliente'         => (is_numeric($data->id_cliente)? $data->id_cliente: NULL),
          'descripcion'        => $data->descripcion,
          'id_autorizo'        => $data->id_autorizo,
          'id_almacen'         => $data->id_almacen,
        );

        $dataOrdenCats['requisiciones'][] = [
          'id_requisicion' => $idOrden,
          'id_orden'       => '',
          'num_row'        => 0
        ];

        // Si es un gasto son requeridos los campos de catálogos
        if ($data->tipo_orden == 'd' || $data->tipo_orden == 'oc' || $data->tipo_orden == 'f' || $data->tipo_orden == 'a'
            || $data->tipo_orden == 'p') {
          // $dataOrden['id_area']         = !empty($data->id_area)? $data->id_area: NULL;
          // Inserta las areas
          if (isset($data->id_area) && $data->id_area > 0) {
            $dataOrdenCats['area'][] = [
              'id_area' => $data->id_area,
              'id_orden'  => '',
              'num'       => 1
            ];
          }

          // Inserta los ranchos
          if (isset($data->rancho) && count($data->rancho) > 0) {
            foreach ($data->rancho as $keyr => $drancho) {
              $dataOrdenCats['rancho'][] = [
                'id_rancho' => $drancho->id_rancho,
                'id_orden'  => '',
                'num'       => $drancho->num
              ];
            }
          }

          // Inserta los centros de costo
          if (isset($data->centroCosto) && count($data->centroCosto) > 0) {
            foreach ($data->centroCosto as $keyr => $dcentro_costo) {
              $dataOrdenCats['centroCosto'][] = [
                'id_centro_costo' => $dcentro_costo->id_centro_costo,
                'id_orden'        => '',
                'num'             => $dcentro_costo->num
              ];
            }
          }

          if ($data->tipo_orden !== 'a') {
            // Inserta los activos
            if (isset($data->id_activo) && $data->id_activo > 0) {
              $dataOrdenCats['activo'][] = [
                'id_activo' => $data->id_activo,
                'id_orden'  => '',
                'num'       => 1
              ];
            }
            // $dataOrden['id_activo'] = !empty($data->id_activo)? $data->id_activo: NULL;
          }
        }

        //si se registra a un vehiculo
        if (is_numeric($data->id_vehiculo))
        {
          $dataOrden['tipo_vehiculo'] = $data->tipo_vehiculo;
          $dataOrden['id_vehiculo']   = $data->id_vehiculo;
        }
        //si es flete
        if ($data->tipo_orden == 'f')
        {
          $dataOrden['ids_facrem'] = $data->ids_facrem;
          $dataOrden['flete_de']   = $data->flete_de;
        }

        // si se registra a un vehiculo
        $veiculoData = array();
        if (is_numeric($data->id_vehiculo))
        {
          //si es de tipo gasolina o diesel se registra los litros
          if($data->tipo_vehiculo !== 'ot')
          {
            $veiculoData = array(
              'id_orden'   => null,
              'kilometros' => $data->gasolina[0]->kilometros,
              'litros'     => $data->gasolina[0]->litros,
              'precio'     => $data->gasolina[0]->precio,
            );
          }
        }

        // Si trae datos extras
        $dataOrden['otros_datos'] = [];
        if (isset($data->otros_datos->infRecogerProv) || isset($data->otros_datos->infPasarBascula) ||
            isset($data->otros_datos->infEntOrdenCom)) {
          $dataOrden['otros_datos'] = $data->otros_datos;
        }
        $dataOrden['otros_datos'] = json_encode($dataOrden['otros_datos']);

        $res = $this->compras_ordenes_model->agregarData($dataOrden, $veiculoData, $dataOrdenCats);
        $id_orden = $res['id_orden'];

        // Productos
        $rows_compras = 0;
        $productos = array();
        foreach ($value['productos'] as $keypr => $prod)
        {
          $productos[] = array(
            'id_orden'             => $id_orden,
            'num_row'              => $rows_compras,
            'id_producto'          => $prod->id_producto,
            'id_presentacion'      => $prod->id_presentacion !== '' ? $prod->id_presentacion : null,
            'descripcion'          => $prod->descripcion,
            'cantidad'             => $prod->cantidad,
            'precio_unitario'      => $prod->precio_unitario,
            'importe'              => $prod->importe,
            'iva'                  => $prod->iva,
            'retencion_iva'        => $prod->retencion_iva,
            'total'                => $prod->total,
            'porcentaje_iva'       => $prod->porcentaje_iva,
            'porcentaje_retencion' => $prod->porcentaje_retencion,
            'faltantes'            => '0',
            'observacion'          => $prod->observacion,
            'ieps'                 => $prod->ieps,
            'porcentaje_ieps'      => $prod->porcentaje_ieps,
            'tipo_cambio'          => $prod->tipo_cambio,
            // 'id_area'              => $prod->id_area,
            $prod->campo           => $prod->id_area,
            'retencion_isr'        => $prod->retencion_isr,
            'porcentaje_isr'       => $prod->porcentaje_isr,
          );
          $rows_compras++;
        }

        if(count($productos) > 0)
          $this->compras_ordenes_model->agregarProductosData($productos);

      }
    }
  }

}