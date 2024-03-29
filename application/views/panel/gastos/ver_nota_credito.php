<div id="content" class="span10">

  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <a href="<?php echo base_url('panel/compras/'); ?>">Compras</a> <span class="divider">/</span>
      </li>
      <li>Nota de Crédito</li>
    </ul>
  </div>

  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-th-list"></i> Nota de Crédito</h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/gastos/ver_nota_credito/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form" enctype="multipart/form-data">

          <div class="row-fluid">
            <div class="span12">
              <div class="row-fluid">
                <div class="span3">
                  <div class="control-group">
                    <div class="controls span9">
                      Serie <input type="text" name="serie" class="span12" id="serie" value="<?php echo set_value('serie', $nota_credito['info']->serie); ?>" maxlength="4" autofocus >
                    </div>
                  </div>
                </div>
                <div class="span3">
                  <div class="control-group">
                    <div class="controls span9">
                      Folio<input type="text" name="folio" class="span12" id="folio" value="<?php echo set_value('folio', $nota_credito['info']->folio); ?>">
                    </div>
                  </div>
                </div>
                <div class="span3">
                  <div class="control-group">
                    <div class="controls span9">
                      Fecha<input type="date" name="fecha" class="span12" id="fecha" value="<?php echo set_value('fecha', $fecha); ?>">
                    </div>
                  </div>
                </div>
                <div class="span3">
                  <div class="control-group">
                    <div class="controls span11">
                      <a class="btn btn-success" href="<?php echo base_url('panel/gastos/verXml/?ide='.$nota_credito['info']->id_empresa.'&idp='.$nota_credito['info']->id_proveedor.'') ?>"
                        rel="superbox-80x550" title="Buscar" id="supermodalBtn">
                        <i class="icon-eye-open icon-white"></i> <span class="hidden-tablet">Buscar XML</span></a>
                      <br><br>
                      UUID: <input type="text" name="uuid" value="<?php echo $nota_credito['info']->uuid; ?>" id="buscarUuid" class="span12"><br>
                      No Certificado: <input type="text" name="noCertificado" value="<?php echo $nota_credito['info']->no_certificado; ?>" id="buscarNoCertificado" class="span12">
                    </div>
                  </div>
                </div>
                <!-- <div class="span3">
                  <div class="control-group">
                    <div class="controls span9">
                      XML<input type="file" name="xml" class="span12" id="xml" data-uniform="false" accept="text/xml">
                    </div>
                  </div>
                </div> -->
              </div>
            </div>
          </div>

          <div class="row-fluid">
            <div class="span12">
              <div class="span3">
                <div class="control-group">
                  <div class="controls span9">
                    Observaciones <textarea type="text" name="observaciones" class="span12" id="observaciones"><?php echo set_value('observaciones', $nota_credito['info']->observaciones); ?></textarea>
                  </div>
                </div>
              </div>
              <div class="span3 offset6 well">
                  <button type="submit" class="btn btn-success btn-large btn-block" style="width:100%;">Actualizar</button>
              </div>
            </div>
          </div>

          <div class="row-fluid">
            <div class="span12">
              <table class="table">
                <thead>
                  <tr>
                    <th style="background-color:#FFF !important;">TOTAL CON LETRA</th>
                    <th style="background-color:#FFF !important;">TOTALES</th>
                    <th style="background-color:#FFF !important;"></th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td rowspan="7">
                      <textarea name="totalLetra" rows="5" class="nokey" style="width:98%;max-width:98%;" id="totalLetra" readonly><?php echo set_value('totalLetra', '');?></textarea>
                    </td>
                  </tr>
                  <tr>
                    <td><em>Subtotal</em></td>
                    <td>
                      <input type="text" name="totalImporte" id="totalImporte" value="<?php echo set_value('totalImporte', $nota_credito['info']->subtotal)?>">
                    </td>
                  </tr>
                  <tr>
                    <td>IVA</td>
                    <td>
                      <input type="text" name="totalImpuestosTrasladados" id="totalImpuestosTrasladados" value="<?php echo set_value('totalImpuestosTrasladados', $nota_credito['info']->importe_iva)?>">
                    </td>
                  </tr>
                  <tr>
                    <td>RET.</td>
                    <td><input type="text" name="totalRetencion" id="totalRetencion" value="<?php echo set_value('totalRetencion', $nota_credito['info']->retencion_iva)?>"></td>
                  </tr>
                  <tr>
                    <td>RET. ISR</td>
                    <td><input type="text" name="totalRetencionIsr" id="totalRetencionIsr" value="<?php echo set_value('totalRetencionIsr', $nota_credito['info']->retencion_isr)?>"></td>
                  </tr>
                  <tr style="font-weight:bold;font-size:1.2em;">
                    <td>TOTAL</td>
                    <td><input type="text" name="totalOrden" id="totalOrden" value="<?php echo set_value('totalOrden', $nota_credito['info']->total)?>"></td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </form>

      </div><!--/span-->
    </div><!--/row-->
  </div><!--/row-->

</div>

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