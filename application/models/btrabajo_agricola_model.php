<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class btrabajo_agricola_model extends CI_Model {
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

		$empresa = $this->empresas_model->getDefaultEmpresa();
    if( ! $this->input->get('did_empresa') != '')
    {
      $_GET['did_empresa'] = $empresa->id_empresa;
      $_GET['dempresa'] = $empresa->nombre_fiscal;
    }
    $sql .= (empty($sql) ? "WHERE ": " AND ") . "e.id_empresa = '".$this->input->get('did_empresa')."'";

		$query = BDUtil::pagination(
				"SELECT e.id_trabajo_agricola, e.folio, COALESCE(DATE(e.fecha_captura), DATE(e.fecha_registro)) AS fecha, e.horometro_total,
					e.total_hrs, COALESCE(c.nombre, c.descripcion) AS vehiculo
				FROM otros.trabajo_agricola e
					LEFT JOIN otros.cat_codigos c ON c.id_cat_codigos = e.id_cat_cod_vehiculo
				".$sql."
				ORDER BY e.folio ASC
				", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'btrabajo_agricola'    => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0){
			$response['btrabajo_agricola'] = $res->result();
		}

		return $response;
	}

 	/**
 	 * Agrega un proveedor a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addEntradas($data)
	{
		$data['fno_formatos'] = $data['fno_formatos']>0? $data['fno_formatos']*2: 0;
		$response = '';
		for ($i=0; $i < $data['fno_formatos']; $i++) {
			$datos = [
				'id_empresa' => $data['did_empresa'],
				'folio'      => $this->getFolio($data['did_empresa']),
			];

			$this->db->insert('otros.trabajo_agricola', $datos);
			$response .= ','.$this->db->insert_id('otros.trabajo_agricola', 'id_trabajo_agricola');
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
						'id_cat_cod_vehiculo' => $this->input->post('codigoAreaId'),
						'id_operador'         => $this->input->post('foperadorId'),
						'horometro_ini'       => $this->input->post('fhorometro_ini'),
						'horometro_fin'       => $this->input->post('fhorometro_fin'),
						'horometro_total'     => $this->input->post('fhorometro_total'),
						'hora_ini'            => $this->input->post('fhr_ini'),
						'hora_fin'            => $this->input->post('fhr_fin'),
						'total_hrs'           => $this->input->post('fhr_total'),
						);
		}

		$this->db->update('otros.trabajo_agricola', $data, array('id_trabajo_agricola' => $id_entrega));
		$this->saveLabores($id_entrega);

		return array('error' => FALSE);
	}

	public function getFolio($id_empresa)
	{
		$folio = 1;
		$result = $this->db->query("SELECT folio FROM otros.trabajo_agricola
		                           WHERE id_empresa = {$id_empresa} ORDER BY folio DESC LIMIT 1");
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

		$sql_res = $this->db->query("SELECT id_trabajo_agricola, id_empresa, folio, fecha_registro, fecha_captura, id_cat_cod_vehiculo,
																	id_operador, horometro_ini, horometro_fin, horometro_total, substring(hora_ini::text FROM 1 FOR 5) AS hora_ini,
																	substring(hora_fin::text FROM 1 FOR 5) AS hora_fin, substring(total_hrs::text FROM 1 FOR 5) AS total_hrs
		                           FROM otros.trabajo_agricola
		                           WHERE id_trabajo_agricola = ".$id_entrega);
		$data['info'] = array();

		if ($sql_res->num_rows() > 0)
		{
			$data['info']	= $sql_res->row();

			if ($basic_info == false) {
				$datares = $this->db->query("SELECT ef.row, ef.id_labor, ef.id_centro_costo, substring(ef.hora_ini::text FROM 1 FOR 5) AS hora_ini,
																COALESCE(c.nombre, c.descripcion) AS centro_costo, l.nombre AS labor
		                           FROM otros.trabajo_agricola_labores ef
		                           	LEFT JOIN compras_salidas_labores l ON l.id_labor = ef.id_labor
		                           	LEFT JOIN otros.cat_codigos c ON c.id_cat_codigos = ef.id_centro_costo
		                           WHERE ef.id_trabajo_agricola = ".$id_entrega)->result();
				$data['info']->labores = $datares;

				if (isset($data['info']->id_cat_cod_vehiculo)) {
					$this->load->model('catalogos_sft_model');
					$data['info']->vehiculo = $this->catalogos_sft_model->getInfoCatCodigos($data['info']->id_cat_cod_vehiculo);
				}
				if (isset($data['info']->id_empresa)) {
					$this->load->model('empresas_model');
					$data['info']->empresa = $this->empresas_model->getInfoEmpresa($data['info']->id_empresa, true)['info'];
				}
				if (isset($data['info']->id_operador)) {
					$this->load->model('usuarios_model');
					$data['info']->operador = $this->usuarios_model->get_usuario_info($data['info']->id_operador, true)['info'][0];
				}
			}
		}
		$sql_res->free_result();


		return $data;
	}

	/**
	 * ******* CUENTAS DE PROVEEDORES ****************
	 * ***********************************************
	 * Agrega o actualiza cuentas del proveedor
	 * @param [type] $id_entrega [description]
	 */
	private function saveLabores($id_entrega)
	{
		$this->db->delete('otros.trabajo_agricola_labores', array('id_trabajo_agricola' => $id_entrega));

		if ( is_array($this->input->post('plaborId')) )
		{
			foreach ($this->input->post('plaborId') as $key => $value)
			{
				if ($_POST['plaborId'][$key]!='' && $_POST['ccostoId'][$key]!='' && $_POST['ptiempo'][$key]!='') {
					$data = array('id_trabajo_agricola' => $id_entrega,
									'row'             => $key,
									'id_labor'        => $_POST['plaborId'][$key],
									'id_centro_costo' => $_POST['ccostoId'][$key],
									'hora_ini'        => $_POST['ptiempo'][$key],
								);
					$this->db->insert('otros.trabajo_agricola_labores', $data);
				}
			}
		}

		// $this->db->update('otros.entrega_fruta', ['total' => $total_cantidad], array('id_entrega_fruta' => $id_entrega));
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

    $x = $y = 5;
    $no_rec = 1;
    foreach ($hojas as $key => $value) {
    	if ($key % 2 == 0) {
    		$pdf->AddPage();
    		$x = $y = 5;
    		$no_rec = 1;
    	}
    	if ($no_rec === 2)
    		$y = 137.5;
    	$this->printRecibo($value, $pdf, $x, $y);
    	$no_rec++;
    }
    $pdf->Output('Reporte.pdf', 'I');
  }

  public function printRecibo($id_entrega, &$pdf=null, &$x=0, &$y=0)
  {
  	$data = $this->getFormatoInfo($id_entrega)['info'];
  	$pdf->SetFont('Arial','B',10);
  	// $pdf->SetXY($x, $y);

		$pdf->Image(APPPATH.(str_replace(APPPATH, '', $pdf->logo)), $x+155, $y, 30);

  	$pdf->SetXY($x, $y);
    $pdf->SetAligns(['L']);
    $pdf->SetWidths([206]);
    $pdf->Row(['BITACORA DE TRABAJO AGRICOLA'], false, false);
    $pdf->SetFont('Arial','B',9);
    $pdf->SetX($x);
    $pdf->Row([(isset($data->empresa)? $data->empresa->nombre_fiscal: '')], false, false);

  	$pdf->SetFont('Arial', 'B', 8);
    $pdf->SetAligns(['L', 'L', 'C']);
    $pdf->SetWidths([29, 120, 58]);
    $pdf->SetX($x);
    $pdf->Row(['FECHA', 'VEHICULO', 'FOLIO'], false, true);
    $pdf->SetX($x);
    $pdf->Row([(isset($data->fecha_captura)? String::fechaAT($data->fecha_captura): ''),
    						(isset($data->vehiculo->nombre)? $data->vehiculo->nombre: ''),
    						(isset($data->folio)? $data->folio: '')], false, true);

    $pdf->SetAligns(['L', 'R', 'L', 'R', 'L', 'R']);
    $pdf->SetWidths([34.5, 34.5, 34.5, 34.5, 34.5, 34.5]);
    $pdf->SetX($x);
    $pdf->Row(['Horometro Ini', (isset($data->horometro_ini)? $data->horometro_ini: ''),
    					'Horometro Fin', (isset($data->horometro_fin)? $data->horometro_fin: ''),
    					'Km', (isset($data->horometro_total)? $data->horometro_total: '')], false, true);
    $pdf->SetX($x);
    $pdf->Row(['Hora Ini', (isset($data->hora_ini)? $data->hora_ini: ''),
    					'Hora Fin', (isset($data->hora_fin)? $data->hora_fin: ''),
    					'Horas', (isset($data->total_hrs)? $data->total_hrs: '')], false, true);

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetAligns(['C', 'C', 'C']);
    $pdf->SetWidths([29, 89, 89]);
    $pdf->SetX($x);
    $pdf->Row(['Hrs', 'Labor', 'Centro costo'], false, true);
    if (isset($data->labores) && count($data->labores) > 0) {
    	foreach ($data->labores as $key => $value) {
    		$pdf->SetX($x);
		    $pdf->Row([$value->hora_ini, $value->labor, $value->centro_costo], false, true);
    	}
    } else {
	    for ($i=0; $i < 7; $i++) {
		    $pdf->SetX($x);
		    $pdf->Row(['', '', ''], false, true);
	    }
	  }

    $pdf->SetAligns(['C', 'C']);
    $pdf->SetWidths([103, 103]);
    $pdf->SetX($x);
    $pdf->Row(['Operador', 'Supervisor'], false, false);
    $pdf->SetX($x);
    $pdf->Row(['_________________________________________________________', '_________________________________________________________'], false, false);
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */