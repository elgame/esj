<div class="tabbable tabs-left">

  <?php
      $htmlLi = '';
      $htmlContent = '';

      $params['dataFactura'] = $factura;
      $params['dataAreas'] = $areas;
      $params['dataDocumento'] = array();

      if ($documentos)
      {
        foreach ($documentos as $key => $doc)
        {
          $active = '';
          if ( $key === 0)
          {
            $active = 'active';

            echo '<input type="hidden" id="documentoId" value="'.$doc->id_documento.'">';
          }

          $label = '<span style="float: right;margin-left: 3px;" class="label label-'.($doc->status === 't' ? 'success' : 'important').'"><i class="icon-li icon-'.($doc->status === 't' ? 'ok-sign' : 'remove-sign').'"></i></span>';

          $htmlLi .= '<li class="'.$active.'" data-doc="'.$doc->id_documento.'"><a href="#doc'.$doc->id_documento.'" data-toggle="tab">'.$label.'<p>'.$doc->nombre.'</p></a></li>';

          if ($doc->data !== '')
            $params['dataDocumento'] = json_decode($doc->data);

          $priv = str_replace('panel/', '', $doc->url_form).'/';
          if ($this->usuarios_model->tienePrivilegioDe('', $priv, false))
            $formContent = $this->load->view($doc->url_form, $params, true);
          else
            $formContent = '<div class="alert alert-error center"><strong>No cuenta con los permisos para editar este documento.</strong></div>';

          $htmlContent .= '<div class="tab-pane '.$active.'" id="doc'.$doc->id_documento.'">'.$formContent.'</div>';
        }
      }
      else
        echo '<div class="alert alert-info center">
                <strong>El cliente no cuenta con documentos asignados.</strong>
              </div>';
      ?>

  <ul class="nav nav-tabs" id="nav-tabs" style="margin-right:20px !important; margin-left: 0px !important;">

    <?php echo $htmlLi; ?>

  </ul>
  <div class="tab-content">

    <?php echo $htmlContent; ?>

  </div>