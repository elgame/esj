<?php

require_once APPPATH.'libraries/excel2/File.php';
require_once APPPATH.'libraries/excel2/Root.php';
include(APPPATH.'libraries/excel2/Writer.php');

class MYexcel {
	var $titulo1 = 'FRUTAS Y LEGUMBRES DE LA ACOSTA SUR DE JALISCO S.A. DE C.V.';
	var $titulo2 = '';
	var $titulo3 = '';
	var $titulo4 = '';

	private $formatsEx = array();
	var $workbook;

	public function __construct(){
		$this->workbook = new Spreadsheet_Excel_Writer();

		$this->formatsEx['format_title1'] =& $this->workbook->addFormat(array('Bold' => 1, 'Size' => 18, 'Align' => 'merge'));
		$this->formatsEx['format_title2'] =& $this->workbook->addFormat(array('Bold' => 1, 'Size' => 15, 'Align' => 'merge'));
		$this->formatsEx['format_title3'] =& $this->workbook->addFormat(array('Bold' => 1, 'Size' => 13, 'Align' => 'merge'));

		$this->formatsEx['format1'] =& $this->workbook->addFormat(array('Bold' => 1, 'Size' => 10, 'BgColor' => 'gray', 'FgColor' => 'white'));
		$this->formatsEx['format2'] =& $this->workbook->addFormat(array('Bold' => 1, 'Size' => 11, 'BgColor' => 'brown', 'FgColor' => 'white'));
		$this->formatsEx['format3'] =& $this->workbook->addFormat(array('Bold' => 1, 'Size' => 11, 'BgColor' => 'gray', 'FgColor' => 'white'));
		$this->formatsEx['format4'] =& $this->workbook->addFormat(array('Size' => 10));
		$this->formatsEx['format5'] =& $this->workbook->addFormat(array('Bold' => 1, 'Size' => 11, 'BgColor' => 'yellow'));
	}

	public function excelHead(&$worksheet, &$row, $col, $info){
		// $worksheet->insertBitmap($row, 0, APPPATH."images/logo.png",0,0,.5,.5);
		$worksheet->write($row, $col, $this->titulo1, $this->formatsEx['format_title1']);
		foreach($info as $key => $val){
			$row++;
			$worksheet->write($row, $col, $val[0], $this->formatsEx[$val[1]]);
		}
	}

	public function excelContent(&$worksheet, &$row, &$data, $campos){
		//cabecera
		foreach($campos['head'] as $kcamp => $camp){
			$worksheet->write($row, $kcamp, $camp, $this->formatsEx['format3']);
		}
		//Contenidos
		$row++;

		foreach($data as $key => $item){
			foreach($campos['conte'] as $kcamp => $camp){
				if($camp['sum'] != -1){
					$item->{$camp['name']} = String::float($item->{$camp['name']});
					$campos['conte'][$kcamp]['sum'] += $item->{$camp['name']};
				}else{
					$item->{$camp['name']} = utf8_decode($item->{$camp['name']});
				}
				if(isset($item->is_total_final)){
					if(!empty($item->{$camp['name']}))
						$worksheet->write($row, $kcamp, strip_tags($item->{$camp['name']}), $this->formatsEx['format5']);
				}
				else
					$worksheet->write($row, $kcamp, strip_tags($item->{$camp['name']}), $this->formatsEx[$camp['format']]);
			}

			$row++;
		}
		//Totales
		foreach($campos['conte'] as $kcamp => $camp){
			if($camp['sum'] != -1)
				$worksheet->write($row, $kcamp, $campos['conte'][$kcamp]['sum'], $this->formatsEx['format5']);
		}
	}
}


?>