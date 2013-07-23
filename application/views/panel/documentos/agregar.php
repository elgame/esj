<div id="content" class="span10">

  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <a href="<?php echo base_url('panel/facturacion/'); ?>">Facturacion</a> <span class="divider">/</span>
      </li>
      <li>Documentos</li>
    </ul>
  </div>

  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-copy"></i> Documentos Cliente</h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <div class="row-fluid">
          <div class="span12">

              <?php echo $documentos ?>

            </div>
          </div>
        </div>

      </div>
    </div>
  </div>

  <?php echo $facturaView; ?>

</div>

<!-- Bloque de alertas -->
<?php if(isset($frm_errors)){
  if($frm_errors['msg'] != ''){
?>
<script type="text/javascript" charset="UTF-8">

  <?php if($frm_errors['ico'] === 'success') {
    echo 'window.open("'.base_url('panel/facturacion/imprimir/?id='.$id).'")';
  }?>

  $(document).ready(function(){
    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
</script>
<?php }
}?>
<!-- Bloque de alertas -->