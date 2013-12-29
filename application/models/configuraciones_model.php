<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class configuraciones_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	/**
	 * Modificar la informacion de un proveedor
	 * @param  [type] $id_vehiculo [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function modificarConfiguracion()
	{
		$data = array(
			'aguinaldo'        => $this->input->post('daguinaldo'),
			'prima_vacacional' => $this->input->post('dprima_vacacional'),
			'puntualidad'      => $this->input->post('dpuntualidad'),
			'asistencia'       => $this->input->post('dasistencia'),
			'despensa'         => $this->input->post('ddespensa'),
			);
		$this->db->update('nomina_configuracion', $data, array('id_configuracion' => '1'));

		foreach ($this->input->post('anio1') as $key => $anio1)
		{
			$data = array('dias' => $_POST['dias'][$key]);
			$this->db->update('nomina_configuracion_vacaciones', $data, array('anio1' => $anio1, 'anio2' => $anio2));
		}

		$data = array(
			'zona_a' => $this->input->post('dzona_a'),
			'zona_b' => $this->input->post('dzona_b'),
			'zona_c' => $this->input->post('dzona_b'),
			);
		$this->db->update('nomina_salarios_minimos', $data, array('id' => '1'));

		return array('error' => FALSE);
	}

	/**
	 * Obtiene la informacion de un camion
	 * @param  boolean $id_camion [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getConfiguraciones()
	{
		$data['conf'] = array();
		$data['conf_vacaciones'] = array();
		$data['salarios_minimos'] = array();

		$sql_res = $this->db->select("id_configuracion, aguinaldo, prima_vacacional, puntualidad, asistencia, despensa" )
							->from("nomina_configuracion")
							->where("id_configuracion", '1')->get();
		if ($sql_res->num_rows() > 0)
			$data['conf']	= $sql_res->row();
		$sql_res->free_result();

		$sql_res = $this->db->select("anio1, anio2, dias" )
							->from("nomina_configuracion_vacaciones")
							->get();
		if ($sql_res->num_rows() > 0)
			$data['conf_vacaciones']	= $sql_res->result();
		$sql_res->free_result();

		$sql_res = $this->db->select("id, zona_a, zona_b, zona_c" )
							->from("nomina_salarios_minimos")
							->where("id", '1')->get();
		if ($sql_res->num_rows() > 0)
			$data['salarios_minimos']	= $sql_res->row();
		$sql_res->free_result();


		return $data;
	}

	/**
	 * Obtiene el listado de camiones para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en las placas, modelo, marca
	 */
	public function getVehiculosAjax(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND ( lower(placa) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' OR
								lower(modelo) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' OR
								lower(marca) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' )";

		$res = $this->db->query("
				SELECT id_vehiculo, placa, modelo, marca, status, (placa || ' ' || modelo || ' ' || marca) AS nombre
				FROM compras_vehiculos
				WHERE status = 't' ".$sql."
				ORDER BY placa ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_vehiculo,
						'label' => $itm->nombre,
						'value' => $itm->nombre,
						'item'  => $itm,
				);
			}
		}

		return $response;
	}


	/**
	 * Reporte de existencias por costo
	 * @return [type] [description]
	 */
	public function getRCombustibleData()
	{
		$sql = '';

		//Filtros para buscar
		$_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
		$_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
		$fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha1']: $_GET['ffecha2'];

		if($this->input->get('fid_vehiculo') == '') $_GET['fid_vehiculo'] = 0;
		$sql .= " AND cv.id_vehiculo = ".$this->input->get('fid_vehiculo');

		// $this->load->model('empresas_model');
		// $client_default = $this->empresas_model->getDefaultEmpresa();
		// $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
		// $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
	    // if($this->input->get('did_empresa') != ''){
	    //   $sql .= " AND p.id_empresa = '".$this->input->get('did_empresa')."'";
	    // }

		$res = $this->db->query(
			"SELECT cv.id_vehiculo, (placa || ' ' || modelo || ' ' || marca) AS nombre, cvg.kilometros, cvg.litros, cvg.precio, Date(c.fecha) AS fecha, c.total
			FROM compras AS c 
				INNER JOIN compras_vehiculos_gasolina AS cvg ON c.id_compra = cvg.id_compra
				INNER JOIN compras_vehiculos AS cv ON cv.id_vehiculo = c.id_vehiculo
			WHERE c.status<>'ca' AND c.tipo_vehiculo='g' {$sql}
			ORDER BY c.fecha ASC
			");
		
		$response = array('gasolina' => array(), 'gastos' => array());
		if($res->num_rows() > 0)
		{
			$response['gasolina'] = $res->result();
		}
		$res->free_result();

		$res = $this->db->query(
			"SELECT c.id_compra, (c.serie || c.folio) AS folio, Date(c.fecha) AS fecha, c.total, c.concepto, (cv.placa || ' ' || cv.modelo || ' ' || cv.marca) AS nombre
			FROM compras AS c 
				INNER JOIN compras_vehiculos AS cv ON cv.id_vehiculo = c.id_vehiculo
			WHERE c.status<>'ca' AND c.tipo_vehiculo='ot' {$sql}
			ORDER BY c.fecha ASC
			");
		if($res->num_rows() > 0)
			$response['gastos'] = $res->result();
		$res->free_result();

		return $response;
	}
	/**
	 * Reporte rendimiento de combustible por costo pdf
	 */
	public function getRCombustiblePdf()
	{
		$res = $this->getRCombustibleData();

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');
		$pdf->titulo2 = 'Reporte de Vehiculo';
		$pdf->titulo3 = (isset($res['gasolina'][0]->nombre)? $res['gasolina'][0]->nombre: '')."\n";
		$pdf->titulo3 .= 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2');
		$pdf->AliasNbPages();
		//$pdf->AddPage();
		$pdf->SetFont('Arial','',8);
		
		$aligns = array('C', 'R', 'R', 'R', 'R', 'R');
		$widths = array(18, 36, 37, 37, 37, 37);
		$header = array('Fecha', 'Kilometros', 'Litros', 'Km/L', 'L/100Km', 'Importe');

		$total_gasolina = $total_kilometros = $total_litros = 0;
		foreach($res['gasolina'] as $key => $item){
			$band_head = false;
			if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
				$pdf->AddPage();

				if ($key == 0)
				{
					$pdf->SetFont('Arial','B',11);
					$pdf->SetX(6);
					$pdf->SetAligns(array('L'));
					$pdf->SetWidths(array(120));
					$pdf->Row(array('Bitácora de Rendimiento de Combustible'), false, false);
				}
				
				$pdf->SetFont('Arial','B',8);
				$pdf->SetTextColor(255,255,255);
				$pdf->SetFillColor(160,160,160);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($header, true);
			}
			
			$pdf->SetFont('Arial','',8);
			$pdf->SetTextColor(0,0,0);
			$precio = $item->total / ($item->litros>0? $item->litros: 1);
			$datos = array($item->fecha, 
				String::formatoNumero($item->kilometros, 2, ''),
				String::formatoNumero($item->litros, 2, ''),
				// String::formatoNumero($precio, 2, ''),
				'', '',
				String::formatoNumero($item->total, 2, '$', false),
				);
			if ($key > 0)
			{
				$rendimiento = ($item->kilometros - $res['gasolina'][$key-1]->kilometros)/($item->litros>0? $item->litros: 1);
				$datos[3] = String::formatoNumero( $rendimiento , 2, '');
				$datos[4] = String::formatoNumero( (100/$rendimiento) , 2, '');

				$total_kilometros += $item->kilometros - $res['gasolina'][$key-1]->kilometros;
				$total_litros     += $item->litros;
			}
			$total_gasolina += $item->total;
			
			$pdf->SetX(6);
			$pdf->SetAligns($aligns);
			$pdf->SetWidths($widths);
			$pdf->Row($datos, false);
		}

		$pdf->SetFont('Arial','B',8);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetX(6);
		$pdf->SetAligns($aligns);
		$pdf->SetWidths($widths);
		$total_rendimiento = ($total_kilometros/($total_litros>0? $total_litros: 1));
		$pdf->Row(array('', 
					String::formatoNumero( $total_kilometros , 2, ''), 
					String::formatoNumero( $total_litros , 2, ''), 
					String::formatoNumero( $total_rendimiento , 2, ''), 
					String::formatoNumero( (100/($total_rendimiento>0? $total_rendimiento: 1)) , 2, ''), 
					String::formatoNumero($total_gasolina, 2, '$', false),
				), true);


		//Otros gastos asignados al vehiculo
		$aligns = array('C', 'L', 'L', 'L', 'R');
		$widths = array(18, 65, 20, 70, 30);
		$header = array('Fecha', 'Vehiculo', 'Folio', 'Concepto', 'Importe');

		$pdf->SetFont('Arial','B',11);
		$pdf->SetTextColor(0,0,0);
		$pdf->SetX(6);
		$pdf->SetAligns(array('L'));
		$pdf->SetWidths(array(120));
		$pdf->Row(array('Otros Gastos'), false, false);

		$pdf->SetFont('Arial','B',8);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetFillColor(160,160,160);
		$pdf->SetX(6);
		$pdf->SetAligns($aligns);
		$pdf->SetWidths($widths);
		$pdf->Row($header, true);

		$total_gasto = 0;
		foreach($res['gastos'] as $key => $item){
			$band_head = false;
			if($pdf->GetY() >= $pdf->limiteY){ //salta de pagina si exede el max
				$pdf->AddPage();
				
				$pdf->SetFont('Arial','B',8);
				$pdf->SetTextColor(255,255,255);
				$pdf->SetFillColor(160,160,160);
				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($header, true);
			}
			
			$pdf->SetFont('Arial','',8);
			$pdf->SetTextColor(0,0,0);
			$datos = array($item->fecha, 
				$item->nombre,
				$item->folio,
				$item->concepto,
				String::formatoNumero($item->total, 2, '$', false),
				);
			$total_gasto += $item->total;
			
			$pdf->SetX(6);
			$pdf->SetAligns($aligns);
			$pdf->SetWidths($widths);
			$pdf->Row($datos, false);
		}

		$pdf->SetFont('Arial','B',8);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetX(6);
		$pdf->SetAligns($aligns);
		$pdf->SetWidths($widths);
		$pdf->Row(array('', '', '', '',
					String::formatoNumero($total_gasto, 2, '$', false),
				), true);

		//Totales
		$pdf->SetFont('Arial','B',8);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetXY(6, $pdf->GetY()+5);
		$pdf->SetAligns($aligns);
		$pdf->SetWidths(array(20, 40, 20, 40, 20, 40));
		$pdf->Row(array('Gasolina', String::formatoNumero($total_gasolina, 2, '$', false), 
						'Otros', String::formatoNumero($total_gasto, 2, '$', false), 
						'Total', String::formatoNumero($total_gasolina+$total_gasto, 2, '$', false) 
						), true);
		
		$pdf->Output('vehiculo.pdf', 'I');
	}

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */