<div class="tabbable tabs-left">

  <?php
      $htmlLi = '';
      $htmlContent = '';

      $params['dataFactura'] = $factura;
      $params['dataDocumento'] = array();

      foreach ($documentos as $key => $doc) {
        $active = $key === 0 ? 'active' : '';

        $label = '<span style="float: right;margin-left: 3px;" class="label label-'.($doc->status === 't' ? 'success' : 'important').'">'.($doc->status === 't' ? 'Si' : 'No').'</span>';

        $htmlLi .= '<li class="'.$active.'"><a href="#doc'.$doc->id_documento.'" data-toggle="tab">'.$label.'<p>'.$doc->nombre.'</p></a></li>';

        if ($doc->data !== '')
          $params['dataDocumento'] = array();

        $formContent = $this->load->view($doc->url_form, $params, true);

        $htmlContent .= '<div class="tab-pane '.$active.'" id="doc'.$doc->id_documento.'">'.$formContent.'</div>';
      } ?>

  <ul class="nav nav-tabs" style="margin-right:20px !important; margin-left: 0px !important;">

    <?php echo $htmlLi; ?>

  </ul>
  <div class="tab-content">

    <?php echo $htmlContent; ?>

  </div>