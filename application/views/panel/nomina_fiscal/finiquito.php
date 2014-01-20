    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Finiquito
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-level-down"></i> Finiquito</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/nomina_fiscal/finiquito'); ?>" method="GET" class="form-search" id="form" target="">
              <div class="form-actions form-filters">
                <label for="empleado">Empleado</label>
                <input type="text" name="empleado" class="input-xlarge search-query" id="empleado" value="<?php echo set_value_get('empleado'); ?>">
                <input type="hidden" name="empleadoId" id="empleadoId" value="<?php echo set_value_get('empleadoId'); ?>">

                <label for="empleado">Fecha de Salida</label>
                <input type="date" name="fechaSalida" value="<?php echo set_value_get('fechaSalida', date('Y-m-d'))  ?>">
                <input type="submit" name="enviar" value="Cargar" class="btn">
              </div>
            </form>

            <form action="<?php echo base_url('panel/nomina_fiscal/add_finiquito/?'.String::getVarsLink(array('msg'))); ?>" method="POST" id="form">

              <div class="row-fluid">
                <div class="span11" style="text-align: center;">
                  <div style="font-size: 1.5em;">
                    CALCULO DE RENUNCIA VOLUNTARIA
                  </div>
                </div>
                <div class="span1">
                  <?php if (isset($empleado)) { ?>
                    <input type="submit" value="guardar" class="btn btn-success">
                  <?php } ?>
                </div>
              </div>

                <table class="table table-striped table-bordered bootstrap-datatable">
                  <thead>
                    <tr>
                      <th colspan="7"></th>
                      <th colspan="5" style="text-align: center;background-color: #BEEEBC;" id="head-percepciones">PERCEPCIONES</th>
                      <th colspan="2" style="text-align: center;background-color: #EEBCBC;" id="head-deducciones">DEDUCCIONES</th>
                      <th style="background-color: #BCD4EE;"></th>
                    </tr>
                    <tr>
                      <th>NOMBRE</th>
                      <th>PUESTO</th>
                      <th>FECHA ENTRADA</th>
                      <th>FECHA SALIDA</th>
                      <th>SALARIO DIARIO</th>
                      <th>DIAS TRAB. AÑO</th>
                      <th>AÑOS TRABAJADOS</th>

                      <!-- Percepciones -->
                      <th style="background-color: #BEEEBC;">SUELDO SEMANA</th>
                      <th id="head-vacaciones" style="background-color: #BEEEBC;">VACACIONES</th>
                      <th id="head-prima-vacacional" style="background-color: #BEEEBC;">P. VACACIONAL</th>
                      <th id="head-aguinaldo" style="background-color: #BEEEBC;">AGUINALDO</th>
                      <!-- <th style="background-color: #BEEEBC;">SUBSIDIO</th> -->
                      <th style="background-color: #BEEEBC;">TOTAL</th>

                      <!-- Deducciones -->
                      <th style="background-color: #EEBCBC;">ISR</th>
                      <th style="background-color: #EEBCBC;">TOTAL</th>

                      <!-- Total nomina -->
                      <th style="background-color: #BCD4EE;">TOTAL.</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (isset($empleado)) {
                        $sueldoSemana = $empleado[0]->dias_trabajados_semana * $empleado[0]->salario_diario;
                        $totalPercepciones = $sueldoSemana +
                                             $empleado[0]->nomina->vacaciones +
                                             $empleado[0]->nomina->prima_vacacional +
                                             $empleado[0]->nomina->aguinaldo;
                        $totalDeducciones = $empleado[0]->nomina->deducciones['isr']['total'];
                      ?>
                      <tr class="tr-empleado" id="empleado<?php echo $empleado[0]->id ?> ">
                        <td>
                          <?php echo strtoupper($empleado[0]->nombre) ?>
                          <input type="hidden" name="empleado_id[]" value="<?php echo $empleado[0]->id ?>" class="span12 empleado-id">
                        </td>
                        <td><?php echo strtoupper($empleado[0]->puesto) ?></td>
                        <td><?php echo strtoupper($empleado[0]->fecha_entrada) ?></td>
                        <td><?php echo strtoupper($empleado[0]->fecha_salida) ?></td>
                        <td>
                          <?php echo String::formatoNumero($empleado[0]->salario_diario) ?>
                          <input type="hidden" value="<?php echo $empleado[0]->salario_diario ?>" class="span12 salario-diario">
                        </td>
                        <td>
                          <?php echo $empleado[0]->dias_trabajados ?>
                          <input type="hidden" value="<?php echo $empleado[0]->dias_trabajados ?>" class="span12 dias-trabajados">
                        </td>
                        <td>
                          <?php echo $empleado[0]->anios_trabajados ?>
                          <input type="hidden" value="<?php echo $empleado[0]->anios_trabajados ?>" class="span12 anios-trabajados">
                        </td>

                        <!-- Percepciones -->
                        <td id="td-vacaciones">
                          <span class="vacaciones-span"><?php echo String::formatoNumero($sueldoSemana); ?></span>
                          <input type="hidden" value="<?php echo $sueldoSemana ?>" class="span12 vacaciones">
                        </td>
                        <td id="td-vacaciones">
                          <span class="vacaciones-span"><?php echo String::formatoNumero($empleado[0]->nomina->vacaciones); ?></span>
                          <input type="hidden" value="<?php echo $empleado[0]->nomina->vacaciones ?>" class="span12 vacaciones">
                        </td>
                        <td id="td-prima-vacacional">
                          <span class="prima-vacacional-span"><?php echo String::formatoNumero($empleado[0]->nomina->prima_vacacional); ?></span>
                          <input type="hidden" value="<?php echo $empleado[0]->nomina->prima_vacacional ?>" class="span12 prima-vacacional">
                        </td>
                        <td id="td-aguinaldo">
                          <span class="aguinaldo-span"><?php echo String::formatoNumero($empleado[0]->nomina->aguinaldo) ?></span>
                          <input type="hidden" value="<?php echo $empleado[0]->nomina->aguinaldo ?>" class="span12 aguinaldo">
                        </td>
                        <td>
                          <span class="total-percepciones-span"><?php echo String::formatoNumero($totalPercepciones) ?></span>
                          <input type="hidden" value="<?php echo (float)number_format($totalPercepciones, 2, '.', '') ?>" class="span12 total-percepciones">
                        </td>

                        <!-- Deducciones -->
                        <td style="width: 60px; ">
                          <span class="isr-span"><?php echo String::formatoNumero($empleado[0]->nomina->deducciones['isr']['total']) ?></span>
                          <input type="hidden" name="isr[]" value="<?php echo $empleado[0]->nomina->deducciones['isr']['total'] ?>" class="span12 isr">
                        </td>
                        <td>
                          <span class="total-deducciones-span"><?php echo String::formatoNumero($empleado[0]->nomina->deducciones['isr']['total']) ?></span>
                          <input type="hidden" value="<?php echo (float)number_format($empleado[0]->nomina->deducciones['isr']['total'], 2, '.', '') ?>" class="span12 total-deducciones">
                        </td>

                        <!-- Total Nomina -->
                        <td>
                          <span class="total-nomina-span"><?php echo String::formatoNumero(floatval($totalPercepciones) - floatval($totalDeducciones)) ?></span>
                          <input type="hidden" value="<?php echo (float)number_format(floatval($totalPercepciones) - floatval($totalDeducciones), 2, '.', '') ?>" class="span12 total-nomina">
                        </td>
                      </tr>
                    <?php } ?>
                  </tbody>
              </table>
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
