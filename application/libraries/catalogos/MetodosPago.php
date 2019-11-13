<?php
// namespace Catalogos;
// use Illuminate\Support\Collection;

class MetodosPago {
  use Catalogos;

  protected $collectionMetodosPagos;

  private $metodosPagosOld = [];

  private $metodosPagos = [
    'PUE'  => ['key' => 'PUE', 'value' => 'Pago en una sola exhibición'],
    'PPD'  => ['key' => 'PPD', 'value' => 'Pago en parcialidades o diferido'],
    // 'PIP'  => ['key' => 'PIP', 'value' => 'Pago inicial y parcialidades'],
  ];

  /**
   * Constructor.
   *
   * @param  Illuminate\Support\Collection $collection
   * @return void
   */
  function __construct()
  {
    $this->collectionMetodosPagos = new Collection($this->metodosPagos);
  }

  public function withTrashed()
  {
    $this->collectionMetodosPagos = $this->collectionMetodosPagos->merge($this->metodosPagosOld);
    return $this;
  }

  /**
   * Obtiene la coleccion de los metodos de pago
   *
   * @return Illuminate\Support\Collection
   */
  public function get()
  {
    return $this->collectionMetodosPagos;
  }

  /**
   * Filtra los metodos de pago
   * @param  Request $request
   * @return Illuminate\Support\Collection
   */
  public function search($key)
  {
    $metodosPago = $this->collectionMetodosPagos->get($key);
    // $metodosPago = $this->collectionMetodosPagos;
    // if ($this->clTxt($request->input('term')) != '') {
    //   $metodosPago = $metodosPago->filter(function($item, $key) use ($request) {
    //     return strpos($this->clTxt($item['value']), $this->clTxt($request->term)) !== false;
    //   });
    // }
    return $metodosPago;
  }

}
