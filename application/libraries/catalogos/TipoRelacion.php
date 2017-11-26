<?php

class TipoRelacion {
  use Catalogos;

  protected $collectionTipoRelacion;

  private $tipoRelacionOld = [];

  private $tipoRelacion = [
    '01' => ['key' => '01', 'value' => 'Nota de crédito de los documentos relacionados'],
    '02' => ['key' => '02', 'value' => 'Nota de débito de los documentos relacionados'],
    '03' => ['key' => '03', 'value' => 'Devolución de mercancía sobre facturas o traslados previos'],
    '04' => ['key' => '04', 'value' => 'Sustitución de los CFDI previos'],
    '05' => ['key' => '05', 'value' => 'Traslados de mercancías facturados previamente'],
    '06' => ['key' => '06', 'value' => 'Factura generada por los traslados previos'],
    '07' => ['key' => '07', 'value' => 'CFDI por aplicación de anticipo'],
  ];

  /**
   * Constructor.
   *
   * @param  Illuminate\Support\Collection $collection
   * @return void
   */
  function __construct()
  {
    $this->collectionTipoRelacion = new Collection($this->tipoRelacion);
  }

  public function withTrashed()
  {
    $this->collectionTipoRelacion = $this->collectionTipoRelacion->merge($this->tipoRelacionOld);
    return $this;
  }

  /**
   * Obtiene la coleccion de las relaciones de cfdi
   *
   * @return Illuminate\Support\Collection
   */
  public function get()
  {
    return $this->collectionTipoRelacion;
  }

  /**
   * Filtra las relaciones de los cfdi
   * @param  Request $request
   * @return Illuminate\Support\Collection
   */
  public function search($key)
  {
    $tipo_rel = $this->collectionTipoRelacion->get($key);
    // $relacion = $this->collectionTipoRelacion;
    // if ($this->clTxt($request->input('term')) != '') {
    //   $relacion = $relacion->filter(function($item, $key) use ($request) {
    //     return strpos($this->clTxt($item['value']), $this->clTxt($request->term)) !== false;
    //   });
    // }
    return $tipo_rel;
  }

}
