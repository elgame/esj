<?php

class String{

	/**
	 * Da formato numerico a una cadena
	 * @param unknown_type $number
	 * @param unknown_type $decimales
	 * @param unknown_type $sigini
	 */
	public static function formatoNumero($number, $decimales=2, $sigini='$'){
		return $sigini.number_format($number, $decimales, '.', ',');
	}
	/**
	 * Limpia el formatoNumero y lo deja en flotante o entero
	 * @param unknown_type $number
	 * @param unknown_type $decimales
	 */
	public static function float($number, $int=false, $decimales=2){
		$decimales = $int? 0: $decimales;
		$number = $number==''? '0': $number;
		$number = str_replace(array('$', ','), '', $number);
		return number_format($number, $decimales, '.', '');
	}

	/**
	 * Obtiene las variables get y las prepara para los links
	 * @param unknown_type $quit
	 */
	public static function getVarsLink($quit=array()){
		$vars = '';
		foreach($_GET as $key => $val){
			if(array_search($key, $quit) === false)
				$vars .= '&'.$key.'='.$val;
		}

		return substr($vars, 1);
	}

	/**
	 * Valida si una cadena es una fecha valida
	 * y regresa en formato correcto
	 */
	public static function isValidDate($str_fecha, $format='Y-m-d'){
		$fecha = explode('-', $str_fecha);
		if(count($fecha) != 3 && strlen($str_fecha) != 10)
			return false;
		return true;
	}

	/**
	 * Limpia una cadena
	 * @param $txt. Texto a ser limpiado
	 * @return String. Texto limpio
	 */
	public static function limpiarTexto($txt, $remove_q=true){
		$ci =& get_instance();
		if(is_array($txt)){
			foreach($txt as $key => $item){
				$txt[$key] = addslashes(self::quitComillas(strip_tags(stripslashes(trim($item)))));
				$txt[$key] = $ci->security->xss_clean(preg_replace("/select (.+) from|update (.+) set|delete from|drop table|where (.+)=(.+)/","", $txt[$key]));
			}
			return $txt;
		}else{
			$txt = addslashes(self::quitComillas(strip_tags(stripslashes(trim($txt)))));
			$txt = $ci->security->xss_clean(preg_replace("/select (.+) from|update (.+) set|delete from|drop table|where (.+)=(.+)/","", $txt));
			return $txt;
		}
	}


	/**
	 * @param $txt. Texto al que se le eliminarÃ¡n las comillas
	 * @return String. Texto sin comillas
	 */
	public static function quitComillas($txt){
		return str_replace("'","’", str_replace('"','”',$txt));
	}

	/**
	 * Crear textos con solo caracteres Ascii, sin espacion
	 * para usar en urls
	 * @param unknown_type $str
	 * @param unknown_type $delimiter
	 * @return mixed
	 */
	public static function toAscii($str, $delimiter='-') {
		$clean = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
		$clean = preg_replace("/[^a-zA-Z0-9\/_|+ -]/", '', $clean);
		$clean = strtolower(trim($clean, '-'));
		$clean = preg_replace("/[\/_|+ -]+/", $delimiter, $clean);

		return $clean;
	}



	/*!
	 @function num2letras ()
	@abstract Dado un n?mero lo devuelve escrito.
	@param $num number - N?mero a convertir.
	@param $fem bool - Forma femenina (true) o no (false).
	@param $dec bool - Con decimales (true) o no (false).
	@param $moneda string - tipo de moneda (M.N.).
	@result string - Devuelve el n?mero escrito en letra.

	*/
	public static function num2letras($num, $moneda='M.N.', $fem = false, $dec = true) {
		$matuni[2]  = "dos";
		$matuni[3]  = "tres";
		$matuni[4]  = "cuatro";
		$matuni[5]  = "cinco";
		$matuni[6]  = "seis";
		$matuni[7]  = "siete";
		$matuni[8]  = "ocho";
		$matuni[9]  = "nueve";
		$matuni[10] = "diez";
		$matuni[11] = "once";
		$matuni[12] = "doce";
		$matuni[13] = "trece";
		$matuni[14] = "catorce";
		$matuni[15] = "quince";
		$matuni[16] = "dieciseis";
		$matuni[17] = "diecisiete";
		$matuni[18] = "dieciocho";
		$matuni[19] = "diecinueve";
		$matuni[20] = "veinte";
		$matunisub[2] = "dos";
		$matunisub[3] = "tres";
		$matunisub[4] = "cuatro";
		$matunisub[5] = "quin";
		$matunisub[6] = "seis";
		$matunisub[7] = "sete";
		$matunisub[8] = "ocho";
		$matunisub[9] = "nove";

		$matdec[2] = "veint";
		$matdec[3] = "treinta";
		$matdec[4] = "cuarenta";
		$matdec[5] = "cincuenta";
		$matdec[6] = "sesenta";
		$matdec[7] = "setenta";
		$matdec[8] = "ochenta";
		$matdec[9] = "noventa";
		$matsub[3]  = 'mill';
		$matsub[5]  = 'bill';
		$matsub[7]  = 'mill';
		$matsub[9]  = 'trill';
		$matsub[11] = 'mill';
		$matsub[13] = 'bill';
		$matsub[15] = 'mill';
		$matmil[4]  = 'millones';
		$matmil[6]  = 'billones';
		$matmil[7]  = 'de billones';
		$matmil[8]  = 'millones de billones';
		$matmil[10] = 'trillones';
		$matmil[11] = 'de trillones';
		$matmil[12] = 'millones de trillones';
		$matmil[13] = 'de trillones';
		$matmil[14] = 'billones de trillones';
		$matmil[15] = 'de billones de trillones';
		$matmil[16] = 'millones de billones de trillones';

		//Zi hack
		$float=explode('.',$num);
		$num=$float[0];

		if(!isset($float[1]))
			$float[1] = '00';

		$num = trim((string)@$num);
		if ($num[0] == '-') {
			$neg = 'menos ';
			$num = substr($num, 1);
		}else
			$neg = '';
		while ($num[0] == '0') $num = substr($num, 1);
		if ($num[0] < '1' or $num[0] > 9) $num = '0' . $num;
		$zeros = true;
		$punt = false;
		$ent = '';
		$fra = '';
		for ($c = 0; $c < strlen($num); $c++) {
			$n = $num[$c];
			if (! (strpos(".,'''", $n) === false)) {
				if ($punt) break;
				else{
					$punt = true;
					continue;
				}

			}elseif (! (strpos('0123456789', $n) === false)) {
				if ($punt) {
					if ($n != '0') $zeros = false;
					$fra .= $n;
				}else

					$ent .= $n;
			}else

				break;

		}
		$ent = '     ' . $ent;
		if ($dec and $fra and ! $zeros) {
			$fin = ' coma';
			for ($n = 0; $n < strlen($fra); $n++) {
				if (($s = $fra[$n]) == '0')
					$fin .= ' cero';
				elseif ($s == '1')
				$fin .= $fem ? ' una' : ' un';
				else
					$fin .= ' ' . $matuni[$s];
			}
		}else
			$fin = '';
		if ((int)$ent === 0) return 'Cero ' . $fin;
		$tex = '';
		$sub = 0;
		$mils = 0;
		$neutro = false;
		while ( ($num = substr($ent, -3)) != '   ') {
			$ent = substr($ent, 0, -3);
			if (++$sub < 3 and $fem) {
				$matuni[1] = 'una';
				$subcent = 'as';
			}else{
				$matuni[1] = $neutro ? 'un' : 'uno';
				$subcent = 'os';
			}
			$t = '';
			$n2 = substr($num, 1);
			if ($n2 == '00') {
			}elseif ($n2 < 21)
			$t = ' ' . $matuni[(int)$n2];
			elseif ($n2 < 30) {
				$n3 = $num[2];
				if ($n3 != 0) $t = 'i' . $matuni[$n3];
				$n2 = $num[1];
				$t = ' ' . $matdec[$n2] . $t;
			}else{
				$n3 = $num[2];
				if ($n3 != 0) $t = ' y ' . $matuni[$n3];
				$n2 = $num[1];
				$t = ' ' . $matdec[$n2] . $t;
			}
			$n = $num[0];
			if ($n == 1) {
				$t = ' ciento' . $t;
			}elseif ($n == 5){
				$t = ' ' . $matunisub[$n] . 'ient' . $subcent . $t;
			}elseif ($n != 0){
				$t = ' ' . $matunisub[$n] . 'cient' . $subcent . $t;
			}
			if ($sub == 1) {
			}elseif (! isset($matsub[$sub])) {
				if ($num == 1) {
					$t = ' mil';
				}elseif ($num > 1){
					$t .= ' mil';
				}
			}elseif ($num == 1) {
				$t .= ' ' . $matsub[$sub] . '?n';
			}elseif ($num > 1){
				$t .= ' ' . $matsub[$sub] . 'ones';
			}
			if ($num == '000') $mils ++;
			elseif ($mils != 0) {
				if (isset($matmil[$sub])) $t .= ' ' . $matmil[$sub];
				$mils = 0;
			}
			$neutro = true;
			$tex = $t . $tex;
		}
		$tex = $neg . substr($tex, 1) . $fin;
		//Zi hack --> return ucfirst($tex);
		
		$tipo_moneda = 'pesos';
		switch ($moneda) {
			case 'USD':
				$tipo_moneda = 'dolares';
				break;
		}

		$end_num=ucfirst($tex).' '.$tipo_moneda.' '.$float[1].'/100 '.$moneda;
		return $end_num;
	}


	/**** FUNCIONES DE FECHA ****/
	/**
	 * Le suma ndias a una fecha dada regresandola en el formato que sea especificado
	 * @param $fecha:string
	 *				Corresponde a la fecha a la que se le sumaran los d�as
	 * @param $ndias:integer
	 *				Es el numero de dias que se le sumaran a la fecha
	 * @param $formato:string (opcional)
	 *				Es el formato en el que sera devuelto la fecha resultante (default Y-m-d)
	 * @return date
	 */
	public static function suma_fechas($fecha,$ndias,$formato = "Y-m-d"){
		if($ndias>=0)
			return date($formato, strtotime($fecha." + ".$ndias." days"));
		else
			return date($formato, strtotime($fecha." ".$ndias." day"));
	}

	// Calcula el numero de dias entre dos fechas.
	// Da igual el formato de las fechas (dd-mm-aaaa o aaaa-mm-dd),
	// pero el caracter separador debe ser un guión.
	public static function diasEntreFechas($fechainicio, $fechafin){
	    return ((strtotime($fechafin)-strtotime($fechainicio))/86400);
	}

	/**
	 * mes()
	 *
	 * Devuelve la cadena de texto asociada al número de mes
	 *
	 * @param   int mes (entero entre 1 y 12)
	 * @return  string  nombre_del_mes
	 */
	public static function mes($num){
		/**
		 * Creamos un array con los meses disponibles.
		 * Agregamos un valor cualquiera al comienzo del array para que los números coincidan
		 * con el valor tradicional del mes. El valor "Error" resultará útil
		 **/
		$meses = array('Error', 'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
				'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre');

		/**
		 * Si el número ingresado está entre 1 y 12 asignar la parte entera.
		 * De lo contrario asignar "0"
		 **/
		$num_limpio = $num >= 1 && $num <= 12 ? intval($num) : 0;
		return $meses[$num_limpio];
	}

	/**
	 * fechaATexto()
	 *
	 * Devuelve la cadena de texto asociada a la fecha ingresada
	 *
	 * @param   string fecha (cadena con formato XXXX-XX-XX)
	 * @param   string formato (puede tomar los valores 'l', 'u', 'c')
	 * @return  string  fecha_en_formato_texto
	 */
	public static function fechaATexto($fecha, $formato = 'c') {

		// Validamos que la cadena satisfaga el formato deseado y almacenamos las partes
		if (preg_match("/([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})/", $fecha, $partes)) {
			// $partes[0] contiene la cadena original
			// $partes[1] contiene el año
			// $partes[2] contiene el número de mes
			// $partes[3] contiene el número del día
			$mes = ' de ' . self::mes($partes[2]) . ' de '; // Corregido!
			if ($formato == 'u') {
				$mes = strtoupper($mes);
			} elseif ($formato == 'l') {
				$mes = strtolower($mes);
			}
			return $partes[3] . $mes . $partes[1];

		} else {
			// Si hubo problemas en la validación, devolvemos false
			return false;
		}
	}

	/**
	 * timestampATexto()
	 *
	 * Devuelve la cadena de texto asociada a la fecha ingresada
	 *
	 * @param   string timestamp (cadena con formato XXXX-XX-XX XX:XX:XX)
	 * @param   string formato (puede tomar los valores 'l', 'u', 'c')
	 * @return  string  fecha_en_formato_texto
	 */
	public static function timestampATexto($timestamp, $formato = 'c') {

		// Buscamos el espacio dentro de la cadena o salimos
		if (strpos($timestamp, " ") === false){
			return false;
		}

		// Dividimos la cadena en el espacio separador
		$timestamp = explode(" ", $timestamp);

		// Como la primera parte es una fecha, simplemente llamamos a self::fechaATexto()
		if (self::fechaATexto($timestamp[0])) {
			$conjuncion = ' a las ';
			if ($formato == 'u') {
				$conjuncion = strtoupper($conjuncion);
			}
			return self::fechaATexto($timestamp[0], $formato) . $conjuncion;
		}
	}




	public static function obtenerSemanasDelAnio($anio=0,$todas=false,$mes=0,$dias_defasados=false){
		$data = array();
		if(intval($anio)<=0 && $dias_defasados==false)
			$anio = date('Y');

		$data[0] = self::obtenerPrimeraSemanaDelAnio($anio,$dias_defasados);

		$pos = 0;
		while(
				(
						(($todas==false && strtotime($data[$pos]['fecha_final'])<strtotime(date('Y-m-d'))) && (strtotime($data[$pos]['fecha_inicio'])<=strtotime($anio."-12-31")))
						||
						($todas==true && (strtotime($data[$pos]['fecha_inicio'])<strtotime($anio."-12-31")))
				)
				&&
				($pos+1<52)
		){
			++$pos;
			$data[$pos]['fecha_inicio'] = self::suma_fechas($data[$pos-1]['fecha_inicio'],7);
			$data[$pos]['fecha_final'] = self::suma_fechas($data[$pos]['fecha_inicio'],6);
			$data[$pos]['anio'] = intval($data[$pos-1]['anio']);
			$data[$pos]['semana'] = $pos + 1;
		}
		if($mes<=0)
			return $data;
		else{
			$dataAux = array();
			foreach($data as $key => $item){
				$vec = explode('-', $item['fecha_inicio']);
				if(intval($vec[1])==$mes)
					$dataAux[] = $item;
			}
			return $dataAux;
		}
	}
	public static function obtenerPrimeraSemanaDelAnio($anio = 0, $dias_defasados=false){
		if(intval($anio)==0 && $dias_defasados==false)
			$anio = date('Y');

		$data = array();
		if($dias_defasados==false){
			$dia = 1;
			while(count($data)==0){
				$diaSemana = -1;
				$diaSemana = self::obtenerDiaSemana($anio."-01-0".$dia);
				if(($dias_defasados==false && $diaSemana==0) || ($dias_defasados==true && $diaSemana==5)){//0=lunes   6=domingo
					$data['fecha_inicio'] = $anio."-01-0".$dia;
					$data['fecha_final'] = self::suma_fechas($data['fecha_inicio'],6);
					$data['semana'] = 1;
					$data['anio'] = $anio;
				}
				++$dia;
			}
		}

		return $data;
	}
	public static function obtenerDiaSemana($fecha){
		$fecha=str_replace("/","-",$fecha);
		list($anio,$mes,$dia)=explode("-",$fecha);
		return (((mktime ( 0, 0, 0, $mes, $dia, $anio) - mktime ( 0, 0, 0, 7, 17, 2006))/(60*60*24))+700000) % 7;
	}

	public static function ultimoDia($anho,$mes){
	   if (((fmod($anho,4)==0) and (fmod($anho,100)!=0)) or (fmod($anho,400)==0)) {
	       $dias_febrero = 29;
	   } else {
	       $dias_febrero = 28;
	   }
	   switch($mes) {
	       case 01: return 31; break;
	       case 02: return $dias_febrero; break;
	       case 03: return 31; break;
	       case 04: return 30; break;
	       case 05: return 31; break;
	       case 06: return 30; break;
	       case 07: return 31; break;
	       case 08: return 31; break;
	       case 09: return 30; break;
	       case 10: return 31; break;
	       case 11: return 30; break;
	       case 12: return 31; break;
	   }
	}

}


?>