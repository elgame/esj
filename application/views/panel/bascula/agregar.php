    <div id="content" class="span10">
      <!-- content starts -->
      <?php
        $disabled = (($accion === 'p' || $accion === 'b') && $e === false) ? '' : '';

        $bmod = array(
          'tipo'       => '',
          'area'       => '',
          'empresa'    => '',
          'proveedor'  => '',
          'rancho'     => '',
          'camion'     => '',
          'chofer'     => '',
          'fecha'      => '',
          'k_bruto'    => '',
          'k_tara'     => '',
          'cajas_pres' => '',
          'pagar'      => '',
          'cajas'      => array('',''),
        );
        $readonly   = 'readonly';
        $crumbTitle = 'Agregar';
        $autorizarInput = '';
        $autorizarInput = '';
        // if ($e === true)
        if ($autorizar === true)
        {
          $readonly = '';
          $crumbTitle = 'Modificar';
          echo '<input type="hidden" id="isEditar" value="t" />';
          $autorizarInput = '<input type="hidden" name="autorizar" id="autorizar" value="" />';

          $bmod = array(
          'tipo'       => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mtipo/')?'':' disabled'),
          'area'       => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/marea/')?'':' disabled'),
          'empresa'    => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mempresa/')?'':' readonly'),
          'proveedor'  => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mproveedor/')?'':' readonly'),
          'rancho'     => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mrancho/')?'':' readonly'),
          'camion'     => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mcamion/')?'':' readonly'),
          'chofer'     => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mchofer/')?'':' readonly'),
          'fecha'      => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mfecha/')?'':' readonly'),
          'k_bruto'    => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mk_bruto/')?'':' readonly'),
          'k_tara'     => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mk_tara/')?'':' readonly'),
          'cajas_pres' => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mcajas_pres/')?'':' readonly'),
          'pagar'      => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mpagar/')?'':' disabled'),
          'fecha_pago' => $this->usuarios_model->tienePrivilegioDe('', 'bascula/mpagar_fecha/'),
          'cajas'      => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mcajas/')?array('',''): array(' disabled',' readonly')),
          );
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

        <?php if ($accion !== 'n' && $accion !== 'en' && isset($_POST['pcajas'])) { ?>
          <a href="<?php echo base_url('panel/bascula/show_view_agregar_lote/?idb='.$_GET['idb']) ?>" class="btn btn-warning" rel="superbox-40x480">Agregar Lote</a>
        <?php } ?>

        <a href="<?php echo base_url('panel/bascula/agregar/') ?>" class="btn btn-success pull-right" id="newPesada">
          Nueva Pesada
          <span class="label label-warning" style="margin: 5px 5px 0 0;">ESC</span>
        </a>

        <?php if ($autorizar) { ?>
          <a href="#myModalAuth" role="button" class="btn btn-primary pull-right" data-toggle="modal" id="btnGuardar" style="margin-right: 5px;">
            Guardar
            <span class="label label-warning" style="margin: 5px 5px 0 0;">ALT + G</span>
          </a>
        <?php } else {  ?>
          <button type="submit" class="btn btn-primary pull-right" <?php //echo $disabled ?> id="btnGuardar" style="margin-right: 5px;">
            Guardar
            <span class="label label-warning" style="margin: 5px 5px 0 0;">ALT + G</span>
          </button>
        <?php } ?>

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

        <?php echo $autorizarInput ?>
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
                  <?php if(isset($_GET['p']) || isset($_GET['f'])){ ?>
                  <input type="hidden" name="pno_lote" id="pno_lote" value="<?php echo $_POST['pno_lote']; ?>">
                  <?php } ?>
                  <div class="control-group" style="margin:0px 0px 2px 0px;">
                    <label class="control-label" for="ptipo">Certificado</label>
                    <div class="controls">
                      <input type="checkbox" name="certificado" id="certificado" value="1" data-uniform="false"  <?php echo set_checkbox('certificado', "1", isset($certificado) && $certificado == '1' ? true : false) ?> autofocus>
                    </div>
                  </div>

                  <div class="control-group" style="margin:0px 0px 2px 0px;">
                    <label class="control-label" for="ptipo">Tipo</label>
                    <div class="controls">
                      <select name="ptipo" class="input-xlarge" id="ptipo" <?php echo $disabled; ?>>
                        <option value="en" <?php $set_select=set_select('ptipo', 'en', false, $this->input->post('ptipo')); echo $set_select.($set_select==' selected="selected"'? '': $bmod['tipo']); ?>>Entrada</option>
                        <option value="sa" <?php $set_select=set_select('ptipo', 'sa', false, $this->input->post('ptipo')); echo $set_select.($set_select==' selected="selected"'? '': $bmod['tipo']); ?>>Salida</option>
                      </select>
                    </div>
                  </div>

                  <div class="control-group" style="margin:0px 0px 2px 0px;">
                    <label class="control-label" for="parea">Area</label>
                    <div class="controls">
                      <select name="parea" class="input-xlarge" id="parea" <?php echo $disabled; ?> data-next="<?php echo ($e === true? 'pfecha': 'pfolio'); ?>">
                        <option value="" <?php echo $bmod['area']; ?>></option>
                        <?php foreach ($areas['areas'] as $area){ ?>
                          <option value="<?php echo $area->id_area ?>" data-tipo="<?php echo $area->tipo; ?>"
                            <?php $set_select=set_select('parea', $area->id_area, false, isset($_POST['parea']) ? $_POST['parea'] : ($area->predeterminado == 't' ? $area->id_area: '') );
                             echo $set_select.($set_select==' selected="selected"'? '': $bmod['area']); ?>><?php echo $area->nombre ?></option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>

                  <div class="control-group" style="margin:0px 0px 2px 0px;">
                    <label class="control-label" for="pempresa">Empresa</label>
                    <div class="controls">
                      <input type="text" name="pempresa"
                        value="<?php echo set_value('pempresa', (isset($_POST['pempresa']) ? $_POST['pempresa'] : $empresa_default->nombre_fiscal)) ?>" id="pempresa" class="input-xlarge next" placeholder="Empresa" <?php echo $disabled.$bmod['empresa']; ?>>
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
                        value="<?php echo set_value('pproveedor', $this->input->post('pproveedor')) ?>" id="pproveedor" class="input-xlarge" placeholder="Proveedor" <?php echo $disabled.$bmod['proveedor']; ?>>
                      <span class="help-inline">
                        <a href="<?php echo base_url('panel/bascula/show_view_agregar_proveedor') ?>" class="btn" rel="superbox-90x550">Agregar</a>
                      </span>
                      <input type="hidden" name="pid_proveedor" value="<?php echo set_value('pid_proveedor', $this->input->post('pid_proveedor')) ?>" id="pid_proveedor">
                    </div>
                  </div>

                  <div class="control-group" id="groupProveedorRancho" style="margin:0px 0px 2px 0px;">
                    <label class="control-label" for="prancho">Rancho</label>
                    <div class="controls">
                      <input type="text" name="prancho" value="<?php echo set_value('prancho', $this->input->post('prancho')) ?>"
                        id="prancho" class="input-xlarge" placeholder="Rancho" <?php echo $disabled.$bmod['rancho']; ?>>
                    </div>
                  </div>

                  <div class="control-group" id="groupCliente" style="display: none;">
                    <label class="control-label" for="pcliente">Cliente</label>
                    <div class="controls">
                      <input type="text" name="pcliente" value="<?php echo set_value('pcliente', $this->input->post('pcliente')) ?>" id="pcliente"
                        class="input-xlarge sikey" data-replace="pproveedor" data-next="pcamion" placeholder="Cliente" <?php echo $disabled.$bmod['proveedor']; ?>>
                      <span class="help-inline">
                        <a href="<?php echo base_url('panel/bascula/show_view_agregar_cliente') ?>" class="btn" rel="superbox-80x550">Agregar</a>
                      </span>
                      <input type="hidden" name="pid_cliente" value="<?php echo set_value('pid_cliente', $this->input->post('pid_cliente')) ?>" id="pid_cliente">
                    </div>
                  </div>

                  <div class="control-group" style="margin:0px 0px 2px 0px;">
                    <label class="control-label" for="pcamion">Camión</label>
                    <div class="controls">
                      <input type="text" name="pcamion"
                        value="<?php echo set_value('pcamion', $this->input->post('pcamion')) ?>" id="pcamion" class="input-xlarge" placeholder="Placas" <?php echo  $disabled.$bmod['camion']; ?>>
                      <span class="help-inline">
                        <a href="<?php echo base_url('panel/bascula/show_view_agregar_camion') ?>" class="btn" rel="superbox-30x540" id="btnSupermodal">Agregar</a>
                      </span>
                      <input type="hidden" name="pid_camion" value="<?php echo set_value('pid_camion', $this->input->post('pid_camion')) ?>" id="pid_camion" value="">
                    </div>
                  </div>

                  <div class="control-group" style="margin:0px 0px 2px 0px;">
                    <label class="control-label" for="pchofer">Chofer</label>
                    <div class="controls">
                      <input type="text" name="pchofer"
                        value="<?php echo set_value('pchofer', $this->input->post('pchofer')) ?>" id="pchofer" class="input-xlarge" placeholder="Chofer" data-next="pkilos_brutos|pkilos_tara" <?php echo $disabled.$bmod['chofer']; ?>>
                      <span class="help-inline">
                        <a href="<?php echo base_url('panel/bascula/show_view_agregar_chofer') ?>" class="btn" rel="superbox-40x600">Agregar</a>
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
                        id="pfolio" class="input-medium vpos-int" style="text-align:center;" data-next="pfecha" <?php //echo ($e === true? ' readonly': ''); ?>>
                      <span class="help-inline">
                        <button class="btn" type="button" id="loadFolio" style="<?php //echo ($e === true? 'display:none': ''); ?>">Cargar</button>
                      </span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="pfecha">Fecha</label>
                    <div class="controls">
                      <input type="datetime-local" name="pfecha"
                        value="<?php echo set_value('pfecha', $fecha ); ?>" id="pfecha" class="span10" <?php echo $disabled.$bmod['fecha']; ?> data-next="pproveedor|pcliente">
                    </div>
                  </div>

                  <div class="control-group">
                    <!-- <label class="control-label">Finalizado?</label> -->
                    <div class="controls">
                      <button type="button" class="btn btn-success span10 <?php echo ($accion==='p' || $accion === 'b') ? 'active' : '' ?>" data-toggle="button"
                        id="pstatus" data-name="pstatus" data-value="1" <?php echo $disabled.$bmod['pagar']; ?>>Pagar</button>
                    </div>
                  </div>

                  <?php if ($accion === 'p' && $bmod['fecha_pago']) { ?>
                  <div class="control-group">
                    <label class="control-label">Fecha de pago</label>
                    <div class="controls">
                      <input type="datetime-local" name="pfecha_pago" value="<?php echo set_value('pfecha_pago', $fecha_pago ); ?>"
                        id="pfecha_pago" class="span10" <?php echo $disabled; ?>>
                    </div>
                  </div>
                  <?php } ?>

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
                        value="<?php echo set_value('pkilos_brutos', $this->input->post('pkilos_brutos')) ?>" <?php echo $disabled.$bmod['k_bruto'].' '.((($accion === 'n' && $e === false) || ($this->input->post('ptipo') === 'sa')) ? '' : $readonly) ?>>
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
                        value="<?php echo set_value('pkilos_tara', $this->input->post('pkilos_tara')) ?>" <?php echo $disabled.$bmod['k_tara'].' '.(((($accion === 'en' || $accion === 'sa') && $e === false) || ($this->input->post('ptipo') === 'en')) ? '' : $readonly) ?>>
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
                        value="<?php echo set_value('pcajas_prestadas', $this->input->post('pcajas_prestadas')) ?>" <?php echo $disabled.$bmod['cajas_pres']; ?>>
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
                                    <input type="hidden" name="pnum_registro[]" value="<?php echo $_POST['pnum_registro'][$key] ?>" id="pnum_registro">
                                    <input type="hidden" name="pcajas[]" value="<?php echo $caja ?>" id="pcajas">
                                    <input type="hidden" name="pcalidad[]" value="<?php echo $_POST['pcalidad'][$key] ?>" id="pcalidad">
                                    <input type="hidden" name="pcalidadtext[]" value="<?php echo $_POST['pcalidadtext'][$key] ?>" id="pcalidadtext">
                                    <!-- <input type="hidden" name="pkilos[]" value="<?php //echo $_POST['pkilos'][$key] ?>" id="pkilos"> -->
                                    <!-- <input type="hidden" name="ppromedio[]" value="<?php //echo $_POST['ppromedio'][$key] ?>" id="ppromedio"> -->
                                    <!-- <input type="hidden" name="pprecio[]" value="<?php //echo $_POST['pprecio'][$key] ?>" id="pprecio"> -->
                                    <input type="hidden" name="pimporte[]" value="<?php echo $_POST['pimporte'][$key] ?>" id="pimporte">
                                  </td>
                                  <td><?php echo $_POST['pcalidadtext'][$key] ?></td>
                                  <td id="tdkilos">

                                    <span><?php echo ($_POST['pkilos_neto'] > 300) ? $_POST['pkilos'][$key] : '' ?></span>
                                    <input type="<?php echo ($_POST['pkilos_neto'] > 300) ? 'hidden' : 'text' ?>" name="pkilos[]" value="<?php echo $_POST['pkilos'][$key] ?>" id="pkilos" style="width: 100px;">
                                  </td>
                                  <td id="tdpromedio">
                                    <input type="text" name="ppromedio[]" value="<?php echo $_POST['ppromedio'][$key] ?>" id="ppromedio" style="width: 80px;" <?php echo $bmod['cajas'][1]; ?>>
                                  </td>
                                  <td>
                                    <?php //echo $_POST['pprecio'][$key] ?>
                                    <input type="text" name="pprecio[]" value="<?php echo $_POST['pprecio'][$key] ?>" class="vpositive" id="pprecio" style="width: 80px;" <?php echo $bmod['cajas'][1]; ?>>
                                  </td>
                                  <td id="tdimporte"><?php echo $_POST['pimporte'][$key] ?></td>
                                  <td><button class="btn btn-info" type="button" title="Eliminar" id="delCaja" <?php echo $disabled.$bmod['cajas'][0]; ?>><i class="icon-trash"></i></button></td>
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

          <?php if ($autorizar) { ?>
            <a href="#myModalAuth" role="button" class="btn btn-primary" data-toggle="modal" id="btnGuardar" style="margin-right: 5px;">
              Guardar
              <span class="label label-warning" style="margin: 5px 5px 0 0;">ALT + G</span>
            </a>
          <?php } else {  ?>
            <button type="submit" class="btn btn-primary" <?php //echo $disabled ?> id="btnGuardar">Guardar</button>
          <?php } ?>

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
          <img src="<?php //echo $this->config->item('base_url_cam_salida_stream') ?>" width="320">
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
          <img src="<?php //echo $this->config->item('base_url_cam_salida_stream') ?>" width="320">
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
          <img src="<?php //echo $this->config->item('base_url_cam_salida_stream') ?>" width="320">
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

<!-- Modal -->
<div id="myModalAuth" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="myModalLabel">Autorizacion</h3>
  </div>
  <div class="modal-body">
    <div class="row-fluid" style="text-align: center;">
      <div class="input-prepend" title="Usuario" data-rel="tooltip">
        <span class="add-on"><i class="icon-user"></i>
        </span><input class="input-large span10" name="usuario" value="" id="usuario" type="text" placeholder="usuario">
      </div>
      <div class="clearfix"></div>

      <div class="input-prepend mtop" title="Contraseña" data-rel="tooltip">
        <span class="add-on"><i class="icon-lock"></i>
        </span><input class="input-large span10" name="pass" value="" id="pass" type="password" placeholder="******">
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
    <button class="btn btn-primary" id="btn-auth">Autorizar</button>
  </div>
</div>


<?php if (isset($ticket)) { ?>
  <script>
    var win=window.open(<?php echo "'".base_url('panel/bascula/imprimir/?id=' . $ticket."'") ?>, '_blank');
    win.focus();
  </script>
<?php } ?>

<?php if (isset($_GET['br']{0})) { ?>
  <script>
    // var win=window.open(<?php echo "'".base_url('panel/bascula/imprimir_recepcion/?id=' . $_GET['br']."'") ?>, '_blank');
    // win.focus();
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

