<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class choferes_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getChoferes($paginados = true)
	{
		$sql = '';
		//paginacion
		if($paginados)
		{
			$this->load->library('pagination');
			$params = array(
					'result_items_per_page' => '60',
					'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
			);
			if($params['result_page'] % $params['result_items_per_page'] == 0)
				$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);
		}
		//Filtros para buscar
		if($this->input->get('fnombre') != '')
			$sql = "WHERE ( lower(nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

		$_GET['fstatus'] = ($this->input->get('fstatus') !== false? $this->input->get('fstatus'): 't');
		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." status='".$this->input->get('fstatus')."'";

		$query = BDUtil::pagination("
				SELECT id_chofer, nombre, telefono, id_nextel, no_licencia, no_ife, status, url_licencia, url_ife
				FROM choferes
				".$sql."
				ORDER BY nombre ASC
				", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'choferes'       => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0){
			$response['choferes'] = $res->result();
		}

		return $response;
	}

 	/**
 	 * Agrega un chofer a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addChofer($data=NULL)
	{
    $this->load->library('my_upload');

		if ($data==NULL)
		{
			$data = array(
        'nombre'      => $this->input->post('fnombre'),
        'telefono'    => $this->input->post('ftelefono'),
        'id_nextel'   => $this->input->post('fid_nextel'),
        'no_licencia' => $this->input->post('fno_licencia'),
        'no_ife'      => $this->input->post('fno_ife'),
      );
		}

    if ($_FILES['flicencia_doc']['tmp_name'] !== '')
    {
      $path_lic = 'documentos/CHOFERES/LICENCIAS';

      $config_upload = array(
        'upload_path'     => APPPATH.$path_lic,
        'allowed_types'   => '*',
        'max_size'        => '2048',
        'encrypt_name'    => FALSE
      );

      $this->my_upload->initialize($config_upload);
      $data_doc = $this->my_upload->do_upload('flicencia_doc');

      $path = explode('application/', $data_doc['full_path']);

      $data['url_licencia'] = APPPATH.$path[1];
    }

    if ($_FILES['fife_docu']['tmp_name'] !== '')
    {
      $path_ife = 'documentos/CHOFERES/IFEs';

      $config_upload = array(
        'upload_path'     => APPPATH.$path_ife,
        'allowed_types'   => '*',
        'max_size'        => '2048',
        'encrypt_name'    => FALSE
      );

      $this->my_upload->initialize($config_upload);
      $data_doc = $this->my_upload->do_upload('fife_docu');

      $path = explode('application/', $data_doc['full_path']);

      $data['url_ife'] = APPPATH.$path[1];
    }

		$this->db->insert('choferes', $data);
		// $id_chofer = $this->db->insert_id('proveedores', 'id_chofer');

		return array('error' => FALSE);
	}

	/**
	 * Modificar la informacion de un chofer
	 * @param  [type] $id_chofer [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateChofer($id_chofer, $data=NULL)
	{

		if ($data==NULL)
		{
      $this->load->library('my_upload');

      $chofer = $this->getChoferInfo($id_chofer);

			$data = array(
        'nombre'      => $this->input->post('fnombre'),
        'telefono'    => $this->input->post('ftelefono'),
        'id_nextel'   => $this->input->post('fid_nextel'),
        'no_licencia' => $this->input->post('fno_licencia'),
        'no_ife'      => $this->input->post('fno_ife'),
      );

      if ($_FILES['flicencia_doc']['tmp_name'] !== '')
      {
        $path_lic = 'documentos/CHOFERES/LICENCIAS';

        $config_upload = array(
          'upload_path'     => APPPATH.$path_lic,
          'allowed_types'   => '*',
          'max_size'        => '2048',
          'encrypt_name'    => FALSE
        );

        $this->my_upload->initialize($config_upload);
        $data_doc = $this->my_upload->do_upload('flicencia_doc');

        $path = explode('application/', $data_doc['full_path']);

        $data['url_licencia'] = APPPATH.$path[1];

        UploadFiles::deleteFile(base_url($chofer['info']->url_licencia));
      }

      if ($_FILES['fife_docu']['tmp_name'] !== '')
      {
        $path_ife = 'documentos/CHOFERES/IFEs';

        $config_upload = array(
          'upload_path'     => APPPATH.$path_ife,
          'allowed_types'   => '*',
          'max_size'        => '2048',
          'encrypt_name'    => FALSE
        );

        $this->my_upload->initialize($config_upload);
        $data_doc = $this->my_upload->do_upload('fife_docu');

        $path = explode('application/', $data_doc['full_path']);

        $data['url_ife'] = APPPATH.$path[1];

        UploadFiles::deleteFile(base_url($chofer['info']->url_ife));
      }

		}

		$this->db->update('choferes', $data, array('id_chofer' => $id_chofer));

		return array('error' => FALSE);
	}

	/**
	 * Obtiene la informacion de un chofer
	 * @param  boolean $id_chofer [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getChoferInfo($id_chofer=FALSE, $basic_info=FALSE)
	{
		$id_chofer = (isset($_GET['id']))? $_GET['id']: $id_chofer;

		$sql_res = $this->db
      ->select("id_chofer, nombre, status, telefono, id_nextel, no_licencia,
                no_ife, url_licencia, url_ife")
  		->from("choferes")
  		->where("id_chofer", $id_chofer)
  		->get();
		$data['info'] = array();

		if ($sql_res->num_rows() > 0)
			$data['info']	= $sql_res->row();
		$sql_res->free_result();

		if ($basic_info == False) {

		}

		return $data;
	}

	/**
	 * Obtiene el listado de camiones para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en las placas, modelo, marca
	 */
	public function getChoferesAjax(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND ( lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' )";

    if ($this->input->get('alldata') !== false)
      $sql .= " AND Coalesce(telefono, '') <> '' AND Coalesce(no_licencia, '') <> '' AND Coalesce(no_ife, '') <> ''";

		$res = $this->db->query("
				SELECT id_chofer, nombre, status
				FROM choferes
				WHERE status = 't' ".$sql."
				ORDER BY nombre ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_chofer,
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