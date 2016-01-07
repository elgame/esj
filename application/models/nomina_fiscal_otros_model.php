<?php
class nomina_fiscal_otros_model extends nomina_fiscal_model{

	function __construct(){
		parent::__construct();
	}

  private $rptPrestamosSaldos = 0;

  public function getPrestamoTrabajador(&$pdf, $usuarioId, $fecha1, $fecha2, $todos = false)
  {
    if ($usuarioId)
    {
      $empleado = $this->usuarios_model->get_usuario_info($usuarioId);
      $empresa = $this->empresas_model->getInfoEmpresa($empleado['info'][0]->id_empresa);

      $semanas = $this->semanasDelAno($empresa['info']->dia_inicia_semana);

      $fecha1 = $fecha1 ? $fecha1 : date('Y-m-d');
      $fecha2 = $fecha2 ? $fecha2 : date('Y-m-d');

      $sql = '';

      if ($fecha1 != '')
      {
        $sql .= " AND DATE(np.fecha) >= '{$fecha1}'";
      }

      if ($fecha2 != '')
      {
        $semana = array();
        foreach ($semanas as $s)
        {
          if (strtotime($fecha2) <= strtotime($s['fecha_final']))
          {
            $semana = $s;
            break;
          }
        }

        $sql .= " AND DATE(np.fecha) <= '{$fecha2}'";
      }

      if ($usuarioId && $usuarioId !== '')
      {
        $sql .= " AND np.id_usuario = {$usuarioId}";
      }

      $having = '';
      if ( ! $todos)
      {
        $having .= " HAVING (np.prestado - COALESCE(SUM(nfp.monto), 0)) > 0";
      }

      $data = $this->db->query(
        "SELECT np.id_prestamo, np.id_usuario, np.prestado, np.pago_semana, np.status, DATE(np.fecha) as fecha, DATE(np.inicio_pago) as inicio_pago, np.prestado - COALESCE(SUM(nfp.monto), 0) as total_pagado
        FROM nomina_prestamos as np
        LEFT JOIN nomina_fiscal_prestamos as nfp ON nfp.id_prestamo = np.id_prestamo AND (nfp.anio < {$semana['anio']} OR (nfp.anio <= {$semana['anio']} AND nfp.semana <= {$semana['semana']}))
        WHERE '1' {$sql}
        GROUP BY np.id_prestamo, np.id_usuario, np.prestado, np.pago_semana, np.status, DATE(np.fecha), DATE(np.inicio_pago)
        {$having}
        ORDER BY fecha ASC
        ")->result();

      foreach ($data as $key => $prestamo)
      {
        $prestamo->prestamos = $this->db->query(
          "SELECT nfp.anio, nfp.semana, nfp.monto
          FROM nomina_fiscal_prestamos as nfp
          WHERE id_prestamo = $prestamo->id_prestamo AND
            (nfp.anio < {$semana['anio']} OR (nfp.anio <= {$semana['anio']} AND nfp.semana <= {$semana['semana']}))
          ORDER BY (nfp.anio, nfp.semana)
          ")->result();
      }

      $pdf->SetFont('Helvetica','B', 8);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(100, 100));
      $pdf->Row(array($empleado['info'][0]->nombre.' '.$empleado['info'][0]->apellido_paterno.' '.
        $empleado['info'][0]->apellido_paterno, $empleado['info'][0]->nombre_fiscal), false, true, null, 2, 1);

      $columnas = array(
        'n' => array('FECHA', 'FECHA INICIO PAGO', 'PRESTADO', 'PAGO X SEMANA', 'SALDO'),
        'w' => array(40, 40, 40, 40, 40),
        'a' => array('L', 'L', 'L', 'L', 'R')
      );

      $pdf->SetFont('Helvetica','B', 8);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetAligns($columnas['a']);
      $pdf->SetWidths($columnas['w']);
      $pdf->Row($columnas['n'], 1, 1, null, 2, 1);

      $y = $pdf->GetY();

      $columnas2 = array(
        'n' => array('AÑO', 'SEMANA', 'MONTO'),
        'w' => array(40, 40, 40),
        'a' => array('L', 'L', 'R')
      );

      foreach ($data as $key => $prestamo)
      {
        $pdf->SetFont('Helvetica','', 8);
        if($pdf->GetY() >= $pdf->limiteY){
          $pdf->AddPage();
          $pdf->SetFont('Helvetica','B', 8);
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->Row($columnas['n'], false, false, null, 2, 1);
        }

        $pdf->SetFont('Helvetica','', 8);
        $pdf->SetXY(6, $pdf->GetY());

        $data2 = array(
          $prestamo->fecha,
          $prestamo->inicio_pago,
          String::formatoNumero($prestamo->prestado),
          String::formatoNumero($prestamo->pago_semana),
          String::formatoNumero($prestamo->total_pagado),
        );

        $this->rptPrestamosSaldos += $prestamo->total_pagado;

        $pdf->Row($data2, false, true, null, 2, 1);

        if ($prestamo->prestamos)
        {
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetFont('Helvetica','B', 8);
          $pdf->SetXY(86, $pdf->GetY() + 2);
          $pdf->SetFillColor(242, 242, 242);
          $pdf->SetTextColor(0, 0, 0);
          $pdf->SetAligns($columnas2['a']);
          // $pdf->SetWidths($columnas2['w']);
          $pdf->Row($columnas2['n'], 1, 1, null, 2, 1);

          foreach ($prestamo->prestamos as $p)
          {
            if($pdf->GetY() >= $pdf->limiteY)
              $pdf->AddPage();

            $pdf->SetXY(86, $pdf->GetY());
            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Row(array($p->anio, $p->semana, $p->monto), 1, 1, null, 2, 1);
          }

          $pdf->SetY($pdf->GetY() + 2);
        }
      }
    }
  }

  public function rptTrabajadoresPrestamosPdf($usuarioId, $fecha1, $fecha2, $todos = false, $id_empresa=0)
  {
    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');

    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    // $pdf->titulo1 S= $empresa['info']->nombre_fiscal;
    // $pdf->logo = $empresa['info']->logo;
    $pdf->titulo2 = "Todos los trabajadores";
    $pdf->titulo3 = "Reporte de Prestamos del {$fecha1} al {$fecha2}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $fecha1 = $fecha1 ? $fecha1 : date('Y-m-d');
    $fecha2 = $fecha2 ? $fecha2 : date('Y-m-d');

    $sql = '';
    if ($id_empresa > 0)
      $sql = " AND u.id_empresa = ".$id_empresa;

    $empleados = $this->db->query("SELECT u.id_empresa, np.id_usuario
      FROM nomina_prestamos np
        INNER JOIN usuarios u ON u.id = np.id_usuario
      WHERE np.status = 't' AND Date(np.fecha) >= '{$fecha1}' AND Date(np.fecha) <= '{$fecha2}' {$sql}
      GROUP BY u.id_empresa, np.id_usuario
      ORDER BY u.id_empresa ASC, np.id_usuario ASC")->result();

    foreach ($empleados as $key => $value) {
      $this->getPrestamoTrabajador($pdf, $value->id_usuario, $fecha1, $fecha2, $todos);
    }

    $pdf->SetFont('Helvetica','B', 9);
    $pdf->SetXY(126, $pdf->GetY());
    $pdf->SetAligns(array('R', 'R'));
    $pdf->SetWidths(array(40, 40));
    $pdf->Row(array("Saldo General", String::formatoNumero($this->rptPrestamosSaldos)), false, true, null, 2, 1);

    $pdf->Output('Reporte_Prestamos_Trabajador.pdf', 'I');
  }


  public function rpt_dim()
  {
      if (!isset($_GET['empresaId']{0}) || !isset($_GET['anio']{0}))
        exit;

      include APPPATH.'libraries/PHPExcel/PHPExcel/IOFactory.php';

      $objReader = PHPExcel_IOFactory::createReader('Excel2007');
      $objPHPExcel = $objReader->load(APPPATH.'media/dim_datos_fuente.xlsx');

      $result = $this->db->query("SELECT t.id, t.nombre, t.apellido_paterno, t.apellido_materno, t.rfc, t.curp, max(semana_max) AS semana_max,
              min(semana_min) AS semana_min, Sum(t.dias) AS dias, Sum(t.subsidio) AS subsidio, Sum(t.sueldo_semanal) AS sueldo_semanal,
              Sum(t.isr) AS isr, Sum(t.aguinaldo) AS aguinaldo, Sum(t.aguinaldo_grabable) AS aguinaldo_grabable, Sum(t.aguinaldo_exento) AS aguinaldo_exento,
              Sum(t.ptu) AS ptu, Sum(t.ptu_exento) AS ptu_exento, Sum(t.ptu_grabable) AS ptu_grabable,
              Sum(t.vacaciones) AS vacaciones, Sum(t.prima_vacacional_grabable) AS prima_vacacional_grabable,
              Sum(t.prima_vacacional_exento) AS prima_vacacional_exento, Sum(t.prima_vacacional) AS prima_vacacional, Sum(t.anios) AS anios
        FROM
        (
              SELECT u.id, u.nombre, u.apellido_paterno, u.apellido_materno, u.rfc, u.curp, max(date_part('month', nf.fecha_inicio)) AS semana_max,
                    min(date_part('month', nf.fecha_inicio)) AS semana_min, Sum(nf.dias_trabajados) AS dias, Sum(nf.subsidio) AS subsidio, Sum(nf.sueldo_semanal) AS sueldo_semanal,
                    Sum(nf.isr) AS isr, Sum(nf.aguinaldo) AS aguinaldo, Sum(nf.aguinaldo_grabable) AS aguinaldo_grabable, Sum(nf.aguinaldo_exento) AS aguinaldo_exento,
                    Sum(nf.ptu) AS ptu, Sum(nf.ptu_exento) AS ptu_exento, Sum(nf.ptu_grabable) AS ptu_grabable,
                    Sum(nf.vacaciones) AS vacaciones, Sum(nf.prima_vacacional_grabable) AS prima_vacacional_grabable,
                    Sum(nf.prima_vacacional_exento) AS prima_vacacional_exento, Sum(nf.prima_vacacional) AS prima_vacacional,
                    date_part('years', age(COALESCE(u.fecha_salida, now()), COALESCE(u.fecha_imss, u.fecha_entrada))) AS anios
              FROM nomina_fiscal nf INNER JOIN usuarios u ON u.id = nf.id_empleado
              WHERE nf.id_empresa = {$_GET['empresaId']} AND nf.anio = {$_GET['anio']} AND nf.esta_asegurado = 't'
              GROUP BY u.id
              UNION
              SELECT u.id, u.nombre, u.apellido_paterno, u.apellido_materno, u.rfc, u.curp, max(date_part('month', nf.fecha_inicio)) AS semana_max,
                    min(date_part('month', nf.fecha_inicio)) AS semana_min, 0 AS dias, 0 AS subsidio, 0 AS sueldo_semanal,
                    Sum(nf.isr) AS isr, Sum(nf.aguinaldo) AS aguinaldo, Sum(nf.aguinaldo_grabable) AS aguinaldo_grabable, Sum(nf.aguinaldo_exento) AS aguinaldo_exento,
                    0 AS ptu, 0 AS ptu_exento, 0 AS ptu_grabable,
                    0 AS vacaciones, 0 AS prima_vacacional_grabable, 0 AS prima_vacacional_exento, 0 AS prima_vacacional, 0 AS anios
              FROM nomina_aguinaldo nf INNER JOIN usuarios u ON u.id = nf.id_empleado
              WHERE nf.id_empresa = {$_GET['empresaId']} AND nf.anio = {$_GET['anio']} AND nf.esta_asegurado = 't'
              GROUP BY u.id
              UNION
              SELECT u.id, u.nombre, u.apellido_paterno, u.apellido_materno, u.rfc, u.curp, max(date_part('month', nf.fecha_inicio)) AS semana_max,
                    min(date_part('month', nf.fecha_inicio)) AS semana_min, 0 AS dias, 0 AS subsidio, 0 AS sueldo_semanal,
                    Sum(nf.isr) AS isr, 0 AS aguinaldo, 0 AS aguinaldo_grabable, 0 AS aguinaldo_exento,
                    Sum(nf.ptu) AS ptu, Sum(nf.ptu_exento) AS ptu_exento, Sum(nf.ptu_grabable) AS ptu_grabable,
                    0 AS vacaciones, 0 AS prima_vacacional_grabable, 0 AS prima_vacacional_exento, 0 AS prima_vacacional, 0 AS anios
              FROM nomina_ptu nf INNER JOIN usuarios u ON u.id = nf.id_empleado
              WHERE nf.id_empresa = {$_GET['empresaId']} AND nf.anio = {$_GET['anio']} AND nf.esta_asegurado = 't'
              GROUP BY u.id
              UNION
              SELECT u.id, u.nombre, u.apellido_paterno, u.apellido_materno, u.rfc, u.curp, max(date_part('month', nf.fecha_salida)) AS semana_max,
                    min(date_part('month', nf.fecha_salida)) AS semana_min, Sum(nf.dias_trabajados) AS dias, Sum(nf.subsidio) AS subsidio, Sum(nf.sueldo_semanal) AS sueldo_semanal,
                    Sum(nf.isr) AS isr, Sum(nf.aguinaldo) AS aguinaldo, Sum(nf.aguinaldo_grabable) AS aguinaldo_grabable, Sum(nf.aguinaldo_exento) AS aguinaldo_exento,
                    0 AS ptu, 0 AS ptu_exento, 0 AS ptu_grabable,
                    Sum(nf.vacaciones) AS vacaciones, Sum(nf.prima_vacacional_grabable) AS prima_vacacional_grabable,
                    Sum(nf.prima_vacacional_exento) AS prima_vacacional_exento, Sum(nf.prima_vacacional) AS prima_vacacional, 0 AS anios
              FROM finiquito nf INNER JOIN usuarios u ON u.id = nf.id_empleado
              WHERE nf.id_empresa = {$_GET['empresaId']} AND Date(nf.fecha_salida) BETWEEN '{$_GET['anio']}-01-01' AND '{$_GET['anio']}-12-31'
              GROUP BY u.id
        ) t
        GROUP BY t.id, t.nombre, t.apellido_paterno, t.apellido_materno, t.rfc, t.curp");

      $row = 4;
      foreach ($result->result() as $key => $value) {
        $gravado = $value->sueldo_semanal+$value->vacaciones+$value->aguinaldo_grabable+$value->prima_vacacional_grabable+$value->ptu_grabable;
        $exento = $value->aguinaldo_exento+$value->prima_vacacional_exento+$value->ptu_exento;
        $objPHPExcel->setActiveSheetIndex(0)->getCellByColumnAndRow(0, $row)->setValueExplicit((strlen($value->semana_min)==1?'0':'').$value->semana_min, PHPExcel_Cell_DataType::TYPE_STRING); // -- Mes inicial
        $objPHPExcel->setActiveSheetIndex(0)->getCellByColumnAndRow(1, $row)->setValueExplicit((strlen($value->semana_max)==1?'0':'').$value->semana_max, PHPExcel_Cell_DataType::TYPE_STRING); // -- Mes final
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(2, $row, $value->rfc); // -- rfc
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(3, $row, $value->curp); // -- curp
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(4, $row, $value->apellido_paterno); // -- apellido paterno
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(5, $row, $value->apellido_materno); // -- apellido materno
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(6, $row, $value->nombre); // -- nombres
        $objPHPExcel->setActiveSheetIndex(0)->getCellByColumnAndRow(7, $row)->setValueExplicit('02', PHPExcel_Cell_DataType::TYPE_STRING); // area geografica
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(8, $row, '2'); // Si el patrón realizó cálculo anual
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(9, $row, '1'); // Tarifa utilizada del ejercicio
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(10, $row, '2'); // Tarifa 1991
        $objPHPExcel->setActiveSheetIndex(0)->getCellByColumnAndRow(11, $row)->setValueExplicit('0.0000', PHPExcel_Cell_DataType::TYPE_STRING); // Proporción del subsidio
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(12, $row, '2'); // Sindicalizado
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(13, $row, '0'); // Si es asimilado a salarios
        $objPHPExcel->setActiveSheetIndex(0)->getCellByColumnAndRow(14, $row)->setValueExplicit('06', PHPExcel_Cell_DataType::TYPE_STRING); // Entidad Federativa
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(15, $row, ''); // RFC otros patrones1
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(16, $row, ''); // RFC otros patrones2
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(17, $row, ''); // RFC otros patrones3
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(18, $row, ''); // RFC otros patrones4
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(19, $row, ''); // RFC otros patrones5
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(20, $row, ''); // RFC otros patrones6
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(21, $row, ''); // RFC otros patrones7
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(22, $row, ''); // RFC otros patrones8
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(23, $row, ''); // RFC otros patrones9
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(24, $row, ''); // RFC otros patrones10
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(25, $row, '2'); // Pagos por separación
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(26, $row, '2'); // Asimilados a salarios
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(27, $row, '1'); // Pagos del patrón efectuados a sus trab
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(28, $row, '0'); // Ingresos totales por pago en parcialidades
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(29, $row, '0'); // Monto diario percibido por jubilaciones
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(30, $row, '0'); // Cantidad que se hubiera percibido
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(31, $row, '0'); // Monto total del pago en una sola exhibición
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(32, $row, round($value->dias)); // -- Días
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(33, $row, '0'); // Ingresos exentos
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(34, $row, '0'); // Ingresos gravables
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(35, $row, '0'); // Ingresos acumulables
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(36, $row, '0'); // Ingresos no acumulables
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(37, $row, '0'); // Impuesto retenido
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(38, $row, '0'); // Monto total pagado de otros pagos
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(39, $row, round($value->anios)); // -- Número de años de servicio
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(40, $row, '0'); // Ingresos exentos
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(41, $row, '0'); // Ingresos gravados
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(42, $row, '0'); // Ingresos acumulables (mes)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(43, $row, '0'); // Impuesto correspondiente(mes)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(44, $row, '0'); // Ingresos no acumulables
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(45, $row, '0'); // Impuesto retenido
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(46, $row, '0'); // Ingresos asimilados a salarios
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(47, $row, '0'); // Impuesto asimilados a salarios retenido durante el ejer.
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(48, $row, round($value->sueldo_semanal+$value->vacaciones)); // -- Sueldos, salarios, rayas y jornales (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(49, $row, '0'); // -- Sueldos, salarios, rayas y jornales (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(50, $row, round($value->aguinaldo_grabable)); // -- Gratificación anual (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(51, $row, round($value->aguinaldo_exento)); // -- Gratificación anual (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(52, $row, '0'); // Viáticos y gastos de viaje (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(53, $row, '0'); // Viáticos y gastos de viaje (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(54, $row, '0'); // Tiempo extraordinario (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(55, $row, '0'); // Tiempo extraordinario (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(56, $row, round($value->prima_vacacional_grabable)); // -- Prima vacacional (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(57, $row, round($value->prima_vacacional_exento)); // -- Prima vacacional (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(58, $row, '0'); // Prima dominical (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(59, $row, '0'); // Prima dominical (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(60, $row, round($value->ptu_grabable)); // -- PTU (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(61, $row, round($value->ptu_exento)); // -- PTU (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(62, $row, '0'); // Reembolso de gastos médicos,(Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(63, $row, '0'); // Reembolso de gastos médicos,(Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(64, $row, '0'); // Fondo de ahorro (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(65, $row, '0'); // Fondo de ahorro (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(66, $row, '0'); // Caja de ahorro (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(67, $row, '0'); // Caja de ahorro (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(68, $row, '0'); // -- Vales para despensa (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(69, $row, '0'); // -- Vales para despensa (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(70, $row, '0'); // Ayuda para gastos de funeral (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(71, $row, '0'); // Ayuda para gastos de funeral (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(72, $row, '0'); // Contribuciones a cargo del trab(Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(73, $row, '0'); // Contribuciones a cargo del trab(Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(74, $row, '0'); // -- Premios por puntualidad (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(75, $row, '0'); // -- Premios por puntualidad (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(76, $row, '0'); // Prima de seguro de vida (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(77, $row, '0'); // Prima de seguro de vida (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(78, $row, '0'); // Seguro de gastos médicos mayores (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(79, $row, '0'); // Seguro de gastos médicos mayores (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(80, $row, '0'); // Vales para restaurante (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(81, $row, '0'); // Vales para restaurante (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(82, $row, '0'); // Vales para gasolina (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(83, $row, '0'); // Vales para gasolina (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(84, $row, '0'); // Vales para ropa (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(85, $row, '0'); // Vales para ropa (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(86, $row, '0'); // Ayuda para renta(Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(87, $row, '0'); // Ayuda para renta(Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(88, $row, '0'); // Ayuda para artículos escolares (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(89, $row, '0'); // Ayuda para artículos escolares (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(90, $row, '0'); // Dotación o ayuda para anteojos (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(91, $row, '0'); // Dotación o ayuda para anteojos (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(92, $row, '0'); // Ayuda para transporte (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(93, $row, '0'); // Ayuda para transporte (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(94, $row, '0'); // Cuotas sindicales pagadas patron(Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(95, $row, '0'); // Cuotas sindicales pagadas patron(Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(96, $row, '0'); // Subsidios por incapacidad (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(97, $row, '0'); // Subsidios por incapacidad (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(98, $row, '0'); // Becas para trabajadores(Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(99, $row, '0'); // Becas para trabajadores(Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(100, $row, '0'); // Pagos efectuados por otros empleadores(Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(101, $row, '0'); // Pagos efectuados por otros empleadores(Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(102, $row, '0'); // -- Otros ingresos por salarios (Gravado)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(103, $row, '0'); // -- Otros ingresos por salarios (Exento)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(104, $row, round($gravado)); // -- Suma del ingreso GRAVADO
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(105, $row, round($exento)); // -- Suma del ingreso EXENTO
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(106, $row, round(($value->isr>0?$value->isr:0))); // -- Impuesto retenido durante el ejercicio
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(107, $row, '0'); // Impuesto retenido por otro(s) patrón(es)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(108, $row, '0'); // Saldo a favor en el ejercicio que declara que el patrón compensará durante el siguiente ejercicio
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(109, $row, '0'); // Saldo a favor del ejercicio anterior no compensado durante el ejercicio que declara
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(110, $row, '0'); // Suma de las cantidades que por concepto de crédito al salario le correspondió al trabajador
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(111, $row, '0'); // Crédito al salario entregado en efectivo
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(112, $row, '0'); // Monto total de ingresos obtenidos Prev Social
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(113, $row, '0'); // Exentos Previsión social
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(114, $row, round($gravado+$exento)); // -- Suma de ingresos por sueldos y salarios
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(115, $row, '0'); // Monto del impuesto local a los ingresos por sueldos y salarios
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(116, $row, round($value->subsidio)); // -- Monto del subsidio para el empleo entregado en el ejercicio
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(117, $row, '0'); // Total de las aportaciones voluntarias deducibles
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(118, $row, '0'); // Impuesto conforme a la tarifa anual
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(119, $row, '0'); // Monto del subsidio acreditable (No aplica)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(120, $row, '0'); // Monto del subsidio no acreditable (No aplica)
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(121, $row, '0'); // Impuesto sobre ingresos acumulables
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(122, $row, '0'); // Impuesto sobre ingresos no acumulables
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(123, $row, '0'); // Impuesto local a los ingresos por sueldos, salarios y en general
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(124, $row, '0'); // -- Monto del subsidio para el empleo que le correspondió al trabajador durante el ejercicio
        $row++;
      }

      $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
      $objWriter->save(APPPATH.'media/dim_datos.xlsx');
      $zipname = APPPATH.'media/reporte_DIM.zip';
      $zip = new ZipArchive;
      if ($zip->open($zipname, ZipArchive::OVERWRITE) === TRUE) {
        $zip->addFile(APPPATH.'media/dim_template.xlsm', 'dim_template.xlsm');
        $zip->addFile(APPPATH.'media/dim_datos.xlsx', 'dim_datos.xlsx');
        $zip->close();
      }

      header('Content-Type: application/zip');
      header('Content-disposition: attachment; filename=reporte_DIM.zip');
      header('Content-Length: ' . filesize($zipname));
      readfile($zipname);

  }
}