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

    $anioVaca = $this->input->post('dzona_anio');
    // if(date("Y") >= 2023) {
    //   $anioVaca = date("Y");
    // }

    $this->db->delete('nomina_configuracion_vacaciones', ['anio' => $anioVaca]);
		foreach ($this->input->post('anio1') as $key => $anio1)
		{
			$data = array('dias' => $_POST['dias'][$key], 'anio1' => $anio1, 'anio2' => $_POST['anio2'][$key], 'anio' => $anioVaca);
			// $this->db->update('nomina_configuracion_vacaciones', $data, array('anio1' => $anio1, 'anio2' => $anio2));
      $this->db->insert('nomina_configuracion_vacaciones', $data);
		}

    // salarios minimos
		$sql_res = $this->db->select("id, zona_a, zona_b, zona_c, anio" )
              ->from("nomina_salarios_minimos")->where("anio", $this->input->post('dzona_anio'))->get();
    $data = array(
      'zona_a' => $this->input->post('dzona_a'),
      'zona_b' => $this->input->post('dzona_b'),
      'zona_c' => $this->input->post('dzona_b'),
      'anio'   => $this->input->post('dzona_anio'),
		);
    if ($sql_res->num_rows() > 0) {
		  $this->db->update('nomina_salarios_minimos', $data, array('anio' => $data['anio']));
    } else {
      $this->db->insert('nomina_salarios_minimos', $data);
    }

		//Tablas de ISR
		$sql_res = $this->db->select("anio")->from("nomina_semanal_art_113")->where("anio", $this->input->post('dzona_anio'))->get();
    foreach ($this->input->post('sem_id') as $key => $sem_id)
		{
      if ($sql_res->num_rows() > 0) {
  			$data = array('lim_inferior' => $_POST['sem_lim_inferior'][$key], 'lim_superior' => $_POST['sem_lim_superior'][$key],
  						'cuota_fija' => $_POST['sem_cuota_fija'][$key], 'porcentaje' => $_POST['sem_porcentaje'][$key]);
        $this->db->update('nomina_semanal_art_113', $data, array('id_art_113' => $sem_id));
      } else {
        $data = array('lim_inferior' => $_POST['sem_lim_inferior'][$key], 'lim_superior' => $_POST['sem_lim_superior'][$key],
              'cuota_fija' => $_POST['sem_cuota_fija'][$key], 'porcentaje' => $_POST['sem_porcentaje'][$key],
              'anio' => $this->input->post('dzona_anio'));
  			$this->db->insert('nomina_semanal_art_113', $data);
      }
		}
    $sql_res = $this->db->select("anio")->from("nomina_diaria_art_113")->where("anio", $this->input->post('dzona_anio'))->get();
		foreach ($this->input->post('dia_id') as $key => $dia_id)
		{
      if ($sql_res->num_rows() > 0) {
        $data = array('lim_inferior' => $_POST['dia_lim_inferior'][$key], 'lim_superior' => $_POST['dia_lim_superior'][$key],
              'cuota_fija' => $_POST['dia_cuota_fija'][$key], 'porcentaje' => $_POST['dia_porcentaje'][$key]);
        $this->db->update('nomina_diaria_art_113', $data, array('id_art_113' => $dia_id));
      } else {
        $data = array('lim_inferior' => $_POST['dia_lim_inferior'][$key], 'lim_superior' => $_POST['dia_lim_superior'][$key],
              'cuota_fija' => $_POST['dia_cuota_fija'][$key], 'porcentaje' => $_POST['dia_porcentaje'][$key],
              'anio' => $this->input->post('dzona_anio'));
        $this->db->insert('nomina_diaria_art_113', $data);
      }
		}
    $sql_res = $this->db->select("anio")->from("nomina_mensual_art_113")->where("anio", $this->input->post('dzona_anio'))->get();
    foreach ($this->input->post('mes_id') as $key => $mes_id)
    {
      if ($sql_res->num_rows() > 0) {
        $data = array('lim_inferior' => $_POST['mes_lim_inferior'][$key], 'lim_superior' => $_POST['mes_lim_superior'][$key],
              'cuota_fija' => $_POST['mes_cuota_fija'][$key], 'porcentaje' => $_POST['mes_porcentaje'][$key]);
        $this->db->update('nomina_mensual_art_113', $data, array('id_art_113' => $mes_id));
      } else {
        $data = array('lim_inferior' => $_POST['mes_lim_inferior'][$key], 'lim_superior' => $_POST['mes_lim_superior'][$key],
              'cuota_fija' => $_POST['mes_cuota_fija'][$key], 'porcentaje' => $_POST['mes_porcentaje'][$key],
              'anio' => $this->input->post('dzona_anio'));
        $this->db->insert('nomina_mensual_art_113', $data);
      }
    }

		//Tablas de Subsidios
		$sql_res = $this->db->select("anio")->from("nomina_semanal_subsidios")->where("anio", $this->input->post('dzona_anio'))->get();
    foreach ($this->input->post('sub_sem_id') as $key => $sub_sem_id)
		{
      if ($sql_res->num_rows() > 0) {
        $data = array('de' => $_POST['sub_sem_lim_inferior'][$key], 'hasta' => $_POST['sub_sem_lim_superior'][$key],
              'subsidio' => $_POST['sub_sem_subsidio'][$key]);
        $this->db->update('nomina_semanal_subsidios', $data, array('id_subsidio' => $sub_sem_id));
      } else {
        $data = array('de' => $_POST['sub_sem_lim_inferior'][$key], 'hasta' => $_POST['sub_sem_lim_superior'][$key],
              'subsidio' => $_POST['sub_sem_subsidio'][$key],
              'anio' => $this->input->post('dzona_anio'));
        $this->db->insert('nomina_semanal_subsidios', $data);
      }
		}
    $sql_res = $this->db->select("anio")->from("nomina_diaria_subsidios")->where("anio", $this->input->post('dzona_anio'))->get();
		foreach ($this->input->post('sub_dia_id') as $key => $sub_dia_id)
		{
      if ($sql_res->num_rows() > 0) {
        $data = array('de' => $_POST['sub_dia_lim_inferior'][$key], 'hasta' => $_POST['sub_dia_lim_superior'][$key],
              'subsidio' => $_POST['sub_dia_subsidio'][$key]);
        $this->db->update('nomina_diaria_subsidios', $data, array('id_subsidio' => $sub_dia_id));
      } else {
        $data = array('de' => $_POST['sub_dia_lim_inferior'][$key], 'hasta' => $_POST['sub_dia_lim_superior'][$key],
              'subsidio' => $_POST['sub_dia_subsidio'][$key],
              'anio' => $this->input->post('dzona_anio'));
        $this->db->insert('nomina_diaria_subsidios', $data);
      }
		}
    $sql_res = $this->db->select("anio")->from("nomina_mensual_subsidios")->where("anio", $this->input->post('dzona_anio'))->get();
    foreach ($this->input->post('sub_mes_id') as $key => $sub_mes_id)
    {
      if ($sql_res->num_rows() > 0) {
        $data = array('de' => $_POST['sub_mes_lim_inferior'][$key], 'hasta' => $_POST['sub_mes_lim_superior'][$key],
              'subsidio' => $_POST['sub_mes_subsidio'][$key]);
        $this->db->update('nomina_mensual_subsidios', $data, array('id_subsidio' => $sub_mes_id));
      } else {
        $data = array('de' => $_POST['sub_mes_lim_inferior'][$key], 'hasta' => $_POST['sub_mes_lim_superior'][$key],
              'subsidio' => $_POST['sub_mes_subsidio'][$key],
              'anio' => $this->input->post('dzona_anio'));
        $this->db->insert('nomina_mensual_subsidios', $data);
      }
    }

		return array('error' => FALSE);
	}

	/**
	 * Obtiene la informacion de un camion
	 * @param  boolean $id_camion [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getConfiguraciones($anio)
	{
    $data['conf']              = array();
    $data['conf_vacaciones']   = array();
    $data['salarios_minimos']  = array();
    $data['semanal_art113']    = array();
    $data['semanal_subsidios'] = array();
    $data['diaria_art113']     = array();
    $data['diaria_subsidios']  = array();
    $data['mensual_art113']    = array();
    $data['mensual_subsidios'] = array();

		$sql_res = $this->db->select("id_configuracion, aguinaldo, prima_vacacional, puntualidad, asistencia, despensa" )
							->from("nomina_configuracion")
							->where("id_configuracion", '1')->get();
		if ($sql_res->num_rows() > 0)
			$data['conf']	= $sql_res->row();
		$sql_res->free_result();

    $anioVaca = 2022;
    if(date("Y") >= 2023) {
      $anioVaca = date("Y");
    }

		$sql_res = $this->db->select("anio1, anio2, dias" )
							->from("nomina_configuracion_vacaciones")
              ->where('anio', $anio)
							->get();
		if ($sql_res->num_rows() > 0)
			$data['conf_vacaciones']	= $sql_res->result();
		$sql_res->free_result();

		$sql_res = $this->db->select("id, zona_a, zona_b, zona_c, anio" )
							->from("nomina_salarios_minimos")
							->where("anio", $anio)->get();
		if ($sql_res->num_rows() > 0)
			$data['salarios_minimos']	= $sql_res->row();
		$sql_res->free_result();


		$sql_res = $this->db->select("id_art_113, lim_inferior, lim_superior, cuota_fija, porcentaje" )
							->from("nomina_semanal_art_113")->where("anio", $anio)->get();
		if ($sql_res->num_rows() > 0)
			$data['semanal_art113']	= $sql_res->result();
		$sql_res->free_result();

		$sql_res = $this->db->select("id_subsidio, de, hasta, subsidio" )
							->from("nomina_semanal_subsidios")->where("anio", $anio)->get();
		if ($sql_res->num_rows() > 0)
			$data['semanal_subsidios']	= $sql_res->result();
		$sql_res->free_result();

		$sql_res = $this->db->select("id_art_113, lim_inferior, lim_superior, cuota_fija, porcentaje" )
							->from("nomina_diaria_art_113")->where("anio", $anio)->get();
		if ($sql_res->num_rows() > 0)
			$data['diaria_art113']	= $sql_res->result();
		$sql_res->free_result();

		$sql_res = $this->db->select("id_subsidio, de, hasta, subsidio" )
							->from("nomina_diaria_subsidios")->where("anio", $anio)->get();
		if ($sql_res->num_rows() > 0)
			$data['diaria_subsidios']	= $sql_res->result();
		$sql_res->free_result();

    $sql_res = $this->db->select("id_art_113, lim_inferior, lim_superior, cuota_fija, porcentaje" )
              ->from("nomina_mensual_art_113")->where("anio", $anio)->get();
    if ($sql_res->num_rows() > 0)
      $data['mensual_art113']  = $sql_res->result();
    $sql_res->free_result();

    $sql_res = $this->db->select("id_subsidio, de, hasta, subsidio" )
              ->from("nomina_mensual_subsidios")->where("anio", $anio)->get();
    if ($sql_res->num_rows() > 0)
      $data['mensual_subsidios'] = $sql_res->result();
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
				MyString::formatoNumero($item->kilometros, 2, ''),
				MyString::formatoNumero($item->litros, 2, ''),
				// MyString::formatoNumero($precio, 2, ''),
				'', '',
				MyString::formatoNumero($item->total, 2, '$', false),
				);
			if ($key > 0)
			{
				$rendimiento = ($item->kilometros - $res['gasolina'][$key-1]->kilometros)/($item->litros>0? $item->litros: 1);
				$datos[3] = MyString::formatoNumero( $rendimiento , 2, '');
				$datos[4] = MyString::formatoNumero( (100/$rendimiento) , 2, '');

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
					MyString::formatoNumero( $total_kilometros , 2, ''),
					MyString::formatoNumero( $total_litros , 2, ''),
					MyString::formatoNumero( $total_rendimiento , 2, ''),
					MyString::formatoNumero( (100/($total_rendimiento>0? $total_rendimiento: 1)) , 2, ''),
					MyString::formatoNumero($total_gasolina, 2, '$', false),
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
				MyString::formatoNumero($item->total, 2, '$', false),
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
					MyString::formatoNumero($total_gasto, 2, '$', false),
				), true);

		//Totales
		$pdf->SetFont('Arial','B',8);
		$pdf->SetTextColor(255,255,255);
		$pdf->SetXY(6, $pdf->GetY()+5);
		$pdf->SetAligns($aligns);
		$pdf->SetWidths(array(20, 40, 20, 40, 20, 40));
		$pdf->Row(array('Gasolina', MyString::formatoNumero($total_gasolina, 2, '$', false),
						'Otros', MyString::formatoNumero($total_gasto, 2, '$', false),
						'Total', MyString::formatoNumero($total_gasolina+$total_gasto, 2, '$', false)
						), true);

		$pdf->Output('vehiculo.pdf', 'I');
	}

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */