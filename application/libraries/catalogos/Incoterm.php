<?php


class Incoterm {
  use Catalogos;

  protected $collectionIncoterm;

  private $incotermOld = [];

  private $incoterm = [
    'CFR' => ['key' => 'CFR', 'value' => 'COSTE Y FLETE (PUERTO DE DESTINO CONVENIDO)'],
    'CIF' => ['key' => 'CIF', 'value' => 'COSTE, SEGURO Y FLETE (PUERTO DE DESTINO CONVENIDO)'],
    'CPT' => ['key' => 'CPT', 'value' => 'TRANSPORTE PAGADO HASTA (EL LUGAR DE DESTINO CONVENIDO)'],
    'CIP' => ['key' => 'CIP', 'value' => 'TRANSPORTE Y SEGURO PAGADOS HASTA (LUGAR DE DESTINO CONVENIDO)'],
    'DAF' => ['key' => 'DAF', 'value' => 'ENTREGADA EN FRONTERA (LUGAR CONVENIDO)'],
    'DAP' => ['key' => 'DAP', 'value' => 'ENTREGADA EN LUGAR'],
    'DAT' => ['key' => 'DAT', 'value' => 'ENTREGADA EN TERMINAL'],
    'DES' => ['key' => 'DES', 'value' => 'ENTREGADA SOBRE BUQUE (PUERTO DE DESTINO CONVENIDO)'],
    'DEQ' => ['key' => 'DEQ', 'value' => 'ENTREGADA EN MUELLE (PUERTO DE DESTINO CONVENIDO)'],
    'DDU' => ['key' => 'DDU', 'value' => 'ENTREGADA DERECHOS NO PAGADOS (LUGAR DE DESTINO CONVENIDO)'],
    'DDP' => ['key' => 'DDP', 'value' => 'ENTREGADA DERECHOS PAGADOS (LUGAR DE DESTINO CONVENIDO)'],
    'EXW' => ['key' => 'EXW', 'value' => 'EN FABRICA (LUGAR CONVENIDO)'],
    'FCA' => ['key' => 'FCA', 'value' => 'FRANCO TRANSPORTISTA (LUGAR DESIGNADO)'],
    'FAS' => ['key' => 'FAS', 'value' => 'FRANCO AL COSTADO DEL BUQUE (PUERTO DE CARGA CONVENIDO)'],
    'FOB' => ['key' => 'FOB', 'value' => 'FRANCO A BORDO (PUERTO DE CARGA CONVENIDO)'],
  ];

  /**
   * Constructor.
   *
   * @param  Illuminate\Support\Collection $collection
   * @return void
   */
  function __construct()
  {
    $this->collectionIncoterm = new Collection($this->incoterm);
  }

  public function withTrashed()
  {
    $this->collectionIncoterm = $this->collectionIncoterm->merge($this->incotermOld);
    return $this;
  }

  /**
   * Obtiene la colección de Incoterm de CE
   * @param  [type] $tipo fisica|moral
   * @return [type]       [description]
   */
  public function get()
  {
    return $this->collectionIncoterm;
  }

  public function getByKey($key)
  {
    $motivo = $this->collectionIncoterm->get($key);

    return $motivo;
  }

  /**
   * Filtra los incoterm de CE
   * @param  Request $request
   * @return Illuminate\Support\Collection
   */
  public function search($key)
  {
    $motivo = $this->collectionIncoterm->get($key);
    return $motivo;
  }

}
