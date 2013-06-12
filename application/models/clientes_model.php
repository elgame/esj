<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class clientes_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getClientes($paginados = true)
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
			$sql = "WHERE ( lower(p.nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.calle) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.colonia) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.municipio) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.estado) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

		$_GET['fstatus'] = ($this->input->get('fstatus') !== false? $this->input->get('fstatus'): 'ac');
		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." p.status='".$this->input->get('fstatus')."'";

		// if($this->input->get('ftipo_proveedor') != '' && $this->input->get('ftipo_proveedor') != 'todos')
		// 	$sql .= ($sql==''? 'WHERE': ' AND')." p.tipo_proveedor='".$this->input->get('ftipo_proveedor')."'";

		$query = BDUtil::pagination("
				SELECT p.id_cliente, p.nombre_fiscal, p.calle, p.no_exterior, p.no_interior, p.colonia, p.localidad, p.municipio,
							p.telefono, p.estado, p.status
				FROM clientes p
				".$sql."
				ORDER BY p.nombre_fiscal ASC
				", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'clientes'    => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0){
			$response['clientes'] = $res->result();
			foreach ($response['clientes'] as $key => $value) {
				$response['clientes'][$key]->direccion = $value->calle.($value->no_exterior!=''? ' '.$value->no_exterior: '')
										 .($value->no_interior!=''? $value->no_interior: '')
										 .($value->colonia!=''? ', '.$value->colonia: '')
										 .($value->localidad!=''? ', '.$value->localidad: '')
										 .($value->municipio!=''? ', '.$value->municipio: '')
										 .($value->estado!=''? ', '.$value->estado: '');
			}
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
						);
		}

		$this->db->insert('clientes', $data);
		// $id_proveedor = $this->db->insert_id('proveedores', 'id_proveedor');

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
						);
		}

		$this->db->update('clientes', $data, array('id_cliente' => $id_cliente));

		return array('error' => FALSE);
	}

	/**
	 * Obtiene la informacion de un proveedor
	 * @param  boolean $id_cliente [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getClienteInfo($id_cliente=FALSE, $basic_info=FALSE)
	{
		$id_cliente = (isset($_GET['id']))? $_GET['id']: $id_cliente;

		$sql_res = $this->db->select("id_cliente, nombre_fiscal, calle, no_exterior, no_interior, colonia, localidad, municipio,
														estado, cp, telefono, celular, email, cuenta_cpi, rfc, curp, status" )
												->from("clientes")
												->where("id_cliente", $id_cliente)
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
	 * Obtiene el listado de proveedores para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en el nombre
	 * @param type. tipo de proveedor que se quiere obtener (insumos, fruta)
	 */
	public function getClientesAjax(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND lower(nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'";

		$res = $this->db->query("
				SELECT id_cliente, nombre_fiscal, rfc, calle, no_exterior, no_interior, colonia, municipio, estado, cp, telefono
				FROM clientes
				WHERE status = 'ac' ".$sql."
				ORDER BY nombre_fiscal ASC
				LIMIT 20");

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