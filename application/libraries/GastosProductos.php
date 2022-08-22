<?php

class GastosProductos {

  public static $gastosProductos = [49, 50, 51, 52, 53, 236, 237, 238, 239];

  public static function conf() {
    $CI =& get_instance();
    if ($CI->config->item('is_bodega') == 1) {
      self::$gastosProductos = [49, 50, 51, 52, 53, 236, 237, 238, 239, 188];
    }
  }

  public static function searchGastosProductos($val) {
    self::conf();

    $res = array_search($val, self::$gastosProductos);
    return ($res !== false);
  }

}

