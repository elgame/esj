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
  public function getProductosFaltantes()
  {
    $sql = '';

    if($this->input->get('did_empresa') != '')
    {
      $sql .= " AND cr.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    $res = $this->db->query("SELECT cr.id_requisicion, p.id_producto, cr.id_almacen, cr.id_empresa, e.nombre_fiscal AS empresa,
        cr.fecha_creacion, cr.folio, p.nombre AS producto, cr.tipo_orden, crq.id_proveedor, pr.nombre_fiscal AS proveedor,
        crq.cantidad, pu.abreviatura AS unidad, crq.precio_unitario, crq.importe
      FROM compras_requisicion cr
        INNER JOIN compras_requisicion_productos crq ON cr.id_requisicion = crq.id_requisicion
        INNER JOIN productos p ON p.id_producto = crq.id_producto
        INNER JOIN productos_unidades pu ON pu.id_unidad = p.id_unidad
        INNER JOIN empresas e ON e.id_empresa = cr.id_empresa
        INNER JOIN proveedores pr ON pr.id_proveedor = crq.id_proveedor
      WHERE cr.status = 'p' AND cr.tipo_orden = 'p' AND cr.autorizado = 'f' AND cr.id_autorizo IS NULL
        AND cr.es_receta = 't' AND crq.importe > 0
        {$sql}
      ORDER BY cr.folio ASC
    ");

    $productos = array();
    if($res->num_rows() > 0) {
      $this->load->model('inventario_model');
      $data = $res->result();
      foreach ($data as $key => $value) {
        $item = $this->inventario_model->getEPUData($value->id_producto, $value->id_almacen, true);
        $existencia = MyString::float( $item[0]->saldo_anterior+$item[0]->entradas-$item[0]->salidas-$item[0]->con_req );
        if ( MyString::float($existencia) < 0) {
          $value->faltantes = $existencia * -1;
          $productos[] = $value;
        }
      }
    }

    return $productos;
  }

}