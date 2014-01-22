<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class nomina_fiscal_model extends CI_Model {

  public function configuraciones()
  {
    $configuraciones['nomina'] = $this->getConfigNomina();
    $configuraciones['vacaciones'] = $this->getConfigNominaVacaciones();
    $configuraciones['salarios_zonas'] = $this->getConfigSalariosZonas();
    $configuraciones['cuentas_contpaq'] =  array(
      'sueldo'           => $this->getSueldoCuentaContpaq(),
      'horas_extras'     => $this->getHorasExtrasCuentaContpaq(),
      'vacaciones'       => $this->getVacacionesCuentaContpaq(),
      'prima_vacacional' => $this->getPrimaVacacionalCuentaContpaq(),
      'aguinaldo'        => $this->getAguinaldoCuentaContpaq(),
      'ptu'              => $this->getPtuCuentaContpaq(),
      'imss'             => $this->getInfonavitCuentaContpaq(),
      'rcv'              => $this->getRcvCuentaContpaq(),
      'infonavit'        => $this->getInfonavitCuentaContpaq(),
      'otros'            => $this->getInfonavitCuentaContpaq(),
    );
    $configuraciones['tablas_isr'] = $this->getTablasIsr();

    return $configuraciones;
  }

  public function nomina($configuraciones, array $filtros = array(), $empleadoId = null, $horasExtrasDinero = null, $descuentoPlayeras = null,
                         $subsidio = null, $isr = null, $utilidadEmpresa = null)
  {
    $this->load->library('nomina');

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

    $diaPrimeroDeLaSemana = $semana['fecha_inicio']; // fecha del primero dia de la semana.
    $diaUltimoDeLaSemana = $semana['fecha_final']; // fecha del ultimo dia de la semana.
    $anio = $semana['anio'];
    $anioPtu = $anio - 1;

    $horasExtrasDinero = $horasExtrasDinero ?: 0;
    $descuentoPlayeras = $descuentoPlayeras ?: 0;
    $utilidadEmpresa = $utilidadEmpresa ?: 0;

    // Query para obtener los empleados de la semana de la nomina.
    $query = $this->db->query(
      "SELECT u.id,
              (COALESCE(u.apellido_paterno, '') || ' ' || COALESCE(u.apellido_materno, '') || ' ' || u.nombre) as nombre,
              u.curp,
              DATE(u.fecha_entrada) as fecha_entrada,
              u.id_puesto,
              u.salario_diario,
              u.salario_diario_real,
              u.infonavit,
              u.regimen_contratacion,
              up.nombre as puesto,
              7  as dias_trabajados,
              (SELECT COALESCE(DATE_PART('DAY', SUM((fecha_fin - fecha_ini) + '1 day'))::integer, 0) as dias
              FROM nomina_asistencia
              WHERE DATE(fecha_ini) >= '{$anio}-01-01' AND DATE(fecha_fin) <= '{$anio}-12-31' AND id_usuario = u.id) as dias_faltados_anio,
              COALESCE(nf.horas_extras, {$horasExtrasDinero}) as horas_extras_dinero,
              COALESCE(nf.descuento_playeras, {$descuentoPlayeras}) as descuento_playeras,
              '{$diaPrimeroDeLaSemana}' as fecha_inicial_pago,
              '{$diaUltimoDeLaSemana}' as fecha_final_pago,
              COALESCE((SELECT SUM(bono) as bonos FROM nomina_percepciones_ext WHERE id_usuario = u.id AND bono != 0  AND DATE(fecha) >= '{$diaPrimeroDeLaSemana}' AND DATE(fecha) <= '{$diaUltimoDeLaSemana}'), 0) as bonos,
              COALESCE((SELECT SUM(otro) as otros FROM nomina_percepciones_ext WHERE id_usuario = u.id AND otro != 0 AND DATE(fecha) >= '{$diaPrimeroDeLaSemana}' AND DATE(fecha) <= '{$diaUltimoDeLaSemana}'), 0) as otros,
              COALESCE(nf.prestamos, 0) as nomina_fiscal_prestamos,
              COALESCE(nf.vacaciones, 0) as nomina_fiscal_vacaciones,
              COALESCE(nf.aguinaldo, 0) as nomina_fiscal_aguinaldo,
              COALESCE(nf.subsidio, 0) as nomina_fiscal_subsidio,
              COALESCE(nf.isr, 0) as nomina_fiscal_isr,
              COALESCE(nf.ptu, 0) as nomina_fiscal_ptu,
              COALESCE(nf.total_percepcion, 0) as nomina_fiscal_total_percepciones,
              COALESCE(nf.total_deduccion, 0) as nomina_fiscal_total_deducciones,
              COALESCE(nf.total_neto, 0) as nomina_fiscal_total_neto,
              COALESCE(nf.uuid, 'false') as esta_generada,
              COALESCE(nf.utilidad_empresa, {$utilidadEmpresa}) as utilidad_empresa,
              (SELECT COALESCE(SUM(total_percepcion), 0) FROM nomina_fiscal WHERE anio = {$anioPtu}) as ptu_percepciones_empleados,
              (SELECT COALESCE(SUM(dias_trabajados), 0) FROM nomina_fiscal WHERE anio = {$anioPtu}) as ptu_dias_trabajados_empleados,
              (SELECT COALESCE(SUM(total_percepcion), 0) FROM nomina_fiscal WHERE anio = {$anioPtu} AND id_empleado = u.id) as ptu_percepciones_empleado,
              (SELECT COALESCE(SUM(dias_trabajados), 0) FROM nomina_fiscal WHERE anio = {$anioPtu} AND id_empleado = u.id) as ptu_dias_trabajados_empleado,
              u.rfc
       FROM usuarios u
       LEFT JOIN usuarios_puestos up ON up.id_puesto = u.id_puesto
       LEFT JOIN nomina_fiscal nf ON nf.id_empleado = u.id AND nf.id_empresa = {$filtros['empresaId']} AND nf.anio = {$anio} AND nf.semana = {$semana['semana']}
       WHERE u.esta_asegurado = 't' AND DATE(u.fecha_entrada) <= '{$diaUltimoDeLaSemana}' AND u.status = 't' {$sql}
       ORDER BY u.apellido_paterno ASC
    ");
    $empleados = $query->num_rows() > 0 ? $query->result() : array();

    $query->free_result();
    // Query para obtener las faltas o incapacidades de la semana.
    $queryFi = $this->db->query(
      "SELECT na.id_usuario,
              DATE(na.fecha_ini) as fecha_ini,
              DATE(na.fecha_fin) as fecha_fin,
              na.tipo,
              nsc.clave as sat_clave,
              nsc.nombre as sat_descripcion
       FROM nomina_asistencia na
       LEFT JOIN nomina_sat_claves nsc ON nsc.id_clave = na.id_clave
       WHERE DATE(na.fecha_ini) >= '{$diaPrimeroDeLaSemana}' AND DATE(na.fecha_fin) <= '{$diaUltimoDeLaSemana}'
       ORDER BY na.id_usuario, DATE(na.fecha_ini) ASC
    ");

    $query->free_result();
    // Query para obtener los prestamos.
    $queryPrestamos = $this->db->query(
      "SELECT np.id_usuario,
              np.id_prestamo,
              np.prestado,
              np.pago_semana,
              np.status,
              DATE(np.fecha) as fecha,
              DATE(np.inicio_pago) as inicio_pago,
              COALESCE(SUM(nfp.monto), 0) as total_pagado
        FROM nomina_prestamos np
        LEFT JOIN nomina_fiscal_prestamos nfp ON nfp.id_prestamo = np.id_prestamo
        WHERE np.status = 't' AND DATE(np.inicio_pago) <= '{$diaUltimoDeLaSemana}'
        GROUP BY np.id_usuario, np.id_prestamo, np.prestado, np.pago_semana, 
          np.status, DATE(np.fecha), DATE(np.inicio_pago)
    ");

      // Recorre los empleados para sacar las faltas|incapacidades y los prestamos
      // del empleado.

      $faltasOIncapacidades = array();
      // Si hay faltas o incapacidades entra.
      if ($queryFi->num_rows() > 0)
      {
        $faltasOIncapacidades = $queryFi->result();
      }

      $prestamos = array();
      // Si hay prestamos entra.
      if ($queryPrestamos->num_rows() > 0)
      {
        $prestamos = $queryPrestamos->result();
      }

      // Recorre los empleados para obtener las faltas|incapacidades y los
      // prestamos de cada uno.
      foreach ($empleados as $empleado)
      {
        $empleado->incapacidades = array();
        $empleado->prestamos = array();

        if (count($faltasOIncapacidades) > 0)
        {
          foreach ($faltasOIncapacidades as $fi)
          {
            $diasIncapacidad = 0;

            // Si la falta o incapacidad pertenece al usuario actual.
            if ($fi->id_usuario === $empleado->id)
            {
              // Si es una falta entonces le resta 1 dia a los dias_trabajados.
              if ($fi->tipo === 'f')
              {
                $empleado->dias_trabajados -= 1;
              }

              // Si es una incapacidad entra.
              else
              {
                // Determina la diferencia de dias entre el primer dia de la incapacidad y el ultimo.
                $diasIncapacidad = intval(String::diasEntreFechas($fi->fecha_ini, $fi->fecha_fin)) + 1;

                // Le resta a los dias trabajados los de incapacidad.
                $empleado->dias_trabajados -= $diasIncapacidad;

                // Agrega esa incapacidad al array de incapacidades.
                // el descuento es multiplicado por el salario_diario que es el salario con el que
                // se hara el timbrado.
                $empleado->incapacidades[] = array(
                  'diasIncapacidad' => $diasIncapacidad,
                  'tipoIncapacidad' => $fi->sat_clave,
                  'descuento' => floatval($diasIncapacidad) * floatval($empleado->salario_diario)
                );
              }
            }
          }
        }

        if (count($prestamos) > 0)
        {
          foreach ($prestamos as $key => $prestamo)
          {
            if ($prestamo->id_usuario === $empleado->id)
            {
              // Obtiene lo que falta de pagar.
              $diff = $prestamo->prestado - $prestamo->total_pagado;

              // Si lo que falta de pagar es mayor o igual al pago que
              // se da semalmente entra.
              if ($diff >= $prestamo->pago_semana)
              {
                $prestamo->pago_semana_descontar = $prestamo->pago_semana;
              }
              else
              {
                $prestamo->pago_semana_descontar = $diff;
              }

              $empleado->prestamos[] = (array)$prestamo;
              unset($prestamos[$key]);
            }
          }
        }
      }

    foreach ($empleados as $key => $empleado)
    {
      $nomina = $this->nomina
        ->setEmpleado($empleado)
        ->setEmpresaConfig($configuraciones['nomina'][0])
        ->setVacacionesConfig($configuraciones['vacaciones'])
        ->setSalariosZonas($configuraciones['salarios_zonas'][0])
        ->setClavesPatron($configuraciones['cuentas_contpaq'])
        ->setTablasIsr($configuraciones['tablas_isr'])
        ->setSubsidioIsr($subsidio, $isr)
        ->procesar();
    }
    // echo "<pre>";
    //   var_dump($empleados);
    // echo "</pre>";exit;
    return $empleados;
  }

  // public function add_nominas($datos, $empresaId)
  // {
  //   $startTime = new DateTime(date('Y-m-d H:i:s'));

  //   $this->load->library('cfdi');
  //   $this->load->library('facturartebarato_api');
  //   $this->load->model('empresas_model');
  //   $this->load->model('usuarios_model');

  //   // Obtiene la informacion de la empresa.
  //   $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);

  //   // Obtiene el certificado.
  //   $certificado = $this->cfdi->obtenCertificado($this->db
  //     ->select('cer')
  //     ->from("empresas")
  //     ->where("id_empresa", $empresaId)
  //     ->get()->row()->cer
  //   );

  //   // Obtiene las configuraciones.
  //   $configuraciones = $this->configuraciones();

  //   // Almacenara los datos de las nominas de cada empleado para despues
  //   // insertarlas.
  //   $nominasEmpleados = array();

  //   // Almacenara los datos de los prestamos de cada empleado para despues
  //   // insertarlos.
  //   $prestamosEmpleados = array();

  //   // Obtiene el rango de fechas de la semana.
  //   $fechasSemana = $this->fechasDeUnaSemana($datos['numSemana']);

  //   // Auxiliar para saber si hubo un error al momento de timbrar alguna nomina.
  //   $errorTimbrar = false;

  //   // Recorre los empleados para agregar y timbrar sus nominas.
  //   foreach ($datos['empleado_id'] as $key => $empleadoId)
  //   {
  //     // Si la nomina del empleado no se ha generado entonces entra.
  //     if ($datos['generar_nomina'][$key] === '1')
  //     {
  //       $empleado = $this->usuarios_model->get_usuario_info($empleadoId, true);

  //       $empleadoNomina = $this->nomina(
  //         $configuraciones,
  //         array('semana' => $datos['numSemana'], 'empresaId' => $empresaId),
  //         $empleadoId,
  //         $datos['horas_extras'][$key],
  //         $datos['descuento_playeras'][$key],
  //         $datos['subsidio'][$key],
  //         $datos['isr'][$key],
  //         $datos['utilidad_empresa']
  //       );
  //       // unset($empleadoNomina[0]->nomina->percepciones['subsidio']);
  //       // unset($empleadoNomina[0]->nomina->percepciones['ptu']);
  //       // unset($empleadoNomina[0]->nomina->deducciones['isr']);

  //       $valorUnitario = 0; // Total de las Percepciones.

  //       // Recorre las percepciones del empleado.
  //       foreach ($empleadoNomina[0]->nomina->percepciones as $tipoPercepcion => $percepcion)
  //       {
  //         // Si activaron las vacaciones entonces suma las vacaciones y la prima vacacional.
  //         if ($tipoPercepcion === 'vacaciones' || $tipoPercepcion === 'prima_vacacional')
  //         {
  //           if ($datos['con_vacaciones'][$key] === '1' && $empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]['total'] != 0)
  //           {
  //             $valorUnitario += $percepcion['total'];
  //             unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]['total']);
  //           }
  //           else
  //             unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]);
  //         }
  //         // Si el tipo de percepcion es aguinaldo
  //         else if ($tipoPercepcion === 'aguinaldo')
  //         {
  //           // Si activarion el aguinaldo entonces lo suma.
  //           if ($datos['con_aguinaldo'] === '1')
  //           {
  //             $valorUnitario += $percepcion['total'];
  //             unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]['total']);
  //           }
  //           else
  //             unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]);
  //         }
  //         // Si es el sueldo u horas extras los suma directo.
  //         else
  //         {
  //           $valorUnitario += $percepcion['total'];
  //           unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]['total']);
  //         }
  //       }

  //       $isr = 0; // retenciones
  //       $descuento = 0; // Total de las deducciones(gravado + excento) excepto el ISR.
  //       // Recorre las deducciones del empleado.
  //       foreach ($empleadoNomina[0]->nomina->deducciones as $tipoDeduccion => $deduccion)
  //       {
  //         if ($tipoDeduccion !== 'isr')
  //         {
  //           $descuento += $deduccion['total'];
  //         }
  //         else
  //         {
  //           $isr = $deduccion['total'];
  //         }
  //         unset($empleadoNomina[0]->nomina->deducciones[$tipoDeduccion]['total']);
  //       }

  //       // Le suma al imss el rcv, para tener solamente la deduccion imss.
  //       $empleadoNomina[0]->nomina->deducciones['imss']['ImporteExcento'] += $empleadoNomina[0]->nomina->deducciones['rcv']['ImporteExcento'];
  //       unset($empleadoNomina[0]->nomina->deducciones['rcv']);

  //       // Obtiene los datos para la cadena original.
  //       $datosCadenaOriginal = $this->datosCadenaOriginal($empleado, $empresa);
  //       $datosCadenaOriginal['subTotal'] = $valorUnitario;
  //       $datosCadenaOriginal['descuento'] = $descuento;
  //       $datosCadenaOriginal['retencion'][0]['importe'] = $isr;
  //       $datosCadenaOriginal['totalImpuestosRetenidos'] = $isr;
  //       $datosCadenaOriginal['total'] = round($valorUnitario - $descuento - $isr, 4);

  //       // Concepto de la nomina.
  //       $concepto = array(array(
  //         'cantidad'         => 1,
  //         'unidad'           => 'Servicio',
  //         'descripcion'      => 'Pago de nomina',
  //         'valorUnitario'    => $valorUnitario,
  //         'importe'          => $valorUnitario,
  //         'idClasificacion' => null,
  //       ));

  //       $datosCadenaOriginal['concepto'] = $concepto;

  //       // Obtiene la cadena original para la nomina.
  //       $cadenaOriginal = $this->cfdi->obtenCadenaOriginal($datosCadenaOriginal, true, $empleadoNomina);

  //       // Genera el sello en base a la cadena original.
  //       $sello = $this->cfdi->obtenSello($cadenaOriginal['cadenaOriginal']);

  //       // Construye los datos para el xml.
  //       $datosXML = $this->datosXml($cadenaOriginal['datos'], $empresa, $empleado, $sello, $certificado);
  //       $datosXML['concepto'] = $concepto;

  //       $archivo = $this->cfdi->generaArchivos($datosXML, true, $fechasSemana);

  //       $result = $this->timbrar($archivo['pathXML']);
  //       // echo "<pre>";
  //       //   var_dump($archivo, $result, $cadenaOriginal);
  //       // echo "</pre>";exit;

  //       // Si la nomina se timbro entonces agrega al array nominas la nomina del
  //       // empleado para despues insertarla en la bdd.
  //       if ($result['result']->status)
  //       {
  //         $vacaciones = isset($empleadoNomina[0]->nomina->percepciones['vacaciones'])
  //           ? $empleadoNomina[0]->nomina->percepciones['vacaciones']['ImporteGravado'] +
  //             $empleadoNomina[0]->nomina->percepciones['vacaciones']['ImporteExcento']
  //           : 0;

  //         $primaVacacionalGravable = isset($empleadoNomina[0]->nomina->percepciones['prima_vacacional'])
  //           ? $empleadoNomina[0]->nomina->percepciones['prima_vacacional']['ImporteGravado']
  //           : 0;

  //         $primaVacacionalExcento = isset($empleadoNomina[0]->nomina->percepciones['prima_vacacional'])
  //           ? $empleadoNomina[0]->nomina->percepciones['prima_vacacional']['ImporteExcento']
  //           : 0;

  //         $primaVacacional = isset($empleadoNomina[0]->nomina->percepciones['prima_vacacional'])
  //           ? $empleadoNomina[0]->nomina->percepciones['prima_vacacional']['ImporteGravado'] +
  //             $empleadoNomina[0]->nomina->percepciones['prima_vacacional']['ImporteExcento']
  //           : 0;

  //         $aguinaldoGravable = isset($empleadoNomina[0]->nomina->percepciones['aguinaldo'])
  //           ? $empleadoNomina[0]->nomina->percepciones['aguinaldo']['ImporteGravado']
  //           : 0;

  //         $aguinaldoExcento = isset($empleadoNomina[0]->nomina->percepciones['aguinaldo'])
  //           ? $empleadoNomina[0]->nomina->percepciones['aguinaldo']['ImporteExcento']
  //           : 0;

  //         $aguinaldo = isset($empleadoNomina[0]->nomina->percepciones['aguinaldo'])
  //           ? $empleadoNomina[0]->nomina->percepciones['aguinaldo']['ImporteGravado'] +
  //             $empleadoNomina[0]->nomina->percepciones['aguinaldo']['ImporteExcento']
  //           : 0;

  //         $imss = $empleadoNomina[0]->nomina->deducciones['imss']['ImporteGravado'] +
  //                 $empleadoNomina[0]->nomina->deducciones['imss']['ImporteExcento'];

  //         $infonavit = $empleadoNomina[0]->nomina->deducciones['infonavit']['ImporteGravado'] +
  //                      $empleadoNomina[0]->nomina->deducciones['infonavit']['ImporteExcento'];

  //         $ptuGravado = isset($empleadoNomina[0]->nomina->percepciones['ptu'])
  //           ? $empleadoNomina[0]->nomina->percepciones['ptu']['ImporteGravado']
  //           : 0;

  //         $ptuExcento = isset($empleadoNomina[0]->nomina->percepciones['ptu'])
  //           ? $empleadoNomina[0]->nomina->percepciones['ptu']['ImporteExcento']
  //           : 0;

  //         $ptu = $ptuGravado + $ptuExcento;

  //         $totalPrestamos = 0;
  //         // Recorre los prestamos del empleado para
  //         foreach ($empleadoNomina[0]->prestamos as $prestamo)
  //         {
  //           $totalPrestamos += floatval($prestamo['pago_semana_descontar']);

  //           $prestamosEmpleados[] = array(
  //             'id_empleado' => $empleadoId,
  //             'id_empresa' => $empresaId,
  //             'anio' => date('Y'),
  //             'semana' => $datos['numSemana'],
  //             'id_prestamo' => $prestamo['id_prestamo'],
  //             'monto' => $prestamo['pago_semana_descontar'],
  //           );

  //           // Suma lo que lleva pagado mas lo que se esta abonando.
  //           $totalAbonado = floatval($prestamo['total_pagado']) + floatval($prestamo['pago_semana_descontar']);

  //           // Si ya termino de pagar el prestamo entonces le cambia el status.
  //           if ($totalAbonado >= floatval($prestamo['prestado']))
  //           {
  //             $this->db->update('nomina_prestamos', array('status' => 'f'), array('id_prestamo' => $prestamo['id_prestamo']));
  //           }
  //         }

  //         $totalNoFiscal = floatval($datos['total_no_fiscal'][$key]);

  //         $nominasEmpleados[] = array(
  //           'id_empleado' => $empleadoId,
  //           'id_empresa' => $empresaId,
  //           'anio' => $fechasSemana['anio'],
  //           'semana' => $datos['numSemana'],
  //           'fecha_inicio' => $fechasSemana['fecha_inicio'],
  //           'fecha_final' => $fechasSemana['fecha_final'],
  //           'dias_trabajados' => $empleadoNomina[0]->dias_trabajados,
  //           'salario_diario' => $empleadoNomina[0]->salario_diario,
  //           'salario_integral' => $empleadoNomina[0]->nomina->salario_diario_integrado,
  //           'subsidio' => $datos['subsidio'][$key],
  //           'sueldo_semanal' => $empleadoNomina[0]->nomina->percepciones['sueldo']['ImporteGravado'],
  //           'bonos' => $empleadoNomina[0]->bonos,
  //           'otros' => $empleadoNomina[0]->otros,
  //           'subsidio_pagado' => 0,
  //           'vacaciones' => $vacaciones,
  //           'prima_vacacional_grabable' => $primaVacacionalGravable,
  //           'prima_vacacional_exento' => $primaVacacionalExcento,
  //           'prima_vacacional' => $primaVacacional,
  //           'aguinaldo_grabable' => $aguinaldoGravable,
  //           'aguinaldo_exento' => $aguinaldoExcento,
  //           'aguinaldo' => $aguinaldo,
  //           'total_percepcion' => $valorUnitario,
  //           'imss' => $imss,
  //           'vejez' => 0,
  //           'isr' => $datos['isr'][$key],
  //           'infonavit' => $infonavit,
  //           'subsidio_cobrado' => 0,
  //           'prestamos' => $totalPrestamos,
  //           'total_deduccion' => $descuento + $isr,
  //           'total_neto' => $valorUnitario - $descuento - $isr,
  //           'id_empleado_creador' => $this->session->userdata('id_usuario'),
  //           'ptu_exento' => $ptuExcento,
  //           'ptu_grabable' => $ptuGravado,
  //           'ptu' => $ptu,
  //           'id_puesto' => $empleadoNomina[0]->id_puesto,
  //           'salario_real' => $empleadoNomina[0]->salario_diario_real,
  //           'sueldo_real' => $empleadoNomina[0]->salario_diario_real * $empleadoNomina[0]->dias_trabajados,
  //           'total_no_fiscal' => $totalNoFiscal,
  //           'horas_extras' => $empleadoNomina[0]->horas_extras_dinero,
  //           'horas_extras_grabable' => $empleadoNomina[0]->nomina->percepciones['horas_extras']['ImporteGravado'],
  //           'horas_extras_excento' => $empleadoNomina[0]->nomina->percepciones['horas_extras']['ImporteExcento'],
  //           'descuento_playeras' => $datos['descuento_playeras'][$key],
  //           'xml' => $result['xml'],
  //           'uuid' => $result['uuid'],
  //           'utilidad_empresa' => $empleadoNomina[0]->utilidad_empresa
  //         );
  //       }
  //       else
  //       {
  //         $errorTimbrar = true;
  //       }

  //       // echo "<pre>";
  //       //   var_dump($datosXML, $archivo);
  //       // echo "</pre>";exit;

  //       // echo "<pre>";
  //       //   var_dump($empleado, $cadenaOriginal, $sello, $certificado);
  //       // echo "</pre>";exit;
  //     }
  //   }

  //   // Inserta las nominas.
  //   if (count($nominasEmpleados) > 0)
  //   {
  //     $this->db->insert_batch('nomina_fiscal', $nominasEmpleados);
  //   }

  //   // Inserta los abonos de los prestamos.
  //   if (count($prestamosEmpleados) > 0)
  //   {
  //     $this->db->insert_batch('nomina_fiscal_prestamos', $prestamosEmpleados);
  //   }

  //   $endTime = new DateTime(date('Y-m-d H:i:s'));

  //   echo "<pre>";
  //     var_dump($startTime->diff($endTime)->format('%H:%I:%S'));
  //   echo "</pre>";exit;

  //   return array('errorTimbrar' => $errorTimbrar);
  // }

  public function add_nominas($datos, $empresaId, $empleadoId)
  {
    // echo "<pre>";
    //   var_dump($datos, $empresaId, $empleadoId);
    // echo "</pre>";exit;
    // $startTime = new DateTime(date('Y-m-d H:i:s'));

    $this->load->library('cfdi');
    $this->load->library('facturartebarato_api');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');

    // Obtiene la informacion de la empresa.
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);

    // Obtiene el certificado.
    $certificado = $this->cfdi->obtenCertificado($this->db
      ->select('cer')
      ->from("empresas")
      ->where("id_empresa", $empresaId)
      ->get()->row()->cer
    );

    // Obtiene las configuraciones.
    $configuraciones = $this->configuraciones();

    // Almacenara los datos de las nominas de cada empleado para despues
    // insertarlas.
    $nominasEmpleados = array();

    // Almacenara los datos de los prestamos de cada empleado para despues
    // insertarlos.
    $prestamosEmpleados = array();

    // Obtiene el rango de fechas de la semana.
    $fechasSemana = $this->fechasDeUnaSemana($datos['numSemana']);

    // Auxiliar para saber si hubo un error al momento de timbrar alguna nomina.
    $errorTimbrar = false;

    // Recorre los empleados para agregar y timbrar sus nominas.
    // foreach ($datos['empleado_id'] as $key => $empleadoId)
    // {
      // Si la nomina del empleado no se ha generado entonces entra.
      if ($datos['generar_nomina'] === '1')
      {
        // $empleado = $this->usuarios_model->get_usuario_info($empleadoId, true);
        $empleado = $this->usuarios_model->get_usuario_info($empleadoId, true);

        $empleadoNomina = $this->nomina(
          $configuraciones,
          array('semana' => $datos['numSemana'], 'empresaId' => $empresaId),
          $empleadoId,
          $datos['horas_extras'],
          $datos['descuento_playeras'],
          $datos['subsidio'],
          $datos['isr'],
          $datos['utilidad_empresa']
        );
        // unset($empleadoNomina[0]->nomina->percepciones['subsidio']);
        // unset($empleadoNomina[0]->nomina->percepciones['ptu']);
        // unset($empleadoNomina[0]->nomina->deducciones['isr']);

        $valorUnitario = 0; // Total de las Percepciones.

        // Recorre las percepciones del empleado.
        foreach ($empleadoNomina[0]->nomina->percepciones as $tipoPercepcion => $percepcion)
        {
          // Si activaron las vacaciones entonces suma las vacaciones y la prima vacacional.
          if ($tipoPercepcion === 'vacaciones' || $tipoPercepcion === 'prima_vacacional')
          {
            if ($datos['con_vacaciones'] === '1' && $empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]['total'] != 0)
            {
              $valorUnitario += $percepcion['total'];
              unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]['total']);
            }
            else
              unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]);
          }
          // Si el tipo de percepcion es aguinaldo
          else if ($tipoPercepcion === 'aguinaldo')
          {
            // Si activarion el aguinaldo entonces lo suma.
            if ($datos['con_aguinaldo'] === '1')
            {
              $valorUnitario += $percepcion['total'];
              unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]['total']);
            }
            else
              unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]);
          }
          // Si es el sueldo u horas extras los suma directo.
          else
          {
            $valorUnitario += $percepcion['total'];
            unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]['total']);
          }
        }

        $isr = 0; // retenciones
        $descuento = 0; // Total de las deducciones(gravado + excento) excepto el ISR.
        // Recorre las deducciones del empleado.
        foreach ($empleadoNomina[0]->nomina->deducciones as $tipoDeduccion => $deduccion)
        {
          if ($tipoDeduccion !== 'isr')
          {
            $descuento += $deduccion['total'];
          }
          else
          {
            $isr = $deduccion['total'];
          }
          unset($empleadoNomina[0]->nomina->deducciones[$tipoDeduccion]['total']);
        }

        // Le suma al imss el rcv, para tener solamente la deduccion imss.
        $empleadoNomina[0]->nomina->deducciones['imss']['ImporteExcento'] += $empleadoNomina[0]->nomina->deducciones['rcv']['ImporteExcento'];
        unset($empleadoNomina[0]->nomina->deducciones['rcv']);

        // Obtiene los datos para la cadena original.
        $datosCadenaOriginal = $this->datosCadenaOriginal($empleado, $empresa);
        $datosCadenaOriginal['subTotal'] = $valorUnitario;
        $datosCadenaOriginal['descuento'] = $descuento;
        $datosCadenaOriginal['retencion'][0]['importe'] = $isr;
        $datosCadenaOriginal['totalImpuestosRetenidos'] = $isr;
        $datosCadenaOriginal['total'] = round($valorUnitario - $descuento - $isr, 4);

        // Concepto de la nomina.
        $concepto = array(array(
          'cantidad'         => 1,
          'unidad'           => 'Servicio',
          'descripcion'      => 'Pago de nomina',
          'valorUnitario'    => $valorUnitario,
          'importe'          => $valorUnitario,
          'idClasificacion' => null,
        ));

        $datosCadenaOriginal['concepto'] = $concepto;

        // Obtiene la cadena original para la nomina.
        $cadenaOriginal = $this->cfdi->obtenCadenaOriginal($datosCadenaOriginal, true, $empleadoNomina);

        // Genera el sello en base a la cadena original.
        $sello = $this->cfdi->obtenSello($cadenaOriginal['cadenaOriginal']);

        // Construye los datos para el xml.
        $datosXML = $this->datosXml($cadenaOriginal['datos'], $empresa, $empleado, $sello, $certificado);
        $datosXML['concepto'] = $concepto;

        $archivo = $this->cfdi->generaArchivos($datosXML, true, $fechasSemana);

        $result = $this->timbrar($archivo['pathXML']);
        // echo "<pre>";
        //   var_dump($archivo, $result, $cadenaOriginal);
        // echo "</pre>";exit;

        // Si la nomina se timbro entonces agrega al array nominas la nomina del
        // empleado para despues insertarla en la bdd.
        if ($result['result']->status)
        {
          $vacaciones = isset($empleadoNomina[0]->nomina->percepciones['vacaciones'])
            ? $empleadoNomina[0]->nomina->percepciones['vacaciones']['ImporteGravado'] +
              $empleadoNomina[0]->nomina->percepciones['vacaciones']['ImporteExcento']
            : 0;

          $primaVacacionalGravable = isset($empleadoNomina[0]->nomina->percepciones['prima_vacacional'])
            ? $empleadoNomina[0]->nomina->percepciones['prima_vacacional']['ImporteGravado']
            : 0;

          $primaVacacionalExcento = isset($empleadoNomina[0]->nomina->percepciones['prima_vacacional'])
            ? $empleadoNomina[0]->nomina->percepciones['prima_vacacional']['ImporteExcento']
            : 0;

          $primaVacacional = isset($empleadoNomina[0]->nomina->percepciones['prima_vacacional'])
            ? $empleadoNomina[0]->nomina->percepciones['prima_vacacional']['ImporteGravado'] +
              $empleadoNomina[0]->nomina->percepciones['prima_vacacional']['ImporteExcento']
            : 0;

          $aguinaldoGravable = isset($empleadoNomina[0]->nomina->percepciones['aguinaldo'])
            ? $empleadoNomina[0]->nomina->percepciones['aguinaldo']['ImporteGravado']
            : 0;

          $aguinaldoExcento = isset($empleadoNomina[0]->nomina->percepciones['aguinaldo'])
            ? $empleadoNomina[0]->nomina->percepciones['aguinaldo']['ImporteExcento']
            : 0;

          $aguinaldo = isset($empleadoNomina[0]->nomina->percepciones['aguinaldo'])
            ? $empleadoNomina[0]->nomina->percepciones['aguinaldo']['ImporteGravado'] +
              $empleadoNomina[0]->nomina->percepciones['aguinaldo']['ImporteExcento']
            : 0;

          $imss = $empleadoNomina[0]->nomina->deducciones['imss']['ImporteGravado'] +
                  $empleadoNomina[0]->nomina->deducciones['imss']['ImporteExcento'];

          $infonavit = $empleadoNomina[0]->nomina->deducciones['infonavit']['ImporteGravado'] +
                       $empleadoNomina[0]->nomina->deducciones['infonavit']['ImporteExcento'];

          $ptuGravado = isset($empleadoNomina[0]->nomina->percepciones['ptu'])
            ? $empleadoNomina[0]->nomina->percepciones['ptu']['ImporteGravado']
            : 0;

          $ptuExcento = isset($empleadoNomina[0]->nomina->percepciones['ptu'])
            ? $empleadoNomina[0]->nomina->percepciones['ptu']['ImporteExcento']
            : 0;

          $ptu = $ptuGravado + $ptuExcento;

          $totalPrestamos = 0;
          // Recorre los prestamos del empleado para
          foreach ($empleadoNomina[0]->prestamos as $prestamo)
          {
            $totalPrestamos += floatval($prestamo['pago_semana_descontar']);

            $prestamosEmpleados[] = array(
              'id_empleado' => $empleadoId,
              'id_empresa' => $empresaId,
              'anio' => date('Y'),
              'semana' => $datos['numSemana'],
              'id_prestamo' => $prestamo['id_prestamo'],
              'monto' => $prestamo['pago_semana_descontar'],
            );

            // Suma lo que lleva pagado mas lo que se esta abonando.
            $totalAbonado = floatval($prestamo['total_pagado']) + floatval($prestamo['pago_semana_descontar']);

            // Si ya termino de pagar el prestamo entonces le cambia el status.
            if ($totalAbonado >= floatval($prestamo['prestado']))
            {
              $this->db->update('nomina_prestamos', array('status' => 'f'), array('id_prestamo' => $prestamo['id_prestamo']));
            }
          }

          $totalNoFiscal = floatval($datos['total_no_fiscal']);

          $nominasEmpleados[] = array(
            'id_empleado' => $empleadoId,
            'id_empresa' => $empresaId,
            'anio' => $fechasSemana['anio'],
            'semana' => $datos['numSemana'],
            'fecha_inicio' => $fechasSemana['fecha_inicio'],
            'fecha_final' => $fechasSemana['fecha_final'],
            'dias_trabajados' => $empleadoNomina[0]->dias_trabajados,
            'salario_diario' => $empleadoNomina[0]->salario_diario,
            'salario_integral' => $empleadoNomina[0]->nomina->salario_diario_integrado,
            'subsidio' => $datos['subsidio'],
            'sueldo_semanal' => $empleadoNomina[0]->nomina->percepciones['sueldo']['ImporteGravado'],
            'bonos' => $empleadoNomina[0]->bonos,
            'otros' => $empleadoNomina[0]->otros,
            'subsidio_pagado' => 0,
            'vacaciones' => $vacaciones,
            'prima_vacacional_grabable' => $primaVacacionalGravable,
            'prima_vacacional_exento' => $primaVacacionalExcento,
            'prima_vacacional' => $primaVacacional,
            'aguinaldo_grabable' => $aguinaldoGravable,
            'aguinaldo_exento' => $aguinaldoExcento,
            'aguinaldo' => $aguinaldo,
            'total_percepcion' => $valorUnitario,
            'imss' => $imss,
            'vejez' => 0,
            'isr' => $datos['isr'],
            'infonavit' => $infonavit,
            'subsidio_cobrado' => 0,
            'prestamos' => $totalPrestamos,
            'total_deduccion' => $descuento + $isr,
            'total_neto' => $valorUnitario - $descuento - $isr,
            'id_empleado_creador' => $this->session->userdata('id_usuario'),
            'ptu_exento' => $ptuExcento,
            'ptu_grabable' => $ptuGravado,
            'ptu' => $ptu,
            'id_puesto' => $empleadoNomina[0]->id_puesto,
            'salario_real' => $empleadoNomina[0]->salario_diario_real,
            'sueldo_real' => $empleadoNomina[0]->salario_diario_real * $empleadoNomina[0]->dias_trabajados,
            'total_no_fiscal' => $totalNoFiscal,
            'horas_extras' => $empleadoNomina[0]->horas_extras_dinero,
            'horas_extras_grabable' => $empleadoNomina[0]->nomina->percepciones['horas_extras']['ImporteGravado'],
            'horas_extras_excento' => $empleadoNomina[0]->nomina->percepciones['horas_extras']['ImporteExcento'],
            'descuento_playeras' => $datos['descuento_playeras'],
            'xml' => $result['xml'],
            'uuid' => $result['uuid'],
            'utilidad_empresa' => $empleadoNomina[0]->utilidad_empresa
          );
        }
        else
        {
          $errorTimbrar = true;
        }

        // echo "<pre>";
        //   var_dump($datosXML, $archivo);
        // echo "</pre>";exit;

        // echo "<pre>";
        //   var_dump($empleado, $cadenaOriginal, $sello, $certificado);
        // echo "</pre>";exit;
      }
    // }

    // Inserta las nominas.
    if (count($nominasEmpleados) > 0)
    {
      $this->db->insert_batch('nomina_fiscal', $nominasEmpleados);
    }

    // Inserta los abonos de los prestamos.
    if (count($prestamosEmpleados) > 0)
    {
      $this->db->insert_batch('nomina_fiscal_prestamos', $prestamosEmpleados);
    }

    // $endTime = new DateTime(date('Y-m-d H:i:s'));

    // echo "<pre>";
    //   var_dump($startTime->diff($endTime)->format('%H:%I:%S'));
    // echo "</pre>";exit;

    return array('errorTimbrar' => $errorTimbrar, 'empleadoId' => $empleadoId, 'ultimoNoGenerado' => $datos['ultimo_no_generado']);
  }

  public function finiquito($empleadoId, $fechaSalida)
  {
    $this->load->library('finiquito');

    $sql = '';
    // if ($filtros['empresaId'] !== '')
    // {
    //   $sql .= " AND u.id_empresa = {$filtros['empresaId']}";
    // }

    // if ($filtros['puestoId'] !== '')
    // {
    //   $sql .= " AND u.id_puesto = {$filtros['puestoId']}";
    // }

    if ($empleadoId !== '')
    {
      $sql .= " AND u.id = {$empleadoId}";
    }

    $fechaSalida = new DateTime($fechaSalida);
    $anio = $fechaSalida->format('Y');

    $fechaEntrada = $this->db->select('DATE(fecha_entrada) as fecha_entrada')
                              ->from('usuarios as u')
                              ->where("u.esta_asegurado = 't' AND u.status = 't' {$sql}")
                              ->get()->row()->fecha_entrada;

    // fecha en la que se inciaran a calcular los dias transcurrido del año
    // a la fecha de renuncia.
    $fechaInicio = date('2013-01-01');
    if (strtotime($fechaInicio) < strtotime($fechaEntrada))
    {
      $fechaInicio = date($fechaEntrada);
    }

    // Saca los dias transcurridos desde el 1 de Enero del año a la fecha de salida.
    $diasTranscurridos = $fechaSalida->diff(new DateTime($fechaInicio))->format("%a") + 1;

    $semanaQueSeVa = String::obtenerSemanasDelAnioV2($fechaSalida->format('Y'), 0, 4, true, $fechaSalida->format('Y-m-d'));
    $fechaInicioSemana = new DateTime($semanaQueSeVa['fecha_inicio']);
    $diasTrabajadosSemana = $fechaInicioSemana->diff($fechaSalida)->days;

    // Query para obtener el empleado.
    $query = $this->db->query(
      "SELECT u.id,
              id_empresa,
              (COALESCE(u.apellido_paterno, '') || ' ' || COALESCE(u.apellido_materno, '') || ' ' || u.nombre) as nombre,
              u.curp,
              DATE(u.fecha_entrada) as fecha_entrada,
              '{$fechaSalida->format('Y-m-d')}' as fecha_salida,
              '{$fechaSalida->format('Y-m-d')}' as fecha_final_pago,
              '{$fechaInicio}' as fecha_inicial_pago,
              u.id_puesto,
              u.salario_diario,
              u.regimen_contratacion,
              up.nombre as puesto,
              {$diasTranscurridos} as dias_transcurridos,
              (SELECT COALESCE(DATE_PART('DAY', SUM((fecha_fin - fecha_ini) + '1 day'))::integer, 0) as dias
              FROM nomina_asistencia
              WHERE DATE(fecha_ini) >= '{$anio}-01-01' AND DATE(fecha_fin) <= '{$anio}-12-31' AND id_usuario = u.id) as dias_faltados_anio,
              {$diasTrabajadosSemana} as dias_trabajados_semana,
              (SELECT COALESCE(isr, 0)
               FROM nomina_fiscal
               WHERE id_empleado = 6 AND dias_trabajados = 7 AND horas_extras = 0 AND ptu = 0 AND vacaciones = 0 AND prima_vacacional = 0 AND aguinaldo = 0
               ORDER BY fecha DESC
               LIMIT 1) as isr_ultima_semana
       FROM usuarios u
       LEFT JOIN usuarios_puestos up ON up.id_puesto = u.id_puesto
       WHERE u.esta_asegurado = 't' AND u.status = 't' {$sql}
    ");
    $empleado = $query->num_rows() > 0 ? $query->result() : array();
    $empleado[0]->incapacidades = array();

    $vacacionesAnios = $this->getConfigNominaVacaciones();
    $configNomina = $this->getConfigNomina();
    $salariosZonas = $this->getConfigSalariosZonas();

    $cuentasContpaq = array(
      'sueldo' => $this->getSueldoCuentaContpaq(),
      'horas_extras' => $this->getHorasExtrasCuentaContpaq(),
      'vacaciones' => $this->getVacacionesCuentaContpaq(),
      'prima_vacacional' => $this->getPrimaVacacionalCuentaContpaq(),
      'aguinaldo' => $this->getAguinaldoCuentaContpaq(),
      'ptu' => $this->getPtuCuentaContpaq(),
      'imss' => $this->getInfonavitCuentaContpaq(),
      'rcv' => $this->getRcvCuentaContpaq(),
      'infonavit' => $this->getInfonavitCuentaContpaq(),
      'otros' => $this->getInfonavitCuentaContpaq(),
    );

    $tablas = $this->getTablasIsr();

    $finiquito = $this->finiquito
      ->setEmpleado($empleado[0])
      ->setEmpresaConfig($configNomina[0])
      ->setVacacionesConfig($vacacionesAnios)
      ->setSalariosZonas($salariosZonas[0])
      ->setClavesPatron($cuentasContpaq)
      ->setTablasIsr($tablas)
      ->procesar();

      // echo "<pre>";
      //   var_dump($empleado);
      // echo "</pre>";exit;
    return $empleado;
  }

  public function add_finiquito($empleadoId, $fechaSalida)
  {
    $this->load->library('cfdi');
    $this->load->library('facturartebarato_api');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');

    // Obtiene la info del empleado.
    $empleado = $this->usuarios_model->get_usuario_info($empleadoId, true);

    // Obtiene los calculos del finiquito.
    $empleadoFiniquito = $this->finiquito($empleadoId, $fechaSalida);

    // Obtiene la informacion de la empresa.
    $empresa = $this->empresas_model->getInfoEmpresa($empleadoFiniquito[0]->id_empresa, true);

    // Obtiene el certificado.
    $certificado = $this->cfdi->obtenCertificado($this->db
      ->select('cer')
      ->from("empresas")
      ->where("id_empresa", $empleadoFiniquito[0]->id_empresa)
      ->get()->row()->cer
    );

    $valorUnitario = 0;
    // Recorre las percepciones del empleado para sacar el valor unitario.
    foreach ($empleadoFiniquito[0]->nomina->percepciones as $tipoPercepcion => $percepcion)
    {
      $valorUnitario += $percepcion['total'];
      unset($empleadoFiniquito[0]->nomina->percepciones[$tipoPercepcion]['total']);
    }

    // Descuento seria 0 pq no hay otra deducciones aparte del isr.
    $descuento = 0;

    // Obtiene los datos para la cadena original.
    $datosCadenaOriginal = $this->datosCadenaOriginal($empleado, $empresa);
    $datosCadenaOriginal['subTotal'] = $valorUnitario;
    $datosCadenaOriginal['descuento'] = $descuento;
    $datosCadenaOriginal['retencion'][0]['importe'] = $isr;
    $datosCadenaOriginal['totalImpuestosRetenidos'] = $isr;
    $datosCadenaOriginal['total'] = $valorUnitario - $descuento; //- $isr

    // Concepto de la nomina.
    $concepto = array(array(
      'cantidad'         => 1,
      'unidad'           => 'Servicio',
      'descripcion'      => 'Finiquito',
      'valorUnitario'    => $valorUnitario,
      'importe'          => $valorUnitario,
      'idClasificacion' => null,
    ));

    $datosCadenaOriginal['concepto'] = $concepto;

    // Obtiene la cadena original para la nomina.
    $cadenaOriginal = $this->cfdi->obtenCadenaOriginal($datosCadenaOriginal, true, $empleadoFiniquito);

    // Genera el sello en base a la cadena original.
    $sello = $this->cfdi->obtenSello($cadenaOriginal['cadenaOriginal']);

    // Construye los datos para el xml.
    $datosXML = $this->datosXml($cadenaOriginal['datos'], $empresa, $empleado, $sello, $certificado);
    $datosXML['concepto'] = $concepto;

    $archivo = $this->cfdi->generaArchivos($datosXML, false, null, 'media/cfdi/FiniquitosXML/'.date('Y'));
    $result = $this->timbrar($archivo['pathXML']);
    // echo "<pre>";
    //   var_dump($archivo, $result, $cadenaOriginal);
    // echo "</pre>";exit;

    if ( ! $result['result']->status)
    {
      $errorTimbrar = false;

      $primaVacacional = $empleadoFiniquito[0]->nomina->percepciones['prima_vacacional']['ImporteGravado'] +
                         $empleadoFiniquito[0]->nomina->percepciones['prima_vacacional']['ImporteExcento'];

      $aguinaldo = $empleadoFiniquito[0]->nomina->percepciones['aguinaldo']['ImporteGravado'] +
                         $empleadoFiniquito[0]->nomina->percepciones['aguinaldo']['ImporteExcento'];

      $totalPercepciones = $empleadoFiniquito[0]->nomina->vacaciones + $primaVacacional + $aguinaldo;

      $totalNeto = $totalPercepciones - $empleadoFiniquito[0]->nomina->deducciones['isr']['ImporteExcento'];

      $data = array(
        'id_empleado' => $empleadoFiniquito[0]->id,
        'id_empresa' => $empleadoFiniquito[0]->id_empresa,
        'id_puesto' => $empleadoFiniquito[0]->id_puesto,
        'id_empleado_creador' => $this->session->userdata('id_usuario'),
        'fecha_salida' => $fechaSalida,
        'salario_diario' => $empleadoFiniquito[0]->salario_diario,
        'vacaciones' => $empleadoFiniquito[0]->nomina->vacaciones,
        'prima_vacacional_grabable' => $empleadoFiniquito[0]->nomina->percepciones['prima_vacacional']['ImporteGravado'],
        'prima_vacacional_exento' => $empleadoFiniquito[0]->nomina->percepciones['prima_vacacional']['ImporteExcento'],
        'prima_vacacional' => $primaVacacional,
        'aguinaldo_grabable' => $empleadoFiniquito[0]->nomina->percepciones['aguinaldo']['ImporteGravado'],
        'aguinaldo_exento' => $empleadoFiniquito[0]->nomina->percepciones['aguinaldo']['ImporteExcento'],
        'aguinaldo' => $aguinaldo,
        'total_percepcion' => $totalPercepciones,
        'isr' => $empleadoFiniquito[0]->nomina->deducciones['isr']['ImporteExcento'],
        'total_deduccion' => 0,
        'total_neto' => $totalNeto,
        'xml' => 'xml',
        'uuid' => 'uuid',
      );

      $this->db->update('usuarios', array('status' => 'f'), array('id' => $empleadoFiniquito[0]->id));

      $this->db->insert('finiquito', $data);
    }
    else
    {
      $errorTimbrar = true;
      unlink($archivo['pathXML']);
    }

    return array('errorTimbrar' => $errorTimbrar);
  }

  /**
  * Inicializa los datos que serviran para generar la cadena original de la nomina.
  *
  * @return array
  */
  private function datosCadenaOriginal($empleado, $empresa)
  {
    $nombreEmpleado = $empleado['info'][0]->nombre.
      ($empleado['info'][0]->apellido_paterno? ' '.$empleado['info'][0]->apellido_paterno :'').
      ($empleado['info'][0]->apellido_materno? ' '.$empleado['info'][0]->apellido_materno:'');

    // Array con los datos necesarios para generar la cadena original.
    $data = array(
      'id'                => $empresa['info']->id_empresa,
      'table'             => 'empresas',

      'version'           => $empresa['info']->cfdi_version,
      'serie'             => '',
      'folio'             => '',
      'fecha'             => date('Y-m-d\TH:i:s'),
      // 'noAprobacion'      => $this->input->post('dno_aprobacion'),
      // 'anoAprobacion'     => $anoAprobacion[0],
      'tipoDeComprobante' => 'egreso',
      'formaDePago'       => 'Pago en una sola exhibicion',
      'condicionesDePago' => 'co',
      'subTotal'          => 0, //total_importe
      'descuento'         => 0, //descuento
      'total'             => 0,
      'metodoDePago'      => 'No identificado', // Tansferencia
      'NumCtaPago'        => 'No identificado',

      'rfc'               => $empleado['info'][0]->rfc,
      'nombre'            => $nombreEmpleado,
      'calle'             => $empleado['info'][0]->calle,
      'noExterior'        => $empleado['info'][0]->numero,
      'noInterior'        => '',
      'colonia'           => $empleado['info'][0]->colonia,
      'localidad'         => '',
      'municipio'         => $empleado['info'][0]->municipio,
      'estado'            => $empleado['info'][0]->estado,
      'pais'              => 'MEXICO',
      'codigoPostal'      => $empleado['info'][0]->cp,

      'concepto' => array(),

      'retencion' => array(array(
        'impuesto' => 'ISR',
        'importe'  => 0,
      )),
      'totalImpuestosRetenidos' => 0,

      'traslado' => array(array(
        'Impuesto' => 'IVA',
        'tasa'     => '0',
        'importe'  => '0',
      )),
      'totalImpuestosTrasladados' => 0,

      'sinCosto' => false,
    );

    return $data;
  }

  private function datosXml($datosCadenaOriginal, $empresa, $empleado, $sello, $certificado)
  {
    $noCertificado = $this->cfdi->obtenNoCertificado($empresa['info']->cer_org);

    $datosXML = $datosCadenaOriginal;
    $datosXML['id']         = $empresa['info']->id_empresa;
    $datosXML['sinCosto']   =  false;
    $datosXML['table']      = 'empresas';
    $datosXML['comprobante']['serie']         = '';
    $datosXML['comprobante']['folio']         = '';
    $datosXML['comprobante']['sello']         = $sello;
    $datosXML['comprobante']['noCertificado'] = $noCertificado;
    $datosXML['comprobante']['certificado']   = $certificado;
    $datosXML['concepto']                     = array();

    $datosXML['domicilio']['calle']        = $empleado['info'][0]->calle;
    $datosXML['domicilio']['noExterior']   = $empleado['info'][0]->numero;
    $datosXML['domicilio']['noInterior']   = '';
    $datosXML['domicilio']['colonia']      = $empleado['info'][0]->colonia;
    $datosXML['domicilio']['localidad']    = '';
    $datosXML['domicilio']['municipio']    = $empleado['info'][0]->municipio;
    $datosXML['domicilio']['estado']       = $empleado['info'][0]->estado;
    $datosXML['domicilio']['pais']         = 'MEXICO';
    $datosXML['domicilio']['codigoPostal'] = $empleado['info'][0]->cp;

    $datosXML['totalImpuestosRetenidos']   = $datosCadenaOriginal['retencion'][2];
    // $datosXML['totalImpuestosRetenidos']   = 0;
    $datosXML['totalImpuestosTrasladados'] = 0;

    $datosXML['retencion'] = array(
      'impuesto' => 'ISR',
      'importe'  => $datosCadenaOriginal['retencion'][2],
      // 'importe'  => '0',
    );

    $datosXML['traslado']  = array(array(
      'Impuesto' => 'IVA',
      'tasa'     => '0',
      'importe'  => '0',
    ));

    return $datosXML;
  }

  private function timbrar($pathXML)
  {
    $this->facturartebarato_api->setPathXML($pathXML);

    // Realiza el timbrado usando la libreria.
    $timbrado = $this->facturartebarato_api->timbrar();

    // Si no hubo errores al momento de realizar el timbrado.
    return array(
      'result' => $timbrado,
      'xml' => $this->facturartebarato_api->getXML(),
      'uuid' => $this->facturartebarato_api->getUUID()
    );
  }

  /**
   * Obtiene la configuracion de la nomina.
   *
   * @return array
   */
  public function getConfigNomina()
  {
    $config = $this->db->query(
      "SELECT id_configuracion,
              aguinaldo,
              prima_vacacional,
              puntualidad,
              asistencia,
              despensa
       FROM nomina_configuracion")->result();
    return $config;
  }

  /**
   * Obtiene la configuracion los dias de las vacaciones.
   *
   * @return array
   */
  public function getConfigNominaVacaciones()
  {
    $config = $this->db->query(
      "SELECT *
       FROM nomina_configuracion_vacaciones")->result();
    return $config;
  }

  /**
   * Obtiene la configuracion de los salarios de las zonas.
   *
   * @return array
   */
  public function getConfigSalariosZonas()
  {
    $config = $this->db->query(
      "SELECT zona_a, zona_b
       FROM nomina_salarios_minimos")->result();
    return $config;
  }

  /**
   * Obtiene el listado de empleados de la semana.
   *
   * @param  array  $filtros
   * @return array
   */
  public function listadoEmpleadosAsistencias(array $filtros = array())
  {
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
      "SELECT id_usuario, DATE(fecha_ini) as fecha_ini, DATE(fecha_fin) as fecha_fin, tipo, id_clave
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
              $empleado->dias_faltantes[] = array('fecha' => $fi->fecha_ini, 'tipo' => 'f', 'id_clave' => false);
            }

            // Si es una incapacidad.
            else
            {
              // Agrega el primer dia de la incapacidad al array.
              $empleado->dias_faltantes[] = array('fecha' => $fi->fecha_ini, 'tipo' => 'in', 'id_clave' => $fi->id_clave);

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
                  $empleado->dias_faltantes[] = array('fecha' => $fechaDia, 'tipo' => 'in', 'id_clave' => $fi->id_clave);
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

      $fechaFinIncapacidadOk = true;
      $auxLastFechaIncapacidad = false;

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
            'id_clave'   => null
          );

          $key++; // Incrementa el key.
        }

        // Si es una Asistencia entra.
        else if ($tipo === 'a')
        {
          // Si hay una incapacidad "abierta" le agrega la fecha fin.
          if ($fechaFinIncapacidadOk === false)
          {
            $nominaAsistencia[$keyIncapacidad]['fecha_fin'] = $fechaFinIncapacidad;
            $fechaFinIncapacidadOk = true; // Cierra la incapacidad.
          }
        }

        // Si es una incapacidad.
        else
        {
          // Si no existe ninguna incapacidad por cerrar entonces agrega una nueva.
          if ($fechaFinIncapacidadOk)
          {
            // Explode para separar el tipo y el Id de la incapacidad
            // ej. "in-52" => [in, 52]
            $tipoIncapacidad = explode('-', $tipo);

            $nominaAsistencia[] = array(
              'fecha_ini'  => $fecha,
              'fecha_fin'  => $fecha,
              'id_usuario' => $empleadoId,
              'tipo'       => $tipoIncapacidad[0],
              'id_clave'   => $tipoIncapacidad[1]
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

            // Si la fecha es la ultima de la semana.
            if (strtotime($fecha) === strtotime($semana['fecha_final']))
            {
              $nominaAsistencia[$keyIncapacidad]['fecha_fin'] = $fechaFinIncapacidad;
              $fechaFinIncapacidadOk = true; // Cierra la incapacidad.
            }
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

  /**
   * Agrega los prestamos.
   *
   * @param string $empleadoId
   * @param array  $datos
   * @param string $numSemana
   * @return array
   */
  public function addPrestamos($empleadoId, array $datos, $numSemana)
  {
    if (isset($datos['prestamos_existentes']))
    {
      $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($numSemana);
      $this->db->where("id_usuario = {$empleadoId} AND DATE(fecha) >= '{$semana['fecha_inicio']}' AND DATE(fecha) <= '{$semana['fecha_final']}'");
      $this->db->delete('nomina_prestamos');
    }

    $insertData = array();
    foreach ($datos['cantidad'] as $key => $cantidad)
    {
      $insertData[] = array(
        'id_usuario'  => $empleadoId,
        'prestado'    => $datos['cantidad'][$key],
        'pago_semana' => $datos['pago_semana'][$key],
        'fecha'       => $datos['fecha'][$key],
        'inicio_pago' => $datos['fecha_inicia_pagar'][$key],
      );
    }

    if (count($insertData) > 0)
    {
      $this->db->insert_batch('nomina_prestamos', $insertData);
    }

    return array('passes' => true);
  }

  /**
   * Obtiene los prestamos de un empleado en dicha semana.
   *
   * @param  string $empleadoId
   * @param  string $numSemana
   * @return array
   */
  public function getPrestamosEmpleado($empleadoId, $numSemana)
  {
    $semana = $this->fechasDeUnaSemana($numSemana);
    $query = $this->db->query("SELECT prestado, pago_semana, status, DATE(fecha) as fecha, DATE(inicio_pago) as inicio_pago
                               FROM nomina_prestamos
                               WHERE id_usuario = {$empleadoId} AND DATE(fecha) >= '{$semana['fecha_inicio']}' AND DATE(fecha) <= '{$semana['fecha_final']}'
                               ORDER BY DATE(fecha) ASC");

    $prestamos = array();
    if ($query->num_rows() > 0)
    {
      $prestamos = $query->result();
    }

    return $prestamos;
  }

  /*
   |------------------------------------------------------------------------
   | Funciones para obtener las cuentas del contpaq de cada tipo de
   | percepcion y deduccion.
   |------------------------------------------------------------------------
   */

  private function getSueldoCuentaContpaq()
  {
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE LOWER(nombre) LIKE '%sueldo%' AND id_padre = '1296'")->result();

    return $query[0]->cuenta;
  }

  private function getHorasExtrasCuentaContpaq()
  {
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE LOWER(nombre) LIKE '%horas extras%' AND id_padre = '1296'")->result();

    return $query[0]->cuenta;
  }

  private function getVacacionesCuentaContpaq()
  {
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE LOWER(nombre) LIKE '%vacaciones%' AND id_padre = '1296'")->result();

    return $query[0]->cuenta;
  }

  private function getPrimaVacacionalCuentaContpaq()
  {
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE LOWER(nombre) LIKE '%prima vacacional%' AND id_padre = '1296'")->result();

    return $query[0]->cuenta;
  }

  private function getAguinaldoCuentaContpaq()
  {
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE LOWER(nombre) LIKE '%aguinaldos%' AND id_padre = '1296'")->result();

    return $query[0]->cuenta;
  }

  private function getInfonavitCuentaContpaq()
  {
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE LOWER(nombre) LIKE '%cuotas infonavit%' AND id_padre = '1296'")->result();

    return $query[0]->cuenta;
  }

  private function getRcvCuentaContpaq()
  {
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE LOWER(nombre) LIKE '%rcv%' AND id_padre = '1296'")->result();

    return $query[0]->cuenta;
  }

  private function getPtuCuentaContpaq()
  {
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE LOWER(nombre) LIKE '%ptu%' AND id_padre = '1296'")->result();

    return $query[0]->cuenta;
  }

  /*
   |------------------------------------------------------------------------
   | Catalos del SAT
   |------------------------------------------------------------------------
   */
  /**
   * Obtien los tipos de incapacidades del catalogo del SAT.
   *
   * @return array
   */
  public function satCatalogoIncapacidades()
  {
    $query = $this->db->query("SELECT id_clave, clave, nombre, tipo
                               FROM nomina_sat_claves
                               WHERE tipo = 'in'
                               ORDER BY id_clave ASC");

    return $query->result();
  }

    /*
   |------------------------------------------------------------------------
   | Tablas Art 113
   |------------------------------------------------------------------------
   */

  /**
   * Obtien las tablas del art 113.
   *
   * @return array
   */
  public function getTablasIsr()
  {
    $tablas = array();
    $tablas['diaria']['art113'] = $this->db->query("SELECT * FROM nomina_diaria_art_113")->result();
    $tablas['diaria']['subsidios'] = $this->db->query("SELECT * FROM nomina_diaria_subsidios")->result();
    $tablas['semanal']['art113'] = $this->db->query("SELECT * FROM nomina_semanal_art_113")->result();
    $tablas['semanal']['subsidios'] = $this->db->query("SELECT * FROM nomina_semanal_subsidios")->result();

    return $tablas;
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
    return String::obtenerSemanasDelAnioV2(date('Y'), 0, 4);
  }

  /**
   * Obtiene las semanas que van del mes actual.
   * corregirla
   *
   * @return array
   */
  // public function semanasDelMesActual()
  // {
  //   return array_slice(String::obtenerSemanasDelAnioV2(date('Y'), 6, 0, true), 0, 4);
  // }

  /**
   * Obtiene la semana actual del mes actual.
   *
   * @return array
   */
  public function semanaActualDelMes()
  {
    return end(String::obtenerSemanasDelAnioV2(date('Y'), 0, 4));
  }

  /**
   * Obtiene las fechas de una semana en especifico.
   *
   * @param  string $semanaABuscar
   * @return array
   */
  public function fechasDeUnaSemana($semanaABuscar)
  {
    return String::obtenerSemanasDelAnioV2(date('Y'), 0, 4, false, $semanaABuscar);
  }

  /**
  * Descarga el ZIP con los documentos.
  *
  * @param  string $idFactura
  * @return void
  */
  public function descargarZipNomina($semana, $empresaId)
  {
    $this->load->model('empresas_model');

    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);
    $semana = $this->fechasDeUnaSemana($semana);

    $path = APPPATH."media/cfdi/NominasXML/{$empresa['info']->nombre_fiscal}/{$semana['anio']}/{$semana['semana']}/";

    // Scanea el directorio para obtener los archivos.
    $archivos = array_diff(scandir($path), array('..', '.'));

    $zip = new ZipArchive;
    if ($zip->open(APPPATH."media/Nomina-{$semana['anio']}-{$semana['semana']}.zip", ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === true)
    {
      foreach ($archivos as $archivo)
        $zip->addFile($path.$archivo, $archivo);

      $zip->close();
    }
    else
    {
      exit('Error al intentar crear el ZIP.');
    }

    header('Content-Type: application/zip');
    header("Content-disposition: attachment; filename=Nomina-{$semana['anio']}-{$semana['semana']}.zip");
    readfile(APPPATH."media/Nomina-{$semana['anio']}-{$semana['semana']}.zip");

    unlink(APPPATH."media/Nomina-{$semana['anio']}-{$semana['semana']}.zip");
  }

  /*
   |------------------------------------------------------------------------
   | PDF's
   |------------------------------------------------------------------------
   */

  public function pdfNominaFiscal($semana, $empresaId)
  {
    $semana = $this->fechasDeUnaSemana($semana);
    $configuraciones = $this->configuraciones();
    $filtros = array('semana' => $semana['semana'], 'empresaId' => $empresaId);
    $empleados = $this->nomina($configuraciones, $filtros);

    // echo "<pre>";
    //   var_dump($empleados);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;
    $pdf->titulo2 = "Lista de Raya de {$semana['fecha_inicio']} al {$semana['fecha_final']}";
    $pdf->titulo3 = "Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $pdf->SetFont('Helvetica','', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(6, 27);
    $pdf->Cell(100, 6, "Reg. Pat. IMSS: A4914083100", 0, 0, 'L', 0);

    $pdf->SetFont('Helvetica','B', 10);
    $pdf->SetXY(6, $pdf->GetY() + 6);
    $pdf->Cell(100, 6, "ADMINISTRACION Reg. Pat. IMSS: A4914083100", 0, 0, 'L', 0);

    $pdf->SetFont('Helvetica','', 10);
    $pdf->SetXY(6, $pdf->GetY() + 8);
    $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
    $pdf->Row(array('', 'Percepción', 'Importe', '', 'Deducción', 'Importe'), false, false, null, 2, 1);

    $pdf->SetFont('Helvetica','', 10);
    $pdf->SetXY(6, $pdf->GetY() - 2);
    $pdf->Cell(200, 2, "________________________________________________________________________________________________________", 0, 0, 'L', 0);

    $y = $pdf->GetY();
    foreach ($empleados as $key => $empleado)
    {
      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY() + 4);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(15, 100));
      $pdf->Row(array($empleado->id, $empleado->nombre), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetFont('Helvetica','', 9);
      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(50, 70, 50));
      $pdf->Row(array($empleado->puesto, "RFC: {$empleado->rfc}", 'Afiliciación IMSS:'), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(50, 35, 35, 35, 30));
      $pdf->Row(array("Fecha Ingr: {$empleado->fecha_entrada}", "Sal. diario: {$empleado->salario_diario}", "S.D.I: {$empleado->nomina->salario_diario_integrado}", "S.B.C: {$empleado->nomina->salario_diario_integrado}", 'Cotiza fijo'), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $horasExtras = 0;
      if ($empleado->horas_extras_dinero > 0)
      {
        $pagoXHora = $empleado->salario_diario / 8;
        $horasExtras = $empleado->horas_extras_dinero / $pagoXHora;
      }

      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(35, 35, 25, 35, 70));
      $pdf->Row(array("Dias Pagados: {$empleado->dias_trabajados}", "Tot Hrs trab: " . $empleado->dias_trabajados * 8, 'Hrs dia: 8.00', "Hrs extras: " . number_format($horasExtras, 2), "CURP: {$empleado->curp}"), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $y2 = $pdf->GetY();

      // Percepciones
      $percepciones = $empleado->nomina->percepciones;

      // Sueldo
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Sueldo', String::formatoNumero($percepciones['sueldo']['total'], 2)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }

      // Horas Extras
      if ($empleado->horas_extras_dinero > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Horas Extras', String::formatoNumero($empleado->horas_extras_dinero, 2)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // Vacaciones y prima vacacional
      if ($empleado->nomina_fiscal_vacaciones > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Vacaciones', String::formatoNumero($empleado->nomina_fiscal_vacaciones, 2)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Prima vacacional', String::formatoNumero($empleado->nomina->prima_vacacional, 2)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // Subsidio
      if ($empleado->nomina_fiscal_subsidio > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Subsidio', String::formatoNumero($empleado->nomina_fiscal_subsidio, 2)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // PTU
      if ($empleado->nomina_fiscal_ptu > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'PTU', String::formatoNumero($empleado->nomina_fiscal_ptu, 2)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // Aguinaldo
      if ($empleado->nomina_fiscal_aguinaldo > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Aguinaldo', String::formatoNumero($empleado->nomina_fiscal_aguinaldo, 2)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      $y = $pdf->GetY();

      // Deducciones
      $deducciones = $empleado->nomina->deducciones;
      $pdf->SetFont('Helvetica','', 9);

      $pdf->SetY($y2);
      if ($empleado->infonavit > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Infonavit', String::formatoNumero($deducciones['infonavit']['total'], 2)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'I.M.M.S.', String::formatoNumero($deducciones['imss']['total'] + $deducciones['rcv']['total'], 2)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }

      if ($empleado->nomina_fiscal_prestamos > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Prestamos', String::formatoNumero($empleado->nomina_fiscal_prestamos, 2)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      if ($empleado->descuento_playeras > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Desc. Playeras', String::formatoNumero($empleado->descuento_playeras, 2)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      if ($empleado->nomina_fiscal_isr > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'ISR', String::formatoNumero($empleado->nomina_fiscal_isr, 2)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      if ($y < $pdf->GetY())
      {
        $y = $pdf->GetY();
      }

      // Total percepciones y deducciones
      $pdf->SetXY(6, $y + 2);
      $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
      $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($empleado->nomina_fiscal_total_percepciones, 2), '', 'Total Deducciones', String::formatoNumero($empleado->nomina_fiscal_total_deducciones, 2)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Total Neto', String::formatoNumero($empleado->nomina_fiscal_total_neto, 2)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

    }
    $pdf->Output('Nomina.pdf', 'I');
  }

}
/* End of file nomina_fiscal_model.php */
/* Location: ./application/models/nomina_fiscal_model.php */