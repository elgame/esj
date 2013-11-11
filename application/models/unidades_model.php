<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class unidades_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

  /**
   * Obtiene el listado de facturas
   *
   * @return
   */
  public function getUnidades($perpage = '40')
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
        "SELECT id_unidad, nombre, status
        FROM unidades
        WHERE 1 = 1 {$sql}
        ORDER BY (nombre) ASC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'unidades'           => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['unidades'] = $res->result();

    return $response;
  }

  public function agregar()
  {
    $this->db->insert('unidades', array('nombre' => $_POST['nombre']));
    $idUnidad = $this->db->insert_id();

    $productos = array();
    foreach ($_POST['productoId'] as $key => $idProd)
    {
      $productos[] = array(
        'id_unidad'   => $idUnidad,
        'id_producto' => $idProd,
        'cantidad'    => $_POST['cantidad'][$key],
      );
    }

    $this->db->insert_batch('unidades_productos', $productos);

    return array('passes' => true, 'msg' => 3);
  }

  public function modificar($idUnidad)
  {
    $this->db->update('unidades', array('nombre' => $_POST['nombre']), array('id_unidad' => $idUnidad));

    $this->db->delete('unidades_productos', array('id_unidad' => $idUnidad));

    $productos = array();
    foreach ($_POST['productoId'] as $key => $idProd)
    {
      $productos[] = array(
        'id_unidad'   => $idUnidad,
        'id_producto' => $idProd,
        'cantidad'    => $_POST['cantidad'][$key],
      );
    }

    $this->db->insert_batch('unidades_productos', $productos);

    return array('passes' => true, 'msg' => 4);
  }

  public function eliminar($idUnidad)
  {
    $this->db->update('unidades', array('status' => 'f'), array('id_unidad' => $idUnidad));

    return true;
  }

	public function info($idUnidad, $full = false)
  {
    $query = $this->db->query(
      "SELECT *
        FROM unidades
        WHERE id_unidad = {$idUnidad}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->result();

      $query->free_result();
      if ($full)
      {
        $query = $this->db->query(
          "SELECT up.id_unidad, up.id_producto, up.cantidad, pr.nombre
           FROM unidades_productos AS up
           INNER JOIN productos AS pr ON pr.id_producto = up.id_producto
           WHERE up.id_unidad = {$data['info'][0]->id_unidad}");

        $data['info'][0]->productos = array();
        if ($query->num_rows() > 0)
        {
          $data['info'][0]->productos = $query->result();
        }
      }

    }

    return $data;
  }

	/**
	 * Obtiene el listado de unidades para usar ajax
	 * @param term. termino escrito en la caja de texto
	 */
	public function ajaxUnidades(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND ( lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' )";

		$res = $this->db->query("
				SELECT id_unidad, nombre, status
				FROM unidades
				WHERE status = 't' ".$sql."
				ORDER BY nombre ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_unidad,
						'label' => $itm->nombre,
						'value' => $itm->nombre,
						'item'  => $itm,
				);
			}
		}

		return $response;
	}

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */