<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class nomina_fiscal_model extends CI_Model {

  public function configuraciones()
  {
    $configuraciones['nomina'] = $this->getConfigNomina();
    $configuraciones['vacaciones'] = $this->getConfigNominaVacaciones();
    $configuraciones['salarios_zonas'] = $this->getConfigSalariosZonas();

    $configuraciones['cuentas_contpaq'] =  array(
      // 'despensa'          => $this->getDespensaContpaq(),
      'sueldo1'            => $this->getSueldoCuentaContpaq(null, 1),
      'premio_asistencia1' => $this->getPAsistenciaContpaq(null, 1),
      'horas_extras1'      => $this->getHorasExtrasCuentaContpaq(null, 1),
      'vacaciones1'        => $this->getVacacionesCuentaContpaq(null, 1),
      'prima_vacacional1'  => $this->getPrimaVacacionalCuentaContpaq(null, 1),
      'aguinaldo1'         => $this->getAguinaldoCuentaContpaq(null, 1),
      'indemnizaciones1'   => $this->getIndemnizacionCuentaContpaq(null, 1),

      'sueldo2'            => $this->getSueldoCuentaContpaq(null, 2),
      'premio_asistencia2' => $this->getPAsistenciaContpaq(null, 2),
      'horas_extras2'      => $this->getHorasExtrasCuentaContpaq(null, 2),
      'vacaciones2'        => $this->getVacacionesCuentaContpaq(null, 2),
      'prima_vacacional2'  => $this->getPrimaVacacionalCuentaContpaq(null, 2),
      'aguinaldo2'         => $this->getAguinaldoCuentaContpaq(null, 2),
      'indemnizaciones2'   => $this->getIndemnizacionCuentaContpaq(null, 2),

      'ptu'               => $this->getPtuCuentaContpaq(),
      'imss'              => $this->getImssCuentaContpaq(),
      'rcv'               => $this->getRcvCuentaContpaq(),
      'infonavit'         => $this->getInfonavitCuentaContpaq(),
      'otros'             => $this->getOtrosGastosCuentaContpaq(),
      'subsidio'          => $this->getSubsidioCuentaContpaq(),
      'isr'               => $this->getIsrCuentaContpaq(),
    );
    $configuraciones['tablas_isr'] = $this->getTablasIsr();

    return $configuraciones;
  }

  public function nomina($configuraciones, array $filtros = array(), $empleadoId = null, $horasExtrasDinero = null, $descuentoPlayeras = null,
                         $subsidio = null, $isr = null, $utilidadEmpresa = null, $descuentoOtros = null, $tipo = null)
  {
    $this->load->library('nomina');

    $filtros = array_merge(array(
      'semana'    => '',
      'anio'    => '',
      'empresaId' => '',
      'puestoId'  => '',
      'dia_inicia_semana' => '4',
    ), $filtros);

    // Filtros
    $semana = $filtros['semana'] !== '' ? $this->fechasDeUnaSemana($filtros['semana'], $filtros['anio'], $filtros['dia_inicia_semana']) : $this->semanaActualDelMes();

    $sqlpt = $sqlsegu = $sql = $sqlg = $sqle_id = '';
    if ($filtros['empresaId'] !== '')
    {
      $sql .= " AND u.id_empresa = {$filtros['empresaId']}";
      $sqlpt .= " AND u.id_empresa = {$filtros['empresaId']}";
      $sqlg .= " AND ".($tipo=='ag'? 'nagui': 'nf').".id_empresa = {$filtros['empresaId']}";
    }

    if ($filtros['puestoId'] !== '')
    {
      $sql .= " AND u.id_puesto = {$filtros['puestoId']}";
      $sqlpt .= " AND u.id_puesto = {$filtros['puestoId']}";
      $sqlg .= " AND ".($tipo=='ag'? 'nagui': 'nf').".id_puesto = {$filtros['puestoId']}";
    }

    if ($empleadoId)
    {
      $sql .= " AND u.id = {$empleadoId}";
      $sqlpt .= " AND u.id = {$empleadoId}";
      $sqlg .= " AND u.id = {$empleadoId}";
      $sqle_id = " AND u.id = {$empleadoId}";
    }

    if(isset($filtros['asegurado']))
    {
      $sql .= " AND u.esta_asegurado = 't'";
      $sqlpt .= " AND nptu.esta_asegurado = 't'";
      $sqlg .= " AND ".($tipo=='ag'? 'nagui': 'nf').".esta_asegurado = 't'";
      $sqlsegu .= " AND esta_asegurado = 't'";
    }

    $ordenar = " ORDER BY u.apellido_paterno ASC, u.apellido_materno ASC ";
    if(isset($filtros['ordenar']))
    {
      $ordenar = $filtros['ordenar'];
    }

    $diaPrimeroDeLaSemana = $semana['fecha_inicio']; // fecha del primero dia de la semana.
    $diaUltimoDeLaSemana = $semana['fecha_final']; // fecha del ultimo dia de la semana.
    $anio = $semana['anio'];
    $anioPtu = $anio - 1;

    $horasExtrasDinero = $horasExtrasDinero ?: 0;
    $descuentoPlayeras = $descuentoPlayeras ?: 0;
    $descuentoOtros = $descuentoOtros ?: 0;
    $utilidadEmpresa = $utilidadEmpresa ?: 0;

    $nm_tipo = 'se';
    if ($tipo === null || $tipo === 'ag')
    {
      $sql .= " AND (u.status = 't' OR (u.status = 'f' AND Date(u.fecha_salida) >= '{$diaUltimoDeLaSemana}')) ";
      $nm_tipo = $tipo===null? 'se': 'ag';
    }
    else if($tipo === 'ptu')
    {
      $sql .= " AND (SELECT COALESCE(SUM(dias_trabajados), 0) FROM nomina_fiscal WHERE anio = {$anioPtu} AND id_empresa = {$filtros['empresaId']} AND id_empleado = u.id) > 0";
      $sqlg .= " AND (SELECT COALESCE(SUM(dias_trabajados), 0) FROM nomina_fiscal WHERE anio = {$anioPtu} AND id_empresa = {$filtros['empresaId']} AND id_empleado = u.id) > 0";
      $nm_tipo = 'pt';
    }

    // si la nomina esta guardada
    $nm_guardada = $this->db->query("SELECT Count(*) AS num FROM nomina_fiscal_guardadas
                               WHERE id_empresa = {$filtros['empresaId']} AND anio = {$anio} AND semana = {$semana['semana']} AND tipo = '{$nm_tipo}'")->row();

    // Query para obtener los empleados de la semana de la nomina.
    if($nm_guardada->num > 0)
    {
      $sql_nm_guardadas = "((nf.anio = {$semana['anio']} AND nf.semana = {$semana['semana']}) or
                (nagui.anio = {$semana['anio']} AND nagui.semana = {$semana['semana']})) {$sqlg}
         {$ordenar}";
      if($nm_tipo == 'pt') {
        $sql_nm_guardadas = "nptu.anio = {$semana['anio']} AND nptu.semana = {$semana['semana']} {$sqlpt} {$ordenar}";
      }

      $query = $this->db->query(
        "SELECT u.id,
                u.no_empleado,
                (COALESCE(u.apellido_paterno, '') || ' ' || COALESCE(u.apellido_materno, '') || ' ' || u.nombre) as nombre,
                COALESCE(u.apellido_paterno, '') AS apellido_paterno, COALESCE(u.apellido_materno, '') AS apellido_materno, u.nombre AS nombre2,
                u.banco,
                COALESCE(nf.esta_asegurado, nptu.esta_asegurado) AS esta_asegurado,
                't' AS nomina_guardada,
                u.curp,
                DATE(COALESCE(u.fecha_imss, u.fecha_entrada)) as fecha_entrada,
                nf.id_puesto, u.id_departamente,
                u.salario_diario,
                u.salario_diario_real,
                nf.infonavit,
                nf.fondo_ahorro,
                u.regimen_contratacion,
                upp.nombre as puesto,
                COALESCE(nf.dias_trabajados, -1) as dias_trabajados,
                extract(days FROM (timestamp '{$anio}-12-31' - DATE(COALESCE(u.fecha_imss, u.fecha_entrada)) )) as dias_aguinaldo_full,
                (SELECT COALESCE(DATE_PART('DAY', SUM((fecha_fin - fecha_ini) + '1 day'))::integer, 0) as dias
                FROM nomina_asistencia
                WHERE DATE(fecha_ini) >= '{$anio}-01-01' AND DATE(fecha_fin) <= '{$anio}-12-31' AND id_usuario = u.id) as dias_faltados_anio,
                COALESCE(nf.horas_extras, {$horasExtrasDinero}) as horas_extras_dinero,
                COALESCE(nf.pasistencia, 0) as pasistencia,
                COALESCE(nf.despensa, 0) as despensa,
                COALESCE(nf.descuento_playeras, {$descuentoPlayeras}) as descuento_playeras,
                COALESCE(nf.descuento_otros, {$descuentoOtros}) as descuento_otros,
                '{$diaPrimeroDeLaSemana}' as fecha_inicial_pago,
                '{$diaUltimoDeLaSemana}' as fecha_final_pago,
                COALESCE((SELECT SUM(bono) as bonos FROM nomina_percepciones_ext WHERE id_usuario = u.id AND bono <> 0  AND DATE(fecha) >= '{$diaPrimeroDeLaSemana}' AND DATE(fecha) <= '{$diaUltimoDeLaSemana}'), 0) as bonos,
                COALESCE((SELECT SUM(otro) as otros FROM nomina_percepciones_ext WHERE id_usuario = u.id AND otro <> 0 AND DATE(fecha) >= '{$diaPrimeroDeLaSemana}' AND DATE(fecha) <= '{$diaUltimoDeLaSemana}'), 0) as otros,
                COALESCE((SELECT SUM(domingo) as domingo FROM nomina_percepciones_ext WHERE id_usuario = u.id AND domingo <> 0 AND DATE(fecha) >= '{$diaPrimeroDeLaSemana}' AND DATE(fecha) <= '{$diaUltimoDeLaSemana}'), 0) as domingo,
                COALESCE(nf.prestamos, 0) as nomina_fiscal_prestamos,
                COALESCE(nf.vacaciones, 0) as nomina_fiscal_vacaciones,
                COALESCE(nf.aguinaldo, 0) as nomina_fiscal_aguinaldo,
                COALESCE(nf.subsidio, 0) as nomina_fiscal_subsidio,
                COALESCE(nf.isr, 0) as nomina_fiscal_isr,
                -- COALESCE(nf.ptu, 0) as nomina_fiscal_ptu,
                COALESCE(nf.total_percepcion, 0) as nomina_fiscal_total_percepciones,
                COALESCE(nf.total_deduccion, 0) as nomina_fiscal_total_deducciones,
                COALESCE(nf.total_neto, 0) as nomina_fiscal_total_neto,
                COALESCE(nf.uuid, 'false') as esta_generada,
                COALESCE(nf.utilidad_empresa, {$utilidadEmpresa}) as utilidad_empresa,
                (SELECT COALESCE(SUM(total_percepcion), 0) FROM nomina_fiscal WHERE anio = {$anioPtu} AND id_empresa = {$filtros['empresaId']}{$sqlsegu}) as ptu_percepciones_empleados,
                (SELECT COALESCE(SUM(dias_trabajados), 0) FROM nomina_fiscal WHERE anio = {$anioPtu} AND id_empresa = {$filtros['empresaId']}{$sqlsegu}) as ptu_dias_trabajados_empleados,
                (SELECT COALESCE(SUM(total_percepcion), 0) FROM nomina_fiscal WHERE anio = {$anioPtu} AND id_empresa = {$filtros['empresaId']}{$sqlsegu} AND id_empleado = u.id) as ptu_percepciones_empleado,
                (SELECT COALESCE(SUM(dias_trabajados), 0) FROM nomina_fiscal WHERE anio = {$anioPtu} AND id_empresa = {$filtros['empresaId']}{$sqlsegu} AND id_empleado = u.id) as ptu_dias_trabajados_empleado,
                u.rfc,
                u.cuenta_banco,
                u.no_seguro,
                (SELECT COALESCE(dias_vacaciones, 0) FROM nomina_fiscal_vacaciones WHERE anio = {$semana['anio']} AND semana = {$semana['semana']} AND id_empleado = u.id) as dias_vacaciones_fijo,
                (SELECT Date(fecha_fin) FROM nomina_fiscal_vacaciones WHERE id_empleado = u.id AND Date(fecha) < '{$diaUltimoDeLaSemana}' ORDER BY fecha DESC LIMIT 1) AS en_vacaciones,

                COALESCE(nptu.uuid, 'false') AS ptu_generado,
                COALESCE(nptu.ptu, 0) AS nomina_fiscal_ptu,
                COALESCE(nptu.utilidad_empresa, {$utilidadEmpresa}) AS utilidad_empresa_ptu,
                COALESCE(nptu.isr, 0) as nomina_fiscal_ptu_isr,
                COALESCE(nptu.total_percepcion, 0) as nomina_fiscal_ptu_total_percepciones,
                COALESCE(nptu.total_deduccion, 0) as nomina_fiscal_ptu_total_deducciones,
                COALESCE(nptu.total_neto, 0) as nomina_fiscal_ptu_total_neto,

                COALESCE(nagui.uuid, 'false') AS aguinaldo_generado,
                COALESCE(nagui.aguinaldo, 0) AS nomina_fiscal_aguinaldo,
                COALESCE(nagui.isr, 0) as nomina_fiscal_aguinaldo_isr,
                COALESCE(nagui.total_percepcion, 0) as nomina_fiscal_aguinaldo_total_percepciones,
                COALESCE(nagui.total_deduccion, 0) as nomina_fiscal_aguinaldo_total_deducciones,
                COALESCE(nagui.total_neto, 0) as nomina_fiscal_aguinaldo_total_neto,

                (SELECT COALESCE(SUM(dias_trabajados), 0) FROM nomina_fiscal WHERE anio = {$anio} AND id_empleado = u.id) as ptu_dias_trabajados_anio
         FROM usuarios u
         LEFT JOIN nomina_fiscal nf ON nf.id_empleado = u.id AND nf.id_empresa = {$filtros['empresaId']} AND nf.anio = {$anio} AND nf.semana = {$semana['semana']}
         LEFT JOIN nomina_ptu nptu ON nptu.id_empleado = u.id AND nptu.id_empresa = {$filtros['empresaId']} AND nptu.anio = {$anio} AND nptu.semana = {$semana['semana']}
         LEFT JOIN nomina_aguinaldo nagui ON nagui.id_empleado = u.id AND nagui.id_empresa = {$filtros['empresaId']} AND nagui.anio = {$anio} AND nagui.semana = {$semana['semana']}
         LEFT JOIN usuarios_puestos upp ON upp.id_puesto = nf.id_puesto
         WHERE {$sql_nm_guardadas}
      ");
    } else
    { // nomina no guardada

      $sql_query_nom = "SELECT u.id,
                u.no_empleado,
                (COALESCE(u.apellido_paterno, '') || ' ' || COALESCE(u.apellido_materno, '') || ' ' || u.nombre) as nombre,
                COALESCE(u.apellido_paterno, '') AS apellido_paterno, COALESCE(u.apellido_materno, '') AS apellido_materno, u.nombre AS nombre2,
                u.banco,
                u.esta_asegurado,
                'f' AS nomina_guardada,
                u.curp,
                DATE(COALESCE(u.fecha_imss, u.fecha_entrada)) as fecha_entrada,
                u.id_puesto, u.id_departamente,
                u.salario_diario,
                u.salario_diario_real,
                u.infonavit,
                u.fondo_ahorro,
                u.regimen_contratacion,
                COALESCE(upp.nombre, up.nombre) as puesto,
                COALESCE(nf.dias_trabajados, -1) as dias_trabajados,
                extract(days FROM (timestamp '{$anio}-12-31' - DATE(COALESCE(u.fecha_imss, u.fecha_entrada)) )) as dias_aguinaldo_full,
                (SELECT COALESCE(DATE_PART('DAY', SUM((fecha_fin - fecha_ini) + '1 day'))::integer, 0) as dias
                FROM nomina_asistencia
                WHERE DATE(fecha_ini) >= '{$anio}-01-01' AND DATE(fecha_fin) <= '{$anio}-12-31' AND id_usuario = u.id) as dias_faltados_anio,
                COALESCE(nf.horas_extras, {$horasExtrasDinero}) as horas_extras_dinero,
                COALESCE(nf.pasistencia, 0) as pasistencia,
                COALESCE(nf.despensa, 0) as despensa,
                COALESCE(nf.descuento_playeras, {$descuentoPlayeras}) as descuento_playeras,
                COALESCE(nf.descuento_otros, {$descuentoOtros}) as descuento_otros,
                '{$diaPrimeroDeLaSemana}' as fecha_inicial_pago,
                '{$diaUltimoDeLaSemana}' as fecha_final_pago,
                COALESCE((SELECT SUM(bono) as bonos FROM nomina_percepciones_ext WHERE id_usuario = u.id AND bono <> 0  AND DATE(fecha) >= '{$diaPrimeroDeLaSemana}' AND DATE(fecha) <= '{$diaUltimoDeLaSemana}'), 0) as bonos,
                COALESCE((SELECT SUM(otro) as otros FROM nomina_percepciones_ext WHERE id_usuario = u.id AND otro <> 0 AND DATE(fecha) >= '{$diaPrimeroDeLaSemana}' AND DATE(fecha) <= '{$diaUltimoDeLaSemana}'), 0) as otros,
                COALESCE((SELECT SUM(domingo) as domingo FROM nomina_percepciones_ext WHERE id_usuario = u.id AND domingo <> 0 AND DATE(fecha) >= '{$diaPrimeroDeLaSemana}' AND DATE(fecha) <= '{$diaUltimoDeLaSemana}'), 0) as domingo,
                COALESCE(nf.prestamos, 0) as nomina_fiscal_prestamos,
                COALESCE(nf.vacaciones, 0) as nomina_fiscal_vacaciones,
                COALESCE(nf.aguinaldo, 0) as nomina_fiscal_aguinaldo,
                COALESCE(nf.subsidio, 0) as nomina_fiscal_subsidio,
                COALESCE(nf.isr, 0) as nomina_fiscal_isr,
                -- COALESCE(nf.ptu, 0) as nomina_fiscal_ptu,
                COALESCE(nf.total_percepcion, 0) as nomina_fiscal_total_percepciones,
                COALESCE(nf.total_deduccion, 0) as nomina_fiscal_total_deducciones,
                COALESCE(nf.total_neto, 0) as nomina_fiscal_total_neto,
                COALESCE(nf.uuid, 'false') as esta_generada,
                COALESCE(nf.utilidad_empresa, {$utilidadEmpresa}) as utilidad_empresa,
                (SELECT COALESCE(SUM(total_percepcion), 0) FROM nomina_fiscal WHERE anio = {$anioPtu} AND id_empresa = {$filtros['empresaId']}{$sqlsegu}) as ptu_percepciones_empleados,
                (SELECT COALESCE(SUM(dias_trabajados), 0) FROM nomina_fiscal WHERE anio = {$anioPtu} AND id_empresa = {$filtros['empresaId']}{$sqlsegu}) as ptu_dias_trabajados_empleados,
                (SELECT COALESCE(SUM(total_percepcion), 0) FROM nomina_fiscal WHERE anio = {$anioPtu} AND id_empresa = {$filtros['empresaId']}{$sqlsegu} AND id_empleado = u.id) as ptu_percepciones_empleado,
                (SELECT COALESCE(SUM(dias_trabajados), 0) FROM nomina_fiscal WHERE anio = {$anioPtu} AND id_empresa = {$filtros['empresaId']}{$sqlsegu} AND id_empleado = u.id) as ptu_dias_trabajados_empleado,
                u.rfc,
                u.cuenta_banco,
                u.no_seguro,
                (SELECT COALESCE(dias_vacaciones, 0) FROM nomina_fiscal_vacaciones WHERE anio = {$semana['anio']} AND semana = {$semana['semana']} AND id_empleado = u.id) as dias_vacaciones_fijo,
                (SELECT Date(fecha_fin) FROM nomina_fiscal_vacaciones WHERE id_empleado = u.id AND Date(fecha) < '{$diaUltimoDeLaSemana}' ORDER BY fecha DESC LIMIT 1) AS en_vacaciones,

                'false' AS ptu_generado,
                0 AS nomina_fiscal_ptu,
                {$utilidadEmpresa} AS utilidad_empresa_ptu,
                0 as nomina_fiscal_ptu_isr,
                0 as nomina_fiscal_ptu_total_percepciones,
                0 as nomina_fiscal_ptu_total_deducciones,
                0 as nomina_fiscal_ptu_total_neto,

                COALESCE(nagui.uuid, 'false') AS aguinaldo_generado,
                COALESCE(nagui.aguinaldo, 0) AS nomina_fiscal_aguinaldo,
                COALESCE(nagui.isr, 0) as nomina_fiscal_aguinaldo_isr,
                COALESCE(nagui.total_percepcion, 0) as nomina_fiscal_aguinaldo_total_percepciones,
                COALESCE(nagui.total_deduccion, 0) as nomina_fiscal_aguinaldo_total_deducciones,
                COALESCE(nagui.total_neto, 0) as nomina_fiscal_aguinaldo_total_neto,

                (SELECT COALESCE(SUM(dias_trabajados), 0) FROM nomina_fiscal WHERE anio = {$anio} AND id_empleado = u.id) as ptu_dias_trabajados_anio
         FROM usuarios u
         LEFT JOIN usuarios_puestos up ON up.id_puesto = u.id_puesto
         LEFT JOIN nomina_fiscal nf ON nf.id_empleado = u.id AND nf.id_empresa = {$filtros['empresaId']} AND nf.anio = {$anio} AND nf.semana = {$semana['semana']}
         LEFT JOIN nomina_aguinaldo nagui ON nagui.id_empleado = u.id AND nagui.id_empresa = {$filtros['empresaId']} AND nagui.anio = {$anio} AND nagui.semana = {$semana['semana']}
         LEFT JOIN usuarios_puestos upp ON upp.id_puesto = nf.id_puesto
         WHERE u.user_nomina = 't' AND u.status = 't' AND u.de_rancho = 'n' AND DATE(u.fecha_entrada) <= '{$diaUltimoDeLaSemana}' {$sql}
         {$ordenar}
      ";

      if ($nm_tipo == 'pt') { // es ptu
        $sql_query_nom = "SELECT u.id,
            u.no_empleado,
            (COALESCE(u.apellido_paterno, '') || ' ' || COALESCE(u.apellido_materno, '') || ' ' || u.nombre) as nombre,
            COALESCE(u.apellido_paterno, '') AS apellido_paterno, COALESCE(u.apellido_materno, '') AS apellido_materno, u.nombre AS nombre2,
            u.banco,
            nf.esta_asegurado,
            'f' AS nomina_guardada,
            u.curp,
            DATE(COALESCE(u.fecha_imss, u.fecha_entrada)) as fecha_entrada,
            u.id_puesto, u.id_departamente,
            u.salario_diario,
            u.salario_diario_real,
            u.infonavit,
            u.fondo_ahorro,
            u.regimen_contratacion,
            COALESCE(upp.nombre, up.nombre) as puesto,
            -1 as dias_trabajados,
            extract(days FROM (timestamp '{$anio}-12-31' - DATE(COALESCE(u.fecha_imss, u.fecha_entrada)) )) as dias_aguinaldo_full,
            (SELECT COALESCE(DATE_PART('DAY', SUM((fecha_fin - fecha_ini) + '1 day'))::integer, 0) as dias
               FROM nomina_asistencia
               WHERE DATE(fecha_ini) >= '{$anio}-01-01' AND DATE(fecha_fin) <= '{$anio}-12-31' AND id_usuario = u.id) as dias_faltados_anio,
            0 as horas_extras_dinero,
            0 as pasistencia,
            0 as despensa,
            0 as descuento_playeras,
            0 as descuento_otros,
            '{$diaPrimeroDeLaSemana}' as fecha_inicial_pago,
            '{$diaUltimoDeLaSemana}' as fecha_final_pago,
            0 as bonos,
            0 as otros,
            0 as domingo,
            0 as nomina_fiscal_prestamos,
            0 as nomina_fiscal_vacaciones,
            0 as nomina_fiscal_aguinaldo,
            0 as nomina_fiscal_subsidio,
            0 as nomina_fiscal_isr,
            -- COALESCE(nf.ptu, 0) as nomina_fiscal_ptu,
            0 as nomina_fiscal_total_percepciones,
            0 as nomina_fiscal_total_deducciones,
            0 as nomina_fiscal_total_neto,
            'false' as esta_generada,

            {$utilidadEmpresa} as utilidad_empresa,
            (SELECT COALESCE(SUM(total_percepcion), 0) FROM nomina_fiscal WHERE anio = {$anioPtu} AND id_empresa = {$filtros['empresaId']}{$sqlsegu}) as ptu_percepciones_empleados,
            (SELECT COALESCE(SUM(dias_trabajados), 0) FROM nomina_fiscal WHERE anio = {$anioPtu} AND id_empresa = {$filtros['empresaId']}{$sqlsegu}) as ptu_dias_trabajados_empleados,
            (SELECT COALESCE(SUM(total_percepcion), 0) FROM nomina_fiscal WHERE anio = {$anioPtu} AND id_empresa = {$filtros['empresaId']}{$sqlsegu} AND id_empleado = u.id) as ptu_percepciones_empleado,
            (SELECT COALESCE(SUM(dias_trabajados), 0) FROM nomina_fiscal WHERE anio = {$anioPtu} AND id_empresa = {$filtros['empresaId']}{$sqlsegu} AND id_empleado = u.id) as ptu_dias_trabajados_empleado,
            -- COALESCE(SUM(nf.total_percepcion), 0) as ptu_percepciones_empleado,
            -- COALESCE(SUM(nf.dias_trabajados), 0) as ptu_dias_trabajados_empleado,
            u.rfc,
            u.cuenta_banco,
            u.no_seguro,
            0 as dias_vacaciones_fijo,
            null AS en_vacaciones,

            'false' AS ptu_generado,
            0 AS nomina_fiscal_ptu,
            {$utilidadEmpresa} AS utilidad_empresa_ptu,
            0 as nomina_fiscal_ptu_isr,
            0 as nomina_fiscal_ptu_total_percepciones,
            0 as nomina_fiscal_ptu_total_deducciones,
            0 as nomina_fiscal_ptu_total_neto,

            'false' AS aguinaldo_generado,
            0 AS nomina_fiscal_aguinaldo,
            0 as nomina_fiscal_aguinaldo_isr,
            0 as nomina_fiscal_aguinaldo_total_percepciones,
            0 as nomina_fiscal_aguinaldo_total_deducciones,
            0 as nomina_fiscal_aguinaldo_total_neto,

            (SELECT COALESCE(SUM(dias_trabajados), 0) FROM nomina_fiscal WHERE anio = {$anio} AND id_empleado = u.id) as ptu_dias_trabajados_anio
        FROM nomina_fiscal nf
            INNER JOIN usuarios u ON u.id = nf.id_empleado
            LEFT JOIN usuarios_puestos up ON up.id_puesto = u.id_puesto
            LEFT JOIN usuarios_puestos upp ON upp.id_puesto = nf.id_puesto
        WHERE nf.anio = {$anioPtu} AND nf.id_empresa = {$filtros['empresaId']} AND nf.esta_asegurado = 't' AND
            (SELECT COALESCE(SUM(dias_trabajados), 0) FROM nomina_fiscal WHERE anio = {$anioPtu} AND id_empresa = {$filtros['empresaId']} AND id_empleado = u.id) > 0
        GROUP BY u.id, upp.nombre, up.nombre, nf.esta_asegurado
        ORDER BY u.apellido_paterno ASC, u.apellido_materno ASC";
        //{$sqle_id}
      }

      $query = $this->db->query($sql_query_nom);
    }
    $empleados = $query->num_rows() > 0 ? $query->result() : array();

    // echo "<pre>";
    //   var_dump($empleados);
    // echo "</pre>";exit;

    $query->free_result();
    // Query para obtener las faltas o incapacidades de la semana.
    $queryFi = $this->db->query(
      "SELECT na.id_usuario,
              DATE(na.fecha_ini) as fecha_ini,
              DATE(na.fecha_fin) as fecha_fin,
              na.tipo,
              nsc.clave as sat_clave,
              nsc.nombre as sat_descripcion
       FROM nomina_asistencia na
       LEFT JOIN nomina_sat_claves nsc ON nsc.id_clave = na.id_clave
       WHERE (DATE(na.fecha_ini) >= '{$diaPrimeroDeLaSemana}' AND DATE(na.fecha_ini) <= '{$diaUltimoDeLaSemana}') OR
        (DATE(na.fecha_fin) >= '{$diaPrimeroDeLaSemana}' AND DATE(na.fecha_fin) <= '{$diaUltimoDeLaSemana}') OR
        (DATE(fecha_ini) < '{$diaPrimeroDeLaSemana}' AND DATE(fecha_fin) > '{$diaUltimoDeLaSemana}')
       ORDER BY na.id_usuario, DATE(na.fecha_ini) ASC
    ");

    $query->free_result();
    // Query para obtener los prestamos.
    $queryPrestamos = $this->db->query(
      "SELECT np.id_usuario,
              np.id_prestamo,
              np.prestado,
              np.pago_semana,
              np.status,
              DATE(np.fecha) as fecha,
              DATE(np.inicio_pago) as inicio_pago,
              COALESCE(SUM(nfp.monto), 0) as total_pagado
        FROM nomina_prestamos np
        LEFT JOIN nomina_fiscal_prestamos nfp ON nfp.id_prestamo = np.id_prestamo
        WHERE np.status = 't' AND np.pausado = 'f' AND DATE(np.inicio_pago) <= '{$diaUltimoDeLaSemana}'
        GROUP BY np.id_usuario, np.id_prestamo, np.prestado, np.pago_semana,
          np.status, DATE(np.fecha), DATE(np.inicio_pago)
    ");

      // Recorre los empleados para sacar las faltas|incapacidades y los prestamos
      // del empleado.

      $faltasOIncapacidades = array();
      // Si hay faltas o incapacidades entra.
      if ($queryFi->num_rows() > 0)
      {
        $faltasOIncapacidades = $queryFi->result();
      }

      $prestamos = array();
      // Si hay prestamos entra.
      if ($queryPrestamos->num_rows() > 0)
      {
        $prestamos = $queryPrestamos->result();
      }


      // Recorre los empleados para obtener las faltas|incapacidades y los
      // prestamos de cada uno.
      foreach ($empleados as $keye => $empleado)
      {
        $dias_trabajadosAux = $empleado->dias_trabajados;
        $empleado->dias_trabajados = 6;
        $empleado->incapacidades = array();
        $empleado->prestamos = array();

        if (count($faltasOIncapacidades) > 0)
        {
          foreach ($faltasOIncapacidades as $fi)
          {
            $diasIncapacidad = 0;

            // Si la falta o incapacidad pertenece al usuario actual.
            if ($fi->id_usuario === $empleado->id)
            {
              // Si es una falta entonces le resta 1 dia a los dias_trabajados.
              if ($fi->tipo === 'f')
              {
                $empleado->dias_trabajados -= 1;
              }

              // Si es una incapacidad entra.
              else
              {
                // Obtiene el primer dia de incapacidad para la semana
                $diaIniciaIncapacidad = strtotime($fi->fecha_ini) > strtotime($diaPrimeroDeLaSemana) ? $fi->fecha_ini : $diaPrimeroDeLaSemana;

                // Obtiene el ultimo dia de incapacidad para la semana
                $diaTerminaIncapacidad = strtotime($fi->fecha_fin) < strtotime($diaUltimoDeLaSemana) ? $fi->fecha_fin : $diaUltimoDeLaSemana;

                $diasIncapacidad = intval(String::diasEntreFechas($diaIniciaIncapacidad, $diaTerminaIncapacidad)) + 1;

                // Le resta a los dias trabajados los de incapacidad.
                $empleado->dias_trabajados -= $diasIncapacidad;

                // Agrega esa incapacidad al array de incapacidades.
                // el descuento es multiplicado por el salario_diario que es el salario con el que
                // se hara el timbrado.
                $empleado->incapacidades[] = array(
                  'diasIncapacidad' => $diasIncapacidad,
                  'tipoIncapacidad' => $fi->sat_clave,
                  'descuento' => floatval($diasIncapacidad) * floatval($empleado->salario_diario)
                );
              }
            }
          }
        }

        if (count($prestamos) > 0)
        {
          foreach ($prestamos as $key => $prestamo)
          {
            if ($prestamo->id_usuario === $empleado->id)
            {
              // Obtiene lo que falta de pagar.
              $diff = $prestamo->prestado - $prestamo->total_pagado;

              // Si lo que falta de pagar es mayor o igual al pago que
              // se da semalmente entra.
              if ($diff >= $prestamo->pago_semana)
              {
                $prestamo->pago_semana_descontar = $prestamo->pago_semana;
              }
              else
              {
                $prestamo->pago_semana_descontar = $diff;
              }

              $empleado->prestamos[] = (array)$prestamo;
              unset($prestamos[$key]);
            }
          }
        }

        // Calcula proporcional el septimo dia a los asegurados.
        if ($empleado->esta_asegurado == 't')
        {
          $empleado->dias_trabajados = round((($empleado->dias_trabajados >= 0 ? $empleado->dias_trabajados : 0) * 7) / 6, 2);
        }
        else
        {
          $empleado->dias_trabajados += 1;
        }

        if($dias_trabajadosAux != -1) {
          $empleado->dias_trabajados = $dias_trabajadosAux;
        }

        //quita al trabajador si esta de vacaciones
        if($empleado->en_vacaciones == '')
          true;
        elseif( $diaUltimoDeLaSemana >= $empleado->en_vacaciones  )
          true;
        elseif($empleado->dias_trabajados == 0)
          unset($empleados[$keye]);

      }

    $total_dias_trabajados = 0;
    if ($nm_tipo == 'pt') { // es ptu
      foreach ($empleados as $key => $value) {
        $value->ptu_dias_trabajados_empleado = round($value->ptu_dias_trabajados_empleado);
        $total_dias_trabajados += $value->ptu_dias_trabajados_empleado;
        if ($empleadoId > 0 && $empleadoId != $value->id) {
          unset($empleados[$key]);
        }
      }
    }
    foreach ($empleados as $key => $empleado)
    {
      if ($nm_tipo == 'pt') { // es ptu
        $empleado->ptu_dias_trabajados_empleados = $total_dias_trabajados;
      }
      $nomina = $this->nomina
        ->setEmpleado($empleado)
        ->setEmpresaConfig($configuraciones['nomina'][0])
        ->setVacacionesConfig($configuraciones['vacaciones'])
        ->setSalariosZonas($configuraciones['salarios_zonas'][0])
        ->setClavesPatron($configuraciones['cuentas_contpaq'])
        ->setTablasIsr($configuraciones['tablas_isr'])
        ->setSubsidioIsr($subsidio, $isr)
        ->procesar();
    }
    if ($nm_tipo == 'pt' && $empleadoId > 0) { // es ptu
      return [(isset($empleados[0])? $empleados[0]: 0)];
    }

    // echo "<pre>";
    //   var_dump($empleados);
    // echo "</pre>";exit;
    return $empleados;
  }

  /**
   * Obtiene el registro de los datos precapturados en la nomina para q se carguen de nuevo
   * @param  [type] $empleadoId [description]
   * @param  [type] $empresaId  [description]
   * @param  [type] $anio       [description]
   * @param  [type] $semana     [description]
   * @return [type]             [description]
   */
  public function getPreNomina($empleadoId, $empresaId, $anio, $semana)
  {
    $data = $this->db->query("SELECT * FROM nomina_fiscal_presave WHERE id_empleado = {$empleadoId} AND id_empresa = {$empresaId} AND anio = {$anio} AND semana = {$semana}")->row();

    return array(
      'horas_extras' => isset($data->horas_extras)? $data->horas_extras: 0,
      'desc_playeras' => isset($data->desc_playeras)? $data->desc_playeras: 0,
      'desc_otros' => isset($data->desc_otros)? $data->desc_otros: 0,
      );
  }

  public function add_nominas($datos, $empresaId, $empleadoId)
  {
    // echo "<pre>";
    //   var_dump($datos, $empresaId, $empleadoId);
    // echo "</pre>";exit;
    // $startTime = new DateTime(date('Y-m-d H:i:s'));

    $this->load->library('cfdi');
    $this->load->library('facturartebarato_api');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');

    // Obtiene la informacion de la empresa.
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);

    // Obtiene el certificado.
    $certificado = $this->cfdi->obtenCertificado($this->db
      ->select('cer')
      ->from("empresas")
      ->where("id_empresa", $empresaId)
      ->get()->row()->cer
    );

    // Obtiene las configuraciones.
    $_GET['cid_empresa'] = $empresaId; //para las cuentas del contpaq
    $configuraciones = $this->configuraciones();

    // Almacenara los datos de las nominas de cada empleado para despues
    // insertarlas.
    $nominasEmpleados = array();

    // Almacenara los datos de los prestamos de cada empleado para despues
    // insertarlos.
    $prestamosEmpleados = array();

    // Obtiene el rango de fechas de la semana.
    $fechasSemana = $this->fechasDeUnaSemana($datos['numSemana'], $datos['anio'], $empresa['info']->dia_inicia_semana );

    // Auxiliar para saber si hubo un error al momento de timbrar alguna nomina.
    $errorTimbrar = false;

    // Recorre los empleados para agregar y timbrar sus nominas.
    // foreach ($datos['empleado_id'] as $key => $empleadoId)
    // {
      // Si la nomina del empleado no se ha generado entonces entra.
      if ($datos['generar_nomina'] === '1')
      {
        // $empleado = $this->usuarios_model->get_usuario_info($empleadoId, true);
        $empleado = $this->usuarios_model->get_usuario_info($empleadoId, true);

        $empleadoNomina = $this->nomina(
          $configuraciones,
          array('semana' => $datos['numSemana'], 'empresaId' => $empresaId, 'anio' => $datos['anio'], 'dia_inicia_semana' => $empresa['info']->dia_inicia_semana ),
          $empleadoId,
          $datos['horas_extras'],
          $datos['descuento_playeras'],
          $datos['subsidio'],
          $datos['isr'],
          $datos['utilidad_empresa'],
          $datos['descuento_otros']
        );
        // unset($empleadoNomina[0]->nomina->percepciones['subsidio']);
        // unset($empleadoNomina[0]->nomina->percepciones['ptu']);
        // unset($empleadoNomina[0]->nomina->deducciones['isr']);

        $valorUnitario = 0; // Total de las Percepciones.

        // Recorre las percepciones del empleado.
        foreach ($empleadoNomina[0]->nomina->percepciones as $tipoPercepcion => $percepcion)
        {
          // Si activaron las vacaciones entonces suma las vacaciones y la prima vacacional.
          if ($tipoPercepcion === 'vacaciones' || $tipoPercepcion === 'prima_vacacional')
          {
            if ($datos['con_vacaciones'] === '1' && $empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]['total'] != 0)
            {
              $valorUnitario += $percepcion['total'];
              unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]['total']);
            }
            else
              unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]);
          }
          // Si el tipo de percepcion es aguinaldo
          else if ($tipoPercepcion === 'aguinaldo')
          {
            // Si activarion el aguinaldo entonces lo suma.
            if ($datos['con_aguinaldo'] === '1')
            {
              $valorUnitario += $percepcion['total'];
              unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]['total']);
            }
            else
              unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]);
          }
          // Si es el sueldo u horas extras los suma directo.
          else
          {
            $valorUnitario += $percepcion['total'];
            unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]['total']);
          }
        }

        $isr = 0; // retenciones
        $descuento = 0; // Total de las deducciones(gravado + excento) excepto el ISR.
        // Recorre las deducciones del empleado.
        foreach ($empleadoNomina[0]->nomina->deducciones as $tipoDeduccion => $deduccion)
        {
          if ($tipoDeduccion !== 'isr')
          {
            $descuento += $deduccion['total'];
          }
          else
          {
            $isr = $deduccion['total'];
          }
          unset($empleadoNomina[0]->nomina->deducciones[$tipoDeduccion]['total']);
        }

        // Le suma al imss el rcv, para tener solamente la deduccion imss.
        $empleadoNomina[0]->nomina->deducciones['imss']['ImporteExcento'] += $empleadoNomina[0]->nomina->deducciones['rcv']['ImporteExcento'];
        unset($empleadoNomina[0]->nomina->deducciones['rcv']);

        $result = array('xml' => '', 'uuid' => '');
        if($datos['esta_asegurado'] == 't')
        {
          // Obtiene los datos para la cadena original.
          $datosCadenaOriginal = $this->datosCadenaOriginal($empleado, $empresa);
          $datosCadenaOriginal['subTotal'] = $valorUnitario;
          $datosCadenaOriginal['descuento'] = $descuento;
          $datosCadenaOriginal['retencion'][0]['importe'] = $isr;
          $datosCadenaOriginal['totalImpuestosRetenidos'] = $isr;
          $datosCadenaOriginal['total'] = round($valorUnitario - $descuento - $isr, 4);

          // Concepto de la nomina.
          $concepto = array(array(
            'cantidad'         => 1,
            'unidad'           => 'Servicio',
            'descripcion'      => 'Pago de nomina',
            'valorUnitario'    => round($valorUnitario, 4),
            'importe'          => round($valorUnitario, 4),
            'idClasificacion' => null,
          ));

          $datosCadenaOriginal['concepto'] = $concepto;

          // Obtiene la cadena original para la nomina.
          $cadenaOriginal = $this->cfdi->obtenCadenaOriginal($datosCadenaOriginal, true, $empleadoNomina);

          // Genera el sello en base a la cadena original.
          $sello = $this->cfdi->obtenSello($cadenaOriginal['cadenaOriginal']);

          // Construye los datos para el xml.
          $datosXML = $this->datosXml($cadenaOriginal['datos'], $empresa, $empleado, $sello, $certificado);
          $datosXML['concepto'] = $concepto;

          $archivo = $this->cfdi->generaArchivos($datosXML, true, $fechasSemana);

          $result = $this->timbrar($archivo['pathXML']);
          // echo "<pre>";
          //   var_dump($archivo, $result, base64_encode($result['xml']), $cadenaOriginal);
          // echo "</pre>";

          // Si la nomina se timbro entonces agrega al array nominas la nomina del
          // empleado para despues insertarla en la bdd.
          if (isset($result['result']->status) && $result['result']->status==true)
          {
            $vacaciones = isset($empleadoNomina[0]->nomina->percepciones['vacaciones'])
              ? $empleadoNomina[0]->nomina->percepciones['vacaciones']['ImporteGravado'] +
                $empleadoNomina[0]->nomina->percepciones['vacaciones']['ImporteExcento']
              : 0;

            $primaVacacionalGravable = isset($empleadoNomina[0]->nomina->percepciones['prima_vacacional'])
              ? $empleadoNomina[0]->nomina->percepciones['prima_vacacional']['ImporteGravado']
              : 0;

            $primaVacacionalExcento = isset($empleadoNomina[0]->nomina->percepciones['prima_vacacional'])
              ? $empleadoNomina[0]->nomina->percepciones['prima_vacacional']['ImporteExcento']
              : 0;

            $primaVacacional = isset($empleadoNomina[0]->nomina->percepciones['prima_vacacional'])
              ? $empleadoNomina[0]->nomina->percepciones['prima_vacacional']['ImporteGravado'] +
                $empleadoNomina[0]->nomina->percepciones['prima_vacacional']['ImporteExcento']
              : 0;

            $aguinaldoGravable = isset($empleadoNomina[0]->nomina->percepciones['aguinaldo'])
              ? $empleadoNomina[0]->nomina->percepciones['aguinaldo']['ImporteGravado']
              : 0;

            $aguinaldoExcento = isset($empleadoNomina[0]->nomina->percepciones['aguinaldo'])
              ? $empleadoNomina[0]->nomina->percepciones['aguinaldo']['ImporteExcento']
              : 0;

            $aguinaldo = isset($empleadoNomina[0]->nomina->percepciones['aguinaldo'])
              ? $empleadoNomina[0]->nomina->percepciones['aguinaldo']['ImporteGravado'] +
                $empleadoNomina[0]->nomina->percepciones['aguinaldo']['ImporteExcento']
              : 0;

            $imss = $empleadoNomina[0]->nomina->deducciones['imss']['ImporteGravado'] +
                    $empleadoNomina[0]->nomina->deducciones['imss']['ImporteExcento'];

            $infonavit = $empleadoNomina[0]->nomina->deducciones['infonavit']['ImporteGravado'] +
                         $empleadoNomina[0]->nomina->deducciones['infonavit']['ImporteExcento'];

            $ptuGravado = isset($empleadoNomina[0]->nomina->percepciones['ptu'])
              ? $empleadoNomina[0]->nomina->percepciones['ptu']['ImporteGravado']
              : 0;

            $ptuExcento = isset($empleadoNomina[0]->nomina->percepciones['ptu'])
              ? $empleadoNomina[0]->nomina->percepciones['ptu']['ImporteExcento']
              : 0;

            $ptu = $ptuGravado + $ptuExcento;

            $premioAsistencia = isset($empleadoNomina[0]->nomina->percepciones['premio_asistencia'])
              ? $empleadoNomina[0]->nomina->percepciones['premio_asistencia']['ImporteGravado']
              : 0;
            $despensa = isset($empleadoNomina[0]->nomina->percepciones['despensa'])
              ? $empleadoNomina[0]->nomina->percepciones['despensa']['ImporteExcento']
              : 0;

            $totalPrestamos = 0;
            // Recorre los prestamos del empleado para
            foreach ($empleadoNomina[0]->prestamos as $prestamo)
            {
              $totalPrestamos += floatval($prestamo['pago_semana_descontar']);

              $prestamosEmpleados[] = array(
                'id_empleado' => $empleadoId,
                'id_empresa' => $empresaId,
                'anio' => date('Y'),
                'semana' => $datos['numSemana'],
                'id_prestamo' => $prestamo['id_prestamo'],
                'monto' => $prestamo['pago_semana_descontar'],
              );

              // Suma lo que lleva pagado mas lo que se esta abonando.
              $totalAbonado = floatval($prestamo['total_pagado']) + floatval($prestamo['pago_semana_descontar']);

              // Si ya termino de pagar el prestamo entonces le cambia el status.
              if ($totalAbonado >= floatval($prestamo['prestado']))
              {
                $this->db->update('nomina_prestamos', array('status' => 'f'), array('id_prestamo' => $prestamo['id_prestamo']));
              }
            }

            $totalNoFiscal = floatval($datos['total_no_fiscal']);

            $nominasEmpleados[] = array(
              'id_empleado' => $empleadoId,
              'id_empresa' => $empresaId,
              'anio' => $fechasSemana['anio'],
              'semana' => $datos['numSemana'],
              'fecha_inicio' => $fechasSemana['fecha_inicio'],
              'fecha_final' => $fechasSemana['fecha_final'],
              'dias_trabajados' => $empleadoNomina[0]->dias_trabajados,
              'salario_diario' => $empleadoNomina[0]->salario_diario,
              'salario_integral' => $empleadoNomina[0]->nomina->salario_diario_integrado,
              'subsidio' => $datos['subsidio'],
              'sueldo_semanal' => $empleadoNomina[0]->nomina->percepciones['sueldo']['ImporteGravado'],
              'bonos' => $empleadoNomina[0]->bonos,
              'otros' => $empleadoNomina[0]->otros,
              'subsidio_pagado' => 0,
              'vacaciones' => $vacaciones,
              'prima_vacacional_grabable' => $primaVacacionalGravable,
              'prima_vacacional_exento' => $primaVacacionalExcento,
              'prima_vacacional' => $primaVacacional,
              'aguinaldo_grabable' => $aguinaldoGravable,
              'aguinaldo_exento' => $aguinaldoExcento,
              'aguinaldo' => $aguinaldo,
              'total_percepcion' => $valorUnitario,
              'imss' => $imss,
              'vejez' => 0,
              'isr' => $datos['isr'],
              'infonavit' => $infonavit,
              'subsidio_cobrado' => 0,
              'prestamos' => $totalPrestamos,
              'total_deduccion' => $descuento + $isr,
              'total_neto' => $valorUnitario - $descuento - $isr,
              'id_empleado_creador' => $this->session->userdata('id_usuario'),
              'ptu_exento' => $ptuExcento,
              'ptu_grabable' => $ptuGravado,
              'ptu' => $ptu,
              'id_puesto' => $empleadoNomina[0]->id_puesto,
              'salario_real' => $empleadoNomina[0]->salario_diario_real,
              'sueldo_real' => $empleadoNomina[0]->salario_diario_real * $empleadoNomina[0]->dias_trabajados,
              'total_no_fiscal' => $totalNoFiscal,
              'horas_extras' => $empleadoNomina[0]->horas_extras_dinero,
              'horas_extras_grabable' => $empleadoNomina[0]->nomina->percepciones['horas_extras']['ImporteGravado'],
              'horas_extras_excento' => $empleadoNomina[0]->nomina->percepciones['horas_extras']['ImporteExcento'],
              'descuento_playeras' => $datos['descuento_playeras'],
              'descuento_otros' => $datos['descuento_otros'],
              'xml' => $result['xml'],
              'uuid' => $result['uuid'],
              'utilidad_empresa' => $empleadoNomina[0]->utilidad_empresa,
              'domingo' => $empleadoNomina[0]->domingo,
              'esta_asegurado' => $datos['esta_asegurado'],
              'fondo_ahorro' => $empleadoNomina[0]->fondo_ahorro,
              'pasistencia' => $premioAsistencia,
              'despensa' => $despensa,
            );
          }
          else
          {
            $errorTimbrar = true;
          }

          // echo "<pre>";
          //   var_dump($datosXML, $archivo);
          // echo "</pre>";exit;

          // echo "<pre>";
          //   var_dump($empleado, $cadenaOriginal, $sello, $certificado);
          // echo "</pre>";exit;
        }else
        {
          $totalPrestamos = 0;
          // Recorre los prestamos del empleado para
          foreach ($empleadoNomina[0]->prestamos as $prestamo)
          {
            $totalPrestamos += floatval($prestamo['pago_semana_descontar']);

            $prestamosEmpleados[] = array(
              'id_empleado' => $empleadoId,
              'id_empresa' => $empresaId,
              'anio' => date('Y'),
              'semana' => $datos['numSemana'],
              'id_prestamo' => $prestamo['id_prestamo'],
              'monto' => $prestamo['pago_semana_descontar'],
            );

            // Suma lo que lleva pagado mas lo que se esta abonando.
            $totalAbonado = floatval($prestamo['total_pagado']) + floatval($prestamo['pago_semana_descontar']);

            // Si ya termino de pagar el prestamo entonces le cambia el status.
            if ($totalAbonado >= floatval($prestamo['prestado']))
            {
              $this->db->update('nomina_prestamos', array('status' => 'f'), array('id_prestamo' => $prestamo['id_prestamo']));
            }
          }

          $totalNoFiscal = floatval($datos['total_no_fiscal']);

          $nominasEmpleados[] = array(
              'id_empleado' => $empleadoId,
              'id_empresa' => $empresaId,
              'anio' => $fechasSemana['anio'],
              'semana' => $datos['numSemana'],
              'fecha_inicio' => $fechasSemana['fecha_inicio'],
              'fecha_final' => $fechasSemana['fecha_final'],
              'dias_trabajados' => $empleadoNomina[0]->dias_trabajados-1,
              'salario_diario' => $empleadoNomina[0]->salario_diario_real,
              'salario_integral' => 0,
              'subsidio' => 0,
              'sueldo_semanal' => ($empleadoNomina[0]->salario_diario_real*($empleadoNomina[0]->dias_trabajados-1)),
              'bonos' => $empleadoNomina[0]->bonos,
              'otros' => $empleadoNomina[0]->otros,
              'subsidio_pagado' => 0,
              'vacaciones' => 0,
              'prima_vacacional_grabable' => 0,
              'prima_vacacional_exento' => 0,
              'prima_vacacional' => 0,
              'aguinaldo_grabable' => 0,
              'aguinaldo_exento' => 0,
              'aguinaldo' => 0,
              'total_percepcion' => 0,
              'imss' => 0,
              'vejez' => 0,
              'isr' => 0,
              'infonavit' => 0,
              'subsidio_cobrado' => 0,
              'prestamos' => $totalPrestamos,
              'total_deduccion' => 0,
              'total_neto' => 0,
              'id_empleado_creador' => $this->session->userdata('id_usuario'),
              'ptu_exento' => 0,
              'ptu_grabable' => 0,
              'ptu' => 0,
              'id_puesto' => $empleadoNomina[0]->id_puesto,
              'salario_real' => $empleadoNomina[0]->salario_diario_real,
              'sueldo_real' => $empleadoNomina[0]->salario_diario_real * ($empleadoNomina[0]->dias_trabajados-1),
              'total_no_fiscal' => $totalNoFiscal,
              'horas_extras' => 0,
              'horas_extras_grabable' => 0,
              'horas_extras_excento' => 0,
              'descuento_playeras' => $datos['descuento_playeras'],
              'descuento_otros' => $datos['descuento_otros'],
              'xml' => '',
              'uuid' => '',
              'utilidad_empresa' => $empleadoNomina[0]->utilidad_empresa,
              'domingo' => $empleadoNomina[0]->domingo,
              'esta_asegurado' => $datos['esta_asegurado'],
              'fondo_ahorro' => $empleadoNomina[0]->fondo_ahorro,
              'pasistencia' => 0,
              'despensa' => 0,
            );
        }

      }
    // }

    // Inserta las nominas.
    if (count($nominasEmpleados) > 0)
    {
      $this->db->insert_batch('nomina_fiscal', $nominasEmpleados);
    }

    // Inserta los abonos de los prestamos.
    if (count($prestamosEmpleados) > 0)
    {
      $this->db->insert_batch('nomina_fiscal_prestamos', $prestamosEmpleados);
    }

    // $endTime = new DateTime(date('Y-m-d H:i:s'));

    // echo "<pre>";
    //   var_dump($startTime->diff($endTime)->format('%H:%I:%S'));
    // echo "</pre>";exit;

    return array('errorTimbrar' => $errorTimbrar, 'empleadoId' => $empleadoId, 'ultimoNoGenerado' => $datos['ultimo_no_generado']);
  }

  public function add_nomina_terminada($datos)
  {
    $this->db->insert('nomina_fiscal_guardadas', array(
      'id_empresa' => $datos['empresa_id'],
      'anio'       => $datos['anio'],
      'semana'     => $datos['semana'],
      'tipo'       => $datos['tipo'],
      ));
    return true;
  }

  public function add_prenominas($datos, $empresaId, $empleadoId)
  {
    $existe = $this->db->query("SELECT Count(*) AS num FROM nomina_fiscal_presave WHERE id_empleado = {$empleadoId} AND id_empresa = {$empresaId} AND anio = {$datos['anio']} AND semana = {$datos['numSemana']}")->row();
    $data = array(
      'id_empleado'   => $empleadoId,
      'id_empresa'    => $empresaId,
      'anio'          => $datos['anio'],
      'semana'        => $datos['numSemana'],
      'horas_extras'  => $datos['horas_extras'],
      'desc_playeras' => $datos['descuento_playeras'],
      'desc_otros'    => $datos['descuento_otros'],
    );
    if($existe->num > 0)
      $this->db->update('nomina_fiscal_presave', $data, "id_empleado = {$empleadoId} AND id_empresa = {$empresaId} AND anio = {$datos['anio']} AND semana = {$datos['numSemana']}");
    else
      $this->db->insert('nomina_fiscal_presave', $data);
    return array('status' => true);
  }

  public function finiquito($empleadoId, $fechaSalida, $despido)
  {
    $this->load->library('finiquito');

    $sql = '';
    // if ($filtros['empresaId'] !== '')
    // {
    //   $sql .= " AND u.id_empresa = {$filtros['empresaId']}";
    // }

    // if ($filtros['puestoId'] !== '')
    // {
    //   $sql .= " AND u.id_puesto = {$filtros['puestoId']}";
    // }

    if ($empleadoId !== '')
    {
      $sql .= " AND u.id = {$empleadoId}";
    }

    $fechaSalida = new DateTime($fechaSalida);
    $anio = $fechaSalida->format('Y');
    $semana_salida = date("W", $fechaSalida->getTimestamp());

    $fechaEntrada = $this->db->query("SELECT DATE(COALESCE(fecha_imss, fecha_entrada)) as fecha_entrada
                               FROM usuarios as u
                               WHERE u.esta_asegurado = 't' AND u.status = 't' {$sql}")->row();
    if(isset($fechaEntrada->fecha_entrada))
      $fechaEntrada = $fechaEntrada->fecha_entrada;
    else
      return false;

    // fecha en la que se inciaran a calcular los dias transcurrido del año
    // a la fecha de renuncia.
    $fechaInicio = date('Y-01-01');
    if (strtotime($fechaInicio) < strtotime($fechaEntrada))
    {
      $fechaInicio = date($fechaEntrada);
    }

    // Saca los dias transcurridos desde el 1 de Enero del año a la fecha de salida.
    $diasTranscurridos = $fechaSalida->diff(new DateTime($fechaInicio))->format("%a") + 1;

    $semanaQueSeVa = String::obtenerSemanasDelAnioV2($fechaSalida->format('Y'), 0, 4, true, $fechaSalida->format('Y-m-d')); // cambiarle a 4=viernes
    $fechaInicioSemana = new DateTime($semanaQueSeVa['fecha_inicio']);
    $diasTrabajadosSemana = $fechaInicioSemana->diff($fechaSalida)->days + 1;

    // Query para obtener el empleado.
    $query = $this->db->query(
      "SELECT u.id,
              u.no_empleado,
              u.id_empresa,
              u.id_puesto, u.id_departamente,
              (COALESCE(u.apellido_paterno, '') || ' ' || COALESCE(u.apellido_materno, '') || ' ' || u.nombre) as nombre,
              u.curp,
              DATE(COALESCE(u.fecha_imss, u.fecha_entrada)) as fecha_entrada,
              '{$fechaSalida->format('Y-m-d')}' as fecha_salida,
              '{$fechaSalida->format('Y-m-d')}' as fecha_final_pago,
              '{$fechaInicio}' as fecha_inicial_pago,
              u.id_puesto,
              u.salario_diario,
              u.regimen_contratacion,
              up.nombre as puesto,
              {$diasTranscurridos} as dias_transcurridos,
              (SELECT COALESCE(DATE_PART('DAY', SUM((fecha_fin - fecha_ini) + '1 day'))::integer, 0) as dias
              FROM nomina_asistencia
              WHERE DATE(fecha_ini) >= '{$anio}-01-01' AND DATE(fecha_fin) <= '{$anio}-12-31' AND id_usuario = u.id) as dias_faltados_anio,
              {$diasTrabajadosSemana} as dias_trabajados_semana,
              (SELECT COALESCE(isr, 0)
               FROM nomina_fiscal
               WHERE id_empleado = {$empleadoId} AND dias_trabajados = 7 AND horas_extras = 0 AND ptu = 0 AND vacaciones = 0 AND prima_vacacional = 0 AND aguinaldo = 0
               ORDER BY fecha DESC
               LIMIT 1) as isr_ultima_semana
       FROM usuarios u
       LEFT JOIN usuarios_puestos up ON up.id_puesto = u.id_puesto
       WHERE u.esta_asegurado = 't' AND u.status = 't' {$sql}
    ");
    $empleado = $query->num_rows() > 0 ? $query->result() : array();
    $empleado[0]->incapacidades = array();

    $vacacionesAnios = $this->getConfigNominaVacaciones();
    $configNomina = $this->getConfigNomina();
    $salariosZonas = $this->getConfigSalariosZonas();

    $cuentasContpaq = array(
      'sueldo1'            => $this->getSueldoCuentaContpaq($empleado[0]->id_empresa, 1),
      'premio_asistencia1' => $this->getPAsistenciaContpaq($empleado[0]->id_empresa, 1),
      'horas_extras1'      => $this->getHorasExtrasCuentaContpaq($empleado[0]->id_empresa, 1),
      'vacaciones1'        => $this->getVacacionesCuentaContpaq($empleado[0]->id_empresa, 1),
      'prima_vacacional1'  => $this->getPrimaVacacionalCuentaContpaq($empleado[0]->id_empresa, 1),
      'aguinaldo1'         => $this->getAguinaldoCuentaContpaq($empleado[0]->id_empresa, 1),
      'indemnizaciones1'   => $this->getIndemnizacionCuentaContpaq($empleado[0]->id_empresa, 1),

      'sueldo2'            => $this->getSueldoCuentaContpaq($empleado[0]->id_empresa, 2),
      'premio_asistencia2' => $this->getPAsistenciaContpaq($empleado[0]->id_empresa, 2),
      'horas_extras2'      => $this->getHorasExtrasCuentaContpaq($empleado[0]->id_empresa, 2),
      'vacaciones2'        => $this->getVacacionesCuentaContpaq($empleado[0]->id_empresa, 2),
      'prima_vacacional2'  => $this->getPrimaVacacionalCuentaContpaq($empleado[0]->id_empresa, 2),
      'aguinaldo2'         => $this->getAguinaldoCuentaContpaq($empleado[0]->id_empresa, 2),
      'indemnizaciones2'   => $this->getIndemnizacionCuentaContpaq($empleado[0]->id_empresa, 2),

      'ptu'               => $this->getPtuCuentaContpaq($empleado[0]->id_empresa),
      'imss'              => $this->getImssCuentaContpaq($empleado[0]->id_empresa),
      'rcv'               => $this->getRcvCuentaContpaq($empleado[0]->id_empresa),
      'infonavit'         => $this->getInfonavitCuentaContpaq($empleado[0]->id_empresa),
      'otros'             => $this->getOtrosGastosCuentaContpaq($empleado[0]->id_empresa),
      'subsidio'          => $this->getSubsidioCuentaContpaq($empleado[0]->id_empresa),
      'isr'               => $this->getIsrCuentaContpaq($empleado[0]->id_empresa),
      // 'premio_asistencia' => $this->getPAsistenciaContpaq(),
      // 'despensa'          => $this->getDespensaContpaq(),
    );

    $tablas = $this->getTablasIsr();

    //Dias trabajados en el año en que entro
    $fecha_entrada = explode('-', $empleado[0]->fecha_entrada);
    $anio_anterior = date("Y", strtotime("-1 year")).'-'.$fecha_entrada[1].'-'.$fecha_entrada[2];
    if(strtotime($anio_anterior) < strtotime($fechaEntrada))
    {
      $anio_anterior = date($fechaEntrada);
    }
    $limite_vacaciones = date("Y-m-d", strtotime($anio_anterior." +1 year"));

    // Obtenemos si se le pagaron vacaciones
    $res_vacaciones = $this->db->query("SELECT Date(fecha_fin) AS fecha FROM nomina_fiscal_vacaciones WHERE id_empleado = {$empleadoId} AND anio = ".date("Y"))->row();
    if(isset($res_vacaciones->fecha) && strtotime($res_vacaciones->fecha) > strtotime($anio_anterior)) {
      if (strtotime($limite_vacaciones) < strtotime($fechaSalida->format('Y-m-d')) ) {
        $anio_anterior = $limite_vacaciones;
      } else
        $anio_anterior = $res_vacaciones->fecha;
    }
    $empleado[0]->dias_anio_vacaciones = intval(String::diasEntreFechas($anio_anterior, $fechaSalida->format('Y-m-d')));

    // Obtenemos los prestamos
    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($empleado[0]->id_empresa);
    // $restamos = $this->getPrestamosEmpleado($empleadoId, $semana_salida, $anio, $empresa['info']->dia_inicia_semana);
    // $anio = $anio==null?date("Y"):$anio;
    $semana = $this->fechasDeUnaSemana($semana_salida, $anio, $empresa['info']->dia_inicia_semana);
    $prestamos = $this->db->query("SELECT id_prestamo, prestado, pago_semana, status, DATE(fecha) as fecha, DATE(inicio_pago) as inicio_pago, pausado,
                                COALESCE((SELECT Sum(monto) FROM nomina_fiscal_prestamos WHERE id_prestamo = nomina_prestamos.id_prestamo), 0) AS pagado
                               FROM nomina_prestamos
                               WHERE id_usuario = {$empleadoId} AND status = 't'
                               ORDER BY DATE(fecha) ASC")->result();

    $empleado[0]->prestamos_pendientes = $prestamos;

    $empleado[0]->prestamos = 0;
    foreach ($prestamos as $key => $value)
    {
      $empleado[0]->prestamos += $value->prestado-$value->pagado;
    }

    $finiquito = $this->finiquito
      ->setEmpleado($empleado[0])
      ->setEmpresaConfig($configNomina[0])
      ->setVacacionesConfig($vacacionesAnios)
      ->setSalariosZonas($salariosZonas[0])
      ->setClavesPatron($cuentasContpaq)
      ->setTablasIsr($tablas)
      ->procesar($despido);

    return $empleado;
  }

  public function add_finiquito($empleadoId, $fechaSalida, $despido)
  {
    $this->load->library('cfdi');
    $this->load->library('facturartebarato_api');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');

    $despido2 = false;
    if ($despido['indem_cons'] || $despido['indem'] || $despido['prima'])
      $despido2 = true;

    // Obtiene la info del empleado.
    $empleado = $this->usuarios_model->get_usuario_info($empleadoId, true);

    // Obtiene los calculos del finiquito.
    $empleadoFiniquito = $this->finiquito($empleadoId, $fechaSalida, $despido);

    // Obtiene la informacion de la empresa.
    $empresa = $this->empresas_model->getInfoEmpresa($empleadoFiniquito[0]->id_empresa, true);

    // Obtiene el certificado.
    $certificado = $this->cfdi->obtenCertificado($this->db
      ->select('cer')
      ->from("empresas")
      ->where("id_empresa", $empleadoFiniquito[0]->id_empresa)
      ->get()->row()->cer
    );

    $valorUnitario = 0;
    // Recorre las percepciones del empleado para sacar el valor unitario.
    foreach ($empleadoFiniquito[0]->nomina->percepciones as $tipoPercepcion => $percepcion)
    {
      $valorUnitario += $percepcion['total'];
      unset($empleadoFiniquito[0]->nomina->percepciones[$tipoPercepcion]['total']);
    }
    $valorUnitario = str_replace(',', '', (String::formatoNumero($valorUnitario, 4, '')));

    // Descuento seria 0 pq no hay otra deducciones aparte del isr.
    $descuento = 0;
    $isr = str_replace(',', '', (String::formatoNumero($empleadoFiniquito[0]->nomina->deducciones['isr']['total'], 4, '')) );
    $otros = str_replace(',', '', (String::formatoNumero($empleadoFiniquito[0]->nomina->deducciones['otros']['total'], 4, '')) );
    unset($empleadoFiniquito[0]->nomina->deducciones['isr']['total']);
    unset($empleadoFiniquito[0]->nomina->deducciones['otros']['total']);

    // Obtiene los datos para la cadena original.
    $datosCadenaOriginal = $this->datosCadenaOriginal($empleado, $empresa);
    $datosCadenaOriginal['subTotal'] = $valorUnitario;
    $datosCadenaOriginal['descuento'] = $descuento;
    $datosCadenaOriginal['retencion'][0]['importe'] = $isr;
    $datosCadenaOriginal['totalImpuestosRetenidos'] = $isr;
    $datosCadenaOriginal['total'] = round($valorUnitario - $descuento - $isr - $otros, 4);

    // Concepto de la nomina.
    $concepto = array(array(
      'cantidad'         => 1,
      'unidad'           => 'Servicio',
      'descripcion'      => 'Finiquito',
      'valorUnitario'    => $valorUnitario,
      'importe'          => $valorUnitario,
      'idClasificacion' => null,
    ));

    $datosCadenaOriginal['concepto'] = $concepto;

    // Obtiene la cadena original para la nomina.
    $cadenaOriginal = $this->cfdi->obtenCadenaOriginal($datosCadenaOriginal, true, $empleadoFiniquito);

    // Genera el sello en base a la cadena original.
    $sello = $this->cfdi->obtenSello($cadenaOriginal['cadenaOriginal']);

    // Construye los datos para el xml.
    $datosXML = $this->datosXml($cadenaOriginal['datos'], $empresa, $empleado, $sello, $certificado);
    $datosXML['concepto'] = $concepto;

    $archivo = $this->cfdi->generaArchivos($datosXML, false, null, 'media/cfdi/FiniquitosXML/');
    $result = $this->timbrar($archivo['pathXML']);
    // echo "<pre>";
    //   var_dump($archivo, $result, $cadenaOriginal);
    // echo "</pre>";exit;

    if ($result['result']->status)
    {
      $errorTimbrar = false;

      $sueldoSemana = $empleadoFiniquito[0]->nomina->percepciones['sueldo']['ImporteGravado'] +
                      $empleadoFiniquito[0]->nomina->percepciones['sueldo']['ImporteExcento'];

      $primaVacacional = $empleadoFiniquito[0]->nomina->percepciones['prima_vacacional']['ImporteGravado'] +
                         $empleadoFiniquito[0]->nomina->percepciones['prima_vacacional']['ImporteExcento'];

      $aguinaldo = $empleadoFiniquito[0]->nomina->percepciones['aguinaldo']['ImporteGravado'] +
                   $empleadoFiniquito[0]->nomina->percepciones['aguinaldo']['ImporteExcento'];

      $indemnizaciones = 0;
      if ($despido2) {
        $indemnizaciones = $empleadoFiniquito[0]->nomina->percepciones['indemnizaciones']['ImporteGravado'] +
                            $empleadoFiniquito[0]->nomina->percepciones['indemnizaciones']['ImporteExcento'];
      }

      $subsidio = $empleadoFiniquito[0]->nomina->percepciones['subsidio']['ImporteGravado'] +
                   $empleadoFiniquito[0]->nomina->percepciones['subsidio']['ImporteExcento'];

      $totalPercepciones = $sueldoSemana + $empleadoFiniquito[0]->nomina->vacaciones + $primaVacacional + $aguinaldo + $subsidio + $indemnizaciones;
      $totalDeducciones = $descuento + $isr + $otros;

      // Compara que halla prestamos.
      if (floatval($empleadoFiniquito[0]->prestamos) > 0)
      {
        $semana = $this->semanaActualDelMes(date('y'));

        // Recorre los prestamos del empleado para
        foreach ($empleadoFiniquito[0]->prestamos_pendientes as $prestamo)
        {
          $prestamosEmpleados[] = array(
            'id_empleado' => $empleadoId,
            'id_empresa'  => $empleadoFiniquito[0]->id_empresa,
            'anio'        => date('Y'),
            'semana'      => $semana['semana'],
            'id_prestamo' => $prestamo->id_prestamo,
            'monto'       => floatval($prestamo->prestado) - floatval($prestamo->pagado),
          );

          $this->db->update('nomina_prestamos', array('status' => 'f'), array('id_prestamo' => $prestamo->id_prestamo));
        }

        // Inserta los abonos de los prestamos.
        if (count($prestamosEmpleados) > 0)
        {
          $this->db->insert_batch('nomina_fiscal_prestamos', $prestamosEmpleados);
        }
      }

      $totalNeto = $totalPercepciones - $totalDeducciones;

      $data = array(
        'id_empleado'               => $empleadoFiniquito[0]->id,
        'id_empresa'                => $empleadoFiniquito[0]->id_empresa,
        'id_puesto'                 => $empleadoFiniquito[0]->id_puesto,
        'id_empleado_creador'       => $this->session->userdata('id_usuario'),
        'fecha_salida'              => $fechaSalida,
        'salario_diario'            => $empleadoFiniquito[0]->salario_diario,
        'vacaciones'                => $empleadoFiniquito[0]->nomina->vacaciones,
        'prima_vacacional_grabable' => $empleadoFiniquito[0]->nomina->percepciones['prima_vacacional']['ImporteGravado'],
        'prima_vacacional_exento'   => $empleadoFiniquito[0]->nomina->percepciones['prima_vacacional']['ImporteExcento'],
        'prima_vacacional'          => $primaVacacional,
        'aguinaldo_grabable'        => $empleadoFiniquito[0]->nomina->percepciones['aguinaldo']['ImporteGravado'],
        'aguinaldo_exento'          => $empleadoFiniquito[0]->nomina->percepciones['aguinaldo']['ImporteExcento'],
        'aguinaldo'                 => $aguinaldo,
        'total_percepcion'          => $totalPercepciones,
        'isr'                       => $empleadoFiniquito[0]->nomina->deducciones['isr']['ImporteExcento'],
        'total_deduccion'           => $totalDeducciones,
        'total_neto'                => $totalNeto,
        'sueldo_semanal'            => $sueldoSemana,
        'dias_trabajados'           => $empleadoFiniquito[0]->dias_trabajados_semana,
        'xml'                       => $result['xml'],
        'uuid'                      => $result['uuid'],
        'subsidio'                  => $subsidio,
        'deduccion_otros'           => $otros,
      );
      if ($despido2) {
        $data['indemnizaciones']          = $indemnizaciones;
        $data['indemnizaciones_grabable'] = $empleadoFiniquito[0]->nomina->percepciones['indemnizaciones']['ImporteGravado'];
        $data['indemnizaciones_exento']   = $empleadoFiniquito[0]->nomina->percepciones['indemnizaciones']['ImporteExcento'];
      }

      $fechaSalida = date('Y-m-d H:i:s');

      $this->db->update('usuarios', array('status' => 'f', 'fecha_salida' => $fechaSalida), array('id' => $empleadoFiniquito[0]->id));

      $this->load->model('usuario_historial_model');
      $this->usuario_historial_model->setIdUsuario($empleadoFiniquito[0]->id);

      $this->usuario_historial_model->make(array(
        array('evento' => 'Finiquito', 'campo' => 'fecha_salida', 'valor_nuevo' => $fechaSalida)
      ));

      $this->db->insert('finiquito', $data);
    }
    else
    {
      $errorTimbrar = true;
      unlink($archivo['pathXML']);
    }

    return array('errorTimbrar' => $errorTimbrar);
  }

  /**
  * Inicializa los datos que serviran para generar la cadena original de la nomina.
  *
  * @return array
  */
  private function datosCadenaOriginal($empleado, $empresa)
  {
    $nombreEmpleado = $empleado['info'][0]->nombre.
      ($empleado['info'][0]->apellido_paterno? ' '.$empleado['info'][0]->apellido_paterno :'').
      ($empleado['info'][0]->apellido_materno? ' '.$empleado['info'][0]->apellido_materno:'');

    // Array con los datos necesarios para generar la cadena original.
    $data = array(
      'id'                => $empresa['info']->id_empresa,
      'table'             => 'empresas',

      'version'           => $empresa['info']->cfdi_version,
      'serie'             => '',
      'folio'             => '',
      'fecha'             => date('Y-m-d\TH:i:s'),
      // 'noAprobacion'      => $this->input->post('dno_aprobacion'),
      // 'anoAprobacion'     => $anoAprobacion[0],
      'tipoDeComprobante' => 'egreso',
      'formaDePago'       => 'Pago en una sola exhibicion',
      'condicionesDePago' => 'co',
      'subTotal'          => 0, //total_importe
      'descuento'         => 0, //descuento
      'total'             => 0,
      'metodoDePago'      => 'No identificado', // Tansferencia
      'NumCtaPago'        => 'No identificado',

      'rfc'               => $empleado['info'][0]->rfc,
      'nombre'            => $nombreEmpleado,
      'calle'             => $empleado['info'][0]->calle,
      'noExterior'        => $empleado['info'][0]->numero,
      'noInterior'        => '',
      'colonia'           => $empleado['info'][0]->colonia,
      'localidad'         => '',
      'municipio'         => $empleado['info'][0]->municipio,
      'estado'            => $empleado['info'][0]->estado,
      'pais'              => 'MEXICO',
      'codigoPostal'      => $empleado['info'][0]->cp,

      'concepto' => array(),

      'retencion' => array(array(
        'impuesto' => 'ISR',
        'importe'  => 0,
      )),
      'totalImpuestosRetenidos' => 0,

      'traslado' => array(array(
        'Impuesto' => 'IVA',
        'tasa'     => '0',
        'importe'  => '0',
      )),
      'totalImpuestosTrasladados' => 0,

      'sinCosto' => false,
    );

    return $data;
  }

  private function datosXml($datosCadenaOriginal, $empresa, $empleado, $sello, $certificado)
  {
    $noCertificado = $this->cfdi->obtenNoCertificado($empresa['info']->cer_org);

    $datosXML = $datosCadenaOriginal;
    $datosXML['id']         = $empresa['info']->id_empresa;
    $datosXML['sinCosto']   =  false;
    $datosXML['table']      = 'empresas';
    $datosXML['comprobante']['serie']         = '';
    $datosXML['comprobante']['folio']         = '';
    $datosXML['comprobante']['sello']         = $sello;
    $datosXML['comprobante']['noCertificado'] = $noCertificado;
    $datosXML['comprobante']['certificado']   = $certificado;
    $datosXML['concepto']                     = array();

    $datosXML['domicilio']['calle']        = $empleado['info'][0]->calle;
    $datosXML['domicilio']['noExterior']   = $empleado['info'][0]->numero;
    $datosXML['domicilio']['noInterior']   = '';
    $datosXML['domicilio']['colonia']      = $empleado['info'][0]->colonia;
    $datosXML['domicilio']['localidad']    = '';
    $datosXML['domicilio']['municipio']    = $empleado['info'][0]->municipio;
    $datosXML['domicilio']['estado']       = $empleado['info'][0]->estado;
    $datosXML['domicilio']['pais']         = 'MEXICO';
    $datosXML['domicilio']['codigoPostal'] = $empleado['info'][0]->cp;

    $datosXML['totalImpuestosRetenidos']   = $datosCadenaOriginal['retencion'][2];
    // $datosXML['totalImpuestosRetenidos']   = 0;
    $datosXML['totalImpuestosTrasladados'] = 0;

    $datosXML['retencion'] = array(
      'impuesto' => 'ISR',
      'importe'  => $datosCadenaOriginal['retencion'][2],
      // 'importe'  => '0',
    );

    $datosXML['traslado']  = array(array(
      'Impuesto' => 'IVA',
      'tasa'     => '0',
      'importe'  => '0',
    ));

    return $datosXML;
  }

  private function timbrar($pathXML)
  {
    $this->facturartebarato_api->setPathXML($pathXML);

    // Realiza el timbrado usando la libreria.
    $timbrado = $this->facturartebarato_api->timbrar();

    // Si no hubo errores al momento de realizar el timbrado.
    return array(
      'result' => $timbrado,
      'xml' => $this->facturartebarato_api->getXML(),
      'uuid' => $this->facturartebarato_api->getUUID()
    );
  }

  /**
   * Obtiene la configuracion de la nomina.
   *
   * @return array
   */
  public function getConfigNomina()
  {
    $config = $this->db->query(
      "SELECT id_configuracion,
              aguinaldo,
              prima_vacacional,
              puntualidad,
              asistencia,
              despensa
       FROM nomina_configuracion")->result();
    return $config;
  }

  /**
   * Obtiene la configuracion los dias de las vacaciones.
   *
   * @return array
   */
  public function getConfigNominaVacaciones()
  {
    $config = $this->db->query(
      "SELECT *
       FROM nomina_configuracion_vacaciones")->result();
    return $config;
  }

  /**
   * Obtiene la configuracion de los salarios de las zonas.
   *
   * @return array
   */
  public function getConfigSalariosZonas()
  {
    $config = $this->db->query(
      "SELECT zona_a, zona_b
       FROM nomina_salarios_minimos")->result();
    return $config;
  }

  /**
   * Obtiene el listado de empleados de la semana.
   *
   * @param  array  $filtros
   * @return array
   */
  public function listadoEmpleadosAsistencias(array $filtros = array())
  {
    $filtros = array_merge(array(
      'semana'    => '',
      'anio'    => '',
      'empresaId' => '',
      'puestoId'  => '',
      'dia_inicia_semana'  => '4',
    ), $filtros);

    // Filtros
    $semana = $filtros['semana'] !== '' ? $this->fechasDeUnaSemana($filtros['semana'], $filtros['anio'], $filtros['dia_inicia_semana']) : $this->semanaActualDelMes();

    $sql = '';
    if ($filtros['empresaId'] !== '')
    {
      $sql .= " AND id_empresa = {$filtros['empresaId']}";
    }

    if ($filtros['puestoId'] !== '')
    {
      $sql .= " AND id_departamente = {$filtros['puestoId']}";
    }

    $diaPrimeroDeLaSemana = $semana['fecha_inicio']; // fecha del primero dia de la semana.
    $diaUltimoDeLaSemana = $semana['fecha_final']; // fecha del ultimo dia de la semana.

    // Query para obtener los empleados de la semana.
    $query = $this->db->query(
      "SELECT id, (COALESCE(apellido_paterno, '') || ' ' || COALESCE(apellido_materno, '') || ' ' || nombre) as nombre,
              DATE(fecha_entrada) as fecha_entrada, id_puesto, id_departamente, salario_diario, salario_diario_real
       FROM usuarios
       WHERE user_nomina = 't' AND de_rancho = 'n' AND DATE(fecha_entrada) <= '{$diaUltimoDeLaSemana}' AND status = 't' {$sql}
       ORDER BY apellido_paterno ASC
      ");
    $empleados = $query->num_rows() > 0 ? $query->result() : array();

    $query->free_result();

    // Query para obtener las faltas o incapacidades de la semana.
    $query = $this->db->query(
      "SELECT id_usuario, DATE(fecha_ini) as fecha_ini, DATE(fecha_fin) as fecha_fin, tipo, id_clave
       FROM nomina_asistencia
       WHERE (DATE(fecha_ini) >= '{$diaPrimeroDeLaSemana}' AND DATE(fecha_ini) <= '{$diaUltimoDeLaSemana}') OR
        (DATE(fecha_fin) >= '{$diaPrimeroDeLaSemana}' AND DATE(fecha_fin) <= '{$diaUltimoDeLaSemana}') OR
        (DATE(fecha_ini) < '{$diaPrimeroDeLaSemana}' AND DATE(fecha_fin) > '{$diaUltimoDeLaSemana}')
       ORDER BY id_usuario, DATE(fecha_ini) ASC
    ");

    // Si hubo al menos una falta o incapacidad en la semana.
    if ($query->num_rows() > 0)
    {
      $faltasOIncapacidades = $query->result();

      // Recorre los empleados para ver cual tuvo faltas o incapacidades.
      foreach ($empleados as $empleado)
      {
        $empleado->dias_faltantes = array();

        foreach ($faltasOIncapacidades as $fi)
        {
          // Si la falta o incapacidad pertenece al usuario actual.
          if ($fi->id_usuario === $empleado->id)
          {
            // Si es una falta entra.
            if ($fi->tipo === 'f')
            {
              // Agrega la falta al array.
              $empleado->dias_faltantes[] = array('fecha' => $fi->fecha_ini, 'tipo' => 'f', 'id_clave' => false);
            }

            // Si es una incapacidad.
            else
            {
              // Agrega el primer dia de la incapacidad al array.
              $empleado->dias_faltantes[] = array('fecha' => $fi->fecha_ini, 'tipo' => 'in', 'id_clave' => $fi->id_clave);

              // Si son mas de 1 dia de incapacidad entra.
              if (strtotime($fi->fecha_ini) !== strtotime($fi->fecha_fin))
              {
                // Determina la diferencia de dias entre el primer dia de la incapacidad
                // y el ultimo
                $diffDias = String::diasEntreFechas($fi->fecha_ini, $fi->fecha_fin);

                // Obtiene los dias restantes de la incapacidad sin tomar en cuenta el primero dia.
                $diasSiguientes = String::obtenerSiguientesXDias(date('Y-m-d', strtotime($fi->fecha_ini . '+1 day')), $diffDias);

                // Agrega los dias faltantes al array.
                foreach ($diasSiguientes as $fechaDia)
                {
                  $empleado->dias_faltantes[] = array('fecha' => $fechaDia, 'tipo' => 'in', 'id_clave' => $fi->id_clave);
                }
              }
            }
          }
        }
      }
    }

    return $empleados;
  }

  /**
   * Guarda faltas e incapacidades de una semana.
   *
   * @param array $datos
   * @param string $numSemana
   * @return array
   */
  public function addAsistencias($datos, $numSemana, $empresaId, $anio=null)
  {
    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId);
    $anio = $anio==null? date("Y"): $anio;
    $semana = $this->fechasDeUnaSemana($numSemana, $anio, $empresa['info']->dia_inicia_semana);

    $nominaAsistencia = array();
    $key = 0; // Auxiliar para el posicionamiento del array $nominaAsistencia.
    $keyIncapacidad = 0; // Auxiliar para saber la posicion la incapacidad abierta.
    $fechaFinIncapacidadOk = true; // Indica si la fecha fin de la incapacidad ya fue establecida.
    $auxLastFechaIncapacidad = false;

    foreach ($datos as $empleadoId => $dias)
    {
      // Elimina las faltas e incapacidades de la semana a agregar del usuario.
      $this->db->where("id_usuario = {$empleadoId} AND
        DATE(fecha_ini) >= '{$semana['fecha_inicio']}' AND
        DATE(fecha_fin) <= '{$semana['fecha_final']}'"
      );
      $this->db->delete('nomina_asistencia');

      $fechaFinIncapacidadOk = true;
      $auxLastFechaIncapacidad = false;

      foreach ($dias as $fecha => $tipo)
      {
        if ($tipo === 'f')
        {
          // Si hay una incapacidad "abierta" le agrega la fecha fin.
          if ($fechaFinIncapacidadOk === false)
          {
            $nominaAsistencia[$keyIncapacidad]['fecha_fin'] = $fechaFinIncapacidad;
            $fechaFinIncapacidadOk = true; // Cierra la incapacidad.
          }

          $nominaAsistencia[] = array(
            'fecha_ini'  => $fecha,
            'fecha_fin'  => $fecha,
            'id_usuario' => $empleadoId,
            'tipo'       => $tipo,
            'id_clave'   => null
          );

          $key++; // Incrementa el key.
        }

        // Si es una Asistencia entra.
        else if ($tipo === 'a')
        {
          // Si hay una incapacidad "abierta" le agrega la fecha fin.
          if ($fechaFinIncapacidadOk === false)
          {
            $nominaAsistencia[$keyIncapacidad]['fecha_fin'] = $fechaFinIncapacidad;
            $fechaFinIncapacidadOk = true; // Cierra la incapacidad.
          }
        }

        // Si es una incapacidad.
        else
        {
          // Si no existe ninguna incapacidad por cerrar entonces agrega una nueva.
          if ($fechaFinIncapacidadOk)
          {
            // Explode para separar el tipo y el Id de la incapacidad
            // ej. "in-52" => [in, 52]
            $tipoIncapacidad = explode('-', $tipo);

            $nominaAsistencia[] = array(
              'fecha_ini'  => $fecha,
              'fecha_fin'  => $fecha,
              'id_usuario' => $empleadoId,
              'tipo'       => $tipoIncapacidad[0],
              'id_clave'   => $tipoIncapacidad[1]
            );

            // Cambia a false para saber que hay una incapacidad "abierta".
            $fechaFinIncapacidadOk = false;

            // Iguala la fecha fin de incapacidad por si la incapacidad es de
            // solo 1 dia.
            $fechaFinIncapacidad = $fecha;

            // Key del array $nominaAsistencia donde se encuentra la incapacidad "abierta".
            $keyIncapacidad = $key;

            $key++; // Incrementa el key.
          }

          // Si hay alguna incapacidad "abierta" entonces va guardando la fecha
          // del dia como la ultima de la incapacidad.
          else
          {
            $fechaFinIncapacidad = $fecha;

            // Si la fecha es la ultima de la semana.
            if (strtotime($fecha) === strtotime($semana['fecha_final']))
            {
              $nominaAsistencia[$keyIncapacidad]['fecha_fin'] = $fechaFinIncapacidad;
              $fechaFinIncapacidadOk = true; // Cierra la incapacidad.
            }
          }
        }
      }

      // no agrega los que la incapasidad sale de esta semana
      foreach ($nominaAsistencia as $nakey => $navalue) {
        $queryFi = $this->db->query(
          "SELECT na.id_usuario,
            DATE(na.fecha_ini) as fecha_ini,
            DATE(na.fecha_fin) as fecha_fin
           FROM nomina_asistencia na
           WHERE na.id_usuario = {$navalue['id_usuario']} AND na.tipo = 'in' AND DATE(na.fecha_ini) <= '{$semana['fecha_inicio']}'
           AND DATE(na.fecha_fin) <= '{$semana['fecha_final']}'
        ")->result(); // DATE(na.fecha_ini) >= '{$semana['fecha_inicio']}' AND DATE(na.fecha_fin) > '{$semana['fecha_final']}'
        foreach ($queryFi as $dkey => $rquit) {
          if ( strtotime($navalue['fecha_ini']) >= strtotime($rquit->fecha_ini) && strtotime($navalue['fecha_fin']) <= strtotime($rquit->fecha_fin)) {
            unset($nominaAsistencia[$nakey]);
            break;
          }
        }
      }
    }

    // Si existen faltas o incapacidades las agrega.
    if (count($nominaAsistencia) > 0)
    {
      $this->db->insert_batch('nomina_asistencia', $nominaAsistencia);
    }

    return array('passes' => true);
  }


  /**
   * Agrega bonos y otros.
   *
   * @param string $empleadoId
   * @param array  $datos
   * @return array
   */
  public function addBonosOtros($empleadoId, array $datos, $numSemana, $anio=null)
  {
    $anio = $anio==null? date("Y"): $anio;
    $this->load->model('usuarios_model');
    $empled = $this->usuarios_model->get_usuario_info($empleadoId, true);
    if (isset($datos['existentes']))
    {
      $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($numSemana, $anio, $empled['info'][0]->dia_inicia_semana);
      $this->db->where("id_usuario = {$empleadoId} AND DATE(fecha) >= '{$semana['fecha_inicio']}' AND DATE(fecha) <= '{$semana['fecha_final']}'");
      $this->db->delete('nomina_percepciones_ext');
    }

    $insertData = array();
    foreach ($datos['tipo'] as $key => $tipo)
    {
      // si el tipo es un bono.
      if ($tipo === 'bono')
      {
        $insertData[] = array(
          'id_usuario' => $empleadoId,
          'fecha'      => $datos['fecha'][$key],
          'bono'       => $datos['cantidad'][$key],
          'otro'       => 0,
          'domingo'    => 0,
        );
      }
      elseif ($tipo === 'domingo')
      {
        $insertData[] = array(
          'id_usuario' => $empleadoId,
          'fecha'      => $datos['fecha'][$key],
          'domingo'    => $datos['cantidad'][$key],
          'bono'       => 0,
          'otro'       => 0,
        );
      }
      else
      {
        $insertData[] = array(
          'id_usuario' => $empleadoId,
          'fecha'      => $datos['fecha'][$key],
          'otro'       => $datos['cantidad'][$key],
          'bono'       => 0,
          'domingo'    => 0,
        );
      }
    }

    if (count($insertData) > 0)
    {
      $this->db->insert_batch('nomina_percepciones_ext', $insertData);
    }

    return array('passes' => true);
  }

  /**
   * Obtiene los bonos y otros de un empleado.
   *
   * @param  string $empleadoId
   * @param  string $numSemana
   * @return array
   */
  public function getBonosOtrosEmpleado($empleadoId, $numSemana, $anio=null, $diaComienza=4)
  {
    $anio = $anio==null? date("Y"): $anio;
    $semana = $this->fechasDeUnaSemana($numSemana, $anio, $diaComienza);
    $query = $this->db->query(
      "SELECT id_usuario, DATE(fecha) as fecha, bono, otro, domingo
       FROM nomina_percepciones_ext
       WHERE id_usuario = {$empleadoId} AND DATE(fecha) >= '{$semana['fecha_inicio']}' AND DATE(fecha) <= '{$semana['fecha_final']}'
       ORDER BY DATE(fecha) ASC
      ");

    $bonosOtros = array();
    if ($query->num_rows() > 0)
    {
      $bonosOtros = $query->result();
    }

    return $bonosOtros;
  }

  /**
   * Agrega los prestamos.
   *
   * @param string $empleadoId
   * @param array  $datos
   * @param string $numSemana
   * @return array
   */
  public function addPrestamos($empleadoId, array $datos, $numSemana, $anio=null)
  {
    $anio = $anio==null? date("Y"): $anio;
    $this->load->model('usuarios_model');
    $empled = $this->usuarios_model->get_usuario_info($empleadoId, true);
    if (isset($datos['prestamos_existentes']))
    {
      $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($numSemana, $anio, $empled['info'][0]->dia_inicia_semana);
      if(count($datos['eliminar_prestamo']) > 0)
        $this->db->delete('nomina_prestamos', "id_prestamo IN(".implode(',', $datos['eliminar_prestamo']).") AND
            id_usuario = {$empleadoId} AND DATE(fecha) >= '{$semana['fecha_inicio']}' AND DATE(fecha) <= '{$semana['fecha_final']}'");
    }

    $insertData = array();
    foreach ($datos['cantidad'] as $key => $cantidad)
    {
      if($datos['id_prestamo'][$key] > 0)
      {
        $this->db->update('nomina_prestamos', array(
          'id_usuario'  => $empleadoId,
          'prestado'    => $datos['cantidad'][$key],
          'pago_semana' => $datos['pago_semana'][$key],
          'fecha'       => $datos['fecha'][$key],
          'inicio_pago' => $datos['fecha_inicia_pagar'][$key],
          'pausado' => $datos['pausarp'][$key],
        ), "id_prestamo = {$datos['id_prestamo'][$key]}");
      }else{
        $insertData[] = array(
          'id_usuario'  => $empleadoId,
          'prestado'    => $datos['cantidad'][$key],
          'pago_semana' => $datos['pago_semana'][$key],
          'fecha'       => $datos['fecha'][$key],
          'inicio_pago' => $datos['fecha_inicia_pagar'][$key],
          'pausado'     => $datos['pausarp'][$key],
        );
      }
    }

    if (count($insertData) > 0)
    {
      $this->db->insert_batch('nomina_prestamos', $insertData);
    }

    return array('passes' => true);
  }

  /**
   * Agrega las vacaciones.
   *
   * @param string $empleadoId
   * @param array  $datos
   * @param string $numSemana
   * @return array
   */
  public function addVacaciones($empleadoId, array $datos, $numSemana)
  {
    $anio = substr($datos['vfecha'], 0, 4);

    $data_vac = $this->db->query("SELECT Date(fecha) AS fecha, Date(fecha_fin) AS fecha_fin FROM nomina_fiscal_vacaciones WHERE id_empleado = {$empleadoId} AND anio = {$anio} AND semana = {$numSemana}")->row();
    if(isset($data_vac->fecha))
      $this->db->delete('nomina_asistencia', "id_usuario = {$empleadoId} AND fecha_ini BETWEEN '{$data_vac->fecha}' AND '{$data_vac->fecha_fin}'");
    $this->db->where("id_empleado = {$empleadoId} AND anio = {$anio} AND semana = {$numSemana}");
    $this->db->delete('nomina_fiscal_vacaciones');

    if($datos['vdias'] > 0)
    {
      $insertData = array(
        'id_empleado'     => $empleadoId,
        'anio'            => $anio,
        'semana'          => $numSemana,
        'dias_vacaciones' => $datos['vdias'],
        'fecha'           => $datos['vfecha'],
        'fecha_fin'       => $datos['vfecha1'],
      );
      $this->db->insert('nomina_fiscal_vacaciones', $insertData);

      //Se asignan las faltas del rango de fechas de vacaciones
      $fecha1 = new DateTime($datos['vfecha']);
      $fecha2 = new DateTime($datos['vfecha1']);
      for ($cont = $fecha1->diff($fecha2)->d; $cont >= 0; $cont--)
      {
        var_dump(array('fecha_ini' => $fecha1->format("Y-m-d"), 'fecha_fin' => $fecha1->format("Y-m-d"), 'id_usuario' => $empleadoId, 'tipo' => 'f'));
        $this->db->insert('nomina_asistencia', array('fecha_ini' => $fecha1->format("Y-m-d"), 'fecha_fin' => $fecha1->format("Y-m-d"), 'id_usuario' => $empleadoId, 'tipo' => 'f'));
        $fecha1->add(new DateInterval('P1D'));
      }
    }

    return array('passes' => true);
  }

  /**
   * Agrega las incapaciades.
   *
   * @param string $empleadoId
   * @param array  $datos
   * @param string $numSemana
   * @return array
   */
  public function addIncapaciades($empleadoId, array $datos, $numSemana, $anio=null)
  {
    $anio = $anio==null? date("Y"): $anio;
    $this->load->model('usuarios_model');
    $empled = $this->usuarios_model->get_usuario_info($empleadoId, true);
    //Elimina los seleccionados
    $semana = $this->nomina_fiscal_model->fechasDeUnaSemana($numSemana, $anio, $empled['info'][0]->dia_inicia_semana);
    if(count($datos['eliminar_incapacidad']) > 0)
      $this->db->delete('nomina_asistencia', "id_asistencia IN(".implode(',', $datos['eliminar_incapacidad']).") AND
          id_usuario = {$empleadoId}");

    foreach ($datos['idias'] as $key => $value)
    {
      if($datos['idias'][$key] > 0)
      {
        $sqlData = array(
          'fecha_ini'           => $datos['ifecha'][$key],
          'fecha_fin'           => String::suma_fechas($datos['ifecha'][$key], $datos['idias'][$key]-1),
          'id_usuario'          => $empleadoId,
          'tipo'                => 'in',
          'id_clave'            => $datos['itipo_inciden'][$key],
          'dias_autorizados'    => $datos['idias'][$key],
          'ramo_seguro'         => $datos['iramo_seguro'][$key],
          'control_incapacidad' => $datos['icontrol_incapa'][$key],
          'folio'               => $datos['ifolio'][$key],
        );
        if(isset($datos['iid_asistencia'][$key]{0}))
        {
          $this->db->update('nomina_asistencia', $sqlData, "id_asistencia = {$datos['iid_asistencia'][$key]}");
        }else
          $this->db->insert('nomina_asistencia', $sqlData);
      }
    }

    return array('passes' => true);
  }

  /**
   * Obtiene los prestamos de un empleado en dicha semana.
   *
   * @param  string $empleadoId
   * @param  string $numSemana
   * @return array
   */
  public function getPrestamosEmpleado($empleadoId, $numSemana, $anio=null, $diaComienza=4)
  {
    $anio = $anio==null?date("Y"):$anio;
    $semana = $this->fechasDeUnaSemana($numSemana, $anio, $diaComienza);
    $query = $this->db->query("SELECT id_prestamo, prestado, pago_semana, status, DATE(fecha) as fecha, DATE(inicio_pago) as inicio_pago, pausado
                               FROM nomina_prestamos
                               WHERE id_usuario = {$empleadoId} AND DATE(fecha) >= '{$semana['fecha_inicio']}' AND DATE(fecha) <= '{$semana['fecha_final']}'
                               ORDER BY DATE(fecha) ASC");

    $prestamos = array();
    if ($query->num_rows() > 0)
    {
      $prestamos = $query->result();
    }

    return $prestamos;
  }

  /**
   * Obtiene la vacaciones que se agrego en la semana.
   *
   * @param  string $empleadoId
   * @param  string $numSemana
   * @return array
   */
  public function getVacacionesEmpleado($empleadoId, $numSemana, $anio=null, $diaComienza=4)
  {
    $anio = $anio==null?date("Y"):$anio;
    $semana = $this->fechasDeUnaSemana($numSemana, $anio, $diaComienza);

    $query = $this->db->query("SELECT id_vacaciones, id_empleado, anio, DATE(fecha) as fecha, Date(fecha_fin) AS fecha_fin, semana, dias_vacaciones
                               FROM nomina_fiscal_vacaciones
                               WHERE id_empleado = {$empleadoId} AND anio = {$semana['anio']} AND semana = {$numSemana}
                               LIMIT 1");

    $vacaciones = array();
    if ($query->num_rows() > 0)
    {
      $vacaciones = $query->row();
    }

    return $vacaciones;
  }

  /**
   * Obtiene las incapacidades que se agrego en la semana.
   *
   * @param  string $empleadoId
   * @param  string $numSemana
   * @return array
   */
  public function getIncapacidadesEmpleado($empleadoId, $numSemana, $anio=null, $diaComienza=4)
  {
    $anio = $anio==null?date("Y"):$anio;
    $semana = $this->fechasDeUnaSemana($numSemana, $anio, $diaComienza);

    $query = $this->db->query("SELECT id_asistencia, DATE(fecha_ini) AS fecha_ini, DATE(fecha_fin) AS fecha_fin, id_usuario, tipo,
                                id_clave, dias_autorizados, ramo_seguro, control_incapacidad, folio
                               FROM nomina_asistencia
                               WHERE tipo = 'in' AND id_usuario = {$empleadoId} AND DATE(fecha_ini) BETWEEN '{$semana['fecha_inicio']}' AND '{$semana['fecha_final']}'");

    $incapacidad = array();
    if ($query->num_rows() > 0)
    {
      $incapacidad = $query->result();
    }

    return $incapacidad;
  }

  /*
   |------------------------------------------------------------------------
   | Funciones para obtener las cuentas del contpaq de cada tipo de
   | percepcion y deduccion.
   |------------------------------------------------------------------------
   */

  private function getSueldoCuentaContpaq($id_empresa=null, $departamento=1)
  {
    $id_empresa = $id_empresa!=null? $id_empresa : $_GET['cid_empresa'];
    $sql = '';
    if ($id_empresa==2 && $departamento == 1) $sql=" AND LOWER(nombre) LIKE '%sueldo%' AND id_padre = '1296'"; //sanjorge
    elseif($id_empresa==2 && $departamento != 1) $sql=" AND id_padre IN(2036, 2037) AND nombre like '%SUELDOS%'"; //sanjorge
    elseif($id_empresa==6) $sql=" AND LOWER(nombre) LIKE '%sueldos%' AND id_cuenta = '1678'"; //francis
    elseif($id_empresa==4) $sql=""; //Raul jorge
    elseif($id_empresa==3) $sql=""; //Gomez gudiño
    elseif($id_empresa==5) $sql=""; //vianey rocio
    else{
      $id_empresa = 2; $sql=" AND LOWER(nombre) LIKE '%sueldo%' AND id_padre = '1296'"; //tests carga las de sanjorge
    }
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE id_empresa = {$id_empresa} {$sql}")->result();

    return (isset($query[0]->cuenta)? $query[0]->cuenta: '');
  }

  private function getHorasExtrasCuentaContpaq($id_empresa=null, $departamento=1)
  {
    $id_empresa = $id_empresa!=null? $id_empresa : $_GET['cid_empresa'];
    $sql = '';
    if ($id_empresa==2 && $departamento == 1) $sql=" AND LOWER(nombre) LIKE '%horas extras%' AND id_padre = '1296'"; //sanjorge
    elseif($id_empresa==2 && $departamento != 1) $sql=" AND id_padre IN(2036, 2037) AND nombre like '%COMPENSACION%'"; //sanjorge
    elseif($id_empresa==6) $sql=" AND LOWER(nombre) LIKE '%horas extras%'"; //francis -
    elseif($id_empresa==4) $sql=""; //Raul jorge
    elseif($id_empresa==3) $sql=""; //Gomez gudiño
    elseif($id_empresa==5) $sql=""; //vianey rocio
    else{
      $id_empresa = 2; $sql=" AND LOWER(nombre) LIKE '%horas extras%' AND id_padre = '1296'"; //tests carga las de sanjorge
    }
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE id_empresa = {$id_empresa} {$sql}")->result();

    return (isset($query[0]->cuenta)? $query[0]->cuenta: '');
  }

  private function getVacacionesCuentaContpaq($id_empresa=null, $departamento=1)
  {
    $id_empresa = $id_empresa!=null? $id_empresa : $_GET['cid_empresa'];
    $sql = '';
    if ($id_empresa==2 && $departamento == 1) $sql=" AND LOWER(nombre) LIKE '%vacaciones%' AND id_padre = '1296'"; //sanjorge
    elseif($id_empresa==2 && $departamento != 1) $sql=" AND id_padre IN(2036, 2037) AND nombre like '%VACACIONES%'"; //sanjorge
    elseif($id_empresa==6) $sql=" AND LOWER(nombre) LIKE '%vacaciones%'"; //francis -
    elseif($id_empresa==4) $sql=""; //Raul jorge
    elseif($id_empresa==3) $sql=""; //Gomez gudiño
    elseif($id_empresa==5) $sql=""; //vianey rocio
    else{
      $id_empresa = 2; $sql=" AND LOWER(nombre) LIKE '%vacaciones%' AND id_padre = '1296'"; //tests carga las de sanjorge
    }
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE id_empresa = {$id_empresa} {$sql}")->result();

    return (isset($query[0]->cuenta)? $query[0]->cuenta: '');
  }

  private function getPrimaVacacionalCuentaContpaq($id_empresa=null, $departamento=1)
  {
    $id_empresa = $id_empresa!=null? $id_empresa : $_GET['cid_empresa'];
    $sql = '';
    if ($id_empresa==2 && $departamento == 1) $sql=" AND LOWER(nombre) LIKE '%prima vacacional%' AND id_padre = '1296'"; //sanjorge
    elseif($id_empresa==2 && $departamento != 1) $sql=" AND id_padre IN(2036, 2037) AND nombre like '%PRIMA VACACIONAL%'"; //sanjorge
    elseif($id_empresa==6) $sql=" AND LOWER(nombre) LIKE '%prima vacacional%'"; //francis -
    elseif($id_empresa==4) $sql=""; //Raul jorge
    elseif($id_empresa==3) $sql=""; //Gomez gudiño
    elseif($id_empresa==5) $sql=""; //vianey rocio
    else{
      $id_empresa = 2; $sql=" AND LOWER(nombre) LIKE '%prima vacacional%' AND id_padre = '1296'"; //tests carga las de sanjorge
    }
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE id_empresa = {$id_empresa} {$sql}")->result();

    return (isset($query[0]->cuenta)? $query[0]->cuenta: '');
  }

  private function getAguinaldoCuentaContpaq($id_empresa=null, $departamento=1)
  {
    $id_empresa = $id_empresa!=null? $id_empresa : $_GET['cid_empresa'];
    $sql = '';
    if ($id_empresa==2 && $departamento == 1) $sql=" AND LOWER(nombre) LIKE '%aguinaldos%' AND id_padre = '1296'"; //sanjorge
    elseif($id_empresa==2 && $departamento != 1) $sql=" AND id_padre IN(2036, 2037) AND nombre like '%AGUINALDO%'"; //sanjorge
    elseif($id_empresa==6) $sql=" AND LOWER(nombre) LIKE '%aguinaldos%'"; //francis -
    elseif($id_empresa==4) $sql=""; //Raul jorge
    elseif($id_empresa==3) $sql=""; //Gomez gudiño
    elseif($id_empresa==5) $sql=""; //vianey rocio
    else{
      $id_empresa = 2; $sql=" AND LOWER(nombre) LIKE '%aguinaldos%' AND id_padre = '1296'"; //tests carga las de sanjorge
    }
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE id_empresa = {$id_empresa} {$sql}")->result();

    return (isset($query[0]->cuenta)? $query[0]->cuenta: '');
  }

  private function getIndemnizacionCuentaContpaq($id_empresa=null, $departamento=1)
  {
    $id_empresa = $id_empresa!=null? $id_empresa : $_GET['cid_empresa'];
    $sql = '';
    if ($id_empresa==2 && $departamento == 1) $sql=" AND LOWER(nombre) LIKE '%indemnizaciones%' AND id_padre = '1296'"; //sanjorge
    elseif($id_empresa==2 && $departamento != 1) $sql=" AND LOWER(nombre) LIKE '%indemnizaciones%' AND id_padre = '1296'"; //$sql=" AND id_padre IN(2036, 2037) AND nombre like '%AGUINALDO%'"; //sanjorge
    elseif($id_empresa==6) $sql=" AND LOWER(nombre) LIKE '%indemnizaciones%'"; //francis -
    elseif($id_empresa==4) $sql=""; //Raul jorge
    elseif($id_empresa==3) $sql=""; //Gomez gudiño
    elseif($id_empresa==5) $sql=""; //vianey rocio
    else{
      $id_empresa = 2; $sql=" AND LOWER(nombre) LIKE '%indemnizaciones%' AND id_padre = '1296'"; //tests carga las de sanjorge
    }
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE id_empresa = {$id_empresa} {$sql}")->result();

    return (isset($query[0]->cuenta)? $query[0]->cuenta: '');
  }

  private function getPAsistenciaContpaq($id_empresa=null, $departamento=1)
  {
    $id_empresa = $id_empresa!=null? $id_empresa : $_GET['cid_empresa'];
    $sql = '';
    if ($id_empresa==2 && $departamento == 1) $sql=" AND UPPER(nombre) LIKE '%ASISTENCIA%' AND id_padre = '1296'"; //sanjorge
    elseif($id_empresa==2 && $departamento != 1) $sql=" AND id_padre IN(2036, 2037) AND nombre like '%ASISTENCIA%'"; //sanjorge
    elseif($id_empresa==6) $sql=" AND LOWER(nombre) LIKE '%ispt antes%'"; //francis -
    elseif($id_empresa==4) $sql=""; //Raul jorge
    elseif($id_empresa==3) $sql=""; //Gomez gudiño
    elseif($id_empresa==5) $sql=""; //vianey rocio
    else{
      $id_empresa = 2; $sql=" AND LOWER(nombre) LIKE '%ispt antes%' AND id_padre = '1191'"; //tests carga las de sanjorge
    }
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE id_empresa = {$id_empresa} {$sql}")->result();

    return (isset($query[0]->cuenta)? $query[0]->cuenta: '');
  }

  private function getDespensaContpaq($id_empresa=null, $departamento=1)
  {
    $id_empresa = $id_empresa!=null? $id_empresa : $_GET['cid_empresa'];
    $sql = '';
    if ($id_empresa==2 && $departamento == 1) $sql=" AND UPPER(nombre) LIKE '%VALES DE DESPENSA%' AND id_padre = '1296'"; //sanjorge
    elseif($id_empresa==2 && $departamento != 1) $sql=" AND id_padre IN(2036, 2037) AND nombre like '%VALES DE DESPENSA%'"; //sanjorge
    elseif($id_empresa==6) $sql=" AND LOWER(nombre) LIKE '%ispt antes%'"; //francis -
    elseif($id_empresa==4) $sql=""; //Raul jorge
    elseif($id_empresa==3) $sql=""; //Gomez gudiño
    elseif($id_empresa==5) $sql=""; //vianey rocio
    else{
      $id_empresa = 2; $sql=" AND LOWER(nombre) LIKE '%ispt antes%' AND id_padre = '1191'"; //tests carga las de sanjorge
    }
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE id_empresa = {$id_empresa} {$sql}")->result();

    return (isset($query[0]->cuenta)? $query[0]->cuenta: '');
  }


  private function getInfonavitCuentaContpaq($id_empresa=null)
  {
    $id_empresa = $id_empresa!=null? $id_empresa : $_GET['cid_empresa'];
    $sql = '';
    if ($id_empresa==2) $sql=" AND LOWER(nombre) LIKE '%credito infonavit%' AND id_padre = '1191'"; //sanjorge
    elseif($id_empresa==6) $sql=" AND LOWER(nombre) LIKE '%credito infonavit%'"; //francis -
    elseif($id_empresa==4) $sql=""; //Raul jorge
    elseif($id_empresa==3) $sql=""; //Gomez gudiño
    elseif($id_empresa==5) $sql=""; //vianey rocio
    else{
      $id_empresa = 2; $sql=" AND LOWER(nombre) LIKE '%credito infonavit%' AND id_padre = '1191'"; //tests carga las de sanjorge
    }
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE id_empresa = {$id_empresa} {$sql}")->result();

    return (isset($query[0]->cuenta)? $query[0]->cuenta: '');
  }

  private function getRcvCuentaContpaq($id_empresa=null)
  {
    $id_empresa = $id_empresa!=null? $id_empresa : $_GET['cid_empresa'];
    $sql = '';
    if ($id_empresa==2) $sql=" AND LOWER(nombre) LIKE '%rcv%' AND id_padre = '1296'"; //sanjorge
    elseif($id_empresa==6) $sql=" AND LOWER(nombre) LIKE '%rcv%'"; //francis -
    elseif($id_empresa==4) $sql=""; //Raul jorge
    elseif($id_empresa==3) $sql=""; //Gomez gudiño
    elseif($id_empresa==5) $sql=""; //vianey rocio
    else{
      $id_empresa = 2; $sql=" AND LOWER(nombre) LIKE '%rcv%' AND id_padre = '1296'"; //tests carga las de sanjorge
    }
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE id_empresa = {$id_empresa} {$sql}")->result();

    return (isset($query[0]->cuenta)? $query[0]->cuenta: '');
  }

  private function getPtuCuentaContpaq($id_empresa=null)
  {
    $id_empresa = $id_empresa!=null? $id_empresa : $_GET['cid_empresa'];
    $sql = '';
    if ($id_empresa==2) $sql=" AND LOWER(nombre) LIKE '%ptu%' AND id_padre = '1296'"; //sanjorge
    elseif($id_empresa==6) $sql=" AND LOWER(nombre) LIKE '%ptu%'"; //francis -
    elseif($id_empresa==4) $sql=""; //Raul jorge
    elseif($id_empresa==3) $sql=""; //Gomez gudiño
    elseif($id_empresa==5) $sql=""; //vianey rocio
    else{
      $id_empresa = 2; $sql=" AND LOWER(nombre) LIKE '%ptu%' AND id_padre = '1296'"; //tests carga las de sanjorge
    }
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE id_empresa = {$id_empresa} {$sql}")->result();

    return (isset($query[0]->cuenta)? $query[0]->cuenta: '');
  }

  private function getImssCuentaContpaq($id_empresa=null)
  {
    $id_empresa = $id_empresa!=null? $id_empresa : $_GET['cid_empresa'];
    $sql = '';
    if ($id_empresa==2) $sql=" AND LOWER(nombre) LIKE '%imss retenido%' AND id_padre = '1191'"; //sanjorge
    elseif($id_empresa==6) $sql=" AND LOWER(nombre) LIKE '%imss retenido%'"; //francis -
    elseif($id_empresa==4) $sql=""; //Raul jorge
    elseif($id_empresa==3) $sql=""; //Gomez gudiño
    elseif($id_empresa==5) $sql=""; //vianey rocio
    else{
      $id_empresa = 2; $sql=" AND LOWER(nombre) LIKE '%imss retenido%' AND id_padre = '1191'"; //tests carga las de sanjorge
    }
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE id_empresa = {$id_empresa} {$sql}")->result();

    return (isset($query[0]->cuenta)? $query[0]->cuenta: '');
  }

  private function getOtrosGastosCuentaContpaq($id_empresa=null)
  {
    $id_empresa = $id_empresa!=null? $id_empresa : $_GET['cid_empresa'];
    $sql = '';
    if ($id_empresa==2) $sql=" AND LOWER(nombre) LIKE '%otros gastos%' AND id_padre = '1296'"; //sanjorge
    elseif($id_empresa==6) $sql=" AND LOWER(nombre) LIKE '%otros gastos%'"; //francis -
    elseif($id_empresa==4) $sql=""; //Raul jorge
    elseif($id_empresa==3) $sql=""; //Gomez gudiño
    elseif($id_empresa==5) $sql=""; //vianey rocio
    else{
      $id_empresa = 2; $sql=" AND LOWER(nombre) LIKE '%otros gastos%' AND id_padre = '1296'"; //tests carga las de sanjorge
    }
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE id_empresa = {$id_empresa} {$sql}")->result();

    return (isset($query[0]->cuenta)? $query[0]->cuenta: '');
  }

  private function getSubsidioCuentaContpaq($id_empresa=null)
  {
    $id_empresa = $id_empresa!=null? $id_empresa : $_GET['cid_empresa'];
    $sql = '';
    if ($id_empresa==2) $sql=" AND LOWER(nombre) LIKE '%subsidio%' AND id_padre = '28'"; //sanjorge
    elseif($id_empresa==6) $sql=" AND LOWER(nombre) LIKE '%subsidio%' AND id_cuenta = '1600'"; //francis
    elseif($id_empresa==4) $sql=""; //Raul jorge
    elseif($id_empresa==3) $sql=""; //Gomez gudiño
    elseif($id_empresa==5) $sql=""; //vianey rocio
    else{
      $id_empresa = 2; $sql=" AND LOWER(nombre) LIKE '%subsidio%' AND id_padre = '28'"; //tests carga las de sanjorge
    }
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE id_empresa = {$id_empresa} {$sql}")->result();

    return (isset($query[0]->cuenta)? $query[0]->cuenta: '');
  }

  private function getIsrCuentaContpaq($id_empresa=null)
  {
    $id_empresa = $id_empresa!=null? $id_empresa : $_GET['cid_empresa'];
    $sql = '';
    if ($id_empresa==2) $sql=" AND LOWER(nombre) LIKE '%ispt antes%' AND id_padre = '1191'"; //sanjorge
    elseif($id_empresa==6) $sql=" AND LOWER(nombre) LIKE '%ispt antes%'"; //francis -
    elseif($id_empresa==4) $sql=""; //Raul jorge
    elseif($id_empresa==3) $sql=""; //Gomez gudiño
    elseif($id_empresa==5) $sql=""; //vianey rocio
    else{
      $id_empresa = 2; $sql=" AND LOWER(nombre) LIKE '%ispt antes%' AND id_padre = '1191'"; //tests carga las de sanjorge
    }
    $query = $this->db->query(
      "SELECT *
       FROM cuentas_contpaq
       WHERE id_empresa = {$id_empresa} {$sql}")->result();

    return (isset($query[0]->cuenta)? $query[0]->cuenta: '');
  }


  /*
   |------------------------------------------------------------------------
   | Catalos del SAT
   |------------------------------------------------------------------------
   */
  /**
   * Obtien los tipos de incapacidades del catalogo del SAT.
   *
   * @return array
   */
  public function satCatalogoIncapacidades()
  {
    $query = $this->db->query("SELECT id_clave, clave, nombre, tipo
                               FROM nomina_sat_claves
                               WHERE tipo = 'in'
                               ORDER BY id_clave ASC");

    return $query->result();
  }

    /*
   |------------------------------------------------------------------------
   | Tablas Art 113
   |------------------------------------------------------------------------
   */

  /**
   * Obtien las tablas del art 113.
   *
   * @return array
   */
  public function getTablasIsr()
  {
    $tablas = array();
    $tablas['diaria']['art113'] = $this->db->query("SELECT * FROM nomina_diaria_art_113")->result();
    $tablas['diaria']['subsidios'] = $this->db->query("SELECT * FROM nomina_diaria_subsidios")->result();
    $tablas['semanal']['art113'] = $this->db->query("SELECT * FROM nomina_semanal_art_113")->result();
    $tablas['semanal']['subsidios'] = $this->db->query("SELECT * FROM nomina_semanal_subsidios")->result();

    return $tablas;
  }


  /*
   |------------------------------------------------------------------------
   | Helpers
   |------------------------------------------------------------------------
   */

  /**
   * Obtiene las semanas que van del año.
   *
   * @return array
   */
  public function semanasDelAno($diaComienza, $anio=null)
  {
    $anio = $anio==null? date('Y'): $anio;
    return String::obtenerSemanasDelAnioV2($anio, 0, $diaComienza);
  }

  /**
   * Obtiene las semanas que van del mes actual.
   * corregirla
   *
   * @return array
   */
  // public function semanasDelMesActual()
  // {
  //   return array_slice(String::obtenerSemanasDelAnioV2(date('Y'), 6, 0, true), 0, 4);
  // }

  /**
   * Obtiene la semana actual del mes actual.
   *
   * @return array
   */
  public function semanaActualDelMes($anio=null)
  {
    $anio = $anio!=null? $anio: date('Y');
    return end(String::obtenerSemanasDelAnioV2(date('Y'), 0, 4));
  }

  /**
   * Obtiene las fechas de una semana en especifico.
   *
   * @param  string $semanaABuscar
   * @return array
   */
  public function fechasDeUnaSemana($semanaABuscar, $anio=null, $diaComienza=4)
  {
    $anio = $anio!=null? $anio: date('Y');
    return String::obtenerSemanasDelAnioV2($anio, 0, $diaComienza, false, $semanaABuscar);
  }

  /**
  * Descarga el ZIP con los documentos.
  *
  * @param  string $idFactura
  * @return void
  */
  public function descargarZipNomina($semana, $empresaId, $anio=null)
  {
    $anio = $anio==null?date("Y"):$anio;
    $this->load->model('empresas_model');

    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);
    $semana = $this->fechasDeUnaSemana($semana, $anio);

    $path = APPPATH."media/cfdi/NominasXML/{$empresa['info']->nombre_fiscal}/{$semana['anio']}/{$semana['semana']}/";

    // Scanea el directorio para obtener los archivos.
    $archivos = array_diff(scandir($path), array('..', '.'));

    $zip = new ZipArchive;
    if ($zip->open(APPPATH."media/Nomina-{$semana['anio']}-{$semana['semana']}.zip", ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === true)
    {
      foreach ($archivos as $archivo)
        $zip->addFile($path.$archivo, $archivo);

      $zip->close();
    }
    else
    {
      exit('Error al intentar crear el ZIP.');
    }

    header('Content-Type: application/zip');
    header("Content-disposition: attachment; filename=Nomina-{$semana['anio']}-{$semana['semana']}.zip");
    readfile(APPPATH."media/Nomina-{$semana['anio']}-{$semana['semana']}.zip");

    unlink(APPPATH."media/Nomina-{$semana['anio']}-{$semana['semana']}.zip");
  }

  public function descargarTxtBanco($semana, $empresaId, $anio=null)
  {
    $anio = $anio==null?date("Y"):$anio;
    $_GET['cid_empresa'] = $empresaId; //para las cuentas del contpaq
    if ($empresaId !== '')
      $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $empresaId)->get()->row()->dia_inicia_semana;
    else
      $dia = '4';

    $configuraciones = $this->configuraciones();
    $semana = $this->fechasDeUnaSemana($semana, $anio, $dia);
    $filtros = array('semana' => $semana['semana'], 'anio' => $anio, 'empresaId' => $empresaId, 'dia_inicia_semana' => $dia);
    $empleados = $this->nomina($configuraciones, $filtros);
    $nombre = "PAGO-{$semana['anio']}-SEM-{$semana['semana']}.txt";

    $content           = array();
    $contentSantr      = array();
    $contador          = 1;
    $contadorSantr     = 1;
    $cuentaSantr       = '92001449876'; // Cuenta cargo santander
    $total_nominaSantr = 0;

    //header santader
    $contentSantr[] = "100001E" . date("mdY") . $this->formatoBanco($cuentaSantr, ' ', 16, 'D') . date("mdY");
    foreach ($empleados as $key => $empleado)
    {
      if($empleado->cuenta_banco != '' && $empleado->esta_asegurado == 't'){
        if($empleado->banco == 'santr') {
          $contentSantr[] = "2" . $this->formatoBanco($contadorSantr+1, '0', 5, 'I') .
                      $this->formatoBanco($contadorSantr, ' ', 7, 'D') .
                      $this->formatoBanco($this->removeTrash($empleado->apellido_paterno), ' ', 30, 'D') .
                      $this->formatoBanco($this->removeTrash($empleado->apellido_materno), ' ', 20, 'D') .
                      $this->formatoBanco($this->removeTrash($empleado->nombre2), ' ', 30, 'D') .
                      $this->formatoBanco($empleado->cuenta_banco, ' ', 16, 'D') .
                      $this->formatoBanco($empleado->nomina_fiscal_total_neto, '0', 18, 'I', true);
          $contadorSantr++;
          $total_nominaSantr += number_format($empleado->nomina_fiscal_total_neto, 2, '.', '');
        } else {
          $content[] = $this->formatoBanco($contador, '0', 9, 'I') .
                      $this->formatoBanco(substr($empleado->rfc, 0, 10), ' ', 16, 'D') .
                      $this->formatoBanco('99', ' ', 2, 'I') .
                      $this->formatoBanco($empleado->cuenta_banco, ' ', 20, 'D') .
                      $this->formatoBanco($empleado->nomina_fiscal_total_neto, '0', 15, 'I', true) .
                      $this->formatoBanco($this->removeTrash($empleado->nombre), ' ', 40, 'D') .
                      $this->formatoBanco('001', ' ', 3, 'D') .
                      $this->formatoBanco('001', ' ', 3, 'D');
          $contador++;
        }
      }
    }

    //Finiquito
    $finiquitos = $this->db->query("SELECT * FROM usuarios AS u INNER JOIN finiquito AS f ON u.id = f.id_empleado
      WHERE f.fecha_salida BETWEEN '{$semana['fecha_inicio']}' AND '{$semana['fecha_final']}'")->result();
    foreach ($finiquitos as $key => $empleado)
    {
      if($empleado->cuenta_banco != '' && $empleado->esta_asegurado == 't'){
        if($empleado->banco == 'santr') {
          $contentSantr[] = "2" . $this->formatoBanco($contadorSantr+1, '0', 5, 'I') .
                      $this->formatoBanco($contadorSantr, ' ', 7, 'D') .
                      $this->formatoBanco($this->removeTrash($empleado->apellido_paterno), ' ', 30, 'D') .
                      $this->formatoBanco($this->removeTrash($empleado->apellido_materno), ' ', 20, 'D') .
                      $this->formatoBanco($this->removeTrash($empleado->nombre), ' ', 30, 'D') .
                      $this->formatoBanco($empleado->cuenta_banco, ' ', 16, 'D') .
                      $this->formatoBanco($empleado->nomina_fiscal_total_neto, '0', 18, 'I', true);
          $contadorSantr++;
          $total_nominaSantr += number_format($empleado->nomina_fiscal_total_neto, 2, '.', '');
        } else {
          $content[] = $this->formatoBanco($contador, '0', 9, 'I') .
                      $this->formatoBanco(substr($empleado->rfc, 0, 10), ' ', 16, 'D') .
                      $this->formatoBanco('99', ' ', 2, 'I') .
                      $this->formatoBanco($empleado->cuenta_banco, ' ', 20, 'D') .
                      $this->formatoBanco($empleado->total_neto, '0', 15, 'I', true) .
                      $this->formatoBanco($this->removeTrash($empleado->apellido_paterno.' '.$empleado->apellido_materno.' '.$empleado->nombre), ' ', 40, 'D') .
                      $this->formatoBanco('001', ' ', 3, 'D') .
                      $this->formatoBanco('001', ' ', 3, 'D');
          $contador++;
        }
      }
    }
    //footer santader
    $contentSantr[] = "3" . $this->formatoBanco($contadorSantr+1, '0', 5, 'I') . $this->formatoBanco($contadorSantr-1, '0', 5, 'I') .
                      $this->formatoBanco($total_nominaSantr, '0', 18, 'I', true);

    $content[]      = '';
    $contentSantr[] = '';
    $content        = implode("\r\n", $content);
    $contentSantr   = implode("\r\n", $contentSantr);

    // $fp = fopen(APPPATH."media/temp/{$nombre}", "wb");
    // fwrite($fp,$content);
    // fclose($fp);

    $zip = new ZipArchive;
    if ($zip->open(APPPATH."media/temp/{$nombre}.zip", ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === true)
    {
      $zip->addFromString('SANTANDER.txt', $contentSantr);
      $zip->addFromString('BBVA Bancomer.txt', $content);

      $zip->close();
    }
    else
    {
      exit('Error al intentar crear el ZIP.');
    }

    header('Content-Type: application/zip');
    header("Content-disposition: attachment; filename={$nombre}.zip");
    readfile(APPPATH."media/temp/{$nombre}.zip");

    unlink(APPPATH."media/temp/{$nombre}.zip");

    // header('Content-Type: text/plain');
    // header("Content-disposition: attachment; filename={$nombre}");
    // readfile(APPPATH."media/temp/{$nombre}");
    // unlink(APPPATH."media/temp/{$nombre}");
  }

  public function removeTrash($valor)
  {
    return str_replace(array('á','é','í','ó','ú','Á','É','Í','Ó','Ú','.','ñ','Ñ',
        '(',')','.',',','°',"'",'!',"\#",'%','=','?','¡','¿','*','{','}','[',']','>','<',';',':','_','-','+','-','&','|','/'),
      array('a','e','i','o','u','A','E','I','O','U',' ','n','N',
        ' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' ',' '), trim($valor));
  }
  public function formatoBanco($valor, $relleno = ' ', $cantidad = 0, $lado = 'I', $decimal = false)
  {
    if ($cantidad != intval(0) && $valor)
    {
      $valor = (string)$valor;

      if ($decimal)
      {
        if (strpos($valor, '.'))
        {
          $valor = number_format($valor, 2, '.', '');
          $valor = explode('.', $valor);

          if (strlen($valor[1]) > 2)
          {
            $valor[1] = substr($valor[1], 0, 2);
          }
          $valor = $valor[0].$valor[1];
        }
        else
        {
          $valor .= '00';
        }
      }

      $longitudValor = strlen($valor);
      for ($i = $longitudValor;  $i < $cantidad; $i++)
      {
        $valor = strtoupper($lado) === 'I' ? $relleno . $valor : $valor . $relleno;
      }

      return $valor;

      // echo "<pre>";
      //   var_dump($valor, $relleno, $cantidad, $lado, $decimal, $longitudValor);
      // echo "</pre>";exit;
    }
  }

  /*
   |------------------------------------------------------------------------
   | PDF's
   |------------------------------------------------------------------------
   */

  public function pdfNominaFiscal($semana, $empresaId, $anio=null, $diaComienza=4)
  {
    $anio = $anio==null? date("Y"): $anio;
    $this->load->model('empresas_model');
    $this->load->model('usuarios_departamentos_model');

    $semana = $this->fechasDeUnaSemana($semana, $anio, $diaComienza);
    $_GET['cid_empresa'] = $empresaId; //para las cuentas del contpaq
    $configuraciones = $this->configuraciones();
    $filtros = array('semana' => $semana['semana'], 'anio' => $anio, 'empresaId' => $empresaId, 'asegurado' => 'si', 'ordenar' => "ORDER BY u.id ASC");
    $empleados = $this->nomina($configuraciones, $filtros);
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);
    $finiquitos = $this->db->query("SELECT * FROM usuarios AS u INNER JOIN finiquito AS f ON u.id = f.id_empleado
      WHERE f.fecha_salida BETWEEN '{$semana['fecha_inicio']}' AND '{$semana['fecha_final']}'")->result();

    // echo "<pre>";
    //   var_dump($empleados);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = "Lista de Raya de {$semana['fecha_inicio']} al {$semana['fecha_final']}";
    $pdf->titulo3 = "Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $pdf->SetFont('Helvetica','', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(6, 27);
    $pdf->Cell(100, 6, "Reg. Pat. IMSS: A4914083100", 0, 0, 'L', 0);

    $pdf->SetFont('Helvetica','B', 10);
    $pdf->SetXY(6, $pdf->GetY() + 6);
    $pdf->Cell(100, 6, "ADMINISTRACION Reg. Pat. IMSS: A4914083100", 0, 0, 'L', 0);

    $total_gral = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0,
      'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'fondo_ahorro' => 0, 'isr' => 0,
      'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0, 'pasistencia' => 0, 'despensa' => 0);

    // $departamentos = $this->usuarios_model->departamentos();
    $_GET['did_empresa'] = $empresaId;
    $departamentos = $this->usuarios_departamentos_model->getPuestos(false)['puestos'];
    foreach ($departamentos as $keyd => $departamento)
    {
      $total_dep = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0,
        'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'fondo_ahorro' => 0, 'isr' => 0,
        'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0, 'pasistencia' => 0, 'despensa' => 0);

      $dep_tiene_empleados = true;
      $y = $pdf->GetY();
      foreach ($empleados as $key => $empleado)
      {
        if($departamento->id_departamento == $empleado->id_departamente)
        {
          if($dep_tiene_empleados)
          {
            $pdf->SetFont('Helvetica','B', 10);
            $pdf->SetXY(6, $pdf->GetY()+6);
            $pdf->Cell(130, 6, $departamento->nombre, 0, 0, 'L', 0);

            $pdf->SetFont('Helvetica','', 10);
            $pdf->SetXY(6, $pdf->GetY() + 8);
            $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
            $pdf->Row(array('', 'Percepción', 'Importe', '', 'Deducción', 'Importe'), false, false, null, 2, 1);

            $pdf->SetFont('Helvetica','', 10);
            $pdf->SetXY(6, $pdf->GetY() - 2);
            $pdf->Cell(200, 2, "________________________________________________________________________________________________________", 0, 0, 'L', 0);
            $dep_tiene_empleados = false;
          }

          $pdf->SetFont('Helvetica','B', 9);
          $pdf->SetXY(6, $pdf->GetY() + 4);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(15, 100));
          $pdf->Row(array($empleado->no_empleado, $empleado->nombre), false, false, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetFont('Helvetica','', 9);
          $pdf->SetXY(6, $pdf->GetY() + 0);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(50, 70, 50));
          $pdf->Row(array($empleado->puesto, "RFC: {$empleado->rfc}", "Afiliciación IMSS: {$empleado->no_seguro}"), false, false, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetXY(6, $pdf->GetY() + 0);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(50, 35, 35, 35, 30));
          $pdf->Row(array("Fecha Ingr: {$empleado->fecha_entrada}", "Sal. diario: {$empleado->salario_diario}", "S.D.I: {$empleado->nomina->salario_diario_integrado}", "S.B.C: {$empleado->nomina->salario_diario_integrado}", 'Cotiza fijo'), false, false, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $horasExtras = 0;
          if ($empleado->horas_extras_dinero > 0)
          {
            $pagoXHora = $empleado->salario_diario / 8;
            $horasExtras = $empleado->horas_extras_dinero / $pagoXHora;
          }

          $pdf->SetXY(6, $pdf->GetY() + 0);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(35, 35, 25, 35, 70));
          $pdf->Row(array("Dias Pagados: {$empleado->dias_trabajados}", "Tot Hrs trab: " . $empleado->dias_trabajados * 8, 'Hrs dia: 8.00', "Hrs extras: " . number_format($horasExtras, 2), "CURP: {$empleado->curp}"), false, false, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $y2 = $pdf->GetY();

          // Percepciones
          $percepciones = $empleado->nomina->percepciones;

          // Sueldo
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Sueldo', String::formatoNumero($percepciones['sueldo']['total'], 2, '$', false)), false, 0, null, 1, 1);
          $total_dep['sueldo'] += $percepciones['sueldo']['total'];
          $total_gral['sueldo'] += $percepciones['sueldo']['total'];
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }

          // P ASISTENCIA
          if ($empleado->pasistencia > 0)
          {
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'P Asistencia', String::formatoNumero($empleado->pasistencia, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['pasistencia'] += $empleado->pasistencia;
            $total_gral['pasistencia'] += $empleado->pasistencia;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y2 = $pdf->GetY();
            }
          }

          // DESPENSA
          if ($empleado->despensa > 0)
          {
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Despensa', String::formatoNumero($empleado->despensa, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['despensa'] += $empleado->despensa;
            $total_gral['despensa'] += $empleado->despensa;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y2 = $pdf->GetY();
            }
          }

          // Horas Extras
          if ($empleado->horas_extras_dinero > 0)
          {
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Horas Extras', String::formatoNumero($empleado->horas_extras_dinero, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['horas_extras'] += $empleado->horas_extras_dinero;
            $total_gral['horas_extras'] += $empleado->horas_extras_dinero;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y2 = $pdf->GetY();
            }
          }

          // Vacaciones y prima vacacional
          if ($empleado->nomina_fiscal_vacaciones > 0)
          {
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Vacaciones', String::formatoNumero($empleado->nomina_fiscal_vacaciones, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['vacaciones'] += $empleado->nomina_fiscal_vacaciones;
            $total_gral['vacaciones'] += $empleado->nomina_fiscal_vacaciones;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y2 = $pdf->GetY();
            }

            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Prima vacacional', String::formatoNumero($empleado->nomina->prima_vacacional, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['prima_vacacional'] += $empleado->nomina->prima_vacacional;
            $total_gral['prima_vacacional'] += $empleado->nomina->prima_vacacional;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y2 = $pdf->GetY();
            }
          }

          // Subsidio
          if ($empleado->nomina_fiscal_subsidio > 0)
          {
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Subsidio', String::formatoNumero($empleado->nomina_fiscal_subsidio, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['subsidio'] += $empleado->nomina_fiscal_subsidio;
            $total_gral['subsidio'] += $empleado->nomina_fiscal_subsidio;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y2 = $pdf->GetY();
            }
          }

          // // PTU
          // if ($empleado->nomina_fiscal_ptu > 0)
          // {
          //   $pdf->SetXY(6, $pdf->GetY());
          //   $pdf->SetAligns(array('L', 'L', 'R'));
          //   $pdf->SetWidths(array(15, 62, 25));
          //   $pdf->Row(array('', 'PTU', String::formatoNumero($empleado->nomina_fiscal_ptu, 2, '$', false)), false, 0, null, 1, 1);
          //   $total_dep['ptu'] += $empleado->nomina_fiscal_ptu;
          //   $total_gral['ptu'] += $empleado->nomina_fiscal_ptu;
          //   if($pdf->GetY() >= $pdf->limiteY)
          //   {
          //     $pdf->AddPage();
          //     $y2 = $pdf->GetY();
          //   }
          // }

          // Aguinaldo
          if ($empleado->nomina_fiscal_aguinaldo > 0)
          {
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Aguinaldo', String::formatoNumero($empleado->nomina_fiscal_aguinaldo, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['aguinaldo'] += $empleado->nomina_fiscal_aguinaldo;
            $total_gral['aguinaldo'] += $empleado->nomina_fiscal_aguinaldo;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y2 = $pdf->GetY();
            }
          }

          $y = $pdf->GetY();

          // Deducciones
          $deducciones = $empleado->nomina->deducciones;
          $pdf->SetFont('Helvetica','', 9);

          $pdf->SetY($y2);
          if ($empleado->infonavit > 0)
          {
            $pdf->SetXY(108, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Infonavit', String::formatoNumero($deducciones['infonavit']['total'], 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['infonavit'] += $deducciones['infonavit']['total'];
            $total_gral['infonavit'] += $deducciones['infonavit']['total'];
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y = $pdf->GetY();
            }
          }

          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'I.M.M.S.', String::formatoNumero($deducciones['imss']['total'] + $deducciones['rcv']['total'], 2, '$', false)), false, 0, null, 1, 1);
          $total_dep['imms'] += $deducciones['imss']['total'] + $deducciones['rcv']['total'];
          $total_gral['imms'] += $deducciones['imss']['total'] + $deducciones['rcv']['total'];
          if($pdf->GetY() >= $pdf->limiteY)
          {
              $pdf->AddPage();
              $y = $pdf->GetY();
            }

          if ($empleado->nomina_fiscal_prestamos > 0)
          {
            $pdf->SetXY(108, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Prestamos', String::formatoNumero($empleado->nomina_fiscal_prestamos, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['prestamos'] += $empleado->nomina_fiscal_prestamos;
            $total_gral['prestamos'] += $empleado->nomina_fiscal_prestamos;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y = $pdf->GetY();
            }
          }

          if ($empleado->fondo_ahorro > 0)
          {
            $pdf->SetXY(108, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Fondo Ahorro', String::formatoNumero($empleado->fondo_ahorro, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['fondo_ahorro'] += $empleado->fondo_ahorro;
            $total_gral['fondo_ahorro'] += $empleado->fondo_ahorro;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y = $pdf->GetY();
            }
          }

          // if ($empleado->descuento_playeras > 0)
          // {
          //   $pdf->SetXY(108, $pdf->GetY());
          //   $pdf->SetAligns(array('L', 'L', 'R'));
          //   $pdf->SetWidths(array(15, 62, 25));
          //   $pdf->Row(array('', 'Desc. Playeras', String::formatoNumero($empleado->descuento_playeras, 2, '$', false)), false, 0, null, 1, 1);
          //   if($pdf->GetY() >= $pdf->limiteY)
          //   {
          //     $pdf->AddPage();
          //     $y = $pdf->GetY();
          //   }
          // }

          if ($empleado->nomina_fiscal_isr > 0)
          {
            $pdf->SetXY(108, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'ISR', String::formatoNumero($empleado->nomina_fiscal_isr, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['isr'] += $empleado->nomina_fiscal_isr;
            $total_gral['isr'] += $empleado->nomina_fiscal_isr;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y = $pdf->GetY();
            }
          }

          if ($y < $pdf->GetY())
          {
            $y = $pdf->GetY();
          }

          // Total percepciones y deducciones
          $pdf->SetXY(6, $y + 2);
          $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
          $total_dep['total_percepcion'] += $empleado->nomina_fiscal_total_percepciones;
          $total_gral['total_percepcion'] += $empleado->nomina_fiscal_total_percepciones;
          $total_dep['total_deduccion'] += $empleado->nomina_fiscal_total_deducciones;
          $total_gral['total_deduccion'] += $empleado->nomina_fiscal_total_deducciones;
          $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($empleado->nomina_fiscal_total_percepciones, 2, '$', false), '', 'Total Deducciones', String::formatoNumero($empleado->nomina_fiscal_total_deducciones, 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
              $pdf->AddPage();

          $pdf->SetFont('Helvetica','B', 9);
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $total_dep['total_neto'] += $empleado->nomina_fiscal_total_neto;
          $total_gral['total_neto'] += $empleado->nomina_fiscal_total_neto;
          $pdf->Row(array('', 'Total Neto', String::formatoNumero($empleado->nomina_fiscal_total_neto, 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
              $pdf->AddPage();

          $pdf->SetFont('Helvetica', '', 9);
          $pdf->SetXY(120, $pdf->GetY()+3);
          $pdf->Cell(200, 2, "--------------------------------------------------------------------------------------", 0, 0, 'L', 0);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();
        }
      }

      //****** Total departamento ******
      if($dep_tiene_empleados == false)
      {
        if($pdf->GetY()+10 >= $pdf->limiteY)
          $pdf->AddPage();
        $pdf->SetFont('Helvetica','B', 10);
        $pdf->SetXY(6, $pdf->GetY()+2);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(200));
        $pdf->Row(array("Total Departamento {$departamento->nombre}"), false, 0, null, 1, 1);
        $pdf->Row(array("____________________________________________________________________________________________________"), false, 0, null, 1, 1);

        $pdf->SetFont('Helvetica','', 9);
        $y2 = $pdf->GetY();
        // Sueldo
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Sueldo', String::formatoNumero($total_dep['sueldo'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        // P Asistencia
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'P Asistencia', String::formatoNumero($total_dep['pasistencia'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        // Despensa
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Despensa', String::formatoNumero($total_dep['despensa'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        // Horas Extras
        if ($total_dep['horas_extras'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Horas Extras', String::formatoNumero($total_dep['horas_extras'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        // Vacaciones y prima vacacional
        if ($total_dep['vacaciones'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Vacaciones', String::formatoNumero($total_dep['vacaciones'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }

          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Prima vacacional', String::formatoNumero($total_dep['prima_vacacional'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        // Subsidio
        if ($total_dep['subsidio'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Subsidio', String::formatoNumero($total_dep['subsidio'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        // PTU
        if ($total_dep['ptu'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'PTU', String::formatoNumero($total_dep['ptu'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        // Aguinaldo
        if ($total_dep['aguinaldo'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Aguinaldo', String::formatoNumero($total_dep['aguinaldo'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        $y = $pdf->GetY();

        // Deducciones
        $deducciones = $empleado->nomina->deducciones;
        $pdf->SetFont('Helvetica','', 9);

        $pdf->SetY($y2);
        if ($total_dep['infonavit'] > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Infonavit', String::formatoNumero($total_dep['infonavit'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'I.M.M.S.', String::formatoNumero($total_dep['imms'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }

        if ($total_dep['prestamos'] > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Prestamos', String::formatoNumero($total_dep['prestamos'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        if ($total_dep['fondo_ahorro'] > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Fondo Ahorro', String::formatoNumero($total_dep['fondo_ahorro'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        if ($total_dep['isr'] > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'ISR', String::formatoNumero($total_dep['isr'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        if ($y < $pdf->GetY())
        {
          $y = $pdf->GetY();
        }

        // Total percepciones y deducciones
        $pdf->SetXY(6, $y + 2);
        $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
        $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($total_dep['total_percepcion'], 2, '$', false), '', 'Total Deducciones', String::formatoNumero($total_dep['total_deduccion'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

        $pdf->SetFont('Helvetica','B', 9);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Total Neto', String::formatoNumero($total_dep['total_neto'], 2, '$', false)), false, 0, null, 1, 1);
      }

      $pdf->SetFont('Helvetica','', 10);
    }

    //finiquito
    $total_dep = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0,
        'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'isr' => 0,
        'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0);
    $dep_tiene_empleados = true;
    $y = $pdf->GetY();
    foreach ($finiquitos as $key => $empleado)
    {
      if($dep_tiene_empleados)
      {
        $pdf->SetFont('Helvetica','B', 10);
        $pdf->SetXY(6, $pdf->GetY()+6);
        $pdf->Cell(130, 6, 'Finiquitos', 0, 0, 'L', 0);

        $pdf->SetFont('Helvetica','', 10);
        $pdf->SetXY(6, $pdf->GetY() + 8);
        $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
        $pdf->Row(array('', 'Percepción', 'Importe', '', 'Deducción', 'Importe'), false, false, null, 2, 1);

        $pdf->SetFont('Helvetica','', 10);
        $pdf->SetXY(6, $pdf->GetY() - 2);
        $pdf->Cell(200, 2, "________________________________________________________________________________________________________", 0, 0, 'L', 0);
        $dep_tiene_empleados = false;
      }

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY() + 4);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(15, 100));
      $pdf->Row(array($empleado->id, $empleado->apellido_paterno.' '.$empleado->apellido_materno.' '.$empleado->nombre), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetFont('Helvetica','', 9);
      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(50, 70, 50));
      $pdf->Row(array('Sin Puesto', "RFC: {$empleado->rfc}", "Afiliciación IMSS: {$empleado->no_seguro}"), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(50, 35, 35, 35, 30));
      $pdf->Row(array("Fecha Ingr: {$empleado->fecha_entrada}", "Sal. diario: {$empleado->salario_diario}", "S.D.I: 0", "S.B.C: 0", 'Cotiza fijo'), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $horasExtras = 0;

      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(35, 35, 25, 35, 70));
      $pdf->Row(array("Dias Pagados: {$empleado->dias_trabajados}", "Tot Hrs trab: " . $empleado->dias_trabajados * 8, 'Hrs dia: 8.00', "Hrs extras: " . number_format('0', 2), "CURP: {$empleado->curp}"), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $y2 = $pdf->GetY();

      // Percepciones
      // $percepciones = $empleado->nomina->percepciones;

      // Sueldo
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Sueldo', String::formatoNumero($empleado->sueldo_semanal, 2, '$', false)), false, 0, null, 1, 1);
      $total_dep['sueldo'] += $empleado->sueldo_semanal;
      $total_gral['sueldo'] += $empleado->sueldo_semanal;
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }

      // // Horas Extras
      // if ($empleado->horas_extras_dinero > 0)
      // {
      //   $pdf->SetXY(6, $pdf->GetY());
      //   $pdf->SetAligns(array('L', 'L', 'R'));
      //   $pdf->SetWidths(array(15, 62, 25));
      //   $pdf->Row(array('', 'Horas Extras', String::formatoNumero($empleado->horas_extras_dinero, 2, '$', false)), false, 0, null, 1, 1);
      //   $total_dep['horas_extras'] += $empleado->horas_extras_dinero;
      //   $total_gral['horas_extras'] += $empleado->horas_extras_dinero;
      //   if($pdf->GetY() >= $pdf->limiteY)
      //   {
      //     $pdf->AddPage();
      //     $y2 = $pdf->GetY();
      //   }
      // }

      // Vacaciones y prima vacacional
      if ($empleado->vacaciones > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Vacaciones', String::formatoNumero($empleado->vacaciones, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['vacaciones'] += $empleado->vacaciones;
        $total_gral['vacaciones'] += $empleado->vacaciones;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Prima vacacional', String::formatoNumero($empleado->prima_vacacional, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['prima_vacacional'] += $empleado->prima_vacacional;
        $total_gral['prima_vacacional'] += $empleado->prima_vacacional;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // Subsidio
      if ($empleado->subsidio > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Subsidio', String::formatoNumero($empleado->subsidio, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['subsidio'] += $empleado->subsidio;
        $total_gral['subsidio'] += $empleado->subsidio;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // // PTU
      // if ($empleado->nomina_fiscal_ptu > 0)
      // {
      //   $pdf->SetXY(6, $pdf->GetY());
      //   $pdf->SetAligns(array('L', 'L', 'R'));
      //   $pdf->SetWidths(array(15, 62, 25));
      //   $pdf->Row(array('', 'PTU', String::formatoNumero($empleado->nomina_fiscal_ptu, 2, '$', false)), false, 0, null, 1, 1);
      //   $total_dep['ptu'] += $empleado->nomina_fiscal_ptu;
      //   $total_gral['ptu'] += $empleado->nomina_fiscal_ptu;
      //   if($pdf->GetY() >= $pdf->limiteY)
      //   {
      //     $pdf->AddPage();
      //     $y2 = $pdf->GetY();
      //   }
      // }

      // Aguinaldo
      if ($empleado->aguinaldo > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Aguinaldo', String::formatoNumero($empleado->aguinaldo, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['aguinaldo'] += $empleado->aguinaldo;
        $total_gral['aguinaldo'] += $empleado->aguinaldo;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      $y = $pdf->GetY();

      // Deducciones
      // $deducciones = $empleado->nomina->deducciones;
      $pdf->SetFont('Helvetica','', 9);

      $pdf->SetY($y2);

      if ($empleado->isr != 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'ISR', String::formatoNumero($empleado->isr, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['isr'] += $empleado->isr;
        $total_gral['isr'] += $empleado->isr;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      if ($y < $pdf->GetY())
      {
        $y = $pdf->GetY();
      }

      // Total percepciones y deducciones
      $pdf->SetXY(6, $y + 2);
      $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
      $total_dep['total_percepcion'] += $empleado->total_percepcion;
      $total_gral['total_percepcion'] += $empleado->total_percepcion;
      $total_dep['total_deduccion'] += $empleado->total_deduccion;
      $total_gral['total_deduccion'] += $empleado->total_deduccion;
      $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($empleado->total_percepcion, 2, '$', false), '', 'Total Deducciones', String::formatoNumero($empleado->total_deduccion, 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $total_dep['total_neto'] += $empleado->total_neto;
      $total_gral['total_neto'] += $empleado->total_neto;
      $pdf->Row(array('', 'Total Neto', String::formatoNumero($empleado->total_neto, 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica', '', 9);
      $pdf->SetXY(120, $pdf->GetY()+3);
      $pdf->Cell(200, 2, "--------------------------------------------------------------------------------------", 0, 0, 'L', 0);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
    }

    //****** Total finiquito ******
    if($dep_tiene_empleados == false)
    {
      if($pdf->GetY()+10 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetFont('Helvetica','B', 10);
      $pdf->SetXY(6, $pdf->GetY()+2);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(200));
      $pdf->Row(array("Total Departamento Finiquito"), false, 0, null, 1, 1);
      $pdf->Row(array("____________________________________________________________________________________________________"), false, 0, null, 1, 1);

      $pdf->SetFont('Helvetica','', 9);
      $y2 = $pdf->GetY();
      // Sueldo
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Sueldo', String::formatoNumero($total_dep['sueldo'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }

      // Horas Extras
      if ($total_dep['horas_extras'] > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Horas Extras', String::formatoNumero($total_dep['horas_extras'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // Vacaciones y prima vacacional
      if ($total_dep['vacaciones'] > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Vacaciones', String::formatoNumero($total_dep['vacaciones'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Prima vacacional', String::formatoNumero($total_dep['prima_vacacional'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // Subsidio
      if ($total_dep['subsidio'] > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Subsidio', String::formatoNumero($total_dep['subsidio'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // PTU
      if ($total_dep['ptu'] > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'PTU', String::formatoNumero($total_dep['ptu'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // Aguinaldo
      if ($total_dep['aguinaldo'] > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Aguinaldo', String::formatoNumero($total_dep['aguinaldo'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      $y = $pdf->GetY();

      // Deducciones
      // $deducciones = $empleado->nomina->deducciones;
      $pdf->SetFont('Helvetica','', 9);

      $pdf->SetY($y2);
      if ($total_dep['infonavit'] > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Infonavit', String::formatoNumero($total_dep['infonavit'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'I.M.M.S.', String::formatoNumero($total_dep['imms'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }

      if ($total_dep['prestamos'] > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Prestamos', String::formatoNumero($total_dep['prestamos'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      if ($total_dep['isr'] > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'ISR', String::formatoNumero($total_dep['isr'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      if ($y < $pdf->GetY())
      {
        $y = $pdf->GetY();
      }

      // Total percepciones y deducciones
      $pdf->SetXY(6, $y + 2);
      $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
      $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($total_dep['total_percepcion'], 2, '$', false), '', 'Total Deducciones', String::formatoNumero($total_dep['total_deduccion'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Total Neto', String::formatoNumero($total_dep['total_neto'], 2, '$', false)), false, 0, null, 1, 1);
    }

    //********* Total general ***************
    if($pdf->GetY()+10 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Helvetica','B', 10);
    $pdf->SetXY(6, $pdf->GetY()+2);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array("Total General"), false, 0, null, 1, 1);
    $pdf->Row(array("____________________________________________________________________________________________________"), false, 0, null, 1, 1);

    $pdf->SetFont('Helvetica','', 9);
    $y2 = $pdf->GetY();
    // Sueldo
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25));
    $pdf->Row(array('', 'Sueldo', String::formatoNumero($total_gral['sueldo'], 2, '$', false)), false, 0, null, 1, 1);
    if($pdf->GetY() >= $pdf->limiteY)
    {
      $pdf->AddPage();
      $y2 = $pdf->GetY();
    }

    // P Asistencia
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25));
    $pdf->Row(array('', 'P Asistencia', String::formatoNumero($total_gral['pasistencia'], 2, '$', false)), false, 0, null, 1, 1);
    if($pdf->GetY() >= $pdf->limiteY)
    {
      $pdf->AddPage();
      $y2 = $pdf->GetY();
    }

    // Despensa
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25));
    $pdf->Row(array('', 'Despensa', String::formatoNumero($total_gral['despensa'], 2, '$', false)), false, 0, null, 1, 1);
    if($pdf->GetY() >= $pdf->limiteY)
    {
      $pdf->AddPage();
      $y2 = $pdf->GetY();
    }

    // Horas Extras
    if ($total_gral['horas_extras'] > 0)
    {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Horas Extras', String::formatoNumero($total_gral['horas_extras'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }
    }

    // Vacaciones y prima vacacional
    if ($total_gral['vacaciones'] > 0)
    {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Vacaciones', String::formatoNumero($total_gral['vacaciones'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }

      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Prima vacacional', String::formatoNumero($total_gral['prima_vacacional'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }
    }

    // Subsidio
    if ($total_gral['subsidio'] > 0)
    {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Subsidio', String::formatoNumero($total_gral['subsidio'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }
    }

    // PTU
    if ($total_gral['ptu'] > 0)
    {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'PTU', String::formatoNumero($total_gral['ptu'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }
    }

    // Aguinaldo
    if ($total_gral['aguinaldo'] > 0)
    {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Aguinaldo', String::formatoNumero($total_gral['aguinaldo'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }
    }

    $y = $pdf->GetY();

    // Deducciones
    // $deducciones = $empleado->nomina->deducciones;
    $pdf->SetFont('Helvetica','', 9);

    $pdf->SetY($y2);
    if ($total_gral['infonavit'] > 0)
    {
      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Infonavit', String::formatoNumero($total_gral['infonavit'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y = $pdf->GetY();
      }
    }

    $pdf->SetXY(108, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25));
    $pdf->Row(array('', 'I.M.M.S.', String::formatoNumero($total_gral['imms'], 2, '$', false)), false, 0, null, 1, 1);
    if($pdf->GetY() >= $pdf->limiteY)
    {
        $pdf->AddPage();
        $y = $pdf->GetY();
      }

    if ($total_gral['prestamos'] > 0)
    {
      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Prestamos', String::formatoNumero($total_gral['prestamos'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y = $pdf->GetY();
      }
    }

    if ($total_gral['fondo_ahorro'] > 0)
    {
      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Fondo Ahorro', String::formatoNumero($total_gral['fondo_ahorro'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y = $pdf->GetY();
      }
    }

    if ($total_gral['isr'] > 0)
    {
      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'ISR', String::formatoNumero($total_gral['isr'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y = $pdf->GetY();
      }
    }

    if ($y < $pdf->GetY())
    {
      $y = $pdf->GetY();
    }

    // Total percepciones y deducciones
    $pdf->SetXY(6, $y + 2);
    $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
    $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($total_gral['total_percepcion'], 2, '$', false), '', 'Total Deducciones', String::formatoNumero($total_gral['total_deduccion'], 2, '$', false)), false, 0, null, 1, 1);
    if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

    $pdf->SetFont('Helvetica','B', 9);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25));
    $pdf->Row(array('', 'Total Neto', String::formatoNumero($total_gral['total_neto'], 2, '$', false)), false, 0, null, 1, 1);

    $pdf->Output('Nomina.pdf', 'I');
  }

  public function pdfRptDataNominaFiscal($datos, $empresaId)
  {
    // echo "<pre>";
    //   var_dump($datos, $empresaId, $empleadoId);
    // echo "</pre>";exit;
    // $startTime = new DateTime(date('Y-m-d H:i:s'));

    $this->load->library('cfdi');
    $this->load->library('facturartebarato_api');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');

    // Obtiene las configuraciones.
    $_GET['cid_empresa'] = $empresaId; //para las cuentas del contpaq
    $configuraciones = $this->configuraciones();

    // Almacenara los datos de las nominas de cada empleado para despues
    // insertarlas.
    $nominasEmpleados = array();

    // Almacenara los datos de los prestamos de cada empleado para despues
    // insertarlos.
    $prestamosEmpleados = array();

    // Obtiene el rango de fechas de la semana.
    $fechasSemana = $this->fechasDeUnaSemana($datos['numSemana']);

    // Auxiliar para saber si hubo un error al momento de timbrar alguna nomina.
    $errorTimbrar = false;

    // Recorre los empleados para agregar y timbrar sus nominas.
    foreach ($datos['empleado_id'] as $key => $empleadoId)
    {
        // $empleado = $this->usuarios_model->get_usuario_info($empleadoId, true);
        // $empleado = $this->usuarios_model->get_usuario_info($empleadoId, true);

        $empleadoNomina = $this->nomina(
          $configuraciones,
          array('semana' => $datos['numSemana'], 'empresaId' => $empresaId),
          $empleadoId,
          $datos['horas_extras'][$key],
          $datos['descuento_playeras'][$key],
          $datos['subsidio'][$key],
          $datos['isr'][$key],
          $datos['utilidad_empresa'],
          $datos['descuento_otros'][$key]
        );
        // unset($empleadoNomina[0]->nomina->percepciones['subsidio']);
        // unset($empleadoNomina[0]->nomina->percepciones['ptu']);
        // unset($empleadoNomina[0]->nomina->deducciones['isr']);

        $valorUnitario = 0; // Total de las Percepciones.

        // Recorre las percepciones del empleado.
        foreach ($empleadoNomina[0]->nomina->percepciones as $tipoPercepcion => $percepcion)
        {
          // Si activaron las vacaciones entonces suma las vacaciones y la prima vacacional.
          if ($tipoPercepcion === 'vacaciones' || $tipoPercepcion === 'prima_vacacional')
          {
            if ($datos['con_vacaciones'][$key] === '1' && $empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]['total'] != 0)
            {
              $valorUnitario += $percepcion['total'];
              unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]['total']);
            }
            else
              unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]);
          }
          // Si el tipo de percepcion es aguinaldo
          else if ($tipoPercepcion === 'aguinaldo')
          {
            // Si activarion el aguinaldo entonces lo suma.
            if ($datos['con_aguinaldo'] === '1')
            {
              $valorUnitario += $percepcion['total'];
              unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]['total']);
            }
            else
              unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]);
          }
          // Si es el sueldo u horas extras los suma directo.
          else
          {
            $valorUnitario += $percepcion['total'];
            unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]['total']);
          }
        }

        $isr = 0; // retenciones
        $descuento = 0; // Total de las deducciones(gravado + excento) excepto el ISR.
        // Recorre las deducciones del empleado.
        foreach ($empleadoNomina[0]->nomina->deducciones as $tipoDeduccion => $deduccion)
        {
          if ($tipoDeduccion !== 'isr')
          {
            $descuento += $deduccion['total'];
          }
          else
          {
            $isr = $deduccion['total'];
          }
          unset($empleadoNomina[0]->nomina->deducciones[$tipoDeduccion]['total']);
        }

        // Le suma al imss el rcv, para tener solamente la deduccion imss.
        $empleadoNomina[0]->nomina->deducciones['imss']['ImporteExcento'] += $empleadoNomina[0]->nomina->deducciones['rcv']['ImporteExcento'];
        unset($empleadoNomina[0]->nomina->deducciones['rcv']);

        $vacaciones = isset($empleadoNomina[0]->nomina->percepciones['vacaciones'])
          ? $empleadoNomina[0]->nomina->percepciones['vacaciones']['ImporteGravado'] +
            $empleadoNomina[0]->nomina->percepciones['vacaciones']['ImporteExcento']
          : 0;

        $primaVacacionalGravable = isset($empleadoNomina[0]->nomina->percepciones['prima_vacacional'])
          ? $empleadoNomina[0]->nomina->percepciones['prima_vacacional']['ImporteGravado']
          : 0;

        $primaVacacionalExcento = isset($empleadoNomina[0]->nomina->percepciones['prima_vacacional'])
          ? $empleadoNomina[0]->nomina->percepciones['prima_vacacional']['ImporteExcento']
          : 0;

        $primaVacacional = isset($empleadoNomina[0]->nomina->percepciones['prima_vacacional'])
          ? $empleadoNomina[0]->nomina->percepciones['prima_vacacional']['ImporteGravado'] +
            $empleadoNomina[0]->nomina->percepciones['prima_vacacional']['ImporteExcento']
          : 0;

        $aguinaldoGravable = isset($empleadoNomina[0]->nomina->percepciones['aguinaldo'])
          ? $empleadoNomina[0]->nomina->percepciones['aguinaldo']['ImporteGravado']
          : 0;

        $aguinaldoExcento = isset($empleadoNomina[0]->nomina->percepciones['aguinaldo'])
          ? $empleadoNomina[0]->nomina->percepciones['aguinaldo']['ImporteExcento']
          : 0;

        $aguinaldo = isset($empleadoNomina[0]->nomina->percepciones['aguinaldo'])
          ? $empleadoNomina[0]->nomina->percepciones['aguinaldo']['ImporteGravado'] +
            $empleadoNomina[0]->nomina->percepciones['aguinaldo']['ImporteExcento']
          : 0;

        $imss = $empleadoNomina[0]->nomina->deducciones['imss']['ImporteGravado'] +
                $empleadoNomina[0]->nomina->deducciones['imss']['ImporteExcento'];

        $infonavit = $empleadoNomina[0]->nomina->deducciones['infonavit']['ImporteGravado'] +
                     $empleadoNomina[0]->nomina->deducciones['infonavit']['ImporteExcento'];

        $ptuGravado = isset($empleadoNomina[0]->nomina->percepciones['ptu'])
          ? $empleadoNomina[0]->nomina->percepciones['ptu']['ImporteGravado']
          : 0;

        $ptuExcento = isset($empleadoNomina[0]->nomina->percepciones['ptu'])
          ? $empleadoNomina[0]->nomina->percepciones['ptu']['ImporteExcento']
          : 0;

        $ptu = $ptuGravado + $ptuExcento;

        $totalPrestamos = 0;
        // Recorre los prestamos del empleado para
        foreach ($empleadoNomina[0]->prestamos as $prestamo)
        {
          $totalPrestamos += floatval($prestamo['pago_semana_descontar']);

          $prestamosEmpleados[] = array(
            'id_empleado' => $empleadoId,
            'id_empresa' => $empresaId,
            'anio' => date('Y'),
            'semana' => $datos['numSemana'],
            'id_prestamo' => $prestamo['id_prestamo'],
            'monto' => $prestamo['pago_semana_descontar'],
          );

          // Suma lo que lleva pagado mas lo que se esta abonando.
          $totalAbonado = floatval($prestamo['total_pagado']) + floatval($prestamo['pago_semana_descontar']);

          // Si ya termino de pagar el prestamo entonces le cambia el status.
          if ($totalAbonado >= floatval($prestamo['prestado']))
          {
            $this->db->update('nomina_prestamos', array('status' => 'f'), array('id_prestamo' => $prestamo['id_prestamo']));
          }
        }

        $totalNoFiscal = floatval($datos['total_no_fiscal'][$key]);

        $nominasEmpleados[] = array(
          'id_empleado' => $empleadoId,
          'nombre_empleado' => $empleadoNomina[0]->nombre,
          'id_empresa' => $empresaId,
          'anio' => $fechasSemana['anio'],
          'semana' => $datos['numSemana'],
          'fecha_inicio' => $fechasSemana['fecha_inicio'],
          'fecha_final' => $fechasSemana['fecha_final'],
          'dias_trabajados' => $empleadoNomina[0]->dias_trabajados,
          'salario_diario' => $empleadoNomina[0]->salario_diario,
          'salario_integral' => $empleadoNomina[0]->nomina->salario_diario_integrado,
          'subsidio' => $datos['subsidio'][$key],
          'sueldo_semanal' => $empleadoNomina[0]->nomina->percepciones['sueldo']['ImporteGravado'],
          'bonos' => $empleadoNomina[0]->bonos,
          'otros' => $empleadoNomina[0]->otros,
          'subsidio_pagado' => 0,
          'vacaciones' => $vacaciones,
          'prima_vacacional_grabable' => $primaVacacionalGravable,
          'prima_vacacional_exento' => $primaVacacionalExcento,
          'prima_vacacional' => $primaVacacional,
          'aguinaldo_grabable' => $aguinaldoGravable,
          'aguinaldo_exento' => $aguinaldoExcento,
          'aguinaldo' => $aguinaldo,
          'total_percepcion' => $valorUnitario,
          'imss' => $imss,
          'vejez' => 0,
          'isr' => $datos['isr'][$key],
          'infonavit' => $infonavit,
          'subsidio_cobrado' => 0,
          'prestamos' => $totalPrestamos,
          'total_deduccion' => $descuento + $isr,
          'total_neto' => $valorUnitario - $descuento - $isr,
          'id_empleado_creador' => $this->session->userdata('id_usuario'),
          'ptu_exento' => $ptuExcento,
          'ptu_grabable' => $ptuGravado,
          'ptu' => $ptu,
          'id_puesto' => $empleadoNomina[0]->id_puesto,
          'nombre_puesto' => $empleadoNomina[0]->puesto,
          'salario_real' => $empleadoNomina[0]->salario_diario_real,
          'sueldo_real' => $empleadoNomina[0]->salario_diario_real * $empleadoNomina[0]->dias_trabajados,
          'total_no_fiscal' => $totalNoFiscal,
          'horas_extras' => $empleadoNomina[0]->horas_extras_dinero,
          'horas_extras_grabable' => $empleadoNomina[0]->nomina->percepciones['horas_extras']['ImporteGravado'],
          'horas_extras_excento' => $empleadoNomina[0]->nomina->percepciones['horas_extras']['ImporteExcento'],
          'descuento_playeras' => $datos['descuento_playeras'][$key],
          'descuento_otros' => $datos['descuento_otros'][$key],
          'utilidad_empresa' => $empleadoNomina[0]->utilidad_empresa,
          'domingo' => $empleadoNomina[0]->domingo,
        );
    }

    return array('data' => $nominasEmpleados);
  }

  public function pdfRptNominaFiscal($semana, $empresaId, $anio)
  {
    // var_dump($_POST);
    // exit();
    // $empleados = $this->pdfRptDataNominaFiscal($_POST, $empresaId);
    $this->load->model('usuarios_model');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_departamentos_model');
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);
    $semana = $this->fechasDeUnaSemana($semana, $anio, $empresa['info']->dia_inicia_semana);

    $finiquitos = $this->db->query("SELECT * FROM usuarios AS u INNER JOIN finiquito AS f ON u.id = f.id_empleado
      WHERE f.id_empresa = {$empresaId} AND f.fecha_salida BETWEEN '{$semana['fecha_inicio']}' AND '{$semana['fecha_final']}'")->result();


    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('L', 'mm', 'Letter');
    $pdf->show_head = true;
    $pdf->logo = $empresa['info']->logo;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = "Lista de Raya de {$semana['fecha_inicio']} al {$semana['fecha_final']}";
    $pdf->titulo3 = "Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    //no mostrar algunas columnas
    $ver_des_otro = $ver_des_playera = false;
    foreach ($_POST['empleado_id'] as $key => $empleado){
      if($_POST['descuento_playeras'][$key]>0) $ver_des_playera = true;
      if($_POST['descuento_otros'][$key]>0) $ver_des_otro = true;
    }

    $ver_total_prestamos = $ver_total_domingo = $ver_total_otros = $ver_infonavit = $ver_trans = $ver_fondo_arro = 0;
    foreach ($_POST['empleado_id'] as $key => $empleado)
    {
      $ver_infonavit       += $_POST['total_infonavit'][$key];
      $ver_trans           += $_POST['ttotal_nomina'][$key];
      $ver_fondo_arro      += $_POST['fondo_ahorro'][$key];
      $ver_total_prestamos += $_POST['total_prestamos'][$key];
      $ver_total_domingo   += $_POST['domingo'][$key];
      $ver_total_otros     += $_POST['bonos'][$key]+$_POST['otros'][$key];
    }

    $columnas = array('n' => array(), 'w' => array(6, 64, 20, 20, 20), 'a' => array('L', 'L', 'R', 'R', 'R'));
    $columnas['n'][] = 'No';
    $columnas['n'][] = 'NOMBRE';
    $columnas['n'][] = 'SUELDO';
    // $columnas['n'][] = 'ASISTENCIA';
    // $columnas['n'][] = 'DESPENSA';

    if ($ver_total_otros != 0)
    {
      $columnas['n'][] = 'OTRAS';
      $columnas['w'][] = 20;
      $columnas['a'][] = 'R';
    }

    if ($ver_total_domingo != 0)
    {
      $columnas['n'][] = 'DOMINGO';
      $columnas['w'][] = 20;
      $columnas['a'][] = 'R';
    }

    if ($ver_total_prestamos != 0)
    {
      $columnas['n'][] = 'PTMO';
      $columnas['w'][] = 20;
      $columnas['a'][] = 'R';
    }

    if ($ver_fondo_arro != 0)
    {
      $columnas['n'][] = 'FA';
      $columnas['w'][] = 15;
      $columnas['a'][] = 'R';
    }

    if ($ver_infonavit != 0)
    {
      $columnas['n'][] = 'INFONAVIT';
      $columnas['w'][] = 20;
      $columnas['a'][] = 'R';
    }

    if($ver_des_playera){
      $columnas['n'][] = 'DESC. PLAY';
      $columnas['w'][] = 20;
      $columnas['a'][] = 'R';
    }
    if($ver_des_otro){
      $columnas['n'][] = 'DESC. OTRO';
      $columnas['w'][] = 20;
      $columnas['a'][] = 'R';
    }
    $columnas['n'][] = 'TOTAL A PAGAR';

    if ($ver_trans !== 0)
    {
      $columnas['n'][] = 'TRANSF';
      $columnas['w'][] = 20;
      $columnas['a'][] = 'R';
    }

    $columnas['n'][] = 'TOTAL COMPLEM';

    $pdf->SetFont('Helvetica','B', 8);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns($columnas['a']);
    $pdf->SetWidths($columnas['w']);
    $pdf->Row($columnas['n'], false, false, null, 2, 1);

    $total_fondo = $ttotal_aseg_no_trs = $sueldo_semanal_real = $otras_percepciones = $domingo =
    $total_prestamos = $total_infonavit = $descuento_playeras = $descuento_otros = $ttotal_pagar =
    $ttotal_nomina = $total_no_fiscal = $premio_asistencia = $despensa = 0;
    $y = $pdf->GetY();

    // $departamentos = $this->usuarios_model->departamentos();
    $_GET['did_empresa'] = $empresaId;
    $departamentos = $this->usuarios_departamentos_model->getPuestos(false)['puestos'];

    // echo "<pre>";
    //   var_dump($infonavit, $trans);
    // echo "</pre>";exit;
    $numero_empleado = 0;
    foreach ($departamentos as $keyd => $departamento)
    {
      if($pdf->GetY()+8 >= $pdf->limiteY){
        $pdf->AddPage();
        $pdf->SetFont('Helvetica','B', 8);
        $pdf->SetXY(6, $pdf->GetY());
        // $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R'));
        // $pdf->SetWidths(array(64, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20));
        $pdf->Row($columnas['n'], false, false, null, 2, 1);
      }

      // $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R'));
      // $pdf->SetWidths(array(64, 22, 20, 20, 20, 20, 20, 20, 20, 20, 20));
      $total_fondo1 = $sueldo_semanal_real1 = $otras_percepciones1 = $domingo1 = $total_prestamos1 =
      $total_infonavit1 = $descuento_playeras1 = $descuento_otros1 = $ttotal_pagar1 = $ttotal_nomina1 =
      $total_no_fiscal1 = $premio_asistencia1 = $despensa1 = 0;

      $pdf->SetFont('Helvetica','B', 10);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Cell(130, 6, $departamento->nombre, 0, 0, 'L', 0);

      $pdf->SetXY(6, $pdf->GetY()+6);
      foreach ($_POST['empleado_id'] as $key => $empleado)
      {
        if($departamento->id_departamento == $_POST['departamento_id'][$key])
        {
          $numero_empleado++;
          $empleado = $this->usuarios_model->get_usuario_info($empleado, true)['info'][0];

          $pdf->SetFont('Helvetica','', 8);
          if($pdf->GetY()+8 >= $pdf->limiteY){
            $pdf->AddPage();
            $pdf->SetFont('Helvetica','B', 8);
            $pdf->SetXY(6, $pdf->GetY());
            // $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R'));
            // $pdf->SetWidths(array(64, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20));
            $pdf->Row($columnas['n'], false, false, null, 2, 1);
          }

          $pdf->SetFont('Helvetica','', 8);
          $total_pagar = $_POST['sueldo_semanal_real'][$key] +
            ($_POST['bonos'][$key]+$_POST['otros'][$key]) +
            $_POST['domingo'][$key] -
            $_POST['total_prestamos'][$key] -
            $_POST['total_infonavit'][$key] -
            $_POST['descuento_playeras'][$key] -
            $_POST['descuento_otros'][$key] -
            $_POST['fondo_ahorro'][$key];
          $pdf->SetXY(6, $pdf->GetY());

          $dataarr = array();
          $dataarr[] = $numero_empleado;
          $dataarr[] = $empleado->apellido_paterno.' '.$empleado->apellido_materno.' '.$empleado->nombre;
          $dataarr[] = String::formatoNumero($_POST['sueldo_semanal_real'][$key], 2, '$', false);
          // $dataarr[] = String::formatoNumero($_POST['premio_asistencia'][$key], 2, '$', false);
          // $dataarr[] = String::formatoNumero($_POST['despensa'][$key], 2, '$', false);

          if ($ver_total_otros != 0)
            $dataarr[] = String::formatoNumero(($_POST['bonos'][$key]+$_POST['otros'][$key]), 2, '$', false);
          if ($ver_total_domingo != 0)
            $dataarr[] = String::formatoNumero($_POST['domingo'][$key], 2, '$', false);
          if ($ver_total_prestamos != 0)
            $dataarr[] = String::formatoNumero($_POST['total_prestamos'][$key], 2, '$', false);

          if ($ver_fondo_arro != 0)
          {
            $dataarr[] = String::formatoNumero($_POST['fondo_ahorro'][$key], 2, '$', false);
          }

          if ($ver_infonavit != 0)
          {
            $dataarr[] = String::formatoNumero($_POST['total_infonavit'][$key], 2, '$', false);
          }

          if($ver_des_playera)
            $dataarr[] = String::formatoNumero($_POST['descuento_playeras'][$key], 2, '$', false);
          if($ver_des_otro)
            $dataarr[] = String::formatoNumero($_POST['descuento_otros'][$key], 2, '$', false);
          $dataarr[] = String::formatoNumero($total_pagar, 2, '$', false);

          if ($ver_trans != 0)
          {
            $dataarr[] = String::formatoNumero($_POST['ttotal_nomina'][$key], 2, '$', false);
          }

          $dataarr[] = String::formatoNumero($_POST['total_no_fiscal'][$key], 2, '$', false);

          $pdf->Row($dataarr, false, true, null, 2, 1);
          $sueldo_semanal_real += $_POST['sueldo_semanal_real'][$key];
          // $premio_asistencia   += $_POST['premio_asistencia'][$key];
          // $despensa            += $_POST['despensa'][$key];
          $otras_percepciones  += ($_POST['bonos'][$key]+$_POST['otros'][$key]);
          $domingo             += $_POST['domingo'][$key];
          $total_prestamos     += $_POST['total_prestamos'][$key];
          $total_infonavit     += $_POST['total_infonavit'][$key];
          $total_fondo         += $_POST['fondo_ahorro'][$key];
          $descuento_playeras  += $_POST['descuento_playeras'][$key];
          $descuento_otros     += $_POST['descuento_otros'][$key];
          $ttotal_pagar        += $total_pagar;
          $ttotal_nomina       += $_POST['ttotal_nomina'][$key];
          $total_no_fiscal     += $_POST['total_no_fiscal'][$key];
          $ttotal_aseg_no_trs  += $_POST['total_percepciones'][$key]-$_POST['total_deducciones'][$key];

          $sueldo_semanal_real1 += $_POST['sueldo_semanal_real'][$key];
          // $premio_asistencia1   += $_POST['premio_asistencia'][$key];
          // $despensa1            += $_POST['despensa'][$key];
          $otras_percepciones1  += ($_POST['bonos'][$key]+$_POST['otros'][$key]);
          $domingo1             += $_POST['domingo'][$key];
          $total_prestamos1     += $_POST['total_prestamos'][$key];
          $total_infonavit1     += $_POST['total_infonavit'][$key];
          $total_fondo1         += $_POST['fondo_ahorro'][$key];
          $descuento_playeras1  += $_POST['descuento_playeras'][$key];
          $descuento_otros1     += $_POST['descuento_otros'][$key];
          $ttotal_pagar1        += $total_pagar;
          $ttotal_nomina1       += $_POST['ttotal_nomina'][$key];
          $total_no_fiscal1     += $_POST['total_no_fiscal'][$key];
        }
      }

      if($pdf->GetY()+10 >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetFont('Helvetica','B', 8);
      $pdf->SetXY(6, $pdf->GetY());
      $datatto = array();
      $datatto[] = '';
      $datatto[] = 'TOTAL';
      $datatto[] = String::formatoNumero($sueldo_semanal_real1, 2, '$', false);
      // $datatto[] = String::formatoNumero($premio_asistencia1, 2, '$', false);
      // $datatto[] = String::formatoNumero($despensa1, 2, '$', false);

      if ($ver_total_otros != 0)
        $datatto[] = String::formatoNumero($otras_percepciones1, 2, '$', false);
      if ($ver_total_domingo != 0)
        $datatto[] = String::formatoNumero($domingo1, 2, '$', false);
      if ($ver_total_prestamos != 0)
        $datatto[] = String::formatoNumero($total_prestamos1, 2, '$', false);

      if ($ver_fondo_arro != 0)
      {
        $datatto[] = String::formatoNumero($total_fondo1, 2, '$', false);
      }

      if ($ver_infonavit != 0)
      {
        $datatto[] = String::formatoNumero($total_infonavit1, 2, '$', false);
      }

      if($ver_des_playera)
        $datatto[] = String::formatoNumero($descuento_playeras1, 2, '$', false);
      if($ver_des_otro)
        $datatto[] = String::formatoNumero($descuento_otros1, 2, '$', false);
      $datatto[] = String::formatoNumero($ttotal_pagar1, 2, '$', false);

      if ($ver_trans != 0)
      {
        $datatto[] = String::formatoNumero($ttotal_nomina1, 2, '$', false);
      }
      $datatto[] = String::formatoNumero($total_no_fiscal1, 2, '$', false);
      $pdf->Row($datatto, false, true, null, 2, 1);
    }

    // **** Se ponen los finiquitos ***********
    if($pdf->GetY()+10 >= $pdf->limiteY){
      $pdf->AddPage();
      $pdf->SetFont('Helvetica','B', 8);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Row($columnas['n'], false, false, null, 2, 1);
    }
    $sueldo_semanal_real1 = $otras_percepciones1 = $domingo1 = $total_prestamos1 = $total_infonavit1 = $descuento_playeras1 = $descuento_otros1 = $ttotal_pagar1 = $ttotal_nomina1 = $total_no_fiscal1 = 0;

    $pdf->SetFont('Helvetica','B', 10);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->Cell(130, 6, 'FINIQUITOS', 0, 0, 'L', 0);

    $pdf->SetXY(6, $pdf->GetY()+6);
    foreach ($finiquitos as $key => $empleado)
    {
      $numero_empleado++;
      $bonos = $this->getBonosOtrosEmpleado($empleado->id, $semana['semana'], $semana['anio'], $empresa['info']->dia_inicia_semana);
      $bonos_suma = 0;
      foreach ($bonos as $keybb => $value)
        $bonos_suma += $value->bono+$value->otro;
      // $empleado = $this->usuarios_model->get_usuario_info($empleado, true)['info'][0];

      $pdf->SetFont('Helvetica','', 8);
      if($pdf->GetY()+8 >= $pdf->limiteY){
        $pdf->AddPage();
        $pdf->SetFont('Helvetica','B', 8);
        $pdf->SetXY(6, $pdf->GetY());
        // $pdf->SetAligns(array('L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R'));
        // $pdf->SetWidths(array(64, 20, 20, 20, 20, 20, 20, 20, 20, 20, 20));
        $pdf->Row($columnas['n'], false, false, null, 2, 1);
      }

      $pdf->SetFont('Helvetica','', 8);
      $total_pagar = $empleado->total_percepcion +
        $bonos_suma -  //bonos + otros
        $empleado->total_deduccion;
      $pdf->SetXY(6, $pdf->GetY());

      $dataarr = array();
      $dataarr[] = $numero_empleado;
      $dataarr[] = $empleado->apellido_paterno.' '.$empleado->apellido_materno.' '.$empleado->nombre;
      $dataarr[] = String::formatoNumero($empleado->total_percepcion, 2, '$', false);
      // $dataarr[] = String::formatoNumero(0, 2, '$', false);
      // $dataarr[] = String::formatoNumero(0, 2, '$', false);

      if ($ver_total_otros != 0)
        $dataarr[] = String::formatoNumero($bonos_suma, 2, '$', false); //bonos + otros
      if ($ver_total_domingo != 0)
        $dataarr[] = String::formatoNumero(0, 2, '$', false);
      if ($ver_total_prestamos != 0)
        $dataarr[] = String::formatoNumero($empleado->total_deduccion, 2, '$', false);

      if ($ver_fondo_arro != 0)
      {
        $dataarr[] = String::formatoNumero('0', 2, '$', false);
      }

      if ($ver_infonavit != 0)
      {
        $dataarr[] = String::formatoNumero(0, 2, '$', false);
      }

      if($ver_des_playera)
        $dataarr[] = String::formatoNumero(0, 2, '$', false);
      if($ver_des_otro)
        $dataarr[] = String::formatoNumero(0, 2, '$', false);
      $dataarr[] = String::formatoNumero($total_pagar, 2, '$', false);

      if ($ver_trans != 0)
      {
        $dataarr[] = String::formatoNumero($empleado->total_neto, 2, '$', false);
      }

      $dataarr[] = String::formatoNumero(($total_pagar-$empleado->total_neto), 2, '$', false);

      $pdf->Row($dataarr, false, true, null, 2, 1);
      $sueldo_semanal_real += $empleado->total_percepcion;
      $otras_percepciones  += $bonos_suma;
      $domingo             += 0;
      $total_prestamos     += 0;
      $total_infonavit     += 0;
      $descuento_playeras  += 0;
      $descuento_otros     += 0;
      $ttotal_pagar        += $total_pagar;
      $ttotal_nomina       += $empleado->total_neto;
      $total_no_fiscal     += ($total_pagar-$empleado->total_neto);
      $ttotal_aseg_no_trs  += $empleado->total_neto;

      $sueldo_semanal_real1 += $empleado->total_percepcion;
      $otras_percepciones1  += $bonos_suma;
      $domingo1             += 0;
      $total_prestamos1     += 0;
      $total_infonavit1     += 0;
      $descuento_playeras1  += 0;
      $descuento_otros1     += 0;
      $ttotal_pagar1        += $total_pagar;
      $ttotal_nomina1       += $empleado->total_neto;
      $total_no_fiscal1     += ($total_pagar-$empleado->total_neto);
    }

    if($pdf->GetY()+10 >= $pdf->limiteY)
      $pdf->AddPage();

    $pdf->SetFont('Helvetica','B', 8);
    $pdf->SetXY(6, $pdf->GetY());
    $datatto = array();
    $datatto[] = '';
    $datatto[] = 'TOTAL';
    $datatto[] = String::formatoNumero($sueldo_semanal_real1, 2, '$', false);
    // $datatto[] = String::formatoNumero(0, 2, '$', false);
    // $datatto[] = String::formatoNumero(0, 2, '$', false);

    if ($ver_total_otros != 0)
      $datatto[] = String::formatoNumero($otras_percepciones1, 2, '$', false);
    if ($ver_total_domingo != 0)
      $datatto[] = String::formatoNumero($domingo1, 2, '$', false);
    if ($ver_total_prestamos != 0)
      $datatto[] = String::formatoNumero($total_prestamos1, 2, '$', false);

    if ($ver_fondo_arro != 0)
    {
      $datatto[] = String::formatoNumero('0', 2, '$', false);
    }

    if ($ver_infonavit != 0)
    {
      $datatto[] = String::formatoNumero($total_infonavit1, 2, '$', false);
    }

    if($ver_des_playera)
      $datatto[] = String::formatoNumero($descuento_playeras1, 2, '$', false);
    if($ver_des_otro)
      $datatto[] = String::formatoNumero($descuento_otros1, 2, '$', false);
    $datatto[] = String::formatoNumero($ttotal_pagar1, 2, '$', false);

    if ($ver_trans != 0)
    {
      $datatto[] = String::formatoNumero($ttotal_nomina1, 2, '$', false);
    }
    $datatto[] = String::formatoNumero($total_no_fiscal1, 2, '$', false);
    $pdf->Row($datatto, false, true, null, 2, 1);


    //******* la nomina de limon para sacar prestamos
    $total_prestamos_limon = 0;
    if ($empresa['info']->rfc == 'GGU090120I91') //GGU090120I91
    {
      $this->load->model('nomina_ranchos_model');
      $filtros = array(
        'semana'    => $semana['semana'],
        'anio'      => $semana['anio'],
        'empresaId' => $empresa['info']->id_empresa,
        'puestoId'  => '',
        'dia_inicia_semana' => $empresa['info']->dia_inicia_semana,
      );
      $empleados_rancho = $this->nomina_ranchos_model->nomina($filtros);
      foreach ($empleados_rancho as $key => $value){
        $total_prestamos_limon += $value->prestamo['total'];
      }
    }
    // Si es diferente a sanjorge agrega empleados ficticios para recuperar los prestamos
    // Se registran como otro departamento y empleados
    if ($empresa['info']->rfc != 'ESJ97052763A' && ($total_prestamos+$descuento_otros+$total_prestamos_limon) > 0)
    {

      $pdf->SetFont('Helvetica','B', 10);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Cell(130, 6, 'Otros', 0, 0, 'L', 0);
      $pdf->SetXY(6, $pdf->GetY()+6);
      $sueldo_semanal_real1 = $otras_percepciones1 = $domingo1 = $total_prestamos1 = $total_infonavit1 = $descuento_playeras1 = $descuento_otros1 = $ttotal_pagar1 = $ttotal_nomina1 = $total_no_fiscal1 = 0;
      $data_otross = array('PRESTAMO FIJO' => $total_prestamos, 'PRESTAMO SEMANAL' => $descuento_otros, 'PRESTAMO LIMON' => $total_prestamos_limon);

      foreach ($data_otross as $keyotss => $dottoss)
      {
        if ($dottoss > 0)
        {
          $pdf->SetFont('Helvetica','', 8);
          if($pdf->GetY()+8 >= $pdf->limiteY){
            $pdf->AddPage();
            $pdf->SetFont('Helvetica','B', 8);
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->Row($columnas['n'], false, false, null, 2, 1);
          }

          $pdf->SetFont('Helvetica','', 8);
          $total_pagar = $dottoss;
          $pdf->SetXY(6, $pdf->GetY());

          $dataarr = array();
          $dataarr[] = '';
          $dataarr[] = $keyotss;
          $dataarr[] = String::formatoNumero('0', 2, '$', false);
          // $datatto[] = String::formatoNumero('0', 2, '$', false);
          // $datatto[] = String::formatoNumero('0', 2, '$', false);
          if ($ver_total_otros != 0)
            $dataarr[] = String::formatoNumero($dottoss, 2, '$', false);
          if ($ver_total_domingo != 0)
            $dataarr[] = String::formatoNumero('0', 2, '$', false);
          if ($ver_total_prestamos != 0)
            $dataarr[] = String::formatoNumero('0', 2, '$', false);
          if ($ver_fondo_arro != 0)
            $dataarr[] = String::formatoNumero('0', 2, '$', false);
          if ($ver_infonavit != 0)
            $dataarr[] = String::formatoNumero('0', 2, '$', false);
          if($ver_des_playera)
            $dataarr[] = String::formatoNumero('0', 2, '$', false);
          if($ver_des_otro)
            $dataarr[] = String::formatoNumero('0', 2, '$', false);
          $dataarr[] = String::formatoNumero($dottoss, 2, '$', false);
          if ($ver_trans != 0)
            $dataarr[] = String::formatoNumero('0', 2, '$', false);
          $dataarr[] = String::formatoNumero($dottoss, 2, '$', false);

          $pdf->Row($dataarr, false, true, null, 2, 1);
          $otras_percepciones  += $dottoss;
          $ttotal_pagar        += $dottoss;
          $total_no_fiscal     += $dottoss;

          $otras_percepciones1  += $dottoss;
          $ttotal_pagar1        += $dottoss;
          $total_no_fiscal1     += $dottoss;
        }
      }

      if($pdf->GetY()+10 >= $pdf->limiteY)
        $pdf->AddPage();
      $pdf->SetFont('Helvetica','B', 8);
      $pdf->SetXY(6, $pdf->GetY());
      $datatto = array();
      $datatto[] = '';
      $datatto[] = 'TOTAL';
      $datatto[] = String::formatoNumero($sueldo_semanal_real1, 2, '$', false);
      // $datatto[] = String::formatoNumero(0, 2, '$', false);
      // $datatto[] = String::formatoNumero(0, 2, '$', false);

      if ($ver_total_otros != 0)
        $datatto[] = String::formatoNumero($otras_percepciones1, 2, '$', false);
      if ($ver_total_domingo != 0)
        $datatto[] = String::formatoNumero($domingo1, 2, '$', false);
      if ($ver_total_prestamos != 0)
        $datatto[] = String::formatoNumero($total_prestamos1, 2, '$', false);
      if ($ver_fondo_arro != 0)
        $datatto[] = String::formatoNumero($ver_fondo_arro, 2, '$', false);
      if ($ver_infonavit != 0)
        $datatto[] = String::formatoNumero($total_infonavit1, 2, '$', false);
      if($ver_des_playera)
        $datatto[] = String::formatoNumero($descuento_playeras1, 2, '$', false);
      if($ver_des_otro)
        $datatto[] = String::formatoNumero($descuento_otros1, 2, '$', false);
      $datatto[] = String::formatoNumero($ttotal_pagar1, 2, '$', false);
      if ($ver_trans != 0)
        $datatto[] = String::formatoNumero($ttotal_nomina1, 2, '$', false);
      $datatto[] = String::formatoNumero($total_no_fiscal1, 2, '$', false);
      $pdf->Row($datatto, false, true, null, 2, 1);
    }

    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetFont('Helvetica','B', 8);
    if($pdf->GetY()+8 >= $pdf->limiteY)
      $pdf->AddPage();
    $datatto = array();
    $datatto[] = '';
    $datatto[] = 'TOTAL';
    $datatto[] = String::formatoNumero($sueldo_semanal_real, 2, '$', false);
    // $datatto[] = String::formatoNumero($premio_asistencia, 2, '$', false);
    // $datatto[] = String::formatoNumero($despensa, 2, '$', false);
    if ($ver_total_otros != 0)
      $datatto[] = String::formatoNumero($otras_percepciones, 2, '$', false);
    if ($ver_total_domingo != 0)
      $datatto[] = String::formatoNumero($domingo, 2, '$', false);
    if ($ver_total_prestamos != 0)
      $datatto[] = String::formatoNumero($total_prestamos, 2, '$', false);
    if ($ver_fondo_arro != 0)
      $datatto[] = String::formatoNumero($ver_fondo_arro, 2, '$', false);
    if($ver_infonavit != 0)
      $datatto[] = String::formatoNumero($total_infonavit, 2, '$', false);
    if($ver_des_playera)
      $datatto[] = String::formatoNumero($descuento_playeras, 2, '$', false);
    if($ver_des_otro)
      $datatto[] = String::formatoNumero($descuento_otros, 2, '$', false);
    $datatto[] = String::formatoNumero($ttotal_pagar, 2, '$', false);
    if ($ver_trans != 0)
      $datatto[] = String::formatoNumero($ttotal_nomina, 2, '$', false);
    $datatto[] = String::formatoNumero($total_no_fiscal, 2, '$', false);
    $pdf->Row($datatto, false, true, null, 2, 1);

    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetFont('Helvetica','B', 8);
      if($pdf->GetY()+8 >= $pdf->limiteY)
        $pdf->AddPage();
    $pdf->SetAligns(array('L', 'L', 'L'));
    $pdf->SetWidths(array(50, 50, 50));

    if ($empresa['info']->rfc === 'ESJ97052763A')
    {
      $pdf->Row(array(
        'NOMINA FISCAL: '.String::formatoNumero($ttotal_aseg_no_trs, 2, '$', false),
        'TRANSFERIDO: '.String::formatoNumero($ttotal_nomina, 2, '$', false),
        'CHEQUE FISCAL: '.String::formatoNumero(($ttotal_aseg_no_trs-$ttotal_nomina), 2, '$', false),
        ), false, true, null, 2, 1);
    }

    //Si es la empresa es gomez gudiño pone la nomina de limon (o ranchos), se obtiene de la bd
    if ($empresa['info']->rfc == 'GGU090120I91') //GGU090120I91
    {
      $pdf->SetFont('Helvetica','B', 10);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Cell(130, 6, 'Corte de limon', 0, 0, 'L', 0);
      $pdf->SetXY(6, $pdf->GetY()+5);
      $pdf->SetFont('Helvetica','B', 8);
        if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();
      $totales_rancho = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
      $pdf->SetAligns(array('L', 'L', 'L', 'L', 'L', 'L', 'L', 'L', 'L', 'L', 'L', 'L', 'L', 'L', 'L'));
      $pdf->SetWidths(array(5, 65, 13, 13, 13, 13, 13, 13, 13, 13, 13, 18, 18, 18, 30));
      $pdf->Row(array('', '', '', '', '', '', '', '', '', '', '', '$'.$empleados_rancho[0]->precio_lam, '$'.$empleados_rancho[0]->precio_lvr, '', ''), false, false, null, 2, 1);
      $pdf->SetX(6);
      $pdf->Row(array('', 'Nombre', 'CC', 'AM', 'S', 'L', 'M', 'M', 'J', 'V', 'D', 'Total AM', 'Total V', 'Prestamo', 'Total'), false, false, null, 2, 1);
      $pdf->SetFont('Helvetica','', 8);
      foreach ($empleados_rancho as $key => $value)
      {
        if($pdf->GetY()+8 >= $pdf->limiteY)
          $pdf->AddPage();
        $numero_empleado++;
        $pdf->SetX(6);
        $pdf->Row(array(
          $numero_empleado,
          $value->nombre,
          String::formatoNumero($value->cajas_cargadas, 2, ''),
          String::formatoNumero($value->total_lam, 2, ''),
          String::formatoNumero($value->sabado, 2, ''),
          String::formatoNumero($value->lunes, 2, ''),
          String::formatoNumero($value->martes, 2, ''),
          String::formatoNumero($value->miercoles, 2, ''),
          String::formatoNumero($value->jueves, 2, ''),
          String::formatoNumero($value->viernes, 2, ''),
          String::formatoNumero($value->domingo, 2, ''),
          String::formatoNumero($value->total_lam, 2, ''),
          String::formatoNumero($value->total_lvrd, 2, ''),
          String::formatoNumero($value->prestamo['total'], 2, '$', false),
          String::formatoNumero($value->total_pagar, 2, '$', false),
        ), false, true, null, 2, 1);
        $totales_rancho[0] += $value->total_lam;
        $totales_rancho[1] += $value->sabado;
        $totales_rancho[2] += $value->lunes;
        $totales_rancho[3] += $value->martes;
        $totales_rancho[4] += $value->miercoles;
        $totales_rancho[5] += $value->jueves;
        $totales_rancho[6] += $value->viernes;
        $totales_rancho[7] += $value->domingo;
        $totales_rancho[8] += $value->total_lam;
        $totales_rancho[9] += $value->total_lvrd;
        $totales_rancho[10] += $value->prestamo['total'];
        $totales_rancho[11] += $value->total_pagar;
        $totales_rancho[12] += $value->cajas_cargadas;
      }
      $pdf->SetFont('Helvetica','B', 8);
      $pdf->SetX(6);
      $pdf->Row(array(
        '',
        'TOTAL',
        String::formatoNumero($totales_rancho[12], 2, ''),
        String::formatoNumero($totales_rancho[0], 2, ''),
        String::formatoNumero($totales_rancho[1], 2, ''),
        String::formatoNumero($totales_rancho[2], 2, ''),
        String::formatoNumero($totales_rancho[3], 2, ''),
        String::formatoNumero($totales_rancho[4], 2, ''),
        String::formatoNumero($totales_rancho[5], 2, ''),
        String::formatoNumero($totales_rancho[6], 2, ''),
        String::formatoNumero($totales_rancho[7], 2, ''),
        String::formatoNumero($totales_rancho[8], 2, ''),
        String::formatoNumero($totales_rancho[9], 2, ''),
        String::formatoNumero($totales_rancho[10], 2, '$', false),
        String::formatoNumero($totales_rancho[11], 2, '$', false),
      ), false, true, null, 2, 1);

      $pdf->SetWidths(array(35, 23));
      $pdf->SetXY(6, $pdf->GetY()+3);
      $pdf->Row(array(
        'TOTAL',
        String::formatoNumero($totales_rancho[11]+$total_no_fiscal, 2, '$', false),
      ), false, true, null, 2, 1);
    }

    $pdf->Output('Nomina.pdf', 'I');
  }

  private function rowXls($data, $style='')
  {
    $html = '';
    $html .= '<tr>';
    foreach ($data as $keycc => $col)
      $html .= '<td '.(is_array($style)? $style[$keycc]: $style).'>'.utf8_decode($col).'</td>';
    $html .= '</tr>';
    return $html;
  }

  public function xlsRptNominaFiscal($semana, $empresaId, $anio)
  {
    $this->load->model('usuarios_model');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_departamentos_model');
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);
    $semana = $this->fechasDeUnaSemana($semana, $anio, $empresa['info']->dia_inicia_semana);
    $finiquitos = $this->db->query("SELECT * FROM usuarios AS u INNER JOIN finiquito AS f ON u.id = f.id_empleado
      WHERE f.fecha_salida BETWEEN '{$semana['fecha_inicio']}' AND '{$semana['fecha_final']}'")->result();

    $html = '<table>';
    $html .= $this->rowXls( array($empresa['info']->nombre_fiscal) );
    $html .= $this->rowXls( array("Lista de Raya de {$semana['fecha_inicio']} al {$semana['fecha_final']}") );
    $html .= $this->rowXls( array("Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}") );
    $html .= $this->rowXls( array() );

    //no mostrar algunas columnas
    $ver_des_otro = $ver_des_playera = false;
    foreach ($_POST['empleado_id'] as $key => $empleado){
      if($_POST['descuento_playeras'][$key]>0) $ver_des_playera = true;
      if($_POST['descuento_otros'][$key]>0) $ver_des_otro = true;
    }

    $ver_total_otros = $ver_total_domingo = $ver_total_prestamos = $ver_fondo_arro = $ver_infonavit = $ver_trans = 0;
    foreach ($_POST['empleado_id'] as $key => $empleado)
    {
      $ver_infonavit       += $_POST['total_infonavit'][$key];
      $ver_trans           += $_POST['ttotal_nomina'][$key];
      $ver_fondo_arro      += $_POST['fondo_ahorro'][$key];
      $ver_total_prestamos += $_POST['total_prestamos'][$key];
      $ver_total_domingo   += $_POST['domingo'][$key];
      $ver_total_otros   += $_POST['bonos'][$key]+$_POST['otros'][$key];
    }

    $columnas = array('n' => array(), 'w' => array(5, 64, 64, 20, 20, 20), 'a' => array('L', 'L', 'L', 'R', 'R', 'R'));
    $columnas['n'][] = 'No';
    $columnas['n'][] = 'PUESTO';
    $columnas['n'][] = 'NOMBRE';
    $columnas['n'][] = 'SUELDO';
    if ($ver_total_domingo != 0)
    {
      $columnas['n'][] = 'OTRAS';
      $columnas['w'][] = 20;
      $columnas['a'][] = 'R';
    }

    if ($ver_total_domingo != 0)
    {
      $columnas['n'][] = 'DOMINGO';
      $columnas['w'][] = 20;
      $columnas['a'][] = 'R';
    }

    if ($ver_total_prestamos != 0)
    {
      $columnas['n'][] = 'PTMO';
      $columnas['w'][] = 20;
      $columnas['a'][] = 'R';
    }

    if ($ver_fondo_arro != 0)
    {
      $columnas['n'][] = 'FA';
      $columnas['w'][] = 20;
      $columnas['a'][] = 'R';
    }

    if ($ver_infonavit != 0)
    {
      $columnas['n'][] = 'INFONAVIT';
      $columnas['w'][] = 20;
      $columnas['a'][] = 'R';
    }

    if($ver_des_playera){
      $columnas['n'][] = 'DESC. PLAY';
      $columnas['w'][] = 20;
      $columnas['a'][] = 'R';
    }
    if($ver_des_otro){
      $columnas['n'][] = 'DESC. OTRO';
      $columnas['w'][] = 20;
      $columnas['a'][] = 'R';
    }
    $columnas['n'][] = 'TOTAL A PAGAR';

    if ($ver_trans !== 0)
    {
      $columnas['n'][] = 'TRANSF';
      $columnas['w'][] = 20;
      $columnas['a'][] = 'R';
    }

    $columnas['n'][] = 'TOTAL COMPLEM';

    $fondo_ahorro = $ttotal_aseg_no_trs = $sueldo_semanal_real = $otras_percepciones = $domingo = $total_prestamos = $total_infonavit = $descuento_playeras = $descuento_otros = $ttotal_pagar = $ttotal_nomina = $total_no_fiscal = 0;

    // $departamentos = $this->usuarios_model->departamentos();
    $_GET['did_empresa'] = $empresaId;
    $departamentos = $this->usuarios_departamentos_model->getPuestos(false)['puestos'];

    $numero_empleado = 0;
    $html .= $this->rowXls($columnas['n']);
    $html .= $this->rowXls(array(''));
    foreach ($departamentos as $keyd => $departamento)
    {
      $fondo_ahorro1 = $sueldo_semanal_real1 = $otras_percepciones1 = $domingo1 = $total_prestamos1 = $total_infonavit1 = $descuento_playeras1 = $descuento_otros1 = $ttotal_pagar1 = $ttotal_nomina1 = $total_no_fiscal1 = 0;

      $html .= '<tr><td style="font-size:14px;">'.$departamento->nombre.'</td></tr>';

      // $pdf->SetXY(6, $pdf->GetY()+6);
      foreach ($_POST['empleado_id'] as $key => $empleado)
      {
        if($departamento->id_departamento == $_POST['departamento_id'][$key])
        {
          $numero_empleado++;
          $empleado = $this->usuarios_model->get_usuario_info($empleado, true)['info'][0];
          $total_pagar = $_POST['sueldo_semanal_real'][$key] +
            ($_POST['bonos'][$key]+$_POST['otros'][$key]) +
            $_POST['domingo'][$key] -
            $_POST['total_prestamos'][$key] -
            $_POST['total_infonavit'][$key] -
            $_POST['descuento_playeras'][$key] -
            $_POST['descuento_otros'][$key] -
            $_POST['fondo_ahorro'][$key];

          $dataarr = array();
          $dataarr[] = $numero_empleado;
          $dataarr[] = $empleado->puesto;
          $dataarr[] = $empleado->apellido_paterno.' '.$empleado->apellido_materno.' '.$empleado->nombre;
          $dataarr[] = String::formatoNumero($_POST['sueldo_semanal_real'][$key], 2, '', false);

          if ($ver_total_otros != 0)
          {
            $dataarr[] = String::formatoNumero(($_POST['bonos'][$key]+$_POST['otros'][$key]), 2, '', false);
          }

          if ($ver_total_domingo != 0)
          {
            $dataarr[] = String::formatoNumero($_POST['domingo'][$key], 2, '', false);
          }

          if ($ver_total_prestamos != 0)
          {
            $dataarr[] = String::formatoNumero($_POST['total_prestamos'][$key], 2, '', false);
          }

          if ($ver_fondo_arro != 0)
          {
            $dataarr[] = String::formatoNumero($_POST['fondo_ahorro'][$key], 2, '', false);
          }

          if ($ver_infonavit != 0)
          {
            $dataarr[] = String::formatoNumero($_POST['total_infonavit'][$key], 2, '', false);
          }

          if($ver_des_playera)
            $dataarr[] = String::formatoNumero($_POST['descuento_playeras'][$key], 2, '', false);
          if($ver_des_otro)
            $dataarr[] = String::formatoNumero($_POST['descuento_otros'][$key], 2, '', false);
          $dataarr[] = String::formatoNumero($total_pagar, 2, '', false);

          if ($ver_trans != 0)
          {
            $dataarr[] = String::formatoNumero($_POST['ttotal_nomina'][$key], 2, '', false);
          }

          $dataarr[] = String::formatoNumero($_POST['total_no_fiscal'][$key], 2, '', false);

          $html .= $this->rowXls($dataarr);
          $sueldo_semanal_real += $_POST['sueldo_semanal_real'][$key];
          $otras_percepciones  += ($_POST['bonos'][$key]+$_POST['otros'][$key]);
          $domingo             += $_POST['domingo'][$key];
          $total_prestamos     += $_POST['total_prestamos'][$key];
          $total_infonavit     += $_POST['total_infonavit'][$key];
          $fondo_ahorro        += $_POST['fondo_ahorro'][$key];
          $descuento_playeras  += $_POST['descuento_playeras'][$key];
          $descuento_otros     += $_POST['descuento_otros'][$key];
          $ttotal_pagar        += $total_pagar;
          $ttotal_nomina       += $_POST['ttotal_nomina'][$key];
          $total_no_fiscal     += $_POST['total_no_fiscal'][$key];
          $ttotal_aseg_no_trs  += $_POST['total_percepciones'][$key]-$_POST['total_deducciones'][$key];

          $sueldo_semanal_real1 += $_POST['sueldo_semanal_real'][$key];
          $otras_percepciones1  += ($_POST['bonos'][$key]+$_POST['otros'][$key]);
          $domingo1             += $_POST['domingo'][$key];
          $total_prestamos1     += $_POST['total_prestamos'][$key];
          $total_infonavit1     += $_POST['total_infonavit'][$key];
          $fondo_ahorro1        += $_POST['fondo_ahorro'][$key];
          $descuento_playeras1  += $_POST['descuento_playeras'][$key];
          $descuento_otros1     += $_POST['descuento_otros'][$key];
          $ttotal_pagar1        += $total_pagar;
          $ttotal_nomina1       += $_POST['ttotal_nomina'][$key];
          $total_no_fiscal1     += $_POST['total_no_fiscal'][$key];
        }
      }

      $datatto = array();
      $datatto[] = '';
      $datatto[] = '';
      $datatto[] = 'TOTAL';
      $datatto[] = String::formatoNumero($sueldo_semanal_real1, 2, '', false);

      if ($ver_total_otros != 0)
      {
        $datatto[] = String::formatoNumero($otras_percepciones1, 2, '', false);
      }

      if ($ver_total_domingo != 0)
      {
        $datatto[] = String::formatoNumero($domingo1, 2, '', false);
      }

      if ($ver_total_prestamos != 0)
      {
        $datatto[] = String::formatoNumero($total_prestamos1, 2, '', false);
      }

      if ($ver_fondo_arro != 0)
      {
        $datatto[] = String::formatoNumero($fondo_ahorro1, 2, '', false);
      }

      if ($ver_infonavit != 0)
      {
        $datatto[] = String::formatoNumero($total_infonavit1, 2, '', false);
      }

      if($ver_des_playera)
        $datatto[] = String::formatoNumero($descuento_playeras1, 2, '', false);
      if($ver_des_otro)
        $datatto[] = String::formatoNumero($descuento_otros1, 2, '', false);
      $datatto[] = String::formatoNumero($ttotal_pagar1, 2, '', false);

      if ($ver_trans != 0)
      {
        $datatto[] = String::formatoNumero($ttotal_nomina1, 2, '', false);
      }
      $datatto[] = String::formatoNumero($total_no_fiscal1, 2, '', false);
      // $pdf->Row($datatto, false, true, null, 2, 1);
      $html .= $this->rowXls($datatto);
      $html .= $this->rowXls(array(''));
    }

    // **** Se ponen los finiquitos ***********
    $html .= '<tr><td style="font-size:14px;">FINIQUITOS</td></tr>';
    $sueldo_semanal_real1 = $otras_percepciones1 = $domingo1 = $total_prestamos1 = $total_infonavit1 = $descuento_playeras1 = $descuento_otros1 = $ttotal_pagar1 = $ttotal_nomina1 = $total_no_fiscal1 = 0;
    foreach ($finiquitos as $key => $empleado)
    {
      $numero_empleado++;
      $bonos = $this->getBonosOtrosEmpleado($empleado->id, $semana['semana'], $semana['anio'], $empresa['info']->dia_inicia_semana);
      $bonos_suma = 0;
      foreach ($bonos as $keybb => $value)
        $bonos_suma += $value->bono+$value->otro;
      // $empleado = $this->usuarios_model->get_usuario_info($empleado, true)['info'][0];

      $total_pagar = $empleado->total_percepcion +
        $bonos_suma -  //bonos + otros
        $empleado->total_deduccion;

      $dataarr = array();
      $dataarr[] = $numero_empleado;
      $dataarr[] = $empleado->apellido_paterno.' '.$empleado->apellido_materno.' '.$empleado->nombre;
      $dataarr[] = String::formatoNumero($empleado->total_percepcion, 2, '', false);

      if ($ver_total_otros != 0)
      {
        $dataarr[] = String::formatoNumero($bonos_suma, 2, '', false); //bonos + otros
      }

      if ($ver_total_domingo != 0)
      {
        $dataarr[] = String::formatoNumero(0, 2, '', false);
      }

      if ($ver_total_prestamos != 0)
      {
        $dataarr[] = String::formatoNumero(0, 2, '', false);
      }

      if ($ver_fondo_arro != 0)
      {
        $dataarr[] = String::formatoNumero(0, 2, '', false);
      }

      if ($ver_infonavit != 0)
      {
        $dataarr[] = String::formatoNumero(0, 2, '', false);
      }

      if($ver_des_playera)
        $dataarr[] = String::formatoNumero(0, 2, '', false);
      if($ver_des_otro)
        $dataarr[] = String::formatoNumero(0, 2, '', false);
      $dataarr[] = String::formatoNumero($total_pagar, 2, '', false);

      if ($ver_trans != 0)
      {
        $dataarr[] = String::formatoNumero($empleado->total_neto, 2, '', false);
      }

      $dataarr[] = String::formatoNumero(($total_pagar-$empleado->total_neto), 2, '', false);

      $html .= $this->rowXls($dataarr);
      $sueldo_semanal_real += $empleado->total_percepcion;
      $otras_percepciones  += $bonos_suma;
      $domingo             += 0;
      $total_prestamos     += 0;
      $total_infonavit     += 0;
      $descuento_playeras  += 0;
      $descuento_otros     += 0;
      $ttotal_pagar        += $total_pagar;
      $ttotal_nomina       += $empleado->total_neto;
      $total_no_fiscal     += ($total_pagar-$empleado->total_neto);
      $ttotal_aseg_no_trs  += $empleado->total_neto;

      $sueldo_semanal_real1 += $empleado->total_percepcion;
      $otras_percepciones1  += $bonos_suma;
      $domingo1             += 0;
      $total_prestamos1     += 0;
      $total_infonavit1     += 0;
      $descuento_playeras1  += 0;
      $descuento_otros1     += 0;
      $ttotal_pagar1        += $total_pagar;
      $ttotal_nomina1       += $empleado->total_neto;
      $total_no_fiscal1     += ($total_pagar-$empleado->total_neto);
    }

    $datatto = array();
    $datatto[] = '';
    $datatto[] = '';
    $datatto[] = 'TOTAL';
    $datatto[] = String::formatoNumero($sueldo_semanal_real1, 2, '', false);

    if ($ver_total_otros != 0)
    {
      $datatto[] = String::formatoNumero($otras_percepciones1, 2, '', false);
    }

    if ($ver_total_domingo != 0)
    {
      $datatto[] = String::formatoNumero($domingo1, 2, '', false);
    }

    if ($ver_total_prestamos != 0)
    {
      $datatto[] = String::formatoNumero($total_prestamos1, 2, '', false);
    }

    if ($ver_fondo_arro != 0)
    {
      $datatto[] = String::formatoNumero('0', 2, '', false);
    }

    if ($ver_infonavit != 0)
    {
      $datatto[] = String::formatoNumero($total_infonavit1, 2, '', false);
    }

    if($ver_des_playera)
      $datatto[] = String::formatoNumero($descuento_playeras1, 2, '', false);
    if($ver_des_otro)
      $datatto[] = String::formatoNumero($descuento_otros1, 2, '', false);
    $datatto[] = String::formatoNumero($ttotal_pagar1, 2, '', false);

    if ($ver_trans != 0)
    {
      $datatto[] = String::formatoNumero($ttotal_nomina1, 2, '', false);
    }
    $datatto[] = String::formatoNumero($total_no_fiscal1, 2, '', false);
    $html .= $this->rowXls($datatto);
    $html .= $this->rowXls(array(''));


    //la nomina de limon para sacar prestamos
    $total_prestamos_limon = 0;
    if ($empresa['info']->rfc == 'GGU090120I91') //GGU090120I91
    {
      $this->load->model('nomina_ranchos_model');
      $filtros = array(
        'semana'    => $semana['semana'],
        'anio'      => $semana['anio'],
        'empresaId' => $empresa['info']->id_empresa,
        'puestoId'  => '',
        'dia_inicia_semana' => $empresa['info']->dia_inicia_semana,
      );
      $empleados_rancho = $this->nomina_ranchos_model->nomina($filtros);
      foreach ($empleados_rancho as $key => $value) {
        $total_prestamos_limon += is_numeric($value->prestamo)? $value->prestamo: $value->prestamo['total'];
      }
    }
    // Si es diferente a sanjorge agrega empleados ficticios para recuperar los prestamos
    // Se registran como otro departamento y empleados
    if ($empresa['info']->rfc != 'ESJ97052763A' && ($total_prestamos+$descuento_otros+$total_prestamos_limon) > 0)
    {
      $html .= $this->rowXls(array('Otros'), 'style="font-weight:bold;font-size:14px;"');
      $sueldo_semanal_real1 = $otras_percepciones1 = $domingo1 = $total_prestamos1 = $total_infonavit1 = $descuento_playeras1 = $descuento_otros1 = $ttotal_pagar1 = $ttotal_nomina1 = $total_no_fiscal1 = 0;
      $data_otross = array('PRESTAMO FIJO' => $total_prestamos, 'PRESTAMO SEMANAL' => $descuento_otros, 'PRESTAMO LIMON' => $total_prestamos_limon);
      foreach ($data_otross as $keyotss => $dottoss)
      {
        if ($dottoss > 0)
        {
          $total_pagar = $dottoss;

          $dataarr = array();
          $dataarr[] = '';
          $dataarr[] = $keyotss;
          $dataarr[] = String::formatoNumero('0', 2, '', false);
          if ($ver_total_otros != 0)
            $dataarr[] = String::formatoNumero($dottoss, 2, '', false);
          if ($ver_total_domingo != 0)
            $dataarr[] = String::formatoNumero('0', 2, '', false);
          if ($ver_total_prestamos != 0)
            $dataarr[] = String::formatoNumero('0', 2, '', false);
          if ($ver_fondo_arro != 0)
            $dataarr[] = String::formatoNumero('0', 2, '', false);
          if ($ver_infonavit != 0)
            $dataarr[] = String::formatoNumero('0', 2, '', false);
          if($ver_des_playera)
            $dataarr[] = String::formatoNumero('0', 2, '', false);
          if($ver_des_otro)
            $dataarr[] = String::formatoNumero('0', 2, '', false);
          $dataarr[] = String::formatoNumero($dottoss, 2, '', false);
          if ($ver_trans != 0)
            $dataarr[] = String::formatoNumero('0', 2, '', false);
          $dataarr[] = String::formatoNumero($dottoss, 2, '', false);

          // $pdf->Row($dataarr, false, true, null, 2, 1);
          $html .= $this->rowXls($dataarr);
          $otras_percepciones  += $dottoss;
          $ttotal_pagar        += $dottoss;
          $total_no_fiscal     += $dottoss;

          $otras_percepciones1  += $dottoss;
          $ttotal_pagar1        += $dottoss;
          $total_no_fiscal1     += $dottoss;
        }
      }

      $datatto = array();
      $datatto[] = '';
      $datatto[] = '';
      $datatto[] = 'TOTAL';
      $datatto[] = String::formatoNumero($sueldo_semanal_real1, 2, '', false);

      if ($ver_total_otros != 0)
        $datatto[] = String::formatoNumero($otras_percepciones1, 2, '', false);
      if ($ver_total_domingo != 0)
        $datatto[] = String::formatoNumero($domingo1, 2, '', false);
      if ($ver_total_prestamos != 0)
        $datatto[] = String::formatoNumero($total_prestamos1, 2, '', false);
      if ($ver_fondo_arro != 0)
        $datatto[] = String::formatoNumero('0', 2, '', false);
      if ($ver_infonavit != 0)
        $datatto[] = String::formatoNumero($total_infonavit1, 2, '', false);
      if($ver_des_playera)
        $datatto[] = String::formatoNumero($descuento_playeras1, 2, '', false);
      if($ver_des_otro)
        $datatto[] = String::formatoNumero($descuento_otros1, 2, '', false);
      $datatto[] = String::formatoNumero($ttotal_pagar1, 2, '', false);
      if ($ver_trans != 0)
        $datatto[] = String::formatoNumero($ttotal_nomina1, 2, '', false);
      $datatto[] = String::formatoNumero($total_no_fiscal1, 2, '', false);
      // $pdf->Row($datatto, false, true, null, 2, 1);
      $html .= $this->rowXls($datatto, 'style="font-weight:bold;font-size:14px;"');
      $html .= $this->rowXls(array(''));
    }

    $datatto = array();
    $datatto[] = '';
    $datatto[] = '';
    $datatto[] = 'TOTAL';
    $datatto[] = String::formatoNumero($sueldo_semanal_real, 2, '', false);
    if($ver_total_otros != 0)
      $datatto[] = String::formatoNumero($otras_percepciones, 2, '', false);
    if($ver_total_domingo != 0)
      $datatto[] = String::formatoNumero($domingo, 2, '', false);
    if($ver_total_prestamos != 0)
      $datatto[] = String::formatoNumero($total_prestamos, 2, '', false);
    if($ver_fondo_arro != 0)
      $datatto[] = String::formatoNumero($fondo_ahorro, 2, '', false);
    if($ver_infonavit != 0)
      $datatto[] = String::formatoNumero($total_infonavit, 2, '', false);
    if($ver_des_playera)
      $datatto[] = String::formatoNumero($descuento_playeras, 2, '', false);
    if($ver_des_otro)
      $datatto[] = String::formatoNumero($descuento_otros, 2, '', false);
    $datatto[] = String::formatoNumero($ttotal_pagar, 2, '', false);
    if ($ver_trans != 0)
      $datatto[] = String::formatoNumero($ttotal_nomina, 2, '', false);
    $datatto[] = String::formatoNumero($total_no_fiscal, 2, '', false);
    // $pdf->Row($datatto, false, true, null, 2, 1);
    $html .= $this->rowXls($datatto, 'style="font-weight:bold;font-size:14px;"');
    $html .= $this->rowXls(array(''));


    if ($empresa['info']->rfc === 'ESJ97052763A')
    {
      $html .= $this->rowXls(array(
        'NOMINA FISCAL: '.String::formatoNumero($ttotal_aseg_no_trs, 2, '', false),
        'TRANSFERIDO: '.String::formatoNumero($ttotal_nomina, 2, '', false),
        'CHEQUE FISCAL: '.String::formatoNumero(($ttotal_aseg_no_trs-$ttotal_nomina), 2, '', false),
        ), 'style="font-weight:bold;font-size:14px;"');
      $html .= $this->rowXls(array(''));
    }

    //Si es la empresa es gomez gudiño pone la nomina de limon (o ranchos), se obtiene de la bd
    if ($empresa['info']->rfc == 'GGU090120I91') //GGU090120I91
    {
      $html .= $this->rowXls(array('Corte de limon'), 'style="font-weight:bold;font-size:14px;"');
      $totales_rancho = array(0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0);
      $html .= $this->rowXls( array('', '', '', '', '', '', '', '', '', '', '', ''.$empleados_rancho[0]->precio_lam, ''.$empleados_rancho[0]->precio_lvr, '', '') );
      $html .= $this->rowXls( array('', 'Nombre', 'CC', 'AM', 'S', 'L', 'M', 'M', 'J', 'V', 'D', 'Total AM', 'Total V', 'Prestamo', 'Total') );

      foreach ($empleados_rancho as $key => $value)
      {
        $numero_empleado++;
        $html .= $this->rowXls( array(
          $numero_empleado,
          $value->nombre,
          String::formatoNumero($value->cajas_cargadas, 2, ''),
          String::formatoNumero($value->total_lam, 2, ''),
          String::formatoNumero($value->sabado, 2, ''),
          String::formatoNumero($value->lunes, 2, ''),
          String::formatoNumero($value->martes, 2, ''),
          String::formatoNumero($value->miercoles, 2, ''),
          String::formatoNumero($value->jueves, 2, ''),
          String::formatoNumero($value->viernes, 2, ''),
          String::formatoNumero($value->domingo, 2, ''),
          String::formatoNumero($value->total_lam, 2, ''),
          String::formatoNumero($value->total_lvrd, 2, ''),
          String::formatoNumero($value->prestamo, 2, '', false),
          String::formatoNumero($value->total_pagar, 2, '', false),
        ) );

        $totales_rancho[0] += $value->total_lam;
        $totales_rancho[1] += $value->sabado;
        $totales_rancho[2] += $value->lunes;
        $totales_rancho[3] += $value->martes;
        $totales_rancho[4] += $value->miercoles;
        $totales_rancho[5] += $value->jueves;
        $totales_rancho[6] += $value->viernes;
        $totales_rancho[7] += $value->domingo;
        $totales_rancho[8] += $value->total_lam;
        $totales_rancho[9] += $value->total_lvrd;
        $totales_rancho[10] += is_numeric($value->prestamo)? $value->prestamo: $value->prestamo['total'];
        $totales_rancho[11] += $value->total_pagar;
        $totales_rancho[12] += $value->cajas_cargadas;
      }

      $html .= $this->rowXls( array(
        '',
        'TOTAL',
        String::formatoNumero($totales_rancho[12], 2, ''),
        String::formatoNumero($totales_rancho[0], 2, ''),
        String::formatoNumero($totales_rancho[1], 2, ''),
        String::formatoNumero($totales_rancho[2], 2, ''),
        String::formatoNumero($totales_rancho[3], 2, ''),
        String::formatoNumero($totales_rancho[4], 2, ''),
        String::formatoNumero($totales_rancho[5], 2, ''),
        String::formatoNumero($totales_rancho[6], 2, ''),
        String::formatoNumero($totales_rancho[7], 2, ''),
        String::formatoNumero($totales_rancho[8], 2, ''),
        String::formatoNumero($totales_rancho[9], 2, ''),
        String::formatoNumero($totales_rancho[10], 2, '', false),
        String::formatoNumero($totales_rancho[11], 2, '', false),
      ), 'style="font-weight:bold;font-size:14px;"' );

      $html .= $this->rowXls( array(
        'TOTAL',
        String::formatoNumero($totales_rancho[11]+$total_no_fiscal, 2, '', false),
      ), 'style="font-weight:bold;font-size:14px;"' );
    }
    $html .= '</table>';

    header("Content-type: application/vnd.ms-excel; name='excel'");
    header("Content-Disposition: filename=nomina.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo $html;
  }

  public function pdfRecibNomin($semana, $anio, $empresaId)
  {
    $this->load->library('mypdf');
    $this->load->model('empresas_model');

    if ($empresaId !== '')
      $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $empresaId)->get()->row()->dia_inicia_semana;
    else
      $dia = '4';
    $semana = $this->fechasDeUnaSemana($semana, $anio, $dia);
    $_GET['cid_empresa'] = $empresaId; //para las cuentas del contpaq
    $configuraciones = $this->configuraciones();
    $filtros = array('semana' => $semana['semana'], 'empresaId' => $empresaId, 'dia_inicia_semana' => $dia,
      'ordenar' => " ORDER BY u.id ASC", 'anio' => $anio);
    $empleados = $this->nomina($configuraciones, $filtros);

    $pdf = new MYpdf('P', 'mm', 'Letter');

    foreach ($empleados as $key => $value)
    {
      if ($value->esta_asegurado == 't')
        $this->pdfReciboNominaFiscal($value->id, $semana['semana'], $anio, $empresaId, $pdf);
    }

    $finiquitos = $this->db->query("SELECT * FROM finiquito AS f
      WHERE f.fecha_salida BETWEEN '{$semana['fecha_inicio']}' AND '{$semana['fecha_final']}'")->result();
    foreach ($finiquitos as $key => $value)
    {
      $this->pdfReciboNominaFiscalFiniquito($value->id_empleado, $semana['semana'], $anio, $empresaId, $pdf);
    }

    $pdf->Output('Nomina.pdf', 'I');
  }

  public function pdfReciboNominaFiscal($empleadoId, $semana, $anio, $empresaId, $pdf=null)
  {
    $pdfuno = $pdf==null? true: false;
    $this->load->model('empresas_model');

    if ($empresaId !== '')
      $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $empresaId)->get()->row()->dia_inicia_semana;
    else
      $dia = '4';
    $semana = $this->fechasDeUnaSemana($semana, $anio, $dia);
    $_GET['cid_empresa'] = $empresaId; //para las cuentas del contpaq
    $configuraciones = $this->configuraciones();
    $filtros = array('semana' => $semana['semana'], 'anio' => $anio, 'empresaId' => $empresaId, 'dia_inicia_semana' => $dia);
    $empleados = $this->nomina($configuraciones, $filtros, $empleadoId);
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);

    // echo "<pre>";
    //   var_dump($empleados);
    // echo "</pre>";exit;

    include_once(APPPATH.'libraries/phpqrcode/qrlib.php');

    $nomina = $this->db->query("SELECT uuid, xml FROM nomina_fiscal WHERE id_empleado = {$empleadoId} AND id_empresa = {$empresaId} AND anio = {$semana['anio']} AND semana = {$semana['semana']}")->row();

    $xml = simplexml_load_string(str_replace(array('cfdi:', 'tfd:', 'nomina:'), '', $nomina->xml));

    // echo "<pre>";
    //   var_dump($nomina, $xml);
    // echo "</pre>";exit;

    if ($pdf == null)
    {
      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
    }
    $pdf->show_head = true;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->logo = $empresa['info']->logo;
    $pdf->titulo2 = "Recibo de Raya de {$semana['fecha_inicio']} al {$semana['fecha_final']}";
    $pdf->titulo3 = "Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $total_gral = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0,
      'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'fondo_ahorro' => 0, 'isr' => 0,
      'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0, 'pasistencia' => 0, 'despensa' => 0);

    $total_dep = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0,
      'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'fondo_ahorro' => 0, 'isr' => 0,
      'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0, 'pasistencia' => 0, 'despensa' => 0);

    $dep_tiene_empleados = true;
    $y = $pdf->GetY();
    foreach ($empleados as $key => $empleado)
    {
      if($dep_tiene_empleados)
      {
        $pdf->SetFont('Helvetica','', 10);
        $pdf->SetXY(6, $pdf->GetY() + 4);
        $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
        $pdf->Row(array('', 'Percepción', 'Importe', '', 'Deducción', 'Importe'), false, false, null, 2, 1);

        $pdf->SetFont('Helvetica','', 10);
        $pdf->SetXY(6, $pdf->GetY() - 2);
        $pdf->Cell(200, 2, "________________________________________________________________________________________________________", 0, 0, 'L', 0);
        $dep_tiene_empleados = false;
      }

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY() + 4);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(15, 100));
      $pdf->Row(array($empleado->no_empleado, $empleado->nombre), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetFont('Helvetica','', 9);
      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(50, 70, 50));
      $pdf->Row(array($empleado->puesto, "RFC: {$empleado->rfc}", "Afiliciación IMSS: {$empleado->no_seguro}"), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(50, 35, 35, 35, 30));
      $pdf->Row(array("Fecha Ingr: {$empleado->fecha_entrada}", "Sal. diario: {$empleado->salario_diario}", "S.D.I: {$empleado->nomina->salario_diario_integrado}", "S.B.C: {$empleado->nomina->salario_diario_integrado}", 'Cotiza fijo'), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $horasExtras = 0;
      if ($empleado->horas_extras_dinero > 0)
      {
        $pagoXHora = $empleado->salario_diario / 8;
        $horasExtras = $empleado->horas_extras_dinero / $pagoXHora;
      }

      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(35, 35, 25, 35, 70));
      $pdf->Row(array("Dias Pagados: {$empleado->dias_trabajados}", "Tot Hrs trab: " . $empleado->dias_trabajados * 8, 'Hrs dia: 8.00', "Hrs extras: " . number_format($horasExtras, 2), "CURP: {$empleado->curp}"), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $y2 = $pdf->GetY();

      // Percepciones
      $percepciones = $empleado->nomina->percepciones;

      // Sueldo
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Sueldo', String::formatoNumero($percepciones['sueldo']['total'], 2, '$', false)), false, 0, null, 1, 1);
      $total_dep['sueldo'] += $percepciones['sueldo']['total'];
      $total_gral['sueldo'] += $percepciones['sueldo']['total'];
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }

      // P ASISTENCIA
      if ($empleado->pasistencia > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'P Asistencia', String::formatoNumero($empleado->pasistencia, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['pasistencia'] += $empleado->pasistencia;
        $total_gral['pasistencia'] += $empleado->pasistencia;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // DESPENSA
      if ($empleado->despensa > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Despensa', String::formatoNumero($empleado->despensa, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['despensa'] += $empleado->despensa;
        $total_gral['despensa'] += $empleado->despensa;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // Horas Extras
      if ($empleado->horas_extras_dinero > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Horas Extras', String::formatoNumero($empleado->horas_extras_dinero, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['horas_extras'] += $empleado->horas_extras_dinero;
        $total_gral['horas_extras'] += $empleado->horas_extras_dinero;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // Vacaciones y prima vacacional
      if ($empleado->nomina_fiscal_vacaciones > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Vacaciones', String::formatoNumero($empleado->nomina_fiscal_vacaciones, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['vacaciones'] += $empleado->nomina_fiscal_vacaciones;
        $total_gral['vacaciones'] += $empleado->nomina_fiscal_vacaciones;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Prima vacacional', String::formatoNumero($empleado->nomina->prima_vacacional, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['prima_vacacional'] += $empleado->nomina->prima_vacacional;
        $total_gral['prima_vacacional'] += $empleado->nomina->prima_vacacional;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // Subsidio
      if ($empleado->nomina_fiscal_subsidio > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Subsidio', String::formatoNumero($empleado->nomina_fiscal_subsidio, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['subsidio'] += $empleado->nomina_fiscal_subsidio;
        $total_gral['subsidio'] += $empleado->nomina_fiscal_subsidio;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // // PTU
      // if ($empleado->nomina_fiscal_ptu > 0)
      // {
      //   $pdf->SetXY(6, $pdf->GetY());
      //   $pdf->SetAligns(array('L', 'L', 'R'));
      //   $pdf->SetWidths(array(15, 62, 25));
      //   $pdf->Row(array('', 'PTU', String::formatoNumero($empleado->nomina_fiscal_ptu, 2, '$', false)), false, 0, null, 1, 1);
      //   $total_dep['ptu'] += $empleado->nomina_fiscal_ptu;
      //   $total_gral['ptu'] += $empleado->nomina_fiscal_ptu;
      //   if($pdf->GetY() >= $pdf->limiteY)
      //   {
      //     $pdf->AddPage();
      //     $y2 = $pdf->GetY();
      //   }
      // }

      // Aguinaldo
      if ($empleado->nomina_fiscal_aguinaldo > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Aguinaldo', String::formatoNumero($empleado->nomina_fiscal_aguinaldo, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['aguinaldo'] += $empleado->nomina_fiscal_aguinaldo;
        $total_gral['aguinaldo'] += $empleado->nomina_fiscal_aguinaldo;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      $y = $pdf->GetY();

      // Deducciones
      $deducciones = $empleado->nomina->deducciones;
      $pdf->SetFont('Helvetica','', 9);

      $pdf->SetY($y2);
      if ($empleado->infonavit > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Infonavit', String::formatoNumero($deducciones['infonavit']['total'], 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['infonavit'] += $deducciones['infonavit']['total'];
        $total_gral['infonavit'] += $deducciones['infonavit']['total'];
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'I.M.M.S.', String::formatoNumero($deducciones['imss']['total'] + $deducciones['rcv']['total'], 2, '$', false)), false, 0, null, 1, 1);
      $total_dep['imms'] += $deducciones['imss']['total'] + $deducciones['rcv']['total'];
      $total_gral['imms'] += $deducciones['imss']['total'] + $deducciones['rcv']['total'];
      if($pdf->GetY() >= $pdf->limiteY)
      {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }

      if ($empleado->nomina_fiscal_prestamos > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Prestamos', String::formatoNumero($empleado->nomina_fiscal_prestamos, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['prestamos'] += $empleado->nomina_fiscal_prestamos;
        $total_gral['prestamos'] += $empleado->nomina_fiscal_prestamos;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      if ($empleado->fondo_ahorro > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Fondo Ahorro', String::formatoNumero($empleado->fondo_ahorro, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['fondo_ahorro'] += $empleado->fondo_ahorro;
        $total_gral['fondo_ahorro'] += $empleado->fondo_ahorro;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      // if ($empleado->descuento_playeras > 0)
      // {
      //   $pdf->SetXY(108, $pdf->GetY());
      //   $pdf->SetAligns(array('L', 'L', 'R'));
      //   $pdf->SetWidths(array(15, 62, 25));
      //   $pdf->Row(array('', 'Desc. Playeras', String::formatoNumero($empleado->descuento_playeras, 2, '$', false)), false, 0, null, 1, 1);
      //   if($pdf->GetY() >= $pdf->limiteY)
      //   {
      //     $pdf->AddPage();
      //     $y = $pdf->GetY();
      //   }
      // }

      if ($empleado->nomina_fiscal_isr > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'ISR', String::formatoNumero($empleado->nomina_fiscal_isr, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['isr'] += $empleado->nomina_fiscal_isr;
        $total_gral['isr'] += $empleado->nomina_fiscal_isr;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      if ($y < $pdf->GetY())
      {
        $y = $pdf->GetY();
      }

      // Total percepciones y deducciones
      $pdf->SetXY(6, $y + 2);
      $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
      $total_dep['total_percepcion'] += $empleado->nomina_fiscal_total_percepciones;
      $total_gral['total_percepcion'] += $empleado->nomina_fiscal_total_percepciones;
      $total_dep['total_deduccion'] += $empleado->nomina_fiscal_total_deducciones;
      $total_gral['total_deduccion'] += $empleado->nomina_fiscal_total_deducciones;
      $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($empleado->nomina_fiscal_total_percepciones, 2, '$', false), '', 'Total Deducciones', String::formatoNumero($empleado->nomina_fiscal_total_deducciones, 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $total_dep['total_neto'] += $empleado->nomina_fiscal_total_neto;
      $total_gral['total_neto'] += $empleado->nomina_fiscal_total_neto;
      $pdf->Row(array('', 'Total Neto', String::formatoNumero($empleado->nomina_fiscal_total_neto, 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica', '', 9);
      $pdf->SetXY(120, $pdf->GetY()+3);
      $pdf->Cell(200, 2, "--------------------------------------------------------------------------------------", 0, 0, 'L', 0);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
    }

    if($xml === false)
      true;
    else
    {
      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY()+4);
      $pdf->Cell(78, 4, 'RFC EMISOR: '.$xml->Emisor[0]['rfc'], 0, 0, 'L', 0);

      $pdf->SetXY(86, $pdf->GetY());
      $pdf->Cell(78, 4, 'Forma de Pago: '.$xml[0]['formaDePago'], 0, 0, 'L', 0);

      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(158, $pdf->GetY());
      $pdf->Cell(78, 4, 'Condicion de Pago: Contado', 0, 0, 'L', 0);

      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(86, $pdf->GetY()+5);
      $pdf->Cell(78, 4, "Metodo de Pago: {$xml[0]['metodoDePago']}", 0, 0, 'L', 0);

      $cuenta_banco = substr($empleado->cuenta_banco, -4);
      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(158, $pdf->GetY());
      $pdf->Cell(76, 4, "Cuenta de Pago: {$cuenta_banco}", 0, 0, 'L', 0);
      ////////////////////
      // Timbrado Datos //
      ////////////////////
      if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
          $pdf->AddPage();

      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(10, $pdf->GetY() + 5);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array('Sello Digital del CFDI:'), false, 0);

      $pdf->SetFont('helvetica', '', 8);
      $pdf->SetY($pdf->GetY() - 3);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array($xml->Complemento->TimbreFiscalDigital[0]['selloCFD']), false, 0);

      if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
          $pdf->AddPage();

      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(10, $pdf->GetY() - 2);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array('Sello Digital del SAT:'), false, 0);

      $pdf->SetFont('helvetica', '', 8);
      $pdf->SetY($pdf->GetY() - 3);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array($xml->Complemento->TimbreFiscalDigital[0]['selloSAT']), false, 0);

      /////////////
      // QR CODE //
      /////////////

      // formato
      // ?re=XAXX010101000&rr=XAXX010101000&tt=1234567890.123456&id=ad662d33-6934-459c-a128-BDf0393f0f44
      // 0000001213.520000

      $total = explode('.', $xml[0]['total']);

      // Obtiene la diferencia de caracteres en la parte entera.
      $diff = 10 - strlen($total[0]);

      // Agrega los 0 faltantes  a la parte entera.
      for ($i=0; $i < $diff; $i++)
        $total[0] = "0{$total[0]}";

      // Si el total no contiene decimales le asigna en la parte decimal 6 ceros.
      if (count($total) === 1)
      {
        $total[1] = '000000';
      }
      else
      {
        // Obtiene la diferencia de caracteres en la parte decimal.
        $diff = 6 - strlen($total[1]);

        // Agregar los 0 restantes en la parte decimal.
        for ($i=0; $i < $diff; $i++)
          $total[1] = "{$total[1]}0";
      }

      $code = "?re={$xml->Emisor[0]['rfc']}";
      $code .= "&rr={$xml->Receptor[0]['rfc']}";
      $code .= "&tt={$total[0]}.{$total[1]}";
      $code .= "&id={$xml->Complemento->TimbreFiscalDigital[0]['UUID']}";

      // echo "<pre>";
      //   var_dump($code, $total, $diff);
      // echo "</pre>";exit;

      QRcode::png($code, APPPATH.'media/qrtemp.png', 'H', 3);

      if($pdf->GetY() + 50 >= $pdf->limiteY) //salta de pagina si exede el max
          $pdf->AddPage();

      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Image(APPPATH.'media/qrtemp.png', null, null, 40);

      // Elimina el QR generado temporalmente.
      unlink(APPPATH.'media/qrtemp.png');

      ////////////////////
      // Timbrado Datos //
      ////////////////////

      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(45, $pdf->GetY() - 39);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(160));
      $pdf->Row(array('Cadena Original del complemento de certificación digital del SAT:'), false, 0);

      $pdf->SetFont('helvetica', '', 8);
      $cadenaOriginalSAT = "||{$xml->Complemento->TimbreFiscalDigital[0]['version']}|{$xml->Complemento->TimbreFiscalDigital[0]['UUID']}|{$xml->Complemento->TimbreFiscalDigital[0]['FechaTimbrado']}|{$xml->Complemento->TimbreFiscalDigital[0]['selloCFD']}|{$xml->Complemento->TimbreFiscalDigital[0]['noCertificadoSAT']}||";
      $pdf->SetXY(45, $pdf->GetY() - 3);
      $pdf->Row(array($cadenaOriginalSAT), false, 0);

      $pdf->SetFont('helvetica', 'B', 10);
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 171, 72);
      $pdf->SetXY(45, $pdf->GetY() + 1);
      $pdf->Cell(68, 6, "Folio Fiscal:", 0, 0, 'R', 1);

      $pdf->SetXY(125, $pdf->GetY());
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['UUID'], 0, 0, 'C', 0);

      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 171, 72);
      $pdf->SetXY(45, $pdf->GetY() + 7);
      $pdf->Cell(68, 6, "No de Serie del Certificado del SAT:", 0, 0, 'R', 1);

      $pdf->SetXY(125, $pdf->GetY());
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['noCertificadoSAT'], 0, 0, 'C', 0);

      $pdf->SetFont('helvetica', 'B', 10);
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 171, 72);
      $pdf->SetXY(45, $pdf->GetY() + 7);
      $pdf->Cell(68, 6, "Fecha y hora de certificación:", 0, 0, 'R', 1);

      $pdf->SetXY(125, $pdf->GetY());
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['FechaTimbrado'], 0, 0, 'C', 0);

      $pdf->SetXY(0, $pdf->GetY()+13);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->Cell(220, 6, 'ESTE DOCUMENTO ES UNA REPRESENTACION IMPRESA DE UN CFDI.', 0, 0, 'C', 0);
    }

    if($pdfuno)
      $pdf->Output('Nomina.pdf', 'I');
  }

  public function pdfReciboNominaFiscalFiniquito($empleadoId, $semana, $anio, $empresaId, $pdf=null)
  {
    $this->load->model('empresas_model');

    if ($empresaId !== '')
      $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $empresaId)->get()->row()->dia_inicia_semana;
    else
      $dia = '4';
    $semana = $this->fechasDeUnaSemana($semana, $anio, $dia);

    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);

    $finiquitos = $this->db->query("SELECT u.*, f.*, up.nombre AS puesto FROM usuarios AS u INNER JOIN finiquito AS f ON u.id = f.id_empleado
        INNER JOIN usuarios_puestos AS up ON up.id_puesto = u.id_puesto
      WHERE u.id = {$empleadoId} AND f.fecha_salida BETWEEN '{$semana['fecha_inicio']}' AND '{$semana['fecha_final']}'")->row();


    $configuraciones = $this->configuraciones();
    $finiquitos = $this->nomina
        ->setEmpresaConfig($configuraciones['nomina'][0])
        ->setVacacionesConfig($configuraciones['vacaciones'])
        ->setSalariosZonas($configuraciones['salarios_zonas'][0])
        ->setClavesPatron($configuraciones['cuentas_contpaq'])
        ->setTablasIsr($configuraciones['tablas_isr'])
        ->calculoBasico($finiquitos);

    include_once(APPPATH.'libraries/phpqrcode/qrlib.php');

    $xml = simplexml_load_string(str_replace(array('cfdi:', 'tfd:', 'nomina:'), '', $finiquitos->xml));

    // echo "<pre>";
    //   var_dump($nomina, $xml);
    // echo "</pre>";exit;

    if ($pdf == null)
    {
      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
    }
    $pdf->show_head = true;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = "Recibo de Finiquito de {$semana['fecha_inicio']} al {$semana['fecha_final']}";
    $pdf->titulo3 = "Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $total_gral = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0,
      'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'isr' => 0,
      'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0);

    $total_dep = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0,
      'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'isr' => 0,
      'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0);

    $dep_tiene_empleados = true;
    $y = $pdf->GetY();
    // foreach ($empleados as $key => $empleado)
    // {
      // if($dep_tiene_empleados)
      // {
        $pdf->SetFont('Helvetica','', 10);
        $pdf->SetXY(6, $pdf->GetY() + 4);
        $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
        $pdf->Row(array('', 'Percepción', 'Importe', '', 'Deducción', 'Importe'), false, false, null, 2, 1);

        $pdf->SetFont('Helvetica','', 10);
        $pdf->SetXY(6, $pdf->GetY() - 2);
        $pdf->Cell(200, 2, "________________________________________________________________________________________________________", 0, 0, 'L', 0);
        $dep_tiene_empleados = false;
      // }

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY() + 4);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(15, 100));
      $pdf->Row(array($finiquitos->no_empleado, $finiquitos->nombre.' '.$finiquitos->apellido_paterno.' '.$finiquitos->apellido_materno), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetFont('Helvetica','', 9);
      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(50, 70, 50));
      $pdf->Row(array($finiquitos->puesto, "RFC: {$finiquitos->rfc}", "Afiliciación IMSS: {$finiquitos->no_seguro}"), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(50, 35, 35, 35, 30));
      $pdf->Row(array("Fecha Ingr: {$finiquitos->fecha_entrada}", "Sal. diario: {$finiquitos->salario_diario}",
        "S.D.I: ".$finiquitos->salario_diario_integrado, "S.B.C: 0", 'Cotiza fijo'), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $horasExtras = 0;

      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(35, 35, 25, 35, 70));
      $pdf->Row(array("Dias Pagados: {$finiquitos->dias_trabajados}", "Tot Hrs trab: " . $finiquitos->dias_trabajados * 8, 'Hrs dia: 8.00', "Hrs extras: " . number_format($horasExtras, 2), "CURP: {$finiquitos->curp}"), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $y2 = $pdf->GetY();

      // Percepciones
      // $percepciones = $empleado->nomina->percepciones;

      // Sueldo
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Sueldo', String::formatoNumero($finiquitos->sueldo_semanal, 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }

      // Vacaciones y prima vacacional
      if ($finiquitos->vacaciones > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Vacaciones', String::formatoNumero($finiquitos->vacaciones, 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }

        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Prima vacacional', String::formatoNumero($finiquitos->prima_vacacional, 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // Subsidio
      if ($finiquitos->subsidio > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Subsidio', String::formatoNumero($finiquitos->subsidio, 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      // Aguinaldo
      if ($finiquitos->aguinaldo > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Aguinaldo', String::formatoNumero($finiquitos->aguinaldo, 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      $y = $pdf->GetY();

      // Deducciones
      // $deducciones = $empleado->nomina->deducciones;
      $pdf->SetFont('Helvetica','', 9);

      $pdf->SetY($y2);

      if ($finiquitos->deduccion_otros > 0) //prestamos
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Prestamos', String::formatoNumero($finiquitos->deduccion_otros, 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      if ($finiquitos->isr > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'ISR', String::formatoNumero($finiquitos->isr, 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      if ($y < $pdf->GetY())
      {
        $y = $pdf->GetY();
      }

      // Total percepciones y deducciones
      $pdf->SetXY(6, $y + 2);
      $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
      $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($finiquitos->total_percepcion, 2, '$', false), '', 'Total Deducciones', String::formatoNumero($finiquitos->total_deduccion, 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Total Neto', String::formatoNumero($finiquitos->total_neto, 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica', '', 9);
      $pdf->SetXY(120, $pdf->GetY()+3);
      $pdf->Cell(200, 2, "--------------------------------------------------------------------------------------", 0, 0, 'L', 0);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
    // }

    if($xml === false)
      true;
    else
    {
      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY()+4);
      $pdf->Cell(78, 4, 'RFC EMISOR: '.$xml->Emisor[0]['rfc'], 0, 0, 'L', 0);

      $pdf->SetXY(86, $pdf->GetY());
      $pdf->Cell(78, 4, 'Forma de Pago: '.$xml[0]['formaDePago'], 0, 0, 'L', 0);

      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(158, $pdf->GetY());
      $pdf->Cell(78, 4, 'Condicion de Pago: Contado', 0, 0, 'L', 0);

      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(86, $pdf->GetY()+5);
      $pdf->Cell(78, 4, "Metodo de Pago: {$xml[0]['metodoDePago']}", 0, 0, 'L', 0);

      $cuenta_banco = substr($finiquitos->cuenta_banco, -4);
      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(158, $pdf->GetY());
      $pdf->Cell(76, 4, "Cuenta de Pago: {$cuenta_banco}", 0, 0, 'L', 0);
      ////////////////////
      // Timbrado Datos //
      ////////////////////
      if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
          $pdf->AddPage();

      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(10, $pdf->GetY() + 5);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array('Sello Digital del CFDI:'), false, 0);

      $pdf->SetFont('helvetica', '', 8);
      $pdf->SetY($pdf->GetY() - 3);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array($xml->Complemento->TimbreFiscalDigital[0]['selloCFD']), false, 0);

      if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
          $pdf->AddPage();

      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(10, $pdf->GetY() - 2);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array('Sello Digital del SAT:'), false, 0);

      $pdf->SetFont('helvetica', '', 8);
      $pdf->SetY($pdf->GetY() - 3);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array($xml->Complemento->TimbreFiscalDigital[0]['selloSAT']), false, 0);

      /////////////
      // QR CODE //
      /////////////

      // formato
      // ?re=XAXX010101000&rr=XAXX010101000&tt=1234567890.123456&id=ad662d33-6934-459c-a128-BDf0393f0f44
      // 0000001213.520000

      $total = explode('.', $xml[0]['total']);

      // Obtiene la diferencia de caracteres en la parte entera.
      $diff = 10 - strlen($total[0]);

      // Agrega los 0 faltantes  a la parte entera.
      for ($i=0; $i < $diff; $i++)
        $total[0] = "0{$total[0]}";

      // Si el total no contiene decimales le asigna en la parte decimal 6 ceros.
      if (count($total) === 1)
      {
        $total[1] = '000000';
      }
      else
      {
        // Obtiene la diferencia de caracteres en la parte decimal.
        $diff = 6 - strlen($total[1]);

        // Agregar los 0 restantes en la parte decimal.
        for ($i=0; $i < $diff; $i++)
          $total[1] = "{$total[1]}0";
      }

      $code = "?re={$xml->Emisor[0]['rfc']}";
      $code .= "&rr={$xml->Receptor[0]['rfc']}";
      $code .= "&tt={$total[0]}.{$total[1]}";
      $code .= "&id={$xml->Complemento->TimbreFiscalDigital[0]['UUID']}";

      // echo "<pre>";
      //   var_dump($code, $total, $diff);
      // echo "</pre>";exit;

      QRcode::png($code, APPPATH.'media/qrtemp.png', 'H', 3);

      if($pdf->GetY() + 50 >= $pdf->limiteY) //salta de pagina si exede el max
          $pdf->AddPage();

      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Image(APPPATH.'media/qrtemp.png', null, null, 40);

      // Elimina el QR generado temporalmente.
      unlink(APPPATH.'media/qrtemp.png');

      ////////////////////
      // Timbrado Datos //
      ////////////////////

      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(45, $pdf->GetY() - 39);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(160));
      $pdf->Row(array('Cadena Original del complemento de certificación digital del SAT:'), false, 0);

      $pdf->SetFont('helvetica', '', 8);
      $cadenaOriginalSAT = "||{$xml->Complemento->TimbreFiscalDigital[0]['version']}|{$xml->Complemento->TimbreFiscalDigital[0]['UUID']}|{$xml->Complemento->TimbreFiscalDigital[0]['FechaTimbrado']}|{$xml->Complemento->TimbreFiscalDigital[0]['selloCFD']}|{$xml->Complemento->TimbreFiscalDigital[0]['noCertificadoSAT']}||";
      $pdf->SetXY(45, $pdf->GetY() - 3);
      $pdf->Row(array($cadenaOriginalSAT), false, 0);

      $pdf->SetFont('helvetica', 'B', 10);
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 171, 72);
      $pdf->SetXY(45, $pdf->GetY() + 1);
      $pdf->Cell(68, 6, "Folio Fiscal:", 0, 0, 'R', 1);

      $pdf->SetXY(125, $pdf->GetY());
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['UUID'], 0, 0, 'C', 0);

      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 171, 72);
      $pdf->SetXY(45, $pdf->GetY() + 7);
      $pdf->Cell(68, 6, "No de Serie del Certificado del SAT:", 0, 0, 'R', 1);

      $pdf->SetXY(125, $pdf->GetY());
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['noCertificadoSAT'], 0, 0, 'C', 0);

      $pdf->SetFont('helvetica', 'B', 10);
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 171, 72);
      $pdf->SetXY(45, $pdf->GetY() + 7);
      $pdf->Cell(68, 6, "Fecha y hora de certificación:", 0, 0, 'R', 1);

      $pdf->SetXY(125, $pdf->GetY());
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['FechaTimbrado'], 0, 0, 'C', 0);

      $pdf->SetXY(0, $pdf->GetY()+13);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->Cell(220, 6, 'ESTE DOCUMENTO ES UNA REPRESENTACION IMPRESA DE UN CFDI.', 0, 0, 'C', 0);
    }

    if($pdf == null)
      $pdf->Output('Nomina.pdf', 'I');
  }

  public function rptVacacionesPdf($empresaId)
  {
    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);

    $data = $this->db->query("SELECT
        id, (COALESCE(u.apellido_paterno, '') || ' ' || COALESCE(u.apellido_materno, '') || ' ' || u.nombre) as nombre,
        Date(fecha_entrada) AS fecha_entrada, id_departamente, Date(fecha_nacimiento) AS fecha_nacimiento,
        (SELECT Date(fecha) FROM nomina_fiscal_vacaciones WHERE id_empleado = u.id ORDER BY anio DESC, semana DESC LIMIT 1) AS fecha_ultima
      FROM usuarios AS u
      WHERE id_empresa = {$empresaId} AND user_nomina = 't' AND status = 't'")->result();


    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->logo = $empresa['info']->logo;
    // $pdf->titulo2 = "Lista de Raya de {$semana['fecha_inicio']} al {$semana['fecha_final']}";
    // $pdf->titulo3 = "Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $columnas = array(
      'n' => array('NOMBRE', 'ULTIMA V.', 'PROX V.', 'DIAS', 'CUMPLEAÑOS'),
      'w' => array(90, 25, 25, 25, 25),
      'a' => array('L', 'L', 'L', 'L', 'L'));

    $pdf->SetFont('Helvetica','B', 8);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns($columnas['a']);
    $pdf->SetWidths($columnas['w']);
    $pdf->Row($columnas['n'], false, false, null, 2, 1);

    $ttotal_aseg_no_trs = $sueldo_semanal_real = $otras_percepciones = $domingo = $total_prestamos = $total_infonavit = $descuento_playeras = $descuento_otros = $ttotal_pagar = $ttotal_nomina = $total_no_fiscal = 0;
    $y = $pdf->GetY();

    $this->load->library('nomina');
    $nomina = $this->nomina->setVacacionesConfig($this->getConfigNominaVacaciones());
    $fechaActual = new DateTime(date('Y-m-d'));

    foreach ($data as $key => $empleado)
    {
      $fechaInicioTrabajar = new DateTime($empleado->fecha_entrada);
      $anios_trabajados_empleado = intval($fechaInicioTrabajar->diff($fechaActual)->y);

      // if($departamento->id_departamento == $_POST['departamento_id'][$key])
      // {
      //   $empleado = $this->usuarios_model->get_usuario_info($empleado, true)['info'][0];

        $pdf->SetFont('Helvetica','', 8);
        if($pdf->GetY() >= $pdf->limiteY){
          $pdf->AddPage();
          $pdf->SetFont('Helvetica','B', 8);
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->Row($columnas['n'], false, false, null, 2, 1);
        }

        $pdf->SetFont('Helvetica','', 8);
        $pdf->SetXY(6, $pdf->GetY());

        if($empleado->fecha_ultima!='')
        {
          $fecha_aux = explode('-', $empleado->fecha_entrada);
          $fecha_aux[0] = date("Y", strtotime("{$empleado->fecha_ultima} +1 year"));
          $fecha_entrada = implode('-', $fecha_aux);
        }else{
          $fecha_entrada = strtotime("{$empleado->fecha_entrada} +1 year");
          if(date("Y", $fecha_entrada) < date("Y") )
            $fecha_entrada = strtotime( date("Y").'-'.date("m-d", $fecha_entrada). " +1 year");
          $fecha_entrada = date("Y-m-d", $fecha_entrada);
        }

        $cumpleanios = strtotime( date("Y").'-'.date("m-d", strtotime("{$empleado->fecha_nacimiento}")) );
        if($cumpleanios < strtotime("now"))
          $cumpleanios = strtotime( date("Y-m-d", $cumpleanios). " +1 year");
        $cumpleanios = date("Y-m-d", $cumpleanios);

        $dataarr = array(
          $empleado->nombre,
          ($empleado->fecha_ultima!=''? String::fechaATexto($empleado->fecha_ultima, '/c'): 'No a tenido'),
          String::fechaATexto($fecha_entrada, '/c'),
          $nomina->diasVacacionesCorresponden($anios_trabajados_empleado+1),
          String::fechaATexto($cumpleanios, '/c'),
          );

        $pdf->Row($dataarr, false, true, null, 2, 1);
      // }
    }

    $pdf->Output('Nomina.pdf', 'I');
  }

  public function rptTrabajadoresPrestamosPdf($usuarioId, $fecha1, $fecha2, $todos = false)
  {
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

      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
      $pdf->show_head = true;
      $pdf->titulo1 = $empresa['info']->nombre_fiscal;
      // $pdf->titulo1 S= $empresa['info']->nombre_fiscal;
      // $pdf->logo = $empresa['info']->logo;
      $pdf->titulo2 = $empleado['info'][0]->nombre.' '.$empleado['info'][0]->apellido_paterno;
      $pdf->titulo3 = "Reporte de Prestamos del {$fecha1} al {$fecha2}";
      $pdf->AliasNbPages();
      $pdf->AddPage();

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

      $pdf->Output('Reporte_Prestamos_Trabajador.pdf', 'I');
    }
  }

  public function asistencia_pdf($empresaId, $semana, $anio=null)
  {
    $anio = $anio==null?date("Y"): $anio;
    $this->load->model('empresas_model');
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId);

    $sem = $this->fechasDeUnaSemana($semana, $anio, $empresa['info']->dia_inicia_semana);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->logo = $empresa['info']->logo;
    $pdf->titulo2 = "Semana {$semana} del {$sem['fecha_inicio']} al {$sem['fecha_final']}";
    $pdf->titulo3 = "LISTA DE TRABAJO";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $columnas = array(
      'n' => array('', 'NOMBRE DEL TRABAJADOR', 'DIAS TRABAJADOS'),
      'w' => array(5, 60, 140),
      'a' => array('L', 'L', 'C')
    );

    $pdf->SetFont('Helvetica','B', 8);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetFillColor(255, 255, 255); // 242, 242, 242
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetAligns($columnas['a']);
    $pdf->SetWidths($columnas['w']);
    $pdf->Row($columnas['n'], 1, 1, null, 2, 1);

    $columnas = array(
      'n' => array('', '', 'S', 'L', 'M', 'M', 'J', 'V', 'D', 'PTMOS'),
      'w' => array(5, 60, 17, 17, 17, 17, 17, 17, 17, 21),
      'a' => array('', 'L', 'C', 'C', 'C', 'C', 'C', 'C', 'C', 'C')
    );

    $pdf->SetFont('Helvetica','B', 8);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetFillColor(255, 255, 255); // 242, 242, 242
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetAligns($columnas['a']);
    $pdf->SetWidths($columnas['w']);
    $pdf->Row($columnas['n'], 1, 1, null, 2, 1);

    $y = $pdf->GetY();
    $this->load->model('usuarios_puestos_model');
    $_GET['did_empresa'] = $empresaId;
    $puestos = $this->usuarios_puestos_model->getPuestos(false);

    $filtros = array(
      'semana'    => $semana,
      'empresaId' => $empresaId,
      'puestoId'  => '',
    );

    // Datos para la vista.
    $empleados = $this->listadoEmpleadosAsistencias($filtros);

    foreach ($puestos['puestos'] as $key => $puesto)
    {
      $pdf->SetFont('Helvetica','', 8);
      if($pdf->GetY() >= $pdf->limiteY){
        $pdf->AddPage();
        $pdf->SetFont('Helvetica','B', 8);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row($columnas['n'], false, false, null, 2, 1);
      }

      $pdf->SetFont('Helvetica','B', 8);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetFillColor(242, 242, 242); // 242, 242, 242
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetAligns($columnas['a']);
      $pdf->SetWidths($columnas['w']);
      $pdf->Row(array('', $puesto->nombre, '', '', '', '', '', '', '', ''), 1, 1, null, 2, 1);

      foreach ($empleados as $empleado)
      {
        if ($empleado->id_puesto == $puesto->id_puesto)
        {
          if($pdf->GetY() >= $pdf->limiteY){
            $pdf->AddPage();
            $pdf->SetFont('Helvetica','B', 8);
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->Row($columnas['n'], false, false, null, 2, 1);
          }

          $pdf->SetFont('Helvetica','B', 8);
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetFillColor(255, 255, 255); // 242, 242, 242
          $pdf->SetTextColor(0, 0, 0);
          $pdf->SetAligns($columnas['a']);
          $pdf->SetWidths($columnas['w']);
          $pdf->Row(array('', $empleado->nombre, '', '', '', '', '', '', '', ''), 1, 1, null, 2, 1);
        }
      }
    }

    $pdf->Output('Reporte_Asistencias.pdf', 'I');
  }

  public function printReciboVacaciones($filtros)
  {
    if ($filtros['fid_trabajador'] !== '' && $filtros['fsalario_real'] !== '' && $filtros['fdias'])
    {
      $this->load->model('usuarios_model');
      $this->load->model('empresas_model');
      $this->load->library('mypdf');

      $empleado = $this->usuarios_model->get_usuario_info($filtros['fid_trabajador']);
      $empresa = $this->empresas_model->getInfoEmpresa($empleado['info'][0]->id_empresa);

      // echo "<pre>";
      //   var_dump($empleado);
      // echo "</pre>";exit;

      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');

      // if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;

      $pdf->titulo1 = $empresa['info']->nombre_fiscal;
      $pdf->titulo2 = 'CALCULO DE VACACIONES';
      $pdf->titulo3 = '';
      // $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
      // $pdf->AliasNbPages();
      $pdf->AddPage();

      $pdf->SetFont('Arial','B', 11);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetFillColor(255, 255, 255);
      $pdf->SetXY(33, 50);
      $pdf->SetAligns(array('L', 'R'));
      $pdf->SetWidths(array(100, 50));
      $pdf->Row(array("{$empleado['info'][0]->nombre} {$empleado['info'][0]->apellido_paterno} {$empleado['info'][0]->apellido_materno}", String::formatoNumero($filtros['fsalario_real'], 2, '$', false)), false, false);

      $vacaciones = $filtros['fsalario_real'] * $filtros['fdias'];
      $primaVacacional = $vacaciones * 0.25;

      $pdf->SetFont('Arial','', 11);
      $pdf->SetXY(33, $pdf->GetY() + 5);
      $pdf->Row(array("{$filtros['fdias']} DIAS DE VACACIONES", String::formatoNumero($filtros['fsalario_real'] * $filtros['fdias'], 2, '$', false)), false, false);

      $pdf->SetX(33);
      $pdf->Row(array("P. VACACIONAL", String::formatoNumero($primaVacacional, 2, '$', false)), false, false);

      $pdf->SetFont('Arial','B', 11);
      $pdf->SetX(33);
      $pdf->SetAligns(array('R', 'R'));
      $pdf->Row(array("TOTAL", String::formatoNumero($vacaciones + $primaVacacional, 2, '$', false)), false, false);

      $pdf->SetFont('Arial','', 10);
      $pdf->SetXY(33, $pdf->GetY() + 10);
      $pdf->SetAligns(array('C'));
      $pdf->SetWidths(array(150));
      $total_pp = String::formatoNumero($vacaciones + $primaVacacional, 2, '$', false);
      $pdf->Row(array("RECIBI POR CONCEPTO DE VACACIONES LA CANTIDAD DE " . $total_pp), false, false);
      $pdf->SetX(33);

      $pdf->Row(array("(== " . strtoupper(String::num2letras(String::float($total_pp))) . " ==)"), false, false);
      $pdf->SetX(33);

      $inicio = new DateTime($empleado['info'][0]->fecha_imss);
      $hoy = new DateTime(date('Y-m-d'));

      $pdf->Row(array("POR LAS VACACIONES DEL ".String::numeroCardinal($hoy->diff($inicio)->y)." AÑO DE LABORES. "), false, false);

      $pdf->SetXY(33, $pdf->GetY() + 10);
      $pdf->SetAligns(array('C'));
      $pdf->SetWidths(array(150));
      $pdf->Row(array("RECIBI"), false, false);

      $pdf->SetXY(33, $pdf->GetY() + 15);
      $pdf->SetAligns(array('C'));
      $pdf->SetWidths(array(150));
      $pdf->Row(array("__________________________________________________________"), false, false);

      $pdf->SetX(33);
      $pdf->Row(array("{$empleado['info'][0]->nombre} {$empleado['info'][0]->apellido_paterno} {$empleado['info'][0]->apellido_materno}"), false, false);

      $pdf->Output('RECIBO_VACACIONES.pdf', 'I');
    }
  }

  public function printReciboFiniquito($filtros)
  {
    if ($filtros['fid_trabajador'] !== '' && $filtros['fsalario_real'] !== '' && $filtros['ffecha1'] && $filtros['ffecha2'])
    {
      $filtros['indem_cons'] = isset($filtros['indem_cons'])? $filtros['indem_cons']: false;
      $filtros['indem'] = isset($filtros['indem'])? $filtros['indem']: false;
      $filtros['prima'] = isset($filtros['prima'])? $filtros['prima']: false;
      $despido = false;
      if ($filtros['indem_cons'] || $filtros['indem'] || $filtros['prima'])
        $despido = true;

      $this->load->model('usuarios_model');
      $this->load->model('empresas_model');
      $this->load->library('mypdf');

      $empleado = $this->usuarios_model->get_usuario_info($filtros['fid_trabajador']);
      $empresa = $this->empresas_model->getInfoEmpresa($empleado['info'][0]->id_empresa);
      $_GET['cid_empresa'] = $empleado['info'][0]->id_empresa;

      $this->load->library('nomina');
      $configuraciones = $this->configuraciones();

      $empleado['info'][0]->fecha_entrada = $filtros['ffecha1'];
      $empleado['info'][0]->fecha_salida = $filtros['ffecha2'];
      $empleado['info'][0] = $this->nomina
          ->setEmpresaConfig($configuraciones['nomina'][0])
          ->setVacacionesConfig($configuraciones['vacaciones'])
          ->setSalariosZonas($configuraciones['salarios_zonas'][0])
          ->setClavesPatron($configuraciones['cuentas_contpaq'])
          ->setTablasIsr($configuraciones['tablas_isr'])
          ->calculoBasico($empleado['info'][0]);

      // echo "<pre>";
      //   var_dump($empleado['info'][0]);
      // echo "</pre>";exit;

      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');

      // if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;

      $pdf->titulo1 = $empresa['info']->nombre_fiscal;
      $pdf->titulo2 = !$despido? 'CALCULO DE RENUNCIA VOLUNTARIA': 'CALCULO DE DESPIDO';
      $pdf->titulo3 = '';
      // $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
      // $pdf->AliasNbPages();
      $pdf->AddPage();

      $pdf->SetFont('Arial','B', 9);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetFillColor(255, 255, 255);
      $pdf->SetXY(33, 25);
      $pdf->SetAligns(array('L', 'R'));
      $pdf->SetWidths(array(100, 50));
      $pdf->Row(array("NOMBRE: {$empleado['info'][0]->nombre} {$empleado['info'][0]->apellido_paterno} {$empleado['info'][0]->apellido_materno}", "SALARIO DIARIO " . String::formatoNumero($filtros['fsalario_real'], 2, '$', false)), false, false);

      // $vacaciones = $filtros['fsalario_real'] * $filtros['fdias'];
      // $primaVacacional = $vacaciones * 0.25;

      $pdf->SetFont('Arial','', 9);
      $pdf->SetX(33);
      $pdf->SetAligns(array('L', 'l'));
      $pdf->SetWidths(array(50, 50));
      $pdf->Row(array("FECHA DE INGRESO", $filtros['ffecha1']), false, false);

      $pdf->SetX(33);
      $pdf->Row(array("FECHA DE BAJA", $filtros['ffecha2']), false, false);

      $pdf->SetAligns(array('L', 'R'));

      $pdf->SetFont('Arial','B', 8);
      $pdf->SetXY(60, $pdf->GetY() + 3);
      $pdf->Row(array("VACACIONES", ""), false, false);

      $pdf->SetFont('Arial','', 8);
      $pdf->SetX(60);
      $pdf->Row(array("SALARIO DIARIO", String::formatoNumero($filtros['fsalario_real'], 2, '$', false)), false, false);

      $fechaEntrada = new DateTime($filtros['ffecha1']);
      $fechaSalida = new DateTime($filtros['ffecha2']);
      $fechaIniAnio = new DateTime($fechaSalida->format("Y").'-01-01');
      // $diasProporcionVacaciones = round((($fechaEntrada->diff($fechaSalida)->days + 1) / 365) * 6, 2);
      if ($fechaIniAnio < $fechaEntrada) {
        $fechaIniAnio = $fechaEntrada;
      }
      $diasProporcionAguinaldo = round(( ($fechaIniAnio->diff($fechaSalida)->days) / 365) * 15, 2);

      //Dias trabajados en el año en que entro
      $anio_anterior = new DateTime(date('Y').'-'.$fechaEntrada->format("m-d"));
      $anio_anterior->modify("-1 year");
      if($anio_anterior->getTimestamp() < $fechaEntrada->getTimestamp())
      {
        $anio_anterior->setTimestamp($fechaEntrada->getTimestamp());
      }
      $limite_vacaciones = new DateTime($anio_anterior->format("Y-m-d"));
      $limite_vacaciones->modify("+1 year");

      // Obtenemos si se le pagaron vacaciones
      $res_vacaciones = $this->db->query("SELECT Date(fecha_fin) AS fecha FROM nomina_fiscal_vacaciones
        WHERE id_empleado = {$filtros['fid_trabajador']} AND anio = ".date("Y"))->row();
      if(isset($res_vacaciones->fecha) && strtotime($res_vacaciones->fecha) > strtotime($anio_anterior->format("Y-m-d"))) {
        if ($limite_vacaciones->getTimestamp() < $fechaSalida->getTimestamp() ) {
          $anio_anterior->setTimestamp($limite_vacaciones->getTimestamp());
        } else
          $anio_anterior->setTimestamp(strtotime($res_vacaciones->fecha));
      }
      $empleado['info'][0]->dias_anio_vacaciones = $anio_anterior->diff($fechaSalida)->days + 1;

      $empleado['info'][0]->dias_vacaciones = $empleado['info'][0]->dias_vacaciones==0? 6: $empleado['info'][0]->dias_vacaciones;
      $diasProporcionVacaciones = round(($empleado['info'][0]->dias_anio_vacaciones / 365) * $empleado['info'][0]->dias_vacaciones, 2);

      $indemnisaciones = 0;
      if ($despido) {
        $despido_injustificado = 0;
        if ($filtros['indem_cons']) {
          // 3 meses de sueldo
          $despido_injustificado = $filtros['fsalario_real']*90;
        }

        $indemnisacion_negativa = 0;
        if ($filtros['indem']) {
          // 20 días de sueldo por cada año de servicios prestados
          $indemnisacion_negativa = 20*$empleado['info'][0]->anios_trabajados*$filtros['fsalario_real'];
          $indemnisacion_negativa += 20*($empleado['info'][0]->dias_anio_vacaciones/365)*$filtros['fsalario_real'];
        }

        $prima_antiguedad = 0;
        if ($filtros['prima']) {
          // Prima de antigüedad 12 días de salario por cada año de servicio
          $prima_antiguedad = floatval($configuraciones['salarios_zonas'][0]->zona_b)*2*$empleado['info'][0]->anios_trabajados*12;
          $prima_antiguedad += floatval($configuraciones['salarios_zonas'][0]->zona_b)*2*($empleado['info'][0]->dias_anio_vacaciones/365)*12;
        }

        $indemnisaciones = round($despido_injustificado+$indemnisacion_negativa+$prima_antiguedad, 4);
      }

      $vacaciones = $diasProporcionVacaciones * $filtros['fsalario_real'];
      $aguinaldo = $diasProporcionAguinaldo * $filtros['fsalario_real'];

      $pdf->SetX(60);
      $pdf->Row(array("DIAS (PROPORCION)", $diasProporcionVacaciones), false, false);

      $pdf->SetX(60);
      $pdf->Row(array("TOTAL", String::formatoNumero($vacaciones, 2, '$', false)), false, false);

      // ---------------------------

      $pdf->SetFont('Arial','B', 8);
      $pdf->SetXY(60, $pdf->GetY() + 3);
      $pdf->Row(array("PRIMA VACACIONAL", ""), false, false);

      $pdf->SetFont('Arial','', 8);
      $pdf->SetX(60);
      $pdf->Row(array("VACACIONES", String::formatoNumero($vacaciones, 2, '$', false)), false, false);

      $fechaEntrada = new DateTime($filtros['ffecha1']);
      $fechaSalida = new DateTime($filtros['ffecha2']);
      $diasProporcionVacaciones = round((($fechaEntrada->diff($fechaSalida)->days + 1) / 365) * 6, 2);

      $pdf->SetX(60);
      $pdf->Row(array("TASA", '25%'), false, false);

      $pdf->SetX(60);
      $pdf->Row(array("PRIMA A PAGAR", String::formatoNumero($vacaciones * 0.25, 2, '$', false)), false, false);

      // ---------------------------

      $pdf->SetFont('Arial','B', 8);
      $pdf->SetXY(60, $pdf->GetY() + 3);
      $pdf->Row(array("AGUINALDO", ""), false, false);

      $pdf->SetFont('Arial','', 8);
      $pdf->SetX(60);
      $pdf->Row(array("SALARIO DIARIO", String::formatoNumero($filtros['fsalario_real'], 2, '$', false)), false, false);

      $pdf->SetX(60);
      $pdf->Row(array("DIAS (PROPORCION)", $diasProporcionAguinaldo), false, false);

      $pdf->SetX(60);
      $pdf->Row(array("TOTAL", String::formatoNumero($aguinaldo, 2, '$', false)), false, false);

      // --------------------------------

      if ($despido) {
        $pdf->SetFont('Arial','B', 8);
        $pdf->SetXY(60, $pdf->GetY() + 3);
        $pdf->Row(array("INDEMNIZACIONES", ""), false, false);

        $pdf->SetFont('Arial','', 8);
        if ($filtros['indem_cons']) {
          $pdf->SetX(60);
          $pdf->Row(array("3 meses de sueldo", String::formatoNumero($despido_injustificado, 2, '$', false)), false, false);
        }

        if ($filtros['indem']) {
          $pdf->SetX(60);
          $pdf->Row(array("Indemnisacion negativa", String::formatoNumero($indemnisacion_negativa, 2, '$', false)), false, false);
        }

        if ($filtros['prima']) {
          $pdf->SetX(60);
          $pdf->Row(array("Prima antiguedad", String::formatoNumero($prima_antiguedad, 2, '$', false)), false, false);
        }

        $pdf->SetX(60);
        $pdf->Row(array("TOTAL", String::formatoNumero($indemnisaciones, 2, '$', false)), false, false);
      }

      // --------------------------------

      $pdf->SetXY(60, $pdf->GetY() + 6);
      $pdf->SetAligns(array('C'));
      $pdf->SetWidths(array(100));
      $pdf->Row(array("RESUMEN"), false, true);

      $pdf->SetFont('Arial','B', 8);
      $pdf->SetXY(60, $pdf->GetY());
      $pdf->SetAligns(array('L', 'R'));
      $pdf->SetWidths(array(50, 50));
      $pdf->Row(array("VACACIONES", String::formatoNumero($vacaciones, 2, '$', false)), false, false);
      $pdf->SetX(60);
      $pdf->Row(array("PRIMA VACACIONAL", String::formatoNumero($vacaciones * 0.25, 2, '$', false)), false, false);
      $pdf->SetX(60);
      $pdf->Row(array("AGUINALDO", String::formatoNumero($aguinaldo, 2, '$', false)), false, false);
      if ($despido) {
        $pdf->SetX(60);
        $pdf->Row(array("INDEMNIZACIONES", String::formatoNumero($indemnisaciones, 2, '$', false)), false, false);
      }
      $pdf->SetXY(60, $pdf->GetY() + 2);
      $pdf->Row(array("NETO A PAGAR", String::formatoNumero( ($vacaciones + ($vacaciones * 0.25) + $aguinaldo + $indemnisaciones), 2, '$', false)), false, false);

      $pdf->SetXY(33, $pdf->GetY() + 6);
      $pdf->SetAligns(array('C'));
      $pdf->SetWidths(array(150));
      $pdf->Row(array("RECIBI"), false, false);

      $pdf->SetXY(33, $pdf->GetY() + 6);
      $pdf->SetAligns(array('C'));
      $pdf->SetWidths(array(150));
      $pdf->Row(array("__________________________________________________________"), false, false);

      $pdf->SetX(33);
      $pdf->Row(array("{$empleado['info'][0]->nombre} {$empleado['info'][0]->apellido_paterno} {$empleado['info'][0]->apellido_materno}"), false, false);

      $pdf->SetFont('Arial','', 7);
      $pdf->SetXY(33, $pdf->GetY() + 3);
      $pdf->Row(array("Recibi las cantidades arriba señaladas por concepto de mi Terminación de la Relación de Trabajo, manifestando que durante todo este tiempo no sufri Riesgo de Trabajo alguno y que no se me adeuda cantidad alguna por concepto de sueldos o prestaciones, y que  no me reservo acción legal alguna a futuro en contra EMPAQUE SAN JORGE SA DE CV. o a quien sus Derechos Represente."), false, false);

      $pdf->Output('RECIBO_FINIQUITO.pdf', 'I');
    }
  }

  public function printReciboIncapacidad($filtros)
  {
    if ($filtros['fid_trabajador'] !== '' && $filtros['fsalario_real'] !== '' && $filtros['ffecha_inicio'] &&
        $filtros['fdias_incapacidad'] && $filtros['fincapacidad_seguro'])
    {
      $this->load->model('usuarios_model');
      $this->load->model('empresas_model');
      $this->load->library('mypdf');

      $empleado = $this->usuarios_model->get_usuario_info($filtros['fid_trabajador']);
      $empresa = $this->empresas_model->getInfoEmpresa($empleado['info'][0]->id_empresa);

      // echo "<pre>";
      //   var_dump($empleado);
      // echo "</pre>";exit;

      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');

      // if ($empresa['info']->logo !== '')
        $pdf->logo = $empresa['info']->logo;

      $pdf->titulo1 = $empresa['info']->nombre_fiscal;
      $pdf->titulo2 = 'CALCULO DE INCAPACIDAD';
      $pdf->titulo3 = '';
      // $pdf->titulo3 .= ($this->input->get('ftipo') == 'pv'? 'Plazo vencido': 'Pendientes por cobrar');
      // $pdf->AliasNbPages();
      $pdf->AddPage();

      $pdf->SetFont('Arial','B', 11);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetFillColor(255, 255, 255);
      $pdf->SetXY(33, 35);
      $pdf->SetAligns(array('L', 'R'));
      $pdf->SetWidths(array(100, 50));
      $pdf->Row(array("NOMBRE: {$empleado['info'][0]->nombre} {$empleado['info'][0]->apellido_paterno} {$empleado['info'][0]->apellido_materno}", ""), false, false); // "SALARIO DIARIO " . String::formatoNumero($filtros['fsalario_real'], 2, '$', false)

      // $vacaciones = $filtros['fsalario_real'] * $filtros['fdias'];
      // $primaVacacional = $vacaciones * 0.25;

      $fechaInicio = new DateTime($filtros['ffecha_inicio']);

      $pdf->SetFont('Arial','', 11);
      $pdf->SetX(33);
      $pdf->SetAligns(array('L', 'l'));
      $pdf->SetWidths(array(50, 60));
      $pdf->Row(array("INICIO A PARTIR DE: ", mb_strtoupper(String::fechaATexto($filtros['ffecha_inicio'])) ), false, false);
      // $pdf->Row(array("INICIO A PARTIR DE: ", $fechaInicio->format('d') .' DE '. mb_strtoupper(String::mes(9)). ' DEL '. $fechaInicio->format('Y')), false, false);

      $pdf->SetXY(33, $pdf->GetY() + 4);
      $pdf->Row(array("EMPRESA", ""), false, false);

      $total = $filtros['fsalario_real'] * $filtros['fdias_incapacidad'];
      $pdf->SetAligns(array('L', 'R'));

      $pdf->SetX(33);
      $pdf->Row(array("SUELDO REAL", String::formatoNumero($filtros['fsalario_real'], 2, '$', false)), false, false);

      $pdf->SetX(33);
      $pdf->Row(array("DIAS INCAPACIDAD", $filtros['fdias_incapacidad']), false, false);

      $pdf->SetX(33);
      $pdf->Row(array("TOTAL", String::formatoNumero($total, 2, '$', false)), false, false);

      $pdf->SetXY(33, $pdf->GetY() + 2);
      $pdf->Row(array($filtros['fporcentaje']."% SUBSIDIO", ""), false, false);

      $pdf->SetXY(33, $pdf->GetY() + 10);
      $pdf->SetX(33);
      $pdf->Row(array("INCAPACIDAD PAGADA POR EL SEGURO", String::formatoNumero($filtros['fincapacidad_seguro'], 2, '$', false)), false, false);
      // $pdf->SetAligns(array('L', 'R'));

      $pdf->SetFont('Arial','B', 11);
      $pdf->SetXY(33, $pdf->GetY() + 5);
      $incapacidad_patron = ($total*$filtros['fporcentaje']/100) + ( ($total*(100-$filtros['fporcentaje'])/100) - $filtros['fincapacidad_seguro']);
      $pdf->Row(array("INCAPACIDAD PAGADA POR EL PATRON", String::formatoNumero($incapacidad_patron, 2, '$', false)), false, false);

      $pdf->SetXY(33, $pdf->GetY() + 10);
      $pdf->SetAligns(array('C'));
      $pdf->SetWidths(array(150));
      $pdf->Row(array("RECIBI"), false, false);

      $pdf->SetXY(33, $pdf->GetY() + 10);
      $pdf->SetAligns(array('C'));
      $pdf->SetWidths(array(150));
      $pdf->Row(array("__________________________________________________________"), false, false);

      $pdf->SetXY(33, $pdf->GetY() + 10);
      $pdf->SetAligns(array('C'));
      $pdf->SetWidths(array(150));
      $pdf->Row(array("{$empleado['info'][0]->nombre} {$empleado['info'][0]->apellido_paterno} {$empleado['info'][0]->apellido_materno}"), false, false);

      $pdf->Output('RECIBO_INCAPACIDAD.pdf', 'I');
    }
  }

  /*
   |------------------------------------------------------------------------
   | PTU
   |------------------------------------------------------------------------
   */

  public function add_nominas_ptu($datos, $empresaId, $empleadoId)
  {
    // echo "<pre>";
    //   var_dump($datos, $empresaId, $empleadoId);
    // echo "</pre>";exit;
    // $startTime = new DateTime(date('Y-m-d H:i:s'));

    $this->load->library('cfdi');
    $this->load->library('facturartebarato_api');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');

    // Obtiene la informacion de la empresa.
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);

    // Obtiene el certificado.
    $certificado = $this->cfdi->obtenCertificado($this->db
      ->select('cer')
      ->from("empresas")
      ->where("id_empresa", $empresaId)
      ->get()->row()->cer
    );

    // Obtiene las configuraciones.
    $_GET['cid_empresa'] = $empresaId; //para las cuentas del contpaq
    $configuraciones = $this->configuraciones();

    // Almacenara los datos de las nominas de cada empleado para despues
    // insertarlas.
    $nominasEmpleados = array();

    // Almacenara los datos de los prestamos de cada empleado para despues
    // insertarlos.
    $prestamosEmpleados = array();

    // Obtiene el rango de fechas de la semana.
    $fechasSemana = $this->fechasDeUnaSemana($datos['numSemana'], $datos['anio'], $empresa['info']->dia_inicia_semana );

    // Auxiliar para saber si hubo un error al momento de timbrar alguna nomina.
    $errorTimbrar = false;

    // Recorre los empleados para agregar y timbrar sus nominas.
    // foreach ($datos['empleado_id'] as $key => $empleadoId)
    // {
      // Si la nomina del empleado no se ha generado entonces entra.
      if ($datos['generar_nomina'] === '1')
      {
        // $empleado = $this->usuarios_model->get_usuario_info($empleadoId, true);
        $empleado = $this->usuarios_model->get_usuario_info($empleadoId, true);

        $empleadoNomina = $this->nomina(
          $configuraciones,
          array('semana' => $datos['numSemana'], 'empresaId' => $empresaId, 'anio' => $datos['anio'],
            'dia_inicia_semana' => $empresa['info']->dia_inicia_semana, 'asegurado'  => true ),
          $empleadoId,
          null,
          null,
          null,
          null,
          $datos['utilidad_empresa'],
          null,
          'ptu'
        );

        $valorUnitario = 0; // Total de las Percepciones.

        // Recorre las percepciones del empleado.
        foreach ($empleadoNomina[0]->nomina->percepciones as $tipoPercepcion => $percepcion)
        {
          // Si activaron las vacaciones entonces suma las vacaciones y la prima vacacional.
          if ($tipoPercepcion === 'ptu')
          {
            $valorUnitario += $percepcion['total'];
            unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]['total']);
          }
          else
          {
            unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]);
          }
        }

        $isr = 0; // retenciones
        $descuento = 0; // Total de las deducciones(gravado + excento) excepto el ISR.
        // Recorre las deducciones del empleado.
        foreach ($empleadoNomina[0]->nomina->deducciones as $tipoDeduccion => $deduccion)
        {
          if ($tipoDeduccion === 'isr')
          {
            $isr = $deduccion['total'];
            unset($empleadoNomina[0]->nomina->deducciones[$tipoDeduccion]['total']);
          }
          else
          {
            unset($empleadoNomina[0]->nomina->deducciones[$tipoDeduccion]);
          }
        }

        $result = array('xml' => '', 'uuid' => '');
        if($datos['esta_asegurado'] == 't')
        {
          // Obtiene los datos para la cadena original.
          $datosCadenaOriginal = $this->datosCadenaOriginal($empleado, $empresa);
          $datosCadenaOriginal['subTotal'] = $valorUnitario;
          $datosCadenaOriginal['descuento'] = $descuento;
          $datosCadenaOriginal['retencion'][0]['importe'] = $isr;
          $datosCadenaOriginal['totalImpuestosRetenidos'] = $isr;
          $datosCadenaOriginal['total'] = round($valorUnitario - $descuento - $isr, 4);

          // Concepto de la nomina.
          $concepto = array(array(
            'cantidad'         => 1,
            'unidad'           => 'Servicio',
            'descripcion'      => 'PTU Reparto de Utilidades',
            'valorUnitario'    => $valorUnitario,
            'importe'          => $valorUnitario,
            'idClasificacion' => null,
          ));

          $datosCadenaOriginal['concepto'] = $concepto;

          // Obtiene la cadena original para la nomina.
          $cadenaOriginal = $this->cfdi->obtenCadenaOriginal($datosCadenaOriginal, true, $empleadoNomina);

          // Genera el sello en base a la cadena original.
          $sello = $this->cfdi->obtenSello($cadenaOriginal['cadenaOriginal']);

          // Construye los datos para el xml.
          $datosXML = $this->datosXml($cadenaOriginal['datos'], $empresa, $empleado, $sello, $certificado);
          $datosXML['concepto'] = $concepto;

          $archivo = $this->cfdi->generaArchivos($datosXML, true, $fechasSemana, null, ' - PTU');

          $result = $this->timbrar($archivo['pathXML']);
          // echo "<pre>";
          //   var_dump($archivo, $result, base64_encode($result['xml']), $cadenaOriginal);
          // echo "</pre>";

          // Si la nomina se timbro entonces agrega al array nominas la nomina del
          // empleado para despues insertarla en la bdd.
          if (isset($result['result']->status) && $result['result']->status==true)
          {
            $ptuGravado = isset($empleadoNomina[0]->nomina->percepciones['ptu'])
              ? $empleadoNomina[0]->nomina->percepciones['ptu']['ImporteGravado']
              : 0;

            $ptuExcento = isset($empleadoNomina[0]->nomina->percepciones['ptu'])
              ? $empleadoNomina[0]->nomina->percepciones['ptu']['ImporteExcento']
              : 0;

            $ptu = $ptuGravado + $ptuExcento;

            $nominasEmpleados[] = array(
              'id_empleado' => $empleadoId,
              'id_empresa' => $empresaId,
              'anio' => $fechasSemana['anio'],
              'semana' => $datos['numSemana'],
              'fecha_inicio' => $fechasSemana['fecha_inicio'],
              'fecha_final' => $fechasSemana['fecha_final'],
              'dias_trabajados' => $empleadoNomina[0]->dias_trabajados,
              'salario_diario' => $empleadoNomina[0]->salario_diario,
              'salario_integral' => $empleadoNomina[0]->nomina->salario_diario_integrado,
              // 'subsidio' => $datos['subsidio'],
              // 'sueldo_semanal' => $empleadoNomina[0]->nomina->percepciones['sueldo']['ImporteGravado'],
              // 'bonos' => $empleadoNomina[0]->bonos,
              // 'otros' => $empleadoNomina[0]->otros,
              // 'subsidio_pagado' => 0,
              // 'vacaciones' => $vacaciones,
              // 'prima_vacacional_grabable' => $primaVacacionalGravable,
              // 'prima_vacacional_exento' => $primaVacacionalExcento,
              // 'prima_vacacional' => $primaVacacional,
              // 'aguinaldo_grabable' => $aguinaldoGravable,
              // 'aguinaldo_exento' => $aguinaldoExcento,
              // 'aguinaldo' => $aguinaldo,
              'total_percepcion' => $valorUnitario,
              // 'imss' => $imss,
              // 'vejez' => 0,
              'isr' => $datos['isr'],
              // 'infonavit' => $infonavit,
              // 'subsidio_cobrado' => 0,
              // 'prestamos' => $totalPrestamos,
              'total_deduccion' => $descuento + $isr,
              'total_neto' => $valorUnitario - $descuento - $isr,
              'id_empleado_creador' => $this->session->userdata('id_usuario'),
              'ptu_exento' => $ptuExcento,
              'ptu_grabable' => $ptuGravado,
              'ptu' => $ptu,
              'id_puesto' => $empleadoNomina[0]->id_puesto,
              // 'salario_real' => $empleadoNomina[0]->salario_diario_real,
              // 'sueldo_real' => $empleadoNomina[0]->salario_diario_real * $empleadoNomina[0]->dias_trabajados,
              // 'total_no_fiscal' => $totalNoFiscal,
              // 'horas_extras' => $empleadoNomina[0]->horas_extras_dinero,
              // 'horas_extras_grabable' => $empleadoNomina[0]->nomina->percepciones['horas_extras']['ImporteGravado'],
              // 'horas_extras_excento' => $empleadoNomina[0]->nomina->percepciones['horas_extras']['ImporteExcento'],
              // 'descuento_playeras' => $datos['descuento_playeras'],
              // 'descuento_otros' => $datos['descuento_otros'],
              'xml' => $result['xml'],
              'uuid' => $result['uuid'],
              'utilidad_empresa' => $empleadoNomina[0]->utilidad_empresa,
              // 'domingo' => $empleadoNomina[0]->domingo,
              'esta_asegurado' => $datos['esta_asegurado'],
            );
          }
          else
          {
            $errorTimbrar = true;
          }

          // echo "<pre>";
          //   var_dump($datosXML, $archivo);
          // echo "</pre>";exit;

          // echo "<pre>";
          //   var_dump($empleado, $cadenaOriginal, $sello, $certificado);
          // echo "</pre>";exit;
        }
      }
    // }

    // Inserta las nominas.
    if (count($nominasEmpleados) > 0)
    {
      $this->db->insert_batch('nomina_ptu', $nominasEmpleados);
    }

    return array('errorTimbrar' => $errorTimbrar, 'empleadoId' => $empleadoId, 'ultimoNoGenerado' => $datos['ultimo_no_generado']);
  }

  public function cancelaPtu($idEmpleado, $anio, $semana, $idEmpresa)
  {
    $this->load->library('cfdi');
    $this->load->library('facturartebarato_api');
    $this->load->model('documentos_model');

    // Obtenemos la info de la factura a cancelar.
    $query = $this->db->query("SELECT nf.uuid, e.rfc, e.id_empresa, e.nombre_fiscal
                               FROM nomina_ptu AS nf
                                INNER JOIN empresas AS e ON e.id_empresa = nf.id_empresa
                               WHERE nf.id_empleado = {$idEmpleado} AND nf.id_empresa = {$idEmpresa} AND nf.anio = '{$anio}' AND nf.semana = '{$semana}'")->row();

    // Carga los datos fiscales de la empresa dentro de la lib CFDI.
    $this->cfdi->cargaDatosFiscales($query->id_empresa);

    // Parametros que necesita el webservice para la cancelacion.
    $params = array(
      'rfc'   => $query->rfc,
      'uuids' => $query->uuid,
      'cer'   => $this->cfdi->obtenCer(),
      'key'   => $this->cfdi->obtenKey(),
    );

    // Lama el metodo cancelar para que realiza la peticion al webservice.
    $result = $this->facturartebarato_api->cancelar($params);

    if ($result->data->status_uuid === '201' || $result->data->status_uuid === '202')
    {
      $this->db->delete('nomina_ptu', "id_empleado = {$idEmpleado} AND id_empresa = {$idEmpresa} AND anio = '{$anio}' AND semana = '{$semana}'");

      $query = $this->db->query("SELECT Count(*) AS num
                               FROM nomina_ptu AS nf
                               WHERE nf.id_empresa = {$idEmpresa} AND nf.anio = '{$anio}' AND nf.semana = '{$semana}'")->row();
      if($query->num == 0)
        $this->db->delete('nomina_fiscal_guardadas', "tipo = 'pt' AND id_empresa = {$idEmpresa} AND anio = '{$anio}' AND semana = '{$semana}'");
    }

    return array('msg' => $result->data->status_uuid, 'empresa' => $query->nombre_fiscal);
  }

  public function pdfReciboNominaFiscalPtu($empleadoId, $semana, $anio, $empresaId, $pdf=null)
  {
    $this->load->model('empresas_model');

    if ($empresaId !== '')
      $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $empresaId)->get()->row()->dia_inicia_semana;
    else
      $dia = '4';

    $semana = $this->fechasDeUnaSemana($semana, $anio, $dia);
    $_GET['cid_empresa'] = $empresaId; //para las cuentas del contpaq
    $configuraciones = $this->configuraciones();
    $filtros = array('semana' => $semana['semana'], 'empresaId' => $empresaId,
            'dia_inicia_semana' => $dia, 'anio' => $semana['anio'], 'asegurado'  => true);
    $empleados = $this->nomina($configuraciones, $filtros, $empleadoId, null, null, null, null, null, null, 'ptu');
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);

    // echo "<pre>";
    //   var_dump($empleados);
    // echo "</pre>";exit;

    include_once(APPPATH.'libraries/phpqrcode/qrlib.php');

    $nomina = $this->db->query("SELECT uuid, xml FROM nomina_ptu WHERE id_empleado = {$empleadoId} AND id_empresa = {$empresaId} AND anio = {$semana['anio']} AND semana = {$semana['semana']}")->row();

    $xml = simplexml_load_string(str_replace(array('cfdi:', 'tfd:', 'nomina:'), '', $nomina->xml));

    // echo "<pre>";
    //   var_dump($nomina, $xml);
    // echo "</pre>";exit;

    $show = false;
    if ($pdf == null)
    {
      $show = true;
      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
    }

    $pdf->show_head = true;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->logo = $empresa['info']->logo;
    // $pdf->titulo2 = "Recibo de PTU de {$semana['fecha_inicio']} al {$semana['fecha_final']}";
    $pdf->titulo2 = "Recibo de PTU";
    $pdf->titulo3 = "Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $total_gral = array( 'ptu' => 0, 'isr' => 0, 'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0);
    $total_dep = array( 'ptu' => 0, 'isr' => 0, 'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0);

    $dep_tiene_empleados = true;
    $y = $pdf->GetY();
    foreach ($empleados as $key => $empleado)
    {
      if($dep_tiene_empleados)
      {
        $pdf->SetFont('Helvetica','', 10);
        $pdf->SetXY(6, $pdf->GetY() + 4);
        $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
        $pdf->Row(array('', 'Percepción', 'Importe', '', 'Deducción', 'Importe'), false, false, null, 2, 1);

        $pdf->SetFont('Helvetica','', 10);
        $pdf->SetXY(6, $pdf->GetY() - 2);
        $pdf->Cell(200, 2, "________________________________________________________________________________________________________", 0, 0, 'L', 0);
        $dep_tiene_empleados = false;
      }

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY() + 4);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(15, 100));
      $pdf->Row(array($empleado->id, $empleado->nombre), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetFont('Helvetica','', 9);
      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(50, 70, 50));
      $pdf->Row(array($empleado->puesto, "RFC: {$empleado->rfc}", "Afiliciación IMSS: {$empleado->no_seguro}"), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(50, 35, 35, 35, 30));
      $pdf->Row(array("Fecha Ingr: {$empleado->fecha_entrada}", "Sal. diario: {$empleado->salario_diario}", "S.D.I: {$empleado->nomina->salario_diario_integrado}", "S.B.C: {$empleado->nomina->salario_diario_integrado}", 'Cotiza fijo'), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $horasExtras = 0;
      if ($empleado->horas_extras_dinero > 0)
      {
        $pagoXHora = $empleado->salario_diario / 8;
        $horasExtras = $empleado->horas_extras_dinero / $pagoXHora;
      }

      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(70));
      $pdf->Row(array("CURP: {$empleado->curp}"), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $y2 = $pdf->GetY();

      // Percepciones
      $percepciones = $empleado->nomina->percepciones;

      // PTU
      if ($empleado->nomina_fiscal_ptu > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'PTU', String::formatoNumero($empleado->nomina_fiscal_ptu, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['ptu'] += $empleado->nomina_fiscal_ptu;
        $total_gral['ptu'] += $empleado->nomina_fiscal_ptu;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      $y = $pdf->GetY();

      // Deducciones
      $deducciones = $empleado->nomina->deducciones;
      $pdf->SetFont('Helvetica','', 9);

      $pdf->SetY($y2);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y = $pdf->GetY();
      }

      if ($empleado->nomina_fiscal_ptu_isr > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'ISR', String::formatoNumero($empleado->nomina_fiscal_ptu_isr, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['isr'] += $empleado->nomina_fiscal_ptu_isr;
        $total_gral['isr'] += $empleado->nomina_fiscal_ptu_isr;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      if ($y < $pdf->GetY())
      {
        $y = $pdf->GetY();
      }

      // Total percepciones y deducciones
      $pdf->SetXY(6, $y + 2);
      $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));

      $total_dep['total_percepcion'] += $empleado->nomina_fiscal_ptu_total_percepciones;
      $total_gral['total_percepcion'] += $empleado->nomina_fiscal_ptu_total_percepciones;
      $total_dep['total_deduccion'] += $empleado->nomina_fiscal_ptu_total_deducciones;
      $total_gral['total_deduccion'] += $empleado->nomina_fiscal_ptu_total_deducciones;
      $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($empleado->nomina_fiscal_ptu_total_percepciones, 2, '$', false), '', 'Total Deducciones', String::formatoNumero($empleado->nomina_fiscal_ptu_total_deducciones, 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $total_dep['total_neto'] += $empleado->nomina_fiscal_ptu_total_neto;
      $total_gral['total_neto'] += $empleado->nomina_fiscal_ptu_total_neto;
      $pdf->Row(array('', 'Total Neto', String::formatoNumero($empleado->nomina_fiscal_ptu_total_neto, 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica', '', 9);
      $pdf->SetXY(120, $pdf->GetY()+3);
      $pdf->Cell(200, 2, "--------------------------------------------------------------------------------------", 0, 0, 'L', 0);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
    }

    if($xml === false)
      true;
    else
    {
      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY()+4);
      $pdf->Cell(78, 4, 'RFC EMISOR: '.$xml->Emisor[0]['rfc'], 0, 0, 'L', 0);

      $pdf->SetXY(86, $pdf->GetY());
      $pdf->Cell(78, 4, 'Forma de Pago: '.$xml[0]['formaDePago'], 0, 0, 'L', 0);

      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(158, $pdf->GetY());
      $pdf->Cell(78, 4, 'Condicion de Pago: Contado', 0, 0, 'L', 0);

      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(86, $pdf->GetY()+5);
      $pdf->Cell(78, 4, "Metodo de Pago: {$xml[0]['metodoDePago']}", 0, 0, 'L', 0);

      $cuenta_banco = substr($empleado->cuenta_banco, -4);
      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(158, $pdf->GetY());
      $pdf->Cell(76, 4, "Cuenta de Pago: {$cuenta_banco}", 0, 0, 'L', 0);
      ////////////////////
      // Timbrado Datos //
      ////////////////////
      if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
          $pdf->AddPage();

      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(10, $pdf->GetY() + 5);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array('Sello Digital del CFDI:'), false, 0);

      $pdf->SetFont('helvetica', '', 8);
      $pdf->SetY($pdf->GetY() - 3);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array($xml->Complemento->TimbreFiscalDigital[0]['selloCFD']), false, 0);

      if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
          $pdf->AddPage();

      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(10, $pdf->GetY() - 2);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array('Sello Digital del SAT:'), false, 0);

      $pdf->SetFont('helvetica', '', 8);
      $pdf->SetY($pdf->GetY() - 3);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array($xml->Complemento->TimbreFiscalDigital[0]['selloSAT']), false, 0);

      /////////////
      // QR CODE //
      /////////////

      // formato
      // ?re=XAXX010101000&rr=XAXX010101000&tt=1234567890.123456&id=ad662d33-6934-459c-a128-BDf0393f0f44
      // 0000001213.520000

      $total = explode('.', $xml[0]['total']);

      // Obtiene la diferencia de caracteres en la parte entera.
      $diff = 10 - strlen($total[0]);

      // Agrega los 0 faltantes  a la parte entera.
      for ($i=0; $i < $diff; $i++)
        $total[0] = "0{$total[0]}";

      // Si el total no contiene decimales le asigna en la parte decimal 6 ceros.
      if (count($total) === 1)
      {
        $total[1] = '000000';
      }
      else
      {
        // Obtiene la diferencia de caracteres en la parte decimal.
        $diff = 6 - strlen($total[1]);

        // Agregar los 0 restantes en la parte decimal.
        for ($i=0; $i < $diff; $i++)
          $total[1] = "{$total[1]}0";
      }

      $code = "?re={$xml->Emisor[0]['rfc']}";
      $code .= "&rr={$xml->Receptor[0]['rfc']}";
      $code .= "&tt={$total[0]}.{$total[1]}";
      $code .= "&id={$xml->Complemento->TimbreFiscalDigital[0]['UUID']}";

      // echo "<pre>";
      //   var_dump($code, $total, $diff);
      // echo "</pre>";exit;

      QRcode::png($code, APPPATH.'media/qrtemp.png', 'H', 3);

      if($pdf->GetY() + 50 >= $pdf->limiteY) //salta de pagina si exede el max
          $pdf->AddPage();

      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Image(APPPATH.'media/qrtemp.png', null, null, 40);

      // Elimina el QR generado temporalmente.
      unlink(APPPATH.'media/qrtemp.png');

      ////////////////////
      // Timbrado Datos //
      ////////////////////

      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(45, $pdf->GetY() - 39);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(160));
      $pdf->Row(array('Cadena Original del complemento de certificación digital del SAT:'), false, 0);

      $pdf->SetFont('helvetica', '', 8);
      $cadenaOriginalSAT = "||{$xml->Complemento->TimbreFiscalDigital[0]['version']}|{$xml->Complemento->TimbreFiscalDigital[0]['UUID']}|{$xml->Complemento->TimbreFiscalDigital[0]['FechaTimbrado']}|{$xml->Complemento->TimbreFiscalDigital[0]['selloCFD']}|{$xml->Complemento->TimbreFiscalDigital[0]['noCertificadoSAT']}||";
      $pdf->SetXY(45, $pdf->GetY() - 3);
      $pdf->Row(array($cadenaOriginalSAT), false, 0);

      $pdf->SetFont('helvetica', 'B', 10);
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 171, 72);
      $pdf->SetXY(45, $pdf->GetY() + 1);
      $pdf->Cell(68, 6, "Folio Fiscal:", 0, 0, 'R', 1);

      $pdf->SetXY(125, $pdf->GetY());
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['UUID'], 0, 0, 'C', 0);

      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 171, 72);
      $pdf->SetXY(45, $pdf->GetY() + 7);
      $pdf->Cell(68, 6, "No de Serie del Certificado del SAT:", 0, 0, 'R', 1);

      $pdf->SetXY(125, $pdf->GetY());
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['noCertificadoSAT'], 0, 0, 'C', 0);

      $pdf->SetFont('helvetica', 'B', 10);
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 171, 72);
      $pdf->SetXY(45, $pdf->GetY() + 7);
      $pdf->Cell(68, 6, "Fecha y hora de certificación:", 0, 0, 'R', 1);

      $pdf->SetXY(125, $pdf->GetY());
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['FechaTimbrado'], 0, 0, 'C', 0);

      $pdf->SetXY(0, $pdf->GetY()+13);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->Cell(220, 6, 'ESTE DOCUMENTO ES UNA REPRESENTACION IMPRESA DE UN CFDI.', 0, 0, 'C', 0);
    }

    if($show)
      $pdf->Output('PTU.pdf', 'I');
  }

  public function pdfRecibNominPtu($semana, $anio, $empresaId)
  {
    $this->load->library('mypdf');
    $this->load->model('empresas_model');

    if ($empresaId !== '')
      $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $empresaId)->get()->row()->dia_inicia_semana;
    else
      $dia = '4';
    $semana = $this->fechasDeUnaSemana($semana, $anio, $dia);
    $_GET['cid_empresa'] = $empresaId; //para las cuentas del contpaq
    $configuraciones = $this->configuraciones();
    $filtros = array('semana' => $semana['semana'], 'empresaId' => $empresaId, 'dia_inicia_semana' => $dia, 'anio' => $semana['anio']);
    $empleados = $this->nomina($configuraciones, $filtros, null, null, null, null, null, null, null, 'ptu');

    $pdf = new MYpdf('P', 'mm', 'Letter');

    foreach ($empleados as $key => $value)
    {
      if ($value->esta_asegurado == 't')
        $this->pdfReciboNominaFiscalPtu($value->id, $semana['semana'], $anio, $empresaId, $pdf);
    }

    $pdf->Output('PTU.pdf', 'I');
  }

  public function pdfNominaFiscalPtu($semana, $empresaId, $anio=null, $diaComienza=4)
  {
    $anio = $anio==null? date("Y"): $anio;
    $this->load->model('empresas_model');
    $this->load->model('usuarios_departamentos_model');

    $semana = $this->fechasDeUnaSemana($semana, $anio, $diaComienza);
    $_GET['cid_empresa'] = $empresaId; //para las cuentas del contpaq
    $configuraciones = $this->configuraciones();
    $filtros = array('semana' => $semana['semana'], 'empresaId' => $empresaId, 'asegurado' => 'si', 'ordenar' => "ORDER BY u.id ASC", 'anio' => $semana['anio']);
    $empleados = $this->nomina($configuraciones, $filtros, null, null, null, null, null, null, null, 'ptu');
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);
    $finiquitos = $this->db->query("SELECT * FROM usuarios AS u INNER JOIN finiquito AS f ON u.id = f.id_empleado
      WHERE f.fecha_salida BETWEEN '{$semana['fecha_inicio']}' AND '{$semana['fecha_final']}'")->result();

    // echo "<pre>";
    //   var_dump($empleados);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    // $pdf->titulo2 = "Lista de Raya de {$semana['fecha_inicio']} al {$semana['fecha_final']}";
    $pdf->titulo2 = "PTU - Reparto de Utilidades";
    // $pdf->titulo3 = "Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $pdf->SetFont('Helvetica','', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(6, 27);
    $pdf->Cell(100, 6, "Reg. Pat. IMSS: A4914083100", 0, 0, 'L', 0);

    $pdf->SetFont('Helvetica','B', 10);
    $pdf->SetXY(6, $pdf->GetY() + 6);
    $pdf->Cell(100, 6, "ADMINISTRACION Reg. Pat. IMSS: A4914083100", 0, 0, 'L', 0);

    $total_gral = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0,
      'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'isr' => 0,
      'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0);

    // $departamentos = $this->usuarios_model->departamentos();
    $_GET['did_empresa'] = $empresaId;
    $departamentos = $this->usuarios_departamentos_model->getPuestos(false)['puestos'];

    foreach ($departamentos as $keyd => $departamento)
    {
      $total_dep = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0,
        'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'isr' => 0,
        'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0);

      $dep_tiene_empleados = true;
      $y = $pdf->GetY();
      foreach ($empleados as $key => $empleado)
      {
        if($departamento->id_departamento == $empleado->id_departamente)
        {
          if($dep_tiene_empleados)
          {
            $pdf->SetFont('Helvetica','B', 10);
            $pdf->SetXY(6, $pdf->GetY()+6);
            $pdf->Cell(130, 6, $departamento->nombre, 0, 0, 'L', 0);

            $pdf->SetFont('Helvetica','', 10);
            $pdf->SetXY(6, $pdf->GetY() + 8);
            $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
            $pdf->Row(array('', 'Percepción', 'Importe', '', 'Deducción', 'Importe'), false, false, null, 2, 1);

            $pdf->SetFont('Helvetica','', 10);
            $pdf->SetXY(6, $pdf->GetY() - 2);
            $pdf->Cell(200, 2, "________________________________________________________________________________________________________", 0, 0, 'L', 0);
            $dep_tiene_empleados = false;
          }

          $pdf->SetFont('Helvetica','B', 9);
          $pdf->SetXY(6, $pdf->GetY() + 4);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(15, 100));
          $pdf->Row(array($empleado->id, $empleado->nombre), false, false, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetFont('Helvetica','', 9);
          $pdf->SetXY(6, $pdf->GetY() + 0);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(50, 70, 50));
          $pdf->Row(array($empleado->puesto, "RFC: {$empleado->rfc}", "Afiliciación IMSS: {$empleado->no_seguro}"), false, false, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetXY(6, $pdf->GetY() + 0);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(50, 35, 35, 35, 30));
          $pdf->Row(array("Fecha Ingr: {$empleado->fecha_entrada}", "Sal. diario: {$empleado->salario_diario}", "S.D.I: {$empleado->nomina->salario_diario_integrado}", "S.B.C: {$empleado->nomina->salario_diario_integrado}", 'Cotiza fijo'), false, false, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $horasExtras = 0;
          if ($empleado->horas_extras_dinero > 0)
          {
            $pagoXHora = $empleado->salario_diario / 8;
            $horasExtras = $empleado->horas_extras_dinero / $pagoXHora;
          }

          $pdf->SetXY(6, $pdf->GetY() + 0);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(70));
          // $pdf->Row(array("Dias Pagados: {$empleado->dias_trabajados}", "Tot Hrs trab: " . $empleado->dias_trabajados * 8, 'Hrs dia: 8.00', "Hrs extras: " . number_format($horasExtras, 2), "CURP: {$empleado->curp}"), false, false, null, 1, 1);
          $pdf->Row(array("CURP: {$empleado->curp}"), false, false, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $y2 = $pdf->GetY();

          // Percepciones
          $percepciones = $empleado->nomina->percepciones;

          // PTU
          if ($empleado->nomina_fiscal_ptu > 0)
          {
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'PTU', String::formatoNumero($empleado->nomina_fiscal_ptu, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['ptu'] += $empleado->nomina_fiscal_ptu;
            $total_gral['ptu'] += $empleado->nomina_fiscal_ptu;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y2 = $pdf->GetY();
            }
          }

          $y = $pdf->GetY();

          // Deducciones
          $deducciones = $empleado->nomina->deducciones;
          $pdf->SetFont('Helvetica','', 9);

          $pdf->SetY($y2);

          if ($empleado->nomina_fiscal_ptu_isr > 0)
          {
            $pdf->SetXY(108, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'ISR', String::formatoNumero($empleado->nomina_fiscal_ptu_isr, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['isr'] += $empleado->nomina_fiscal_ptu_isr;
            $total_gral['isr'] += $empleado->nomina_fiscal_ptu_isr;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y = $pdf->GetY();
            }
          }

          if ($y < $pdf->GetY())
          {
            $y = $pdf->GetY();
          }

          // Total percepciones y deducciones
          $pdf->SetXY(6, $y + 2);
          $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
          $total_dep['total_percepcion'] += $empleado->nomina_fiscal_ptu_total_percepciones;
          $total_gral['total_percepcion'] += $empleado->nomina_fiscal_ptu_total_percepciones;
          $total_dep['total_deduccion'] += $empleado->nomina_fiscal_ptu_total_deducciones;
          $total_gral['total_deduccion'] += $empleado->nomina_fiscal_ptu_total_deducciones;
          $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($empleado->nomina_fiscal_ptu_total_percepciones, 2, '$', false), '', 'Total Deducciones', String::formatoNumero($empleado->nomina_fiscal_ptu_total_deducciones, 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
              $pdf->AddPage();

          $pdf->SetFont('Helvetica','B', 9);
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $total_dep['total_neto'] += $empleado->nomina_fiscal_ptu_total_neto;
          $total_gral['total_neto'] += $empleado->nomina_fiscal_ptu_total_neto;
          $pdf->Row(array('', 'Total Neto', String::formatoNumero($empleado->nomina_fiscal_ptu_total_neto, 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
              $pdf->AddPage();

          $pdf->SetFont('Helvetica', '', 9);
          $pdf->SetXY(120, $pdf->GetY()+3);
          $pdf->Cell(200, 2, "--------------------------------------------------------------------------------------", 0, 0, 'L', 0);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();
        }
      }

      //****** Total departamento ******
      if($dep_tiene_empleados == false)
      {
        if($pdf->GetY()+10 >= $pdf->limiteY)
          $pdf->AddPage();
        $pdf->SetFont('Helvetica','B', 10);
        $pdf->SetXY(6, $pdf->GetY()+2);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(200));
        $pdf->Row(array("Total Departamento {$departamento->nombre}"), false, 0, null, 1, 1);
        $pdf->Row(array("____________________________________________________________________________________________________"), false, 0, null, 1, 1);

        $pdf->SetFont('Helvetica','', 9);
        $y2 = $pdf->GetY();

        // PTU
        if ($total_dep['ptu'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'PTU', String::formatoNumero($total_dep['ptu'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        $y = $pdf->GetY();

        // Deducciones
        $deducciones = $empleado->nomina->deducciones;
        $pdf->SetFont('Helvetica','', 9);

        $pdf->SetY($y2);
        if ($total_dep['isr'] > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'ISR', String::formatoNumero($total_dep['isr'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        if ($y < $pdf->GetY())
        {
          $y = $pdf->GetY();
        }

        // Total percepciones y deducciones
        $pdf->SetXY(6, $y + 2);
        $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
        $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($total_dep['total_percepcion'], 2, '$', false), '', 'Total Deducciones', String::formatoNumero($total_dep['total_deduccion'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

        $pdf->SetFont('Helvetica','B', 9);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Total Neto', String::formatoNumero($total_dep['total_neto'], 2, '$', false)), false, 0, null, 1, 1);
      }

      $pdf->SetFont('Helvetica','', 10);
    }

    //********* Total general ***************
    if($pdf->GetY()+10 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Helvetica','B', 10);
    $pdf->SetXY(6, $pdf->GetY()+2);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array("Total General"), false, 0, null, 1, 1);
    $pdf->Row(array("____________________________________________________________________________________________________"), false, 0, null, 1, 1);

    $pdf->SetFont('Helvetica','', 9);
    $y2 = $pdf->GetY();
    // Sueldo
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25));
    $pdf->Row(array('', 'Sueldo', String::formatoNumero($total_gral['sueldo'], 2, '$', false)), false, 0, null, 1, 1);
    if($pdf->GetY() >= $pdf->limiteY)
    {
      $pdf->AddPage();
      $y2 = $pdf->GetY();
    }

    // PTU
    if ($total_gral['ptu'] > 0)
    {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'PTU', String::formatoNumero($total_gral['ptu'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }
    }

    $y = $pdf->GetY();

    // Deducciones
    // $deducciones = $empleado->nomina->deducciones;
    $pdf->SetFont('Helvetica','', 9);

    $pdf->SetY($y2);
    if ($total_gral['isr'] > 0)
    {
      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'ISR', String::formatoNumero($total_gral['isr'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y = $pdf->GetY();
      }
    }

    if ($y < $pdf->GetY())
    {
      $y = $pdf->GetY();
    }

    // Total percepciones y deducciones
    $pdf->SetXY(6, $y + 2);
    $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
    $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($total_gral['total_percepcion'], 2, '$', false), '', 'Total Deducciones', String::formatoNumero($total_gral['total_deduccion'], 2, '$', false)), false, 0, null, 1, 1);
    if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

    $pdf->SetFont('Helvetica','B', 9);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25));
    $pdf->Row(array('', 'Total Neto', String::formatoNumero($total_gral['total_neto'], 2, '$', false)), false, 0, null, 1, 1);

    $pdf->Output('PTU.pdf', 'I');
  }

  public function pdfRptNominaPtu($semana, $empresaId, $anio)
  {
    // var_dump($_POST);
    // exit();
    // $empleados = $this->pdfRptDataNominaFiscal($_POST, $empresaId);
    $this->load->model('usuarios_model');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_departamentos_model');
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);
    $semana = $this->fechasDeUnaSemana($semana, $anio, $empresa['info']->dia_inicia_semana);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('L', 'mm', 'Letter');
    $pdf->show_head = true;
    $pdf->logo = $empresa['info']->logo;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = "PTU - Reparto de Utilidades";
    $pdf->titulo3 = "Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $columnas = array('n' => array(), 'w' => array(6, 70, 20, 20, 20, 20, 20, 20, 20, 20, 20),
      'a' => array('L', 'L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R'));
    $columnas['n'][] = 'No';
    $columnas['n'][] = 'NOMBRE';
    $columnas['n'][] = 'Días Lab.';
    $columnas['n'][] = 'Fact. X Dia';
    $columnas['n'][] = 'PTU por dias';
    $columnas['n'][] = 'Sueldo anual';
    $columnas['n'][] = 'Fact. De Sal';
    $columnas['n'][] = 'PTU por sal';
    $columnas['n'][] = 'PTU';
    $columnas['n'][] = 'ISR';
    $columnas['n'][] = 'TOTAL A PAGAR';

    $pdf->SetFont('Helvetica','B', 8);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns($columnas['a']);
    $pdf->SetWidths($columnas['w']);
    $pdf->Row($columnas['n'], false, false, null, 2, 1);

    // $ttotal_aseg_no_trs = $sueldo_semanal_real = $otras_percepciones = $domingo = $total_prestamos = $total_infonavit = $descuento_playeras = $descuento_otros = $ttotal_pagar = $ttotal_nomina = $total_no_fiscal = 0;
    $ptu = 0;
    $y = $pdf->GetY();

    // $_GET['did_empresa'] = $empresaId;
    $departamentos = $this->usuarios_departamentos_model->getPuestos(false, 'todos')['puestos'];

    $ptuTtotal = 0;
    $isrTtotal = 0;
    $tTotal    = 0;
    $tdiasTotal = 0;
    $ptu_diasTotal = 0;
    $tsueldosTotal = 0;
    $ptu_sueldosTotal = 0;

    $numero_empleado = 0;
    $ptuTotal = 0;
    $isrTotal = 0;
    $tdias = 0;
    $ptu_dias = 0;
    $tsueldos = 0;
    $ptu_sueldos = 0;
    $ttotal_pagar = 0;
    foreach ($departamentos as $keyd => $departamento)
    {
      $ptu = 0;

      if($pdf->GetY()+8 >= $pdf->limiteY){
        $pdf->AddPage();
        $pdf->SetFont('Helvetica','B', 8);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row($columnas['n'], false, false, null, 2, 1);
      }

      $pdf->SetFont('Helvetica','B', 10);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Cell(130, 6, $departamento->nombre, 0, 0, 'L', 0);

      $pdf->SetXY(6, $pdf->GetY()+6);
      foreach ($_POST['empleado_id'] as $key => $empleado)
      {
        if($departamento->id_departamento == $_POST['departamento_id'][$key])
        {
          $numero_empleado++;
          $empleado = $this->usuarios_model->get_usuario_info($empleado, true)['info'][0];

          $pdf->SetFont('Helvetica','', 8);
          if($pdf->GetY()+8 >= $pdf->limiteY){
            $pdf->AddPage();
            $pdf->SetFont('Helvetica','B', 8);
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->Row($columnas['n'], false, false, null, 2, 1);
          }

          $pdf->SetFont('Helvetica','', 8);

          $total_pagar = $_POST['ptu'][$key];

          $pdf->SetXY(6, $pdf->GetY());

          $dataarr = array();
          $dataarr[] = $numero_empleado;
          $dataarr[] = $empleado->apellido_paterno.' '.$empleado->apellido_materno.' '.$empleado->nombre;

          $dataarr[] = $_POST['ptu_dias_trabajados_empleado'][$key];
          $dataarr[] = $_POST['ptu_empleado_dias_fact'][$key];
          $dataarr[] = String::formatoNumero($_POST['ptu_empleado_dias'][$key], 2, '$', false);
          $dataarr[] = String::formatoNumero($_POST['ptu_percepciones_empleado'][$key], 2, '$', false);
          $dataarr[] = $_POST['ptu_empleado_percepciones_fact'][$key];
          $dataarr[] = String::formatoNumero($_POST['ptu_empleado_percepciones'][$key], 2, '$', false);

          $dataarr[] = String::formatoNumero($_POST['ptu'][$key], 2, '$', false);
          $dataarr[] = String::formatoNumero($_POST['isr'][$key], 2, '$', false);
          $dataarr[] = String::formatoNumero($_POST['ttotal_nomina'][$key], 2, '$', false);

          $pdf->Row($dataarr, false, true, null, 2, 1);

          $ptuTotal     += $_POST['ptu'][$key];
          $isrTotal     += $_POST['isr'][$key];
          $ttotal_pagar += $_POST['ttotal_nomina'][$key];
          $tdias        += $_POST['ptu_dias_trabajados_empleado'][$key];
          $ptu_dias     += $_POST['ptu_empleado_dias'][$key];
          $tsueldos     += $_POST['ptu_percepciones_empleado'][$key];
          $ptu_sueldos  += $_POST['ptu_empleado_percepciones'][$key];
        }
      }

      if($pdf->GetY()+10 >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetFont('Helvetica','B', 8);
      $pdf->SetXY(6, $pdf->GetY());
      $datatto = array();
      $datatto[] = '';
      $datatto[] = 'TOTAL';
      $datatto[] = $tdias;
      $datatto[] = '';
      $datatto[] = String::formatoNumero($ptu_dias, 2, '$', false);
      $datatto[] = String::formatoNumero($tsueldos, 2, '$', false);
      $datatto[] = '';
      $datatto[] = String::formatoNumero($ptu_sueldos, 2, '$', false);
      $datatto[] = String::formatoNumero($ptuTotal, 2, '$', false);
      $datatto[] = String::formatoNumero($isrTotal, 2, '$', false);
      $datatto[] = String::formatoNumero($ttotal_pagar, 2, '$', false);
      $pdf->Row($datatto, false, true, null, 2, 1);

      $ptuTtotal        += $ptuTotal;
      $isrTtotal        += $isrTotal;
      $tdiasTotal       += $tdias;
      $ptu_diasTotal    += $ptu_dias;
      $tsueldosTotal    += $tsueldos;
      $ptu_sueldosTotal += $ptu_sueldos;

      $ptuTotal     = 0;
      $isrTotal     = 0;
      $ttotal_pagar = 0;
      $tdias = 0;
      $ptu_dias = 0;
      $tsueldos = 0;
      $ptu_sueldos = 0;
    }

    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetFont('Helvetica','B', 8);
    if($pdf->GetY()+8 >= $pdf->limiteY)
      $pdf->AddPage();
    $datatto = array();
    $datatto[] = '';
    $datatto[] = 'TOTAL';
    $datatto[] = $tdiasTotal;
    $datatto[] = '';
    $datatto[] = String::formatoNumero($ptu_diasTotal, 2, '$', false);
    $datatto[] = String::formatoNumero($tsueldosTotal, 2, '$', false);
    $datatto[] = '';
    $datatto[] = String::formatoNumero($ptu_sueldosTotal, 2, '$', false);
    $datatto[] = String::formatoNumero($ptuTtotal, 2, '$', false);
    $datatto[] = String::formatoNumero($isrTtotal, 2, '$', false);
    $datatto[] = String::formatoNumero($ptuTtotal - $isrTtotal, 2, '$', false);
    $pdf->Row($datatto, false, true, null, 2, 1);

    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetFont('Helvetica','B', 8);
      if($pdf->GetY()+8 >= $pdf->limiteY)
        $pdf->AddPage();
    $pdf->SetAligns(array('L', 'L', 'L'));
    $pdf->SetWidths(array(50, 50, 50));

    // if ($empresa['info']->rfc === 'ESJ97052763A')
    // {
    //   $pdf->Row(array(
    //     'NOMINA FISCAL: '.String::formatoNumero($ptuTotal, 2, '$', false),
    //     'TRANSFERIDO: '.String::formatoNumero($ptuTotal, 2, '$', false),
    //     'CHEQUE FISCAL: '.String::formatoNumero(($ptuTotal), 2, '$', false),
    //     ), false, true, null, 2, 1);
    // }

    $pdf->Output('Nomina.pdf', 'I');
  }

  public function xlsRptNominaPtu($semana, $empresaId, $anio)
  {
    $this->load->model('usuarios_model');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_departamentos_model');
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);
    $semana = $this->fechasDeUnaSemana($semana, $anio, $empresa['info']->dia_inicia_semana);
    // $finiquitos = $this->db->query("SELECT * FROM usuarios AS u INNER JOIN finiquito AS f ON u.id = f.id_empleado
    //   WHERE f.fecha_salida BETWEEN '{$semana['fecha_inicio']}' AND '{$semana['fecha_final']}'")->result();

    $html = '<table>';
    $html .= $this->rowXls( array($empresa['info']->nombre_fiscal) );
    $html .= $this->rowXls( array("PTU - Reparto de Utilidades") );
    $html .= $this->rowXls( array("Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}") );
    $html .= $this->rowXls( array() );

    $columnas = array('n' => array(), 'w' => array(5, 64, 20, 20, 20, 20, 20, 20, 20, 20), 'a' => array('L', 'L', 'R', 'R', 'R', 'R', 'R', 'R', 'R', 'R'));
    $columnas['n'][] = 'No';
    $columnas['n'][] = 'NOMBRE';
    $columnas['n'][] = 'Días Lab.';
    $columnas['n'][] = 'Fact. X Dia';
    $columnas['n'][] = 'PTU por dias';
    $columnas['n'][] = 'Sueldo anual';
    $columnas['n'][] = 'Fact. De Sal';
    $columnas['n'][] = 'PTU por sal';
    $columnas['n'][] = 'PTU';
    $columnas['n'][] = 'ISR';
    $columnas['n'][] = 'TOTAL A PAGAR';

    $ptu = 0;

    // $departamentos = $this->usuarios_model->departamentos();
    // $_GET['did_empresa'] = $empresaId;
    $departamentos = $this->usuarios_departamentos_model->getPuestos(false, 'todos')['puestos'];

    $numero_empleado = 0;
    $html .= $this->rowXls($columnas['n']);
    $html .= $this->rowXls(array(''));

    $ptuTtotal = 0;
    $isrTtotal = 0;
    $tTotal    = 0;
    $tdiasTotal = 0;
    $ptu_diasTotal = 0;
    $tsueldosTotal = 0;
    $ptu_sueldosTotal = 0;

    $ptuTotal = 0;
    $isrTotal = 0;
    $tdias = 0;
    $ptu_dias = 0;
    $tsueldos = 0;
    $ptu_sueldos = 0;
    $ttotal_pagar = 0;

    foreach ($departamentos as $keyd => $departamento)
    {
      $ptu = 0;

      // $sueldo_semanal_real1 = $otras_percepciones1 = $domingo1 = $total_prestamos1 = $total_infonavit1 = $descuento_playeras1 = $descuento_otros1 = $ttotal_pagar1 = $ttotal_nomina1 = $total_no_fiscal1 = 0;

      $html .= '<tr><td style="font-size:14px;">'.$departamento->nombre.'</td></tr>';

      // $pdf->SetXY(6, $pdf->GetY()+6);
      foreach ($_POST['empleado_id'] as $key => $empleado)
      {
        if($departamento->id_departamento == $_POST['departamento_id'][$key])
        {
          $numero_empleado++;
          $empleado = $this->usuarios_model->get_usuario_info($empleado, true)['info'][0];
          $total_pagar = $_POST['ptu'][$key];

          $dataarr = array();
          $dataarr[] = $numero_empleado;
          $dataarr[] = $empleado->apellido_paterno.' '.$empleado->apellido_materno.' '.$empleado->nombre;
          $dataarr[] = $_POST['ptu_dias_trabajados_empleado'][$key];
          $dataarr[] = $_POST['ptu_empleado_dias_fact'][$key];
          $dataarr[] = String::formatoNumero($_POST['ptu_empleado_dias'][$key], 2, '$', false);
          $dataarr[] = String::formatoNumero($_POST['ptu_percepciones_empleado'][$key], 2, '$', false);
          $dataarr[] = $_POST['ptu_empleado_percepciones_fact'][$key];
          $dataarr[] = String::formatoNumero($_POST['ptu_empleado_percepciones'][$key], 2, '$', false);
          $dataarr[] = String::formatoNumero($_POST['ptu'][$key], 2, '', false);
          $dataarr[] = String::formatoNumero($_POST['isr'][$key], 2, '', false);
          $dataarr[] = String::formatoNumero($_POST['ttotal_nomina'][$key], 2, '$', false);

          $html .= $this->rowXls($dataarr);
          $ptuTotal     += $_POST['ptu'][$key];
          $isrTotal     += $_POST['isr'][$key];
          $ttotal_pagar += $_POST['ttotal_nomina'][$key];
          $tdias        += $_POST['ptu_dias_trabajados_empleado'][$key];
          $ptu_dias     += $_POST['ptu_empleado_dias'][$key];
          $tsueldos     += $_POST['ptu_percepciones_empleado'][$key];
          $ptu_sueldos  += $_POST['ptu_empleado_percepciones'][$key];
        }
      }

      $datatto = array();
      $datatto[] = '';
      $datatto[] = 'TOTAL';
      $datatto[] = $tdias;
      $datatto[] = '';
      $datatto[] = String::formatoNumero($ptu_dias, 2, '$', false);
      $datatto[] = String::formatoNumero($tsueldos, 2, '$', false);
      $datatto[] = '';
      $datatto[] = String::formatoNumero($ptu_sueldos, 2, '$', false);
      $datatto[] = String::formatoNumero($ptuTotal, 2, '', false);
      $datatto[] = String::formatoNumero($isrTotal, 2, '', false);
      $datatto[] = String::formatoNumero($ptuTotal - $isrTotal, 2, '$', false);
      $html .= $this->rowXls($datatto);
      $html .= $this->rowXls(array(''));

      $ptuTtotal += $ptuTotal;
      $isrTtotal += $isrTotal;
      $tdiasTotal       += $tdias;
      $ptu_diasTotal    += $ptu_dias;
      $tsueldosTotal    += $tsueldos;
      $ptu_sueldosTotal += $ptu_sueldos;

      $ptuTotal     = 0;
      $isrTotal     = 0;
      $ttotal_pagar = 0;
      $tdias = 0;
      $ptu_dias = 0;
      $tsueldos = 0;
      $ptu_sueldos = 0;
    }

    $datatto = array();
    $datatto[] = '';
    $datatto[] = 'TOTAL';
    $datatto[] = $tdiasTotal;
    $datatto[] = '';
    $datatto[] = String::formatoNumero($ptu_diasTotal, 2, '$', false);
    $datatto[] = String::formatoNumero($tsueldosTotal, 2, '$', false);
    $datatto[] = '';
    $datatto[] = String::formatoNumero($ptu_sueldosTotal, 2, '$', false);
    $datatto[] = String::formatoNumero($ptuTtotal, 2, '$', false);
    $datatto[] = String::formatoNumero($isrTtotal, 2, '$', false);
    $datatto[] = String::formatoNumero($ptuTtotal - $isrTtotal, 2, '$', false);

    $html .= $this->rowXls($datatto);
    $html .= $this->rowXls(array(''));

    // if ($empresa['info']->rfc === 'ESJ97052763A')
    // {
    //   $html .= $this->rowXls(array(
    //     'NOMINA FISCAL: '.String::formatoNumero($ttotal_aseg_no_trs, 2, '', false),
    //     'TRANSFERIDO: '.String::formatoNumero($ttotal_nomina, 2, '', false),
    //     'CHEQUE FISCAL: '.String::formatoNumero(($ttotal_aseg_no_trs-$ttotal_nomina), 2, '', false),
    //     ), 'style="font-weight:bold;font-size:14px;"');
    //   $html .= $this->rowXls(array(''));
    // }

    //Si es la empresa es gomez gudiño pone la nomina de limon (o ranchos), se obtiene de la bd
    $html .= '</table>';

    header("Content-type: application/vnd.ms-excel; name='excel'");
    header("Content-Disposition: filename=nomina.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo $html;
  }

  public function descargarTxtBancoPtu($semana, $empresaId, $anio=null)
  {
    $anio = $anio==null?date("Y"):$anio;
    $_GET['cid_empresa'] = $empresaId; //para las cuentas del contpaq
    if ($empresaId !== '')
      $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $empresaId)->get()->row()->dia_inicia_semana;
    else
      $dia = '4';

    $configuraciones = $this->configuraciones();
    $semana = $this->fechasDeUnaSemana($semana, $anio, $dia);
    $filtros = array('semana' => $semana['semana'], 'empresaId' => $empresaId,
              'dia_inicia_semana' => $dia, 'anio' => $semana['anio'], 'asegurado' => true);
    $empleados = $this->nomina($configuraciones, $filtros, null, null, null, null, null, null, null, 'ptu');
    // $nombre = "PAGO-{$semana['anio']}-SEM-{$semana['semana']}.txt";
    $nombre = "PAGO-PTU-{$semana['anio']}.txt";

    $content           = array();
    $contentSantr      = array();
    $contador          = 1;
    $contadorSantr     = 1;
    $cuentaSantr       = '92001449876'; // Cuenta cargo santander
    $total_nominaSantr = 0;

    //header santader
    $contentSantr[] = "100001E" . date("mdY") . $this->formatoBanco($cuentaSantr, ' ', 16, 'D') . date("mdY");

    foreach ($empleados as $key => $empleado)
    {
      if($empleado->cuenta_banco != '' && $empleado->esta_asegurado == 't'){
        if($empleado->banco == 'santr') {
          $contentSantr[] = "2" . $this->formatoBanco($contadorSantr+1, '0', 5, 'I') .
                      $this->formatoBanco($contadorSantr, ' ', 7, 'D') .
                      $this->formatoBanco($this->removeTrash($empleado->apellido_paterno), ' ', 30, 'D') .
                      $this->formatoBanco($this->removeTrash($empleado->apellido_materno), ' ', 20, 'D') .
                      $this->formatoBanco($this->removeTrash($empleado->nombre2), ' ', 30, 'D') .
                      $this->formatoBanco($empleado->cuenta_banco, ' ', 16, 'D') .
                      $this->formatoBanco($empleado->nomina_fiscal_ptu_total_neto, '0', 18, 'I', true);
          $contadorSantr++;
          $total_nominaSantr += number_format($empleado->nomina_fiscal_ptu_total_neto, 2, '.', '');
        } else {
          $content[] = $this->formatoBanco($contador, '0', 9, 'I') .
                      $this->formatoBanco(substr($empleado->rfc, 0, 10), ' ', 16, 'D') .
                      $this->formatoBanco('99', ' ', 2, 'I') .
                      $this->formatoBanco($empleado->cuenta_banco, ' ', 20, 'D') .
                      $this->formatoBanco($empleado->nomina_fiscal_ptu_total_neto, '0', 15, 'I', true) .
                      $this->formatoBanco($this->removeTrash($empleado->nombre), ' ', 40, 'D') .
                      $this->formatoBanco('001', ' ', 3, 'D') .
                      $this->formatoBanco('001', ' ', 3, 'D');
          $contador++;
        }
      }
    }

    //footer santader
    $contentSantr[] = "3" . $this->formatoBanco($contadorSantr+1, '0', 5, 'I') . $this->formatoBanco($contadorSantr-1, '0', 5, 'I') .
                      $this->formatoBanco($total_nominaSantr, '0', 18, 'I', true);

    $content[]      = '';
    $contentSantr[] = '';
    $content        = implode("\r\n", $content);
    $contentSantr   = implode("\r\n", $contentSantr);

    $zip = new ZipArchive;
    if ($zip->open(APPPATH."media/temp/{$nombre}.zip", ZIPARCHIVE::CREATE | ZIPARCHIVE::OVERWRITE) === true)
    {
      $zip->addFromString('SANTANDER.txt', $contentSantr);
      $zip->addFromString('BBVA Bancomer.txt', $content);

      $zip->close();
    }
    else
    {
      exit('Error al intentar crear el ZIP.');
    }

    header('Content-Type: application/zip');
    header("Content-disposition: attachment; filename={$nombre}.zip");
    readfile(APPPATH."media/temp/{$nombre}.zip");

    unlink(APPPATH."media/temp/{$nombre}.zip");

    // $fp = fopen(APPPATH."media/temp/{$nombre}", "wb");
    // fwrite($fp,$content);
    // fclose($fp);

    // header('Content-Type: text/plain');
    // header("Content-disposition: attachment; filename={$nombre}");
    // readfile(APPPATH."media/temp/{$nombre}");
    // unlink(APPPATH."media/temp/{$nombre}");
  }

  /*
   |------------------------------------------------------------------------
   | AGUINALDO
   |------------------------------------------------------------------------
   */

  public function add_nominas_aguinaldo($datos, $empresaId, $empleadoId)
  {
    // echo "<pre>";
    //   var_dump($datos, $empresaId, $empleadoId);
    // echo "</pre>";exit;
    // $startTime = new DateTime(date('Y-m-d H:i:s'));

    $this->load->library('cfdi');
    $this->load->library('facturartebarato_api');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_model');

    // Obtiene la informacion de la empresa.
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);

    // Obtiene el certificado.
    $certificado = $this->cfdi->obtenCertificado($this->db
      ->select('cer')
      ->from("empresas")
      ->where("id_empresa", $empresaId)
      ->get()->row()->cer
    );

    // Obtiene las configuraciones.
    $_GET['cid_empresa'] = $empresaId; //para las cuentas del contpaq
    $configuraciones = $this->configuraciones();

    // Almacenara los datos de las nominas de cada empleado para despues
    // insertarlas.
    $nominasEmpleados = array();

    // Almacenara los datos de los prestamos de cada empleado para despues
    // insertarlos.
    $prestamosEmpleados = array();

    // Obtiene el rango de fechas de la semana.
    $fechasSemana = $this->fechasDeUnaSemana($datos['numSemana'], $datos['anio'], $empresa['info']->dia_inicia_semana );

    // Auxiliar para saber si hubo un error al momento de timbrar alguna nomina.
    $errorTimbrar = false;

    // Recorre los empleados para agregar y timbrar sus nominas.
    // foreach ($datos['empleado_id'] as $key => $empleadoId)
    // {
      // Si la nomina del empleado no se ha generado entonces entra.
      if ($datos['generar_nomina'] === '1')
      {
        // $empleado = $this->usuarios_model->get_usuario_info($empleadoId, true);
        $empleado = $this->usuarios_model->get_usuario_info($empleadoId, true);

        $empleadoNomina = $this->nomina(
          $configuraciones,
          array('semana' => $datos['numSemana'], 'empresaId' => $empresaId, 'anio' => $datos['anio'], 'dia_inicia_semana' => $empresa['info']->dia_inicia_semana ),
          $empleadoId,
          null,
          null,
          null,
          null,
          null,
          null,
          'ag'
        );

        $valorUnitario = 0; // Total de las Percepciones.

        // Recorre las percepciones del empleado.
        foreach ($empleadoNomina[0]->nomina->percepciones as $tipoPercepcion => $percepcion)
        {
          // Si activaron las vacaciones entonces suma las vacaciones y la prima vacacional.
          if ($tipoPercepcion === 'aguinaldo')
          {
            $valorUnitario += $percepcion['total'];
            unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]['total']);
          }
          else
          {
            unset($empleadoNomina[0]->nomina->percepciones[$tipoPercepcion]);
          }
        }

        $isr = 0; // retenciones
        $descuento = 0; // Total de las deducciones(gravado + excento) excepto el ISR.
        // Recorre las deducciones del empleado.
        foreach ($empleadoNomina[0]->nomina->deducciones as $tipoDeduccion => $deduccion)
        {
          if ($tipoDeduccion === 'isr')
          {
            $isr = $deduccion['total'];
            unset($empleadoNomina[0]->nomina->deducciones[$tipoDeduccion]['total']);
          }
          else
          {
            unset($empleadoNomina[0]->nomina->deducciones[$tipoDeduccion]);
          }
        }

        $result = array('xml' => '', 'uuid' => '');
        if($datos['esta_asegurado'] == 't')
        {
          // Obtiene los datos para la cadena original.
          $datosCadenaOriginal = $this->datosCadenaOriginal($empleado, $empresa);
          $datosCadenaOriginal['subTotal'] = $valorUnitario;
          $datosCadenaOriginal['descuento'] = $descuento;
          $datosCadenaOriginal['retencion'][0]['importe'] = $isr;
          $datosCadenaOriginal['totalImpuestosRetenidos'] = $isr;
          $datosCadenaOriginal['total'] = round($valorUnitario - $descuento - $isr, 4);

          // Concepto de la nomina.
          $concepto = array(array(
            'cantidad'         => 1,
            'unidad'           => 'Servicio',
            'descripcion'      => 'Aguinaldo',
            'valorUnitario'    => $valorUnitario,
            'importe'          => $valorUnitario,
            'idClasificacion' => null,
          ));

          $datosCadenaOriginal['concepto'] = $concepto;

          // Obtiene la cadena original para la nomina.
          $cadenaOriginal = $this->cfdi->obtenCadenaOriginal($datosCadenaOriginal, true, $empleadoNomina);

          // Genera el sello en base a la cadena original.
          $sello = $this->cfdi->obtenSello($cadenaOriginal['cadenaOriginal']);

          // Construye los datos para el xml.
          $datosXML = $this->datosXml($cadenaOriginal['datos'], $empresa, $empleado, $sello, $certificado);
          $datosXML['concepto'] = $concepto;

          $archivo = $this->cfdi->generaArchivos($datosXML, true, $fechasSemana, null, ' - Aguinaldo');

          $result = $this->timbrar($archivo['pathXML']);
          // echo "<pre>";
          //   var_dump($archivo, $result, base64_encode($result['xml']), $cadenaOriginal);
          // echo "</pre>";exit;

          // Si la nomina se timbro entonces agrega al array nominas la nomina del
          // empleado para despues insertarla en la bdd.
          if (isset($result['result']->status) && $result['result']->status==true)
          {
            $aguinaldoGravado = isset($empleadoNomina[0]->nomina->percepciones['aguinaldo'])
              ? $empleadoNomina[0]->nomina->percepciones['aguinaldo']['ImporteGravado']
              : 0;

            $aguinaldoExcento = isset($empleadoNomina[0]->nomina->percepciones['aguinaldo'])
              ? $empleadoNomina[0]->nomina->percepciones['aguinaldo']['ImporteExcento']
              : 0;

            $aguinaldo = $aguinaldoGravado + $aguinaldoExcento;

            $nominasEmpleados[] = array(
              'id_empleado' => $empleadoId,
              'id_empresa' => $empresaId,
              'anio' => $fechasSemana['anio'],
              'semana' => $datos['numSemana'],
              'fecha_inicio' => $fechasSemana['fecha_inicio'],
              'fecha_final' => $fechasSemana['fecha_final'],
              'dias_trabajados' => $empleadoNomina[0]->dias_trabajados,
              'salario_diario' => $empleadoNomina[0]->salario_diario,
              'salario_integral' => $empleadoNomina[0]->nomina->salario_diario_integrado,
              'aguinaldo_grabable' => $aguinaldoGravado,
              'aguinaldo_exento' => $aguinaldoExcento,
              'aguinaldo' => $aguinaldo,
              'total_percepcion' => $valorUnitario,
              'isr' => $datos['isr'],
              'total_deduccion' => $descuento + $isr,
              'total_neto' => $valorUnitario - $descuento - $isr,
              'id_empleado_creador' => $this->session->userdata('id_usuario'),
              'id_puesto' => $empleadoNomina[0]->id_puesto,
              'xml' => $result['xml'],
              'uuid' => $result['uuid'],
              'esta_asegurado' => $datos['esta_asegurado'],
            );
          }
          else
          {
            $errorTimbrar = true;
          }

          // echo "<pre>";
          //   var_dump($datosXML, $archivo);
          // echo "</pre>";exit;

          // echo "<pre>";
          //   var_dump($empleado, $cadenaOriginal, $sello, $certificado);
          // echo "</pre>";exit;
        } else {
          $nominasEmpleados[] = array(
              'id_empleado'         => $empleadoId,
              'id_empresa'          => $empresaId,
              'anio'                => $fechasSemana['anio'],
              'semana'              => $datos['numSemana'],
              'fecha_inicio'        => $fechasSemana['fecha_inicio'],
              'fecha_final'         => $fechasSemana['fecha_final'],
              'dias_trabajados'     => $empleadoNomina[0]->dias_trabajados,
              'salario_diario'      => $empleadoNomina[0]->salario_diario_real,
              'salario_integral'    => 0,
              'aguinaldo_grabable'  => 0,
              'aguinaldo_exento'    => 0,
              'aguinaldo'           => ($empleadoNomina[0]->dias_aguinaldo * $empleadoNomina[0]->salario_diario_real),
              'total_percepcion'    => 0,
              'isr'                 => 0,
              'total_deduccion'     => 0,
              'total_neto'          => ($empleadoNomina[0]->dias_aguinaldo * $empleadoNomina[0]->salario_diario_real),
              'id_empleado_creador' => $this->session->userdata('id_usuario'),
              'id_puesto'           => $empleadoNomina[0]->id_puesto,
              'xml'                 => '',
              'uuid'                => '',
              'esta_asegurado'      => $datos['esta_asegurado'],
            );
        }
      }
    // }

    // Inserta las nominas.
    if (count($nominasEmpleados) > 0)
    {
      $this->db->insert_batch('nomina_aguinaldo', $nominasEmpleados);
    }

    return array('errorTimbrar' => $errorTimbrar, 'empleadoId' => $empleadoId, 'ultimoNoGenerado' => $datos['ultimo_no_generado']);
  }

  /**
   * Cancela una factura. Cambia el status a 'ca'.
   *
   * @return array
   */
  public function cancelaFactura($idEmpleado, $anio, $semana, $idEmpresa)
  {
    $this->load->library('cfdi');
    $this->load->library('facturartebarato_api');
    $this->load->model('documentos_model');

    // Obtenemos la info de la factura a cancelar.
    $query = $this->db->query("SELECT nf.uuid, e.rfc, e.id_empresa, e.nombre_fiscal
                               FROM nomina_fiscal AS nf
                                INNER JOIN empresas AS e ON e.id_empresa = nf.id_empresa
                               WHERE nf.id_empleado = {$idEmpleado} AND nf.id_empresa = {$idEmpresa} AND nf.anio = '{$anio}' AND nf.semana = '{$semana}'")->row();

    // Carga los datos fiscales de la empresa dentro de la lib CFDI.
    $this->cfdi->cargaDatosFiscales($query->id_empresa);

    // Parametros que necesita el webservice para la cancelacion.
    $params = array(
      'rfc'   => $query->rfc,
      'uuids' => $query->uuid,
      'cer'   => $this->cfdi->obtenCer(),
      'key'   => $this->cfdi->obtenKey(),
    );

    // Lama el metodo cancelar para que realiza la peticion al webservice.
    $result = $this->facturartebarato_api->cancelar($params);
    $cancelada = false;
    if ($result->data->status_uuid === '201' || $result->data->status_uuid === '202')
    {
      $this->db->delete('nomina_fiscal', "id_empleado = {$idEmpleado} AND id_empresa = {$idEmpresa} AND anio = '{$anio}' AND semana = '{$semana}'");
      $query1 = $this->db->query("SELECT id_prestamo
                                 FROM nomina_fiscal_prestamos
                                 WHERE id_empleado = {$idEmpleado} AND id_empresa = {$idEmpresa} AND anio = '{$anio}' AND semana = '{$semana}'");
      if($query1->num_rows() > 0){
        $this->db->delete('nomina_fiscal_prestamos', "id_empleado = {$idEmpleado} AND id_empresa = {$idEmpresa} AND anio = '{$anio}' AND semana = '{$semana}'");
        foreach ($query1->result() as $key => $value)
        {
          $this->db->update('nomina_prestamos', array('status' => 't'), "id_prestamo = {$value->id_prestamo}");
        }
      }
      $cancelada = true;
    }

    return array('msg' => $result->data->status_uuid, 'empresa' => $query->nombre_fiscal, 'cancelada' => $cancelada);
  }

  public function pdfReciboNominaFiscalAguinaldo($empleadoId, $semana, $anio, $empresaId, $pdf=null)
  {
    $this->load->model('empresas_model');

    if ($empresaId !== '')
      $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $empresaId)->get()->row()->dia_inicia_semana;
    else
      $dia = '4';

    $semana = $this->fechasDeUnaSemana($semana, $anio, $dia);
    $_GET['cid_empresa'] = $empresaId; //para las cuentas del contpaq
    $configuraciones = $this->configuraciones();
    $filtros = array('semana' => $semana['semana'], 'empresaId' => $empresaId, 'dia_inicia_semana' => $dia, 'anio' => $semana['anio']);
    $empleados = $this->nomina($configuraciones, $filtros, $empleadoId);
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);

    // echo "<pre>";
    //   var_dump($empleados);
    // echo "</pre>";exit;

    include_once(APPPATH.'libraries/phpqrcode/qrlib.php');

    $nomina = $this->db->query("SELECT uuid, xml FROM nomina_aguinaldo WHERE id_empleado = {$empleadoId} AND id_empresa = {$empresaId} AND anio = {$semana['anio']} AND semana = {$semana['semana']}")->row();

    $xml = simplexml_load_string(str_replace(array('cfdi:', 'tfd:', 'nomina:'), '', $nomina->xml));

    // echo "<pre>";
    //   var_dump($nomina->xml, $xml);
    // echo "</pre>";exit;

    $show = false;
    if ($pdf == null)
    {
      $show = true;
      $this->load->library('mypdf');
      // Creación del objeto de la clase heredada
      $pdf = new MYpdf('P', 'mm', 'Letter');
    }

    $pdf->show_head = true;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->logo = $empresa['info']->logo;
    // $pdf->titulo2 = "Recibo de PTU de {$semana['fecha_inicio']} al {$semana['fecha_final']}";
    $pdf->titulo2 = "Aguinaldo";
    $pdf->titulo3 = "Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $total_gral = array( 'aguinaldo' => 0, 'isr' => 0, 'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0);
    $total_dep = array( 'aguinaldo' => 0, 'isr' => 0, 'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0);

    $dep_tiene_empleados = true;
    $y = $pdf->GetY();
    foreach ($empleados as $key => $empleado)
    {
      if($dep_tiene_empleados)
      {
        $pdf->SetFont('Helvetica','', 10);
        $pdf->SetXY(6, $pdf->GetY() + 4);
        $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
        $pdf->Row(array('', 'Percepción', 'Importe', '', 'Deducción', 'Importe'), false, false, null, 2, 1);

        $pdf->SetFont('Helvetica','', 10);
        $pdf->SetXY(6, $pdf->GetY() - 2);
        $pdf->Cell(200, 2, "________________________________________________________________________________________________________", 0, 0, 'L', 0);
        $dep_tiene_empleados = false;
      }

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY() + 4);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(15, 100));
      $pdf->Row(array($empleado->no_empleado, $empleado->nombre), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetFont('Helvetica','', 9);
      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(50, 70, 50));
      $pdf->Row(array($empleado->puesto, "RFC: {$empleado->rfc}", "Afiliciación IMSS: {$empleado->no_seguro}"), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(50, 35, 35, 35, 30));
      $pdf->Row(array("Fecha Ingr: {$empleado->fecha_entrada}", "Sal. diario: {$empleado->salario_diario}", "S.D.I: {$empleado->nomina->salario_diario_integrado}", "S.B.C: {$empleado->nomina->salario_diario_integrado}", 'Cotiza fijo'), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $horasExtras = 0;
      if ($empleado->horas_extras_dinero > 0)
      {
        $pagoXHora = $empleado->salario_diario / 8;
        $horasExtras = $empleado->horas_extras_dinero / $pagoXHora;
      }

      $pdf->SetXY(6, $pdf->GetY() + 0);
      $pdf->SetAligns(array('L', 'L'));
      $pdf->SetWidths(array(70));
      $pdf->Row(array("CURP: {$empleado->curp}"), false, false, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

      $y2 = $pdf->GetY();

      // Percepciones
      $percepciones = $empleado->nomina->percepciones;

      // AGUINALDO
      if ($empleado->nomina_fiscal_ptu > 0)
      {
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'AGUINALDO', String::formatoNumero($empleado->nomina_fiscal_aguinaldo, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['aguinaldo'] += $empleado->nomina_fiscal_aguinaldo;
        $total_gral['aguinaldo'] += $empleado->nomina_fiscal_aguinaldo;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y2 = $pdf->GetY();
        }
      }

      $y = $pdf->GetY();

      // Deducciones
      $deducciones = $empleado->nomina->deducciones;
      $pdf->SetFont('Helvetica','', 9);

      $pdf->SetY($y2);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y = $pdf->GetY();
      }

      if ($empleado->nomina_fiscal_aguinaldo_isr > 0)
      {
        $pdf->SetXY(108, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'ISR', String::formatoNumero($empleado->nomina_fiscal_aguinaldo_isr, 2, '$', false)), false, 0, null, 1, 1);
        $total_dep['isr'] += $empleado->nomina_fiscal_aguinaldo_isr;
        $total_gral['isr'] += $empleado->nomina_fiscal_aguinaldo_isr;
        if($pdf->GetY() >= $pdf->limiteY)
        {
          $pdf->AddPage();
          $y = $pdf->GetY();
        }
      }

      if ($y < $pdf->GetY())
      {
        $y = $pdf->GetY();
      }

      // Total percepciones y deducciones
      $pdf->SetXY(6, $y + 2);
      $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));

      $total_dep['total_percepcion'] += $empleado->nomina_fiscal_aguinaldo_total_percepciones;
      $total_gral['total_percepcion'] += $empleado->nomina_fiscal_aguinaldo_total_percepciones;
      $total_dep['total_deduccion'] += $empleado->nomina_fiscal_aguinaldo_total_deducciones;
      $total_gral['total_deduccion'] += $empleado->nomina_fiscal_aguinaldo_total_deducciones;
      $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($empleado->nomina_fiscal_aguinaldo_total_percepciones, 2, '$', false), '', 'Total Deducciones', String::formatoNumero($empleado->nomina_fiscal_aguinaldo_total_deducciones, 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $total_dep['total_neto'] += $empleado->nomina_fiscal_aguinaldo_total_neto;
      $total_gral['total_neto'] += $empleado->nomina_fiscal_aguinaldo_total_neto;
      $pdf->Row(array('', 'Total Neto', String::formatoNumero($empleado->nomina_fiscal_aguinaldo_total_neto, 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
          $pdf->AddPage();

      $pdf->SetFont('Helvetica', '', 9);
      $pdf->SetXY(120, $pdf->GetY()+3);
      $pdf->Cell(200, 2, "--------------------------------------------------------------------------------------", 0, 0, 'L', 0);
      if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();
    }

    if($xml === false)
      true;
    else
    {
      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(6, $pdf->GetY()+4);
      $pdf->Cell(78, 4, 'RFC EMISOR: '.$xml->Emisor[0]['rfc'], 0, 0, 'L', 0);

      $pdf->SetXY(86, $pdf->GetY());
      $pdf->Cell(78, 4, 'Forma de Pago: '.$xml[0]['formaDePago'], 0, 0, 'L', 0);

      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(158, $pdf->GetY());
      $pdf->Cell(78, 4, 'Condicion de Pago: Contado', 0, 0, 'L', 0);

      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(86, $pdf->GetY()+5);
      $pdf->Cell(78, 4, "Metodo de Pago: {$xml[0]['metodoDePago']}", 0, 0, 'L', 0);

      $cuenta_banco = substr($empleado->cuenta_banco, -4);
      $pdf->SetFont('helvetica','B', 9);
      $pdf->SetXY(158, $pdf->GetY());
      $pdf->Cell(76, 4, "Cuenta de Pago: {$cuenta_banco}", 0, 0, 'L', 0);
      ////////////////////
      // Timbrado Datos //
      ////////////////////
      if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
          $pdf->AddPage();

      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(10, $pdf->GetY() + 5);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array('Sello Digital del CFDI:'), false, 0);

      $pdf->SetFont('helvetica', '', 8);
      $pdf->SetY($pdf->GetY() - 3);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array($xml->Complemento->TimbreFiscalDigital[0]['selloCFD']), false, 0);

      if($pdf->GetY() + 25 >= $pdf->limiteY) //salta de pagina si exede el max
          $pdf->AddPage();

      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(10, $pdf->GetY() - 2);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array('Sello Digital del SAT:'), false, 0);

      $pdf->SetFont('helvetica', '', 8);
      $pdf->SetY($pdf->GetY() - 3);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(196));
      $pdf->Row(array($xml->Complemento->TimbreFiscalDigital[0]['selloSAT']), false, 0);

      /////////////
      // QR CODE //
      /////////////

      // formato
      // ?re=XAXX010101000&rr=XAXX010101000&tt=1234567890.123456&id=ad662d33-6934-459c-a128-BDf0393f0f44
      // 0000001213.520000

      $total = explode('.', $xml[0]['total']);

      // Obtiene la diferencia de caracteres en la parte entera.
      $diff = 10 - strlen($total[0]);

      // Agrega los 0 faltantes  a la parte entera.
      for ($i=0; $i < $diff; $i++)
        $total[0] = "0{$total[0]}";

      // Si el total no contiene decimales le asigna en la parte decimal 6 ceros.
      if (count($total) === 1)
      {
        $total[1] = '000000';
      }
      else
      {
        // Obtiene la diferencia de caracteres en la parte decimal.
        $diff = 6 - strlen($total[1]);

        // Agregar los 0 restantes en la parte decimal.
        for ($i=0; $i < $diff; $i++)
          $total[1] = "{$total[1]}0";
      }

      $code = "?re={$xml->Emisor[0]['rfc']}";
      $code .= "&rr={$xml->Receptor[0]['rfc']}";
      $code .= "&tt={$total[0]}.{$total[1]}";
      $code .= "&id={$xml->Complemento->TimbreFiscalDigital[0]['UUID']}";

      // echo "<pre>";
      //   var_dump($code, $total, $diff);
      // echo "</pre>";exit;

      QRcode::png($code, APPPATH.'media/qrtemp.png', 'H', 3);

      if($pdf->GetY() + 50 >= $pdf->limiteY) //salta de pagina si exede el max
          $pdf->AddPage();

      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Image(APPPATH.'media/qrtemp.png', null, null, 40);

      // Elimina el QR generado temporalmente.
      unlink(APPPATH.'media/qrtemp.png');

      ////////////////////
      // Timbrado Datos //
      ////////////////////

      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->SetXY(45, $pdf->GetY() - 39);
      $pdf->SetAligns(array('L'));
      $pdf->SetWidths(array(160));
      $pdf->Row(array('Cadena Original del complemento de certificación digital del SAT:'), false, 0);

      $pdf->SetFont('helvetica', '', 8);
      $cadenaOriginalSAT = "||{$xml->Complemento->TimbreFiscalDigital[0]['version']}|{$xml->Complemento->TimbreFiscalDigital[0]['UUID']}|{$xml->Complemento->TimbreFiscalDigital[0]['FechaTimbrado']}|{$xml->Complemento->TimbreFiscalDigital[0]['selloCFD']}|{$xml->Complemento->TimbreFiscalDigital[0]['noCertificadoSAT']}||";
      $pdf->SetXY(45, $pdf->GetY() - 3);
      $pdf->Row(array($cadenaOriginalSAT), false, 0);

      $pdf->SetFont('helvetica', 'B', 10);
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 171, 72);
      $pdf->SetXY(45, $pdf->GetY() + 1);
      $pdf->Cell(68, 6, "Folio Fiscal:", 0, 0, 'R', 1);

      $pdf->SetXY(125, $pdf->GetY());
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['UUID'], 0, 0, 'C', 0);

      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 171, 72);
      $pdf->SetXY(45, $pdf->GetY() + 7);
      $pdf->Cell(68, 6, "No de Serie del Certificado del SAT:", 0, 0, 'R', 1);

      $pdf->SetXY(125, $pdf->GetY());
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['noCertificadoSAT'], 0, 0, 'C', 0);

      $pdf->SetFont('helvetica', 'B', 10);
      $pdf->SetFillColor(242, 242, 242);
      $pdf->SetTextColor(0, 171, 72);
      $pdf->SetXY(45, $pdf->GetY() + 7);
      $pdf->Cell(68, 6, "Fecha y hora de certificación:", 0, 0, 'R', 1);

      $pdf->SetXY(125, $pdf->GetY());
      $pdf->SetTextColor(0, 0, 0);
      $pdf->Cell(65, 6, $xml->Complemento->TimbreFiscalDigital[0]['FechaTimbrado'], 0, 0, 'C', 0);

      $pdf->SetXY(0, $pdf->GetY()+13);
      $pdf->SetTextColor(0, 0, 0);
      $pdf->SetFont('helvetica', 'B', 8);
      $pdf->Cell(220, 6, 'ESTE DOCUMENTO ES UNA REPRESENTACION IMPRESA DE UN CFDI.', 0, 0, 'C', 0);
    }

    if($show)
      $pdf->Output('AGUINALDO_'.strtoupper($empleado->nombre).'.pdf', 'I');
  }

  public function pdfRecibNominAguinaldo($semana, $anio, $empresaId)
  {
    $this->load->library('mypdf');
    $this->load->model('empresas_model');

    if ($empresaId !== '')
      $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $empresaId)->get()->row()->dia_inicia_semana;
    else
      $dia = '4';
    $semana = $this->fechasDeUnaSemana($semana, $anio, $dia);
    $_GET['cid_empresa'] = $empresaId; //para las cuentas del contpaq
    $configuraciones = $this->configuraciones();
    $filtros = array('semana' => $semana['semana'], 'empresaId' => $empresaId, 'dia_inicia_semana' => $dia, 'anio' => $semana['anio']);
    $empleados = $this->nomina($configuraciones, $filtros);

    $pdf = new MYpdf('P', 'mm', 'Letter');

    foreach ($empleados as $key => $value)
    {
      if ($value->esta_asegurado == 't')
        $this->pdfReciboNominaFiscalAguinaldo($value->id, $semana['semana'], $anio, $empresaId, $pdf);
    }

    $pdf->Output('AGUINALDOS.pdf', 'I');
  }

  public function pdfNominaFiscalAguinaldo($semana, $empresaId, $anio=null, $diaComienza=4)
  {
    $anio = $anio==null? date("Y"): $anio;
    $this->load->model('empresas_model');
    $this->load->model('usuarios_departamentos_model');

    $semana = $this->fechasDeUnaSemana($semana, $anio, $diaComienza);
    $_GET['cid_empresa'] = $empresaId; //para las cuentas del contpaq
    $configuraciones = $this->configuraciones();
    $filtros = array('semana' => $semana['semana'], 'empresaId' => $empresaId, 'asegurado' => 'si', 'ordenar' => "ORDER BY u.id ASC", 'anio' => $semana['anio']);
    $empleados = $this->nomina($configuraciones, $filtros);
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);
    $finiquitos = $this->db->query("SELECT * FROM usuarios AS u INNER JOIN finiquito AS f ON u.id = f.id_empleado
      WHERE f.fecha_salida BETWEEN '{$semana['fecha_inicio']}' AND '{$semana['fecha_final']}'")->result();

    // echo "<pre>";
    //   var_dump($empleados);
    // echo "</pre>";exit;

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('P', 'mm', 'Letter');
    $pdf->show_head = true;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    // $pdf->titulo2 = "Lista de Raya de {$semana['fecha_inicio']} al {$semana['fecha_final']}";
    $pdf->titulo2 = "Aguinaldo";
    // $pdf->titulo3 = "Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $pdf->SetFont('Helvetica','', 10);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->SetXY(6, 27);
    $pdf->Cell(100, 6, "Reg. Pat. IMSS: A4914083100", 0, 0, 'L', 0);

    $pdf->SetFont('Helvetica','B', 10);
    $pdf->SetXY(6, $pdf->GetY() + 6);
    $pdf->Cell(100, 6, "ADMINISTRACION Reg. Pat. IMSS: A4914083100", 0, 0, 'L', 0);

    $total_gral = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0,
      'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'isr' => 0,
      'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0);

    // $departamentos = $this->usuarios_model->departamentos();
    $_GET['did_empresa'] = $empresaId;
    $departamentos = $this->usuarios_departamentos_model->getPuestos(false)['puestos'];

    foreach ($departamentos as $keyd => $departamento)
    {
      $total_dep = array( 'sueldo' => 0, 'horas_extras' => 0, 'vacaciones' => 0, 'prima_vacacional' => 0, 'subsidio' => 0,
        'ptu' => 0, 'aguinaldo' => 0, 'infonavit' => 0, 'imms' => 0, 'prestamos' => 0, 'isr' => 0,
        'total_percepcion' => 0, 'total_deduccion' => 0, 'total_neto' => 0);

      $dep_tiene_empleados = true;
      $y = $pdf->GetY();
      foreach ($empleados as $key => $empleado)
      {
        if($departamento->id_departamento == $empleado->id_departamente)
        {
          if($dep_tiene_empleados)
          {
            $pdf->SetFont('Helvetica','B', 10);
            $pdf->SetXY(6, $pdf->GetY()+6);
            $pdf->Cell(130, 6, $departamento->nombre, 0, 0, 'L', 0);

            $pdf->SetFont('Helvetica','', 10);
            $pdf->SetXY(6, $pdf->GetY() + 8);
            $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
            $pdf->Row(array('', 'Percepción', 'Importe', '', 'Deducción', 'Importe'), false, false, null, 2, 1);

            $pdf->SetFont('Helvetica','', 10);
            $pdf->SetXY(6, $pdf->GetY() - 2);
            $pdf->Cell(200, 2, "________________________________________________________________________________________________________", 0, 0, 'L', 0);
            $dep_tiene_empleados = false;
          }

          $pdf->SetFont('Helvetica','B', 9);
          $pdf->SetXY(6, $pdf->GetY() + 4);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(15, 100));
          $pdf->Row(array($empleado->no_empleado, $empleado->nombre), false, false, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetFont('Helvetica','', 9);
          $pdf->SetXY(6, $pdf->GetY() + 0);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(50, 70, 50));
          $pdf->Row(array($empleado->puesto, "RFC: {$empleado->rfc}", "Afiliciación IMSS: {$empleado->no_seguro}"), false, false, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $pdf->SetXY(6, $pdf->GetY() + 0);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(50, 35, 35, 35, 30));
          $pdf->Row(array("Fecha Ingr: {$empleado->fecha_entrada}", "Sal. diario: {$empleado->salario_diario}", "S.D.I: {$empleado->nomina->salario_diario_integrado}", "S.B.C: {$empleado->nomina->salario_diario_integrado}", 'Cotiza fijo'), false, false, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $horasExtras = 0;
          if ($empleado->horas_extras_dinero > 0)
          {
            $pagoXHora = $empleado->salario_diario / 8;
            $horasExtras = $empleado->horas_extras_dinero / $pagoXHora;
          }

          $pdf->SetXY(6, $pdf->GetY() + 0);
          $pdf->SetAligns(array('L', 'L'));
          $pdf->SetWidths(array(70));
          // $pdf->Row(array("Dias Pagados: {$empleado->dias_trabajados}", "Tot Hrs trab: " . $empleado->dias_trabajados * 8, 'Hrs dia: 8.00', "Hrs extras: " . number_format($horasExtras, 2), "CURP: {$empleado->curp}"), false, false, null, 1, 1);
          $pdf->Row(array("CURP: {$empleado->curp}"), false, false, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

          $y2 = $pdf->GetY();

          // Percepciones
          $percepciones = $empleado->nomina->percepciones;

          // Aguinaldo
          if ($empleado->nomina_fiscal_aguinaldo > 0)
          {
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'Aguinaldo', String::formatoNumero($empleado->nomina_fiscal_aguinaldo, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['aguinaldo'] += $empleado->nomina_fiscal_aguinaldo;
            $total_gral['aguinaldo'] += $empleado->nomina_fiscal_aguinaldo;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y2 = $pdf->GetY();
            }
          }

          $y = $pdf->GetY();

          // Deducciones
          $deducciones = $empleado->nomina->deducciones;
          $pdf->SetFont('Helvetica','', 9);

          $pdf->SetY($y2);

          if ($empleado->nomina_fiscal_aguinaldo_isr > 0)
          {
            $pdf->SetXY(108, $pdf->GetY());
            $pdf->SetAligns(array('L', 'L', 'R'));
            $pdf->SetWidths(array(15, 62, 25));
            $pdf->Row(array('', 'ISR', String::formatoNumero($empleado->nomina_fiscal_aguinaldo_isr, 2, '$', false)), false, 0, null, 1, 1);
            $total_dep['isr'] += $empleado->nomina_fiscal_aguinaldo_isr;
            $total_gral['isr'] += $empleado->nomina_fiscal_aguinaldo_isr;
            if($pdf->GetY() >= $pdf->limiteY)
            {
              $pdf->AddPage();
              $y = $pdf->GetY();
            }
          }

          if ($y < $pdf->GetY())
          {
            $y = $pdf->GetY();
          }

          // Total percepciones y deducciones
          $pdf->SetXY(6, $y + 2);
          $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
          $total_dep['total_percepcion'] += $empleado->nomina_fiscal_aguinaldo_total_percepciones;
          $total_gral['total_percepcion'] += $empleado->nomina_fiscal_aguinaldo_total_percepciones;
          $total_dep['total_deduccion'] += $empleado->nomina_fiscal_aguinaldo_total_deducciones;
          $total_gral['total_deduccion'] += $empleado->nomina_fiscal_aguinaldo_total_deducciones;
          $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($empleado->nomina_fiscal_aguinaldo_total_percepciones, 2, '$', false), '', 'Total Deducciones', String::formatoNumero($empleado->nomina_fiscal_aguinaldo_total_deducciones, 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
              $pdf->AddPage();

          $pdf->SetFont('Helvetica','B', 9);
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $total_dep['total_neto'] += $empleado->nomina_fiscal_aguinaldo_total_neto;
          $total_gral['total_neto'] += $empleado->nomina_fiscal_aguinaldo_total_neto;
          $pdf->Row(array('', 'Total Neto', String::formatoNumero($empleado->nomina_fiscal_aguinaldo_total_neto, 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
              $pdf->AddPage();

          $pdf->SetFont('Helvetica', '', 9);
          $pdf->SetXY(120, $pdf->GetY()+3);
          $pdf->Cell(200, 2, "--------------------------------------------------------------------------------------", 0, 0, 'L', 0);
          if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();
        }
      }

      //****** Total departamento ******
      if($dep_tiene_empleados == false)
      {
        if($pdf->GetY()+10 >= $pdf->limiteY)
          $pdf->AddPage();
        $pdf->SetFont('Helvetica','B', 10);
        $pdf->SetXY(6, $pdf->GetY()+2);
        $pdf->SetAligns(array('L'));
        $pdf->SetWidths(array(200));
        $pdf->Row(array("Total Departamento {$departamento->nombre}"), false, 0, null, 1, 1);
        $pdf->Row(array("____________________________________________________________________________________________________"), false, 0, null, 1, 1);

        $pdf->SetFont('Helvetica','', 9);
        $y2 = $pdf->GetY();

        // Aguinaldo
        if ($total_dep['aguinaldo'] > 0)
        {
          $pdf->SetXY(6, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'Aguinaldo', String::formatoNumero($total_dep['aguinaldo'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y2 = $pdf->GetY();
          }
        }

        $y = $pdf->GetY();

        // Deducciones
        $deducciones = $empleado->nomina->deducciones;
        $pdf->SetFont('Helvetica','', 9);

        $pdf->SetY($y2);
        if ($total_dep['isr'] > 0)
        {
          $pdf->SetXY(108, $pdf->GetY());
          $pdf->SetAligns(array('L', 'L', 'R'));
          $pdf->SetWidths(array(15, 62, 25));
          $pdf->Row(array('', 'ISR', String::formatoNumero($total_dep['isr'], 2, '$', false)), false, 0, null, 1, 1);
          if($pdf->GetY() >= $pdf->limiteY)
          {
            $pdf->AddPage();
            $y = $pdf->GetY();
          }
        }

        if ($y < $pdf->GetY())
        {
          $y = $pdf->GetY();
        }

        // Total percepciones y deducciones
        $pdf->SetXY(6, $y + 2);
        $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
        $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($total_dep['total_percepcion'], 2, '$', false), '', 'Total Deducciones', String::formatoNumero($total_dep['total_deduccion'], 2, '$', false)), false, 0, null, 1, 1);
        if($pdf->GetY() >= $pdf->limiteY)
            $pdf->AddPage();

        $pdf->SetFont('Helvetica','B', 9);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->SetAligns(array('L', 'L', 'R'));
        $pdf->SetWidths(array(15, 62, 25));
        $pdf->Row(array('', 'Total Neto', String::formatoNumero($total_dep['total_neto'], 2, '$', false)), false, 0, null, 1, 1);
      }

      $pdf->SetFont('Helvetica','', 10);
    }

    //********* Total general ***************
    if($pdf->GetY()+10 >= $pdf->limiteY)
      $pdf->AddPage();
    $pdf->SetFont('Helvetica','B', 10);
    $pdf->SetXY(6, $pdf->GetY()+2);
    $pdf->SetAligns(array('L'));
    $pdf->SetWidths(array(200));
    $pdf->Row(array("Total General"), false, 0, null, 1, 1);
    $pdf->Row(array("____________________________________________________________________________________________________"), false, 0, null, 1, 1);

    $pdf->SetFont('Helvetica','', 9);
    $y2 = $pdf->GetY();
    // Sueldo
    // $pdf->SetXY(6, $pdf->GetY());
    // $pdf->SetAligns(array('L', 'L', 'R'));
    // $pdf->SetWidths(array(15, 62, 25));
    // $pdf->Row(array('', 'Sueldo', String::formatoNumero($total_gral['sueldo'], 2, '$', false)), false, 0, null, 1, 1);
    // if($pdf->GetY() >= $pdf->limiteY)
    // {
    //   $pdf->AddPage();
    //   $y2 = $pdf->GetY();
    // }

    // PTU
    if ($total_gral['aguinaldo'] > 0)
    {
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'Aguinaldo', String::formatoNumero($total_gral['aguinaldo'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y2 = $pdf->GetY();
      }
    }

    $y = $pdf->GetY();

    // Deducciones
    // $deducciones = $empleado->nomina->deducciones;
    $pdf->SetFont('Helvetica','', 9);

    $pdf->SetY($y2);
    if ($total_gral['isr'] > 0)
    {
      $pdf->SetXY(108, $pdf->GetY());
      $pdf->SetAligns(array('L', 'L', 'R'));
      $pdf->SetWidths(array(15, 62, 25));
      $pdf->Row(array('', 'ISR', String::formatoNumero($total_gral['isr'], 2, '$', false)), false, 0, null, 1, 1);
      if($pdf->GetY() >= $pdf->limiteY)
      {
        $pdf->AddPage();
        $y = $pdf->GetY();
      }
    }

    if ($y < $pdf->GetY())
    {
      $y = $pdf->GetY();
    }

    // Total percepciones y deducciones
    $pdf->SetXY(6, $y + 2);
    $pdf->SetAligns(array('L', 'L', 'R', 'L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25, 15, 62, 25));
    $pdf->Row(array('', 'Total Percepciones', String::formatoNumero($total_gral['total_percepcion'], 2, '$', false), '', 'Total Deducciones', String::formatoNumero($total_gral['total_deduccion'], 2, '$', false)), false, 0, null, 1, 1);
    if($pdf->GetY() >= $pdf->limiteY)
        $pdf->AddPage();

    $pdf->SetFont('Helvetica','B', 9);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns(array('L', 'L', 'R'));
    $pdf->SetWidths(array(15, 62, 25));
    $pdf->Row(array('', 'Total Neto', String::formatoNumero($total_gral['total_neto'], 2, '$', false)), false, 0, null, 1, 1);

    $pdf->Output('Aguinaldo.pdf', 'I');
  }

  public function pdfRptNominaAguinaldo($semana, $empresaId, $anio)
  {
    // echo "<pre>";
    //   var_dump($_POST);
    // echo "</pre>";exit;
    // $empleados = $this->pdfRptDataNominaFiscal($_POST, $empresaId);
    $this->load->model('usuarios_model');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_departamentos_model');
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);
    $semana = $this->fechasDeUnaSemana($semana, $anio, $empresa['info']->dia_inicia_semana);

    $this->load->library('mypdf');
    // Creación del objeto de la clase heredada
    $pdf = new MYpdf('L', 'mm', 'Letter');
    $pdf->show_head = true;
    $pdf->logo = $empresa['info']->logo;
    $pdf->titulo1 = $empresa['info']->nombre_fiscal;
    $pdf->titulo2 = "AGUINALDO";
    $pdf->titulo3 = "Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}";
    $pdf->AliasNbPages();
    $pdf->AddPage();

    $columnas = array('n' => array(), 'w' => array(6, 120, 15, 20, 20, 20, 10, 15, 20, 20), 'a' => array('L', 'L', 'R', 'R', 'R', 'R', 'R', 'R', 'R'));
    $columnas['n'][] = 'No';
    $columnas['n'][] = 'NOMBRE';
    $columnas['n'][] = 'SALARIO';
    $columnas['n'][] = 'AGUINALDO';
    $columnas['n'][] = 'ISR';
    $columnas['n'][] = 'TOTAL A PAGAR';
    $columnas['n'][] = 'DIAS';
    $columnas['n'][] = 'S REAL';
    $columnas['n'][] = 'A REAL';
    $columnas['n'][] = 'COMPLEM';

    $pdf->SetFont('Helvetica','B', 8);
    $pdf->SetXY(6, $pdf->GetY());
    $pdf->SetAligns($columnas['a']);
    $pdf->SetWidths($columnas['w']);
    $pdf->Row($columnas['n'], false, false, null, 2, 1);

    $ttotal_aseg_no_trs = $sueldo_semanal_real = $otras_percepciones = $domingo = $total_prestamos = $total_infonavit = $descuento_playeras = $descuento_otros = $ttotal_pagar = $ttotal_nomina = $total_no_fiscal = 0;
    $aguinaldo = 0;
    $y = $pdf->GetY();

    $_GET['did_empresa'] = $empresaId;
    $departamentos = $this->usuarios_departamentos_model->getPuestos(false)['puestos'];

    $aguinaldoTtotal = 0;
    $isrTtotal       = 0;
    $tTotal         = 0;

    $numero_empleado     = 0;
    $aguinaldoTotal      = 0;
    $isrTotal            = 0;
    $ttotal_pagar_gral   = 0;
    $complementoTtotal   = 0;
    $total_complementoe  = 0;
    $total_compleeTotal  = 0;
    $ttotal_pagarTtota   = 0;
    foreach ($departamentos as $keyd => $departamento)
    {
      $aguinaldo = 0;

      if($pdf->GetY()+8 >= $pdf->limiteY){
        $pdf->AddPage();
        $pdf->SetFont('Helvetica','B', 8);
        $pdf->SetXY(6, $pdf->GetY());
        $pdf->Row($columnas['n'], false, false, null, 2, 1);
      }

      $pdf->SetFont('Helvetica','B', 10);
      $pdf->SetXY(6, $pdf->GetY());
      $pdf->Cell(130, 6, $departamento->nombre, 0, 0, 'L', 0);

      $pdf->SetXY(6, $pdf->GetY()+6);
      foreach ($_POST['empleado_id'] as $key => $empleado)
      {
        if($departamento->id_departamento == $_POST['departamento_id'][$key])
        {
          $numero_empleado++;
          $empleado = $this->usuarios_model->get_usuario_info($empleado, true)['info'][0];

          $pdf->SetFont('Helvetica','', 8);
          if($pdf->GetY()+8 >= $pdf->limiteY){
            $pdf->AddPage();
            $pdf->SetFont('Helvetica','B', 8);
            $pdf->SetXY(6, $pdf->GetY());
            $pdf->Row($columnas['n'], false, false, null, 2, 1);
          }

          $pdf->SetFont('Helvetica','', 8);

          $total_pagar = $_POST['aguinaldo'][$key];

          $pdf->SetXY(6, $pdf->GetY());

          $dataarr = array();
          $dataarr[] = $numero_empleado;
          $dataarr[] = $empleado->apellido_paterno.' '.$empleado->apellido_materno.' '.$empleado->nombre;
          $dataarr[] = String::formatoNumero($_POST['salario_diario'][$key], 2, '$', false);
          $dataarr[] = String::formatoNumero($_POST['aguinaldo'][$key], 2, '$', false);
          $dataarr[] = String::formatoNumero($_POST['isr'][$key], 2, '$', false);
          $dataarr[] = String::formatoNumero($_POST['ttotal_nomina'][$key], 2, '$', false);
          $dataarr[] = String::formatoNumero($_POST['dias_aguinaldo'][$key], 2, '', false);
          $dataarr[] = String::formatoNumero($_POST['salario_diario_real'][$key], 2, '$', false);
          $dataarr[] = String::formatoNumero($_POST['total_complementoe'][$key], 2, '$', false);
          $dataarr[] = String::formatoNumero($_POST['total_no_fiscal'][$key], 2, '$', false);

          $pdf->Row($dataarr, false, true, null, 2, 1);
          $aguinaldoTotal      += $_POST['aguinaldo'][$key];
          $isrTotal            += $_POST['isr'][$key];
          $ttotal_pagar        += $_POST['ttotal_nomina'][$key];
          $ttotal_pagar_gral   += $_POST['total_no_fiscal'][$key];
          $total_complementoe  += $_POST['total_complementoe'][$key];
        }
      }

      if($pdf->GetY()+10 >= $pdf->limiteY)
        $pdf->AddPage();

      $pdf->SetFont('Helvetica','B', 8);
      $pdf->SetXY(6, $pdf->GetY());
      $datatto = array();
      $datatto[] = '';
      $datatto[] = 'TOTAL';
      $datatto[] = '';
      $datatto[] = String::formatoNumero($aguinaldoTotal, 2, '$', false);
      $datatto[] = String::formatoNumero($isrTotal, 2, '$', false);
      $datatto[] = String::formatoNumero($ttotal_pagar, 2, '$', false);
      $datatto[] = '';
      $datatto[] = '';
      $datatto[] = String::formatoNumero($total_complementoe, 2, '$', false);
      $datatto[] = String::formatoNumero($ttotal_pagar_gral, 2, '$', false);
      $pdf->Row($datatto, false, true, null, 2, 1);

      $aguinaldoTtotal    += $aguinaldoTotal;
      $isrTtotal          += $isrTotal;
      $ttotal_pagarTtota  += $ttotal_pagar;
      $complementoTtotal  += $ttotal_pagar_gral;
      $total_compleeTotal += $total_complementoe;

      $aguinaldoTotal     = 0;
      $isrTotal           = 0;
      $ttotal_pagar       = 0;
      $ttotal_pagar_gral  = 0;
      $total_complementoe = 0;
    }

    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetFont('Helvetica','B', 8);
    if($pdf->GetY()+8 >= $pdf->limiteY)
      $pdf->AddPage();
    $datatto = array();
    $datatto[] = '';
    $datatto[] = 'TOTAL';
    $datatto[] = '';
    $datatto[] = String::formatoNumero($aguinaldoTtotal, 2, '$', false);
    $datatto[] = String::formatoNumero($isrTtotal, 2, '$', false);
    $datatto[] = String::formatoNumero($ttotal_pagarTtota, 2, '$', false);
    $datatto[] = '';
    $datatto[] = '';
    $datatto[] = String::formatoNumero($total_compleeTotal, 2, '$', false);
    $datatto[] = String::formatoNumero($complementoTtotal, 2, '$', false);
    $pdf->Row($datatto, false, true, null, 2, 1);

    $pdf->SetXY(6, $pdf->GetY()+5);
    $pdf->SetFont('Helvetica','B', 8);
      if($pdf->GetY()+8 >= $pdf->limiteY)
        $pdf->AddPage();
    $pdf->SetAligns(array('L', 'L', 'L'));
    $pdf->SetWidths(array(50, 50, 50));

    // if ($empresa['info']->rfc === 'ESJ97052763A')
    // {
    //   $pdf->Row(array(
    //     'NOMINA FISCAL: '.String::formatoNumero($aguinaldoTotal, 2, '$', false),
    //     'TRANSFERIDO: '.String::formatoNumero($aguinaldoTotal, 2, '$', false),
    //     'CHEQUE FISCAL: '.String::formatoNumero(($aguinaldoTotal), 2, '$', false),
    //     ), false, true, null, 2, 1);
    // }

    $pdf->Output('AGUINALDOS.pdf', 'I');
  }

  public function xlsRptNominaAguinaldo($semana, $empresaId, $anio)
  {
    $this->load->model('usuarios_model');
    $this->load->model('empresas_model');
    $this->load->model('usuarios_departamentos_model');
    $empresa = $this->empresas_model->getInfoEmpresa($empresaId, true);
    $semana = $this->fechasDeUnaSemana($semana, $anio, $empresa['info']->dia_inicia_semana);
    $finiquitos = $this->db->query("SELECT * FROM usuarios AS u INNER JOIN finiquito AS f ON u.id = f.id_empleado
      WHERE f.fecha_salida BETWEEN '{$semana['fecha_inicio']}' AND '{$semana['fecha_final']}'")->result();

    $html = '<table>';
    $html .= $this->rowXls( array($empresa['info']->nombre_fiscal) );
    $html .= $this->rowXls( array("AGUINALDO") );
    $html .= $this->rowXls( array("Periodo Semanal No. {$semana['semana']} del Año {$semana['anio']}") );
    $html .= $this->rowXls( array() );

    $columnas = array('n' => array(), 'w' => array(5, 64, 20, 20), 'a' => array('L', 'L', 'R', 'R'));
    $columnas['n'][] = 'No';
    $columnas['n'][] = 'NOMBRE';
    $columnas['n'][] = 'SALARIO';
    $columnas['n'][] = 'AUGINALDO';
    $columnas['n'][] = 'ISR';
    $columnas['n'][] = 'TOTAL A PAGAR';
    $columnas['n'][] = 'DIAS';
    $columnas['n'][] = 'S REAL';
    $columnas['n'][] = 'A REAL';
    $columnas['n'][] = 'COMPLEM';

    $aguinaldo = 0;

    $aguinaldoTtotal = 0;
    $isrTtotal       = 0;
    $tTotal          = 0;
    $complementoT    = 0;
    $totalcomplemtno = 0;
    $total_complementoe  = 0;
    $total_compleeTotal  = 0;

    // $departamentos = $this->usuarios_model->departamentos();
    $_GET['did_empresa'] = $empresaId;
    $departamentos = $this->usuarios_departamentos_model->getPuestos(false)['puestos'];

    $numero_empleado = 0;
    $html .= $this->rowXls($columnas['n']);
    $html .= $this->rowXls(array(''));
    $aguinaldoTotal = 0;
    $ttotal_pagar = 0;

    $aguinaldoTotal = 0;
    $isrTotal = 0;
    foreach ($departamentos as $keyd => $departamento)
    {
      $aguinaldo = 0;

      // $sueldo_semanal_real1 = $otras_percepciones1 = $domingo1 = $total_prestamos1 = $total_infonavit1 = $descuento_playeras1 = $descuento_otros1 = $ttotal_pagar1 = $ttotal_nomina1 = $total_no_fiscal1 = 0;

      $html .= '<tr><td style="font-size:14px;">'.$departamento->nombre.'</td></tr>';

      // $pdf->SetXY(6, $pdf->GetY()+6);
      foreach ($_POST['empleado_id'] as $key => $empleado)
      {
        if($departamento->id_departamento == $_POST['departamento_id'][$key])
        {
          // $numero_empleado++;
          $empleado = $this->usuarios_model->get_usuario_info($empleado, true)['info'][0];
          $total_pagar = $_POST['aguinaldo'][$key];

          $dataarr = array();
          $dataarr[] = $empleado->no_empleado;
          $dataarr[] = $empleado->apellido_paterno.' '.$empleado->apellido_materno.' '.$empleado->nombre;
          $dataarr[] = String::formatoNumero($_POST['salario_diario'][$key], 2, '', false);
          $dataarr[] = String::formatoNumero($_POST['aguinaldo'][$key], 2, '', false);
          $dataarr[] = String::formatoNumero($_POST['isr'][$key], 2, '', false);
          $dataarr[] = String::formatoNumero($_POST['ttotal_nomina'][$key], 2, '', false);
          $dataarr[] = String::formatoNumero($_POST['dias_aguinaldo'][$key], 2, '', false);
          $dataarr[] = String::formatoNumero($_POST['salario_diario_real'][$key], 2, '', false);
          $dataarr[] = String::formatoNumero($_POST['total_complementoe'][$key], 2, '', false);
          $dataarr[] = String::formatoNumero($_POST['total_no_fiscal'][$key], 2, '', false);

          $html .= $this->rowXls($dataarr);

          $aguinaldoTotal     += $_POST['aguinaldo'][$key];
          $isrTotal           += $_POST['isr'][$key];
          $ttotal_pagar       += $_POST['ttotal_nomina'][$key];
          $totalcomplemtno    += $_POST['total_no_fiscal'][$key];
          $total_complementoe += $_POST['total_complementoe'][$key];
        }
      }

      $datatto = array();
      $datatto[] = '';
      $datatto[] = 'TOTAL';
      $datatto[] = '';
      $datatto[] = String::formatoNumero($aguinaldoTotal, 2, '', false);
      $datatto[] = String::formatoNumero($isrTotal, 2, '', false);
      $datatto[] = String::formatoNumero($ttotal_pagar, 2, '', false);
      $datatto[] = '';
      $datatto[] = '';
      $datatto[] = String::formatoNumero($total_complementoe, 2, '', false);
      $datatto[] = String::formatoNumero($totalcomplemtno, 2, '', false);
      $html .= $this->rowXls($datatto);
      $html .= $this->rowXls(array(''));

      $aguinaldoTtotal    += $aguinaldoTotal;
      $isrTtotal          += $isrTotal;
      $complementoT       += $totalcomplemtno;
      $total_compleeTotal += $total_complementoe;

      $aguinaldoTotal  = 0;
      $isrTotal        = 0;
      $ttotal_pagar    = 0;
      $totalcomplemtno = 0;
      $total_complementoe = 0;
    }

    $datatto = array();
    $datatto[] = '';
    $datatto[] = 'TOTAL';
    $datatto[] = '';
    $datatto[] = String::formatoNumero($aguinaldoTtotal, 2, '', false);
    $datatto[] = String::formatoNumero($isrTtotal, 2, '', false);
    $datatto[] = String::formatoNumero($aguinaldoTtotal - $isrTtotal, 2, '', false);
    $datatto[] = '';
    $datatto[] = '';
    $datatto[] = String::formatoNumero($total_compleeTotal, 2, '', false);
    $datatto[] = String::formatoNumero($complementoT, 2, '', false);

    $html .= $this->rowXls($datatto);
    $html .= $this->rowXls(array(''));

    if ($empresa['info']->rfc === 'ESJ97052763A')
    {
      $html .= $this->rowXls(array(
        'NOMINA FISCAL: '.String::formatoNumero($ttotal_aseg_no_trs, 2, '', false),
        'TRANSFERIDO: '.String::formatoNumero($ttotal_nomina, 2, '', false),
        'CHEQUE FISCAL: '.String::formatoNumero(($ttotal_aseg_no_trs-$ttotal_nomina), 2, '', false),
        ), 'style="font-weight:bold;font-size:14px;"');
      $html .= $this->rowXls(array(''));
    }

    //Si es la empresa es gomez gudiño pone la nomina de limon (o ranchos), se obtiene de la bd
    $html .= '</table>';

    header("Content-type: application/vnd.ms-excel; name='excel'");
    header("Content-Disposition: filename=Aguinaldos.xls");
    header("Pragma: no-cache");
    header("Expires: 0");
    echo $html;
  }

  public function descargarTxtBancoAguinaldo($semana, $empresaId, $anio=null)
  {
    $anio = $anio==null?date("Y"):$anio;
    $_GET['cid_empresa'] = $empresaId; //para las cuentas del contpaq
    if ($empresaId !== '')
      $dia = $this->db->select('dia_inicia_semana')->from('empresas')->where('id_empresa', $empresaId)->get()->row()->dia_inicia_semana;
    else
      $dia = '4';

    $configuraciones = $this->configuraciones();
    $semana = $this->fechasDeUnaSemana($semana, $anio, $dia);
    $filtros = array('semana' => $semana['semana'], 'empresaId' => $empresaId, 'dia_inicia_semana' => $dia, 'anio' => $semana['anio']);
    $empleados = $this->nomina($configuraciones, $filtros);
    $nombre = "PAGO-AGUINALDO-{$semana['anio']}.txt";

    $content = array();
    $contador = 1;
    foreach ($empleados as $key => $empleado)
    {
      if($empleado->cuenta_banco != '' && $empleado->esta_asegurado == 't'){
        $content[] = $this->formatoBanco($contador, '0', 9, 'I') .
                    $this->formatoBanco(substr($empleado->rfc, 0, 10), ' ', 16, 'D') .
                    $this->formatoBanco('99', ' ', 2, 'I') .
                    $this->formatoBanco($empleado->cuenta_banco, ' ', 20, 'D') .
                    $this->formatoBanco($empleado->nomina_fiscal_aguinaldo_total_neto, '0', 15, 'I', true) .
                    $this->formatoBanco($this->removeTrash($empleado->nombre), ' ', 40, 'D') .
                    $this->formatoBanco('001', ' ', 3, 'D') .
                    $this->formatoBanco('001', ' ', 3, 'D');
        $contador++;
      }
    }

    $content[] = '';
    $content = implode("\r\n", $content);

    $fp = fopen(APPPATH."media/temp/{$nombre}", "wb");
    fwrite($fp,$content);
    fclose($fp);

    header('Content-Type: text/plain');
    header("Content-disposition: attachment; filename={$nombre}");
    readfile(APPPATH."media/temp/{$nombre}");
    unlink(APPPATH."media/temp/{$nombre}");
  }
}
/* End of file nomina_fiscal_model.php */
/* Location: ./application/models/nomina_fiscal_model.php */