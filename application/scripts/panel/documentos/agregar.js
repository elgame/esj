(function (closure) {

  closure(window.jQuery, window);

})(function ($, window) {

  $(function(){
    config();

    autocompleteLineasT();
    autocompleteLineasTLive();

    onClickEventLiveTabs();

    // Manifiesto Chofer
    doc_mc.loadTicket();
    doc_mc.btnSave();
    doc_mc.btnImprimir();

    // Chofer Foto Firma Manifiesto
    doc_cffm.btnSnapshot();
    doc_cffm.btnSnapshotSave();

    // Acomodo de Embarque
    doc_acoemb.init();


    dataTable();
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
  };

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

              console.log(data);

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

            // Actualiza el listado de los documentos.
            $('#listadoDocs').html(data.htmlDocs)
          }

        }, 'json');

      });
    }

    function btnImprimir () {
      $('#listadoDocs').on('click', '#btnPrint', function(event) {
        var factura_id   = $('#facturaId').val(),
            documento_id = $('#documentoId').val();

        var win = window.open(base_url + 'panel/documentos/imprime_manifiesto_chofer?idf=' + factura_id + '&idd=' + documento_id, '_blank');
        win.focus();

      });
    }

    return {
      'loadTicket': loadTicket,
      'btnSave': btnSave,
      'btnImprimir': btnImprimir,
    }

  })(window.jQuery, window);

  // Funciones para el documento Chofer Foto Firma Manifiesto.
  var doc_cffm = (function ($, window) {

    function btnSnapshot() {
      $('#listadoDocs').on('click', '#btnSnapshot', function(event) {
        event.preventDefault();

        $.get( base_url + 'panel/documentos/ajax_get_snapshot/', {}, function(data) {
          console.log(data);

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

              setTimeout("location.reload(true);", 1000);
            }

          }, 'json');

        } else {
          noty({"text": 'Haga una captura para poder guardarla', "layout":"topRight", "type": 'error'});
        }

      });
    }

    return {
      'btnSnapshot': btnSnapshot,
      'btnSnapshotSave': btnSnapshotSave
    };

  })(window.jQuery, window);


  // Funciones para el documento acomodo embarque.
  var doc_acoemb = (function ($, window) {

    function init () {
      draggable();
      droppable();
      otrosCheckbox();
      otrosAdd();
      sendForm();
    }

    function draggable () {
      $("div#draggable").draggable({
        scroll: true,
        revert : function(event, ui) {
          // on older version of jQuery use "draggable"
          // $(this).data("draggable")
          $(this).data("uiDraggable").originalPosition = {
              top : 0,
              left : 0
          };
          // return boolean
          return !event;
          // that evaluate like this:
          // return event !== false ? false : true;
        }
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
              clasificaciones = '',
              cajas           = 0;

          $draggable.parent().parent().find('td').css('background-color', '#F4AF67');

          noPosicion = $droppable.attr('data-no-posicion');

          $tableDETr = $tableDatosEmbarque.find('#noPos'+noPosicion);

          idPallet        = $draggable.attr('data-id-pallet');
          clasificaciones = $draggable.attr('data-clasificaciones');
          cajas           = $draggable.attr('data-cajas');

          $tableDETr.find('#pid_pallet').val(idPallet);
          $tableDETr.find('#pclasificacion').val(clasificaciones);
          $tableDETr.find('#pcajas').val(cajas);


          $droppable.find('p').html(cajas).css('color', 'red');
          $droppable.attr("data-drag", $draggable.attr('data-id-pallet'));
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
            $tableDETr.find('#pclasificacion').val('');
            $tableDETr.find('#pcajas').val('');

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
    return {
      'init': init
    };

  })(window.jQuery, window);



});