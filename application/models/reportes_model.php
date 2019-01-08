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

  public function erCultivosAjuste($saldo, $mes)
  {
    $campos = [];
    foreach ($saldo as $key => $value) {
      $campos[$value->id_area] = (object)['cultivo' => $value->cultivo, 'saldo' => $value->total, 'mes' => 0];
    }

    foreach ($mes as $key => $value) {
      if (isset($campos[$value->id_area])) {
        $campos[$value->id_area]->mes = $value->total;
      } else {
        $campos[$value->id_area] = (object)['cultivo' => $value->cultivo, 'saldo' => 0, 'mes' => $value->total];
      }
    }

    return $campos;
  }

  public function erCultivosMergue(...$listas)
  {
    $response = [];
    foreach ($listas as $key => $lista) {
      foreach ($lista as $area => $value) {
        if (isset($response[$area])) {
          $response[$area]->saldo += $value->saldo;
          $response[$area]->mes += $value->mes;
        } else {
          $response[$area] = $value;
        }
      }
    }

    return $response;
  }

  public function repartAplicacionGeneral($datos)
  {
    $response = [];
    $aplicacione_gral = [0, 0];
    foreach ($datos as $key => $value) {
      if ($value->cultivo === 'APLICACION GENERAL') {
        $aplicacione_gral[0] += $value->saldo;
        $aplicacione_gral[1] += $value->mes;
      } else {
        $response[] = $value;
      }
    }

    if ($aplicacione_gral[0] > 0 || $aplicacione_gral[1] > 0) {
      $aplicacione_gral[0] = $aplicacione_gral[0]/(count($response)>0? count($response): 1);
      $aplicacione_gral[1] = $aplicacione_gral[1]/(count($response)>0? count($response): 1);
      foreach ($response as $key => $value) {
        $response[$key]->saldo += $aplicacione_gral[0];
        $response[$key]->mes += $aplicacione_gral[1];
      }
    }

    return $response;
  }

  public function erIngresos($sqlFecha, $sqlemp1, $sqlemp2, $sqlarea1)
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
        AND Date(f.fecha) BETWEEN {$sqlFecha} {$sqlemp1} {$sqlarea1}
      GROUP BY a.id_area
      UNION
      SELECT 0 AS id_area, 'INTANGIBLES' AS cultivo, Coalesce(Sum(ci.monto), 0) AS total
      FROM cajachica_ingresos ci
        INNER JOIN cajachica_categorias cc ON cc.id_categoria = ci.id_categoria
      WHERE ci.id_nomenclatura = 11 AND ci.no_caja = 2
        AND Date(ci.fecha) BETWEEN {$sqlFecha} $sqlemp2");
    return $query->result();
  }

  public function erIngresosDescuentos($sqlFecha, $sqlemp1, $sqlarea1)
  {
    $query = $this->db->query(
      "SELECT a.id_area, Coalesce(a.nombre, 'OTROS INGRESOS') AS cultivo, Sum(fp.importe) AS total
      FROM facturacion f
        INNER JOIN facturacion_productos fp ON f.id_factura = fp.id_factura
        LEFT JOIN clasificaciones c ON c.id_clasificacion = fp.id_clasificacion
        LEFT JOIN areas a ON a.id_area = c.id_area
      WHERE f.status <> 'ca' AND f.status <> 'b' AND f.tipo_comprobante = 'egreso'
        AND Date(f.fecha) BETWEEN {$sqlFecha} {$sqlemp1} {$sqlarea1}
      GROUP BY a.id_area");
    return $query->result();
  }

  public function erEgresosCompraFruta($sqlFecha, $sqlemp3, $sqlarea1)
  {
    $query = $this->db->query(
      "SELECT a.id_area, Coalesce(a.nombre, 'OTRA FRUTA') AS cultivo, Sum(b.importe) AS total
      FROM bascula b
        LEFT JOIN areas a ON a.id_area = b.id_area
      WHERE b.status = 't' AND b.intangible = 'f' AND b.tipo = 'en' AND a.tipo = 'fr'
         {$sqlemp3} {$sqlarea1}
         AND Date(b.fecha_bruto) BETWEEN {$sqlFecha}
      GROUP BY a.id_area");
    return $query->result();
  }

  public function erEgresosSalidas($sqlFecha, $sqlemp3, $sqlarea1)
  {
    $query = $this->db->query(
      "SELECT a.id_area, Coalesce(a.nombre, 'COSTO SALIDAS ALMACEN') AS cultivo, Sum(csp.cantidad*csp.precio_unitario) AS total
      FROM compras_salidas cs
        INNER JOIN compras_salidas_productos csp ON cs.id_salida = csp.id_salida
        LEFT JOIN areas a ON a.id_area = cs.id_area
      WHERE (cs.status = 's' OR cs.status = 'b') {$sqlemp3} {$sqlarea1}
        AND Date(cs.fecha_creacion) BETWEEN {$sqlFecha}
      GROUP BY a.id_area");
    return $query->result();
  }

  public function erEgresosGastosDir($sqlFecha, $sqlemp4, $sqlarea1, $intangible = 'f')
  {
    $query = $this->db->query(
      "SELECT a.id_area, Coalesce(a.nombre, 'COSTO GASTOS DIRECTOS') AS cultivo, Sum(c.subtotal) AS total
      FROM compras c
        LEFT JOIN areas a ON a.id_area = c.id_area
      WHERE c.status <> 'ca' AND c.isgasto = 't' {$sqlemp4} {$sqlarea1}
        AND Date(c.fecha) BETWEEN {$sqlFecha} AND c.intangible = '{$intangible}'
      GROUP BY a.id_area, c.intangible");
    return $query->result();
  }

  public function erEgresosGastosOrd($sqlFecha, $sqlemp5, $sqlarea1)
  {
    $query = $this->db->query(
      "SELECT a.id_area, Coalesce(a.nombre, 'COSTO SALIDAS ALMACEN') AS cultivo, Sum(cp.importe) AS total
      FROM compras_ordenes co
        INNER JOIN compras_productos cp ON co.id_orden = cp.id_orden
        LEFT JOIN areas a ON a.id_area = co.id_area
      WHERE (co.status = 'a' OR co.status = 'f') AND co.tipo_orden in('d', 'f', 'oc') {$sqlemp5} {$sqlarea1}
        AND Date(co.fecha_aceptacion) BETWEEN {$sqlFecha}
      GROUP BY a.id_area");
    return $query->result();
  }

  public function erEgresosGastosCajaTry($sqlFecha, $sqlemp2, $sqlarea1)
  {
    $query = $this->db->query(
      "SELECT a.id_area, Coalesce(a.nombre, 'GASTOS CAJA 2') AS cultivo, Sum(cg.monto) AS total
      FROM cajachica_gastos cg
        INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
        LEFT JOIN areas a ON a.id_area = cg.id_areac
      WHERE cg.no_caja = 2 {$sqlemp2} {$sqlarea1}
        AND Date(cg.fecha) BETWEEN {$sqlFecha}
      GROUP BY a.id_area");

    return $query->result();
  }

  public function erEgresosGastosCajaGdl($sqlFecha, $sqlemp2, $sqlarea1)
  {
    $query = $this->db->query(
      "SELECT a.id_area, Coalesce(a.nombre, 'GASTOS CAJA GDL') AS cultivo, Sum(cg.monto) AS total
      FROM otros.bodega_gastos cg
        INNER JOIN cajachica_categorias cc ON cc.id_categoria = cg.id_categoria
        LEFT JOIN areas a ON a.id_area = cg.id_areac
      WHERE cg.no_caja = 1 {$sqlemp2} {$sqlarea1}
        AND Date(cg.fecha) BETWEEN {$sqlFecha}
      GROUP BY a.id_area");
    return $query->result();
  }

  public function erEgresosGastosNomina($sqlFecha, $sqlemp6, $sqlarea1)
  {
    $query = $this->db->query(
      "SELECT a.id_area, Coalesce(a.nombre, 'OTROS') AS cultivo, Sum(nf.total_percepcion-nf.subsidio) AS total
      FROM nomina_fiscal nf
        INNER JOIN usuarios u ON u.id = nf.id_empleado
        LEFT JOIN areas a ON a.id_area = u.id_area
      WHERE {$sqlemp6} Date(nf.fecha) BETWEEN {$sqlFecha} {$sqlarea1}
      GROUP BY a.id_area");
    return $query->result();
  }

  public function erEgresosComisionesBan($sqlFecha, $sqlemp7, $sqlarea1)
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

    $total = 0;
    foreach ($query->result() as $key => $value) {
      if ($sqlarea1 !== '') {
        $total += $value->total / $value->no_areas;
      } else {
        $total += $value->total;
      }
    }

    return $total;
  }

  public function erEgresosNc($sqlFecha, $sqlemp4, $sqlarea1)
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
        {$sqlemp4} {$sqlarea1} AND (c.fecha) BETWEEN {$sqlFecha}
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
        {$sqlemp4} {$sqlarea1} AND (c.fecha) BETWEEN {$sqlFecha}
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

  public function getTotalResultado($datos)
  {
    // Ingresos
    $total_singreso = $total_mingreso = 0;
    foreach ($datos['ingresos'] as $key => $value) {
      $total_singreso += $value->saldo;
      $total_mingreso += $value->mes;
    }

    $total_singresodesc = $total_mingresodesc = 0;
    foreach ($datos['ingresos_descuentos'] as $key => $value) {
      $total_singresodesc += $value->saldo;
      $total_mingresodesc += $value->mes;
    }

    // Egresos
    $total_scostoventa = $total_mcostoventa = 0;
    foreach ($datos['egresos_costos_ventas'] as $key => $value) {
      $total_scostoventa += $value->saldo;
      $total_mcostoventa += $value->mes;
    }

    $total_sgastosgenerales = $total_mgastosgenerales = 0;
    foreach ($datos['egresos_gastos_generales'] as $key => $value) {
      $total_sgastosgenerales += $value->saldo;
      $total_mgastosgenerales += $value->mes;
    }

    $total_sgastosintangibles = $total_mgastosintangibles = 0;
    foreach ($datos['egresos_gastos_intangibles'] as $key => $value) {
      $total_sgastosintangibles += $value->saldo;
      $total_mgastosintangibles += $value->mes;
    }

    $total_scomisionesban = $total_mcomisionesban = 0;
    $total_scomisionesban += $datos['egresos_comisiones_ban']->saldo;
    $total_mcomisionesban += $datos['egresos_comisiones_ban']->mes;

    $total_segresosnc = $total_megresosnc = 0;
    foreach ($datos['egresos_nc'] as $key => $value) {
      $total_segresosnc += $value->saldo;
      $total_megresosnc += $value->mes;
    }

    $response['ingresos'] = ($total_mingreso-$total_mingresodesc)+
                            ($total_singreso-$total_singresodesc);
    $response['egresos'] = ($total_mcostoventa+$total_mgastosgenerales+$total_mgastosintangibles+$total_mcomisionesban-$total_megresosnc)+
                           ($total_scostoventa+$total_sgastosgenerales+$total_sgastosintangibles+$total_scomisionesban-$total_segresosnc);
    $response['resultado'] = $response['ingresos'] - $response['egresos'];
    return $response;
  }

  public function getTotalOfData($datos)
  {
    $total_mes = $total_saldo = 0;
    foreach ($datos as $key => $value) {
      $total_saldo += $value->saldo+$value->mes;
      $total_mes += $value->mes;
    }

    return ['mes' => $total_mes, 'saldo' => $total_saldo];
  }

  public function getDataEstadoResultado($request, $onlyTotal = false)
  {
    $sqlemp1 = $sqlemp2 = $sqlemp3 = $sqlemp4 = $sqlemp5 = $sqlemp6 =
    $sqlemp7 = $sqlemp8 = $sql = '';
    $sqlarea1 = '';
    $res = ['saldo' => [], 'mes' => []];
    $response = [];

    $anio = date("Y");
    $ini_anio = $anio.'-01-01';
    if (!empty($request['ffecha1'])) {
      $fecha = explode('-', $request['ffecha1']);
      $anio = $fecha[0];
      $ini_anio = $anio.'-01-01';
    }
    $mes_actual[1] = !empty($request['ffecha2'])? $request['ffecha2']: date("Y-m-d");
    $mes_actual[0] = substr($mes_actual[1], 0, 8).'01';
    $fin_anio = new DateTime($mes_actual[0]);
    $fin_anio->sub(new DateInterval('P1D'));
    if (!empty($request['did_empresa'])) {
      $sqlemp1 = "AND f.id_empresa = ".$request['did_empresa'];
      $sqlemp2 = "AND cc.id_empresa = ".$request['did_empresa'];
      $sqlemp3 = "AND cs.id_empresa = ".$request['did_empresa'];
      $sqlemp4 = "AND c.id_empresa = ".$request['did_empresa'];
      $sqlemp5 = "AND co.id_empresa = ".$request['did_empresa'];
      $sqlemp6 = " nf.id_empresa = ".$request['did_empresa']." AND ";
      $sqlemp7 = "AND p.id_empresa = ".$request['did_empresa'];
      $sqlemp8 = "AND b.id_empresa = ".$request['did_empresa'];
    }
    if (!empty($request['areaId'])) {
      $sqlarea1 = "AND a.id_area = ".$request['areaId'];
    }

    $sqlFechaSaldo = "'{$ini_anio}' AND '{$fin_anio->format('Y-m-d')}'";
    $sqlFechaMes = "'{$mes_actual[0]}' AND '{$mes_actual[1]}'";

    $response['filtros']['mes'] = MyString::mes(substr($mes_actual[0], 5, 2));

    // Ingresos
    $res['saldo']['ingresos'] = $this->erIngresos($sqlFechaSaldo, $sqlemp1, $sqlemp2, $sqlarea1);
    $res['mes']['ingresos'] = $this->erIngresos($sqlFechaMes, $sqlemp1, $sqlemp2, $sqlarea1);
    $response['ingresos'] = $this->erCultivosAjuste($res['saldo']['ingresos'], $res['mes']['ingresos']);

    // Ingresos descuentos
    $res['saldo']['ingresos_descuentos'] = $this->erIngresosDescuentos($sqlFechaSaldo, $sqlemp1, $sqlarea1);
    $res['mes']['ingresos_descuentos'] = $this->erIngresosDescuentos($sqlFechaMes, $sqlemp1, $sqlarea1);
    $response['ingresos_descuentos'] = $this->erCultivosAjuste($res['saldo']['ingresos_descuentos'], $res['mes']['ingresos_descuentos']);

    // Egresos compra de fruta
    $res['saldo']['egresos_compra_fruta'] = $this->erEgresosCompraFruta($sqlFechaSaldo, $sqlemp8, $sqlarea1);
    $res['mes']['egresos_compra_fruta'] = $this->erEgresosCompraFruta($sqlFechaMes, $sqlemp8, $sqlarea1);
    $response['egresos_compra_fruta'] = $this->erCultivosAjuste($res['saldo']['egresos_compra_fruta'], $res['mes']['egresos_compra_fruta']);

    // Egresos salidas almacén
    $res['saldo']['egresos_salidas'] = $this->erEgresosSalidas($sqlFechaSaldo, $sqlemp3, $sqlarea1);
    $res['mes']['egresos_salidas'] = $this->erEgresosSalidas($sqlFechaMes, $sqlemp3, $sqlarea1);
    $response['egresos_salidas'] = $this->erCultivosAjuste($res['saldo']['egresos_salidas'], $res['mes']['egresos_salidas']);

    // Egresos gastos directos
    $res['saldo']['egresos_gastos_dir'] = $this->erEgresosGastosDir($sqlFechaSaldo, $sqlemp4, $sqlarea1);
    $res['mes']['egresos_gastos_dir'] = $this->erEgresosGastosDir($sqlFechaMes, $sqlemp4, $sqlarea1);
    $response['egresos_gastos_dir'] = $this->erCultivosAjuste($res['saldo']['egresos_gastos_dir'], $res['mes']['egresos_gastos_dir']);

    // Egresos gastos de ordenes
    $res['saldo']['egresos_gastos_ord'] = $this->erEgresosGastosOrd($sqlFechaSaldo, $sqlemp5, $sqlarea1);
    $res['mes']['egresos_gastos_ord'] = $this->erEgresosGastosOrd($sqlFechaMes, $sqlemp5, $sqlarea1);
    $response['egresos_gastos_ord'] = $this->erCultivosAjuste($res['saldo']['egresos_gastos_ord'], $res['mes']['egresos_gastos_ord']);

    $response['egresos_costos_ventas'] = $this->erCultivosMergue($response['egresos_salidas'], $response['egresos_gastos_dir'], $response['egresos_gastos_ord']);
    $response['egresos_costos_ventas'] = $this->repartAplicacionGeneral($response['egresos_costos_ventas']);

    // Egresos gastos de caja tryana
    $res['saldo']['egresos_gastos_caja_try'] = $this->erEgresosGastosCajaTry($sqlFechaSaldo, $sqlemp2, $sqlarea1);
    $res['mes']['egresos_gastos_caja_try'] = $this->erEgresosGastosCajaTry($sqlFechaMes, $sqlemp2, $sqlarea1);
    $response['egresos_gastos_caja_try'] = $this->erCultivosAjuste($res['saldo']['egresos_gastos_caja_try'], $res['mes']['egresos_gastos_caja_try']);

    // Egresos gastos de caja Gdl
    $res['saldo']['egresos_gastos_caja_gdl'] = $this->erEgresosGastosCajaGdl($sqlFechaSaldo, $sqlemp2, $sqlarea1);
    $res['mes']['egresos_gastos_caja_gdl'] = $this->erEgresosGastosCajaGdl($sqlFechaMes, $sqlemp2, $sqlarea1);
    $response['egresos_gastos_caja_gdl'] = $this->erCultivosAjuste($res['saldo']['egresos_gastos_caja_gdl'], $res['mes']['egresos_gastos_caja_gdl']);

    // Egresos nomina
    $res['saldo']['egresos_gastos_nomina'] = $this->erEgresosGastosNomina($sqlFechaSaldo, $sqlemp6, $sqlarea1);
    $res['mes']['egresos_gastos_nomina'] = $this->erEgresosGastosNomina($sqlFechaMes, $sqlemp6, $sqlarea1);
    $response['egresos_gastos_nomina'] = $this->erCultivosAjuste($res['saldo']['egresos_gastos_nomina'], $res['mes']['egresos_gastos_nomina']);

    $response['egresos_gastos_generales'] = $this->erCultivosMergue($response['egresos_gastos_caja_try'], $response['egresos_gastos_caja_gdl'], $response['egresos_gastos_nomina']);
    $response['egresos_gastos_generales'] = $this->repartAplicacionGeneral($response['egresos_gastos_generales']);

    // Egresos comisiones bancarias
    $res['saldo']['egresos_comisiones_ban'] = $this->erEgresosComisionesBan($sqlFechaSaldo, $sqlemp7, $sqlarea1);
    $res['mes']['egresos_comisiones_ban'] = $this->erEgresosComisionesBan($sqlFechaMes, $sqlemp7, $sqlarea1);
    $response['egresos_comisiones_ban'] = (object)['cultivo' => 'COMISIONES BANCARIAS', 'saldo' => $res['saldo']['egresos_comisiones_ban'], 'mes' => $res['mes']['egresos_comisiones_ban']];

    // Egresos gastos directos intangibles
    $res['saldo']['egresos_gastos_intangibles'] = $this->erEgresosGastosDir($sqlFechaSaldo, $sqlemp4, $sqlarea1, 't');
    $res['mes']['egresos_gastos_intangibles'] = $this->erEgresosGastosDir($sqlFechaMes, $sqlemp4, $sqlarea1, 't');
    $response['egresos_gastos_intangibles'] = $this->erCultivosAjuste($res['saldo']['egresos_gastos_intangibles'], $res['mes']['egresos_gastos_intangibles']);

    // Egresos notas de credito
    $res['saldo']['egresos_nc'] = $this->erEgresosNc($sqlFechaSaldo, $sqlemp4, $sqlarea1);
    $res['mes']['egresos_nc'] = $this->erEgresosNc($sqlFechaMes, $sqlemp4, $sqlarea1);
    $response['egresos_nc'] = $this->erCultivosAjuste($res['saldo']['egresos_nc'], $res['mes']['egresos_nc']);

    if ($onlyTotal) {
      return $this->getTotalResultado($response);
    } else{
      return $response;
    }
  }

  public function estado_resultado_pdf($request)
  {
    $datos = $this->getDataEstadoResultado($request);
    // echo "<pre>";
    // var_dump($datos);
    // echo "</pre>";exit;

    $this->load->model('empresas_model');
    if (!empty($request['did_empresa']))
      $empresa = $this->empresas_model->getInfoEmpresa($request['did_empresa']);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;

    if (isset($empresa) && $empresa['info']->logo !== '')
      $pdf->logo = $empresa['info']->logo;

    $pdf->titulo1 = isset($empresa)? $empresa['info']->nombre_fiscal: 'Todas las empresas';
    $pdf->titulo2 = "Estado de resultado";
    $pdf->titulo3 = $datos['filtros']['mes'];

    $pdf->AliasNbPages();
    $pdf->AddPage();
    $pdf->SetY(30);

    $aligns = array('L', 'R', 'R', 'R', 'R');
    $widths = array(150);
    $widths2 = array(70, 30, 10, 40, 10);
    $widths3 = array(70, 30, 10, 40, 10);

    $pdf->SetTextColor(0,0,0);
    $pdf->SetAligns($aligns);

    $pdf->SetFont('Arial','B', 9);
    $pdf->SetWidths($widths);
    $pdf->SetX(6);
    $pdf->Row(['INGRESOS'], false, false);

    $pdf->SetFont('Arial','B', 9);
    $pdf->SetWidths($widths);
    $pdf->SetX(12);
    $pdf->Row(['Ingresos'], false, false);

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetWidths($widths2);
    $pdf->SetX(12);
    $pdf->Row(['Cultivo', 'Mes', '%', 'Acumulado', '%'], false, true);
    $pdf->SetFont('Arial', '', 7);
    $total_singreso = $total_mingreso = 0;
    $totales = $this->getTotalOfData($datos['ingresos']);
    foreach ($datos['ingresos'] as $key => $value) {
      $pdf->SetX(12);
      $pdf->Row([
        $value->cultivo,
        MyString::formatoNumero($value->mes, 2, '', false),
        MyString::formatoNumero(($totales['mes']>0? $value->mes/$totales['mes']: 0)*100, 2, '', false),
        MyString::formatoNumero($value->saldo+$value->mes, 2, '', false),
        MyString::formatoNumero(($totales['saldo']>0? (($value->saldo+$value->mes)/$totales['saldo']): 0)*100, 2, '', false),
      ], false, true);
      $total_singreso += $value->saldo+$value->mes;
      $total_mingreso += $value->mes;
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(12);
    $pdf->Row([
      'TOTAL',
      MyString::formatoNumero($total_mingreso, 2, '', false),
      '',
      MyString::formatoNumero($total_singreso, 2, '', false),
      ''
    ], false, true);

    // --
    $pdf->SetFont('Arial','B', 9);
    $pdf->SetWidths($widths);
    $pdf->SetX(12);
    $pdf->Row(['Devoluciones y descuentos'], false, false);

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetWidths($widths2);
    $pdf->SetX(12);
    $pdf->Row(['Cultivo', 'Mes', '%', 'Acumulado', '%'], false, true);
    $pdf->SetFont('Arial', '', 7);
    $total_singresodesc = $total_mingresodesc = 0;
    $totales = $this->getTotalOfData($datos['ingresos_descuentos']);
    foreach ($datos['ingresos_descuentos'] as $key => $value) {
      $pdf->SetX(12);
      $pdf->Row([
        $value->cultivo,
        MyString::formatoNumero($value->mes, 2, '', false),
        MyString::formatoNumero(($totales['mes']>0? $value->mes/$totales['mes']: 0)*100, 2, '', false),
        MyString::formatoNumero($value->saldo+$value->mes, 2, '', false),
        MyString::formatoNumero(($totales['saldo']>0? (($value->saldo+$value->mes)/$totales['saldo']): 0)*100, 2, '', false),
      ], false, true);
      $total_singresodesc += $value->saldo+$value->mes;
      $total_mingresodesc += $value->mes;
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(12);
    $pdf->Row([
      'TOTAL',
      MyString::formatoNumero($total_mingresodesc, 2, '', false),
      '',
      MyString::formatoNumero($total_singresodesc, 2, '', false),
      ''
    ], false, true);

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetWidths($widths3);
    $pdf->SetX(12);
    $pdf->Row([
      'TOTAL',
      MyString::formatoNumero($total_mingreso-$total_mingresodesc, 2, '', false),
      '',
      MyString::formatoNumero($total_singreso-$total_singresodesc, 2, '', false),
      '',
    ], false, false);

    // ----------------------- EGRESO
    $pdf->SetFont('Arial','B', 9);
    $pdf->SetWidths($widths);
    $pdf->SetX(6);
    $pdf->Row(['EGRESOS'], false, false);

    $pdf->SetFont('Arial','B', 9);
    $pdf->SetWidths($widths);
    $pdf->SetX(12);
    $pdf->Row(['Compra de fruta'], false, false);

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetWidths($widths2);
    $pdf->SetX(12);
    $pdf->Row(['Cultivo', 'Mes', '%', 'Acumulado', '%'], false, true);
    $pdf->SetFont('Arial', '', 7);
    $total_scomprafruta = $total_mcomprafruta = 0;
    $totales = $this->getTotalOfData($datos['egresos_compra_fruta']);
    foreach ($datos['egresos_compra_fruta'] as $key => $value) {
      $pdf->SetX(12);
      $pdf->Row([
        $value->cultivo,
        MyString::formatoNumero($value->mes, 2, '', false),
        MyString::formatoNumero(($totales['mes']>0? $value->mes/$totales['mes']: 0)*100, 2, '', false),
        MyString::formatoNumero($value->saldo, 2, '', false),
        MyString::formatoNumero(($totales['saldo']>0? (($value->saldo+$value->mes)/$totales['saldo']): 0)*100, 2, '', false),
      ], false, true);
      $total_scomprafruta += $value->saldo+$value->mes;
      $total_mcomprafruta += $value->mes;
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(12);
    $pdf->Row([
      'TOTAL',
      MyString::formatoNumero($total_mcomprafruta, 2, '', false),
      '',
      MyString::formatoNumero($total_scomprafruta, 2, '', false),
      '',
    ], false, true);

    $pdf->SetFont('Arial','B', 9);
    $pdf->SetWidths($widths);
    $pdf->SetX(12);
    $pdf->Row(['Costo de venta'], false, false);

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetWidths($widths2);
    $pdf->SetX(12);
    $pdf->Row(['Cultivo', 'Mes', '%', 'Acumulado', '%'], false, true);
    $pdf->SetFont('Arial', '', 7);
    $total_scostoventa = $total_mcostoventa = 0;
    $totales = $this->getTotalOfData($datos['egresos_costos_ventas']);
    foreach ($datos['egresos_costos_ventas'] as $key => $value) {
      $pdf->SetX(12);
      $pdf->Row([
        $value->cultivo,
        MyString::formatoNumero($value->mes, 2, '', false),
        MyString::formatoNumero(($totales['mes']>0? $value->mes/$totales['mes']: 0)*100, 2, '', false),
        MyString::formatoNumero($value->saldo, 2, '', false),
        MyString::formatoNumero(($totales['saldo']>0? (($value->saldo+$value->mes)/$totales['saldo']): 0)*100, 2, '', false),
      ], false, true);
      $total_scostoventa += $value->saldo+$value->mes;
      $total_mcostoventa += $value->mes;
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(12);
    $pdf->Row([
      'TOTAL',
      MyString::formatoNumero($total_mcostoventa, 2, '', false),
      '',
      MyString::formatoNumero($total_scostoventa, 2, '', false),
      '',
    ], false, true);

    $pdf->SetFont('Arial','B', 9);
    $pdf->SetWidths($widths);
    $pdf->SetX(12);
    $pdf->Row(['Gastos generales'], false, false);

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetWidths($widths2);
    $pdf->SetX(12);
    $pdf->Row(['Cultivo', 'Mes', '%', 'Acumulado', '%'], false, true);
    $pdf->SetFont('Arial', '', 7);
    $total_sgastosgenerales = $total_mgastosgenerales = 0;
    $totales = $this->getTotalOfData($datos['egresos_gastos_generales']);
    foreach ($datos['egresos_gastos_generales'] as $key => $value) {
      $pdf->SetX(12);
      $pdf->Row([
        $value->cultivo,
        MyString::formatoNumero($value->mes, 2, '', false),
        MyString::formatoNumero(($totales['mes']>0? $value->mes/$totales['mes']: 0)*100, 2, '', false),
        MyString::formatoNumero($value->saldo, 2, '', false),
        MyString::formatoNumero(($totales['saldo']>0? (($value->saldo+$value->mes)/$totales['saldo']): 0)*100, 2, '', false),
      ], false, true);
      $total_sgastosgenerales += $value->saldo+$value->mes;
      $total_mgastosgenerales += $value->mes;
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(12);
    $pdf->Row([
      'TOTAL',
      MyString::formatoNumero($total_mgastosgenerales, 2, '', false),
      '',
      MyString::formatoNumero($total_sgastosgenerales, 2, '', false),
      '',
    ], false, true);

    $pdf->SetFont('Arial','B', 9);
    $pdf->SetWidths($widths);
    $pdf->SetX(12);
    $pdf->Row(['Gastos intangibles'], false, false);

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetWidths($widths2);
    $pdf->SetX(12);
    $pdf->Row(['Cultivo', 'Mes', '%', 'Acumulado', '%'], false, true);
    $pdf->SetFont('Arial', '', 7);
    $total_sgastosintangibles = $total_mgastosintangibles = 0;
    $totales = $this->getTotalOfData($datos['egresos_gastos_intangibles']);
    foreach ($datos['egresos_gastos_intangibles'] as $key => $value) {
      $pdf->SetX(12);
      $pdf->Row([
        $value->cultivo,
        MyString::formatoNumero($value->mes, 2, '', false),
        MyString::formatoNumero(($totales['mes']>0? $value->mes/$totales['mes']: 0)*100, 2, '', false),
        MyString::formatoNumero($value->saldo, 2, '', false),
        MyString::formatoNumero(($totales['saldo']>0? (($value->saldo+$value->mes)/$totales['saldo']): 0)*100, 2, '', false),
      ], false, true);
      $total_sgastosintangibles += $value->saldo+$value->mes;
      $total_mgastosintangibles += $value->mes;
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(12);
    $pdf->Row([
      'TOTAL',
      MyString::formatoNumero($total_mgastosintangibles, 2, '', false),
      '',
      MyString::formatoNumero($total_sgastosintangibles, 2, '', false),
      '',
    ], false, true);

    $pdf->SetFont('Arial','B', 9);
    $pdf->SetWidths($widths);
    $pdf->SetX(12);
    $pdf->Row(['Comisiones bancarias'], false, false);

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetWidths($widths2);
    $pdf->SetX(12);
    $pdf->Row(['Cultivo', 'Mes', '%', 'Acumulado', '%'], false, true);
    $pdf->SetFont('Arial', '', 7);
    $total_scomisionesban = $total_mcomisionesban = 0;
    $pdf->SetX(12);
    $pdf->Row([
      $datos['egresos_comisiones_ban']->cultivo,
      MyString::formatoNumero($datos['egresos_comisiones_ban']->mes, 2, '', false),
      '',
      MyString::formatoNumero($datos['egresos_comisiones_ban']->saldo, 2, '', false),
      '',
    ], false, true);
    $total_scomisionesban += $datos['egresos_comisiones_ban']->saldo+$datos['egresos_comisiones_ban']->mes;
    $total_mcomisionesban += $datos['egresos_comisiones_ban']->mes;
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(12);
    $pdf->Row([
      'TOTAL',
      MyString::formatoNumero($total_scomisionesban, 2, '', false),
      '',
      MyString::formatoNumero($total_mcomisionesban, 2, '', false),
      '',
    ], false, true);

    $pdf->SetFont('Arial','B', 9);
    $pdf->SetWidths($widths);
    $pdf->SetX(12);
    $pdf->Row(['Devoluciones y descuentos'], false, false);

    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetWidths($widths2);
    $pdf->SetX(12);
    $pdf->Row(['Cultivo', 'Mes', '%', 'Acumulado', '%'], false, true);
    $pdf->SetFont('Arial', '', 7);
    $total_segresosnc = $total_megresosnc = 0;
    $totales = $this->getTotalOfData($datos['egresos_nc']);
    foreach ($datos['egresos_nc'] as $key => $value) {
      $pdf->SetX(12);
      $pdf->Row([
        $value->cultivo,
        MyString::formatoNumero($value->mes, 2, '', false),
        MyString::formatoNumero(($totales['mes']>0? $value->mes/$totales['mes']: 0)*100, 2, '', false),
        MyString::formatoNumero($value->saldo, 2, '', false),
        MyString::formatoNumero(($totales['saldo']>0? (($value->saldo+$value->mes)/$totales['saldo']): 0)*100, 2, '', false),
      ], false, true);
      $total_segresosnc += $value->saldo+$value->mes;
      $total_megresosnc += $value->mes;
    }
    $pdf->SetFont('Arial', 'B', 7);
    $pdf->SetX(12);
    $pdf->Row([
      'TOTAL',
      MyString::formatoNumero($total_megresosnc, 2, '', false),
      '',
      MyString::formatoNumero($total_segresosnc, 2, '', false),
      '',
    ], false, true);

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetWidths($widths3);
    $pdf->SetX(12);
    $pdf->Row([
      'TOTAL',
      MyString::formatoNumero($total_mcomprafruta+$total_mcostoventa+$total_mgastosgenerales+$total_mgastosintangibles+$total_mcomisionesban-$total_megresosnc, 2, '', false),
      '',
      MyString::formatoNumero($total_scomprafruta+$total_scostoventa+$total_sgastosgenerales+$total_sgastosintangibles+$total_scomisionesban-$total_segresosnc, 2, '', false),
      '',
    ], false, false);

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetWidths([70, 30, 50, 40]);
    $pdf->SetX(12);
    $pdf->Row([
      'RESULTADO',
      MyString::formatoNumero(
        ($total_mingreso-$total_mingresodesc) -
        ($total_mcomprafruta+$total_mcostoventa+$total_mgastosgenerales+$total_mgastosintangibles+$total_mcomisionesban-$total_megresosnc)
      , 2, '', false),
      MyString::formatoNumero(
        ($total_singreso-$total_singresodesc) -
        ($total_scomprafruta+$total_scostoventa+$total_sgastosgenerales+$total_sgastosintangibles+$total_scomisionesban-$total_segresosnc)
      , 2, '', false),
      MyString::formatoNumero(
        (
          ($total_mingreso-$total_mingresodesc)+
          ($total_singreso-$total_singresodesc)
        ) - (
          ($total_mcomprafruta+$total_mcostoventa+$total_mgastosgenerales+$total_mgastosintangibles+$total_mcomisionesban-$total_megresosnc)+
          ($total_scomprafruta+$total_scostoventa+$total_sgastosgenerales+$total_sgastosintangibles+$total_scomisionesban-$total_segresosnc)
        )
      , 2, '', false)
    ], false, false);

    $pdf->Output('estado_resultado.pdf', 'I');
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