<?php
// namespace Catalogos;

trait Catalogos {

  protected function clTxt($value)
  {
    $value = str_replace(['Á','É','Í','Ó','Ú','á','é','í','ó','ú', "'"], ['A','E','I','O','U','a','e','i','o','u', "\'"], $value);
    $value = mb_strtolower(trim($value), 'UTF-8');
    return $value;
  }

}
