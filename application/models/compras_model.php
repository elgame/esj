<?php
class compras_model extends privilegios_model{

	function __construct(){
		parent::__construct();
	}



	/**
	 * Obtiene la informacion de una compra
	 */
	public function getInfoCompra($id_compra, $info_basic=false)
  {
		$res = $this->db
            ->select("*")
            ->from('compras')
            ->where("id_compra = {$id_compra}")
            ->get();

    if($res->num_rows() > 0)
    {
			$response['info'] = $res->row();
      // $response['info']->fechaT = str_replace(' ', 'T', substr($response['info']->fecha, 0, 16));
      // $response['info']->fecha = substr($response['info']->fecha, 0, 10);

			$res->free_result();

      if($info_basic)
				return $response;

      // Carga la info de la empresa.
      // $this->load->model('empresas_model');
      // $empresa = $this->empresas_model->getInfoEmpresa($response['info']->id_empresa);
      // $response['info']->empresa = $empresa['info'];

      // Carga la info del proveedor.
			$this->load->model('proveedores_model');
			$prov = $this->proveedores_model->getProveedorInfo($response['info']->id_proveedor);
			$response['info']->proveedor = $prov['info'];

      //Productos
      $res = $this->db->query(
          "SELECT cf.id_compra, cp.id_orden, cp.num_row,
                  cp.id_producto, pr.nombre AS producto, pr.codigo, pr.id_unidad, pu.abreviatura, pu.nombre as unidad,
                  cp.id_presentacion, pp.nombre AS presentacion, pp.cantidad as presen_cantidad,
                  cp.descripcion, cp.cantidad, cp.precio_unitario, cp.importe,
                  cp.iva, cp.retencion_iva, cp.total, cp.porcentaje_iva,
                  cp.porcentaje_retencion, cp.status, pr.cuenta_cpi
           FROM compras_facturas AS cf 
             INNER JOIN compras_productos AS cp ON cf.id_orden = cp.id_orden
             LEFT JOIN productos AS pr ON pr.id_producto = cp.id_producto
             LEFT JOIN productos_presentaciones AS pp ON pp.id_presentacion = cp.id_presentacion
             LEFT JOIN productos_unidades AS pu ON pu.id_unidad = pr.id_unidad
           WHERE cf.id_compra = {$id_compra}");

      $response['productos'] = $res->result();

			return $response;
		}else
			return false;
	}

}