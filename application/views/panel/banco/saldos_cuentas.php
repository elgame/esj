    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Saldos
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-file"></i> Saldos</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <a href="<?php echo base_url('panel/banco/saldos_pdf/?'.String::getVarsLink(array('msg'))); ?>" class="linksm" target="_blank">
              <i class="icon-print"></i> Imprimir</a> | 
            <a href="<?php echo base_url('panel/banco/saldos_xls/?'.String::getVarsLink(array('msg'))); ?>" class="linksm" target="_blank">
              <i class="icon-table"></i> Excel</a>

            <form action="<?php echo base_url('panel/banco/'); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters">
                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="date" name="ffecha1" class="input-large search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1'); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="date" name="ffecha2" class="input-large search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2'); ?>" size="10"> | 
                
                <label for="vertodos">Tipo:</label>
                <select name="vertodos" id="vertodos" class="input-large search-query">
                  <option value="" <?php echo set_select_get('vertodos', ''); ?>>Todas</option>
                  <option value="tran" <?php echo set_select_get('vertodos', 'tran'); ?>>En transito</option>
                  <option value="notran" <?php echo set_select_get('vertodos', 'notran'); ?>>Cobrados (no transito)</option>
                </select><br>

                <label for="fid_banco">Banco:</label>
                <select name="fid_banco" id="fid_banco" class="input-large search-query">
                  <option value="" <?php echo set_select_get('fid_banco', ''); ?>></option>
              <?php 
              foreach ($bancos['bancos'] as $key => $banco) {
              ?>
                  <option value="<?php echo $banco->id_banco; ?>" <?php echo set_select_get('fid_banco', $banco->id_banco); ?>><?php echo $banco->nombre; ?></option>
              <?php 
              } ?>
                </select>

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="dempresa" value="<?php echo set_value_get('dempresa', (isset($empresa->nombre_fiscal)? $empresa->nombre_fiscal: '') ); ?>" size="73">
                <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value_get('did_empresa', (isset($empresa->id_empresa)? $empresa->id_empresa: '')); ?>">

                <button type="submit" class="btn">Enviar</button>
              </div>
            </form>

            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Banco</th>
                  <th>Cuenta</th>
                  <th>Alias</th>
                  <th>Saldo</th>
                </tr>
              </thead>
              <tbody>
            <?php
            foreach($data['cuentas'] as $cuenta){
            ?>
                <tr>
                  <td><?php echo $cuenta->banco; ?></td>
                  <td><a href="<?php echo base_url('panel/banco/cuenta').'?id_cuenta='.$cuenta->id_cuenta.'&'.
                    String::getVarsLink(array('id_cuenta', 'msg', 'fstatus')); ?>" class="linksm lkzoom"><?php echo $cuenta->numero; ?></a>
                  </td>
                  <td><a href="<?php echo base_url('panel/banco/cuenta').'?id_cuenta='.$cuenta->id_cuenta.'&'.
                    String::getVarsLink(array('id_cuenta', 'msg', 'fstatus')); ?>" class="linksm lkzoom"><?php echo $cuenta->alias; ?></a>
                  </td>
                  <td><?php echo String::formatoNumero($cuenta->saldo); ?></td>
                </tr>
            <?php }?>
                <tr style="background-color:#ccc;font-weight: bold;">
                  <td style="text-align: right" colspan="3">Total:</td>
                  <td><?php echo String::formatoNumero($data['total_saldos']); ?></td>
                </tr>
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
