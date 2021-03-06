		<div id="content" class="span10">
			<!-- content starts -->

			<div>
				<ul class="breadcrumb">
					<li>
						<a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
					</li>
					<li>
						Panel principal
					</li>
				</ul>
			</div>
<!--
			<div class="sortable row-fluid">
        <a data-rel="tooltip" title="<?php //echo $venta_dia; ?>" class="well span3 top-block">
          <span class="icon32 icon-red icon-shopping-cart"></span>
          <div>Ventas del dia</div>
          <div><?php //echo MyString::formatoNumero($venta_dia); ?></div>
        </a>

				<a data-rel="tooltip" title="<?php //echo $venta_semana; ?>" class="well span3 top-block">
					<span class="icon32 icon-red icon-shopping-cart"></span>
					<div>Ventas semanal</div>
					<div><?php //echo MyString::formatoNumero($venta_semana); ?></div>
				</a>

				<a data-rel="tooltip" title="<?php //echo $venta_mes; ?>" class="well span3 top-block">
					<span class="icon32 icon-color icon-shopping-cart"></span>
					<div>Ventas del mes</div>
					<div><?php //echo MyString::formatoNumero($venta_mes); ?></div>
				</a>
      <?php
      $tienep = $this->usuarios_model->tienePrivilegioDe('', 'reportes/bajos_inventario/');
      ?>
        <a <?php echo ($tienep? 'href="'.base_url('panel/reportes/bajos_inventario').'" target="_blank"': ''); ?> data-rel="tooltip" title="<?php //echo $bajos_inventario; ?>" class="well span3 top-block">
          <span class="icon32 icon-color icon-th-list"></span>
          <div>Productos bajos inventario</div>
          <div><?php //echo $bajos_inventario; ?></div>
        </a>
			</div> -->
      <?php echo $cuentas; ?>
      <div class="row-fluid sortable">
        <div class="box span6">
          <div class="box-header well">
            <h2>Cuentas por pagar</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Productor</th>
                  <th>Saldo</th>
                </tr>
              </thead>
              <tbody id="product_mas_vend">
            <?php
            if (isset($cuentas_pagar))
              foreach($cuentas_pagar as $product) {?>
                <tr>
                  <td><a href="<?php echo base_url('panel/cajas/cuentas_pagar_productor/?id='.$product->id_productor.'&ffecha1='.date("Y-m").'-01&ffecha2='.date("Y-m-d")); ?>"><?php echo $product->nombre; ?></a></td>
                  <td><?php echo MyString::formatoNumero($product->total_pagar); ?></td>
                </tr>
            <?php }?>
              </tbody>
            </table>

          </div>
        </div><!--/span-->

        <div class="box span6">
          <div class="box-header well">
            <h2>Productos bajos de inventario</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content" style="height:300px;overflow-y:auto;">
            <table class="table table-condensed">
              <thead>
                <tr>
                  <th>Producto</th>
                  <th>Stock Min</th>
                  <th>Existencia</th>
                </tr>
              </thead>
              <tbody id="product_menos_vend" >
            <?php
            if (isset($empresas))
              foreach($empresas as $product) {?>
                <tr>
                  <td colspan="3" style="font-weight:bold;"><?php echo $product->nombre_fiscal; ?></td>
                </tr>
                  <?php foreach ($product->productos as $key => $value)
                  {
                  ?>
                <tr>
                  <td><?php echo $value->nombre_producto; ?></td>
                  <td><?php echo MyString::formatoNumero($value->stock_min, 2, '', false); ?></td>
                  <td><?php echo MyString::formatoNumero($value->saldo_anterior + $value->entradas - $value->salidas, 2, '', false); ?></td>
                </tr>
                  <?php
                  } ?>
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