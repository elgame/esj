<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class productos_bajas_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
  }

  /**
   * Obtiene el listado de facturas
   *
   * @return
   */
  public function getBajas($perpage = '40')
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
      $sql = " AND Date(cs.fecha_creacion) BETWEEN '".$this->input->get('ffecha1')."' AND '".$this->input->get('ffecha2')."'";
    elseif($this->input->get('ffecha1') != '')
      $sql = " AND Date(cs.fecha_creacion) = '".$this->input->get('ffecha1')."'";
    elseif($this->input->get('ffecha2') != '')
      $sql = " AND Date(cs.fecha_creacion) = '".$this->input->get('ffecha2')."'";


    if($this->input->get('ffolio') != '')
    {
      $sql .= " AND cs.folio = '".$this->input->get('ffolio')."'";
    }

    if($this->input->get('did_empresa') != '')
    {
      $sql .= " AND e.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if ($this->input->get('fstatus') != '')
    {
      $sql .= " AND cs.status = '".$this->input->get('fstatus')."'";
    }
    else
    {
      $sql .= " AND cs.status in ('b', 'ca')";
    }

    $query = BDUtil::pagination(
        "SELECT cs.id_salida,
                cs.id_empresa, e.nombre_fiscal AS empresa,
                cs.id_empleado, u.nombre AS empleado,
                cs.folio, cs.fecha_creacion AS fecha, cs.fecha_registro,
                cs.status, cs.concepto
        FROM compras_salidas AS cs
        INNER JOIN empresas AS e ON e.id_empresa = cs.id_empresa
        INNER JOIN usuarios AS u ON u.id = cs.id_empleado
        WHERE 1 = 1 AND cs.concepto is not null {$sql}
        ORDER BY (cs.fecha_creacion, cs.folio) DESC
        ", $params, true);

    $res = $this->db->query($query['query']);

    $response = array(
        'bajas'           => array(),
        'total_rows'     => $query['total_rows'],
        'items_per_page' => $params['result_items_per_page'],
        'result_page'    => $params['result_page']
    );
    if($res->num_rows() > 0)
      $response['bajas'] = $res->result();

    return $response;
  }

  /**
   * Agrega una orden de compra
   *
   * @return array
   */
  public function agregar()
  {
    $data = array(
      'id_empresa'      => $_POST['empresaId'],
      'id_empleado'     => $this->session->userdata('id_usuario'),
      'folio'           => $_POST['folio'],
      'fecha_creacion'  => str_replace('T', ' ', $_POST['fecha']),
      'fecha_registro'  => str_replace('T', ' ', $_POST['fecha']),
      'concepto'        => $_POST['conceptoSalida'],
      'status'          => 'b',
    );

    $this->db->insert('compras_salidas', $data);

    $this->load->model('inventario_model');
    $productos = array();
    foreach ($_POST['concepto'] as $key => $concepto)
    {
      $res = $this->inventario_model->promedioData($_POST['productoId'][$key], date('Y-m-d'), date('Y-m-d'));

      $saldo = array_shift($res);

      $productos[] = array(
        'id_salida'       => $this->db->insert_id(),
        'id_producto'     => $_POST['productoId'][$key],
        'no_row'          => $key,
        'cantidad'        => $_POST['cantidad'][$key],
        'precio_unitario' => $saldo['saldo'][1],
      );
    }

    $this->db->insert_batch('compras_salidas_productos', $productos);

    return array('passes' => true, 'msg' => 3);
  }

  public function modificarProductos($idSalida)
  {
    foreach ($_POST['id_producto'] as $key => $producto)
    {
      $this->db->update('compras_salidas_productos', array('cantidad' => $_POST['cantidad'][$key]), array('id_salida' => $idSalida, 'id_producto' => $producto));
    }

    return array('passes' => true, 'msg' => 5);
  }

  public function cancelar($idOrden)
  {
    $this->db->update('compras_salidas', array('status' => 'ca'), array('id_salida' => $idOrden));

    return array('passes' => true);
  }

  public function info($idSalida, $full = false)
  {
    $query = $this->db->query(
      "SELECT cs.id_salida,
              cs.id_empresa, e.nombre_fiscal AS empresa,
              cs.id_empleado, u.nombre AS empleado,
              cs.folio, cs.fecha_creacion AS fecha, cs.fecha_registro,
              cs.status, cs.concepto
        FROM compras_salidas AS cs
        INNER JOIN empresas AS e ON e.id_empresa = cs.id_empresa
        INNER JOIN usuarios AS u ON u.id = cs.id_empleado
        WHERE cs.id_salida = {$idSalida}");

    $data = array();
    if ($query->num_rows() > 0)
    {
      $data['info'] = $query->result();

      $query->free_result();
      if ($full)
      {
        $query = $this->db->query(
          "SELECT csp.id_salida, csp.no_row,
                  csp.id_producto, pr.nombre AS producto, pr.codigo,
                  csp.cantidad, csp.precio_unitario
           FROM compras_salidas_productos AS csp
           INNER JOIN productos AS pr ON pr.id_producto = csp.id_producto
           WHERE csp.id_salida = {$data['info'][0]->id_salida}");

        $data['info'][0]->productos = array();
        if ($query->num_rows() > 0)
        {
          $data['info'][0]->productos = $query->result();
        }
      }

    }

    return $data;
  }

  public function folio($tipo = 'p')
  {
    $res = $this->db->select('folio')
      ->from('compras_salidas')
      ->order_by('folio', 'DESC')
      ->limit(1)->get()->row();

    $folio = (isset($res->folio) ? $res->folio : 0) + 1;

    return $folio;
  }
}