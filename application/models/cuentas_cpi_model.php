<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class cuentas_cpi_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function obtenCuentas(){
		$sql = '';
		//paginacion
		$params = array(
				'result_items_per_page' => '40',
				'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
		);
		if($params['result_page'] % $params['result_items_per_page'] == 0)
			$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

		//Filtros para buscar
		if($this->input->get('fnombre') != '')
			$sql .= " AND ( lower(cc.nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
				lower(cc.cuenta) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";
		if($this->input->get('did_empresa') != '')
			$sql .= " AND e.id_empresa = ".$this->input->get('did_empresa');


		$query = BDUtil::pagination(
			"SELECT cc.id_cuenta, cc.id_padre, cc.nivel, cc.cuenta, cc.nombre, cc.tipo,
        cc.registro_patronal, e.id_empresa, e.nombre_fiscal
			FROM cuentas_contpaq cc
				INNER JOIN empresas e ON cc.id_empresa = e.id_empresa
			WHERE cc.status = 't' AND cc.id_cuenta > 0 {$sql}
			ORDER BY cc.nombre ASC
		", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'cuentas' => array(),
				'total_rows' 		=> $query['total_rows'],
				'items_per_page' 	=> $params['result_items_per_page'],
				'result_page' 		=> $params['result_page']
		);
		if($res->num_rows() > 0)
			$response['cuentas'] = $res->result();

		return $response;
	}

	/**
	 * Agrega un cuenta a la bd
	 */
	public function addCuenta(){
		$data = array(
      'id_padre'          => ($this->input->post('dcuenta_padre')!='0'? $this->input->post('dcuenta_padre'): NULL),
      'nivel'             => '1',
      'cuenta'            => $this->input->post('dcuenta'),
      'nombre'            => $this->input->post('dnombre'),
      'tipo'              => '',
      'id_empresa'        => $this->input->post('did_empresa'),
      'tipo_cuenta'       => $this->input->post('dtipo_cuenta'),
      'registro_patronal' => $this->input->post('dregistro_patronal'),
		);
		$this->db->insert('cuentas_contpaq', $data);
		return array(true, '');
	}

	/**
	 * Modifica la informacion de una cuanta
	 */
	public function updateCuenta($id_cuenta){
		$data = array(
      'id_padre'          => ($this->input->post('dcuenta_padre')!='0'? $this->input->post('dcuenta_padre'): NULL),
      'cuenta'            => $this->input->post('dcuenta'),
      'nombre'            => $this->input->post('dnombre'),
      'id_empresa'        => $this->input->post('did_empresa'),
      'tipo_cuenta'       => $this->input->post('dtipo_cuenta'),
      'registro_patronal' => $this->input->post('dregistro_patronal'),
		);
		$this->db->update('cuentas_contpaq', $data, "id_cuenta = '{$id_cuenta}'");
		return array(true, '');
	}

	/**
	 * Elimina una cuenta de la bd
	 */
	public function deleteCuenta($id_cuenta){
		$this->db->update('cuentas_contpaq', array('status' => 'f'), "id_cuenta = '{$id_cuenta}'");
		return array(true, '');
	}

	/**
	 * Obtiene el listado de bancos para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en las placas, modelo, marca
	 */
	public function getCuentasAjax($did_empresa=null){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND ( lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' OR
                    lower(cuenta) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' )";
    $did_empresa = $did_empresa!=null? $did_empresa : $this->input->get('did_empresa') ;
    if(is_numeric($did_empresa))
      $sql .= " AND id_empresa = {$did_empresa}";

		$res = $this->db->query(
				"SELECT id_cuenta, id_padre, nivel, cuenta, nombre, tipo, id_empresa
				FROM cuentas_contpaq
				WHERE status = 't' AND id_cuenta > 0 ".$sql."
				ORDER BY nombre ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
					'id'    => $itm->cuenta,
					'label' => $itm->nombre.' - '.$itm->cuenta,
					'value' => $itm->nombre.' - '.$itm->cuenta,
					'item'  => $itm,
				);
			}
		}
		$res->free_result();

		return $response;
	}

  public function getCuentaInfo($datos=array(), $info_basic=true)
  {
    $sql = isset($datos['id_cuenta'])? " AND bm.id_cuenta = '{$datos['id_cuenta']}'": '';
    $sql .= isset($datos['cuenta'])? " AND bm.cuenta = '{$datos['cuenta']}'": '';
    $res = $this->db
      ->select('bm.*, e.nombre_fiscal')
      ->from('cuentas_contpaq AS bm')
      ->join('empresas e', 'bm.id_empresa = e.id_empresa', 'inner')
      ->where('id_cuenta > 0 '.$sql)
    ->get();
    if($res->num_rows() > 0){
      $response['info'] = $res->row();
      $res->free_result();
      if($info_basic)
        return $response;

      return $response;
    }else
      return false;
  }

  public function getArbolCuenta($id_empresa, $id_submenu='NULL', $firs=true, $tipo=null, $showp=false)
  {
		$txt = "";
		$bande = true;

		$res = $this->db
			->select("p.id_cuenta, p.nivel, p.id_padre, p.cuenta, p.nombre, p.tipo")
			->from('cuentas_contpaq AS p')
			->where($id_submenu=='NULL'? "p.id_padre is ".$id_submenu."": "p.id_padre = ".$id_submenu."")
			->where("p.id_empresa = ".$id_empresa)
			->where("p.status = 't'")
			->order_by('p.nombre', 'asc')
		->get();
		$txt .= $firs? '<ul class="treeview">': '<ul>';
		foreach($res->result() as $data){
			$res1 = $this->db
				->select('Count(p.id_cuenta) AS num')
				->from('cuentas_contpaq AS p')
				->where("p.id_padre = '".$data->id_cuenta."'")
			->get();
			$data1 = $res1->row();

			if($tipo != null && !is_array($tipo)){
				$set_nombre = 'dcuenta_padre';
				$set_val = set_radio($set_nombre, $data->id_cuenta, ($tipo==$data->id_cuenta? true: false));
				$tipo_obj = 'radio';
			}else{
				$set_nombre = 'dcuenta_padre[]';
				if(is_array($tipo))
					$set_val = set_checkbox($set_nombre, $data->id_cuenta,
							(array_search($data->id_cuenta, $tipo)!==false? true: false) );
				else
					$set_val = set_checkbox($set_nombre, $data->id_cuenta);
				$tipo_obj = 'checkbox';
			}

			if($bande==true && $firs==true && $showp==true){
				$txt .= '<li><label style="font-size:11px;">
				<input type="'.$tipo_obj.'" name="'.$set_nombre.'" data-uniform="false" value="0" '.$set_val.($data->id_padre==0?  ' checked': '').'> Padre</label>
				</li>';
				$bande = false;
			}

			if($data1->num > 0){
				$txt .= '<li><label style="font-size:11px;">
					<input type="'.$tipo_obj.'" name="'.$set_nombre.'" data-uniform="false" value="'.$data->id_cuenta.'" '.$set_val.'> '.$data->nombre.' - '.$data->cuenta.'</label>
					'.$this->getArbolCuenta($id_empresa, $data->id_cuenta, false, $tipo).'
				</li>';
			}else{
				$txt .= '<li><label style="font-size:11px;">
					<input type="'.$tipo_obj.'" name="'.$set_nombre.'" data-uniform="false" value="'.$data->id_cuenta.'" '.$set_val.'> '.$data->nombre.' - '.$data->cuenta.'</label>
				</li>';
			}
			$res1->free_result();
		}

		if($txt === '<ul class="treeview">')
		{
			if($tipo != null && !is_array($tipo)){
				$set_nombre = 'dcuenta_padre';
				$tipo_obj = 'radio';
			}else{
				$set_nombre = 'dcuenta_padre[]';
				$tipo_obj = 'checkbox';
			}
			$txt .= '<li><label style="font-size:11px;">
				<input type="'.$tipo_obj.'" name="'.$set_nombre.'" data-uniform="false" value="0" checked> Padre</label>
				</li>';
		}
		$txt .= '</ul>';
		$res->free_result();


		return $txt;
	}

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */