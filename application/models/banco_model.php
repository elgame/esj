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
    $sql1 = $sql2 = '';

    $_GET['ffecha1'] = isset($_GET['ffecha1'])? $_GET['ffecha1']: date("Y-m").'-01';
    $_GET['ffecha2'] = isset($_GET['ffecha2'])? $_GET['ffecha2']: date("Y-m-d");
    $fecha = strtotime($_GET['ffecha1']) > strtotime($_GET['ffecha2'])? $_GET['ffecha1']: $_GET['ffecha2'];
    $fecha1 = strtotime($_GET['ffecha1']) < strtotime($_GET['ffecha2'])? $_GET['ffecha1']: $_GET['ffecha2'];
    $con_mov = isset($_GET['dcon_mov']) && $_GET['dcon_mov'] == 'si'? true: false;
    $sin_mov = isset($_GET['dsin_mov']) && $_GET['dsin_mov'] == 'si'? true: false;
    if (isset($_GET['ids_proveedores']) && count($_GET['ids_proveedores']) > 0) {
      $sql1 = " AND id_proveedor in(".implode(',', $_GET['ids_proveedores']).")";
      $sql2 = " AND m.id_proveedor in(".implode(',', $_GET['ids_proveedores']).")";
    }

    if ($this->input->get('did_empresa') && count($this->input->get('did_empresa')) > 0) {
      foreach ($this->input->get('did_empresa') as $keye => $id_empresa) {
        $empresa = $this->db->query("SELECT id_empresa, nombre_fiscal FROM empresas WHERE id_empresa = {$id_empresa}")->row();

        $cuentas = $this->banco_cuentas_model->getCuentas(false, null, ['id_empresa' => $id_empresa, 'hasta' => $fecha, 'tipo' => $this->input->get('dtipo_cuenta')]);
        if (count($cuentas['cuentas']) > 0) {
          foreach ($cuentas['cuentas'] as $keyc => $cuenta) {
            // Se Obtiene el saldo anterior a fecha1
            $data_anterior = $this->db->query(
                "SELECT deposito, retiro, ret_transito, (deposito - (retiro + ret_transito)) AS saldo
                FROM (
                  SELECT
                    (SELECT COALESCE(Sum(monto), 0) FROM banco_movimientos WHERE status = 't' AND tipo = 't' AND id_cuenta = {$cuenta->id_cuenta} AND Date(fecha) < '{$fecha1}' {$sql1}) AS deposito,
                    (SELECT COALESCE(Sum(monto), 0) FROM banco_movimientos WHERE status = 't' AND tipo = 'f' AND id_cuenta = {$cuenta->id_cuenta} AND Date(fecha) < '{$fecha1}' {$sql1}) AS retiro,
                    (SELECT COALESCE(Sum(monto), 0) FROM banco_movimientos WHERE status = 't' AND tipo = 'f' AND entransito = 't' AND metodo_pago = 'cheque' AND id_cuenta = {$cuenta->id_cuenta} AND Date(fecha) < '{$fecha1}' {$sql1}) AS ret_transito
                  ) AS m")->row();
            $cuenta->saldo_ini = $data_anterior->saldo;

            //Saldo en el rango de fecha
            $res = $this->db->query("SELECT *
              FROM
              (
                (
                  SELECT
                    m.id_movimiento,
                    Date(m.fecha) AS fecha,
                    m.numero_ref,
                    m.concepto,
                    Coalesce(c.nombre_fiscal, p.nombre_fiscal, a_nombre_de, '') AS cli_pro,
                    Coalesce(c.id_cliente, p.id_proveedor, 0) AS id_cli_pro,
                    m.monto,
                    '' AS retiro,
                    '' AS deposito,
                    0 AS saldo,
                    m.tipo,
                    m.status,
                    (CASE WHEN (m.fecha_aplico IS NULL) THEN m.entransito ELSE (CASE WHEN Date(m.fecha_aplico) > Date(m.fecha) THEN 'true'::boolean ELSE 'false'::boolean END) END) AS entransito,
                    m.salvo_buen_cobro,
                    m.metodo_pago,
                    m.id_cuenta_proveedor,
                    m.desglosar_iva
                  FROM banco_movimientos AS m
                    LEFT JOIN clientes AS c ON c.id_cliente = m.id_cliente
                    LEFT JOIN proveedores AS p ON p.id_proveedor = m.id_proveedor
                  WHERE m.id_cuenta = {$cuenta->id_cuenta}
                    AND Date(m.fecha) BETWEEN '{$fecha1}' AND '{$fecha}'
                    AND m.status = 't' AND (m.tipo = 't' OR (m.tipo = 'f'))
                    {$sql2}
                  ORDER BY m.fecha ASC, m.id_movimiento ASC
                )
                UNION ALL
                (
                  SELECT
                    m.id_movimiento,
                    Date(m.fecha_aplico) AS fecha,
                    m.numero_ref,
                    m.concepto,
                    Coalesce(c.nombre_fiscal, p.nombre_fiscal, a_nombre_de, '') AS cli_pro,
                    Coalesce(c.id_cliente, p.id_proveedor, 0) AS id_cli_pro,
                    m.monto,
                    '' AS retiro,
                    '' AS deposito,
                    0 AS saldo,
                    m.tipo,
                    m.status,
                    'false'::boolean AS entransito,
                    m.salvo_buen_cobro,
                    m.metodo_pago,
                    m.id_cuenta_proveedor,
                    m.desglosar_iva
                  FROM banco_movimientos AS m
                    LEFT JOIN clientes AS c ON c.id_cliente = m.id_cliente
                    LEFT JOIN proveedores AS p ON p.id_proveedor = m.id_proveedor
                  WHERE m.id_cuenta = {$cuenta->id_cuenta}
                    AND Date(m.fecha_aplico) BETWEEN '{$fecha1}' AND '{$fecha}'
                    AND m.status = 't' AND (m.tipo = 't' OR (m.tipo = 'f'))
                    {$sql2}
                  ORDER BY m.fecha_aplico ASC, m.id_movimiento ASC
                )
              ) t
              ORDER BY fecha ASC, id_movimiento ASC");
            $cuenta->movimientos = $res->result();

            foreach ($cuenta->movimientos as $key => $mov) {
              if ($mov->entransito == 't' && $mov->metodo_pago == 'cheque') {
                $cuenta->saldo -= $mov->monto;
              }elseif ($mov->entransito == 'f' && $mov->metodo_pago == 'cheque') {
                $cuenta->saldo += $mov->monto;
              }
            }

            if ($con_mov) {
              if (count($cuenta->movimientos) === 0)
                unset($cuentas['cuentas'][$keyc]);
            } elseif ($sin_mov) {
              if (count($cuenta->movimientos) > 0)
                unset($cuentas['cuentas'][$keyc]);
            }
          }
        }

        $empresa->cuentas = $cuentas['cuentas'];
        $response[] = $empresa;
      }
    }

    return $response;
  }

  public function getRAcumuladoEmpresaPdf()
  {
    // Obtiene los datos del reporte.
    $data = $this->rAcumuladoEmpresaData();
    // echo "<pre>";
    //   var_dump($data);
    // echo "</pre>";exit;

    $fecha = new DateTime($_GET['ffecha1']);
    $fecha2 = new DateTime($_GET['ffecha2']);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->titulo2 = "REPORTE BANCOS ACUMULADO POR EMPRESA";
    $pdf->titulo3 = "DEL {$fecha->format('d/m/Y')} al {$fecha2->format('d/m/Y')}";
    $pdf->titulo3 .= isset($_GET['dtipo_cuenta']) && $_GET['dtipo_cuenta']!=''? " ({$_GET['dtipo_cuenta']})": '';
    // $lote = isset($data['data'][count($data['data'])-1]->no_lote)? $data['data'][count($data['data'])-1]->no_lote: '1';
    // $pdf->titulo3 .= "Estado: 6 | Municipio: 9 | Semana {$fecha->format('W')} | NUMERADOR: 69{$fecha->format('Ww')}/1 Al ".$lote;

    $pdf->AliasNbPages();
    $pdf->AddPage();


    // Listado de Rendimientos
    $pdf->SetFont('helvetica','', 8);
    $pdf->SetY($pdf->GetY()+2);

    $aligns = array('L', 'L', 'R', 'R', 'L', 'L');
    $widths = array(45, 18, 27, 27, 24, 63);
    $header = array('Beneficiario', 'Tipo', 'Ingreso', 'Retiro', 'No. Poliza', 'Concepto');

    $total_importes_ingre = $total_importes_total_ingre = $total_importes_egre = $total_importes_total_egre = 0;

    foreach($data as $key => $empresa)
    {
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetX(6);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(205));
      $pdf->Row(array(
        $empresa->nombre_fiscal
      ), false, false);

      $total_importes_trans = $total_importes_ingre = $total_importes_egre = 0;
      foreach ($empresa->cuentas as $keyc => $cuenta) {
        if($pdf->GetY() >= $pdf->limiteY) {
          $pdf->AddPage();
        }

        // cuenta
        $pdf->SetFont('helvetica','B',8);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(200,200,200);
        $pdf->SetX(6);
        $aligns[5] = 'R';
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row(array($cuenta->alias, 'Saldo:', MyString::formatoNumero($cuenta->saldo_ini, 2, '$', false), '',
                        'Saldo Final:', MyString::formatoNumero($cuenta->saldo, 2, '$', false)), true, true);
        $aligns[5] = 'L';

        if ($keyc == 0) {
          // header
          $pdf->SetFont('helvetica','',8);
          $pdf->SetTextColor(0,0,0);
          $pdf->SetFillColor(230,230,230);
          $pdf->SetX(6);
          $aligns[2] = 'L'; $aligns[3] = 'L';
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true, true);
          $aligns[2] = 'R'; $aligns[3] = 'R';
        }

        $pdf->SetFont('helvetica','', 7);
        $pdf->SetTextColor(0,0,0);

        foreach ($cuenta->movimientos as $keym => $mov) {
          if($pdf->GetY() >= $pdf->limiteY) {
            $pdf->AddPage();
          }

          if ($mov->entransito == 't' && $mov->metodo_pago == 'cheque') {
            $pdf->SetFont('helvetica','B', 7);
          } else
            $pdf->SetFont('helvetica','', 7);

          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row(array(
              $mov->cli_pro,
              ucfirst(substr($mov->metodo_pago, 0, 5)),
              $mov->tipo=='t'? MyString::formatoNumero($mov->monto, 2, '$', false): '',
              $mov->tipo=='f'? MyString::formatoNumero($mov->monto, 2, '$', false): '',
              $mov->numero_ref,
              $mov->concepto,
            ), false, 'B');

          if ($mov->entransito == 't' && $mov->metodo_pago == 'cheque')
            $total_importes_trans += $mov->monto;
          else {
            if ($mov->tipo == 't')
              $total_importes_ingre += $mov->monto;
            else
              $total_importes_egre += $mov->monto;
          }
        }
      }

      $total_importes_total_ingre += $total_importes_ingre;
      $total_importes_total_egre += $total_importes_egre;

      //total
      $pdf->SetFont('helvetica','B',8);
      $pdf->SetX(51);
      $pdf->SetAligns(array('L', 'R','R'));
      $pdf->SetWidths(array(18, 27, 27));
      $pdf->Row(array('Suma:',
        MyString::formatoNumero($total_importes_ingre, 2, '$', false),
        MyString::formatoNumero($total_importes_egre, 2, '$', false)
      ), false);
    }

    //total general
    $pdf->SetFont('helvetica','B',8);
    $pdf->SetTextColor(0 ,0 ,0 );
    $pdf->SetX(51);
    $pdf->SetAligns(array('L', 'R','R'));
    $pdf->SetWidths(array(18, 27, 27));
    $pdf->Row(array('Total:',
      MyString::formatoNumero($total_importes_total_ingre, 2, '$', false),
      MyString::formatoNumero($total_importes_total_egre, 2, '$', false)
    ), false, 'B');


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

  /**
   * Reporte saldos bancarios
   * @return [type]
   */
  public function rptSaldosBancariosData()
  {
    $this->load->model('banco_cuentas_model');
    $response = array();
    $sql1 = $sql2 = '';

    $_GET['ffecha1'] = isset($_GET['ffecha1'])? $_GET['ffecha1']: date("Y-m").'-01';
    $_GET['ffecha2'] = isset($_GET['ffecha2'])? $_GET['ffecha2']: date("Y-m-d");
    $fecha = strtotime($_GET['ffecha1']) > strtotime($_GET['ffecha2'])? $_GET['ffecha1']: $_GET['ffecha2'];
    $fecha1 = strtotime($_GET['ffecha1']) < strtotime($_GET['ffecha2'])? $_GET['ffecha1']: $_GET['ffecha2'];
    $con_mov = isset($_GET['dcon_mov']) && $_GET['dcon_mov'] == 'si'? true: false;
    $sin_mov = isset($_GET['dsin_mov']) && $_GET['dsin_mov'] == 'si'? true: false;
    if (isset($_GET['ids_proveedores']) && count($_GET['ids_proveedores']) > 0) {
      $sql1 = " AND id_proveedor in(".implode(',', $_GET['ids_proveedores']).")";
      $sql2 = " AND m.id_proveedor in(".implode(',', $_GET['ids_proveedores']).")";
    }

    if ($this->input->get('did_empresa') && count($this->input->get('did_empresa')) > 0) {
      foreach ($this->input->get('did_empresa') as $keye => $id_empresa) {
        $empresa = $this->db->query("SELECT id_empresa, nombre_fiscal FROM empresas WHERE id_empresa = {$id_empresa}")->row();

        $cuentas = $this->banco_cuentas_model->getCuentas(false, null, ['id_empresa' => $id_empresa, 'hasta' => $fecha, 'tipo' => $this->input->get('dtipo_cuenta')]);
        if (count($cuentas['cuentas']) > 0) {
          foreach ($cuentas['cuentas'] as $keyc => $cuenta) {
            // Se Obtiene el saldo anterior a fecha1
            $data_anterior = $this->db->query(
                "SELECT deposito, retiro, (deposito - retiro) AS saldo_ini, transito
                FROM (
                  SELECT
                    (SELECT COALESCE(Sum(monto), 0) FROM banco_movimientos WHERE status = 't' AND tipo = 't' AND id_cuenta = {$cuenta->id_cuenta} AND Date(fecha) < '{$fecha1}' {$sql1}) AS deposito,
                    (SELECT COALESCE(Sum(monto), 0) FROM banco_movimientos WHERE status = 't' AND tipo = 'f' AND id_cuenta = {$cuenta->id_cuenta} AND Date(fecha) < '{$fecha1}' {$sql1}) AS retiro,
                    (SELECT COALESCE(Sum(monto), 0) FROM banco_movimientos WHERE status = 't' AND tipo = 'f' AND entransito = 't'
                      AND (metodo_pago = 'cheque' OR metodo_pago = 'transferencia') AND id_cuenta = {$cuenta->id_cuenta} AND Date(fecha) BETWEEN '{$fecha1}' AND '{$fecha}' {$sql1}
                    ) AS transito
                  ) AS m")->row();
            $cuenta->saldo_ini = $data_anterior->saldo_ini;
            $cuenta->transito = $data_anterior->transito;

            if ($con_mov) {
              if ($cuenta->saldo_ini == $cuenta->saldo)
                unset($cuentas['cuentas'][$keyc]);
            } elseif ($sin_mov) {
              if ($cuenta->saldo_ini != $cuenta->saldo)
                unset($cuentas['cuentas'][$keyc]);
            }
          }
        }

        $empresa->cuentas = $cuentas['cuentas'];
        $response[] = $empresa;
      }
    }

    return $response;
  }

  public function getRptSaldosBancariosPdf()
  {
    // Obtiene los datos del reporte.
    $data = $this->rptSaldosBancariosData();

    // echo "<pre>";
    //   var_dump($data);
    // echo "</pre>";exit;

    $fecha = new DateTime($_GET['ffecha1']);
    $fecha2 = new DateTime($_GET['ffecha2']);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->titulo2 = "REPORTE DE SALDOS BANCARIOS";
    $pdf->titulo3 = "DEL {$fecha->format('d/m/Y')} al {$fecha2->format('d/m/Y')}";
    $pdf->titulo3 .= isset($_GET['dtipo_cuenta']) && $_GET['dtipo_cuenta']!=''? " ({$_GET['dtipo_cuenta']})": '';
    // $lote = isset($data['data'][count($data['data'])-1]->no_lote)? $data['data'][count($data['data'])-1]->no_lote: '1';
    // $pdf->titulo3 .= "Estado: 6 | Municipio: 9 | Semana {$fecha->format('W')} | NUMERADOR: 69{$fecha->format('Ww')}/1 Al ".$lote;

    $pdf->AliasNbPages();
    $pdf->AddPage();


    // Listado de Rendimientos
    $pdf->SetY($pdf->GetY()+2);

    $aligns = array('L', 'L', 'R', 'R', 'R');
    $widths = array(57, 56, 27, 27, 27);
    $header = array('EMPRESA', 'CUENTA', 'SALDO INICIAL', 'CH-TRANSITO', 'SALDO FINAL');

    $total_importes_ingre = $total_importes_total_ingre = $total_importes_egre = $total_importes_total_egre = 0;

    // header
    $pdf->SetFont('helvetica','B',8.5);
    $pdf->SetX(6);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->Row($header, false, 'B');

    foreach($data as $key => $empresa)
    {
      // $pdf->SetFont('helvetica','B',8);
      // $pdf->SetX(6);
      // $pdf->SetAligns(array('L'));
      // $pdf->SetWidths(array(205));
      // $pdf->Row(array(
      //   $empresa->nombre_fiscal
      // ), false, false);

      $total_importes_ingre = $total_importes_egre = 0;
      foreach ($empresa->cuentas as $keyc => $cuenta) {
        if($pdf->GetY() >= $pdf->limiteY) {
          $pdf->AddPage();
        }

        $nombree = '';
        if($keyc == 0) {
          $nombree = $empresa->nombre_fiscal;
        }

        // cuenta
        $pdf->SetFont('helvetica','',8);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row(array($nombree, $cuenta->alias, MyString::formatoNumero($cuenta->saldo_ini, 2, '$', false),
                        MyString::formatoNumero($cuenta->transito, 2, '$', false),
                        MyString::formatoNumero($cuenta->saldo, 2, '$', false)), false, false);
      }
    }

    $pdf->Output('saldos_bancarios.pdf', 'I');
  }

  public function getRptSaldosBancariosXls(){
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

  public function getRptMovSinUuidPdf()
  {
    // Obtiene los datos del reporte.
    $this->load->model('polizas_model');
    $this->polizas_model->empresaId = $this->input->get('fid_empresa');
    if($this->input->get('ftipo3') == 'el')  //Egreso de limon
    {
      $data = $this->polizas_model->polizaEgresoLimon();
    }elseif($this->input->get('ftipo3') == 'ec') //Egreso de cheque
    {
      $data = $this->polizas_model->polizaEgreso();
    }else //egreso de gasto
    {
      $data = $this->polizas_model->polizaEgreso('otros');
    }

    // echo "<pre>";
    //   var_dump($data['abonos']);
    // echo "</pre>";exit;

    $tipos = [
      'el' => 'Limon',
      'ec' => 'Cheques',
      'eg' => 'Gastos'
    ];
    $fecha = new DateTime($_GET['ffecha1']);
    $fecha2 = new DateTime($_GET['ffecha2']);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->titulo2 = "REPORTE DE MOVIMIENTOS UUID";
    $pdf->titulo3 = "DEL {$fecha->format('d/m/Y')} al {$fecha2->format('d/m/Y')}";
    $pdf->titulo3 .= $tipos[$_GET['ftipo3']];

    $pdf->AliasNbPages();
    $pdf->AddPage();

    // Listado de Rendimientos
    $pdf->SetY($pdf->GetY()+2);

    $aligns = array('L', 'L', 'L', 'L', 'R', 'C');
    $widths = array(17, 25, 65, 60, 20, 18);
    $header = array('FECHA', 'REF MOV', 'CONCEPTO', 'PROVEEDOR', 'MONTO', 'UUID');

    // header
    $pdf->SetFont('helvetica','B',8.5);
    $pdf->SetX(6);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->Row($header, false, 'B');

    foreach($data['abonos'] as $key => $mov)
    {
      $pdf->SetFont('helvetica','', 8);
      $pdf->SetX(6);
      $pdf->Row([
        $mov->fecha,
        $mov->ref_movimiento,
        $mov->concepto,
        $mov->nombre_fiscal,
        MyString::formatoNumero($mov->total_abono, 2, '$', false),
        (trim($mov->uuid)!=''? 'Si': 'No')
      ], false, true);
    }

    $pdf->Output('movimeintos_uuid.pdf', 'I');
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */