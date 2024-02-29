<?php
class pg_produccion_model extends privilegios_model{
  private $otrosDatoss = null;

  public $tipos = [
    '' => '',
    'bg' => '(Bodega GDL)',
    'ff' => '(Flete Foráneo)',
    'fl' => '(Flete Loca)l'
  ];

	function __construct(){
		parent::__construct();
    $this->load->model('bitacora_model');
	}


  public function getProduccion($perpage = '40', $sql2='')
  {
    $sql = '';
    //paginacion
    $params = array(
        'result_items_per_page' => $perpage,
        'result_page'       => (isset($_GET['pag'])? $_GET['pag']: 0)
    );
    if($params['result_page'] % $params['result_items_per_page'] == 0)
      $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

    //Filtros para buscar
    if($this->input->get('ffecha1') != '' && $this->input->get('ffecha2') != '')
      $sql = " AND Date(pgp.fecha) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffecha1') != '')
      $sql = " AND Date(pgp.fecha) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffecha2') != '')
      $sql = " AND Date(pgp.fecha) = '".$this->input->get('ffecha2')."'";

    if($this->input->get('fbuscar') != '' && is_numeric($this->input->get('fbuscar'))) {
      $sql .= " AND pgp.folio = '".$this->input->get('fbuscar')."'";
    } elseif($this->input->get('fbuscar') != '') {
      $sql .= " AND (
        lower(es.nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('fbuscar'), 'UTF-8')."%' OR
        lower(pgm.nombre) LIKE '%".mb_strtolower($this->input->get('fbuscar'), 'UTF-8')."%' OR
        lower(pgmo.nombre) LIKE '%".mb_strtolower($this->input->get('fbuscar'), 'UTF-8')."%'
      )";
    }

    if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'all') {
      $sql .= " AND pgp.status = ".$this->input->get('fstatus')."";
    }

    $empresa_default = $this->empresas_model->getDefaultEmpresa();
    if($this->input->get('did_empresa') != '') {
      $sql .= " AND pgp.id_empresa = '".$this->input->get('did_empresa')."'";
    } else {
      $sql .= " AND pgp.id_empresa = '".$empresa_default->id_empresa."'";
    }

    $query = BDUtil::pagination("
        SELECT pgp.id_produccion, pgp.folio, pgp.turno, pgp.fecha, pgp.cajas_total,
          pgp.status, es.nombre_fiscal AS sucursal, pgm.nombre AS maquina, pgmo.nombre AS molde
        FROM otros.pg_produccion pgp
          LEFT JOIN empresas_sucursales es ON es.id_sucursal = pgp.id_sucursal
          INNER JOIN otros.pg_maquinas pgm ON pgm.id_maquina = pgp.id_maquina
          INNER JOIN otros.pg_moldes pgmo ON pgmo.id_molde = pgp.id_molde
        WHERE 1 = 1 ".$sql."
        ORDER BY pgp.fecha DESC, pgp.folio DESC
        ", $params, true);
    $res = $this->db->query($query['query']);

    $response = array(
        'producciones'   => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['producciones'] = $res->result();

    return $response;
  }

  public function produccionInfo($id, $full = false)
  {
    $query = $this->db->query(
      "SELECT pgp.id_produccion, pgp.id_empresa, pgp.id_sucursal, pgp.id_maquina, pgp.id_molde, pgp.id_grupo, pgp.id_registro,
          pgp.id_jefe_turno, pgp.folio, pgp.turno, pgp.fecha, pgp.cajas_buenas, pgp.cajas_merma, pgp.cajas_total, pgp.plasta_kg,
          pgp.inyectado_kg, pgp.peso_prom, pgp.fecha_creada, pgp.status, pgp.tiempo_ciclo, pgma.nombre AS maquina,
          pgmo.nombre AS molde, pggr.nombre AS grupo, Concat(jt.nombre, ' ', jt.apellido_paterno, ' ', jt.apellido_materno) AS jefe_turno,
          Concat(reg.nombre, ' ', reg.apellido_paterno, ' ', reg.apellido_materno) AS registro, pgp.no_impresiones
        FROM otros.pg_produccion pgp
          LEFT JOIN otros.pg_maquinas pgma ON pgma.id_maquina = pgp.id_maquina
          LEFT JOIN otros.pg_moldes pgmo ON pgmo.id_molde = pgp.id_molde
          LEFT JOIN otros.pg_grupos pggr ON pggr.id_grupo = pgp.id_grupo
          LEFT JOIN usuarios jt ON jt.id = pgp.id_jefe_turno
          LEFT JOIN usuarios reg ON reg.id = pgp.id_registro
        WHERE pgp.id_produccion = {$id}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->row();
    }

    if ($full && isset($data['info']->id_produccion)) {
      $this->load->model('empresas_model');
      $data['info']->empresa = $this->empresas_model->getInfoEmpresa($data['info']->id_empresa)['info'];
      $data['info']->sucursal = $this->empresas_model->infoSucursal($data['info']->id_sucursal);

      $data['info']->productos = $this->db->query(
        "SELECT pgp.id, pgp.id_clasificacion, c.nombre AS clasificacion,
            pgp.cajas_buenas, pgp.cajas_merma, pgp.cajas_total, pgp.plasta_kg,
            pgp.inyectado_kg, pgp.peso_prom, pgp.tiempo_ciclo
          FROM otros.pg_produccion_productos pgp
            INNER JOIN clasificaciones c ON c.id_clasificacion = pgp.id_clasificacion
          WHERE pgp.id_produccion = {$id}")->result();
    }

    return $data;
  }

  public function addProduccion($data=NULL)
  {

    if ($data==NULL)
    {
      $data = array(
        'id_empresa'    => $this->input->post('did_empresa'),
        'id_sucursal'   => $this->input->post('sucursalId'),
        'id_maquina'    => $this->input->post('dmaquina'),
        'id_molde'      => $this->input->post('dmolde'),
        'id_grupo'      => $this->input->post('dgrupo'),
        'id_registro'   => $this->session->userdata('id_usuario'),
        'id_jefe_turno' => $this->input->post('djefeTurnId'),
        'folio'         => $this->getFolio($this->input->post('did_empresa'), $this->input->post('sucursalId')),
        'turno'         => $this->input->post('dturno'),
        'fecha'         => $this->input->post('dfecha'),
        // 'cajas_buenas'  => $this->input->post('cajas_buenas'),
        // 'cajas_merma'   => $this->input->post('cajas_merma'),
        // 'cajas_total'   => $this->input->post('cajas_total'),
        // 'plasta_kg'     => $this->input->post('plasta_kg'),
        // 'inyectado_kg'  => $this->input->post('inyectado_kg'),
        // 'peso_prom'     => $this->input->post('peso_prom'),
        // 'tiempo_ciclo'  => $this->input->post('tiempo_ciclo'),
      );
    }

    $this->db->insert('otros.pg_produccion', $data);
    $id_produccion = $this->db->insert_id('otros.pg_produccion_id_produccion_seq');
    $this->saveProductos($id_produccion);

    return array('passes' => true);
  }

  public function updateProduccion($idProd, $data=NULL)
  {

    if ($data==NULL)
    {
      $data = array(
        // 'id_empresa'    => $this->input->post('did_empresa'),
        // 'id_sucursal'   => $this->input->post('sucursalId'),
        'id_maquina'    => $this->input->post('dmaquina'),
        'id_molde'      => $this->input->post('dmolde'),
        'id_grupo'      => $this->input->post('dgrupo'),
        // 'id_registro'   => $this->session->userdata('id_usuario'),
        'id_jefe_turno' => $this->input->post('djefeTurnId'),
        // 'folio'         => $this->getFolio($this->input->post('did_empresa'), $this->input->post('sucursalId')),
        'turno'         => $this->input->post('dturno'),
        'fecha'         => $this->input->post('dfecha'),
        // 'cajas_buenas'  => $this->input->post('cajas_buenas'),
        // 'cajas_merma'   => $this->input->post('cajas_merma'),
        // 'cajas_total'   => $this->input->post('cajas_total'),
        // 'plasta_kg'     => $this->input->post('plasta_kg'),
        // 'inyectado_kg'  => $this->input->post('inyectado_kg'),
        // 'peso_prom'     => $this->input->post('peso_prom'),
        // 'tiempo_ciclo'  => $this->input->post('tiempo_ciclo'),
      );
    }

    $this->db->update('otros.pg_produccion', $data, "id_produccion = {$idProd}");
    $this->saveProductos($idProd);

    return array('passes' => true);
  }

  public function saveProductos($produccionId)
  {
    if (isset($_POST['prod_id_clasificacion']) && count($_POST['prod_id_clasificacion']) > 0) {
      foreach ($_POST['prod_id_clasificacion'] as $key => $itm) {
        $data = [
          'id_produccion'    => $produccionId,
          'id_clasificacion' => $_POST['prod_id_clasificacion'][$key],
          'cajas_buenas'     => $_POST['prod_cajas_buenas'][$key],
          'cajas_merma'      => $_POST['prod_cajas_merma'][$key],
          'cajas_total'      => $_POST['prod_total_cajas'][$key],
          'plasta_kg'        => $_POST['prod_plasta'][$key],
          'inyectado_kg'     => $_POST['prod_Kgs_inyectados'][$key],
          'peso_prom'        => $_POST['prod_peso_promedio'][$key],
          'tiempo_ciclo'     => $_POST['prod_ciclo'][$key],
        ];

        if ($_POST['prod_id'][$key] > 0 && $_POST['prod_del'][$key] == 'true') {
          $this->db->delete('otros.pg_produccion_productos', "id = {$_POST['prod_id'][$key]}");
        } elseif ($_POST['prod_id'][$key] > 0) {
          $this->db->update('otros.pg_produccion_productos', $data, "id = {$_POST['prod_id'][$key]}");
        } else {
          $this->db->insert('otros.pg_produccion_productos', $data);
        }
      }
    }
  }

  public function cancelar($idProd)
  {

    $this->db->update('otros.pg_produccion', ['status' => 'f'], "id_produccion = {$idProd}");

    return array('passes' => true);
  }

  /**
   * Obtiene el folio de acuerdo a la serie seleccionada
   */
  public function getFolio($empresa, $sucursal)
  {
    $res = $this->db->select('folio')->
                      from('otros.pg_produccion')->
                      where("id_empresa = {$empresa}")->
                      where("id_sucursal = '{$sucursal}'")->
                      order_by('folio', 'DESC')->
                      limit(1)->get()->row();

    $folio = (isset($res->folio)? $res->folio: 0)+1;

    return $folio;
  }

  public function imprimir($idProd, $path = null)
  {
    $orden = $this->produccionInfo($idProd, true);
    // echo "<pre>";
    //   var_dump($orden['info']);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', array(63, 600));
    $pdf->show_head = false;
    $pdf->AddPage();
    $pdf->AddFont($pdf->fount_num, '');

    $tituloo = 'PRODUCCIÓN PLÁSTICOS';

    $pdf->SetY(0);

    // Título
    $pdf->SetFont($pdf->fount_txt, 'B', 8.5);
    $pdf->SetXY(0, $pdf->GetY()+5);
    $pdf->MultiCell($pdf->pag_size[0], 4, $tituloo, 0, 'C');
    $pdf->SetFont($pdf->fount_txt, '', 8);
    $pdf->SetX(0);
    $pdf->MultiCell($pdf->pag_size[0], 4, $orden['info']->empresa->nombre_fiscal, 0, 'C');
    if (isset($orden['info']->sucursal->nombre_fiscal)) {
      $pdf->SetX(0);
      $pdf->MultiCell($pdf->pag_size[0], 4, $orden['info']->sucursal->nombre_fiscal, 0, 'C');
    }

    $pdf->SetFont($pdf->fount_txt, '', 7);
    $pdf->SetWidths(array(11, 21, 11, 19));
    $pdf->SetAligns(array('L','L', 'R', 'R'));
    $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_num, $pdf->fount_txt, $pdf->fount_num),
      [2.5, 2.5, 1, 1]);
    $pdf->SetX(0);
    $pdf->Row2(array('Folio', $orden['info']->folio, 'Fecha', MyString::fechaAT( substr($orden['info']->fecha, 0, 11) )), false, false, 5);

    $semana = MyString::obtenerSemanaDeFecha(substr($orden['info']->fecha, 0, 10), $orden['info']->empresa->dia_inicia_semana);

    $pdf->SetWidths(array(64));
    $pdf->SetAligns(array('L', 'L'));

    $pdf->SetXY(0, $pdf->GetY());
    $pdf->SetFounts(array($pdf->fount_txt), [], ['B']);
    $pdf->Row2(array('Maquina: '), false, false);
    $pdf->SetFounts(array($pdf->fount_txt), [], ['']);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array($orden['info']->maquina), false, false);

    $pdf->SetFounts(array($pdf->fount_txt), [], ['B']);
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row2(array('Molde: '), false, false);
    $pdf->SetFounts(array($pdf->fount_txt), [], ['']);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array($orden['info']->molde), false, false);

    $pdf->SetWidths(array(13, 18, 13, 18));
    $pdf->SetAligns(array('L','L', 'L', 'L'));
    $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_num, $pdf->fount_txt, $pdf->fount_num),
      [1, 1, 1, 1], ['B', '', 'B', '']);
    $pdf->SetX(0);
    $pdf->Row2(array('Grupo', $orden['info']->grupo, 'Turno', $orden['info']->turno), false, false, 5);

    $pdf->SetWidths(array(64));
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetFounts(array($pdf->fount_txt), [], ['B']);
    $pdf->SetXY(0, $pdf->GetY());
    $pdf->Row2(array('Jefe de Turno: '), false, false);
    $pdf->SetFounts(array($pdf->fount_txt), [], ['']);
    $pdf->SetXY(0, $pdf->GetY()-2);
    $pdf->Row2(array($orden['info']->jefe_turno), false, false);


    $pdf->SetFont($pdf->fount_txt, '', 7);
    $pdf->SetX(0);
    $pdf->MultiCell($pdf->pag_size[0], 2, '--------------------------------------------------------------------------', 0, 'L');

    $totalCajas = 0;
    foreach ($orden['info']->productos as $key => $prod) {
      $pdf->SetWidths(array(64));
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetFounts(array($pdf->fount_txt), [], ['B']);
      $pdf->SetXY(0, $pdf->GetY());
      $pdf->Row2(array('Clasificación: '), false, false);
      $pdf->SetFounts(array($pdf->fount_txt), [-1], ['']);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array($prod->clasificacion), false, false);

      $pdf->SetWidths(array(21, 21, 21));
      $pdf->SetAligns(array('R','R','R'));
      $pdf->SetFounts(array($pdf->fount_txt,$pdf->fount_txt,$pdf->fount_txt,$pdf->fount_txt),
                     array(0, 0, 0, 0), ['B', 'B', 'B', 'B']);
      $pdf->SetXY(0, $pdf->GetY());
      $pdf->Row2(array('Cajas Buenas', 'Cajas Merma', 'Total Cajas'), false, false, 5);
      $pdf->SetFounts(array($pdf->fount_num,$pdf->fount_num,$pdf->fount_num,$pdf->fount_num),
                     array(1, 1, 1, 1), ['', '', '', '']);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array(
          MyString::formatoNumero($prod->cajas_buenas, 2, '', true),
          MyString::formatoNumero($prod->cajas_merma, 2, '', true),
          MyString::formatoNumero(($prod->cajas_total), 2, '', true),
        ), false, false, 5);

      $pdf->SetFounts(array($pdf->fount_txt,$pdf->fount_txt,$pdf->fount_txt,$pdf->fount_txt),
                     array(0, 0, 0, 0), ['B', 'B', 'B', 'B']);
      $pdf->SetXY(0, $pdf->GetY());
      $pdf->Row2(array('Peso Prom', 'Plasta (kg)', 'Kgs Inyec'), false, false, 5);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->SetFounts(array($pdf->fount_num,$pdf->fount_num,$pdf->fount_num,$pdf->fount_num),
                     array(1, 1, 1, 1), ['', '', '', '']);
      $pdf->Row2(array(
          MyString::formatoNumero($prod->peso_prom, 2, '', true),
          MyString::formatoNumero($prod->plasta_kg, 2, '', true),
          MyString::formatoNumero(($prod->inyectado_kg), 2, '', true),
        ), false, false, 5);

      $pdf->SetFounts(array($pdf->fount_txt,$pdf->fount_txt,$pdf->fount_txt,$pdf->fount_txt),
                     array(0, 0, 0, 0), ['B', 'B', 'B', 'B']);
      $pdf->SetXY(0, $pdf->GetY());
      $pdf->Row2(array('Tiempo Ciclo', '', ''), false, false, 5);
      $pdf->SetFounts(array($pdf->fount_num,$pdf->fount_num,$pdf->fount_num,$pdf->fount_num),
                     array(1, 1, 1, 1), ['', '', '', '']);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array(
          MyString::formatoNumero($prod->tiempo_ciclo, 2, '', true),
          '',
          '',
        ), false, false, 6);


      $totalCajas += floatval($prod->cajas_total);


      $pdf->SetFont($pdf->fount_txt, '', 7);
      $pdf->SetXY(0, $pdf->GetY());
      $pdf->MultiCell($pdf->pag_size[0], 2, '--------------------------------------------------------------------------', 0, 'L');
    }

      $pdf->SetFounts(array($pdf->fount_txt, $pdf->fount_num), array(-1,-1));
      $pdf->SetAligns(array('L', 'R'));
      $pdf->SetWidths(array(66, 0));
      $pdf->SetXY(0, $pdf->GetY());
      $pdf->Row2(array('REGISTRO: '.strtoupper($orden['info']->registro), '' ), false, false);
      $pdf->SetXY(0, $pdf->GetY()-2);
      $pdf->Row2(array('Expedido el: '.MyString::fechaAT(date("Y-m-d"))), false, false);

      $pdf->SetXY(0, $pdf->GetY()-4);
      $pdf->Row2(array( 'Impresión '.($orden['info']->no_impresiones==0? 'ORIGINAL': 'COPIA '.$orden['info']->no_impresiones)), false, false);
      $pdf->Line(0, $pdf->GetY()-1, 62, $pdf->GetY()-1);
    // }

    $this->db->update('otros.pg_produccion', ['no_impresiones' => $orden['info']->no_impresiones+1], "id_produccion = ".$orden['info']->id_produccion);

    $pdf->AutoPrint(true);
    $pdf->Output();
  }



  /**
   ******************** MAQUINAS
   */

  public function maquinasGet($perpage = '40', $status = null)
  {
    $sql = '';
    //paginacion
    $params = array(
        'result_items_per_page' => $perpage,
        'result_page'       => (isset($_GET['pag'])? $_GET['pag']: 0)
    );

    if($params['result_page'] % $params['result_items_per_page'] == 0)
      $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

    //Filtros para buscar
    if ($this->input->get('fnombre') != '')
    {
      $sql .= " AND lower(nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%'";
    }

    $_GET['fstatus'] = $status? $status: $this->input->get('fstatus');
    if ($this->input->get('fstatus') != '')
    {
      $sql .= " AND status = '".$this->input->get('fstatus')."'";
    }

    $query = BDUtil::pagination(
        "SELECT id_maquina, nombre, status
        FROM otros.pg_maquinas
        WHERE 1 = 1 {$sql}
        ORDER BY (nombre) ASC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'conceptos'     => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['conceptos'] = $res->result();

    return $response;
  }

  public function maquinasAgregar($data)
  {
    $insertData = array(
      'nombre' => $data['nombre']
    );

    $this->db->insert('otros.pg_maquinas', $insertData);

    return true;
  }

  public function maquinasInfo($id)
  {
    $query = $this->db->query(
      "SELECT id_maquina, nombre, status
        FROM otros.pg_maquinas
        WHERE id_maquina = {$id}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->result();
    }

    return $data;
  }

  public function maquinasModificar($id, $data)
  {
    $updateData = array(
      'nombre' => $data['nombre'],
    );

    $this->db->update('otros.pg_maquinas', $updateData, array('id_maquina' => $id));

    return true;
  }

  public function maquinasEliminar($id)
  {
    $this->db->update('otros.pg_maquinas', array('status' => 'f'), array('id_maquina' => $id));

    return true;
  }


  /**
   ******************** MOLDES
   */

  public function moldesGet($perpage = '40', $status = null)
  {
    $sql = '';
    //paginacion
    $params = array(
        'result_items_per_page' => $perpage,
        'result_page'       => (isset($_GET['pag'])? $_GET['pag']: 0)
    );

    if($params['result_page'] % $params['result_items_per_page'] == 0)
      $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

    //Filtros para buscar
    if ($this->input->get('fnombre') != '')
    {
      $sql .= " AND lower(nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%'";
    }

    $_GET['fstatus'] = $status? $status: $this->input->get('fstatus');
    if ($this->input->get('fstatus') != '')
    {
      $sql .= " AND status = '".$this->input->get('fstatus')."'";
    }

    $query = BDUtil::pagination(
        "SELECT id_molde, nombre, status
        FROM otros.pg_moldes
        WHERE 1 = 1 {$sql}
        ORDER BY (nombre) ASC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'conceptos'     => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['conceptos'] = $res->result();

    return $response;
  }

  public function moldesAgregar($data)
  {
    $insertData = array(
      'nombre' => $data['nombre']
    );

    $this->db->insert('otros.pg_moldes', $insertData);

    return true;
  }

  public function moldesInfo($id)
  {
    $query = $this->db->query(
      "SELECT id_molde, nombre, status
        FROM otros.pg_moldes
        WHERE id_molde = {$id}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->result();
    }

    return $data;
  }

  public function moldesModificar($id, $data)
  {
    $updateData = array(
      'nombre' => $data['nombre'],
    );

    $this->db->update('otros.pg_moldes', $updateData, array('id_molde' => $id));

    return true;
  }

  public function moldesEliminar($id)
  {
    $this->db->update('otros.pg_moldes', array('status' => 'f'), array('id_molde' => $id));

    return true;
  }


  /**
   ******************** GRUPOS
   */

  public function gruposGet($perpage = '40', $status = null)
  {
    $sql = '';
    //paginacion
    $params = array(
        'result_items_per_page' => $perpage,
        'result_page'       => (isset($_GET['pag'])? $_GET['pag']: 0)
    );

    if($params['result_page'] % $params['result_items_per_page'] == 0)
      $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

    //Filtros para buscar
    if ($this->input->get('fnombre') != '')
    {
      $sql .= " AND lower(nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%'";
    }

    $_GET['fstatus'] = $status? $status: $this->input->get('fstatus');
    if ($this->input->get('fstatus') != '')
    {
      $sql .= " AND status = '".$this->input->get('fstatus')."'";
    }

    $query = BDUtil::pagination(
        "SELECT id_grupo, nombre, status
        FROM otros.pg_grupos
        WHERE 1 = 1 {$sql}
        ORDER BY (nombre) ASC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'conceptos'     => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['conceptos'] = $res->result();

    return $response;
  }

}
