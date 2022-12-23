    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            Estado de Resultados
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-file"></i> Estado de Resultados Trans</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/estado_resultado_trans/'.$method); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters center">
                <label for="fbuscar">Buscar</label>
                <input type="text" name="fbuscar" id="fbuscar" value="<?php echo set_value_get('fbuscar'); ?>" class="input-xlarge search-query" autofocus>

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="dempresa" value="<?php echo set_value_get('dempresa', $empresa_default->nombre_fiscal) ?>" size="73">
                <input type="hidden" name="did_empresa" id="did_empresa" value="<?php echo set_value_get('did_empresa', $empresa_default->id_empresa) ?>">

                <br>
                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="date" name="ffecha1" class="input-xlarge search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1', date('Y-m').'-01' ); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="date" name="ffecha2" class="input-xlarge search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2', $fecha); ?>" size="10">

                <!-- <label for="fstatus">Estado</label>
                <select name="fstatus" class="input-medium" id="fstatus">
                  <option value="">TODAS</option>
                  <option value="pa" <?php echo set_select_get('fstatus', 'pa'); ?>>PAGADAS</option>
                  <option value="p" <?php echo set_select_get('fstatus', 'p'); ?>>PENDIENTE</option>
                  <option value="ca" <?php echo set_select_get('fstatus', 'ca'); ?>>CANCELADAS</option>
                </select> -->

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <?php
            echo $this->usuarios_model->getLinkPrivSm('estado_resultado_trans/agregar/', array(
                    'params'   => '',
                    'btn_type' => 'btn-success pull-right',
                    'attrs' => array('style' => 'margin-bottom: 10px;') )
                );
             ?>
            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Folio</th>
                  <th>Chofer</th>
                  <th>Empresa</th>
                  <th>Activo</th>
                  <th>Acción</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($datos_s['res_trans'] as $fact) {?>
                <tr>
                  <td style="width:70px;"><?php echo $fact->fecha; ?></td>
                  <td style="width:70px;"><?php echo $fact->folio; ?></td>
                  <td>
                    <span class="label"><?php echo $fact->chofer; ?></span>
                  </td>
                  <td><?php echo $fact->empresa; ?></td>
                  <td><?php echo $fact->activo; ?></td>
                  <td class="center">
                    <?php

                      if($this->usuarios_model->tienePrivilegioDe('', 'estado_resultado_trans/modificar/'))
                        echo '<a class="btn btn-success" href="'.base_url().'panel/estado_resultado_trans/agregar/?id_nr='.$fact->id.'" title="Modificar">
                              <i class="icon-edit icon-white"></i> <span class="hidden-tablet">Modificar</span></a>';

                      echo $this->usuarios_model->getLinkPrivSm('estado_resultado_trans/imprimir/', array(
                        'params'   => 'id='.$fact->id,
                        'btn_type' => 'btn-info',
                        'attrs' => array('target' => "_blank"))
                      );

                      if ($fact->status !== 'ca')
                      {
                        echo $this->usuarios_model->getLinkPrivSm('estado_resultado_trans/cancelar/', array(
                          'params'   => 'id='.$fact->id,
                          'btn_type' => 'btn-danger',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de Cancelar el estado de resultado trans?', 'Notas de Remisión', this); return false;"))
                        );
                      }

                    ?>
                  </td>
                </tr>
            <?php }?>
              </tbody>
            </table>

            <?php
            //Paginacion
            $this->pagination->initialize(array(
                'base_url'      => base_url($this->uri->uri_string()).'?'.MyString::getVarsLink(array('pag')).'&',
                'total_rows'    => $datos_s['total_rows'],
                'per_page'      => $datos_s['items_per_page'],
                'cur_page'      => $datos_s['result_page']*$datos_s['items_per_page'],
                'page_query_string' => TRUE,
                'num_links'     => 1,
                'anchor_class'  => 'pags corner-all',
                'num_tag_open'  => '<li>',
                'num_tag_close' => '</li>',
                'cur_tag_open'  => '<li class="active"><a href="#">',
                'cur_tag_close' => '</a></li>'
            ));
            $pagination = $this->pagination->create_links();
            echo '<div class="pagination pagination-centered"><ul>'.$pagination.'</ul></div>';
            ?>
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
