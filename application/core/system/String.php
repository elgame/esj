<?php
require FCPATH.'vendor/nesbot/carbon/src/Carbon/Carbon.php';

use Carbon\Carbon;

class String{

  public static function getMetodoPago($codigo='', $nombre='')
  {
    $codigo = (string)$codigo;
    $nombre = (string)$nombre;
    $metodosPagos = [
      '01' => 'Efectivo',
      '02' => 'Cheque nominativo',
      '03' => 'Transferencia electrónica de fondos',
      '04' => 'Tarjetas de crédito',
      '05' => 'Monederos electrónicos',
      '06' => 'Dinero electrónico',
      '08' => 'Vales de despensa',
      '28' => 'Tarjeta de débito',
      '29' => 'Tarjeta de servicio',
      '99' => 'Otros',
      'NA' => 'No aplica',

      // '01' => 'Efectivo',
      // '02' => 'Cheque',
      // '03' => 'Transferencia',
      // '04' => 'Tarjetas de crédito',
      // '05' => 'Monederos electrónicos',
      // '06' => 'Dinero electrónico',
      // '07' => 'Tarjetas digitales',
      // '08' => 'Vales de despensa',
      // '09' => 'Bienes',
      // '10' => 'Servicio',
      // '11' => 'Por cuenta de tercero',
      // '12' => 'Dación en pago',
      // '13' => 'Pago por subrogación',
      // '14' => 'Pago por consignación',
      // '15' => 'Condonación',
      // '16' => 'Cancelación',
      // '17' => 'Compensación',
      // '98' => 'NA',
      // '99' => 'Otros'
    ];

    if (isset($codigo{0})) {
      return isset($metodosPagos[$codigo])? $codigo.' - '.$metodosPagos[$codigo]: $codigo;
    } elseif (isset($nombre{0})) {
      $codigo = array_search($nombre, $metodosPagos);
      return $codigo === false? $nombre: $codigo;
    } else {
      return $metodosPagos;
    }
  }

  /**
   * Da formato numerico a una cadena
   * @param unknown_type $number
   * @param unknown_type $decimales
   * @param unknown_type $sigini
   */
  public static function formatoNumero($number, $decimales=2, $sigini='$', $condecim=true){
    $number = floatval($number);
    $num = explode('.', $number);
    if($condecim){
      if(isset($num[1]))
        $decimales = (strlen($num[1])<$decimales? strlen($num[1]): $decimales);
      else
        $decimales = 0;
    }
    $number = floatval(number_format($number, $decimales, '.', ''))==0? abs($number): $number;
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
        if (is_array($item))
        {
          self::limpiarTexto($item);
        }
        else
        {
          $txt[$key] = addslashes(self::quitComillas(strip_tags(stripslashes(trim($item)))));
          $txt[$key] = $ci->security->xss_clean(preg_replace("/select (.+) from|update (.+) set|delete from|drop table|where (.+)=(.+)/","", $txt[$key]));
        }
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
  public static function num2letras($num, $moneda='MXN', $fem = false, $dec = true) {
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
   *        Corresponde a la fecha a la que se le sumaran los d�as
   * @param $ndias:integer
   *        Es el numero de dias que se le sumaran a la fecha
   * @param $formato:string (opcional)
   *        Es el formato en el que sera devuelto la fecha resultante (default Y-m-d)
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
  public static function mes($num, $formato='l'){
    /**
     * Creamos un array con los meses disponibles.
     * Agregamos un valor cualquiera al comienzo del array para que los números coincidan
     * con el valor tradicional del mes. El valor "Error" resultará útil
     **/
    if($formato == 'c')
      $meses = array('Error', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun',
        'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic');
    else
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
   * mes()
   *
   * Devuelve la cadena de texto asociada al número de semana
   *
   * @param   int mes (entero entre 1 y 12)
   * @return  string  nombre_del_mes
   */
  public static function dia($fecha, $formato='l'){
    $num = date("w", strtotime($fecha));
    if($formato == 'c')
      $meses = array('DO', 'LU', 'MA', 'MI', 'JU', 'VI', 'SA');
    else
      $meses = array('DOMINGO', 'LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO');
    return $meses[$num];
  }

  public static function diff2Dates($fch1, $fch2)
  {
    $response = new stdClass;

    $fecha1 = Carbon::createFromFormat('Y-m-d H:i:s', $fch1.' 12:00:00', 'America/Mexico_City');
    $fecha2 = Carbon::createFromFormat('Y-m-d H:i:s', $fch2.' 12:00:00', 'America/Mexico_City');
    $interval = $fecha2->diff($fecha1);
    $response->days = $interval->days;
    $response->dias = $interval->d;
    $response->meses = $interval->m;
    $response->anios = $interval->y;

    $fecha2->addDay();
    $interval = $fecha2->diff($fecha1);
    $response->semanas = intval($interval->days/7);
    $response->days2 = $interval->days;
    // $response->days = $fecha1->diffInDays($fecha2);
    // $response->dias = $fecha1->diffInDays($fecha2);
    // $response->comp = $fecha1->diffInDays($fecha2);

    // $response->anios = intval($response->dias/365);
    // $response->dias -= $response->anios*365;
    // $response->comp .= "-".($response->anios*365);
    // $response->meses = 0;
    // $fecha1->addYears($response->anios);
    // $response->comp .= "-".(($fecha1->daysInMonth-$fecha1->day)+1);
    // $fecha1->addDays(($fecha1->daysInMonth-$fecha1->day)+1);
    // while ($fecha1->lt($fecha2)) {
    //   if ($response->dias > $fecha1->daysInMonth) {
    //     $fecha1->addDays($fecha1->daysInMonth);
    //     $response->dias -= $fecha1->daysInMonth;
    //     $response->comp .= "-".$fecha1->daysInMonth;
    //     ++$response->meses;
    //   } else
    //     break;
    // }
    // if ($response->meses > 0) {
    //   --$response->dias;
    //     $response->comp .= "-1";
    // }
    return $response;
  }


  public static function fechaAT($fecha) {
    return self::fechaATexto($fecha, 'in');
  }
  /**
   * fechaATexto()
   *
   * Devuelve la cadena de texto asociada a la fecha ingresada
   *
   * @param   string fecha (cadena con formato XXXX-XX-XX)
   * @param   string formato (puede tomar los valores 'l', 'u', 'c', '/c', 'in')
   * @return  string  fecha_en_formato_texto
   */
  public static function fechaATexto($fecha, $formato = 'c') {

    // Validamos que la cadena satisfaga el formato deseado y almacenamos las partes
    if (preg_match("/([0-9]{4})-([0-9]{1,2})-([0-9]{1,2})/", $fecha, $partes)) {
      // $partes[0] contiene la cadena original
      // $partes[1] contiene el año
      // $partes[2] contiene el número de mes
      // $partes[3] contiene el número del día
      if($formato == '/c')
        return $partes[3] .'/'. self::mes($partes[2], 'c') .'/'. $partes[1];
      elseif($formato == 'in')
        return $partes[3] .'/'. $partes[2] .'/'. $partes[1];
      else{
        $mes = ' de ' . self::mes($partes[2]) . ' de '; // Corregido!
        if ($formato == 'u') {
          $mes = strtoupper($mes);
        } elseif ($formato == 'l') {
          $mes = strtolower($mes);
        }
        return $partes[3] . $mes . $partes[1];
      }

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
         case '01': return 31; break;
         case '02': return $dias_febrero; break;
         case '03': return 31; break;
         case '04': return 30; break;
         case '05': return 31; break;
         case '06': return 30; break;
         case '07': return 31; break;
         case '08': return 31; break;
         case '09': return 30; break;
         case '10': return 31; break;
         case '11': return 30; break;
         case '12': return 31; break;
     }
  }

  /**
   * Obtiene las semanas del año.
   *
   * @param  string  $anio | Año del q se obtendra las semanas.
   * @param  mixed $mes | Mes del que se obtendran las semanas. 0:todos los meses
   * @param  mixed $diaEmpieza | Dia donde se empezaran a calcular las semanas 0:lunes, 1:martes etc
   * @param  boolean $todas | true: todas las semanas del año. false: obtiene hasta la semana actual del año.
   * @param  mixed $semana | numero de semana a obtener.
   * @return array 2014-01-01  | 2014
   */
  public static function obtenerSemanasDelAnioV2($anio, $mes = 0, $diaEmpieza = 0, $todas = false, $semanaFecha = false)
  {
    $mesInicial = intval($mes) === 0 ? 1 : intval($mes);
    $diasNombres = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');

    // $primerDiaSemana = mktime(0, 0, 0, $mesInicial, $diaEmpieza, $anio);
    $nombrePrimerDia = $diasNombres[$diaEmpieza];
    $nombreUltimoDia = $diasNombres[($diaEmpieza != 0 ? ($diaEmpieza - 1) : 6)];

    // Obtiene el primer dia de la primera semana del año.
    $primerDiaPrimeraSemanaDelAnio = self::primerDiaPrimeraSemanaDelAnio($anio);

    switch ($diaEmpieza)
    {
      case 0:
        $dias = '-7';
        break;
      case 1:
        $dias = '-6';
        break;
      case 2:
        $dias = '-5';
        break;
      case 3:
        $dias = '-4';
        break;
      case 4:
        $dias = '-3';
        break;
      case 5:
        $dias = '-2';
        break;
      default:
        $dias = '-1';
        break;
    }

    // Obtiene el primer dia donde se empezaran a contar las semanas.
    // $siguientePrimerDia = strtotime($nombrePrimerDia, $primerDiaPrimeraSemanaDelAnio);
    $siguientePrimerDia = strtotime(date('Y-m-d', $primerDiaPrimeraSemanaDelAnio) . " {$dias} days");

    // Si el dia actual es menor al primer dia de la primera semana del año que se
    // estan calculando las semanas, entonces quiere decir que no pertenece
    // al año que se estan calculado las semanas.
    if (strtotime(date('Y-m-d')) < $siguientePrimerDia)
    {
      // Al año se le resta 1.
      $anio = intval($anio) - 1;

      // Recalcula la primera semana del año.
      $primerDiaPrimeraSemanaDelAnio = self::primerDiaPrimeraSemanaDelAnio($anio);

      // Recalcula el siguiente primer dia.
      $siguientePrimerDia = strtotime($nombrePrimerDia, $primerDiaPrimeraSemanaDelAnio);
    }

    // Obtiene los el ultimo dia donde se empezaran a contar las semanas.
    $siguienteUltimoDia = strtotime($nombreUltimoDia, $siguientePrimerDia);

    // Numero de semana por default.
    $numeroSemana = 1;

    // Si el mes inicial no es 1:enero entonces obtiene el primer y ultimo
    // dia del mes donde se empezara.
    // if ($mesInicial !== 1)
    // {
    //   // Saca las semana a recorrer.
    //   $numeroSemana = 4 * ($mesInicial - 1);

    //   $siguientePrimerDia = strtotime("+{$numeroSemana} weeks", $siguientePrimerDia);
    //   $siguienteUltimoDia = strtotime($nombreUltimoDia, $siguientePrimerDia);
    //   $numeroSemana += 1;
    // }

    // si no se estan obteniendo todas las semanas, este auxiliar ayudara a
    // saber si ya se obtiene la ultima semana actual.
    $aux = false;

    // Almacena las semanas.
    $semanas = array();

    $semanasDefault = 52;

    while ($numeroSemana <= $semanasDefault)
    {
      if ($semanaFecha)
      {
        if ((intval($semanaFecha) === $numeroSemana) || ($siguientePrimerDia <= strtotime($semanaFecha) && $siguienteUltimoDia >= strtotime($semanaFecha)))
        {
          return array(
            'fecha_inicio' => date('Y-m-d', $siguientePrimerDia),
            'fecha_final'  => date('Y-m-d', $siguienteUltimoDia),
            'anio'         => $anio,
            'semana'       => $numeroSemana,
          );
        }
      }

      $semanas[] = array(
        'fecha_inicio' => date('Y-m-d', $siguientePrimerDia),
        'fecha_final'  => date('Y-m-d', $siguienteUltimoDia),
        'anio'         => $anio,
        'semana'       => $numeroSemana,
      );

      if ($todas === false && (strtotime(date('Y-m-d')) >= $siguientePrimerDia && strtotime(date('Y-m-d')) <= $siguienteUltimoDia))
        break;

      $siguientePrimerDia = strtotime('+1 week', $siguientePrimerDia);
      $siguienteUltimoDia = strtotime('+1 week', $siguienteUltimoDia);
      $numeroSemana++;

      if ($numeroSemana === 53)
      {
        $semanasDefault++;
      }
    }

    return $semanas;
  }

  public static function obtenerSemanaDeFecha($fecha, $diaEmpieza = 0)
  {
    $fecha_split = explode('-', $fecha);
    $semanas = self::obtenerSemanasDelAnioV2($fecha_split[0], 0, $diaEmpieza);

    foreach ($semanas as $key => $value) {
      if ($value['fecha_inicio'] <= $fecha && $value['fecha_final'] >= $fecha) {
        return $value;
      }
    }

    return false;
  }

  /**
   * Obtiene las fechas de X cantidad de dias apartir de la fecha
   * especificada.
   *
   * @param  string $fecha
   * @param  int $xdias | cantidad de dias a obtener contando $fecha
   * @return array
   */
  public static function obtenerSiguientesXDias($fecha, $xdias)
  {
    $dias = array();
    for ($i = 0; $i < $xdias; $i++)
    {
      $dias[] = date("Y-m-d", strtotime($fecha.' +'.$i.' day'));
      // $dias[] = date("Y-m-d", strtotime($fecha) + 86400 * $i);
    }

    return $dias;
  }

  /**
   * Obtiene el primer dia de la primera semana del año.
   *
   * Si el ultimo dia del año anterior fue el 29 de diciembre
   * entonces el primer dia de la primera semana seria el 30 de diciembre.
   *
   * @param  string $anio
   * @return string | strtotime
   */
  public function primerDiaPrimeraSemanaDelAnio($anio, $format = 'Y-m-d')
  {
    $primerDiaDelAnio = mktime(0, 0, 0, 1, 1, $anio);
    $primerJuevesDelAnio = strtotime('thursday', $primerDiaDelAnio);
    $primerDiaPrimeraSemanaDelAnio = strtotime(date("Y-m-d", $primerJuevesDelAnio) . " - 3 days");

    return $primerDiaPrimeraSemanaDelAnio;
  }

  /**
   * Obtiene el numero de dia del año. Va del 1 al 365.
   *
   * @return int
   */
  public function obtenerNumeroDeDiaDelAnio($fecha)
  {
    return date("z", strtotime($fecha)) + 1;
  }

  public static function numeroCardinal($numero = 1)
  {
    $terminacion = array('mo', 'er','do','er','to','to','to','mo','vo','no','mo');
    if (($numero % 100) > 11 && ($numero % 100) < 13)
       $abreviacion = $numero. 'do';
    else
       $abreviacion = $numero. $terminacion[($numero % 10)];

    return $abreviacion;
  }

}
?>