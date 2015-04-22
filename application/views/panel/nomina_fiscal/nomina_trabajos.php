    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/nomina_fiscal/'); ?>">Nomina</a> <span class="divider">/</span>
          </li>
          <li>
            Trabajos
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-file"></i> Trabajos</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content" id="box-content">
            <form action="<?php echo base_url('panel/nomina_trabajos'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters">
                <label for="empresa">Empresa</label>
                <input type="text" name="empresa" class="input-xlarge search-query" id="empresa" value="<?php echo set_value_get('empresa', $empresaDefault->nombre_fiscal); ?>" size="73">
                <input type="hidden" name="empresaId" id="empresaId" value="<?php echo set_value_get('empresaId', $empresaDefault->id_empresa); ?>">

                <label for="ffecha" style="margin-top: 15px;">Dia</label>
                <input type="date" name="ffecha" id="ffecha" value="<?php echo $fecha ?>">

                <input type="submit" name="enviar" value="Buscar" class="btn">
              </div>
            </form>


            <form action="<?php echo base_url('panel/nomina_fiscal/addAsistencias/?'.String::getVarsLink(array('msg'))); ?>" method="POST" class="form">
              <input type="hidden" id="fdia_semana" value="<?php echo date('N'); ?>">
              <?php
                foreach ($puestos['puestos'] as $puesto) {
                  $tuvoEmpleados = false;
                  if ( ! isset($_GET['puestoId']) || ($_GET['puestoId'] == $puesto->id_departamento || $_GET['puestoId'] == '')) {
                ?>
                    <table class="table table-striped table-bordered bootstrap-datatable tableClasif">
                      <caption style="text-align: left;"><?php echo $puesto->nombre; ?></caption>
                      <thead>
                        <tr>
                          <th>Nombre</th>
                          <th>Centro Costo</th>
                          <th>Labor(s)</th>
                          <th>Horas</th>
                          <th>Hrs Extras</th>
                          <th>Descripcion</th>
                          <th>Costo</th>
                          <th></th>
                        </tr>
                      </thead>
                      <tbody>
                    <?php foreach($empleados as $e) {
                            if ($puesto->id_departamento === $e->id_departamente) {
                              $tuvoEmpleados = true;
                    ?>
                          <tr class="trempleado">
                            <td class="empleado-dbl-click"><?php echo $e->nombre; ?></td>
                            <td class="">
                              <input type="text" id="fcentro_costo" value="<?php echo isset($infoE[$e->id])? $infoE[$e->id]->area: ''; ?>" class="span11 pull-left showCodigoAreaAuto">
                              <input type="hidden" id="fcentro_costo_id" value="<?php echo isset($infoE[$e->id])? $infoE[$e->id]->id_area: ''; ?>" class="span12">
                              <i class="ico icon-list pull-right showCodigoArea" style="cursor:pointer"></i>
                              <input type="hidden" id="fempleado_id" value="<?php echo $e->id ?>">
                              <input type="hidden" id="fsalario_diario" value="<?php echo $e->salario_diario ?>">
                            </td>
                            <td colspan="2">
                              <table>
                                <tbody>
                              <?php if (isset($infoE[$e->id])) {
                                foreach ($infoE[$e->id]->labores as $keyinf => $ie) {
                              ?>
                                  <tr>
                                    <td>
                                      <input type="text" id="flabor<?php echo $e->id ?>" value="<?php echo $ie['labor'] ?>" class="span12 showLabores">
                                      <input type="hidden" id="flabor<?php echo $e->id ?>_id" value="<?php echo $ie['id_labor'] ?>" class="span12 hideLabor">
                                    </td>
                                    <td>
                                      <input type="text" id="fhoras<?php echo $e->id ?>" value="<?php echo $ie['horas'] ?>" class="span11 pull-left vpositive laborhoras">
                                      <i class="ico pull-right <?php echo $keyinf==0? 'icon-plus addNewlabor': 'icon-remove removelabor'; ?>" style="cursor:pointer"></i>
                                    </td>
                                  </tr>
                              <?php
                                }
                              }else{ ?>
                                  <tr>
                                    <td>
                                      <input type="text" id="flabor<?php echo $e->id ?>" value="" class="span12 showLabores">
                                      <input type="hidden" id="flabor<?php echo $e->id ?>_id" value="" class="span12 hideLabor">
                                    </td>
                                    <td>
                                      <input type="text" id="fhoras<?php echo $e->id ?>" value="" class="span11 pull-left vpositive laborhoras">
                                      <i class="ico icon-plus pull-right addNewlabor" style="cursor:pointer"></i>
                                    </td>
                                  </tr>
                              <?php } ?>
                                </tbody>
                              </table>
                            </td>
                            <td>
                              <input type="text" id="fhrs_extras" value="<?php echo isset($infoE[$e->id])? $infoE[$e->id]->hrs_extra: ''; ?>" class="span12 fhrs_extras<?php echo $e->id ?> vpositive">
                            </td>
                            <td>
                              <input type="text" id="fdescripcion" value="<?php echo isset($infoE[$e->id])? $infoE[$e->id]->descripcion: ''; ?>" class="span12">
                            </td>
                            <td>
                              <input type="text" id="fcosto" value="<?php echo isset($infoE[$e->id])? $infoE[$e->id]->importe: ''; ?>" class="span12 vpositive" readonly>
                              <input type="hidden" id="fhrs_trabajo" value="<?php echo isset($infoE[$e->id])? $infoE[$e->id]->horas: ''; ?>">
                              <input type="hidden" id="fhrs_trabajo_importe" value="<?php echo isset($infoE[$e->id])? $infoE[$e->id]->importe_trabajo: ''; ?>">
                              <input type="hidden" id="fhrs_extra_importe" value="<?php echo isset($infoE[$e->id])? $infoE[$e->id]->importe_extra: ''; ?>">
                            </td>
                            <td><button type="button" class="btn btn-success" id="btnAddClasif">Guardar</button></td>
                          </tr>
                      <?php }} ?>

                    <?php if ( ! $tuvoEmpleados){ ?>
                        <tr style="color: red;">
                          <td>Sin Registros</td>
                          <td style="width: 100px;"></td>
                          <td style="width: 100px;"></td>
                          <td style="width: 100px;"></td>
                          <td style="width: 100px;"></td>
                          <td style="width: 100px;"></td>
                          <td style="width: 100px;"></td>
                          <td style="width: 100px;"></td>
                        </tr>
                    <?php } ?>
                      </tbody>
                    </table>
              <?php }} ?>
            </form>
          </div>
        </div><!--/span-->

      </div><!--/row-->
    </div><!--/#content.span10-->

    <!-- Modal -->
    <div id="modalAreas" class="modal modal-w70 hide fade" tabindex="-1" role="dialog" aria-labelledby="modalAreasLavel" aria-hidden="true">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
        <h3 id="modalAreasLavel">Catalogo de maquinaria, equipos e instalaciones</h3>
      </div>
      <div class="modal-body">

        <div class="row-fluid">

          <div>

        <?php foreach ($areas as $key => $value)
        { ?>
            <div class="span3" id="tblAreasDiv<?php echo $value->id_tipo ?>" style="display: none;">
              <table class="table table-hover table-condensed <?php echo ($key==0? 'tblAreasFirs': ''); ?>"
                  id="tblAreas<?php echo $value->id_tipo ?>" data-id="<?php echo $value->id_tipo ?>">
                <thead>
                  <tr>
                    <th style="width:10px;"></th>
                    <th>Codigo</th>
                    <th><?php echo $value->nombre ?></th>
                  </tr>
                </thead>
                <tbody>
                  <!-- <tr class="areaClick" data-id="" data-sig="">
                    <td><input type="radio" name="modalRadioSel" value="" data-uniform="false"></td>
                    <td>9</td>
                    <td>EMPAQUE</td>
                  </tr> -->
                </tbody>
              </table>
            </div>
        <?php
        } ?>

          </div>

        </div>

      </div>
      <div class="modal-footer">
        <button class="btn" data-dismiss="modal" aria-hidden="true">Cancelar</button>
        <button class="btn btn-primary" id="btnModalAreasSel">Seleccionar</button>
      </div>
    </div>
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
