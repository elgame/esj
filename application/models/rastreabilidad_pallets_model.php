<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class rastreabilidad_pallets_model extends privilegios_model {


	function __construct()
	{
		parent::__construct();
	}

	public function getPallets($paginados = true)
	{
		$sql = '';
		//paginacion
		if($paginados)
		{
			$this->load->library('pagination');
			$params = array(
					'result_items_per_page' => '40',
					'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
			);
			if($params['result_page'] % $params['result_items_per_page'] == 0)
				$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);
		}
		//Filtros para buscar
		if($this->input->get('fnombre') != '')
			$sql = "WHERE ( lower(c.nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' 
								".(is_numeric($this->input->get('fnombre'))? "OR rp.folio = '".$this->input->get('fnombre')."'": '')." )";

		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." rp.status = '".$this->input->get('fstatus')."'";

		$query = BDUtil::pagination("SELECT 
					rp.id_pallet, rp.folio, Date(rp.fecha) AS fecha, rp.no_cajas, c.nombre, Sum(rpr.cajas) AS cajas
				FROM rastria_pallets AS rp 
					INNER JOIN rastria_pallets_rendimiento AS rpr ON rp.id_pallet = rpr.id_pallet
					INNER JOIN clasificaciones AS c ON c.id_clasificacion = rp.id_clasificacion 
				{$sql}
				GROUP BY rp.id_pallet, rp.folio, rp.fecha, rp.no_cajas, c.nombre
				ORDER BY folio ASC
				", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'pallets'      => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0)
			$response['pallets'] = $res->result();

		$res->free_result();

		return $response;
	}

	/**
	 * Obtiene el siguiente folio para el pallet
	 * @return [type] [description]
	 */
 	public function getNextFolio(){
 		$result = $this->db->query("SELECT (folio+1) AS folio FROM rastria_pallets ORDER BY folio DESC LIMIT 1")->row();
 		return (is_object($result)? $result->folio: '');
 	}

 	public function getRendimientoLibre($id_clasificacion){
 		$result = $this->db->query("SELECT rr.id_rendimiento, rr.lote, Date(rr.fecha) AS fecha, rcl.rendimiento, rcl.cajas, rcl.libres
 		                           FROM rastria_rendimiento AS rr 
																	INNER JOIN rastria_cajas_libres AS rcl ON rr.id_rendimiento = rcl.id_rendimiento
 		                           WHERE rcl.id_clasificacion = {$id_clasificacion}
 		                           ORDER BY ");
 	}

 	/**
 	 * Agregar un pallet a la bd
 	 * @param [type] $data array con los valores a insertar
 	 */
 	public function addPallet($data=NULL){
 		if ($data==NULL)
		{
			$data = array(
						'id_clasificacion' => $this->input->post('fid_clasificacion'),
						'folio'            => $this->input->post('ffolio'),
						'no_cajas'         => $this->input->post('fcajas'),
						);
		}

		//se valida que no este un pallet pendiente de la misma clasificacion
		// if($this->checkPalletPendiente($data['id_clasificacion'])){
			$this->db->insert('rastria_pallets', $data);
			$id_pallet = $this->db->insert_id('rastria_pallets', 'id_pallet');

			// $this->addPalletRendimiento($data['id_clasificacion']);

			return array('msg' => 3, $id_pallet);
		// }
		// return array('msg' => 4, 0);
 	}



 	public function addPalletRendimiento($id_clasificacion){
 		$pallets = $this->db->query("SELECT 
									id_pallet, id_clasificacion, folio, fecha, no_cajas, status, nombre, cajas, cajas_faltantes
								FROM rastria_pallets_lista
								WHERE id_clasificacion = {$id_clasificacion} AND cajas_faltantes > 0
								ORDER BY id_pallet ASC");
 		foreach ($pallets->result() as $key => $pallet) {
 			$cajas_faltantes = $pallet->cajas_faltantes;
 			$cajas_pallet = $pallet->cajas;
 			$pallets_rendimiento = array();
 			$cajas = $this->db->query("SELECT 
										id_rendimiento, id_clasificacion, rendimiento, cajas, libres
									FROM rastria_cajas_libres
									WHERE id_clasificacion = {$id_clasificacion}
									ORDER BY id_rendimiento ASC");
 			foreach ($cajas->result() as $key => $caja) {
 				if($cajas_pallet < $pallet->cajas_faltantes){
 					$cajas_agregar = ($caja->libres>=$cajas_faltantes? $cajas_faltantes: $caja->libres);
 					$pallets_rendimiento[] = array(
								'id_pallet'        => $pallet->id_pallet,
								'id_rendimiento'   => $caja->id_rendimiento,
								'id_clasificacion' => $pallet->id_clasificacion,
								'cajas'            => $cajas_agregar,
 						);
					$cajas_pallet   += $cajas_agregar;
					$cajas_faltantes -= $cajas_agregar;
 				}else
 					break;
 			}
 			$cajas->free_result();

 			if(count($pallets_rendimiento) > 0)
 				$this->db->insert_batch('rastria_pallets_rendimiento', $pallets_rendimiento);
 		}
 		$pallets->free_result();
 		return true;
 	}

 	public function checkPalletPendiente($id_clasificacion){
 		$result = $this->db->query("SELECT Count(id_pallet) AS num
 		                           FROM rastria_pallets_lista
 		                           WHERE id_clasificacion = {$id_clasificacion} AND cajas_faltantes > 0")->row();
 		return ($result->num == 0? true: false);
 	}

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */