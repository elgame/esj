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

    $fecha1 = $this->input->get('dfecha1')? $this->input->get('dfecha1'): date("Y-m").'-01';
    $fecha2 = $this->input->get('dfecha2')? $this->input->get('dfecha2'): date("Y-m-d");

    $query['query'] =
      "SELECT ra.id_resguardo, e.id_empresa, e.nombre_fiscal AS empresa, p.id_producto, p.nombre AS producto,
        ra.id_entrego, (ue.nombre || ' ' || ue.apellido_paterno || ' ' || ue.apellido_materno) AS entrego,
        ra.id_recibio, (ur.nombre || ' ' || ur.apellido_paterno || ' ' || ur.apellido_materno) AS recibio,
        ra.id_registro, (urg.nombre || ' ' || urg.apellido_paterno || ' ' || urg.apellido_materno) AS registro,
        ra.tipo, ra.fecha_entrega, ra.fecha_finalizo, ra.status
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
  public function addProductor($data=NULL)
  {

    if ($data==NULL)
    {
      $data = array(
            'id_empresa'        => $this->input->post('did_empresa'),
            'nombre_fiscal'     => $this->input->post('fnombre_fiscal'),
            'calle'             => $this->input->post('fcalle'),
            'no_exterior'       => $this->input->post('fno_exterior'),
            'no_interior'       => $this->input->post('fno_interior'),
            'colonia'           => $this->input->post('fcolonia'),
            'localidad'         => $this->input->post('flocalidad'),
            'municipio'         => $this->input->post('fmunicipio'),
            'estado'            => $this->input->post('festado'),
            'cp'                => $this->input->post('fcp'),
            'telefono'          => $this->input->post('ftelefono'),
            'celular'           => $this->input->post('fcelular'),
            'email'             => $this->input->post('femail'),
            // 'cuenta_cpi'     => $this->input->post('fcuenta_cpi'),
            'parcela'           => $this->input->post('fparcela'),
            'ejido_parcela'     => $this->input->post('fejido_parcela'),
            'pais'              => $this->input->post('fpais'),
            'tipo'              => $this->input->post('ftipo'),
            'no_coeplim'        => $this->input->post('no_coeplim'),
            'hectareas'         => floatval($this->input->post('hectareas')),
            'pequena_propiedad' => $this->input->post('pequena_propiedad'),
            'propietario'       => $this->input->post('propietario'),
          );
    }
    $this->db->insert('otros.productor', $data);

    $id_productor = $this->db->insert_id('otros.productor', 'id_productor');

    // Bitacora
    $this->bitacora_model->_insert('otros.productor', $id_productor,
                                    array(':accion'    => 'el productor', ':seccion' => 'productores',
                                          ':folio'     => $data['nombre_fiscal'],
                                          ':id_empresa' => $data['id_empresa'],
                                          ':empresa'   => 'en '.$this->input->post('fempresa')));

    return array('error' => FALSE);
  }

  /**
   * Modificar la informacion de un proveedor
   * @param  [type] $id_productor [description]
   * @param  [type] $data       [description]
   * @return [type]             [description]
   */
  public function updateProductor($id_productor, $data=NULL)
  {

    if ($data==NULL)
    {
      $data = array(
            'id_empresa'        => $this->input->post('did_empresa'),
            'nombre_fiscal'     => $this->input->post('fnombre_fiscal'),
            'calle'             => $this->input->post('fcalle'),
            'no_exterior'       => $this->input->post('fno_exterior'),
            'no_interior'       => $this->input->post('fno_interior'),
            'colonia'           => $this->input->post('fcolonia'),
            'localidad'         => $this->input->post('flocalidad'),
            'municipio'         => $this->input->post('fmunicipio'),
            'estado'            => $this->input->post('festado'),
            'cp'                => $this->input->post('fcp'),
            'telefono'          => $this->input->post('ftelefono'),
            'celular'           => $this->input->post('fcelular'),
            'email'             => $this->input->post('femail'),
            // 'cuenta_cpi'     => $this->input->post('fcuenta_cpi'),
            'parcela'           => $this->input->post('fparcela'),
            'ejido_parcela'     => $this->input->post('fejido_parcela'),
            'pais'              => $this->input->post('fpais'),
            'tipo'              => $this->input->post('ftipo'),
            'no_coeplim'        => $this->input->post('no_coeplim'),
            'hectareas'         => $this->input->post('hectareas'),
            'pequena_propiedad' => $this->input->post('pequena_propiedad'),
            'propietario'       => $this->input->post('propietario'),
            );
      // Bitacora
      $id_bitacora = $this->bitacora_model->_update('otros.productor', $id_productor, $data,
                                array(':accion'       => 'el productor', ':seccion' => 'productores',
                                      ':folio'        => $data['nombre_fiscal'],
                                      ':id_empresa'   => $data['id_empresa'],
                                      ':empresa'      => 'en '.$this->input->post('fempresa'),
                                      ':id'           => 'id_productor',
                                      ':titulo'       => 'Productor'));
    }else {
      if (isset($data['status']) && $data['status'] === 'e') {
        // Bitacora
        $clientedata = $this->getProductorInfo($id_productor);
        $this->bitacora_model->_cancel('otros.productor', $id_productor,
                                        array(':accion'     => 'el productor', ':seccion' => 'productores',
                                              ':folio'      => $clientedata['info']->nombre_fiscal,
                                              ':id_empresa' => $clientedata['info']->id_empresa,
                                              ':empresa'    => 'de '.$clientedata['info']->empresa->nombre_fiscal));
      }
    }

    $this->db->update('otros.productor', $data, array('id_productor' => $id_productor));

    return array('error' => FALSE);
  }


  /**
   * Obtiene la informacion de un proveedor
   * @param  boolean $id_productor [description]
   * @param  boolean $basic_info [description]
   * @return [type]              [description]
   */
  public function getProductorInfo($id_productor=FALSE, $basic_info=FALSE)
  {
    // $id_productor = (isset($_GET['id']))? $_GET['id']: $id_productor;
    $id_productor = $id_productor? $id_productor: (isset($_GET['id'])? $_GET['id']: 0);

    $sql_res = $this->db->select("id_productor, nombre_fiscal, calle, no_exterior, no_interior, colonia, localidad, municipio,
                            estado, cp, telefono, celular, email, parcela, ejido_parcela, status, tipo, pais, id_empresa,
                            no_coeplim, hectareas, pequena_propiedad, propietario" )
                        ->from("otros.productor")
                        ->where("id_productor", $id_productor)
                        ->get();
    $data['info'] = array();

    if ($sql_res->num_rows() > 0)
      $data['info'] = $sql_res->row();
    $sql_res->free_result();

    $data['docus'] = array();
    if ($basic_info == False) {

      // Carga la info de la empresa.
      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($data['info']->id_empresa);
      $data['info']->empresa = $empresa['info'];
    }

    return $data;
  }

  /**
   * Obtiene el listado de proveedores para usar ajax
   * @param term. termino escrito en la caja de texto, busca en el nombre
   * @param type. tipo de proveedor que se quiere obtener (insumos, fruta)
   */
  public function getProductorAjax($sqlX = null){
    $sql = '';
    if ($this->input->get('term') !== false)
      $sql = " AND lower(c.nombre_fiscal) LIKE '%".mb_strtolower($this->input->get('term'), 'UTF-8')."%'";

    if ($this->input->get('did_empresa') !== false && $this->input->get('did_empresa') !== '')
      $sql .= " AND e.id_empresa in(".$this->input->get('did_empresa').")";

      if ( ! is_null($sqlX))
        $sql .= $sqlX;

    $res = $this->db->query(
        "SELECT c.id_productor, c.nombre_fiscal, c.parcela, c.calle, c.no_exterior, c.no_interior, c.colonia, c.municipio, c.estado, c.cp,
          c.telefono, c.ejido_parcela, c.tipo, c.id_empresa, e.nombre_fiscal AS empresa
        FROM otros.productor c INNER JOIN empresas e ON e.id_empresa = c.id_empresa
        WHERE c.status = 'ac'
          {$sql}
        ORDER BY c.nombre_fiscal ASC
        LIMIT 20"
    );

    $response = array();
    if($res->num_rows() > 0){
      foreach($res->result() as $itm){
        $dato_ext = $itm->municipio==''? ($itm->estado==''? '': ' - '.$itm->estado): ' - '.$itm->municipio;
        $dato_ext .= $this->input->get('empresa')=='si'? ' - '.substr($itm->empresa, 0, 5): '';
        $response[] = array(
            'id'    => $itm->id_productor,
            'label' => $itm->nombre_fiscal,
            'value' => $itm->nombre_fiscal,
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
    $producotres = $this->getResguardos(false);

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