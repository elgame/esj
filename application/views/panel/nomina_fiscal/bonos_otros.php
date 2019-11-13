<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="es" class="no-js"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

  <title><?php echo $seo['titulo'];?></title>
  <meta name="description" content="<?php echo $seo['titulo'];?>">
  <meta name="viewport" content="width=device-width">

<?php
  if(isset($this->carabiner)){
    $this->carabiner->display('css');
    $this->carabiner->display('base_panel');
    $this->carabiner->display('js');
  }
?>

  <!-- The HTML5 shim, for IE6-8 support of HTML5 elements -->
  <!--[if lt IE 9]>
    <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->

  <script type="text/javascript" charset="UTF-8">
    var base_url = "<?php echo base_url();?>";
  </script>
</head>
  <body>

    <div class="modal-header">
      <h3><?php echo $empleado['info'][0]->apellido_paterno.' '.$empleado['info'][0]->apellido_materno.' '.$empleado['info'][0]->nombre ?><button type="button" class="btn btn-info" title="Recargar" id="btn-refresh" style="float: right;"><i class="icon-refresh"></i></button></h3>
    </div><!--/modal-header -->

    <div class="modal-body" style="max-height: none;">
      <ul class="nav nav-tabs" id="myTab">
        <li class="active"><a href="#tab-bonos-otros">Bonos y Otros</a></li>
        <?php if ($this->usuarios_model->tienePrivilegioDe('', 'nomina_fiscal/add_prestamos/')){ ?>
          <li><a href="#tab-prestamos">Prestamos</a></li>
        <?php } ?>
        <li><a href="#tab-vacaciones">Vacaciones</a></li>
        <li><a href="#tab-incapacidades">Incapacidades</a></li>
      </ul>
      <div class="tab-content">
          <div class="tab-pane active" id="tab-bonos-otros">
            <form class="form-horizontal" action="<?php echo base_url('panel/nomina_fiscal/bonos_otros/?'.String::getVarsLink(array('msg'))); ?>" method="POST" id="form-bonos">
              <?php if (count($bonosOtros) > 0) { ?>
                <input type="hidden" name="existentes" value="1" id="existentes">
              <?php } ?>

                <div class="row-fluid" style="text-align: center;">
                  <div class="span12">
                    Dia
                    <select id="fecha">
                      <?php foreach ($dias as $key => $dia) { ?>
                        <option value="<?php echo $dia ?>"><?php echo (new DateTime)->createFromFormat('Y-m-d', $dia)->format('d-m-Y')." | {$nombresDias[$key]}" ?></option>
                      <?php } ?>
                    </select>
                    <button type="button" class="btn btn-success" id="btn-add-bono">Agregar Bono</button>
                    <button type="button" class="btn btn-success" id="btn-add-otro">Agregar Otro</button>
                  </div>
                </div>
                <br>
                <div class="row-fluid">
                  <div class="span12">
                    <table class="table table-striped table-bordered table-hover table-condensed" id="table-bonos-otros">
                      <thead>
                        <tr>
                          <th>Fecha</th>
                          <th>Cantidad</th>
                          <th>Tipo</th>
                          <th>Acción</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($bonosOtros as $key => $item) {
                          if($item->bono > 0) $cantidad = $item->bono;
                          elseif($item->otro > 0) $cantidad = $item->otro;
                          else $cantidad = $item->domingo;
                        ?>
                          <tr>
                            <td style="width: 200px;"><input type="text" name="fecha[]" value="<?php echo $item->fecha ?>" class="span12" readonly> </td>
                            <td style="width: 100px;"><input type="text" name="cantidad[]" value="<?php echo $cantidad ?>" class="span12 vpositive cantidad" required></td>
                            <td style="width: 200px;">
                              <select name="tipo[]" class="span12">
                                <option value="bono" <?php echo $item->bono !== '0' ? 'selected' : '' ?>>Bono</option>
                                <option value="otro" <?php echo $item->otro !== '0' ? 'selected' : '' ?>>Otro</option>
                                <option value="domingo">Domingo</option>
                              </select>
                            </td>
                            <td>
                              <button type="button" class="btn btn-danger btn-del-item"><i class="icon-trash"></i></button>
                            </td>
                          </tr>
                        <?php } ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-success" id="btn-guardar-bonos">Guardar</button>
                </div><!--/modal-footer -->
            </form><!--/form-horizontal -->
          </div>
          <div class="tab-pane" id="tab-prestamos">
            <form class="form-horizontal" action="<?php echo base_url('panel/nomina_fiscal/add_prestamos/?'.String::getVarsLink(array('msg'))); ?>" method="POST" id="form-prestamos">
              <?php if (count($prestamos) > 0) { ?>
                <input type="hidden" name="prestamos_existentes" value="1" id="prestamos-existentes">
              <?php } ?>

                <div class="row-fluid" style="text-align: center;">
                  <div class="span12">
                    Dia
                    <select id="fecha-prestamos">
                      <?php foreach ($dias as $key => $dia) { ?>
                        <option value="<?php echo $dia ?>"><?php echo (new DateTime)->createFromFormat('Y-m-d', $dia)->format('d-m-Y')." | {$nombresDias[$key]}" ?></option>
                      <?php } ?>
                    </select>
                    <button type="button" class="btn btn-success" id="btn-add-prestamo">Agregar Prestamo</button>
                  </div>
                </div>
                <br>
                <div class="row-fluid">
                  <div class="span12">
                    <table class="table table-striped table-bordered table-hover table-condensed" id="table-prestamos">
                      <thead>
                        <tr>
                          <th>Fecha</th>
                          <th>Cantidad</th>
                          <th>Pago semana</th>
                          <th>Fecha inicio pagos</th>
                          <th>Pausar</th>
                          <th>Tipo</th>
                          <th>Eliminar</th>
                        </tr>
                      </thead>
                      <tbody>
                        <?php foreach ($prestamos as $key => $prestamo) { ?>
                          <tr>
                            <td style="width: 200px;"><input type="text" name="fecha[]" value="<?php echo $prestamo->fecha ?>" class="span12" readonly> </td>
                            <td style="width: 100px;"><input type="text" name="cantidad[]" value="<?php echo $prestamo->prestado ?>" class="span12 vpositive cantidad" required></td>
                            <td style="width: 100px;"><input type="text" name="pago_semana[]" value="<?php echo $prestamo->pago_semana ?>" class="span12 vpositive pago-semana" required></td>
                            <td style="width: 200px;"><input type="date" name="fecha_inicia_pagar[]" value="<?php echo $prestamo->inicio_pago ?>" class="span12 vpositive" required></td>
                            <td style="width: 100px;">
                              <input type="hidden" name="id_prestamo[]" value="<?php echo $prestamo->id_prestamo; ?>">
                              <select name="pausarp[]" required style="width: 100px;">
                                <option value="f" <?php echo set_select('pausarp', 'f', false, $prestamo->pausado); ?>>Activo</option>
                                <option value="t" <?php echo set_select('pausarp', 't', false, $prestamo->pausado); ?>>Pausado</option>
                              </select>
                            </td>
                            <td style="width: 50px;">
                              <select name="tipo_efectico[]" required style="width: 50px;">
                                <option value="fi" <?php echo set_select('tipo_efectico', 'fi', false, $prestamo->tipo); ?>>Fiscal</option>
                                <option value="ef" <?php echo set_select('tipo_efectico', 'ef', false, $prestamo->tipo); ?>>Efectivo</option>
                              </select>
                            </td>
                            <td>
                              <input type="checkbox" name="eliminar_prestamo[]" value="<?php echo $prestamo->id_prestamo; ?>">
                            </td>
                          </tr>
                        <?php } ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-success" id="btn-guardar-prestamos">Guardar</button>
                </div><!--/modal-footer -->
            </form><!--/form-horizontal -->
          </div><!--/tab-pane -->
          <div class="tab-pane" id="tab-vacaciones">
            <form class="form-horizontal" action="<?php echo base_url('panel/nomina_fiscal/add_vacaciones/?'.String::getVarsLink(array('msg'))); ?>" method="POST" id="form-prestamos">
                <div class="row-fluid">
                  <div class="span12">
                    <table class="table table-striped table-bordered table-hover table-condensed" id="table-vacaciones">
                      <thead>
                        <tr>
                          <th>Fecha Salida</th>
                          <th>Fecha Regreso</th>
                          <th>Dias</th>
                          <th>Acción</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr>
                          <td style="width: 200px;"><input type="date" name="vfecha" value="<?php echo set_value('vfecha', (isset($vacaciones->anio)?$vacaciones->fecha:$semana['fecha_inicio']) ); ?>" class="span12 vfecha" required> </td>
                          <td style="width: 200px;"><input type="date" name="vfecha1" value="<?php echo set_value('vfecha1', (isset($vacaciones->anio)?$vacaciones->fecha_fin:$semana['fecha_inicio']) ); ?>" class="span12 vfecha1" required> </td>
                          <td style="width: 100px;"><input type="text" name="vdias" value="<?php echo set_value('vdias', (isset($vacaciones->anio)?$vacaciones->dias_vacaciones:0) ); ?>" class="span12 vpositive vdias" required></td>
                          <td style="width: 100px;">
                            <input type="hidden" id="vfechadefault" value="<?php echo str_replace('-', '/', $semana['fecha_inicio']); ?>">
                            <button type="button" class="btn btn-danger btn-del-item-vacacion"><i class="icon-trash"></i></button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-success" id="btn-guardar-prestamos">Guardar</button>
                </div><!--/modal-footer -->
            </form><!--/form-horizontal -->
          </div><!--/tab-pane -->
          <div class="tab-pane" id="tab-incapacidades">
            <form class="form-horizontal" action="<?php echo base_url('panel/nomina_fiscal/add_incapacidades/?'.String::getVarsLink(array('msg'))); ?>" method="POST" id="form-prestamos">
                <div class="row-fluid">
                  <div class="span12">
                    <div style="display:none" id="sat_incapacidades"><?php echo json_encode($sat_incapacidades); ?></div>
                    <button type="button" class="btn btn-success" id="btn-add-incapacidad">Agregar Incapacidad</button>

                    <table class="table table-striped table-bordered table-hover table-condensed" id="table-incapacidades">
                      <thead>
                        <tr>
                          <th>Folio</th>
                          <th>Tipo Incidencia</th>
                          <th>Fecha Ini</th>
                          <th>Dias Autz</th>
                          <th>Ramo Seguro</th>
                          <th>Control Inca</th>
                          <th>Acción</th>
                        </tr>
                      </thead>
                      <tbody>
                      <?php foreach ($incapacidades as $key => $incapacidad){ ?>
                        <tr>
                          <td style="width: 60px;"><input type="text" name="ifolio[]" value="<?php echo set_value('ifolio', (isset($incapacidad->tipo)?$incapacidad->folio:'') ); ?>" class="span12" required> </td>
                          <td style="width: 100px;">
                            <input type="hidden" name="iid_asistencia[]" value="<?php echo (isset($incapacidad->id_asistencia)?$incapacidad->id_asistencia:'') ?>">
                            <select name="itipo_inciden[]" class="span12">
                            <?php foreach ($sat_incapacidades as $key => $tipo) { ?>
                              <option value="<?php echo $tipo->id_clave ?>" <?php echo set_select('itipo_inciden', $tipo->id_clave, false, (isset($incapacidad->tipo)?$incapacidad->id_clave:'0')); ?>><?php echo $tipo->nombre ?></option>
                            <?php } ?>
                            </select>
                          </td>
                          <td style="width: 80px;"><input type="date" name="ifecha[]" value="<?php echo set_value('ifecha', (isset($incapacidad->tipo)?$incapacidad->fecha_ini:$semana['fecha_inicio']) ); ?>" class="span12 ifecha" required> </td>
                          <td style="width: 100px;"><input type="number" name="idias[]" value="<?php echo set_value('idias', (isset($incapacidad->tipo)?$incapacidad->dias_autorizados:'0') ); ?>" class="span12" required> </td>
                          <td style="width: 100px;">
                            <select name="iramo_seguro[]" class="span12">
                              <option value="Riesgo de Trabajo" <?php echo set_select('iramo_seguro', 'Riesgo de Trabajo', false, (isset($incapacidad->tipo)?$incapacidad->ramo_seguro:'0')); ?>>Riesgo de Trabajo</option>
                              <option value="Enfermedad General" <?php echo set_select('iramo_seguro', 'Enfermedad General', false, (isset($incapacidad->tipo)?$incapacidad->ramo_seguro:'0')); ?>>Enfermedad General</option>
                              <option value="Maternitad Prenatal" <?php echo set_select('iramo_seguro', 'Maternitad Prenatal', false, (isset($incapacidad->tipo)?$incapacidad->ramo_seguro:'0')); ?>>Maternitad Prenatal</option>
                              <option value="Maternitad Postnatal" <?php echo set_select('iramo_seguro', 'Maternitad Postnatal', false, (isset($incapacidad->tipo)?$incapacidad->ramo_seguro:'0')); ?>>Maternitad Postnatal</option>
                            </select>
                          </td>
                          <td style="width: 100px;">
                            <select name="icontrol_incapa[]" class="span12">
                              <option value="Unica" <?php echo set_select('icontrol_incapa', 'Unica', false, (isset($incapacidad->tipo)?$incapacidad->control_incapacidad:'0')); ?>>Unica</option>
                              <option value="Inicial" <?php echo set_select('icontrol_incapa', 'Inicial', false, (isset($incapacidad->tipo)?$incapacidad->control_incapacidad:'0')); ?>>Inicial</option>
                              <option value="Subsecuente" <?php echo set_select('icontrol_incapa', 'Subsecuente', false, (isset($incapacidad->tipo)?$incapacidad->control_incapacidad:'0')); ?>>Subsecuente</option>
                              <option value="Alta Medica o ST-2" <?php echo set_select('icontrol_incapa', 'Alta Medica o ST-2', false, (isset($incapacidad->tipo)?$incapacidad->control_incapacidad:'0')); ?>>Alta Medica o ST-2</option>
                              <option value="Prenatal" <?php echo set_select('icontrol_incapa', 'Prenatal', false, (isset($incapacidad->tipo)?$incapacidad->control_incapacidad:'0')); ?>>Prenatal</option>
                              <option value="Postnatal" <?php echo set_select('icontrol_incapa', 'Postnatal', false, (isset($incapacidad->tipo)?$incapacidad->control_incapacidad:'0')); ?>>Postnatal</option>
                              <option value="Valuacion o ST-3" <?php echo set_select('icontrol_incapa', 'Valuacion o ST-3', false, (isset($incapacidad->tipo)?$incapacidad->control_incapacidad:'0')); ?>>Valuacion o ST-3</option>
                            </select>
                          </td>
                          <td style="width: 100px;">
                            <input type="checkbox" name="eliminar_incapacidad[]" value="<?php echo $incapacidad->id_asistencia; ?>"> Eliminar
                          </td>
                        </tr>
                      <?php } ?>
                      </tbody>
                    </table>
                  </div>
                </div>
                <div class="modal-footer">
                  <button type="submit" class="btn btn-success" id="btn-guardar-prestamos">Guardar</button>
                </div><!--/modal-footer -->
            </form><!--/form-horizontal -->
          </div><!--/tab-pane -->
      </div><!--/tab-content -->
    </div><!--/modal-body -->

    </div>

<!-- Bloque de alertas -->
<?php if(isset($frm_errors)){
  if($frm_errors['msg'] != ''){
?>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function(){

    <?php if (isset($close)) {?>
        setInterval(function() {
          window.parent.$('#supermodal').modal('hide');
          window.parent.location.reload();
      }, 2000);
    <?php }?>

    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
</script>
<?php }
}?>
<!-- Bloque de alertas -->

  </body>
</html>