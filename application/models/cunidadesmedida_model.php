<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class cunidadesmedida_model extends CI_Model {


  function __construct()
  {
    parent::__construct();
  }

  private $unidadesCE = [
    '1' => 'KILO',
    '2' => 'GRAMO',
    '3' => 'METRO LINEAL',
    '4' => 'METRO CUADRADO',
    '5' => 'METRO CUBICO',
    '6' => 'PIEZA',
    '7' => 'CABEZA',
    '8' => 'LITRO',
    '9' => 'PAR',
    '10' => 'KILOWATT',
    '11' => 'MILLAR',
    '12' => 'JUEGO',
    '13' => 'KILOWATT/HORA',
    '14' => 'TONELADA',
    '15' => 'BARRIL',
    '16' => 'GRAMO NETO',
    '17' => 'DECENAS',
    '18' => 'CIENTOS',
    '19' => 'DOCENAS',
    '20' => 'CAJA',
    '21' => 'BOTELLA',
    '99' => 'SERVICIOS'
  ];

  private $unidades = [
    'No aplica'  => 'No aplica',
    'Pieza'      => 'Pieza',
    'Servicio'   => 'Servicio',
    'Tonelada'   => 'Tonelada',
    'Kilogramo'  => 'Kilogramo',
    'Gramo'      => 'Gramo',
    'Litro'      => 'Litro',
    'Metro'      => 'Metro',
    'm2'         => 'm2',
    'm3'         => 'm3',
    'Centimetro' => 'Centimetro',
    'Caja'       => 'Caja',
    'Docena'     => 'Docena',
    'Saco'       => 'Saco',
  ];

  /**
   * Obtiene la coleccion de las unidades de Comercio Exterior
   *
   * @return Illuminate\Support\Collection
   */
  public function getCE()
  {
    return $this->unidadesCE;
  }

  /**
   * Obtiene la coleccion de las unidades de Comercio Exterior
   *
   * @return Illuminate\Support\Collection
   */
  public function get()
  {
    return $this->unidades;
  }

  public function getByKey($key)
  {
    $unidad = isset($this->unidades[$key]) ? $this->unidades[$key] : '';
    if (strlen($unidad) === 0) {
      $unidad = isset($this->unidadesCE[$key]) ? $this->unidadesCE[$key] : '';
    }

    return $unidad;
  }
}
