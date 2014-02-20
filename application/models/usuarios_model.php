<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Usuarios_model extends privilegios_model {


	function __construct()
	{
		parent::__construct();
	}

	public function get_usuarios($paginados = true, $tipo='f')
	{
		$sql = '';
		//paginacion
		if($paginados)
		{
			$this->load->library('pagination');
			$params = array(
					'result_items_per_page' => '40',
					'result_page' => (isset($_GET['pag'])? $_GET['pag']: 0)
			);
			if($params['result_page'] % $params['result_items_per_page'] == 0)
				$params['result_page'] = ($params['result_page']/$params['result_items_per_page']);
		}

		$sql = "WHERE user_nomina = '{$tipo}'";
		//Filtros para buscar
		if($this->input->get('fnombre') != '')
			$sql .= " AND ( lower(u.nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(u.usuario) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(u.email) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%')";

		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? ' AND': ' AND')." u.status='".$this->input->get('fstatus')."'";

    if($this->input->get('did_empresa') != '')
      $sql .= ' AND u.id_empresa = ' . $this->input->get('did_empresa');

		$query = BDUtil::pagination("
				SELECT u.id AS id_usuario, u.nombre, u.apellido_paterno, u.apellido_materno, u.usuario, u.email, u.tipo,
					u.status, u.rfc, u.cuenta_banco, u.no_seguro
				FROM usuarios u
				".$sql."
				ORDER BY u.usuario ASC
				", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'usuarios'      => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0)
			$response['usuarios'] = $res->result();

		return $response;
	}

 	/*
 	|	Agrega un usuario a la BDD ya sea por medio del Formulario, Facebook o Twitter
 	*/
	public function setRegistro($data=NULL)
	{
		if ($data == NULL)
		{
			$data = array(
						'nombre'           => mb_strtoupper($this->input->post('fnombre'), 'utf-8'),
						'apellido_paterno' => mb_strtoupper($this->input->post('fapellido_paterno'), 'utf-8'),
						'apellido_materno' => mb_strtoupper($this->input->post('fapellido_materno'), 'utf-8'),
						'usuario'          => trim($this->input->post('fusuario'))!=''?$this->input->post('fusuario'): NULL,
						'password'         => trim($this->input->post('fpass'))?$this->input->post('fpass'): NULL,

						'calle'            => mb_strtoupper($this->input->post('fcalle'), 'utf-8'),
						'numero'           => mb_strtoupper($this->input->post('fnumero'), 'utf-8'),
						'colonia'          => mb_strtoupper($this->input->post('fcolonia'), 'utf-8'),
						'municipio'        => mb_strtoupper($this->input->post('fmunicipio'), 'utf-8'),
						'estado'           => mb_strtoupper($this->input->post('festado'), 'utf-8'),
						'cp'               => mb_strtoupper($this->input->post('fcp'), 'utf-8'),

						'curp'             => mb_strtoupper($this->input->post('fcurp'), 'utf-8'),
						'fecha_nacimiento' => ($this->input->post('ffecha_nacimiento')!=''? $this->input->post('ffecha_nacimiento'): NULL),
						'fecha_entrada'    => ($this->input->post('ffecha_entrada')!=''? $this->input->post('ffecha_entrada'): NULL),
						'nacionalidad'     => $this->input->post('fnacionalidad'),
						'estado_civil'     => $this->input->post('festado_civil'),
						'sexo'             => $this->input->post('fsexo'),
						'cuenta_cpi'       => $this->input->post('fcuenta_cpi'),
						'email'            => $this->input->post('femail'),

						'id_empresa'           => $this->input->post('did_empresa'),
						'id_puesto'            => $this->input->post('fpuesto'),
						'salario_diario'       => is_numeric($this->input->post('fsalario_diario'))? $this->input->post('fsalario_diario'): 0,
						'infonavit'            => is_numeric($this->input->post('finfonavit'))? $this->input->post('finfonavit'): 0,
						'salario_diario_real'  => is_numeric($this->input->post('fsalario_diario_real'))? $this->input->post('fsalario_diario_real'): 0,
						'esta_asegurado'       => $this->input->post('festa_asegurado')=='t'?'t':'f',
						'regimen_contratacion' => $this->input->post('fregimen_contratacion'),
						'rfc'                  => mb_strtoupper($this->input->post('frfc'), 'utf-8'),

						'cuenta_banco'      => trim($this->input->post('dcuenta_banco'))?$this->input->post('dcuenta_banco'): '',
						'no_seguro'         => trim($this->input->post('dno_seguro'))?$this->input->post('dno_seguro'): '',
						'user_nomina'       => trim($this->input->post('duser_nomina'))?$this->input->post('duser_nomina'): 'f',
						'id_departamente'   => $this->input->post('fdepartamente')!==false? $this->input->post('fdepartamente'): NULL,
					);
			if($this->input->post('ffecha_salida') != '')
				$data['fecha_salida']    = $this->input->post('ffecha_salida');
			$data_privilegios = $this->input->post('dprivilegios');
		}

		$this->db->insert('usuarios', $data);
		$id_usuario = $this->db->insert_id('usuarios', 'id');

		//privilegios
		if (is_array( $data_privilegios )) {
			$privilegios = array();
			foreach ($data_privilegios as $key => $value) {
				$privilegios[] = array('usuario_id' => $id_usuario, 'privilegio_id' => $value);
			}
			$this->db->insert_batch('usuarios_privilegios', $privilegios);
		}

		return array('error' => FALSE);
	}

	/*
 	|	Modificar la informacion de un usuario
 	*/
	public function modificar_usuario($id_usuario, $data=NULL)
	{

		if ($data == NULL)
		{
			$data = array(
						'nombre'           => mb_strtoupper($this->input->post('fnombre'), 'utf-8'),
						'apellido_paterno' => mb_strtoupper($this->input->post('fapellido_paterno'), 'utf-8'),
						'apellido_materno' => mb_strtoupper($this->input->post('fapellido_materno'), 'utf-8'),
						'usuario'          => trim($this->input->post('fusuario'))!=''?$this->input->post('fusuario'): NULL,
						'password'         => trim($this->input->post('fpass')),

						'calle'            => mb_strtoupper($this->input->post('fcalle'), 'utf-8'),
						'numero'           => mb_strtoupper($this->input->post('fnumero'), 'utf-8'),
						'colonia'          => mb_strtoupper($this->input->post('fcolonia'), 'utf-8'),
						'municipio'        => mb_strtoupper($this->input->post('fmunicipio'), 'utf-8'),
						'estado'           => mb_strtoupper($this->input->post('festado'), 'utf-8'),
						'cp'               => $this->input->post('fcp'),

						'curp'             => $this->input->post('fcurp'),
						'fecha_nacimiento' => ($this->input->post('ffecha_nacimiento')!=''? $this->input->post('ffecha_nacimiento'): NULL),
						'fecha_entrada'    => ($this->input->post('ffecha_entrada')!=''? $this->input->post('ffecha_entrada'): NULL),
						'fecha_salida'     => ($this->input->post('ffecha_salida')!=''? $this->input->post('ffecha_salida'): NULL),
						'nacionalidad'     => $this->input->post('fnacionalidad'),
						'estado_civil'     => $this->input->post('festado_civil'),
						'sexo'             => $this->input->post('fsexo'),
						'cuenta_cpi'       => $this->input->post('fcuenta_cpi'),
						'email'            => $this->input->post('femail'),

						'id_empresa'           => $this->input->post('did_empresa'),
						'id_puesto'            => $this->input->post('fpuesto'),
						'salario_diario'       => is_numeric($this->input->post('fsalario_diario'))? $this->input->post('fsalario_diario'): 0,
						'infonavit'            => is_numeric($this->input->post('finfonavit'))? $this->input->post('finfonavit'): 0,
						'salario_diario_real'  => is_numeric($this->input->post('fsalario_diario_real'))? $this->input->post('fsalario_diario_real'): 0,
						'esta_asegurado'       => $this->input->post('festa_asegurado')=='t'?'t':'f',
						'regimen_contratacion' => $this->input->post('fregimen_contratacion'),
						'rfc'                  => mb_strtoupper($this->input->post('frfc'), 'utf-8'),

						'cuenta_banco'      => trim($this->input->post('dcuenta_banco'))?$this->input->post('dcuenta_banco'): '',
						'no_seguro'         => trim($this->input->post('dno_seguro'))?$this->input->post('dno_seguro'): '',
						'user_nomina'       => trim($this->input->post('duser_nomina'))?$this->input->post('duser_nomina'): 'f',
						'id_departamente'   => $this->input->post('fdepartamente')!==false? $this->input->post('fdepartamente'): NULL,
					);
			$data_privilegios = $this->input->post('dprivilegios');
		}

		if ($data['password'] == '')
			unset($data['password']);

		$this->db->update('usuarios', $data, array('id'=>$id_usuario));

		//privilegios
		if (is_array( $data_privilegios )) {
			$this->db->delete('usuarios_privilegios', array('usuario_id' => $id_usuario));
			$privilegios = array();
			foreach ($data_privilegios as $key => $value) {
				$privilegios[] = array('usuario_id' => $id_usuario, 'privilegio_id' => $value);
			}
			$this->db->insert_batch('usuarios_privilegios', $privilegios);
		}

		return array('error' => FALSE);
	}

	/*
	 |	Obtiene la informacion de un usuario
	 */
	public function get_usuario_info($id_usuario=FALSE, $basic_info=FALSE)
	{
		$id_usuario = ($id_usuario==false)? $_GET['id']: $id_usuario;

		$sql_res = $this->db->select("u.id, u.nombre, u.usuario, u.email, u.tipo, u.status,
						u.apellido_paterno, u.apellido_materno, u.calle, u.numero, u.colonia, u.municipio, u.estado, u.cp,
						Date(u.fecha_nacimiento) AS fecha_nacimiento, Date(u.fecha_entrada) AS fecha_entrada,
						Date(u.fecha_salida) AS fecha_salida, u.nacionalidad, u.estado_civil, u.sexo, u.cuenta_cpi,
						e.id_empresa, e.nombre_fiscal, u.id_puesto, u.salario_diario, u.infonavit, u.salario_diario_real,
						u.esta_asegurado, u.regimen_contratacion, u.curp, u.rfc, u.cuenta_banco, u.user_nomina, u.no_seguro,
						u.id_departamente" )
 												->from("usuarios u")
 												->join("empresas e", "e.id_empresa = u.id_empresa", "left")
												->where("id", $id_usuario)
												->get();
		$data['info'] = array();

		if ($sql_res->num_rows() > 0)
			$data['info']	=$sql_res->result();

		if ($basic_info == False) {
			//Privilegios
			$res = $this->db
				->select('privilegio_id')
				->from('usuarios_privilegios')
				->where("usuario_id = '".$id_usuario."'")
			->get();
			if($res->num_rows() > 0){
				foreach($res->result() as $priv)
					$data['privilegios'][] = $priv->privilegio_id;
			}
			$res->free_result();
		}

		return $data;
	}

	/*
	 |	Cambia el estatus de un usuario a eliminado
	 */
	public function eliminar_usuario($id_usuario)
	{
		$this->db->update('usuarios', array('status' => 'f'), array('id' => $id_usuario));
		return TRUE;
	}

	/*
	 |	Cambia el estatus de un usuari a activo
	 */
	public function activar_usuario($id_usuario)
	{
		$this->db->update('usuarios', array('status' => 't'), array('id' => $id_usuario));
		return TRUE;
	}

	/*
	| Actualiza algun campo de un registro tipo usuario
	*/
	public function updateUserFields($data, $where)
	{
		$this->db->update('usuarios', $data, $where);
		return TRUE;
	}

	/*
		|	Valida un email si esta disponible, primero valida que el email exista para el
		|	usuario que se modificara, si el email no es igual al de ese usuario entonces
		|	verifica que el email exista para algun otro usuario.
		|	$tabla : tabla de la bdd donde esta el campo email
		|	$where : condicion donde se debe pasar un array de la siguiente forma
								array('campo_id_usuario'=>$valor_id_usuario, 'email'=>$valor_email)
	*/
	public function valida_email($tabla, $where)
	{
		$sql_res = $this->db->select("id")
												->from($tabla)
												->where($where)
												->get();

		if ($sql_res->num_rows() == 0)
			return FALSE;
		return TRUE;
	}



	/**
	 * **************************************************************
	 * *************   Login, sessiones  **************
	 * **************************************************************
	 *
	 * Revisa si la sesion del usuario esta activa
	 */
	public function checkSession($check_admin=true){
		if($this->session->userdata('id_usuario') && $this->session->userdata('usuario')) {
			if($this->session->userdata('id_usuario')!='' && $this->session->userdata('usuario')!=''){
				if ($check_admin) {
					if ($this->session->userdata('tipo') === 'admin')
						return true;
				}else
					return true;
			}
		}
		return false;
	}

	/*
		|	Logea al usuario y crea la session con los parametros
		| id_usuario, username, email y si marco el campo "no cerra sesion" agrega el parametro "remember" a
		|	la session con 1 año para que expire
		| array('usuario', 'pass')
		*/
	public function setLogin($user_data)
	{
		$user_data = "usuario = '".$user_data['usuario']."' AND password = '".$user_data['pass']."' AND status = '1' ";
		$fun_res = $this->exec_GetWhere('usuarios', $user_data, TRUE);

		if ($fun_res != FALSE)
		{
			$user_data = array(
					'id_usuario'      => $fun_res[0]->id,
					'usuario' => $fun_res[0]->usuario,
					'email'   => $fun_res[0]->email,
					'tipo'    => $fun_res[0]->tipo,
					'idunico' => uniqid('l', true));
				$this->crea_session($user_data);
		}

			return array($fun_res, 'msg'=>'El correo electrónico y/o contraseña son incorrectos');
		// return array($fun_res);
	}

	/*
		|	Ejecuta un db->get_where() || Select * From <tabla> Where <condicion>
		|	$tabla : tabla de la bdd
		|	$where : condicion
		| $return_data : Indica si regresara el resulta de la consulta, si es False y la consulta obtuvo al menos
		|		un registro entonces regresara TRUE si no FALSE
		*/
	public function exec_GetWhere($tabla, $where, $return_data=FALSE)
	{
		// SELECT * FROM $tabla WHERE id=''
		$sql_res = $this->db->get_where($tabla, $where);
		if ($sql_res->num_rows() > 0)
		{
			if ($return_data)
				return $sql_res->result();
			return TRUE;
		}
		return FALSE;
	}

	/*
		|	Crea la session del usuario que se logueara
		|	$user_data : informacion del usuario que se agregara al array session
		*/
	private function crea_session($user_data)
	{
		if (isset($_POST['remember']))
		{
			$this->session->set_userdata('remember',TRUE);
			$this->session->sess_expiration	= 60*60*24*365;
		}
		else
			$this->session->sess_expiration	= 7200;

		$this->session->set_userdata($user_data);
	}

  /**
   * Obtiene el listado de empresas para usar en peticiones Ajax.
   */
  public function getUsuariosAjax(){
    $sql = '';
    $res = $this->db->query("
        SELECT id, nombre, usuario, apellido_paterno, apellido_materno
        FROM usuarios
        WHERE status = 't' AND
                (lower(nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' OR
                 lower(apellido_paterno) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' OR
                 lower(apellido_materno) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%')
        ORDER BY nombre ASC
        LIMIT 20");

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $response[] = array(
            'id' => $itm->id,
            'label' => $itm->nombre.' '.$itm->apellido_paterno.' '.$itm->apellido_materno,
            'value' => $itm->nombre.' '.$itm->apellido_paterno.' '.$itm->apellido_materno,
            'item' => $itm,
        );
      }
    }

    return $response;
  }

  /**
   * Obtiene los puestos de los usuarios.
   *
   * @return array
   */
  public function puestos()
  {
    $query = $this->db->query("SELECT id_puesto, nombre, abreviatura
                               FROM usuarios_puestos
                               WHERE status = 't'
                               ORDER BY nombre ASC");

    $puestos = array();

    if ($query->num_rows() > 0)
    {
      $puestos = $query->result();
    }

    return $puestos;
  }

  /**
   * Obtiene los departamentos de los usuarios.
   *
   * @return array
   */
  public function departamentos()
  {
    $query = $this->db->query("SELECT id_departamento, nombre
                               FROM usuarios_departamento
                               WHERE status = 't'
                               ORDER BY nombre ASC");

    $departamentos = array();

    if ($query->num_rows() > 0)
    {
      $departamentos = $query->result();
    }

    return $departamentos;
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */