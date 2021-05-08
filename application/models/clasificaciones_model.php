<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class clasificaciones_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
	}

	public function getClasificaciones($id_area=null, $paginados = true)
	{
		$sql = '';
		//paginacion
		if($paginados)
		{
			$this->load->library('pagination');
			$params = array(
					'result_items_per_page' => '50',
					'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
			);
			if($params['result_page'] % $params['result_items_per_page'] == 0)
				$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);
		}
		//Filtros para buscar
		if($this->input->get('fnombre') != '')
			$sql = "WHERE ( lower(nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

		$_GET['fstatus'] = $this->input->get('fstatus')!==false? $this->input->get('fstatus'): 't';
		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." status='".$this->input->get('fstatus')."'";

		if($id_area!=null)
			$sql .= ($sql==''? 'WHERE': ' AND')." id_area = '".$id_area."'";

		$str_query = "
				SELECT id_clasificacion, id_area, nombre, precio_venta, cuenta_cpi, status
				FROM clasificaciones
				".$sql."
				ORDER BY nombre ASC
				";
		if($paginados){
			$query = BDUtil::pagination($str_query, $params, true);
			$res = $this->db->query($query['query']);
		}else
			$res = $this->db->query($str_query);

		$response = array(
				'clasificaciones'=> array(),
				'total_rows'     => (isset($query['total_rows'])? $query['total_rows']: ''),
				'items_per_page' => (isset($params['result_items_per_page'])? $params['result_items_per_page']: ''),
				'result_page'    => (isset($params['result_page'])? $params['result_page']: '')
		);
		if($res->num_rows() > 0){
			$response['clasificaciones'] = $res->result();
		}

		return $response;
	}

 	/**
 	 * Agrega un area mas calidades y clasificaciones a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addClasificacion($data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
            'id_area'         => $this->input->post('farea'),
            'nombre'          => $this->input->post('fnombre'),
            'iva'             => $this->input->post('diva'),
            'id_unidad'       => $this->input->post('dunidad'),
            // 'precio_venta' => $this->input->post('fprecio_venta'),
            'cuenta_cpi'      => $this->input->post('fcuenta_cpi'),
            'cuenta_cpi2'     => count($this->input->post('fcuentas'))>0? json_encode($this->input->post('fcuentas')): [],
            'codigo'          => $this->input->post('fcodigo'),
            'inventario'      => $this->input->post('dinventario')=='t'? 't': 'f',
            'clave_prod_serv' => $this->input->post('dclave_producto_cod'),
            // 'clave_unidad'    => $this->input->post('dclave_unidad_cod'),
						);
		}

		$this->db->insert('clasificaciones', $data);

		$id_clasificacion = $this->db->insert_id('clasificaciones_id_clasificacion_seq');

    // if (isset($_POST['fcalibres']))
    // {
    //   $calibres = array();

    //   foreach ($_POST['fcalibres'] as $idCalibre)
    //   {
    //     $calibres[] = array('id_clasificacion' => $id_clasificacion, 'id_calibre' => $idCalibre);
    //   }

    //   $this->db->insert_batch('clasificaciones_calibres', $calibres);
    // }

		return array('error' => FALSE, $id_clasificacion);
	}

	/**
	 * Modificar la informacion de una clasificacion
	 * @param  [type] $id_clasificacion [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateClasificacion($id_clasificacion, $data=NULL)
	{
		if ($data==NULL)
		{
			$data = array(
            'nombre'       => $this->input->post('fnombre'),
            'precio_venta' => $this->input->post('fprecio_venta'),
            'cuenta_cpi'   => $this->input->post('fcuenta_cpi'),
            'id_area'      => $this->input->post('farea'),
            'iva'          => $this->input->post('diva'),
            'id_unidad'    => $this->input->post('dunidad'),
            'cuenta_cpi2'  => count($this->input->post('fcuentas'))>0? json_encode($this->input->post('fcuentas')): [],
            'codigo'       => $this->input->post('fcodigo'),
            'inventario'   => $this->input->post('dinventario')=='t'? 't': 'f',
            'clave_prod_serv' => $this->input->post('dclave_producto_cod'),
            // 'clave_unidad'    => $this->input->post('dclave_unidad_cod'),
						);

      // $this->db->delete('clasificaciones_calibres', array('id_clasificacion' => $id_clasificacion));

      // if (isset($_POST['fcalibres']))
      // {
      //   $calibres = array();

      //   foreach ($_POST['fcalibres'] as $idCalibre)
      //   {
      //     $calibres[] = array('id_clasificacion' => $id_clasificacion, 'id_calibre' => $idCalibre);
      //   }

      //   $this->db->insert_batch('clasificaciones_calibres', $calibres);
      // }
		}

		$this->db->update('clasificaciones', $data, array('id_clasificacion' => $id_clasificacion));

		return array('error' => FALSE);
	}

	/**
	 * Obtiene la informacion de un clasificacion
	 * @param  boolean $id_clasificacion [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getClasificacionInfo($id_clasificacion=FALSE, $basic_info=FALSE, $firstP = true)
	{
    if ($firstP)
      $id_clasificacion = (isset($_GET['id']))? $_GET['id']: $id_clasificacion;

		$sql_res = $this->db->select("id_clasificacion, id_area, nombre, precio_venta, cuenta_cpi, status, iva, id_unidad,
                                  cuenta_cpi2, codigo, inventario, clave_prod_serv, clave_unidad" )
												->from("clasificaciones")
												->where("id_clasificacion", $id_clasificacion)
												->get();

		$data['info'] = array();

		if ($sql_res->num_rows() > 0)
			$data['info']	= $sql_res->row();

    $sql_res->free_result();

		if ($basic_info == false)
    {
      $this->load->model('catalogos33_model');
      $data['cprodserv'] = $this->catalogos33_model->claveProdServ($data['info']->clave_prod_serv);
      $data['cunidad'] = $this->catalogos33_model->claveUnidad($data['info']->clave_unidad);

      $sql_res = $this->db->query(
        "SELECT cal.id_calibre, cal.nombre
         FROM calibres as cal
         INNER JOIN clasificaciones_calibres as clas_cal ON clas_cal.id_calibre = cal.id_calibre
         WHERE clas_cal.id_clasificacion = {$id_clasificacion}");

      $data['calibres'] = array();

      if ($sql_res->num_rows() > 0)
      {
        $data['calibres'] = $sql_res->result();
      }
		}

		return $data;
	}

	/**
	 * Obtiene el listado de clasificaciones para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en el nombre
	 * @param type. clasificaciones de una area
	 */
	public function ajaxClasificaciones($limit=20){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'";
		if($this->input->get('type') !== false)
			$sql .= " AND id_area = {$this->input->get('type')}";
    if($this->input->get('inventario') !== false)
      $sql .= " AND inventario = 't'";
		$res = $this->db->query(" SELECT id_clasificacion, id_area, nombre, status, iva, id_unidad, unidad_cantidad
				FROM clasificaciones
				WHERE status = true {$sql}
				ORDER BY nombre ASC
				LIMIT {$limit}");

    $con_inventario = false;
    if ($this->input->get('inventario') !== false) {
      $con_inventario = true;
    }

    $this->load->model('produccion_model');
		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
        if ($con_inventario) {
          $itm->inventario = $this->produccion_model->getInventarioData($itm->id_clasificacion)[0];
          // echo "<pre>";
          // var_dump($this->produccion_model->getInventarioData($itm->id_clasificacion));
          // echo "</pre>";exit;
        }
				$response[] = array(
						'id'    => $itm->id_clasificacion,
						'label' => $itm->nombre,
						'value' => $itm->nombre,
						'item'  => $itm,
				);
			}
		}

		return $response;
	}

  public function clasificaciones_xls($id_area)
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=productos.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $this->load->model('areas_model');
    $area = $this->areas_model->getAreaInfo($id_area, true);
    $clasificaciones = $this->getClasificaciones($id_area, false);

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "Catalogo de productos";
    $titulo3 = $area['info']->nombre;

    $html = '<table>
      <tbody>
        <tr>
          <td colspan="3" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="3" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="3" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="3"></td>
        </tr>
        <tr style="font-weight:bold">
          <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Nombre</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Precio</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Cta Contpaq</td>
        </tr>';

    foreach ($clasificaciones['clasificaciones'] as $key => $clasif)
    {
      $html .= '<tr>
          <td style="width:100px;border:1px solid #000;">'.$clasif->nombre.'</td>
          <td style="width:150px;border:1px solid #000;">'.$clasif->precio_venta.'</td>
          <td style="width:400px;border:1px solid #000;">'.$clasif->cuenta_cpi.'</td>
        </tr>';
    }

    $html .= '
      </tbody>
    </table>';

    echo $html;
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */