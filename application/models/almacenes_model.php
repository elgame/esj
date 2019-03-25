<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class almacenes_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getAlmacenes($paginados = true)
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
			$sql = "WHERE ( lower(nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

		$_GET['fstatus'] = ($this->input->get('fstatus') !== false? $this->input->get('fstatus'): 't');
		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." status='".$this->input->get('fstatus')."'";

    $query['query'] = "
        SELECT id_almacen, nombre, status
        FROM compras_almacenes
        ".$sql."
        ORDER BY nombre ASC
        ";
    $query['total_rows'] = 0;
    if ($paginados)
		  $query = BDUtil::pagination($query['query'], $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'almacenes'      => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => (isset($params['result_items_per_page'])? $params['result_items_per_page']: 0),
				'result_page'    => (isset($params['result_page'])? $params['result_page']: 0)
		);
		if($res->num_rows() > 0){
			$response['almacenes'] = $res->result();
		}

		return $response;
	}

 	/**
 	 * Agrega un camion a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addAlmacen($data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
        'nombre'  => $this->input->post('nombre')
			);
		}

		$this->db->insert('compras_almacenes', $data);
		// $id_vehiculo = $this->db->insert_id('compras_vehiculos', 'id_vehiculo');

		return array('error' => FALSE);
	}

	/**
	 * Modificar la informacion de un proveedor
	 * @param  [type] $id_almacen [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateAlmacen($id_almacen, $data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
			 'nombre' => $this->input->post('nombre')
			);
		}

		$this->db->update('compras_almacenes', $data, array('id_almacen' => $id_almacen));

		return array('error' => FALSE);
	}

	/**
	 * Obtiene la informacion de un camion
	 * @param  boolean $id_camion [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getAlmacenInfo($id_almacen=FALSE, $basic_info=FALSE)
	{
		$id_almacen = ($id_almacen!==FALSE)? $id_almacen: $_GET['id'];

		$sql_res = $this->db->select("id_almacen, nombre, status, calle, no_exterior, no_interior, colonia,
                                  localidad, municipio, estado, pais, cp, telefono" )
												->from("compras_almacenes")
												->where("id_almacen", $id_almacen)
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
   * Reporte de existencias por costo
   * @return [type] [description]
   */
  public function getHistorialData()
  {
    $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha1']: $_GET['ffecha2'];

    if($this->input->get('id_almacen') != ''){
      $sql .= " AND cs.id_almacen = ".$this->input->get('id_almacen');
    }
    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    // if($this->input->get('did_empresa') != ''){
    //   $sql .= " AND p.id_empresa = '".$this->input->get('did_empresa')."'";
    // }

    $res = $this->db->query(
      "SELECT cs.id_salida, cs.folio AS folio_salida, Date(cs.fecha_creacion) AS fecha, co.folio AS folio_orden, e.nombre_fiscal,
          aas.nombre AS almacens, aao.nombre AS almaceno
        FROM compras_salidas cs
          INNER JOIN compras_transferencias ct ON cs.id_salida = ct.id_salida
          INNER JOIN compras_ordenes co ON co.id_orden = ct.id_orden
          INNER JOIN empresas e ON e.id_empresa = cs.id_empresa
          INNER JOIN compras_almacenes aas ON aas.id_almacen = cs.id_almacen
          INNER JOIN compras_almacenes aao ON aao.id_almacen = co.id_almacen
        WHERE cs.status = 's' AND ct.id_salida IS NOT NULL {$sql} AND
          Date(cs.fecha_creacion) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'");

    $response = array();
    if($res->num_rows() > 0)
    {
      $response = $res->result();
      foreach ($response as $key => $value)
      {
        $res_cosa = $this->db->query("SELECT csp.cantidad, csp.precio_unitario, p.nombre,
            (csp.cantidad*csp.precio_unitario) AS importe, pu.nombre AS unidad
          FROM compras_salidas_productos csp
            INNER JOIN productos p ON p.id_producto = csp.id_producto
            INNER JOIN productos_unidades pu ON pu.id_unidad = p.id_unidad
          WHERE csp.id_salida = 8614");
        $value->productos = $res_cosa->result();
        $res_cosa->free_result();

        if(count($value->productos) === 0)
          unset($response[$key]);
      }
    }

    return $response;
  }
  /**
   * Reporte existencias por costo pdf
   */
  public function getHistorialPdf()
  {
    $res = $this->getHistorialData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    $pdf->titulo2 = 'Historial de transferencias';
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'C', 'L', 'C', 'L');
    $widths = array(20, 60, 25, 37, 25, 37);
    $header = array('Fecha', 'Empresa', 'Folio', 'Almacen', 'Folio', 'Almacen');

    $familia = '';
    $totaltes = array('familia' => array(0,0,0,0), 'general' => array(0,0,0,0));
    $total_cargos = $total_abonos = $total_saldo = 0;
    foreach($res as $key => $item){
      $band_head = false;
      if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',9);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);

        $pdf->SetX(86);
        $pdf->SetAligns(array('C', 'C'));
        $pdf->SetWidths(array(62, 62));
        $pdf->Row(array('Salida', 'Orden'), true, true);

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true, true);
        $pdf->SetTextColor(0,0,0);
      }

      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row(array(MyString::fechaAT($item->fecha), $item->nombre_fiscal, $item->folio_salida,
                      $item->almacens, $item->folio_orden, $item->almaceno), false, false);

      $pdf->SetFont('Arial','',8);
      $pdf->SetTextColor(0,0,0);

      foreach ($item->productos as $key2 => $prod)
      {
        $datos = array($prod->nombre,
          $prod->unidad,
          $prod->cantidad,
          $prod->precio_unitario,
          $prod->importe,
          );

        $pdf->SetX(6);
        $pdf->SetAligns(array('L', 'L', 'L', 'R', 'R'));
        $pdf->SetWidths(array(65, 25, 30, 30, 35));
        $pdf->Row($datos, false);
      }
    }

    $pdf->Output('epc.pdf', 'I');
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */