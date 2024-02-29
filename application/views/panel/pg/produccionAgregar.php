<div id="content" class="span10">
<style type="text/css">
  #lts_precios {
    margin-top: 10px;
    clear: both;
  }
  span.rowltsp {
    background-color: #ddd;
    padding: 5px 8px;
    border-radius: 7px;
    cursor: not-allowed;
    margin-right: 5px;
    margin-top: 5px;
  }
  input[type=text], input[type=number] {
    width: auto;
  }
</style>

  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <a href="<?php echo base_url('panel/pg_produccion/'); ?>">Producción</a> <span class="divider">/</span>
      </li>
      <li>Agregar</li>
    </ul>
  </div>

  <div class="row-fluid">
    <form class="form-horizontal" action="<?php echo base_url('panel/pg_produccion/agregar/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form">

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-plus"></i> Producción
            </h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">


              <div class="row-fluid">
                <div class="span6">

                  <div class="control-group">
                    <label class="control-label" for="dempresa">Empresa</label>
                    <div class="controls">

                      <input type="text" name="dempresa" class="span9" id="dempresa" value="<?php echo set_value('dempresa', isset($borrador) ? $borrador['info']->empresa->nombre_fiscal : $empresa_default->nombre_fiscal); ?>" size="73" autofocus>
                      <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value('did_empresa', isset($borrador) ? $borrador['info']->empresa->id_empresa : $empresa_default->id_empresa); ?>">
                    </div>
                  </div>

                  <div class="control-group sucursales" style="display: none;">
                    <label class="control-label" for="sucursalId">Sucursal </label>
                    <div class="controls">
                      <div class="input-append span9">
                        <select name="sucursalId" class="span12" id="sucursalId" data-selected="<?php echo (!empty($borrador['info']->id_sucursal) ? $borrador['info']->id_sucursal : $this->input->get('sucursalId')) ?>">
                          <option></option>
                          <?php foreach ($sucursales as $key => $sucur) { ?>
                            <option value="<?php echo $sucur->id_sucursal ?>" <?php echo set_select('sucursalId', $sucur->id_sucursal, false, (!empty($borrador['info']->id_sucursal) ? $borrador['info']->id_sucursal : $this->input->get('sucursalId'))); ?>><?php echo $sucur->nombre_fiscal ?></option>
                          <?php } ?>
                        </select>
                      </div>
                    </div>
                  </div><!--/control-group -->

                  <div class="control-group">
                    <label class="control-label" for="dmaquina">Maquina</label>
                    <div class="controls">
                      <select name="dmaquina" class="span9" id="dmaquina" required>
                        <?php foreach ($maquinas['conceptos'] as $maq): ?>
                        <option value="<?php echo $maq->id_maquina ?>" <?php echo set_select('dmaquina', $maq->id_maquina, false, (!empty($borrador['info']->id_maquina) ? $borrador['info']->id_maquina : $this->input->get('dmaquina'))); ?>><?php echo $maq->nombre ?></option>
                        <?php endforeach ?>
                      </select>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="dmolde">Molde</label>
                    <div class="controls">
                      <select name="dmolde" class="span9" id="dmolde" required>
                        <?php foreach ($moldes['conceptos'] as $mold): ?>
                        <option value="<?php echo $mold->id_molde ?>" <?php echo set_select('dmolde', $mold->id_molde, false, (!empty($borrador['info']->id_molde) ? $borrador['info']->id_molde : $this->input->get('dmolde'))); ?>><?php echo $mold->nombre ?></option>
                        <?php endforeach ?>
                      </select>
                    </div>
                  </div>

                </div>

                <div class="span6">

                  <div class="control-group">
                    <label class="control-label" for="dgrupo">Grupo</label>
                    <div class="controls">
                      <select name="dgrupo" class="span9" id="dgrupo" required>
                        <?php foreach ($grupos['conceptos'] as $mold): ?>
                        <option value="<?php echo $mold->id_grupo ?>" <?php echo set_select('dgrupo', $mold->id_grupo, false, (!empty($borrador['info']->id_grupo) ? $borrador['info']->id_grupo : $this->input->get('dgrupo'))); ?>><?php echo $mold->nombre ?></option>
                        <?php endforeach ?>
                      </select>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="dturno">Turno</label>
                    <div class="controls">
                      <select name="dturno" class="span9" id="dturno" required>
                        <option value="1" <?php echo set_select('dturno', '1', false, (!empty($borrador['info']->turno) ? $borrador['info']->turno : $this->input->get('dturno'))); ?>>1</option>
                        <option value="2" <?php echo set_select('dturno', '2', false, (!empty($borrador['info']->turno) ? $borrador['info']->turno : $this->input->get('dturno'))); ?>>2</option>
                        <option value="3" <?php echo set_select('dturno', '3', false, (!empty($borrador['info']->turno) ? $borrador['info']->turno : $this->input->get('dturno'))); ?>>3</option>
                      </select>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="dfecha">Fecha</label>
                    <div class="controls">
                      <input type="date" name="dfecha" class="span9" id="dfecha" value="<?php echo set_value('dfecha', isset($borrador) ? $borrador['info']->fecha : $fecha); ?>" size="25">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="djefeTurn">Jefe de Turno</label>
                    <div class="controls">
                      <input type="text" name="djefeTurn" class="span9 ui-autocomplete-input" id="djefeTurn" value="<?php echo set_value('djefeTurn', isset($borrador) ? $borrador['info']->jefe_turno : ''); ?>">
                      <input type="hidden" name="djefeTurnId" id="djefeTurnId" value="<?php echo set_value('djefeTurnId', isset($borrador) ? $borrador['info']->id_jefe_turno : ''); ?>" required="">
                    </div>
                  </div>




                  <div class="control-group">
                    <div class="controls">
                      <button type="submit" class="btn btn-success">Guardar</button>
                    </div>
                  </div>

                </div>
              </div>

          </div><!--/span-->


          <div class="row-fluid">
            <div class="box span12">
              <div class="box-header well" data-original-title>
                <h2><i class="icon-plus"></i> Datos
                </h2>
                <div class="box-icon">
                  <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                </div>
              </div>
              <div class="box-content">


                <div class="row-fluid formgrupslim">
                  <div class="row-fluid">

                    <div class="control-group span4">
                      <label class="control-label" for="clasificacion">Clasificación</label>
                      <div class="controls">
                        <input type="text" name="clasificacion" class="span12" id="clasificacion" value="<?php echo set_value('clasificacion', isset($borrador->clasificacion) ? $borrador['info']->clasificacion : ''); ?>">
                        <input type="hidden" name="id_clasificacion" class="span12" id="id_clasificacion" value="<?php echo set_value('id_clasificacion', isset($borrador->id_clasificacion) ? $borrador['info']->id_clasificacion : ''); ?>">
                      </div>
                    </div>

                    <div class="control-group span2">
                      <label class="control-label" for="cajas_buenas">Cajas Buenas</label>
                      <div class="controls">
                        <input type="text" name="cajas_buenas" class="span12 vpositive" id="cajas_buenas" value="<?php echo set_value('cajas_buenas', isset($borrador) ? $borrador['info']->cajas_buenas : ''); ?>">
                      </div>
                    </div>

                    <div class="control-group span2">
                      <label class="control-label" for="cajas_merma">Cajas Merma</label>
                      <div class="controls">
                        <input type="text" name="cajas_merma" class="span12 vpositive" id="cajas_merma" value="<?php echo set_value('cajas_merma', isset($borrador) ? $borrador['info']->cajas_merma : ''); ?>">
                      </div>
                    </div>

                    <div class="control-group span2">
                      <label class="control-label" for="cajas_total">Total Cajas</label>
                      <div class="controls">
                        <input type="text" name="cajas_total" class="span12" id="cajas_total" value="<?php echo set_value('cajas_total', isset($borrador) ? $borrador['info']->cajas_total : ''); ?>" readonly>
                      </div>
                    </div>

                  </div>

                  <div class="row-fluid">
                    <div class="control-group span2">
                      <label class="control-label" for="peso_prom">Peso Promedio Producto</label>
                      <div class="controls">
                        <input type="text" name="peso_prom" class="span12 vpositive" id="peso_prom" value="<?php echo set_value('peso_prom', isset($borrador) ? $borrador['info']->peso_prom : ''); ?>">
                      </div>
                    </div>

                    <div class="control-group span2">
                      <label class="control-label" for="plasta_kg">Plasta (kg)</label>
                      <div class="controls">
                        <input type="text" name="plasta_kg" class="span12 vpositive" id="plasta_kg" value="<?php echo set_value('plasta_kg', isset($borrador) ? $borrador['info']->plasta_kg : ''); ?>">
                      </div>
                    </div>

                    <div class="control-group span2">
                      <label class="control-label" for="inyectado_kg">Kgs Inyectados</label>
                      <div class="controls">
                        <input type="text" name="inyectado_kg" class="span12" id="inyectado_kg" value="<?php echo set_value('inyectado_kg', isset($borrador) ? $borrador['info']->inyectado_kg : ''); ?>" readonly>
                      </div>
                    </div>

                    <div class="control-group span2">
                      <label class="control-label" for="tiempo_ciclo">Tiempo Ciclo</label>
                      <div class="controls">
                        <input type="text" name="tiempo_ciclo" class="span12 vpositive" id="tiempo_ciclo" value="<?php echo set_value('tiempo_ciclo', isset($borrador) ? $borrador['info']->tiempo_ciclo : ''); ?>" data-next="addProducto">
                      </div>
                    </div>

                    <div class="control-group span2">
                      <div class="controls" style="margin-top: 18px;">
                        <button type="button" class="btn btn-info addProducto" id="addProducto"><i class="icon-plus"></i> Agregar</button>
                      </div>
                    </div>
                  </div>

                </div>

                <div class="row-fluid">
                  <table class="table table-striped table-bordered table-hover table-condensed" id="table_prod" style="margin-top: 10px;">
                    <thead>
                      <tr>
                        <th>Clasificación</th>
                        <th>Cajas Buenas</th>
                        <th>Cajas Merma</th>
                        <th>T Cajas</th>
                        <th>P Prom</th>
                        <th>Plasta</th>
                        <th>Kgs Inyectados</th>
                        <th>Ciclo</th>
                        <th>Accion</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      if (isset($_POST['prod_id_clasificacion']) && count($_POST['prod_id_clasificacion']) > 0) {
                        foreach ($_POST['prod_id_clasificacion'] as $key => $value): ?>
                      <tr>
                        <td>
                          <input type="hidden" name="prod_id[]" id="prod_id" value="<?php echo $_POST['prod_id'][$key] ?>">
                          <input type="hidden" name="prod_clasificacion[]" id="prod_clasificacion" value="<?php echo $_POST['prod_clasificacion'][$key] ?>">
                          <input type="hidden" name="prod_id_clasificacion[]" id="prod_id_clasificacion" value="<?php echo $_POST['prod_id_clasificacion'][$key] ?>">
                          <input type="hidden" name="prod_cajas_buenas[]" id="prod_cajas_buenas" value="<?php echo $_POST['prod_cajas_buenas'][$key] ?>">
                          <input type="hidden" name="prod_cajas_merma[]" id="prod_cajas_merma" value="<?php echo $_POST['prod_cajas_merma'][$key] ?>">
                          <input type="hidden" name="prod_total_cajas[]" id="prod_total_cajas" value="<?php echo $_POST['prod_total_cajas'][$key] ?>">
                          <input type="hidden" name="prod_peso_promedio[]" id="prod_peso_promedio" value="<?php echo $_POST['prod_peso_promedio'][$key] ?>">
                          <input type="hidden" name="prod_plasta[]" id="prod_plasta" value="<?php echo $_POST['prod_plasta'][$key] ?>">
                          <input type="hidden" name="prod_Kgs_inyectados[]" id="prod_Kgs_inyectados" value="<?php echo $_POST['prod_Kgs_inyectados'][$key] ?>">
                          <input type="hidden" name="prod_ciclo[]" id="prod_ciclo" value="<?php echo $_POST['prod_ciclo'][$key] ?>">
                          <input type="hidden" name="prod_del[]" id="prod_del" value="<?php echo $_POST['prod_del'][$key] ?>">
                        </td>
                        <td><?php echo $_POST['prod_cajas_buenas'][$key] ?></td>
                        <td><?php echo $_POST['prod_cajas_merma'][$key] ?></td>
                        <td><?php echo $_POST['prod_total_cajas'][$key] ?></td>
                        <td><?php echo $_POST['prod_peso_promedio'][$key] ?></td>
                        <td><?php echo $_POST['prod_plasta'][$key] ?></td>
                        <td><?php echo $_POST['prod_Kgs_inyectados'][$key] ?></td>
                        <td><?php echo $_POST['prod_ciclo'][$key] ?></td>
                        <td>
                          <button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button>
                        </td>
                      </tr>
                      <?php endforeach ?>
                      <?php
                      } elseif(isset($borrador['info']) && count($borrador['info']->productos) > 0) {
                        foreach ($borrador['info']->productos as $key => $itm):
                      ?>
                      <tr>
                        <td><?php echo $itm->clasificacion ?>
                          <input type="hidden" name="prod_id[]" id="prod_id" value="<?php echo $itm->id ?>">
                          <input type="hidden" name="prod_clasificacion[]" id="prod_clasificacion" value="<?php echo $itm->clasificacion ?>">
                          <input type="hidden" name="prod_id_clasificacion[]" id="prod_id_clasificacion" value="<?php echo $itm->id_clasificacion ?>">
                          <input type="hidden" name="prod_cajas_buenas[]" id="prod_cajas_buenas" value="<?php echo $itm->cajas_buenas ?>">
                          <input type="hidden" name="prod_cajas_merma[]" id="prod_cajas_merma" value="<?php echo $itm->cajas_merma ?>">
                          <input type="hidden" name="prod_total_cajas[]" id="prod_total_cajas" value="<?php echo $itm->cajas_total ?>">
                          <input type="hidden" name="prod_peso_promedio[]" id="prod_peso_promedio" value="<?php echo $itm->peso_prom ?>">
                          <input type="hidden" name="prod_plasta[]" id="prod_plasta" value="<?php echo $itm->plasta_kg ?>">
                          <input type="hidden" name="prod_Kgs_inyectados[]" id="prod_Kgs_inyectados" value="<?php echo $itm->inyectado_kg ?>">
                          <input type="hidden" name="prod_ciclo[]" id="prod_ciclo" value="<?php echo $itm->tiempo_ciclo ?>">
                          <input type="hidden" name="prod_del[]" id="prod_del" value="false">
                        </td>
                        <td><?php echo $itm->cajas_buenas ?></td>
                        <td><?php echo $itm->cajas_merma ?></td>
                        <td><?php echo $itm->cajas_total ?></td>
                        <td><?php echo $itm->peso_prom ?></td>
                        <td><?php echo $itm->plasta_kg ?></td>
                        <td><?php echo $itm->inyectado_kg ?></td>
                        <td><?php echo $itm->tiempo_ciclo ?></td>
                        <td>
                          <button type="button" class="btn btn-danger" id="delProd"><i class="icon-remove"></i></button>
                        </td>
                      </tr>
                      <?php endforeach ?>
                      <?php } ?>

                    </tbody>
                  </table>
                </div>


              </div><!--/span-->
            </div><!--/row-->
          </div>

        </div><!--/row-->
      </div>


    </form>
  </div><!--/row-->

  <!-- Modal -->
  <div id="modal-remisiones" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="width: 700px;left: 45%;">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="myModalLabel">Remisiones</h3>
    </div>
    <div class="modal-body" style="max-height: 370px;">
      <table id="lista_remisiones_modal" class="table table-striped table-bordered table-hover table-condensed">
        <caption></caption>
        <thead>
          <tr>
            <th></th>
            <th>Fecha</th>
            <th>Folio</th>
            <th>Cliente</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <button class="btn btn-primary" id="carga-remisiones">Cargar</button>
    </div>
  </div>

  <!-- Modal -->
  <div id="modal-repmant" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="width: 700px;left: 45%;">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="myModalLabel">Gastos</h3>
    </div>
    <div class="modal-body" style="max-height: 370px;">
      <table id="lista_repmant_modal" class="table table-striped table-bordered table-hover table-condensed">
        <caption></caption>
        <thead>
          <tr>
            <th></th>
            <th>Fecha</th>
            <th>Folio</th>
            <th>Proveedor</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <input type="hidden" id="tipo-repmant" value="repMant">
      <button class="btn btn-primary" id="carga-repmant">Cargar</button>
    </div>
  </div>

  <!-- Modal -->
  <div id="modal-gastoscaja" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true" style="width: 900px;left: 40%;">
    <div class="modal-header">
      <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
      <h3 id="myModalLabel">Gastos Caja</h3>
    </div>
    <div class="modal-body" style="max-height: 370px;">
      <table id="lista_gastoscaja_modal" class="table table-striped table-bordered table-hover table-condensed">
        <caption></caption>
        <thead>
          <tr>
            <th></th>
            <th>Fecha</th>
            <th>Folio</th>
            <th>Empresa</th>
            <th>Concepto</th>
            <th>Nombre</th>
            <th>Total</th>
          </tr>
        </thead>
        <tbody>
        </tbody>
      </table>
    </div>
    <div class="modal-footer">
      <button class="btn" data-dismiss="modal" aria-hidden="true">Cerrar</button>
      <button class="btn btn-primary" id="carga-gastoscaja">Cargar</button>
    </div>
  </div>

</div>

<!-- Bloque de alertas -->
<script type="text/javascript" charset="UTF-8">
<?php if (isset($_GET['imprimir_tk']{0})) {
?>
var win = window.open(base_url+'panel/ventas/imprimir_tk/?id=<?php echo $_GET['imprimir_tk']; ?>', '_blank');
if (win)
  win.focus();
else
  noty({"text":"Activa las ventanas emergentes (pop-ups) para este sitio", "layout":"topRight", "type":"error"});
<?php
} ?>
<?php if(isset($frm_errors)){
  if($frm_errors['msg'] != ''){
?>
  $(document).ready(function(){
    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
<?php }
}?>
</script>
<!-- Bloque de alertas -->