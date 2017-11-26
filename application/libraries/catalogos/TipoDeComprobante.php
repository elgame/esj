<?php
// namespace Catalogos;
// use Illuminate\Support\Collection;

class TipoDeComprobante {
  use Catalogos;

  private $collectionTiposComprobantes;

  private $tiposComprobantesOld = [
  ];

  private $tiposComprobantes = [
    'I' => ['key' => 'I', 'value' => 'Ingreso', 'max' => 100000000],
    'E' => ['key' => 'E', 'value' => 'Egreso', 'max' => 100000000],
    'T' => ['key' => 'T', 'value' => 'Traslado', 'max' => 100000000],
    'N' => ['key' => 'N', 'value' => 'Nómina', 'max' => ['ns' => 400000, 'nds' => 2000000]],
    'P' => ['key' => 'P', 'value' => 'Pago', 'max' => 100000000],
  ];

  /**
   * Constructor.
   *
   * @param  Illuminate\Support\Collection $collection
   * @return void
   */
  function __construct()
  {
    $this->collectionTiposComprobantes = new Collection($this->tiposComprobantes);
  }

  public function withTrashed()
  {
    $this->collectionTiposComprobantes = $this->collectionTiposComprobantes->merge($this->tiposComprobantesOld);
    return $this;
  }

  /**
   * Obtiene la coleccion de las tiposComprobantes
   *
   * @return Illuminate\Support\Collection
   */
  public function get()
  {
    return $this->collectionTiposComprobantes;
  }

  /**
   * Filtra las tiposComprobantes para los CFDI
   * @param  Request $request
   * @return Illuminate\Support\Collection
   */
  public function search($key)
  {
    $tiposComprobantes = $this->collectionTiposComprobantes->get($key);
    // if ($this->clTxt($request->input('term')) != '') {
    //   $tiposComprobantes = $tiposComprobantes->filter(function($item, $key) use ($request) {
    //     return (strpos($this->clTxt($item['value']), $this->clTxt($request->term)) !== false ||
    //             strpos($this->clTxt($item['key']), $this->clTxt($request->term)) !== false);
    //   });
    // }
    return $tiposComprobantes;
  }

}