<div class="tabbable tabs-left">

  <?php
      $htmlLi = '';
      $htmlContent = '';

      $params['dataFactura']   = $factura;
      $params['dataAreas']     = $areas;
      $params['dataPallets']   = $pallets; // Pallets disponibles
      $params['dataDocumento'] = array();
      $params['finalizados']   = $is_finalizados;

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

          $label = '<span class="label label-'.($doc->status === 't' ? 'success' : 'important').'"><i class="icon-li icon-'.($doc->status === 't' ? 'ok-sign' : 'remove-sign').'"></i></span>';

          $htmlLi .= '<li class="'.$active.'" data-doc="'.$doc->id_documento.'"><a href="#doc'.$doc->id_documento.'" data-toggle="tab" id="docsTab" title="'.$doc->nombre.'" style="min-width: 20px;">'.$label.'<p></p></a></li>';

          if ($doc->data !== '')
          {
            $params['dataDocumento'] = json_decode($doc->data);
            $params['idDocumento']   = $doc->id_documento;
          }

          if ($doc->nombre === 'ACOMODO DEL EMBARQUE')
            $params['dataEmbarque'] = $this->documentos_model->getEmbarqueData($factura['info']->id_factura, $doc->id_documento);

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