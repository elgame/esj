<!DOCTYPE html>
<!--[if lt IE 7]>      <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]>         <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]>         <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html lang="es" class="no-js"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">

  <title></title>
  <meta name="description" content="">
  <meta name="viewport" content="width=device-width">

<?php
  if(isset($this->carabiner)){
    $this->carabiner->display('css');
    $this->carabiner->display('base_panel');
    $this->carabiner->display('js');
  }
?>

  <!-- The HTML5 shim, for IE6-8 support of HTML5 elements -->
  <!--[if lt IE 9]>
    <script src="http://html5shim.googlecode.com/svn/trunk/html5.js"></script>
  <![endif]-->

<script type="text/javascript" charset="UTF-8">
  var base_url = "<?php echo base_url();?>",
      base_url_bascula = "<?php echo $this->config->item('base_url_bascula');?>",
      base_url_cam_salida_snapshot = "<?php echo $this->config->item('base_url_cam_salida_snapshot') ?> ";
</script>
</head>
<body>
  <div id="content" class="container-fluid">
    <div class="row-fluid">
      <!--[if lt IE 7]>
        <div class="alert alert-info">
          <button type="button" class="close" data-dismiss="alert">×</button>
          <p>Usted está usando un navegador desactualizado. <a href="http://browsehappy.com/">Actualice su navegador</a> o <a href="http://www.google.com/chromeframe/?redirect=true">instale Google Chrome Frame</a> para experimentar mejor este sitio.</p>
        </div>
      <![endif]-->
      <div class="box span12">
        <div class="box-header well" data-original-title>
          <h2><i class="icon-eye-open"></i> Ordenes</h2>
          <div class="box-icon">
            <a href="#" class="btn btn-minimize btn-round"><i class="icon-chevron-up"></i></a>
          </div>
        </div>
        <div class="box-content">
          <table class="table table-striped table-bordered bootstrap-datatable">
              <thead>
                <tr>
                  <th></th>
                  <th>Fecha</th>
                  <th>Folio</th>
                  <th>Proveedor</th>
                  <th>Empresa</th>
                  <th>Autorizada</th>
                  <th>Estado</th>
                  <th>Tipo</th>
                </tr>
              </thead>
              <tbody>
            <?php foreach($ordenes['ordenes'] as $orden) {?>
                <tr>
                  <td>
                    <?php if ($orden->status === 'a' && isset($_GET['did_proveedor']) && $_GET['did_proveedor'] !== '' &&
                              isset($_GET['did_empresa']) && $_GET['did_empresa'] !== ''){ ?>
                      <input type="checkbox" class="addToFactura" value="<?php echo $orden->id_orden ?>" data-folio="<?php echo $orden->folio; ?>">
                    <?php } ?>
                  </td>
                  <td><?php echo substr($orden->fecha, 0, 10); ?></td>
                  <td><span class="label"><?php echo $orden->folio; ?></span></td>
                  <td><?php echo $orden->proveedor; ?></td>
                  <td><?php echo $orden->empresa; ?></td>
                  <td><span class="label label-info"><?php echo $orden->autorizado === 't' ? 'SI' : 'NO'?></span></td>
                  <td><?php
                          $texto = 'CANCELADA';
                          $label = 'warning';
                        if ($orden->status === 'p') {
                          $texto = 'PENDIENTE';
                          $label = 'warning';
                        } else if ($orden->status === 'r') {
                          $texto = 'RECHAZADA';
                          $label = 'warning';
                        } else if ($orden->status === 'a') {
                          $texto = 'ACEPTADA';
                          $label = 'success';
                        } else if ($orden->status === 'f') {
                          $texto = 'FACTURADA';
                          $label = 'success';
                        }
                      ?>
                      <span class="label label-<?php echo $label ?> "><?php echo $texto ?></span>
                  </td>
                  <td><?php
                          $texto = 'Flete';
                          $label = 'warning';
                        if ($orden->tipo_orden === 'p') {
                          $texto = 'Productos';
                          $label = 'warning';
                        } else if ($orden->tipo_orden === 'd') {
                          $texto = 'Descripciones';
                          $label = 'warning';
                        }
                      ?>
                      <span class="label label-<?php echo $label ?> "><?php echo $texto ?></span>
                  </td>
                </tr>
            <?php }?>
              </tbody>
            </table>

            <div>
              <button type="buttom" class="btn btn-info" id="btnCargarOrdenesGasto">Cargar Ordenes</button>
            </div>

            <?php
            //Paginacion
            $this->pagination->initialize(array(
                'base_url'      => base_url($this->uri->uri_string()).'?'.String::getVarsLink(array('pag')).'&',
                'total_rows'    => $ordenes['total_rows'],
                'per_page'      => $ordenes['items_per_page'],
                'cur_page'      => $ordenes['result_page']*$ordenes['items_per_page'],
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


    </div><!--/fluid-row-->
  </div><!--/.fluid-container-->

  <div class="clear"></div>
</body>
</html>