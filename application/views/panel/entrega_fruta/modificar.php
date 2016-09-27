    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/entrega_fruta/'); ?>">Entrega de fruta</a> <span class="divider">/</span>
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
            <form action="<?php echo base_url('panel/entrega_fruta/modificar/?id='.$_GET['id']); ?>" method="post" class="form-horizontal" id="formprovee">
              <fieldset>
                <legend></legend>
                <?php
                  $data = $data['info'];
                ?>

                <div class="span6">
                  <div class="control-group">
                    <label class="control-label" for="fnombre_fiscal">Folio </label>
                    <div class="controls">
                      <?php echo $data->folio; ?>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="ffecha">Fecha </label>
                    <div class="controls">
                      <input type="date" name="ffecha" id="ffecha" class="span12" value="<?php echo isset($data->fecha)?$data->fecha:''; ?>"
                        required autofocus>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fboleta"># Boleta </label>
                    <div class="controls">
                      <input type="number" name="fboleta" id="fboleta" class="span10" value="<?php echo isset($data->basc_boleta)?$data->basc_boleta:''; ?>" required>
                      <input type="hidden" name="fid_bascula" id="fid_bascula" class="span10" value="<?php echo isset($data->id_bascula)?$data->id_bascula:''; ?>">
                      <input type="hidden" name="fid_area" id="fid_area" class="span10" value="<?php echo isset($data->id_area)?$data->id_area:''; ?>">
                    </div>
                  </div>

                  <div class="control-group tipo3" id="productos">
                    <label class="control-label" for="codigoArea">Rancho </label>
                    <div class="controls">
                      <input type="text" name="codigoArea" value="<?php echo isset($data->rancho->id_cat_codigos)?$data->rancho->nombre_full:''; ?>" id="codigoArea" class="span12 showCodigoAreaAuto" required>
                      <input type="hidden" name="codigoAreaId" value="<?php echo isset($data->rancho->id_cat_codigos)?$data->rancho->id_cat_codigos:''; ?>" id="codigoAreaId" class="span12">
                      <input type="hidden" name="codigoCampo" value="id_cat_codigos" id="codigoCampo" class="span12">
                      <i class="ico icon-list showCodigoArea" style="cursor:pointer"></i>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fvehiculo">Transporte </label>
                    <div class="controls">
                      <input type="text" name="fvehiculo" class="span7 sikey" id="fvehiculo" value="<?php echo isset($data->vehiculo->id_vehiculo)?$data->vehiculo->placa.' '.$data->vehiculo->modelo.' '.$data->vehiculo->marca:''; ?>" placeholder="Vehiculos" style="float: left;" required>
                      <input type="hidden" name="vehiculoId" id="vehiculoId" value="<?php echo isset($data->vehiculo->id_vehiculo)?$data->vehiculo->id_vehiculo:''; ?>">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fchofer">Chofer </label>
                    <div class="controls">
                      <input type="text" name="fchofer" id="fchofer" class="span10" value="<?php echo isset($data->chofer[0]->id)?$data->chofer[0]->nombre.' '.$data->chofer[0]->apellido_paterno:''; ?>" required>
                      <input type="hidden" name="fchoferId" id="fchoferId" class="span10" value="<?php echo isset($data->chofer[0]->id)?$data->chofer[0]->id:''; ?>">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fencargado">Encargado </label>
                    <div class="controls">
                      <input type="text" name="fencargado" id="fencargado" class="span10" value="<?php echo isset($data->encargado[0]->id)?$data->encargado[0]->nombre.' '.$data->encargado[0]->apellido_paterno:''; ?>" required>
                      <input type="hidden" name="fencargadoId" id="fencargadoId" class="span10" value="<?php echo isset($data->encargado[0]->id)?$data->encargado[0]->id:''; ?>">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="frecibe">Recibe </label>
                    <div class="controls">
                      <input type="text" name="frecibe" id="frecibe" class="span10" value="<?php echo isset($data->recibe[0]->id)?$data->recibe[0]->nombre.' '.$data->recibe[0]->apellido_paterno:''; ?>" required>
                      <input type="hidden" name="frecibeId" id="frecibeId" class="span10" value="<?php echo isset($data->recibe[0]->id)?$data->recibe[0]->id:''; ?>">
                    </div>
                  </div>

                </div> <!--/span-->

                <div class="span11">
                          <table class="table table-striped table-bordered table-hover table-condensed">
                    <thead>
                      <tr>
                        <th>Clasf</th>
                        <th># Piso</th>
                        <th>Estibas</th>
                        <th>Melga</th>
                        <th>Piezas</th>
                        <th>OPC</th>
                      </tr>
                    </thead>
                    <tbody id="tableCuentas">
                    <?php if (count($data->fruta) > 0)
                    {
                      foreach ($data->fruta as $key => $value)
                      {
                    ?>
                      <tr>
                          <td>
                            <input type="text" name="prod_ddescripcion[]" class="span12" value="<?php echo $value->clasif; ?>" id="prod_ddescripcion">
                            <input type="hidden" name="prod_did_prod[]" class="span12" value="<?php echo $value->id_clasificacion; ?>" id="prod_did_prod">
                          </td>
                          <td><input type="text" name="prod_piso[]" value="<?php echo $value->piso; ?>" class="prod_piso vpositive"></td>
                          <td><input type="text" name="prod_estibas[]" value="<?php echo $value->estibas; ?>" class="prod_estibas vpositive"></td>
                          <td><input type="text" name="prod_altura[]" value="<?php echo $value->altura; ?>" class="prod_altura" maxlength="30"></td>
                          <td><input type="text" name="prod_cantidad[]" value="<?php echo $value->cantidad; ?>" class="prod_cantidad vpositive"></td>
                          <td><button type="button" class="btn btn-danger delProd"><i class="icon-remove"></i></button></td>
                      </tr>
                    <?php
                      }
                    } ?>

                    <?php if (is_array($this->input->post('cuentas_alias')))
                    {
                      foreach ($this->input->post('cuentas_alias') as $key => $value)
                      {
                    ?>
                      <tr>
                          <td>
                            <input type="text" name="prod_ddescripcion[]" class="span12" value="<?php echo $_POST['prod_ddescripcion'][$key]; ?>" id="prod_ddescripcion">
                            <input type="hidden" name="prod_did_prod[]" class="span12" value="<?php echo $_POST['prod_did_prod'][$key]; ?>" id="prod_did_prod">
                          </td>
                          <td><input type="text" name="prod_piso[]" value="<?php echo $_POST['prod_piso'][$key]; ?>" class="prod_piso vpositive"></td>
                          <td><input type="text" name="prod_estibas[]" value="<?php echo $_POST['prod_estibas'][$key]; ?>" class="prod_estibas vpositive"></td>
                          <td><input type="text" name="prod_altura[]" value="<?php echo $_POST['prod_altura'][$key]; ?>" class="prod_altura" maxlength="30"></td>
                          <td><input type="text" name="prod_cantidad[]" value="<?php echo $_POST['prod_cantidad'][$key]; ?>" class="prod_cantidad vpositive"></td>
                          <td><button type="button" class="btn btn-danger delProd"><i class="icon-remove"></i></button></td>
                      </tr>
                    <?php
                      }
                    } ?>
                      <tr>
                          <td>
                            <input type="text" name="prod_ddescripcion[]" class="span12" value="" id="prod_ddescripcion">
                            <input type="hidden" name="prod_did_prod[]" class="span12" value="" id="prod_did_prod">
                          </td>
                          <td><input type="text" name="prod_piso[]" value="" class="prod_piso vpositive"></td>
                          <td><input type="text" name="prod_estibas[]" value="" class="prod_estibas vpositive"></td>
                          <td><input type="text" name="prod_altura[]" value="" class="prod_altura" maxlength="30"></td>
                          <td><input type="text" name="prod_cantidad[]" value="" class="prod_cantidad vpositive"></td>
                          <td><button type="button" class="btn btn-danger delProd"><i class="icon-remove"></i></button></td>
                      </tr>

                    </tbody>
                  </table>
                        </div>

                <div class="clearfix"></div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary">Guardar</button>
                  <a href="<?php echo base_url('panel/entrega_fruta/'); ?>" class="btn">Cancelar</a>
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


