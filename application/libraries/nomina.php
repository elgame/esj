<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class nomina
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

  public $subsidio = null;

  public $isr = null;

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
   * Asigna el subsidio e isr, si ya es asignado aqui ya no se calcula.
   *
   * @param array $config
   */
  public function setSubsidioIsr($subsidio, $isr)
  {
    $this->subsidio = $subsidio;
    $this->isr = $isr;
    return $this;
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

    // Percepciones
    $this->empleado->nomina->percepciones = array();
    $this->empleado->nomina->percepciones['sueldo'] = $this->pSueldo();
    // $this->empleado->nomina->percepciones['premio_puntualidad'] = $this->pPremioPuntualidad();
    $this->empleado->nomina->percepciones['premio_asistencia'] = $this->pPremioAsistencia();
    // $this->empleado->nomina->percepciones['despensa'] = $this->pDespensa();
    $this->empleado->nomina->percepciones['horas_extras'] = $this->pHorasExtras();
    $this->empleado->nomina->percepciones['aguinaldo'] = $this->pAguinaldo();
    $this->empleado->nomina->percepciones['vacaciones'] = $this->pVacaciones();
    $this->empleado->nomina->percepciones['prima_vacacional'] = $this->pPrimaVacacional();
    $this->empleado->nomina->percepciones['ptu'] = $this->pPtu();

    // Deducciones
    $this->empleado->nomina->deducciones = array();
    $this->empleado->nomina->deducciones['imss'] = $this->dImss();
    $this->empleado->nomina->deducciones['rcv'] = $this->dRcv();
    $this->empleado->nomina->deducciones['infonavit'] = $this->dInfonavit();
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
  public function diasPrimaVacacional()
  {
    return (intval($this->empresaConfig->prima_vacacional) / 100) * $this->empleado->dias_vacaciones;
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
    $this->empleado->nomina->sueldo = $this->empleado->salario_diario * $this->empleado->dias_trabajados;

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
    // echo "</pre>";

    if ($this->empleado->utilidad_empresa_ptu > 0 && $this->empleado->ptu_percepciones_empleados > 0 && $this->empleado->ptu_dias_trabajados_empleados > 0)
    {
      $ptu = $this->empleado->utilidad_empresa_ptu / 2;

      $percepciones = round((floatval($this->empleado->ptu_percepciones_empleado) * $ptu) / floatval($this->empleado->ptu_percepciones_empleados), 3);
      $dias = round((floatval($this->empleado->ptu_dias_trabajados_empleado) * $ptu) / floatval($this->empleado->ptu_dias_trabajados_empleados), 3);

      $this->empleado->ptu_empleado_percepciones = $percepciones;
      $this->empleado->ptu_empleado_dias         = $dias;
      $this->empleado->ptu_empleado_percepciones_fact = round($ptu/floatval($this->empleado->ptu_percepciones_empleados), 4);
      $this->empleado->ptu_empleado_dias_fact         = round($ptu/floatval($this->empleado->ptu_dias_trabajados_empleados), 4);

      $ptuTrabajador = $percepciones + $dias;

      $topeExcento = 15 * $this->salariosZonasConfig->zona_b;

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
    $topeExcento = 25 * floatval($this->salariosZonasConfig->zona_b);

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
    $topeAdicionalImss = 3 * $this->salariosZonasConfig->zona_b;
    if ($this->empleado->nomina->salario_diario_integrado > $topeAdicionalImss)
    {
      $cuotaAdicionalImss = (0.012 * ($this->empleado->nomina->salario_diario_integrado - $topeAdicionalImss)) * $this->empleado->dias_trabajados;
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
    if ($this->subsidio !== null && $this->isr !== null)
    {
      $this->empleado->nomina->percepciones['subsidio'] = array(
        'TipoPercepcion' => '017',
        'Clave'          => $this->clavesPatron['subsidio'],
        'Concepto'       => 'Subsidio para el empleo',
        'ImporteGravado' => 0,
        'ImporteExcento' => (float)$this->subsidio,
        'total'          => floatval($this->subsidio) + 0,
      );

      return array(
        'TipoDeduccion' => '002',
        'Clave'          => $this->clavesPatron['isr'],
        'Concepto'       => 'ISR',
        'ImporteGravado' => 0,
        'ImporteExcento' => (float)$this->isr,
        'total'          => floatval($this->isr) + 0,
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
      $sumaImporteGravados += floatval($this->empleado->nomina->percepciones['sueldo']['ImporteGravado']);

      // Si el importe gravado del premio de asistencia es mayor a 0.
      $premioAsistenciaGravadoDiario = 0;
      if ($this->empleado->nomina->percepciones['premio_asistencia']['ImporteGravado'] > 0)
      {
        $sumaImporteGravados += floatval($this->empleado->nomina->percepciones['premio_asistencia']['ImporteGravado']);

        $premioAsistenciaGravadoDiario = round(floatval($this->empleado->nomina->percepciones['premio_asistencia']['ImporteGravado']) / 365, 4);
      }

      // Si se tomara en cuenta la percepcion horas extras la suma.
      $horasExtrasGravadoDiario = 0;
      if (isset($_POST['horas_extras']) && $_POST['horas_extras'] != 0)
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
      if (isset($_POST['con_aguinaldo']) && $_POST['con_aguinaldo'] === '1')
      {
        $aguinaldoGravadoDiario = round(floatval($this->empleado->nomina->percepciones['aguinaldo']['ImporteGravado']) / 365, 4);
      }

      // Si el importe gravado del ptu es mayor a 0.
      $ptuGravadoDiario = 0;
      if ($this->empleado->nomina->percepciones['ptu']['ImporteGravado'] > 0)
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

      // Recorre los rangos de la tabla semanal de los subsidios para determinar en que
      // limites se encuentra la suma de los importes gravados.
      $isr = 0;
      foreach ($this->tablasIsr['semanal']['subsidios'] as $rango)
      {
        if ($sumaImporteGravados >= floatval($rango->de) && $sumaImporteGravados <= floatval($rango->hasta))
        {
          $isr = abs($isrAntesSubsidio - floatval($rango->subsidio));
          break;
        }
      }

      // Agrega la percepcion subsidio a la nomina.
      $this->empleado->nomina->percepciones['subsidio'] = array(
        'TipoPercepcion' => '017',
        'Clave'          => $this->clavesPatron['subsidio'],
        'Concepto'       => 'Subsidio para el empleo',
        'ImporteGravado' => 0,
        'ImporteExcento' => (float)$rango->subsidio,
        'total'          => floatval($rango->subsidio) + 0,
      );

      // Suma todos los gravados diarios con aguinaldo, prima vacacional y ptu.
      $sumaImporteGravadosDiariosConOtros = $this->empleado->salario_diario + $horasExtrasGravadoDiario + $premioAsistenciaGravadoDiario + $aguinaldoGravadoDiario + $ptuGravadoDiario;

      // Suma todos los gravados diarios sin aguinaldo, prima vacacional y ptu.
      $sumaImporteGravadosDiariosSinOtros = $this->empleado->salario_diario + $horasExtrasGravadoDiario + $premioAsistenciaGravadoDiario;

      // Recorre los rangos de la tabla diaria de ISR para determinar en que
      // limites se encuentra la suma de los importes gravados diarios con
      // aguinaldo, prima vacacional, ptu y tambien para obtener el rango
      // de la suma de los importes gravados diarios sin aguinaldo, prima vacacional
      // y ptu.
      $isrAuxConOtros = 0;
      $isrAuxSinOtros = 0;
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

      $isrAguinaldoPrimaPtu = round(($isrAuxConOtros - $isrAuxSinOtros) * 365, 2);

      return array(
        'TipoDeduccion' => '002',
        'Clave'          => $this->clavesPatron['isr'],
        'Concepto'       => 'ISR',
        'ImporteGravado' => 0,
        'ImporteExcento' => (float)$isr + $isrAguinaldoPrimaPtu,
        'total'          => floatval($isr + $isrAguinaldoPrimaPtu) + 0,
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

    foreach ($this->empleado->prestamos as $prestamo)
    {
      $otros += floatval($prestamo['pago_semana_descontar']);
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
    $fechaActual = new DateTime(date('Y-m-d'));
    $fechaInicioTrabajar = new DateTime($this->empleado->fecha_entrada);
    return intval($fechaInicioTrabajar->diff($fechaActual)->y);
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
    // // Si tiene 1 año o mas.
    // if (intval($this->empleado->anios_trabajados) > 0)
    // {
    //   // Recorre las configuraciones para obtener los dias de vacaciones a dar
    //   // segun los años trabajados.
    //   foreach ($this->vacacionesConfig as $anio)
    //   {
    //     if (intval($this->empleado->anios_trabajados) >= intval($anio->anio1) && intval($this->empleado->anios_trabajados) <= intval($anio->anio2))
    //     {
    //       $diasVacaciones = intval($anio->dias);
    //       break;
    //     }
    //   }
    // }

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

    $dias_anio_vacaciones = intval(String::diasEntreFechas($anio_anterior, date("Y-m-d")));

    if($dias_anio_vacaciones > 365)
      $dias_anio_vacaciones = 365;

    return $dias_anio_vacaciones;
  }

}