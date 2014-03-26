<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class nomina_ranchos_model extends CI_Model {

  public function nomina(array $filtros = array(), $empleadoId = null)
  {
    $this->load->model('nomina_fiscal_model');

    $filtros = array_merge(array(
      'semana'    => '',
      'anio'    => '',
      'empresaId' => '',
      'puestoId'  => '',
      'dia_inicia_semana' => '4',
      'de_rancho' => 'l',
    ), $filtros);

    // Filtros
    $semana = $filtros['semana'] !== '' ? $this->nomina_fiscal_model->fechasDeUnaSemana($filtros['semana'], $filtros['anio'], $filtros['dia_inicia_semana']) : $this->nomina_fiscal_model->semanaActualDelMes();

    $sql = '';
    if ($filtros['empresaId'] !== '')
    {
      $sql .= " AND u.id_empresa = {$filtros['empresaId']}";
    }

    if ($filtros['puestoId'] !== '')
    {
      $sql .= " AND u.id_puesto = {$filtros['puestoId']}";
    }

    if ($empleadoId)
    {
      $sql .= " AND u.id = {$empleadoId}";
    }

    if(isset($filtros['asegurado']))
    {
      $sql .= " AND u.esta_asegurado = 't'";
    }

    $ordenar = " ORDER BY u.apellido_paterno ASC, u.apellido_materno ASC ";
    if(isset($filtros['ordenar']))
    {
      $ordenar = $filtros['ordenar'];
    }

    $diaPrimeroDeLaSemana = $semana['fecha_inicio']; // fecha del primero dia de la semana.
    $diaUltimoDeLaSemana = $semana['fecha_final']; // fecha del ultimo dia de la semana.
    $anio = $semana['anio'];

    // Query para obtener los empleados de la semana de la nomina.
    $query = $this->db->query(
      "SELECT u.id,
              (COALESCE(u.apellido_paterno, '') || ' ' || COALESCE(u.apellido_materno, '') || ' ' || u.nombre) as nombre,
              u.curp,
              DATE(u.fecha_entrada) as fecha_entrada,
              nf.fecha_inicio,
              nf.fecha_final,
              nf.fecha,
              COALESCE(nf.precio_lam, 0) AS precio_lam,
              COALESCE(nf.precio_lvr, 0) AS precio_lvr,
              COALESCE(nf.domingo, 0) AS domingo,
              COALESCE(nf.sabado, 0) AS sabado,
              COALESCE(nf.lunes, 0) AS lunes,
              COALESCE(nf.martes, 0) AS martes,
              COALESCE(nf.miercoles, 0) AS miercoles,
              COALESCE(nf.jueves, 0) AS jueves,
              COALESCE(nf.viernes, 0) AS viernes,
              COALESCE(nf.total_lvrd, 0) AS total_lvrd,
              COALESCE(nf.total_lam, 0) AS total_lam,
              COALESCE(nf.prestamo, 0) AS prestamo,
              COALESCE(nf.total_pagar, 0) AS total_pagar,
              COALESCE(nf.id_empleado, 0) AS generada
       FROM usuarios u
       LEFT JOIN nomina_ranchos nf ON nf.id_empleado = u.id AND nf.id_empresa = {$filtros['empresaId']} AND nf.anio = {$anio} AND nf.semana = {$semana['semana']}
       WHERE u.user_nomina = 't' AND u.de_rancho = '{$filtros['de_rancho']}' AND DATE(u.fecha_entrada) <= '{$diaUltimoDeLaSemana}' AND u.status = 't' {$sql}
       {$ordenar}
    ");
    $empleados = $query->num_rows() > 0 ? $query->result() : array();
    $query->free_result();
    // echo "<pre>";
    //   var_dump($empleados);
    // echo "</pre>";exit;
    return $empleados;
  }

  public function add_nominas($datos, $empresaId, $empleadoId)
  {
    $this->load->model('nomina_fiscal_model');
    // echo "<pre>";
    //   var_dump($datos, $empresaId, $empleadoId);
    // echo "</pre>";exit;
    // $startTime = new DateTime(date('Y-m-d H:i:s'));

    // Almacenara los datos de las nominas de cada empleado para despues
    // insertarlas.
    $nominasEmpleados = array();

    $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $datos['id_empresa'])->get()->row()->dia_inicia_semana;
    // Obtiene el rango de fechas de la semana.
    $fechasSemana = $this->nomina_fiscal_model->fechasDeUnaSemana($datos['semana'], $datos['anio'], $dia);

    // Auxiliar para saber si hubo un error al momento de timbrar alguna nomina.
    $errorTimbrar = false;

    if ($datos['generada'] == 0)
    {
      $nominasEmpleados[] = array(
        'id_empleado'  => $datos['id_empleado'],
        'id_empresa'   => $datos['id_empresa'],
        'anio'         => $datos['anio'],
        'semana'       => $datos['semana'],
        'fecha_inicio' => $fechasSemana['fecha_inicio'],
        'fecha_final'  => $fechasSemana['fecha_final'],
        'precio_lam'   => $datos['precio_lam'],
        'precio_lvr'   => $datos['precio_lvr'],
        'domingo'      => $datos['domingo'],
        'sabado'       => $datos['sabado'],
        'lunes'        => $datos['lunes'],
        'martes'       => $datos['martes'],
        'miercoles'    => $datos['miercoles'],
        'jueves'       => $datos['jueves'],
        'viernes'      => $datos['viernes'],
        'total_lvrd'   => $datos['total_lvrd'],
        'total_lam'    => $datos['total_lam'],
        'prestamo'     => $datos['prestamo'],
        'total_pagar'  => $datos['total_pagar'],
        );
      // Inserta las nominas.
      if (count($nominasEmpleados) > 0)
      {
        $this->db->insert_batch('nomina_ranchos', $nominasEmpleados);
      }
    }

    // echo "<pre>";
    //   var_dump($startTime->diff($endTime)->format('%H:%I:%S'));
    // echo "</pre>";exit;

    return array('errorTimbrar' => $errorTimbrar, 'empleadoId' => $empleadoId, 'ultimoNoGenerado' => null);
  }

}
/* End of file nomina_fiscal_model.php */
/* Location: ./application/models/nomina_fiscal_model.php */