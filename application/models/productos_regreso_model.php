<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class productos_regreso_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
  }

  /**
   * Agrega la info de una salida sin productos.
   *
   * @return array
   */
  public function agregar($data = null)
  {
    $proveedor = $this->db->query("SELECT id_proveedor FROM proveedores WHERE UPPER(nombre_fiscal)='FICTICIO' LIMIT 1")->row();
    $departamento = $this->db->query("SELECT id_departamento FROM compras_departamentos WHERE UPPER(nombre)='FICTICIO' LIMIT 1")->row();
    $data = array(
      'id_empresa'         => $_POST['empresaId'],
      'id_proveedor'       => $proveedor->id_proveedor,
      'id_departamento'    => $departamento->id_departamento,
      'id_empleado'        => $this->session->userdata('id_usuario'),
      'id_almacen'         => ($this->input->post('id_almacen')>0?$this->input->post('id_almacen'):1),
      'folio'              => $_POST['folio'],
      'status'             => 'n',
      'autorizado'         => 't',
      'solicito'           => $_POST['solicito'],
      'fecha_autorizacion' => $_POST['fecha'],
      'fecha_aceptacion'   => $_POST['fecha'],
      'fecha_creacion'     => $_POST['fecha'],
      'regresa_product'    => 't',
    );

    $res = $this->compras_ordenes_model->agregarData($data);
    $id_orden = $res['id_orden'];

    $compra = array();
    foreach ($_POST['productoId'] as $key => $id) {
      $presenta = $this->db->query("SELECT id_presentacion FROM productos_presentaciones WHERE status = 'ac' AND id_producto = {$id} AND cantidad = 1 LIMIT 1")->row();
      $compra[] = array(
        'id_orden'         => $id_orden,
        'num_row'          => $key,
        'id_producto'      => $id,
        'id_presentacion'  => (count($presenta)>0? $presenta->id_presentacion: NULL),
        'descripcion'      => $_POST['concepto'][$key],
        'cantidad'         => $_POST['cantidad'][$key],
        'precio_unitario'  => $_POST['precioUnit'][$key],
        'importe'          => (abs($_POST['cantidad'][$key])*$_POST['precioUnit'][$key]),
        'total'            => (abs($_POST['cantidad'][$key])*$_POST['precioUnit'][$key]),
        'status'           => 'a',
        'fecha_aceptacion' => $_POST['fecha'],
      );
    }
    if (count($compra) > 0)
      $this->compras_ordenes_model->agregarProductosData($compra);

    return array('passes' => true, 'msg' => 3, 'id_orden' => $id_orden);
  }

  /**
   * Modificar los productos de una salida.
   *
   * @return array
   */
  public function modificarProductos($idOrden)
  {
    foreach ($_POST['id_producto'] as $key => $producto)
    {
      $this->db->update('compras_productos',
        array(
          'cantidad' => $_POST['cantidad'][$key],
          'importe'  => (abs($_POST['cantidad'][$key])*$_POST['valorUnitario'][$key]),
          'total'    => (abs($_POST['cantidad'][$key])*$_POST['valorUnitario'][$key]),
        ),
        array('id_orden' => $idOrden, 'num_row' => $_POST['num_row'][$key]));
    }

    return array('passes' => true, 'msg' => 5);
  }

  public function cancelar($idOrden)
  {
    $this->load->model('compras_ordenes_model');
    $this->compras_ordenes_model->cancelar($idOrden);

    return array('passes' => true);
  }

}