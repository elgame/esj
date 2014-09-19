<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class bitacora_model extends bitacora_msg_model {

	function __construct()
 	{
		parent::__construct();
	}

	public function _insert($tabla, $id, $params = array())
	{
		$datos = array(
			'id_usuario'   => $this->session->userdata('id_usuario'),
			'tabla'        => $tabla,
			'id_tabla'     => $id,
			'descripcion'  => $this->_getDescrip($tabla.'_insert', $params),
			'json_cambios' => '',
			'tipo'         => 'Agregar',
			'seccion'      => $params[':seccion'],
		);
		if(isset($params[':id_empresa']))
			$datos['id_empresa'] = $params[':id_empresa'];
		$this->db->insert('bitacora', $datos);
	}

	public function _update($tabla, $id, $new, $params = array())
	{
		$campos = $this->campos[(isset($params[':updates_fields'])? $params[':updates_fields']: $tabla.'_update')];

    $old = $this->db->query("SELECT * FROM {$tabla} WHERE {$params[':id']} = {$id}")->row();

    $response = array();
    foreach ($campos['campos'] as $key => $campo) {
    	$special = true;
    	if (preg_match("/fecha/i", $key) === 1 ) {
				if(strtotime($old->{$key}) === strtotime($new[$key]))
					$special = false;
			}

    	if ( isset($new[$key]) && $new[$key] != $old->{$key} && $special) {
    		if (array_key_exists($key, $campos['campos_ids'])) {
    			$response[] = array('campo'   => $campo,
															'antes'   => $this->db->query(str_replace('?', $old->{$key}, $campos['campos_ids'][$key]))->row()->dato,
															'despues' => $this->db->query(str_replace('?', $new[$key], $campos['campos_ids'][$key]))->row()->dato);
    		}else {
    			$response[] = array('campo' => $campo, 'antes' => $old->{$key}, 'despues' => $new[$key]);
    		}
    	}
    }

  	$response = array( array('titulo' => $params[':titulo'], 'cambios' => $response) );
		$datos = array(
			'id_usuario'   => $this->session->userdata('id_usuario'),
			'tabla'        => $tabla,
			'id_tabla'     => $id,
			'descripcion'  => $this->_getDescrip($tabla.'_update', $params),
			'json_cambios' => json_encode($response),
			'tipo'         => 'Editar',
			'seccion'      => $params[':seccion'],
		);
		if(isset($params[':id_empresa']))
			$datos['id_empresa'] = $params[':id_empresa'];

		$this->db->insert('bitacora', $datos);
		$id_bitacora = $this->db->insert_id();
    return $id_bitacora;
	}

	public function _updateExt($id_bitacora, $tabla, $id, $new, $params = array())
	{
		$campos = $this->campos[(isset($params[':updates_fields'])? $params[':updates_fields']: $tabla.'_update')];

		// Datos viejos de la BD
    $old = $this->db->query("SELECT * FROM {$tabla} WHERE {$params[':id']} = {$id}")->result();

    // Selecciona los campos del array campos, crea hash para comparar y asigna valores especiales
    foreach ($new as $key => $value) {
			$new[$key]          = array_intersect_key($new[$key], $campos['campos']);
			$new[$key]['hash']  = md5(implode('|', array_values($new[$key]) ));
			$new[$key][':tipo'] = 'new';

			foreach ($campos['campos_ids'] as $keycm => $sqlcm) {
				$new[$key][$keycm] = $this->db->query(str_replace('?', $new[$key][$keycm], $sqlcm))->row()->dato;
			}
    }
    foreach ($old as $key => $value) {
			$old[$key]          = array_intersect_key( (array)$old[$key], $campos['campos']);
			$old[$key]['hash']  = md5(implode('|', array_values($old[$key]) ));
			$old[$key][':tipo'] = 'old';

			foreach ($campos['campos_ids'] as $keycm => $sqlcm) {
				$old[$key][$keycm] = $this->db->query(str_replace('?', $old[$key][$keycm], $sqlcm))->row()->dato;
			}
    }

    // Con el hash comprar y elimina los q no cambiaron
    foreach ($new as $keyn => $valuen) {
	    foreach ($old as $keyo => $valueo) {
				if ($valuen['hash'] == $valueo['hash']) {
					unset($new[$keyn], $old[$keyo]);
				}
	    }
    }

    // Creamos las modificciones
    $response = array();
    foreach (array_merge($new, $old) as $key => $row) {
    	$tipo = $row[':tipo'];
    	unset($row['hash'], $row[':tipo']);
    	if($tipo === 'new')
    		$response[] = array('campo' => $campos['campos'], 'antes' => '', 'despues' => implode('|', $row));
    	else
    		$response[] = array('campo' => $campos['campos'], 'antes' => implode('|', $row), 'despues' => 'Eliminado');
    }

    // Se actualiza la modificacion base con los nuevos cambios
    if (count($response) > 0) {
    	$info = $this->db->query("SELECT json_cambios FROM bitacora WHERE id = {$id_bitacora}")->row();
    	$info->json_cambios = json_decode($info->json_cambios);

    	$info->json_cambios[] = array('titulo' => $params[':titulo'], 'cambios' => $response);

			$this->db->update('bitacora', array('json_cambios' => json_encode($info->json_cambios)), array('id' => $id_bitacora));
    }
	}

	public function _cancel($tabla, $id, $params = array())
	{
		$datos = array(
			'id_usuario'   => $this->session->userdata('id_usuario'),
			'tabla'        => $tabla,
			'id_tabla'     => $id,
			'descripcion'  => $this->_getDescrip($tabla.'_cancel', $params),
			'json_cambios' => '',
			'tipo'         => 'Cancelar',
			'seccion'      => $params[':seccion'],
		);
		if(isset($params[':id_empresa']))
			$datos['id_empresa'] = $params[':id_empresa'];

		$this->db->insert('bitacora', $datos);
	}


	private function _getDescrip($tabla, $param)
	{
		$msg = str_replace(array_keys($param), array_values($param), $this->descripciones[$tabla]);
		return $msg;
	}


	/**
	 * 						REPORTE DE BITACORA
	 * *********************************************
	 *
	 * @return [type] [description]
	 */
	private function bitacoraData()
  {
    $sql = "";
    if ((isset($_GET['ffecha1']) && $_GET['ffecha1']) && (isset($_GET['ffecha2']) && $_GET['ffecha2']))
    {
      $sql .= " AND DATE(bb.fecha) >= '{$_GET['ffecha1']}' AND DATE(bb.fecha) <= '{$_GET['ffecha2']}'";
    }
    else
    {
      $sql .= " AND DATE(bb.fecha) >= '".date('Y-m-d')."' AND DATE(bb.fecha) <= '".date('Y-m-d')."'";
    }

    if (isset($_GET['fseccion']) && $_GET['fseccion'])
    {
      $sql .= " AND bb.seccion LIKE '{$_GET['fseccion']}'";
    }

    if (isset($_GET['ftipo']) && $_GET['ftipo'])
    {
      $sql .= " AND bb.tipo = '{$_GET['ftipo']}'";
    }

    if (isset($_GET['fid_empresa']) && $_GET['fid_empresa'])
    {
      $sql .= " AND em.id_empresa = {$_GET['fid_empresa']}";
    }

    if (isset($_GET['fdescripcion']) && $_GET['fdescripcion'])
    {
      $sql .= " AND LOWER(bb.descripcion) LIKE LOWER('%{$_GET['fdescripcion']}%')";
    }

    $query = $this->db->query(
      "SELECT bb.id, Date(bb.fecha) AS fecha, bb.descripcion, bb.json_cambios, us.nombre, em.nombre_fiscal,
      	bb.tipo, bb.seccion
       FROM bitacora bb
       	INNER JOIN usuarios us ON us.id = bb.id_usuario
       	LEFT JOIN empresas em ON em.id_empresa = bb.id_empresa
       WHERE 1=1 {$sql}
       ORDER BY bb.fecha ASC
    ");

    return $query->result();
  }

  public function bitacora_pdf()
  {
    // Obtiene los datos del reporte.
    $data = $this->bitacoraData();

    // echo "<pre>";
    //   var_dump($data);
    // echo "</pre>";exit;

    $fecha = new DateTime(isset($_GET['ffecha1'])?$_GET['ffecha1']:date('Y-m-d'));
    $fecha2 = new DateTime(isset($_GET['ffecha2'])?$_GET['ffecha2']:date('Y-m-d'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if (isset($_GET['fid_empresa']) && $_GET['fid_empresa'])
    {
      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('fid_empresa'));

      if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;

      $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    }

    $pdf->titulo2 = "BITACORA DEL {$fecha->format('d/m/Y')} AL {$fecha2->format('d/m/Y')}";
    // $pdf->titulo3 = $this->input->get('fempresa').' | '.$data['tipo'].' | '.$data['status'];

    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('helvetica','', 8);

    $aligns = array('L', 'L', 'L', 'L', 'L', 'L');
    $widths = array(20, 105, 40, 20, 20);
    $header = array('FECHA', 'DESCRIPCION', 'SECCION', 'TIPO', 'USUARIO');

    $aligns2 = array('L', 'L', 'L');
    $widths2 = array(65, 65, 65);
    $header2 = array('CAMPO', 'ANTES', 'DESPUES');

    $aux = 0;

    foreach($data as $key => $log)
    {
      if($pdf->GetY() >= $pdf->limiteY || $key==0) //salta de pagina si exede el max
      {
        $pdf->AddPage();

        $pdf->SetFont('helvetica', 'B', 8);
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFillColor(240,240,240);
        $pdf->SetY($pdf->GetY()-2);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, 1, 1);
      }

      $pdf->SetFont('helvetica', 'B', 8);

      // se colocaria la info de la bascula
      $pdf->SetY($pdf->GetY());
      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row(array(
          $log->fecha,
          $log->descripcion,
          ucfirst($log->seccion),
          $log->tipo,
          $log->nombre,
        ), false, false);

      if($log->json_cambios !== '')
      {
	      // se coloca el header de la tabla de los registros
      	$log_cambios = json_decode($log->json_cambios);

      	foreach ($log_cambios as $keyc => $cambio) {
      		$pdf->SetX(10);
      		$pdf->SetFont('helvetica', 'B', 8);
      		$pdf->SetAligns(array('L'));
		      $pdf->SetWidths(array(150));
	      	$pdf->Row(array($cambio->titulo), false, false);

	      	$pdf->SetFont('helvetica','', 7);
		      $pdf->SetTextColor(0,0,0);
		      $pdf->SetFillColor(255, 255, 255);

		      $pdf->SetY($pdf->GetY());
		      $pdf->SetX(15);
		      $pdf->SetAligns($aligns2);
		      $pdf->SetWidths($widths2);
		      $pdf->Row($header2, false, 1);
		      foreach ($cambio->cambios as $keyrc => $rowc) {
						$pdf->SetX(15);
						$pdf->Row(array(
						    implode('|', (array)$rowc->campo),
						    $rowc->antes,
						    $rowc->despues
						  ), false, false);
		      }
      	}
      }
    }

    $pdf->Output('BITACORA_'.$fecha->format('d/m/Y').'.pdf', 'I');
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */