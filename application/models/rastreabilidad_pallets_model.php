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
		
		if($this->input->get('ffecha') != '')
			$sql .= ($sql==''? 'WHERE': ' AND')." Date(rp.fecha) = '".$this->input->get('ffecha')."'";

		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." rp.status = '".$this->input->get('fstatus')."'";

		$query = BDUtil::pagination("SELECT 
					rp.id_pallet, rp.folio, Date(rp.fecha) AS fecha, rp.no_cajas, c.nombre, Coalesce(Sum(rpr.cajas), 0) AS cajas
				FROM rastria_pallets AS rp 
					LEFT JOIN rastria_pallets_rendimiento AS rpr ON rp.id_pallet = rpr.id_pallet
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

	public function getInfoPallet($id_pallet, $basic_info=FALSE, $cajas_libres=true){
		$result = $this->db->query("SELECT * FROM rastria_pallets_lista WHERE id_pallet = {$id_pallet}");
		$response['info'] = array();
		if($result->num_rows() > 0)
		{
			$response['info'] = $result->row();
			$result->free_result();

			if(!$basic_info)
			{
				$result = $this->db->query("SELECT rpr.id_pallet, rr.id_rendimiento, rr.lote, Date(rr.fecha) AS fecha, rpr.cajas
					FROM rastria_pallets_rendimiento AS rpr 
						INNER JOIN rastria_rendimiento AS rr ON rpr.id_rendimiento = rr.id_rendimiento
					WHERE id_pallet = {$id_pallet}");
				$response['rendimientos'] = $result->result();

				if($cajas_libres){
					$rendimientos_libres     = $this->getRendimientoLibre($response['info']->id_clasificacion);
					$response['rend_libres'] = $rendimientos_libres['rendimientos'];
				}
			}
		}
		return $response;
	}

	/**
	 * Obtiene el siguiente folio para el pallet
	 * @return [type] [description]
	 */
 	public function getNextFolio(){
 		$result = $this->db->query("SELECT (folio+1) AS folio FROM rastria_pallets ORDER BY folio DESC LIMIT 1")->row();
 		return (is_object($result)? $result->folio: '1');
 	}

 	/**
 	 * Obtiene la lista de rendimientos con cajas disponibles para agregarlos a los pallets
 	 * de una clasificacion espesifica
 	 * @param  [type] $id_clasificacion [description]
 	 * @return [type]                   [description]
 	 */
 	public function getRendimientoLibre($id_clasificacion){
 		$result = $this->db->query("SELECT rr.id_rendimiento, rr.lote, Date(rr.fecha) AS fecha, rcl.rendimiento, rcl.cajas, rcl.libres
 		                           FROM rastria_rendimiento AS rr 
																	INNER JOIN rastria_cajas_libres AS rcl ON rr.id_rendimiento = rcl.id_rendimiento
 		                           WHERE rcl.id_clasificacion = {$id_clasificacion}
 		                           ORDER BY fecha ASC, lote ASC");
 		$response = array('rendimientos' => array());
		if($result->num_rows() > 0)
			$response['rendimientos'] = $result->result();

		$result->free_result();

		return $response;
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
			
			$this->addPalletRendimientos($id_pallet);

			// $this->addPalletRendimiento($data['id_clasificacion']);

			return array('msg' => 3, $id_pallet);
		// }
		// return array('msg' => 4, 0);
 	}

 	/**
 	 * Modifica un pallet a la bd
 	 * @param [type] $data array con los valores a insertar
 	 */
 	public function updatePallet($id_pallet, $data=NULL){
 		if ($data==NULL)
		{
			$data = array(
						'id_clasificacion' => $this->input->post('fid_clasificacion'),
						'folio'            => $this->input->post('ffolio'),
						'no_cajas'         => $this->input->post('fcajas'),
						);
		}

		$this->db->update('rastria_pallets', $data, "id_pallet = {$id_pallet}");
		
		$this->db->delete('rastria_pallets_rendimiento', "id_pallet = {$id_pallet}");
		$this->addPalletRendimientos($id_pallet);

		return array('msg' => 5);
 	}

	public function addPalletRendimientos($id_pallet, $data=NULL){
		if ($data==NULL)
		{
			$cajas_faltantes = $this->input->post('fcajas');

			if(is_array($this->input->post('rendimientos')))
			{
				foreach ($this->input->post('rendimientos') as $key => $value) 
				{
					$value = explode('|', $value);
					$cajas_agregar = ($value[1]>=$cajas_faltantes? $cajas_faltantes: $value[1]);

					if(isset($data[$value[0]])){
						$data[$value[0]]['cajas'] += $cajas_agregar;
					}else{
						$data[$value[0]] = array(
							'id_pallet'        => $id_pallet,
							'id_rendimiento'   => $value[0],
							'id_clasificacion' => $this->input->post('fid_clasificacion'),
							'cajas'            => $cajas_agregar,
							);
					}
					$cajas_faltantes -= $cajas_agregar;
				}
			}
		}

		if(count($data) > 0)
			$this->db->insert_batch('rastria_pallets_rendimiento', $data);

		return true;
	}

	public function pallet_pdf($id_pallet){
		// Obtiene los datos del reporte.
    $data = $this->getInfoPallet($id_pallet, false, false);


      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', array(105, 140));
      $pdf->show_head = false;

      $pdf->AliasNbPages();
      $pdf->AddPage();
      $pdf->SetFont('helvetica','', 8);

      $pdf->SetXY(25, 3);
      $pdf->Image(APPPATH.'images/logo.png');

      $pdf->SetTextColor(0,0,0);
      $pdf->SetX(6);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(46, 46));
      $pdf->Row(array('DESTINO:', "No CLASIF: {$data['info']->nombre}"), false);
      $pdf->SetX(6);
      $pdf->SetAligns(array('C', 'C'));
      $pdf->Row(array('LOTE', 'CAJAS'), false);

      foreach ($data['rendimientos'] as $key => $value) {
      	$fecha = strtotime($value->fecha);
      	$pdf->SetX(6);
	      $pdf->Row(array(date("Ww").' '.$value->lote, $value->cajas), false);
      }
      $pdf->SetX(6);
	    $pdf->Row(array('No. TARIMA', $data['info']->no_cajas), false);

	    $pdf->SetX(6);
	    $pdf->SetAligns(array('L'));
	    $pdf->SetWidths(array(66));
	    $pdf->Row(array('FECHA: '.$data['info']->fecha), false, false);

      // $aligns = array('C', 'C', 'C', 'L', 'C', 'C', 'C', 'C', 'C');
      // $widths = array(6, 20, 17, 55, 16, 25, 25, 17, 25);
      // $header = array('',   'BOLETA', 'CUENTA','NOMBRE', 'PROM',
      //                 'CAJAS', 'KILOS', 'PRECIO','IMPORTE');

      // $totalPagado    = 0;
      // $totalNoPagado  = 0;
      // $totalCancelado = 0;

      // foreach($rde as $key => $calidad)
      // {
      //   if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
      //   {
      //     $pdf->AddPage();

      //     $pdf->SetFont('helvetica','B', 8);
      //     $pdf->SetTextColor(0,0,0);
      //     $pdf->SetFillColor(160,160,160);
      //     $pdf->SetY($pdf->GetY()-2);
      //     $pdf->SetX(6);
      //     $pdf->SetAligns($aligns);
      //     $pdf->SetWidths($widths);
      //     $pdf->Row($header, false);
      //   }

      //   $pdf->SetFont('helvetica','', 9);
      //   $pdf->SetTextColor(0,0,0);

      //   $pdf->SetY($pdf->GetY()-1);
      //   $pdf->SetX(6);
      //   $pdf->SetAligns(array('L'));
      //   $pdf->SetWidths(array(206));
      //   $pdf->Row(array($calidad['calidad']), false, false);

      //   $pdf->SetFont('helvetica','',8);
      //   $pdf->SetTextColor(0,0,0);

      //   $promedio = 0;
      //   $cajas    = 0;
      //   $kilos    = 0;
      //   $precio   = 0;
      //   $importe  = 0;

      //   foreach ($calidad['cajas'] as $caja)
      //   {
      //     if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
      //     {
      //       $pdf->AddPage();

      //       $pdf->SetFont('helvetica','B', 8);
      //       $pdf->SetTextColor(0,0,0);
      //       $pdf->SetFillColor(160,160,160);
      //       $pdf->SetY($pdf->GetY()-2);
      //       $pdf->SetX(6);
      //       $pdf->SetAligns($aligns);
      //       $pdf->SetWidths($widths);
      //       $pdf->Row($header, false);
      //     }

      //     $promedio += $caja->promedio;
      //     $cajas    += $caja->cajas;
      //     $kilos    += $caja->kilos;
      //     $precio   += $caja->precio;
      //     $importe  += $caja->importe;

      //     if ($caja->pagado === 'p' || $caja->pagado === 'b')
      //       $totalPagado += $caja->importe;
      //     else
      //       $totalNoPagado += $caja->importe;

      //     $datos = array(($caja->pagado === 'p' || $caja->pagado === 'b') ? ucfirst($caja->pagado) : '',
      //                    $caja->folio,
      //                    $caja->cuenta_cpi,
      //                    substr($caja->proveedor, 0, 35),
      //                    $caja->promedio,
      //                    $caja->cajas,
      //                    $caja->kilos,
      //                    String::formatoNumero($caja->precio),
      //                    String::formatoNumero($caja->importe));

      //     $pdf->SetY($pdf->GetY()-2);
      //     $pdf->SetX(6);
      //     $pdf->SetAligns($aligns);
      //     $pdf->SetWidths($widths);
      //     $pdf->Row($datos, false, false);
      //   }

      //   $pdf->SetY($pdf->GetY()-1);
      //   $pdf->SetX(6);
      //   $pdf->SetAligns(array('R', 'C', 'C', 'C', 'C', 'C'));
      //   $pdf->SetWidths(array(98, 16, 25, 25, 17, 25));
      //   $pdf->Row(array(
      //     'TOTALES',
      //     String::formatoNumero($kilos/$cajas, 2, ''),
      //     $cajas,
      //     $kilos,
      //     String::formatoNumero($precio/count($calidad['cajas'])),
      //     String::formatoNumero($importe)), false, false);

      // }

      // if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
      // {
      //   $pdf->AddPage();
      // }

      // $pdf->SetFont('helvetica','B', 8);
      // // $pdf->SetX(6);
      // $pdf->SetY($pdf->getY() + 6);
      // $pdf->SetAligns(array('C', 'C', 'C', 'C'));
      // $pdf->SetWidths(array(50, 50, 50, 50));
      // $pdf->Row(array(
      //   'PAGADO',
      //   'NO PAGADO',
      //   'CANCELADO',
      //   'TOTAL IMPORTE'), false);

      // $totalImporte = (floatval($totalPagado) + floatval($totalNoPagado)) - floatval($data['cancelados']);

      // if($pdf->GetY() >= $pdf->limiteY) //salta de pagina si exede el max
      // {
      //   $pdf->AddPage();
      // }
      // $pdf->SetAligns(array('C', 'C', 'C', 'C'));
      // $pdf->SetWidths(array(50, 50, 50, 50));
      // $pdf->Row(array(
      //   String::formatoNumero($totalPagado),
      //   String::formatoNumero($totalNoPagado),
      //   String::formatoNumero($data['cancelados']),
      //   String::formatoNumero($totalImporte)), false);

      $pdf->Output('REPORTE_DIARIO.pdf', 'I');
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