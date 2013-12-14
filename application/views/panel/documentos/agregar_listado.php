<div class="tabbable tabs-left">

  <?php
      $htmlLi = '';
      $htmlContent = '';

      $params['dataFactura']   = $factura; // Datos de la factura.
      $params['dataAreas']     = $areas;   // Areas.
      $params['dataPallets']   = $pallets; // Pallets disponibles.
      $params['dataDocumento'] = array();  // Datos del documento.
      $params['finalizados']   = $is_finalizados; // Indica si los documentos estan finalizados.
      $params['empresa_default'] = $empresa_default; // Datos de la empresa por default.

      // Si el cliente tiene documentos.
      if ($documentos)
      {
        // Recorre los documentos para cargar sus vistas y datos en caso de tener.
        foreach ($documentos as $key => $doc)
        {
          $priv = str_replace('panel/', '', $doc->url_form).'/';

          // Si el usuario tiene el privilegio de editar el documento entra.
          if ($this->usuarios_model->tienePrivilegioDe('', $priv, false))
          {
            $params['doc'] = $doc;

            $active = '';

            if (isset($_GET['ds']))
            {
              if ($_GET['ds'] == $doc->id_documento)
              {
                $active = 'active';

                echo '<input type="hidden" id="documentoId" value="'.$doc->id_documento.'">';
              }
            }
            else if ($key === 0)
            {
              $active = 'active';

              echo '<input type="hidden" id="documentoId" value="'.$doc->id_documento.'">';
            }

            $label = '<span class="label label-'.($doc->status === 't' ? 'success' : 'important').'"><i class="icon-li icon-'.($doc->status === 't' ? 'ok-sign' : 'remove-sign').'"></i></span>';

            $htmlLi .= '<li class="'.$active.'" data-doc="'.$doc->id_documento.'"><a href="#doc'.$doc->id_documento.'" data-toggle="tab" id="docsTab" title="'.$doc->nombre.'" style="min-width: 20px;">'.$label.'<p></p></a></li>';

            // Si el documento contiene datos en el json.
            if ($doc->data !== '')
            {
              $params['dataDocumento'] = json_decode($doc->data);
              $params['idDocumento']   = $doc->id_documento;
            }

            // Si el documento es el ACOMODO DEL EMBARQUE.
            if ($doc->nombre === 'ACOMODO DEL EMBARQUE')
            {
              $params['dataEmbarque'] = $this->documentos_model->getEmbarqueData($factura['info']->id_factura, $doc->id_documento);
            }

            // Si el documento es el MANIFIESTO DEL CAMION entonces obtiene los datos de embarque y del manifiesto chofer.
            if ($doc->nombre === 'MANIFIESTO DEL CAMION')
            {
              $params['dataManChofer'] = $this->documentos_model->getJsonDataDocus($factura['info']->id_factura, 1);
              $params['dataEmbarque'] = $this->documentos_model->getEmbarqueData($factura['info']->id_factura, 2);

              $params['dataClasificaciones'] = array('clasificaciones' => array());
              if(isset($params['dataEmbarque']['info']))
                $params['dataClasificaciones'] = $this->documentos_model->getEmbarqueClasifi($params['dataEmbarque']['info'][0]->id_embarque);
            }

            // Carga la vista del documento.
            $formContent = $this->load->view($doc->url_form, $params, true);
          }
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

  <div class="tab-content" style="overflow: hidden !important;">
    <?php echo $htmlContent; ?>
  </div>