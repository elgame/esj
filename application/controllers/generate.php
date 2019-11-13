<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Generate extends MY_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see http://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		include APPPATH.'libraries/PHPExcel/PHPExcel/IOFactory.php';

		$inputFileName = FCPATH.'catalogo.xlsx';
		$objReader = PHPExcel_IOFactory::createReader('Excel2007');
		$objReader->setReadDataOnly(true);
		$objPHPExcel = $objReader->load($inputFileName);
		$objWorksheet = $objPHPExcel->getActiveSheet();

		$highestRow = $objWorksheet->getHighestRow();
		$highestColumn = $objWorksheet->getHighestColumn();
		$highestColumnIndex = PHPExcel_Cell::columnIndexFromString($highestColumn);
		$arbol = array();
		$indice = array();
		$idcount = 2;
		$cols = array('nivel', 'codigo', 'nombre', 'descripcion', 'ubicacion', 'otro_dato', 'tipo', 'afectable');
		for ($row = 1; $row <= $highestRow; ++$row) {
			$rows = array();
		  for ($col = 0; $col <= $highestColumnIndex; ++$col) {
		  	if (isset($cols[$col])) {
		    	$rows[$cols[$col]] = str_replace("'", '', trim($objWorksheet->getCellByColumnAndRow($col, $row)->getValue()) );
		  	}
		  }
		  $rows['nivel'] = intval($rows['nivel']);
		  $rows['afectable'] = $rows['afectable']!=''? 't': 'f';
		  if (count($indice) == 0 || $rows['nivel'] == 1) {
		  	$rows['id_padre'] = 'NULL';
		  	$indice = array();
		  } elseif($rows['nivel'] > count($indice)) {
		  	$rows['id_padre'] = $indice[count($indice)-1];
		  } elseif ($rows['nivel'] == count($indice)) {
		  	array_pop($indice);
		  	$rows['id_padre'] = $indice[count($indice)-1];
		  } else {
		  	$quitar = count($indice) - $rows['nivel'] + 1;
		  	for ($i=1; $i <= $quitar; $i++) {
		  		array_pop($indice);
		  	}
		  	$rows['id_padre'] = $indice[count($indice)-1];
		  }

		  if ($rows['descripcion'] == '') {
		  	$rows['descripcion'] = $rows['nombre'];
		  }elseif ($rows['nombre'] == '') {
		  	$rows['nombre'] = $rows['descripcion'];
		  }

		  // echo "<pre>";
		  //   var_dump($rows);
		  // echo "</pre>";

		  // $this->db->insert('otros.cat_codigos', $rows);
		  // $idcount = $this->db->insert_id();
		  echo "INSERT INTO otros.cat_codigos (codigo, nombre, descripcion, ubicacion, otro_dato, id_padre, nivel, tipo, afectable) VALUES ('{$rows['codigo']}', '{$rows['nombre']}', '{$rows['descripcion']}', '{$rows['ubicacion']}', '{$rows['otro_dato']}', {$rows['id_padre']}, {$rows['nivel']}, '{$rows['tipo']}', '{$rows['afectable']}');\n";
		  array_push($indice, $idcount);

		  // if ($row > 400) {
		  // 	exit;
		  // }

		  ++$idcount;
		}
	}
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */