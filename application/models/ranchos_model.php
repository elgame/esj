<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class ranchos_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
		$this->load->model('bitacora_model');
	}

	public function getRanchos($paginados = true)
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
			$sql = "WHERE ( lower(r.nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

		$_GET['fstatus'] = ($this->input->get('fstatus') !== false? $this->input->get('fstatus'): 't');
		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." r.status='".$this->input->get('fstatus')."'";

    if($this->input->get('did_empresa') != '')
      $sql .= ($sql==''? 'WHERE': ' AND').' e.id_empresa = ' . $this->input->get('did_empresa');

		if($this->input->get('did_area') != '')
			$sql .= ($sql==''? 'WHERE': ' AND').' a.id_area = ' . $this->input->get('did_area');

    $query['query'] =
    			"SELECT r.id_rancho, r.nombre, r.status, e.id_empresa, e.nombre_fiscal AS empresa,
            a.id_area, a.nombre AS area
					FROM otros.ranchos r
            INNER JOIN public.empresas e ON e.id_empresa = r.id_empresa
            INNER JOIN public.areas a ON a.id_area = r.id_area
					{$sql}
					ORDER BY r.nombre ASC
					";
    if($paginados) {
			$query = BDUtil::pagination($query['query'], $params, true);
    }
		$res = $this->db->query($query['query']);

		$response = array(
				'ranchos'    => array(),
				'total_rows'     => isset($query['total_rows'])? $query['total_rows']: 0,
				'items_per_page' => isset($params['result_items_per_page'])? $params['result_items_per_page']: 0,
				'result_page'    => isset($params['result_page'])? $params['result_page']: 0
		);
		if($res->num_rows() > 0) {
			$response['ranchos'] = $res->result();
		}

		return $response;
	}

 	/**
 	 * Agrega un proveedor a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addRancho($data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
        'id_empresa' => $this->input->post('did_empresa'),
        'id_area'    => $this->input->post('did_area'),
        'nombre'     => $this->input->post('nombre'),
      );
		}

		$this->db->insert('otros.ranchos', $data);
		$id_ranchos = $this->db->insert_id('otros.ranchos', 'id_rancho');

		// Bitacora
    $this->bitacora_model->_insert('otros.ranchos', $id_ranchos,
                                    array(':accion'    => 'el ranchos', ':seccion' => 'ranchos',
                                          ':folio'     => $data['nombre_fiscal'],
                                          ':id_empresa' => $data['id_empresa'],
                                          ':empresa'   => 'en '.$this->input->post('fempresa')));

		return array('error' => FALSE);
	}

	/**
	 * Modificar la informacion de un proveedor
	 * @param  [type] $id_productor [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateRancho($id_rancho, $data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
        'id_empresa' => $this->input->post('did_empresa'),
        'id_area'    => $this->input->post('did_area'),
        'nombre'     => $this->input->post('nombre'),
      );
			// Bitacora
	    $id_bitacora = $this->bitacora_model->_update('otros.ranchos', $id_rancho, $data,
	                              array(':accion'       => 'el rancho', ':seccion' => 'ranchos',
	                                    ':folio'        => $data['nombre_fiscal'],
	                                    ':id_empresa'   => $data['id_empresa'],
	                                    ':empresa'      => 'en '.$this->input->post('fempresa'),
	                                    ':id'           => 'id_rancho',
	                                    ':titulo'       => 'Rancho'));
		}else {
			if (isset($data['status']) && $data['status'] === 'e') {
				// Bitacora
				$rancho = $this->getRanchoInfo($id_rancho);
		    $this->bitacora_model->_cancel('otros.ranchos', $id_rancho,
		                                    array(':accion'     => 'el rancho', ':seccion' => 'ranchos',
		                                          ':folio'      => $rancho['info']->nombre,
		                                          ':id_empresa' => $rancho['info']->id_empresa,
		                                          ':empresa'    => 'de '.$rancho['info']->empresa->nombre_fiscal));
			}
		}

		$this->db->update('otros.ranchos', $data, array('id_rancho' => $id_rancho));

		return array('error' => FALSE);
	}


	/**
	 * Obtiene la informacion de un proveedor
	 * @param  boolean $id_rancho [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getRanchoInfo($id_rancho=FALSE, $basic_info=FALSE)
	{
		$id_rancho = $id_rancho? $id_rancho: (isset($_GET['id'])? $_GET['id']: 0);

		$sql_res = $this->db->select("id_rancho, id_empresa, id_area, nombre, status" )
												->from("otros.ranchos")
												->where("id_rancho", $id_rancho)
												->get();
		$data['info'] = array();

		if ($sql_res->num_rows() > 0)
			$data['info']	= $sql_res->row();
		$sql_res->free_result();

		$data['docus'] = array();
		if ($basic_info == False) {

			// Carga la info de la empresa.
      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($data['info']->id_empresa);
      $data['info']->empresa = $empresa['info'];

      // Carga la info de la area.
      $this->load->model('areas_model');
      $empresa = $this->areas_model->getAreaInfo($data['info']->id_area, true);
      $data['info']->area = $empresa['info'];
		}

		return $data;
	}

	/**
	 * Obtiene el listado de proveedores para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en el nombre
	 * @param type. tipo de proveedor que se quiere obtener (insumos, fruta)
	 */
	public function getProductorAjax($sqlX = null){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND lower(r.nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'";

		if ($this->input->get('did_empresa') !== false && $this->input->get('did_empresa') !== '')
			$sql .= " AND e.id_empresa in(".$this->input->get('did_empresa').")";

	    if ( ! is_null($sqlX))
	      $sql .= $sqlX;

		$res = $this->db->query(
      	"SELECT r.id_rancho, r.nombre, r.status, a.id_area, a.nombre AS area
        FROM otros.ranchos r
          INNER JOIN public.areas a ON a.id_area = r.id_area
  			WHERE r.status = 't'
        	{$sql}
  			ORDER BY r.nombre ASC
  			LIMIT 20"
    );

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_rancho,
						'label' => $itm->nombre,
						'value' => $itm->nombre,
						'item'  => $itm,
				);
			}
		}

		return $response;
	}

	public function catalogo_xls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=productores.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    // $this->load->model('areas_model');
    // $area = $this->areas_model->getAreaInfo($id_area, true);
    $producotres = $this->getProductores(false);

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "Catalogo de productores";
    $titulo3 = '';

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
          <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Nombre Fiscal</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Calle</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">No exterior</td>
          <td style="width:400px;border:1px solid #000;background-color: #cccccc;">No interior</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Colonia</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Localidad</td>
          <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Municipio</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Estado</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Pais</td>
          <td style="width:400px;border:1px solid #000;background-color: #cccccc;">CP</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Telefono</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Celular</td>
          <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Email</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Parcela</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Ejido parcela</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Tipo</td>
        </tr>';

    foreach ($producotres['productores'] as $key => $clasif)
    {
      $html .= '<tr>
          <td style="width:400px;border:1px solid #000;">'.utf8_decode($clasif->nombre_fiscal).'</td>
					<td style="width:400px;border:1px solid #000;">'.utf8_decode($clasif->calle).'</td>
					<td style="width:100px;border:1px solid #000;">'.$clasif->no_exterior.'</td>
					<td style="width:100px;border:1px solid #000;">'.$clasif->no_interior.'</td>
					<td style="width:150px;border:1px solid #000;">'.$clasif->colonia.'</td>
					<td style="width:100px;border:1px solid #000;">'.$clasif->localidad.'</td>
					<td style="width:400px;border:1px solid #000;">'.$clasif->municipio.'</td>
					<td style="width:150px;border:1px solid #000;">'.$clasif->estado.'</td>
					<td style="width:100px;border:1px solid #000;">'.$clasif->pais.'</td>
					<td style="width:400px;border:1px solid #000;">'.$clasif->cp.'</td>
					<td style="width:150px;border:1px solid #000;">'.$clasif->telefono.'</td>
					<td style="width:100px;border:1px solid #000;">'.$clasif->celular.'</td>
					<td style="width:400px;border:1px solid #000;">'.$clasif->email.'</td>
					<td style="width:100px;border:1px solid #000;">'.$clasif->parcela.'</td>
					<td style="width:150px;border:1px solid #000;">'.$clasif->ejido_parcela.'</td>
					<td style="width:100px;border:1px solid #000;">'.$clasif->tipo.'</td>
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