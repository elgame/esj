<?php

include APPPATH."libraries/PDFMerger/PDFMerger.php";

class MyMergePdf extends PDFMerger {
	/**
	 * P:Carta Vertical, L:Carta Horizontal, lP:Legal vertical, lL:Legal Horizontal
	 * @param unknown_type $orientation
	 * @param unknown_type $unit
	 * @param unknown_type $size
	 */
	function __construct(){
		parent::__construct();
	}

}


?>