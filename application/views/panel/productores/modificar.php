    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/productores/'); ?>">Productores</a> <span class="divider">/</span>
          </li>
          <li>Modificar</li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-edit"></i> Modificar productor</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/productores/modificar/?id='.$_GET['id']); ?>" method="post" class="form-horizontal">
              <fieldset>
                <legend></legend>
                <?php
                  $data = $productor['info'];
                ?>

                <div class="span6">
                  <div class="control-group">
                    <label class="control-label" for="fnombre_fiscal">Nombre fiscal </label>
                    <div class="controls">
                      <input type="text" name="fnombre_fiscal" id="fnombre_fiscal" class="span10" maxlength="140"
                      value="<?php echo isset($data->nombre_fiscal)?$data->nombre_fiscal:''; ?>" required autofocus placeholder="GAS MENGUC SA DE CV, MORA NARANJO ALFREDO">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fcalle">Calle </label>
                    <div class="controls">
                      <input type="text" name="fcalle" id="fcalle" class="span10" value="<?php echo isset($data->calle)?$data->calle:''; ?>"
                        maxlength="60" placeholder="PRIVADA SAN MARINO, 5 DE MAYO">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fno_exterior">No. exterior </label>
                    <div class="controls">
                      <input type="text" name="fno_exterior" id="fno_exterior" class="span10" value="<?php echo isset($data->no_exterior)?$data->no_exterior:''; ?>"
                        maxlength="7" placeholder="102, S/N">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fno_interior">No. interior </label>
                    <div class="controls">
                      <input type="text" name="fno_interior" id="fno_interior" class="span10" value="<?php echo isset($data->no_interior)?$data->no_interior:''; ?>"
                        maxlength="7" placeholder="102, S/N">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fcolonia">Colonia </label>
                    <div class="controls">
                      <input type="text" name="fcolonia" id="fcolonia" class="span10" value="<?php echo isset($data->colonia)?$data->colonia:''; ?>"
                        maxlength="60" placeholder="Juan Jose Rios, 3ra Cocoteros">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="flocalidad">Localidad </label>
                    <div class="controls">
                      <input type="text" name="flocalidad" id="flocalidad" class="span10" value="<?php echo isset($data->localidad)?$data->localidad:''; ?>"
                        maxlength="45" placeholder="Cerro de ortega, Ranchito">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fmunicipio">Municipio </label>
                    <div class="controls">
                      <input type="text" name="fmunicipio" id="fmunicipio" class="span10" value="<?php echo isset($data->municipio)?$data->municipio:''; ?>"
                        maxlength="45" placeholder="Tecoman, Armeria">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="festado">Estado </label>
                    <div class="controls">
                      <input type="text" name="festado" id="festado" class="span10" value="<?php echo isset($data->estado)?$data->estado:''; ?>"
                        maxlength="45" placeholder="Colima, Jalisco">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fpais">Pais </label>
                    <div class="controls">
                      <input type="text" name="fpais" id="fpais" class="span10" value="<?php echo isset($data->pais)?$data->pais:''; ?>"
                        maxlength="45" placeholder="MEXICO">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fempresa">Empresa </label>
                    <div class="controls">
                    <input type="text" name="fempresa" id="fempresa" class="span10" value="<?php echo set_value('fempresa', $empresa['info']->nombre_fiscal); ?>" placeholder="Nombre">
                    <input type="hidden" name="did_empresa" value="<?php echo set_value('did_empresa', $empresa['info']->id_empresa); ?>" id="did_empresa">
                    </div>
                  </div>
                </div> <!--/span-->

                <div class="span5">

                  <div class="control-group">
                    <label class="control-label" for="fparcela">Parcela </label>
                    <div class="controls">
                      <select name="fparcela" id="fparcela">
                        <option value="RENTADA" <?php echo $data->parcela === 'RENTADA' ? 'selected' : ''?>>RENTADA</option>
                        <option value="PROPIA" <?php echo $data->parcela === 'PROPIA' ? 'selected' : ''?>>PROPIA</option>
                      </select>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fejido_parcela">Ejido parcela </label>
                    <div class="controls">
                      <input type="text" name="fejido_parcela" id="fejido_parcela" class="span12" value="<?php echo isset($data->ejido_parcela)?$data->ejido_parcela:''; ?>"
                        maxlength="150">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fcp">CP </label>
                    <div class="controls">
                      <input type="text" name="fcp" id="fcp" class="span12" value="<?php echo isset($data->cp)?$data->cp:''; ?>"
                        maxlength="10" placeholder="28084, 28000">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="ftelefono">Telefono </label>
                    <div class="controls">
                      <input type="text" name="ftelefono" id="ftelefono" class="span12" value="<?php echo isset($data->telefono)?$data->telefono:''; ?>"
                        maxlength="15" placeholder="3189212, 312 308 7691">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fcelular">Celular </label>
                    <div class="controls">
                      <input type="text" name="fcelular" id="fcelular" class="span12" value="<?php echo isset($data->celular)?$data->celular:''; ?>"
                        maxlength="20" placeholder="044 312 1379827, 313 552 1232">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="femail">Email </label>
                    <div class="controls">
                      <input type="text" name="femail" id="femail" class="span12" value="<?php echo isset($data->email)?$data->email:''; ?>"
                        maxlength="600" placeholder="correo@gmail.com">
                    </div>
                  </div>

                  <!-- <div class="control-group">
                    <label class="control-label" for="fcuenta_cpi" style="font-weight: bold;">Cuenta ContpaqI </label>
                    <div class="controls">
                      <input type="text" name="fcuenta_cpi" id="fcuenta_cpi" class="span12" value="<?php //echo isset($data->cuenta_cpi)?$data->cuenta_cpi:''; ?>"
                        maxlength="12" placeholder="12312, 312322">
                    </div>
                  </div> -->

                  <div class="control-group">
                    <label class="control-label" for="ftipo">Tipo </label>
                    <div class="controls">
                      <select name="ftipo" class="span9" id="ftipo">
                        <option value="SIN FACTURA PAGO EN EFECTIVO" <?php echo $data->tipo === 'SIN FACTURA PAGO EN EFECTIVO' ? 'selected' : ''?>>SIN FACTURA PAGO EN EFECTIVO</option>
                        <option value="CON FACTURA PAGO EN EFECTIVO" <?php echo $data->tipo === 'CON FACTURA PAGO EN EFECTIVO' ? 'selected' : ''?>>CON FACTURA PAGO EN EFECTIVO</option>
                        <option value="FACTURADOR EMPAQUE SAN JORGE" <?php echo $data->tipo === 'FACTURADOR EMPAQUE SAN JORGE' ? 'selected' : ''?>>FACTURADOR EMPAQUE SAN JORGE</option>
                      </select>
                    </div>
                  </div>

                </div> <!--/span-->

                <div class="clearfix"></div>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary">Guardar</button>
                  <a href="<?php echo base_url('panel/productores/'); ?>" class="btn">Cancelar</a>
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
    noty({"text":"<?php echo $frm_errors['msg']; ?>", "layout":"topRight", "type":"<?php echo $frm_errors['ico']; ?>"});
  });
</script>
<?php }
}?>
<!-- Bloque de alertas -->


