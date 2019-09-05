<?php

class MyFiles {
  public static function microtime_float()
  {
      list($usec, $sec) = explode(" ", microtime());
      return ((float)$usec + (float)$sec);
  }

  public static function searchXmlEnlinea($path, $brfcProv, $bfolio='', $bfechaIni='', $bfechaFin=''){
    // $time_start = self::microtime_float();
    $files = array();

    $brfcProv = trim($brfcProv);
    $bfolio = (trim($bfolio) !==''? 'folio="'.trim($bfolio): '');
    if ($bfechaIni != '') {
      $bfechaIni = new DateTime($bfechaIni);
    }
    if ($bfechaFin != '') {
      $bfechaFin = new DateTime($bfechaFin);
    }

    $pathanio = '/'.date("Y").'/'.date("m");
    if ($bfechaFin != '') {
      $pathanio = '/'.$bfechaFin->format("Y").'/'.$bfechaFin->format("m");
    } elseif ($bfechaIni != '') {
      $pathanio = '/'.$bfechaIni->format("Y").'/'.$bfechaFin->format("m");
    }

    if (is_dir($path.$pathanio)) {
      $dir = new DirectoryIterator($path.$pathanio);
    } else {
      return "No se encontro el direcctorio de busqueda ({$path}{$pathanio}).";
    }

    $totalFiles = 0;
    $cont = 0;
    $find = false;
    foreach ($dir as $file){
      if (!$file->isDot()){
        $content = file_get_contents($file->getPathname());

        $find = false;
        if ($brfcProv != '' && stripos($content, $brfcProv) !== false) {
          $find = true;
        }
        if ($bfolio != '' && $find === true) {
          $find = false;
          if (stripos($content, $bfolio) !== false) {
            $find = true;
          }
        }

        if ($find) {
          $matches = [];

          libxml_use_internal_errors(true);
          $xml = simplexml_load_string(str_replace(array('cfdi:', 'tfd:'), '', $content));

          if (isset($xml->Complemento->TimbreFiscalDigital['UUID'])) {
            $uuid = (string)$xml->Complemento->TimbreFiscalDigital['UUID'];
            $fecha = substr((((string)$xml['fecha'])<>''? (string)$xml['fecha']: (string)$xml['Fecha']), 0, 10);
            $folio = (($xml['serie'].$xml['folio'])<>''? $xml['serie'].$xml['folio']: $xml['Serie'].$xml['Folio']);
            $folioInt = (($xml['folio'])<>''? $xml['folio']: $xml['Folio']);
            $noCertificado = ((string)$xml->Complemento->TimbreFiscalDigital['noCertificadoSAT']<>''?
              (string)$xml->Complemento->TimbreFiscalDigital['noCertificadoSAT']:
              (string)$xml->Complemento->TimbreFiscalDigital['NoCertificadoSAT']);
            $total = (((string)$xml['total'])<>''? (string)$xml['total']: (string)$xml['Total']);

            $find = false;
            $fechaVal = new DateTime(substr($fecha, 0, 10));
            if ($bfechaIni != '' && $bfechaFin != '') {
              $bfechaIni = $bfechaIni;
              $bfechaFin = $bfechaFin;
              if ($fechaVal >= $bfechaIni && $fechaVal <= $bfechaFin) {
                $find = true;
              }
            } elseif ($bfechaIni != '') {
              $bfechaIni = $bfechaIni;
              if ($fechaVal >= $bfechaIni) {
                $find = true;
              }
            } elseif ($bfechaFin != '') {
              $bfechaFin = $bfechaFin;
              if ($fechaVal >= $bfechaFin) {
                $find = true;
              }
            } else
              $find = true;

            if ($find) {
              $files[strtotime($fecha)+intval($folioInt)] = [
                'name'          => $file->getBasename(),
                'rfc'           => $brfcProv,
                'fecha'         => $fecha,
                'folio'         => $folio,
                'total'         => $total,
                'uuid'          => $uuid,
                'noCertificado' => $noCertificado
              ];
            }
          }
        }
      }
    }

    // $time_end = self::microtime_float();
    // $time = $time_end - $time_start;
    // echo "<pre>";
    // var_dump($time);
    // echo "</pre>";exit;

    krsort($files);

    return $files;
  }

}
?>