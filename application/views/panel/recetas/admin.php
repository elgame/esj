    <div id="content" class="span10">
      <!-- content starts -->


      <div>
        <ul class="breadcrumb">
          <li>
            <a href="<?php echo base_url('panel'); ?>">Inicio</a> <span class="divider">/</span>
          </li>
          <li>
            <?php echo $titleBread ?>
          </li>
        </ul>
      </div>

      <div class="row-fluid">
        <div class="box span12">
          <div class="box-header well" data-original-title>
            <h2><i class="icon-shopping-cart"></i> Recetas</h2>
            <div class="box-icon">
              <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
            </div>
          </div>
          <div class="box-content">
            <form action="<?php echo base_url('panel/recetas/'.$method); ?>" method="GET" class="form-search">
              <div class="form-actions form-filters center">
                <label for="fbuscar">Buscar</label>
                <input type="text" name="fbuscar" id="fbuscar" value="<?php echo set_value_get('fbuscar'); ?>" class="search-query" placeholder="Folio, Nombre" autofocus>

                <label for="dempresa">Empresa</label>
                <input type="text" name="dempresa" class="input-large search-query" id="empresa" value="<?php echo set_value_get('dempresa', $empresa_default->nombre_fiscal) ?>" size="73">
                <input type="hidden" name="did_empresa" id="empresaId" value="<?php echo set_value_get('did_empresa', $empresa_default->id_empresa) ?>">

                <label for="fstatus">Estado</label>
                <select name="fstatus" class="input-medium" id="fstatus">
                  <option value="">TODAS</option>
                  <option value="t" <?php echo set_select_get('fstatus', 't'); ?>>Activos</option>
                  <option value="f" <?php echo set_select_get('fstatus', 'f'); ?>>Eliminados</option>
                </select>

                <br>
                <label for="ffecha1" style="margin-top: 15px;">Fecha del</label>
                <input type="date" name="ffecha1" class="input-xlarge search-query" id="ffecha1" value="<?php echo set_value_get('ffecha1', date('Y-m-01')); ?>" size="10">
                <label for="ffecha2">Al</label>
                <input type="date" name="ffecha2" class="input-xlarge search-query" id="ffecha2" value="<?php echo set_value_get('ffecha2', $fecha); ?>" size="10">

                <br>

                <label for="darea">Cultivo</label>
                <input type="text" name="darea" class="input-large search-query" id="darea" value="<?php echo set_value_get('darea') ?>" size="73">
                <input type="hidden" name="did_area" id="areaId" value="<?php echo set_value_get('did_area') ?>">

                <label for="ftipo">Tipo</label>
                <select name="ftipo" class="input-medium" id="ftipo">
                  <option value="">TODAS</option>
                  <option value="kg" <?php echo set_select_get('ftipo', 'kg'); ?>>Kg</option>
                  <option value="lts" <?php echo set_select_get('ftipo', 'lts'); ?>>Lts</option>
                </select>

                <input type="submit" name="enviar" value="Enviar" class="btn">
              </div>
            </form>

            <?php
              echo $this->usuarios_model->getLinkPrivSm('recetas/agregar/', array(
                'params'   => 'w='.($requisicion ? 'r' : 'c'),
                'btn_type' => 'btn-success pull-right',
                'attrs' => array('style' => 'margin-bottom: 10px;') )
              );
             ?>
             <a href="<?php echo base_url('panel/recetas/show_import_recetas_corona/?id='.set_value_get('did_empresa', $empresa_default->id_empresa).'&id_area='.set_value_get('did_area').'&cultivo='.set_value_get('darea'). '&fecha=' . set_value_get('ffecha2', $fecha)) ?>" class="btn btn-info" rel="superbox-50x450" title="Importar Recetas Corona"><i class="icon-upload"></i></a>


             <div id="sumaRowsSel" style="display:none;position:fixed;top:200px;right: 0px;width: 130px;background-color:#FFFF00;padding:3px 0px 3px 3px;font-size:16px;font-weight:bold;"></div>

            <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>F. Aplicación</th>
                  <th>Cultivo</th>
                  <th>Folio</th>
                  <th>Formula</th>
                  <th>Tipo</th>
                  <th>Estado</th>
                  <th>Importe</th>
                  <th>Opc</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($recetas['recetas'] as $receta) { ?>
                <tr>
                  <td><?php echo $receta->fecha; ?></td>
                  <td><?php echo $receta->fecha_aplicacion; ?></td>
                  <td><?php echo $receta->area; ?></td>
                  <td><?php echo $receta->folio; ?></td>
                  <td><?php echo $receta->nombre; ?></td>
                  <td><?php echo $receta->tipo; ?></td>
                  <td><?php
                        $texto = 'Activa';
                        $label = 'success';
                        if ($receta->status === 'f') {
                          $texto = 'Eliminada';
                          $label = 'warning';
                        }
                      ?>
                      <span class="label label-<?php echo $label ?> "><?php echo $texto ?></span>
                  </td>
                  <td><?php echo $receta->total_importe; ?></td>
                  <td class="center">
                    <?php

                      if ($receta->paso == 'p') {
                        echo $this->usuarios_model->getLinkPrivSm('recetas/modificar/', array(
                          'params'   => 'id='.$receta->id_recetas,
                          'btn_type' => 'btn-success',
                          'attrs' => array())
                        );
                      }
                      echo $this->usuarios_model->getLinkPrivSm('recetas/imprimir/', array(
                        'params'   => 'id='.$receta->id_recetas,
                        'btn_type' => 'btn-info',
                        'attrs' => array('target' => '_BLANK'))
                      );

                      if ($receta->paso != 't') {
                        echo $this->usuarios_model->getLinkPrivSm('recetas/salida/', array(
                          'params'   => 'id='.$receta->id_recetas,
                          'btn_type' => 'btn',
                          'attrs' => array())
                        );
                      }

                      if ($receta->paso !== 'p') {
                        echo $this->usuarios_model->getLinkPrivSm('recetas/salidas/', array(
                          'params'   => 'id='.$receta->id_recetas,
                          'btn_type' => 'btn-info',
                          'attrs' => array('rel' => 'superbox-70x550'))
                        );
                      }

                      if ($receta->paso == 'p' && $receta->status === 't')
                      {
                        echo $this->usuarios_model->getLinkPrivSm('recetas/cancelar/', array(
                          'params'   => 'id='.$receta->id_recetas,
                          'btn_type' => 'btn-danger',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de Cancelar la receta?', 'recetas', this); return false;"))
                        );
                      } elseif ($receta->paso == 'p') {
                        echo $this->usuarios_model->getLinkPrivSm('recetas/activar/', array(
                          'params'   => 'id='.$receta->id_recetas,
                          'btn_type' => 'btn',
                          'attrs' => array('onclick' => "msb.confirm('Estas seguro de Activar la receta?', 'recetas', this); return false;"))
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
                'total_rows'    => $recetas['total_rows'],
                'per_page'      => $recetas['items_per_page'],
                'cur_page'      => $recetas['result_page']*$recetas['items_per_page'],
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


      <!-- Modal -->
      <div id="modalOrden" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
        aria-hidden="true" style="width: 80%;left: 25%;top: 40%;height: 600px;">
        <div class="modal-body" style="max-height: 1500px;">
          <iframe id="frmOrdenView" src="" style="width: 100%;height: 800px;"></iframe>
        </div>
      </div>



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
