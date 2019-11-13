<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class contrarecibo_model extends CI_Model {
	private $pass_finkok = 'gamaL1!l';

	function __construct()
	{
		parent::__construct();
		$this->load->model('bitacora_model');
	}

	public function getContrarecibos($paginados = true)
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
			$sql = "WHERE ( lower(c.folio) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

		if($this->input->get('fstatus') != '')
			$sql .= ($sql==''? 'WHERE': ' AND')." c.status = '".$this->input->get('fstatus')."'";

		if($this->input->get('did_empresa') != '')
			$sql .= ($sql==''? 'WHERE': ' AND')." c.id_empresa='".$this->input->get('did_empresa')."'";

		if($this->input->get('did_proveedor') != '')
			$sql .= ($sql==''? 'WHERE': ' AND')." c.id_proveedor='".$this->input->get('did_proveedor')."'";

		$query = BDUtil::pagination(
				"SELECT c.id_contrarecibo, c.fecha, c.folio, c.total, p.id_proveedor, p.nombre_fiscal AS proveedor,
					e.id_empresa, e.nombre_fiscal AS empresa, c.status
				FROM otros.contrarecibos c
					INNER JOIN empresas e ON e.id_empresa = c.id_empresa
					INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
				{$sql}
				ORDER BY c.fecha DESC, c.folio DESC", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'contrarecibos'    => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0){
			$response['contrarecibos'] = $res->result();
		}

		return $response;
	}

 	/**
 	 * Agrega un proveedor a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addContrarecibo($data=NULL)
	{
		if ($data==NULL)
		{
			$data = array(
						'fecha'        => $this->input->post('ffecha1'),
						'folio'        => $this->getFolio(),
						'id_proveedor' => $this->input->post('did_proveedor'),
						'total'        => $this->input->post('dtotal'),
						'id_empresa'   => $this->input->post('did_empresa'),
						'id_usuario'   => $this->session->userdata('id_usuario'),
						);
		}

		$this->db->insert('otros.contrarecibos', $data);
		$id_contrarecibo = $this->db->insert_id('otros.contrarecibos', 'id_contrarecibo');

		// // Bitacora
  //   $this->bitacora_model->_insert('proveedores', $id_contrarecibo,
  //                                   array(':accion'    => 'el proveedor', ':seccion' => 'proveedores',
  //                                         ':folio'     => $data['nombre_fiscal'],
  //                                         ':id_empresa' => $data['id_empresa'],
  //                                         ':empresa'   => 'en '.$this->input->post('fempresa')));

		$this->addFacturas($id_contrarecibo);

		return array('error' => FALSE);
	}

	/**
	 * Modificar la informacion de un proveedor
	 * @param  [type] $id_contrarecibo [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateContrarecibo($id_contrarecibo, $data=NULL)
	{
		// $info = $this->getProveedorInfo($id_contrarecibo);

		if ($data==NULL)
		{
			$data = array(
					'fecha'        => $this->input->post('ffecha1'),
					'folio'        => $this->input->post('ffolio'),
					'id_proveedor' => $this->input->post('did_proveedor'),
					'total'        => $this->input->post('dtotal'),
					'id_empresa'   => $this->input->post('did_empresa'),
					'id_usuario'   => $this->session->userdata('id_usuario'),
					);
			// // Bitacora
			//   $id_bitacora = $this->bitacora_model->_update('proveedores', $id_proveedor, $data,
			//                             array(':accion'       => 'el proveedor', ':seccion' => 'proveedores',
			//                                   ':folio'        => $data['nombre_fiscal'],
			//                                   ':id_empresa'   => $data['id_empresa'],
			//                                   ':empresa'      => 'en '.$this->input->post('fempresa'),
			//                                   ':id'           => 'id_proveedor',
			//                                   ':titulo'       => 'Proveedor'));
		}
		// else {
		// 	if(isset($data['status']) && $data['status'] === 'e') {
		// 		$proveerd = $this->getProveedorInfo($id_proveedor);
		// 		// Bitacora
		// 		$this->bitacora_model->_cancel('proveedores', $id_proveedor,
		// 		                                array(':accion'     => 'el proveedor', ':seccion' => 'proveedores',
		// 		                                      ':folio'      => $proveerd['info']->nombre_fiscal,
		// 		                                      ':id_empresa' => $proveerd['info']->id_empresa,
		// 		                                      ':empresa'    => 'de '.$proveerd['info']->empresa->nombre_fiscal));
		// 	}
		// }

		$this->db->update('otros.contrarecibos', $data, array('id_contrarecibo' => $id_contrarecibo));
		$this->addFacturas($id_contrarecibo);

		return array('error' => FALSE);
	}

	public function getFolio()
	{
		$folio = $this->db->query("SELECT folio FROM otros.contrarecibos ORDER BY id_contrarecibo DESC LIMIT 1")->row();
		if (isset($folio->folio)) {
			return $folio->folio+1;
		} else
			return 1;
	}

	/**
	 * Obtiene la informacion de un proveedor
	 * @param  boolean $id_contrarecibo [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getContrareciboInfo($id_contrarecibo=FALSE, $basic_info=FALSE)
	{
		$id_contrarecibo = $id_contrarecibo ? $id_contrarecibo : (isset($_GET['id'])? $_GET['id']: 0) ;

		$sql_res = $this->db->select("ct.id_contrarecibo, ct.fecha, ct.folio, ct.id_proveedor, ct.total, ct.status, ct.id_empresa,
													(usuarios.nombre || ' ' || usuarios.apellido_paterno) AS usuario" )
												->from("otros.contrarecibos AS ct")
													->join('usuarios', 'usuarios.id = ct.id_usuario', 'left')
												->where("id_contrarecibo", $id_contrarecibo)
												->get();
		$data['info'] = array();

		if ($sql_res->num_rows() > 0)
		{
			$data['info']	= $sql_res->row();

			if ($basic_info == False) {
				$this->load->model('empresas_model');
				$data['info']->empresa = $this->empresas_model->getInfoEmpresa($data['info']->id_empresa)['info'];
				$this->load->model('proveedores_model');
				$data['info']->proveedor = $this->proveedores_model->getProveedorInfo($data['info']->id_proveedor)['info'];
			}
		}
		$sql_res->free_result();

		return $data;
	}

	/**
	 * ******* CUENTAS DE PROVEEDORES ****************
	 * ***********************************************
	 * Agrega o actualiza cuentas del proveedor
	 * @param [type] $id_contrarecibo [description]
	 */
	private function addFacturas($id_contrarecibo)
	{
		if ( is_array($this->input->post('facturas_folio')) )
		{
			$this->db->delete('otros.contrarecibos_facturas', "id_contrarecibo = ".$id_contrarecibo);
			foreach ($this->input->post('facturas_folio') as $key => $value)
			{
				if ($_POST['facturas_folio'][$key]!='' && $_POST['facturas_fecha'][$key]!='' && $_POST['facturas_importe'][$key]!='') {
					$data = array('id_contrarecibo' => $id_contrarecibo,
									'norow'       => $key,
									'folio'       => $_POST['facturas_folio'][$key],
									'fecha'       => $_POST['facturas_fecha'][$key],
									'importe'     => $_POST['facturas_importe'][$key],
									'observacion' => $_POST['facturas_observacion'][$key],
								);
					$this->db->insert('otros.contrarecibos_facturas', $data);
				}
			}
		}
	}

	/**
	 * Obtiene el listado de proveedores
	 * @return [type] [description]
	 */
	public function getFacturas($id_contrarecibo){
		$res = $this->db->query("
				SELECT norow, folio, fecha, importe, observacion
				FROM otros.contrarecibos_facturas
				WHERE id_contrarecibo = {$id_contrarecibo}
				ORDER BY norow ASC");

		$response = array();
		if($res->num_rows() > 0){
			$response = $res->result();
		}

		return $response;
	}

  public function imprimirContrarecibo($id_contrarecibo, $path = null)
  {
    $this->load->model('compras_areas_model');
    $this->load->model('catalogos_sft_model');

    $data = $this->getContrareciboInfo($id_contrarecibo);
    $facturas = $this->getFacturas($id_contrarecibo);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 130));
    $pdf->show_head = false;
    $pdf->AddPage();
    $pdf->AddFont($pdf->fount_num, '');

    // Título
    $pdf->SetFont($pdf->fount_txt, '', 8);
    $pdf->SetXY(0, 3);
    $pdf->MultiCell($pdf->pag_size[0], 4, $data['info']->empresa->nombre_fiscal, 0, 'C');
    $pdf->SetFont($pdf->fount_txt, '', 7);
    $pdf->SetX(0);
    $pdf->MultiCell($pdf->pag_size[0], 4, 'CONTRARECIBO', 0, 'C');

    $pdf->SetX(0);
    $pdf->MultiCell($pdf->pag_size[0], 2, '--------------------------------------------------------------------------', 0, 'L');
    $pdf->SetFont($pdf->fount_txt, '', $pdf->font_size);

    $pdf->SetWidths(array(31, 31));
    $pdf->SetAligns(array('L','R'));
    $pdf->SetX(0);
    $pdf->Row2(array('Folio: '.$data['info']->folio, String::fechaAT($data['info']->fecha)), false, false, 5);

    $pdf->SetWidths(array(62));
    $pdf->SetAligns(array('L'));
    $pdf->SetFounts(array($pdf->fount_txt), array(-1));
    $pdf->SetX(0);
    $pdf->Row2(array('Proveedor: '.$data['info']->proveedor->nombre_fiscal), false, false, 7);

    $pdf->SetX(0);
    $pdf->MultiCell($pdf->pag_size[0], 2, '--------------------------------------------------------------------------', 0, 'L');
    $pdf->SetFont($pdf->fount_txt, '', $pdf->font_size);

    $pdf->SetWidths(array(13, 15, 15, 19));
    $pdf->SetAligns(array('L','L','R','L'));
    $pdf->SetFounts(array($pdf->fount_txt), array(-1,-1,-1,-2));
    $pdf->SetX(0);
    $pdf->Row2(array('NUMERO', 'FECHA', 'IMPORTE', 'OBSERVACION'), false, true, 5);

    $pdf->SetFounts(array($pdf->fount_num,$pdf->fount_txt,$pdf->fount_num,$pdf->fount_num),
                   array(.5,-1,-1,-2));
    $subtotal = $iva = $total = $retencion = $ieps = 0;
    $tipoCambio = 0;
    $codigoAreas = array();
    $inc = 1;
    foreach ($facturas as $key => $prod) {
      $pdf->SetXY(0, $pdf->GetY()-$inc);
      $pdf->Row(array(
        $prod->folio,
        $prod->fecha,
        String::formatoNumero($prod->importe, 2, '', true),
        $prod->observacion
      ), false, false);
      $inc = 2;
    }

    // $pdf->SetX(29);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(13, 20));
    // $pdf->SetX(29);
    // $pdf->Row(array('TOTAL', String::formatoNumero($total, 2, '$', false)), false, true);
    $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_num), array(-1,-1));
    $pdf->SetX(30);
    $pdf->Row2(array('TOTAL', String::formatoNumero($data['info']->total, 2, '', true)), false, true, 5);

    $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_num), array(-1,-1));
    $pdf->SetAligns(array('C'));
    $pdf->SetWidths(array(66));

    $pdf->SetXY(0, $pdf->GetY()+4);
    $pdf->Row2(array('__________________________________'), false, false);
    $pdf->SetXY(0, $pdf->GetY()-1);
    $pdf->Row2(array(strtoupper($data['info']->usuario)), false, false);


    // $yy2 = $pdf->GetY();
    // if(count($codigoAreas) > 0){
    //   $yy2 = $pdf->GetY();
    //   $pdf->SetXY(0, $pdf->GetY());
    //   $pdf->Row2(array('COD/AREA: ' . implode(' - ', $codigoAreas)), false, false);
    // }

    // if ($orden['info'][0]->trabajador != '') {
    //   $pdf->SetXY(0, $pdf->GetY()-2);
    //   $pdf->Row2(array('Se asigno a: '.strtoupper($orden['info'][0]->trabajador)), false, false);
    // }

    // $pdf->AutoPrint(true);
    $pdf->Output();
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */