<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class nomina
{
  use nominaCalMensual;

  /**
   * Almacena la informacion del empleado.
   *
   * @var array
   */
  public $empleado;

  /**
   * Almacena la configuracion de la empresa
   *
   * @var object
   */
  public $empresaConfig;

  public $empresa;

  public $nominaFiltros;

  /**
   * Almacena la informacion de los dias de vacaciones a dar segun los años
   * trabajados del empleado.
   *
   * @var array
   */
  public $vacacionesConfig = array();

  /**
   * Almacena los salarios de las zonas.
   *
   * @var object
   */
  public $salariosZonasConfig = array();

  /**
   * Almacena las claves de las nominas de el patron.
   *
   * @var object
   */
  public $clavesPatron = array();

  /**
   * Almacena las tablas semanal y diaria del art. 113.
   *
   * @var array
   */
  public $tablasIsr = array();

  public $subsidio = null;

  public $isr = null;

  public $subsidioCausado = null;

  /*
   |------------------------------------------------------------------------
   | Setters
   |------------------------------------------------------------------------
   */


  public function setEmpresa(stdclass $empresa)
  {
    $this->empresa = $empresa;
    return $this;
  }

  /**
   * Asigna al empleado que se le calculara la nomina.
   * Estructura del object(stdClass)
   * [
   *   'salario_diario' => salario,
   *   'dias_trabajados' => dias,
   *   'fecha_entrada' => 'Y-m-d',
   *   'infonavit' => 0,
   *   'dias_faltados_anio' => 0
   * ]
   *
   * @param object $empleado
   */
  public function setEmpleado(stdclass $empleado)
  {
    $this->empleado = $empleado;
    return $this;
  }

  /**
   * Asigna la configuracion de la empresa.
   * Estructura del object(stdClass)
   *
   *  [
   *   'aguinaldo'        => dias,
   *   'prima_vacacional' => %,
   *   'puntualidad'      => %
   *   'asistencia'       => %
   *   'despensa'         => %
   *  ]
   *
   * @param object
   */
  public function setEmpresaConfig(stdclass $config)
  {
    $this->empresaConfig = $config;
    return $this;
  }

  public function setFiltros($filtros)
  {
    $this->nominaFiltros = $filtros;
    if (isset($filtros['calcMes'])) {
      $semanas = MyString::obtenerSemanasDelAnioV2($this->nominaFiltros['anio'], 0, $this->nominaFiltros['dia_inicia_semana'], true);
      for ($i=count($semanas)-1; $i >= 0; $i--) {
        if ($this->nominaFiltros['semana'] == $semanas[$i]['semana']) {
          $this->nominaFiltros['calcMes'] = $semanas[$i]['calcmes'];
        }
      }
    }

    return $this;
  }

  /**
   * Asigna la configuracion de los dias de vacaciones.
   * Estructura del Array
   *
   *  [
   *   'anio1' => anio,
   *   'anio2' => anio,
   *   'dias'  => dias
   *  ]
   *
   * @param object
   */
  public function setVacacionesConfig(array $config)
  {
    $this->vacacionesConfig = $config;
    return $this;
  }

  /**
   * Asigna los salarios de las zonas.
   *
   * @param object
   */
  public function setSalariosZonas(stdClass $config)
  {
    $this->salariosZonasConfig = $config;
    return $this;
  }

  /**
   * Asigna las claves para las percepciones y deducciones que seria las claves
   * de la contabilidad de cada patron.
   * [
   *   'sueldo' => CLAVE,
   *   'horas_extras' => CLAVE,
   *   'vacaciones' => CLAVE,
   *   'prima_vacacional' => CLAVE,
   *   'aguinaldo' => CLAVE,
   *   'imss' => CLAVE,
   *   'rcv' => CLAVE,
   *   'infonavit' => CLAVE,
   *   'otros' => CLAVE,
   * ]
   * @param array
   */
  public function setClavesPatron(array $config)
  {
    $this->clavesPatron = $config;
    return $this;
  }

  /**
   * Asigna las tablas del Art113 para calcular el isr.
   *
   * @param array $config
   */
  public function setTablasIsr(array $config)
  {
    $this->tablasIsr = $config;
    return $this;
  }

  /**
   * Asigna el subsidio e isr, si ya es asignado aqui ya no se calcula.
   *
   * @param array $config
   */
  public function setSubsidioIsr($subsidio, $isr, $subsidioCausado = 0)
  {
    $this->subsidio        = $subsidio;
    $this->isr             = $isr;
    $this->subsidioCausado = $subsidioCausado;

    if ($subsidio == 0.01 && $subsidioCausado == 0) {
      $this->subsidioCausado = 0.01;
    }

    if ($this->empleado->nomina_guardada == 't') {
      $this->subsidio        = $this->empleado->nomina_fiscal_subsidio;
      $this->isr             = $this->empleado->nomina_fiscal_isr;
      $this->subsidioCausado = $this->empleado->nomina_fiscal_subsidio_causado;

      if ($this->subsidio == 0.01 && $this->subsidioCausado == 0) {
        $this->subsidioCausado = 0.01;
      }
    }

    return $this;
  }

  public function calculoBasico($empleado)
  {
    $this->empleado = $empleado;
    $this->empleado->anios_trabajados      = $this->aniosTrabajadosEmpleado();
    $this->empleado->dias_vacaciones       = $this->diasDeVacaciones();
    $this->empleado->dias_prima_vacacional = $this->diasPrimaVacacional();
    $this->empleado->factor_integracion    = $this->factorIntegracion();
    $this->empleado->salario_diario_integrado = $this->sdi();
    return $this->empleado;
  }

  /**
   * Procesa la nomina.
   *
   * @return array
   */
  public function procesar()
  {
    $this->empleado->anios_trabajados      = $this->aniosTrabajadosEmpleado();
    $this->empleado->dias_vacaciones       = $this->empleado->dias_vacaciones_fijo>0? $this->empleado->dias_vacaciones_fijo: $this->diasDeVacaciones();
    $this->empleado->dias_prima_vacacional = $this->diasPrimaVacacional();
    $this->empleado->factor_integracion    = $this->factorIntegracion();
    $this->empleado->dias_aguinaldo        = 0;

    $this->empleado->nomina = new stdclass;
    $this->empleado->nomina->aguinaldo = $this->aguinaldo();
    $this->empleado->nomina->vacaciones = $this->vacaciones();
    $this->empleado->nomina->prima_vacacional = $this->primaVacacional();
    $this->empleado->nomina->salario_diario_integrado = $this->sdi();

    $this->calculoNominaTotaltes();

    if ($this->empleado->otros_datos != 'false') {
      $this->empleado->otros_datos = json_decode($this->empleado->otros_datos);
    }

    if (isset($this->empleado->calculo_anual) && $this->empleado->esta_asegurado === 't') {
      $this->setCalculoAnual();
    } elseif (isset($this->empleado->otros_datos->calculoAnual) && $this->empleado->esta_asegurado === 't') {
      $this->setCalculoAnual(true);
    }

    $this->calculoNominaTotaltes(false);

    $this->emisor();
    $this->receptor();

    // if ($this->nominaFiltros['calcMes'] == true) {
    //   $this->calculoMensual();
    // }

    return $this->empleado;
  }

  private function calculoNominaTotaltes($resetAll=true)
  {
    $this->datosNomina($resetAll);

    $this->confPercepciones();

    // Otros
    // $this->empleado->nomina->otrosPagos = array();

    $this->confDeducciones();

    // Totales Percepciones
    $totalSueldosClaves = array('022', '023', '025', '039', '044');
    $totalSeparacionIndemnizacionClaves = array('022', '023', '025');
    $excluirPercepciones = ['septimo_dia']; // Percepciones q ya están incluidas en otra
    foreach ($this->empleado->nomina->percepciones as $keyp => $percep) {
      if (!in_array($keyp, $excluirPercepciones)) {
        $this->empleado->nomina->subtotal          += $percep['total'];
        $this->empleado->nomina->TotalPercepciones += $percep['total'];
        $this->empleado->nomina->percepcionesTotales['TotalGravado'] += $percep['ImporteGravado'];
        $this->empleado->nomina->percepcionesTotales['TotalExento'] += $percep['ImporteExcento'];

        if (!in_array($percep['TipoPercepcion'], $totalSueldosClaves)) {
          if (!isset($this->empleado->nomina->percepcionesTotales['TotalSueldos']))
            $this->empleado->nomina->percepcionesTotales['TotalSueldos'] = 0;
          $this->empleado->nomina->percepcionesTotales['TotalSueldos'] += $percep['total'];
        }

        if (in_array($percep['TipoPercepcion'], $totalSeparacionIndemnizacionClaves)) {
          if (($percep['ImporteGravado']+$percep['ImporteExcento']) > 0) {
            if (!isset($this->empleado->nomina->percepcionesTotales['TotalSeparacionIndemnizacion']))
              $this->empleado->nomina->percepcionesTotales['TotalSeparacionIndemnizacion'] = 0;
            $this->empleado->nomina->percepcionesTotales['TotalSeparacionIndemnizacion'] += $percep['total'];

            $finte = $this->aniosTrabajadosEmpleado(true);
            $anios_antiguedad = $finte->y+($finte->m>5? 1: 0);
            $this->empleado->nomina->percepcionesSeparacionIndemnizacion = array(
              'TotalPagado'         => 0,
              'NumAñosServicio'     => $anios_antiguedad,
              'UltimoSueldoMensOrd' => floatval($this->empleado->salario_diario*30),
              'IngresoAcumulable'   => 0,
              'IngresoNoAcumulable' => 0,
            );

            if ($percep['TipoPercepcion'] == '022') { // Prima por antigüedad
              $this->empleado->nomina->percepcionesSeparacionIndemnizacion['TotalPagado'] += $percep['total'];
            } elseif ($percep['TipoPercepcion'] == '023') { // Pagos por separación
              $this->empleado->nomina->percepcionesSeparacionIndemnizacion['TotalPagado'] += $percep['total'];
            } else { // Indemnizaciones
              $this->empleado->nomina->percepcionesSeparacionIndemnizacion['TotalPagado'] += $percep['total'];
            }
          }
        }

        if ($percep['TipoPercepcion'] == '019' && ($percep['total']) > 0) { // hrs extras
          $this->empleado->nomina->percepciones[$keyp]['HorasExtra'] = array(
            array(
              'Dias' => '1', 'TipoHoras' => '03', 'HorasExtra' => ceil(($percep['total'])/($this->empleado->salario_diario>0? $this->empleado->salario_diario: 1)),
              'ImportePagado' => $percep['ImporteGravado']+$percep['ImporteExcento']),
            );
        }
      }
    }
    if (count($this->empleado->nomina->percepcionesSeparacionIndemnizacion) > 0) {
      if ($this->empleado->nomina->percepcionesTotales['TotalGravado'] >= $this->empleado->nomina->percepcionesSeparacionIndemnizacion['UltimoSueldoMensOrd'])
        $this->empleado->nomina->percepcionesSeparacionIndemnizacion['IngresoAcumulable'] = $this->empleado->nomina->percepcionesSeparacionIndemnizacion['UltimoSueldoMensOrd'];
      else
        $this->empleado->nomina->percepcionesSeparacionIndemnizacion['IngresoAcumulable'] = $this->empleado->nomina->percepcionesTotales['TotalGravado'];
      $this->empleado->nomina->percepcionesSeparacionIndemnizacion['IngresoNoAcumulable'] = abs($this->empleado->nomina->percepcionesTotales['TotalGravado']-$this->empleado->nomina->percepcionesSeparacionIndemnizacion['UltimoSueldoMensOrd']);
    }

    // Totales OtrosPagos
    foreach ($this->empleado->nomina->otrosPagos as $keyp => $otroPago) {
      $this->empleado->nomina->TotalOtrosPagos += floatval($otroPago['total']);
      $this->empleado->nomina->subtotal        += floatval($otroPago['total']);
    }

    if ($this->nominaFiltros['tipo_nomina']['tipo'] == 'se') {
      if ((isset($this->empleado->p_alimenticia) && $this->empleado->p_alimenticia > 0) ||
          isset($this->empleado->otros_datos->dePensionAlimenticia)) {
        $this->empleado->nomina->deducciones['pencion_alimenticia'] = $this->dPencionAlimenticia();
      }

      if ((isset($this->empleado->fonacot) && $this->empleado->fonacot > 0) ||
          isset($this->empleado->otros_datos->deInfonacot)) {
        $this->empleado->nomina->deducciones['infonacot'] = $this->dInfonacot();
      }
    }

    // Totales Deducciones
    foreach ($this->empleado->nomina->deducciones as $keyp => $deducc) {
      if ($deducc['TipoDeduccion'] == '002') //  || $deducc['TipoDeduccion'] == '101'
        $this->empleado->nomina->deduccionesTotales['TotalImpuestosRetenidos']   += $deducc['total'];
      else
        $this->empleado->nomina->deduccionesTotales['TotalOtrasDeducciones']   += $deducc['total'];
    }

    // Si es cualquier deduccion menos el isr entonces el importe se lo suma al descuento.
    // Si la deduccion es el ISR  no se lo suma al descuento ya que el isr ira en la parte de retenciones.
    $this->empleado->nomina->TotalDeducciones = $this->empleado->nomina->deduccionesTotales['TotalOtrasDeducciones'] + $this->empleado->nomina->deduccionesTotales['TotalImpuestosRetenidos'];
    $this->empleado->nomina->descuento        = $this->empleado->nomina->TotalDeducciones;
    $this->empleado->nomina->isr              = $this->empleado->nomina->deduccionesTotales['TotalImpuestosRetenidos'];
  }

  public function datosNomina($resetAll=true)
  {
    $this->empleado->nomina->Version           = '1.2';
    $this->empleado->nomina->TipoNomina        = $this->empleado->tipo_nomina;
    $this->empleado->nomina->FechaPago         = substr($this->empleado->fecha_final_pago, 0, 10);
    $this->empleado->nomina->FechaInicialPago  = substr($this->empleado->fecha_inicial_pago, 0, 10);
    $this->empleado->nomina->FechaFinalPago    = substr($this->empleado->fecha_final_pago, 0, 10);
    $this->empleado->nomina->NumDiasPagados    = ceil($this->empleado->dias_aguinaldo);
    $this->empleado->nomina->TotalPercepciones = 0;
    $this->empleado->nomina->TotalDeducciones  = 0;
    $this->empleado->nomina->TotalOtrosPagos   = 0;
    $this->empleado->nomina->subtotal          = 0;
    $this->empleado->nomina->descuento         = 0;
    $this->empleado->nomina->isr               = 0;

    if ($resetAll) {
      $this->empleado->nomina->percepciones                        = [];
      $this->empleado->nomina->deducciones                         = [];
      $this->empleado->nomina->otrosPagos                          = [];
      $this->empleado->nomina->percepcionesJubilacionPensionRetiro = [];
      $this->empleado->nomina->percepcionesSeparacionIndemnizacion = [];
    }
    $this->empleado->nomina->percepcionesTotales                 = ['TotalGravado' => 0, 'TotalExento' => 0];
    $this->empleado->nomina->deduccionesTotales                  = ['TotalOtrasDeducciones' => 0, 'TotalImpuestosRetenidos' => 0];
  }

  public function emisor()
  {
    $this->empleado->nomina->emisor = [];
    if (strlen($this->empresa->rfc) > 12)
      $this->empleado->nomina->emisor['Curp'] = $this->empresa->curp;
    if (isset($this->empresa->registro_patronal) && intval($this->empleado->tipo_contrato) < 9)
    {
      $this->empleado->nomina->emisor['RegistroPatronal'] = $this->empresa->registro_patronal;
    }
    // if (true)
    // {
    //   $entidadSNCF = ['OrigenRecurso' => 'IP'];
    //   $this->empleado->nomina->emisor['EntidadSNCF'] = $entidadSNCF;
    // }
  }

  public function receptor()
  {
    $this->empleado->nomina->receptor['Curp'] = $this->empleado->curp;
    if (isset($this->empleado->no_seguro))
    {
      $this->empleado->nomina->receptor['NumSeguridadSocial'] = $this->empleado->no_seguro;
    }
    if (isset($this->empleado->fecha_entrada))
    {
      $this->empleado->nomina->receptor['FechaInicioRelLaboral'] = $this->empleado->fecha_entrada;

      // $fecha1 = new DateTime($this->empleado->nomina->receptor['FechaInicioRelLaboral']);
      // $fecha2 = new DateTime($this->empleado->nomina->FechaFinalPago);
      // $fecha1 = Carbon::createFromFormat('Y-m-d', $this->empleado->nomina->receptor['FechaInicioRelLaboral'], 'America/Mexico_City');
      // $fecha2 = Carbon::createFromFormat('Y-m-d', $this->empleado->nomina->FechaFinalPago, 'America/Mexico_City');
      // $finte = $fecha1->diff($fecha2);
      $finte = MyString::diff2Dates($this->empleado->nomina->receptor['FechaInicioRelLaboral'], $this->empleado->nomina->FechaFinalPago);
      if ($finte->semanas > 0) {
        $this->empleado->nomina->receptor['Antigüedad'] = 'P'.$finte->semanas.'W';
      } else {
        $this->empleado->nomina->receptor['Antigüedad'] = 'P'.($finte->anios>0?$finte->anios.'Y':'').($finte->meses>0?$finte->meses.'M':'').$finte->dias.'D';
      }
      // if ($finte->m == 0 && $finte->y > 0) {
      //   $this->empleado->nomina->receptor['Antigüedad'] = 'P'.(intval($finte->days/7)).'W';
      // } else
      //   $this->empleado->nomina->receptor['Antigüedad'] = 'P'.($finte->y>0?$finte->y.'Y':'').($finte->m>0?$finte->m.'M':'').$finte->d.'D';
    }
    $this->empleado->nomina->receptor['TipoContrato'] = $this->empleado->tipo_contrato;
    if (isset($this->empleado->tipo_jornada))
    {
      $this->empleado->nomina->receptor['TipoJornada'] = $this->empleado->tipo_jornada;
    }
    $this->empleado->nomina->receptor['TipoRegimen'] = $this->empleado->regimen_contratacion;
    $this->empleado->nomina->receptor['NumEmpleado'] = $this->empleado->id;
    // if (isset($this->empleado->departamento))
    // {
    //   $this->empleado->nomina->receptor['Departamento'] = $this->empleado->departamento;
    // }
    // if (isset($this->empleado->puesto))
    // {
    //   $this->empleado->nomina->receptor['Puesto'] = $this->empleado->puesto;
    // }
    if (isset($this->empleado->riesgo_puesto))
    {
      $this->empleado->nomina->receptor['RiesgoPuesto'] = $this->empleado->riesgo_puesto;
    }
    // 02:semanal, 99:otra
    $this->empleado->nomina->receptor['PeriodicidadPago'] = (isset($this->nominaFiltros['tipo_nomina']) && $this->nominaFiltros['tipo_nomina']['tipo'] != 'se'? '99': '02');
    // Si existe la inforcionmacion del banco la agrega.
    // if (isset($this->empleado->cuenta_banorte) && isset($this->empleado->banco))
    // {
    //   $this->empleado->nomina->receptor['Cuenta'] = $this->empleado->cuenta_banorte;
    //   $this->empleado->nomina->receptor['Banco'] = $this->empleado->banco;
    // }
    if (isset($this->empleado->nomina->salario_diario_integrado)) {
      $this->empleado->nomina->receptor['SalarioDiarioIntegrado'] = $this->empleado->nomina->salario_diario_integrado;
    }
    $this->empleado->nomina->receptor['ClaveEntFed'] = $this->empleado->estado;
  }

  public function confPercepciones()
  {
    if (isset($this->nominaFiltros['tipo_nomina'])) {
      if ($this->nominaFiltros['tipo_nomina']['tipo'] == 'se') {
        $this->empleado->nomina->percepciones['sueldo'] = $this->pSueldo();
        $this->empleado->nomina->percepciones['septimo_dia'] = $this->pSueldo('7d');
        // $this->empleado->nomina->percepciones['premio_puntualidad'] = $this->pPremioPuntualidad();
        $this->empleado->nomina->percepciones['premio_asistencia'] = $this->pPremioAsistencia();
        // $this->empleado->nomina->percepciones['despensa'] = $this->pDespensa();
        $this->empleado->nomina->percepciones['horas_extras'] = $this->pHorasExtras();
      }

      if ( ($this->nominaFiltros['tipo_nomina']['tipo'] == 'se' && $this->nominaFiltros['tipo_nomina']['con_aguinaldo'] == '1') ||
         ($this->nominaFiltros['tipo_nomina']['tipo'] == 'ag' && $this->nominaFiltros['tipo_nomina']['con_aguinaldo'] == '1')) {
        $this->empleado->nomina->percepciones['aguinaldo'] = $this->pAguinaldo();
      }

      if ( ($this->nominaFiltros['tipo_nomina']['tipo'] == 'se' && $this->nominaFiltros['tipo_nomina']['con_vacaciones'] == '1') ) {
        $this->empleado->nomina->percepciones['vacaciones'] = $this->pVacaciones();
        $this->empleado->nomina->percepciones['prima_vacacional'] = $this->pPrimaVacacional();
      } elseif ( ($this->nominaFiltros['tipo_nomina']['tipo'] == 'se' && $this->empleado->nomina_guardada == 't' &&
          $this->empleado->nomina_fiscal_vacaciones > 0) ) {
        $this->empleado->nomina->percepciones['vacaciones'] = $this->pVacaciones();
        $this->empleado->nomina->percepciones['prima_vacacional'] = $this->pPrimaVacacional();
      }

      if ( ($this->nominaFiltros['tipo_nomina']['tipo'] == 'ptu') )
        $this->empleado->nomina->percepciones['ptu'] = $this->pPtu();
    } else {
      $this->empleado->nomina->percepciones['sueldo'] = $this->pSueldo();
      $this->empleado->nomina->percepciones['septimo_dia'] = $this->pSueldo('7d');
      // $this->empleado->nomina->percepciones['premio_puntualidad'] = $this->pPremioPuntualidad();
      $this->empleado->nomina->percepciones['premio_asistencia'] = $this->pPremioAsistencia();
      // $this->empleado->nomina->percepciones['despensa'] = $this->pDespensa();
      $this->empleado->nomina->percepciones['horas_extras'] = $this->pHorasExtras();
      $this->empleado->nomina->percepciones['aguinaldo'] = $this->pAguinaldo();
      $this->empleado->nomina->percepciones['vacaciones'] = $this->pVacaciones();
      $this->empleado->nomina->percepciones['prima_vacacional'] = $this->pPrimaVacacional();
      $this->empleado->nomina->percepciones['ptu'] = $this->pPtu();
    }
  }

  public function confDeducciones()
  {
    if (isset($this->nominaFiltros['tipo_nomina'])) {
      if ($this->nominaFiltros['tipo_nomina']['tipo'] == 'se') {
        $this->empleado->nomina->deducciones['imss'] = $this->dImss();
        $this->empleado->nomina->deducciones['rcv'] = $this->dRcv();
        $this->empleado->nomina->deducciones['infonavit'] = $this->dInfonavit();
      }

      if ( ($this->nominaFiltros['tipo_nomina']['tipo'] != 'ptu' && $this->nominaFiltros['tipo_nomina']['tipo'] != 'ag') )
        $this->empleado->nomina->deducciones['otros'] = $this->dOtros();

      $this->empleado->nomina->deducciones['isr'] = $this->dIsr();
    } else {
      $this->empleado->nomina->deducciones['imss'] = $this->dImss();
      $this->empleado->nomina->deducciones['rcv'] = $this->dRcv();
      $this->empleado->nomina->deducciones['infonavit'] = $this->dInfonavit();
      $this->empleado->nomina->deducciones['otros'] = $this->dOtros();
      $this->empleado->nomina->deducciones['isr'] = $this->dIsr();
    }
  }

  /*
   |------------------------------------------------------------------------
   | Calculos Base
   |------------------------------------------------------------------------
   */

  /**
   * Calcula el aguinaldo en base a los dias que este dando la empresa de
   * aguinaldo y el salario diario del empleado.
   *
   * @return float
   */
  public function aguinaldo()
  {
    if ($this->empleado->dias_aguinaldo_full > 365) {
      $this->empleado->dias_aguinaldo_full = 365;
    }
    // $diasTrabajadosAnio = 365 - $this->empleado->dias_faltados_anio;
    $this->empleado->dias_aguinaldo = round(($this->empleado->dias_aguinaldo_full * $this->empresaConfig->aguinaldo) / 365, 2);

    return $this->empleado->dias_aguinaldo * $this->empleado->salario_diario;
  }

  /**
   * Calcula el total a pagarle de las vacaciones al empleado.
   *
   * @return mixed
   */
  public function vacaciones()
  {
    return round($this->empleado->dias_vacaciones * $this->empleado->salario_diario, 2);
  }

  /**
   * Calcula la prima vacacional en base al porcentaje que este dando
   * la empresa y el salario diario del empleado.
   *
   * @return float
   */
  public function primaVacacional()
  {
    return round($this->empleado->dias_prima_vacacional * $this->empleado->salario_diario, 2);
  }

  /**
   * Calcula cuantos dias de prima vacacional se pagaran.
   *
   * @return float
   */
  public function diasPrimaVacacional($dias = null)
  {
    $dias = $dias? $dias: $this->empleado->dias_vacaciones;
    return (intval($this->empresaConfig->prima_vacacional) / 100) * $dias;
  }

  /**
   * Calcula el factor de integracion.
   *
   * @return float
   */
  public function factorIntegracion()
  {
    // dias completos de vacaciones para el calculo de sdi
    $dias_prima_full = $this->diasPrimaVacacional($this->diasDeVacaciones());
    return round((365 + $this->empresaConfig->aguinaldo + $dias_prima_full) / 365, 4);
  }

  /**
   * Calcula el SDI = SALARIO DIARIO INTEGRADO
   *
   * @return float
   */
  public function sdi()
  {
    return round($this->empleado->factor_integracion * $this->empleado->salario_diario, 2);
  }


  /*
   |------------------------------------------------------------------------
   | Percepciones
   |------------------------------------------------------------------------
   */

  /**
   * * Percepcion Sueldo - 001
   *
   * @param  string $tipo n: normal, sueldo semanal | 7d: séptimo dia de trabajo
   * @return array
   */
  public function pSueldo($tipo = 'n')
  {
    if ($tipo === 'n') {
      $dias = $this->empleado->dias_trabajados; //==7? 6: intval($this->empleado->dias_trabajados);

      $this->empleado->nomina->sueldo = $this->empleado->salario_diario * $dias;

      return array(
        'TipoPercepcion' => '001',
        'Clave'          => $this->clavesPatron[($this->empleado->id_departamente==1? 'sueldo1': 'sueldo2')],
        'Concepto'       => 'Sueldos, Salarios Rayas y Jornales',
        'ImporteGravado' => round($this->empleado->nomina->sueldo, 2),
        'ImporteExcento' => 0,
        'total'          => round($this->empleado->nomina->sueldo + 0, 2),
        'ApiKey'         => 'pe_sueldo_',
      );
    } elseif ($tipo === '7d') {
      $dias = $this->empleado->dias_trabajados==7? 1:  ($this->empleado->dias_trabajados - floor($this->empleado->dias_trabajados));

      $septimo = $this->empleado->salario_diario * $dias;
      $this->empleado->nomina->sueldo += round($septimo, 2);

      $septimo = $this->empleado->ttipo_nnomina == 'quincena'? 0: $septimo;

      return array(
        'TipoPercepcion' => '001',
        'Clave'          => $this->clavesPatron[($this->empleado->id_departamente==1? 'sueldo1': 'sueldo2')],
        'Concepto'       => 'Sueldos, Séptimo día',
        'ImporteGravado' => round($septimo, 2),
        'ImporteExcento' => 0,
        'total'          => round($septimo + 0, 2),
        'ApiKey'         => 'pe_sueldo_7d_',
      );
    }
  }

  /**
   * Percepcion Premio Puntualidad - 010
   *
   * @return array
   */
  public function pPremioPuntualidad()
  {
    $dias = $this->empleado->dias_trabajados==7? 1: ($this->empleado->dias_trabajados - floor($this->empleado->dias_trabajados));
    $septimo = $this->empleado->salario_diario * $dias;
    $sueldo = $this->empleado->nomina->sueldo - $septimo;
    $premioPuntualidad = $sueldo * ($this->empresaConfig->puntualidad / 100);
    $premioPuntualidad = $this->empleado->ttipo_nnomina == 'quincena'? 0: $premioPuntualidad;

    return array(
      'TipoPercepcion' => '010',
      'Clave'          => $this->clavesPatron['premio_puntualidad'],
      'Concepto'       => 'Premios por puntualidad',
      'ImporteGravado' => round($premioPuntualidad, 2),
      'ImporteExcento' => 0,
      'total'          => round($premioPuntualidad + 0, 2),
      'ApiKey'         => 'pe_premio_puntualidad_',
    );
  }

  /**
   * Percepcion Premio Asistencia - 010
   *
   * @return array
   */
  public function pPremioAsistencia()
  {
    $dias = $this->empleado->dias_trabajados==7? 1: ($this->empleado->dias_trabajados - floor($this->empleado->dias_trabajados));
    $septimo = $this->empleado->salario_diario * $dias;
    $sueldo = $this->empleado->nomina->sueldo - $septimo;
    $premioAsistencia = $sueldo * ($this->empresaConfig->asistencia / 100);
    $premioAsistencia = $this->empleado->ttipo_nnomina == 'quincena'? 0: $premioAsistencia;

    return array(
      'TipoPercepcion' => '049',
      'Clave'          => $this->clavesPatron[($this->empleado->id_departamente==1? 'premio_asistencia1': 'premio_asistencia2')],
      'Concepto'       => 'Premios por asistencia',
      'ImporteGravado' => round($premioAsistencia, 2),
      'ImporteExcento' => 0,
      'total'          => round($premioAsistencia + 0, 2),
      'ApiKey'         => 'pe_p_asistencia_',
    );
  }

  /**
   * Percepcion Despensa - 008
   *
   * @return array
   */
  public function pDespensa()
  {
    $despensa = round(($this->empleado->dias_trabajados * ($this->empresaConfig->despensa / 100)) * $this->salariosZonasConfig->zona_a, 2);

    return array(
      'TipoPercepcion' => '029',
      'Clave'          => $this->clavesPatron['despensa'],
      'Concepto'       => 'Vales de despensa',
      'ImporteGravado' => 0,
      'ImporteExcento' => round($despensa, 2),
      'total'          => round($despensa + 0, 2),
      'ApiKey'         => 'pe_vales_despensa_',
    );
  }

  /**
   * Percepcion Horas Extras - 019
   *
   * @return array
   */
  public function pHorasExtras()
  {
    $horasExtras = 0;
    $gravado = 0;
    $excento = 0;
    $sueldoPorHora = $this->empleado->salario_diario / 8;

    if (floatval($this->empleado->horas_extras_dinero) !== 0)
    {
      // $horasExtras = floatval($this->empleado->horas_extras_dinero) / $sueldoPorHora;
      $topeExcento = 5 * $this->salariosZonasConfig->zona_a;
      $gravado = $excento = $this->empleado->horas_extras_dinero / 2;

      if ($excento > $topeExcento)
      {
        $gravado = floatval($this->empleado->horas_extras_dinero) - $topeExcento;
        $excento = $topeExcento;
      }
    }

    return array(
      'TipoPercepcion' => '019',
      'Clave'          => $this->clavesPatron[($this->empleado->id_departamente==1? 'horas_extras1': 'horas_extras2')],
      'Concepto'       => 'Horas extra',
      'ImporteGravado' => round($gravado, 2),
      'ImporteExcento' => round($excento, 2),
      'total'          => round($gravado, 2) + round($excento, 2),
      'ApiKey'         => 'pe_horas_extras_',
    );
  }

  /**
   * Percepcion Aguinaldo - 002
   *
   * @return array
   */
  public function pAguinaldo()
  {
    $topeExcento = 30 * floatval($this->salariosZonasConfig->zona_a);

    // Si los que se le dara de aguinaldo al empleado excede el tope excento.
    if ($this->empleado->nomina->aguinaldo > $topeExcento)
    {
      $gravado = $this->empleado->nomina->aguinaldo - $topeExcento;
      $excento = $topeExcento;
    }
    else
    {
      $gravado = 0;
      $excento = $this->empleado->nomina->aguinaldo;
    }

    return array(
      'TipoPercepcion' => '002',
      'Clave'          => $this->clavesPatron[($this->empleado->id_departamente==1? 'aguinaldo1': 'aguinaldo2')],
      'Concepto'       => 'Gratificación Anual Aguinaldo',
      'ImporteGravado' => round($gravado, 2),
      'ImporteExcento' => round($excento, 2),
      'total'          => round($gravado, 2) + round($excento, 2),
      'ApiKey'         => 'pe_aguinaldo_',
    );
  }

  /**
   * Percepcion Vacaciones
   *
   * @return array
   */
  public function pVacaciones()
  {
    $vacaciones = 0;
    if ($this->empleado->nomina_guardada == 'f') {
      $vacaciones = floatval($this->empleado->nomina->vacaciones);
    } elseif (isset($this->empleado->nomina_fiscal_vacaciones)) {
      $vacaciones = floatval($this->empleado->nomina_fiscal_vacaciones);
    }

    return array(
      'TipoPercepcion' => '038',
      'Clave'          => $this->clavesPatron[($this->empleado->id_departamente==1? 'vacaciones1': 'vacaciones2')],
      'Concepto'       => 'Vacaciones',
      'ImporteGravado' => round($vacaciones, 2),
      'ImporteExcento' => 0,
      'total'          => round($vacaciones, 2) + 0,
      'ApiKey'         => 'pe_sueldo_',
    );
  }

  /**
   * Percepcion Prima Vacacional - 021
   *
   * @return array
   */
  public function pPrimaVacacional()
  {
    if ($this->empleado->nomina_guardada == 'f') {
      $topeExcento = 15 * floatval($this->salariosZonasConfig->zona_a);

      // Si los que se le dara de aguinaldo al empleado excede el tope excento.
      if ($this->empleado->nomina->prima_vacacional > $topeExcento)
      {
        $gravado = $this->empleado->nomina->prima_vacacional - $topeExcento;
        $excento = $topeExcento;
      }
      else
      {
        $gravado = 0;
        $excento = $this->empleado->nomina->prima_vacacional;
      }
    } else {
      $gravado = $this->empleado->nomina_fiscal_prima_vacacional_grabable;
      $excento = $this->empleado->nomina_fiscal_prima_vacacional_exento;
    }

    return array(
      'TipoPercepcion' => '021',
      'Clave'          => $this->clavesPatron[($this->empleado->id_departamente==1? 'prima_vacacional1': 'prima_vacacional2')],
      'Concepto'       => 'Prima vacacional',
      'ImporteGravado' => round($gravado, 2),
      'ImporteExcento' => round($excento, 2),
      'total'          => round($gravado, 2) + round($excento, 2),
      'ApiKey'         => 'pe_prima_vacacional_',
    );
  }

  /**
   * Percepcion PTU - 003.
   *
   * @return array
   */
  public function pPtu()
  {
    $gravado = 0;
    $excento = 0;

    // echo "<pre>";
    //   var_dump($this->empleado->utilidad_empresa_ptu);
    // echo "</pre>";exit;

    if ($this->empleado->utilidad_empresa_ptu > 0 && $this->empleado->ptu_percepciones_empleados > 0 && $this->empleado->ptu_dias_trabajados_empleados > 0)
    {
      if ($this->empleado->ptu_generado === 'false') {
        $ptu = $this->empleado->utilidad_empresa_ptu / 2;

        $percepciones = round((floatval($this->empleado->ptu_percepciones_empleado) * $ptu) / floatval($this->empleado->ptu_percepciones_empleados), 3);
        $dias = round((floatval($this->empleado->ptu_dias_trabajados_empleado) * $ptu) / floatval($this->empleado->ptu_dias_trabajados_empleados), 3);

        $this->empleado->ptu_empleado_percepciones = $percepciones;
        $this->empleado->ptu_empleado_dias         = $dias;
        $this->empleado->ptu_empleado_percepciones_fact = round($ptu/floatval($this->empleado->ptu_percepciones_empleados), 4);
        $this->empleado->ptu_empleado_dias_fact         = round($ptu/floatval($this->empleado->ptu_dias_trabajados_empleados), 4);

        $ptuTrabajador = $percepciones + $dias;
      } else {
        $ptuTrabajador = $this->empleado->nomina_fiscal_ptu;
      }

      $topeExcento = 15 * $this->salariosZonasConfig->zona_a;

      if ($ptuTrabajador > $topeExcento)
      {
        $gravado = $ptuTrabajador - $topeExcento;
        $excento = $topeExcento;
      }
      else
      {
        $gravado = 0;
        $excento = $ptuTrabajador;
      }
    }

    return array(
      'TipoPercepcion' => '003',
      'Clave'          => $this->clavesPatron['ptu'],
      'Concepto'       => 'PTU',
      'ImporteGravado' => round($gravado, 2),
      'ImporteExcento' => round($excento, 2),
      'total'          => round($gravado, 2) + round($excento, 2),
      'ApiKey'         => 'pe_ptu_',
    );
  }

  /*
   |------------------------------------------------------------------------
   | Deducciones
   |------------------------------------------------------------------------
   */

  /**
   * Deduccion IMSS - 001
   *
   * @return array
   */
  public function dImss()
  {
    $topeExcento = 25 * floatval($this->salariosZonasConfig->zona_a);

    // Si los que se le dara de aguinaldo al empleado excede el tope excento.
    if ($this->empleado->nomina->salario_diario_integrado > $topeExcento)
    {
      $excento = $topeExcento;
    }
    else
    {
      $excento = $this->empleado->nomina->salario_diario_integrado;
    }

    $baseImss = $excento * $this->empleado->dias_trabajados;
    $retencionImss = round(0.0125 * $baseImss, 4);

    $cuotaAdicionalImss = 0;
    $topeAdicionalImss = 3 * $this->salariosZonasConfig->zona_a;
    if ($this->empleado->nomina->salario_diario_integrado > $topeAdicionalImss)
    {
      $cuotaAdicionalImss = (0.012 * ($this->empleado->nomina->salario_diario_integrado - $topeAdicionalImss)) * $this->empleado->dias_trabajados;
    }
    $totalImss = round($cuotaAdicionalImss + $retencionImss, 2);

    if($this->empleado->nomina_guardada != 'f') {
      $totalImss = $this->empleado->imss;
    }

    return array(
      'TipoDeduccion' => '001',
      'Clave'          => $this->clavesPatron['imss'],
      'Concepto'       => 'Seguridad social',
      'ImporteGravado' => 0,
      'ImporteExcento' => round($totalImss, 2),
      'total'          => round($totalImss, 2) + 0,
      'ApiKey'         => 'de_seguro_social_',
    );
  }

  /**
   * Deduccion RCV - 003
   *
   * @return array
   */
  public function dRcv()
  {
    $topeExcento = 18 * floatval($this->salariosZonasConfig->zona_a);

    // Si los que se le dara de aguinaldo al empleado excede el tope excento.
    if ($this->empleado->nomina->salario_diario_integrado > $topeExcento)
    {
      $baseRcv = $topeExcento;
    }
    else
    {
      $baseRcv = $this->empleado->nomina->salario_diario_integrado;
    }
    $rcv = round(0.01125 * (floatval($baseRcv) * floatval($this->empleado->dias_trabajados)), 2);

    if($this->empleado->nomina_guardada != 'f') {
      $totalImss = $this->empleado->vejez;
    }

    return array(
      'TipoDeduccion' => '003',
      'Clave'          => $this->clavesPatron['rcv'],
      'Concepto'       => 'Aportaciones a retiro, cesantía en edad avanzada y vejez',
      'ImporteGravado' => 0,
      'ImporteExcento' => round($rcv, 2),
      'total'          => round($rcv, 2) + 0,
      'ApiKey'         => 'de_cesantia_vejez_',
    );
  }

  /**
   * Deduccion Infonavit - 010
   *
   * @return array
   */
  public function dInfonavit()
  {
    // $infonavit = round(($this->empleado->infonavit / 30.4) * ($this->salariosZonasConfig->zona_a * $this->empleado->dias_trabajados), 2);
    if($this->empleado->nomina_guardada == 'f')
      $infonavit = round(($this->empleado->infonavit * $this->empleado->dias_trabajados) / 7, 2);
    else
      $infonavit = $this->empleado->infonavit;

    return array(
      'TipoDeduccion' => '010',
      'Clave'          => $this->clavesPatron['infonavit'],
      'Concepto'       => 'Pago por crédito de vivienda',
      'ImporteGravado' => 0,
      'ImporteExcento' => round($infonavit, 2),
      'total'          => round($infonavit, 2) + 0,
      'ApiKey'         => 'de_credito_vivienda_',
    );
  }

  /**
   * Deduccion ISR - 002
   *
   * @return array
   */
  public function dIsr()
  {
    if ($this->subsidio !== null && $this->isr !== null)
    {
      $this->empleado->nomina->otrosPagos['subsidio'] = array(
        'TipoOtroPago'     => '002',
        'Clave'            => $this->clavesPatron['subsidio'],
        'Concepto'         => 'Subsidio para el empleo',
        'ImporteGravado'   => 0,
        'ImporteExcento'   => round($this->subsidio, 2),
        'total'            => round($this->subsidio, 2) + 0,
        'SubsidioAlEmpleo' => array('SubsidioCausado' => (round($this->subsidioCausado, 2) + 0) ),
        'ApiKey'           => 'top_subsidio_empleo_',
      );

      return array(
        'TipoDeduccion' => '002',
        'Clave'          => $this->clavesPatron['isr'],
        'Concepto'       => 'ISR',
        'ImporteGravado' => 0,
        'ImporteExcento' => round($this->isr, 2),
        'total'          => round($this->isr, 2) + 0,
        'ApiKey'         => 'de_isr_',
      );
    }
    else
    {
      // Almacena el total de la suma del importe gravado de las percepciones.
      $sumaImporteGravados = 0;

      // Almacena el total de la suma del importe gravado diarios que incluye
      // el aguinaldo, prima vacacional y ptu
      $sumaImporteGravadosDiariosConOtros = 0;

      // Almacena el total de la suma del importe gravado diarios que no incluye
      // el aguinaldo, prima vacacional y ptu
      $sumaImporteGravadosDiariosSinOtros = 0;

      // Agrega a la suma de de los importes gravados el sueldo.
      if (isset($this->empleado->nomina->percepciones['sueldo']))
        $sumaImporteGravados += floatval($this->empleado->nomina->percepciones['sueldo']['ImporteGravado']);

      // Si el importe gravado del premio de asistencia es mayor a 0.
      $premioAsistenciaGravadoDiario = 0;
      if (isset($this->empleado->nomina->percepciones['premio_asistencia']) && $this->empleado->nomina->percepciones['premio_asistencia']['ImporteGravado'] > 0)
      {
        $sumaImporteGravados += floatval($this->empleado->nomina->percepciones['premio_asistencia']['ImporteGravado']);

        $premioAsistenciaGravadoDiario = round(floatval($this->empleado->nomina->percepciones['premio_asistencia']['ImporteGravado']) / 365, 4);
      }

      // Si se tomara en cuenta la percepcion horas extras la suma.
      $horasExtrasGravadoDiario = 0;
      if (isset($this->empleado->nomina->percepciones['horas_extras']) && isset($_POST['horas_extras']) && $_POST['horas_extras'] != 0)
      {
        $sumaImporteGravados += floatval($this->empleado->nomina->percepciones['horas_extras']['ImporteGravado']);

        $horasExtrasGravadoDiario = round(floatval($this->empleado->nomina->percepciones['horas_extras']['ImporteGravado']) / 365, 4);
      }

      // Si se tomara en cuenta la percepcion vacaciones y prima vacacional.
      $primaVacacionalGravadoDiario = 0;
      if (isset($_POST['con_vacaciones']) && $_POST['con_vacaciones'] === '1')
      {
        $primaVacacionalGravadoDiario = round(floatval($this->empleado->nomina->percepciones['prima_vacacional']['ImporteGravado']) / 365, 4);
      }

      // Si se tomara en cuenta la percepcion aguinaldo.
      $aguinaldoGravadoDiario = 0;
      if ( ($this->nominaFiltros['tipo_nomina']['tipo'] == 'se' && $this->nominaFiltros['tipo_nomina']['con_aguinaldo'] == '1') ||
         ($this->nominaFiltros['tipo_nomina']['tipo'] == 'ag' && $this->nominaFiltros['tipo_nomina']['con_aguinaldo'] == '1')) {
        $aguinaldoGravadoDiario = round(floatval($this->empleado->nomina->percepciones['aguinaldo']['ImporteGravado']) / 365, 4);
      }

      // Si el importe gravado del ptu es mayor a 0.
      $ptuGravadoDiario = 0;
      if (isset($this->empleado->nomina->percepciones['ptu']) && $this->empleado->nomina->percepciones['ptu']['ImporteGravado'] > 0)
      {
        $ptuGravadoDiario = round(floatval($this->empleado->nomina->percepciones['ptu']['ImporteGravado']) / 365, 4);
      }

      // Recorre los rangos de la tabla semanal de ISR para determinar en que
      // limites se encuentra la suma de los importes gravados.
      $isrAntesSubsidio = 0;
      foreach ($this->tablasIsr['semanal']['art113'] as $rango)
      {
        if ($sumaImporteGravados >= floatval($rango->lim_inferior) && $sumaImporteGravados <= floatval($rango->lim_superior))
        {
          $isrAntesSubsidio = round((($sumaImporteGravados - floatval($rango->lim_inferior)) * (floatval($rango->porcentaje) / 100.00)) + floatval($rango->cuota_fija), 4);
          break;
        }
      }


      // Suma todos los gravados diarios con aguinaldo, prima vacacional y ptu.
      // base_semana_ord_gravada = sueldo+hrse+premAsistencia
      $sumaImporteGravadosDiariosConOtros = $this->empleado->base_semana_ord_gravada + $aguinaldoGravadoDiario + $ptuGravadoDiario + $primaVacacionalGravadoDiario;

      // Suma todos los gravados diarios sin aguinaldo, prima vacacional y ptu.
      $sumaImporteGravadosDiariosSinOtros = $this->empleado->base_semana_ord_gravada;

      // Recorre los rangos de la tabla diaria de ISR para determinar en que
      // limites se encuentra la suma de los importes gravados diarios con
      // aguinaldo, prima vacacional, ptu y tambien para obtener el rango
      // de la suma de los importes gravados diarios sin aguinaldo, prima vacacional
      // y ptu.
      $isrAuxConOtros = 0;
      $isrAuxSinOtros = 0;
      // if (isset($this->nominaFiltros['tipo_nomina']['tipo']) && $this->nominaFiltros['tipo_nomina']['tipo'] == 'ptu') {
        foreach ($this->tablasIsr['diaria']['art113'] as $rango)
        {
          if ($sumaImporteGravadosDiariosConOtros >= floatval($rango->lim_inferior) && $sumaImporteGravadosDiariosConOtros <= floatval($rango->lim_superior))
          {
            $isrAuxConOtros = round((($sumaImporteGravadosDiariosConOtros - floatval($rango->lim_inferior)) * (floatval($rango->porcentaje) / 100.00)) + floatval($rango->cuota_fija), 2);
          }

          if ($sumaImporteGravadosDiariosSinOtros >= floatval($rango->lim_inferior) && $sumaImporteGravadosDiariosSinOtros <= floatval($rango->lim_superior))
          {
            $isrAuxSinOtros = round((($sumaImporteGravadosDiariosSinOtros - floatval($rango->lim_inferior)) * (floatval($rango->porcentaje) / 100.00)) + floatval($rango->cuota_fija), 2);
          }
        }
      // }
      $isrAguinaldoPrimaPtu = round(($isrAuxConOtros - $isrAuxSinOtros) * 365, 2);


      $isrAntesSubsidio += $isrAguinaldoPrimaPtu;
      // Obtiene el subsidio de acuerdo a a las persepciones gravadas
      $subsidioIsr = $this->getSubsidioIsr($sumaImporteGravados, $isrAntesSubsidio, $this->nominaFiltros['tipo_nomina']['tipo']);
      // if ($this->empleado->id == 1481) {
      //   echo "<pre>";
      //     var_dump ($isrAuxConOtros, $isrAuxSinOtros);
      //     var_dump ($this->empleado->base_semana_ord_gravada, $ptuGravadoDiario, $this->empleado->nomina->percepciones['ptu']);
      //     var_dump($isrAntesSubsidio, $isrAguinaldoPrimaPtu, $sumaImporteGravados, $subsidioIsr);
      //   echo "</pre>";exit;
      // }
      $isrTotal              = $subsidioIsr['isr']; // isr semana
      $this->subsidio        = $subsidioIsr['subsidio'];
      $this->subsidioCausado = $subsidioIsr['subsidioCausado'];
      // var_dump ($this->subsidio, $isrTotal);
      // die();
      // Agrega la percepcion subsidio a la nomina.
      $this->empleado->nomina->otrosPagos['subsidio'] = array(
        'TipoOtroPago'     => '002',
        'Clave'            => $this->clavesPatron['subsidio'],
        'Concepto'         => 'Subsidio para el empleo',
        'ImporteGravado'   => 0,
        'ImporteExcento'   => round($this->subsidio, 2),
        'total'            => round($this->subsidio, 2) + 0,
        'SubsidioAlEmpleo' => array('SubsidioCausado' => (round($this->subsidioCausado, 2) + 0) ),
        'ApiKey'           => 'top_subsidio_empleo_',
      );

      return array(
        'TipoDeduccion'  => '002',
        'Clave'          => $this->clavesPatron['isr'],
        'Concepto'       => 'ISR',
        'ImporteGravado' => 0,
        'ImporteExcento' => round($isrTotal, 2),
        'total'          => round($isrTotal, 2) + 0,
        'ApiKey'         => 'de_isr_',
      );
    }
  }

  /**
   * Deduccion otros - 004
   *
   * @return array
   */
  public function dOtros()
  {
    $otros = 0; //floatval($this->empleado->descuento_playeras);

    if ($this->empleado->esta_generada != 'false' || floatval($this->empleado->nomina_fiscal_prestamos) > 0) {
      $otros += floatval($this->empleado->nomina_fiscal_prestamos);
    } else {
      foreach ($this->empleado->prestamos as $prestamo)
      {
        $otros += floatval($prestamo['pago_semana_descontar']);
      }
    }

    // Fondo de ahorro
    if($this->empleado->nomina_guardada == 'f')
      $otros += $this->empleado->fondo_ahorro; //round(($this->empleado->fondo_ahorro * $this->empleado->dias_trabajados) / 7, 2);
    else
      $otros += $this->empleado->fondo_ahorro;

    return array(
      'TipoDeduccion' => '004',
      'Clave'          => $this->clavesPatron['otros'],
      'Concepto'       => 'Otros',
      'ImporteGravado' => 0,
      'ImporteExcento' => round($otros, 2),
      'total'          => round($otros, 2) + 0,
      'ApiKey'         => 'de_otros_',
    );
  }

  /**
   * Deduccion otros - 007
   *
   * @return array
   */
  public function dPencionAlimenticia()
  {
    $otros = 0; //floatval($this->empleado->descuento_playeras);

    if ($this->empleado->nomina_guardada == 'f') {
      $otros = floatval(($this->empleado->p_alimenticia / 100) * $this->empleado->nomina->TotalPercepciones);
    } elseif (isset($this->empleado->otros_datos->dePensionAlimenticia)) {
      $otros = floatval($this->empleado->otros_datos->dePensionAlimenticia);
    }

    return array(
      'TipoDeduccion' => '007',
      'Clave'          => $this->clavesPatron['otros'],
      'Concepto'       => 'Pensión alimenticia',
      'ImporteGravado' => 0,
      'ImporteExcento' => round($otros, 2),
      'total'          => round($otros, 2) + 0,
      'ApiKey'         => 'de_pencion_alimenticia_',
    );
  }

  /**
   * Deduccion infonacot - 011
   *
   * @return array
   */
  public function dInfonacot()
  {
    $otros = 0; //floatval($this->empleado->descuento_playeras);

    if ($this->empleado->nomina_guardada == 'f') {
      $otros = floatval($this->empleado->fonacot);
    } elseif (isset($this->empleado->otros_datos->deInfonacot)) {
      $otros = floatval($this->empleado->otros_datos->deInfonacot);
    }

    return array(
      'TipoDeduccion' => '011',
      'Clave'          => $this->clavesPatron['otros'],
      'Concepto'       => 'Pago de abonos INFONACOT',
      'ImporteGravado' => 0,
      'ImporteExcento' => round($otros, 2),
      'total'          => round($otros, 2) + 0,
      'ApiKey'         => 'de_infonacot_',
    );
  }


  /*
   |------------------------------------------------------------------------
   | Deducciones
   |------------------------------------------------------------------------
   */
  public function setCalculoAnual($set=false)
  {
    if ($set) {
      // $calculo_anual = json_decode($this->empleado->otros_datos);
      $calculo_anual = $this->empleado->otros_datos->calculoAnual; // $calculo_anual->calculoAnual;
      if ($calculo_anual->tipo === 't') { // isr a pagar
        $this->empleado->nomina->deducciones['isrAnual'] = array(
          'TipoDeduccion'  => '101',
          'Clave'          => $this->clavesPatron['isr'],
          'Concepto'       => 'ISR Retenido de ejercicio anterior',
          'ImporteGravado' => 0,
          'ImporteExcento' => round($calculo_anual->desc_abon, 2),
          'total'          => round($calculo_anual->desc_abon, 2) + 0,
          'ApiKey'         => 'de_isr_retenido_ejercicio_anterior_',
        );
      } else { // subsidio a pagar
        $this->empleado->nomina->otrosPagos['subsidioAnual'] = array(
          'TipoOtroPago'     => '004',
          'Clave'            => $this->clavesPatron['subsidio'],
          'Concepto'         => 'Saldo favor compensación anual',
          'ImporteGravado'   => 0,
          'ImporteExcento'   => round($calculo_anual->desc_abon, 2),
          'total'            => round($calculo_anual->desc_abon, 2) + 0,
          'ApiKey'           => 'top_saldo_favor_compensacion_',
        );
      }
    } else {
      if ($this->empleado->calculo_anual->tipo === 't') { // isr a pagar
        $importe_pagar = $this->empleado->nomina->TotalPercepciones - $this->empleado->nomina->TotalDeducciones + $this->empleado->nomina->TotalOtrosPagos;
        $descuento_isr = $this->empleado->calculo_anual->saldo;
        if ($importe_pagar < $this->empleado->calculo_anual->saldo) {
          $descuento_isr = $importe_pagar;
        }
        $this->empleado->calculo_anual->desc_abon = $descuento_isr;

        $this->empleado->nomina->deducciones['isrAnual'] = array(
          'TipoDeduccion'  => '101',
          'Clave'          => $this->clavesPatron['isr'],
          'Concepto'       => 'ISR Retenido de ejercicio anterior',
          'ImporteGravado' => 0,
          'ImporteExcento' => round($descuento_isr, 2),
          'total'          => round($descuento_isr, 2) + 0,
          'ApiKey'         => 'de_isr_retenido_ejercicio_anterior_',
        );
        // echo "<pre>";
        //   var_dump($this->empleado);
        // echo "</pre>";exit;
      } else { // subsidio a pagar
        $subsidio = $this->empleado->calculo_anual->saldo;
        if ($this->empleado->calculo_anual->saldo > $this->empleado->nomina->isr) {
          $subsidio = $this->empleado->nomina->isr;
        }
        $this->empleado->calculo_anual->desc_abon = $subsidio;
        $remanenteSalFav = $this->empleado->calculo_anual->saldo - $subsidio;

        $this->empleado->nomina->otrosPagos['subsidioAnual'] = array(
          'TipoOtroPago'    => '004',
          'Clave'           => $this->clavesPatron['subsidio'],
          'Concepto'        => 'Saldo favor compensación anual',
          'ImporteGravado'  => 0,
          'ImporteExcento'  => round($subsidio, 2),
          'total'           => round($subsidio, 2) + 0,
          'saldoAFavor'     => round($this->empleado->calculo_anual->saldo, 2) + 0,
          'año'             => round($this->empleado->calculo_anual->anio, 2) + 0,
          'remanenteSalFav' => round($remanenteSalFav, 2) + 0,
          'ApiKey'          => 'top_saldo_favor_compensacion_',
        );
      }
    }
  }


  /*
   |------------------------------------------------------------------------
   | Helpers
   |------------------------------------------------------------------------
   */

  /**
   * Obtiene los años que el empleado lleva trabajando en la empresa.
   *
   * @return int | años trabajados
   */
  private function aniosTrabajadosEmpleado($full=false)
  {
    $fechaActual = new DateTime( (!isset($this->empleado->fecha_salida{0})? date('Y-m-d'): $this->empleado->fecha_salida) );
    $fechaInicioTrabajar = new DateTime($this->empleado->fecha_entrada);
    $finte = $fechaInicioTrabajar->diff($fechaActual);
    return ($full? $finte: intval($finte->y));
  }

  /**
   * Obtiene los dias de vacaciones que se le daran al empleado segun los años
   * que lleve trabajados.
   *
   * @return int
   */
  private function diasDeVacaciones()
  {
    // Dias de vacaciones son cero por si no tiene almenos 1 año de antigüedad.
    $diasVacaciones = $this->diasVacacionesCorresponden($this->empleado->anios_trabajados);

    $diasVacaciones = round(($this->diasAnioVacaciones() / 365) * $diasVacaciones, 4);

    return $diasVacaciones;
  }

  /**
   * Obtienen los dias que le tocan de acuerdo a la configuracion de vacaciones y años trabajados
   * @param  [type] $anios_trabajados [description]
   * @return [type]                   [description]
   */
  public function diasVacacionesCorresponden($anios_trabajados)
  {
    $diasVacaciones = 0;
    // Si tiene 1 año o mas.
    if (intval($anios_trabajados) > 0)
    {
      // Recorre las configuraciones para obtener los dias de vacaciones a dar
      // segun los años trabajados.
      foreach ($this->vacacionesConfig as $anio)
      {
        if (intval($anios_trabajados) >= intval($anio->anio1) && intval($anios_trabajados) <= intval($anio->anio2))
        {
          $diasVacaciones = intval($anio->dias);
          break;
        }
      }
    }
    // else {
    //   $diasVacaciones = $this->vacacionesConfig[0]->dias;
    // }

    return $diasVacaciones;
  }

  /**
   * Obtiene los dias de las vacaciones para calcular los dias a pagar
   * @return int
   */
  private function diasAnioVacaciones()
  {
    //Dias trabajados en el año en que entro
    $fecha_entrada = explode('-', $this->empleado->fecha_entrada);
    $anio_anterior = date("Y", strtotime("-1 year")).'-'.$fecha_entrada[1].'-'.$fecha_entrada[2];

    $fechaActual = new DateTime(date('Y-m-d'));
    $fechaInicioTrabajar = new DateTime($anio_anterior);
    if(intval($fechaInicioTrabajar->diff($fechaActual)->y) == 0 && $this->aniosTrabajadosEmpleado() > 0 );
      $anio_anterior = date("Y", strtotime("-2 year")).'-'.$fecha_entrada[1].'-'.$fecha_entrada[2];

    $dias_anio_vacaciones = intval(MyString::diasEntreFechas($anio_anterior, date("Y-m-d")));

    if($dias_anio_vacaciones > 365)
      $dias_anio_vacaciones = 365;

    return $dias_anio_vacaciones;
  }

  public function getSubsidioIsr($sumaImporteGravados, $isrAntesSubsidio, $tipo='se')
  {
    $isr = 0;
    $subsidio = 0.01;
    $causado = 0.0;
    if ($tipo == 'se') {
      foreach ($this->tablasIsr['semanal']['subsidios'] as $rango)
      {
        if ($sumaImporteGravados >= floatval($rango->de) && $sumaImporteGravados <= floatval($rango->hasta))
        {
          $causado = floatval($rango->subsidio);
          $isr = $isrAntesSubsidio - floatval($rango->subsidio);
          if ($isr <= 0) {
            $subsidio = abs($isr);
            $isr = 0;
          }
          break;
        }
      }
    }

    if ($tipo != 'se' && $isr == 0 && $causado == 0) {
      $isr = $isrAntesSubsidio;
      $subsidio = 0;
    } elseif ($tipo === 'se' && $this->empleado->dias_trabajados == 0) {
      $subsidio = 0;
    }

    return ['isr' => $isr, 'subsidio' => $subsidio, 'subsidioCausado' => $causado];
  }

}