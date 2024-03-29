    <div class="span3">
      <!-- content starts -->
      <div class="row-fluid">
        <div class="box span12">
          <div class="box-content">
            <form id="form" action="<?php echo base_url('panel/productos/rpt_lista_colores_pdf/'); ?>" method="GET" class="form-search" target="frame_reporte">
              <div class="form-actions form-filters">

                <div class="control-group">
                  <label class="control-label" for="dempresa">Empresa</label>
                  <div class="controls">
                    <input type="text" name="dempresa"
                      value="<?php echo (isset($empresa->nombre_fiscal)? $empresa->nombre_fiscal: ''); ?>" id="dempresa" class="span12" placeholder="Nombre">
                    <input type="hidden" name="did_empresa" value="<?php echo (isset($empresa->id_empresa)? $empresa->id_empresa: ''); ?>" id="did_empresa">
                  </div>
                </div>

                <div class="control-group" id="empresaApGrup">
                  <label class="control-label" for="empresaAp">Empresa aplicación </label>
                  <div class="controls">
                    <div class="input-append span12">
                      <input type="text" name="empresaAp" class="span11" id="empresaAp" value="<?php echo set_value('empresaAp') ?>" placeholder="Empaque, Mamita, etc">
                    </div>
                    <input type="hidden" name="empresaApId" id="empresaApId" value="<?php echo set_value('empresaApId') ?>">
                  </div>
                </div><!--/control-group -->

                <div class="control-group">
                  <label class="control-label" for="dcolor">Color</label>
                  <div class="controls">
                    <div class="input-append span12">
                      <select name="dcolor" id="dcolor" class="span11">
                        <option value=""></option>
                        <option value="v">Verde</option>
                        <option value="a">Amarillo</option>
                        <option value="r">Rojo</option>
                      </select>
                    </div>
                  </div>
                </div>

                <div class="control-group">
                  <label class="control-label" for="fproducto">Producto</label>
                  <div class="controls">
                    <input type="text" name="fproducto" data-add="false"
                      value="<?php echo set_value_get('fproducto', $this->input->get('fproducto')) ?>" id="fproducto" class="span12" placeholder="Nombre">
                    <input type="hidden" name="fid_producto" value="<?php echo set_value_get('fid_producto', $this->input->get('fid_producto')) ?>" id="fid_producto">
                  </div>
                </div>

                <!-- <div>
                  <label for="con_existencia">Con Existencia <input type="checkbox" name="con_existencia" id="con_existencia" value="si"> </label> |
                  <label for="con_movimiento">Con Movimientos <input type="checkbox" name="con_movimiento" id="con_movimiento" value="si"> </label>
                </div> -->

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

      <div class="box span12">
        <a href="" id="linkDownXls" data-url="<?php echo base_url('panel/productos/rpt_lista_colores_xls'); ?>" class="linksm" target="_blank">
          <i class="icon-table"></i> Excel</a>
        <div class="box-content">
          <div class="row-fluid">
            <iframe id="frame_reporte" name="frame_reporte" src="<?php echo base_url('panel/productos/rpt_lista_colores_pdf/'); ?>" style="width: 100%;height: 475px;"></iframe>
          </div>
        </div>
      </div><!--/span-->

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