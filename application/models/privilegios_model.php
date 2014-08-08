<?php
class privilegios_model extends CI_Model{
	/**
	 * los url_accion q se asignen seran excluidos de la validacion y la funcion
	 * tienePrivilegioDe regresara un true como si el usuario si tiene ese privilegio,
	 * Esta enfocado para cuendo se utilice Ajax
	 * @var unknown_type
	 */
	public $excepcion_privilegio = array();


	function __construct(){
		parent::__construct();
	}

	/**
	 * Obtiene el listado de todos los privilegios paginados
	 */
	public function obtenPrivilegios(){
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
			$sql = "WHERE ( lower(nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
				lower(url_accion) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

		$query = BDUtil::pagination("
			SELECT id AS id_privilegio, id_padre, nombre, mostrar_menu, url_accion
			FROM privilegios
			".$sql."
			ORDER BY url_accion ASC
		", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'privilegios' => array(),
				'total_rows' 		=> $query['total_rows'],
				'items_per_page' 	=> $params['result_items_per_page'],
				'result_page' 		=> $params['result_page']
		);
		if($res->num_rows() > 0)
			$response['privilegios'] = $res->result();

		return $response;
	}

	/**
	 * Obtiene toda la informacion de un privilegio
	 * @param unknown_type $id
	 */
	public function getInfoPrivilegio($id){
		$res = $this->db
			->select('*')
			->from('privilegios')
			->where("id = '".$id."'")
		->get();
		if($res->num_rows() > 0)
			return $res->row();
		else
			return false;
	}

	/**
	 * Modifica la informacion de un privilegio
	 */
	public function updatePrivilegio(){
		$data = array(
			'nombre'       => $this->input->post('dnombre'),
			'id_padre'     => ($this->input->post('dprivilegios')!=''? $this->input->post('dprivilegios'): '0'),
			'mostrar_menu' => ($this->input->post('dmostrar_menu')=='si'? 't': 'f'),
			'url_accion'   => $this->input->post('durl_accion'),
			'url_icono'    => $this->input->post('durl_icono'),
			'target_blank' => ($this->input->post('dtarget_blank')=='si'? 't': 'f')
		);
		$this->db->update('privilegios', $data, "id = '".$_GET['id']."'");
		return array(true, '');
	}

	/**
	 * Agrega un privilegio a la bd
	 */
	public function addPrivilegio(){
		$data = array(
			'nombre'       => $this->input->post('dnombre'),
			'id_padre'     => ($this->input->post('dprivilegios')!=''? $this->input->post('dprivilegios'): '0'),
			'mostrar_menu' => ($this->input->post('dmostrar_menu')=='si'? 't': 'f'),
			'url_accion'   => $this->input->post('durl_accion'),
			'url_icono'    => $this->input->post('durl_icono'),
			'target_blank' => ($this->input->post('dtarget_blank')=='si'? 't': 'f')
		);
		$this->db->insert('privilegios', $data);
		return array(true, '');
	}

	/**
	 * Elimina un privilegio de la bd
	 */
	public function deletePrivilegio(){
		$this->db->delete('privilegios', "id = '".$_GET['id']."'");
		return array(true, '');
	}




	/**
		* Verifica si el usuairo tiene ese privilegio, si lo tiene genera un link para accederlo
		* @param unknown_type $url_accion
		* @param unknown_type $config = array(
		*			'params'    => 'id=1&tipo=2',
		*			'btn_type'  => 'btn-danger',
		*			'icon_type' => 'icon-white',
		*			'attrs'     => array('onclick' => "msb.confirm('Estas seguro de eliminar el artículo?', 'Publicaciones', this); return false;")
		*			);
		*/
	public function getLinkPrivSm($url_accion, $config){
		$txt = '';
		$priv = $this->tienePrivilegioDe('', $url_accion, true);
		if(is_object($priv)){
			$conf = array(
        'nombre'    => $priv->nombre,
				'params'    => '',
				'btn_type'  => '',
				'icon_type' => 'icon-white',
				'attrs'     => array(),
				'text_link' => 'hidden-tablet',
        		'html'      => '',
				);
			$conf = array_merge($conf, $config);

			$attrs = '';
			foreach ($conf['attrs'] as $key => $value) {
				$attrs .= $key.'="'.$value.'" ';
			}

			$txt = '<a class="btn '.$conf['btn_type'].'" href="'.base_url('panel/'.$priv->url_accion.'?'.$conf['params']).'" '.$attrs.' title="'.$priv->nombre.'">
							<i class="icon-'.$priv->url_icono.' '.$conf['icon_type'].'"></i> <span class="'.$conf['text_link'].'">'.$conf['nombre'].'</span>'.$conf['html'].'</a>';
		}
		return $txt;
	}

	/**
	 * Verifica si el usuario tiene ese privilegio de alerta, si lo tiene genera el html de la alerta con sus datos
	 * @param unknown_type $url_accion
	 */
	public function getAlertPriv($url_accion){
		$txt = '';
		$priv = $this->tienePrivilegioDe('', $url_accion, true);
		if(is_object($priv)){
			list($controler,$metodo) = explode("/",$url_accion);
			$txt = $this->alertas_model->{$metodo}();
		}
		return $txt;
	}


	/**
	 * Verifica si el usuario tiene un privilegio en espesifico
	 * @param unknown_type $id_privilegio
	 * @param unknown_type $url_accion
	 * @param unknown_type $returninfo
	 */
	public function tienePrivilegioDe($id_privilegio="", $url_accion="", $returninfo=false, $id_usuario = null){
		$band = false;
		$url_accion = str_replace('index/', '', $url_accion);

		$excluir = array_search($url_accion, $this->excepcion_privilegio);

    $id_usuario = $id_usuario?:$this->session->userdata('id_usuario');

		$sql = $id_privilegio!=''? "p.id = '".$id_privilegio."'": "lower(url_accion) = lower('".$url_accion."')";
		$res = $this->db
			->select('p.id, p.nombre, p.url_accion, p.mostrar_menu, p.url_icono')
			->from('privilegios AS p')
				->join('usuarios_privilegios AS ep', 'p.id = ep.privilegio_id', 'inner')
			->where("ep.usuario_id = '".$id_usuario."' AND ".$sql."")
			->limit(1)
		->get();
		if($res->num_rows() > 0){
			if($returninfo)
				return $res->row();
			$band = true;
		}
		if($excluir !== false)
			return true;
		return $band;
	}

	public function getFrmPrivilegios($id_submenu=0, $firs=true, $tipo=null, $showp=false){
		$txt = "";
		$bande = true;

		$res = $this->db
			->select("p.id, p.nombre, p.id_padre, p.url_accion, p.url_icono, p.target_blank,
				(SELECT count(privilegio_id) FROM usuarios_privilegios WHERE usuario_id = '".$this->session->userdata('id_usuario')."'
					AND privilegio_id = p.id) as tiene_p")
			->from('privilegios AS p')
			->where("p.id_padre = '".$id_submenu."'")
			->order_by('p.nombre', 'asc')
		->get();
		$txt .= $firs? '<ul class="treeview">': '<ul>';
		foreach($res->result() as $data){
			$res1 = $this->db
				->select('Count(p.id) AS num')
				->from('privilegios AS p')
				->where("p.id_padre = '".$data->id."'")
			->get();
			$data1 = $res1->row();

			if($tipo != null && !is_array($tipo)){
				$set_nombre = 'dprivilegios';
				$set_val = set_radio($set_nombre, $data->id, ($tipo==$data->id? true: false));
				$tipo_obj = 'radio';
			}else{
				$set_nombre = 'dprivilegios[]';
				if(is_array($tipo))
					$set_val = set_checkbox($set_nombre, $data->id,
							(array_search($data->id, $tipo)!==false? true: false) );
				else
					$set_val = set_checkbox($set_nombre, $data->id);
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
					<input type="'.$tipo_obj.'" name="'.$set_nombre.'" data-uniform="false" value="'.$data->id.'" '.$set_val.'> '.$data->nombre.'</label>
					'.$this->getFrmPrivilegios($data->id, false, $tipo).'
				</li>';
			}else{
				$txt .= '<li><label style="font-size:11px;">
					<input type="'.$tipo_obj.'" name="'.$set_nombre.'" data-uniform="false" value="'.$data->id.'" '.$set_val.'> '.$data->nombre.'</label>
				</li>';
			}
			$res1->free_result();
		}
		$txt .= '</ul>';
		$res->free_result();

		return $txt;
	}

	/**
	 * Genera el menu izq con los privilegios q el usuario tenga asignados
	 * @param unknown_type $id_submenu
	 * @param unknown_type $firs
	 */
	public function generaMenuPrivilegio($id_submenu=0, $firs=true){
		$txt = "";
		$bande = true;

		$res = $this->db
			->select('p.id, p.nombre, p.id_padre, p.url_accion, p.url_icono, p.target_blank')
			->from('privilegios AS p')
				->join('usuarios_privilegios AS ep','p.id = ep.privilegio_id','inner')
			->where("ep.usuario_id = '".$this->session->userdata('id_usuario')."' AND p.id_padre = '".$id_submenu."' AND mostrar_menu = 't'")
			->order_by('p.nombre', 'asc')
		->get();
		foreach($res->result() as $data){
			$res1 = $this->db
				->select('Count(p.id) AS num')
				->from('privilegios AS p')
					->join('usuarios_privilegios AS ep','p.id = ep.privilegio_id','inner')
				->where("ep.usuario_id = '".$this->session->userdata('id_usuario')."' AND p.id_padre = '".$data->id."' AND mostrar_menu = 't'")
			->get();
			$data1 = $res1->row();

			$link_tar = $data->target_blank=='t'? ' target="_blank"': '';


			if($data1->num > 0){
				$txt .= '
				<li'.($firs==false? ' class="submenu parent"': ' class="parent"').'>
					<a class="ajax-link" '.($firs? 'onclick="panel.menu('.$data->id.');"': '').' href="'.base_url('panel/'.$data->url_accion).'"'.$link_tar.'>
						<i class="icon-'.$data->url_icono.'"></i><span class="hidden-tablet"> '.$data->nombre.'</span>
					</a>
					<div class="menu-flotante">
						<ol '.($firs? 'id="subm'.$data->id.'" class=""': '').'>';
						if ($data->url_accion!='#' && $data->url_accion!='') {
							// $txt .= '
							// 	<li class="submenu">
							// 		<a class="ajax-link" href="'.base_url('panel/'.$data->url_accion).'"'.$link_tar.'>
							// 			<i class="icon-'.$data->url_icono.'"></i><span class="hidden-tablet"> '.$data->nombre.'</span>
							// 		</a>
							// 	</li>';
						}
					$txt .= $this->generaMenuPrivilegio($data->id, false).'
						</ol>
					</div>
				</li>';
			}else{
				$txt .= '
				<li'.($firs==false? ' class="submenu"': '').'>
					<a class="ajax-link" href="'.base_url('panel/'.$data->url_accion).'"'.$link_tar.'>
						<i class="icon-'.$data->url_icono.'"></i><span class="hidden-tablet"> '.$data->nombre.'</span>
					</a>
				</li>';
			}

		}
		return $txt;
	}
}