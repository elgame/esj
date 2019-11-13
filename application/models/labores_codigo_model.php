<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class labores_codigo_model extends CI_Model {

  public function getLabores($perpage = '40')
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
    if ($this->input->get('fnombre') != '')
    {
      $sql .= " AND lower(nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%'";
    }

    if ($this->input->get('fstatus') != '')
    {
      $sql .= " AND status = '".$this->input->get('fstatus')."'";
    }

    $query = BDUtil::pagination(
        "SELECT id_labor, codigo, nombre, status
        FROM compras_salidas_labores
        WHERE 1 = 1 {$sql}
        ORDER BY (nombre) ASC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'labores'     => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['labores'] = $res->result();

    return $response;
  }

  public function agregar($data)
  {
    $insertData = array(
      'nombre' => $data['nombre'],
      'codigo' => strtoupper($data['codigo']),
      'costo' => floatval($data['costo']),
    );

    $this->db->insert('compras_salidas_labores', $insertData);

    return true;
  }

  public function info($id_labor)
  {
    $query = $this->db->query(
      "SELECT id_labor, codigo, nombre, costo, status
        FROM compras_salidas_labores
        WHERE id_labor = {$id_labor}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->result();
    }

    return $data;
  }

  public function modificar($id_labor, $data)
  {
    $updateData = array(
      'nombre' => $data['nombre'],
      'codigo' => strtoupper($data['codigo']),
      'costo' => floatval($data['costo']),
    );

    $this->db->update('compras_salidas_labores', $updateData, array('id_labor' => $id_labor));

    return true;
  }

  public function elimimnar($id_labor)
  {
    $this->db->update('compras_salidas_labores', array('status' => 'f'), array('id_labor' => $id_labor));

    return true;
  }

  public function ajaxLabores()
  {
    $sql = '';
    $res = $this->db->query("
        SELECT *
        FROM compras_salidas_labores
        WHERE status = 't' AND lower(nombre) LIKE '%".mb_strtolower($_GET['term'], 'UTF-8')."%'
        ORDER BY nombre ASC
        LIMIT 20");

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $response[] = array(
          'id' => $itm->id_labor,
          'label' => $itm->nombre,
          'value' => $itm->nombre,
          'item' => $itm,
        );
      }
    }

    return $response;
  }

}

/* End of file caja_chica_model.php */
/* Location: ./application/models/caja_chica_model.php */