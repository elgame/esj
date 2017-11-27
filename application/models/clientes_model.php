<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class clientes_model extends CI_Model {


	function __construct()
	{
		parent::__construct();
		$this->load->model('bitacora_model');
	}

	public function getClientes($paginados = true)
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
			$sql = "WHERE ( lower(p.nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.calle) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.colonia) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.municipio) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(p.estado) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

		$_GET['fstatus'] = ($this->input->get('fstatus') !== false? $this->input->get('fstatus'): 'ac');
		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." p.status='".$this->input->get('fstatus')."'";

    if($this->input->get('did_empresa') != '')
      $sql .= ($sql==''? 'WHERE': ' AND').' p.id_empresa = ' . $this->input->get('did_empresa');

		// if($this->input->get('ftipo_proveedor') != '' && $this->input->get('ftipo_proveedor') != 'todos')
		// 	$sql .= ($sql==''? 'WHERE': ' AND')." p.tipo_proveedor='".$this->input->get('ftipo_proveedor')."'";

    $query['query'] =
    			"SELECT p.id_cliente, p.nombre_fiscal, p.calle, p.no_exterior, p.no_interior, p.colonia,
    						p.localidad, p.municipio, p.telefono, p.estado, p.status,
								p.nombre_fiscal, p.pais, p.cp, p.celular, p.email, p.pag_web,
								p.cuenta_cpi, p.rfc, p.curp, p.dias_credito, p.metodo_pago
					FROM clientes p
					{$sql}
					ORDER BY p.nombre_fiscal ASC
					";
    if($paginados) {
			$query = BDUtil::pagination($query['query'], $params, true);
    }
		$res = $this->db->query($query['query']);

		$response = array(
				'clientes'    => array(),
				'total_rows'     => isset($query['total_rows'])? $query['total_rows']: 0,
				'items_per_page' => isset($params['result_items_per_page'])? $params['result_items_per_page']: 0,
				'result_page'    => isset($params['result_page'])? $params['result_page']: 0
		);
		if($res->num_rows() > 0){
			$response['clientes'] = $res->result();
			foreach ($response['clientes'] as $key => $value) {
				$response['clientes'][$key]->direccion = $value->calle.($value->no_exterior!=''? ' '.$value->no_exterior: '')
										 .($value->no_interior!=''? $value->no_interior: '')
										 .($value->colonia!=''? ', '.$value->colonia: '')
										 .($value->localidad!=''? ', '.$value->localidad: '')
										 .($value->municipio!=''? ', '.$value->municipio: '')
										 .($value->estado!=''? ', '.$value->estado: '');
			}
		}

		return $response;
	}

 	/**
 	 * Agrega un proveedor a la BDD
 	 * @param [type] $data [description]
 	 */
	public function addCliente($data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
            'nombre_fiscal'   => $this->input->post('fnombre_fiscal'),
            'calle'           => $this->input->post('fcalle'),
            'no_exterior'     => $this->input->post('fno_exterior'),
            'no_interior'     => $this->input->post('fno_interior'),
            'colonia'         => $this->input->post('fcolonia'),
            'localidad'       => $this->input->post('flocalidad'),
            'municipio'       => $this->input->post('fmunicipio'),
            'estado'          => $this->input->post('festado'),
            'cp'              => $this->input->post('fcp'),
            'telefono'        => $this->input->post('ftelefono'),
            'celular'         => $this->input->post('fcelular'),
            'email'           => $this->input->post('femail'),
            'cuenta_cpi'      => $this->input->post('fcuenta_cpi'),
            'rfc'             => $this->input->post('frfc'),
            'curp'            => $this->input->post('fcurp'),
            'pais'            => $this->input->post('fpais'),
            'dias_credito'    => (is_numeric($this->input->post('fdias_credito'))? $this->input->post('fdias_credito'): 0),
            'metodo_pago'     => $this->input->post('fmetodo_pago'),
            'ultimos_digitos' => $this->input->post('fdigitos'),
            'id_empresa'      => $this->input->post('did_empresa'),
            'show_saldo'      => $this->input->post('show_saldo')==='true'? 't': 'f',
						);
		}

		$this->db->insert('clientes', $data);
		$id_cliente = $this->db->insert_id('clientes', 'id_cliente');
		$this->addDocumentos($id_cliente);
    $this->addCuentas($id_cliente);

		// Bitacora
    $this->bitacora_model->_insert('clientes', $id_cliente,
                                    array(':accion'    => 'el cliente', ':seccion' => 'clientes',
                                          ':folio'     => $data['nombre_fiscal'],
                                          ':id_empresa' => $data['id_empresa'],
                                          ':empresa'   => 'en '.$this->input->post('fempresa')));

		return array('error' => FALSE);
	}

	/**
	 * Modificar la informacion de un proveedor
	 * @param  [type] $id_cliente [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateCliente($id_cliente, $data=NULL)
	{

		if ($data==NULL)
		{
			$data = array(
            'nombre_fiscal'   => $this->input->post('fnombre_fiscal'),
            'calle'           => $this->input->post('fcalle'),
            'no_exterior'     => $this->input->post('fno_exterior'),
            'no_interior'     => $this->input->post('fno_interior'),
            'colonia'         => $this->input->post('fcolonia'),
            'localidad'       => $this->input->post('flocalidad'),
            'municipio'       => $this->input->post('fmunicipio'),
            'estado'          => $this->input->post('festado'),
            'cp'              => $this->input->post('fcp'),
            'telefono'        => $this->input->post('ftelefono'),
            'celular'         => $this->input->post('fcelular'),
            'email'           => $this->input->post('femail'),
            'cuenta_cpi'      => $this->input->post('fcuenta_cpi'),
            'rfc'             => $this->input->post('frfc'),
            'curp'            => $this->input->post('fcurp'),
            'pais'            => $this->input->post('fpais'),
            'dias_credito'    => $this->input->post('fdias_credito'),
            'metodo_pago'     => $this->input->post('fmetodo_pago'),
            'ultimos_digitos' => $this->input->post('fdigitos'),
            'id_empresa'      => $this->input->post('did_empresa'),
            'show_saldo'      => $this->input->post('show_saldo')==='true'? 't': 'f',
						);
			// Bitacora
	    $id_bitacora = $this->bitacora_model->_update('clientes', $id_cliente, $data,
	                              array(':accion'       => 'el cliente', ':seccion' => 'clientes',
	                                    ':folio'        => $data['nombre_fiscal'],
	                                    ':id_empresa'   => $data['id_empresa'],
	                                    ':empresa'      => 'en '.$this->input->post('fempresa'),
	                                    ':id'           => 'id_cliente',
	                                    ':titulo'       => 'Cliente'));
		}else {
			if (isset($data['status']) && $data['status'] === 'e') {
				// Bitacora
				$clientedata = $this->getClienteInfo($id_cliente);
		    $this->bitacora_model->_cancel('clientes', $id_cliente,
		                                    array(':accion'     => 'el cliente', ':seccion' => 'clientes',
		                                          ':folio'      => $clientedata['info']->nombre_fiscal,
		                                          ':id_empresa' => $clientedata['info']->id_empresa,
		                                          ':empresa'    => 'de '.$clientedata['info']->empresa->nombre_fiscal));
			}
		}

		$this->db->update('clientes', $data, array('id_cliente' => $id_cliente));

		$this->db->delete('clientes_documentos', array('id_cliente' => $id_cliente));
		$this->addDocumentos($id_cliente);
    $this->addCuentas($id_cliente);

		return array('error' => FALSE);
	}

	public function addDocumentos($id_cliente, $data=null){
		$data = array();

		if ($data==NULL)
		{
			if(is_array($this->input->post('documentos')))
			{
				foreach ($this->input->post('documentos') as $key => $docu)
				{
					$data[] = array(
							'id_cliente'   => $id_cliente,
							'id_documento' => $docu
							);
				}
			}
		}

		if(count($data) > 0)
			$this->db->insert_batch('clientes_documentos', $data);
	}

  /**
   * ******* CUENTAS DE CLIENTES ****************
   * ***********************************************
   * Agrega o actualiza cuentas del cliente
   * @param [type] $id_cliente [description]
   */
  private function addCuentas($id_cliente)
  {
    $cuentas = $this->getCuentas($id_cliente);

    if ( is_array($this->input->post('cuentas_alias')) )
    {
      foreach ($this->input->post('cuentas_alias') as $key => $value)
      {
        $data = array('id_cliente' => $id_cliente,
                'alias'        => $_POST['cuentas_alias'][$key],
                'cuenta'       => $_POST['cuentas_cuenta'][$key],
                'id_banco'     => $_POST['fbanco'][$key],
              );
        if (is_numeric($_POST['cuentas_id'][$key]))  //update
        {
          foreach ($cuentas as $keyc => $cuent) {
            if ($cuent->id_cuenta == $_POST['cuentas_id'][$key]) {
              unset($cuentas[$keyc]);
            }
          }

          // if($_POST['cuentas_delte'][$key] == 'true')
          //  $data['status'] = 'f';
          $this->db->update('clientes_cuentas', $data, "id_cuenta = {$_POST['cuentas_id'][$key]}");
        }else  //insert
        {
          if($data['alias'] != '' && $data['cuenta'] != '')
            $this->db->insert('clientes_cuentas', $data);
        }
      }
    }

    // Elimina las cuentas
    if (count($cuentas) > 0)
      foreach ($cuentas as $keyc => $cuent) {
        $this->db->update('clientes_cuentas', array('status' => 'f'), "id_cuenta = {$cuent->id_cuenta}");
      }
  }

  /**
   * Obtiene el listado de cuentas del cliente
   * @return [type] [description]
   */
  public function getCuentas($id_cliente, $id_cuenta=null){
    $sql = ($id_cuenta==null? '': ' AND pc.id_cuenta = '.$id_cuenta);
    $res = $this->db->query("
        SELECT pc.id_cuenta, pc.id_cliente, pc.alias, pc.cuenta, pc.status,
          (pc.alias || ' *' || substring(pc.cuenta from '....$')) AS full_alias,
          bb.id_banco, bb.nombre AS banco, bb.codigo, bb.rfc
        FROM clientes_cuentas AS pc
          LEFT JOIN banco_bancos AS bb ON pc.id_banco = bb.id_banco
        WHERE pc.status = 't' AND pc.id_cliente = {$id_cliente} {$sql}
        ORDER BY full_alias ASC");

    $response = array();
    if($res->num_rows() > 0){
      $response = $res->result();
    }

    return $response;
  }

	/**
	 * Obtiene la informacion de un proveedor
	 * @param  boolean $id_cliente [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getClienteInfo($id_cliente=FALSE, $basic_info=FALSE)
	{
    // $id_cliente = (isset($_GET['id']))? $_GET['id']: $id_cliente;
		$id_cliente = $id_cliente? $id_cliente: (isset($_GET['id'])? $_GET['id']: 0);

		$sql_res = $this->db->select("id_cliente, nombre_fiscal, calle, no_exterior, no_interior, colonia, localidad, municipio,
														estado, cp, telefono, celular, email, cuenta_cpi, rfc, curp, status, dias_credito, pais,
                            metodo_pago, ultimos_digitos, id_empresa, show_saldo" )
												->from("clientes")
												->where("id_cliente", $id_cliente)
												->get();
		$data['info'] = array();

		if ($sql_res->num_rows() > 0)
			$data['info']	= $sql_res->row();
		$sql_res->free_result();

		$data['docus'] = array();
		if ($basic_info == False) {
			$sql_res = $this->db->select("id_cliente, id_documento" )
													->from("clientes_documentos")
													->where("id_cliente", $id_cliente)
													->get();
			$data['docus'] = $sql_res->result();
			$sql_res->free_result();

			// Carga la info de la empresa.
      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($data['info']->id_empresa);
      $data['info']->empresa = $empresa['info'];
		}

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