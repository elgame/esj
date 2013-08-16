<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class polizas_model extends CI_Model {

  function __construct()
  {
    parent::__construct();
  }

  public function getCuentaIvaTrasladar($basic=true){
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE nivel = 4 AND nombre like 'IVA TRASLADADO'")->row();
    return $basic? $data->cuenta: $data;
  }

  /**
   * GENERA UNA POLIZA DE DIARIOS
   * @return [type] [description]
   */
  public function polizaDiario()
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
        WHERE f.status = 'p' AND poliza_diario = false
           {$sql}
        ORDER BY id_factura ASC
        ");

      if($query->num_rows() > 0)
      {
        $data = $query->result();
        $response['facturas'] = $data;

        $this->load->model('facturacion_model');

        $impuestos = array('iva_trasladar' => array('cuenta_cpi' => $this->getCuentaIvaTrasladar(), 'importe' => 0) );

        //Agregamos el header de la poliza
        $response['data'] = $this->setEspacios('P',2).
                            $this->setEspacios(date("Ymd"),8).$this->setEspacios('3',4,'r').  //tipo poliza = 3 poliza diarios
                            $this->setEspacios('32',9,'r').  //folio poliza
                            $this->setEspacios('1',1). //clase
                            $this->setEspacios('0',10). //iddiario
                            $this->setEspacios('Calando Polizas Diario',100). //concepto
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
                            $this->setEspacios( $this->numero($inf_factura['info']->total) , 20).  //importe movimiento
                            $this->setEspacios('0',10).  //iddiario poner 0
                            $this->setEspacios('0.0',20).  //importe de moneda extranjera = 0.0
                            $this->setEspacios('FAC No. '.$inf_factura['info']->serie.$inf_factura['info']->folio,100). //concepto
                            $this->setEspacios('',4)."\n"; //segmento de negocio
          
          $impuestos['iva_trasladar']['importe'] = 0;
          //Colocamos los Ingresos de la factura
          foreach ($inf_factura['productos'] as $key => $value) 
          {
            $impuestos['iva_trasladar']['importe'] += $value->iva;
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
          foreach ($impuestos as $key => $value) 
          {
            if ($impuestos[$key]['importe'] > 0) 
            {
              $response['data'] .= $this->setEspacios('M',2).
                            $this->setEspacios($value['cuenta_cpi'],30).
                            $this->setEspacios($inf_factura['info']->serie.$inf_factura['info']->folio,10).
                            $this->setEspacios('1',1).  //clientes es un abono = 1
                            $this->setEspacios( $this->numero($value['importe']) , 20).
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
    * Visualiza/Descarga el PDF para el Reporte Diario de Entradas.
    *
    * @return void
    */
  public function generaPoliza()
  {
    $response = array('data' => '', 'facturas' => array());
    if ($this->input->get('ftipo') == '3') //Polizas Diario
    {
      $response = $this->polizaDiario();
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
        }
      }
    }elseif ($this->input->get('ftipo') == '1')  //Polizas Ingresos
    {
      # code...
    }

    return $response;
  }


}

/* End of file bascula_model.php */
/* Location: ./application/models/bascula_model.php */