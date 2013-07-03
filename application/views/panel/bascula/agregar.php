    <div id="content" class="span10">
      <!-- content starts -->
      <?php
        $disabled = (($accion === 'p' || $accion === 'b') && $e === false) ? 'disabled' : '';

        $readonly   = 'readonly';
        $crumbTitle = 'Agregar';
        if ($e === true)
        {
          $readonly = '';
          $crumbTitle = 'Modificar';
          echo '<input type="hidden" id="isEditar" value="t" />';
        }

      ?>

      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/bascula/'); ?>">Bascula</a> <span class="divider">/</span>
          </li>
          <li><?php echo $crumbTitle ?></li>
        </ul>
      </div>

      <form action="<?php echo base_url('panel/bascula/agregar?'.String::getVarsLink(array('msg', 'fstatus', 'p', 'f'))); ?>" method="post" class="form-horizontal" id="form">

        <?php if ($accion === 'en') { ?>
          <button type="button" class="btn btn-info" id="btnSetFocoKilosTara">Cargar Kilos Tara</button>
        <?php } ?>

        <a href="<?php echo base_url('panel/bascula/agregar/') ?>" class="btn btn-success pull-right" id="newPesada">
          Nueva Pesada
          <span class="label label-warning" style="margin: 5px 5px 0 0;">ESC</span>
        </a>
        <button type="submit" class="btn btn-primary pull-right" <?php echo $disabled ?> id="btnGuardar" style="margin-right: 5px;">
          Guardar
          <span class="label label-warning" style="margin: 5px 5px 0 0;">ALT + G</span>
        </button>
        <?php
        if ($accion !== 'n')
          echo $this->usuarios_model->getLinkPrivSm('bascula/imprimir/', array(
              'params'   => 'id='.$idb,
              'btn_type' => 'btn-success pull-right',
              'attrs' => array('id' => 'btnPrint', 'target' => '_BLANK', 'style' => 'margin-right: 5px;'),
              'html' =>' <span class="label label-warning" style="margin: 5px 5px 0 0;">ALT + P</span>')
            );

        if ($accion === 'p' || $accion === 'b' || $accion === 'sa')
        {
          echo $this->usuarios_model->getLinkPrivSm('bascula/bonificacion/', array(
              'params'   => 'idb='.$idb,
              'btn_type' => 'btn-success pull-right',
              'attrs' => array('target' => '_BLANK', 'style' => 'margin-right: 5px;'))
            );
        }

        ?>

        <a href="<?php echo base_url('panel/bascula/'); ?>" class="btn pull-right" style="margin-right: 5px;">Cancelar</a>

        <input type="hidden" name="paccion" value="<?php echo $accion ?>" id="paccion">
        <input type="hidden" name="pidb" value="<?php echo $idb ?>" id="pidb">

        <?php if(isset($_GET['f'])) { ?>
          <input type="hidden" value="pfolio" id="kjfocus">
        <?php } ?>

        <div class="row-fluid"><!--Datos Bascula-->
          <div class="box span12">
            <div class="box-header well" data-original-title>
              <h2><i class="icon-road"></i> Datos Bascula</h2>
              <div class="box-icon">
                <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
              </div>
            </div>
            <div class="box-content">
              <div class="row-fluid">
                <div class="span7">
                  <div class="control-group">
                    <label class="control-label" for="ptipo">Tipo</label>
                    <div class="controls">
                      <select name="ptipo" class="input-xlarge" id="ptipo" <?php echo $disabled ?> autofocus>
                        <option value="en" <?php echo set_select('ptipo', 'en', false, $this->input->post('ptipo')) ?>>Entrada</option>
                        <option value="sa" <?php echo set_select('ptipo', 'sa', false, $this->input->post('ptipo')) ?>>Salida</option>
                      </select>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="parea">Area</label>
                    <div class="controls">
                      <select name="parea" class="input-xlarge" id="parea" <?php echo $disabled ?> data-next="pfolio">
                        <option value=""></option>
                        <?php foreach ($areas['areas'] as $area){ ?>
                          <option value="<?php echo $area->id_area ?>"
                            <?php echo set_select('parea', $area->id_area, false, isset($_POST['parea']) ? $_POST['parea'] : ($area->predeterminado == 't' ? $area->id_area: '') ) ?>><?php echo $area->nombre ?></option>
                        <?php } ?>
                      </select>
                      <!-- <span class="help-inline">
                        <a href="<?php// echo base_url('panel/areas/agregar') ?>" class="btn" rel="superbox-80x500">Agregar</a>
                      </span> -->
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="pempresa">Empresa</label>
                    <div class="controls">
                      <input type="text" name="pempresa"
                        value="<?php echo set_value('pempresa', (isset($_POST['pempresa']) ? $_POST['pempresa'] : $empresa_default->nombre_fiscal)) ?>" id="pempresa" class="input-xlarge next" placeholder="Empresa" <?php echo $disabled ?>>
                      <span class="help-inline">
                        <a href="<?php echo base_url('panel/bascula/show_view_agregar_empresa') ?>" class="btn" rel="superbox-80x500">Agregar</a>
                      </span>
                      <input type="hidden" name="pid_empresa" value="<?php echo set_value('pid_empresa', (isset($_POST['pid_empresa']) ? $_POST['pid_empresa'] : $empresa_default->id_empresa)) ?>" id="pid_empresa">
                    </div>
                  </div>

                  <div class="control-group" id="groupProveedor">
                    <label class="control-label" for="pproveedor">Proveedor</label>
                    <div class="controls">
                      <input type="text" name="pproveedor"
                        value="<?php echo set_value('pproveedor', $this->input->post('pproveedor')) ?>" id="pproveedor" class="input-xlarge" placeholder="Proveedor" <?php echo $disabled ?>>
                      <span class="help-inline">
                        <a href="<?php echo base_url('panel/bascula/show_view_agregar_proveedor') ?>" class="btn" rel="superbox-80x550">Agregar</a>
                      </span>
                      <input type="hidden" name="pid_proveedor" value="<?php echo set_value('pid_proveedor', $this->input->post('pid_proveedor')) ?>" id="pid_proveedor">
                    </div>
                  </div>

                  <div class="control-group" id="groupCliente" style="display: none;">
                    <label class="control-label" for="pcliente">Cliente</label>
                    <div class="controls">
                      <input type="text" name="pcliente"
                        value="<?php echo set_value('pcliente', $this->input->post('pcliente')) ?>" id="pcliente" class="input-xlarge sikey" data-replace="pproveedor" placeholder="Cliente" <?php echo $disabled ?>>
                      <span class="help-inline">
                        <a href="<?php echo base_url('panel/bascula/show_view_agregar_cliente') ?>" class="btn" rel="superbox-80x550">Agregar</a>
                      </span>
                      <input type="hidden" name="pid_cliente" value="<?php echo set_value('pid_cliente', $this->input->post('pid_cliente')) ?>" id="pid_cliente">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="pcamion">Camión</label>
                    <div class="controls">
                      <input type="text" name="pcamion"
                        value="<?php echo set_value('pcamion', $this->input->post('pcamion')) ?>" id="pcamion" class="input-xlarge" placeholder="Placas" <?php echo  $disabled ?>>
                      <span class="help-inline">
                        <a href="<?php echo base_url('panel/bascula/show_view_agregar_camion') ?>" class="btn" rel="superbox-50x480" id="btnSupermodal">Agregar</a>
                      </span>
                      <input type="hidden" name="pid_camion" value="<?php echo set_value('pid_camion', $this->input->post('pid_camion')) ?>" id="pid_camion" value="">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="pchofer">Chofer</label>
                    <div class="controls">
                      <input type="text" name="pchofer"
                        value="<?php echo set_value('pchofer', $this->input->post('pchofer')) ?>" id="pchofer" class="input-xlarge" placeholder="Chofer" data-next="pkilos_brutos|pkilos_tara" <?php echo $disabled ?>>
                      <span class="help-inline">
                        <a href="<?php echo base_url('panel/bascula/show_view_agregar_chofer') ?>" class="btn" rel="superbox-50x440">Agregar</a>
                      </span>
                      <input type="hidden" name="pid_chofer" value="<?php echo set_value('pid_chofer', $this->input->post('pid_chofer')) ?>" id="pid_chofer">
                    </div>
                  </div>
                </div><!--/span-->

                <div class="span5">
                  <div class="control-group">
                    <label class="control-label" for="pfolio">Folio</label>
                    <div class="controls">
                      <input type="text" name="pfolio" value="<?php echo set_value('pfolio', $next_folio) ?>"
                        id="pfolio" class="input-medium vpos-int" style="text-align:center;" data-next="pfecha">
                      <span class="help-inline">
                        <button class="btn" type="button" id="loadFolio">Cargar</button>
                      </span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="pfecha">Fecha</label>
                    <div class="controls">
                      <input type="datetime-local" name="pfecha"
                        value="<?php echo set_value('pfecha', $fecha ); ?>" id="pfecha" class="span10" <?php echo $disabled ?> data-next="pproveedor|pcliente">
                    </div>
                  </div>

                  <div class="control-group">
                    <!-- <label class="control-label">Finalizado?</label> -->
                    <div class="controls">
                      <button type="button" class="btn btn-success span10 <?php echo ($accion==='p' || $accion === 'b') ? 'active' : '' ?>" data-toggle="button"
                        id="pstatus" data-name="pstatus" data-value="1" <?php echo $disabled ?>>Pagar</button>
                    </div>
                  </div>

                  <div class="control-group">
                    <!-- <label class="control-label">Finalizado?</label> -->
                    <div class="controls">
                      <!-- <button type="button" class="btn btn-info span10" id="pfotos">Fotos</button> -->
                      <!-- <a href="#modalFotos" role="button" class="btn btn-info span10" data-toggle="modal">Fotos</a> -->
                    </div>
                  </div>
                </div><!--/span-->
              </div><!--/row-fluid-->

            </div><!--/box-content-->
          </div><!--/box span12-->
        </div><!--/row-fluid datos bascula-->

        <div class="row-fluid"><!--pesajes-->
          <div class="box span12">
            <div class="box-header well" data-original-title>
              <h2><i class="icon-road"></i> Pesajes</h2>
              <div class="box-icon">
                <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
              </div>
            </div>
            <div class="box-content">
              <div class="row-fluid">
                <div class="span3">
                  <div class="control-group">
                    <label class="control-label" for="pkilos_brutos" style="width: 100px;">Kilos Brutos <br><span class="label label-warning">ALT + B</span></label>
                    <div class="controls" style="margin-left: 115px;">
                      <input type="text" name="pkilos_brutos" id="pkilos_brutos" class="input-small vpositive"
                        value="<?php echo set_value('pkilos_brutos', $this->input->post('pkilos_brutos')) ?>" <?php echo $disabled.' '.(($accion === 'n' && $e === false) ? '' : $readonly) ?>>
                      <span class="help-inline">
                        <button type="button" class="btn btn-info" id="btnKilosBruto" data-loading-text="Cargando..." <?php echo $disabled ?> style="display: none;">Cargar</button>
                      </span>
                    </div>
                  </div>
                </div>
                <div class="span3">
                  <div class="control-group">
                    <label class="control-label" for="pkilos_tara" style="width: 100px;">Kilos Tara <br> <span class="label label-warning">ALT + T</span> </label>
                    <div class="controls" style="margin-left: 115px;">
                      <input type="text" name="pkilos_tara" id="pkilos_tara" class="input-small vpositive"
                        value="<?php echo set_value('pkilos_tara', $this->input->post('pkilos_tara')) ?>" <?php echo $disabled.' '.((($accion === 'en' || $accion === 'sa') && $e === false) ? '' : $readonly) ?>>
                      <span class="help-inline">
                        <button type="button" class="btn btn-info" id="btnKilosTara" data-loading-text="Cargando..." <?php echo $disabled ?> style="display: none;">Cargar</button>
                      </span>
                    </div>
                  </div>
                </div>
                <div class="span3">
                  <div class="control-group">
                    <label class="control-label" for="pcajas_prestadas" style="width: 100px;">Cajas Prestadas</label>
                    <div class="controls" style="margin-left: 115px;">
                      <input type="text" name="pcajas_prestadas" id="pcajas_prestadas" class="input-small vpos-int"
                        value="<?php echo set_value('pcajas_prestadas', $this->input->post('pcajas_prestadas')) ?>">
                    </div>
                  </div>
                </div>
                <div class="span3">
                  <div class="control-group">
                    <label class="control-label" for="pkilos_neto" style="width: 100px;">Kilos Neto</label>
                    <div class="controls" style="margin-left: 115px;">
                      <input type="text" name="pkilos_neto" id="pkilos_neto" class="input-small vpositive"
                        value="<?php echo set_value('pkilos_neto', $this->input->post('pkilos_neto')) ?>" readonly <?php echo $disabled ?>>
                    </div>
                  </div>
                </div>
              </div>
            </div><!--/box-content-->
          </div><!--/box span12-->
        </div><!--/row-fluid pesajes-->

        <div class="row-fluid" id="box-cajas"><!--cajas-->
          <div class="box span12">
            <div class="box-header well" data-original-title>
              <h2><i class="icon-road"></i> Cajas <span class="label label-warning">ALT + C</span></h2>
              <div class="box-icon">
                <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
              </div>
            </div>
            <div class="box-content">
              <div class="row-fluid">
                <div class="span1">
                  <label>Cajas</label>
                  <input type="text" id="icajas" class="span11 vpos-int" <?php echo $disabled ?>>
                </div>
                <div class="span2">
                  <label>Calidad</label>
                  <select class="input-medium" id="icalidad" <?php echo $disabled ?>>

                  </select>
                </div>
                <!-- <div class="span2">
                  <label>Kilos</label>
                  <input type="text" id="ikilos" class="input-medium vpositive">
                </div>
                <div class="span2">
                  <label>Promedio</label>
                  <input type="text" id="ipromedio" class="input-medium vpositive">
                </div> -->
                <div class="span2">
                  <label>Precio</label>
                  <input type="text" id="iprecio" class="input-medium vpositive" <?php echo $disabled ?>>
                </div>
               <!--  <div class="span2">
                  <label>Importe</label>
                  <input type="text" id="iimporte" class="input-medium vpositive">
                </div> -->
                <div class="span1">
                  <a href="javascript:void(0)" id="addCaja"><i class="icon-plus-sign-alt icon-4x"></i></a>
                </div>
              </div>
              <br>
              <div class="row-fluid">
                <div class="span12">

                  <table class="table table-striped table-bordered table-hover" id="tableCajas">
                    <thead>
                      <tr>
                        <th>Cajas</th>
                        <th>Calidad</th>
                        <th>Kilos</th>
                        <th>Promedio</th>
                        <th>Precio</th>
                        <th>Importe</th>
                        <th>Opc</th>
                      </tr>

                    </thead>
                    <tbody>
                      <!-- <tr>
                        <td>12
                          <input type="hidden" name="pcajas[]" value="">
                          <input type="hidden" name="pcalidad[]" value="">
                          <input type="hidden" name="pkilos[]" value="">
                          <input type="hidden" name="ppromedio[]" value="">
                          <input type="hidden" name="pprecio[]" value="">
                          <input type="hidden" name="pimporte" value="">
                        </td>
                        <td>asdfg</td>
                        <td>12</td>
                        <td>12</td>
                        <td>12</td>
                        <td>12</td>
                        <td><button class="btn btn-info" type="button" title="Eliminar" id="delCaja"><i class="icon-trash"></i></button></td>
                      </tr> -->
                      <?php if (isset($_POST['pcajas'])) {
                              foreach ($_POST['pcajas'] as $key => $caja) {
                      ?>
                                <tr data-kneto="">
                                  <td><?php echo $caja ?>
                                    <input type="hidden" name="pcajas[]" value="<?php echo $caja ?>" id="pcajas">
                                    <input type="hidden" name="pcalidad[]" value="<?php echo $_POST['pcalidad'][$key] ?>" id="pcalidad">
                                    <input type="hidden" name="pcalidadtext[]" value="<?php echo $_POST['pcalidadtext'][$key] ?>" id="pcalidadtext">
                                    <input type="hidden" name="pkilos[]" value="<?php echo $_POST['pkilos'][$key] ?>" id="pkilos">
                                    <!-- <input type="hidden" name="ppromedio[]" value="<?php //echo $_POST['ppromedio'][$key] ?>" id="ppromedio"> -->
                                    <!-- <input type="hidden" name="pprecio[]" value="<?php //echo $_POST['pprecio'][$key] ?>" id="pprecio"> -->
                                    <input type="hidden" name="pimporte[]" value="<?php echo $_POST['pimporte'][$key] ?>" id="pimporte">
                                  </td>
                                  <td><?php echo $_POST['pcalidadtext'][$key] ?></td>
                                  <td id="tdkilos"><?php echo $_POST['pkilos'][$key] ?></td>
                                  <td id="tdpromedio">
                                    <input type="text" name="ppromedio[]" value="<?php echo $_POST['ppromedio'][$key] ?>" id="ppromedio" style="width: 80px;">
                                  </td>
                                  <td>
                                    <?php //echo $_POST['pprecio'][$key] ?>
                                    <input type="text" name="pprecio[]" value="<?php echo $_POST['pprecio'][$key] ?>" class="vpositive" id="pprecio" style="width: 80px;">
                                  </td>
                                  <td id="tdimporte"><?php echo $_POST['pimporte'][$key] ?></td>
                                  <td><button class="btn btn-info" type="button" title="Eliminar" id="delCaja" <?php echo $disabled ?>><i class="icon-trash"></i></button></td>
                                </tr>

                      <?php }} ?>
                    </tbody>
                  </table>

                </div>
              </div>

              <div class="row-fluid">
                <div class="span12">
                  <div class="span4">
                    <label for="ptotal_cajas">Total cajas</label>
                    <input type="text" name="ptotal_cajas"
                      value="<?php echo set_value('ptotal_cajas', $this->input->post('ptotal_cajas')) ?>" id="ptotal_cajas" class="input-medium vpositive" <?php echo $disabled ?> readonly>
                  </div>

                  <div class="span4">
                    <label for="ppesada">Pesada</label>
                    <input type="text" name="ppesada"
                      value="<?php echo set_value('ppesada', $this->input->post('ppesada')) ?>" id="ppesada" class="input-medium vpositive nokey" <?php echo $disabled ?>>
                  </div>

                  <div class="span4">
                    <label for="ptotal">Total</label>
                    <input type="text" name="ptotal"
                      value="<?php echo set_value('ptotal', $this->input->post('ptotal')) ?>" id="ptotal" class="input-medium vpositive" <?php echo $disabled ?> readonly>
                  </div>
                </div>
              </div>
              <br>
              <div class="row-fluid">
                <div class="span12">
                  <label class="" for="pobcervaciones">Descripción</label>
                  <textarea name="pobcervaciones" id="pobcervaciones" class="span6" rows="5" <?php echo $disabled ?>><?php echo set_value('pobcervaciones', $this->input->post('pobcervaciones')) ?></textarea>
                </div>
              </div>

            </div><!--/box-content-->
          </div><!--/box span12-->
        </div><!--/row-fluid cajas-->

        <div class="form-actions">
          <span class="label label-warning" style="margin: 5px 5px 0 0;">ESC</span>
          <button type="submit" class="btn btn-primary" <?php echo $disabled ?> id="btnGuardar">Guardar</button>

          <?php
              if ($accion !== 'n')
                echo $this->usuarios_model->getLinkPrivSm('bascula/imprimir/', array(
                    'params'   => 'id='.$idb,
                    'btn_type' => 'btn-success',
                    'attrs' => array('id' => 'btnPrint', 'target' => '_BLANK'),
                    'html' =>' <span class="label label-warning" style="margin: 5px 5px 0 0;">ALT + P</span>')
                  );
            ?>

          <a href="<?php echo base_url('panel/bascula/'); ?>" class="btn">Cancelar</a>
        </div>
      </form>


          <!-- content ends -->
    </div><!--/#content.span10-->


<!-- Modal -->
<!-- <div id="modalFotos" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="modalFotosLabel" aria-hidden="true" style="width: 760px;left: 44%;">

  <div class="modal-body">

    <fieldset class="span4">
      <legend style="margin-bottom: 3px;">Camara Salida</legend>
      <div class="row-fluid">
        <div class="span12">
          <button class="btn pull-right" type="button" id="btnCamera" data-name="pimgsalida"><i class="icon-camera"></i></button>
        </div>
        <div class="span12">
          <img src="<?php echo $this->config->item('base_url_cam_salida_stream') ?>" width="320">
        </div>
        <div class="span12" id="snapshot"></div>
      </div>
    </fieldset>

    <fieldset class="span4">
      <legend style="margin-bottom: 3px;">Camara Entrada</legend>
      <div class="row-fluid">
        <div class="span12">
          <button class="btn pull-right" type="button" id="btnCamera" data-name="pimgentrada"><i class="icon-camera"></i></button>
        </div>
        <div class="span12">
          <img src="<?php echo $this->config->item('base_url_cam_salida_stream') ?>" width="320">
        </div>
        <div class="span12" id="snapshot"></div>
      </div>
    </fieldset>

    <fieldset class="span4">
      <legend style="margin-bottom: 3px;">Camara Entrada 2</legend>
      <div class="row-fluid">
        <div class="span12">
          <button class="btn pull-right" type="button" id="btnCamera" data-name="pimgentrada2"><i class="icon-camera"></i></button>
        </div>
        <div class="span12">
          <img src="<?php echo $this->config->item('base_url_cam_salida_stream') ?>" width="320">
        </div>
        <div class="span12" id="snapshot"></div>
      </div>
    </fieldset>


    <canvas id="myCanvas"/>

  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
    <button class="btn btn-primary">Save changes</button>
  </div>
</div> -->


<?php if (isset($ticket)) { ?>
  <script>
    var win=window.open(<?php echo "'".base_url('panel/bascula/imprimir/?id=' . $ticket."'") ?>, '_blank');
    win.focus();
  </script>
<?php } ?>

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

