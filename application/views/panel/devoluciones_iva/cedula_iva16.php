    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form action="<?php echo base_url('panel/devoluciones_iva/cedula_iva16_pdf/'); ?>" method="GET" class="form-search" id="frmrptcproform" target="frame_reporte">
              <div class="form-actions form-filters">

                <div class="control-group span6">
                  <label class="control-label" for="ffecha1">Del</label>
                  <div class="controls">
                    <input type="date" name="ffecha1" class="span11" id="ffecha1" value="<?php echo isset($_GET['ffecha1']) ? $_GET['ffecha1'] : date('Y-m-01'); ?>">
                  </div>
                </div>

                <div class="control-group span6">
                  <label class="control-label" for="ffecha2">Al</label>
                  <div class="controls">
                    <input type="date" name="ffecha2" class="span11" id="ffecha2" value="<?php echo isset($_GET['ffecha2']) ? $_GET['ffecha2'] : date('Y-m-d'); ?>">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="dempresa">Empresa</label>
                  <div class="controls">
                    <input type="text" name="dempresa"
                      value="<?php echo (isset($empresa->nombre_fiscal)? $empresa->nombre_fiscal: ''); ?>" id="dempresa" class="span12" placeholder="Nombre">
                    <input type="hidden" name="did_empresa" value="<?php echo (isset($empresa->id_empresa)? $empresa->id_empresa: ''); ?>" id="did_empresa">
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fproducto">Clientes</label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="dproveedor" value="" id="dproveedor" class="span9" placeholder="Buscar">
                      <button class="btn" type="button" id="btnAddProveedor" style="margin-left:-3px;"><i class="icon-plus-sign"></i></button>
                      <input type="hidden" name="did_proveedor" value="" id="did_proveedor">
                    </div>
                    <div class="clearfix"></div>
                    <div style="height:130px;overflow-y: scroll;background-color:#eee;">
                      <ul id="lista_proveedores" style="list-style: none;margin-left: 4px;">
                      </ul>
                    </div>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="tasa_iva">Tasa de IVA</label>
                  <div class="controls">
                    <select name="tasa_iva" id="tasa_iva">
                      <option value="">Todas</option>
                      <option value="16">16%</option>
                      <option value="0">0%</option>
                    </select>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="exportacion">Exportación</label>
                  <div class="controls">
                    <select name="exportacion" id="exportacion">
                      <option value="no">No</option>
                      <option value="si">Si</option>
                    </select>
                  </div>
                </div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary btn-large span12">Enviar</button>
                </div>

              </div>
            </form> <!-- /form -->

          </div>
        </div><!--/span12 -->
      </div><!--/row-fluid -->
    </div><!-- /span3 -->

    <div id="content" class="span9">
      <!-- content starts -->

      <div class="row-fluid">
        <div class="box span12">
          <a href="" id="linkDownXls" data-url="<?php echo base_url('panel/devoluciones_iva/cedula_iva16_xls'); ?>" class="linksm" target="_blank">
            <i class="icon-table"></i> Excel</a>

          <div class="box-content">
            <div class="row-fluid">
              <iframe id="frame_reporte" name="frame_reporte" src="<?php echo base_url('panel/devoluciones_iva/cedula_iva16_pdf/'); ?>" style="width: 100%;height: 475px;"></iframe>
            </div>
          </div>
        </div><!--/span-->
      </div><!--/row-->

    </div><!--/#content.span9-->



<?php if (isset($p) && isset($pe)) { ?>
  <script>
    var win=window.open(<?php echo "'".base_url('panel/bascula/imprimir_pagadas/?'.MyString::getVarsLink(array('msg', 'p', 'pe')).'&pe='.$pe)."'" ?>, '_blank');
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