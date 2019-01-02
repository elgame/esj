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
            <form action="<?php echo base_url('panel/nomina_trabajos2'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters">
                <label for="empresa">Empresa</label>
                <input type="text" name="empresa" class="input-xlarge search-query" id="empresa" value="<?php echo set_value_get('empresa', $empresaDefault->nombre_fiscal); ?>" size="73">
                <input type="hidden" name="empresaId" id="empresaId" value="<?php echo set_value_get('empresaId', $empresaDefault->id_empresa); ?>">

                <label for="ffecha" style="margin-top: 15px;">Dia</label>
                <input type="date" name="ffecha" id="ffecha" value="<?php echo $fecha ?>">

                <input type="submit" name="enviar" value="Ir" class="btn">
              </div>
            </form>

            <div class="stickcontent">
              <form action="<?php echo base_url('panel/nomina_trabajos2/addTarea/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" class="form row-fluid">
                <input type="hidden" id="dempresaId" value="<?php echo $filtros['empresaId']; ?>">
                <input type="hidden" id="dsemana" value="<?php echo $filtros['semana']; ?>">
                <input type="hidden" id="danio" value="<?php echo $filtros['anio']; ?>">
                <input type="hidden" id="dfecha" value="<?php echo $fecha; ?>">

                <div class="span3">
                  <label for="dempleado">Empleado</label>
                  <input type="text" class="span12" id="dempleado" value="">
                  <input type="hidden" id="dempleadoId" value="">
                </div>
                <div class="span3">
                  <label for="dlabor">Labor</label>
                  <input type="text" class="span12" id="dlabor" value="">
                  <input type="hidden" id="dlaborId" value="">
                </div>
                <div class="span1">
                  <label for="dcosto">Costo</label>
                  <input type="text" class="span12" id="dcosto" value="" readonly>
                </div>
                <div class="span1">
                  <label for="davance">Avance</label>
                  <input type="text" class="span12" id="davance" value="">
                </div>
                <div class="span2">
                  <label for="dimporte">Importe</label>
                  <input type="text" class="span12" id="dimporte" value="" readonly>
                </div>

                <div class="span3">
                  <label for="area">Cultivo / Actividad / Producto</label>
                  <input type="text" class="span12" id="area" value="">
                  <input type="hidden" id="areaId" value="">
                </div>

                <div class="span3">
                  <label class="control-label" for="rancho">Areas / Ranchos / Lineas </label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="rancho" class="span11" id="rancho" value="" placeholder="Milagro A, Linea 1">
                    </div>
                  </div>
                  <ul class="tags" id="tagsRanchoIds">
                    <li><span class="tag"></span>
                      <input type="hidden" name="ranchoId[]" class="ranchoId" value="">
                      <input type="hidden" name="ranchoText[]" class="ranchoText" value="">
                    </li>
                  </ul>
                </div>

                <div class="span3">
                  <label class="control-label" for="centroCosto">Centro de costo </label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="centroCosto" class="span11" id="centroCosto" value="" placeholder="Mantenimiento, Gasto general">
                    </div>
                  </div>
                  <ul class="tags" id="tagsCCIds">
                    <li><span class="tag"></span>
                      <input type="hidden" name="centroCostoId[]" class="centroCostoId" value="">
                      <input type="hidden" name="centroCostoText[]" class="centroCostoText" value="">
                    </li>
                  </ul>
                </div>
              </form>

              <table class="table table-striped table-bordered bootstrap-datatable" id="actividades_tra">
                <caption style="text-align: left;"></caption>
                <thead>
                  <tr>
                    <th style="width:18%;">Nombre</th>
                    <th style="width:10%;">Centro Costo</th>
                    <th style="width:10%;">Labor(s)</th>
                    <th style="width:9%;">Horas</th>
                    <th style="width:10%;">Hrs Extras</th>
                    <!-- <th style="width:10%;">Asis</th> -->
                    <th style="width:10%;">Descripcion</th>
                    <th style="width:8%;">Costo</th>
                    <th style="width:5%;"></th>
                  </tr>
                </thead>
                <tbody>
                </tbody>
              </table>
            </div>
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
