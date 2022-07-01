(function (closure) {

  closure(window.jQuery, window);

})(function ($, window) {

  $(function(){
    config();

    $('#formManifiestoChofer').keyJump();

    autocompleteLineasT();
    autocompleteLineasTLive();

    onClickEventLiveTabs();

    // Manifiesto Chofer
    doc_mc.loadTicket();
    doc_mc.btnSave();

    // Chofer Foto Firma Manifiesto
    doc_cffm.btnSnapshot();
    doc_cffm.btnSnapshotSave();
    doc_cffm.btnDelCaptura();

    // Acomodo de Embarque
    doc_acoemb.init();

    // CERTIFICADO DE TLC
    doc_tlc.init();

    dataTable();

    // Asigna la funcionalidad de ligar remisiones a facturas
    eventsRemisiones();
    eventsRemoveRemision();

  });

  var config = function () {
    var $menu_dat = $('#menu_dat');

    // Si el menu principal esta desplegado lo oculta.
    if ($menu_dat.find('i').attr('class') === 'icon-arrow-left') {
      $menu_dat.click();
    }

    // Tooltip para los tabs de listado de documentos.
    $('a#docsTab').tooltip({
      'placement': 'right'
    });

    $('#listadoDocs').on('click', 'li', function(event) {
      event.preventDefault();

      var $this = $(this),
          $a = $this.find('a');

      if ($this.attr('data-doc') === '2') {
        setTimeout(function () {
          $('#formEmbarque').keyJump();
        }, 500);
      }

      if ($this.attr('data-doc') === '7') {
        setTimeout(function () {
          $('#formTlc').keyJump();
        }, 500);
      }

      if ($this.attr('data-doc') === '8') {
        setTimeout(function () {
          $('#formManifiestoCamion').keyJump();
        }, 500);
      }
    });
  };

  var eventsRemisiones = function(){
    // Boton guardar remisiones
    $('#save-remisiones').on('click', function(event) {
      var $this = $(this), paramas={
        id_factura: $("#facturaId").val(),
        remisionesIds: [], palletsIds: []
      }; // boton
      $("#remisiones-selected .remision-selected").each(function(index, el) {
        paramas.remisionesIds.push($(this).val());
      });
      $("#pallets-selected .pallet-selected").each(function(index, el) {
        paramas.palletsIds.push($(this).val());
      });

      if (paramas.remisionesIds.length > 0) {
        msb.confirm("Estas seguro de ligar las remisiones a la factura?", "", this, function(){
          $.post(base_url + 'panel/facturacion/ajax_ligar_remisiones', paramas, function(data, textStatus, xhr) {
            noty({"text": data.msg, "layout":"topRight", "type": 'success'});
          }, 'json');
        });
      };
    });

    // Boton Ventas de Remision.
    $('#show-remisiones').on('click', function(event) {
      var $this = $(this); // boton
      $('#modal-remisiones').modal('show');
    });

    $("#remisiones-selected").on('click', '.quitRemision', function(event) {
      event.preventDefault();
      var $parent = $(this).parent(),
      modal_sel = $("#chk-cli-remision-"+$parent.find('.remision-selected').val());
      modal_sel.removeAttr('style');
      modal_sel.find('.chk-cli-remisiones').removeAttr('disabled').removeAttr('checked');
      $parent.remove();
    });

    $('#BtnAddRemisiones').on('click', function(event) {
      if ($('.chk-cli-remisiones:checked').length > 0) {
        $.get(base_url + 'panel/facturacion/ajax_get_unidades', function(unidades) {
          $('.chk-cli-remisiones:checked').each(function(index, el) {
            var $chkRemision = $(this),
                $parent = $chkRemision.parent(),
                jsonObj = jQuery.parseJSON($parent.find('#jsonData').val()),
                $parent_tr = $parent.parent();

            var existRemision = false;
            $('.remision-selected').each(function(index, el) {
              if ($(this).val() === $chkRemision.val()) {
                existRemision = true;
                return false;
              }
            });

            // Si no existe la remision en el listado entonces la agrega.
            if ( ! existRemision) {

              $parent.parent().css('background-color', '#FF9A9D');
              $chkRemision.prop('disabled', true);

              $('#remisiones-selected').append('<label><i class="icon-remove quitRemision"></i> '+$parent_tr.find('td:nth-child(2)').text()+' <input type="hidden" value="' + $chkRemision.val() + '" name="remisionesIds[]" class="remision-selected" id="remision' + $chkRemision.val() + '"></label>');

              var existPallet;
              for (var i in jsonObj) {
                existPallet = false;
                $('.pallet-selected').each(function(index, el) {
                  if ($(this).val() === jsonObj[i]['id_pallet']) {
                    existPallet = true;
                    return false;
                  }
                });

                if (! existPallet) {
                  $('#pallets-selected').append('<input type="hidden" value="' + jsonObj[i]['id_pallet'] + '" name="palletsIds[]" class="pallet-selected" id="pallet' + jsonObj[i]['id_pallet'] + '">');
                }

                addProducto(unidades, {
                  'id': jsonObj[i]['id_clasificacion'],
                  'nombre': jsonObj[i]['nombre'],
                  'cajas': jsonObj[i]['cajas'],
                  'id_pallet': jsonObj[i]['id_pallet'],
                  'id_unidad': jsonObj[i]['id_unidad'],
                  'unidad': jsonObj[i]['unidad'],
                  'id_unidad_clasificacion': jsonObj[i]['id_unidad_clasificacion'],
                  'iva_clasificacion': jsonObj[i]['iva_clasificacion'],
                  'kilos': jsonObj[i]['kilos'],
                  'id_size': jsonObj[i]['id_size'],
                  'size': jsonObj[i]['size'],
                  'id_remision': $chkRemision.val(),
                });
              }
            }
          });

          $('#modal-remisiones').modal('hide');
        }, 'json');
      } else {
        noty({"text": 'Seleccione al menos una remisión para agregarla al listado.', "layout":"topRight", "type": 'error'});
      }
    });
  }

  var eventsRemoveRemision = function () {
    $('#remisiones-selected').on('click', '.remligadasFactura', function(event) {
      msb.confirm('Estas seguro de Quitar la remision?', 'Facturacion', this, function (obj) {
        $.getJSON(base_url + 'panel/facturacion/ajax_remove_remision_fact/',
          {id_remision: $('input.remision-selected', obj).val(), id_factura: $('#facturaId').val() },
          function(data, textStatus) {
            noty({"text": data.msg, "layout":"topRight", "type": 'success'});
        });
        console.log('test', $('input.remision-selected', obj).val());
        obj.remove();
      });
    });
  }

  var dataTable = function () {
    $('.datatable').dataTable({
      // "sDom": "<'row-fluid'<'span6'l><'span6'f>r>t<'row-fluid'<'span12'i><'span12 center'p>>",
      // "sDom": "<'row-fluid'<'span6'f><'span6'p>r>t<'row-fluid'<'span12'i><'span12 center'p>>",
      "sDom": "<'span6'f><'span6'p>",

      // "sPaginationType": "bootstrap",
      "bFilter": true,
      "bPaginate": true,
      "bLengthChange": false,
      "iDisplayLength": 20,
      "bFilter": true,
      // "bSort": false,
      // "bInfo": false,
      "bAutoWidth": false,
      "oLanguage": {
        "sLengthMenu": "_MENU_ registros por página",
        "sInfo": "Mostrando _START_ a _END_ de _TOTAL_ registros",
        "sSearch": "Buscar:",
        "sInfoFiltered": " - filtrando desde _MAX_ registros",
        "sZeroRecords": "No se encontraron registros",
        "sInfoEmpty": "Mostrando _END_ de _TOTAL_ ",
        "oPaginate": {
          "sFirst": "Primera",
          "sPrevious": "Anterior ",
          "sNext": " Siguiente",
          "sLast": "Ultima"
        }
       }
    });

    $('.dataTables_filter').find('input').addClass('span12');
  };

  // Autocomplete lines transportistas.
  var autocompleteLineasT = function () {
    $("#dlinea_trans").autocomplete({
      source: base_url+'panel/lineas_transporte/ajax_get_lineas/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#did_linea").val(ui.item.id);
        $("#dlinea_trans").css("background-color", "#B0FFB0");
        $("#dlinea_tel").val(ui.item.item.telefonos);
        $("#dlinea_ID").val(ui.item.item.id);
      }
    }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $("#did_linea").val("");
          $("#dlinea_trans").css("background-color", "#FFD9B3");
          $("#dlinea_tel").val("");
          $("#dlinea_ID").val("");
        }
    });
  };

  // Autocomplete lines transportistas Live.
  var autocompleteLineasTLive = function () {
    $('#listadoDocs').on('focus', 'input#dlinea_trans:not(.ui-autocomplete-input)', function(event) {
      $(this).autocomplete({
        source: base_url+'panel/lineas_transporte/ajax_get_lineas/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          $("#did_linea").val(ui.item.id);
          $("#dlinea_trans").css("background-color", "#B0FFB0");
          $("#dlinea_tel").val(ui.item.item.telefonos);
          $("#dlinea_ID").val(ui.item.item.id);
        }
      }).keydown(function(event){
        if(event.which == 8 || event == 46) {
          $("#did_linea").val("");
          $("#dlinea_trans").css("background-color", "#FFD9B3");
          $("#dlinea_tel").val("");
          $("#dlinea_ID").val("");
        }
      });
    });
  }

  // Evento on click para los tabs del listado de documentos.
  var onClickEventLiveTabs = function () {
    $('#listadoDocs').on('click', 'li', function(event) {
      var $li = $(this);

      $('#documentoId').val($li.attr('data-doc'));
    });
  };

  // Funciones para el documento manifiesto chofer.
  var doc_mc = (function($, window) {

    // Carga la infomarcion del ticket|folio.
    function loadTicket () {
      $('#loadTicket').on('click', function(event) {
        var $ticket = $('#ticket'),
            $area   = $('#darea'),
            $factura = $('#facturaId');

        if ($area.find('option:selected').val() != '') {
          if ($ticket.val() != '' || $ticket.val() != 0) {
            $.get(base_url + 'panel/documentos/ajax_get_ticket_info/', {idt: $ticket.val(), ida: $area.find('option:selected').val(), idf: $factura.val()}, function(data) {

              // console.log(data);

              if (data !== false) {

                // Datos del chofer
                if (data.hasOwnProperty('chofer')) {
                  $('#dchofer').val(data.chofer.info.nombre);
                  $('#did_chofer').val(data.chofer.info.id_chofer);
                  $('#dchofer_tel').val(data.chofer.info.telefono);
                  $('#dchofer_ID').val(data.chofer.info.id_nextel);
                  $('#dchofer_no_licencia').val(data.chofer.info.no_licencia);
                  $('#dchofer_ife').val(data.chofer.info.no_ife);


                  if (data.chofer.info.url_ife === null || data.chofer.info.url_licencia === null) {
                    $('#alertChofer').css('display', 'block');
                  } else {
                    $('#alertChofer').css('display', 'none');
                  }

                } else {
                  noty({"text": 'El No. Ticket no tiene un chofer asignado', "layout":"topRight", "type": 'error'});
                }

                // Datos camion
                if (data.hasOwnProperty('camion')) {
                  $('#dcamion_placas').val(data.camion.info.placa);
                  $('#did_camion').val(data.camion.info.id_camion);
                  $('#dcamion_marca').val(data.camion.info.marca);
                  $('#dcamion_model').val(data.camion.info.modelo);
                  $('#dcamion_color').val(data.camion.info.color);
                } else {
                  noty({"text": 'El No. Ticket no tiene un camion asignado', "layout":"topRight", "type": 'error'});
                }


              } else {
                noty({"text": 'El No. Ticket no existe para el area especficada', "layout":"topRight", "type": 'error'});
              }

            }, 'json');
          } else {
            noty({"text": 'Especifique un No. de Ticket', "layout":"topRight", "type": 'error'});
          }
        } else {
          noty({"text": 'Seleccione una Area', "layout":"topRight", "type": 'error'});
        }
      });
    }

    // Evento click button.
    function btnSave () {
      $('#listadoDocs').on('click', '#btnSave', function(event) {
        event.preventDefault();

        var json = {};

        // Id de la factura y documento a actualizar.
        json.factura_id   = $('#facturaId').val();
        json.documento_id = $('#documentoId').val();

        // Datos Factura
        json.folio       = $('#dfolio').val();
        json.importe     = $('#dimporte').val();
        json.direccion   = $('#ddireccion').val();
        json.cliente     = $('#dcliente').val();
        json.id_cliente  = $('#did_cliente').val();
        json.fecha       = $('#dfecha').val();

        // Datos Linea Transportista
        json.linea_trans    = $('#dlinea_trans').val();
        json.linea_id       = $('#did_linea').val();
        json.linea_tel      = $('#dlinea_tel').val();
        json.linea_ID       = $('#dlinea_ID').val();
        json.no_carta_porte = $('#dno_carta_porte').val();

        // Datos Area y ticket pesada
        json.area_id     = $('#darea').find('option:selected').val();
        json.no_ticket   = $('#ticket').val();

        // Datos del Chofer
        json.chofer             = $('#dchofer').val();
        json.chofer_id          = $('#did_chofer').val();
        json.chofer_tel         = $('#dchofer_tel').val();
        json.chofer_ID          = $('#dchofer_ID').val();
        json.chofer_no_licencia = $('#dchofer_no_licencia').val();
        json.chofer_ife         = $('#dchofer_ife').val();

        // Datos del Camion
        json.camion_placas            = $('#dcamion_placas').val();
        json.camion_id                = $('#did_camion').val();
        json.camion_placas_econ       = $('#dcamion_placas_econ').val();
        json.camion_marca             = $('#dcamion_marca').val();
        json.camion_model             = $('#dcamion_model').val();
        json.camion_color             = $('#dcamion_color').val();
        json.camion_placas_termo      = $('#dcamion_placas_termo').val();
        json.camion_placas_termo_econ = $('#dcamion_placas_termo_econ').val();

        // console.log(json);

        $.post(base_url + 'panel/documentos/ajax_update_doc/', json, function(data) {

          // Si se actualiza correctamente el documento.
          if (data.passes) {
            noty({"text": 'El documento se actualizo correctamente', "layout":"topRight", "type": 'success'});

            // setTimeout("location.reload(true);", 500);

            refreshWithDoc();

            // Actualiza el listado de los documentos.
            // $('#listadoDocs').html(data.htmlDocs)
          }

        }, 'json');

      });
    }

    return {
      'loadTicket': loadTicket,
      'btnSave': btnSave
    };

  })(window.jQuery, window);

  // Funciones para el documento Chofer Foto Firma Manifiesto.
  var doc_cffm = (function ($, window) {

    function btnSnapshot() {
      $('#listadoDocs').on('click', '#btnSnapshot', function(event) {
        event.preventDefault();

        $.get( base_url + 'panel/documentos/ajax_get_snapshot/', {}, function(data) {
          // console.log(data);

          $('#imgCapture').attr('src', data.base64);
          $('#inputImgCapture').val(data.base64);

        }, 'json');

      });
    }

    function btnSnapshotSave() {
      $('#listadoDocs').on('click', '#btnSnapshotSave', function(event) {
        event.preventDefault();

        if ($('#inputImgCapture').val() !== '') {

          var json = {};

          // Id de la factura y documento a actualizar.
          json.factura_id   = $('#facturaId').val();
          json.documento_id = $('#documentoId').val();

          json.url    = $('#documentoId').val();
          json.base64 = $('#inputImgCapture').val();

          $.post(base_url + 'panel/documentos/ajax_save_snaptshot/', json, function(data) {

            // Si se actualiza correctamente el documento.
            if (data.passes) {
              noty({"text": 'El documento se actualizo correctamente', "layout":"topRight", "type": 'success'});

              // Actualiza el listado de los documentos.
              // $('#listadoDocs').html(data.htmlDocs)

              // setTimeout("location.reload(true);", 1000);
              refreshWithDoc();
            }

          }, 'json');

        } else {
          noty({"text": 'Haga una captura para poder guardarla', "layout":"topRight", "type": 'error'});
        }

      });
    }

    function btnDelCaptura () {
      $('#btn-del-captura').on('click', function(event) {
        if ($('#imgCapture').attr('src') !== '') {
          $('#imgCapture').attr('src', '');
          $('#inputImgCapture').val('');

          var json = {};

          // Id de la factura y documento a actualizar.
          json.factura_id   = $('#facturaId').val();
          json.documento_id = $('#documentoId').val();
          json.url    = '';

          $.post(base_url + 'panel/documentos/ajax_del_snaptshot/', json, function(data) {
            $('#btn-show-captura').remove();
          }, 'json');
        }
      });
    }

    return {
      'btnSnapshot': btnSnapshot,
      'btnSnapshotSave': btnSnapshotSave,
      'btnDelCaptura': btnDelCaptura,
    };

  })(window.jQuery, window);

  // Funciones para el documento acomodo embarque.
  var doc_acoemb = (function ($, window) {

    var lastMove = 0,
        initPos  = 0;

    function init () {
      draggable();
      droppable();
      otrosCheckbox();
      otrosAdd();
      sendForm();
      loadPalletsLibres();

      if ($('#total-kilos-pallets').val() !== '0') {
        $('#kilos-pallets').html($('#total-kilos-pallets').val());
      }
    }

    function draggable () {
      $("div.draggableitem").draggable({
        scroll: true,
        // start: function(){
        //   $(this).data("startingScrollTop",$(this).parent().scrollTop());
        // },
        // drag: function(event,ui){
        //   var st = parseInt($(this).data("startingScrollTop"));
        //   ui.position.top -= $(this).parent().scrollTop() - st;
        // },
        // // helper: 'clone',
        revert : function(event, ui) {
          var $this = $(this);
          // on older version of jQuery use "draggable"
          // $(this).data("draggable")
          $this.data("uiDraggable").originalPosition = {
            top : $this.attr('data-pos-top'),
            left : $this.attr('data-pos-left')
          };
          // return boolean
          return !event;
          // that evaluate like this:
          // return event !== false ? false : true;
        }
        // revert : true
      });

      $("#tblPalletsLibres .draggableitem").each(function(index, el) {
        var $this = $(this), poss = $this.position();
        $this.attr('data-pos-top', poss.top).attr('data-pos-left', poss.left);
      });
    }

    function droppable () {
      $("div#droppable").droppable({
        hoverClass: "ui-state-active",
        // tolerance: "pointer",
        drop: function( event, ui ) {
          // console.log(this);
          // console.log(ui.draggable[0]);

          var $droppable = $(this),
              $draggable = $(ui.draggable[0]),
              $tableDatosEmbarque = $('#tableDatosEmbarque'),

              $tableDETr,

              noPosicion,
              idPallet        = '',
              etiquetas       = '',
              clasificaciones = '',
              cajas           = 0;

          $draggable.parent().parent().find('td').css('background-color', '#F4AF67');

          noPosicion = $droppable.attr('data-no-posicion');

          $tableDETr = $tableDatosEmbarque.find('#noPos'+noPosicion);

          $totalKilosPallet = $('#kilos-pallets');

          idPallet        = $draggable.attr('data-id-pallet');
          etiquetas       = $draggable.attr('data-etiquetas');
          clasificaciones = $draggable.attr('data-clasificaciones');
          calibres        = $draggable.attr('data-calibres');
          cajas           = $draggable.attr('data-cajas');
          kilosPallet     = $draggable.attr('data-kilos-pallet');

          $tableDETr.find('#pid_pallet').val(idPallet);
          $tableDETr.find('#pmarca').val(etiquetas);
          $tableDETr.find('#pclasificacion').val(clasificaciones);
          $tableDETr.find('#pcalibres').val(calibres);
          $tableDETr.find('#pcajas').val(cajas);
          $tableDETr.find('#pcajas-span').html(cajas);

          $droppable.find('p').html(cajas).css('color', 'red');
          $droppable.attr("data-drag", $draggable.attr('data-id-pallet'));

          $totalKilosPallet.html(parseFloat($totalKilosPallet.html()) + parseFloat(kilosPallet));
        },
        out: function( event, ui ) {
          // console.log(this);
          // console.log(ui);

          var $droppable = $(this),
              $draggable = $(ui.draggable[0]),
              $tableDatosEmbarque = $('#tableDatosEmbarque'),
              $tableDETr,

              noPosicion;

          // Si el draggable que sale es el que esta sobre el droppable entra.
          if ($draggable.attr('data-id-pallet') == $droppable.attr('data-drag')) {
            $droppable.find('p').html('Vacio').css('color', 'black');
            $draggable.parent().parent().find('td').css('background-color', 'white');

            noPosicion = $droppable.attr('data-no-posicion');

            $tableDETr = $tableDatosEmbarque.find('#noPos'+noPosicion);

            $tableDETr.find('#pid_pallet').val('');
            $tableDETr.find('#pmarca').val('SAN JORGE');
            $tableDETr.find('#pclasificacion').val('');
            $tableDETr.find('#pcalibres').val('');
            $tableDETr.find('#pcajas').val('');
            $tableDETr.find('#pcajas-span').html('0');

            $totalKilosPallet = $('#kilos-pallets');
            kilosPallet = $draggable.attr('data-kilos-pallet');
            $totalKilosPallet.html(parseFloat($totalKilosPallet.html()) - parseFloat(kilosPallet));

            $droppable.attr("data-drag", '');
          }
        }
      });
    }

    function otrosAdd () {
      $('input#pmarca').on('keyup', function(e) {
        var key   = e.which,
            $this = $(this),
            $tr   = $this.parent().parent(),

            pos;

        if ($tr.find('#potro').is(':checked')) {
          pos = $tr.find('#pno_posicion').val();

          $track = $('div.track'+pos);

          $track.find('p').html($this.val());
        }

      });
    }

    function otrosCheckbox () {
      $('input#potro').on('change', function(event) {
        event.preventDefault();

        var $this = $(this),
            $tr   = $this.parent().parent();

        if ($this.is(':checked')){
          if ($tr.find('#pid_pallet').val() === '') {
            $tr.find('#pmarca').val('').focus();
          } else {
            $this.prop('checked', '');
          }
        } else {
          $tr.find('#pmarca').val('SAN JORGE');

          pos = $tr.find('#pno_posicion').val();

          $track = $('div.track'+pos);
          $track.find('p').html('Vacio');
        }
      });
    }

    function sendForm () {
      $('#sendEmbarque').on('click', function(event) {
        event.preventDefault();

        var $embIdFac = $('#embIdFac'),
            $embIdDoc = $('#embIdDoc'),
            $ctrl     = $('#pctrl_embarque'),

            $formEmbarque = $('#formEmbarque');

        $.post(base_url + 'panel/documentos/ajax_check_ctrl/', {id_fac: $embIdFac.val(), id_doc: $embIdDoc.val(), no_ctrl: $ctrl.val()}, function(data) {

          if (data == '1') {
            noty({"text": 'El Ctrl Embarque ya esta siendo usado.', "layout":"topRight", "type": 'error'});
          }
          else {
            $formEmbarque.submit();
          }

        });

      });
    }

    function loadPalletsLibres() {
      $("#txtPalletsFolios, #txtPalletsClasif").on('keyup', function(event) {
        $.ajax({
            url: base_url + 'panel/documentos/ajax_get_pallets_libres/',
            dataType: "json",
            data: {
                folios: $("#txtPalletsFolios").val(),
                term : $("#txtPalletsClasif").val()
            },
            success: function(data) {
              var html = '';
              for (var i in data) {
                html += '<tr>'+
                  '<td>'+data[i].item.folio+'</td>'+
                  '<td>'+data[i].item.fecha+'</td>'+
                  '<td>'+
                    '<div id="draggable" class="ui-widget-content draggableitem" data-id-pallet="'+data[i].item.id_pallet+'" '+
                      'data-kilos-pallet="'+data[i].item.kilos_pallet+'" data-cajas="'+data[i].item.no_cajas+'" '+
                      'data-clasificaciones="'+data[i].item.clasificaciones+'" data-calibres="'+data[i].item.calibres+'" '+
                      'data-etiquetas="'+data[i].item.etiquetas+'" style="z-index: 10;position: absolute;height: 29px;">'+
                      '<p>'+data[i].item.no_cajas+'</p>'+
                    '</div>'+
                  '</td>'+
                  '<td>'+data[i].item.clasificaciones+'</td>'+
                '</tr>';
              }
              $("#tblPalletsLibres").html(html);

              draggable();
            }
        });
      });
    }

    return {
      'init': init
    };

  })(window.jQuery, window);

  var doc_tlc = (function ($) {

    function init () {
      autoCompleteEmpresas();
      autoCompleteCliente();
    }

    function autoCompleteEmpresas () {
      $("#dempresa").autocomplete({
        source: base_url+'panel/empresas/ajax_get_empresas/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {

          var domicilio;

          $("#dempresa_id").val(ui.item.id);
          $("#dempresa").css("background-color", "#B0FFB0");
          $("#dregistroFiscal").val(ui.item.item.rfc);

          domicilio = buildDomicilio(ui.item.item);
          $('#ddomicilio').val(domicilio);
        }
      }).on("keydown", function(event){
          if(event.which == 8 || event == 46){
            $("#dempresa_id").val("");
            $("#dempresa").css("background-color", "#FFD9B3");
            $("#dregistroFiscal").val("");
            $('#ddomicilio').val("");
          }
      });
    }

    function autoCompleteCliente () {
      $("#dcliente_tlc").autocomplete({
        source: base_url+'panel/clientes/ajax_get_proveedores/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {

          var domicilio;

          $("#dcliente_id_tlc").val(ui.item.id);
          $("#dcliente_tlc").css("background-color", "#B0FFB0");
          $("#dcliente_no_reg_fiscal_tlc").val(ui.item.item.rfc);

          domicilio = buildDomicilio(ui.item.item);
          $('#dcliente_domicilio').val(domicilio);
        }
      }).on("keydown", function(event){
          if(event.which == 8 || event == 46){
            $("#dcliente_id_tlc").val("");
            $("#dcliente_tlc").css("background-color", "#FFD9B3");
            $("#dcliente_no_reg_fiscal_tlc").val("");
            $('#dcliente_domicilio').val("");
          }
      });
    }

    return {
      'init': init
    };

  })(jQuery);

  function buildDomicilio (data) {
    var domicilio = [];

    if (data.hasOwnProperty('calle') && data.calle !== '') domicilio.push(data.calle);
    if (data.hasOwnProperty('no_exterior') && data.no_exterior !== '') domicilio.push(data.no_exterior);
    if (data.hasOwnProperty('no_interior') && data.no_interior !== '') domicilio.push(data.no_interior);
    if (data.hasOwnProperty('colonia') && data.colonia !== '') domicilio.push(data.colonia);
    if (data.hasOwnProperty('localidad') && data.localidad !== '') domicilio.push(data.localidad);
    if (data.hasOwnProperty('municipio') && data.municipio !== '') domicilio.push(data.municipio);
    if (data.hasOwnProperty('estado') && data.estado !== '') domicilio.push(data.estado);
    if (data.hasOwnProperty('pais') && data.pais !== '') domicilio.push(data.pais);

    return domicilio.join(' ', domicilio);
  }

  var refreshWithDoc = function () {
    setTimeout(function () {
      window.location.href = base_url + 'panel/documentos/agregar/?id='+$('#facturaId').val()+'&ds='+$('#documentoId').val();
    }, 500);
  };

});