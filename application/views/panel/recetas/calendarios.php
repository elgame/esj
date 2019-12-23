    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <?php echo $titleBread ?>
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-shopping-cart"></i> Calendarios</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/recetas/calendario/'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters center">

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="empresa" value="<?php echo set_value_get('dempresa', $empresa_default->nombre_fiscal) ?>" size="73" required>
                <input type="hidden" name="did_empresa" id="empresaId" value="<?php echo set_value_get('did_empresa', $empresa_default->id_empresa) ?>">

                <label for="ffecha1" style="margin-top: 15px;">Fecha</label>
                <input type="date" name="ffecha1" class="input-xlarge search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1', date('Y-m-01')); ?>" size="10" required>

                <br>

                <label for="darea">Cultivo</label>
                <input type="text" name="darea" class="input-large search-query" id="darea" value="<?php echo set_value_get('darea') ?>" size="73" required>
                <input type="hidden" name="did_area" id="areaId" value="<?php echo set_value_get('did_area') ?>">

                <label for="calendario">Calendario</label>
                <select name="calendario" class="input-medium" id="calendario" required>
                  <?php foreach ($calendarios as $key => $value): ?>
                  <option value="<?php echo $value->id ?>" <?php echo set_select_get('calendario', $value->id) ?>><?php echo $value->nombre ?></option>
                  <?php endforeach ?>
                </select>

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <div id="eventos" style="display: none;"><?php echo json_encode($eventos); ?></div>

            <div id="calendar"></div>

            <div class="clearfix"></div>
          </div>
        </div><!--/span-->

      </div><!--/row-->


      <!-- Modal -->
      <div id="modalOrden" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
        aria-hidden="true" style="width: 80%;left: 25%;top: 40%;height: 600px;">
        <div class="modal-body" style="max-height: 1500px;">
          <iframe id="frmOrdenView" src="" style="width: 100%;height: 800px;"></iframe>
        </div>
      </div>



          <!-- content ends -->
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
