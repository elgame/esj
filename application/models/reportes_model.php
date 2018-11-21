<?php
class reportes_model extends CI_Model {

  public function getSaldoCajaIngClientes($fecha, $empresa=null)
  {
    $sql = ['', '', ''];
    if ($empresa) {
      $sql[0] = "AND cc.id_empresa = {$empresa}";
      $sql[1] = "GROUP BY cc.id_empresa";
    }
    $sql[2] = " AND ci.fecha <= '{$fecha}'";

    $query = $this->db->query(
      "SELECT Sum(ci.monto) AS monto
      FROM cajachica_ingresos ci
        INNER JOIN cajachica_categorias cc ON cc.id_categoria = ci.id_categoria
        INNER JOIN cajachica_nomenclaturas cn ON cn.id = ci.id_nomenclatura
      WHERE ci.otro = 'f'
        {$sql[0]}
        {$sql[2]}
      {$sql[1]}");
    $res = $query->row();
    return floatval($res->monto);
  }

  public function getSaldoCajaIngRemisiones($fecha, $empresa=null)
  {
    $sql = ['', '', ''];
    if ($empresa) {
      $sql[0] = "AND cc.id_empresa = {$empresa}";
      $sql[1] = "GROUP BY cc.id_empresa";
    }

    $query = $this->db->query(
      "SELECT Sum(cr.monto) AS monto
      FROM cajachica_remisiones cr
        INNER JOIN facturacion f ON f.id_factura = cr.id_remision
        INNER JOIN cajachica_categorias cc ON cc.id_categoria = cr.id_categoria
        LEFT JOIN facturacion_ventas_remision_pivot fvr ON fvr.id_venta = f.id_factura
      WHERE cr.fecha <= '{$fecha}'
        {$sql[0]}
      {$sql[1]}");
    $res = $query->row();
    return floatval((isset($res->monto)? $res->monto: 0));
  }

  public function getSaldoCajaBascula($fecha, $empresa=null)
  {
    $sql = ['', '', ''];
    if ($empresa) {
      $sql[0] = "AND b.id_empresa = {$empresa}";
      $sql[1] = "GROUP BY b.id_empresa";
    }

    $query = $this->db->query(
      "SELECT Sum(b.importe) AS importe
      FROM bascula b
        INNER JOIN areas a ON a.id_area = b.id_area
        INNER JOIN proveedores pr ON pr.id_proveedor = b.id_proveedor
        INNER JOIN cajachica_boletas cb ON (cb.id_bascula = b.id_bascula AND cb.fecha = Date(b.fecha_pago))
      WHERE a.tipo = 'fr' AND DATE(b.fecha_pago) <= '{$fecha}'
        {$sql[0]}
        AND (b.accion = 'p' OR (b.metodo_pago = 'co' AND b.accion <> 'b')) AND b.status = 't'
      {$sql[1]}");
    $res = $query->row();
    return floatval((isset($res->importe)? $res->importe: 0));
  }

  public function getSaldoCajaGastos($fecha, $empresa=null)
  {
    $sql = ['', '', ''];
    if ($empresa) {
      $sql[0] = "AND cc.id_empresa = {$empresa}";
      $sql[1] = "GROUP BY cc.id_empresa";
    }

    $query = $this->db->query(
      "SELECT Sum(cg.monto) AS monto
      FROM cajachica_gastos cg
        INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
      WHERE cg.fecha <= '{$fecha}'
        {$sql[0]}
      {$sql[1]}");
    $res = $query->row();
    return floatval((isset($res->monto)? $res->monto: 0));
  }

  public function getSaldoCaja($fecha)
  {
    $query = $this->db->query(
      "SELECT DISTINCT ON (no_caja) no_caja, saldo, fecha
      FROM cajachica_efectivo
      WHERE fecha <= '{$fecha}'
      ORDER BY no_caja ASC, fecha DESC");
    $saldo = 0;
    if ($query->num_rows() > 0) {
      foreach ($query->result() as $key => $value) {
        $saldo += $value->saldo;
      }
    }
    return floatval($saldo);
  }

  /**
   * Reportes Productos Facturados.
   *
   * @return void
   */
  public function getDataBalanceGeneral()
  {
    $sql = '';
    $response = array();

    $fecha = $this->input->get('ffecha2')? $this->input->get('ffecha2'): date("Y-m-d");
    $empresa = $this->input->get('did_empresa')? $this->input->get('did_empresa'): null;

    // Saldo de caja
    $response['caja'] = $this->getSaldoCajaIngClientes($fecha, $empresa) +
            $this->getSaldoCajaIngRemisiones($fecha, $empresa) -
            $this->getSaldoCajaBascula($fecha, $empresa) -
            $this->getSaldoCajaGastos($fecha, $empresa);
    $response['caja2'] = $this->getSaldoCaja($fecha);

    // Saldo bancos
    $this->load->model('banco_cuentas_model');
    $_GET['did_empresa'] = $empresa? $empresa: 'all';
    $_GET['contable'] = 't';
    $bancos = $this->banco_cuentas_model->getSaldosCuentasData();
    $response['bancos'] = $bancos['total_saldos'];

    // Saldo clientes
    $this->load->model('cuentas_cobrar_model');
    $_GET['did_empresa'] = $empresa? $empresa: 'all';
    $clientes = $this->cuentas_cobrar_model->getCuentasCobrarData(1000);
    $response['clientes'] = $clientes['ttotal_saldo'];

    // Saldo proveedores
    $this->load->model('cuentas_pagar_model');
    $_GET['did_empresa'] = $empresa? $empresa: 'all';
    $proveedor = $this->cuentas_pagar_model->getCuentasPagarData(10000);
    $response['proveedores'] = $proveedor['ttotal_saldo'];

    // Deudores diversos / saldo caja prestamo
    $this->load->model('caja_chica_prest_model');
    $prestamos = $this->caja_chica_prest_model->get_saldos($fecha, '1' );
    $response['deudores_diversos'] = $prestamos['prestamos_lp_fi']+$prestamos['prestamos_lp_ef']+$prestamos['prestamos_cp'];
    $response['caja_prestamos'] = $prestamos['saldo_caja'];

    // Almacén
    $this->load->model('inventario_model');
    $_GET['did_empresa'] = $empresa;
    $response['almacen'] = $this->inventario_model->getCostoInventario($fecha);
    $response['almacen'] = $response['almacen']->costo;

    return $response;
  }
  public function balance_general_pdf()
  {
    $datos = $this->getDataBalanceGeneral();

    $this->load->model('empresas_model');
    if ($this->input->get('did_empresa'))
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;

    if (isset($empresa) && $empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = isset($empresa)? $empresa['info']->nombre_fiscal: '';
    $pdf->titulo2 = "Balance General";

    // $pdf->titulo3 = "{$_GET['dproducto']} \n";
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1'])." al ".MyString::fechaAT($_GET['ffecha2'])."";
    elseif (!empty($_GET['ffecha1']))
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1']);
    elseif (!empty($_GET['ffecha2']))
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha2']);

    $pdf->AliasNbPages();
    $pdf->AddPage();
    // $links = array('', '', '', '');
    $pdf->SetY(30);
    $aligns = array('L', 'R');
    $widths = array(50, 30);

    $pdf->SetFont('Arial','',7);
    $pdf->SetTextColor(0,0,0);

    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->SetX(6);
    $pdf->Row(['Caja', MyString::formatoNumero($datos['caja'], 2, '', false)], false);
    $pdf->SetX(6);
    $pdf->Row(['Caja 2', MyString::formatoNumero($datos['caja2'], 2, '', false)], false);
    $pdf->SetX(6);
    $pdf->Row(['Bancos', MyString::formatoNumero($datos['bancos'], 2, '', false)], false);
    $pdf->SetX(6);
    $pdf->Row(['Clientes', MyString::formatoNumero($datos['clientes'], 2, '', false)], false);
    $pdf->SetX(6);
    $pdf->Row(['Proveedores', MyString::formatoNumero($datos['proveedores'], 2, '', false)], false);
    $pdf->SetX(6);
    $pdf->Row(['Deudores Diversos', MyString::formatoNumero($datos['deudores_diversos'], 2, '', false)], false);
    $pdf->SetX(6);
    $pdf->Row(['Caja prestamos', MyString::formatoNumero($datos['caja_prestamos'], 2, '', false)], false);
    $pdf->SetX(6);
    $pdf->Row(['Almacén', MyString::formatoNumero($datos['almacen'], 2, '', false)], false);

    $pdf->Output('balance.pdf', 'I');
  }




  /////////////////////////// ESTADO DE RESULTADO ///////////////////////////////////////

  public function erIngresos($sqlFecha, $sqlemp1, $sqlemp2)
  {
    $query = $this->db->query(
      "SELECT a.id_area, Coalesce(a.nombre, 'OTROS INGRESOS') AS cultivo, Sum(fp.importe) AS total
      FROM facturacion f
        INNER JOIN facturacion_productos fp ON f.id_factura = fp.id_factura
        LEFT JOIN clasificaciones c ON c.id_clasificacion = fp.id_clasificacion
        LEFT JOIN areas a ON a.id_area = c.id_area
        LEFT JOIN (
          SELECT id_remision, id_factura, status
          FROM remisiones_historial
          WHERE status <> 'ca' AND status <> 'b'
        ) fh ON f.id_factura = fh.id_remision
      WHERE f.status <> 'ca' AND f.status <> 'b' AND f.tipo_comprobante = 'ingreso'
        AND COALESCE(fh.id_remision, 0) = 0
        AND Date(f.fecha) BETWEEN {$sqlFecha} {$sqlemp1}
      GROUP BY a.id_area
      UNION
      SELECT 0 AS id_area, 'INTANGIBLES' AS cultivo, Coalesce(Sum(ci.monto), 0) AS total
      FROM cajachica_ingresos ci
        INNER JOIN cajachica_categorias cc ON cc.id_categoria = ci.id_categoria
      WHERE ci.id_nomenclatura = 11 AND ci.no_caja = 2
        AND Date(ci.fecha) BETWEEN {$sqlFecha} $sqlemp2");
    return $query->result();
  }

  public function erIngresosDescuentos($sqlFecha, $sqlemp1)
  {
    $query = $this->db->query(
      "SELECT a.id_area, Coalesce(a.nombre, 'OTROS INGRESOS') AS cultivo, Sum(fp.importe) AS total
      FROM facturacion f
        INNER JOIN facturacion_productos fp ON f.id_factura = fp.id_factura
        LEFT JOIN clasificaciones c ON c.id_clasificacion = fp.id_clasificacion
        LEFT JOIN areas a ON a.id_area = c.id_area
      WHERE f.status <> 'ca' AND f.status <> 'b' AND f.tipo_comprobante = 'egreso'
        AND Date(f.fecha) BETWEEN {$sqlFecha} {$sqlemp1}
      GROUP BY a.id_area");
    return $query->result();
  }

  public function erEgresosSalidas($sqlFecha, $sqlemp3)
  {
    $query = $this->db->query(
      "SELECT a.id_area, Coalesce(a.nombre, 'COSTO SALIDAS ALMACEN') AS cultivo, Sum(csp.cantidad*csp.precio_unitario) AS total
      FROM compras_salidas cs
        INNER JOIN compras_salidas_productos csp ON cs.id_salida = csp.id_salida
        LEFT JOIN areas a ON a.id_area = cs.id_area
      WHERE (cs.status = 's' OR cs.status = 'b') {$sqlemp3}
        AND Date(cs.fecha_creacion) BETWEEN {$sqlFecha}
      GROUP BY a.id_area");
    return $query->result();
  }

  public function erEgresosGastosDir($sqlFecha, $sqlemp4)
  {
    $query = $this->db->query(
      "SELECT a.id_area, Coalesce(a.nombre, 'COSTO GASTOS DIRECTOS') AS cultivo, Sum(c.subtotal) AS total, c.intangible
      FROM compras c
        LEFT JOIN areas a ON a.id_area = c.id_area
      WHERE c.status <> 'ca' AND c.isgasto = 't' {$sqlemp4}
        AND Date(c.fecha) BETWEEN {$sqlFecha}
      GROUP BY a.id_area, c.intangible");
    return $query->result();
  }

  public function erEgresosGastosOrd($sqlFecha, $sqlemp5)
  {
    $query = $this->db->query(
      "SELECT a.id_area, Coalesce(a.nombre, 'COSTO SALIDAS ALMACEN') AS cultivo, Sum(cp.importe) AS total
      FROM compras_ordenes co
        INNER JOIN compras_productos cp ON co.id_orden = cp.id_orden
        LEFT JOIN areas a ON a.id_area = co.id_area
      WHERE (co.status = 'a' OR co.status = 'f') AND co.tipo_orden in('d', 'f', 'oc') {$sqlemp5}
        AND Date(co.fecha_aceptacion) BETWEEN {$sqlFecha}
      GROUP BY a.id_area");
    return $query->result();
  }

  public function erEgresosGastosCajaTry($sqlFecha, $sqlemp2)
  {
    $query = $this->db->query(
      "SELECT a.id_area, Coalesce(a.nombre, 'GASTOS CAJA 2') AS cultivo, Sum(cg.monto) AS total
      FROM cajachica_gastos cg
        INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
        LEFT JOIN areas a ON a.id_area = cg.id_areac
      WHERE cg.no_caja = 2 {$sqlemp2}
        AND Date(cg.fecha) BETWEEN {$sqlFecha}
      GROUP BY a.id_area");
    return $query->result();
  }

  public function erEgresosGastosCajaGdl($sqlFecha, $sqlemp2)
  {
    $query = $this->db->query(
      "SELECT a.id_area, Coalesce(a.nombre, 'GASTOS CAJA GDL') AS cultivo, Sum(cg.monto) AS total
      FROM otros.bodega_gastos cg
        INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
        LEFT JOIN areas a ON a.id_area = cg.id_areac
      WHERE cg.no_caja = 1 {$sqlemp2}
        AND Date(cg.fecha) BETWEEN {$sqlFecha}
      GROUP BY a.id_area");
    return $query->result();
  }

  public function erEgresosGastosNomina($sqlFecha, $sqlemp6)
  {
    $query = $this->db->query(
      "SELECT a.id_area, Coalesce(a.nombre, 'OTROS'), Sum(nf.total_percepcion-nf.subsidio) AS total
      FROM nomina_fiscal nf
        INNER JOIN usuarios u ON u.id = nf.id_empleado
        LEFT JOIN areas a ON a.id_area = u.id_area
      WHERE {$sqlemp6} Date(nf.fecha) BETWEEN {$sqlFecha}
      GROUP BY a.id_area");
    return $query->result();
  }

  public function erEgresosComisionesBan($sqlFecha, $sqlemp7)
  {
    $query = $this->db->query(
      "SELECT p.id_empresa, Sum(pm.monto) AS total, Coalesce(Sum(n_areas.no_areas), 1) AS no_areas
      FROM otros.polizas p
        INNER JOIN otros.polizas_movimientos pm ON p.id = pm.id_poliza
        INNER JOIN otros.centro_costo cc ON cc.id_centro_costo = pm.id_centro_costo
        LEFT JOIN (
          SELECT id_empresa, Count(*) AS no_areas
          FROM areas_empresas
          GROUP BY id_empresa
        ) n_areas ON p.id_empresa = n_areas.id_empresa
      WHERE p.status = 't' AND cc.tipo = 'gastofinanciero' AND cc.nombre = 'COMISIONES BANCARIAS'
        {$sqlemp7} AND p.fecha BETWEEN {$sqlFecha}
      GROUP BY p.id_empresa");
    return $query->result();
  }

  public function erEgresosNc($sqlFecha, $sqlemp4)
  {
    $query = $this->db->query(
      "SELECT a.id_area, Coalesce(a.nombre, 'no') AS cultivo, Sum(c.subtotal) AS total
      FROM compras c
        INNER JOIN (
          SELECT c.id_compra, c.id_area
          FROM compras c
          WHERE c.status <> 'ca' AND c.tipo = 'c' AND c.id_area IS NOT NULL
          GROUP BY c.id_compra, c.id_area
        ) ca ON c.id_nc = ca.id_compra
        LEFT JOIN areas a ON a.id_area = ca.id_area
      WHERE c.status <> 'ca' AND c.tipo = 'nc'
        AND a.id_area IS NOT NULL
        {$sqlemp4} AND (c.fecha) BETWEEN {$sqlFecha}
      GROUP BY a.id_area");
    $response = $query->result();

    $query = $this->db->query(
      "SELECT a.id_area, Coalesce(a.nombre, 'no') AS cultivo, Sum(c.subtotal/ca.num) AS total
      FROM compras c
        INNER JOIN (
          SELECT c.id_compra, co.id_area, Sum(cc.num) AS num
          FROM compras c
            INNER JOIN compras_productos cp ON c.id_compra = cp.id_compra
            INNER JOIN compras_ordenes co ON co.id_orden = cp.id_orden
            INNER JOIN (
              SELECT c.id_compra, Count(c.id_compra) AS num
              FROM compras c
                INNER JOIN compras_productos cp ON c.id_compra = cp.id_compra
                INNER JOIN compras_ordenes co ON co.id_orden = cp.id_orden
              WHERE c.status <> 'ca' AND c.tipo = 'c' AND co.id_area IS NOT NULL
              GROUP BY c.id_compra
            ) cc ON cc.id_compra = c.id_compra
          WHERE c.status <> 'ca' AND c.tipo = 'c' AND co.id_area IS NOT NULL
          GROUP BY c.id_compra, co.id_area
        ) ca ON c.id_nc = ca.id_compra
        LEFT JOIN areas a ON a.id_area = ca.id_area
      WHERE c.status <> 'ca' AND c.tipo = 'nc'
        {$sqlemp4} AND (c.fecha) BETWEEN {$sqlFecha}
      GROUP BY a.id_area");

    foreach ($query->result() as $key => $value) {
      $entro = false;
      foreach ($response as $key1 => $value1) {
        if ($value->id_area == $value1->id_area) {
          $value1->total += $value->total;
          $entro = true;
          break;
        }
      }

      if (!$entro) {
        $response[] = $value;
      }
    }

    return $response;
  }

  public function getDataEstadoResultado()
  {
    $sqlemp1 = $sqlemp2 = $sqlemp3 = $sqlemp4 = $sqlemp5 = $sqlemp6 =
    $sqlemp7 = $sql = '';
    $response = ['saldo' => [], 'mes' => []];

    $anio = date("Y");
    $ini_anio = $anio.'-01-01';
    if ($this->input->get('ffecha1')) {
      $fecha = explode('-', $this->input->get('ffecha1'));
      $anio = $fecha[0];
      $ini_anio = $anio.'-01-01';
    }
    $mes_actual[1] = $this->input->get('ffecha2')? $this->input->get('ffecha2'): date("Y-m-d");
    $mes_actual[0] = substr($mes_actual[1], 0, 8).'01';
    $fin_anio = new DateTime($mes_actual[0]);
    $fin_anio->sub(new DateInterval('P1D'));
    if ($this->input->get('did_empresa') > 0) {
      $sqlemp1 = "AND f.id_empresa = ".$this->input->get('did_empresa');
      $sqlemp2 = "AND cc.id_empresa = ".$this->input->get('did_empresa');
      $sqlemp3 = "AND cs.id_empresa = ".$this->input->get('did_empresa');
      $sqlemp4 = "AND c.id_empresa = ".$this->input->get('did_empresa');
      $sqlemp5 = "AND co.id_empresa = ".$this->input->get('did_empresa');
      $sqlemp6 = " nf.id_empresa = ".$this->input->get('did_empresa')." AND ";
      $sqlemp7 = "AND p.id_empresa = ".$this->input->get('did_empresa');
    }

    $sqlFechaSaldo = "'{$ini_anio}' AND '{$fin_anio->format('Y-m-d')}'";
    $sqlFechaMes = "'{$mes_actual[0]}' AND '{$mes_actual[0]}'";

    // Ingresos
    $response['saldo']['ingresos'] = $this->erIngresos($sqlFechaSaldo, $sqlemp1, $sqlemp2);
    $response['mes']['ingresos'] = $this->erIngresos($sqlFechaMes, $sqlemp1, $sqlemp2);

    // Ingresos descuentos
    $response['saldo']['ingresos_descuentos'] = $this->erIngresosDescuentos($sqlFechaSaldo, $sqlemp1);
    $response['mes']['ingresos_descuentos'] = $this->erIngresosDescuentos($sqlFechaMes, $sqlemp1);

    // Egresos salidas almacén
    $response['saldo']['egresos_salidas'] = $this->erEgresosSalidas($sqlFechaSaldo, $sqlemp3);
    $response['mes']['egresos_salidas'] = $this->erEgresosSalidas($sqlFechaMes, $sqlemp3);

    // Egresos gastos directos
    $response['saldo']['egresos_gastos_dir'] = $this->erEgresosGastosDir($sqlFechaSaldo, $sqlemp4);
    $response['mes']['egresos_gastos_dir'] = $this->erEgresosGastosDir($sqlFechaMes, $sqlemp4);

    // Egresos gastos de ordenes
    $response['saldo']['egresos_gastos_ord'] = $this->erEgresosGastosOrd($sqlFechaSaldo, $sqlemp5);
    $response['mes']['egresos_gastos_ord'] = $this->erEgresosGastosOrd($sqlFechaMes, $sqlemp5);

    // Egresos gastos de caja tryana
    $response['saldo']['egresos_gastos_caja_try'] = $this->erEgresosGastosCajaTry($sqlFechaSaldo, $sqlemp2);
    $response['mes']['egresos_gastos_caja_try'] = $this->erEgresosGastosCajaTry($sqlFechaMes, $sqlemp2);

    // Egresos gastos de caja Gdl
    $response['saldo']['egresos_gastos_caja_gdl'] = $this->erEgresosGastosCajaGdl($sqlFechaSaldo, $sqlemp2);
    $response['mes']['egresos_gastos_caja_gdl'] = $this->erEgresosGastosCajaGdl($sqlFechaMes, $sqlemp2);

    // Egresos nomina
    $response['saldo']['egresos_gastos_nomina'] = $this->erEgresosGastosNomina($sqlFechaSaldo, $sqlemp6);
    $response['mes']['egresos_gastos_nomina'] = $this->erEgresosGastosNomina($sqlFechaMes, $sqlemp6);

    // Egresos comisiones bancarias
    $response['saldo']['egresos_comisiones_ban'] = $this->erEgresosComisionesBan($sqlFechaSaldo, $sqlemp7);
    $response['mes']['egresos_comisiones_ban'] = $this->erEgresosComisionesBan($sqlFechaMes, $sqlemp7);

    // Egresos notas de credito
    $response['saldo']['egresos_nc'] = $this->erEgresosNc($sqlFechaSaldo, $sqlemp4);
    $response['mes']['egresos_nc'] = $this->erEgresosNc($sqlFechaMes, $sqlemp4);

    return $response;
  }

  public function estado_resultado_pdf()
  {
    $datos = $this->getDataEstadoResultado();
    echo "<pre>";
    var_dump($datos);
    echo "</pre>";exit;

    $this->load->model('empresas_model');
    if ($this->input->get('did_empresa'))
      $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;

    if (isset($empresa) && $empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = isset($empresa)? $empresa['info']->nombre_fiscal: '';
    $pdf->titulo2 = "Balance General";

    // $pdf->titulo3 = "{$_GET['dproducto']} \n";
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1'])." al ".MyString::fechaAT($_GET['ffecha2'])."";
    elseif (!empty($_GET['ffecha1']))
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha1']);
    elseif (!empty($_GET['ffecha2']))
        $pdf->titulo3 .= "Del ".MyString::fechaAT($_GET['ffecha2']);

    $pdf->AliasNbPages();
    $pdf->AddPage();
    // $links = array('', '', '', '');
    $pdf->SetY(30);
    $aligns = array('L', 'R');
    $widths = array(50, 30);

    $pdf->SetFont('Arial','',7);
    $pdf->SetTextColor(0,0,0);

    $pdf->SetAligns($aligns);
    $pdf->SetWidths($widths);
    $pdf->SetX(6);
    $pdf->Row(['Caja', MyString::formatoNumero($datos['caja'], 2, '', false)], false);
    $pdf->SetX(6);
    $pdf->Row(['Caja 2', MyString::formatoNumero($datos['caja2'], 2, '', false)], false);
    $pdf->SetX(6);
    $pdf->Row(['Bancos', MyString::formatoNumero($datos['bancos'], 2, '', false)], false);
    $pdf->SetX(6);
    $pdf->Row(['Clientes', MyString::formatoNumero($datos['clientes'], 2, '', false)], false);
    $pdf->SetX(6);
    $pdf->Row(['Proveedores', MyString::formatoNumero($datos['proveedores'], 2, '', false)], false);
    $pdf->SetX(6);
    $pdf->Row(['Deudores Diversos', MyString::formatoNumero($datos['deudores_diversos'], 2, '', false)], false);
    $pdf->SetX(6);
    $pdf->Row(['Caja prestamos', MyString::formatoNumero($datos['caja_prestamos'], 2, '', false)], false);
    $pdf->SetX(6);
    $pdf->Row(['Almacén', MyString::formatoNumero($datos['almacen'], 2, '', false)], false);

    $pdf->Output('balance.pdf', 'I');
  }






  public function balance_general_xls()
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=productos_facturados.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $facturas = $this->getDataRPF2();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "Reporte Productos Facturados con Kilos";
    $titulo3 = "";
    if (!empty($_GET['ffecha1']) && !empty($_GET['ffecha2']))
        $titulo3 .= "Del ".$_GET['ffecha1']." al ".$_GET['ffecha2']."";
    elseif (!empty($_GET['ffecha1']))
        $titulo3 .= "Del ".$_GET['ffecha1'];
    elseif (!empty($_GET['ffecha2']))
        $titulo3 .= "Del ".$_GET['ffecha2'];

    $html = '<table>
      <tbody>
        <tr>
          <td colspan="6" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="6" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="6" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>
        <tr style="font-weight:bold">
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Fecha</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Serie/Folio</td>
          <td style="width:400px;border:1px solid #000;background-color: #cccccc;">Cliente</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Cantidad</td>
          <td style="width:100px;border:1px solid #000;background-color: #cccccc;">Kgs</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Precio</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">Importe</td>
        </tr>';
    $total_importe = $total_cantidad = $total_kilos = 0;
    $total_importet = $total_cantidadt = $total_kilost = 0;
    foreach ($facturas as $key => $produc)
    {
      $total_importe = $total_cantidad = $total_kilos = 0;

      $html .= '<tr>
            <td colspan="6" style="font-size:14px;border:1px solid #000;">'.$produc['producto']->nombre.'</td>
          </tr>';
      foreach ($produc['listado'] as $key2 => $value)
      {
        $html .= '<tr>
            <td style="width:150px;border:1px solid #000;">'.$value->fecha.'</td>
            <td style="width:100px;border:1px solid #000;">'.$value->serie.$value->folio.'</td>
            <td style="width:400px;border:1px solid #000;">'.$value->cliente.'</td>
            <td style="width:100px;border:1px solid #000;">'.$value->cantidad.'</td>
            <td style="width:100px;border:1px solid #000;">'.$value->kilos.'</td>
            <td style="width:150px;border:1px solid #000;">'.$value->precio_unitario.'</td>
            <td style="width:150px;border:1px solid #000;">'.$value->importe.'</td>
          </tr>';
          $total_importe   += $value->importe;
          $total_cantidad  += $value->cantidad;
          $total_kilos     += $value->kilos;
          $total_importet  += $value->importe;
          $total_cantidadt += $value->cantidad;
          $total_kilost    += $value->kilos;
      }
      $html .= '
        <tr style="font-weight:bold">
          <td colspan="3">TOTAL</td>
          <td style="border:1px solid #000;">'.$total_cantidad.'</td>
          <td style="border:1px solid #000;">'.$total_kilos.'</td>
          <td style="border:1px solid #000;">'.($total_cantidad == 0 ? 0 : $total_importe/$total_cantidad).'</td>
          <td style="border:1px solid #000;">'.$total_importe.'</td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>
        <tr>
          <td colspan="6"></td>
        </tr>';
    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="3">TOTALES</td>
          <td style="border:1px solid #000;">'.$total_cantidadt.'</td>
          <td style="border:1px solid #000;">'.$total_kilost.'</td>
          <td style="border:1px solid #000;">'.($total_cantidadt == 0 ? 0 : $total_importet/$total_cantidadt).'</td>
          <td style="border:1px solid #000;">'.$total_importet.'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }

}