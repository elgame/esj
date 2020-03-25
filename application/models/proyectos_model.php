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
            e.id_empresa, e.nombre_fiscal
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
        'id_empresa'  => $this->input->post('did_empresa'),
        'nombre'      => $this->input->post('nombre'),
        'presupuesto' => $this->input->post('presupuesto'),
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
        'id_empresa'  => $this->input->post('did_empresa'),
        'nombre'      => $this->input->post('nombre'),
        'presupuesto' => $this->input->post('presupuesto'),
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
  public function getProyectoInfo($id_proyecto=FALSE, $basic_info=FALSE)
  {
    $id_proyecto = $id_proyecto? $id_proyecto: (isset($_GET['id'])? $_GET['id']: 0);

    $sql_res = $this->db->select("id_proyecto, id_empresa, nombre, presupuesto, status" )
                        ->from("otros.proyectos")
                        ->where("id_proyecto", $id_proyecto)
                        ->get();
    $data['info'] = array();

    if ($sql_res->num_rows() > 0)
      $data['info'] = $sql_res->row();
    $sql_res->free_result();

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
            e.id_empresa, e.nombre_fiscal
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

  public function getCentrosCostosPagesAjax($centros){
    $sql = '';

    $cc = [];
    $codigos = explode(',', $centros);
    foreach ($codigos as $key => $cod) {
      $cod = mb_strtolower(trim($cod), 'UTF-8');
      if (strpos($cod, '-') !== false) {
        $subcodigos = explode('-', $cod);
        preg_match('/[a-z]+/', $subcodigos[0], $subfijos);
        $subfijos = count($subfijos)>0? $subfijos[0]: ''; // m, t, c
        $codini = preg_replace('/[^0-9]/', '', $subcodigos[0]);
        $codfin = preg_replace('/[^0-9]/', '', $subcodigos[1]);
        $subcodigos = range($codini, $codfin);
        foreach ($subcodigos as $key2 => $cod2) {
          $cod2 = trim($cod2);
          $cc[] = "'{$subfijos}{$cod2}'";
        }
      } else {
        $cc[] = "'{$cod}'";
      }
    }

    $cc = implode(',', $cc);
    $sql .= " AND (lower(cc.codigo) IN({$cc}))";

    // if ($this->input->get('tipo') !== false) {
    //   if (is_array($this->input->get('tipo'))) {
    //     $sql .= " AND cc.tipo in('".implode("','", $this->input->get('tipo'))."')";
    //   } else
    //     $sql .= " AND cc.tipo = '".$this->input->get('tipo')."'";
    // }

    // if ($this->input->get('id_area') !== false)
    //   $sql .= " AND cc.id_area = {$this->input->get('id_area')}";

    // if (!is_null($sqlX))
    //   $sql .= $sqlX;

    $res = $this->db->query(
        "SELECT cc.id_centro_costo, cc.nombre, cc.tipo, cc.cuenta_cpi, a.id_area, a.nombre AS area,
          cc.hectareas, cc.no_plantas, cc.codigo
        FROM otros.centro_costo cc
          LEFT JOIN public.areas a ON a.id_area = cc.id_area
        WHERE cc.status = 't'
          {$sql}
        ORDER BY cc.nombre ASC"
    );

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $response[] = array(
            'id'    => $itm->id_centro_costo,
            'label' => $itm->nombre.($itm->codigo!=''? " ({$itm->codigo})": ''),
            'value' => $itm->nombre.($itm->codigo!=''? " ({$itm->codigo})": ''),
            'item'  => $itm,
        );
      }
    }

    return $response;
  }

  public function catalogo_xls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=productores.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    // $this->load->model('areas_model');
    // $area = $this->areas_model->getAreaInfo($id_area, true);
    $producotres = $this->getProductores(false);

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "Catalogo de productores";
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
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Parcela</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Ejido parcela</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Tipo</td>
        </tr>';

    foreach ($producotres['productores'] as $key => $clasif)
    {
      $html .= '<tr>
          <td style="width:400px;border:1px solid #000;">'.utf8_decode($clasif->nombre_fiscal).'</td>
          <td style="width:400px;border:1px solid #000;">'.utf8_decode($clasif->calle).'</td>
          <td style="width:100px;border:1px solid #000;">'.$clasif->no_exterior.'</td>
          <td style="width:100px;border:1px solid #000;">'.$clasif->no_interior.'</td>
          <td style="width:150px;border:1px solid #000;">'.$clasif->colonia.'</td>
          <td style="width:100px;border:1px solid #000;">'.$clasif->localidad.'</td>
          <td style="width:400px;border:1px solid #000;">'.$clasif->municipio.'</td>
          <td style="width:150px;border:1px solid #000;">'.$clasif->estado.'</td>
          <td style="width:100px;border:1px solid #000;">'.$clasif->pais.'</td>
          <td style="width:400px;border:1px solid #000;">'.$clasif->cp.'</td>
          <td style="width:150px;border:1px solid #000;">'.$clasif->telefono.'</td>
          <td style="width:100px;border:1px solid #000;">'.$clasif->celular.'</td>
          <td style="width:400px;border:1px solid #000;">'.$clasif->email.'</td>
          <td style="width:100px;border:1px solid #000;">'.$clasif->parcela.'</td>
          <td style="width:150px;border:1px solid #000;">'.$clasif->ejido_parcela.'</td>
          <td style="width:100px;border:1px solid #000;">'.$clasif->tipo.'</td>
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