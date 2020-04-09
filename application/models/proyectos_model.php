<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class proyectos_model extends CI_Model {


  function __construct()
  {
    parent::__construct();
    $this->load->model('bitacora_model');
  }

  public function getProyectos($paginados = true)
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
      $sql = "WHERE ( lower(p.nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

    $_GET['fstatus'] = ($this->input->get('fstatus') !== false? $this->input->get('fstatus'): 't');
    if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
      $sql .= ($sql==''? 'WHERE': ' AND')." p.status = '".$this->input->get('fstatus')."'";

    if($this->input->get('did_empresa') != '')
      $sql .= ($sql==''? 'WHERE': ' AND').' e.id_empresa = ' . $this->input->get('did_empresa');

    $query['query'] =
          "SELECT p.id_proyecto, p.nombre, p.presupuesto, p.status,
            e.id_empresa, e.nombre_fiscal, p.fecha_inicio, p.fecha_terminacion
          FROM otros.proyectos p
            INNER JOIN empresas e ON e.id_empresa = p.id_empresa
          {$sql}
          ORDER BY nombre ASC";
    if($paginados) {
      $query = BDUtil::pagination($query['query'], $params, true);
    }
    $res = $this->db->query($query['query']);

    $response = array(
        'proyectos' => array(),
        'total_rows'     => isset($query['total_rows'])? $query['total_rows']: 0,
        'items_per_page' => isset($params['result_items_per_page'])? $params['result_items_per_page']: 0,
        'result_page'    => isset($params['result_page'])? $params['result_page']: 0
    );
    if($res->num_rows() > 0) {
      $response['proyectos'] = $res->result();
    }

    return $response;
  }

  /**
   * Agrega un proveedor a la BDD
   * @param [type] $data [description]
   */
  public function addProyecto($data=NULL)
  {
    if ($data==NULL)
    {
      $data = array(
        'id_empresa'        => $this->input->post('did_empresa'),
        'nombre'            => $this->input->post('nombre'),
        'presupuesto'       => $this->input->post('presupuesto'),
        'fecha_inicio'      => ($this->input->post('fecha_inicio')!=false? $_POST['fecha_inicio']: NULL),
        'fecha_terminacion' => ($this->input->post('fecha_terminacion')!=false? $_POST['fecha_terminacion']: NULL),
      );
    }

    $this->db->insert('otros.proyectos', $data);

    return array('error' => FALSE);
  }

  /**
   * Modificar la informacion de un proveedor
   * @param  [type] $id_productor [description]
   * @param  [type] $data       [description]
   * @return [type]             [description]
   */
  public function updateProyecto($id_proyecto, $data=NULL)
  {
    if ($data==NULL)
    {
      $data = array(
        'id_empresa'        => $this->input->post('did_empresa'),
        'nombre'            => $this->input->post('nombre'),
        'presupuesto'       => $this->input->post('presupuesto'),
        'fecha_inicio'      => ($this->input->post('fecha_inicio')!=false? $_POST['fecha_inicio']: NULL),
        'fecha_terminacion' => ($this->input->post('fecha_terminacion')!=false? $_POST['fecha_terminacion']: NULL),
      );
    }

    $this->db->update('otros.proyectos', $data, array('id_proyecto' => $id_proyecto));

    return array('error' => FALSE);
  }


  /**
   * Obtiene la informacion de un proveedor
   * @param  boolean $id_rancho [description]
   * @param  boolean $basic_info [description]
   * @return [type]              [description]
   */
  public function getProyectoInfo($id_proyecto=FALSE, $basic_info=FALSE, $with_apl = false)
  {
    $id_proyecto = $id_proyecto? $id_proyecto: (isset($_GET['id'])? $_GET['id']: 0);

    $sql_res = $this->db->select("id_proyecto, id_empresa, nombre, presupuesto, status, fecha_inicio, fecha_terminacion" )
                        ->from("otros.proyectos")
                        ->where("id_proyecto", $id_proyecto)
                        ->get();
    $data['info'] = array();

    if ($sql_res->num_rows() > 0)
      $data['info'] = $sql_res->row();
    $sql_res->free_result();

    if ($basic_info == False || $with_apl) {
      $data['info']->aplicado = $this->getProyectoAplicado($id_proyecto);
    }

    if ($basic_info == False) {

      // Carga la info de la empresa.
      if (isset($data['info']->id_empresa)) {
        $this->load->model('empresas_model');
        $empresa = $this->empresas_model->getInfoEmpresa($data['info']->id_empresa, true);
        $data['info']->empresa = $empresa['info'];
      }
    }

    return $data;
  }

  public function getProyectoPresupuesto($id_proyecto)
  {
    $response = [];
    $response['salidas'] = $this->db->query("SELECT cs.id_salida, cs.folio, Date(cs.fecha_creacion) AS fecha,
        (cs.concepto || ' ' || cs.observaciones) AS concepto,
        Sum(csp.cantidad*csp.precio_unitario) AS costo
      FROM compras_salidas cs
        INNER JOIN compras_salidas_productos csp ON cs.id_salida = csp.id_salida
      WHERE cs.status <> 'ca' AND cs.id_proyecto = {$id_proyecto}
      GROUP BY cs.id_salida
      ORDER BY fecha ASC")->result();

    $response['compras'] = $this->db->query("SELECT id_compra, serie, folio, Date(fecha) AS fecha,
        (concepto || ' ' || observaciones) AS concepto, subtotal AS costo
      FROM compras
      WHERE isgasto = 't' AND status <> 'ca' AND id_proyecto = {$id_proyecto}
      ORDER BY fecha ASC")->result();

    $response['ordenes'] = $this->db->query("SELECT cs.id_orden, cs.folio, Date(cs.fecha_creacion) AS fecha,
        (cs.descripcion) AS concepto,
        Sum(csp.importe) AS costo
      FROM compras_ordenes cs
        INNER JOIN compras_productos csp ON cs.id_orden = csp.id_orden
      WHERE cs.status in('a','f','n') AND cs.id_proyecto = {$id_proyecto}
      GROUP BY cs.id_orden
      ORDER BY fecha ASC")->result();

    return $response;
  }

  public function getProyectoAplicado($id_proyecto)
  {
    $response = 0;
    $data = $this->getProyectoPresupuesto($id_proyecto);

    if (count($data['salidas']) > 0) {
      foreach ($data['salidas'] as $key => $value) {
        $response += $value->costo;
      }
    }

    if (count($data['compras']) > 0) {
      foreach ($data['compras'] as $key => $value) {
        $response += $value->costo;
      }
    }

    if (count($data['ordenes']) > 0) {
      foreach ($data['ordenes'] as $key => $value) {
        $response += $value->costo;
      }
    }

    return $response;
  }

  /**
   * Obtiene el listado de proveedores para usar ajax
   * @param term. termino escrito en la caja de texto, busca en el nombre
   * @param type. tipo de proveedor que se quiere obtener (insumos, fruta)
   */
  public function getProyectosAjax($sqlX = null){
    $sql = '';
    //Filtros para buscar
    if($this->input->get('fnombre') != '')
      $sql = "WHERE ( lower(p.nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

    $sql .= ($sql==''? 'WHERE': ' AND')." p.status = 't'";

    if($this->input->get('did_empresa') != '')
      $sql .= ($sql==''? 'WHERE': ' AND').' e.id_empresa = ' . $this->input->get('did_empresa');

    $query['query'] =
          "SELECT p.id_proyecto, p.nombre, p.presupuesto, p.status,
            e.id_empresa, e.nombre_fiscal, p.fecha_inicio, p.fecha_terminacion
          FROM otros.proyectos p
            INNER JOIN empresas e ON e.id_empresa = p.id_empresa
          {$sql}
          ORDER BY nombre ASC";
    $res = $this->db->query($query['query']);

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $response[] = array(
            'id'    => $itm->id_proyecto,
            'label' => $itm->nombre,
            'value' => $itm->nombre,
            'item'  => $itm,
        );
      }
    }

    return $response;
  }

  public function print($idProyecto)
  {
    $orden = $this->getProyectoInfo($idProyecto);
    $presupuesto = $this->getProyectoPresupuesto($idProyecto);
    // echo "<pre>";
    //   var_dump($orden, $presupuesto);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    // $pdf->show_head = true;
    $pdf->titulo1 = $orden['info']->empresa->nombre_fiscal;
    $pdf->titulo2 = mb_strtoupper($orden['info']->nombre);

    $pdf->logo = $orden['info']->empresa->logo!=''? (file_exists($orden['info']->empresa->logo)? $orden['info']->empresa->logo: '') : '';

    $pdf->AliasNbPages();
    $pdf->AddPage();

    $pdf->SetXY(90, $pdf->GetY()-8);

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetAligns(array('L', 'L'));
    $pdf->SetWidths(array(60, 60));
    $pdf->Row(array(
      "Fecha de Inicio: ". MyString::fechaATexto($orden['info']->fecha_inicio, '/c'),
      "Fecha de terminación: ". MyString::fechaATexto($orden['info']->fecha_terminacion, '/c')
    ), false, false);

    $salidas = $compras = $ordenes = 0;

    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array("SALIDAS DE ALMACEN"), false, false);

    $aligns = array('C', 'C', 'L', 'R');
    $widths = array(20, 30, 120, 35);
    $header = array('FECHA', 'FOLIO', 'CONCEPTO', 'COSTO');

    if (count($presupuesto['salidas']) > 0) {
      foreach ($presupuesto['salidas'] as $key => $prod)
      {
        $band_head = false;
        if($pdf->GetY() >= $pdf->limiteY || $key==0) { //salta de pagina si exede el max
          if($pdf->GetY()+5 >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetFont('Arial','B',8);
          $pdf->SetFillColor(160,160,160);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('Arial','',8);
        $datos = array(
          $prod->fecha,
          $prod->folio,
          $prod->concepto,
          MyString::formatoNumero($prod->costo, 2, '$', false),
        );

        $pdf->SetX(6);
        $pdf->Row($datos, false);

        $salidas += floatval($prod->costo);
      }
    }

    $pdf->SetFillColor(160,160,160);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->SetX(6);
    $pdf->SetFont('Arial','B',8);
    $pdf->Row(['', '', '', MyString::formatoNumero($salidas, 2, '$', false)], true);

    $pdf->SetY($pdf->GetY()+2);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array("GASTOS DIRECTOS (COMPRAS)"), false, false);

    $aligns = array('C', 'C', 'L', 'R');
    $widths = array(20, 30, 120, 35);
    $header = array('FECHA', 'FOLIO', 'CONCEPTO', 'COSTO');

    if (count($presupuesto['compras']) > 0) {
      foreach ($presupuesto['compras'] as $key => $prod)
      {
        $band_head = false;
        if($pdf->GetY() >= $pdf->limiteY || $key==0) { //salta de pagina si exede el max
          if($pdf->GetY()+5 >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetFont('Arial','B',8);
          $pdf->SetFillColor(160,160,160);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('Arial','',8);
        $datos = array(
          $prod->fecha,
          $prod->serie.$prod->folio,
          $prod->concepto,
          MyString::formatoNumero($prod->costo, 2, '$', false),
        );

        $pdf->SetX(6);
        $pdf->Row($datos, false);

        $compras += floatval($prod->costo);
      }
    }

    $pdf->SetFillColor(160,160,160);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->SetX(6);
    $pdf->SetFont('Arial','B',8);
    $pdf->Row(['','','', MyString::formatoNumero($compras, 2, '$', false)], true);

    $pdf->SetY($pdf->GetY()+2);
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array("ORDENES DE COMPRAS (COMPRAS)"), false, false);

    $aligns = array('C', 'C', 'L', 'R');
    $widths = array(20, 30, 120, 35);
    $header = array('FECHA', 'FOLIO', 'CONCEPTO', 'COSTO');

    if (count($presupuesto['ordenes']) > 0) {
      foreach ($presupuesto['ordenes'] as $key => $prod)
      {
        $band_head = false;
        if($pdf->GetY() >= $pdf->limiteY || $key==0) { //salta de pagina si exede el max
          if($pdf->GetY()+5 >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetFont('Arial','B',8);
          $pdf->SetFillColor(160,160,160);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('Arial','',8);
        $datos = array(
          $prod->fecha,
          $prod->folio,
          $prod->concepto,
          MyString::formatoNumero($prod->costo, 2, '$', false),
        );

        $pdf->SetX(6);
        $pdf->Row($datos, false);

        $ordenes += floatval($prod->costo);
      }
    }

    $pdf->SetFillColor(160,160,160);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->SetX(6);
    $pdf->SetFont('Arial','B',8);
    $pdf->Row(['','','', MyString::formatoNumero($ordenes, 2, '$', false)], true);

    $pdf->SetXY(6, $pdf->GetY()+2);
    $pdf->SetFont('helvetica','B', 11);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array("PRESUPUESTO: ".MyString::formatoNumero($orden['info']->presupuesto, 2, '$', false) ), false, false);

    $pdf->SetXY(6, $pdf->GetY()+2);
    $pdf->SetFont('helvetica','B', 11);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array("GASTOS: ".MyString::formatoNumero($salidas+$compras+$ordenes, 2, '$', false) ), false, false);

    $pdf->SetXY(6, $pdf->GetY()+2);
    $pdf->SetFont('helvetica','B', 11);
    $pdf->SetAligns(array('R'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array("RESTO: ".MyString::formatoNumero(($orden['info']->presupuesto-($salidas+$compras+$ordenes)), 2, '$', false) ), false, false);


    $pdf->Output('PROYECTO'.date('Y-m-d').'.pdf', 'I');

  }

}
/* End of file usuarios_model.php */
/* Location: ./application/controllers/usuarios_model.php */