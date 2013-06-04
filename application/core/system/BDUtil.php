<?php

class BDUtil{
	
	/**
	 * Genera Id unicos para la bd
	 */
	public static function getId(){
		return uniqid('l', true);
	}
	
	/**
	 * Ayuda a paginar resultados
	 * @param unknown_type $query
	 * @param unknown_type $params
	 * @param unknown_type $total_rows
	 */
	public static function pagination($query, &$params, $total_rows=false){
		$CI =& get_instance();
		$res = $CI->db->query($query);
		
		$num_pag = ceil($res->num_rows()/$params['result_items_per_page']);
		
		$result_page = ($params['result_page']>0? $params['result_page']: 0);
		$pag = $result_page*$params['result_items_per_page'];
	
		if($total_rows)
			return array("query" => $query." LIMIT ".$params['result_items_per_page']." OFFSET ".$pag,
					"total_rows" => $res->num_rows(), 'resultset' => $res);
		else
			return $query." LIMIT ".$params['result_items_per_page']." OFFSET ".$pag;
	}
}
?>