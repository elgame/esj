<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class catalogos extends MY_Controller {

	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array(
			'catalogos/cpaises/',
      'catalogos/cestados/',
      'catalogos/cmunicipios/',
			'catalogos/clocalidades/',
			'catalogos/ccps/',
			'catalogos/ccolonias/',
      'catalogos/fraccionArancelaria/',
      'catalogos/cnumEstacion/',
      'catalogos/cclaveStcc/',
      'catalogos/cclaveMatPeligro/',
			'catalogos/cunidadPeso/',
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

  /*
   |------------------------------------------------------------------------
   | AJAX
   |------------------------------------------------------------------------
   */

  /**
   * Obtiene un listado de paises.
   *
   * @return json
   */
  public function cpaises()
  {
  	$this->load->model('cpais_model');

    echo json_encode($this->cpais_model->getPaises($this->input->get('term')));
  }

  /**
   * Obtiene un listado de estados.
   *
   * @return json
   */
  public function cestados()
  {
  	$this->load->model('cestado_model');

    echo json_encode($this->cestado_model->getEstados($this->input->get()));
  }

  /**
   * Obtiene un listado de municipios.
   *
   * @return json
   */
  public function cmunicipios()
  {
  	$this->load->model('cmunicipio_model');

    echo json_encode($this->cmunicipio_model->getMunicipios($this->input->get()));
  }

  /**
   * Obtiene un listado de localidad.
   *
   * @return json
   */
  public function clocalidades()
  {
  	$this->load->model('clocalidad_model');

    echo json_encode($this->clocalidad_model->getLocalidades($this->input->get()));
  }

  /**
   * Obtiene un listado de codigos postales.
   *
   * @return json
   */
  public function ccps()
  {
  	$this->load->model('ccp_model');

    echo json_encode($this->ccp_model->getCPs($this->input->get()));
  }

  /**
   * Obtiene un listado de codigos postales.
   *
   * @return json
   */
  public function ccolonias()
  {
  	$this->load->model('ccolonias_model');

    echo json_encode($this->ccolonias_model->getColonias($this->input->get()));
  }

  /**
   * Obtiene un listado de fracciones arancelarias.
   *
   * @return json
   */
  public function fraccionArancelaria()
  {
  	$this->load->model('cfraccionarancelaria_model');

    echo json_encode($this->cfraccionarancelaria_model->getFraccionArancelaria($this->input->get('term')));
  }

  /**
   * Obtiene un listado de estaciones para carta porte.
   *
   * @return json
   */
  public function cnumEstacion()
  {
    $this->load->model('cnum_estacion_model');

    echo json_encode($this->cnum_estacion_model->get($this->input->get('term')));
  }

  /**
   * Obtiene un listado de clave stcc para carta porte.
   *
   * @return json
   */
  public function cclaveStcc()
  {
    $this->load->model('cclave_stcc_model');

    echo json_encode($this->cclave_stcc_model->get($this->input->get('term')));
  }

  /**
   * Obtiene un listado de clave material peligroso para carta porte.
   *
   * @return json
   */
  public function cclaveMatPeligro()
  {
    $this->load->model('cclave_mat_peligro_model');

    echo json_encode($this->cclave_mat_peligro_model->get($this->input->get('term')));
  }

  /**
   * Obtiene un listado de unidad peso para carta porte.
   *
   * @return json
   */
  public function cunidadPeso()
  {
    $this->load->model('cunidad_peso_model');

    echo json_encode($this->cunidad_peso_model->get($this->input->get('term')));
  }

}



/* End of file usuarios.php */
/* Location: ./application/controllers/panel/usuarios.php */
