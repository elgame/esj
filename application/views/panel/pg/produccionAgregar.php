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
    <form class="form-horizontal" action="<?php echo base_url('panel/pg_produccion/agregar/'); ?>" method="POST" id="form">

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
                        <select name="sucursalId" class="span12" id="sucursalId">
                          <option></option>
                          <?php foreach ($sucursales as $key => $sucur) { ?>
                            <option value="<?php echo $sucur->id_sucursal ?>" <?php echo set_select('sucursalId', $sucur->id_departamento); ?>><?php echo $depa->nombre_fiscal ?></option>
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
                      <input type="text" name="djefeTurn" class="span9 ui-autocomplete-input" id="djefeTurn" value="" autocomplete="off"><span role="status" aria-live="polite" class="ui-helper-hidden-accessible"></span>
                      <input type="hidden" name="djefeTurnId" id="djefeTurnId" value="" required="">
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


                <div class="row-fluid">
                  <div class="row-fluid">

                    <div class="control-group span4">
                      <label class="control-label" for="cajas_buenas">Cajas Buenas</label>
                      <div class="controls">
                        <input type="text" name="cajas_buenas" class="span11 vpositive" id="cajas_buenas" value="<?php echo set_value('cajas_buenas', isset($borrador) ? $borrador['info']->cajas_buenas : ''); ?>">
                      </div>
                    </div>

                    <div class="control-group span4">
                      <label class="control-label" for="cajas_merma">Cajas Merma</label>
                      <div class="controls">
                        <input type="text" name="cajas_merma" class="span11 vpositive" id="cajas_merma" value="<?php echo set_value('cajas_merma', isset($borrador) ? $borrador['info']->cajas_merma : ''); ?>">
                      </div>
                    </div>

                    <div class="control-group span4">
                      <label class="control-label" for="cajas_total">Total Cajas</label>
                      <div class="controls">
                        <input type="text" name="cajas_total" class="span11" id="cajas_total" value="<?php echo set_value('cajas_total', isset($borrador) ? $borrador['info']->cajas_total : ''); ?>" readonly>
                      </div>
                    </div>

                  </div>

                  <div class="row-fluid">
                    <div class="control-group span4">
                      <label class="control-label" for="peso_prom">Peso Promedio Producto</label>
                      <div class="controls">
                        <input type="text" name="peso_prom" class="span11 vpositive" id="peso_prom" value="<?php echo set_value('peso_prom', isset($borrador) ? $borrador['info']->peso_prom : ''); ?>">
                      </div>
                    </div>

                    <div class="control-group span4">
                      <label class="control-label" for="plasta_kg">Plasta (kg)</label>
                      <div class="controls">
                        <input type="text" name="plasta_kg" class="span11 vpositive" id="plasta_kg" value="<?php echo set_value('plasta_kg', isset($borrador) ? $borrador['info']->plasta_kg : ''); ?>">
                      </div>
                    </div>

                    <div class="control-group span4">
                      <label class="control-label" for="inyectado_kg">Kgs Inyectados</label>
                      <div class="controls">
                        <input type="text" name="inyectado_kg" class="span11" id="inyectado_kg" value="<?php echo set_value('inyectado_kg', isset($borrador) ? $borrador['info']->inyectado_kg : ''); ?>" readonly>
                      </div>
                    </div>
                  </div>

                  <div class="row-fluid">
                    <div class="control-group span4">
                      <label class="control-label" for="tiempo_ciclo">Tiempo Ciclo</label>
                      <div class="controls">
                        <input type="text" name="tiempo_ciclo" class="span11 vpositive" id="tiempo_ciclo" value="<?php echo set_value('tiempo_ciclo', isset($borrador) ? $borrador['info']->tiempo_ciclo : ''); ?>">
                      </div>
                    </div>

                  </div>

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