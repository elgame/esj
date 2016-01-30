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
              COALESCE(nf.cajas_cargadas, 0) AS cajas_cargadas,
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

    // Prestamos
    foreach ($empleados as $key => $value)
    {
      if ($value->prestamo == 0)
      {
        $value->prestamo = array('total' => 0, 'prestamos_ids' => array());
        $prestamos = $this->getPrestamosEmpleado($value->id, " AND pausado = 'f' AND Date(inicio_pago) <= '{$diaUltimoDeLaSemana}'");
        foreach ($prestamos as $keyp => $prestamo)
        {
          $saldo = $prestamo->prestado - $prestamo->pagado;
          $value->prestamo['total'] += ($saldo>=$prestamo->pago_semana? $prestamo->pago_semana: $saldo);
          $value->prestamo['prestamos_ids'][] = $prestamo->id_prestamo;
        }
         $value->prestamo['prestamos_ids'] = implode(',',  $value->prestamo['prestamos_ids']);
      }else
        $value->prestamo = array('total' => $value->prestamo, 'prestamos_ids' => '');
    }

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

    // if ($datos['generada'] == 0)
    // {
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
        'cajas_cargadas'  => $datos['cajas_cargadas'],
        );
      // Inserta las nominas.
      if (count($nominasEmpleados) > 0)
      {
        foreach ($nominasEmpleados as $key => $value)
        {
          $result = $this->db->query("SELECT Count(*) AS num FROM nomina_ranchos WHERE id_empleado = {$value['id_empleado']}
            AND id_empresa = {$value['id_empresa']} AND anio = {$value['anio']} AND semana = {$value['semana']}")->row();
          if($result->num == 0)
            $this->db->insert('nomina_ranchos', $value);
          else
            $this->db->update('nomina_ranchos', $value, "id_empleado = {$value['id_empleado']}
            AND id_empresa = {$value['id_empresa']} AND anio = {$value['anio']} AND semana = {$value['semana']}");
        }
      }

      // Inserta los prestamos
      $this->deletePrestamos(array(
        'id_empleado' => $datos['id_empleado'], 'id_empresa' => $datos['id_empresa'],
        'anio' => $fechasSemana['anio'], 'semana' => $fechasSemana['semana']
        ));
      $prestamos = $this->getPrestamosEmpleado($datos['id_empleado'], " AND pausado = 'f' AND Date(inicio_pago) <= '{$fechasSemana['fecha_final']}'");
      foreach ($prestamos as $keyp => $prestamo)
      {
        $saldo = $prestamo->prestado - $prestamo->pagado;
        $pagar = ($saldo>=$prestamo->pago_semana? $prestamo->pago_semana: $saldo);
        $this->db->insert('nomina_fiscal_prestamos', array(
          'id_prestamo' => $prestamo->id_prestamo,
          'id_empleado' => $datos['id_empleado'],
          'id_empresa'  => $datos['id_empresa'],
          'anio'        => $fechasSemana['anio'],
          'semana'      => $fechasSemana['semana'],
          'monto'       => $pagar,
          'fecha'       => $fechasSemana['fecha_final'],
          ));

        if( ($saldo - $pagar) == 0 ) // se pago el prestamo
          $this->db->update('nomina_prestamos', array('status' => 'f'), "id_prestamo = {$prestamo->id_prestamo}");
      }

    // }

    // echo "<pre>";
    //   var_dump($startTime->diff($endTime)->format('%H:%I:%S'));
    // echo "</pre>";exit;

    return array('errorTimbrar' => $errorTimbrar, 'empleadoId' => $empleadoId, 'ultimoNoGenerado' => null);
  }

  public function deletePrestamos($params)
  {
    $prestamos = $this->db->select('id_prestamo')->from('nomina_fiscal_prestamos')->where($params)->get();
    foreach ($prestamos->result() as $key => $value)
      $this->db->update('nomina_prestamos', array('status' => 't'), "id_prestamo = {$value->id_prestamo}");
    $this->db->delete('nomina_fiscal_prestamos', $params);
  }

  /**
   * Obtiene los prestamos de un empleado en dicha semana.
   *
   * @param  string $empleadoId
   * @param  string $numSemana
   * @return array
   */
  public function getPrestamosEmpleado($empleadoId, $sql='')
  {
    $query = $this->db->query("SELECT id_prestamo, prestado, pago_semana, status, DATE(fecha) as fecha, DATE(inicio_pago) as inicio_pago, pausado,
                                (SELECT Sum(monto) FROM nomina_fiscal_prestamos WHERE id_prestamo = nomina_prestamos.id_prestamo ) AS pagado
                               FROM nomina_prestamos
                               WHERE id_usuario = {$empleadoId} AND status = 't' {$sql}
                               ORDER BY DATE(fecha) ASC");

    $prestamos = array();
    if ($query->num_rows() > 0)
    {
      $prestamos = $query->result();
    }

    return $prestamos;
  }

  /**
   * Agrega los prestamos.
   *
   * @param string $empleadoId
   * @param array  $datos
   * @param string $numSemana
   * @return array
   */
  public function addPrestamos($empleadoId, array $datos)
  {
    $anio = $anio==null? date("Y"): $anio;
    $this->load->model('usuarios_model');
    $empled = $this->usuarios_model->get_usuario_info($empleadoId, true);
    if (isset($datos['prestamos_existentes']))
    {
      if(count($datos['eliminar_prestamo']) > 0)
        $this->db->delete('nomina_prestamos', "id_prestamo IN(".implode(',', $datos['eliminar_prestamo']).") AND id_usuario = {$empleadoId}");
    }

    $insertData = array();
    foreach ($datos['cantidad'] as $key => $cantidad)
    {
      if($datos['id_prestamo'][$key] > 0)
      {
        $this->db->update('nomina_prestamos', array(
          'id_usuario'  => $empleadoId,
          'prestado'    => $datos['cantidad'][$key],
          'pago_semana' => $datos['pago_semana'][$key],
          'fecha'       => $datos['fecha'][$key],
          'inicio_pago' => $datos['fecha_inicia_pagar'][$key],
          'pausado' => $datos['pausarp'][$key],
        ), "id_prestamo = {$datos['id_prestamo'][$key]}");
      }else{
        $insertData[] = array(
          'id_usuario'  => $empleadoId,
          'prestado'    => $datos['cantidad'][$key],
          'pago_semana' => $datos['pago_semana'][$key],
          'fecha'       => $datos['fecha'][$key],
          'inicio_pago' => $datos['fecha_inicia_pagar'][$key],
          'pausado'     => $datos['pausarp'][$key],
        );
      }
    }

    if (count($insertData) > 0)
    {
      $this->db->insert_batch('nomina_prestamos', $insertData);
    }

    return array('passes' => true);
  }

  public function listadoAsistenciaPdf($datos)
  {
    $this->load->model('empresas_model');
    $this->load->model('nomina_fiscal_model');
     $params['empresaDefault'] = $this->empresas_model->getDefaultEmpresa();
      $filtros = array(
        'semana'    => isset($datos['semana']) ? $datos['semana'] : '',
        'anio'    => isset($datos['anio']) ? $datos['anio'] : date("Y"),
        'empresaId' => isset($datos['empresaId']) ? $datos['empresaId'] : $params['empresaDefault']->id_empresa,
        'puestoId'  => isset($datos['puestoId']) ? $datos['puestoId'] : '',
      );
      if ($filtros['empresaId'] !== '')
      {
        $empresa = $this->db->select('*')->from('empresas')->where('id_empresa', $filtros['empresaId'])->get()->row();
        $filtros['dia_inicia_semana'] = $empresa->dia_inicia_semana;
      }
      else
        $filtros['dia_inicia_semana'] = '4';
      $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($filtros['semana'], $filtros['anio'], $filtros['dia_inicia_semana']);

      // Datos para la vista.
      $empleados_rancho = $this->nomina_ranchos_model->nomina($filtros);

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('L', 'mm', 'Letter');
      $pdf->show_head = true;
      $pdf->logo = $empresa->logo;
      $pdf->titulo1 = $empresa->nombre_fiscal;
      $pdf->titulo2 = "Lista de Raya de {$semana['fecha_inicio']} al {$semana['fecha_final']}";
      $pdf->titulo3 = "Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}";
      $pdf->AliasNbPages();
      $pdf->AddPage();

      $pdf->SetFont('Helvetica','B', 10);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetFont('Helvetica','B', 8);
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();
      $totales_rancho = array('', '', '', '', '', '', '', '', '', '', '', '', '');
      $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'L', 'L', 'L', 'L', 'L', 'L', 'L', 'L', 'L'));
      $pdf->SetWidths(array(65, 13, 13, 13, 13, 13, 13, 13, 13, 13, 18, 18, 18, 30));
      $pdf->Row(array('Nombre', 'CC', 'AM', 'S', 'L', 'M', 'M', 'J', 'V', 'D', 'Total AM', 'Total V', 'Prestamo', 'Total'), false, false, null, 2, 1);
      $pdf->SetFont('Helvetica','', 8);
      foreach ($empleados_rancho as $key => $value)
      {
        $pdf->SetX(6);
        $pdf->Row(array(
          $value->nombre, '', '', '', '', '', '', '', '', '', '', '', '', '',
        ), false, true, null, 2, 1);
      }
      $pdf->SetFont('Helvetica','B', 8);
      $pdf->SetX(6);
      $pdf->Row(array(
        'TOTAL',
        $totales_rancho[12],
        $totales_rancho[0],
        $totales_rancho[1],
        $totales_rancho[2],
        $totales_rancho[3],
        $totales_rancho[4],
        $totales_rancho[5],
        $totales_rancho[6],
        $totales_rancho[7],
        $totales_rancho[8],
        $totales_rancho[9],
        $totales_rancho[10],
        $totales_rancho[11],
      ), false, true, null, 2, 1);
      $pdf->Output('Nomina.pdf', 'I');
  }

}
/* End of file nomina_fiscal_model.php */
/* Location: ./application/models/nomina_fiscal_model.php */