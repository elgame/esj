<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class cfdi{
  public $path_certificado_org = '';
  public $path_certificado     = '';
  public $path_key             = '';
  public $pass_key             = 'Piloto01';//CHONITA09

	public $version = '4.0';

  public $rfc            = 'NEDR620710H76';
  private $razon_social   = 'ROBERTO NEVAREZ DOMINGUEZ';
  private $regimen_fiscal = 'Actividad empresarial, régimen general de ley'; //'Actividad empresarial y profesional, Régimen de honorarios';
  private $calle          = 'Pista Aérea';
  private $no_exterior    = 'S/N';
  private $no_interior    = '';
  private $colonia        = 'Ranchito';
  private $localidad      = 'Ranchito';
  private $municipio      = 'Michoacán';
  private $estado         = 'Michoacán';
  private $pais           = 'México';
  private $cp             = '60800';
  private $curp           = '';

  private $isNomina = false;
  public $anio     = '2013'; // variable util para las nominas.
  public $semana   = '1'; // variable util para las nominas.

	public $default_id_empresa = 3; //informacion fiscal guardada en la bd

	/**
   * Inicializa las rutas del certificado, certificado.pem y key.pem
   *
   * @return void
   */
  public function __construct()
  {
		$this->path_certificado_org = APPPATH.'CFDI/certificados/nedr620710h76_1302281329s.cer';
		$this->path_certificado = APPPATH.'CFDI/certificados/nedr620710h76_1302281329s.cer.pem';
		$this->path_key = APPPATH.'CFDI/certificados/nedr620710h76_1012091114s_p.key.pem';
	}

  /**
   * Obtiene el numero de certificado de la empresa.
   *
   * @param  string $path_certificado_org
   * @return string
   */
	public function obtenNoCertificado($path_certificado_org = null)
  {
    $path_certificado_org = $path_certificado_org==null? $this->path_certificado_org: $path_certificado_org;

    $num_certificado = '';
    if (file_exists($path_certificado_org)) {
      $datos_cer            = file_get_contents($path_certificado_org);
      $num_certificado      = substr($datos_cer, 15, 20);
    }

		return $num_certificado;
	}

  /**
   * Obtiene la fecha del certificado.
   *
   * @param  string $path_certificado_org
   * @return string
   */
	public function obtenFechaCertificado($path_certificado_org=null)
  {
    $path_certificado_org = $path_certificado_org==null? $this->path_certificado_org: $path_certificado_org;
    $datos_cer            = file_get_contents($path_certificado_org);
    $fecha_certificado    = substr($datos_cer, (strpos($datos_cer, "Z")+3), 6);
    $fecha_certificado    = '20'.substr($fecha_certificado, 0, 2).'-'.substr($fecha_certificado, 2, 2).'-'.substr($fecha_certificado, 4, 2);

    return $fecha_certificado;
	}

  /**
   * Obtiene el Sello.
   *
   * @param  string $cadena_original
   * @return string
   */
	public function obtenSello($cadena_original)
  {
    $pkeyid = openssl_pkey_get_private(file_get_contents($this->path_key), $this->pass_key);
    openssl_sign($cadena_original, $crypttext, $pkeyid, OPENSSL_ALGO_SHA1);
		openssl_free_key($pkeyid);
		$sello = base64_encode($crypttext);

		return $sello;
	}

  /**
   * Obtiene el .key.pem y lo pasa a base64.
   *
   * @return string
   */
  public function obtenKey()
  {
    $text = file_get_contents($this->path_key);
    $data = base64_encode($text);
    return $data;
  }

  /**
   * Obtiene el contenido del .cer.pem y lo pasa a base64.
   *
   * @return string
   */
  public function obtenCer()
  {
    $text = file_get_contents($this->path_certificado);
    $data = base64_encode($text);
    return $data;
  }

  /**
   * Lee el contenido del certificado .pem y obtiene el contenido que se encuentra
   * entre los lineas -----BEGIN CERTIFICATE----- y -----END CERTIFICATE-----
   *
   * @param  string $path
   * @return string
   */
  public function obtenCertificado($path, $one_line=true)
  {
    // Lee el contenido del .cer.pem
    $datacer = file_get_contents($path);
    openssl_x509_export($datacer, $content);

    if($one_line){
      $cerpem = explode('-----BEGIN CERTIFICATE-----', $content);
      $cerpem = explode('-----END CERTIFICATE-----', $cerpem[1]);

      // Retorna la cadena del certificado sin espacios.
      return str_replace("\n", "", $cerpem[0]);
    }
    return $content;
  }

  /**
   * Lee el contenido de la llave key.pem y obtiene el contenido
   *
   * @param  string $path
   * @return string
   */
  public function obtenLlave($path)
  {
    // Lee el contenido del .key.pem
    $datacer = file_get_contents($path);

    return $datacer;
  }

  public function numero($valor)
  {
    $num = explode('.', $valor);
    if(isset($num[1]))
      $num[1] = substr($num[1], 0, 6);
    return implode('.', $num);
  }

  /**
   * Genera la Cadena Original.
   *
   * @param  array $data
   * @param  boolean $isNomina | indica si es una nomina
   * @return array
   */
  public function obtenCadenaOriginal($data, $isNomina = false, $empleado = null)
  {
    // Obtiene el ID de la empresa que emite la factura, si no llega
    // entonces obtiene el ID por default.
    // $id_empresa = isset($data['id_empresa']) ? $data['id_empresa'] : $this->default_id_empresa;
    $id = isset($data['id']) ? $data['id'] : $this->default_id_empresa;

    // Carga los datos de la empresa que emite la factura.
    $this->cargaDatosFiscales($id, $data['table']);

    if (!$isNomina) {
      $CI =& get_instance();
      $CI->load->model('nomina_catalogos_model');
      $cod_rf = $CI->nomina_catalogos_model->findByClave($this->regimen_fiscal, 'rgf');
      $this->regimen_fiscal = isset($cod_rf->nombre_corto)? $cod_rf->nombre_corto: $this->regimen_fiscal;
    }

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
    // $datos['comprobante']['tipoDeComprobante']    = 'egreso';
    $datos['comprobante']['formaDePago']          = $data['formaDePago'];
    if (!empty($data['condicionesDePago'])) {
      $datos['comprobante']['condicionesDePago']   = $data['condicionesDePago'];
    }
    $datos['comprobante']['subTotal']             = (float)$this->numero($data['subTotal']);

    // Nomina
    if (isset($data['descuento']))
    {
      $datos['comprobante']['descuento'] = $data['descuento'];
    }

    if (isset($data['Moneda']) && $data['Moneda'] !== '' && $data['Moneda'] !== 'M.N.')
    {
      $datos['comprobante']['TipoCambio']           = $data['TipoCambio'];
      $datos['comprobante']['Moneda']               = $data['Moneda'];
    }
    $datos['comprobante']['total']                = (float)$this->numero($data['total']);
    $datos['comprobante']['metodoDePago']         = $data['metodoDePago'];
    $datos['comprobante']['LugarExpedición']      = $this->municipio.', '.$this->estado;
    $datos['comprobante']['NumCtaPago']           = $data['NumCtaPago'];

    // $datos['comprobante']['FolioFiscalOrig']      = $data['FolioFiscalOrig'];
    // $datos['comprobante']['SerieFolioFiscalOrig'] = $data['SerieFolioFiscalOrig'];
    // $datos['comprobante']['FechaFolioFiscalOrig'] = $data['FechaFolioFiscalOrig'];
    // $datos['comprobante']['MontoFolioFiscalOrig'] = $data['MontoFolioFiscalOrig'];

    // ----------> Nodo emisor
    $datos['emisor']['rfc']    = $this->rfc;
    $datos['emisor']['nombre'] = $this->nombre_fiscal;

    // ----------> Nodo domicilioFiscal

    if ($this->calle !== null && $this->calle !== '')
      $datos['domicilioFiscal']['calle'] = $this->calle;

    if ($this->no_exterior !== null && $this->no_exterior !== '')
      $datos['domicilioFiscal']['noExterior'] = $this->no_exterior;

    if ($this->no_interior !== null && $this->no_interior !== '')
      $datos['domicilioFiscal']['noInterior'] = $this->no_interior;

    if ($this->colonia !== null && $this->colonia !== '')
      $datos['domicilioFiscal']['colonia'] = $this->colonia;

    if ($this->localidad !== null && $this->localidad !== '')
      $datos['domicilioFiscal']['localidad'] = $this->localidad;

    // $datos['domicilioFiscal']['referencia']

    if ($this->municipio !== null && $this->municipio !== '')
      $datos['domicilioFiscal']['municipio'] = $this->municipio;

    if ($this->estado !== null && $this->estado !== '')
      $datos['domicilioFiscal']['estado'] = $this->estado;

    if ($this->pais !== null && $this->pais !== '')
      $datos['domicilioFiscal']['pais'] = $this->pais;

    if ($this->cp !== null && $this->cp !== '')
      $datos['domicilioFiscal']['codigoPostal'] = $this->cp;

    // ----------> Nodo expedidoEn

    if ($this->calle !== null && $this->calle !== '')
      $datos['expedidoEn']['calle'] = $this->calle;

    if ($this->no_exterior !== null && $this->no_exterior !== '')
      $datos['expedidoEn']['noExterior'] = $this->no_exterior;

    if ($this->no_interior !== null && $this->no_interior !== '')
      $datos['expedidoEn']['noInterior'] = $this->no_interior;

    if ($this->colonia !== null && $this->colonia !== '')
      $datos['expedidoEn']['colonia'] = $this->colonia;

    if ($this->localidad !== null && $this->localidad !== '')
      $datos['expedidoEn']['localidad'] = $this->localidad;

    // ----------> $datos['expedidoEn']['referencia']

    if ($this->municipio !== null && $this->municipio !== '')
      $datos['expedidoEn']['municipio'] = $this->municipio;

    if ($this->estado !== null && $this->estado !== '')
      $datos['expedidoEn']['estado'] = $this->estado;

    if ($this->pais !== null && $this->pais !== '')
      $datos['expedidoEn']['pais'] = $this->pais;

    if ($this->cp !== null && $this->cp !== '')
      $datos['expedidoEn']['codigoPostal'] = $this->cp;

    // ----------> Nodo regimenFiscal
    $datos['regimenFiscal']['regimen'] = $this->regimen_fiscal;

    // ----------> Nodo receptor
    $datos['receptor']['rfc']    = $data['rfc'];
    $datos['receptor']['nombre'] = $data['nombre'];

    // ----------> Nodo domicilio

    if ($data['calle'] !== null && $data['calle'] !== '')
      $datos['domicilio']['calle'] = $data['calle'];

    if ($data['noExterior'] !== null && $data['noExterior'] !== '')
      $datos['domicilio']['noExterior'] = $data['noExterior'];

    if ($data['noInterior'] !== null && $data['noInterior'] !== '')
      $datos['domicilio']['noInterior'] = $data['noInterior'];

    if ($data['colonia'] !== null && $data['colonia'] !== '')
      $datos['domicilio']['colonia'] = $data['colonia'];

    if ($data['localidad'] !== null && $data['localidad'] !== '')
      $datos['domicilio']['localidad'] = $data['localidad'];

    // $datos['domicilio']['referencia']   = $data['referencia'];

    if ($data['municipio'] !== null && $data['municipio'] !== '')
      $datos['domicilio']['municipio'] = $data['municipio'];

    if ($data['estado'] !== null && $data['estado'] !== '')
      $datos['domicilio']['estado'] = $data['estado'];

    if ($data['pais'] !== null && $data['pais'] !== '')
      $datos['domicilio']['pais'] = $data['pais'];

    if ($data['codigoPostal'] !== null && $data['codigoPostal'] !== '')
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
    $datos['conceptos'] = $data['concepto'];
    foreach ($data['concepto'] as $key => $producto)
    {
      if ($data['sinCosto'])
      {
        if ($producto['idClasificacion'] != '49' AND $producto['idClasificacion'] != '50' AND
            $producto['idClasificacion'] != '51' AND $producto['idClasificacion'] != '52' AND
            $producto['idClasificacion'] != '53')
        {
          $datos['concepto'][] = (float)$this->numero($producto['cantidad']);
          $datos['concepto'][] = $producto['unidad'];
          isset($producto['noIdentificacion']{0})? $datos['concepto'][] = $producto['noIdentificacion'] : '';
          $datos['concepto'][] = $this->replaceSpecialChars($producto['descripcion'], true);
          $datos['concepto'][] = (float)$this->numero($producto['valorUnitario']);
          $datos['concepto'][] = (float)$this->numero($producto['importe']);
        }
      }
      else
      {
        $datos['concepto'][] = (float)$this->numero($producto['cantidad']);
        $datos['concepto'][] = $producto['unidad'];
        isset($producto['noIdentificacion']{0})? $datos['concepto'][] = $producto['noIdentificacion'] : '';
        $datos['concepto'][] = $this->replaceSpecialChars($producto['descripcion'], true);
        $datos['concepto'][] = (float)$this->numero($producto['valorUnitario']);
        $datos['concepto'][] = (float)$this->numero($producto['importe']);
      }
    }

    // ----------> Nodo retencion
    // impuesto
    // importe
    // totalImpuestosRetenidos
    $datos['retencion'] = array();
    $datos['retenciones'] = $data['retencion'];
    if(count($data['retencion']) > 0)
    {
      foreach ($data['retencion'] as $key => $retencion)
      {
        if ($retencion['impuesto'] > 0 || !$isNomina) {
          $datos['retencion'][] = $retencion['impuesto'];
          $datos['retencion'][] = (float)$this->numero($retencion['importe']);
        }
      }
      if ($data['totalImpuestosRetenidos'] > 0 || !$isNomina)
        $datos['retencion'][] = (float)$this->numero($data['totalImpuestosRetenidos']);
    }

    // ----------> Nodo traslado
    // Impuesto
    // tasa
    // importe
    // totalImpuestosTrasladados
    $datos['traslado'] = array();
    $datos['traslados'] = $data['traslado'];
    if (!$isNomina) {
      foreach ($data['traslado'] as $key => $traslado)
      {
        $datos['traslado'][] = $traslado['Impuesto'];
        $datos['traslado'][] = $traslado['tasa'];
        $datos['traslado'][] = (float)$this->numero($traslado['importe']);
      }
      $datos['traslado'][] = (float)$this->numero($data['totalImpuestosTrasladados']);
    }

    // ----------> Nodo Nomina Si es una nomina la que se facturara.
    $datos['nomina'] = array('datos_cadena' => array());
    if ($isNomina)
    {
      $datos['nomina'] = $this->nodoNomina($empleado, $datos, $data);
    }
    // echo "<pre>";
    //   var_dump($datos['nomina']);
    // echo "</pre>";exit;

    // ----------> COMERCIO EXTERIOR
    $comercioExterior = [];
    if (isset($data['comercioExterior']))
    {
      $datos['comercioExterior'] = $this->comercioExterior($data);

      if (isset($datos['comercioExterior']) && count($datos['comercioExterior']) > 0)
      {
        foreach ($datos['comercioExterior'] as $key => $item) {
          if (!is_array($item))
            $comercioExterior[] = $item;
          else {
            switch ($key) {
              case 'Emisor':
              case 'Receptor':
                $comercioExterior = array_merge($comercioExterior, array_values($item));
                break;
              case 'Destinatario':
                foreach ($item as $key2 => $item2) {
                  if (!is_array($item2))
                    $comercioExterior[] = $item2;
                  elseif (is_array($item2) && $key2 == 'Domicilio') {
                    $comercioExterior = array_merge($comercioExterior, array_values($item2));
                  }
                }
                break;
              case 'Mercancias':
                foreach ($item as $key2 => $item2) {
                  foreach ($item2 as $key3 => $item3) {
                    if (!is_array($item3))
                      $comercioExterior[] = $item3;
                    elseif (is_array($item3) && $key3 == 'DescripcionesEspecificas') {
                      foreach ($item3 as $key4 => $item4) {
                        $comercioExterior = array_merge($comercioExterior, array_values($item4));
                      }
                    }
                  }
                }
                break;
            }
          }
        }
      }
    }

    $mergeDatos = array_merge(
      array_values($datos['comprobante']),
      array_values($datos['emisor']));
    if (isset($datos['domicilioFiscal']))
      $mergeDatos = array_merge($mergeDatos, array_values($datos['domicilioFiscal']));
    if (isset($datos['expedidoEn']))
      $mergeDatos = array_merge($mergeDatos, array_values($datos['expedidoEn']));
    $mergeDatos = array_merge($mergeDatos,
      array_values($datos['regimenFiscal']),
      array_values($datos['receptor']));
    if (isset($datos['domicilio']))
      $mergeDatos = array_merge($mergeDatos, array_values($datos['domicilio']));
    $mergeDatos = array_merge($mergeDatos,
      array_values($datos['concepto']),
      array_values($datos['retencion']),
      array_values($datos['traslado']),
      array_values($datos['nomina']['datos_cadena'])
    );

    // echo "<pre>";
    //   var_dump(ltrim(rtrim(preg_replace('/\s+/', ' ', '||'.implode('|', $mergeDatos).'||'))));
    // echo "</pre>";exit;

    return array(
      'cadenaOriginal' => ltrim(rtrim(preg_replace('/\s+/', ' ', '||'.implode('|', $mergeDatos).'||'))),
      'datos' => $datos
    );
  }

  public function nodoNomina($empleado, &$datos, $data=array())
  {
    $nomina = array();

    $datos['comprobante']['LugarExpedición'] = $datos['expedidoEn']['codigoPostal']!=''? $datos['expedidoEn']['codigoPostal']: $datos['domicilioFiscal']['codigoPostal'];
    unset($datos['comprobante']['NumCtaPago']); // si es nomina quita numcuenta
    unset($datos['domicilioFiscal']);
    unset($datos['expedidoEn']);
    unset($datos['domicilio']);

    $nominaDatos = array(
      'Version'          => $empleado[0]->nomina->Version,
      'TipoNomina'       => $empleado[0]->nomina->TipoNomina,
      'FechaPago'        => $empleado[0]->nomina->FechaPago,
      'FechaInicialPago' => $empleado[0]->nomina->FechaInicialPago,
      'FechaFinalPago'   => $empleado[0]->nomina->FechaFinalPago,
      'NumDiasPagados'   => $empleado[0]->nomina->NumDiasPagados,

      // // 'RegistroPatronal'       => '', // opcional
      // 'NumEmpleado'            => $empleado[0]->no_empleado,
      // 'CURP'                   => $empleado[0]->curp,
      // 'TipoRegimen'            => $empleado[0]->regimen_contratacion,
      // // 'NumSeguridadSocial'     => '123456789', // opcional
      // 'FechaPago'              => $empleado[0]->fecha_final_pago,
      // 'FechaInicialPago'       => $empleado[0]->fecha_inicial_pago,
      // 'FechaFinalPago'         => $empleado[0]->fecha_final_pago,
      // 'NumDiasPagados'         => $empleado[0]->dias_trabajados,
      // 'Departamento'           => $empleado[0]->puesto,
      // // 'CLABE'                  => '', // opcional
      // // 'Banco'                  => '', // opcional
      // 'FechaInicioRelLaboral'  => $empleado[0]->fecha_entrada, // opcional
      // // 'Antiguedad'             => '30', // opcional
      // 'Puesto'                 => $empleado[0]->puesto, // opcional
      // // 'TipoContrato'           => 'Base', // opcional
      // // 'TipoJornada'            => 'continuada', // opcional
      // 'PeriodicidadPago'       => 'semanal',
      // // 'SalarioBaseCotApor'     => '', // opcional
      // // 'RiesgoPuesto'           => '', // opcional
      // // 'SalarioDiarioIntegrado' => $empleado[0]->nomina->salario_diario_integrado, // opcional
    );
    if (isset($empleado[0]->nomina->TotalPercepciones) && $empleado[0]->nomina->TotalPercepciones > 0)
      $nominaDatos['TotalPercepciones'] = $empleado[0]->nomina->TotalPercepciones;
    if (isset($empleado[0]->nomina->TotalDeducciones) && $empleado[0]->nomina->TotalDeducciones > 0)
      $nominaDatos['TotalDeducciones'] = $empleado[0]->nomina->TotalDeducciones;
    if (isset($empleado[0]->nomina->TotalOtrosPagos) && $empleado[0]->nomina->TotalOtrosPagos > 0)
      $nominaDatos['TotalOtrosPagos'] = $empleado[0]->nomina->TotalOtrosPagos;
    $nomina['Nomina'] = $nominaDatos;

    $nominaEmisor = array();
    if (isset($empleado[0]->nomina->emisor['Curp']) && $empleado[0]->nomina->emisor['Curp'] !== '')
      $nominaEmisor['Curp'] = $empleado[0]->nomina->emisor['Curp'];
    if (isset($empleado[0]->nomina->emisor['RegistroPatronal']) && $empleado[0]->nomina->emisor['RegistroPatronal'] !== '')
      $nominaEmisor['RegistroPatronal'] = $empleado[0]->nomina->emisor['RegistroPatronal'];
    if (isset($empleado[0]->nomina->emisor['RfcPatronOrigen']) && $empleado[0]->nomina->emisor['RfcPatronOrigen'] !== '')
      $nominaEmisor['RfcPatronOrigen'] = $empleado[0]->nomina->emisor['RfcPatronOrigen'];
    if (isset($empleado[0]->nomina->emisor['EntidadSNCF']))
      $nominaEmisor['OrigenRecurso'] = $empleado[0]->nomina->emisor['EntidadSNCF']['OrigenRecurso'];
      if (isset($empleado[0]->nomina->emisor['EntidadSNCF']['MontoRecursoPropio']))
        $nominaEmisor['MontoRecursoPropio'] = $empleado[0]->nomina->emisor['EntidadSNCF']['MontoRecursoPropio'];
    $nomina['Emisor'] = $nominaEmisor;

    $nominaReceptor = array();
    $nominaReceptor['Curp'] = $empleado[0]->nomina->receptor['Curp'];
    if (isset($empleado[0]->nomina->receptor['NumSeguridadSocial']) && $empleado[0]->nomina->receptor['NumSeguridadSocial'] !== '')
      $nominaReceptor['NumSeguridadSocial'] = $empleado[0]->nomina->receptor['NumSeguridadSocial'];
    if (isset($empleado[0]->nomina->receptor['FechaInicioRelLaboral']) && $empleado[0]->nomina->receptor['FechaInicioRelLaboral'] !== '')
      $nominaReceptor['FechaInicioRelLaboral'] = $empleado[0]->nomina->receptor['FechaInicioRelLaboral'];
    if (isset($empleado[0]->nomina->receptor['Antigüedad']) && $empleado[0]->nomina->receptor['Antigüedad'] !== '')
      $nominaReceptor['Antigüedad'] = $empleado[0]->nomina->receptor['Antigüedad'];
    $nominaReceptor['TipoContrato'] = $empleado[0]->nomina->receptor['TipoContrato'];
    // if (isset($empleado[0]->nomina->receptor['Sindicalizado']) && $empleado[0]->nomina->receptor['Sindicalizado'] !== '')
    //   $nominaReceptor['Sindicalizado'] = $empleado[0]->nomina->receptor['Sindicalizado'];
    if (isset($empleado[0]->nomina->receptor['TipoJornada']) && $empleado[0]->nomina->receptor['TipoJornada'] !== '')
      $nominaReceptor['TipoJornada'] = $empleado[0]->nomina->receptor['TipoJornada'];
    $nominaReceptor['TipoRegimen'] = $empleado[0]->nomina->receptor['TipoRegimen'];
    $nominaReceptor['NumEmpleado'] = $empleado[0]->nomina->receptor['NumEmpleado'];
    if (isset($empleado[0]->nomina->receptor['Departamento']) && $empleado[0]->nomina->receptor['Departamento'] !== '')
      $nominaReceptor['Departamento'] = $empleado[0]->nomina->receptor['Departamento'];
    if (isset($empleado[0]->nomina->receptor['Puesto']) && $empleado[0]->nomina->receptor['Puesto'] !== '')
      $nominaReceptor['Puesto'] = $empleado[0]->nomina->receptor['Puesto'];
    if (isset($empleado[0]->nomina->receptor['RiesgoPuesto']) && $empleado[0]->nomina->receptor['RiesgoPuesto'] !== '')
      $nominaReceptor['RiesgoPuesto'] = $empleado[0]->nomina->receptor['RiesgoPuesto'];
    $nominaReceptor['PeriodicidadPago'] = $empleado[0]->nomina->receptor['PeriodicidadPago'];
    if (isset($empleado[0]->nomina->receptor['Banco']) && $empleado[0]->nomina->receptor['Banco'] !== '')
      $nominaReceptor['Banco'] = $empleado[0]->nomina->receptor['Banco'];
    if (isset($empleado[0]->nomina->receptor['Cuenta']) && $empleado[0]->nomina->receptor['Cuenta'] !== '')
      $nominaReceptor['CuentaBancaria'] = $empleado[0]->nomina->receptor['Cuenta'];
    // if (isset($empleado[0]->nomina->receptor['SalarioBaseCotApor']) && $empleado[0]->nomina->receptor['SalarioBaseCotApor'] !== '')
    //   $nominaReceptor['SalarioBaseCotApor'] = $empleado[0]->nomina->receptor['SalarioBaseCotApor'];
    if (isset($empleado[0]->nomina->receptor['SalarioDiarioIntegrado']) && $empleado[0]->nomina->receptor['SalarioDiarioIntegrado'] > 0)
      $nominaReceptor['SalarioDiarioIntegrado'] = $empleado[0]->nomina->receptor['SalarioDiarioIntegrado'];
    $nominaReceptor['ClaveEntFed'] = $empleado[0]->nomina->receptor['ClaveEntFed'];
    $nomina['Receptor'] = $nominaReceptor;

    $nominaPercepciones = array();
    $totalPercepciones = array();
    $percepciones = array();
    if (isset($empleado[0]->nomina->percepcionesTotales['TotalSueldos']) && $empleado[0]->nomina->percepcionesTotales['TotalSueldos'] != 0)
      $totalPercepciones['TotalSueldos'] = $empleado[0]->nomina->percepcionesTotales['TotalSueldos'];
    if (isset($empleado[0]->nomina->percepcionesTotales['TotalSeparacionIndemnizacion']) && $empleado[0]->nomina->percepcionesTotales['TotalSeparacionIndemnizacion'] != 0)
      $totalPercepciones['TotalSeparacionIndemnizacion'] = $empleado[0]->nomina->percepcionesTotales['TotalSeparacionIndemnizacion'];
    // if (isset($empleado[0]->nomina->percepcionesTotales['TotalJubilacionPensionRetiro']) && $empleado[0]->nomina->percepcionesTotales['TotalJubilacionPensionRetiro'] !== '')
    //   $totalPercepciones['TotalJubilacionPensionRetiro'] = $empleado[0]->nomina->percepcionesTotales['TotalJubilacionPensionRetiro'];
    $totalPercepciones['TotalGravado'] = $empleado[0]->nomina->percepcionesTotales['TotalGravado'];
    $totalPercepciones['TotalExento'] = $empleado[0]->nomina->percepcionesTotales['TotalExento'];

    foreach ($empleado[0]->nomina->percepciones as $key => $percepcion)
    {
      if (($percepcion['total']) > 0) {
        $hrs_extrasc = $hrs_extras = array();
        if ($percepcion['TipoPercepcion'] == '019') {
          $hrs_extrasc = $percepcion['HorasExtra'];
          foreach ($percepcion['HorasExtra'] as $keyaa => $hrss) {
            $hrs_extras = array_merge($hrs_extras, array_values($hrss));
          }
          unset($percepcion['HorasExtra']);
        }
        $percepcion = [
          'TipoPercepcion' => $percepcion['TipoPercepcion'],
          'Clave'          => $percepcion['Clave'],
          'Concepto'       => $percepcion['Concepto'],
          'ImporteGravado' => $percepcion['ImporteGravado'],
          'ImporteExcento' => $percepcion['ImporteExcento'],
        ];
        $percepciones = array_merge($percepciones, array_values($percepcion), $hrs_extras);
        $percepcion['HorasExtra'] = $hrs_extrasc;
        $nomina['Percepciones'][] = $percepcion;
      }
    }
    // SeparacionIndemnizacion
    $percepcionesSeparacionIndemnizacion = array();
    if (isset($empleado[0]->nomina->percepcionesSeparacionIndemnizacion) &&
        count($empleado[0]->nomina->percepcionesSeparacionIndemnizacion) > 0) {
      $nomina['PercepcionesSeparacionIndemnizacion'] = $empleado[0]->nomina->percepcionesSeparacionIndemnizacion;
      $percepcionesSeparacionIndemnizacion = $empleado[0]->nomina->percepcionesSeparacionIndemnizacion;
    }
    $nominaPercepciones = array_merge($totalPercepciones, $percepciones, $percepcionesSeparacionIndemnizacion);
    $nomina['PercepcionesTotales'] = $totalPercepciones;

    $nominaDeducciones = array();
    $totalDeducciones = array();
    $deducciones = array();
    if (isset($empleado[0]->nomina->deduccionesTotales['TotalOtrasDeducciones']) && $empleado[0]->nomina->deduccionesTotales['TotalOtrasDeducciones'] != 0)
      $totalDeducciones['TotalOtrasDeducciones'] = $empleado[0]->nomina->deduccionesTotales['TotalOtrasDeducciones'];
    if (isset($empleado[0]->nomina->deduccionesTotales['TotalImpuestosRetenidos']) && $empleado[0]->nomina->deduccionesTotales['TotalImpuestosRetenidos'] != 0)
      $totalDeducciones['TotalImpuestosRetenidos'] = $empleado[0]->nomina->deduccionesTotales['TotalImpuestosRetenidos'];
    foreach ($empleado[0]->nomina->deducciones as $key => $deduccion)
    {
      if ($deduccion['total'] > 0) {
        $deduccion = [
          'TipoDeduccion' => $deduccion['TipoDeduccion'],
          'Clave'         => $deduccion['Clave'],
          'Concepto'      => $deduccion['Concepto'],
          'Importe'       => $deduccion['total'],
        ];
        $deducciones = array_merge($deducciones, array_values($deduccion));
        $nomina['Deducciones'][] = $deduccion;
      }
    }
    $nominaDeducciones = array_merge($totalDeducciones, array_values($deducciones));
    $nomina['DeduccionesTotales'] = $totalDeducciones;

    $otrosPagos = array();
    if (count($empleado[0]->nomina->otrosPagos) > 0) {
      foreach ($empleado[0]->nomina->otrosPagos as $otroPago)
      {
        if ($otroPago['total'] > 0) {
          $otItem = [
            'TipoOtroPago' => $otroPago['TipoOtroPago'],
            'Clave'        => $otroPago['Clave'],
            'Concepto'     => $otroPago['Concepto'],
            'Importe'      => $otroPago['total'],
          ];
          if (isset($otroPago['SubsidioAlEmpleo']['SubsidioCausado']) && $otroPago['SubsidioAlEmpleo']['SubsidioCausado'] > 0)
          {
            $otItem['SubsidioCausado'] = $otroPago['SubsidioAlEmpleo']['SubsidioCausado'];
          }
          // if (isset($otroPago['CompensacionSaldosAFavor']) && count($otroPago['CompensacionSaldosAFavor']) > 0)
          // {
          //   $otItem['CompensacionSaldosAFavor'] = [
          //     'SaldoAFavor'     => $otroPago['CompensacionSaldosAFavor']['SaldoAFavor'],
          //     'Año'             => $otroPago['CompensacionSaldosAFavor']['Año'],
          //     'RemanenteSalFav' => $otroPago['CompensacionSaldosAFavor']['RemanenteSalFav'],
          //   ];
          // }

          $otrosPagos = array_merge($otrosPagos, array_values($otItem));
          $nomina['otrosPagos'][] = $otItem;
        }
      }
    }

    // echo "<pre>";
    //   var_dump($nomina);
    // echo "</pre>";exit;

    // $nominaPercepciones = array();
    // $totalPercepciones = array('total_gravado' => 0, 'total_excento' => 0);
    // $percepciones = array();
    // foreach ($empleado[0]->nomina->percepciones as $key => $percepcion)
    // {
    //   $totalPercepcion = floatval($percepcion['ImporteGravado']) + floatval($percepcion['ImporteExcento']);

    //   if ($totalPercepcion !== floatval(0))
    //   {
    //     $percepcion['ImporteGravado'] = floatval($this->numero($percepcion['ImporteGravado']));
    //     $percepcion['ImporteExcento'] = floatval($this->numero($percepcion['ImporteExcento']));

    //     $totalPercepciones['total_gravado'] += floatval($percepcion['ImporteGravado']);
    //     $totalPercepciones['total_excento'] += floatval($percepcion['ImporteExcento']);

    //     $percepciones = array_merge($percepciones, array_values($percepcion));
    //     $nomina['Percepciones']['percepciones'][] = $percepcion;
    //   }
    // }
    // if ( floatval($totalPercepciones['total_gravado']+$totalPercepciones['total_excento']) > 0)
    // {
    //   $totalPercepciones['total_gravado'] = floatval($this->numero($totalPercepciones['total_gravado']));
    //   $totalPercepciones['total_excento'] = floatval($this->numero($totalPercepciones['total_excento']));
    //   $nominaPercepciones = array_merge($totalPercepciones, $percepciones);
    //   $nomina['Percepciones']['totales'] = $totalPercepciones;
    // }

    // $nominaDeducciones = array();
    // $totalDeducciones = array('total_gravado' => 0, 'total_excento' => 0);
    // $deducciones = array();
    // foreach ($empleado[0]->nomina->deducciones as $key => $deduccion)
    // {
    //   $totalDeduccion = floatval($deduccion['ImporteGravado']) + floatval($deduccion['ImporteExcento']);

    //   // Si el total de la deduccion no es 0.
    //   if ($totalDeduccion !== floatval(0))
    //   {
    //     $deduccion['ImporteGravado'] = (float)$this->numero($deduccion['ImporteGravado']);
    //     $deduccion['ImporteExcento'] = (float)$this->numero($deduccion['ImporteExcento']);

    //     $totalDeducciones['total_gravado'] += $deduccion['ImporteGravado'];
    //     $totalDeducciones['total_excento'] += $deduccion['ImporteExcento'];

    //     $deducciones = array_merge($deducciones, array_values($deduccion));
    //     $nomina['Deducciones']['deducciones'][] = $deduccion;
    //   }
    // }
    // if ( floatval($totalDeducciones['total_gravado']+$totalDeducciones['total_excento']) > 0)
    // {
    //   $totalDeducciones['total_gravado'] = (float)$this->numero($totalDeducciones['total_gravado']);
    //   $totalDeducciones['total_excento'] = (float)$this->numero($totalDeducciones['total_excento']);
    //   $nominaDeducciones = array_merge($totalDeducciones, array_values($deducciones));
    //   $nomina['Deducciones']['totales'] = $totalDeducciones;
    // }

    // echo "<pre>";
    //   var_dump(array_merge(array_values($nominaDatos), array_values($nominaPercepciones),  array_values($nominaDeducciones)));
    // echo "</pre>";exit;

    $nomina['Incapacidades'] = array();
    $nominaIncapacidades = array();
    $incapacidades = array();
    if (count($empleado[0]->incapacidades) > 0 && (!isset($data['is_ptu'])))
    {
      foreach ($empleado[0]->incapacidades as $incapacidad)
      {
        $incapacidades = array_merge($incapacidades, array_values($incapacidad));
        $nomina['Incapacidades'][] = $incapacidad;
      }
    }
    $nominaIncapacidades =  array_values($incapacidades);

    $cadena = array_merge(
      array_values($nominaDatos),
      array_values($nominaEmisor),
      array_values($nominaReceptor),
      array_values($nominaPercepciones),
      array_values($nominaDeducciones),
      array_values($otrosPagos),
      array_values($nominaIncapacidades)
    );

    return array('datos_cadena' => $cadena, 'nomina' => $nomina);
  }

  public function comercioExterior($data)
  {

    $response = [
      'Version'                   => '1.0',
      'TipoOperacion'             => $data['comercioExterior']['tipo_operacion'],
      'ClaveDePedimento'          => $data['comercioExterior']['clave_pedimento'],
      'CertificadoOrigen'         => $data['comercioExterior']['certificado_origen'],
      'NumCertificadoOrigen'      => $data['comercioExterior']['num_certificado_origen'],
      'NumeroExportadorConfiable' => $data['comercioExterior']['numero_exportador_confiable'],
      'Incoterm'                  => $data['comercioExterior']['incoterm'],
      'Subdivision'               => $data['comercioExterior']['subdivision'],
      'Observaciones'             => $data['comercioExterior']['observaciones'],
      'TipoCambioUSD'             => $data['comercioExterior']['tipocambio_USD'],
      'TotalUSD'                  => $data['comercioExterior']['total_USD'],

      // 'Emisor'                    => $this->emisor(),
      // 'Receptor'                  => $this->receptor(),
      // 'Destinatario'              => $this->destinatario(),
      // 'Mercancias'                => $this->mercancias(),
    ];

    if (isset($data['comercioExterior']['Emisor']['Curp']{0})) {
      $response['Emisor'] = ['Curp' => $data['comercioExterior']['Emisor']['Curp']];
    }

    $receptor = [];
    if (isset($data['comercioExterior']['Receptor']['Curp']{0})) {
      $receptor['Curp'] = $data['comercioExterior']['Receptor']['Curp'];
    }
    $receptor['NumRegIdTrib'] = $data['comercioExterior']['Receptor']['NumRegIdTrib'];
    $response['Receptor'] = $receptor;


    $destinatario = [];
    foreach ($data['comercioExterior']['Destinatario'] as $key => $value) {
      if ( !is_array($data['comercioExterior']['Destinatario'][$key]) && isset($data['comercioExterior']['Destinatario'][$key]{0}) )
        $destinatario[$key] = $data['comercioExterior']['Destinatario'][$key];
      elseif (is_array($data['comercioExterior']['Destinatario'][$key])) {
        foreach ($data['comercioExterior']['Destinatario'][$key] as $key2 => $value2) {
          if ( isset($data['comercioExterior']['Destinatario'][$key][$key2]{0}) )
            $destinatario[$key][$key2] = $data['comercioExterior']['Destinatario'][$key][$key2];
        }
      }
    }
    $domicilio = [];
    if (isset($destinatario['Domicilio'])) {
      (isset($destinatario['Domicilio']['Calle']) ? $domicilio['Calle']                   = $destinatario['Domicilio']['Calle'] : '');
      (isset($destinatario['Domicilio']['NumeroExterior']) ? $domicilio['NumeroExterior'] = $destinatario['Domicilio']['NumeroExterior'] : '');
      (isset($destinatario['Domicilio']['NumeroInterior']) ? $domicilio['NumeroInterior'] = $destinatario['Domicilio']['NumeroInterior'] : '');
      (isset($destinatario['Domicilio']['Colonia']) ? $domicilio['Colonia']               = $destinatario['Domicilio']['Colonia'] : '');
      (isset($destinatario['Domicilio']['Localidad']) ? $domicilio['Localidad']           = $destinatario['Domicilio']['Localidad'] : '');
      (isset($destinatario['Domicilio']['Referencia']) ? $domicilio['Referencia']         = $destinatario['Domicilio']['Referencia'] : '');
      (isset($destinatario['Domicilio']['Municipio']) ? $domicilio['Municipio']           = $destinatario['Domicilio']['Municipio'] : '');
      (isset($destinatario['Domicilio']['Estado']) ? $domicilio['Estado']                 = $destinatario['Domicilio']['Estado'] : '');
      (isset($destinatario['Domicilio']['Pais']) ? $domicilio['Pais']                     = $destinatario['Domicilio']['Pais'] : '');
      (isset($destinatario['Domicilio']['CodigoPostal']) ? $domicilio['CodigoPostal']     = $destinatario['Domicilio']['CodigoPostal'] : '');

      $destinatario['Domicilio'] = $domicilio;
    }
    $response['Destinatario'] = $destinatario;


    $mercancias = [];
    if (isset($data['comercioExterior']['Mercancias']) && count($data['comercioExterior']['Mercancias']) > 0)
    {
      $cont = 0;
      foreach ($data['comercioExterior']['Mercancias']['NoIdentificacion'] as $key => $value) {
        if ( isset($data['comercioExterior']['Mercancias']['NoIdentificacion'][$key]{0}) )
          $mercancias[$cont]['NoIdentificacion'] = $data['comercioExterior']['Mercancias']['NoIdentificacion'][$key];
        if ( isset($data['comercioExterior']['Mercancias']['FraccionArancelaria'][$key]{0}) )
          $mercancias[$cont]['FraccionArancelaria'] = $data['comercioExterior']['Mercancias']['FraccionArancelaria'][$key];
        if ( isset($data['comercioExterior']['Mercancias']['CantidadAduana'][$key]{0}) )
          $mercancias[$cont]['CantidadAduana'] = $data['comercioExterior']['Mercancias']['CantidadAduana'][$key];
        if ( isset($data['comercioExterior']['Mercancias']['UnidadAduana'][$key]{0}) )
          $mercancias[$cont]['UnidadAduana'] = $data['comercioExterior']['Mercancias']['UnidadAduana'][$key];
        if ( isset($data['comercioExterior']['Mercancias']['ValorUnitarioAduana'][$key]{0}) )
          $mercancias[$cont]['ValorUnitarioAduana'] = $data['comercioExterior']['Mercancias']['ValorUnitarioAduana'][$key];
        if ( isset($data['comercioExterior']['Mercancias']['ValorDolares'][$key]{0}) )
          $mercancias[$cont]['ValorDolares'] = number_format($data['comercioExterior']['Mercancias']['ValorDolares'][$key], 2, '.', '');


        if ( isset($data['comercioExterior']['Mercancias']['DescripcionesEspecificas'][$key]) && is_array($data['comercioExterior']['Mercancias']['DescripcionesEspecificas'][$key])) {
          foreach ($data['comercioExterior']['Mercancias']['DescripcionesEspecificas'][$key]['Marca'] as $key2 => $value2) {
            if ( isset($data['comercioExterior']['Mercancias']['DescripcionesEspecificas'][$key]['Marca'][$key2]{0}) )
              $mercancias[$cont]['DescripcionesEspecificas'][$key2]['Marca'] = $data['comercioExterior']['Mercancias']['DescripcionesEspecificas'][$key]['Marca'][$key2];
            if ( isset($data['comercioExterior']['Mercancias']['DescripcionesEspecificas'][$key]['Modelo'][$key2]{0}) )
              $mercancias[$cont]['DescripcionesEspecificas'][$key2]['Modelo'] = $data['comercioExterior']['Mercancias']['DescripcionesEspecificas'][$key]['Modelo'][$key2];
            if ( isset($data['comercioExterior']['Mercancias']['DescripcionesEspecificas'][$key]['SubModelo'][$key2]{0}) )
              $mercancias[$cont]['DescripcionesEspecificas'][$key2]['SubModelo'] = $data['comercioExterior']['Mercancias']['DescripcionesEspecificas'][$key]['SubModelo'][$key2];
            if ( isset($data['comercioExterior']['Mercancias']['DescripcionesEspecificas'][$key]['NumeroSerie'][$key2]{0}) )
              $mercancias[$cont]['DescripcionesEspecificas'][$key2]['NumeroSerie'] = $data['comercioExterior']['Mercancias']['DescripcionesEspecificas'][$key]['NumeroSerie'][$key2];
          }
        }

        ++$cont;
      }
    }
    $response['Mercancias'] = $mercancias;


    if (!isset($response['ClaveDePedimento']) || $response['ClaveDePedimento'] == '')
      unset($response['ClaveDePedimento']);

    if (!isset($response['CertificadoOrigen']) || $response['CertificadoOrigen'] == '')
      unset($response['CertificadoOrigen']);

    if (!isset($response['NumCertificadoOrigen']) || $response['NumCertificadoOrigen'] == '')
      unset($response['NumCertificadoOrigen']);

    if (!isset($response['NumeroExportadorConfiable']) || $response['NumeroExportadorConfiable'] == '')
      unset($response['NumeroExportadorConfiable']);

    if (!isset($response['Incoterm']) || $response['Incoterm'] == '')
      unset($response['Incoterm']);

    if (!isset($response['Subdivision']) || $response['Subdivision'] == '')
      unset($response['Subdivision']);

    if (!isset($response['Observaciones']) || $response['Observaciones'] == '')
      unset($response['Observaciones']);

    if (!isset($response['TipoCambioUSD']) || $response['TipoCambioUSD'] == '')
      unset($response['TipoCambioUSD']);

    if (!isset($response['TotalUSD']) || $response['TotalUSD'] == '')
      unset($response['TotalUSD']);
    else
      $response['TotalUSD'] = number_format($data['comercioExterior']['total_USD'], 2, '.', '');

    // if (count($response['Emisor']) == 0)
    //   unset($response['Emisor']);

    if (count($response['Receptor']) == 0)
      unset($response['Receptor']);

    if (count($response['Destinatario']) == 0)
      unset($response['Destinatario']);

    if (count($response['Mercancias']) == 0)
      unset($response['Mercancias']);

    return $response;
  }

  public function obtenDatosCfdi40($data, $productosApi, $id_nc = false)
  {
    $CI =& get_instance();

    // Obtiene el ID de la empresa que emite la factura, si no llega
    // entonces obtiene el ID por default.
    // $id_empresa = isset($data['id_empresa']) ? $data['id_empresa'] : $this->default_id_empresa;
    $id = isset($data['did_empresa']) ? $data['did_empresa'] : $this->default_id_empresa;

    // Carga los datos de la empresa que emite la factura.
    $this->cargaDatosFiscales($id, 'empresas');

    // Obtiene los datos del receptor.
    $CI->load->model('clientes_model');
    $cliente = $CI->clientes_model->getClienteInfo($_POST['did_cliente'], true);

    if ($id_nc) {
      // Obtiene los datos de la factura.
      $CI->load->model('facturacion_model');
      $factura = $CI->facturacion_model->getInfoFactura($id_nc, true);

      if ($factura['info']->tipo_comprobante === 'traslado') { // Traslados
        $cfdiRel = array(
          [
            'tipoRelacion' => '06',
            'cfdiRelacionado' => array(
              array(
                'uuid' => $factura['info']->uuid,
              )
            )
          ]
        );
      } else { // Notas de credito
        $cfdiRel = array(
          [
            'tipoRelacion' => '01',
            'cfdiRelacionado' => array(
              array(
                'uuid' => $factura['info']->uuid,
              )
            )
          ]
        );
      }
    }

    if (isset($data['cfdiRelPrev']) && $data['cfdiRelPrev'] != '') {
      $cfdiRel = array(
        [
          'tipoRelacion' => (!empty($data['cfdiRelPrevTipo'])? $data['cfdiRelPrevTipo']: '04'),
          'cfdiRelacionado' => array(
            array(
              'uuid' => $data['cfdiRelPrev'],
            )
          )
        ]
      );
    }

    // $CI->load->model('catalogos33_model');
    // $this->regimen_fiscal = $CI->catalogos33_model->regimenFiscales($this->regimen_fiscal);

    $tipoComprobante = 'I';
    if ($data['dtipo_comprobante'] == 'ingreso')
      $tipoComprobante = 'I';
    elseif ($data['dtipo_comprobante'] == 'egreso')
      $tipoComprobante = 'E';
    elseif ($data['dtipo_comprobante'] == 'traslado')
      $tipoComprobante = 'T';
    elseif ($data['dtipo_comprobante'] == 'nomina')
      $tipoComprobante = 'N';

    $nombre_receptor = $cliente['info']->nombre_fiscal;
    if ($cliente['info']->rfc == 'XAXX010101000') {
      $nombre_receptor = 'PUBLICO EN GENERAL';
    }


    $datosApi = array(
      'emisor' => array(
        'nombreFiscal'  => $this->nombre_fiscal,
        'rfc'           => $this->rfc,
        'calle'         => $this->calle,
        'noExterior'    => $this->no_exterior,
        'noInterior'    => $this->no_interior,
        'colonia'       => $this->colonia,
        'localidad'     => $this->localidad,
        'municipio'     => $this->municipio,
        'estado'        => $this->estado,
        'pais'          => $this->pais,
        'cp'            => $this->cp,
        'curp'          => $this->curp,
        'regimenFiscal' => $this->regimen_fiscal,
        'cer'           => $this->obtenCer($this->path_certificado),
        'key'           => $this->obtenKey($this->path_key),
      ),
      'receptor' => array(
        'nombreFiscal' => $nombre_receptor,
        'rfc'          => $cliente['info']->rfc,
        'calle'        => $cliente['info']->calle,
        'noExterior'   => $cliente['info']->no_exterior,
        'noInterior'   => $cliente['info']->no_interior,
        'colonia'      => $cliente['info']->colonia,
        'localidad'    => $cliente['info']->localidad,
        'municipio'    => $cliente['info']->municipio,
        'estado'       => $cliente['info']->estado,
        'pais'         => $cliente['info']->pais,
        'cp'           => $cliente['info']->cp,
        'regimenFiscal' => $cliente['info']->regimen_fiscal,
      ),
      'serie'             => $data['dserie'],
      'folio'             => $data['dfolio'],
      'fecha'             => $data['dfecha'].date(':s'),
      'formaDePago'       => $data['dforma_pago'],
      'condicionesDePago' => $data['dcondicion_pago'] == 'cr'? 'CREDITO': 'CONTADO',
      'moneda'            => $data['moneda'],
      'tipoCambio'        => $data['tipoCambio']? $data['tipoCambio']: 1,
      'tipoDeComprobante' => $tipoComprobante,
      'metodoDePago'      => $data['dmetodo_pago'],
      'confirmacion'      => '',
      'usoCfdi'           => $data['duso_cfdi'],
      'noCertificado'     => $data['dno_certificado'],
      'totalImporte'      => $data['total_subtotal'],
      'descuento'         => '0',
      'total'             => $data['total_totfac'],
      'exportacion'       => $data['exportacion'],
      'trasladosImporte'  => array(
        'iva'  => $data['total_iva'],
        'ieps' => floatval((isset($data['total_ieps'])? $data['total_ieps']: 0))
      ),
      'retencionesImporte'  => array(
        'iva' => $data['total_retiva']
      ),
      'productos' => $productosApi
    );

    if (isset($cfdiRel) && $cfdiRel) {
      $datosApi['cfdiRelacionados'] = $cfdiRel;
    }

    if ($cliente['info']->rfc == 'XAXX010101000') {
      $datosApi['informacionGlobal'] = [
        'periodicidad' => (!empty($data['ig_periodicidad'])? $data['ig_periodicidad']: ''),
        'meses' => (!empty($data['ig_meses'])? $data['ig_meses']: ''),
        'anio' => (!empty($data['ig_anio'])? $data['ig_anio']: ''),
      ];
    }

    if ($tipoComprobante === 'T') {
      $datosApi['formaDePago'] = '';
      $datosApi['metodoDePago'] = '';
    }

    if (!empty($_POST['comercioExterior']['tipoOperacion']) ||
        !empty($_POST['comercioExterior']['clavePedimento']) ||
        !empty($_POST['comercioExterior']['certificadoOrigen']) ) {
      $datosApi['comercioExterior'] = $_POST['comercioExteriorPros'];

      $datosApi['emisor']['calle']      = $datosApi['comercioExterior']['emisor']['domicilio']['calle'];
      $datosApi['emisor']['noExterior'] = $datosApi['comercioExterior']['emisor']['domicilio']['numeroExterior'];
      $datosApi['emisor']['noInterior'] = $datosApi['comercioExterior']['emisor']['domicilio']['numeroInterior'];
      $datosApi['emisor']['colonia']    = $datosApi['comercioExterior']['emisor']['domicilio']['colonia'];
      $datosApi['emisor']['localidad']  = $datosApi['comercioExterior']['emisor']['domicilio']['localidad'];
      $datosApi['emisor']['municipio']  = $datosApi['comercioExterior']['emisor']['domicilio']['municipio'];
      $datosApi['emisor']['estado']     = $datosApi['comercioExterior']['emisor']['domicilio']['estado'];
      $datosApi['emisor']['pais']       = $datosApi['comercioExterior']['emisor']['domicilio']['pais'];
      $datosApi['emisor']['cp']         = $datosApi['comercioExterior']['emisor']['domicilio']['codigoPostal'];

      $datosApi['receptor']['numRegIdTrib'] = $datosApi['comercioExterior']['receptor']['numRegIdTrib'];
      $datosApi['comercioExterior']['receptor']['numRegIdTrib'] = '';

      if ($tipoComprobante !== 'T')
        unset($datosApi['comercioExterior']['propietario']);
    }

    if (!empty($_POST['cp']['ubicaciones']) &&
        !empty($_POST['cp']['mercancias']['mercancias']) &&
        !empty($_POST['cp']['figuraTransporte']['tiposFigura']) &&
        count($_POST['cp']['ubicaciones']) > 0 &&
        count($_POST['cp']['mercancias']['mercancias']) > 0 &&
        count($_POST['cp']['figuraTransporte']['tiposFigura']) > 0 ) {
      $datosApi['cartaPorteSat'] = $_POST['cp'];
    }

    return $datosApi;
  }

  public function obtenDatosCfdi33($data, $productosApi, $id_nc = false)
  {
    $CI =& get_instance();

    // Obtiene el ID de la empresa que emite la factura, si no llega
    // entonces obtiene el ID por default.
    // $id_empresa = isset($data['id_empresa']) ? $data['id_empresa'] : $this->default_id_empresa;
    $id = isset($data['did_empresa']) ? $data['did_empresa'] : $this->default_id_empresa;

    // Carga los datos de la empresa que emite la factura.
    $this->cargaDatosFiscales($id, 'empresas');

    // Obtiene los datos del receptor.
    $CI->load->model('clientes_model');
    $cliente = $CI->clientes_model->getClienteInfo($_POST['did_cliente'], true);

    if ($id_nc) {
      // Obtiene los datos de la factura.
      $CI->load->model('facturacion_model');
      $factura = $CI->facturacion_model->getInfoFactura($id_nc, true);

      if ($factura['info']->tipo_comprobante === 'traslado') { // Traslados
        $cfdiRel = array(
          'tipoRelacion' => '06',
          'cfdiRelacionado' => array(
            array(
              'uuid' => $factura['info']->uuid,
            )
          ),
        );
      } else { // Notas de credito
        $cfdiRel = array(
          'tipoRelacion' => '01',
          'cfdiRelacionado' => array(
            array(
              'uuid' => $factura['info']->uuid,
            )
          ),
        );
      }
    }

    if (isset($data['cfdiRelPrev']) && $data['cfdiRelPrev'] != '') {
      $cfdiRel = array(
        'tipoRelacion' => (!empty($data['cfdiRelPrevTipo'])? $data['cfdiRelPrevTipo']: '04'),
        'cfdiRelacionado' => array(
          array(
            'uuid' => $data['cfdiRelPrev'],
          )
        )
      );
    }

    // $CI->load->model('catalogos33_model');
    // $this->regimen_fiscal = $CI->catalogos33_model->regimenFiscales($this->regimen_fiscal);

    $tipoComprobante = 'I';
    if ($data['dtipo_comprobante'] == 'ingreso')
      $tipoComprobante = 'I';
    elseif ($data['dtipo_comprobante'] == 'egreso')
      $tipoComprobante = 'E';
    elseif ($data['dtipo_comprobante'] == 'traslado')
      $tipoComprobante = 'T';
    elseif ($data['dtipo_comprobante'] == 'nomina')
      $tipoComprobante = 'N';


    $datosApi = array(
      'emisor' => array(
        'nombreFiscal'  => $this->nombre_fiscal,
        'rfc'           => $this->rfc,
        'calle'         => $this->calle,
        'noExterior'    => $this->no_exterior,
        'noInterior'    => $this->no_interior,
        'colonia'       => $this->colonia,
        'localidad'     => $this->localidad,
        'municipio'     => $this->municipio,
        'estado'        => $this->estado,
        'pais'          => $this->pais,
        'cp'            => $this->cp,
        'regimenFiscal' => $this->regimen_fiscal,
        'cer'           => $this->obtenCer($this->path_certificado),
        'key'           => $this->obtenKey($this->path_key),
      ),
      'receptor' => array(
        'nombreFiscal' => $cliente['info']->nombre_fiscal,
        'rfc'          => $cliente['info']->rfc,
        'calle'        => $cliente['info']->calle,
        'noExterior'   => $cliente['info']->no_exterior,
        'noInterior'   => $cliente['info']->no_interior,
        'colonia'      => $cliente['info']->colonia,
        'localidad'    => $cliente['info']->localidad,
        'municipio'    => $cliente['info']->municipio,
        'estado'       => $cliente['info']->estado,
        'pais'         => $cliente['info']->pais,
        'cp'           => $cliente['info']->cp,
      ),
      'serie'             => $data['dserie'],
      'folio'             => $data['dfolio'],
      'fecha'             => $data['dfecha'].date(':s'),
      'formaDePago'       => $data['dforma_pago'],
      'condicionesDePago' => $data['dcondicion_pago'] == 'cr'? 'CREDITO': 'CONTADO',
      'moneda'            => $data['moneda'],
      'tipoCambio'        => $data['tipoCambio']? $data['tipoCambio']: 1,
      'tipoDeComprobante' => $tipoComprobante,
      'metodoDePago'      => $data['dmetodo_pago'],
      'confirmacion'      => '',
      'usoCfdi'           => $data['duso_cfdi'],
      'noCertificado'     => $data['dno_certificado'],
      'totalImporte'      => $data['total_subtotal'],
      'descuento'         => '0',
      'total'             => $data['total_totfac'],
      'trasladosImporte'  => array(
        'iva'  => $data['total_iva'],
        'ieps' => floatval((isset($data['total_ieps'])? $data['total_ieps']: 0))
      ),
      'retencionesImporte'  => array(
        'iva' => $data['total_retiva'],
        'isr'  => floatval((isset($data['total_isr'])? $data['total_isr']: 0))
      ),
      'productos' => $productosApi
    );

    if (isset($cfdiRel) && $cfdiRel) {
      $datosApi['cfdiRelacionados'] = $cfdiRel;
    }

    if ($tipoComprobante === 'T') {
      $datosApi['formaDePago'] = '';
      $datosApi['metodoDePago'] = '';
    }

    if (!empty($_POST['comercioExterior']['tipoOperacion']) ||
        !empty($_POST['comercioExterior']['clavePedimento']) ||
        !empty($_POST['comercioExterior']['certificadoOrigen']) ) {
      $datosApi['comercioExterior'] = $_POST['comercioExteriorPros'];

      $datosApi['emisor']['calle']      = $datosApi['comercioExterior']['emisor']['domicilio']['calle'];
      $datosApi['emisor']['noExterior'] = $datosApi['comercioExterior']['emisor']['domicilio']['numeroExterior'];
      $datosApi['emisor']['noInterior'] = $datosApi['comercioExterior']['emisor']['domicilio']['numeroInterior'];
      $datosApi['emisor']['colonia']    = $datosApi['comercioExterior']['emisor']['domicilio']['colonia'];
      $datosApi['emisor']['localidad']  = $datosApi['comercioExterior']['emisor']['domicilio']['localidad'];
      $datosApi['emisor']['municipio']  = $datosApi['comercioExterior']['emisor']['domicilio']['municipio'];
      $datosApi['emisor']['estado']     = $datosApi['comercioExterior']['emisor']['domicilio']['estado'];
      $datosApi['emisor']['pais']       = $datosApi['comercioExterior']['emisor']['domicilio']['pais'];
      $datosApi['emisor']['cp']         = $datosApi['comercioExterior']['emisor']['domicilio']['codigoPostal'];

      $datosApi['receptor']['numRegIdTrib'] = $datosApi['comercioExterior']['receptor']['numRegIdTrib'];
      $datosApi['comercioExterior']['receptor']['numRegIdTrib'] = '';

      if ($tipoComprobante !== 'T')
        unset($datosApi['comercioExterior']['propietario']);
    }

    if (!empty($_POST['cp']['ubicaciones']) &&
        !empty($_POST['cp']['mercancias']['mercancias']) &&
        !empty($_POST['cp']['figuraTransporte']['tiposFigura']) &&
        count($_POST['cp']['ubicaciones']) > 0 &&
        count($_POST['cp']['mercancias']['mercancias']) > 0 &&
        count($_POST['cp']['figuraTransporte']['tiposFigura']) > 0 ) {
      $datosApi['cartaPorteSat'] = $_POST['cp'];
    }

    return $datosApi;
  }

  public function obtenDatosCfdi33ComP($data, $cuentaCliente, $folio, $cfdiRelacionados = null, $posts = [])
  {
    // echo "<pre>";
    //   var_dump($data, $cuentaCliente);
    // echo "</pre>";exit;
    $CI =& get_instance();

    // Obtiene el ID de la empresa que emite la factura, si no llega
    // entonces obtiene el ID por default.
    $id = isset($data[0]->id_empresa) ? $data[0]->id_empresa : $this->default_id_empresa;
    // Carga los datos de la empresa que emite la factura.
    $this->cargaDatosFiscales($id, 'empresas');

    // Obtiene los datos del receptor.
    $CI->load->model('clientes_model');
    $cliente = $CI->clientes_model->getClienteInfo($data[0]->id_cliente, true);

    $CI->load->model('cuentas_cobrar_model');

    $cfdi_ext = json_decode($data[0]->cfdi_ext);

    $formaDePago = '03';
    if ($data[0]->forma_pago == 'transferencia')
      $formaDePago = '03';
    elseif ($data[0]->forma_pago == 'cheque')
      $formaDePago = '02';
    elseif ($data[0]->forma_pago == 'efectivo')
      $formaDePago = '01';

    $nombreBancoOrdExt = '';
    if ($cuentaCliente->rfc === 'XEXX010101000') {
      $cuentaCliente->rfc = '';
      $nombreBancoOrdExt = $cuentaCliente->banco;
    }

    if ($cfdiRelacionados != null) {
      $cfdiRel = array(
        'tipoRelacion' => $cfdiRelacionados['tipo'],
        'cfdiRelacionado' => array(),
      );
      if (isset($cfdiRelacionados['uuids'])) {
        foreach ($cfdiRelacionados['uuids'] as $key => $uuid) {
          $cfdiRel['cfdiRelacionado'][] = array(
            'uuid' => $uuid,
          );
        }
      }
    }

    $override_monto = true;
    $pago_moneda      = (isset($cfdi_ext->moneda)? $cfdi_ext->moneda: 'MXN');
    $pago_tipo_cambio = (isset($cfdi_ext->tipoCambio)? $cfdi_ext->tipoCambio: 1);
    $pago_monto       = $data[0]->pago;
    if (!empty($posts['moneda'])) {
      $pago_moneda = $posts['moneda'];
      $pago_tipo_cambio = (!empty($posts['tipoCambio']) && $posts['tipoCambio']>0? $posts['tipoCambio']: $pago_tipo_cambio);

      if ($pago_moneda == 'USD') {
        // if ($posts['tipoCambio']>0) {
        //   $override_monto = false;
        // }

        $pago_monto = number_format($data[0]->pago/$pago_tipo_cambio, 2, '.', '');
      }
    }
    $comPago = [
      'cadenaPago'        => "",
      'certificadoPago'   => "",
      'cuentaBen'         => $formaDePago != '01'? $data[0]->num_cuenta: '',
      'cuentaOrd'         => $cuentaCliente? $cuentaCliente->cuenta : '',
      'fechaPago'         => str_replace(' ', 'T', substr($data[0]->fecha, 0, 19)),
      'formaDePago'       => $formaDePago,
      'moneda'            => $pago_moneda,
      'monto'             => $pago_monto,
      'nombreBancoOrdExt' => $nombreBancoOrdExt,
      'numOperacion'      => "1",
      'rfcEmisorCtaBen'   => $formaDePago != '01'? $data[0]->rfc: '',
      'rfcEmisorCtaOrd'   => $cuentaCliente? $cuentaCliente->rfc : '',
      'selloPago'         => "",
      'tipoCadPago'       => "",
      'tipoCambio'        => $pago_tipo_cambio,
      'doctoRelacionado'  => []
    ];
    $firstCfdiRel = (isset($cfdiRel['cfdiRelacionado']) && count($cfdiRel['cfdiRelacionado']) == 0);
    $monto = 0;
    foreach ($data as $key => $pago) {
      if (floatval($pago->version) >= 3.2) {
        if ($firstCfdiRel) { // cuando es la primera ves
          $cfdiRel['cfdiRelacionado'][] = array(
            'uuid' => $pago->uuid,
          );
        }

        $saldo_factura = $CI->cuentas_cobrar_model->getDetalleVentaFacturaData($pago->id_factura, 'f', true, true);
        $saldo_factura['saldo'] = floor($saldo_factura['saldo']*100)/100;
        $saldo_factura['saldo'] = $saldo_factura['saldo']<0? 0: $saldo_factura['saldo'];
        $saldoAnt = ($saldo_factura['saldo']+$pago->pago_factura);
        $metodoDePago = $pago->metodo_pago;
        // if ($saldo_factura['saldo'] == 0 && $pago->parcialidades == 1)
        //   $metodoDePago = 'PUE';

        $pago->tipo_cambio = floatval($pago->tipo_cambio);
        $pago->tipo_cambio = $pago->tipo_cambio > 0? $pago->tipo_cambio: 1;
        $pagado = number_format($pago->pago_factura/$pago->tipo_cambio, 2, '.', '');
        $comPago['doctoRelacionado'][] = array(
          "idDocumento"    => $pago->uuid,
          "serie"          => $pago->serie,
          "folio"          => $pago->folio,
          "moneda"         => (floatval($pago->version) > 3.2? $pago->moneda: 'MXN'),
          "tipoCambio"     => number_format($pago->tipo_cambio, 2, '.', ''),
          "metodoDePago"   => (floatval($pago->version) > 3.2? $metodoDePago: 'PUE'),
          "numParcialidad" => $pago->parcialidades,
          "saldoAnterior"  => number_format($saldoAnt/$pago->tipo_cambio, 2, '.', ''),
          "importePagado"  => $pagado,
          "saldoInsoluto"  => number_format($saldo_factura['saldo']/$pago->tipo_cambio, 2, '.', '')
        );
        $monto += $pagado;
      }
    }

    if ($override_monto) {
      $comPago['monto'] = $monto;
    }

    $noCertificado = $this->obtenNoCertificado();

    // xml 3.3
    $datosApi = array(
      'emisor' => array(
        'nombreFiscal'  => $this->nombre_fiscal,
        'rfc'           => $this->rfc,
        'calle'         => $this->calle,
        'noExterior'    => $this->no_exterior,
        'noInterior'    => $this->no_interior,
        'colonia'       => $this->colonia,
        'localidad'     => $this->localidad,
        'municipio'     => $this->municipio,
        'estado'        => $this->estado,
        'pais'          => $this->pais,
        'cp'            => $this->cp,
        'regimenFiscal' => $this->regimen_fiscal,
        'cer'           => $this->obtenCer($this->path_certificado),
        'key'           => $this->obtenKey($this->path_key),
      ),
      'receptor' => array(
        'nombreFiscal' => $cliente['info']->nombre_fiscal,
        'rfc'          => $cliente['info']->rfc,
        'calle'        => $cliente['info']->calle,
        'noExterior'   => $cliente['info']->no_exterior,
        'noInterior'   => $cliente['info']->no_interior,
        'colonia'      => $cliente['info']->colonia,
        'localidad'    => $cliente['info']->localidad,
        'municipio'    => $cliente['info']->municipio,
        'estado'       => $cliente['info']->estado,
        'pais'         => $cliente['info']->pais,
        'cp'           => $cliente['info']->cp,
      ),
      'serie'             => 'P',
      'folio'             => $folio,
      'fecha'             => date("Y-m-d\TH:i:s"),
      'formaDePago'       => '99',
      'condicionesDePago' => 'CONTADO',
      'moneda'            => 'XXX',
      'tipoCambio'        => '1',
      'tipoDeComprobante' => 'P',
      'metodoDePago'      => 'PUE',
      'confirmacion'      => '',
      'usoCfdi'           => 'P01',
      'noCertificado'     => $noCertificado,
      'totalImporte'      => '0',
      'descuento'         => '0',
      'total'             => '0',
      'trasladosImporte'  => array(
        'iva' => '0'
      ),
      'productos' => [
        array(
          'claveProdServ'           => '84111506',
          'claveUnidad'             => 'ACT',
          'unidad'                  => 'ACT',
          'cantidad'                => '1',
          'concepto'                => 'Pago',
          'cuentaPredial'           => '',
          'descuentoProd'           => '0',
          'descuentoProdPorcent'    => '0',
          'importe'                 => '0',
          'noIdentificacion'        => '',
          'retencionCedular'        => '0',
          'retencionCedularPorcent' => '0',
          'retencionIsr'            => '0',
          'retencionIsrPorcent'     => '0',
          'retencionIva'            => '0',
          'retencionIvaPorcent'     => '0',
          'retencionIvc'            => '0',
          'retencionIvcPorcent'     => '0',
          'trasladoCedular'         => '0',
          'trasladoCedularPorcent'  => '0',
          'trasladoIeps'            => '0',
          'trasladoIepsPorcent'     => '0',
          'trasladoIsh'             => '0',
          'trasladoIshPorcent'      => '0',
          'trasladoIva'             => '0',
          'trasladoIvaPorcent'      => '0',
          'valorUnitario'           => '0',
        )
      ],
      'pagos' => [$comPago]
    );
    if (isset($cfdiRel)) {
      $datosApi['cfdiRelacionados'] = $cfdiRel;
    }

    return $datosApi;
  }

  public function obtenDatosCfdi40ComP($data, $cuentaCliente, $folio, $cfdiRelacionados = null, $posts = [])
  {
    // echo "<pre>";
    //   var_dump($data, $cuentaCliente);
    // echo "</pre>";exit;
    $CI =& get_instance();

    // Obtiene el ID de la empresa que emite la factura, si no llega
    // entonces obtiene el ID por default.
    $id = isset($data[0]->id_empresa) ? $data[0]->id_empresa : $this->default_id_empresa;
    // Carga los datos de la empresa que emite la factura.
    $this->cargaDatosFiscales($id, 'empresas');

    // Obtiene los datos del receptor.
    $CI->load->model('clientes_model');
    $cliente = $CI->clientes_model->getClienteInfo($data[0]->id_cliente, true);

    $CI->load->model('cuentas_cobrar_model');

    $cfdi_ext = json_decode($data[0]->cfdi_ext);

    $formaDePago = '03';
    if ($data[0]->forma_pago == 'transferencia')
      $formaDePago = '03';
    elseif ($data[0]->forma_pago == 'cheque')
      $formaDePago = '02';
    elseif ($data[0]->forma_pago == 'efectivo')
      $formaDePago = '01';

    $nombreBancoOrdExt = '';
    if ($cuentaCliente->rfc === 'XEXX010101000') {
      $cuentaCliente->rfc = '';
      $nombreBancoOrdExt = $cuentaCliente->banco;
    }

    if ($cfdiRelacionados != null) {
      $cfdiRel = array(
        [
          'tipoRelacion' => $cfdiRelacionados['tipo'],
          'cfdiRelacionado' => array(),
        ]
      );
      if (isset($cfdiRelacionados['uuids'])) {
        foreach ($cfdiRelacionados['uuids'] as $key => $uuid) {
          $cfdiRel[0]['cfdiRelacionado'][] = array(
            'uuid' => $uuid,
          );
        }
      }
    }

    $override_monto = true;
    $pago_moneda      = (isset($cfdi_ext->moneda)? $cfdi_ext->moneda: 'MXN');
    $pago_tipo_cambio = (isset($cfdi_ext->tipoCambio)? $cfdi_ext->tipoCambio: 1);
    $pago_monto       = $data[0]->pago;
    if (!empty($posts['moneda'])) {
      $pago_moneda = $posts['moneda'];
      $pago_tipo_cambio = (!empty($posts['tipoCambio']) && $posts['tipoCambio']>0? $posts['tipoCambio']: $pago_tipo_cambio);

      if ($pago_moneda == 'USD') {
        // if ($posts['tipoCambio']>0) {
        //   $override_monto = false;
        // }

        $pago_monto = number_format($data[0]->pago/$pago_tipo_cambio, 2, '.', '');
      }
    }
    $comPago = [
      'cadenaPago'        => "",
      'certificadoPago'   => "",
      'cuentaBen'         => $formaDePago != '01'? $data[0]->num_cuenta: '',
      'cuentaOrd'         => $cuentaCliente? $cuentaCliente->cuenta : '',
      'fechaPago'         => str_replace(' ', 'T', substr($data[0]->fecha, 0, 19)),
      'formaDePago'       => $formaDePago,
      'moneda'            => $pago_moneda,
      'monto'             => $pago_monto,
      'nombreBancoOrdExt' => $nombreBancoOrdExt,
      'numOperacion'      => "1",
      'rfcEmisorCtaBen'   => $formaDePago != '01'? $data[0]->rfc: '',
      'rfcEmisorCtaOrd'   => $cuentaCliente? $cuentaCliente->rfc : '',
      'selloPago'         => "",
      'tipoCadPago'       => "",
      'tipoCambio'        => $pago_tipo_cambio,
      'doctoRelacionado'  => []
    ];

    // $firstCfdiRel = (isset($cfdiRel['cfdiRelacionado']) && count($cfdiRel['cfdiRelacionado']) == 0);
    $monto = 0;
    foreach ($data as $key => $pago) {
      if (floatval($pago->version) >= 3.2) {
        // if ($firstCfdiRel) { // cuando es la primera ves
        //   $cfdiRel['cfdiRelacionado'][] = array(
        //     'uuid' => $pago->uuid,
        //   );
        // }

        $saldo_factura = $CI->cuentas_cobrar_model->getDetalleVentaFacturaData($pago->id_factura, 'f', true, true);
        $saldo_factura['saldo'] = floor($saldo_factura['saldo']*100)/100;
        $saldo_factura['saldo'] = $saldo_factura['saldo']<0? 0: $saldo_factura['saldo'];
        $saldoAnt = ($saldo_factura['saldo']+$pago->pago_factura);
        $metodoDePago = $pago->metodo_pago;
        // if ($saldo_factura['saldo'] == 0 && $pago->parcialidades == 1)
        //   $metodoDePago = 'PUE';

        $pago->tipo_cambio = floatval($pago->tipo_cambio);
        $pago->tipo_cambio = $pago->tipo_cambio > 0? $pago->tipo_cambio: 1;
        $pagado = number_format($pago->pago_factura/$pago->tipo_cambio, 2, '.', '');
        $imps = [
          'iva' => ['tipo' => 't', 'imps' => '002'],
          'ret_iva' => ['tipo' => 'r', 'imps' => '002'],
          'ieps' => ['tipo' => 't', 'imps' => '003'],
          'isr' => ['tipo' => 'r', 'imps' => '001'],
        ];
        $impuestos = [
          "retenciones"  => [],
          "traslados"    => []
        ];
        foreach ($pago->impuestos as $keyim => $imp) {
          $impuestos[(($imps[$imp['impuesto']]['tipo'] === 't')? 'traslados': 'retenciones')][] = [
            "base" => number_format($imp['base']/$pago->tipo_cambio, 2, '.', ''),
            "importe" => number_format($imp['importe']/$pago->tipo_cambio, 2, '.', ''),
            "impuesto" => $imps[$imp['impuesto']]['imps'],
            "tasaOCuota" => $imp['percent'],
            "tipoFactor" => "Tasa"
          ];
        }

        $equivalencia = $pago_tipo_cambio > 1? 1/$pago_tipo_cambio: $pago_tipo_cambio;
        $comPago['doctoRelacionado'][] = array(
          "idDocumento"    => $pago->uuid,
          "serie"          => $pago->serie,
          "folio"          => $pago->folio,
          "moneda"         => (floatval($pago->version) > 3.2? $pago->moneda: 'MXN'),
          "equivalencia"   => ($pago_moneda != $pago->moneda? number_format($equivalencia, 6, '.', ''): 1),
          "metodoDePago"   => (floatval($pago->version) > 3.2? $metodoDePago: 'PUE'),
          "numParcialidad" => $pago->parcialidades,
          "saldoAnterior"  => number_format($saldoAnt/$pago->tipo_cambio, 2, '.', ''),
          "importePagado"  => $pagado,
          "saldoInsoluto"  => number_format($saldo_factura['saldo']/$pago->tipo_cambio, 2, '.', ''),
          "objetoImp"      => "02",
          "impuestos"      => $impuestos
        );
        $monto += $pagado;
      }
    }

    if ($override_monto) {
      $comPago['monto'] = bcdiv($monto, 1, 2);
    }

    $noCertificado = $this->obtenNoCertificado();

    // xml 3.3
    $datosApi = array(
      'emisor' => array(
        'nombreFiscal'  => $this->nombre_fiscal,
        'rfc'           => $this->rfc,
        'calle'         => $this->calle,
        'noExterior'    => $this->no_exterior,
        'noInterior'    => $this->no_interior,
        'colonia'       => $this->colonia,
        'localidad'     => $this->localidad,
        'municipio'     => $this->municipio,
        'estado'        => $this->estado,
        'pais'          => $this->pais,
        'cp'            => $this->cp,
        'regimenFiscal' => $this->regimen_fiscal,
        'cer'           => $this->obtenCer($this->path_certificado),
        'key'           => $this->obtenKey($this->path_key),
      ),
      'receptor' => array(
        'nombreFiscal' => $cliente['info']->nombre_fiscal,
        'rfc'          => $cliente['info']->rfc,
        'calle'        => $cliente['info']->calle,
        'noExterior'   => $cliente['info']->no_exterior,
        'noInterior'   => $cliente['info']->no_interior,
        'colonia'      => $cliente['info']->colonia,
        'localidad'    => $cliente['info']->localidad,
        'municipio'    => $cliente['info']->municipio,
        'estado'       => $cliente['info']->estado,
        'pais'         => $cliente['info']->pais,
        'cp'           => $cliente['info']->cp,
        'regimenFiscal' => $cliente['info']->regimen_fiscal,
      ),
      'serie'             => 'P',
      'folio'             => $folio,
      'fecha'             => date("Y-m-d\TH:i:s"),
      'formaDePago'       => '99',
      'condicionesDePago' => 'CONTADO',
      'moneda'            => 'XXX',
      'tipoCambio'        => '1',
      'tipoDeComprobante' => 'P',
      'metodoDePago'      => 'PUE',
      'confirmacion'      => '',
      'usoCfdi'           => 'P01',
      'noCertificado'     => $noCertificado,
      'totalImporte'      => '0',
      'descuento'         => '0',
      'total'             => '0',
      'exportacion'       => '01',
      'trasladosImporte'  => array(
        'iva' => '0'
      ),
      'productos' => [
        array(
          'claveProdServ'           => '84111506',
          'claveUnidad'             => 'ACT',
          'unidad'                  => 'ACT',
          'cantidad'                => '1',
          'concepto'                => 'Pago',
          'cuentaPredial'           => '',
          'descuentoProd'           => '0',
          'descuentoProdPorcent'    => '0',
          'importe'                 => '0',
          'noIdentificacion'        => '',
          'retencionCedular'        => '0',
          'retencionCedularPorcent' => '0',
          'retencionIsr'            => '0',
          'retencionIsrPorcent'     => '0',
          'retencionIva'            => '0',
          'retencionIvaPorcent'     => '0',
          'retencionIvc'            => '0',
          'retencionIvcPorcent'     => '0',
          'trasladoCedular'         => '0',
          'trasladoCedularPorcent'  => '0',
          'trasladoIeps'            => '0',
          'trasladoIepsPorcent'     => '0',
          'trasladoIsh'             => '0',
          'trasladoIshPorcent'      => '0',
          'trasladoIva'             => '0',
          'trasladoIvaPorcent'      => '0',
          'valorUnitario'           => '0',
          'objetoImp'               => '01',
        )
      ],
      'pagos' => [$comPago]
    );
    if (isset($cfdiRel)) {
      $datosApi['cfdiRelacionados'] = $cfdiRel;
    }

    return $datosApi;
  }

  /**
   * Carga los datos fiscales de la empresa|proveedor que emitira la factura.
   *
   * @param  string|int $id_empresa
   * @return void
   */
	public function cargaDatosFiscales($id, $table = 'empresas')
  {
    if ($table === 'empresas')
      $pkey = 'id_empresa';
    else
      $pkey = 'id_proveedor';

		$CI =& get_instance();
		$data = $CI->db->query(
      "SELECT *
       FROM {$table}
       WHERE {$pkey} = {$id}"
    )->row();

		$this->path_certificado_org = $data->cer_org;
		$this->path_certificado     = $data->cer;
		$this->path_key             = $data->key_path;
		$this->pass_key             = $data->pass;

    $this->version        = $data->cfdi_version;
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
    $this->curp           = '';
    if (strlen($this->rfc) === 13) {
      $this->curp = $data->curp;
    }
	}

	public function generaArchivos($data, $isNomina = false, $semana = null, $path = null, $nameAppend = null)
  {
		$this->cargaDatosFiscales($data['id'], $data['table']);
    $this->isNomina = $isNomina;

    if (is_null($path))
    {
      if ( ! $isNomina)
      {
    		// $vers = str_replace('.', '_', $this->version);
    		$pathXML = $this->guardarXML($data);
  		  // $this->generarUnPDF($data);
      }
      else
      {
        $this->anio = $semana['anio'];
        $this->semana = $semana['semana'];
        $pathXML = $this->guardarXMLNomina($data, $nameAppend);
      }
    }
    else
    {
      $pathXML = $this->guardarXMLXPath($path, $data);
    }

    return array('pathXML' => $pathXML);
	}

	public function actualizarArchivos($data){
		$this->cargaDatosFiscales($data['id'], $data['table']);

		$vers = str_replace('.', '_', $this->version);
		$this->guardarXML($data,true);
		$this->generarUnPDF($data,array('F'),true);
	}

  /*
   |-------------------------------------------------------------------------
   | REPORTE MENSUAL
   |-------------------------------------------------------------------------
   */

  public function descargaReporte($anio, $mes)
  {
		if($this->existeReporte($anio, $mes))
    {
			$path = APPPATH.'media/cfd/reportesMensuales/'.$anio.'/1'.$this->rfc.$mes.$anio.'.txt';
			header('Content-type: text/plain');
			header('Content-Disposition: attachment; filename="1'.$this->rfc.$mes.$anio.'.txt"');
			readfile($path);
		}
	}

	public function existeReporte($anio, $mes)
  {
		$path = APPPATH.'media/cfd/reportesMensuales/'.$anio.'/1'.$this->rfc.$mes.$anio.'.txt';
		return file_exists($path);
	}

	public function generaReporte($anio, $mes, $reporte, $ex_nombre='')
  {
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

  /**
   * Regresa el MES que corresponde en texto.
   *
   * @param  int $mes
   * @return string
   */
	private function mesToString($mes)
  {
		switch(floatval($mes))
    {
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

  /**
   * Acomoda el folio.
   *
   * @param  string $folio
   * @return string
   */
	public function acomodarFolio($folio)
  {
		$folio .= '';
		for($i=strlen($folio); $i<8; ++$i){
			$folio = '0'.$folio;
		}
		return $folio;
	}

  /**
   * Ajusta el texto.
   *
   * @param  string $cadena
   * @param  string $caracteres
   * @return string
   */
	public function ajustaTexto($cadena, $caracteres)
  {
    $res  = '';
    $len  = strlen($cadena);
    $cont = 0;

		while($cont<$len)
    {
      $res  .= substr($cadena, $cont, $caracteres)."<br>";
      $cont += $caracteres;
		}

		return $res;
	}

	/**
	 * Valida si el directorio espesificado existe o si no lo crea.
   *
   * @param string $tipo
   * @param string $path
   * @return string
	 */
	private function validaDir($tipo, $path)
  {
		$path = APPPATH.'media/cfdi/'.$path;

    if($tipo === 'empresa')
      $directorio = $this->nombre_fiscal;
    else if($tipo === 'anio')
    {
      if ($this->isNomina)
        $directorio = $this->anio;
      else
        $directorio = date("Y");
    }
    else if($tipo === 'semana')
      $directorio = $this->semana;
    else
      $directorio = $this->mesToString(date("n"));

		if( ! file_exists($path.$directorio."/"))
			$this->crearFolder($path, $directorio."/");

		return $directorio;
	}

	/**
	 * Crea un folder en el servidor.
   *
	 * @param $path_directorio: string. ruta donde se creara el directorio.
	 * @param $nombre_directorio: string. nombre del folder a crear.
   * @return mixed array|boolean
	 */
	private function crearFolder($path_directorio, $nombre_directorio)
  {
		if($nombre_directorio != "" && file_exists($path_directorio))
    {
			if( ! file_exists($path_directorio.$nombre_directorio))
				return mkdir($path_directorio.$nombre_directorio, 0777);
			else
				return true;
		}
    else
			return false;
	}

	private function obtenFechaMes($fecha)
  {
		$fecha = explode('-', $fecha);
		return array($fecha[0],$fecha[1]);
	}

  /*
   |-------------------------------------------------------------------------
   | FUNCIONES PARA GENERAR Y GUARDAR|DESCARGAR EL XML.
   |-------------------------------------------------------------------------
   */

  /**
   * Guarda el XML en capertas especificas AÑO/MES.
   *
   * @param  array  $data
   * @param  boolean $update
   * @return void
   */
	private function guardarXML($data, $update = false)
  {
    $vers = str_replace('.', '_', $this->version);
    $xml  = $this->{'generarXML'.$vers}($data);

    if( ! $update)
    {
			$dir_anio = $this->validaDir('anio', 'facturasXML/');
			$dir_mes = $this->validaDir('mes', 'facturasXML/'.$dir_anio.'/');
		}
		else
    {
      $fecha    = $this->obtenFechaMes($data['comprobante']['fecha']);
      $dir_anio = $fecha[0];
      $dir_mes  = $this->mesToString($fecha[1]);

			if( ! file_exists(APPPATH.'media/cfdi/facturasXML/'.$dir_anio.'/'))
        $this->crearFolder(APPPATH.'media/cfdi/facturasXML/', $dir_anio.'/');

			if( ! file_exists(APPPATH.'media/cfdi/facturasXML/'.$dir_anio.'/'.$dir_mes.'/'))
				$this->crearFolder(APPPATH.'media/cfdi/facturasXML/'.$dir_anio.'/', $dir_mes.'/');
		}

		$path_guardar = APPPATH.'media/cfdi/facturasXML/'.$dir_anio.'/'.$dir_mes.'/'.
			$this->rfc.'-'.$data['comprobante']['serie'].'-'.$this->acomodarFolio($data['comprobante']['folio']).'.xml';

		$fp = fopen($path_guardar, 'w');
		fwrite($fp, $xml);
		fclose($fp);

    return $path_guardar;
	}

  public function guardarXMLFactura($xml, $rfc, $serie, $folio, $fechaFactura)
  {
    $fecha    = $this->obtenFechaMes($fechaFactura);
    $dir_anio = $fecha[0];
    $dir_mes  = $this->mesToString($fecha[1]);

    if( ! file_exists(APPPATH.'media/cfdi/facturasXML/'.$dir_anio.'/'))
      $this->crearFolder(APPPATH.'media/cfdi/facturasXML/', $dir_anio.'/');

    if( ! file_exists(APPPATH.'media/cfdi/facturasXML/'.$dir_anio.'/'.$dir_mes.'/'))
      $this->crearFolder(APPPATH.'media/cfdi/facturasXML/'.$dir_anio.'/', $dir_mes.'/');

    $path_guardar = APPPATH.'media/cfdi/facturasXML/'.$dir_anio.'/'.$dir_mes.'/'.
      $rfc.'-'.$serie.'-'.$this->acomodarFolio($folio).'.xml';

    $fp = fopen($path_guardar, 'w');
    fwrite($fp, $xml);
    fclose($fp);

    return $path_guardar;
  }

  /**
   * Guarda el XML en capertas especificas AÑO/MES.
   *
   * @param  array  $data
   * @param  boolean $update
   * @return void
   */
  public function guardarXMLNomina($xml, $nameAppend)
  {
    $this->isNomina = true;

    $empresa = $this->validaDir('empresa', 'NominasXML/');
    $dir_anio = $this->validaDir('anio', 'NominasXML/'.$empresa.'/');
    $dir_semana = $this->validaDir('semana', 'NominasXML/'.$empresa.'/'.$dir_anio.'/');

    $path_guardar = APPPATH.'media/cfdi/NominasXML/'.$empresa.'/'.$dir_anio.'/'.$dir_semana.'/'.$nameAppend.'.xml';

    $fp = fopen($path_guardar, 'w');
    fwrite($fp, $xml);
    fclose($fp);

    return $path_guardar;
  }

  /**
   * Crea
   *
   * @param  array  $data
   * @param  boolean $update
   * @return void
   */
  public function guardarXMLXPath($path, $xml, $nombre)
  {
    $path = APPPATH.$path;

    // $vers = str_replace('.', '_', $this->version);
    // $xml  = $this->{'generarXML'.$vers}($data, true);

    $directorio = date('Y');
    $this->crearFolder($path, $directorio."/");

    $path_guardar = $path.$directorio.'/'.$nombre.'.xml';

    $fp = fopen($path_guardar, 'w');
    fwrite($fp, $xml);
    fclose($fp);

    return $path_guardar;
  }

  /**
   * Descarga el XML.
   *
   * @param  array $data
   * @param  string $pathXML
   * @return void
   */
	public function descargarXML($data = null, $pathXML = null)
  {
    // Carga los datos fiscales de la empresa|proveedor.
    $this->cargaDatosFiscales($data['id'], $data['table']);

    // Si el parametro $data contiene datos.
    if ( ! is_null($data) && is_null($pathXML))
    {
      $vers = str_replace('.', '_', $this->version);
      $xml  = $this->{'generarXML'.$vers}($data);
    }
    else
    {
      // Obtiene el contenido del XML.
      $xml = file_get_contents($pathXML);
    }

    header('Content-type: content-type: text/xml');
    header('Content-Disposition: attachment; filename="'.$this->rfc.'-'.$data['comprobante']['serie'].'-'.$this->acomodarFolio($data['comprobante']['folio']).'.xml"');

    echo $xml;
	}

  /**
   * Genera el contentido del XML con la informacion de facturacion.
   *
   * @param  array  $data
   * @return string
   */
	public function generarXML3_2($data = array(), $isNomina = false)
  {
    $namespace = '';
    $schemna   = '';
    if (isset($data['comercioExterior'])) {
      $namespace .= ' xmlns:cce="http://www.sat.gob.mx/ComercioExterior"';
      $schemna   .= ' http://www.sat.gob.mx/ComercioExterior http://www.sat.gob.mx/sitio_internet/cfd/ComercioExterior/ComercioExterior10.xsd';
    }
    if ($isNomina) {
      $namespace .= ' xmlns:nomina12="http://www.sat.gob.mx/nomina12"';
      $schemna   .= ' http://www.sat.gob.mx/nomina12 http://www.sat.gob.mx/sitio_internet/cfd/nomina/nomina12.xsd';
    } else {
      $CI =& get_instance();
      $CI->load->model('nomina_catalogos_model');
      $cod_rf = $CI->nomina_catalogos_model->findByClave($this->regimen_fiscal, 'rgf');
      $this->regimen_fiscal = isset($cod_rf->nombre_corto)? $cod_rf->nombre_corto: $this->regimen_fiscal;
    }

		$xml = '';
		$xml .= '<?xml version="1.0" encoding="UTF-8"?> ';
		$xml .= '<cfdi:Comprobante xmlns:cfdi="http://www.sat.gob.mx/cfd/3" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'.$namespace.' xsi:schemaLocation="http://www.sat.gob.mx/cfd/3 http://www.sat.gob.mx/sitio_internet/cfd/3/cfdv32.xsd'.$schemna.'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬version="'.$this->replaceSpecialChars($data['comprobante']['version']).'" ';

    if(isset($data['comprobante']['serie']) && $data['comprobante']['serie'] !== '')
		  $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬serie="'.$data['comprobante']['serie'].'" ';

    if(isset($data['comprobante']['folio']) && $data['comprobante']['folio'] !== '')
		  $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬folio="'.$data['comprobante']['folio'].'" ';

    $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬fecha="'.$data['comprobante']['fecha'].'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬sello="'.$data['comprobante']['sello'].'" ';
    $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬formaDePago="'.$data['comprobante']['formaDePago'].'" ';
    $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬noCertificado="'.$data['comprobante']['noCertificado'].'" ';
    $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬certificado="'.$data['comprobante']['certificado'].'" ';
    if (isset($data['comprobante']['condicionesDePago']))
      $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬condicionesDePago="'.$data['comprobante']['condicionesDePago'].'" ';
    $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬subTotal="'.(float)$data['comprobante']['subTotal'].'" ';

    // Nomina
    if (isset($data['comprobante']['descuento']))
    {
      $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬descuento="'.(float)$data['comprobante']['descuento'].'" ';
      // $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬motivoDescuento="Deducciones nómina" ';
    }

    $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬total="'.(float)$data['comprobante']['total'].'" ';
    $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬tipoDeComprobante="'.$data['comprobante']['tipoDeComprobante'].'" ';
    $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬metodoDePago="'.$data['comprobante']['metodoDePago'].'" ';
    $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬LugarExpedicion="'.$data['comprobante']['LugarExpedición'].'" ';
    if(!empty($data['comprobante']['NumCtaPago']))
      $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬NumCtaPago="'.$data['comprobante']['NumCtaPago'].'" ';

    if (isset($data['comprobante']['Moneda']) && $data['comprobante']['Moneda'] !== ''){
      $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬TipoCambio="'.$data['comprobante']['TipoCambio'].'" ';
      $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬Moneda="'.$data['comprobante']['Moneda'].'" ';
    }
		$xml .= '>';

		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬<cfdi:Emisor rfc="'.$this->replaceSpecialChars($this->rfc).'" nombre="'.$this->replaceSpecialChars($this->nombre_fiscal).'">';
    if (!$isNomina) {
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<cfdi:DomicilioFiscal ';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬calle="'.$this->replaceSpecialChars($this->calle).'" ';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬noExterior="'.$this->replaceSpecialChars($this->no_exterior).'" ';
  		if($this->no_interior !== '')
  			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬noInterior="'.$this->replaceSpecialChars($this->no_interior).'" ';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬colonia="'.$this->replaceSpecialChars($this->colonia).'" ';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬localidad="'.$this->replaceSpecialChars($this->localidad).'" ';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬municipio="'.$this->replaceSpecialChars($this->municipio).'" ';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬estado="'.$this->replaceSpecialChars($this->estado).'" ';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬pais="'.$this->replaceSpecialChars($this->pais).'" ';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬codigoPostal="'.$this->replaceSpecialChars($this->cp).'"';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬/>';

  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<cfdi:ExpedidoEn ';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬calle="'.$this->replaceSpecialChars($this->calle).'" ';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬noExterior="'.$this->replaceSpecialChars($this->no_exterior).'" ';
  		if($this->no_interior !== '')
  			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬noInterior="'.$this->replaceSpecialChars($this->no_interior).'" ';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬colonia="'.$this->replaceSpecialChars($this->colonia).'" ';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬localidad="'.$this->replaceSpecialChars($this->localidad).'" ';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬municipio="'.$this->replaceSpecialChars($this->municipio).'" ';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬estado="'.$this->replaceSpecialChars($this->estado).'" ';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬pais="'.$this->replaceSpecialChars($this->pais).'" ';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬codigoPostal="'.$this->replaceSpecialChars($this->cp).'"';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬/>';
    }
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<cfdi:RegimenFiscal ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬Regimen="'.$this->replaceSpecialChars($this->regimen_fiscal).'" ';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬/>';
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬</cfdi:Emisor>';

		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬<cfdi:Receptor rfc="'.$data['receptor']['rfc'].'" nombre="'.$this->replaceSpecialChars($data['receptor']['nombre']).'">';
    if (!$isNomina) {
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<cfdi:Domicilio ';
      if (isset($data['domicilio']['calle']) && $data['domicilio']['calle'] !== '')
  		  $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬calle="'.$this->replaceSpecialChars($data['domicilio']['calle']).'" ';
      if (isset($data['domicilio']['noExterior']) && $data['domicilio']['noExterior'] !== '')
  		  $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬noExterior="'.$this->replaceSpecialChars($data['domicilio']['noExterior']).'" ';
  		if(isset($data['domicilio']['noInterior']) && $data['domicilio']['noInterior'] !== '')
  			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬noInterior="'.$this->replaceSpecialChars($data['domicilio']['noInterior']).'" ';
      if(isset($data['domicilio']['colonia']) && $data['domicilio']['colonia'] !== '')
        $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬colonia="'.$this->replaceSpecialChars($data['domicilio']['colonia']).'" ';
      if(isset($data['domicilio']['localidad']) && $data['domicilio']['localidad'] !== '')
  		  $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬localidad="'.$this->replaceSpecialChars($data['domicilio']['localidad']).'" ';
      if(isset($data['domicilio']['municipio']) && $data['domicilio']['municipio'] !== '')
  		  $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬municipio="'.$this->replaceSpecialChars($data['domicilio']['municipio']).'" ';
      if(isset($data['domicilio']['estado']) && $data['domicilio']['estado'] !== '')
  		  $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬estado="'.$this->replaceSpecialChars($data['domicilio']['estado']).'" ';
      if(isset($data['domicilio']['pais']) && $data['domicilio']['pais'] !== '')
  		  $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬pais="'.$this->replaceSpecialChars($data['domicilio']['pais']).'" ';
      if(isset($data['domicilio']['codigoPostal']) && $data['domicilio']['codigoPostal'] !== '')
  		  $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬codigoPostal="'.$this->replaceSpecialChars($data['domicilio']['codigoPostal']).'"';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬/>';
    }
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬</cfdi:Receptor>';

		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬<cfdi:Conceptos>';

		foreach($data['conceptos'] as $concepto)
    {
      if ($data['sinCosto'])
      {
        if ($concepto['idClasificacion'] != '49' AND $concepto['idClasificacion'] != '50' AND
            $concepto['idClasificacion'] != '51' AND $concepto['idClasificacion'] != '52' AND
            $concepto['idClasificacion'] != '53')
        {
          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<cfdi:Concepto ';
          if (isset($concepto['noIdentificacion']{0})) {
            $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬noIdentificacion="'.$concepto['noIdentificacion'].'" ';
          }
          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬cantidad="'.(float)$concepto['cantidad'].'" ';
          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬unidad="'.$concepto['unidad'].'" ';
          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬descripcion="'.$this->replaceSpecialChars($concepto['descripcion']).'" ';
          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬valorUnitario="'.(float)$concepto['valorUnitario'].'" ';
          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬importe="'.(float)$concepto['importe'].'"';
          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬>';
          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬</cfdi:Concepto>';
        }
      }
      else
      {
        $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<cfdi:Concepto ';
        if (isset($concepto['noIdentificacion']{0})) {
          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬noIdentificacion="'.$concepto['noIdentificacion'].'" ';
        }
        $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬cantidad="'.(float)$concepto['cantidad'].'" ';
        $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬unidad="'.$concepto['unidad'].'" ';
        $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬descripcion="'.$this->replaceSpecialChars($concepto['descripcion']).'" ';
        $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬valorUnitario="'.(float)$concepto['valorUnitario'].'" ';
        $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬importe="'.(float)$concepto['importe'].'"';
        $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬>';
        $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬</cfdi:Concepto>';
      }
		}
		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬</cfdi:Conceptos>';

		$totalImpuestosRetenidos = '';
		if(isset($data['totalImpuestosRetenidos']))
			$totalImpuestosRetenidos = 'totalImpuestosRetenidos="'.(float)$data['totalImpuestosRetenidos'].'"';

    if ($isNomina) {
      $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬<cfdi:Impuestos>';
      $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬</cfdi:Impuestos>';
    } else {
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬<cfdi:Impuestos '.$totalImpuestosRetenidos.' totalImpuestosTrasladados="'.(float)$data['totalImpuestosTrasladados'].'">';
  		if(isset($data['totalImpuestosRetenidos'])){
  			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<cfdi:Retenciones>';
  			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<cfdi:Retencion ';
  			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬impuesto="'.$data['retencion']['impuesto'].'" ';
  			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬importe="'.(float)$data['retencion']['importe'].'"';
  			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬/>';
  			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬</cfdi:Retenciones>';
  		}
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<cfdi:Traslados>';
  		foreach($data['traslado'] as $traslado){
  			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<cfdi:Traslado ';
  			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬impuesto="IVA" ';
  			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬tasa="'.(float)$traslado['tasa'].'" ';
  			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬importe="'.(float)$traslado['importe'].'"';
  			$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬/>';
  		}
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬</cfdi:Traslados>';
  		$xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬</cfdi:Impuestos>';
    }

    // 'Version'                => '1.1',
    // 'NumEmpleado'            => $empleado[0]->id,
    // 'CURP'                   => $empleado[0]->curp,
    // 'TipoRegimen'            => '1',
    // 'FechaPago'              => $empleado[0]->fecha_final_pago,
    // 'FechaInicialPago'       => $empleado[0]->fecha_inicial_pago,
    // 'FechaFinalPago'         => $empleado[0]->fecha_final_pago,
    // 'NumDiasPagados'         => $empleado[0]->dias_trabajados,
    // 'Departamento'           => $empleado[0]->puesto,
    // 'FechaInicioRelLaboral'  => $empleado[0]->fecha_entrada, // opcional
    // 'Puesto'                 => $empleado[0]->puesto, // opcional
    // 'PeriodicidadPago'       => 'semanal',

    // Si es una nomina entonces agregar el nodo nomina dentro del nodo Complemento.
    if ($isNomina)
    {
      // Nodo Complemento
      $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬<cfdi:Complemento>';

      $attrTotalPercepciones = '';
      if (isset($data['nomina']['nomina']['Nomina']['TotalPercepciones']) && $data['nomina']['nomina']['Nomina']['TotalPercepciones'] > 0)
      {
        $attrTotalPercepciones = 'TotalPercepciones="'.$data['nomina']['nomina']['Nomina']['TotalPercepciones'].'" ';
      }

      $attrTotalDeducciones = '';
      if (isset($data['nomina']['nomina']['Nomina']['TotalDeducciones']) && $data['nomina']['nomina']['Nomina']['TotalDeducciones'] > 0)
      {
        $attrTotalDeducciones = 'TotalDeducciones="'.$data['nomina']['nomina']['Nomina']['TotalDeducciones'].'" ';
      }

      $attrTotalOtrosPagos = '';
      if (isset($data['nomina']['nomina']['Nomina']['TotalOtrosPagos']) && $data['nomina']['nomina']['Nomina']['TotalOtrosPagos'] > 0)
      {
        $attrTotalOtrosPagos = 'TotalOtrosPagos="'.$data['nomina']['nomina']['Nomina']['TotalOtrosPagos'].'" ';
      }

      // Datos Generales.
      $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<nomina12:Nomina Version="'.$data['nomina']['nomina']['Nomina']['Version'].'" '.
                                               'TipoNomina="'.$data['nomina']['nomina']['Nomina']['TipoNomina'].'" '.
                                               'FechaPago="'.$data['nomina']['nomina']['Nomina']['FechaPago'].'" '.
                                               'FechaInicialPago="'.$data['nomina']['nomina']['Nomina']['FechaInicialPago'].'" '.
                                               'FechaFinalPago="'.$data['nomina']['nomina']['Nomina']['FechaFinalPago'].'" '.
                                               'NumDiasPagados="'.$data['nomina']['nomina']['Nomina']['NumDiasPagados'].'" '.
                                               $attrTotalPercepciones.
                                               $attrTotalDeducciones.
                                               $attrTotalOtrosPagos.'>';

      // Emisor
        $attrCurp = '';
        if (isset($data['nomina']['nomina']['Emisor']['Curp']) && $data['nomina']['nomina']['Emisor']['Curp'] !== '')
        {
          $attrCurp = 'Curp="'.$data['nomina']['nomina']['Emisor']['Curp'].'" ';
        }

        $attrRegistroPatronal = '';
        if (isset($data['nomina']['nomina']['Emisor']['RegistroPatronal']) && $data['nomina']['nomina']['Emisor']['RegistroPatronal'] !== '')
        {
          $attrRegistroPatronal = 'RegistroPatronal="'.$data['nomina']['nomina']['Emisor']['RegistroPatronal'].'" ';
        }

        $attrRfcPatronOrigen = '';
        if (isset($data['nomina']['nomina']['Emisor']['RfcPatronOrigen']) && $data['nomina']['nomina']['Emisor']['RfcPatronOrigen'] !== '')
        {
          $attrRfcPatronOrigen = 'RfcPatronOrigen="'.$data['nomina']['nomina']['Emisor']['RfcPatronOrigen'].'" ';
        }

        if ($attrCurp !== '' || $attrRegistroPatronal !== '' || $attrRfcPatronOrigen !== '' ||
            (isset($data['nomina']['nomina']['Emisor']['OrigenRecurso']))) {
          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<nomina12:Emisor '.$attrCurp.
                                                        $attrRegistroPatronal.
                                                        $attrRfcPatronOrigen.'>';
          if (isset($data['nomina']['nomina']['Emisor']['OrigenRecurso'])) {
            $attrMontoRecursoPropio = '';
            if (isset($data['nomina']['nomina']['Emisor']['MontoRecursoPropio']) && $data['nomina']['nomina']['Emisor']['MontoRecursoPropio'] != '')
            {
              $attrMontoRecursoPropio = 'MontoRecursoPropio="'.$data['nomina']['nomina']['Emisor']['MontoRecursoPropio'].'" ';
            }

            $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<nomina12:EntidadSNCF OrigenRecurso="'.$data['nomina']['nomina']['Emisor']['OrigenRecurso'].'" '.
                                                                  $attrMontoRecursoPropio.' />';
          }
          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬</nomina12:Emisor>';
        }

      // Receptor
        $attrNumSeguridadSocial = '';
        if (isset($data['nomina']['nomina']['Receptor']['NumSeguridadSocial']) && $data['nomina']['nomina']['Receptor']['NumSeguridadSocial'] !== '')
        {
          $attrNumSeguridadSocial = 'NumSeguridadSocial="'.$data['nomina']['nomina']['Receptor']['NumSeguridadSocial'].'" ';
        }

        $attrFechaInicioRelLaboral = '';
        if (isset($data['nomina']['nomina']['Receptor']['FechaInicioRelLaboral']) && $data['nomina']['nomina']['Receptor']['FechaInicioRelLaboral'] !== '')
        {
          $attrFechaInicioRelLaboral = 'FechaInicioRelLaboral="'.$data['nomina']['nomina']['Receptor']['FechaInicioRelLaboral'].'" ';
        }

        $attrAntiguedad = '';
        if (isset($data['nomina']['nomina']['Receptor']['Antigüedad']) && $data['nomina']['nomina']['Receptor']['Antigüedad'] !== '')
        {
          $attrAntiguedad = 'Antigüedad="'.$data['nomina']['nomina']['Receptor']['Antigüedad'].'" ';
        }

        $attrSindicalizado = '';
        if (isset($data['nomina']['nomina']['Receptor']['Sindicalizado']) && $data['nomina']['nomina']['Receptor']['Sindicalizado'] !== '')
        {
          $attrSindicalizado = 'Sindicalizado="'.$data['nomina']['nomina']['Receptor']['Sindicalizado'].'" ';
        }

        $attrTipoJornada = '';
        if (isset($data['nomina']['nomina']['Receptor']['TipoJornada']) && $data['nomina']['nomina']['Receptor']['TipoJornada'] !== '')
        {
          $attrTipoJornada = 'TipoJornada="'.$data['nomina']['nomina']['Receptor']['TipoJornada'].'" ';
        }

        $attrDepartamento = '';
        if (isset($data['nomina']['nomina']['Receptor']['Departamento']) && $data['nomina']['nomina']['Receptor']['Departamento'] !== '')
        {
          $attrDepartamento = 'Departamento="'.$data['nomina']['nomina']['Receptor']['Departamento'].'" ';
        }

        $attrPuesto = '';
        if (isset($data['nomina']['nomina']['Receptor']['Puesto']) && $data['nomina']['nomina']['Receptor']['Puesto'] !== '')
        {
          $attrPuesto = 'Puesto="'.$data['nomina']['nomina']['Receptor']['Puesto'].'" ';
        }

        $attrRiesgoPuesto = '';
        if (isset($data['nomina']['nomina']['Receptor']['RiesgoPuesto']) && $data['nomina']['nomina']['Receptor']['RiesgoPuesto'] !== '')
        {
          $attrRiesgoPuesto = 'RiesgoPuesto="'.$data['nomina']['nomina']['Receptor']['RiesgoPuesto'].'" ';
        }

        $attrCuentaBancaria = '';
        if (isset($data['nomina']['nomina']['Receptor']['CuentaBancaria']) && $data['nomina']['nomina']['Receptor']['CuentaBancaria'] !== '')
        {
          $attrCuentaBancaria = 'CuentaBancaria="'.$data['nomina']['nomina']['Receptor']['CuentaBancaria'].'" ';
        }

        $attrBanco = '';
        if (isset($data['nomina']['nomina']['Receptor']['Banco']) && $data['nomina']['nomina']['Receptor']['Banco'] !== '')
        {
          $attrBanco = 'Banco="'.$data['nomina']['nomina']['Receptor']['Banco'].'" ';
        }

        $attrSalarioBaseCotApor = '';
        if (isset($data['nomina']['nomina']['Receptor']['SalarioBaseCotApor']) && $data['nomina']['nomina']['Receptor']['SalarioBaseCotApor'] !== '')
        {
          $attrSalarioBaseCotApor = 'SalarioBaseCotApor="'.$data['nomina']['nomina']['Receptor']['SalarioBaseCotApor'].'" ';
        }

        $attrSalarioDiarioIntegrado = '';
        if (isset($data['nomina']['nomina']['Receptor']['SalarioDiarioIntegrado']) && $data['nomina']['nomina']['Receptor']['SalarioDiarioIntegrado'] !== '')
        {
          $attrSalarioDiarioIntegrado = 'SalarioDiarioIntegrado="'.$data['nomina']['nomina']['Receptor']['SalarioDiarioIntegrado'].'" ';
        }

        $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<nomina12:Receptor Curp="'.$this->replaceSpecialChars($data['nomina']['nomina']['Receptor']['Curp']).'" '.
                                                        $attrNumSeguridadSocial.
                                                        $attrFechaInicioRelLaboral.
                                                        $attrAntiguedad.
                                                        'TipoContrato="'.$data['nomina']['nomina']['Receptor']['TipoContrato'].'" '.
                                                        $attrSindicalizado.
                                                        $attrTipoJornada.
                                                        'TipoRegimen="'.$data['nomina']['nomina']['Receptor']['TipoRegimen'].'" '.
                                                        'NumEmpleado="'.$data['nomina']['nomina']['Receptor']['NumEmpleado'].'" '.
                                                        $attrDepartamento.
                                                        $attrPuesto.
                                                        $attrRiesgoPuesto.
                                                        'PeriodicidadPago="'.$this->replaceSpecialChars($data['nomina']['nomina']['Receptor']['PeriodicidadPago']).'" '.
                                                        $attrBanco.
                                                        $attrCuentaBancaria.
                                                        $attrSalarioBaseCotApor.
                                                        $attrSalarioDiarioIntegrado.
                                                        'ClaveEntFed="'.$this->replaceSpecialChars($data['nomina']['nomina']['Receptor']['ClaveEntFed']).'">';
        if (isset($data['nomina']['nomina']['Receptor']['SubContratacion']) && is_array($data['nomina']['nomina']['Receptor']['SubContratacion'])) {
          foreach ($data['nomina']['nomina']['Receptor']['SubContratacion'] as $subContra)
          {
            $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<nomina12:SubContratacion RfcLabora="'.$subContra['RfcLabora'].'" '.
                                                                      'PorcentajeTiempo="'.$subContra['PorcentajeTiempo'].'" />';
          }
        }
        $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬</nomina12:Receptor>';

      // Percepciones
        if (isset($data['nomina']['nomina']['Percepciones'])) {
          $attrTotalSueldos = '';
          if (isset($data['nomina']['nomina']['PercepcionesTotales']['TotalSueldos']) && $data['nomina']['nomina']['PercepcionesTotales']['TotalSueldos'] != 0)
          {
            $attrTotalSueldos = 'TotalSueldos="'.$data['nomina']['nomina']['PercepcionesTotales']['TotalSueldos'].'" ';
          }

          $attrTotalSeparacionIndemnizacion = '';
          if (isset($data['nomina']['nomina']['PercepcionesTotales']['TotalSeparacionIndemnizacion']) && $data['nomina']['nomina']['PercepcionesTotales']['TotalSeparacionIndemnizacion'] != 0)
          {
            $attrTotalSeparacionIndemnizacion = 'TotalSeparacionIndemnizacion="'.$data['nomina']['nomina']['PercepcionesTotales']['TotalSeparacionIndemnizacion'].'" ';
          }

          $attrTotalJubilacionPensionRetiro = '';
          if (isset($data['nomina']['nomina']['PercepcionesTotales']['TotalJubilacionPensionRetiro']) && $data['nomina']['nomina']['PercepcionesTotales']['TotalJubilacionPensionRetiro'] != 0)
          {
            $attrTotalJubilacionPensionRetiro = 'TotalJubilacionPensionRetiro="'.$data['nomina']['nomina']['PercepcionesTotales']['TotalJubilacionPensionRetiro'].'" ';
          }

          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<nomina12:Percepciones TotalGravado="'.(float)$data['nomina']['nomina']['PercepcionesTotales']['TotalGravado'].'" '.
                                                              'TotalExento="'.(float)$data['nomina']['nomina']['PercepcionesTotales']['TotalExento'].'" '.
                                                              $attrTotalSueldos.
                                                              $attrTotalSeparacionIndemnizacion.
                                                              $attrTotalJubilacionPensionRetiro.'>';

          foreach ($data['nomina']['nomina']['Percepciones'] as $percepcion)
          {
            $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<nomina12:Percepcion TipoPercepcion="'.$percepcion['TipoPercepcion'].'" '.
                                                                'Clave="'.$percepcion['Clave'].'" '.
                                                                'Concepto="'.$percepcion['Concepto'].'" '.
                                                                'ImporteGravado="'.(float)$percepcion['ImporteGravado'].'" '.
                                                                'ImporteExento="'.(float)$percepcion['ImporteExcento'].'">';
            if (isset($percepcion['HorasExtra']) && count($percepcion['HorasExtra']) > 0) {
              foreach ($percepcion['HorasExtra'] as $keyhrss => $hrsss) {
                $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<nomina12:HorasExtra Dias="'.$hrsss['Dias'].'" TipoHoras="'.$hrsss['TipoHoras'].'" HorasExtra="'.$hrsss['HorasExtra'].'" ImportePagado="'.$hrsss['ImportePagado'].'" />';
              }
            }
            $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬</nomina12:Percepcion>';
          }

          // // JubilacionPensionRetiro
          // if (isset($data['nomina']['nomina']['JubilacionPensionRetiro'])) {
          //   $attrTotalUnaExhibicion = '';
          //   if (isset($data['nomina']['nomina']['JubilacionPensionRetiro']['TotalUnaExhibicion']) && $data['nomina']['nomina']['JubilacionPensionRetiro']['TotalUnaExhibicion'] > 0)
          //   {
          //     $attrTotalUnaExhibicion = 'TotalUnaExhibicion="'.$data['nomina']['nomina']['JubilacionPensionRetiro']['TotalUnaExhibicion'].'" ';
          //   }

          //   $attrTotalParcialidad = '';
          //   if (isset($data['nomina']['nomina']['JubilacionPensionRetiro']['TotalParcialidad']) && $data['nomina']['nomina']['JubilacionPensionRetiro']['TotalParcialidad'] > 0)
          //   {
          //     $attrTotalParcialidad = 'TotalParcialidad="'.$data['nomina']['nomina']['JubilacionPensionRetiro']['TotalParcialidad'].'" ';
          //   }

          //   $attrMontoDiario = '';
          //   if (isset($data['nomina']['nomina']['JubilacionPensionRetiro']['MontoDiario']) && $data['nomina']['nomina']['JubilacionPensionRetiro']['MontoDiario'] > 0)
          //   {
          //     $attrMontoDiario = 'MontoDiario="'.$data['nomina']['nomina']['JubilacionPensionRetiro']['MontoDiario'].'" ';
          //   }

          //   $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<nomina12:JubilacionPensionRetiro IngresoAcumulable="'.(float)$data['nomina']['nomina']['JubilacionPensionRetiro']['IngresoAcumulable'].'" '.
          //                                                                 'IngresoNoAcumulable="'.(float)$data['nomina']['nomina']['JubilacionPensionRetiro']['IngresoNoAcumulable'].'" '.
          //                                                                 $attrTotalUnaExhibicion.
          //                                                                 $attrTotalParcialidad.
          //                                                                 $attrMontoDiario.' />';
          // }

          // SeparacionIndemnizacion
          if (isset($data['nomina']['nomina']['PercepcionesSeparacionIndemnizacion'])) {
            $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<nomina12:SeparacionIndemnizacion TotalPagado="'.(float)$data['nomina']['nomina']['PercepcionesSeparacionIndemnizacion']['TotalPagado'].'" '.
                                                                          'NumAñosServicio="'.(float)$data['nomina']['nomina']['PercepcionesSeparacionIndemnizacion']['NumAñosServicio'].'" '.
                                                                          'UltimoSueldoMensOrd="'.(float)$data['nomina']['nomina']['PercepcionesSeparacionIndemnizacion']['UltimoSueldoMensOrd'].'" '.
                                                                          'IngresoAcumulable="'.(float)$data['nomina']['nomina']['PercepcionesSeparacionIndemnizacion']['IngresoAcumulable'].'" '.
                                                                          'IngresoNoAcumulable="'.(float)$data['nomina']['nomina']['PercepcionesSeparacionIndemnizacion']['IngresoNoAcumulable'].'" />';
          }

          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬</nomina12:Percepciones>';
        }


      // Deducciones.
        if (isset($data['nomina']['nomina']['Deducciones'])) {
          $attrTotalOtrasDeducciones = '';
          if (isset($data['nomina']['nomina']['DeduccionesTotales']['TotalOtrasDeducciones']) && $data['nomina']['nomina']['DeduccionesTotales']['TotalOtrasDeducciones'] != 0)
          {
            $attrTotalOtrasDeducciones = 'TotalOtrasDeducciones="'.$data['nomina']['nomina']['DeduccionesTotales']['TotalOtrasDeducciones'].'" ';
          }

          $attrTotalImpuestosRetenidos = '';
          if (isset($data['nomina']['nomina']['DeduccionesTotales']['TotalImpuestosRetenidos']) && $data['nomina']['nomina']['DeduccionesTotales']['TotalImpuestosRetenidos'] != 0)
          {
            $attrTotalImpuestosRetenidos = 'TotalImpuestosRetenidos="'.$data['nomina']['nomina']['DeduccionesTotales']['TotalImpuestosRetenidos'].'" ';
          }

          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<nomina12:Deducciones '.$attrTotalOtrasDeducciones.
                                                              $attrTotalImpuestosRetenidos.'>';

          foreach ($data['nomina']['nomina']['Deducciones'] as $deduccion)
          {
            $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<nomina12:Deduccion TipoDeduccion="'.$deduccion['TipoDeduccion'].'" '.
                                                                'Clave="'.$deduccion['Clave'].'" '.
                                                                'Concepto="'.$deduccion['Concepto'].'" '.
                                                                'Importe="'.(float)$deduccion['Importe'].'" />';
          }
          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬</nomina12:Deducciones>';
        }

      // OtrosPagos.
        if (isset($data['nomina']['nomina']['otrosPagos'])) {
          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<nomina12:OtrosPagos>';

          foreach ($data['nomina']['nomina']['otrosPagos'] as $otroPago)
          {
            $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<nomina12:OtroPago TipoOtroPago="'.$otroPago['TipoOtroPago'].'" '.
                                                                'Clave="'.$otroPago['Clave'].'" '.
                                                                'Concepto="'.$otroPago['Concepto'].'" '.
                                                                'Importe="'.(float)$otroPago['Importe'].'">';
            if ($otroPago['TipoOtroPago'] == '002' && isset($otroPago['SubsidioCausado'])) {
              $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<nomina12:SubsidioAlEmpleo SubsidioCausado="'.$otroPago['SubsidioCausado'].'" />';
            }

            // if ($otroPago['TipoOtroPago'] == '004' && isset($otroPago['CompensacionSaldosAFavor'])) {
            //   $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<nomina12:CompensacionSaldosAFavor SaldoAFavor="'.$otroPago['CompensacionSaldosAFavor']['SaldoAFavor'].'" '.
            //                                                                           'Año="'.$otroPago['CompensacionSaldosAFavor']['Año'].'" '.
            //                                                                           'RemanenteSalFav="'.$otroPago['CompensacionSaldosAFavor']['RemanenteSalFav'].'" />';
            // }

            $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬</nomina12:OtroPago>';
          }
          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬</nomina12:OtrosPagos>';
        }

      // Incapacidades.
        if (isset($data['nomina']['nomina']['Incapacidades']) && count($data['nomina']['nomina']['Incapacidades']) > 0) {
          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<nomina12:Incapacidades>';

          foreach ($data['nomina']['nomina']['Incapacidades'] as $incapasidad)
          {
            $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<nomina12:Incapacidad DiasIncapacidad="'.$incapasidad['DiasIncapacidad'].'" '.
                                                                'TipoIncapacidad="'.$incapasidad['TipoIncapacidad'].'" '.
                                                                'ImporteMonetario="'.$incapasidad['ImporteMonetario'].'" />';
          }
          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬</nomina12:Incapacidades>';
        }
      $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬</nomina12:Nomina>';

      $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬</cfdi:Complemento>';
    } elseif (isset($data['comercioExterior'])) {
      // complemento Comercio Exterior
      $xml .= '¬¬¬¬<cfdi:Complemento>';
      $xml .= $this->xmlComplementoComercioExterior($data);
      $xml .= '¬¬¬¬</cfdi:Complemento>';
    }

		$xml .= '</cfdi:Comprobante>';

		$xml = str_replace('¬','',$xml);

    // echo "<pre>";
    //   var_dump($xml);
    // echo "</pre>";exit;

		return $xml;
	}

  public function xmlComplementoComercioExterior($data)
  {
    $xml = '';
    if (isset($data['comercioExterior']) && count($data['comercioExterior']) > 0)
    {
      $comercioExtVersionAttr       = 'Version="'.$data['comercioExterior']['Version'].'" ';
      $comercioExtTipoOperacionAttr = 'TipoOperacion="'.$data['comercioExterior']['TipoOperacion'].'" ';

      $comercioExtClaveDePedimentoAttr = '';
      if (isset($data['comercioExterior']['ClaveDePedimento']) && $data['comercioExterior']['ClaveDePedimento'] !== '')
        $comercioExtClaveDePedimentoAttr = 'ClaveDePedimento="'.$data['comercioExterior']['ClaveDePedimento'].'" ';

      $comercioExtCertificadoOrigenAttr = '';
      if (isset($data['comercioExterior']['CertificadoOrigen']) && $data['comercioExterior']['CertificadoOrigen'] !== '')
        $comercioExtCertificadoOrigenAttr = 'CertificadoOrigen="'.$data['comercioExterior']['CertificadoOrigen'].'" ';

      $comercioExtNumCertificadoOrigenAttr = '';
      if (isset($data['comercioExterior']['NumCertificadoOrigen']) && $data['comercioExterior']['NumCertificadoOrigen'] !== '')
        $comercioExtNumCertificadoOrigenAttr = 'NumCertificadoOrigen="'.$data['comercioExterior']['NumCertificadoOrigen'].'" ';

      $comercioExtNumeroExportadorConfiableAttr = '';
      if (isset($data['comercioExterior']['NumeroExportadorConfiable']) && $data['comercioExterior']['NumeroExportadorConfiable'] !== '')
        $comercioExtNumeroExportadorConfiableAttr = 'NumeroExportadorConfiable="'.$data['comercioExterior']['NumeroExportadorConfiable'].'" ';

      $comercioExtIncotermAttr = '';
      if (isset($data['comercioExterior']['Incoterm']) && $data['comercioExterior']['Incoterm'] !== '')
        $comercioExtIncotermAttr = 'Incoterm="'.$data['comercioExterior']['Incoterm'].'" ';

      $comercioExtSubdivisionAttr = '';
      if (isset($data['comercioExterior']['Subdivision']) && $data['comercioExterior']['Subdivision'] !== '')
        $comercioExtSubdivisionAttr = 'Subdivision="'.$data['comercioExterior']['Subdivision'].'" ';

      $comercioExtObservacionesAttr = '';
      if (isset($data['comercioExterior']['Observaciones']) && $data['comercioExterior']['Observaciones'] !== '')
        $comercioExtObservacionesAttr = 'Observaciones="'.$data['comercioExterior']['Observaciones'].'" ';

      $comercioExtTipoCambioUSDAttr = '';
      if (isset($data['comercioExterior']['TipoCambioUSD']) && $data['comercioExterior']['TipoCambioUSD'] !== '')
        $comercioExtTipoCambioUSDAttr = 'TipoCambioUSD="'.$data['comercioExterior']['TipoCambioUSD'].'" ';

      $comercioExtTotalUSDAttr = '';
      if (isset($data['comercioExterior']['TotalUSD']) && $data['comercioExterior']['TotalUSD'] !== '')
        $comercioExtTotalUSDAttr = 'TotalUSD="'.$data['comercioExterior']['TotalUSD'].'" ';

      // xmlns:cce="http://www.sat.gob.mx/ComercioExterior"
      $xml .= '¬¬¬¬<cce:ComercioExterior '.$comercioExtVersionAttr.
                        $comercioExtTipoOperacionAttr.
                        $comercioExtClaveDePedimentoAttr.
                        $comercioExtCertificadoOrigenAttr.
                        $comercioExtNumCertificadoOrigenAttr.
                        $comercioExtNumeroExportadorConfiableAttr.
                        $comercioExtIncotermAttr.
                        $comercioExtSubdivisionAttr.
                        $comercioExtObservacionesAttr.
                        $comercioExtTipoCambioUSDAttr.
                        $comercioExtTotalUSDAttr.
                    '>';

      // Emisor.
      if(isset($data['comercioExterior']['Emisor']) && count($data['comercioExterior']['Emisor']) > 0)
      {
        $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬<cce:Emisor Curp="'.$data['comercioExterior']['Emisor']['Curp'].'" />';
      }

      // Receptor.
      if(isset($data['comercioExterior']['Receptor']) && count($data['comercioExterior']['Receptor']) > 0)
      {
        $curpAttr = '';
        if (isset($data['comercioExterior']['Receptor']['Curp']) && $data['comercioExterior']['Receptor']['Curp'] != '')
          $curpAttr = 'Curp="'.$data['comercioExterior']['Receptor']['Curp'].'" ';

        $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬<cce:Receptor '.$curpAttr.'NumRegIdTrib="'.$data['comercioExterior']['Receptor']['NumRegIdTrib'].'" />';
      }

      // Destinatario.
      if(isset($data['comercioExterior']['Destinatario']) && count($data['comercioExterior']['Destinatario']) > 0)
      {
        $NumRegIdTribAttr = '';
        if (isset($data['comercioExterior']['Destinatario']['NumRegIdTrib']) && $data['comercioExterior']['Destinatario']['NumRegIdTrib'] != '')
          $NumRegIdTribAttr = 'NumRegIdTrib="'.$data['comercioExterior']['Destinatario']['NumRegIdTrib'].'" ';

        $RfcAttr = '';
        if (isset($data['comercioExterior']['Destinatario']['Rfc']) && $data['comercioExterior']['Destinatario']['Rfc'] != '')
          $RfcAttr = 'Rfc="'.$data['comercioExterior']['Destinatario']['Rfc'].'" ';

        $curpAttr = '';
        if (isset($data['comercioExterior']['Destinatario']['Curp']) && $data['comercioExterior']['Destinatario']['Curp'] != '')
          $curpAttr = 'Curp="'.$data['comercioExterior']['Destinatario']['Curp'].'" ';

        $NombreAttr = '';
        if (isset($data['comercioExterior']['Destinatario']['Nombre']) && $data['comercioExterior']['Destinatario']['Nombre'] != '')
          $NombreAttr = 'Nombre="'.$data['comercioExterior']['Destinatario']['Nombre'].'" ';

        $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬<cce:Destinatario '.$NumRegIdTribAttr.
                              $RfcAttr.
                              $curpAttr.
                              $NombreAttr.
                              '>';
        // Domicilio.
        if(isset($data['comercioExterior']['Destinatario']['Domicilio']) && count($data['comercioExterior']['Destinatario']['Domicilio']) > 0)
        {
          $CalleAttr        = 'Calle="'.$data['comercioExterior']['Destinatario']['Domicilio']['Calle'].'" ';
          $EstadoAttr       = 'Estado="'.$data['comercioExterior']['Destinatario']['Domicilio']['Estado'].'" ';
          $PaisAttr         = 'Pais="'.$data['comercioExterior']['Destinatario']['Domicilio']['Pais'].'" ';
          $CodigoPostalAttr = 'CodigoPostal="'.$data['comercioExterior']['Destinatario']['Domicilio']['CodigoPostal'].'" ';

          $NumeroExteriorAttr = '';
          if (isset($data['comercioExterior']['Destinatario']['Domicilio']['NumeroExterior']) && $data['comercioExterior']['Destinatario']['Domicilio']['NumeroExterior'] != '')
            $NumeroExteriorAttr = 'NumeroExterior="'.$data['comercioExterior']['Destinatario']['Domicilio']['NumeroExterior'].'" ';

          $NumeroInteriorAttr = '';
          if (isset($data['comercioExterior']['Destinatario']['Domicilio']['NumeroInterior']) && $data['comercioExterior']['Destinatario']['Domicilio']['NumeroInterior'] != '')
            $NumeroInteriorAttr = 'NumeroInterior="'.$data['comercioExterior']['Destinatario']['Domicilio']['NumeroInterior'].'" ';

          $ColoniaAttr = '';
          if (isset($data['comercioExterior']['Destinatario']['Domicilio']['Colonia']) && $data['comercioExterior']['Destinatario']['Domicilio']['Colonia'] != '')
            $ColoniaAttr = 'Colonia="'.$data['comercioExterior']['Destinatario']['Domicilio']['Colonia'].'" ';

          $LocalidadAttr = '';
          if (isset($data['comercioExterior']['Destinatario']['Domicilio']['Localidad']) && $data['comercioExterior']['Destinatario']['Domicilio']['Localidad'] != '')
            $LocalidadAttr = 'Localidad="'.$data['comercioExterior']['Destinatario']['Domicilio']['Localidad'].'" ';

          $ReferenciaAttr = '';
          if (isset($data['comercioExterior']['Destinatario']['Domicilio']['Referencia']) && $data['comercioExterior']['Destinatario']['Domicilio']['Referencia'] != '')
            $ReferenciaAttr = 'Referencia="'.$data['comercioExterior']['Destinatario']['Domicilio']['Referencia'].'" ';

          $MunicipioAttr = '';
          if (isset($data['comercioExterior']['Destinatario']['Domicilio']['Municipio']) && $data['comercioExterior']['Destinatario']['Domicilio']['Municipio'] != '')
            $MunicipioAttr = 'Municipio="'.$data['comercioExterior']['Destinatario']['Domicilio']['Municipio'].'" ';

          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<cce:Domicilio '.$CalleAttr.
                                    $EstadoAttr.
                                    $PaisAttr.
                                    $CodigoPostalAttr.
                                    $NumeroExteriorAttr.
                                    $NumeroInteriorAttr.
                                    $ColoniaAttr.
                                    $LocalidadAttr.
                                    $ReferenciaAttr.
                                    $MunicipioAttr.
                                    '/>';
        }
        $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬</cce:Destinatario>';
      }

      // Mercancias.
      if(isset($data['comercioExterior']['Mercancias']) && count($data['comercioExterior']['Mercancias']) > 0)
      {
        $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬<cce:Mercancias>';
        // Mercancia.
        foreach ($data['comercioExterior']['Mercancias'] as $mercancia)
        {
          $NoIdentificacionAttr = 'NoIdentificacion="'.$mercancia['NoIdentificacion'].'" ';
          $ValorDolaresAttr     = 'ValorDolares="'.$mercancia['ValorDolares'].'" ';

          $FraccionArancelariaAttr = '';
          if (isset($mercancia['FraccionArancelaria']) && $mercancia['FraccionArancelaria'] != '')
            $FraccionArancelariaAttr = 'FraccionArancelaria="'.$mercancia['FraccionArancelaria'].'" ';

          $CantidadAduanaAttr = '';
          if (isset($mercancia['CantidadAduana']) && $mercancia['CantidadAduana'] != '')
            $CantidadAduanaAttr = 'CantidadAduana="'.$mercancia['CantidadAduana'].'" ';

          $UnidadAduanaAttr = '';
          if (isset($mercancia['UnidadAduana']) && $mercancia['UnidadAduana'] != '')
            $UnidadAduanaAttr = 'UnidadAduana="'.$mercancia['UnidadAduana'].'" ';

          $ValorUnitarioAduanaAttr = '';
          if (isset($mercancia['ValorUnitarioAduana']) && $mercancia['ValorUnitarioAduana'] != '')
            $ValorUnitarioAduanaAttr = 'ValorUnitarioAduana="'.$mercancia['ValorUnitarioAduana'].'" ';

          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<cce:Mercancia '.$NoIdentificacionAttr.
                                    $ValorDolaresAttr.
                                    $FraccionArancelariaAttr.
                                    $CantidadAduanaAttr.
                                    $UnidadAduanaAttr.
                                    $ValorUnitarioAduanaAttr.
                                    '>';
          if(isset($mercancia['DescripcionesEspecificas']) && count($mercancia['DescripcionesEspecificas']) > 0)
          {
            foreach ($mercancia['DescripcionesEspecificas'] as $desc_espe) {
              $MarcaAttr = 'Marca="'.$desc_espe['Marca'].'" ';

              $ModeloAttr = '';
              if (isset($desc_espe['Modelo']) && $desc_espe['Modelo'] != '')
                $ModeloAttr = 'Modelo="'.$desc_espe['Modelo'].'" ';

              $SubModeloAttr = '';
              if (isset($desc_espe['SubModelo']) && $desc_espe['SubModelo'] != '')
                $SubModeloAttr = 'SubModelo="'.$desc_espe['SubModelo'].'" ';

              $NumeroSerieAttr = '';
              if (isset($desc_espe['NumeroSerie']) && $desc_espe['NumeroSerie'] != '')
                $NumeroSerieAttr = 'NumeroSerie="'.$desc_espe['NumeroSerie'].'" ';

              $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬<cce:DescripcionesEspecificas '.$MarcaAttr.
                                              $ModeloAttr.
                                              $SubModeloAttr.
                                              $NumeroSerieAttr.
                                              '/>';
            }
          }

          $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬¬</cce:Mercancia>';
        }

        $xml .= '¬¬¬¬¬¬¬¬¬¬¬¬</cce:Mercancias>';
      }

      $xml .= '¬¬¬¬</cce:ComercioExterior>';
    }

    return $xml;
  }

  /*
   |------------------------------------------------------------------------
   | FUNCIONES HELPERS
   |------------------------------------------------------------------------
   */

  /**
   * Reemplaza los siguientes caracteres especiales segun anexo 20:
   *
   *   En el caso del & se deberá usar la secuencia &amp;
   *   En el caso del “ se deberá usar la secuencia &quot;
   *   En el caso del < se deberá usar la secuencia &lt;
   *   En el caso del > se deberá usar la secuencia &gt;
   *   En el caso del ‘ se deberá usar la secuencia &apos;
   *
   * @param  string $texto
   * @return string
   */
  public function replaceSpecialChars($texto, $orig=false)
  {
    if ($orig) {
      return preg_replace(array('/”/', '/’/'), array('"', '\''), $texto);
    } else {
      $texto = preg_replace(array('/”/', '/’/'), array('"', '\''), $texto);
      return preg_replace('/&#0*39;/', '&apos;', htmlspecialchars($texto, ENT_QUOTES));
    }

    // $caracteres = array('/&/', '/</', '/>/', '/”/', '/"/', '/\'/', '/’/');
    // $reemplazo  =  array('&amp;', '&lt;', '&gt;', '&quot;', '&quot;', '&apos;', '&apos;');
    // return preg_replace($caracteres, $reemplazo, $texto);
  }

  /**
   * Da formato numerico a una cadena
   *
   * @param string|int $number
   * @param int $decimales
   * @param string $sigini
   * @param boolean $condecim
   *
   * @return string
   */
  private function limpiaDecimales($number, $decimales=2, $sigini='$', $condecim=true)
  {
    $number = floatval($number);
    $num = explode('.', $number);
    if($condecim)
    {
      if(isset($num[1]))
        $decimales = (strlen($num[1])<$decimales? strlen($num[1]): $decimales);
      else
        $decimales = 0;
    }
    return $sigini.number_format($number, $decimales, '.', ',');
  }

  /*
   |-------------------------------------------------------------------------
   | FUNCIONES PARA GENERAR PDF's
   |-------------------------------------------------------------------------
   */

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

				if(!file_exists(APPPATH.'media/cfd/facturasPDF/'.$dir_anio.'/')){
					$this->crearFolder(APPPATH.'media/cfd/facturasPDF/', $dir_anio.'/');
				}
				if(!file_exists(APPPATH.'media/cfd/facturasPDF/'.$dir_anio.'/'.$dir_mes.'/')){
					$this->crearFolder(APPPATH.'media/cfd/facturasPDF/'.$dir_anio.'/', $dir_mes.'/');
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

				$datos = array($item['cantidad'], $item['descripcion'], MyString::formatoNumero($item['precio_unit']),MyString::formatoNumero($item['importe']));

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
			$pdf->Cell(33, 6, MyString::formatoNumero($data['subtotal'],2) , 1, 0, 'C');
			$pdf->SetXY(175, ($y+11));

			if (strtoupper($data['crfc']) != 'XAXX010101000') {
				$pdf->Cell(33, 6, MyString::formatoNumero($data['importe_iva'],2) , 1, 0, 'C');
				$pdf->SetXY(175, ($y+17));
			}

			if (isset($data['total_isr'])) {
				$pdf->Cell(33, 6, (isset($data['total_isr'])) ? MyString::formatoNumero($data['total_isr'],2) : '$0.00' , 1, 0, 'C');
				$pdf->SetXY(175, ($y+23));
			}

			$pdf->Cell(33, 6, MyString::formatoNumero($data['total'],2) , 1, 0, 'C');

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
