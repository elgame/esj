<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

trait nominaCalMensual
{
  public $ci = null;

  public function calculoMensual()
  {
    $this->ci =& get_instance();
    // echo "<pre>";
    //   var_dump($this->nominaFiltros ,$this->empleado);
    // echo "</pre>";
    // exit;
    $this->getDataPeriodos();
    $this->calculaMes();
  }

  public function getDataPeriodos()
  {
    $periodosAtras = ($this->nominaFiltros['tipo_nomina']['tipo'] == 'se'? 3: 1);
    $per2 = intval($this->nominaFiltros['semana']);
    $per1 = 1;
    $semanas = MyString::obtenerSemanasDelAnioV2($this->nominaFiltros['anio'], 0, $this->nominaFiltros['dia_inicia_semana'], true);
    $band = false;
    for ($i=count($semanas)-1; $i >= 0; $i--) {
      if ($per2 == $semanas[$i]['semana']) {
        $band = true;
      } elseif ($band && $per2 > $semanas[$i]['semana'] && $semanas[$i]['calcmes']) {
        $per1 = $semanas[$i]['semana'] + 1;
        break;
      }
    }
    // $per2--;

    if (empty($per1)) {
      return '';
    }

    $this->dataAnt = $this->ci->db->query(
      "SELECT id_empleado, Sum(sueldo_semanal) AS sueldo_semanal, Sum(vacaciones) AS vacaciones,
        Sum(prima_vacacional_grabable) AS prima_vacacional_grabable, Sum(aguinaldo_grabable) AS aguinaldo_grabable,
        Sum(ptu_grabable) AS ptu_grabable, Sum(horas_extras_grabable) AS horas_extras_grabable,
        Sum(isr) AS isr, Sum(subsidio_pagado - subsidio) AS isr_ant_sub, Sum(subsidio) AS subsidio,
        Sum(subsidio_pagado) AS subsidio_causado
      FROM nomina_fiscal
      WHERE id_empleado = {$this->empleado->id} AND id_empresa = {$this->nominaFiltros['empresaId']}
        AND anio = {$this->nominaFiltros['anio']}
        AND semana BETWEEN {$per1} AND {$per2}
      GROUP BY id_empleado
      "
    )->row();

    $this->datosMes = [
      'gravado'          => $this->empleado->nomina->percepcionesTotales['TotalGravado'],
      'isr'              => 0, // (isset($this->empleado->nomina->deducciones['isr'])? $this->empleado->nomina->deducciones['isr']['total']: 0),
      'isr_ant_sub'      => 0,
      'subsidio'         => 0, // (isset($this->empleado->nomina->otrosPagos['subsidio'])? $this->empleado->nomina->otrosPagos['subsidio']['total']: 0),
      'subsidio_causado' => 0, // (isset($this->empleado->nomina->otrosPagos['subsidio'])? $this->empleado->nomina->otrosPagos['subsidio']['SubsidioAlEmpleo']['SubsidioCausado']: 0),
    ];
    $this->datosMes['isr_ant_sub'] = $this->datosMes['subsidio_causado'] - $this->datosMes['subsidio'];
    $this->datosMes['isr_ant_sub'] = $this->datosMes['isr_ant_sub'] > 0? $this->datosMes['isr_ant_sub']: $this->datosMes['isr'];

    if (!empty($this->dataAnt)) {
      $this->dataAnt->gravado = $this->dataAnt->sueldo_semanal + $this->dataAnt->vacaciones + $this->dataAnt->prima_vacacional_grabable + $this->dataAnt->aguinaldo_grabable + $this->dataAnt->ptu_grabable + $this->dataAnt->horas_extras_grabable;
      $this->datosMes['gravado']          += $this->dataAnt->gravado;
      $this->datosMes['isr']              += $this->dataAnt->isr;
      $this->datosMes['isr_ant_sub']      += ($this->dataAnt->isr_ant_sub > 0? $this->dataAnt->isr_ant_sub: $this->dataAnt->isr);
      $this->datosMes['subsidio']         += $this->dataAnt->subsidio;
      $this->datosMes['subsidio_causado'] += $this->dataAnt->subsidio_causado;
    } else {
      $this->dataAnt = new stdClass;
      $this->dataAnt->gravado = 0;
      $this->dataAnt->isr = 0;
      $this->dataAnt->isr_ant_sub = 0;
      $this->dataAnt->subsidio = 0;
      $this->dataAnt->subsidio_causado = 0;
    }

    // echo "<pre>";
    //   var_dump($this->datosMes, $this->dataAnt, $this->empleado);
    // echo "</pre>";
    // exit;
  }

  public function calculaMes()
  {
    $dataIsr = $this->ci->db->query(
      "SELECT lim_inferior, lim_superior, cuota_fija, porcentaje, anio
      FROM nomina_mensual_art_113
      WHERE anio = {$this->nominaFiltros['anio']}
        AND {$this->datosMes['gravado']} BETWEEN lim_inferior AND lim_superior
      "
    )->row();

    $dataSub = $this->ci->db->query(
      "SELECT de, hasta, subsidio, anio
      FROM nomina_mensual_subsidios
      WHERE anio = {$this->nominaFiltros['anio']}
        AND {$this->datosMes['gravado']} BETWEEN de AND hasta
      "
    )->row();

    $isrAntSubMes = round((($this->datosMes['gravado'] - floatval($dataIsr->lim_inferior)) * (floatval($dataIsr->porcentaje) / 100.00)) + floatval($dataIsr->cuota_fija), 4);
    $causadoMes = round(floatval($dataSub->subsidio), 2);
    $subsidioMes = 0;
    // 1. Resta el isr de la tabla menos el sub de la tabla mensual y si es < 0 se pone en subsidio si es > 0 se pone en isr de la semana
    $isrMes = $isrAntSubMes - $causadoMes;
    if ($isrMes <= 0) {
      $subsidioMes = abs($isrMes);
      $isrMes = 0;

      $this->empleado->nomina->otrosPagos['subsidio']['ImporteExcento'] = round($subsidioMes, 2);
      $this->empleado->nomina->otrosPagos['subsidio']['total'] = round($subsidioMes, 2);
      $this->empleado->nomina->deducciones['isr']['ImporteExcento'] = 0;
      $this->empleado->nomina->deducciones['isr']['total'] = 0;
    } else {
      $this->empleado->nomina->otrosPagos['subsidio']['ImporteExcento'] = $causadoMes > 0? 0.01: 0;
      $this->empleado->nomina->otrosPagos['subsidio']['total'] = $causadoMes > 0? 0.01: 0;
      $this->empleado->nomina->deducciones['isr']['ImporteExcento'] = round($isrMes, 2);
      $this->empleado->nomina->deducciones['isr']['total'] = round($isrMes, 2);
    }
    $this->empleado->nomina->otrosPagos['subsidio']['SubsidioAlEmpleo']['SubsidioCausado'] = $causadoMes;

    if ($causadoMes < $this->dataAnt->subsidio_causado) {
      $this->empleado->nomina->otrosPagos['subsidio_efec_ent'] = array(
        'TipoOtroPago'     => '008',
        'Clave'            => $this->clavesPatron['subsidio'],
        'Concepto'         => 'Subsidio efectivamente entregado',
        'ImporteGravado'   => 0,
        'ImporteExcento'   => round($this->dataAnt->subsidio, 2),
        'total'            => round($this->dataAnt->subsidio, 2) + 0,
        'ApiKey'           => 'top_subsidio_efectivamente_entregado_',
      );
      $this->empleado->nomina->otrosPagos['isr_ajus_sub'] = array(
        'TipoOtroPago'     => '007',
        'Clave'            => $this->clavesPatron['isr'],
        'Concepto'         => 'ISR ajustado por subsidio',
        'ImporteGravado'   => 0,
        'ImporteExcento'   => round(($this->dataAnt->isr_ant_sub > 0? $this->dataAnt->isr_ant_sub: $this->dataAnt->isr), 2),
        'total'            => round(($this->dataAnt->isr_ant_sub > 0? $this->dataAnt->isr_ant_sub: $this->dataAnt->isr), 2) + 0,
        'ApiKey'           => 'top_isr_ajustado_subsidio_',
      );

      $this->empleado->nomina->deducciones['a_subsidio_emp'] = array(
        'TipoDeduccion'  => '071',
        'Clave'          => $this->clavesPatron['subsidio'],
        'Concepto'       => 'A. Subsidio al empleo',
        'ImporteGravado' => 0,
        'ImporteExcento' => round($this->dataAnt->subsidio, 2),
        'total'          => round($this->dataAnt->subsidio, 2) + 0,
        'ApiKey'         => 'de_a_subsidio_empleo_',
      );
      $this->empleado->nomina->deducciones['a_subsidio_emp'] = array(
        'TipoDeduccion'  => '107',
        'Clave'          => $this->clavesPatron['subsidio'],
        'Concepto'       => 'Ajuste Subsidio Causado',
        'ImporteGravado' => 0,
        'ImporteExcento' => round($this->dataAnt->subsidio_causado, 2),
        'total'          => round($this->dataAnt->subsidio_causado, 2) + 0,
        'ApiKey'         => 'de_ajuste_subsidio_causado_',
      );
    }


    $isrUltSem = $isrAntSubMes - $this->dataAnt->isr_ant_sub;
    $subUltSem = $causadoMes - $this->dataAnt->subsidio_causado;
    $isrSubUltSem = $isrUltSem - $subUltSem - $isrMes;

    // echo "<pre>";
    //   var_dump($isrUltSem, $subUltSem, $isrSubUltSem, $isrAntSubMes, "isrMes => {$isrMes}", "subsidioMes => $subsidioMes", "causadoMes => {$causadoMes}", $this->dataAnt, $this->datosMes, $this->empleado);
    // echo "</pre>";exit;
  }

}