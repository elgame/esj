    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Nomina Fiscal
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-list-alt"></i> Nomina</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content" style="/*overflow-x: auto;*/ max-height: 700px; font-size: 0.9em;">
            <form action="<?php echo base_url('panel/nomina_fiscal/'); ?>" method="GET" class="form-search" id="form">
              <div class="form-actions form-filters">
                <label for="anio">Año</label>
                <input type="number" name="anio" class="input-small search-query" id="anio" value="<?php echo set_value_get('anio', date("Y")); ?>">

                <label for="empresa">Empresa</label>
                <input type="text" name="empresa" class="input-xlarge search-query" id="empresa" value="<?php echo set_value_get('empresa', $empresaDefault->nombre_fiscal); ?>" size="73">
                <input type="hidden" name="empresaId" id="empresaId" value="<?php echo set_value_get('empresaId', $empresaDefault->id_empresa); ?>">

                <label for="fregistro_patronal" style="margin-top: 15px;">Registro Patronal</label>
                  <select name="fregistro_patronal" id="fregistro_patronal" class="input-medium">
                    <option value=""></option>
                    <?php foreach ($registros_patronales as $key => $regp): ?>
                    <option value="<?php echo $regp ?>" <?php echo set_select_get('fregistro_patronal', $regp, ($this->input->get('fregistro_patronal') == $regp)); ?>><?php echo $regp ?></option>
                    <?php endforeach ?>
                </select>

                <label for="ffecha1" style="margin-top: 15px;" class="txtTiponomin"><?php echo ucfirst($tipoNomina) ?></label>
                <select name="semana" class="input-xlarge" id="semanas">
                  <?php
                    $calcMes = 'false';
                    foreach ($semanasDelAno as $semana) {
                      if ($semana[$tipoNomina] == $numSemanaSelected) {
                        $semana2 =  $semana;
                        $calcMes = ($semana['calcmes']? 'true': 'false');
                      }
                    ?>
                    <option value="<?php echo $semana[$tipoNomina] ?>" <?php echo $semana[$tipoNomina] == $numSemanaSelected ? 'selected' : '' ?>><?php echo "{$semana[$tipoNomina]} - Del {$semana['fecha_inicio']} Al {$semana['fecha_final']}" ?></option>
                  <?php }
                    $_GET['anio'] = isset($_GET['anio']) ? $_GET['anio'] : date("Y");
                    $_GET['semana'] = isset($_GET['semana']) ? $_GET['semana'] : $semana2[$tipoNomina];
                    $_GET['empresaId'] = isset($_GET['empresaId']) ? $_GET['empresaId'] : $empresaDefault->id_empresa;
                    $_GET['empresa'] = isset($_GET['empresa']) ? $_GET['empresa'] : $empresaDefault->nombre_fiscal;
                  ?>
                </select>
                <input type="hidden" name="calcMes" value="<?php echo $calcMes ?>">

                <input type="submit" name="enviar" value="Buscar" class="btn">
                  <a rel="superbox-80x450" href="<?php echo base_url('panel/nomina_fiscal/calc_anual/?'.MyString::getVarsLink(array('msg'))) ?>" class="btn btn-success pull-right" title="Calculo Anual" id="calculoAnual">Calculo Anual</a>
                <?php if ( $nominas_finalizadas){ ?>
                  <a class="btn btn-success pull-right" href="<?php echo base_url('panel/nomina_fiscal/rpt_dim/?'.MyString::getVarsLink(array('msg'))) ?>" target="_blank" title="DIM">
                    DIM</a>
                <?php } ?>
              </div>
            </form>

            <form action="<?php echo base_url('panel/nomina_fiscal/nomina_fiscal_rpt_pdf/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form" target="_blank">

              <div class="row-fluid">
                <div class="span4" style="font-size: 1.3em;">
                  <div class="row-fluid">
                    <div class="span4">
                      Aguinaldo <input type="checkbox" id="check-aguinaldo" value="1" <?php echo $nominas_generadas ? 'disabled' : ''?>>
                      <input type="hidden" name="con_aguinaldo" value="0" class="span12" id="con-aguinaldo">
                    </div>
                    <div class="span8">
                      PTU <input type="text" name="utilidad_empresa" id="ptu" value="" class="input-small vpositive" <?php echo $nominas_generadas ? 'readonly' : ''?> style="margin-bottom: 0;">
                      <a href="javascript:void(0);" class="btn btn-success" id="btn-ptu"><i class="icon-refresh"></i></a>
                    </div>

                    <?php if ( $nominas_finalizadas){ ?>
                      <a href="<?php echo base_url('panel/nomina_fiscal/recibos_nomina_pdf/?'.MyString::getVarsLink(array('msg'))) ?>" target="_blank" title="Recibos Nomina"><img src="<?php echo base_url('application/images/otros/doc_pdf.png') ?>" width="40" height="40"></a>
                      <a href="<?php echo base_url('panel/nomina_trabajos/nomina_fiscal_ticket/?'.MyString::getVarsLink(array('msg'))) ?>" target="_blank" title="Tickets Nomina"><img src="<?php echo base_url('application/images/otros/doc_pdf.png') ?>" width="40" height="40"></a>
                    <?php } ?>

                  </div>
                </div>
                <div class="span5" style="text-align: center;">
                  <div style="font-size: 1.5em;">
                    <?php echo "<span class=\"txtTiponomin\">".ucfirst($tipoNomina)."</span> <span class=\"label\" style=\"font-size: 1em;\">{$semana2[$tipoNomina]}</span> - Del <span style=\"font-weight: bold;\">{$semana2['fecha_inicio']}</span> Al <span style=\"font-weight: bold;\">{$semana2['fecha_final']}</span>" ?>
                  </div>
                </div>
                <div class="span3">
                  <div class="input-prepend input-append">
                    <span class="add-on"><input type="checkbox" value="si" name="xls"> En Excel</span>
                    <button type="submit" name="rptlistado" class="btn btn-success" id="rptlistado">Reporte</button>
                  </div>

                  <?php if ( ! $nominas_finalizadas){ ?>
                    <?php if ($nominas_generadas): ?>
                    <button type="button" name="timbrar" class="btn btn-success" style="float: right;" id="timbrarNomina">Timbrar</button>
                    <?php else: ?>
                    <button type="button" name="guardar" class="btn btn-success" style="float: right;" id="guardarNomina">Guardar</button>
                    <?php endif ?>
                  <?php } else { ?>
                    <a href="<?php echo base_url('panel/nomina_fiscal/download_descuentos_corona/?id='.(isset($_GET['empresaId']) ? $_GET['empresaId'] : $empresaDefault->id_empresa).'&sem='.$numSemanaSelected. '&anio=' . $_GET['anio']) ?>" class="btn btn-info" title="Descargar Nomina Corona"><i class="icon-download"></i></a> |
                    <span class="label label-success" style="font-size: 1.3em;">Nominas generadas</span>
                    <a href="<?php echo base_url('panel/nomina_fiscal/nomina_fiscal_pdf/?'.MyString::getVarsLink(array('msg'))) ?>" target="_blank" title="Ver PDF"><img src="<?php echo base_url('application/images/otros/doc_pdf.png') ?>" width="40" height="40"></a>
                    <a href="<?php echo base_url('panel/nomina_fiscal/nomina_fiscal_cfdis/?'.MyString::getVarsLink(array('msg'))) ?>" target="_blank" title="Descargar XML"><img src="<?php echo base_url('application/images/otros/doc_xml.png') ?>" width="40" height="40"></a>
                    <a href="<?php echo base_url('panel/nomina_fiscal/nomina_fiscal_banco/?'.MyString::getVarsLink(array('msg'))) ?>" target="_blank" title="Descargar Archivo Banco"><img src="<?php echo base_url('application/images/otros/creditcard.png') ?>" width="40" height="40"></a>
                  <?php } ?>
                </div>
              </div>

              <input type="hidden" value="<?php echo $numSemanaSelected?>" name="numSemana">
              <input type="hidden" name="calcMes" value="<?php echo $calcMes ?>">
                <table class="table table-striped" style="display: block; overflow-x: auto;">
                  <thead>
                    <tr>
                      <th style="width: 202px;padding-top: 17px;padding-bottom: 17px;position: absolute;border-right: 1px #ccc solid;" colspan="2"></th>
                      <th colspan="4"></th>
                      <th colspan="5" style="text-align: center;background-color: #BEEEBC;" id="head-percepciones">PERCEPCIONES</th>
                      <th colspan="8" style="text-align: center;background-color: #EEBCBC;" id="head-deducciones">DEDUCCIONES</th>
                      <th style="background-color: #BCD4EE;"></th>
                      <th colspan="9" style="background-color: #EEEEBC;"></th>
                    </tr>
                    <tr>
                      <th style="position: absolute;z-index: 100;margin-top: -10px;">No. <?php echo count($empleados); ?></th>
                      <th style="width: 170px;padding-left: 40px!important; padding-top: 26px; border-right: 1px #ccc solid; position: absolute; ">NOMBRE</th>
                      <th style="padding-left: 225px!important;">PUESTO</th>
                      <th>SALARIO</th>
                      <th>SDI</th>
                      <th>DIAS TRAB.</th>

                      <!-- Percepciones -->
                      <th style="background-color: #BEEEBC;">SUELDO</th>
                      <th style="background-color: #BEEEBC;">P ASISTENCIA</th>
                      <!-- <th style="background-color: #BEEEBC;">DESPENSA</th> -->
                      <th id="head-vacaciones" style="display: none;background-color: #BEEEBC;">VACACIONES</th>
                      <th id="head-prima-vacacional" style="display: none;background-color: #BEEEBC;">P. VACACIONAL</th>
                      <th style="background-color: #BEEEBC;">HRS. EXT.</th>
                      <th id="head-aguinaldo" style="display: none;background-color: #BEEEBC;">AGUINALDO</th>
                      <th style="background-color: #BEEEBC;">SUBSIDIO</th>
                      <th id="head-ptu" style="display: none;background-color: #BEEEBC;">PTU</th>
                      <th style="background-color: #BEEEBC;">TOTAL</th>

                      <!-- Deducciones -->
                      <th style="background-color: #EEBCBC;">INFO.</th>
                      <th style="background-color: #EEBCBC;">IMSS</th>
                      <th style="background-color: #EEBCBC;">PRESTAMOS</th>
                      <th style="background-color: #EEBCBC;">FA</th>
                      <th style="background-color: #EEBCBC;">PA</th>
                      <th style="background-color: #EEBCBC;">Fonacot</th>
                      <th style="background-color: #EEBCBC;">ISR</th>
                      <th style="background-color: #EEBCBC;">TOTAL</th>

                      <!-- Total nomina -->
                      <th style="background-color: #BCD4EE;">TRANSF.</th>

                      <!-- Totales por fuera -->
                      <th style="background-color: #EEEEBC;">BONOS</th>
                      <th style="background-color: #EEEEBC;">OTRAS</th>
                      <th style="background-color: #EEEEBC;">DOMINGO</th>
                      <th style="background-color: #EEBCBC;">DESC PLAY</th>
                      <th style="background-color: #EEBCBC;">DESC OTRO</th>
                      <th style="background-color: #EEBCBC;">DESC COCINA</th>
                      <th style="background-color: #EEBCBC;">DESC PREST EF</th>
                      <th style="background-color: #EEBCBC;">DESC MATERI</th>
                      <th style="background-color: #EEEEBC;">TOTAL COMPLEM.</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                      $totalPercepcionesEmpleado = 0; // total de las percepciones del empleado.
                      $totalDeduccionesEmpleado = 0;

                      $totalSalarios = 0;
                      $totalPasistencia = 0;
                      // $totalDespensa = 0;
                      $totalSdi = 0;
                      $totalDiasTrabajados = 0;
                      $totalSueldos = 0;
                      $totalVacaciones = 0;
                      $totalPrimasVacacionales = 0;
                      $totalHorasExtras = 0;
                      $totalAguinaldos = 0;
                      $totalSubsidios = 0;
                      $totalPtu = 0;
                      $totalPercepciones = 0; // total de todas las percepciones.
                      $totalInfonavit = 0;
                      $totalFondoAhorro = 0;
                      $totalPensionAlimenticia = 0;
                      $totalFonacot = 0;
                      $totalImss = 0;
                      $totalPrestamos = 0;
                      $totalDescuentoPlayeras = 0;
                      $totalDescuentoOtros = 0;
                      $totalDescuentoCocina = 0;
                      $ttotalPrestamosEmpleadoEf = 0;
                      $ttotalDescuentoMaterial = 0;
                      $totalIsrs = 0;
                      $totalDeducciones = 0; // total de todas las deducciones.
                      $totalTransferencias = 0;
                      $totalBonos = 0;
                      $totalOtras = 0;
                      $totalDomingos = 0;
                      $totalComplementos = 0;
                      $utilidadEmpresa = 0;
                      $ultimoNoGenerado = '';
                      foreach($empleados as $key => $e)
                      {
                        //Se obtienen lo que se preguardo en hrs_ext y descuentos para q se carguen de nuevo
                        $prenomina = $this->nomina_fiscal_model->getPreNomina($e->id, $_GET['empresaId'], $_GET['anio'], $_GET['semana']);
                        $e->nomina->percepciones['horas_extras']['total'] = $e->nomina->percepciones['horas_extras']['total']==0?$prenomina['horas_extras']: $e->nomina->percepciones['horas_extras']['total'];
                        $e->horas_extras_dinero = $e->nomina->percepciones['horas_extras']['total'];
                        $e->descuento_playeras  = $prenomina['desc_playeras'];
                        $e->descuento_otros     = $prenomina['desc_otros'];
                        $e->descuento_cocina    = $prenomina['desc_cocina'];

                        $totalPercepcionesEmpleado = $e->nomina->percepciones['sueldo']['total'] +
                                             $e->nomina->percepciones['horas_extras']['total'] +
                                             $e->nomina->percepciones['premio_asistencia']['total'];
                                             // $e->nomina->percepciones['despensa']['total'];

                        $totalDeduccionesEmpleado = $e->nomina->deducciones['infonavit']['total'] +
                                            $e->nomina->deducciones['imss']['total'] +
                                            $e->nomina->deducciones['rcv']['total'] +
                                            (isset($e->nomina->deducciones['pencion_alimenticia']['total'])? $e->nomina->deducciones['pencion_alimenticia']['total']: 0) +
                                            (isset($e->nomina->deducciones['infonacot']['total'])? $e->nomina->deducciones['infonacot']['total']: 0) +
                                            $e->fondo_ahorro; //+
                                            //$e->descuento_playeras;

                        $dias_complemento = (($e->esta_asegurado=='f' && $e->nomina_guardada=='f'? $e->dias_trabajados-1: $e->dias_trabajados) * 6/ ($e->esta_asegurado=='f'?6:7) );
                        // Ajuste de nomina real con monto de Corona *********************
                        if (isset($e->monto_corona) && $e->monto_corona > 0) {
                          $e->salario_diario_real = $e->monto_corona / $dias_complemento;
                        }
                        $totalComplementoEmpleado = $dias_complemento * $e->salario_diario_real;

                        $bgColor = '';
                        $htmlLabel = '';
                        $sinCurp = false;
                        if (is_null($e->curp))
                        {
                          $sinCurp = true;
                          $bgColor = 'background-color: #E9A8A8;';
                          $htmlLabel = '<span class="label label-warning">SIN CURP</span>';
                        }

                        $readonly = '';
                        $disabled = '';
                        $generarNomina = '1';

                        $subsidioEmpleado = $e->nomina->otrosPagos['subsidio']['total'];
                        $subsidioAnualEmpleado = (isset($e->nomina->otrosPagos['subsidioAnual'])? $e->nomina->otrosPagos['subsidioAnual']['total']: 0);
                        $subsidioEmpleadoCausado = $e->nomina->otrosPagos['subsidio']['SubsidioAlEmpleo']['SubsidioCausado'];
                        $isrEmpleado = $e->nomina->deducciones['isr']['total'];
                        $isrAnualEmpleado = (isset($e->nomina->deducciones['isrAnual'])? $e->nomina->deducciones['isrAnual']['total']: 0);
                        // $ptuEmpleado = $e->nomina->percepciones['ptu']['total'];
                        $ptuEmpleado = 0;

                        // Si ya hay nominas generadas y la de este empleado tambien se genero.
                        if ($nominas_generadas && $e->esta_generada !== 'false')
                        {
                          $readonly = 'readonly';
                          $disabled = 'disabled';
                          $generarNomina = '0';
                          $subsidioEmpleado = $e->nomina_fiscal_subsidio;
                          $subsidioEmpleadoCausado = $e->nomina_fiscal_subsidio_causado;
                          $isrEmpleado = $e->nomina_fiscal_isr;

                          // $subsidioAnualEmpleado = (isset($e->nomina->otrosPagos['subsidioAnual'])? $e->nomina->otrosPagos['subsidioAnual']['total']: 0);
                          // $isrAnualEmpleado = (isset($e->nomina->deducciones['isrAnual'])? $e->nomina->deducciones['isrAnual']['total']: 0);
                          // $ptuEmpleado = $e->nomina_fiscal_ptu;
                        } elseif (!$nominas_generadas && $e->esta_guardada === 't') {
                          $generarNomina = '0';
                        }

                        if ($nominas_generadas && $e->esta_generada === 'false')
                        {
                          $bgColor = 'background-color: #EEBCBC;';
                        }

                        $activaVacaciones = 0;
                        if ($e->nomina_fiscal_vacaciones !== '0' && $e->esta_generada !== 'false')
                        {
                          $activaVacaciones = 1;
                        }

                        $activaAguinaldo = 0;
                        if ($e->nomina_fiscal_aguinaldo !== '0' && $e->esta_generada !== 'false')
                        {
                          $activaAguinaldo = 0;
                        }

                        if ($e->esta_generada === 'false')
                        {
                          $ultimoNoGenerado = $e->id;
                        }

                        // echo "<pre>";
                        //   var_dump($totalPercepcionesEmpleado, $ptuEmpleado);
                        // echo "</pre>";exit;

                        $totalPercepcionesEmpleado += $subsidioEmpleado + $ptuEmpleado + $subsidioAnualEmpleado;
                        $totalDeduccionesEmpleado += $isrEmpleado + $isrAnualEmpleado;

                        $utilidadEmpresa = $e->utilidad_empresa;

                        //vacaciones agregadas de asistencia
                        if($e->dias_vacaciones_fijo > 0)
                        {
                          $activaVacaciones = 1;
                        }
                    ?>
                      <tr class="tr-empleado" id="empleado<?php echo $e->id ?>">
                        <td style="position: absolute;background-color: #eee; z-index: 100; <?php echo $bgColor ?>">
                          <?php if($nominas_finalizadas || $nominas_generadas){
                              // if ($e->esta_generada !== 'false') {
                          ?>
                            <a href="<?php echo base_url('panel/nomina_fiscal/cancelar/?empleadoId='.$e->id.'&anio='.$_GET['anio'].'&semana='.$_GET['semana'].'&empresaId='.$_GET['empresaId'].'&fregistro_patronal='.$_GET['fregistro_patronal']) ?>"
                              onclick="if(confirm('Seguro de cancelar el comprobante de nomina?')){return true;}else{return false;}" title="Cancelar"><i class="icon-ban-circle" style="zoom: 1.5;color: red;"></i></a>
                              <br>
                          <?php
                              // }
                              if ($nominas_finalizadas) {
                          ?>
                            <a href="<?php echo base_url('panel/nomina_fiscal/recibo_nomina_pdf/?empleadoId='.$e->id.'&anio='.$_GET['anio'].'&semana='.$_GET['semana'].'&empresaId='.$_GET['empresaId'].'&fregistro_patronal='.$_GET['fregistro_patronal']) ?>" target="_blank" title="Ver PDF"><img src="<?php echo base_url('application/images/otros/doc_pdf.png') ?>" width="20" height="20"></a>
                          <?php
                              }
                          } ?>
                          <input type="checkbox" class="check-vacaciones <?php echo ($nominas_finalizadas || $nominas_generadas? 'hide': '') ?>" <?php echo $disabled ?>>
                          <input type="hidden" name="con_vacaciones[]" value="0" class="span12 con-vacaciones">
                          <input type="hidden" name="generar_nomina[]" value="<?php echo $generarNomina ?>" class="span12 generar-nomina">
                          <input type="hidden" name="hrs_trabajadas[]" value="<?php echo $e->hrs ?>" class="span12">
                          <input type="hidden" value="<?php echo $activaVacaciones ?>" class="span12 activa-vacaciones">
                          <input type="hidden" value="<?php echo $activaAguinaldo ?>" class="span12 activa-aguinaldo">
                          <?php if ($sinCurp){ ?>
                            <input type="hidden" value="1" class="span12 sin-curp">
                          <?php } ?>
                        </td>
                        <td style="background-color: #eee; height: 39px;width: 170px;padding-left: 40px!important;border-right: 1px #ccc solid; position: absolute; <?php echo $bgColor ?>">
                          <?php echo strtoupper($e->nombre) ?>
                          <?php echo $htmlLabel ?>
                          <input type="hidden" name="empleado_id[]" value="<?php echo $e->id ?>" class="span12 empleado-id">
                          <input type="hidden" name="esta_asegurado[]" value="<?php echo $e->esta_asegurado ?>" class="span12 empleado-esta_asegurado">
                          <input type="hidden" name="puesto_id[]" value="<?php echo $e->id_puesto ?>" class="span12">
                          <input type="hidden" name="departamento_id[]" value="<?php echo $e->id_departamente ?>" class="span12">
                          <input type="hidden" name="dcuenta_banco[]" value="<?php echo $e->cuenta_banco ?>" class="span12 empleado-cuenta_banco">
                        </td>
                        <td style="padding-left: 225px!important; <?php echo $bgColor ?>"><?php echo strtoupper($e->puesto) ?></td>
                        <td style="<?php echo $bgColor ?>">
                          <?php echo MyString::formatoNumero($e->esta_asegurado=='f'?$e->salario_diario_real:$e->salario_diario) ?>
                          <input type="hidden" name="salario_diario[]" value="<?php echo $e->esta_asegurado=='f'?$e->salario_diario_real:$e->salario_diario ?>" class="span12 salario-diario">
                          <input type="hidden" name="salario_diario_real[]" value="<?php echo $e->salario_diario_real ?>" class="span12 salario-diario-real">
                        </td>
                        <td style="<?php echo $bgColor ?>">
                          <?php echo $e->esta_asegurado=='f'?0:$e->nomina->salario_diario_integrado ?>
                        </td>
                        <td style="<?php echo $bgColor ?>">
                          <?php echo $e->esta_asegurado=='f'&&$e->nomina_guardada=='f'?$e->dias_trabajados-1:$e->dias_trabajados ?>
                          <input type="hidden" name="dias_trabajados[]" value="<?php echo $e->esta_asegurado=='f'&&$e->nomina_guardada=='f'?$e->dias_trabajados-1:$e->dias_trabajados ?>" class="span12 dias-trabajados">
                        </td>

                        <!-- Percepciones -->
                        <td style="<?php echo $bgColor ?>">
                          <?php echo MyString::formatoNumero($e->esta_asegurado=='f'?$totalComplementoEmpleado:$e->nomina->percepciones['sueldo']['total']) ?>
                          <input type="hidden" name="sueldo_semanal[]" value="<?php echo $e->esta_asegurado=='f'?$totalComplementoEmpleado:$e->nomina->percepciones['sueldo']['total'] ?>" class="span12 sueldo">
                          <input type="hidden" name="sueldo_semanal_real[]" value="<?php echo $totalComplementoEmpleado ?>" class="span12 sueldo-real">
                        </td>
                        <td id="td-premio_asistencia" style="<?php echo $bgColor ?>">
                          <span class="premio_asistencia-span"><?php echo MyString::formatoNumero($e->esta_asegurado=='f'?0:$e->nomina->percepciones['premio_asistencia']['total']) ?></span>
                          <input type="hidden" name="premio_asistencia[]" value="<?php echo $e->esta_asegurado=='f'?0:$e->nomina->percepciones['premio_asistencia']['total'] ?>" class="span12 premio_asistencia">
                        </td>
                        <!-- <td id="td-despensa" style="<?php //echo $bgColor ?>">
                          <span class="despensa-span"><?php //echo MyString::formatoNumero($e->esta_asegurado=='f'?0:$e->nomina->percepciones['despensa']['total']) ?></span>
                          <input type="hidden" name="despensa[]" value="<?php //echo $e->esta_asegurado=='f'?0:$e->nomina->percepciones['despensa']['total'] ?>" class="span12 despensa">
                        </td> -->
                        <td id="td-vacaciones" style="display: none; <?php echo $bgColor ?>">
                          <span class="vacaciones-span">0</span>
                          <input type="hidden" value="<?php echo $e->nomina->vacaciones ?>" class="span12 vacaciones">
                        </td>
                        <td id="td-prima-vacacional" style="display: none; <?php echo $bgColor ?>">
                          <span class="prima-vacacional-span">0</span>
                          <input type="hidden" value="<?php echo $e->esta_asegurado=='f'?0:$e->nomina->prima_vacacional ?>" class="span12 prima-vacacional">
                        </td>
                        <td style="width: 60px; <?php echo $bgColor?>"><input type="text" name="horas_extras[]" class="span12 vpositive horas-extras" value="<?php echo $e->horas_extras_dinero ?>" <?php echo $e->esta_asegurado=='f'?'readonly':$readonly ?>></td>
                        <td id="td-aguinaldo" style="display: none; <?php echo $bgColor ?>">
                          <span class="aguinaldo-span"><?php echo MyString::formatoNumero(0) ?></span>
                          <input type="hidden" value="<?php echo $e->esta_asegurado=='f'?0:0 ?>" class="span12 aguinaldo">
                        </td>
                        <td id="td-subsidio" style="<?php echo $bgColor ?>">
                          <span class="subsidio-span"><?php echo MyString::formatoNumero($e->esta_asegurado=='f'?0:$subsidioEmpleado+$subsidioAnualEmpleado) ?></span>
                          <input type="hidden" name="subsidio[]" value="<?php echo $e->esta_asegurado=='f'?0:$subsidioEmpleado ?>" class="span12 subsidio">
                          <input type="hidden" name="subsidioCausado[]" value="<?php echo $e->esta_asegurado=='f'?0:$subsidioEmpleadoCausado ?>" class="span12 subsidioCausado">
                          <input type="hidden" name="subsidioAnual[]" value="<?php echo $e->esta_asegurado=='f'?0:$subsidioAnualEmpleado ?>" class="span12 subsidioAnual">
                        </td>
                        <td id="td-ptu" style="display: none; <?php echo $bgColor ?>">
                          <span class="ptu-span"><?php echo MyString::formatoNumero($e->esta_asegurado=='f'?0:$ptuEmpleado) ?></span>
                          <input type="hidden" name="ptu[]" value="<?php echo $e->esta_asegurado=='f'?0:$ptuEmpleado ?>" class="span12 ptu">
                        </td>
                        <td style="<?php echo $bgColor ?>">
                          <span class="total-percepciones-span"><?php echo MyString::formatoNumero($e->esta_asegurado=='f'?0:$totalPercepcionesEmpleado) ?></span>
                          <input type="hidden" name="total_percepciones[]" value="<?php echo (float)number_format($e->esta_asegurado=='f'?0:$totalPercepcionesEmpleado, 2, '.', '') ?>" class="span12 total-percepciones">
                        </td>

                        <!-- Deducciones -->
                        <td style="<?php echo $bgColor ?>">
                          <?php echo MyString::formatoNumero($e->esta_asegurado=='f'?0:$e->nomina->deducciones['infonavit']['total']) ?>
                          <input type="hidden" name="total_infonavit[]" value="<?php echo $e->esta_asegurado=='f'?0:$e->nomina->deducciones['infonavit']['total'] ?>" class="span12 infonavit">
                        </td>
                        <td style="<?php echo $bgColor ?>">
                          <?php echo MyString::formatoNumero($e->esta_asegurado=='f'?0:($e->nomina->deducciones['imss']['total'] + $e->nomina->deducciones['rcv']['total'])) ?>
                          <input type="hidden" value="<?php echo $e->esta_asegurado=='f'?0:($e->nomina->deducciones['imss']['total'] + $e->nomina->deducciones['rcv']['total']) ?>" class="span12 imss">
                        </td>
                        <td style="<?php echo $bgColor ?>"><!-- prestamos -->
                          <?php
                                $totalPrestamosEmpleado = 0;
                                $totalPrestamosEmpleadoEf = 0;
                                $totalDescuentoMaterial = 0;
                                if (floatval($e->nomina_fiscal_prestamos) != 'false')
                                {
                                  $totalPrestamosEmpleado = $e->nomina_fiscal_prestamos;
                                  $totalPrestamosEmpleadoEf = isset($e->otros_datos->totalPrestamosEf)? $e->otros_datos->totalPrestamosEf: 0;
                                  $totalDescuentoMaterial = isset($e->otros_datos->totalDescuentoMaterial)? $e->otros_datos->totalDescuentoMaterial: 0;
                                } elseif (isset($e->otros_datos) && (isset($e->otros_datos->totalPrestamosEf) || isset($e->otros_datos->totalDescuentoMaterial))) {
                                  $totalPrestamosEmpleadoEf = isset($e->otros_datos->totalPrestamosEf)? $e->otros_datos->totalPrestamosEf: 0;
                                  $totalDescuentoMaterial = isset($e->otros_datos->totalDescuentoMaterial)? $e->otros_datos->totalDescuentoMaterial: 0;
                                } else {
                                  foreach ($e->prestamos as $key => $prestamo) {
                                    if ($prestamo['tipo'] == 'ef' || $prestamo['tipo'] == 'efd') {
                                      $totalPrestamosEmpleadoEf += $prestamo['pago_semana_descontar'];
                                    } elseif ($prestamo['tipo'] == 'mt') {
                                      $totalDescuentoMaterial += $prestamo['pago_semana_descontar'];
                                    } else {
                                      $totalPrestamosEmpleado += $prestamo['pago_semana_descontar'];
                                    }
                                  }
                                }

                                $totalDeduccionesEmpleado += floatval($totalPrestamosEmpleado);
                          ?>
                          <?php echo MyString::formatoNumero($totalPrestamosEmpleado) ?>
                          <input type="hidden" name="total_prestamos[]" value="<?php echo $totalPrestamosEmpleado ?>" class="span12 prestamos">
                        </td>
                        <td style="width: 60px; <?php echo $bgColor ?>">
                          <span class="fondo_ahorro-span"><?php echo MyString::formatoNumero($e->esta_asegurado=='f'?0:$e->fondo_ahorro) ?></span>
                          <input type="hidden" name="fondo_ahorro[]" value="<?php echo $e->esta_asegurado=='f'?0:$e->fondo_ahorro ?>" class="span12 fondo_ahorro">
                        </td>
                        <td style="width: 60px; <?php echo $bgColor ?>">
                          <span class="pension_alimenticia-span"><?php echo MyString::formatoNumero($e->esta_asegurado=='f'? 0: (isset($e->nomina->deducciones['pencion_alimenticia']['total'])? $e->nomina->deducciones['pencion_alimenticia']['total']: 0)) ?></span>
                          <input type="hidden" name="pension_alimenticia[]" value="<?php echo $e->esta_asegurado=='f'? 0: (isset($e->nomina->deducciones['pencion_alimenticia']['total'])? $e->nomina->deducciones['pencion_alimenticia']['total']: 0) ?>" class="span12 pension_alimenticia">
                        </td>
                        <td style="width: 60px; <?php echo $bgColor ?>">
                          <span class="fonacot-span"><?php echo MyString::formatoNumero($e->esta_asegurado=='f'? 0: (isset($e->nomina->deducciones['infonacot']['total'])? $e->nomina->deducciones['infonacot']['total']: 0)) ?></span>
                          <input type="hidden" name="fonacot[]" value="<?php echo $e->esta_asegurado=='f'? 0: (isset($e->nomina->deducciones['infonacot']['total'])? $e->nomina->deducciones['infonacot']['total']: 0) ?>" class="span12 fonacot">
                        </td>
                        <td style="width: 60px; <?php echo $bgColor ?>">
                          <span class="isr-span"><?php echo MyString::formatoNumero($e->esta_asegurado=='f'?0:$isrEmpleado+$isrAnualEmpleado) ?></span>
                          <input type="hidden" name="isr[]" value="<?php echo $e->esta_asegurado=='f'?0:$isrEmpleado ?>" class="span12 isr">
                          <input type="hidden" name="isrAnual[]" value="<?php echo $e->esta_asegurado=='f'?0:$isrAnualEmpleado ?>" class="span12 isrAnual">
                        </td>
                        <td style="<?php echo $bgColor ?>">
                          <span class="total-deducciones-span"><?php echo MyString::formatoNumero($e->esta_asegurado=='f'?0:$totalDeduccionesEmpleado) ?></span>
                          <input type="hidden" name="total_deducciones[]" value="<?php echo (float)number_format($e->esta_asegurado=='f'?0:$totalDeduccionesEmpleado, 2, '.', '') ?>" class="span12 total-deducciones">
                        </td>

                        <!-- Total Nomina -->
                        <td style="<?php echo $bgColor ?>">
                          <span class="total-nomina-span"><?php
                            $ttotal_nomina = $e->esta_asegurado=='f'? 0: (floatval($totalPercepcionesEmpleado) - floatval($totalDeduccionesEmpleado));
                            $ttotal_nomina_cheques = 0;
                            if(($e->cuenta_banco == '' && $e->nomina_guardada == 'f') || ($e->cuenta_banco == '' && $e->esta_asegurado == 't' && $e->nomina_guardada == 't')){
                              $ttotal_nomina_cheques = $ttotal_nomina;
                              $ttotal_nomina = 0;
                            }
                            echo MyString::formatoNumero($ttotal_nomina);
                          ?></span>
                          <input type="hidden" name="ttotal_nomina[]" value="<?php echo (float)number_format($ttotal_nomina, 2, '.', '') ?>" class="span12 total-nomina">
                        </td>

                      <!-- Totales por fuera -->
                        <td style="<?php echo $bgColor ?>">
                          <?php echo MyString::formatoNumero($e->bonos) ?>
                          <input type="hidden" name="bonos[]" value="<?php echo $e->bonos ?>" class="span12 bonos">
                        </td>
                        <td style="<?php echo $bgColor ?>">
                          <?php echo MyString::formatoNumero($e->otros) ?>
                          <input type="hidden" name="otros[]" value="<?php echo $e->otros ?>" class="span12 otros">
                        </td>
                        <td style="<?php echo $bgColor ?>">
                          <?php echo MyString::formatoNumero($e->domingo) ?>
                          <input type="hidden" name="domingo[]" value="<?php echo $e->domingo ?>" class="span12 domingo">
                        </td>
                        <td style="width: 60px; <?php echo $bgColor ?>"><input type="text" name="descuento_playeras[]" value="<?php echo $e->descuento_playeras ?>" class="span12 vpositive descuento-playeras" <?php echo $readonly ?>></td><!-- desc playeras -->
                        <td style="width: 60px; <?php echo $bgColor ?>"><input type="text" name="descuento_otros[]" value="<?php echo $e->descuento_otros ?>" class="span12 vpositive descuento-otros" <?php echo $readonly ?>></td><!-- desc playeras -->
                        <td style="width: 60px; <?php echo $bgColor ?>"><input type="text" name="descuento_cocina[]" value="<?php echo $e->descuento_cocina ?>" class="span12 vpositive descuento-cocina" <?php echo $readonly ?>></td><!-- desc playeras -->
                        <td style="width: 60px; <?php echo $bgColor ?>"><input type="text" name="descuento_prestamoef[]" value="<?php echo $totalPrestamosEmpleadoEf ?>" class="span12 vpositive descuento-prestef" readonly></td><!-- desc playeras -->
                        <td style="width: 60px; <?php echo $bgColor ?>"><input type="text" name="descuento_materiales[]" value="<?php echo $totalDescuentoMaterial ?>" class="span12 vpositive descuento-material" readonly></td><!-- desc playeras -->
                        <!-- total por fuera -->
                        <?php

                            $totalComplementoEmpleado = $totalComplementoEmpleado +
                                                  $ttotal_nomina_cheques +
                                                  $e->bonos +
                                                  $e->otros +
                                                  $e->domingo -
                                                  ( $e->esta_asegurado=='f'?0:(floatval($totalPercepcionesEmpleado) - floatval($totalDeduccionesEmpleado)) ) -
                                                  ($e->esta_asegurado=='f'?0:$e->nomina->deducciones['infonavit']['total']) -
                                                  ($e->esta_asegurado=='f'?0:$e->fondo_ahorro) -
                                                  $totalPrestamosEmpleado -
                                                  $e->descuento_playeras -
                                                  $e->descuento_otros -
                                                  $e->descuento_cocina -
                                                  $totalPrestamosEmpleadoEf -
                                                  $totalDescuentoMaterial -
                                                  (isset($e->nomina->deducciones['pencion_alimenticia']['total'])? $e->nomina->deducciones['pencion_alimenticia']['total']: 0) -
                                                  (isset($e->nomina->deducciones['infonacot']['total'])? $e->nomina->deducciones['infonacot']['total']: 0);
                        ?>
                        <td style="<?php echo $bgColor ?>">
                          <span class="total-complemento-span"><?php echo MyString::formatoNumero($totalComplementoEmpleado) ?></span>
                          <input type="hidden" name="total_no_fiscal[]" class="span12 total-complemento" value="<?php echo $totalComplementoEmpleado ?>">
                        </td>
                      </tr>
                    <?php
                      $totalSalarios             += $e->esta_asegurado=='f'?$e->salario_diario_real:$e->salario_diario;
                      $totalPasistencia          += $e->esta_asegurado=='f'?0:$e->nomina->percepciones['premio_asistencia']['total'];
                      // $totalDespensa          += $e->esta_asegurado=='f'?0:$e->nomina->percepciones['despensa']['total'];
                      $totalSdi                  += $e->esta_asegurado=='f'?0:$e->nomina->salario_diario_integrado;
                      $totalDiasTrabajados       += $e->esta_asegurado=='f'&&$e->nomina_guardada=='f'?$e->dias_trabajados-1:$e->dias_trabajados;
                      $totalSueldos              += $e->esta_asegurado=='f'?$totalComplementoEmpleado:$e->nomina->percepciones['sueldo']['total'];
                      $totalVacaciones           += $e->esta_asegurado=='f'?0:$e->nomina->vacaciones;
                      $totalPrimasVacacionales   += $e->esta_asegurado=='f'?0:$e->nomina->prima_vacacional;
                      $totalHorasExtras          += $e->horas_extras_dinero;
                      $totalAguinaldos           += $e->esta_asegurado=='f'?0:0;
                      $totalSubsidios            += $e->esta_asegurado=='f'?0:$subsidioEmpleado+$subsidioAnualEmpleado;
                      $totalPtu                  += $e->esta_asegurado=='f'?0:$ptuEmpleado;
                      $totalPercepciones         += $e->esta_asegurado=='f'?0:$totalPercepcionesEmpleado;
                      $totalInfonavit            += $e->esta_asegurado=='f'?0:$e->nomina->deducciones['infonavit']['total'];
                      $totalFondoAhorro          += $e->esta_asegurado=='f'?0:$e->fondo_ahorro;
                      $totalPensionAlimenticia   += (isset($e->nomina->deducciones['pencion_alimenticia']['total'])? $e->nomina->deducciones['pencion_alimenticia']['total']: 0);
                      $totalFonacot              += (isset($e->nomina->deducciones['infonacot']['total'])? $e->nomina->deducciones['infonacot']['total']: 0);
                      $totalImss                 += $e->esta_asegurado=='f'?0:($e->nomina->deducciones['imss']['total'] + $e->nomina->deducciones['rcv']['total']);
                      $totalPrestamos            += $totalPrestamosEmpleado;
                      $totalDescuentoPlayeras    += $e->descuento_playeras;
                      $totalDescuentoOtros       += $e->descuento_otros;
                      $totalDescuentoCocina      += $e->descuento_cocina;
                      $ttotalPrestamosEmpleadoEf += $totalPrestamosEmpleadoEf;
                      $ttotalDescuentoMaterial   += $totalDescuentoMaterial;
                      $totalIsrs                 += $e->esta_asegurado=='f'?0:$isrEmpleado+$isrAnualEmpleado;
                      $totalDeducciones          += $e->esta_asegurado=='f'?0:$totalDeduccionesEmpleado;
                      $totalTransferencias       += $e->esta_asegurado=='f'?0:(floatval($totalPercepcionesEmpleado) - floatval($totalDeduccionesEmpleado));
                      $totalBonos                += $e->bonos;
                      $totalOtras                += $e->otros;
                      $totalDomingos             += $e->domingo;
                      $totalComplementos         += $totalComplementoEmpleado;
                    } ?>
                    <tr>
                      <td colspan="2" style="width:202px; position: absolute; background-color: #BCD4EE; text-align: right; font-weight: bold;border-right: 1px #ccc solid;">TOTALES</td>
                      <td style="padding-left: 215px!important; background-color: #BCD4EE; text-align: right; font-weight: bold;"></td>
                      <td id="totales-salarios" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalSalarios) ?></td>
                      <td id="totales-sdi" style="background-color: #BCD4EE;"><?php echo $totalSdi ?></td>
                      <td id="totales-dias-trabajados" style="background-color: #BCD4EE;"><?php echo $totalDiasTrabajados ?></td>
                      <td id="totales-sueldos" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalSueldos) ?></td>
                      <td id="totales-pasistencia" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalPasistencia) ?></td>
                      <!-- <td id="totales-despensa" style="background-color: #BCD4EE;"><?php //echo MyString::formatoNumero($totalDespensa) ?></td> -->
                      <td id="totales-vacaciones" style="background-color: #BCD4EE; display: none;"><?php echo MyString::formatoNumero($totalVacaciones) ?></td>
                      <td id="totales-prima-vacacional" style="background-color: #BCD4EE; display: none;"><?php echo MyString::formatoNumero($totalPrimasVacacionales) ?></td>
                      <td id="totales-horas-extras" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalHorasExtras) ?></td>
                      <td id="totales-aguinaldo" style="background-color: #BCD4EE; display: none;"><?php echo MyString::formatoNumero($totalAguinaldos) ?></td>
                      <td id="totales-subsidios" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalSubsidios) ?></td>
                      <td id="totales-ptus" style="background-color: #BCD4EE; display: none;"><?php echo MyString::formatoNumero($totalPtu) ?><input type="hidden" id="totales-ptu-input" value="<?php echo $utilidadEmpresa?>"></td>
                      <td id="totales-percepciones" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalPercepciones) ?></td>
                      <td id="totales-infonavit" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalInfonavit) ?></td>
                      <td id="totales-imss" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalImss) ?></td>
                      <td id="totales-prestamos" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalPrestamos) ?></td>
                      <td id="totales-prestamos" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalFondoAhorro) ?></td>
                      <td id="totales-pension-aliment" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalPensionAlimenticia) ?></td>
                      <td id="totales-fonacot" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalFonacot) ?></td>
                      <td id="totales-isrs" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalIsrs) ?></td>
                      <td id="totales-deducciones" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalDeducciones) ?></td>
                      <td id="totales-transferencias" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalTransferencias) ?></td>
                      <td id="totales-bonos" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalBonos) ?></td>
                      <td id="totales-otras" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalOtras) ?></td>
                      <td id="totales-domingo" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalDomingos) ?></td>
                      <td id="totales-descuento-playeras" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalDescuentoPlayeras) ?></td>
                      <td id="totales-descuento-otros" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalDescuentoOtros) ?></td>
                      <td id="totales-descuento-cocina" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalDescuentoCocina) ?></td>
                      <td id="totales-descuento-presef" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($ttotalPrestamosEmpleadoEf) ?></td>
                      <td id="totales-descuento-materi" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($ttotalDescuentoMaterial) ?></td>
                      <td id="totales-complementos" style="background-color: #BCD4EE;"><?php echo MyString::formatoNumero($totalComplementos) ?></td>
                    </tr>
                  </tbody>
              </table>
              <input type="hidden" value="<?php echo $ultimoNoGenerado ?>" id="ultimo-no-generado">
            </form>
          </div>
        </div><!--/span-->

      </div><!--/row-->
    </div><!--/#content.span10-->

<!-- Bloque de alertas -->
<?php if(isset($frm_errors)){
  if($frm_errors['msg'] != ''){
?>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function(){
    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
</script>
<?php }
}?>
<!-- Bloque de alertas -->
