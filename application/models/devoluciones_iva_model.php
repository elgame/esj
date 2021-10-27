<?php
class devoluciones_iva_model extends privilegios_model{

  function __construct(){
    parent::__construct();
  }

  /*-------------------------------------------
   * --------- Reportes de compras ------------
   -------------------------------------------*/

  /**
   * Reporte de cedula proveedores
   *
   * @return
   */
  public function getCedulaProveedoresData()
  {
    $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND c.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if(is_array($this->input->get('ids_proveedores')))
    {
      $sql .= " AND p.id_proveedor IN(".implode(',', $this->input->get('ids_proveedores')).")";
    }

    $sql .= " AND Date(c.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'";

    $facturas = $this->db->query(
    "SELECT c.id_compra, Date(c.fecha) AS fecha, c.serie, c.folio, c.subtotal, c.importe_iva, c.total,
      p.id_proveedor, p.nombre_fiscal AS proveedor, p.rfc AS rfc_proveedor, c.uuid, c.no_certificado,
      Sum(ca.total) AS total_pago, string_agg(DISTINCT Date(ca.fecha)::text, ', ') AS fecha_pagos,
      string_agg(DISTINCT bm.metodo_pago, ', ') AS metodo_pago, string_agg(DISTINCT ca.concepto, ', ') AS concepto
    FROM compras c
      INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
      INNER JOIN compras_abonos ca ON c.id_compra = ca.id_compra
      INNER JOIN banco_movimientos_compras bmc ON bmc.id_compra_abono = ca.id_abono
      INNER JOIN banco_movimientos bm ON bm.id_movimiento = bmc.id_movimiento
    WHERE c.status <> 'ca' AND c.importe_iva > 0
      {$sql}
    GROUP BY c.id_compra, p.id_proveedor
    ORDER BY p.nombre_fiscal
    ");
    $response = $facturas->result();

    return $response;
  }
  public function getCedulaProveedoresXls($show = false)
  {
    if (!$show) {
      header('Content-type: application/vnd.ms-excel; charset=utf-8');
      header("Content-Disposition: attachment; filename=CedulaProveedores.xls");
      header("Pragma: no-cache");
      header("Expires: 0");
    }

    $res = $this->getCedulaProveedoresData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte Cédula a Detalle de Proveedores';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";


    $html = '<table>
      <tbody>
        <tr>
          <td colspan="14" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="14" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="14" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="14"></td>
        </tr>';

    $proveedor = '';
    $total_subtotal = $total_iva = $total_total = $total_pagos = 0;
    $subtotal = $iva = $total = $pagos = $ret_iva = 0;
    foreach($res as $key => $item){
      if ($proveedor != $item->id_proveedor) {
        if ($key > 0) {
          $html .= '
          <tr style="font-weight:bold">
            <td colspan="7"></td>
            <td style="border:1px solid #000;">'.$subtotal.'</td>
            <td style="border:1px solid #000;">'.$iva.'</td>
            <td style="border:1px solid #000;">'.$total.'</td>
            <td style="border:1px solid #000;">'.$pagos.'</td>
            <td colspan="3"></td>
          </tr>
          <tr>
            <td colspan="14"></td>
          </tr>';
        }

        $html .= '<tr style="font-weight:bold">
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Fecha de Pago</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Fecha Factura</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Serie CFDI</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio CFDI</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">RFC Proveedor</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Nombre del Proveedor</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Concepto a detalle</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Subtotal</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">IVA</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Total</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Pago Total</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Forma de Pago</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Certificado digital</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio fiscal autorizado</td>
        </tr>';

        $subtotal = $iva = $total = $pagos = 0;
        $proveedor = $item->id_proveedor;
      }

      $html .= '<tr>
          <td style="width:150px;border:1px solid #000;">'.$item->fecha_pagos.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->fecha.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->serie.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->folio.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->rfc_proveedor.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->proveedor.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->concepto.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->subtotal.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->importe_iva.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->total.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->total_pago.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->metodo_pago.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->no_certificado.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->uuid.'</td>
        </tr>';

      $subtotal += $item->subtotal;
      $iva      += $item->importe_iva;
      $total    += $item->total;
      $pagos    += $item->total_pago;

      $total_subtotal += $item->subtotal;
      $total_iva      += $item->importe_iva;
      $total_total    += $item->total;
      $total_pagos    += $item->total_pago;



    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="7"></td>
          <td style="border:1px solid #000;">'.$subtotal.'</td>
          <td style="border:1px solid #000;">'.$iva.'</td>
          <td style="border:1px solid #000;">'.$total.'</td>
          <td style="border:1px solid #000;">'.$pagos.'</td>
          <td colspan="3"></td>
        </tr>
        <tr>
          <td colspan="14"></td>
        </tr>

        <tr style="font-weight:bold">
          <td colspan="7"></td>
          <td style="border:1px solid #000;">'.$total_subtotal.'</td>
          <td style="border:1px solid #000;">'.$total_iva.'</td>
          <td style="border:1px solid #000;">'.$total_total.'</td>
          <td style="border:1px solid #000;">'.$total_pagos.'</td>
          <td colspan="3"></td>
        </tr>
        <tr>
          <td colspan="14"></td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

	/**
   * Reporte de totalida de iva
   *
   * @return
   */
  public function getCedulaTotalidadIvaData()
  {
    $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND c.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if(is_array($this->input->get('ids_proveedores')))
    {
      $sql .= " AND p.id_proveedor IN(".implode(',', $this->input->get('ids_proveedores')).")";
    }

    $sql .= " AND Date(c.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'";

    $facturas = $this->db->query(
    "SELECT c.id_compra, Date(c.fecha) AS fecha, c.serie, c.folio, Coalesce(Sum(cp.subtotal), c.subtotal) AS subtotal,
      Coalesce(Sum(cp.importe_iva), c.importe_iva) AS importe_iva, Coalesce(Sum(cp.total), c.total) AS total,
      c.retencion_iva, bm.monto AS total_pago, bm.id_movimiento, p.id_proveedor, p.nombre_fiscal AS proveedor,
      p.rfc AS rfc_proveedor, c.uuid, c.no_certificado, Date(bm.fecha) AS fecha_pagos,
      bm.metodo_pago AS metodo_pago, Coalesce(string_agg(cp.conceptos, ', '), c.concepto) AS conceptos,
      bc.alias AS cuenta_pago, Coalesce(Sum(cp.tipo_cambio), 0) AS tipo_cambio
    FROM compras c
      INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
      INNER JOIN compras_abonos ca ON c.id_compra = ca.id_compra
      INNER JOIN banco_movimientos_compras bmc ON bmc.id_compra_abono = ca.id_abono
      INNER JOIN banco_movimientos bm ON bm.id_movimiento = bmc.id_movimiento
      INNER JOIN banco_cuentas bc ON bc.id_cuenta = bm.id_cuenta
      LEFT JOIN (
        SELECT id_compra, Sum(importe) AS subtotal, Sum(iva) AS importe_iva, Sum(retencion_iva) AS retencion_iva,
          Coalesce(Sum(tipo_cambio)/(CASE WHEN Count(tipo_cambio) = 0 THEN 1 ELSE Count(tipo_cambio) END), 0) AS tipo_cambio,
          Sum(importe + iva) AS total, string_agg(DISTINCT descripcion, ', ') AS conceptos
        FROM compras_productos
        GROUP BY id_compra
      ) cp ON c.id_compra = cp.id_compra
    WHERE c.status <> 'ca' AND c.importe_iva > 0 AND bm.status = 't'
       {$sql}
    GROUP BY c.id_compra, p.id_proveedor, bm.id_movimiento, bc.id_cuenta
    ORDER BY proveedor ASC, fecha_pagos ASC, id_movimiento ASC
    ");
    $response = $facturas->result();

    return $response;
  }
  public function getCedulaTotalidadIvaXls($show = false)
  {
    if (!$show) {
      header('Content-type: application/vnd.ms-excel; charset=utf-8');
      header("Content-Disposition: attachment; filename=CedulaTotalidadIva.xls");
      header("Pragma: no-cache");
      header("Expires: 0");
    }

    $res = $this->getCedulaTotalidadIvaData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte Cédula Integración Totalidad de IVA';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";


    $html = '<table>
      <tbody>
        <tr>
          <td colspan="17" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="17" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="17" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="17"></td>
        </tr>';

    $proveedor = '';
    $auxmov = '';
    $total_subtotal = $total_iva = $total_total = $total_pagos = $total_ret_iva = 0;
    $subtotal = $iva = $total = $pagos = $ret_iva = 0;
    foreach($res as $key => $item){
      if ($proveedor != $item->id_proveedor) {
        if ($key > 0) {
          $html .= '
          <tr style="font-weight:bold">
            <td colspan="6"></td>
            <td style="border:1px solid #000;">'.$subtotal.'</td>
            <td style="border:1px solid #000;">'.$iva.'</td>
            <td style="border:1px solid #000;">'.$ret_iva.'</td>
            <td style="border:1px solid #000;">'.$total.'</td>
            <td style="border:1px solid #000;">'.$pagos.'</td>
            <td colspan="3"></td>
          </tr>
          <tr>
            <td colspan="17"></td>
          </tr>';
        }

        $html .= '<tr style="font-weight:bold">
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Fecha Factura</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Serie CFDI</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio CFDI</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">RFC Proveedor</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Nombre del Proveedor</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Concepto a detalle</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Subtotal</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">IVA</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Ret IVA</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Total</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Pago Total</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Forma de Pago</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Fecha de Pago</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Cuenta Pago</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Certificado digital</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio fiscal autorizado</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Tipo Cambio</td>
        </tr>';

        $subtotal = $iva = $total = $pagos = $ret_iva = 0;
        $proveedor = $item->id_proveedor;
      }

      $subtotal += $item->subtotal;
      $iva      += $item->importe_iva;
      $ret_iva  += $item->retencion_iva;
      $total    += $item->total;

      $total_subtotal += $item->subtotal;
      $total_iva      += $item->importe_iva;
      $total_ret_iva  += $item->retencion_iva;
      $total_total    += $item->total;

      if ($auxmov != $item->id_movimiento) {
        $pagos       += $item->total_pago;
        $total_pagos += $item->total_pago;
      }

      $html .= '<tr>
          <td style="width:150px;border:1px solid #000;">'.$item->fecha.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->serie.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->folio.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->rfc_proveedor.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->proveedor.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->conceptos.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->subtotal.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->importe_iva.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->retencion_iva.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->total.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->total_pago.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->metodo_pago.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->fecha_pagos.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->cuenta_pago.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->no_certificado.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->uuid.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->tipo_cambio.'</td>
        </tr>';

    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="6"></td>
          <td style="border:1px solid #000;">'.$subtotal.'</td>
          <td style="border:1px solid #000;">'.$iva.'</td>
          <td style="border:1px solid #000;">'.$ret_iva.'</td>
          <td style="border:1px solid #000;">'.$total.'</td>
          <td style="border:1px solid #000;">'.$pagos.'</td>
          <td colspan="3"></td>
        </tr>
        <tr>
          <td colspan="17"></td>
        </tr>

        <tr style="font-weight:bold">
          <td colspan="6"></td>
          <td style="border:1px solid #000;">'.$total_subtotal.'</td>
          <td style="border:1px solid #000;">'.$total_iva.'</td>
          <td style="border:1px solid #000;">'.$total_ret_iva.'</td>
          <td style="border:1px solid #000;">'.$total_total.'</td>
          <td style="border:1px solid #000;">'.$total_pagos.'</td>
          <td colspan="3"></td>
        </tr>
        <tr>
          <td colspan="17"></td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

  /**
   * Reporte de integracion de iva 16
   *
   * @return
   */
  public function getCedulaIva16Data()
  {
    $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if(is_array($this->input->get('ids_clientes')))
    {
      $sql .= " AND c.id_cliente IN(".implode(',', $this->input->get('ids_clientes')).")";
    }

    if ($this->input->get('tasa_iva') == '')
      $sql_iva = "";
    elseif($this->input->get('tasa_iva') == 16)
      $sql_iva = " ffp.iva > 0 AND";
    elseif($this->input->get('tasa_iva') == 0)
      $sql_iva = " ffp.iva = 0 AND";

    if($this->input->get('exportacion') == 'si')
      $sql .= " AND c.rfc = 'XEXX010101000'";
    else
      $sql .= " AND c.rfc <> 'XEXX010101000'";

    $sql .= " AND Date(bm.fecha) BETWEEN '{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'";

    $facturas = $this->db->query(
    "SELECT f.id_factura, f.serie, f.folio, Date(f.fecha) AS fecha, c.rfc, c.nombre_fiscal AS cliente,
      c.id_cliente, fp.conceptos AS conceptos, fp.subtotal AS subtotal,
      fp.importe_iva AS importe_iva, fp.total AS total,
      f.no_certificado, f.uuid, Date(bm.fecha) AS fecha_pago,
      bm.monto AS total_pago, bm.id_movimiento, bc.alias AS cuentas,
      bm.metodo_pago AS metodo_pago, f.tipo_cambio
    FROM facturacion f
      INNER JOIN clientes c ON c.id_cliente = f.id_cliente
      INNER JOIN (
        SELECT ffp.id_factura, Sum(ffp.importe) AS subtotal, Sum(ffp.iva) AS importe_iva,
          Sum(ffp.importe + ffp.iva) AS total, string_agg(DISTINCT ffp.descripcion, ', ') AS conceptos
        FROM facturacion_productos ffp
          INNER JOIN facturacion ff ON ff.id_factura = ffp.id_factura
            WHERE {$sql_iva} (
          ff.sin_costo = 'f' OR
          (ff.sin_costo = 't' AND ffp.id_clasificacion <> '49' AND ffp.id_clasificacion <> '50' AND
           ffp.id_clasificacion <> '51' AND ffp.id_clasificacion <> '52' AND ffp.id_clasificacion <> '53')
        )
        GROUP BY ffp.id_factura
      ) fp ON f.id_factura = fp.id_factura
      INNER JOIN facturacion_abonos fa ON f.id_factura = fa.id_factura
      INNER JOIN banco_movimientos_facturas bmf ON bmf.id_abono_factura = fa.id_abono
      INNER JOIN banco_movimientos bm ON bm.id_movimiento = bmf.id_movimiento
      INNER JOIN banco_cuentas bc ON bc.id_cuenta = bm.id_cuenta
    WHERE f.status <> 'ca' AND f.status <> 'b' AND f.is_factura = 't' AND bm.status = 't'
      {$sql}
    ORDER BY cliente ASC, fecha_pago ASC, id_movimiento ASC
    ");
    $response = $facturas->result();

    return $response;
  }
  public function getCedulaIva16Xls($show = false)
  {
    if (!$show) {
      header('Content-type: application/vnd.ms-excel; charset=utf-8');
      header("Content-Disposition: attachment; filename=CedulaIva16.xls");
      header("Pragma: no-cache");
      header("Expires: 0");
    }

    $res = $this->getCedulaIva16Data();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte Cédula Integración IVA 16%';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";


    $html = '<table>
      <tbody>
        <tr>
          <td colspan="17" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="17" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="17" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="17"></td>
        </tr>';

    $cliente = '';
    $auxmov = '';
    $total_subtotal = $total_iva = $total_total = $total_pagos = $total_ret_iva = 0;
    $subtotal = $iva = $total = $pagos = $ret_iva = 0;
    foreach($res as $key => $item){
      if ($cliente != $item->id_cliente) {
        if ($key > 0) {
          $html .= '
          <tr style="font-weight:bold">
            <td colspan="6"></td>
            <td style="border:1px solid #000;">'.$subtotal.'</td>
            <td style="border:1px solid #000;">'.$iva.'</td>
            <td style="border:1px solid #000;">'.$total.'</td>
            <td style="border:1px solid #000;">'.$pagos.'</td>
            <td colspan="3"></td>
          </tr>
          <tr>
            <td colspan="16"></td>
          </tr>';
        }

        $html .= '<tr style="font-weight:bold">
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Fecha Factura</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Serie CFDI</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio CFDI</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">RFC Cliente</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Nombre del Cliente</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Concepto a detalle</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Subtotal</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">IVA</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Total</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Pago Total</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Forma de Pago</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Fecha de Pago</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Cuenta Pago</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Certificado digital</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Folio fiscal autorizado</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Tipo Cambio</td>
        </tr>';

        $subtotal = $iva = $total = $pagos = $ret_iva = 0;
        $cliente = $item->id_cliente;
      }

      $subtotal += $item->subtotal;
      $iva      += $item->importe_iva;
      $total    += $item->total;

      $total_subtotal += $item->subtotal;
      $total_iva      += $item->importe_iva;
      $total_total    += $item->total;

      if ($auxmov != $item->id_movimiento) {
        $pagos       += $item->total_pago;
        $total_pagos += $item->total_pago;
        $auxmov      = $item->id_movimiento;
      } else {
        $item->total_pago = 0;
      }

      $html .= '<tr>
          <td style="width:150px;border:1px solid #000;">'.$item->fecha.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->serie.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->folio.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->rfc.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->cliente.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->conceptos.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->subtotal.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->importe_iva.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->total.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->total_pago.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->metodo_pago.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->fecha_pago.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->cuentas.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->no_certificado.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->uuid.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->tipo_cambio.'</td>
        </tr>';

    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="6"></td>
          <td style="border:1px solid #000;">'.$subtotal.'</td>
          <td style="border:1px solid #000;">'.$iva.'</td>
          <td style="border:1px solid #000;">'.$total.'</td>
          <td style="border:1px solid #000;">'.$pagos.'</td>
          <td colspan="3"></td>
        </tr>
        <tr>
          <td colspan="16"></td>
        </tr>

        <tr style="font-weight:bold">
          <td colspan="6"></td>
          <td style="border:1px solid #000;">'.$total_subtotal.'</td>
          <td style="border:1px solid #000;">'.$total_iva.'</td>
          <td style="border:1px solid #000;">'.$total_total.'</td>
          <td style="border:1px solid #000;">'.$total_pagos.'</td>
          <td colspan="3"></td>
        </tr>
        <tr>
          <td colspan="16"></td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

  /**
   * Reporte de ieps
   *
   * @return
   */
  public function getRptIepsData()
  {
    $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND c.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if(is_array($this->input->get('ids_proveedores')))
    {
      $sql .= " AND p.id_proveedor IN(".implode(',', $this->input->get('ids_proveedores')).")";
    }

    $sql_fecha = "'{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'";

    $facturas = $this->db->query(
      "SELECT d.*, cp.id_producto, cp.descripcion, cp.cantidad, --(cp.cantidad * Coalesce(pp.cantidad, 1)) AS cantidad,
        cp.precio_unitario, cp.importe, cp.iva, cp.ieps, cp.porcentaje_ieps, pu.id_unidad, pu.abreviatura,
        pu.nombre as unidad
      FROM (
          SELECT c.id_compra, c.total, round(Sum(ca.total)::decimal, 2) abonos,
            round((c.total - round(Sum(ca.total)::decimal, 2))::decimal, 2) saldo, cad.fecha,
            p.id_proveedor, p.nombre_fiscal AS proveedor, p.rfc AS rfc_prov
          FROM compras c
            INNER JOIN proveedores p ON p.id_proveedor = c.id_proveedor
            INNER JOIN (
              SELECT id_compra, Sum(total) AS total
              FROM (
                SELECT id_nc AS id_compra, total
                FROM compras
                WHERE status <> 'ca' AND id_nc IS NOT NULL
                UNION
                SELECT id_compra, total
                FROM compras_abonos
              ) ab
              GROUP BY id_compra
            ) ca ON c.id_compra = ca.id_compra
            INNER JOIN (
              SELECT distinct on(id_compra) id_compra, fecha
              FROM (
                SELECT id_nc AS id_compra, total, Date(fecha) AS fecha
                FROM compras
                WHERE status <> 'ca' AND id_nc IS NOT NULL
                UNION
                SELECT id_compra, total, Date(fecha) AS fecha
                FROM compras_abonos
              ) ab
              WHERE fecha BETWEEN {$sql_fecha}
              ORDER BY id_compra, fecha DESC
            ) cad ON cad.id_compra = c.id_compra
          WHERE c.status <> 'ca' AND c.id_nc IS NULL {$sql}
          GROUP BY c.id_compra, cad.fecha, p.id_proveedor
          HAVING round((c.total - round(Sum(ca.total)::decimal, 2))::decimal, 2) = 0
        ) d
        INNER JOIN compras_productos cp ON cp.id_compra = d.id_compra
        INNER JOIN productos AS pr ON pr.id_producto = cp.id_producto
        INNER JOIN productos_unidades AS pu ON pu.id_unidad = pr.id_unidad
        INNER JOIN productos_familias AS pf ON pf.id_familia = pr.id_familia
        LEFT JOIN productos_presentaciones AS pp ON pp.id_presentacion = cp.id_presentacion
      WHERE pf.tipo = 'p' AND cp.ieps > 0 AND d.fecha BETWEEN {$sql_fecha}
      ORDER BY cp.porcentaje_ieps ASC
      ");
    $response = $facturas->result();

    return $response;
  }
  public function getRptIepsXls($show = false)
  {
    if (!$show) {
      header('Content-type: application/vnd.ms-excel; charset=utf-8');
      header("Content-Disposition: attachment; filename=rpt_ieps.xls");
      header("Pragma: no-cache");
      header("Expires: 0");
    }

    $res = $this->getRptIepsData();
    // echo "<pre>";
    // var_dump($res);
    // echo "</pre>";exit;

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte de IEPS';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";


    $html = '<table>
      <tbody>
        <tr>
          <td colspan="11" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="11" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="11" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="11"></td>
        </tr>

        ';

    $porcentaje_ieps = '';
    $total_subtotal = $total_iva = $total_total = $total_ieps = 0;
    $subtotal = $iva = $total = $ieps = $ret_iva = 0;
    foreach($res as $key => $item){
      if ($porcentaje_ieps != $item->porcentaje_ieps) {
        if ($key > 0) {
          $html .= '
          <tr style="font-weight:bold">
            <td colspan="7"></td>
            <td style="border:1px solid #000;">'.$subtotal.'</td>
            <td style="border:1px solid #000;">'.$iva.'</td>
            <td style="border:1px solid #000;">'.$ieps.'</td>
            <td style="border:1px solid #000;">'.$total.'</td>
          </tr>
          <tr>
            <td colspan="11"></td>
          </tr>';
        }

        $html .= '<tr style="font-weight:bold">
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Fecha de Pago</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Proveedor</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">RFC</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Categoría</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Producto</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Cantidad</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Unidad</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Subtotal</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">IVA</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">IEPS</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Total</td>
        </tr>';

        $subtotal = $iva = $total = $ieps = 0;
        $porcentaje_ieps = $item->porcentaje_ieps;
      }

      $categoria = '';
      if ($item->porcentaje_ieps == '6') {
        $categoria = '4';
      } elseif ($item->porcentaje_ieps == '7') {
        $categoria = '3';
      } elseif ($item->porcentaje_ieps == '9') {
        $categoria = '1, 2';
      }

      $html .= '<tr>
          <td style="width:150px;border:1px solid #000;">'.$item->fecha.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->proveedor.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->rfc_prov.'</td>
          <td style="width:150px;border:1px solid #000;">'.$categoria.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->descripcion.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->cantidad.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->unidad.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->importe.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->iva.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->ieps.'</td>
          <td style="width:150px;border:1px solid #000;">'.($item->importe+$item->iva+$item->ieps).'</td>
        </tr>';

      $subtotal += $item->importe;
      $iva      += $item->iva;
      $total    += ($item->importe+$item->iva+$item->ieps);
      $ieps     += $item->ieps;

      $total_subtotal += $item->importe;
      $total_iva      += $item->iva;
      $total_total    += ($item->importe+$item->iva+$item->ieps);
      $total_ieps     += $item->ieps;



    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="7"></td>
          <td style="border:1px solid #000;">'.$subtotal.'</td>
          <td style="border:1px solid #000;">'.$iva.'</td>
          <td style="border:1px solid #000;">'.$ieps.'</td>
          <td style="border:1px solid #000;">'.$total.'</td>
        </tr>
        <tr>
          <td colspan="14"></td>
        </tr>

        <tr style="font-weight:bold">
          <td colspan="7"></td>
          <td style="border:1px solid #000;">'.$total_subtotal.'</td>
          <td style="border:1px solid #000;">'.$total_iva.'</td>
          <td style="border:1px solid #000;">'.$total_ieps.'</td>
          <td style="border:1px solid #000;">'.$total_total.'</td>
        </tr>
        <tr>
          <td colspan="14"></td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }


  /**
   * Reporte de ieps ventas
   *
   * @return
   */
  public function getRptIepsVentasData()
  {
    $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND c.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if(is_array($this->input->get('ids_clientes')))
    {
      $sql .= " AND p.id_proveedor IN(".implode(',', $this->input->get('ids_clientes')).")";
    }

    $sql_fecha = "'{$_GET['ffecha1']}' AND '{$_GET['ffecha2']}'";
    $sql_fecha2 = "'{$_GET['ffecha2']}'";

    $facturas = $this->db->query(
      "SELECT d.*, cp.id_clasificacion, cp.descripcion, cp.cantidad, --(cp.cantidad * Coalesce(pp.cantidad, 1)) AS cantidad,
        cp.precio_unitario, cp.importe, cp.iva, cp.ieps, cp.porcentaje_ieps, pu.id_unidad, pu.nombre AS abreviatura,
        pu.nombre as unidad
      FROM (
          SELECT
            f.id_factura,
            f.serie,
            f.folio,
            Date(f.fecha) AS fecha,
            COALESCE(f.total, 0) AS total,
            COALESCE(ac.abono, 0) AS abonos,
            (COALESCE(f.total, 0) - COALESCE(ac.abono, 0))::numeric(15,2) AS saldo,
            acf.fecha AS fecha_pago,
            c.id_cliente, c.nombre_fiscal AS cliente, c.rfc AS rfc_cliente
          FROM
            facturacion AS f
            INNER JOIN clientes c ON c.id_cliente = f.id_cliente
            INNER JOIN (
              SELECT id_factura, Sum(abono) AS abono
              FROM (
                (
                  SELECT
                  id_factura,
                  Sum(total) AS abono
                  FROM
                  facturacion_abonos as fa
                  GROUP BY id_factura
                )
                UNION
                (
                  SELECT
                  id_nc AS id_factura,
                  Sum(total) AS abono
                  FROM
                  facturacion
                  WHERE status <> 'ca' AND status <> 'b' AND id_nc IS NOT NULL AND id_abono_factura IS NULL
                  GROUP BY id_nc
                )
              ) AS ffs
              GROUP BY id_factura
            ) AS ac ON f.id_factura = ac.id_factura
            INNER JOIN (
              SELECT distinct on(id_factura) id_factura, fecha
              FROM (
                (
                  SELECT
                  id_factura, Date(fecha) AS fecha
                  FROM
                  facturacion_abonos as fa
                )
                UNION
                (
                  SELECT
                  id_nc AS id_factura, Date(fecha) AS fecha
                  FROM
                  facturacion
                  WHERE status <> 'ca' AND status <> 'b' AND id_nc IS NOT NULL AND id_abono_factura IS NULL
                )
              ) AS fff
              WHERE fecha <= {$sql_fecha2}
              ORDER BY id_factura, fecha DESC
            ) AS acf ON f.id_factura = acf.id_factura
            -- LEFT JOIN (
            --   SELECT id_remision, id_factura, status
            --   FROM remisiones_historial WHERE status <> 'ca' AND status <> 'b'
            -- ) fh ON f.id_factura = fh.id_remision
          WHERE f.id_abono_factura IS NULL
            AND f.status <> 'ca' AND f.status <> 'b' AND id_nc IS NULL
            AND (Date(f.fecha) BETWEEN {$sql_fecha})
            AND f.is_factura = 't'
            AND (COALESCE(f.total, 0) - COALESCE(ac.abono, 0))::numeric(15,2) <= 0
             {$sql}
            -- AND COALESCE(fh.id_remision, 0) = 0
          ORDER BY fecha ASC, serie ASC, folio ASC
        ) d
        INNER JOIN facturacion_productos cp ON cp.id_factura = d.id_factura
        INNER JOIN clasificaciones AS pr ON pr.id_clasificacion = cp.id_clasificacion
        INNER JOIN unidades AS pu ON pu.id_unidad = pr.id_unidad
      WHERE cp.ieps > 0 AND d.fecha BETWEEN {$sql_fecha}
        ORDER BY cp.porcentaje_ieps ASC
      ");
    $response = $facturas->result();

    return $response;
  }
  public function getRptIepsVentasXls($show = false)
  {
    if (!$show) {
      header('Content-type: application/vnd.ms-excel; charset=utf-8');
      header("Content-Disposition: attachment; filename=rpt_ieps_ventas.xls");
      header("Pragma: no-cache");
      header("Expires: 0");
    }

    $res = $this->getRptIepsVentasData();
    // echo "<pre>";
    // var_dump($res);
    // echo "</pre>";exit;

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Reporte de IEPS Ventas';
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";


    $html = '<table>
      <tbody>
        <tr>
          <td colspan="11" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="11" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="11" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="11"></td>
        </tr>

        ';

    $porcentaje_ieps = '';
    $total_subtotal = $total_iva = $total_total = $total_ieps = 0;
    $subtotal = $iva = $total = $ieps = $ret_iva = 0;
    foreach($res as $key => $item){
      if ($porcentaje_ieps != $item->porcentaje_ieps) {
        if ($key > 0) {
          $html .= '
          <tr style="font-weight:bold">
            <td colspan="7"></td>
            <td style="border:1px solid #000;">'.$subtotal.'</td>
            <td style="border:1px solid #000;">'.$iva.'</td>
            <td style="border:1px solid #000;">'.$ieps.'</td>
            <td style="border:1px solid #000;">'.$total.'</td>
          </tr>
          <tr>
            <td colspan="11"></td>
          </tr>';
        }

        $html .= '<tr style="font-weight:bold">
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Fecha de Pago</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Cliente</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">RFC</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Categoría</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Producto</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Cantidad</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Unidad</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Subtotal</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">IVA</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">IEPS</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Total</td>
        </tr>';

        $subtotal = $iva = $total = $ieps = 0;
        $porcentaje_ieps = $item->porcentaje_ieps;
      }

      $categoria = '';
      if ($item->porcentaje_ieps == '6') {
        $categoria = '4';
      } elseif ($item->porcentaje_ieps == '7') {
        $categoria = '3';
      } elseif ($item->porcentaje_ieps == '9') {
        $categoria = '1, 2';
      }

      $html .= '<tr>
          <td style="width:150px;border:1px solid #000;">'.$item->fecha_pago.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->cliente.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->rfc_cliente.'</td>
          <td style="width:150px;border:1px solid #000;">'.$categoria.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->descripcion.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->cantidad.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->unidad.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->importe.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->iva.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->ieps.'</td>
          <td style="width:150px;border:1px solid #000;">'.($item->importe+$item->iva+$item->ieps).'</td>
        </tr>';

      $subtotal += $item->importe;
      $iva      += $item->iva;
      $total    += ($item->importe+$item->iva+$item->ieps);
      $ieps     += $item->ieps;

      $total_subtotal += $item->importe;
      $total_iva      += $item->iva;
      $total_total    += ($item->importe+$item->iva+$item->ieps);
      $total_ieps     += $item->ieps;



    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="7"></td>
          <td style="border:1px solid #000;">'.$subtotal.'</td>
          <td style="border:1px solid #000;">'.$iva.'</td>
          <td style="border:1px solid #000;">'.$ieps.'</td>
          <td style="border:1px solid #000;">'.$total.'</td>
        </tr>
        <tr>
          <td colspan="14"></td>
        </tr>

        <tr style="font-weight:bold">
          <td colspan="7"></td>
          <td style="border:1px solid #000;">'.$total_subtotal.'</td>
          <td style="border:1px solid #000;">'.$total_iva.'</td>
          <td style="border:1px solid #000;">'.$total_ieps.'</td>
          <td style="border:1px solid #000;">'.$total_total.'</td>
        </tr>
        <tr>
          <td colspan="14"></td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

}