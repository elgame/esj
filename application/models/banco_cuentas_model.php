<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class banco_cuentas_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getCuentas($paginados = true)
	{
		$sql = '';
		$query['total_rows'] = $params['result_items_per_page'] = $params['result_page'] = '';
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
			$sql = "WHERE ( lower(c.numero) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(c.alias) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

		$_GET['fstatus'] = ($this->input->get('fstatus') !== false? $this->input->get('fstatus'): 'ac');
		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." c.status='".$this->input->get('fstatus')."'";
 		$query['query'] = 
 						"SELECT c.id_cuenta, c.numero, c.alias, c.cuenta_cpi, c.status
						FROM banco_cuentas c
						{$sql}
						ORDER BY c.alias ASC";
		if($paginados)
			$query = BDUtil::pagination($sql, $params, true);

		$res = $this->db->query($query['query']);

		$response = array(
				'cuentas'        => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0)
		{
			$response['cuentas'] = $res->result();
		}

		return $response;
	}

 	/**
 	 * Agrega un proveedor a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addCliente($data=NULL)
	{
		if ($data==NULL)
		{
			$data = array(
						'nombre_fiscal'  => $this->input->post('fnombre_fiscal'),
						'calle'          => $this->input->post('fcalle'),
						'no_exterior'    => $this->input->post('fno_exterior'),
						'no_interior'    => $this->input->post('fno_interior'),
						'colonia'        => $this->input->post('fcolonia'),
						'localidad'      => $this->input->post('flocalidad'),
						'municipio'      => $this->input->post('fmunicipio'),
						'estado'         => $this->input->post('festado'),
						'cp'             => $this->input->post('fcp'),
						'telefono'       => $this->input->post('ftelefono'),
						'celular'        => $this->input->post('fcelular'),
						'email'          => $this->input->post('femail'),
						'cuenta_cpi'     => $this->input->post('fcuenta_cpi'),
						'rfc'            => $this->input->post('frfc'),
						'curp'           => $this->input->post('fcurp'),
						'dias_credito'   => $this->input->post('fdias_credito'),
						);
		}

		$this->db->insert('clientes', $data);
		$id_cliente = $this->db->insert_id('clientes', 'id_cliente');
		$this->addDocumentos($id_cliente);

		return array('error' => FALSE);
	}

	/**
	 * Modificar la informacion de un proveedor
	 * @param  [type] $id_cliente [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateCliente($id_cliente, $data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
						'nombre_fiscal'  => $this->input->post('fnombre_fiscal'),
						'calle'          => $this->input->post('fcalle'),
						'no_exterior'    => $this->input->post('fno_exterior'),
						'no_interior'    => $this->input->post('fno_interior'),
						'colonia'        => $this->input->post('fcolonia'),
						'localidad'      => $this->input->post('flocalidad'),
						'municipio'      => $this->input->post('fmunicipio'),
						'estado'         => $this->input->post('festado'),
						'cp'             => $this->input->post('fcp'),
						'telefono'       => $this->input->post('ftelefono'),
						'celular'        => $this->input->post('fcelular'),
						'email'          => $this->input->post('femail'),
						'cuenta_cpi'     => $this->input->post('fcuenta_cpi'),
						'rfc'            => $this->input->post('frfc'),
						'curp'           => $this->input->post('fcurp'),
						'dias_credito'   => $this->input->post('fdias_credito'),
						);
		}

		$this->db->update('clientes', $data, array('id_cliente' => $id_cliente));

		$this->db->delete('clientes_documentos', array('id_cliente' => $id_cliente));
		$this->addDocumentos($id_cliente);

		return array('error' => FALSE);
	}

	public function addDocumentos($id_cliente, $data=null){
		$data = array();

		if ($data==NULL)
		{
			if(is_array($this->input->post('documentos')))
			{
				foreach ($this->input->post('documentos') as $key => $docu)
				{
					$data[] = array(
							'id_cliente'   => $id_cliente,
							'id_documento' => $docu
							);
				}
			}
		}

		if(count($data) > 0)
			$this->db->insert_batch('clientes_documentos', $data);
	}

	/**
	 * Obtiene la informacion de un proveedor
	 * @param  boolean $id_cliente [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getClienteInfo($id_cliente=FALSE, $basic_info=FALSE)
	{
    // $id_cliente = (isset($_GET['id']))? $_GET['id']: $id_cliente;
		$id_cliente = $id_cliente? $id_cliente: $_GET['id'];

		$sql_res = $this->db->select("id_cliente, nombre_fiscal, calle, no_exterior, no_interior, colonia, localidad, municipio,
														estado, cp, telefono, celular, email, cuenta_cpi, rfc, curp, status, dias_credito, pais" )
												->from("clientes")
												->where("id_cliente", $id_cliente)
												->get();
		$data['info'] = array();

		if ($sql_res->num_rows() > 0)
			$data['info']	= $sql_res->row();
		$sql_res->free_result();

		$data['docus'] = array();
		if ($basic_info == False) {
			$sql_res = $this->db->select("id_cliente, id_documento" )
													->from("clientes_documentos")
													->where("id_cliente", $id_cliente)
													->get();
			$data['docus'] = $sql_res->result();
			$sql_res->free_result();
		}

		return $data;
	}

	/**
	 * Obtiene el listado de proveedores para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en el nombre
	 * @param type. tipo de proveedor que se quiere obtener (insumos, fruta)
	 */
	public function getClientesAjax($sqlX = null){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND lower(nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'";

    if ( ! is_null($sqlX))
      $sql .= $sqlX;

		$res = $this->db->query(
      "SELECT id_cliente, nombre_fiscal, rfc, calle, no_exterior, no_interior, colonia, municipio, estado, cp, telefono, dias_credito
  			FROM clientes
  			WHERE status = 'ac'
        {$sql}
  			ORDER BY nombre_fiscal ASC
  			LIMIT 20"
    );

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_cliente,
						'label' => $itm->nombre_fiscal,
						'value' => $itm->nombre_fiscal,
						'item'  => $itm,
				);
			}
		}

		return $response;
	}

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */