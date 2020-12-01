<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

trait nominaCalMensual
{
  public $ci = null;

  public function calculoMensual()
  {
    $this->ci =& get_instance();
    // echo "<pre>";
    //   var_dump($this->nominaFiltros ,$this->empleado);
    // echo "</pre>";exit;
    $this->getDataPeriodos();
  }

  public function getDataPeriodos()
  {
    $periodosAtras = ($this->nominaFiltros['tipo_nomina']['tipo'] == 'se'? 3: 1);
    $per2 = $this->nominaFiltros['semana'];
    $per1 = $per2 - $periodosAtras;
    $per2--;

    $data = $this->ci->db->query(
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

    $datos = [
      'gravado'          => $this->empleado->nomina->percepcionesTotales['TotalGravado'],
      'isr'              => (isset($this->empleado->nomina->deducciones['isr'])? $this->empleado->nomina->deducciones['isr']['total']: 0),
      'isr_ant_sub'      => 0,
      'subsidio'         => (isset($this->empleado->nomina->otrosPagos['subsidio'])? $this->empleado->nomina->otrosPagos['subsidio']['total']: 0),
      'subsidio_causado' => (isset($this->empleado->nomina->otrosPagos['subsidio'])? $this->empleado->nomina->otrosPagos['subsidio']['SubsidioAlEmpleo']['SubsidioCausado']: 0),
    ];
    $datos['isr_ant_sub'] = $datos['subsidio_causado'] - $datos['subsidio'];
    if ($data) {
      $datos['gravado']          += $data->sueldo_semanal + $data->vacaciones + $data->prima_vacacional_grabable + $data->aguinaldo_grabable + $data->ptu_grabable + $data->horas_extras_grabable;
      $datos['isr']              += $data->isr;
      $datos['isr_ant_sub']      += $data->isr_ant_sub;
      $datos['subsidio']         += $data->subsidio;
      $datos['subsidio_causado'] += $data->subsidio_causado;
    }

    echo "<pre>";
      var_dump($datos, $this->empleado->nomina);
    echo "</pre>";exit;
  }

}