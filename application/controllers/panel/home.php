<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


class home extends MY_Controller {
	/**
	 * Evita la validacion (enfocado cuando se usa ajax). Ver mas en privilegios_model
	 * @var unknown_type
	 */
	private $excepcion_privilegio = array('');

	public function _remap($method){

		$this->load->model("usuarios_model");
		if($this->usuarios_model->checkSession()){
			$this->usuarios_model->excepcion_privilegio = $this->excepcion_privilegio;
			$this->info_empleado                         = $this->usuarios_model->get_usuario_info($this->session->userdata('id'), true);

			$this->{$method}();
		}else
			$this->{'login'}();
	}

	public function index(){

    $this->carabiner->js(array(
      array('general/msgbox.js'),
      array('panel/home.js'),
    ));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Panel de Administración'
		);
		
		// $this->load->model('cuentas_pagar_model');
		// $params['cuentas_pagar'] = $this->cuentas_pagar_model->get_cuentas_pagar('15', 'total_pagar DESC')['cuenta_pagar'];

		// $this->load->model('cajas_model');
		// $params['inventario'] = $this->cajas_model->get_inventario('15', 'total_debe DESC')['inventario'];
		

		// $client = new SoapClient("http://demo-facturacion.finkok.com/servicios/soap/stamp.wsdl");
		// $params = array('xml' => "PGNmZGk6Q29tcHJvYmFudGUgeG1sbnM6Y2ZkaT0iaHR0cDovL3d3dy5zYXQuZ29iLm14L2NmZC8zIiB4bWxuczp4c2k9Imh0dHA6Ly93d3cudzMub3JnLzIwMDEvWE1MU2NoZW1hLWluc3RhbmNlIiB4c2k6c2NoZW1hTG9jYXRpb249Imh0dHA6Ly93d3cuc2F0LmdvYi5teC9jZmQvMyBodHRwOi8vd3d3LnNhdC5nb2IubXgvc2l0aW9faW50ZXJuZXQvY2ZkLzMvY2ZkdjMyLnhzZCAgICIgdmVyc2lvbj0iMy4yIiBmb2xpbz0iMSIgZmVjaGE9IjIwMTMtMDEtMjNUMTM6NTA6MTAiIGZvcm1hRGVQYWdvPSJQQUdPIEVOIFVOQSBTT0xBIEVYSElCSUNJT04iIG5vQ2VydGlmaWNhZG89IjIwMDAxMDAwMDAwMjAwMDAwMjkzIiBjZXJ0aWZpY2Fkbz0iTUlJRTJqQ0NBOEtnQXdJQkFnSVVNakF3TURFd01EQXdNREF5TURBd01EQXlPVE13RFFZSktvWklodmNOQVFFRkJRQXdnZ0ZjTVJvd0dBWURWUVFEREJGQkxrTXVJRElnWkdVZ2NISjFaV0poY3pFdk1DMEdBMVVFQ2d3bVUyVnlkbWxqYVc4Z1pHVWdRV1J0YVc1cGMzUnlZV05wdzdOdUlGUnlhV0oxZEdGeWFXRXhPREEyQmdOVkJBc01MMEZrYldsdWFYTjBjbUZqYWNPemJpQmtaU0JUWldkMWNtbGtZV1FnWkdVZ2JHRWdTVzVtYjNKdFlXTnB3N051TVNrd0p3WUpLb1pJaHZjTkFRa0JGaHBoYzJsemJtVjBRSEJ5ZFdWaVlYTXVjMkYwTG1kdllpNXRlREVtTUNRR0ExVUVDUXdkUVhZdUlFaHBaR0ZzWjI4Z056Y3NJRU52YkM0Z1IzVmxjbkpsY204eERqQU1CZ05WQkJFTUJUQTJNekF3TVFzd0NRWURWUVFHRXdKTldERVpNQmNHQTFVRUNBd1FSR2x6ZEhKcGRHOGdSbVZrWlhKaGJERVNNQkFHQTFVRUJ3d0pRMjk1YjJGanc2RnVNVFF3TWdZSktvWklodmNOQVFrQ0RDVlNaWE53YjI1ellXSnNaVG9nUVhKaFkyVnNhU0JIWVc1a1lYSmhJRUpoZFhScGMzUmhNQjRYRFRFeU1UQXlOakU1TWpJME0xb1hEVEUyTVRBeU5qRTVNakkwTTFvd2dnRlRNVWt3UndZRFZRUURFMEJCVTA5RFNVRkRTVTlPSUVSRklFRkhVa2xEVlV4VVQxSkZVeUJFUlV3Z1JFbFRWRkpKVkU4Z1JFVWdVa2xGUjA4Z01EQTBJRVJQVGlCTlFWSlVTVTRnTVdFd1h3WURWUVFwRTFoQlUwOURTVUZEU1U5T0lFUkZJRUZIVWtsRFZVeFVUMUpGVXlCRVJVd2dSRWxUVkZKSlZFOGdSRVVnVWtsRlIwOGdNREEwSUVSUFRpQk5RVkpVU1U0Z1EwOUJTRlZKVEVFZ1dTQk9WVVZXVHlCTVJVOU9JRUZETVVrd1J3WURWUVFLRTBCQlUwOURTVUZEU1U5T0lFUkZJRUZIVWtsRFZVeFVUMUpGVXlCRVJVd2dSRWxUVkZKSlZFOGdSRVVnVWtsRlIwOGdNREEwSUVSUFRpQk5RVkpVU1U0Z01TVXdJd1lEVlFRdEV4eEJRVVE1T1RBNE1UUkNVRGNnTHlCSVJVZFVOell4TURBek5GTXlNUjR3SEFZRFZRUUZFeFVnTHlCSVJVZFVOell4TURBelRVUkdVazVPTURreEVUQVBCZ05WQkFzVENGTmxjblpwWkc5eU1JR2ZNQTBHQ1NxR1NJYjNEUUVCQVFVQUE0R05BRENCaVFLQmdRRGxySTlsb296ZCtVY1c3WUh0cUppbVFqelg5d0hJVWNjMUtaeUJCQjgvNWZac2daL3NtV1M0U2Q2SG5QczlHU1R0blRtTTRiRWd4MjhOM3VsVXNoYWFCRXRabzN0c2p3a0JWL3lWUTNTUnlNRGtxQkEyTkVqYmN1bStlL01kQ01IaVBJMWVTR0hFcGRFU3Q1NWEwUzZOMjRQVzczMlhtM1piR2dPcDF0aHQxd0lEQVFBQm94MHdHekFNQmdOVkhSTUJBZjhFQWpBQU1Bc0dBMVVkRHdRRUF3SUd3REFOQmdrcWhraUc5dzBCQVFVRkFBT0NBUUVBdW9QWGUrQkJJcm1KbitJR2VJK205N09sUDNSQzRDdDNhbWpHbVpJQ2J2aEk5QlRCTENML1B6UWpqV0J3VTBNRzh1SzZlL2djQjlmK2tsUGlYaFFUZUkxWUt6RnRXcnpjdHBORUpZbzBLWE1ndkRpcHV0S3BoUTMyNGRQMG56a0tVZlhsUkl6U2NKSkNTZ1J3OVppZktXTjBEOXFUZGtOa2prODNUb1Bnd25sZGc1bHpVNjJ3b1hvNEFLYmN1YWJBWU9Wb0M3b3dNNWJmTnVXSmU1NjZVekQ2aTVQRlkxNWpZTXppMStJQ3JpREl0Q3YzUytKZHF5ckJyWDNSbG9aaGR5WHFzMkh0eGZ3NGIxT2NZYm9QQ3U0KzlxTTNPVjAyd3lHS2xHUU1oZnJYTndZeWo4aHV4UzFwSGdoRVJPTTJaczBwYVpVT3krNmFqTStYaDBMWDJ3PT0iIGNvbmRpY2lvbmVzRGVQYWdvPSJTZXJhIG1hcmNhZGEgY29tbyBwYWdhZGEgZW4gY3VhbnRvIGVsIHJlY2VwdG9yIGhheWEgY3ViaWVydG8gZWwgcGFnby4iIHN1YlRvdGFsPSIxMTAwMC4wMCIgTW9uZWRhPSJwZXNvcyIgdG90YWw9IjEyNzYwLjAwIiBtZXRvZG9EZVBhZ289IlRyYW5zZmVyZW5jaWEgQmFuY2FyaWEiIHRpcG9EZUNvbXByb2JhbnRlPSJpbmdyZXNvIiBMdWdhckV4cGVkaWNpb249Ik1vcmVsaWEsIE1pY2hvYWMmIzIyNTtuIiBzZWxsbz0iMWFCWGo1SndJNXAxeW05ZTlSWDJpREp6N1QrRU5jUldXc1FHZjFZcFFWU21iZlRUN3JPSlZNdFROTWdzbE44Rk4vbnBxYjVjTE5nYXlHQWJJSGh0VlJyaVY5WkFRY2ZCUE4zZG1jNCsrUU9sdXpTbGpuUG44cXg0U2F3a1dEQWlFNWhWRFI0TC9NYnhzQ1F2dUZMWE1qS1FBRUNqdzN0N01Ta254MFZQM1ZZPSI+ICA8Y2ZkaTpFbWlzb3Igbm9tYnJlPSIgQXNvY2lhY2lvbiBkZSBBZ3JpY3VsdG9yZXMgZGVsIGRpc3RyaXRvICAiIHJmYz0iQUFEOTkwODE0QlA3Ij4gICAgPGNmZGk6RG9taWNpbGlvRmlzY2FsIGNhbGxlPSJBdiBNYWRlcm8iIG5vRXh0ZXJpb3I9IjQ1IiBjb2xvbmlhPSJDZW50cm8iIGxvY2FsaWRhZD0iTW9yZWxpYSIgcmVmZXJlbmNpYT0iU2luIFJlZmVyZW5jaWEiIG11bmljaXBpbz0iTW9yZWxpYSIgZXN0YWRvPSJNaWNob2FjJiMyMjU7biIgcGFpcz0iTSYjMjMzO3hpY28iIGNvZGlnb1Bvc3RhbD0iNTgwMDAiLz4gIDxjZmRpOkV4cGVkaWRvRW4gY2FsbGU9IkF2IE1hZGVybyIgcmVmZXJlbmNpYT0iU2luIFJlZmVyZW5jaWEiIG5vRXh0ZXJpb3I9IjQ1IiBjb2xvbmlhPSJDZW50cm8iIGxvY2FsaWRhZD0iTW9yZWxpYSIgbXVuaWNpcGlvPSJNb3JlbGlhIiBlc3RhZG89Ik1pY2hvYWMmIzIyNTtuIiBwYWlzPSJNJiMyMzM7eGljbyIgY29kaWdvUG9zdGFsPSI1ODAwMCIvPiAgICA8Y2ZkaTpSZWdpbWVuRmlzY2FsIFJlZ2ltZW49IlBydWViYXMgRmlzY2FsZXMiLz4gIDwvY2ZkaTpFbWlzb3I+ICA8Y2ZkaTpSZWNlcHRvciBub21icmU9IkVMIFNvY2lvbmF0aW9uIFNBIGRlIENWIiByZmM9IkhFTzg2MTIxNEpLTCI+ICAgIDxjZmRpOkRvbWljaWxpbyByZWZlcmVuY2lhPSJTaW4gUmVmZXJlbmNpYSIgZXN0YWRvPSJBZ3Vhc2NhbGllbnRlcyIgcGFpcz0iTSYjMjMzO3hpY28iLz4gIDwvY2ZkaTpSZWNlcHRvcj4gIDxjZmRpOkNvbmNlcHRvcz4gICAgPGNmZGk6Q29uY2VwdG8gY2FudGlkYWQ9IjIiIHVuaWRhZD0iUGllemEiIG5vSWRlbnRpZmljYWNpb249IlNVTiIgZGVzY3JpcGNpb249IlByYWRhIFN1bmdsYXNzZXMtUHJhZGEgU3VuIEdsYXNzZXMgQXZpYXRvciIgdmFsb3JVbml0YXJpbz0iNTUwMC4wMCIgaW1wb3J0ZT0iMTEwMDAuMDAiPjxjZmRpOkNvbXBsZW1lbnRvQ29uY2VwdG8vPiA8L2NmZGk6Q29uY2VwdG8+ICAgPC9jZmRpOkNvbmNlcHRvcz4gIDxjZmRpOkltcHVlc3RvcyB0b3RhbEltcHVlc3Rvc1RyYXNsYWRhZG9zPSIxNzYwLjAwIj4gICAgICAgIDxjZmRpOlRyYXNsYWRvcz4gICAgICAgICAgICA8Y2ZkaTpUcmFzbGFkbyBpbXBvcnRlPSIxNzYwLjAwIiB0YXNhPSIxNi4wMCIgaW1wdWVzdG89IklWQSIvPiAgICAgICAgCSAgICAgIDwvY2ZkaTpUcmFzbGFkb3M+ICAgICAgICA8L2NmZGk6SW1wdWVzdG9zPiAgPGNmZGk6Q29tcGxlbWVudG8vPjwvY2ZkaTpDb21wcm9iYW50ZT4K", 'username' => "gamalielm@indieds.com", 'password' => "gamaL1&l");
		// var_dump($client->stamp($params));



		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/general/home', $params);
		$this->load->view('panel/footer');
	}




	/**
	 * carga el login para entrar al panel
	 */
	public function login(){

		$params['seo'] = array(
			'titulo' => 'Login'
		);

		$this->load->library('form_validation');
		$rules = array(
			array('field'	=> 'usuario',
				'label'		=> 'Usuario',
				'rules'		=> 'required'),
			array('field'	=> 'pass',
				'label'		=> 'Contraseña',
				'rules'		=> 'required')
		);
		$this->form_validation->set_rules($rules);
		if($this->form_validation->run() == FALSE){
			$params['frm_errors'] = array(
					'title' => 'Error al Iniciar Sesión!',
					'msg' => preg_replace("[\n|\r|\n\r]", '', validation_errors()),
					'ico' => 'error');
		}else{
			$data = array('usuario' => $this->input->post('usuario'), 'pass' => $this->input->post('pass'));
			$mdl_res = $this->usuarios_model->setLogin($data);
			if ($mdl_res[0] && $this->usuarios_model->checkSession()) {
				redirect(base_url('panel/home'));
			}
			else{
				$params['frm_errors'] = array(
					'title' => 'Error al Iniciar Sesión!',
					'msg' => 'El usuario y/o contraseña son incorrectos, o no cuenta con los permisos necesarios para loguearse',
					'ico' => 'error');
			}
		}

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/login', $params);
		$this->load->view('panel/footer');
	}

	/**
	 * cierra la sesion del usuario
	 */
	public function logout(){
		$this->session->sess_destroy();
		redirect(base_url('panel/home'));
	}
}

?>