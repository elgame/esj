<?php

class MyFiles {

  public static function searchXmlEnlinea($path, $rfcProv, $folio=''){
    $dir = new DirectoryIterator($path);
    $files = array();

    $rfcProv = trim($rfcProv);
    $folio = (trim($folio) !==''? 'folio="'.trim($folio): '');

    $totalFiles = 0;
    $cont = 0;
    $find = false;
    foreach ($dir as $file){
      if (!$file->isDot()){
        $content = file_get_contents($file->getPathname());

        $find = false;
        if ($rfcProv != '' && strpos($content, $rfcProv) !== false) {
          $find = true;
        }
        if ($folio != '' && $find === true) {
          $find = false;
          if (strpos($content, $folio) !== false) {
            $find = true;
          }
        }

        if ($find) {
          $matches = [];

          $uuid = '';
          preg_match('/TimbreFiscalDigital (.+) UUID="([A-Z0-9\-]){35,38}"/', $content, $matches);
          if (count($matches) > 0) {
            preg_match('/UUID="([A-Z0-9\-]){35,38}"/', $matches[0], $matches);
            if (count($matches) > 0) {
              $uuid = preg_replace('/(UUID=|")/', '', $matches[0]);
            }
          }

          $noCertificado = '';
          preg_match('/TimbreFiscalDigital (.+) noCertificadoSAT="([0-9]){18,22}"/', $content, $matches);
          if (count($matches) > 0) {
            preg_match('/noCertificadoSAT="([0-9]){18,22}"/', $matches[0], $matches);
            if (count($matches) > 0) {
              $noCertificado = preg_replace('/(noCertificadoSAT=|")/', '', $matches[0]);
            }
          }

          $files[$file->getMTime()] = [
            'name'          => $file->getBasename(),
            'rfc'           => $rfcProv,
            'uuid'          => $uuid,
            'noCertificado' => $noCertificado
          ];
        }
      }
    }

    // ksort($files);

    return $files;
  }

}
?>