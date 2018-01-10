<?php

class UnidadesMedida {
  use Catalogos;

  protected $collectionUnidadesCE;
  protected $collectionUnidades;

  private $unidadesCE = [
    '01' => ['key' => '01', 'value' => 'KILO'],
    '02' => ['key' => '02', 'value' => 'GRAMO'],
    '03' => ['key' => '03', 'value' => 'METRO LINEAL'],
    '04' => ['key' => '04', 'value' => 'METRO CUADRADO'],
    '05' => ['key' => '05', 'value' => 'METRO CUBICO'],
    '06' => ['key' => '06', 'value' => 'PIEZA'],
    '07' => ['key' => '07', 'value' => 'CABEZA'],
    '08' => ['key' => '08', 'value' => 'LITRO'],
    '09' => ['key' => '09', 'value' => 'PAR'],
    '10' => ['key' => '10', 'value' => 'KILOWATT'],
    '11' => ['key' => '11', 'value' => 'MILLAR'],
    '12' => ['key' => '12', 'value' => 'JUEGO'],
    '13' => ['key' => '13', 'value' => 'KILOWATT/HORA'],
    '14' => ['key' => '14', 'value' => 'TONELADA'],
    '15' => ['key' => '15', 'value' => 'BARRIL'],
    '16' => ['key' => '16', 'value' => 'GRAMO NETO'],
    '17' => ['key' => '17', 'value' => 'DECENAS'],
    '18' => ['key' => '18', 'value' => 'CIENTOS'],
    '19' => ['key' => '19', 'value' => 'DOCENAS'],
    '20' => ['key' => '20', 'value' => 'CAJA'],
    '21' => ['key' => '21', 'value' => 'BOTELLA'],
    '99' => ['key' => '99', 'value' => 'SERVICIOS'],
  ];

  private $unidades = [
    'No aplica'  => ['key' => 'No aplica', 'value' => 'No aplica'],
    'Pieza'      => ['key' => 'Pieza', 'value' => 'Pieza'],
    'Servicio'   => ['key' => 'Servicio', 'value' => 'Servicio'],
    'Tonelada'   => ['key' => 'Tonelada', 'value' => 'Tonelada'],
    'Kilogramo'  => ['key' => 'Kilogramo', 'value' => 'Kilogramo'],
    'Gramo'      => ['key' => 'Gramo', 'value' => 'Gramo'],
    'Litro'      => ['key' => 'Litro', 'value' => 'Litro'],
    'Metro'      => ['key' => 'Metro', 'value' => 'Metro'],
    'm2'         => ['key' => 'm2', 'value' => 'm2'],
    'm3'         => ['key' => 'm3', 'value' => 'm3'],
    'Centimetro' => ['key' => 'Centimetro', 'value' => 'Centimetro'],
    'Caja'       => ['key' => 'Caja', 'value' => 'Caja'],
    'Docena'     => ['key' => 'Docena', 'value' => 'Docena'],
    'Saco'       => ['key' => 'Saco', 'value' => 'Saco'],
    'Tmo'        => ['key' => 'Tmo', 'value' => 'Tmo'],
    'Cubeta'     => ['key' => 'Cubeta', 'value' => 'Cubeta'],
    'Galon'      => ['key' => 'Galon', 'value' => 'Galon'],
    'Rollo'      => ['key' => 'Rollo', 'value' => 'Rollo'],
    'Bote'       => ['key' => 'Bote', 'value' => 'Bote'],
    'Bolsa'      => ['key' => 'Bolsa', 'value' => 'Bolsa'],
  ];

  /**
   * Constructor.
   *
   * @param  Illuminate\Support\Collection $collection
   * @return void
   */
  function __construct()
  {
    $this->collectionUnidadesCE = new Collection($this->unidadesCE);
    $this->collectionUnidades   = new Collection($this->unidades);
  }

  /**
   * Obtiene la coleccion de las unidades de Comercio Exterior
   *
   * @return Illuminate\Support\Collection
   */
  public function getCE()
  {
    return $this->collectionUnidadesCE;
  }

  /**
   * Obtiene la coleccion de las unidades de Comercio Exterior
   *
   * @return Illuminate\Support\Collection
   */
  public function get()
  {
    return $this->collectionUnidades;
  }

  public function getByKey($key)
  {
    $unidad = $this->collectionUnidades->get($key);
    if (is_null($unidad)) {
      $unidad = $this->collectionUnidadesCE->get($key);
    }

    return $unidad;
  }
}
