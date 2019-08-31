<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class resguardos_activos_model extends CI_Model {


  function __construct()
  {
    parent::__construct();
    $this->load->model('bitacora_model');
  }

  public function getResguardos($paginados = true)
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

    $_GET['fstatus'] = ($this->input->get('fstatus') !== false? $this->input->get('fstatus'): 't');
    if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos'){
      $sql .= " AND ra.status = '".$this->input->get('fstatus')."'";
    }

    if($this->input->get('did_empresa') != ''){
      $sql .= ' AND e.id_empresa = ' . $this->input->get('did_empresa');
    }

    if($this->input->get('did_producto') != ''){
      $sql .= ' AND p.id_producto = ' . $this->input->get('did_producto');
    }

    if($this->input->get('did_entrego') != ''){
      $sql .= ' AND ra.id_entrego = ' . $this->input->get('did_entrego');
    }

    if($this->input->get('did_recibio') != ''){
      $sql .= ' AND ra.id_recibio = ' . $this->input->get('did_recibio');
    }

    if($this->input->get('did_registro') != ''){
      $sql .= ' AND ra.id_registro = ' . $this->input->get('did_registro');
    }

    if($this->input->get('ftipo') != '' && $this->input->get('ftipo') != 'todos'){
      $sql .= " AND ra.tipo = '".$this->input->get('ftipo')."'";
    }

    $fecha1 = $this->input->get('ffecha1')? $this->input->get('ffecha1'): date("Y-m").'-01';
    $fecha2 = $this->input->get('ffecha2')? $this->input->get('ffecha2'): date("Y-m-d");

    $query['query'] =
      "SELECT ra.id_resguardo, e.id_empresa, e.nombre_fiscal AS empresa, p.id_producto, p.nombre AS producto,
        ra.id_entrego, (ue.nombre || ' ' || ue.apellido_paterno || ' ' || ue.apellido_materno) AS entrego,
        ra.id_recibio, (ur.nombre || ' ' || ur.apellido_paterno || ' ' || ur.apellido_materno) AS recibio,
        ra.id_registro, (urg.nombre || ' ' || urg.apellido_paterno || ' ' || urg.apellido_materno) AS registro,
        ra.tipo, ra.fecha_entrega, ra.fecha_finalizo, ra.status, ra.observaciones, p.tipo_activo,
        p.descripcion AS descripcion_activo, p.monto AS monto_activo
      FROM otros.resguardos_activos ra
        INNER JOIN empresas e ON e.id_empresa = ra.id_empresa
        INNER JOIN productos p ON p.id_producto = ra.id_producto
        INNER JOIN usuarios ue ON ue.id = ra.id_entrego
        INNER JOIN usuarios ur ON ur.id = ra.id_recibio
        INNER JOIN usuarios urg ON urg.id = ra.id_registro
      WHERE ra.fecha_entrega BETWEEN '{$fecha1}' AND '{$fecha2}' {$sql}
      ";
    if($paginados) {
      $query = BDUtil::pagination($query['query'], $params, true);
    }
    $res = $this->db->query($query['query']);

    $response = array(
        'resguardos_activos' => array(),
        'total_rows'         => isset($query['total_rows'])? $query['total_rows']: 0,
        'items_per_page'     => isset($params['result_items_per_page'])? $params['result_items_per_page']: 0,
        'result_page'        => isset($params['result_page'])? $params['result_page']: 0
    );
    if($res->num_rows() > 0){
      $response['resguardos_activos'] = $res->result();
    }

    return $response;
  }

  /**
   * Agrega un proveedor a la BDD
   * @param [type] $data [description]
   */
  public function addResguardo($data=NULL)
  {

    if ($data==NULL)
    {
      $data = array(
        'id_empresa'     => $this->input->post('did_empresa'),
        'id_producto'    => $this->input->post('fid_producto'),
        'id_entrego'     => $this->input->post('fid_entrego'),
        'id_recibio'     => $this->input->post('fid_recibio'),
        'id_registro'    => $this->session->userdata('id_usuario'),
        'tipo'           => $this->input->post('ftipo'),
        'fecha_entrega'  => $this->input->post('ffecha_entrego'),
        'observaciones'  => $this->input->post('fobservaciones'),
      );
      $this->closeResguardo($data['id_empresa'], $data['id_producto'], $data['fecha_entrega']);
    }

    $this->db->insert('otros.resguardos_activos', $data);

    return array('error' => FALSE);
  }

  /**
   * Modificar la informacion de un proveedor
   * @param  [type] $id_resguardo [description]
   * @param  [type] $data       [description]
   * @return [type]             [description]
   */
  public function updateResguardo($id_resguardo, $data=NULL)
  {

    if ($data==NULL)
    {
      $data = array(
        'id_empresa'     => $this->input->post('did_empresa'),
        'id_producto'    => $this->input->post('fid_producto'),
        'id_entrego'     => $this->input->post('fid_entrego'),
        'id_recibio'     => $this->input->post('fid_recibio'),
        'id_registro'    => $this->session->userdata('id_usuario'),
        'tipo'           => $this->input->post('ftipo'),
        'fecha_entrega'  => $this->input->post('ffecha_entrego'),
        'observaciones'  => $this->input->post('fobservaciones'),
      );
    }

    $this->db->update('otros.resguardos_activos', $data, array('id_resguardo' => $id_resguardo));

    return array('error' => FALSE);
  }


  /**
   * Obtiene la informacion de un proveedor
   * @param  boolean $id_productor [description]
   * @param  boolean $basic_info [description]
   * @return [type]              [description]
   */
  public function getResguardoInfo($id_resguardo=FALSE, $basic_info=FALSE)
  {
    $id_resguardo = $id_resguardo? $id_resguardo: (isset($_GET['id'])? $_GET['id']: 0);

    $sql_res = $this->db->query("SELECT ra.id_resguardo, e.id_empresa, e.nombre_fiscal AS empresa, p.id_producto, p.nombre AS producto,
        ra.id_entrego, (ue.nombre || ' ' || ue.apellido_paterno || ' ' || ue.apellido_materno) AS entrego,
        ra.id_recibio, (ur.nombre || ' ' || ur.apellido_paterno || ' ' || ur.apellido_materno) AS recibio,
        ra.id_registro, (urg.nombre || ' ' || urg.apellido_paterno || ' ' || urg.apellido_materno) AS registro,
        ra.tipo, ra.fecha_entrega, ra.fecha_finalizo, ra.status, e.logo, ra.observaciones, p.tipo_activo,
        p.descripcion AS descripcion_activo, p.monto AS monto_activo
      FROM otros.resguardos_activos ra
        INNER JOIN empresas e ON e.id_empresa = ra.id_empresa
        INNER JOIN productos p ON p.id_producto = ra.id_producto
        INNER JOIN usuarios ue ON ue.id = ra.id_entrego
        INNER JOIN usuarios ur ON ur.id = ra.id_recibio
        INNER JOIN usuarios urg ON urg.id = ra.id_registro
      WHERE ra.id_resguardo = {$id_resguardo}");
    $data['info'] = array();

    if ($sql_res->num_rows() > 0)
      $data['info'] = $sql_res->row();
    $sql_res->free_result();

    if ($basic_info == False) {
    }

    return $data;
  }

  /**
   * Si es un activo resguardo busca el anterior y cierra la fecha de asignación
   * @param  [type] $id_empresa     [description]
   * @param  [type] $id_producto    [description]
   * @param  [type] $fecha_finalizo [description]
   * @return [type]                 [description]
   */
  private function closeResguardo($id_empresa, $id_producto, $fecha_finalizo)
  {
    $result = $this->db->query(
      "SELECT id_resguardo
        FROM otros.resguardos_activos
        WHERE id_empresa = {$id_empresa} AND id_producto = {$id_producto}
          AND tipo = 'resguardo' AND status = 't'
        ORDER BY id_resguardo DESC");
    if($result->num_rows() > 0){
      $resguardo = $result->row();
      $this->db->update('otros.resguardos_activos', ['fecha_finalizo' => $fecha_finalizo], "id_resguardo = {$resguardo->id_resguardo}");
    }
  }

  private function getTipoActivo($tipo)
  {
    switch ($tipo) {
      case 'et': $tipo = 'Equipo de Transporte'; break;
      case 'ec': $tipo = 'Equipo de Computo'; break;
      case 'meo': $tipo = 'Mobiliario y Equipo de Oficina'; break;
      case 'me': $tipo = 'Maquinaria y Equipo'; break;
      case 'e': $tipo = 'Edificios'; break;
      case 't': $tipo = 'Terrenos'; break;
    }
    return $tipo;
  }

  /**
  * Visualiza/Descarga el PDF del resguardo.
  *
  * @return void
  */
  public function printResguardo($id_resguardo, $path = null)
  {
    $this->load->model('compras_areas_model');
    $this->load->model('catalogos_sft_model');

    $resguardo = $this->getResguardoInfo($id_resguardo, true);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    // $pdf->show_head = true;
    $pdf->titulo1 = $resguardo['info']->empresa;
    if ($resguardo['info']->tipo === 'resguardo') {
      $tipo_orden = 'RESGUARDO DE BIENES MUEBLES';
    } else {
      $tipo_orden = 'ASIGNACION DE PRODUCTOS';
    }

    $pdf->logo = $resguardo['info']->logo!=''? (file_exists($resguardo['info']->logo)? $resguardo['info']->logo: '') : '';

    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFillColor(190,190,190);

    $pdf->SetXY(6, $pdf->GetY()-10);

    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(150, 50));
    $pdf->Row(array(
      $tipo_orden,
      '',
    ), false, false);

    $pdf->SetFont('helvetica','', 10);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(90, 115));
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->Row(array('Clasificación', 'Producto'), true, true);

    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array($this->getTipoActivo($resguardo['info']->tipo_activo), $resguardo['info']->producto), false, true);

    $pdf->SetWidths(array(50));
    $pdf->SetXY(6, $pdf->GetY()+5);
    $auxy = $pdf->GetY();
    $pdf->Row(array('Descripción'), true, true);
    $pdf->SetWidths(array(155));
    $pdf->SetXY(56, $auxy);
    $pdf->Row(array($resguardo['info']->descripcion_activo), false, true);

    $pdf->SetWidths(array(50));
    $auxy = $pdf->GetY();
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('Importe'), true, true);
    $pdf->SetWidths(array(155));
    $pdf->SetXY(56, $auxy);
    $pdf->Row(array(MyString::formatoNumero($resguardo['info']->monto_activo, 2, '$', false)), false, true);

    $pdf->SetWidths(array(50));
    $auxy = $pdf->GetY();
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array('Fecha de Entrega'), true, true);
    $pdf->SetWidths(array(155));
    $pdf->SetXY(56, $auxy);
    $pdf->Row(array($resguardo['info']->fecha_entrega), false, true);

    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(100, 105));
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->Row(array('Entrego', 'Recibió'), true, true);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array($resguardo['info']->entrego, $resguardo['info']->recibio), false, true);

    $pdf->SetWidths(array(205));
    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->Row(array('Observaciones'), false, false);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Row(array($resguardo['info']->observaciones), false, true);

    if ($resguardo['info']->tipo === 'resguardo') {
      $pdf->SetXY(6, $pdf->GetY()+10);
      $pdf->Row(array("El Bien detallado en este documento forma parte del patrimonio de la empresa {$resguardo['info']->empresa}, y está dedicado al servicio de la misma, en la que el Custodio y Usuario es Responsable del buen uso que se haga de él. El Bien descrito lo recibe en buen estado y asume la responsabilidad que se derive de su mal uso, pérdida o daños ocasionados por el mal trato o utilización ajena a las funciones laborales de la empresa {$resguardo['info']->empresa}"), false, true);
    }

    $pdf->SetAligns(array('C', 'L'));
    $pdf->SetWidths(array(200));
    $pdf->SetXY(6, $pdf->GetY()+10);
    $pdf->Row(array("____________________________________________\nREGISTRO: ".strtoupper($resguardo['info']->registro)), false, false);
    $pdf->SetXY(6, $pdf->GetY()+10);
    $pdf->Row(array("____________________________________________\nENTREGO: ".strtoupper($resguardo['info']->entrego)), false, false);
    $pdf->SetXY(6, $pdf->GetY()+10);
    $pdf->Row(array("____________________________________________\nRECIBE: ".strtoupper($resguardo['info']->recibio)), false, false);

    if ($path)
    {
      $file = $path.'resguardo'.date('Y-m-d').'.pdf';
      $pdf->Output($file, 'F');
      return $file;
    }
    else
    {
      $pdf->Output('resguardo'.date('Y-m-d').'.pdf', 'I');
    }
  }

  public function printListado()
  {
    $this->load->model('empresas_model');
    $resguardos = $this->getResguardos(false);
    $empresa = $this->empresas_model->getInfoEmpresa(true);
    // echo "<pre>";
    //   var_dump($resguardos);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('L', 'mm', 'Letter');
    // $pdf->show_head = true;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $tipo_orden = 'RESGUARDO DE ACTIVOS';

    $pdf->logo = $empresa['info']->logo!=''? (file_exists($empresa['info']->logo)? $empresa['info']->logo: '') : '';

    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetFillColor(190,190,190);

    $pdf->SetXY(6, $pdf->GetY()-10);

    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetAligns(array('L', 'R'));
    $pdf->SetWidths(array(150, 50));
    $pdf->Row(array(
      $tipo_orden,
      '',
    ), false, false);

    $aligns = array('L', 'L', 'L', 'L', 'L', 'L');
    $widths = array(68, 68, 68, 25, 20, 20);
    $header = array('Producto', 'Entrego', 'Recibió', 'Fecha Entrega', 'Tipo', 'Status');

    foreach ($resguardos['resguardos_activos'] as $key => $resg)
    {
      if($pdf->GetY() >= $pdf->limiteY || $key==0) { //salta de pagina si exede el max
        if($pdf->GetY()+5 >= $pdf->limiteY)
          $pdf->AddPage();

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
      }

      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->SetFont('Arial','B',8);
      $pdf->SetTextColor(0,0,0);
      $datos = array(
        $resg->producto,
        $resg->entrego,
        $resg->recibio,
        $resg->fecha_entrega,
        $resg->tipo,
        ($resg->status == 't'? 'Activo': 'Eliminado'),
      );
      $pdf->SetXY(6, $pdf->getY());
      $pdf->Row($datos, false);

      $pdf->SetFont('Arial','',8);
      $pdf->SetWidths([68, 38, 30, 133]);
      $datos = array(
        "Tipo Activo: ".$this->getTipoActivo($resg->tipo_activo),
        "Monto: ".MyString::formatoNumero($resg->monto_activo, 2, '$', false),
        "Finalizo: {$resg->fecha_finalizo}",
        "Observaciones: {$resg->observaciones}",
      );
      $pdf->SetXY(6, $pdf->getY());
      $pdf->Row($datos, false);

      $pdf->SetXY(6, $pdf->getY()+3);

    }

    $pdf->Output('resguardo_lista.pdf', 'I');
  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */