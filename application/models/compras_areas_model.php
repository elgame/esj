<?php
class compras_areas_model extends CI_Model{
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
	public function obtenAreas(){
		$sql = '';
		//paginacion
		$params = array(
				'result_items_per_page' => '40',
				'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
		);
		if($params['result_page'] % $params['result_items_per_page'] == 0)
			$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

		//Filtros para buscar

		if($this->input->get('status') != '')
		{
			$sql .= " AND ca.status = '".$this->input->get('status')."'";
		}else
			$sql .= " AND ca.status = 't'";

		if($this->input->get('fnombre') != '')
			$sql .= " AND ( lower(ca.nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

		$query = BDUtil::pagination(
			"SELECT ca.id_area, cat.id_tipo, ca.codigo, ca.codigo_fin, ca.nombre, ca.status, ca.id_padre,
				cat.nombre AS tipo
			FROM compras_areas ca
				INNER JOIN compras_areas_tipo cat ON ca.id_tipo = cat.id_tipo
			WHERE 1 = 1 {$sql}
			ORDER BY ca.codigo_fin ASC
		", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'areas' => array(),
				'total_rows' 		=> $query['total_rows'],
				'items_per_page' 	=> $params['result_items_per_page'],
				'result_page' 		=> $params['result_page']
		);
		if($res->num_rows() > 0)
			$response['areas'] = $res->result();

		return $response;
	}

	/**
	 * Obtiene toda la informacion de un privilegio
	 * @param unknown_type $id
	 */
	public function getInfo($id){
		$res = $this->db->query(
				"SELECT ca.id_area, cat.id_tipo, ca.codigo, ca.codigo_fin, ca.nombre, ca.status, ca.id_padre,
					cat.nombre AS tipo
				FROM compras_areas ca
					INNER JOIN compras_areas_tipo cat ON ca.id_tipo = cat.id_tipo
				WHERE ca.id_area = {$id}
				ORDER BY ca.codigo_fin ASC");
		if($res->num_rows() > 0)
			return $res->row();
		else
			return false;
	}

	/**
	 * Modifica la informacion de un privilegio
	 */
	public function updateArea($id_area){
		$data = array(
			'nombre'     => $this->input->post('dnombre'),
			'id_padre'   => (intval($this->input->post('dareas'))>0? $this->input->post('dareas'): NULL),
			// 'id_tipo'    => $this->input->post('did_tipo'),
			'codigo'     => $this->input->post('dcodigo'),
			'codigo_fin' => $this->input->post('dcodigo'),
			// 'nivel'      => 1,
		);

		if ($data['id_padre'] !== NULL){
			$data['codigo_fin'] = $this->getDescripCodigo($data['id_padre'], 'codigo').$data['codigo'];
		}
		$this->db->update('compras_areas', $data, "id_area = '".$id_area."'");

		$data = array('nivel' => $this->getDescripCodigo($id_area, 'nivel'));
		$this->db->update('compras_areas', $data, "id_area = '".$id_area."'");

		return array(true, '');
	}

	/**
	 * Agrega un area a la bd
	 */
	public function addArea(){
		$data = array(
			'nombre'     => $this->input->post('dnombre'),
			'id_padre'   => (intval($this->input->post('dareas'))>0? $this->input->post('dareas'): NULL),
			// 'id_tipo' => $this->input->post('did_tipo'),
			'codigo'     => $this->input->post('dcodigo'),
			'codigo_fin' => $this->input->post('dcodigo'),
			// 'nivel'      => 1,
		);

		if ($data['id_padre'] !== NULL){
			$data['codigo_fin'] = $this->getDescripCodigo($data['id_padre'], 'codigo').$data['codigo'];
		}

		$this->db->insert('compras_areas', $data);
		$id_area = $this->db->insert_id();

		$data = array('nivel' => $this->getDescripCodigo($id_area, 'nivel'));
		$this->db->update('compras_areas', $data, "id_area = '".$id_area."'");
		return array(true, '');
	}

	/**
	 * Elimina un area de la bd
	 */
	public function deleteArea($idArea){
		$this->db->update('compras_areas', array('status' => 'f'), "id_area = '{$idArea}'");
		return array(true, '');
	}


	public function getAreasEspesifico($area, $padre=null)
	{
		$sql = $padre? " AND id_padre = {$padre}": '';
		$query = $this->db->query("SELECT id_area, id_tipo, codigo, codigo_fin, nombre, status, id_padre
		                           FROM compras_areas
		                           WHERE status = 't' AND id_tipo = {$area} {$sql}
		                           ORDER BY codigo ASC");

		return $query->result();
	}

	/**
	 * Obtiene el listado de clasificaciones para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en el nombre
	 * @param type. clasificaciones de una area
	 */
	public function ajaxAreas(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND (lower(codigo_fin) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' OR
				lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%')";
		// if($this->input->get('type') !== false)
		// 	$sql .= " AND id_area = {$this->input->get('type')}";
		$res = $this->db->query(" SELECT id_area, id_tipo, codigo, codigo_fin, nombre, status, id_padre
				FROM compras_areas
				WHERE status = 't' {$sql}
				ORDER BY (id_area, codigo) ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_area,
						'label' => $itm->codigo_fin.' - '.$itm->nombre,
						'value' => $itm->codigo_fin.' - '.$itm->nombre,
						'item'  => $itm,
				);
			}
		}

		return $response;
	}


	public function getDescripCodigo($id_area, $tipo='nombre', $nivel=0)
	{
		$data = $this->db->query("SELECT id_area, id_tipo, codigo, codigo_fin, nombre, status, id_padre
		                           FROM compras_areas
		                           WHERE id_area = {$id_area}")->row();
		if($tipo === 'nombre')
		{
			if($data->id_padre != '')
				$nombre = $this->getDescripCodigo($data->id_padre, $tipo).'/'.$data->nombre;
			else
				$nombre = $data->nombre;
			return $nombre;
		}elseif($tipo === 'id') {
			if($data->id_padre != '')
				$nombre = $this->getDescripCodigo($data->id_padre, $tipo).','.$data->id_area;
			else
				$nombre = $data->id_area;
			return $nombre;
		}elseif($tipo === 'nivel') {
			if($data->id_padre != '') {
				$nivel++;
				$nivel = $this->getDescripCodigo($data->id_padre, $tipo, $nivel);
			} else
				$nivel++;
			return $nivel;
		}else
		{
			if($data->id_padre != '')
				$nombre = $this->getDescripCodigo($data->id_padre, $tipo).$data->codigo;
			else
				$nombre = $data->codigo;
			return $nombre;
		}
	}

	public function getHijos($id_area)
	{
		$data = $this->db->query("SELECT id_area, id_tipo, codigo, codigo_fin, nombre, status, id_padre,
																(SELECT Count(id_area) FROM compras_areas WHERE id_padre = ca.id_area) AS tiene_hijos
		                           FROM compras_areas AS ca
		                           WHERE id_padre = {$id_area}");
		$nombre = '';
		foreach ($data->result() as $key => $value) {
			if ($value->tiene_hijos > 0) {
				$nombre .= $this->getHijos($value->id_area).','.$value->id_area;
			} else {
				$nombre .= ','.$value->id_area;
			}
		}
		return $nombre;
	}


	public $class_treeAreas = 'treeview';

	public function getFrmAreas($id_submenu=0, $firs=true, $tipo=null, $showp=false){
		$txt = "";
		$bande = true;
		$sql_subm = $id_submenu==0? 'id_padre IS NULL': "id_padre = '{$id_submenu}'";

		$res = $this->db
			->select("id_area, id_tipo, codigo, codigo_fin, nombre, status, id_padre")
			->from('compras_areas')
			->where("status = 't' AND {$sql_subm}")
			->order_by('codigo_fin', 'asc')
		->get();

		$txt .= $firs? '<ul class="'.$this->class_treeAreas.'">': '<ul>';
		foreach($res->result() as $data){
			$res1 = $this->db
				->select('Count(id_area) AS num')
				->from('compras_areas')
				->where("id_padre = '".$data->id_area."'")
			->get();
			$data1 = $res1->row();

			if($tipo !== null && !is_array($tipo)){
				$set_nombre = 'dareas';
				$set_val = set_radio($set_nombre, $data->id_area, ($tipo==$data->id_area? true: false));
				$tipo_obj = 'radio';
			}else{
				$set_nombre = 'dareas[]';
				if(is_array($tipo))
					$set_val = set_checkbox($set_nombre, $data->id_area,
							(array_search($data->id_area, $tipo)!==false? true: false) );
				else
					$set_val = set_checkbox($set_nombre, $data->id_area);
				$tipo_obj = 'checkbox';
			}

			if($bande==true && $firs==true && $showp==true){
				$txt .= '<li><label style="font-size:11px;">
				<input type="'.$tipo_obj.'" name="'.$set_nombre.'" data-uniform="false" data-tipo="0" value="0" '.$set_val.($data->id_padre==0?  ' checked': '').'> Padre</label>
				</li>';
				$bande = false;
			}

			if($data1->num > 0){
				$txt .= '<li><label style="font-size:11px;">
					<input type="'.$tipo_obj.'" name="'.$set_nombre.'" data-uniform="false" data-tipo="'.$data->id_tipo.'" value="'.$data->id_area.'" '.$set_val.'> '.$data->codigo_fin.' - '.$data->nombre.'</label>
					'.$this->getFrmAreas($data->id_area, false, $tipo).'
				</li>';
			}else{
				$txt .= '<li><label style="font-size:11px;">
					<input type="'.$tipo_obj.'" name="'.$set_nombre.'" data-uniform="false" data-tipo="'.$data->id_tipo.'" value="'.$data->id_area.'" '.$set_val.'> '.$data->codigo_fin.' - '.$data->nombre.'</label>
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


	public function listaAreas(){
    // $res = $this->getRptComprasData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('L', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    $pdf->titulo2 = 'Catalogo de maquinaria, equipos e instalaciones';
    // $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L');
    $widths = array(15, 50);
    $header = array('CODIGO', 'NOMBRE');

    $response = $this->getAreasEspesifico(1, '');

    $newpag = false;
    $y2aux = $pdf->GetY();
    foreach ($response as $key => $value) {
    	if($y2aux > $pdf->GetY() && !$newpag)
				$pdf->SetY($y2aux);

    	if($pdf->GetY()+4 >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
        $pdf->SetY($pdf->GetY());
      }

      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','',8);
      $datos = array($value->codigo, $value->nombre);
      $pdf->SetXY(6, $pdf->GetY());
      $y = $pdf->GetY();
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false, true);
      $y2aux = $pdf->GetY();

      $datos2 = $this->getAreasEspesifico(2, $value->id_area);
      if(count($datos2) > 0)
      	$newpag = $this->listaAreasRec($pdf, $datos2, 2, $y);
    }


    $pdf->Output('compras_proveedor.pdf', 'I');
  }

  public function listaAreasRec(&$pdf, $datos, $tipo, $y)
  {
  	$aligns = array('L', 'L');
    $widths = array(15, 50);
    $header = array('CODIGO', 'NOMBRE');

    $newpag = false;
    $y2aux = $y;
    $pdf->SetY($y);

		foreach ($datos as $key => $value) {
			if($y2aux > $pdf->GetY() && !$newpag)
				$pdf->SetY($y2aux);

			if($pdf->GetY()+4 >= $pdf->limiteY){
        $pdf->AddPage();
        $newpag = true;
      }

      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','',8);
      $datos = array($value->codigo, $value->nombre);
      $pdf->SetX(6+(($tipo-1)*65));
      $y2 = $pdf->GetY();
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false, true);
      $y2aux = $pdf->GetY();

  		$datos2 = $this->getAreasEspesifico($tipo+1, $value->id_area);
	  	if(count($datos2) > 0) {
	  		$newpag = $this->listaAreasRec($pdf, $datos2, $tipo+1, $y2);
	  	}
		}

		return $newpag;
  }



	/** **************************************
	 * Tipos de Areas
	 */
	public function getTipoAreas()
	{
		$query = $this->db->query("SELECT *
		                           FROM compras_areas_tipo
		                           WHERE status = 't'
		                           ORDER BY id_tipo ASC");
		return $query->result();
	}

}