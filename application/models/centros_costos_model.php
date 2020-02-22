<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class centros_costos_model extends CI_Model {


  function __construct()
  {
    parent::__construct();
    $this->load->model('bitacora_model');
  }

  public function getCentrosCostos($paginados = true)
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
      $sql = "WHERE ( lower(cc.nombre) LIKE '%".mb_strtolower($this->input->get('fnombre'), 'UTF-8')."%' )";

    $_GET['fstatus'] = ($this->input->get('fstatus') !== false? $this->input->get('fstatus'): 't');
    if($this->input->get('fstatus') != '' && $this->input->get('fstatus') != 'todos')
      $sql .= ($sql==''? 'WHERE': ' AND')." cc.status='".$this->input->get('fstatus')."'";

    if($this->input->get('did_area') != '')
      $sql .= ($sql==''? 'WHERE': ' AND').' cc.id_area = ' . $this->input->get('did_area');

    if($this->input->get('ftipo') != '')
      $sql .= ($sql==''? 'WHERE': ' AND')." cc.tipo = '" . $this->input->get('ftipo') . "'";

    $query['query'] =
          "SELECT cc.id_centro_costo, cc.nombre, cc.status, cc.tipo,
            a.id_area, a.nombre AS area, cc.codigo
          FROM otros.centro_costo cc
            LEFT JOIN public.areas a ON a.id_area = cc.id_area
          {$sql}
          ORDER BY cc.nombre ASC";
    if($paginados) {
      $query = BDUtil::pagination($query['query'], $params, true);
    }
    $res = $this->db->query($query['query']);

    $response = array(
        'centros_costos' => array(),
        'total_rows'     => isset($query['total_rows'])? $query['total_rows']: 0,
        'items_per_page' => isset($params['result_items_per_page'])? $params['result_items_per_page']: 0,
        'result_page'    => isset($params['result_page'])? $params['result_page']: 0
    );
    if($res->num_rows() > 0) {
      $response['centros_costos'] = $res->result();
    }

    return $response;
  }

  /**
   * Agrega un proveedor a la BDD
   * @param [type] $data [description]
   */
  public function addCentroCosto($data=NULL)
  {

    if ($data==NULL)
    {
      $data = array(
        'nombre'        => $this->input->post('nombre'),
        'tipo'          => $this->input->post('tipo'),
        'hectareas'     => floatval($this->input->post('hectareas')),
        'no_plantas'    => floatval($this->input->post('no_plantas')),
        'id_area'       => $this->input->post('did_area') > 0? $this->input->post('did_area'): NULL,
        'anios_credito' => floatval($this->input->post('anios_credito')),
        'id_cuenta'     => NULL,
        'cuenta_cpi'    => $this->input->post('cuenta_cpi'),
        'codigo'        => $this->input->post('codigo'),
      );

      if ($data['tipo'] === 'banco' && $this->input->post('id_cuenta') !== false) {
        $data['id_cuenta'] = intval($this->input->post('id_cuenta'));
      }
    }

    $this->db->insert('otros.centro_costo', $data);

    return array('error' => FALSE);
  }

  /**
   * Modificar la informacion de un proveedor
   * @param  [type] $id_productor [description]
   * @param  [type] $data       [description]
   * @return [type]             [description]
   */
  public function updateCentroCosto($id_centro_costo, $data=NULL)
  {

    if ($data==NULL)
    {
      $data = array(
        'nombre'        => $this->input->post('nombre'),
        'tipo'          => $this->input->post('tipo'),
        'hectareas'     => floatval($this->input->post('hectareas')),
        'no_plantas'    => floatval($this->input->post('no_plantas')),
        'id_area'       => $this->input->post('did_area') > 0? $this->input->post('did_area'): NULL,
        'anios_credito' => floatval($this->input->post('anios_credito')),
        'id_cuenta'     => NULL,
        'cuenta_cpi'    => $this->input->post('cuenta_cpi'),
        'codigo'        => $this->input->post('codigo'),
      );

      if ($data['tipo'] === 'banco' && $this->input->post('id_cuenta') !== false) {
        $data['id_cuenta'] = intval($this->input->post('id_cuenta'));
      }
    }

    $this->db->update('otros.centro_costo', $data, array('id_centro_costo' => $id_centro_costo));

    return array('error' => FALSE);
  }


  /**
   * Obtiene la informacion de un proveedor
   * @param  boolean $id_rancho [description]
   * @param  boolean $basic_info [description]
   * @return [type]              [description]
   */
  public function getCentroCostoInfo($id_centro_costo=FALSE, $basic_info=FALSE)
  {
    $id_centro_costo = $id_centro_costo? $id_centro_costo: (isset($_GET['id'])? $_GET['id']: 0);

    $sql_res = $this->db->select("id_centro_costo, id_area, nombre, status, tipo, hectareas, no_plantas, anios_credito,
                                  id_cuenta, cuenta_cpi, codigo" )
                        ->from("otros.centro_costo")
                        ->where("id_centro_costo", $id_centro_costo)
                        ->get();
    $data['info'] = array();

    if ($sql_res->num_rows() > 0)
      $data['info'] = $sql_res->row();
    $sql_res->free_result();

    $data['docus'] = array();
    if ($basic_info == False) {

      // Carga la info de la area.
      if (isset($data['info']->id_area)) {
        $this->load->model('areas_model');
        $empresa = $this->areas_model->getAreaInfo($data['info']->id_area, true);
        $data['info']->area = $empresa['info'];
      }

      if (isset($data['info']->id_cuenta)) {
        $this->load->model('banco_cuentas_model');
        $cuenta = $this->banco_cuentas_model->getCuentaInfo($data['info']->id_cuenta, true);
        $data['info']->cuenta = $cuenta['info'];
      }
    }

    return $data;
  }

  /**
   * Obtiene el listado de proveedores para usar ajax
   * @param term. termino escrito en la caja de texto, busca en el nombre
   * @param type. tipo de proveedor que se quiere obtener (insumos, fruta)
   */
  public function getCentrosCostosAjax($sqlX = null){
    $sql = '';
    if ($this->input->get('term') !== false)
      $sql .= " AND (lower(cc.nombre) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%' OR
                lower(cc.codigo) LIKE '".mb_strtolower($this->input->get('term'), 'UTF-8')."%'
              )";
    if ($this->input->get('tipo') !== false) {
      if (is_array($this->input->get('tipo'))) {
        $sql .= " AND cc.tipo in('".implode("','", $this->input->get('tipo'))."')";
      } else
        $sql .= " AND cc.tipo = '".$this->input->get('tipo')."'";
    }

    if ($this->input->get('id_area') !== false)
      $sql .= " AND cc.id_area = {$this->input->get('id_area')}";

    if (!is_null($sqlX))
      $sql .= $sqlX;

    $res = $this->db->query(
        "SELECT cc.id_centro_costo, cc.nombre, cc.tipo, cc.cuenta_cpi, a.id_area, a.nombre AS area,
          cc.hectareas, cc.no_plantas, cc.codigo
        FROM otros.centro_costo cc
          LEFT JOIN public.areas a ON a.id_area = cc.id_area
        WHERE cc.status = 't'
          {$sql}
        ORDER BY cc.nombre ASC
        LIMIT 20"
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