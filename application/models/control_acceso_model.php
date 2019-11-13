<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class control_acceso_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
		$this->load->model('bitacora_model');
	}

	public function getControl($paginados = true)
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
			$sql = " AND ( lower(p.nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.asunto) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.departamento) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.placas) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

    if($this->input->get('ffil_fecha') != '' && $this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '')
      $sql = " AND Date(p.{$this->input->get('ffil_fecha')}) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffil_fecha') != '' && $this->input->get('ffecha1') != '')
      $sql = " AND Date(p.{$this->input->get('ffil_fecha')}) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffil_fecha') != '' && $this->input->get('ffecha2') != '')
      $sql = " AND Date(p.{$this->input->get('ffil_fecha')}) = '".$this->input->get('ffecha2')."'";

    // if($this->input->get('did_empresa') != '')
    //   $sql .= ($sql==''? 'WHERE': ' AND').' p.id_empresa = ' . $this->input->get('did_empresa');

		// if($this->input->get('ftipo_proveedor') != '' && $this->input->get('ftipo_proveedor') != 'todos')
		// 	$sql .= ($sql==''? 'WHERE': ' AND')." p.tipo_proveedor='".$this->input->get('ftipo_proveedor')."'";

    $query['query'] =
    			"SELECT p.id_control, p.id_usaurio_ent, p.id_usuario_sal,
            (ue.nombre || ' ' || ue.apellido_paterno || ' ' || ue.apellido_materno) AS usuario_entrada,
            (us.nombre || ' ' || us.apellido_paterno || ' ' || us.apellido_materno) AS usuario_salida,
            v.folio AS folio_vale, p.nombre, p.asunto, p.departamento, p.placas, p.fecha_entrada, p.fecha_salida
					FROM otros.control_acceso p
            INNER JOIN usuarios ue ON ue.id = p.id_usaurio_ent
            LEFT JOIN usuarios us ON us.id = p.id_usuario_sal
            LEFT JOIN otros.vales_salida v ON v.id_vale_salida = p.id_vale_salida
					WHERE 1 = 1 {$sql}
					ORDER BY p.id_control ASC
					";
    if($paginados) {
			$query = BDUtil::pagination($query['query'], $params, true);
    }
		$res = $this->db->query($query['query']);

		$response = array(
				'control_acceso'    => array(),
				'total_rows'     => isset($query['total_rows'])? $query['total_rows']: 0,
				'items_per_page' => isset($params['result_items_per_page'])? $params['result_items_per_page']: 0,
				'result_page'    => isset($params['result_page'])? $params['result_page']: 0
		);
		if($res->num_rows() > 0){
			$response['control_acceso'] = $res->result();
		}

		return $response;
	}

 	/**
 	 * Agrega un proveedor a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addEntrada($data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
            'id_usaurio_ent' => $this->input->post('id_usaurio_ent'),
            'nombre'         => $this->input->post('nombre'),
            'asunto'         => $this->input->post('asunto'),
            'departamento'   => $this->input->post('departamento'),
            'placas'         => $this->input->post('placas'),
						);
		}

		$this->db->insert('otros.control_acceso', $data);

		return array('error' => FALSE);
	}

  public function addSalida($id_control, $data=NULL)
  {

    if ($data==NULL)
    {
      $data = array(
            'id_usuario_sal' => $this->input->post('id_usuario_sal'),
            'id_vale_salida' => ($this->input->post('id_vale_salida')!=''? $this->input->post('id_vale_salida'): null),
            'fecha_salida'   => date("Y-m-d H:i:s"),
            );
    }
    $this->db->update('otros.control_acceso', $data, "id_control = {$id_control}");

    return array('error' => FALSE);
  }

	/**
	 * Modificar la informacion de un proveedor
	 * @param  [type] $id_cliente [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateRegistro($id_control, $data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
            'id_usaurio_ent' => $this->input->post('id_usaurio_ent'),
            'id_usuario_sal' => $this->input->post('id_usuario_sal'),
            'id_vale_salida' => $this->input->post('id_vale_salida'),
            'nombre'         => $this->input->post('nombre'),
            'asunto'         => $this->input->post('asunto'),
            'departamento'   => $this->input->post('departamento'),
            'placas'         => $this->input->post('placas'),
						);
		}

		$this->db->update('otros.control_acceso', $data, array('id_control' => $id_control));

		return array('error' => FALSE);
	}

	/**
	 * Obtiene la informacion de un proveedor
	 * @param  boolean $id_cliente [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getControlInfo($id_control=FALSE, $placas=FALSE)
	{
    // $id_cliente = (isset($_GET['id']))? $_GET['id']: $id_cliente;
    $sql = $id_control? " AND p.id_control = {$id_control}": '';
		$sql .= $placas? " AND UPPER(p.placas) = UPPER('{$placas}') AND p.fecha_salida IS NULL": '';

		$result = $this->db->query("SELECT p.id_control, p.id_usaurio_ent, p.id_usuario_sal, p.id_vale_salida,
            (ue.nombre || ' ' || ue.apellido_paterno || ' ' || ue.apellido_materno) AS usuario_entrada,
            (us.nombre || ' ' || us.apellido_paterno || ' ' || us.apellido_materno) AS usuario_salida,
            v.folio AS folio_vale, p.nombre, p.asunto, p.departamento, p.placas, p.fecha_entrada, p.fecha_salida
          FROM otros.control_acceso p
            INNER JOIN usuarios ue ON ue.id = p.id_usaurio_ent
            LEFT JOIN usuarios us ON us.id = p.id_usuario_sal
            LEFT JOIN otros.vales_salida v ON v.id_vale_salida = p.id_vale_salida
          WHERE 1 = 1 {$sql}
          ORDER BY p.id_control DESC
          ");

		$data = $result->row();

		return $data;
	}

	/**
	 * Obtiene el listado de proveedores para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en el nombre
	 * @param type. tipo de proveedor que se quiere obtener (insumos, fruta)
	 */
	public function getClientesAjax($sqlX = null){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND lower(c.nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'";

		if ($this->input->get('did_empresa') !== false && $this->input->get('did_empresa') !== '')
			$sql .= " AND e.id_empresa in(".$this->input->get('did_empresa').")";

	    if ( ! is_null($sqlX))
	      $sql .= $sqlX;

		$res = $this->db->query(
      	"SELECT c.id_cliente, c.nombre_fiscal, c.rfc, c.calle, c.no_exterior, c.no_interior, c.colonia, c.municipio, c.estado, c.cp,
          c.telefono, c.dias_credito, c.metodo_pago, c.ultimos_digitos, c.id_empresa, e.nombre_fiscal AS empresa
  			FROM clientes c INNER JOIN empresas e ON e.id_empresa = c.id_empresa
  			WHERE c.status = 'ac'
        	{$sql}
  			ORDER BY c.nombre_fiscal ASC
  			LIMIT 20"
    );

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
        $dato_ext = $itm->municipio==''? ($itm->estado==''? '': ' - '.$itm->estado): ' - '.$itm->municipio;
        $dato_ext .= $this->input->get('empresa')=='si'? ' - '.substr($itm->empresa, 0, 5): '';
				$response[] = array(
						'id'    => $itm->id_cliente,
						'label' => $itm->nombre_fiscal.$dato_ext,
						'value' => $itm->nombre_fiscal.$dato_ext,
						'item'  => $itm,
				);
			}
		}

		return $response;
	}

	public function catalogo_xls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=clientes.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    // $this->load->model('areas_model');
    // $area = $this->areas_model->getAreaInfo($id_area, true);
    $clasificaciones = $this->getClientes(false);

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "Catalogo de clientes";
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
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Pag Web</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Cta Contpaq</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">RFC</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">CURP</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Dias de Credito</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Metodo de Pago</td>
        </tr>';

    foreach ($clasificaciones['clientes'] as $key => $clasif)
    {
      $html .= '<tr>
          <td style="width:400px;border:1px solid #000;">'.$clasif->nombre_fiscal.'</td>
					<td style="width:400px;border:1px solid #000;">'.$clasif->calle.'</td>
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
					<td style="width:150px;border:1px solid #000;">'.$clasif->pag_web.'</td>
					<td style="width:100px;border:1px solid #000;">'.$clasif->cuenta_cpi.'</td>
					<td style="width:100px;border:1px solid #000;">'.$clasif->rfc.'</td>
					<td style="width:150px;border:1px solid #000;">'.$clasif->curp.'</td>
					<td style="width:100px;border:1px solid #000;">'.$clasif->dias_credito.'</td>
					<td style="width:100px;border:1px solid #000;">'.$clasif->metodo_pago.'</td>
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