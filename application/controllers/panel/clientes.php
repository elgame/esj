<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class clientes extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array('clientes/ajax_get_proveedores/',
		'clientes/merges/',
		'clientes/catalogo_xls/');

	public function _remap($method){

		$this->load->model("usuarios_model");
		if($this->usuarios_model->checkSession()){
			$this->usuarios_model->excepcion_privilegio = $this->excepcion_privilegio;
			$this->info_empleado                         = $this->usuarios_model->get_usuario_info($this->session->userdata('id_usuario'), true);

			if($this->usuarios_model->tienePrivilegioDe('', get_class($this).'/'.$method.'/')){
				$this->{$method}();
			}else
				redirect(base_url('panel/home?msg=1'));
		}else
			redirect(base_url('panel/home'));
	}

  public function fecha($fecha)
  {
    $meses = array('ENE' => '01', 'FEB' => '02', 'MAR' => '03', 'ABR' => '04', 'MAY' => '05', 'JUN' => '06', 'JUL' => '07', 'AGO' => '08', 'SEP' => '09', 'OCT' => '10', 'NOV' => '11', 'DIC' => '12' );
    $fecha = explode('/', $fecha);
    return $fecha[2].'-'.$meses[strtoupper($fecha[1])].'-'.$fecha[0];
  }

	public function merges()
	{

		$fila = 0;
		//C¢digo Cliente,Raz¢n Social,R.F.C.,Estatus,Calle,N£mero Exterior,N£mero Interior,Colonia,C¢digo Postal,eMail,Pa¡s,Estado,Ciudad,Municipio
		if (($gestor = fopen("estados_de_cuenta2.csv", "r")) !== FALSE) {
		    while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
		        $numero = count($datos);

		        if ($datos[0] == 'new')
		        {
		        	$id_cliente = 0;
		        	$res = $this->db->query("SELECT id_cliente FROM clientes WHERE cuenta_cpi like '".trim($datos[1])."'");
		        	if($res->num_rows() > 0)
		        		$id_cliente = $res->row()->id_cliente;
		        	else
		        		echo "No Registrado: ".$datos[1]." <==> ".$datos[2]."<br>";
		        }elseif($id_cliente > 0)
		        {
		        	if (substr($datos[0], 0, 3) == '   ') //abono
		        	{
		        		$data_abono = array(
							'id_factura'     => $id_factura,
							'fecha'          => $this->fecha(trim($datos[0])),
							'concepto'       => trim($datos[3]),
							'total'          => MyString::float(trim($datos[5])),
							'poliza_ingreso' => 't',
		        		);
		        		$this->db->insert('facturacion_abonos', $data_abono);
		        	}else //factura
		        	{
		        		$data_factura = array(
							'id_cliente'       => $id_cliente,
							'id_empresa'       => '1',
							'version'          => '3.2',
							'serie'            => ''.trim($datos[1]),
							'folio'            => ''.trim($datos[2]),
							'fecha'            => $this->fecha(trim($datos[0])),
							'subtotal'         => MyString::float(trim($datos[4])),
							'total'            => MyString::float(trim($datos[4])),
							'total_letra'      => MyString::num2letras( MyString::float(trim($datos[4])) ),
							'no_aprobacion'    => 0,
							'plazo_credito'    => MyString::diasEntreFechas($this->fecha(trim($datos[0])), $this->fecha(trim($datos[6])) ),
							'ano_aprobacion'   => '',
							'no_certificado'   => '',
							'cadena_original'  => '',
							'sello'            => '',
							'certificado'      => '',
							'condicion_pago'   => 'cr',
							'status'           => 'P',
							'docs_finalizados' => 't',
							'poliza_diario'    => 't',
							'is_factura'       => 'f',
		        		);
		        		$this->db->insert('facturacion', $data_factura);
		        		$id_factura = $this->db->insert_id('facturacion', 'id_factura');
		        	}
		        }





				// $res = $this->db->query("SELECT * FROM clientes WHERE rfc like '".trim($datos[2])."' AND rfc <> ''");
				// if ($res->num_rows() > 0)
				// {
				// 	if($datos[2] != '')
				// 	{
				// 		// echo "UPDATE clientes SET nombre_fiscal = '".utf8_encode(trim($datos[1]))."', calle = '".utf8_encode(trim($datos[4]))."',
				// 		// 							no_exterior = '".trim($datos[5])."', no_interior = '".trim($datos[6])."', colonia = '".utf8_encode(trim($datos[7]))."',
				// 		// 							cp = '".trim($datos[8])."', email = '".trim($datos[9])."', pais = '".utf8_encode(trim($datos[10]))."',
				// 		// 							estado = '".utf8_encode(trim($datos[11]))."', localidad = '".utf8_encode(trim($datos[12]))."',
				// 		// 							municipio = '".utf8_encode(trim($datos[13]))."', rfc = '".trim($datos[2])."', cuenta_cpi = '".trim($datos[0])."'
				// 		// 		WHERE rfc like '".trim($datos[2])."'; <br>";
				// 	}
				// }else{
				// 	$res->free_result();
				// 	$res = $this->db->query("SELECT * FROM clientes WHERE cuenta_cpi like '".trim($datos[0])."'");
				// 	if ($res->num_rows() == 0)
				// 	{
				// 		// echo "INSERT INTO clientes (
				// 		// 	nombre_fiscal, calle, no_exterior, no_interior,
				// 		// 	colonia, cp, email, pais,
				// 		// 	estado, localidad, municipio, rfc, cuenta_cpi) VALUES
				// 		// 	('".utf8_encode(trim($datos[1]))."', '".utf8_encode(trim($datos[4]))."', '".trim($datos[5])."', '".trim($datos[6])."',
				// 		// 	'".utf8_encode(trim($datos[7]))."', '".trim($datos[8])."', '".trim($datos[9])."', '".utf8_encode(trim($datos[10]))."',
				// 		// 	'".utf8_encode(trim($datos[11]))."', '".utf8_encode(trim($datos[12]))."', '".utf8_encode(trim($datos[13]))."',
				// 		// 	'".trim($datos[2])."', '".trim($datos[0])."'  ); <br>";
				// 	}

				// 	$fila++;
				// 	// if($datos[2] != '')
				// 		// echo "INSERT INTO clientes (
				// 		// nombre_fiscal, calle, no_exterior, no_interior,
				// 		// colonia, cp, email, pais,
				// 		// estado, localidad, municipio, rfc, cuenta_cpi) VALUES
				// 		// ('".utf8_encode(trim($datos[1]))."', '".utf8_encode(trim($datos[4]))."', '".trim($datos[5])."', '".trim($datos[6])."',
				// 		// '".utf8_encode(trim($datos[7]))."', '".trim($datos[8])."', '".trim($datos[9])."', '".utf8_encode(trim($datos[10]))."',
				// 		// '".utf8_encode(trim($datos[11]))."', '".utf8_encode(trim($datos[12]))."', '".utf8_encode(trim($datos[13]))."', '".trim($datos[2])."', '".trim($datos[0])."'  ); <br>";
				// 	// echo "'".trim($datos[2])."',";
				// }
				// $res->free_result();
		    }
		    fclose($gestor);
		}

	}

  public function index()
  {
		$this->carabiner->js(array(
	        array('general/msgbox.js'),
			array('panel/clientes/agregar.js'),
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Administración de Clientes'
		);

    $this->load->model('empresas_model');
    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
    if(!isset($_GET['did_empresa']))
    	$_GET['did_empresa'] = $params['empresa']->id_empresa;

    $this->load->model('clientes_model');
    $params['clientes'] = $this->clientes_model->getClientes();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/clientes/admin', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Muestra el Formulario para agregar un proveedor
	 * @return [type] [description]
	 */
	public function agregar()
	{
		$this->carabiner->css(array(
			array('libs/jquery.uniform.css', 'screen'),
		));
		$this->carabiner->js(array(
      	array('libs/jquery.uniform.min.js'),
        array('libs/jquery.numeric.js'),
        array('general/keyjump.js'),
			array('panel/clientes/agregar.js'),
		));

    	$this->load->model('empresas_model');

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Agregar Cliente'
		);

		$this->configAddModCliente();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$this->load->model('clientes_model');
			$res_mdl = $this->clientes_model->addCliente();

			if(!$res_mdl['error'])
				redirect(base_url('panel/clientes/agregar/?'.MyString::getVarsLink(array('msg')).'&msg=3'));
		}

		$this->load->model('documentos_model');
    $params['documentos'] = $this->documentos_model->getDocumentos();
    $params['empresa']    = $this->empresas_model->getDefaultEmpresa();

    //bancos
    $this->load->model('banco_cuentas_model');
    $params['bancos'] = $this->banco_cuentas_model->getBancos(false);

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/clientes/agregar', $params);
		$this->load->view('panel/footer');
	}

	/*
 	|	Muestra el Formulario para modificar un usuario
 	*/
	public function modificar()
	{
		if (isset($_GET['id']))
		{
			$this->carabiner->css(array(
				array('libs/jquery.uniform.css', 'screen'),
				array('libs/jquery.treeview.css', 'screen')
			));
			$this->carabiner->js(array(
				array('libs/jquery.uniform.min.js'),
        array('libs/jquery.numeric.js'),
        array('general/keyjump.js'),
				array('libs/jquery.treeview.js'),
				array('panel/clientes/agregar.js')
			));

      $this->load->model('clientes_model');
			$this->load->model('empresas_model');

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar cliente'
			);

			$this->configAddModCliente('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->clientes_model->updateCliente($this->input->get('id'));

				if($res_mdl['error'] == FALSE)
					redirect(base_url('panel/clientes/?'.MyString::getVarsLink(array('msg', 'id')).'&msg=4'));
			}

			$params['cliente'] = $this->clientes_model->getClienteInfo();
      //Cuentas del cliente
      $params['cuentas_clientes'] = $this->clientes_model->getCuentas($_GET['id']);
      //bancos
      $this->load->model('banco_cuentas_model');
      $params['bancos'] = $this->banco_cuentas_model->getBancos(false);

			$this->load->model('documentos_model');
			$params['documentos'] = $this->documentos_model->getDocumentos();
      $params['empresa']       = $this->empresas_model->getInfoEmpresa($params['cliente']['info']->id_empresa);

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/header', $params);
			$this->load->view('panel/general/menu', $params);
			$this->load->view('panel/clientes/modificar', $params);
			$this->load->view('panel/footer');
		}
		else
			redirect(base_url('panel/clientes/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * pone eliminado a un proveedor
	 * @return [type] [description]
	 */
	public function eliminar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('clientes_model');
			$res_mdl = $this->clientes_model->updateCliente( $this->input->get('id'), array('status' => 'e') );
			if($res_mdl)
				redirect(base_url('panel/clientes/?'.MyString::getVarsLink(array('msg')).'&msg=5'));
		}
		else
			redirect(base_url('panel/clientes/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Activa un proveedor eliminado
	 * @return [type] [description]
	 */
	public function activar()
	{
		if (isset($_GET['id']))
		{
			$this->load->model('clientes_model');
			$res_mdl = $this->clientes_model->updateCliente( $this->input->get('id'), array('status' => 'ac') );
			if($res_mdl)
				redirect(base_url('panel/clientes/?'.MyString::getVarsLink(array('msg')).'&msg=6'));
		}
		else
			redirect(base_url('panel/clientes/?'.MyString::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * Obtiene lostado de productores para el autocomplete, ajax
	 */
	public function ajax_get_proveedores(){
		$this->load->model('clientes_model');
		$params = $this->clientes_model->getClientesAjax();

		echo json_encode($params);
	}

	public function catalogo_xls()
	{
		$this->load->model('clientes_model');
		$this->clientes_model->catalogo_xls();
	}



  /*
 	|	Asigna las reglas para validar un articulo al agregarlo
 	*/
	public function configAddModCliente($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'fnombre_fiscal',
						'label' => 'Nombre fiscal',
						'rules' => 'required|max_length[140]'),
			array('field' => 'fcalle',
						'label' => 'Calle',
						'rules' => 'max_length[60]'),
			array('field' => 'fno_exterior',
						'label' => 'No. exterior',
						'rules' => 'max_length[7]'),
			array('field' => 'fno_interior',
						'label' => 'No. interior',
						'rules' => 'max_length[20]'),
			array('field' => 'fcolonia',
						'label' => 'Colonia',
						'rules' => 'max_length[60]'),
			array('field' => 'flocalidad',
						'label' => 'Localidad',
						'rules' => 'max_length[45]'),
			array('field' => 'fmunicipio',
						'label' => 'Municipio',
						'rules' => 'max_length[45]'),
			array('field' => 'festado',
						'label' => 'Estado',
						'rules' => 'max_length[45]'),
			array('field' => 'fpais',
						'label' => 'Pais',
						'rules' => 'max_length[25]'),

			array('field' => 'frfc',
						'label' => 'RFC',
						'rules' => 'max_length[13]'),
			array('field' => 'fcurp',
						'label' => 'CURP',
						'rules' => 'max_length[35]'),
			array('field' => 'fcp',
						'label' => 'CP',
						'rules' => 'max_length[10]'),
			array('field' => 'ftelefono',
						'label' => 'Telefono',
						'rules' => 'max_length[15]'),
			array('field' => 'fcelular',
						'label' => 'Celular',
						'rules' => 'max_length[20]'),

			array('field' => 'femail',
						'label' => 'Email',
						'rules' => 'max_length[600]'),
			array('field' => 'fcuenta_cpi',
						'label' => 'Cuenta ContpaqI',
						'rules' => 'max_length[12]'),
			array('field' => 'fdias_credito',
						'label' => 'Dias de credito',
						'rules' => 'is_natural'),
      array('field' => 'fmetodo_pago',
            'label' => 'Metodo de Pago',
            'rules' => 'max_length[20]'),
      array('field' => 'fdigitos',
            'label' => 'Ultimos 4 digitos',
            'rules' => 'max_length[20]'),

			array('field' => 'documentos[]',
						'label' => 'Documentos del cliente',
						'rules' => 'is_natural_no_zero'),

      array('field' => 'fempresa',
            'label' => '',
            'rules' => ''),
      array('field' => 'did_empresa',
            'label' => 'Empresa',
            'rules' => ''),
		);

		$this->form_validation->set_rules($rules);
	}


	private function showMsgs($tipo, $msg='', $title='Usuarios')
	{
		switch($tipo){
			case 1:
				$txt = 'El campo ID es requerido.';
				$icono = 'error';
				break;
			case 2: //Cuendo se valida con form_validation
				$txt = $msg;
				$icono = 'error';
				break;
			case 3:
				$txt = 'El cliente se agregó correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'El cliente se modificó correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'El cliente se eliminó correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'El cliente se activó correctamente.';
				$icono = 'success';
				break;
		}

		return array(
				'title' => $title,
				'msg' => $txt,
				'ico' => $icono);
	}
}



/* End of file usuarios.php */
/* Location: ./application/controllers/panel/usuarios.php */
