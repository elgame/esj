    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Nomenclaturas
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-list"></i> Nomenclaturas</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/caja_chica/nomenclaturas'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters center">
                <label for="fstatus">Estado</label>
                <select name="fstatus" class="input-medium" id="fstatus">
                  <option value="t" <?php echo set_select_get('fstatus', 't'); ?>>ACTIVAS</option>
                  <option value="">TODAS</option>
                  <option value="f" <?php echo set_select_get('fstatus', 'f'); ?>>ELIMINADAS</option>
                </select>

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <?php
              echo $this->usuarios_model->getLinkPrivSm('caja_chica/nomenclaturas_agregar/', array(
                'params'   => '',
                'btn_type' => 'btn-success pull-right',
                'attrs' => array('style' => 'margin-bottom: 10px;') )
              );
             ?>

            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Nomenclatura</th>
                  <th>Nombre</th>
                  <th>Estado</th>
                  <th>Opc</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($nomenclaturas as $nomenclatura) {?>
                <tr>
                  <td><?php echo $nomenclatura->nomenclatura; ?></td>
                  <td><?php echo $nomenclatura->nombre; ?></td>
                  <td><?php
                          $texto = 'ELIMINADA';
                          $label = 'warning';
                        if ($nomenclatura->status === 't') {
                          $texto = 'ACTIVA';
                          $label = 'success';
                        }
                      ?>
                      <span class="label label-<?php echo $label ?> "><?php echo $texto ?></span>
                  </td>
                  <td class="center">
                    <?php
                      if ($nomenclatura->status === 't')
                      {
                        echo $this->usuarios_model->getLinkPrivSm('caja_chica/nomenclaturas_modificar/', array(
                          'params'   => 'id='.$nomenclatura->id,
                          'btn_type' => 'btn-success',
                          'attrs' => array())
                        );

                        echo $this->usuarios_model->getLinkPrivSm('caja_chica/nomenclaturas_eliminar/', array(
                          'params'   => 'id='.$nomenclatura->id,
                          'btn_type' => 'btn-danger',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de eliminar la nomenclatura?', 'Categorias', this); return false;"))
                        );
                      }else{
                        echo $this->usuarios_model->getLinkPrivSm('caja_chica/nomenclaturas_eliminar/', array(
                          'params'   => 'id='.$nomenclatura->id.'&activar=t',
                          'btn_type' => 'btn-success', 'nombre' => 'Activar',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de activar la nomenclatura?', 'Categorias', this); return false;"))
                        );
                      }
                    ?>
                  </td>
                </tr>
            <?php }?>
              </tbody>
            </table>
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
    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
</script>
<?php }
}?>
<!-- Bloque de alertas -->
