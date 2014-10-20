    <div id="content" class="span10">
      <!-- content starts -->


      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-plus"></i> Lote</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/bascula/show_view_agregar_lote/?idb='.$_GET['idb']); ?>" method="post" class="form-horizontal">
              <fieldset>
                <legend></legend>

                <div class="control-group">
                  <label class="control-label" for="pno_lote">No. Lote</label>
                  <div class="controls">
                    <input type="text" name="pno_lote" id="pno_lote" class="span6 vpos-int"
                    value="<?php echo set_value('pno_lote', $no_lote); ?>" autofocus placeholder="1, 2, 40, 100">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="pchofer_es_productor">Chofer es productor </label>
                  <div class="controls">
                    <input type="checkbox" name="pchofer_es_productor" value="t" id="pchofer_es_productor" class="" <?php echo set_checkbox('pchofer_es_productor', 't', $chofer_prod=='t'?true:false); ?>>
                  </div>
                </div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
              </fieldset>
            </form>

          </div>
        </div><!--/span-->

      </div><!--/row-->


          <!-- content ends -->
    </div><!--/#content.span10-->



<!-- Bloque de alertas -->
<?php if(isset($frm_errors)){
  if($frm_errors['msg'] != ''){
?>
<script type="text/javascript" charset="UTF-8">
  $(document).ready(function(){
    <?php if($frm_errors['ico'] == 'success'){ ?>
      parent.setLoteBoleta();
    <?php } ?>
    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
</script>
<?php }
}?>
<!-- Bloque de alertas -->

