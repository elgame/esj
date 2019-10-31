<?php

class GastosProductos {

  public static $gastosProductos = [49, 50, 51, 52, 53, 236, 237, 238, 239];

  public static function searchGastosProductos($val) {
    $res = array_search($val, self::$gastosProductos);
    return ($res !== false);
  }

}

