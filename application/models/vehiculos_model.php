<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class vehiculos_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getVehiculos($paginados = true)
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
			$sql = "WHERE ( lower(p.placa) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.modelo) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.marca) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

		$_GET['fstatus'] = ($this->input->get('fstatus') !== false? $this->input->get('fstatus'): 't');
		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." p.status='".$this->input->get('fstatus')."'";

		$query = BDUtil::pagination("
				SELECT p.id_vehiculo, p.placa, p.modelo, p.marca, p.status, p.unidad
				FROM compras_vehiculos p
				".$sql."
				ORDER BY p.placa ASC
				", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'vehiculos'       => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0){
			$response['vehiculos'] = $res->result();
		}

		return $response;
	}

 	/**
 	 * Agrega un camion a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addVehiculo($data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
          'placa'  => $this->input->post('fplacas'),
          'modelo' => $this->input->post('fmodelo'),
          'marca'  => $this->input->post('fmarca'),
          'color'  => $this->input->post('fcolor'),
          'unidad' => $this->input->post('funidad'),
				);
		}

		$this->db->insert('compras_vehiculos', $data);
		// $id_vehiculo = $this->db->insert_id('compras_vehiculos', 'id_vehiculo');

		return array('error' => FALSE);
	}

	/**
	 * Modificar la informacion de un proveedor
	 * @param  [type] $id_vehiculo [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateVehiculo($id_vehiculo, $data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
        'placa'  => $this->input->post('fplacas'),
        'modelo' => $this->input->post('fmodelo'),
        'marca'  => $this->input->post('fmarca'),
        'color'  => $this->input->post('fcolor'),
        'unidad' => $this->input->post('funidad'),
      );
		}

		$this->db->update('compras_vehiculos', $data, array('id_vehiculo' => $id_vehiculo));

		return array('error' => FALSE);
	}

	/**
	 * Obtiene la informacion de un camion
	 * @param  boolean $id_camion [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getVehiculoInfo($id_vehiculo=FALSE, $basic_info=FALSE)
	{
		$id_vehiculo = ($id_vehiculo!==FALSE)? $id_vehiculo: $_GET['id'];

		$sql_res = $this->db->select("id_vehiculo, placa, modelo, marca, status, color, unidad, (placa || ' ' || modelo || ' ' || marca) AS nombre" )
												->from("compras_vehiculos")
												->where("id_vehiculo", $id_vehiculo)
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
  public function getRCombustibleGeneralData()
  {
    $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha1']: $_GET['ffecha2'];
    $sqlf1 = " AND Date(c.fecha_aceptacion) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'";
    $sqlf2 = " AND Date(c.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'";

    if($this->input->get('fid_vehiculo') == '') $_GET['fid_vehiculo'] = 0;
    if ($_GET['fid_vehiculo'] > 0)
      $sql .= " AND id_vehiculo = ".$this->input->get('fid_vehiculo');

    // $this->load->model('empresas_model');
    // $client_default = $this->empresas_model->getDefaultEmpresa();
    // $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    // $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
      // if($this->input->get('did_empresa') != ''){
      //   $sql .= " AND p.id_empresa = '".$this->input->get('did_empresa')."'";
      // }

    $vehiculos = $this->db->query("SELECT * FROM compras_vehiculos WHERE status = 't' {$sql} ORDER BY (placa || ' ' || modelo || ' ' || marca) ASC")->result();

    $res_vehiculo = array();
    foreach ($vehiculos as $key => $vehiculo) {
      //Gasolina
      $res = $this->db->query(
        "SELECT * FROM (
        (
          SELECT 1 AS orden, cv.id_vehiculo, (placa || ' ' || modelo || ' ' || marca) AS nombre, cvg.kilometros, cvg.litros, cvg.precio, Date(c.fecha_creacion) AS fecha,
            (cvg.litros * cvg.precio) AS total, c.id_empresa, c.folio, 'Gasolina' AS tipo, cv.unidad
          FROM compras_ordenes AS c
            INNER JOIN compras_vehiculos_gasolina AS cvg ON c.id_orden = cvg.id_orden
            INNER JOIN compras_vehiculos AS cv ON cv.id_vehiculo = c.id_vehiculo
          WHERE c.status<>'ca' AND c.tipo_vehiculo='g' AND cv.id_vehiculo = {$vehiculo->id_vehiculo} {$sqlf1}
          ORDER BY c.fecha_creacion ASC
          LIMIT 1
        )
        UNION
        (
          SELECT 2 AS orden, cv.id_vehiculo, (placa || ' ' || modelo || ' ' || marca) AS nombre, cvg.kilometros, cvg.litros, cvg.precio, Date(c.fecha_creacion) AS fecha,
            (cvg.litros * cvg.precio) AS total, c.id_empresa, c.folio, 'Gasolina' AS tipo, cv.unidad
          FROM compras_ordenes AS c
            INNER JOIN compras_vehiculos_gasolina AS cvg ON c.id_orden = cvg.id_orden
            INNER JOIN compras_vehiculos AS cv ON cv.id_vehiculo = c.id_vehiculo
          WHERE c.status<>'ca' AND c.tipo_vehiculo='g' AND cv.id_vehiculo = {$vehiculo->id_vehiculo} {$sqlf1}
          ORDER BY c.fecha_creacion DESC
          LIMIT 1
        )
        UNION
        (
          SELECT 3 AS orden, cv.id_vehiculo, ''::text AS nombre, 0 AS kilometros, Sum(cvg.litros) AS litros, 0 AS precio, null AS fecha,
            0 AS total, 0 AS id_empresa, 0 AS folio, 'Gasolina' AS tipo, cv.unidad
          FROM compras_ordenes AS c
            INNER JOIN compras_vehiculos_gasolina AS cvg ON c.id_orden = cvg.id_orden
            INNER JOIN compras_vehiculos AS cv ON cv.id_vehiculo = c.id_vehiculo
          WHERE c.status<>'ca' AND c.tipo_vehiculo='g' AND cv.id_vehiculo = {$vehiculo->id_vehiculo} {$sqlf1}
          GROUP BY cv.id_vehiculo
        )
      ) f
      ORDER BY orden ASC
      ")->result();
      if (count($res) == 3) {
        $res[2]->nombre     = $res[0]->nombre;
        $res[2]->kilometros = ($res[1]->kilometros-$res[0]->kilometros) * ($res[0]->unidad === 'km'? 1: 1.609);
        $res[2]->litros     = ($res[2]->litros-$res[0]->litros);
        $res[2]->km_litro   = $res[2]->kilometros/($res[2]->litros>0 ? $res[2]->litros : 1);
        $res[2]->id_empresa = $res[0]->id_empresa;
        $res_vehiculo[] = $res[2];
      }

      //Disel
      $res = $this->db->query(
        "SELECT *
        FROM (
          (
            SELECT 1 AS orden, cv.id_vehiculo, (placa || ' ' || modelo || ' ' || marca) AS nombre, cvg.kilometros, cvg.litros, cvg.precio, Date(c.fecha_creacion) AS fecha,
              (cvg.litros * cvg.precio) AS total, c.id_empresa, c.folio, 'Diesel' AS tipo, cv.unidad
            FROM compras_ordenes AS c
              INNER JOIN compras_vehiculos_gasolina AS cvg ON c.id_orden = cvg.id_orden
              INNER JOIN compras_vehiculos AS cv ON cv.id_vehiculo = c.id_vehiculo
            WHERE c.status<>'ca' AND c.tipo_vehiculo='d' AND cv.id_vehiculo = {$vehiculo->id_vehiculo} {$sqlf1}
            ORDER BY c.fecha_creacion ASC
            LIMIT 1
          )
          UNION
          (
            SELECT 2 AS orden, cv.id_vehiculo, (placa || ' ' || modelo || ' ' || marca) AS nombre, cvg.kilometros, cvg.litros, cvg.precio, Date(c.fecha_creacion) AS fecha,
              (cvg.litros * cvg.precio) AS total, c.id_empresa, c.folio, 'Diesel' AS tipo, cv.unidad
            FROM compras_ordenes AS c
              INNER JOIN compras_vehiculos_gasolina AS cvg ON c.id_orden = cvg.id_orden
              INNER JOIN compras_vehiculos AS cv ON cv.id_vehiculo = c.id_vehiculo
            WHERE c.status<>'ca' AND c.tipo_vehiculo='d' AND cv.id_vehiculo = {$vehiculo->id_vehiculo} {$sqlf1}
            ORDER BY c.fecha_creacion DESC
            LIMIT 1
          )
          UNION
          (
            SELECT 3 AS orden, cv.id_vehiculo, ''::text AS nombre, 0 AS kilometros, Sum(cvg.litros) AS litros, 0 AS precio, null AS fecha,
              0 AS total, 0 AS id_empresa, 0 AS folio, 'Diesel' AS tipo, cv.unidad
            FROM compras_ordenes AS c
              INNER JOIN compras_vehiculos_gasolina AS cvg ON c.id_orden = cvg.id_orden
              INNER JOIN compras_vehiculos AS cv ON cv.id_vehiculo = c.id_vehiculo
            WHERE c.status<>'ca' AND c.tipo_vehiculo='d' AND cv.id_vehiculo = {$vehiculo->id_vehiculo} {$sqlf1}
            GROUP BY cv.id_vehiculo
          )
        ) f
        ORDER BY orden ASC
        ")->result();
      if (count($res) == 3) {
        $res[2]->nombre     = $res[0]->nombre;
        $res[2]->kilometros = ($res[1]->kilometros-$res[0]->kilometros) * ($res[0]->unidad === 'km'? 1: 1.609);
        $res[2]->litros     = $res[2]->litros-$res[0]->litros;
        $res[2]->km_litro   = $res[2]->kilometros/($res[2]->litros>0 ? $res[2]->litros : 1);
        $res[2]->id_empresa = $res[0]->id_empresa;
        $res_vehiculo[] = $res[2];
      }
    }

    // echo "<pre>";
    //   var_dump($res_vehiculo);
    // echo "</pre>";exit;

    return $res_vehiculo;
  }
  /**
   * Reporte rendimiento de combustible general pdf
   */
  public function getRCombustibleGeneralPdf()
  {
    $this->load->model('empresas_model');
    $vehiculos = $this->getRCombustibleGeneralData();

    $id_empresa = isset($vehiculos->id_empresa)?$vehiculos->id_empresa:0;
    $empresa22 = $this->empresas_model->getInfoEmpresa($id_empresa);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    if(isset($empresa22['info']->nombre_fiscal)){
      $pdf->titulo1 = $empresa22['info']->nombre_fiscal;
      $pdf->logo = $empresa22['info']->logo;
    }
    $pdf->titulo2 = 'Reporte de Vehiculo';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'));
    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'R', 'R', 'R', 'R');
    $widths = array(76, 15, 41, 35, 35);
    $header = array('Vehiculo', 'Tipo', 'Km/Recorridos', 'Litros', 'Km/L');

    foreach($vehiculos as $key => $item){
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
      $datos = array($item->nombre,
        $item->tipo,
        MyString::formatoNumero($item->kilometros, 2, ''),
        MyString::formatoNumero($item->litros, 2, ''),
        MyString::formatoNumero($item->km_litro, 2, '')
        );

      $pdf->SetX(6);
      $_GET['fid_vehiculo'] = $item->id_vehiculo;
      $pdf->SetMyLinks(array(base_url('panel/vehiculos/combustible_pdf/?'.MyString::getVarsLink(array('msg'))), '', '', '', ''));
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false);
    }

    $pdf->Output('vehiculo.pdf', 'I');
  }

  public function getRCombustibleGasDieData($sql, $sqlf1, $tipo='g', $order='ASC')
  {
    $res = $this->db->query(
      "SELECT cv.id_vehiculo, (placa || ' ' || modelo || ' ' || marca) AS nombre,
        cvg.kilometros, cvg.litros, cvg.precio, Date(c.fecha_creacion) AS fecha,
        (cvg.litros * cvg.precio) AS total1, c.id_empresa, c.folio, cv.unidad,
        (SELECT Sum(importe) FROM compras_productos WHERE id_orden = c.id_orden) AS total
      FROM compras_ordenes AS c
        INNER JOIN compras_vehiculos_gasolina AS cvg ON c.id_orden = cvg.id_orden
        INNER JOIN compras_vehiculos AS cv ON cv.id_vehiculo = c.id_vehiculo
      WHERE c.status<>'ca' AND c.tipo_vehiculo='{$tipo}' {$sql} {$sqlf1}
      ORDER BY c.fecha_creacion {$order}
      ");
    $response = [];
    if($res->num_rows() > 0)
    {
      $response = $res->result();
    }
    $res->free_result();

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
		$sqlf1 = " AND Date(c.fecha_creacion) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'";
		$sqlf2 = " AND Date(c.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'";

		if($this->input->get('fid_vehiculo') == '') $_GET['fid_vehiculo'] = 0;
		$sql .= " AND cv.id_vehiculo = ".$this->input->get('fid_vehiculo');

		// $this->load->model('empresas_model');
		// $client_default = $this->empresas_model->getDefaultEmpresa();
		// $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
		// $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
	    // if($this->input->get('did_empresa') != ''){
	    //   $sql .= " AND p.id_empresa = '".$this->input->get('did_empresa')."'";
	    // }

    $response = array('gasolina' => array(), 'disel' => array(), 'gastos' => array());

    //Gasolina
    $response['gasolina'] = $this->getRCombustibleGasDieData($sql, $sqlf1, 'g');
    $sqlf11 = " AND Date(c.fecha_creacion) < '{$_GET['ffecha1']}'";
    $gasolina = $this->getRCombustibleGasDieData($sql, $sqlf11, 'g', 'DESC LIMIT 1');
    if (count($gasolina) > 0) {
      array_unshift($response['gasolina'], $gasolina[0]);
    }

		//Disel
    $response['disel'] = $this->getRCombustibleGasDieData($sql, $sqlf1, 'd');
    $sqlf11 = " AND Date(c.fecha_creacion) < '{$_GET['ffecha1']}'";
    $disel = $this->getRCombustibleGasDieData($sql, $sqlf11, 'd', 'DESC LIMIT 1');
    if (count($disel) > 0) {
      array_unshift($response['disel'], $disel[0]);
    }

		//Gastos
		$res = $this->db->query(
			"SELECT cc.id_orden, cc.folio, Date(cc.fecha) AS fecha, cc.nombre, cc.total, cc.concepto, cc.id_empresa
			FROM (
				(
					SELECT c.id_orden, (c.folio) AS folio, c.fecha_aceptacion AS fecha, (cv.placa || ' ' || cv.modelo || ' ' || cv.marca) AS nombre,
						(SELECT Sum(importe) FROM compras_productos WHERE id_orden = c.id_orden) AS total,
						array_to_string(Array(SELECT descripcion FROM compras_productos WHERE id_orden = c.id_orden), ', ') AS concepto, c.id_empresa
					FROM compras_ordenes AS c
						INNER JOIN compras_vehiculos AS cv ON cv.id_vehiculo = c.id_vehiculo
					WHERE c.status<>'ca' AND c.tipo_vehiculo='ot' {$sql} {$sqlf1}
				)
				UNION
				(
					SELECT c.id_compra AS id_orden, c.folio, c.fecha, (cv.placa || ' ' || cv.modelo || ' ' || cv.marca) AS nombre,
						c.subtotal AS total, c.concepto, c.id_empresa
					FROM compras AS c
						INNER JOIN compras_vehiculos AS cv ON cv.id_vehiculo = c.id_vehiculo
					WHERE c.status<>'ca' AND c.tipo_vehiculo='ot' {$sql} {$sqlf2}
				)
			) AS cc
			ORDER BY cc.fecha ASC
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
    $this->load->model('empresas_model');
		$res = $this->getRCombustibleData();

    $id_empresa = isset($res['gasolina'][0]->id_empresa)?$res['gasolina'][0]->id_empresa:0;
    $id_empresa = isset($res['disel'][0]->id_empresa)?$res['disel'][0]->id_empresa: ($id_empresa>0?$id_empresa:0);
    $id_empresa = isset($res['gastos'][0]->id_empresa)?$res['gastos'][0]->id_empresa: ($id_empresa>0?$id_empresa:0);
    $empresa22 = $this->empresas_model->getInfoEmpresa($id_empresa);

		$this->load->library('mypdf');
		// Creación del objeto de la clase heredada
		$pdf = new MYpdf('P', 'mm', 'Letter');
    if(isset($empresa22['info']->nombre_fiscal)){
      $pdf->titulo1 = $empresa22['info']->nombre_fiscal;
      $pdf->logo = $empresa22['info']->logo;
    }
    $pdf->excel = base_url('panel/vehiculos/combustible_xls/?'.MyString::getVarsLink(array('msg')));
		$pdf->titulo2 = 'Reporte de Vehiculo';
		$pdf->titulo3 = (isset($res['gasolina'][0]->nombre)? $res['gasolina'][0]->nombre: '')."\n";
		$pdf->titulo3 .= 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'));
		$pdf->AliasNbPages();
		//$pdf->AddPage();
		$pdf->SetFont('Arial','',8);

		$aligns = array('C', 'L', 'R', 'R', 'R', 'R', 'R', 'R');
		$widths = array(18, 20, 36, 25, 20, 25, 25, 33);
		$header = array('Fecha', 'Folio Ordn', 'Kilometros', 'Km/Recorridos', 'Litros', 'Km/L', 'L/100Km', 'Importe');

		$total_gasolina = $total_kilometros = $total_litros = $totalRecorridos = 0;
		if(count($res['gasolina']) > 0)
		{
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
				$datos = array(MyString::fechaAT($item->fecha),
					$item->folio,
					MyString::formatoNumero($this->millasToKm($item->kilometros, $item->unidad), 2, ''),
          '',
					MyString::formatoNumero($item->litros, 2, ''),
					// MyString::formatoNumero($precio, 2, ''),
					'', '',
					MyString::formatoNumero($item->total, 2, '$', false),
				);
				if ($key > 0)
				{
					$rendimiento = ($this->millasToKm($item->kilometros, $item->unidad) - $this->millasToKm($res['gasolina'][$key-1]->kilometros, $res['gasolina'][$key-1]->unidad))/($item->litros>0? $item->litros: 1);
          $datos[3] = MyString::formatoNumero($this->millasToKm($item->kilometros, $item->unidad) - $this->millasToKm($res['gasolina'][$key-1]->kilometros, $res['gasolina'][$key-1]->unidad), 2, '');

					$datos[5] = MyString::formatoNumero( $rendimiento , 2, '');
					$datos[6] = MyString::formatoNumero( (100/($rendimiento == 0 ? 1 : $rendimiento)) , 2, '');

					$total_kilometros += $this->millasToKm($item->kilometros, $item->unidad) - $this->millasToKm($res['gasolina'][$key-1]->kilometros, $res['gasolina'][$key-1]->unidad);
					$total_litros     += $item->litros;
          $totalRecorridos += $this->millasToKm($item->kilometros, $item->unidad) - $this->millasToKm($res['gasolina'][$key-1]->kilometros, $res['gasolina'][$key-1]->unidad);
				}

				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
        if ($item->fecha >= $this->input->get('ffecha1')) {
				  $pdf->Row($datos, false);

          $total_gasolina += $item->total;
        }
			}

			$pdf->SetFont('Arial','B',8);
			$pdf->SetTextColor(255,255,255);
			$pdf->SetX(6);
			$pdf->SetAligns($aligns);
			$pdf->SetWidths($widths);
			$total_rendimiento = ($total_kilometros/($total_litros>0? $total_litros: 1));
			$pdf->Row(array('', '',
						'', // MyString::formatoNumero( $total_kilometros , 2, ''),
            MyString::formatoNumero( $totalRecorridos, 2, ''),
						MyString::formatoNumero( $total_litros , 2, ''),
						MyString::formatoNumero( $total_rendimiento , 2, ''),
						MyString::formatoNumero( (100/($total_rendimiento>0? $total_rendimiento: 1)) , 2, ''),
						MyString::formatoNumero($total_gasolina, 2, '$', false),
					), true);
		}

		$total_disel = $total_kilometros = $total_litros = $totalRecorridos = 0;
		if(count($res['disel']) > 0)
		{
			foreach($res['disel'] as $key => $item){
				$band_head = false;
				if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
					if($pdf->GetY() >= $pdf->limiteY)
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
				$datos = array(MyString::fechaAT($item->fecha),
          $item->folio,
          MyString::formatoNumero($this->millasToKm($item->kilometros, $item->unidad), 2, ''),
          '',
          MyString::formatoNumero($item->litros, 2, ''),
          // MyString::formatoNumero($precio, 2, ''),
          '', '',
          MyString::formatoNumero($item->total, 2, '$', false),
        );
				if ($key > 0)
				{
          $rendimiento = ($this->millasToKm($item->kilometros, $item->unidad) - $this->millasToKm($res['disel'][$key-1]->kilometros, $res['disel'][$key-1]->unidad))/($item->litros>0? $item->litros: 1);
					$rendimiento = $rendimiento==0? 1 : $rendimiento;
          $datos[3] = MyString::formatoNumero($this->millasToKm($item->kilometros, $item->unidad) - $this->millasToKm($res['disel'][$key-1]->kilometros, $res['disel'][$key-1]->unidad), 2, '');

					$datos[5] = MyString::formatoNumero( $rendimiento , 2, '');
					$datos[6] = MyString::formatoNumero( (100/$rendimiento) , 2, '');

					$total_kilometros += $this->millasToKm($item->kilometros, $item->unidad) - $this->millasToKm($res['disel'][$key-1]->kilometros, $res['disel'][$key-1]->unidad);
					$total_litros     += $item->litros;
          $totalRecorridos += $this->millasToKm($item->kilometros, $item->unidad) - $this->millasToKm($res['disel'][$key-1]->kilometros, $res['disel'][$key-1]->unidad);
				}

				$pdf->SetX(6);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
        if ($item->fecha >= $this->input->get('ffecha1')) {
          $pdf->Row($datos, false);

          $total_disel += $item->total;
        }
			}

			$pdf->SetFont('Arial','B',8);
			$pdf->SetTextColor(255,255,255);
			$pdf->SetX(6);
			$pdf->SetAligns($aligns);
			$pdf->SetWidths($widths);
			$total_rendimiento = ($total_kilometros/($total_litros>0? $total_litros: 1));
			$pdf->Row(array('', '',
						'', //MyString::formatoNumero( $total_kilometros , 2, ''),
            MyString::formatoNumero( $totalRecorridos, 2, ''),
						MyString::formatoNumero( $total_litros , 2, ''),
						MyString::formatoNumero( $total_rendimiento , 2, ''),
						MyString::formatoNumero( (100/($total_rendimiento>0? $total_rendimiento: 1)) , 2, ''),
						MyString::formatoNumero($total_disel, 2, '$', false),
					), true);
		}

    if($pdf->GetY() == NULL)
      $pdf->AddPage();
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
			$datos = array(MyString::fechaAT($item->fecha),
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
		$pdf->SetWidths(array(20, 30, 20, 30, 20, 30, 20, 30));
		$pdf->Row(array('Gasolina', MyString::formatoNumero($total_gasolina, 2, '$', false),
						'Disel', MyString::formatoNumero($total_disel, 2, '$', false),
						'Otros', MyString::formatoNumero($total_gasto, 2, '$', false),
						'Total', MyString::formatoNumero($total_gasolina+$total_disel+$total_gasto, 2, '$', false)
						), true);

		$pdf->Output('vehiculo.pdf', 'I');
	}

  public function getRCombustibleXls()
  {
    $this->load->model('empresas_model');
    $res = $this->getRCombustibleData();
    // echo "<pre>";
    // var_dump($res);
    // echo "</pre>";exit;

    $id_empresa = isset($res['gasolina'][0]->id_empresa)?$res['gasolina'][0]->id_empresa:0;
    $id_empresa = isset($res['disel'][0]->id_empresa)?$res['disel'][0]->id_empresa: ($id_empresa>0?$id_empresa:0);
    $id_empresa = isset($res['gastos'][0]->id_empresa)?$res['gastos'][0]->id_empresa: ($id_empresa>0?$id_empresa:0);
    $empresa22 = $this->empresas_model->getInfoEmpresa($id_empresa);

    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=reporte_vehiculo.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $titulo1 = $empresa22['info']->nombre_fiscal;
    $titulo2 = 'Reporte de Vehículo';
    $titulo3 = (isset($res['gasolina'][0]->nombre)? $res['gasolina'][0]->nombre: '')."\n";
    $titulo3 .= 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'));

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
        </tr>';


    $total_gasolina = $total_kilometros = $total_litros = $totalRecorridos = 0;
    if(count($res['gasolina']) > 0)
    {
      $html .= '<tr style="font-weight:bold">
          <td colspan="8" style="border:1px solid #000;background-color: #cccccc;">Bitácora de Rendimiento de Combustible</td>
        </tr>
        <tr style="font-weight:bold">
          <td style="border:1px solid #000;background-color: #cccccc;">Fecha</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Folio Ordn</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Kilometros</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Km/Recorridos</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Litros</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Km/L</td>
          <td style="border:1px solid #000;background-color: #cccccc;">L/100Km</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Importe</td>
        </tr>';

      foreach($res['gasolina'] as $key => $item){
        $precio = $item->total / ($item->litros>0? $item->litros: 1);
        $datos = array(MyString::fechaAT($item->fecha),
          $item->folio,
          MyString::formatoNumero($this->millasToKm($item->kilometros, $item->unidad), 2, ''),
          '',
          MyString::formatoNumero($item->litros, 2, ''),
          // MyString::formatoNumero($precio, 2, ''),
          '', '',
          MyString::formatoNumero($item->total, 2, '$', false),
        );
        if ($key > 0)
        {
          $rendimiento = ($this->millasToKm($item->kilometros, $item->unidad) - $this->millasToKm($res['gasolina'][$key-1]->kilometros, $res['gasolina'][$key-1]->unidad))/($item->litros>0? $item->litros: 1);
          $datos[3] = MyString::formatoNumero($this->millasToKm($item->kilometros, $item->unidad) - $this->millasToKm($res['gasolina'][$key-1]->kilometros, $res['gasolina'][$key-1]->unidad), 2, '');

          $datos[5] = MyString::formatoNumero( $rendimiento , 2, '');
          $datos[6] = MyString::formatoNumero( (100/($rendimiento == 0 ? 1 : $rendimiento)) , 2, '');

          $total_kilometros += $this->millasToKm($item->kilometros, $item->unidad) - $this->millasToKm($res['gasolina'][$key-1]->kilometros, $res['gasolina'][$key-1]->unidad);
          $total_litros     += $item->litros;
          $totalRecorridos += $this->millasToKm($item->kilometros, $item->unidad) - $this->millasToKm($res['gasolina'][$key-1]->kilometros, $res['gasolina'][$key-1]->unidad);
        }

        if ($item->fecha >= $this->input->get('ffecha1')) {
          $html .= '<tr style="">
            <td style="border:1px solid #000;">'.$datos[0].'</td>
            <td style="border:1px solid #000;">'.$datos[1].'</td>
            <td style="border:1px solid #000;">'.$datos[2].'</td>
            <td style="border:1px solid #000;">'.$datos[3].'</td>
            <td style="border:1px solid #000;">'.$datos[4].'</td>
            <td style="border:1px solid #000;">'.$datos[5].'</td>
            <td style="border:1px solid #000;">'.$datos[6].'</td>
            <td style="border:1px solid #000;">'.$datos[7].'</td>
          </tr>';

          $total_gasolina += $item->total;
        }
      }

      $total_rendimiento = ($total_kilometros/($total_litros>0? $total_litros: 1));
      $html .= '<tr style="font-weight:bold">
          <td style="border:1px solid #000;background-color: #cccccc;"></td>
          <td style="border:1px solid #000;background-color: #cccccc;"></td>
          <td style="border:1px solid #000;background-color: #cccccc;"></td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.$totalRecorridos.'</td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.$total_litros.'</td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.$total_rendimiento.'</td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.(100/($total_rendimiento>0? $total_rendimiento: 1)).'</td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.$total_gasolina.'</td>
        </tr>
        <tr style="">
          <td colspan="8"></td>
        </tr>';
    }

    $total_disel = $total_kilometros = $total_litros = $totalRecorridos = 0;
    if(count($res['disel']) > 0)
    {
      $html .= '<tr style="font-weight:bold">
          <td colspan="8" style="border:1px solid #000;background-color: #cccccc;">Bitácora de Rendimiento de Combustible</td>
        </tr>
        <tr style="font-weight:bold">
          <td style="border:1px solid #000;background-color: #cccccc;">Fecha</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Folio Ordn</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Kilometros</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Km/Recorridos</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Litros</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Km/L</td>
          <td style="border:1px solid #000;background-color: #cccccc;">L/100Km</td>
          <td style="border:1px solid #000;background-color: #cccccc;">Importe</td>
        </tr>';
      foreach($res['disel'] as $key => $item){
        $precio = $item->total / ($item->litros>0? $item->litros: 1);
        $datos = array(MyString::fechaAT($item->fecha),
          $item->folio,
          MyString::formatoNumero($this->millasToKm($item->kilometros, $item->unidad), 2, ''),
          '',
          MyString::formatoNumero($item->litros, 2, ''),
          // MyString::formatoNumero($precio, 2, ''),
          '', '',
          MyString::formatoNumero($item->total, 2, '$', false),
        );
        if ($key > 0)
        {
          $rendimiento = ($this->millasToKm($item->kilometros, $item->unidad) - $this->millasToKm($res['disel'][$key-1]->kilometros, $res['disel'][$key-1]->unidad))/($item->litros>0? $item->litros: 1);
          $rendimiento = $rendimiento==0? 1 : $rendimiento;
          $datos[3] = MyString::formatoNumero($this->millasToKm($item->kilometros, $item->unidad) - $this->millasToKm($res['disel'][$key-1]->kilometros, $res['disel'][$key-1]->unidad), 2, '');

          $datos[5] = MyString::formatoNumero( $rendimiento , 2, '');
          $datos[6] = MyString::formatoNumero( (100/$rendimiento) , 2, '');

          $total_kilometros += $this->millasToKm($item->kilometros, $item->unidad) - $this->millasToKm($res['disel'][$key-1]->kilometros, $res['disel'][$key-1]->unidad);
          $total_litros     += $item->litros;
          $totalRecorridos += $this->millasToKm($item->kilometros, $item->unidad) - $this->millasToKm($res['disel'][$key-1]->kilometros, $res['disel'][$key-1]->unidad);
        }

        if ($item->fecha >= $this->input->get('ffecha1')) {
          $html .= '<tr style="">
            <td style="border:1px solid #000;">'.$datos[0].'</td>
            <td style="border:1px solid #000;">'.$datos[1].'</td>
            <td style="border:1px solid #000;">'.$datos[2].'</td>
            <td style="border:1px solid #000;">'.$datos[3].'</td>
            <td style="border:1px solid #000;">'.$datos[4].'</td>
            <td style="border:1px solid #000;">'.$datos[5].'</td>
            <td style="border:1px solid #000;">'.$datos[6].'</td>
            <td style="border:1px solid #000;">'.$datos[7].'</td>
          </tr>';

          $total_disel += $item->total;
        }
      }

      $total_rendimiento = ($total_kilometros/($total_litros>0? $total_litros: 1));
      $html .= '<tr style="font-weight:bold">
          <td style="border:1px solid #000;background-color: #cccccc;"></td>
          <td style="border:1px solid #000;background-color: #cccccc;"></td>
          <td style="border:1px solid #000;background-color: #cccccc;"></td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.$totalRecorridos.'</td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.$total_litros.'</td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.$total_rendimiento.'</td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.(100/($total_rendimiento>0? $total_rendimiento: 1)).'</td>
          <td style="border:1px solid #000;background-color: #cccccc;">'.$total_disel.'</td>
        </tr>
        <tr style="">
          <td colspan="8"></td>
        </tr>';
    }

    $html .= '<tr style="font-weight:bold">
        <td colspan="8" style="border:1px solid #000;background-color: #cccccc;">Otros Gastos</td>
      </tr>
      <tr style="font-weight:bold">
        <td style="border:1px solid #000;background-color: #cccccc;">Fecha</td>
        <td style="border:1px solid #000;background-color: #cccccc;">Vehiculo</td>
        <td style="border:1px solid #000;background-color: #cccccc;">Folio</td>
        <td style="border:1px solid #000;background-color: #cccccc;">Concepto</td>
        <td style="border:1px solid #000;background-color: #cccccc;">Importe</td>
      </tr>';

    $total_gasto = 0;
    foreach($res['gastos'] as $key => $item){
      $datos = array(MyString::fechaAT($item->fecha),
        $item->nombre,
        $item->folio,
        $item->concepto,
        MyString::formatoNumero($item->total, 2, '$', false),
        );
      $total_gasto += $item->total;

      $html .= '<tr style="">
          <td style="border:1px solid #000;">'.$datos[0].'</td>
          <td style="border:1px solid #000;">'.$datos[1].'</td>
          <td style="border:1px solid #000;">'.$datos[2].'</td>
          <td style="border:1px solid #000;">'.$datos[3].'</td>
          <td style="border:1px solid #000;">'.$datos[4].'</td>
        </tr>';
    }

    $html .= '<tr style="font-weight:bold">
        <td style="border:1px solid #000;background-color: #cccccc;"></td>
        <td style="border:1px solid #000;background-color: #cccccc;"></td>
        <td style="border:1px solid #000;background-color: #cccccc;"></td>
        <td style="border:1px solid #000;background-color: #cccccc;"></td>
        <td style="border:1px solid #000;background-color: #cccccc;">'.$total_gasto.'</td>
      </tr>
      <tr style="font-weight:bold">
        <td colspan="5"></td>
      </tr>
      <tr style="font-weight:bold">
        <td style="border:1px solid #000;background-color: #cccccc;">Gasolina</td>
        <td style="border:1px solid #000;background-color: #cccccc;">Disel</td>
        <td style="border:1px solid #000;background-color: #cccccc;">Otros</td>
        <td style="border:1px solid #000;background-color: #cccccc;">Total</td>
      </tr>
      <tr style="font-weight:bold">
        <td style="border:1px solid #000;background-color: #cccccc;">'.$total_gasolina.'</td>
        <td style="border:1px solid #000;background-color: #cccccc;">'.$total_disel.'</td>
        <td style="border:1px solid #000;background-color: #cccccc;">'.$total_gasto.'</td>
        <td style="border:1px solid #000;background-color: #cccccc;">'.($total_gasolina+$total_disel+$total_gasto).'</td>
      </tr>
    </tbody>
    </table>';

    echo $html;
  }

  public function millasToKm($kilometros, $unidad)
  {
    $millas = $kilometros * ($unidad === 'km'? 1: 1.609);
    return $millas;
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */