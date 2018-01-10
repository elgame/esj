<?php

class MotivoTraslado {
  use Catalogos;

  protected $collectionMotivoTraslado;

  private $usoCfdiOld = [];

  private $motivoTraslado = [
    '01' => ['key' => '01', 'value' => 'Envío de mercancías facturadas con anterioridad '],
    '02' => ['key' => '02', 'value' => 'Reubicación de mercancías propias'],
    '03' => ['key' => '03', 'value' => 'Envío de mercancías objeto de contrato de consignación'],
    '04' => ['key' => '04', 'value' => 'Envío de mercancías para posterior enajenación'],
    '05' => ['key' => '05', 'value' => 'Envío de mercancías propiedad de terceros'],
    '99' => ['key' => '99', 'value' => 'Otros'],
  ];

  /**
   * Constructor.
   *
   * @param  Illuminate\Support\Collection $collection
   * @return void
   */
  function __construct()
  {
    $this->collectionMotivoTraslado = new Collection($this->motivoTraslado);
  }

  public function withTrashed()
  {
    $this->collectionMotivoTraslado = $this->collectionMotivoTraslado->merge($this->usoCfdiOld);
    return $this;
  }

  /**
   * Obtiene la colección de las Motivos traslados de CE
   * @param  [type] $tipo fisica|moral
   * @return [type]       [description]
   */
  public function get()
  {
    return $this->collectionMotivoTraslado;
  }

  public function getByKey($key)
  {
    $motivo = $this->collectionMotivoTraslado->get($key);

    return $motivo;
  }

  /**
   * Filtra los motivos traslados de CE
   * @param  Request $request
   * @return Illuminate\Support\Collection
   */
  public function search($key)
  {
    $motivo = $this->collectionMotivoTraslado->get($key);

    return $motivo;
  }

}
