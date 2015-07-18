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

  /*
   |------------------------------------------------------------------------
   | Setters
   |------------------------------------------------------------------------
   */

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
  public function procesar($despido=false)
  {
    $this->despido = $despido;

    $this->empleado->anios_trabajados      = $this->aniosTrabajadosEmpleado();
    $this->empleado->dias_trabajados      = $this->diasTrabajadosEmpleado();
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

    return $this->empleado;
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
    $diasAguinaldo = round(($this->empleado->dias_trabajados / 365 ) * $this->empresaConfig->aguinaldo, 2);
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
    // 3 meses de sueldo
    $despido_injustificado = $this->empleado->salario_diario_integrado*90;

    // 20 días de sueldo por cada año de servicios prestados
    $indemnisacion_negativa = 20*$this->empleado->anios_trabajados*$this->empleado->salario_diario_integrado;
    $indemnisacion_negativa += ($this->empleado->dias_anio_vacaciones*20/365)*$this->empleado->salario_diario_integrado;

    // Prima de antigüedad 12 días de salario por cada año de servicio
    $prima_antiguedad = 12*$this->empleado->anios_trabajados*$this->empleado->salario_diario_integrado;
    $prima_antiguedad += ($this->empleado->dias_anio_vacaciones*12/365)*$this->empleado->salario_diario_integrado;

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
      'ImporteGravado' => (float)$this->empleado->nomina->sueldo,
      'ImporteExcento' => 0,
      'total'          => (float)$this->empleado->nomina->sueldo + 0,
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
      'ImporteGravado' => (float)$premioPuntualidad,
      'ImporteExcento' => 0,
      'total'          => (float)$premioPuntualidad + 0,
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
      'TipoPercepcion' => '010',
      'Clave'          => $this->clavesPatron[($this->empleado->id_departamente==1? 'premio_asistencia1': 'premio_asistencia2')],
      'Concepto'       => 'Premios por asistencia',
      'ImporteGravado' => (float)$premioAsistencia,
      'ImporteExcento' => 0,
      'total'          => (float)$premioAsistencia + 0,
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
      'TipoPercepcion' => '008',
      'Clave'          => $this->clavesPatron['despensa'],
      'Concepto'       => 'Ayudas',
      'ImporteGravado' => 0,
      'ImporteExcento' => (float)$despensa,
      'total'          => (float)$despensa + 0,
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
      'ImporteGravado' => $gravado,
      'ImporteExcento' => (float)$excento,
      'total'          => floatval($gravado) + floatval($excento),
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
      'Concepto'       => 'Aguinaldo',
      'ImporteGravado' => $gravado,
      'ImporteExcento' => (float)$excento,
      'total'          => floatval($gravado) + floatval($excento),
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
      'TipoPercepcion' => '016',
      'Clave'          => $this->clavesPatron[($this->empleado->id_departamente==1? 'vacaciones1': 'vacaciones2')],
      'Concepto'       => 'Vacaciones',
      'ImporteGravado' => (float)$this->empleado->nomina->vacaciones,
      'ImporteExcento' => 0,
      'total'          => floatval($this->empleado->nomina->vacaciones) + 0,
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
      'Concepto'       => 'Prima Vacacional',
      'ImporteGravado' => $gravado,
      'ImporteExcento' => (float)$excento,
      'total'          => floatval($gravado) + floatval($excento),
    );
  }

  /**
   * Percepcion Indemnizaciones - 025
   *
   * @return array
   */
  public function pIndemnizaciones()
  {
    $anios_trabajados = $this->empleado->anios_trabajados+($this->empleado->dias_anio_vacaciones>0? 1: 0);
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
      'ImporteGravado' => $gravado,
      'ImporteExcento' => (float)$excento,
      'total'          => floatval($gravado) + floatval($excento),
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
      'ImporteExcento' => (float)$totalImss,
      'total'          => floatval($totalImss) + 0,
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
      'ImporteExcento' => (float)$rcv,
      'total'          => floatval($rcv) + 0,
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
      'ImporteExcento' => (float)$infonavit,
      'total'          => floatval($infonavit) + 0,
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
    //   var_dump($sumaImporteGravadosDiariosConOtros);
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
    //   var_dump($isrSemana, $isrSemanaSubsidio, $isrAnual, $isr);
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
    $this->empleado->nomina->percepciones['subsidio'] = array(
      'TipoPercepcion' => '017',
      'Clave'          => $this->clavesPatron['subsidio'],
      'Concepto'       => 'Subsidio para el empleo',
      'ImporteGravado' => 0,
      'ImporteExcento' => (float)$subsidio,
      'total'          => floatval($subsidio) + 0,
    );

    return array(
      'TipoDeduccion' => '002',
      'Clave'          => $this->clavesPatron['isr'],
      'Concepto'       => 'ISR',
      'ImporteGravado' => 0,
      'ImporteExcento' => (float)$isr,
      'total'          => floatval($isr) + 0,
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
      'ImporteExcento' => (float)$otros,
      'total'          => floatval($otros) + 0,
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
  private function aniosTrabajadosEmpleado()
  {
    $fechaActual = new DateTime($this->empleado->fecha_salida);
    $fechaInicioTrabajar = new DateTime($this->empleado->fecha_entrada);
    return intval($fechaInicioTrabajar->diff($fechaActual)->y);
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