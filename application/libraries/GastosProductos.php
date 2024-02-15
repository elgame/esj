<?php

class GastosProductos {

  /**
   * c: certificados
   * ct: comisiones terceros
   * g: gastos generales
   * @var [type]
   */
  public static $gastosProductos = [
    49 => 'c',
    50 => 'g',
    51 => 'c',
    52 => 'c',
    53 => 'c',
    236 => 'g',
    237 => 'g',
    238 => 'ct',
    239 => 'c',
    1299 => 'g',
    1601 => 'ct',
    1602 => 'g',
    1603 => 'c',
    1610 => 'g',
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

  public static function getCerts($rkeys = true) {
    self::conf();

    $keys = array_filter(self::$gastosProductos, function ($itm) {
      return ($itm === 'c');
    });

    return ($rkeys? array_keys($keys) : $keys);
  }

  public static function getGastos($rkeys = true) {
    self::conf();

    $keys = array_filter(self::$gastosProductos, function ($itm) {
      return ($itm === 'g');
    });

    return ($rkeys? array_keys($keys) : $keys);
  }

  public static function getComTer($rkeys = true) {
    self::conf();

    $keys = array_filter(self::$gastosProductos, function ($itm) {
      return ($itm === 'ct');
    });

    return ($rkeys? array_keys($keys) : $keys);
  }

}

