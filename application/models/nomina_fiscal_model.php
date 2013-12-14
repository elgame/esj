<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class nomina_fiscal_model extends CI_Model {

  public function listadoAsistencias($filtros = array())
  {
    // GET semana
    $filtros = array_merge(array(
      'semana' => '',
    ), $filtros);

    $semana = $filtros['semana'] !== '' ? $this->fechasDeUnaSemana($filtros['semana']) : $this->semanaActualDelMes();

    $diaPrimeroDeLaSemana = $semana['fecha_inicio'];
    $diaUltimoDeLaSemana = $semana['fecha_final'];

    // Query para obtener los empleados de la semana.
    $query = $this->db->query(
      "SELECT id, (COALESCE(apellido_paterno, '') || ' ' || COALESCE(apellido_materno, '') || ' ' || nombre) as nombre, DATE(fecha_entrada) as fecha_entrada
       FROM usuarios
       WHERE esta_asegurado = 't' AND DATE(fecha_entrada) <= '{$diaUltimoDeLaSemana}' AND status = 't'
       ORDER BY apellido_paterno ASC
      ");
    $empleados = $query->num_rows() > 0 ? $query->result() : array();

    $query->free_result();
    // Query para obtener las faltas o incapacidades de la semana.
    $query = $this->db->query(
      "SELECT id_usuario, DATE(fecha_ini) as fecha_ini, DATE(fecha_fin) as fecha_fin, tipo
       FROM nomina_asistencia
       WHERE DATE(fecha_ini) >= '{$diaPrimeroDeLaSemana}' AND DATE(fecha_fin) <= '{$diaUltimoDeLaSemana}'
       ORDER BY id_usuario, DATE(fecha_ini) ASC
      ");

    // Si hubo al menos una falta o incapacidad en la semana.
    if ($query->num_rows() > 0)
    {
      $faltasOIncapacidades = $query->result();

      // Recorre los empleados para ver cual tuvo faltas o incapacidades.
      foreach ($empleados as $empleado)
      {
        $empleado->dias_faltantes = array();

        foreach ($faltasOIncapacidades as $fi)
        {
          if ($fi->id_usuario === $empleado->id)
          {
            $empleado->dias_faltantes[] = $fi;
          }
        }
      }
    }

    // echo "<pre>";
    //   var_dump($empleados);
    // echo "</pre>";exit;

    return $empleados;
  }


  /*
   |------------------------------------------------------------------------
   | Helpers
   |------------------------------------------------------------------------
   */

  /**
   * Obtiene las semanas que van del año.
   *
   * @return array
   */
  public function semanasDelAno()
  {
    return String::obtenerSemanasDelAnio(date('Y'));
  }

  /**
   * Obtiene las semanas que van del mes actual.
   *
   * @return array
   */
  public function semanasDelMesActual()
  {
    return String::obtenerSemanasDelAnio(date('Y'), false, date('m'));
  }

  /**
   * Obtiene la semana actual del mes actual.
   *
   * @return array
   */
  public function semanaActualDelMes()
  {
    return end(String::obtenerSemanasDelAnio(date('Y'), false, date('m')));
  }

  /**
   * Obtiene las fechas de una semana en especifico.
   *
   * @param  string $semanaABuscar
   * @return array
   */
  public function fechasDeUnaSemana($semanaABuscar)
  {
    $semanasDelAno = $this->semanasDelAno();

    foreach ($semanasDelAno as $semana)
    {
      if ($semana['semana'] == $semanaABuscar)
      {
        return $semana;
      }
    }
  }

}

/* End of file nomina_fiscal_model.php */
/* Location: ./application/models/nomina_fiscal_model.php */