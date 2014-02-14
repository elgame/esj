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
      'imss'             => $this->getImssCuentaContpaq(),
      'rcv'              => $this->getRcvCuentaContpaq(),
      'infonavit'        => $this->getInfonavitCuentaContpaq(),
      'otros'            => $this->getOtrosGastosCuentaContpaq(),
      'subsidio'         => $this->getSubsidioCuentaContpaq(),
      'isr'              => $this->getIsrCuentaContpaq(),
    );
    $configuraciones['tablas_isr'] = $this->getTablasIsr();

    return $configuraciones;
  }

  public function nomina($configuraciones, array $filtros = array(), $empleadoId = null, $horasExtrasDinero = null, $descuentoPlayeras = null,
                         $subsidio = null, $isr = null, $utilidadEmpresa = null, $descuentoOtros = null)
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

    if(isset($filtros['asegurado']))
    {
      $sql .= " AND u.esta_asegurado = 't'";
    }

    $diaPrimeroDeLaSemana = $semana['fecha_inicio']; // fecha del primero dia de la semana.
    $diaUltimoDeLaSemana = $semana['fecha_final']; // fecha del ultimo dia de la semana.
    $anio = $semana['anio'];
    $anioPtu = $anio - 1;

    $horasExtrasDinero = $horasExtrasDinero ?: 0;
    $descuentoPlayeras = $descuentoPlayeras ?: 0;
    $descuentoOtros = $descuentoOtros ?: 0;
    $utilidadEmpresa = $utilidadEmpresa ?: 0;

    // Query para obtener los empleados de la semana de la nomina.
    $query = $this->db->query(
      "SELECT u.id,
              (COALESCE(u.apellido_paterno, '') || ' ' || COALESCE(u.apellido_materno, '') || ' ' || u.nombre) as nombre,
              u.esta_asegurado, 
              u.curp,
              DATE(u.fecha_entrada) as fecha_entrada,
              u.id_puesto, u.id_departamente, 
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
              COALESCE(nf.descuento_otros, {$descuentoOtros}) as descuento_otros,
              '{$diaPrimeroDeLaSemana}' as fecha_inicial_pago,
              '{$diaUltimoDeLaSemana}' as fecha_final_pago,
              COALESCE((SELECT SUM(bono) as bonos FROM nomina_percepciones_ext WHERE id_usuario = u.id AND bono != 0  AND DATE(fecha) >= '{$diaPrimeroDeLaSemana}' AND DATE(fecha) <= '{$diaUltimoDeLaSemana}'), 0) as bonos,
              COALESCE((SELECT SUM(otro) as otros FROM nomina_percepciones_ext WHERE id_usuario = u.id AND otro != 0 AND DATE(fecha) >= '{$diaPrimeroDeLaSemana}' AND DATE(fecha) <= '{$diaUltimoDeLaSemana}'), 0) as otros,
              COALESCE((SELECT SUM(domingo) as domingo FROM nomina_percepciones_ext WHERE id_usuario = u.id AND domingo != 0 AND DATE(fecha) >= '{$diaPrimeroDeLaSemana}' AND DATE(fecha) <= '{$diaUltimoDeLaSemana}'), 0) as domingo,
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
              u.rfc,
              u.cuenta_banco,
              u.no_seguro,
              (SELECT COALESCE(dias_vacaciones, 0) FROM nomina_fiscal_vacaciones WHERE anio = {$semana['anio']} AND semana = {$semana['semana']} AND id_empleado = u.id) as dias_vacaciones_fijo,
              (SELECT Date(fecha_fin) FROM nomina_fiscal_vacaciones WHERE id_empleado = u.id AND Date(fecha) < '{$diaUltimoDeLaSemana}' ORDER BY fecha DESC LIMIT 1) AS en_vacaciones
       FROM usuarios u
       LEFT JOIN usuarios_puestos up ON up.id_puesto = u.id_puesto
       LEFT JOIN nomina_fiscal nf ON nf.id_empleado = u.id AND nf.id_empresa = {$filtros['empresaId']} AND nf.anio = {$anio} AND nf.semana = {$semana['semana']}
       WHERE user_nomina = 't' AND DATE(u.fecha_entrada) <= '{$diaUltimoDeLaSemana}' AND u.status = 't' {$sql}
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
        WHERE np.status = 't' AND np.pausado = 'f' AND DATE(np.inicio_pago) <= '{$diaUltimoDeLaSemana}'
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
      foreach ($empleados as $keye => $empleado)
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
        //quita al trabajador si esta de vacaciones
        if($empleado->en_vacaciones == '')
          true;
        elseif( $diaUltimoDeLaSemana >= $empleado->en_vacaciones  )
          true;
        else
          unset($empleados[$keye]);

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
          $datos['utilidad_empresa'],
          $datos['descuento_otros']
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

        $result = array('xml' => '', 'uuid' => '');
        if($datos['esta_asegurado'] == 't')
        {
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
          //   var_dump($archivo, $result, base64_encode($result['xml']), $cadenaOriginal);
          // echo "</pre>";
          
          // Si la nomina se timbro entonces agrega al array nominas la nomina del
          // empleado para despues insertarla en la bdd.
          if (isset($result['result']->status) && $result['result']->status==true)
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
              'descuento_otros' => $datos['descuento_otros'],
              'xml' => $result['xml'],
              'uuid' => $result['uuid'],
              'utilidad_empresa' => $empleadoNomina[0]->utilidad_empresa,
              'domingo' => $empleadoNomina[0]->domingo,
              'esta_asegurado' => $datos['esta_asegurado'],
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
        }else
        {
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
              'dias_trabajados' => $empleadoNomina[0]->dias_trabajados-1,
              'salario_diario' => $empleadoNomina[0]->salario_diario_real,
              'salario_integral' => 0,
              'subsidio' => 0,
              'sueldo_semanal' => ($empleadoNomina[0]->salario_diario_real*($empleadoNomina[0]->dias_trabajados-1)),
              'bonos' => $empleadoNomina[0]->bonos,
              'otros' => $empleadoNomina[0]->otros,
              'subsidio_pagado' => 0,
              'vacaciones' => 0,
              'prima_vacacional_grabable' => 0,
              'prima_vacacional_exento' => 0,
              'prima_vacacional' => 0,
              'aguinaldo_grabable' => 0,
              'aguinaldo_exento' => 0,
              'aguinaldo' => 0,
              'total_percepcion' => 0,
              'imss' => 0,
              'vejez' => 0,
              'isr' => 0,
              'infonavit' => 0,
              'subsidio_cobrado' => 0,
              'prestamos' => $totalPrestamos,
              'total_deduccion' => 0,
              'total_neto' => 0,
              'id_empleado_creador' => $this->session->userdata('id_usuario'),
              'ptu_exento' => 0,
              'ptu_grabable' => 0,
              'ptu' => 0,
              'id_puesto' => $empleadoNomina[0]->id_puesto,
              'salario_real' => $empleadoNomina[0]->salario_diario_real,
              'sueldo_real' => $empleadoNomina[0]->salario_diario_real * ($empleadoNomina[0]->dias_trabajados-1),
              'total_no_fiscal' => $totalNoFiscal,
              'horas_extras' => 0,
              'horas_extras_grabable' => 0,
              'horas_extras_excento' => 0,
              'descuento_playeras' => $datos['descuento_playeras'],
              'descuento_otros' => $datos['descuento_otros'],
              'xml' => '',
              'uuid' => '',
              'utilidad_empresa' => $empleadoNomina[0]->utilidad_empresa,
              'domingo' => $empleadoNomina[0]->domingo,
              'esta_asegurado' => $datos['esta_asegurado'],
            );
        }

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
    $fechaInicio = date('Y-01-01');
    if (strtotime($fechaInicio) < strtotime($fechaEntrada))
    {
      $fechaInicio = date($fechaEntrada);
    }

    // Saca los dias transcurridos desde el 1 de Enero del año a la fecha de salida.
    $diasTranscurridos = $fechaSalida->diff(new DateTime($fechaInicio))->format("%a") + 1;

    $semanaQueSeVa = String::obtenerSemanasDelAnioV2($fechaSalida->format('Y'), 0, 4, true, $fechaSalida->format('Y-m-d'));
    $fechaInicioSemana = new DateTime($semanaQueSeVa['fecha_inicio']);
    $diasTrabajadosSemana = $fechaInicioSemana->diff($fechaSalida)->days + 1;

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
               WHERE id_empleado = {$empleadoId} AND dias_trabajados = 7 AND horas_extras = 0 AND ptu = 0 AND vacaciones = 0 AND prima_vacacional = 0 AND aguinaldo = 0
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
      'sueldo'           => $this->getSueldoCuentaContpaq(),
      'horas_extras'     => $this->getHorasExtrasCuentaContpaq(),
      'vacaciones'       => $this->getVacacionesCuentaContpaq(),
      'prima_vacacional' => $this->getPrimaVacacionalCuentaContpaq(),
      'aguinaldo'        => $this->getAguinaldoCuentaContpaq(),
      'ptu'              => $this->getPtuCuentaContpaq(),
      'imss'             => $this->getImssCuentaContpaq(),
      'rcv'              => $this->getRcvCuentaContpaq(),
      'infonavit'        => $this->getInfonavitCuentaContpaq(),
      'otros'            => $this->getOtrosGastosCuentaContpaq(),
      'subsidio'         => $this->getSubsidioCuentaContpaq(),
      'isr'              => $this->getIsrCuentaContpaq(),
    );

    $tablas = $this->getTablasIsr();

    //Dias trabajados en el año en que entro
    $fecha_entrada = explode('-', $empleado[0]->fecha_entrada);
    $anio_anterior = date("Y", strtotime("-1 year")).'-'.$fecha_entrada[1].'-'.$fecha_entrada[2];
    if(strtotime($anio_anterior) < strtotime($fechaEntrada))
    {
      $anio_anterior = date($fechaEntrada);
    }
    $empleado[0]->dias_anio_vacaciones = intval(String::diasEntreFechas($anio_anterior, $fechaSalida->format('Y-m-d')));

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
    $isr = $empleadoFiniquito[0]->nomina->deducciones['isr']['total'];
    unset($empleadoFiniquito[0]->nomina->deducciones['isr']['total']);

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

    $archivo = $this->cfdi->generaArchivos($datosXML, false, null, 'media/cfdi/FiniquitosXML/');
    $result = $this->timbrar($archivo['pathXML']);
    // echo "<pre>";
    //   var_dump($archivo, $result, $cadenaOriginal);
    // echo "</pre>";exit;

    if ($result['result']->status)
    {
      $errorTimbrar = false;

      $sueldoSemana = $empleadoFiniquito[0]->nomina->percepciones['sueldo']['ImporteGravado'] +
                      $empleadoFiniquito[0]->nomina->percepciones['sueldo']['ImporteExcento'];

      $primaVacacional = $empleadoFiniquito[0]->nomina->percepciones['prima_vacacional']['ImporteGravado'] +
                         $empleadoFiniquito[0]->nomina->percepciones['prima_vacacional']['ImporteExcento'];

      $aguinaldo = $empleadoFiniquito[0]->nomina->percepciones['aguinaldo']['ImporteGravado'] +
                   $empleadoFiniquito[0]->nomina->percepciones['aguinaldo']['ImporteExcento'];

      $totalPercepciones = $sueldoSemana + $empleadoFiniquito[0]->nomina->vacaciones + $primaVacacional + $aguinaldo;

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
        'total_deduccion' => $empleadoFiniquito[0]->nomina->deducciones['isr']['ImporteExcento'],
        'total_neto' => $totalNeto,
        'sueldo_semanal' => $sueldoSemana,
        'dias_trabajados' => $empleadoFiniquito[0]->dias_trabajados_semana,
        'xml' => $result['xml'],
        'uuid' => $result['uuid'],
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
      $sql .= " AND id_departamente = {$filtros['puestoId']}";
    }

    $diaPrimeroDeLaSemana = $semana['fecha_inicio']; // fecha del primero dia de la semana.
    $diaUltimoDeLaSemana = $semana['fecha_final']; // fecha del ultimo dia de la semana.

    // Query para obtener los empleados de la semana.
    $query = $this->db->query(
      "SELECT id, (COALESCE(apellido_paterno, '') || ' ' || COALESCE(apellido_materno, '') || ' ' || nombre) as nombre,
              DATE(fecha_entrada) as fecha_entrada, id_puesto, id_departamente
       FROM usuarios
       WHERE user_nomina = 't' AND DATE(fecha_entrada) <= '{$diaUltimoDeLaSemana}' AND status = 't' {$sql}
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
          'domingo'    => 0,
        );
      }
      elseif ($tipo === 'domingo')
      {
        $insertData[] = array(
          'id_usuario' => $empleadoId,
          'fecha'      => $datos['fecha'][$key],
          'domingo'    => $datos['cantidad'][$key],
          'bono'       => 0,
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
          'domingo'    => 0,
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
      "SELECT id_usuario, DATE(fecha) as fecha, bono, otro, domingo
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
      if(count($datos['eliminar_prestamo']) > 0)
        $this->db->delete('nomina_prestamos', "id_prestamo IN(".implode(',', $datos['eliminar_prestamo']).") AND 
            id_usuario = {$empleadoId} AND DATE(fecha) >= '{$semana['fecha_inicio']}' AND DATE(fecha) <= '{$semana['fecha_final']}'");
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

  /**
   * Agrega las vacaciones.
   *
   * @param string $empleadoId
   * @param array  $datos
   * @param string $numSemana
   * @return array
   */
  public function addVacaciones($empleadoId, array $datos, $numSemana)
  {
    $anio = substr($datos['vfecha'], 0, 4);

    $this->db->where("id_empleado = {$empleadoId} AND anio = {$anio} AND semana = {$numSemana}");
    $this->db->delete('nomina_fiscal_vacaciones');

    if($datos['vdias'] > 0)
    {
      $insertData = array(
        'id_empleado'     => $empleadoId,
        'anio'            => $anio,
        'semana'          => $numSemana,
        'dias_vacaciones' => $datos['vdias'],
        'fecha'           => $datos['vfecha'],
        'fecha_fin'       => $datos['vfecha1'],
      );
      $this->db->insert('nomina_fiscal_vacaciones', $insertData);
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
  public function getPrestamosEmpleado($empleadoId, $numSemana, $anio=null)
  {
    $anio = $anio==null?date("Y"):$anio;
    $semana = $this->fechasDeUnaSemana($numSemana, $anio);
    $query = $this->db->query("SELECT id_prestamo, prestado, pago_semana, status, DATE(fecha) as fecha, DATE(inicio_pago) as inicio_pago, pausado
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

  /**
   * Obtiene la vacaciones que se agrego en la semana.
   *
   * @param  string $empleadoId
   * @param  string $numSemana
   * @return array
   */
  public function getVacacionesEmpleado($empleadoId, $numSemana, $anio=null)
  {
    $anio = $anio==null?date("Y"):$anio;
    $semana = $this->fechasDeUnaSemana($numSemana, $anio);

    $query = $this->db->query("SELECT id_vacaciones, id_empleado, anio, DATE(fecha) as fecha, Date(fecha_fin) AS fecha_fin, semana, dias_vacaciones
                               FROM nomina_fiscal_vacaciones
                               WHERE id_empleado = {$empleadoId} AND anio = {$semana['anio']} AND semana = {$numSemana}
                               LIMIT 1");

    $vacaciones = array();
    if ($query->num_rows() > 0)
    {
      $vacaciones = $query->row();
    }

    return $vacaciones;
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
       WHERE LOWER(nombre) LIKE '%credito infonavit%' AND id_padre = '1191'")->result();

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

  private function getImssCuentaContpaq()
  {
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE LOWER(nombre) LIKE '%imss retenido%' AND id_padre = '1191'")->result();

    return $query[0]->cuenta;
  }

  private function getOtrosGastosCuentaContpaq()
  {
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE LOWER(nombre) LIKE '%otros gastos%' AND id_padre = '1296'")->result();

    return $query[0]->cuenta;
  }

  private function getSubsidioCuentaContpaq()
  {
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE LOWER(nombre) LIKE '%subsidio%' AND id_padre = '28'")->result();

    return $query[0]->cuenta;
  }

  private function getIsrCuentaContpaq()
  {
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE LOWER(nombre) LIKE '%ispt antes%' AND id_padre = '1191'")->result();

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
  public function semanaActualDelMes($anio=null)
  {
    $anio = $anio!=null? $anio: date('Y');
    return end(String::obtenerSemanasDelAnioV2(date('Y'), 0, 4));
  }

  /**
   * Obtiene las fechas de una semana en especifico.
   *
   * @param  string $semanaABuscar
   * @return array
   */
  public function fechasDeUnaSemana($semanaABuscar, $anio=null)
  {
    $anio = $anio!=null? $anio: date('Y');
    return String::obtenerSemanasDelAnioV2($anio, 0, 4, false, $semanaABuscar);
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

  public function descargarTxtBanco($semana, $empresaId)
  {
    $configuraciones = $this->configuraciones();
    $semana = $this->fechasDeUnaSemana($semana);
    $filtros = array('semana' => $semana['semana'], 'empresaId' => $empresaId);
    $empleados = $this->nomina($configuraciones, $filtros);
    $nombre = "PAGO-{$semana['anio']}-SEM-{$semana['semana']}.txt";

    $content = array();
    foreach ($empleados as $key => $empleado)
    {
      if($empleado->cuenta_banco != '' && $empleado->esta_asegurado == 't'){
        $content[] = $this->formatoBanco($key + 1, '0', 9, 'I') .
                    $this->formatoBanco($empleado->rfc, ' ', 16, 'D') .
                    $this->formatoBanco('99'.$empleado->cuenta_banco, ' ', 22, 'D') .
                    $this->formatoBanco($empleado->nomina_fiscal_total_neto, '0', 15, 'I', true) .
                    $this->formatoBanco($empleado->nombre, ' ', 40, 'D') .
                    "001001";
      }
    }
    $content = implode("\r\n", $content);

    $fp = fopen(APPPATH."media/temp/{$nombre}", "wb");
    fwrite($fp,$content);
    fclose($fp);

    header('Content-Type: text/plain');
    header("Content-disposition: attachment; filename={$nombre}");
    readfile(APPPATH."media/temp/{$nombre}");
    unlink(APPPATH."media/temp/{$nombre}");
  }

  public function formatoBanco($valor, $relleno = ' ', $cantidad = 0, $lado = 'I', $decimal = false)
  {
    if ($cantidad != intval(0) && $valor)
    {
      $valor = (string)$valor;

      if ($decimal)
      {
        if (strpos($valor, '.'))
        {
          $valor = number_format($valor, 2, '.', '');
          $valor = explode('.', $valor);

          if (strlen($valor[1]) > 2)
          {
            $valor[1] = substr($valor[1], 0, 2);
          }
          $valor = $valor[0].$valor[1];
        }
        else
        {
          $valor .= '00';
        }
      }

      $longitudValor = strlen($valor);
      for ($i = $longitudValor;  $i < $cantidad; $i++)
      {
        $valor = strtoupper($lado) === 'I' ? $relleno . $valor : $valor . $relleno;
      }

      return $valor;

      // echo "<pre>";
      //   var_dump($valor, $relleno, $cantidad, $lado, $decimal, $longitudValor);
      // echo "</pre>";exit;
    }
  }

  /*
   |------------------------------------------------------------------------
   | PDF's
   |------------------------------------------------------------------------
   */

  public function pdfNominaFiscal($semana, $empresaId, $anio=null)
  {
    $anio = $anio==null? date("Y"): $anio;
    $this->load->model('empresas_model');

    $semana = $this->fechasDeUnaSemana($semana, $anio);
    $configuraciones = $this->configuraciones();
    $filtros = array('semana' => $semana['semana'], 'empresaId' => $empresaId, 'asegurado' => 'si');
    $empleados = $this->nomina($configuraciones, $filtros);
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);
    $finiquitos = $this->db->query("SELECT * FROM usuarios AS u INNER JOIN finiquito AS f ON u.id = f.id_empleado
      WHERE f.fecha_salida BETWEEN '{$semana['fecha_inicio']}' AND '{$semana['fecha_final']}'")->result();

    // echo "<pre>";
    //   var_dump($empleados);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
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

    $total_gral = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0, 
      'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'isr' => 0, 
      'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0);

    $departamentos = $this->usuarios_model->departamentos();
    foreach ($departamentos as $keyd => $departamento)
    {
      $total_dep = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0, 
        'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'isr' => 0, 
        'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0);

      $dep_tiene_empleados = true;
      $y = $pdf->GetY();
      foreach ($empleados as $key => $empleado)
      {
        if($departamento->id_departamento == $empleado->id_departamente)
        {
          if($dep_tiene_empleados)
          {
            $pdf->SetFont('Helvetica','B', 10);
            $pdf->SetXY(6, $pdf->GetY()+6);
            $pdf->Cell(130, 6, $departamento->nombre, 0, 0, 'L', 0);

            $pdf->SetFont('Helvetica','', 10);
            $pdf->SetXY(6, $pdf->GetY() + 8);
            $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
            $pdf->Row(array('', 'Percepción', 'Importe', '', 'Deducción', 'Importe'), false, false, null, 2, 1);

            $pdf->SetFont('Helvetica','', 10);
            $pdf->SetXY(6, $pdf->GetY() - 2);
            $pdf->Cell(200, 2, "________________________________________________________________________________________________________", 0, 0, 'L', 0);
            $dep_tiene_empleados = false;
          }

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
          $pdf->Row(array($empleado->puesto, "RFC: {$empleado->rfc}", "Afiliciación IMSS: {$empleado->no_seguro}"), false, false, null, 1, 1);
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
          $pdf->Row(array('', 'Sueldo', String::formatoNumero($percepciones['sueldo']['total'], 2, '$', false)), false, 0, null, 1, 1);
          $total_dep['sueldo'] += $percepciones['sueldo']['total'];
          $total_gral['sueldo'] += $percepciones['sueldo']['total'];
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
            $pdf->Row(array('', 'Horas Extras', String::formatoNumero($empleado->horas_extras_dinero, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['horas_extras'] += $empleado->horas_extras_dinero;
            $total_gral['horas_extras'] += $empleado->horas_extras_dinero;
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
            $pdf->Row(array('', 'Vacaciones', String::formatoNumero($empleado->nomina_fiscal_vacaciones, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['vacaciones'] += $empleado->nomina_fiscal_vacaciones;
            $total_gral['vacaciones'] += $empleado->nomina_fiscal_vacaciones;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y2 = $pdf->GetY();
            }

            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Prima vacacional', String::formatoNumero($empleado->nomina->prima_vacacional, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['prima_vacacional'] += $empleado->nomina->prima_vacacional;
            $total_gral['prima_vacacional'] += $empleado->nomina->prima_vacacional;
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
            $pdf->Row(array('', 'Subsidio', String::formatoNumero($empleado->nomina_fiscal_subsidio, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['subsidio'] += $empleado->nomina_fiscal_subsidio;
            $total_gral['subsidio'] += $empleado->nomina_fiscal_subsidio;
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
            $pdf->Row(array('', 'PTU', String::formatoNumero($empleado->nomina_fiscal_ptu, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['ptu'] += $empleado->nomina_fiscal_ptu;
            $total_gral['ptu'] += $empleado->nomina_fiscal_ptu;
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
            $pdf->Row(array('', 'Aguinaldo', String::formatoNumero($empleado->nomina_fiscal_aguinaldo, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['aguinaldo'] += $empleado->nomina_fiscal_aguinaldo;
            $total_gral['aguinaldo'] += $empleado->nomina_fiscal_aguinaldo;
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
            $pdf->Row(array('', 'Infonavit', String::formatoNumero($deducciones['infonavit']['total'], 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['infonavit'] += $deducciones['infonavit']['total'];
            $total_gral['infonavit'] += $deducciones['infonavit']['total'];
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y = $pdf->GetY();
            }
          }

          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'I.M.M.S.', String::formatoNumero($deducciones['imss']['total'] + $deducciones['rcv']['total'], 2, '$', false)), false, 0, null, 1, 1);
          $total_dep['imms'] += $deducciones['imss']['total'] + $deducciones['rcv']['total'];
          $total_gral['imms'] += $deducciones['imss']['total'] + $deducciones['rcv']['total'];
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
            $pdf->Row(array('', 'Prestamos', String::formatoNumero($empleado->nomina_fiscal_prestamos, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['prestamos'] += $empleado->nomina_fiscal_prestamos;
            $total_gral['prestamos'] += $empleado->nomina_fiscal_prestamos;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y = $pdf->GetY();
            }
          }

          // if ($empleado->descuento_playeras > 0)
          // {
          //   $pdf->SetXY(108, $pdf->GetY());
          //   $pdf->SetAligns(array('L', 'L', 'R'));
          //   $pdf->SetWidths(array(15, 62, 25));
          //   $pdf->Row(array('', 'Desc. Playeras', String::formatoNumero($empleado->descuento_playeras, 2, '$', false)), false, 0, null, 1, 1);
          //   if($pdf->GetY() >= $pdf->limiteY)
          //   {
          //     $pdf->AddPage();
          //     $y = $pdf->GetY();
          //   }
          // }

          if ($empleado->nomina_fiscal_isr > 0)
          {
            $pdf->SetXY(108, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'ISR', String::formatoNumero($empleado->nomina_fiscal_isr, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['isr'] += $empleado->nomina_fiscal_isr;
            $total_gral['isr'] += $empleado->nomina_fiscal_isr;
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
          $total_dep['total_percepcion'] += $empleado->nomina_fiscal_total_percepciones;
          $total_gral['total_percepcion'] += $empleado->nomina_fiscal_total_percepciones;
          $total_dep['total_deduccion'] += $empleado->nomina_fiscal_total_deducciones;
          $total_gral['total_deduccion'] += $empleado->nomina_fiscal_total_deducciones;
          $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($empleado->nomina_fiscal_total_percepciones, 2, '$', false), '', 'Total Deducciones', String::formatoNumero($empleado->nomina_fiscal_total_deducciones, 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
              $pdf->AddPage();

          $pdf->SetFont('Helvetica','B', 9);
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $total_dep['total_neto'] += $empleado->nomina_fiscal_total_neto;
          $total_gral['total_neto'] += $empleado->nomina_fiscal_total_neto;
          $pdf->Row(array('', 'Total Neto', String::formatoNumero($empleado->nomina_fiscal_total_neto, 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
              $pdf->AddPage();

          $pdf->SetFont('Helvetica', '', 9);
          $pdf->SetXY(120, $pdf->GetY()+3);
          $pdf->Cell(200, 2, "--------------------------------------------------------------------------------------", 0, 0, 'L', 0);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();
        }
      }

      //****** Total departamento ******
      if($dep_tiene_empleados == false)
      {
        if($pdf->GetY()+10 >= $pdf->limiteY)
          $pdf->AddPage();
        $pdf->SetFont('Helvetica','B', 10);
        $pdf->SetXY(6, $pdf->GetY()+2);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(200));
        $pdf->Row(array("Total Departamento {$departamento->nombre}"), false, 0, null, 1, 1);
        $pdf->Row(array("____________________________________________________________________________________________________"), false, 0, null, 1, 1);

        $pdf->SetFont('Helvetica','', 9);
        $y2 = $pdf->GetY();
        // Sueldo
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Sueldo', String::formatoNumero($total_dep['sueldo'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        // Horas Extras
        if ($total_dep['horas_extras'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Horas Extras', String::formatoNumero($total_dep['horas_extras'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        // Vacaciones y prima vacacional
        if ($total_dep['vacaciones'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Vacaciones', String::formatoNumero($total_dep['vacaciones'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }

          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Prima vacacional', String::formatoNumero($total_dep['prima_vacacional'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        // Subsidio
        if ($total_dep['subsidio'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Subsidio', String::formatoNumero($total_dep['subsidio'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        // PTU
        if ($total_dep['ptu'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'PTU', String::formatoNumero($total_dep['ptu'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        // Aguinaldo
        if ($total_dep['aguinaldo'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Aguinaldo', String::formatoNumero($total_dep['aguinaldo'], 2, '$', false)), false, 0, null, 1, 1);
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
        if ($total_dep['infonavit'] > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Infonavit', String::formatoNumero($total_dep['infonavit'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'I.M.M.S.', String::formatoNumero($total_dep['imms'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }

        if ($total_dep['prestamos'] > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Prestamos', String::formatoNumero($total_dep['prestamos'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        if ($total_dep['isr'] > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'ISR', String::formatoNumero($total_dep['isr'], 2, '$', false)), false, 0, null, 1, 1);
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
        $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($total_dep['total_percepcion'], 2, '$', false), '', 'Total Deducciones', String::formatoNumero($total_dep['total_deduccion'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

        $pdf->SetFont('Helvetica','B', 9);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Total Neto', String::formatoNumero($total_dep['total_neto'], 2, '$', false)), false, 0, null, 1, 1);
      }

      $pdf->SetFont('Helvetica','', 10);
    }

    //finiquito
    $total_dep = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0, 
        'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'isr' => 0, 
        'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0);
    $dep_tiene_empleados = true;
    $y = $pdf->GetY();
    foreach ($finiquitos as $key => $empleado)
    {
      if($dep_tiene_empleados)
      {
        $pdf->SetFont('Helvetica','B', 10);
        $pdf->SetXY(6, $pdf->GetY()+6);
        $pdf->Cell(130, 6, 'Finiquitos', 0, 0, 'L', 0);

        $pdf->SetFont('Helvetica','', 10);
        $pdf->SetXY(6, $pdf->GetY() + 8);
        $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
        $pdf->Row(array('', 'Percepción', 'Importe', '', 'Deducción', 'Importe'), false, false, null, 2, 1);

        $pdf->SetFont('Helvetica','', 10);
        $pdf->SetXY(6, $pdf->GetY() - 2);
        $pdf->Cell(200, 2, "________________________________________________________________________________________________________", 0, 0, 'L', 0);
        $dep_tiene_empleados = false;
      }

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY() + 4);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(15, 100));
      $pdf->Row(array($empleado->id, $empleado->apellido_paterno.' '.$empleado->apellido_materno.' '.$empleado->nombre), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetFont('Helvetica','', 9);
      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(50, 70, 50));
      $pdf->Row(array('Sin Puesto', "RFC: {$empleado->rfc}", "Afiliciación IMSS: {$empleado->no_seguro}"), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(50, 35, 35, 35, 30));
      $pdf->Row(array("Fecha Ingr: {$empleado->fecha_entrada}", "Sal. diario: {$empleado->salario_diario}", "S.D.I: 0", "S.B.C: 0", 'Cotiza fijo'), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $horasExtras = 0;

      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(35, 35, 25, 35, 70));
      $pdf->Row(array("Dias Pagados: {$empleado->dias_trabajados}", "Tot Hrs trab: " . $empleado->dias_trabajados * 8, 'Hrs dia: 8.00', "Hrs extras: " . number_format('0', 2), "CURP: {$empleado->curp}"), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $y2 = $pdf->GetY();

      // Percepciones
      // $percepciones = $empleado->nomina->percepciones;

      // Sueldo
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Sueldo', String::formatoNumero($empleado->sueldo_semanal, 2, '$', false)), false, 0, null, 1, 1);
      $total_dep['sueldo'] += $empleado->sueldo_semanal;
      $total_gral['sueldo'] += $empleado->sueldo_semanal;
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }

      // // Horas Extras
      // if ($empleado->horas_extras_dinero > 0)
      // {
      //   $pdf->SetXY(6, $pdf->GetY());
      //   $pdf->SetAligns(array('L', 'L', 'R'));
      //   $pdf->SetWidths(array(15, 62, 25));
      //   $pdf->Row(array('', 'Horas Extras', String::formatoNumero($empleado->horas_extras_dinero, 2, '$', false)), false, 0, null, 1, 1);
      //   $total_dep['horas_extras'] += $empleado->horas_extras_dinero;
      //   $total_gral['horas_extras'] += $empleado->horas_extras_dinero;
      //   if($pdf->GetY() >= $pdf->limiteY)
      //   {
      //     $pdf->AddPage();
      //     $y2 = $pdf->GetY();
      //   }
      // }

      // Vacaciones y prima vacacional
      if ($empleado->vacaciones > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Vacaciones', String::formatoNumero($empleado->vacaciones, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['vacaciones'] += $empleado->vacaciones;
        $total_gral['vacaciones'] += $empleado->vacaciones;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Prima vacacional', String::formatoNumero($empleado->prima_vacacional, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['prima_vacacional'] += $empleado->prima_vacacional;
        $total_gral['prima_vacacional'] += $empleado->prima_vacacional;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // // Subsidio
      // if ($empleado->nomina_fiscal_subsidio > 0)
      // {
      //   $pdf->SetXY(6, $pdf->GetY());
      //   $pdf->SetAligns(array('L', 'L', 'R'));
      //   $pdf->SetWidths(array(15, 62, 25));
      //   $pdf->Row(array('', 'Subsidio', String::formatoNumero($empleado->nomina_fiscal_subsidio, 2, '$', false)), false, 0, null, 1, 1);
      //   $total_dep['subsidio'] += $empleado->nomina_fiscal_subsidio;
      //   $total_gral['subsidio'] += $empleado->nomina_fiscal_subsidio;
      //   if($pdf->GetY() >= $pdf->limiteY)
      //   {
      //     $pdf->AddPage();
      //     $y2 = $pdf->GetY();
      //   }
      // }

      // // PTU
      // if ($empleado->nomina_fiscal_ptu > 0)
      // {
      //   $pdf->SetXY(6, $pdf->GetY());
      //   $pdf->SetAligns(array('L', 'L', 'R'));
      //   $pdf->SetWidths(array(15, 62, 25));
      //   $pdf->Row(array('', 'PTU', String::formatoNumero($empleado->nomina_fiscal_ptu, 2, '$', false)), false, 0, null, 1, 1);
      //   $total_dep['ptu'] += $empleado->nomina_fiscal_ptu;
      //   $total_gral['ptu'] += $empleado->nomina_fiscal_ptu;
      //   if($pdf->GetY() >= $pdf->limiteY)
      //   {
      //     $pdf->AddPage();
      //     $y2 = $pdf->GetY();
      //   }
      // }

      // Aguinaldo
      if ($empleado->aguinaldo > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Aguinaldo', String::formatoNumero($empleado->aguinaldo, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['aguinaldo'] += $empleado->aguinaldo;
        $total_gral['aguinaldo'] += $empleado->aguinaldo;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      $y = $pdf->GetY();

      // Deducciones
      // $deducciones = $empleado->nomina->deducciones;
      $pdf->SetFont('Helvetica','', 9);

      $pdf->SetY($y2);

      if ($empleado->isr != 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'ISR', String::formatoNumero($empleado->isr, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['isr'] += $empleado->isr;
        $total_gral['isr'] += $empleado->isr;
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
      $total_dep['total_percepcion'] += $empleado->total_percepcion;
      $total_gral['total_percepcion'] += $empleado->total_percepcion;
      $total_dep['total_deduccion'] += $empleado->total_deduccion;
      $total_gral['total_deduccion'] += $empleado->total_deduccion;
      $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($empleado->total_percepcion, 2, '$', false), '', 'Total Deducciones', String::formatoNumero($empleado->total_deduccion, 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $total_dep['total_neto'] += $empleado->total_neto;
      $total_gral['total_neto'] += $empleado->total_neto;
      $pdf->Row(array('', 'Total Neto', String::formatoNumero($empleado->total_neto, 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica', '', 9);
      $pdf->SetXY(120, $pdf->GetY()+3);
      $pdf->Cell(200, 2, "--------------------------------------------------------------------------------------", 0, 0, 'L', 0);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
    }

    //****** Total finiquito ******
    if($dep_tiene_empleados == false)
    {
      if($pdf->GetY()+10 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetFont('Helvetica','B', 10);
      $pdf->SetXY(6, $pdf->GetY()+2);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(200));
      $pdf->Row(array("Total Departamento Finiquito"), false, 0, null, 1, 1);
      $pdf->Row(array("____________________________________________________________________________________________________"), false, 0, null, 1, 1);

      $pdf->SetFont('Helvetica','', 9);
      $y2 = $pdf->GetY();
      // Sueldo
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Sueldo', String::formatoNumero($total_dep['sueldo'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }

      // Horas Extras
      if ($total_dep['horas_extras'] > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Horas Extras', String::formatoNumero($total_dep['horas_extras'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // Vacaciones y prima vacacional
      if ($total_dep['vacaciones'] > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Vacaciones', String::formatoNumero($total_dep['vacaciones'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Prima vacacional', String::formatoNumero($total_dep['prima_vacacional'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // Subsidio
      if ($total_dep['subsidio'] > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Subsidio', String::formatoNumero($total_dep['subsidio'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // PTU
      if ($total_dep['ptu'] > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'PTU', String::formatoNumero($total_dep['ptu'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // Aguinaldo
      if ($total_dep['aguinaldo'] > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Aguinaldo', String::formatoNumero($total_dep['aguinaldo'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      $y = $pdf->GetY();

      // Deducciones
      // $deducciones = $empleado->nomina->deducciones;
      $pdf->SetFont('Helvetica','', 9);

      $pdf->SetY($y2);
      if ($total_dep['infonavit'] > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Infonavit', String::formatoNumero($total_dep['infonavit'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'I.M.M.S.', String::formatoNumero($total_dep['imms'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }

      if ($total_dep['prestamos'] > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Prestamos', String::formatoNumero($total_dep['prestamos'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      if ($total_dep['isr'] > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'ISR', String::formatoNumero($total_dep['isr'], 2, '$', false)), false, 0, null, 1, 1);
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
      $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($total_dep['total_percepcion'], 2, '$', false), '', 'Total Deducciones', String::formatoNumero($total_dep['total_deduccion'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Total Neto', String::formatoNumero($total_dep['total_neto'], 2, '$', false)), false, 0, null, 1, 1);
    }

    //********* Total general ***************
    if($pdf->GetY()+10 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Helvetica','B', 10);
    $pdf->SetXY(6, $pdf->GetY()+2);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array("Total General"), false, 0, null, 1, 1);
    $pdf->Row(array("____________________________________________________________________________________________________"), false, 0, null, 1, 1);

    $pdf->SetFont('Helvetica','', 9);
    $y2 = $pdf->GetY();
    // Sueldo
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25));
    $pdf->Row(array('', 'Sueldo', String::formatoNumero($total_gral['sueldo'], 2, '$', false)), false, 0, null, 1, 1);
    if($pdf->GetY() >= $pdf->limiteY)
    {
      $pdf->AddPage();
      $y2 = $pdf->GetY();
    }

    // Horas Extras
    if ($total_gral['horas_extras'] > 0)
    {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Horas Extras', String::formatoNumero($total_gral['horas_extras'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }
    }

    // Vacaciones y prima vacacional
    if ($total_gral['vacaciones'] > 0)
    {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Vacaciones', String::formatoNumero($total_gral['vacaciones'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }

      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Prima vacacional', String::formatoNumero($total_gral['prima_vacacional'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }
    }

    // Subsidio
    if ($total_gral['subsidio'] > 0)
    {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Subsidio', String::formatoNumero($total_gral['subsidio'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }
    }

    // PTU
    if ($total_gral['ptu'] > 0)
    {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'PTU', String::formatoNumero($total_gral['ptu'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }
    }

    // Aguinaldo
    if ($total_gral['aguinaldo'] > 0)
    {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Aguinaldo', String::formatoNumero($total_gral['aguinaldo'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }
    }

    $y = $pdf->GetY();

    // Deducciones
    // $deducciones = $empleado->nomina->deducciones;
    $pdf->SetFont('Helvetica','', 9);

    $pdf->SetY($y2);
    if ($total_gral['infonavit'] > 0)
    {
      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Infonavit', String::formatoNumero($total_gral['infonavit'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y = $pdf->GetY();
      }
    }

    $pdf->SetXY(108, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25));
    $pdf->Row(array('', 'I.M.M.S.', String::formatoNumero($total_gral['imms'], 2, '$', false)), false, 0, null, 1, 1);
    if($pdf->GetY() >= $pdf->limiteY)
    {
        $pdf->AddPage();
        $y = $pdf->GetY();
      }

    if ($total_gral['prestamos'] > 0)
    {
      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Prestamos', String::formatoNumero($total_gral['prestamos'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y = $pdf->GetY();
      }
    }

    if ($total_gral['isr'] > 0)
    {
      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'ISR', String::formatoNumero($total_gral['isr'], 2, '$', false)), false, 0, null, 1, 1);
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
    $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($total_gral['total_percepcion'], 2, '$', false), '', 'Total Deducciones', String::formatoNumero($total_gral['total_deduccion'], 2, '$', false)), false, 0, null, 1, 1);
    if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

    $pdf->SetFont('Helvetica','B', 9);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25));
    $pdf->Row(array('', 'Total Neto', String::formatoNumero($total_gral['total_neto'], 2, '$', false)), false, 0, null, 1, 1);

    $pdf->Output('Nomina.pdf', 'I');
  }


  public function pdfRptDataNominaFiscal($datos, $empresaId)
  {
    // echo "<pre>";
    //   var_dump($datos, $empresaId, $empleadoId);
    // echo "</pre>";exit;
    // $startTime = new DateTime(date('Y-m-d H:i:s'));

    $this->load->library('cfdi');
    $this->load->library('facturartebarato_api');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');

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
    foreach ($datos['empleado_id'] as $key => $empleadoId)
    {
        // $empleado = $this->usuarios_model->get_usuario_info($empleadoId, true);
        // $empleado = $this->usuarios_model->get_usuario_info($empleadoId, true);

        $empleadoNomina = $this->nomina(
          $configuraciones,
          array('semana' => $datos['numSemana'], 'empresaId' => $empresaId),
          $empleadoId,
          $datos['horas_extras'][$key],
          $datos['descuento_playeras'][$key],
          $datos['subsidio'][$key],
          $datos['isr'][$key],
          $datos['utilidad_empresa'],
          $datos['descuento_otros'][$key]
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
            if ($datos['con_vacaciones'][$key] === '1' && $empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]['total'] != 0)
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

        $totalNoFiscal = floatval($datos['total_no_fiscal'][$key]);

        $nominasEmpleados[] = array(
          'id_empleado' => $empleadoId,
          'nombre_empleado' => $empleadoNomina[0]->nombre,
          'id_empresa' => $empresaId,
          'anio' => $fechasSemana['anio'],
          'semana' => $datos['numSemana'],
          'fecha_inicio' => $fechasSemana['fecha_inicio'],
          'fecha_final' => $fechasSemana['fecha_final'],
          'dias_trabajados' => $empleadoNomina[0]->dias_trabajados,
          'salario_diario' => $empleadoNomina[0]->salario_diario,
          'salario_integral' => $empleadoNomina[0]->nomina->salario_diario_integrado,
          'subsidio' => $datos['subsidio'][$key],
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
          'isr' => $datos['isr'][$key],
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
          'nombre_puesto' => $empleadoNomina[0]->puesto,
          'salario_real' => $empleadoNomina[0]->salario_diario_real,
          'sueldo_real' => $empleadoNomina[0]->salario_diario_real * $empleadoNomina[0]->dias_trabajados,
          'total_no_fiscal' => $totalNoFiscal,
          'horas_extras' => $empleadoNomina[0]->horas_extras_dinero,
          'horas_extras_grabable' => $empleadoNomina[0]->nomina->percepciones['horas_extras']['ImporteGravado'],
          'horas_extras_excento' => $empleadoNomina[0]->nomina->percepciones['horas_extras']['ImporteExcento'],
          'descuento_playeras' => $datos['descuento_playeras'][$key],
          'descuento_otros' => $datos['descuento_otros'][$key],
          'utilidad_empresa' => $empleadoNomina[0]->utilidad_empresa,
          'domingo' => $empleadoNomina[0]->domingo,
        );
    }

    return array('data' => $nominasEmpleados);
  }

  public function pdfRptNominaFiscal($semana, $empresaId)
  {
    // var_dump($_POST);
    // exit();
    // $empleados = $this->pdfRptDataNominaFiscal($_POST, $empresaId);
    $semana = $this->fechasDeUnaSemana($semana);

    $this->load->model('usuarios_model');
    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);
    
    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('L', 'mm', 'Letter');
    $pdf->show_head = true;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = "Lista de Raya de {$semana['fecha_inicio']} al {$semana['fecha_final']}";
    $pdf->titulo3 = "Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    //no mostrar algunas columnas
    $ver_des_otro = $ver_des_playera = false;
    foreach ($_POST['empleado_id'] as $key => $empleado){
      if($_POST['descuento_playeras'][$key]>0) $ver_des_playera = true;
      if($_POST['descuento_otros'][$key]>0) $ver_des_otro = true;
    }

    $columnas = array('n' => array(), 'w' => array(64, 20, 20, 20, 20, 20, 20, 20, 20), 'a' => array('L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R'));
    $columnas['n'][] = 'NOMBRE';
    $columnas['n'][] = 'SUELDO';
    $columnas['n'][] = 'OTRAS';
    $columnas['n'][] = 'DOMINGO';
    $columnas['n'][] = 'PTMO';
    $columnas['n'][] = 'INFONAVIT';
    if($ver_des_playera){
      $columnas['n'][] = 'DESC. PLAY';
      $columnas['w'][] = 20;
      $columnas['a'][] = 'R';
    }
    if($ver_des_otro){
      $columnas['n'][] = 'DESC. OTRO';
      $columnas['w'][] = 20;
      $columnas['a'][] = 'R';
    }
    $columnas['n'][] = 'TOTAL A PAGAR';
    $columnas['n'][] = 'TRANSF';
    $columnas['n'][] = 'TOTAL COMPLEM';

    $pdf->SetFont('Helvetica','B', 8);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns($columnas['a']);
    $pdf->SetWidths($columnas['w']);
    $pdf->Row($columnas['n'], false, false, null, 2, 1);

    $ttotal_aseg_no_trs = $sueldo_semanal_real = $otras_percepciones = $domingo = $total_prestamos = $total_infonavit = $descuento_playeras = $descuento_otros = $ttotal_pagar = $ttotal_nomina = $total_no_fiscal = 0;
    $y = $pdf->GetY();
    
    $departamentos = $this->usuarios_model->departamentos();
    foreach ($departamentos as $keyd => $departamento)
    {
      if($pdf->GetY() >= $pdf->limiteY){
        $pdf->AddPage();
        $pdf->SetFont('Helvetica','B', 8);
        $pdf->SetXY(6, $pdf->GetY());
        // $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R'));
        // $pdf->SetWidths(array(64, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20));
        $pdf->Row($columnas['n'], false, false, null, 2, 1);
      }

      // $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R'));
      // $pdf->SetWidths(array(64, 22, 20, 20, 20, 20, 20, 20, 20, 20, 20));
      $sueldo_semanal_real1 = $otras_percepciones1 = $domingo1 = $total_prestamos1 = $total_infonavit1 = $descuento_playeras1 = $descuento_otros1 = $ttotal_pagar1 = $ttotal_nomina1 = $total_no_fiscal1 = 0;
      
      $pdf->SetFont('Helvetica','B', 10);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Cell(130, 6, $departamento->nombre, 0, 0, 'L', 0);

      $pdf->SetXY(6, $pdf->GetY()+6);
      foreach ($_POST['empleado_id'] as $key => $empleado)
      {
        if($departamento->id_departamento == $_POST['departamento_id'][$key])
        {
          $empleado = $this->usuarios_model->get_usuario_info($empleado, true)['info'][0];
          
          $pdf->SetFont('Helvetica','', 8);
          if($pdf->GetY() >= $pdf->limiteY){
            $pdf->AddPage();
            $pdf->SetFont('Helvetica','B', 8);
            $pdf->SetXY(6, $pdf->GetY());
            // $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R'));
            // $pdf->SetWidths(array(64, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20));
            $pdf->Row($columnas['n'], false, false, null, 2, 1);
          }

          $pdf->SetFont('Helvetica','', 8);
          $total_pagar = $_POST['sueldo_semanal_real'][$key] + 
            ($_POST['bonos'][$key]+$_POST['otros'][$key]) + 
            $_POST['domingo'][$key] -
            $_POST['total_prestamos'][$key] -
            $_POST['total_infonavit'][$key] -
            $_POST['descuento_playeras'][$key] -
            $_POST['descuento_otros'][$key];
          $pdf->SetXY(6, $pdf->GetY());

          $dataarr = array();
          $dataarr[] = $empleado->apellido_paterno.' '.$empleado->apellido_materno.' '.$empleado->nombre;
          $dataarr[] = String::formatoNumero($_POST['sueldo_semanal_real'][$key], 2, '$', false);
          $dataarr[] = String::formatoNumero(($_POST['bonos'][$key]+$_POST['otros'][$key]), 2, '$', false);
          $dataarr[] = String::formatoNumero($_POST['domingo'][$key], 2, '$', false);
          $dataarr[] = String::formatoNumero($_POST['total_prestamos'][$key], 2, '$', false);
          $dataarr[] = String::formatoNumero($_POST['total_infonavit'][$key], 2, '$', false);
          if($ver_des_playera)
            $dataarr[] = String::formatoNumero($_POST['descuento_playeras'][$key], 2, '$', false);
          if($ver_des_otro)
            $dataarr[] = String::formatoNumero($_POST['descuento_otros'][$key], 2, '$', false);
          $dataarr[] = String::formatoNumero($total_pagar, 2, '$', false);
          $dataarr[] = String::formatoNumero($_POST['ttotal_nomina'][$key], 2, '$', false);
          $dataarr[] = String::formatoNumero($_POST['total_no_fiscal'][$key], 2, '$', false);
          
          $pdf->Row($dataarr, false, true, null, 2, 1);
          $sueldo_semanal_real += $_POST['sueldo_semanal_real'][$key];
          $otras_percepciones  += ($_POST['bonos'][$key]+$_POST['otros'][$key]);
          $domingo             += $_POST['domingo'][$key];
          $total_prestamos     += $_POST['total_prestamos'][$key];
          $total_infonavit     += $_POST['total_infonavit'][$key];
          $descuento_playeras  += $_POST['descuento_playeras'][$key];
          $descuento_otros     += $_POST['descuento_otros'][$key];
          $ttotal_pagar        += $total_pagar;
          $ttotal_nomina       += $_POST['ttotal_nomina'][$key];
          $total_no_fiscal     += $_POST['total_no_fiscal'][$key];
          $ttotal_aseg_no_trs  += $_POST['total_percepciones'][$key]-$_POST['total_deducciones'][$key];

          $sueldo_semanal_real1 += $_POST['sueldo_semanal_real'][$key];
          $otras_percepciones1  += ($_POST['bonos'][$key]+$_POST['otros'][$key]);
          $domingo1             += $_POST['domingo'][$key];
          $total_prestamos1     += $_POST['total_prestamos'][$key];
          $total_infonavit1     += $_POST['total_infonavit'][$key];
          $descuento_playeras1  += $_POST['descuento_playeras'][$key];
          $descuento_otros1     += $_POST['descuento_otros'][$key];
          $ttotal_pagar1        += $total_pagar;
          $ttotal_nomina1       += $_POST['ttotal_nomina'][$key];
          $total_no_fiscal1     += $_POST['total_no_fiscal'][$key];
        }
      }

      if($pdf->GetY() >= $pdf->limiteY+10)
        $pdf->AddPage();
        
      $pdf->SetFont('Helvetica','B', 8);
      $pdf->SetXY(6, $pdf->GetY());
      $datatto = array();
      $datatto[] = 'TOTAL';
      $datatto[] = String::formatoNumero($sueldo_semanal_real1, 2, '$', false);
      $datatto[] = String::formatoNumero($otras_percepciones1, 2, '$', false);
      $datatto[] = String::formatoNumero($domingo1, 2, '$', false);
      $datatto[] = String::formatoNumero($total_prestamos1, 2, '$', false);
      $datatto[] = String::formatoNumero($total_infonavit1, 2, '$', false);
      if($ver_des_playera)
        $datatto[] = String::formatoNumero($descuento_playeras1, 2, '$', false);
      if($ver_des_otro)
        $datatto[] = String::formatoNumero($descuento_otros1, 2, '$', false);
      $datatto[] = String::formatoNumero($ttotal_pagar1, 2, '$', false);
      $datatto[] = String::formatoNumero($ttotal_nomina1, 2, '$', false);
      $datatto[] = String::formatoNumero($total_no_fiscal1, 2, '$', false);
      $pdf->Row($datatto, false, true, null, 2, 1);
    }

    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetFont('Helvetica','B', 8);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
    $datatto = array();
    $datatto[] = 'TOTAL';
    $datatto[] = String::formatoNumero($sueldo_semanal_real, 2, '$', false);
    $datatto[] = String::formatoNumero($otras_percepciones, 2, '$', false);
    $datatto[] = String::formatoNumero($domingo, 2, '$', false);
    $datatto[] = String::formatoNumero($total_prestamos, 2, '$', false);
    $datatto[] = String::formatoNumero($total_infonavit, 2, '$', false);
    if($ver_des_playera)
      $datatto[] = String::formatoNumero($descuento_playeras, 2, '$', false);
    if($ver_des_otro)
      $datatto[] = String::formatoNumero($descuento_otros, 2, '$', false);
    $datatto[] = String::formatoNumero($ttotal_pagar, 2, '$', false);
    $datatto[] = String::formatoNumero($ttotal_nomina, 2, '$', false);
    $datatto[] = String::formatoNumero($total_no_fiscal, 2, '$', false);
    $pdf->Row($datatto, false, true, null, 2, 1);

    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetFont('Helvetica','B', 8);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
    $pdf->SetAligns(array('L', 'L', 'L'));
    $pdf->SetWidths(array(50, 50, 50));
    $pdf->Row(array(
      'NOMINA FISCAL: '.String::formatoNumero($ttotal_aseg_no_trs, 2, '$', false),
      'TRANSFERIDO: '.String::formatoNumero($ttotal_nomina, 2, '$', false),
      'CHEQUE FISCAL: '.String::formatoNumero(($ttotal_aseg_no_trs-$ttotal_nomina), 2, '$', false),
      ), false, true, null, 2, 1);

    $pdf->Output('Nomina.pdf', 'I');
  }

  public function pdfReciboNominaFiscal($empleadoId, $semana, $anio, $empresaId)
  {
    $this->load->model('empresas_model');

    $semana = $this->fechasDeUnaSemana($semana, $anio);
    $configuraciones = $this->configuraciones();
    $filtros = array('semana' => $semana['semana'], 'empresaId' => $empresaId);
    $empleados = $this->nomina($configuraciones, $filtros, $empleadoId);
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);

    // echo "<pre>";
    //   var_dump($empleados);
    // echo "</pre>";exit;
    
    include(APPPATH.'libraries/phpqrcode/qrlib.php');

    $nomina = $this->db->query("SELECT uuid, xml FROM nomina_fiscal WHERE id_empleado = {$empleadoId} AND id_empresa = {$empresaId} AND anio = {$semana['anio']} AND semana = {$semana['semana']}")->row();

    $xml = simplexml_load_string(str_replace(array('cfdi:', 'tfd:', 'nomina:'), '', $nomina->xml));
    
    // echo "<pre>";
    //   var_dump($nomina, $xml);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = "Recibo de Raya de {$semana['fecha_inicio']} al {$semana['fecha_final']}";
    $pdf->titulo3 = "Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $total_gral = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0, 
      'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'isr' => 0, 
      'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0);

    $total_dep = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0, 
      'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'isr' => 0, 
      'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0);

    $dep_tiene_empleados = true;
    $y = $pdf->GetY();
    foreach ($empleados as $key => $empleado)
    {
      if($dep_tiene_empleados)
      {
        $pdf->SetFont('Helvetica','', 10);
        $pdf->SetXY(6, $pdf->GetY() + 4);
        $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
        $pdf->Row(array('', 'Percepción', 'Importe', '', 'Deducción', 'Importe'), false, false, null, 2, 1);

        $pdf->SetFont('Helvetica','', 10);
        $pdf->SetXY(6, $pdf->GetY() - 2);
        $pdf->Cell(200, 2, "________________________________________________________________________________________________________", 0, 0, 'L', 0);
        $dep_tiene_empleados = false;
      }

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
      $pdf->Row(array($empleado->puesto, "RFC: {$empleado->rfc}", "Afiliciación IMSS: {$empleado->no_seguro}"), false, false, null, 1, 1);
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
      $pdf->Row(array('', 'Sueldo', String::formatoNumero($percepciones['sueldo']['total'], 2, '$', false)), false, 0, null, 1, 1);
      $total_dep['sueldo'] += $percepciones['sueldo']['total'];
      $total_gral['sueldo'] += $percepciones['sueldo']['total'];
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
        $pdf->Row(array('', 'Horas Extras', String::formatoNumero($empleado->horas_extras_dinero, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['horas_extras'] += $empleado->horas_extras_dinero;
        $total_gral['horas_extras'] += $empleado->horas_extras_dinero;
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
        $pdf->Row(array('', 'Vacaciones', String::formatoNumero($empleado->nomina_fiscal_vacaciones, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['vacaciones'] += $empleado->nomina_fiscal_vacaciones;
        $total_gral['vacaciones'] += $empleado->nomina_fiscal_vacaciones;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Prima vacacional', String::formatoNumero($empleado->nomina->prima_vacacional, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['prima_vacacional'] += $empleado->nomina->prima_vacacional;
        $total_gral['prima_vacacional'] += $empleado->nomina->prima_vacacional;
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
        $pdf->Row(array('', 'Subsidio', String::formatoNumero($empleado->nomina_fiscal_subsidio, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['subsidio'] += $empleado->nomina_fiscal_subsidio;
        $total_gral['subsidio'] += $empleado->nomina_fiscal_subsidio;
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
        $pdf->Row(array('', 'PTU', String::formatoNumero($empleado->nomina_fiscal_ptu, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['ptu'] += $empleado->nomina_fiscal_ptu;
        $total_gral['ptu'] += $empleado->nomina_fiscal_ptu;
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
        $pdf->Row(array('', 'Aguinaldo', String::formatoNumero($empleado->nomina_fiscal_aguinaldo, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['aguinaldo'] += $empleado->nomina_fiscal_aguinaldo;
        $total_gral['aguinaldo'] += $empleado->nomina_fiscal_aguinaldo;
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
        $pdf->Row(array('', 'Infonavit', String::formatoNumero($deducciones['infonavit']['total'], 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['infonavit'] += $deducciones['infonavit']['total'];
        $total_gral['infonavit'] += $deducciones['infonavit']['total'];
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'I.M.M.S.', String::formatoNumero($deducciones['imss']['total'] + $deducciones['rcv']['total'], 2, '$', false)), false, 0, null, 1, 1);
      $total_dep['imms'] += $deducciones['imss']['total'] + $deducciones['rcv']['total'];
      $total_gral['imms'] += $deducciones['imss']['total'] + $deducciones['rcv']['total'];
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
        $pdf->Row(array('', 'Prestamos', String::formatoNumero($empleado->nomina_fiscal_prestamos, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['prestamos'] += $empleado->nomina_fiscal_prestamos;
        $total_gral['prestamos'] += $empleado->nomina_fiscal_prestamos;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      // if ($empleado->descuento_playeras > 0)
      // {
      //   $pdf->SetXY(108, $pdf->GetY());
      //   $pdf->SetAligns(array('L', 'L', 'R'));
      //   $pdf->SetWidths(array(15, 62, 25));
      //   $pdf->Row(array('', 'Desc. Playeras', String::formatoNumero($empleado->descuento_playeras, 2, '$', false)), false, 0, null, 1, 1);
      //   if($pdf->GetY() >= $pdf->limiteY)
      //   {
      //     $pdf->AddPage();
      //     $y = $pdf->GetY();
      //   }
      // }

      if ($empleado->nomina_fiscal_isr > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'ISR', String::formatoNumero($empleado->nomina_fiscal_isr, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['isr'] += $empleado->nomina_fiscal_isr;
        $total_gral['isr'] += $empleado->nomina_fiscal_isr;
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
      $total_dep['total_percepcion'] += $empleado->nomina_fiscal_total_percepciones;
      $total_gral['total_percepcion'] += $empleado->nomina_fiscal_total_percepciones;
      $total_dep['total_deduccion'] += $empleado->nomina_fiscal_total_deducciones;
      $total_gral['total_deduccion'] += $empleado->nomina_fiscal_total_deducciones;
      $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($empleado->nomina_fiscal_total_percepciones, 2, '$', false), '', 'Total Deducciones', String::formatoNumero($empleado->nomina_fiscal_total_deducciones, 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $total_dep['total_neto'] += $empleado->nomina_fiscal_total_neto;
      $total_gral['total_neto'] += $empleado->nomina_fiscal_total_neto;
      $pdf->Row(array('', 'Total Neto', String::formatoNumero($empleado->nomina_fiscal_total_neto, 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica', '', 9);
      $pdf->SetXY(120, $pdf->GetY()+3);
      $pdf->Cell(200, 2, "--------------------------------------------------------------------------------------", 0, 0, 'L', 0);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
    }

    if($xml === false)
      true;
    else
    {
      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY()+4);
      $pdf->Cell(78, 4, 'RFC EMISOR: '.$xml->Emisor[0]['rfc'], 0, 0, 'L', 0);
      
      $pdf->SetXY(86, $pdf->GetY());
      $pdf->Cell(78, 4, 'Forma de Pago: '.$xml[0]['formaDePago'], 0, 0, 'L', 0);

      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(158, $pdf->GetY());
      $pdf->Cell(78, 4, 'Condicion de Pago: Contado', 0, 0, 'L', 0);

      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(86, $pdf->GetY()+5);
      $pdf->Cell(78, 4, "Metodo de Pago: {$xml[0]['metodoDePago']}", 0, 0, 'L', 0);

      $cuenta_banco = substr($empleado->cuenta_banco, -4);
      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(158, $pdf->GetY());
      $pdf->Cell(76, 4, "Cuenta de Pago: {$cuenta_banco}", 0, 0, 'L', 0);
      ////////////////////
      // Timbrado Datos //
      ////////////////////
      if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
          $pdf->AddPage();

      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(10, $pdf->GetY() + 5);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array('Sello Digital del CFDI:'), false, 0);

      $pdf->SetFont('helvetica', '', 8);
      $pdf->SetY($pdf->GetY() - 3);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array($xml->Complemento->TimbreFiscalDigital[0]['selloCFD']), false, 0);

      if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
          $pdf->AddPage();

      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(10, $pdf->GetY() - 2);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array('Sello Digital del SAT:'), false, 0);

      $pdf->SetFont('helvetica', '', 8);
      $pdf->SetY($pdf->GetY() - 3);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array($xml->Complemento->TimbreFiscalDigital[0]['selloSAT']), false, 0);

      /////////////
      // QR CODE //
      /////////////

      // formato
      // ?re=XAXX010101000&rr=XAXX010101000&tt=1234567890.123456&id=ad662d33-6934-459c-a128-BDf0393f0f44
      // 0000001213.520000

      $total = explode('.', $xml[0]['total']);

      // Obtiene la diferencia de caracteres en la parte entera.
      $diff = 10 - strlen($total[0]);

      // Agrega los 0 faltantes  a la parte entera.
      for ($i=0; $i < $diff; $i++)
        $total[0] = "0{$total[0]}";

      // Si el total no contiene decimales le asigna en la parte decimal 6 ceros.
      if (count($total) === 1)
      {
        $total[1] = '000000';
      }
      else
      {
        // Obtiene la diferencia de caracteres en la parte decimal.
        $diff = 6 - strlen($total[1]);

        // Agregar los 0 restantes en la parte decimal.
        for ($i=0; $i < $diff; $i++)
          $total[1] = "{$total[1]}0";
      }

      $code = "?re={$xml->Emisor[0]['rfc']}";
      $code .= "&rr={$xml->Receptor[0]['rfc']}";
      $code .= "&tt={$total[0]}.{$total[1]}";
      $code .= "&id={$xml->Complemento->TimbreFiscalDigital[0]['UUID']}";

      // echo "<pre>";
      //   var_dump($code, $total, $diff);
      // echo "</pre>";exit;

      QRcode::png($code, APPPATH.'media/qrtemp.png', 'H', 3);

      if($pdf->GetY() + 50 >= $pdf->limiteY) //salta de pagina si exede el max
          $pdf->AddPage();

      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Image(APPPATH.'media/qrtemp.png', null, null, 40);

      // Elimina el QR generado temporalmente.
      unlink(APPPATH.'media/qrtemp.png');

      ////////////////////
      // Timbrado Datos //
      ////////////////////

      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(45, $pdf->GetY() - 39);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(160));
      $pdf->Row(array('Cadena Original del complemento de certificación digital del SAT:'), false, 0);

      $pdf->SetFont('helvetica', '', 8);
      $cadenaOriginalSAT = "||{$xml->Complemento->TimbreFiscalDigital[0]['version']}|{$xml->Complemento->TimbreFiscalDigital[0]['UUID']}|{$xml->Complemento->TimbreFiscalDigital[0]['FechaTimbrado']}|{$xml->Complemento->TimbreFiscalDigital[0]['selloCFD']}|{$xml->Complemento->TimbreFiscalDigital[0]['noCertificadoSAT']}||";
      $pdf->SetXY(45, $pdf->GetY() - 3);
      $pdf->Row(array($cadenaOriginalSAT), false, 0);

      $pdf->SetFont('helvetica', 'B', 10);
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 171, 72);
      $pdf->SetXY(45, $pdf->GetY() + 1);
      $pdf->Cell(68, 6, "Folio Fiscal:", 0, 0, 'R', 1);

      $pdf->SetXY(125, $pdf->GetY());
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['UUID'], 0, 0, 'C', 0);

      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 171, 72);
      $pdf->SetXY(45, $pdf->GetY() + 7);
      $pdf->Cell(68, 6, "No de Serie del Certificado del SAT:", 0, 0, 'R', 1);

      $pdf->SetXY(125, $pdf->GetY());
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['noCertificadoSAT'], 0, 0, 'C', 0);

      $pdf->SetFont('helvetica', 'B', 10);
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 171, 72);
      $pdf->SetXY(45, $pdf->GetY() + 7);
      $pdf->Cell(68, 6, "Fecha y hora de certificación:", 0, 0, 'R', 1);

      $pdf->SetXY(125, $pdf->GetY());
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['FechaTimbrado'], 0, 0, 'C', 0);

      $pdf->SetXY(0, $pdf->GetY()+13);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->Cell(220, 6, 'ESTE DOCUMENTO ES UNA REPRESENTACION IMPRESA DE UN CFDI.', 0, 0, 'C', 0);
    }

    $pdf->Output('Nomina.pdf', 'I');
  }

  public function rptVacacionesPdf($empresaId)
  {
    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);

    $data = $this->db->query("SELECT 
        id, (COALESCE(u.apellido_paterno, '') || ' ' || COALESCE(u.apellido_materno, '') || ' ' || u.nombre) as nombre, 
        Date(fecha_entrada) AS fecha_entrada, id_departamente, Date(fecha_nacimiento) AS fecha_nacimiento,
        (SELECT Date(fecha) FROM nomina_fiscal_vacaciones WHERE id_empleado = u.id ORDER BY anio DESC, semana DESC LIMIT 1) AS fecha_ultima
      FROM usuarios AS u
      WHERE id_empresa = {$empresaId} AND user_nomina = 't' AND status = 't'")->result();

    
    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    // $pdf->titulo2 = "Lista de Raya de {$semana['fecha_inicio']} al {$semana['fecha_final']}";
    // $pdf->titulo3 = "Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $columnas = array(
      'n' => array('NOMBRE', 'ULTIMA V.', 'PROX V.', 'DIAS', 'CUMPLEAÑOS'), 
      'w' => array(90, 25, 25, 25, 25), 
      'a' => array('L', 'L', 'L', 'L', 'L'));
    
    $pdf->SetFont('Helvetica','B', 8);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns($columnas['a']);
    $pdf->SetWidths($columnas['w']);
    $pdf->Row($columnas['n'], false, false, null, 2, 1);

    $ttotal_aseg_no_trs = $sueldo_semanal_real = $otras_percepciones = $domingo = $total_prestamos = $total_infonavit = $descuento_playeras = $descuento_otros = $ttotal_pagar = $ttotal_nomina = $total_no_fiscal = 0;
    $y = $pdf->GetY();

    $this->load->library('nomina');
    $nomina = $this->nomina->setVacacionesConfig($this->getConfigNominaVacaciones());
    $fechaActual = new DateTime(date('Y-m-d'));
    
    foreach ($data as $key => $empleado)
    {
      $fechaInicioTrabajar = new DateTime($empleado->fecha_entrada);
      $anios_trabajados_empleado = intval($fechaInicioTrabajar->diff($fechaActual)->y);

      // if($departamento->id_departamento == $_POST['departamento_id'][$key])
      // {
      //   $empleado = $this->usuarios_model->get_usuario_info($empleado, true)['info'][0];
        
        $pdf->SetFont('Helvetica','', 8);
        if($pdf->GetY() >= $pdf->limiteY){
          $pdf->AddPage();
          $pdf->SetFont('Helvetica','B', 8);
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->Row($columnas['n'], false, false, null, 2, 1);
        }

        $pdf->SetFont('Helvetica','', 8);
        $pdf->SetXY(6, $pdf->GetY());

        if($empleado->fecha_ultima!='')
        {
          $fecha_aux = explode('-', $empleado->fecha_entrada);
          $fecha_aux[0] = date("Y", strtotime("{$empleado->fecha_ultima} +1 year"));
          $fecha_entrada = implode('-', $fecha_aux);
        }else{
          $fecha_entrada = strtotime("{$empleado->fecha_entrada} +1 year");
          if(date("Y", $fecha_entrada) < date("Y") )
            $fecha_entrada = strtotime( date("Y").'-'.date("m-d", $fecha_entrada). " +1 year");
          $fecha_entrada = date("Y-m-d", $fecha_entrada);
        }

        $cumpleanios = strtotime( date("Y").'-'.date("m-d", strtotime("{$empleado->fecha_nacimiento}")) );
        if($cumpleanios < strtotime("now"))
          $cumpleanios = strtotime( date("Y-m-d", $cumpleanios). " +1 year");
        $cumpleanios = date("Y-m-d", $cumpleanios);

        $dataarr = array(
          $empleado->nombre,
          ($empleado->fecha_ultima!=''? String::fechaATexto($empleado->fecha_ultima, '/c'): 'No a tenido'),
          String::fechaATexto($fecha_entrada, '/c'),
          $nomina->diasVacacionesCorresponden($anios_trabajados_empleado+1),
          String::fechaATexto($cumpleanios, '/c'),
          );
        
        $pdf->Row($dataarr, false, true, null, 2, 1);
      // }
    }

    $pdf->Output('Nomina.pdf', 'I');
  }

}
/* End of file nomina_fiscal_model.php */
/* Location: ./application/models/nomina_fiscal_model.php */