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
		$id_area = $this->db->insert_id('otros.cat_soft_id_cat_soft_seq');

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
		$data = $this->db->query("SELECT id_cat_codigos AS id_area, nivel AS id_tipo, codigo, codigo AS codigo_fin, nombre, status, id_padre,
																(SELECT Count(id_cat_codigos) FROM otros.cat_codigos WHERE id_padre = ca.id_cat_codigos) AS tiene_hijos
		                           FROM otros.cat_codigos AS ca
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
				"SELECT ca.id_cat_codigos, ca.codigo, ca.descripcion, ca.nombre, ca.ubicacion, ca.otro_dato, ca.status, ca.id_padre,
          COALESCE(ca.nombre, ca.descripcion) AS nombre_full
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
		$id_area = $this->db->insert_id('otros.cat_codigos_id_cat_codigos_seq');

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

  public function getDescripCodigoSim($id_area)
  {
    $data = $this->db->query("SELECT id_cat_codigos AS id_area, codigo, nombre, descripcion, ubicacion, otro_dato, status, id_padre
                               FROM otros.cat_codigos
                               WHERE id_cat_codigos = {$id_area}")->row();
    return $data->nombre;
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
		if($this->input->get('id_padre') > 0)
			$sql .= " AND id_padre = {$this->input->get('id_padre')}";
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

      $datos2 = $this->getCatCodigosEspesifico(2, $value->id_area);
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

  		$datos2 = $this->getCatCodigosEspesifico($tipo+1, $value->id_area);
	  	if(count($datos2) > 0) {
	  		$newpag = $this->listaCatCodigosRec($pdf, $datos2, $tipo+1, $y2aux);
	  	}
		}

		return $newpag;
  }

  public function getCatCodigosXls($id_submenu=0, $firs=true, $nivel=0){
		$html = "";
		$bande = true;
		$sql_subm = $id_submenu==0? 'id_padre IS NULL': "id_padre = '{$id_submenu}'";

		$res = $this->db
			->select("id_cat_codigos, codigo, descripcion, nombre, status, id_padre, ubicacion, otro_dato, nivel, tipo, afectable")
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
          <td>'.utf8_decode($data->nivel).'</td>
          <td>'.$data->codigo.'</td>
          <td>'.utf8_decode($data->nombre).'</td>
          <td>'.utf8_decode($data->descripcion).'</td>
          <td>'.utf8_decode($data->ubicacion).'</td>
          <td>'.utf8_decode($data->otro_dato).'</td>
          <td>'.utf8_decode($data->tipo).'</td>
          <td>'.utf8_decode($data->afectable).'</td>
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

  public function getDataCodigosCuentas()
  {
    // $this->load->model('compras_areas_model');
    $sql_compras = $sql_caja = $sql = $sql2 = '';
    $sql_nom_dia = $sql_nom_hre = '';

    //Filtro de fecha.
    if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '') {
      $sql_caja .= " AND Date(cg.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
      $sql_compras .= " AND Date(cp.fecha_aceptacion) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
      $sql_nom_dia .= " AND Date(ndl.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
      $sql_nom_hre .= " AND Date(ndh.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    }
    elseif($this->input->get('ffecha1') != '') {
      $sql_caja .= " AND Date(cg.fecha) = '".$this->input->get('ffecha1')."'";
      $sql_compras .= " AND Date(cp.fecha_aceptacion) = '".$this->input->get('ffecha1')."'";
      $sql .= " AND Date(csc.fecha) = '".$this->input->get('ffecha1')."'";
      $sql_nom_dia .= " AND Date(ndl.fecha) = '".$this->input->get('ffecha1')."'";
      $sql_nom_hre .= " AND Date(ndh.fecha) = '".$this->input->get('ffecha1')."'";
    }
    elseif($this->input->get('ffecha2') != ''){
      $sql_caja .= " AND Date(cg.fecha) = '".$this->input->get('ffecha2')."'";
      $sql_compras .= " AND Date(cp.fecha_aceptacion) = '".$this->input->get('ffecha2')."'";
      $sql .= " AND Date(csc.fecha) = '".$this->input->get('ffecha2')."'";
      $sql_nom_dia .= " AND Date(ndl.fecha) = '".$this->input->get('ffecha2')."'";
      $sql_nom_hre .= " AND Date(ndh.fecha) = '".$this->input->get('ffecha2')."'";
    }

    if ($this->input->get('did_empresa') != '') {
      $sql_caja .= " AND cc.id_empresa = ".$this->input->get('did_empresa')."";
      $sql_compras .= " AND co.id_empresa = ".$this->input->get('did_empresa')."";
      // $sql .= " AND Date(csc.id_empresa) = ".$this->input->get('did_empresa')."";
      $sql_nom_dia .= " AND ndl.id_empresa = ".$this->input->get('did_empresa')."";
      $sql_nom_hre .= " AND ndh.id_empresa = ".$this->input->get('did_empresa')."";
    }

    if ($this->input->get('sucursalId') != '') {
      $sql_caja .= " AND cg.id_sucursal = ".$this->input->get('sucursalId')."";
      $sql_compras .= " AND co.id_sucursal = ".$this->input->get('sucursalId')."";
      // $sql .= " AND Date(csc.id_empresa) = ".$this->input->get('sucursalId')."";
      // $sql_nom_dia .= " AND ndl.id_empresa = ".$this->input->get('sucursalId')."";
      // $sql_nom_hre .= " AND ndh.id_empresa = ".$this->input->get('sucursalId')."";
    }

    $sql_co = '';
    if ($this->input->get('q_conceptos') != '') {
      switch ($this->input->get('q_conceptos')) {
        case 'qgdc':
          $sql_co .= " AND (cp.descripcion <> 'DIESEL' AND cp.descripcion <> 'GASOLINA' AND cp.descripcion <> 'CALCOMANIA FISCAL VEHICULAR')";
          break;
      }
    }

    $sql2 = $sql;

    // vehiculos

    $response = array();


    if (isset($_GET['dareas']) && count($_GET['dareas']) > 0)
    {
      foreach ($_GET['dareas'] as $key => $value) {
        $ids_hijos = $value.$this->getHijos($value);
        $result = $this->db->query("SELECT ca.nombre, (
            SELECT Sum(importe) importe
            FROM (
              SELECT Sum(cp.total) importe
              FROM compras_productos cp INNER JOIN compras_ordenes co ON co.id_orden = cp.id_orden
              WHERE cp.id_cat_codigos In({$ids_hijos}) {$sql_compras} AND cp.status = 'a' AND co.status <> 'ca'
                {$sql_co}
              UNION
              SELECT Sum(cg.monto) importe
              FROM cajachica_gastos cg INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
              WHERE cg.id_cat_codigos In({$ids_hijos}) AND cg.status = 't'
                AND cg.tipo <> 'pre' {$sql_caja}
              UNION
              SELECT Sum(ndl.importe) importe
              FROM nomina_trabajos_dia_labores ndl
              WHERE ndl.id_area In({$ids_hijos}) {$sql_nom_dia}
              UNION
              SELECT Sum(ndh.importe) importe
              FROM nomina_trabajos_dia_hrsext ndh
              WHERE ndh.id_area In({$ids_hijos}) {$sql_nom_hre}
            ) t
          ) importe
          FROM otros.cat_codigos ca
          WHERE ca.id_cat_codigos = {$value}");
        $response[] = $result->row();
        $result->free_result();


        if (isset($_GET['dmovimientos']{0}) && $_GET['dmovimientos'] == '1' && $response[count($response)-1]->importe == 0)
          array_pop($response);
        else {
          if (isset($_GET['ddesglosado']{0}) && $_GET['ddesglosado'] == '1') {
            $response[count($response)-1]->detalle = $this->db->query(
            "SELECT *
              FROM (
                SELECT
                  ca.id_cat_codigos AS id_area, ca.nombre, Date(co.fecha_creacion) fecha_orden, co.folio::text folio_orden,
                  Date(c.fecha) fecha_compra, (c.serie || c.folio) folio_compra, cp.descripcion producto,
                  cp.total importe, oranc.areas
                FROM compras_ordenes co
                  INNER JOIN compras_productos cp ON co.id_orden = cp.id_orden
                  INNER JOIN otros.cat_codigos ca ON ca.id_cat_codigos = cp.id_cat_codigos
                  LEFT JOIN compras c ON c.id_compra = cp.id_compra
                  LEFT JOIN (
                    SELECT cor.id_orden, STRING_AGG(r.nombre, ', ') AS areas
                    FROM compras_ordenes_rancho cor
                      LEFT JOIN otros.ranchos r ON r.id_rancho = cor.id_rancho
                    WHERE r.id_rancho > 0
                    GROUP BY cor.id_orden
                  ) oranc ON oranc.id_orden = co.id_orden
                WHERE ca.id_cat_codigos In({$ids_hijos}) {$sql_compras}
                  AND cp.status = 'a' AND co.status <> 'ca' {$sql_co}
                UNION
                SELECT ca.id_cat_codigos AS id_area, ca.nombre, Date(cg.fecha) fecha_orden, cg.folio::text folio_orden,
                  NULL fecha_compra, NULL folio_compra,
                  ('Caja #' || cg.no_caja || ' ' || cg.concepto) producto,
                  cg.monto AS importe, oranc.areas
                FROM cajachica_gastos cg
                  INNER JOIN otros.cat_codigos ca ON ca.id_cat_codigos = cg.id_cat_codigos
                  INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
                  LEFT JOIN (
                    SELECT cor.id_gasto, STRING_AGG(r.nombre, ', ') AS areas
                    FROM cajachica_gastos cor
                      LEFT JOIN otros.ranchos r ON r.id_rancho = cor.id_rancho
                    WHERE r.id_rancho > 0
                    GROUP BY cor.id_gasto
                  ) oranc ON oranc.id_gasto = cg.id_gasto
                WHERE ca.id_cat_codigos In({$ids_hijos}) AND cg.status = 't'
                  AND cg.tipo <> 'pre' {$sql_caja}
                UNION
                SELECT ca.id_cat_codigos AS id_area, ca.nombre, Date(ndl.fecha) fecha_orden, ''::text folio_orden,
                  NULL fecha_compra, NULL folio_compra,
                  (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno || ', Labor ' || csl.nombre || ' ' || ndl.horas || 'hrs') producto,
                  ndl.importe, '' AS areas
                FROM nomina_trabajos_dia_labores ndl
                  INNER JOIN otros.cat_codigos ca ON ca.id_cat_codigos = ndl.id_area
                  INNER JOIN compras_salidas_labores csl ON csl.id_labor = ndl.id_labor
                  INNER JOIN usuarios u ON u.id = ndl.id_usuario
                WHERE ca.id_cat_codigos In({$ids_hijos}) {$sql_nom_dia}
                UNION
                SELECT ca.id_cat_codigos AS id_area, ca.nombre, Date(ndh.fecha) fecha_orden, ''::text folio_orden,
                  NULL fecha_compra, NULL folio_compra,
                  (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno || ', horas Ext ' || ndh.horas) producto,
                  ndh.importe, '' AS areas
                FROM nomina_trabajos_dia_hrsext ndh
                  INNER JOIN otros.cat_codigos ca ON ca.id_cat_codigos = ndh.id_area
                  INNER JOIN usuarios u ON u.id = ndh.id_usuario
                WHERE ca.id_cat_codigos In({$ids_hijos}) {$sql_nom_hre}
              ) t
              ORDER BY fecha_orden ASC")->result();

          }
        }

      }
    }

    return $response;
  }
  public function rpt_codigos_cuentas_pdf()
  {
    $combustible = $this->getDataCodigosCuentas();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(($this->input->get('did_empresa')? $this->input->get('did_empresa'): 2));
    $sucursal = $this->empresas_model->infoSucursal(intval($this->input->get('sucursalId')));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('L', 'mm', 'Letter');
    $pdf->show_head = true;

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = "Reporte de Gastos";

    $pdf->titulo3 = ($sucursal? $sucursal->nombre_fiscal."\n": ''); //"{$_GET['dproducto']} \n";
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1'])." al ".MyString::fechaAT($_GET['ffecha2'])."";
    elseif (!empty($_GET['ffecha1']))
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1']);
    elseif (!empty($_GET['ffecha2']))
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha2']);

    $pdf->AliasNbPages();
    // $links = array('', '', '', '');
    $pdf->SetY(30);
    $aligns = array('L', 'R');
    $widths = array(177, 29, 45);
    $header = array('Nombre', 'Importe', '');
    $aligns2 = array('L', 'L', 'L', 'L', 'L', 'L', 'R', 'L', 'L');
    $widths2 = array(18, 18, 18, 18, 60, 45, 29, 45);
    $header2 = array('Fecha O', 'Folio O', 'Fecha C', 'Folio C', 'C Costo', 'Producto', 'Importe', 'Areas');

    $lts_combustible = 0;
    $horas_totales = 0;

    $entro = false;
    foreach($combustible as $key => $vehiculo)
    {
      $cantidad = 0;
      $importe = 0;
      if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
      {
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        // $pdf->Row($header, true);

        if (isset($vehiculo->detalle)) {
          $pdf->SetX(6);
          $pdf->SetAligns($aligns2);
          $pdf->SetWidths($widths2);
          $pdf->Row($header2, true);
        }
      }
      $pdf->SetFont('Arial','B',8);
      $pdf->SetTextColor(0,0,0);
      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row(array(
        $vehiculo->nombre,
        MyString::formatoNumero($vehiculo->importe, 2, '', false),
      ), false, false);

      $lts_combustible += floatval($vehiculo->importe);

      if (isset($vehiculo->detalle)) {
        foreach ($vehiculo->detalle as $key2 => $item)
        {
          $band_head = false;
          if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
          {
            $pdf->AddPage();

            $pdf->SetFont('Arial','B',8);
            $pdf->SetTextColor(255,255,255);
            $pdf->SetFillColor(160,160,160);
            $pdf->SetX(6);
            $pdf->SetAligns($aligns2);
            $pdf->SetWidths($widths2);
            $pdf->Row($header2, true);
          }

          $pdf->SetFont('Arial','',8);
          $pdf->SetTextColor(0,0,0);

          $datos = array(
            MyString::fechaAT($item->fecha_orden),
            $item->folio_orden,
            MyString::fechaAT($item->fecha_compra),
            $item->folio_compra,
            $item->nombre,
            $item->producto,
            MyString::formatoNumero($item->importe, 2, '', false),
            $item->areas,
          );

          $pdf->SetX(6);
          $pdf->SetAligns($aligns2);
          $pdf->SetWidths($widths2);
          $pdf->Row($datos, false, false);
        }
      }

    }

    $pdf->SetX(6);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);

    $pdf->SetFont('Arial','B',9);
    $pdf->SetTextColor(0,0,0);
    $pdf->Row(array('TOTALES',
        MyString::formatoNumero($lts_combustible, 2, '', false) ),
    true, false);

    $pdf->Output('reporte_gasto_codigo.pdf', 'I');
  }

  public function rpt_codigos_cuentas_xls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=reporte_gasto_codigo.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $combustible = $this->getDataCodigosCuentas();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "Reporte de Gastos";
    $titulo3 = "";
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
        $titulo3 .= "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
    elseif (!empty($_GET['ffecha1']))
        $titulo3 .= "Del ".$_GET['ffecha1'];
    elseif (!empty($_GET['ffecha2']))
        $titulo3 .= "Del ".$_GET['ffecha2'];

    $html = '<table>
      <tbody>
        <tr>
          <td colspan="8" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="8" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="8" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="8"></td>
        </tr>
        <tr style="font-weight:bold">
          <td colspan="6" style="border:1px solid #000;background-color: #cccccc;">Nombre</td>
          <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">Importe</td>
        </tr>';
    if (isset($combustible[0]->detalle)) {
      $html .= '<tr style="font-weight:bold">
        <td></td>
        <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Fecha O</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio O</td>
        <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Fecha C</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio C</td>
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">C Costo</td>
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Producto</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Importe</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Areas</td>
      </tr>';
    }
    $lts_combustible = $horas_totales = 0;
    foreach ($combustible as $key => $vehiculo)
    {
      $lts_combustible += floatval($vehiculo->importe);

      $html .= '<tr style="font-weight:bold">
          <td colspan="6" style="border:1px solid #000;background-color: #cccccc;">'.$vehiculo->nombre.'</td>
          <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">'.$vehiculo->importe.'</td>
        </tr>';
      if (isset($vehiculo->detalle)) {
        foreach ($vehiculo->detalle as $key2 => $item)
        {
          $html .= '<tr>
              <td></td>
              <td style="width:100px;border:1px solid #000;background-color: #cccccc;">'.$item->fecha_orden.'</td>
              <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$item->folio_orden.'</td>
              <td style="width:100px;border:1px solid #000;background-color: #cccccc;">'.$item->fecha_compra.'</td>
              <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$item->folio_compra.'</td>
              <td style="width:400px;border:1px solid #000;background-color: #cccccc;">'.$item->nombre.'</td>
              <td style="width:400px;border:1px solid #000;background-color: #cccccc;">'.$item->producto.'</td>
              <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$item->importe.'</td>
              <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$item->areas.'</td>
            </tr>';
        }
      }

    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="6">TOTALES</td>
          <td colspan="2" style="border:1px solid #000;">'.$lts_combustible.'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

  public function getDataCodigosComprasPiezas()
  {
    // $this->load->model('compras_areas_model');
    $sql_compras = $sql_caja = $sql = $sql2 = '';
    $sql_nom_dia = $sql_nom_hre = '';

    //Filtro de fecha.
    if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '') {
      $sql_compras .= " AND Date(cp.fecha_aceptacion) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    }
    elseif($this->input->get('ffecha1') != '') {
      $sql_compras .= " AND Date(cp.fecha_aceptacion) = '".$this->input->get('ffecha1')."'";
    }
    elseif($this->input->get('ffecha2') != ''){
      $sql_compras .= " AND Date(cp.fecha_aceptacion) = '".$this->input->get('ffecha2')."'";
    }

    if ($this->input->get('did_empresa') != '') {
      $sql_compras .= " AND co.id_empresa = ".$this->input->get('did_empresa')."";
    }

    if ($this->input->get('sucursalId') != '') {
      $sql_compras .= " AND co.id_sucursal = ".$this->input->get('sucursalId')."";
    }

    $response = array();


    if (isset($_GET['dareas']) && count($_GET['dareas']) > 0)
    {
      $ids_hijos = [];
      foreach ($_GET['dareas'] as $key => $value) {
        $ids_hijos[] = $value.$this->getHijos($value);
      }
      $ids_hijos = implode(',', $ids_hijos);

      $response = $this->db->query("
        SELECT
          cp.id_producto, cp.descripcion producto, Sum(cp.piezas) AS piezas
        FROM compras_ordenes co
          INNER JOIN compras_productos cp ON co.id_orden = cp.id_orden
          INNER JOIN otros.cat_codigos ca ON ca.id_cat_codigos = cp.id_cat_codigos
          LEFT JOIN compras c ON c.id_compra = cp.id_compra
        WHERE cp.status = 'a' AND co.status <> 'ca' AND cp.piezas > 0
          AND ca.id_cat_codigos In({$ids_hijos}) {$sql_compras}
        GROUP BY cp.id_producto, cp.descripcion")->result();
    }

    return $response;
  }
  public function rpt_codigos_compras_piezas_pdf()
  {
    $compras = $this->getDataCodigosComprasPiezas();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(($this->input->get('did_empresa')? $this->input->get('did_empresa'): 2));
    $sucursal = $this->empresas_model->infoSucursal(intval($this->input->get('sucursalId')));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = "Reporte de Compras por Piezas";

    $pdf->titulo3 = ($sucursal? $sucursal->nombre_fiscal."\n": ''); //"{$_GET['dproducto']} \n";
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1'])." al ".MyString::fechaAT($_GET['ffecha2'])."";
    elseif (!empty($_GET['ffecha1']))
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1']);
    elseif (!empty($_GET['ffecha2']))
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha2']);

    $pdf->AliasNbPages();
    // $links = array('', '', '', '');
    $pdf->SetY(30);
    $aligns = array('L', 'R');
    $widths = array(170, 35);
    $header = array('Producto', 'Piezas');

    $lts_compras = 0;
    $horas_totales = 0;

    $entro = false;
    foreach($compras as $key => $compra)
    {
      $cantidad = 0;
      $importe = 0;
      if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
      {
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
      }
      $pdf->SetFont('Arial','B',8);
      $pdf->SetTextColor(0,0,0);
      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row(array(
        $compra->producto,
        MyString::formatoNumero($compra->piezas, 2, '', false),
      ), false, false);

      $lts_compras += floatval($compra->piezas);
    }

    $pdf->SetX(6);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);

    $pdf->SetFont('Arial','B',9);
    $pdf->SetTextColor(0,0,0);
    $pdf->Row(array('TOTALES',
        MyString::formatoNumero($lts_compras, 2, '', false) ),
    true, false);

    $pdf->Output('rpt_codigos_compras_piezas.pdf', 'I');
  }

  public function getDataCodigosCuentasSalidas()
  {
    // $this->load->model('compras_areas_model');
    $sql_salida = $sql_caja = $sql = $sql2 = '';
    $sql_nom_dia = $sql_nom_hre = '';

    //Filtro de fecha.
    if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '') {
      $sql_salida .= " AND Date(co.fecha_creacion) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    }
    elseif($this->input->get('ffecha1') != '') {
      $sql_salida .= " AND Date(co.fecha_creacion) = '".$this->input->get('ffecha1')."'";
    }
    elseif($this->input->get('ffecha2') != ''){
      $sql_salida .= " AND Date(co.fecha_creacion) = '".$this->input->get('ffecha2')."'";
    }

    if ($this->input->get('did_empresa') != '') {
      $sql_salida .= " AND co.id_empresa = ".$this->input->get('did_empresa')."";
    }

    $sql2 = $sql;

    // vehiculos

    $response = array();


    if (isset($_GET['dareas']) && count($_GET['dareas']) > 0)
    {
      foreach ($_GET['dareas'] as $key => $value) {
        $ids_hijos = $value.$this->getHijos($value);
        $result = $this->db->query("SELECT ca.nombre, (
            SELECT Sum(importe) importe
            FROM (
              SELECT Sum(cp.cantidad*cp.precio_unitario) importe
              FROM compras_salidas_productos cp INNER JOIN compras_salidas co ON co.id_salida = cp.id_salida
              WHERE cp.id_cat_codigos In({$ids_hijos}) {$sql_salida} AND co.status <> 'ca' AND co.status <> 'n'
            ) t
          ) importe
          FROM otros.cat_codigos ca
          WHERE ca.id_cat_codigos = {$value}");
        $response[] = $result->row();
        $result->free_result();


        if (isset($_GET['dmovimientos']{0}) && $_GET['dmovimientos'] == '1' && $response[count($response)-1]->importe == 0)
          array_pop($response);
        else {
          if (isset($_GET['ddesglosado']{0}) && $_GET['ddesglosado'] == '1') {
            $response[count($response)-1]->detalle = $this->db->query(
            "SELECT *
              FROM (
                SELECT
                  ca.id_cat_codigos AS id_area, ca.nombre, Date(co.fecha_creacion) fecha_orden, co.folio::text folio_orden,
                  p.nombre producto, co.solicito, (cp.cantidad*cp.precio_unitario) importe, cp.cantidad, cp.precio_unitario,
                  pu.nombre AS unidad
                FROM compras_salidas co
                  INNER JOIN compras_salidas_productos cp ON co.id_salida = cp.id_salida
                  INNER JOIN otros.cat_codigos ca ON ca.id_cat_codigos = cp.id_cat_codigos
                  INNER JOIN productos p ON p.id_producto = cp.id_producto
                  INNER JOIN productos_unidades pu ON pu.id_unidad = p.id_unidad
                WHERE ca.id_cat_codigos In({$ids_hijos}) {$sql_salida}
                  AND co.status <> 'ca' AND co.status <> 'n'
              ) t
              ORDER BY fecha_orden ASC")->result();

          }
        }

      }
    }

    return $response;
  }

  public function rpt_codigos_cuentas_salidas_pdf()
  {
    $combustible = $this->getDataCodigosCuentasSalidas();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = "Reporte de Salidas";

    $pdf->titulo3 = ''; //"{$_GET['dproducto']} \n";
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1'])." al ".MyString::fechaAT($_GET['ffecha2'])."";
    elseif (!empty($_GET['ffecha1']))
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1']);
    elseif (!empty($_GET['ffecha2']))
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha2']);

    $pdf->AliasNbPages();
    // $links = array('', '', '', '');
    $pdf->SetY(30);
    $aligns = array('L', 'R');
    $widths = array(170, 35);
    $header = array('Nombre', 'Importe');
    $aligns2 = array('L', 'L', 'L', 'L', 'L', 'L', 'R', 'R', 'R');
    $widths2 = array(18, 17, 31, 42, 32, 13, 12, 12, 29);
    $header2 = array('Fecha S', 'Folio S', 'Solicito', 'C Costo', 'Producto', 'Unidad', 'Cantidad', 'Costo', 'Importe');

    $lts_combustible = 0;
    $horas_totales = 0;

    $entro = false;
    foreach($combustible as $key => $vehiculo)
    {
      $cantidad = 0;
      $importe = 0;
      if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
      {
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);

        if (isset($vehiculo->detalle)) {
          $pdf->SetX(6);
          $pdf->SetAligns($aligns2);
          $pdf->SetWidths($widths2);
          $pdf->Row($header2, true);
        }
      }
      $pdf->SetFont('Arial','B',8);
      $pdf->SetTextColor(0,0,0);
      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row(array(
        $vehiculo->nombre,
        MyString::formatoNumero($vehiculo->importe, 2, '', false),
      ), false, false);

      $lts_combustible += floatval($vehiculo->importe);

      if (isset($vehiculo->detalle)) {
        foreach ($vehiculo->detalle as $key2 => $item)
        {
          $band_head = false;
          if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
          {
            $pdf->AddPage();

            $pdf->SetFont('Arial','B',8);
            $pdf->SetTextColor(255,255,255);
            $pdf->SetFillColor(160,160,160);
            $pdf->SetX(6);
            $pdf->SetAligns($aligns2);
            $pdf->SetWidths($widths2);
            $pdf->Row($header2, true);
          }

          $pdf->SetFont('Arial','',8);
          $pdf->SetTextColor(0,0,0);

          $datos = array(
            MyString::fechaAT($item->fecha_orden),
            $item->folio_orden,
            $item->solicito,
            $item->nombre,
            $item->producto,
            $item->unidad,
            MyString::formatoNumero($item->cantidad, 2, '', false),
            MyString::formatoNumero($item->precio_unitario, 2, '', false),
            MyString::formatoNumero($item->importe, 2, '', false),
          );

          $pdf->SetX(6);
          $pdf->SetAligns($aligns2);
          $pdf->SetWidths($widths2);
          $pdf->Row($datos, false, false);
        }
      }

    }

    $pdf->SetX(6);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);

    $pdf->SetFont('Arial','B',9);
    $pdf->SetTextColor(0,0,0);
    $pdf->Row(array('TOTALES',
        MyString::formatoNumero($lts_combustible, 2, '', false) ),
    true, false);

    $pdf->Output('reporte_gasto_salidas.pdf', 'I');
  }

  public function rpt_codigos_cuentas_salidas_xls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=reporte_gasto_salidas.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $combustible = $this->getDataCodigosCuentasSalidas();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "Reporte de Salidas";
    $titulo3 = "";
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
        $titulo3 .= "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
    elseif (!empty($_GET['ffecha1']))
        $titulo3 .= "Del ".$_GET['ffecha1'];
    elseif (!empty($_GET['ffecha2']))
        $titulo3 .= "Del ".$_GET['ffecha2'];

    $html = '<table>
      <tbody>
        <tr>
          <td colspan="7" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="7" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="7" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="7"></td>
        </tr>
        <tr style="font-weight:bold">
          <td colspan="5" style="border:1px solid #000;background-color: #cccccc;">Nombre</td>
          <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">Importe</td>
        </tr>';
    if (isset($combustible[0]->detalle)) {
      $html .= '<tr style="font-weight:bold">
        <td></td>
        <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Fecha S</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio S</td>
        <td style="width:300px;border:1px solid #000;background-color: #cccccc;">Solicito</td>
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">C Costo</td>
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Producto</td>
        <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Unidad</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Cantidad</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Costo</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Importe</td>
      </tr>';
    }
    $lts_combustible = $horas_totales = 0;
    foreach ($combustible as $key => $vehiculo)
    {
      $lts_combustible += floatval($vehiculo->importe);

      $html .= '<tr style="font-weight:bold">
          <td colspan="5" style="border:1px solid #000;background-color: #cccccc;">'.$vehiculo->nombre.'</td>
          <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">'.$vehiculo->importe.'</td>
        </tr>';
      if (isset($vehiculo->detalle)) {
        foreach ($vehiculo->detalle as $key2 => $item)
        {
          $html .= '<tr>
              <td></td>
              <td style="width:100px;border:1px solid #000;background-color: #cccccc;">'.$item->fecha_orden.'</td>
              <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$item->folio_orden.'</td>
              <td style="width:300px;border:1px solid #000;background-color: #cccccc;">'.$item->solicito.'</td>
              <td style="width:400px;border:1px solid #000;background-color: #cccccc;">'.$item->nombre.'</td>
              <td style="width:400px;border:1px solid #000;background-color: #cccccc;">'.$item->producto.'</td>
              <td style="width:400px;border:1px solid #000;background-color: #cccccc;">'.$item->unidad.'</td>
              <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$item->cantidad.'</td>
              <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$item->precio_unitario.'</td>
              <td style="width:150px;border:1px solid #000;background-color: #cccccc;">'.$item->importe.'</td>
            </tr>';
        }
      }

    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="5">TOTALES</td>
          <td colspan="2" style="border:1px solid #000;">'.$lts_combustible.'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

}