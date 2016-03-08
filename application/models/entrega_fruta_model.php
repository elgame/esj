<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class entrega_fruta_model extends CI_Model {
	private $pass_finkok = 'gamaL1!l';

	function __construct()
	{
		parent::__construct();
		$this->load->model('bitacora_model');
	}

	public function getEntradas($paginados = true)
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
			$sql = "WHERE ( e.folio = ".$this->input->get('fnombre')." )";

    if (isset($_GET['fechaini']))
      if ($this->input->get('fechaini') !== '')
        $sql .= (empty($sql) ? "WHERE ": " AND ") . "COALESCE(DATE(e.fecha_captura), DATE(e.fecha_registro)) >= '".$this->input->get('fechaini')."'";

    if (isset($_GET['fechaend']))
      if ($this->input->get('fechaend') !== '')
        $sql .= (empty($sql) ? "WHERE ": " AND ") . "COALESCE(DATE(e.fecha_captura), DATE(e.fecha_registro)) <= '".$this->input->get('fechaend')."'";

		if($this->input->get('farea') != '')
			$sql .= ($sql==''? 'WHERE': ' AND')." a.id_area='".$this->input->get('farea')."'";

		$query = BDUtil::pagination(
				"SELECT e.id_entrega_fruta, e.folio, COALESCE(DATE(e.fecha_captura), DATE(e.fecha_registro)) AS fecha, e.total,
					COALESCE(c.nombre, c.descripcion) AS rancho, a.nombre AS area
				FROM otros.entrega_fruta e
					LEFT JOIN areas a ON a.id_area = e.id_area
					LEFT JOIN otros.cat_codigos c ON c.id_cat_codigos = e.id_cat_codigos_rnch
				".$sql."
				ORDER BY e.folio ASC
				", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'entrega_fruta'    => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0){
			$response['entrega_fruta'] = $res->result();
		}

		return $response;
	}

 	/**
 	 * Agrega un proveedor a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addEntradas($data)
	{
		$data['fno_formatos'] = $data['fno_formatos']>0? $data['fno_formatos']*4: 0;
		$response = '';
		for ($i=0; $i < $data['fno_formatos']; $i++) {
			$datos = [
				'id_area' => $data['farea'],
				'folio'   => $this->getFolio($data['farea']),
			];

			$this->db->insert('otros.entrega_fruta', $datos);
			$response .= ','.$this->db->insert_id('otros.entrega_fruta', 'id_entrega_fruta');
		}

		return array('error' => FALSE, 'hojas' => $response);
	}

	/**
	 * Modificar la informacion de un proveedor
	 * @param  [type] $id_entrega [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateEntrada($id_entrega, $data=NULL)
	{
		if ($data==NULL)
		{
			$data = array(
						'fecha_captura'       => $this->input->post('ffecha'),
						'id_cat_codigos_rnch' => $this->input->post('codigoAreaId'),
						'id_vehiculo'         => $this->input->post('vehiculoId'),
						'id_usuario'          => $this->input->post('fchoferId'),
						'id_encargado'        => $this->input->post('fencargadoId'),
						'no'                  => $this->input->post('fno'),
						'total'               => 0,
						);
		}

		$this->db->update('otros.entrega_fruta', $data, array('id_entrega_fruta' => $id_entrega));
		$this->saveFruta($id_entrega);

		return array('error' => FALSE);
	}

	public function getFolio($id_area)
	{
		$folio = 1;
		$result = $this->db->query("SELECT folio FROM otros.entrega_fruta
		                           WHERE id_area = {$id_area} ORDER BY folio DESC LIMIT 1");
		if ($result->num_rows() > 0) {
			$folio = $result->row()->folio+1;
		}
		return $folio;
	}

	/**
	 * Obtiene la informacion de un proveedor
	 * @param  boolean $id_entrega [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getFormatoInfo($id_entrega=FALSE, $basic_info=FALSE)
	{
		$id_entrega = $id_entrega ? $id_entrega : (isset($_GET['id'])? $_GET['id']: 0) ;

		$sql_res = $this->db->query("SELECT id_entrega_fruta, id_area, folio, COALESCE(fecha_captura, fecha_registro) AS fecha, id_cat_codigos_rnch,
																	id_vehiculo, id_usuario, no, id_encargado, total
		                           FROM otros.entrega_fruta
		                           WHERE id_entrega_fruta = ".$id_entrega);
		$data['info'] = array();

		if ($sql_res->num_rows() > 0)
		{
			$data['info']	= $sql_res->row();

			if ($basic_info == false) {
				$datares = $this->db->query("SELECT ef.row, ef.id_clasificacion, ef.piso, ef.estibas, ef.altura, ef.cantidad,
																c.nombre AS clasif
		                           FROM otros.entrega_fruta_cantidad ef
		                           	LEFT JOIN clasificaciones c ON c.id_clasificacion = ef.id_clasificacion
		                           WHERE ef.id_entrega_fruta = ".$id_entrega)->result();
				$data['info']->fruta = $datares;

				if (isset($data['info']->id_cat_codigos_rnch)) {
					$this->load->model('catalogos_sft_model');
					$data['info']->rancho = $this->catalogos_sft_model->getInfoCatCodigos($data['info']->id_cat_codigos_rnch);
				}
				if (isset($data['info']->id_area)) {
					$this->load->model('areas_model');
					$data['info']->area = $this->areas_model->getAreaInfo($data['info']->id_area, true)['info'];
				}
				if (isset($data['info']->id_vehiculo)) {
					$this->load->model('vehiculos_model');
					$data['info']->vehiculo = $this->vehiculos_model->getVehiculoInfo($data['info']->id_vehiculo, true)['info'];
				}
				if (isset($data['info']->id_usuario)) {
					$this->load->model('usuarios_model');
					$data['info']->chofer = $this->usuarios_model->get_usuario_info($data['info']->id_usuario, true)['info'];
				}
				if (isset($data['info']->id_encargado)) {
					$this->load->model('usuarios_model');
					$data['info']->encargado = $this->usuarios_model->get_usuario_info($data['info']->id_encargado, true)['info'];
				}
			}
		}
		$sql_res->free_result();


		return $data;
	}

	// /**
	//  * Obtiene el listado de proveedores para usar ajax
	//  * @param term. termino escrito en la caja de texto, busca en el nombre
	//  * @param type. tipo de proveedor que se quiere obtener (insumos, fruta)
	//  */
	// public function getProveedoresAjax(){
	// 	$sql = '';
	// 	if ($this->input->get('term') !== false)
	// 		$sql = " AND lower(nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'";
	// 	if($this->input->get('did_empresa') != '')
	// 		$sql .= " AND id_empresa = '".$this->input->get('did_empresa')."'";

	// 	$res = $this->db->query("
	// 			SELECT id_proveedor, nombre_fiscal, rfc, calle, no_exterior, no_interior, colonia, municipio, estado, cp, telefono,
	// 				condicion_pago, dias_credito
	// 			FROM proveedores
	// 			WHERE status = 'ac' ".$sql."
	// 			ORDER BY nombre_fiscal ASC
	// 			LIMIT 20");

	// 	$response = array();
	// 	if($res->num_rows() > 0){
	// 		foreach($res->result() as $itm){
	// 			$response[] = array(
	// 					'id'    => $itm->id_proveedor,
	// 					'label' => $itm->nombre_fiscal,
	// 					'value' => $itm->nombre_fiscal,
	// 					'item'  => $itm,
	// 			);
	// 		}
	// 	}

	// 	return $response;
	// }

	// public function getRanchosAjax(){
	// 	$sql = '';
	// 	if ($this->input->get('term') !== false)
	// 		$sql = " AND upper(rancho) LIKE '%".mb_strtoupper($this->input->get('term'), 'UTF-8')."%'";
	// 	$res = $this->db->query("
	// 			SELECT rancho
	// 			FROM ranchos_bascula
	// 			WHERE rancho <> '' ".$sql."
	// 			ORDER BY rancho ASC
	// 			LIMIT 20");

	// 	$response = array();
	// 	if($res->num_rows() > 0){
	// 		foreach($res->result() as $itm){
	// 			$response[] = array(
	// 					'id'    => '0',
	// 					'label' => $itm->rancho,
	// 					'value' => $itm->rancho,
	// 					'item'  => $itm,
	// 			);
	// 		}
	// 	}

	// 	return $response;
	// }

	/**
	 * ******* CUENTAS DE PROVEEDORES ****************
	 * ***********************************************
	 * Agrega o actualiza cuentas del proveedor
	 * @param [type] $id_entrega [description]
	 */
	private function saveFruta($id_entrega)
	{
		$this->db->delete('otros.entrega_fruta_cantidad', array('id_entrega_fruta' => $id_entrega));

		if ( is_array($this->input->post('prod_cantidad')) )
		{
			foreach ($this->input->post('prod_cantidad') as $key => $value)
			{
				if ($_POST['prod_cantidad'][$key]!='' && $_POST['prod_estibas'][$key]!='') {
					$data = array('id_entrega_fruta' => $id_entrega,
									'row'              => $key,
									'id_clasificacion' => $_POST['prod_did_prod'][$key]!=''? $_POST['prod_did_prod'][$key]: NULL,
									'piso'             => $_POST['prod_piso'][$key]!=''? $_POST['prod_piso'][$key]: NULL,
									'estibas'          => $_POST['prod_estibas'][$key]!=''? $_POST['prod_estibas'][$key]: NULL,
									'altura'           => $_POST['prod_altura'][$key]!=''? $_POST['prod_altura'][$key]: NULL,
									'cantidad'         => $_POST['prod_cantidad'][$key]!=''? $_POST['prod_cantidad'][$key]: NULL,
								);
					$this->db->insert('otros.entrega_fruta_cantidad', $data);
					$total_cantidad += $_POST['prod_cantidad'][$key];
				}
			}
		}

		$this->db->update('otros.entrega_fruta', ['total' => $total_cantidad], array('id_entrega_fruta' => $id_entrega));
	}

	/**
	 * Obtiene el listado de proveedores
	 * @return [type] [description]
	 */
	public function getCuentas($id_proveedor, $id_cuenta=null){
		$sql = ($id_cuenta==null? '': ' AND pc.id_cuenta = '.$id_cuenta);
		$res = $this->db->query("
				SELECT pc.id_cuenta, pc.id_proveedor, pc.is_banamex, pc.alias, pc.sucursal, pc.cuenta, pc.status,
					(pc.alias || ' *' || substring(pc.cuenta from '....$')) AS full_alias, bb.id_banco, bb.nombre AS banco, bb.codigo, pc.referencia
				FROM proveedores_cuentas AS pc
					LEFT JOIN banco_bancos AS bb ON pc.id_banco = bb.id_banco
				WHERE pc.status = 't' AND pc.id_proveedor = {$id_proveedor} {$sql}
				ORDER BY full_alias ASC");

		$response = array();
		if($res->num_rows() > 0){
			$response = $res->result();
		}

		return $response;
	}


  /**
  * Reporte de Certificados y Seguro.
  */
  public function printHojas($hojas){
  	$hojas = explode(',', substr($hojas, 1));
  	$this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = false;
    $pdf->SetMargins(0, 0, 0);
    $pdf->SetAutoPageBreak(false);

    $x = $y = 0;
    $no_rec = 1;
    foreach ($hojas as $key => $value) {
    	if ($key % 4 == 0) {
    		$pdf->AddPage();
    		$x = $y = 0;
    		$no_rec = 1;
    	}
    	if ($no_rec === 2 || $no_rec === 4)
    		$x = 107.5;
    	elseif ($no_rec === 3){
    		$x = 0;
    		$y = 137.5;
    	}
    	$this->printRecibo($value, $pdf, $x, $y);
    	$no_rec++;
    }
    $pdf->Output('Reporte.pdf', 'I');
  }

  public function printRecibo($id_entrega, &$pdf=null, &$x=0, &$y=0)
  {
  	$data = $this->getFormatoInfo($id_entrega)['info'];
  	$pdf->SetFont('Arial','B',8);
  	// $pdf->SetXY($x, $y);

		$pdf->Image(APPPATH.(str_replace(APPPATH, '', $pdf->logo)), $x+43, $y+2, 20);

  	$pdf->SetXY($x, $y+8);
    $pdf->SetAligns(['L', 'R']);
    $pdf->SetWidths([53.5, 53.5]);
    $pdf->Row(['Entrada de Fruta a Empaque', $data->area->nombre], false, false);

  	$pdf->SetFont('Arial', 'B', 8);
    $pdf->SetAligns(['R', 'L']);
    $pdf->SetWidths([30, 77]);
    $pdf->SetX($x);
    $pdf->Row(['Folio', (isset($data->folio)? $data->folio: '')], false, true);
    $pdf->SetFont('Arial', '', 7);
    $pdf->SetX($x);
    $pdf->Row(['Fecha', (isset($data->no)? $data->fecha: '')], false, true);
    $pdf->SetX($x);
    $pdf->Row(['Rancho', (isset($data->rancho)? $data->rancho->nombre: '')], false, true);
    $pdf->SetX($x);
    $pdf->Row(['Transporte', (isset($data->vehiculo)? $data->vehiculo->marca.' '.$data->vehiculo->modelo: '')], false, true);
    $pdf->SetX($x);
    $pdf->Row(['Placas', (isset($data->vehiculo)? $data->vehiculo->placa: '')], false, true);
    $pdf->SetX($x);
    $pdf->Row(['Chofer', (isset($data->chofer[0])? $data->chofer[0]->nombre.' '.$data->chofer[0]->apellido_paterno: '')], false, true);
    $pdf->SetX($x);
    $pdf->Row(['# Melga', (isset($data->no)? $data->no: '')], false, true);

    $pdf->SetX($x);
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetAligns(['C']);
    $pdf->SetWidths([107]);
    $pdf->Row(['Cantidad de fruta'], false, true);

    $pdf->SetFont('Arial', '', 7);
    $pdf->SetAligns(['C', 'C', 'C', 'C', 'C']);
    $pdf->SetWidths([21.4, 21.4, 21.4, 21.4, 21.4]);
    $pdf->SetX($x);
    $pdf->Row(['Clasf', '# Piso', 'Estibas', 'Altura', 'Cantidad'], false, true);
    $total_cantidad = 0;
    if (isset($data->fruta) && count($data->fruta) > 0) {
    	foreach ($data->fruta as $key => $value) {
    		$pdf->SetX($x);
		    $pdf->Row([$value->clasif, $value->piso, $value->estibas, $value->altura, $value->cantidad], false, true);
		    $total_cantidad += $value->cantidad;
    	}
    } else {
	    for ($i=0; $i < 7; $i++) {
		    $pdf->SetX($x);
		    $pdf->Row(['', '', '', '', ''], false, true);
	    }
	  }
	  $pdf->SetAligns(['R', 'L']);
    $pdf->SetWidths([85.6, 21.4]);
	  $pdf->SetX($x);
		$pdf->Row(['Total', $total_cantidad], false, true);

    $pdf->SetAligns(['R', 'L']);
    $pdf->SetWidths([30, 77]);
    $pdf->SetX($x);
    $pdf->Row(['Encargado', (isset($data->encargado[0])? $data->encargado[0]->nombre.' '.$data->encargado[0]->apellido_paterno: '')], false, true);
    $pdf->SetX($x);
    $pdf->Row(['Firma', ''], false, true);
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */