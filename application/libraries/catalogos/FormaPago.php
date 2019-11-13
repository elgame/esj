<?php
// namespace Catalogos;
// use Illuminate\Support\Collection;

class FormaPago {
  use Catalogos;

  protected $collectionFormaPago;

  private $formaPagoOld = [
    'NA' => ['key' => 'NA', 'value' => 'No aplica']
  ];

  private $formaPago = [
    '01' => ['key' => '01', 'value' => 'Efectivo'],
    '02' => ['key' => '02', 'value' => 'Cheque nominativo'],
    '03' => ['key' => '03', 'value' => 'Transferencia electrónica de fondos'],
    '04' => ['key' => '04', 'value' => 'Tarjeta de crédito'],
    '05' => ['key' => '05', 'value' => 'Monedero electrónico'],
    '06' => ['key' => '06', 'value' => 'Dinero electrónico'],
    '08' => ['key' => '08', 'value' => 'Vales de despensa'],
    '12' => ['key' => '12', 'value' => 'Dación en pago'],
    '13' => ['key' => '13', 'value' => 'Pago por subrogación'],
    '14' => ['key' => '14', 'value' => 'Pago por consignación'],
    '15' => ['key' => '15', 'value' => 'Condonación'],
    '17' => ['key' => '17', 'value' => 'Compensación'],
    '23' => ['key' => '23', 'value' => 'Novación'],
    '24' => ['key' => '24', 'value' => 'Confusión'],
    '25' => ['key' => '25', 'value' => 'Remisión de deuda'],
    '26' => ['key' => '26', 'value' => 'Prescripción o caducidad'],
    '27' => ['key' => '27', 'value' => 'A satisfacción del acreedor'],
    '28' => ['key' => '28', 'value' => 'Tarjeta de débito'],
    '29' => ['key' => '29', 'value' => 'Tarjeta de servicios'],
    '30' => ['key' => '30', 'value' => 'Aplicación de anticipos'],
    '99' => ['key' => '99', 'value' => 'Por definir'],
  ];

  /**
   * Constructor.
   *
   * @param  Illuminate\Support\Collection $collection
   * @return void
   */
  function __construct()
  {
    $this->collectionFormaPago = new Collection($this->formaPago);
  }

  public function withTrashed()
  {
    $this->collectionFormaPago = $this->collectionFormaPago->merge($this->formaPagoOld);
    return $this;
  }

  /**
   * Obtiene la coleccion de los forma de pago
   *
   * @return Illuminate\Support\Collection
   */
  public function get()
  {
    return $this->collectionFormaPago;
  }

  /**
   * Filtra los forma de pago
   * @param  Request $request
   * @return Illuminate\Support\Collection
   */
  public function search($key)
  {
    $metodosPago = $this->collectionFormaPago->get($key);
    // if ($this->clTxt($request->input('term')) != '') {
    //   $metodosPago = $metodosPago->filter(function($item, $key) use ($request) {
    //     return strpos($this->clTxt($item['value']), $this->clTxt($request->term)) !== false;
    //   });
    // }
    return $metodosPago;
  }

}
