<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class polizas_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
  }

  public function getCuentaIvaTrasladado($basic=true){
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE nivel = 4 AND nombre like 'IVA TRASLADADO'")->row();
    return $basic? $data->cuenta: $data;
  }
  public function getCuentaIvaXTrasladar($basic=true){
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE nivel = 4 AND nombre like 'IVA X TRASLADAR'")->row();
    return $basic? $data->cuenta: $data;
  }
  public function getCuentaIvaRetCobradoAc($basic=true){
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_padre = 39 AND nivel = 4 AND nombre like 'IVA RETENIDO COBRADO'")->row();
    return $basic? $data->cuenta: $data;
  }
  public function getCuentaIvaRetXCobrarAc($basic=true){
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_padre = 39 AND nivel = 4 AND nombre like 'IVA RETENIDO X COBRAR'")->row();
    return $basic? $data->cuenta: $data;
  }
  public function getCuentaNCVenta($basic=true){
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_padre = 39 AND nivel = 4 AND nombre like 'IVA RETENIDO X COBRAR'")->row();
    return $basic? $data->cuenta: $data;
  }
  public function getCuentaIvaXAcreditar($basic=true){
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_padre = 231 AND nivel = 4 AND nombre like '%IVA ACREDITABLE PO%'")->row();
    return $basic? $data->cuenta: $data;
  }
  public function getCuentaIvaAcreditado($basic=true){
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_padre = 231 AND nivel = 4 AND nombre like '%IVA ACREDITABLE PA%'")->row();
    return $basic? $data->cuenta: $data;
  }
  public function getCuentaIvaRetXPagar($basic=true){
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_padre = 1191 AND nivel = 4 AND nombre like '4% RETENCION IVA X PAGAR'")->row();
    return $basic? $data->cuenta: $data;
  }
  public function getCuentaIvaRetPagado($basic=true){
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_padre = 1191 AND nivel = 4 AND nombre like '4% RETENCION IVA PAGADO'")->row();
    return $basic? $data->cuenta: $data;
  }

  public function setEspacios($texto, $posiciones, $direccion='l')
  {
    $len   = strlen($texto);
    $texto = $len>$posiciones? substr($texto, 0, $posiciones): $texto;
    $len   = strlen($texto);
    $faltante = $posiciones-$len;
    $espacios = '';
    if ($faltante > 0) 
      for ($i=0; $i < $faltante; $i++)
        $espacios .= ' ';

    if($direccion=='l')
      $texto .= $espacios;
    else
      $texto = $espacios.$texto;

    return $texto.' ';
  }
  public function numero($numero)
  {
    $numero = str_replace(',', '', String::formatoNumero($numero, 2, '') );
    $num = explode('.', $numero);
    if(!isset($num[1]))
      $numero .= '.0';
    return $numero;
  }

   /**
    * OBTIENE EL FOLIO PARA LA POLIZA CON RANGOS DE ACUERDO AL TIPO
    * @return [type]
    */
  public function getFolio($tipo=null, $tipo2=null, $tipo3=null){
    $tipo  = $tipo!=null? $tipo: $this->input->post('tipo');
    $tipo2 = $tipo2!=null? $tipo2: $this->input->post('tipo2');
    $tipo3 = $tipo3!=null? $tipo3: $this->input->post('tipo3');

    $rangos = array(
      'diario_ventas'    => array(1, 50),
      'diario_ventas_nc' => array(51, 100),
      'diario_gastos'    => array(101, 200),
      'nomina'           => array(201, 250),
      'ingresos'         => array(1, 1000),
      'egreso_limon'     => array(1, 299),
      'egreso_cheque'    => array(300, 599),
      'egreso_gasto'     => array(600, 1500),
      );

    $response = array('folio' => '', 'concepto' => '');
    $rango_sel = '';
    $sql = '';
    if ($tipo == '3') //Diarios
    {
      $response['concepto'] = "Gastos del dia ".String::fechaATexto(date("Y-m-d"));
      $rango_sel         = 'diario_gastos';
      if ($tipo2 == 'v')
      {
        $response['concepto'] = "Ventas del dia ".String::fechaATexto(date("Y-m-d"));
        $rango_sel         = 'diario_ventas';
      }elseif ($tipo2 == 'vnc') 
      {
        $response['concepto'] = "Notas de Credito del dia ".String::fechaATexto(date("Y-m-d"));
        $rango_sel         = 'diario_ventas_nc';
      }elseif ($tipo2 == 'no') 
      {
        $response['concepto'] = "Nomina ".String::fechaATexto(date("Y-m-d"));
        $rango_sel            = 'nomina';
      }
      $sql = " AND tipo = {$tipo} AND tipo2 = '{$tipo2}'";
    }elseif ($tipo == '1') //Ingresos
    {
      $rango_sel = 'ingresos';
      $sql       = " AND tipo = {$tipo}";
    }elseif($tipo == '2'){ //Egresos = 2
      $response['concepto'] = "Egresos de limon, ".String::fechaATexto(date("Y-m-d"));
      $rango_sel            = 'egreso_limon';
      if ($tipo3 == 'ec')
      {
        $response['concepto'] = "Egresos de cheques, ".String::fechaATexto(date("Y-m-d"));
        $rango_sel            = 'egreso_cheque';
      }elseif ($tipo3 == 'eg') 
      {
        $response['concepto'] = "Egresos de gastos, ".String::fechaATexto(date("Y-m-d"));
        $rango_sel            = 'egreso_gasto';
      }
      $sql       = " AND tipo = {$tipo} AND tipo2 = '{$tipo3}'";
    }

    $anio = date("Y"); $mes = date("m");
    $result = $this->db->query("SELECT * FROM polizas
                               WHERE extract(year FROM fecha) = '{$anio}' AND extract(month FROM fecha) = '{$mes}' 
                                {$sql} ORDER BY id_poliza DESC LIMIT 1");
    $folio = $rangos[$rango_sel][0];
    if ($result->num_rows() > 0) {
      $row = $result->row();
      $folio = $row->folio+1;
    }

    if($folio > $rangos[$rango_sel][1])
      $folio = '';
    $response['folio'] = $folio;
    return $response;
  }

  /**
   * GENERA UNA POLIZA DE DIARIOS PARA LAS VENTAS
   * @return [type] [description]
   */
  public function polizaDiarioVentas()
  {
    $response = array('data' => '', 'facturas' => array());
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
      $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      $sql .= " AND Date(f.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    }

    if ($this->input->get('fid_empresa') != '')
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";

    $query = $this->db->query(
      "SELECT id_factura
       FROM facturacion AS f
      WHERE status <> 'ca' AND status <> 'b' AND is_factura = 't' 
          AND poliza_diario = 'f' AND id_nc IS NULL 
         {$sql}
      ORDER BY id_factura ASC
      ");

    if($query->num_rows() > 0)
    {
      $data = $query->result();
      $response['facturas'] = $data;

      $this->load->model('facturacion_model');

      $impuestos = array('iva_trasladar' => array('cuenta_cpi' => $this->getCuentaIvaXTrasladar(), 'importe' => 0, 'tipo' => '1'),
                         'iva_retenido' => array('cuenta_cpi' => $this->getCuentaIvaRetXCobrarAc(), 'importe' => 0, 'tipo' => '0'), );

      //Agregamos el header de la poliza
      $response['data'] = $this->setEspacios('P',2).
                          $this->setEspacios(date("Ymd"),8).$this->setEspacios('3',4,'r').  //tipo poliza = 3 poliza diarios
                          $this->setEspacios($this->input->get('ffolio'),9,'r').  //folio poliza
                          $this->setEspacios('1',1). //clase
                          $this->setEspacios('0',10). //iddiario
                          $this->setEspacios($this->input->get('fconcepto'),100). //concepto
                          $this->setEspacios('11',2). //sistema de origen
                          $this->setEspacios('0',1). //impresa
                          $this->setEspacios('0',1)."\n"; //ajuste
      //Contenido de la Poliza
      foreach ($data as $key => $value) 
      {
        $inf_factura = $this->facturacion_model->getInfoFactura($value->id_factura);

        //Colocamos el Cargo al Cliente de la factura
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($inf_factura['info']->cliente->cuenta_cpi,30).  //cuenta contpaq
                          $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).  //referencia movimiento
                          $this->setEspacios('0',1).  //tipo movimiento, clientes es un cargo = 0
                          $this->setEspacios( $this->numero($inf_factura['info']->total) , 20).  //importe movimiento - retencion
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios('FAC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio, 100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio
        
        $impuestos['iva_trasladar']['importe'] = 0;
        $impuestos['iva_retenido']['importe']  = 0;
        //Colocamos los Ingresos de la factura
        foreach ($inf_factura['productos'] as $key => $value) 
        {
          $impuestos['iva_trasladar']['importe'] += $value->iva;
          $impuestos['iva_retenido']['importe']  += $value->retencion_iva;
          $response['data'] .= $this->setEspacios('M',2).
                          $this->setEspacios($value->cuenta_cpi,30).
                          $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).
                          $this->setEspacios('1',1).  //clientes es un abono = 1
                          $this->setEspacios( $this->numero($value->importe) , 20).
                          $this->setEspacios('0',10).
                          $this->setEspacios('0.0',20).
                          $this->setEspacios('FAC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio,100).
                          $this->setEspacios('',4)."\n";
        }
        //Colocamos los impuestos de la factura
        foreach ($impuestos as $key => $impuesto) 
        {
          if ($impuestos[$key]['importe'] > 0) 
          {
            $response['data'] .= $this->setEspacios('M',2).
                          $this->setEspacios($impuesto['cuenta_cpi'],30).
                          $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).
                          $this->setEspacios($impuesto['tipo'],1).  //clientes es un abono = 1
                          $this->setEspacios( $this->numero($impuesto['importe']) , 20).
                          $this->setEspacios('0',10).
                          $this->setEspacios('0.0',20).
                          $this->setEspacios('FAC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio,100).
                          $this->setEspacios('',4)."\n";
          }
        }
        unset($inf_factura);
      }
    }

    return $response;
  }

  /**
   * GENERA UNA POLIZA DE DIARIOS PARA LAS NOTAS DE CREDITO DE VENTAS
   * @return [type] [description]
   */
  public function polizaDiarioVentasNC()
  {
    $response = array('data' => '', 'facturas' => array());
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
      $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      $sql .= " AND Date(f.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    }

    if ($this->input->get('fid_empresa') != '')
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";

    $query = $this->db->query(
      "SELECT id_factura
       FROM facturacion AS f
      WHERE status <> 'ca' AND status <> 'b' 
          AND poliza_diario = 'f' AND id_nc IS NOT NULL 
         {$sql}
      ORDER BY id_factura ASC
      ");

    if($query->num_rows() > 0)
    {
      $data = $query->result();
      $response['facturas'] = $data;

      $this->load->model('facturacion_model');

      $impuestos = array('iva_trasladar' => array('cuenta_cpi' => $this->getCuentaIvaTrasladado(), 'importe' => 0, 'tipo' => '1'),
                         'iva_retenido' => array('cuenta_cpi' => $this->getCuentaIvaRetXCobrarAc(), 'importe' => 0, 'tipo' => '0'), );

      //Agregamos el header de la poliza
      $response['data'] = $this->setEspacios('P',2).
                          $this->setEspacios(date("Ymd"),8).$this->setEspacios('3',4,'r').  //tipo poliza = 3 poliza diarios
                          $this->setEspacios($this->input->get('ffolio'),9,'r').  //folio poliza
                          $this->setEspacios('1',1). //clase
                          $this->setEspacios('0',10). //iddiario
                          $this->setEspacios($this->input->get('fconcepto'),100). //concepto
                          $this->setEspacios('11',2). //sistema de origen
                          $this->setEspacios('0',1). //impresa
                          $this->setEspacios('0',1)."\n"; //ajuste
      //Contenido de la Poliza
      foreach ($data as $key => $value) 
      {
        $inf_factura = $this->facturacion_model->getInfoFactura($value->id_factura);

        //Colocamos el Cargo al Cliente de la factura
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($inf_factura['info']->cliente->cuenta_cpi,30).  //cuenta contpaq
                          $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, clientes es un cargo = 1
                          $this->setEspacios( $this->numero($inf_factura['info']->total) , 20).  //importe movimiento - retencion
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios('NC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio, 100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio
        
        $impuestos['iva_trasladar']['importe'] = 0;
        $impuestos['iva_retenido']['importe']  = 0;
        //Colocamos los Ingresos de la factura
        foreach ($inf_factura['productos'] as $key => $value) 
        {
          $impuestos['iva_trasladar']['importe'] += $value->iva;
          $impuestos['iva_retenido']['importe']  += $value->retencion_iva;
          $response['data'] .= $this->setEspacios('M',2).
                          $this->setEspacios($this->getCuentaNCVenta(), 30).  //cuenta nc ventas
                          $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).
                          $this->setEspacios('0',1).  //ingresos es un abono = 0
                          $this->setEspacios( $this->numero($value->importe) , 20).
                          $this->setEspacios('0',10).
                          $this->setEspacios('0.0',20).
                          $this->setEspacios('NC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio,100).
                          $this->setEspacios('',4)."\n";
        }
        //Colocamos los impuestos de la factura, negativos por nota de credito
        foreach ($impuestos as $key => $impuesto) 
        {
          if ($impuestos[$key]['importe'] > 0) 
          {
            $response['data'] .= $this->setEspacios('M',2).
                          $this->setEspacios($impuesto['cuenta_cpi'],30).
                          $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).
                          $this->setEspacios($impuesto['tipo'],1).  //clientes es un abono = 1
                          $this->setEspacios( '-'.$this->numero($impuesto['importe']) , 20).
                          $this->setEspacios('0',10).
                          $this->setEspacios('0.0',20).
                          $this->setEspacios('FAC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio,100).
                          $this->setEspacios('',4)."\n";
          }
        }
        unset($inf_factura);
      }
    }

    return $response;
  }

  /**
   * GENERA UNA POLIZA DE DIARIOS PARA LOS GASTOS, COMPRAS
   * @return [type] [description]
   */
  public function polizaDiarioGastos()
  {
    $response = array('data' => '', 'facturas' => array());
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
      $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      $sql .= " AND Date(f.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
      $sql2 .= " AND Date(f.fecha_tara) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    }

    if ($this->input->get('fid_empresa') != '')
    {
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";
      $sql2 .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";
    }

    $query = $this->db->query(
      "SELECT id_compra
       FROM compras AS f
      WHERE status <> 'ca'
          AND poliza_diario = 'f'
         {$sql}
      ORDER BY id_compra ASC
      ");
    //Gastos de limon
    $query2 = $this->db->query(
      "SELECT id_bascula
       FROM bascula AS f
      WHERE status = 't' AND poliza_diario = 'f'
        AND tipo = 'en' AND accion = 'sa'
         {$sql2}
      ORDER BY id_bascula ASC
      ");

    if($query->num_rows() > 0 || $query2->num_rows() > 0)
    {
      $data = $query->result();
      $response['facturas'] = $data;
      $data2 = $query2->result();
      $response['bascula'] = $data2;

      $this->load->model('compras_model');
      $this->load->model('bascula_model');

      $impuestos = array('iva_acreditar' => array('cuenta_cpi' => $this->getCuentaIvaXAcreditar(), 'importe' => 0, 'tipo' => '0'),
                         'iva_retenido' => array('cuenta_cpi' => $this->getCuentaIvaRetXPagar(), 'importe' => 0, 'tipo' => '1'), );

      //Agregamos el header de la poliza
      $response['data'] = $this->setEspacios('P',2).
                          $this->setEspacios(date("Ymd"),8).$this->setEspacios('3',4,'r').  //tipo poliza = 3 poliza diarios
                          $this->setEspacios($this->input->get('ffolio'),9,'r').  //folio poliza
                          $this->setEspacios('1',1). //clase
                          $this->setEspacios('0',10). //iddiario
                          $this->setEspacios($this->input->get('fconcepto'),100). //concepto
                          $this->setEspacios('11',2). //sistema de origen
                          $this->setEspacios('0',1). //impresa
                          $this->setEspacios('0',1)."\n"; //ajuste
      
      //Contenido de la Poliza de Compras
      foreach ($data as $key => $value) 
      {
        $inf_compra = $this->compras_model->getInfoCompra($value->id_compra);

        //Colocamos el Cargo al Proveedor de la factura
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($inf_compra['info']->proveedor->cuenta_cpi,30).  //cuenta contpaq
                          $this->setEspacios($inf_compra['info']->serie.$inf_compra['info']->folio,10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, proveedor es un abono = 1
                          $this->setEspacios( $this->numero($inf_compra['info']->total) , 20).  //importe movimiento - retencion
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios('Compra No. '.$inf_compra['info']->serie.$inf_compra['info']->folio, 100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio
        
        $impuestos['iva_acreditar']['importe'] = 0;
        $impuestos['iva_retenido']['importe']  = 0;
        //Colocamos los productos de la factura
        foreach ($inf_compra['productos'] as $key => $value) 
        {
          $impuestos['iva_acreditar']['importe'] += $value->iva;
          $impuestos['iva_retenido']['importe']  += $value->retencion_iva;
          $response['data'] .= $this->setEspacios('M',2).
                          $this->setEspacios($value->cuenta_cpi, 30).  //cuenta conpaq
                          $this->setEspacios($inf_compra['info']->serie.$inf_compra['info']->folio,10).
                          $this->setEspacios('0',1).  //cargo, = 0
                          $this->setEspacios( $this->numero($value->importe) , 20).
                          $this->setEspacios('0',10).
                          $this->setEspacios('0.0',20).
                          $this->setEspacios('Compra No. '.$inf_compra['info']->serie.$inf_compra['info']->folio,100).
                          $this->setEspacios('',4)."\n";
        }
        //Colocamos los impuestos de la factura, negativos por nota de credito
        foreach ($impuestos as $key => $impuesto) 
        {
          if ($impuestos[$key]['importe'] > 0) 
          {
            $response['data'] .= $this->setEspacios('M',2).
                          $this->setEspacios($impuesto['cuenta_cpi'],30).
                          $this->setEspacios($inf_compra['info']->serie.$inf_compra['info']->folio,10).
                          $this->setEspacios($impuesto['tipo'],1).  //de acuerdo al impuesto
                          $this->setEspacios( $this->numero($impuesto['importe']) , 20).
                          $this->setEspacios('0',10).
                          $this->setEspacios('0.0',20).
                          $this->setEspacios('Compra No. '.$inf_compra['info']->serie.$inf_compra['info']->folio,100).
                          $this->setEspacios('',4)."\n";
          }
        }
        unset($inf_compra);
      }

      //Contenido de la Poliza de Bascula
      foreach ($data2 as $key => $value) 
      {
        $inf_compra = $this->bascula_model->getBasculaInfo($value->id_bascula);

        //Colocamos el Cargo al Proveedor de la factura
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($inf_compra['info'][0]->cpi_proveedor,30).  //cuenta contpaq
                          $this->setEspacios($inf_compra['info'][0]->folio,10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, proveedor es un abono = 1
                          $this->setEspacios( $this->numero($inf_compra['info'][0]->importe) , 20).  //importe movimiento - retencion
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios('Compra Boleta. '.$inf_compra['info'][0]->folio, 100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio
        
        // $impuestos['iva_acreditar']['importe'] = 0;
        // $impuestos['iva_retenido']['importe']  = 0;
        //Colocamos los productos de la factura
        foreach ($inf_compra['cajas'] as $key => $value) 
        {
          // $impuestos['iva_acreditar']['importe'] += $value->iva;
          // $impuestos['iva_retenido']['importe']  += $value->retencion_iva;
          $response['data'] .= $this->setEspacios('M',2).
                          $this->setEspacios($value->cuenta_cpi, 30).  //cuenta conpaq
                          $this->setEspacios($inf_compra['info'][0]->folio,10).
                          $this->setEspacios('0',1).  //cargo, = 0
                          $this->setEspacios( $this->numero($value->importe) , 20).
                          $this->setEspacios('0',10).
                          $this->setEspacios('0.0',20).
                          $this->setEspacios('Compra Boleta. '.$inf_compra['info'][0]->folio,100).
                          $this->setEspacios('',4)."\n";
        }

        // //Colocamos los impuestos de la factura, negativos por nota de credito
        // foreach ($impuestos as $key => $impuesto) 
        // {
        //   if ($impuestos[$key]['importe'] > 0) 
        //   {
        //     $response['data'] .= $this->setEspacios('M',2).
        //                   $this->setEspacios($impuesto['cuenta_cpi'],30).
        //                   $this->setEspacios($inf_compra['info']->serie.$inf_compra['info']->folio,10).
        //                   $this->setEspacios($impuesto['tipo'],1).  //de acuerdo al impuesto
        //                   $this->setEspacios( $this->numero($impuesto['importe']) , 20).
        //                   $this->setEspacios('0',10).
        //                   $this->setEspacios('0.0',20).
        //                   $this->setEspacios('Compra No. '.$inf_compra['info']->serie.$inf_compra['info']->folio,100).
        //                   $this->setEspacios('',4)."\n";
        //   }
        // }
        unset($inf_compra);
      }

    }

    return $response;
  }

  /**
   * GENERA UNA POLIZA DE DIARIOS PARA LAS NOMINAS
   * @return [type] [description]
   */
  public function polizaDiarioNomina()
  {
    $response = array('data' => '', 'facturas' => array());
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
      $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      $sql .= " AND Date(f.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    }

    if ($this->input->get('fid_empresa') != '')
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";

    $query = $this->db->query(
      "SELECT id_empleado, id_empresa, anio, semana, fecha_inicio, fecha_final, sueldo_semanal, vacaciones, 
          prima_vacacional, aguinaldo, horas_extras, subsidio_pagado, imss, infonavit, isr, total_neto
       FROM nomina_fiscal AS f
      WHERE anio > 0 
         {$sql}
      ORDER BY id_empleado ASC, id_empresa ASC, semana ASC
      ");
    $nominas = array();
    foreach ($query->result() as $key => $value)
    {
      if(isset($nominas[$value->id_empresa.$value->anio.$value->semana]))
      {
        $nominas[$value->id_empresa.$value->anio.$value->semana]->sueldo_semanal   += $value->sueldo_semanal;
        $nominas[$value->id_empresa.$value->anio.$value->semana]->vacaciones       += $value->vacaciones;
        $nominas[$value->id_empresa.$value->anio.$value->semana]->prima_vacacional += $value->prima_vacacional;
        $nominas[$value->id_empresa.$value->anio.$value->semana]->aguinaldo        += $value->aguinaldo;
        $nominas[$value->id_empresa.$value->anio.$value->semana]->horas_extras     += $value->horas_extras;

        $nominas[$value->id_empresa.$value->anio.$value->semana]->subsidio_pagado += $value->subsidio_pagado;
        $nominas[$value->id_empresa.$value->anio.$value->semana]->imss            += $value->imss;
        $nominas[$value->id_empresa.$value->anio.$value->semana]->infonavit       += $value->infonavit;
        $nominas[$value->id_empresa.$value->anio.$value->semana]->isr             += $value->isr;
        $nominas[$value->id_empresa.$value->anio.$value->semana]->total_neto      += $value->total_neto;
      }else
        $nominas[$value->id_empresa.$value->anio.$value->semana] = $value;

      $query = $this->db->query(
      "SELECT id_empleado, id_empresa, anio, semana, monto
       FROM nomina_fiscal
      WHERE id_empleado =  AND id_empresa = AND anio = AND semana
      ");
    }

    var_dump($nominas);
    exit();

    if(count($nominas) > 0)
    {
      $data = $query->result();
      $response['facturas'] = $data;

      $this->load->model('facturacion_model');

      $impuestos = array('iva_trasladar' => array('cuenta_cpi' => $this->getCuentaIvaXTrasladar(), 'importe' => 0, 'tipo' => '1'),
                         'iva_retenido' => array('cuenta_cpi' => $this->getCuentaIvaRetXCobrarAc(), 'importe' => 0, 'tipo' => '0'), );

      //Agregamos el header de la poliza
      $response['data'] = $this->setEspacios('P',2).
                          $this->setEspacios(date("Ymd"),8).$this->setEspacios('3',4,'r').  //tipo poliza = 3 poliza diarios
                          $this->setEspacios($this->input->get('ffolio'),9,'r').  //folio poliza
                          $this->setEspacios('1',1). //clase
                          $this->setEspacios('0',10). //iddiario
                          $this->setEspacios("Nom {$data[0]->semana} Sem {$data[0]->fecha_inicio}-{$data[0]->fecha_final}",100). //concepto
                          $this->setEspacios('11',2). //sistema de origen
                          $this->setEspacios('0',1). //impresa
                          $this->setEspacios('0',1)."\n"; //ajuste
      //Contenido de la Poliza
      foreach ($data as $key => $value) 
      {
        $inf_factura = $this->facturacion_model->getInfoFactura($value->id_factura);

        //Colocamos el Cargo al Cliente de la factura
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($inf_factura['info']->cliente->cuenta_cpi,30).  //cuenta contpaq
                          $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).  //referencia movimiento
                          $this->setEspacios('0',1).  //tipo movimiento, clientes es un cargo = 0
                          $this->setEspacios( $this->numero($inf_factura['info']->total) , 20).  //importe movimiento - retencion
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios('FAC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio, 100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio
        
        $impuestos['iva_trasladar']['importe'] = 0;
        $impuestos['iva_retenido']['importe']  = 0;
        //Colocamos los Ingresos de la factura
        foreach ($inf_factura['productos'] as $key => $value) 
        {
          $impuestos['iva_trasladar']['importe'] += $value->iva;
          $impuestos['iva_retenido']['importe']  += $value->retencion_iva;
          $response['data'] .= $this->setEspacios('M',2).
                          $this->setEspacios($value->cuenta_cpi,30).
                          $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).
                          $this->setEspacios('1',1).  //clientes es un abono = 1
                          $this->setEspacios( $this->numero($value->importe) , 20).
                          $this->setEspacios('0',10).
                          $this->setEspacios('0.0',20).
                          $this->setEspacios('FAC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio,100).
                          $this->setEspacios('',4)."\n";
        }
        //Colocamos los impuestos de la factura
        foreach ($impuestos as $key => $impuesto) 
        {
          if ($impuestos[$key]['importe'] > 0) 
          {
            $response['data'] .= $this->setEspacios('M',2).
                          $this->setEspacios($impuesto['cuenta_cpi'],30).
                          $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).
                          $this->setEspacios($impuesto['tipo'],1).  //clientes es un abono = 1
                          $this->setEspacios( $this->numero($impuesto['importe']) , 20).
                          $this->setEspacios('0',10).
                          $this->setEspacios('0.0',20).
                          $this->setEspacios('FAC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio,100).
                          $this->setEspacios('',4)."\n";
          }
        }
        unset($inf_factura);
      }
    }

    return $response;
  }

  /**
   * GENERA UNA POLIZA DE INGRESOS
   * @return [type] [description]
   */
  public function polizaIngreso()
  {
    $response = array('data' => '', 'abonos' => array());
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
      $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      $sql .= " AND Date(fa.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    }

    if ($this->input->get('fid_empresa') != '')
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";

    $query = $this->db->query(
      "SELECT 
        fa.id_abono, f.id_factura, fa.ref_movimiento, fa.concepto, fa.total AS total_abono, 
        bc.cuenta_cpi, f.subtotal, f.total, f.importe_iva, f.retencion_iva, c.nombre_fiscal, 
        c.cuenta_cpi AS cuenta_cpi_cliente
      FROM facturacion AS f 
        INNER JOIN facturacion_abonos AS fa ON fa.id_factura = f.id_factura
        INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = fa.id_cuenta 
        INNER JOIN clientes AS c ON c.id_cliente = f.id_cliente 
      WHERE f.status <> 'ca' AND f.status <> 'b' AND fa.poliza_ingreso = 'f'
         {$sql}
      ORDER BY fa.id_abono ASC
      ");

    if($query->num_rows() > 0)
    {
      $data = $query->result();
      $response['abonos'] = $data;

      $this->load->model('facturacion_model');

      $impuestos = array(
        'iva_trasladar'  => array('cuenta_cpi' => $this->getCuentaIvaXTrasladar(), 'importe' => 0, 'tipo' => '1'),
        'iva_trasladado' => array('cuenta_cpi' => $this->getCuentaIvaTrasladado(), 'importe' => 0, 'tipo' => '0'),
        'iva_retener'    => array('cuenta_cpi' => $this->getCuentaIvaRetXCobrarAc(), 'importe' => 0, 'tipo' => '1'),
        'iva_retenido'   => array('cuenta_cpi' => $this->getCuentaIvaRetCobradoAc(), 'importe' => 0, 'tipo' => '0'), );

      //Agregamos el header de la poliza
      $response['data'] = $this->setEspacios('P',2).
                          $this->setEspacios(date("Ymd"),8).$this->setEspacios('1',4,'r').  //tipo poliza = 1 poliza ingresos
                          $this->setEspacios($this->input->get('ffolio'),9,'r').  //folio poliza
                          $this->setEspacios('1',1). //clase
                          $this->setEspacios('0',10). //iddiario
                          $this->setEspacios($this->input->get('fconcepto'),100). //concepto
                          $this->setEspacios('11',2). //sistema de origen
                          $this->setEspacios('0',1). //impresa
                          $this->setEspacios('0',1)."\n"; //ajuste
      //Contenido de la Poliza
      foreach ($data as $key => $value) 
      {
        //Se obtiene un registro del abono si es que se pago de mas
        $query_mayor = $this->db->query(
        "SELECT id_abono_otro, fecha, concepto, total, cuenta_cpi
        FROM facturacion_abonos_otros
        WHERE tipo = 'm' AND id_factura = {$value->id_factura} AND id_abono = {$value->id_abono}")->row();
        //Se obtiene un registro del abono si es que se pago de menos
        $query_saldar = $this->db->query(
        "SELECT id_abono_otro, fecha, concepto, total, cuenta_cpi
        FROM facturacion_abonos_otros
        WHERE tipo = 's' AND id_factura = {$value->id_factura} AND id_abono = {$value->id_abono}")->row();

        $factor = $value->total_abono*100/($value->total); //abono*100/total_factura
        $impuestos['iva_retener']['importe']    = $factor*$value->retencion_iva/100;
        $impuestos['iva_retenido']['importe']   = $impuestos['iva_retener']['importe'];

        $impuestos['iva_trasladar']['importe']  = $factor*($value->importe_iva)/100;
        $impuestos['iva_trasladado']['importe'] = $impuestos['iva_trasladar']['importe'];
        $subtotal = $value->total_abono;//-$impuestos['iva_retener']['importe']-$impuestos['iva_trasladar']['importe'];

        //Colocamos el Cargo al Banco que se deposito el dinero
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($value->cuenta_cpi,30).  //cuenta contpaq
                          $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                          $this->setEspacios('0',1).  //tipo movimiento, banco es un cargo = 0
                          $this->setEspacios( $this->numero($subtotal+( isset($query_mayor->id_abono_otro)? $query_mayor->total: 0)-( isset($query_saldar->id_abono_otro)? $query_saldar->total: 0) ) , 20).  //importe movimiento
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios($value->nombre_fiscal,100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio
        //Colocamos el Abono al Cliente que realizo el pago
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($value->cuenta_cpi_cliente,30).  //cuenta contpaq
                          $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, Cliente es un abono = 1
                          $this->setEspacios( $this->numero($subtotal), 20).  //importe movimiento
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios($value->concepto,100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio
        //Si hay abonos de mas se agregan a los mov
        if (isset($query_mayor->id_abono_otro))
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($query_mayor->cuenta_cpi,30).  //cuenta contpaq
                          $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, Cliente es un abono = 1
                          $this->setEspacios( $this->numero($query_mayor->total), 20).  //importe movimiento
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios($query_mayor->concepto,100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio
        //Si hay abonos de mas se agregan a los mov
        if (isset($query_saldar->id_abono_otro))
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($query_saldar->cuenta_cpi,30).  //cuenta contpaq
                          $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                          $this->setEspacios('0',1).  //tipo movimiento, Cliente es un abono = 1
                          $this->setEspacios( $this->numero($query_saldar->total), 20).  //importe movimiento
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios($query_saldar->concepto,100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio

        
        //Colocamos los impuestos de la factura
        foreach ($impuestos as $key => $impuesto) 
        {
          if ($impuestos[$key]['importe'] > 0) 
          {
            $response['data'] .= $this->setEspacios('M',2).
                          $this->setEspacios($impuesto['cuenta_cpi'],30).
                          $this->setEspacios($value->ref_movimiento,10).
                          $this->setEspacios($impuesto['tipo'],1).  //clientes es un abono = 1
                          $this->setEspacios( $this->numero($impuesto['importe']) , 20).
                          $this->setEspacios('0',10).
                          $this->setEspacios('0.0',20).
                          $this->setEspacios($value->concepto,100).
                          $this->setEspacios('',4)."\n";
          }
        }
      }
    }

    return $response;
  }

  /**
   * GENERA UNA POLIZA DE EGRESO de limon
   * @return [type] [description]
   */
  public function polizaEgresoLimon()
  {
    $response = array('data' => '', 'abonos' => array());
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
      $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      $sql .= " AND Date(fa.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    }

    if ($this->input->get('fid_empresa') != '')
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";

    $query = $this->db->query(
      "SELECT 
        fa.id_pago, '' AS ref_movimiento, fa.concepto, fa.monto AS total_abono, 
        bc.cuenta_cpi
      FROM bascula_pagos AS fa 
        INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = fa.id_cuenta 
        INNER JOIN bascula_pagos_basculas AS bpb ON bpb.id_pago = fa.id_pago 
        INNER JOIN bascula AS f ON f.id_bascula = bpb.id_bascula 
      WHERE fa.status = 't' AND fa.poliza_egreso = 'f'
         {$sql}
      GROUP BY fa.id_pago, fa.concepto, fa.monto, bc.cuenta_cpi 
      ORDER BY fa.id_pago ASC
      ");

    if($query->num_rows() > 0)
    {
      $data = $query->result();
      $response['abonos'] = $data;

      $this->load->model('facturacion_model');

      $impuestos = array(
        'iva_acreditar'  => array('cuenta_cpi' => $this->getCuentaIvaXAcreditar(), 'importe' => 0, 'tipo' => '1'),
        'iva_acreditado' => array('cuenta_cpi' => $this->getCuentaIvaAcreditado(), 'importe' => 0, 'tipo' => '0'),
        'iva_retener'    => array('cuenta_cpi' => $this->getCuentaIvaRetXPagar(), 'importe' => 0, 'tipo' => '0'),
        'iva_retenido'   => array('cuenta_cpi' => $this->getCuentaIvaRetPagado(), 'importe' => 0, 'tipo' => '1'), );

      //Agregamos el header de la poliza
      $response['data'] = $this->setEspacios('P',2).
                          $this->setEspacios(date("Ymd"),8).$this->setEspacios('2',4,'r').  //tipo poliza = 2 poliza egreso
                          $this->setEspacios($this->input->get('ffolio'),9,'r').  //folio poliza
                          $this->setEspacios('1',1). //clase
                          $this->setEspacios('0',10). //iddiario
                          $this->setEspacios($this->input->get('fconcepto'),100). //concepto
                          $this->setEspacios('11',2). //sistema de origen
                          $this->setEspacios('0',1). //impresa
                          $this->setEspacios('0',1)."\n"; //ajuste
      //Contenido de la Poliza
      foreach ($data as $key => $value) 
      {
        $data_frutas = $this->db->query(
            "SELECT b.id_bascula, b.importe, b.folio, p.id_proveedor, p.nombre_fiscal, p.cuenta_cpi 
            FROM bascula AS b 
              INNER JOIN bascula_pagos_basculas AS bpb ON bpb.id_bascula = b.id_bascula 
              INNER JOIN proveedores AS p ON p.id_proveedor = b.id_proveedor 
            WHERE bpb.id_pago = {$value->id_pago}")->result();

        // $factor = $value->total_abono*100/($value->total); //abono*100/total_factura
        // $impuestos['iva_retener']['importe']    = $factor*$value->retencion_iva/100;
        // $impuestos['iva_retenido']['importe']   = $impuestos['iva_retener']['importe'];

        // $impuestos['iva_acreditar']['importe']  = $factor*($value->importe_iva)/100;
        // $impuestos['iva_acreditado']['importe'] = $impuestos['iva_acreditar']['importe'];
        $subtotal = $value->total_abono;//-$impuestos['iva_retener']['importe']-$impuestos['iva_acreditar']['importe'];

        //Colocamos el Cargo al Banco que se deposito el dinero
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($value->cuenta_cpi,30).  //cuenta contpaq
                          $this->setEspacios($value->ref_movimiento.'Fruta',10).  //referencia movimiento
                          $this->setEspacios('0',1).  //tipo movimiento, banco es un cargo = 0
                          $this->setEspacios( $this->numero($subtotal) , 20).  //importe movimiento
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios($data_frutas[0]->nombre_fiscal,100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio
        //Colocamos el Abono al Proveedor que realizo el pago
        foreach ($data_frutas as $key => $value_fruta)
        {
          $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($value_fruta->cuenta_cpi,30).  //cuenta contpaq
                          $this->setEspacios($value->ref_movimiento.'Fruta',10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, Proveedor es un abono = 1
                          $this->setEspacios( $this->numero($value_fruta->importe), 20).  //importe movimiento
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios($value->concepto.' (Boleta:'.$value_fruta->folio.')',100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio
        }
        
        // //Colocamos los impuestos de la factura
        // foreach ($impuestos as $key => $impuesto) 
        // {
        //   if ($impuestos[$key]['importe'] > 0) 
        //   {
        //     $response['data'] .= $this->setEspacios('M',2).
        //                   $this->setEspacios($impuesto['cuenta_cpi'],30).
        //                   $this->setEspacios($value->ref_movimiento,10).
        //                   $this->setEspacios($impuesto['tipo'],1).  //clientes es un abono = 1
        //                   $this->setEspacios( $this->numero($impuesto['importe']) , 20).
        //                   $this->setEspacios('0',10).
        //                   $this->setEspacios('0.0',20).
        //                   $this->setEspacios($value->concepto,100).
        //                   $this->setEspacios('',4)."\n";
        //   }
        // }
      }
    }

    return $response;
  }

  /**
   * GENERA UNA POLIZA DE EGRESO DE CHEQUES
   * @return [type] [description]
   */
  public function polizaEgreso($tipo_movimientos='cheque')
  {
    $response = array('data' => '', 'abonos' => array());
    $sql = $sql2 = '';

    if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
      $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m-d");
      $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
    }
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
      $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
      $sql .= " AND Date(fa.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
    }

    if ($this->input->get('fid_empresa') != '')
      $sql .= " AND f.id_empresa = '".$_GET['fid_empresa']."'";

    if($tipo_movimientos == 'cheque')
      $sql .= " AND LOWER(bm.metodo_pago) = 'cheque' ";
    else
      $sql .= " AND LOWER(bm.metodo_pago) <> 'cheque' ";

    $query = $this->db->query(
      "SELECT 
        fa.id_abono, f.id_compra, fa.ref_movimiento, fa.concepto, fa.total AS total_abono, 
        bc.cuenta_cpi, f.subtotal, f.total, f.importe_iva, f.retencion_iva, c.nombre_fiscal, 
        c.cuenta_cpi AS cuenta_cpi_proveedor, bm.metodo_pago
      FROM compras AS f 
        INNER JOIN compras_abonos AS fa ON fa.id_compra = f.id_compra
        INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = fa.id_cuenta 
        INNER JOIN proveedores AS c ON c.id_proveedor = f.id_proveedor 
        INNER JOIN banco_movimientos_compras AS bmc ON bmc.id_compra_abono = fa.id_compra
        INNER JOIN banco_movimientos AS bm ON bm.id_movimiento = bmc.id_movimiento 
      WHERE f.status <> 'ca' AND fa.poliza_egreso = 'f' 
         {$sql}
      ORDER BY fa.id_abono ASC
      ");

    if($query->num_rows() > 0)
    {
      $data = $query->result();
      $response['abonos'] = $data;

      $this->load->model('facturacion_model');

      $impuestos = array(
        'iva_acreditar'  => array('cuenta_cpi' => $this->getCuentaIvaXAcreditar(), 'importe' => 0, 'tipo' => '1'),
        'iva_acreditado' => array('cuenta_cpi' => $this->getCuentaIvaAcreditado(), 'importe' => 0, 'tipo' => '0'),
        'iva_retener'    => array('cuenta_cpi' => $this->getCuentaIvaRetXPagar(), 'importe' => 0, 'tipo' => '0'),
        'iva_retenido'   => array('cuenta_cpi' => $this->getCuentaIvaRetPagado(), 'importe' => 0, 'tipo' => '1'), );

      //Agregamos el header de la poliza
      $response['data'] = $this->setEspacios('P',2).
                          $this->setEspacios(date("Ymd"),8).$this->setEspacios('2',4,'r').  //tipo poliza = 2 poliza egreso
                          $this->setEspacios($this->input->get('ffolio'),9,'r').  //folio poliza
                          $this->setEspacios('1',1). //clase
                          $this->setEspacios('0',10). //iddiario
                          $this->setEspacios($this->input->get('fconcepto'),100). //concepto
                          $this->setEspacios('11',2). //sistema de origen
                          $this->setEspacios('0',1). //impresa
                          $this->setEspacios('0',1)."\n"; //ajuste
      //Contenido de la Poliza
      foreach ($data as $key => $value) 
      {
        $factor = $value->total_abono*100/($value->total); //abono*100/total_factura
        $impuestos['iva_retener']['importe']    = $factor*$value->retencion_iva/100;
        $impuestos['iva_retenido']['importe']   = $impuestos['iva_retener']['importe'];

        $impuestos['iva_acreditar']['importe']  = $factor*($value->importe_iva)/100;
        $impuestos['iva_acreditado']['importe'] = $impuestos['iva_acreditar']['importe'];
        $subtotal = $value->total_abono;//-$impuestos['iva_retener']['importe']-$impuestos['iva_acreditar']['importe'];

        //Colocamos el Cargo al Banco que se deposito el dinero
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($value->cuenta_cpi,30).  //cuenta contpaq
                          $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                          $this->setEspacios('0',1).  //tipo movimiento, banco es un cargo = 0
                          $this->setEspacios( $this->numero($subtotal) , 20).  //importe movimiento
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios($value->nombre_fiscal,100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio
        //Colocamos el Abono al Proveedor que realizo el pago
        $response['data'] .= $this->setEspacios('M',2). //movimiento = M
                          $this->setEspacios($value->cuenta_cpi_proveedor,30).  //cuenta contpaq
                          $this->setEspacios($value->ref_movimiento,10).  //referencia movimiento
                          $this->setEspacios('1',1).  //tipo movimiento, Proveedor es un abono = 1
                          $this->setEspacios( $this->numero($subtotal), 20).  //importe movimiento
                          $this->setEspacios('0',10).  //iddiario poner 0
                          $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                          $this->setEspacios($value->concepto,100). //concepto
                          $this->setEspacios('',4)."\n"; //segmento de negocio
        
        //Colocamos los impuestos de la factura
        foreach ($impuestos as $key => $impuesto) 
        {
          if ($impuestos[$key]['importe'] > 0) 
          {
            $response['data'] .= $this->setEspacios('M',2).
                          $this->setEspacios($impuesto['cuenta_cpi'],30).
                          $this->setEspacios($value->ref_movimiento,10).
                          $this->setEspacios($impuesto['tipo'],1).  //clientes es un abono = 1
                          $this->setEspacios( $this->numero($impuesto['importe']) , 20).
                          $this->setEspacios('0',10).
                          $this->setEspacios('0.0',20).
                          $this->setEspacios($value->concepto,100).
                          $this->setEspacios('',4)."\n";
          }
        }
      }
    }

    return $response;
  }

  

   /**
    * Visualiza/Descarga el PDF para el Reporte Diario de Entradas.
    *
    * @return void
    */
  public function generaPoliza()
  {
    $response = array('data' => '', 'facturas' => array());
    if ($this->input->get('ftipo') == '3') //******Polizas Diario
    {
      if($this->input->get('ftipo2') == 'v') //**Diario de ventas
      {
        $response = $this->polizaDiarioVentas();

        //actualizamos el estado de la factura y descarga el archivo
        if (isset($_POST['poliza']{0})) 
        {
          $idsf = array();
          foreach ($response['facturas'] as $key => $value) 
            $idsf[] = $value->id_factura;
          if(count($idsf) > 0)
          {
            $this->db->where_in('id_factura', $idsf);
            $this->db->update('facturacion', array('poliza_diario' => 't'));

            $_GET['poliza_nombre'] = 'polizadiario.txt';
            file_put_contents(APPPATH.'media/polizas/polizadiario.txt', $response['data']);
            $this->addPoliza($response['data']); //se registra la poliza en la BD
          }
        }
      }elseif($this->input->get('ftipo2') == 'vnc') //**Diario de notas de credito de Ventas
      {
        $response = $this->polizaDiarioVentasNC();

        //actualizamos el estado de la factura y descarga el archivo
        if (isset($_POST['poliza']{0})) 
        {
          $idsf = array();
          foreach ($response['facturas'] as $key => $value) 
            $idsf[] = $value->id_factura;
          if(count($idsf) > 0)
          {
            $this->db->where_in('id_factura', $idsf);
            $this->db->update('facturacion', array('poliza_diario' => 't'));

            $_GET['poliza_nombre'] = 'polizadiarionc.txt';
            file_put_contents(APPPATH.'media/polizas/polizadiarionc.txt', $response['data']);
            $this->addPoliza($response['data']); //se registra la poliza en la BD
          }
        }
      }elseif($this->input->get('ftipo2') == 'g')
      {
        $response = $this->polizaDiarioGastos();

        //actualizamos el estado de la factura y bascula y descarga el archivo
        if (isset($_POST['poliza']{0})) 
        {
          $idsf = array();
          foreach ($response['facturas'] as $key => $value) 
            $idsf[] = $value->id_compra;
          $idsb = array();
          foreach ($response['bascula'] as $key => $value) 
            $idsb[] = $value->id_bascula;
          if(count($idsf) > 0 || count($idsb) > 0)
          {
            if(count($idsf) > 0)
            {
              $this->db->where_in('id_compra', $idsf);
              $this->db->update('compras', array('poliza_diario' => 't'));
            }
            if(count($idsb) > 0)
            {
              $this->db->where_in('id_bascula', $idsb);
              $this->db->update('bascula', array('poliza_diario' => 't'));
            }


            $_GET['poliza_nombre'] = 'polizadiarionc.txt';
            file_put_contents(APPPATH.'media/polizas/polizadiarionc.txt', $response['data']);
            $this->addPoliza($response['data']); //se registra la poliza en la BD
          }
        }
      }else
      {
        $response = $this->polizaDiarioNomina();

        //actualizamos el estado de la factura y bascula y descarga el archivo
        if (isset($_POST['poliza']{0})) 
        {
          $idsf = array();
          foreach ($response['facturas'] as $key => $value) 
            $idsf[] = $value->id_compra;
          $idsb = array();
          foreach ($response['bascula'] as $key => $value) 
            $idsb[] = $value->id_bascula;
          if(count($idsf) > 0 || count($idsb) > 0)
          {
            if(count($idsf) > 0)
            {
              $this->db->where_in('id_compra', $idsf);
              $this->db->update('compras', array('poliza_diario' => 't'));
            }
            if(count($idsb) > 0)
            {
              $this->db->where_in('id_bascula', $idsb);
              $this->db->update('bascula', array('poliza_diario' => 't'));
            }


            $_GET['poliza_nombre'] = 'polizadiarionc.txt';
            file_put_contents(APPPATH.'media/polizas/polizadiarionc.txt', $response['data']);
            $this->addPoliza($response['data']); //se registra la poliza en la BD
          }
        }
      }

      
    }elseif ($this->input->get('ftipo') == '1')  //*******Polizas Ingresos
    {
      $response = $this->polizaIngreso();
      //actualizamos el estado de los abonos de las facturas
      if (isset($_POST['poliza']{0})) 
      {
        $idsa = array();
        foreach ($response['abonos'] as $key => $value) 
          $idsa[] = $value->id_abono;
        if(count($idsa) > 0)
        {
          $this->db->where_in('id_abono', $idsa);
          $this->db->update('facturacion_abonos', array('poliza_ingreso' => 't'));

          $_GET['poliza_nombre'] = 'polizaingreso.txt';
          file_put_contents(APPPATH.'media/polizas/polizaingreso.txt', $response['data']);
          $this->addPoliza($response['data']); //se registra la poliza en la BD
        }
      }
    
    }elseif ($this->input->get('ftipo') == '2')  //*******Polizas Egreso
    {
      if($this->input->get('ftipo3') == 'el')  //Egreso de limon
      {
        $response = $this->polizaEgresoLimon();
        //actualizamos el estado de los abonos de las boletas
        if (isset($_POST['poliza']{0})) 
        {
          $idsa = array();
          foreach ($response['abonos'] as $key => $value) 
            $idsa[] = $value->id_pago;
          if(count($idsa) > 0)
          {
            $this->db->where_in('id_pago', $idsa);
            $this->db->update('bascula_pagos', array('poliza_egreso' => 't'));

            $_GET['poliza_nombre'] = 'polizaegreso.txt';
            file_put_contents(APPPATH.'media/polizas/polizaegreso.txt', $response['data']);
            $this->addPoliza($response['data']); //se registra la poliza en la BD
          }
        }
      }elseif($this->input->get('ftipo3') == 'ec') //Egreso de cheque
      {
        $response = $this->polizaEgreso();
        //actualizamos el estado de los abonos de las compras
        if (isset($_POST['poliza']{0})) 
        {
          $idsa = array();
          foreach ($response['abonos'] as $key => $value) 
            $idsa[] = $value->id_abono;
          if(count($idsa) > 0)
          {
            $this->db->where_in('id_abono', $idsa);
            $this->db->update('compras_abonos', array('poliza_egreso' => 't'));

            $_GET['poliza_nombre'] = 'polizaegreso.txt';
            file_put_contents(APPPATH.'media/polizas/polizaegreso.txt', $response['data']);
            $this->addPoliza($response['data']); //se registra la poliza en la BD
          }
        }
      }else //egreso de gasto
      {
        $response = $this->polizaEgreso('otros');
        //actualizamos el estado de los abonos de las facturas
        if (isset($_POST['poliza']{0})) 
        {
          $idsa = array();
          foreach ($response['abonos'] as $key => $value) 
            $idsa[] = $value->id_abono;
          if(count($idsa) > 0)
          {
            $this->db->where_in('id_abono', $idsa);
            $this->db->update('compras_abonos', array('poliza_egreso' => 't'));

            $_GET['poliza_nombre'] = 'polizaegreso.txt';
            file_put_contents(APPPATH.'media/polizas/polizaegreso.txt', $response['data']);
            $this->addPoliza($response['data']); //se registra la poliza en la BD
          }
        }
      }

    }

    return $response;
  }

  public function addPoliza($txtpoliza){
    if($this->input->get('ftipo') == '3') //diarios
      $tipo2 = $this->input->get('ftipo2');
    elseif($this->input->get('ftipo') == '2') //egresos
      $tipo2 = $this->input->get('ftipo3');
    else
      $tipo2 = 'i';
    $data = array(
      'tipo'     => $this->input->get('ftipo'),
      'tipo2'    => $tipo2,
      'folio'    => $this->input->get('ffolio'),
      'concepto' => $this->input->get('fconcepto'),
      'poliza'   => $txtpoliza,
      );
    $this->db->insert('polizas', $data);
  }


}

/* End of file bascula_model.php */
/* Location: ./application/models/bascula_model.php */