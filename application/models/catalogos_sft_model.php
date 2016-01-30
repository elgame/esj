<?php
class catalogos_sft_model extends CI_Model{
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
	public function obtenCatSoft(){
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
			"SELECT ca.id_cat_soft, ca.codigo, ca.nombre, ca.descripcion, ca.status, ca.id_padre
			FROM otros.cat_soft ca
			WHERE 1 = 1 {$sql}
			ORDER BY ca.nombre ASC
		", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'cat_soft' => array(),
				'total_rows' 		=> $query['total_rows'],
				'items_per_page' 	=> $params['result_items_per_page'],
				'result_page' 		=> $params['result_page']
		);
		if($res->num_rows() > 0)
			$response['cat_soft'] = $res->result();

		return $response;
	}

	/**
	 * Obtiene toda la informacion de un privilegio
	 * @param unknown_type $id
	 */
	public function getInfoCatSoft($id){
		$res = $this->db->query(
				"SELECT ca.id_cat_soft, ca.codigo, ca.descripcion, ca.nombre, ca.status, ca.id_padre
				FROM otros.cat_soft ca
				WHERE ca.id_cat_soft = {$id}
				ORDER BY ca.codigo ASC");
		if($res->num_rows() > 0)
			return $res->row();
		else
			return false;
	}

	/**
	 * Modifica la informacion de un privilegio
	 */
	public function updateCatSoft($id_cat_soft){
		$data = array(
			'nombre'     => $this->input->post('dnombre'),
			'id_padre'   => (intval($this->input->post('dareas'))>0? $this->input->post('dareas'): NULL),
			'codigo'     => $this->input->post('dcodigo'),
			'descripcion' => $this->input->post('ddescripcion'),
		);

		$this->db->update('otros.cat_soft', $data, "id_cat_soft = '".$id_cat_soft."'");

		return array(true, '');
	}

	/**
	 * Agrega un area a la bd
	 */
	public function addCatSoft(){
		$data = array(
			'nombre'     => $this->input->post('dnombre'),
			'id_padre'   => (intval($this->input->post('dareas'))>0? $this->input->post('dareas'): NULL),
			'codigo'     => $this->input->post('dcodigo'),
			'descripcion' => $this->input->post('ddescripcion'),
		);

		$this->db->insert('otros.cat_soft', $data);
		$id_area = $this->db->insert_id();

		return array(true, '');
	}

	/**
	 * Elimina un area de la bd
	 */
	public function deleteCatSoft($id_cat_soft){
		$this->db->update('otros.cat_soft', array('status' => 'f'), "id_cat_soft = '{$id_cat_soft}'");
		return array(true, '');
	}


	public function getCatSoftEspesifico($area, $padre=null)
	{
		$sql = $padre? " AND id_padre = {$padre}": ' AND id_padre IS NULL';
		// $sql .= $area>0? " AND (id_tipo = {$area} OR id_tipo IS NULL)": '';
		$query = $this->db->query("SELECT id_cat_soft, codigo, nombre, descripcion, status, id_padre
		                           FROM otros.cat_soft
		                           WHERE status = 't' {$sql}
		                           ORDER BY (id_cat_soft, codigo) ASC");

		return $query->result();
	}

	/**
	 * Obtiene el listado de clasificaciones para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en el nombre
	 * @param type. clasificaciones de una area
	 */
	public function ajaxCatSoft(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND (lower(codigo) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' OR
				lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%')";
		// if($this->input->get('type') !== false)
		// 	$sql .= " AND id_area = {$this->input->get('type')}";
		$res = $this->db->query(" SELECT id_cat_soft, codigo, nombre, status, id_padre
				FROM compras_areas
				WHERE status = 't' {$sql}
				ORDER BY (id_cat_soft, codigo) ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_cat_soft,
						'label' => $itm->codigo.' - '.$itm->nombre,
						'value' => $itm->codigo.' - '.$itm->nombre,
						'item'  => $itm,
				);
			}
		}

		return $response;
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

	public function getFrmCatSoft($id_submenu=0, $firs=true, $tipo=null, $showp=false){
		$txt = "";
		$bande = true;
		$sql_subm = $id_submenu==0? 'id_padre IS NULL': "id_padre = '{$id_submenu}'";

		$res = $this->db
			->select("id_cat_soft, codigo, nombre, status, id_padre")
			->from('otros.cat_soft')
			->where("status = 't' AND {$sql_subm}")
			->order_by('codigo', 'asc')
		->get();

		$txt .= $firs? '<ul class="'.$this->class_treeAreas.'">': '<ul>';
		foreach($res->result() as $data){
			$res1 = $this->db
				->select('Count(id_cat_soft) AS num')
				->from('otros.cat_soft')
				->where("id_padre = '".$data->id_cat_soft."'")
			->get();
			$data1 = $res1->row();

			if($tipo !== null && !is_array($tipo)){
				$set_nombre = 'dareas';
				$set_val = set_radio($set_nombre, $data->id_cat_soft, ($tipo==$data->id_cat_soft? true: false));
				$tipo_obj = 'radio';
			}else{
				$set_nombre = 'dareas[]';
				if(is_array($tipo))
					$set_val = set_checkbox($set_nombre, $data->id_cat_soft,
							(array_search($data->id_cat_soft, $tipo)!==false? true: false) );
				else
					$set_val = set_checkbox($set_nombre, $data->id_cat_soft);
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
					<input type="'.$tipo_obj.'" name="'.$set_nombre.'" data-uniform="false" value="'.$data->id_cat_soft.'" '.$set_val.'> '.$data->codigo.' - '.$data->nombre.'</label>
					'.$this->getFrmCatSoft($data->id_cat_soft, false, $tipo).'
				</li>';
			}else{
				$txt .= '<li><label style="font-size:11px;">
					<input type="'.$tipo_obj.'" name="'.$set_nombre.'" data-uniform="false" value="'.$data->id_cat_soft.'" '.$set_val.'> '.$data->codigo.' - '.$data->nombre.'</label>
				</li>';
			}
			$res1->free_result();
		}
		$txt .= '</ul>';
		$res->free_result();

		return $txt;
	}


	public function listaCatSoft(){
    // $res = $this->getRptComprasData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    $pdf->titulo2 = 'Catalogo software';
    // $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',7);

    $aligns = array('L', 'L', 'L');
    $widths = array(15, 50, 60);
    $header = array('CODIGO', 'NOMBRE', 'DESCRIPCION');

    $response = $this->getCatSoftEspesifico(1, '');

    $newpag = false;
    $y2aux = $pdf->GetY();
    foreach ($response as $key => $value) {
    	if($y2aux > $pdf->GetY() && !$newpag)
				$pdf->SetY($y2aux);

    	if($pdf->GetY()+4 >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',7);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
        $pdf->SetY($pdf->GetY());
      }

      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','',7);
      $datos = array($value->codigo, $value->nombre, $value->descripcion);
      $pdf->SetXY(6, $pdf->GetY());
      $y = $pdf->GetY();
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false, true);
      $y2aux = $pdf->GetY();

      $datos2 = $this->getCatSoftEspesifico(2, $value->id_cat_soft);
      if(count($datos2) > 0)
      	$newpag = $this->listaCatSoftRec($pdf, $datos2, 2, $y2aux);
    }


    $pdf->Output('compras_proveedor.pdf', 'I');
  }

  public function listaCatSoftRec(&$pdf, $datos, $tipo, $y)
  {
  	$aligns = array('L', 'L', 'L');
    $widths = array(15, 50, 60);
    $header = array('CODIGO', 'NOMBRE', 'DESCRIPCION');

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
      $pdf->SetFont('Arial','',7);
      $datos = array($value->codigo, $value->nombre, $value->descripcion);
      $pdf->SetX(6+(($tipo-1)*1));
      $y2 = $pdf->GetY();
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false, true);
      $y2aux = $pdf->GetY();

  		$datos2 = $this->getCatSoftEspesifico($tipo+1, $value->id_cat_soft);
	  	if(count($datos2) > 0) {
	  		$newpag = $this->listaCatSoftRec($pdf, $datos2, $tipo+1, $y2);
	  	}
		}

		return $newpag;
  }

  public function getCatSoftXls($id_submenu=0, $firs=true, $nivel=0){
		$html = "";
		$bande = true;
		$sql_subm = $id_submenu==0? 'id_padre IS NULL': "id_padre = '{$id_submenu}'";

		$res = $this->db
			->select("id_cat_soft, codigo, descripcion, nombre, status, id_padre")
			->from('otros.cat_soft')
			->where("status = 't' AND {$sql_subm}")
			->order_by('codigo', 'asc')
		->get();

		if($firs) {
			$this->load->model('empresas_model');
    	$empresa = $this->empresas_model->getInfoEmpresa(2);
			$titulo1 = $empresa['info']->nombre_fiscal;
	    $titulo2 = 'Catalogo software';
			$html .= '<table>
      <tbody>
        <tr>
          <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>';
    }
		foreach($res->result() as $data){
			$res1 = $this->db
				->select('Count(id_cat_soft) AS num')
				->from('otros.cat_soft')
				->where("id_padre = '".$data->id_cat_soft."'")
			->get();
			$data1 = $res1->row();

			$html .= '
        <tr>
          <td colspan="'.$nivel.'" style="width:10px;"></td>
          <td>'.$data->codigo.'</td>
          <td>'.utf8_decode($data->nombre).'</td>
          <td>'.utf8_decode($data->descripcion).'</td>
        </tr>';

			if($data1->num > 0){
				$html .= $this->getCatSoftXls($data->id_cat_soft, false, $nivel+1);
			}
			$res1->free_result();
		}
		$res->free_result();

		if($firs) {
			$html .= '</tbody>
    	</table>';
    }

		return $html;
	}



	/** **************************************
	 * Catalogo codigos
	 * ***************************************
	 */
	public function obtenCatCodigos(){
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
			"SELECT ca.id_cat_codigos, ca.codigo, ca.nombre, ca.descripcion, ca.ubicacion, ca.otro_dato, ca.status, ca.id_padre
			FROM otros.cat_codigos ca
			WHERE 1 = 1 {$sql}
			ORDER BY ca.nombre ASC
		", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'cat_codigos' => array(),
				'total_rows' 		=> $query['total_rows'],
				'items_per_page' 	=> $params['result_items_per_page'],
				'result_page' 		=> $params['result_page']
		);
		if($res->num_rows() > 0)
			$response['cat_codigos'] = $res->result();

		return $response;
	}

	/**
	 * Obtiene toda la informacion de un privilegio
	 * @param unknown_type $id
	 */
	public function getInfoCatCodigos($id){
		$res = $this->db->query(
				"SELECT ca.id_cat_codigos, ca.codigo, ca.descripcion, ca.nombre, ca.ubicacion, ca.otro_dato, ca.status, ca.id_padre
				FROM otros.cat_codigos ca
				WHERE ca.id_cat_codigos = {$id}
				ORDER BY ca.codigo ASC");
		if($res->num_rows() > 0)
			return $res->row();
		else
			return false;
	}

	/**
	 * Modifica la informacion de un privilegio
	 */
	public function updateCatCodigos($id_cat_codigos){
		$data = array(
			'nombre'     => $this->input->post('dnombre'),
			'id_padre'   => (intval($this->input->post('dareas'))>0? $this->input->post('dareas'): NULL),
			'codigo'     => $this->input->post('dcodigo'),
			'descripcion' => $this->input->post('ddescripcion'),
			'ubicacion' => $this->input->post('dubicacion'),
			'otro_dato' => $this->input->post('dotro_dato'),
		);

		$this->db->update('otros.cat_codigos', $data, "id_cat_codigos = '".$id_cat_codigos."'");

		return array(true, '');
	}

	/**
	 * Agrega un area a la bd
	 */
	public function addCatCodigos(){
		$data = array(
			'nombre'     => $this->input->post('dnombre'),
			'id_padre'   => (intval($this->input->post('dareas'))>0? $this->input->post('dareas'): NULL),
			'codigo'     => $this->input->post('dcodigo'),
			'descripcion' => $this->input->post('ddescripcion'),
			'ubicacion' => $this->input->post('dubicacion'),
			'otro_dato' => $this->input->post('dotro_dato'),
		);

		$this->db->insert('otros.cat_codigos', $data);
		$id_area = $this->db->insert_id();

		return array(true, '');
	}

	/**
	 * Elimina un area de la bd
	 */
	public function deleteCatCodigos($id_cat_codigos){
		$this->db->update('otros.cat_codigos', array('status' => 'f'), "id_cat_codigos = '{$id_cat_codigos}'");
		return array(true, '');
	}


	public function getCatCodigosEspesifico($area, $padre=null)
	{
		$sql = $padre? " AND id_padre = {$padre}": ' AND id_padre IS NULL';
		// $sql .= $area>0? " AND (id_tipo = {$area} OR id_tipo IS NULL)": '';
		$query = $this->db->query("SELECT id_cat_codigos AS id_area, codigo, nombre, descripcion, ubicacion, otro_dato, status, id_padre
		                           FROM otros.cat_codigos
		                           WHERE status = 't' {$sql}
		                           ORDER BY (id_cat_codigos, codigo) ASC");

		return $query->result();
	}

	public function getDescripCodigo($id_area, $tipo='nombre', $nivel=0)
	{
		$data = $this->db->query("SELECT id_cat_codigos AS id_area, codigo, nombre, descripcion, ubicacion, otro_dato, status, id_padre
		                           FROM otros.cat_codigos
		                           WHERE id_cat_codigos = {$id_area}")->row();
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

	/**
	 * Obtiene el listado de clasificaciones para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en el nombre
	 * @param type. clasificaciones de una area
	 */
	public function ajaxCatCodigos(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND (lower(codigo) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' OR
				lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%')";
		// if($this->input->get('type') !== false)
		// 	$sql .= " AND id_area = {$this->input->get('type')}";
		$res = $this->db->query(" SELECT id_cat_codigos AS id_area, codigo, nombre, status, id_padre
				FROM otros.cat_codigos
				WHERE status = 't' {$sql}
				ORDER BY (id_cat_codigos, codigo) ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_area,
						'label' => $itm->codigo.' - '.$itm->nombre,
						'value' => $itm->codigo.' - '.$itm->nombre,
						'item'  => $itm,
				);
			}
		}

		return $response;
	}

	public function getFrmCatCodigos($id_submenu=0, $firs=true, $tipo=null, $showp=false){
		$txt = "";
		$bande = true;
		$sql_subm = $id_submenu==0? 'id_padre IS NULL': "id_padre = '{$id_submenu}'";

		$res = $this->db
			->select("id_cat_codigos, codigo, nombre, status, id_padre")
			->from('otros.cat_codigos')
			->where("status = 't' AND {$sql_subm}")
			->order_by('codigo', 'asc')
		->get();

		$txt .= $firs? '<ul class="'.$this->class_treeAreas.'">': '<ul>';
		foreach($res->result() as $data){
			$res1 = $this->db
				->select('Count(id_cat_codigos) AS num')
				->from('otros.cat_codigos')
				->where("id_padre = '".$data->id_cat_codigos."'")
			->get();
			$data1 = $res1->row();

			if($tipo !== null && !is_array($tipo)){
				$set_nombre = 'dareas';
				$set_val = set_radio($set_nombre, $data->id_cat_codigos, ($tipo==$data->id_cat_codigos? true: false));
				$tipo_obj = 'radio';
			}else{
				$set_nombre = 'dareas[]';
				if(is_array($tipo))
					$set_val = set_checkbox($set_nombre, $data->id_cat_codigos,
							(array_search($data->id_cat_codigos, $tipo)!==false? true: false) );
				else
					$set_val = set_checkbox($set_nombre, $data->id_cat_codigos);
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
					<input type="'.$tipo_obj.'" name="'.$set_nombre.'" data-uniform="false" value="'.$data->id_cat_codigos.'" '.$set_val.'> '.$data->codigo.' - '.$data->nombre.'</label>
					'.$this->getFrmCatCodigos($data->id_cat_codigos, false, $tipo).'
				</li>';
			}else{
				$txt .= '<li><label style="font-size:11px;">
					<input type="'.$tipo_obj.'" name="'.$set_nombre.'" data-uniform="false" value="'.$data->id_cat_codigos.'" '.$set_val.'> '.$data->codigo.' - '.$data->nombre.'</label>
				</li>';
			}
			$res1->free_result();
		}
		$txt .= '</ul>';
		$res->free_result();

		return $txt;
	}


	public function listaCatCodigos(){
    // $res = $this->getRptComprasData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    $pdf->titulo2 = 'Catalogo codigos';
    // $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',7);

    $aligns = array('L', 'L', 'L', 'L', 'L');
    $widths = array(15, 50, 60, 30, 30);
    $header = array('CODIGO', 'NOMBRE', 'DESCRIPCION', 'Dato 1', 'Dato 2');

    $response = $this->getCatCodigosEspesifico(1, '');

    $newpag = false;
    $y2aux = $pdf->GetY();
    foreach ($response as $key => $value) {
    	if($y2aux > $pdf->GetY() && !$newpag)
				$pdf->SetY($y2aux);

    	if($pdf->GetY()+4 >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',7);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
        $pdf->SetY($pdf->GetY());
      }

      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','',7);
      $datos = array($value->codigo, $value->nombre, $value->descripcion, $value->ubicacion, $value->otro_dato);
      $pdf->SetXY(6, $pdf->GetY());
      $y = $pdf->GetY();
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false, true);
      $y2aux = $pdf->GetY();

      $datos2 = $this->getCatCodigosEspesifico(2, $value->id_cat_codigos);
      if(count($datos2) > 0)
      	$newpag = $this->listaCatCodigosRec($pdf, $datos2, 2, $y2aux);
    }


    $pdf->Output('compras_proveedor.pdf', 'I');
  }

  public function listaCatCodigosRec(&$pdf, $datos, $tipo, $y)
  {
  	$aligns = array('L', 'L', 'L', 'L', 'L');
    $widths = array(15, 50, 60, 30, 30);
    $header = array('CODIGO', 'NOMBRE', 'DESCRIPCION', 'Dato 1', 'Dato 2');

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
      $pdf->SetFont('Arial','',7);
      $datos = array($value->codigo, $value->nombre, $value->descripcion, $value->ubicacion, $value->otro_dato);
      $pdf->SetX(6+(($tipo-1)*1));
      $y2 = $pdf->GetY();
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false, true);
      $y2aux = $pdf->GetY();

  		$datos2 = $this->getCatCodigosEspesifico($tipo+1, $value->id_cat_codigos);
	  	if(count($datos2) > 0) {
	  		$newpag = $this->listaCatCodigosRec($pdf, $datos2, $tipo+1, $y2);
	  	}
		}

		return $newpag;
  }

  public function getCatCodigosXls($id_submenu=0, $firs=true, $nivel=0){
		$html = "";
		$bande = true;
		$sql_subm = $id_submenu==0? 'id_padre IS NULL': "id_padre = '{$id_submenu}'";

		$res = $this->db
			->select("id_cat_codigos, codigo, descripcion, nombre, status, id_padre, ubicacion, otro_dato")
			->from('otros.cat_codigos')
			->where("status = 't' AND {$sql_subm}")
			->order_by('codigo', 'asc')
		->get();

		if($firs) {
			$this->load->model('empresas_model');
    	$empresa = $this->empresas_model->getInfoEmpresa(2);
			$titulo1 = $empresa['info']->nombre_fiscal;
	    $titulo2 = 'Catalogo codigos';
			$html .= '<table>
      <tbody>
        <tr>
          <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>';
    }
		foreach($res->result() as $data){
			$res1 = $this->db
				->select('Count(id_cat_codigos) AS num')
				->from('otros.cat_codigos')
				->where("id_padre = '".$data->id_cat_codigos."'")
			->get();
			$data1 = $res1->row();

			$html .= '
        <tr>
          <td colspan="'.$nivel.'" style="width:10px;"></td>
          <td>'.$data->codigo.'</td>
          <td>'.utf8_decode($data->nombre).'</td>
          <td>'.utf8_decode($data->descripcion).'</td>
          <td>'.utf8_decode($data->ubicacion).'</td>
          <td>'.utf8_decode($data->otro_dato).'</td>
        </tr>';

			if($data1->num > 0){
				$html .= $this->getCatCodigosXls($data->id_cat_codigos, false, $nivel+1);
			}
			$res1->free_result();
		}
		$res->free_result();

		if($firs) {
			$html .= '</tbody>
    	</table>';
    }

		return $html;
	}
}