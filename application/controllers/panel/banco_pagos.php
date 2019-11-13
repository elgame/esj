<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class banco_pagos extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array(
		'banco_pagos/set_compra/',
		'banco_pagos/layout/',
    'banco_pagos/aplica_pagos/',

    'banco_pagos/set_bascula/',
    'banco_pagos/layout_bascula/',
    'banco_pagos/aplica_pagos_bascula/',
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
      array('libs/jquery.numeric.js'),
			array('general/msgbox.js'),
      array('general/util.js'),
			array('panel/banco/banco_pagos.js')
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Pagos Bancarios'
		);

		$this->load->model('banco_cuentas_model');
    $this->load->model('banco_pagos_model');
		$this->load->model('empresas_model');

    $this->configAddModPagos();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->banco_pagos_model->actualizarPagos($_POST);
      redirect(base_url('panel/banco_pagos/?msg=4'));
    }

    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
    $empresaD = array('did_empresa' => (isset($_GET['did_empresa'])? $_GET['did_empresa']: $params['empresa']->id_empresa));
    $_GET['did_empresa'] = $empresaD['did_empresa'];
    $params['data']    = $this->banco_cuentas_model->getSaldosCuentasData();
    $params['pagos']  = $this->banco_pagos_model->getPagos( $empresaD );
    $params['rows_completos'] = true;
    // foreach($params['pagos'] as $pago){
    //   foreach ($pago->pagos as $key => $value)
    //   {
    //     if ( $value->id_cuenta<=0 || $value->ref_alfanumerica=='' || $value->referencia=='' )
    //     {
    //       $params['rows_completos'] = false;
    //       break;
    //     }
    //   }
    // }


		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/banco/pagos/listado_compras', $params);
		$this->load->view('panel/footer');
	}

  public function layout()
  {
    $this->load->model('banco_pagos_model');
    $_GET['did_empresa'] = $_GET['ide'];
    $this->banco_pagos_model->layoutBanamex();
  }

  public function aplica_pagos()
  {
    $this->load->model('banco_pagos_model');
    $_GET['did_empresa'] = $_GET['ide'];
    $this->banco_pagos_model->aplicarPagos();
    redirect(base_url('panel/banco_pagos/?msg=5'));
  }

  public function eliminar_pago()
  {
    if (isset($_GET['id_pago']{0}))
    {
      $this->load->model('banco_pagos_model');
      $this->banco_pagos_model->eliminarPago($_GET['id_pago']);
      redirect(base_url('panel/banco_pagos?msg=3'));
    }else
      redirect(base_url('panel/banco_pagos?msg=1'));
  }

  /**
   * Asigna o quita una compra que no se ha aplicado el pago
   */
	public function set_compra()
	{
		$this->load->model('banco_pagos_model');
    echo json_encode( $this->banco_pagos_model->setCompra($_POST) );
	}


  /**
   * **************************************************
   * ********** Pagos a bascula
   * **************************************************
   */
  public function bascula()
  {
    $this->carabiner->js(array(
      array('libs/jquery.numeric.js'),
      array('general/msgbox.js'),
      array('general/util.js'),
      array('panel/banco/banco_pagos.js')
    ));

    $params['info_empleado'] = $this->info_empleado['info']; //info empleado
    $params['seo'] = array(
      'titulo' => 'Pagos Bascula'
    );

    $this->load->model('banco_cuentas_model');
    $this->load->model('banco_pagos_model');
    $this->load->model('empresas_model');

    $this->configAddModPagosBascula();
    if ($this->form_validation->run() == FALSE)
    {
      $params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
    }
    else
    {
      $this->banco_pagos_model->actualizarPagosBascula($_POST);
      redirect(base_url('panel/banco_pagos/bascula/?msg=4'));
    }

    $params['data']    = $this->banco_cuentas_model->getSaldosCuentasData();
    $params['empresa'] = $this->empresas_model->getDefaultEmpresa();
    $params['pagos']  = $this->banco_pagos_model->getPagosBascula();
    $params['rows_completos'] = true;
    // foreach($params['pagos'] as $pago){
    //   foreach ($pago->pagos as $key => $value)
    //   {
    //     if ( $value->id_cuenta<=0 || $value->ref_alfanumerica=='' || $value->referencia=='' )
    //     {
    //       $params['rows_completos'] = false;
    //       break;
    //     }
    //   }
    // }

    if (isset($_GET['msg']))
      $params['frm_errors'] = $this->showMsgs($_GET['msg']);

    $this->load->view('panel/header', $params);
    $this->load->view('panel/general/menu', $params);
    $this->load->view('panel/banco/pagos/listado_basculas', $params);
    $this->load->view('panel/footer');
  }

  public function layout_bascula()
  {
    $this->load->model('banco_pagos_model');
    $this->banco_pagos_model->layoutBanamexBascula();
  }

  public function aplica_pagos_bascula()
  {
    $this->load->model('banco_pagos_model');
    $_GET['did_empresa'] = $_GET['ide'];
    $this->banco_pagos_model->aplicarPagosBascula();
    redirect(base_url('panel/banco_pagos/bascula/?msg=5'));
  }

  public function eliminar_pago_bascula()
  {
    if (isset($_GET['id_pago']{0}))
    {
      $this->load->model('banco_pagos_model');
      $this->banco_pagos_model->eliminarPagoBascula($_GET['id_pago']);
      redirect(base_url('panel/banco_pagos/bascula?msg=3'));
    }else
      redirect(base_url('panel/banco_pagos/bascula?msg=1'));
  }

  /**
   * Asigna o quita una compra que no se ha aplicado el pago
   */
  public function set_bascula()
  {
    $this->load->model('banco_pagos_model');
    echo json_encode( $this->banco_pagos_model->setBascula($_POST) );
  }


  /*
 	|	Asigna las reglas para validar un articulo al agregarlo
 	*/
	public function configAddModPagos($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'id_pago',
						'label' => 'Pago',
						'rules' => 'callback_validafield[id_pago]'),
      array('field' => 'monto',
            'label' => 'Monto',
            'rules' => 'callback_validafield[monto]'),
			array('field' => 'cuenta_proveedor',
						'label' => 'Cuenta Proveedor',
						'rules' => ''),
			array('field' => 'ref_alfanumerica',
						'label' => 'Referencia 1',
						'rules' => ''),
      array('field' => 'ref_numerica',
            'label' => 'Referencia 2',
            'rules' => ''),
		);

		$this->form_validation->set_rules($rules);
	}
  public function validafield($val, $param)
  {
    if(is_array($val))
      foreach ($val as $key => $value)
      {
        foreach ($value as $key2 => $value2)
        {
          if($value2 == ''){
            $this->form_validation->set_message('validafield', 'El valor es requerido del campo '.$param);
            return FALSE;
          }
        }
      }
  }

	public function configAddModPagosBascula($accion='agregar')
  {
    $this->load->library('form_validation');
    $rules = array(
      array('field' => 'id_pago',
            'label' => 'Pago',
            'rules' => 'callback_validafield[id_pago]'),
      array('field' => 'monto',
            'label' => 'Monto',
            'rules' => 'callback_validafield[monto]'),
      array('field' => 'cuenta_proveedor',
            'label' => 'Cuenta Proveedor',
            'rules' => ''),
      array('field' => 'ref_alfanumerica',
            'label' => 'Referencia 1',
            'rules' => ''),
      array('field' => 'ref_numerica',
            'label' => 'Referencia 2',
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
				$txt = 'Se elimino el pago correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'Se guardo correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'Se registraron los pagos correctamente.';
				$icono = 'success';
				break;

			case 6:
				$txt = 'La cuenta se activó correctamente.';
				$icono = 'success';
				break;

			case 7:
				$txt = 'Se registro el depósito correctamente.';
				$icono = 'success';
				break;
			case 8:
				$txt = 'Se registro el retiro correctamente.';
				$icono = 'success';
				break;
			case 9:
				$txt = 'Se cancelo la operacion correctamente.';
				$icono = 'success';
				break;
			case 10:
				$txt = 'Se elimino la operacion correctamente.';
				$icono = 'success';
				break;
			case 11:
				$txt = 'La operacion cambio de estado correctamente.';
				$icono = 'success';
				break;
			case 30:
				$txt = 'La cuenta no tiene saldo suficiente.';
				$icono = 'error';
				break;
      case 31:
        $txt = 'Los movimientos se trasladaron correctamente!';
        $icono = 'success';
        break;
      case 32:
        $txt = 'No hubo movimientos para trasladar!';
        $icono = 'error';
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
