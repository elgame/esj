<?php

class GastosProductos {

  /**
   * c: certificados
   * ct: comisiones terceros
   * g: gastos generales
   * @var [type]
   */
  public static $gastosProductos = [
    49 => ['tipo' => 'c', 'nombre' => 'SEGURO'],
    50 => ['tipo' => 'g', 'nombre' => 'SER TRANSPORTE'],
    51 => ['tipo' => 'c', 'nombre' => 'C FITOSANITARIO'],
    52 => ['tipo' => 'c', 'nombre' => 'C ORIGEN'],
    53 => ['tipo' => 'c', 'nombre' => 'SUP CARGAS'],
    236 => ['tipo' => 'g', 'nombre' => 'FLETES Y ACARREOS'],
    237 => ['tipo' => 'g', 'nombre' => 'GASTOS ADUANALES'],
    238 => ['tipo' => 'ct', 'nombre' => 'COMISION S/VTA'],
    239 => ['tipo' => 'c', 'nombre' => 'C ADICIONALES'],
    1299 => ['tipo' => 'g', 'nombre' => 'C FIN FLETE'],
    1601 => ['tipo' => 'ct', 'nombre' => 'COMISIONES 3RO'],
    1602 => ['tipo' => 'g', 'nombre' => 'DES DISTRIBUCION'],
    1603 => ['tipo' => 'c', 'nombre' => 'EXPE FITOSANITARIO'],
    1610 => ['tipo' => 'g', 'nombre' => 'MANIOBRA'],
  ];
  public static $gastosProductosKeys = [];

  public static function conf() {
    $CI =& get_instance();
    if ($CI->config->item('is_bodega') == 1) {
      self::$gastosProductos = [49, 50, 51, 52, 53, 236, 237, 238, 239, 188];
      self::$gastosProductosKeys = self::$gastosProductos;
    } else {
      self::$gastosProductosKeys = array_keys(self::$gastosProductos);
    }
  }

  public static function searchGastosProductos($val) {
    self::conf();

    $res = array_search($val, self::$gastosProductosKeys);
    return ($res !== false);
  }

  public static function getAll($rkeys = true) {
    self::conf();

    $keys = self::$gastosProductos;

    return ($rkeys? array_keys($keys) : $keys);
  }

  public static function getCerts($rkeys = true) {
    self::conf();

    $keys = array_filter(self::$gastosProductos, function ($itm) {
      return ($itm['tipo'] === 'c');
    });

    return ($rkeys? array_keys($keys) : $keys);
  }

  public static function getGastos($rkeys = true) {
    self::conf();

    $keys = array_filter(self::$gastosProductos, function ($itm) {
      return ($itm['tipo'] === 'g');
    });

    return ($rkeys? array_keys($keys) : $keys);
  }

  public static function getComTer($rkeys = true) {
    self::conf();

    $keys = array_filter(self::$gastosProductos, function ($itm) {
      return ($itm['tipo'] === 'ct');
    });

    return ($rkeys? array_keys($keys) : $keys);
  }

}

