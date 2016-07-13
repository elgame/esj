    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <a href="<?php echo base_url('panel/clientes/'); ?>">Clientes</a> <span class="divider">/</span>
          </li>
          <li>Modificar</li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-edit"></i> Modificar cliente</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/clientes/modificar/?id='.$_GET['id']); ?>" method="post" class="form-horizontal">
              <fieldset>
                <legend></legend>
                <?php
                  $data = $cliente['info'];
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
                    <label class="control-label" for="dpais">Pais </label>
                    <div class="controls">
                      <input type="text" name="fpais" id="dpais" class="span10" value="<?php echo isset($data->pais)?$data->pais:''; ?>"
                        maxlength="45" placeholder="MEXICO">
                      <span class="dpais help-block nomarg" style="color:#bd362f"><?php echo (isset($data->pais)? cpais_model::getPaisKey($data->pais): '') ?></span>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="destado">Estado </label>
                    <div class="controls">
                      <input type="text" name="festado" id="destado" class="span10" value="<?php echo isset($data->estado)?$data->estado:''; ?>"
                        maxlength="45" placeholder="Colima, Jalisco">
                      <span class="destado help-block nomarg" style="color:#bd362f"><?php echo (isset($data->estado)? cestado_model::getEstadoKey($data->estado, $data->pais): '') ?></span>
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
                    <label class="control-label" for="frfc">RFC </label>
                    <div class="controls">
                      <input type="text" name="frfc" id="frfc" class="span12" value="<?php echo isset($data->rfc)?$data->rfc:''; ?>"
                        maxlength="13" placeholder="MPE050528A58, SFM00061515A">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fcurp">CURP </label>
                    <div class="controls">
                      <input type="text" name="fcurp" id="fcurp" class="span12" value="<?php echo isset($data->curp)?$data->curp:''; ?>"
                        maxlength="35" placeholder="IIML781216MCMXNS02, MONA731117HMNRRL05">
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

                  <div class="control-group">
                    <label class="control-label" for="fcuenta_cpi" style="font-weight: bold;">Cuenta ContpaqI </label>
                    <div class="controls">
                      <input type="text" name="fcuenta_cpi" id="fcuenta_cpi" class="span12" value="<?php echo isset($data->cuenta_cpi)?$data->cuenta_cpi:''; ?>"
                        maxlength="12" placeholder="12312, 312322">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fdias_credito">Dias de credito </label>
                    <div class="controls">
                      <input type="text" name="fdias_credito" id="fdias_credito" class="span12" value="<?php echo isset($data->dias_credito)?$data->dias_credito:''; ?>"
                        maxlength="12" placeholder="0, 15, 30">
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fmetodo_pago">Metodo de Pago </label>
                    <div class="controls">
                      <select name="fmetodo_pago" class="span9" id="fmetodo_pago">
                        <?php foreach (String::getMetodoPago() as $key => $mtp) { ?>
                          <option value="<?php echo $key ?>" <?php echo set_select('fmetodo_pago', $key, $data->metodo_pago === $key ? true : false); ?>><?php echo $mtp ?></option>
                        <?php } ?>
                      </select>
                    </div>
                  </div>

                  <div class="control-group">
                    <label class="control-label" for="fdigitos">Ultimos 4 digitos </label>
                    <div class="controls">
                      <input type="text" name="fdigitos" id="fdigitos" class="span12" value="<?php echo set_value('fdigitos', $data->ultimos_digitos); ?>" placeholder="1234">
                    </div>
                  </div>

                </div> <!--/span-->

                <div class="clearfix"></div>

                <fieldset>
                  <legend>Documentos del cliente</legend>
              <?php
              $docus_select = array();
              foreach ($cliente['docus'] as $key => $value) {
                $docus_select[] = $value->id_documento;
              }

              $ul1 = $ul2 = '';
              foreach ($documentos['documentos'] as $key => $value) {
                if($key % 2 == 0)
                  $ul1 .= '<li><label><input type="checkbox" name="documentos[]" value="'.$value->id_documento.'"
                            '.set_checkbox('documentos[]', $value->id_documento, (array_search($value->id_documento, $docus_select)!==false? true: false)).'> '.$value->nombre.'</label></li>';
                else
                  $ul2 .= '<li><label><input type="checkbox" name="documentos[]" value="'.$value->id_documento.'"
                            '.set_checkbox('documentos[]', $value->id_documento, (array_search($value->id_documento, $docus_select)!==false? true: false)).'> '.$value->nombre.'</label></li>';
              }
              ?>
                  <ul class="span6">
                    <?php echo $ul1; ?>
                  </ul>
                  <ul class="span6" style="margin-left: 0px;">
                    <?php echo $ul2; ?>
                  </ul>
                </fieldset>

                <div class="form-actions">
                  <button type="submit" class="btn btn-primary">Guardar</button>
                  <a href="<?php echo base_url('panel/proveedores/'); ?>" class="btn">Cancelar</a>
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


