<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class proveedores_model extends CI_Model {
	private $pass_finkok = 'gamaL1!l';

	function __construct()
	{
		parent::__construct();
		$this->load->model('bitacora_model');
	}

	public function getProveedores($paginados = true)
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
			$sql .= ($sql==''? 'WHERE': ' AND')." p.id_empresa='".$this->input->get('did_empresa')."'";

		if($this->input->get('ftipo_proveedor') != '' && $this->input->get('ftipo_proveedor') != 'todos')
			$sql .= ($sql==''? 'WHERE': ' AND')." p.tipo_proveedor='".$this->input->get('ftipo_proveedor')."'";

		$query = BDUtil::pagination(
				"SELECT p.id_proveedor, p.nombre_fiscal, p.calle, p.no_exterior, p.no_interior, p.colonia, p.localidad, p.municipio,
							p.telefono, p.estado, p.tipo_proveedor, p.status, e.id_empresa, e.nombre_fiscal AS empresa
				FROM proveedores p
				INNER JOIN empresas AS e ON e.id_empresa = p.id_empresa
				".$sql."
				ORDER BY p.nombre_fiscal ASC
				", $params, true);
		$res = $this->db->query($query['query']);

		$response = array(
				'proveedores'    => array(),
				'total_rows'     => $query['total_rows'],
				'items_per_page' => $params['result_items_per_page'],
				'result_page'    => $params['result_page']
		);
		if($res->num_rows() > 0){
			$response['proveedores'] = $res->result();
			foreach ($response['proveedores'] as $key => $value) {
				$response['proveedores'][$key]->direccion = $value->calle.($value->no_exterior!=''? ' '.$value->no_exterior: '')
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
	public function addProveedor($data=NULL)
	{
		//certificado
		$dcer_org   = '';
		$dcer       = '';
		$cer_caduca = '';
		$upload_res = UploadFiles::uploadFile('dcer_org');
		var_dump($upload_res);
		if($upload_res !== false && $upload_res !== 'ok'){
			$upload_res = json_decode( file_get_contents(base_url("openssl/bin/cer.php?file={$upload_res}&path=".APPPATH."CFDI/certificados_pv/")) );
			$dcer_org   = $upload_res[0];
			$dcer       = $upload_res[1];

			$this->load->library('cfdi');
			$cer_caduca = $this->cfdi->obtenFechaCertificado($dcer_org);
		}
		//llave
		$new_pass   = $this->pass_finkok;
		$dkey_path  = '';
		$upload_res = UploadFiles::uploadFile('dkey_path');
		if($upload_res !== false && $upload_res !== 'ok'){
			$upload_res = json_decode( file_get_contents(base_url("openssl/bin/key.php?newpass={$new_pass}&pass={$this->input->post('dpass')}&file={$upload_res}&path=".APPPATH."CFDI/certificados_pv/")) );
			$dkey_path  = $upload_res[0];
			$_POST['dpass'] = $new_pass;
		}

		if ($data==NULL)
		{
			$data = array(
						'nombre_fiscal'  => $this->input->post('fnombre_fiscal'),
						'calle'          => $this->input->post('fcalle'),
						'no_exterior'    => $this->input->post('fno_exterior'),
						'no_interior'    => $this->input->post('fno_interior'),
						'colonia'        => $this->input->post('fcolonia'),
						'localidad'      => $this->input->post('flocalidad'),
						'municipio'      => $this->input->post('fmunicipio'),
						'estado'         => $this->input->post('festado'),
						'cp'             => $this->input->post('fcp'),
						'telefono'       => $this->input->post('ftelefono'),
						'celular'        => $this->input->post('fcelular'),
						'email'          => $this->input->post('femail'),
						'cuenta_cpi'     => $this->input->post('fcuenta_cpi'),
						'tipo_proveedor' => $this->input->post('ftipo_proveedor'),
						'rfc'            => $this->input->post('frfc'),
						'curp'           => $this->input->post('fcurp'),
						'regimen_fiscal' => $this->input->post('dregimen_fiscal'),
						'cer_org'        => $dcer_org,
						'cer'            => $dcer,
						'key_path'       => $dkey_path,
						'pass'           => $this->input->post('dpass'),
						'cfdi_version'   => $this->input->post('dcfdi_version'),
						'condicion_pago' => $this->input->post('condicionPago'),
						'dias_credito'   => intval($this->input->post('plazoCredito')),
						'id_empresa'     => $this->input->post('did_empresa'),
						);
			if($cer_caduca != '')
				$data['cer_caduca'] = $cer_caduca;
		}

		$this->db->insert('proveedores', $data);
		$id_proveedor = $this->db->insert_id('proveedores_id_proveedor_seq');

		// Bitacora
    $this->bitacora_model->_insert('proveedores', $id_proveedor,
                                    array(':accion'    => 'el proveedor', ':seccion' => 'proveedores',
                                          ':folio'     => $data['nombre_fiscal'],
                                          ':id_empresa' => $data['id_empresa'],
                                          ':empresa'   => 'en '.$this->input->post('fempresa')));

		$this->addCuentas($id_proveedor);
    $this->saveCentrosCostos($id_proveedor);

		return array('error' => FALSE);
	}

	/**
	 * Modificar la informacion de un proveedor
	 * @param  [type] $id_proveedor [description]
	 * @param  [type] $data       [description]
	 * @return [type]             [description]
	 */
	public function updateProveedor($id_proveedor, $data=NULL)
	{
		$info = $this->getProveedorInfo($id_proveedor);

		//certificado
		$dcer_org   = (isset($info['info']->cer_org)? $info['info']->cer_org: '');
		$dcer       = (isset($info['info']->cer)? $info['info']->cer: '');
		$cer_caduca = (isset($info['info']->cer_caduca)? $info['info']->cer_caduca: '');
		$upload_res = UploadFiles::uploadFile('dcer_org');
		if($upload_res !== false && $upload_res !== 'ok'){
			if($dcer_org != '' && strpos($dcer_org, $upload_res) === false){
				UploadFiles::deleteFile($dcer_org);
				UploadFiles::deleteFile($dcer);
			}

			$upload_res = json_decode( file_get_contents(base_url("openssl/bin/cer.php?file={$upload_res}&path=".APPPATH."CFDI/certificados_pv/")) );
			$dcer_org   = $upload_res[0];
			$dcer       = $upload_res[1];
			//se obtiene la fecha que caduca el certificado
			$this->load->library('cfdi');
			$cer_caduca = $this->cfdi->obtenFechaCertificado($dcer_org);
		}
		//llave
		$new_pass = $this->pass_finkok;
		$dkey_path = (isset($info['info']->key_path)? $info['info']->key_path: '');
		$upload_res = UploadFiles::uploadFile('dkey_path');
		if($upload_res !== false && $upload_res !== 'ok'){
			if($dkey_path != '' && strpos($dkey_path, $upload_res) === false)
				UploadFiles::deleteFile($dkey_path);

			$upload_res = json_decode( file_get_contents(base_url("openssl/bin/key.php?newpass={$new_pass}&pass={$this->input->post('dpass')}&file={$upload_res}&path=".APPPATH."CFDI/certificados_pv/")) );
			$dkey_path  = $upload_res[0];
			$_POST['dpass'] = $new_pass;
		}

		if ($data==NULL)
		{
			$data = array(
						'nombre_fiscal'  => $this->input->post('fnombre_fiscal'),
						'calle'          => $this->input->post('fcalle'),
						'no_exterior'    => $this->input->post('fno_exterior'),
						'no_interior'    => $this->input->post('fno_interior'),
						'colonia'        => $this->input->post('fcolonia'),
						'localidad'      => $this->input->post('flocalidad'),
						'municipio'      => $this->input->post('fmunicipio'),
						'estado'         => $this->input->post('festado'),
						'cp'             => $this->input->post('fcp'),
						'telefono'       => $this->input->post('ftelefono'),
						'celular'        => $this->input->post('fcelular'),
						'email'          => $this->input->post('femail'),
						'cuenta_cpi'     => $this->input->post('fcuenta_cpi'),
						'tipo_proveedor' => $this->input->post('ftipo_proveedor'),
						'rfc'            => $this->input->post('frfc'),
						'curp'           => $this->input->post('fcurp'),
						'regimen_fiscal' => $this->input->post('dregimen_fiscal'),
						'cer_org'        => $dcer_org,
						'cer'            => $dcer,
						'key_path'       => $dkey_path,
						'pass'           => $this->input->post('dpass'),
						'cfdi_version'   => $this->input->post('dcfdi_version'),
						'condicion_pago' => $this->input->post('condicionPago'),
						'dias_credito'   => intval($this->input->post('plazoCredito')),
						'id_empresa'     => $this->input->post('did_empresa'),
						);
			if($cer_caduca != '')
				$data['cer_caduca'] = $cer_caduca;

			// Bitacora
	    $id_bitacora = $this->bitacora_model->_update('proveedores', $id_proveedor, $data,
	                              array(':accion'       => 'el proveedor', ':seccion' => 'proveedores',
	                                    ':folio'        => $data['nombre_fiscal'],
	                                    ':id_empresa'   => $data['id_empresa'],
	                                    ':empresa'      => 'en '.$this->input->post('fempresa'),
	                                    ':id'           => 'id_proveedor',
	                                    ':titulo'       => 'Proveedor'));
		}else {
			if(isset($data['status']) && $data['status'] === 'e') {
				$proveerd = $this->getProveedorInfo($id_proveedor);
				// Bitacora
				$this->bitacora_model->_cancel('proveedores', $id_proveedor,
				                                array(':accion'     => 'el proveedor', ':seccion' => 'proveedores',
				                                      ':folio'      => $proveerd['info']->nombre_fiscal,
				                                      ':id_empresa' => $proveerd['info']->id_empresa,
				                                      ':empresa'    => 'de '.$proveerd['info']->empresa->nombre_fiscal));
			}
		}

		$this->db->update('proveedores', $data, array('id_proveedor' => $id_proveedor));
    $this->addCuentas($id_proveedor);
		$this->saveCentrosCostos($id_proveedor);

		return array('error' => FALSE);
	}

  public function saveCentrosCostos($id_proveedor)
  {
    if (is_array($this->input->post('centros_costos')) && count($this->input->post('centros_costos')) > 0) {
      $this->db->delete('otros.proveedores_centros_costo', "id_proveedor = {$id_proveedor}");
      $centros = [];
      foreach ($this->input->post('centros_costos') as $key => $value) {
        if ($this->input->post('centros_costos_del')[$key] === 'false') { // insert
          $centros[] = ['id_proveedor' => $id_proveedor, 'id_centro_costo' => $value];
        }
        // else {
        //   $this->db->delete('otros.proveedores_centros_costo', "id_proveedor = {$id_proveedor} AND id_centro_costo = {$value}");
        // }
      }

      if (count($centros) > 0) {
        $this->db->insert_batch('otros.proveedores_centros_costo', $centros);
      }
    }
  }

	/**
	 * Obtiene la informacion de un proveedor
	 * @param  boolean $id_proveedor [description]
	 * @param  boolean $basic_info [description]
	 * @return [type]              [description]
	 */
	public function getProveedorInfo($id_proveedor=FALSE, $basic_info=FALSE)
	{
		$id_proveedor = $id_proveedor ? $id_proveedor : (isset($_GET['id'])? $_GET['id']: 0) ;

		$sql_res = $this->db->select("id_proveedor, nombre_fiscal, calle, no_exterior, no_interior, colonia, localidad, municipio,
							estado, cp, telefono, celular, email, cuenta_cpi, tipo_proveedor, rfc, curp, status,
                            cer_org, cer, key_path, pass, cfdi_version, cer_caduca, regimen_fiscal, condicion_pago, dias_credito, id_empresa" )
												->from("proveedores")
												->where("id_proveedor", $id_proveedor)
												->get();
		$data['info'] = array();

		if ($sql_res->num_rows() > 0)
		{
			$data['info']	= $sql_res->row();

			if ($basic_info == False) {
				$this->load->model('empresas_model');
				$data['info']->empresa = $this->empresas_model->getInfoEmpresa($data['info']->id_empresa)['info'];

        $data['info']->centros_costos = $this->db->query("SELECT cc.id_centro_costo, cc.nombre
          FROM otros.proveedores_centros_costo pc
            INNER JOIN otros.centro_costo cc ON cc.id_centro_costo = pc.id_centro_costo
          WHERE pc.id_proveedor = {$id_proveedor}")->result();
			}
		}
		$sql_res->free_result();


		return $data;
	}

	/**
	 * Obtiene el listado de proveedores para usar ajax
	 * @param term. termino escrito en la caja de texto, busca en el nombre
	 * @param type. tipo de proveedor que se quiere obtener (insumos, fruta)
	 */
	public function getProveedoresAjax(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND lower(p.nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'";
		if($this->input->get('did_empresa') != '')
			$sql .= " AND p.id_empresa = '".$this->input->get('did_empresa')."'";

    $lbl = false;
    if($this->input->get('lbempresa') != '')
      $lbl = true;

		$res = $this->db->query("
				SELECT p.id_proveedor, p.nombre_fiscal, p.rfc, p.calle, p.no_exterior, p.no_interior, p.colonia, p.municipio, p.estado, p.cp, p.telefono,
					p.condicion_pago, p.dias_credito, e.nombre_fiscal AS empresa
				FROM proveedores p LEFT JOIN empresas e ON e.id_empresa = p.id_empresa
				WHERE p.status = 'ac' ".$sql."
				ORDER BY p.nombre_fiscal ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => $itm->id_proveedor,
						'label' => $itm->nombre_fiscal.($lbl? " (".substr($itm->empresa, 0, 8).")": ''),
						'value' => $itm->nombre_fiscal.($lbl? " (".substr($itm->empresa, 0, 8).")": ''),
						'item'  => $itm,
				);
			}
		}

		return $response;
	}

	public function getRanchosAjax(){
		$sql = '';
		if ($this->input->get('term') !== false)
			$sql = " AND upper(rancho) LIKE '%".mb_strtoupper($this->input->get('term'), 'UTF-8')."%'";
		$res = $this->db->query("
				SELECT rancho
				FROM ranchos_bascula
				WHERE rancho <> '' ".$sql."
				ORDER BY rancho ASC
				LIMIT 20");

		$response = array();
		if($res->num_rows() > 0){
			foreach($res->result() as $itm){
				$response[] = array(
						'id'    => '0',
						'label' => $itm->rancho,
						'value' => $itm->rancho,
						'item'  => $itm,
				);
			}
		}

		return $response;
	}

  public function getTablasAjax(){
    $sql = '';
    if ($this->input->get('term') !== false)
      $sql = " AND upper(tabla) LIKE '%".mb_strtoupper($this->input->get('term'), 'UTF-8')."%'";
    $res = $this->db->query("
        SELECT tabla
        FROM tablas_bascula
        WHERE tabla <> '' ".$sql."
        ORDER BY tabla ASC
        LIMIT 20");

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $response[] = array(
            'id'    => '0',
            'label' => $itm->tabla,
            'value' => $itm->tabla,
            'item'  => $itm,
        );
      }
    }

    return $response;
  }

	/**
	 * ******* CUENTAS DE PROVEEDORES ****************
	 * ***********************************************
	 * Agrega o actualiza cuentas del proveedor
	 * @param [type] $id_proveedor [description]
	 */
	private function addCuentas($id_proveedor)
	{
		$cuentas = $this->getCuentas($id_proveedor);

		if ( is_array($this->input->post('cuentas_alias')) )
		{
			foreach ($this->input->post('cuentas_alias') as $key => $value)
			{
				$data = array('id_proveedor' => $id_proveedor,
								'is_banamex'   => ($_POST['cuentas_banamex'][$key]=='true'? 't': 'f'),
								'alias'        => $_POST['cuentas_alias'][$key],
								'sucursal'     => ($_POST['cuentas_sucursal'][$key]==''? NULL: $_POST['cuentas_sucursal'][$key]),
								'cuenta'       => $_POST['cuentas_cuenta'][$key],
								'id_banco'     => $_POST['fbanco'][$key],
                'referencia'   => $_POST['cuentas_ref'][$key],
							);
				if (is_numeric($_POST['cuentas_id'][$key]))  //update
				{
					foreach ($cuentas as $keyc => $cuent) {
						if ($cuent->id_cuenta == $_POST['cuentas_id'][$key]) {
							unset($cuentas[$keyc]);
						}
					}

					// if($_POST['cuentas_delte'][$key] == 'true')
					// 	$data['status'] = 'f';
					$this->db->update('proveedores_cuentas', $data, "id_cuenta = {$_POST['cuentas_id'][$key]}");
				}else  //insert
				{
					if($data['alias'] != '' && $data['cuenta'] != '')
						$this->db->insert('proveedores_cuentas', $data);
				}
			}
		}

		// Elimina las cuentas
		if (count($cuentas) > 0)
			foreach ($cuentas as $keyc => $cuent) {
				$this->db->update('proveedores_cuentas', array('status' => 'f'), "id_cuenta = {$cuent->id_cuenta}");
			}
	}

	/**
	 * Obtiene el listado de proveedores
	 * @return [type] [description]
	 */
	public function getCuentas($id_proveedor, $id_cuenta=null){
		$sql = ($id_cuenta==null? '': ' AND pc.id_cuenta = '.$id_cuenta);
		$res = $this->db->query("
				SELECT pc.id_cuenta, pc.id_proveedor, pc.is_banamex, pc.alias, pc.sucursal, pc.cuenta, pc.status,
					(pc.alias || ' *' || substring(pc.cuenta from '....$')) AS full_alias, bb.id_banco, bb.nombre AS banco, bb.codigo, pc.referencia
				FROM proveedores_cuentas AS pc
					LEFT JOIN banco_bancos AS bb ON pc.id_banco = bb.id_banco
				WHERE pc.status = 't' AND pc.id_proveedor = {$id_proveedor} {$sql}
				ORDER BY full_alias ASC");

		$response = array();
		if($res->num_rows() > 0){
			$response = $res->result();
		}

		return $response;
	}

  protected function getSegurosCertificados()
  {
    $sql = '';

    $_GET['ffecha1'] = isset($_GET['ffecha1']) ? $_GET['ffecha1'] : date('Y-m-01');
    $_GET['ffecha2'] = isset($_GET['ffecha2']) ? $_GET['ffecha2'] : date('Y-m-d');

    if (strtotime($_GET['ffecha1']) > strtotime($_GET['ffecha2']))
    {
      $aux = $_GET['ffecha1'];
      $_GET['ffecha1'] = $_GET['ffecha2'];
      $_GET['ffecha2'] = $aux;
    }

    $sql .= " AND (DATE(f.fecha) >= '" . $_GET['ffecha1'] . "' AND DATE(f.fecha) <= '" . $_GET['ffecha2'] . "')";

    $this->load->model('empresas_model');
    $empresaDefault = $this->empresas_model->getDefaultEmpresa();

    $empresa = isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $empresaDefault->id_empresa;
    $sql .= " AND f.id_empresa = {$empresa}";

    if (isset($_GET['pid_proveedor']) && $_GET['pid_proveedor'] !== '')
    {
      $sql .= " AND p.id_proveedor = " . $_GET['pid_proveedor'];
    }

    if (isset($_GET['pproducto_id']) && $_GET['pproducto_id'] !== '')
    {
      $sql .= " AND fsc.id_clasificacion = " . $_GET['pproducto_id'];
    }

    $query = $this->db->query(
      "SELECT p.nombre_fiscal as proveedor,
              DATE(f.fecha) as fecha,
              fsc.pol_seg,
              fsc.certificado,
              fsc.folio,
              fsc.bultos,
              c.nombre_fiscal as cliente,
              fp.importe
       FROM facturacion_seg_cert fsc
       INNER JOIN proveedores p ON p.id_proveedor = fsc.id_proveedor
       INNER JOIN facturacion f ON f.id_factura = fsc.id_factura
       INNER JOIN clientes c ON c.id_cliente = f.id_cliente
       INNER JOIN facturacion_productos fp ON fp.id_factura = f.id_factura AND fp.id_clasificacion = fsc.id_clasificacion
       WHERE 1=1 {$sql}
      ");

    // echo "<pre>";
    //   var_dump($query->result());
    // echo "</pre>";exit;

    return $query->result();
  }

  /**
  * Reporte de Certificados y Seguro.
  */
  public function reporteSegCert(){
    if (isset($_GET['pproducto_id']) && $_GET['pproducto_id'] !== '')
    {
      $res = $this->getSegurosCertificados();

      // echo "<pre>";
      //   var_dump($res);
      // echo "</pre>";exit;

      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

      $this->load->model('proveedores_model');
      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');

      if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;

      $pdf->titulo1 = $empresa['info']->nombre_fiscal;

      if (isset($_GET['pid_proveedor']) && $_GET['pid_proveedor'] !== '')
      {
        $proveedor = $this->proveedores_model->getProveedorInfo($_GET['pid_proveedor']);
        $pdf->titulo2 = 'PROVEEDOR : ' . $proveedor['info']->nombre_fiscal;
      }

      $pdf->titulo3 = 'PERIODO: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
      $pdf->AliasNbPages();
      // $pdf->AddPage();
      $pdf->SetFont('Arial','',8);

      $aligns = array('C');
      $widths = array(25);
      $header = array('FECHA');
      $aligns2 = array('L');

      $tipo = '';
      if ($_GET['pproducto_id'] === '49')
      {
        $header[] = 'POL/SEG';
        $aligns[] = 'C';
        $aligns2[] = 'L';
        $widths[] = 25;
        $tipo = 'seguro';
      }

      if ($_GET['pproducto_id'] === '51' || $_GET['pproducto_id'] === '52')
      {
        $header[] = 'CERTF';
        $header[] = 'BULTOS';

        $aligns[] = 'C';
        $aligns[] = 'C';

        $aligns2[] = 'L';
        $aligns2[] = 'R';

        $widths[] = 25;
        $widths[] = 25;

        $tipo = 'certificado';
      }

      $header = array_merge($header, array('FOLIO', 'CLIENTE', 'IMPORTE'));
      $widths = array_merge($widths, array(15, ($tipo === 'seguro' ? 110 : 85), 30));
      $aligns = array_merge($aligns, array('C', 'C', 'C'));
      $aligns2 = array_merge($aligns2, array('L', 'L', 'R'));

      $total = 0;

      foreach($res as $key => $data)
      {
        if($pdf->GetY() >= $pdf->limiteY || $key == 0)
        {
          $pdf->AddPage();

          $pdf->SetFont('Arial', 'B', 9);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, false, true);
        }

        $pdf->SetFont('Arial', '', 8);

        $total += floatval($data->importe);

        if ($tipo === 'seguro')
        {
          $datos = array(
            MyString::fechaATexto($data->fecha, '/c'),
            $data->pol_seg,
            $data->folio,
            $data->cliente,
            MyString::formatoNumero($data->importe, 2, '', false),
          );
        }
        elseif ($tipo === 'certificado')
        {
          $datos = array(
            MyString::fechaATexto($data->fecha, '/c'),
            $data->certificado,
            $data->bultos,
            $data->folio,
            $data->cliente,
            MyString::formatoNumero($data->importe, 2, '', false),
          );
        }

        $pdf->SetX(6);
        $pdf->SetAligns($aligns2);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false, true);
      }

      $pdf->SetX(6);
      $pdf->SetFont('Arial','B',8);
      $pdf->SetAligns(array('R', 'R'));
      $pdf->SetWidths(array(175, 30));

      $pdf->Row(array(' TOTAL:',  MyString::formatoNumero($total, 2, '', false)), false);

      $pdf->Output('Reporte.pdf', 'I');
    }
  }

  public function rpt_listado_cuentas_data()
  {
    $this->load->model('empresas_model');
    $this->load->model('cuentas_cobrar_model');

    $response = array('ventas' => array());
    $sql1 = $sql2 = '';

    if (empty($_GET['did_empresa'])){
      $empresa = $this->empresas_model->getDefaultEmpresa();
      $_GET['did_empresa'] = $empresa->id_empresa;
    }
    $sql2 .= " AND p.id_empresa = ".$_GET['did_empresa']."";

    // Obtenemos las ventas
    $query = $this->db->query(
      "SELECT p.id_proveedor, p.nombre_fiscal, pc.alias, pc.sucursal, pc.cuenta, bb.nombre AS banco
      FROM proveedores p
        INNER JOIN proveedores_cuentas pc ON p.id_proveedor = pc.id_proveedor
        INNER JOIN banco_bancos bb ON bb.id_banco = pc.id_banco
      WHERE p.status = 'ac' AND pc.status = 't' {$sql2}
      ORDER BY p.id_proveedor ASC, pc.alias ASC");

    if($query->num_rows() > 0){
      $listado = [];
      $aux = 0;
      foreach ($query->result() as $key => $value) {
        if ($aux != $value->id_proveedor) {
          $listado[$value->id_proveedor] = [
            'proveedor' => $value->nombre_fiscal,
            'cuentas' => [$value]
          ];
          $aux = $value->id_proveedor;
        } else {
          $listado[$value->id_proveedor]['cuentas'][] = $value;
        }
      }
    }
    $query->free_result();


    return $listado;
  }

  /**
  * Reporte de ventas de dia
  * @return void
  */
  public function rpt_listado_cuentas_pdf()
  {
    // Obtiene los datos del reporte.
    $data = $this->rpt_listado_cuentas_data();
    // echo "<pre>";
    //   var_dump($data);
    // echo "</pre>";exit;

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($_GET['did_empresa']);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = "Listado de cuentas";

    $pdf->AliasNbPages();

    // Listado de Rendimientos
    $pdf->SetFont('helvetica','', 8);
    $pdf->SetY($pdf->GetY()+2);

    $aligns = array('L', 'L', 'R', 'R');
    $widths = array(39, 77, 30, 60);
    $header = array('BANCO','ALIAS','SUCURSAL','CUENTA');

    $key11 = 0;
    foreach($data as $key => $proveedor)
    {
      if($pdf->GetY() >= $pdf->limiteY || $key11==0) //salta de pagina si exede el max
      {
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

        $pdf->SetFont('helvetica','B',8);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(200,200,200);
        // $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
        $key11=1;
      }

      $pdf->SetFont('helvetica','B', 8);
      $pdf->SetTextColor(0,0,0);
      $pdf->SetX(6);
      $pdf->SetAligns('L');
      $pdf->SetWidths(array(200));
      $pdf->Row(array(
        $proveedor['proveedor']
        ), false, false);

      foreach ($proveedor['cuentas'] as $keyc => $cuenta) {
        $pdf->SetFont('helvetica','', 8);
        $pdf->SetTextColor(0,0,0);

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row(array(
          $cuenta->banco,
          $cuenta->alias,
          $cuenta->sucursal,
          $cuenta->cuenta,
          ), false, false);
      }
    }

    $pdf->Output('lista.pdf', 'I');
  }

  public function rpt_listado_cuentas_xls(){
    $data = $this->rpt_listado_cuentas_data();

    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=lista_cuentas.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($_GET['did_empresa']);

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "Listado de cuentas";

    $html = '<table>
    <tbody>
      <tr>
        <td colspan="4" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
      </tr>
      <tr>
        <td colspan="4" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
      </tr>
      <tr>
        <td colspan="4"></td>
      </tr>
      <tr style="font-weight:bold">
        <td style="border:1px solid #000;background-color: #cccccc;">BANCO</td>
        <td style="border:1px solid #000;background-color: #cccccc;">ALIAS</td>
        <td style="border:1px solid #000;background-color: #cccccc;">SUCURSAL</td>
        <td style="border:1px solid #000;background-color: #cccccc;">CUENTA</td>
      </tr>';

      $key11 = 0;
      foreach($data as $key => $proveedor)
      {
        $html .= '<tr>
          <td style="border:1px solid #000;" colspan="4">'.$proveedor['proveedor'].'</td>
        </tr>';

        foreach ($proveedor['cuentas'] as $keyc => $cuenta) {
          $html .= '<tr>
            <td style="border:1px solid #000;">'.$cuenta->banco.'</td>
            <td style="border:1px solid #000;">'.$cuenta->alias.'</td>
            <td style="border:1px solid #000;">\''.$cuenta->sucursal.'</td>
            <td style="border:1px solid #000;">\''.$cuenta->cuenta.'</td>
          </tr>';
        }
    }

    $html .= '
    </tbody>
    </table>';

    echo $html;
  }

  public function catalogo_xls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=proveedores.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $proveedores = $this->db->query("SELECT *
        FROM proveedores p
        WHERE status <> 'e'
        ORDER BY p.nombre_fiscal ASC")->result();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "Catalogo de proveedores";
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
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Cta Contpaq</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">RFC</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">CURP</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Dias de Credito</td>
        </tr>';

    foreach ($proveedores as $key => $proveedor)
    {
      $html .= '<tr>
          <td style="width:400px;border:1px solid #000;">'.$proveedor->nombre_fiscal.'</td>
          <td style="width:400px;border:1px solid #000;">'.$proveedor->calle.'</td>
          <td style="width:100px;border:1px solid #000;">'.$proveedor->no_exterior.'</td>
          <td style="width:100px;border:1px solid #000;">'.$proveedor->no_interior.'</td>
          <td style="width:150px;border:1px solid #000;">'.$proveedor->colonia.'</td>
          <td style="width:100px;border:1px solid #000;">'.$proveedor->localidad.'</td>
          <td style="width:400px;border:1px solid #000;">'.$proveedor->municipio.'</td>
          <td style="width:150px;border:1px solid #000;">'.$proveedor->estado.'</td>
          <td style="width:100px;border:1px solid #000;">'.$proveedor->pais.'</td>
          <td style="width:400px;border:1px solid #000;">'.$proveedor->cp.'</td>
          <td style="width:150px;border:1px solid #000;">'.$proveedor->telefono.'</td>
          <td style="width:100px;border:1px solid #000;">'.$proveedor->celular.'</td>
          <td style="width:400px;border:1px solid #000;">'.$proveedor->email.'</td>
          <td style="width:100px;border:1px solid #000;">'.$proveedor->cuenta_cpi.'</td>
          <td style="width:100px;border:1px solid #000;">'.$proveedor->rfc.'</td>
          <td style="width:150px;border:1px solid #000;">'.$proveedor->curp.'</td>
          <td style="width:100px;border:1px solid #000;">'.$proveedor->dias_credito.'</td>
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