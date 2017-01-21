<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class finiquito
{

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

  private $despido = false;

  private $con_aguin = true;

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
   *
   *
   * @return [type] [description]
   */
  public function procesar($despido)
  {
    if ($despido['indem_cons'] || $despido['indem'] || $despido['prima']) {
      $this->despido_det = $despido;
      $this->despido     = true;
    }

    if ($despido['aguin']) {
      $this->con_aguin = false;
    }

    $this->empleado->anios_trabajados      = $this->aniosTrabajadosEmpleado();
    $this->empleado->dias_trabajados       = $this->diasTrabajadosEmpleado();
    $this->empleado->dias_vacaciones       = $this->diasDeVacaciones();
    $this->empleado->dias_prima_vacacional = $this->diasPrimaVacacional();
    $this->empleado->factor_integracion    = $this->factorIntegracion();
    $this->empleado->dias_aguinaldo        = 0;

    $this->empleado->nomina = new stdclass;
    $this->empleado->nomina->aguinaldo = $this->aguinaldo();
    $this->empleado->nomina->vacaciones = $this->vacaciones();
    $this->empleado->nomina->prima_vacacional = $this->primaVacacional();
    $this->empleado->salario_diario_integrado = $this->sdi();
    if ($this->despido)
      $this->empleado->nomina->indemnizaciones  = $this->indemnizaciones();

    $this->datosNomina();

    // Percepciones
    $this->empleado->nomina->percepciones = array();
    $this->empleado->nomina->percepciones['sueldo'] = $this->pSueldo();
    // $this->empleado->nomina->percepciones['premio_puntualidad'] = $this->pPremioPuntualidad();
    // $this->empleado->nomina->percepciones['premio_asistencia'] = $this->pPremioAsistencia();
    // $this->empleado->nomina->percepciones['despensa'] = $this->pDespensa();
    // $this->empleado->nomina->percepciones['horas_extras'] = $this->pHorasExtras();
    $this->empleado->nomina->percepciones['aguinaldo'] = $this->pAguinaldo();
    $this->empleado->nomina->percepciones['vacaciones'] = $this->pVacaciones();
    $this->empleado->nomina->percepciones['prima_vacacional'] = $this->pPrimaVacacional();
    if ($this->despido)
      $this->empleado->nomina->percepciones['indemnizaciones'] = $this->pIndemnizaciones();
    // $this->empleado->nomina->percepciones['ptu'] = $this->pPtu();

    // Deducciones
    // $this->empleado->nomina->deducciones = array();
    // $this->empleado->nomina->deducciones['imss'] = $this->dImss();
    // $this->empleado->nomina->deducciones['rcv'] = $this->dRcv();
    // $this->empleado->nomina->deducciones['infonavit'] = $this->dInfonavit();
    $this->empleado->nomina->deducciones['otros'] = $this->dOtros();
    $this->empleado->nomina->deducciones['isr'] = $this->dIsr();

    // Totales Percepciones
    $totalSueldosClaves = array('022', '023', '025', '039', '044');
    $totalSeparacionIndemnizacionClaves = array('022', '023', '025');
    foreach ($this->empleado->nomina->percepciones as $keyp => $percep) {
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
            'UltimoSueldoMensOrd' => round($this->empleado->salario_diario*30, 2),
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
    if (count($this->empleado->nomina->percepcionesSeparacionIndemnizacion) > 0) {
      if ($this->empleado->nomina->percepcionesTotales['TotalGravado'] >= $this->empleado->nomina->percepcionesSeparacionIndemnizacion['UltimoSueldoMensOrd'])
        $this->empleado->nomina->percepcionesSeparacionIndemnizacion['IngresoAcumulable'] = round($this->empleado->nomina->percepcionesSeparacionIndemnizacion['UltimoSueldoMensOrd'], 2);
      else
        $this->empleado->nomina->percepcionesSeparacionIndemnizacion['IngresoAcumulable'] = round($this->empleado->nomina->percepcionesTotales['TotalGravado'], 2);
      $this->empleado->nomina->percepcionesSeparacionIndemnizacion['IngresoNoAcumulable'] = abs($this->empleado->nomina->percepcionesTotales['TotalGravado']-$this->empleado->nomina->percepcionesSeparacionIndemnizacion['UltimoSueldoMensOrd']);
    }

    // Totales OtrosPagos
    foreach ($this->empleado->nomina->otrosPagos as $keyp => $otroPago) {
      $this->empleado->nomina->TotalOtrosPagos += floatval($otroPago['total']);
      $this->empleado->nomina->subtotal        += floatval($otroPago['total']);
    }

    // Totales Deducciones
    foreach ($this->empleado->nomina->deducciones as $keyp => $deducc) {
      if ($deducc['TipoDeduccion'] == '002')
        $this->empleado->nomina->deduccionesTotales['TotalImpuestosRetenidos']   += $deducc['total'];
      else
        $this->empleado->nomina->deduccionesTotales['TotalOtrasDeducciones']   += $deducc['total'];
    }
    // Si es cualquier deduccion menos el isr entonces el importe se lo suma al descuento.
    // Si la deduccion es el ISR  no se lo suma al descuento ya que el isr ira en la parte de retenciones.
    $this->empleado->nomina->TotalDeducciones = $this->empleado->nomina->deduccionesTotales['TotalOtrasDeducciones'] + $this->empleado->nomina->deduccionesTotales['TotalImpuestosRetenidos'];
    $this->empleado->nomina->descuento        = $this->empleado->nomina->TotalDeducciones;
    $this->empleado->nomina->isr              = $this->empleado->nomina->deduccionesTotales['TotalImpuestosRetenidos'];

    $this->emisor();
    $this->receptor();

    return $this->empleado;
  }

  public function datosNomina()
  {
    $this->empleado->nomina->Version           = '1.2';
    $this->empleado->nomina->TipoNomina        = $this->empleado->tipo_nomina;
    $this->empleado->nomina->FechaPago         = substr($this->empleado->fecha_final_pago, 0, 10);
    $this->empleado->nomina->FechaInicialPago  = substr($this->empleado->fecha_inicial_pago, 0, 10);
    $this->empleado->nomina->FechaFinalPago    = substr($this->empleado->fecha_final_pago, 0, 10);
    $this->empleado->nomina->NumDiasPagados    = ceil($this->empleado->dias_trabajados);
    $this->empleado->nomina->TotalPercepciones = 0;
    $this->empleado->nomina->TotalDeducciones  = 0;
    $this->empleado->nomina->TotalOtrosPagos   = 0;
    $this->empleado->nomina->subtotal          = 0;
    $this->empleado->nomina->descuento         = 0;
    $this->empleado->nomina->isr               = 0;

    $this->empleado->nomina->percepciones                        = [];
    $this->empleado->nomina->percepcionesJubilacionPensionRetiro = [];
    $this->empleado->nomina->percepcionesSeparacionIndemnizacion = [];
    $this->empleado->nomina->percepcionesTotales                 = ['TotalGravado' => 0, 'TotalExento' => 0];
    $this->empleado->nomina->deducciones                         = [];
    $this->empleado->nomina->deduccionesTotales                  = ['TotalOtrasDeducciones' => 0, 'TotalImpuestosRetenidos' => 0];
    $this->empleado->nomina->otrosPagos                          = [];
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

      $fecha1 = new DateTime($this->empleado->nomina->receptor['FechaInicioRelLaboral']);
      $fecha2 = new DateTime($this->empleado->nomina->FechaFinalPago);
      $finte = $fecha1->diff($fecha2);
      if ($finte->m == 0 && $finte->y > 0) {
        $this->empleado->nomina->receptor['Antigüedad'] = 'P'.(intval($finte->days/7)).'W';
      } else
        $this->empleado->nomina->receptor['Antigüedad'] = 'P'.($finte->y>0?$finte->y.'Y':'').($finte->m>0?$finte->m.'M':'').$finte->d.'D';
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
    if (isset($this->empleado->salario_diario_integrado)) {
      $this->empleado->nomina->receptor['SalarioDiarioIntegrado'] = $this->empleado->salario_diario_integrado;
    }
    $this->empleado->nomina->receptor['ClaveEntFed'] = $this->empleado->estado;
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
    $diasAguinaldo = 0;
    if ($this->con_aguin) {
      $diasAguinaldo = round(($this->empleado->dias_trabajados / 365 ) * $this->empresaConfig->aguinaldo, 2);
    }
    return $diasAguinaldo * $this->empleado->salario_diario;
  }

  /**
   * Calcula el total a pagarle de las vacaciones al empleado.
   *
   * @return mixed
   */
  public function vacaciones()
  {
    return $this->empleado->dias_vacaciones * $this->empleado->salario_diario;
  }

  /**
   * Calcula la prima vacacional en base al porcentaje que este dando
   * la empresa y el salario diario del empleado.
   *
   * @return float
   */
  public function primaVacacional()
  {
    return $this->empleado->dias_prima_vacacional * $this->empleado->salario_diario;
  }

  /**
   * Calcula cuantos dias de prima vacacional se pagaran.
   *
   * @return float
   */
  public function diasPrimaVacacional()
  {
    return round((intval($this->empresaConfig->prima_vacacional) / 100) * $this->empleado->dias_vacaciones, 4);
  }

  /**
   * Calcula el factor de integracion.
   *
   * @return float
   */
  public function factorIntegracion()
  {
    return round((365 + $this->empresaConfig->aguinaldo + $this->empleado->dias_prima_vacacional) / 365, 4);
  }

  /**
   * Calcula el SDI = SALARIO DIARIO INTEGRADO
   *
   * @return float
   */
  public function sdi()
  {
    return round($this->empleado->factor_integracion * $this->empleado->salario_diario);
  }

  /**
   *  Cálculo de las indemnizaciones
   * Cuando es una liquidacion (lo corren) de trabajador calcula las indemnizaciones
   *
   * @return [type] [description]
   */
  public function indemnizaciones()
  {
    $despido_injustificado = 0;
    if ($this->despido_det['indem_cons']) {
      // 3 meses de sueldo
      $despido_injustificado = $this->empleado->salario_diario_integrado*90;
    }

    $indemnisacion_negativa = 0;
    if ($this->despido_det['indem']) {
      // 20 días de sueldo por cada año de servicios prestados
      $indemnisacion_negativa = 20*$this->empleado->anios_trabajados*$this->empleado->salario_diario_integrado;
      $indemnisacion_negativa += 20*($this->empleado->dias_anio_vacaciones/365)*$this->empleado->salario_diario_integrado;
    }

    $prima_antiguedad = 0;
    if ($this->despido_det['prima']) {
      // Prima de antigüedad 12 días de salario por cada año de servicio
      // SalariozonaB*2*años_trabajados*12
      $prima_antiguedad = floatval($this->salariosZonasConfig->zona_b)*2*$this->empleado->anios_trabajados*12;
      $prima_antiguedad += floatval($this->salariosZonasConfig->zona_b)*2*($this->empleado->dias_anio_vacaciones/365)*12;
    }

    return round($despido_injustificado+$indemnisacion_negativa+$prima_antiguedad, 4);
  }

  /*
   |------------------------------------------------------------------------
   | Percepciones
   |------------------------------------------------------------------------
   */

  /**
   * Percepcion Sueldo - 001
   *
   * @return array
   */
  public function pSueldo()
  {
    $this->empleado->nomina->sueldo = $this->empleado->salario_diario * $this->empleado->dias_trabajados_semana;

    return array(
      'TipoPercepcion' => '001',
      'Clave'          => $this->clavesPatron[($this->empleado->id_departamente==1? 'sueldo1': 'sueldo2')],
      'Concepto'       => 'Sueldos, Salarios Rayas y Jornales',
      'ImporteGravado' => round($this->empleado->nomina->sueldo, 2),
      'ImporteExcento' => 0,
      'total'          => round($this->empleado->nomina->sueldo + 0, 2),
    );
  }

  /**
   * Percepcion Premio Puntualidad - 010
   *
   * @return array
   */
  public function pPremioPuntualidad()
  {
    $premioPuntualidad = $this->empleado->nomina->sueldo * ($this->empresaConfig->puntualidad / 100);

    return array(
      'TipoPercepcion' => '010',
      'Clave'          => $this->clavesPatron['premio_puntualidad'],
      'Concepto'       => 'Premios por puntualidad',
      'ImporteGravado' => round($premioPuntualidad, 2),
      'ImporteExcento' => 0,
      'total'          => round($premioPuntualidad + 0, 2),
    );
  }

  /**
   * Percepcion Premio Asistencia - 010
   *
   * @return array
   */
  public function pPremioAsistencia()
  {
    $premioAsistencia = $this->empleado->nomina->sueldo * ($this->empresaConfig->asistencia / 100);

    return array(
      'TipoPercepcion' => '049',
      'Clave'          => $this->clavesPatron[($this->empleado->id_departamente==1? 'premio_asistencia1': 'premio_asistencia2')],
      'Concepto'       => 'Premios por asistencia',
      'ImporteGravado' => round($premioAsistencia, 2),
      'ImporteExcento' => 0,
      'total'          => round($premioAsistencia + 0, 2),
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
      $topeExcento = 5 * $this->salariosZonasConfig->zona_b;
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
    );
  }

  /**
   * Percepcion Aguinaldo - 002
   *
   * @return array
   */
  public function pAguinaldo()
  {
    $topeExcento = 30 * floatval($this->salariosZonasConfig->zona_b);

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
    );
  }

  /**
   * Percepcion Vacaciones
   *
   * @return array
   */
  public function pVacaciones()
  {
    return array(
      'TipoPercepcion' => '038',
      'Clave'          => $this->clavesPatron[($this->empleado->id_departamente==1? 'vacaciones1': 'vacaciones2')],
      'Concepto'       => 'Vacaciones',
      'ImporteGravado' => round($this->empleado->nomina->vacaciones, 2),
      'ImporteExcento' => 0,
      'total'          => round($this->empleado->nomina->vacaciones, 2) + 0,
    );
  }

  /**
   * Percepcion Prima Vacacional - 021
   *
   * @return array
   */
  public function pPrimaVacacional()
  {
    $topeExcento = 15 * floatval($this->salariosZonasConfig->zona_b);

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

    return array(
      'TipoPercepcion' => '021',
      'Clave'          => $this->clavesPatron[($this->empleado->id_departamente==1? 'prima_vacacional1': 'prima_vacacional2')],
      'Concepto'       => 'Prima vacacional',
      'ImporteGravado' => round($gravado, 2),
      'ImporteExcento' => round($excento, 2),
      'total'          => round($gravado, 2) + round($excento, 2),
    );
  }

  /**
   * Percepcion Indemnizaciones - 025
   *
   * @return array
   */
  public function pIndemnizaciones()
  {
    $anios_trabajados = $this->empleado->anios_trabajados+($this->empleado->dias_anio_vacaciones/365); //($this->empleado->dias_anio_vacaciones>0? 1: 0)
    $topeExcento = 90 * floatval($this->salariosZonasConfig->zona_a)*$anios_trabajados;

    // Si los que se le dara de indemnizacion al empleado excede el tope excento.
    if ($this->empleado->nomina->indemnizaciones > $topeExcento)
    {
      $gravado = $this->empleado->nomina->indemnizaciones - $topeExcento;
      $excento = $topeExcento;
    }
    else
    {
      $gravado = 0;
      $excento = $this->empleado->nomina->indemnizaciones;
    }

    return array(
      'TipoPercepcion' => '025',
      'Clave'          => $this->clavesPatron[($this->empleado->id_departamente==1? 'indemnizaciones1': 'indemnizaciones2')],
      'Concepto'       => 'Indemnizaciones',
      'ImporteGravado' => round($gravado, 2),
      'ImporteExcento' => round($excento, 2),
      'total'          => round($gravado, 2) + round($excento, 2),
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
    if ($this->empleado->utilidad_empresa > 0)
    {
      // 5000
      $ptu = $this->empleado->utilidad_empresa / 2;

      // 1438.05
      $percepciones = round((floatval($this->empleado->ptu_percepciones_empleado) * $ptu) / floatval($this->empleado->ptu_percepciones_empleados), 2);

      // 1506.02
      $dias = round((floatval($this->empleado->ptu_dias_trabajados_empleado) * $ptu) / floatval($this->empleado->ptu_dias_trabajados_empleados), 2);

      // 2944.07
      $ptuTrabajador = $percepciones + $dias;

      // 15 * 61.38 = 920.7
      $topeExcento = 15 * $this->salariosZonasConfig->zona_b;

      if ($ptuTrabajador > $topeExcento)
      {
        // 2023.37
        $gravado = $ptuTrabajador - $topeExcento;
        // 920.7
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
      'ImporteGravado' => (float)$gravado,
      'ImporteExcento' => (float)$excento,
      'total'          => floatval($gravado) + floatval($excento),
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
    $retencionImss = 0.0125 * $baseImss;

    $cuotaAdicionalImss = 0;
    $topeAdicionalImss = 3 * $this->salariosZonasConfig->zona_a;
    if ($this->empleado->nomina->salario_diario_integrado > $topeAdicionalImss)
    {
      $cuotaAdicionalImss = 0.012 * ($this->empleado->nomina->salario_diario_integrado - $topeAdicionalImss);
    }
    $totalImss = round($cuotaAdicionalImss + $retencionImss, 2);

    return array(
      'TipoDeduccion' => '001',
      'Clave'          => $this->clavesPatron['imss'],
      'Concepto'       => 'Seguro social',
      'ImporteGravado' => 0,
      'ImporteExcento' => round($totalImss, 2),
      'total'          => round($totalImss, 2) + 0,
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

    return array(
      'TipoDeduccion' => '003',
      'Clave'          => $this->clavesPatron['rcv'],
      'Concepto'       => 'Aportaciones a retiro, cesantía en edad avanzada y vejez',
      'ImporteGravado' => 0,
      'ImporteExcento' => round($rcv, 2),
      'total'          => round($rcv, 2) + 0,
    );
  }

  /**
   * Deduccion Infonavit - 010
   *
   * @return array
   */
  public function dInfonavit()
  {
    $infonavit = round(($this->empleado->infonavit / 30.4) * ($this->salariosZonasConfig->zona_a * $this->empleado->dias_trabajados), 2);

    return array(
      'TipoDeduccion' => '10',
      'Clave'          => $this->clavesPatron['infonavit'],
      'Concepto'       => 'Pago por crédito de vivienda',
      'ImporteGravado' => 0,
      'ImporteExcento' => round($infonavit, 2),
      'total'          => round($infonavit, 2) + 0,
    );
  }

  /**
   * Deduccion ISR - 002
   *
   * @return array
   */
  public function dIsr()
  {
    // Almacena el total de la suma del importe gravado diarios que incluye
    // el aguinaldo, prima vacacional y ptu
    $sumaImporteGravadosDiariosConOtros = 0;

    // Almacena el total de la suma del importe gravado diarios que no incluye
    // el aguinaldo, prima vacacional y ptu
    $sumaImporteGravadosDiariosSinOtros = 0;

    $isrBase = round($this->empleado->nomina->percepciones['aguinaldo']['ImporteGravado'] +
                     $this->empleado->nomina->percepciones['prima_vacacional']['ImporteGravado'] +
                     $this->empleado->nomina->percepciones['vacaciones']['ImporteGravado'], 2);

    $aguinaldoGravadoDiario = round(floatval($this->empleado->nomina->percepciones['aguinaldo']['ImporteGravado']) / 52, 2);
    $primaVacacionalGravadoDiario = round(floatval($this->empleado->nomina->percepciones['prima_vacacional']['ImporteGravado']) / 52, 2);
    $vacacionesGravadoDiario = round(floatval($this->empleado->nomina->percepciones['vacaciones']['ImporteGravado']) / 52 , 2);
    $salarioDiario = (float)$this->empleado->salario_diario;
    if ($this->despido)
      $indemnizacionGravadoDiario = round(floatval($this->empleado->nomina->percepciones['indemnizaciones']['ImporteGravado']) / 52, 2);

    $sueldoSemana = round($this->empleado->dias_trabajados_semana * $this->empleado->salario_diario, 2);
    // Recorre los rangos de la tabla semanal de ISR para determinar en que
    // limites se encuentra el sueldo que obtuvo de la semana que se va.
    $isrSemana = 0;
    foreach ($this->tablasIsr['semanal']['art113'] as $rango)
    {
      if ($sueldoSemana >= floatval($rango->lim_inferior) && $sueldoSemana <= floatval($rango->lim_superior))
      {
        // 46.08
        $isrSemana = round((($sueldoSemana - floatval($rango->lim_inferior)) * (floatval($rango->porcentaje) / 100.00)) + floatval($rango->cuota_fija), 2);
        break;
      }
    }
    // echo "<pre>";
    //   var_dump($isrSemana);
    // echo "</pre>";exit;

    // Recorre los rangos de la tabla semanal de los subsidios para determinar en que
    // limites se encuentra la suma de los importes gravados.
    $isrSemanaSubsidio = 0;
    foreach ($this->tablasIsr['semanal']['subsidios'] as $rango)
    {
      if ($sueldoSemana >= floatval($rango->de) && $sueldoSemana <= floatval($rango->hasta))
      {
        // −44.36
        $isrSemanaSubsidio = floatval($rango->subsidio);
        break;
      }
    }
    // echo "<pre>";
    //   var_dump($isrSemanaSubsidio);
    // echo "</pre>";exit;

    // Suma todos los gravados diarios con salario diario. 200.23 $salarioDiario +
    $sumaImporteGravadosDiariosConOtros = $aguinaldoGravadoDiario + $primaVacacionalGravadoDiario + $vacacionesGravadoDiario + $sueldoSemana;
    if ($this->despido)
      $sumaImporteGravadosDiariosConOtros += $indemnizacionGravadoDiario;
    // echo "<pre>";
    //   var_dump($sumaImporteGravadosDiariosConOtros, $indemnizacionGravadoDiario);
    // echo "</pre>";exit;

    // Recorre los rangos de la tabla diaria de ISR para determinar en que
    // limites se encuentra la suma de los importes gravados diarios con
    // aguinaldo, prima vacacional, ptu y tambien para obtener el rango
    // de la suma de los importes gravados diarios sin aguinaldo, prima vacacional
    // y ptu.
    $isrAuxConOtros = 0;
    foreach ($this->tablasIsr['semanal']['art113'] as $rango)
    {
      if ($sumaImporteGravadosDiariosConOtros >= floatval($rango->lim_inferior) && $sumaImporteGravadosDiariosConOtros <= floatval($rango->lim_superior))
      {
        // 14.85
        $isrAuxConOtros = round((($sumaImporteGravadosDiariosConOtros - floatval($rango->lim_inferior)) * (floatval($rango->porcentaje) / 100.00)) + floatval($rango->cuota_fija), 2);
      }
    }

    $isrSemaAgu = floatval($isrAuxConOtros) - floatval($isrSemana);
    $isrAnual = $isrSemaAgu * 52;
    $isr = floatval($isrSemana - $isrSemanaSubsidio + $isrAnual);
    // echo "<pre>";
    //   var_dump($isrSemana, $isrAuxConOtros, $isrSemanaSubsidio, $isrAnual, $isr);
    // echo "</pre>";exit;

    // if ($this->despido) {
    //   // ISR para el calculo de las indemnizaciones
    //   $sueldoMensual = round(7 * $this->empleado->salario_diario * 30, 2);
    //   $taza_isr = $this->empleado->isr_ultima_semana/$sueldoMensual;
    //   $isr += $this->empleado->nomina->percepciones['indemnizaciones']['ImporteGravado']*$taza_isr;
    // }

    // $isrUltimaSemana = floatval($this->empleado->isr_ultima_semana / 7);
    // $isr = round(($isrAuxConOtros - $isrUltimaSemana) * $this->empleado->dias_trabajados, 2);
    // $isr += floatval($isrSemana);

    $subsidio = 0;
    if ($isr < 0)
    {
      $subsidio = abs($isr);
      $isr = 0;
    }

    $this->empleado->nomina->subsidio = $subsidio;
    $this->empleado->nomina->otrosPagos['subsidio'] = array(
      'TipoOtroPago'     => '002',
      'Clave'            => $this->clavesPatron['subsidio'],
      'Concepto'         => 'Subsidio para el empleo',
      'ImporteGravado'   => 0,
      'ImporteExcento'   => round($this->subsidio, 2),
      'total'            => round($this->subsidio, 2) + 0,
      'SubsidioAlEmpleo' => array('SubsidioCausado' => (round($this->subsidio, 2) + 0) )
    );

    return array(
      'TipoDeduccion' => '002',
      'Clave'          => $this->clavesPatron['isr'],
      'Concepto'       => 'ISR',
      'ImporteGravado' => 0,
      'ImporteExcento' => round($isr, 2),
      'total'          => round($isr, 2) + 0,
    );
  }

  /**
   * Deduccion otros - 004
   *
   * @return array
   */
  public function dOtros()
  {
    // $otros = floatval($this->empleado->descuento_playeras);
    $otros = 0;
    $otros += $this->empleado->prestamos;
    // foreach ($this->empleado->prestamos as $prestamo)
    // {
    //   $otros += floatval($prestamo['pago_semana_descontar']);
    // }

    return array(
      'TipoDeduccion' => '004',
      'Clave'          => $this->clavesPatron['otros'],
      'Concepto'       => 'Otros',
      'ImporteGravado' => 0,
      'ImporteExcento' => round($otros, 2),
      'total'          => round($otros, 2) + 0,
    );
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
   * Obtiene los dias trabajados del empleado en el año.
   *
   * @return int | dias trabajados
   */
  private function diasTrabajadosEmpleado()
  {
    // return intval($this->empleado->dias_transcurridos) - intval($this->empleado->dias_faltados_anio);
    return intval($this->empleado->dias_transcurridos);
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
    $diasVacaciones = 0;

    // Si tiene 1 año o mas.
    if (intval($this->empleado->anios_trabajados) > 0)
    {
      // Recorre las configuraciones para obtener los dias de vacaciones a dar
      // segun los años trabajados.
      foreach ($this->vacacionesConfig as $anio)
      {
        if (intval($this->empleado->anios_trabajados) >= intval($anio->anio1) && intval($this->empleado->anios_trabajados) <= intval($anio->anio2))
        {
          $diasVacaciones = intval($anio->dias);
          break;
        }
      }
    }
    else
    {
      $diasVacaciones = 6;
    }

    // Saca el proporcional de dias de vacaciones.
    $diasVacaciones = round(($this->empleado->dias_anio_vacaciones / 365 ) * $diasVacaciones , 4);

    return $diasVacaciones;
  }
}