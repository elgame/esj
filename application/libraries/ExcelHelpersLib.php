<?php

require_once APPPATH.'libraries/PHPExcel/PHPExcel/IOFactory.php';

class ExcelHelpersLib {

  public function excelToArray($filePath, $header=true){
    //Create excel reader after determining the file type
    $inputFileName = $filePath;
    /**  Identify the type of $inputFileName  **/
    $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
    /**  Create a new Reader of the type that has been identified  **/
    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
    /** Set read type to read cell data onl **/
    $objReader->setReadDataOnly(true);
    /**  Load $inputFileName to a PHPExcel Object  **/
    $objPHPExcel = $objReader->load($inputFileName);
    //Get worksheet and built array with first row as header
    $objWorksheet = $objPHPExcel->getActiveSheet();

    //excel with first row header, use header as key
    if($header){
        $highestRow = $objWorksheet->getHighestRow();
        $highestColumn = $objWorksheet->getHighestColumn();
        $headingsArray = $objWorksheet->rangeToArray('A1:'.$highestColumn.'1',null, true, true, true);
        $headingsArray = $headingsArray[1];
        foreach ($headingsArray as $key => $value) {
          $headingsArray[$key] = trim($value);
        }

        $r = -1;
        $namedDataArray = array();
        for ($row = 2; $row <= $highestRow; ++$row) {
            $dataRow = $objWorksheet->rangeToArray('A'.$row.':'.$highestColumn.$row,null, true, true, true);
            if ((isset($dataRow[$row]['A'])) && ($dataRow[$row]['A'] > '')) {
                ++$r;
                foreach($headingsArray as $columnKey => $columnHeading) {
                    $namedDataArray[$r][$columnHeading] = $dataRow[$row][$columnKey];
                }
            }
        }
    } else{
        //excel sheet with no header
        $namedDataArray = $objWorksheet->toArray(null,true,true,true);
    }

    return $namedDataArray;
  }

  public function excelToArrayAsis($filePath, $header=true){
    //Create excel reader after determining the file type
    $inputFileName = $filePath;
    /**  Identify the type of $inputFileName  **/
    $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
    /**  Create a new Reader of the type that has been identified  **/
    $objReader = PHPExcel_IOFactory::createReader($inputFileType);
    /** Set read type to read cell data onl **/
    $objReader->setReadDataOnly(true);
    /**  Load $inputFileName to a PHPExcel Object  **/
    $objPHPExcel = $objReader->load($inputFileName);
    //Get worksheet and built array with first row as header
    $objWorksheet = $objPHPExcel->getActiveSheet();

    //excel with first row header, use header as key
    if($header){
        $highestRow = $objWorksheet->getHighestRow();
        $highestColumn = $objWorksheet->getHighestColumn();
        $headingsArray = $objWorksheet->rangeToArray('A1:'.$highestColumn.'1',null, true, true, true);
        $headingsArray = $headingsArray[1];
        foreach ($headingsArray as $key => $value) {
          $headingsArray[$key] = trim($value);
        }

        $r = -1;
        $namedDataArray = array();
        for ($row = 2; $row <= $highestRow; ++$row) {
            $dataRow = $objWorksheet->rangeToArray('A'.$row.':'.$highestColumn.$row,null, true, true, true);
            if ((isset($dataRow[$row]['E'])) && ($dataRow[$row]['E'] != '')) {
                ++$r;
                foreach($headingsArray as $columnKey => $columnHeading) {
                    $namedDataArray[$r][$columnHeading] = $dataRow[$row][$columnKey];
                }
            }
        }
    } else{
        //excel sheet with no header
        $namedDataArray = $objWorksheet->toArray(null,true,true,true);
    }

    return $namedDataArray;
  }

  // 25/mar./2021 jue.
  public function formatFechaAsis($fecha)
  {
    $meses = [
      'ene.' => '01', 'feb.' => '02', 'mar.' => '03', 'abr.' => '04',
      'may.' => '05', 'jun.' => '06', 'jul.' => '07', 'ago.' => '08',
      'sep.' => '09', 'oct.' => '10', 'nov.' => '11', 'dic.' => '12',
    ];
    $fecha = explode(' ', $fecha);
    $fecha = explode('/', $fecha[0]);

    return "{$fecha[2]}-{$meses[$fecha[1]]}-{$fecha[0]}";
  }
}