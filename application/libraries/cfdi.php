<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class cfdi{
	private $path_certificado_org = '';
	private $path_certificado = '';
	private $path_key = '';
	private $pass_key = 'Piloto01';//CHONITA09

	public $version = '3.2';

	private $rfc = 'NEDR620710H76';
	private $razon_social = 'ROBERTO NEVAREZ DOMINGUEZ';
	private $regimen_fiscal = 'Actividad empresarial, régimen general de ley'; //'Actividad empresarial y profesional, Régimen de honorarios';
	private $calle = 'Pista Aérea';
	private $no_exterior = 'S/N';
	private $no_interior = '';
	private $colonia = 'Ranchito';
	private $localidad = 'Ranchito';
	private $municipio = 'Michoacán';
	private $estado = 'Michoacán';
	private $pais = 'México';
	private $cp = '60800';

	public $default_id_empresa = 3; //informacion fiscal guardada en la bd

	public function __construct(){
		$this->path_certificado_org = APPPATH.'CFDI/certificados/nedr620710h76_1302281329s.cer';
		$this->path_certificado = APPPATH.'CFDI/certificados/nedr620710h76_1302281329s.cer.pem';
		$this->path_key = APPPATH.'CFDI/certificados/nedr620710h76_1012091114s_p.key.pem';
	}

	public function obtenNoCertificado($path_certificado_org=null){
		$path_certificado_org = $path_certificado_org==null? $this->path_certificado_org: $path_certificado_org;
		$datos_cer = file_get_contents($path_certificado_org);
		$num_certificado = substr($datos_cer, 15, 20);
		return $num_certificado;
	}
	public function obtenFechaCertificado($path_certificado_org=null){
		$path_certificado_org = $path_certificado_org==null? $this->path_certificado_org: $path_certificado_org;
		$datos_cer = file_get_contents($path_certificado_org);
		$fecha_certificado = substr($datos_cer, (strpos($datos_cer, "Z")+3), 6);
		$fecha_certificado = '20'.substr($fecha_certificado, 0, 2).'-'.substr($fecha_certificado, 2, 2).'-'.substr($fecha_certificado, 4, 2);
		return $fecha_certificado;
	}

	public function obtenSello($cadena_original){
		$pkeyid = openssl_pkey_get_private(file_get_contents($this->path_key), $this->pass_key);
		openssl_sign($cadena_original, $crypttext, $pkeyid, OPENSSL_ALGO_SHA1);
		openssl_free_key($pkeyid);
		$sello = base64_encode($crypttext);
		return $sello;
	}

	// public function obtenCadenaOriginal($data){
	// 	$id_empresa = isset($data['id_empresa'])? $data['id_empresa']: $this->default_id_empresa;
	// 	$this->cargaDatosFiscales($id_empresa);

	// 	$data['cno_interior']= (isset($data['cno_interior'])) ? (($data['cno_interior']!='') ? '|'.$data['cno_interior']: '') : ''; // Numero Interior
	// 	$data['no_cuenta_pago']	= (isset($data['no_cuenta_pago'])) ? (($data['no_cuenta_pago']!='') ? '|'.$data['no_cuenta_pago']: '|No identificado') : '|No identificado'; // Ultimos 4 digitos

	// 	$cadena = '||'.$this->version.'|'.$data['serie'].'|'.$data['folio'].'|'.$data['fecha_xml'].'|'.$data['no_aprobacion'].'|'.$data['ano_aprobacion'].'|'.$data['tipo_comprobante'].'|'.$data['forma_pago'].'|'.$data['subtotal'].'|0|'.$data['total'].'|'.$data['metodo_pago'].'|'.$this->localidad.', '.$this->estado.$data['no_cuenta_pago'].'|'.$data['moneda'].'|'.$this->rfc.'|'.$this->razon_social.'|'.$this->calle.'|'.$this->no_exterior.'|'.$this->colonia.'|'.$this->localidad.'|'.$this->municipio.'|'.$this->estado.'|'.$this->pais.'|'.$this->cp.'|'.$this->calle.'|'.$this->no_exterior.'|'.$this->colonia.'|'.$this->localidad.'|'.$this->municipio.'|'.$this->estado.'|'.$this->pais.'|'.$this->cp.'|'.$this->regimen_fiscal.'|'.$data['crfc'].'|'.$data['cnombre'].'|'.$data['ccalle'].'|'.$data['cno_exterior'].$data['cno_interior'].'|'.$data['ccolonia'].'|'.$data['clocalidad'].'|'.$data['cmunicipio'].'|'.$data['cestado'].'|'.$data['cpais'].'|'.$data['ccp'].'|';

	// 	if(count($data["productos"])>0)
	// 		foreach($data["productos"] as $key => $p){
	// 			$cadena .= $p['cantidad'].'|'.$p['unidad'].'|'.$p['descripcion'].'|'.$p['precio_unit'].'|'.$p['importe'].'|';
	// 	  }

	// 	if(isset($data["total_isr"]))
	// 		$cadena .= 'ISR|'.$data['total_isr'].'|'.$data['total_isr'].'|';

	// 	if(count($data["ivas"])>0)
	// 		foreach($data["ivas"] as $key => $iva)
	// 			$cadena .= 'IVA|'.$iva['tasa_iva'].'|'.$iva['importe_iva'].'|';

	// 	$cadena .= $data['iva_total'].'||';
	// 	$cadena = preg_replace('/ +/', ' ', $cadena);
	// 	return $cadena;
	// }

  public function obtenCadenaOriginal($data)
  {
    // Obtiene el ID de la empresa que emite la factura, si no llega
    // entonces obtiene el ID por default.
    $id_empresa = isset($data['id_empresa']) ? $data['id_empresa'] : $this->default_id_empresa;

    // Carga los datos de la empresa que emite la factura.
    $this->cargaDatosFiscales($id_empresa);

    // $cadenaOriginal = '||';

    // Array que contiene la secuencia de informacion respetando el orden expresado
    // en el anexo 20.
    $datos = array();

    // ----------> Nodo comprobante
    $datos['comprobante']['version']              = $data['version'];
    // $datos['comprobante']['serie']                = $data['serie'];
    // $datos['comprobante']['folio']                = $data['folio'];
    $datos['comprobante']['fecha']                = $data['fecha'];
    // $datos['comprobante']['noAprobacion']         = $data['noAprobacion'];
    // $datos['comprobante']['anoAprobacion']        = $data['anoAprobacion'];
    $datos['comprobante']['tipoDeComprobante']    = $data['tipoDeComprobante'];
    $datos['comprobante']['formaDePago']          = $data['formaDePago'];
    $datos['comprobante']['condicionesDePago']    = $data['condicionesDePago'];
    $datos['comprobante']['subTotal']             = $data['subTotal'];
    // $datos['comprobante']['descuento']            = $data['descuento'];
    // $datos['comprobante']['TipoCambio']           = $data['TipoCambio'];
    // $datos['comprobante']['Moneda']               = $data['Moneda'];
    $datos['comprobante']['total']                = $data['total'];
    $datos['comprobante']['metodoDePago']         = $data['metodoDePago'];
    $datos['comprobante']['LugarExpedición']      = $this->municipio.', '.$this->estado;

    // if (isset($data['NumCtaPago']) && $data['NumCtaPago'] !== '')
    $datos['comprobante']['NumCtaPago'] = $data['NumCtaPago'];

    // $datos['comprobante']['FolioFiscalOrig']      = $data['FolioFiscalOrig'];
    // $datos['comprobante']['SerieFolioFiscalOrig'] = $data['SerieFolioFiscalOrig'];
    // $datos['comprobante']['FechaFolioFiscalOrig'] = $data['FechaFolioFiscalOrig'];
    // $datos['comprobante']['MontoFolioFiscalOrig'] = $data['MontoFolioFiscalOrig'];

    // ----------> Nodo emisor
    $datos['emisor']['rfc']    = $this->rfc;
    $datos['emisor']['nombre'] = $this->nombre_fiscal;

    // ----------> Nodo domicilioFiscal
    $datos['domicilioFiscal']['calle']        = $this->calle;
    $datos['domicilioFiscal']['noExterior']   = $this->no_exterior;

    if ($this->no_interior !== null && $this->no_interior !== '')
      $datos['domicilioFiscal']['noInterior'] = $this->no_interior;

    $datos['domicilioFiscal']['colonia']      = $this->colonia;
    $datos['domicilioFiscal']['localidad']    = $this->localidad;
    // $datos['domicilioFiscal']['referencia']
    $datos['domicilioFiscal']['municipio']    = $this->municipio;
    $datos['domicilioFiscal']['estado']       = $this->estado;
    $datos['domicilioFiscal']['pais']         = $this->pais;
    $datos['domicilioFiscal']['codigoPostal'] = $this->cp;

    // ----------> Nodo expedidoEn
    $datos['expedidoEn']['calle']        = $this->calle;
    $datos['expedidoEn']['noExterior']   = $this->no_exterior;

    if ($this->no_interior !== null && $this->no_interior !== '')
      $datos['expedidoEn']['noInterior'] = $this->no_interior;

    $datos['expedidoEn']['colonia']      = $this->colonia;
    $datos['expedidoEn']['localidad']    = $this->localidad;
    // ----------> $datos['expedidoEn']['referencia']
    $datos['expedidoEn']['municipio']    = $this->municipio;
    $datos['expedidoEn']['estado']       = $this->estado;
    $datos['expedidoEn']['pais']         = $this->pais;
    $datos['expedidoEn']['codigoPostal'] = $this->cp;

    // ----------> Nodo regimenFiscal
    $datos['regimenFiscal']['regimen'] = $this->regimen_fiscal;

    // ----------> Nodo receptor
    $datos['receptor']['rfc']    = $data['rfc'];
    $datos['receptor']['nombre'] = $data['nombre'];

    // ----------> Nodo domicilio
    $datos['domicilio']['calle']        = $data['calle'];
    $datos['domicilio']['noExterior']   = $data['noExterior'];

    if ($data['noInterior'] !== null && $data['noInterior'] !== '')
      $datos['domicilio']['noInterior'] = $data['noInterior'];

    $datos['domicilio']['colonia']      = $data['colonia'];
    $datos['domicilio']['localidad']    = $data['localidad'];
    // $datos['domicilio']['referencia']   = $data['referencia'];
    $datos['domicilio']['municipio']    = $data['municipio'];
    $datos['domicilio']['estado']       = $data['estado'];
    $datos['domicilio']['pais']         = $data['pais'];
    $datos['domicilio']['codigoPostal'] = $data['codigoPostal'];

    // ----------> Nodo concepto
    // cantidad
    // unidad
    // noIdentificacion
    // descripcion
    // valorUnitario
    // importe
    // cuentaPredial - numero

    $datos['concepto'] = array();
    foreach ($data['concepto'] as $key => $producto)
    {
      $datos['concepto'][] = $producto['cantidad'];
      $datos['concepto'][] = $producto['unidad'];
      $datos['concepto'][] = $producto['descripcion'];
      $datos['concepto'][] = $producto['valorUnitario'];
      $datos['concepto'][] = $producto['importe'];
    }

    // ----------> Nodo retencion
    // impuesto
    // importe
    // totalImpuestosRetenidos
    // if (isset($data['totalImpuestosRetenidos']) && $data['totalImpuestosRetenidos'] != 0)
    // {
    $datos['retencion'] = array();
    foreach ($data['retencion'] as $key => $retencion)
    {
      $datos['retencion'][] = $retencion['impuesto'];
      $datos['retencion'][] = $retencion['importe'];
    }
    $datos['retencion'][] = $data['totalImpuestosRetenidos'];

    // $retencion = '|'.implode('|', $datos['retencion']);
    // }

    // ----------> Nodo traslado
    // Impuesto
    // tasa
    // importe
    // totalImpuestosTrasladados
    // if (isset($data['totalImpuestosTrasladados']) && $data['totalImpuestosTrasladados'] != 0)
    // {
    $datos['traslado'] = array();
    foreach ($data['traslado'] as $key => $traslado)
    {
      $datos['traslado'][] = $traslado['Impuesto'];
      $datos['traslado'][] = $traslado['tasa'];
      $datos['traslado'][] = $traslado['importe'];
    }
    $datos['traslado'][] = $data['totalImpuestosTrasladados'];
    // }

    $mergeDatos = array_merge(
      $datos['comprobante'],
      $datos['emisor'],
      $datos['domicilioFiscal'],
      $datos['expedidoEn'],
      $datos['regimenFiscal'],
      $datos['receptor'],
      $datos['domicilio'],
      $datos['concepto'],
      $datos['retencion'],
      $datos['traslado']
    );

    return ltrim(rtrim(preg_replace('/\s+/', ' ', '||'.implode('|', $mergeDatos).'||')));
  }

	public function cargaDatosFiscales($id_empresa)
  {
		$CI =& get_instance();
		$data = $CI->db->query(
      "SELECT e.*, ef.id_fiscal, ef.version, ef.path_cer_org, ef.path_cer,
              ef.path_key, ef.pass_key
       FROM empresas AS e
       INNER JOIN empresas_fiscal AS ef ON ef.id_empresa = e.id_empresa
       WHERE e.id_empresa = " . $id_empresa
    )->row(); //nv_fiscal

		$this->path_certificado_org = $data->cer_org; //APPPATH.'CFDI/certificados/'.
		$this->path_certificado     = $data->cer; // APPPATH.'CFDI/certificados/'.
		$this->path_key             = $data->key_path; // APPPATH.'CFDI/certificados/'.
		$this->pass_key             = $data->pass;

    $this->version        = $data->version;
    $this->rfc            = $data->rfc;
    $this->nombre_fiscal  = $data->nombre_fiscal; // razon_social
    $this->regimen_fiscal = $data->regimen_fiscal;
    $this->calle          = $data->calle;
    $this->no_exterior    = $data->no_exterior;
    $this->no_interior    = $data->no_interior;
    $this->colonia        = $data->colonia;
    $this->localidad      = $data->localidad;
    $this->municipio      = $data->municipio;
    $this->estado         = $data->estado;
    $this->pais           = $data->pais;
    $this->cp             = $data->cp;
	}

	public function generaArchivos($data){
		$this->cargaDatosFiscales($data['id_nv_fiscal']);

		$vers = str_replace('.', '_', $this->version);
		$this->guardarXML($data);
		$this->generarUnPDF($data);
	}

	public function actualizarArchivos($data){
		$this->cargaDatosFiscales($data['id_nv_fiscal']);

		$vers = str_replace('.', '_', $this->version);
		$this->guardarXML($data,true);
		$this->generarUnPDF($data,array('F'),true);
	}
	/********** REPORTE MENSUAL ************/
	public function descargaReporte($anio, $mes){
		if($this->existeReporte($anio, $mes)){
			$path = APPPATH.'media/cfd/reportesMensuales/'.$anio.'/1'.$this->rfc.$mes.$anio.'.txt';
			header('Content-type: text/plain');
			header('Content-Disposition: attachment; filename="1'.$this->rfc.$mes.$anio.'.txt"');
			readfile($path);
		}
	}
	public function existeReporte($anio, $mes){;
		$path = APPPATH.'media/cfd/reportesMensuales/'.$anio.'/1'.$this->rfc.$mes.$anio.'.txt';
		return file_exists($path);
	}
	public function generaReporte($anio, $mes, $reporte, $ex_nombre=''){
		$path = APPPATH.'media/cfd/reportesMensuales/';
		if(!file_exists($path.$anio.'/'))
			$this->crearFolder($path, $anio."/");

		$path .= $anio.'/1'.$this->rfc.$mes.$anio.$ex_nombre.'.txt';
		$fp = fopen($path, 'w');
		fwrite($fp, $reporte);
		fclose($fp);
// 		$this->descargaReporte($anio, $mes);
		return array('tipo' => 0, 'mensaje' => 'El reporte se genero correctamente.');;
	}

	private function mesToString($mes){
		switch(floatval($mes)){
			case 1: return 'ENERO'; break;
			case 2: return 'FEBRERO'; break;
			case 3: return 'MARZO'; break;
			case 4: return 'ABRIL'; break;
			case 5: return 'MAYO'; break;
			case 6: return 'JUNIO'; break;
			case 7: return 'JULIO'; break;
			case 8: return 'AGOSTO'; break;
			case 9: return 'SEPTIEMBRE'; break;
			case 10: return 'OCTUBRE'; break;
			case 11: return 'NOVIEMBRE'; break;
			case 12: return 'DICIEMBRE'; break;
		}
	}

	public function acomodarFolio($folio){
		$folio .= '';
		for($i=strlen($folio); $i<8; ++$i){
			$folio = '0'.$folio;
		}
		return $folio;
	}

	public function ajustaTexto($cadena, $caracteres){
		$res = '';
		$len = strlen($cadena);
		$cont = 0;
		while($cont<$len){
			$res .= substr($cadena, $cont, $caracteres)."<br>";
			$cont += $caracteres;
		}

		return $res;
	}

	/**
	 * Valida si el directorio espesificado existe o si no lo crea.
	 */
	private function validaDir($tipo, $path){
		$path = APPPATH.'media/cfd/'.$path;
		if($tipo=='anio'){
			$directorio = date("Y");
		}else{
			$directorio = $this->mesToString(date("n"));
		}
		if(!file_exists($path.$directorio."/")){
			$this->crearFolder($path, $directorio."/");
		}
		return $directorio;
	}

	/**
	 * Crea un folder en el servidor.
	 * @param $path_directorio: string. ruta donde se creara el directorio.
	 * @param $nombre_directorio: string. nombre del folder a crear.
	 */
	private function crearFolder($path_directorio, $nombre_directorio){
		if($nombre_directorio != "" && file_exists($path_directorio)){
			if(!file_exists($path_directorio.$nombre_directorio))
				return mkdir($path_directorio.$nombre_directorio, 0777);
			else
				return true;
		}else
			return false;
	}

	private function obtenFechaMes($fecha){
		$fecha = explode('-', $fecha);
		return array($fecha[0],$fecha[1]);
	}



	/**
	 * FUNCIONES DE LS DISTINTAS VERSIONES DE CFD PARA LOS XML
	 */
	private function guardarXML($data,$update=false){
		$vers = str_replace('.', '_', $this->version);
		$xml = $this->{'generarXML'.$vers}($data);
		if(!$update){
			$dir_anio = $this->validaDir('anio', 'facturasXML/');
			$dir_mes = $this->validaDir('mes', 'facturasXML/'.$dir_anio.'/');
		}
		else{
			$fecha = $this->obtenFechaMes($data['fecha_xml']);
			$dir_anio = $fecha[0];
			$dir_mes = $this->mesToString($fecha[1]);

			if(!file_exists(APPPATH.'/media/cfd/facturasXML/'.$dir_anio.'/')){
				$this->crearFolder(APPPATH.'/media/cfd/facturasXML/', $dir_anio.'/');
			}
			if(!file_exists(APPPATH.'/media/cfd/facturasXML/'.$dir_anio.'/'.$dir_mes.'/')){
				$this->crearFolder(APPPATH.'/media/cfd/facturasXML/'.$dir_anio.'/', $dir_mes.'/');
			}
		}
		$path_guardar = APPPATH.'/media/cfd/facturasXML/'.$dir_anio.'/'.$dir_mes.'/'.
				$this->rfc.'-'.$data['serie'].'-'.$this->acomodarFolio($data['folio']).'.xml';
		$fp = fopen($path_guardar, 'w');
		fwrite($fp, $xml);
		fclose($fp);
	}

	public function descargarXML($data){
		$this->cargaDatosFiscales($data['id_nv_fiscal']);

		$vers = str_replace('.', '_', $this->version);
		$xml = $this->{'generarXML'.$vers}($data);
		header('Content-type: content-type: text/xml');
		header('Content-Disposition: attachment; filename="'.$this->rfc.'-'.$data['serie'].'-'.$this->acomodarFolio($data['folio']).'.xml"');
		echo $xml;
	}

	public function generarXML2_2($data=array()){
		$xml = '';
		$xml .= '<?xml version="1.0" encoding="utf-8"?>';
		$xml .= '<Comprobante xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns="http://www.sat.gob.mx/cfd/2" xsi:schemaLocation="http://www.sat.gob.mx/cfd/2 http://www.sat.gob.mx/sitio_internet/cfd/2/cfdv22.xsd" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬version="'.$data['version'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬serie="'.$data['serie'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬folio="'.$data['folio'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬fecha="'.$data['fecha_xml'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬noAprobacion="'.$data['no_aprobacion'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬anoAprobacion="'.$data['ano_aprobacion'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬tipoDeComprobante="'.$data['tipo_comprobante'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬formaDePago="'.$data['forma_pago'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬metodoDePago="'.$data['metodo_pago'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬LugarExpedicion="'.$this->localidad.', '.$this->estado.'" ';
		if($data['no_cuenta_pago']!=='')
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬NumCtaPago="'.$data['no_cuenta_pago'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬subTotal="'.$data['subtotal'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬descuento="'.$data['descuento'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬total="'.$data['total'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬sello="'.$data['sello'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬noCertificado="'.$data['no_certificado'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬Moneda="'.$data['moneda'].'"';
		$xml .= '>';

		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬<Emisor rfc="'.$this->rfc.'" nombre="'.$this->razon_social.'">';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<DomicilioFiscal ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬calle="'.$this->calle.'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬noExterior="'.$this->no_exterior.'" ';
		if($this->no_interior!=='')
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬noInterior="'.$this->no_interior.'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬colonia="'.$this->colonia.'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬localidad="'.$this->localidad.'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬municipio="'.$this->municipio.'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬estado="'.$this->estado.'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬pais="'.$this->pais.'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬codigoPostal="'.$this->cp.'"';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬/>';

		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<ExpedidoEn ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬calle="'.$this->calle.'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬noExterior="'.$this->no_exterior.'" ';
		if($this->no_interior!=='')
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬noInterior="'.$this->no_interior.'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬colonia="'.$this->colonia.'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬localidad="'.$this->localidad.'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬municipio="'.$this->municipio.'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬estado="'.$this->estado.'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬pais="'.$this->pais.'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬codigoPostal="'.$this->cp.'"';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬/>';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<RegimenFiscal ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬Regimen="'.$this->regimen_fiscal.'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬/>';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬</Emisor>';

		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬<Receptor rfc="'.$data['crfc'].'" nombre="'.$data['cnombre'].'">';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<Domicilio ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬calle="'.$data['ccalle'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬noExterior="'.$data['cno_exterior'].'" ';
		if($data['cno_interior']!=='')
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬noInterior="'.$data['cno_interior'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬colonia="'.$data['ccolonia'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬localidad="'.$data['clocalidad'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬municipio="'.$data['cmunicipio'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬estado="'.$data['cestado'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬pais="'.$data['cpais'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬codigoPostal="'.$data['ccp'].'"';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬/>';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬</Receptor>';

		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬<Conceptos>';

		foreach($data['productos'] as $itm){
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<Concepto ';
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬cantidad="'.(float)$itm['cantidad'].'" ';
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬unidad="'.$itm['unidad'].'" ';
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬valorUnitario="'.(float)$itm['precio_unit'].'" ';
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬descripcion="'.$itm['descripcion'].'" ';
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬importe="'.(float)$itm['importe'].'"';
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬/>';
		}
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬</Conceptos>';

		$attr_isr = '';
		if(isset($data['total_isr']))
			$attr_isr = ' totalImpuestosRetenidos="'.(float)$data['total_isr'].'"';

		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬<Impuestos totalImpuestosTrasladados="'.(float)$data['iva_total'].'"'.$attr_isr.'>';
		if(isset($data['total_isr'])){
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<Retenciones>';
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<Retencion ';
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬impuesto="ISR" ';
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬importe="'.(float)$data['total_isr'].'"';
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬/>';
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬</Retenciones>';
		}
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<Traslados>';
		foreach($data['ivas'] as $itm){
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<Traslado ';
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬impuesto="IVA" ';
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬tasa="'.(float)$itm['tasa_iva'].'" ';
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬importe="'.(float)$itm['importe_iva'].'"';
			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬/>';
		}
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬</Traslados>';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬</Impuestos>';

		$xml .= '</Comprobante>';

		$xml = str_replace('¬','',$xml);
		return $xml;
	}


	/**
	 * FUNCIONES DE LS DISTINTAS VERSIONES DE CFD PARA LOS PDF
	 */
	public function generarPDF($data=array(), $accion=array('F'), $update=false){
		$this->cargaDatosFiscales($data['id_nv_fiscal']);
		$this->generarUnPDF($data, $accion, $update);
	}

	public function generarUnPDF($data=array(), $accion=array('F'), $update=false){
		if(count($data)>0){
			$ci =& get_instance();
			$ci->load->library('mypdf');

			// Creacion del objeto de la clase heredada
			$pdf = new MYpdf('P', 'mm', 'Letter');
			$pdf->show_head = false;
			$vers = str_replace('.', '_', $this->version);
			$this->{'generarFacturaPDF'.$vers}($pdf, $data);

			//-----------------------------------------------------------------------------------

			if(!$update){
				$dir_anio = $this->validaDir('anio', 'facturasPDF/');
				$dir_mes = $this->validaDir('mes', 'facturasPDF/'.$dir_anio.'/');
			}
			else{
				$fecha = $this->obtenFechaMes($data['fecha_xml']);
				$dir_anio = $fecha[0];
				$dir_mes = $this->mesToString($fecha[1]);

				if(!file_exists(APPPATH.'/media/cfd/facturasPDF/'.$dir_anio.'/')){
					$this->crearFolder(APPPATH.'/media/cfd/facturasPDF/', $dir_anio.'/');
				}
				if(!file_exists(APPPATH.'/media/cfd/facturasPDF/'.$dir_anio.'/'.$dir_mes.'/')){
					$this->crearFolder(APPPATH.'/media/cfd/facturasPDF/'.$dir_anio.'/', $dir_mes.'/');
				}
			}

			if(count($accion)>0){
				foreach($accion as $a){
					switch (strtolower($a)){
						case 'v': // VISUALIZA PDF EN WEB
							$pdf->Output($dir_anio.'|'.$dir_mes.'|'.$this->rfc.'-'.$data['serie'].'-'.$this->acomodarFolio($data['folio']).'.pdf', 'I');
						break;
						case 'f': // GUARDA EN DIRECTORIO facturasPDF
							$path_guardar = APPPATH.'media/cfd/facturasPDF/'.$dir_anio.'/'.$dir_mes.'/'.
															$this->rfc.'-'.$data['serie'].'-'.$this->acomodarFolio($data['folio']).'.pdf';
							$pdf->Output($path_guardar, 'F');
						break;
						case 'd':  // DESCARGA DIRECTA DEL PDF
							$pdf->Output($dir_anio.'|'.$dir_mes.'|'.$this->rfc.'-'.$data['serie'].'-'.$this->acomodarFolio($data['folio']).'.pdf', 'D');
						break;
						default: // VISUALIZA PDF EN WEB
							$pdf->Output($dir_anio.'|'.$dir_mes.'|'.$this->rfc.'-'.$data['serie'].'-'.$this->acomodarFolio($data['folio']).'.pdf', 'I');
					}
				}
			}
		}
	}
	public function generarMasPDF($data=array(), $accion='I'){
		if(count($data)>0){
			$ci =& get_instance();
			$ci->load->library('mypdf');

			// Creacion del objeto de la clase heredada
			$pdf = new MYpdf('P', 'mm', 'Letter');
			$pdf->show_head = false;

			foreach ($data as $key => $value) {
				$this->cargaDatosFiscales($value['id_nv_fiscal']);
				$vers = str_replace('.', '_', $this->version);
				$this->{'generarFacturaPDF'.$vers}($pdf, $value);
			}


			switch (strtolower($accion)){
				case 'd':  // DESCARGA DIRECTA DEL PDF
					$pdf->Output($this->rfc.'_'.date("Y-m-d").'.pdf', 'D');
				break;
				default: // VISUALIZA PDF EN WEB
					$pdf->Output($this->rfc.'_'.date("Y-m-d").'.pdf', 'I');
			}
		}
	}
	public function generarFacturaPDF2_2(&$pdf, $data){
			$pdf->AddPage();
			$pdf->SetFont('Arial','',8);

			$y = 40;
			$pdf->Image(APPPATH.'/images/logo.png',8,20,25,25,"PNG");

			$pdf->SetFont('Arial','B',17);
			$pdf->SetXY(38, $y-30);
			$pdf->Cell(120, 6, $this->razon_social , 0, 0, 'C');

			$pdf->SetFont('Arial','',13);
			$pdf->SetXY(38, $y-23);
			$pdf->MultiCell(116, 6, "R.F.C.".$this->rfc." \n Pista Aerea No. S/N \n Ranchito 60800 Ranchito Michoacan Mexico \n {$this->regimen_fiscal} " , 0,'C',0);
			$pdf->SetDrawColor(140,140,140);
			// ----------- FOLIO ------------------
			$pdf->SetFont('Arial','',13);
			$pdf->SetXY(164, ($y-29));
			$pdf->Cell(38, 7, (substr($data['fecha_xml'], 0, 10) < '2012-10-31'? 'Recibo de honorarios': 'Factura') , 0, 0, 'C');

			$pdf->SetXY(158, ($y-22));
			$pdf->Cell(50, 13, '' , 1, 0, 'C');

			$pdf->SetFont('Arial','B',11);
			$pdf->SetTextColor(255,255,255);
			$pdf->SetFillColor(140,140,140);
			$pdf->SetXY(158, ($y-22));
			$pdf->Cell(50, 5, 'Serie y Folio', 1, 0, 'C',1);

			$pdf->SetFont('Arial','',12);
			$pdf->SetTextColor(255,0,0);
			$pdf->SetFillColor(255,255,255);
			$pdf->SetXY(158, $y-17);
			$pdf->Cell(50, 8, $data['serie'].'-'.$data['folio'] , 0, 0, 'C');

			// ----------- FECHA ------------------

			$pdf->SetXY(158, ($y-8));
			$pdf->Cell(50, 13, '' , 1, 0, 'C');

			$pdf->SetFont('Arial','B',11);
			$pdf->SetTextColor(255,255,255);
			$pdf->SetFillColor(140,140,140);
			$pdf->SetXY(158, ($y-8));
			$pdf->Cell(50, 5, 'Fecha de Expedición' , 1, 0, 'C',1);

			$pdf->SetFont('Arial','',12);
			$pdf->SetTextColor(255,0,0);
			$pdf->SetFillColor(255,255,255);
			$pdf->SetXY(158, ($y-3));
			$pdf->Cell(50, 8, $data['fecha_xml'] , 1, 0, 'C',1);

			// ----------- No y Año aprob ------------------

			$pdf->SetXY(158, ($y+6));
			$pdf->Cell(50, 13, '' , 1, 0, 'C');

			$pdf->SetFont('Arial','B',11);
			$pdf->SetTextColor(255,255,255);
			$pdf->SetFillColor(140,140,140);
			$pdf->SetXY(158, ($y+6));
			$pdf->Cell(50, 5, 'No. y Año aprobracion' , 1, 0, 'C',1);

			$pdf->SetFont('Arial','',12);
			$pdf->SetTextColor(255,0,0);
			$pdf->SetFillColor(255,255,255);
			$pdf->SetXY(158, ($y+11));
			$pdf->Cell(50, 8, $data['no_aprobacion'].'-'.$data['ano_aprobacion'] , 1, 0, 'C',1);

			// ----------- No Certificado ------------------

			$pdf->SetXY(158, ($y+20));
			$pdf->Cell(50, 13, '' , 1, 0, 'C');

			$pdf->SetFont('Arial','B',11);
			$pdf->SetTextColor(255,255,255);
			$pdf->SetFillColor(140,140,140);
			$pdf->SetXY(158, ($y+20));
			$pdf->Cell(50, 5, 'No. Certificado' , 1, 0, 'C',1);

			$pdf->SetFont('Arial','',12);
			$pdf->SetTextColor(255,0,0);
			$pdf->SetFillColor(255,255,255);
			$pdf->SetXY(158, ($y+25));
			$pdf->Cell(50, 8, $data['no_certificado'] , 1, 0, 'C',1);

			// ----------- DATOS CLIENTE ------------------

			$pdf->SetXY(8, ($y+7));
			$pdf->Cell(149, 41, '' , 1, 0, 'C');

			$pdf->SetFont('Arial','B',9);
			$pdf->SetTextColor(255,255,255);
			$pdf->SetFillColor(140,140,140);

			$pdf->SetXY(8, $y+7);  // BLOQUE DATOS 1
			$pdf->Cell(16, 41, '', 0, 0, 'C',1);

			$pdf->SetXY(8, $y+9);
			$pdf->Cell(16, 6, 'R.F.C.', 0, 0, 'L');

			$pdf->SetXY(8, $y+15);
			$pdf->Cell(16, 6, 'NOMBRE' , 0, 0, 'L');

			$pdf->SetXY(8, $y+21);
			$pdf->Cell(16, 6, 'CALLE' , 0, 0, 'L');

			$pdf->SetXY(8, $y+27);
			$pdf->Cell(16, 6, 'NUMERO' , 0, 0, 'L');

			$pdf->SetXY(8, $y+33);
			$pdf->Cell(16, 6, 'COLONIA' , 0, 0, 'L');

			$pdf->SetXY(8, $y+39);
			$pdf->Cell(16, 6, 'EDO' , 0, 0, 'L');

			$pdf->SetXY(70, $y+27); // BLOQUE DATOS 2
			$pdf->Cell(18, 21, '', 0, 0, 'C',1);

			$pdf->SetXY(70, $y+27);
			$pdf->Cell(18, 6, 'INT' , 0, 0, 'L');

			$pdf->SetXY(70, $y+33);
			$pdf->Cell(18, 6, 'MUNICIPIO' , 0, 0, 'L');

			$pdf->SetXY(70, $y+39);
			$pdf->Cell(18, 6, 'PAIS' , 0, 0, 'L');

			$pdf->SetXY(117, $y+27); // BLOQUE DATOS 3
			$pdf->Cell(16, 14, '', 0, 0, 'C',1);

			$pdf->SetXY(117, $y+27);
			$pdf->Cell(18, 6, 'C.P.' , 0, 0, 'L');

			$pdf->SetXY(117, $y+33);
			$pdf->Cell(18, 6, 'CIUDAD' , 0, 0, 'L');

			$pdf->SetFont('Arial','',7);
			$pdf->SetTextColor(0,0,0);

			$pdf->SetXY(25, $y+9); // BLOQUE DATOS 1 INFO
			$pdf->Cell(132, 6, strtoupper($data['crfc']), 0, 0, 'L');

			$pdf->SetXY(25, $y+15);
			$pdf->Cell(132, 6, strtoupper($data['cnombre']), 0, 0, 'L');

			$pdf->SetXY(25, $y+21);
			$pdf->Cell(132, 6, strtoupper($data['ccalle']), 0, 0, 'L');

			$pdf->SetXY(25, $y+27);
			$pdf->Cell(44, 6, strtoupper($data['cno_exterior']), 0, 0, 'L');

			$pdf->SetXY(25, $y+33);
			$pdf->Cell(44, 6, strtoupper($data['ccolonia']), 0, 0, 'L');

			$pdf->SetXY(25, $y+39);
			$pdf->Cell(44, 6, strtoupper($data['cestado']), 0, 0, 'L');

			$pdf->SetXY(88, $y+27); // BLOQUE DATOS 2 INFO
			$pdf->Cell(28, 6, strtoupper($data['cno_interior']), 0, 0, 'L');

			$pdf->SetXY(88, $y+33);
			$pdf->Cell(28, 6, strtoupper($data['cmunicipio']), 0, 0, 'L');

			$pdf->SetXY(88, $y+39);
			$pdf->Cell(28, 6, strtoupper($data['cpais']), 0, 0, 'L');

			$pdf->SetXY(133, $y+27); // BLOQUE DATOS 3 INFO
			$pdf->Cell(24, 6, strtoupper($data['ccp']), 0, 0, 'L');

			$pdf->SetXY(133, $y+33);
			$pdf->Cell(24, 6, strtoupper($data['cmunicipio']), 0, 0, 'L');

			// ----------- TABLA CON LOS PRODUCTOS ------------------
			$pdf->SetY($y+50);
			$aligns = array('C', 'C', 'C', 'C');
			$widths = array(25, 109, 33,33);
			$header = array('CANTIDAD', 'DESCRIPCION', 'PRECIO UNIT.','IMPORTE');
			foreach($data['productos'] as $key => $item){
				$band_head = false;
				if($pdf->GetY() >= 200 || $key==0){ //salta de pagina si exede el max
					if($key > 0)
						$pdf->AddPage();

					$pdf->SetFont('Arial','B',8);
					$pdf->SetTextColor(255,255,255);
					$pdf->SetFillColor(140,140,140);
					$pdf->SetX(8);
					$pdf->SetAligns($aligns);
					$pdf->SetWidths($widths);
					$pdf->Row($header, true);
				}

				$pdf->SetFont('Arial','',10);
				$pdf->SetTextColor(0,0,0);

				$datos = array($item['cantidad'], $item['descripcion'], String::formatoNumero($item['precio_unit']),String::formatoNumero($item['importe']));

				$pdf->SetX(8);
				$pdf->SetAligns($aligns);
				$pdf->SetWidths($widths);
				$pdf->Row($datos, false);
			}

			//------------ SUBTOTAL, IVA ,TOTAL --------------------

			$y = $pdf->GetY();
			$pdf->SetFont('Arial','B',10);
			$pdf->SetTextColor(255,255,255);
			$pdf->SetFillColor(140,140,140);

			$pdf->SetXY(144, ($y+5));
			$pdf->Cell(31, 6, 'Subtotal' , 1, 0, 'C',1);
			$pdf->SetXY(144, ($y+11));

			if (strtoupper($data['crfc']) != 'XAXX010101000') {
				$pdf->Cell(31, 6, 'IVA' , 1, 0, 'C',1);
				$pdf->SetXY(144, ($y+17));
			}

			if (isset($data['total_isr'])) {
				$pdf->Cell(31, 6, 'Retencion ISR' , 1, 0, 'C',1);
				$pdf->SetXY(144, ($y+23));
			}

			$pdf->Cell(31, 6, 'Total' , 1, 0, 'C',1);

			$pdf->SetTextColor(0,0,0);
			$pdf->SetFillColor(255,255,255);
			$pdf->SetXY(175, ($y+5));
			$pdf->Cell(33, 6, String::formatoNumero($data['subtotal'],2) , 1, 0, 'C');
			$pdf->SetXY(175, ($y+11));

			if (strtoupper($data['crfc']) != 'XAXX010101000') {
				$pdf->Cell(33, 6, String::formatoNumero($data['importe_iva'],2) , 1, 0, 'C');
				$pdf->SetXY(175, ($y+17));
			}

			if (isset($data['total_isr'])) {
				$pdf->Cell(33, 6, (isset($data['total_isr'])) ? String::formatoNumero($data['total_isr'],2) : '$0.00' , 1, 0, 'C');
				$pdf->SetXY(175, ($y+23));
			}

			$pdf->Cell(33, 6, String::formatoNumero($data['total'],2) , 1, 0, 'C');

			//------------ TOTAL CON LETRA--------------------

			$pdf->SetXY(8, ($y+5));
			$pdf->Cell(134, 24, '' , 1, 0, 'C');

			$pdf->SetFont('Arial','B',10);
			$pdf->SetTextColor(255,255,255);
			$pdf->SetFillColor(140,140,140);
			$pdf->SetXY(8, ($y+5));
			$pdf->Cell(134, 6, '	IMPORTE CON LETRA' , 0, 0, 'L',1);

			$pdf->SetFont('Arial','',8);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetXY(9, ($y+12));
			$pdf->MultiCell(130, 6, $data['total_letra'] , 0, 'L');

			$pdf->SetXY(9, ($y+24));
			$pdf->Cell(130, 6, "Método de Pago: {$data['metodo_pago']}".(($data['metodo_pago'] == 'efectivo')?'':" | No. Cuenta: {$data['no_cuenta_pago'] }") , 0, 0, 'L',0);

			//------------ CADENA ORIGINAL --------------------
			$y += 32;
			$pdf->SetY($y);
			$pdf->SetX(8);

			$pdf->SetFont('Arial','B',10);
			$pdf->SetTextColor(255,255,255);
			$pdf->SetFillColor(140,140,140);

			$pdf->SetAligns(array('L'));
			$pdf->SetWidths(array(200));
			$pdf->Row(array('CADENA ORIGINAL'), true);

			$pdf->SetX(8);

			$pdf->SetFont('Arial','',9);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFillColor(255,255,255);

			$pdf->SetAligns(array('L'));
			$pdf->SetWidths(array(200));
			$pdf->Row(array($data['cadena_original']), false);

			//------------ SELLO DIGITAL --------------------

			$y = $pdf->GetY();

			$pdf->SetY($y+3);
			$pdf->SetX(8);

			$pdf->SetFont('Arial','B',10);
			$pdf->SetTextColor(255,255,255);
			$pdf->SetFillColor(140,140,140);

			$pdf->SetAligns(array('L'));
			$pdf->SetWidths(array(200));
			$pdf->Row(array('SELLO DIGITAL'), true);

			$pdf->SetX(8);

			$pdf->SetFont('Arial','',9);
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFillColor(255,255,255);

			$pdf->SetAligns(array('L'));
			$pdf->SetWidths(array(200));
			$pdf->Row(array($data['sello']), false);

			if($data['fobservaciones'] != ''){
				$y = $pdf->GetY();
				$pdf->SetY($y+3);
				$pdf->SetX(8);

				$pdf->SetFont('Arial','B',10);
				$pdf->SetTextColor(255,255,255);
				$pdf->SetFillColor(140,140,140);

				$pdf->SetAligns(array('L'));
				$pdf->SetWidths(array(200));
				$pdf->Row(array('OBSERVACIONES'), true);

				$pdf->SetX(8);

				$pdf->SetFont('Arial','',9);
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFillColor(255,255,255);

				$pdf->SetAligns(array('L'));
				$pdf->SetWidths(array(200));
				$pdf->Row(array($data['fobservaciones']), false);
			}

			$y = $pdf->GetY();

			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(8, $y+2);
			$pdf->Cell(200,5,'ESTE DOCUMENTO ES UNA IMPRESIÓN DE UN COMPROBANTE FISCAL DIGITAL',0,0,'C');

			//------------ IMAGEN CANDELADO --------------------

			if(isset($data['status'])){
				if($data['status']=='ca'){
					$pdf->Image(APPPATH.'/images/cancelado.png',20,40,190,190,"PNG");
				}
			}
	}

}