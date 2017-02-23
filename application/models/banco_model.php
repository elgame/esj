<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class banco_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getBancos($paginados = true)
	{
		$sql = '';
		$query['total_rows'] = $params['result_items_per_page'] = $params['result_page'] = '';
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
			$sql = "WHERE ( lower(nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

		$_GET['fstatus'] = ($this->input->get('fstatus') !== false? $this->input->get('fstatus'): 'ac');
		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? ' WHERE ': ' AND ')." status = '".$this->input->get('fstatus')."'";
 		$query['query'] =
 						"SELECT id_banco, nombre, status
						FROM banco_bancos
						{$sql}
						ORDER BY nombre ASC";
		if($paginados)
			$query = BDUtil::pagination($query['query'], $params, true);

		$res = $this->db->query($query['query']);

		$response = array(
				'bancos'        => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0)
		{
			$response['bancos'] = $res->result();
		}

		return $response;
	}

	/**
	 * Obtiene la informacion de un banco
	 * @param  boolean $id_banco [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getBancoInfo($id_banco=FALSE, $basic_info=FALSE)
	{
		$id_banco = (isset($_GET['id']))? $_GET['id']: $id_banco;

		$sql_res = $this->db->select("id_banco, nombre, status" )
												->from("banco_bancos")
												->where("id_banco", $id_banco)
												->get();
		$data['info'] = array();

		if ($sql_res->num_rows() > 0)
			$data['info']	= $sql_res->row();
		$sql_res->free_result();

		if ($basic_info == False) {

		}

		return $data;
	}

	/**
	 * Obtiene el listado de bancos para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en las placas, modelo, marca
	 */
	public function getBancosAjax(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND ( lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' )";

		$res = $this->db->query(
				"SELECT id_banco, nombre, status
				FROM banco_bancos
				WHERE status = 'ac' ".$sql."
				ORDER BY nombre ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_banco,
						'label' => $itm->nombre,
						'value' => $itm->nombre,
						'item'  => $itm,
				);
			}
		}
		$res->free_result();

		return $response;
	}


  /**
   * Reporte acumulado de empresas
   * @return [type]
   */
  public function rAcumuladoEmpresaData()
  {
    $this->load->model('banco_cuentas_model');
    $response = array();
    $sql = '';

    $_GET['ffecha1'] = isset($_GET['ffecha1'])? $_GET['ffecha1']: date("Y-m").'-01';
    $_GET['ffecha2'] = isset($_GET['ffecha2'])? $_GET['ffecha2']: date("Y-m-d");
    $sql .= " AND Date(bm.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."'";

    if ($this->input->get('did_empresa') && count($this->input->get('did_empresa')) > 0) {

      foreach ($this->input->get('did_empresa') as $keye => $id_empresa) {
        $cuentas = $this->banco_cuentas_model->getCuentas(false, null, ['id_empresa' => $id_empresa]);
        echo "<pre>";
          var_dump($cuentas);
        echo "</pre>";exit;

        // // Se Obtiene el saldo anterior a fecha1
        // $data_anterior = $this->db->query(
        //     "SELECT deposito, retiro, (deposito - retiro) AS saldo
        //     FROM (
        //       SELECT
        //         (SELECT COALESCE(Sum(monto), 0) FROM banco_movimientos WHERE status = 't' AND tipo = 't' AND id_cuenta = {$this->input->get('id_cuenta')} AND Date(fecha) < '{$fecha1}' {$sqlsaldo}) AS deposito,

        //         (SELECT COALESCE(Sum(monto), 0) FROM banco_movimientos WHERE status = 't' AND tipo = 'f' AND id_cuenta = {$this->input->get('id_cuenta')} AND Date(fecha) < '{$fecha1}' {$sql_todos} {$sqlsaldo}) AS retiro
        //       ) AS m")->row();
      }
    }

    return $response;
  }

  public function getRAcumuladoEmpresaPdf()
  {
    // Obtiene los datos del reporte.
    $data = $this->rAcumuladoEmpresaData();
    echo "<pre>";
      var_dump($data);
    echo "</pre>";exit;

    $fecha = new DateTime($_GET['ffecha1']);
    $fecha2 = new DateTime($_GET['ffecha2']);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->titulo2 = "REPORTE BANCOS ACUMULADO POR EMPRESA";
    $pdf->titulo3 = ($_GET['ftipo']==='i'? 'INGRESOS': 'EGRESOS')." DEL {$fecha->format('d/m/Y')} al {$fecha2->format('d/m/Y')}\n";
    // $lote = isset($data['data'][count($data['data'])-1]->no_lote)? $data['data'][count($data['data'])-1]->no_lote: '1';
    // $pdf->titulo3 .= "Estado: 6 | Municipio: 9 | Semana {$fecha->format('W')} | NUMERADOR: 69{$fecha->format('Ww')}/1 Al ".$lote;

    $pdf->AliasNbPages();
    $pdf->AddPage();


    // Listado de Rendimientos
    $pdf->SetFont('helvetica','', 8);
    $pdf->SetY($pdf->GetY()+2);

    $aligns = array('L', 'L', 'L', 'R', 'R', 'L', 'L');
    $widths = array(18, 40, 15, 22, 22, 48, 40);
    $header = array('Fecha', 'Cuenta', 'Tipo', 'Ingreso', 'Retiro', 'Beneficiario', 'Descripcion');

    $total_importes_ingre = $total_importes_total_ingre = $total_importes_egre = $total_importes_total_egre = 0;

    foreach($data as $key => $movimiento)
    {
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetX(6);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(205));
      $pdf->Row(array(
        $movimiento[0]->nombre_fiscal
      ), false, false);

      $total_importes_ingre = $total_importes_egre = 0;
      foreach ($movimiento as $keym => $mov) {
        if($pdf->GetY() >= $pdf->limiteY || $keym==0) //salta de pagina si exede el max
        {
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetFont('helvetica','B',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(200,200,200);
          // $pdf->SetY($pdf->GetY()-2);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('helvetica','', 8);
        $pdf->SetTextColor(0,0,0);

        // $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row(array(
            $mov->fecha,
            $mov->cuenta,
            substr($mov->tipo, 0, 5),
            $mov->tipomov=='t'? String::formatoNumero($mov->monto, 2, '$', false): '',
            $mov->tipomov=='f'? String::formatoNumero($mov->monto, 2, '$', false): '',
            ($mov->status=='f'? 'Cancelado': substr($mov->a_nombre_de, 0, 33)),
            $mov->numero_ref.($mov->numero_ref!=''? ' | ': '').$mov->concepto,
          ), false);

        if ($mov->status == 't') {
          if ($mov->tipomov=='t') {
            $total_importes_ingre       += $mov->monto;
            $total_importes_total_ingre += $mov->monto;
          } else {
            $total_importes_egre       += $mov->monto;
            $total_importes_total_egre += $mov->monto;
          }
        }
      }

      //total
      $pdf->SetX(71);
      $pdf->SetAligns(array('R','R'));
      $pdf->SetWidths(array(30, 30));
      $pdf->Row(array(
        String::formatoNumero($total_importes_ingre, 2, '$', false),
        String::formatoNumero($total_importes_egre, 2, '$', false)
      ), false);
    }

    //total general
    $pdf->SetFont('helvetica','B',8);
    $pdf->SetTextColor(0 ,0 ,0 );
    $pdf->SetX(71);
    $pdf->SetAligns(array('R','R'));
    $pdf->SetWidths(array(30, 30));
    $pdf->Row(array(
      String::formatoNumero($total_importes_total_ingre, 2, '$', false),
      String::formatoNumero($total_importes_total_egre, 2, '$', false)
    ), false);


    $pdf->Output('reporte_banco.pdf', 'I');
  }

  public function getRAcumuladoEmpresaXls(){
    $data = $this->rie_data();

    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=reporte_banco.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $fecha = new DateTime($_GET['ffecha1']);
    $fecha2 = new DateTime($_GET['ffecha2']);

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "REPORTE BANCOS ACUMULADO POR EMPRESA";
    $titulo3 = ($_GET['ftipo']==='i'? 'INGRESOS': 'EGRESOS')." DEL {$fecha->format('d/m/Y')} al {$fecha2->format('d/m/Y')}";

    $html = '<table>
      <tbody>
        <tr>
          <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="6" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>';
    $total_importes_total = 0;
    foreach($data as $key => $movimiento)
    {
      $html .= '<tr>
          <td style="font-weight:bold;" colspan="6">'.$movimiento[0]->nombre_fiscal.'</td>
        </tr>';
      $total_importes = 0;
      foreach ($movimiento as $keym => $mov) {
        if($keym==0) //salta de pagina si exede el max
        {
          $html .= '<tr style="font-weight:bold">
            <td style="border:1px solid #000;background-color: #cccccc;">Fecha</td>
            <td style="border:1px solid #000;background-color: #cccccc;">Cuenta</td>
            <td style="border:1px solid #000;background-color: #cccccc;">Tipo</td>
            <td style="border:1px solid #000;background-color: #cccccc;">Importe</td>
            <td style="border:1px solid #000;background-color: #cccccc;">Beneficiario</td>
            <td style="border:1px solid #000;background-color: #cccccc;">Descripcion</td>
          </tr>';
        }
        $html .= '<tr>
          <td style="border:1px solid #000;">'.$mov->fecha.'</td>
          <td style="border:1px solid #000;">'.$mov->cuenta.'</td>
          <td style="border:1px solid #000;">'.substr($mov->tipo, 0, 5).'</td>
          <td style="border:1px solid #000;">'.$mov->monto.'</td>
          <td style="border:1px solid #000;">'.($mov->status=='f'? 'Cancelado': substr($mov->a_nombre_de, 0, 33)).'</td>
          <td style="border:1px solid #000;">'.$mov->numero_ref.($mov->numero_ref!=''? ' | ': '').$mov->concepto.'</td>
        </tr>';

        if ($mov->status == 't') {
          $total_importes       += $mov->monto;
          $total_importes_total += $mov->monto;
        }
      }

      //total
      $html .= '<tr>
        <td style="" colspan="2"></td>
        <td style="border:1px solid #000;" colspan="2">'.$total_importes.'</td>
        <td style="" colspan="2"></td>
      </tr>';

    }

    $html .= '<tr>
        <td style="" colspan="2"></td>
        <td style="border:1px solid #000;" colspan="2">'.$total_importes_total.'</td>
        <td style="" colspan="2"></td>
      </tr>';

    echo $html;
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */