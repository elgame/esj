<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class nomina_fiscal_model extends CI_Model {

  public function listadoEmpleados($filtros = array())
  {
    // GET semana
    $filtros = array_merge(array(
      'semana'    => '',
      'empresaId' => '',
      'puestoId'  => '',
    ), $filtros);

    // Filtros
    $semana = $filtros['semana'] !== '' ? $this->fechasDeUnaSemana($filtros['semana']) : $this->semanaActualDelMes();

    $sql = '';
    if ($filtros['empresaId'] !== '')
    {
      $sql .= " AND id_empresa = {$filtros['empresaId']}";
    }

    if ($filtros['puestoId'] !== '')
    {
      $sql .= " AND id_puesto = {$filtros['puestoId']}";
    }


    $diaPrimeroDeLaSemana = $semana['fecha_inicio']; // fecha del primero dia de la semana.
    $diaUltimoDeLaSemana = $semana['fecha_final']; // fecha del ultimo dia de la semana.

    // Query para obtener los empleados de la semana.
    $query = $this->db->query(
      "SELECT id, (COALESCE(apellido_paterno, '') || ' ' || COALESCE(apellido_materno, '') || ' ' || nombre) as nombre,
              DATE(fecha_entrada) as fecha_entrada, id_puesto
       FROM usuarios
       WHERE esta_asegurado = 't' AND DATE(fecha_entrada) <= '{$diaUltimoDeLaSemana}' AND status = 't' {$sql}
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
          // Si la falta o incapacidad pertenece al usuario actual.
          if ($fi->id_usuario === $empleado->id)
          {
            // Si es una falta entra.s
            if ($fi->tipo === 'f')
            {
              // Agrega la falta al array.
              $empleado->dias_faltantes[] = array('fecha' => $fi->fecha_ini, 'tipo' => 'f');
            }

            // Si es una incapacidad.
            else
            {
              // Agrega el primer dia de la incapacidad al array.
              $empleado->dias_faltantes[] = array('fecha' => $fi->fecha_ini, 'tipo' => 'in');

              // Si son mas de 1 dia de incapacidad entra.
              if (strtotime($fi->fecha_ini) !== strtotime($fi->fecha_fin))
              {
                // Determina la diferencia de dias entre el primer dia de la incapacidad
                // y el ultimo
                $diffDias = String::diasEntreFechas($fi->fecha_ini, $fi->fecha_fin);

                // Obtiene los dias restantes de la incapacidad sin tomar en cuenta el primero dia.
                $diasSiguientes = String::obtenerSiguientesXDias(date('Y-m-d', strtotime($fi->fecha_ini . '+1 day')), $diffDias);

                // Agrega los dias faltantes al array.
                foreach ($diasSiguientes as $fechaDia)
                {
                  $empleado->dias_faltantes[] = array('fecha' => $fechaDia, 'tipo' => 'in');
                }
              }
            }
          }
        }
      }
    }

    return $empleados;
  }

  /**
   * Guarda faltas e incapacidades de una semana.
   *
   * @param array $datos
   * @param string $numSemana
   * @return array
   */
  public function addAsistencias($datos, $numSemana)
  {
    $semana = $this->fechasDeUnaSemana($numSemana);

    $nominaAsistencia = array();
    $key = 0; // Auxiliar para el posicionamiento del array $nominaAsistencia.
    $keyIncapacidad = 0; // Auxiliar para saber la posicion la incapacidad abierta.
    $fechaFinIncapacidadOk = true; // Indica si la fecha fin de la incapacidad ya fue establecida.
    $auxLastFechaIncapacidad = false;


    foreach ($datos as $empleadoId => $dias)
    {
      // Elimina las faltas e incapacidades de la semana a agregar del usuario.
      $this->db->where("id_usuario = {$empleadoId} AND
        DATE(fecha_ini) >= '{$semana['fecha_inicio']}' AND
        DATE(fecha_fin) <= '{$semana['fecha_final']}'"
      );
      $this->db->delete('nomina_asistencia');

      foreach ($dias as $fecha => $tipo)
      {
        if ($tipo === 'f')
        {
          // Si hay una incapacidad "abierta" le agrega la fecha fin.
          if ($fechaFinIncapacidadOk === false)
          {
            $nominaAsistencia[$keyIncapacidad]['fecha_fin'] = $fechaFinIncapacidad;
            $fechaFinIncapacidadOk = true; // Cierra la incapacidad.
          }

          $nominaAsistencia[] = array(
            'fecha_ini'  => $fecha,
            'fecha_fin'  => $fecha,
            'id_usuario' => $empleadoId,
            'tipo'       => $tipo,
          );

          $key++; // Incrementa el key.
        }
        else if ($tipo === 'in')
        {
          // Si no existe ninguna incapacidad por cerrar entonces agrega una nueva.
          if ($fechaFinIncapacidadOk)
          {
            $nominaAsistencia[] = array(
              'fecha_ini'  => $fecha,
              'fecha_fin'  => $fecha,
              'id_usuario' => $empleadoId,
              'tipo'       => $tipo,
            );

            // Cambia a false para saber que hay una incapacidad "abierta".
            $fechaFinIncapacidadOk = false;

            // Iguala la fecha fin de incapacidad por si la incapacidad es de
            // solo 1 dia.
            $fechaFinIncapacidad = $fecha;

            // Key del array $nominaAsistencia donde se encuentra la incapacidad "abierta".
            $keyIncapacidad = $key;

            $key++; // Incrementa el key.
          }

          // Si hay alguna incapacidad "abierta" entonces va guardando la fecha
          // del dia como la ultima de la incapacidad.
          else
          {
            $fechaFinIncapacidad = $fecha;
          }
        }

        // Si es una Asistencia entra.
        else
        {
          // Si hay una incapacidad "abierta" le agrega la fecha fin.
          if ($fechaFinIncapacidadOk === false)
          {
            $nominaAsistencia[$keyIncapacidad]['fecha_fin'] = $fechaFinIncapacidad;
            $fechaFinIncapacidadOk = true; // Cierra la incapacidad.
          }
        }
      }
    }

    // Si existen faltas o incapacidades las agrega.
    if (count($nominaAsistencia) > 0)
    {
      $this->db->insert_batch('nomina_asistencia', $nominaAsistencia);
    }

    return array('passes' => true);
  }


  /**
   * Agrega bonos y otros.
   *
   * @param string $empleadoId
   * @param array  $datos
   * @return array
   */
  public function addBonosOtros($empleadoId, array $datos, $numSemana)
  {

    if (isset($datos['existentes']))
    {
      $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($numSemana);
      $this->db->where("id_usuario = {$empleadoId} AND DATE(fecha) >= '{$semana['fecha_inicio']}' AND DATE(fecha) <= '{$semana['fecha_final']}'");
      $this->db->delete('nomina_percepciones_ext');
    }

    $insertData = array();
    foreach ($datos['tipo'] as $key => $tipo)
    {
      // si el tipo es un bono.
      if ($tipo === 'bono')
      {
        $insertData[] = array(
          'id_usuario' => $empleadoId,
          'fecha'      => $datos['fecha'][$key],
          'bono'       => $datos['cantidad'][$key],
          'otro'       => 0,
        );
      }
      else
      {
        $insertData[] = array(
          'id_usuario' => $empleadoId,
          'fecha'      => $datos['fecha'][$key],
          'otro'       => $datos['cantidad'][$key],
          'bono'       => 0,
        );
      }
    }

    if (count($insertData) > 0)
    {
      $this->db->insert_batch('nomina_percepciones_ext', $insertData);
    }

    return array('passes' => true);
  }

  /**
   * Obtiene los bonos y otros de un empleado.
   *
   * @param  string $empleadoId
   * @param  string $numSemana
   * @return array
   */
  public function getBonosOtrosEmpleado($empleadoId, $numSemana)
  {
    $semana = $this->fechasDeUnaSemana($numSemana);
    $query = $this->db->query(
      "SELECT id_usuario, DATE(fecha) as fecha, bono, otro
       FROM nomina_percepciones_ext
       WHERE id_usuario = {$empleadoId} AND DATE(fecha) >= '{$semana['fecha_inicio']}' AND DATE(fecha) <= '{$semana['fecha_final']}'
       ORDER BY DATE(fecha) ASC
      ");

    $bonosOtros = array();
    if ($query->num_rows() > 0)
    {
      $bonosOtros = $query->result();
    }

    return $bonosOtros;
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
    return String::obtenerSemanasDelAnioV2(date('Y'), 0, 5);
  }

  /**
   * Obtiene las semanas que van del mes actual.
   *
   * @return array
   */
  public function semanasDelMesActual()
  {
    return String::obtenerSemanasDelAnioV2(date('Y'), date('m'), 5);
  }

  /**
   * Obtiene la semana actual del mes actual.
   *
   * @return array
   */
  public function semanaActualDelMes()
  {
    return end(String::obtenerSemanasDelAnioV2(date('Y'), 0, 5));
  }

  /**
   * Obtiene las fechas de una semana en especifico.
   *
   * @param  string $semanaABuscar
   * @return array
   */
  public function fechasDeUnaSemana($semanaABuscar)
  {
    return String::obtenerSemanasDelAnioV2(date('Y'), 0, 5, false, $semanaABuscar);
  }

}

/* End of file nomina_fiscal_model.php */
/* Location: ./application/models/nomina_fiscal_model.php */