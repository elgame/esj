    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Nomina Ranchos
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
          <div class="box-content" style="overflow-x: auto; max-height: 600px; font-size: 0.9em;">
            <form action="<?php echo base_url('panel/nomina_ranchos/'); ?>" method="GET" class="form-search" id="form">
              <div class="form-actions form-filters">
                <label for="anio">Año</label>
                <input type="number" name="anio" class="search-query" id="anio" value="<?php echo set_value_get('anio', date("Y")); ?>">

                <label for="empresa">Empresa</label>
                <input type="text" name="empresa" class="input-xlarge search-query" id="empresa" value="<?php echo set_value_get('empresa', $empresaDefault->nombre_fiscal); ?>" size="73">
                <input type="hidden" name="empresaId" id="empresaId" value="<?php echo set_value_get('empresaId', $empresaDefault->id_empresa); ?>">

                <label for="ffecha1" style="margin-top: 15px;">Semana</label>
                <select name="semana" class="input-xlarge" id="semanas">
                  <?php foreach ($semanasDelAno as $semana) {
                      if ($semana['semana'] == $numSemanaSelected) {
                        $semana2 =  $semana;
                      }
                    ?>
                    <option value="<?php echo $semana['semana'] ?>" <?php echo $semana['semana'] == $numSemanaSelected ? 'selected' : '' ?>><?php echo "{$semana['semana']} - Del {$semana['fecha_inicio']} Al {$semana['fecha_final']}" ?></option>
                  <?php }
                    $_GET['anio'] = isset($_GET['anio']) ? $_GET['anio'] : date("Y");
                    $_GET['semana'] = isset($_GET['semana']) ? $_GET['semana'] : $semana2['semana'];
                    $_GET['empresaId'] = isset($_GET['empresaId']) ? $_GET['empresaId'] : $empresaDefault->id_empresa;
                    $_GET['empresa'] = isset($_GET['empresa']) ? $_GET['empresa'] : $empresaDefault->nombre_fiscal;
                  ?>
                </select>

               <!--  <label for="ffecha1" style="margin-top: 15px;">Puesto</label>
                  <select name="puestoId" class="input-large">
                    <option value=""></option>
                  <?php //foreach ($puestos as $puesto) { ?>
                    <option value="<?php //echo $puesto->id_puesto ?>" <?php //echo set_select_get('puestoId', $puesto->id_puesto) ?>><?php //echo $puesto->nombre ?></option>
                  <?php //} ?>
                </select> -->

                <input type="submit" name="enviar" value="Buscar" class="btn">

                <?php
                if ($show_prestamos) {
                ?>
                <a href="<?php echo base_url('panel/nomina_ranchos/quitar_prestamos?').String::getVarsLink(); ?>" class="btn btn-info pull-right"
                  onclick="msb.confirm('Estas seguro de quitar los prestamos para que se carguen de nuevo?', 'Nomina', this); return false;">Quitar prestamos</a>
                <?php } ?>
                <a href="<?php echo base_url('panel/nomina_ranchos/lista_asistencia?').String::getVarsLink(); ?>" class="btn btn-info pull-right" target="_blank">Imprimir Lista</a>
              </div>
            </form>

              <div class="row-fluid">
                <div class="span4" style="font-size: 1.3em;">
                </div>
                <div class="span5" style="text-align: center;">
                  <div style="font-size: 1.5em;">
                    <?php echo "Semana <span class=\"label\" style=\"font-size: 1em;\">{$semana2['semana']}</span> - Del <span style=\"font-weight: bold;\">{$semana2['fecha_inicio']}</span> Al <span style=\"font-weight: bold;\">{$semana2['fecha_final']}</span>" ?>
                  </div>
                </div>
                <div class="span3">
                  <?php
                  $readonly = 'readonly';
                  if ( ! $nominas_finalizadas){
                    $readonly = '';
                  ?>
                    <button type="button" name="guardarNominaR" class="btn btn-success" style="float: right;" id="guardarNominaR">Guardar</button>
                  <?php } ?>
                </div>
              </div>

               <input type="hidden" value="<?php echo $_GET['anio']; ?>" name="numAnio" id="numAnio">
                <input type="hidden" value="<?php echo $numSemanaSelected; ?>" name="numSemana" id="numSemana">
                <table class="table table-striped">
                  <thead>
                    <tr>
                      <th colspan="12"></th>
                      <th style="width:120px;"><label>AM $<input type="text" name="precio_am" id="precio_am" value="<?php echo (isset($empleados[0]->precio_lam)?$empleados[0]->precio_lam:0); ?>" class="span9 vpositive tchange" <?php echo $readonly ?>></label></th>
                      <th style="width:120px;"><label>Verde $<input type="text" name="precio_verde" id="precio_verde" value="<?php echo (isset($empleados[0]->precio_lvr)?$empleados[0]->precio_lvr:0); ?>" class="span9 vpositive tchange" <?php echo $readonly ?>></label></th>
                    </tr>
                    <tr>
                      <th>NOMBRE</th>
                      <th style="width:70px;">CC</th>
                      <th style="width:70px;">AM</th>
                      <th style="width:70px;">S</th>
                      <th style="width:70px;">L</th>
                      <th style="width:70px;">M</th>
                      <th style="width:70px;">M</th>
                      <th style="width:70px;">J</th>
                      <th style="width:70px;">V</th>
                      <th style="width:70px;">D</th>
                      <th style="width:100px;">Total AM</th>
                      <th style="width:100px;">Total V</th>
                      <th style="width:100px;">Prestamo</th>
                      <th style="width:100px;">Total</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $total_limon_am = $total_sabado = $total_lunes = $total_martes = $total_miercoles =
                    $total_jueves = $total_viernes = $total_domingo = $total_total_lam =
                    $total_total_lvrd = $total_total_pagar = $total_prestamos = $total_cajas_cargadas = 0;
                    foreach ($empleados as $key => $value)
                    {
                      $total_cajas_cargadas += $value->cajas_cargadas;
                      $total_limon_am += $value->sabado;
                      $total_sabado += $value->sabado;
                      $total_lunes += $value->lunes;
                      $total_martes += $value->martes;
                      $total_miercoles += $value->miercoles;
                      $total_jueves += $value->jueves;
                      $total_viernes += $value->viernes;
                      $total_domingo += $value->domingo;
                      $total_total_lam += $value->total_lam;
                      $total_total_lvrd += $value->total_lvrd;
                      $total_prestamos += $value->prestamo['total'];
                      $total_total_pagar += $value->total_pagar;
                    ?>
                    <tr class="tr_row" id="empleado<?php echo $value->id; ?>" data-generar="1">
                      <td><?php echo $value->nombre ?>
                        <input type="hidden" value="<?php echo $value->id; ?>" name="eId[]" id="eId">
                        <input type="hidden" value="<?php echo $value->generada; ?>" name="generada[]" id="generada">
                      </td>
                      <td><input type="text" name="cajas_cargadas[]" id="cajas_cargadas" value="<?php echo $value->cajas_cargadas; ?>" class="span11 vinteger tchange" <?php echo $readonly ?>></td>
                      <td><input type="text" name="limon_am[]" id="limon_am" value="<?php echo $value->total_lam; ?>" class="span11 vpositive tchange" <?php echo $readonly ?>></td>
                      <td><input type="text" name="sabado[]" id="sabado" value="<?php echo $value->sabado; ?>" class="span11 vpositive tchange" <?php echo $readonly ?>></td>
                      <td><input type="text" name="lunes[]" id="lunes" value="<?php echo $value->lunes; ?>" class="span11 vpositive tchange" <?php echo $readonly ?>></td>
                      <td><input type="text" name="martes[]" id="martes" value="<?php echo $value->martes; ?>" class="span11 vpositive tchange" <?php echo $readonly ?>></td>
                      <td><input type="text" name="miercoles[]" id="miercoles" value="<?php echo $value->miercoles; ?>" class="span11 vpositive tchange" <?php echo $readonly ?>></td>
                      <td><input type="text" name="jueves[]" id="jueves" value="<?php echo $value->jueves; ?>" class="span11 vpositive tchange" <?php echo $readonly ?>></td>
                      <td><input type="text" name="viernes[]" id="viernes" value="<?php echo $value->viernes; ?>" class="span11 vpositive tchange" <?php echo $readonly ?>></td>
                      <td><input type="text" name="domingo[]" id="domingo" value="<?php echo $value->domingo; ?>" class="span11 vpositive tchange" <?php echo $readonly ?>></td>
                      <td><input type="text" name="total_lam[]" id="total_lam" value="<?php echo $value->total_lam; ?>" class="span11" readonly></td>
                      <td><input type="text" name="total_lvrd[]" id="total_lvrd" value="<?php echo $value->total_lvrd; ?>" class="span11" readonly></td>
                      <td><input type="text" name="prestamo[]" id="prestamo" value="<?php echo $value->prestamo['total']; ?>" class="span11 vpositie tchange" readonly>
                        <input type="hidden" name="prestamos_ids[]" id="prestamos_ids" value="<?php echo $value->prestamo['prestamos_ids']; ?>" class="span11 vpositive tchange">
                      </td>
                      <td><input type="text" name="total_pagar[]" id="total_pagar" value="<?php echo $value->total_pagar; ?>" class="span11" readonly></td>
                    </tr>
                    <?php
                    } ?>
                    <tr>
                      <td style="background-color: #BCD4EE; text-align: right; font-weight: bold;">TOTALES</td>
                      <td id="total_cajas_cargadas" style="background-color: #BCD4EE;"><?php echo String::formatoNumero($total_cajas_cargadas, 2, ''); ?></td>
                      <td id="total_limon_am" style="background-color: #BCD4EE;"><?php echo String::formatoNumero($total_limon_am, 2, ''); ?></td>
                      <td id="total_sabado" style="background-color: #BCD4EE;"><?php echo String::formatoNumero($total_sabado, 2, ''); ?></td>
                      <td id="total_lunes" style="background-color: #BCD4EE;"><?php echo String::formatoNumero($total_lunes, 2, ''); ?></td>
                      <td id="total_martes" style="background-color: #BCD4EE;"><?php echo String::formatoNumero($total_martes, 2, ''); ?></td>
                      <td id="total_miercoles" style="background-color: #BCD4EE;"><?php echo String::formatoNumero($total_miercoles, 2, ''); ?></td>
                      <td id="total_jueves" style="background-color: #BCD4EE;"><?php echo String::formatoNumero($total_jueves, 2, ''); ?></td>
                      <td id="total_viernes" style="background-color: #BCD4EE;"><?php echo String::formatoNumero($total_viernes, 2, ''); ?></td>
                      <td id="total_domingo" style="background-color: #BCD4EE;"><?php echo String::formatoNumero($total_domingo, 2, ''); ?></td>
                      <td id="total_total_lam" style="background-color: #BCD4EE;"><?php echo String::formatoNumero($total_total_lam, 2, ''); ?></td>
                      <td id="total_total_lvrd" style="background-color: #BCD4EE;"><?php echo String::formatoNumero($total_total_lvrd, 2, ''); ?></td>
                      <td id="total_prestamos" style="background-color: #BCD4EE;"><?php echo String::formatoNumero($total_prestamos, 2, ''); ?></td>
                      <td id="total_total_pagar" style="background-color: #BCD4EE;"><?php echo String::formatoNumero($total_total_pagar, 2, '', false); ?></td>
                    </tr>
                  </tbody>
              </table>
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
