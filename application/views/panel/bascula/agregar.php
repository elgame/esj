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
          'fecha'      => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mfecha/')? '':' readonly'),
          'k_bruto'    => '',
          'k_tara'     => '',
          'cajas_pres' => '',
          'pagar'      => '',
          'cajas'      => array('',''),
          'metodo_pago' => '',
          'intangible'  => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/aintangibles/')? 'block': 'none'),
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
          'tipo'        => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mtipo/')?'':' disabled'),
          'metodo_pago' => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mtipo/')?'':' disabled'),
          'area'        => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/marea/')?'':' disabled'),
          'empresa'     => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mempresa/')?'':' readonly'),
          'proveedor'   => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mproveedor/')?'':' readonly'),
          'rancho'      => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mrancho/')?'':' readonly'),
          'camion'      => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mcamion/')?'':' readonly'),
          'chofer'      => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mchofer/')?'':' readonly'),
          'fecha'       => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mfecha/')? '':' readonly'),
          'k_bruto'     => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mk_bruto/')?'':' readonly'),
          'k_tara'      => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mk_tara/')?'':' readonly'),
          'cajas_pres'  => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mcajas_pres/')?'':' readonly'),
          'pagar'       => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mpagar/')?'':' disabled'),
          'fecha_pago'  => $this->usuarios_model->tienePrivilegioDe('', 'bascula/mpagar_fecha/'),
          'cajas'       => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/mcajas/')?array('',''): array(' disabled',' readonly')),
          'intangible'  => ($this->usuarios_model->tienePrivilegioDe('', 'bascula/aintangibles/')? 'block': 'none'),
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

      <form action="<?php echo base_url('panel/bascula/agregar?'.MyString::getVarsLink(array('msg', 'fstatus', 'p', 'f'))); ?>" method="post" class="form-horizontal" id="form">
        <input type="hidden" id="userId" value="<?php echo $this->session->userdata('id_usuario') ?>">

        <?php if ($accion === 'en') { ?>
          <button type="button" class="btn btn-info" id="btnSetFocoKilosTara">Cargar Kilos Tara</button>
        <?php } ?>

        <?php if ($accion !== 'n' && $accion !== 'en' && isset($_POST['pcajas'])) { ?>
          <a href="<?php echo base_url('panel/bascula/show_view_agregar_lote/?idb='.$_GET['idb']) ?>" class="btn btn-warning" rel="superbox-40x480">Agregar Lote</a>
          <?php if (isset($_POST['parea_nom']) && $_POST['parea_nom'] == 'PIÑA MIEL'): ?>
            <a href="<?php echo base_url('panel/bascula_pina/show_view_guardar_pina/?idb='.$_GET['idb']) ?>" class="btn" rel="superbox-80x500">Agregar Entrada Piña</a>
          <?php endif ?>
        <?php } ?>

        <?php if ($accion !== 'n' && $accion !== 'en' && $_POST['parea_nom'] == 'INSUMOS MT' && isset($_POST['pcajas'])) { ?>
          <a href="<?php echo base_url('panel/bascula/show_view_ligar_orden/?idb='.$_GET['idb']) ?>" class="btn btn-warning" rel="superbox-70x480">Ligar orden</a>
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
          <button type="button" class="btn btn-primary pull-right" <?php //echo $disabled ?> id="btnGuardar" style="margin-right: 5px;">
            Guardar
            <span class="label label-warning" style="margin: 5px 5px 0 0;">ALT + G</span>
          </button>
        <?php }

        if (isset($idb)) {
        ?>
          <a href="<?php echo base_url('panel/bascula/imprimir_recepcion/?id='.$idb) ?>" class="btn btn-primary" title="Recepción" target="_blank">Recepción</a>
          <div class="btn-group">
            <button class="btn dropdown-toggle" data-toggle="dropdown">Fotos <span class="caret"></span></button>
            <ul class="dropdown-menu">
          <?php
          if (isset($fotos)) {
            foreach ($fotos as $key => $value) {
              $nombre = ($value->tipo=='en'? 'Entrada': 'Salida')." Cam {$value->no_camara}";
          ?>
              <li><a href="<?php echo base_url($value->url_foto) ?>" target="_blank"><?php echo $nombre ?></a></li>
          <?php
            }
          } ?>
            </ul>
          </div>
        <?php
        }
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

        <input type="hidden" id="modif_kilosbt" value="<?php echo $modkbt? 'true':'false' ?>">

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
                  <?php if( ( $accion === 'sa' || $accion === 'f' || $accion === 'p' || $accion === 'b') &&
                            ($this->input->post('ptipo') === 'en') ){ ?>
                  <input type="hidden" name="pno_lote" id="pno_lote" value="<?php echo $_POST['pno_lote']; ?>">
                  <?php } ?>
                  <div class="span12">
                    <div class="control-group span4" style="margin:0px 0px 2px 0px;">
                      <label class="control-label" for="ptipo">Certificado</label>
                      <div class="controls">
                        <input type="checkbox" name="certificado" id="certificado" value="1" data-uniform="false"  <?php echo set_checkbox('certificado', "1", isset($certificado) && $certificado == '1' ? true : false) ?> autofocus>
                      </div>
                    </div>
                    <div class="control-group span4" style="margin:0px 0px 2px 0px; display: <?php echo $bmod['intangible'] ?>">
                      <label class="control-label" for="intangible">Intangible</label>
                      <div class="controls">
                        <input type="checkbox" name="intangible" id="intangible" value="1" data-uniform="false"  <?php echo set_checkbox('intangible', "1", isset($intangible) && $intangible == '1' ? true : false) ?>>
                      </div>
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
                          <option value="<?php echo $area->id_area ?>" data-tipo="<?php echo $area->tipo; ?>" data-coco="<?php echo ($area->nombre == 'COCOS'? 't': 'f') ?>"
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

                  <div class="control-group" id="groupProveedorTabla" style="margin:0px 0px 2px 0px;">
                    <label class="control-label" for="ptabla">Tabla/Lote</label>
                    <div class="controls">
                      <input type="text" name="ptabla" value="<?php echo set_value('ptabla', $this->input->post('ptabla')) ?>"
                        id="ptabla" class="input-xlarge" placeholder="Tabla/Lote" <?php echo $disabled.$bmod['rancho']; ?>>
                    </div>
                  </div>

                  <div class="control-group" id="groupCliente" style="display: none;">
                    <label class="control-label" for="pcliente">Cliente</label>
                    <div class="controls">
                      <input type="text" name="pcliente" value="<?php echo set_value('pcliente', $this->input->post('pcliente')) ?>" id="pcliente"
                        class="input-xlarge sikey" data-replace="pproveedor" data-next="dno_trazabilidad" placeholder="Cliente" <?php echo $disabled.$bmod['proveedor']; ?>>
                      <span class="help-inline">
                        <a href="<?php echo base_url('panel/bascula/show_view_agregar_cliente') ?>" class="btn" rel="superbox-80x550">Agregar</a>
                      </span>
                      <input type="hidden" name="pid_cliente" value="<?php echo set_value('pid_cliente', $this->input->post('pid_cliente')) ?>" id="pid_cliente">
                    </div>
                  </div>

                  <div class="control-group" id="groupTrazabilidad" style="display: none;background-color: #fffed7;">
                    <label class="control-label" for="dno_trazabilidad">No Trazabilidad</label>
                    <div class="controls">
                      <input type="text" name="dno_trazabilidad" value="<?php echo set_value('dno_trazabilidad', $this->input->post('dno_trazabilidad')) ?>" id="dno_trazabilidad"
                        class="input-xlarge sikey" data-replace="pproveedor" data-next="pcamion" placeholder="No Trazabilidad" <?php echo $disabled.$bmod['proveedor']; ?>>
                      <input type="hidden" name="id_paleta_salida" value="<?php echo set_value('id_paleta_salida', $this->input->post('id_paleta_salida')) ?>">
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
                        value="<?php echo set_value('pchofer', $this->input->post('pchofer')) ?>" id="pchofer" class="input-xlarge" placeholder="Chofer" data-next="pmetodo_pago" <?php echo $disabled.$bmod['chofer']; ?>>
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
                        id="pfolio" class="input-medium vpos-int" style="text-align:center;" data-next="pproveedor|pcliente" <?php //echo ($e === true? ' readonly': ''); ?>>
                      <span class="help-inline">
                        <button class="btn" type="button" id="loadFolio" style="<?php //echo ($e === true? 'display:none': ''); ?>">Cargar</button>
                      </span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="pfecha">Fecha</label>
                    <div class="controls">
                      <input type="datetime-local" name="pfecha"
                        value="<?php echo set_value('pfecha', $fecha ); ?>" id="pfecha" class="input-large" <?php echo $disabled.$bmod['fecha']; ?> data-next="pproveedor|pcliente">
                      <?php if ($bmod['fecha'] == ' readonly') { ?>
                        <span class="help-inline">
                          <button class="btn" type="button" id="cambiarFecha"><i class="icon-calendar"></i></button>
                        </span>
                      <?php } ?>
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
                    <label class="control-label" for="pmetodo_pago">Método de pago</label>
                    <div class="controls">
                      <select name="pmetodo_pago" class="input-xlarge" id="pmetodo_pago" <?php echo $disabled; ?>>
                        <option value="co" <?php $set_select=set_select('pmetodo_pago', 'co', false, $this->input->post('pmetodo_pago')); echo $set_select.($set_select==' selected="selected"'? '': $bmod['metodo_pago']); ?>>Contado</option>
                        <option value="ot" <?php $set_select=set_select('pmetodo_pago', 'ot', false, $this->input->post('pmetodo_pago')); echo $set_select.($set_select==' selected="selected"'? '': $bmod['metodo_pago']); ?>>Otro</option>
                      </select>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="pproductor">Productor</label>
                    <div class="controls">
                      <input type="text" name="pproductor"
                        value="<?php echo set_value('pproductor', $this->input->post('pproductor')) ?>" data-next="pkilos_brutos|pkilos_tara" data-next2="pkilos_brutos"
                        id="pproductor" class="span12" placeholder="Productor" <?php echo $disabled.$bmod['proveedor']; ?>>
                      <span class="help-inline">
                        <a href="<?php echo base_url('panel/productores/show_view_agregar_productor') ?>" class="btn" rel="superbox-80x550">Agregar</a>
                      </span>
                      <input type="hidden" name="pid_productor" value="<?php echo set_value('pid_productor', $this->input->post('pid_productor')) ?>" id="pid_productor">
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
                        value="<?php echo set_value('pkilos_brutos', $this->input->post('pkilos_brutos')) ?>" <?php echo $disabled.$bmod['k_bruto'].' '.((($accion === 'n' && $e === false) || ($this->input->post('ptipo') === 'sa')) && $modkbt ? '' : $readonly) ?>>
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
                        value="<?php echo set_value('pkilos_tara', $this->input->post('pkilos_tara')) ?>" <?php echo $disabled.$bmod['k_tara'].' '.(((($accion === 'en' || $accion === 'sa') && $e === false) || ($this->input->post('ptipo') === 'en')) && $modkbt ? '' : $readonly) ?>>
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
                        value="<?php echo set_value('pcajas_prestadas', $this->input->post('pcajas_prestadas')) ?>"
                        data-next="icajas|prod_ddescripcion" <?php echo $disabled.$bmod['cajas_pres']; ?>>
                    </div>
                  </div>
                </div>
                <div class="span3">
                  <div class="control-group">
                    <label class="control-label" for="pkilos_neto" style="width: 100px;">Kilos Neto</label>
                    <div class="controls" style="margin-left: 115px;">
                      <input type="text" name="pkilos_neto" id="pkilos_neto" class="input-small vpositive"
                        value="<?php echo set_value('pkilos_neto', $this->input->post('pkilos_neto')) ?>" readonly <?php echo $disabled ?>>
                      <input type="hidden" name="pkilos_neto2" id="pkilos_neto2" class="input-small vpositive"
                        value="<?php echo set_value('pkilos_neto2', $this->input->post('pkilos_neto2')) ?>" readonly>

                      <p class="help-block" id="info_kilos_netos" style="cursor:pointer;color: red;"></p>
                    </div>
                  </div>
                </div>
              </div>
            </div><!--/box-content-->
          </div><!--/box span12-->
        </div><!--/row-fluid pesajes-->

        <div data-tipo="<?php echo $this->input->post('ptipo')=='sa'?'none':'block'; ?>" class="row-fluid" id="box-cajas"><!--cajas-->
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
                                <tr data-kneto="<?php echo $this->input->post('pkilos_neto') ?>">
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
                  <div class="span3">
                    <label for="ptotal_cajas">Total cajas</label>
                    <input type="text" name="ptotal_cajas"
                      value="<?php echo set_value('ptotal_cajas', $this->input->post('ptotal_cajas')) ?>" id="ptotal_cajas" class="input-medium vpositive" <?php echo $disabled ?> readonly>
                  </div>

                  <div class="span3">
                    <label for="ppesada">Pesada</label>
                    <input type="text" name="ppesada"
                      value="<?php echo set_value('ppesada', $this->input->post('ppesada')) ?>" id="ppesada" class="input-medium vpositive nokey" <?php echo $disabled ?>>
                  </div>

                  <div class="span3">
                    <label for="pisr" style="cursor: pointer;">ISR</label>
                    <input type="text" name="pisr"
                      value="<?php echo set_value('pisr', $this->input->post('pisr')) ?>" id="pisr" class="input-medium vpositive" <?php echo $disabled ?> readonly style="cursor: pointer;">
                    <input type="hidden" name="pisrPorcent" id="pisrPorcent" value="<?php echo set_value('pisrPorcent', $this->input->post('pisrPorcent')) ?>">
                  </div>

                  <div class="span3">
                    <label for="ptotal">Total</label>
                    <input type="text" name="ptotal"
                      value="<?php echo set_value('ptotal', $this->input->post('ptotal')) ?>" id="ptotal" class="input-medium vpositive" <?php echo $disabled ?> readonly>
                  </div>
                </div>
              </div>


            </div><!--/box-content-->
          </div><!--/box span12-->
        </div><!--/row-fluid cajas-->

        <div data-tipo="<?php echo $this->input->post('ptipo')=='sa'?'block':'none'; ?>" class="row-fluid box" id="box-cajas-salidas"><!--row-fluid salida cajas-->
          <div class="box-header well" data-original-title>
            <h2><i class="icon-road"></i> Cajas Salidas <span class="label label-warning">ALT + C</span></h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <div class="row-fluid">
              <div class="span12">
                <table class="table table-striped table-bordered table-hover table-condensed" id="table_prod">
                  <thead>
                    <tr>
                      <th>Descripción</th>
                      <th>Medida</th>
                      <th>Cant.</th>
                      <th>Kilos</th>
                      <th>Promedio</th>
                      <th>P Unitario</th>
                      <th>IVA%</th>
                      <th>IVA</th>
                      <th>Importe</th>
                      <th>Cert.</th>
                      <th>Accion</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                          if (isset($borrador) && ! isset($_POST['prod_did_prod']))
                          {
                            // foreach ($borrador['productos'] as $key => $p) {
                            //   $_POST['prod_did_prod'][$key]           = $p->id_clasificacion;
                            //   $_POST['prod_importe'][$key]            = $p->importe;
                            //   $_POST['prod_ddescripcion'][$key]       = $p->descripcion;
                            //   $_POST['prod_dmedida'][$key]            = $p->unidad;
                            //   $_POST['prod_dcantidad'][$key]          = $p->cantidad;
                            //   $_POST['prod_dpreciou'][$key]           = $p->precio_unitario;
                            //   $_POST['prod_diva_porcent'][$key]       = $p->porcentaje_iva;
                            //   $_POST['prod_diva_total'][$key]         = $p->iva;
                            //   $_POST['prod_dreten_iva_porcent'][$key] = $p->porcentaje_retencion;
                            //   $_POST['prod_dreten_iva_total'][$key]   = $p->retencion_iva;
                            //   $_POST['pallets_id'][$key]              = $p->ids_pallets;
                            //   $_POST['remisiones_id'][$key]           = $p->ids_remisiones;
                            //   $_POST['prod_dkilos'][$key]             = $p->kilos;
                            //   $_POST['prod_dcajas'][$key]             = $p->cajas;
                            //   $_POST['id_unidad_rendimiento'][$key]   = $p->id_unidad_rendimiento;
                            //   $_POST['id_size_rendimiento'][$key]     = $p->id_size_rendimiento;

                            //   $_POST['prod_dclase'][$key]             = $p->clase;
                            //   $_POST['prod_dpeso'][$key]              = $p->peso;
                            //   $_POST['isCert'][$key]                  = $p->certificado === 't' ? '1' : '0';
                            // }
                          } ?>

                          <?php if (isset($_POST['prod_did_prod'])) {
                            foreach ($_POST['prod_did_prod'] as $k => $v) {
                              if ($_POST['prod_importe'][$k] >= 0 && isset($_POST['prod_ddescripcion'][$k]{0}) && isset($_POST['prod_importe'][$k])) {
                              ?>
                                <tr>
                                  <td>
                                    <input type="text" name="prod_ddescripcion[]" class="span12" value="<?php echo $_POST['prod_ddescripcion'][$k]?>" id="prod_ddescripcion">
                                    <input type="hidden" name="prod_did_prod[]" class="span12" value="<?php echo $v ?>" id="prod_did_prod">
                                    <!-- <input type="hidden" name="pallets_id[]" value="<?php echo $_POST['pallets_id'][$k] ?>" id="pallets_id" class="span12">
                                    <input type="hidden" name="remisiones_id[]" value="<?php echo $_POST['remisiones_id'][$k] ?>" id="remisiones_id" class="span12">
                                    <input type="hidden" name="id_unidad_rendimiento[]" value="<?php echo $_POST['id_unidad_rendimiento'][$k] ?>" id="id_unidad_rendimiento" class="span12">
                                    <input type="hidden" name="id_size_rendimiento[]" value="<?php echo $_POST['id_size_rendimiento'][$k] ?>" id="id_size_rendimiento" class="span12"> -->
                                  </td>
                                  <td>
                                    <select name="prod_dmedida[]" id="prod_dmedida" class="span12">
                                      <?php foreach ($unidades as $key => $u) {
                                        if ($_POST['prod_dmedida'][$k] == $u->nombre) $uid = $u->id_unidad; ?>
                                        <option value="<?php echo $u->nombre ?>" <?php echo $_POST['prod_dmedida'][$k] == $u->nombre ? 'selected' : '' ?> data-id="<?php echo $u->id_unidad ?>"><?php echo $u->nombre ?></option>
                                      <?php } ?>
                                    </select>
                                    <input type="hidden" name="prod_dmedida_id[]" value="<?php echo $uid ?>" id="prod_dmedida_id" class="span12 vpositive">
                                  </td>
                                  <td>
                                      <input type="text" name="prod_dcantidad[]" class="span12 vpositive" value="<?php echo $_POST['prod_dcantidad'][$k]; ?>" id="prod_dcantidad">
                                      <!-- <input type="hidden" name="prod_dcajas[]" value="<?php echo $_POST['prod_dcajas'][$k] ?>" id="prod_dcajas" class="span12 vpositive">
                                      <input type="hidden" name="prod_dkilos[]" value="<?php echo $_POST['prod_dkilos'][$k] ?>" id="prod_dkilos" class="span12 vpositive"> -->
                                  </td>
                                  <td id="tdkilos">
                                    <span></span>
                                    <input type="text" name="pkilos[]" value="<?php echo $_POST['pkilos'][$k] ?>" id="pkilos" style="width: 100px;">
                                  </td>
                                  <td id="tdpromedio">
                                    <input type="text" name="ppromedio[]" value="<?php echo $_POST['ppromedio'][$k] ?>" id="ppromedio" style="width: 80px;">
                                  </td>
                                  <td>
                                    <input type="text" name="prod_dpreciou[]" class="span12 vnumeric" value="<?php echo $_POST['prod_dpreciou'][$k]; ?>" id="prod_dpreciou">
                                  </td>
                                  <td>
                                      <select name="diva" id="diva" class="span12">
                                        <option value="0" <?php echo $_POST['prod_diva_porcent'][$k] == 0 ? 'selected' : ''; ?>>0%</option>
                                        <option value="11" <?php echo $_POST['prod_diva_porcent'][$k] == 11 ? 'selected' : ''; ?>>11%</option>
                                        <option value="16" <?php echo $_POST['prod_diva_porcent'][$k] == 16 ? 'selected' : ''; ?>>16%</option>
                                      </select>

                                      <!-- <input type="hidden" name="prod_diva_total[]" class="span12" value="<?php //echo $_POST['prod_diva_total'][$k]; ?>" id="prod_diva_total"> -->
                                      <input type="hidden" name="prod_diva_porcent[]" class="span12" value="<?php echo $_POST['prod_diva_porcent'][$k]; ?>" id="prod_diva_porcent">
                                  </td>
                                  <td style="width: 80px;">
                                    <input type="text" name="prod_diva_total[]" class="span12" value="<?php echo $_POST['prod_diva_total'][$k]; ?>" id="prod_diva_total" readonly>
                                  </td>
                                   <td>
                                    <input type="text" name="prod_importe[]" class="span12 vpositive" value="<?php echo $_POST['prod_importe'][$k]?>" id="prod_importe">
                                  </td>
                                  <td>
                                    <input type="checkbox" class="is-cert-check" <?php echo ($_POST['isCert'][$k] == '1' ? 'checked' : '') ?>>
                                    <input type="hidden" name="isCert[]" value="<?php echo $_POST['isCert'][$k] ?>" class="certificado">
                                  </td>
                                  <td>
                                    <button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button>
                                  </td>
                                </tr>
                          <?php }}} ?>
                          <tr data-pallets="" data-remisiones="">
                            <td>
                              <input type="text" name="prod_ddescripcion[]" value="" id="prod_ddescripcion" class="span12" data-next="prod_dmedida">
                              <input type="hidden" name="prod_did_prod[]" value="" id="prod_did_prod" class="span12">
                              <!-- <input type="hidden" name="pallets_id[]" value="" id="pallets_id" class="span12">
                              <input type="hidden" name="remisiones_id[]" value="" id="remisiones_id" class="span12">
                              <input type="hidden" name="id_unidad_rendimiento[]" value="" id="id_unidad_rendimiento" class="span12">
                              <input type="hidden" name="id_size_rendimiento[]" value="" id="id_size_rendimiento" class="span12"> -->
                            </td>
                            <td>
                              <!-- <input type="text" name="prod_dmedida[]" value="" id="prod_dmedida" class="span12"> -->
                              <select name="prod_dmedida[]" id="prod_dmedida" class="span12">
                                <?php foreach ($unidades as $key => $u) {
                                    if ($key === 0) $uni = $u->id_unidad;
                                  ?>
                                  <option value="<?php echo $u->nombre ?>" data-id="<?php echo $u->id_unidad ?>"><?php echo $u->nombre ?></option>
                                <?php } ?>
                                <input type="hidden" name="prod_dmedida_id[]" value="<?php echo $uni ?>" id="prod_dmedida_id" class="span12 vpositive">
                              </select>
                            </td>
                            <td>
                                <input type="text" name="prod_dcantidad[]" value="0" id="prod_dcantidad" class="span12 vpositive">
                                <!-- <input type="hidden" name="prod_dcajas[]" value="0" id="prod_dcajas" class="span12 vpositive">
                                <input type="hidden" name="prod_dkilos[]" value="0" id="prod_dkilos" class="span12 vpositive"> -->
                            </td>
                            <td id="tdkilos">
                              <span></span>
                              <input type="text" name="pkilos[]" value="" id="pkilos" style="width: 100px;">
                            </td>
                            <td id="tdpromedio">
                              <input type="text" name="ppromedio[]" value="" id="ppromedio" style="width: 80px;">
                            </td>
                            <td>
                              <input type="text" name="prod_dpreciou[]" value="0" id="prod_dpreciou" class="span12 vnumeric">
                            </td>
                            <td>
                                <select name="diva" id="diva" class="span12">
                                  <option value="0">0%</option>
                                  <option value="11">11%</option>
                                  <option value="16">16%</option>
                                </select>

                                <!-- <input type="hidden" name="prod_diva_total[]" value="0" id="prod_diva_total" class="span12"> -->
                                <input type="hidden" name="prod_diva_porcent[]" value="0" id="prod_diva_porcent" class="span12">
                            </td>
                            <td style="width: 80px;">
                              <input type="text" name="prod_diva_total[]" class="span12" value="0" id="prod_diva_total" readonly>
                            </td>
                            <td>
                              <input type="text" name="prod_importe[]" value="0" id="prod_importe" class="span12 vpositive">
                            </td>
                            <td><input type="checkbox" class="is-cert-check"><input type="hidden" name="isCert[]" value="0" class="certificado"></td>
                            <td><button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button></td>
                          </tr>
                  </tbody>
                </table>

                <table class="table">
                  <tbody>
                    <tr>
                      <td rowspan="7" style="width:98%;max-width:98%;"></td>
                    </tr>
                    <tr>
                      <td><em>Subtotal</em></td>
                      <td id="importe-format"><?php echo MyString::formatoNumero(set_value('total_importe', isset($borrador) ? $borrador['info']->subtotal : 0))?></td>
                      <input type="hidden" name="total_importe" id="total_importe" value="<?php echo set_value('total_importe', isset($borrador) ? $borrador['info']->subtotal : 0); ?>">
                    </tr>
                    <tr>
                      <td>Descuento</td>
                      <td id="descuento-format"><?php echo MyString::formatoNumero(set_value('total_descuento', 0))?></td>
                      <input type="hidden" name="total_descuento" id="total_descuento" value="<?php echo set_value('total_descuento', 0); ?>">
                    </tr>
                    <tr>
                      <td>SUBTOTAL</td>
                      <td id="subtotal-format"><?php echo MyString::formatoNumero(set_value('total_subtotal', isset($borrador) ? $borrador['info']->subtotal : 0))?></td>
                      <input type="hidden" name="total_subtotal" id="total_subtotal" value="<?php echo set_value('total_subtotal', isset($borrador) ? $borrador['info']->subtotal : 0); ?>">
                    </tr>
                    <tr>
                      <td>IVA</td>
                      <td id="iva-format"><?php echo MyString::formatoNumero(set_value('total_iva', isset($borrador) ? $borrador['info']->importe_iva : 0))?></td>
                      <input type="hidden" name="total_iva" id="total_iva" value="<?php echo set_value('total_iva', isset($borrador) ? $borrador['info']->importe_iva : 0); ?>">
                    </tr>
                    <tr style="font-weight:bold;font-size:1.2em;">
                      <td>TOTAL</td>
                      <td id="totfac-format"><?php echo MyString::formatoNumero(set_value('total_totfac', isset($borrador) ? $borrador['info']->total : 0))?></td>
                      <input type="hidden" name="total_totfac" id="total_totfac" value="<?php echo set_value('total_totfac', isset($borrador) ? $borrador['info']->total : 0); ?>">
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div><!--/row-fluid salida cajas-->


        <div class="row-fluid">
          <div class="span8">
            <label class="" for="pobcervaciones">Descripción</label>
            <textarea name="pobcervaciones" id="pobcervaciones" class="span6" rows="5" <?php echo $disabled ?>><?php echo set_value('pobcervaciones', $this->input->post('pobcervaciones')) ?></textarea>
          </div>

          <?php if ($accion === 'en' && $this->input->post('ptipo') === 'en') { ?>
            <div class="span4">
              <div class="control-group">
                <label class="control-label" for="pno_lote">No. Lote</label>
                <div class="controls">
                  <input type="text" name="pno_lote" id="pno_lote" class="span6 vpos-int"
                  value="<?php echo set_value('pno_lote', $this->input->post('pno_lote')); ?>" autofocus placeholder="1, 2, 40, 100">
                </div>
              </div>

              <div class="control-group">
                <label class="control-label" for="pchofer_es_productor">Chofer es productor </label>
                <div class="controls">
                  <input type="checkbox" name="pchofer_es_productor" value="t" id="pchofer_es_productor" class="" <?php echo set_checkbox('pchofer_es_productor', 't', $this->input->post('pchofer_es_productor')=='t'?true:false); ?>>
                </div>
              </div>
            </div>
          <?php } ?>
        </div>

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

<!-- Modal Fecha -->
<div id="myModalFechaCh" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
    <h3 id="myModalLabel">Cambio de fecha</h3>
  </div>
  <div class="modal-body">
    <div class="row-fluid" style="text-align: center;">
      <div class="input-prepend" title="Fecha" data-rel="tooltip">
        <span class="add-on"><i class="icon-calendar"></i>
        </span><input class="input-large span10" name="fechaCh" value="" id="fechaCh" type="datetime-local" placeholder="Fecha">
      </div>
      <div class="clearfix"></div>

      <div class="input-prepend" title="Usuario" data-rel="tooltip">
        <span class="add-on"><i class="icon-user"></i>
        </span><input class="input-large span10" name="usuarioCh" value="" id="usuarioCh" type="text" placeholder="usuario">
      </div>
      <div class="clearfix"></div>

      <div class="input-prepend mtop" title="Contraseña" data-rel="tooltip">
        <span class="add-on"><i class="icon-lock"></i>
        </span><input class="input-large span10" name="passCh" value="" id="passCh" type="password" placeholder="******">
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
    <button class="btn btn-primary" id="btn-auth2">Autorizar</button>
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
    var win=window.open(<?php echo "'".base_url('panel/bascula/imprimir_recepcion/?id=' . $_GET['br']."'") ?>, '_blank');
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

