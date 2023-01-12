(function (closure) {
  closure(jQuery, window);
})(function ($, window) {
  $(function () {

    obtenRemisionesAjax();
    cargaRemisiones();
    btnDelRemision();
    onChangeTotalRemisiones();
    btnAddSueldo();
    btnDelSueldo();
    onChanceImporteSueldo();
    obtenRepMantAjax();
    cargaRepMant();
    btnAddRepMant();
    btnDelRepMant();
    onChangeTotalRepMant();
    btnAddGastos();
    btnDelGastos();
    onChanceImporteGastos();
    obtenGastosCajaAjax();

    autocompleteEmpresas();
    autocompleteActivos();
    autocompleteChofer();
    autocompleteProveedoresLive();
    autocompleteCodGastosLive();

    chkcomprobacion();
  });

  var submitForm = function () {
    $('#frmcajachica').submit(function(event) {
      $('#btnGuardar').attr('disabled', 'disabled');
      $('#btnGuardar2').attr('disabled', 'disabled');
      $('#logo1').focus();
      if ($('#btnGuardar').length == 0) {
        event.preventDefault();
      }
    });
  }

  var chkcomprobacion = function() {
    $('body').on('change', '.chkcomprobacion', function() {
      if($(this).is(':checked')) {
        $(this).parent().find('.valcomprobacion').val('true');
      } else {
        $(this).parent().find('.valcomprobacion').val('');
      }
    });
  }

  var obtenRemisionesAjax = function () {
    $('#modal-remisiones').on('show', function () {
      $.getJSON(base_url+'panel/estado_resultado_trans/ajax_get_remisiones', function(json, textStatus) {
        var html = '';
        for (var key in json) {
          html += '<tr>'+
              '<td><input type="checkbox" class="chk-remision" data-id="'+json[key].id_factura+'" '+
                'data-numremision="'+json[key].serie+json[key].folio+'" data-total="'+json[key].subtotal+'" '+
                'data-cliente="'+json[key].cliente+'" '+
                'data-fecha="'+json[key].fecha+'"></td>'+
              '<td style="width: 66px;">'+json[key].fecha+'</td>'+
              '<td>'+json[key].serie+json[key].folio+'</td>'+
              '<td>'+json[key].cliente+'</td>'+
              '<td style="text-align: right;">'+json[key].subtotal+'</td>'+
            '</tr>';
        }

        $('#modal-remisiones #lista_remisiones_modal tbody').html(html);
        $("#lista_remisiones_modal").filterTable();
      });
    });
  };
  var cargaRemisiones = function () {
    $('#carga-remisiones').on('click', function(event) {
      var $table = $('#table-remisiones').find('#table-rem-tbody'),
          html = '',
          $this;

      if ($('.chk-remision:checked').length > 0) {
        $('.chk-remision:checked').each(function(index, el) {
          $this = $(this);

          agregarRemisiones({
            id: $this.attr('data-id'),
            numremision: $this.attr('data-numremision'),
            total: $this.attr('data-total'),
            cliente: $this.attr('data-cliente'),
            fecha: $this.attr('data-fecha')
          });
        });

        // $(html).appendTo($table);
        calculaTotalRemisiones();

        $('#modal-remisiones').modal('hide');
      } else {
        noty({"text": 'Seleccione al menos una remision.', "layout":"topRight", "type": 'error'});
      }
    });
  };
  var btnDelRemision = function () {
    $('#table-remisiones').on('click', '.btn-del-remision', function(event) {
      let $tr = $(this).parents('tr');
      // if($tr.find('.remision-id').val() !== '') {
        // $tr.find('#remision_del').val('true')
      // } else {
        $tr.remove();
      // }
      calculaTotalRemisiones();
    });
  };
  var agregarRemisiones = function (remision) {
    var $table = $('#table-remisiones').find('tbody .row-total'),
        tr;

    if ($('#table-remisiones').find('.remision-id[value='+remision.id+']').length == 0) {
      var numRemision = '', folio = '', id = '', total = '0', cliente = '', fecha = '';
      if (remision) {
        id           = remision.id;
        numRemision  = remision.numremision;
        total        = remision.total;
        cliente     = remision.cliente;
        fecha        = remision.fecha;
      }

      tr =  '<tr>' +
              '<td style=""><input type="date" name="remision_fecha[]" value="'+fecha+'" class="remision_fecha" placeholder="Fecha" readonly></td>' +
              '<td style=""><input type="text" name="remision_numero[]" value="'+numRemision+'" class="remision-numero vpositive" placeholder="" readonly style=""></td>' +
              '<td colspan="3">' +
                '<input type="text" name="remision_cliente[]" value="'+cliente+'" class="remision-cliente span12" maxlength="500" placeholder="Nombre" required readonly>' +
                '<input type="hidden" name="remision_id[]" value="'+id+'" class="remision-id span12" required>' +
                '<input type="hidden" name="remision_row[]" value="" class="input-small vpositive remision_row">' +
              '</td>' +
              '<td style=""><input type="number" step="any" name="remision_importe[]" value="'+total+'" class="remision-importe vpositive" placeholder="Importe" required readonly></td>' +
              '<td style="">' +
                '<input type="checkbox" value="true" class="chkcomprobacion">' +
                '<input type="hidden" name="remision_comprobacion[]" value="" class="valcomprobacion">' +
              '</td>' +
              '<td style="width: 30px;">'+
                '<button type="button" class="btn btn-danger btn-del-remision" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>'+
                '<input type="hidden" name="remision_del[]" value="" id="remision_del">'+
              '</td>' +
            '</tr>';
      $(tr).insertBefore($table);
      $(".vpositive").numeric({ negative: false }); //Numero positivo
    } else {
      alert("Ya esta agregada la remisión "+remision.numremision+" no puedes agregarla en el mismo estado.");
    }
  };
  var calculaTotalRemisiones = function () {
    var total = 0;
    $('#table-remisiones .remision-importe').each(function(index, el) {
      total += parseFloat($(this).val());
    });

    // calculaTotalIngresos();

    $('#total-ingresosRemisiones').val(util.darFormatoNum(total.toFixed(2)));
  };
  var onChangeTotalRemisiones = function () {
    $('#table-remisiones').on('keyup', '.remision-importe', function(e) {
      var key = e.which,
          $this = $(this),
          $tr = $this.parent().parent();

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaTotalRemisiones();
      }
    });
  };


  var btnAddSueldo = function () {
    $('#btn-add-sueldos').on('click', function(event) {
      agregarSueldo();
    });
  };
  var agregarSueldo = function () {
    var $table = $('#table-sueldos').find('tbody .row-total'),
        tr = '<tr>'+
                '<td><input type="date" name="sueldos_fecha[]" value="" required></td>'+
                '<td>'+
                  '<input type="hidden" name="sueldos_id_sueldo[]" value="" id="sueldos_id_sueldo">'+
                  '<input type="hidden" name="sueldos_del[]" value="" id="sueldos_del">'+
                  '<input type="text" name="sueldos_proveedor[]" value="" class="span12 autproveedor" required>'+
                  '<input type="hidden" name="sueldos_proveedor_id[]" value="" class="span12 vpositive autproveedor-id">'+
                '</td>'+
                '<td style="">'+
                  '<input type="text" name="sueldos_concepto[]" value="" class="span12 sueldos-concepto" required>'+
                '</td>'+
                '<td style="width: 60px;"><input type="text" name="sueldos_importe[]" value="" class="span12 vpositive sueldos-importe" required></td>'+
                '<td style="">' +
                  '<input type="checkbox" value="true" class="chkcomprobacion">' +
                  '<input type="hidden" name="sueldos_comprobacion[]" value="" class="valcomprobacion">' +
                '</td>' +
                '<td style="width: 30px;">'+
                  '<button type="button" class="btn btn-danger btn-del-sueldos" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>'+
                  '<input type="hidden" name="sueldos_del[]" value="" id="sueldos_del">'+
                '</td>' +
              '</tr>';

    $(tr).insertBefore($table);
    $(".vpositive").numeric({ negative: false }); //Numero positivo
  };
  var btnDelSueldo = function () {
    $('#table-sueldos').on('click', '.btn-del-sueldos', function(event) {
      var $tr = $(this).parents('tr'),
          // id = $tr.find('.gasto-cargo-id').val(),
          // $totalRepo = $('#repo-'+id).find('.reposicion-importe'),
          $sueldos_id_sueldo = $tr.find('#sueldos_id_sueldo'),
          $sueldos_del = $tr.find('#sueldos_del'),
          total = 0;

      if ($sueldos_id_sueldo.val() != '') {
        $sueldos_del.val('true');
        $tr.css('display', 'none');
      } else {
        $tr.remove();
      }

      calculaTotalSueldo();
      // calculaCorte();
    });
  };
  var calculaTotalSueldo = function () {
    var total = 0;
    $('#table-sueldos .sueldos-importe').each(function(index, el) {
      total += parseFloat($(this).val() || 0);
    });
    $('input#ttotal-sueldos').val(total.toFixed(2));
  };
  var onChanceImporteSueldo = function () {
    $('#table-sueldos').on('keyup', '.sueldos-importe', function(e) {
      var key = e.which,
          $this = $(this),
          $t = $('#table-sueldos'),
          total = 0;

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaTotalSueldo();
        // calculaCorte();
      }
    });
  };


  var obtenRepMantAjax = function () {
    $('#modal-repmant').on('show', function () {
      $.getJSON(base_url+'panel/estado_resultado_trans/ajax_get_repmant', function(json, textStatus) {
        var html = '';
        for (var key in json) {
          html += '<tr>'+
              '<td><input type="checkbox" class="chk-repmant" data-id="'+json[key].id_compra+'" '+
                'data-folio="'+json[key].folio+'" data-total="'+json[key].subtotal+'" '+
                'data-proveedor="'+json[key].proveedor+'" '+
                'data-concepto="'+json[key].concepto+'" '+
                'data-fecha="'+json[key].fecha+'"></td>'+
              '<td style="width: 66px;">'+json[key].fecha+'</td>'+
              '<td>'+json[key].folio+'</td>'+
              '<td>'+json[key].proveedor+'</td>'+
              '<td style="text-align: right;">'+json[key].subtotal+'</td>'+
            '</tr>';
        }

        $('#modal-repmant #lista_repmant_modal tbody').html(html);
        $("#lista_repmant_modal").filterTable();
      });
    });
  };
  var cargaRepMant = function () {
    $('#carga-repmant').on('click', function(event) {
      var html = '',
          $this;

      if ($('.chk-repmant:checked').length > 0) {
        $('.chk-repmant:checked').each(function(index, el) {
          $this = $(this);

          agregarRepMant({
            id: $this.attr('data-id'),
            folio: $this.attr('data-folio'),
            total: $this.attr('data-total'),
            proveedor: $this.attr('data-proveedor'),
            concepto: $this.attr('data-concepto'),
            fecha: $this.attr('data-fecha')
          });
        });

        calculaTotalRepMant();

        $('#modal-repmant').modal('hide');
      } else {
        noty({"text": 'Seleccione al menos una compra.', "layout":"topRight", "type": 'error'});
      }
    });
  };
  var btnDelRepMant = function () {
    $('#table-repmant').on('click', '.btn-del-repmant', function(event) {
      let $tr = $(this).parents('tr')
      // if($tr.find('.repmant-id').val() !== '') {
        // $tr.find('#repmant_del').val('true')
      // } else {
        $tr.remove();
      // }
      calculaTotalRepMant();
    });
  };
  var btnAddRepMant = function () {
    $('#btn-add-repmant').on('click', function(event) {
      agregarRepMant({
        id: '',
        folio: '',
        total: '',
        proveedor: '',
        concepto: '',
        fecha: ''
      });
    });
  };
  var agregarRepMant = function (compra) {
    var $table = $('#table-repmant').find('tbody .row-total'),
        tr;

    if ($('#table-repmant').find('.repmant-id[value='+compra.id+']').length == 0 || compra.id == '') {
      var folio = '', folio = '', id = '', total = '0', proveedor = '', concepto = '', fecha = '';
      if (compra) {
        id        = compra.id;
        folio     = compra.folio;
        total     = compra.total;
        proveedor = compra.proveedor;
        concepto  = compra.concepto;
        fecha     = compra.fecha;
      }

      const readonly = id > 0 ? 'readonly' : '';

      tr =  '<tr>' +
              '<td style=""><input type="date" name="repmant_fecha[]" value="'+fecha+'" class="repmant_fecha" placeholder="Fecha" '+readonly+'></td>' +
              '<td style=""><input type="text" name="repmant_numero[]" value="'+folio+'" class="repmant-numero vpositive" placeholder="" '+readonly+' style=""></td>' +
              '<td colspan="3">' +
                '<input type="text" name="repmant_proveedor[]" value="'+proveedor+'" class="repmant-proveedor span12" maxlength="500" placeholder="Nombre" required '+readonly+'>' +
                '<input type="hidden" name="repmant_id[]" value="'+id+'" class="repmant-id span12">' +
                '<input type="hidden" name="repmant_row[]" value="" class="input-small vpositive repmant_row">' +
              '</td>' +
              '<td style=""><input type="text" name="repmant_concepto[]" value="'+concepto+'" class="repmant-concepto" placeholder="Concepto" '+readonly+'></td>' +
              '<td style=""><input type="number" step="any" name="repmant_importe[]" value="'+total+'" class="repmant-importe vpositive" placeholder="Importe" required '+readonly+'></td>' +
              '<td style="">' +
                '<input type="checkbox" value="true" class="chkcomprobacion">' +
                '<input type="hidden" name="repmant_comprobacion[]" value="" class="valcomprobacion">' +
              '</td>' +
              '<td style="width: 30px;">' +
                '<button type="button" class="btn btn-danger btn-del-repmant" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>' +
                '<input type="hidden" name="repmant_del[]" value="" id="repmant_del">' +
              '</td>' +
            '</tr>';
      $(tr).insertBefore($table);
      $(".vpositive").numeric({ negative: false }); //Numero positivo
    } else {
      alert("Ya esta agregada la compra "+compra.folio+" no puedes agregarla en el mismo estado.");
    }
  };
  var calculaTotalRepMant = function () {
    var total = 0;
    $('#table-repmant .repmant-importe').each(function(index, el) {
      total += parseFloat($(this).val());
    });

    // calculaTotalIngresos();

    $('#total-repmant').val(util.darFormatoNum(total.toFixed(2)));
  };
  var onChangeTotalRepMant = function () {
    $('#table-repmant').on('keyup', '.repmant-importe', function(e) {
      var key = e.which,
          $this = $(this),
          $tr = $this.parent().parent();

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaTotalRepMant();
      }
    });
  };


  var btnAddGastos = function () {
    $('#btn-add-gastos').on('click', function(event) {
      agregarGastos();
    });
  };
  var agregarGastos = function () {
    var $table = $('#table-gastos').find('tbody .row-total'),
        tr = '<tr>'+
                '<td><input type="date" name="gastos_fecha[]" value="" required></td>'+
                '<td>'+
                  '<input type="hidden" name="gastos_id_gasto[]" value="" id="gastos_id_gasto">'+
                  '<input type="text" name="gastos_proveedor[]" value="" class="span12 autproveedor" required>'+
                  '<input type="hidden" name="gastos_proveedor_id[]" value="" class="span12 vpositive autproveedor-id">'+
                '</td>'+
                '<td style="">'+
                  '<input type="text" name="gastos_codg[]" value="" class="span12 codsgastos" required>'+
                  '<input type="hidden" name="gastos_codg_id[]" value="" class="span12 vpositive codsgastos-id">'+
                '</td>'+
                '<td style="width: 60px;"><input type="text" name="gastos_importe[]" value="" class="span12 vpositive gastos-importe" required></td>'+
                '<td style="">' +
                  '<input type="checkbox" value="true" class="chkcomprobacion">' +
                  '<input type="hidden" name="gastos_comprobacion[]" value="" class="valcomprobacion">' +
                '</td>' +
                '<td style="width: 30px;">'+
                  '<button type="button" class="btn btn-danger btn-del-gastos" style="padding: 2px 7px 2px;"><i class="icon-remove"></i></button>'+
                  '<input type="hidden" name="gastos_del[]" value="" id="gastos_del">'+
                '</td>' +
              '</tr>';

    $(tr).insertBefore($table);
    $(".vpositive").numeric({ negative: false }); //Numero positivo
  };
  var btnDelGastos = function () {
    $('#table-gastos').on('click', '.btn-del-gastos', function(event) {
      var $tr = $(this).parents('tr'),
          // id = $tr.find('.gasto-cargo-id').val(),
          // $totalRepo = $('#repo-'+id).find('.reposicion-importe'),
          $gastos_id_gasto = $tr.find('#gastos_id_gasto'),
          $gastos_del = $tr.find('#gastos_del'),
          total = 0;

      if ($gastos_id_gasto.val() != '') {
        $gastos_del.val('true');
        $tr.css('display', 'none');
      } else {
        $tr.remove();
      }

      calculaTotalGastos();
      // calculaCorte();
    });
  };
  var calculaTotalGastos = function () {
    var total = 0;
    $('#table-gastos .gastos-importe').each(function(index, el) {
      total += parseFloat($(this).val() || 0);
    });
    $('input#ttotal-gastos').val(total.toFixed(2));
  };
  var onChanceImporteGastos = function () {
    $('#table-gastos').on('keyup', '.gastos-importe', function(e) {
      var key = e.which,
          $this = $(this),
          $t = $('#table-gastos'),
          total = 0;

      if ((key > 47 && key < 58) || (key >= 96 && key <= 105) || key === 8) {
        calculaTotalGastos();
        // calculaCorte();
      }
    });
  };


  var obtenGastosCajaAjax = function () {
    $('#modal-gastoscaja').on('show', function () {
      $.getJSON(base_url+'panel/estado_resultado_trans/ajax_get_gastos_caja', function(json, textStatus) {
        var html = '';
        for (var key in json) {
          html += '<tr>'+
              '<td><input type="checkbox" class="chk-remision" data-id="'+json[key].id_gasto+'" '+
                'data-folio="'+json[key].folio+'" data-total="'+json[key].monto+'" '+
                'data-nombre="'+json[key].nombre+'" '+
                'data-abreviatura="'+json[key].abreviatura+'" '+
                'data-concepto="'+json[key].concepto+'" '+
                'data-fecha="'+json[key].fecha+'"></td>'+
              '<td style="width: 66px;">'+json[key].fecha+'</td>'+
              '<td>'+json[key].folio+'</td>'+
              '<td>'+json[key].abreviatura+'</td>'+
              '<td>'+json[key].concepto+'</td>'+
              '<td>'+json[key].nombre+'</td>'+
              '<td style="text-align: right;">'+json[key].monto+'</td>'+
            '</tr>';
        }

        $('#modal-gastoscaja #lista_gastoscaja_modal tbody').html(html);
        $("#lista_gastoscaja_modal").filterTable();
      });
    });

    $('#modal-gastoscaja').on('change', '.chk-remision', function(){
      console.log($(this).attr('data-id'));
      $('#modal-gastoscaja .chk-remision:not([data-id='+$(this).attr('data-id')+'])').prop('checked', false);
    });

    $('#carga-gastoscaja').click(function(event) {
      const sel = $('#modal-gastoscaja .chk-remision:checked');
      if(sel.length > 0) {
        $('#did_gasto').val(sel.attr('data-id'));
        $('#gasto_monto').val(sel.attr('data-total'));
        $('#modal-gastoscaja').modal('hide');
      } else {
        noty({"text": 'Seleccione un gasto.', "layout":"topRight", "type": 'error'});
      }
    });
  };


  var autocompleteEmpresas = function () {
    $("#dempresa").autocomplete({
      source: base_url + 'panel/empresas/ajax_get_empresas/',
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var dempresa =  $(this);

        dempresa.val(ui.item.id);
        $("#did_empresa").val(ui.item.id);
        dempresa.css("background-color", "#A1F57A");
      }
    }).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#dempresa").css("background-color", "#FFD071");
        $("#did_empresa").val('');
      }
    });
  };

  var autocompleteActivos = function () {
    $("#dactivo").autocomplete({
      source: function(request, response) {
        var params = {term : request.term};
        // if(parseInt($("#empresaApId").val()) > 0)
        //   params.did_empresa = $("#empresaApId").val();
        params.tipo = 'a'; // activos
        $.ajax({
            url: base_url + 'panel/productos/ajax_aut_productos/',
            dataType: "json",
            data: params,
            success: function(data) {
              response(data);
            }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        var dempresa =  $(this);

        dempresa.val(ui.item.id);
        $("#did_activo").val(ui.item.id);
        dempresa.css("background-color", "#A1F57A");
      }
    }).css('z-index', 1011).on("keydown", function(event) {
      if(event.which == 8 || event.which == 46) {
        $("#dactivo").css("background-color", "#FFD071");
        $("#did_activo").val('');
      }
    });
  };

  var autocompleteChofer = function () {
    // Autocomplete Chofer
    $("#dchofer").autocomplete({
      // source: base_url + 'panel/bascula/ajax_get_choferes/',
      source: function(request, response) {
        params = {term : request.term};
        params['alldata'] = 'true'; // salidas

        $.ajax({
            url: base_url + 'panel/bascula/ajax_get_choferes/',
            dataType: "json",
            data: params,
            success: function(data) {
                response(data);
            }
        });
      },
      minLength: 1,
      selectFirst: true,
      select: function( event, ui ) {
        $("#did_chofer").val(ui.item.id);
        $("#dchofer").val(ui.item.label).css({'background-color': '#99FF99'});
      }
    }).keydown(function(e){
      if (e.which === 8) {
        $(this).css({'background-color': '#FFD9B3'});
        $('#did_chofer').val('');
      }
    });
  };

  var autocompleteProveedoresLive = function () {
    console.log('autocompleteProveedoresLive');
    $('body').on('focus', '.autproveedor:not(.ui-autocomplete-input)', function(event) {
      console.log('autocompleteProveedoresLive Focus');
      $(this).autocomplete({
        // source: base_url+'panel/estado_resultado_trans/ajax_get_proveedores/',
        source: function(request, response) {
          var params = {term : request.term};
          if(parseInt($("#did_empresa").val()) > 0)
            params.did_empresa = $("#did_empresa").val();
          $.ajax({
              url: base_url + 'panel/estado_resultado_trans/ajax_get_proveedores/',
              dataType: "json",
              data: params,
              success: function(data) {
                response(data);
              }
          });
        },
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          $(this).parents('tr').find(".autproveedor-id").val(ui.item.id);
          $(this).css("background-color", "#B0FFB0");
        }
      }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $(this).parents('tr').find(".autproveedor-id").val("");
          $(this).val("").css("background-color", "#FFD9B3");
        }
      });
    });
  };

  var autocompleteCodGastosLive = function () {
    console.log('autocompleteCodGastosLive');
    $('body').on('focus', '.codsgastos:not(.ui-autocomplete-input)', function(event) {
      console.log('autocompleteCodGastosLive Focus');
      $(this).autocomplete({
        source: base_url+'panel/estado_resultado_trans/ajax_get_cods/',
        minLength: 1,
        selectFirst: true,
        select: function( event, ui ) {
          $(this).parents('tr').find(".codsgastos-id").val(ui.item.id);
          $(this).css("background-color", "#B0FFB0");
        }
      }).on("keydown", function(event){
        if(event.which == 8 || event == 46){
          $(this).parents('tr').find(".codsgastos-id").val("");
          $(this).css("background-color", "#FFD9B3");
        }
      });
    });
  };

});
