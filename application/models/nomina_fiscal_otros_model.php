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
        $empleado['info'][0]->apellido_materno, $empleado['info'][0]->nombre_fiscal), false, true, null, 2, 1);

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
          MyString::formatoNumero($prestamo->prestado),
          MyString::formatoNumero($prestamo->pago_semana),
          MyString::formatoNumero($prestamo->total_pagado),
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
    $pdf->Row(array("Saldo General", MyString::formatoNumero($this->rptPrestamosSaldos)), false, true, null, 2, 1);

    $pdf->Output('Reporte_Prestamos_Trabajador.pdf', 'I');
  }

  public function rptTrabajadoresPrestamosXls1($usuarioId, $fecha1, $fecha2, $todos = false, $id_empresa=0)
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=rpt_prestamos_trabajador.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    if ($usuarioId)
    {
      $this->load->model('empresas_model');
      $this->load->model('usuarios_model');
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

      $titulo1 = $empresa['info']->nombre_fiscal;
      $titulo2 = $empleado['info'][0]->nombre.' '.$empleado['info'][0]->apellido_paterno.' '.$empleado['info'][0]->apellido_materno;
      $titulo3 = "Reporte de Prestamos del {$fecha1} al {$fecha2}";

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
        </tr>';

      $columnas = '<tr style="font-weight:bold">
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">FECHA</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">FECHA INICIO PAGO</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">PRESTADO</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">PAGO X SEMANA</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">SALDO</td>
      </tr>';
      $html .= $columnas;

      $columnas2 = '<tr style="font-weight:bold">
        <td style="width:150px;border:1px solid #000;"></td>
        <td style="width:150px;border:1px solid #000;"></td>
        <td style="width:150px;border:1px solid #000;">AÑO</td>
        <td style="width:150px;border:1px solid #000;">SEMANA</td>
        <td style="width:150px;border:1px solid #000;">MONTO</td>
      </tr>';

      foreach ($data as $key => $prestamo)
      {
        $html .= '<tr>
          <td style="width:150px;border:1px solid #000;">'.$prestamo->fecha.'</td>
          <td style="width:150px;border:1px solid #000;">'.$prestamo->inicio_pago.'</td>
          <td style="width:150px;border:1px solid #000;">'.$prestamo->prestado.'</td>
          <td style="width:150px;border:1px solid #000;">'.$prestamo->pago_semana.'</td>
          <td style="width:150px;border:1px solid #000;">'.$prestamo->total_pagado.'</td>
        </tr>';

        if ($prestamo->prestamos)
        {
          $html .= $columnas2;

          foreach ($prestamo->prestamos as $p)
          {
            $html .= '<tr>
              <td style="width:150px;border:1px solid #000;"></td>
              <td style="width:150px;border:1px solid #000;"></td>
              <td style="width:150px;border:1px solid #000;">'.$p->anio.'</td>
              <td style="width:150px;border:1px solid #000;">'.$p->semana.'</td>
              <td style="width:150px;border:1px solid #000;">'.$p->monto.'</td>
            </tr>';
          }
        }
      }

      echo $html;
    }
  }

  public function getPrestamoTrabajadorXls($usuarioId, $fecha1, $fecha2, $todos = false)
  {
    $html = '';
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

      $html .= '<tr style="font-weight:bold">
        <td colspan="2" style="border:1px solid #000;background-color: #cccccc;">'.$empleado['info'][0]->nombre.' '.$empleado['info'][0]->apellido_paterno.' '.$empleado['info'][0]->apellido_materno.'</td>
        <td colspan="3" style="border:1px solid #000;background-color: #cccccc;">'.$empleado['info'][0]->nombre_fiscal.'</td>
      </tr>';

      $columnas = '<tr style="font-weight:bold">
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">FECHA</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">FECHA INICIO PAGO</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">PRESTADO</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">PAGO X SEMANA</td>
        <td style="width:150px;border:1px solid #000;background-color: #cccccc;">SALDO</td>
      </tr>';
      $html .= $columnas;

      $columnas2 = '<tr style="font-weight:bold">
        <td style="width:150px;border:1px solid #000;"></td>
        <td style="width:150px;border:1px solid #000;"></td>
        <td style="width:150px;border:1px solid #000;">AÑO</td>
        <td style="width:150px;border:1px solid #000;">SEMANA</td>
        <td style="width:150px;border:1px solid #000;">MONTO</td>
      </tr>';

      foreach ($data as $key => $prestamo)
      {
        $html .= '<tr style="font-weight:bold">
          <td style="width:150px;border:1px solid #000;">'.$prestamo->fecha.'</td>
          <td style="width:150px;border:1px solid #000;">'.$prestamo->inicio_pago.'</td>
          <td style="width:150px;border:1px solid #000;">'.$prestamo->prestado.'</td>
          <td style="width:150px;border:1px solid #000;">'.$prestamo->pago_semana.'</td>
          <td style="width:150px;border:1px solid #000;">'.$prestamo->total_pagado.'</td>
        </tr>';

        $this->rptPrestamosSaldos += $prestamo->total_pagado;

        if ($prestamo->prestamos)
        {
          $html .= $columnas2;

          foreach ($prestamo->prestamos as $p)
          {
            $html .= '<tr style="font-weight:bold">
              <td style="width:150px;border:1px solid #000;"></td>
              <td style="width:150px;border:1px solid #000;"></td>
              <td style="width:150px;border:1px solid #000;">'.$p->anio.'</td>
              <td style="width:150px;border:1px solid #000;">'.$p->semana.'</td>
              <td style="width:150px;border:1px solid #000;">'.$p->monto.'</td>
            </tr>';
          }
        }
      }
    }

    return $html;
  }

  public function rptTrabajadoresPrestamosXls($usuarioId, $fecha1, $fecha2, $todos = false, $id_empresa=0)
  {
    header('Content-type: application/vnd.ms-excel; charset=utf-8');
    header("Content-Disposition: attachment; filename=rpt_prestamos_trabajador.xls");
    header("Pragma: no-cache");
    header("Expires: 0");

    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');

    $empresa = $this->empresas_model->getInfoEmpresa(2);

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = "Todos los trabajadores";
    $titulo3 = "Reporte de Prestamos del {$fecha1} al {$fecha2}";

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
        </tr>';

    foreach ($empleados as $key => $value) {
      $html .= $this->getPrestamoTrabajadorXls($value->id_usuario, $fecha1, $fecha2, $todos);
    }

    $html .= '
        <tr style="font-weight:bold">
          <td colspan="3"></td>
          <td style="border:1px solid #000;">Saldo General</td>
          <td style="border:1px solid #000;">'.$this->rptPrestamosSaldos.'</td>
        </tr>
      </tbody>
    </table>';

    echo $html;
  }


  public function setSubsidioCausado($anio, $empresa)
  {
    $this->load->library('nomina');

    $configuraciones = $this->configuraciones($anio);
    $this->nomina
        ->setEmpresaConfig($configuraciones['nomina'][0])
        ->setVacacionesConfig($configuraciones['vacaciones'])
        ->setSalariosZonas($configuraciones['salarios_zonas'][0])
        ->setClavesPatron($configuraciones['cuentas_contpaq'])
        ->setTablasIsr($configuraciones['tablas_isr']);

    $result = $this->db->query("SELECT id_empleado, id_empresa, anio, semana, (sueldo_semanal+prima_vacacional_grabable+aguinaldo_grabable+ptu_grabable+horas_extras_grabable) AS total_gravado
      FROM nomina_fiscal
      WHERE id_empresa = {$empresa} AND uuid <> '' and anio = {$anio}");
    foreach ($result->result() as $key => $nomina) {
      $subsidio = $this->nomina->getSubsidioIsr($nomina->total_gravado, 0);
      $this->db->update('nomina_fiscal', ['subsidio_pagado' => $subsidio['subsidioCausado']],
        "id_empleado = {$nomina->id_empleado} AND id_empresa = {$nomina->id_empresa} AND anio = {$nomina->anio} AND semana = {$nomina->semana}");
    }
    echo "ok";
    exit;
  }

  public function data_calc_anual($empresaId, $anio, $tipo='tabla')
  {
    $result = $this->db->query("SELECT t.id, t.nombre, t.apellido_paterno, t.apellido_materno, t.rfc, t.curp,
            max(t.mes_max) AS mes_max, min(t.mes_min) AS mes_min,
            max(t.semana_max) AS semana_max, min(t.semana_min) AS semana_min,
            Sum(t.semanas) AS semanas,
            Sum(t.dias) AS dias, Sum(t.subsidio) AS subsidio, Sum(t.subsidio_causado) AS subsidio_causado,
            Sum(t.sueldo_semanal) AS sueldo_semanal,
            Sum(t.isr) AS isr, Sum(t.aguinaldo) AS aguinaldo, Sum(t.aguinaldo_grabable) AS aguinaldo_grabable, Sum(t.aguinaldo_exento) AS aguinaldo_exento,
            Sum(t.ptu) AS ptu, Sum(t.ptu_exento) AS ptu_exento, Sum(t.ptu_grabable) AS ptu_grabable,
            Sum(t.vacaciones) AS vacaciones, Sum(t.prima_vacacional_grabable) AS prima_vacacional_grabable,
            Sum(t.prima_vacacional_exento) AS prima_vacacional_exento, Sum(t.prima_vacacional) AS prima_vacacional, Sum(t.anios) AS anios,
            Sum(t.pasistencia) AS pasistencia, Sum(t.fondo_ahorro) AS fondo_ahorro, max(t.dias_anio) AS dias_anio
      FROM
      (
            SELECT u.id, u.nombre, u.apellido_paterno, u.apellido_materno, u.rfc, u.curp,
                  max(date_part('month', nf.fecha_inicio)) AS mes_max, min(date_part('month', nf.fecha_inicio)) AS mes_min,
                  max(nf.semana) AS semana_max, min(nf.semana) AS semana_min,
                  Count(nf.id_empleado) AS semanas,
                  Sum(nf.dias_trabajados) AS dias, Sum(nf.subsidio) AS subsidio,
                  Sum(nf.subsidio_pagado) AS subsidio_causado, Sum(nf.sueldo_semanal) AS sueldo_semanal,
                  Sum(nf.isr) AS isr, Sum(nf.aguinaldo) AS aguinaldo, Sum(nf.aguinaldo_grabable) AS aguinaldo_grabable, Sum(nf.aguinaldo_exento) AS aguinaldo_exento,
                  Sum(nf.ptu) AS ptu, Sum(nf.ptu_exento) AS ptu_exento, Sum(nf.ptu_grabable) AS ptu_grabable,
                  Sum(nf.vacaciones) AS vacaciones, Sum(nf.prima_vacacional_grabable) AS prima_vacacional_grabable,
                  Sum(nf.prima_vacacional_exento) AS prima_vacacional_exento, Sum(nf.prima_vacacional) AS prima_vacacional,
                  date_part('years', age(COALESCE(u.fecha_salida, now()), COALESCE(u.fecha_imss, u.fecha_entrada))) AS anios,
                  Sum(nf.pasistencia) AS pasistencia, Sum(nf.fondo_ahorro) AS fondo_ahorro,
                  DATE_PART('day', max(nf.fecha_final) - min(nf.fecha_inicio)) AS dias_anio
            FROM nomina_fiscal nf INNER JOIN usuarios u ON u.id = nf.id_empleado
            WHERE nf.id_empresa = {$empresaId} AND nf.anio = {$anio} AND nf.esta_asegurado = 't'
            GROUP BY u.id
            UNION
            SELECT u.id, u.nombre, u.apellido_paterno, u.apellido_materno, u.rfc, u.curp,
                  max(date_part('month', nf.fecha_inicio)) AS mes_max,
                  min(date_part('month', nf.fecha_inicio)) AS mes_min,
                  max(nf.semana) AS semana_max, min(nf.semana) AS semana_min,
                  0 AS semanas,
                  0 AS dias, 0 AS subsidio, 0 AS subsidio_causado, 0 AS sueldo_semanal,
                  Sum(nf.isr) AS isr, Sum(nf.aguinaldo) AS aguinaldo, Sum(nf.aguinaldo_grabable) AS aguinaldo_grabable, Sum(nf.aguinaldo_exento) AS aguinaldo_exento,
                  0 AS ptu, 0 AS ptu_exento, 0 AS ptu_grabable,
                  0 AS vacaciones, 0 AS prima_vacacional_grabable, 0 AS prima_vacacional_exento, 0 AS prima_vacacional, 0 AS anios,
                  0 AS pasistencia, 0 AS fondo_ahorro, 0 AS dias_anio
            FROM nomina_aguinaldo nf INNER JOIN usuarios u ON u.id = nf.id_empleado
            WHERE nf.id_empresa = {$empresaId} AND nf.anio = {$anio} AND nf.esta_asegurado = 't'
            GROUP BY u.id
            UNION
            SELECT u.id, u.nombre, u.apellido_paterno, u.apellido_materno, u.rfc, u.curp,
                  max(date_part('month', nf.fecha_inicio)) AS mes_max,
                  min(date_part('month', nf.fecha_inicio)) AS mes_min,
                  max(nf.semana) AS semana_max, min(nf.semana) AS semana_min,
                  0 AS semanas,
                  0 AS dias, 0 AS subsidio, 0 AS subsidio_causado, 0 AS sueldo_semanal,
                  Sum(nf.isr) AS isr, 0 AS aguinaldo, 0 AS aguinaldo_grabable, 0 AS aguinaldo_exento,
                  Sum(nf.ptu) AS ptu, Sum(nf.ptu_exento) AS ptu_exento, Sum(nf.ptu_grabable) AS ptu_grabable,
                  0 AS vacaciones, 0 AS prima_vacacional_grabable, 0 AS prima_vacacional_exento, 0 AS prima_vacacional, 0 AS anios,
                  0 AS pasistencia, 0 AS fondo_ahorro, 0 AS dias_anio
            FROM nomina_ptu nf INNER JOIN usuarios u ON u.id = nf.id_empleado
            WHERE nf.id_empresa = {$empresaId} AND nf.anio = {$anio} AND nf.esta_asegurado = 't'
            GROUP BY u.id
            UNION
            SELECT u.id, u.nombre, u.apellido_paterno, u.apellido_materno, u.rfc, u.curp,
                  max(date_part('month', nf.fecha_salida)) AS mes_max,
                  min(date_part('month', nf.fecha_salida)) AS mes_min,
                  0 AS semana_max, 1 AS semana_min,
                  0 AS semanas,
                  Sum(nf.dias_trabajados) AS dias, Sum(nf.subsidio) AS subsidio,
                  0 AS subsidio_causado, Sum(nf.sueldo_semanal) AS sueldo_semanal,
                  Sum(nf.isr) AS isr, Sum(nf.aguinaldo) AS aguinaldo, Sum(nf.aguinaldo_grabable) AS aguinaldo_grabable, Sum(nf.aguinaldo_exento) AS aguinaldo_exento,
                  0 AS ptu, 0 AS ptu_exento, 0 AS ptu_grabable,
                  Sum(nf.vacaciones) AS vacaciones, Sum(nf.prima_vacacional_grabable) AS prima_vacacional_grabable,
                  Sum(nf.prima_vacacional_exento) AS prima_vacacional_exento, Sum(nf.prima_vacacional) AS prima_vacacional, 0 AS anios,
                  0 AS pasistencia, 0 AS fondo_ahorro, 0 AS dias_anio
            FROM finiquito nf INNER JOIN usuarios u ON u.id = nf.id_empleado
            WHERE nf.id_empresa = {$empresaId} AND Date(nf.fecha_salida) BETWEEN '{$anio}-01-01' AND '{$anio}-12-31'
            GROUP BY u.id
      ) t
      GROUP BY t.id, t.nombre, t.apellido_paterno, t.apellido_materno, t.rfc, t.curp
      HAVING Sum(t.semanas) > 50 --max(semana_max) = 12 AND min(semana_min) = 1
      ");
    $trabajadores = $result->result();

    $this->load->model('nomina_fiscal_model');
    $configuracion = $this->nomina_fiscal_model->configuraciones($anio);

    $dias_anio = 365; //max(array_column($trabajadores, 'dias_anio'));
    $rows_xls = '';
    foreach ($trabajadores as $key => $value) {
      // PTU
      $topeExcento = 15 * $configuracion['salarios_zonas'][0]->zona_a;
      if ($value->ptu > $topeExcento)
      {
        $ptuGravado = $value->ptu - $topeExcento;
        $ptuExcento = $topeExcento;
      }
      else
      {
        $ptuGravado = 0;
        $ptuExcento = $value->ptu;
      }

      // Aguinaldo
      $topeExcento = 30 * floatval($configuracion['salarios_zonas'][0]->zona_a);
      if ($value->aguinaldo > $topeExcento)
      {
        $aguinaldoGravado = $value->aguinaldo - $topeExcento;
        $aguinaldoExcento = $topeExcento;
      }
      else
      {
        $aguinaldoGravado = 0;
        $aguinaldoExcento = $value->aguinaldo;
      }

      // Prima
      $topeExcento = 15 * floatval($configuracion['salarios_zonas'][0]->zona_a);
      if ($value->prima_vacacional > $topeExcento)
      {
        $primaGravado = $value->prima_vacacional - $topeExcento;
        $primaExcento = $topeExcento;
      }
      else
      {
        $primaGravado = 0;
        $primaExcento = $value->prima_vacacional;
      }
      // var_dump($ptuGravado, $value->ptu_grabable);

      // ingresos_gravados/365 eso buscar en la tabla los limites
      $total_gravado = $value->sueldo_semanal + $aguinaldoGravado + $ptuGravado + $primaGravado + $value->pasistencia;
      $value->total_gravado = $total_gravado;
      $gravado_diario = ($total_gravado/$dias_anio);
      $rango_isr = $this->db->query("SELECT id_art_113, lim_inferior, lim_superior, cuota_fija, porcentaje
                                 FROM nomina_diaria_art_113
                                 WHERE lim_inferior <= {$gravado_diario} AND lim_superior >= {$gravado_diario} LIMIT 1")->row();
      $calculo_isr = ((($gravado_diario - $rango_isr->lim_inferior) * ($rango_isr->porcentaje / 100)) + $rango_isr->cuota_fija) * $dias_anio;
      $total_isr_sub_guardado = $value->isr - $value->subsidio;
      $total_isr_sub = $calculo_isr - $value->subsidio; // sub causado
      $res_isr_sub = $total_isr_sub_guardado - $total_isr_sub;


      $value->total_isr_sub = $res_isr_sub;
      // $value->total_isr = $value->isr;
      // $value->total_sub = $value->subsidio;


      if ($key == 0) {
        $rows_xls .= implode(',', array_keys((array)$value))."\n";
      }
      $rows_xls .= implode(',', array_values((array)$value))."\n";
    }

    if ($tipo === 'tabla') {
      return $trabajadores;
    } elseif ($tipo === 'descargar') {
      header("Content-type: text/csv");
      header("Content-Disposition: attachment; filename=file.csv");
      header("Pragma: no-cache");
      header("Expires: 0");
      echo $rows_xls;
      exit;
    } elseif ($tipo === 'guardar') {
      $inserts = [];
      foreach ($trabajadores as $key => $value) {
        $inserts[] = [
          'id_empleado' => $value->id,
          'id_empresa'  => $empresaId,
          'anio'        => $anio,
          'monto'       => round(abs($value->total_isr_sub), 2),
          'aplicado'    => 0,
          'tipo'        => ($value->total_isr_sub >= 0? 'f': 't'),
        ];
      }
      $this->db->insert_batch('nomina_calculo_anual', $inserts);
    }
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
              Sum(t.prima_vacacional_exento) AS prima_vacacional_exento, Sum(t.prima_vacacional) AS prima_vacacional, Sum(t.anios) AS anios,
              Sum(t.pasistencia) AS pasistencia, Sum(t.fondo_ahorro) AS fondo_ahorro
        FROM
        (
              SELECT u.id, u.nombre, u.apellido_paterno, u.apellido_materno, u.rfc, u.curp, max(date_part('month', nf.fecha_inicio)) AS semana_max,
                    min(date_part('month', nf.fecha_inicio)) AS semana_min, Sum(nf.dias_trabajados) AS dias, Sum(nf.subsidio) AS subsidio, Sum(nf.sueldo_semanal) AS sueldo_semanal,
                    Sum(nf.isr) AS isr, Sum(nf.aguinaldo) AS aguinaldo, Sum(nf.aguinaldo_grabable) AS aguinaldo_grabable, Sum(nf.aguinaldo_exento) AS aguinaldo_exento,
                    Sum(nf.ptu) AS ptu, Sum(nf.ptu_exento) AS ptu_exento, Sum(nf.ptu_grabable) AS ptu_grabable,
                    Sum(nf.vacaciones) AS vacaciones, Sum(nf.prima_vacacional_grabable) AS prima_vacacional_grabable,
                    Sum(nf.prima_vacacional_exento) AS prima_vacacional_exento, Sum(nf.prima_vacacional) AS prima_vacacional,
                    date_part('years', age(COALESCE(u.fecha_salida, now()), COALESCE(u.fecha_imss, u.fecha_entrada))) AS anios,
                    Sum(nf.pasistencia) AS pasistencia, Sum(nf.fondo_ahorro) AS fondo_ahorro
              FROM nomina_fiscal nf INNER JOIN usuarios u ON u.id = nf.id_empleado
              WHERE nf.id_empresa = {$_GET['empresaId']} AND nf.anio = {$_GET['anio']} AND nf.esta_asegurado = 't'
              GROUP BY u.id
              UNION
              SELECT u.id, u.nombre, u.apellido_paterno, u.apellido_materno, u.rfc, u.curp, max(date_part('month', nf.fecha_inicio)) AS semana_max,
                    min(date_part('month', nf.fecha_inicio)) AS semana_min, 0 AS dias, 0 AS subsidio, 0 AS sueldo_semanal,
                    Sum(nf.isr) AS isr, Sum(nf.aguinaldo) AS aguinaldo, Sum(nf.aguinaldo_grabable) AS aguinaldo_grabable, Sum(nf.aguinaldo_exento) AS aguinaldo_exento,
                    0 AS ptu, 0 AS ptu_exento, 0 AS ptu_grabable,
                    0 AS vacaciones, 0 AS prima_vacacional_grabable, 0 AS prima_vacacional_exento, 0 AS prima_vacacional, 0 AS anios,
                    0 AS pasistencia, 0 AS fondo_ahorro
              FROM nomina_aguinaldo nf INNER JOIN usuarios u ON u.id = nf.id_empleado
              WHERE nf.id_empresa = {$_GET['empresaId']} AND nf.anio = {$_GET['anio']} AND nf.esta_asegurado = 't'
              GROUP BY u.id
              UNION
              SELECT u.id, u.nombre, u.apellido_paterno, u.apellido_materno, u.rfc, u.curp, max(date_part('month', nf.fecha_inicio)) AS semana_max,
                    min(date_part('month', nf.fecha_inicio)) AS semana_min, 0 AS dias, 0 AS subsidio, 0 AS sueldo_semanal,
                    Sum(nf.isr) AS isr, 0 AS aguinaldo, 0 AS aguinaldo_grabable, 0 AS aguinaldo_exento,
                    Sum(nf.ptu) AS ptu, Sum(nf.ptu_exento) AS ptu_exento, Sum(nf.ptu_grabable) AS ptu_grabable,
                    0 AS vacaciones, 0 AS prima_vacacional_grabable, 0 AS prima_vacacional_exento, 0 AS prima_vacacional, 0 AS anios,
                    0 AS pasistencia, 0 AS fondo_ahorro
              FROM nomina_ptu nf INNER JOIN usuarios u ON u.id = nf.id_empleado
              WHERE nf.id_empresa = {$_GET['empresaId']} AND nf.anio = {$_GET['anio']} AND nf.esta_asegurado = 't'
              GROUP BY u.id
              UNION
              SELECT u.id, u.nombre, u.apellido_paterno, u.apellido_materno, u.rfc, u.curp, max(date_part('month', nf.fecha_salida)) AS semana_max,
                    min(date_part('month', nf.fecha_salida)) AS semana_min, Sum(nf.dias_trabajados) AS dias, Sum(nf.subsidio) AS subsidio, Sum(nf.sueldo_semanal) AS sueldo_semanal,
                    Sum(nf.isr) AS isr, Sum(nf.aguinaldo) AS aguinaldo, Sum(nf.aguinaldo_grabable) AS aguinaldo_grabable, Sum(nf.aguinaldo_exento) AS aguinaldo_exento,
                    0 AS ptu, 0 AS ptu_exento, 0 AS ptu_grabable,
                    Sum(nf.vacaciones) AS vacaciones, Sum(nf.prima_vacacional_grabable) AS prima_vacacional_grabable,
                    Sum(nf.prima_vacacional_exento) AS prima_vacacional_exento, Sum(nf.prima_vacacional) AS prima_vacacional, 0 AS anios,
                    0 AS pasistencia, 0 AS fondo_ahorro
              FROM finiquito nf INNER JOIN usuarios u ON u.id = nf.id_empleado
              WHERE nf.id_empresa = {$_GET['empresaId']} AND Date(nf.fecha_salida) BETWEEN '{$_GET['anio']}-01-01' AND '{$_GET['anio']}-12-31'
              GROUP BY u.id
        ) t
        GROUP BY t.id, t.nombre, t.apellido_paterno, t.apellido_materno, t.rfc, t.curp");

      $row = 4;
      foreach ($result->result() as $key => $value) {
        $gravado = $value->sueldo_semanal+$value->vacaciones+$value->aguinaldo_grabable+$value->prima_vacacional_grabable+$value->ptu_grabable;
        $exento = $value->aguinaldo_exento+$value->prima_vacacional_exento+$value->ptu_exento+$value->pasistencia;
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
        $objPHPExcel->setActiveSheetIndex(0)->setCellValueByColumnAndRow(75, $row, round($value->pasistencia)); // -- Premios por puntualidad (Exento)
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


  public function getCuadroAntiguedadData()
  {
    $sql = '';

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND u.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    $facturas = $this->db->query(
    "SELECT
        id, COALESCE(u.apellido_paterno, '') AS apellido_paterno, COALESCE(u.apellido_materno, '') AS apellido_materno, u.nombre as nombre,
        u.rfc, u.salario_diario, u.no_seguro, Date(u.fecha_entrada) AS fecha_entrada,
        u.email, u.curp, u.calle, u.numero, u.colonia, u.municipio, u.estado, u.cp, u.fecha_salida,
        u.nacionalidad, u.estado_civil, u.cuenta_cpi, u.salario_diario, u.infonavit, u.salario_diario_real, u.fecha_imss, u.telefono,
        u.id_departamente, ud.nombre AS departamento, Date(u.fecha_nacimiento) AS fecha_nacimiento,
        (DATE_PART('year', NOW()) - DATE_PART('year', u.fecha_entrada)) AS antiguedad, up.nombre AS puesto,
        (SELECT dias FROM nomina_configuracion_vacaciones WHERE (DATE_PART('year', NOW()) - DATE_PART('year', u.fecha_entrada)) >= anio1 AND (DATE_PART('year', NOW()) - DATE_PART('year', u.fecha_entrada)) <= anio2 ) AS dias_vacaciones
      FROM usuarios AS u
        INNER JOIN usuarios_departamento ud ON u.id_departamente = ud.id_departamento
        LEFT JOIN usuarios_puestos up ON up.id_puesto = u.id_puesto
      WHERE u.user_nomina = 't' AND u.status = 't' {$sql}
    ");
    $response = $facturas->result();

    return $response;
  }
  public function getCuadroAntiguedadXls($show = false)
  {
    if (!$show) {
      header('Content-type: application/vnd.ms-excel; charset=utf-8');
      header("Content-Disposition: attachment; filename=cuadro_antiguedad.xls");
      header("Pragma: no-cache");
      header("Expires: 0");
    }

    $res = $this->getCuadroAntiguedadData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = $empresa['info']->nombre_fiscal;
    $titulo2 = 'Cuadro General de Antigüedad de los Trabajadores';
    $titulo3 = "";


    $html = '<table>
      <tbody>
        <tr>
          <td colspan="26" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="26" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="26" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="26"></td>
        </tr>';

    foreach($res as $key => $item){
      if ($key == 0) {

        $html .= '<tr style="font-weight:bold">
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">NOMBRE</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">A PATERNO</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">A MATERNO</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">RFC</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">CURP</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">NSS</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">DEPARTAMENTO</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">PUESTO</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">S.D.</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">F DE INGRESO</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">F DE IMSS</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">F DE SALIDA</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">F DE NACIMIENTO</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">ANTIGUEDAD</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">D VACACIONES</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">CALLE</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">NUMERO</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">COLONIA</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">MUNICIPIO</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">ESTADO</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">CP</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">NACIONALIDAD</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">ESTADO CIVIL</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">SALARIO DIARIO</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">INFONAVIT</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">SALARIO REAL</td>
        </tr>';
      }

      $html .= '<tr>
          <td style="width:150px;border:1px solid #000;">'.$item->nombre.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->apellido_paterno.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->apellido_materno.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->rfc.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->curp.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->no_seguro.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->departamento.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->puesto.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->salario_diario.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->fecha_entrada.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->fecha_imss.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->fecha_salida.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->fecha_nacimiento.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->antiguedad.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->dias_vacaciones.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->calle.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->numero.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->colonia.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->municipio.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->estado.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->cp.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->nacionalidad.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->estado_civil.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->salario_diario.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->infonavit.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->salario_diario_real.'</td>
        </tr>';
    }

    $html .= '
      </tbody>
    </table>';

    echo $html;
  }


  public function getAcumuladoNominasEmpleadosData()
  {
    $sql = '';

    $fecha1 = (isset($_GET['fechaini']) ? $_GET['fechaini'] : date("Y-m-01"));
    $fecha2 = (isset($_GET['fechaend']) ? $_GET['fechaend'] : date("Y-m-d"));

    $this->load->model('empresas_model');
    $client_default = $this->empresas_model->getDefaultEmpresa();
    $_GET['did_empresa'] = (isset($_GET['did_empresa']) ? $_GET['did_empresa'] : $client_default->id_empresa);
    $_GET['dempresa']    = (isset($_GET['dempresa']) ? $_GET['dempresa'] : $client_default->nombre_fiscal);
    if($this->input->get('did_empresa') != ''){
      $sql .= " AND nf.id_empresa = '".$this->input->get('did_empresa')."'";
    }

    if($this->input->get('dregistro_patronal') != ''){
      $sql .= " AND nf.registro_patronal = '".$this->input->get('dregistro_patronal')."'";
    }

    $facturas = $this->db->query(
    "SELECT
        (u.nombre || ' ' || u.apellido_paterno || ' ' || u.apellido_materno) AS trabajador,
        Sum(nf.dias_trabajados) AS dias_trabajados, Sum(nf.subsidio) AS subsidio,
        Sum(nf.sueldo_semanal) AS sueldo_semanal, Sum(nf.bonos) AS bonos,
        Sum(nf.otros) AS otros, Sum(nf.vacaciones) AS vacaciones,
        Sum(nf.prima_vacacional) AS prima_vacacional, Sum(nf.aguinaldo) AS aguinaldo,
        Sum(nf.fondo_ahorro) AS fondo_ahorro, Sum(nf.pasistencia) AS pasistencia,
        Sum(nf.imss) AS imss, Sum(nf.vejez) AS vejez, Sum(nf.isr) AS isr,
        Sum(nf.infonavit) AS infonavit, Sum(nf.prestamos) AS prestamos,
        Sum(nf.descuento_playeras) AS descuento_playeras, Sum(nf.descuento_otros) AS descuento_otros,
        Sum(nf.descuento_cocina) AS descuento_cocina,
        Sum(Coalesce((nf.otros_datos->>'totalPrestamosEf')::double precision, 0)) AS totalPrestamosEf,
        Sum(Coalesce((nf.otros_datos->>'totalDescuentoMaterial')::double precision, 0)) AS totalDescuentoMaterial,
        Sum(CASE WHEN Coalesce(Nullif(u.cuenta_banco, ''), '') <> '' AND nf.esta_asegurado = 't' THEN
          nf.total_neto ELSE 0 END) AS total_neto,
        Sum(nf.total_no_fiscal) AS total_no_fiscal
      FROM nomina_fiscal nf
        INNER JOIN usuarios u ON u.id = nf.id_empleado
      WHERE 1 = 1 AND Date(nf.fecha_final) BETWEEN '{$fecha1}' AND '{$fecha2}' {$sql}
      GROUP BY u.id
      ORDER BY trabajador
    ");
    $response['data'] = $facturas->result();
    $response['fecha1'] = $fecha1;
    $response['fecha2'] = $fecha2;

    return $response;
  }
  public function getAcumuladoNominasEmpleadosXls($show = false)
  {
    if (!$show) {
      header('Content-type: application/vnd.ms-excel; charset=utf-8');
      header("Content-Disposition: attachment; filename=nominasEmpleados.xls");
      header("Pragma: no-cache");
      header("Expires: 0");
    }

    $res = $this->getAcumuladoNominasEmpleadosData();

    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($this->input->get('did_empresa'));

    $titulo1 = (isset($empresa['info']->nombre_fiscal)? $empresa['info']->nombre_fiscal: '');
    $titulo2 = 'Reporte Acumulado de Nominas';
    $titulo3 = "{$res['fecha1']} Al {$res['fecha1']}";


    $html = '<table>
      <tbody>
        <tr>
          <td colspan="23" style="font-size:18px;text-align:center;">'.$titulo1.'</td>
        </tr>
        <tr>
          <td colspan="23" style="font-size:14px;text-align:center;">'.$titulo2.'</td>
        </tr>
        <tr>
          <td colspan="23" style="text-align:center;">'.$titulo3.'</td>
        </tr>
        <tr>
          <td colspan="23"></td>
        </tr>';

    foreach($res['data'] as $key => $item){
      if ($key == 0) {

        $html .= '<tr style="font-weight:bold">
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">TRABAJADOR</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">DIAS TRABAJADOS</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">SUBSIDIO</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">SUELDO SEMANAL</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">BONOS</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">OTROS</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">VACACIONES</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">PRIMA VACACIONAL</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">AGUINALDO</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">FONDO AHORRO</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">PREMIO ASISTENCIA</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">IMSS</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">VEJEZ</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">ISR</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">INFONAVIT</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">PRESTAMOS</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">DESCUENTO PLAYERAS</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">DESCUENTO OTROS</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">DESCUENTO COCINA</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">PRESTAMOS EFECTIVO</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">DESCUENTO MATERIALES</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">TOTAL FISCAL</td>
          <td style="width:150px;border:1px solid #000;background-color: #cccccc;">TOTAL NO FISCAL</td>
        </tr>';
      }

      $html .= '<tr>
          <td style="width:150px;border:1px solid #000;">'.$item->trabajador.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->dias_trabajados.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->subsidio.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->sueldo_semanal.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->bonos.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->otros.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->vacaciones.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->prima_vacacional.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->aguinaldo.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->fondo_ahorro.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->pasistencia.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->imss.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->vejez.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->isr.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->infonavit.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->prestamos.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->descuento_playeras.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->descuento_otros.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->descuento_cocina.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->totalprestamosef.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->totaldescuentomaterial.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->total_neto.'</td>
          <td style="width:150px;border:1px solid #000;">'.$item->total_no_fiscal.'</td>
        </tr>';
    }

    $html .= '
      </tbody>
    </table>';

    echo $html;
  }


  public function importAsistencias($semana)
  {
    $config['upload_path'] = APPPATH.'media/temp/';
    $config['allowed_types'] = '*';
    $config['max_size'] = '2000';

    $this->load->library('upload', $config);

    if ( ! $this->upload->do_upload('archivo_asistencias'))
    {
      return array('error' => '501');
    }
    else
    {
      $file = $this->upload->data();
      $nominaAsistencia = [];

      $handle = fopen($file['full_path'], "r");
      if ($handle) {
        $this->load->model('usuarios_model');

        while (($line = fgets($handle)) !== false) {
          $datos = str_getcsv($line);
          if (isset($datos[0]) && is_numeric($datos[0])) { // si es un # de trabajador
            $fecha = (DateTime::createFromFormat('d/m/Y', $datos[2]));
            $fecha = $fecha->format('Y-m-d');

            // si esta dentro del rango de la semana
            if (strtotime($fecha) >= strtotime($semana['fecha_inicio']) && strtotime($fecha) <= strtotime($semana['fecha_final'])) {
              $empleado = $this->db->select("u.id, u.no_checador" )->from("usuarios u")
                ->where("u.id_empresa", $_POST['id_empresa'])->where("u.no_checador", $datos[0])->get()->row();
              if (isset($empleado->id)) { // si existe el trabajador en la empresa
                $tipo = 'f';
                if ($datos[4] != '' && $datos[5] != '') {
                  $tipo = 'a';
                } elseif($datos[4] != '' || $datos[5] != '') {
                  $tipo = 'a';
                }

                $incapasidad = $this->db->query("SELECT id_asistencia FROM nomina_asistencia
                                           WHERE id_usuario = {$empleado->id} AND tipo = 'in'
                                            AND DATE(fecha_ini) >= '{$fecha}' AND DATE(fecha_fin) <= '{$fecha}'")->row();

                if (!isset($incapasidad->id_asistencia)) { // si no es incapacidad
                  $this->db->where("id_usuario = {$empleado->id} AND tipo = 'f' AND
                    DATE(fecha_ini) = '{$fecha}' AND DATE(fecha_fin) = '{$fecha}'"
                  );
                  $this->db->delete('nomina_asistencia'); // elimina la falta de ese día

                  if ($tipo == 'f') { // si es una falta se inserta
                    $nominaAsistencia[] = array(
                      'fecha_ini'   => $fecha,
                      'fecha_fin'   => $fecha,
                      'id_usuario'  => $empleado->id,
                      'tipo'        => $tipo,
                      'id_clave'    => null,
                      'id_registro' => $this->session->userdata('id_usuario'),
                    );
                  }
                }

                // Agregamos las horas trabajadas
                $this->db->where("id_empleado = {$empleado->id} AND id_empresa = {$_POST['id_empresa']} AND DATE(fecha) = '{$fecha}'");
                $this->db->delete('nomina_asistencia_hrs'); // elimina las hrs del dia
                if ($tipo !== 'f') {
                  $hrs = 0;
                  if (trim($datos[8]) != '') {
                    $hhrr = explode(':', $datos[8]);
                    $hrs = floatval($hhrr[0]);
                    $hrs += floatval($hhrr[1])/60;
                  }
                  $this->db->insert('nomina_asistencia_hrs', [
                    'id_empresa'  => $_POST['id_empresa'],
                    'anio'        => $semana['anio'],
                    'semana'      => $semana['semana'],
                    'id_empleado' => $empleado->id,
                    'fecha'       => $fecha,
                    'hrs'         => $hrs,
                  ]);
                }

              }
            }
          }
        }
        fclose($handle);

        // Si existen faltas o incapacidades las agrega.
        if (count($nominaAsistencia) > 0)
        {
          $this->db->insert_batch('nomina_asistencia', $nominaAsistencia);
        }
      } else {
        return array('error' => '502');
      }

      return array('error' => '500');
    }
  }

  public function importAsistencias2($semana)
  {
    $config['upload_path'] = APPPATH.'media/temp/';
    $config['allowed_types'] = '*';
    $config['max_size'] = '2000';

    $this->load->library('ExcelHelpersLib');
    $excelHelper = new ExcelHelpersLib;

    $this->load->library('upload', $config);

    if ( ! $this->upload->do_upload('archivo_asistencias'))
    {
      return array('error' => '501');
    }
    else
    {
      $file = $this->upload->data();
      $nominaAsistencia = [];
      $resumen = [];

      $arrayAsistencias = $excelHelper->excelToArray($file['full_path']);

      if (count($arrayAsistencias) > 0 && !empty($arrayAsistencias[0]['Clave Empleado'])) {
        $this->load->model('usuarios_model');

        foreach ($arrayAsistencias as $key => $datos) {

          if (isset($datos['Clave Empleado']) && is_numeric($datos['Clave Empleado'])) { // si es un # de trabajador
            $fecha = $excelHelper->formatFechaAsis($datos['Calendario']);
            $fechaSem = MyString::obtenerSemanaDeFecha($fecha, $_POST['dia_inicia_semana']);

            // si esta dentro del rango de la semana
            if (strtotime($fecha) >= strtotime($semana['fecha_inicio']) && strtotime($fecha) <= strtotime($semana['fecha_final'])) {
              $empleado = $this->db->select("u.id, u.no_checador" )->from("usuarios u")
                ->where("u.id_empresa", $_POST['id_empresa'])->where("u.id", $datos['Clave Empleado'])->get()->row();
              if (isset($empleado->id)) { // si existe el trabajador en la empresa
                $tipo = 'f';
                if ($datos['Status'] == 'Asistencia') {
                  $tipo = 'a';
                }

                $incapasidad = $this->db->query("SELECT id_asistencia FROM nomina_asistencia
                                           WHERE id_usuario = {$empleado->id} AND tipo = 'in'
                                            AND DATE(fecha_ini) >= '{$fecha}' AND DATE(fecha_fin) <= '{$fecha}'")->row();

                if (!isset($incapasidad->id_asistencia)) { // si no es incapacidad
                  $this->db->where("id_usuario = {$empleado->id} AND tipo = 'f' AND
                    DATE(fecha_ini) = '{$fecha}' AND DATE(fecha_fin) = '{$fecha}'"
                  );
                  $this->db->delete('nomina_asistencia'); // elimina la falta de ese día

                  if ($tipo == 'f') { // si es una falta se inserta
                    $nominaAsistencia[] = array(
                      'fecha_ini'   => $fecha,
                      'fecha_fin'   => $fecha,
                      'id_usuario'  => $empleado->id,
                      'tipo'        => $tipo,
                      'id_clave'    => null,
                      'id_registro' => $this->session->userdata('id_usuario'),
                    );
                  }
                }

                // Agregamos las horas trabajadas
                $this->db->where("id_empleado = {$empleado->id} AND id_empresa = {$_POST['id_empresa']} AND DATE(fecha) = '{$fecha}'");
                $this->db->delete('nomina_asistencia_hrs'); // elimina las hrs del dia
                if ($tipo !== 'f') {
                  $hrs = 0;
                  if (trim($datos['Tiempo Trabajado']) != '') {
                    $hhrr = explode(':', $datos['Tiempo Trabajado']);
                    $hrs = floatval($hhrr[0]);
                    $hrs += floatval($hhrr[1])/60;
                  }
                  $this->db->insert('nomina_asistencia_hrs', [
                    'id_empresa'  => $_POST['id_empresa'],
                    'anio'        => $semana['anio'],
                    'semana'      => $semana['semana'],
                    'id_empleado' => $empleado->id,
                    'fecha'       => $fecha,
                    'hrs'         => $hrs,
                  ]);
                }

              } else {
                $resumen[] = "El trabajador {$datos['Clave Empleado']} No esta registrado en la empresa seleccionada.";
              }
            } else {
              $resumen[] = "El trabajador {$datos['Clave Empleado']}, La fecha {$fecha} no esta dentro de la semana seleccionada.";
            }
          }
        }

        // Si existen faltas o incapacidades las agrega.
        if (count($nominaAsistencia) > 0)
        {
          $this->db->insert_batch('nomina_asistencia', $nominaAsistencia);
        }
      } else {
        return array('error' => '502');
      }

      return array('error' => '500', 'resumen' => $resumen);
    }
  }

  public function importNominaCorina($semana)
  {
    $config['upload_path'] = APPPATH.'media/temp/';
    $config['allowed_types'] = '*';
    $config['max_size'] = '2000';

    $this->load->library('upload', $config);
    // array(3) {
    //   ["id"]=>
    //   string(1) "2"
    //   ["sem"]=>
    //   string(2) "12"
    //   ["anio"]=>
    //   string(4) "2021"
    // }

    if ( ! $this->upload->do_upload('archivo_nomina'))
    {
      return array('error' => '551');
    }
    else
    {
      $file = $this->upload->data();
      $nominaAsistencia = [];

      $handle = fopen($file['full_path'], "r");
      if ($handle) {
        $this->load->model('usuarios_model');

        $val_close   = false;
        $val_res     = ['error' => '550'];
        $val_resumen = [];
        // Formato
        // 0:empresa, 1:no_trabajador, 2:dias_laborados, 3:monto_pagado, 4:bono, 5:semana, 6:año
        while (($line = fgets($handle)) !== false && !$val_close) {
          $row = explode("\t", $line);

          if (isset($row[0])) {
            $row = $this->clenRowNominaCorona($row);

            if ($row[0] == $_GET['id'] && $row[5] == $_GET['sem'] && $row[6] == $_GET['anio']) {
              $empleado = $this->usuarios_model->get_usuario_info($row[1], true);
              if (isset($empleado['info'][0])) {
                if ($empleado['info'][0]->id_empresa == $row[0]) {
                  $registro = $this->db->query("SELECT id
                    FROM nomina_fiscal_monto_real
                    WHERE id_empleado = {$row[1]} AND id_empresa = {$row[0]}
                      AND anio = {$row[6]} AND semana = {$row[5]}")->row();

                  if (isset($registro->id)) {
                    $this->db->update('nomina_fiscal_monto_real', [
                      'id_empleado'     => $row[1],
                      'id_empresa'      => $row[0],
                      'anio'            => $row[6],
                      'semana'          => $row[5],
                      'dias_trabajados' => $row[2],
                      'monto'           => $row[3],
                      'bono'            => $row[4],
                    ], "id_empleado = {$row[1]} AND id_empresa = {$row[0]} AND anio = {$row[6]} AND semana = {$row[5]}");
                  } else {
                    $this->db->insert('nomina_fiscal_monto_real', [
                      'id_empleado'     => $row[1],
                      'id_empresa'      => $row[0],
                      'anio'            => $row[6],
                      'semana'          => $row[5],
                      'dias_trabajados' => $row[2],
                      'monto'           => $row[3],
                      'bono'            => $row[4],
                    ]);
                  }
                } else {
                  $val_resumen[] = "El trabajador No {$row[1]} tiene asignada otra empresa ({$empleado['info'][0]->nombre_fiscal}).";
                }
              } else {
                $val_resumen[] = "El trabajador No {$row[1]} no existe.";
              }
            } else {
              $val_close = true;
              $val_res = ['error' => '553'];
            }
          }
        }
        fclose($handle);

      } else {
        return array('error' => '552');
      }

      if (isset($val_resumen)) {
        $val_res['resumen'] = $val_resumen;
      }

      return $val_res;
    }
  }

  private function clenRowNominaCorona($row)
  {
    foreach ($row as $key => $value) {
      $row[$key] = trim($value);
      if ($key == 1) {
        $row[$key] = intval(str_replace('"', '', $row[$key]));
      }
    }

    return $row;
  }

  public function descargarNominaCorona($params) {
    $queryNomina = $this->db->query(
      "SELECT nf.id_empleado, nf.id_empresa, nf.anio, nf.semana, nf.fecha_inicio, Date(nf.fecha_final) AS fecha_final, nf.fecha,
        nf.dias_trabajados, nf.salario_diario, nf.salario_integral, nf.subsidio, nf.sueldo_semanal, nf.bonos,
        nf.otros, nf.subsidio_pagado, nf.vacaciones, nf.prima_vacacional_grabable, nf.prima_vacacional_exento,
        nf.prima_vacacional, nf.aguinaldo_grabable, nf.aguinaldo_exento, nf.aguinaldo, nf.total_percepcion,
        nf.imss, nf.vejez, nf.isr, nf.infonavit, nf.subsidio_cobrado, nf.prestamos, nf.deduccion_otros,
        nf.total_deduccion, nf.total_neto, nf.id_empleado_creador, nf.ptu_exento, nf.ptu_grabable, nf.ptu,
        nf.id_puesto, nf.salario_real, nf.sueldo_real, nf.total_no_fiscal, nf.horas_extras, nf.horas_extras_grabable,
        nf.horas_extras_excento, nf.descuento_playeras, nf.utilidad_empresa, nf.descuento_otros, nf.domingo,
        nf.esta_asegurado, nf.fondo_ahorro, nf.pasistencia, nf.despensa, nf.cfdi_ext, nf.descuento_cocina,
        nf.otros_datos, Concat(u.nombre, ' ', u.apellido_paterno, ' ', u.apellido_materno) AS empleado
      FROM nomina_fiscal AS nf
        INNER JOIN usuarios u ON u.id = nf.id_empleado
      WHERE nf.anio = {$params['anio']} AND nf.semana = {$params['sem']} AND nf.id_empresa = {$params['id']}
    ");
    if ($queryNomina->num_rows() > 0) {
    	$this->load->model('empresas_model');
    	$empresa = $this->empresas_model->getInfoEmpresa($_GET['id'])['info'];

    	header('Content-Type:text/html; charset=UTF-8');
      header('Content-Disposition: attachment; filename="corona-'.$empresa->nombre_fiscal.'.txt"');

      $nominas = $queryNomina->result();
      $deducc = [
        'infonavit' => ['IN', 'Infonavit'], 'prestamos' => ['PF', 'Prestamos fiscales'],
        'fondo_ahorro' => ['FA', 'Fondo de ahorro'], 'descuento_playeras' => ['DP', 'Descuento materiales y otros'],
        'descuento_otros' => ['DM', 'Descuento prestamos semanales'], 'descuento_cocina' => ['DC', 'Descuento de cocina']
      ];
      foreach ($nominas as $key => $p) {
        $p->otros_datos = (!empty($p->otros_datos)? json_decode($p->otros_datos): null);
        foreach ($deducc as $keyd => $deduc) {
          if ($p->{$keyd} > 0) {
            echo "\"\"	\"\"	{$p->fecha_final}	{$p->id_empleado}	\"{$p->empleado}\"	\"{$deduc[0]}{$p->anio}{$p->semana}\"	\"{$deduc[1]}, Año {$p->anio}, Sem {$p->semana}\"	{$p->{$keyd}}	0.00	{$p->{$keyd}}	\"\"	2\n";
          }
        }
        if ($p->otros_datos) {
          if (isset($p->otros_datos->totalPrestamosEf) && $p->otros_datos->totalPrestamosEf > 0) {
            echo "\"\"	\"\"	{$p->fecha_final}	{$p->id_empleado}	\"{$p->empleado}\"	\"PE{$p->anio}{$p->semana}\"	\"Prestamos en efectivo, Año {$p->anio}, Sem {$p->semana}\"	{$p->otros_datos->totalPrestamosEf}	0.00	{$p->otros_datos->totalPrestamosEf}	\"\"	2\n";
          }

          if (isset($p->otros_datos->totalDescuentoMaterial) && $p->otros_datos->totalDescuentoMaterial > 0) {
            echo "\"\"	\"\"	{$p->fecha_final}	{$p->id_empleado}	\"{$p->empleado}\"	\"M{$p->anio}{$p->semana}\"	\"Descuento Materiales, Año {$p->anio}, Sem {$p->semana}\"	{$p->otros_datos->totalDescuentoMaterial}	0.00	{$p->otros_datos->totalDescuentoMaterial}	\"\"	2\n";
          }
        }

        if ($p->total_percepcion > 0) {
	        echo "\"\"	\"\"	{$p->fecha_final}	{$p->id_empleado}	\"{$p->empleado}\"	\"S{$p->anio}{$p->semana}\"	\"Sueldo, Año {$p->anio}, Sem {$p->semana}\"	{$p->dias_trabajados}	0.00	{$p->total_percepcion}	\"\"	1\n";
        }
      }
      exit;
    }
  }

}