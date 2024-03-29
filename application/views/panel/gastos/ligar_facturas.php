<div id="content" class="span10">

  <div>
    <ul class="breadcrumb">
      <li>
        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
      </li>
      <li>
        <a href="<?php echo base_url('panel/compras/'); ?>">Compras</a> <span class="divider">/</span>
      </li>
      <li>Ligar facturas de Venta</li>
    </ul>
  </div>

  <div class="row-fluid">
    <div class="box span12">
      <div class="box-header well" data-original-title>
        <h2><i class="icon-th-list"></i> Facturas de venta</h2>
        <div class="box-icon">
          <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
        </div>
      </div>
      <div class="box-content">

        <form class="form-horizontal" action="<?php echo base_url('panel/gastos/ligar_facturas/?'.MyString::getVarsLink(array('msg'))); ?>" method="POST" id="form">

          <div class="row-fluid">

              <fieldset class="span6">
                <legend>Disponibles</legend>
                <div class="row-fluid">
                  <div class="span5">
                    <input type="date" name="fecha" class="span12" id="fecha" value="<?php echo date("Y-m-d") ?>">
                  </div>
                </div>
                <div class="row-fluid">
                  <div class="span8">
                    <input type="text" name="dempresa" class="span12" id="empresa" value="<?php echo (isset($empresa['info'])? $empresa['info']->nombre_fiscal: '') ?>">
                    <input type="hidden" name="id_empresa" id="id_empresa" value="<?php echo (isset($empresa['info'])? $empresa['info']->id_empresa: '') ?>">
                  </div>
                  <div class="span2">
                    <input type="text" name="costo" id="costo" class="span12" placeholder="Costo">
                  </div>
                  <div class="span2">
                    <buttom id="addProdsCancelados" class="btn"><i class="icon-angle-right"></i></buttom>
                  </div>
                </div>
                <div class="row-fluid">
                  <div class="span5">
                    <input type="text" name="fclasificacion" id="fclasificacion" class="span12" value="<?php echo set_value('fclasificacion'); ?>"
                      maxlength="100" placeholder="Clasificación" data-next="fcliente">
                    <input type="hidden" name="fid_clasificacion" id="fid_clasificacion" value="<?php echo set_value('fid_clasificacion'); ?>">
                  </div>
                  <div class="span5">
                    <input type="text" name="fcliente" id="fcliente" class="span12" value="<?php echo set_value('fcliente'); ?>"
                      maxlength="200" placeholder="Cliente" data-next="ffolio">
                    <input type="hidden" name="fid_cliente" id="fid_cliente" value="<?php echo set_value('fid_cliente'); ?>">
                  </div>
                  <div class="span2">
                    <input type="text" name="ffolio" id="ffolio" class="span12" value="<?php echo set_value('ffolio'); ?>"
                      maxlength="200" placeholder="Folio" data-next="funidad">
                  </div>
                  <div class="span4">
                    <input type="date" name="fechaf" class="span12" id="fechaf" value="<?php echo date("2018-m-d") ?>">
                  </div>
                </div>
                <table class="table table-striped table-bordered bootstrap-datatable">
                  <thead>
                    <tr>
                      <th style="width:70px;">Fecha</th>
                      <th>Folio</th>
                      <th>Cliente</th>
                      <th>Opciones</th>
                    </tr>
                  </thead>
                  <tbody id="tblfacturaslibres">
                  </tbody>
                </table>
              </fieldset>

              <fieldset class="span6 nomarg">
                <legend>Seleccionadas <button type="submit" id="btn_submit" class="btn btn-primary pull-right">Guardar</button></legend>
                <input type="hidden" name="id_compra" id="id_compra" value="<?php echo $this->input->get('idc') ?>">
                <input type="hidden" name="id_empresa" id="id_empresa" value="<?php echo $this->input->get('ide') ?>">
                <div id="tblsligadas">
              <?php
              $aux_clasif = 0;
              foreach ($facturas['ligadas'] as $key => $value)
              {
                $idrow = $value->id_clasificacion.'_'.$value->id_compra.'_'.$value->id_factura;
                if($aux_clasif != $value->id_clasificacion)
                {
              ?>
                <?php if($aux_clasif != 0)
                { ?>
                    </tbody>
                  </table>
                <?php }
                $aux_clasif = $value->id_clasificacion; ?>
                  <table id="tbl<?php echo $value->id_clasificacion ?>" class="table table-striped table-bordered bootstrap-datatable">
                    <caption><?php echo $value->nombre ?> - <buttom class="btn deleteTblSel"><i class="icon-remove"></i></buttom></caption>
                    <thead>
                      <tr>
                        <th style="width:70px;">Fecha</th>
                        <th>Folio</th>
                        <th>Cliente</th>
                       <th>Opciones</th>
                      </tr>
                    </thead>
                    <tbody class="tblfacturasligadas">
          <?php } ?>
                      <tr id="row_sel<?php echo $idrow ?>">
                        <td style="width:70px;"><?php echo $value->fecha ?>
                          <input type="hidden" name="idclasif[]" class="idclasif" value="<?php echo $value->id_clasificacion ?>">
                          <input type="hidden" name="idfactura[]" class="idfactura" value="<?php echo $value->id_factura ?>">
                          <input type="hidden" name="fecha[]" class="fecha" value="">
                          <input type="hidden" name="costo[]" class="costo" value="">
                        </td>
                        <td><?php echo $value->serie.$value->folio ?></td>
                        <td><?php echo $value->cliente ?>
                          <input type="hidden" name="id_cliente[]" class="id_cliente" value="">
                        </td>
                        <td><buttom class="btn deleteFacturaSel"><i class="icon-remove"></i></buttom></td>
                      </tr>
          <?php
              } ?>
                    </tbody>
                  </table>

                  <table id="tblcanceladas" class="table table-striped table-bordered bootstrap-datatable">
                    <caption>Canceladas</caption>
                    <thead>
                      <tr>
                        <th style="width:70px;">Fecha</th>
                        <th>Folio</th>
                        <th>Cliente</th>
                       <th>Opciones</th>
                      </tr>
                    </thead>
                    <tbody class="tblfacturasligadas">
                  <?php
                  foreach ($facturas['canceladas'] as $key => $value)
                  { ?>
                      <tr id="row_sel">
                        <td style="width:70px;"><?php echo $value->fecha ?>
                          <input type="hidden" name="idclasif[]" class="idclasif" value="<?php echo $value->id_clasificacion ?>">
                          <input type="hidden" name="idfactura[]" class="idfactura" value="">
                          <input type="hidden" name="fecha[]" class="fecha" value="<?php echo $value->fecha ?>">
                          <input type="hidden" name="costo[]" class="costo" value="<?php echo $value->importe ?>">
                        </td>
                        <td><?php echo $value->serie.$value->folio ?></td>
                        <td><?php echo $value->cliente ?>
                          <input type="hidden" name="id_cliente[]" class="id_cliente" value="<?php echo $value->id_cliente ?>">
                        </td>
                        <td><buttom class="btn deleteFacturaSel"><i class="icon-remove"></i></buttom></td>
                      </tr>
                  <?php } ?>
                    </tbody>
                  </table>

                </div>
              </fieldset>

            <div class="form-actions">
              <button type="submit" id="btn_submit" class="btn btn-primary">Guardar</button>
              <a href="<?php echo base_url('panel/compras/'); ?>" class="btn">Cancelar</a>
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