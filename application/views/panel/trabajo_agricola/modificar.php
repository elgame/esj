    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/btrabajo_agricola/'); ?>">Trabajo agricola</a> <span class="divider">/</span>
          </li>
          <li>Modificar</li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-edit"></i> Modificar formato</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content" id="table-productos">
            <form action="<?php echo base_url('panel/btrabajo_agricola/modificar/?id='.$_GET['id']); ?>" method="post" class="form-horizontal" id="formprovee">
              <fieldset>
                <legend></legend>
                <?php
                  $data = $data['info'];
                ?>

                <div class="span6">
                  <div class="control-group">
                    <label class="control-label" for="fnombre_fiscal">Empresa </label>
                    <div class="controls">
                      <?php echo $data->empresa->nombre_fiscal; ?>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fnombre_fiscal">Folio </label>
                    <div class="controls">
                      <?php echo $data->folio; ?>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="ffecha">Fecha </label>
                    <div class="controls">
                      <input type="date" name="ffecha" id="ffecha" class="span12" value="<?php echo isset($data->fecha_captura)?$data->fecha_captura:''; ?>"
                        required autofocus>
                    </div>
                  </div>

                  <div class="control-group tipo3" id="productos">
                    <label class="control-label" for="codigoArea">Vehiculo </label>
                    <div class="controls">
                      <input type="text" name="codigoArea" value="<?php echo isset($data->vehiculo->id_cat_codigos)?$data->vehiculo->nombre_full:''; ?>" id="codigoArea" class="span12 showCodigoAreaAuto" required>
                      <input type="hidden" name="codigoAreaId" value="<?php echo isset($data->vehiculo->id_cat_codigos)?$data->vehiculo->id_cat_codigos:''; ?>" id="codigoAreaId" class="span12">
                      <input type="hidden" name="codigoCampo" value="id_cat_codigos" id="codigoCampo" class="span12">
                      <i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="foperador">Operador </label>
                    <div class="controls">
                      <input type="text" name="foperador" id="foperador" class="span10" value="<?php echo isset($data->operador->id)?$data->operador->nombre.' '.$data->operador->apellido_paterno:''; ?>" required>
                      <input type="hidden" name="foperadorId" id="foperadorId" class="span10" value="<?php echo isset($data->operador->id)?$data->operador->id:''; ?>">
                    </div>
                  </div>

                </div> <!--/span-->

                <div class="span5">
                  <div class="control-group">
                    <label class="control-label" for="fhorometro_ini">Horometro Ini </label>
                    <div class="controls">
                      <input type="number" step="any" name="fhorometro_ini" id="fhorometro_ini" class="span10" value="<?php echo isset($data->horometro_ini)?$data->horometro_ini:''; ?>" required min="0">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fhorometro_fin">Horometro Fin </label>
                    <div class="controls">
                      <input type="number" step="any" name="fhorometro_fin" id="fhorometro_fin" class="span10" value="<?php echo isset($data->horometro_fin)?$data->horometro_fin:''; ?>" required min="0">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fhorometro_total">Total Kms </label>
                    <div class="controls">
                      <input type="number" step="any" name="fhorometro_total" id="fhorometro_total" class="span10" value="<?php echo isset($data->horometro_total)?$data->horometro_total:''; ?>" required readonly min="0">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fhr_ini">Hora Ini </label>
                    <div class="controls">
                      <input type="time" name="fhr_ini" id="fhr_ini" class="span10" value="<?php echo isset($data->hora_ini)?$data->hora_ini:''; ?>" required>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fhr_fin">Hora Fin </label>
                    <div class="controls">
                      <input type="time" name="fhr_fin" id="fhr_fin" class="span10" value="<?php echo isset($data->hora_fin)?$data->hora_fin:''; ?>" required>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fhr_total">Total Hrs </label>
                    <div class="controls">
                      <input type="text" name="fhr_total" id="fhr_total" class="span10" value="<?php echo isset($data->total_hrs)?$data->total_hrs:''; ?>" required readonly>
                    </div>
                  </div>

                </div>

                <div class="span11">
                  <table class="table table-striped table-bordered table-hover table-condensed tblproductos0">
                    <thead>
                      <tr>
                        <th>Hrs</th>
                        <th>Labor</th>
                        <th>Centro costo</th>
                        <th>OPC</th>
                      </tr>
                    </thead>
                    <tbody id="tableCuentas">
                    <?php if (count($data->labores) > 0)
                    {
                      foreach ($data->labores as $key => $value)
                      {
                    ?>
                      <tr>
                          <td>
                            <input type="time" name="ptiempo[]" class="span12" value="<?php echo $value->hora_ini; ?>" id="ptiempo">
                          </td>
                          <td>
                            <input type="text" name="plabor[]" value="<?php echo $value->labor; ?>" class="span12 prod_piso showLabores">
                            <input type="hidden" name="plaborId[]" id="plaborId" value="<?php echo $value->id_labor; ?>" class="span12 hideLabor">
                          </td>
                          <td>
                            <input type="text" name="ccosto[]" value="<?php echo $value->centro_costo; ?>" id="codigoArea" class="span12 showCodigoAreaAuto">
                            <input type="hidden" name="ccostoId[]" value="<?php echo $value->id_centro_costo; ?>" id="codigoAreaId" class="span12">
                            <input type="hidden" name="ccostoCampo[]" value="id_cat_codigos" id="codigoCampo" class="span12">
                            <i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>
                          </td>
                          <td><button type="button" class="btn btn-danger delProd"><i class="icon-remove"></i></button></td>
                      </tr>
                    <?php
                      }
                    } ?>

                    <?php if (is_array($this->input->post('plaborId')))
                    {
                      foreach ($this->input->post('plaborId') as $key => $value)
                      {
                    ?>
                      <tr>
                          <td>
                            <input type="time" name="ptiempo[]" class="span12" value="<?php echo $_POST['ptiempo'][$key]; ?>" id="ptiempo">
                          </td>
                          <td>
                            <input type="text" name="plabor[]" value="<?php echo $_POST['plabor'][$key]; ?>" class="span12 prod_piso showLabores">
                            <input type="hidden" name="plaborId[]" id="plaborId" value="<?php echo $_POST['plaborId'][$key]; ?>" class="span12 hideLabor">
                          </td>
                          <td>
                            <input type="text" name="ccosto[]" value="<?php echo $_POST['ccosto'][$key]; ?>" id="codigoArea" class="span12 showCodigoAreaAuto">
                            <input type="hidden" name="ccostoId[]" value="<?php echo $_POST['ccostoId'][$key]; ?>" id="codigoAreaId" class="span12">
                            <input type="hidden" name="ccostoCampo[]" value="id_cat_codigos" id="codigoCampo" class="span12">
                            <i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>
                          </td>
                          <td><button type="button" class="btn btn-danger delProd"><i class="icon-remove"></i></button></td>
                      </tr>
                    <?php
                      }
                    } ?>
                      <tr>
                          <td>
                            <input type="time" name="ptiempo[]" class="span12" value="" id="ptiempo">
                          </td>
                          <td>
                            <input type="text" name="plabor[]" value="" class="span12 prod_piso showLabores">
                            <input type="hidden" name="plaborId[]" id="plaborId" value="" class="span12 hideLabor">
                          </td>
                          <td>
                            <input type="text" name="ccosto[]" value="" id="codigoArea" class="span12 showCodigoAreaAuto">
                            <input type="hidden" name="ccostoId[]" value="" id="codigoAreaId" class="span12">
                            <input type="hidden" name="ccostoCampo[]" value="id_cat_codigos" id="codigoCampo" class="span12">
                            <i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>
                          </td>
                          <td><button type="button" class="btn btn-danger delProd"><i class="icon-remove"></i></button></td>
                      </tr>

                    </tbody>
                  </table>
                        </div>

                <div class="clearfix"></div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary">Guardar</button>
                  <a href="<?php echo base_url('panel/btrabajo_agricola/'); ?>" class="btn">Cancelar</a>
                </div>
              </fieldset>
            </form>

          </div>
        </div><!--/span-->

      </div><!--/row-->


          <!-- content ends -->
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


