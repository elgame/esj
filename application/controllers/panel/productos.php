<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class productos extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array(
			'productos/ajax_get_familias/',
			'productos/ajax_get_productos/',
			'productos/acomoda_codigos/',
		);

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

  public function index()
  {
		$this->carabiner->js(array(
			array('general/msgbox.js'),
			array('general/supermodal.js'),
			array('panel/almacen/agregar_familias.js'),
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Administración de Productos'
		);

		$this->load->model('productos_model');
		$params['familias'] = $this->productos_model->getFamilias();
		$params['html_familias']  = $this->load->view('panel/almacen/productos/admin_familias', $params, true);

		$params['productos'] = $this->productos_model->getProductos();
		$params['html_productos'] = $this->load->view('panel/almacen/productos/admin_productos', $params, true);

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/almacen/productos/admin', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * Agregar familias supermodal
	 * @return [type] [description]
	 */
	public function agregar_familia()
	{
		$this->carabiner->css(array(
			array('libs/jquery.uniform.css', 'screen'),
		));
		$this->carabiner->js(array(
			array('libs/jquery.uniform.min.js'),
			array('libs/jquery.numeric.js'),
			array('panel/almacen/agregar_familias.js'),
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Agregar Familia'
		);

		$params['closeModal'] = false;

		$this->configAddModFamilia();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$this->load->model('productos_model');
			$res_mdl = $this->productos_model->addFamilia();

			$params['closeModal'] = true;
			$params['frm_errors'] = $this->showMsgs('3');
		}


		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);


		$this->load->view('panel/almacen/productos/agregar_familia', $params);
	}

	/*
 	|	Muestra el Formulario para modificar un familia
 	*/
	public function modificar_familia()
	{
		if (isset($_GET['id']))
		{
			$this->carabiner->css(array(
				array('libs/jquery.uniform.css', 'screen'),
			));
			$this->carabiner->js(array(
				array('libs/jquery.uniform.min.js'),
				array('general/msgbox.js'),
				array('panel/almacen/agregar_familias.js'),
			));

			$this->load->model('productos_model');
			$params['closeModal'] = false;

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar Familia'
			);

			$this->configAddModFamilia('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->productos_model->updateFamilia($this->input->get('id'));

				$params['closeModal'] = true;
				$params['frm_errors'] = $this->showMsgs('4');
			}

			$params['data'] = $this->productos_model->getFamiliaInfo($_GET['id']);

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/almacen/productos/modificar_familia', $params);
		}
		else
			redirect(base_url('panel/productos/modificar_familia/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * pone eliminado a un area
	 * @return [type] [description]
	 */
	public function eliminar_familia()
	{
		$response = $this->showMsgs(1);

		if (isset($_GET['id']))
		{
			$this->load->model('productos_model');
			$res_mdl  = $this->productos_model->deleteFamilia( $this->input->get('id'));
			$response = $this->showMsgs(5);
		}

		echo json_encode($response);
	}

	/**
	 * Obtiene las familias de una empresa
	 */
	public function ajax_get_familias(){
		$this->load->model('productos_model');
		$params['familias'] = $this->productos_model->getFamilias();
		$html = $this->load->view('panel/almacen/productos/admin_familias', $params, true);

		echo json_encode(array(
				'response' => array(
						'title' => '',
						'msg'   => '',
						'ico'   => 'success'),
						'data' => $html
				));
	}


	public function acomoda_codigos()
	{
		$this->load->model('productos_model');
		$this->load->model('empresas_model');
		$empresas = $this->empresas_model->getEmpresas();
		foreach ($empresas['empresas'] as $key => $empresa)
		{
			echo "<br>".$empresa->nombre_fiscal."<br>";
			$_GET['fid_empresa'] = $empresa->id_empresa;
			$_GET['fstatus'] = 'ac';
			$familias = $this->productos_model->getFamilias();
			foreach ($familias['familias'] as $key => $familia)
			{
				echo $familia->nombre."<br>";
				$_GET['fid_familia'] = $familia->id_familia;
				$productos = $this->productos_model->getProductos(false);
				foreach ($productos['productos'] as $key => $producto)
				{
					$codigo = $key+1;
					echo "{$producto->nombre} => {$codigo}<br>";
					$this->db->update('productos', array('codigo' => "{$codigo}"), "id_producto = {$producto->id_producto}");
				}
			}
		}
	}
	/**
	 * Agregar productos supermodal
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
			array('panel/almacen/agregar_familias.js'),
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Agregar Producto'
		);

		$params['closeModal'] = false;

		$this->load->model('productos_model');

		$this->configAddModProducto();
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$res_mdl = $this->productos_model->addProducto();

			$params['closeModal'] = true;
			$params['frm_errors'] = $this->showMsgs('10');
		}

		$params['unidades'] = $this->productos_model->getUnidades(false);
		$params['folio'] = $this->productos_model->getFolioNext($this->input->get('fid_familia'));


		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);


		$this->load->view('panel/almacen/productos/agregar_producto', $params);
	}

	/*
 	|	Muestra el Formulario para modificar un producto
 	*/
	public function modificar()
	{
		if (isset($_GET['id']))
		{
			$this->carabiner->css(array(
				array('libs/jquery.uniform.css', 'screen'),
			));
			$this->carabiner->js(array(
				array('libs/jquery.uniform.min.js'),
				array('general/msgbox.js'),
				array('panel/almacen/agregar_familias.js'),
			));

			$this->load->model('productos_model');
			$params['closeModal'] = false;

			$params['info_empleado'] = $this->info_empleado['info']; //info empleado
			$params['seo'] = array(
				'titulo' => 'Modificar Familia'
			);

			$this->configAddModProducto('modificar');
			if ($this->form_validation->run() == FALSE)
			{
				$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
			}
			else
			{
				$res_mdl = $this->productos_model->updateProducto($this->input->get('id'));

				$params['closeModal'] = true;
				$params['frm_errors'] = $this->showMsgs('9');
			}

			$params['unidades'] = $this->productos_model->getUnidades(false);
			$params['data']     = $this->productos_model->getProductoInfo($_GET['id']);

			if (isset($_GET['msg']))
				$params['frm_errors'] = $this->showMsgs($_GET['msg']);

			$this->load->view('panel/almacen/productos/modificar_producto', $params);
		}
		else
			redirect(base_url('panel/productos/modificar_familia/?'.String::getVarsLink(array('msg')).'&msg=1'));
	}

	/**
	 * pone eliminado a un producto
	 * @return [type] [description]
	 */
	public function eliminar()
	{
		$response = $this->showMsgs(1);

		if (isset($_GET['id']))
		{
			$this->load->model('productos_model');
			$res_mdl  = $this->productos_model->deleteProducto( $this->input->get('id'));
			$response = $this->showMsgs(10);
		}

		echo json_encode($response);
	}

	/**
	 * Activa un producto eliminado
	 * @return [type] [description]
	 */
	public function activar()
	{
		$response = $this->showMsgs(1);

		if (isset($_GET['id']))
		{
			$this->load->model('productos_model');
			$res_mdl  = $this->productos_model->deleteProducto( $this->input->get('id'), 'ac');
			$response = $this->showMsgs(8);
		}

		echo json_encode($response);
	}

	/**
	 * Obtiene los productos de una familia
	 */
	public function ajax_get_productos(){
		$this->load->model('productos_model');
		$params['productos'] = $this->productos_model->getProductos();
		$html = $this->load->view('panel/almacen/productos/admin_productos', $params, true);

		echo json_encode(array(
				'response' => array(
						'title'   => '',
						'msg'     => '',
						'ico'     => 'success'),
						'data'    => $html,
						'familia' => $params['productos']['familia'],
				));
	}



  /*
 	|	Asigna las reglas para validar una familia
 	*/
	public function configAddModFamilia($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'fid_empresa',
						'label' => 'Empresa',
						'rules' => 'required|numeric'),
			array('field' => 'fnombre',
						'label' => 'Nombre',
						'rules' => 'required|max_length[50]'),
			array('field' => 'ftipo',
						'label' => 'Tipo empresa',
						'rules' => 'required|max_length[2]'),

			array('field' => 'fempresa',
						'label' => 'Empresa',
						'rules' => ''),
		);

		$this->form_validation->set_rules($rules);
	}

	public function configAddModProducto($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'fcodigo',
						'label' => 'Codigo',
						'rules' => 'required|max_length[25]|callback_val_codigo['.$accion.']'),
			array('field' => 'fnombre',
						'label' => 'Nombre',
						'rules' => 'required|max_length[90]'),
			array('field' => 'funidad',
						'label' => 'Unidad medida',
						'rules' => 'required|numeric'),
			array('field' => 'fstock_min',
						'label' => 'Stock min',
						'rules' => 'numeric'),
			array('field' => 'ubicacion',
						'label' => 'Ubicacion',
						'rules' => 'max_length[70]'),
      array('field' => 'fieps',
            'label' => 'IEPS',
            'rules' => 'numeric'),
			array('field' => 'cuenta_contpaq',
						'label' => 'Cuenta contpaq',
						'rules' => 'max_length[12]'),

			array('field' => 'pnombre[]',
						'label' => 'Presentacion',
						'rules' => 'max_length[25]'),
			array('field' => 'pcantidad[]',
						'label' => 'Cantidad',
						'rules' => ''),
		);

		// if ($accion == 'modificar')
		// {
		// 	$rules[0]['rules'] = 'required|max_length[25]|callback_val_codigo['.$accion.']';
		// }

		$this->form_validation->set_rules($rules);
	}

	public function val_codigo($str, $tipo)
	{
		$sql = '';
		if($tipo == 'modificar')
			$sql = " AND id_producto <> ".$this->input->get('id')."";

		$res = $this->db->select('Count(id_producto) AS num')
			->from('productos')
			->where("id_familia = ".$this->input->get('fid_familia')." AND codigo = '".$str."'".$sql)->get()->row();
		if($res->num == '0')
			return true;

		$this->form_validation->set_message('val_codigo', 'El codigo ya esta utilizado.');
		return false;
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
				$txt = 'La Familia se agregó correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'La Familia se modificó correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'La Familia se eliminó correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'La Familia se activó correctamente.';
				$icono = 'success';
				break;

			case 7:
				$txt = 'El producto se eliminó correctamente.';
				$icono = 'success';
				break;
			case 8:
				$txt = 'El producto se activó correctamente.';
				$icono = 'success';
				break;
			case 9:
				$txt = 'El producto se modificó correctamente.';
				$icono = 'success';
				break;
			case 10:
				$txt = 'El producto se agregó correctamente.';
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
