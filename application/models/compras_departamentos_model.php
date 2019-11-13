<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class compras_departamentos_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

  /**
   * Obtiene el listado de facturas
   *
   * @return
   */
  public function getDepartamentos($perpage = '40')
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
    if ($this->input->get('ffiltro') != '')
    {
      $sql .= " AND lower(nombre) LIKE '%".mb_strtolower($_GET['ffiltro'], 'UTF-8')."%'";
    }

    $query = BDUtil::pagination(
        "SELECT id_departamento, nombre
        FROM compras_departamentos
        WHERE 1 = 1 {$sql}
        ORDER BY (nombre) ASC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'departamentos'           => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['departamentos'] = $res->result();

    return $response;
  }

  public function agregar()
  {
    $this->db->insert('compras_departamentos', array('nombre' => $_POST['nombre']));
    $departamentoId = $this->db->insert_id();

    return array('passes' => true, 'msg' => 3, 'departamentoId' => $departamentoId);
  }

  public function modificar($departamentoId)
  {
    $this->db->update('compras_departamentos', array('nombre' => $_POST['nombre']), array('id_departamento' => $departamentoId));

    return array('passes' => true, 'msg' => 4);
  }

	public function info($departamentoId, $full = false)
  {
    $query = $this->db->query(
      "SELECT *
        FROM compras_departamentos
        WHERE id_departamento = {$departamentoId}");

    $data = array('info' => array());
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->result();
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