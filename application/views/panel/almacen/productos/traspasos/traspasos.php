        <div id="content" class="span10">
            <!-- content starts -->
            <div>
                <ul class="breadcrumb">
                    <li>
                        <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
                    </li>
                    <li>
                        Productos
                    </li>
                </ul>
            </div>

            <div class="row-fluid">
                <div class="box span6">
                    <div class="box-header well" data-original-title>
                        <h2><i class="icon-sign-blank"></i> Productos a Traspasar desde</h2>
                        <div class="box-icon">
                            <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                        </div>
                    </div>
                    <div class="box-content">
                        <form action="<?php echo base_url('panel/productos_traspasos/'); ?>" method="get" class="form-search" id="frmproductos">
                            <input type="text" name="fempresa" value="<?php echo set_value_get('fempresa', $empresa->nombre_fiscal) ?>" id="fempresa" class="input-xlarge" placeholder="Empresa" autofocus>
                            <input type="hidden" name="fid_empresa" value="<?php echo set_value_get('fid_empresa', $empresa->id_empresa) ?>" id="fid_empresa">

                            <input type="text" name="fproducto" value="<?php echo set_value_get('fproducto') ?>" id="fproducto" class="input-medium" placeholder="Producto">

                            <input type="submit" name="enviar" value="Buscar" class="btn">
                        </form>

                        <div id="content_productos_salida">
                            <?php echo $html_productos; ?>
                        </div>

                    </div>
                </div><!--/span-->

                <div id="boxproductos" class="box span6">
                    <div class="box-header well" data-original-title>
                        <h2><i class="icon-th-large"></i> Productos a Traspasar para <span id="familia_sel"></span></h2>
                        <div class="box-icon">
                            <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
                        </div>
                    </div>
                    <div class="box-content">

                            <input type="text" name="fempresa_to" value="<?php echo set_value_get('fempresa_to', $empresa->nombre_fiscal) ?>" id="fempresa_to" class="input-xlarge" placeholder="Empresa" autofocus>
                            <input type="hidden" name="fid_empresa_to" value="<?php echo set_value_get('fid_empresa_to', $empresa->id_empresa) ?>" id="fid_empresa_to">

                            <?php
                            echo $this->usuarios_model->getLinkPrivSm('productos_traspasos/agregar/', array(
                                    'params'   => '',
                                    'btn_type' => 'btn-success pull-right',
                                    'attrs' => array('id' => 'guardar-productos'),
                                ));
                             ?>

                            <input type="text" name="descripcion" value="<?php echo set_value_get('descripcion') ?>" id="descripcion" class="span12" placeholder="Descripcion del Traspaso" maxlength="255">

                        <div id="content_productos">
                            <table class="table table-striped table-bordered bootstrap-datatable" id="table-productos-traspasar">
                                <thead>
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Cantidad</th>
                                        <th>Descripcion</th>
                                        <th>Opc</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
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

        <?php if(isset($openTraspaso)){?>
            window.open(base_url + 'panel/productos_traspasos/print_orden/?idt=<?php echo $openTraspaso; ?>');
        <?php } ?>
    });
</script>
<?php }
}?>
<!-- Bloque de alertas -->