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
    $per2 = $this->nominaFiltros['semana'];
    $per1 = $per2 - $periodosAtras;
    $per2--;

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
      'isr'              => (isset($this->empleado->nomina->deducciones['isr'])? $this->empleado->nomina->deducciones['isr']['total']: 0),
      'isr_ant_sub'      => 0,
      'subsidio'         => (isset($this->empleado->nomina->otrosPagos['subsidio'])? $this->empleado->nomina->otrosPagos['subsidio']['total']: 0),
      'subsidio_causado' => (isset($this->empleado->nomina->otrosPagos['subsidio'])? $this->empleado->nomina->otrosPagos['subsidio']['SubsidioAlEmpleo']['SubsidioCausado']: 0),
    ];
    $this->datosMes['isr_ant_sub'] = $this->datosMes['subsidio_causado'] - $this->datosMes['subsidio'];
    $this->datosMes['isr_ant_sub'] = $this->datosMes['isr_ant_sub'] > 0? $this->datosMes['isr_ant_sub']: $this->datosMes['isr'];

    if ($this->dataAnt) {
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
    $causadoMes = floatval($dataSub->subsidio);
    $subsidioMes = 0;
    $isrMes = $isrAntSubMes - floatval($dataSub->subsidio);
    if ($isrMes <= 0) {
      $subsidioMes = abs($isrMes);
      $isrMes = 0;
    }

    $isrUltSem = $isrAntSubMes - $this->dataAnt->isr_ant_sub;
    $subUltSem = $causadoMes - $this->dataAnt->subsidio_causado;
    $isrSubUltSem = $isrUltSem - $subUltSem - $isrMes;

    echo "<pre>";
      var_dump($isrUltSem, $subUltSem, $isrSubUltSem, $isrAntSubMes, $isrMes, $subsidioMes, $causadoMes, $this->dataAnt, $this->datosMes);
    echo "</pre>";exit;
  }

}