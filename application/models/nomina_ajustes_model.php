<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class nomina_ajustes_model extends CI_Model {

  private $empleado = [];

  /**
   * Ajuste del ptu, se incremento el monto del ptu, se recalculo el monto total
   * y se resto lo ya timbrado (se desactivo temporalmente el *2 y /2)
   * @return [type]        [description]
   */
  public function confAjustePtu2019()
  {
    $data = $this->db->query("
      SELECT id_empleado, anio, Sum(ptu) ptu, Sum(ptu_exento) ptu_exento, Sum(ptu_grabable) ptu_grabable,
        Sum(total_percepcion) total_percepcion, Sum(isr) isr, Sum(total_deduccion) total_deduccion,
        Sum(total_neto) total_neto
      FROM public.nomina_ptu
      WHERE anio = 2019 AND id_empresa = 2
      GROUP BY id_empleado, anio
    ")->result();

    foreach ($data as $key => $value) {
      $this->empleado[$value->id_empleado] = $value;
    }

    return $this;
  }

  public function ajustePtu2019($empleados)
  {
    foreach ($empleados as $key => $empleado) {
      $empleado->nomina->deducciones['isr']['total']           = abs(number_format($empleado->nomina->deducciones['isr']['total'] - $this->empleado[$empleado->id]->isr, 2, '.', ''));
      $empleado->nomina->deducciones['isr']['ImporteExcento']  = abs(number_format($empleado->nomina->deducciones['isr']['ImporteExcento'] - $this->empleado[$empleado->id]->isr, 2, '.', ''));

      $empleado->nomina->percepciones['ptu']['total']          = abs(number_format($empleado->nomina->percepciones['ptu']['total'] - $this->empleado[$empleado->id]->ptu, 2, '.', ''));
      $empleado->nomina->percepciones['ptu']['ImporteExcento'] = abs(number_format($empleado->nomina->percepciones['ptu']['ImporteExcento'] - $this->empleado[$empleado->id]->ptu_exento, 2, '.', ''));
      $empleado->nomina->percepciones['ptu']['ImporteGravado'] = abs(number_format($empleado->nomina->percepciones['ptu']['ImporteGravado'] - $this->empleado[$empleado->id]->ptu_grabable, 2, '.', ''));

      $empleado->nomina->percepcionesTotales['TotalSueldos'] = abs(number_format($empleado->nomina->percepcionesTotales['TotalSueldos'] - $this->empleado[$empleado->id]->ptu, 2, '.', ''));
      $empleado->nomina->percepcionesTotales['TotalExento'] = abs(number_format($empleado->nomina->percepcionesTotales['TotalExento'] - $this->empleado[$empleado->id]->ptu_exento, 2, '.', ''));
      $empleado->nomina->percepcionesTotales['TotalGravado'] = abs(number_format($empleado->nomina->percepcionesTotales['TotalGravado'] - $this->empleado[$empleado->id]->ptu_grabable, 2, '.', ''));

      $empleado->nomina->deduccionesTotales['TotalImpuestosRetenidos'] = abs(number_format($empleado->nomina->deduccionesTotales['TotalImpuestosRetenidos'] - $this->empleado[$empleado->id]->isr, 2, '.', ''));
      $empleado->nomina->TotalPercepciones = abs(number_format($empleado->nomina->TotalPercepciones - $this->empleado[$empleado->id]->ptu, 2, '.', ''));
      $empleado->nomina->TotalDeducciones = abs(number_format($empleado->nomina->TotalDeducciones - $this->empleado[$empleado->id]->isr, 2, '.', ''));
      $empleado->nomina->subtotal = abs(number_format($empleado->nomina->subtotal - $this->empleado[$empleado->id]->ptu, 2, '.', ''));
      $empleado->nomina->descuento = abs(number_format($empleado->nomina->descuento - $this->empleado[$empleado->id]->isr, 2, '.', ''));
      $empleado->nomina->isr = abs(number_format($empleado->nomina->isr - $this->empleado[$empleado->id]->isr, 2, '.', ''));
      // $empleado->ptu_empleado_dias
      // $empleado->ptu_empleado_percepciones
    }
  }

}
/* End of file nomina_fiscal_model.php */
/* Location: ./application/models/nomina_fiscal_model.php */