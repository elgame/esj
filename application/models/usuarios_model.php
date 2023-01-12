<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Usuarios_model extends privilegios_model {


	function __construct()
	{
		parent::__construct();
	}

	public function get_usuarios($paginados = true, $tipo='f', $de_rancho='n')
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

		$sql = "WHERE u.tipo = 'admin' AND user_nomina = '{$tipo}'";
    $sql .= " AND u.de_rancho = '{$de_rancho}'"; //filtro para los de rancho
		//Filtros para buscar
		if($this->input->get('fnombre') != '')
			$sql .= " AND ( lower(u.nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
                lower(u.apellido_paterno) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
                lower(u.apellido_materno) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(u.usuario) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' OR
								lower(u.email) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%')";

		if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
			$sql .= ($sql==''? ' AND': ' AND')." u.status='".$this->input->get('fstatus')."'";

    if($this->input->get('did_empresa') != '')
      $sql .= ' AND u.id_empresa = ' . $this->input->get('did_empresa');

    if ($this->input->get('contrato') == 'true') {
      $sql .= " AND u.fecha_contrato IS NOT NULL AND (u.fecha_contrato - Date(now())) <= 15";
    }

		$query = BDUtil::pagination("
				SELECT u.id AS id_usuario, u.nombre, u.apellido_paterno, u.apellido_materno, u.usuario, u.email, u.tipo,
					u.status, u.rfc, u.cuenta_banco, u.no_seguro, (fecha_contrato - Date(now())) AS dias_faltantes
				FROM usuarios u
				".$sql."
				ORDER BY (u.nombre || u.apellido_paterno || u.apellido_materno) ASC
				", $params, true);
		$res = $this->db->query($query['query']);

    $rescontrato = $this->db->query("
      SELECT Count(id) AS nums
      FROM usuarios
      WHERE fecha_contrato IS NOT NULL AND (fecha_contrato - Date(now())) <= 15")->row();

		$response = array(
				'usuarios'      => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page'],
        'contrato'       => $rescontrato->nums
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
      $query = $this->db->select('no_empleado')
        ->from('usuarios')
        ->where('id_empresa', $this->input->post('did_empresa'))
        ->order_by('no_empleado', 'DESC')
        ->limit(1)
        ->get();

      // $noEmpleado = $query->num_rows === 0 ? 1 : ++$query->row()->no_empleado;

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
						'fecha_imss'       => ($this->input->post('ffecha_imss')!=''? $this->input->post('ffecha_imss'): NULL),
						'nacionalidad'     => $this->input->post('fnacionalidad'),
						'estado_civil'     => $this->input->post('festado_civil'),
						'sexo'             => $this->input->post('fsexo'),
						'cuenta_cpi'       => $this->input->post('fcuenta_cpi'),
            'email'            => $this->input->post('femail'),
						'telefono'         => $this->input->post('ftelefono'),

						'id_empresa'           => $this->input->post('did_empresa'),
            'id_puesto'            => $this->input->post('fpuesto'),
            'registro_patronal'    => (strlen($this->input->post('fregistro_patronal'))>0? $this->input->post('fregistro_patronal'): NULL),

						'id_area'              => is_numeric($this->input->post('areaId'))? $this->input->post('areaId'): NULL, // cultivo
						'salario_diario'       => is_numeric($this->input->post('fsalario_diario'))? $this->input->post('fsalario_diario'): 0,
						'infonavit'            => is_numeric($this->input->post('finfonavit'))? $this->input->post('finfonavit'): 0,
            'fondo_ahorro'         => is_numeric($this->input->post('ffondo_ahorro'))? $this->input->post('ffondo_ahorro'): 0,
            'fondo_ahorro_cpi'     => $this->input->post('ffondo_ahorro_cpi'),
						'salario_diario_real'  => is_numeric($this->input->post('fsalario_diario_real'))? $this->input->post('fsalario_diario_real'): 0,
						'esta_asegurado'       => $this->input->post('festa_asegurado')=='t'?'t':'f',
						'regimen_contratacion' => $this->input->post('fregimen_contratacion'),
						'rfc'                  => mb_strtoupper($this->input->post('frfc'), 'utf-8'),

						'cuenta_banco'      => trim($this->input->post('dcuenta_banco'))?$this->input->post('dcuenta_banco'): '',
						'no_seguro'         => trim($this->input->post('dno_seguro'))?$this->input->post('dno_seguro'): '',
						'user_nomina'       => trim($this->input->post('duser_nomina'))?$this->input->post('duser_nomina'): 'f',
						'id_departamente'   => $this->input->post('fdepartamente')!==false? $this->input->post('fdepartamente'): NULL,
            'de_rancho'       => trim($this->input->post('de_rancho'))?$this->input->post('de_rancho'): 'n',

            // 'no_empleado' => $noEmpleado,
            'no_empleado'   => trim($this->input->post('dno_trabajador'))? intval($this->input->post('dno_trabajador')): 0,
            'no_checador'   => trim($this->input->post('dno_checador'))? intval($this->input->post('dno_checador')): NULL,

            'tipo_contrato' => trim($this->input->post('tipo_contrato'))? $this->input->post('tipo_contrato'): NULL,
            // 'tipo_regimen'  => trim($this->input->post('tipo_regimen'))? $this->input->post('tipo_regimen'): NULL,
            'tipo_jornada'  => trim($this->input->post('tipo_jornada'))? $this->input->post('tipo_jornada'): NULL,
            'riesgo_puesto' => trim($this->input->post('riesgo_puesto'))? $this->input->post('riesgo_puesto'): NULL,
            'p_alimenticia' => trim($this->input->post('dp_alimenticia'))? $this->input->post('dp_alimenticia'): 0,
            'fonacot' => trim($this->input->post('dinfonacot'))? $this->input->post('dinfonacot'): 0,

            'fecha_contrato' => ($this->input->post('ffecha_contrato')!=''? $this->input->post('ffecha_contrato'): NULL)
					);
			if($this->input->post('ffecha_salida') != '')
				$data['fecha_salida']    = $this->input->post('ffecha_salida');

      if($this->input->post('fbanco') != '')
        $data['banco'] = $this->input->post('fbanco');

			$data_privilegios = $this->input->post('dprivilegios');
		}

		$this->db->insert('usuarios', $data);
		$id_usuario = $this->db->insert_id('usuarios_id_seq');

		//privilegios
		if (is_array( $data_privilegios )) {
			$privilegios = array();
			foreach ($data_privilegios as $key => $value) {
				$privilegios[] = array('usuario_id' => $id_usuario, 'privilegio_id' => $value, 'id_empresa' => $_POST['idEmpresa']);
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
						'fecha_imss'       => ($this->input->post('ffecha_imss')!=''? $this->input->post('ffecha_imss'): NULL),
						'fecha_salida'     => ($this->input->post('ffecha_salida')!=''? $this->input->post('ffecha_salida'): NULL),
						'nacionalidad'     => $this->input->post('fnacionalidad'),
						'estado_civil'     => $this->input->post('festado_civil'),
						'sexo'             => $this->input->post('fsexo'),
						'cuenta_cpi'       => $this->input->post('fcuenta_cpi'),
						'email'            => $this->input->post('femail'),
            'telefono'         => $this->input->post('ftelefono'),

						'id_empresa'           => $this->input->post('did_empresa'),
						'id_puesto'            => $this->input->post('fpuesto'),
            'registro_patronal'    => (strlen($this->input->post('fregistro_patronal'))>0? $this->input->post('fregistro_patronal'): NULL),

            'id_area'              => is_numeric($this->input->post('areaId'))? $this->input->post('areaId'): NULL, // cultivo
						'salario_diario'       => is_numeric($this->input->post('fsalario_diario'))? $this->input->post('fsalario_diario'): 0,
            'infonavit'            => is_numeric($this->input->post('finfonavit'))? $this->input->post('finfonavit'): 0,
						'fondo_ahorro'         => is_numeric($this->input->post('ffondo_ahorro'))? $this->input->post('ffondo_ahorro'): 0,
            'fondo_ahorro_cpi'     => $this->input->post('ffondo_ahorro_cpi'),
						'salario_diario_real'  => is_numeric($this->input->post('fsalario_diario_real'))? $this->input->post('fsalario_diario_real'): 0,
						'esta_asegurado'       => $this->input->post('festa_asegurado')=='t'?'t':'f',
						'regimen_contratacion' => $this->input->post('fregimen_contratacion'),
						'rfc'                  => mb_strtoupper($this->input->post('frfc'), 'utf-8'),

            'cuenta_banco'         => trim($this->input->post('dcuenta_banco'))?$this->input->post('dcuenta_banco'): '',
            'no_proveedor_banorte' => trim($this->input->post('dno_proveedor_banorte'))?$this->input->post('dno_proveedor_banorte'): '',
            'no_seguro'            => trim($this->input->post('dno_seguro'))?$this->input->post('dno_seguro'): '',
            'user_nomina'          => trim($this->input->post('duser_nomina'))?$this->input->post('duser_nomina'): 'f',
            'id_departamente'      => $this->input->post('fdepartamente')!==false? $this->input->post('fdepartamente'): NULL,
            'de_rancho'            => trim($this->input->post('de_rancho'))?$this->input->post('de_rancho'): 'n',
            'no_empleado'          => trim($this->input->post('dno_trabajador'))? intval($this->input->post('dno_trabajador')): 0,
            'no_checador'          => trim($this->input->post('dno_checador'))? intval($this->input->post('dno_checador')): NULL,

            'tipo_contrato'        => trim($this->input->post('tipo_contrato'))? $this->input->post('tipo_contrato'): NULL,
            // 'tipo_regimen'      => trim($this->input->post('tipo_regimen'))? $this->input->post('tipo_regimen'): NULL,
            'tipo_jornada'         => trim($this->input->post('tipo_jornada'))? $this->input->post('tipo_jornada'): NULL,
            'riesgo_puesto'        => trim($this->input->post('riesgo_puesto'))? $this->input->post('riesgo_puesto'): NULL,
            'p_alimenticia'        => trim($this->input->post('dp_alimenticia'))? $this->input->post('dp_alimenticia'): 0,
            'fonacot'              => trim($this->input->post('dinfonacot'))? $this->input->post('dinfonacot'): 0,

            'fecha_contrato'       => ($this->input->post('ffecha_contrato')!=''? $this->input->post('ffecha_contrato'): NULL)
					);
      if($this->input->post('fbanco') != '')
        $data['banco'] = $this->input->post('fbanco');

			$data_privilegios = $this->input->post('dprivilegios');
		}

    if ($data['password'] == '')
      unset($data['password']);

    if (count($data) > 0)
    {
      $this->load->model('usuario_historial_model');
      $this->usuario_historial_model->setIdUsuario($id_usuario);

      $camposHistorial = array(
        array('evento' => 'Cambio de Salario Diario', 'campo' => 'salario_diario', 'valor_nuevo' => $data['salario_diario']),
        array('evento' => 'Cambio de Salario Diario Real', 'campo' => 'salario_diario_real', 'valor_nuevo' => $data['salario_diario_real']),
        array('evento' => 'Cambio de Empresa', 'campo' => 'id_empresa', 'valor_nuevo' => $data['id_empresa']),
        array('evento' => 'Fecha de salida', 'campo' => 'fecha_salida', 'valor_nuevo' => $data['fecha_salida'], 'date' => true),
        array('evento' => 'Fecha de entrada', 'campo' => 'fecha_entrada', 'valor_nuevo' => $data['fecha_entrada'], 'date' => true),
      );

      $this->usuario_historial_model->make($camposHistorial);
    }

		$this->db->update('usuarios', $data, array('id'=>$id_usuario));

		//privilegios
    $this->updatePrivilegios($data_privilegios, $id_usuario, $this->input->post('idEmpresa'));

		return array('error' => FALSE);
	}

  public function updatePrivilegios($data_privilegios, $id_usuario, $id_empresa)
  {
    $this->db->delete('usuarios_privilegios', array('usuario_id' => $id_usuario, 'id_empresa' => $id_empresa));
    if (is_array( $data_privilegios )) {
      $privilegios = array();
      foreach ($data_privilegios as $key => $value) {
        $privilegios[] = array('usuario_id' => $id_usuario, 'privilegio_id' => $value, 'id_empresa' => $id_empresa);
      }
      $this->db->insert_batch('usuarios_privilegios', $privilegios);
    }
  }

  public function copiarPrivilegios($datos)
  {
    $this->load->model('usuarios_model');

    $data = $this->usuarios_model->get_usuario_info($datos['usuarioId'], false, $datos['empresaId']);
    $privilegios = isset($data['privilegios']) ? $data['privilegios']: [];

    foreach ($datos['id_empresas'] as $key => $empresa) {
      $this->updatePrivilegios($privilegios, $datos['usuarioId'], $empresa);
    }

    return array('error' => FALSE);
  }

	/*
	 |	Obtiene la informacion de un usuario
	 */
	public function get_usuario_info($id_usuario=FALSE, $basic_info=FALSE, $empresa_id=0)
	{
		$id_usuario = ($id_usuario==false)? $_GET['id']: $id_usuario;

		$sql_res = $this->db->select("u.id, u.id AS no_empleado, u.nombre, u.usuario, u.email, u.tipo, u.status,
						u.apellido_paterno, u.apellido_materno, u.calle, u.numero, u.colonia, u.municipio, u.estado, u.cp,
						Date(u.fecha_nacimiento) AS fecha_nacimiento, Date(u.fecha_entrada) AS fecha_entrada,
						Date(u.fecha_salida) AS fecha_salida, u.nacionalidad, u.estado_civil, u.sexo, u.cuenta_cpi,
						e.id_empresa, e.nombre_fiscal, u.id_puesto, u.salario_diario, u.infonavit, u.fondo_ahorro, u.fondo_ahorro_cpi, u.salario_diario_real,
						u.esta_asegurado, u.regimen_contratacion, u.curp, u.rfc, u.cuenta_banco, u.banco, u.user_nomina, u.no_seguro,
						u.id_departamente, e.dia_inicia_semana, DATE(u.fecha_imss) as fecha_imss, ep.nombre AS puesto,
            u.tipo_contrato, u.tipo_jornada, u.riesgo_puesto, u.no_checador, u.id_area, u.telefono, u.fecha_contrato,
            u.no_proveedor_banorte, u.p_alimenticia, u.fonacot, u.registro_patronal" )
 												->from("usuarios u")
 												->join("empresas e", "e.id_empresa = u.id_empresa", "left")
 												->join("usuarios_puestos ep", "ep.id_puesto = u.id_puesto", "left")
                        ->where("id", $id_usuario)
												->where("e.status", 't')
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
        ->where("id_empresa = {$empresa_id}")
			->get();
			if($res->num_rows() > 0){
				foreach($res->result() as $priv)
					$data['privilegios'][] = $priv->privilegio_id;
			}
			$res->free_result();

      if ($data['info'][0]->id_area > 0) {
        $this->load->model('areas_model');
        $data['cultivo'] = $this->areas_model->getAreaInfo($data['info'][0]->id_area, true)['info'];
      }
		}

		return $data;
	}

	/*
	 |	Cambia el estatus de un usuario a eliminado
	 */
	public function eliminar_usuario($id_usuario)
	{
    $this->load->model('usuario_historial_model');
    $this->usuario_historial_model->setIdUsuario($id_usuario);

    $evento = array('evento' => 'Desactivado del listado de empleados', 'valor_anterior' => null, 'valor_nuevo' => null);
    $historial = $this->usuario_historial_model->buildEvent($evento);
    $this->usuario_historial_model->guardaHistorial(array($historial));

		$this->db->update('usuarios', array('status' => 'f'), array('id' => $id_usuario));

		return TRUE;
	}

	/*
	 |	Cambia el estatus de un usuari a activo
	 */
	public function activar_usuario($id_usuario)
	{
    $fechaEntrada = date('Y-m-d H:i:s');

		$this->db->update('usuarios', array('status' => 't', 'fecha_entrada' => $fechaEntrada, 'fecha_salida' => null), array('id' => $id_usuario));

    $this->load->model('usuario_historial_model');
    $this->usuario_historial_model->setIdUsuario($id_usuario);

    $evento = array('evento' => 'Activado del listado de empleados', 'campo' => 'fecha_entrada', 'valor_nuevo' => $fechaEntrada, 'date' => true);
    $historial = $this->usuario_historial_model->buildEvent($evento);
    $this->usuario_historial_model->guardaHistorial(array($historial));

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

  public function updateSueldos($datos)
  {
    if (count($datos['id_empledo']) > 0)
    {
      $this->load->model('usuario_historial_model');

      foreach ($datos['id_empledo'] as $key => $value)
      {
        $sd = $datos['sueldo_diario'][$key];
        if ($datos['tipo'][$key] == 't') //asegurados
          $sd = number_format($datos['sueldo_diario'][$key] / $datos['factor_integracion'][$key], 4, '.', '');

        $this->usuario_historial_model->setIdUsuario($value);

        $camposHistorial = array(
          array('evento' => 'Cambio de Salario Diario', 'campo' => 'salario_diario', 'valor_nuevo' => $sd),
          array('evento' => 'Cambio de Salario Diario Real', 'campo' => 'salario_diario_real', 'valor_nuevo' => $datos['sueldo_real'][$key]),
        );

        $this->usuario_historial_model->make($camposHistorial);

        $this->db->update('usuarios', array(
          'salario_diario' => $sd,
          'salario_diario_real' => $datos['sueldo_real'][$key],
          ), "id = {$value}");
      }
    }
    return true;
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

  public function getEmpresasPermiso($tipo=null)
  {
    if ($this->session->userdata('selempresa') == false) {
      $this->load->model('empresas_model');
      $emp = $this->empresas_model->getDefaultEmpresa();
      $this->session->set_userdata('selempresa', $emp->id_empresa);
    }

    $result = [];
    if ($this->session->userdata('id_usuario') > 0) {
      $result = $this->db->query("SELECT e.id_empresa, e.nombre_fiscal
        FROM empresas e INNER JOIN usuarios_privilegios up ON e.id_empresa = up.id_empresa
        WHERE e.status = 't' AND up.usuario_id = ".$this->session->userdata('id_usuario')."
        GROUP BY e.id_empresa");
      $result = $result->result();
    }

    if ($tipo == 'ids') {
      $ids = [];
      foreach ($result as $key => $value) {
        $ids[] = $value->id_empresa;
      }
      return $ids;
    }

    return $result;
  }

  public function changeEmpresaSel($empresa)
  {
    $this->session->set_userdata('selempresa', $empresa);
    return true;
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
          'id_usuario' => $fun_res[0]->id,
          'usuario'    => $fun_res[0]->usuario,
          'nombre'     => $fun_res[0]->nombre,
          'email'      => $fun_res[0]->email,
          'tipo'       => $fun_res[0]->tipo,
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
    $sql = "(
        lower(nombre || ' ' || apellido_paterno || ' ' || apellido_materno) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'
      )";
    if (is_numeric($this->input->get('term'))) {
      $sql = "id = {$_GET['term']}";
    }

    if($this->input->get('empleados')!='')
      $sql .= " AND user_nomina = 't'";
    if($this->input->get('did_empresa')!='')
      $sql .= " AND id_empresa = ".$this->input->get('did_empresa');
    if($this->input->get('only_usuario')!='')
      $sql .= " AND usuario IS NOT NULL";
    if($this->input->get('status')!='') {
      $status = $this->input->get('status')==='all'? '': $this->input->get('status');
      if (!empty($status)) {
        $sql .= " AND status = '{$status}'";
      }
    } else {
      $sql .= " AND status = 't'";
    }

    $res = $this->db->query(
        "SELECT id, nombre, usuario, apellido_paterno, apellido_materno, salario_diario_real, salario_diario,
                DATE(fecha_entrada) as fecha_entrada, DATE(fecha_salida) as fecha_salida, esta_asegurado
        FROM usuarios
        WHERE
          {$sql}
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


  public function getPercDeducPdf($datos)
  {
    $this->load->model('empresas_model');
    $this->load->model('nomina_fiscal_model');

    if (!isset($datos['did_empresa']))
    {
      $datos['did_empresa'] = $this->empresas_model->getDefaultEmpresa()->id_empresa;
      if ($datos['did_empresa'] !== '')
        $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $datos['did_empresa'])->get()->row()->dia_inicia_semana;
      else
        $dia = '4';
      $datos['anio'] = isset($datos['anio'])? $datos['anio']: date("Y");
      $semanasDelAno = $this->nomina_fiscal_model->semanasDelAno($dia, $datos['anio']);
      $datos['fsemana1'] = $semanasDelAno[0]['semana'];
      $datos['fsemana2'] = $semanasDelAno[count($semanasDelAno)-1]['semana'];
    }
    $empresa = $this->empresas_model->getInfoEmpresa($datos['did_empresa']);

    $ids_empleados = '0';
    if(isset($datos['ids_empleados']) && count($datos['ids_empleados']) > 0)
      $ids_empleados = implode(',', $datos['ids_empleados']);

    $data = $this->db->query("SELECT u.id, (u.apellido_paterno || ' ' || u.apellido_materno || ' ' || u.nombre) AS nombre, Sum(nf.dias_trabajados) AS dias_trabajados, Sum(nf.subsidio) AS subsidio,
          Sum(nf.sueldo_semanal) AS sueldo_semanal, Sum(nf.subsidio_pagado) AS subsidio_pagado, Sum(nf.vacaciones) AS vacaciones,
          Sum(nf.prima_vacacional_grabable) AS prima_vacacional_grabable, Sum(nf.prima_vacacional_exento) AS prima_vacacional_exento,
          Sum(nf.prima_vacacional) AS prima_vacacional, Sum(nf.aguinaldo_grabable) AS aguinaldo_grabable, Sum(nf.aguinaldo_exento) AS aguinaldo_exento,
          Sum(nf.aguinaldo) AS aguinaldo, Sum(nf.total_percepcion) AS total_percepcion, Sum(nf.imss) AS imss, Sum(nf.vejez) AS vejez,
          Sum(nf.isr) AS isr, Sum(nf.infonavit) AS infonavit, Sum(nf.subsidio_cobrado) AS subsidio_cobrado, Sum(nf.prestamos) AS prestamos,
          Sum(nf.total_deduccion) AS total_deduccion, Sum(nf.horas_extras) AS horas_extras, Sum(nf.horas_extras_grabable) AS horas_extras_grabable,
          Sum(nf.horas_extras_excento) AS horas_extras_excento
        FROM nomina_fiscal AS nf INNER JOIN usuarios AS u ON u.id = nf.id_empleado
        WHERE nf.id_empresa = {$datos['did_empresa']} AND nf.anio = {$datos['anio']} AND nf.semana BETWEEN {$datos['fsemana1']} AND {$datos['fsemana2']}
          AND u.id IN({$ids_empleados})
        GROUP BY u.id, u.nombre")->result();

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->logo = $empresa['info']->logo;
    $pdf->titulo2 = "Resumen de percepciones, deducciones y obligaciones";
    $pdf->titulo3 = "Periodo Semanal {$datos['fsemana1']} al {$datos['fsemana2']} del Año {$datos['anio']}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $total_gral = array( 'percepciones' => 0, 'percepciones_grav' => 0, 'percepciones_ext' => 0, 'deducciones' => 0, 'obligaciones' => 0,
      'conceptos' => array(
          'Sueldo' => array(0, 0, 0, 0, 0, 0),
          'Hrs Extras' => array(0, 0, 0, 0, 0, 0),
          'Vacaciones' => array(0, 0, 0, 0, 0, 0),
          'Prima vacacional' => array(0, 0, 0, 0, 0, 0),
          'Subsidio' => array(0, 0, 0, 0, 0, 0),
          'Infonavit' => array(0, 0, 0, 0, 0, 0),
          'I.M.M.S.' => array(0, 0, 0, 0, 0, 0),
          'Prestamos' => array(0, 0, 0, 0, 0, 0),
          'ISR' => array(0, 0, 0, 0, 0, 0),
          'Vejez' => array(0, 0, 0, 0, 0, 0),
      ));

    $total_dep = array(  'percepciones' => 0, 'percepciones_grav' => 0, 'percepciones_ext' => 0, 'deducciones' => 0, 'obligaciones' => 0);

    $dep_tiene_empleados = true;
    $y = $pdf->GetY();
    foreach ($data as $key => $empleado)
    {
      $total_dep = array(  'percepciones' => 0, 'percepciones_grav' => 0, 'percepciones_ext' => 0, 'deducciones' => 0, 'obligaciones' => 0);
      if($dep_tiene_empleados)
      {
        $pdf->SetFont('Helvetica','', 10);
        $pdf->SetXY(6, $pdf->GetY() + 4);
        $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R', 'R', 'R'));
        $pdf->SetWidths(array(55, 25, 25, 25, 25, 25, 25));
        $pdf->Row(array('Concepto', 'Percepcion', 'Perc Grava', 'Perc Exenta', 'Perc Otros', 'Deducciones', 'Obligacion'), false, false, null, 2, 1);

        $pdf->SetFont('Helvetica','', 10);
        $pdf->SetXY(6, $pdf->GetY() - 2);
        $pdf->Cell(200, 2, "________________________________________________________________________________________________________", 0, 0, 'L', 0);
        $dep_tiene_empleados = false;
      }

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY() + 4);
      $pdf->SetAligns(array('L', 'L', 'L'));
      $pdf->SetWidths(array(15, 100, 15));
      $pdf->Row(array($empleado->id, $empleado->nombre, 'Dias: '.$empleado->dias_trabajados), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetFont('Helvetica','', 9);

      // Percepciones

      // Sueldo
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R', 'R', 'R'));
      $pdf->SetWidths(array(55, 25, 25, 25, 25, 25, 25));
      $pdf->Row(array('Sueldo', MyString::formatoNumero($empleado->sueldo_semanal, 2, '', false),
                      MyString::formatoNumero($empleado->sueldo_semanal, 2, '', false),
                      MyString::formatoNumero('0', 2, '', false),
                      '0.00', '0.00', '0.00'), false, 0, null, 1, 1);
      $total_dep['percepciones'] += $empleado->sueldo_semanal;
      $total_dep['percepciones_grav'] += $empleado->sueldo_semanal;
      $total_gral['percepciones'] += $empleado->sueldo_semanal;
      $total_gral['percepciones_grav'] += $empleado->sueldo_semanal;
      $total_gral['conceptos']['Sueldo'] = array($total_gral['conceptos']['Sueldo'][0]+$empleado->sueldo_semanal,
          $total_gral['conceptos']['Sueldo'][1]+$empleado->sueldo_semanal, $total_gral['conceptos']['Sueldo'][2],
          $total_gral['conceptos']['Sueldo'][3], $total_gral['conceptos']['Sueldo'][4], $total_gral['conceptos']['Sueldo'][5]);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      // Horas Extras
      if ($empleado->horas_extras > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row(array('Hrs Extras', MyString::formatoNumero($empleado->horas_extras, 2, '', false),
                      MyString::formatoNumero($empleado->horas_extras_grabable, 2, '', false),
                      MyString::formatoNumero($empleado->horas_extras_excento, 2, '', false),
                      '0.00', '0.00', '0.00'), false, 0, null, 1, 1);
        $total_dep['percepciones'] += $empleado->horas_extras;
        $total_dep['percepciones_grav'] += $empleado->horas_extras_grabable;
        $total_dep['percepciones_ext'] += $empleado->horas_extras_excento;
        $total_gral['percepciones'] += $empleado->horas_extras;
        $total_gral['percepciones_grav'] += $empleado->horas_extras_grabable;
        $total_gral['percepciones_ext'] += $empleado->horas_extras_excento;
        $total_gral['conceptos']['Hrs Extras'] = array($total_gral['conceptos']['Hrs Extras'][0]+$empleado->horas_extras,
          $total_gral['conceptos']['Hrs Extras'][1]+$empleado->horas_extras_grabable, $total_gral['conceptos']['Hrs Extras'][2]+$empleado->horas_extras_excento,
          $total_gral['conceptos']['Hrs Extras'][3], $total_gral['conceptos']['Hrs Extras'][4], $total_gral['conceptos']['Hrs Extras'][5]);
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();
      }

      // Vacaciones y prima vacacional
      if ($empleado->vacaciones > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row(array('Vacaciones', MyString::formatoNumero($empleado->vacaciones, 2, '', false),
                      MyString::formatoNumero($empleado->vacaciones, 2, '', false),
                      MyString::formatoNumero('0', 2, '', false),
                      '0.00', '0.00', '0.00'), false, 0, null, 1, 1);
        $total_dep['percepciones'] += $empleado->vacaciones;
        $total_dep['percepciones_grav'] += $empleado->vacaciones;
        $total_gral['percepciones'] += $empleado->vacaciones;
        $total_gral['percepciones_grav'] += $empleado->vacaciones;
        $total_gral['conceptos']['Vacaciones'] = array($total_gral['conceptos']['Vacaciones'][0]+$empleado->vacaciones,
          $total_gral['conceptos']['Vacaciones'][1]+$empleado->vacaciones, $total_gral['conceptos']['Vacaciones'][2],
          $total_gral['conceptos']['Vacaciones'][3], $total_gral['conceptos']['Vacaciones'][4], $total_gral['conceptos']['Vacaciones'][5]);
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row(array('Prima vacacional', MyString::formatoNumero($empleado->prima_vacacional, 2, '', false),
                      MyString::formatoNumero($empleado->prima_vacacional_grabable, 2, '', false),
                      MyString::formatoNumero($empleado->prima_vacacional_exento, 2, '', false),
                      '0.00', '0.00', '0.00'), false, 0, null, 1, 1);
        $total_dep['percepciones']       += $empleado->prima_vacacional;
        $total_dep['percepciones_grav']  += $empleado->prima_vacacional_grabable;
        $total_dep['percepciones_ext']   += $empleado->prima_vacacional_exento;
        $total_gral['percepciones']      += $empleado->prima_vacacional;
        $total_gral['percepciones_grav'] += $empleado->prima_vacacional_grabable;
        $total_gral['percepciones_ext']  += $empleado->prima_vacacional_exento;
        $total_gral['conceptos']['Prima vacacional'] = array($total_gral['conceptos']['Prima vacacional'][0]+$empleado->prima_vacacional,
          $total_gral['conceptos']['Prima vacacional'][1]+$empleado->prima_vacacional_grabable, $total_gral['conceptos']['Prima vacacional'][2]+$empleado->prima_vacacional_exento,
          $total_gral['conceptos']['Prima vacacional'][3], $total_gral['conceptos']['Prima vacacional'][4], $total_gral['conceptos']['Prima vacacional'][5]);
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();
      }

      // Subsidio
      if ($empleado->subsidio > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row(array('Subsidio', '0.00', '0.00', '0.00',
                      '0.00', MyString::formatoNumero('-'.$empleado->subsidio, 2, '', false), '0.00'), false, 0, null, 1, 1);
        $total_dep['deducciones']       += $empleado->subsidio*-1;
        $total_gral['deducciones']      += $empleado->subsidio*-1;
        $total_gral['conceptos']['Subsidio'] = array($total_gral['conceptos']['Subsidio'][0],
          $total_gral['conceptos']['Subsidio'][1], $total_gral['conceptos']['Subsidio'][2],
          $total_gral['conceptos']['Subsidio'][3], ($total_gral['conceptos']['Subsidio'][4]+$empleado->subsidio*-1), $total_gral['conceptos']['Subsidio'][5]);
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();
      }

      // PTU
      // if ($empleado->ptu > 0)
      // {
      //   $pdf->SetXY(6, $pdf->GetY());
      //   $pdf->Row(array('PTU', MyString::formatoNumero($empleado->ptu, 2, '', false),
      //                 MyString::formatoNumero($empleado->ptu_grabable, 2, '', false),
      //                 MyString::formatoNumero($empleado->ptu_exento, 2, '', false),
      //                 '0.00', '0.00', '0.00'), false, 0, null, 1, 1);
      //   $total_dep['percepciones']       += $empleado->ptu;
      //   $total_dep['percepciones_grav']  += $empleado->ptu_grabable;
      //   $total_dep['percepciones_ext']   += $empleado->ptu_exento;
      //   $total_gral['percepciones']      += $empleado->ptu;
      //   $total_gral['percepciones_grav'] += $empleado->ptu_grabable;
      //   $total_gral['percepciones_ext']  += $empleado->ptu_exento;
      //   $total_gral['conceptos']['PTU'] = array($total_gral['conceptos']['PTU'][0]+$empleado->ptu,
          // $total_gral['conceptos']['PTU'][1]+$empleado->ptu_grabable, $total_gral['conceptos']['PTU'][2]+$empleado->ptu_exento,
          // $total_gral['conceptos']['PTU'][3], $total_gral['conceptos']['PTU'][4], $total_gral['conceptos']['PTU'][5]);
      //   if($pdf->GetY() >= $pdf->limiteY)
      //   {
      //     $pdf->AddPage();
      //     $y2 = $pdf->GetY();
      //   }
      // }

      // Aguinaldo
      // if ($empleado->nomina_fiscal_aguinaldo > 0)
      // {
      //   $pdf->SetXY(6, $pdf->GetY());
      //   $pdf->Row(array('Aguinaldo', MyString::formatoNumero($empleado->aguinaldo, 2, '', false),
      //                 MyString::formatoNumero($empleado->aguinaldo_grabable, 2, '', false),
      //                 MyString::formatoNumero($empleado->aguinaldo_exento, 2, '', false),
      //                 '0.00', '0.00', '0.00'), false, 0, null, 1, 1);
      //   $total_dep['percepciones']       += $empleado->aguinaldo;
      //   $total_dep['percepciones_grav']  += $empleado->aguinaldo_grabable;
      //   $total_dep['percepciones_ext']   += $empleado->aguinaldo_exento;
      //   $total_gral['percepciones']      += $empleado->aguinaldo;
      //   $total_gral['percepciones_grav'] += $empleado->aguinaldo_grabable;
      //   $total_gral['percepciones_ext']  += $empleado->aguinaldo_exento;
      //   $total_gral['conceptos']['Aguinaldo'] = array($total_gral['conceptos']['Aguinaldo'][0]+$empleado->aguinaldo,
          // $total_gral['conceptos']['Aguinaldo'][1]+$empleado->aguinaldo_grabable, $total_gral['conceptos']['Aguinaldo'][2]+$empleado->aguinaldo_exento,
          // $total_gral['conceptos']['Aguinaldo'][3], $total_gral['conceptos']['Aguinaldo'][4], $total_gral['conceptos']['Aguinaldo'][5]);
      //   if($pdf->GetY() >= $pdf->limiteY)
      //   {
      //     $pdf->AddPage();
      //     $y2 = $pdf->GetY();
      //   }
      // }

      // Deducciones
      $pdf->SetFont('Helvetica','', 9);

      if ($empleado->infonavit > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row(array('Infonavit', '0.00', '0.00', '0.00',
                      '0.00', MyString::formatoNumero($empleado->infonavit, 2, '', false), '0.00'), false, 0, null, 1, 1);
        $total_dep['deducciones']       += $empleado->infonavit;
        $total_gral['deducciones']      += $empleado->infonavit;
        $total_gral['conceptos']['Infonavit'] = array($total_gral['conceptos']['Infonavit'][0],
          $total_gral['conceptos']['Infonavit'][1], $total_gral['conceptos']['Infonavit'][2],
          $total_gral['conceptos']['Infonavit'][3], $total_gral['conceptos']['Infonavit'][4]+$empleado->infonavit, $total_gral['conceptos']['Infonavit'][5]);
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();
      }
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row(array('I.M.M.S.', '0.00', '0.00', '0.00',
                      '0.00', MyString::formatoNumero($empleado->imss, 2, '', false), '0.00'), false, 0, null, 1, 1);
        $total_dep['deducciones']       += $empleado->imss;
        $total_gral['deducciones']      += $empleado->imss;
        $total_gral['conceptos']['I.M.M.S.'] = array($total_gral['conceptos']['I.M.M.S.'][0],
          $total_gral['conceptos']['I.M.M.S.'][1], $total_gral['conceptos']['I.M.M.S.'][2],
          $total_gral['conceptos']['I.M.M.S.'][3], $total_gral['conceptos']['I.M.M.S.'][4]+$empleado->imss, $total_gral['conceptos']['I.M.M.S.'][5]);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      if ($empleado->prestamos > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row(array('Prestamos', '0.00', '0.00', '0.00',
                      '0.00', MyString::formatoNumero($empleado->prestamos, 2, '', false), '0.00'), false, 0, null, 1, 1);
        $total_dep['deducciones']       += $empleado->prestamos;
        $total_gral['deducciones']      += $empleado->prestamos;
        $total_gral['conceptos']['Prestamos'] = array($total_gral['conceptos']['Prestamos'][0],
          $total_gral['conceptos']['Prestamos'][1], $total_gral['conceptos']['Prestamos'][2],
          $total_gral['conceptos']['Prestamos'][3], $total_gral['conceptos']['Prestamos'][4]+$empleado->prestamos, $total_gral['conceptos']['Prestamos'][5]);
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();
      }

      if ($empleado->isr > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row(array('ISR', '0.00', '0.00', '0.00',
                      '0.00', MyString::formatoNumero($empleado->isr, 2, '', false), '0.00'), false, 0, null, 1, 1);
        $total_dep['deducciones']       += $empleado->isr;
        $total_gral['deducciones']      += $empleado->isr;
        $total_gral['conceptos']['ISR'] = array($total_gral['conceptos']['ISR'][0],
          $total_gral['conceptos']['ISR'][1], $total_gral['conceptos']['ISR'][2],
          $total_gral['conceptos']['ISR'][3], $total_gral['conceptos']['ISR'][4]+$empleado->isr, $total_gral['conceptos']['ISR'][5]);
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();
      }

      if ($empleado->vejez > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row(array('Vejez', '0.00', '0.00', '0.00',
                      '0.00', MyString::formatoNumero($empleado->vejez, 2, '', false), '0.00'), false, 0, null, 1, 1);
        $total_dep['deducciones']       += $empleado->vejez;
        $total_gral['deducciones']      += $empleado->vejez;
        $total_gral['conceptos']['Vejez'] = array($total_gral['conceptos']['Vejez'][0],
          $total_gral['conceptos']['Vejez'][1], $total_gral['conceptos']['Vejez'][2],
          $total_gral['conceptos']['Vejez'][3], $total_gral['conceptos']['Vejez'][4]+$empleado->vejez, $total_gral['conceptos']['Vejez'][5]);
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();
      }

      $pdf->SetFont('Helvetica','', 10);
      $pdf->SetXY(6, $pdf->GetY()-1);
      $pdf->Cell(200, 2, "________________________________________________________________________________________________________", 0, 0, 'L', 0);
      $pdf->SetXY(6, $pdf->GetY()+2);
      $pdf->Row(array('Total', MyString::formatoNumero($total_dep['percepciones'], 2, '', false),
                MyString::formatoNumero($total_dep['percepciones_grav'], 2, '', false),
                MyString::formatoNumero($total_dep['percepciones_ext'], 2, '', false),
                MyString::formatoNumero('0', 2, '', false),
                MyString::formatoNumero($total_dep['deducciones'], 2, '', false),
                MyString::formatoNumero($total_dep['obligaciones'], 2, '', false)), false, 0, null, 1, 1);
    }

    if ($total_gral['percepciones'] > 0 ||
        $total_gral['percepciones_grav'] > 0 ||
        $total_gral['percepciones_ext'] > 0 ||
        $total_gral['deducciones'] > 0 ||
        $total_gral['obligaciones'] > 0)
    {
      $pdf->SetFont('Helvetica','B', 10);
      $pdf->SetXY(6, $pdf->GetY()+3);
      $pdf->Cell(200, 2, "Total General ____________________________________________________________________________________________", 0, 0, 'L', 0);
      $pdf->SetXY(6, $pdf->GetY()+2);
      $pdf->SetFont('Helvetica','', 9);
      foreach ($total_gral['conceptos'] as $key => $value)
      {
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();
        $value[0] = MyString::formatoNumero($value[0], 2, '', false);
        $value[1] = MyString::formatoNumero($value[1], 2, '', false);
        $value[2] = MyString::formatoNumero($value[2], 2, '', false);
        $value[3] = MyString::formatoNumero($value[3], 2, '', false);
        $value[4] = MyString::formatoNumero($value[4], 2, '', false);
        $value = array_merge(array($key), $value);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row($value, false, 0, null, 1, 1);
      }
      $pdf->SetFont('Helvetica','', 10);
      $pdf->SetXY(6, $pdf->GetY()+1);
      $pdf->Cell(200, 2, "________________________________________________________________________________________________________", 0, 0, 'L', 0);
      $pdf->SetXY(6, $pdf->GetY()+2);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->Row(array('Total General', MyString::formatoNumero($total_gral['percepciones'], 2, '', false),
                MyString::formatoNumero($total_gral['percepciones_grav'], 2, '', false),
                MyString::formatoNumero($total_gral['percepciones_ext'], 2, '', false),
                MyString::formatoNumero('0', 2, '', false),
                MyString::formatoNumero($total_gral['deducciones'], 2, '', false),
                MyString::formatoNumero($total_gral['obligaciones'], 2, '', false)), false, 0, null, 1, 1);
    }
    $pdf->Output('Nomina.pdf', 'I');
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */