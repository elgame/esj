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
			$this->info_empleado                         = $this->usuarios_model->get_usuario_info($this->session->userdata('id_usuario'), true);

			$this->{$method}();
		}else
			$this->{'login'}();
	}

	public function index(){
		$this->carabiner->css(array(
			array('libs/jquery.treeview.css', 'screen')
		));
		$this->carabiner->js(array(
			array('libs/jquery.treeview.js'),
			array('general/msgbox.js'),
      array('panel/home.js'),
		));

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Panel de Administración'
		);

		// $gestor = @fopen("Catalogo de Cuentas.txt", "r");
		// if ($gestor) {
		// 	$idconta = 1;
		// 	$ids = array(1 => null, 2 => null, 3 => null, 4 => null);
		//     while (($bufer = fgets($gestor, 4096)) !== false) {
		//     	$nivel = trim(substr($bufer, 4, 3));
		//     	if($nivel == '1'){
		//     		$ids = array(1 => 'NULL', 2 => null, 3 => null, 4 => null);
		//     		$ids[$nivel+1] = $idconta;
		//     	}elseif ($nivel == '2') {
		//     		$ids[$nivel+1] = $idconta;
		//     	}elseif ($nivel == '3') {
		//     		$ids[$nivel+1] = $idconta;
		//     	}else
		//     		$nivel = 4;

		//     	$bufer = utf8_encode($bufer);
		//     	echo "INSERT INTO cuentas_contpaq (id_padre, nivel, cuenta, nombre, tipo) VALUES (".$ids[$nivel].", '".$nivel."', '".trim(str_replace("-", "", substr($bufer, 7, 10)))."', '".trim(substr($bufer, 23, 21))."', '".trim(substr($bufer, 46, 19))."' );\n";
		//     	$idconta++;
		//     }
		//     if (!feof($gestor)) {
		//         echo "Error: fallo inesperado de fgets()\n";
		//     }
		//     fclose($gestor);
		// }
		// $departamento = array('ADMINISTRACION' => 1, 'EMPAQUE' => 2, 'MANTENIMIENTO INDUSTRIAL' => 3, 'RANCHOS' => 4);
		// $puestos = array('AUXILIAR CONTABLE' => 5, 'RECEPCION DE FRUTA' => 6, 'GERENTE GENERAL' => 7, 'GERENTE ADMINISTRATIVO' => 8, 'RECEPCIONISTA' => 9, 'CONTADORA' => 10, 'AUXILIAR ADMINISTRATIVO' => 11, 'MENSAJERO' => 12, 'ASISTENTE INOCUIDAD' => 13, 'EMPACADOR' => 14, 'CAJONERA' => 15, 'ALMACENISTA' => 16, 'MONTACARGUISTA' => 17, 'CONTROL DE PRODUCCION' => 18, 'GERENTE DE PRODUCCION' => 19, 'SELECCIONADORA' => 20, 'SUPERVISOR DE PRODUCCION' => 21, 'JEFE DE PERSONAL' => 22, 'INTENDENTE' => 23, 'VIGILANTE' => 24, 'EMPAPELADORA' => 25, 'CAJONERO' => 26, 'SUPERVISOR DE MANTENIMIENTO' => 27, 'JEFE DE PROYECTOS' => 28, 'AUXILIAR MECANICO' => 29, 'SOLDADOR' => 30, 'REGADOR' => 31, 'MAYORDOMO' => 32, 'TRACTORISTA' => 33, 'JARDINERO' => 34);
		// if (($gestor = fopen("CATALOGO DE EMPLEADOS EMPAQUE.csv", "r")) !== FALSE) {
		//     while (($datos = fgetcsv($gestor, 1000, ",")) !== FALSE) {
		//     	$datos[2] = utf8_encode($datos[2]);
		//     	$nombre = explode(" ", $datos[2]);
		//     	$appe1 = $nombre[0];
		//     	$appe2 = $nombre[1];
		//     	unset($nombre[0], $nombre[1]);
		//     	$nombre = implode(' ', $nombre);

		//     	// if(array_search($datos[3], $puestos) === false)
		//     	// 	$puestos[] = $datos[3];

		//     	echo "UPDATE usuarios SET salario_diario='{$datos[9]}', salario_diario_real='{$datos[10]}', rfc='{$datos[5]}' WHERE no_seguro = '{$datos[0]}';<br>";
		//       //   echo "INSERT INTO usuarios(nombre, apellido_paterno, apellido_materno, curp, fecha_nacimiento, fecha_entrada, nacionalidad,
		//       //   	estado_civil, id_empresa, id_puesto, esta_asegurado, regimen_contratacion, rfc, cuenta_banco, user_nomina, no_seguro, email, id_departamente)
  //   				// VALUES ('{$nombre}', '{$appe1}', '{$appe2}', '{$datos[4]}', '".date("Y-m-d", strtotime(str_replace('/', '-', $datos[6])))."',
  //   				// 	'".date("Y-m-d", strtotime(str_replace('/', '-', $datos[7])))."', 'MEXICANA', 'soltero', 1, ".(isset($puestos[$datos[3]])? $puestos[$datos[3]]: 'NULL').", 't', '2', '',
  //   				// 	'{$datos[8]}', 't', '$datos[0]', '', ".$departamento[$datos[1]].");<br><br>";
		//     }
		//     fclose($gestor);

		//     // $cont = 5;
		//     // foreach ($puestos as $key => $value)
		//     // {
		//     // 	if($value != ''){
		//     // 		echo "'{$value}' => {$cont}, ";
		//     // 		$cont++;
		//     // 	}
		//     // }
		// }

		$params['cuentas'] = '';//$this->getArbolCuenta();

		// $this->load->library('cfdi');
		// $this->cfdi->cargaDatosFiscales(1076, 'proveedores');
		// $this->cfdi->obtenSello("dasdasdasd");
		// echo $this->cfdi->obtenCertificado($this->cfdi->path_certificado, false);
		// echo $this->cfdi->obtenLlave($this->cfdi->path_key);

    $this->load->model('inventario_model');
    $this->load->model('empresas_model');
    $empresas = $this->empresas_model->getEmpresas();
    foreach ($empresas['empresas'] as $keye => $empresa)
    {
      $_GET['did_empresa'] = $empresa->id_empresa;
      $productos = $this->inventario_model->getEPUData();
      $empresa->productos = array();
      foreach ($productos as $key => $value)
      {
        if($value->stock_min > ($value->saldo_anterior + $value->entradas - $value->salidas) )
          $empresa->productos[] = $value;
      }
      if(count($empresa->productos) == 0)
        unset($empresas['empresas'][$keye]);
    }
    $params['empresas'] = $empresas['empresas'];

    $this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/general/home', $params);
		$this->load->view('panel/footer');
	}

	public function getArbolCuenta($id_submenu='NULL', $firs=true, $tipo=null, $showp=false){
		$txt = "";
		$bande = true;

		$res = $this->db
			->select("p.id_cuenta, p.nivel, p.id_padre, p.cuenta, p.nombre, p.tipo")
			->from('cuentas_contpaq AS p')
			->where($id_submenu=='NULL'? "p.id_padre is ".$id_submenu."": "p.id_padre = ".$id_submenu."")
			->order_by('p.nombre', 'asc')
		->get();
		$txt .= $firs? '<ul class="treeview">': '<ul>';
		foreach($res->result() as $data){
			$res1 = $this->db
				->select('Count(p.id_cuenta) AS num')
				->from('cuentas_contpaq AS p')
				->where("p.id_padre = '".$data->id_cuenta."'")
			->get();
			$data1 = $res1->row();

			if($tipo != null && !is_array($tipo)){
				$set_nombre = 'dprivilegios';
				$set_val = set_radio($set_nombre, $data->id_cuenta, ($tipo==$data->id_cuenta? true: false));
				$tipo_obj = 'radio';
			}else{
				$set_nombre = 'dprivilegios[]';
				if(is_array($tipo))
					$set_val = set_checkbox($set_nombre, $data->id_cuenta,
							(array_search($data->id_cuenta, $tipo)!==false? true: false) );
				else
					$set_val = set_checkbox($set_nombre, $data->id_cuenta);
				$tipo_obj = 'checkbox';
			}

			if($bande==true && $firs==true && $showp==true){
				$txt .= '<li><label style="font-size:11px;">
				<input type="'.$tipo_obj.'" name="'.$set_nombre.'" data-uniform="false" value="0" '.$set_val.($data->id_padre==0?  ' checked': '').'> Padre</label>
				</li>';
				$bande = false;
			}

			if($data1->num > 0){
				$txt .= '<li><label style="font-size:11px;">
					<input type="'.$tipo_obj.'" name="'.$set_nombre.'" data-uniform="false" value="'.$data->id_cuenta.'" '.$set_val.'> '.$data->nombre.' - '.$data->cuenta.'</label>
					'.$this->getArbolCuenta($data->id_cuenta, false, $tipo).'
				</li>';
			}else{
				$txt .= '<li><label style="font-size:11px;">
					<input type="'.$tipo_obj.'" name="'.$set_nombre.'" data-uniform="false" value="'.$data->id_cuenta.'" '.$set_val.'> '.$data->nombre.' - '.$data->cuenta.'</label>
				</li>';
			}
			$res1->free_result();
		}
		$txt .= '</ul>';
		$res->free_result();

		return $txt;
	}



	/**
	 * CONFIGURACIONES DE NOMINA IMPUESTOS Y ESO
	 * @return [type] [description]
	 */
	public function configuraciones()
	{
		$this->carabiner->css(array(
			array('libs/jquery.uniform.css', 'screen'),
		));
		$this->carabiner->js(array(
			array('libs/jquery.uniform.min.js'),
			array('libs/jquery.numeric.js'),
		));

		$this->load->model('configuraciones_model');

		$params['info_empleado'] = $this->info_empleado['info']; //info empleado
		$params['seo'] = array(
			'titulo' => 'Modificar usuario'
		);

		$this->config_configs('modificar');
		if ($this->form_validation->run() == FALSE)
		{
			$params['frm_errors'] = $this->showMsgs(2, preg_replace("[\n|\r|\n\r]", '', validation_errors()));
		}
		else
		{
			$res_mdl = $this->configuraciones_model->modificarConfiguracion();

			if($res_mdl['error'] == FALSE)
				redirect(base_url('panel/home/configuraciones/?'.String::getVarsLink(array('msg', 'id')).'&msg=4'));
		}

		$params['data'] = $this->configuraciones_model->getConfiguraciones();

		if (isset($_GET['msg']))
			$params['frm_errors'] = $this->showMsgs($_GET['msg']);

		$this->load->view('panel/header', $params);
		$this->load->view('panel/general/menu', $params);
		$this->load->view('panel/configuraciones/modificar', $params);
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

  public function test()
  {
/*
    $xml = '<?xml version="1.0" encoding="UTF-8"?> <cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/3" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.sat.gob.mx/cfd/3 http://www.sat.gob.mx/sitio_internet/cfd/3/cfdv32.xsd" version="3.2" serie="R" folio="301" fecha="2013-08-01T11:49:56" sello="OIA7+ewwlVTlYuAlOBiaSl0O9XeuVjuEO8scIoEmoY5bbBcN2INObmV93WS3xppkCXSkZGfIgcT2I6j+8fMwsu4Yh3sjnQztrK95fKjpMaw3TPaNpsbaf8oeOg0jidtevfAl1VmSq+SDYi+BddNmV6foP+S8mCQFDkDa7zSU5tw=" formaDePago="Pago en una sola exhibición" noCertificado="00001000000203144869" certificado="MIIEbDCCA1SgAwIBAgIUMDAwMDEwMDAwMDAyMDMxNDQ4NjkwDQYJKoZIhvcNAQEFBQAwggGVMTgwNgYDVQQDDC9BLkMuIGRlbCBTZXJ2aWNpbyBkZSBBZG1pbmlzdHJhY2nDs24gVHJpYnV0YXJpYTEvMC0GA1UECgwmU2VydmljaW8gZGUgQWRtaW5pc3RyYWNpw7NuIFRyaWJ1dGFyaWExODA2BgNVBAsML0FkbWluaXN0cmFjacOzbiBkZSBTZWd1cmlkYWQgZGUgbGEgSW5mb3JtYWNpw7NuMSEwHwYJKoZIhvcNAQkBFhJhc2lzbmV0QHNhdC5nb2IubXgxJjAkBgNVBAkMHUF2LiBIaWRhbGdvIDc3LCBDb2wuIEd1ZXJyZXJvMQ4wDAYDVQQRDAUwNjMwMDELMAkGA1UEBhMCTVgxGTAXBgNVBAgMEERpc3RyaXRvIEZlZGVyYWwxFDASBgNVBAcMC0N1YXVodMOpbW9jMRUwEwYDVQQtEwxTQVQ5NzA3MDFOTjMxPjA8BgkqhkiG9w0BCQIML1Jlc3BvbnNhYmxlOiBDZWNpbGlhIEd1aWxsZXJtaW5hIEdhcmPDrWEgR3VlcnJhMB4XDTEzMDIyODE5MzMzNVoXDTE3MDIyODE5MzMzNVowga0xIjAgBgNVBAMTGVJPQkVSVE8gTkVWQVJFWiBET01JTkdVRVoxIjAgBgNVBCkTGVJPQkVSVE8gTkVWQVJFWiBET01JTkdVRVoxIjAgBgNVBAoTGVJPQkVSVE8gTkVWQVJFWiBET01JTkdVRVoxFjAUBgNVBC0TDU5FRFI2MjA3MTBINzYxGzAZBgNVBAUTEk5FRFI2MjA3MTBIQ0hWTUIwMDEKMAgGA1UECxMBMTCBnzANBgkqhkiG9w0BAQEFAAOBjQAwgYkCgYEA5tufImZ9dhFrBJU+n+GI7J57mBOoay/+JmqUU70RW7b6RqEsRNg0JP27qY/8R1IyTWzjsB6dupx5G1/i3WtYUBAfpGiycPnI1M5tB52KaYGcD9m6b5g5d32Npdn0sRyqGUspt06zHaL9OJU/5pV4cW9ZVFN0uEMR7ur7uOLNXqMCAwEAAaMdMBswDAYDVR0TAQH/BAIwADALBgNVHQ8EBAMCBsAwDQYJKoZIhvcNAQEFBQADggEBAAoZyfQZ+uxgejY7orFVI4uujg60OewVq7mAi83tkvJIeY/Cghw3gIjN3H8cguZVEUrgd1Y5qg2+HHN0QJxbY10CPPlOgv/T0oJPTGj/l0IBSqq/JXd80DnHgi0IeoP62liAlWf/ikS4ugH1IzbeAjYWDmPMjnsS2uyLK3LtuEX6Goa/PvkIihJZs8qmZ4/UuNRfhD7zUeruVK1xoh1fqA636ozwCxgpeo4vOaU+QFQRyavjrmOqMa2zYunok2GOsZOZURRmdxMg9hZx4UwvSnBXZoPjX2AKxiWb0AmQF/HMtwElDXfpDFTYf/FWv+zTJiDMQpuzteteFfEOWNJQErs=" condicionesDePago="co" subTotal="396" total="459.36" tipoDeComprobante="ingreso" metodoDePago="efectivo" LugarExpedicion="Michoacán, Michoacán" NumCtaPago="No identificado" ><cfdi:Emisor rfc="NEDR620710H76" nombre="ROBERTO NEVAREZ DOMINGUEZ"><cfdi:DomicilioFiscal calle="Pista Aérea" noExterior="S/N" colonia="Ranchito" localidad="Ranchito" municipio="Michoacán" estado="Michoacán" pais="MEXICO" codigoPostal="60800"/><cfdi:ExpedidoEn calle="Pista Aérea" noExterior="S/N" colonia="Ranchito" localidad="Ranchito" municipio="Michoacán" estado="Michoacán" pais="MEXICO" codigoPostal="60800"/><cfdi:RegimenFiscal Regimen="Actividad empresarial, régimen general de ley" /></cfdi:Emisor><cfdi:Receptor rfc="CIFA750513NE7" nombre="AIDE CISNEROS FRAUSTO"><cfdi:Domicilio calle="BLVD. FEDERICO BENITES" noExterior="6400" colonia="LOC. 23 FRACC. YAMILLE" pais="MEXICO" /></cfdi:Receptor><cfdi:Conceptos><cfdi:Concepto cantidad="33" unidad="Kg" descripcion="KGS. DE LIMON AMARILLO INDUSTRIAL" valorUnitario="12" importe="396"></cfdi:Concepto></cfdi:Conceptos><cfdi:Impuestos totalImpuestosRetenidos="0" totalImpuestosTrasladados="63.36"><cfdi:Retenciones><cfdi:Retencion impuesto="IVA" importe="0"/></cfdi:Retenciones><cfdi:Traslados><cfdi:Traslado impuesto="IVA" tasa="16" importe="63.36"/></cfdi:Traslados></cfdi:Impuestos></cfdi:Comprobante>';
*/
    // $xml = 'asdasdasdasd';

    // $this->load->library('facturartebarato_api');

    // $response = $this->facturartebarato_api->timbrar($xml);

    // $decode = base64_decode("PD94bWwgdmVyc2lvbj0iMS4wIiBlbmNvZGluZz0iVVRGLTgiPz4gPGNmZGk6Q29tcHJvYmFudGUgeG1sbnM6Y2ZkaT0iaHR0cDovL3d3dy5zYXQuZ29iLm14L2NmZC8zIiB4bWxuczp4c2k9Imh0dHA6Ly93d3cudzMub3JnLzIwMDEvWE1MU2NoZW1hLWluc3RhbmNlIiB4c2k6c2NoZW1hTG9jYXRpb249Imh0dHA6Ly93d3cuc2F0LmdvYi5teC9jZmQvMyBodHRwOi8vd3d3LnNhdC5nb2IubXgvc2l0aW9faW50ZXJuZXQvY2ZkLzMvY2ZkdjMyLnhzZCIgdmVyc2lvbj0iMy4yIiBzZXJpZT0iUiIgZm9saW89IjMzMSIgZmVjaGE9IjIwMTMtMDgtMDVUMTE6MDE6MjIiIHNlbGxvPSJYSmIybFpRcDZEaU1wc2tCZ21JaThVSXhHTjdlWFFSNFVaRFF2c0JqcHpnTmJzOU5OWkZGMnUzdWxvWWd5bVhJZ25yMEZWcVVwM1NzSFRCaUdLUW9yNWZJN3RNYldrZmtnOUJCbmhsb0RFbmZHQ0tySFZWUURhRGJOU0E3NVZwWFlWZWxVQmcvMkpxeUdvUkxoMHkvZHlKSzN3M0tlTFFtN0o3Y0JtTkcwR0U9IiBmb3JtYURlUGFnbz0iUGFnbyBlbiB1bmEgc29sYSBleGhpYmljacOzbiIgbm9DZXJ0aWZpY2Fkbz0iMDAwMDEwMDAwMDAyMDMxNDQ4NjkiIGNlcnRpZmljYWRvPSJNSUlFYkRDQ0ExU2dBd0lCQWdJVU1EQXdNREV3TURBd01EQXlNRE14TkRRNE5qa3dEUVlKS29aSWh2Y05BUUVGQlFBd2dnR1ZNVGd3TmdZRFZRUUREQzlCTGtNdUlHUmxiQ0JUWlhKMmFXTnBieUJrWlNCQlpHMXBibWx6ZEhKaFkybkRzMjRnVkhKcFluVjBZWEpwWVRFdk1DMEdBMVVFQ2d3bVUyVnlkbWxqYVc4Z1pHVWdRV1J0YVc1cGMzUnlZV05wdzdOdUlGUnlhV0oxZEdGeWFXRXhPREEyQmdOVkJBc01MMEZrYldsdWFYTjBjbUZqYWNPemJpQmtaU0JUWldkMWNtbGtZV1FnWkdVZ2JHRWdTVzVtYjNKdFlXTnB3N051TVNFd0h3WUpLb1pJaHZjTkFRa0JGaEpoYzJsemJtVjBRSE5oZEM1bmIySXViWGd4SmpBa0JnTlZCQWtNSFVGMkxpQklhV1JoYkdkdklEYzNMQ0JEYjJ3dUlFZDFaWEp5WlhKdk1RNHdEQVlEVlFRUkRBVXdOak13TURFTE1Ba0dBMVVFQmhNQ1RWZ3hHVEFYQmdOVkJBZ01FRVJwYzNSeWFYUnZJRVpsWkdWeVlXd3hGREFTQmdOVkJBY01DME4xWVhWb2RNT3BiVzlqTVJVd0V3WURWUVF0RXd4VFFWUTVOekEzTURGT1RqTXhQakE4QmdrcWhraUc5dzBCQ1FJTUwxSmxjM0J2Ym5OaFlteGxPaUJEWldOcGJHbGhJRWQxYVd4c1pYSnRhVzVoSUVkaGNtUERyV0VnUjNWbGNuSmhNQjRYRFRFek1ESXlPREU1TXpNek5Wb1hEVEUzTURJeU9ERTVNek16TlZvd2dhMHhJakFnQmdOVkJBTVRHVkpQUWtWU1ZFOGdUa1ZXUVZKRldpQkVUMDFKVGtkVlJWb3hJakFnQmdOVkJDa1RHVkpQUWtWU1ZFOGdUa1ZXUVZKRldpQkVUMDFKVGtkVlJWb3hJakFnQmdOVkJBb1RHVkpQUWtWU1ZFOGdUa1ZXUVZKRldpQkVUMDFKVGtkVlJWb3hGakFVQmdOVkJDMFREVTVGUkZJMk1qQTNNVEJJTnpZeEd6QVpCZ05WQkFVVEVrNUZSRkkyTWpBM01UQklRMGhXVFVJd01ERUtNQWdHQTFVRUN4TUJNVENCbnpBTkJna3Foa2lHOXcwQkFRRUZBQU9CalFBd2dZa0NnWUVBNXR1ZkltWjlkaEZyQkpVK24rR0k3SjU3bUJPb2F5LytKbXFVVTcwUlc3YjZScUVzUk5nMEpQMjdxWS84UjFJeVRXempzQjZkdXB4NUcxL2kzV3RZVUJBZnBHaXljUG5JMU01dEI1MkthWUdjRDltNmI1ZzVkMzJOcGRuMHNSeXFHVXNwdDA2ekhhTDlPSlUvNXBWNGNXOVpWRk4wdUVNUjd1cjd1T0xOWHFNQ0F3RUFBYU1kTUJzd0RBWURWUjBUQVFIL0JBSXdBREFMQmdOVkhROEVCQU1DQnNBd0RRWUpLb1pJaHZjTkFRRUZCUUFEZ2dFQkFBb1p5ZlFaK3V4Z2VqWTdvckZWSTR1dWpnNjBPZXdWcTdtQWk4M3RrdkpJZVkvQ2dodzNnSWpOM0g4Y2d1WlZFVXJnZDFZNXFnMitISE4wUUp4YlkxMENQUGxPZ3YvVDBvSlBUR2ovbDBJQlNxcS9KWGQ4MERuSGdpMEllb1A2MmxpQWxXZi9pa1M0dWdIMUl6YmVBallXRG1QTWpuc1MydXlMSzNMdHVFWDZHb2EvUHZrSWloSlpzOHFtWjQvVXVOUmZoRDd6VWVydVZLMXhvaDFmcUE2MzZvendDeGdwZW80dk9hVStRRlFSeWF2anJtT3FNYTJ6WXVub2syR09zWk9aVVJSbWR4TWc5aFp4NFV3dlNuQlhab1BqWDJBS3hpV2IwQW1RRi9ITXR3RWxEWGZwREZUWWYvRld2K3pUSmlETVFwdXp0ZXRlRmZFT1dOSlFFcnM9IiBjb25kaWNpb25lc0RlUGFnbz0iY28iIHN1YlRvdGFsPSIyIiB0b3RhbD0iMiIgdGlwb0RlQ29tcHJvYmFudGU9ImluZ3Jlc28iIG1ldG9kb0RlUGFnbz0iZWZlY3Rpdm8iIEx1Z2FyRXhwZWRpY2lvbj0iTWljaG9hY8OhbiwgTWljaG9hY8OhbiIgTnVtQ3RhUGFnbz0iTm8gaWRlbnRpZmljYWRvIiA+PGNmZGk6RW1pc29yIHJmYz0iTkVEUjYyMDcxMEg3NiIgbm9tYnJlPSJST0JFUlRPIE5FVkFSRVogRE9NSU5HVUVaIj48Y2ZkaTpEb21pY2lsaW9GaXNjYWwgY2FsbGU9IlBpc3RhIEHDqXJlYSIgbm9FeHRlcmlvcj0iUy9OIiBjb2xvbmlhPSJSYW5jaGl0byIgbG9jYWxpZGFkPSJSYW5jaGl0byIgbXVuaWNpcGlvPSJNaWNob2Fjw6FuIiBlc3RhZG89Ik1pY2hvYWPDoW4iIHBhaXM9Ik1FWElDTyIgY29kaWdvUG9zdGFsPSI2MDgwMCIvPjxjZmRpOkV4cGVkaWRvRW4gY2FsbGU9IlBpc3RhIEHDqXJlYSIgbm9FeHRlcmlvcj0iUy9OIiBjb2xvbmlhPSJSYW5jaGl0byIgbG9jYWxpZGFkPSJSYW5jaGl0byIgbXVuaWNpcGlvPSJNaWNob2Fjw6FuIiBlc3RhZG89Ik1pY2hvYWPDoW4iIHBhaXM9Ik1FWElDTyIgY29kaWdvUG9zdGFsPSI2MDgwMCIvPjxjZmRpOlJlZ2ltZW5GaXNjYWwgUmVnaW1lbj0iQWN0aXZpZGFkIGVtcHJlc2FyaWFsLCByw6lnaW1lbiBnZW5lcmFsIGRlIGxleSIgLz48L2NmZGk6RW1pc29yPjxjZmRpOlJlY2VwdG9yIHJmYz0iU1VDQTc4MTEwNUc5MyIgbm9tYnJlPSJBTE1BIERFTElBIFNVQVJFWiBDUlVaIj48Y2ZkaTpEb21pY2lsaW8gY2FsbGU9IkFOR0VMIERFIExBIEdVQVJEQSIgbm9FeHRlcmlvcj0iMjAwMTAiIGNvbG9uaWE9IkJVRU5PUyBBSVJFUyBTVVIiIHBhaXM9Ik1FWElDTyIgY29kaWdvUG9zdGFsPSIyMjIwNyIvPjwvY2ZkaTpSZWNlcHRvcj48Y2ZkaTpDb25jZXB0b3M+PGNmZGk6Q29uY2VwdG8gY2FudGlkYWQ9IjEiIHVuaWRhZD0iYXNkIiBkZXNjcmlwY2lvbj0iS0dTLiBERSBMSU1PTiBBTUFSSUxMTyBJTkRVU1RSSUFMIiB2YWxvclVuaXRhcmlvPSIyIiBpbXBvcnRlPSIyIj48L2NmZGk6Q29uY2VwdG8+PC9jZmRpOkNvbmNlcHRvcz48Y2ZkaTpJbXB1ZXN0b3MgdG90YWxJbXB1ZXN0b3NSZXRlbmlkb3M9IjAiIHRvdGFsSW1wdWVzdG9zVHJhc2xhZGFkb3M9IjAiPjxjZmRpOlJldGVuY2lvbmVzPjxjZmRpOlJldGVuY2lvbiBpbXB1ZXN0bz0iSVZBIiBpbXBvcnRlPSIwIi8+PC9jZmRpOlJldGVuY2lvbmVzPjxjZmRpOlRyYXNsYWRvcz48Y2ZkaTpUcmFzbGFkbyBpbXB1ZXN0bz0iSVZBIiB0YXNhPSIwIiBpbXBvcnRlPSIwIi8+PC9jZmRpOlRyYXNsYWRvcz48L2NmZGk6SW1wdWVzdG9zPjwvY2ZkaTpDb21wcm9iYW50ZT4=");
    // $decode = str_replace('cfdi:', '', $decode);


    // $xml = simplexml_load_string($xml);
    // $is_xml = simplexml_load_string($decode);

    echo "<pre>";
      var_dump(String::formatoNumero(5.10, 5, ''), (float)1.999999);
    echo "</pre>";exit;
  }


	/**
	 * cierra la sesion del usuario
	 */
	public function logout(){
		$this->session->sess_destroy();
		redirect(base_url('panel/home'));
	}



	public function config_configs($accion='agregar')
	{
		$this->load->library('form_validation');
		$rules = array(
			array('field' => 'daguinaldo',
						'label' => 'Aguinaldo',
						'rules' => 'required|numeric'),
			array('field' => 'dprima_vacacional',
						'label' => 'Prima Vacacional',
						'rules' => 'required|numeric'),
			array('field' => 'dpuntualidad',
						'label' => 'Puntualidad',
						'rules' => 'required|numeric'),
			array('field' => 'dasistencia',
						'label' => 'Asistencia',
						'rules' => 'required|numeric'),
			array('field' => 'ddespensa',
						'label' => 'Despensa',
						'rules' => 'required|numeric'),

			array('field' => 'anio1[]',
						'label' => 'Despensa',
						'rules' => 'required|numeric'),
			array('field' => 'anio2[]',
						'label' => 'Despensa',
						'rules' => 'required|numeric'),
			array('field' => 'dias[]',
						'label' => 'Despensa',
						'rules' => 'required|numeric'),

			array('field' => 'dzona_a',
						'label' => 'Despensa',
						'rules' => 'required|numeric'),
			array('field' => 'dzona_b',
						'label' => 'Despensa',
						'rules' => 'required|numeric'),
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
				$txt = 'El usuario se agregó correctamente.';
				$icono = 'success';
				break;
			case 4:
				$txt = 'Se actualizaron correctamente.';
				$icono = 'success';
				break;
			case 5:
				$txt = 'El usuario se eliminó correctamente.';
				$icono = 'success';
				break;
			case 6:
				$txt = 'El usuario se activó correctamente.';
				$icono = 'success';
				break;
		}

		return array(
				'title' => $title,
				'msg' => $txt,
				'ico' => $icono);
	}
}

?>