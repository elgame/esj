<?php
class cuentas_cobrar_pago_model extends cuentas_cobrar_model{

  function __construct(){
    parent::__construct();
    // $this->load->model('bitacora_model');
  }


  // public function getAbonosData($movimientoId=null)
  // {
  //   //paginacion
  //   $params = array(
  //     'result_items_per_page' => '60',
  //     'result_page'       => (isset($_GET['pag'])? $_GET['pag']: 0)
  //     );
  //   if($params['result_page'] % $params['result_items_per_page'] == 0)
  //     $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

  //   $response = array();
  //   $sql = $sql2 = '';

  //   if($movimientoId!=null)
  //     $sql .= " AND bmf.id_movimiento = {$movimientoId}";
  //   else{
  //     if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
  //       $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m").'-01';
  //       $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
  //     }
  //     if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
  //       $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
  //       $sql .= " AND Date(fa.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
  //     }

  //     if ($this->input->get('did_empresa') != '')
  //       $sql .= " AND f.id_empresa = '".$_GET['did_empresa']."'";
  //   }


  //   $query = BDUtil::pagination(
  //     "SELECT
  //     bmf.id_movimiento, fa.ref_movimiento, fa.concepto, Sum(fa.total) AS total_abono,
  //     bc.cuenta_cpi, Sum(f.subtotal) AS subtotal, Sum(f.total) AS total, Sum(((fa.total*100/f.total)*f.importe_iva/100)) AS importe_iva,
  //     Sum(((fa.total*100/f.total)*f.retencion_iva/100)) AS retencion_iva, c.nombre_fiscal,
  //     c.cuenta_cpi AS cuenta_cpi_cliente, Date(fa.fecha) AS fecha, e.nombre_fiscal AS empresa, e.logo
  //     FROM facturacion AS f
  //     INNER JOIN facturacion_abonos AS fa ON fa.id_factura = f.id_factura
  //     INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = fa.id_cuenta
  //     INNER JOIN clientes AS c ON c.id_cliente = f.id_cliente
  //     INNER JOIN empresas AS e ON e.id_empresa = f.id_empresa
  //     INNER JOIN banco_movimientos_facturas AS bmf ON bmf.id_abono_factura = fa.id_abono
  //     WHERE f.status <> 'ca' AND f.status <> 'b'
  //     {$sql}
  //     GROUP BY bmf.id_movimiento, fa.ref_movimiento, fa.concepto,
  //     bc.cuenta_cpi, c.nombre_fiscal, c.cuenta_cpi, Date(fa.fecha),
  //     e.nombre_fiscal, e.logo
  //     ORDER BY Date(fa.fecha) DESC
  //     ", $params, true);
  //   $res = $this->db->query($query['query']);

  //   $response = array(
  //     'abonos'         => array(),
  //     'facturas'       => array(),
  //     'total_rows'     => $query['total_rows'],
  //     'items_per_page' => $params['result_items_per_page'],
  //     'result_page'    => $params['result_page'],
  //     );

  //   if($res->num_rows() > 0)
  //   {
  //     $response['abonos'] = $res->result();
  //     $res->free_result();


  //     if($movimientoId!=null)
  //     {
  //       $res = $this->db->query(
  //         "SELECT
  //         fa.id_abono, f.serie, f.folio, fa.ref_movimiento, fa.concepto, fa.total, Date(fa.fecha) AS fecha
  //         FROM facturacion AS f
  //         INNER JOIN facturacion_abonos AS fa ON fa.id_factura = f.id_factura
  //         INNER JOIN banco_movimientos_facturas AS bmf ON bmf.id_abono_factura = fa.id_abono
  //         WHERE f.status <> 'ca' AND f.status <> 'b'
  //         {$sql}
  //         ORDER BY fa.id_abono ASC
  //         ");
  //       $response['facturas'] = $res->result();
  //       $res->free_result();
  //     }
  //   }

  //   return $response;
  // }

  public function addComPago($id_movimiento)
  {
    $query = $this->db->query(
          "SELECT *, (select Count(id_movimiento) from banco_movimientos_com_pagos where id_movimiento = {$id_movimiento}) AS num_row
           FROM banco_movimientos_com_pagos
           WHERE id_movimiento = {$id_movimiento} AND status = 'facturada'"
        );

    if ($query->num_rows() == 0) {
      $this->load->library('cfdi');

      $queryMov = $this->db->query(
          "SELECT bm.id_movimiento, bm.fecha, bm.metodo_pago AS forma_pago, bm.concepto,
            bm.monto AS pago, bb.rfc, bc.numero AS num_cuenta, caf.total AS pago_factura, v.version, v.serie, v.folio,
            v.id_factura, v.uuid, v.cfdi_ext, Coalesce(par.parcialidades, 1) AS parcialidades, v.id_cliente
           FROM banco_movimientos bm
            INNER JOIN banco_cuentas bc ON bc.id_cuenta = bm.id_cuenta
            INNER JOIN banco_bancos bb ON bb.id_banco = bm.id_banco
            INNER JOIN banco_movimientos_facturas bmf ON bm.id_movimiento = bmf.id_movimiento
            INNER JOIN facturacion_abonos caf ON caf.id_abono = bmf.id_abono_factura
            INNER JOIN facturacion v ON v.id_factura = caf.id_factura
            LEFT JOIN (
              SELECT id_factura, Count(*) AS parcialidades FROM facturacion_abonos GROUP BY id_factura
            ) par ON v.id_factura = par.id_factura
           WHERE bm.id_movimiento = {$id_movimiento} AND v.version::float > 3.2"
        );

      if ($queryMov->num_rows() > 0) {
        // xml 3.3
        $datosApi = $this->cfdi->obtenDatosCfdi33ComP($_POST, $productosApi, $cid_nc);

        // Timbrado de la factura.
        $result = $this->timbrar($datosApi, $idFactura);

        if ($result['passes'])
        {

          // $xmlName = explode('/', $archivos['pathXML']);

          // copy($archivos['pathXML'], $pathDocs.end($xmlName));

          //Si es otra moneda actualiza al tipo de cambio
          if($datosFactura['moneda'] !== 'MXN')
          {
            $datosFactura1 = array();
            $datosFactura1['total']         = number_format($datosFactura['total']*$datosFactura['tipo_cambio'], 2, '.', '');
            $datosFactura1['subtotal']      = number_format($datosFactura['subtotal']*$datosFactura['tipo_cambio'], 2, '.', '');
            $datosFactura1['importe_iva']   = number_format($datosFactura['importe_iva']*$datosFactura['tipo_cambio'], 2, '.', '');
            $datosFactura1['retencion_iva'] = number_format($datosFactura['retencion_iva']*$datosFactura['tipo_cambio'], 2, '.', '');
            $this->db->update('facturacion', $datosFactura1, array('id_factura' => $idFactura));

            foreach ($productosFactura as $key => $value)
            {
              $value['precio_unitario'] = number_format($value['precio_unitario']*$datosFactura['tipo_cambio'], 2, '.', '');
              $value['importe']         = number_format($value['importe']*$datosFactura['tipo_cambio'], 2, '.', '');
              $value['iva']             = number_format($value['iva']*$datosFactura['tipo_cambio'], 2, '.', '');
              $value['retencion_iva']   = number_format($value['retencion_iva']*$datosFactura['tipo_cambio'], 2, '.', '');
              $this->db->update('facturacion_productos', $value, "id_factura = {$value['id_factura']} AND num_row = {$value['num_row']}");
            }
          }

          $this->db->query("SELECT refreshallmaterializedviews();");

          $this->generaFacturaPdf($idFactura, $pathDocs);

          // si probiene de una venta se asigna
          if (isset($_GET['id_vd'])) {
            $this->load->model('ventas_dia_model');
            $this->ventas_dia_model->idFacturaVenta(array('id_factura' => $idFactura, 'id_venta' => $_GET['id_vd']));
          }

        }
        else
        {
          rmdir($pathDocs);
        }

        // $datosFactura, $cadenaOriginal, $sello, $productosFactura,
        // echo "<pre>";
        //   var_dump($datosXML);
        // echo "</pre>";exit;

        return $result;
      }
      return array("passes" => false, "codigo" => "13");
    }
    return array("passes" => false, "codigo" => "12");
  }

  private function timbrar($dataXml, $idFactura, $delFiles = true)
  {
    $this->load->library('facturartebarato_api');

    // $this->facturartebarato_api->setPathXML($pathXML);

    // Realiza el timbrado usando la libreria.
    $timbrado = $this->facturartebarato_api->timbrar($dataXml);

    // echo "<pre>";
    //   var_dump($timbrado);
    // echo "</pre>";exit;

    $result = array(
      'id_factura' => $idFactura,
      'codigo'     => $timbrado->codigo
    );

    // Si no hubo errores al momento de realizar el timbrado.
    if ($timbrado->status)
    {
      // Si el codigo es 501:Autenticación no válida o 708:No se pudo conectar al SAT,
      // significa que el timbrado esta pendiente.
      if ($timbrado->codigo === '501' || $timbrado->codigo === '708')
      {
        // Se coloca el status de timbre de la factura como pendiente.
        $statusTimbrado = 'p';
      }
      else
      {
        // Si el timbrado se realizo correctamente.

        // Se coloca el status de timbre de la factura como timbrado.
        $statusTimbrado = 't';
      }

      // Actualiza los datos en la BDD.
      $dataTimbrado = array(
        'xml'             => $timbrado->data->xml,
        'status_timbrado' => $statusTimbrado,
        'uuid'            => $timbrado->data->uuid,
        'cadena_original' => $timbrado->data->cadenaOriginal,
        'sello'           => $timbrado->data->sello,
        'certificado'     => $dataXml['emisor']['cer'],
        'cfdi_ext'        => json_encode($dataXml),
      );
      $this->db->update('facturacion', $dataTimbrado, array('id_factura' => $idFactura));

      $result['passes'] = true;
    }
    else
    {
      // Si es true $delFile entonces elimina todo lo relacionado con la factura.
      if ($delFiles)
      {
        $this->db->delete('facturacion_cliente', array('id_factura' => $idFactura));
        $this->db->delete('facturacion', array('id_factura' => $idFactura));
        // unlink($pathXML);
      }

      // Entra si hubo un algun tipo de error de conexion a internet.
      if ($timbrado->codigo === 'ERR_INTERNET_DISCONNECTED')
        $result['msg'] = 'Error Timbrado: Internet Desconectado. Verifique su conexión para realizar el timbrado.';
      elseif ($timbrado->codigo === '500')
        $result['msg'] = 'Error en el servidor del timbrado. Pongase en contacto con el equipo de desarrollo del sistema.';
      else
        $result['msg'] = $timbrado->mensaje;

      $result['passes'] = false;
      }

      // echo "<pre>";
      //   var_dump($timbrado);
      // echo "</pre>";exit;

      return $result;
  }

  public function getFolioSerie($serie, $empresa, $sqlX = null)
  {
    $res = $this->db->select('folio')
      ->from('facturacion')
      ->where("serie = '".$serie."' AND id_empresa = ".$empresa."") // AND status != 'b'
      ->where('is_factura', 't')
      ->order_by('folio', 'DESC')
      ->limit(1)->get()->row();

    $folio = (isset($res->folio)? $res->folio: 0)+1;

    if ( ! is_null($sqlX))
      $this->db->where($sqlX);

    $res = $this->db->select('*')
      ->from('facturacion_series_folios')
      ->where("serie = '".$serie."' AND id_empresa = ".$empresa)
      ->limit(1)->get()->row();

    if(is_object($res)){
      if($folio < $res->folio_inicio)
        $folio = $res->folio_inicio;

      $res->folio = $folio;
      $msg = 'ok';

      if($folio > $res->folio_fin || $folio < $res->folio_inicio)
        $msg = "El folio ".$folio." está fuera del rango de folios para la serie ".$serie.". <br>
          Verifique las configuraciones para asignar un nuevo rango de folios";
    }else
      $msg = 'La serie no existe.';

    return array($res, $msg);
  }

}