<?php
class cuentas_cobrar_model extends privilegios_model{

  function __construct(){
    parent::__construct();
    $this->load->model('bitacora_model');
  }


  /**
   * Saldos de los clientes
   *
   * @return
   */
  public function getCuentasCobrarData($perpage = '60', $sql2='')
  {
    $sql = '';
    //paginacion
    $params = array(
      'result_items_per_page' => $perpage,
      'result_page'       => (isset($_GET['pag'])? $_GET['pag']: 0)
      );
    if($params['result_page'] % $params['result_items_per_page'] == 0)
      $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha1']: $_GET['ffecha2'];
    $_GET['ftipo'] = (isset($_GET['ftipo']))?$_GET['ftipo']:'pp';

    $sql = $this->input->get('ftipo')=='pv'? " AND (Date('".$fecha."'::timestamp with time zone)-Date(f.fecha)) > f.plazo_credito": '';
    $sqlt = $this->input->get('ftipo')=='pv'? " AND (Date('".$fecha."'::timestamp with time zone)-Date(f.fecha)) > f.plazo_credito": '';

    if($this->input->get('fid_cliente') != ''){
      $sql .= " AND f.id_cliente = '".$this->input->get('fid_cliente')."'";
      $sqlt .= " AND f.id_cliente = '".$this->input->get('fid_cliente')."'";
    }

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != '' && $_GET['did_empresa'] != 'all'){
      $sql .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
      $sqlt .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if($this->input->get('ftipodoc') != ''){
      $sql .= " AND f.is_factura = '".($this->input->get('ftipodoc') === 'f' ? 't' : 'f')."'";
    }

    $query = BDUtil::pagination(
      "SELECT
        id_cliente,
        nombre_fiscal as nombre,
        Sum(total) AS total,
        Sum(iva) AS iva,
        Sum(abonos) AS abonos,
        Sum(saldo)::numeric(12, 2) AS saldo,
        SUM(saldo_cambio) as saldo_cambio,
        (SUM(plazo_credito) / Count(plazo_credito))::Numeric(10, 0) as plazo_credito,
        SUM(vencidas) as vencidas
      FROM
      (
        SELECT
          sal1.id_cliente,
          sal1.nombre_fiscal,
          Sum(sal1.total) AS total,
          Sum(sal1.iva) AS iva,
          Sum(sal1.plazo_credito) / Count(sal1.plazo_credito) AS plazo_credito,
          Sum(sal1.abonos) as abonos,
          Sum(sal1.saldo) AS saldo,
          Sum(sal1.saldo_cambio) AS saldo_cambio,
          Sum(sal1.vencidas) AS vencidas
        FROM (
          SELECT
            c.id_cliente,
            c.nombre_fiscal,
            Sum(f.total) AS total,
            Sum(f.importe_iva) AS iva,
            Sum(f.plazo_credito) AS plazo_credito,
            COALESCE(Sum(faa.abonos),0) as abonos,
            COALESCE(Sum(f.total) - COALESCE(Sum(faa.abonos),0), 0)::Numeric(15, 2) AS saldo,
            (CASE WHEN f.tipo_cambio > 1 THEN COALESCE(Sum(f.total/f.tipo_cambio) - COALESCE(faa.abonos/f.tipo_cambio, 0), 0) ELSE 0 END) AS saldo_cambio,
            Sum(CASE WHEN Date(Date(f.fecha) + (f.plazo_credito || ' days')::interval) < Date(now()) THEN
              COALESCE(f.total - COALESCE(faa.abonos, 0), 0)
             ELSE
              0
             END)::Numeric(15, 2) AS vencidas
          FROM
            clientes AS c
          INNER JOIN facturacion AS f ON c.id_cliente = f.id_cliente
          LEFT JOIN (
            SELECT
            d.id_cliente,
            d.id_factura,
            Sum(d.abonos) AS abonos
            FROM
            (
            SELECT
              f.id_cliente,
              f.id_factura,
              Sum(fa.total) AS abonos
            FROM
              facturacion AS f
            INNER JOIN facturacion_abonos AS fa ON f.id_factura = fa.id_factura
            WHERE f.status <> 'ca' AND f.status <> 'b' AND f.id_abono_factura IS NULL
              AND Date(fa.fecha) <= '{$fecha}'{$sql}
            GROUP BY f.id_cliente, f.id_factura

            UNION

            SELECT
              f.id_cliente,
              f.id_nc AS id_factura,
              Sum(f.total) AS abonos
            FROM
              facturacion AS f
            WHERE f.status <> 'ca' AND f.status <> 'b' AND f.id_nc IS NOT NULL AND f.id_abono_factura IS NULL
              AND Date(f.fecha) <= '{$fecha}'{$sql}
            GROUP BY f.id_cliente, f.id_factura
            ) AS d
            GROUP BY d.id_cliente, d.id_factura
          ) AS faa ON f.id_cliente = faa.id_cliente AND f.id_factura = faa.id_factura
          LEFT JOIN (
            SELECT id_remision, id_factura, status
            FROM remisiones_historial WHERE status <> 'ca' AND status <> 'b'
          ) fh ON f.id_factura = fh.id_remision
          WHERE f.status <> 'ca' AND f.status <> 'b'
            AND f.id_abono_factura IS NULL AND id_nc IS NULL
            AND Date(f.fecha) <= '{$fecha}'{$sql}
            AND COALESCE(fh.id_remision, 0) = 0
          GROUP BY c.id_cliente, c.nombre_fiscal, faa.abonos, f.tipo_cambio, f.fecha,f.plazo_credito
        ) sal1
        GROUP BY sal1.id_cliente, sal1.nombre_fiscal
      ) AS sal
      GROUP BY id_cliente, nombre_fiscal
      HAVING Sum(saldo)::numeric(12, 2) > 0 OR SUM(saldo_cambio) > 0
      ORDER BY saldo DESC",
      $params, true);
    $res = $this->db->query($query['query']);

    $response = array(
      'cuentas'             => array(),
      'total_rows'          => $query['total_rows'],
      'items_per_page'      => $params['result_items_per_page'],
      'result_page'         => $params['result_page'],
      'ttotal_cargos'       => 0,
      'ttotal_abonos'       => 0,
      'ttotal_saldo'        => 0,
      'ttotal_saldo_cambio' => 0,
      );
    if($res->num_rows() > 0)
      $response['cuentas'] = $res->result();

    foreach ($query['resultset']->result() as $cliente) {
      $response['ttotal_cargos']       += $cliente->total;
      $response['ttotal_abonos']       += $cliente->abonos;
      $response['ttotal_saldo']        += $cliente->saldo;
      $response['ttotal_saldo_cambio'] += $cliente->saldo_cambio;
    }

    return $response;
  }
  /**
   * Descarga el listado de cuentas por pagar en formato pdf
   */
  public function cuentasCobrarPdf($pdf=null, $num=0) {
    $this->load->library('mypdf');

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    // Creación del objeto de la clase heredada
    $showw = false;
    $first = false;
    if (is_null($pdf)) {
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $showw = true;
      $first = true;
    }

    if ($empresa['info']->logo !== '' && file_exists($empresa['info']->logo))
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'Cuentas por cobrar';
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': $this->input->get('ftipo') == 'pp'? 'Pendientes por cobrar': 'Todas');
    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'R', 'R', 'R', 'R', 'R', 'R');
    $widths = array(75, 30, 30, 25, 25, 15, 30);
    $header = array('Cliente', 'Cargos', 'Abonos', 'Saldo', 'Vencido', 'D. Cred.',
      // 'Saldo TC'
    );

    $res = $this->getCuentasCobrarData(9999999999);

    $total_saldo_cambio = $total_cargos = $total_abonos = $total_saldo = 0;
    $total_vencido = 0;
    if (count($res['cuentas']) > 0) {
      foreach($res['cuentas'] as $key => $item){
        $band_head = false;
        if($pdf->GetY() >= $pdf->limiteY || $key == 0){ //salta de pagina si exede el max
          if ($showw && $key == 0) {
            $pdf->AddPage();
          } elseif ($key > 0 && $pdf->GetY() >= $pdf->limiteY) {
            $pdf->AddPage();
          }

          $pdf->SetFont('Arial','B',8);
          $pdf->SetTextColor(255,255,255);
          $pdf->SetFillColor(160,160,160);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);

        }

        $pdf->SetFont('Arial','',8);
        $pdf->SetTextColor(0,0,0);
        $datos = array($item->nombre,
          MyString::formatoNumero($item->total, 2, '$', false),
          MyString::formatoNumero($item->abonos, 2, '$', false),
          MyString::formatoNumero($item->saldo, 2, '$', false),
          MyString::formatoNumero($item->vencidas, 2, '$', false),
          MyString::formatoNumero($item->plazo_credito, 0, '', false),
          // MyString::formatoNumero($item->saldo_cambio, 2, '$', false),
          );
        $total_cargos += $item->total;
        $total_abonos += $item->abonos;
        $total_saldo += $item->saldo;
        $total_saldo_cambio += $item->saldo_cambio;
        $total_vencido += $item->vencidas;

        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($datos, false);
      }

      // $pdf->SetFont('Arial','',8);
      // $pdf->SetTextColor(0,0,0);
      // $datos = array($item->nombre,
      //   MyString::formatoNumero($item->total, 2, '$', false),
      //   MyString::formatoNumero($item->abonos, 2, '$', false),
      //   MyString::formatoNumero($item->saldo, 2, '$', false),
      //   // MyString::formatoNumero($item->saldo_cambio, 2, '$', false),
      //   );
      // $total_cargos += $item->total;
      // $total_abonos += $item->abonos;
      // $total_saldo += $item->saldo;
      // $total_saldo_cambio += $item->saldo_cambio;

      // $pdf->SetX(6);
      // $pdf->SetAligns($aligns);
      // $pdf->SetWidths($widths);
      // $pdf->Row($datos, false);
    }

    $pdf->SetFillColor(160,160,160);
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->SetX(6);
    $pdf->SetFont('Arial','B',8);
    $pdf->SetTextColor(255,255,255);
    $pdf->Row(array('Total:',
      MyString::formatoNumero($total_cargos, 2, '$', false),
      MyString::formatoNumero($total_abonos, 2, '$', false),
      MyString::formatoNumero($total_saldo, 2, '$', false),
      MyString::formatoNumero($total_vencido, 2, '$', false),
      '',
      // MyString::formatoNumero($total_saldo_cambio, 2, '$', false),
      ), true);


      if ($this->input->get('did_empresa') == 11 &&
        $this->input->get('ffecha1') == $this->input->get('ffecha2') &&
        $this->usuarios_model->tienePrivilegioDe('', 'bodega_guadalajara/show_totales_c/')) { // ESJ BODEGA
        $this->load->model('bodega_guadalajara_model');
        $caja = $this->bodega_guadalajara_model->printCaja($this->input->get('ffecha1'), 1, true);

        $pdf->SetTextColor(0,0,0);
        if ($pdf->GetY() > 200) {
          $pdf->AddPage();
        }
        $this->bodega_guadalajara_model->printEstadoResultado($pdf, $caja['estadoResult'], 10);
      }
    if ($showw) {

      $pdf->Output('cuentas_x_cobrar.pdf', 'I');
    } else {
      return $pdf;
    }
  }

  public function cuentasCobrarExcel(){
    $res = $this->getCuentasCobrarData(9999999999);

    $this->load->library('myexcel');
    $xls = new myexcel();

    $worksheet =& $xls->workbook->addWorksheet();

    $xls->titulo2 = 'Cuentas por cobrar';
    $xls->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $xls->titulo4 = ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': $this->input->get('ftipo') == 'pp'? 'Pendientes por cobrar': 'Todas');

    $data_fac = $res['cuentas'];

    $row=0;
    //Header
    $xls->excelHead($worksheet, $row, 8, array(
      array($xls->titulo2, 'format_title2'),
      array($xls->titulo3, 'format_title3'),
      array($xls->titulo4, 'format_title3')
      ));

    $row +=3;
    $xls->excelContent($worksheet, $row, $data_fac, array(
      'head' => array('Cliente', 'Cargos', 'Abonos', 'Saldo', 'Vencido', 'Dias Cred.', 'Saldo TC'),
      'conte' => array(
        array('name' => 'nombre', 'format' => 'format4', 'sum' => -1),
        array('name' => 'total', 'format' => 'format4', 'sum' => 0),
        array('name' => 'abonos', 'format' => 'format4', 'sum' => 0),
        array('name' => 'saldo', 'format' => 'format4', 'sum' => 0),
        array('name' => 'vencidas', 'format' => 'format4', 'sum' => 0),
        array('name' => 'plazo_credito', 'format' => 'format4', 'sum' => -1),
        array('name' => 'saldo_cambio', 'format' => 'format4', 'sum' => 0),
        )
      ));

    foreach ($data_fac as $key => $cuenta) {
      $_GET['id_cliente'] = $cuenta->id_cliente;
      $this->cuentaClienteExcel($xls, false);
    }

    $xls->workbook->send('cuentas_cobrar.xls');
    $xls->workbook->close();
  }

  public function cuentasCobrarAllPdf() {
    $this->load->library('mypdf');

    $empresas = $this->db->query("SELECT id_empresa, nombre_fiscal, rfc,
        sucursal, calle, no_exterior, no_interior, colonia, localidad, municipio, estado, status
      FROM empresas
      WHERE status = 't'
      ORDER BY num_orden ASC")->result();

    $pdf = new MYpdf('L', 'mm', 'Letter');
    $pdf->show_head = false;
    $pdf->limiteY = 190;

    $pdf->AddPage();
    $pdf->SetFont('Arial','B',11);
    $pdf->SetXY(46, 11);
    $pdf->Cell(206, 6, 'Cuentas por cobrar', 0, 0, 'C');
    $pdf->SetXY(6, 23);


    foreach ($empresas as $key => $emp) {
      $_GET['did_empresa'] = $emp->id_empresa;
      if($pdf->GetY() >= $pdf->limiteY) {
        $pdf->AddPage();
      }
      $pdf->SetFont('Arial','B',8);
      $pdf->SetTextColor(255,255,255);
      $pdf->SetFillColor(160,160,160);
      $pdf->SetX(6);
      $pdf->SetAligns(['L']);
      $pdf->SetWidths([265]);
      $pdf->Row([$emp->nombre_fiscal], true);

      $pdf = $this->cuentasCobrarPdf($pdf, $key);
      $pdf->SetY($pdf->GetY()+5);
    }

    $pdf->Output('cuentas_x_cobrar_all.pdf', 'I');
  }


  /**
   *  CUENTAS
   * ***************************************
   * Saldo de un cliente seleccionado
   * @return [type] [description]
   */
  public function getCuentaClienteData()
  {
    $sqlp1 = $sqlp2 = $sqlp3 = $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha1 = $fecha2 = '';
    if($_GET['ffecha1'] > $_GET['ffecha2']){
      $fecha2 = $_GET['ffecha1'];
      $fecha1 = $_GET['ffecha2'];
    }else{
      $fecha2 = $_GET['ffecha2'];
      $fecha1 = $_GET['ffecha1'];
    }

    $sql = $sqlt = $sql2 = '';
    if($this->input->get('ftipo')=='pv'){
      $sql = " AND (Date('".$fecha2."'::timestamp with time zone)-Date(f.fecha)) > f.plazo_credito";
      $sqlt = " AND (Date('".$fecha2."'::timestamp with time zone)-Date(f.fecha)) > f.plazo_credito";
      $sql2 = 'WHERE saldo > 0';
    }

    if($this->input->get('did_empresa') != ''){
      $sql .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
      $sqlt .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if($this->input->get('id_cliente') != ''){
      $sqlp1 = " AND f.id_cliente = '".$this->input->get('id_cliente')."'";
      $sqlp2 = " AND c.id_cliente = '".$this->input->get('id_cliente')."'";
      $sqlp3 = " AND id_cliente = '".$this->input->get('id_cliente')."'";
    }

    if($this->input->get('ftipodoc') != ''){
      $sql .= " AND f.is_factura = '".($this->input->get('ftipodoc') === 'f' ? 't' : 'f')."'";
    }

    /*** Saldo anterior ***/
    $saldo_anterior = $this->db->query(
      "SELECT
      id_cliente,
      Sum(total) AS total,
      Sum(iva) AS iva,
      Sum(abonos) AS abonos,
      Sum(saldo)::numeric(12, 2) AS saldo,
      SUM(saldo_cambio) as saldo_cambio,
      tipo
      FROM
      (
        SELECT
        c.id_cliente,
        c.nombre_fiscal,
        Sum(f.total) AS total,
        Sum(f.importe_iva) AS iva,
        COALESCE(Sum(faa.abonos),0) as abonos,
        COALESCE(Sum(f.total) - COALESCE(Sum(faa.abonos),0), 0) AS saldo,
        (CASE WHEN f.tipo_cambio > 1 THEN COALESCE(Sum(f.total/f.tipo_cambio) - COALESCE(faa.abonos/f.tipo_cambio, 0), 0) ELSE 0 END) AS saldo_cambio,
        'f' as tipo
        FROM
        clientes AS c
        INNER JOIN facturacion AS f ON c.id_cliente = f.id_cliente
        LEFT JOIN (
          SELECT
          d.id_cliente,
          d.id_factura,
          Sum(d.abonos) AS abonos
          FROM
          (
            SELECT
            f.id_cliente,
            f.id_factura,
            Sum(fa.total) AS abonos
            FROM
            facturacion AS f
            INNER JOIN facturacion_abonos AS fa ON f.id_factura = fa.id_factura
            WHERE f.status <> 'ca' AND f.status <> 'b' AND f.id_abono_factura IS NULL
            {$sqlp1}
            AND Date(fa.fecha) <= '{$fecha2}'{$sql}
            GROUP BY f.id_cliente, f.id_factura

            UNION

            SELECT
            f.id_cliente,
            f.id_nc AS id_factura,
            Sum(f.total) AS abonos
            FROM
            facturacion AS f
            WHERE f.status <> 'ca' AND f.status <> 'b' AND f.id_nc IS NOT NULL AND f.id_abono_factura IS NULL
            {$sqlp1}
            AND Date(f.fecha) <= '{$fecha2}'{$sql}
            GROUP BY f.id_cliente, f.id_factura
          ) AS d
          GROUP BY d.id_cliente, d.id_factura
        ) AS faa ON f.id_cliente = faa.id_cliente AND f.id_factura = faa.id_factura
        LEFT JOIN (SELECT id_remision, id_factura, status
          FROM remisiones_historial WHERE status <> 'ca' AND status <> 'b'
        ) fh ON f.id_factura = fh.id_remision
        WHERE f.status <> 'ca' AND f.status <> 'b'
        {$sqlp2}
        AND f.id_abono_factura IS NULL AND id_nc IS NULL
        AND Date(f.fecha) < '{$fecha1}'
        AND COALESCE(fh.id_remision, 0) = 0 {$sql}
        GROUP BY c.id_cliente, c.nombre_fiscal, faa.abonos, tipo, f.tipo_cambio
        ) AS sal
    {$sql2}
    GROUP BY id_cliente, tipo
    ");

    /*** Facturas y ventas en el rango de fechas ***/
    $res = $this->db->query(
      "SELECT
        f.id_factura,
        f.serie,
        f.folio,
        Date(f.fecha) AS fecha,
        COALESCE(f.total, 0) AS cargo,
        COALESCE(f.importe_iva, 0) AS iva,
        COALESCE(ac.abono, 0) AS abono,
        (COALESCE(f.total, 0) - COALESCE(ac.abono, 0))::numeric(100,2) AS saldo,
        (CASE WHEN f.tipo_cambio > 1 THEN (COALESCE(f.total/f.tipo_cambio, 0) - COALESCE(ac.abono/f.tipo_cambio, 0))::numeric(100,2) ELSE 0 END) AS saldo_cambio,
        (CASE (COALESCE(f.total, 0) - COALESCE(ac.abono, 0)) WHEN 0 THEN 'Pagada' ELSE 'Pendiente' END) AS estado,
        Date(f.fecha + (f.plazo_credito || ' days')::interval) AS fecha_vencimiento,
        (Date('{$fecha2}'::timestamp with time zone)-Date(f.fecha + (f.plazo_credito || ' days')::interval)) AS dias_transc,
        ( (CASE WHEN f.is_factura='t' THEN 'FACTURA ' ELSE 'REMISION ' END) || f.serie || f.folio) AS concepto,
        'f' as tipo, c.nombre_fiscal AS cliente
      FROM
        facturacion AS f
        LEFT JOIN (
          SELECT id_factura, Sum(abono) AS abono
          FROM (
            (
              SELECT
              id_factura,
              Sum(total) AS abono
              FROM
              facturacion_abonos as fa
              WHERE Date(fecha) <= '{$fecha2}'
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
              {$sqlp3}
              AND Date(fecha) <= '{$fecha2}'
              GROUP BY id_nc
            )
          ) AS ffs
          GROUP BY id_factura
        ) AS ac ON f.id_factura = ac.id_factura {$sql}
        LEFT JOIN (SELECT id_remision, id_factura, status
          FROM remisiones_historial WHERE status <> 'ca' AND status <> 'b'
        ) fh ON f.id_factura = fh.id_remision
        LEFT JOIN clientes c ON c.id_cliente = f.id_cliente
      WHERE f.id_abono_factura IS NULL
        {$sqlp1}
        AND f.status <> 'ca' AND f.status <> 'b' AND id_nc IS NULL
        AND (Date(f.fecha) >= '{$fecha1}' AND Date(f.fecha) <= '{$fecha2}')
        AND COALESCE(fh.id_remision, 0) = 0 {$sql}

      ORDER BY fecha ASC, serie ASC, folio ASC
    ");


    //obtenemos la info del cliente
    $prov = null;
    if (!empty($_GET['id_cliente'])) {
      $this->load->model('clientes_model');
      $prov = $this->clientes_model->getClienteInfo($_GET['id_cliente'], true);
      $prov = $prov['info'];
    }

    $response = array(
      'cuentas'  => array(),
      'anterior' => array(),
      'cliente'  => $prov,
      'fecha1'   => $fecha1
      );
    if($res->num_rows() > 0){
      $response['cuentas'] = $res->result();

      //verifica q no sea negativo o exponencial el saldo
    foreach ($response['cuentas'] as $key => $cuenta) {
      $cuenta->saldo = floatval(MyString::float($cuenta->saldo));
      $cuenta->saldo_cambio = floatval(MyString::float($cuenta->saldo_cambio));
          // anticipos a fruta
      if ((strtolower($cuenta->serie) == 'an') && $cuenta->saldo == 0) {
        $cuenta->cargo = 0;
        } elseif ( strtolower($cuenta->serie) != 'an') { // $cuenta->cargo == 0 &&
          $resp = $this->db
          ->select('fp.id_factura, fp.num_row, fp.cantidad, fp.descripcion, fp.precio_unitario, fp.importe, fp.iva')
          ->from('facturacion_productos as fp')
          ->where("fp.id_factura = ".$cuenta->id_factura)
          ->where("fp.descripcion = 'ANTICIPO A FRUTA'")->get()->row();
          if (isset($resp->id_factura) && $resp->importe < 0) {
            $cuenta->cargo += abs($resp->importe);
          }
        }

        $cuenta->saldo = floatval(MyString::float($cuenta->saldo));
        if($cuenta->saldo == 0){
          $cuenta->estado = 'Pagada';
          $cuenta->fecha_vencimiento = $cuenta->dias_transc = '';

          if($this->input->get('ftipo')=='pv' || $this->input->get('ftipo')=='pp')
            unset($response['cuentas'][$key]);
        }elseif($cuenta->dias_transc<0)
        $cuenta->dias_transc = '';
      }
    }

    if($saldo_anterior->num_rows() > 0){
      $response['anterior'] = $saldo_anterior->result();
      foreach ($response['anterior'] as $key => $c) {
        if ($key > 0){
          $response['anterior'][0]->total        += $c->total;
          $response['anterior'][0]->abonos       += $c->abonos;
          $response['anterior'][0]->saldo        += $c->saldo;
          $response['anterior'][0]->saldo_cambio += $c->saldo_cambio;
        }
      }
    }

    return $response;
  }

  /**
   * Descarga el listado de cuentas por pagar en formato pdf
   */
  public function cuentaClientePdf(){
    $res = $this->getCuentaClienteData();

    // echo "<pre>";
    //   var_dump($res);
    // echo "</pre>";exit;

    if (count($res['anterior']) > 0)
      $res['anterior'] = $res['anterior'][0];

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->titulo2 = 'Cuenta de '.$res['cliente']->nombre_fiscal;
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
    $pdf->AliasNbPages();
    //$pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('C', 'C', 'C', 'L', 'R', 'R', 'R', 'R', 'C', 'C', 'C');
    $widths = array(17, 11, 15, 38, 23, 23, 20, 17, 16, 17, 10);
    $header = array('Fecha', 'Serie', 'Folio', 'Concepto', 'Cargo', 'Abono', 'Saldo', 'Saldo TC', 'Estado', 'F. Ven.', 'Trans');

    $total_cargo = 0;
    $total_abono = 0;
    $total_saldo_cambio = $total_saldo = 0;

    $bad_saldo_ante = true;
    if(isset($res['anterior']->saldo)){ //se suma a los totales del saldo anterior
      $total_cargo        += $res['anterior']->total;
      $total_abono        += $res['anterior']->abonos;
      $total_saldo        += $res['anterior']->saldo;
      $total_saldo_cambio += $res['anterior']->saldo_cambio;
    }else{
      $res['anterior'] = new stdClass();
      $res['anterior']->total        = 0;
      $res['anterior']->abonos       = 0;
      $res['anterior']->saldo        = 0;
      $res['anterior']->saldo_cambio = 0;
    }
    $res['anterior']->concepto = 'Saldo anterior a '.$res['fecha1'];

    foreach($res['cuentas'] as $key => $item){
      $band_head = false;
      if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        $pdf->AddPage();

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
      }

      $pdf->SetFont('Arial','',8);
      $pdf->SetTextColor(0,0,0);
      if($bad_saldo_ante){
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row(array('', '', '', $res['anterior']->concepto,
          MyString::formatoNumero($res['anterior']->total, 2, '$', false),
          MyString::formatoNumero($res['anterior']->abonos, 2, '$', false),
          MyString::formatoNumero($res['anterior']->saldo, 2, '$', false),
          MyString::formatoNumero($res['anterior']->saldo_cambio, 2, '$', false),
          '', '', ''), false);
        $bad_saldo_ante = false;
      }

      $datos = array($item->fecha,
        $item->serie,
        $item->folio,
        $item->concepto,
        MyString::formatoNumero($item->cargo, 2, '$', false),
        MyString::formatoNumero($item->abono, 2, '$', false),
        MyString::formatoNumero($item->saldo, 2, '$', false),
        MyString::formatoNumero($item->saldo_cambio, 2, '$', false),
        $item->estado, $item->fecha_vencimiento,
        $item->dias_transc > 0 ? $item->dias_transc : '0');

      $total_cargo        += $item->cargo;
      $total_abono        += $item->abono;
      $total_saldo        += $item->saldo;
      $total_saldo_cambio += $item->saldo_cambio;

      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false);
    }

    $pdf->SetX(6);
    $pdf->SetFont('Arial','B',8);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetAligns(array('R', 'R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(81, 23, 23, 20, 17));
    $pdf->Row(array('Totales:',
      MyString::formatoNumero($total_cargo, 2, '$', false),
      MyString::formatoNumero($total_abono, 2, '$', false),
      MyString::formatoNumero($total_saldo, 2, '$', false),
      MyString::formatoNumero($total_saldo_cambio, 2, '$', false) ), true);

    $pdf->Output('cuentas_proveedor.pdf', 'I');
  }

  public function cuentaClienteExcel(&$xls=null, $close=true){
    $res = $this->getCuentaClienteData();

    if (count($res['anterior']) > 0)
      $res['anterior'] = $res['anterior'][0];

    $this->load->library('myexcel');
    if($xls == null)
      $xls = new myexcel();

    $worksheet =& $xls->workbook->addWorksheet();

    $xls->titulo2 = 'Cuenta de '.$res['cliente']->nombre_fiscal;
    $xls->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $xls->titulo4 = ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');

    if(is_array($res['anterior'])){
      $res['anterior'] = new stdClass();
      $res['anterior']->cargo        = 0;
      $res['anterior']->abono        = 0;
      $res['anterior']->saldo        = 0;
      $res['anterior']->saldo_cambio = 0;
    }else{
      $res['anterior']->cargo = $res['anterior']->total;
      $res['anterior']->abono = $res['anterior']->abonos;
    }
    $res['anterior']->fecha       = $res['anterior']->serie = $res['anterior']->folio = '';
    $res['anterior']->concepto    = $res['anterior']->estado = $res['anterior']->fecha_vencimiento = '';
    $res['anterior']->dias_transc = '';

    array_unshift($res['cuentas'], $res['anterior']);

    $data_fac = $res['cuentas'];

    $row=0;
    //Header
    $xls->excelHead($worksheet, $row, 8, array(
      array($xls->titulo2, 'format_title2'),
      array($xls->titulo3, 'format_title3'),
      array($xls->titulo4, 'format_title3')
      ));

    $row +=3;
    $xls->excelContent($worksheet, $row, $data_fac, array(
      'head' => array('Fecha', 'Serie', 'Folio', 'Concepto', 'Cargo', 'Abono', 'Saldo', 'Saldo TC', 'Estado', 'Fecha Vencimiento', 'Dias Trans.'),
      'conte' => array(
        array('name' => 'fecha', 'format' => 'format4', 'sum' => -1),
        array('name' => 'serie', 'format' => 'format4', 'sum' => -1),
        array('name' => 'folio', 'format' => 'format4', 'sum' => -1),
        array('name' => 'concepto', 'format' => 'format4', 'sum' => -1),
        array('name' => 'cargo', 'format' => 'format4', 'sum' => 0),
        array('name' => 'abono', 'format' => 'format4', 'sum' => 0),
        array('name' => 'saldo', 'format' => 'format4', 'sum' => 0),
        array('name' => 'saldo_cambio', 'format' => 'format4', 'sum' => 0),
        array('name' => 'estado', 'format' => 'format4', 'sum' => -1),
        array('name' => 'fecha_vencimiento', 'format' => 'format4', 'sum' => -1),
        array('name' => 'dias_transc', 'format' => 'format4', 'sum' => -1))
      ));

    if($close){
      $xls->workbook->send('cuentaCliente.xls');
      $xls->workbook->close();
    }
  }

  public function cuenta2ClientePdf(){
    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('L', 'mm', 'Letter');

    $res = $this->getCuentaClienteData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $pdf->logo = $empresa['info']->logo!=''? (file_exists($empresa['info']->logo)? $empresa['info']->logo: '') : '';
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    if (count($res['anterior']) > 0)
      $res['anterior'] = $res['anterior'][0];

    $pdf->titulo2 = isset($res['cliente']->nombre_fiscal)? 'Cuenta de '.$res['cliente']->nombre_fiscal: '';
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por pagar');
    $pdf->AliasNbPages();
    //$pdf->AddPage();

    // $response = $this->cuentaProveedorCurpPdf($pdf, $res);
    // echo "<pre>";
    //   var_dump($res);
    // echo "</pre>";exit;


    $pdf->SetFont('Arial','',8);

    $aligns = array('C', 'C', 'C', 'L', 'L', 'R', 'R', 'R', 'C', 'C', 'C');
    $widths = array(17, 11, 20, 40, 50, 28, 28, 28, 16, 17, 15);
    $header = array('Fecha', 'Serie', 'Folio', 'Concepto', 'Cliente', 'Cargo', 'Abono', 'Saldo', 'Estado', 'F. Ven.', 'D. Trans.');

    $total_cargo = 0;
    $total_abono = 0;
    $total_saldo = 0;

    $bad_saldo_ante = true;
    if(isset($res['anterior']->saldo)){ //se suma a los totales del saldo anterior
      // $total_cargo += $res['anterior']->total;
      // $total_abono += $res['anterior']->abonos;
      // $total_saldo += $res['anterior']->saldo;
    }else{
      $res['anterior'] = new stdClass();
      $res['anterior']->total = 0;
      $res['anterior']->abonos = 0;
      $res['anterior']->saldo = 0;
    }
    $res['anterior']->concepto = 'Saldo anterior a '.$res['fecha1'];
    foreach($res['cuentas'] as $key => $item){
      $band_head = false;
      if($pdf->GetY()+5 >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
        if ($pdf->GetY()+5 >= $pdf->limiteY)
          $pdf->AddPage();

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(255,255,255);
        $pdf->SetFillColor(160,160,160);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
      }

      $pdf->SetTextColor(0,0,0);
      if($bad_saldo_ante){
        $pdf->SetFont('Arial','',7);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row(array('', '', '', $res['anterior']->concepto, '',
          MyString::formatoNumero($res['anterior']->total, 2, '$', false),
          MyString::formatoNumero($res['anterior']->abonos, 2, '$', false),
          MyString::formatoNumero($res['anterior']->saldo, 2, '$', false),
          '', '', ''), false);
        $bad_saldo_ante = false;
      }

      $pdf->SetFont('Arial','',8);

      $datos = array($item->fecha,
                  $item->serie,
                  $item->folio,
                  $item->concepto,
                  $item->cliente,
                  MyString::formatoNumero($item->cargo, 2, '$', false),
                  MyString::formatoNumero($item->abono, 2, '$', false),
                  MyString::formatoNumero($item->saldo, 2, '$', false),
                  $item->estado, $item->fecha_vencimiento,
                  $item->dias_transc);

      $total_cargo += $item->cargo;
      $total_abono += $item->abono;
      $total_saldo += $item->saldo;

      $pdf->SetX(6);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false);
    }

    $pdf->SetX(6);
    $pdf->SetFont('Arial','B',8);
    $pdf->SetTextColor(255,255,255);
    $pdf->SetAligns(array('R', 'R', 'R', 'R'));
    $pdf->SetWidths(array(138, 28, 28, 28));
    $pdf->Row(array('Totales:',
        MyString::formatoNumero($total_cargo, 2, '$', false),
        MyString::formatoNumero($total_abono, 2, '$', false),
        MyString::formatoNumero($total_saldo, 2, '$', false)), true);


    $pdf->Output('cuentas_proveedor.pdf', 'I');
  }
  public function cuenta2ClienteExcel(&$xls=null, $close=true){
    $res = $this->getCuentaClienteData();

    if (count($res['anterior']) > 0)
      $res['anterior'] = $res['anterior'][0];

    $this->load->library('myexcel');
    if($xls == null)
      $xls = new myexcel();

    $worksheet =& $xls->workbook->addWorksheet();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));
    $xls->titulo1 = $empresa['info']->nombre_fiscal;

    $xls->titulo2 = (isset($res['proveedor']->nombre_fiscal)? 'Cuenta de '.$res['proveedor']->nombre_fiscal: '');
    $xls->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    $xls->titulo4 = ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por pagar');

    if(is_array($res['anterior'])){
      $res['anterior'] = new stdClass();
      $res['anterior']->cargo = 0;
      $res['anterior']->abono = 0;
      $res['anterior']->saldo = 0;
    }else{
      $res['anterior']->cargo = $res['anterior']->total;
      $res['anterior']->abono = $res['anterior']->abonos;
    }
    $res['anterior']->fecha = $res['anterior']->serie = $res['anterior']->folio = '';
    $res['anterior']->concepto = $res['anterior']->estado = $res['anterior']->fecha_vencimiento = '';
    $res['anterior']->proveedor = '';
    $res['anterior']->dias_transc = '';
    $res['anterior']->command_sum = false;

    array_unshift($res['cuentas'], $res['anterior']);

    $data_fac = $res['cuentas'];

    $row=0;
    //Header
    $xls->excelHead($worksheet, $row, 8, array(
        array($xls->titulo2, 'format_title2'),
        array($xls->titulo3, 'format_title3'),
        array($xls->titulo4, 'format_title3')
    ));

    $row +=3;
    $xls->excelContent($worksheet, $row, $data_fac, array(
        'head' => array('Fecha', 'Serie', 'Folio', 'Concepto', 'Proveedor', 'Cargo', 'Abono', 'Saldo', 'Estado', 'Fecha Vencimiento', 'Dias Trans.'),
        'conte' => array(
            array('name' => 'fecha', 'format' => 'format4', 'sum' => -1),
            array('name' => 'serie', 'format' => 'format4', 'sum' => -1),
            array('name' => 'folio', 'format' => 'format4', 'sum' => -1),
            array('name' => 'concepto', 'format' => 'format4', 'sum' => -1),
            array('name' => 'proveedor', 'format' => 'format4', 'sum' => -1),
            array('name' => 'cargo', 'format' => 'format4', 'sum' => 0),
            array('name' => 'abono', 'format' => 'format4', 'sum' => 0),
            array('name' => 'saldo', 'format' => 'format4', 'sum' => 0),
            array('name' => 'estado', 'format' => 'format4', 'sum' => -1),
            array('name' => 'fecha_vencimiento', 'format' => 'format4', 'sum' => -1),
            array('name' => 'dias_transc', 'format' => 'format4', 'sum' => -1))
    ));

    if($close){
      $xls->workbook->send('cuentaProveedor.xls');
      $xls->workbook->close();
    }
  }



  /**
   * DETALLE FACTURA
   * **************************
   * Obtiene los abonos de una factura o nota de venta
   * @return [type] [description]
   */
  public function getDetalleVentaFacturaData($id_factura=null, $tipo=null, $sin_fecha=false, $only_abono=false)
  {
    $_GET['id'] = $id_factura==null? $_GET['id']: $id_factura;
    $_GET['tipo'] = $tipo==null? $_GET['tipo']: $tipo;
    $id_factura_aux = $_GET['id'];
    $sql = '';

    //Filtros para buscar
    $ffecha1 = $this->input->get('ffecha1')==''||$sin_fecha? date("Y-m-").'01': $this->input->get('ffecha1');
    $ffecha2 = $this->input->get('ffecha2')==''||$sin_fecha? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha1 = $fecha2 = '';
    if($ffecha1 > $ffecha2){
      $fecha2 = $ffecha1;
      $fecha1 = $ffecha2;
    }else{
      $fecha2 = $ffecha2;
      $fecha1 = $ffecha1;
    }

    $sql = $sql2 = '';
    if($this->input->get('ftipo')=='pv'){
      $sql = " AND (Date('".$fecha2."'::timestamp with time zone)-Date(c.fecha)) > c.plazo_credito";
      $sql2 = 'WHERE saldo > 0';
    }

    $sql_nc = '';
    // if ($_GET['tipo'] == 'f')
    // {
    $data['info'] = $this->db->query(
      "SELECT id_factura AS id, DATE(fecha) as fecha, serie, folio, condicion_pago, status, total,
      plazo_credito, id_cliente, id_empresa, 'f' AS tipo, is_factura
      FROM facturacion
      WHERE id_factura={$_GET['id']}")->result();
    $sql = array('tabla' => 'facturacion_abonos',
      'where_field' => 'id_factura');
    $sql_nc = "UNION
    SELECT
      id_factura AS id_abono,
      fecha,
      total AS abono,
      ('Nota de credito ' || serie || folio) AS concepto,
      'nc' AS tipo, 1 AS facturado,
      '' AS url_comp_pago
    FROM facturacion
    WHERE status <> 'ca' AND status <> 'b' AND id_nc IS NOT NULL AND id_abono_factura IS NULL
    AND id_nc = {$_GET['id']}
    AND Date(fecha) <= '{$fecha2}' ";
    // }
    // else
    // {
    //  $data['info'] = $this->db->query(
    //                  "SELECT id_venta AS id, fecha, '' as serie, folio,
    //                    condicion_pago,
    //                    status, total,
    //                    plazo_credito, id_cliente, 'v' AS tipo
    //                  FROM facturacion_ventas_remision
    //                  WHERE id_venta={$_GET['id']}")->result();
    //  $sql = array('tabla' => 'facturacion_ventas_remision_abonos',
    //                'where_field' => 'id_venta');
    // }

      //Obtenemos los abonos de la factura o ticket
    $res = $this->db->query(
      "SELECT id_abono, Date(fecha) AS fecha, abono, concepto, tipo, facturado, url_comp_pago
      FROM
      (
        SELECT
          id_abono,
          fecha,
          total AS abono,
          concepto,
          'ab' AS tipo,
          (SELECT Count(id_factura) FROM facturacion WHERE id_abono_factura = {$sql['tabla']}.id_abono) AS facturado,
          url_comp_pago
        FROM {$sql['tabla']}
        WHERE {$sql['where_field']} = {$_GET['id']}
        AND Date(fecha) <= '{$fecha2}'
        {$sql_nc}
      ) AS tt
    ORDER BY fecha ASC
    ");

    //obtenemos la info del cliente
    $prov['info'] = '';
    if (isset($data['info'][0]->id_cliente) && !$only_abono)
    {
      $this->load->model('clientes_model');
      $prov = $this->clientes_model->getClienteInfo($data['info'][0]->id_cliente, true);
    }

    //obtenemos la info de la empresa
    $empresa['info'] = '';
    if (isset($data['info'][0]->id_empresa) && !$only_abono)
    {
      $this->load->model('empresas_model');
      $empresa = $this->empresas_model->getInfoEmpresa($data['info'][0]->id_empresa, true);
    }

    $response = array(
      'abonos'  => array(),
      'saldo'   => '',
      'cobro'   => $data['info'],
      'cliente' => $prov['info'],
      'empresa' => $empresa['info'],
      'fecha1'  => $fecha1
      );
    $abonos = 0;

    if($res->num_rows() > 0){
      $response['abonos'] = $res->result();

      foreach ($response['abonos'] as $key => $value) {
        $abonos += $value->abono;
      }
    }
    $response['saldo'] = $response['cobro'][0]->total - $abonos;

    $_GET['id'] = $id_factura_aux;

    return $response;
  }

  public function saldoFactura($id_factura)
  {
    $data = $this->db->query(
      "SELECT id_factura, serie, folio, id_cliente,
      nombre_fiscal, id_empresa, empresa, total,
      iva, abonos, saldo, tipo
      FROM saldos_facturas_remisiones
      WHERE id_factura={$id_factura}")->row();
    return $data;
  }


  /**
   * Abonos de facturas y ventas
   * ************************************
   */
  public function getCuentaPagoAdicional()
  {
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'PagoAdicional'")->row();
    return (isset($data->cuenta)? $data->cuenta : '42200400');
  }

  public function getCuentaPagoMenor()
  {
    $data = $this->db->query("SELECT * FROM cuentas_contpaq WHERE id_empresa = {$this->empresaId} AND tipo_cuenta = 'PagoMenor'")->row();
    return (isset($data->cuenta)? $data->cuenta : '00800000');
  }

  public function uploadComPago()
  {
    if (isset($_FILES['comprobante']) && $_FILES['comprobante']['tmp_name'] !== '')
    {
      $path_comp = 'media/ventas/com_pagos';

      $config_upload = array(
        'upload_path'     => APPPATH.$path_comp,
        'allowed_types'   => '*',
        'max_size'        => '2048',
        'encrypt_name'    => FALSE
      );

      $this->load->library('my_upload');
      $this->my_upload->initialize($config_upload);
      $this->my_upload->crearFolder(true);
      $data_doc = $this->my_upload->do_upload('comprobante');

      if (isset($data_doc[0]) && $data_doc[0] === false) {
        return $data_doc;
      } else {
        $path = explode('application/', $data_doc['full_path']);
        return APPPATH.$path[1];
      }
    }

    return '';
  }

  public function addAbonoMasivo()
  {
    $ids   = $_POST['ids']; //explode(',', substr($_GET['id'], 1));
    $tipos = $_POST['tipos']; //explode(',', substr($_GET['tipo'], 1));
    $total = 0; //$this->input->post('dmonto');
    $desc  = '';

    //Se registra el movimiento en la cuenta bancaria
    $this->load->model('banco_cuentas_model');
    $data_cuenta  = $this->banco_cuentas_model->getCuentaInfo($this->input->post('dcuenta'));
    $data_cuenta  = $data_cuenta['info'];
    $this->empresaId = $data_cuenta->id_empresa;
    $_GET['id']   = $ids[0];
    $_GET['tipo'] = $tipos[0];
    $inf_factura  = $this->cuentas_cobrar_model->getDetalleVentaFacturaData($_GET['id'], $_GET['tipo'], true);

    // Carga comprobante de pago
    $url_comp_pago = $this->uploadComPago();
    if (is_array($url_comp_pago)) {
      return $url_comp_pago;
    }

    //Registra deposito
    foreach ($_POST['ids'] as $key => $value)  //foreach ($ids as $key => $value)
    {
      $total += $_POST['montofv'][$key];
      $desc .= ' | '.$_POST['factura_desc'][$key].'=>'.MyString::formatoNumero($_POST['montofv'][$key], 2, '', false);
    }
    $desc = ' ('.substr($desc, 1).')';
    $resp = $this->banco_cuentas_model->addDeposito(array(
      'id_cuenta'   => $this->input->post('dcuenta'),
      'id_banco'    => $data_cuenta->id_banco,
      'fecha'       => $this->input->post('dfecha'),
      'numero_ref'  => $this->input->post('dreferencia'),
      'concepto'    => $this->input->post('dconcepto').$desc,
      'monto'       => $total,
      'tipo'        => 't',
      'entransito'  => 'f',
      'metodo_pago' => $this->input->post('fmetodo_pago'),
      'id_cliente'  => $inf_factura['cliente']->id_cliente,
      'a_nombre_de' => $inf_factura['cliente']->nombre_fiscal,
      ));

    $fecha_pago = $this->input->post('dfecha');
    foreach ($_POST['ids'] as $key => $value)  //foreach ($ids as $key => $value)
    {
      $_GET['id']   = $value;
      $_GET['tipo'] = $_POST['tipos'][$key];
      $data = array('fecha'        => $fecha_pago,
        'concepto'       => $this->input->post('dconcepto'),
        'total'          => $_POST['montofv'][$key], //$total,
        'id_cuenta'      => $this->input->post('dcuenta'),
        'ref_movimiento' => $this->input->post('dreferencia'),
        'saldar'         => $_POST['saldar'][$key],
        'url_comp_pago'  => $url_comp_pago
      );
      $resa = $this->addAbono($data, null, true);
      $total -= $resa['total'];

      //Registra el rastro de la factura o remision que se abono en bancos
      if(isset($resp['id_movimiento']))
      {
        if($_POST['tipos'][$key] == 'f') //factura
        $this->db->insert('banco_movimientos_facturas', array('id_movimiento' => $resp['id_movimiento'], 'id_abono_factura' => $resa['id_abono']));
        else //remision
        $this->db->insert('banco_movimientos_facturas', array('id_movimiento' => $resp['id_movimiento'], 'id_abono_factura' => $resa['id_abono']));
          // $this->db->insert('banco_movimientos_ventas_remision', array('id_movimiento' => $resp['id_movimiento'], 'id_abono_venta_remision' => $resa['id_abono']));
      }
    }
    $_POST['dfecha'] = $fecha_pago;

    $this->creaFacturaAbono($resp['id_movimiento']);

    $this->db->query("SELECT refreshallmaterializedviews();");

    return $resp;
  }

  public function addAbono($data=null, $id=null, $masivo=false)
  {
    $id = $id==null? $this->input->get('id') : $id; //id factura o nota de venta

    if ($this->input->get('tipo') == 'f') {
      $camps = array('id_factura', 'facturacion_abonos', 'facturacion', 'facturacion_abonos_id_abono_seq');
    }else{
      $camps = array('id_factura', 'facturacion_abonos', 'facturacion', 'facturacion_abonos_id_abono_seq');
      // $camps = array('id_venta', 'facturacion_ventas_remision_abonos', 'facturacion_ventas_remision');
    }

    if ($data == null) {
      // Carga comprobante de pago
      $url_comp_pago = $this->uploadComPago();
      if (is_array($url_comp_pago)) {
        return $url_comp_pago;
      }

      $data = array(
        'fecha'          => $this->input->post('dfecha'),
        'concepto'       => $this->input->post('dconcepto'),
        'total'          => $this->input->post('dmonto'),
        'id_cuenta'      => $this->input->post('dcuenta'),
        'ref_movimiento' => $this->input->post('dreferencia'),
        'saldar'         => 'no',
        'url_comp_pago'  => $url_comp_pago
      );
    }

    $pagada = false;
    $pago_mayor = 0;//valor cuando pagan de mas en una factura se carga a pagos adicionales
    $pago_saldar = 0;//valor cuando pagan de menos y aun asi saldan la cuenta
    $inf_factura = $this->cuentas_cobrar_model->getDetalleVentaFacturaData($id, $this->input->get('tipo'), true);
    if ($inf_factura['saldo'] <= $data['total']){ //se ajusta
      $pago_mayor = $data['total']-$inf_factura['saldo'];
      $data['total'] -= $pago_mayor;
      $pagada = true;
    }elseif($data['saldar'] == 'si'){
      $pago_saldar = $inf_factura['saldo']-$data['total'];
      $pagada = true;
    }

    //Se registra el movimiento en la cuenta bancaria
    if ($masivo == false)
    {
      $this->load->model('banco_cuentas_model');
      $data_cuenta  = $this->banco_cuentas_model->getCuentaInfo($data['id_cuenta']);
      $data_cuenta  = $data_cuenta['info'];
      $this->empresaId = $data_cuenta->id_empresa;

      $data['concepto'] .= ' ('.$inf_factura['cobro'][0]->serie.$inf_factura['cobro'][0]->folio.'=>'.MyString::formatoNumero($data['total'], 2, '', false).')';
      $resp = $this->banco_cuentas_model->addDeposito(array(
        'id_cuenta'   => $data['id_cuenta'],
        'id_banco'    => $data_cuenta->id_banco,
        'fecha'       => $data['fecha'],
        'numero_ref'  => $data['ref_movimiento'],
        'concepto'    => $data['concepto'],
        'monto'       => $data['total']+$pago_mayor,
        'tipo'        => 't',
        'entransito'  => 'f',
        'metodo_pago' => $this->input->post('fmetodo_pago'),
        'id_cliente'  => $inf_factura['cliente']->id_cliente,
        'a_nombre_de' => $inf_factura['cliente']->nombre_fiscal,
        ));
    }

    $data = array(
      $camps[0]        => $id,
      'fecha'          => $data['fecha'],
      'concepto'       => $data['concepto'],
      'total'          => $data['total']+$pago_saldar,
      'id_cuenta'      => $data['id_cuenta'],
      'ref_movimiento' => $data['ref_movimiento'],
      'url_comp_pago'  => $data['url_comp_pago']
    );
    //se inserta el abono
    $this->db->insert($camps[1], $data);
    $data['id_abono'] = $this->db->insert_id($camps[3]);

    // Bitacora
    $this->bitacora_model->_insert($camps[1], $data['id_abono'],
      array(':accion'    => 'un abono a la venta ',
        ':seccion' => 'cuentas por cobrar',
        ':folio'     => $inf_factura['cobro'][0]->serie.$inf_factura['cobro'][0]->folio.' por '.MyString::formatoNumero($data['total']),
        ':id_empresa' => $inf_factura['empresa']->id_empresa,
        ':empresa'   => 'de '.$inf_factura['cliente']->nombre_fiscal));

    //verifica si la factura se pago, se cambia el status
    if($pagada){
      $this->db->update($camps[2], array('status' => 'pa'), "{$camps[0]} = {$id}");
    }

    //Si se hiso un pago mayor se registra a la factura
    if ($pago_mayor > 0)
    {
      $data_mayor               = $data;
      $data_mayor['concepto']   = 'Pago adicional';
      $data_mayor['total']      = $pago_mayor;
      $data_mayor['cuenta_cpi'] = $this->getCuentaPagoAdicional();
      $data_mayor['tipo']       = 'm';
      unset($data_mayor['id_cuenta'], $data_mayor['ref_movimiento']);
      $this->db->insert($camps[1].'_otros', $data_mayor);
    }elseif($pago_saldar > 0){
      $data_mayor               = $data;
      $data_mayor['concepto']   = 'Pago menor';
      $data_mayor['total']      = $pago_saldar;
      $data_mayor['cuenta_cpi'] = $this->getCuentaPagoMenor();
      $data_mayor['tipo']       = 's';
      unset($data_mayor['id_cuenta'], $data_mayor['ref_movimiento']);
      $this->db->insert($camps[1].'_otros', $data_mayor);
    }

    if (!$masivo){ // Complemento de pago
      // Verifica si es pago probicionado y crea la factura
      $this->creaFacturaAbono($resp['id_movimiento']);
    }

    //Registra el rastro de la factura o remision que se abono en bancos (si no es masivo)
    if(isset($resp['id_movimiento']))
    {
      if($camps[0] == 'id_factura') //factura
      $this->db->insert('banco_movimientos_facturas', array('id_movimiento' => $resp['id_movimiento'], 'id_abono_factura' => $data['id_abono']));
      else //remision
      $this->db->insert('banco_movimientos_facturas', array('id_movimiento' => $resp['id_movimiento'], 'id_abono_factura' => $data['id_abono']));
        // $this->db->insert('banco_movimientos_ventas_remision', array('id_movimiento' => $resp['id_movimiento'], 'id_abono_venta_remision' => $data['id_abono']));
    }

    if($masivo)
      return $data;
    else{
      $this->db->query("SELECT refreshallmaterializedviews();");
      return $resp;
    }
  }

  public function removeAbono($id=null, $tipo=null, $ida=null)
  {
    $tipo = $tipo!=null? $tipo : $this->input->get('tipo');
    $ida  = $ida!=null? $ida : $_GET['ida'];
    $id   = $id!=null? $id : $_GET['id'];

    if($this->input->get('nc') == 'si')
    {
      $this->load->model('facturacion_model');
      $this->facturacion_model->cancelaFactura($ida);
    }else
    {
      if ($tipo == 'f') {
        $camps = array('id_abono', 'facturacion_abonos', 'facturacion', 'id_factura');
      }else{
        $camps = array('id_abono', 'facturacion_abonos', 'facturacion', 'id_factura');
        // $camps = array('id_abono', 'facturacion_ventas_remision_abonos', 'facturacion_ventas_remision', 'id_venta');
      }

      $info_abano = $this->db->query("SELECT * FROM facturacion_abonos WHERE {$camps[0]} = {$ida}")->row();

      $this->db->delete($camps[1], "{$camps[0]} = {$ida}");
      $this->db->delete($camps[1].'_otros', "{$camps[0]} = {$ida}"); //elimina los pagos adicionales

      // Bitacora
      $inf_factura = $this->getDetalleVentaFacturaData($id);
      $this->bitacora_model->_cancel('facturacion_abonos', $ida,
        array(':accion'     => 'un abono de la venta ', ':seccion' => 'cuentas por cobrar',
          ':folio'      => $inf_factura['cobro'][0]->serie.$inf_factura['cobro'][0]->folio.' por '.MyString::formatoNumero($info_abano->total),
          ':id_empresa' => $inf_factura['empresa']->id_empresa,
          ':empresa'    => 'de '.$inf_factura['cliente']->nombre_fiscal));
    }
    //Se cambia el estado de la factura
    $this->db->update($camps[2], array('status' => 'p'), "{$camps[3]} = {$id}");

    $this->db->query("SELECT refreshallmaterializedviews();");

    return true;
  }

  public function getAbonosData($movimientoId=null)
  {
    //paginacion
    $params = array(
      'result_items_per_page' => '60',
      'result_page'       => (isset($_GET['pag'])? $_GET['pag']: 0)
      );
    if($params['result_page'] % $params['result_items_per_page'] == 0)
      $params['result_page'] = ($params['result_page']/$params['result_items_per_page']);

    $response = array();
    $sql = $sql2 = '';

    if($movimientoId!=null)
      $sql .= " AND bmf.id_movimiento = {$movimientoId}";
    else{
      if (empty($_GET['ffecha1']) && empty($_GET['ffecha2'])){
        $_GET['ffecha1'] = $this->input->get('ffecha1')!=''? $_GET['ffecha1']: date("Y-m").'-01';
        $_GET['ffecha2'] = $this->input->get('ffecha2')!=''? $_GET['ffecha2']: date("Y-m-d");
      }
      if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2'])){
        $response['titulo3'] = "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
        $sql .= " AND Date(fa.fecha) BETWEEN '".$_GET['ffecha1']."' AND '".$_GET['ffecha2']."' ";
      }

      if ($this->input->get('did_empresa') != '')
        $sql .= " AND f.id_empresa = '".$_GET['did_empresa']."'";
    }


    $query = BDUtil::pagination(
      "SELECT
      bmf.id_movimiento, fa.ref_movimiento, fa.concepto, Sum(fa.total) AS total_abono,
      bc.cuenta_cpi, Sum(f.subtotal) AS subtotal, Sum(f.total) AS total, Sum(((fa.total*100/Coalesce(NULLIF(f.total, 0), 1))*f.importe_iva/100)) AS importe_iva,
      Sum(((fa.total*100/Coalesce(NULLIF(f.total, 0), 1))*f.retencion_iva/100)) AS retencion_iva, c.nombre_fiscal,
      c.cuenta_cpi AS cuenta_cpi_cliente, Date(fa.fecha) AS fecha, e.nombre_fiscal AS empresa, e.logo
      FROM facturacion AS f
      INNER JOIN facturacion_abonos AS fa ON fa.id_factura = f.id_factura
      INNER JOIN banco_cuentas AS bc ON bc.id_cuenta = fa.id_cuenta
      INNER JOIN clientes AS c ON c.id_cliente = f.id_cliente
      INNER JOIN empresas AS e ON e.id_empresa = f.id_empresa
      INNER JOIN banco_movimientos_facturas AS bmf ON bmf.id_abono_factura = fa.id_abono
      WHERE f.status <> 'ca' AND f.status <> 'b'
      {$sql}
      GROUP BY bmf.id_movimiento, fa.ref_movimiento, fa.concepto,
      bc.cuenta_cpi, c.nombre_fiscal, c.cuenta_cpi, Date(fa.fecha),
      e.nombre_fiscal, e.logo
      ORDER BY Date(fa.fecha) DESC
      ", $params, true);
    $res = $this->db->query($query['query']);

    $response = array(
      'abonos'         => array(),
      'facturas'       => array(),
      'total_rows'     => $query['total_rows'],
      'items_per_page' => $params['result_items_per_page'],
      'result_page'    => $params['result_page'],
      );

    if($res->num_rows() > 0)
    {
      $response['abonos'] = $res->result();
      $res->free_result();


      if($movimientoId!=null)
      {
        $res = $this->db->query(
          "SELECT
          fa.id_abono, f.serie, f.folio, fa.ref_movimiento, fa.concepto, fa.total, Date(fa.fecha) AS fecha
          FROM facturacion AS f
          INNER JOIN facturacion_abonos AS fa ON fa.id_factura = f.id_factura
          INNER JOIN banco_movimientos_facturas AS bmf ON bmf.id_abono_factura = fa.id_abono
          WHERE f.status <> 'ca' AND f.status <> 'b'
          {$sql}
          ORDER BY fa.id_abono ASC
          ");
        $response['facturas'] = $res->result();
        $res->free_result();
      }
    }

    return $response;
  }

  /**
   * Funcion que registra una factura del complemento de pago
   * @param  [type] $id_movimiento [description]
   * @return [type]           [description]
   */
  public function creaFacturaAbono($id_abono)
  {
    // $this->load->model('facturacion_model');
    // $data_abono = $this->db->query("SELECT * FROM facturacion_abonos WHERE id_abono = {$id_abono}")->row();

    // $data_val = $this->db->query("SELECT f.status, f.is_factura, Count(fa.id_abono) AS num_abonos
    //  FROM facturacion f LEFT JOIN facturacion_abonos fa ON f.id_factura = fa.id_factura
    //  WHERE f.id_factura = {$data_abono->id_factura} AND fa.id_abono <= {$id_abono}
    //  GROUP BY f.id_factura")->row();

    // // Valida que se auna factura y que sea en parcialidades
    // if ($data_val->is_factura == 't' && ($data_val->status == 'p' || $data_val->num_abonos > 1)) {
    //   $this->load->library('cfdi');
    //   $data_factura = $this->facturacion_model->getInfoFactura($data_abono->id_factura);

    //   $data_folio = $this->facturacion_model->getFolioSerie('AB', $data_factura['info']->empresa->id_empresa, "es_nota_credito = 'f'");

    //   // Obtiene el numero de certificado de la empresa predeterminada.
    //   $certificado = $this->cfdi->obtenNoCertificado($data_factura['info']->empresa->cer_org);

    //   $dirCliente = $dirCliente2 = '';
    //   $dirCliente .= $data_factura['info']->cliente->calle!=''? $data_factura['info']->cliente->calle: '';
    //   $dirCliente .= $data_factura['info']->cliente->no_exterior!=''? ' #'+$data_factura['info']->cliente->no_exterior: '';
    //   $dirCliente .= $data_factura['info']->cliente->no_interior!=''? '-'+$data_factura['info']->cliente->no_interior: '';
    //   $dirCliente .= $data_factura['info']->cliente->colonia!=''? ', '+$data_factura['info']->cliente->colonia: '';

    //   $dirCliente2 .= $data_factura['info']->cliente->municipio!=''? $data_factura['info']->cliente->municipio: '';
    //   $dirCliente2 .= $data_factura['info']->cliente->estado!=''? ', '+$data_factura['info']->cliente->estado: '';
    //   $dirCliente2 .= $data_factura['info']->cliente->cp!=''? ', CP: '+$data_factura['info']->cliente->cp: '';

    //   $dirEmpresa = [];
    //   if ($data_factura['info']->empresa->calle) array_push($dirEmpresa, $data_factura['info']->empresa->calle);
    //   if ($data_factura['info']->empresa->no_exterior) array_push($dirEmpresa, $data_factura['info']->empresa->no_exterior);
    //   if ($data_factura['info']->empresa->no_interior) array_push($dirEmpresa, $data_factura['info']->empresa->no_interior);
    //   if ($data_factura['info']->empresa->colonia) array_push($dirEmpresa, $data_factura['info']->empresa->colonia);
    //   if ($data_factura['info']->empresa->localidad) array_push($dirEmpresa, $data_factura['info']->empresa->localidad);
    //   if ($data_factura['info']->empresa->municipio) array_push($dirEmpresa, $data_factura['info']->empresa->municipio);
    //   if ($data_factura['info']->empresa->estado) array_push($dirEmpresa, $data_factura['info']->empresa->estado);
    //   if ($data_factura['info']->empresa->pais) array_push($dirEmpresa, $data_factura['info']->empresa->pais);
    //   if ($data_factura['info']->empresa->cp) array_push($dirEmpresa, $data_factura['info']->empresa->cp);
    //   $dirEmpresa = implode(' ', $dirEmpresa);

    //   $abonos = $this->db->query("SELECT Count(id_abono) AS num FROM facturacion_abonos WHERE id_factura = {$data_factura['info']->id_factura}")->row();
    //   $subtotal = $data_abono->total;
    //   $iva      = 0;
    //   if($data_val->num_abonos == 1)
    //   {
    //     $subtotal = $data_abono->total - $data_factura['info']->importe_iva;
    //     $iva      = $data_factura['info']->importe_iva;
    //   }
    //   $subtotal = number_format($subtotal, 3, '.', '');
    //   $iva      = number_format($iva, 3, '.', '');

    //   $_POST['id_abono_factura']          = $id_abono;
    //   $_POST['dempresa']                  = $data_factura['info']->empresa->nombre_fiscal;
    //   $_POST['did_empresa']               = $data_factura['info']->empresa->id_empresa;
    //   $_POST['dversion']                  = '3.2';
    //   $_POST['dcer_caduca']               = $data_factura['info']->empresa->cer_caduca;
    //   $_POST['dno_certificado']           = $certificado;
    //   $_POST['dserie']                    = $data_folio[0]->serie;
    //   $_POST['dfolio']                    = $data_folio[0]->folio;
    //   $_POST['dano_aprobacion']           = $data_folio[0]->ano_aprobacion;
    //   $_POST['dcliente']                  = $data_factura['info']->cliente->nombre_fiscal;
    //   $_POST['did_cliente']               = $data_factura['info']->cliente->id_cliente;
    //   $_POST['dcliente_rfc']              = $data_factura['info']->cliente->rfc;
    //   $_POST['dcliente_domici']           = $dirCliente;
    //   $_POST['dcliente_ciudad']           = $dirCliente2;
    //   $_POST['dobservaciones']            = '';
    //   $_POST['dfecha']                    = date("Y-m-d\TH:i");
    //   $_POST['dno_aprobacion']            = $data_folio[0]->no_aprobacion;
    //   $_POST['moneda']                    = $data_factura['info']->moneda;
    //   $_POST['tipoCambio']                = $data_factura['info']->tipo_cambio;
    //   $_POST['dtipo_comprobante']         = 'ingreso';
    //   $_POST['dforma_pago']               = 'Pago en parcialidades';
    //   $_POST['dforma_pago_parcialidad']   = 'Parcialidad '.$data_val->num_abonos.' de '.($data_val->num_abonos < $abonos->num? $abonos->num: ($data_val->status=='pa'? $data_val->num_abonos: $data_val->num_abonos+1));
    //   $_POST['dmetodo_pago']              = 'No aplica'; //$data_factura['info']->metodo_pago;
    //   $_POST['dmetodo_pago_digitos']      = 'No identificado';
    //   $_POST['dcondicion_pago']           = 'co';
    //   $_POST['dplazo_credito']            = 0;

    //   $this->clearPostFactura();
    //   $_POST['prod_ddescripcion'][]       = 'Abono a factura '.$data_factura['info']->serie.$data_factura['info']->folio;
    //   $_POST['prod_ddescripcion2'][]      = '';
    //   $_POST['no_identificacion'][]       = '';
    //   $_POST['prod_did_calidad'][]        = '';
    //   $_POST['prod_did_tamanio'][]        = '';
    //   $_POST['prod_did_prod'][]           = '';
    //   $_POST['pallets_id'][]              = '';
    //   $_POST['remisiones_id'][]           = '';
    //   $_POST['id_unidad_rendimiento'][]   = '';
    //   $_POST['id_size_rendimiento'][]     = '';
    //   $_POST['prod_dclase'][]             = '';
    //   $_POST['prod_dpeso'][]              = '';
    //   $_POST['prod_dmedida'][]            = 'NO APLICA';
    //   $_POST['prod_dmedida_id'][]         = '17';
    //   $_POST['prod_dcantidad'][]          = '1';
    //   $_POST['prod_dcajas'][]             = '0';
    //   $_POST['prod_dkilos'][]             = '0';
    //   $_POST['prod_dpreciou'][]           = $subtotal;
    //   $_POST['prod_diva_porcent'][]       = $iva>0? 16: 0;
    //   $_POST['prod_diva_total'][]         = $iva;
    //   $_POST['dreten_iva']                = 0;
    //   $_POST['prod_dreten_iva_total'][]   = 0;
    //   $_POST['prod_dreten_iva_porcent'][] = 0;
    //   $_POST['prod_importe'][]            = $subtotal;
    //   $_POST['isCert'][]                  = '0';
    //   $_POST['dttotal_letra']             = strtoupper(MyString::num2letras($subtotal+$iva, $data_factura['info']->moneda));
    //   $_POST['total_importe']             = $subtotal;
    //   $_POST['total_descuento']           = 0;
    //   $_POST['total_subtotal']            = $subtotal;
    //   $_POST['total_iva']                 = $iva;
    //   $_POST['total_retiva']              = 0;
    //   $_POST['total_totfac']              = $subtotal+$iva;
    //   $_POST['diva']                      = 0;

    //   $_POST['remitente_nombre']          = $data_factura['info']->empresa->nombre_fiscal;
    //   $_POST['remitente_rfc']             = $data_factura['info']->empresa->rfc;
    //   $_POST['remitente_domicilio']       = $dirEmpresa;
    //   $_POST['remitente_chofer']          = '';
    //   $_POST['remitente_marca']           = '';
    //   $_POST['remitente_modelo']          = '';
    //   $_POST['remitente_placas']          = '';
    //   $_POST['destinatario_nombre']       = $data_factura['info']->cliente->nombre_fiscal;
    //   $_POST['destinatario_rfc']          = $data_factura['info']->cliente->rfc;
    //   $_POST['destinatario_domicilio']    = $dirCliente.' '.$dirCliente2;
    //   $_POST['pproveedor_seguro']         = '';
    //   $_POST['seg_id_proveedor']          = '';
    //   $_POST['seg_poliza']                = '';
    //   $_POST['pproveedor_certificado51']  = '';
    //   $_POST['cert_id_proveedor51']       = '';
    //   $_POST['cert_certificado51']        = '';
    //   $_POST['cert_bultos51']             = '';
    //   $_POST['pproveedor_certificado52']  = '';
    //   $_POST['cert_id_proveedor52']       = '';
    //   $_POST['cert_certificado52']        = '';
    //   $_POST['cert_bultos52']             = '';
    //   $_POST['pproveedor_supcarga']       = '';
    //   $_POST['supcarga_id_proveedor']     = '';
    //   $_POST['supcarga_numero']           = '';
    //   $_POST['supcarga_bultos']           = '';
    //   $_POST['new_orden_flete']           = '0';

    //   $result = $this->facturacion_model->addFactura();
    //   return $result;
    // }

  }

  public function clearPostFactura()
  {
    unset($_POST['prod_ddescripcion']);
    unset($_POST['prod_did_prod']);
    unset($_POST['pallets_id']);
    unset($_POST['remisiones_id']);
    unset($_POST['id_unidad_rendimiento']);
    unset($_POST['id_size_rendimiento']);
    unset($_POST['prod_dclase']);
    unset($_POST['prod_dpeso']);
    unset($_POST['prod_dmedida']);
    unset($_POST['prod_dmedida_id']);
    unset($_POST['prod_dcantidad']);
    unset($_POST['prod_dcajas']);
    unset($_POST['prod_dkilos']);
    unset($_POST['prod_dpreciou']);
    unset($_POST['prod_diva_porcent']);
    unset($_POST['prod_diva_total']);
    unset($_POST['dreten_iva']);
    unset($_POST['prod_dreten_iva_total']);
    unset($_POST['prod_dreten_iva_porcent']);
    unset($_POST['prod_importe']);
    unset($_POST['isCert']);
  }

  /**
    * Visualiza/Descarga el PDF del abono.
    *
    * @return void
    */
  public function imprimir_abono($movimientoId, $path = null)
  {
    $orden = $this->getAbonosData($movimientoId);

    $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
    $pdf = new MYpdf();
    $pdf->show_head = false;
    $pdf->titulo1 = $orden['abonos'][0]->empresa;
    $pdf->titulo2 = 'Cliente: ' . $orden['abonos'][0]->nombre_fiscal;
    $pdf->titulo3 = 'RECIBO DE PAGO';

    $pdf->logo = $orden['abonos'][0]->logo!=''? (file_exists($orden['abonos'][0]->logo)? $orden['abonos'][0]->logo: '') : '';

    $pdf->AliasNbPages();
    $pdf->AddPage();


    $y = $pdf->GetY();

    $pdf->SetFont('helvetica','B', 13);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetXY(80, $y);
    $pdf->MultiCell(60,4, 'RECIBO DE PAGO');
      // $pdf->SetY($pdf->GetY()+1);
      // $pdf->MultiCell(60,4, 'Recibi de '.$orden['abonos'][0]->nombre_fiscal);
      // $pdf->MultiCell(60,4, 'La cantidad de '.MyString::formatoNumero($orden['abonos'][0]->total_abono, 2, '$', false).
      //      ' ('.MyString::num2letras($orden['abonos'][0]->total_abono).')');
      // $pdf->MultiCell(60,4, 'A cuenta de: '.implode('-', $facturas_txt));

    $pdf->SetFont('helvetica','B', 10);
    $pdf->SetXY(10, $pdf->GetY()+6);
    $pdf->MultiCell(115,4, "FECHA: ".MyString::fechaATexto($orden['abonos'][0]->fecha));
    $pdf->SetXY(10, $pdf->GetY()+1);
    $pdf->MultiCell(115,4, 'Recibi de '.$orden['abonos'][0]->nombre_fiscal);
    $pdf->SetX(10);
    $pdf->MultiCell(115,4, 'La cantidad de '.MyString::formatoNumero($orden['abonos'][0]->total_abono, 2, '$', false).
      ' ('.MyString::num2letras($orden['abonos'][0]->total_abono).')');
    $pdf->SetX(10);
    $pdf->MultiCell(115,4, 'A orden de: '.$orden['abonos'][0]->empresa);
    $pdf->SetX(10);
    $pdf->MultiCell(115,4, 'Forma de pago: '.$orden['abonos'][0]->ref_movimiento);

    $pdf->Text(60, 53, 'Firma ____________________________________');


    $pdf->SetXY(140, $y+6);
    $pdf->MultiCell(115,4, 'A cuenta de: ');

    $aligns = array('C', 'R');
    $widths = array(25, 40);
    $header = array('FOLIO', 'TOTAL');

    $pdf->SetFont('Arial','B',8);
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFillColor(255,255,255);
    $pdf->SetXY(140, $pdf->GetY());
    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->Row($header, true, false);
    $pdf->Line(140, $pdf->GetY()-1, 205, $pdf->GetY()-1);
    foreach ($orden['facturas'] as $key => $prod)
    {
      $pdf->SetFont('Arial','',8);
      $pdf->SetTextColor(0,0,0);
      $datos = array(
        $prod->serie.$prod->folio,
        MyString::formatoNumero($prod->total, 2, '$', false),
        );
      $pdf->SetXY(140, $pdf->GetY()-2);
      $pdf->Row($datos, false, false);
    }

    if ($path)
    {
      $file = $path.'recibo_pago.pdf';
      $pdf->Output($file, 'F');
      return $file;
    }
    else
    {
      $pdf->Output('recibo_pago.pdf', 'I');
    }
  }

  /**
  *  ESTADO DE CUENTAS
  * ***************************************
  * Saldo de un cliente seleccionado
  * @return [type] [description]
  */
  public function getEstadoCuentaData($sql_clientes='', $all_clientes=false, $all_facturas=false, $sqlext=array('',''))
  {
    $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha1 = $fecha2 = '';
    if($_GET['ffecha1'] > $_GET['ffecha2']){
      $fecha2 = $_GET['ffecha1'];
      $fecha1 = $_GET['ffecha2'];
    }else{
      $fecha2 = $_GET['ffecha2'];
      $fecha1 = $_GET['ffecha1'];
    }

    $sql = $sqlt = $sql2 = '';
    if($this->input->get('ftipo')=='pv'){
      $sql = " AND (Date('".$fecha2."'::timestamp with time zone)-Date(f.fecha)) > f.plazo_credito";
      $sqlt = " AND (Date('".$fecha2."'::timestamp with time zone)-Date(f.fecha)) > f.plazo_credito";
      $sql2 = 'WHERE saldo > 0';
    }elseif($this->input->get('ftipo')=='to'){
      $all_clientes = true;
      $all_facturas = true;
      // if($this->input->get('fid_cliente') != '')
      //   $sql_clientes .= " AND id_cliente = ".$this->input->get('fid_cliente');
    }

    if($this->input->get('ftipodoc') != ''){
      $sql_clientes .= " AND is_factura = '".($this->input->get('ftipodoc') === 'f' ? 't' : 'f')."'";
    }

    if($this->input->get('did_empresa') != ''){
      $sql .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
      $sqlt .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
      $sql_clientes .= " AND id_empresa = ".$this->input->get('did_empresa');
    }

    if($this->input->get('fid_cliente') != ''){
      $sql .= " AND f.id_cliente = '".$this->input->get('fid_cliente')."'";
      $sqlt .= " AND f.id_cliente = '".$this->input->get('fid_cliente')."'";
      $sql_clientes .= " AND id_cliente = ".$this->input->get('fid_cliente');
    }

    if($this->input->get('fserie') != ''){
      $sql_clientes .= " AND serie = '".$this->input->get('fserie')."'";
    }

    $clientes = $this->db->query("SELECT id_cliente, nombre_fiscal, cuenta_cpi, dias_credito, id_factura,
        id_empresa, fecha, serie, folio, concepto, subtotal, importe_iva, total, total_cambio, tipo_cambio,
        is_factura, fecha_vencimiento, a_id_abono, a_serie, a_folio, a_fecha, a_concepto, a_abono
      FROM estado_cuenta
      WHERE Date(fecha) <= '{$fecha2}'  {$sql_clientes}
      ORDER BY nombre_fiscal ASC, id_factura ASC, a_id_abono ASC ");
    $response = array();
    $aux_cliente = 0;
    $aux_factura = 0;
    $cliente = null;
    foreach ($clientes->result() as $keyc => $cliente1)
    {
      if ($aux_cliente != $cliente1->id_cliente) {
        if( isset($cliente->saldo) && ($cliente->saldo > 0 || $all_clientes))
        {
          if($all_clientes && $all_facturas)
            $response[] = $cliente;
          elseif($cliente->saldo > 0 && $all_clientes == false)
            $response[] = $cliente;
          elseif($all_clientes && count($cliente->facturas) > 0)
            $response[] = $cliente;
        }

        $cliente                = new stdClass;
        $cliente->id_cliente    = $cliente1->id_cliente;
        $cliente->nombre_fiscal = $cliente1->nombre_fiscal;
        $cliente->cuenta_cpi    = $cliente1->cuenta_cpi;
        $cliente->dias_credito  = $cliente1->dias_credito;
        $cliente->saldo         = 0;
        $cliente->saldo         = 0;
        $cliente->saldo_cambio  = 0;
        $cliente->facturas      = [];

        $aux_cliente = $cliente1->id_cliente;
        $aux_factura = 0;
      }

      if ($aux_factura != $cliente1->id_factura) {
        if(count($cliente->facturas) > 0 && $all_facturas == false && MyString::float($cliente->facturas[$aux_factura]->saldo) <= 0)
          unset($cliente->facturas[$aux_factura]);

        $cliente->facturas[$cliente1->id_factura]                    = new stdClass;
        $cliente->facturas[$cliente1->id_factura]->id_factura        = $cliente1->id_factura;
        $cliente->facturas[$cliente1->id_factura]->fecha             = $cliente1->fecha;
        $cliente->facturas[$cliente1->id_factura]->serie             = $cliente1->serie;
        $cliente->facturas[$cliente1->id_factura]->folio             = $cliente1->folio;
        $cliente->facturas[$cliente1->id_factura]->concepto          = $cliente1->concepto;
        $cliente->facturas[$cliente1->id_factura]->subtotal          = $cliente1->subtotal;
        $cliente->facturas[$cliente1->id_factura]->importe_iva       = $cliente1->importe_iva;
        $cliente->facturas[$cliente1->id_factura]->total             = $cliente1->total;
        $cliente->facturas[$cliente1->id_factura]->total_cambio      = $cliente1->total_cambio;
        $cliente->facturas[$cliente1->id_factura]->tipo_cambio       = $cliente1->tipo_cambio;
        $cliente->facturas[$cliente1->id_factura]->is_factura        = $cliente1->is_factura;
        $cliente->facturas[$cliente1->id_factura]->fecha_vencimiento = $cliente1->fecha_vencimiento;
        $cliente->facturas[$cliente1->id_factura]->abonos            = [];
        $cliente->facturas[$cliente1->id_factura]->abonos_total      = 0;
        $cliente->facturas[$cliente1->id_factura]->saldo             = $cliente1->total;
        $cliente->facturas[$cliente1->id_factura]->saldo_cambio      = $cliente1->total_cambio;

        $cliente->saldo                                              += $cliente1->total;
        $cliente->saldo_cambio                                       += $cliente1->total_cambio;

        $aux_factura = $cliente1->id_factura;
      }

      if ($aux_factura == $cliente1->id_factura && $cliente1->a_id_abono > 0) {
        if ($cliente1->a_fecha <= $fecha2) {
          $aabono = new stdClass;
          $aabono->id_abono = $cliente1->a_id_abono;
          $aabono->serie    = $cliente1->a_serie;
          $aabono->folio    = $cliente1->a_folio;
          $aabono->fecha    = $cliente1->a_fecha;
          $aabono->concepto = str_replace('()', '', $cliente1->a_concepto);
          $aabono->abono    = $cliente1->a_abono;
          $cliente->facturas[$cliente1->id_factura]->abonos[] = $aabono;
          // echo "<pre>";
          //   var_dump($cliente->facturas[$cliente1->id_factura]);
          // echo "</pre>";

          $cliente->facturas[$cliente1->id_factura]->abonos_total += $cliente1->a_abono;

          $cliente->saldo                                               -= $cliente1->a_abono;
          $cliente->saldo_cambio                                        -= $cliente1->a_abono/$cliente1->tipo_cambio;
          $cliente->facturas[$cliente1->id_factura]->saldo        -= $cliente1->a_abono;
          $cliente->facturas[$cliente1->id_factura]->saldo_cambio -= $cliente1->a_abono/$cliente1->tipo_cambio;
        }
      }
    }

    if( $cliente->saldo > 0 || $all_clientes)
    {
      if($all_clientes && $all_facturas)
        $response[] = $cliente;
      elseif($cliente->saldo > 0 && $all_clientes == false)
        $response[] = $cliente;
      elseif($all_clientes && count($cliente->facturas) > 0)
        $response[] = $cliente;
    }

    // echo "<pre>";
    //   var_dump($response);
    // echo "</pre>";exit;
    return $response;
  }

  /**
   * Descarga el listado de cuentas por pagar en formato pdf
   */
  public function estadoCuentaPdf(){
    $res = $this->getEstadoCuentaData();
    // var_dump($res);

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = 'ESTADO DE CUENTA DE CLIENTES';
    $pdf->titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2')."\n";
    // $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
    $pdf->AliasNbPages();
    // $pdf->AddPage();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'R', 'L', 'R', 'R', 'R', 'L');
    $widths = array(28, 11, 20, 50, 23, 23, 23, 23);
    $header = array('Fecha', 'Serie', 'Folio', 'Concepto', 'Cargos', 'Abonos', 'Saldo', 'F. Ven.');

    $total_saldo_cliente = 0;
    $totalVencido = 0;
    foreach($res as $key => $item){
      if (($item->saldo > 0 && $this->input->get('fcon_saldo')=='si') || $this->input->get('fcon_saldo')!='si')
      {
        $total_cargo = 0;
        $total_abono = 0;
        $total_saldo = 0;
        $total_saldo_cambio = 0;
        $totalVencido = 0;

        if (isset($item->saldo_anterior_vencido->saldo))
          $totalVencido += $item->saldo_anterior_vencido->saldo;

        if($pdf->GetY() >= $pdf->limiteY || $key==0){ //salta de pagina si exede el max
          $pdf->AddPage();

          $pdf->SetFont('Arial','B',8);
          $pdf->SetTextColor(255,255,255);
          $pdf->SetFillColor(160,160,160);
          $pdf->SetX(6);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($header, true);
        }

        $pdf->SetFont('Arial','B',8);
        $pdf->SetTextColor(0,0,0);

        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L'));
        $pdf->SetWidths(array(20, 170));
        $pdf->Row(array('CLIENTE:', $item->cuenta_cpi), false, false);
        $pdf->SetXY(6, $pdf->GetY()-2);
        $pdf->Row(array('NOMBRE:', $item->nombre_fiscal), false, false);

        $pdf->SetXY(6, $pdf->GetY()+3);

        $pdf->SetFont('Arial','',8);
        //Saldo anterior
        if(isset($item->saldo_anterior->saldo) ){
          $datos = array('', '', '',
            'Saldo Inicial',
            MyString::formatoNumero($item->saldo_anterior->saldo, 2, '', false),
            MyString::formatoNumero($item->saldo_anterior->saldo_cambio, 2, '', false),
            '', '',
            );
          $pdf->SetXY(6, $pdf->GetY()-2);
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($datos, false, false);
        }
        //Facturas del cliente
        foreach ($item->facturas as $keyf => $factura)
        {
          $total_cargo        += $factura->total;
          $total_saldo        += $factura->saldo;
          $total_saldo_cambio += $factura->saldo_cambio;

          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $datos = array(MyString::fechaATexto($factura->fecha, '/c'),
            $factura->serie,
            $factura->folio,
            $factura->concepto,
            MyString::formatoNumero($factura->total, 2, '', false),
            '',
            MyString::formatoNumero( ($factura->saldo) , 2, '', false),
            MyString::fechaATexto($factura->fecha_vencimiento, '/c'),
            );
          //si esta vencido
          if (strtotime($this->input->get('ffecha2')) > strtotime($factura->fecha_vencimiento))
          {
            $totalVencido += $factura->saldo;
            if(MyString::formatoNumero( ($factura->saldo) , 2, '', false) != '0.00')
              $pdf->SetFillColor(255,255,204);
            else
              $pdf->SetFillColor(255,255,255);
          }else
          $pdf->SetFillColor(255,255,255);

          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns($aligns);
          $pdf->SetWidths($widths);
          $pdf->Row($datos, true, true);

          foreach ($factura->abonos as $keya => $abono)
          {
            if($pdf->GetY() >= $pdf->limiteY)
              $pdf->AddPage();

            $total_abono += $abono->abono;
            $datos = array('   '.MyString::fechaATexto($abono->fecha, '/c'),
              $abono->serie,
              $abono->folio,
              $abono->concepto,
              '',
              '('.MyString::formatoNumero($abono->abono, 2, '', false).')',
              '', '',
              );

            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns($aligns);
            $pdf->SetWidths($widths);
            $pdf->Row($datos, false, true);
          }

        }

        $pdf->SetX(115);
        $pdf->SetFont('Arial','B',8);
        // $pdf->SetTextColor(255,255,255);
        $pdf->SetAligns(array('R', 'R', 'R', 'R'));
        $pdf->SetWidths(array(23, 23, 23));
        $pdf->Row(array(
          MyString::formatoNumero($total_cargo, 2, '', false),
          MyString::formatoNumero($total_abono, 2, '', false),
          MyString::formatoNumero($total_saldo, 2, '', false)), false);

        $saldo_cliente = ((isset($item->saldo_anterior->saldo)? $item->saldo_anterior->saldo: 0) + $total_cargo - $total_abono);
        $saldo_cliente_cambio = (isset($item->saldo_anterior->saldo)? $item->saldo_anterior->saldo_cambio: 0) + $total_saldo_cambio;
        $saldo_cliente_cambio = $saldo_cliente == $saldo_cliente_cambio? 0: $saldo_cliente_cambio;
        $pdf->SetAligns(array('R', 'R', 'R', 'R'));
        $pdf->SetWidths(array(50, 23, 23, 23));
        $pdf->SetX(65);
        $pdf->Row(array('Saldo Inicial', MyString::formatoNumero( (isset($item->saldo_anterior->saldo)? $item->saldo_anterior->saldo: 0) , 2, '', false), 'Vencido', MyString::formatoNumero($totalVencido, 2, '', false)), false);

        $pdf->SetX(65);
        $pdf->Row(array('(+) Cargos', MyString::formatoNumero($total_cargo, 2, '', false), 'Credito', $item->dias_credito.' Dias'), false);
        $pdf->SetX(65);
        $pdf->Row(array('(-) Abonos', MyString::formatoNumero($total_abono, 2, '', false)), false);
        $pdf->SetX(65);
        $pdf->Row(array('(=) Saldo Final', MyString::formatoNumero( $saldo_cliente , 2, '', false), MyString::formatoNumero( $saldo_cliente_cambio , 2, '', false)), false);

        $total_saldo_cliente += $saldo_cliente;
      }
    }

    $pdf->SetXY(65, $pdf->GetY()+4);
    $pdf->Row(array('TOTAL SALDO DE CLIENTES', MyString::formatoNumero( $total_saldo_cliente , 2, '', false)), false);


    $pdf->Output('estado_cuenta.pdf', 'I');
  }

  public function estadoCuentaXls()
  {
    header('Content-type: application/vnd.ms-excel');
    header("Content-Disposition: attachment; filename=estado_cuenta.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getEstadoCuentaData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "ESTADO DE CUENTA DE CLIENTES";
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2');

    $html = '<table>
    <tbody>
    <tr>
    <td colspan="8" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
    </tr>
    <tr>
    <td colspan="8" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
    </tr>
    <tr>
    <td colspan="8" style="text-align:center;">'.$titulo3.'</td>
    </tr>
    <tr style="font-weight:bold">
    <td style="width:80px;border:1px solid #000;background-color: #cccccc;">Fecha</td>
    <td style="width:60px;border:1px solid #000;background-color: #cccccc;">Serie</td>
    <td style="width:60px;border:1px solid #000;background-color: #cccccc;">Folio</td>
    <td style="width:200px;border:1px solid #000;background-color: #cccccc;">Concepto</td>
    <td style="width:80px;border:1px solid #000;background-color: #cccccc;">Cargos</td>
    <td style="width:80px;border:1px solid #000;background-color: #cccccc;">Abonos</td>
    <td style="width:80px;border:1px solid #000;background-color: #cccccc;">Saldo</td>
    <td style="width:80px;border:1px solid #000;background-color: #cccccc;">F. Ven.</td>
    </tr>';
    $total_saldo_cliente = 0;
    foreach ($res as $key => $value)
    {
      if (($value->saldo > 0 && $this->input->get('fcon_saldo')=='si') || $this->input->get('fcon_saldo')!='si')
      {
        $total_cargo = $total_abono = $total_saldo = $totalVencido = 0;

        if (isset($value->saldo_anterior_vencido->saldo))
          $totalVencido += $value->saldo_anterior_vencido->saldo;

        $html .= '
        <tr style="font-weight:bold;">
        <td>CLIENTE:</td>
        <td colspan="7" style="text-align:left;">'.$value->cuenta_cpi.'</td>
        </tr>
        <tr style="font-weight:bold;">
        <td>NOMBRE:</td>
        <td colspan="7">'.$value->nombre_fiscal.'</td>
        </tr>';

        if( isset($value->saldo_anterior->saldo) )
          $html .= '
        <tr>
        <td colspan="3"></td>
        <td>Saldo Inicial</td>
        <td style="mso-number-format:\'0.00\';">'.MyString::float($value->saldo_anterior->saldo).'</td>
        <td colspan="3"></td>
        </tr>';

        foreach ($value->facturas as $keyf => $factura)
        {
          $total_cargo += $factura->total;
          $total_saldo += $factura->saldo;

          //si esta vencido
          $color = '255,255,255';
          if (strtotime($this->input->get('ffecha2')) > strtotime($factura->fecha_vencimiento))
          {
            $totalVencido += $factura->saldo;
            if(MyString::formatoNumero( ($factura->saldo) , 2, '', false) != '0.00')
              $color = '255,255,204';
          }

          $html .= '
          <tr>
          <td style="border:0px solid #000;background-color: rgb('.$color.');text-align:left;">'.MyString::fechaATexto($factura->fecha, '/c').'</td>
          <td style="border:0px solid #000;background-color: rgb('.$color.');">'.$factura->serie.'</td>
          <td style="border:0px solid #000;background-color: rgb('.$color.');">'.$factura->folio.'</td>
          <td style="border:0px solid #000;background-color: rgb('.$color.');">'.$factura->concepto.'</td>
          <td style="border:0px solid #000;background-color: rgb('.$color.');mso-number-format:\'0.00\';">'.MyString::float($factura->total).'</td>
          <td style="border:0px solid #000;background-color: rgb('.$color.');"></td>
          <td style="border:0px solid #000;background-color: rgb('.$color.');mso-number-format:\'0.00\';">'.MyString::float($factura->saldo).'</td>
          <td style="border:0px solid #000;background-color: rgb('.$color.');">'.MyString::fechaATexto($factura->fecha_vencimiento, '/c').'</td>
          </tr>';

          foreach ($factura->abonos as $keya => $abono)
          {
            $total_abono += $abono->abono;

            $html .= '
            <tr>
            <td>'.MyString::fechaATexto($abono->fecha, '/c').'</td>
            <td>'.$abono->serie.'</td>
            <td>'.$abono->folio.'</td>
            <td>'.$abono->concepto.'</td>
            <td></td>
            <td>'.$abono->abono.'</td>
            <td></td>
            <td></td>
            </tr>';
          }
        }

        $saldo_cliente = ((isset($value->saldo_anterior->saldo)? $value->saldo_anterior->saldo: 0) + $total_cargo - $total_abono);
        $total_saldo_cliente += $saldo_cliente;
        $html .= '<tr style="font-weight:bold">
        <td colspan="4"></td>
        <td style="border:0px solid #000;mso-number-format:\'0.00\';">'.MyString::float($total_cargo).'</td>
        <td style="border:0px solid #000;mso-number-format:\'0.00\';">'.MyString::float($total_abono).'</td>
        <td style="border:0px solid #000;mso-number-format:\'0.00\';">'.MyString::float($total_saldo).'</td>
        <td></td>
        </tr>
        <tr style="font-weight:bold">
        <td colspan="3"></td>
        <td style="border:0px solid #000;text-align:right;">Saldo Inicial</td>
        <td style="border:0px solid #000;mso-number-format:\'0.00\';">'.MyString::float((isset($value->saldo_anterior->saldo)? $value->saldo_anterior->saldo: 0)).'</td>
        <td style="border:0px solid #000;background-color: rgb(255,255,204);">Vencido</td>
        <td style="border:0px solid #000;background-color: rgb(255,255,204);mso-number-format:\'0.00\';">'.MyString::float($totalVencido).'</td>
        <td></td>
        </tr>
        <tr style="font-weight:bold">
        <td colspan="3"></td>
        <td style="border:0px solid #000;text-align:right;">(+) Cargos</td>
        <td style="border:0px solid #000;mso-number-format:\'0.00\';">'.MyString::float($total_cargo).'</td>
        <td style="border:0px solid #000;background-color: rgb(255,255,204);">Credito</td>
        <td style="border:0px solid #000;background-color: rgb(255,255,204);">'.$value->dias_credito.'</td>
        <td></td>
        </tr>
        <tr style="font-weight:bold">
        <td colspan="3"></td>
        <td style="border:0px solid #000;text-align:right;">(-) Abonos</td>
        <td style="border:0px solid #000;mso-number-format:\'0.00\';">'.MyString::float($total_abono).'</td>
        <td colspan="3"></td>
        </tr>
        <tr style="font-weight:bold">
        <td colspan="3"></td>
        <td style="border:0px solid #000;text-align:right;">(=) Saldo Final</td>
        <td style="border:0px solid #000;mso-number-format:\'0.00\';">'.MyString::float($saldo_cliente).'</td>
        <td colspan="3"></td>
        </tr>
        <tr>
        <td colspan="8"></td>
        </tr>';
      }
    }

    $html .= '
    <tr style="font-weight:bold">
    <td colspan="3"></td>
    <td style="border:0px solid #000;">TOTAL SALDO DE CLIENTES</td>
    <td style="border:0px solid #000;mso-number-format:\'0.00\';">'.MyString::float($total_saldo_cliente).'</td>
    <td colspan="3"></td>
    </tr>
    </tbody>
    </table>';

    echo $html;
  }


  public function getRptventasData($order_by='fa.fecha ASC, f.folio ASC')
  {
    $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha1 = $fecha2 = '';
    if($_GET['ffecha1'] > $_GET['ffecha2']){
      $fecha2 = $_GET['ffecha1'];
      $fecha1 = $_GET['ffecha2'];
    }else{
      $fecha2 = $_GET['ffecha2'];
      $fecha1 = $_GET['ffecha1'];
    }

    $sql = $sqlt = $sql2 = '';
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND f.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    // $sql .= " AND f.status = 'pa'";

    $response = array();
    $response = $this->db->query(
      "SELECT
      f.id_factura, f.serie, f.folio, p.rfc, p.nombre_fiscal, f.subtotal, f.importe_iva AS fimporte_iva, f.total, Date(fa.fecha) AS fecha_pago,
      fa.total AS total_abono, ((fa.total*100/f.total)*f.importe_iva/100) AS importe_iva,
      (SELECT Count(*) FROM facturacion_abonos WHERE id_abono <= fa.id_abono AND id_factura = f.id_factura) AS num
      FROM facturacion AS f
      INNER JOIN facturacion_abonos AS fa ON fa.id_factura = f.id_factura
      INNER JOIN clientes AS p ON p.id_cliente = f.id_cliente
      INNER JOIN banco_movimientos_facturas AS bmf ON bmf.id_abono_factura = fa.id_abono
      WHERE f.status <> 'ca' AND f.status <> 'b' AND fa.poliza_ingreso = 'f' AND f.id_abono_factura IS NULL
      AND (Date(fa.fecha) >= '{$fecha1}' AND Date(fa.fecha) <= '{$fecha2}')
      {$sql} AND ((f.fecha < '2014-01-01' AND f.is_factura = 'f') OR (f.is_factura = 't') )
      ORDER BY {$order_by}")->result();
    foreach ($response as $keyi => $facid)
    {
      $facid->importe_iva = 0;
      if($facid->num == 1){
        $facid->importe_iva = $facid->fimporte_iva;
        // $facid->importe_retencion += $infodac->retencion_iva;
      }
    }

    return $response;
  }

  public function rptVentasXls()
  {
    header('Content-type: application/vnd.ms-excel');
    header("Content-Disposition: attachment; filename=compras.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getRptventasData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "RELACION A DETALLE DE FACTURAS COBRADAS";
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2');

    $html = '<table>
    <tbody>
    <tr>
    <td colspan="5" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
    </tr>
    <tr>
    <td colspan="5" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
    </tr>
    <tr>
    <td colspan="5" style="text-align:center;">'.$titulo3.'</td>
    </tr>
    <tr style="font-weight:bold">
    <td style="width:150px;border:1px solid #000;background-color: #cccccc;">SERIE</td>
    <td style="width:150px;border:1px solid #000;background-color: #cccccc;">FOLIO</td>
    <td style="width:150px;border:1px solid #000;background-color: #cccccc;">FECHA DE PAGO</td>
    <td style="width:150px;border:1px solid #000;background-color: #cccccc;">RFC</td>
    <td style="width:400px;border:1px solid #000;background-color: #cccccc;">CLIENTE</td>
    <td style="width:100px;border:1px solid #000;background-color: #cccccc;">IMPORTE</td>
    <td style="width:100px;border:1px solid #000;background-color: #cccccc;">IVA</td>
    <td style="width:150px;border:1px solid #000;background-color: #cccccc;">TOTAL</td>
    </tr>';
    $total_importe = $total_iva = 0;
    foreach ($res as $key => $value)
    {
      $html .= '<tr>
      <td style="width:150px;border:1px solid #000;">'.$value->serie.'</td>
      <td style="width:150px;border:1px solid #000;">'.$value->folio.'</td>
      <td style="width:150px;border:1px solid #000;">'.$value->fecha_pago.'</td>
      <td style="width:150px;border:1px solid #000;">'.$value->rfc.'</td>
      <td style="width:400px;border:1px solid #000;">'.$value->nombre_fiscal.'</td>
      <td style="width:100px;border:1px solid #000;">'.($value->total_abono-$value->importe_iva).'</td>
      <td style="width:100px;border:1px solid #000;">'.$value->importe_iva.'</td>
      <td style="width:150px;border:1px solid #000;">'.($value->total_abono).'</td>
      </tr>';
      $total_importe += $value->total_abono;
      $total_iva += $value->importe_iva;
    }

    $html .= '
    <tr style="font-weight:bold">
    <td colspan="5">TOTALES</td>
    <td style="border:1px solid #000;">'.($total_importe-$total_iva).'</td>
    <td style="border:1px solid #000;">'.$total_iva.'</td>
    <td style="border:1px solid #000;">'.($total_importe).'</td>
    </tr>
    </tbody>
    </table>';

    echo $html;
  }

  public function rptVentasClienteXls()
  {
    header('Content-type: application/vnd.ms-excel');
    header("Content-Disposition: attachment; filename=compras.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $res = $this->getRptventasData('p.nombre_fiscal ASC');

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "INTEGRACION DE OPERACIONES EFECTUADAS AL 100% DE LOS ACTOS GRAVADOS POR LOS CLIENTES";
    $titulo3 = 'Del: '.$this->input->get('ffecha1')." Al ".$this->input->get('ffecha2');

    $html = '<table>
    <tbody>
    <tr>
    <td colspan="5" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
    </tr>
    <tr>
    <td colspan="5" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
    </tr>
    <tr>
    <td colspan="5" style="text-align:center;">'.$titulo3.'</td>
    </tr>
    <tr style="font-weight:bold">
    <td style="width:150px;border:1px solid #000;background-color: #cccccc;">RFC</td>
    <td style="width:400px;border:1px solid #000;background-color: #cccccc;">CLIENTE</td>
    <td style="width:150px;border:1px solid #000;background-color: #cccccc;">TOTAL</td>
    </tr>';
    $total_importe = $total_iva = $total_proveedor = 0;
    $grupo_cliente = '';
    foreach ($res as $key => $value)
    {
      if($value->nombre_fiscal !== $grupo_cliente)
      {
        if($key > 0){
          $html .= '<tr style="font-weight:bold;">
          <td style="width:150px;border:1px solid #000;"></td>
          <td style="width:500px;border:1px solid #000;">Total '.$grupo_cliente.'</td>
          <td style="width:150px;border:1px solid #000;">'.$total_proveedor.'</td>
          </tr>';
        }
        $total_proveedor = 0;
        $grupo_cliente = $value->nombre_fiscal;
      }
      $html .= '<tr>
      <td style="width:150px;border:1px solid #000;">'.$value->rfc.'</td>
      <td style="width:500px;border:1px solid #000;">'.$value->nombre_fiscal.'</td>
      <td style="width:150px;border:1px solid #000;">'.($value->total_abono+$value->importe_iva).'</td>
      </tr>';
      $total_importe += $value->total_abono-$value->importe_iva;
      $total_iva += $value->importe_iva;
      $total_proveedor += $value->total_abono;
    }

    if($total_proveedor > 0){
      $html .= '<tr style="font-weight:bold;">
      <td style="width:150px;border:1px solid #000;"></td>
      <td style="width:500px;border:1px solid #000;">Total '.$grupo_cliente.'</td>
      <td style="width:150px;border:1px solid #000;">'.$total_proveedor.'</td>
      </tr>';
    }
    $html .= '
    <tr style="font-weight:bold">
    <td colspan="1">TOTALES</td>
    <td style="border:1px solid #000;">'.($total_importe+$total_iva).'</td>
    </tr>
    </tbody>
    </table>';

    echo $html;
  }


  public function rptFacturasVencidasData()
  {
    $sql = '';

    //Filtros para buscar
    $_GET['ffecha1'] = $this->input->get('ffecha1')==''? date("Y-m-").'01': $this->input->get('ffecha1');
    $_GET['ffecha2'] = $this->input->get('ffecha2')==''? date("Y-m-d"): $this->input->get('ffecha2');
    $fecha = $_GET['ffecha1'] > $_GET['ffecha2']? $_GET['ffecha2']: $_GET['ffecha1'];
    $sql .= " AND (Date(f.fecha) >= '{$_GET['ffecha1']}' AND Date(f.fecha) <= '{$_GET['ffecha2']}')";

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']{0}) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']{0}) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND f.id_empresa = {$_GET['did_empresa']}";
    }

    if ($this->input->get('tipo') != '') {
      $sql .= " AND f.is_factura = '{$_GET['tipo']}'";
    }

    $response = array();
    $facturas = $this->db->query("SELECT
        f.id_factura,
        f.serie,
        f.folio,
        Date(f.fecha) AS fecha,
        c.nombre_fiscal AS cliente,
        COALESCE(f.total, 0) AS cargo,
        COALESCE(f.importe_iva, 0) AS iva,
        COALESCE(ac.abono, 0) AS abono,
        (COALESCE(f.total, 0) - COALESCE(ac.abono, 0))::numeric(100,2) AS saldo,
        (CASE WHEN f.tipo_cambio > 1 THEN (COALESCE(f.total/f.tipo_cambio, 0) - COALESCE(ac.abono/f.tipo_cambio, 0))::numeric(100,2) ELSE 0 END) AS saldo_cambio,
        (CASE (COALESCE(f.total, 0) - COALESCE(ac.abono, 0)) WHEN 0 THEN 'Pagada' ELSE 'Pendiente' END) AS estado,
        Date(f.fecha + (f.plazo_credito || ' days')::interval) AS fecha_vencimiento,
        (Date('2019-09-10'::timestamp with time zone)-Date(f.fecha + (f.plazo_credito || ' days')::interval)) AS dias_transc,
        ( (CASE WHEN f.is_factura='t' THEN 'FACTURA ' ELSE 'REMISION ' END) || f.serie || f.folio) AS concepto,
        'f' as tipo
      FROM facturacion AS f
        INNER JOIN clientes c ON c.id_cliente = f.id_cliente
          LEFT JOIN (
            SELECT id_factura, Sum(abono) AS abono
            FROM (
              (
          SELECT
          id_factura,
          Sum(total) AS abono
          FROM
          facturacion_abonos as fa
          WHERE Date(fecha) <= '2019-09-10'
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
          AND id_cliente = 960
          AND Date(fecha) <= '2019-09-10'
          GROUP BY id_nc
              )
            ) AS ffs
            GROUP BY id_factura
          ) AS ac ON f.id_factura = ac.id_factura  AND f.id_empresa = '2'
          LEFT JOIN (SELECT id_remision, id_factura, status
            FROM remisiones_historial WHERE status <> 'ca' AND status <> 'b'
          ) fh ON f.id_factura = fh.id_remision
      WHERE f.id_abono_factura IS NULL AND f.status <> 'ca' AND f.status <> 'b' AND id_nc IS NULL
        AND COALESCE(fh.id_remision, 0) = 0 {$sql}
        AND (COALESCE(f.total, 0) - COALESCE(ac.abono, 0))::numeric(100,2) > 0
        AND Date(f.fecha + (f.plazo_credito || ' days')::interval) <= Date(Now())
      ORDER BY cliente ASC, fecha ASC, serie ASC, folio ASC");
    $response = $facturas->result();

    return $response;
  }
  public function rptFacturasVencidasPdf(){
    $res = $this->rptFacturasVencidasData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');

    if ($empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = $empresa['info']->nombre_fiscal;

    $pdf->titulo2 = 'Reporte de Facturas Vencidas';
    $pdf->titulo3 = 'Del: '.MyString::fechaAT($this->input->get('ffecha1'))." Al ".MyString::fechaAT($this->input->get('ffecha2'))."\n";
    $pdf->titulo3 .= ($this->input->get('tipo')==''? 'Todas': ($this->input->get('tipo')=='t'? 'Facturas': 'Remisiones') );
    $pdf->AliasNbPages();
    $pdf->SetFont('Arial','',8);

    $aligns = array('L', 'L', 'L', 'R', 'R', 'R', 'L');
    $widths = array(19, 25, 60, 25, 25, 25, 25);
    $header = array('Fecha', 'Factura', 'Concepto', 'Cargo', 'Abono', 'Saldo', 'F. Vencimiento');

    $cliente_aux = '';
    $show_headers = false;
    foreach($res as $key => $factura){

      if ($cliente_aux !== $factura->cliente) {
        if ($pdf->GetY() >= $pdf->limiteY || $key==0) {
          $pdf->AddPage();
        }

        $pdf->SetFont('Arial','B',8);
        $pdf->SetX(6);
        $pdf->SetAligns(['L']);
        $pdf->SetWidths([200]);
        $pdf->Row([$factura->cliente], false, false);

        $cliente_aux = $factura->cliente;
        $show_headers = true;
      }

      if($pdf->GetY() >= $pdf->limiteY || $key==0 || $show_headers){ //salta de pagina si exede el max
        $show_headers = false;
        if ($pdf->GetY() >= $pdf->limiteY) {
          $pdf->AddPage();
        }

        $pdf->SetFont('Arial','B',8);
        $pdf->SetFillColor(200,200,200);
        $pdf->SetX(6);
        $pdf->SetAligns($aligns);
        $pdf->SetWidths($widths);
        $pdf->Row($header, true);
        $pdf->SetY($pdf->GetY()+2);
      }

      $pdf->SetFont('Arial','',8);
      $datos = [
        MyString::fechaATexto($factura->fecha, '/c'),
        $factura->serie.$factura->folio,
        $factura->concepto,
        MyString::formatoNumero($factura->cargo, 2, '', false),
        MyString::formatoNumero($factura->abono, 2, '', false),
        MyString::formatoNumero($factura->saldo, 2, '', false),
        MyString::fechaATexto($factura->fecha_vencimiento, '/c'),
      ];
      $pdf->SetXY(6, $pdf->GetY()-2);
      $pdf->SetAligns($aligns);
      $pdf->SetWidths($widths);
      $pdf->Row($datos, false, false);
    }
    // $datos = array('Total General',
    //   MyString::formatoNumero($proveedor_importe, 2, '', false),
    //   );
    // $pdf->SetXY(6, $pdf->GetY());
    // $pdf->SetAligns(array('L', 'R'));
    // $pdf->SetWidths(array(162, 20));
    // $pdf->Row($datos, false);

    $pdf->Output('compras_productos.pdf', 'I');
  }

}
